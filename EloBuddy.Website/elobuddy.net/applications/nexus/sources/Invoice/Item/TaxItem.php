<?php
/**
 * @brief		Tax Item
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		9 Dec 2015
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
 * Tax Item
 */
class _TaxItem
{
	/**
	 * @brief	Class ID
	 */
	protected $class;

	/**
	 * @brief	Data
	 */
	protected $data;
	
	/**
	 * Constructor
	 *
	 * @param	int		$class	Class ID
	 * @param	array	$data	Data
	 * @return	void
	 */
	public function __construct( $class, $data )
	{
		$this->class = $class;
		$this->data = $data;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse	\IPS\nexus\Tax		class 	The tax class
	 * @apiresponse	float				rate	The rate (for example 0.2 means 20%)
	 * @apiresponse	\IPS\nexus\Money	amount	Amount to pay
	 */
	public function apiOutput()
	{
		return array(
			'class'		=> \IPS\nexus\Tax::load( $this->class )->apiOutput(),
			'rate'		=> $this->data['rate'],
			'amount'	=> $this->data['amount']
		);
	}
}