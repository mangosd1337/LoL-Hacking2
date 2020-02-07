<?php
/**
 * @brief		Donations
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Jun 2014
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
 * Donations
 */
class _donations extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		parent::execute();
	}
		
	/**
	 * View List
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( isset( \IPS\Request::i()->id ) )
		{
			if ( \IPS\Request::i()->isAjax() )
			{
				
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=donations', 'front', 'clientsdonations', array(), \IPS\Settings::i()->nexus_https ) );
			}
		}
		
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=donations', 'front', 'clientsdonations', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_donations') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_donations');
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'donations.css' ) );
				
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->donations();
	}
	
	/**
	 * Make Donation
	 *
	 * @return	void
	 */
	public function donate()
	{
		try
		{
			$goal = \IPS\nexus\Donation\Goal::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X238/1', 404, '' );
		}
		
		$form = new \IPS\Helpers\Form( 'donate', 'donate' );
		$form->class = 'ipsForm_vertical';
		if ( !isset( \IPS\Request::i()->noDesc ) and $desc = \IPS\Member::loggedIn()->language()->get("nexus_donategoal_{$goal->id}_desc") )
		{
			$form->addMessage( $desc, '', FALSE );
		}
		$form->add( new \IPS\Helpers\Form\Number( 'donate_amount', 0, TRUE, array( 'decimals' => TRUE, 'min' => 0.01 ), NULL, NULL, $goal->currency ) );
		
		if ( $values = $form->values() )
		{
			$item = new \IPS\nexus\extensions\nexus\Item\Donation( \IPS\Member::loggedIn()->language()->get( 'nexus_donategoal_' . $goal->_id ), new \IPS\nexus\Money( $values['donate_amount'], $goal->currency ) );
			$item->id = $goal->_id;
						
			$invoice = new \IPS\nexus\Invoice;
			$invoice->member = \IPS\nexus\Customer::loggedIn();
			$invoice->currency = $goal->currency;
			$invoice->addItem( $item );
			$invoice->return_uri = 'app=nexus&module=clients&controller=donations&thanks=1';			
			$invoice->save();
			
			\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
		}
		
		\IPS\Output::i()->title = $goal->_title;
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
}