<?php
/**
 * @brief		PayPal Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
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
 * PayPal Gateway
 */
class _PayPal extends \IPS\nexus\Gateway
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
	 * @see		<a href="https://developer.paypal.com/docs/integration/direct/rest_api_payment_country_currency_support/">PayPal REST API Country & Currency Support</a>
	 */
	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress )
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( $settings['type'] === 'card' )
		{
			if ( !in_array( $amount->currency, array( 'USD', 'GBP', 'CAD', 'EUR', 'JPY' ) ) )
			{
				return FALSE;
			}
		}
		
		switch ( $amount->currency )
		{
			case 'AUD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '12500' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'BRL':
				if ( $amount->amount->compare( new \IPS\Math\Number( '20000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'CAD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '12500' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'CZK':
				if ( $amount->amount->compare( new \IPS\Math\Number( '240000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'DKK':
				if ( $amount->amount->compare( new \IPS\Math\Number( '60000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'EUR':
				if ( $amount->amount->compare( new \IPS\Math\Number( '8000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'HKD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '80000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'HUF':
				if ( $amount->amount->compare( new \IPS\Math\Number( '2000000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'ILS':
				if ( $amount->amount->compare( new \IPS\Math\Number( '40000 ' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'JPY':
				if ( $amount->amount->compare( new \IPS\Math\Number( '1000000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'MYR':
				if ( $amount->amount->compare( new \IPS\Math\Number( '40000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'MXN':
				if ( $amount->amount->compare( new \IPS\Math\Number( '110000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'TWD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '330000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'NZD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '15000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'NOK':
				if ( $amount->amount->compare( new \IPS\Math\Number( '70000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'PHP':
				if ( $amount->amount->compare( new \IPS\Math\Number( '500000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'PLN':
				if ( $amount->amount->compare( new \IPS\Math\Number( '32000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'GBP':
				if ( $amount->amount->compare( new \IPS\Math\Number( '5500' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'SGD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '16000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'SEK':
				if ( $amount->amount->compare( new \IPS\Math\Number( '80000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'CHF':
				if ( $amount->amount->compare( new \IPS\Math\Number( '13000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'THB':
				if ( $amount->amount->compare( new \IPS\Math\Number( '360000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'TRY':
				if ( $amount->amount->compare( new \IPS\Math\Number( '25000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			case 'USD':
				if ( $amount->amount->compare( new \IPS\Math\Number( '10000' ) ) !== -1 )
				{
					return FALSE;
				}
				break;
			default:
				return FALSE;	
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
		return ( isset( $settings['type'] ) and $settings['type'] === 'card' and $settings['vault'] );
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @return	bool
	 */
	public function canAdminCharge()
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( isset( $settings['type'] ) and $settings['type'] === 'card' );
	}
	
	/**
	 * Supports billing agreements?
	 *
	 * @return	bool
	 */
	public function billingAgreements()
	{
		$settings = json_decode( $this->settings, TRUE );
		return ( isset( $settings['type'] ) and $settings['type'] === 'paypal' ) and ( isset( $settings['billing_agreements'] ) and in_array( $settings['billing_agreements'], array( 'required', 'optional' ) ) );
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
		$settings = json_decode( $this->settings, TRUE );
		if ( $settings['type'] === 'card' )
		{
			return array( 'card' => new \IPS\nexus\Form\CreditCard( $this->id . '_card', NULL, FALSE, array(
				'types' 	=> array( \IPS\nexus\CreditCard::TYPE_VISA, \IPS\nexus\CreditCard::TYPE_MASTERCARD, \IPS\nexus\CreditCard::TYPE_DISCOVER, \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS ),
				'save'		=> ( $settings['vault'] and \IPS\Member::loggedIn()->member_id ) ? $this : NULL,
				'member'	=> $member
			) ) );
		}
		elseif ( $settings['billing_agreements'] == 'optional' and count( $recurrings ) == 1 )
		{
			return array( 'billing_agreement' => new \IPS\Helpers\Form\Checkbox( 'paypal_billing_agreement', TRUE, FALSE ) );
		}
		return array();
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
		/* We need a transaction ID */
		$transaction->save();
		
		/* Do it */
		$settings = json_decode( $this->settings, TRUE );
		if ( $settings['type'] === 'card' )
		{
			return $this->_cardAuth( is_array( $values ) ? $values[ $this->id . '_card' ] : $values, $transaction, $maxMind );
		}
		else
		{
			if ( count( $recurrings ) == 1 and ( $settings['billing_agreements'] == 'required' or ( $settings['billing_agreements'] == 'optional' and $values['paypal_billing_agreement'] ) ) )
			{
				foreach ( $recurrings as $recurrance )
				{
					break;
				}
				return $this->_billingAgreementAuth( $transaction, $maxMind, $recurrance['term'], $recurrance['items'] );
			}
			else
			{			
				return $this->_paypalAuth( $transaction, $maxMind );
			}
		}
	}
	
	/**
	 * Authorize Card Payment
	 *
	 * @param	\IPS\nexus\CreditCard|\IPS\nexus\Customer\CreditCard	$card	The card to charge
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @return	\IPS\DateTime|NULL		Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException			Message will be displayed to user
	 */
	protected function _cardAuth( $card, \IPS\nexus\Transaction $transaction, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL )
	{		
		/* Stored Card */	
		if ( $card instanceof \IPS\nexus\Customer\CreditCard )
		{
			$payer = array(
				'payment_method'		=> 'credit_card',
				'funding_instruments'	=> array(
					array(
						'credit_card_token'	=> array(
							'credit_card_id'	=> $card->data
						)
					)
				)
			);
		}
		/* New Card */
		else
		{
			if ( $maxMind )
			{
				$maxMind->setCard( $card );
			}
			
			switch ( $card->type )
			{
				case \IPS\nexus\CreditCard::TYPE_VISA:
					$cardType = 'visa';
					break;
				case \IPS\nexus\CreditCard::TYPE_MASTERCARD:
					$cardType = 'mastercard';
					break;
				case \IPS\nexus\CreditCard::TYPE_DISCOVER:
					$cardType = 'discover';
					break;
				case \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS:
					$cardType = 'amex';
					break;
			}

			$payer = array(
				'payment_method'		=> 'credit_card',
				'funding_instruments'	=> array(
					array(
						'credit_card'		=> array(
							'number'			=> $card->number,
							'type'				=> $cardType,
							'expire_month'		=> intval( $card->expMonth ),
							'expire_year'		=> intval( $card->expYear ),
							'cvv2'				=> intval( $card->ccv ),
							'first_name'		=> $this->_getFirstName( $transaction ),
							'last_name'			=> $this->_getLastName( $transaction ),
							'billing_address'	=> $this->_getAddress( $transaction->invoice->billaddress, $transaction->member )
						)
					),
				)
			);
		}
		
		/* Send the request */
		$response = $this->api( 'payments/payment', array(
			'intent'		=> 'authorize',
			'payer'			=> $payer,
			'transactions'	=> array( $this->_getTransactions( $transaction ) ),
			'redirect_urls'	=> array(
				'return_url'	=> \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/paypal.php?nexusTransactionId=' . $transaction->id,
				'cancel_url'	=> (string) $transaction->invoice->checkoutUrl(),
			)
		) );		
		$transaction->gw_id = $response['id'];
		
		/* Save the card first if the user wants */
		if ( $card->save )
		{
			try
			{
				$storedCard = new \IPS\nexus\Gateway\PayPal\CreditCard;
				$storedCard->member = $transaction->member;
				$storedCard->method = $this;
				$storedCard->card = $card;
				$storedCard->save();
			}
			catch ( \Exception $e ) { }
		}
		
		/* And return */
		return \IPS\DateTime::ts( strtotime( $response['transactions'][0]['related_resources'][0]['authorization']['valid_until'] ) );
	}
	
	/**
	 * Authorize PayPal Payment
	 *
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @return	\IPS\DateTime|NULL		Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException			Message will be displayed to user
	 */
	protected function _paypalAuth( \IPS\nexus\Transaction $transaction, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL )
	{
		/* Send the request */
		$response = $this->api( 'payments/payment', array(
			'intent'		=> 'authorize',
			'payer'			=> array( 'payment_method' => 'paypal' ),
			'transactions'	=> array( $this->_getTransactions( $transaction ) ),
			'redirect_urls'	=> array(
				'return_url'	=> \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/paypal.php?nexusTransactionId=' . $transaction->id,
				'cancel_url'	=> (string) $transaction->invoice->checkoutUrl(),
			)
		) );
		
		/* Set gateway ID */		
		$transaction->gw_id = $response['id'];
		$transaction->save();
		
		/* Redirect */
		foreach ( $response['links'] as $link )
		{
			if ( $link['rel'] === 'approval_url' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::external( $link['href'] ) );
			}
		}
		throw new \RuntimeException;
	}
	
	/**
	 * Authorize Billing Agreement
	 *
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @param	\IPS\nexus\Purchase\RenewalTerm			$term			Renewal Term
	 * @param	array									$items			Items
	 * @return	\IPS\DateTime|NULL		Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException			Message will be displayed to user
	 */
	protected function _billingAgreementAuth( \IPS\nexus\Transaction $transaction, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL, \IPS\nexus\Purchase\RenewalTerm $term, $items )
	{
		/* Work out the name */
		$titles = array();
		foreach ( $items as $item )
		{
			$titles[] = ( $item->name . ( $item->quantity > 1 ? " x{$item->quantity}" : '' ) );
		}
		$title = implode( ', ', $titles );
		if ( mb_strlen( $title ) > 128 )
		{
			$title = mb_substr( $title, 0, 125 ) . '...';
		}
		$description = sprintf( $transaction->member->language()->get('transaction_number'), $transaction->id );
		
		/* Create Billing Plan */
		$paymentDefinitions = array();
		$definition = array(
			'name'	=> 'Payment Definition',
			'type'	=> 'REGULAR',
		);
		if ( $term->interval->y )
		{
			$definition['frequency_interval'] = $term->interval->y;
			$definition['frequency'] = 'YEAR';
		}
		elseif ( $term->interval->m )
		{
			$definition['frequency_interval'] = $term->interval->m;
			$definition['frequency'] = 'MONTH';
		}
		elseif ( $term->interval->w )
		{
			$definition['frequency_interval'] = $term->interval->w;
			$definition['frequency'] = 'WEEK';
		}
		elseif ( $term->interval->d )
		{
			$definition['frequency_interval'] = $term->interval->d;
			$definition['frequency'] = 'DAY';
		}
		$definition['cycles'] = '0';
		$definition['amount'] = array(
			'currency'			=> $term->cost->currency,
			'value'				=> $term->cost->amountAsString()
		);
		if ( $term->tax )
		{
			$definition['charge_models'] = array(
				array(
					'type'				=> 'TAX',
					'amount'			=> array(
						'currency'			=> $term->cost->currency,
						'value'				=> (string) $term->cost->amount->multiply( new \IPS\Math\Number( (string) $term->tax->rate( $transaction->invoice->billaddress ) ) )
					)
				),
			);
		}
		$paymentDefinitions[] = $definition;
		$response = $this->api( 'payments/billing-plans', array(
			'name'					=> $description,
			'description'			=> $title,
			'type'					=> 'INFINITE',
			'payment_definitions'	=> $paymentDefinitions,
			'merchant_preferences'	=> array(
				'setup_fee'				=> array(
					'currency'				=> $transaction->amount->currency,
					'value'					=> $transaction->amount->amountAsString(),
				),
				'cancel_url'					=> (string) $transaction->invoice->checkoutUrl(),
				'return_url'					=> \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/paypal.php?billingAgreement=1&nexusTransactionId=' . $transaction->id,
				'initial_fail_amount_action'	=> 'CANCEL',
			)
		) );
		$billingPlanId = $response['id'];
		
		/* Activate it */
		$response = $this->api( 'payments/billing-plans/' . $billingPlanId, array(
			array(
				'path'	=> '/',
				'value'	=> array(
					'state'	=> 'ACTIVE'
				),
				'op'	=> 'replace',
			)
		), 'patch' );
		
		/* Create Billing Agreement */
		$payerInfo = array( 'email' => $transaction->member->email );
		if ( $transaction->member->cm_phone )
		{
			$payerInfo['phone'] = $transaction->member->cm_phone;
		}
		$payerInfo['billing_address'] = $this->_getAddress( $transaction->invoice->billaddress, $transaction->invoice->member );
		$billingAgreementData = array(
			'name'			=> $description,
			'description'	=> $title,
			'start_date'	=> \IPS\DateTime::create()->add( $term->interval )->rfc3339(),
			'payer'			=> array(
				'payment_method'	=> 'paypal',
				'payer_info'		=> $payerInfo,
			)
		);
		if ( $transaction->invoice->shipaddress )
		{
			$billingAgreementData['shipping_address'] = $this->_getAddress( $transaction->invoice->shipaddress, $transaction->invoice->member );
		}
		$billingAgreementData['plan'] = array( 'id' => $billingPlanId );
		$response = $this->api( 'payments/billing-agreements', $billingAgreementData );
				
		/* Redirect */
		foreach ( $response['links'] as $link )
		{
			if ( $link['rel'] === 'approval_url' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::external( $link['href'] ) );
			}
		}
		throw new \RuntimeException;
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
		/* If it's not a payment (such as a billing agreement transaction) - we can only refund */
		if ( mb_substr( $transaction->gw_id, 0, 4 ) !== 'PAY-' )
		{
			return $this->refund( $transaction );
		}
		
		/* Otherwise, do it... */	
		$payment = $this->api( "payments/payment/{$transaction->gw_id}", NULL, 'get' );
		foreach ( $payment['transactions'][0]['related_resources'] as $rr )
		{
			if ( isset( $rr['authorization'] ) )
			{
				return $this->api( "payments/authorization/{$rr['authorization']['id']}/void" );
			}
		}
		
		/* Still here? Throw an exception */
		throw new \RuntimeException;
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
		/* If it's not a payment (such as a billing agreement transaction) - it has already been captured */
		if ( mb_substr( $transaction->gw_id, 0, 4 ) !== 'PAY-' )
		{
			return TRUE;
		}
		
		/* Otherwise, do it... */		
		$payment = $this->api( "payments/payment/{$transaction->gw_id}", NULL, 'get' );
		foreach ( $payment['transactions'][0]['related_resources'] as $rr )
		{
			if ( isset( $rr['authorization'] ) )
			{
				try
				{
					$response = $this->api( "payments/authorization/{$rr['authorization']['id']}/capture", array(
						'amount'			=> array(
							'currency'			=> $transaction->amount->currency,
							'total'				=> $transaction->amount->amountAsString(),
						),
						'is_final_capture'	=> TRUE,
					) );
				}
				catch ( \IPS\nexus\Gateway\PayPal\Exception $e )
				{
					if ( $e->getName() == 'AUTHORIZATION_ALREADY_COMPLETED' )
					{
						return TRUE;
					}
					throw $e;
				}
				
				return TRUE;
			}
		}
				
		/* Still here? Throw an exception */
		throw new \RuntimeException;
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
		/* The capture ID is *normally* the gateway transaction ID */
		$captureId = $transaction->gw_id;
		
		/* But if it starts with I- (or is a blank but known to be a billing agreement payment) - that's a billing agreement */
		if ( ( $transaction->billing_agreement and !$transaction->gw_id ) or ( mb_substr( $transaction->gw_id, 0, 2 ) === 'I-' ) )
		{
			$transactions = $this->api( "payments/billing-agreements/{$transaction->billing_agreement->gw_id}/transactions?start_date=" . $transaction->date->sub( new \DateInterval('P1D') )->format('Y-m-d') . '&end_date=' . $transaction->date->format('Y-m-d'), NULL, 'get' );
			foreach ( $transactions['agreement_transaction_list'] as $t )
			{
				if ( $t['status'] == 'Completed' )
				{
					$transaction->gw_id = $t['transaction_id'];
					$transaction->save();
					$captureId = $transaction->gw_id;
					break;
				}
			}
		}
		/* And if it starts with PAY-, that's a payment */
		elseif ( mb_substr( $transaction->gw_id, 0, 4 ) === 'PAY-' )
		{
			$payment = $this->api( "payments/payment/{$transaction->gw_id}", NULL, 'get' );		
			$captureId = NULL;
			foreach ( $payment['transactions'][0]['related_resources'] as $rr )
			{
				if ( isset( $rr['capture'] ) )
				{
					$captureId = $rr['capture']['id'];
					break;
				}
			}
		}
		
		/* Process Refund */
		$amount = $amount ? new \IPS\nexus\Money( $amount, $transaction->currency ) : $transaction->amount;
		$response = $this->api( "payments/capture/{$captureId}/refund", array( 'amount' => array(
			'currency'	=> $amount->currency,
			'total'		=> $amount->amountAsString()
		) ) );
		return $response['id'];
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
		
		$form->add( new \IPS\Helpers\Form\Radio( 'paypal_type', $settings['type'], TRUE, array( 'options' => array( 'paypal' => 'paypal_type_paypal', 'card' => 'paypal_type_card' ), 'toggles' => array( 'paypal' => array( 'paypal_billing_agreements' ), 'card' => array( 'paypal_vault' ) ) ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'paypal_billing_agreements', $this->id ? ( (string) $settings['billing_agreements'] ) : 'optional', FALSE, array( 'options' => array(
			'required'	=> 'paypal_billing_agreements_req',
			'optional'	=> 'paypal_billing_agreements_opt',
			''	=> 'paypal_billing_agreements_dis',
		) ), NULL, NULL, NULL, 'paypal_billing_agreements' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'paypal_vault', $this->id ? $settings['vault'] : TRUE, FALSE, array(), NULL, NULL, NULL, 'paypal_vault' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'paypal_client_id', $settings['client_id'], TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'paypal_secret', $settings['secret'], TRUE ) );
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
			$this->getNewToken( $settings );
		}
		catch ( \Exception $e )
		{
			throw new \InvalidArgumentException( $e->getMessage(), $e->getCode() );
		}
		
		return $settings;
	}
	
	/* !Utility Methods */
	
	/**
	 * Send API Request
	 *
	 * @param	string	$uri			The API to request (e.g. "payments/payment")
	 * @param	array	$data			The data to send
	 * @param	string	$method			Method (get/post)
	 * @parsam	bool	$expectResponse	
	 * @return	array|null
	 * @throws	\IPS\Http|Exception
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	public function api( $uri, $data=NULL, $method='post', $expectResponse=TRUE )
	{
		$settings = json_decode( $this->settings, TRUE );
		if ( !isset( $settings['token'] ) or $settings['token_expire'] < time() )
		{
			$token = $this->getNewToken();
			$settings['token'] = $token['access_token'];
			$settings['token_expire'] = ( time() + $token['expires_in'] );
			$this->settings = json_encode( $settings );
			$this->save();
		}
		
		$response = \IPS\Http\Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/' . $uri )
			->request( \IPS\LONG_REQUEST_TIMEOUT )
			->setHeaders( array(
				'Content-Type'					=> 'application/json',
				'Authorization'					=> "Bearer {$settings['token']}",
				'PayPal-Partner-Attribution-Id'	=> 'InvisionPower_SP'
			) )
			->$method( $data === NULL ? NULL : json_encode( $data ) );
					
		if ( mb_substr( $response->httpResponseCode, 0, 1 ) !== '2' )
		{
			throw new \IPS\nexus\Gateway\PayPal\Exception( $response );
		}
		
		if ( in_array( $method, array( 'delete', 'patch' ) ) or $response->httpResponseCode == 204 )
		{
			return NULL;
		}
		else
		{
			return $response->decodeJson();
		}
	}
	
	/**
	 * Get Token
	 *
	 * @param	array|NULL	$settings	Settings (NULL for saved setting)
	 * @return	array
	 * @throws	\IPS\Http|Exception
	 * @throws	\UnexpectedValueException
	 */
	protected function getNewToken( $settings = NULL )
	{
		$settings = $settings ?: json_decode( $this->settings, TRUE );
				
		$response = \IPS\Http\Url::external( 'https://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'api.sandbox.paypal.com' : 'api.paypal.com' ) . '/v1/oauth2/token' )
			->request()
			->setHeaders( array(
				'Accept'			=> 'application/json',
				'Accept-Language'	=> 'en_US',
			) )
			->login( $settings['client_id'], $settings['secret'] )
			->post( array( 'grant_type' => 'client_credentials' ) )
			->decodeJson();
			
		if ( !isset( $response['access_token'] ) )
		{
			throw new \UnexpectedValueException( isset( $response['error_description'] ) ? $response['error_description'] : $response );
		}

		return $response;
	}
	
	/**
	 * Get address for PayPal
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	array
	 */
	protected function _getAddress( \IPS\GeoLocation $address, \IPS\nexus\Customer $customer )
	{
		/* PayPal requires short codes for states */
		$state = $address->region;
		if ( isset( \IPS\nexus\Customer\Address::$stateCodes[ $address->country ] ) )
		{
			if ( !array_key_exists( $state, \IPS\nexus\Customer\Address::$stateCodes[ $address->country ] ) )
			{
				$_state = array_search( $address->region, \IPS\nexus\Customer\Address::$stateCodes[ $address->country ] );
				if ( $_state !== FALSE )
				{
					$state = $_state;
				}
			}
		}
		
		/* Construct */
		$address = array(
			'line1'				=> $address->addressLines[0],
			'line2'				=> isset( $address->addressLines[1] ) ? $address->addressLines[1] : '',
			'city'				=> $address->city,
			'country_code'		=> $address->country,
			'postal_code'		=> $address->postalCode,
			'state'				=> $state,
		);

		/* Add phone number */
		if ( $customer->cm_phone )
		{
			$address['phone'] = preg_replace( '/[^\+0-9\s]/', '', $customer->cm_phone );
		}
		
		/* Return */
		return $address;
	}
	
	/**
	 * Get first name for PayPal
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	array
	 */
	protected function _getFirstName( \IPS\nexus\Transaction $transaction )
	{
		return $transaction->invoice->member->member_id ? $transaction->invoice->member->cm_first_name : $transaction->invoice->guest_data['member']['cm_first_name'];
	}
	
	/**
	 * Get last name for PayPal
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	array
	 */
	protected function _getLastName( \IPS\nexus\Transaction $transaction )
	{
		return $transaction->invoice->member->member_id ? $transaction->invoice->member->cm_last_name : $transaction->invoice->guest_data['member']['cm_last_name'];
	}
	
	/**
	 * Get transaction data for PayPal
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	array
	 */
	protected function _getTransactions( \IPS\nexus\Transaction $transaction )
	{
		/* Init */
		$payPalTransactionData = array(
			'amount'	=> array(
				'currency'	=> $transaction->amount->currency,
				'total'		=> $transaction->amount->amountAsString(),
			),
			'invoice_number'=> \IPS\SITE_SECRET_KEY . '-' . $transaction->id,
		);
		
		/* If we're paying the whole invoice, we can add item data... */
		if ( $transaction->amount->amount->compare( $transaction->invoice->total->amount ) === 0 )
		{
			$summary = $transaction->invoice->summary();
			
			/* Shipping / Tax */
			$payPalTransactionData['amount']['details'] = array(
				'shipping'	=> $summary['shippingTotal']->amountAsString(),
				'subtotal'	=> $summary['subtotal']->amountAsString(),
				'tax'		=> $summary['taxTotal']->amountAsString(),
			);

			/* Items */
			$payPalTransactionData['item_list'] = array( 'items' => array() );
			foreach ( $summary['items'] as $item )
			{
				$payPalTransactionData['item_list']['items'][] = array(
					'quantity'	=> $item->quantity,
					'name'		=> $item->name,
					'price'		=> $item->price->amountAsString(),
					'currency'	=> $transaction->amount->currency,
				);
			}

			/* Shipping Address */
			if ( $transaction->invoice->shipaddress )
			{
				$payPalTransactionData['item_list']['shipping_address'] = array_merge(
					array( 'recipient_name'	=> $this->_getFirstName( $transaction ) . ' ' . $this->_getLastName( $transaction ) ),
					$this->_getAddress( $transaction->invoice->shipaddress, $transaction->invoice->member )
				);
			}
		}
		/* Otherwise just use a generic description */
		else
		{
			$payPalTransactionData['description'] = sprintf( $transaction->member->language()->get('partial_payment_desc'), $transaction->invoice->id );
		}
		
		return $payPalTransactionData;
	}
}
