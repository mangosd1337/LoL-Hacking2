<?php
/**
 * @brief		Dashboard extension: PendingActions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: PendingActions
 */
class _PendingActions
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return  ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' )
		or
		\IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'shiporders_manage' )
		or
		\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_manage' )
		or
		\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'hosting', 'errors_manage' )
		or
		\IPS\Settings::i()->nexus_payout and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_manage' ) );
	}

	/** 
	 * Return the block HTML show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		/* Pending transactions *might* happen some weird way, so always get the count... but only show the count if we have fraud rules set up */
		$pendingTransactions = NULL;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
		{
			$pendingTransactions = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_status=?', \IPS\nexus\Transaction::STATUS_HELD ) )->first();
			if ( !$pendingTransactions and !\IPS\Db::i()->select( 'COUNT(*)', 'nexus_fraud_rules' )->first() )
			{
				$pendingTransactions = NULL;
			}
		}
		
		/* Same with shipments */
		$pendingShipments = NULL;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'shiporders_manage' ) )
		{
			$pendingShipments = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_ship_orders', array( 'o_status=?', \IPS\nexus\Shipping\Order::STATUS_PENDING ) )->first();
			if ( !$pendingShipments and !\IPS\Db::i()->select( 'COUNT(*)', 'nexus_packages_products', 'p_physical=1' )->first() )
			{
				$pendingShipments = NULL;
			}
		}
		
		/* And advertisements */
		$pendingAdvertisements = NULL;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_manage' ) )
		{
			$pendingAdvertisements = \IPS\Db::i()->select( 'COUNT(*)', 'core_advertisements', array( 'ad_active=-1' ) )->first();
			if ( !$pendingAdvertisements and !\IPS\Db::i()->select( 'COUNT(*)', 'nexus_packages_ads' )->first() )
			{
				$pendingAdvertisements = NULL;
			}
		}
		
		/* And hosting errors */
		$hostingErrors = NULL;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'hosting', 'errors_manage' ) )
		{
			$hostingErrors = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_hosting_errors' )->first();
			if ( !$hostingErrors and !\IPS\Db::i()->select( 'COUNT(*)', 'nexus_hosting_servers' )->first() )
			{
				$hostingErrors = NULL;
			}
		}
		
		/* Withdrawals will only be if enabled */
		$pendingWithdrawals = NULL;
		if ( \IPS\Settings::i()->nexus_payout and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_manage' ) )
		{
			$pendingWithdrawals = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_status=?', \IPS\nexus\Payout::STATUS_PENDING ) )->first();
		}
		
		/* Show support if there are departments we can see */
		$openSupportRequests = NULL;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'requests_manage' ) and \IPS\Db::i()->select( '*', 'nexus_support_departments', array( "( dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) . ')' ) ) )
		{
			$myFilters = \IPS\nexus\Support\Request::myFilters();
			$openSupportRequests = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( array( "( dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) . ')' ), $myFilters['whereClause'] ) )->join( 'nexus_support_departments', 'dpt_id=r_department' )->first();
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'widgets.css', 'nexus', 'front' ) );
		
		return \IPS\Theme::i()->getTemplate( 'dashboard', 'nexus' )->pendingActions( $pendingTransactions, $pendingShipments, $pendingWithdrawals, $openSupportRequests, $pendingAdvertisements, $hostingErrors );
	}

	/** 
	 * Return the block information
	 *
	 * @return	array	array( 'name' => 'Block title', 'key' => 'unique_key', 'size' => [1,2,3], 'by' => 'Author name' )
	 */
	public function getInfo()
	{
		return array();
	}

	/**
	 * Save the block data submitted.  This method is only necessary if your block accepts some sort of submitted data to save (such as the 'admin notes' block).
	 *
	 * @return	void
	 * @throws	\LogicException
	 */
	public function saveBlock()
	{
	}
}