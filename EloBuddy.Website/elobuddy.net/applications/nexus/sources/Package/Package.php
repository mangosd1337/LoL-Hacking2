<?php
/**
 * @brief		Package Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
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
 * Package
 */
class _Package extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_packages';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'p_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $parentNodeColumnId = 'group';
	
	/**
	 * @brief	[Node] Parent Node Class
	 */
	public static $parentNodeClass = 'IPS\nexus\Package\Group';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'menu__nexus_store_packages';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'nexus_package_';

	/**
	 * @@rief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = '_desc';
	
	/* !ActiveRecord */
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$classname = 'IPS\nexus\Package\\' . ucfirst( $data['p_type'] );
		if ( isset( $classname::$packageDatabaseTable ) )
		{
			$data = array_merge( $data, \IPS\Db::i()->select( '*', $classname::$packageDatabaseTable, array( 'p_id=?', $data['p_id'] ) )->first() );
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
		'module'	=> 'store',
		'prefix'	=> 'packages_',
	);
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->member_groups = '*';
		$this->date_added = time();
		$this->store = TRUE;
	}
		
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		$data = $this->changed;

		$secondaryTable = array();
		foreach ( $this->changed as $k => $v )
		{
			if ( isset( static::$packageDatabaseColumns ) and in_array( "p_{$k}", static::$packageDatabaseColumns ) )
			{
				$secondaryTable[ "p_{$k}" ] = $v;
				unset( $this->changed[ $k ] );
			}
			elseif ( !in_array( "p_{$k}", array( 'p_id', 'p_name', 'p_seo_name', 'p_desc', 'p_group', 'p_stock', 'p_reg', 'p_store', 'p_member_groups', 'p_allow_upgrading', 'p_upgrade_charge', 'p_allow_downgrading', 'p_downgrade_refund', 'p_base_price', 'p_tax', 'p_renewal_days', 'p_primary_group', 'p_secondary_group', 'p_return_primary', 'p_return_secondary', 'p_position', 'p_associable', 'p_force_assoc', 'p_assoc_error', 'p_discounts', 'p_page', 'p_support', 'p_support_department', 'p_support_severity', 'p_featured', 'p_upsell', 'p_notify', 'p_type', 'p_custom', 'p_reviewable', 'p_review_moderate', 'p_image', 'p_methods', 'p_renew_options', 'p_group_renewals', 'p_rebuild_thumb', 'p_renewal_days_advance', 'p_date_added', 'p_reviews', 'p_rating', 'p_grace_period' ) ) )
			{
				unset( $this->changed[ $k ] );
			}
		}
		
		if ( isset( $data['base_price'] ) )
		{
			$decoded = json_decode( $data['base_price'], TRUE );
			if ( $decoded and is_array( $decoded ) )
			{
				$prices = array( 'id' => $this->id );
				foreach ( $decoded as $currency => $value )
				{
					if ( !\IPS\Db::i()->checkForColumn( 'nexus_package_base_prices', $currency ) )
					{
						\IPS\Db::i()->addColumn( 'nexus_package_base_prices', array(
							'name'	=> $currency,
							'type'	=> 'FLOAT'
						) );
					}
					$prices[ $currency ] = $value['amount'];
				}
				\IPS\Db::i()->replace( 'nexus_package_base_prices', $prices );
			}
		}

		parent::save();
		$this->changed = $data;
		
		if ( !empty( $secondaryTable ) AND isset( static::$packageDatabaseTable ) )
		{
			\IPS\Db::i()->update( static::$packageDatabaseTable, $secondaryTable, array( 'p_id=?', $this->id ) );
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
		\IPS\Db::i()->delete( static::$packageDatabaseTable, array( 'p_id=?', $this->id ) );
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$primaryTable = array();
		$secondaryTable = array();
		foreach ( $this->_data as $k => $v )
		{
			if ( $k != 'id' )
			{
				if ( in_array( "p_{$k}", static::$packageDatabaseColumns ) )
				{
					$secondaryTable[ "p_{$k}" ] = $v;
				}
				else
				{
					$primaryTable[ "p_{$k}" ] = $v;
				}
			}
		}

		/* We need to update the date_added field so cloned products appear in the new products block */
		$primaryTable['p_date_added'] = time();

		$oldId = $this->_id;
		$id = \IPS\Db::i()->insert( 'nexus_packages', $primaryTable );

		$secondaryTable['p_id'] = $id;
		\IPS\Db::i()->insert( static::$packageDatabaseTable, $secondaryTable );
		
		\IPS\Lang::saveCustom( 'nexus', "nexus_package_{$id}", iterator_to_array( \IPS\Db::i()->select( 'CONCAT(word_custom, \' ' . \IPS\Member::loggedIn()->language()->get('copy_noun') . '\') as word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $this->_id ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		\IPS\Lang::saveCustom( 'nexus', static::$titleLangPrefix . $id . static::$descriptionLangSuffix, iterator_to_array( \IPS\Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $oldId . static::$descriptionLangSuffix ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );

		\IPS\Db::i()->insert( 'nexus_package_base_prices', \IPS\Db::i()->select( "{$id} AS id, " . implode( ',', array_map( function( $val )
		{
			return "`{$val}`";
		}, \IPS\nexus\Money::currencies() ) ), 'nexus_package_base_prices', array( 'id=?', $this->_id ) ) );
		
		foreach ( \IPS\Db::i()->select( '*', 'nexus_package_images', array( 'image_product=?', $this->_id ) ) as $image )
		{
			$file = \IPS\File::get( 'nexus_Products', $image['image_location'] );	
					
			\IPS\Db::i()->insert( 'nexus_package_images', array(
				'image_product'		=> $id,
				'image_location'	=> (string) \IPS\File::create( 'nexus_Products', $file->originalFilename, $file->contents(), $file->container ),
				'image_primary'		=> $image['image_primary']
			) );
		}
		
		\IPS\Db::i()->insert( 'nexus_product_options', \IPS\Db::i()->select( "NULL as opt_id, {$id} AS opt_package, opt_values, opt_stock, opt_base_price, opt_renew_price", 'nexus_product_options', array( 'opt_package=?', $this->_id ) ) );
		
		\IPS\Db::i()->update( 'nexus_package_fields', "cf_packages=CONCAT( cf_packages, ',{$id}' )", \IPS\Db::i()->findInSet( 'cf_packages', array( $this->_id ) ) );
		\IPS\Db::i()->update( 'nexus_referral_rules', "rrule_purchase_packages=CONCAT( rrule_purchase_packages, ',{$id}' )", \IPS\Db::i()->findInSet( 'rrule_purchase_packages', array( $this->_id ) ) );
		\IPS\Db::i()->update( 'nexus_support_departments', "dpt_packages=CONCAT( dpt_packages, ',{$id}' )", \IPS\Db::i()->findInSet( 'dpt_packages', array( $this->_id ) ) );

		$primaryKey = static::$databaseColumnId;
		$this->$primaryKey = $id;
	}
	
	/* !Properties */
	
	/**
	 * Get the lowest price for multiple packages
	 * May return price ($x) or "From $x" or a price struck and replaced with a discount price
	 *
	 * @param	\IPS\Patterns\ActiveRecordIterator	$iterator	Iterator of packages
	 * @param	\IPS\nexus\Customer|NULL			$customer	The customer (NULL for currently logged in member)
	 * @return	string
	 */
	public static function lowestPriceToDisplay( \IPS\Patterns\ActiveRecordIterator $iterator, \IPS\nexus\Customer $customer = NULL )
	{
		$customer = $customer ?: \IPS\nexus\Customer::loggedIn();
		$currency = ( $customer->member_id === \IPS\nexus\Customer::loggedIn()->member_id ) ? ( ( isset( $_SESSION['currency'] ) and in_array( $_SESSION['currency'], \IPS\nexus\Money::currencies() ) ) ? $_SESSION['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		
		$lowest = NULL;
		$_priceMayChange = FALSE;
		$priceMayChange = FALSE;
		foreach ( $iterator as $package )
		{
			$price = $package->_lowestPrice( $customer, $currency, $priceMayChange );
			if ( $lowest !== NULL and $price != $lowest )
			{
				$_priceMayChange = TRUE;
			}
			if ( $lowest === NULL or $price < $lowest )
			{
				$lowest = $price;
			}
		}
		
		return \IPS\Theme::i()->getTemplate( 'store', 'nexus' )->price( new \IPS\nexus\Money( $lowest, $currency ), $priceMayChange or $_priceMayChange );
	}

	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'archive';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'product';
		
	/**
	 * Basic purchase price
	 * May be further adjusted by renewal terms which add
	 *
	 * @param	\IPS\nexus\Customer|NULL	$customer			The customer (NULL for currently logged in member)
	 * @param	bool						$usergroupDiscounts	Account for usergroup discounts?
	 * @param	bool						$loyaltyDiscounts	Account for loyalty discounts?
	 * @param	bool						$bulkDiscounts		Account for bulk discounts?
	 * @param	int							$initialCount		Will assume that number of additional purchases for this package
	 * @return	\IPS\nexus\Money
	 */
	public function price( \IPS\nexus\Customer $customer = NULL, $usergroupDiscounts = TRUE, $loyaltyDiscounts = TRUE, $bulkDiscounts = TRUE, $initialCount = 0 )
	{
		/* Base */
		$customer = $customer ?: \IPS\nexus\Customer::loggedIn();
		$currency = ( $customer->member_id === \IPS\nexus\Customer::loggedIn()->member_id ) ? ( ( isset( $_SESSION['currency'] ) and in_array( $_SESSION['currency'], \IPS\nexus\Money::currencies() ) ) ? $_SESSION['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		$prices = json_decode( $this->base_price, TRUE );
		if ( !isset( $prices[ $currency ] ) )
		{
			throw new \OutOfBoundsException;
		}
		$price = $prices[ $currency ]['amount'];
		$discounts = json_decode( $this->discounts, TRUE );

		/* Usergroup discounts */
		if ( $usergroupDiscounts and isset( $discounts['usergroup'] ) )
		{
			foreach ( $discounts['usergroup'] as $discount )
			{
				if ( $discount['price'][ $currency ] < $price )
				{
					if ( $discount['secondary'] )
					{
						$inGroup = $customer->inGroup( $discount['group'] );
					}
					else
					{
						$inGroup = $customer->member_group_id == $discount['group'];
					}
										
					if ( $inGroup )
					{
						$price = $discount['price'][ $currency ];
					}
				}
			}
		}
		
		/* Loyalty discounts */
		if ( $loyaltyDiscounts and isset( $discounts['loyalty'] ) )
		{
			foreach ( $discounts['loyalty'] as $discount )
			{
				$count = ( ( $discount['package'] ?: $this->id ) == $this->id ) ? $initialCount : 0;
				if ( $discount['price'][ $currency ] < $price )
				{
					$count += $customer->member_id ? $customer->previousPurchasesCount( $discount['package'] ?: $this->id, $discount['active'] ) : 0;
				}
				if ( $customer->member_id === \IPS\Member::loggedIn()->member_id and isset( $_SESSION['cart'] ) )
				{
					foreach ( $_SESSION['cart'] as $item )
					{
						if ( $item->id == $this->id )
						{
							$count += $item->quantity;
						}
					}
				}
				
				if ( $count >= $discount['owns'] )
				{
					$price = $discount['price'][ $currency ];
				}
			}
		}
		
		/* Bulk Discounts */
		if ( $bulkDiscounts and isset( $discounts['bulk'] ) )
		{
			foreach ( $discounts['bulk'] as $discount )
			{
				$discountPackageId = ( $discount['package'] ?: $this->id );
				$count = ( $discountPackageId == $this->id ) ? $initialCount : 0;
				if ( $customer->member_id === \IPS\Member::loggedIn()->member_id and isset( $_SESSION['cart'] ) )
				{
					foreach ( $_SESSION['cart'] as $item )
					{
						if ( $item->id == $discountPackageId )
						{
							$count += $item->quantity;
						}
					}
				}
				
				if ( $count and ( ( $count + 1 ) % ( $discount['buying'] + 1 ) == 0 ) )
				{
					$price = $discount['price'][ $currency ];
				}
			}
		}

		/* Return */
		return new \IPS\nexus\Money( $price, $currency );
	}
		
	/**
	 * Get the lowest price
	 *
	 * @param	\IPS\nexus\Customer|NULL	$customer					The customer
	 * @param	string						$currency					Currency
	 * @param	bool						$priceMayChange				[Reference] will be set to a value indicating if the price might change (i.e. should be displayed as "From $x")
	 * @param	float						$priceNotIncludingDiscounts	[Reference] will be set to the lowest price not taking discounts into consideration
	 * @return	float
	 */
	protected function _lowestPrice( \IPS\nexus\Customer $customer, $currency, &$priceMayChange = FALSE, &$priceNotIncludingDiscounts = NULL )
	{
		/* What's the base price with and without discounts? */
		$baseNoDiscounts = $this->price( $customer, FALSE, FALSE, FALSE )->amount;
		$base = $this->price( $customer )->amount;
		
		/* Adjustments based on custom fields */
		if ( $this->stock == -2 )
		{
			$mostReducedOption = NULL;
			foreach ( \IPS\Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=?', $this->id ) ) as $option )
			{
				$basePriceAdjustments = json_decode( $option['opt_base_price'], TRUE );
				if ( isset( $basePriceAdjustments[ $currency ] ) and ( $basePriceAdjustments[ $currency ] or $basePriceAdjustments[ $currency ] === '0' ) )
				{
					if ( $mostReducedOption === NULL or $basePriceAdjustments[ $currency ] < $mostReducedOption )
					{
						$mostReducedOption = new \IPS\Math\Number( number_format( $basePriceAdjustments[ $currency ], \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) );
					}
					
					$priceMayChange = TRUE;
				}
			}
			$mostReducedOption = $mostReducedOption ?: new \IPS\Math\Number('0');
			
			$baseNoDiscounts = $baseNoDiscounts->add( $mostReducedOption );
			$base = $base->add( $mostReducedOption );
		}
		
		/* Init */
		$lowestNoDiscounts = $baseNoDiscounts;
		$lowest = $base;

		/* It's possible the base price is $0, but all the renewal options add, so let's look at that */
		$renewOptions = $this->renew_options ? json_decode( $this->renew_options, TRUE ) : array();
		if ( !empty( $renewOptions ) )
		{
			$lowestNoDiscounts = NULL;
			$lowest = NULL;
			foreach ( $renewOptions as $term )
			{
				if ( $term['add'] )
				{
					if ( count( $renewOptions ) > 1 )
					{
						$priceMayChange = TRUE;
					}
					$_lowest = $base->add( new \IPS\Math\Number( number_format( $term['cost'][ $currency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
					$_lowestNoDiscounts = $baseNoDiscounts->add( new \IPS\Math\Number( number_format( $term['cost'][ $currency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
				}
				else
				{
					$_lowest = $base;
					$_lowestNoDiscounts = $baseNoDiscounts;
				}
				
				if ( $lowest === NULL or $_lowest->compare( $lowest ) === -1 )
				{
					$lowest = $_lowest;
				}
				if ( $lowestNoDiscounts === NULL or $_lowestNoDiscounts->compare( $lowestNoDiscounts ) === -1 )
				{
					$lowestNoDiscounts = $_lowestNoDiscounts;
				}
			}
		}
					
		/* Add tax? */
		if ( \IPS\Settings::i()->nexus_show_tax and $this->tax )
		{
			try
			{
				/* Load tax rate */
				$tax = \IPS\nexus\Tax::load( $this->tax );
								
				/* What is the rate? */
				$taxRate = new \IPS\Math\Number( number_format( $tax->rate( $customer->estimatedLocation() ), 2, '.', '' ) );
				
				/* Add it on */
				$lowest = $lowest->add( $lowest->multiply( $taxRate ) );
				$lowestNoDiscounts = $lowestNoDiscounts->add( $lowestNoDiscounts->multiply( $taxRate ) );
			}
			catch ( \OutOfRangeException $e ) { }
		}

		/* Return */
		$priceNotIncludingDiscounts = $lowestNoDiscounts;
		return $lowest;
	}
		
	/**
	 * Price to display in store
	 * May return price ($x) or "From $x" or a price struck and replaced with a discount price or NULL if not available in desired currency
	 *
	 * @param	\IPS\nexus\Customer|NULL	$customer	The customer (NULL for currently logged in member)
	 * @return	string|NULL
	 */
	public function priceToDisplay( \IPS\nexus\Customer $customer = NULL )
	{
		/* Get customer */
		$customer = $customer ?: \IPS\nexus\Customer::loggedIn();
		$currency = ( $customer->member_id === \IPS\nexus\Customer::loggedIn()->member_id ) ? ( ( isset( $_SESSION['currency'] ) and in_array( $_SESSION['currency'], \IPS\nexus\Money::currencies() ) ) ? $_SESSION['currency'] : $customer->defaultCurrency() ) : $customer->defaultCurrency();
		
		/* Get the price */
		$priceMayChange = FALSE;
		$priceNotIncludingDiscounts = NULL;
		try
		{
			$price = $this->_lowestPrice( $customer, $currency, $priceMayChange, $priceNotIncludingDiscounts );
		}
		catch ( \OutOfBoundsException $e )
		{
			return NULL;
		}

		/* Display */
		if ( $price < $priceNotIncludingDiscounts )
		{
			return \IPS\Theme::i()->getTemplate( 'store', 'nexus' )->priceDiscounted( new \IPS\nexus\Money( $priceNotIncludingDiscounts, $currency ), new \IPS\nexus\Money( $price, $currency ), $priceMayChange );
		}
		else
		{
			return \IPS\Theme::i()->getTemplate( 'store', 'nexus' )->price( new \IPS\nexus\Money( $price, $currency ), $priceMayChange );
		}
	}
	
	/**
	 * Support Severity
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	\IPS\nexus\Support\Severity|NULL
	 */
	public function supportSeverity( \IPS\nexus\Purchase $purchase )
	{
		if ( $this->support_severity )
		{
			try
			{
				return \IPS\nexus\Support\Severity::load( $this->support_severity );
			}
			catch ( \OutOfRangeException $e )
			{
				return NULL;
			}
		}
		return NULL;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=nexus&module=store&controller=product&id={$this->id}", 'front', 'store_product', \IPS\Http\Url::seoTitle( \IPS\Member::loggedIn()->language()->get( 'nexus_package_' . $this->id ) ) );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}

	/**
	 * Get full image URL
	 *
	 * @return string
	 */
	public function get_image()
	{
		return ( $this->_data['image'] ) ? (string) \IPS\File::get( 'nexus_Products', $this->_data['image'] )->url : NULL;
	}
		
	/**
	 * Get additional name info
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	array
	 */
	public function getPurchaseNameInfo( \IPS\nexus\Purchase $purchase )
	{
		$stickyFields = array();
		if ( count( $purchase->custom_fields ) )
		{
			$customFields = \IPS\nexus\Package\CustomField::roots();
			foreach ( $purchase->custom_fields as $k => $v )
			{
				if ( $v and isset( $customFields[ $k ] ) and $customFields[ $k ]->sticky )
				{
					$stickyFields[] = $v;
				}
			}
		}
		return $stickyFields;
	}
	
	/**
	 * @brief	Associable packages
	 */
	protected $_associablePackages = NULL;
	
	/**
	 * Associable Packages
	 *
	 * @return	array
	 */
	public function associablePackages()
	{
		if ( $this->_associablePackages === NULL )
		{
			$this->_associablePackages = array();
			foreach ( explode( ',', $this->associable ) as $id )
			{
				try
				{
					$this->_associablePackages[ $id ] = \IPS\nexus\Package::load( $id );
				}
				catch ( \OutOfRangeException $e ) { }
			}
		}
		return $this->_associablePackages;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = array();
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'purchases_view' ) )
		{
			$buttons['view_purchases'] = array(
				'icon'	=> 'search',
				'title'	=> 'view_purchases',
				'link'	=> $url->setQueryString( array( 'do' => 'viewPurchases', 'id' => $this->_id ) )
			);
		}
		
		$buttons = array_merge( $buttons, parent::getButtons( $url, $subnode ) );
				
		return $buttons;
	}
	
	/* !ACP Package Form */
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$groups = array();
		foreach ( \IPS\Member\Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		$types = array();
		$typeFields = array();
		$typeFieldToggles = array();
		$formId = $this->id ? "form_{$this->id}" : 'form_new';
		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/nexus/sources/Package' ) as $file )
		{
			if ( !$file->isDot() and mb_substr( $file, 0, 1 ) !== '.' and $file != 'Dedi.php' and $file != 'index.html' )
			{
				require_once $file;
				$key = mb_substr( $file, 0, -4 );
				$class = 'IPS\nexus\Package\_' . $key;
				$autoloadClass = "IPS\\nexus\\Package\\{$key}";
				if ( class_exists( $class, FALSE ) and in_array( 'IPS\nexus\Package', class_parents( $class ) ) )
				{
					$forceShow = TRUE;
					$types[ mb_strtolower( $key ) ] = 'p_type_' . $key;
					
					if ( $fields = $autoloadClass::acpFormFields( $this ) and is_array( $fields ) )
					{
						foreach ( $fields as $group => $fields )
						{
							foreach ( $fields as $field )
							{
								if ( $field->name === 'p_show' )
								{
									$forceShow = FALSE;
								}							
								if ( !$field->htmlId )
								{
									$field->htmlId = $field->name;
									$typeFieldToggles[ mb_strtolower( $key ) ][] = $field->htmlId;		
								}	
								$typeFields[ $group ][] = $field;
							}
						}
					}
					
					if ( $forceShow )
					{
						$typeFieldToggles[ mb_strtolower( $key ) ] = array_merge( isset( $typeFieldToggles[ mb_strtolower( $key ) ] ) ? $typeFieldToggles[ mb_strtolower( $key ) ] : array(), array( "{$formId}_tab_package_client_area", "{$formId}_header_package_associations", "{$formId}_header_package_associations_desc", 'p_associate', "{$formId}_header_package_renewals", 'p_renews', 'p_support_severity', 'p_lkey' ) );
					}
				}
			}
		}
						
		$renewOptions = array();
		if ( $this->renew_options and $_renewOptions = json_decode( $this->renew_options, TRUE ) and is_array( $_renewOptions ) )
		{
			foreach ( $_renewOptions as $option )
			{
				$costs = array();
				foreach ( $option['cost'] as $cost )
				{
					$costs[ $cost['currency'] ] = new \IPS\nexus\Money( $cost['amount'], $cost['currency'] );
				}				
				$renewOptions[] = new \IPS\nexus\Purchase\RenewalTerm( $costs, new \DateInterval( "P{$option['term']}" . mb_strtoupper( $option['unit'] ) ), NULL, $option['add'] );
			}
		}
				
		$form->addTab('package_settings');
		$form->addHeader('package_settings');
		$form->add( new \IPS\Helpers\Form\Radio( 'p_type', ( $this->id ? $this->type : 'product' ), TRUE, array( 'options' => $types, 'toggles' => $typeFieldToggles, 'disabled' => (bool) $this->id ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'p_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_package_{$this->id}" : NULL ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_group', $this->group, TRUE, array( 'class' => 'IPS\nexus\Package\Group', 'subnodes' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_custom_fields', $this->id ? \IPS\nexus\Package\CustomField::roots( NULL, NULL, array( array( \IPS\Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ) ) : array(), FALSE, array( 'class' => 'IPS\nexus\Package\CustomField', 'multiple' => TRUE ) ) );
		foreach ( $typeFields['package_settings'] as $field )
		{
			$form->add( $field );
		}
		$form->addHeader('package_registration');
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_reg', $this->reg, FALSE, array(), function( $val )
		{
			if ( $val and ( !\IPS\Request::i()->p_store_checkbox or ( \IPS\Request::i()->p_member_groups and ( !in_array( \IPS\Settings::i()->guest_group, \IPS\Request::i()->p_member_groups ) and \IPS\Request::i()->p_member_groups_unlimited != "*" ) ) ) )
			{
				throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'p_reg_err', FALSE, array( 'sprintf' => array( \IPS\Member\Group::load( \IPS\Settings::i()->guest_group )->name ) ) ) );
			}
		} ) );
		$form->addHeader('package_associations');
		$form->addMessage('package_associations_desc');
		$form->add( new \IPS\Helpers\Form\Radio( 'p_associate', ( $this->force_assoc and count( $this->associablePackages() ) ) ? 2 : ( count( $this->associablePackages() ) ? 1 : 0 ), FALSE, array(
			'options' => array(
				0	=> 'p_associate_none',
				1	=> 'p_associate_optional',
				2	=> 'p_associate_required'
			),
			'toggles'	=> array(
				1	=> array( 'p_associable', 'p_group_renewals', 'p_upsell' ),
				2	=> array( 'p_associable', 'p_group_renewals', 'p_assoc_error_editor', 'p_upsell' )
			)
		), NULL, NULL, NULL, 'p_associate' ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_associable', $this->associablePackages(), FALSE, array( 'class' => 'IPS\nexus\Package\Group', 'multiple' => TRUE, 'permissionCheck' => function( $node )
		{
			return !( $node instanceof \IPS\nexus\Package\Group );
		} ), NULL, NULL, NULL, 'p_associable' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_group_renewals', $this->group_renewals ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'p_group_renewals' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'p_assoc_error', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_package_{$this->id}_assoc" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-pkg-{$this->id}-assoc" : "nexus-new-pkg-assoc" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pkg-assoc' ) : NULL, 'minimize' => 'p_assoc_error_placeholder'
			)
		), NULL, NULL, NULL, 'p_assoc_error_editor' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_upsell', $this->upsell ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'p_upsell' ) );

		$form->addTab( 'package_pricing' );
		$form->addHeader( 'package_purchase_price' );
		$form->add( new \IPS\nexus\Form\Money( 'p_base_price', $this->base_price, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_tax', (int) $this->tax, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ) ) );
		$form->addHeader('package_discounts');
		$discounts = json_decode( $this->discounts, TRUE );
		$form->add( new \IPS\Helpers\Form\Stack( 'p_usergroup_discounts', isset( $discounts['usergroup'] ) ? $discounts['usergroup'] : array(), FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\DiscountUsergroup' ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'p_loyalty_discounts', isset( $discounts['loyalty'] ) ? $discounts['loyalty'] : array(), FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\DiscountLoyalty' ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'p_bulk_discounts', isset( $discounts['bulk'] ) ? $discounts['bulk'] : array(), FALSE, array( 'stackFieldType' => 'IPS\nexus\Form\DiscountBulk' ) ) );
		$form->addHeader( 'package_renewals' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_renews', !empty( $renewOptions ), FALSE, array( 'togglesOn' => array( 'p_renew_options', 'p_grace_period', 'p_renew', ( $this->id ? "form_{$this->id}" : 'form_new' ) . '_header_package_client_area_renewals' ) ), NULL, NULL, NULL, 'p_renews' ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'p_renew_options', $renewOptions, FALSE, array(
			'stackFieldType'	=> 'IPS\nexus\Form\RenewalTerm',
			'allCurrencies'		=> TRUE,
			'addToBase'			=> TRUE
		), NULL, NULL, NULL, 'p_renew_options' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_grace_period', $this->grace_period, FALSE, array( 'max' => \IPS\Settings::i()->cm_invoice_expireafter ?: NULL ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days'), 'p_grace_period' ) );
		
		$form->addTab('package_stock_adjustments');
		$fields = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_package_fields', array( array( '( cf_type=? OR cf_type=? )', 'Select', 'Radio' ), \IPS\Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ), 'IPS\nexus\Package\CustomField' );
		if ( count( $fields ) )
		{
			\IPS\Member::loggedIn()->language()->words['p_stock_price_dynamic_desc'] = '';
		}
		$fieldOptions = array();
		foreach ( $fields as $field )
		{
			$fieldOptions[ $field->id ] = $field->_title;
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'p_stock_price_type', $this->stock == -2 ? 'dynamic' : 'static', TRUE, array(
			'options'	=> array(
				'static'	=> 'p_stock_price_static',
				'dynamic'	=> 'p_stock_price_dynamic',
			),
			'toggles'	=> array(
				'static'	=> array( 'p_stock' ),
				'dynamic'	=> array( 'p_dynamic_stock' )
			),
			'disabled'		=> count( $fields ) ? array() : array( 'dynamic' )
		) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_stock', $this->id ? ( $this->stock == -2 ? -1 : $this->stock ) : -1, TRUE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'p_stock' ) );
		$package = $this;
		$form->add( new \IPS\Helpers\Form\Custom( 'custom_fields', $this->optionIdKeys(), FALSE, array(
			'rowHtml'	=> function( $input ) use ( $fields, $package )
			{
				return \IPS\Theme::i()->getTemplate('store')->productOptions( $input, $fields, $package );
			}
		), NULL, NULL, NULL, 'p_dynamic_stock' ) );
		
		$form->addTab('package_store');
		$form->addHeader('package_store_permissions');
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_store', $this->store, FALSE, array( 'togglesOn' => array( 'p_member_groups', 'p_featured', 'p_desc_editor', 'p_images', 'p_reviewable', "{$form->id}_header_package_store_display", "{$form->id}_header_package_reviews" ) ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'p_member_groups', $this->member_groups === '*' ? '*' : explode( ',', $this->member_groups ), FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'all' ), NULL, NULL, NULL, 'p_member_groups' ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_methods', ( !$this->methods or $this->methods === '*' ) ? 0 : explode( ',', $this->methods ), FALSE, array( 'class' => 'IPS\nexus\Gateway', 'multiple' => TRUE, 'zeroVal' => 'any' ) ) );
		foreach ( $typeFields['store_permissions'] as $field )
		{
			$form->add( $field );
		}
		$form->addHeader('package_store_display');
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_featured', $this->featured, FALSE, array(), NULL, NULL, NULL, 'p_featured' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'p_desc', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_package_{$this->id}_desc" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-pkg-{$this->id}" : "nexus-new-pkg" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pkg' ) : NULL, 'minimize' => 'p_desc_placeholder'
			)
		), NULL, NULL, NULL, 'p_desc_editor' ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'p_images', iterator_to_array( new \IPS\File\Iterator( \IPS\Db::i()->select( 'image_location', 'nexus_package_images', array( 'image_product=?', $this->id ), 'image_primary desc' ), 'nexus_Products' ) ), FALSE, array( 'storageExtension' => 'nexus_Products', 'multiple' => TRUE, 'image' => TRUE, 'template' => "nexus.store.images" ), NULL, NULL, NULL, 'p_images' ) );
		$form->addHeader('package_reviews');
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_reviewable', $this->reviewable, FALSE, array( 'togglesOn' => array( 'p_review_moderate' ) ), NULL, NULL, NULL, 'p_reviewable' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_review_moderate', $this->review_moderate ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'p_review_moderate' ) );
		
		$form->addHeader( 'package_benefits' );
		unset( $groups[ \IPS\Settings::i()->guest_group ] );
		$form->add( new \IPS\Helpers\Form\Select( 'p_primary_group', $this->primary_group ?: '*', FALSE, array( 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_primary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_return_primary', $this->return_primary, FALSE, array(), NULL, NULL, NULL, 'p_return_primary' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'p_secondary_group', $this->secondary_group ? explode( ',', $this->secondary_group ) : '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_secondary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_return_secondary', $this->return_secondary, FALSE, array(), NULL, NULL, NULL, 'p_return_secondary' ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_support_severity', $this->support_severity ?: 0, FALSE, array( 'class' => 'IPS\nexus\Support\Severity', 'zeroVal' => 'none' ), NULL, NULL, NULL, 'p_support_severity' ) );
		foreach ( $typeFields['package_benefits'] as $field )
		{
			$form->add( $field );
		}
		$form->addHeader( 'package_notify' );
		$form->add( new \IPS\Helpers\Form\Stack( 'p_notify', $this->notify ? explode( ',', $this->notify ) : array(), FALSE, array( 'stackFieldType' => 'Email' ) ) );

		$form->addTab('package_client_area');
		$form->addHeader('package_client_area_display');
		$form->add( new \IPS\Helpers\Form\Translatable( 'p_page', NULL, FALSE, array(
			'app' => 'nexus',
			'key' => $this->id ? "nexus_package_{$this->id}_page" : NULL,
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-pkg-{$this->id}-pg" : "nexus-new-pkg-pg" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pkg-pg' ) : NULL, 'minimize' => 'p_page_placeholder'
			)
		), NULL, NULL, NULL, 'p_desc_editor' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_support', $this->support, FALSE, array( 'togglesOn' => array( 'p_support_department' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_support_department', $this->support ? $this->support_department : 0, FALSE, array( 'class' => 'IPS\nexus\Support\Department', 'zeroVal' => 'none' ), NULL, NULL, NULL, 'p_support_department' ) );
		$form->addHeader('package_client_area_renewals');
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_renew', $this->renewal_days != 0, FALSE, array( 'togglesOn' => array( 'p_renewal_days', 'p_renewal_days_advance' ) ), NULL, NULL, NULL, 'p_renew' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_renewal_days', $this->id ? $this->renewal_days : -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'any_time' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days_before_expiry'), 'p_renewal_days' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_renewal_days_advance', $this->id ? $this->renewal_days_advance : -1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days'), 'p_renewal_days_advance' ) );
		$form->addHeader('package_upgrade_downgrade');
		$form->addMessage('package_upgrade_downgrade_desc');
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_allow_upgrading', $this->allow_upgrading, FALSE, array( 'togglesOn' => array( 'p_upgrade_charge' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'p_upgrade_charge', $this->upgrade_charge, FALSE, array( 'options' => array(
			0	=> 'p_upgrade_charge_none',
			1	=> 'p_upgrade_charge_full',
			2	=> 'p_upgrade_charge_prorate'
		) ), NULL, NULL, NULL, 'p_upgrade_charge' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_allow_downgrading', $this->allow_downgrading, FALSE, array( 'togglesOn' => array( 'p_downgrade_refund' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'p_downgrade_refund', $this->downgrade_refund, FALSE, array( 'options' => array(
			0	=> 'p_downgrade_refund_none',
			1	=> 'p_downgrade_refund_full',
			2	=> 'p_downgrade_refund_prorate'
		)), NULL, NULL, NULL, 'p_downgrade_refund' ) );
	}
	
	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function saveForm( $values )
	{		
		if ( !$this->id )
		{	
			$this->type = ( isset( $values['p_type'] ) ) ? $values['p_type'] : '';
			$this->save();
			unset( static::$multitons[ $this->id ] );
			
			\IPS\File::claimAttachments( 'nexus-new-pkg', $this->id, NULL, 'pkg', TRUE );
			\IPS\File::claimAttachments( 'nexus-new-pkg-assoc', $this->id, NULL, 'pkg-assoc', TRUE );
			\IPS\File::claimAttachments( 'nexus-new-pkg-pg', $this->id, NULL, 'pkg-pg', TRUE );
			
			$class = 'IPS\nexus\Package\\' . ucfirst( $values['p_type'] );
			
			if ( isset( $class::$packageDatabaseTable ) )
			{
				\IPS\Db::i()->insert( $class::$packageDatabaseTable, array( 'p_id' => $this->id ) );
			}
			
			$obj = $class::load( $this->id );
			return $obj->saveForm( $obj->formatFormValues( $values ) );			
		}
		
		return parent::saveForm( $values );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( !$this->id )
		{
			return $values;
		}
		
		/* Translatables */
		foreach ( array( 'name' => '', 'desc' => '_desc', 'assoc_error' => '_assoc', 'page' => '_page' ) as $key => $suffix )
		{
			if ( isset( $values[ 'p_' . $key ] ) )
			{
				\IPS\Lang::saveCustom( 'nexus', "nexus_package_{$this->id}{$suffix}", $values[ 'p_' . $key ] );
			}
			unset( $values[ 'p_' . $key ] );
		}
		
		/* Custom Fields */
		if( isset( $values['p_custom_fields'] ) )
		{
			$old = array_keys( \IPS\nexus\Package\CustomField::roots( NULL, NULL, array( array( \IPS\Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ) ) );
			$new = ( isset( $values['p_custom_fields'] ) and is_array( $values['p_custom_fields'] ) ) ? array_keys( $values['p_custom_fields'] ) : array();
			$added = array_diff( $new, $old );
			$removed = array_diff( $old, $new );
			if ( count( $added ) )
			{
				foreach ( $added as $id )
				{
					$field = \IPS\nexus\Package\CustomField::load( $id );
					$packages = explode( ',', $field->packages );
					$packages[] = $this->id;
					$field->packages = implode( ',', $packages );
					$field->save();
				}
			}
			if ( count( $removed ) )
			{
				foreach ( $removed as $id )
				{
					$field = \IPS\nexus\Package\CustomField::load( $id );
					$packages = explode( ',', $field->packages );
					unset( $packages[ array_search( $this->id, $packages ) ] );
					$field->packages = implode( ',', $packages );
					$field->save();
				}
			}
			unset( $values['p_custom_fields'] );
		}
		
		/* Normalise */
		if( isset( $values['p_group'] ) )
		{
			$values['p_group'] = $values['p_group']->id;
		}

		if( isset( $values['p_base_price'] ) )
		{
			$values['p_base_price'] = json_encode( $values['p_base_price'] );
		}

		if( isset( $values['p_member_groups'] ) )
		{
			$values['p_member_groups'] = $values['p_member_groups'] === '*' ? '*' : implode( ',', $values['p_member_groups'] );
		}

		if( isset( $values['p_primary_group'] ) )
		{
			$values['p_primary_group'] = $values['p_primary_group'] == '*' ? 0 : $values['p_primary_group'];
		}

		if( isset( $values['p_secondary_group'] ) )
		{
			$values['p_secondary_group'] = $values['p_secondary_group'] == '*' ? '' : implode( ',', $values['p_secondary_group'] );
		}

		if( isset( $values['p_support_department'] ) )
		{
			$values['p_support_department'] = ( $values['p_support'] and $values['p_support_department'] ) ? $values['p_support_department']->id : 0;
		}

		if( isset( $values['p_support_severity'] ) )
		{
			$values['p_support_severity'] = $values['p_support_severity'] ? $values['p_support_severity']->id : 0;
		}

		if( isset( $values['p_notify'] ) )
		{
			$values['p_notify'] = implode( ',', $values['p_notify'] );
		}

		if( isset( $values['p_methods'] ) )
		{
			$values['p_methods'] = ( isset( $values['p_methods'] ) and is_array( $values['p_methods'] ) ) ? implode( ',', array_keys( $values['p_methods'] ) ) : '*';
		}

		if( isset( $values['p_tax'] ) )
		{
			$values['p_tax'] = $values['p_tax'] ? $values['p_tax']->id : 0;
		}

		if( isset( $values['p_renew'] ) AND isset( $values['p_renewal_days'] ) )
		{
			$values['p_renewal_days'] = $values['p_renew'] ? $values['p_renewal_days'] : 0;
		}
		
		/* Renewal options */
		if( isset( $values['p_renews'] ) )
		{
			if ( $values['p_renews'] )
			{
				$renewOptions = array();
				foreach ( $values['p_renew_options'] as $option )
				{
					$term = $option->getTerm();
					$renewOptions[] = array(
						'cost'	=> $option->cost,
						'term'	=> $term['term'],
						'unit'	=> $term['unit'],
						'add'	=> $option->addToBase
					);
				}
				$values['p_renew_options'] = json_encode( $renewOptions );
			}
			else
			{
				$values['p_renew_options'] = '';
			}
		}
		
		/* Associate */
		if ( isset( $values['p_associate'] ) )
		{
			switch ( $values['p_associate'] )
			{
				case 0:
					$values['p_associable'] = '';
					$values['p_force_assoc'] = 0;
					break;
				
				case 1:
					$values['p_associable'] = implode( ',', array_map( function( $node ) { return $node->id; }, $values['p_associable'] ) );
					$values['p_force_assoc'] = 0;
					break;
				
				case 2:
					$values['p_associable'] = implode( ',', array_map( function( $node ) { return $node->id; }, $values['p_associable'] ) );
					$values['p_force_assoc'] = 1;
					break;
			}
		}

		/* Product Options */
		\IPS\Db::i()->delete( 'nexus_product_options', array( 'opt_package=?', $this->id ) );
		if ( isset( $values['p_stock_price_type'] ) )
		{
			if ( $values['p_stock_price_type'] === 'dynamic' )
			{
				$values['p_stock'] = -2;
				foreach ( $values['custom_fields'] as $k => $data )
				{				
					\IPS\Db::i()->insert( 'nexus_product_options', array(
						'opt_package'		=> $this->id,
						'opt_values'		=> rawurldecode( $k ),
						'opt_stock'			=> isset( $data['unlimitedStock'] ) ? -1 : intval( $data['stock'] ),
						'opt_base_price'	=> json_encode( $data['bpa'] ),
						'opt_renew_price'	=> isset( $data['rpa'] ) ? json_encode( $data['rpa'] ) : ''
					) );
				}
			}
			unset( $values['p_stock_price_type'] );
			unset( $values['custom_fields'] );
		}
		
		/* Discounts */
		if( isset( $values['p_usergroup_discounts'] ) OR isset( $values['p_loyalty_discounts'] ) OR isset( $values['p_bulk_discounts'] ) )
		{
			$discounts = array();
		}

		if( isset( $values['p_usergroup_discounts'] ) )
		{
			foreach ( $values['p_usergroup_discounts'] as $k => $data )
			{
				if ( isset( $data['group'] ) and $data['group'] and isset( $data['price'] ) )
				{
					$discounts['usergroup'][] = array(
						'group'		=> $data['group'],
						'price'		=> $data['price'],
						'secondary'	=> (int) isset( $data['secondary'] ),
					);
				}
			}
			unset( $values['p_usergroup_discounts'] );
		}

		if( isset( $values['p_loyalty_discounts'] ) )
		{
			foreach ( $values['p_loyalty_discounts'] as $k => $data )
			{
				if ( $data['owns'] )
				{
					$packageId = $data['package'] ? mb_substr( $data['package'], 0, mb_strpos( $data['package'], '.' ) ) : 0;
						
					$discounts['loyalty'][] = array(
						'owns'		=> $data['owns'],
						'package'	=> ( $data['this'] or ( $this->id and $this->id == $packageId ) ) ? '0' : $packageId,
						'price'		=> $data['price'],
						'active'	=> (int) isset( $data['active'] ),
					);
				}
			}
			unset( $values['p_loyalty_discounts'] );
		}

		if( isset( $values['p_bulk_discounts'] ) )
		{
			foreach ( $values['p_bulk_discounts'] as $k => $data )
			{
				if ( $data['buying'] )
				{
					$packageId = $data['package'] ? mb_substr( $data['package'], 0, mb_strpos( $data['package'], '.' ) ) : 0;
						
					$discounts['bulk'][] = array(
						'buying'	=> $data['buying'],
						'package'	=> ( $data['this'] or ( $this->id and $this->id == $packageId ) ) ? '0' : $packageId,
						'price'		=> $data['price'],
					);
				}
			}
			unset( $values['p_bulk_discounts'] );
		}

		if( isset( $discounts ) )
		{
			$values['discounts'] = json_encode( $discounts );
		}
		
		/* Images */
		if( isset( $values['p_images'] ) )
		{
			\IPS\Db::i()->delete( 'nexus_package_images', array( 'image_product=?', $this->id ) );

			if( !empty( $values['p_images'] ) )
			{
				$primary = TRUE;
				foreach ( $values['p_images'] as $_key => $image )
				{
					if (  \IPS\Request::i()->p_images_primary_image )
					{
						$primary = ( \IPS\Request::i()->p_images_primary_image == $_key ) ? 1 : 0;
					}

					\IPS\Db::i()->insert( 'nexus_package_images', array(
						'image_product'		=> $this->id,
						'image_location'	=> (string) $image,
						'image_primary'		=> $primary,
					) );

					if ( $primary )
					{
						$this->image = $image;
					}

					$primary = FALSE;
				}
			}
			else
			{
				$this->image = NULL;
			}
		}
		
		/* Save */
		return $values;
	}
	
	/**
	 * ACP Fields
	 *
	 * @param	\IPS\nexus\Package	$package	The package
	 * @param	bool				$custom		If TRUE, is for a custom package
	 * @param	bool				$customEdit	If TRUE, is editing a custom package
	 * @return	array
	 */
	public static function acpFormFields( \IPS\nexus\Package $package, $custom=FALSE, $customEdit=FALSE )
	{
		return array();
	}
	
	/**
	 * Updateable fields
	 *
	 * @return	array
	 */
	public static function updateableFields()
	{
		return array(
			'renew_options',
			'primary_group',
			'secondary_group',
			'group_renewals'
		);
	}
	
	/**
	 * Update existing purchases
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	array				$changes	The old values
	 * @return	void
	 */
	public function updatePurchase( \IPS\nexus\Purchase $purchase, $changes )
	{
		if ( array_key_exists( 'renew_options', $changes ) )
		{
			$newRenewTerms = json_decode( $this->renew_options, TRUE );

			if( !is_array( $newRenewTerms ) )
			{
				$newRenewTerms = array();
			}
						
			$key = 'x';
			if ( $purchase->renewals )
			{
				foreach ( $changes['renew_options'] as $k => $data )
				{
					if ( $data['old'] != 'x' )
					{
						if ( $purchase->renewals->cost->amount->compare( new \IPS\Math\Number( number_format( $data['old']['cost'][ $purchase->renewals->cost->currency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $purchase->renewals->cost->currency ), '.', '' ) ) ) === 0 )
						{
							$term = $purchase->renewals->getTerm();
							if ( $data['old']['term'] == $term['term'] and $data['old']['unit'] == $term['unit'] )
							{
								$key = $k;
								break;
							}
						}
					}
				}
			}
			
			switch ( $changes['renew_options'][ $key ]['new'] )
			{
				case 'x':
					$purchase->renewals = NULL;
					$purchase->save();
					break;
				case 'y':
					$purchase->renewals = NULL;
					$purchase->active = TRUE;
					$purchase->save();
					break;
				case 'z':
					$purchase->renewals = NULL;
					$purchase->active = FALSE;
					$purchase->save();
					break;
				case '-':
					// do nothing
					break;
				default:
					if ( mb_substr( $changes['renew_options'][ $key ]['new'], 0, 1 ) === 'o' )
					{
						$key = mb_substr( $changes['renew_options'][ $key ]['new'], 1 );
						if ( isset( $newRenewTerms[ $key ] ) )
						{
							$tax = NULL;
							if ( $purchase->tax )
							{
								try
								{
									$tax = \IPS\nexus\Tax::load( $purchase->tax );
								}
								catch ( \OutOfRangeException $e ) { }
							}
							
							$currency = $purchase->renewal_currency ?: $purchase->member->defaultCurrency( );

							$purchase->renewals = new \IPS\nexus\Purchase\RenewalTerm(
								new \IPS\nexus\Money( $newRenewTerms[ $key ]['cost'][ $currency ]['amount'], $currency ), 
								new \DateInterval( 'P' . $newRenewTerms[ $key ]['term'] . mb_strtoupper( $newRenewTerms[ $key ]['unit'] ) ),
								$tax
							);
							$purchase->save();
						}
					}
					break;
			}
		}
		
		if ( array_key_exists( 'primary_group', $changes ) or array_key_exists( 'secondary_group', $changes ) )
		{
			$this->_removeUsergroups( $purchase, $changes );
			$this->_addUsergroups( $purchase );
		}
		
		if ( array_key_exists( 'group_renewals', $changes ) )
		{
			if ( $this->group_renewals )
			{
				try
				{
					$purchase->groupWithParent();
				}
				catch ( \LogicException $e ) { }
			}
			else
			{
				try
				{
					$purchase->ungroupFromParent();
				}
				catch ( \LogicException $e ) { }
			}
		}
	}
	
	/* !Store */
	
	/**
	 * Store Form
	 *
	 * @param	\IPS\Helpers\Form	$form			The form
	 * @param	string				$memberCurrency	The currency being used
	 * @return	void
	 */
	public function storeForm( \IPS\Helpers\Form $form, $memberCurrency )
	{
		if( $this->subscription )
		{
			$form->hiddenValues['quantity'] = 1;
		}
		else
		{
			$form->add( new \IPS\Helpers\Form\Number('quantity', 1, TRUE, array( 'min' => 1 ) ) );
		}
	}
	
	/**
	 * Add To Cart
	 *
	 * @param	\IPS\nexus\extensions\nexus\Item\Package	$item			The item
	 * @param	array										$values			Values from form
	 * @param	string										$memberCurrency	The currency being used
	 * @return	array	Additional items to add
	 */
	public function addToCart( \IPS\nexus\extensions\nexus\Item\Package $item, array $values, $memberCurrency )
	{
		
	}
	
	/**
	 * Get item
	 *
	 * @return	\IPS\nexus\Package\Item
	 */
	public function item()
	{
		$data = array();
		foreach ( $this->_data as $k => $v )
		{
			$data[ 'p_' . $k ] = $v; 
		}
		
		return \IPS\nexus\Package\Item::constructFromData( $data );
	}
	
	/**
	 * Get option values for stock/price adjustments
	 *
	 * @param	array	$details	Custom field values
	 * @return	array
	 */
	public function optionValues( $details )
	{
		$optionValues = array();
		if ( $this->stock == -2 )
		{
			foreach( $this->optionIdKeys() as $k )
			{
				$optionValues[ $k ] = $details[ $k ];
			}
		}
		return $optionValues;
	}
	
	/**
	 * Get stock and price for option values
	 *
	 * @param	array	$optionValues		Option values
	 * @param	bool	$includeDiscounts	Get discounted price?
	 * @return	array( 'price' => \IPS\nexus\Money, 'stock' => 5, 'renewalAdjustment' => 0 )	'stock' will be -1 for unlimited, 0 for none in stock
	 */
	public function optionValuesStockAndPrice( $optionValues, $includeDiscounts=TRUE )
	{
		/* Dynamic */
		if ( $this->stock == -2 )
		{
			$chosenOption = \IPS\Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $this->id, json_encode( $optionValues ) ) )->first();
			
			/* Price */
			$basePriceAdjustments = json_decode( $chosenOption['opt_base_price'], TRUE );
			$defaultPrice = $this->price( NULL, $includeDiscounts, $includeDiscounts, $includeDiscounts );
			if ( isset( $basePriceAdjustments[ $defaultPrice->currency ] ) )
			{
				$price = new \IPS\nexus\Money( $defaultPrice->amount->add( new \IPS\Math\Number( number_format( $basePriceAdjustments[ $defaultPrice->currency ], \IPS\nexus\Money::numberOfDecimalsForCurrency( $defaultPrice->currency ), '.', '' ) ) ), $defaultPrice->currency );
			}
			else
			{
				$price = $defaultPrice;
			}
			
			$renewalAdjustment = 0;
			if ( $chosenOption['opt_renew_price'] )
			{
				$renewPriceAdjustments = json_decode( $chosenOption['opt_renew_price'], TRUE );
				if ( isset( $renewPriceAdjustments[ $price->currency ] ) )
				{
					$renewalAdjustment = $renewPriceAdjustments[ $price->currency ];
				}
			}
			
			/* Return */
			return array( 'price' => $price, 'stock' => $chosenOption['opt_stock'], 'renewalAdjustment' => $renewalAdjustment );
		}
		/* Static */
		else
		{
			return array( 'price' => $this->price(), 'stock' => $this->stock, 'renewalAdjustment' => 0 );
		}
	}
	
	/**
	 * Create Item ibject
	 *
	 * @param	\IPS\nexus\Package	$package	The package
	 * @param	\IPS\nexus\Money	$price		Price
	 * @return	\IPS\nexus\extensions\nexus\Item\Package
	 */
	public function createItemForCart( \IPS\nexus\Money $price )
	{
		$item = new \IPS\nexus\extensions\nexus\Item\Package( \IPS\Member::loggedIn()->language()->get( 'nexus_package_' . $this->id ), $price );
		$item->id = $this->id;
		$item->tax = $this->tax ? \IPS\nexus\Tax::load( $this->tax ) : NULL;
		if ( $this instanceof \IPS\nexus\Package\Product and $this->physical )
		{
			$item->physical = TRUE;
			$item->weight = new \IPS\nexus\Shipping\Weight( $this->weight );
			$item->length = new \IPS\nexus\Shipping\Length( $this->length );
			$item->width = new \IPS\nexus\Shipping\Length( $this->width );
			$item->height = new \IPS\nexus\Shipping\Length( $this->height );
			if ( $this->shipping !== '*' )
			{
				$item->shippingMethodIds = explode( ',', $this->shipping );
			}
		}
		if ( $this->methods and $this->methods != '*' )
		{
			$item->paymentMethodIds = explode( ',', $this->methods );
		}
		if ( $this->group_renewals )
		{
			$item->groupWithParent = TRUE;
		}

		return $item;
	}
	
	/**
	 * Add items into cart
	 *
	 * @param	array									$details			Custom field values
	 * @param	int										$quantity			Quantity to add
	 * @param	\IPS\nexus\Purchase\RenewalTerm|NULL	$renewalTerm		Chosen renewal term, if applicable
	 * @param	\IPS\nexus\Purchase|int					$parent				Parent purchase, if applicable
	 * @param	array|NULL								$values				If adding from the main purchase field, any additional values which may be used for extra items
	 * @return	int
	 * @note	Does not verify stock. Stock check first
	 */
	public function addItemsToCartData( $details=array(), $quantity=1, \IPS\nexus\Purchase\RenewalTerm $renewalTerm = NULL, $parent=NULL, $values=NULL )
	{		
		$optionValues = $this->optionValues( $details );
		$return = array();
					
		/* Work out how we're splitting these */
		$items = array();
		$previousPrice = NULL;
		$inThisBatch = 0;
		for ( $i=0; $i<$quantity;$i++ )
		{
			$price = $this->price( NULL, TRUE, TRUE, TRUE, $i );			
			$memberCurrency = $price->currency;
			$price = $price->amount;
							
			if ( $previousPrice !== NULL and $previousPrice != $price )
			{
				$items[] = $inThisBatch;
				$inThisBatch = 0;
			}
						
			$inThisBatch++;
			$previousPrice = $price;
		}
						
		if ( $inThisBatch )
		{
			$items[] = $inThisBatch;
		}
		
		/* And do it */
		foreach ( $items as $quantity )
		{
			/* Get base price */
			$price = $this->price()->amount;
			
			/* Renewal term */
			if ( $renewalTerm and $renewalTerm->addToBase )
			{
				$price = $price->add( $renewalTerm->cost->amount );
			}
						
			/* Adjustments based on custom fields */
			if ( $this->stock == -2 )
			{					
				try
				{
					$chosenOption = \IPS\Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $this->id, json_encode( $optionValues ) ) )->first();
					$basePriceAdjustments = json_decode( $chosenOption['opt_base_price'], TRUE );
					if ( isset( $basePriceAdjustments[ $memberCurrency ] ) )
					{
						$price = $price->add( new \IPS\Math\Number( number_format( $basePriceAdjustments[ $memberCurrency ], \IPS\nexus\Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) );
					}
				}
				catch ( \UnderflowException $e ) {}
			}
			
			/* Create the item */			
			$item = $this->createItemForCart( new \IPS\nexus\Money( $price, $memberCurrency ) );
			$item->renewalTerm = $renewalTerm;
			$item->quantity = $quantity;
			$item->details = $details;

			/* Associations */
			if ( $parent !== NULL )
			{
				$item->parent = $parent;
			}
			
			/* Do any package-sepcific modifications */
			$extraItems = ( $values === NULL ) ? array() : $this->addToCart( $item, $values, $memberCurrency );
			
			/* Hang on, is that the same as anything else in the cart? */
			$added = FALSE;
			foreach ( $_SESSION['cart'] as $_id => $_item )
			{
				$cloned = clone $_item;
				$cloned->quantity = $quantity;
				
				if ( $cloned == $item )
				{
					$_item->quantity += $quantity;
					$added = TRUE;
					$cartId = $_id;
					break;
				}
			}
									
			/* Nope, go ahead and add it */
			if ( !$added )
			{
				$_SESSION['cart'][] = $item;
				$keys = array_keys( $_SESSION['cart'] );
				$cartId = array_pop( $keys );
			}
			
			/* Associate extras */
			if ( count( $extraItems ) )
			{
				foreach ( $extraItems as $item )
				{
					$item->parent = $cartId;
					$_SESSION['cart'][] = $item;
				}
			}
		}
		
		/* Turn off guest page caching */
		if ( \IPS\CACHE_PAGE_TIMEOUT and !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Request::i()->setCookie( 'noCache', 1 );
		}
		
		/* Return */
		return $cartId;
	}
	
	/* !Client Area */
	
	/**
	 * Show Purchase Record?
	 *
	 * @return	bool
	 */
	public function showPurchaseRecord()
	{
		return TRUE;
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public function clientAreaPage( \IPS\nexus\Purchase $purchase )
	{
		/* Custom Fields */
		$editable = FALSE;
		$customFieldsForm = new \IPS\Helpers\Form;
		$customFieldsForm->class = 'ipsForm_vertical';
		$customFields = \IPS\nexus\Package\CustomField::roots( NULL, NULL, array( array( \IPS\Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ) ) );
		foreach ( $customFields as $field )
		{
			$value = isset( $purchase->custom_fields[ $field->id ] ) ? $purchase->custom_fields[ $field->id ] : NULL;
			if ( $field->editable )
			{
				$editable = TRUE;
				$customFieldsForm->add( $field->buildHelper( $value ) );
			}
			else
			{
				$customFieldsForm->addDummy( $field->_title, htmlspecialchars( $value, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) );
			}
		}
		if ( !$editable )
		{
			$customFieldsForm->actionButtons = array();
		}
		
		/* Support Link */
		$supportUrl = NULL;
		if ( $purchase->active and $this->support )
		{
			$supportUrl = \IPS\Http\Url::internal( 'app=nexus&module=support&controller=home&do=create&purchase=' . $purchase->id, 'front', 'support_create' );
			if ( $this->support_department )
			{
				$supportUrl = $supportUrl->setQueryString( 'department', $this->support_department );
			}
		}
		
		/* Stuff only the owner or "billing" alternate contact can do */
		$reactivateUrl = NULL;
		$upgradeDowngradeUrl = NULL;
		$upgradeDowngradeLang = 'change_package';
		if ( $purchase->member->member_id === \IPS\Member::loggedIn()->member_id or array_key_exists( \IPS\Member::loggedIn()->member_id, iterator_to_array( $purchase->member->alternativeContacts( array( \IPS\Db::i()->findInSet( 'purchases', array( $purchase->id ) ) . ' AND billing=1' ) ) ) ) )
		{
			/* Reactivate */
			if ( !$purchase->renewals and count( json_decode( $this->renew_options, TRUE ) ) and $purchase->can_reactivate and !$purchase->billing_agreement )
			{
				$reactivateUrl = $purchase->url()->setQueryString( array( 'do' => 'extra', 'act' => 'reactivate' ) )->csrf();
			}
			
			/* Upgrade/Downgrade */
			$includesUpgrades = FALSE;
			$includesDowngrades = FALSE;
			if( count( $this->upgradeDowngradeOptions( $purchase, FALSE, $includesUpgrades, $includesDowngrades ) ) )
			{				
				$upgradeDowngradeUrl = $purchase->url()->setQueryString( array( 'do' => 'extra', 'act' => 'change' ) );
				if ( $includesUpgrades and !$includesDowngrades )
				{
					$upgradeDowngradeLang = 'change_package_upgrade';
				}
				elseif ( !$includesUpgrades and $includesDowngrades )
				{
					$upgradeDowngradeLang = 'change_package_downgrade';
				}
			}
		}
		
		/* Associated Downloads Files */
		$associatedFiles = array();
		if ( \IPS\Application::appIsEnabled( 'downloads' ) and \IPS\Settings::i()->idm_nexus_on )
		{
			$associatedFiles = \IPS\downloads\File::getItemsWithPermission( array( array( \IPS\Db::i()->findInSet( 'file_nexus', array( $this->id ) ) ) ) );
		}
		
		/* Associated support requests */
		$last5AssociatedSupportRequests = \IPS\nexus\Support\Request::getItemsWithPermission( array( array( 'r_purchase=?', $purchase->id ) ), 'r_last_reply', 5 );
				
		/* Display */
		return array(
			'packageInfo'	=> \IPS\Member::loggedIn()->language()->checkKeyExists("nexus_package_{$this->id}_page") ? \IPS\Member::loggedIn()->language()->addToStack("nexus_package_{$this->id}_page") : '',
			'purchaseInfo'	=> \IPS\Theme::i()->getTemplate( 'purchases' )->package( $this, $customFieldsForm, $supportUrl, $reactivateUrl, $upgradeDowngradeUrl, $upgradeDowngradeLang, $associatedFiles, $last5AssociatedSupportRequests ),
		);
	}
	
	/**
	 * Client Area Action
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	string
	 */
	public function clientAreaAction( \IPS\nexus\Purchase $purchase )
	{
		switch ( \IPS\Request::i()->act )
		{
			case 'change':
				$form = $this->upgradeDowngradeForm( $purchase );
				if ( $values = $form->values() )
				{
					$newPackage				= \IPS\nexus\Package::load( $values['change_package_to'] );
					$chosenRenewalOption	= isset( $values["renew_option_{$newPackage->id}" ] ) ? $values[ "renew_option_{$newPackage->id}" ] : NULL;
					
					/* This package may only have one renewal option, in which case it is not displayed in the form - we should auto select it here, if renewal options are available */
					if ( $chosenRenewalOption === NULL )
					{
						$renewOptions = json_decode( $newPackage->renew_options, TRUE );
						if ( count( $renewOptions ) === 1 )
						{
							$chosenRenewalOption = key( $renewOptions );
						}
					}
					
					$invoice = $this->upgradeDowngrade( $purchase, $newPackage, $chosenRenewalOption );
					if ( $invoice )
					{
						\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
					}
					$purchase->member->log( 'purchase', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $purchase->name, 'name' => $newPackage->_title, 'system' => FALSE ) );
					\IPS\Output::i()->redirect( $purchase->url() );
				}
				return (string) $form;
			break;
			
			case 'reactivate':
				$currency = $purchase->original_invoice->currency;
				$renewalOptions = json_decode( $this->renew_options, TRUE );
				$noOptions = count( $renewalOptions ) === 1;
								
				if ( $noOptions )
				{
					\IPS\Session::i()->csrfCheck();
					$term = array_pop( $renewalOptions );
					$purchase->renewals = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $term['cost'][$currency]['amount'], $currency ), new \DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ) );
					$purchase->cancelled = FALSE;
					$purchase->save();
					
					foreach ( $purchase->children() as $child )
					{
						if ( $child->grouped_renewals )
						{
							$child->groupWithParent();
						}
					}
					
					$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'change_renewals', 'to' => array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) ) );
					
					if ( !$purchase->active and $purchase->canRenewUntil() !== FALSE )
					{
						\IPS\Output::i()->redirect( $purchase->url()->setQueryString( 'do', 'renew' )->csrf() );
					}
					else
					{
						\IPS\Output::i()->redirect( $purchase->url() );
					}
				}
			
				$form = new \IPS\Helpers\Form( 'reactivate', 'reactivate' );
				$form->class = 'ipsForm_vertical';
				$options = array();
				foreach ( $renewalOptions as $k => $term )
				{
					$options[ $k ] = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $term['cost'][$currency]['amount'], $currency ), new \DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ) );
				}
				$form->add( new \IPS\Helpers\Form\Radio( 'ps_renewals', NULL, TRUE, array( 'options' => $options, 'parse' => 'none' ) ) );
				if ( $values = $form->values() )
				{
					$term = $renewalOptions[ $values['ps_renewals'] ];
					$purchase->renewals = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $term['cost'][$currency]['amount'], $currency ), new \DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ) );
					$purchase->cancelled = FALSE;
					$purchase->save();
					
					foreach ( $purchase->children() as $child )
					{
						if ( $child->grouped_renewals )
						{
							$child->groupWithParent();
						}
					}
					
					$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'change_renewals', 'to' => array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) ) );
					
					if ( !$purchase->active and $purchase->canRenewUntil() !== FALSE )
					{
						\IPS\Output::i()->redirect( $purchase->url()->setQueryString( 'do', 'renew' )->csrf() );
					}
					else
					{
						\IPS\Output::i()->redirect( $purchase->url() );
					}
				}
				return (string) $form;
			break;
		}
	}
	
	/* !ACP */
	
	/**
	 * ACP Add to invoice
	 *
	 * @param	\IPS\nexus\extensions\nexus\Item\Package	$item			The item
	 * @param	array										$values			Values from form
	 * @param	string										$k				The key to add to the field names
	 * @param	\IPS\nexus\Invoice							$invoice		The invoice
	 * @return	void
	 */
	public function acpAddToInvoice( \IPS\nexus\extensions\nexus\Item\Package $item, array $values, $k, \IPS\nexus\Invoice $invoice )
	{
	
	}	
	
	/**
	 * Get ACP Page Buttons
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\Http\Url		$url		The page URL
	 * @return	array
	 */
	public function acpButtons( \IPS\nexus\Purchase $purchase, $url )
	{
		$return = array();
		
		if ( count( $this->upgradeDowngradeOptions( $purchase, TRUE ) ) )
		{
			$return['change'] = array(
				'icon'	=> 'archive',
				'title'	=> 'change_package',
				'link'	=> $url->setQueryString( array( 'do' => 'extra', 'act' => 'change' ) ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('change_package') )
			);
		}
		
		return $return;
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @return	string
	 */
	public function acpPage( \IPS\nexus\Purchase $purchase )
	{
		return '';
	}
	
	/**
	 * ACP Action
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	string|void
	 */
	public function acpAction( \IPS\nexus\Purchase $purchase )
	{
		switch ( \IPS\Request::i()->act )
		{
			case 'change':
				$form = $this->upgradeDowngradeForm( $purchase, TRUE );
				$form->class = 'ipsForm_horizontal';
				$form->add( new \IPS\Helpers\Form\YesNo( 'change_package_ship_charges', FALSE, TRUE ) );
				if ( $values = $form->values() )
				{
					$newPackage = \IPS\nexus\Package::load( $values['change_package_to'] );
					$invoice = $this->upgradeDowngrade( $purchase, $newPackage, isset( $values[ "renew_option_{$newPackage->id}" ] ) ? $values[ "renew_option_{$newPackage->id}" ] : NULL, $values['change_package_ship_charges'] );
					if ( $invoice )
					{
						\IPS\Output::i()->redirect( $invoice->acpUrl() );
					}
					$purchase->member->log( 'purchase', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $purchase->name, 'name' => $newPackage->_title, 'system' => FALSE ) );
					\IPS\Output::i()->redirect( $purchase->acpUrl() );
				}
				return (string) $form;
			break;
		}
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param	\IPS\nexus\Purchase				$purchase	The purchase
	 * @param	\IPS\Helpers\Form				$form	The form
	 * @param	\IPS\nexus\Purchase\RenewalTerm	$renewals	The renewal term
	 * @return	string
	 */
	public function acpEdit( \IPS\nexus\Purchase $purchase, \IPS\Helpers\Form $form, $renewals )
	{
		$form->addHeader('menu__nexus_store_fields');
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_package_fields', \IPS\Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ), 'IPS\nexus\Package\CustomField' ) as $field )
		{
			$form->add( $field->buildHelper( isset( $purchase->custom_fields[ $field->id ] ) ? $purchase->custom_fields[ $field->id ] : NULL ) );
		}
	}
	
	/** 
	 * ACP Edit Save
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	array				$values		Values from form
	 * @return	string
	 */
	public function acpEditSave( \IPS\nexus\Purchase $purchase, array $values )
	{
		$customFields = $purchase->custom_fields;
				
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_package_fields', \IPS\Db::i()->findInSet( 'cf_packages', array( $this->id ) ) ), 'IPS\nexus\Package\CustomField' ) as $field )
		{
			if( array_key_exists( $field->type, \IPS\nexus\Package\CustomField::$additionalFieldTypes ) )
			{
				$class = 'IPS\nexus\Form\\' . $field->type;
			}
			else
			{
				$class = 'IPS\Helpers\Form\\' . $field->type;
			}

			$customFields[ $field->id ] = $class::stringValue( $values[ 'nexus_pfield_' . $field->id ] );
		}
		
		$purchase->custom_fields = $customFields;
		$purchase->save();
	}
	
	/* !Actions */
	
	/**
	 * On Paid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPaid( \IPS\nexus\Invoice $invoice )
	{
		// Blank for hooking
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( \IPS\nexus\Invoice $invoice )
	{
		// Blank for hooking
		return array();
	}
	
	/**
	 * On Unpaid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @param	string				$status		Status
	 * @return	void
	 */
	public function onUnpaid( \IPS\nexus\Invoice $invoice, $status )
	{
		// Blank for hooking
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onInvoiceCancel( \IPS\nexus\Invoice $invoice )
	{
		// Blank for hooking
	}
	
	/**
	 * Check for member
	 * If a user initially checks out as a guest and then logs in during checkout, this method
	 * is ran to check the items they are purchasing can be bought.
	 * Is expected to throw a DomainException with an error message to display to the user if not valid
	 *
	 * @param	\IPS\Member	$member	The new member
	 * @return	void
	 * @throws	\DomainException
	 */
	public function memberCanPurchase( \IPS\Member $member )
	{
		// Handled by subclasses
	}
	
	/**
	 * Can Renew Until
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	bool				$admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return	\IPS\DateTime|bool	TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public function canRenewUntil( \IPS\nexus\Purchase $purchase, $admin )
	{
		if ( $admin )
		{
			return TRUE;
		}
		
		if ( !$this->renewal_days )
		{
			return FALSE;
		}
		elseif ( $this->renewal_days != -1 )
		{
			if ( $purchase->expire->diff( \IPS\DateTime::create() )->days > $this->renewal_days )
			{
				return FALSE;
			}
		}
		
		if ( $this->renewal_days_advance == -1 )
		{
			return TRUE;
		}
		else
		{
			return $purchase->expire->add( new \DateInterval( 'P' . $this->renewal_days_advance . 'D' ) );
		}
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPurchaseGenerated( \IPS\nexus\Purchase $purchase, \IPS\nexus\Invoice $invoice )
	{		
		$this->_changeStockLevel( -1, $this->_getOptionId( $purchase ) );
		$this->_addUsergroups( $purchase );
		
		if ( $this->notify )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'purchaseNotify', array( $purchase, $invoice ), \IPS\Email::TYPE_LIST );
			foreach ( explode( ',', $this->notify ) as $to )
			{
				$email->send( $to );
			}
		}
	}
	
	/**
	 * On Renew (Renewal invoice paid. Is not called if expiry data is manually changed)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	int					$cycles		Cycles
	 * @return	void
	 */
	public function onRenew( \IPS\nexus\Purchase $purchase, $cycles )
	{
		// Blank for hooking
	}
	
	/**
	 * On Expiration Date Change
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onExpirationDateChange( \IPS\nexus\Purchase $purchase )
	{
	
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onExpire( \IPS\nexus\Purchase $purchase )
	{
		$this->_removeUsergroups( $purchase );
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onCancel( \IPS\nexus\Purchase $purchase )
	{
		$this->_changeStockLevel( 1, $this->_getOptionId( $purchase ) );
		$this->_removeUsergroups( $purchase );
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onDelete( \IPS\nexus\Purchase $purchase )
	{
		$this->onCancel( $purchase );
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onReactivate( \IPS\nexus\Purchase $purchase )
	{
		$this->_changeStockLevel( -1, $this->_getOptionId( $purchase ) );
		$this->_addUsergroups( $purchase );
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase		The purchase
	 * @param	\IPS\Member			$newCustomer	New Customer
	 * @return	void
	 */
	public function onTransfer( \IPS\nexus\Purchase $purchase, \IPS\Member $newCustomer )
	{
		$this->_removeUsergroups( $purchase );
		$purchase->member = $newCustomer;
		$this->_addUsergroups( $purchase );
	}
	
	/**
	 * On Upgrade/Downgrade
	 *
	 * @param	\IPS\nexus\Purchase							$purchase				The purchase
	 * @param	\IPS\nexus\Package							$newPackage				The package to upgrade to
	 * @param	int|NULL|\IPS\nexus\Purchase\RenewalTerm	$chosenRenewalOption	The chosen renewal option
	 * @return	void
	 */
	public function onChange( \IPS\nexus\Purchase $purchase, \IPS\nexus\Package $newPackage, $chosenRenewalOption = NULL )
	{
	
	}
	
	/* !Upgrading/Downgrading */
	
	/**
	 * Upgrade/Downgrade Options
	 *
	 * @param	\IPS\nexus\Purchase	$purchase				The purchase
	 * @param	bool				$overrideRestrictions	If true, will be able to choose any packages in the same group
	 * @param	bool				$includesUpgrades		Will be set if there are upgrade options, useful for knowing what text to show
	 * @param	bool				$includesDowngrades		Will be set if there are downgrade options, useful for knowing what text to show
	 * @return	array
	 */
	protected function upgradeDowngradeOptions( \IPS\nexus\Purchase $purchase, $overrideRestrictions = FALSE, &$includesUpgrades=FALSE, &$includesDowngrades=FALSE )
	{
		if ( $this->custom or !$purchase->active or ( !$this->allow_upgrading and !$this->allow_upgrading and !$overrideRestrictions ) or $purchase->grouped_renewals or $purchase->billing_agreement )
		{
			return array();
		}
		else
		{
			$return = array();
			$currency = $purchase->original_invoice->currency;
			$prices = json_decode( $this->base_price, TRUE );
			$thisPrice = $prices[ $currency ]['amount'];
			foreach ( $this->parent()->children() as $package )
			{
				if ( $package->id === $this->id )
				{
					continue;
				}
				
				$prices = json_decode( $package->base_price, TRUE );
				if ( !isset( $prices[ $currency ] ) )
				{
					continue;
				}
				$price = $prices[ $currency ]['amount'];
				
				/* Does Renewal add to base price */
				if ( $package->renew_options )
				{
					$renewalOptions = json_decode( $package->renew_options, TRUE );
					if ( !empty( $renewalOptions ) )
					{
						$option = array_shift( $renewalOptions );						
						if ( $option['add'] )
						{
							$price += ( $option['cost'][ $currency ]['amount'] );
						}
					}
				}
				
				/* Upgrade */
				if ( $price >= $thisPrice and ( $this->allow_upgrading or $overrideRestrictions ) )
				{
					$return[] = $package;
					$includesUpgrades = TRUE;
				}
				
				/* Downgrade */
				elseif ( $price < $thisPrice and ( $this->allow_downgrading or $overrideRestrictions ) )
				{
					$return[] = $package;
					$includesDowngrades = TRUE;
				}
			}
			
			return $return;
		}
	}
	
	/**
	 * Upgrade/Downgrade Form
	 *
	 * @param	\IPS\nexus\Purchase	$purchase				The purchase
	 * @param	bool				$overrideRestrictions	If true, will be able to choose any packages in the same group
	 * @return	\IPS\Helpers\Form
	 */
	protected function upgradeDowngradeForm( \IPS\nexus\Purchase $purchase, $overrideRestrictions = FALSE )
	{
		$currency = $purchase->original_invoice->currency;
		$options = array();
		$desciptions = array();
		$toggles = array();
		$renewFields = array();
		foreach ( $this->upgradeDowngradeOptions( $purchase, $overrideRestrictions ) as $package )
		{
			$options[ $package->id ] = $package->_title;
								
			$renewOptions = json_decode( $package->renew_options, TRUE ) ?: array();
			$renewalOptions = array();
			foreach ( $renewOptions as $k => $option )
			{
				$renewalOptions[ $k ] = (string) new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $option['cost'][$currency]['amount'], $currency ), new \DateInterval( 'P' . $option['term'] . mb_strtoupper( $option['unit'] ) ) );
			}
			
			if ( count( $renewalOptions ) === 1 )
			{
				foreach ( $renewalOptions as $k => $v )
				{
					try
					{
						$costToUpgrade = $package->costToUpgrade( $purchase, $k );
					}
					catch ( \InvalidArgumentException $e )
					{
						unset( $options[ $package->id ] );
						continue 2;
					}
					if ( $costToUpgrade->amount->isZero() )
					{
						$desciptions[ $package->id ] = $v;
					}
					elseif( $costToUpgrade->amount->isPositive() )
					{
						$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'upgrade_cost_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $v ) ) );
					}
					else
					{
						$costToUpgrade->amount = $costToUpgrade->amount->multiply( new \IPS\Math\Number( '-1' ) );
						$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'downgrade_refund_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $v ) ) );
					}
				}
			}
			elseif ( count( $renewalOptions ) === 0 )
			{
				try
				{
					$costToUpgrade = $package->costToUpgrade( $purchase );
				}
				catch ( \InvalidArgumentException $e )
				{
					unset( $options[ $package->id ] );
					continue;
				}
				if( $costToUpgrade->amount->isGreaterThanZero() )
				{
					$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'upgrade_cost', FALSE, array( 'sprintf' => array( $costToUpgrade ) ) );
				}
				elseif( !$costToUpgrade->amount->isPositive() )
				{
					$costToUpgrade->amount = $costToUpgrade->amount->multiply( new \IPS\Math\Number( '-1' ) );
					$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'downgrade_refund', FALSE, array( 'sprintf' => array( $costToUpgrade ) ) );
				}
			}
			else
			{
				$renewalOptionsString = \IPS\Member::loggedIn()->language()->formatList( $renewalOptions, \IPS\Member::loggedIn()->language()->get('or_list_format') );
				$renewFieldsDescriptions = array();
				try
				{
					$costToUpgrade = $package->costToUpgrade( $purchase );
					if ( $costToUpgrade->amount->isZero() )
					{
						$desciptions[ $package->id ] = $renewalOptionsString;
					}
					elseif( $costToUpgrade->amount->isGreaterThanZero() )
					{
						$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'upgrade_cost_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $renewalOptionsString ) ) );
					}
					else
					{
						$costToUpgrade->amount = $costToUpgrade->amount->multiply( new \IPS\Math\Number( '-1' ) );
						$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'downgrade_refund_and_renew', FALSE, array( 'sprintf' => array( $costToUpgrade, $renewalOptionsString ) ) );
					}
				}
				catch ( \InvalidArgumentException $e )
				{
					if ( intval( $e->getMessage() ) >= 0 )
					{								
						$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'unknown_upgrade_charge_and_renew', FALSE, array( 'sprintf' => array( $renewalOptionsString ) ) );
					}
					else
					{
						$desciptions[ $package->id ] = \IPS\Member::loggedIn()->language()->addToStack( 'unknown_downgrade_refund_and_renew', FALSE, array( 'sprintf' => array( $renewalOptionsString ) ) );
					}
					
					foreach ( $renewalOptions as $k => $v )
					{
						$costToUpgrade = $package->costToUpgrade( $purchase, $k );
						if( $costToUpgrade->amount->isGreaterThanZero() )
						{
							$renewFieldsDescriptions[ $k ] = \IPS\Member::loggedIn()->language()->addToStack( 'upgrade_cost', FALSE, array( 'sprintf' => array( $costToUpgrade ) ) );
						}
						elseif( !$costToUpgrade->amount->isPositive() )
						{
							$costToUpgrade->amount = $costToUpgrade->amount->multiply( new \IPS\Math\Number( '-1' ) );
							$renewFieldsDescriptions[ $k ] = \IPS\Member::loggedIn()->language()->addToStack( 'downgrade_refund', FALSE, array( 'sprintf' => array( $costToUpgrade ) ) );
						}
					}
				}						
				
				$renewFields[] = new \IPS\Helpers\Form\Radio( 'renew_option_' . $package->id, NULL, NULL, array( 'options' => $renewalOptions, 'descriptions' => $renewFieldsDescriptions, 'parse' => 'none' ), NULL, NULL, NULL, 'renew_option_' . $package->id );
				\IPS\Member::loggedIn()->language()->words['renew_option_' . $package->id] = \IPS\Member::loggedIn()->language()->addToStack( 'renewal_term', FALSE );
				$toggles[ $package->id ] = array( 'renew_option_' . $package->id );
			}
		}
		
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Radio( 'change_package_to', NULL, TRUE, array( 'options' => $options, 'descriptions' => $desciptions, 'toggles' => $toggles, 'parse' => 'none' ) ) );
		foreach ( $renewFields as $field )
		{
			$form->add( $field );
		}
		
		return $form;
	}
	
	/** 
	 * Cost to upgrade to this package (may return negative value for refund)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase to be upgraded
	 * @param	int					$renewalOption	The key for the chosen renewal option
	 * @return	\IPS\nexus\Money|NULL
	 */
	protected function costToUpgrade( \IPS\nexus\Purchase $purchase, $renewalOption = NULL )
	{
		$package = \IPS\nexus\Package::load( $purchase->item_id );
		$currency = $purchase->original_invoice->currency;
		
		$priceOfExistingPackage = json_decode( $package->base_price, TRUE );
		$priceOfExistingPackage = $priceOfExistingPackage[ $currency ]['amount'];
		
		$priceOfThisPackage = json_decode( $this->base_price, TRUE );
		$priceOfThisPackage = $priceOfThisPackage[ $currency ]['amount'];
				
		if ( $priceOfThisPackage >= $priceOfExistingPackage )
		{
			$type = $package->upgrade_charge;
		}
		else
		{
			$type = $package->downgrade_refund;
		}
						
		switch ( $type )
		{
			case 0:
				return new \IPS\nexus\Money( 0, $currency );
			
			case 1:
				return new \IPS\nexus\Money( $priceOfThisPackage - $priceOfExistingPackage, $currency );
			
			case 2:
				
				if ( !$purchase->expire )
				{
					return new \IPS\nexus\Money( 0, $currency );
				}
				
				$renewalOptions = json_decode( $this->renew_options, TRUE );
				
				if ( $renewalOption === NULL or !isset( $renewalOptions[ $renewalOption ] ) )
				{
					throw new \InvalidArgumentException( $priceOfThisPackage - $priceOfExistingPackage );
				}
				
				$option = $renewalOptions[ $renewalOption ];
				$option = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $option['cost'][ $currency ]['amount'], $currency ), new \DateInterval( 'P' . $option['term'] . mb_strtoupper( $option['unit'] ) ) );
				$daysLeftUntilExpiry = new \IPS\Math\Number( (string) \IPS\DateTime::create()->diff( $purchase->expire )->days );
				$newCostPerDay = $option->costPerDay()->amount;
				$oldCostPerDay = $purchase->renewals->costPerDay()->amount;				
				return new \IPS\nexus\Money( $newCostPerDay->multiply( $daysLeftUntilExpiry )->subtract( $oldCostPerDay->multiply( $daysLeftUntilExpiry ) ), $currency );
		}
	}
	
	/**
	 * Actually Upgrade/Downgrade
	 *
	 * @param	\IPS\nexus\Purchase							$purchase				The purchase
	 * @param	\IPS\nexus\Package							$newPackage				The package to upgrade to
	 * @param	int|NULL|\IPS\nexus\Purchase\RenewalTerm	$chosenRenewalOption	The chosen renewal option
	 * @param	bool										$skipCharge				If TRUE, an upgrade charges and downgrade refunds will not be issued
	 * @return	\IPS\nexus\Invoice|void												An invoice if an upgrade charge has to be paid, or void if not
	 * @throws	\InvalidArgumentException											If $chosenRenewalOption is invalid
	 */
	public function upgradeDowngrade( \IPS\nexus\Purchase $purchase, \IPS\nexus\Package $newPackage, $chosenRenewalOption = NULL, $skipCharge = FALSE )
	{
		/* Charge / Refund */
		if ( !$skipCharge )
		{
			$costToUpgrade = $newPackage->costToUpgrade( $purchase, $chosenRenewalOption );
			
			/* Upgrade Charge */
			if ( $costToUpgrade->amount->isGreaterThanZero() )
			{
				$item = new \IPS\nexus\extensions\nexus\Item\UpgradeCharge( sprintf( $purchase->member->language()->get( 'upgrade_charge_item' ), $purchase->member->language()->get( "nexus_package_{$this->id}" ), $purchase->member->language()->get( "nexus_package_{$newPackage->id}" ) ), $costToUpgrade );
				$item->tax = $newPackage->tax ? \IPS\nexus\Tax::load( $newPackage->tax ) : NULL;
				$item->id = $purchase->id;
				$item->extra = array( 'newPackage' => $newPackage->id, 'oldPackage' => $this->id, 'renewalOption' => $chosenRenewalOption, 'previousRenewalTerms' => $purchase->renewals ? array( 'cost' => $purchase->renewals->cost->amount, 'currency' => $purchase->renewals->cost->currency, 'term' => $purchase->renewals->getTerm() ) : NULL );
				
				$invoice = new \IPS\nexus\Invoice;
				$invoice->member = $purchase->member;
				$invoice->currency = $costToUpgrade->currency;
				$invoice->addItem( $item );
				$invoice->return_uri = "app=nexus&module=clients&controller=purchases&do=view&id={$purchase->id}";
				$invoice->renewal_ids = array( $purchase->id );
				$invoice->save();
				return $invoice;
			}
			elseif ( !$costToUpgrade->amount->isPositive() )
			{
				$credits = $purchase->member->cm_credits;
				$credits[ $costToUpgrade->currency ]->amount = $credits[ $costToUpgrade->currency ]->amount->add( $costToUpgrade->amount->multiply( new \IPS\Math\Number( '-1' ) ) );
				$purchase->member->cm_credits = $credits;
				$purchase->member->save();
			}
		}
		
		/* Work out the new renewal term */
		if ( $chosenRenewalOption instanceof \IPS\nexus\Purchase\RenewalTerm )
		{
			$term = $chosenRenewalOption;
		}
		else
		{
			$term = NULL;
			$renewalOptions = json_decode( $newPackage->renew_options, TRUE );
			if ( $chosenRenewalOption === NULL )
			{
				if ( count( $renewalOptions ) === 1 )
				{
					$term = array_pop( $renewalOptions );
				}
				elseif ( count( $renewalOptions ) !== 0 )
				{
					throw new \InvalidArgumentException;
				}
			}
			else
			{
				if ( isset( $renewalOptions[ $chosenRenewalOption ] ) )
				{
					$term = $renewalOptions[ $chosenRenewalOption ];
				}
				else
				{
					throw new \InvalidArgumentException;
				}
			}
			if ( $term )
			{
				$currency = $purchase->original_invoice->currency;
				$term = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $term['cost'][$currency]['amount'], $currency ), new \DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ) );
			}
		}
		
		/* Do package-specific stuff */
		$this->onChange( $purchase, $newPackage, $chosenRenewalOption );
		
		/* Remove usergroups */
		$this->_removeUsergroups( $purchase );
		
		/* Ungroup any children */
		$groupedChildren = array();
		foreach ( $purchase->children() as $child )
		{
			if ( $child->grouped_renewals )
			{
				$child->ungroupFromParent();
				$groupedChildren[] = $child;
			}
		}
		
		/* If we didn't have an expiry date before, but the new package has a renewal term, set an expiry date */
		if ( !$purchase->expire and $term )
		{
			$purchase->expire = \IPS\DateTime::create()->add( $term->interval );
		}
		/* OR if we did have an expiry date, but the new package does not have a renewal term, remove it */
		elseif ( !$term )
		{
			$purchase->expire = NULL;
		}
				
		/* Update Purchase */
		$purchase->name = \IPS\Member::loggedIn()->language()->get( "nexus_package_{$newPackage->id}" );
		$purchase->item_id = $newPackage->id;
		$purchase->renewals = $term;
		$purchase->save();
		
		/* Regroup children */
		foreach ( $groupedChildren as $child )
		{
			$child->groupWithParent();
		}
		
		/* Re-add usergroups */
		$newPackage->_addUsergroups( $purchase );
		
		/* Cancel any pending invoices */
		if ( $pendingInvoice = $purchase->invoice_pending )
		{
			$pendingInvoice->status = \IPS\nexus\invoice::STATUS_CANCELED;
			$pendingInvoice->save();
			$purchase->invoice_pending = NULL;
			$purchase->save();
		}

		/* Regenerate license key */
		try
		{
			if ( $old = $purchase->licenseKey() )
			{
				$old->delete();
			}
		}
		catch ( \OutOfRangeException $e ) { }
		if ( $newPackage->lkey )
		{
			$class = 'IPS\nexus\Purchase\LicenseKey\\' . ucfirst( $newPackage->lkey );
			$licenseKey = new $class;
			$licenseKey->identifier = $newPackage->lkey_identifier;
			$licenseKey->purchase = $purchase;
			$licenseKey->max_uses = $newPackage->lkey_uses;
			$licenseKey->save();
		}
	}
	
	/* !Stock */
	
	/**
	 * Change Stock Level
	 *
	 * @param	int			$changeBy	Amount to change by
	 * @param	int|NULL	$optionId	Product option ID, if applicable
	 * @return	void
	 */
	protected function _changeStockLevel( $changeBy, $optionId=NULL )
	{		
		if ( $this->stock == -2 )
		{
			if ( $optionId !== NULL )
			{
				try
				{
					$stock = \IPS\Db::i()->select( 'opt_stock', 'nexus_product_options', array( 'opt_id=?', $optionId ) )->first();
					if ( $stock != -1 )
					{
						$newValue = $stock + $changeBy;
						if ( $newValue < 0 )
						{
							$newValue = 0;
						}
						
						\IPS\Db::i()->update( 'nexus_product_options', array( 'opt_stock' => $newValue ), array( 'opt_id=?', $optionId ) );
					}
				}
				catch ( \UnderflowException $e ) { }
			}
		}
		elseif ( $this->stock > 0 )
		{
			$newValue = $this->stock + $changeBy;
			if ( $newValue < 0 )
			{
				$newValue = 0;
			}
			$this->stock = $newValue;
			$this->save();
		}
	}
	
	/**
	 * @brief	Fields which affect the option ID
	 */
	protected $_optionIdKeys = NULL;
	
	/**
	 * Get field IDs which affect the option ID
	 *
	 * @return	array
	 */
	public function optionIdKeys()
	{
		if( $this->_optionIdKeys === NULL )
		{
			$this->_optionIdKeys = array();
			try
			{
				$options = \IPS\Db::i()->select( 'opt_values', 'nexus_product_options', array( 'opt_package=?', $this->id ) )->first();
				if ( $options = json_decode( $options, TRUE ) )
				{
					$this->_optionIdKeys = array_keys(  $options );
				}

			}
			catch ( \UnderflowException $e ) { }
		}
		return $this->_optionIdKeys;
	}
	
	/**
	 * Get option ID
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	int|NULL
	 */
	protected function _getOptionId( \IPS\nexus\Purchase $purchase )
	{
		$optionValues = array();
		foreach( $this->optionIdKeys() as $id )
		{
			$optionValues[ $id ] = $purchase->custom_fields[$id];
		}
		
		try
		{
			return \IPS\Db::i()->select( 'opt_id', 'nexus_product_options', array( 'opt_package=? AND opt_values=?', $this->id, json_encode( $optionValues ) ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			return NULL;
		}
	}
	
	/* !Usergroups */
	
	/**
	 * Add user groups
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function _addUsergroups( \IPS\nexus\Purchase $purchase )
	{
		$extra = $purchase->extra;
	
		/* Primary Group */
		if ( $this->primary_group and $this->primary_group != $purchase->member->member_group_id and !in_array( $purchase->member->member_group_id, explode( ',', \IPS\Settings::i()->cm_protected ) ) )
		{
			/* Hang on, are we about to boot someone out the ACP? */
			if ( ! ( \IPS\Member\Group::load( $purchase->member->member_group_id )->g_access_cp and !\IPS\Member\Group::load( $this->primary_group )->g_access_cp ) )
			{
				/* Save the current group */
				$extra['old_primary_group'] = $purchase->member->member_group_id;
				$purchase->extra = $extra;
				$purchase->save();
				if ( !$purchase->member->cm_return_group )
				{
					$purchase->member->cm_return_group = $purchase->member->member_group_id;
				}
				
				/* And update to the new group */
				$purchase->member->member_group_id = $this->primary_group;
				$purchase->member->save();
			}
		}
						
		/* Secondary Groups */
		$secondary = array_filter( explode( ',', $this->secondary_group ), create_function( '$v', 'return (bool) $v;' ) );
		$current_secondary = $purchase->member->mgroup_others ? explode( ',', $purchase->member->mgroup_others ) : array();
		$new_secondary = $current_secondary;
		if ( !empty( $secondary ) )
		{
			foreach ( $secondary as $gid )
			{
				if ( !in_array( $gid, $new_secondary ) )
				{
					$new_secondary[] = $gid;
				}
			}
		}
		if ( $current_secondary != $new_secondary )
		{
			$extra['old_secondary_groups'] = $purchase->member->mgroup_others;
			$purchase->extra = $extra;
			$purchase->save();
									
			$purchase->member->mgroup_others = ',' . implode( ',', $new_secondary ) . ',';
			$purchase->member->save();
		}
	}
	
	/**
	 * Remove user groups
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	array				$basis		The current package data, if needed to override
	 * @return	void
	 */
	public function _removeUsergroups( \IPS\nexus\Purchase $purchase, $basis = array() )
	{		
		$basis = array_merge( $this->_data, $basis );		
		$extra = $purchase->extra;
				
		/* Primary Group */
		if ( $basis['return_primary'] )
		{
			/* We only want to move them back if they haven't been moved again since */
			if ( $purchase->member->member_group_id == $basis['primary_group'] )
			{
				/* Have we made other purchases that have changed their primary group? */
				try
				{
					$purchase->member->member_group_id = \IPS\Db::i()->select( 'p_primary_group', 'nexus_purchases', array( 'ps_member=? AND ps_app=? AND ps_type=? AND ps_active=1 AND p_primary_group<>0 AND ps_id<>?', $purchase->member->member_id, 'nexus', 'package', $purchase->id ) )
						->join( 'nexus_packages', 'p_id=ps_item_id' )
						->first();
					$purchase->member->save();
				}
				/* No, move them to their original group */
				catch ( \UnderflowException $e )
				{
					$purchase->member->member_group_id = $purchase->member->cm_return_group;

					/* Does this group exist? */
					try
					{
						\IPS\Member\Group::load( $purchase->member->member_group_id );
					}
					catch ( \OutOfRangeException $e )
					{
						$purchase->member->member_group_id = \IPS\Settings::i()->member_group;
					}
										
					/* Save */
					$purchase->member->cm_return_group = 0;
					$purchase->member->save();
				}
			}
		}
				
		// Secondary groups
		if ( isset( $extra['old_secondary_groups'] ) and $extra['old_secondary_groups'] !== NULL and $basis['return_secondary'] )
		{			
			/* Work some stuff out */
			$membersSecondaryGroups = $purchase->member->mgroup_others ? array_unique( array_filter( explode( ',', $purchase->member->mgroup_others ) ) ) : array();
			$membersPreviousSecondaryGroupsBeforeThisPurchase = array_unique( array_filter( explode( ',', $extra['old_secondary_groups'] ) ) );
			$secondaryGroupsAwardedByThisPurchase = array_unique( array_filter( explode( ',', $basis['secondary_group'] ) ) );
			
			/* Have we made other purchases that have added secondary groups? */
			$secondaryGroupsAwardedByOtherPurchases = array();
			foreach ( \IPS\Db::i()->select( 'p_secondary_group', 'nexus_purchases', array( 'ps_member=? AND ps_app=? AND ps_type=? AND ps_active=1 AND p_secondary_group IS NOT NULL AND p_secondary_group<>? AND ps_id<>?', $purchase->member->member_id, 'nexus', 'package', '', $purchase->id ) )->join( 'nexus_packages', 'p_id=ps_item_id' ) as $secondaryGroups )
			{
				$secondaryGroupsAwardedByOtherPurchases = array_merge( $secondaryGroupsAwardedByOtherPurchases, array_filter( explode( ',', $secondaryGroups ) ) );
			}
			$secondaryGroupsAwardedByOtherPurchases = array_unique( $secondaryGroupsAwardedByOtherPurchases );
			
			/* Loop through */
			foreach ( $secondaryGroupsAwardedByThisPurchase as $groupId )
			{
				/* If we had this group before we made this purchase, we're going to keep it */
				if ( in_array( $groupId, $membersPreviousSecondaryGroupsBeforeThisPurchase ) )
				{
					continue;
				}
				
				/* If we are being awarded this group by a different purchase, we're also going to keep it */
				if ( in_array( $groupId, $secondaryGroupsAwardedByOtherPurchases ) )
				{
					continue;
				}
				
				/* If we're still here, remove it */
				unset( $membersSecondaryGroups[ array_search( $groupId, $membersSecondaryGroups ) ] );
			}
			
			/* Save */
			$purchase->member->mgroup_others = implode( ',', $membersSecondaryGroups );
			$purchase->member->save();
		}
	}
}