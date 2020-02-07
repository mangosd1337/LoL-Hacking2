<?php
/**
 * @brief		2Checkout Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

require_once '../../../../init.php';

try
{
	$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->nexustransactionid );
	
	if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING )
	{
		throw new \OutofRangeException;
	}
}
catch ( \OutOfRangeException $e )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=checkout&controller=checkout&do=transaction&id=&t=" . \IPS\Request::i()->nexustransactionid, 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https ) );
}

$settings = json_decode( $transaction->method->settings, TRUE );
$hash = mb_strtoupper( md5( $settings['word'] . $settings['sid'] . ( \IPS\NEXUS_TEST_GATEWAYS ? 1 : \IPS\Request::i()->order_number ) . number_format( $transaction->amount->amountAsString(), 2 ) ) );
if ( !\IPS\Login::compareHashes( (string) $hash, (string) \IPS\Request::i()->key ) )
{
	\IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $transaction->member->language()->get('gateway_err') ) ) );
}
if ( \IPS\Request::i()->credit_card_processed !== 'Y' )
{
	\IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $transaction->member->language()->get('card_refused') ) ) );
}

$transaction->gw_id = \IPS\Request::i()->order_number;
$transaction->save();
	
$maxMind = NULL;
if ( \IPS\Settings::i()->maxmind_key )
{
	$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
	$maxMind->setTransaction( $transaction );
}

$transaction->checkFraudRulesAndCapture( $maxMind );
$transaction->sendNotification();
\IPS\Session::i()->setMember( $transaction->invoice->member ); // This is in case the checkout was a guest, meaning checkFraudRulesAndCapture() may have just created an account. There is no security issue as we have just verified they were just bounced back from 2CheckOut
\IPS\Output::i()->redirect( $transaction->url() );