<?php
/**
 * @brief		Invoices
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invoices
 */
class _invoices extends \IPS\Dispatcher\Controller
{	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_manage' );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'invoice.css', 'nexus', 'admin' ) );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		/* Table */
		$url = \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=invoices' );
		$where = array();
		$customer = NULL;
		if ( isset( \IPS\Request::i()->member ) )
		{
			try
			{
				$customer = \IPS\nexus\Customer::load( \IPS\Request::i()->member );
				$url = $url->setQueryString( 'member', $customer->member_id );
				$where[] = array( 'i_member=?', $customer->member_id );
			}
			catch ( \OutOfRangeException $e ) { }
		}
		$table = \IPS\nexus\Invoice::table( $where, $url, 't' );
		$table->advancedSearch = array(
			'i_id'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'i_title'	=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'i_status'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => \IPS\nexus\Invoice::statuses(), 'multiple' => TRUE ) ),
			'i_member'	=> \IPS\Helpers\Table\SEARCH_MEMBER,
			'i_total'	=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			'i_date'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
		);
		$table->quickSearch = 'i_id';
		if ( $customer )
		{
			unset( $table->advancedSearch['i_member'] );
		}
				
		/* Action Buttons */
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_add' ) )
		{
			$generateUrl = \IPS\Http\Url::internal( "app=nexus&module=payments&controller=invoices&do=generate&_new=1" );
			
			if ( $customer )
			{
				$generateUrl = $generateUrl->setQueryString( 'member', $customer->member_id );
			}
			
			\IPS\Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'plus',
				'title'	=> 'generate_invoice',
				'link'	=> $generateUrl
			);
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_settings' ) and !$customer )
		{
			\IPS\Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'cog',
				'title'	=> 'invoice_settings',
				'link'	=> \IPS\Http\Url::internal( "app=nexus&module=payments&controller=invoices&do=settings" )
			);
		}
		
		/* Display */
		\IPS\Output::i()->title		= $customer ? \IPS\Member::loggedIn()->language()->addToStack( 'members_invoices', FALSE, array( 'sprintf' => array( $customer->cm_name ) ) ) : \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_payments_invoices');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view()
	{
		/* Load Invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/3', 404, '' );
		}
				
		/* Get transactions */
		$transactions = NULL;
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) and ( !isset( \IPS\Request::i()->table ) ) )
		{
			$transactions = \IPS\nexus\Transaction::table( array( array( 't_invoice=?', $invoice->id ) ), $invoice->acpUrl(), 'i' );
			$transactions->limit = 50;
			$transactions->tableTemplate = array( \IPS\Theme::i()->getTemplate('invoices'), 'transactionsTable' );
			$transactions->rowsTemplate = array( \IPS\Theme::i()->getTemplate('invoices'), 'transactionsTableRows' );

			foreach ( $transactions->include as $k => $v )
			{
				if ( in_array( $v, array( 't_member', 't_invoice' ) ) )
				{
					unset( $transactions->include[ $k ] );
				}
			}
		}
		
		/* Get shipments */
		$shipments = NULL;
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'shiporders_manage' ) )
		{
			if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_ship_orders', array( 'o_invoice=?', $invoice->id ) )->first() )
			{
				$shipments = new \IPS\Helpers\Table\Db( 'nexus_ship_orders', $invoice->acpUrl()->setQueryString( 'table', 'shipments' ), array( 'o_invoice=?', $invoice->id ) );
				$shipments->sortBy = $shipments->sortBy ?: 'o_shipped_date';
				$shipments->include = array( 'o_status', 'o_method', 'o_shipped_date' );
				$shipments->limit = 50;
				$shipments->tableTemplate = array( \IPS\Theme::i()->getTemplate('invoices'), 'shipmentsTable' );
				$shipments->rowsTemplate = array( \IPS\Theme::i()->getTemplate('invoices'), 'shipmentsTableRows' );

				$shipments->parsers = array(
					'o_status'	=> function( $val )
					{						
						return \IPS\Theme::i()->getTemplate('shiporders')->status( $val );
					},
					'o_method'	=> function( $val, $row )
					{
						if ( $row['o_api_service'] )
						{
							return $row['o_api_service'];
						}
						
						try
						{
							return \IPS\nexus\Shipping\FlatRate::load( $val )->_title;
						}
						catch ( \Exception $e )
						{
							return '';
						}
					},
					'o_shipped_date'	=> function( $val )
					{
						return $val ? ( (string) \IPS\DateTime::ts( $val ) ) : \IPS\Member::loggedIn()->language()->addToStack('not_shipped_yet');
					}
				);
				$shipments->rowButtons = function( $row )
				{
					return array_merge( array(
						'view'	=> array(
							'icon'	=> 'search',
							'title'	=> 'shipment_view',
							'link'	=> \IPS\Http\Url::internal( "app=nexus&module=payments&controller=shipping&do=view&id={$row['o_id']}" )
						),
					), \IPS\nexus\Shipping\Order::constructFromData( $row )->buttons( 'i' ) );
				};
			}
		}

		/* Add Buttons */
		\IPS\Output::i()->sidebar['actions'] = $invoice->buttons( 'v' );
		
		/* Output */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'invoice_number', FALSE, array( 'sprintf' => array( $invoice->id ) ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'invoices' )->view( $invoice, $invoice->summary(), $transactions, (string) $shipments );
	}
	
	/**
	 * Paid
	 *
	 * @return	void
	 */
	public function paid()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_edit' );
		
		/* Load Invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/6', 404, '' );
		}
		
		/* Any pending transactions? */
		if ( !isset( \IPS\Request::i()->override ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) )
		{
			$pendingTransactions = $invoice->transactions( array( \IPS\nexus\Transaction::STATUS_WAITING, \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW ) );
			if ( count( $pendingTransactions ) )
			{
				$transUrl = $invoice->acpUrl();
				if ( count( $pendingTransactions ) === 1 )
				{
					foreach ( $pendingTransactions as $transaction )
					{
						$transUrl = $transaction->acpUrl();
					}
				}
				
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global', 'core')->decision( 'invoice_paid_trans', array(
					'invoice_paid_trans_view'	=> $transUrl,
					'invoice_paid_trans_ovrd'	=> $invoice->acpUrl()->setQueryString( array( 'do' => 'paid', 'override' => 1 ) )
				) );
				return;
			}
		}
		
		/* Log (do this first so the log appears in the correct order) */
		$invoice->member->log( 'invoice', array(
			'type'	=> 'status',
			'new'	=> \IPS\nexus\Invoice::STATUS_PAID,
			'id'	=> $invoice->id,
			'title' => $invoice->title
		) );
		
		/* Send Email */
		\IPS\Email::buildFromTemplate( 'nexus', 'invoiceMarkedPaid', array( $invoice, $invoice->summary() ), \IPS\Email::TYPE_TRANSACTIONAL )
			->send(
				$invoice->member,
				array_map(
					function( $contact )
					{
						return $contact->alt_id->email;
					},
					iterator_to_array( $invoice->member->alternativeContacts( array( 'billing=1' ) ) )
				),
				( ( in_array( 'new_invoice', explode( ',', \IPS\Settings::i()->nexus_notify_copy_types ) ) AND \IPS\Settings::i()->nexus_notify_copy_email ) ? explode( ',', \IPS\Settings::i()->nexus_notify_copy_email ) : array() )
			);
		
		/* Do it */
		$invoice->markPaid( \IPS\Member::loggedIn() );
		
		/* Redirect */
		$this->_redirect( $invoice );
	}
	
	/**
	 * Charge to card
	 *
	 * @return	void
	 */
	public function card()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'chargetocard' );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'customer.css', 'nexus', 'admin' ) );

		/* Load Invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/8', 404, '' );
		}
		
		/* Get gateways */
		$gateways = \IPS\nexus\Gateway::manualChargeGateways();
		
		/* Can we do this? */
		if ( $invoice->status !== \IPS\nexus\Invoice::STATUS_PENDING or !count( $gateways ) )
		{
			\IPS\Output::i()->error( 'invoice_status_err', '2X190/9', 403, '' );
		}

		$self = $this;
		/* Wizard */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'invoice_charge_to_card' );
		\IPS\Output::i()->output = new \IPS\Helpers\Wizard(
			array(
				't_amount'		=> function( $data ) use ( $invoice )
				{					
					$amountToPay = $invoice->amountToPay()->amount;
					$form = new \IPS\Helpers\Form( 'amount', 'continue' );
					$form->add( new \IPS\Helpers\Form\Number( 't_amount', $amountToPay, TRUE, array( 'min' => 0.01, 'max' => (string) $amountToPay, 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
					if ( $values = $form->values() )
					{
						return $values;
					}
					return $form;
				},
				'checkout_pay'	=> function( $data ) use ( $invoice, $gateways, $self )
				{
					$amountToPay = new \IPS\nexus\Money( $data['t_amount'], $invoice->currency );
					
					/* Get elements */
					$elements = array();
					$paymentMethodsToggles = array();
					foreach ( $gateways as $gateway )
					{
						foreach ( $gateway->paymentScreen( $invoice, $amountToPay, $invoice->member ) as $element )
						{
							if ( !$element->htmlId )
							{
								$element->htmlId = $gateway->id . '-' . $element->name;
							}
							$elements[] = $element;
							$paymentMethodsToggles[ $gateway->id ][] = $element->htmlId;
						}
					}
					$paymentMethodOptions = array();
					foreach ( $gateways as $k => $v )
					{
						$paymentMethodOptions[ $k ] = $v->_title;
					}
					
					/* Build form */
					$form = new \IPS\Helpers\Form( 'charge', 'invoice_charge_to_card' );
					if ( count( $gateways ) > 1 )
					{
						$form->add( new \IPS\Helpers\Form\Radio( 'payment_method', NULL, TRUE, array( 'options' => $paymentMethodOptions, 'toggles' => $paymentMethodsToggles ) ) );
					}
					foreach ( $elements as $element )
					{
						$form->add( $element );
					}
						
					/* Handle submissions */
					if ( $values = $form->values() )
					{
						if ( count( $gateways ) === 1 )
						{
							$gateway = array_pop( $gateways );
						}
						else
						{
							$gateway = $gateways[ $values['payment_method'] ];
						}
						
						$transaction = new \IPS\nexus\Transaction;
						$transaction->member = $invoice->member;
						$transaction->invoice = $invoice;
						$transaction->method = $gateway;
						$transaction->amount = $amountToPay;						
						$transaction->currency = $invoice->currency;
						$transaction->ip = \IPS\Request::i()->ipAddress();
						$transaction->extra = array( 'admin' => \IPS\Member::loggedIn()->member_id );
						
						try
						{
							$transaction->auth = $gateway->auth( $transaction, $values );
							$transaction->capture();
							
							$transaction->member->log( 'transaction', array(
								'type'			=> 'paid',
								'status'		=> \IPS\nexus\Transaction::STATUS_PAID,
								'id'			=> $transaction->id,
								'invoice_id'	=> $invoice->id,
								'invoice_title'	=> $invoice->title,
							) );
							
							$transaction->approve();
							
							$transaction->sendNotification();
							
							$self->_redirect( $invoice );
						}
						catch ( \Exception $e )
						{
							$form->error = $e->getMessage();
							return $form;
						}						
					}
					
					/* Display form */
					return $form;
				}

			),
			$invoice->acpUrl()->setQueryString( 'do', 'card' )
		);
	}
	
	/**
	 * Charge to account credit
	 *
	 * @return	void
	 */
	public function credit()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_edit' );
		
		/* Load Invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/A', 404, '' );
		}
				
		/* Can we do this? */
		if ( $invoice->status !== \IPS\nexus\Invoice::STATUS_PENDING )
		{
			\IPS\Output::i()->error( 'invoice_status_err', '2X190/B', 403, '' );
		}
		
		/* How much can we do? */
		$amountToPay = $invoice->amountToPay()->amount;
		$credits = $invoice->member->cm_credits;
		$credit = $credits[ $invoice->currency ]->amount;
		$maxCanCharge = ( $credit->compare( $amountToPay ) === -1 ) ? $credit : $amountToPay;

		/* Build Form */
		$form = new \IPS\Helpers\Form( 'amount', 'invoice_charge_to_credit' );
		$form->add( new \IPS\Helpers\Form\Number( 't_amount', $maxCanCharge, TRUE, array( 'min' => 0.01, 'max' => (string) $maxCanCharge, 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{			
			$transaction = new \IPS\nexus\Transaction;
			$transaction->member = $invoice->member;
			$transaction->invoice = $invoice;
			$transaction->amount = new \IPS\nexus\Money( $values['t_amount'], $invoice->currency );
			$transaction->ip = \IPS\Request::i()->ipAddress();
			$transaction->extra = array( 'admin' => \IPS\Member::loggedIn()->member_id );
			$transaction->save();
			$transaction->approve( NULL );
			$transaction->sendNotification();
			
			$credits[ $invoice->currency ]->amount = $credits[ $invoice->currency ]->amount->subtract( $transaction->amount->amount );
			$invoice->member->cm_credits = $credits;
			$invoice->member->save();
			
			$invoice->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> \IPS\nexus\Transaction::STATUS_PAID,
				'id'			=> $transaction->id,
				'invoice_id'	=> $invoice->id,
				'invoice_title'	=> $invoice->title,
			) );
			
			$this->_redirect( $invoice );
		}
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'invoice_charge_to_credit' );
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Reissue
	 *
	 * @return	void
	 */
	public function resend()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_resend' );
		
		/* Load Invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/C', 404, '' );
		}
		
		/* Update */
		$invoice->date = new \IPS\DateTime;
		$invoice->status = \IPS\nexus\Invoice::STATUS_PENDING;
		$invoice->save();
		
		/* Send email */
		$emailSent = FALSE;
		if ( isset( \IPS\Request::i()->prompt ) and \IPS\Request::i()->prompt )
		{
			$emailSent = TRUE;
			$invoice->sendNotification();
		}
		
		/* Log */
		$invoice->member->log( 'invoice', array( 'type' => 'resend', 'id' => $invoice->id, 'title' => $invoice->title, 'email' => $emailSent ) );
		
		/* Redirect */
		$this->_redirect( $invoice );
	}
	
	/**
	 * Print
	 *
	 * @return	void
	 */
	public function printout()
	{
		/* Load */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/D', 404, '' );
		}
		
		/* Get output */
		$output = \IPS\Theme::i()->getTemplate( 'invoices', 'nexus', 'global' )->printInvoice( $invoice, $invoice->summary() );
		\IPS\Output::i()->title = 'I' . $invoice->id;
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->blankTemplate( $output ) );
	}
	
	/**
	 * Unpaid
	 *
	 * @return	void
	 */
	public function unpaid()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_edit' );
		
		/* Load Invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/7', 404, '' );
		}
						
		/* Get paid transactions */
		$transactions = $invoice->transactions( array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED ) );
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		
		/* Ask what we want to do with the transactions */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_refund' ) )
		{
			foreach ( $transactions as $transaction )
			{
				/* What refund options are available? */
				$method = $transaction->method;
				$refundMethods = array();
				$refundMethodToggles = array( 'credit' => array( $transaction->id . '_refund_amount' ) );
				if ( $method and $method::SUPPORTS_REFUNDS )
				{
					$refundMethods['gateway'] = $method->_title;
					if ( $method::SUPPORTS_PARTIAL_REFUNDS )
					{
						$refundMethodToggles['gateway'] = array( $transaction->id . '_refund_amount' );
					}
				}
				$refundMethods['credit'] = 'refund_method_credit';
				$refundMethods['none'] = 'refund_method_none';
				
				/* How do we want to refund? */
				$field = new \IPS\Helpers\Form\Radio( $transaction->id . '_refund_method', 'none', TRUE, array( 'options' => $refundMethods, 'toggles' => $refundMethodToggles ) );
				$field->label = count( $transactions ) === 1 ? \IPS\Member::loggedIn()->language()->addToStack( 'refund_method' ) : \IPS\Member::loggedIn()->language()->addToStack( 'trans_refund_method', FALSE, array( 'sprintf' => array( $transaction->id ) ) );
				$form->add( $field );
				
				/* Partial refund? */
				$field = new \IPS\Helpers\Form\Number( $transaction->id . '_refund_amount', ( $transaction->status === \IPS\nexus\Transaction::STATUS_PART_REFUNDED ? (string) $transaction->amount->amount->subtract( $transaction->partial_refund->amount ) : 0 ), TRUE, array( 'unlimited' => 0, 'unlimitedLang' => \IPS\Member::loggedIn()->language()->addToStack( 'refund_full', FALSE, array( 'sprintf' => array( $transaction->amount ) ) ), 'max' => (string) $transaction->amount->amount->subtract( $transaction->partial_refund->amount ), 'decimals' => TRUE ), NULL, NULL, $transaction->amount->currency, $transaction->id . '_refund_amount' );
				$field->label = \IPS\Member::loggedIn()->language()->addToStack( 'refund_amount' );
				$form->add( $field );
			}
		}
		
		/* Do we want to mark the invoice as pending or canceled? */
		if ( $invoice->status === \IPS\nexus\Invoice::STATUS_PAID )
		{
			$statusOptions = array();
			if ( !$invoice->total->amount->isZero() )
			{
				$statusOptions[ \IPS\nexus\Invoice::STATUS_PENDING ] = 'refund_invoice_pending';
			}
			if ( \IPS\Settings::i()->cm_invoice_expireafter )
			{
				$statusOptions[ \IPS\nexus\Invoice::STATUS_EXPIRED ] = 'refund_invoice_expired';
			}
			$statusOptions[ \IPS\nexus\Invoice::STATUS_CANCELED ] = 'refund_invoice_canceled';
			$field = new \IPS\Helpers\Form\Radio( 'refund_invoice_status', \IPS\nexus\Invoice::STATUS_CANCELED, TRUE, array( 'options' => $statusOptions ) );
			$field->warningBox = \IPS\Theme::i()->getTemplate('invoices')->unpaidConsequences( $invoice );
			$form->add( $field );
		}
		else
		{
			$statusOptions = array();
			if ( \IPS\Settings::i()->cm_invoice_expireafter )
			{
				$statusOptions[ \IPS\nexus\Invoice::STATUS_EXPIRED ] = 'invoice_status_expd';
			}
			$statusOptions[ \IPS\nexus\Invoice::STATUS_CANCELED ] = 'invoice_status_canc';
			$form->add( new \IPS\Helpers\Form\Radio( 'refund_invoice_status', \IPS\nexus\Invoice::STATUS_CANCELED, TRUE, array( 'options' => $statusOptions ) ) );
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Refund transactions */
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_refund' ) )
			{
				foreach ( $transactions as $transaction )
				{
					try
					{
						$transaction->refund( $values[ $transaction->id . '_refund_method' ], $values[ $transaction->id . '_refund_amount' ] );
					}
					catch ( \LogicException $e )
					{
						\IPS\Output::i()->error( $e->getMessage(), '1X190/1', 500, '' );
					}
					catch ( \RuntimeException $e )
					{
						\IPS\Output::i()->error( 'refund_failed', '3X190/2', 500, '' );
					}
				}
			}
			
			/* Log */
			$invoice->member->log( 'invoice', array(
				'type'	=> 'status',
				'new'	=> $values['refund_invoice_status'],
				'id'	=> $invoice->id,
				'title' => $invoice->title
			) );
			
			/* Change invoice status */
			$invoice->markUnpaid( $values['refund_invoice_status'], \IPS\Member::loggedIn() );
			
			/* Boink */
			$this->_redirect( $invoice );
		}
		
		/* Display */
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * PO Number
	 *
	 * @return	void
	 */
	public function poNumber()
	{
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/4', 404, '' );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'invoice_po_number', $invoice->po, FALSE, array( 'maxLength' => 255 ) ) );
		if ( $values = $form->values() )
		{
			$invoice->po = $values['invoice_po_number'];
			$invoice->save();
			$this->_redirect( $invoice );
		}
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Notes
	 *
	 * @return	void
	 */
	public function notes()
	{
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/5', 404, '' );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\TextArea( 'invoice_notes', $invoice->notes ) );
		if ( $values = $form->values() )
		{
			$invoice->notes = $values['invoice_notes'];
			$invoice->save();
			$this->_redirect( $invoice );
		}
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Load Transaction */
		try
		{
			$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X190/E', 404, '' );
		}
				
		/* Log it */
		$invoice->member->log( 'invoice', array(
			'type'		=> 'delete',
			'id'		=> $invoice->id,
			'title'		=> $invoice->title
		) );
		
		/* Delete */
		$invoice->delete();
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=invoices') );
	}
	
	/**
	 * Generate
	 *
	 * @return	void
	 */
	public function generate()
	{
		/* Init */
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_add' );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_store.js', 'nexus', 'admin' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('generate_invoice');
		$url = \IPS\Http\Url::internal("app=nexus&module=payments&controller=invoices&do=generate");
		if ( isset( \IPS\Request::i()->member ) )
		{
			$url = $url->setQueryString( 'member', \IPS\Request::i()->member );
		}
			
		/* Select Customer */	
		$steps = array();
		if ( !isset( \IPS\Request::i()->member ) )
		{
			$steps['invoice_generate_member'] = function( $data )
			{
				$form = new \IPS\Helpers\Form('customer', 'continue');
				$form->add( new \IPS\Helpers\Form\Member( 'invoice_generate_member', NULL, TRUE ) );
				if ( $values = $form->values() )
				{
					return array( 'member' => $values['invoice_generate_member']->member_id );
				}
				return $form;
			};
		}
		
		/* Select Addresses */
		$steps['invoice_generate_settings'] = function( $data )
		{
			$customer = \IPS\nexus\Customer::load( isset( \IPS\Request::i()->member ) ? \IPS\Request::i()->member : $data['member'] );
			
			$form = new \IPS\Helpers\Form('settings', 'continue');
			
			$form->addHeader( 'invoice_settings' );
			$currencies = \IPS\nexus\Money::currencies();
			if ( count( $currencies ) > 1 )
			{
				$form->add( new \IPS\Helpers\Form\Radio( 'currency', $customer->defaultCurrency(), TRUE, array( 'options' => array_combine( $currencies, $currencies ) ) ) );
			}
			$statusOptions = array();
			$statusOptions[ \IPS\nexus\Invoice::STATUS_PAID ] = 'invoice_status_paid';
			$statusOptions[ \IPS\nexus\Invoice::STATUS_PENDING ] = 'invoice_status_pend';
			if ( \IPS\Settings::i()->cm_invoice_expireafter )
			{
				$statusOptions[ \IPS\nexus\Invoice::STATUS_EXPIRED ] = 'invoice_status_expd';
			}
			$statusOptions[ \IPS\nexus\Invoice::STATUS_CANCELED ] = 'invoice_status_canc';
			$form->add( new \IPS\Helpers\Form\Radio( 'invoice_status', \IPS\nexus\Invoice::STATUS_PENDING, TRUE, array( 'options' => $statusOptions ) ) );					
			$form->add( new \IPS\Helpers\Form\Text( 'invoice_title', NULL, FALSE ) );
			$form->add( new \IPS\Helpers\Form\Text( 'invoice_po_number', NULL, FALSE, array( 'maxLength' => 255 ) ) );
			$form->add( new \IPS\Helpers\Form\TextArea( 'invoice_notes', NULL ) );
			
			$form->addHeader( 'invoice_generate_addresses' );
			$addresses = \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=?', isset( \IPS\Request::i()->member ) ? \IPS\Request::i()->member : $data['member'] ) );
			if ( count( $addresses ) )
			{
				$billing = NULL;
				$shipping = 'x';
				$options = array();
				foreach ( new \IPS\Patterns\ActiveRecordIterator( $addresses, 'IPS\nexus\Customer\Address' ) as $address )
				{
					$options[ $address->id ] = (string) $address->address;
					if ( $address->primary_billing )
					{
						$billing = $address->id;
					}
					if ( $address->primary_shipping )
					{
						$shipping = $address->id;
					}
				}
				$options[0] = 'other';
				
				$form->add( new \IPS\Helpers\Form\Radio( 'billing_address', $billing, TRUE, array( 'options' => $options, 'toggles' => array( 0 => array( 'new_billing_address' ) ) ) ) );
				$newAddress = new \IPS\Helpers\Form\Address( 'new_billing_address', NULL, FALSE, array(), NULL, NULL, NULL, 'new_billing_address' );
				$newAddress->label = ' ';
				$form->add( $newAddress );
				
				$form->add( new \IPS\Helpers\Form\Radio( 'shipping_address', ( $shipping == $billing ) ? 'x' : $shipping, TRUE, array( 'options' => array( 'x' => 'same_as_billing_address' ) + $options, 'toggles' => array( 0 => array( 'new_shipping_address' ) ) ) ) );
				$newAddress = new \IPS\Helpers\Form\Address( 'new_shipping_address', NULL, FALSE, array(), NULL, NULL, NULL, 'new_shipping_address' );
				$newAddress->label = ' ';
				$form->add( $newAddress );
			}
			else
			{
				$form->add( new \IPS\Helpers\Form\Address( 'new_billing_address', NULL, FALSE ) );
				$form->add( new \IPS\Helpers\Form\Radio( 'shipping_address', 'x', FALSE, array( 'options' => array( 'x' => 'same_as_billing_address', '1' => 'other' ), 'toggles' => array( '1' => array( 'new_shipping_address' ) ) ) ) );
				$form->add( new \IPS\Helpers\Form\Address( 'new_shipping_address', NULL, FALSE, array(), NULL, NULL, NULL, 'new_shipping_address' ) );
			}
			
			if ( $values = $form->values() )
			{
				if ( count( $addresses ) and $values['billing_address'] )
				{
					$data['billaddress'] = \IPS\nexus\Customer\Address::load( $values['billing_address'] )->address;
				}
				else
				{
					if( $values['new_billing_address'] === NULL OR empty( $values['new_billing_address']->addressLines ) or !$values['new_billing_address']->city or !$values['new_billing_address']->country or ( !$values['new_billing_address']->region and array_key_exists( $values['new_billing_address']->country, \IPS\GeoLocation::$states ) ) or !$values['new_billing_address']->postalCode )
					{
						$data['billaddress'] = NULL;
					}
					else
					{
						$data['billaddress'] = $values['new_billing_address'];
					}
				}

				if ( count( $addresses ) and $values['shipping_address'] and $values['shipping_address'] !== 'x' )
				{
					$data['shipaddress'] = \IPS\nexus\Customer\Address::load( $values['shipping_address'] )->address;
				}
				elseif ( $values['shipping_address'] === 'x' )
				{
					$data['shipaddress'] = $data['billaddress'];
				}
				else
				{
					if( $values['new_shipping_address'] === NULL OR empty( $values['new_shipping_address']->addressLines ) or !$values['new_shipping_address']->city or !$values['new_shipping_address']->country or ( !$values['new_shipping_address']->region and array_key_exists( $values['new_shipping_address']->country, \IPS\GeoLocation::$states ) ) or !$values['new_shipping_address']->postalCode )
					{
						$data['shipaddress'] = NULL;
					}
					else
					{
						$data['shipaddress'] = $values['new_shipping_address'];
					}
				}
								
				$data['currency'] = isset( $values['currency'] ) ? $values['currency'] : $customer->defaultCurrency();
				$data['status'] = $values['invoice_status'];
				$data['title'] = $values['invoice_title'];
				$data['po_number'] = $values['invoice_po_number'];
				$data['notes'] = $values['invoice_notes'];
				
				return $data;
			}
			
			return $form;
		};
		
		/* Add Items */
		$steps['invoice_generate_items'] = function( $data ) use ( $url )
		{
			$invoice = new \IPS\nexus\Invoice;
			$invoice->member = \IPS\Member::load( isset( \IPS\Request::i()->member ) ? \IPS\Request::i()->member : $data['member'] );
			$invoice->currency = $data['currency'];
			if ( $data['billaddress'] )
			{
				$invoice->billaddress = $data['billaddress'];
			}
			if ( $data['shipaddress'] )
			{ 	
				$invoice->shipaddress = $data['shipaddress'];
			}
			$invoice->items = isset( $data['items'] ) ? json_encode( $data['items'] ) : json_encode( array() );			
																		
			if ( isset( \IPS\Request::i()->continue ) )
			{
				$invoice->recalculateTotal();

				if ( $data['title'] )
				{
					$invoice->title = $data['title'];
				}
				if ( $data['po_number'] )
				{
					$invoice->po = $data['po_number'];
				}
				if ( $data['notes'] )
				{
					$invoice->notes = $data['notes'];
				}
				
				if ( $data['status'] === \IPS\nexus\Invoice::STATUS_PAID )
				{
					$invoice->status = \IPS\nexus\Invoice::STATUS_PENDING;
					$invoice->save();
					
					$invoice->member->log( 'invoice', array(
						'type'	=> 'status',
						'new'	=> \IPS\nexus\Invoice::STATUS_PAID,
						'id'	=> $invoice->id,
						'title' => $invoice->title
					) );
					$invoice->markPaid();
				}
				else
				{
					$invoice->status = $data['status'];
					$invoice->save();
				}
				
				$invoice->sendNotification();
				
				\IPS\Output::i()->redirect( $invoice->acpUrl() );
			}
			elseif ( isset( \IPS\Request::i()->remove ) )
			{
				unset( $data['items'][ \IPS\Request::i()->remove ] );
				$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;
				\IPS\Output::i()->redirect( $url );
			}
			elseif ( isset( \IPS\Request::i()->addRenewal ) )
			{
				$form = new \IPS\Helpers\Form;
				$form->add( new \IPS\Helpers\Form\Node( 'purchases_to_renew', NULL, TRUE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => $invoice->member, 'multiple' => TRUE, 'permissionCheck' => function( $purchase )
				{
					return (bool) $purchase->renewals;
				} ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'renew_cycles', 1, TRUE, array( 'min' => 1 ) ) );
				if ( $values = $form->values() )
				{
					foreach ( $values['purchases_to_renew'] as $purchase )
					{
						$invoice->addItem( \IPS\nexus\Invoice\Item\Renewal::create( $purchase, $values['renew_cycles'] ) );
					}
					$data['items'] = $invoice->items->getArrayCopy();
					$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;
					\IPS\Output::i()->redirect( $url );
				}
				return $form;
			}
						
			$itemTypes = \IPS\Application::allExtensions( 'nexus', 'Item', TRUE, NULL, NULL, FALSE );
			if ( isset( \IPS\Request::i()->add ) and isset( $itemTypes[ \IPS\Request::i()->add ] ) )
			{
				$class = $itemTypes[ \IPS\Request::i()->add ];
				
				$formUrl = $url->setQueryString( 'add', \IPS\Request::i()->add );				
				$form = new \IPS\Helpers\Form( 'add', 'invoice_add_item', $formUrl );
				if ( method_exists( $class, 'formSecondStep' ) )
				{
					$form->ajaxOutput = TRUE;
				}
				$class::form( $form, $invoice );
				if ( $values = $form->values() or ( method_exists( $class, 'formSecondStep' ) and isset( \IPS\Request::i()->firstStep ) ) )
				{
					if ( method_exists( $class, 'formSecondStep' ) )
					{
						$firstStepValues = isset( \IPS\Request::i()->firstStep ) ? urldecode( \IPS\Request::i()->firstStep ) : json_encode( array_map( function( $val )
						{
							return ( $val instanceof \IPS\Node\Model ) ? $val->_id : $val;
						}, $values ) );						
						$secondStepForm = new \IPS\Helpers\Form( 'add2', 'invoice_add_item', $formUrl->setQueryString( 'firstStep', $firstStepValues ) );
						$secondStepForm->ajaxOutput = TRUE;
						$secondStepForm->hiddenValues['firstStep'] = $firstStepValues;
						if ( $class::formSecondStep( json_decode( $firstStepValues, TRUE ), $secondStepForm, $invoice ) )
						{
							if ( $secondStepValues = $secondStepForm->values() )
							{
								$item = $class::createFromForm( $secondStepValues, $invoice );
								if( is_array( $item ) )
								{
									foreach ( $item as $i )
									{
										$invoice->addItem( $i );
									}
								}
								else
								{
									$invoice->addItem( $item );
								}
								$data['items'] = $invoice->items->getArrayCopy();
								$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;
								\IPS\Output::i()->redirect( $url );
								return;
							}
							return $secondStepForm;
						}
					}

					$item = $class::createFromForm( $values, $invoice );
					if( is_array( $item ) )
					{
						foreach ( $item as $i )
						{
							$invoice->addItem( $i );
						}
					}
					else
					{
						$invoice->addItem( $item );
					}

					$data['items'] = $invoice->items->getArrayCopy();
					$_SESSION[ 'wizard-' . md5( $url ) . '-data' ] = $data;					
					\IPS\Output::i()->redirect( $url );
				}
				
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $form ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
				}
				else
				{
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, $form, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
				}
				return;
			}
			
			return \IPS\Theme::i()->getTemplate('invoices')->generate( $invoice->summary(), $itemTypes, $url );					
			
		};
		
		/* Display */
		\IPS\Output::i()->output = new \IPS\Helpers\Wizard( $steps, $url, ( !isset( \IPS\Request::i()->add ) and !isset( \IPS\Request::i()->addRenewal ) ) );
	}
	
	/**
	 * Product Tree (AJAX)
	 *
	 * @return	void
	 */
	public function productTree()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_add' );
		
		$output = '';
		foreach( \IPS\nexus\Package\Group::load( \IPS\Request::i()->id )->children() as $child )
		{
			if ( $child instanceof \IPS\nexus\Package\Group )
			{
				$output .= \IPS\Theme::i()->getTemplate('invoices')->packageSelectorGroup( $child );
			}
			else
			{
				$output .= \IPS\Theme::i()->getTemplate('invoices')->packageSelectorProduct( $child );
			}
		}
		
		\IPS\Output::i()->json( $output );
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'invoices_settings' );
		
		$form = new \IPS\Helpers\Form;
		$form->addheader('invoice_flow');
		$form->addMessage('invoice_flow_visualise');
		$form->add( new \IPS\Helpers\Form\Number( 'cm_invoice_generate', \IPS\Settings::i()->cm_invoice_generate, FALSE, array( 'min' => 1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('cm_invoice_generate_suffix') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'cm_invoice_warning', \IPS\Settings::i()->cm_invoice_warning, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('cm_invoice_warning_suffix') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'cm_invoice_expireafter', \IPS\Settings::i()->cm_invoice_expireafter, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days') ) );
		$form->addHeader('invoice_layout');
		$form->addMessage('invoice_layout_blurb');
		$form->add( new \IPS\Helpers\Form\Editor( 'nexus_invoice_header', \IPS\Settings::i()->nexus_invoice_header, FALSE, array( 'app' => 'nexus', 'key' => 'Admin', 'autoSaveKey'	=> 'nexus-invoice-header', 'attachIds' => array( NULL, NULL, 'invoice-header' ), 'minimize' => 'nexus_invoice_header_placeholder'  ) ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'nexus_invoice_footer', \IPS\Settings::i()->nexus_invoice_footer, FALSE, array( 'app' => 'nexus', 'key' => 'Admin', 'autoSaveKey'	=> 'nexus-invoice-footer', 'attachIds' => array( NULL, NULL, 'invoice-footer' ), 'minimize' => 'nexus_invoice_footer_placeholder'  ) ) );
		
		if ( $values = $form->values() )
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => (int) (bool) $values['cm_invoice_expireafter'] ), "`key`='expireInvoices'" );
			
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplogs__invoice_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=invoices&do=settings'), 'saved' );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('invoice_settings');
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Redirect
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	protected function _redirect( \IPS\nexus\Invoice $invoice )
	{
		if ( isset( \IPS\Request::i()->r ) )
		{
			switch ( mb_substr( \IPS\Request::i()->r, 0, 1 ) )
			{
				case 'v':
					\IPS\Output::i()->redirect( $invoice->acpUrl() );
					break;
				
				case 'p':
					try
					{
						\IPS\Output::i()->redirect( \IPS\nexus\Purchase::load( mb_substr( \IPS\Request::i()->r, 2 ) )->acpUrl() );
						break;
					}
					catch ( \OutOfRangeException $e ) {}
				
				case 'c':
					\IPS\Output::i()->redirect( $invoice->member->acpUrl() );
					break;
				
				case 't':
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=invoices') );
					break;
					
			}
		}
		
		\IPS\Output::i()->redirect( $invoice->acpUrl() );
	}
}