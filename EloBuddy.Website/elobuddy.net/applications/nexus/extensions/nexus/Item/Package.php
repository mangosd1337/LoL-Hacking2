<?php
/**
 * @brief		Package
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package
 */
class _Package extends \IPS\nexus\Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'package';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'archive';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'product';
	
	/**
	 * Get (can be used to override static properties like icon and title in an instance)
	 *
	 * @param	string	$k	Property
	 * @return	mixed
	 */
	public function __get( $k )
	{
		if ( $k === '_icon' or $k === '_title' )
		{
			try
			{
				$package = \IPS\nexus\Package::load( $this->id );
				return $k === '_icon' ? $package::$icon : $package::$title;
			}
			catch ( \Exception $e ) { }
		}
		return parent::__get( $k );
	}
	
	/**
	 * Get Icon
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	string
	 */
	public static function getIcon( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$class = \IPS\nexus\Package::load( $purchase->item_id );
			return $class::$icon;
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Get Title
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	string
	 */
	public static function getTypeTitle( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$class = \IPS\nexus\Package::load( $purchase->item_id );
			return $class::$title;
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Image
	 *
	 * @return |IPS\File|NULL
	 */
	public function image()
	{
		try
		{
			$imageUrl = \IPS\nexus\Package::load( $this->id )->image;
			if ( !$imageUrl )
			{
				return NULL;
			}
			
			return \IPS\File::get( 'nexus_Products', $imageUrl );
		}
		catch ( \Exception $e ) {}
		
		return NULL;
	}
	
	/**
	 * Image
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return |IPS\File|NULL
	 */
	public static function purchaseImage( \IPS\nexus\Purchase $purchase )
	{
		try
		{			
			$imageUrl = \IPS\nexus\Package::load( $purchase->item_id )->image;
			if ( !$imageUrl )
			{
				return NULL;
			}
			
			return \IPS\File::get( 'nexus_Products', $imageUrl );
		}
		catch ( \Exception $e )
		{
			return NULL;
		}
	}
		
	/**
	 * Generate Invoice Form: First Step
	 *
	 * @param	\IPS\Helpers\Form	$form		The form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( \IPS\Helpers\Form $form, \IPS\nexus\Invoice $invoice )
	{	
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Custom( 'invoice_products', array(), TRUE, array(
			'rowHtml'	=> function( $field )
			{
				return \IPS\Theme::i()->getTemplate('invoices')->packageSelector( $field->value );
			}
		) ) );
	}
	
	/**
	 * Generate Invoice Form: Second Step
	 *
	 * @param	\IPS\Helpers\Form	$form		The form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	bool
	 */
	public static function formSecondStep( array $values, \IPS\Helpers\Form $form, \IPS\nexus\Invoice $invoice )
	{
		$displayForm = FALSE;
		
		/* Do an initial loop so we know what we can associate with */
		$justSelected = array_filter( $values['invoice_products'] );
		
		/* Now do the actual loop */
		foreach ( array_filter( $values['invoice_products'] ) as $id => $qty )
		{
			$package = \IPS\nexus\Package::load( $id );
			$customFields = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_package_fields', \IPS\Db::i()->findInSet( 'cf_packages', array( $package->id ) ) ), 'IPS\nexus\Package\CustomField' );
			$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();

			for ( $i = 0; $i < $qty; $i++ )
			{							
				if ( count( $customFields ) or count( $renewOptions ) > 1 or count( $package->associablePackages() ) or method_exists( $package, 'generateInvoiceForm' ) )
				{
					$displayForm = TRUE;
					$form->addHeader( $package->_title );
					
					if ( count( $customFields ) )
					{
						foreach ( $customFields as $field )
						{
							$field = $field->buildHelper();
							$field->label = \IPS\Member::loggedIn()->language()->addToStack( $field->name );
							$field->name = "{$field->name}_{$id}_{$i}";
							$form->add( $field );
						}
					}
					
					if ( count( $renewOptions ) > 1 )
					{
						$options = array();
						foreach ( $renewOptions as $k => $option )
						{
							switch ( $option['unit'] )
							{
								case 'd':
									$term = \IPS\Member::loggedIn()->language()->addToStack('renew_days', FALSE, array( 'pluralize' => array( $option['term'] ) ) );
									break;
								case 'm':
									$term = \IPS\Member::loggedIn()->language()->addToStack('renew_months', FALSE, array( 'pluralize' => array( $option['term'] ) ) );
									break;
								case 'y':
									$term = \IPS\Member::loggedIn()->language()->addToStack('renew_years', FALSE, array( 'pluralize' => array( $option['term'] ) ) );
									break;
							}
							
							$options[ $k ] = 
								\IPS\Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array(
								(string) new \IPS\nexus\Money( $option['cost'][ $invoice->currency ]['amount'], $invoice->currency ),
								$term
							) ) );
						}
						
						$field = new \IPS\Helpers\Form\Radio( "renewal_term_{$id}_{$i}", NULL, TRUE, array( 'options' => $options ) );
						$field->label = 'renewal_term';
						$form->add( $field );
					}
					
					if ( count( $package->associablePackages() ) )
					{
						$associableIds = array_keys( $package->associablePackages() );
						$associableOptions = array();
						if ( !$package->force_assoc )
						{
							$associableOptions[0] = 'no_parent';
						}
						$selected = NULL;
						foreach ( $justSelected as $k => $_qty )
						{
							if ( in_array( $k, $associableIds ) )
							{
								for ( $j = 0; $j < $_qty; $j++ )
								{
									$associableOptions['just_selected'][ "2.{$k}.{$j}" ] = \IPS\nexus\Package::load( $k )->_title;
									if ( $j === $i )
									{
										$selected = "2.{$k}.{$j}";
									}
								}
							}
						}
						foreach ( $invoice->items as $k => $item )
						{
							if ( in_array( $item->id, $associableIds ) )
							{
								for ( $i = 0; $i < $item->quantity; $i++ )
								{
									$name = $item->name;
									if ( count( $item->details ) )
									{
										$customFields = \IPS\nexus\Package\CustomField::roots();
										$stickyFields = array();
										foreach ( $item->details as $k => $v )
										{
											if ( $v and isset( $customFields[ $k ] ) and $customFields[ $k ]->sticky )
											{
												$stickyFields[] = $v;
											}
										}
										if ( count( $stickyFields ) )
										{
											$name .= ' (' . implode( ' &middot; ', $stickyFields ) . ')';
										}
									}
									$associableOptions['on_invoice']["0.{$k}"] = $name;
								}
							}
						}
						foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( array( 'ps_member=? AND ps_app=? AND ps_type=?', $invoice->member->member_id, 'nexus', 'package' ), \IPS\Db::i()->in( 'ps_item_id', $associableIds ) ) ), 'IPS\nexus\Purchase' ) as $purchase )
						{
							$associableOptions['existing_purchases'][ "1.{$purchase->id}" ] = $purchase->name;
						}
						$field = new \IPS\Helpers\Form\Select( "associate_with_{$id}_{$i}", $selected, $package->force_assoc, array( 'options' => $associableOptions ) );
						$field->label = 'associate_with';
						$form->add( $field );
					}
					
					if ( method_exists( $package, 'generateInvoiceForm' ) )
					{
						$package->generateInvoiceForm( $form, "_{$id}_{$i}" );
					}
				}
			}
		}
		
		return $displayForm;
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	array
	 */
	public static function createFromForm( array $values, \IPS\nexus\Invoice $invoice )
	{
		/* Get the packages we want to add */
		if ( isset( \IPS\Request::i()->firstStep ) )
		{
			$data = json_decode( \IPS\Request::i()->firstStep, TRUE );
			$data = $data['invoice_products'];
		}
		else
		{
			$data = $values['invoice_products'];
		}
		
		/* Loop them */
		$items = array();
		$itemsToBeAssociated = array();
		foreach ( array_filter( $data ) as $id => $qty )
		{
			/* Load package */
			$package = \IPS\nexus\Package::load( $id );
			
			/* Get the number already on the invoice for the purposes of discounts */
			$initialCount = 0;
			foreach ( $invoice->items as $_item )
			{
				if ( $_item instanceof \IPS\nexus\extensions\nexus\Item\Package and $_item->id == $id )
				{
					$initialCount += $_item->quantity;
				}
			}
			
			/* Loop for each qty */
			for ( $i = 0; $i < $qty; $i++ )
			{	
				/* Base price */
				$price = $package->price( $invoice->member, TRUE, TRUE, TRUE, $initialCount + $i )->amount;
				
				/* Custom Fields */
				$details = array();
				foreach ( $values as $k => $v )
				{
					if ( preg_match( "/nexus_pfield_(\d+)_{$id}_{$i}/", $k, $matches ) )
					{
						$details[ $matches[1] ] = (string) $v;
					}
				}
				
				/* Work out renewal term */
				$renewalTerm = NULL;
				$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
				if ( count( $renewOptions ) )
				{
					if ( count( $renewOptions ) === 1 )
					{
						$chosenRenewOption = array_pop( $renewOptions );
					}
					else
					{
						$chosenRenewOption = $renewOptions[ $values["renewal_term_{$id}_{$i}"] ];
					}
					
					$renewalTerm = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $chosenRenewOption['cost'][ $invoice->currency ]['amount'], $invoice->currency ), new \DateInterval( 'P' . $chosenRenewOption['term'] . mb_strtoupper( $chosenRenewOption['unit'] ) ), $package->tax ? \IPS\nexus\Tax::load( $package->tax ) : NULL );
					
					if ( $chosenRenewOption['add'] )
					{
						$price = $price->add( $renewalTerm->cost->amount );
					}
				}
				
				/* Create item */
				$item = new \IPS\nexus\extensions\nexus\Item\Package( \IPS\Member::loggedIn()->language()->get( 'nexus_package_' . $package->id ), new \IPS\nexus\Money( $price, $invoice->currency ) );
				$item->renewalTerm = $renewalTerm;
				$item->id = $package->id;
				$item->tax = $package->tax ? \IPS\nexus\Tax::load( $package->tax ) : NULL;
				if ( $package instanceof \IPS\nexus\Package\Product and $package->physical )
				{
					$item->physical = TRUE;
					$item->weight = new \IPS\nexus\Shipping\Weight( $package->weight );
					$item->length = new \IPS\nexus\Shipping\Length( $package->length );
					$item->width = new \IPS\nexus\Shipping\Length( $package->width );
					$item->height = new \IPS\nexus\Shipping\Length( $package->height );
					if ( $package->shipping !== '*' )
					{
						$item->shippingMethodIds = explode( ',', $package->shipping );
					}
				}
				if ( $package->methods and $package->methods != '*' )
				{
					$item->paymentMethodIds = explode( ',', $package->methods );
				}
				$item->details = $details;
				
				/* Associations */
				if ( isset( $values["associate_with_{$id}_{$i}"] ) and $values["associate_with_{$id}_{$i}"] )
				{
					$exploded = explode( '.', $values["associate_with_{$id}_{$i}"] );
					switch ( $exploded[0] )
					{
						case '0':
							$item->parent = (int) $exploded[1];
							break;
						case '1':
							$item->parent = \IPS\nexus\Purchase::load( $exploded[1] );
							break;
						case '2':
							$itemsToBeAssociated["{$id}.{$i}"] = "{$exploded[1]}.{$exploded[2]}";
							break;
					}
				}
				
				/* Do any package-sepcific modifications */
				$package->acpAddToInvoice( $item, $values, "_{$id}_{$i}", $invoice );
								
				/* Add it */
				$items["{$id}.{$i}"] = $item;
			}
		}
		
		/* Sort out any associations */
		$added = array();
		foreach( $itemsToBeAssociated as $itemKey => $associateKey )
		{
			if ( !array_key_exists( $associateKey, $added ) )
			{
				$added[ $associateKey ] = $invoice->addItem( $items[ $associateKey ] );
			}
			$items[ $itemKey ]->parent = $added[ $associateKey ];
		}
						
		/* Group wherever we can */
		$itemsToAdd = array();
		foreach ( $items as $key => $item )
		{
			if ( !array_key_exists( $key, $added ) )
			{
				/* Is this the same as any of the other items? */
				foreach ( $itemsToAdd as $_item )
				{
					$cloned = clone $_item;
					$cloned->quantity = 1;
					
					if ( $cloned == $item )
					{
						$_item->quantity++;
						continue 2;
					}
				}
				
				/* Or anything on the invoice? */
				foreach ( $invoice->items as $k => $_item )
				{
					$cloned = clone $_item;
					$cloned->quantity = 1;
					
					if ( $cloned == $item )
					{
						$invoice->changeItem( $k, array( 'quantity' => $_item->quantity + 1 ) );
						continue 2;
					}
				}
				
				/* Nope, give it it's own entry */
				$itemsToAdd[] = $item;
			}
		}
		
		/* Return */
		return $itemsToAdd;
	}
	
	/**
	 * Get additional name info
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	array
	 */
	public static function getPurchaseNameInfo( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'getPurchaseNameInfo' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return array();
		}
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @return	string
	 */
	public static function acpPage( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'acpPage' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return '';
		}
	}
	
	/**
	 * Get ACP Page Buttons
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\Http\Url		$url		The page URL
	 * @return	array
	 */
	public static function acpButtons( \IPS\nexus\Purchase $purchase, \IPS\Http\Url $url )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'acpButtons' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return array();
		}
	}
	
	/**
	 * ACP Action
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function acpAction( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'acpAction' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e ) {}
	}
	
	/** 
	 * ACP Edit Form
	 *
	 * @param	\IPS\nexus\Purchase				$purchase	The purchase
	 * @param	\IPS\Helpers\Form				$form	The form
	 * @param	\IPS\nexus\Purchase\RenewalTerm	$renewals	The renewal term
	 * @return	void
	 */
	public static function acpEdit( \IPS\nexus\Purchase $purchase, \IPS\Helpers\Form $form, $renewals )
	{
		$form->addHeader('nexus_purchase_settings');
		parent::acpEdit( $purchase, $form, $renewals );
		
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'acpEdit' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e ) { }
	}
	
	/** 
	 * ACP Edit Save
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	array				$values		Values from form
	 * @return	void
	 */
	public static function acpEditSave( \IPS\nexus\Purchase $purchase, array $values )
	{
		try
		{
			call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'acpEditSave' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e ) { }
		
		parent::acpEditSave( $purchase, $values );
	}
	
	/**
	 * Get Client Area Page HTML
	 *
	 * @return	array( 'packageInfo' => '...', 'purchaseInfo' => '...' )
	 */
	public static function clientAreaPage( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'clientAreaPage' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return array( 'packageInfo' => '', 'purchaseInfo' => '' );
		}
	}
	
	/**
	 * Client Area Action
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	string
	 */
	public static function clientAreaAction( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'clientAreaAction' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Support Severity
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	\IPS\nexus\Support\Severity|NULL
	 */
	public static function supportSeverity( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'supportSeverity' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/** 
	 * Get renewal payment methods IDs
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	array|NULL
	 */
	public static function renewalPaymentMethodIds( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$package = \IPS\nexus\Package::load( $purchase->item_id );
			if ( $package->methods and $package->methods != '*' )
			{
				return explode( ',', $package->methods );
			}
			else
			{
				return NULL;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Paid
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPaid( \IPS\nexus\Invoice $invoice )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $this->id ), 'onPaid' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Unpaid description
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	array
	 */
	public function onUnpaidDescription( \IPS\nexus\Invoice $invoice )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $this->id ), 'onUnpaidDescription' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return array();
		}
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
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $this->id ), 'onUnpaid' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Invoice Cancel (when unpaid)
	 *
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onInvoiceCancel( \IPS\nexus\Invoice $invoice )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $this->id ), 'onInvoiceCancel' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
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
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $this->id ), 'memberCanPurchase' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Purchase Generated
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function onPurchaseGenerated( \IPS\nexus\Purchase $purchase, \IPS\nexus\Invoice $invoice )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onPurchaseGenerated' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Renew (Renewal invoice paid. Is not called if expiry data is manually changed)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	int					$cycles		Cycles
	 * @return	void
	 */
	public static function onRenew( \IPS\nexus\Purchase $purchase, $cycles )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onRenew' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Expiration Date Change
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onExpirationDateChange( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onExpirationDateChange' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Purchase Expired
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onExpire( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onExpire' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onCancel( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onCancel' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onDelete( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onDelete' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public static function onReactivate( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onReactivate' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * On Transfer (is ran before transferring)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase		The purchase
	 * @param	\IPS\Member			$newCustomer	New Customer
	 * @return	void
	 */
	public static function onTransfer( \IPS\nexus\Purchase $purchase, \IPS\Member $newCustomer )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'onTransfer' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Can Renew Until
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	bool				$admin		If TRUE, is for ACP. If FALSE, is for front-end.
	 * @return	\IPS\DateTime|bool	TRUE means can renew as much as they like. FALSE means cannot renew at all. \IPS\DateTime means can renew until that date
	 */
	public static function canRenewUntil( \IPS\nexus\Purchase $purchase, $admin )
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $purchase->item_id ), 'canRenewUntil' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Show Purchase Record?
	 *
	 * @return	bool
	 */
	public function showPurchaseRecord()
	{
		try
		{
			return call_user_func_array( array( \IPS\nexus\Package::load( $this->id ), 'showPurchaseRecord' ), func_get_args() );
		}
		catch ( \OutOfRangeException $e )
		{
			return FALSE;
		}
	}
}