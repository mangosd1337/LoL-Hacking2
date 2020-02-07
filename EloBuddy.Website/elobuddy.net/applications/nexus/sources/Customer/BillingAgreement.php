<?php
/**
 * @brief		Billing Agreement Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		16 Dec 2015
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
 * Billing Agreement Model
 */
class _BillingAgreement extends \IPS\Patterns\ActiveRecord
{	
	const STATUS_ACTIVE		= 'active'; // Billing Agreement is active and will charge automatically
	const STATUS_SUSPENDED	= 'suspended'; // Billing Agreement has been suspended but can be reactivated
	const STATUS_CANCELED	= 'canceled'; // Billing Agreement has been canceled
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'nexus_billing_agreements';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'ba_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$gateway = \IPS\nexus\Gateway::load( $data['ba_method'] );
		$classname = 'IPS\nexus\Gateway\\' . $gateway->gateway . '\\BillingAgreement';
		
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
	 * Load and check permissions
	 *
	 * @return	\IPS\Content\Item
	 * @throws	\OutOfRangeException
	 */
	public static function loadAndCheckPerms( $id )
	{
		$obj = static::load( $id );
		
		if ( !$obj->canView() )
		{
			throw new \OutOfRangeException;
		}

		return $obj;
	}
	
	/**
	 * Member can view?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for, or NULL for currently logged in member
	 * @return	bool
	 */
	public function canView( \IPS\Member $member = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		return $this->member->member_id === $member->member_id or array_key_exists( $member->member_id, iterator_to_array( $this->member->alternativeContacts( array( 'billing=1' ) ) ) );
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
	
	/**
	 * Get start date
	 *
	 * @return	\IPS\DateTime
	 */
	public function get_started()
	{
		return \IPS\DateTime::ts( $this->_data['started'] );
	}
	
	/**
	 * Set next payment date
	 *
	 * @param	\IPS\DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_next_cycle( \IPS\DateTime $date = NULL )
	{
		$this->_data['next_cycle'] = $date ? $date->getTimestamp() : NULL;
	}
	
	/**
	 * Get next payment date
	 *
	 * @return	\IPS\DateTime
	 */
	public function get_next_cycle()
	{
		return $this->_data['next_cycle'] ? \IPS\DateTime::ts( $this->_data['next_cycle'] ) : NULL;
	}
	
	/**
	 * Set start date
	 *
	 * @param	\IPS\DateTime	$date	The invoice date
	 * @return	void
	 */
	public function set_started( \IPS\DateTime $date )
	{
		$this->_data['started'] = $date->getTimestamp();
	}
	
	/**
	 * Suspend
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function suspend()
	{
		$this->doSuspend();
		
		$this->member->log( 'billingagreement', array( 'type' => 'suspend', 'id' => $this->id, 'gw_id' => $this->gw_id ) );
		$this->next_cycle = NULL;
		$this->save();
	}
	
	/**
	 * Reactivate
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function reactivate()
	{
		$this->doReactivate();
		
		$this->member->log( 'billingagreement', array( 'type' => 'reactivate', 'id' => $this->id, 'gw_id' => $this->gw_id ) );
		$this->next_cycle = $this->nextPaymentDate();
		$this->save();
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function cancel()
	{
		$this->doCancel();
		
		$this->member->log( 'billingagreement', array( 'type' => 'cancel', 'id' => $this->id, 'gw_id' => $this->gw_id ) );
		$this->next_cycle = NULL;
		$this->canceled = TRUE;
		
		\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_billing_agreement' => NULL ), array( 'ps_billing_agreement=?', $this->id ) );
	}
	
	/**
	 * Front-End URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		return \IPS\Http\Url::internal( "app=nexus&module=clients&controller=billingagreements&do=view&id={$this->id}", 'front', 'clientsbillingagreement' );
	}
	
	/**
	 * ACP URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function acpUrl()
	{
		return \IPS\Http\Url::internal( "app=nexus&module=payments&controller=billingagreements&id={$this->id}", 'admin' );
	}
}