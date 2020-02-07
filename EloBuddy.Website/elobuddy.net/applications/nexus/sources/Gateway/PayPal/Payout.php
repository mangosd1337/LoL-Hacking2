<?php
/**
 * @brief		PayPal Pay Out Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		7 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\PayPal;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Pay Out Gateway
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
		
		$return = array();
		$return[] = new \IPS\Helpers\Form\Text( 'paypal_api_username', isset( $settings['PayPal'] ) ? $settings['PayPal']['api_username'] : '' );
		$return[] = new \IPS\Helpers\Form\Text( 'paypal_api_password', isset( $settings['PayPal'] ) ? $settings['PayPal']['api_password'] : '' );
		$return[] = new \IPS\Helpers\Form\Text( 'paypal_api_signature', isset( $settings['PayPal'] ) ? $settings['PayPal']['api_signature'] : '' );
		return $return;
	}
	
	/**
	 * Payout Form
	 *
	 * @return	array
	 */
	public static function form()
	{		
		$return = array();
		$return[] = new \IPS\Helpers\Form\Email( 'paypal_email', \IPS\Member::loggedIn()->email, NULL, array(), function( $val )
		{
			if ( !$val and \IPS\Request::i()->withdraw_method === 'PayPal' )
			{
				throw new \DomainException('form_required');
			}
		} );
		return $return;
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
		return $values['paypal_email'];
	}
	
	/** 
	 * Process
	 *
	 * @return	void
	 * @throws	\Exception
	 */
	public function process()
	{
		$settings = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
				
		$response = \IPS\Http\Url::external( \IPS\NEXUS_TEST_GATEWAYS ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp' )->request()->post( array(
			'METHOD'		=> 'MassPay',
			'VERSION'		=> '90',
			'USER'			=> $settings['PayPal']['api_username'],
			'PWD'			=> $settings['PayPal']['api_password'],
			'SIGNATURE'		=> $settings['PayPal']['api_signature'],
			'RECEIVERTYPE'	=> 'EmailAddress',
			'CURRENCYCODE'	=> $this->amount->currency,
			'L_EMAIL0'		=> $this->data,
			'L_AMT0'		=> $this->amount->amountAsString()
		) )->decodeQueryString();

		if ( $response['ACK'] != 'Success' )
		{
			throw new \DomainException( $response['L_LONGMESSAGE0'] );
		}
		else
		{			
			$this->status = static::STATUS_COMPLETE;
			$this->completed = new \IPS\DateTime;
			$this->save();
		}
	}
	
	/** 
	 * Mass Process
	 *
	 * @param	\IPS\Patterns\ActiveRecordIterator
	 * @return	void
	 * @throws	\Exception
	 */
	public static function massProcess( \IPS\Patterns\ActiveRecordIterator $payouts )
	{
		$settings = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );

		$byCurrency = array();
		foreach ( $payouts as $payout )
		{
			$byCurrency[ $payout->amount->currency ][] = array( 'email' => $payout->data, 'amount' => $payout->amount->amountAsString() );
		}
		
		$batches = array();
		foreach ( $byCurrency as $currency => $recipients )
		{
			$baseData = array(
				'METHOD'		=> 'MassPay',
				'VERSION'		=> '90',
				'USER'			=> $settings['PayPal']['api_username'],
				'PWD'			=> $settings['PayPal']['api_password'],
				'SIGNATURE'		=> $settings['PayPal']['api_signature'],
				'RECEIVERTYPE'	=> 'EmailAddress',
				'CURRENCYCODE'	=> $currency,
			);
			
			$data = $baseData;
			foreach ( $recipients as $i => $recipient )
			{
				$data["L_EMAIL{$i}"] = $recipient['email'];
				$data["L_AMT{$i}"] = $recipient['amount'];
				
				if ( $i >= 249 )
				{
					$batches[] = $data;
					$data = $baseData;
				}
			}
			
			$batches[] = $data;
		}
		
		$error = NULL;
		foreach ( $batches as $batchData )
		{
			$response = \IPS\Http\Url::external( \IPS\NEXUS_TEST_GATEWAYS ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp' )->request()->post( $batchData )->decodeQueryString();
	
			if ( $response['ACK'] != 'Success' )
			{
				$error = $response['L_LONGMESSAGE0'];
			}
		}
		
		if ( $error )
		{
			throw new \DomainException( $error );
		}
	}
}