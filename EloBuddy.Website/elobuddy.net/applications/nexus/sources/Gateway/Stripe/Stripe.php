<?php
/**
 * @brief		Stripe Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		13 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Gateway
 */
class _Stripe extends \IPS\nexus\Gateway
{
	/* !Features */
	
	const SUPPORTS_REFUNDS = TRUE;
	const SUPPORTS_PARTIAL_REFUNDS = TRUE;
	
	/**
	 * Check the gateway can process this...
	 *
	 * @param	$amount			\IPS\nexus\Money	The amount
	 * @param	$billingAddress	\IPS\GeoLocation	The billing address
	 * @return	bool
	 * @see		<a href="https://support.stripe.com/questions/which-currencies-does-stripe-support/">Which currencies does Stripe support?</a>
	 */
	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress )
	{
		if ( static::_amountAsCents( $amount ) < 50 )
		{
			return FALSE;
		}
		
		$settings = json_decode( $this->settings, TRUE );
		switch ( $settings['country'] )
		{
			case 'CA':
				if ( !in_array( $amount->currency, array( 'CAD', 'USD' ) ) )
				{
					return FALSE;
				}
				break;
			
			case 'AU':
				if( $amount->currency != 'AUD' )
				{
					return FALSE;
				}
				break;
			
			default:
				if ( !in_array( $amount->currency, array( 'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BWP', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EEK', 'EGP', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LTL', 'LVL', 'MAD', 'MDL', 'MGA', 'MKD', 'MNT', 'MOP', 'MRO', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SVC', 'SZL', 'THB', 'TJS', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYI', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW' ) ) )
				{
					return FALSE;
				}
				break;
		}
		
		return parent::checkValidity( $amount, $billingAddress );
	}
	
	/**
	 * Can store cards?
	 *
	 * @return	bool
	 */
	public function canStoreCards()
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( $settings['cards'] );
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @return	bool
	 */
	public function canAdminCharge()
	{
		return TRUE;
	}
	
	/* !Payment Gateway */
	
	/**
	 * Payment Screen Fields
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	Invoice
	 * @param	\IPS\nexus\Money	$amount		The amount to pay now
	 * @param	\IPS\Member			$member		The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param	array				$recurrings	Details about recurring costs
	 * @return	array
	 */
	public function paymentScreen( \IPS\nexus\Invoice $invoice, \IPS\nexus\Money $amount, \IPS\Member $member = NULL, $recurrings = array() )
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'global_gateways.js', 'nexus', 'global' ) );
		
		$settings = json_decode( $this->settings, TRUE );
		
		$supportedCards = array( \IPS\nexus\CreditCard::TYPE_VISA, \IPS\nexus\CreditCard::TYPE_MASTERCARD );
		if ( !in_array( $invoice->currency, array( 'AFN', 'AOA', 'ARS', 'AUD', 'BOB', 'BRL', 'CAD', 'CLP', 'COP', 'CRC', 'CVE', 'CZK', 'DJF', 'EEK', 'FKP', 'GNF', 'GTQ', 'HNL', 'INR', 'LAK', 'MUR', 'MXN', 'NIO', 'PAB', 'PEN', 'PYG', 'SHP', 'SRD', 'SVC', 'UYI', 'VEF', 'XOF', 'XPF' ) ) )
		{
			$supportedCards[] = \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS;
		}
		if ( $settings['country'] == 'US' )
		{
			$supportedCards[] = \IPS\nexus\CreditCard::TYPE_DISCOVER;
			$supportedCards[] = \IPS\nexus\CreditCard::TYPE_DINERS_CLUB;
			$supportedCards[] = \IPS\nexus\CreditCard::TYPE_JCB;
		}
		
		return array( 'card' => new \IPS\nexus\Form\CreditCard( $this->id . '_card', NULL, FALSE, array(
			'types' 		=> $supportedCards,
			'attr'			=> array(
				'data-controller'	=> 'nexus.global.gateways.stripe',
				'data-id'			=> $this->id,
				'class'				=> 'ipsHide',
				'data-key'			=> $settings['publishable_key']
			),
			'jsRequired'	=> TRUE,
			'names'			=> FALSE,
			'save'			=> ( $settings['cards'] and \IPS\Member::loggedIn()->member_id ) ? $this : NULL,
			'member'		=> $member,
		) ) );
	}
	
	/**
	 * Authorize
	 *
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	array|\IPS\nexus\Customer\CreditCard	$values			Values from form OR a stored card object if this gateway supports them
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @param	array									$recurrings		Details about recurring costs
	 * @return	\IPS\DateTime|NULL						Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException							Message will be displayed to user
	 */
	public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL, $recurrings = array() )
	{
		/* Set MaxMind type */
		if ( $maxMind )
		{
			$maxMind->setTransactionType('creditcard');
		}
		
		/* Build data */
		$data = array(
			'amount'	=> static::_amountAsCents( $transaction->amount ),
			'currency'	=> $transaction->amount->currency,
			'capture'	=> 'false'
		);
		$card = $values[ $this->id . '_card' ];
		
		/* Stored Card */
		if ( $card instanceof \IPS\nexus\Gateway\Stripe\CreditCard )
		{
			$profiles = $card->member->cm_profiles;
			$data['customer'] = $profiles[ $this->id ];
			$data['card'] = $values[ $this->id . '_card' ]->data;
		}
		
		/* New Card */
		else
		{
			/* Are we saving it? */
			$settings = json_decode( $this->settings, TRUE );
			if ( $settings['cards'] and $card->save )
			{			
				$storedCard = new \IPS\nexus\Gateway\Stripe\CreditCard;
				$storedCard->member = $transaction->member;
				$storedCard->method = $this;
				$storedCard->card = $card;
				$storedCard->save();
												
				$profiles = $transaction->member->cm_profiles;
				$data['customer'] = $profiles[ $this->id ];
				$data['card'] = $storedCard->data;
			}
			/* Nope, just use the token */
			else
			{
				$data['card'] = $card->token;
			}
		}
								
		/* Authorize */
		$response = $this->api( 'charges', $data );
		$transaction->gw_id = $response['id'];
		
		/* Return */
		return \IPS\DateTime::ts( $response['created'] )->add( new \DateInterval( 'P7D' ) );
	}
	
	/**
	 * Void
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\Exception
	 */
	public function void( \IPS\nexus\Transaction $transaction )
	{
		try
		{
			$response = $this->refund( $transaction );
		}
		catch ( \Exception $e ) { }
	}
	
	/**
	 * Capture
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\LogicException
	 */
	public function capture( \IPS\nexus\Transaction $transaction )
	{
		$this->api( "charges/{$transaction->gw_id}/capture" );
	}
	
	/**
	 * Refund
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction to be refunded
	 * @param	float|NULL				$amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @return	mixed									Gateway reference ID for refund, if applicable
	 * @throws	\Exception
 	 */
	public function refund( \IPS\nexus\Transaction $transaction, $amount = NULL )
	{
		$data = NULL;
		if ( $amount )
		{
			$data['amount'] = static::_amountAscents( new \IPS\nexus\Money( $amount, $transaction->currency ) );
		}
		
		$this->api( "charges/{$transaction->gw_id}/refund", $data );
	}
	
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function settings( &$form )
	{
		$settings = json_decode( $this->settings, TRUE );
		$form->add( new \IPS\Helpers\Form\Text( 'stripe_secret_key', $settings ? $settings['secret_key'] : NULL, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'stripe_publishable_key', $settings ? $settings['publishable_key'] : NULL, TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'stripe_cards', $settings ? $settings['cards'] : TRUE, TRUE ) );
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array	$settings	Settings
	 * @return	array
	 * @throws	\InvalidArgumentException
	 */
	public function testSettings( $settings )
	{
		try
		{
			$response = $this->api( 'account', NULL, 'get', $settings );
			$settings['country'] = $response['country'];
			return $settings;
		}
		catch ( \IPS\nexus\Gateway\Stripe\Exception $e )
		{
			throw new \InvalidArgumentException( $e->details['message'] );
		}
	}
	
	/* !Utility Methods */
	
	/**
	 * Send API Request
	 *
	 * @param	string		$uri		The API to request (e.g. "charges")
	 * @param	array		$data		The data to send
	 * @param	string		$method		Method (get/post)
	 * @param	array|NULL	$settings	Settings (NULL for saved setting)
	 * @return	array
	 * @throws	\IPS\Http|Exception
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	public function api( $uri, $data=NULL, $method='post', $settings = NULL )
	{		
		$settings = $settings ?: json_decode( $this->settings, TRUE );
		
		$response = \IPS\Http\Url::external( 'https://api.stripe.com/v1/' . $uri )
			->request( \IPS\LONG_REQUEST_TIMEOUT )
			->setHeaders( array( 'Stripe-Version' => '2014-01-31' ) )
			->login( $settings['secret_key'], '' )
			->$method( $data )
			->decodeJson();
			
		if ( isset( $response['error'] ) )
		{
			throw new \IPS\nexus\Gateway\Stripe\Exception( $response['error'] );
		}
		
		return $response;
	}
	
	/**
	 * Convert amount into cents
	 *
	 * @param	\IPS\nexus\Money	$amount		The amount
	 * @return	int
	 */
	protected static function _amountAsCents( \IPS\nexus\Money $amount )
	{
		if ( in_array( $amount->currency, array( 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VUV', 'XAF', 'XOF', 'XPF' ) ) )
		{
			return intval( (string) $amount->amount );
		}
		else
		{
			return intval( (string) $amount->amount->multiply( new \IPS\Math\Number( '100' ) ) );
		}
	}
}