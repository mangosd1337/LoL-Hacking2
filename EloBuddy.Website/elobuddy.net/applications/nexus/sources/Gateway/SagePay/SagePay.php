<?php
/**
 * @brief		SagePay Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Mar 2014
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
 * SagePay Gateway
 */
class _SagePay extends \IPS\nexus\Gateway
{
	/* !Features (Each gateway will override) */

	const SUPPORTS_REFUNDS = TRUE;
	const SUPPORTS_PARTIAL_REFUNDS = TRUE;
	
	/**
	 * Can store cards?
	 *
	 * @return	bool
	 */
	public function canStoreCards()
	{
		return FALSE;
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @return	bool
	 */
	public function canAdminCharge()
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( $settings['method'] === 'direct' );
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
		$return = array();
		$settings = json_decode( $this->settings, TRUE );
		
		if ( $settings['method'] === 'direct' )
		{
			$options = array( 'member' => $member );
			$options['types'] = array( \IPS\nexus\CreditCard::TYPE_VISA, \IPS\nexus\CreditCard::TYPE_MASTERCARD, \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS, \IPS\nexus\CreditCard::TYPE_DINERS_CLUB, \IPS\nexus\CreditCard::TYPE_DISCOVER, \IPS\nexus\CreditCard::TYPE_JCB );
			$return['card'] = new \IPS\nexus\Form\CreditCard( $this->id . '_card', NULL, FALSE, $options );
		}
		
		return $return;
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
		/* Init */
		$transaction->save();
		$settings = json_decode( $this->settings, TRUE );
		
		/* Build basic data */
		$shipAddress = $transaction->invoice->shipaddress ?: $transaction->invoice->billaddress;
		$data = array(
			'TxType'			=> 'PAYMENT',
			'VendorTxCode'		=> $transaction->id,
			'Amount'			=> $transaction->amount->amount,
			'Currency'			=> $transaction->amount->currency,
			'Description'		=> mb_substr( $transaction->invoice->title, 0, 100 ),
			'BillingSurname'	=> $transaction->member->cm_last_name,
			'BillingFirstnames'	=> $transaction->member->cm_first_name,
			'BillingAddress1'	=> $transaction->invoice->billaddress->addressLines[0],
			'BillingAddress2'	=> isset( $transaction->invoice->billaddress->addressLines[1] ) ? $transaction->invoice->billaddress->addressLines[0] : NULL,
			'BillingCity'		=> $transaction->invoice->billaddress->city,
			'BillingPostCode'	=> $transaction->invoice->billaddress->postalCode,
			'BillingCountry'	=> $transaction->invoice->billaddress->country,
			'BillingState'		=> ( $transaction->invoice->billaddress->country === 'US' ) ? $transaction->invoice->billaddress->region : NULL,
			'BillingPhone'		=> $transaction->member->cm_phone,
			'DeliverySurname'	=> $transaction->member->cm_last_name,
			'DeliveryFirstnames'=> $transaction->member->cm_first_name,
			'DeliveryAddress1'	=> $shipAddress->addressLines[0],
			'DeliveryAddress2'	=> isset( $shipAddress->addressLines[1] ) ? $shipAddress->addressLines[0] : NULL,
			'DeliveryCity'		=> $shipAddress->city,
			'DeliveryPostCode'	=> $shipAddress->postalCode,
			'DeliveryCountry'	=> $shipAddress->country,
			'DeliveryState'		=> ( $shipAddress->country === 'US' ) ? $shipAddress->region : NULL,
			'DeliveryPhone'		=> $transaction->member->cm_phone,
			'CustomerEMail'		=> $transaction->member->email,
		);
		
		/* Add method-specific data */
		if ( $settings['method'] === 'direct' )
		{
			$data['ClientIPAddress'] = \IPS\Request::i()->ipAddress();
			
			$card = $values[ $this->id . '_card' ];			
			$data['CardHolder']	= $transaction->member->cm_first_name . ' ' . $transaction->member->cm_last_name;
			$data['CardNumber']	= $card->number;
			$data['ExpiryDate']	= $card->expMonth . mb_substr( $card->expYear, 2 );
			$data['CV2']		= $card->ccv;
			$data['CardType']	= static::cardType( $card );
		}
		else
		{
			$data['NotificationURL'] = \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/sagepay.php';
		}
								
		/* Add Basket XML */
		if ( $transaction->amount->amount == $transaction->invoice->total->amount )
		{
			$summary = $transaction->invoice->summary();

			$basketXml = \IPS\Xml\SimpleXml::create( 'basket' );
			$discounts = array();
			
			foreach ( $summary['items'] as $item )
			{
				if ( $item->price->amount < 0 )
				{
					$discounts[] = array(
						'fixed'	=> abs( $item->price->amount * $item->quantity )
					);
				}
				else
				{				
					$basketXml->addChild( 'item', array(
						'description'		=> $item->name,
						'quantity'			=> $item->quantity,
						'unitNetAmount'		=> $item->price->amount,
						'unitTaxAmount'		=> $item->price->amount * $item->taxRate(),
						'unitGrossAmount'	=> $item->price->amount + ( $item->price->amount * $item->taxRate() ),
						'totalGrossAmount'	=> $item->grossLinePrice()->amount,
					) );
				}
			}
			
			$shippingNet = 0;
			$shippingTax = 0;
			foreach ( $summary['shipping'] as $shipping )
			{
				$shippingNet += $shipping->price->amount;
				$shippingTax += ( $shipping->price->amount * $shipping->taxRate() );
			}
			if ( $shippingNet )
			{
				$basketXml->addChild( 'deliveryNetAmount', $shippingNet );
				$basketXml->addChild( 'deliveryTaxAmount', $shippingTax );
				$basketXml->addChild( 'deliveryGrossAmount', $shippingNet + $shippingTax );
			}
			
			if ( count( $discounts ) )
			{
				$basketXml->addChild( 'discounts', $discounts );
			}
			
			$data['BasketXML'] = trim( str_replace( '<?xml version="1.0" encoding="UTF-8"?>', '', $basketXml->asXml() ) );
		}
		
		/* Send */
		try
		{
			if ( $settings['method'] === 'direct' )
			{
				$response = $this->api( 'vspdirect-register.vsp', $data );
				
				if ( isset( $data['CreateToken'] ) and isset( $response['Token'] ) and $response['Token'] )
				{
					echo 'xx<pre>';
					print_r( $response );
					exit;
				}
				
				echo 'zz<pre>';
				print_r( $response );
				exit;
			}
			else
			{
				$response = $this->api( 'vspserver-register.vsp', $data );
			}
		}
		catch ( \DomainException $e )
		{
			echo '<pre>';
			print_r( $e );
			exit;
			throw new \DomainException( $transaction->member->language()->addToStack('gateway_err') );
		}
				
		/* Save details */
		$transaction->gw_id = $response['VPSTxId'];
		$extra = $transaction->extra;
		$extra['sagepaySecurityKey'] = $response['SecurityKey'];
		$transaction->extra = $extra;
		$transaction->save();
		
		/* Continue */
		if ( $settings['method'] === 'direct' )
		{
			return NULL;
		}
		else
		{
			\IPS\Output::i()->redirect( $response['NextURL'] . '=' . $response['VPSTxId'] );
		}
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
		$refundId = $transaction->id . 'R' . rand( 1, 999 );
		
		$extra = $transaction->extra;
		$response = $this->api( 'refund.vsp', array(
			'TxType'				=> 'REFUND',
			'VendorTxCode'			=> $refundId,
			'Amount'				=> $amount ?: $transaction->amount->amount,
			'Currency'				=> $transaction->currency,
			'Description'			=> $transaction->invoice->title,
			'RelatedVPSTxId'		=> $transaction->gw_id,
			'RelatedVendorTxCode'	=> $transaction->id,
			'RelatedSecurityKey'	=> $extra['sagepaySecurityKey'],
			'RelatedTxAuthNo'		=> $extra['sagepayAuth'],
			
		) );
		
		return $response['VPSTxId'];
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
		$form->add( new \IPS\Helpers\Form\Text( 'sagepay_vendor', $settings ? $settings['vendor'] : '', TRUE ) );
		\IPS\Member::loggedIn()->language()->words['sagepay_vendor_desc'] = sprintf( \IPS\Member::loggedIn()->language()->get( 'sagepay_vendor_desc' ), gethostbyname( gethostname() ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'sagepay_method', $settings ? $settings['method'] : 'server', TRUE, array( 'options' => array( 'server' => 'sagepay_method_server', 'direct' => 'sagepay_method_direct' ) ) ) );
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
		return $settings;
	}
	
	/* !Utility Methods */
	
	/**
	 * Get card type
	 *
	 * @param	\IPS\nexus\CreditCard	$card	The card
	 * @return	string
	 */
	public static function cardType( \IPS\nexus\CreditCard $card )
	{
		switch ( $card->type )
		{
			case \IPS\nexus\CreditCard::TYPE_VISA:
				if ( preg_match( '/^(400626|40854749|40940002|41228586|41373337|41378788|418760|41917679|419772|420672|42159294|422793|423769|431072|444001|44400508|44620011|44621354|44625772|44627483|446286|446294|450875|45397|454313|45443235|454742|45672545|46583079|46590150|47511059|47571059|47622069|47634089|48440910|484427|49096079|49218182)/', $card->number ) )
				{
					return 'DELTA';
				}
				elseif ( preg_match( '/^(400(115|121|83[7-9])|401773|4026|405670|410654|41292[1-3]|4140(49|99)|415045|416(039|451|896)|417(7|935)|4197(4[0-1]|7[3-6])|420796|429158|431262|4329(19|37)|433445|435225|44(0(626|753)|615[578]|8360)|4508|453904|479(056|731)|4844(0[6-9]|1[013489]|2[0-68-9]|[3-5][0-9])|491(3|7([3-5][0-9])|859)|4935|494114)
/', $card->number ) )
				{
					return 'UKE';
				}
				else
				{
					return 'VISA';
				}
				
				break;
			
			case \IPS\nexus\CreditCard::TYPE_MASTERCARD:
				if ( preg_match( '/^(?:5[0678]\d\d|6304|6390|67\d\d)\d{8,15}$/', $card->number ) )
				{
					return 'MAESTRO';
				}
				elseif ( in_array( mb_substr( $card->number, 0, 6 ), array( '516730', '516979', '517000', '517049', '535110', '535309', '535420', '535819', '537210', '537609', '557347', '557496', '557498', '557547' ) ) )
				{
					return 'MCDEBIT';
				}
				else
				{
					return 'MC';
				}
				break;
			
			case \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS:
				return 'AMEX';
				break;
			
			case \IPS\nexus\CreditCard::TYPE_DINERS_CLUB:
			case \IPS\nexus\CreditCard::TYPE_DISCOVER:
				return 'DC';
				break;
				
			case \IPS\nexus\CreditCard::TYPE_JCB:
				return 'JCB';
				break;
		}
		
		throw new \UnexpectedValueException;
	}
	
	/**
	 * Send API Request
	 *
	 * @param	string	$uri	The API to request (e.g. "payments/payment")
	 * @param	array	$data	The data to send
	 * @return	array
	 * @throws	\IPS\Http|Exception
	 */
	public function api( $uri, $data=NULL )
	{
		$settings = json_decode( $this->settings, TRUE );
		$data = array_merge( array(
			'VPSProtocol'	=> '3.00',
			'Vendor'		=> $settings['vendor'],
		), $data );
		
		$_response = \IPS\Http\Url::external( \IPS\NEXUS_TEST_GATEWAYS ? 'https://test.sagepay.com/gateway/service/' . $uri : 'https://live.sagepay.com/gateway/service/' . $uri )->request()->post( $data );
		$response = array();
		foreach ( explode( "\n", $_response ) as $line )
		{
			$line = trim( $line );
			if ( $line )
			{
				$ex = explode( '=', $line );
				$response[ $ex[0] ] = $ex[1];
			}
		}
		
		if ( !isset( $response['Status'] ) or $response['Status'] !== 'OK' )
		{
			throw new \DomainException( $response['StatusDetail'] );
		}
		
		return $response;
	}
}