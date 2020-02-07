<?php
/**
 * @brief		4.1.12.1 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 May 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_101031;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.12.1 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Update reports with missing report dates
	 * @link https://invisionpower.beanstalkapp.com/ips4/changesets/865c057c90bc98fbf05f85bd9c76b227b9805a8b
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		\IPS\Db::i()->update( 'core_rc_index', 'last_updated=first_report_date', "last_updated IS NULL" );
		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Fixing missing report center last updated dates";
	}
}