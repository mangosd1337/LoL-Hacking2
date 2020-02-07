<?php
/**
 * @brief		whosOnline Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Jul 2014
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
 * whosOnline Widget
 */
class _whosOnline extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'whosOnline';
	
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
		
		/* Init */
		$members     = array();
		$memberCount = 0;
		$guests      = 0;
		$anonymous   = 0;
		
		/* Query */
		$where = array(
			array( 'core_sessions.running_time>' . \IPS\DateTime::create()->sub( new \DateInterval( 'PT120M' ) )->getTimeStamp() ),
			array( 'core_groups.g_hide_online_list=0' )
		);
		foreach( \IPS\Db::i()->select( 'core_sessions.member_id,core_sessions.member_name,core_sessions.seo_name,core_sessions.member_group,core_sessions.login_type', 'core_sessions', $where, 'core_sessions.running_time DESC' )->join( 'core_groups', 'core_sessions.member_group=core_groups.g_id' ) as $row )
		{
			switch ( $row['login_type'] )
			{
				/* Not-anonymous Member */
				case \IPS\Session\Front::LOGIN_TYPE_MEMBER:
					if ( $row['member_id'] != \IPS\Member::loggedIn()->member_id ) // We add them manually to make sure they go at the top of the list
					{
						if ( $row['member_name'] )
						{
							$members[ $row['member_id'] ] = $row;
						}
						else
						{
							$guests += 1;
						}
					}
					break;
					
				/* Anonymous member */
				case \IPS\Session\Front::LOGIN_TYPE_ANONYMOUS:
					$anonymous += 1;
					break;
					
				/* Guest */
				case \IPS\Session\Front::LOGIN_TYPE_GUEST:
					$guests += 1;
					break;
			}
		}
		$memberCount = count( $members );
		
		/* If it's on the sidebar (rather than at the bottom), we want to limit it to 60 so we don't take too much space */
		if ( $this->orientation === 'vertical' and count( $members ) >= 60 )
		{
			$members = array_slice( $members, 0, 60 );
		}
		
		/* Add ourselves at the top of the list */
		if( \IPS\Member::loggedIn()->member_id and !\IPS\Member::loggedIn()->group['g_hide_online_list'] )
		{
			$memberCount++;
						
			$members = array_merge( array( \IPS\Member::loggedIn()->member_id => array(
				'member_id'			=> \IPS\Member::loggedIn()->member_id,
				'member_name'		=> \IPS\Member::loggedIn()->name,
				'seo_name'			=> \IPS\Member::loggedIn()->members_seo_name,
				'member_group'		=> \IPS\Member::loggedIn()->member_group_id
			) ), $members );
		}
		
		/* Display */
		return $this->output( $members, $memberCount, $guests, $anonymous );
	}
}
