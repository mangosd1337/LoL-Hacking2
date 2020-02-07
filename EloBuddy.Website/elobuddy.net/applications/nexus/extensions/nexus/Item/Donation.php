<?php
/**
 * @brief		Donation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Donation
 */
class _Donation extends \IPS\nexus\Invoice\Item\Charge
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'donation';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'money';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'donation';
	
	/**
	 * @brief	Can use coupons?
	 */
	public static $canUseCoupons = FALSE;
	
	/**
	 * @brief	Can use account credit?
	 */
	public static $canUseAccountCredit = FALSE;
	
	/**
	 * On Paid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPaid( \IPS\nexus\Invoice $invoice )
	{
		try
		{
			$goal = \IPS\nexus\Donation\Goal::load( $this->id );
			$goalAmount = new \IPS\Math\Number( (string) $goal->_data['current'] );
			$goalAmount = $goalAmount->add( $this->price->amount );
			$goal->current = (string) $goalAmount;
			$goal->save();
		}
		catch ( \Exception $e ) {}
		
		\IPS\Db::i()->insert( 'nexus_donate_logs', array(
			'dl_goal'	=> $this->id,
			'dl_member'	=> $invoice->member->member_id,
			'dl_amount'	=> $this->price->amount,
			'dl_invoice'=> $invoice->id,
			'dl_date'	=> time()
		) );
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
		
	}
}