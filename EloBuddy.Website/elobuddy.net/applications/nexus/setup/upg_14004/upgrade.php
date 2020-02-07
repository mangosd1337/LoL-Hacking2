<?php
/**
 * @brief		1.4.2 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		19 Dec 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_14004;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 1.4.2 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Drop p_image if it exists
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		if ( \IPS\Db::i()->checkForColumn( 'nexus_packages', 'p_image' ) )
		{
			\IPS\Db::i()->dropColumn( 'nexus_packages', 'p_image' );
		}
		return TRUE;
	}
}