<?php
/**
 * @brief		Transactions
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
 * Transactions
 */
class _transactions extends \IPS\Dispatcher\Controller
{	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'transactions_manage' );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'transaction.css', 'nexus', 'admin' ) );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Create Table */
		$table = \IPS\nexus\Transaction::table( array(), \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=transactions' ), 't' );
		$table->filters = array(
			'tstatus_hold'	=> array( 't_status=?', 'hold' ),
		);
		$table->advancedSearch = array(
			't_id'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			't_status'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => \IPS\nexus\Transaction::statuses(), 'multiple' => TRUE ) ),
			't_member'	=> \IPS\Helpers\Table\SEARCH_MEMBER,
			't_amount'	=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			't_method'	=> array( \IPS\Helpers\Table\SEARCH_NODE, array( 'class' => '\IPS\nexus\Gateway' ) ),
			't_date'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
		);
		$table->quickSearch = 't_id';
		
		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_payments_transactions');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view()
	{
		/* Load Transaction */
		try
		{
			$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X186/8', 404, '' );
		}
				
		/* Output */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'transaction_number', FALSE, array( 'sprintf' => array( $transaction->id ) ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'transactions' )->view( $transaction );
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	public function approve()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'transactions_edit' );
		
		/* Load Transaction */
		try
		{
			$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X186/9', 404, '' );
		}
		$method = $transaction->method;
		
		/* Can we approve it? */
		if ( !in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_PENDING, \IPS\nexus\Transaction::STATUS_WAITING, \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW ) ) )
		{
			\IPS\Output::i()->error( 'transaction_status_err', '2X186/A', 403, '' );
		}
		
		/* Log it */
		$transaction->member->log( 'transaction', array(
			'type'		=> 'status',
			'status'	=> \IPS\nexus\Transaction::STATUS_PAID,
			'id'		=> $transaction->id
		) );
		
		/* Do it */
		try
		{
			$transaction->capture();
			$transaction->approve( \IPS\Member::loggedIn() );
		}
		catch ( \LogicException $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '3X186/2', 500, '' );
		}
		catch ( \RuntimeException $e )
		{
			\IPS\Output::i()->error( 'transaction_capture_err', '3X186/3', 500, '' );
		}
		
		/* Send Email */
		$transaction->sendNotification();
		
		/* Redirect */
		$this->_redirect( $transaction );
	}
	
	/**
	 * Flag for review
	 *
	 * @return	void
	 */
	public function review()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'transactions_edit' );
		
		/* Load Transaction */
		try
		{
			$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X186/C', 404, '' );
		}
		$method = $transaction->method;
		
		/* Can we flag it? */
		if ( !in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_PENDING, \IPS\nexus\Transaction::STATUS_WAITING, \IPS\nexus\Transaction::STATUS_HELD ) ) )
		{
			\IPS\Output::i()->error( 'transaction_status_err', '2X186/B', 403, '' );
		}
		
		/* Set it */
		$extra = $transaction->extra;
		$extra['history'][] = array( 's' => \IPS\nexus\Transaction::STATUS_REVIEW, 'on' => time(), 'by' => \IPS\Member::loggedIn()->member_id );
		$transaction->extra = $extra;
		$transaction->status = \IPS\nexus\Transaction::STATUS_REVIEW;
		$transaction->save();
		
		/* Log it */
		$transaction->member->log( 'transaction', array(
			'type'		=> 'status',
			'status'	=> \IPS\nexus\Transaction::STATUS_REVIEW,
			'id'		=> $transaction->id
		) );
		
		/* Create a support request? */
		if ( \IPS\Settings::i()->nexus_revw_sa != -1 )
		{
			$createUrl = \IPS\Http\Url::internal('app=nexus&module=support&controller=requests&do=create');
			$key = md5( $createUrl );
			
			if ( \IPS\Settings::i()->nexus_revw_sa )
			{
				$_SESSION["wizard-{$key}-data"] = array(
					'member'		=> $transaction->member->member_id,
					'stock_action'	=> \IPS\Settings::i()->nexus_revw_sa
				);
				$_SESSION["wizard-{$key}-step"] = 'request_details';
			}
			else
			{
				$_SESSION["wizard-{$key}-data"] = array(
					'member'		=> $transaction->member->member_id,
				);
				if ( count( \IPS\nexus\Support\StockAction::roots() ) )
				{
					$_SESSION["wizard-{$key}-step"] = 'stock_action';
				}
				else
				{
					$_SESSION["wizard-{$key}-step"] = 'request_details';
				}
			}
			
			$_SESSION["wizard-{$key}-data"]['transaction'] = $transaction->id;
			$_SESSION["wizard-{$key}-data"]['ref'] = isset( \IPS\Request::i()->r ) ? \IPS\Request::i()->r : 'v';
			
			\IPS\Output::i()->redirect( $createUrl );
		}
		
		/* Redirect */
		$this->_redirect( $transaction );
	}
	
	/**
	 * Void
	 *
	 * @return	void
	 */
	public function void()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'transactions_edit' );
		
		/* Load Transaction */
		try
		{
			$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X186/4', 404, '' );
		}
		$method = $transaction->method;
		
		/* Can we void it? */
		if ( !in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_PENDING, \IPS\nexus\Transaction::STATUS_WAITING ) ) and ( !$transaction->auth or !in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW ) ) ) )
		{
			\IPS\Output::i()->error( 'transaction_status_err', '2X186/5', 403, '' );
		}
		
		/* Void it */
		try
		{
			$transaction->method->void( $transaction );
		}
		catch ( \Exception $e )
		{
			if ( !isset( \IPS\Request::i()->override ) )
			{
				\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'transaction_void_err', FALSE, array( 'sprintf' => array( $transaction->acpUrl()->setQueryString( array( 'do' => 'void', 'override' => 1 ) ) ) ) ), '3X186/6', 500, '', array(), $e->getMessage() );
			}
		}
		$extra = $transaction->extra;
		$extra['history'][] = array( 's' => \IPS\nexus\Transaction::STATUS_REFUSED, 'on' => time(), 'by' => \IPS\Member::loggedIn()->member_id );
		$transaction->extra = $extra;
		$transaction->status = \IPS\nexus\Transaction::STATUS_REFUSED;
		$transaction->save();
		
		/* Log it */
		$transaction->member->log( 'transaction', array(
			'type'		=> 'status',
			'status'	=> \IPS\nexus\Transaction::STATUS_REFUSED,
			'id'		=> $transaction->id
		) );
		
		/* Send Email */
		$transaction->sendNotification();
		
		/* Redirect */
		$this->_redirect( $transaction );
	}	
	
	/**
	 * Refund
	 *
	 * @return	void
	 */
	public function refund()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'transactions_refund' );
		
		/* Load Transaction */
		try
		{
			$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X186/D', 404, '' );
		}
		$method = $transaction->method;
		
		/* Can we refund it? */
		if ( !in_array( $transaction->status, array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_HELD, \IPS\nexus\Transaction::STATUS_REVIEW, \IPS\nexus\Transaction::STATUS_PART_REFUNDED ) ) )
		{
			\IPS\Output::i()->error( 'transaction_status_err', '2X186/E', 403, '' );
		}
		
		/* What are the refund methods? */
		$refundMethods = array();
		$refundMethodToggles = array( 'credit' => array( 'refund_amount' ) );
		if ( $method and $method::SUPPORTS_REFUNDS )
		{
			$refundMethods['gateway'] = $method->_title;
			if ( $method::SUPPORTS_PARTIAL_REFUNDS )
			{
				$refundMethodToggles['gateway'] = array( 'refund_amount' );
			}
		}
		$refundMethods['credit'] = 'refund_method_credit';
		$refundMethods['none'] = 'refund_method_none';
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Radio( 'refund_method', ( $method and $method::SUPPORTS_REFUNDS ) ? 'gateway' : 'credit', TRUE, array( 'options' => $refundMethods, 'toggles' => $refundMethodToggles ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'refund_amount', ( $transaction->status === \IPS\nexus\Transaction::STATUS_PART_REFUNDED ? $transaction->amount->amount->subtract( $transaction->partial_refund->amount ) : 0 ), TRUE, array( 'unlimited' => 0, 'unlimitedLang' => \IPS\Member::loggedIn()->language()->addToStack( 'refund_full', FALSE, array( 'sprintf' => array( $transaction->amount ) ) ), 'max' => (string) $transaction->amount->amount->subtract( $transaction->partial_refund->amount ), 'decimals' => TRUE ), NULL, NULL, $transaction->amount->currency, 'refund_amount' ) );
		if ( $transaction->invoice->status === \IPS\nexus\Invoice::STATUS_PAID )
		{
			$field = new \IPS\Helpers\Form\Radio( 'refund_invoice_status', \IPS\nexus\Invoice::STATUS_PENDING, TRUE, array(
				'options' => array(
					\IPS\nexus\Invoice::STATUS_PAID	=> 'refund_invoice_paid',
					\IPS\nexus\Invoice::STATUS_PENDING	=> 'refund_invoice_pending',
					\IPS\nexus\Invoice::STATUS_CANCELED	=> 'refund_invoice_canceled',
				),
				'toggles'	=> array(
					\IPS\nexus\Invoice::STATUS_PENDING	=> array( 'form_refund_invoice_status_warning' ),
					\IPS\nexus\Invoice::STATUS_CANCELED	=> array( 'form_refund_invoice_status_warning' )
				)
			) );
			$field->warningBox = \IPS\Theme::i()->getTemplate('invoices')->unpaidConsequences( $transaction->invoice );
			$form->add( $field );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Refund */
			try
			{
				$transaction->refund( $values['refund_method'], $values['refund_amount'] );
			}
			catch ( \LogicException $e )
			{
				\IPS\Output::i()->error( $e->getMessage(), '1X186/1', 500, '' );
			}
			catch ( \RuntimeException $e )
			{
				\IPS\Output::i()->error( 'refund_failed', '3X186/7', 500, '' );
			}
			
			/* Handle invoice */
			if ( isset( $values['refund_invoice_status'] ) and $values['refund_invoice_status'] !== \IPS\nexus\Invoice::STATUS_PAID )
			{
				$transaction->invoice->markUnpaid( $values['refund_invoice_status'] );

				$transaction->invoice->member->log( 'invoice', array(
					'type'	=> 'status',
					'new'	=> $values['refund_invoice_status'],
					'id'	=> $transaction->invoice->id,
					'title' => $transaction->invoice->title
				) );
			}
			
			/* Send Email */
			$transaction->sendNotification();
						
			/* Redirect */
			$this->_redirect( $transaction );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'transaction_refund_title', FALSE, array( 'sprintf' => array( $transaction->amount ) ) );
		\IPS\Output::i()->output = $form;		
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'transactions_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Load Transaction */
		try
		{
			$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X186/F', 404, '' );
		}
		
		/* Delete */
		$transaction->delete();
		
		/* Log it */
		$transaction->member->log( 'transaction', array(
			'type'		=> 'delete',
			'id'		=> $transaction->id,
			'method'	=> $transaction->method->id
		) );
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=transactions') );
	}
	
	
	/**
	 * Redirect
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	The transaction
	 * @return	void
	 */
	protected function _redirect( \IPS\nexus\Transaction $transaction )
	{
		if ( isset( \IPS\Request::i()->r ) )
		{
			switch ( \IPS\Request::i()->r )
			{
				case 'v':
					\IPS\Output::i()->redirect( $transaction->acpUrl() );
					break;
					
				case 'i':
					\IPS\Output::i()->redirect( $transaction->invoice->acpUrl() );
					break;
				
				case 'c':
					\IPS\Output::i()->redirect( $transaction->member->acpUrl() );
					break;
				
				case 't':
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=transactions') );
					break;
			}
		}
		
		\IPS\Output::i()->redirect( $transaction->acpUrl() );
	}
}