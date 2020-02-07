<?php
/**
 * @brief		PayPal Billing Agreement
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		16 Dec 2015
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
 * PayPal Billing Agreement
 */
class _BillingAgreement extends \IPS\nexus\Customer\BillingAgreement
{
	/**
	 * Get status
	 *
	 * @return	string	See STATUS_* constants
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	public function status()
	{
		$data = $this->_getData();
		
		switch ( $data['state'] )
		{
			case 'Active':
			case 'Pending':
			case 'Reactivate':
				return static::STATUS_ACTIVE;
				break;
			case 'Suspend':
			case 'Suspended':
				return static::STATUS_SUSPENDED;
				break;
			case 'Expired':
			case 'Cancel':
			case 'Cancelled':
				return static::STATUS_CANCELED;
				break;
		}
	}
	
	/**
	 * Get term
	 *
	 * @return	\IPS\nexus\Purchase\RenewalTerm
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	public function term()
	{
		$data = $this->_getData();
		
		$amount = $data['plan']['payment_definitions'][0]['amount']['value'];
		if ( isset( $data['plan']['payment_definitions'][0]['charge_models'][0] ) )
		{
			$amount += $data['plan']['payment_definitions'][0]['charge_models'][0]['amount']['value'];
		}
		
		return new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $amount, $data['plan']['payment_definitions'][0]['amount']['currency'] ), new \DateInterval( 'P' . $data['plan']['payment_definitions'][0]['frequency_interval'] . mb_substr( $data['plan']['payment_definitions'][0]['frequency'], 0, 1 ) ) );
	}
	
	/**
	 * Get next payment date
	 *
	 * @return	\IPS\DateTime
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	public function nextPaymentDate()
	{
		$data = $this->_getData();
		
		return new \IPS\DateTime( $data['agreement_details']['next_billing_date'] );
	}
	
	/**
	 * Get latest unclaimed transaction (only gets transactions within the last 24 hours which do not yet have a matching transaction)
	 *
	 * @return	\IPS\nexus\Transaction
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 * @throws	\OutOfRangeException
	 */
	public function latestUnclaimedTransaction()
	{
		$transactions = $this->method->api( "payments/billing-agreements/{$this->gw_id}/transactions?start_date=" . date( 'Y-m-d', time() - 86400 ) . '&end_date=' . date( 'Y-m-d' ), NULL, 'get' );
		foreach ( array_reverse( $transactions['agreement_transaction_list'] ) as $t )
		{
			if ( $t['status'] == 'Completed' )
			{
				try
				{
					$existingTransaction = \IPS\Db::i()->select( 't_id', 'nexus_transactions', array( 't_method=? AND t_gw_id=?', $this->method->id, $t['transaction_id'] ) )->first();
				}
				catch ( \UnderflowException $e )
				{
					$transaction = new \IPS\nexus\Transaction;
					$transaction->member = $this->member;
					$transaction->method = $this->method;
					$transaction->amount = new \IPS\nexus\Money( $t['amount']['value'], $t['amount']['currency'] );
					$transaction->date = new \IPS\DateTime( $t['time_stamp'] );
					$transaction->extra = array( 'automatic' => TRUE );
					$transaction->gw_id = $t['transaction_id'];
					$transaction->billing_agreement = $this;
					return $transaction;
				}
			}
		}
		throw new \OutOfRangeException;
	}
	
	/**
	 * Suspend
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function doSuspend()
	{
		$this->method->api( "payments/billing-agreements/{$this->gw_id}/suspend", array( 'note' => 'Suspend' ) );
	}
	
	/**
	 * Reactivate
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function doReactivate()
	{
		$this->method->api( "payments/billing-agreements/{$this->gw_id}/re-activate", array( 'note' => 'Reactivate' ) );
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function doCancel()
	{
		$this->method->api( "payments/billing-agreements/{$this->gw_id}/cancel", array( 'note' => 'Cancel' ) );
	}
	
	/**
	 * @brief	Cached data
	 */
	protected $_payPalData = NULL;
	
	/**
	 * Get data
	 *
	 * @return	array
	 * @throws	\IPS\nexus\Gateway\PayPal\Exception
	 */
	protected function _getData()
	{
		if ( $this->_payPalData === NULL )
		{
			$this->_payPalData = $this->method->api( "payments/billing-agreements/{$this->gw_id}", NULL, 'get' );
		}
		return $this->_payPalData;
	}
}