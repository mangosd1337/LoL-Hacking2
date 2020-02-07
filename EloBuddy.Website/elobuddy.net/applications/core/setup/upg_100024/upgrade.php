<?php
/**
 * @brief		4.0.1 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Apr 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_100024;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.1 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Fix {blank} settings
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 * @see		http://community.invisionpower.com/4bugtrack/settings-value-blank-r4972/
	 */
	public function step1()
	{
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_value=?', '{blank}' ) );


		return TRUE;
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Fixing old settings";
	}
}