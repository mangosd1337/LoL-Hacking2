<?php

$SQL[] = "ALTER TABLE cal_events ADD event_all_day TINYINT NOT NULL DEFAULT '0';";
$SQL[] = "UPDATE cal_events SET event_all_day=1 WHERE TIME(event_start_date) = '00:00:00';";
