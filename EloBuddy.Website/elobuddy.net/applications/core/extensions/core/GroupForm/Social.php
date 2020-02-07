<?php
/**
 * @brief		Group Form: Core: Social
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\GroupForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Form: Core: Social
 */
class _Social
{
	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member\Group		$group	Existing Group
	 * @return	void
	 */
	public function process( &$form, $group )
	{
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			/* Profiles */
			if ( $group->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) )
			{
				$form->addHeader( 'group_profiles' );
				$form->add( new \IPS\Helpers\Form\YesNo( 'g_edit_profile', $group->g_id ? $group->g_edit_profile : 1, FALSE, array( 'togglesOn' => array( 'gbw_allow_upload_bgimage', 'g_photo_max_vars_size' ) ) ) );
				$photos = ( $group->g_id ? explode( ':', $group->g_photo_max_vars ) : array( 50, 150, 150 ) );
				$form->add( new \IPS\Helpers\Form\Number( 'g_photo_max_vars_size', $photos[0], FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'g_photo_max_vars_none', 'unlimitedToggleOn' => FALSE, 'unlimitedToggles' => array( 'g_photo_max_vars_wh' ) ), NULL, NULL, 'kB', 'g_photo_max_vars_size' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'g_photo_max_vars_wh', $photos[1], FALSE, array(), NULL, NULL, 'px', 'g_photo_max_vars_wh' ) );
				$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_allow_upload_bgimage', $group->g_id ? ( $group->g_bitoptions['gbw_allow_upload_bgimage'] ) : TRUE, FALSE, array( 'togglesOn' => array( 'g_max_bgimg_upload' ) ), NULL, NULL, NULL, 'gbw_allow_upload_bgimage' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'g_max_bgimg_upload', $group->g_id ? $group->g_max_bgimg_upload : -1, FALSE, array( 'unlimited' => -1 ), function( $value ) {
					if( !$value )
					{
						throw new \InvalidArgumentException('form_required');
					}
				}, NULL, 'kB', 'g_max_bgimg_upload' ) );
			}
			
			/* Personal Conversations */
			if ( $group->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) )
			{
				$form->addHeader( 'personal_conversations' );
				$form->add( new \IPS\Helpers\Form\Number( 'g_pm_perday', $group->g_pm_perday, FALSE, array( 'unlimited' => -1, 'min' => 0 ), NULL, NULL, NULL, 'g_pm_perday' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'g_pm_flood_mins', $group->g_pm_flood_mins, FALSE, array( 'unlimited' => -1, 'min' => 0 ), NULL, NULL, NULL, 'g_pm_flood_mins' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'g_max_mass_pm', $group->g_max_mass_pm, FALSE, array( 'unlimited' => -1, 'max' => 500, 'min' => 0 ), NULL, NULL, NULL, 'g_max_mass_pm' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'g_max_messages', $group->g_max_messages, FALSE, array( 'unlimited' => -1, 'min' => 0 ), NULL, NULL, NULL, 'g_max_messages' ) );
				if ( \IPS\Settings::i()->attach_allowed_types != 'none' )
				{
					$form->add( new \IPS\Helpers\Form\YesNo( 'g_can_msg_attach', $group->g_can_msg_attach, FALSE, array(), NULL, NULL, NULL, 'g_can_msg_attach' ) );
				}
				$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_pm_override_inbox_full', $group->g_id ? ( $group->g_bitoptions['gbw_pm_override_inbox_full'] ) : TRUE ) );
			}
		}
		
		/* Reputation */
		if ( \IPS\Settings::i()->reputation_enabled )
		{
			$form->addHeader( 'reputation' );
		
			if( $group->g_id != \IPS\Settings::i()->guest_group )
			{
				if ( in_array( \IPS\Settings::i()->reputation_point_types, array( 'like', 'both', 'positive' ) ) )
				{
					$form->add( new \IPS\Helpers\Form\Number( 'g_rep_max_positive', $group->g_rep_max_positive, FALSE, array( 'unlimited' => 999, ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('points_per_day') ) );
				}
				if ( in_array( \IPS\Settings::i()->reputation_point_types , array( 'both', 'negative' ) ) )
				{
					$form->add( new \IPS\Helpers\Form\Number( 'g_rep_max_negative', $group->g_rep_max_negative, FALSE, array( 'unlimited' => 999, ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('points_per_day') ) );
				}
			}
			
			$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_view_reps', $group->g_id ? ( $group->g_bitoptions['gbw_view_reps'] ) : TRUE ) );
		}
		
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			/* Status Updates */
			if ( $group->canAccessModule( \IPS\Application\Module::load( 'members', 'sys_module_key', array( 'sys_module_application=? AND sys_module_area=?', 'core', 'front' ) ) ) )
			{
				$form->addHeader( 'status_updates' );
				$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_no_status_update', !$group->g_bitoptions['gbw_no_status_update'] ) );
				$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_no_status_import', !$group->g_bitoptions['gbw_no_status_import'] ) );
			}
		}
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member\Group	$group	The group
	 * @return	void
	 */
	public function save( $values, &$group )
	{
		/* Init */
		$bwKeys	= array();
		$keys	= array();

		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			/* Profiles */
			if ( $group->canAccessModule( \IPS\Application\Module::load( 'members', 'sys_module_key', array( 'sys_module_application=? AND sys_module_area=?', 'core', 'front' ) ) ) )
			{
				$bwKeys[]	= 'gbw_allow_upload_bgimage';
				$keys		= array_merge( $keys, array( 'g_edit_profile', 'g_max_bgimg_upload' ) );
	
				/* Photos */
				$group->g_photo_max_vars = implode( ':', array( $values['g_photo_max_vars_size'], $values['g_photo_max_vars_wh'], $values['g_photo_max_vars_wh'] ) );
			}
	
			/* Status updates */
			if ( $group->canAccessModule( \IPS\Application\Module::load( 'members', 'sys_module_key', array( 'sys_module_application=? AND sys_module_area=?', 'core', 'front' ) ) ) )
			{
				$values['gbw_no_status_update'] = !$values['gbw_no_status_update'];
				$values['gbw_no_status_import'] = !$values['gbw_no_status_import'];
	
				$bwKeys[] = 'gbw_no_status_import';
				$bwKeys[] = 'gbw_no_status_update';
			}
			else
			{
				unset( $values['gbw_no_status_update'], $values['gbw_no_status_import'] );
			}
	
			/* Personal messages */
			if ( $group->canAccessModule( \IPS\Application\Module::get( 'core', 'messaging', 'front' ) ) )
			{
				$bwKeys[]	= 'gbw_pm_override_inbox_full';
				$keys		= array_merge( $keys, array( 'g_pm_perday', 'g_pm_flood_mins', 'g_max_mass_pm', 'g_max_messages', 'g_can_msg_attach', 'g_max_notifications' ) );
			}
		}
		
		/* Reputation */
		if ( \IPS\Settings::i()->reputation_enabled )
		{
			if ( !in_array( $group->g_id, explode( ',', \IPS\Settings::i()->reputation_protected_groups ) ) )
			{
				$bwKeys[] = 'gbw_view_reps';
			}

			if( $group->g_id != \IPS\Settings::i()->guest_group )
			{
				switch ( \IPS\Settings::i()->reputation_point_types )
				{
					case 'like':
					case 'positive':
						$keys[] = 'g_rep_max_positive';
						break;
						
					case 'both':
						$keys[] = 'g_rep_max_positive';
						$keys[] = 'g_rep_max_negative';
						break;
									
					case 'negative':
						$keys[] = 'g_rep_max_negative';
						break;
				}
			}
		}

		/* Store bitwise options */
		foreach ( $bwKeys as $k )
		{
			$group->g_bitoptions[ $k ] = $values[ $k ];
		}

		/* Store other options */
		foreach ( $keys as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$group->$k = $values[ $k ];
			}
		}
	}
}