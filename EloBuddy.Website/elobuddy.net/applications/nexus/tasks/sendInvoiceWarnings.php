<?php
/**
 * @brief		Send Invoice Warnings Task
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
 * Send Invoice Warnings Task
 */
class _sendInvoiceWarnings extends \IPS\Task
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
		if ( \IPS\Settings::i()->cm_invoice_warning )
		{
			$normalCutoff = \IPS\DateTime::create()->add( new \DateInterval( 'P' . \IPS\Settings::i()->cm_invoice_generate . 'D' ) )->add( new \DateInterval( 'P' . \IPS\Settings::i()->cm_invoice_warning . 'D' ) )->getTimestamp();
			$billingAgreementCutoff = \IPS\DateTime::create()->add( new \DateInterval( 'P' . \IPS\Settings::i()->cm_invoice_warning . 'D' ) )->getTimestamp();
			$select = \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_renewals>0 AND ps_invoice_pending=0 AND ps_invoice_warning_sent=0 AND ps_active=1 AND ps_expire>0 AND ( ( ps_billing_agreement IS NULL AND ps_expire<? ) OR ( ps_billing_agreement IS NOT NULL AND ps_expire<? ) )', $normalCutoff, $billingAgreementCutoff ), 'ps_member', 50 );
			
			$groupedPurchases = array();
			foreach ( new \IPS\Patterns\ActiveRecordIterator( $select, 'IPS\nexus\Purchase' ) as $purchase )
			{
				$groupedPurchases[ $purchase->member->member_id ][ $purchase->billing_agreement ? $purchase->billing_agreement->id : 0 ][ $purchase->renewal_currency ][ $purchase->id ] = $purchase;
			}
			
			/* Loop */
			foreach ( $groupedPurchases as $memberId => $_groupedPurchases )
			{
				$member = \IPS\nexus\Customer::load( $memberId );
				foreach ( $_groupedPurchases as $billingAgreementId => $__groupedPurchases )
				{
					foreach ( $__groupedPurchases as $currency => $purchases )
					{
						$email = NULL;
						
						/* Create a temporary invoice (we're not going to save this) so that we knkow what the charges will be */
						$invoice = new \IPS\nexus\Invoice;
						foreach ( $purchases as $purchase )
						{
							$invoice->addItem( \IPS\nexus\Invoice\Item\Renewal::create( $purchase ) );
						}
						$invoice->setDefaultTitle();
						
						/* If there is a billing agreement - send an email abiout that */
						if ( $billingAgreementId )
						{
							$email = \IPS\Email::buildFromTemplate( 'nexus', 'invoiceWarning', array( array(), NULL, \IPS\nexus\Customer\BillingAgreement::load( $billingAgreementId ), $invoice, $invoice->summary() ), \IPS\Email::TYPE_TRANSACTIONAL );
						}
						/* Otherwise check account credit and cards */
						else
						{						
							$cards = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', $member->member_id ) ), 'IPS\nexus\Customer\CreditCard' );
							$credits = $member->cm_credits;
							$credit = $credits[ $currency ]->amount;
							
							if ( count( $cards ) or $credit->isGreaterThanZero() )
							{
								$email = \IPS\Email::buildFromTemplate( 'nexus', 'invoiceWarning', array( $cards, $credits[ $currency ], NULL, $invoice, $invoice->summary() ), \IPS\Email::TYPE_TRANSACTIONAL );
							}
						}
						
						/* Send the email */
						if ( $email )
						{
							$email->send(
								$member,
								array_map(
									function( $contact )
									{
										return $contact->alt_id->email;
									},
									iterator_to_array( $member->alternativeContacts( array( 'billing=1' ) ) )
								),
								( ( in_array( 'invoice_warn', explode( ',', \IPS\Settings::i()->nexus_notify_copy_types ) ) AND \IPS\Settings::i()->nexus_notify_copy_email ) ? explode( ',', \IPS\Settings::i()->nexus_notify_copy_email ) : array() )
							);
						}
						
						/* Update Purchases */											
						\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_invoice_warning_sent' => 1 ), \IPS\Db::i()->in( 'ps_id', array_keys( $purchases ) ) );
					}
				}
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