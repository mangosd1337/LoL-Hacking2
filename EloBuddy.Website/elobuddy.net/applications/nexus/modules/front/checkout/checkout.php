<?php
/**
 * @brief		Checkout
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\checkout;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Checkout
 */
class _checkout extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Invoice
	 */
	protected $invoice;
	
	/**
	 * @brief	Does the user need to log in?
	 */
	protected $needsToLogin = FALSE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'checkout.css', 'nexus' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store.css', 'nexus' ) );

		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store_responsive.css', 'nexus', 'front' ) );
		}
		
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_checkout.js', 'nexus', 'front' ) );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'module__nexus_checkout' );
		parent::execute();
	}

	/**
	 * Checkout
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		/* Load invoice */
		try
		{
			$this->invoice = \IPS\nexus\Invoice::loadAndCheckPerms( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			$msg = 'no_module_permission';
			if ( !\IPS\Member::loggedIn()->member_id )
			{
				$msg = 'no_module_permission_guest';
			}
			
			\IPS\Output::i()->error( $msg, '2X196/1', 403, '' );
		}
		
		/* Is it paid? */
		if ( $this->invoice->status === \IPS\nexus\Invoice::STATUS_PAID )
		{
			if ( $this->invoice->return_uri )
			{
				\IPS\Output::i()->redirect( $this->invoice->return_uri );
			}
			else
			{
				\IPS\Output::i()->redirect( $this->invoice->url() );
			}
		}
		
		/* Or cancelled or expired? */
		if ( $this->invoice->status !== \IPS\nexus\Invoice::STATUS_PENDING )
		{
			\IPS\Output::i()->redirect( $this->invoice->url() );
		}
		
		/* What are the steps? */
		$steps = array();
		$steps['checkout_customer'] = array( $this, '_customer' );
		if ( $this->invoice->hasPhysicalItems() )
		{
			$steps['checkout_shipping'] = array( $this, '_shipping' );
		}
		$steps['checkout_pay'] = array( $this, '_pay' );
		
		/* Do we need to log in? */
		$this->needsToLogin = ( !\IPS\Member::loggedIn()->member_id and ( $this->invoice->requiresLogin() or \IPS\Settings::i()->nexus_donate_loggedin ) );
		
		/* Can we skip the first step? */
		$checkoutUrl = $this->invoice->checkoutUrl();
		if ( \IPS\Member::loggedIn()->member_id and $this->invoice->member->cm_first_name and $this->invoice->member->cm_last_name and !isset( $_SESSION[ 'wizard-' . md5( $checkoutUrl ) . '-step' ] ) )
		{
			$canSkipFirstStep = TRUE;
			foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
			{
				$column = $field->column;
				if ( $field->purchase_show and $field->purchase_require and !$this->invoice->member->$column )
				{
					$canSkipFirstStep = FALSE;
					break;
				}
			}
						
			if ( $canSkipFirstStep )
			{
				try
				{					
					$this->invoice->billaddress = \IPS\nexus\Customer\Address::constructFromData( \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=? AND primary_billing=1', \IPS\Member::loggedIn()->member_id ) )->first() )->address;
					$this->invoice->save();
					$_SESSION[ 'wizard-' . md5( $checkoutUrl ) . '-step' ] = isset( $steps['checkout_shipping'] ) ? 'checkout_shipping' : 'checkout_pay';
				}
				catch ( \UnderflowException $e ) { }
			}
		}
		
		/* Do the wizard */
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->breadcrumb['module'][0] = NULL;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('checkout')->checkoutWrapper( (string) new \IPS\Helpers\Wizard( $steps, $checkoutUrl, !isset( $steps['checkout_login'] ) ) );
	}
		
	/**
	 * Step: Customer Details
	 *
	 * @param	array	$data	Wizard data
	 * @return	string
	 */
	public function _customer()
	{
		/* Init */
		$buttonLang = 'continue_to_review';

		if ( $this->invoice->hasPhysicalItems() )
		{
			$buttonLang = 'continue_to_shipping';
		}

		$form = new \IPS\Helpers\Form( 'customer', $buttonLang, $this->invoice->checkoutUrl()->setQueryString( '_step', 'checkout_customer' ) );
		
		/* Account Information */
		if ( $this->needsToLogin and \IPS\Settings::i()->allow_reg )
		{
			$guestData = $this->invoice->guest_data;

			$form->addHeader('account_information');
			if ( \IPS\Settings::i()->nexus_checkreg_usernames )
			{
				$form->add( new \IPS\Helpers\Form\Text( 'username', isset( $guestData['member']['name'] ) ? $guestData['name'] : NULL, TRUE, array( 'accountUsername' => TRUE ) ) );
			}
			$form->add( new \IPS\Helpers\Form\Email( 'email_address', $guestData ? $guestData['member']['email'] : NULL, TRUE, array( 'accountEmail' => TRUE, 'maxLength' => 150 ) ) );
			$form->add( new \IPS\Helpers\Form\Password( 'password', NULL, TRUE, array() ) );
			$form->add( new \IPS\Helpers\Form\Password( 'password_confirm', NULL, TRUE, array( 'confirm' => 'password' ) ) );
			$form->addHeader('billing_information');
		}
		
		/* Billing Information */
		$form->add( new \IPS\Helpers\Form\Text( 'cm_first_name', $this->invoice->member->cm_first_name, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'cm_last_name', $this->invoice->member->cm_last_name, TRUE ) );
		$addresses = \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=?', \IPS\Member::loggedIn()->member_id ) );
		if ( count( $addresses ) )
		{
			$billing = NULL;
			$options = array();
			foreach ( new \IPS\Patterns\ActiveRecordIterator( $addresses, 'IPS\nexus\Customer\Address' ) as $address )
			{
				$options[ $address->id ] = $address->address->toString('<br>');
				if ( ( !$this->invoice->billaddress and $address->primary_billing ) or $this->invoice->billaddress == $address->address )
				{
					$billing = $address->id;
				}
			}
			$options[0] = 'other';
			
			$form->add( new \IPS\Helpers\Form\Radio( 'billing_address', $billing, TRUE, array( 'options' => $options, 'toggles' => array( 0 => array( 'new_billing_address' ) ) ) ) );
			$newAddress = new \IPS\Helpers\Form\Address( 'new_billing_address', !$billing ? $this->invoice->billaddress : NULL, FALSE, array(), NULL, NULL, NULL, 'new_billing_address' );
			$newAddress->label = ' ';
			$form->add( $newAddress );
		}
		else
		{
			$form->add( new \IPS\Helpers\Form\Address( 'new_billing_address', $this->invoice->billaddress, TRUE ) );
		}
		
		/* Customer Fields */
		$customer = \IPS\nexus\Customer::loggedIn();
		foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
		{
			if ( $field->purchase_show )
			{
				$column = $field->column;
				$input = $field->buildHelper( $customer->$column );
				$input->required = $field->purchase_require;
				$input->appearRequired = $field->purchase_require;
				$form->add( $input );
			}
		}
		
		/* Additional Information */
		if ( $this->needsToLogin and \IPS\Settings::i()->allow_reg )
		{
			$customFields = \IPS\core\ProfileFields\Field::fields( $guestData ? $guestData['profileFields'] : NULL, \IPS\core\ProfileFields\Field::REG );
			if ( count( $customFields ) )
			{
				$form->addHeader('additional_information');
				foreach ( $customFields as $group => $fields )
				{
					foreach ( $fields as $field )
					{
						$form->add( $field );
					}
				}
			}
		}
		
		/* Q&A and Captcha */
		if ( $this->needsToLogin and \IPS\Settings::i()->allow_reg )
		{
			$form->addSeparator();
			if ( \IPS\Settings::i()->nexus_checkreg_captcha )
			{
				$question = FALSE;
				try
				{
					$question = \IPS\Db::i()->select( '*', 'core_question_and_answer', NULL, "RAND()" )->first();
				}
				catch ( \UnderflowException $e ) {}
				
				if( $question )
				{
					$form->hiddenValues['q_and_a_id'] = $question['qa_id'];
				
					$form->add( new \IPS\Helpers\Form\Text( 'q_and_a', NULL, TRUE, array(), function( $val )
					{
						$qanda  = intval( \IPS\Request::i()->q_and_a_id );
						$pass = true;
					
						if( $qanda )
						{
							$question = \IPS\Db::i()->select( '*', 'core_question_and_answer', array( 'qa_id=?', $qanda ) )->first();
							$answers = json_decode( $question['qa_answers'] );
				
							if( count( $answers ) )
							{
								$pass = FALSE;
							
								foreach( $answers as $answer )
								{
									$answer = trim( $answer );
				
									if( mb_strlen( $answer ) AND mb_strtolower( $answer ) == mb_strtolower( $val ) )
									{
										$pass = TRUE;
									}
								}
							}
						}
						else
						{
							$questions = \IPS\Db::i()->select( 'count(*)', 'core_question_and_answer', 'qa_id > 0' )->first();
							if( $questions )
							{
								$pass = FALSE;
							}
						}
						
						if( !$pass )
						{
							throw new \DomainException( 'q_and_a_incorrect' );
						}
					} ) );
					
					\IPS\Member::loggedIn()->language()->words['q_and_a'] = \IPS\Member::loggedIn()->language()->addToStack( 'core_question_and_answer_' . $question['qa_id'], FALSE );
				}
			}
			
			if ( !$guestData )
			{
				$captcha = new \IPS\Helpers\Form\Captcha;
				if ( (string) $captcha !== '' )
				{
					$form->add( $captcha );
				}
			}
						
			$form->add( new \IPS\Helpers\Form\Checkbox( 'reg_admin_mails', $guestData ? $guestData['member']['allow_admin_mails'] : TRUE, FALSE ) );
			\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] = sprintf( \IPS\Member::loggedIn()->language()->get("reg_agreed_terms"), \IPS\Http\Url::internal( 'app=core&module=system&controller=terms', 'front', 'terms', array(), \IPS\Http\Url::PROTOCOL_RELATIVE ) );
			
			/* Build the appropriate links for registration terms & privacy policy */
			if ( \IPS\Settings::i()->privacy_type == "internal" )
			{
				\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] .= sprintf( \IPS\Member::loggedIn()->language()->get("reg_privacy_link"), \IPS\Http\Url::internal( 'app=core&module=system&controller=privacy', 'front', 'privacy', array(), \IPS\Http\Url::PROTOCOL_RELATIVE ), 'data-ipsDialog data-ipsDialog-size="wide" data-ipsDialog-title="' . \IPS\Member::loggedIn()->language()->get("privacy") . '"' );
			}
			else if ( \IPS\Settings::i()->privacy_type == "external" )
			{
				\IPS\Member::loggedIn()->language()->words[ "reg_agreed_terms" ] .= sprintf( \IPS\Member::loggedIn()->language()->get("reg_privacy_link"), \IPS\Http\Url::external( \IPS\Settings::i()->privacy_link ), 'target="_blank"' );
			}
			$form->add( new \IPS\Helpers\Form\Checkbox( 'reg_agreed_terms', (bool) $guestData, TRUE, array(), function( $val )
			{
				if ( !$val )
				{
					throw new \InvalidArgumentException('reg_not_agreed_terms');
				}
			} ) );

		}
		
		/* Handle submission */
		if ( $values = $form->values() )
		{
			/* If user is a guest create the member object but don't save it */
			if ( $this->needsToLogin )
			{
				/* It shouldn't be possible to get here */
				if ( !\IPS\Settings::i()->allow_reg )
				{
					\IPS\Output::i()->error( 'reg_disabled', '3X196/A', 403, '' );
				}
				
				/* Set basic details */
				$member = new \IPS\nexus\Customer;
				if ( \IPS\Settings::i()->nexus_checkreg_usernames )
				{
					$member->cm_first_name		= $values['cm_first_name'];
					$member->cm_last_name		= $values['cm_last_name'];
					$member->name				= $values['username'];
				}
				else
				{
					$member->cm_first_name		= $values['cm_first_name'];
					$member->cm_last_name		= $values['cm_last_name'];
					$member->name				= "{$values['cm_first_name']} {$values['cm_last_name']}";
				}
				$member->email				= $values['email_address'];
				$member->members_pass_salt  = $member->generateSalt();
				$member->members_pass_hash  = $member->encryptedPassword( $values['password'] );
				$member->allow_admin_mails  = $values['reg_admin_mails'];
				$member->member_group_id	= \IPS\Settings::i()->member_group;
				
				/* Customer Fields */
				foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
				{
					if ( $field->purchase_show )
					{
						$column = $field->column;
						$member->$column = $values["nexus_ccfield_{$field->id}"];
					}
					
					if ( $field->type === 'Editor' )
					{
						$field->claimAttachments( $member->member_id );
					}
				}
				
				/* Custom Fields */
				$profileFields = array();
				foreach ( \IPS\core\ProfileFields\Field::fields( array(), \IPS\core\ProfileFields\Field::REG ) as $group => $fields )
				{
					foreach ( $fields as $id => $field )
					{
						$profileFields[ "field_{$id}" ] = (string) $values[ $field->name ];
					}
				}
				
				/* Run it through the spam service */
				if( \IPS\Settings::i()->spam_service_enabled )
				{
					if( $member->spamService() == 4 )
					{
						\IPS\Output::i()->error( 'spam_denied_account', '2S129/1', 403, '' );
					}
				}
				
				/* Save on invoice */
				$this->invoice->guest_data = array( 'member' => $member->changed, 'profileFields' => $profileFields );
			}
			/* Otherwise just update the name and details */
			else
			{
				
				$changes = array();
				foreach ( array( 'cm_first_name', 'cm_last_name' ) as $k )
				{
					if ( $values[ $k ] != $this->invoice->member->$k )
					{
						$changes['name'] = $this->invoice->member->cm_name;
						$this->invoice->member->$k = $values[ $k ];
					}
				}
				foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
				{
					if ( $field->purchase_show )
					{
						$column = $field->column;
						if ( $this->invoice->member->$column != $values["nexus_ccfield_{$field->id}"] )
						{
							$changes['other'][] = array( 'name' => 'nexus_ccfield_' . $field->id, 'value' => $field->displayValue( $values["nexus_ccfield_{$field->id}"] ), 'old' => $this->invoice->member->$column );
						}
						$this->invoice->member->$column = $values["nexus_ccfield_{$field->id}"];
					}
				}
				if ( !empty( $changes ) )
				{
					$this->invoice->member->log( 'info', $changes );
				}
				$this->invoice->member->save();
			}
						
			/* Save the billing address */
			if ( count( $addresses ) and $values['billing_address'] )
			{
				$this->invoice->billaddress = \IPS\nexus\Customer\Address::load( $values['billing_address'] )->address;
			}
			else
			{
				if( empty( $values['new_billing_address']->addressLines ) or !$values['new_billing_address']->city or !$values['new_billing_address']->country or ( !$values['new_billing_address']->region and array_key_exists( $values['new_billing_address']->country, \IPS\GeoLocation::$states ) ) or !$values['new_billing_address']->postalCode )
				{
					$form->error = \IPS\Member::loggedIn()->language()->addToStack('billing_address_required');
					return $form;
				}
				
				if ( \IPS\Member::loggedIn()->member_id )
				{
					$address = new \IPS\nexus\Customer\Address;
					$address->member = \IPS\Member::loggedIn();
					$address->address = $values['new_billing_address'];
					$address->primary_billing = !count( $addresses );
					$address->primary_shipping = ( !count( $addresses ) and !$this->invoice->hasPhysicalItems() );
					$address->save();
					
					\IPS\nexus\Customer::loggedIn()->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['new_billing_address'] ) ) );
				}
				
				$this->invoice->billaddress = $values['new_billing_address'];
			}
						
			/* Save and return */
			$this->invoice->save();
			return array();
		}
		
		/* If we're not logged in, and we need an account for this purchase, show the login form */
		$loginForms = NULL;
		$loginError = NULL;
		if ( $this->needsToLogin )
		{
			$login = new \IPS\Login( $this->invoice->checkoutUrl() );
			try
			{
				$member = $login->authenticate();
				if ( $member !== NULL )
				{
					/* Verify it's okay for this member to be buying those items */
					try
					{
						foreach ( $this->invoice->items as $item )
						{
							$item->memberCanPurchase( $member );
						}
					}
					catch ( \DomainException $e )
					{
						\IPS\Output::i()->error( $e->getMessage(), '1X196/B', 403, '' );
					}
					
					/* Log in */
					\IPS\Session::i()->setMember( $member );
					if ( $login->flags['signin_anonymous'] and !\IPS\Settings::i()->disable_anonymous )
					{
						\IPS\Session::i()->setAnon();
					}
					$member->checkLoginKey();
					if ( $login->flags['remember_me'] )
					{
						$expire = new \IPS\DateTime;
						$expire->add( new \DateInterval( 'P7D' ) );
						\IPS\Request::i()->setCookie( 'member_id', $member->member_id, $expire );
						\IPS\Request::i()->setCookie( 'pass_hash', $member->member_login_key, $expire );
					}
					$member->memberSync( 'onLogin', array( $this->invoice->checkoutUrl() ) );
										
					/* Set the invoice owner */
					$this->invoice->member = $member;
					$this->invoice->save();
					
					/* Redirect */
					\IPS\Output::i()->redirect( $this->invoice->checkoutUrl() );
				}
			}
			catch ( \IPS\Login\Exception $e )
			{
				$loginError = $e->getMessage();
			}
			$loginForms = $login->forms();
		}

		/* Display */
		return \IPS\Theme::i()->getTemplate('checkout')->customerInformation( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'checkout', 'nexus' ) ), 'customerInformationForm' ) ), $loginForms, $loginError, $this->invoice );
	}
	
	/**
	 * Step: Select Shipping
	 *
	 * @param	array	$data	Wizard data
	 * @return	\IPS\Helpers\Form
	 */
	public function _shipping()
	{
		/* Init */
		$form = new \IPS\Helpers\Form( 'shipping', 'continue_to_review', $this->invoice->checkoutUrl() );
		$form->attributes['data-controller'] = 'nexus.front.checkout.billingForm';
		$form->attributes['data-new-billing-address-url'] = $this->invoice->checkoutUrl()->setQueryString( 'do', 'addShippingAddress' );
		
		/* Shipping Address field */
		$primaryShipping = NULL;
		$billingAddress = NULL;
		if ( \IPS\Member::loggedIn()->member_id )
		{
			$addresses = \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=?', \IPS\Member::loggedIn()->member_id ) );
			foreach ( new \IPS\Patterns\ActiveRecordIterator( $addresses, 'IPS\nexus\Customer\Address' ) as $address )
			{
				if ( $address->primary_shipping )
				{
					$primaryShipping = $address->id;
				}
				if ( $this->invoice->billaddress == $address->address )
				{
					$billingAddress = $address->id;
				}
			}
			$form->hiddenValues['shipping_address'] = \IPS\nexus\Customer\Address::load( isset( \IPS\Request::i()->shipping_address ) ? \IPS\Request::i()->shipping_address : ( $primaryShipping ?: $billingAddress ) )->id;
			$this->invoice->shipaddress = \IPS\nexus\Customer\Address::load( isset( \IPS\Request::i()->shipping_address ) ? \IPS\Request::i()->shipping_address : ( $primaryShipping ?: $billingAddress ) )->address;
		}
		elseif ( !$this->invoice->shipaddress )
		{
			$this->invoice->shipaddress = $this->invoice->billaddress;
		}
				
		/* Shipping method field */
		if ( isset( \IPS\Request::i()->shipping_address ) )
		{
			/* Save selected address */
			$this->invoice->shipaddress = \IPS\nexus\Customer\Address::load( \IPS\Request::i()->shipping_address )->address;
			$this->invoice->save();
		}
			
		/* Get shipping methods */
		$shipMethods = array();
		foreach ( \IPS\nexus\Shipping\FlatRate::roots() as $rate )
		{
			if ( $rate->isAvailable( $this->invoice->shipaddress, iterator_to_array( $this->invoice->items ), $this->invoice->currency, $this->invoice ) )
			{
				$shipMethods[ $rate->id ] = $rate;
			}
		}
		
		/* Shipping method field */
		$shippingGroups = array();
		$selected = array();
		$shippingAddressErrors = array();
		foreach ( $this->invoice->items as $k => $item )
		{
			if ( $item->physical )
			{				
				if ( $item->shippingMethodIds )
				{
					$availableMethods = ( \IPS\Settings::i()->easypost_api_key and \IPS\Settings::i()->easypost_show_rates ) ? array_intersect( $item->shippingMethodIds, array_merge( array_keys( $shipMethods ), array( 'easypost' ) ) ) : array_intersect( $item->shippingMethodIds, array_keys( $shipMethods ) );
				}
				else
				{
					$availableMethods = ( \IPS\Settings::i()->easypost_api_key and \IPS\Settings::i()->easypost_show_rates ) ? array_merge( array_keys( $shipMethods ), array( 'easypost' ) ) : array_keys( $shipMethods );
				}
				if ( empty( $availableMethods ) )
				{
					$shippingAddressErrors[] = \IPS\Member::loggedIn()->language()->addToStack( 'checkout_no_ship', FALSE, array( 'sprintf' => array( $item->name ) ) );
				}
				sort( $availableMethods );
				$key = md5( json_encode( $availableMethods ) );
				
				if ( !isset( $shippingGroups[ $key ] ) )
				{
					$shippingGroups[ $key ] = array( 'items' => array(), 'methods' => array() );
					foreach ( $availableMethods as $v )
					{
						$shippingGroups[ $key ]['methods'][ $v ] = ( $v === 'easypost' ? NULL : $shipMethods[ $v ] );
					}
				}
				$shippingGroups[ $key ]['items'][ $k ] = $item;
				
				if ( isset( $item->chosenShippingMethodId ) and $item->chosenShippingMethodId )
				{
					$selected[ $key ] = $item->chosenShippingMethodId;
				}
			}
		}
		if ( \IPS\Settings::i()->easypost_api_key and \IPS\Settings::i()->easypost_show_rates )
		{			
			foreach ( $shippingGroups as $key => $data )
			{
				if ( array_key_exists( 'easypost', $data['methods'] ) )
				{
					unset( $shippingGroups[ $key ]['methods']['easypost'] );
					
					$lengthInInches = 0;
					$widthInInches = 0;
					$heightInInches = 0;
					$weightInOz = 0;
					foreach ( $data['items'] as $item )
					{
						$weightInOz += ( $item->weight->float('oz') * $item->quantity );
						$heightInInches += ( $item->height->float('in') * $item->quantity );

						foreach ( array( 'length', 'width' ) as $k )
						{
							$v = "{$k}InInches";
							if ( $item->$k->float('in') > $$v )
							{
								$$v = $item->$k->float('in');
							}
						}
					}

					try
					{
						$easyPost = \IPS\nexus\Shipping\EasyPostRate::getRates( $lengthInInches, $widthInInches, $heightInInches, $weightInOz, $this->invoice->member, $this->invoice->shipaddress, $this->invoice->currency );
						if ( isset( $easyPost['rates'] ) )
						{
							foreach ( $easyPost['rates'] as $rate )
							{
								if ( $rate['currency'] === $this->invoice->currency )
								{
									$shippingGroups[ $key ]['methods'][ $rate['service'] ] = new \IPS\nexus\Shipping\EasyPostRate( $rate );
								}
							}
						}
					}
					catch ( \IPS\Http\Request\Exception $e ) { }

					
					if ( !count( $shippingGroups[ $key ]['methods'] ) )
					{
						\IPS\Output::i()->error( 'err_no_shipping_methods', '4X196/6', 403, 'err_no_shipping_methods_admin' );
					}					
				}
			}
		}
		$form->add( new \IPS\nexus\Form\Shipping( 'shipping_method', count( $selected ) ? $selected : NULL, TRUE, array( 'options' => $shippingGroups, 'currency' => $this->invoice->currency, 'invoice' => $this->invoice ) ) );
		
		/* Submissions */
		if ( $values = $form->values() )
		{
			/* Save new shipping address */
			if ( !$this->invoice->shipaddress and !$form->hiddenValues['shipping_address'] )
			{
				$this->addShippingAddress();
				return \IPS\Output::i()->output;
			}
			elseif ( !isset( $values['shipping_method'] ) )
			{
				\IPS\Output::i()->redirect( $this->invoice->checkoutUrl()->setQueryString( 'shipping_address', $form->hiddenValues['shipping_address'] ) );
			}
			
			/* Remove any existing shipping charges on the invoice */
			foreach ( $this->invoice->items as $k => $v )
			{
				if ( $v instanceof \IPS\nexus\extensions\nexus\Item\ ShippingCharge )
				{
					$this->invoice->removeItem( $k );
				}
			}
			
			/* Loop chosen methods */
			foreach ( $values['shipping_method'] as $key => $method )
			{
				/* Set that we've chosen that method for those items */
				foreach ( $shippingGroups[ $key ]['items'] as $k => $item )
				{
					$this->invoice->changeItem( $k, array( 'chosen_shipping' => $method ) );
				}
				
				/* Add the charge to the invoice */
				$_method = $shippingGroups[ $key ]['methods'][ $method ];
				$charge = new \IPS\nexus\extensions\nexus\Item\ShippingCharge( $_method->getName(), $_method->getPrice( $shippingGroups[ $key ]['items'], $this->invoice->currency, $this->invoice ) );
				$charge->id = $method;
				$charge->tax = $_method->getTax();
				$shippingItems[] = $charge;
			}
			
			/* Save */
			foreach ( $shippingItems as $s )
			{
				$this->invoice->addItem( $s );
			}
			$this->invoice->save();
			
			/* Continue */
			return array();
		}
		
		/* Display */
		return \IPS\Theme::i()->getTemplate( 'checkout', 'nexus' )->checkoutShipping( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'checkout', 'nexus' ) ), 'checkoutShippingForm' ), $this->invoice->shipaddress, $shippingAddressErrors ), $this->invoice );
	}
	
	/**
	 * Add shipping address
	 *
	 * @return	\IPS\Helpers\Form
	 */
	protected function addShippingAddress()
	{
		if ( !$this->invoice )
		{
			try
			{
				$this->invoice = \IPS\nexus\Invoice::loadAndCheckPerms( \IPS\Request::i()->id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'no_module_permission', '2X196/7', 403, '' );
			}
		}
		
		$form = new \IPS\Helpers\Form( 'new_shipping_address', 'continue', $this->invoice->checkoutUrl()->setQueryString( 'do', 'addShippingAddress' ) );

		/* Shipping Address field */
		if ( \IPS\Member::loggedIn()->member_id )
		{
			$addresses = \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=?', \IPS\Member::loggedIn()->member_id ) );
			$options = array();
			$primaryShipping = NULL;
			$billingAddress = NULL;
			foreach ( new \IPS\Patterns\ActiveRecordIterator( $addresses, 'IPS\nexus\Customer\Address' ) as $address )
			{
				$options[ $address->id ] = $address->address->toString('<br>');
				if ( $address->primary_shipping )
				{
					$primaryShipping = $address->id;
				}
				if ( $this->invoice->billaddress == $address->address )
				{
					$billingAddress = $address->id;
				}
			}
			$options[0] = 'other';
			$form->add( new \IPS\Helpers\Form\Radio( 'shipping_address', $primaryShipping ?: $billingAddress, TRUE, array( 'options' => $options, 'disabled' => ( isset( \IPS\Request::i()->shipping_address ) AND !isset( \IPS\Request::i()->new_shipping_address_submitted ) ) ), function( $val )
			{
				if ( $val )
				{
					return static::_shippingAddressValidation( \IPS\nexus\Customer\Address::load( $val )->address );
				}
			} ) );
		}
		$form->add( new \IPS\Helpers\Form\Address( 'new_shipping_address', NULL, FALSE, array(), function( $val )
		{
			if ( $val )
			{
				return static::_shippingAddressValidation( $val );
			}
		}, NULL, NULL, 'new_shipping_address' ) );
		
		if ( $values = $form->values() )
		{
			if ( \IPS\Member::loggedIn()->member_id )
			{
				$addressId = $values['shipping_address'];
	
				if ( intval( $values['shipping_address'] ) === 0 )
				{
					$address = new \IPS\nexus\Customer\Address;
					$address->member = \IPS\Member::loggedIn();
					$address->address = $values['new_shipping_address'];
					$address->save();	
	
					$addressId = $address->id;
					
					\IPS\nexus\Customer::loggedIn()->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['shipping_address'] ) ) );
				}
							
				\IPS\Output::i()->redirect( $this->invoice->checkoutUrl()->setQueryString( 'shipping_address', $addressId )->setQueryString( '_step', 'checkout_shipping' ) );
			}
			else
			{
				$this->invoice->shipaddress = $values['new_shipping_address'];
				$this->invoice->save();
				\IPS\Output::i()->redirect( $this->invoice->checkoutUrl() );
			}
		}
		
		\IPS\Output::i()->output = \IPS\Member::loggedIn()->member_id ? $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'checkout', 'nexus', 'front' ) ), 'changeShippingAddressForm' ) ) : $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Shipping Address Validation
	 *
	 * @param	\IPS\Geolocation	$address	The address
	 * @return	void
	 * @throws	\DomainException
	 */
	protected static function _shippingAddressValidation( \IPS\GeoLocation $address )
	{
		if ( \IPS\Settings::i()->easypost_api_key and $address->country === 'US' )
		{
			$phone = NULL;
			foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
			{
				if ( $field->type === 'Tel' )
				{
					$fieldId = 'nexus_ccfield_' . $field->id;
					$phone = \IPS\Request::i()->$fieldId;
					
					if ( $field->column === 'cm_phone' )
					{
						break;
					}
				}
			}
			
			$response = \IPS\Http\Url::external( 'https://api.easypost.com/v2/addresses' )->request()->login( \IPS\Settings::i()->easypost_api_key, '' )->post( array( 'address' => array(
				'street1'	=> array_shift( $address->addressLines ),
				'street2'	=> count( $address->addressLines ) ? implode( ', ', $address->addressLines ) : NULL,
				'city'		=> $address->city,
				'state'		=> $address->region,
				'zip'		=> $address->postalCode,
				'country'	=> $address->country,
				'name'		=> \IPS\Request::i()->cm_first_name . ' ' . \IPS\Request::i()->cm_last_name,
				'phone'		=> $phone,
				'email'		=> \IPS\Member::loggedIn()->email
			) ) )->decodeJson();
			
			$response = \IPS\Http\Url::external( "https://api.easypost.com/v2/addresses/{$response['id']}/verify" )->request()->login( \IPS\Settings::i()->easypost_api_key, '' )->get();
			if ( $response->httpResponseCode != 200 )
			{
				throw new \DomainException('address_couldnt_validate');
			}
		}
		
		return NULL;
	}
	
	/**
	 * Step: Select Payment Method
	 *
	 * @param	array	$data	Wizard data
	 * @return	string
	 */
	public function _pay( $data )
	{
		/* How much are we paying? */
		$this->invoice->recalculateTotal();
		$amountToPay = $this->invoice->amountToPay();
		if ( isset( \IPS\Request::i()->split ) )
		{
			$split = new \IPS\Math\Number( \IPS\Request::i()->split );
			if ( $amountToPay->amount->compare( $split ) === 1 )
			{
				$amountToPay->amount = $split;
			}
		}
		
		/* Nothing to pay? */
		if ( !$amountToPay->amount->isPositive() )
		{
			\IPS\Output::i()->error( 'err_no_methods', '5X196/8', 500, '' );
		}
		elseif ( $amountToPay->amount->isZero() )
		{
			/* If a guest is checking out, we need to remember that so we can automatically log them in */
			$isGuest = (bool) !$this->invoice->member->member_id;

			/* Mark the invoice paid */
			$extra = $this->invoice->status_extra;
			$extra['type']		= 'zero';
			$this->invoice->status_extra = $extra;
			$this->invoice->markPaid();
			
			/* Redirect */
			$destination = $this->invoice->return_uri ?: $this->invoice->url();
			if ( \IPS\Member::loggedIn()->member_id )
			{
				\IPS\Output::i()->redirect( $destination );
			}
			else
			{
				if ( $isGuest )
				{
					\IPS\Session::i()->setMember( $this->invoice->member );
				}
				
				\IPS\Output::i()->redirect( $destination );
			}
		}
		
		/* Get available payment methods */
		$paymentMethods = array();
		foreach ( \IPS\nexus\Gateway::roots() as $gateway )
		{
			if ( $gateway->checkValidity( $amountToPay, $this->invoice->billaddress ) )
			{
				$paymentMethods[ $gateway->id ] = $gateway;
			}
		}
		
		/* Remove any not supported by items */
		$canUseAccountCredit = TRUE;
		foreach ( $this->invoice->items as $item )
		{
			if ( $item->paymentMethodIds )
			{
				foreach ( $paymentMethods as $k => $v )
				{
					if ( !in_array( $k, $item->paymentMethodIds ) )
					{
						unset( $paymentMethods[ $k ] );
					}
				}				
			}
			
			if ( !$item::$canUseAccountCredit )
			{
				$canUseAccountCredit = FALSE;
			}
		}
		
		/* If we don't have any, show an error */
		if ( count( $paymentMethods ) === 0 )
		{
			\IPS\Output::i()->error( 'err_no_methods', '4X196/3', 500, 'err_no_methods_admin' );
		}
						
		/* Work out recurring payments */
		$recurrings = array();
		foreach ( $this->invoice->items as $item )
		{
			$term = NULL;
			if ( $item instanceof \IPS\nexus\Invoice\Item\Renewal )
			{
				$term = \IPS\nexus\Purchase::load( $item->id )->renewals;
			}
			elseif ( isset( $item->renewalTerm ) and $item->renewalTerm )
			{
				$term = $item->renewalTerm;
			}
			
			if ( $term )
			{
				$format = $term->interval->format('%d/%m/%y') . '/' . $term->cost->currency . '/' . ( $term->tax ? $term->tax->id : '0' );
				if ( isset( $recurrings[ $format ] ) )
				{
					$recurrings[ $format ]['items'][] = $item;
				}
				else
				{
					$recurrings[ $format ] = array( 'items' => array( $item ), 'term' => new \IPS\nexus\Purchase\RenewalTerm( new \IPS\nexus\Money( 0, $term->cost->currency ), $term->interval, $term->tax ) );
				}
				$recurrings[ $format ]['term']->cost->amount = $recurrings[ $format ]['term']->cost->amount->add( $term->cost->amount->multiply( new \IPS\Math\Number( "{$item->quantity}" ) ) );
			}
		}
						
		/* Build form */
		$elements = array();
		$paymentMethodsToggles = array();
		foreach ( $paymentMethods as $gateway )
		{
			foreach ( $gateway->paymentScreen( $this->invoice, $amountToPay, NULL, $recurrings ) as $element )
			{
				if ( !$element->htmlId )
				{
					$element->htmlId = $gateway->id . '-' . $element->name;
				}
				$elements[] = $element;
				$paymentMethodsToggles[ $gateway->id ][] = $element->htmlId;
			}
		}
		$paymentMethodOptions = array();

		foreach ( $paymentMethods as $k => $v )
		{
			$paymentMethodOptions[ $k ] = $v->_title;
		}
		if ( $canUseAccountCredit and \IPS\nexus\Customer::loggedIn()->cm_credits[ $this->invoice->currency ]->amount->isGreaterThanZero() )
		{
			$paymentMethodOptions[0] = \IPS\Member::loggedIn()->language()->addToStack( 'account_credit_with_amount', FALSE, array( 'sprintf' => array( \IPS\nexus\Customer::loggedIn()->cm_credits[ $this->invoice->currency ] ) ) );
		}
		
		$form = new \IPS\Helpers\Form( 'select_method', 'checkout_pay', $this->invoice->checkoutUrl() );
		if ( isset( \IPS\Request::i()->split ) )
		{
			$form->hiddenValues['split'] = $amountToPay->amountAsString();
		}
		$form->class = 'ipsForm_vertical';
		if ( count( $paymentMethodOptions ) > 1 )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'payment_method', NULL, TRUE, array( 'options' => $paymentMethodOptions, 'toggles' => $paymentMethodsToggles ) ) );
		}
		foreach ( $elements as $element )
		{
			$form->add( $element );
		}
		if ( \IPS\Settings::i()->nexus_tac === 'checkbox' )
		{
			$form->add( new \IPS\Helpers\Form\Checkbox( 'i_agree_to_tac', FALSE, TRUE, array( 'labelHtmlSprintf' => array( "<a href='" . htmlspecialchars( \IPS\Settings::i()->nexus_tac_link, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) . "' target='_blank'>" . \IPS\Member::loggedIn()->language()->addToStack( 'terms_and_conditions' ) . '</a>' ) ), function( $val )
			{
				if ( !$val )
				{
					throw new \DomainException( 'you_must_agree_to_tac' );
				}
			} ) );
		}
		
		/* Error to show? */
		if ( isset( \IPS\Request::i()->err ) )
		{
			$form->error = \IPS\Request::i()->err;
		}
		
		/* Submitted? */
		$values = $form->values();
		if ( $values !== FALSE )
		{
			/* Load gateway */
			$gateway = NULL;
			if ( isset( $values['payment_method'] ) )
			{
				if ( $values['payment_method'] != 0 )
				{
					$gateway = \IPS\nexus\Gateway::load( $values['payment_method'] );
				}
			}
			else
			{
				$gateway = array_pop( $paymentMethods );
			}
						
			/* Do we already have a "waiting" transaction (which means a manual payment, such as by check or bank wire) we don't
				need to create a new one since it'll be exactly the same. We can just take them to the screen for the transaction
				we already have which shows the instructions they need */
			try
			{
				$existingWaitingTransaction = \IPS\Db::i()->select( '*', 'nexus_transactions', array(
					't_member=? AND t_invoice=? AND t_method=? AND t_status=? AND t_amount=? AND t_currency=?',
					\IPS\Member::loggedIn()->member_id,
					$this->invoice->id,
					$gateway->_id,
					\IPS\nexus\Transaction::STATUS_WAITING,
					(string) $amountToPay->amount,
					$amountToPay->currency
				) )->first();

				\IPS\Output::i()->redirect( \IPS\nexus\Transaction::constructFromData( $existingWaitingTransaction )->url() );
			}
			catch ( \UnderflowException $e ) { }

			
			/* Create a transaction */
			$transaction = new \IPS\nexus\Transaction;
			$transaction->member = \IPS\Member::loggedIn();
			$transaction->invoice = $this->invoice;
			$transaction->amount = $amountToPay;
			$transaction->ip = \IPS\Request::i()->ipAddress();
			
			/* Account Credit? */
			if ( $gateway === NULL )
			{
				$credits = \IPS\nexus\Customer::loggedIn()->cm_credits;
				$inWallet = $credits[ $this->invoice->currency ]->amount;
				if ( $transaction->amount->amount->compare( $inWallet ) === 1 )
				{
					$transaction->amount = new \IPS\nexus\Money( $inWallet, $this->invoice->currency );
				}
				$transaction->status = $transaction::STATUS_PAID;
				$transaction->save();
							
				$credits[ $this->invoice->currency ]->amount = $credits[ $this->invoice->currency ]->amount->subtract( $transaction->amount->amount );
				$this->invoice->member->cm_credits = $credits;
				$this->invoice->member->save();
				
				$this->invoice->member->log( 'transaction', array(
					'type'			=> 'paid',
					'status'		=> \IPS\nexus\Transaction::STATUS_PAID,
					'id'			=> $transaction->id,
					'invoice_id'	=> $this->invoice->id,
					'invoice_title'	=> $this->invoice->title,
				) );
				
				$transaction->sendNotification();
				
				if ( !$this->invoice->amountToPay()->amount->isGreaterThanZero() )
				{	
					$this->invoice->markPaid();
				}
				
				\IPS\Output::i()->redirect( $transaction->url() );
			}
			/* Nope - gateway */
			else
			{
				$transaction->method = $gateway;
			}			
						
			/* Create a MaxMind request */
			$maxMind = NULL;
			if ( \IPS\Settings::i()->maxmind_key )
			{
				$maxMind = new \IPS\nexus\Fraud\MaxMind\Request;
				$maxMind->setTransaction( $transaction );
			}
			
			/* Authorize */			
			try
			{
				$transaction->auth = $gateway->auth( $transaction, $values, $maxMind, $recurrings );
			}
			catch ( \LogicException $e )
			{
				$form->error = $e->getMessage();
				return $form;
			}
			catch ( \RuntimeException $e )
			{
				\IPS\Log::log( $e, 'checkout' );
				
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('gateway_err');
				return $form;
			}
						
			/* Check Fraud Rules and capture */
			try
			{
				$transaction->checkFraudRulesAndCapture( $maxMind );
			}
			catch ( \LogicException $e )
			{
				$form->error = $e->getMessage();
				return $form;
			}
			catch ( \RuntimeException $e )
			{
				\IPS\Log::log( $e, 'checkout' );
				
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('gateway_err');
				return $form;
			}			
			
			/* Logged in? */
			if ( !\IPS\Member::loggedIn()->member_id and $this->invoice->member->member_id )
			{
				\IPS\Session::i()->setMember( $this->invoice->member );
			}
			
			/* Send email receipt */
			$transaction->sendNotification();
			
			/* Show thanks screen */
			\IPS\Output::i()->redirect( $transaction->url() );
		}
		
		/* Coupons */
		$couponForm = NULL;
		if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_coupons' )->first() )
		{
			$canUseCoupons = TRUE;
			foreach ( $this->invoice->items as $item )
			{
				if ( !$item::$canUseCoupons )
				{
					$canUseCoupons = FALSE;
					break;
				}
			}
			
			if ( $canUseCoupons )
			{
				$invoice = $this->invoice;
				$couponForm = new \IPS\Helpers\Form( 'coupon', 'save', $this->invoice->checkoutUrl() );
				$couponForm->add( new \IPS\Helpers\Form\Custom( 'coupon_code', NULL, TRUE, array(
					'getHtml'	=> function( $field )
					{
						return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->text( $field->name, 'text', $field->value, $field->required, 25 );
					},
					'formatValue'	=> function( $field ) use ( $invoice )
					{
						if ( $field->value )
						{
							try
							{
								return \IPS\nexus\Coupon::load( $field->value, 'c_code' )->useCoupon( $invoice, \IPS\nexus\Customer::loggedIn() );
							}
							catch ( \OutOfRangeException $e )
							{
								throw new \DomainException('coupon_code_invalid');
							}
						}
					}
				) ) );
				if ( $values = $couponForm->values() )
				{
					$invoice->addItem( $values['coupon_code'] );
					$invoice->save();
					\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
				}
			}
		}
		
		/* Display */
		return \IPS\Theme::i()->getTemplate('checkout')->confirmAndPay( $this->invoice, $this->invoice->summary(), $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'checkout', 'nexus' ) ), 'paymentForm' ), $this->invoice, $amountToPay ), $amountToPay, $couponForm ? $couponForm->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'checkout', 'nexus' ) ), 'couponForm' ) ) : NULL, $recurrings );
	}
	
	/**
	 * Split Payment
	 *
	 * @return	void
	 */
	public function split()
	{
		/* Load invoice */
		try
		{
			$invoice = \IPS\nexus\Invoice::loadAndCheckPerms( \IPS\Request::i()->id );

			$minSplitAmount = $invoice->canSplitPayment();
			if ( $minSplitAmount === FALSE )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X196/4', 404, '' );
		}
		
		/* What is the max? */
		$maxSplitAmount = floatval( (string) ( $invoice->amountToPay()->amount->subtract( new \IPS\Math\Number( "{$minSplitAmount}" ) ) ) );
				
		/* Build Form */
		$form = new \IPS\Helpers\Form( 'split', 'continue' );
		$form->add( new \IPS\Helpers\Form\Number( 'split_payment_amount', 0, TRUE, array( 'min' => $minSplitAmount, 'max' => $maxSplitAmount, 'decimals' => TRUE ), NULL, NULL, $invoice->currency ) );
		
		/* Handle Submissions */
		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( $invoice->checkoutUrl()->setQueryString( 'split', $values['split_payment_amount'] ) );
		}
		
		/* Display */
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * View Transaction Status
	 *
	 * @return	void
	 */
	public function transaction()
	{
		try
		{
			$transaction = \IPS\nexus\Transaction::loadAndCheckPerms( \IPS\Request::i()->t );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X196/5', 404, '' );
		}

		$output = '';
		$checkoutStatus = '';

		switch ( $transaction->status )
		{
			case \IPS\nexus\Transaction::STATUS_PAID:
				$complete = ( $transaction->invoice->status === \IPS\nexus\Invoice::STATUS_PAID );
				$purchases = array();
				$checkoutStatus = 'complete';

				if ( $complete )
				{
					if ( $transaction->invoice->return_uri )
					{
						\IPS\Output::i()->redirect( $transaction->invoice->return_uri );
					}
					else
					{
						$purchases = $transaction->invoice->purchasesCreated();
					}
				}
				else
				{
					$checkoutStatus = 'continue';
				}
				
				$output = \IPS\Theme::i()->getTemplate('checkout')->transactionOkay( $transaction, $complete, $purchases );
				break;
				
			case \IPS\nexus\Transaction::STATUS_WAITING:
				$checkoutStatus = 'waiting';
				$output = \IPS\Theme::i()->getTemplate('checkout')->transactionWait( $transaction );
				break;
				
			case \IPS\nexus\Transaction::STATUS_HELD:
				$checkoutStatus = 'hold';
				$output = \IPS\Theme::i()->getTemplate('checkout')->transactionHold( $transaction );
				break;
				
			case \IPS\nexus\Transaction::STATUS_REFUSED:
				$checkoutStatus = 'refused';
				$output = \IPS\Theme::i()->getTemplate('checkout')->transactionFail( $transaction );
				break;
			
			default:
				\IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl() );
				break;
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('checkout')->checkoutWrapper( $output, $checkoutStatus );
	}	 
}