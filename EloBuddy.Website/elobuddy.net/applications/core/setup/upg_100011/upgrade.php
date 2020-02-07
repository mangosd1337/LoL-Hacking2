<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Jan 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_100011;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Beta 6 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Step 1
	 * Fix IPS Connect Slave Count
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Db::i()->select( 'COUNT(*)', 'core_ipsconnect_slaves' )->first() ), array( 'conf_key=?', 'connect_slaves' ) );
		unset( \IPS\Data\Store::i()->settings );

		return TRUE;
	}

	/**
	 * Step 2
	 * Fix the imported ipb3 words ( http://community.invisionpower.com/4bugtrack/beta-6-still-deleting-inactive-members-r2074/ )
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		$existingApplications = array_keys( \IPS\Application::applications() );
		\IPS\Db::i()->delete( 'core_sys_lang_words', 'word_plugin <> NULL AND ' . \IPS\Db::i()->in( 'word_app', $existingApplications, TRUE ) );

		return TRUE;
	}

	/**
	 * Step 3
	 * Remove search cleanup task
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step3()
	{
		\IPS\Db::i()->delete('core_tasks', array('`key`=? and app=?', 'searchcleanup', 'core' ) );

		return TRUE;
	}
}