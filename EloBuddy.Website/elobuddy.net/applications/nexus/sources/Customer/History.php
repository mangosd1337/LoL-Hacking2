<?php
/**
 * @brief		Customer History Table
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Customer;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Customer Model
 */
class _History extends \IPS\Helpers\Table\Db
{		
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url	$url			The URL the table will be displayed on
	 * @param	mixed			$where			WHERE clause
	 * @param	bool			$showIp			If the IP address column should be included
	 * @param	bool			$showCustomer	If the customer column should show
	 * @return	void
	 */
	public function __construct( \IPS\Http\Url $url, $where, $showIp=TRUE, $showCustomer=FALSE )
	{
		parent::__construct( 'nexus_customer_history', $url, $where );
		
		$this->tableTemplate	= array( \IPS\Theme::i()->getTemplate( 'customers', 'nexus' ), 'historyTable' );
		$this->rowsTemplate		= array( \IPS\Theme::i()->getTemplate( 'customers', 'nexus' ), 'historyRows' );
		
		$this->include = array( 'log_type', 'log_date', 'log_data' );
		if ( $showIp )
		{
			$this->include[] = 'log_ip_address';
		}
		if ( $showCustomer )
		{
			$this->include[] = 'log_member';
		}		

		$this->sortBy = $this->sortBy ?: 'log_date';
		$this->noSort = array( 'log_type', 'log_ip_address', 'log_data' );
		$this->parsers = array(
			'log_type'	=> function( $val )
			{
				return \IPS\Theme::i()->getTemplate('customers', 'nexus')->logType( $val );
			},
			'log_date'	=> function( $val )
			{
				return \IPS\DateTime::ts( $val );
			},
			'log_ip_address'	=> function( $val )
			{
				return "<a href='" . \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
			},
			'log_member'=> function( $val )
			{
				return \IPS\nexus\Customer::load( $val )->link();
			},
			'log_data'	=> function( $val, $row )
			{
				$val = json_decode( $val, TRUE );
				
				$byCustomer = '';
				$byStaff = '';
				if ( $row['log_by'] )
				{
					if ( $row['log_by'] === $row['log_member'] )
					{
						$byCustomer = \IPS\Member::loggedIn()->language()->addToStack('history_by_customer');
					}

					$byStaff = \IPS\Member::loggedIn()->language()->addToStack('history_by_staff', FALSE, array( 'sprintf' => array( \IPS\Member::load( $row['log_by'] )->name ) ) );
				}
				
				switch ( $row['log_type'] )
				{
					case 'invoice':
						try
						{
							$invoice = \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val['id'] ) );
						}
						catch ( \OutOfRangeException $e )
						{
							$invoice = $val['title'];
						}
						
						if ( isset( $val['type'] ) )
						{
							switch ( $val['type'] )
							{
								case 'status':
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_invoice_status', FALSE, array( 'htmlsprintf' => array( $invoice, mb_strtolower( \IPS\Member::loggedIn()->language()->addToStack( 'istatus_' . $val['new'] ) ), $byStaff ) ) );
									
								case 'resend':
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_invoice_resend', FALSE, array( 'htmlsprintf' => array( $invoice, $byStaff, isset( $val['email'] ) ? \IPS\Member::loggedIn()->language()->addToStack( $val['email'] ? 'history_invoice_resend_email' : 'history_invoice_resend_no_email' ) : '' ) ) );
								
								case 'delete':
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_invoice_delete', FALSE, array( 'htmlsprintf' => array( $invoice, $byStaff ) ) );
									
								case 'expire':
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_invoice_expired', FALSE, array( 'htmlsprintf' => array( $invoice ) ) );
							}
						}
						else
						{
							if ( isset( $val['system'] ) and $val['system'] )
							{
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_invoice_generated', FALSE, array( 'htmlsprintf' => array( $invoice, '' ) ) );
							}
							else
							{
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_invoice_generated', FALSE, array( 'htmlsprintf' => array( $invoice, $byCustomer ?: $byStaff ) ) );
							}
						}
						break;
					
					case 'transaction':
						try
						{
							$transaction = \IPS\Theme::i()->getTemplate('transactions', 'nexus')->link( \IPS\nexus\Transaction::load( $val['id'] ) );
						}
						catch ( \OutOfRangeException $e )
						{
							$transaction = \IPS\Member::loggedIn()->language()->addToStack( 'transaction_number', FALSE, array( 'htmlsprintf' => array( $val['id'] ) ) );
						}
						
						switch ( $val['type'] )
						{
							case 'paid':
								
								try
								{
									$invoice = \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val['invoice_id'] ), TRUE );
								}
								catch ( \OutOfRangeException $e )
								{
									$invoice = \IPS\Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $val['id'] ) ) );
								}
								
								if ( isset( $val['automatic'] ) and $val['automatic'] )
								{
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_auto', FALSE, array( 'htmlsprintf' => array( $transaction, $invoice, \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_status_' . $val['status'] ) ) ) );
								}
								else
								{							
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_paid', FALSE, array( 'htmlsprintf' => array( $transaction, $invoice, $byStaff, \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_status_' . $val['status'] ) ) ) );
								}
								
							case 'status':
								if ( $val['status'] === \IPS\nexus\Transaction::STATUS_REFUNDED or $val['status'] === \IPS\nexus\Transaction::STATUS_PART_REFUNDED )
								{
									if ( $val['refund'] === 'gateway' )
									{
										$refundedTo = ( is_object( $transaction ) and $transaction->method ) ? $transaction->method->_title : \IPS\Member::loggedIn()->language()->addToStack('history_transaction_refunded_gateway');
									}
									else
									{
										$refundedTo = mb_strtolower( \IPS\Member::loggedIn()->language()->addToStack( 'refund_method_credit' ) );
									}
									
									if ( $val['status'] === \IPS\nexus\Transaction::STATUS_REFUNDED )
									{
										return \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_refunded', FALSE, array( 'htmlsprintf' => array( $transaction, $refundedTo, $byStaff ) ) );
									}
									else
									{
										return \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_part_refunded', FALSE, array( 'htmlsprintf' => array( new \IPS\nexus\Money( $val['amount'], $val['currency'] ), $transaction, $refundedTo, $byStaff ) ) );
									}
								}
								else
								{
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_status', FALSE, array( 'htmlsprintf' => array( $transaction, \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_status_' . $val['status'] ), $byStaff ) ) );
								}
							
							case 'delete':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_transaction_delete', FALSE, array( 'htmlsprintf' => array( $transaction, $byStaff ) ) );
						}
						break;
						
					case 'shipping':
						try
						{
							$shipment = \IPS\Theme::i()->getTemplate('shiporders', 'nexus')->link( \IPS\nexus\Shipping\Order::load( $val['id'] ) );
						}
						catch ( \OutOfRangeException $e )
						{
							$shipment = \IPS\Member::loggedIn()->language()->addToStack( 'shipment_number', FALSE, array( 'sprintf' => array( $val['id'] ) ) );
						}
						
						if ( isset( $val['type'] ) )
						{
							if ( $val['type'] === 'new' )
							{
								try
								{
									$invoice = \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val['invoice_id'] ), TRUE );
								}
								catch ( \OutOfRangeException $e )
								{
									$invoice = \IPS\Member::loggedIn()->language()->addToStack('invoice_number', FALSE, array( 'sprintf' => array( $val['id'] ) ) );
								}
								
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_shipping_new', FALSE, array( 'htmlsprintf' => array( $shipment, $invoice ) ) );
							}
							elseif ( $val['type'] === 'canc' )
							{
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_shipping_canc', FALSE, array( 'htmlsprintf' => array( $shipment, $byStaff ) ) );
							}
						}
						elseif ( isset( $val['deleted'] ) and $val['deleted'] )
						{
							return \IPS\Member::loggedIn()->language()->addToStack( 'history_shipping_deleted', FALSE, array( 'htmlsprintf' => array( $shipment, $byStaff ) ) );
						}
						else
						{
							return \IPS\Member::loggedIn()->language()->addToStack( 'history_shipping_ship', FALSE, array( 'htmlsprintf' => array( $shipment, $byStaff ) ) );
						}
						break;
					
					case 'purchase':
						try
						{
							$purchase = \IPS\Theme::i()->getTemplate('purchases', 'nexus')->link( \IPS\nexus\Purchase::load( $val['id'] ), $val['type'] === 'change' );
						}
						catch ( \OutOfRangeException $e )
						{
							$purchase = $val['name'];
						}
						
						switch ( $val['type'] )
						{
							case 'new':
							case 'renew':
								try
								{
									$invoice = \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val['invoice_id'] ), TRUE );
								}
								catch ( \OutOfRangeException $e )
								{
									$invoice = \IPS\Member::loggedIn()->language()->addToStack('invoice_number', FALSE, array( 'sprintf' => array( $val['invoice_id'] ) ) );
								}
								
								if ( $val['type'] === 'new' )
								{
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_created', FALSE, array( 'htmlsprintf' => array( $purchase, $invoice ) ) );
								}
								else
								{
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_renewed', FALSE, array( 'htmlsprintf' => array( $purchase, $invoice ) ) );
								}
								break;
							
							case 'info':
								if ( isset( $val['info'] ) )
								{
									switch ( $val['info'] )
									{
										case 'change_renewals':
											/* A bug in an older version logged this wrong... */
											if ( !isset( $val['to']['currency'] ) )
											{
												foreach ( $val['to']['cost'] as $currency => $amount )
												{
													$to = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $amount['amount'], $currency ), new \DateInterval( 'P' . $val['to']['term'] . mb_strtoupper( $val['to']['unit'] ) ) );
													break;
												}
											}
											/* This is the correct way */
											else
											{
												$to = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $val['to']['cost'], $val['to']['currency'] ), new \DateInterval( 'P' . $val['to']['term']['term'] . mb_strtoupper( $val['to']['term']['unit'] ) ) );
											}
											return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_renewals_changed', FALSE, array( 'htmlsprintf' => array( $purchase, ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff, $to ) ) );
											
										case 'remove_renewals':
											return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_renewals_removed', FALSE, array( 'htmlsprintf' => array( $purchase, ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff ) ) );
									}
								}
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_edited', FALSE, array( 'htmlsprintf' => array( $purchase, ( isset( $val['system'] ) and $val['system'] ) ? '' : $byStaff ) ) );
							
							case 'transfer_from':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_transfer_from', FALSE, array( 'htmlsprintf' => array( $purchase, \IPS\Theme::i()->getTemplate('global', 'nexus')->userLink( \IPS\nexus\Customer::load( $val['to'] ) ), $byStaff ) ) );
							case 'transfer_to':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_transfer_to', FALSE, array( 'htmlsprintf' => array( $purchase, \IPS\Theme::i()->getTemplate('global', 'nexus')->userLink( \IPS\nexus\Customer::load( $val['from'] ) ), $byStaff ) ) );
							
							case 'cancel':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_canceled', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
							case 'uncancel':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_reactivated', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
							
							case 'delete':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_deleted', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
							
							case 'expire':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_expired', FALSE, array( 'htmlsprintf' => array( $purchase ) ) );
							
							case 'change':
								$by = ( isset( $val['system'] ) and $val['system'] ) ? '' : $byCustomer;
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_changed', FALSE, array( 'htmlsprintf' => array( $purchase, $val['old'], $val['name'], $by ) ) );
								
							case 'group':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_grouped', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
							case 'ungroup':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_purchase_ungrouped', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff ) ) );
						}
						break;
						
					case 'comission':
						
						switch ( $val['type'] )
						{
							case 'purchase':
							case 'purchase_refund':
								try
								{
									$purchase = \IPS\Theme::i()->getTemplate('purchases', 'nexus')->link( \IPS\nexus\Purchase::load( $val['id'] ) );
								}
								catch ( \OutOfRangeException $e )
								{
									$purchase = $val['name'];
								}
																
								return \IPS\Member::loggedIn()->language()->addToStack( "history_commission_{$val['type']}", FALSE, array( 'htmlsprintf' => array( new \IPS\nexus\Money( $val['amount'], $val['currency'] ), $purchase ) ) );
							
							case 'invoice':
								try
								{
									$invoice = \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val['invoice_id'] ), TRUE );
								}
								catch ( \OutOfRangeException $e )
								{
									$invoice = \IPS\Member::loggedIn()->language()->addToStack('invoice_number', FALSE, array( 'sprintf' => array( $val['invoice_id'] ) ) );
								}
																
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_commission_invoice', FALSE, array( 'htmlsprintf' => array( new \IPS\nexus\Money( $val['amount'], $val['currency'] ), $invoice ) ) );
							
							case 'bought':
								try
								{
									$invoice = \IPS\Theme::i()->getTemplate('invoices', 'nexus')->link( \IPS\nexus\Invoice::load( $val['invoice_id'] ), TRUE );
								}
								catch ( \OutOfRangeException $e )
								{
									$invoice = \IPS\Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $val['invoice_id'] ) ) );
								}
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_commission_bought', FALSE, array( 'htmlsprintf' => array( new \IPS\nexus\Money( $val['new_amount'], $val['currency'] ), $invoice, new \IPS\nexus\Money( $val['amount'], $val['currency'] ) ) ) );
								
							case 'manual':
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_commission_manual', FALSE, array( 'sprintf' => array( $byStaff, new \IPS\nexus\Money( $val['new'], $val['currency'] ), new \IPS\nexus\Money( $val['old'], $val['currency'] ) ) ) );
						}
					
					case 'giftvoucher':
						
						$currency = isset( $val['currency'] ) ? $val['currency'] : \IPS\nexus\Customer::load( $val['by'] )->defaultCurrency();
						switch ( $val['type'] )
						{
							case 'used':
								/* If the customer who used this gift card no longer exists, then we need to load up a guest customer object to avoid an OutOfRangeException */
								try
								{
									$customer = \IPS\nexus\Customer::load( $val['by'] );
								}
								catch( \OutOfRangeException $e )
								{
									$customer = new \IPS\nexus\Customer;
								}
								
								return 
									\IPS\Member::loggedIn()->language()->addToStack( 'history_giftvoucher_used', FALSE, array( 'htmlsprintf' => array(
									new \IPS\nexus\Money( $val['amount'], $currency ),
									$val['code'],
									\IPS\Theme::i()->getTemplate('global', 'nexus')->userLink( $customer )
								) ) );
								break;
							
							case 'redeemed':
								/* If the customer who redeemed this gift card no longer exists, then we need to load up a guest customer object to avoid an OutOfRangeException */
								try
								{
									$customer = \IPS\nexus\Customer::load( $val['ps_member'] );
								}
								catch( \OutOfRangeException $e )
								{
									$customer = new \IPS\nexus\Customer;
								}
								
								return 
									\IPS\Member::loggedIn()->language()->addToStack( 'history_giftvoucher_redeemed', FALSE, array( 'htmlsprintf' => array(
									new \IPS\nexus\Money( $val['amount'], $currency ),
									$val['code'],
									\IPS\Theme::i()->getTemplate('global', 'nexus')->userLink( $customer ),
									new \IPS\nexus\Money( $val['newCreditAmount'], $currency )
								) ) );
								break;
						}
						
						break;
						
					case 'info':
						$changes = array( \IPS\Member::loggedIn()->language()->addToStack('history_info_change', FALSE, array( 'sprintf' => array( $byCustomer ?: $byStaff ) ) ) );
						
						if ( isset( $val['name'] ) )
						{
							$name = is_array( $val['name'] ) ? implode( ' ', array_values( $val['name'] ) ) : $val['name'];
							$changes[] =  \IPS\Member::loggedIn()->language()->addToStack('history_name_changed_from', FALSE, array( 'sprintf' => array( $name ) ) );
						}
						
						if ( isset( $val['email'] ) )
						{
							$changes[] =  \IPS\Member::loggedIn()->language()->addToStack('history_email_changed_from', FALSE, array( 'sprintf' => array( $val['email'] ) ) );
						}
						
						if ( isset( $val['password'] ) )
						{
							$changes[] =  \IPS\Member::loggedIn()->language()->addToStack('history_password_changed');
						}
						
						if ( isset( $val['other'] ) )
						{
							foreach ( $val['other'] as $change )
							{
								/* Older versions may not have stored the display value, so we need to account for that */
								if( mb_strpos( $change['name'], 'nexus_ccfield_' ) !== FALSE AND is_array( $change['value'] ) )
								{
									try
									{
										/* If it's an array, we will start first by assuming it is probably an address */
										$value = \IPS\GeoLocation::buildFromJson( json_encode( $change['value'] ) )->toString( ', ' );

										/* A bad address will return an empty string */
										if( !$value )
										{
											throw new \BadFunctionCallException;
										}

										$change['value'] = $value;
									}
									/* Maybe it wasn't an address or geoip support is disabled */
									catch( \BadFunctionCallException $e )
									{
										$_value = array();

										/* We will just loop and implode so we have a string */
										foreach( $change['value'] as $k => $v )
										{
											if( is_array( $v ) )
											{
												foreach( $v as $_k => $_v )
												{
													$_value[] = $_k . ': ' . $_v;
												}
											}
											else
											{
												$_value[] = $k . ': ' . $v;
											}
										}

										$value = implode( ', ', $_value );

										$change['value'] = \IPS\Lang::wordbreak( htmlspecialchars( $value, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) );
									}
								}

								if ( isset( $change['old'] ) )
								{
									$changes[] = \IPS\Member::loggedIn()->language()->addToStack('history_field_changed_from', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $change['name'] ), $change['old'], $change['value'] ) ) );
								}
								else
								{
									$changes[] = \IPS\Member::loggedIn()->language()->addToStack('history_field_changed', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $change['name'] ), $change['value'] ) ) );
								}
							}
						}
						
						return implode( '<br>', $changes );
					
					case 'address':
						switch ( $val['type'] )
						{
							case 'add':
								return \IPS\Member::loggedIn()->language()->addToStack('history_address_add', FALSE, array( 'sprintf' => array( (string) \IPS\GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
							case 'edit':
								return \IPS\Member::loggedIn()->language()->addToStack('history_address_edit', FALSE, array( 'sprintf' => array( (string) \IPS\GeoLocation::buildFromJson( $val['old'] ), (string) \IPS\GeoLocation::buildFromJson( $val['new'] ), $byCustomer ) ) );
							case 'primary_billing':
								return \IPS\Member::loggedIn()->language()->addToStack('history_address_primary_billing', FALSE, array( 'sprintf' => array( (string) \IPS\GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
							case 'primary_shipping':
								return \IPS\Member::loggedIn()->language()->addToStack('history_address_primary_shipping', FALSE, array( 'sprintf' => array( (string) \IPS\GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
							case 'delete':
								return \IPS\Member::loggedIn()->language()->addToStack('history_address_delete', FALSE, array( 'sprintf' => array( (string) \IPS\GeoLocation::buildFromJson( $val['details'] ), $byCustomer ) ) );
						}
						break;
					
					
					case 'card':
						switch ( $val['type'] )
						{
							case 'add':
								return \IPS\Member::loggedIn()->language()->addToStack('history_card_add', FALSE, array( 'sprintf' => array( $val['number'], $byCustomer ) ) );
							case 'delete':
								return \IPS\Member::loggedIn()->language()->addToStack('history_card_delete', FALSE, array( 'sprintf' => array( $val['number'], $byCustomer ) ) );
						}
						break;
						
					case 'alternative':
						
						$altContact = \IPS\nexus\Customer::load( $val['alt_id'] );
						$altContact = $altContact->member_id ? $altContact->link() : $val['alt_name'];
						
						switch ( $val['type'] )
						{
							case 'add':
								return \IPS\Member::loggedIn()->language()->addToStack('history_altcontact_add', FALSE, array( 'htmlsprintf' => array( $altContact, $byCustomer ) ) );
							case 'edit':
								return \IPS\Member::loggedIn()->language()->addToStack('history_altcontact_edit', FALSE, array( 'htmlsprintf' => array( $altContact, $byCustomer ) ) );
							case 'delete':
								return \IPS\Member::loggedIn()->language()->addToStack('history_altcontact_delete', FALSE, array( 'htmlsprintf' => array( $altContact, $byCustomer ) ) );
						}
						break;
						
					case 'payout':
						
						try
						{
							if ( !isset( $val['payout_id'] ) )
							{
								throw new \OutOfRangeException;
							}
							$payout = \IPS\Theme::i()->getTemplate('payouts', 'nexus')->link( \IPS\nexus\Payout::load( $val['payout_id'] ) );
						}
						catch ( \OutOfRangeException $e )
						{
							if ( isset( $va['currency'] ) )
							{
								$payout = new \IPS\nexus\Money( $val['amount'], $val['currency'] );
							}
							else
							{
								$payout = $val['amount'];
							}
						}
						
						switch ( $val['type'] )
						{
							case 'autoprocess':
								return \IPS\Member::loggedIn()->language()->addToStack('history_payout_autoprocess', FALSE, array( 'htmlsprintf' => array( $payout ) ) );
							case 'request':
								return \IPS\Member::loggedIn()->language()->addToStack('history_payout_request', FALSE, array( 'htmlsprintf' => array( $payout ) ) );
							case 'cancel':
								return \IPS\Member::loggedIn()->language()->addToStack('history_payout_cancel', FALSE, array( 'htmlsprintf' => array( $payout, $byCustomer ?: $byStaff ) ) );
							case 'processed':
								return \IPS\Member::loggedIn()->language()->addToStack('history_payout_processed', FALSE, array( 'htmlsprintf' => array( $payout, $byStaff ) ) );
							case 'dismissed':
								return \IPS\Member::loggedIn()->language()->addToStack('history_payout_dismissed', FALSE, array( 'htmlsprintf' => array( $payout, $byStaff ) ) );
						}
						break;
						
					case 'lkey':
						
						try
						{
							$purchase = \IPS\Theme::i()->getTemplate('purchases', 'nexus')->link( \IPS\nexus\Purchase::load( $val['ps_id'] ) );
						}
						catch ( \OutOfRangeException $e )
						{
							if ( isset( $val['ps_name'] ) )
							{
								$purchase = $val['ps_name'];
							}
							else
							{
								$purchase = \IPS\Member::loggedIn()->language()->addToStack( 'purchase_number', FALSE, array( 'sprintf' => array( $val['ps_id'] ) ) );
							}
						}
						
						switch ( $val['type'] )
						{
							case 'activated':
								return \IPS\Member::loggedIn()->language()->addToStack('history_lkey_activated', FALSE, array( 'htmlsprintf' => array( $purchase ) ) );
							case 'reset':
								return \IPS\Member::loggedIn()->language()->addToStack('history_lkey_reset', FALSE, array( 'htmlsprintf' => array( $purchase, $byStaff, $val['new'], $val['key'] ) ) );
						}
						
						break;
						
					case 'download':
						switch ( $val['type'] )
						{
							case 'idm':
								try
								{
									$options = array( 'htmlsprintf' => array( \IPS\Theme::i()->getTemplate( 'customers', 'nexus' )->downloadsLink( \IPS\downloads\File::load( $val['id'] ) ) ) );
								}
								catch ( \OutOfRangeException $e )
								{
									$options = array( 'sprintf' => array( $val['name'] ) );
								}
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_download', FALSE, $options );
							case 'attach':
							
								$file = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->attachedFile( \IPS\Settings::i()->base_url . "applications/core/interface/file/attachment.php?id=" . $val['id'], $val['name'], FALSE );
								if ( isset( $val['ps_id'] ) and $val['ps_id'] )
								{
									try
									{
										$options = array( 'htmlsprintf' => array( $file, \IPS\Theme::i()->getTemplate('purchases', 'nexus')->link( \IPS\nexus\Purchase::load( $val['ps_id'] ) ) ) );
									}
									catch ( \OutOfRangeException $e )
									{
										$options = array( 'htmlsprintf' => array( $file, htmlspecialchars( $val['ps_name'], ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ) ) );
									}
									return \IPS\Member::loggedIn()->language()->addToStack( 'history_download_with_purchase', FALSE, $options );
								}
								
								return \IPS\Member::loggedIn()->language()->addToStack( 'history_download', FALSE, array( 'htmlsprintf' => $file ) );
						}
						break;
						
					case 'support':
					
						if ( isset( $val['type'] ) and $val['type'] == 'email' )
						{
							$by = \IPS\Member::loggedIn()->language()->addToStack( 'history_support_by_email' );
						}
						else
						{
							$by = $byCustomer ?: $byStaff;
						}
					
						try
						{
							$options = array( 'htmlsprintf' => array( \IPS\Theme::i()->getTemplate( 'support', 'nexus' )->link( \IPS\nexus\Support\Request::load( $val['id'] ), FALSE, NULL, NULL ), $by ) );
						}
						catch ( \OutOfRangeException $e )
						{
							$options = array( 'sprintf' => array( $val['title'] ), 'htmlsprintf' => array( $by ) );
						}
						
						return \IPS\Member::loggedIn()->language()->addToStack( 'history_support', FALSE, $options );

					case 'billingagreement':
						return \IPS\Member::loggedIn()->language()->addToStack( 'history_billingagreement_' . $val['type'], FALSE, array( 'sprintf' => array( $val['id'], $val['gw_id'], $byCustomer ?: $byStaff ) ) );
				}

				return '';
			}
		);
	}
}