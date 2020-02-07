<?php
/**
 * @brief		Invoice Item Class for Renewals
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		01 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Invoice\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Item Class for Renewals
 */
class _Renewal extends \IPS\nexus\Invoice\Item
{
	/**
	 * @brief	Act (new/charge)
	 */
	public static $act = 'renewal';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'refresh';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'renewal';
	
	/**
	 * @brief	Requires login to purchase?
	 */
	public static $requiresAccount = TRUE;
	
	/**
	 * @brief	New expiry date (NULL will cause automatic calculation)
	 */
	public $expire = NULL;
	
	/**
	 * Create
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase to renew
	 * @param	int					$cycles		The number of cycles to renew for
	 * @return	void
	 */
	public static function create( \IPS\nexus\Purchase $purchase, $cycles = 1 )
	{
		$obj = new static( sprintf( $purchase->member->language()->get('renew_title'), $purchase->name ), $purchase->renewals->cost );
		$obj->tax = $purchase->renewals->tax;
		$obj->quantity = $cycles;
		$obj->application = $purchase->app;
		$obj->type = $purchase->type;
		$obj->id = $purchase->id;
		
		if ( $purchase->pay_to )
		{
			$obj->payTo = $purchase->pay_to;
			$obj->commission = $purchase->commission;
		}
		
		if ( $renewalPaymentMethodIds = $purchase->renewalPaymentMethodIds() )
		{
			$obj->paymentMethodIds = $renewalPaymentMethodIds;
		}
		
		return $obj;
	}
	
	/**
	 * On Paid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPaid( \IPS\nexus\Invoice $invoice )
	{
		$purchase = \IPS\nexus\Purchase::load( $this->id );
		
		if ( $purchase->renewals and $interval = $purchase->renewals->interval and !$purchase->cancelled and $expire = $purchase->expire )
		{			
			$_expire = clone $expire;
			if ( $_expire->add( new \DateInterval( 'PT' . $purchase->grace_period . 'S' ) )->getTimestamp() < time() )
			{				
				$expire = new \IPS\DateTime;
			}
			for ( $i=0; $i<$this->quantity; $i++ )
			{
				$expire->add( $interval );
			}
			
			$purchase->expire = $expire;
			$purchase->invoice_pending = NULL;
			
			$billingAgreement = NULL;
			foreach ( $invoice->transactions( array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED ), array( array( 't_billing_agreement IS NOT NULL' ) ) ) as $transaction )
			{
				$billingAgreement = $transaction->billing_agreement;
			}
			if ( $billingAgreement )
			{
				$purchase->billing_agreement = $billingAgreement;
			}
			
			$purchase->save();
			$purchase->onRenew( $this->quantity );
			
			$purchase->member->log( 'purchase', array( 'type' => 'renew', 'id' => $purchase->id, 'name' => $purchase->name, 'invoice_id' => $invoice->id, 'invoice_title' => $invoice->title ) );
		}		
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( \IPS\nexus\Invoice $invoice )
	{
		$return = parent::onUnpaidDescription( $invoice );
		
		$purchase = \IPS\nexus\Purchase::load( $this->id );
		
		if ( $purchase->renewals and $interval = $purchase->renewals->interval and !$purchase->cancelled and $expire = $purchase->expire )
		{
			for ( $i=0; $i<$this->quantity; $i++ )
			{
				$expire->sub( $interval );
			}
			
			$return[] = \IPS\Member::loggedIn()->language()->addToStack( 'renewal_unpaid', FALSE, array( 'sprintf' => array( $purchase->name, $purchase->id, $expire->localeDate() ) ) );
		}
		
		return $return;
	}
	
	/**
	 * On Unpaid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @param	string				$status		Status
	 * @return	void
	 */
	public function onUnpaid( \IPS\nexus\Invoice $invoice, $status )
	{
		$purchase = \IPS\nexus\Purchase::load( $this->id );
		
		if ( $purchase->renewals and $interval = $purchase->renewals->interval and !$purchase->cancelled and $expire = $purchase->expire )
		{
			for ( $i=0; $i<$this->quantity; $i++ )
			{
				$expire->sub( $interval );
			}
			$purchase->expire = $expire;
			
			if ( $status === \IPS\nexus\Invoice::STATUS_PENDING )
			{
				$purchase->invoice_pending = $invoice;
			}
			
			$purchase->save();
			
			$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'invoice_id' => $invoice->id, 'invoice_title' => $invoice->title, 'system' => TRUE ) );
		}
	}
	
	/**
	 * Client Area URL
	 *
	 * @return |IPS\Http\Url|NULL
	 */
	public function url()
	{
		try
		{
			return \IPS\nexus\Purchase::load( $this->id )->url();
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * ACP URL
	 *
	 * @return |IPS\Http\Url|NULL
	 */
	public function acpUrl()
	{
		return \IPS\nexus\Purchase::load( $this->id )->acpUrl();
	}
}