<?php

\IPS\Db::i()->insert('auth_history', [
    'account_id'        => 1337,
    'time'              => date('Y-m-d H:i:s'),
    'game_id'           => 1338,
    'custom_game'       => true,
    'game_version'      => '6.14',
    'game_region'       => 'EUW'
]);
