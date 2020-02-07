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
class _Profile
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
		/* Profile Preferences */
		$form->addHeader('member_preferences_profile');
		$form->add( new \IPS\Helpers\Form\Upload( 'pp_cover_photo', $member->pp_cover_photo ? \IPS\File::get( 'core_Profile', $member->pp_cover_photo ) : NULL, FALSE, array( 'storageExtension' => 'core_Profile', 'image' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'member_title', $member->member_title, FALSE, array( 'maxLength' => 64 ) ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'bday', array( 'year' => $member->bday_year, 'month' => $member->bday_month, 'day' => $member->bday_day ), FALSE, array( 'getHtml' => function( $element )
		{
			return strtr( \IPS\Member::loggedIn()->language()->preferredDateFormat(), array(
				'dd'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_day( $element->name, $element->value, $element->error ),
				'mm'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_month( $element->name, $element->value, $element->error ),
				'yy'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
				'yyyy'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
			) );
		} ) ) );

		if ( \IPS\Settings::i()->signatures_enabled )
		{
			$form->add( new \IPS\Helpers\Form\Editor( 'signature', $member->signature, FALSE, array( 'app' => 'core', 'key' => 'Signatures', 'autoSaveKey' => "sig-{$member->member_id}", 'attachIds' => array( $member->member_id ) ) ) );
		}
		
		$form->add( new \IPS\Helpers\Form\YesNo( 'pp_setting_count_visitors', $member->members_bitoptions['pp_setting_count_visitors'], FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'pp_setting_moderate_followers', !$member->members_bitoptions['pp_setting_moderate_followers'] ) );
	
		/* Profile Fields */
		try
		{
			$values = \IPS\Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $member->member_id ) )->first();
		}
		catch( \UnderflowException $e )
		{
			$values	= array();
		}
		if( count( $values ) )
		{
			foreach ( \IPS\core\ProfileFields\Field::fields( $values, \IPS\core\ProfileFields\Field::STAFF ) as $group => $fields )
			{
				$form->addHeader( "core_pfieldgroups_{$group}" );
				foreach ( $fields as $field )
				{
					$form->add( $field );
				}
			}
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
		/* Profile Preferences */
		$member->member_title		= $values['member_title'];

		if ( $values['bday'] )
		{
			$member->bday_day	= $values['bday']['day'];
			$member->bday_month	= $values['bday']['month'];
			$member->bday_year	= $values['bday']['year'];
		}
		else
		{
			$member->bday_day = NULL;
			$member->bday_month = NULL;
			$member->bday_year = NULL;
		}
		
		if ( \IPS\Settings::i()->signatures_enabled )
		{
			$member->signature = $values['signature'];
		}
		
		$member->members_bitoptions['pp_setting_count_visitors']		= $values['pp_setting_count_visitors'];
		$member->members_bitoptions['pp_setting_moderate_followers']	= $values['pp_setting_moderate_followers'] ? FALSE : TRUE;
						
		/* Cover photo */
		if ( $values['pp_cover_photo'] )
		{
			$member->pp_cover_photo = (string) $values['pp_cover_photo'];
		}
		else
		{
			$member->pp_cover_photo = '';
		}

		/* Profile Fields */
		try
		{
			$profileFields = \IPS\Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $member->member_id ) )->first();
		}
		catch( \UnderflowException $e )
		{
			$profileFields	= array();
		}
		
		/* If \IPS\Db::i()->select()->first() has only one column, then the contents of that column is returned. We do not want this here. */
		if ( !is_array( $profileFields ) )
		{
			$profileFields = array();
		}
		
		$profileFields['member_id'] = $member->member_id;
		foreach ( \IPS\core\ProfileFields\Field::fields( $profileFields, \IPS\core\ProfileFields\Field::STAFF ) as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				$profileFields[ "field_{$id}" ] = $field::stringValue( !empty( $values[ $field->name ] ) ? $values[ $field->name ] : NULL );
			}
		}
		\IPS\Db::i()->replace( 'core_pfields_content', $profileFields );
		
		$member->changedCustomFields = $profileFields;
	}
}