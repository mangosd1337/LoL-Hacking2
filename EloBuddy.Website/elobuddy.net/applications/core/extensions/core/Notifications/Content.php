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
class _Content
{
	/**
	 * Get configuration
	 *
	 * @param	\IPS\Member|null	$member	The member
	 * @return	array
	 */
	public function getConfiguration( $member )
	{
		$return = array(
			'new_content'		=> array( 'default' => array( 'email' ), 'disabled' => array() ),
			'new_comment'		=> array( 'default' => array( 'email' ), 'disabled' => array() ),
			'new_review'		=> array( 'default' => array( 'email' ), 'disabled' => array() ),
			'follower_content'	=> array( 'default' => array(), 'disabled' => array() ),
			'quote'				=> array( 'default' => array( 'inline' ), 'disabled' => array() ),
			'mention'			=> array( 'default' => array( 'inline' ), 'disabled' => array() ),
		);
		
		if ( $member === NULL or \IPS\Settings::i()->reputation_enabled )
		{
			$return['new_likes'] = array( 'default' => array( 'inline' ), 'disabled' => array() );
		}
				
		if ( $member === NULL or $member->modPermission( 'mod_see_warn' ) )
		{
			$return['warning_mods'] = array( 'default' => array( 'inline' ), 'disabled' => array(), 'icon' => 'lock' );
		}
		
		if ( $member === NULL or $member->modPermission( 'can_view_reports' ) )
		{
			$return['report_center'] = array( 'default' => array( 'inline', 'email' ), 'disabled' => array(), 'icon' => 'lock' );
		}
		
		if ( $member === NULL or $member->modPermission( 'can_view_hidden_content' ) )
		{
			$return['unapproved_content'] = array( 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'lock' );
		}
		else
		{
			foreach ( \IPS\Content::routedClasses( TRUE, TRUE ) as $class )
			{
				if ( in_array( 'IPS\Content\Hideable', class_implements( $class ) ) )
				{
					if ( $member->modPermission( 'can_view_hidden_' . $class::$title ) )
					{
						$return['unapproved_content'] = array( 'default' => array( 'email' ), 'disabled' => array(), 'icon' => 'lock' );
						break;
					}
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Parse notification: new_content
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
	public function parse_new_content( $notification )
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new \OutOfRangeException;
		}

		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_content', FALSE, array( 'sprintf' => array( $item->author()->name, mb_strtolower( $item->indefiniteArticle() ), $item->container()->_title, $item->mapped('title') ) ) ),
			'url'		=> $notification->item->url(),
			'content'	=> $notification->item->content(),
			'author'	=> $notification->item->author(),
			'unread'	=> (bool) ( $item->unread() )
		);
	}
	
	/**
	 * Parse notification: new_content_bulk
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
	public function parse_new_content_bulk( $notification )
	{
		$node = $notification->item;
		
		if ( !$node )
		{
			throw new \OutOfRangeException;
		}
				
		if ( $notification->extra )
		{
			/* \IPS\Notification->extra will always be an array, but for bulk content notifications we are only storing a single member ID,
				so we need to grab just the one array entry (the member ID we stored) */
			$memberId = $notification->extra;

			if( is_array( $memberId ) )
			{
				$memberId = array_pop( $memberId );
			}

			$author = \IPS\Member::load( $memberId );
		}
		else
		{
			$author = new \IPS\Member;
		}
		
		$contentClass = $node::$contentItemClass;
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_content_bulk', FALSE, array( 'sprintf' => array( $author->name, \IPS\Member::loggedIn()->language()->get( $contentClass::$title . '_pl_lc' ), $node->_title ) ) ),
			'url'		=> $node->url(),
			'author'	=> $author
		);
	}
	
	/**
	 * Parse notification: unapproved_content_bulk
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
	public function parse_unapproved_content_bulk( $notification )
	{
		$node = $notification->item;
		
		if ( !$node )
		{
			throw new \OutOfRangeException;
		}
		
		if ( $notification->extra )
		{
			/* \IPS\Notification->extra will always be an array, but for bulk content notifications we are only storing a single member ID,
				so we need to grab just the one array entry (the member ID we stored) */
			$memberId = $notification->extra;

			if( is_array( $memberId ) )
			{
				$memberId = array_pop( $memberId );
			}

			$author = \IPS\Member::load( $memberId );
		}
		else
		{
			$author = new \IPS\Member;
		}
		
		$contentClass = $node::$contentItemClass;
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__unapproved_content_bulk', FALSE, array( 'sprintf' => array( $author->name, \IPS\Member::loggedIn()->language()->get( $contentClass::$title . '_pl_lc' ), $node->_title ) ) ),
			'url'		=> $node->url(),
			'author'	=> $author
		);
	}
	
	/**
	 * Parse notification: new_content
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
	public function parse_new_comment( $notification )
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new \OutOfRangeException;
		}
		
		$idColumn = $item::$databaseColumnId;
		$commentClass = $item::$commentClass;
		
		$between = time();
		try
		{
			/* Is there a newer notification for this item? */
			$between = \IPS\Db::i()->select( 'sent_time', 'core_notifications', array( 'member=? AND item_id=? AND item_class=? AND sent_time>? AND notification_key=?', \IPS\Member::loggedIn()->member_id, $item->$idColumn, get_class( $item ), $notification->sent_time->getTimestamp(), $notification->notification_key ) )->first();
		}
		catch( \UnderflowException $e ) {}
		
		$commenters = \IPS\Db::i()->select( 'DISTINCT ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'], $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] . '>=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] . '<? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . ' !=?', $item->$idColumn, $notification->sent_time->getTimestamp(), $between, \IPS\Member::loggedIn()->member_id ) );
						
		$names = array();
		foreach ( $commenters as $member )
		{
			if ( count( $names ) > 2 )
			{
				$names[] = \IPS\Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( count( $commenters ) - 3 ) ) );
				break;
			}
			$names[] = \IPS\Member::load( $member )->name;
		}
		
		$comment = $commentClass::loadAndCheckPerms( $notification->item_sub_id );
		
		/* Unread? */
		$unread = false;
		if ( $item->timeLastRead() instanceof \IPS\DateTime )
		{
			$unread = (bool) ( $item->timeLastRead()->getTimestamp() > $notification->updated_time->getTimestamp() );
		}
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_comment', FALSE, array( 'pluralize' => array( count( $commenters ) ), 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $names ), $item->mapped('title') ) ) ),
			'url'		=> $comment->url('find'),
			'content'	=> $comment->content(),
			'author'	=> $comment->author(),
			'unread'	=> $unread,
		);
	}
	
	/**
	 * Parse notification: new_review
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
	public function parse_new_review( $notification )
	{
		$item = $notification->item;

		if ( !$item )
		{
			throw new \OutOfRangeException;
		}
		
		$idColumn = $item::$databaseColumnId;
		$between = time();
		try
		{
			/* Is there a newer notification for this item? */
			$between = \IPS\Db::i()->select( 'sent_time', 'core_notifications', array( 'member=? AND item_id=? AND item_class=? AND sent_time>?', \IPS\Member::loggedIn()->member_id, $item->$idColumn, get_class( $item ), $notification->sent_time->getTimestamp() ) )->first();
		}
		catch( \UnderflowException $e ) {}
		
		$commentClass = $item::$reviewClass;
		$commenters = \IPS\Db::i()->select( 'DISTINCT ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'], $commentClass::$databaseTable, array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] . '>=? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'] . '<? AND ' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . ' !=?', $item->$idColumn, $notification->sent_time->getTimestamp(), $between, \IPS\Member::loggedIn()->member_id ) );
						
		$names = array();
		foreach ( $commenters as $member )
		{
			if ( count( $names ) > 2 )
			{
				$names[] = \IPS\Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( count( $commenters ) - 3 ) ) );
				break;
			}
			$names[] = \IPS\Member::load( $member )->name;
		}
		
		$review = $commentClass::loadAndCheckPerms( $notification->item_sub_id );
		
		/* Unread? */
		$unread = false;
		if ( $item->timeLastRead() instanceof \IPS\DateTime )
		{
			$unread = (bool) ( $item->timeLastRead()->getTimestamp() > $notification->updated_time->getTimestamp() );
		}
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_review', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $names ), $item->mapped('title') ) ) ),
			'url'		=> $review->url('find'),
			'content'	=> $review->content(),
			'author'	=> $review->author(),
			'unread'	=> $unread,
		);
	}
	
	/**
	 * Parse notification: warning_mods
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
	public function parse_warning_mods( $notification )
	{
		if ( !$notification->item )
		{
			throw new \OutOfRangeException;
		}
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__warning_mods', FALSE, array( 'sprintf' => array( \IPS\Member::load( $notification->item->member )->name, \IPS\Member::load( $notification->item->moderator )->name ) ) ),
			'url'		=> $notification->item->url(),
			'content'	=> $notification->item->note_mods,
			'author'	=> \IPS\Member::load( $notification->item->moderator ),
		);
	}
	
	/**
	 * Parse notification: report_center
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
	public function parse_report_center( $notification )
	{
		if ( !$notification->item_sub )
		{
			throw new \OutOfRangeException;
		}

		$reported = $notification->item_sub;
		$item = ( $reported instanceof \IPS\Content\Item ) ? $reported : $reported->item();

		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__report_center', FALSE, array( 'sprintf' => array( $notification->item->author()->name, mb_strtolower( $reported->indefiniteArticle() ), $item->mapped('title' ) ) ) ),
			'url'		=> $notification->item->url(),
			'content'	=> NULL,
			'author'	=> $notification->item->author(),
		);
	}
	
	/**
	 * Parse notification: unapproved_content
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
	public function parse_unapproved_content( $notification )
	{
		if ( !$notification->item )
		{
			throw new \OutOfRangeException;
		}
		
		$item = $notification->item;
		
		if ( $item instanceof \IPS\Content\Comment OR $item instanceof \IPS\Content\Review )
		{
			$title = $item->item()->mapped('title');
		}
		else
		{
			$title = $item->mapped('title');
		}
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__unapproved_content', FALSE, array( 'sprintf' => array( $item->author()->name, mb_strtolower( $item->indefiniteArticle() ), $title ) ) ),
			'url'		=> $item->url(),
			'content'	=> $item->content(),
			'author'	=> $item->author(),
		);
	}
	
	/**
	 * Parse notification: mention
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
	public function parse_mention( $notification )
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new \OutOfRangeException;
		}
		
		$comment = $notification->item_sub ?: $item;
		if ( !$comment )
		{
			throw new \OutOfRangeException;
		}
		
		if ( $notification->extra )
		{
			$memberIds = array_unique( $notification->extra );
			$andXOthers = NULL;
			if ( count( $memberIds ) > 3 )
			{
				$andXOthers = count( $memberIds ) - 2;
				array_splice( $memberIds, 2 );
			}
			
			$quoters = iterator_to_array( \IPS\Db::i()->select( 'name', 'core_members', \IPS\Db::i()->in( 'member_id', $memberIds ) ) );
			if ( $andXOthers )
			{
				$quoters[] = \IPS\Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( $andXOthers ) ) );
			}
		}
		else
		{
			$quoters = array( $comment->author()->name );
		}
		$quoters = \IPS\Member::loggedIn()->language()->formatList( $quoters );
		
		return array(
			'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_mention', FALSE, array( 'sprintf' => array( $quoters, mb_strtolower( $item->indefiniteArticle() ), $item->mapped('title') ) ) ),
			'url'		=> $comment->url(),
			'content'	=> $comment->truncated(),
			'author'	=> $comment->author(),
			'unread'	=> (bool) ( $item->unread() ),
		);
	}
	
	/**
	 * Parse notification: quote
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
	public function parse_quote( $notification )
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new \OutOfRangeException;
		}
		
		$comment = $notification->item_sub ?: $item;
		if ( !$comment )
		{
			throw new \OutOfRangeException;
		}
		
		if ( $notification->extra )
		{
			$memberIds = array_unique( $notification->extra );
			$andXOthers = NULL;
			if ( count( $memberIds ) > 3 )
			{
				$andXOthers = count( $memberIds ) - 2;
				array_splice( $memberIds, 2 );
			}
			
			$quoters = iterator_to_array( \IPS\Db::i()->select( 'name', 'core_members', \IPS\Db::i()->in( 'member_id', $memberIds ) ) );
			if ( $andXOthers )
			{
				$quoters[] = \IPS\Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( $andXOthers ) ) );
			}
		}
		else
		{
			$quoters = array( $comment->author()->name );
		}
		$quoters = \IPS\Member::loggedIn()->language()->formatList( $quoters );
		
		return array(
				'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'notification__new_quote', FALSE, array( 'sprintf' => array( $quoters, mb_strtolower( $item->indefiniteArticle() ), $item->mapped('title') ) ) ),
				'url'		=> $comment->url('find'),
				'content'	=> $comment->truncated(),
				'author'	=> $comment->author(),
				'unread'	=> (bool) ( $item->unread() ),
		);
	}
	
	/**
	 * Parse notification: new_likes
	 *
	 * @param	\IPS\Notification\Inline	$notification	The notification
	 * @return	array|NULL
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
	public function parse_new_likes( $notification )
	{
		
		$comment = $notification->item;
		
		if ( !$comment )
		{
			throw new \OutOfRangeException;
		}

		$item = ( $comment instanceof \IPS\Content\Item ) ? $comment : $comment->item();
		$idColumn = $comment::$databaseColumnId;
		
		$between = time();
		try
		{
			/* Is there a newer notification for this item? */
			$between = \IPS\Db::i()->select( 'sent_time', 'core_notifications', array( 'member=? AND item_id=? AND item_class=? AND sent_time>? AND notification_key=?', \IPS\Member::loggedIn()->member_id, $comment->$idColumn, get_class( $comment ), $notification->sent_time->getTimestamp(), $notification->notification_key ) )->first();
		}
		catch( \UnderflowException $e ) {}
		
		$likers = \IPS\Db::i()->select( 'DISTINCT member_id, rep_date', 'core_reputation_index', array( 'app=? AND type=? AND rep_date>=? AND rep_date<? AND type_id=?', $comment::$application, $comment::$reputationType, $notification->sent_time->getTimestamp(), $between, $comment->$idColumn ), 'rep_date desc' );

		$names = array();
		foreach( $likers AS $member )
		{
			if ( count( $names ) > 2 )
			{
				$names[] = \IPS\Member::loggedIn()->language()->addToStack( 'x_others', FALSE, array( 'pluralize' => array( count( $likers ) - 3 ) ) );
				break;
			}
			$names[] = \IPS\Member::load( $member['member_id'] )->name;
		}
		
		try
		{
			$first = $likers->first();
			return array(
					'title'		=> \IPS\Member::loggedIn()->language()->addToStack( \IPS\Settings::i()->reputation_point_types === 'like' ? 'notification__new_likes' : 'notification__new_rep', FALSE, array( 'sprintf' => array( ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ) ? \IPS\Member::loggedIn()->language()->formatList( $names ) : \IPS\Member::loggedIn()->language()->pluralize( \IPS\Member::loggedIn()->language()->get( 'notifications_user_count' ), array( count( $likers ) ) ), mb_strtolower( $comment->indefiniteArticle() ) ) ) ) . ' ' . $item->mapped('title'),
					'url'		=> ( $comment instanceof \IPS\Content\Comment ) ? $comment->url('find') : $comment->url(),
					'content'	=> $comment->truncated(),
					'author'	=> ( \IPS\Member::loggedIn()->group['gbw_view_reps'] ) ? \IPS\Member::load( $first['member_id'] ) : new \IPS\Member
			);
		}
		catch( \UnderflowException $ex )
		{
			throw new \OutOfRangeException;
		}
	}
	
}