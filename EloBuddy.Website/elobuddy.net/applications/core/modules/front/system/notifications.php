<?php
/**
 * @brief		Notification Settings Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 Aug 2013
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
 * Notification Settings Controller
 */
class _notifications extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 */
	protected function _checkLoggedIn()
	{
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C154/2', 403, '' );
		}
	}
	
	/**
	 * View Notifications
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$this->_checkLoggedIn();

		/* Init table */
		$urlObject	= \IPS\Http\Url::internal( 'app=core&module=system&controller=notifications', 'front', 'notifications' );
		$table = new \IPS\Notification\Table( $urlObject );
		$table->setMember( \IPS\Member::loggedIn() );		
		
		$notifications = $table->getRows();
	
		\IPS\Db::i()->update( 'core_notifications', array( 'read_time' => time() ), array( 'member=?', \IPS\Member::loggedIn()->member_id ) );
		\IPS\Member::loggedIn()->recountNotifications();
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( array( 'data' => \IPS\Theme::i()->getTemplate( 'system' )->notificationsAjax( $notifications ) ) );
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('notifications');
			\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Output::i()->title );
			\IPS\Output::i()->output = (string) $table;
		}
	}
	
	/**
	 * Options
	 *
	 * @return	void
	 */
	protected function options()
	{
		$this->_checkLoggedIn();

		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Checkbox( 'allow_admin_mails', \IPS\Member::loggedIn()->allow_admin_mails ) );

		$_autoTrack	= array();
		if( \IPS\Member::loggedIn()->auto_follow['content'] )
		{
			$_autoTrack[]	= 'content';
		}
		if( \IPS\Member::loggedIn()->auto_follow['comments'] )
		{
			$_autoTrack[]	= 'comments';
		}
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'auto_track', $_autoTrack, FALSE, array( 'options' => array( 'content' => 'auto_track_content', 'comments' => 'auto_track_comments' ), 'multiple' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'auto_track_type', \IPS\Member::loggedIn()->auto_follow['method'] ?: 'immediate', FALSE, array( 'options' => array(
			'immediate'	=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_immediate'),
			'daily'		=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_daily'),
			'weekly'	=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_weekly')
		) ), NULL, NULL, NULL, 'auto_track_type' ) );
		/* Changed in 4.1: since email_notifications_once is new, combined show_pm_popup in and made a checkbox set for better organization */
		$notificationPrefValues = array();
		if ( \IPS\Member::loggedIn()->members_bitoptions['show_pm_popup'] )
		{
			$notificationPrefValues[] = 'show_pm_popup';
		}
		if ( \IPS\Member::loggedIn()->members_bitoptions['email_notifications_once'] )
		{
			$notificationPrefValues[] = 'email_notifications_once';
		}
		if ( !\IPS\Member::loggedIn()->members_bitoptions['disable_notification_sounds'] )
		{
			$notificationPrefValues[] = 'enable_notification_sounds';
		}
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'notification_prefs', $notificationPrefValues, FALSE, array( 'options' => array( 'show_pm_popup' => 'show_pm_popup', 'email_notifications_once' => 'email_notifications_once', 'enable_notification_sounds' => 'enable_notification_sounds' ), 'multiple' => TRUE ) ) );
		$form->addMatrix( 'notifications', \IPS\Notification::buildMatrix( \IPS\Member::loggedIn() ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			\IPS\Member::loggedIn()->allow_admin_mails = $values['allow_admin_mails'];
			\IPS\Member::loggedIn()->auto_track = json_encode( array(
				'content'	=> ( is_array( $values['auto_track'] ) AND in_array( 'content', $values['auto_track'] ) ) ? 1 : 0,
				'comments'	=> ( is_array( $values['auto_track'] ) AND in_array( 'comments', $values['auto_track'] ) ) ? 1 : 0,
				'method'	=> $values['auto_track_type']
			)	);

			\IPS\Member::loggedIn()->members_bitoptions['show_pm_popup'] = ( is_array( $values['notification_prefs'] ) AND in_array( 'show_pm_popup', $values['notification_prefs'] ) ) ? 1 : 0;
			\IPS\Member::loggedIn()->members_bitoptions['email_notifications_once'] = ( is_array( $values['notification_prefs'] ) AND in_array( 'email_notifications_once', $values['notification_prefs'] ) ) ? 1 : 0;
			\IPS\Member::loggedIn()->members_bitoptions['disable_notification_sounds'] = ( is_array( $values['notification_prefs'] ) AND in_array( 'enable_notification_sounds', $values['notification_prefs'] ) ) ? 0 : 1;
						
			\IPS\Notification::saveMatrix( \IPS\Member::loggedIn(), $values );

			/* Refetch config to see if inline report center notification is set. We have to do this because if toggling report center
				option is disabled but inline is forced on by the admin, the setting won't be included in the $values array but the saveMatrix()
				method will automatically set it and calling notificationsConfiguration() will pull the data directly from the db. */
			$config = \IPS\Member::loggedIn()->notificationsConfiguration();

			if ( isset( $config['report_center'] ) and !in_array( 'inline', $config['report_center'] ) )
			{
				\IPS\Member::loggedIn()->members_bitoptions['no_report_count'] = TRUE;
			}
			else
			{
				\IPS\Member::loggedIn()->members_bitoptions['no_report_count'] = FALSE;
			}

			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=notifications&do=options', 'front', 'notifications_options' ), 'saved' );
		}
		
		/* Display */
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_system.js', 'core' ) );
		\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/notification_settings.css' ) );
		\IPS\Output::i()->output = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'system' ), 'notificationsSettings' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('notification_options');
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=system&controller=notifications', 'front', 'notifications' ), \IPS\Member::loggedIn()->language()->addToStack('notifications') );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('options') );
	}
	
	/**
	 * Follow Something
	 *
	 * @return	void
	 */
	protected function follow()
	{
		$this->_checkLoggedIn();

		/* Get class */
		$class = NULL;
		foreach ( \IPS\Application::load( \IPS\Request::i()->follow_app )->extensions( 'core', 'ContentRouter' ) as $ext )
		{
			foreach ( $ext->classes as $classname )
			{
				if ( $classname == 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area ) )
				{
					$class = $classname;
					break;
				}
				if ( isset( $classname::$containerNodeClass ) and $classname::$containerNodeClass == 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area ) )
				{
					$class = $classname::$containerNodeClass;
					break;
				}
				if( isset( $classname::$containerFollowClasses ) )
				{
					foreach( $classname::$containerFollowClasses as $followClass )
					{
						if( $followClass == 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area ) )
						{
							$class = $followClass;
							break;
						}
					}
				}
			}
		}
		
		if( \IPS\Request::i()->follow_app == 'core' and \IPS\Request::i()->follow_area == 'member' )
		{
			/* You can't follow yourself */
			if( \IPS\Request::i()->follow_id == \IPS\Member::loggedIn()->member_id )
			{
				\IPS\Output::i()->error( 'cant_follow_self', '3C154/7', 403, '' );
			}
			
			/* Following disabled */
			$member = \IPS\Member::load( \IPS\Request::i()->follow_id );

			if( !$member->member_id )
			{
				\IPS\Output::i()->error( 'cant_follow_member', '3C154/9', 403, '' );
			}

			if( $member->members_bitoptions['pp_setting_moderate_followers'] and !\IPS\Member::loggedIn()->following( 'core', 'member', $member->member_id ) )
			{
				\IPS\Output::i()->error( 'cant_follow_member', '3C154/8', 403, '' );
			}
				
			$class = 'IPS\\Member';
		}
		
		if ( !$class )
		{
			\IPS\Output::i()->error( 'node_error', '2C154/3', 404, '' );
		}
		
		/* Get thing */
		$thing = NULL;
		$indexId = NULL;
		try
		{
			if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				$thing = $class::loadAndCheckPerms( \IPS\Request::i()->follow_id );
				\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'follow_thing', FALSE, array( 'sprintf' => array( $thing->_title ) ) );

				/* Set navigation */
				try
				{
					foreach ( $thing->parents() as $parent )
					{
						\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
					}
					\IPS\Output::i()->breadcrumb[] = array( NULL, $thing->_title );
				}
				catch ( \Exception $e ) { }
			}
			else if ( $class != "IPS\Member" )
			{
				$thing = $class::loadAndCheckPerms( \IPS\Request::i()->follow_id );
				\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'follow_thing', FALSE, array( 'sprintf' => array( $thing->mapped('title') ) ) );
				
				/* Work out the index ID */
				try
				{
					$indexId = \IPS\Content\Search\Index::i()->getIndexId( $thing );
				}
				catch ( \UnderflowException $e ) { }

				/* Set navigation */
				$container = NULL;
				try
				{
					$container = $thing->container();
					foreach ( $container->parents() as $parent )
					{
						\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
					}
					\IPS\Output::i()->breadcrumb[] = array( $container->url(), $container->_title );
				}
				catch ( \Exception $e ) { }
				
				/* Set meta tags */
				\IPS\Output::i()->breadcrumb[] = array( NULL, $thing->mapped('title') );
			}
			else 
			{
				$thing = $class::load( \IPS\Request::i()->follow_id );				
				
				\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('follow_thing', FALSE, array( 'sprintf' => array( $thing->name ) ) );

				/* Set navigation */
				\IPS\Output::i()->breadcrumb[] = array( NULL, $thing->name );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C154/4', 404, '' );
		}
		
		/* Do we follow it? */
		try
		{
			$current = \IPS\Db::i()->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=? AND follow_member_id=?', \IPS\Request::i()->follow_app, \IPS\Request::i()->follow_area, \IPS\Request::i()->follow_id, \IPS\Member::loggedIn()->member_id ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			$current = FALSE;
		}
				
		/* How do we receive notifications? */
		if ( $class == 'IPS\Member' )
		{
			$type = 'follower_content';
		}
		elseif ( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
		{
			$type = 'new_comment';
		}
		else
		{
			$type = 'new_content';
		}
		$notificationConfiguration = \IPS\Member::loggedIn()->notificationsConfiguration();
		$notificationConfiguration = isset( $notificationConfiguration[ $type ] ) ? $notificationConfiguration[ $type ] : array();
		$lang = 'follow_type_immediate';
		if ( in_array( 'email', $notificationConfiguration ) and in_array( 'inline', $notificationConfiguration ) )
		{
			$lang = 'follow_type_immediate_inline_email';
		}
		elseif ( in_array( 'email', $notificationConfiguration ) )
		{
			$lang = 'follow_type_immediate_email';
		}
		
		if ( $class == "IPS\Member" )
		{
			\IPS\Member::loggedIn()->language()->words[ $lang ] = \IPS\Member::loggedIn()->language()->addToStack( $lang . '_member', FALSE, array( 'sprintf' => array( $thing->name ) ) );
		}
		
		if ( empty( $notificationConfiguration ) )
		{
			\IPS\Member::loggedIn()->language()->words[ $lang . '_desc' ] = \IPS\Member::loggedIn()->language()->addToStack( 'follow_type_immediate_none', FALSE ) . ' <a href="' .  \IPS\Http\Url::internal( 'app=core&module=system&controller=notifications&do=options&type=' . $type, 'front', 'notifications_options' ) . '">' . \IPS\Member::loggedIn()->language()->addToStack( 'notification_options', FALSE ) . '</a>';
		}
		else
		{
			\IPS\Member::loggedIn()->language()->words[ $lang . '_desc' ] = '<a href="' .  \IPS\Http\Url::internal( 'app=core&module=system&controller=notifications&do=options&type=' . $type, 'front', 'notifications_options' ) . '">' . \IPS\Member::loggedIn()->language()->addToStack( 'follow_type_immediate_change', FALSE ) . '</a>';
		}
			
		/* Build form */
		$form = new \IPS\Helpers\Form( 'follow', ( $current ) ? 'update_follow' : 'follow', NULL, array(
			'data-followApp' 	=> \IPS\Request::i()->follow_app,
			'data-followArea' 	=> \IPS\Request::i()->follow_area,
			'data-followID' 	=> \IPS\Request::i()->follow_id
		) );

		$form->class = 'ipsForm_vertical';
		
		$options = array();
		$options['immediate'] = $lang;
		
		if ( $class != "IPS\Member" )
		{
			$options['daily']	= \IPS\Member::loggedIn()->language()->addToStack('follow_type_daily');
			$options['weekly']	= \IPS\Member::loggedIn()->language()->addToStack('follow_type_weekly');
			$options['none']	= \IPS\Member::loggedIn()->language()->addToStack('follow_type_no_notification');
		}
		
		if ( count( $options ) > 1 )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'follow_type', $current ? $current['follow_notify_freq'] : NULL, TRUE, array(
				'options'	=> $options,
				'disabled'	=> empty( $notificationConfiguration ) ? array( 'immediate' ) : array()
			) ) );
		}
		else
		{	
			foreach ( $options as $k => $v )
			{
				$form->hiddenValues[ $k ] = $v;
				if ( empty( $notificationConfiguration ) )
				{
					$form->addMessage( \IPS\Member::loggedIn()->language()->addToStack( 'follow_type_no_config' ) . ' <a href="' .  \IPS\Http\Url::internal( 'app=core&module=system&controller=notifications&do=options&type=' . $type, 'front', 'notifications_options' ) . '">' . \IPS\Member::loggedIn()->language()->addToStack( 'notification_options', FALSE ) . '</a>', '', FALSE );

				}
				else
				{
					$form->addMessage( \IPS\Member::loggedIn()->language()->addToStack( $v ) . '<br>' . \IPS\Member::loggedIn()->language()->addToStack( $lang  . '_desc' ), '', FALSE );
				}
			}
		}
		$form->add( new \IPS\Helpers\Form\Checkbox( 'follow_public', $current ? !$current['follow_is_anon'] : TRUE, FALSE, array(
			'label' => ( $class != "IPS\Member" ) ? \IPS\Member::loggedIn()->language()->addToStack( 'follow_public' ) : \IPS\Member::loggedIn()->language()->addToStack('follow_public_member', FALSE, array( 'sprintf' => array( $thing->name ) ) )
		) ) );
		if ( $current )
		{
			$form->addButton( 'unfollow', 'link', \IPS\Http\Url::internal( "app=core&module=system&section=notifications&do=unfollow&id={$current['follow_id']}&follow_app={$current['follow_app']}&follow_area={$current['follow_area']}" )->csrf(), 'ipsButton ipsButton_negative ipsPos_right', array('data-action' => 'unfollow') );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Insert */
			$save = array(
				'follow_id'			=> md5( \IPS\Request::i()->follow_app . ';' . \IPS\Request::i()->follow_area . ';' . \IPS\Request::i()->follow_id . ';' .  \IPS\Member::loggedIn()->member_id ),
				'follow_app'			=> \IPS\Request::i()->follow_app,
				'follow_area'			=> \IPS\Request::i()->follow_area,
				'follow_rel_id'		=> \IPS\Request::i()->follow_id,
				'follow_member_id'	=> \IPS\Member::loggedIn()->member_id,
				'follow_is_anon'		=> !$values['follow_public'],
				'follow_added'		=> time(),
				'follow_notify_do'	=> ( isset( $values['follow_type'] ) AND $values['follow_type'] == 'none' ) ? 0 : 1,
				'follow_notify_meta'	=> '',
				'follow_notify_freq'	=> ( $class == "IPS\Member" ) ? 'immediate' : $values['follow_type'],
				'follow_notify_sent'	=> 0,
				'follow_visible'		=> 1,
				'follow_index_id'		=> $indexId,
			);
			if ( $current )
			{
				\IPS\Db::i()->update( 'core_follow', $save, array( 'follow_id=?', $current['follow_id'] ) );
			}
			else
			{
				\IPS\Db::i()->insert( 'core_follow', $save );
			}
			
			/* Send notification if following member */
			if( $class == "IPS\Member"  )
			{
				$notification = new \IPS\Notification( \IPS\Application::load( 'core' ), 'member_follow', \IPS\Member::loggedIn(), array( \IPS\Member::loggedIn() ) );
				$notification->recipients->attach( $thing );
				$notification->send();
			}
			
			/* Boink */
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( 'ok' );
			}
			else
			{
				\IPS\Output::i()->redirect( $thing->url() );
			}
		}

		/* Display */
		$output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'system', 'core' ) ), 'followForm' ) );

		/* @see http://community.invisionpower.com/4bugtrack/follow-button-adds-meta-title-and-link-tags-r3209/ */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output, 200, 'text/html', \IPS\Output::i()->httpHeaders );	
		}
		else
		{
			\IPS\Output::i()->output = $output;
		}		
	}
	
	/**
	 * Unfollow
	 *
	 * @return	void
	 */
	protected function unfollow()
	{
		$this->_checkLoggedIn();

		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$follow = \IPS\Db::i()->select( '*', 'core_follow', array( 'follow_id=? AND follow_member_id=?', \IPS\Request::i()->id, \IPS\Member::loggedIn()->member_id ) )->first();
		}
		catch ( \OutOfRangeException $e ) {}
		
		\IPS\Db::i()->delete( 'core_follow', array( 'follow_id=? AND follow_member_id=?', \IPS\Request::i()->id, \IPS\Member::loggedIn()->member_id ) );

		/* Get class */
		$class = NULL;
		foreach ( \IPS\Application::load( \IPS\Request::i()->follow_app )->extensions( 'core', 'ContentRouter' ) as $ext )
		{
			foreach ( $ext->classes as $classname )
			{
				if ( $classname == 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area ) )
				{
					$class = $classname;
					break;
				}
				if ( isset( $classname::$containerNodeClass ) and $classname::$containerNodeClass == 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area ) )
				{
					$class = $classname::$containerNodeClass;
					break;
				}
				if( isset( $classname::$containerFollowClasses ) )
				{
					foreach( $classname::$containerFollowClasses as $followClass )
					{
						if( $followClass == 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area ) )
						{
							$class = $followClass;
							break;
						}
					}
				}
			}
		}
		
		if( \IPS\Request::i()->follow_app == 'core' and \IPS\Request::i()->follow_area == 'member' )
		{
			$class = 'IPS\\Member';
		}

		/* Get thing */
		$thing = NULL;

		try
		{
			if ( $class != "IPS\Member" )
			{
				$thing = $class::loadAndCheckPerms( $follow['follow_rel_id'] );
			}
			else if( !in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				$thing = $class::load( $follow['follow_rel_id'] );
			}
		}
		catch ( \OutOfRangeException $e )
		{
		}

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'ok' );
		}
		else
		{
			\IPS\Output::i()->redirect( ( !empty( $_SERVER['HTTP_REFERER'] ) ) ? \IPS\Http\Url::external( $_SERVER['HTTP_REFERER'] ) : \IPS\Http\Url::internal( '' ) );
		}
	}
	
	/**
	 * Show Followers
	 *
	 * @return	void
	 */
	protected function followers()
	{
		$perPage	= 50;
		$thisPage	= isset( \IPS\Request::i()->followerPage ) ? \IPS\Request::i()->followerPage : 1;
		$thisPage	= ( $thisPage > 0 ) ? $thisPage : 1;
				
		/* Get class */
		if( \IPS\Request::i()->follow_app == 'core' and \IPS\Request::i()->follow_area == 'member' )
		{
			$class = 'IPS\\Member';
		}
		else
		{
			$class = 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area );
		}
		
		if ( !class_exists( $class ) )
		{
			\IPS\Output::i()->error( 'node_error', '2C154/5', 404, '' );
		}
		
		/* Get thing */
		$thing = NULL;
		$anonymous = 0;
		try
		{
			if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				$classname = $class::$contentItemClass;
				$containerClass = $class;
				$thing = $containerClass::loadAndCheckPerms( \IPS\Request::i()->follow_id );
				$followers = $classname::containerFollowers( $thing, $classname::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$anonymous = $classname::containerFollowers( $thing, $classname::FOLLOW_ANONYMOUS )->count( TRUE );
				$title = $thing->_title;
			}
			else if ( $class != "IPS\Member" )
			{
				$thing = $class::loadAndCheckPerms( \IPS\Request::i()->follow_id );
				$followers = $thing->followers( $class::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$anonymous = $thing->followers( $class::FOLLOW_ANONYMOUS )->count( TRUE );
				$title = $thing->name;
			}
			else
			{
				$thing = $class::load( \IPS\Request::i()->follow_id );
				$followers = $thing->followers( $class::FOLLOW_PUBLIC, array( 'none', 'immediate', 'daily', 'weekly' ), NULL, array( ( $thisPage - 1 ) * $perPage, $perPage ), 'name' );
				$anonymous = $thing->followers( $class::FOLLOW_ANONYMOUS )->count( TRUE );
				$title = $thing->mapped('title');
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C154/6', 404, '' );
		}
				
		/* Display */
		if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->_infScroll ) )
		{
			\IPS\Output::i()->sendOutput(  \IPS\Theme::i()->getTemplate( 'system' )->followersRows( $followers ) );
		}
		else
		{
			$url = \IPS\Http\Url::internal( "app=core&module=system&section=notifications&do=followers&follow_app=". \IPS\Request::i()->follow_app ."&follow_area=". \IPS\Request::i()->follow_area ."&follow_id=" . \IPS\Request::i()->follow_id . "&_infScroll=1" );
			$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $url, ceil( $followers->count( TRUE ) / $perPage ), $thisPage, $perPage, FALSE, 'followerPage' );
			
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('item_followers', FALSE, array( 'sprintf' => array( $title ) ) );
			\IPS\Output::i()->breadcrumb[] = array( $thing->url(), $title );
			\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('who_follows_this') );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->followers( $url, $pagination, $followers, $anonymous );
		}
	}

	/**
	 * Follow button
	 *
	 * @return	void
	 */
	protected function button()
	{
		/* Get class */
		if( \IPS\Request::i()->follow_app == 'core' and \IPS\Request::i()->follow_area == 'member' )
		{
			$class = 'IPS\\Member';
		}
		else
		{
			$class = 'IPS\\' . \IPS\Request::i()->follow_app . '\\' . ucfirst( \IPS\Request::i()->follow_area );
		}
		if ( !class_exists( $class ) )
		{
			\IPS\Output::i()->error( 'node_error', '2C154/5', 404, '' );
		}
		
		/* Get thing */
		$thing = NULL;
		try
		{
			if ( in_array( 'IPS\Node\Model', class_parents( $class ) ) )
			{
				$classname = $class::$contentItemClass;
				$containerClass = $class;
				$thing = $containerClass::loadAndCheckPerms( \IPS\Request::i()->follow_id );
				$count = $classname::containerFollowerCount( $thing );
			}
			else if ( $class != "IPS\Member" )
			{
				$thing = $class::loadAndCheckPerms( \IPS\Request::i()->follow_id );
				$count = $thing->followers()->count( TRUE );
			}
			else
			{
				$thing = $class::load( \IPS\Request::i()->follow_id );
				$count = $thing->followers()->count( TRUE );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C154/6', 404, '' );
		}

		if ( \IPS\Request::i()->follow_area == 'member' && ( !isset( \IPS\Request::i()->button_type ) || \IPS\Request::i()->button_type === 'search' ) )
		{
			if ( isset( \IPS\Request::i()->button_type ) && \IPS\Request::i()->button_type === 'search' )
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'profile' )->memberSearchFollowButton( \IPS\Request::i()->follow_app, \IPS\Request::i()->follow_area, \IPS\Request::i()->follow_id, $count ) );
			}
			else
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'profile' )->memberFollowButton( \IPS\Request::i()->follow_app, \IPS\Request::i()->follow_area, \IPS\Request::i()->follow_id, $count ) );	
			}			
		}
		else
		{
			if ( \IPS\Request::i()->button_type == 'manage' )
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'system' )->manageFollowButton( \IPS\Request::i()->follow_app, \IPS\Request::i()->follow_area, \IPS\Request::i()->follow_id ) );
			}
			else
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global' )->followButton( \IPS\Request::i()->follow_app, \IPS\Request::i()->follow_area, \IPS\Request::i()->follow_id, $count ) );	
			}			
		}
	}
}