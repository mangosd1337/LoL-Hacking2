<?php


if(empty($_GET['action']))
{
  exit('kek no');
}

$action = $_GET['action'];
$modulesDir = __DIR__ . '/modules';


include '../elobuddy.net/init.php';


switch ($action) {
    case 'verifyAPIKey':
        include "{$modulesDir}/verifyAPIKey.php";
    break;

    case 'getEmoticons':
        include "{$modulesDir}/getEmoticons.php";
    break;

    case 'auth':
        include "{$modulesDir}/auth.php";
    break;

    case 'test':
        include "{$modulesDir}/test.php";
    break;

    default:
        exit('kek no');
    break;
}
