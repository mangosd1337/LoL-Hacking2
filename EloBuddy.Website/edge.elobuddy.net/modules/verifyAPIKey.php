<?php


if(empty($_GET['session']))
{
  header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error", true, 500);
  exit('Session is missing');
}

$session = $_GET['session'];
$queryObj = \IPS\Db::i()->select(["member_id", "member_name", "member_group"], "core_sessions", ['id=?', $session]);

if($queryObj->count() === 0)
{
  header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error", true, 500);
  exit('No session');
}

$queryObj->rewind();
$memberData = $queryObj->first();

$member = \IPS\Member::load($memberData['member_id']);

// Get the Group Name cause IPB is retarded
$groupName = \IPS\Db::i()->select(["word_custom"], "core_sys_lang_words", ['word_key=?', "core_group_{$memberData['member_group']}"])->first();



$json = [
    'uid' => $memberData['member_id'],
    'group_name' => $groupName,
    'displayName' => $member->real_name,
    'formattedName' => preg_replace('/[0-9a-f]{32}/i', $member->real_name, $member->groupName),
    'group_id' => $memberData['member_group']
];


header('Content-Type: application/json');
echo json_encode($json);


