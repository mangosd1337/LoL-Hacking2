<?php
/**
 * @brief		Test Gateway
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Test Gateway
 */
class _Test extends \IPS\nexus\Gateway
{
	/* !Features */
	
	const SUPPORTS_REFUNDS = FALSE;
	const SUPPORTS_PARTIAL_REFUNDS = FALSE;
	
	/* !Payment Gateway */
	
	/**
	 * Payment Screen Fields
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	Invoice
	 * @param	\IPS\nexus\Money	$amount		The amount to pay now
	 * @param	\IPS\Member			$member		The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param	array				$recurrings	Details about recurring costs
	 * @return	array
	 */
	public function paymentScreen( \IPS\nexus\Invoice $invoice, \IPS\nexus\Money $amount, \IPS\Member $member = NULL, $recurrings = array() )
	{
		return array();
	}
	
	/**
	 * Capture
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\LogicException
	 */
	public function capture( \IPS\nexus\Transaction $transaction )
	{
		
	}
	
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function settings( &$form )
	{
		
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array	$settings	Settings
	 * @return	array
	 * @throws	\InvalidArgumentException
	 */
	public function testSettings( $settings )
	{
		return $settings;
	}
}