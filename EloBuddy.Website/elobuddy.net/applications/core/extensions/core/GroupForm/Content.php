<?php
/**
 * @brief		Group Form: Core: Content
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
 * Group Form: Core: Content
 */
class _Content
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
		/* Uploading */
		if ( \IPS\Settings::i()->attach_allowed_types != 'none' )
		{
			$form->addHeader( 'uploads' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_attach', ( $group->g_attach_max != 0 ), FALSE, array( 'togglesOn' => array( 'g_attach_max', 'g_attach_per_post', 'gbw_delete_attachments' ) ) ) );
			if( $group->g_id != \IPS\Settings::i()->guest_group )
			{
				$form->add( new \IPS\Helpers\Form\Number( 'g_attach_max', $group->g_attach_max, FALSE, array( 'unlimited' => -1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack( 'filesize_raw_k' ), 'g_attach_max' ) );
			}
			$form->add( new \IPS\Helpers\Form\Number( 'g_attach_per_post', $group->g_attach_per_post, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack( 'filesize_raw_k' ), 'g_attach_per_post' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_delete_attachments', $group->g_bitoptions['gbw_delete_attachments'], FALSE, array(), NULL, NULL, NULL, 'gbw_delete_attachments' ) );
		}
		
		/* Polls */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->addHeader( 'polls' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_post_polls', $group->g_post_polls ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_vote_polls', $group->g_vote_polls ) );
		}
		
		/* Tags */
		$form->addHeader( 'tags' );

		if ( \IPS\Settings::i()->tags_enabled )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_disable_tagging', $group->g_id ? !( $group->g_bitoptions['gbw_disable_tagging'] ) : TRUE ) );
			if ( \IPS\Settings::i()->tags_can_prefix )
			{
				$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_disable_prefixes', $group->g_id ? !( $group->g_bitoptions['gbw_disable_prefixes'] ) : TRUE ) );
			}
		}
		
		/* Ratings */
		$form->addHeader('ratings');
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_topic_rate_setting', $group->g_topic_rate_setting, FALSE, array( 'togglesOn' => array( 'g_topic_rate_change' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_topic_rate_change', $group->g_topic_rate_setting == 2, FALSE, array(), NULL, NULL, NULL, 'g_topic_rate_change' ) );
		
		/* Editing */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->addHeader( 'group_editing' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_edit_posts', $group->g_edit_posts, FALSE, array( 'togglesOn' => array( 'g_edit_cutoff', 'g_append_edit' ) ) ) );
			$form->add( new \IPS\Helpers\Form\Number( 'g_edit_cutoff', $group->g_edit_cutoff, FALSE, array( 'unlimited' => 0 ), NULL, \IPS\Member::loggedIn()->language()->addToStack('g_edit_cutoff_prefix'), \IPS\Member::loggedIn()->language()->addToStack('g_edit_cutoff_suffix'), 'g_edit_cutoff' ) );
			if ( \IPS\Settings::i()->edit_log )
			{
				$form->add( new \IPS\Helpers\Form\YesNo( 'g_append_edit', $group->g_append_edit, FALSE, array(), NULL, NULL, NULL, 'g_append_edit' ) );
			}
		}
		
		/* Deleting */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->addHeader( 'group_deleting' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_soft_delete_own', $group->g_id ? ( $group->g_bitoptions['gbw_soft_delete_own'] ) : FALSE ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_delete_own_posts', $group->g_delete_own_posts ) );
		}
		
		/* Content Limits */
        if( $group->g_id != \IPS\Settings::i()->guest_group )
        {
            $form->addHeader( 'group_content_limits' );
            $form->add( new \IPS\Helpers\Form\Number( 'g_ppd_limit', $group->g_id ? $group->g_ppd_limit : 0, FALSE, array( 'unlimitedToggles' => array( 'g_ppd_unit' ), 'unlimited' => 0, 'unlimitedToggleOn' => FALSE ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('per_day') ) );
            $form->add( new \IPS\Helpers\Form\Custom( 'g_ppd_unit', array( ( $group->g_id ? $group->g_ppd_unit : 0 ), $group->g_bitoptions['gbw_ppd_unit_type'] ), FALSE, array( 'getHtml' => function( $element )
            {
                return \IPS\Theme::i()->getTemplate( 'members' )->postingLimits( $element->name, $element->value );
            } ), NULL, NULL, NULL, 'g_ppd_unit' ) );
        }
        
		/* Moderation */
		$form->addHeader( 'group_moderation' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'gbw_no_report', !$group->g_bitoptions['gbw_no_report'] ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_avoid_flood', $group->g_avoid_flood ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_avoid_q', $group->g_avoid_q, FALSE, array( 'togglesOff' => array( 'g_mod_preview' ), 'toggleValue' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_mod_preview', $group->g_mod_preview, FALSE, array( 'togglesOn' => array( 'g_mod_post_unit' ) ), NULL, NULL, NULL, 'g_mod_preview' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'g_mod_post_unit', array( $group->g_mod_post_unit ? : 0, $group->g_bitoptions['gbw_mod_post_unit_type'] ), FALSE, array( 'getHtml' => function( $element )
		{
			return \IPS\Theme::i()->getTemplate( 'members' )->moderationLimits( $element->name, $element->value );
		} ), NULL, NULL, NULL, 'g_mod_post_unit' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_bypass_badwords', $group->g_bypass_badwords ) );
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
		/* Posting limit */
        if( $group->g_id != \IPS\Settings::i()->guest_group )
        {
            $group->g_ppd_limit = $values['g_ppd_limit'];
            $group->g_ppd_unit = intval( $values['g_ppd_unit'][0] );
            $group->g_bitoptions['gbw_ppd_unit_type'] = $values['g_ppd_unit'][1];
        }
        
		/* Polls */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$group->g_post_polls = $values['g_post_polls'];
			$group->g_vote_polls = $values['g_vote_polls'];
		}
		
		/* Ratings */
		$group->g_topic_rate_setting = 0;
		if ( $values['g_topic_rate_setting'] )
		{
			if ( $values['g_topic_rate_change'] )
			{
				$group->g_topic_rate_setting = 2;
			}
			else
			{
				$group->g_topic_rate_setting = 1;
			}
		}

		/* If we can bypass the mod-queue, then the require approval setting is hidden so we need to turn that off */
		if( isset( $values['g_avoid_q'] ) AND $values['g_avoid_q'] )
		{
			$values['g_mod_preview'] = 0;
		}
		
		/* Mod Queue */
		$group->g_mod_post_unit = isset( $values['g_mod_post_unit'][2] ) ? 0 : $values['g_mod_post_unit'][0];
		$group->g_bitoptions['gbw_mod_post_unit_type'] = isset( $values['g_mod_post_unit'][2] ) ? 0 : $values['g_mod_post_unit'][1];
	
		/* Bitwise */
		$values['gbw_disable_tagging'] = \IPS\Settings::i()->tags_enabled ? !$values['gbw_disable_tagging'] : $group->g_bitoptions['gbw_disable_tagging'];
		$values['gbw_disable_prefixes'] = ( \IPS\Settings::i()->tags_enabled AND \IPS\Settings::i()->tags_can_prefix ) ? !$values['gbw_disable_prefixes'] : $group->g_bitoptions['gbw_disable_prefixes'];
		$values['gbw_no_report'] = !$values['gbw_no_report'];
		$bwKeys = array( 'gbw_disable_tagging', 'gbw_disable_prefixes', 'gbw_soft_delete_own', 'gbw_no_report', 'gbw_delete_attachments' );
		foreach ( $bwKeys as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$group->g_bitoptions[ $k ] = $values[ $k ];
			}
		}
		
		/* Other */
		if ( !$values['g_attach'] )
		{
			$values['g_attach_max'] = 0;
		}
		if ( !isset( $values['g_attach_max'] ) )
		{
			$values['g_attach_max'] = -1;
		} 
		$keys = array( 'g_attach_max', 'g_attach_per_post', 'g_edit_posts', 'g_edit_cutoff', 'g_append_edit', 'g_delete_own_posts', 'g_avoid_flood', 'g_avoid_q', 'g_mod_preview', 'g_bypass_badwords' );
		foreach ( $keys as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$group->$k = $values[ $k ];
			}
		}
	}
}