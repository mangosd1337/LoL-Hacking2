<?php
/**
 * @brief		Admin CP Member Form
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Apr 2013
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
class _Restrictions
{
	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member				$member	Existing Member
	 * @return	void
	 */
	public function process( &$form, $member )
	{
		/* Moderation */
		$form->addHeader( 'member_moderation' );
		if ( \IPS\Settings::i()->warn_on )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'member_warnings', $member->warn_level, FALSE ) );	
		}
		$form->add( new \IPS\Helpers\Form\Date( 'restrict_post', $member->restrict_post, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('until') ) );
		$form->add( new \IPS\Helpers\Form\Date( 'mod_posts', $member->mod_posts, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('until') ) );
		
		/* Restrictions */
		$form->addHeader( 'member_restrictions' );
		if ( \IPS\Settings::i()->tags_enabled )
		{
			if ( !$member->group['gbw_disable_tagging'] )
			{
				$form->add( new \IPS\Helpers\Form\YesNo( 'bw_disable_tagging', !$member->members_bitoptions['bw_disable_tagging'] ) );
			}
			if ( !$member->group['gbw_disable_prefixes'] )
			{
				$form->add( new \IPS\Helpers\Form\YesNo( 'bw_disable_prefixes', !$member->members_bitoptions['bw_disable_prefixes'] ) );
			}
		}

		if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) )
		{
			/* There are two columns for "can post status updates". The first is a user-level option (the user can turn status updates on and
				off in their account), and the second is an admin-level option (block user from posting status updates). Having two options is
				confusing so we merge both options into one select list which matches the PM dropdown below */
			$value = ( !$member->members_bitoptions['bw_no_status_update'] AND $member->pp_setting_count_comments ) ? 0 :
				( !$member->members_bitoptions['bw_no_status_update'] ? 1 : 2 );

			$form->add( new \IPS\Helpers\Form\Select( 'bw_no_status_update', $value, FALSE, array( 'options' => array(
				0 => 'members_disable_pm_on',
				1 => 'members_disable_pm_member_disable',
				2 => 'members_disable_pm_admin_disable',
			) ) ) );
		}

		if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) )
		{
			$form->add( new \IPS\Helpers\Form\Select( 'members_disable_pm', $member->members_disable_pm, FALSE, array( 'options' => array(
				0 => 'members_disable_pm_on',
				1 => 'members_disable_pm_member_disable',
				2 => 'members_disable_pm_admin_disable',
			) ) ) );
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
		if ( \IPS\Settings::i()->warn_on )
		{
			$member->warn_level		= $values['member_warnings'];
		}
		$member->mod_posts		= is_object( $values['mod_posts'] ) ? $values['mod_posts']->getTimestamp() : ( $values['mod_posts'] ?: 0 );
		$member->restrict_post	= is_object( $values['restrict_post'] ) ? $values['restrict_post']->getTimestamp() : ( $values['restrict_post'] ?: 0 );
		
		if ( \IPS\Settings::i()->tags_enabled )
		{
			if ( !$member->group['gbw_disable_tagging'] )
			{
				$member->members_bitoptions['bw_disable_tagging'] = !$values['bw_disable_tagging'];
			}
			if ( !$member->group['gbw_disable_prefixes'] )
			{
				$member->members_bitoptions['bw_disable_prefixes'] = !$values['bw_disable_prefixes'];
			}
		}
		if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) ) )
		{
			/* We may be adding a secondary group which DOES have access - we should just use the default in this instance */
			if ( array_key_exists( 'bw_no_status_update', $values ) )
			{
				/* 0 means 'yes can post', 1 means 'no, but user can enable' and 2 means 'no and user cannot re-enable'. */
				$member->members_bitoptions['bw_no_status_update']	= ( $values['bw_no_status_update'] == 2 ) ? 1 : 0;
				$member->pp_setting_count_comments					= ( $values['bw_no_status_update'] == 0 ) ? 1 : 0;
			}
			else
			{
				$member->members_bitoptions['bw_no_status_update'] = FALSE;
			}
		}
		
		if ( $member->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) )
		{
			/* We may be adding a secondary group which DOES have access - we should just use the default in this instance */
			if ( array_key_exists( 'members_disable_pm', $values ) )
			{
				$member->members_disable_pm = $values['members_disable_pm'];
			}
			else
			{
				$member->members_disable_pm = 0;
			}
		}
	}
}