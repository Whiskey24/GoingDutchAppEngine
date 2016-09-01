<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 4-9-2015
 * Time: 23:07
 */

namespace Models;

use Db;

class Member
{
    function getGroupsBalance($uid, $json = true)
    {
        $groups = $this->getAllGroups($uid);

        // get all the users that are members of these groups from the concatenated field
        $user_id_list = $this->createUserIdList($groups);

        // create group list as concatenated field for use in query
        $group_id_list = implode(',', array_keys($groups));

        // Get the total expense for all users in each group
        // Use find in set, see here: http://stackoverflow.com/questions/1586587/pdo-binding-values-for-mysql-in-statement
        $sql = "SELECT userid, group_id, SUM(expenses_summary.per_person) AS expense FROM (
                    SELECT users_expenses.user_id AS userid, group_id, users_expenses.expense_id AS exid,
                    amount AS total_amount,
                    (SELECT COUNT(*) FROM users_expenses WHERE expense_id = exid) AS member_count,
                    (SELECT total_amount/member_count) AS per_person
                  FROM users_expenses, expenses WHERE users_expenses.expense_id = expenses.expense_id
                  AND FIND_IN_SET(users_expenses.user_id, :uids) AND FIND_IN_SET(expenses.group_id, :gids)) AS expenses_summary
                GROUP BY userid, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uids' => $user_id_list, ':gids' => $group_id_list));
        $expense_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // setup members array for balance figures
        foreach ($groups as &$group){
            $group['members'] = array();
            $uids = explode(',', $group['user_id_list']);
            foreach ($uids as $m_uid){
                $group['members'][$m_uid] = array('uid' => $m_uid, 'expense' => 0, 'paid' => 0, 'balance' => 0);
            }
        }

        // add expense costs for each user to groups array, skip groups for which this user is not a member
        foreach ($expense_summary as $userJoined) {
            if (!array_key_exists($userJoined['group_id'], $groups))
                continue;
            $groups[$userJoined['group_id']]['members'][$userJoined['userid']]['expense'] = floatval($userJoined['expense']);
            $groups[$userJoined['group_id']]['members'][$userJoined['userid']]['balance'] = -floatval($userJoined['expense']);
        }

        // Get the total paid by this user in each group
        $sql = "SELECT user_id, group_id, SUM(amount) AS paid FROM expenses WHERE FIND_IN_SET(expenses.user_id, :uids) GROUP BY user_id, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uids' => $user_id_list));
        $paid_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // add paid balance for each user to groups array, skip groups for which this user is not a member
        foreach ($paid_summary as $userPaid) {
            $gid = $userPaid['group_id'];
            $p_uid = $userPaid['user_id'];
            if (!array_key_exists($gid, $groups))
                continue;
            $groups[$gid]['members'][$p_uid]['paid'] = floatval($userPaid['paid']);
            $groups[$gid]['members'][$p_uid]['balance'] = floatval($userPaid['paid']) - $groups[$gid]['members'][$p_uid]['expense'];
        }

        foreach ($groups as &$group) {
            $group['balance'] = $group['members'][$uid]['balance'];
        }

        // get the role for each user in each group
        // Get the total paid by this user in each group
        $sql = "SELECT user_id, group_id, users_groups.role_id, users_groups.send_mail, shortname, removed FROM users_groups, roles WHERE FIND_IN_SET(user_id, :uids) 
                AND FIND_IN_SET(group_id, :gids) AND users_groups.role_id = roles.role_id GROUP BY user_id, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uids' => $user_id_list, ':gids' => $group_id_list));
        $role_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach($role_summary as $user){
            $groups[$user['group_id']]['members'][$user['user_id']]['role_id'] = $user['role_id'];
            $groups[$user['group_id']]['members'][$user['user_id']]['send_mail'] = $user['send_mail'];
            $groups[$user['group_id']]['members'][$user['user_id']]['role'] = $user['shortname'];
            $groups[$user['group_id']]['members'][$user['user_id']]['removed'] = $user['removed'];
        }

        // Get all the categories for these groups
        $sql = "SELECT * FROM categories WHERE FIND_IN_SET(group_id, :gids) ORDER BY group_id, sort, cid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gids' => $group_id_list));
        $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($categories as $category) {
            $gid = $category['group_id'];
            if (!array_key_exists('categories', $groups[$gid]))
                $groups[$gid]['categories'] = array();
            $groups[$gid]['categories'][$category['cid']] = $category;
        }

        return $json ? json_encode($groups, JSON_NUMERIC_CHECK) : $groups;
    }

    function getDetailsMembersInGroups($uid)
    {
        $groups = $this->getAllGroups($uid);

        // get all the users that are members of these groups from the concatenated field
        $user_id_list = $this->createUserIdList($groups);

        $sql = "SELECT user_id AS uid, username AS nickName, activated AS active, reg_date AS created,
                email, realname, firstName, lastName, updated FROM users
                WHERE FIND_IN_SET(user_id, :uids)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uids' => $user_id_list));
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->realname2firstLastNameHelper($users);
        $users = $this->rearrangeArrayKey('uid', $users);
        return json_encode($users, JSON_NUMERIC_CHECK);
    }

    function getMemberDetails($requestUid, $askedByUid)
    {
        $groups = $this->getAllGroups($askedByUid);

        // get all the users that are members of these groups from the concatenated field
        $user_id_list = $this->createUserIdList($groups, false);

        if (!in_array($requestUid, $user_id_list)){
            return 'Error: invalid request';
        }

        $sql = "SELECT user_id AS uid, username AS nickName, activated AS active, reg_date AS created,
                email, realname, firstName, lastName, updated FROM users
                WHERE user_id = :uid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $requestUid));
        $details = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $this->realname2firstLastNameHelper($details);
        $details = array_pop($details);
        return json_encode($details, JSON_NUMERIC_CHECK);
    }

    function updatePassword($uid, $details, $rUid){
        if (empty($details['password']) || $uid != $rUid){
            $response = array('success' => 0);
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        global $app_config;
        $salt = $app_config['secret']['hash'];
        $hash = md5($salt . $details['password'] . $salt);

        $sql = "UPDATE users SET password=:password WHERE user_id=:uid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(
            ':password' => $hash,
            ':uid' => $uid
        ));

        $response = array('success' => 1);
        return json_encode($response, JSON_NUMERIC_CHECK);

    }

    function updateMemberDetails($requestUid, $details, $askedByUid)
    {
        if ($requestUid != $askedByUid || $requestUid != $details['uid']){
            return 'Error: invalid request';
        }

        $sql = "UPDATE users SET username=:nickName, firstName=:firstName, lastName=:lastName, realname=:realname, email=:email WHERE user_id=:uid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(
            ':nickName' => $details['nickName'],
            ':firstName' => $details['firstName'],
            ':lastName' => $details['lastName'],
            ':realname' => $details['realname'],
            ':email' => $details['email'],
            ':uid' => $requestUid
        ));

        return $this->getMemberDetails($requestUid, $askedByUid);
    }

    function updateGroupSort($groups, $uidToUpdate, $uidRequester){

        if ($uidToUpdate != $uidRequester) {
            return 'Error: invalid uid';
        }

        $sql = "UPDATE users_groups SET `sort`=:sort WHERE user_id=:uid AND group_id=:group_id";
        $stmt = Db::getInstance()->prepare($sql);
        foreach ($groups as $group) {
            $stmt->execute(
                array(
                    ':sort' => $group['sort'],
                    ':uid' => $uidToUpdate,
                    ':group_id' => $group['gid'],
                )
            );
        }

        $sql = "SELECT group_id as gid, `sort` FROM users_groups WHERE user_id=:uid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $uidToUpdate));
        $resultArray  = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $groups = array();
        foreach ($resultArray as $group)
        {
            $groups[$group['gid']] = $group;
        }
        return json_encode($groups, JSON_NUMERIC_CHECK);
    }

    function emailExists($details){
        $response = array('error' => 1);
        if (empty($details) || empty($details['email'])) {
            return json_encode($response , JSON_NUMERIC_CHECK);
        }

        $userCount = $this->emailInDb($details['email']);
        if ($userCount > 0) {
            $response = array('error' => 0, 'exists' => 1);
        } else {
            $response = array('error' => 0, 'exists' => 0);
        }
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    private function emailInDb($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':email' => $email));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $userCount = $result[0];
        return $userCount;
    }

    function addNewMember($details){
        global $app_config;
        $response = array('success' => 0, 'uid' =>0);

        if (empty($details) || empty($details['nickName']) || empty($details['pass'])) {
            return json_encode($response , JSON_NUMERIC_CHECK);
        }

        // check for existing email
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':email' => $details['email']));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $userCount = $result[0];
        if ($userCount> 0) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $salt = $app_config['secret']['hash'];
        $hash = md5($salt . $details['pass'] . $salt);
        $now = time();

        $sql = "INSERT INTO users (username, password, email, realname, firstName, lastName, activated, account_deleted, confirmation, reg_date, last_login, updated)
                VALUES (:username, :password, :email, :realname, :firstName, :lastName, :activated, :account_deleted, :confirmation, :reg_date, :last_login, :updated)";
        $stmt = Db::getInstance()->prepare($sql);

        $stmt->execute(
            array(
                ':username' => trim($details['nickName']),
                ':password' => $hash,
                ':email' => trim($details['email']),
                ':realname' => trim($details['fullName']),
                ':firstName' => trim($details['firstName']),
                ':lastName' => trim($details['lastName']),
                ':activated' => 1,
                ':account_deleted' =>0,
                ':confirmation' => 0,
                ':reg_date' => $now,
                ':last_login' => 0,
                ':updated' => $now
            )
        );
        $uid = Db::getInstance()->lastInsertId();
        $response  = array('success' => 1, 'uid' => $uid);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    function deleteMember($details, $uid){
        $response = array('success' => 0);
        if (empty($details) || empty($details['email']) || empty($details['uid'])) {
            return json_encode($response , JSON_NUMERIC_CHECK);
        }

        // check if email and uid match
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email AND user_id = :uid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':email' => $details['email'], ':uid' => $details['uid']));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $userCount = $result[0];
        if ($userCount < 1) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        // a user can only delete himself
        if ($uid !== $details['uid']){
            return json_encode($response , JSON_NUMERIC_CHECK);
        }

        // only completely delete a user if he has no expenses or things break
        $sql = "SELECT COUNT(*) FROM expenses WHERE user_id = :uid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $details['uid']));
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        $count = $result[0];
        $full_delete = true;
        if ($count > 0) {
            $full_delete = false;
        } else {
            $sql = "SELECT COUNT(*) FROM users_expenses WHERE user_id = :uid";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':uid' => $details['uid']));
            $result = $stmt->fetch(\PDO::FETCH_NUM);
            $count = $result[0];
            if ($count > 0) {
                $full_delete = false;
            }
        }

        if ($full_delete) {
            $sql = "DELETE FROM users WHERE user_id = :uid AND email = :email";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':email' => $details['email'], ':uid' => $details['uid']));

            // check if deleted
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email AND user_id = :uid";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':email' => $details['email'], ':uid' => $details['uid']));
            $result = $stmt->fetch(\PDO::FETCH_NUM);
            $userCount = $result[0];
            if ($userCount > 0) {
                return json_encode($response, JSON_NUMERIC_CHECK);
            }
        } else {
            $sql = "UPDATE users SET account_deleted = 1 WHERE user_id = :user_id";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(array(':user_id' => $details['uid']));
        }

        $response = array('success' => 1);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    function forgetPwd($details){
        global $app_config;

        $response = array('success' => 0);
        if (empty($details || empty($details['email']))) {
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $userCount = $this->emailInDb($details['email']);
        if ($userCount == 0){
            return json_encode($response, JSON_NUMERIC_CHECK);
        }

        $sql = "SELECT user_id FROM users WHERE email = :email";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':email' => $details['email']));
        $uid = $stmt->fetchColumn();

        $newPwd = $this->randstr(6);
        // error_log($newPwd);
        $salt = $app_config['secret']['hash'];
        $hash = md5($salt . $newPwd . $salt);

        $sql = "UPDATE users SET pwd_recovery = :hash WHERE  user_id = :user_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':hash' => $hash, ':user_id' => $uid));

        $email_subject = "Password reset for Going Dutch";
        $email_msg = "Somebody (hopefully you) requested a password reset for Going Dutch. You can now login with " . $newPwd . ". Please change this password again.";
        $from = 'goingdutch@santema.eu';

        $sql = "INSERT INTO email (gid , eid, subject, message, toaddress, fromaddress, submitted)
                    VALUES (:gid, :eid, :subject, :message, :toaddress, :fromaddress, FROM_UNIXTIME(:submitted))";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':gid' => 0,
                ':eid' => 0,
                ':subject' => $email_subject,
                ':message' => $email_msg,
                ':toaddress' => $details['email'],
                ':fromaddress' => $from,
                ':submitted' => time(),
            )
        );


        $file = 'C:\xampp\htdocs\api.gdutch.nl\sendmail.php';

        //$cmd = "/usr/bin/php5 {$background_mailfile} {$user['email']} {$from} \"{$from_name}\" \"{$subject}\" \"{$body}\" \"{$replyto}\" \"{$sendas}\"";
        //exec("/usr/bin/php {$background_mailfile} {$user['email']} {$from} {$from_name} {$subject} {$body} {$replyto} {$sendas} > {$ouput} &");
//        $cmd = "C:\\xampp\\php\\php.exe {$file}";
//        $output = '/dev/null';
        // exec("{$cmd} > {$output} &");
        //exec("{$cmd} ");
        Db::triggerSendMail();
        $response = array('success' => 1);
        return json_encode($response, JSON_NUMERIC_CHECK);
    }

    /*
     * Get all the groups for this user in an associative array
     */
    private function getAllGroups($uid)
    {
        $sql = "SELECT users_groups.group_id AS gid, groups.currency, sort,
                groups.name, shortname AS role, UNIX_TIMESTAMP(join_date) AS join_date, groups.description,
                (SELECT COUNT(*) FROM users_groups WHERE group_id = gid) AS member_count,
                (SELECT GROUP_CONCAT(user_id) AS user_id_list FROM users_groups WHERE group_id = gid) AS user_id_list
                FROM `users_groups`, groups, roles
                WHERE users_groups.group_id = groups.group_id AND users_groups.role_id = roles.role_id
                AND user_id = :uid ORDER BY groups.name ASC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $uid));
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $groups = $this->rearrangeArrayKey('gid', $groups);
        return $groups;
    }

    /*
     * Get all the users that are members of these groups from the concatenated field in the groups
     */
    private function createUserIdList ($groups, $returnString = true)
    {
        $user_id_list = array();
        foreach ($groups as $group)
            $user_id_list = array_merge($user_id_list, explode(',', $group['user_id_list']));
        if ($returnString)
            $user_id_list = implode(',', array_unique($user_id_list));
        return $user_id_list;
    }

    /*
     * Rearranges an array of array to be indexed by one of the keys of the sub arrays
     * If a key occurs more than once, the last one overrides the previous array with that key
     * If the key is not found in the original sub arrays, the original array is returned
     */
    public static function rearrangeArrayKey($keyname, $array)
    {
        $newArray = array();
        foreach ($array as $item) {
            if (array_key_exists($keyname, $item)) {
                $newArray[$item[$keyname]] = $item;
            }
        }
        return empty($newArray) ? $array : $newArray;
    }

    /*
     * Helper function for backwards compatibility
     * Fills firstName and lastName fields based on realname
     */
    private function realname2firstLastNameHelper(&$users)
    {
        foreach ($users as &$user){
            if (empty($user['firstName']) && empty($user['lastName'])){
                $pieces = explode(' ', $user['realname']);
                switch (count($pieces)){
                    case 1:
                        $user['firstName'] = $pieces[0];
                        break;
                    case 2:
                        $user['firstName'] = $pieces[0];
                        $user['lastName'] = $pieces[1];
                        break;
                    default:
                        $user['firstName'] = $pieces[0];
                        $user['lastName'] = substr( $user['realname'], strlen($pieces[0]));
                        break;
                }
            }
        }
    }

    private function pdo_sql_debug($sql, $placeholders)
    {
        foreach ($placeholders as $k => $v) {
            $sql = preg_replace('/' . $k . '/', "'" . $v . "'", $sql);
        }
        return $sql;
    }


    // http://stackoverflow.com/questions/1992978/generate-a-5-char-long-string-0-9-a-z
    private function randstr($len) {
        $currLen = 0;
        $value = '';
        while($currLen < $len) {
            $new = base_convert(rand(), 10, 36);
            $value .= $new;
            $currLen += strlen($new);
        }
        //$value may be longer than the requested $len
        return substr($value, 0, $len);
    }
}