<?php
require_once 'vendor/autoload.php';
require_once 'Db/Db.php';
//require_once 'sendmail.php';


//require 'RedBeanPHP/rb.php';

// set up database connection
//R::setup('mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['name'], $dbConfig['user'], $dbConfig['pass']);
//R::freeze(true);

// ToDo: use this for compression - http://pieroxy.net/blog/pages/lz-string/index.html



$app = new \Slim\App();
$auth = new \Middleware\Authenticate();

global $app_config;
$app_config = parse_ini_file('config.ini', true);

$app->get('/version', function ($request, $response, $args) {
    $id = array('service' => 'Going Dutch API', 'version' =>'0.1', 'uid' => \Middleware\Authenticate::$requestUid);
    $response->write(json_encode($id));
    return $response;
})->add($auth);

$app->get('/groups', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->getGroupsBalance(\Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/groups', function ($request, $response, $args) {
    $member = new \Models\Group();
    $response->write($member->updateGroupDetails($request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/users', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->getDetailsMembersInGroups(\Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/user/{uid}/details', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->getMemberDetails($args['uid'], \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/user/{uid}/details', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->updateMemberDetails($args['uid'], $request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/user/{uid}/pass', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->updatePassword($args['uid'], $request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/user/{uid}/groups', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->updateGroupSort($request->getParsedBody(), $args['uid'], \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->post('/user', function ($request, $response, $args) {
    $member = new \Models\Member();
//    error_log( print_r($request->getParsedBody(), 1));
    $response->write($member->addNewMember($request->getParsedBody()));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
});

$app->post('/emailexists', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->emailExists($request->getParsedBody()));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
});

$app->delete('/user', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->deleteMember($request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->post('/user/forgetpwd', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->forgetPwd($request->getParsedBody()));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
});

$app->post('/group', function ($request, $response, $args) {
    $group = new \Models\Group();
    //error_log( print_r($request->getParsedBody(), 1));
    $response->write($group->addNewGroup($request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

//$app->delete('/group', function ($request, $response, $args) {
//    $group = new \Models\Group();
//    error_log( print_r($request->getParsedBody(), 1));
//    $response->write($group->deleteGroup($request->getParsedBody(),\Middleware\Authenticate::$requestUid));
//    $newResponse = $response->withHeader('Content-type', 'application/json');
//    return $newResponse;
//})->add($auth);

$app->delete('/group/{gid}', function ($request, $response, $args) {
    $group = new \Models\Group();
//    error_log("Deleting group " . $args['gid']);
    $response->write($group->deleteGroup($args['gid'],\Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->post('/group/{gid}/members', function ($request, $response, $args) {
    $group = new \Models\Group();
    //error_log( $request->getParsedBody());
    $response->write($group->addGroupMembers($request->getParsedBody(), $args['gid'], \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

//$app->delete('/group/{gid}/members', function ($request, $response, $args) {
//    $group = new \Models\Group();
//    //error_log( $request->getParsedBody());
//    $response->write($group->deleteGroupMembers($request->getParsedBody(), $args['gid'], \Middleware\Authenticate::$requestUid));
//    $newResponse = $response->withHeader('Content-type', 'application/json');
//    return $newResponse;
//})->add($auth);

$app->delete('/group/{gid}/members/{uid}', function ($request, $response, $args) {
    $group = new \Models\Group();
    //error_log( $request->getParsedBody());
    $response->write($group->deleteGroupMembers($args['uid'], $args['gid'], \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/group/{gid}/members/{uid}', function ($request, $response, $args) {
    $group = new \Models\Group();
    //error_log( $request->getParsedBody());
    $response->write($group->changeRole($args['uid'], $args['gid'], $request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/group/{gid}/members/{uid}/email', function ($request, $response, $args) {
    $group = new \Models\Group();
    //error_log( $request->getParsedBody());
    $response->write($group->sendEmail($args['uid'], $args['gid'], $request->getParsedBody(), \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

// Edit, add & delete group categories
$app->put('/group/{gid}/categories', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->updateGroupCategories($request->getParsedBody(), $args['gid'], \Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/group/{gid}/expenses', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->getExpenses($args['gid']));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/group/{gid}/expensesdel', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->getExpensesDel($args['gid']));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/group/{gid}/expenses/{eid}', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->getExpense($args['gid'], $args['eid']));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/group/{gid}/expensesdel/{eid}', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->getExpenseDel($args['gid'], $args['eid']));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->post('/group/{gid}/expenses', function ($request, $response, $args) {
    $group = new \Models\Group();
//    error_log( "TEST");
//    error_log( $request->getBody());
//    error_log( print_r($request->getParsedBody(), 1));
//    error_log( "TEST2");
    $response->write($group->addExpense($args['gid'], $request->getParsedBody()));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->put('/group/{gid}/expenses', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->updateExpense($args['gid'], $request->getParsedBody()));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->delete('/group/{gid}/expenses/{eid}', function ($request, $response, $args) {
    $group = new \Models\Group();
    $response->write($group->deleteExpense($args['gid'], $args['eid']));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);



$app->get('/examplegroups', function ($request, $response, $args) {
    $response->write('{"1":{"gid":1,"title":"Group 1","subtitle":"group 1 subtitle","picture":"","created_ts":1434605924,"updated_ts":1434615924,"balance":120.03,"member_create_events":1,"member_other_expense":1,"member_add_member":1,"currency":"EUR","sort":1,"email_notify":1,"nickname":"test nick 1","members":{"1":23.23,"2":1542.36,"3":-1000,"5":565.59},"categories":{"1":{"cid":1,"title":"drinks","sort":1,"presents":0,"inactive":0,"can_delete":0},"2":{"cid":2,"title":"food","sort":2,"presents":0,"inactive":0,"can_delete":0},"3":{"cid":3,"title":"presents","sort":3,"presents":1,"inactive":0,"can_delete":0},"4":{"cid":4,"title":"tickets","sort":4,"presents":0,"inactive":0,"can_delete":1}}},"2":{"gid":2,"title":"Group 2","subtitle":"group 2 subtitle","picture":"","created_ts":1434605924,"updated_ts":1434615924,"balance":0.07,"member_create_events":1,"member_other_expense":1,"member_add_member":1,"sort":2,"email_notify":1,"nickname":"test nick 2","currency":"USD","members":{"1":-857.65,"4":452.85,"6":623.88,"7":219.08},"categories":{"1":{"cid":1,"title":"drinks","sort":1,"presents":0,"inactive":0,"can_delete":0},"2":{"cid":2,"title":"food","sort":2,"presents":0,"inactive":0,"can_delete":0},"3":{"cid":3,"title":"presents","sort":3,"presents":1,"inactive":0,"can_delete":0},"4":{"cid":4,"title":"tickets","sort":4,"presents":0,"inactive":0,"can_delete":1}}},"3":{"gid":3,"title":"Group 3","subtitle":"group 3 subtitle","picture":"","created_ts":1434605924,"updated_ts":1434615924,"balance":-123.03,"member_create_events":1,"member_other_expense":1,"member_add_member":1,"currency":"GBP","sort":3,"email_notify":1,"nickname":"test nick 3","members":{"8":853.33,"1":11000.36,"3":-5000.76,"5":-6852},"categories":{"1":{"cid":1,"title":"drinks","sort":1,"presents":0,"inactive":0,"can_delete":0},"2":{"cid":2,"title":"food","sort":2,"presents":0,"inactive":0,"can_delete":0},"3":{"cid":3,"title":"presents","sort":3,"presents":1,"inactive":0,"can_delete":0},"4":{"cid":4,"title":"tickets","sort":4,"presents":0,"inactive":0,"can_delete":1}}},"4":{"gid":4,"title":"Group 4","subtitle":"group 4 subtitle","picture":"","created_ts":1434605924,"updated_ts":1434615924,"balance":555.48,"member_create_events":1,"member_other_expense":1,"member_add_member":1,"currency":"EUR","sort":4,"email_notify":1,"nickname":"test nick 4","members":{"1":853.33,"2":200.36,"3":-2100.76,"5":-1852,"6":782.36,"7":4503.76,"8":-2386},"categories":{"1":{"cid":1,"title":"drinks","sort":1,"presents":0,"inactive":0,"can_delete":0},"2":{"cid":2,"title":"food","sort":2,"presents":0,"inactive":0,"can_delete":0},"3":{"cid":3,"title":"presents","sort":3,"presents":1,"inactive":0,"can_delete":0},"4":{"cid":4,"title":"tickets","sort":4,"presents":0,"inactive":0,"can_delete":1}}}}');
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/exampleusers', function ($request, $response, $args) {
    $response->write('{"1":{"uid":1,"email":"jan@test.com","password":"123123123","firstName":"Jan","lastName":"van den Broek","nickName":"Jantje","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"2":{"uid":2,"email":"jan2@test.com","password":"123123123","firstName":"Jan Alleman","lastName":"van den Broek","nickName":"Jantje2","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"3":{"uid":3,"email":"LangeAchternaam@test.com","password":"123123123","firstName":"Rob","lastName":"met een hele lange Achternaam","nickName":"Rob","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"4":{"uid":4,"email":"whiskey@test.com","password":"123123123","firstName":"Albert Theodorus","lastName":"Santema","nickName":"Whiskey","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"5":{"uid":5,"email":"jan5@test.com","password":"123123123","firstName":"Jan5","lastName":"van den Broek5","nickName":"Jantje5","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"6":{"uid":6,"email":"jan6@test.com","password":"123123123","firstName":"Jan6 Alleman","lastName":"van den Broekkkk","nickName":"Alleman6","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"7":{"uid":7,"email":"LangeAchternaam7@test.com","password":"123123123","firstName":"Rob7","lastName":"met een hele lange Achternaam","nickName":"Rob7","picture":"","active":1,"created":"1435658751","updated":"1435662751"},"8":{"uid":8,"email":"whiskey8@test.com","password":"123123123","firstName":"Albert 8","lastName":"Santema8","nickName":"Whiskey8","picture":"","active":1,"created":"1435658751","updated":"1435662751"}}');
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/exampleexpenses', function ($request, $response, $args) {
    $response->write('{"1":[{"eid":1,"etitle":"group 1 expense 1","cid":1,"ctitle":"drinks","uid":1,"uids":"1,2,3","amount":12.24,"timezoneoffset":-120,"ecreated":1437605924,"eupdated":1437605924},{"eid":2,"etitle":"group 1 expense 2","cid":2,"ctitle":"tickets","uid":2,"uids":"1,5","amount":55.58,"timezoneoffset":-120,"ecreated":1437692324,"eupdated":1437692324},{"eid":3,"etitle":"group 1 expense 3","cid":3,"ctitle":"presents","uid":1,"uids":"2,3,5","amount":123.85,"timezoneoffset":-120,"ecreated":1437778724,"eupdated":1437778724},{"eid":4,"etitle":"group 1 expense 4","cid":1,"ctitle":"drinks","uid":3,"uids":"1,2,3","amount":99.63,"timezoneoffset":-120,"ecreated":1437865124,"eupdated":1437865124},{"eid":5,"etitle":"group 1 expense 5","cid":2,"ctitle":"tickets","uid":1,"uids":"1,2,3","amount":63,"timezoneoffset":-120,"ecreated":1437951524,"eupdated":1437951524},{"eid":6,"etitle":"group 1 expense 6","cid":3,"ctitle":"presents","uid":5,"uids":"1,2,3","amount":891.2,"timezoneoffset":-120,"ecreated":1438037924,"eupdated":1438037924},{"eid":7,"etitle":"group 1 expense 7","cid":1,"ctitle":"drinks","uid":5,"uids":"1,5,3","amount":12.24,"timezoneoffset":-120,"ecreated":1438124324,"eupdated":1438124324},{"eid":8,"etitle":"group 1 expense 8","cid":2,"ctitle":"tickets","uid":3,"uids":"2,3","amount":55.58,"timezoneoffset":-120,"ecreated":1438210724,"eupdated":1438210724},{"eid":9,"etitle":"group 1 expense 9","cid":3,"ctitle":"presents","uid":5,"uids":"1,3","amount":123.85,"timezoneoffset":-120,"ecreated":1438297124,"eupdated":1438297124},{"eid":10,"etitle":"group 1 expense 10","cid":1,"ctitle":"drinks","uid":2,"uids":"1,5","amount":99.63,"timezoneoffset":-120,"ecreated":1438383524,"eupdated":1438383524},{"eid":11,"etitle":"group 1 expense 11","cid":2,"ctitle":"tickets","uid":5,"uids":"2,3,5","amount":63,"timezoneoffset":-120,"ecreated":1438469924,"eupdated":1438469924},{"eid":12,"etitle":"group 1 expense 12","cid":3,"ctitle":"presents","uid":1,"uids":"1,2,5","amount":891.2,"timezoneoffset":-120,"ecreated":1438556324,"eupdated":1438556324},{"eid":13,"etitle":"group 1 expense 13","cid":1,"ctitle":"drinks","uid":5,"uids":"1,2","amount":12.24,"timezoneoffset":-120,"ecreated":1438642724,"eupdated":1438642724},{"eid":14,"etitle":"group 1 expense 14","cid":2,"ctitle":"tickets","uid":3,"uids":"1,3","amount":55.58,"timezoneoffset":-120,"ecreated":1438729124,"eupdated":1438729124},{"eid":15,"etitle":"group 1 expense 15","cid":3,"ctitle":"presents","uid":2,"uids":"1,2,3,5","amount":123.85,"timezoneoffset":-120,"ecreated":1438815524,"eupdated":1438815524},{"eid":16,"etitle":"group 1 expense 16","cid":1,"ctitle":"drinks","uid":2,"uids":"1,2,3,5","amount":99.63,"timezoneoffset":-120,"ecreated":1438901924,"eupdated":1438901924},{"eid":17,"etitle":"group 1 expense 17","cid":2,"ctitle":"tickets","uid":5,"uids":"1,5,3","amount":63,"timezoneoffset":-120,"ecreated":1438988324,"eupdated":1438988324},{"eid":18,"etitle":"group 1 expense 18","cid":2,"ctitle":"tickets","uid":1,"uids":"5,2,3","amount":891.2,"timezoneoffset":-120,"ecreated":1439074724,"eupdated":1439074724},{"eid":19,"etitle":"group 1 expense 19","cid":3,"ctitle":"presents","uid":3,"uids":"1,2,5","amount":123,"timezoneoffset":-120,"ecreated":1439161124,"eupdated":1439161124},{"eid":20,"etitle":"group 1 expense 20","cid":1,"ctitle":"drinks","uid":5,"uids":"1,2,3,5","amount":222.22,"timezoneoffset":-120,"ecreated":1439247524,"eupdated":1439247524}],"2":[{"eid":21,"etitle":"group 2 expense 1","cid":1,"ctitle":"drinks","uid":1,"uids":"1,4,6,7","amount":12.24,"timezoneoffset":-120,"ecreated":1437605924,"eupdated":1437605924},{"eid":22,"etitle":"group 2 expense 2","cid":2,"ctitle":"tickets","uid":7,"uids":"1,2,7","amount":55.58,"timezoneoffset":-120,"ecreated":1437692324,"eupdated":1437692324},{"eid":23,"etitle":"group 2 expense 3","cid":3,"ctitle":"presents","uid":1,"uids":"1,4,7","amount":123.85,"timezoneoffset":-120,"ecreated":1437778724,"eupdated":1437778724},{"eid":24,"etitle":"group 2 expense 4","cid":1,"ctitle":"drinks","uid":4,"uids":"1,4","amount":99.63,"timezoneoffset":-120,"ecreated":1437865124,"eupdated":1437865124},{"eid":25,"etitle":"group 2 expense 5","cid":2,"ctitle":"tickets","uid":7,"uids":"1,7","amount":63,"timezoneoffset":-120,"ecreated":1437951524,"eupdated":1437951524},{"eid":26,"etitle":"group 2 expense 6","cid":3,"ctitle":"presents","uid":7,"uids":"1,6,7","amount":891.2,"timezoneoffset":-120,"ecreated":1438037924,"eupdated":1438037924},{"eid":27,"etitle":"group 2 expense 7","cid":1,"ctitle":"drinks","uid":1,"uids":"1,4,7","amount":12.24,"timezoneoffset":-120,"ecreated":1438124324,"eupdated":1438124324},{"eid":28,"etitle":"group 2 expense 8","cid":2,"ctitle":"tickets","uid":7,"uids":"1,4,7","amount":55.58,"timezoneoffset":-120,"ecreated":1438210724,"eupdated":1438210724},{"eid":29,"etitle":"group 2 expense 9","cid":3,"ctitle":"presents","uid":6,"uids":"1,4","amount":123.85,"timezoneoffset":-120,"ecreated":1438297124,"eupdated":1438297124},{"eid":30,"etitle":"group 2 expense 10","cid":1,"ctitle":"drinks","uid":4,"uids":"1,4,6","amount":99.63,"timezoneoffset":-120,"ecreated":1438383524,"eupdated":1438383524},{"eid":31,"etitle":"group 2 expense 11","cid":2,"ctitle":"tickets","uid":4,"uids":"1,4,6","amount":63,"timezoneoffset":-120,"ecreated":1438469924,"eupdated":1438469924},{"eid":32,"etitle":"group 2 expense 12","cid":3,"ctitle":"presents","uid":1,"uids":"1,7,4","amount":891.2,"timezoneoffset":-120,"ecreated":1438556324,"eupdated":1438556324},{"eid":33,"etitle":"group 2 expense 13","cid":1,"ctitle":"drinks","uid":1,"uids":"1,6,4","amount":12.24,"timezoneoffset":-120,"ecreated":1438642724,"eupdated":1438642724},{"eid":34,"etitle":"group 2 expense 14","cid":2,"ctitle":"tickets","uid":6,"uids":"1,6","amount":55.58,"timezoneoffset":-120,"ecreated":1438729124,"eupdated":1438729124},{"eid":35,"etitle":"group 2 expense 15","cid":3,"ctitle":"presents","uid":4,"uids":"1,7","amount":123.85,"timezoneoffset":-120,"ecreated":1438815524,"eupdated":1438815524},{"eid":36,"etitle":"group 2 expense 16","cid":1,"ctitle":"drinks","uid":4,"uids":"1,4,7","amount":99.63,"timezoneoffset":-120,"ecreated":1438901924,"eupdated":1438901924},{"eid":37,"etitle":"group 2 expense 17","cid":2,"ctitle":"tickets","uid":7,"uids":"1,6,7","amount":63,"timezoneoffset":-120,"ecreated":1438988324,"eupdated":1438988324},{"eid":38,"etitle":"group 2 expense 18","cid":2,"ctitle":"tickets","uid":7,"uids":"6,7","amount":891.2,"timezoneoffset":-120,"ecreated":1439074724,"eupdated":1439074724},{"eid":39,"etitle":"group 2 expense 19","cid":3,"ctitle":"presents","uid":6,"uids":"4,6,7","amount":123,"timezoneoffset":-120,"ecreated":1439161124,"eupdated":1439161124},{"eid":40,"etitle":"group 2 expense 20","cid":1,"ctitle":"drinks","uid":6,"uids":"1,4,6","amount":222.22,"timezoneoffset":-120,"ecreated":1439247524,"eupdated":1439247524}],"3":[{"eid":41,"etitle":"group 3 expense 1","cid":1,"ctitle":"drinks","uid":1,"uids":"1,3,5,8","amount":12.24,"timezoneoffset":-120,"ecreated":1437605924,"eupdated":1437605924},{"eid":42,"etitle":"group 3 expense 2","cid":2,"ctitle":"tickets","uid":3,"uids":"1,8,3","amount":55.58,"timezoneoffset":-120,"ecreated":1437692324,"eupdated":1437692324},{"eid":43,"etitle":"group 3 expense 3","cid":3,"ctitle":"presents","uid":5,"uids":"1,8,3","amount":123.85,"timezoneoffset":-120,"ecreated":1437778724,"eupdated":1437778724},{"eid":44,"etitle":"group 3 expense 4","cid":1,"ctitle":"drinks","uid":8,"uids":"1,5,3","amount":99.63,"timezoneoffset":-120,"ecreated":1437865124,"eupdated":1437865124},{"eid":45,"etitle":"group 3 expense 5","cid":2,"ctitle":"tickets","uid":1,"uids":"1,5,3","amount":63,"timezoneoffset":-120,"ecreated":1437951524,"eupdated":1437951524},{"eid":46,"etitle":"group 3 expense 6","cid":3,"ctitle":"presents","uid":8,"uids":"5,8,3","amount":891.2,"timezoneoffset":-120,"ecreated":1438037924,"eupdated":1438037924},{"eid":47,"etitle":"group 3 expense 7","cid":1,"ctitle":"drinks","uid":5,"uids":"5,8,3","amount":12.24,"timezoneoffset":-120,"ecreated":1438124324,"eupdated":1438124324},{"eid":48,"etitle":"group 3 expense 8","cid":2,"ctitle":"tickets","uid":3,"uids":"8,3","amount":55.58,"timezoneoffset":-120,"ecreated":1438210724,"eupdated":1438210724},{"eid":49,"etitle":"group 3 expense 9","cid":3,"ctitle":"presents","uid":5,"uids":"5,3","amount":123.85,"timezoneoffset":-120,"ecreated":1438297124,"eupdated":1438297124},{"eid":50,"etitle":"group 3 expense 10","cid":1,"ctitle":"drinks","uid":1,"uids":"1,5,3,8","amount":99.63,"timezoneoffset":-120,"ecreated":1438383524,"eupdated":1438383524},{"eid":51,"etitle":"group 3 expense 11","cid":2,"ctitle":"tickets","uid":3,"uids":"1,5,3,8","amount":63,"timezoneoffset":-120,"ecreated":1438469924,"eupdated":1438469924},{"eid":52,"etitle":"group 3 expense 12","cid":3,"ctitle":"presents","uid":8,"uids":"1,5,3,8","amount":891.2,"timezoneoffset":-120,"ecreated":1438556324,"eupdated":1438556324},{"eid":53,"etitle":"group 3 expense 13","cid":1,"ctitle":"drinks","uid":5,"uids":"1,5,3","amount":12.24,"timezoneoffset":-120,"ecreated":1438642724,"eupdated":1438642724},{"eid":54,"etitle":"group 3 expense 14","cid":2,"ctitle":"tickets","uid":3,"uids":"5,3,8","amount":55.58,"timezoneoffset":-120,"ecreated":1438729124,"eupdated":1438729124},{"eid":55,"etitle":"group 3 expense 15","cid":3,"ctitle":"presents","uid":8,"uids":"1,5,8","amount":123.85,"timezoneoffset":-120,"ecreated":1438815524,"eupdated":1438815524},{"eid":56,"etitle":"group 3 expense 16","cid":1,"ctitle":"drinks","uid":1,"uids":"1,5,8","amount":99.63,"timezoneoffset":-120,"ecreated":1438901924,"eupdated":1438901924},{"eid":57,"etitle":"group 3 expense 17","cid":2,"ctitle":"tickets","uid":3,"uids":"1,3,8","amount":63,"timezoneoffset":-120,"ecreated":1438988324,"eupdated":1438988324},{"eid":58,"etitle":"group 3 expense 18","cid":2,"ctitle":"tickets","uid":1,"uids":"1,3,8","amount":891.2,"timezoneoffset":-120,"ecreated":1439074724,"eupdated":1439074724},{"eid":59,"etitle":"group 3 expense 19","cid":3,"ctitle":"presents","uid":8,"uids":"1,5,3,8","amount":123,"timezoneoffset":-120,"ecreated":1439161124,"eupdated":1439161124},{"eid":60,"etitle":"group 3 expense 20","cid":1,"ctitle":"drinks","uid":8,"uids":"1,5,3,8","amount":222.22,"timezoneoffset":-120,"ecreated":1439247524,"eupdated":1439247524}]}');
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);






$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

/*// handle GET requests for /articles
$app->get('/articles', function ($request, $response, $args) {
//$app->get('/', function () use ($app) {
    // query database for all articles
    $articles = R::find('articles');

    // send response header for JSON content type
    // $app->response()->header('Content-Type', 'application/json');

    // return JSON-encoded response body with query results
    //echo json_encode(R::exportAll($articles));

    $response->write(json_encode(R::exportAll($articles)));
    return $response;
});*/


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();


/*

groups update json
 {
	"1": {

		"gid": 1,
		"currency": "EUR",
		"name": "Delluf",
		"description": "Mannenuitjes"
	},
	"8": {
		"gid": 8,
		"currency": "EUR",
		"name": "Broers",
		"description": "Broertjes"
	}
}

group categories update json
{
	"1": {
		"gid": 1,
		"sort": 1,
	},
	"8": {
		"gid": 8,
		"sort": 2,
	}
}

user update json
 {
		"uid": 1,
		"firstName": "Jane",
		"lastName": "Doe",
		"nickName": "JD"
		"realname": "Jennifer Diade,
		"currency": "EUR",
		"email": "jd@diade-email.com",
}
 */