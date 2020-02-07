<?php
/**
 * @brief		Invoice Item Class for Charges
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Invoice\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoice Item Class for Charges
 */
abstract class _Charge extends \IPS\nexus\Invoice\Item
{
	/**
	 * @brief	Act (new/charge)
	 */
	public static $act = 'charge';
	
	/**
	 * @brief	Requires login to purchase?
	 */
	public static $requiresAccount = FALSE;
}