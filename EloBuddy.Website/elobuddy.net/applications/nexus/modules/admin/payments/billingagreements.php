<?php
/**
 * @brief		Billing Agreememts
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		16 Dec 2015
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
 * Billing Agreements
 */
class _billingagreements extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'billingagreements_view' );
		parent::execute();
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Load */
		try
		{
			$billingAgreement = \IPS\nexus\Customer\BillingAgreement::load( \IPS\Request::i()->id );
			
			if ( !$billingAgreement->canceled and $billingAgreement->status() == $billingAgreement::STATUS_CANCELED )
			{
				$billingAgreement->canceled = TRUE;
				$billingAgreement->save();
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X320/1', 404, '' );
		}
		
		/* Show */
		try
		{
			/* Purchases */
			$purchases = NULL;
			if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) and ( !isset( \IPS\Request::i()->table ) ) )
			{
				$purchases = \IPS\nexus\Purchase::tree( $billingAgreement->acpUrl(), array( array( 'ps_billing_agreement=?', $billingAgreement->id ) ), 'ba' );
			}
			
			/* Transactions */
			$transactions = NULL;
			if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'transactions_manage' ) and ( !isset( \IPS\Request::i()->table ) ) )
			{
				$transactions = \IPS\nexus\Transaction::table( array( array( 't_billing_agreement=?', $billingAgreement->id ) ), $billingAgreement->acpUrl(), 'ba' );
				$transactions->limit = 50;
				foreach ( $transactions->include as $k => $v )
				{
					if ( in_array( $v, array( 't_method', 't_member' ) ) )
					{
						unset( $transactions->include[ $k ] );
					}
				}
			}
			
			/* Action Buttons */
			if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'billingagreements_manage' ) )
			{
				if ( $billingAgreement->status() == $billingAgreement::STATUS_ACTIVE )
				{
					\IPS\Output::i()->sidebar['actions']['suspend'] = array(
						'icon'	=> 'times',
						'title'	=> 'billing_agreement_suspend',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'suspend' ) ),
						'data'	=> array( 'confirm' => '', 'confirmSubMessage' => \IPS\Member::loggedIn()->language()->addToStack('billing_agreement_suspend_confirm') )
					);
				}
				elseif ( $billingAgreement->status() == $billingAgreement::STATUS_SUSPENDED )
				{
					\IPS\Output::i()->sidebar['actions']['reactivate'] = array(
						'icon'	=> 'check',
						'title'	=> 'billing_agreement_reactivate',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'reactivate' ) ),
						'data'	=> array( 'confirm' => '' )
					);
				}
				if ( $billingAgreement->status() != $billingAgreement::STATUS_CANCELED )
				{
					\IPS\Output::i()->sidebar['actions']['cancel'] = array(
						'icon'	=> 'times-circle',
						'title'	=> 'billing_agreement_cancel',
						'link'	=> $billingAgreement->acpUrl()->setQueryString( array( 'do' => 'act', 'act' => 'cancel' ) ),
					);
				}
			}
							
			/* Display */
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'billing_agreement_id', FALSE, array( 'sprintf' => array( $billingAgreement->id ) ) );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'billingagreements' )->view( $billingAgreement, $purchases, $transactions );
		}
		catch ( \IPS\nexus\Gateway\PayPal\Exception $e )
		{
			\IPS\Output::i()->error( 'billing_agreement_error', '1X320/2', 500, '', array(), $e->getName() );
		}
		catch ( \DomainException $e )
		{
			\IPS\Output::i()->error( 'billing_agreement_error', '1X320/3', 500 );
		}
	}
	
	/**
	 * Reconcile - resets next_cycle date
	 *
	 * @return	void
	 */
	public function reconcile()
	{
		/* Load */
		try
		{
			$billingAgreement = \IPS\nexus\Customer\BillingAgreement::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X320/5', 404, '' );
		}
		
		/* Reconcile */
		try
		{
			$billingAgreement->next_cycle = $billingAgreement->nextPaymentDate();
			$billingAgreement->save();
			
			\IPS\Output::i()->redirect( $billingAgreement->acpUrl() );
		}
		catch ( \IPS\nexus\Gateway\PayPal\Exception $e )
		{
			\IPS\Output::i()->error( 'billing_agreement_error', '1X320/6', 500, '', array(), $e->getName() );
		}
		catch ( \DomainException $e )
		{
			\IPS\Output::i()->error( 'billing_agreement_error', '1X320/7', 500 );
		}
	}
	
	/**
	 * Suspend/Reactivate/Cancel
	 *
	 * @return	void
	 */
	public function act()
	{
		/* Check act */
		$act = \IPS\Request::i()->act;
		if ( !in_array( $act, array( 'suspend', 'reactivate', 'cancel' ) ) )
		{
			\IPS\Output::i()->error( 'node_error', '3X320/8', 403, '' );
		}
		
		/* Load */
		try
		{
			$billingAgreement = \IPS\nexus\Customer\BillingAgreement::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X320/8', 404, '' );
		}
		
		/* Perform Action */
		try
		{
			$billingAgreement->$act();
			
			\IPS\Output::i()->redirect( $billingAgreement->acpUrl() );
		}
		catch ( \IPS\nexus\Gateway\PayPal\Exception $e )
		{
			\IPS\Output::i()->error( 'billing_agreement_error', '1X320/9', 500, '', array(), $e->getName() );
		}
		catch ( \DomainException $e )
		{
			\IPS\Output::i()->error( 'billing_agreement_error', '1X320/A', 500 );
		}
	}
}