<?php
/**
 * @brief		Stripe Pay Out Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		7 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Pay Out Gateway
 */
class _Payout extends \IPS\nexus\Payout
{
	/**
	 * ACP Settings
	 *
	 * @return	array
	 */
	public static function settings()
	{
		$settings = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
		if ( !isset( $settings['Stripe'] ) or !count( $settings['Stripe'] ) )
		{
			try
			{
				$method = \IPS\Db::i()->select( '*', 'nexus_paymethods', array( 'm_gateway=? AND m_active=1', 'Stripe' ) )->first();
				$_settings = json_decode( $method['m_settings'], TRUE );
				$settings['Stripe']['secret_key'] = $_settings['secret_key'];
			}
			catch ( \UnderflowException $e ) { }
		}
				
		return array( new \IPS\Helpers\Form\Text( 'stripe_secret_key', ( isset( $settings['Stripe'] ) and isset( $settings['Stripe']['secret_key'] ) ) ? $settings['Stripe']['secret_key'] : NULL ) );
	}
	
	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	public static function form()
	{
		$return = array();
		
		/* Get any details we've used before */
		$previous = \IPS\Db::i()->select( 'po_data', 'nexus_payouts', array( 'po_member=? AND po_gateway=?', \IPS\Member::loggedIn()->member_id, 'Stripe' ) );
		if ( count( $previous ) )
		{
			$options = array();
			foreach ( $previous as $id )
			{
				$recipient = static::api( "recipients/{$id}", NULL, 'get' );
				$options[ $id ] = "{$recipient['active_account']['bank_name']} - XXXX{$recipient['active_account']['last4']}";
			}
			$options[0] = 'other';
			$return[] = new \IPS\Helpers\Form\Radio( 'stripe_recipient', NULL, NULL, array( 'options' => $options, 'toggles' => array( 0 => array( 'stripe_country', 'stripe_routing_number', 'stripe_account_number', 'stripe_account_type' ) ) ) );
		}
		
		/* Required Validation */
		$requiredValidation = function( $val )
		{
			if ( !$val and \IPS\Request::i()->withdraw_method === 'Stripe' and isset( \IPS\Request::i()->stripe_recipient ) and !\IPS\Request::i()->stripe_recipient )
			{
				throw new \DomainException('form_required');
			}
		};
		
		/* Build form for new */
		return array_merge( $return, array(
			'country'	=> new \IPS\Helpers\Form\Select( 'stripe_country', 'US', NULL, array( 'options' => array_map( function( $val )
			{
				return 'country-' . $val;
			}, array_combine( \IPS\GeoLocation::$countries, \IPS\GeoLocation::$countries ) ) ), NULL, NULL, NULL, 'stripe_country' ),
			'routing_number'	=> new \IPS\Helpers\Form\Text( 'stripe_routing_number', NULL, NULL, array(), $requiredValidation, NULL, NULL, 'stripe_routing_number' ),
			'account_number'	=> new \IPS\Helpers\Form\Text( 'stripe_account_number', NULL, NULL, array(), $requiredValidation, NULL, NULL, 'stripe_account_number' ),
			'account_type'		=> new \IPS\Helpers\Form\Radio( 'stripe_account_type', 'individual', NULL, array(
				'options' => array(
					'individual' => 'stripe_account_type_individual',
					'corporation' => 'stripe_account_type_corporation'
				),
				'toggles'	=> array(
					'individual' => array( 'stripe_individual_name' ),
					'corporation' => array( 'stripe_corporation_name' )
				)
			), NULL, NULL, NULL, 'stripe_account_type' ),
			'individual_name'	=> new \IPS\Helpers\Form\Text( 'stripe_individual_name', \IPS\nexus\Customer::loggedIn()->cm_name, NULL, array(), function( $val )
			{
				if ( !$val and \IPS\Request::i()->withdraw_method === 'Stripe' and isset( \IPS\Request::i()->stripe_recipient ) and !\IPS\Request::i()->stripe_recipient and \IPS\Request::i()->stripe_account_type === 'individual' )
				{
					throw new \DomainException('form_required');
				}
			}, NULL, NULL, 'stripe_individual_name' ),
			'corporation_name'	=> new \IPS\Helpers\Form\Text( 'stripe_corporation_name', NULL, NULL, array(), function( $val )
			{
				if ( !$val and \IPS\Request::i()->withdraw_method === 'Stripe' and isset( \IPS\Request::i()->stripe_recipient ) and !\IPS\Request::i()->stripe_recipient and \IPS\Request::i()->stripe_account_type === 'corporation' )
				{
					throw new \DomainException('form_required');
				}
			}, NULL, NULL, 'stripe_corporation_name' ),
		) );
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
		if ( isset( $values['stripe_recipient'] ) and $values['stripe_recipient'] )
		{
			return $values['stripe_recipient'];
		}
		
		$recipient = static::api( 'recipients', array(
			'name'			=> $values['stripe_account_type'] === 'individual' ? $values['stripe_individual_name'] : $values['stripe_corporation_name'],
			'type'			=> $values['stripe_account_type'],
			'bank_account'	=> array(
				'country'			=> $values['stripe_country'],
				'routing_number'	=> $values['stripe_routing_number'],
				'account_number'	=> $values['stripe_account_number']
			),
			'metadata'		=> array(
				'id'			=> \IPS\Member::loggedIn()->member_id
			),
		) );
				
		return $recipient['id'];	
	}
	
	/** 
	 * Process
	 *
	 * @return	void
	 * @throws	\Exception
	 */
	public function process()
	{
		$response = static::api( 'transfers', array(
			'amount'		=> (string) $this->amount->amount,
			'currency'		=> $this->amount->currency,
			'recipient'		=> $this->data
		) );
		
		$this->status = static::STATUS_COMPLETE;
		$this->completed = new \IPS\DateTime;
		$this->gw_id = $response['id'];
		$this->save();
	}
	
	/**
	 * Get recipient
	 *
	 * @return	array
	 */
	public function recipient()
	{
		return static::api( "recipients/{$this->data}", NULL, 'get' );
	}
	
	/**
	 * Send API Request
	 *
	 * @param	string	$uri	The API to request (e.g. "charges")
	 * @param	array	$data	The data to send
	 * @param	string	$method	Method (get/post)
	 * @return	array
	 * @throws	\IPS\Http|Exception
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	protected static function api( $uri, $data=NULL, $method='post' )
	{		
		$settings = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
		
		$response = \IPS\Http\Url::external( 'https://api.stripe.com/v1/' . $uri )
			->request()
			->setHeaders( array( 'Stripe-Version' => '2014-01-31' ) )
			->login( $settings['Stripe']['secret_key'], '' )
			->$method( $data )
			->decodeJson();
			
		if ( isset( $response['error'] ) )
		{
			throw new \DomainException( $response['error']['message'] );
		}
		
		return $response;
	}
}