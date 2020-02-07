<?php
/**
 * @brief		Fraud Rule Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Fraud;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Fraud Rule Node
 */
class _Rule extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_fraud_rules';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'f_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'order';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'fraud_rules';
	
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
		'all'		=> 'fraud_manage'
	);
	
	/**
	 * [Node] Get title
	 *
	 * @return	string
	 */
	protected function get__title()
	{
		return $this->name;
	}
	
	/**
	 * [Node] Get description
	 *
	 * @return	string
	 */
	protected function get__description()
	{
		$conditions = array();
		$results = array();
		
		/* Amount */
		if ( $this->amount )
		{
			$amounts = array();
			foreach ( $this->amount_unit as $currency => $amount )
			{
				$amounts[] = (string) new \IPS\nexus\Money( $amount, $currency );
			}
			
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_amount', FALSE, array( 'sprintf' => array( $this->_gle( $this->amount ), implode( ' ' . \IPS\Member::loggedIn()->language()->addToStack('or') . ' ', $amounts ) ) ) );
		}
		
		/* Methods */
		if ( $this->methods != '*' )
		{
			$paymentMethods = array();
			foreach ( \IPS\nexus\Gateway::roots() as $gateway )
			{
				$paymentMethods[ $gateway->id ] = $gateway->_title;
			}
			
			$_paymentMethods = array();
			foreach ( explode( ',', $this->methods ) as $m )
			{
				$_paymentMethods[] = $paymentMethods[ $m ];
			}
			
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_methods', FALSE, array( 'sprintf' => array( implode( ' ' . \IPS\Member::loggedIn()->language()->addToStack('or') . ' ', $_paymentMethods ) ) ) );
		}
		
		/* Coupon */
		if ( $this->coupon )
		{
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( $this->coupon == 1 ? 'f_blurb_coupon_y' : 'f_blurb_coupon_n' );
		}
		
		/* Countries */
		if ( $this->country != '*' )
		{			
			$countries = array();
			foreach ( explode( ',', $this->country ) as $m )
			{
				$countries[] = \IPS\Member::loggedIn()->language()->addToStack( 'country-' . $m );
			}
			
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_countries', FALSE, array( 'sprintf' => array( implode( ' ' . \IPS\Member::loggedIn()->language()->addToStack('or') . ' ', $countries ) ) ) );
		}
		
		/* Email */
		if ( $this->email )
		{
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_email', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $this->email == 'c' ? 'contains' : 'is' ), $this->email_unit ) ) );
		}
		
		/* Approved transactions */
		if ( $this->trans_okay )
		{
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_trans_okay', FALSE, array( 'sprintf' => array( $this->_gle( $this->trans_okay ), $this->trans_okay_unit ) ) );
		}
		
		/* Refused transactions */
		if ( $this->trans_fraud )
		{
			$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_trans_fraud', FALSE, array( 'sprintf' => array( $this->_gle( $this->trans_fraud ), $this->trans_fraud_unit ) ) );
		}
		
		/* MaxMind */
		if ( \IPS\Settings::i()->maxmind_key )
		{
			/* Score */
			if ( $this->maxmind )
			{
				$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_maxmind', FALSE, array( 'sprintf' => array( $this->_gle( $this->maxmind ), $this->maxmind_unit ) ) );
			}
			
			/* Proxy score */
			if ( $this->f_maxmind_proxy )
			{
				$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_maxmind_proxy', FALSE, array( 'sprintf' => array( $this->_gle( $this->maxmind_proxy ), $this->maxmind_proxy_unit ) ) );
			}
			
			/* Other */
			foreach ( array( 'maxmind_address_match', 'maxmind_address_valid', 'maxmind_phone_match', 'maxmind_freeemail', 'maxmind_riskyemail' ) as $k )
			{
				if ( $this->$k )
				{
					$conditions[] = \IPS\Member::loggedIn()->language()->addToStack( $this->$k == 1 ? "f_blurb_{$k}_y" : "f_blurb_{$k}_y" );
				}
			}
		}
		
		/* Action */
		$results[] = "&rarr; " . \IPS\Member::loggedIn()->language()->addToStack( 'f_action_' . $this->action );
		
		/* Return */
		return implode( \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_join' ), $conditions ) . '<br>' . implode( \IPS\Member::loggedIn()->language()->addToStack( 'f_blurb_join' ), $results );
	}
	
	/**
	 * Get greater than/less than/equal to language string
	 *
	 * @param	string	$value	g, l or e
	 * @return	string
	 */
	protected function _gle( $value )
	{
		switch ( $value )
		{
			case 'g':
				$lang = 'gt';
				break;
			case 'l':
				$lang = 'lt';
				break;
			case 'e':
				$lang = 'exactly';
				break;
		}
		return mb_strtolower( \IPS\Member::loggedIn()->language()->addToStack( $lang ) );
	}
	
	/**
	 * Get amount unit
	 *
	 * @return	array
	 */
	public function get_amount_unit()
	{
		return ( isset( $this->_data['amount_unit'] ) and $this->_data['amount_unit'] ) ? json_decode( $this->_data['amount_unit'], TRUE ) : array();
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{		
		$paymentMethods = array();
		foreach ( \IPS\nexus\Gateway::roots() as $gateway )
		{
			$paymentMethods[ $gateway->id ] = $gateway->_title;
		}
		
		$countries = array();
		foreach ( \IPS\GeoLocation::$countries as $k => $v )
		{
			$countries[ $v ] = 'country-' . $v;
		}
				
		$yesNoEither = array( 0 => 'any_value', 1 => 'yes', -1 => 'no' );
		
		$form->addTab( 'fraud_rule_settings' );
		$form->add( new \IPS\Helpers\Form\Text( 'f_name', $this->name, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'f_action', $this->action ?: 'hold', TRUE, array( 'options' => array( 'okay' => 'f_action_okay', 'hold' => 'f_action_hold', 'fail' => 'f_action_fail' ) ) ) );
		
		$form->addTab( 'fraud_rule_transaction' );
		$form->add( $this->_combine( 'f_amount', 'IPS\nexus\Form\Money' ) );		
		$form->add( new \IPS\Helpers\Form\Node( 'f_methods', ( !$this->methods or $this->methods === '*' ) ? 0 : explode( ',', $this->methods ), FALSE, array( 'class' => 'IPS\nexus\Gateway', 'multiple' => TRUE, 'zeroVal' => 'any' ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'f_coupon', $this->coupon, FALSE, array( 'options' => $yesNoEither ) ) );

		$form->addTab( 'fraud_rule_customer' );
		$form->add( new \IPS\Helpers\Form\Select( 'f_country', ( !$this->country or $this->country === '*' ) ? '*' : explode( ',', $this->country ), FALSE, array( 'options' => $countries, 'multiple' => TRUE, 'class' => 'ipsField_long', 'unlimited' => '*', 'unlimitedLang' => 'any' ) ) );
		$form->add( $this->_combine( 'f_email', 'IPS\Helpers\Form\Text' ) );
		$form->add( $this->_combine( 'f_trans_okay', 'IPS\Helpers\Form\Number' ) );
		$form->add( $this->_combine( 'f_trans_fraud', 'IPS\Helpers\Form\Number' ) );
		
		$form->addTab( 'fraud_rule_maxmind' );
		if ( \IPS\Settings::i()->maxmind_key )
		{
			$form->add( $this->_combine( 'f_maxmind', 'IPS\Helpers\Form\Number', array( 'min' => 0, 'max' => 100, 'decimals' => 2 ) ) );
			$form->add( $this->_combine( 'f_maxmind_proxy', 'IPS\Helpers\Form\Number', array( 'min' => 0, 'max' => 4, 'decimals' => 2 ) ) );
			$form->add( new \IPS\Helpers\Form\Radio( 'f_maxmind_address_match', $this->maxmind_address_match, FALSE, array( 'options' => $yesNoEither ) ) );
			$form->add( new \IPS\Helpers\Form\Radio( 'f_maxmind_address_valid', $this->maxmind_address_valid, FALSE, array( 'options' => $yesNoEither ) ) );
			$form->add( new \IPS\Helpers\Form\Radio( 'f_maxmind_phone_match', $this->maxmind_phone_match, FALSE, array( 'options' => $yesNoEither, 'toggles' => array( 1 => array( 'f_maxmind_phone_match_warning' ), -1 => array( 'f_maxmind_phone_match_warning' ) ) ), NULL, NULL, NULL, 'f_maxmind_phone_match' ) );
			$form->add( new \IPS\Helpers\Form\Radio( 'f_maxmind_freeemail', $this->maxmind_freeemail, FALSE, array( 'options' => $yesNoEither ) ) );
			$form->add( new \IPS\Helpers\Form\Radio( 'f_maxmind_riskyemail', $this->maxmind_riskyemail, FALSE, array( 'options' => $yesNoEither ) ) );
		}
				
	}
	
	/**
	 * Combine two fields
	 *
	 * @param	string	$name			Field name
	 * @param	bool	$field2Class	Classname for second field
	 * @param	array	$options		Additional options for second field
	 * @return	\IPS\Helpers\Form\Custom
	 */
	public function _combine( $name, $field2Class, $_options=array(), $field2Suffix=NULL )
	{
		$field1Key = mb_substr( $name, 2 );
		$field2Key = $field1Key . '_unit';
		
		if ( in_array( $field2Class, array( 'IPS\nexus\Form\Money', 'IPS\Helpers\Form\Number' ) ) )
		{
			$options = array(
				'options' => array(
					''	=> 'any_value',
					'g'	=> 'gt',
					'e'	=> 'exactly',
					'l'	=> 'lt'
				),
				'toggles' => array(
					'g'	=> array( $name . '_unit' ),
					'e'	=> array( $name . '_unit' ),
					'l'	=> array( $name . '_unit' ),
				)
			);
		}
		else
		{
			$options = array(
				'options' => array(
					''	=> 'any_value',
					'c'	=> 'contains',
					'e'	=> 'exactly',
				),
				'toggles' => array(
					'c'	=> array( $name . '_unit' ),
					'e'	=> array( $name . '_unit' ),
				)
			);
		}
		
		$field1 = new \IPS\Helpers\Form\Select( $name . '_type', $this->$field1Key, FALSE, $options, NULL, NULL, NULL );
		$field2 = new $field2Class( $field1Key . '_unit', $this->$field2Key, FALSE, $_options );
		
		return new \IPS\Helpers\Form\Custom( $name, array( $this->$field1Key, $this->$field2Key ), FALSE, array(
			'getHtml'	=> function() use ( $name, $field1, $field2 )
			{
				return \IPS\Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->combined( $name, $field1, $field2 );
			},
			'formatValue'	=> function() use ( $field1, $field2 )
			{
				return array( $field1->value, $field2->value );
			},
			'validate'		=> function() use( $name, $field1, $field2 )
			{
				$field1->validate();
				$field2->validate();
			} 
		) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		foreach ( array( 'f_amount', 'f_email', 'f_trans_okay', 'f_trans_fraud', 'f_maxmind', 'f_maxmind_proxy' ) as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$values[ $k . '_unit' ] = $values[ $k ][1];
				$values[ $k ] = $values[ $k ][0];
			}
		}
		
		if( isset( $values['f_methods'] ) )
		{
			$values['f_methods'] = is_array( $values['f_methods'] ) ? implode(',', array_keys( $values['f_methods'] ) ) : '*';
		}

		if( isset( $values['f_country'] ) )
		{
			$values['f_country'] = is_array( $values['f_country'] ) ? implode(',', $values['f_country'] ) : '*';
		}
		
		if( isset( $values['f_amount_unit'] ) )
		{
			$amounts = array();
			foreach ( $values['f_amount_unit'] as $amount )
			{
				$amounts[ $amount->currency ] = $amount->amount;
			}
			$values['f_amount_unit'] = json_encode( $amounts );
		}
				
		return $values;
	}
	
	/** 
	 * Check if rule matches transaction
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	The transaction
	 * @return	bool
	 */
	public function matches( \IPS\nexus\Transaction $transaction )
	{		
		/* Amount */
		if ( $this->amount )
		{
			$amounts = $this->amount_unit;
			if ( !$this->_checkCondition( $transaction->amount->amount, $this->amount, $amounts[ $transaction->currency ] ) )
			{
				return FALSE;
			}
		}
		
		/* Methods */
		if ( $this->methods != '*' )
		{
			if ( !in_array( $transaction->method->id, explode(',', $this->methods ) ) )
			{
				return FALSE;
			}
		}
		
		/* Coupon */
		if ( $this->coupon )
		{
			$couponUsed = FALSE;
			foreach ( $transaction->invoice->items as $item )
			{
				if ( $item instanceof \IPS\nexus\extensions\nexus\Item\CouponDiscount )
				{
					$couponUsed = TRUE;
					break;
				}
			}
			
			if ( $this->coupon == 1 and !$couponUsed )
			{
				return FALSE;
			}
			if ( $this->coupon == -1 and $couponUsed )
			{
				return FALSE;
			}
		}
		
		/* Country */
		if ( $this->country != '*' )
		{
			if ( !in_array( $transaction->invoice->billaddress->country, explode(',', $this->country ) ) )
			{
				return FALSE;
			}
		}
		
		/* Email */
		if ( $this->email )
		{
			if ( !$this->_checkCondition( $transaction->member->email, $this->email, $this->email_unit ) )
			{
				return FALSE;
			}
		}
		
		/* Approved transactions */
		if ( $this->trans_okay )
		{
			if ( !$this->_checkCondition( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_member=? AND t_status=?', $transaction->member->member_id, \IPS\nexus\Transaction::STATUS_PAID ) )->first(), $this->trans_okay, $this->trans_okay_unit ) )
			{
				return FALSE;
			}
		}
		
		/* Refused transactions */
		if ( $this->trans_fraud )
		{
			if ( !$this->_checkCondition( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_transactions', array( 't_member=? AND t_status=? AND t_fraud_blocked<>0', $transaction->member->member_id, \IPS\nexus\Transaction::STATUS_REFUSED ) )->first(), $this->trans_fraud, $this->trans_fraud_unit ) )
			{
				return FALSE;
			}
		}
		
		/* MaxMind */
		if ( \IPS\Settings::i()->maxmind_key and ( $this->maxmind or $this->maxmind_proxy or $this->maxmind_address_match or $this->maxmind_address_valid or $this->maxmind_phone_match or $this->maxmind_freeemail or $this->maxmind_riskyemail ) )
		{
			/* If there was an error, we cannot check any of these */
			$maxMind = $transaction->fraud;
			if ( $maxMind->err )
			{
				return FALSE;
			}
			
			/* Score */
			if ( $this->maxmind )
			{
				if ( !$this->_checkCondition( $maxMind->score, $this->maxmind, $this->maxmind_unit ) )
				{
					return FALSE;
				}
			}
			
			/* Proxy score */
			if ( $this->maxmind_proxy )
			{
				if ( !$this->_checkCondition( $maxMind->proxyScore, $this->maxmind_proxy, $this->maxmind_proxy_unit ) )
				{
					return FALSE;
				}
			}
			
			/* Address match */
			if ( $this->maxmind_address_match )
			{
				if ( !$this->_checkCondition( $maxMind->countryMatch, 'mm', $this->maxmind_address_match ) )
				{
					return FALSE;
				}
			}
			
			/* Address valid */
			if ( $this->maxmind_address_valid )
			{
				if ( !$this->_checkCondition( $maxMind->cityPostalMatch, 'mm', $this->maxmind_address_valid ) )
				{
					return FALSE;
				}
			}
			
			/* Phone match */
			if ( $this->maxmind_phone_match )
			{
				if ( !$this->_checkCondition( $maxMind->custPhoneInBillingLoc, 'mm', $this->maxmind_phone_match ) )
				{
					return FALSE;
				}
			}
			
			/* Free email */
			if ( $this->maxmind_freeemail )
			{
				if ( !$this->_checkCondition( $maxMind->freeMail, 'mm', $this->maxmind_freeemail ) )
				{
					return FALSE;
				}
			}
			
			/* High-risk email */
			if ( $this->maxmind_riskyemail )
			{
				if ( !$this->_checkCondition( $maxMind->carderEmail, 'mm', $this->maxmind_riskyemail ) )
				{
					return FALSE;
				}
			}
		}
		
		/* Still here? Return true */
		return TRUE;		
	}
	
	/**
	 * Check condition
	 *
	 * @param	mixed	$a			First parameter
	 * @param	string	$operator	Operator (g = greater than, e = equal to, l = less than, c = contains, mm = MaxMind)
	 * @param	mixed	$b			Second parameter
	 * @return	bool
	 */
	protected function _checkCondition( $a, $operator, $b )
	{
		if ( $a instanceof \IPS\Math\Number and !( $b instanceof \IPS\Math\Number ) )
		{
			$b = new \IPS\Math\Number( "{$b}" );
		}
		if ( !( $a instanceof \IPS\Math\Number ) and $b instanceof \IPS\Math\Number )
		{
			$a = new \IPS\Math\Number( "{$a}" );
		}
		
		switch ( $operator )
		{
			case 'g':
				if ( $a instanceof \IPS\Math\Number )
				{
					return $a->compare( $b ) === 1;
				}
				else
				{
					return $a > $b;
				}
			case 'e':
				if ( $a instanceof \IPS\Math\Number )
				{
					return $a->compare( $b ) === 0;
				}
				else
				{
					return $a == $b;
				}
			case 'l':
				if ( $a instanceof \IPS\Math\Number )
				{
					return $a->compare( $b ) === 11;
				}
				else
				{
					return $a < $b;
				}
			case 'c':
				return mb_strpos( $a, $b ) !== FALSE;
			case 'mm':
				$a = mb_strtolower( $a );
				if ( $a === 'yes' )
				{
					return $b == 1;
				}
				elseif ( $a === 'no' )
				{
					return $b == -1;
				}
				else
				{
					return FALSE;
				}
				break;
		}
		return FALSE;
	}
	
	/** 
	 * Check if one rule is a super-set of another
	 *
	 * @param	\IPS\nexus\Fraud\Rule	$other	Other rule
	 * @return	bool
	 */
	public function isSubsetOf( \IPS\nexus\Fraud\Rule $other )
	{
		/* Amount */
		if ( $this->amount != $other->amount )
		{
			return FALSE;
		}
		elseif ( $this->amount xor $other->amount )
		{
			if ( !$this->amount )
			{
				return FALSE;
			}
		}
		elseif ( $this->amount and $other->amount )
		{
			$otherAmounts = $other->amount_unit;
			foreach ( $this->amount_unit as $currency => $amount )
			{
				if ( $this->amount == 'e' )
				{
					if ( $amount != $otherAmounts[ $currency ] )
					{
						return FALSE;
					}
				}
				elseif ( $this->amount == 'g' )
				{
					if ( $amount < $otherAmounts[ $currency ] )
					{
						return FALSE;
					}
				}
				elseif ( $this->amount == 'l' )
				{
					if ( $amount > $otherAmounts[ $currency ] )
					{
						return FALSE;
					}
				}
			}
		}
		
		/* Methods */
		if ( $this->methods != '*' or $other->methods != '*' )
		{
			$thisPaymentMethods = $this->methods === '*' ? array_keys( \IPS\nexus\Gateway::roots() ) : explode( ',', $this->methods );
			$otherPaymentMethods = $other->methods === '*' ? array_keys( \IPS\nexus\Gateway::roots() ) : explode( ',', $other->methods );
			$diff = array_diff( $thisPaymentMethods, $otherPaymentMethods );
			
			if ( !empty( $diff ) )
			{
				return FALSE;
			}
		}
				
		/* Yes/No */
		foreach ( array( 'coupon', 'maxmind_address_match', 'maxmind_address_valid', 'maxmind_phone_match', 'maxmind_freeemail', 'maxmind_riskyemail' ) as $k )
		{
			if ( $this->$k != $other->$k )
			{
				return FALSE;
			}
		}
		
		/* Countries */
		if ( $this->country != '*' or $other->country != '*' )
		{
			$thisCountries = $this->country === '*' ? array_values( \IPS\GeoLocation::$countries ) : explode( ',', $this->country );
			$otherCountries = $other->country === '*' ? array_values( \IPS\GeoLocation::$countries ) : explode( ',', $other->country );
			$diff = array_diff( $thisCountries, $otherCountries );
			
			if ( !empty( $diff ) )
			{
				return FALSE;
			}
		}
		
		/* Email */
		if ( $this->email != $other->email )
		{
			return FALSE;
		}
		elseif ( $this->email xor $other->email )
		{
			if ( !$this->email )
			{
				return FALSE;
			}
		}
		elseif ( $this->email and $other->email )
		{
			if ( $this->email == 'e' )
			{
				if ( $this->email_unit != $other->email_unit )
				{
					return FALSE;
				}
			}
			elseif ( $this->email == 'c' )
			{
				if ( mb_strpos( $other->email_unit, $this->email_unit ) !== FALSE )
				{
					return FALSE;
				}
			}
		}
		
		/* Numeric */
		foreach ( array( 'trans_okay', 'trans_fraud', 'maxmind', 'maxmind_proxy' ) as $k )
		{
			if ( $this->$k != $other->$k )
			{
				return FALSE;
			}
			elseif ( $this->$k xor $other->$k )
			{
				if ( !$this->$k )
				{
					return FALSE;
				}
			}
			elseif ( $this->$k and $other->$k )
			{
				$unitKey = "{$k}_unit";
				
				if ( $this->$k == 'e' )
				{
					if ( $this->$unitKey != $other->$unitKey )
					{
						return FALSE;
					}
				}
				elseif ( $this->$k == 'g' )
				{
					if ( $this->$unitKey < $other->$unitKey )
					{
						return FALSE;
					}
				}
				elseif ( $this->$k == 'l' )
				{
					if ( $this->$unitKey > $other->$unitKey )
					{
						return FALSE;
					}
				}
			}
		}
		
		/* Still here - return TRUE */
		return TRUE;
	}
}