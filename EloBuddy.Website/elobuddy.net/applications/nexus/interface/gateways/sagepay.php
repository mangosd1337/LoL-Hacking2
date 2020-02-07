<?php
/**
 * @brief		SagePay Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		19 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

require_once '../../../../init.php';

function returnData( $status, $detail, $returnUrl )
{
	echo "Status={$status}\r\nStatusDetail={$detail}\r\nRedirectURL={$returnUrl}";
	exit;
}

/* Load */
try
{
	$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->VendorTxCode );
	
	if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING )
	{
		throw new \OutofRangeException;
	}
}
catch ( \OutOfRangeException $e )
{
	returnData( 'ERROR', 'NO_TRANSACTION', \IPS\Settings::i()->base_url );
}

/* Authenticate */
$settings = json_decode( $transaction->method->settings, TRUE );
$extra = $transaction->extra;

if( !\IPS\Login::compareHashes( \IPS\Request::i()->VPSSignature, mb_strtoupper( md5( \IPS\Request::i()->VPSTxId . \IPS\Request::i()->VendorTxCode . \IPS\Request::i()->Status . \IPS\Request::i()->TxAuthNo . mb_strtolower( $settings['vendor'] ) . \IPS\Request::i()->AVSCV2 . $extra['sagepaySecurityKey'] . \IPS\Request::i()->AddressResult . \IPS\Request::i()->PostCodeResult . \IPS\Request::i()->CV2Result . \IPS\Request::i()->GiftAid . \IPS\Request::i()->__get('3DSecureStatus') . \IPS\Request::i()->CAVV . \IPS\Request::i()->AddressStatus . \IPS\Request::i()->PayerStatus . \IPS\Request::i()->CardType . \IPS\Request::i()->Last4Digits . \IPS\Request::i()->DeclineCode . \IPS\Request::i()->ExpiryDate . \IPS\Request::i()->FraudResponse . \IPS\Request::i()->BankAuthCode ) ) ) )
{
	returnData( 'INVALID', 'MD5', $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $transaction->member->language()->get( 'gateway_err' ) ) ) );
}

/* Save the auth code */
$extra['sagepayAuth'] = \IPS\Request::i()->TxAuthNo;
$transaction->extra = $extra;
$transaction->save();

/* Handle */
if ( \IPS\Request::i()->Status === 'OK' )
{
	$maxMind = NULL;
	if ( \IPS\Settings::i()->maxmind_key )
	{
		$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
		$maxMind->setTransaction( $transaction );
	}
	$transaction->checkFraudRulesAndCapture( $maxMind );
	$transaction->sendNotification();
}

/* Respond */
returnData( 'OK', 'MD5', $transaction->url() );