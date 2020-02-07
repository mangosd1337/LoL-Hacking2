<?php
/**
 * @brief		Messenger
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\messaging;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Messenger
 */
class _messenger extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\core\Messenger\Conversation';
	
	/**
	 * [Content\Controller]	FURL Base
	 */
	protected static $furlBase = 'messenger';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{		
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C137/1', 403, '' );
		}
		
		if ( \IPS\Member::loggedIn()->members_disable_pm AND \IPS\Member::loggedIn()->members_disable_pm != 2 AND \IPS\Request::i()->do != 'enableMessenger' )
		{
			\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack('messenger_disabled_can_enable', FALSE, array( 'sprintf' => array( \IPS\Http\URL::internal( 'app=core&module=messaging&controller=messenger&do=enableMessenger', 'front' )->csrf() ) ) ), '2C137/A', 403, '' );
		}
		else if ( \IPS\Member::loggedIn()->members_disable_pm == 2 )
		{
			\IPS\Output::i()->error( 'messenger_disabled', '2C137/9', 403, '' );
		}

		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		if ( !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery-ui.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery-touchpunch.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_model_messages.js', 'core' ) );
			\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_messages.js', 'core' ) );
			\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/messaging.css' ) );
			\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/messaging_responsive.css' ) );
		}
		
		/* Set Session Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', NULL, 'messenger' ), array(), 'loc_using_messenger' );
		
		parent::execute();
	}
	
	/**
	 * Messenger
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$baseUrl = \IPS\Http\Url::internal( "app=core&module=messaging&controller=messenger", 'front', 'messaging' );
		
		/* Get folders */
		$folders = array( 'myconvo'	=> \IPS\Member::loggedIn()->language()->addToStack('messenger_folder_inbox') );
		if ( \IPS\Member::loggedIn()->pconversation_filters )
		{
			$folders = $folders + array_filter( json_decode( \IPS\Member::loggedIn()->pconversation_filters, TRUE ) );
		}
		
		/* Are we looking at a specific folder? */
		if ( isset( $folders[ \IPS\Request::i()->folder ] ) )
		{
			$baseUrl = $baseUrl->setQueryString( 'folder', \IPS\Request::i()->folder );
			$folder = \IPS\Request::i()->folder;
		}
		else
		{
			$folder = NULL;
		}
		
		/* What are our folder counts? */
		/* Note: The setKeyField and setValueField calls here were causing the folder counts to be incorrect (if you had two folders with 1 message in each, then both showed a count of 2) */
		$counts = iterator_to_array( \IPS\Db::i()->select( 'map_folder_id, count(*) as count', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', \IPS\Member::loggedIn()->member_id ), NULL, NULL, 'map_folder_id' ) );
		$folderCounts = array();
		foreach( $counts AS $k => $count )
		{
			$folderCounts[$count['map_folder_id']] = $count['count'];
		}
		
		/* Are we looking at a message? */
		$conversation = NULL;
		if ( \IPS\Request::i()->id )
		{
			try
			{
				$conversation = \IPS\core\Messenger\Conversation::loadAndCheckPerms( \IPS\Request::i()->id );

				/* If this message triggered a PM popup and we view it directly, it will no longer be marked as unread but msg_show_notification for
					the member will still be set, causing the next oldest PM to show in a popup erroneously.  If this is the latest unread PM, reset
					msg_show_notification now to prevent the popup from showing. */
				if( \IPS\Member::loggedIn()->msg_show_notification )
				{
					$latestConversation = NULL;

					try
					{
						$latestConversation = \IPS\Db::i()->select( 'map_topic_id', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1 AND map_has_unread=1 AND map_ignore_notification=0', \IPS\Member::loggedIn()->member_id ), 'map_last_topic_reply DESC' )->first();
					}
					catch ( \UnderflowException $e ) { }

					if( $latestConversation == $conversation->id )
					{
						\IPS\Member::loggedIn()->msg_show_notification = FALSE;
						\IPS\Member::loggedIn()->save();
					}
				}
				
				\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_read_time' => time(), 'map_has_unread' => 0 ), array( 'map_user_id=? AND map_topic_id=?', \IPS\Member::loggedIn()->member_id, $conversation->id ) );
				\IPS\core\Messenger\Conversation::rebuildMessageCounts( \IPS\Member::loggedIn() );

				if ( \IPS\Request::i()->isAjax() and \IPS\Request::i()->getRow )
				{
					$row = \IPS\Db::i()->select(
						'core_message_topic_user_map.*, core_message_topics.*',
						'core_message_posts',
						array( 'mt_id=?', $conversation->id )
					)->join(
						'core_message_topics',
						'core_message_posts.msg_topic_id=core_message_topics.mt_id'
					)->join(
						'core_message_topic_user_map',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					)->first();
					$row['last_message'] = \IPS\core\Messenger\Conversation::load( $row['mt_id'] )->comments( 1, 0, 'date', 'desc' );

					\IPS\Output::i()->json( \IPS\Theme::i()->getTemplate( 'messaging' )->messageListRow( $row, FALSE ) );
				}

				\IPS\Output::i()->title = $conversation->title;
				$baseUrl = $baseUrl->setQueryString( 'id', \IPS\Request::i()->id );
				
				if ( isset( \IPS\Request::i()->page ) and $conversation->replies )
				{
					$maxPages = ceil( $conversation->replies / $conversation::getCommentsPerPage() );
					if ( \IPS\Request::i()->page > $maxPages )
					{
						\IPS\Output::i()->redirect( $baseUrl->setQueryString( 'page', $maxPages ) );
					}
				}
				
				/* We check isset() on the map because checking message from a report means no map will be returned */
				if ( $folder === NULL AND isset( $conversation->map['map_folder_id'] ) )
				{
					$folder = $conversation->map['map_folder_id'];
				}
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C137/2', 403, '' );
			}
			
			if ( isset( \IPS\Request::i()->latest ) )
			{
				$maps = $conversation->maps();

				$message = NULL;
				if ( isset( $maps[ \IPS\Member::loggedIn()->member_id ] ) and $maps[ \IPS\Member::loggedIn()->member_id ]['map_read_time'] )
				{
					$message = $conversation->comments( 1, NULL, 'date', 'asc', NULL, NULL, \IPS\DateTime::ts( $maps[ \IPS\Member::loggedIn()->member_id ]['map_read_time'] ) );
				}

				/** if we don't have a unread comment, redirect to the last comment */
				if ( !$message )
				{
					$message = $conversation->comments( 1, NULL, 'date', 'desc', NULL, NULL );
				}

				if ( $message )
				{
					\IPS\Output::i()->redirect( $message->url() );
				}
			}
		}
		
		/* Default folder */
		if ( $folder === NULL )
		{
			$folder = 'myconvo';
		}
		
		if ( !isset( \IPS\Request::i()->id ) )
		{
			\IPS\Output::i()->title = $folders[ $folder ];
		}
		
		/* Do we need a filter? */
		$where = array( array( 'core_message_topic_user_map.map_user_id=? AND core_message_topic_user_map.map_user_active=1', \IPS\Member::loggedIn()->member_id ) );
		if ( \IPS\Request::i()->filter == 'mine' )
		{
			$where[] = array( 'core_message_topic_user_map.map_is_starter=1' );
		}
		elseif(  \IPS\Request::i()->filter == 'not_mine' )
		{
			$where[] = array( 'core_message_topic_user_map.map_is_starter=0' );
		}

		/* Add a folder filter */
		if ( !isset( \IPS\Request::i()->overview ) )
		{
			if ( !is_null( $folder ) AND isset( $folders[ $folder ] ) )
			{
				$where[] = array( 'core_message_topic_user_map.map_folder_id=?', $folder );
			}
			else
			{
				$where[] = array( 'core_message_topic_user_map.map_folder_id=?', 'myconvo' );
			}
		}

		/* If we're searching, get the results */
		$perPage	= 25;
		$iterator	= array();

		if ( \IPS\Request::i()->q )
		{
			$where[]	= array( '(' .
				\IPS\Content\Search\Mysql\Query::matchClause( "core_message_topics.mt_title", \IPS\Request::i()->q, '+', FALSE ) . 
				' OR ' .
				\IPS\Content\Search\Mysql\Query::matchClause( "core_message_posts.msg_post", \IPS\Request::i()->q, '+', FALSE ) . 
				' OR ' .
				"sending_member.name LIKE '%" . \IPS\Db::i()->escape_string( \IPS\Request::i()->q ) . "%'" .
				' OR ' . 
				"receiving_member.name LIKE '%" . \IPS\Db::i()->escape_string( \IPS\Request::i()->q ) . "%'" .
				')' );

			/* Get a count */
			try
			{
				$count	= \IPS\Db::i()->select( 'COUNT(*)', 'core_message_posts', $where, NULL, NULL, 'msg_topic_id' )
					->join(
						'core_message_topics',
						'core_message_posts.msg_topic_id=core_message_topics.mt_id'
					)
					->join(
						'core_message_topic_user_map',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					)
					->join(
						array( 'core_message_topic_user_map', 'sender_map' ),
						'sender_map.map_topic_id=core_message_topics.mt_id AND sender_map.map_is_starter=1'
					)
					->join(
						array( 'core_message_topic_user_map', 'receiver_map' ),
						'receiver_map.map_topic_id=core_message_topics.mt_id AND receiver_map.map_is_starter=0'
					)
					->join(
						array( 'core_members', 'sending_member' ),
						'sending_member.member_id=sender_map.map_user_id'
					)
					->join(
						array( 'core_members', 'receiving_member' ),
						'receiving_member.member_id=receiver_map.map_user_id'
					)
					->first();
			}
			catch( \UnderflowException $e )
			{
				$count	= 0;
			}

			/* Performance: if count is 0, don't bother selecting ... it's a wasted query */
			if( $count )
			{
				/* Because of strict group by, we first need to select the ids, then grab those topics */
				$sortColumn	= ( in_array( \IPS\Request::i()->sortBy, array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) ) ? \IPS\Request::i()->sortBy : 'mt_last_post_time' );
				$iterator	= \IPS\Db::i()->select(
						'core_message_posts.msg_topic_id',
						'core_message_posts',
						$where,
						$sortColumn . ' DESC',
						array( ( intval( \IPS\Request::i()->listPage ?: 1 ) - 1 ) * $perPage, $perPage ),
						array( 'msg_topic_id', $sortColumn )
					)->join(
						'core_message_topics',
						'core_message_posts.msg_topic_id=core_message_topics.mt_id'
					)->join(
						'core_message_topic_user_map',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					)
					->join(
						array( 'core_message_topic_user_map', 'sender_map' ),
						'sender_map.map_topic_id=core_message_topics.mt_id AND sender_map.map_is_starter=1'
					)
					->join(
						array( 'core_message_topic_user_map', 'receiver_map' ),
						'receiver_map.map_topic_id=core_message_topics.mt_id AND receiver_map.map_is_starter=0'
					)
					->join(
						array( 'core_members', 'sending_member' ),
						'sending_member.member_id=sender_map.map_user_id'
					)
					->join(
						array( 'core_members', 'receiving_member' ),
						'receiving_member.member_id=receiver_map.map_user_id'
					);

				/* Get iterator */
				$iterator	= \IPS\Db::i()->select(
						'core_message_topic_user_map.*, core_message_topics.*',
						'core_message_topic_user_map',
						array( 'map_topic_id IN(' . implode( ',', iterator_to_array( $iterator ) ) . ')' ),
						( in_array( \IPS\Request::i()->sortBy, array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) ) ? \IPS\Request::i()->sortBy : 'mt_last_post_time' ) . ' DESC',
						array( ( intval( \IPS\Request::i()->listPage ?: 1 ) - 1 ) * $perPage, $perPage )
					)->join(
						'core_message_topics',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					);
			}
		}
		else
		{
			/* Get a count */
			$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_message_topic_user_map', $where )->first();

			/* Performance: if $count is 0, don't bother selecting ... it's a wasted query */
			if( $count )
			{
				/* Get iterator */
				$iterator	= \IPS\Db::i()->select(
						'core_message_topic_user_map.*, core_message_topics.*',
						'core_message_topic_user_map',
						$where,
						( in_array( \IPS\Request::i()->sortBy, array( 'mt_last_post_time', 'mt_start_time', 'mt_replies' ) ) ? \IPS\Request::i()->sortBy : 'mt_last_post_time' ) . ' DESC',
						array( ( intval( \IPS\Request::i()->listPage ?: 1 ) - 1 ) * $perPage, $perPage )
					)->join(
						'core_message_topics',
						'core_message_topic_user_map.map_topic_id=core_message_topics.mt_id'
					);
			}
		}

		/* Build the message list */
		$conversations = array();

		foreach ( $iterator as $row )
		{
			$row['last_message'] = \IPS\core\Messenger\Conversation::load( $row['mt_id'] )->comments( 1, 0, 'date', 'desc' );
			$row['participants'] = \IPS\core\Messenger\Conversation::load( $row['mt_id'] )->participantBlurb();
			$conversations[ $row['mt_id'] ] = $row;
		}

		/* Note the last time we looked at the message list */
		\IPS\Member::loggedIn()->msg_count_reset = time();
		\IPS\core\Messenger\Conversation::rebuildMessageCounts( \IPS\Member::loggedIn() );

		/* Display */
		$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $baseUrl->setQueryString('_list', 1), ceil( $count / $perPage ), ( \IPS\Request::i()->listPage ?: 1 ), $perPage, TRUE, 'listPage' );
		if ( \IPS\Request::i()->isAjax() )
		{
			if( \IPS\Request::i()->id )
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('messaging')->conversation( $conversation, $folders );
			}
			elseif( \IPS\Request::i()->overview )
			{
				\IPS\Output::i()->json( array( 'data' => \IPS\Theme::i()->getTemplate('messaging')->messageListRows( $conversations, $pagination, TRUE ) ) );
			}
			else
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate('messaging')->messageListRows( $conversations, $pagination, TRUE ), 200, 'text/html' );
			}
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('messaging')->template( $folder, $folders, $folderCounts, $conversations, $pagination, $conversation, $baseUrl, ( $conversation ? 'messenger_convo' : 'messenger' ), ( \IPS\Request::i()->sortBy ? htmlspecialchars( \IPS\Request::i()->sortBy, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ): 'mt_last_post_time' ), ( \IPS\Request::i()->filter ? htmlspecialchars( \IPS\Request::i()->filter, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ) : '' ) );
		}
	}
	
	/**
	 * Compose
	 *
	 * @return	void
	 */
	protected function compose()
	{
		$form = \IPS\core\Messenger\Conversation::create();
		$form->class = 'ipsForm_vertical';

		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('compose_new');
		\IPS\Output::i()->output	= $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Add Folder
	 *
	 * @return	void
	 */
	protected function addFolder()
	{
		\IPS\Session::i()->csrfCheck();

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'messenger_add_folder_name', NULL, TRUE ) );
		if ( $values = $form->values() )
		{
			$folders = json_decode( \IPS\Member::loggedIn()->pconversation_filters, TRUE );
			$folders[] = $values['messenger_add_folder_name'];
			\IPS\Member::loggedIn()->pconversation_filters = json_encode( $folders );
			\IPS\Member::loggedIn()->save();

			if ( \IPS\Request::i()->isAjax() )
			{
				$keys = array_keys( $folders );
				
				\IPS\Output::i()->json( array(
					'folderName' => $values['messenger_add_folder_name'],
					'key' => array_pop( $keys )
				)	);
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ) );
			}
		}
		
		$form->class = 'ipsForm_vertical';
		$formHtml = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );

		\IPS\Output::i()->output = \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'messaging' )->folderForm( 'add', $formHtml );
	}
	
	/**
	 * Mark Folder Read
	 *
	 * @return	void
	 */
	protected function readFolder()
	{
		\IPS\Session::i()->csrfCheck();
		\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_has_unread' => FALSE ), array( 'map_user_id=? AND map_folder_id=?', \IPS\Member::loggedIn()->member_id, \IPS\Request::i()->folder ) );
		\IPS\core\Messenger\Conversation::rebuildMessageCounts( \IPS\Member::loggedIn() );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ) );
	}
	
	/**
	 * Delete Folder
	 *
	 * @return	void
	 */
	protected function deleteFolder()
	{
		\IPS\Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => FALSE, 'map_left_time' => time() ), array( 'map_user_id=? AND map_folder_id=?', \IPS\Member::loggedIn()->member_id, \IPS\Request::i()->folder ) );
		
		$folders = json_decode( \IPS\Member::loggedIn()->pconversation_filters, TRUE );

		unset( $folders[ \IPS\Request::i()->folder ] );
		
		\IPS\Member::loggedIn()->pconversation_filters = json_encode( $folders );
		\IPS\Member::loggedIn()->save();
		
		\IPS\core\Messenger\Conversation::rebuildMessageCounts( \IPS\Member::loggedIn() );

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( $this->_getNewTotals() );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ) );
		}
	}
	
	/**
	 * Empty Folder
	 *
	 * @return	void
	 */
	protected function emptyFolder()
	{
		\IPS\Session::i()->csrfCheck();
		\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_user_active' => FALSE, 'map_left_time' => time() ), array( 'map_user_id=? AND map_folder_id=?', \IPS\Member::loggedIn()->member_id, \IPS\Request::i()->folder ) );
		\IPS\core\Messenger\Conversation::rebuildMessageCounts( \IPS\Member::loggedIn() );
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( $this->_getNewTotals() );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ) );
		}
	}
	
	/**
	 * Rename Folder
	 *
	 * @return	void
	 */
	protected function renameFolder()
	{
		\IPS\Session::i()->csrfCheck();

			$folders = json_decode( \IPS\Member::loggedIn()->pconversation_filters, TRUE );
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'messenger_add_folder_name', $folders[ \IPS\Request::i()->folder ], TRUE ) );
		if ( $values = $form->values() )
		{
			$folders[ \IPS\Request::i()->folder ] = $values['messenger_add_folder_name'];
			\IPS\Member::loggedIn()->pconversation_filters = json_encode( $folders );
			\IPS\Member::loggedIn()->save();

			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array(
					'folderName' => $values['messenger_add_folder_name'],
					'key' => \IPS\Request::i()->folder
				)	);
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ) );
			}
		}
		
		$form->class = 'ipsForm_vertical';
		$formHtml = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );

		\IPS\Output::i()->output = \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'messaging' )->folderForm( 'rename', $formHtml );
	}
	
	/**
	 * Block a participant
	 *
	 * @return	void
	 */
	protected function blockParticipant()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			$conversation = \IPS\core\Messenger\Conversation::loadAndCheckPerms( \IPS\Request::i()->id );
			if ( $conversation->starter_id != \IPS\Member::loggedIn()->member_id )
			{
				throw new \BadMethodCallException;
			}
			$conversation->deauthorize( \IPS\Member::load( \IPS\Request::i()->member ), TRUE );

			if ( \IPS\Request::i()->isAjax() )
			{
				$thisUser = array();

				foreach ( $conversation->maps( TRUE ) as $map )
				{
					if( $map['map_user_id'] == \IPS\Request::i()->member )
					{
						$thisUser = $map;
					}
				}

				\IPS\Output::i()->output = \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'messaging' )->participant( $thisUser, $conversation );
			}
			else
			{
				\IPS\Output::i()->redirect( $conversation->url() );
			}
		}
		catch ( \LogicException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C137/4', 403, '' );
		}
	}
	
	/**
	 * Unblock / Add a participant
	 *
	 * @return	void
	 */
	protected function addParticipant()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			$conversation = \IPS\core\Messenger\Conversation::loadAndCheckPerms( \IPS\Request::i()->id );
			
			$members = array();
			$ids = array();
			$failed = 0;
			
			if ( isset( \IPS\Request::i()->member_names ) )
			{
				foreach ( explode( ',', \IPS\Request::i()->member_names ) as $name )
				{
					/* We have to html_entity_decode because the javascript code sends the encoded name here */
					$memberToAdd = \IPS\Member::load( html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ), 'name' );
					if ( $memberToAdd->member_id )
					{
						$members[] = $memberToAdd;
					}
					else
					{
						$failed++;
					}
				}
			}
			else
			{
				$memberToAdd = \IPS\Member::load( \IPS\Request::i()->member );
				if ( $memberToAdd->member_id )
				{
					$members[] = $memberToAdd;
				}
				else
				{
					$failed++;
				}
			}

			/* If we failed to load any members, error out if we're not ajaxing */
			if ( $failed > 0 && !\IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->error( 'form_member_bad', '2C137/5', 403, '' );
			}

			$maps = $conversation->maps( TRUE );
			/* Authorize each of the members */
			foreach ( $members as $member )
			{
				if ( array_key_exists( $member->member_id, $maps ) and !$maps[$member->member_id]['map_user_active'] and !\IPS\Request::i()->unblock )
				{
					throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('messenger_member_left', FALSE, array( 'sprintf' => array( $member->name ) ) ) );
				}
				
				$maps = $conversation->authorize( $member );
				$ids[] = $member->member_id;

				$notification = new \IPS\Notification( \IPS\Application::load('core'), 'private_message_added', $conversation, array( $conversation, \IPS\Member::loggedIn() ) );
				$notification->send();
			}

			/* Build the HTML for each new member */
			$memberHTML = array();

			foreach ( $maps as $map )
			{
				if( in_array( $map['map_user_id'], $ids ) )
				{
					$memberHTML[ $map['map_user_id'] ] = \IPS\Theme::i()->getTemplate( 'messaging' )->participant( $map, $conversation );
				}
			}

			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array( 'members' => $memberHTML, 'failed' => $failed ) );
			}
			else
			{
				\IPS\Output::i()->redirect( $conversation->url() );
			}
		}
		catch ( \InvalidArgumentException $e )
		{
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array( 'error' => $e->getMessage() ), 403 );
			}
			else
			{
				\IPS\Output::i()->error( $e->getMessage(), '2C137/B', 403, '' );
			}
		}
		catch ( \LogicException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C137/5', 403, '' );
		}
	}
	
	/**
	 * Turn notifications on/off
	 *
	 * @return	void
	 */
	protected function notifications()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			$conversation = \IPS\core\Messenger\Conversation::loadAndCheckPerms( \IPS\Request::i()->id );
			\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_ignore_notification' => !\IPS\Request::i()->status ), array( 'map_user_id=? AND map_topic_id=?', \IPS\Member::loggedIn()->member_id, $conversation->id ) );
			\IPS\Output::i()->redirect( $conversation->url() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C137/6', 403, '' );
		}
	}
	
	/**
	 * Move a conversation from one folder to another
	 *
	 * @return	void
	 */
	protected function move()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			$conversation = \IPS\core\Messenger\Conversation::loadAndCheckPerms( \IPS\Request::i()->id );
			
			if ( in_array( \IPS\Request::i()->to, array_merge( array( 'myconvo' ), array_keys( json_decode( \IPS\Member::loggedIn()->pconversation_filters, TRUE ) ) ) ) )
			{
				\IPS\Db::i()->update( 'core_message_topic_user_map', array( 'map_folder_id' => \IPS\Request::i()->to ), array( 'map_user_id=? AND map_topic_id=?', \IPS\Member::loggedIn()->member_id, $conversation->id ) );
			}
			
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( $this->_getNewTotals() );
			}
			else
			{
				\IPS\Output::i()->redirect( $conversation->url() );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C137/8', 403, '' );
		}
	}
	
	/**
	 * Leave the conversation
	 *
	 * @return	void
	 */
	protected function leaveConversation()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			$ids = \IPS\Request::i()->id;

			if ( !is_array( \IPS\Request::i()->id ) )
			{
				$ids = array( $ids );
			}

			foreach ( $ids as $id )
			{
				try
				{
					$conversation = \IPS\core\Messenger\Conversation::loadAndCheckPerms( $id );
					$conversation->deauthorize( \IPS\Member::loggedIn() );
				}
				catch ( \Exception $e ) {}
			}

			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array_merge( array( 'result' => 'success' ), $this->_getNewTotals() ) );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ) );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C137/7', 403, '' );
		}
	}
	
	/**
	 * Enable Messenger
	 *
	 * @return	void
	 */
	protected function enableMessenger()
	{
		\IPS\Session::i()->csrfCheck();
		\IPS\Member::loggedIn()->members_disable_pm = 0;
		\IPS\Member::loggedIn()->save();
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=messaging&controller=messenger', 'front', 'messenger' ), 'messenger_enabled' );
	}
	
	/**
	 * Disable Messenger
	 *
	 * @return	void
	 */
	protected function disableMessenger()
	{
		\IPS\Session::i()->csrfCheck();
		\IPS\Member::loggedIn()->members_disable_pm = 1;
		\IPS\Member::loggedIn()->save();
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ), 'messenger_disabled' );
	}

	/**
	 * Get user conversation storage data
	 *
	 * @return	array
	 */
	protected function _getNewTotals()
	{
		/* Get folders */
		$folders = array( 'myconvo'	=> \IPS\Member::loggedIn()->language()->addToStack('messenger_folder_inbox') );
		if ( \IPS\Member::loggedIn()->pconversation_filters )
		{
			$folders = array_merge( $folders, json_decode( \IPS\Member::loggedIn()->pconversation_filters, TRUE ) );
		}

		/* What are our folder counts? */
		$counts = iterator_to_array( \IPS\Db::i()->select( 'map_folder_id, count(*) as _count', 'core_message_topic_user_map', array( 'map_user_id=? AND map_user_active=1', \IPS\Member::loggedIn()->member_id ), NULL, NULL, 'map_folder_id' )->setKeyField( 'map_folder_id' )->setValueField( '_count' ) );
		
		/* Fill in for the empty folders */
		foreach ( $folders as $id => $name )
		{
			if( !isset( $counts[ $id ] ) )
			{
				$counts[ $id ] = 0;
			}
		}

		return array(
			'quotaText'		=> \IPS\Member::loggedIn()->language()->addToStack( 'messenger_quota', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->group['g_max_messages'] ), 'pluralize' =>  array( \IPS\Member::loggedIn()->msg_count_total ) ) ),
			'quotaPercent'	=> \IPS\Member::loggedIn()->group['g_max_messages'] ? ( ( 100 / \IPS\Member::loggedIn()->group['g_max_messages'] ) * \IPS\Member::loggedIn()->msg_count_total ) : 0,
			'totalMessages'	=> \IPS\Member::loggedIn()->msg_count_total,
			'newMessages'	=> \IPS\Member::loggedIn()->msg_count_new,
			'counts'		=> $counts
		);
	}
}