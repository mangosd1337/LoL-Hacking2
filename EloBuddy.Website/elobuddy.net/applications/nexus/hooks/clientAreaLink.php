//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    exit;
}

class nexus_hook_clientAreaLink extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'userBar' => 
  array (
    1 => 
    array (
      'selector' => '#cUserLink',
      'type' => 'add_before',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'store\' ) )}}
	{template="cartHeader" app="nexus" group="store" params=""}
{{endif}}',
    ),
    2 => 
    array (
      'selector' => '#elSignInLink',
      'type' => 'add_before',
      'content' => '{{if \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( \'nexus\', \'store\' ) )}}
	{template="cartHeader" app="nexus" group="store" params=""}
{{endif}}',
    )
  ),
), parent::hookData() );
}
/* End Hook Data */












































}