<?php
/**
 * @brief		Invoices
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		06 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\clients;

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
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2X215/3', 403, '' );
		}
		
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		/* Load Invoice */
		if ( isset( \IPS\Request::i()->id ) )
		{
			try
			{
				$this->invoice = \IPS\nexus\Invoice::loadAndCheckPerms( \IPS\Request::i()->id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2X215/1', 404, '' );
			}
			
			\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=invoices', 'front', 'clientsinvoices', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_invoices') );
			\IPS\Output::i()->breadcrumb[] = array( $this->invoice->url(), $this->invoice->title );
			\IPS\Output::i()->title = $this->invoice->title;
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_invoices');
			if ( isset( \IPS\Request::i()->do ) )
			{
				\IPS\Output::i()->error( 'node_error', '2X215/2', 403, '' );
			}
		}
		
		/* Execute */
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'clients.css', 'nexus' ) );
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}

	/**
	 * View List
	 *
	 * @return	void
	 */
	protected function manage()
	{	
		$where = array( 'i_member=?', \IPS\Member::loggedIn()->member_id );
		$parentContacts = \IPS\nexus\Customer::loggedIn()->parentContacts( array( 'billing=1' ) );
		if ( count( $parentContacts ) )
		{
			$or = array();
			foreach ( array_keys( iterator_to_array( $parentContacts ) ) as $id )
			{
				$where[0] .= ' OR i_member=?';
				$where[] = $id;
			}
		}
				
		$invoices = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_invoices', $where, 'i_date DESC' ), 'IPS\nexus\Invoice' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->invoices( $invoices );
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function view()
	{
		$shipments = $this->invoice->shipments();		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->invoice( $this->invoice );
	}
	
	/**
	 * PO Number
	 *
	 * @return	void
	 */
	public function poNumber()
	{		
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Text( 'invoice_po_number', $this->invoice->po, FALSE, array( 'maxLength' => 255 ) ) );
		if ( $values = $form->values() )
		{
			$this->invoice->po = $values['invoice_po_number'];
			$this->invoice->save();
			\IPS\Output::i()->redirect( $this->invoice->url() );
		}
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );;
	}
	
	/**
	 * Notes
	 *
	 * @return	void
	 */
	public function notes()
	{		
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\TextArea( 'invoice_notes', $this->invoice->notes ) );
		\IPS\Member::loggedIn()->language()->words['invoice_notes_desc'] = '';
		if ( $values = $form->values() )
		{
			$this->invoice->notes = $values['invoice_notes'];
			$this->invoice->save();
			\IPS\Output::i()->redirect( $this->invoice->url() );
		}
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );;
	}
	
	/**
	 * Print
	 *
	 * @return	void
	 */
	public function printout()
	{
		$output = \IPS\Theme::i()->getTemplate( 'invoices', 'nexus', 'global' )->printInvoice( $this->invoice, $this->invoice->summary() );
		\IPS\Output::i()->title = 'I' . $this->invoice->id;
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ) );
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 */
	public function cancel()
	{
		/* CSRF check */
		\IPS\Session::i()->csrfCheck();
		        
        /* If they have already made a partial payment, refund it to their account credit */
        foreach ( $this->invoice->transactions( array( \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED ) ) as $transaction )
		{
			try
			{
				$transaction->refund( 'credit' );
			}
			catch ( \Exception $e )
			{
				\IPS\Output::i()->error( 'order_cancel_error', '4C171/5', 500, $e->getMessage() );
			}
		}
		
		/* Cancel the invoice */
		$this->invoice->status = \IPS\nexus\invoice::STATUS_CANCELED;
		$this->invoice->save();
		$this->invoice->member->log( 'invoice', array( 'type' => 'status', 'new' => 'canc', 'id' => $this->invoice->id, 'title' => $this->invoice->title ) );

		/* Run any callbacks (for example, coupons get unmarked as being used) */
        foreach ( $this->invoice->items as $k => $item )
        {
            $item->onInvoiceCancel( $this->invoice );
        }
        
        /* Redirect */
		\IPS\Output::i()->redirect( $this->invoice->url() );
	}
}