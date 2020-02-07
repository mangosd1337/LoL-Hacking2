<?php
/**
 * @brief		Gateway Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Gateway Node
 */
class _Gateway extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_paymethods';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'm_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'payment_methods';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'nexus_paymethod_';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$classname = 'IPS\nexus\Gateway\\' . $data['m_gateway'];
		if ( !class_exists( $classname ) )
		{
			throw new \OutOfRangeException;
		}
		
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
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		return $this->enabled;
	}
	
	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
		$this->enabled	= $enabled;
	}
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'payments',
		'all'		=> 'gateways_manage',
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->add( new \IPS\Helpers\Form\Translatable( 'paymethod_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => "nexus_paymethod_{$this->id}" ) ) );
		$this->settings( $form );
		$form->add( new \IPS\Helpers\Form\Select( 'paymethod_countries', ( $this->countries and $this->countries !== '*' ) ? explode( ',', $this->countries ) : '*', FALSE, array( 'options' => array_map( function( $val )
		{
			return "country-{$val}";
		}, array_combine( \IPS\GeoLocation::$countries, \IPS\GeoLocation::$countries ) ), 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'no_restriction' ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['paymethod_name'] ) )
		{
			\IPS\Lang::saveCustom( 'nexus', "nexus_paymethod_{$this->id}", $values['paymethod_name'] );
			unset( $values['paymethod_name'] );
		}

		if( isset( $values['paymethod_countries'] ) )
		{
			$values['countries'] = is_array( $values['paymethod_countries'] ) ? implode( ',', $values['paymethod_countries'] ) : $values['paymethod_countries'];
		}

		$settings = array();
		foreach ( $values as $k => $v )
		{
			if ( mb_substr( $k, 0, mb_strlen( $this->gateway ) + 1 ) === mb_strtolower( "{$this->gateway}_" ) )
			{
				$settings[ mb_substr( $k, mb_strlen( $this->gateway ) + 1 ) ] = $v;
			}
			if( $k != "countries" )
			{
				unset( $values[$k] );
			}
		}
		$values['settings'] = json_encode( $this->testSettings( $settings ) );

		return $values;
	}

	/**
	 * Get gateways that support storing cards
	 *
	 * @return	array
	 */
	public static function cardStorageGateways()
	{
		$return = array();
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->canStoreCards() )
			{
				$return[ $gateway->id ] = $gateway;
			}
		}
		return $return;
	}
	
	/**
	 * Get gateways that support manual admin charges
	 *
	 * @return	array
	 */
	public static function manualChargeGateways()
	{
		$return = array();
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->canAdminCharge() )
			{
				$return[ $gateway->id ] = $gateway;
			}
		}
		return $return;
	}
	
	/**
	 * Get gateways that support billing agreements
	 *
	 * @return	array
	 */
	public static function billingAgreementGateways()
	{
		$return = array();
		foreach ( static::roots() as $gateway )
		{
			if ( $gateway->billingAgreements() )
			{
				$return[ $gateway->id ] = $gateway;
			}
		}
		return $return;
	}
	
	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		static::recountCardStorageGateways();
	}
	
	/**
	 * [ActiveRecord] Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* Delete cards and billing agreements */
		\IPS\Db::i()->delete( 'nexus_customer_cards', array( 'card_method=?', $this->id ) );
		\IPS\Db::i()->delete( 'nexus_billing_agreements', array( 'ba_method=?', $this->id ) );
		
		/* Delete */
		parent::delete();
		
		/* Recount how many gateways support cards */
		static::recountCardStorageGateways();
	}
	
	/**
	 * Recount card storage gateays
	 *
	 * @return	void
	 */
	protected static function recountCardStorageGateways()
	{
		foreach ( array( 'card_storage_gateways' => count( static::cardStorageGateways() ), 'billing_agreement_gateways' => count( static::billingAgreementGateways() ) ) as $k => $v )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $v ), array( 'conf_key=?', $k ) );
			\IPS\Settings::i()->$k = $v;
		}
		unset( \IPS\Data\Store::i()->settings );
	}
	
	/* !Features (Each gateway will override) */

	const SUPPORTS_REFUNDS = FALSE;
	const SUPPORTS_PARTIAL_REFUNDS = FALSE;
	
	/**
	 * Check the gateway can process this...
	 *
	 * @param	$amount			\IPS\nexus\Money	The amount
	 * @param	$billingAddress	\IPS\GeoLocation	The billing address
	 * @return	bool
	 */
	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress )
	{
		if ( $this->countries and $this->countries !== '*' )
		{
			return in_array( $billingAddress->country, explode( ',', $this->countries ) );
		}
		
		return TRUE;
	}
	
	/**
	 * Can store cards?
	 *
	 * @return	bool
	 */
	public function canStoreCards()
	{
		return FALSE;
	}
	
	/**
	 * Admin can manually charge using this gateway?
	 *
	 * @return	bool
	 */
	public function canAdminCharge()
	{
		return FALSE;
	}
	
	/**
	 * Supports billing agreements?
	 *
	 * @return	bool
	 */
	public function billingAgreements()
	{
		return FALSE;
	}
	
	/* !Payment Gateway */
	
	/**
	 * Payment Screen Fields
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	Invoice
	 * @param	\IPS\nexus\Money	$amount		The amount to pay now
	 * @param	\IPS\Member			$member		The member the payment screen is for (if in the ACP charging to a member's card) or NULL for currently logged in member
	 * @param	array				$recurrings	Details about recurring costs
	 * @return	array
	 */
	public function paymentScreen( \IPS\nexus\Invoice $invoice, \IPS\nexus\Money $amount, \IPS\Member $member = NULL, $recurrings = array() )
	{
		return array();
	}

	/**
	 * Authorize
	 *
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	array|\IPS\nexus\Customer\CreditCard	$values			Values from form OR a stored card object if this gateway supports them
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @param	array									$recurrings		Details about recurring costs
	 * @return	\IPS\DateTime|NULL						Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException							Message will be displayed to user
	 */
	public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL, $recurrings = array() )
	{
		return NULL;
	}
	
	/**
	 * Void
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\Exception
	 */
	public function void( \IPS\nexus\Transaction $transaction )
	{
		return $this->refund( $transaction );
	}
	
	/**
	 * Capture
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\LogicException
	 */
	public function capture( \IPS\nexus\Transaction $transaction )
	{
		
	}
	
	/**
	 * Refund
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction to be refunded
	 * @param	float|NULL				$amount			Amount to refund (NULL for full amount - always in same currency as transaction)
	 * @return	mixed									Gateway reference ID for refund, if applicable
	 * @throws	\Exception
 	 */
	public function refund( \IPS\nexus\Transaction $transaction, $amount = NULL )
	{
		throw new \Exception;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse		int						id				ID number
	 * @apiresponse		string					name			Name
	 */
	public function apiOutput()
	{
		return array(
			'id'	=> $this->id,
			'name'	=> $this->_title
		);
	}
}