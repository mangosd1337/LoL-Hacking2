<?php
/**
 * @brief		Dashboard extension: Failed Admin Logins
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Failed Admin Logins
 */
class _FailedLogins
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return \IPS\Member::loggedIn()->hasAcpRestriction( 'core' , 'settings', 'login_manage' );

	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		$logins = \IPS\Db::i()->select(
			array( 'admin_id', 'admin_ip_address', 'admin_username', 'admin_time' ),
			'core_admin_login_logs',
			array( 'admin_success=?', FALSE ),
			'admin_time DESC',
			array( 0, 3 )
		);

		return \IPS\Theme::i()->getTemplate( 'dashboard' )->failedLogins( $logins );
	}
}