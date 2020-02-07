<?php
/**
 * @brief		activeUsers Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * activeUsers Widget
 */
class _activeUsers extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'activeUsers';
	
	/**
	 * @brief	App
	 */
	public $app = 'core';
	
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		/* Do we have permission? */
		if ( !\IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'online' ) ) )
		{
			return "";
		}
				
		/* Build WHERE clause */
		$parts = parse_url( (string) \IPS\Request::i()->url() );
		$url = $parts['scheme'] . "://" . $parts['host'] . $parts['path'];

		$where = array(
			array( 'core_sessions.login_type=' . \IPS\Session\Front::LOGIN_TYPE_MEMBER ),
			array( 'core_sessions.current_appcomponent=?', \IPS\Dispatcher::i()->application->directory ),
			array( 'core_sessions.current_module=?', \IPS\Dispatcher::i()->module->key ),
			array( 'core_sessions.current_controller=?', \IPS\Dispatcher::i()->controller ),
			array( 'core_sessions.running_time>' . \IPS\DateTime::create()->sub( new \DateInterval( 'PT120M' ) )->getTimeStamp() ),
			array( 'core_sessions.location_url IS NOT NULL AND location_url LIKE ?', "{$url}%" ),
			array( 'core_sessions.member_id IS NOT NULL' ),
			array( 'core_groups.g_hide_online_list=0' )
		);

		if( \IPS\Request::i()->id )
		{
			$where[] = array( 'core_sessions.current_id = ?', \IPS\Request::i()->id );
		}

		/* Get members */
		if ( $this->orientation === 'vertical' )
		{
			$members = \IPS\Db::i()->select( 'core_sessions.member_id,core_sessions.member_name,core_sessions.seo_name,core_sessions.member_group', 'core_sessions', $where, 'core_sessions.running_time DESC', 60, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )->join( 'core_members', 'core_members.member_id=core_sessions.member_id' )->join( 'core_groups', 'core_members.member_group_id=core_groups.g_id' )->setKeyField( 'member_id' );
			$memberCount = $members->count( TRUE );			
		}
		else
		{
			$members = \IPS\Db::i()->select( 'core_sessions.member_id,core_sessions.member_name,core_sessions.seo_name,core_sessions.member_group', 'core_sessions', $where, 'core_sessions.running_time DESC' )->join( 'core_members', 'core_members.member_id=core_sessions.member_id' )->join( 'core_groups', 'core_members.member_group_id=core_groups.g_id' )->setKeyField( 'member_id' );
			$memberCount = $members->count();
		}

		$members = iterator_to_array( $members );

		/* Make sure the logged in member is not included as we're going to add them on to the start of the list later */
		unset( $members[ \IPS\Member::loggedIn()->member_id ] );

		if( \IPS\Member::loggedIn()->member_id )
		{
			if( !\IPS\Member::loggedIn()->group['g_hide_online_list'] )
			{
				if( !isset( $members[ \IPS\Member::loggedIn()->member_id ] ) )
				{
					$memberCount++;
				}

				$members = array_merge( array( \IPS\Member::loggedIn()->member_id => array(
					'member_id'			=> \IPS\Member::loggedIn()->member_id,
					'member_name'		=> \IPS\Member::loggedIn()->name,
					'seo_name'			=> \IPS\Member::loggedIn()->members_seo_name,
					'member_group'		=> \IPS\Member::loggedIn()->member_group_id
				) ), $members );

			}
		}

		/* Display */
		return $this->output( $members, $memberCount );
	}
}
