<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 4-9-2015
 * Time: 23:07
 */

namespace Models;

use Db;

class Group
{

    protected $readExpensesSql = "SELECT expense_id AS eid, group_id AS gid, cid, type, description AS etitle, user_id AS uid,
                amount, amount, UNIX_TIMESTAMP(expense_date) AS ecreated,
                UNIX_TIMESTAMP(timestamp) AS eupdated, timezoneoffset, event_id, deposit_id AS depid,
                (SELECT GROUP_CONCAT(DISTINCT users_expenses.user_id)
                    FROM users_expenses, users_groups
                    WHERE users_expenses.user_id = users_groups.user_id AND users_expenses.expense_id = eid
                    GROUP BY users_expenses.expense_id
                ) AS uids,
                (SELECT COUNT(DISTINCT expense_id)
                    FROM expenses
                    WHERE deposit_id = depid
                    GROUP BY deposit_id
                ) AS deposit_count
                FROM expenses
                WHERE expenses.group_id = :gid ";

    protected $readExpensesDelSql = "SELECT expense_id AS eid, group_id AS gid, cid, type, description AS etitle, user_id AS uid, uids,
                amount, amount, UNIX_TIMESTAMP(expense_date) AS ecreated, UNIX_TIMESTAMP(delete_date) AS edeleted,
                UNIX_TIMESTAMP(timestamp) AS eupdated, timezoneoffset, event_id, deposit_id AS depid
                FROM expenses_del
                WHERE expenses_del.group_id = :gid ";

    protected $updateGroupDetailsSql = "UPDATE groups SET name=:name, description=:description, currency=:currency WHERE group_id=:gid;";

    function getExpenses($gid)
    {
        $sql = $this->readExpensesSql . "ORDER BY expense_date DESC, eid DESC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        // put the results in an array with gid as key
        $expense_list = array($gid => $stmt->fetchAll(\PDO::FETCH_ASSOC));
        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function getExpensesDel($gid)
    {
        $sql = $this->readExpensesDelSql . "ORDER BY expense_date DESC, eid DESC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        // put the results in an array with gid as key
        $expense_list = array($gid => $stmt->fetchAll(\PDO::FETCH_ASSOC));
        //$expense_list = Member::rearrangeArrayKey('eid', $expense_list);
        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function getExpense($gid, $eid, $json = true)
    {
        $sql = $this->readExpensesSql . "AND expenses.expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($json)
            return json_encode($expense, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        else
            return $expense;
    }

    function getExpenseDel($gid, $eid, $json = true)
    {
        $sql = $this->readExpensesDelSql . "AND expenses_del.expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($json)
            return json_encode($expense, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        else
            return $expense;
    }

    function addExpense($gid, $expense)
    {
        $uids = $expense->uids . ',' . $expense->uid;
        if (!$this->validateUids($uids, $gid)) {
            return 'Error: invalid uids';
        }

        if (!isset($expense->type))
            $expense->type = 1;

        $sql = "INSERT INTO expenses (type, cid, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency, timezoneoffset)
                VALUES (:type, :cid, :user_id, :group_id, :description, :amount, FROM_UNIXTIME(:created), :event_id, FROM_UNIXTIME(:updated), :currency, :timezoneoffset)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':type' => $expense->type,
                ':cid' => $expense->cid,
                ':user_id' => $expense->uid,
                ':group_id' => $gid,
                ':description' => $expense->etitle,
                ':amount' => $expense->amount,
                ':created' => $expense->ecreated,
                ':updated' => $expense->eupdated,
                ':event_id' => $expense->event_id,
                ':timezoneoffset' => $expense->timezoneoffset,
                ':currency' => 1
            )
        );
        $eid = Db::getInstance()->lastInsertId();

        $sql = "INSERT INTO users_expenses (user_id , expense_id) VALUES (:user_id, :eid)";
        $stmt = Db::getInstance()->prepare($sql);
        $uids = explode(',', $expense->uids);
        foreach ($uids as $user_id) {
            $stmt->execute(array(':user_id' => $user_id, ':eid' => $eid));
        }

        $this->addExpenseEmail($expense, $eid);

        return $this->getExpense($gid, $eid);
    }

    function deleteExpense($gid, $eid)
    {
        $expense = $this->getExpense($gid, $eid, false);

        if (!isset($expense['type']))
            $expense['type'] = 1;
        $sql = "INSERT INTO expenses_del (expense_id, type, cid, user_id, group_id, uids, description, amount, expense_date, event_id, timestamp, currency, timezoneoffset, delete_date)
                VALUES (:eid, :type, :cid, :user_id, :group_id, :uids, :description, :amount, FROM_UNIXTIME(:created), :event_id, FROM_UNIXTIME(:updated), :currency, :timezoneoffset, FROM_UNIXTIME(:now))";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':eid' => $expense['eid'],
                ':type' => $expense['type'],
                ':cid' => $expense['cid'],
                ':user_id' => $expense['uid'],
                ':group_id' => $expense['gid'],
                ':uids' => $expense['uids'],
                ':description' => $expense['etitle'],
                ':amount' => $expense['amount'],
                ':created' => $expense['ecreated'],
                ':updated' => $expense['eupdated'],
                ':event_id' => $expense['event_id'],
                ':timezoneoffset' => $expense['timezoneoffset'],
                ':currency' => 1,
                ':now' => time()
            )
        );

        $sql = "DELETE FROM expenses WHERE expense_id = :eid AND group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));

        $sql = "DELETE FROM users_expenses WHERE expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':eid' => $eid));

        $expense = json_decode(json_encode($expense), FALSE);
        $this->addExpenseEmail($expense, $eid, 'delete');

        return $eid;
    }

    function updateExpense($gid, $expense)
    {
        $uids = $expense->uids . ',' . $expense->uid;
        if (!$this->validateUids($uids, $gid)) {
            return 'Error: invalid uids';
        }

        $oldExpense = $this->getExpense($gid, $expense->eid, false);

        // keep track of any users removed from this expense
        $removedUids = array_diff(explode(',', $oldExpense['uids'] . ',' . $oldExpense['uid']), explode(',', $uids));

        if (!isset($expense->type))
            $expense->type = 1;
        $sql = "UPDATE expenses SET type=:type, cid=:cid, user_id=:user_id, description=:description, amount=:amount, event_id=:event_id, timestamp=:updated,
                currency=:currency, timezoneoffset=:timezoneoffset, expense_date=FROM_UNIXTIME(:created), timestamp=FROM_UNIXTIME(:updated)
                WHERE expense_id=:eid AND group_id=:group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':type' => $expense->type,
                ':cid' => $expense->cid,
                ':user_id' => $expense->uid,
                ':group_id' => $gid,
                ':description' => $expense->etitle,
                ':amount' => $expense->amount,
                ':event_id' => $expense->event_id,
                ':timezoneoffset' => $expense->timezoneoffset,
                ':currency' => 1,
                ':eid' => $expense->eid,
                ':updated' => $expense->eupdated,
                ':created' => $expense->ecreated
            )
        );

        $sql = "DELETE FROM users_expenses WHERE expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':eid' => $expense->eid));

        $sql = "INSERT INTO users_expenses (user_id , expense_id) VALUES (:user_id, :eid)";
        $stmt = Db::getInstance()->prepare($sql);
        $uids = explode(',', $expense->uids);
        foreach ($uids as $user_id) {
            $stmt->execute(array(':user_id' => $user_id, ':eid' => $expense->eid));
        }

        $this->addExpenseEmail($expense, $expense->eid, 'update', $removedUids);

        return $this->getExpense($gid, $expense->eid);
    }

    function updateGroupDetails($groupDetails, $uid)
    {
        $uids = array($uid);
        $gIds = array();
        // check user requesting change is part of this group
        foreach ($groupDetails as $detailSet) {
            if (!$this->validateUids($uids, $detailSet->gid)) {
                return 'Error: invalid uid';
            }

            $gIds[] = $detailSet->gid;

            $sql = $this->updateGroupDetailsSql;
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(
                array(
                    ':gid' => $detailSet->gid,
                    ':currency' => $detailSet->currency,
                    ':name' => substr($detailSet->name, 0, 30),
                    ':description' => substr($detailSet->description, 0, 60)
                )
            );
        }

        // return  group details for updated groups
        $gIdStr = implode(',', $gIds);
        $sql = "SELECT group_id AS gid, name, description, currency FROM groups WHERE FIND_IN_SET(group_id, :gIdStr)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gIdStr' => $gIdStr));
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $return = array();
        foreach ($result as $group) {
            $return[$group['gid']] = $group;
        }
        return json_encode($return, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function addGroupMembers($body, $gid, $uid)
    {
        $response = array('success' => 0, 'added' => 0, 'invited' => 0);
        if (empty($body)) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // check if added by admin
        if (!$this->validateIsAdminOfGroup($uid, $gid)) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // get list of user_ids for emails
        $emailList = implode(',', $body->emails);
        $sql = "SELECT user_id, email FROM users WHERE FIND_IN_SET (email, :email)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':email' => $emailList
            )
        );
        $emailUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        // get current group members to avoid adding twice
        $memberList = $this->getGroupUserIds($gid);

        $added = 0;
        // this could be optimized into a single query for speed
        $sql = "INSERT INTO users_groups (user_id, group_id, role_id, join_date)
                VALUES (:user_id, :group_id, :role_id, FROM_UNIXTIME(:submitted))";
        $stmt = Db::getInstance()->prepare($sql);
        foreach ($emailUsers as $invitee) {
            // skip users that are already a group member
            if (in_array($invitee['user_id'], $memberList))
                continue;

            $stmt->execute(
                array(
                    ':user_id' => $invitee['user_id'],
                    ':group_id' => $gid,
                    ':role_id' => 4,
                    ':submitted' => time()
                )
            );
            $added++;
        }

        // TODO: foreach not in list, put in invited table (to be created) and send email

        $response = array('success' => 1, 'added' => $added, 'invited' => 0);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    function changeRole($targetUid, $gid, $request, $rUid){
        $response = array('success' => 0, 'error' => 1, 'invalid_request' => 0);
        if (empty($targetUid) || empty($gid) || empty($request)
            || !isset($request->role_id) || $request->role_id == '' || $request->role_id > 5) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // validate uids are part of the group
        if (!$this->validateUids($rUid . ',' . $targetUid, $gid)) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $keys = array ('user_id', 'group_id', 'role_id', 'removed', 'join_date');
        $sql = "SELECT " .implode(',', $keys) . " FROM users_groups WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        $result = $stmt->fetchall(\PDO::FETCH_ASSOC);

        $targetRoleId = $request->role_id;
        $requesterRoleId = 4;
        $currentTargetRoleId = 4;
        $founderCount = 0;
        foreach($result as $row){
            if ($row['user_id'] == $targetUid) $currentTargetRoleId = $row['role_id'];
            if ($row['user_id'] == $rUid) $requesterRoleId = $row['role_id'];
            if ($row['role_id'] == 0) $founderCount++;
        }

        $response = array('success' => 0, 'error' => 0, 'invalid_request' => 1);

        // first deal with lowering founder role
        // if currently founder, only user himself can lower role
        if ($targetRoleId > 0 && $currentTargetRoleId == 0 && $rUid != $targetUid){
            // error_log("if currently founder, only user himself can lower role");
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // if user himself lowers from founder, there must be at least one other founder left in the group
        if ($targetRoleId > 0 && $currentTargetRoleId == 0 && $founderCount < 2){
            // error_log("if user himself lowers from founder, there must be at least one other founder left in the group");
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // users can only grant privileges lower or equal to their own
        if ($targetRoleId < $requesterRoleId){
            // error_log("users can only grant privileges lower or equal to their own");
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $sql = "UPDATE users_groups SET role_id=:role_id WHERE group_id=:gid AND user_id = :user_id";
        // error_log($this->pdo_sql_debug($sql, array(':gid' => $gid, ':user_id' => $uid)));
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':user_id' => $targetUid, ':role_id' => $targetRoleId));

        $response = array('success' => 1, 'new_role' => $targetRoleId);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }


    function sendEmail($targetUid, $gid, $request, $rUid){
        $response = array('success' => 0, 'error' => 1, 'invalid_request' => 0);
        if (empty($targetUid) || empty($gid) || empty($request)
            || !isset($request->send_email) || $request->send_email === '' ) {
            // error_log("send email change failed targetuid $targetUid gid $gid rUid $rUid");
            // error_log(print_r($request,1));
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // validate uids are part of the group
        if (!$this->validateUids($rUid . ',' . $targetUid, $gid)) {
            error_log("uids are not part of the group");
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $send_mail = $request->send_email == 0 ? 0 : 1;
        $response = array('success' => 0, 'error' => 0, 'invalid_request' => 1);

        if ($rUid != $targetUid){
            error_log("uid does not match target uid");
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $sql = "UPDATE users_groups SET send_mail=:send_mail WHERE group_id=:gid AND user_id = :user_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':user_id' => $targetUid, ':send_mail' => $send_mail));

        $response = array('success' => 1, 'send_mail' => $send_mail);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }


    function deleteGroupMembers($dUid, $gid, $uid)
    {
        $response = array('success' => 0, 'deleted' => 0, 'removed' => 0);
        if (empty($dUid)) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // check if deleted by admin
        if (!$this->validateIsAdminOfGroup($uid, $gid)) {
            //error_log("Trying to delete {$dUid} in group {$gid} as non-admin");
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        error_log("Trying to delete {$dUid} in group {$gid}");

        //$uidList = implode(',', $body->user_ids);
        $uidList = $dUid;
        // check for paid expenses by users
        $sql = "SELECT user_id, COUNT(*) AS ecount FROM expenses WHERE group_id = :gid 
                AND FIND_IN_SET (user_id, :user_ids) GROUP BY user_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':gid' => $gid,
                ':user_ids' => $uidList
            )
        );
        $expensePaidCount = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //error_log(print_r($expensePaidCount ,1));

        // check for participated expenses by users
        $sql = "SELECT users_expenses.user_id, COUNT(users_expenses.expense_id) as ecount, group_id 
                FROM users_expenses, expenses WHERE users_expenses.expense_id = expenses.expense_id 
                AND group_id = :gid AND FIND_IN_SET (users_expenses.user_id, :user_ids) GROUP BY user_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':gid' => $gid,
                ':user_ids' => $uidList
            )
        );
        $expensePartCount = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        //error_log(print_r($expensePartCount ,1));

        // array with counts per user
        $expensesCount = array();
        foreach ($expensePaidCount as $user) {
            if(!array_key_exists($user['user_id'], $expensesCount))
                $expensesCount[$user['user_id']] = array('paid' => 0, 'participated' => 0);
            $expensesCount[$user['user_id']]['paid'] += $user['ecount'];
        }
        foreach ($expensePartCount as $user) {
            if(!array_key_exists($user['user_id'], $expensesCount))
                $expensesCount[$user['user_id']] = array('paid' => 0, 'participated' => 0);
            $expensesCount[$user['user_id']]['participated'] += $user['ecount'];
        }
        //error_log(print_r($expensesCount,1));

        $deleted = 0;
        $removed = 0;
        if (empty($expensesCount)){
            // no expenses made, can completely remove user from group
            $sql = "DELETE FROM users_groups WHERE group_id = :gid AND user_id = :user_id";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':gid' => $gid, ':user_id' => $dUid));
            $deleted++;
        }

        foreach ($expensesCount as $uid => $user) {
            if ($user['paid'] == 0 && $user['participated'] == 0) {
                // no expenses made, can completely remove user from group
                $sql = "DELETE FROM users_groups WHERE group_id = :gid AND user_id = :user_id";
                $stmt = Db::getInstance()->prepare($sql);
                $stmt->execute(array(':gid' => $gid, ':user_id' => $user['user_id']));
                $deleted++;
            } else {
                // expenses made by user, only set removed flag
                $sql = "UPDATE users_groups SET removed=1 WHERE group_id=:gid AND user_id = :user_id";
                // error_log($this->pdo_sql_debug($sql, array(':gid' => $gid, ':user_id' => $uid)));
                $stmt = Db::getInstance()->prepare($sql);
                $stmt->execute(array(':gid' => $gid, ':user_id' => $uid));
                $removed++;
            }
        }
        $response = array('success' => 1, 'deleted' => $deleted, 'removed' => $removed);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }


    function updateGroupCategories($categories, $gid, $uid)
    {
        $uids = array($uid);

        if (!$this->validateUids($uids, $gid)) {
            return 'Error: invalid uid';
        }

        if (!is_object($categories)){
            return 'Error: invalid categories set 1';
        }

        $keys = array('cid', 'group_id', 'title', 'presents', 'inactive', 'can_delete', 'sort');

        $maxCid = 1;
        // sanity check on categories array before we delete existing values and get max cid
        foreach ($categories as $category){
            foreach ($keys as $key){
                if (!array_key_exists($key, $category)){
                    return 'Error: invalid categories set ' . $key;
                }
            }
            $maxCid = $category->cid > $maxCid ? $category->cid : $maxCid;
        }
        $maxCid++;  // keep track of new cid to use in case we're adding a new category

        // get current list of categories and check if any have been deleted

        $sql = "SELECT * FROM categories WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        $resultArray  = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $CurrentCategories = array();
        foreach ($resultArray as $category)
        {
            $CurrentCategories[$category['cid']] = $category;
        }
        $currentCatIds = array_keys($CurrentCategories);

        // check cids in each category as multiple new categories could have been added (they will have cid 0)
        $newIds = array();
        foreach ($categories as $category)
        {
            if ($category->cid > 0)
                $newIds[] = $category->cid;
        }
        $diff = array_diff($currentCatIds, $newIds);

//        if (count($diff) > 0){
//            error_log("Categories in group {$gid} that are scheduled for deletion: " . implode(',', $diff));
//        }

        // check if categories scheduled for deletion are not used by any expenses
        $cannotDeleteCats = array();
        foreach ($diff as $catId) {
            $sql = "SELECT COUNT(*) FROM expenses WHERE group_id = :gid AND cid = :cid";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':gid' => $gid, ':cid' => $catId));
            $result = $stmt->fetch(\PDO::FETCH_NUM);
            $expenseCount = $result[0];
            if ($expenseCount > 0){
                error_log('Error: requested to delete category with cid ' . $catId . ' in group ' . $gid . ' but cannot because expenses were found');
                // add this category to the list as we are not allowed to delete it
                $cannotDeleteCats[] = $CurrentCategories[$catId];
            }
        }

        // delete current categories for this group
        $sql = "DELETE FROM categories WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));

        $sql = "INSERT INTO categories (cid, group_id, title, presents, inactive, can_delete, sort)
                VALUES (:cid, :gid, :title, :presents, :inactive, :can_delete, :sort)";
        $stmt = Db::getInstance()->prepare($sql);

        foreach ($cannotDeleteCats as $category) {
            $stmt->execute(array(
                ':cid' => $category['cid'],
                ':gid' => $category['group_id'],
                ':title' => $category['title'],
                ':presents' => $category['presents'],
                ':inactive' => $category['inactive'],
                ':can_delete' => $category['can_delete'],
                ':sort' => $category['sort']
            ));
        }

        foreach ($categories as $category){
            // check for new categories
            if ($category->cid == 0) {
                $cid = $maxCid;
                $maxCid++;
            } else {
                $cid = $category->cid;
            }

            $stmt->execute(array(
                ':cid' => $cid,
                ':gid' => $category->group_id,
                ':title' => $category->title,
                ':presents' => $category->presents,
                ':inactive' => $category->inactive,
                ':can_delete' => $category->can_delete,
                ':sort' => $category->sort
            ));
        }

        // return  category details for updated group
        $sql = "SELECT * FROM categories WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $return = array();
        foreach ($result as $category)
        {
            $return[$category['cid']] = $category;
        }
        return json_encode($return, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function addNewGroup($details, $uid){
        $response = array('success' => 0, 'gid' =>0);

        if (empty($details) || empty($details->currency) || empty($details->name)) {
            return json_encode($response , JSON_NUMERIC_CHECK);
        }

        $sql = "INSERT INTO groups (currency, name, description)
                VALUES (:currency, :name, :description)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':currency' => $details->currency,
                ':name' => $details->name,
                ':description' => $details->description
            )
        );
        $gid = Db::getInstance()->lastInsertId();

        $sql = "INSERT INTO users_groups (user_id, group_id, role_id, join_date)
                VALUES (:user_id, :group_id, :role_id, FROM_UNIXTIME(:submitted))";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':user_id' => $uid,
                ':group_id' => $gid,
                ':role_id' => 0,
                ':submitted' => time()
            )
        );

        // Add first (default) group category
        $sql = "INSERT INTO categories  (cid, group_id, title, presents, inactive, can_delete, sort)
                VALUES (:cid, :group_id, :title, :presents, :inactive, :can_delete, :sort)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':cid' => 1,
                ':group_id' => $gid,
                ':title' => 'Whatever',
                ':presents' => 0,
                ':inactive' => 0,
                ':can_delete' => 1,
                ':sort' => 1
            )
        );

        $response  = array('success' => 1, 'gid' => $gid);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    function deleteGroup($gid, $uid){
        $response = array('success' => 0);
        if (empty($gid)) {
            return json_encode($response , JSON_NUMERIC_CHECK);
        }

        // check if deleted by admin
        if (!$this->validateIsAdminOfGroup($uid, $gid)) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $sql = "SELECT COUNT(*) FROM expenses WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $expenseCount = $result[0];

        if ($expenseCount > 0) {
            // found expenses, copy to groups_del table
            $sql = "SELECT * FROM groups WHERE group_id = :gid";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':gid' => $gid));
            $group = $stmt->fetch(\PDO::FETCH_ASSOC);

            $sql = "INSERT INTO groups_del (group_id, name, description, reg_date, del_date, currency)
                VALUES (:group_id, :name, :description, :reg_date, FROM_UNIXTIME(:del_date), :currency)";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(
                array(
                    ':group_id' => $gid,
                    ':name' => $group['name'],
                    ':description' => $group['description'],
                    ':reg_date' => $group['reg_date'],
                    ':del_date' => time(),
                    ':currency' => $group['currency'],
                )
            );

            // copy users_groups rows to users_groups_del table
            $keys = array ('user_id', 'group_id', 'role_id', 'removed', 'join_date');
            $sql = "SELECT " .implode(',', $keys) . " FROM users_groups WHERE group_id = :gid";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':gid' => $gid));
            $result = $stmt->fetchall(\PDO::FETCH_ASSOC);

            $values = array();
            foreach ($result as $row) {
                $rowV = array();
                foreach ($keys as $key)
                    $rowV[] = $row[$key];
                $values[] = $rowV;
            }

            // http://stackoverflow.com/questions/19680494/insert-multiple-rows-with-pdo-prepared-statements
            $row_length = count($values[0]);
            $nb_rows = count($values);
            $length = $nb_rows * $row_length;

            /* Fill in chunks with '?' and separate them by group of $row_length */
            $args = implode(',', array_map(
                function($el) { return '('.implode(',', $el).')'; },
                array_chunk(array_fill(0, $length, '?'), $row_length)
            ));

            $params = array();
            foreach($values as $row)
            {
                foreach($row as $value)
                {
                    $params[] = $value;
                }
            }

            $query = "INSERT INTO users_groups_del (" . implode(',', $keys)  . ") VALUES ".$args;
            $stmt = DB::getInstance()->prepare($query);
            $stmt->execute($params);
        } else {
            // no expenses found, so we can also delete all categories of this group
            $sql = "DELETE FROM categories WHERE group_id = :gid";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':gid' => $gid));
        }

        $sql = "DELETE FROM groups WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));

        $sql = "DELETE FROM users_groups WHERE group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));

        // check if deleted
        $sql = "SELECT COUNT(*) FROM groups WHERE group_id = :gid ";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $userCount = $result[0];
        if ($userCount > 0) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $response = array('success' => 1);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    private function validateUids($uids, $gid)
    {
        if (!is_array($uids))
            $uids = explode(',', $uids);
        // get member ids for $gid
        $sql = "SELECT GROUP_CONCAT(DISTINCT user_id) AS uids FROM users_groups WHERE group_id = :group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':group_id' => $gid
            )
        );
        $result = $stmt->fetchColumn();
        $validUids = explode(',', $result);
        foreach ($uids as $uid) {
            if (!in_array($uid, $validUids))
                return false;
        }
        return true;
    }

    private function validateIsAdminOfGroup($uid, $gid)
    {
        $sql = "SELECT COUNT(*) FROM users_groups WHERE user_id = :uid AND group_id = :gid 
                AND (ROLE_ID=0 OR ROLE_ID=1)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(
            ':gid' => $gid,
            ':uid' => $uid
        ));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $userCount = $result[0];
        return $userCount == 0 ? false : true;
    }

    private function addExpenseEmail($expense, $eid, $type = 'add', $removedUids = array())
    {
        // error_log("START WITH EID " . $eid);

        $uids = explode(',', $expense->uids);
        $uidValues = array_values($uids);
        $uid = array_pop($uidValues);
        $member = new \Models\Member();
        $groupsInfo = $member->getGroupsBalance($uid, false);

        // keep track of users that do not want email
        // error_log(print_r($groupsInfo ,1));
        $noMailUsers = array();
        foreach ($groupsInfo[$expense->gid]['members'] as $member){
            if ($member['send_mail'] == 0) $noMailUsers[] = $member['uid'];
        }

        $uidDetails = $this->getUserDetails(implode(',', array_keys($groupsInfo[$expense->gid]['members'])));

        // error_log(print_r($groupsInfo, 1));
        $groupName = $groupsInfo[$expense->gid]['name'];

        $created = date('l jS \of F Y', $expense->ecreated);
        $from = 'goingdutch@santema.eu';

        // PHP Fatal error:  Class 'Models\NumberFormatter' not found
        // You just need to enable this extension in php.ini by uncommenting this line:
        // extension=ext/php_intl.dll
        $formatter = new \NumberFormatter('nl_NL', \NumberFormatter::CURRENCY);
        $amount = $formatter->formatCurrency($expense->amount, $groupsInfo[$expense->gid]['currency']);
        $amountpp = $formatter->formatCurrency($expense->amount / count($uids), $groupsInfo[$expense->gid]['currency']);

        switch ($type) {
            case 'update':
                $subject = "Going Dutch expense updated in group \"{$groupName}\"";
                $messageTemplate = "The expense made on {date} by {eowner} with {amount} and description \"{description}\" has been updated.<br /><br />\n{removed}";
                $messageTemplateEnd = "The costs per person are {amountpp} making your current balance {yourbalance} which comes to position {yourposition} in the group.\n";
                // error_log("Removed UIDS: " + implode(', ', $removedUids));
                break;
            case 'delete':
                $subject = "Going Dutch expense deleted in group \"{$groupName}\"";
                $messageTemplate = "The expense on {date} made by {eowner} with {amount} and description \"{description}\" has been deleted.<br /><br />\n";
                $messageTemplateEnd = "The costs per person were {amountpp} making your current balance {yourbalance} which comes to position {yourposition} in the group.\n";
                break;
            default:
                $subject = "Going Dutch expense booked in group \"{$groupName}\"";
                $messageTemplate = "On {date} {eowner} made an expense of {amount} with description \"{description}\".<br /><br />\n";
                $messageTemplateEnd = "The costs per person are {amountpp} making your current balance {yourbalance} which comes to position {yourposition} in the group.\n";
        }

        if (count($uids) == 1) {
            $messageTemplateOnlyPay = $messageTemplate . "{participants} was listed as the only participant (but you paid).<br /><br />\n";
            $messageTemplate .= "You were listed as the only participant.<br /><br />\n";
        } else {
            $messageTemplateOnlyPay = $messageTemplate . "{participants} were listed as the participants (but you paid).<br /><br />";
            $messageTemplate .= "You were listed as a participant, together with {participants}.<br /><br />\n";
        }

        $messageTemplateEnd .= "The balance list is now: <br /><br />{balancelist}\n<br /><br />\n";
        $messageTemplateEnd .= "This expense is logged with id {eid}.";
        $messageTemplate .= $messageTemplateEnd;
        $messageTemplateOnlyPay .= $messageTemplateEnd;

        //$message .= "<br /><br /><a href=\"".LOGIN_URL."\">Going Dutch</a>";

        $posArray = array();
        $balanceTable = "\n<table>\n";
        $i = 1;
        // error_log(print_r($uidDetails,1));
        // error_log(print_r($groupsInfo[$expense->gid]['members'],1));
        foreach ($groupsInfo[$expense->gid]['members'] as $member) {
            $posArray[$member['uid']] = $i;
            $b = $formatter->formatCurrency($member['balance'], $groupsInfo[$expense->gid]['currency']);
            // $style = $i < 0 ? '<style=\"color: red\">' : '<style = \"\">';
            // $balanceTable .= "<tr><td>{$i}</td><td>{$uidDetails[$member['uid']]['realname']}</td><td>{$style}{$b}</style></td></tr>\n";
            $balanceTable .= "<tr><td>{$i}</td><td>{$uidDetails[$member['uid']]['realname']}</td><td>{$b}</td></tr>\n";
            $i++;
        }
        $balanceTable .= "</table>\n";

        $onlyPay = false;
        if (!in_array($expense->uid, $uids)) {
            $onlyPay = true;
            $uids[] = $expense->uid;
        }

        $uids = array_merge($uids, $removedUids);
        foreach ($uids as $uid) {
            if ($onlyPay && $uid == $expense->uid) {
                $message = str_replace('{date}', $created, $messageTemplateOnlyPay);
            } else {
                $message = str_replace('{date}', $created, $messageTemplate);
            }
            // $style = $groupsInfo[$expense->gid]['members'][$uid]['balance'] < 0 ? '<style=\"color: red\">' : '<style = \"\">';
            $yourBalance = $formatter->formatCurrency($groupsInfo[$expense->gid]['members'][$uid]['balance'], $groupsInfo[$expense->gid]['currency']);
            // $yourBalance = $style . $yourBalance . '</style>';

            $message = str_replace('{eowner}', $expense->uid == $uid ? "you" : $uidDetails[$expense->uid]['realname'], $message);
            $message = str_replace('{amount}', $amount, $message);
            $message = str_replace('{amountpp}', $amountpp, $message);
            $message = str_replace('{yourbalance}', $yourBalance, $message);
            $message = str_replace('{description}', $expense->etitle, $message);
            $message = str_replace('{yourposition}', $posArray[$uid], $message);
            $message = str_replace('{balancelist}', $balanceTable, $message);
            $message = str_replace('{eid}', $eid, $message);
            if (in_array($uid, $removedUids)) {
                $message = str_replace('{removed}', "You are no longer listed as a participant for this expense.<br /><br />\n", $message);
            } else {
                $message = str_replace('{removed}', '', $message);
            }

            $participants = '';

            $count = count($uids) - count($removedUids) - ($onlyPay ? 1 : 0);
            // error_log("EID: " . $eid . " COUNT: " . $count . " Onlypay: " . $onlyPay . " Uids: " . implode(",", $uids) . ' UID: ' . $expense->uid);
            if ($count > 1) {
                // error_log("EID: " .  $eid . " COUNT: " . $count . " Onlypay: " . $onlyPay . " Uids: " . implode(",", $uids) . ' UID: ' . $expense->uid);

                foreach ($uids as $uidP) {
                    if ($uid == $uidP || ($onlyPay && $uidP == $expense->uid) || in_array($uidP, $removedUids))
                        continue;
                    $participants[] = $uidDetails[$uidP]['realname'];
                }
                $last = array_pop($participants);
                $participants = count($participants) ? implode(", ", $participants) . " and " . $last : $last;
            } elseif ($count == 1 && $onlyPay) {
                foreach ($uids as $uidP) {
                    if ($uidP != $expense->uid && !in_array($uidP, $removedUids)) {
                        $participants = $uidDetails[$uidP]['realname'];
                    }
                }
            }
            $message = str_replace('{participants}', $participants, $message);
            $to = $uidDetails[$uid]['email'];

            if (in_array($uid, $removedUids)) {
                $message = preg_replace('/You were listed .*<br \/>/', '', $message);
                $message = preg_replace('/The costs per person .* current balance/', 'Your current balance is now', $message);
            }

            if (!in_array($uid, $noMailUsers )) {
                $sql = "INSERT INTO email (gid , eid, subject, message, toaddress, fromaddress, submitted)
                    VALUES (:gid, :eid, :subject, :message, :toaddress, :fromaddress, FROM_UNIXTIME(:submitted))";
                $stmt = Db::getInstance()->prepare($sql);
                $stmt->execute(
                    array(
                        ':gid' => $expense->gid,
                        ':eid' => $eid,
                        ':subject' => $subject,
                        ':message' => $message,
                        ':toaddress' => $to,
                        ':fromaddress' => $from,
                        ':submitted' => time(),
                    )
                );
            }
        }

        $file = 'C:\xampp\htdocs\api.gdutch.nl\sendmail.php';

        //$cmd = "/usr/bin/php5 {$background_mailfile} {$user['email']} {$from} \"{$from_name}\" \"{$subject}\" \"{$body}\" \"{$replyto}\" \"{$sendas}\"";
        //exec("/usr/bin/php {$background_mailfile} {$user['email']} {$from} {$from_name} {$subject} {$body} {$replyto} {$sendas} > {$ouput} &");
        $cmd = "C:\\xampp\\php\\php.exe {$file}";
        $output = '/dev/null';
        // exec("{$cmd} > {$output} &");
        exec("{$cmd} ");
    }

    private function getUserDetails($uids)
    {
        $sql = "SELECT user_id, email, username, realname FROM users WHERE FIND_IN_SET (user_id, :uids)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':uids' => $uids
            )
        );
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $uidDetails = array();
        foreach ($result as $val) {
            $uidDetails[$val['user_id']] = $val;
        }
        return $uidDetails;
    }

    private function getGroupUserIds($gid)
    {
        $sql = "SELECT user_id FROM users_groups WHERE group_id = :group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':group_id' => $gid
            )
        );
        $result = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        return $result;
    }

//    private function queueEmail($gid, $eid, $subject, $message, $to, $from){
//        if (!isset($expense->type))
//            $expense->type = 1;
//        $sql = "INSERT INTO expenses (type, cid, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency, timezoneoffset)
//                VALUES (:type, :cid, :user_id, :group_id, :description, :amount, FROM_UNIXTIME(:created), :event_id, FROM_UNIXTIME(:updated), :currency, :timezoneoffset)";
//        $stmt = Db::getInstance()->prepare($sql);
//        $stmt->execute(
//            array(
//                ':type' => $expense->type,
//                ':cid' => $expense->cid,
//                ':user_id' => $expense->uid,
//                ':group_id' => $gid,
//                ':description' => utf8_decode($expense->etitle),
//                ':amount' => $expense->amount,
//                ':created' => $expense->ecreated,
//                ':updated' => $expense->eupdated,
//                ':event_id' => $expense->event_id,
//                ':timezoneoffset' => $expense->timezoneoffset,
//                ':currency' => 1
//            )
//        );
//        $eid = Db::getInstance()->lastInsertId();
//    }


    private function pdo_sql_debug($sql, $placeholders)
    {
        foreach ($placeholders as $k => $v) {
            $sql = preg_replace('/' . $k . '/', "'" . $v . "'", $sql);
        }
        return $sql;
    }
}

/*
 * CREATE TABLE `Email` (
  `email_id` INT NOT NULL AUTO_INCREMENT,
  `gid` INT NOT NULL DEFAULT '0',
  `eid` INT NULL DEFAULT '0',
  `subject` TINYTEXT NULL,
  `message` TEXT NULL,
  `to` TEXT NULL,
  `from` TEXT NULL,
  `submitted` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `sent` DATETIME NULL DEFAULT '0',
  PRIMARY KEY (`email_id`)
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB
;

 */