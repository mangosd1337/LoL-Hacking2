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
class _Messenger
{
	/**
	 * Get configuration
	 *
	 * @param	\IPS\Member"null	$member	The member
	 * @return	array
	 */
	public function getConfiguration( $member )
	{
		$return = array();
		if ( $member === NULL or ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) and !$member->members_disable_pm ) )
		{
			return array(
				'new_private_message'	=> array( 'default' => array( 'email' ), 'disabled' => array( 'inline' ) ),
				'private_message_added'	=> array( 'default' => array( 'email' ), 'disabled' => array( 'inline' ) ),
			);
		}
		return array();
	}
	
	/**
	 * Parse notification: new private message
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
	public function parse_new_private_message( $notification )
	{
		$item = $notification->item;

		if ( !$item )
		{
			throw new \OutOfRangeException;
		}

		$idColumn = $item::$databaseColumnId;
		$commentClass = $item::$commentClass;

		try
		{
			$comment = $commentClass::loadAndCheckPerms( $notification->item_sub_id );
		}
		catch( \OutOfRangeException $e )
		{
			return;
		}

		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_private_message', FALSE, array( 'sprintf' => array( $comment->author()->name ) ) ),
				'url'		=> $item->url(),
				'content'	=> $comment->content(),
				'author'	=> \IPS\Member::load( $comment->author()->member_id ),
		);
	}
	
	/**
	 * Parse notification: private message added
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
	public function parse_private_message_added( $notification )
	{
		$item = $notification->item;
	
		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__private_message_added', FALSE, array( 'sprintf' => array( $item->author()->name ) ) ),
				'url'		=> $item->item()->url(),
				'content'	=> $item->content(),
				'author'	=> \IPS\Member::load( $item->author()->member_id ),
		);
	}
}