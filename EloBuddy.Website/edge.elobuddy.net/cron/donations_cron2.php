<?php

require_once '/home/elobuddy.net/init.php';

$lastID = (int)file_get_contents('last_id');
$maxID = \IPS\Db::i()->select(["i_id"], "nexus_invoices", null, 'i_id DESC')->first();

$queryObj = \IPS\Db::i()->select(["i_member"], "nexus_invoices", "i_status = 'paid' AND i_total >= 1.00 AND i_id <= {$maxID} AND i_id > {$lastID}", null, null, null, null, \IPS\Db::SELECT_DISTINCT);

$count =  $queryObj->count();
if($count === 0)
{
  logStatus("No users to update. From (i_id): {$lastID} to {$maxID}");
  file_put_contents('last_id', $maxID);
  exit;
}

$queryObj->rewind();

$users = [];
$users[] = (int) $queryObj->first();

for ($i=2; $i <= $count; $i++)
{
  $queryObj->next();
  $users[] = (int) $queryObj->current();
}

$where = 'member_id = ' . implode(' OR member_id = ', $users);
$updateObj = \IPS\Db::i()->update('core_members', 'member_group_id = 8', $where);

file_put_contents('last_id', $maxID);

// Logging
$usrs = implode(',', $users);
$log = "Updated total: {$count} users (User IDs: {$usrs}). From (i_id): {$lastID} to {$maxID}";
logStatus($log);


// Helper Func
function logStatus($text){
  $date = date('m/d/Y H:i:s', time());
  file_put_contents('update_logs.txt', "[{$date}]: {$text}" . PHP_EOL, FILE_APPEND);
}
