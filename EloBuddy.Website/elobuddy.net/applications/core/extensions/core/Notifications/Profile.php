<?php
/**
 * @brief		Notification Options
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class _Profile
{
	/**
	 * Get configuration
	 *
	 * @param	\IPS\Member|null	$member	The member
	 * @return	array
	 */
	public function getConfiguration( $member )
	{
		$return = array();
				
		if ( $member === NULL or $member->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) )
		{
			$return['member_follow'] = array( 'default' => array( 'inline' ), 'disabled' => array() );
			
			if ( $member === NULL or $member->pp_setting_count_comments or ( ( !$member->group['gbw_no_status_update'] or !$member->group['gbw_no_status_import'] ) and !$member->members_bitoptions['bw_no_status_update'] ) )
			{
				$return['profile_comment'] = array( 'default' => array( 'inline' ), 'disabled' => array() );
			}
			
			$return['profile_reply']	= array( 'default' => array( 'inline' ), 'disabled' => array() );
			$return['new_status']		= array( 'default' => array( 'inline' ), 'disabled' => array() );
		}
		
		return $return;
	}
	
	/**
	 * Parse notification: member_follow
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> new \IPS\Http\Url( ... ),		// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_member_follow( $notification )
	{
		$member = $notification->item;

		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__member_follow', FALSE, array( 'sprintf' => array( $member->name ) ) ),
				'url'		=> $member->url(),
				'author'	=> $member,
		);
	}
	
	/**
	 * Parse notification: profile_comment
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> new \IPS\Http\Url( ... ),		// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_profile_comment( $notification )
	{
		
		$item = $notification->item;
		
		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__profile_comment', FALSE, array( 'sprintf' => array( $item->author()->name ) ) ),
				'url'		=> $item->url(),
				'content'	=> $item->content(),
				'author'	=> \IPS\Member::load( $item->author()->member_id ),
		);
	}

	/**
	 * Parse notification: new_status
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> new \IPS\Http\Url( ... ),		// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_new_status( $notification )
	{
		
		$item = $notification->item;
		
		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_status', FALSE, array( 'sprintf' => array( $item->author()->name ) ) ),
				'url'		=> $item->url(),
				'content'	=> $item->content(),
				'author'	=> \IPS\Member::load( $item->author()->member_id ),
		);
	}
	
	/**
	 * Parse notification: profile_reply
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @return	array
	 * @code
	 return array(
	 'title'		=> "Mark has replied to A Topic",	// The notification title
	 'url'		=> new \IPS\Http\Url( ... ),		// The URL the notification should link to
	 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 // explains what the notification is about - just include any appropriate content.
	 // For example, if the notification is about a post, set this as the body of the post.
	 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 );
	 * @endcode
	 */
	public function parse_profile_reply( $notification )
	{
	
		$item = $notification->item;
		
		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__profile_reply', FALSE, array( 'sprintf' => array( $item->author()->name ) ) ),
				'url'		=> $item->item()->url(),
				'content'	=> $item->content(),
				'author'	=> \IPS\Member::load( $item->author()->member_id ),
		);
	}
}