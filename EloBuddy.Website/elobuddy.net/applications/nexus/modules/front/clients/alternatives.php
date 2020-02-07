<?php
/**
 * @brief		Alternative Contacts
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
 * Alternative Contacts
 */
class _alternatives extends \IPS\Dispatcher\Controller
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
			\IPS\Output::i()->error( 'no_module_permission_guest', '2X237/1', 403, '' );
		}
		
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'clients.css', 'nexus' ) );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_alternatives') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_alternatives');
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
		foreach ( \IPS\nexus\Customer::loggedIn()->alternativeContacts() as $contact )
		{
			$contact->alt_id;
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->alternatives();
	}
	
	/**
	 * Add/Edit
	 *
	 * @return	void
	 */
	protected function form()
	{
		$existing = NULL;
		if ( isset( \IPS\Request::i()->id ) )
		{
			try
			{
				$existing = \IPS\nexus\Customer\AlternativeContact::constructFromData( \IPS\Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', \IPS\nexus\Customer::loggedIn()->member_id, \IPS\Request::i()->id ) )->first() );
			}
			catch ( \UnderflowException $e ) {}
		}
				
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		if ( !$existing )
		{
			$form->add( new \IPS\Helpers\Form\Email( 'altcontact_email', NULL, TRUE, array(), function( $val )
			{
				$member = \IPS\Member::load( $val, 'email' );
				if ( !$member->member_id )
				{
					throw new \DomainException('altcontact_email_error');
				}
				
				try
				{
					\IPS\Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', \IPS\nexus\Customer::loggedIn()->member_id, $member->member_id ) )->first();
					throw new \DomainException('altcontact_already_exists');
				}
				catch ( \UnderflowException $e ) {}
			} ) );
		}
		$form->add( new \IPS\Helpers\Form\Node( 'altcontact_purchases', $existing ? iterator_to_array( $existing->purchases ) : NULL, FALSE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => \IPS\Member::loggedIn(), 'multiple' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Checkbox( 'altcontact_support', $existing ? $existing->support : FALSE ) );
		$form->add( new \IPS\Helpers\Form\Checkbox( 'altcontact_billing', $existing ? $existing->billing : FALSE ) );
		if ( $values = $form->values() )
		{
			if ( $existing )
			{
				$altContact = $existing;
				\IPS\nexus\Customer::loggedIn()->log( 'alternative', array( 'type' => 'edit', 'alt_id' => $altContact->alt_id->member_id, 'alt_name' => $altContact->alt_id->name, 'purchases' => json_encode( $values['altcontact_purchases'] ? $values['altcontact_purchases'] : array() ), 'billing' => $values['altcontact_billing'], 'support' => $values['altcontact_support'] ) );
			}
			else
			{
				$altContact = new \IPS\nexus\Customer\AlternativeContact;
				$altContact->main_id = \IPS\nexus\Customer::loggedIn();
				$altContact->alt_id = \IPS\Member::load( $values['altcontact_email'], 'email' );
				\IPS\nexus\Customer::loggedIn()->log( 'alternative', array( 'type' => 'add', 'alt_id' => $altContact->alt_id->member_id, 'alt_name' => $altContact->alt_id->name, 'purchases' => json_encode( $values['altcontact_purchases'] ? $values['altcontact_purchases'] : array() ), 'billing' => $values['altcontact_billing'], 'support' => $values['altcontact_support'] ) );			
			}
			$altContact->purchases = $values['altcontact_purchases'] ? $values['altcontact_purchases'] : array();
			$altContact->billing = $values['altcontact_billing'];
			$altContact->support = $values['altcontact_support'];
			$altContact->save();
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives', array(), \IPS\Settings::i()->nexus_https ) );
		}

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) ) );
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
	protected function delete()
	{
		\IPS\Session::i()->csrfCheck();
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		try
		{
			$contact = \IPS\nexus\Customer\AlternativeContact::constructFromData( \IPS\Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', \IPS\nexus\Customer::loggedIn()->member_id, \IPS\Request::i()->id ) )->first() );
			$contact->delete();
			\IPS\nexus\Customer::loggedIn()->log( 'alternative', array( 'type' => 'delete', 'alt_id' => $contact->alt_id->member_id, 'alt_name' => $contact->alt_id->name ) );
		}
		catch ( \UnderflowException $e ) {}
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=alternatives', 'front', 'clientsalternatives', array(), \IPS\Settings::i()->nexus_https ) );
	}
}