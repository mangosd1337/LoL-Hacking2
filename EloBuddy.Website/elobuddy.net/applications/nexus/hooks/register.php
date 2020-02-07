//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class nexus_hook_register extends _HOOK_CLASS_
{
	/**
	 * Register
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'nexus', 'store' ) ) and ( \IPS\Settings::i()->nexus_reg_force or ( !isset( \IPS\Request::i()->noPurchase ) and \IPS\Db::i()->select( 'COUNT(*)', 'nexus_packages', 'p_reg=1' )->first() ) ) )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=store&controller=store&do=register', 'front', 'store' ) );
		}
		
		return call_user_func_array( 'parent::manage', func_get_args() );
	}
}