<?php
/**
 * @brief		Bandwidth
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Sep 2014
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
 * Bandwidth
 */
class _Bandwidth extends \IPS\nexus\Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'bandwidth';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'cloud-upload';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'bandwidth';
	
	/**
	 * Show Purchase Record?
	 *
	 * @return	bool
	 */
	public function showPurchaseRecord()
	{
		return FALSE;
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function onPurchaseGenerated( \IPS\nexus\Purchase $purchase, \IPS\nexus\Invoice $invoice )
	{
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->parent );
			$account->changeBandWidthLimit( $account->monthlyBandwidthAllowance() + ( $purchase->extra['bwAmount'] * 1000000 ) );
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
		}
		catch ( \OutOfRangeException $e ) { }
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onExpire( \IPS\nexus\Purchase $purchase )
	{
		$purchase->delete();
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onCancel( \IPS\nexus\Purchase $purchase )
	{
		$purchase->delete();
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onDelete( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->parent );
			$account->changeBandWidthLimit( $account->monthlyBandwidthAllowance() - ( $purchase->extra['bwAmount'] * 1000000 ) );
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
		}
		catch ( \OutOfRangeException $e ) { }
	}
}