<?php
/**
 * @brief		Dedicated Server Package
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dedicated Server Package
 */
class _Dedi extends \IPS\nexus\Package
{
	/**
	 * @brief	Icon
	 */
	public static $icon = 'database';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'dedicated_server';
	
	/**
	 * Show Purchase Record?
	 *
	 * @return	bool
	 */
	public function showPurchaseRecord()
	{
		return TRUE;
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPurchaseGenerated( \IPS\nexus\Purchase $purchase, \IPS\nexus\Invoice $invoice )
	{	
		try
		{
			$server = \IPS\nexus\Hosting\Server::load( $purchase->name, 'server_hostname' );
			$server->dedicated = $purchase->id;
			$server->save();		
		}
		catch ( \OutOfRangeException $e ) { }
		
		return parent::onPurchaseGenerated( $purchase, $invoice );
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onCancel( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$server = \IPS\nexus\Hosting\Server::load( $purchase->id, 'server_dedicated' );
			$server->dedicated = 0;
			$server->save();		
		}
		catch ( \OutOfRangeException $e ) { }
		
		return parent::onCancel( $purchase );
	}
}