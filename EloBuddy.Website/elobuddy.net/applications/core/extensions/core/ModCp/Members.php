<?php
/**
 * @brief		Moderator Control Panel Extension: Member Management
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		24 Oct 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member Management
 */
class _Members
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string
	 */
	public function getTab()
	{
		/* Check Permissions */
		if ( ! \IPS\Member::loggedIn()->modPermission('can_modify_profiles') )
		{
			return null;
		}
		
		return 'members';
	}
	
	/**
	 * Get content to display
	 *
	 * @return	string
	 */
	public function manage()
	{
		/* Check Permissions */
		if ( ! \IPS\Member::loggedIn()->modPermission('can_modify_profiles') )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C228/1', 403, '' );
		}
		
		/* Which filter? */
		$area = \IPS\Request::i()->area ?: 'banned';
		
		/* Member search form */
		$form = new \IPS\Helpers\Form( 'form', 'edit' );
	
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Member( 'modcp_member_find', NULL, TRUE, array( 'multiple' => 1, 'placeholder' => \IPS\Member::loggedIn()->language()->addToStack('modcp_member_find') ) ) );
		
		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=members&controller=profile&do=edit&id={$values['modcp_member_find']->member_id}", 'front', 'edit_profile', array( $values['modcp_member_find']->members_seo_name ) ) );
		}
		
		/* Load the extensions */
		$tabs = array();
		foreach ( \IPS\Application::allExtensions( 'core', 'ModCpMemberManagement', TRUE, 'core', 'Banned' ) as $key => $extension )
		{
			$tab = $extension->getTab();

			if ( $tab )
			{
				$tabs[ $tab ][] = $key;
				$exploded = explode( "_", $key );
				if( mb_strtolower( $key ) == $exploded[0] . "_" . mb_strtolower( $area ) )
				{
					$content = call_user_func( array( $extension, 'manage' ) );
				}
			}
		}

		/* Display */
		if ( \IPS\Request::i()->isAjax() )
		{
			return $content;
		}
		else
		{
			\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'modcp_members' ) );
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'modcp_members' );
			return \IPS\Theme::i()->getTemplate( 'modcp' )->members( $content, $tabs, $area, $form );
		}
	}
}