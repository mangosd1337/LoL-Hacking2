<?php
/**
 * @brief		Profile Fields and Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		09 Apr 2013
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
 * Profile Fields and Settings
 */
class _profiles extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\core\ProfileFields\Group';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'profilefields_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init */
		$activeTab = \IPS\Request::i()->tab ?: 'fields';
		$activeTabContents = '';
		$tabs = array( 'fields' => 'profile_fields' );
		
		/* Add a tab for settings */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'profiles_manage' ) )
		{
			$tabs['settings'] = 'profile_settings';
		}
		
		/* Get contents */
		if ( $activeTab == 'fields' )
		{	
			parent::manage();
			$activeTabContents = \IPS\Output::i()->output;
		}
		else
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'profiles_manage' );
		
			$form = new \IPS\Helpers\Form;
			
			$form->addHeader( 'photos' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'allow_gravatars', \IPS\Settings::i()->allow_gravatars ) );
			$form->addHeader( 'usernames' );
			$form->add( new \IPS\Helpers\Form\Custom( 'user_name_length', array( \IPS\Settings::i()->min_user_name_length, \IPS\Settings::i()->max_user_name_length ), FALSE, array(
				'getHtml'	=> function( $field ) {
					return \IPS\Theme::i()->getTemplate('members')->usernameLengthSetting( $field->name, $field->value );
				}
			),
			function( $val )
			{
				if ( $val[0] < 1 )
				{
					throw new \DomainException('user_name_length_too_low');
				}
				if ( $val[1] > 255 )
				{
					throw new \DomainException('user_name_length_too_high');
				}
				if ( $val[0] > $val[1] )
				{
					throw new \DomainException('user_name_length_no_match');
				}
			} ) );
			$form->add( new \IPS\Helpers\Form\Text( 'username_characters', \IPS\Settings::i()->username_characters, FALSE, array( 'max' => 255 ) ) );
			$form->addHeader( 'signatures' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'signatures_enabled', \IPS\Settings::i()->signatures_enabled ) );
			$form->addHeader( 'statuses_profile_comments' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'profile_comments', \IPS\Settings::i()->profile_comments, FALSE, array( 'togglesOn' => array( 'profile_comment_approval' ) ) ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'profile_comment_approval', \IPS\Settings::i()->profile_comment_approval, FALSE, array(), NULL, NULL, NULL, 'profile_comment_approval' ) );
			
			if ( $values = $form->values() )
			{
				$values['min_user_name_length'] = $values['user_name_length'][0];
				$values['max_user_name_length'] = $values['user_name_length'][1];
				unset( $values['user_name_length'] );
				
				$form->saveAsSettings( $values );
				\IPS\Session::i()->log( 'acplog__profile_settings' );
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=profiles&tab=settings' ), 'saved' );
			}
			
			$activeTabContents = (string) $form;
		}
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('module__core_profile');
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=membersettings&controller=profiles" ) );
		}
	}
}