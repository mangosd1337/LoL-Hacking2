<?php
/**
 * @brief		Manual Pay Out Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		7 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\Manual;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manual Pay Out Gateway
 */
class _Payout extends \IPS\nexus\Payout
{
	/**
	 * @brief	Requires manual approval?
	 */
	public static $requiresApproval = TRUE;
	
	/**
	 * ACP Settings
	 *
	 * @return	array
	 */
	public static function settings()
	{
		$settings = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
		
		$return = array();
		$return[] = new \IPS\Helpers\Form\Text( 'manual_name', isset( $settings['Manual'] ) ? $settings['Manual']['name'] : '' );
		$return[] = new \IPS\Helpers\Form\Text( 'manual_title', isset( $settings['Manual'] ) ? $settings['Manual']['title'] : '' );
		return $return;
	}
	
	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	public static function form()
	{		
		$settings = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
		
		$field = new \IPS\Helpers\Form\TextArea( 'manual_details', NULL, NULL, array(), function( $val )
		{
			if ( !$val and \IPS\Request::i()->withdraw_method === 'Manual' )
			{
				throw new \DomainException('form_required');
			}
		} );
		$field->label = $settings['Manual']['title'];
		return array( $field );
	}
	
	/**
	 * Get data and validate
	 *
	 * @param	array	$values	Values from form
	 * @return	mixed
	 * @throws	\DomainException
	 */
	public function getData( array $values )
	{
		return $values['manual_details'];
	}
	
	/** 
	 * Process
	 *
	 * @return	void
	 * @throws	\Exception
	 */
	public function process()
	{
		$this->status = static::STATUS_COMPLETE;
		$this->completed = new \IPS\DateTime;
		$this->save();
	}
}