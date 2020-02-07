<?php
/**
 * @brief		Transactions API
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
 * @brief	Transactions API
 */
class _transactions extends \IPS\Api\Controller
{
	/**
	 * GET /nexus/transactions
	 * Get list of transactions
	 *
	 * @apiparam	string	customers			Comma-delimited list of customer IDs - if provided, only transactions from those customers are returned
	 * @apiparam	string	statuses			Comma-delimited list of statuses - if provided, only transactions with those statuses are returned - see transaction object for status keys
	 * @apiparam	string	gateways			Comma-delimited list of gateway IDs - if provided, only transactions from those gateways are returned
	 * @apiparam	string	sortBy				What to sort by. Can be 'date', 'amount' or do not specify for ID
	 * @apiparam	string	sortDir				Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page				Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\nexus\Transaction>
	 */
	public function GETindex()
	{
		/* Where clause */
		$where = array();
		
		/* Customers */
		if ( isset( \IPS\Request::i()->customers ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'i_member', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->customers ) ) ) ) );
		}
		
		/* Statuses */
		if ( isset( \IPS\Request::i()->statuses ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'i_status', array_filter( explode( ',', \IPS\Request::i()->statuses ) ) ) );
		}

		/* Methods */
		if ( isset( \IPS\Request::i()->gateways ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'i_method', array_filter( explode( ',', \IPS\Request::i()->gateways ) ) ) );
		}
				
		/* Sort */
		if ( isset( \IPS\Request::i()->sortBy ) and in_array( \IPS\Request::i()->sortBy, array( 'date', 'amount' ) ) )
		{
			$sortBy = 't_' . \IPS\Request::i()->sortBy;
		}
		else
		{
			$sortBy = 't_id';
		}
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		/* Return */
		return new \IPS\Api\PaginatedResponse(
			200,
			\IPS\Db::i()->select( '*', 'nexus_transactions', $where, "{$sortBy} {$sortDir}", NULL, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS ),
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			'IPS\nexus\Transaction'
		);
	}
	
	/**
	 * GET /nexus/transactions/{id}
	 * Get information about a specific transaction
	 *
	 * @param		int		$id			ID Number
	 * @throws		2X307/1	INVALID_ID	The transaction ID does not exist
	 * @return		\IPS\nexus\Transaction
	 */
	public function GETitem( $id )
	{
		try
		{			
			return new \IPS\Api\Response( 200, \IPS\nexus\Transaction::load( $id )->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X307/1', 404 );
		}
	}
}