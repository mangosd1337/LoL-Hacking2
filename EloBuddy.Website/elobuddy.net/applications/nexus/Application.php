<?php
/**
 * @brief		Nexus Application Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Nexus Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init - catches legacy PayPal IPN messages
	 *
	 * @return	void
	 */
	public function init()
	{
		if ( \IPS\Request::i()->app == 'nexus' and \IPS\Request::i()->module == 'payments' and \IPS\Request::i()->section == 'receive' and \IPS\Request::i()->validate == 'paypal' )
		{
			if ( \IPS\Request::i()->txn_type == 'subscr_payment' and \IPS\Request::i()->payment_status == 'Completed' )
			{
				try
				{
					$saveSubscription = FALSE;

					/* Get the subscription */
					try
					{
						$subscription = \IPS\Db::i()->select( '*', 'nexus_subscriptions', array( 's_gateway_key=? AND s_id=?', 'paypal', \IPS\Request::i()->subscr_id ) )->first();
						$items = \unserialize( $subscription['s_items'] );
						if ( !is_array( $items ) or empty( $items ) )
						{
							/* Don't exit here, throw an underflow exception so we can see if this is a legacy subscription */
							throw new \UnderflowException( 'NO_ITEMS' );
						}
					}
					catch( \UnderflowException $e )
					{
						/* Was this from the old IP.Subscriptions? */
						if ( isset( \IPS\Request::i()->old ) or mb_strstr( \IPS\Request::i()->custom, '-' ) !== FALSE )
						{
							if ( mb_strstr( \IPS\Request::i()->custom, '-' ) !== FALSE )
							{
								$exploded	= explode( '-', \IPS\Request::i()->custom );
								$memberId	= intval( $exploded[0] );
								$purchaseId	= intval( $exploded[1] );

								$item = \IPS\Db::i()->select( '*', 'nexus_purchases', array( "ps_id=?", $purchaseId ), 'ps_id' )->first();
							}
							else
							{
								$memberId	= intval( \IPS\Request::i()->custom );

								$item = \IPS\Db::i()->select( '*', 'nexus_purchases', array( "ps_member=? AND ps_name=?", $memberId, $itemName ), 'ps_start DESC' )->first();
							}
				
							/* If the purchase isn't valid or was cancelled just exit */
							if ( !$item['ps_id'] or $item['ps_cancelled'] )
							{
								exit;
							}

							/* We need $items below, not $item */
							$items = array( $item['ps_id'] );

							/* Grab the first paypal method we can find */
							$method		= \IPS\Db::i()->select( 'm_id', 'nexus_paymethods', array( 'm_gateway=?', 'PayPal' ) )->first();

							/* We are here because the subscription record doesn't exist.  The 3.x code just silently and automatically added one, so do the same now.
								Note that we will need the transaction ID to save the subscription, but we can set the array parameters that will be referenced below. */
							$subscription = array(
								's_gateway_key'	=> 'paypal',
								's_id'			=> \IPS\Request::i()->subscr_id,
								's_start_trans'	=> 0, 	// We will set this later after saving the transaction
								's_method'		=> $method,
								's_member'		=> $memberId,
							);

							$saveSubscription = TRUE;
						}
						else
						{
							/* Just let the exception bubble up and get caught */
							throw $e;
						}
					}
					
					/* If the gateway still exists, fetch it */
					$gateway = NULL;
					try
					{
						$gateway = \IPS\nexus\Gateway::load( $subscription['s_method'] );
					}
					catch ( \OutOfRangeException $e ) { }
										
					/* Check this was actually a PayPal IPN message */					
					try
					{
						$response = \IPS\Http\Url::external( 'https://www.' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr/' )->request()->setHeaders( array( 'Accept' => 'application/x-www-form-urlencoded' ) )->post( array_merge( array( 'cmd' => '_notify-validate' ), $_POST ) );
						if ( (string) $response !== 'VERIFIED' )
						{
							exit;
						}
					}
					catch ( \Exception $e )
					{
						exit;
					}
					
					/* Has an invoice already been generated? */
					$_items = $items;
					try
					{
						$invoice = \IPS\nexus\Invoice::constructFromData( \IPS\Db::i()->select( '*', 'nexus_invoices', array( 'i_member=? AND i_status=?', $subscription['s_member'], 'pend' ), 'i_date DESC', 1 )->first() );
						foreach ( $invoice->items as $item )
						{
							if ( $item instanceof \IPS\nexus\Invoice\Item\Renewal and in_array( $item->id, $_items ) )
							{
								unset( $_items[ array_search( $item->id, $_items ) ] );
							}
						}
					}
					catch ( \UnderflowException $e ) { }
					
					/* No, create one */
					if ( count( $_items ) )
					{
						$invoice = new \IPS\nexus\Invoice;
						$invoice->member = \IPS\nexus\Customer::load( $subscription['s_member'] );
						foreach ( $items as $purchaseId )
						{
							try
							{
								$purchase = \IPS\nexus\Purchase::load( $purchaseId );
								if ( $purchase->renewals and !$purchase->cancelled )
								{
									$invoice->addItem( \IPS\nexus\Invoice\Item\Renewal::create( $purchase ) );
								}
							}
							catch ( \OutOfRangeException $e ) { }
						}
						if ( !count( $invoice->items ) )
						{
							exit;
						}
						$invoice->save();
					}
					
					/* And then log a transaction */
					$transaction = new \IPS\nexus\Transaction;
					$transaction->member = $invoice->member;
					$transaction->invoice = $invoice;
					$transaction->method = $gateway;
					$transaction->amount = new \IPS\nexus\Money( $_POST['mc_gross'], $_POST['mc_currency'] );
					$transaction->gw_id = $_POST['txn_id'];
					$transaction->approve();

					/* If this was a legacy subscription payment, we'll need to save the subscription now that we have a trans ID */
					if( $saveSubscription === TRUE )
					{
						$subscription['s_start_trans']	= $transaction->id;

						\IPS\Db::i()->insert( 'nexus_subscriptions', $subscription );
					}
				}
				catch ( \UnderflowException $e ) { }
			}
			
			exit;
		}
	}
		
	/**
	 * ACP Menu Numbers
	 *
	 * @param	array	$queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( $queryString )
	{
		parse_str( $queryString, $queryString );
		switch ( $queryString['controller'] )
		{
			case 'transactions':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_status=?', \IPS\nexus\Transaction::STATUS_HELD ) )->first();
				break;
			
			case 'shipping':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_ship_orders', array( 'o_status=?', \IPS\nexus\Shipping\Order::STATUS_PENDING ) )->first();
				break;
			
			case 'payouts':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_payouts', array( 'po_status=?', \IPS\nexus\Payout::STATUS_PENDING ) )->first();
				break;
			
			case 'requests':
				$myFilters = \IPS\nexus\Support\Request::myFilters();
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( array( "( dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) . ')' ), $myFilters['whereClause'] ) )->join( 'nexus_support_departments', 'dpt_id=r_department' )->first();
				break;
				
			case 'errors':
				return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_hosting_errors' )->first();
				break;
		}
	}
	
	/**
	 * Cart count
	 *
	 * @return	int
	 */
	public static function cartCount()
	{
		$count = 0;
		foreach ( $_SESSION['cart'] as $item )
		{
			$count += $item->quantity;
		}
		return $count;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'shopping-cart';
	}
	
	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	\IPS\Application\Module	$module			The module
	 * @param	string					$controller		The controller
	 * @param	string|NULL				$do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess( \IPS\Application\Module $module, $controller, $do )
	{
		return $module->key == 'store';
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'		=> array(
				array(
					'key'		=> 'Store',
					'children'	=> array(
						array( 'key' => 'Donations' ),
						array( 'key' => 'Orders' ),
						array( 'key' => 'Purchases' ),
						array(
							'app'		=> 'core',
							'key'		=> 'Menu',
							'title'		=> 'default_menu_item_my_details',
							'children'	=> array(
								array( 'key' => 'Info' ),
								array( 'key' => 'Addresses' ),
								array( 'key' => 'Cards' ),
								array( 'key' => 'BillingAgreements' ),
								array( 'key' => 'Credit' ),
								array( 'key' => 'Alternatives' ),
								array( 'key' => 'Referrals' ),
							)
						)
					),
				),
				array(
					'app'		=> 'core',
					'key'		=> 'CustomItem',
					'title'		=> 'default_menu_item_support',
					'config'	=> array( 'menu_custom_item_url' => 'app=nexus&module=support&controller=home', 'internal' => 'support' ),
					'children'	=> array(
						array( 'key' => 'Support' ),
						array( 'key' => 'NetworkStatus' ),
					)
				)
			),
			'browseTabs'	=> array(),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters()
	{
		/* Support legacy subscriptions */
		if( isset( \IPS\Request::i()->app ) AND \IPS\Request::i()->app == 'subscriptions' )
		{
			/* Redirecting isn't necessary, we just need to route the payment to the appropriate area.
				@see \IPS\nexus\Application */
			\IPS\Request::i()->app			= 'nexus';
			\IPS\Request::i()->module		= 'payments';
			\IPS\Request::i()->section		= 'receive';	/* It actually looks for section=receive, so make sure we set that */
			\IPS\Request::i()->controller	= 'receive';	/* We set this just to be complete in case anywhere else only looks at controller */
			\IPS\Request::i()->validate		= 'paypal';
		}
	}
}