<?php
/**
 * @brief		Notification Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Class
 */
class _Notification
{
	/**
	 * @brief	Default Configuration
	 */
	protected static $defaultConfiguration = NULL;
	
	/**
	 * Get default configuration
	 *
	 * @return	array
	 */
	public static function defaultConfiguration()
	{
		if ( static::$defaultConfiguration === NULL )
		{
			static::$defaultConfiguration = iterator_to_array( \IPS\Db::i()->select( '*', 'core_notification_defaults' )->setKeyField('notification_key') );
			
			foreach( \IPS\Application::allExtensions( 'core', 'Notifications' ) as $group => $class )
			{
				$configuration = $class->getConfiguration( NULL );
				if ( !empty( $configuration ) )
				{
					foreach ( $configuration as $key => $data )
					{
						if ( !isset( static::$defaultConfiguration[ $key ] ) )
						{
							/* Row isn't in DB, add it */
							\IPS\Db::i()->insert( 'core_notification_defaults', array(
								'notification_key' => $key,
								'default'		   => implode( ',', $data['default'] ),
								'disabled'		   => implode( ',', $data['disabled'] )
							) );
							
							static::$defaultConfiguration[ $key ] = array_merge( $data, array( 'editable' => TRUE ) );
						}
						else
						{
							static::$defaultConfiguration[ $key ]['default'] = array_filter( explode( ',', static::$defaultConfiguration[ $key ]['default'] ) );
							static::$defaultConfiguration[ $key ]['disabled'] = array_filter( explode( ',', static::$defaultConfiguration[ $key ]['disabled'] ) );
						}
					}
				}
			}
		}
		return static::$defaultConfiguration;
	}
	
	/**
	 * Build Matrix
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	\IPS\Helpers\Form\Matrix
	 */
	public static function buildMatrix( \IPS\Member $member )
	{
		$matrix = new \IPS\Helpers\Form\Matrix();
		$matrix->manageable = FALSE;
		$matrix->langPrefix = FALSE;
		$matrix->columns = array(
			'label'		=> function( $key, $value, $data )
			{
				if ( mb_substr( $key, 0, -7 ) === 'new_likes' )
				{
					if ( \IPS\Settings::i()->reputation_point_types === 'like' )
					{
						$return = 'notifications__new_likes_like';
					}
					else
					{
						$return = 'notifications__new_likes_rep';
					}
				}
				else
				{
					$return = 'notifications__' . mb_substr( $key, 0, -7 );
				}
				
				return \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->notificationLabel( $return, $data );
			},
			'member_notifications_inline'	=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\YesNo( $key, in_array( 'inline', ( is_array( $data['selected'] ) ) ? $data['selected'] : array() ), FALSE, array( 'disabled' => ( !$data['editable'] OR in_array( 'inline', $data['disabled'] ) ), 'tooltip' => ( !$data['editable'] OR in_array( 'inline', $data['disabled'] ) ) ? \IPS\Member::loggedIn()->language()->addToStack('admin_notification_disabled') : NULL ) );
			},
			'member_notifications_email'	=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\YesNo( $key, in_array( 'email', ( is_array( $data['selected'] ) ) ? $data['selected'] : array() ), FALSE, array( 'disabled' => ( !$data['editable'] OR in_array( 'email', $data['disabled'] ) ), 'tooltip' => ( !$data['editable'] OR in_array( 'email', $data['disabled'] ) ) ? \IPS\Member::loggedIn()->language()->addToStack('admin_notification_disabled') : NULL ) );
			},
		);
		
		/* Add rows */
		$defaultConfiguration = static::defaultConfiguration();
		$memberConfiguration = $member->notificationsConfiguration();
		foreach( \IPS\Application::allExtensions( 'core', 'Notifications' ) as $group => $class )
		{
			$configuration = $class->getConfiguration( $member );
			if ( !empty( $configuration ) )
			{
				$lang = "notifications__{$group}";
				$header = \IPS\Member::loggedIn()->language()->addToStack( $lang );
				$matrix->rows[] = $header;
				
				foreach ( $configuration as $key => $data )
				{
					$matrix->rows[ $key ] = array( 'selected' => ( !empty( $memberConfiguration[ $key ] ) ) ? $memberConfiguration[ $key ] : NULL, 'disabled' => $defaultConfiguration[ $key ]['disabled'], 'icon' => isset( $data['icon'] ) ? $data['icon'] : NULL, 'editable' => $defaultConfiguration[ $key ]['editable'] );
				}
			}
		}
		
		return $matrix;
	}
	
	/**
	 * Save Matrix
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	array		$values	Values from matrix
	 * @return	void
	 */
	public static function saveMatrix( \IPS\Member $member, array $values )
	{
		/* Remove the current preferences */
		\IPS\Db::i()->delete( 'core_notification_preferences', array( 'member_id=?', $member->member_id ) );
		
		/* Get the default configuration so we know what is forced enabled by the admin */
		$defaults = static::defaultConfiguration();

		/* Now loop over the notifications and set preferences */
		$insert = array();
		foreach ( $values['notifications'] as $k => $v )
		{
			$pref = array();
			if ( $v['member_notifications_inline'] )
			{
				$pref[] = 'inline';
			}
			else if( !$defaults[ $k ]['editable'] AND in_array( 'inline', $defaults[ $k ]['default'] ) )
			{
				$pref[] = 'inline';
			}
			if ( $v['member_notifications_email'] )
			{
				$pref[] = 'email';
			}
			else if( !$defaults[ $k ]['editable'] AND in_array( 'email', $defaults[ $k ]['default'] ) )
			{
				$pref[] = 'email';
			}
			
			$insert[] = array(
				'member_id'			=> $member->member_id,
				'notification_key'	=> $k,
				'preference'		=> implode( ',', $pref )
			);
		}

		\IPS\Db::i()->insert( 'core_notification_preferences', $insert );
	}
	
	/**
	 * @brief	Application
	 */
	protected $app;
	
	/**
	 * @brief	Notification key
	 */
	protected $key;
	
	/**
	 * @brief	Item
	 */
	protected $item;
		
	/**
	 * @brief	An SplObjectStorage object which contains \IPS\Member objects and replacements to use for that member in the notification content.
	 * @code
	 	$notification->recipients->attach( $member, array( 'foo' => 'bar' ) );
	 	$notification->recipients->attach( $member2, array( 'foo' => 'baz' ) );
	 * @endcode
	 */
	public $recipients;
	
	/**
	 * @brief	Data for notification emails
	 */
	protected $emailParams = array();
	
	/**
	 * @brief	Extra data to save with inline notifications
	 */
	protected $inlineExtra = array();
	
	/**
	 * @brief	Unsubscribe Type
	 */
	public $unsubscribeType = 'notification';
		
	/**
	 * Constructor
	 *
	 * @param	\IPS\Application	$app			The application the notification belongs to
	 * @param	string				$key			Notification key
	 * @param	object|NULL			$item			The thing the notification is about
	 * @param	array				$emailParams	Data for notification emails
	 * @param	array				$inlineExtra	Extra data to save with inline notifications. Use sparingly: only in cases where it is not possible to obtain the same data later. Will be merged for duplicate notifications.
	 * @return	void
	 */
	public function __construct( \IPS\Application $app, $key, $item=NULL, $emailParams=array(), $inlineExtra=array() )
	{
		$this->app = $app;
		$this->key = $key;
		$this->item = $item;
		$this->recipients = new \SplObjectStorage;
		$this->emailParams = $emailParams;
		$this->inlineExtra = $inlineExtra;
	}
	
	/**
	 * Send Notification
	 *
	 * @param	array	$sentTo		Members who have already received a notification and how (same format as the return value) to prevent duplicates
	 * @return	array	The members that were notified and how they were notified
	 */
	public function send( $sentTo = array() )
	{
		/* Make a placeholder for emails - we'll need to generate one per language */
		$emails = array();
		$emailRecipients = array();
		$thingsBeingFollowed = array();
						
		/* Loop recipients */
		foreach ( $this->recipients as $member )
		{			
			/* Let's not send notifications to banned members */
			if ( $member->isBanned() )
			{
				$this->recipients->detach( $member );
				continue;
			}
			
			/* If there's an item, check the user has permission to view it and is not ignoring */
			if ( $this->item )
			{
				/* Permission check */
				$item = $this->item;
				if ( $item instanceof \IPS\Content\Item )
				{
					$application = \IPS\Application::load( $item::$application );
					if ( !$application->canAccess( $member ) )
					{
						$this->recipients->detach( $member );
						continue;
					}

					/* Remove if member is ignoring the item author but only if this is a new content item.
					If a member is following content they should still receive reply notifications regardless of author */
					if ( $this->key == "new_content" and $member->isIgnoring( $item->author(), 'topics' ) )
					{
						$this->recipients->detach( $member );
						continue;
					}
				}
				
				/* Not ignoring the comment this is about */
				foreach( $this->emailParams AS $param )
				{
					if ( $param instanceof \IPS\Content\Comment OR $param instanceof \IPS\Content\Review )
					{
						if ( $member->isIgnoring( $param->author(), 'topics' ) )
						{
							$this->recipients->detach( $member );
							continue 2;
						}
					}
					
					if ( $param instanceof \IPS\Member )
					{
						if ( $member->isIgnoring( $param, 'topics' ) )
						{
							$this->recipients->detach( $member );
							continue 2;
						}
					}
				}
			}
			
			/* Work out how the user wants to receive this notification */
			$notificationPreferences = $member->notificationsConfiguration();
			$info = $this->recipients->getInfo();
			if ( $info['follow_app'] === 'core' and $info['follow_area'] === 'member' )
			{
				$keyToCheck = 'follower_content';
			}
			else
			{
				$keyToCheck = $this->key;
				if ( $this->key === 'new_content_bulk' )
				{
					$keyToCheck = 'new_content';
				}
				if ( $this->key === 'unapproved_content_bulk' )
				{
					$keyToCheck = 'unapproved_content';
				}
			}
			
			/* They want to receive an email (we don't send until the end once we've collated all the emails to send) */
			if ( isset( $notificationPreferences[ $keyToCheck ] ) AND in_array( 'email', $notificationPreferences[ $keyToCheck ] ) and ( !isset( $sentTo[ $member->member_id ] ) or !in_array( 'email', $sentTo[ $member->member_id ] ) ) )
			{
				$language = $member->language()->id;

				if ( !isset( $emails[ $language ] ) )
				{
					$email = \IPS\Email::buildFromTemplate( $this->app->directory, 'notification_' . $this->key, $this->emailParams, \IPS\Email::TYPE_LIST );
					
					if ( $info )
					{
						$email->setUnsubscribe( 'core', 'unsubscribeFollow', array( $this->key ) );
					}
					else
					{
						$email->setUnsubscribe( 'core', 'unsubscribeNotification', array( $this->key ) );
					}
					
					$emails[ $language ] = $email;
				}
				
				$unsubscribeBlurb = NULL;
				$unfollowLink = NULL;
				$okToEmail = TRUE;
				
				if ( $info )
				{
					if ( !isset( $thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ] ) )
					{
						if ( $info['follow_app'] === 'core' and $info['follow_area'] === 'member' )
						{
							$thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ] = \IPS\Member::load( $info['follow_rel_id'] );
						}
						else
						{
							$classname = 'IPS\\' . $info['follow_app'] . '\\' . ucfirst( $info['follow_area'] );
							$thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ] = $classname::load( $info['follow_rel_id'] );
						}
					}
				
					$thingBeingFollowed = $thingsBeingFollowed[ $info['follow_app'] ][ $info['follow_area'] ][ $info['follow_rel_id'] ];
					if ( $thingBeingFollowed instanceof \IPS\Member )
					{
						$unsubscribeBlurb = $member->language()->addToStack( 'unsubscribe_blurb_follow_member', FALSE, array( 'sprintf' => array( $thingBeingFollowed->name ) ) );
					}
					elseif ( $thingBeingFollowed instanceof \IPS\Node\Model )
					{
						$unsubscribeBlurb	= $member->language()->addToStack( 'unsubscribe_blurb_follow', FALSE, array( 'sprintf' => array( $member->language()->addToStack( $thingBeingFollowed::$nodeTitle . '_sg' ), $thingBeingFollowed->_title ) ) );
					}
					else
					{
						$unsubscribeBlurb	= $member->language()->addToStack( 'unsubscribe_blurb_follow', FALSE, array( 'sprintf' => array( $member->language()->addToStack( $thingBeingFollowed::$title ), $thingBeingFollowed->mapped('title') ) ) );
					}
					
					$unfollowLink = (string) \IPS\Http\Url::internal( "app=core&module=system&section=notifications&do=follow&follow_app={$info['follow_app']}&follow_area={$info['follow_area']}&follow_id={$info['follow_rel_id']}", 'front' );
	
					if ( $member->members_bitoptions['email_notifications_once'] and $member->last_activity < $info['follow_notify_sent'] )
					{
						$okToEmail = FALSE;
					}
				}
				
				if ( $okToEmail )
				{
					$emailRecipients[ $language ][ $member->email ] = array(
						'member_name'		=> $member->name,
						'unsubscribe_blurb'	=> $unsubscribeBlurb,
						'unfollow_link'		=> $unfollowLink
					);
				}
				
				$sentTo[ $member->member_id ][] = 'email';
			}

			/* They want to receive an inline notification... (ignore for report center which is treated special and the 'inline' notification
				preference actually instead controls whether the bubble should be shown on the report center icon at the top or not) */
			if ( $this->key != 'report_center' and isset( $notificationPreferences[ $keyToCheck ] ) and in_array( 'inline', $notificationPreferences[ $keyToCheck ] ) and ( !isset( $sentTo[ $member->member_id ] ) or !in_array( 'inline', $sentTo[ $member->member_id ] ) ) )
			{
				if ( $this->item )
				{
					try
					{
						$item = $this->item;
						$idColumn = $item::$databaseColumnId;
						$notification = \IPS\Notification\Inline::constructFromData( \IPS\Db::i()->select( '*', 'core_notifications', array( 'notification_key=? AND item_class=? AND item_id=? AND member=? AND read_time IS NULL', $this->key, get_class( $this->item ), $item->$idColumn, $member->member_id ) )->first() );
						
						$notification->member = $member;
						$notification->updated_time = time();
						$notification->extra = array_merge( $notification->extra, $this->inlineExtra );
						$notification->save();
						
						continue;
					}
					catch ( \UnderflowException $e ) { }
				}

				$notification = new \IPS\Notification\Inline;
				$notification->member = $member;
				$notification->notification_app = $this->app;
				$notification->notification_key = $this->key;
				if ( $this->item )
				{
					$notification->item = $this->item;
				}
				$notification->member_data = $info;
				
				foreach( $this->emailParams AS $param )
				{
					if ( $param instanceof \IPS\Content )
					{
						$subIdColumn = $param::$databaseColumnId;
						$notification->item_sub_class	= get_class( $param );
						$notification->item_sub_id		= $param->$subIdColumn;

						/*
						 * If this is a grouped comment or review, set the sent time to the same time as the comment just in case there is a slight delay
						 * @see <a href='http://community.invisionpower.com/resources/bugs.html/_/4-0-0/reply-notifications-missing-names-r46841'>Bug Report</a>
						 */
						if ( ( $param instanceof \IPS\Content\Comment OR $param instanceof \IPS\Content\Review ) && in_array( $this->key, array( 'new_comment', 'new_review', 'quote' ) ) )
						{
							$notification->sent_time = $param->mapped('date');
						}
					}
				}

				$notification->extra = $this->inlineExtra;
				
				$notification->save();
				
				$sentTo[ $member->member_id ][] = 'inline';
			}
		}

		/* Send any emails */
		$this->sendEmails( $emails, $emailRecipients );
		
		/* And return */
		return $sentTo;
	}

	/**
	 * Send emails
	 *
	 * @param	array 	$emails				Emails to send
	 * @param	array 	$emailRecipients	Email recipients
	 * @return	void
	 */
	protected function sendEmails( $emails, $emailRecipients )
	{
		foreach ( $emails as $languageId => $email )
		{
			if ( !empty( $emailRecipients[ $languageId ] ) )
			{
				$email->mergeAndSend( $emailRecipients[ $languageId ], NULL, NULL, array(), \IPS\Lang::load( $languageId ) );
			}
		}
	}
}