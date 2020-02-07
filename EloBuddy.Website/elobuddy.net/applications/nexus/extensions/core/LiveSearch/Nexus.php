<?php
/**
 * @brief		ACP Live Search Extension
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Live Search Extension
 */
class _Nexus
{	
	/**
	 * Check we have access
	 *
	 * @return	void
	 */
	public function hasAccess()
	{
		return	\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_manage' )
		or 		\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' )
		or		\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' )
		or		\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'requests_manage' )
		or		\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' )
		or		\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_view' );
	}
	
	/**
	 * Get the search results
	 *
	 * @param	string	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( $searchTerm )
	{
		$results = array();
		
		/* Numeric */
		if ( is_numeric( $searchTerm ) )
		{
			/* Invoice */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_manage' ) )
			{
				try
				{
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->invoice( \IPS\nexus\Invoice::load( $searchTerm ) );
				}
				catch ( \OutOfRangeException $e ) { }
			}
			
			/* Transaction */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
			{
				try
				{
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->transaction( \IPS\nexus\Transaction::load( $searchTerm ) );
				}
				catch ( \OutOfRangeException $e ) { }
			}
			
			/* Purchase */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) )
			{
				try
				{
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->purchase( \IPS\nexus\Purchase::load( $searchTerm ) );
				}
				catch ( \OutOfRangeException $e ) { }
			}
						
			/* Support */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'requests_manage' ) )
			{
				try
				{
					$supportRequest = \IPS\nexus\Support\Request::load( $searchTerm );
					if ( $supportRequest->canView() )
					{
						$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->support( $supportRequest );
					}
				}
				catch ( \OutOfRangeException $e ) { }
			}
			
			/* Customer */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' ) )
			{
				try
				{
					$customer = \IPS\nexus\Customer::load( $searchTerm );
					if ( $customer->member_id )
					{
						$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->customer( $customer );
					}
				}
				catch ( \OutOfRangeException $e ) { }
			}
		}
		
		/* Textual */
		else
		{
			/* License Key */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'lkeys_view' ) )
			{
				try
				{
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->licensekey( \IPS\nexus\Purchase\LicenseKey::load( $searchTerm ) );
				}
				catch ( \OutOfRangeException $e ) { }
			}
			
			/* Hosting Accounts */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) )
			{
				try
				{
					$account = \IPS\nexus\Hosting\Account::constructFromData( \IPS\Db::i()->select( '*', 'nexus_hosting_accounts', array( 'LOWER(account_domain)=?', mb_strtolower( $searchTerm ) ) )->first() );
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->hosting( $account );
				}
				catch ( \Exception $e ) {}
				
				try
				{
					$account = \IPS\nexus\Hosting\Account::constructFromData( \IPS\Db::i()->select( '*', 'nexus_hosting_accounts', array( 'LOWER(account_username)=?', mb_strtolower( $searchTerm ) ) )->first() );
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->hosting( $account );
				}
				catch ( \Exception $e ) {}
			}
			
			/* Customers */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_view' ) )
			{
				foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customers', array( "LOWER( name ) LIKE CONCAT( '%', ?, '%' ) OR LOWER( email ) LIKE CONCAT( '%', ?, '%' ) OR CONCAT( LOWER( cm_first_name ), ' ', LOWER( cm_last_name ) ) LIKE CONCAT( '%', ?, '%' )", mb_strtolower( $searchTerm ), mb_strtolower( $searchTerm ), mb_strtolower( $searchTerm ) ), NULL, 50 )->join( 'core_members', 'core_members.member_id=nexus_customers.member_id' ), 'IPS\nexus\Customer' ) as $customer )
				{
					$results[] = \IPS\Theme::i()->getTemplate('livesearch', 'nexus')->customer( $customer );
				}
			}
		}
		
		return $results;
	}
}