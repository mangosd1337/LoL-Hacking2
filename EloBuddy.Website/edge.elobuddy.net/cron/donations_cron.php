<?php
ignore_user_abort(true);
set_time_limit(1800);
require_once '/home/elobuddy.net/init.php';

//$lastID = (int)file_get_contents('last_id') - 4000;
//$maxID = \IPS\Db::i()->select(["i_id"], "nexus_invoices", null, 'i_id DESC')->first();
//AND i_id <= {$maxID} AND i_id > {$lastID}

$upgradedUsers = explode("\n", file_get_contents('upgraded_users'));



$queryObj = \IPS\Db::i()->select(["i_member"], "nexus_invoices", "i_status = 'paid' AND i_total >= 10.00 ", null, null, null, null, \IPS\Db::SELECT_DISTINCT);

$count =  $queryObj->count();
if($count === 0)
{
  logStatus("No users to upgrade (1).");
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

$upgradeUsers = [];

foreach ($users as $userID) {
  if(! in_array($userID, $upgradedUsers) && !empty($userID)){
    $upgradeUsers[] = $userID;
  }
}

// clear user array for memory shit
$users = [];

$count =  count($upgradeUsers);
if($count === 0)
{
  logStatus("No users to upgrade (2).");
  exit;
}

$where = 'member_id = ' . implode(' OR member_id = ', $upgradeUsers);
$updateObj = \IPS\Db::i()->update('core_members', 'member_group_id = 8', $where);



// Logging
$usrs = implode(',', $upgradeUsers);

$log = "Updated total: {$count} users (User IDs: {$usrs}).";
logStatus($log);


$upgradedUsersCont = implode("\n", array_merge($upgradedUsers, $upgradeUsers));

file_put_contents('upgraded_users', $upgradedUsersCont);

// Helper Func
function logStatus($text){
  $date = date('m/d/Y H:i:s', time());
  file_put_contents('update_logs.txt', "[{$date}]: {$text}" . PHP_EOL, FILE_APPEND);
}
