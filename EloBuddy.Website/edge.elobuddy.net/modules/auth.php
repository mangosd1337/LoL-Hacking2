<?php

if(!isset($_POST['MessageAuthInfo']))
    exit;


$msgAuthInfo = json_decode($_POST['MessageAuthInfo']);
$req_fields = [ 'Username', 'PasswordHash', 'GameId', 'IsCustomGame', 'GameVersion', 'Region' ];

/*
foreach ($req_fields as $k) {
    if(!isset($_POST['MessageAuthInfo'][$k])) {
        exit;
    }
}*/

\IPS\Db::i()->insert('auth_history', [
    'account_name'      => $msgAuthInfo->Username,
    'time'              => date('Y-m-d H:i:s'),
    'game_id'           => $msgAuthInfo->GameId,
    'custom_game'       => $msgAuthInfo->IsCustomGame,
    'game_version'      => $msgAuthInfo->GameVersion,
    'game_region'       => $msgAuthInfo->Region
]);
