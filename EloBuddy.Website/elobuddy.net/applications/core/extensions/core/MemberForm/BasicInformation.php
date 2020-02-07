<?php
/**
 * @brief		Admin CP Member Form
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		08 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\MemberForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Member Form
 */
class _BasicInformation
{
	/**
	 * Action Buttons
	 *
	 * @param	\IPS\Member	$member	The Member
	 * @return	array
	 */
	public function actionButtons( $member )
	{
		$return = array();

		$return['view'] = array(
			'title'		=> 'profile_view_profile',
			'icon'		=> 'search',
			'link'		=> $member->url(),
			'class'		=> '',
			'target'    => '_blank'
		);

		if ( \IPS\Member::loggedIn()->member_id != $member->member_id AND \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_login' ) AND !$member->isBanned() )
		{
			$return['login'] = array(
				'title'		=> 'login',
				'icon'		=> 'key',
				'link'		=> \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=login&id={$member->member_id}" ),
				'class'		=> '',
				'target'    => '_blank'
			);
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit_admin' ) or !$member->isAdmin() ) AND $member->member_id != \IPS\Member::loggedIn()->member_id )
		{
			$return['spam'] = array(
				'title'		=> $member->members_bitoptions['bw_is_spammer'] ? 'spam_unflag' : 'spam_flag',
				'icon'		=> 'flag',
				'link'		=>  \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=spam&id=' )->setQueryString( array( 'id' => $member->member_id, 'status' => $member->members_bitoptions['bw_is_spammer'] ? 0 : 1 ) ),
				'class'		=> ''
			);
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'members_merge' ) )
		{
			$return['merge'] = array(
				'title'		=> 'merge',
				'icon'		=> 'level-up',
				'link'		=> \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=merge&id={$member->member_id}" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('merge') )
			);
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_delete' ) )
		{
			$return['delete_content'] = array(
				'title'		=> 'member_delete_content',
				'icon'		=> 'trash-o',
				'link'		=> \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=deleteContent&id={$member->member_id}" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('member_delete_content') )
			);
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_ban' ) and ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_ban_admin' ) or !$member->isAdmin() ) AND $member->member_id != \IPS\Member::loggedIn()->member_id )
		{
			$return['ban'] = array(
				'title'		=> $member->temp_ban ? 'adjust_ban' : 'ban',
				'icon'		=> 'times',
				'link'		=> \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=ban&id={$member->member_id}" ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => $member->temp_ban ? \IPS\Member::loggedIn()->language()->addToStack('adjust_ban') : \IPS\Member::loggedIn()->language()->addToStack('ban') )
			);
		}
		
		return $return;
	}
	
	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member				$member	Existing Member
	 * @return	void
	 */
	public function process( &$form, $member )
	{
		/* Username */
		$form->addHeader('member_basic_information');
		$form->add( new \IPS\Helpers\Form\Text( 'ips_member_name', $member->name, TRUE, array( 'accountUsername' => $member ), NULL, NULL, '<a data-ipsDialog data-ipsDialog-title="' . \IPS\Member::loggedIn()->language()->addToStack( 'dname_history' ) . '" data-ipsDialog-url="' . \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=viewDnameHistory&id={$member->member_id}" ) . '" href="' . \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=viewDnameHistory&id={$member->member_id}" ) . '">' . \IPS\Member::loggedIn()->language()->addToStack( 'view_username_history' ) . '</a>' ) );
		
		/* Password */
		$form->add( new \IPS\Helpers\Form\Custom( 'password', NULL, FALSE, array(
			'getHtml'	=> function( $element ) use ( $member )
			{
				return \IPS\Theme::i()->getTemplate('members')->changePassword( $element->name, $member->member_id );
			}
		) ) );
		
		/* Email */
		$form->add( new \IPS\Helpers\Form\Email( 'ips_address_mail', $member->email, TRUE, array( 'accountEmail' => $member ) ) );
		
		/* Group - if this member is an admin, we need the "Can move admins into other groups" restriction */
		if ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin1' ) )
		{
			/* If we are editing ourselves, we can only move ourselves into a group with the same restrictions as what we have now... */
			if ( $member->member_id == \IPS\Member::loggedIn()->member_id )
			{
				/* Get the row... */
				try
				{
					$currentRestrictions = \IPS\Db::i()->select( 'row_perm_cache', 'core_admin_permission_rows', array( 'row_id=? AND row_id_type=?', $member->member_group_id, 'group' ) )->first();
					$availableGroups = array();
					foreach( \IPS\Db::i()->select( 'row_id', 'core_admin_permission_rows', array( 'row_perm_cache=? AND row_id_type=?', $currentRestrictions, 'group' ) ) AS $groupId )
					{
						$availableGroups[ $groupId ] = \IPS\Member\Group::load( $groupId );
					}
				}
				/* If we don't have a row in core_admin_permission_rows, we're an admin as a member rather than apart of our group, so we can be moved anywhere and it won't matter because member-level restrictions override group-level */
				catch ( \UnderflowException $e )
				{
					$availableGroups = \IPS\Member\Group::groups( TRUE, FALSE );
				}
			}
			/* Not editing ourselves - do we have the Can move members into admin groups"" restriction? */
			else
			{
				$availableGroups = \IPS\Member\Group::groups( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' ), FALSE );
			}
			
			$form->add( new \IPS\Helpers\Form\Select( 'group', $member->member_group_id, TRUE, array( 'options' => $availableGroups, 'parse' => 'normal' ) ) );
			$form->add( new \IPS\Helpers\Form\Select( 'secondary_groups', array_filter( explode( ',', $member->mgroup_others ) ), FALSE, array( 'options' => \IPS\Member\Group::groups( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin2' ), FALSE ), 'multiple' => TRUE, 'parse' => 'normal' ) ) );
		}
		
		/* Counts */
		$confirmButtons = json_encode( array(
			'yes'		=>	\IPS\Member::loggedIn()->language()->addToStack('yes'),
			'no'		=>	\IPS\Member::loggedIn()->language()->addToStack('recount_all'),
			'cancel'	=>	\IPS\Member::loggedIn()->language()->addToStack('cancel'),
		) );
		$form->addHeader('member_counts');
		$form->add( new \IPS\Helpers\Form\Number( 'member_content_items', $member->member_posts, FALSE, array(), NULL, NULL, '<a data-confirm data-confirmType="verify" data-confirmButtons=\'' . $confirmButtons . '\' data-confirmSubMessage="' . \IPS\Member::loggedIn()->language()->addToStack('member_content_items_recount') . '" href="' . \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=recountContent&id={$member->member_id}") . '">' . \IPS\Member::loggedIn()->language()->addToStack('recount') . '</a>' ) );
		if ( \IPS\Settings::i()->reputation_enabled )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'member_reputation', $member->pp_reputation_points, FALSE, array( 'min' => NULL ), NULL, NULL, '<a data-confirm data-confirmType="verify" data-confirmButtons=\'' . $confirmButtons . '\' data-confirmSubMessage="' . \IPS\Member::loggedIn()->language()->addToStack('member_reputation_recount') . '" href="' . \IPS\Http\Url::internal( "app=core&module=members&controller=members&do=recountReputation&id={$member->member_id}") . '">' . \IPS\Member::loggedIn()->language()->addToStack('recount') . '</a>' ) );
		}
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member			$member	The member
	 * @return	void
	 */
	public function save( $values, &$member )
	{
		if ( $values['ips_member_name'] != $member->name )
		{
			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				try
				{
					$handler->changeUsername( $member, $member->name, $values['ips_member_name'] );
				}
				catch( \BadMethodCallException $e ) {}
			}
		}
		
		if ( $values['ips_address_mail'] != $member->email )
		{
			$oldEmail = $member->email;
			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				try
				{
					$handler->changeEmail( $member, $oldEmail, $values['ips_address_mail'] );
				}
				catch( \BadMethodCallException $e ) {}
			}
		}
		
		if ( !$member->isAdmin() or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_move_admin1' ) )
		{
			$member->member_group_id		= $values['group'];
			$member->mgroup_others			= implode( ',', $values['secondary_groups'] );
		}
		$member->member_posts					= $values['member_content_items'];
		if ( \IPS\Settings::i()->reputation_enabled )
		{
			$member->pp_reputation_points	= $values['member_reputation'];
		}
		
		if ( $values['password'] )
		{
			$member->members_pass_salt	= $member->generateSalt();
			$member->members_pass_hash	= $member->encryptedPassword( $values['password'] );
			$member->member_login_key	= '';
		}
	}
}