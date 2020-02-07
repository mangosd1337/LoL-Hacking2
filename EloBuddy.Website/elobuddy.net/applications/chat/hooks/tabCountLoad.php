//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class chat_hook_tabCountLoad extends _HOOK_CLASS_
{
	/**
	 * Constructor
	 * Gets stores which are always needed to save individual queries
	 *
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();
		if ( \IPS\CACHE_METHOD === 'None' )
		{
			$this->initLoad[] = 'chatters';
		}
	}

}