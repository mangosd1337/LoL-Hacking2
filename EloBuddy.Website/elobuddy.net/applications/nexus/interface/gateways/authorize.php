<?php
/**
 * @brief		Authorize.Net DPM Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

require_once '../../../../init.php';

try
{
	$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->x_invoice_num );
}
catch ( \Exception $e )
{
	\IPS\Output::i()->sendOutput( '', 404 );
	exit;
}

try
{
	/* Load Gateway */
	$gateway = \IPS\nexus\Gateway::load( \IPS\Request::i()->payment_method );
	$settings = json_decode( $gateway->settings, TRUE );
	
	/* Check MD5 hash */
	if ( !\IPS\Login::compareHashes( mb_strtoupper( md5( $settings['hash'] . $settings['login'] . \IPS\Request::i()->x_trans_id . \IPS\Request::i()->x_amount ) ), \IPS\Request::i()->x_MD5_Hash ) )
	{
		throw new \Exception( $invoice->member->language()->addToStack('gateway_err') );
	}
	
	/* Was it accepted? */
	if ( \IPS\Request::i()->x_response_code != 1 )
	{
		throw new \IPS\nexus\Gateway\AuthorizeNet\Exception( \IPS\Request::i()->x_response_reason_code );
	}
	
	/* Create a transaction */
	$transaction = new \IPS\nexus\Transaction;
	$transaction->member = \IPS\Member::load( \IPS\Request::i()->x_cust_id );
	$transaction->invoice = $invoice;
	$transaction->method = $gateway;
	$transaction->amount = \IPS\Request::i()->x_amount;
	$transaction->currency = $invoice->currency;
	$transaction->gw_id = \IPS\Request::i()->x_trans_id;
	$transaction->ip = \IPS\Request::i()->x_customer_ip;
	$extra = $transaction->extra;
	$extra['lastFour'] = str_replace( 'X', '', \IPS\Request::i()->x_account_number );
	$transaction->extra  = $extra;
	$transaction->auth = \IPS\DateTime::create()->add( new \DateInterval( 'P30D' ) );
	
	/* Create a MaxMind request */
	$maxMind = NULL;
	if ( \IPS\Settings::i()->maxmind_key )
	{
		$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
		$maxMind->setTransaction( $transaction );
		$maxMind->setTransactionType( 'creditcard' );
		$maxMind->setAVS( \IPS\Request::i()->x_avs_code );
	}
		
	/* Check Fraud Rules and capture */
	$transaction->checkFraudRulesAndCapture( $maxMind );
	$transaction->sendNotification();
	
	/* Show thanks screen */
	$url = $transaction->url();
}
catch ( \Exception $e )
{
	$url = $invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $e->getMessage() ) );
}

?>
<html>
<head>
<script type='text/javascript' charset='utf-8'>
window.location='<?php echo $url; ?>';
</script>
<noscript>
<meta http-equiv='refresh' content='1;url=<?php echo $url; ?>'>
</noscript>
</head>
<body></body>
</html>