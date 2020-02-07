<?php

$conf = [
    'database' => [
        'database_type' => 'mysql',
        'server' => 'localhost',
        'database_name' => 'elobuddy_live',
        'username' => 'buddy',
        'password' => '7hpxXYe5CvCFaRAfDLEMrw43uFYsNjJL7a8WLMEz88'
    ],
    'throttler' => [
        'hits' => 25,
        'session_duration' => 5 * 60, // seconds
        'ban_time' => 3 * 60 // seconds
    ],
    'IPB_location' => '/home/elobuddy.net'
];
