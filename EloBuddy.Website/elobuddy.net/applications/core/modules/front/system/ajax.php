<?php
/**
 * @brief		AJAX actions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		04 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AJAX actions
 */
class _ajax extends \IPS\Dispatcher\Controller
{
	/**
	 * Find Member
	 *
	 * @retun	void
	 */
	public function findMember()
	{
		$results = array();
		
		$input = mb_strtolower( \IPS\Request::i()->input );
		
		$where = array( "name LIKE CONCAT('%', ?, '%')" );
		$binds = array( $input );
		if ( \IPS\Dispatcher::i()->controllerLocation === 'admin' )
		{
			$where[] = "email LIKE CONCAT('%', ?, '%')";
			$binds[] = $input;
			
			if ( is_numeric( \IPS\Request::i()->input ) )
			{
				$where[] = "member_id=?";
				$binds[] = intval( \IPS\Request::i()->input );
			}
		}
				
		/* Build the array item for this member after constructing a record */
		/* The value should be just the name so that it's inserted into the input properly, but for display, we wrap it in the group *fix */
		foreach ( \IPS\Db::i()->select( '*', 'core_members', array_merge( array( implode( ' OR ', $where ) ), $binds ), 'LENGTH(name) ASC', array( 0, 20 ) ) as $row )
		{
			$member = \IPS\Member::constructFromData( $row );
			
			$results[] = array(
				'id'	=> 	$member->member_id,
				'value' => 	$member->name,
				'name'	=> 	\IPS\Dispatcher::i()->controllerLocation == 'admin' ? $member->group['prefix'] . htmlspecialchars( $member->name, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) . $member->group['suffix'] : htmlspecialchars( $member->name, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ),
				'extra'	=> 	\IPS\Dispatcher::i()->controllerLocation == 'admin' ? htmlspecialchars( $member->email, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) : $member->groupName,
				'photo'	=> 	(string) $member->photo,
			);
		}
				
		\IPS\Output::i()->json( $results );
	}
	
	/**
	 * Returns boolean in json indicating whether the supplied username already exists
	 *
	 * @return	void
	 */
	public function usernameExists()
	{
		$result = array( 'result' => 'ok' );
		
		/* The value comes urlencoded so we need to decode so length is correct (and not using a percent-encoded value) */
		$name = urldecode( \IPS\Request::i()->input );
		
		/* Check is valid */
		if ( !$name )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_required') );
		}
		elseif ( mb_strlen( $name ) < \IPS\Settings::i()->min_user_name_length )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack( 'form_minlength', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->min_user_name_length ) ) ) );
		}
		elseif ( mb_strlen( $name ) > \IPS\Settings::i()->max_user_name_length )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack( 'form_maxlength', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->max_user_name_length ) ) ) );
		}
		elseif ( \IPS\Settings::i()->username_characters and !preg_match( '/^[' . str_replace( '\-', '-', preg_quote( \IPS\Settings::i()->username_characters, '/' ) ) . ']*$/i', $name ) )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_bad_value') );
		}

		/* Check if it exists */
		else
		{
			foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
			{
				if ( $handler->usernameIsInUse( $name ) === TRUE )
				{
					if ( \IPS\Member::loggedIn()->isAdmin() )
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_name_exists_admin', FALSE, array( 'sprintf' => array( $k ) ) ) );
					}
					else
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_name_exists') );
					}
				}
			}
		}
		
		/* Check it's not banned */
		if ( $result == array( 'result' => 'ok' ) )
		{
			foreach( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array("ban_type=?", 'name') ) as $bannedName )
			{
				if( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedName, '/' ) ) . '$/i', $name ) )
				{
					$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_name_banned') );
				}
			}
		}

		\IPS\Output::i()->json( $result );	
	}

	/**
	 * Returns boolean in json indicating whether the supplied email already exists
	 *
	 * @return	void
	 */
	public function emailExists()
	{
		$result = array( 'result' => 'ok' );
		
		/* The value comes urlencoded so we need to decode so length is correct (and not using a percent-encoded value) */
		$email = urldecode( \IPS\Request::i()->input );
		
		/* Check is valid */
		if ( !$email )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_required') );
		}
		elseif ( filter_var( $email, FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('form_bad_value') );
		}

		/* Check if it exists */
		else
		{
			foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
			{
				if ( $handler->emailIsInUse( $email ) === TRUE )
				{
					if ( \IPS\Member::loggedIn()->isAdmin() )
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_email_exists_admin', FALSE, array( 'sprintf' => array( $k ) ) ) );
					}
					else
					{
						$result = array( 'result' => 'fail', 'message' => \IPS\Member::loggedIn()->language()->addToStack('member_email_exists') );
					}
				}
			}
		}

		\IPS\Output::i()->json( $result );	
	}

	/**
	 * Get state/region list for country
	 *
	 * @return	void
	 */
	public function states()
	{
		$states = array();
		if ( array_key_exists( \IPS\Request::i()->country, \IPS\GeoLocation::$states ) )
		{
			$states = \IPS\GeoLocation::$states[ \IPS\Request::i()->country ];
		}
		
		\IPS\Output::i()->json( $states );
	}
	
	/**
	 * Top Contributors
	 *
	 * @retun	void
	 */
	public function topContributors()
	{
		/* How many? */
		$limit = intval( ( isset( \IPS\Request::i()->limit ) and \IPS\Request::i()->limit <= 25 ) ? \IPS\Request::i()->limit : 5 );
		
		/* What timeframe? */
		$where = array( array( 'member_received > 0' ) );
		$timeframe = 'all';
		if ( isset( \IPS\Request::i()->time ) and \IPS\Request::i()->time != 'all' )
		{
			switch ( \IPS\Request::i()->time )
			{
				case 'week':
					$where[] = array( 'rep_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1W' ) )->getTimestamp() );
					$timeframe = 'week';
					break;
				case 'month':
					$where[] = array( 'rep_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1M' ) )->getTimestamp() );
					$timeframe = 'month';
					break;
				case 'year':
					$where[] = array( 'rep_date>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1Y' ) )->getTimestamp() );
					$timeframe = 'year';
					break;
			}
            $topContributors = iterator_to_array( \IPS\Db::i()->select( 'core_reputation_index.member_received as member, SUM(rep_rating) as rep', 'core_reputation_index', $where, 'rep DESC', $limit, 'member' )->setKeyField('member')->setValueField('rep') );
        }
        else
        {
            $topContributors = iterator_to_array( \IPS\Db::i()->select( 'member_id as member, pp_reputation_points as rep', 'core_members', array( 'pp_reputation_points > 0' ), 'rep DESC', $limit )->setKeyField('member')->setValueField('rep') );
        }

		/* Load their data */	
		foreach ( \IPS\Db::i()->select( '*', 'core_members', \IPS\Db::i()->in( 'member_id', array_keys( $topContributors ) ) ) as $member )
		{
			\IPS\Member::constructFromData( $member );
		}
		
		/* Render */
		$output = \IPS\Theme::i()->getTemplate( 'widgets' )->topContributorRows( $topContributors, $timeframe, \IPS\Request::i()->orientation );
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output );
		}
		else
		{
			\IPS\Output::i()->output = $output;
		}
	}
	
	/**
	 * Menu Preview
	 *
	 * @retun	void
	 */
	public function menuPreview()
	{
		if ( isset( \IPS\Request::i()->theme ) )
		{
			\IPS\Theme::switchTheme( \IPS\Request::i()->theme, FALSE );
		}
		
		$preview = \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->navBar( TRUE );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'system/menumanager.css', 'core', 'admin' ) );
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'applications', 'core', 'admin' )->menuPreviewWrapper( $preview ) );
	}
	
	/**
	 * Instant Notifications
	 *
	 * @retun	void
	 */
	public function instantNotifications()
	{
		/* If auto-polling isn't enabled, kill the polling now */
		if ( !\IPS\Settings::i()->auto_polling_enabled )
		{
			\IPS\Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
			return;
		}

		/* Get the intitial counts */
		$return = array( 'notifications' => array( 'count' => \IPS\Member::loggedIn()->notification_cnt, 'data' => array() ), 'messages' => array( 'count' => \IPS\Member::loggedIn()->msg_count_new, 'data' => array() ) );
		
		/* If there's new notifications, get the actual data */
		if ( \IPS\Request::i()->notifications < $return['notifications']['count'] )
		{
			$notificationsDifference = $return['notifications']['count'] - \IPS\Request::i()->notifications;

			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_notifications', array( 'member=? AND ( read_time IS NULL OR read_time<? )', \IPS\Member::loggedIn()->member_id, time() ), 'updated_time DESC', $notificationsDifference ), 'IPS\Notification\Inline' ) as $notification )
			{
				/* It is possible that the content has been removed after the iterator has started but before we fetch the data */
				try
				{
					$data = $notification->getData();
				}
				catch( \OutOfRangeException $e )
				{
					continue;
				}
								
				$return['notifications']['data'][] = array(
					'id'			=> $notification->id,
					'title'			=> htmlspecialchars( $data['title'], \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ),
					'url'			=> (string) $data['url'],
					'content'		=> isset( $data['content'] ) ? htmlspecialchars( $data['content'], \IPS\HTMLENTITIES, 'UTF-8', FALSE ) : NULL,
					'date'			=> $notification->updated_time->getTimestamp(),
					'author_photo'	=> $data['author'] ? $data['author']->photo : NULL
				);
			}
		}
		
		/* If there's new messages, get the actual data */
		if ( !\IPS\Member::loggedIn()->members_disable_pm and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging' ) ) )
		{
			if ( \IPS\Request::i()->messages < $return['messages']['count'] )
			{
				$messagesDifference = $return['messages']['count'] - \IPS\Request::i()->messages;

				foreach ( \IPS\Db::i()->select( 'map_topic_id', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0', \IPS\Member::loggedIn()->member_id ), 'map_last_topic_reply DESC', $messagesDifference ) as $conversationId )
				{
					$conversation = \IPS\core\Messenger\Conversation::load( $conversationId );
					$message = $conversation->comments( 1, 0, 'date', 'desc' );
									
					$return['messages']['data'][] = array(
						'id'			=> $conversation->id,
						'title'			=> htmlspecialchars( $conversation->title, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ),
						'url'			=> (string) $conversation->url()->setQueryString( 'latest', 1 ),
						'message'		=> $message->truncated(),
						'date'			=> $message->mapped('date'),
						'author_photo'	=> (string) $message->author()->photo
					);
				}
			}
		}
		
		/* And return */
		\IPS\Output::i()->json( $return );
	}
}