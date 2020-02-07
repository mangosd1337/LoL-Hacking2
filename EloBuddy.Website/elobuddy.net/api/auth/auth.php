<?php
include 'inc/config.php';
include 'inc/classes/cacheManager.php';
include 'inc/classes/database.php';
include 'inc/classes/throttler.php';
include 'inc/classes/user.php';

$db = new medoo($conf['database']);
$cache = new cacheManager();
$throttler = new throttler($db, $cache, $conf['throttler']);
$user = new user($conf['IPB_location']);

//$IP = $_SERVER['REMOTE_ADDR'];

if(empty($_GET['username']) || empty($_GET['password']))
    exit('Params missing');

$username = $_GET['username'];
$password = $_GET['password'];


$response = ['success' => false];
$response['errorMsg'] = null;
$response['user'] = [
            'displayName' => null,
            'groupName' => null,
            'groupID' => null,
            'avatar' => null
        ];

if(!$throttler->isThrottled($username)){
    // init ipb
    include_once $conf['IPB_location'] . '/init.php';
    // Now we do our stuff
    if($user->authenticate($username, $password)){
        $response['success'] = true;
        $response['user'] = [
            'displayName' => $user->displayName,
            'groupName' =>  $user->groupName,
            'groupID' =>  $user->groupID,
            'avatar' => base64_encode($user->getAvatar())
        ];
    }else{
        $response['errorMsg'] = 'Invalid username/password.';
    }

}else{
    $banTime = $conf['throttler']['ban_time'];
    $response['errorMsg'] =  "Max requests exceeded. Try again in {$banTime} seconds.";
}

echo json_encode($response);
