<?php
/**
 * @brief		Addresses
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
 * Addresses
 */
class _addresses extends \IPS\Dispatcher\Controller
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
			\IPS\Output::i()->error( 'no_module_permission_guest', '2X235/1', 403, '' );
		}
		
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'clients.css', 'nexus' ) );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_addresses') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_addresses');
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
		$addresses = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=?', \IPS\Member::loggedIn()->member_id ) ), 'IPS\nexus\Customer\Address' );

		$shippingAddress = NULL;
		$billingAddress = NULL;
		$otherAddresses = array();

		foreach ( $addresses as $address )
		{
			if( $address->primary_billing )
			{
				$billingAddress = $address;
			}

			if( $address->primary_shipping )
			{
				$shippingAddress = $address;
			}

			if( !$address->primary_shipping && !$address->primary_billing )
			{
				$otherAddresses[] = $address;
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->addresses( $billingAddress, $shippingAddress, $otherAddresses );
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
				$existing = \IPS\nexus\Customer\Address::load( \IPS\Request::i()->id );
				if ( $existing->member->member_id != \IPS\Member::loggedIn()->member_id )
				{
					throw new \OutOfRangeException;
				}
			}
			catch ( \OutOfRangeException $e )
			{
				$existing = NULL;
			}
		}

		$form = new \IPS\Helpers\Form;		
		$form->add( new \IPS\Helpers\Form\Address( 'address', $existing ? $existing->address : NULL, TRUE ) );
		
		if ( $values = $form->values() )
		{
			if ( !$existing )
			{
				$existing = new \IPS\nexus\Customer\Address;
				$existing->member = \IPS\Member::loggedIn();
				$existing->primary_billing = !\IPS\Db::i()->select( 'count(*)', 'nexus_customer_addresses', array( 'member=? AND primary_billing=1', \IPS\Member::loggedIn()->member_id ) )->first();
				$existing->primary_shipping = !\IPS\Db::i()->select( 'count(*)', 'nexus_customer_addresses', array( 'member=? AND primary_shipping=1', \IPS\Member::loggedIn()->member_id ) )->first();
				
				\IPS\nexus\Customer::loggedIn()->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['address'] ) ) );
			}
			else
			{
				\IPS\nexus\Customer::loggedIn()->log( 'address', array( 'type' => 'edit', 'new' => json_encode( $values['address'] ), 'old' => json_encode( $existing->address ) ) );
			}
			
			$existing->address = $values['address'];
			$existing->save();
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses', array(), \IPS\Settings::i()->nexus_https ) );
		}

		if ( \IPS\Request::i()->isAjax() )
		{
			$form->class = 'ipsForm_vertical ipsForm_noLabels';
			\IPS\Output::i()->sendOutput( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) ) );
		}
		else
		{
			\IPS\Output::i()->output = $form;	
		}		
	}
	
	/**
	 * Make Primary
	 *
	 * @return	void
	 */
	protected function primary()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{
			$address = \IPS\nexus\Customer\Address::load( \IPS\Request::i()->id );
			if ( $address->member->member_id == \IPS\Member::loggedIn()->member_id )
			{
				$field = \IPS\Request::i()->primary === 'billing' ? 'primary_billing' : 'primary_shipping';
				\IPS\Db::i()->update( 'nexus_customer_addresses', array( $field => 0 ), array( 'member=?', \IPS\Member::loggedIn()->member_id ) );
				$address->$field = TRUE;
				$address->save();
				
				\IPS\nexus\Customer::loggedIn()->log( 'address', array( 'type' => ( \IPS\Request::i()->primary === 'billing' ? 'primary_billing' : 'primary_shipping' ), 'details' => json_encode( $address->address ) ) );
			}
		}
		catch ( \OutOfRangeException $e ) {}

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses', array(), \IPS\Settings::i()->nexus_https ) );
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
			$address = \IPS\nexus\Customer\Address::load( \IPS\Request::i()->id );
			if ( $address->member->member_id == \IPS\Member::loggedIn()->member_id )
			{
				$address->delete();
				\IPS\nexus\Customer::loggedIn()->log( 'address', array( 'type' => 'delete', 'details' => json_encode( $address->address ) ) );
			}
		}
		catch ( \OutOfRangeException $e ) {}

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=addresses', 'front', 'clientsaddresses', array(), \IPS\Settings::i()->nexus_https ) );
	}
}