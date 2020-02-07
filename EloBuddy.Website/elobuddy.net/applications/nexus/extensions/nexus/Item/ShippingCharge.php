<?php
/**
 * @brief		Invoice Item Class for Shipping Charges
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		20 Mar 2014
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
 * Invoice Item Class for Shipping Charges
 */
class _ShippingCharge extends \IPS\nexus\Invoice\Item\Charge
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'shipping';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'truck';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'checkout_shipping';
}