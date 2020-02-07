//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class nexus_hook_allowStoreRegistrationGuests extends _HOOK_CLASS_
{
	/**
	 * Define that the page should load even if the user is banned and not logged in
	 *
	 * @return	bool
	 */
	protected function notAllowedBannedPage()
	{
		if( $this->application->directory == 'nexus' AND \IPS\Settings::i()->nexus_reg_force AND ( $this->module->key == 'store' OR $this->module->key == 'checkout' ) )
		{
			return FALSE;
		}

		return parent::notAllowedBannedPage();
	}
}