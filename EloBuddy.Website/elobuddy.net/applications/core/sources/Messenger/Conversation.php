<?php
/**
 * @brief		Personal Conversation Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Messenger;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Personal Conversation Model
 */
class _Conversation extends \IPS\Content\Item
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_message_topics';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'mt_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	[Content\Item]	Include the ability to search this content item in global site searches
	 */
	public static $includeInSiteSearch = FALSE;
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
		\IPS\Db::i()->delete( 'core_message_topic_user_map', array( 'map_topic_id=?', $this->id ) );
	}
	
	/* !\IPS\Content\Item */

	/**
	 * @brief	Title
	 */
	public static $title = 'personal_conversation';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'title'				=> 'title',
		'date'				=> array( 'date', 'start_time', 'last_post_time' ),
		'author'			=> 'starter_id',
		'num_comments'		=> 'replies',
		'last_comment'		=> 'last_post_time',
		'first_comment_id'	=> 'first_msg_id',
	);
	
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'messaging';
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static $formLangPrefix = 'messenger_';
	
	/**
	 * @brief	Comment Class
	 */
	public static $commentClass = 'IPS\core\Messenger\Message';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = TRUE;
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @return	void
	 */
	public static function incrementPostCount( \IPS\Node\Model $container = NULL )
	{
		return FALSE;
	}
		
	/**
	 * Can a given member create this type of content?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model			$container	Container (e.g. forum) ID, if appropriate
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		/* Can we access the module? */
		if ( !parent::canCreate( $member, $container, $showError ) )
		{
			return FALSE;
		}
		
		/* We have to be logged in */
		if ( !$member->member_id )
		{
			if ( $showError )
			{
				\IPS\Output::i()->error( 'no_module_permission_guest', '1C149/1', 403, '' );
			}
			
			return FALSE;
		}
		
		/* Have we exceeded our limit for the day/minute? */
		if ( $member->group['g_pm_perday'] !== -1 )
		{
			$messagesSentToday = \IPS\Db::i()->select( 'COUNT(*) AS count, MAX(mt_date) AS max', 'core_message_topics', array( 'mt_starter_id=? AND mt_date>?', $member->member_id, \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) )->getTimeStamp() ) )->first();
			if ( $messagesSentToday['count'] >= $member->group['g_pm_perday'] )
			{
				$next = \IPS\DateTime::ts( $messagesSentToday['max'] )->add( new \DateInterval( 'P1D' ) );
				
				if ( $showError )
				{
					\IPS\Output::i()->error( $member->language()->addToStack( 'err_too_many_pms_day', FALSE, array( 'pluralize' => array( $member->group['g_pm_perday'] ) ) ), '1C149/2', 429, '', array( 'Retry-After' => $next->format('r') ) );
				}
				
				return FALSE;
			}
		}
		if ( $member->group['g_pm_flood_mins'] !== -1 )
		{
			$messagesSentThisMinute = \IPS\Db::i()->select( 'COUNT(*)', 'core_message_topics', array( 'mt_starter_id=? AND mt_date>?', $member->member_id, \IPS\DateTime::create()->sub( new \DateInterval( 'PT1M' ) )->getTimeStamp() ) )->first();
			if ( $messagesSentThisMinute >= $member->group['g_pm_flood_mins'] )
			{
				if ( $showError )
				{
					\IPS\Output::i()->error( $member->language()->addToStack( 'err_too_many_pms_minute', FALSE, array( 'pluralize' => array( $member->group['g_pm_flood_mins'] ) ) ), '1C149/3', 429, '', array( 'Retry-After' => 3600 ) );
				}
				
				return FALSE;
			}
		}
		
		/* Is our inbox full? */
		if ( $member->group['g_max_messages'] !== -1 )
		{
			$messagesInInbox = \IPS\Db::i()->select( 'COUNT(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', $member->member_id ) )->first();
			if ( $messagesInInbox > $member->group['g_max_messages'] )
			{
				if ( $showError )
				{
					\IPS\Output::i()->error( 'err_inbox_full', '1C149/4', 403, '' );
				}
				
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	int						$container	Container (e.g. forum) ID, if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model  $container=NULL )
	{
		$return = array();
		foreach ( parent::formElements( $item, $container ) as $k => $v )
		{
			if ( $k == 'title' )
			{
 				if( !$item )
 				{
 					$member	= NULL;

 					if( \IPS\Request::i()->to )
 					{
 						$member = \IPS\Member::load( \IPS\Request::i()->to );

 						if( !$member->member_id )
 						{
 							$member = NULL;
 						}
 					}
 					
					$return['to'] = new \IPS\Helpers\Form\Member( 'messenger_to', $member, TRUE, array( 'multiple' => ( \IPS\Member::loggedIn()->group['g_max_mass_pm'] == -1 ) ? NULL : \IPS\Member::loggedIn()->group['g_max_mass_pm'] ), function ( $members )
					{
						$messengerModule = \IPS\Application\Module::get( 'core', 'messaging' );
						
						if ( $members instanceof \IPS\Member )
						{
							$members = array( $members );
						}
						
						if ( !is_array( $members ) )
						{
							throw new \InvalidArgumentException( 'messenger_invalid_recipient' );
						}
						
						foreach ( $members as $m )
						{
							if ( $m->members_disable_pm or !$m->canAccessModule( $messengerModule ) or ( ( $m->group['g_max_messages'] > 0 AND $m->msg_count_total >= $m->group['g_max_messages'] ) and !\IPS\Member::loggedIn()->group['gbw_pm_override_inbox_full'] ) or $m->isIgnoring( \IPS\Member::loggedIn(), 'messages' ) )
							{
								throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('meesnger_err_bad_recipient', FALSE, array( 'sprintf' => array( $m->name ) ) ) );
							}
						}
					} );
				}
			}
			$return[ $k ] = $v;
		}
		return $return;
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( $values )
	{
		$this->to_count = count( $values['messenger_to'] );

		parent::processBeforeCreate( $values );
	}
				
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( $comment, $values )
	{
		/* Set the first message ID */
		$this->first_msg_id = $comment->id;
		$this->save();
		
		if ( is_array( $values['messenger_to'] ) )
		{
			$members = array_map( function( $member )
			{
				return $member->member_id;
			}, $values['messenger_to'] );
		}
		else
		{
			$members[] = $values['messenger_to']->member_id;
		}

		$members[]	= $this->starter_id;

		/* Authorize everyone */
		$this->authorize( $members );
		
		/* Run parent */
		parent::processAfterCreate( $comment, $values );
		
		/* Send the notification for the first message */
		$comment->sendNotifications();
	}
	
	/**
	 * Does a member have permission to access?
	 *
	 * @param	\IPS\Member	$member	The member to check for
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();

		/* Is the user part of the conversation? */
		foreach ( $this->maps() as $map )
		{
			if ( $map['map_user_id'] === $member->member_id and $map['map_user_active'] )
			{
				return TRUE;
			}
		}
		
		/* Have we granted them temporary permission from the report center or a warning log? */
		if ( $member->modPermission('can_view_reports') )
		{
			/* If we are coming directly from a report, and the Report ID is different from what is stored in session, then we need to unset it so it can be reset */
			if ( isset( $_SESSION['report'] ) AND isset( \IPS\Request::i()->_report ) AND \IPS\Request::i()->_report != $_SESSION['report'] )
			{
				unset( $_SESSION['report'] );
			}
			
			$report = isset( $_SESSION['report'] ) ? $_SESSION['report'] : ( isset( \IPS\Request::i()->_report ) ? \IPS\Request::i()->_report : NULL );
			if ( $report )
			{
				try
				{
					$report = \IPS\core\Reports\Report::load( $report );
					if ( $report->class == 'IPS\core\Messenger\Message' and in_array( $report->content_id, iterator_to_array( \IPS\Db::i()->select( 'msg_id', 'core_message_posts', array( 'msg_topic_id=?', $this->id ) ) ) ) )
					{
						$_SESSION['report'] = $report->id;
						return TRUE;
					}
				}
				catch ( \OutOfRangeException $e ){ }
			}
		}
		if ( $member->modPermission('mod_see_warn') )
		{
			/* If we are coming directly from a warning, and the Warning ID is different from what is stored in session, then we need to unset it so it can be reset */
			if ( isset( $_SESSION['warning'] ) AND isset( \IPS\Request::i()->_warning ) AND \IPS\Request::i()->_warning != $_SESSION['warning'] )
			{
				unset( $_SESSION['warning'] );
			}
			
			$warning = isset( $_SESSION['warning'] ) ? $_SESSION['warning'] : ( isset( \IPS\Request::i()->_warning ) ? \IPS\Request::i()->_warning : NULL );
			if ( $warning )
			{
				try
				{
					$warning = \IPS\core\Warnings\Warning::load( $warning );
					
					if ( $warning->content_app == 'core' AND $warning->content_module == 'messaging-comment' AND $warning->content_id1 == $this->id )
					{
						$_SESSION['warning'] = $warning->id;
						return TRUE;
					}
				}
				catch( \OutOfRangeException $e ) { }
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( $member=NULL )
	{
		return FALSE; // You don't delete a conversation. It gets deleted automatically when everyone has left.
	}
	
	/**
	 * Actions to show in comment multi-mod
	 *
	 * @param	\IPS\Member	$member	Member (NULL for currently logged in member)
	 * @return	array
	 */
	public function commentMultimodActions( \IPS\Member $member = NULL )
	{
		return array();
	}

	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger&id={$this->id}", 'front', 'messenger_convo' );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/* !\IPS\core\Messenger\Conversation */
	
	/**
	 * Get the number of active participants
	 *
	 * @return	int
	 */
	public function get_activeParticipants()
	{
		return count( array_filter( $this->maps, function( $map )
		{
			return $map['map_user_active'];
		} ) );
	}
	
	/**
	 * Get the map for the current member
	 *
	 * @return	mixed
	 */
	public function get_map()
	{
		$maps = $this->maps();
		
		/* From a report? */
		if ( ( isset( $_SESSION['report'] ) ? $_SESSION['report'] : ( isset( \IPS\Request::i()->_report ) ? \IPS\Request::i()->_report : NULL ) ) AND \IPS\Member::loggedIn()->modPermission( 'can_view_reports' ) )
		{
			return array();
		}
		
		if ( isset( $maps[ \IPS\Member::loggedIn()->member_id ] ) )
		{
			return $maps[ \IPS\Member::loggedIn()->member_id ];
		}
		
		throw new \OutOfRangeException;
	}
	
	/**
	 * Get the most recent unread conversation and dismiss the popup
	 *
	 * @return	\IPS\Conversation|NULL
	 */
	public static function latestUnreadConversation()
	{
		$return = NULL;
		$latestConversationMap = \IPS\Db::i()->select( 'map_topic_id', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0', \IPS\Member::loggedIn()->member_id ), 'map_last_topic_reply DESC' );

		try
		{
			$return = static::loadAndCheckPerms( $latestConversationMap->first() );
		}
		catch ( \OutOfRangeException $e ) { }
		catch ( \UnderflowException $e ) { }
		
		\IPS\Member::loggedIn()->msg_show_notification = FALSE;
		\IPS\Member::loggedIn()->save();

		return $return;
	}
	
	/**
	 * Recount the member's message counts
	 *
	 * @param	\IPS\Member	$member	Member
	 * @return	void
	 */
	public static function rebuildMessageCounts( \IPS\Member $member )
	{
		$total = \IPS\Db::i()->select( 'count(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', $member->member_id ) )->first();
		$member->msg_count_total = $total;
		
		$new = \IPS\Db::i()->select( 'count(*)', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0 AND map_last_topic_reply>?', $member->member_id, $member->msg_count_reset ) )->first();
		$member->msg_count_new = $new;
		
		$member->save();
	}
	
	/**
	 * @brief	Maps cache
	 */
	protected $maps = NULL;
	
	/**
	 * Get maps
	 *
	 * @param 	boolean		$refresh 		Force maps to be refreshed?
	 * @return	array
	 */
	public function maps( $refresh = FALSE )
	{
		if ( $this->maps === NULL || $refresh === TRUE )
		{
			$this->maps = iterator_to_array( \IPS\Db::i()->select( '*', 'core_message_topic_user_map', array( 'map_topic_id=?', $this->id ) )->setKeyField( 'map_user_id' ) );
		}
		return $this->maps;
	}
	
	/**
	 * Grant a member access
	 *
	 * @param	\IPS\Member|array	$member		The member(s) to grant access
	 * @return	bool
	 */
	public function authorize( $members )
	{
		$members = is_array( $members ) ? $members : array( $members );
		
		/* Go through each member */
		foreach ( $members as $member )
		{
			if ( is_int( $member ) )
			{
				$member = \IPS\Member::load( $member );
			}
						
			$done = FALSE;
			
			/* If they already have a map, update it */
			foreach ( $this->maps() as $map )
			{
				if ( $map['map_user_id'] == $member->member_id )
				{
					$this->maps[ $member->member_id ]['map_user_active'] = TRUE;
					$this->maps[ $member->member_id ]['map_user_banned'] = FALSE;
					\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => 1, 'map_user_banned' => 0 ), array( 'map_user_id=? AND map_topic_id=?', $member->member_id, $this->id ) );
					$done = TRUE;
					break;
				}
			}

			/* If not, create one */
			if ( !$done )
			{
				/* Create map */
				$membersLastComment = $this->comments( 1, 0, 'date', 'desc', $member );
				$this->maps[ $member->member_id ] = array(
					'map_user_id'				=> $member->member_id,
					'map_topic_id'				=> $this->id,
					'map_folder_id'				=> 'myconvo',
					'map_read_time'				=> ( $member->member_id == $this->starter_id ) ? time() : 0,
					'map_user_active'			=> TRUE,
					'map_user_banned'			=> FALSE,
					'map_has_unread'			=> ( $member->member_id == $this->starter_id ) ? FALSE : TRUE,
					'map_is_system'				=> FALSE,
					'map_is_starter'			=> ( $member->member_id == $this->starter_id ),
					'map_left_time'				=> 0,
					'map_ignore_notification'	=> FALSE,
					'map_last_topic_reply'		=> $membersLastComment ? $membersLastComment->date : time(),
				);
				\IPS\Db::i()->insert( 'core_message_topic_user_map', $this->maps[ $member->member_id ] );
			}

			if ( $member->members_bitoptions['show_pm_popup'] and $this->author()->member_id != $member->member_id )
			{
				$member->msg_show_notification = TRUE;
				$member->save();
			}
			
			/* Note: emails for added participants are sent from controller, as this central method is called when conversation is first created also */

			/* Rebuild the user's counts */
			static::rebuildMessageCounts( $member );
		}
		
		/* Rebuild the participants of this conversation */
		$this->rebuildParticipants();
		
		return $this->maps;
	}
	
	/**
	 * Remove a member access
	 *
	 * @param	\IPS\Member|array	$members	The member(s) to remove access
	 * @param	bool				$banned		User is being blocked by the conversation starter (as opposed to leaving voluntarily)?
	 * @return	bool
	 */
	public function deauthorize( $members, $banned=FALSE )
	{
		$members = is_array( $members ) ? $members : array( $members );
		foreach ( $members as $member )
		{
			\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => 0, 'map_user_banned' => $banned ), array( 'map_user_id=? AND map_topic_id=?', $member->member_id, $this->id ) );
			\IPS\Db::i()->delete( 'core_notifications', array( 'notification_key=? AND item_id = ? AND member=?', 'new_private_message', $this->id, $member->member_id ) );
			static::rebuildMessageCounts( $member );
		}
		$this->rebuildParticipants();
	}
	
	/**
	 * Rebuild participants
	 *
	 * @return	void
	 */
	public function rebuildParticipants()
	{
		$activeParticipants = 0;
		foreach ( $this->maps( TRUE ) as $map )
		{
			if ( $map['map_user_active'] )
			{
				$activeParticipants++;
			}
		}
		
		if ( $activeParticipants )
		{
			$this->to_count = $activeParticipants;
			$this->save();
		}
		else
		{
			$this->delete();
		}
	}
	
	/**
	 * @brief	Particpant blurb
	 */
	public $participantBlurb = NULL;
	
	/**
	 * Get participant blurb
	 *
	 * @return	string
	 */
	public function participantBlurb()
	{
		if( $this->participantBlurb !== NULL )
		{
			return $this->participantBlurb;
		}

		$people = array();
		foreach( \IPS\Db::i()->select( 'member_id, name', 'core_members', array( \IPS\Db::i()->in( 'member_id', array_keys( $this->maps() ) ) ) ) as $member )
		{
			if ( $member['member_id'] === \IPS\Member::loggedIn()->member_id )
			{
				$member['name'] = ( $member['member_id'] == $this->starter_id ) ? \IPS\Member::loggedIn()->language()->addToStack('participant_you_upper') : \IPS\Member::loggedIn()->language()->addToStack('participant_you_lower');
			}
			$people[ $member['member_id'] ] = $member['name'];
		}
		
		/* Move the starter to the front of the array */
		$starter = $people[ $this->starter_id ];
		unset( $people[ $this->starter_id ] );
		array_unshift( $people, $starter );
		unset( $starter );
		
		if ( count( $people ) == 1 )
		{
			$id   = key( $people );
			$name = array_pop( $people );
			$this->participantBlurb = \IPS\Member::loggedIn()->member_id === $id ? \IPS\Member::loggedIn()->language()->addToStack( 'participant_you_upper' ) : $name;
		}
		elseif ( count( $people ) == 2 )
		{
			$this->participantBlurb = \IPS\Member::loggedIn()->language()->addToStack( 'participant_two', FALSE, array( 'sprintf' => $people ) );
		}
		else
		{
			$count = 0;
			$others = array();
			$sprintf = array();
			foreach( $people as $id => $name )
			{
				if ( $count > 1 )
				{
					$others[] = $name;
				}
				else
				{
					$sprintf[] = $name;
				}
				
				$count++;
			}

			$sprintf[] = \IPS\Member::loggedIn()->language()->formatList( $others );
			$sprintf[] = count( $others );
			
			$this->participantBlurb = \IPS\Member::loggedIn()->language()->addToStack( 'participant_three_plus', FALSE, array( 'pluralize' => array( count( $others ) ), 'sprintf' => $sprintf ) );
		}

		return $this->participantBlurb;
	}

}