<?php
/**
 * @brief		View product
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus 
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\store;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View product
 */
class _product extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\nexus\Package\Item';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_store.js', 'nexus', 'front' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store.css', 'nexus' ) );

		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store_responsive.css', 'nexus', 'front' ) );
		}
		
		parent::execute();
	}

	/**
	 * View
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init */
		if ( !isset( $_SESSION['cart'] ) )
		{
			$_SESSION['cart'] = array();
		}
		$memberCurrency = ( ( isset( $_SESSION['currency'] ) and in_array( $_SESSION['currency'], \IPS\nexus\Money::currencies() ) ) ? $_SESSION['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() );
		
		/* Load Package */
		$item = parent::manage();
		if ( !$item )
		{
			\IPS\Output::i()->error( 'node_error', '2X240/1', 404, '' );
		}
		$package = \IPS\nexus\Package::load( $item->id );

		/* Do we have any in the cart already (this will affect stock level)? */
		$inCart = 0;
		foreach ( $_SESSION['cart'] as $itemInCart )
		{
			if ( $itemInCart->id === $package->id )
			{
				$inCart += $itemInCart->quantity;
			}
		}
						
		/* Showing just the form to purchase, or full product page? */
		if ( \IPS\Request::i()->purchase )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->purchaseForm( $package, $item, $this->_getForm( $package, $inCart, TRUE ), $inCart );	
		}
		
		/* No - show the full page */
		else
		{
			/* We need to create a dummy item so we can work out ship times/prices */
			$itemDataForShipping = NULL;
			if ( $package->physical )
			{
				try
				{
					$itemDataForShipping = $package->createItemForCart( $package->price() );
				}
				catch ( \OutOfBoundsException $e ) { }
			}
			
			/* If physical, get available shipping methods */
			$shippingMethods = array();
			$locationType = 'none';
			if ( $package->physical )
			{
				/* Where are we shipping to? */
				$shipAddress = NULL;
				if ( \IPS\Member::loggedIn()->member_id )
				{
					try
					{
						$shipAddress = \IPS\nexus\Customer\Address::constructFromData( \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=? AND primary_billing=1', \IPS\Member::loggedIn()->member_id ) )->first() )->address;
						$locationType = 'address';
					}
					catch ( \UnderflowException $e ) { }
				}
				if ( !$shipAddress )
				{
					try
					{
						$shipAddress = \IPS\GeoLocation::getByIp( \IPS\Request::i()->ipAddress() );
						$locationType = 'geo';
					}
					catch ( \Exception $e ) { }
				}
				
				/* Standard */
				$where = NULL;
				if ( $package->shipping != '*' )
				{
					$where = \IPS\Db::i()->in( 's_id', explode( ',', $package->shipping ) );
				}
				foreach ( \IPS\nexus\Shipping\FlatRate::roots( NULL, NULL, $where ) as $rate )
				{
					if ( ( $shipAddress and $rate->isAvailable( $shipAddress, array( $itemDataForShipping ), $memberCurrency ) ) or ( $rate->locations === '*' ) )
					{	
						$shippingMethods[] = $rate;
					}
				}
				
				/* Easypost */
				if ( $shipAddress and \IPS\Settings::i()->easypost_api_key and \IPS\Settings::i()->easypost_show_rates and ( $package->shipping == '*' or in_array( 'easypost', explode( ',', $package->shipping ) ) ) )
				{
					try
					{
						$fromAddress = \IPS\GeoLocation::buildFromJson( \IPS\Settings::i()->easypost_address ?: \IPS\Settings::i()->site_address );
						
						$length = new \IPS\nexus\Shipping\Length( $package->length );
						$width = new \IPS\nexus\Shipping\Length( $package->width );
						$height = new \IPS\nexus\Shipping\Length( $package->height );
						$weight = new \IPS\nexus\Shipping\Weight( $package->weight );
						
						$easyPost = \IPS\nexus\Shipping\EasyPostRate::getRates( $length->float('in'), $width->float('in'), $height->float('in'), $weight->float('oz'), \IPS\nexus\Customer::loggedIn(), $shipAddress, $memberCurrency );
						if ( isset( $easyPost['rates'] ) )
						{
							foreach ( $easyPost['rates'] as $rate )
							{
								if ( $rate['currency'] === $memberCurrency )
								{
									$shippingMethods[] = new \IPS\nexus\Shipping\EasyPostRate( $rate );
								}
							}
						}
					}
					catch ( \IPS\Http\Request\Exception $e ) { }
				}
			}
						
			/* Do we have renewal terms? */
			$renewalTerm = NULL;
			$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
			if ( count( $renewOptions ) )
			{
				$renewalTerm = TRUE;
				if ( count( $renewOptions ) === 1 )
				{
					$renewalTerm = array_pop( $renewOptions );
					$renewalTerm = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( $renewalTerm['cost'][ $memberCurrency ]['amount'], $memberCurrency ), new \DateInterval( 'P' . $renewalTerm['term'] . mb_strtoupper( $renewalTerm['unit'] ) ), isset( $this->tax ) ? \IPS\nexus\Tax::load( $this->tax ) : NULL, $renewalTerm['add'] );
				}
			}
			
			/* Display */
			$formKey = "package_{$package->id}_submitted";
			if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->$formKey ) )
			{
				\IPS\Output::i()->sendOutput( $this->_getForm( $package, $inCart, TRUE ), 500 );
			}
			else
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->package( $package, $item, $this->_getForm( $package, $inCart, TRUE ), $inCart, $shippingMethods, $itemDataForShipping, $locationType, $renewalTerm );	
			}
		}		
	}

	/**
	 * Get form
	 *
	 * @param	\IPS\nexus\Package	$package	The package
	 * @param	int					$inCart		The number in the cart already
	 * @return	string
	 */
	protected function _getForm( \IPS\nexus\Package $package, $inCart, $verticalForm = FALSE )
	{
		/* Get member's currency */
		$memberCurrency = ( ( isset( $_SESSION['currency'] ) and in_array( $_SESSION['currency'], \IPS\nexus\Money::currencies() ) ) ? $_SESSION['currency'] : \IPS\nexus\Customer::loggedIn()->defaultCurrency() );
				
		/* Init form */		
		$form = new \IPS\Helpers\Form( "package_{$package->id}", 'add_to_cart' );

		if ( $verticalForm )
		{
			$form->class = 'ipsForm_vertical';
		}
		
		/* Package-dependant fields */
		$package->storeForm( $form, $memberCurrency );
		
		/* Are we in stock? */
		if ( $package->stock != -1 and $package->stock != -2 and ( $package->stock - $inCart ) <= 0 )
		{
			$form->actionButtons = array();
			$form->addButton( 'out_of_stock', 'submit', NULL, 'ipsButton ipsButton_primary', array( 'disabled' => 'disabled' ) );
		}
		
		/* And is it available for our currency */
		else
		{
			try
			{
				$price = $package->price();
			}
			catch ( \OutOfBoundsException $e )
			{
				$form->actionButtons = array();
				$form->addButton( 'currently_unavailable', 'submit', NULL, 'ipsButton ipsButton_primary', array( 'disabled' => 'disabled' ) );
			}
		}

		/* Associate */
		if ( count( $package->associablePackages() ) )
		{
			$associableIds = array_keys( $package->associablePackages() );
			$associableOptions = array();
			foreach ( $_SESSION['cart'] as $k => $item )
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
						$associableOptions['in_cart']["0.{$k}"] = $name;
					}
				}
			}
			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( array( 'ps_member=? AND ps_app=? AND ps_type=?', \IPS\nexus\Customer::loggedIn()->member_id, 'nexus', 'package' ), \IPS\Db::i()->in( 'ps_item_id', $associableIds ) ) ), 'IPS\nexus\Purchase' ) as $purchase )
			{
				$associableOptions['existing_purchases']["1.{$purchase->id}"] = $purchase->name;
			}
			
			if ( !empty( $associableOptions ) )
			{
				if ( !$package->force_assoc )
				{
					array_unshift( $associableOptions, 'do_not_associate' );
				}
				$form->add( new \IPS\Helpers\Form\Select( 'associate_with', NULL, $package->force_assoc, array( 'options' => $associableOptions ) ) );
			}
			elseif ( $package->force_assoc )
			{
				return \IPS\Member::loggedIn()->language()->addToStack("nexus_package_{$package->id}_assoc");
			}
		}
		
		/* Renewal options */
		$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
		if ( count( $renewOptions ) > 1 )
		{
			$options = array();
			foreach ( $renewOptions as $k => $option )
			{
				if ( isset( $option['cost'][ $memberCurrency ] ) )
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
						(string) new \IPS\nexus\Money( $option['cost'][ $memberCurrency ]['amount'], $memberCurrency ),
						$term
					) ) );
				}
			}
			
			$form->add( new \IPS\Helpers\Form\Radio( 'renewal_term', NULL, TRUE, array( 'options' => $options ) ) );
		}
		
		/* Custom Fields */
		$customFields = \IPS\nexus\Package\CustomField::roots( NULL, NULL, array( array( 'cf_purchase=1' ), array( \IPS\Db::i()->findInSet( 'cf_packages', array( $package->id ) ) ) ) );
		foreach ( $customFields as $field )
		{
			$form->add( $field->buildHelper() );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Custom fields */
			$details = array();
			$editorUploadIds = array();
			foreach ( $customFields as $field )
			{
				if ( isset( $values[ 'nexus_pfield_' . $field->id ] ) )
				{
					$class = $field->buildHelper();
					$details[ $field->id ] = $class::stringValue( $values[ 'nexus_pfield_' . $field->id ] );
					
					if ( !isset( \IPS\Request::i()->stockCheck ) and $field->type === 'Editor' )
					{
						$uploadId = \IPS\Db::i()->insert( 'nexus_cart_uploads', array(
							'session_id'	=> \IPS\Session::i()->id,
							'time'			=> time()
						) );
						$field->claimAttachments( $uploadId, 'cart' );
						$editorUploadIds[] = $uploadId;
					}
				}
			}
			$optionValues = $package->optionValues( $details );
			
			/* Stock check */
			$quantity = isset( $values['quantity'] ) ? $values['quantity'] : 1;
			try
			{
				$data = $package->optionValuesStockAndPrice( $optionValues, TRUE );
			}
			catch ( \UnderflowException $e )
			{
				\IPS\Output::i()->error( 'product_options_price_error', '3X240/2', 500, 'product_options_price_error_admin' );
			}

			if ( \IPS\Request::i()->isAjax() && isset( \IPS\Request::i()->stockCheck ) )
			{
				/* Stock */
				if ( $data['stock'] == -1 )
				{
					$return = array(
						'stock'	=> '',
						'okay'	=> true
					);
				}
				else
				{
					$return = array(
						'stock'	=> \IPS\Member::loggedIn()->language()->addToStack( 'x_in_stock', FALSE, array( 'pluralize' => array( $data['stock'] - $inCart ) ) ),
						'okay'	=> true
					);
				}
							
				/* Price */	
				$_data = $package->optionValuesStockAndPrice( $optionValues, FALSE );
				$normalPrice = $_data['price'];
				
				/* Renewals */
				$renewOptions = $package->renew_options ? json_decode( $package->renew_options, TRUE ) : array();
				if ( !empty( $renewOptions ) )
				{
					$term = ( isset( \IPS\Request::i()->renewal_term ) and isset( $renewOptions[ \IPS\Request::i()->renewal_term ] ) ) ? $renewOptions[ \IPS\Request::i()->renewal_term ] : array_shift( $renewOptions );

					if ( $term['add'] )
					{
						$data['price']->amount = $data['price']->amount->add( new \IPS\Math\Number( number_format( $term['cost'][ $memberCurrency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) );
						$normalPrice->amount = $normalPrice->amount->add( new \IPS\Math\Number( number_format( $term['cost'][ $memberCurrency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) );
					}
					
					$return['renewal'] = \IPS\Member::loggedIn()->language()->addToStack( 'and_renewal', FALSE, array( 'sprintf' => array( new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( ( new \IPS\Math\Number( number_format( $term['cost'][ $memberCurrency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) )->add( ( new \IPS\Math\Number( number_format( $_data['renewalAdjustment'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $memberCurrency ), '.', '' ) ) ) ), $memberCurrency ), new \DateInterval( 'P' . $term['term'] . mb_strtoupper( $term['unit'] ) ), isset( $this->tax ) ? \IPS\nexus\Tax::load( $this->tax ) : NULL, $term['add'] ) ) ) );
				}
				else
				{
					$return['renewal'] = '';
				}
				
				/* Include tax? */
				if ( \IPS\Settings::i()->nexus_show_tax and $package->tax )
				{
					try
					{
						$taxRate = new \IPS\Math\Number( number_format( \IPS\nexus\Tax::load( $package->tax )->rate( \IPS\nexus\Customer::loggedIn()->estimatedLocation() ), 2, '.', '' ) );
						
						$data['price']->amount = $data['price']->amount->add( $data['price']->amount->multiply( $taxRate ) );
						$normalPrice->amount = $normalPrice->amount->add( $normalPrice->amount->multiply( $taxRate ) );
					}
					catch ( \OutOfRangeException $e ) { }
				}
				
				/* Format and return */
				if ( $data['price']->amount->compare( $normalPrice->amount ) !== 0 )
				{
					$return['price'] = \IPS\Theme::i()->getTemplate( 'store', 'nexus' )->priceDiscounted( $normalPrice, $data['price'], FALSE );
				}
				else
				{
					$return['price'] = \IPS\Theme::i()->getTemplate( 'store', 'nexus' )->price( $data['price'], FALSE );
				}
				\IPS\Output::i()->json( $return );
			}
			elseif ( $data['stock'] != -1 and ( $data['stock'] - $inCart ) < $quantity )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'not_enough_in_stock', FALSE, array( 'sprintf' => array( $data['stock'] - $inCart ) ) );
				return (string) $form;
			}
						
			/* Work out renewal term */
			$renewalTerm = NULL;
			if ( count( $renewOptions ) )
			{
				if ( count( $renewOptions ) === 1 )
				{
					$chosenRenewOption = array_pop( $renewOptions );
				}
				else
				{
					$chosenRenewOption = $renewOptions[ $values['renewal_term'] ];
				}
				
				$renewalTerm = new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( ( new \IPS\Math\Number( number_format( $chosenRenewOption['cost'][ $memberCurrency ]['amount'], 2, '.', '' ) ) )->add( new \IPS\Math\Number( number_format( $data['renewalAdjustment'], 2, '.', '' ) ) ), $memberCurrency ), new \DateInterval( 'P' . $chosenRenewOption['term'] . mb_strtoupper( $chosenRenewOption['unit'] ) ), isset( $this->tax ) ? \IPS\nexus\Tax::load( $this->tax ) : NULL, $chosenRenewOption['add'], $package->grace_period ? new \DateInterval( 'P' . $package->grace_period . 'D' ) : NULL );
			}
			
			/* Associations */
			$parent = NULL;
			if ( isset( $values['associate_with'] ) and $values['associate_with'] )
			{
				$exploded = explode( '.', $values['associate_with'] );
				if ( $exploded[0] )
				{
					$parent = \IPS\nexus\Purchase::load( $exploded[1] );
				}
				else
				{
					$parent = (int) $exploded[1];
				}
			}
			
			/* Actually add to cart */
			$cartId = $package->addItemsToCartData( $details, $quantity, $renewalTerm, $parent, $values );
			\IPS\Db::i()->update( 'nexus_cart_uploads', array( 'item_id' => $cartId ), \IPS\Db::i()->in( 'id', $editorUploadIds ) );

			/* Redirect or AJAX */
			if ( \IPS\Request::i()->isAjax() )
			{
				/* Upselling? */
				$upsell = \IPS\nexus\Package\Item::getItemsWithPermission( array( array( 'p_upsell=1' ), array( \IPS\Db::i()->findInSet( 'p_associable', array( $package->id ) ) ) ), 'p_position' );
				
				/* Send */
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate('store')->cartReview( $package, $quantity, $upsell ), 200, 'text/html' );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=store&controller=cart&added=' . $package->id, 'front', 'store_cart' ) );	
			}			
		}
		
		
		return (string) $form;
	}
}