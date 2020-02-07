<?php
/**
 * @brief		Customer Stored Card Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		12 Mar 2014
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
 * Customer Stored Card Model
 */
class _CreditCard extends \IPS\Patterns\ActiveRecord
{	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'nexus_customer_cards';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'card_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$gateway = \IPS\nexus\Gateway::load( $data['card_method'] );
		$classname = 'IPS\nexus\Gateway\\' . $gateway->gateway . '\\CreditCard';
		
		/* Initiate an object */
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = \substr( $k, \strlen( static::$databasePrefix ) );
			}

			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
		
		/* Init */
		if ( method_exists( $obj, 'init' ) )
		{
			$obj->init();
		}
				
		/* Return */
		return $obj;
	}
	
	/**
	 * Add Form
	 *
	 * @param	\IPS\nexus\Customer	$customer	The customer
	 * @return	\IPS\Helpers\Form|\IPS\nexus\CreditCard
	 */
	public static function create( \IPS\nexus\Customer $customer )
	{
		$form = new \IPS\Helpers\Form;
		$gateways = \IPS\nexus\Gateway::cardStorageGateways();
		
		$elements = array();
		$paymentMethodsToggles = array();
		foreach ( $gateways as $gateway )
		{
			$invoice = new \IPS\nexus\Invoice;
			foreach ( $gateway->paymentScreen( $invoice, $invoice->total, $customer ) as $element )
			{
				if ( !$element->htmlId )
				{
					$element->htmlId = $gateway->id . '-' . $element->name;
				}
				if ( isset( $element->options['save'] ) )
				{
					$element->options['save'] = NULL;
				}
				$elements[] = $element;
				$paymentMethodsToggles[ $gateway->id ][] = $element->htmlId;
			}
		}
		
		if ( count( $gateways ) > 1 )
		{
			$options = array();
			foreach ( $gateways as $gateway )
			{
				$options[ $gateway->id ] = $gateway->_title;
			} 
			
			$element = new \IPS\Helpers\Form\Radio( 'payment_method', NULL, TRUE, array( 'options' => $options, 'toggles' => $paymentMethodsToggles ) );
			$element->label = \IPS\Member::loggedIn()->language()->addToStack('card_gateway');			
			$form->add( $element );
		}
		else
		{
			foreach ( $gateways as $gateway )
			{
				$form->hiddenValues['payment_method'] = $gateway->id;
			}
		}
		
		foreach ( $elements as $element )
		{
			$form->add( $element );
		}
		
		if ( $values = $form->values() )
		{			
			if ( !$values[ \IPS\Request::i()->payment_method . '_card' ] )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('card_number_invalid');
			}
			else
			{
				try
				{
					$gateway = \IPS\nexus\Gateway::load( \IPS\Request::i()->payment_method );
					$classname = 'IPS\nexus\Gateway\\' . $gateway->gateway . '\CreditCard';
					$card = new $classname;
					$card->member = $customer;
					$card->method = $gateway;
					$card->card = $values[ $gateway->id . '_card' ];
					$card->save();
					
					$customer->log( 'card', array( 'type' => 'add', 'number' => $card->card->lastFour ) );
					
					return $card;
				}
				catch ( \DomainException $e )
				{
					$form->error = $e->getMessage();
				}
			}
		}
		
		return $form;
	}
	
	/**
	 * Get member
	 *
	 * @return	\IPS\Member
	 */
	public function get_member()
	{
		return \IPS\nexus\Customer::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	\IPS\Member
	 * @return	void
	 */
	public function set_member( \IPS\Member $member )
	{
		$this->_data['member'] = $member->member_id;
	}
	
	/**
	 * Get payment gateway
	 *
	 * @return	\IPS\nexus\Gateway
	 */
	public function get_method()
	{
		return \IPS\nexus\Gateway::load( $this->_data['method'] );
	}
	
	/**
	 * Set payment gateway
	 *
	 * @param	\IPS\nexus\Gateway
	 * @return	void
	 */
	public function set_method( \IPS\nexus\Gateway $gateway )
	{
		$this->_data['method'] = $gateway->id;
	}
}