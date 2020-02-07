<?php
/**
 * @brief		Notification Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Settings
 */
class _notifications extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'notifications_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init Matrix */
		$matrix = new \IPS\Helpers\Form\Matrix;
		$matrix->manageable = FALSE;
		$matrix->langPrefix = 'notificationsettings_';
		$matrix->classes = array( 'cMemberNotifications' );
		
		/* Populate columns */
		$matrix->columns = array(
			'label'		=> function( $key, $value, $data )
			{
				if ( mb_substr( $key, 0, -7 ) === 'new_likes' )
				{
					if ( \IPS\Settings::i()->reputation_point_types === 'like' )
					{
						return 'notifications__new_likes_like';
					}
					else
					{
						return 'notifications__new_likes_rep';
					}
				}
				
				return $value;
			},
			'default'	=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\CheckboxSet( $key, $value, FALSE, array( 'options' => array( 'email' => 'member_notifications_email', 'inline' => 'member_notifications_inline' ), 'multiple' => TRUE ) );
			},
			'disabled'	=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\CheckboxSet( $key, $value, FALSE, array( 'options' => array( 'email' => 'member_notifications_email', 'inline' => 'member_notifications_inline' ), 'multiple' => TRUE ) );
			},
			'editable'	=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\YesNo( $key, $value );
			},
		);
		
		/* Populate rows */
		$current = iterator_to_array( \IPS\Db::i()->select( '*', 'core_notification_defaults' )->setKeyField( 'notification_key' ) );
		foreach( \IPS\Application::allExtensions( 'core', 'Notifications', FALSE ) as $group => $class )
		{
			$configuration = $class->getConfiguration( NULL );
			if ( !empty( $configuration ) )
			{
				$lang = "notifications__{$group}";
				$header = \IPS\Member::loggedIn()->language()->addToStack( $lang );
				$matrix->rows[] = $header;
				
				foreach ( $configuration as $key => $data )
				{
					$matrix->rows[ $key ] = array(
						'label'		=> "notifications__{$key}",
						'default'	=> isset( $current[ $key ] ) ? explode( ',', $current[ $key ]['default'] ) : $data['default'],
						'disabled'	=> isset( $current[ $key ] ) ? explode( ',', $current[ $key ]['disabled'] ) : $data['disabled'],
						'editable'	=> isset( $current[ $key ] ) ? $current[ $key ]['editable'] : TRUE,
					);
				}
			}
		}
		
		/* Handle submissions */
		if ( $values = $matrix->values() )
		{
			\IPS\Db::i()->delete( 'core_notification_defaults' );
			
			$inserts = array();
			foreach ( $values as $k => $data )
			{
				$inserts[] = array(
					'notification_key'	=> $k,
					'default'			=> implode( ',', $data['default'] ),
					'disabled'			=> implode( ',', $data['disabled'] ),
					'editable'			=> $data['editable']
				);
			}
			
			if( count( $inserts ) )
			{
					\IPS\Db::i()->insert( 'core_notification_defaults', $inserts );
			}
			
			\IPS\Session::i()->log( 'acplog__notifications_edited' );
		}
		
		/* Add a button for settings */
		\IPS\Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'notificationsettings',
				'icon'		=> 'cog',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=notifications&do=settings' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('notificationsettings') )
			),
		);
		
		/* Display */
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'members/notifications.css', 'core', 'admin' ) );
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('notifications');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'notifications', $matrix );
	}
	
	/**
	 * Profile Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'profiles_manage' );
		
		$form = new \IPS\Helpers\Form;
		
		$form->add( new \IPS\Helpers\Form\Number( 'subs_autoprune', \IPS\Settings::i()->subs_autoprune, FALSE, array(), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'prune_notifications', \IPS\Settings::i()->prune_notifications, FALSE, array(), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('months') ) );
		
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();

			\IPS\Session::i()->log( 'acplog__notification_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=notifications' ), 'saved' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('notificationsettings');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate('global')->block( 'notificationsettings', $form, FALSE );
	}
}