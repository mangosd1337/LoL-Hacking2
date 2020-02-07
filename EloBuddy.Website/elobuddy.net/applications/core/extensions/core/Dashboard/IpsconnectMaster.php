<?php
/**
 * @brief		Dashboard extension: Show a notice if there are failed IPS Connect communications
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		03 Dec 2013
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
 * @brief	Dashboard extension: Show a notice if there are failed IPS Connect communications
 */
class _IpsconnectMaster
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return TRUE;
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 * @note	We may want to consider doing something when there are failed queued requests but the slave record no longer exists. For now I don't want to automatically
	 	clear them because it may be indicative of a bug elsewhere, but down the road we may want to simply delete the requests from the queue table and not show the failure.
	 */
	public function getBlock()
	{
		/* Are we deleting the slave? */
		if( \IPS\Request::i()->deleteSlave )
		{
			\IPS\Session::i()->csrfCheck();

			\IPS\Db::i()->delete( 'core_ipsconnect_queue', array( 'slave_id=?', intval( \IPS\Request::i()->deleteSlave ) ) );
			\IPS\Db::i()->delete( 'core_ipsconnect_slaves', array( 'slave_id=?', intval( \IPS\Request::i()->deleteSlave ) ) );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Db::i()->select( 'COUNT(*)', 'core_ipsconnect_slaves' )->first() ), array( 'conf_key=?', 'connect_slaves' ) );
			unset( \IPS\Data\Store::i()->settings );

			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('connect_slave_deleted');
			\IPS\Session::i()->log( 'acplog__slave_deleted' );
		}

		/* Do we have any fails to show? */
		$fails = \IPS\Db::i()->select( 'DISTINCT(slave_id)', 'core_ipsconnect_queue' );

		if( count($fails) )
		{
			$failures	= array();

			foreach( $fails as $slave )
			{
				try
				{
					$slave	= \IPS\Db::i()->select( '*', 'core_ipsconnect_slaves', array( 'slave_id=?', $slave ) )->first();
					$fails	= count( \IPS\Db::i()->select( 'id', 'core_ipsconnect_queue', array( 'slave_id=?', $slave['slave_id'] ) ) );

					$failures[]	= array(
						'slave'	=> $slave,
						'count'	=> $fails
					);
				}
				catch( \UnderflowException $e )
				{
					/* This means that the slave record doesn't actually exist which would only happen in theory if someone played with the URL */
				}
			}
			
			return \IPS\Theme::i()->getTemplate( 'dashboard' )->connectFailures( $failures, count($failures) );
		}
		else
		{
			return \IPS\Theme::i()->getTemplate( 'dashboard' )->noConnectFailures();
		}
	}
}