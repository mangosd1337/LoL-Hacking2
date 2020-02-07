<?php
/**
 * @brief		Cards
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 May 2014
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
 * Cards
 */
class _cards extends \IPS\Dispatcher\Controller
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
			\IPS\Output::i()->error( 'no_module_permission_guest', '2X236/1', 403, '' );
		}
		
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'clients.css', 'nexus' ) );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_cards') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_cards');
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
		$cards = array();
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', \IPS\nexus\Customer::loggedIn()->member_id ) ), 'IPS\nexus\Customer\CreditCard' ) as $card )
		{
			try
			{
				$cardData = $card->card;
				$cards[ $card->id ] = array(
					'id'			=> $card->id,
					'card_type'		=> $cardData->type,
					'card_number'	=> $cardData->lastFour,
					'card_expire'	=> str_pad( $cardData->expMonth , 2, '0', STR_PAD_LEFT ). '/' . $cardData->expYear
				);
			}
			catch ( \Exception $e ) { }
		}
				
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->cards( $cards );
	}
	
	/**
	 * Add
	 *
	 * @return	void
	 */
	public function add()
	{
		$form = \IPS\nexus\Customer\CreditCard::create( \IPS\nexus\Customer::loggedIn() );
		if ( $form instanceof \IPS\nexus\Customer\CreditCard )
		{
			\IPS\nexus\Customer::loggedIn()->log( 'card', array( 'type' => 'add', 'number' => $form->card->lastFour ) );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards', array(), \IPS\Settings::i()->nexus_https ) );
		}
		else
		{
			\IPS\Output::i()->output = $form;
		}
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		try
		{
			$card = \IPS\nexus\Customer\CreditCard::load( \IPS\Request::i()->id );
			if ( $card->member->member_id === \IPS\nexus\Customer::loggedIn()->member_id )
			{
				$card->delete(); 
				\IPS\nexus\Customer::loggedIn()->log( 'card', array( 'type' => 'delete', 'number' => $card->card->lastFour ) );
			}
		}
		catch ( \Exception $e ) { }
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=cards', 'front', 'clientscards', array(), \IPS\Settings::i()->nexus_https ) );
	}
}