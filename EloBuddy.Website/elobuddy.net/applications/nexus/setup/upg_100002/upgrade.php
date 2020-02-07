<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		30 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_100002;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Fix SEO titles
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\Db::i()->update( 'nexus_packages', array( 'p_seo_name' => null ) );

		return true;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Resetting commerce friendly URL titles";
	}
}