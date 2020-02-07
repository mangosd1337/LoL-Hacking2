<?php
/**
 * @brief		PayPal Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

require_once '../../../../init.php';
\IPS\Session\Front::i();

/* Load Transaction */
try
{
	$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->nexusTransactionId );
	
	if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING )
	{
		throw new \OutofRangeException;
	}
}
catch ( \OutOfRangeException $e )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=checkout&controller=checkout&do=transaction&id=&t=" . \IPS\Request::i()->nexusTransactionId, 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https ) );
}

/* Process */
try
{	
	/* Check fraud rules */
	$maxMind = NULL;
	if ( \IPS\Settings::i()->maxmind_key )
	{
		$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
		$maxMind->setTransaction( $transaction );
		$maxMind->setTransactionType( 'paypal' );
	}
	$fraudResult = $transaction->runFraudCheck( $maxMind );
	if ( $fraudResult === \IPS\nexus\Transaction::STATUS_REFUSED )
	{
		$transaction->executeFraudAction( $fraudResult, FALSE );
		$transaction->sendNotification();
		\IPS\Session::i()->setMember( $transaction->invoice->member ); // This is in case the checkout was a guest, meaning checkFraudRulesAndCapture() may have just created an account. There is no security issue as we have just verified they were just bounced back from PayPal
		\IPS\Output::i()->redirect( $transaction->url() );
	}
	
	/* Billing Agreement */
	if ( isset( \IPS\Request::i()->billingAgreement ) )
	{
		/* Execute */
		$response = $transaction->method->api( "payments/billing-agreements/" . \IPS\Request::i()->token . "/agreement-execute" );
		$agreementId = $response['id'];
				
		/* Create Billing Agreement */
		$billingAgreement = new \IPS\nexus\Gateway\PayPal\BillingAgreement;
		$billingAgreement->gw_id = $agreementId;
		$billingAgreement->method = $transaction->method;
		$billingAgreement->member = $transaction->member;
		$billingAgreement->started = \IPS\DateTime::create();
		$billingAgreement->next_cycle = \IPS\DateTime::create()->add( new \DateInterval( 'P' . $response['plan']['payment_definitions'][0]['frequency_interval'] . mb_substr( $response['plan']['payment_definitions'][0]['frequency'], 0, 1 ) ) );
		$billingAgreement->save();
		$transaction->billing_agreement = $billingAgreement;
		$transaction->save();
		
		/* Get the initial setup transaction if possible (PayPal may not respond immediately, but that's okay, we're only trying to get the transaction ID) */
		sleep( 10 ); // Wait 10 seconds just to give PayPal *some* time which will be plenty for most cases
		$haveInitialTransaction = FALSE;
		$transactions = $transaction->method->api( "payments/billing-agreements/{$agreementId}/transactions?start_date=" . date( 'Y-m-d', time() - 86400 ) . '&end_date=' . date( 'Y-m-d' ), NULL, 'get' );
		foreach ( $transactions['agreement_transaction_list'] as $t )
		{
			if ( $t['status'] == 'Completed' )
			{
				$haveInitialTransaction = TRUE;
				$transaction->gw_id = $t['transaction_id'];
				$transaction->save();
				break;
			}
		}
	}
	
	/* Normal */
	else
	{
		$response = $transaction->method->api( "payments/payment/{$transaction->gw_id}/execute", array(
			'payer_id'	=> \IPS\Request::i()->PayerID,
		) );
		$transaction->auth = \IPS\DateTime::ts( strtotime( $response['transactions'][0]['related_resources'][0]['authorization']['valid_until'] ) );
		$transaction->save();
	}
	
	/* Capture */
	if ( $fraudResult )
	{
		$transaction->executeFraudAction( $fraudResult, TRUE );
	}
	if ( !$fraudResult or $fraudResult === \IPS\nexus\Transaction::STATUS_PAID )
	{
		$transaction->captureAndApprove();
	}
	$transaction->sendNotification();
	\IPS\Session::i()->setMember( $transaction->invoice->member ); // This is in case the checkout was a guest, meaning checkFraudRulesAndCapture() may have just created an account. There is no security issue as we have just verified they were just bounced back from PayPal
	\IPS\Output::i()->redirect( $transaction->url() );
}
catch ( \Exception $e )
{
	\IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $e->getMessage() ) ) );
}