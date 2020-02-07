<?php
/**
 * @brief		Generate Renewal Invoices Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		01 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Generate Renewal Invoices Task
 */
class _generateRenewalInvoices extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
        $renewalDate = \IPS\DateTime::create();
        if( \IPS\Settings::i()->cm_invoice_generate )
        {
            $renewalDate->add( new \DateInterval( 'P' . \IPS\Settings::i()->cm_invoice_generate . 'D' )  );
        }
        /* Get purchases grouped by member and currency */
        $select = \IPS\Db::i()->select( '*', 'nexus_purchases', array(
            'ps_renewals>0 AND ps_invoice_pending=0 AND ps_active=1 AND ps_expire>0 AND ps_expire<? AND ps_billing_agreement IS NULL',
            $renewalDate->getTimestamp()
        ), 'ps_member', 50 );

		$groupedPurchases = array();
		foreach ( new \IPS\Patterns\ActiveRecordIterator( $select, 'IPS\nexus\Purchase' ) as $purchase )
		{
			/* If the member does not exist, we should not lock the task */
			try
			{
				$groupedPurchases[ $purchase->member->member_id ][ $purchase->renewal_currency ][ $purchase->id ] = $purchase;
			}
			catch( \OutOfRangeException $e )
			{
				/* Set the purchase inactive so we don't try again. */
				$purchase->active = 0;
				$purchase->save();
			}
		}
		
		/* Loop */
		foreach ( $groupedPurchases as $memberId => $currencies )
		{
			$member = \IPS\nexus\Customer::load( $memberId );
			foreach ( $currencies as $currency => $purchases )
			{				
				/* Create Invoice */
				$invoice = new \IPS\nexus\Invoice;
				$invoice->system = TRUE;
				$invoice->currency = $currency;
				$invoice->member = $member;
				foreach ( $purchases as $purchase )
				{
					$invoice->addItem( \IPS\nexus\Invoice\Item\Renewal::create( $purchase ) );
				}
				$invoice->save();

				/* Nothing to pay? */
				if ( $invoice->amountToPay()->amount->isZero() )
				{
					$extra = $invoice->status_extra;
					$extra['type']		= 'zero';
					$invoice->status_extra = $extra;
					$invoice->markPaid();
				}

				/* Charge what we can to account credit */
				if ( $invoice->status !== $invoice::STATUS_PAID )
				{
					$credits = $member->cm_credits;
					$credit = $credits[$currency]->amount;
					if( $credit->isGreaterThanZero() )
					{
						if ( $credit->compare( $invoice->total->amount ) === 1 )
						{
							$take = $invoice->total->amount;
						}
						else
						{
							$take = $credit;
						}
						
						$transaction = new \IPS\nexus\Transaction;
						$transaction->member = $member;
						$transaction->invoice = $invoice;
						$transaction->amount = new \IPS\nexus\Money( $take, $currency );
						$transaction->extra = array('automatic' => TRUE);
						$transaction->save();
						$transaction->approve();

						$member->log( 'transaction', array(
							'type' => 'paid',
							'status' => \IPS\nexus\Transaction::STATUS_PAID,
							'id' => $transaction->id,
							'invoice_id' => $invoice->id,
							'invoice_title' => $invoice->title,
							'automatic' => TRUE,
						), FALSE );

						$credits[$currency]->amount = $credits[$currency]->amount->subtract( $take );
						$member->cm_credits = $credits;
						$member->save();

						$invoice->status = $transaction->invoice->status;
					}
				}
				/* Charge to card */
				if ( $invoice->status !== $invoice::STATUS_PAID )
				{
					try
					{
						foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', $member->member_id  ) ), 'IPS\nexus\Customer\CreditCard' ) as $card )
						{
							$amountToPay = $invoice->amountToPay();
							$gateway = $card->method;

							$transaction = new \IPS\nexus\Transaction;
							$transaction->member = $member;
							$transaction->invoice = $invoice;
							$transaction->method = $gateway;
							$transaction->amount = $amountToPay;
							$transaction->currency = $currency;
							$transaction->extra = array( 'automatic' => TRUE );

							try
							{
								$transaction->auth = $gateway->auth( $transaction, array(
									( $gateway->id . '_card' ) => $card
								) );
								$transaction->capture();

								$transaction->member->log( 'transaction', array(
									'type'			=> 'paid',
									'status'		=> \IPS\nexus\Transaction::STATUS_PAID,
									'id'			=> $transaction->id,
									'invoice_id'	=> $invoice->id,
									'invoice_title'	=> $invoice->title,
									'automatic'		=> TRUE,
								), FALSE );

								$transaction->approve();
							}
							catch ( \Exception $e ){}

							$invoice->status = $transaction->invoice->status;
						}
					}
					// credit card doesn't exist anymore , move on
					catch ( \OutOfRangeException $e ){}

				}
				
				/* Update the purchase */
				if ( $invoice->status !== $invoice::STATUS_PAID )
				{
					foreach ( $purchases as $purchase )
					{
						$purchase->invoice_pending = $invoice;
						$purchase->save();
					}
				}
			
				/* Send notification */
				$invoice->sendNotification();
			}
		}
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}