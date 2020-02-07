<?php
/**
 * @brief		Group Form: Core
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
 * Group Form: Core
 */
class _GroupSettings
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
		/* Group title */
		$form->addHeader( 'group_details' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'g_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( !$group->g_id ) ? NULL : "core_group_{$group->g_id}" ) ) );
		
		/* Group Icon */
		$icon = NULL;
		if ( $group->g_icon )
		{
			try
			{
				$icon = \IPS\File::get( 'core_Theme', $group->g_icon );
			}
			catch ( \Exception $e ) { }
		}
		$form->add( new \IPS\Helpers\Form\Upload( 'g_icon', $icon, FALSE, array( 'storageExtension' => 'core_Theme', 'allowedFileTypes' => array( 'gif', 'png', 'jpeg', 'jpg' ) ) ) );

		/* Prefix/Suffix */
		$form->add( new \IPS\Helpers\Form\Custom( 'g_prefixsuffix', array( 'prefix' => $group->prefix, 'suffix' => $group->suffix ), FALSE, array(
			'getHtml'	=> function( $element )
			{
				$color = NULL;
				if ( preg_match( '/^<span style=\'color:#((?:(?:[a-f0-9]{3})|(?:[a-f0-9]{6})));\'>$/i', $element->value['prefix'], $matches ) and $element->value['suffix'] === '</span>' )
				{
					$color = $matches[1];
					$element->value['prefix'] = NULL;
					$element->value['suffix'] = NULL;
				}
								
				return \IPS\Theme::i()->getTemplate( 'members', 'core' )->prefixSuffix( $element->name, $color, $element->value['prefix'], $element->value['suffix'] );
			},
			'formatValue' => function( $element )
			{
				if ( $element->value['prefix'] or $element->value['suffix'] )
				{
					return array( 'prefix' => $element->value['prefix'], 'suffix' => $element->value['suffix'] );
				}
				elseif ( isset( $element->value['color'] ) )
				{
					$color = mb_strtolower( $element->value['color'] );
					if ( mb_substr( $color, 0, 1 ) !== '#' )
					{
						$color = '#' . $color;
					}
				
					if( !in_array( $color, array( '#fff', '#ffffff', '#000', '#000000' ) ) )
					{
						return array( 'prefix' => "<span style='color:{$color}'>", 'suffix' => '</span>' );
					}
				}
				
				return array( 'prefix' => '', 'suffix' => '' );
			}
		) ) );
		
		/* Promotion */
		$form->add( new \IPS\Helpers\Form\Custom( 'group_promotion', array_merge( array( explode( '&', $group->g_promotion ?: '-1&-1' ) ), array( $group->g_bitoptions['gbw_promote_unit_type'] ) ), FALSE, array(
			'getHtml' => function( $element )
			{
				return \IPS\Theme::i()->getTemplate( 'members' )->groupPromotion( $element->name, $element->value, \IPS\Member\Group::groups( TRUE, FALSE ) );
			},
			'formatValue' => function( $element )
			{
				$value = $element->value;
				if ( !isset( $value[0][1] ) )
				{
					$value[0][1] = -1;
				}
				if ( !isset( $value[1] ) )
				{
					$value[1] = 0;
				}
				return $value;
			}
		), NULL, NULL, NULL, 'group_promotion' ) );
		
		/* Can access site? */
		$form->addHeader( 'permissions' );
		$tabs = array();
		foreach ( \IPS\Application::allExtensions( 'core', 'GroupForm', FALSE ) as $key => $class )
		{
			if ( $key != 'core_GroupSettings' )
			{
				$tabs[] = $form->id . '_tab_group__' . $key;
			}
		}

		$form->add( new \IPS\Helpers\Form\YesNo( 'g_view_board', $group->g_id ? $group->g_view_board : TRUE, FALSE, array( 'togglesOn' => array_merge( $tabs, array( 'group_username', 'g_dohtml', 'g_use_search', "{$form->id}_header_group_privacy", 'g_hide_online_list', "{$form->id}_header_group_signatures", 'g_use_signatures', "{$form->id}_header_group_staff", 'g_access_offline', 'g_search_flood', 'gbw_cannot_be_ignored' ) ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_access_offline', $group->g_access_offline, FALSE, array(), NULL, NULL, NULL, 'g_access_offline' ) );

		/* Usernames */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->add( new \IPS\Helpers\Form\Custom( 'group_username', array( $group->g_dname_changes, $group->g_displayname_unit, $group->g_bitoptions['gbw_displayname_unit_type'], $group->g_dname_date ?: 1, 'canchange' => (bool) $group->g_dname_changes ), FALSE, array(
				'getHtml' => function( $element )
				{ 
					return \IPS\Theme::i()->getTemplate( 'members' )->usernameChanges( $element->name, $element->value );
				},
				'formatValue' => function( $element )
				{
					$value = $element->value;
					
					if ( !isset( $value['canchange'] ) )
					{
						$value[0] = 0;
					}
					
					if ( isset( $value['unlimited'] ) )
					{
						$value[0] = -1;
						$value[3] = 0;
					}
					elseif ( !isset( $value[0] ) )
					{
						$value[0] = 0;
					}
					
					if ( isset( $value['always'] ) )
					{
						$value[1] = 0;
						$value[2] = 0;
					}
													
					return $value;
				}
			), NULL, NULL, NULL, 'group_username' ) );
		}
		
		/* HTML */
		$form->add( new \IPS\Helpers\Form\YesNo( 'g_dohtml', $group->g_dohtml, FALSE, array( 'togglesOn' => array( 'g_dohtml_warning' ) ), NULL, NULL, NULL, 'g_dohtml' ) );

		/* Search */
		if ( $group->canAccessModule( \IPS\Application\Module::get( 'core', 'search', 'front' ) ) )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'g_search_flood', $group->g_search_flood, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('seconds'), 'g_search_flood' ) );
		}
		
		/* Privacy */
		if ( \IPS\Application\Module::get( 'core', 'online', 'front' )->visible )
		{
			$form->addHeader( 'group_privacy' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_hide_online_list', !$group->g_hide_online_list, FALSE, array(), NULL, NULL, NULL, 'g_hide_online_list' ) );
		}

		$form->add( new \IPS\Helpers\Form\YesNo( 'can_be_ignored', $group->g_id ? ( !$group->g_bitoptions['gbw_cannot_be_ignored'] ) : TRUE, FALSE, array(), NULL, NULL, NULL, 'gbw_cannot_be_ignored' ) );

		/* Signatures */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
			$form->addHeader( 'group_signatures' );
			$signatureLimits = ( $group->g_id ) ? explode( ':', $group->g_signature_limits ) : array( 0, '', '', '', '', '' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'g_use_signatures', !$signatureLimits[0], FALSE, array( 'togglesOn' => array( 'g_signature_limit', 'g_sig_max_images', 'g_sig_max_image_size', 'g_sig_max_urls', 'g_sig_max_lines' ) ), NULL, NULL, NULL, 'g_use_signatures' ) );
			$form->add( new \IPS\Helpers\Form\Custom( 'g_signature_limit', array( $group->g_sig_unit, $group->g_bitoptions['gbw_sig_unit_type'] ), FALSE, array( 'getHtml' => function( $element )
			{
				if( !isset( $element->value[0] ) )
				{
					$element->value[0] = 0;
				}

				if( !isset( $element->value[1] ) )
				{
					$element->value[1] = 0;
				}

				return \IPS\Theme::i()->getTemplate( 'members' )->signatureLimits( $element->name, $element->value );
			} ), NULL, NULL, NULL, 'g_signature_limit' ) );
			
			/* We need a special check for 0 here, as that indicates the user is not allowed to use images at all - the ternary condition here will always show the field as "unlimited" in this case */
			$form->add( new \IPS\Helpers\Form\Number( 'g_sig_max_images', ( $signatureLimits[1] OR (int) $signatureLimits[1] === 0 ) ? $signatureLimits[1] : '', FALSE, array( 'unlimited' => '' ), NULL, NULL, NULL, 'g_sig_max_images' ) );


			$form->add( new \IPS\Helpers\Form\WidthHeight( 'g_sig_max_image_size', array( $signatureLimits[2] ?: '', $signatureLimits[3] ?: '' ), FALSE, array( 'unlimited' => array( '', '' ) ), NULL, NULL, NULL, 'g_sig_max_image_size' ) );
			$form->add( new \IPS\Helpers\Form\Number( 'g_sig_max_urls', ( $signatureLimits[4] OR (int) $signatureLimits[4] === 0 ) ? $signatureLimits[4] : '', FALSE, array( 'unlimited' => '' ), NULL, NULL, NULL, 'g_sig_max_urls' ) );
			$form->add( new \IPS\Helpers\Form\Number( 'g_sig_max_lines', ( $signatureLimits[5] OR (int) $signatureLimits[5] === 0 ) ? $signatureLimits[5] : '', FALSE, array( 'unlimited' => '' ), NULL, NULL, NULL, 'g_sig_max_lines' ) );
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
		/* Group title */
		\IPS\Lang::saveCustom( 'core', "core_group_{$group->g_id}", $values['g_title'] );
		
		/* Group Icon */
		$group->g_icon = NULL;
		if ( $values['g_icon'] instanceof \IPS\File )
		{
			$group->g_icon = (string) $values['g_icon'];
		}
		
		/* Prefix/Suffix */
		$group->prefix = $values['g_prefixsuffix']['prefix'];
		$group->suffix = $values['g_prefixsuffix']['suffix'];
				
		/* Promotion */
		if ( isset( $values['group_promotion'] ) )
		{
			$group->g_promotion = implode( '&', $values['group_promotion'][0] );
			$group->g_bitoptions['gbw_promote_unit_type'] = $values['group_promotion'][1];
		}
		else
		{
			$group->g_promotion = '-1&-1';
		}

		/* Username changes */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
	        $group->g_dname_changes = isset( $values['group_username']['canchange'] ) ? $values['group_username'][0] : 0;
	        $group->g_displayname_unit = isset( $values['group_username']['canchange'] ) ? (int) $values['group_username'][1] : 0;
	        $group->g_bitoptions['gbw_displayname_unit_type'] = $values['group_username'][2];
	        $group->g_dname_date = isset( $values['group_username']['canchange'] ) ? $values['group_username'][3] : 1;
		}

		/* Signatures */
		if( $group->g_id != \IPS\Settings::i()->guest_group )
		{
            if( isset( $values['g_use_signatures'] ) )
			{
				$group->g_signature_limits = implode( ':', array( (int) !$values['g_use_signatures'], $values['g_sig_max_images'], $values['g_sig_max_image_size'][0], $values['g_sig_max_image_size'][1], $values['g_sig_max_urls'], $values['g_sig_max_lines'] ) );

                if( !isset( $values['g_signature_limit'][3] ) )
                {
                    $group->g_sig_unit = $values['g_signature_limit'][0];
                    $group->g_bitoptions['gbw_sig_unit_type'] = $values['g_signature_limit'][1];
                }
                else
                {
                    $group->g_sig_unit = 0;
                }
			}
			else
			{
				$group->g_signature_limits = '0:::::';
				$group->g_sig_unit = 0;
			}
		}
						
		/* Other */
		$group->g_view_board = $values['g_view_board'];
		$group->g_dohtml = $values['g_dohtml'];
		if ( isset( $values['g_search_flood'] ) )
		{
			$group->g_search_flood = $values['g_search_flood'];
		}
		if ( isset( $values['g_hide_online_list'] ) )
		{
			$group->g_hide_online_list = !$values['g_hide_online_list'];
		}
		$group->g_access_offline = $values['g_access_offline'];
		$group->g_bitoptions['gbw_cannot_be_ignored'] = !$values['can_be_ignored'];
	}
}