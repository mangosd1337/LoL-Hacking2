<?php
/**
 * @brief		Withdrawals API
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Withdrawals API
 */
class _withdrawals extends \IPS\Api\Controller
{
	/**
	 * GET /nexus/shipments
	 * Get list of shipments
	 *
	 * @apiparam	string	customers			Comma-delimited list of customer IDs - if provided, only invoices belonging to those customers are returned
	 * @apiparam	string	statuses			Comma-delimited list of statuses - if provided, only transactions with those statuses are returned - see payout object for status keys
	 * @apiparam	string	sortBy				What to sort by. Can be 'date', 'completed' (for the date it was completed), 'amount' or do not specify for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page				Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\nexus\Shipping\Order>
	 */
	public function GETindex()
	{
		/* Where clause */
		$where = array();
		
		/* Customers */
		if ( isset( \IPS\Request::i()->customers ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'po_member', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->customers ) ) ) ) );
		}
		
		/* Statuses */
		if ( isset( \IPS\Request::i()->statuses ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'po_status', array_filter( explode( ',', \IPS\Request::i()->statuses ) ) ) );
		}
				
		/* Sort */
		if ( isset( \IPS\Request::i()->sortBy ) and in_array( \IPS\Request::i()->sortBy, array( 'date', 'amount' ) ) )
		{
			$sortBy = 'po_' . \IPS\Request::i()->sortBy;
		}
		else
		{
			$sortBy = 'po_id';
		}
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		/* Return */
		return new \IPS\Api\PaginatedResponse(
			200,
			\IPS\Db::i()->select( '*', 'nexus_payouts', $where, "{$sortBy} {$sortDir}", NULL, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS ),
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			'IPS\nexus\Payout`'
		);
	}
	
	/**
	 * GET /nexus/withdrawals/{id}
	 * Get information about a specific invoice
	 *
	 * @param		int		$id			ID Number
	 * @throws		2X307/1	INVALID_ID	The withdrawal ID does not exist
	 * @return		\IPS\nexus\Payout
	 */
	public function GETitem( $id )
	{
		try
		{			
			return new \IPS\Api\Response( 200, \IPS\nexus\Payout::load( $id )->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X309/1', 404 );
		}
	}
}