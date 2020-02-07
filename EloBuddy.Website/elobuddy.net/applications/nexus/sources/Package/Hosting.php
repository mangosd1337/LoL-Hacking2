<?php
/**
 * @brief		Hosting Package
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Hosting Package
 */
class _Hosting extends \IPS\nexus\Package
{
	/**
	 * @brief	Database Table
	 */
	protected static $packageDatabaseTable = 'nexus_packages_hosting';
	
	/**
	 * @brief	Which columns belong to the local table
	 */
	protected static $packageDatabaseColumns = array( 'p_queue', 'p_quota', 'p_ip', 'p_cgi', 'p_frontpage', 'p_hasshell', 'p_maxftp', 'p_maxsql', 'p_maxpop', 'p_maxlst', 'p_maxsub', 'p_maxpark', 'p_maxaddon', 'p_bwlimit' );
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'cloud';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'hosting_account';
	
	/**
	 * Get additional name info
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	array
	 */
	public function getPurchaseNameInfo( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			return array_merge( array( \IPS\nexus\Hosting\Account::load( $purchase->id )->domain ), parent::getPurchaseNameInfo( $purchase ) );
		}
		catch ( \Exception $e )
		{
			return parent::getPurchaseNameInfo( $purchase );
		}
	}
	
	/* !ACP Package Form */
	
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
		$return = array();
		
		if ( !$customEdit ) // The acpEdit method will add these fields
		{
			$return['package_settings']['queue'] = new \IPS\Helpers\Form\Node( 'p_queue', $package->type === 'hosting' ? $package->queue : NULL, NULL, array( 'class' => 'IPS\nexus\Hosting\Queue' ), function( $val )
			{
				if ( !$val and \IPS\Request::i()->p_type == 'hosting' )
				{
					throw new \DomainException('form_required');
				}
			} );
			
			$return['package_settings']['quota'] = new \IPS\Helpers\Form\Number( 'p_quota', $package->type === 'hosting' ? $package->quota : -1, NULL, array( 'unlimited' => -1 ), NULL, NULL, 'MB' );
			$return['package_settings']['bwlimit'] = new \IPS\Helpers\Form\Number( 'p_bwlimit', $package->type === 'hosting' ? $package->bwlimit : -1, NULL, array( 'unlimited' => -1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->get('mb_per_month') );
			$return['package_settings']['maxftp'] = new \IPS\Helpers\Form\Number( 'p_maxftp', $package->type === 'hosting' ? $package->maxftp : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['maxsql'] = new \IPS\Helpers\Form\Number( 'p_maxsql', $package->type === 'hosting' ? $package->maxsql : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['maxpop'] = new \IPS\Helpers\Form\Number( 'p_maxpop', $package->type === 'hosting' ? $package->maxpop : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['maxlst'] = new \IPS\Helpers\Form\Number( 'p_maxlst', $package->type === 'hosting' ? $package->maxlst : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['maxsub'] = new \IPS\Helpers\Form\Number( 'p_maxsub', $package->type === 'hosting' ? $package->maxsub : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['maxpark'] = new \IPS\Helpers\Form\Number( 'p_maxpark', $package->type === 'hosting' ? $package->maxpark : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['maxaddon'] = new \IPS\Helpers\Form\Number( 'p_maxaddon', $package->type === 'hosting' ? $package->maxaddon : -1, FALSE, array( 'unlimited' => -1 ) );
			$return['package_settings']['ip'] = new \IPS\Helpers\Form\YesNo( 'p_ip', $package->type === 'hosting' ? $package->ip : 0 );
			$return['package_settings']['cgi'] = new \IPS\Helpers\Form\YesNo( 'p_cgi', $package->type === 'hosting' ? $package->cgi : 0 );
			$return['package_settings']['frontpage'] = new \IPS\Helpers\Form\YesNo( 'p_frontpage', $package->type === 'hosting' ? $package->frontpage : 0 );
			$return['package_settings']['hasshell'] = new \IPS\Helpers\Form\YesNo( 'p_hasshell', $package->type === 'hosting' ? $package->hasshell : 0 );
		}

		return $return;
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['p_queue'] ) )
		{
			$values['p_queue'] = is_object( $values['p_queue'] ) ? $values['p_queue']->id : $values['p_queue'];
		}
		
		return parent::formatFormValues( $values );
	}
	
	/**
	 * Updateable fields
	 *
	 * @return	array
	 */
	public static function updateableFields()
	{
		return array_merge( parent::updateableFields(), array(
			'quota',
			'bwlimit',
			'hasshell',
			'maxftp',
			'maxsql',
			'maxpop',
			'maxlst',
			'maxsub',
			'maxpark',
			'maxaddon',
		) );
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
		/* Get current values */
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
			
			$current = array(
				'account_username'	=> $account->username,
				'account_domain'	=> $account->domain,
				'account_password'	=> (string) $account->password,
				'p_quota'			=> $account->diskspaceAllowance() !== NULL ? $account->diskspaceAllowance() / 1000000 : -1,
				'p_maxftp'			=> $account->maxFtpAccounts() !== NULL ? $account->maxFtpAccounts() : -1,
				'p_maxsql'			=> $account->maxDatabases() !== NULL ? $account->maxDatabases() : -1,
				'p_maxpop'			=> $account->maxEmailAccounts() !== NULL ? $account->maxEmailAccounts() : -1,
				'p_maxlst'			=> $account->maxMailingLists() !== NULL ? $account->maxMailingLists() : -1,
				'p_maxsub'			=> $account->maxSubdomains() !== NULL ? $account->maxSubdomains() : -1,
				'p_maxpark'			=> $account->maxParkedDomains() !== NULL ? $account->maxParkedDomains() : -1,
				'p_maxaddon'		=> $account->maxAddonDomains() !== NULL ? $account->maxAddonDomains() : -1,
				'p_hasshell'		=> $account->hasSSHAccess(),
				'p_bwlimit'			=> $account->monthlyBandwidthAllowance() !== NULL ? $account->monthlyBandwidthAllowance() / 1000000 : -1,
			);
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
			return parent::updatePurchase( $purchase, $changes );
		}
		catch ( \Exception $e )
		{
			return parent::updatePurchase( $purchase, $changes );
		}
		
		/* Set new values */
		$update = array();
		foreach ( array(
			'quota',
			'bwlimit',
			'hasshell',
			'maxftp',
			'maxsql',
			'maxpop',
			'maxlst',
			'maxsub',
			'maxpark',
			'maxaddon',
		) as $k )
		{
			if ( array_key_exists( $k, $changes ) )
			{
				if ( $current["p_{$k}"] == $changes[ $k ] )
				{
					$update["p_{$k}"] = $this->$k;
				}
			}
		}
		
		/* Send API call */		
		if ( !empty( $update ) )
		{
			try
			{
				$account->edit( array_merge( $current, $update ) );
			}
			catch ( \IPS\nexus\Hosting\Exception $e )
			{
				$e->log();
			}
			catch ( \Exception $e ) {}
		}
		
		/* Call parent */
		return parent::updatePurchase( $purchase, $changes );
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
		/* We need to know now the server this will go on so we have the right nameservers */
		try
		{
			$queue = \IPS\nexus\Hosting\Queue::load( $this->queue );
			$server = $queue->activeServer();
			$form->hiddenValues['server'] = $server->id;
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'hosting_err_public', '4X245/2', 500, 'hosting_err_no_queue' );
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'hosting_err_public', '4X245/3', 500, 'hosting_err_no_server' );
		}
		
		/* What options are available? */
		$domainTypeOptions = array();
		$domainTypeToggles = array();
		$domainPrices = NULL;
		if ( \IPS\Settings::i()->nexus_enom_un and $domainPrices = json_decode( \IPS\Settings::i()->nexus_domain_prices, TRUE ) and count( $domainPrices ) )
		{
			$domainTypeOptions['buy'] = 'ha_domain_buy';
			$domainTypeToggles['buy'] = array( 'ha_domain_to_buy' );
		}
		if ( \IPS\Settings::i()->nexus_hosting_subdomains )
		{
			$domainTypeOptions['sub'] = 'ha_domain_sub';
			$domainTypeToggles['sub'] = array( 'ha_subdomain_to_use' );
		}
		if ( \IPS\Settings::i()->nexus_hosting_allow_own_domain )
		{
			$domainTypeOptions['own'] = 'ha_domain_own';
			$domainTypeToggles['own'] = array( 'ha_domain_to_use' );
		}
		if ( !count( $domainTypeOptions ) )
		{
			\IPS\Output::i()->error( 'hosting_err_public', '4X245/4', 500, 'hosting_err_no_domains' );
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'ha_domain_type', NULL, TRUE, array( 'options' => $domainTypeOptions, 'toggles' => $domainTypeToggles ) ) );
		
		/* Buy Domain */
		if ( array_key_exists( 'buy', $domainTypeOptions ) )
		{
			$form->add( new \IPS\Helpers\Form\Custom( 'ha_domain_to_buy', NULL, NULL, array(
				'getHtml'	=> function( $field ) use ( $domainPrices, $memberCurrency )
				{
					$prices = array();
					foreach ( $domainPrices as $tld => $_prices )
					{
						$prices[ $tld ] = new \IPS\nexus\Money( $_prices[ $memberCurrency ]['amount'], $memberCurrency );
					}
					
					return \IPS\Theme::i()->getTemplate('store')->domainBuy( $field, $prices );
				},
				'validate'	=> function( $field )
				{
					if ( \IPS\Request::i()->ha_domain_type === 'buy' )
					{
						if ( !$field->value['tld'] or !$field->value['sld'] )
						{
							throw new \DomainException('form_required');
						}
						else
						{
							$enom = new \IPS\nexus\DomainRegistrar\Enom( \IPS\Settings::i()->nexus_enom_un, \IPS\Settings::i()->nexus_enom_pw );
							if ( !$enom->check( $field->value['sld'], $field->value['tld'] ) )
							{
								throw new \DomainException('domain_not_available');
							}
						}
					}
				}
			), NULL, NULL, NULL, 'ha_domain_to_buy' ) );
		}
		
		/* Choose Subdomain */
		if ( array_key_exists( 'sub', $domainTypeOptions ) )
		{
			$form->add( new \IPS\Helpers\Form\Custom( 'ha_subdomain_to_use', NULL, NULL, array(
				'getHtml'	=> function( $field )
				{
					return \IPS\Theme::i()->getTemplate('store')->domainSub( $field );
				},
				'validate'	=> function( $field )
				{
					if ( \IPS\Request::i()->ha_domain_type === 'sub' )
					{
						if ( !$field->value['subdomain'] or !$field->value['domain'] )
						{
							throw new \DomainException('form_required');
						}
						else
						{
							if ( mb_strpos( $field->value['subdomain'], '.' ) !== FALSE )
							{
								throw new \DomainException('subdomain_cannot_contain_dot');
							}
							return static::_validateDomain( $field->value['subdomain'] . '.' . $field->value['domain'] );
						}
					}
				}
			), NULL, NULL, NULL, 'ha_subdomain_to_use' ) );
		}
		
		/* Use own domain */
		if ( array_key_exists( 'own', $domainTypeOptions ) )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'ha_domain_to_use', NULL, NULL, array(), function( $value )
			{
				if ( \IPS\Request::i()->ha_domain_type === 'own' )
				{
					if ( !$value )
					{
						throw new \DomainException('form_required');
					}
					else
					{
						return static::_validateDomain( $value );
					}
				}
			}, 'http://', NULL, 'ha_domain_to_use' ) );
			
			\IPS\Member::loggedIn()->language()->words['ha_domain_to_use_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'ha_domain_to_use_ns', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $server->nameservers() ) ) ) );
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
		try
		{
			$server = \IPS\nexus\Hosting\Server::load( \IPS\Request::i()->server );
			if ( !in_array( $this->queue, explode( ',', $server->queues ) ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'generic_error', '3X245/5', 403, '' );
		}
		$item->extra['server'] = $server->id;
				
		switch ( $values['ha_domain_type'] )
		{
			case 'buy':
				$domainPrices = json_decode( \IPS\Settings::i()->nexus_domain_prices, TRUE );
				$cost = new \IPS\nexus\Money( $domainPrices[ $values['ha_domain_to_buy']['tld'] ][ $memberCurrency ]['amount'], $memberCurrency );
				
				$tax = NULL;
				if ( \IPS\Settings::i()->nexus_domain_tax )
				{
					try
					{
						$tax = \IPS\nexus\Tax::load( \IPS\Settings::i()->nexus_domain_tax );
					}
					catch ( \OutOfRangeException $e ) { }
				}
				
				$domain = new \IPS\nexus\extensions\nexus\Item\Domain( $values['ha_domain_to_buy']['sld'] . '.' . $values['ha_domain_to_buy']['tld'], $cost );
				$domain->renewalTerm = new \IPS\nexus\Purchase\RenewalTerm( $cost, new \DateInterval('P1Y'), $tax );
				$domain->paymentMethodIds = $item->paymentMethodIds;
				$domain->tax = $tax;
				$domain->extra = array_merge( $values['ha_domain_to_buy'], array( 'nameservers' => $server->nameservers() ) );
				$item->extra['domain'] = $values['ha_domain_to_buy']['sld'] . '.' . $values['ha_domain_to_buy']['tld'];
				return array( $domain );
			
			case 'sub':
				$item->extra['domain'] = $values['ha_subdomain_to_use']['subdomain'] . '.' . $values['ha_subdomain_to_use']['domain'];
				return array();
				
			case 'own':
				$item->extra['domain'] = $values['ha_domain_to_use'];
				return array();
		}
	}
	
	/**
	 * Validate a domain
	 *
	 * @param	string	$domain	The domain
	 * @return	void
	 * @throws	\DomainException
	 */
	public static function _validateDomain( $domain )
	{
		$domain = mb_strtolower( $domain );				
		$data = @parse_url( 'http://' . $domain );
		if ( $data === FALSE or $data['host'] != $domain or !preg_match( '/^[a-z][a-z0-9-]*\.[a-z0-9-\.]+$/', $domain ) )
		{
			throw new \DomainException('domain_not_valid');
		}
		
		try
		{
			\IPS\Db::i()->select( '*', 'nexus_hosting_accounts', array( 'account_domain=? AND account_exists=1', $domain ) )->first();
			throw new \DomainException('domain_not_available');
		}
		catch ( \UnderflowException $e ) { }
	}
		
	/* !ACP */
	
	/**
	 * ACP Generate Invoice Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @param	string				$k		The key to add to the field names
	 * @return	void
	 */
	public function generateInvoiceForm( \IPS\Helpers\Form $form, $k )
	{
		$class = get_class();
		$field = new \IPS\Helpers\Form\Text( 'ha_domain_to_use' . $k, NULL, NULL, array(), function( $value ) use ( $class )
		{
			if ( !$value )
			{
				throw new \DomainException('form_required');
			}
			else
			{
				return $class::_validateDomain( $value );
			}
		}, 'http://', NULL, 'ha_domain_to_use' );
		$field->label = 'ha_domain_to_use';
		$form->add( $field );
	}
	
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
		try
		{
			$item->extra['server'] = \IPS\nexus\Hosting\Queue::load( $this->queue )->activeServer()->id;
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'generic_error', '3X245/6', 403, '' );
		}		
		$item->extra['domain'] = $values[ 'ha_domain_to_use' . $k ];
	}
	
	/**
	 * Get ACP Page HTML
	 *
	 * @return	string
	 */
	public function acpPage( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
			
			$bandwidthAddons = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_parent=?', 'nexus', 'bandwidth', $purchase->id ) ), 'IPS\nexus\Purchase' );
			
			return \IPS\Theme::i()->getTemplate('purchases')->hosting( $purchase, $account, $bandwidthAddons );
		}
		catch ( \OutOfRangeException $e )
		{
			return parent::acpPage( $purchase );
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			return \IPS\Theme::i()->getTemplate('purchases')->hostingNoConnect( $purchase, $account );
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
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
			if ( $account->exists )
			{	
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_hosting.js', 'nexus', 'admin' ) );
				$form->attributes['data-controller'] = 'nexus.admin.hosting.accountform';
							
				$form->addHeader('hosting_account');
				$form->add( new \IPS\Helpers\Form\Node( 'account_server', $account->server, TRUE, array( 'class' => 'IPS\nexus\Hosting\Server' ) ) );
				$form->add( new \IPS\Helpers\Form\Text( 'account_username', $account->username, TRUE, array(), NULL, NULL, \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) );
				$form->add( new \IPS\Helpers\Form\Text( 'account_password', $account->password, TRUE, array(), NULL, NULL, \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) );
				$form->add( new \IPS\Helpers\Form\Text( 'account_domain', $account->domain, TRUE, array(), NULL, NULL, \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_quota', ( $account->diskspaceAllowance() !== NULL ? $account->diskspaceAllowance() / 1000000 : -1 ), TRUE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ), NULL, NULL, 'MB' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_bwlimit', ( $account->monthlyBandwidthAllowance() !== NULL ? $account->monthlyBandwidthAllowance() / 1000000 : -1 ), TRUE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ), NULL, NULL, \IPS\Member::loggedIn()->language()->get('mb_per_month') ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxftp', ( $account->maxFtpAccounts() !== NULL ? $account->maxFtpAccounts() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxsql', ( $account->maxDatabases() !== NULL ? $account->maxDatabases() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxpop', ( $account->maxEmailAccounts() !== NULL ? $account->maxEmailAccounts() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxlst', ( $account->maxMailingLists() !== NULL ? $account->maxMailingLists() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxsub', ( $account->maxSubdomains() !== NULL ? $account->maxSubdomains() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxpark', ( $account->maxParkedDomains() !== NULL ? $account->maxParkedDomains() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_maxaddon', ( $account->maxAddonDomains() !== NULL ? $account->maxAddonDomains() : -1 ), FALSE, array( 'unlimited' => -1, 'endSuffix' => \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) ) );
				$form->add( new \IPS\Helpers\Form\YesNo( 'p_hasshell', $account->hasSSHAccess(), FALSE, array(), NULL, NULL, \IPS\Theme::i()->getTemplate('hosting')->accountEditWarning() ) );
				$form->add( new \IPS\Helpers\Form\Custom( 'do_not_update_server', FALSE, FALSE, array(
					'rowHtml'	=> function()
					{
						return \IPS\Theme::i()->getTemplate('hosting')->accountEditToggle();
					}
				) ) );
				
				if ( count( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_parent=?', 'nexus', 'bandwidth', $purchase->id ) ) ) )
				{
					\IPS\Member::loggedIn()->language()->words['p_bwlimit_warning'] = \IPS\Member::loggedIn()->language()->addToStack('bandwidth_edit_warn');
				}
			}
		}
		catch ( \Exception $e ) { }
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
		try
		{
			/* Update server if necessary */
			$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
			if ( $values['account_server']->id != $account->server->id )
			{
				$account->server = $values['account_server'];
				$account->save();
			}
			
			/* Send API calls */
			if ( $account->exists and !$values['do_not_update_server'] )
			{
				try
				{
					$account->edit( $values );
				}
				catch ( \IPS\nexus\Hosting\Exception $e )
				{
					\IPS\Output::i()->error( '', '3X245/1', 503, '' );
				}
			}
			
			/* Update local database */
			$account->username = $values['account_username'];
			$account->password = $values['account_password'];
			$account->domain = $values['account_domain'];
			$account->save();
		}
		catch ( \Exception $e ) { }
			
		parent::save( $purchase, $values );
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
			case 'bandwidth':
				$bandwidthOptions = json_decode( \IPS\Settings::i()->nexus_hosting_bandwidth, TRUE );
				
				$form = new \IPS\Helpers\Form('bwinvoice', 'send');
				$form->addMessage( \IPS\Member::loggedIn()->language()->addToStack( 'admin_bandwidth_invoice_info', FALSE, array( 'sprintf' => $purchase->acpUrl()->setQueryString('do', 'edit') ) ), 'ipsMessage ipsMessage_info', FALSE );
				$options = array();
				foreach ( $bandwidthOptions as $amount => $prices )
				{
					if ( $purchase->renewal_currency and isset( $prices[ $purchase->renewal_currency ] ) )
					{
						$currency = $purchase->renewal_currency;
					}
					elseif ( $memberDefaultCurrency = $purchase->member->defaultCurrency() and isset( $prices[ $memberDefaultCurrency ] ) )
					{
						$currency = $memberDefaultCurrency;
					}
					else
					{
						foreach ( $prices as $currency => $value ) break; // That looks weird but it's right
					}
					
					$options[ $amount ] = \IPS\Member::loggedIn()->language()->addToStack( 'bandwidth_purchase_option_admin', FALSE, array( 'sprintf' => array( \IPS\Output\Plugin\Filesize::humanReadableFilesize( $amount * 1000000, TRUE ), (string) new \IPS\nexus\Money( $prices[ $currency ]['amount'], $currency ) ) ) );
				}
				$options['x'] = 'other';
				$form->add( new \IPS\Helpers\Form\Radio( 'bandwidth_to_buy', NULL, TRUE, array( 'options' => $options, 'toggles' => array( 'x' => array( 'bandwidth_to_buy_amount', 'p_base_price', 'bandwidth_expire' ) ) ) ) );
				$form->add( new \IPS\Helpers\Form\Number( 'bandwidth_to_buy_amount', 0, NULL, array(), NULL, NULL, 'MB', 'bandwidth_to_buy_amount' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'p_base_price', NULL, NULL, array( 'decimals' => TRUE ), NULL, NULL, $purchase->renewal_currency ?: $purchase->member->defaultCurrency(), 'p_base_price' ) );
				$form->add( new \IPS\Helpers\Form\Date( 'bandwidth_expire', \IPS\DateTime::create()->add( new \DateInterval('P1M') ), NULL, array(), NULL, NULL, NULL, 'bandwidth_expire' ) );
				
				if ( $values = $form->values() )
				{
					if ( $values['bandwidth_to_buy'] === 'x' )
					{
						$item = new \IPS\nexus\extensions\nexus\Item\Bandwidth(
							sprintf( $purchase->member->language()->get('bandwidth_purchase_name'), \IPS\Output\Plugin\Filesize::humanReadableFilesize( $values['bandwidth_to_buy_amount'] * 1000000, TRUE ) ),
							new \IPS\nexus\Money( $values['p_base_price'], $purchase->renewal_currency ?: $purchase->member->defaultCurrency() )
						);
						$item->parent = $purchase;
						$item->expireDate = $values['bandwidth_expire'];
						$item->extra['bwAmount'] = $values['bandwidth_to_buy_amount'];
					}
					else
					{
						if ( $purchase->renewal_currency and isset( $bandwidthOptions[ $values['bandwidth_to_buy'] ][ $purchase->renewal_currency ] ) )
						{
							$currency = $purchase->renewal_currency;
						}
						elseif ( $memberDefaultCurrency = $purchase->member->defaultCurrency() and isset( $bandwidthOptions[ $values['bandwidth_to_buy'] ][ $memberDefaultCurrency ] ) )
						{
							$currency = $memberDefaultCurrency;
						}
						else
						{
							foreach ( $bandwidthOptions[ $values['bandwidth_to_buy'] ] as $currency => $value ) break; // That looks weird but it's right
						}
						
						$item = new \IPS\nexus\extensions\nexus\Item\Bandwidth(
							sprintf( $purchase->member->language()->get('bandwidth_purchase_name'), \IPS\Output\Plugin\Filesize::humanReadableFilesize( $values['bandwidth_to_buy'] * 1000000, TRUE ) ),
							new \IPS\nexus\Money( $bandwidthOptions[ $values['bandwidth_to_buy'] ][ $currency ]['amount'], $currency )
						);
						$item->parent = $purchase;
						$item->expireDate = \IPS\DateTime::create()->add( new \DateInterval('P1M') );
						$item->extra['bwAmount'] = $values['bandwidth_to_buy'];
					}
										
					$invoice = new \IPS\nexus\Invoice;
					$invoice->member = $purchase->member;
					$invoice->addItem( $item );
					$invoice->return_uri = "app=nexus&module=clients&controller=purchases&do=view&id={$purchase->id}";
					$invoice->save();
					$invoice->sendNotification();
					
					\IPS\Output::i()->redirect( $invoice->acpUrl() );
				}
				
				return (string) $form;
			break;
			
			default:
				return parent::acpAction( $purchase );
			break;
		}
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
		$parent = parent::clientAreaPage( $purchase );
		
		return array(
			'packageInfo'	=> $parent['packageInfo'],
			'purchaseInfo'	=> $parent['purchaseInfo'] . $this->acpPage( $purchase ) /* Although we're calling acpPage, it will use the front-end templates */,
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
			case 'changepass':
				$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
				$form = new \IPS\Helpers\Form;
				$form->add( new \IPS\Helpers\Form\Password( 'new_password' ) );
				if ( $values = $form->values() )
				{
					try
					{
						$account->changePassword( $values['new_password'] );
					}
					catch ( \IPS\nexus\Hosting\Exception $e )
					{
						\IPS\Output::i()->error( 'generic_error', '4X245/7', 500, $e->getMessage() );
					}
					$account->password = $values['new_password'];
					$account->save();
					return;
				}
				return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
			
			case 'bandwidth':
				$bandwidthOptions = json_decode( \IPS\Settings::i()->nexus_hosting_bandwidth, TRUE );
				
				$form = new \IPS\Helpers\Form;
				$options = array();
				foreach ( $bandwidthOptions as $amount => $prices )
				{
					if ( $purchase->renewal_currency and isset( $prices[ $purchase->renewal_currency ] ) )
					{
						$currency = $purchase->renewal_currency;
					}
					elseif ( $memberDefaultCurrency = $purchase->member->defaultCurrency() and isset( $prices[ $memberDefaultCurrency ] ) )
					{
						$currency = $memberDefaultCurrency;
					}
					else
					{
						foreach ( $prices as $currency => $value ) break; // That looks weird but it's right
					}
					
					$options[ $amount ] = \IPS\Member::loggedIn()->language()->addToStack( 'bandwidth_purchase_option', FALSE, array( 'sprintf' => array( \IPS\Output\Plugin\Filesize::humanReadableFilesize( $amount * 1000000, TRUE ), (string) new \IPS\nexus\Money( $prices[ $currency ]['amount'], $currency ) ) ) );
				}
				$form->add( new \IPS\Helpers\Form\Radio( 'bandwidth_to_buy', NULL, TRUE, array( 'options' => $options ) ) );
				
				if ( $values = $form->values() )
				{
					if ( $purchase->renewal_currency and isset( $bandwidthOptions[ $values['bandwidth_to_buy'] ][ $purchase->renewal_currency ] ) )
					{
						$currency = $purchase->renewal_currency;
					}
					elseif ( $memberDefaultCurrency = $purchase->member->defaultCurrency() and isset( $bandwidthOptions[ $values['bandwidth_to_buy'] ][ $memberDefaultCurrency ] ) )
					{
						$currency = $memberDefaultCurrency;
					}
					else
					{
						foreach ( $bandwidthOptions[ $values['bandwidth_to_buy'] ] as $currency => $value ) break; // That looks weird but it's right
					}
					
					$item = new \IPS\nexus\extensions\nexus\Item\Bandwidth(
						sprintf( $purchase->member->language()->get('bandwidth_purchase_name'), \IPS\Output\Plugin\Filesize::humanReadableFilesize( $values['bandwidth_to_buy'] * 1000000, TRUE ) ),
						new \IPS\nexus\Money( $bandwidthOptions[ $values['bandwidth_to_buy'] ][ $currency ]['amount'], $currency )
					);
					$item->parent = $purchase;
					$item->expireDate = \IPS\DateTime::create()->add( new \DateInterval('P1M') );
					$item->extra['bwAmount'] = $values['bandwidth_to_buy'];
					
					$invoice = new \IPS\nexus\Invoice;
					$invoice->member = \IPS\nexus\Customer::loggedIn();
					$invoice->addItem( $item );
					$invoice->return_uri = "app=nexus&module=clients&controller=purchases&do=view&id={$purchase->id}";
					
					\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
					return;
				}
				
				return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
				
			default:
				return parent::clientAreaAction( $purchase );
		}
	}
	
	/* !Actions */
	
	/**
	 * On Purchase Generated
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public function onPurchaseGenerated( \IPS\nexus\Purchase $purchase, \IPS\nexus\Invoice $invoice )
	{
		/* Get Server */
		try
		{
			$server = \IPS\nexus\Hosting\Server::load( $purchase->extra['server'] );
			if ( !in_array( $this->queue, explode( ',', $server->queues ) ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			try
			{
				$server = \IPS\nexus\Hosting\Queue::load( $this->queue )->activeServer();
			}
			catch ( \Exception $e )
			{
				return;
			}
		}
		
		/* Create Account */
		try
		{
			/* Create a username */
			$username = mb_substr( preg_replace("/[^a-z\s]/", '', mb_strtolower( $purchase->extra['domain'] ) ), 0, 8 );
			while ( mb_strlen( $username ) < 8 )
			{
				$username .= chr( rand( 97, 122 ) );
			}
			
			/* If it already exists, change characters until we get one that doesn't */
			do
			{
				$select = \IPS\Db::i()->select( '*', 'nexus_hosting_accounts', array( 'account_username=? AND account_server=?', $username, $server->id ) );			
				if ( count( $select ) or !$server->checkUsername( $username ) )
				{
					$charToChange = rand( 0, 8 - 1 );
					$username[ $charToChange ] = chr( rand( 97, 122 ) );
				}
			}
			while ( count( $select ) or !$server->checkUsername( $username ) );
			
			/* Create the account */
			$class = 'IPS\nexus\Hosting\\' . ucfirst( $server->type ) . '\Account';
			$account = new $class;
			$account->ps_id = $purchase->id;
			$account->server = $server;
			$account->domain = $purchase->extra['domain'];
			$account->username = $username;
			$account->create( $this, $invoice->member );
			$account->save();
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			/* If it fails - try a different server */
			try
			{
				$account->server = \IPS\nexus\Hosting\Queue::load( $this->queue )->activeServer();
				$account->create( $this, $invoice->member );
			}
			/* If it failed, log the original error */
			catch ( \Exception $f )
			{
				$e->log();
			}
		}
	}
		
	/**
	 * On Purchase Expired
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onExpire( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			if ( \IPS\Settings::i()->nexus_hosting_terminate )
			{
				\IPS\nexus\Hosting\Account::load( $purchase->id )->suspend();
			}
			else
			{
				$purchase->cancelled = TRUE;
				$purchase->can_reactivate = FALSE;
				$purchase->save();
			}
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
		}
	}
	
	/**
	 * On Purchase Reactivated (renewed after being expired or reactivated after being canceled)
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onReactivate( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			\IPS\nexus\Hosting\Account::load( $purchase->id )->unsuspend();
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
		}
	}
	
	/**
	 * On Purchase Canceled
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onCancel( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
			$account->terminate();
			$account->exists = FALSE;
			$account->save();
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
		}
	}
	
	/**
	 * On Purchase Deleted
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	void
	 */
	public function onDelete( \IPS\nexus\Purchase $purchase )
	{
		try
		{
			$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
			if ( $account->exists )
			{
				$account->terminate();
			}
			$account->delete();
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			$e->log();
		}
		catch ( \OutOfRangeException $e ) { }
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
		$account = \IPS\nexus\Hosting\Account::load( $purchase->id );
		
		$update = array();
		foreach ( array( 'quota' => 'diskspaceAllowance', 'bwlimit' => 'monthlyBandwidthAllowance', 'maxftp' => 'maxFtpAccounts', 'maxsql' => 'maxDatabases', 'maxpop' => 'maxEmailAccounts', 'maxlst' => 'maxMailingLists', 'maxsub' => 'maxSubdomains', 'maxpark' => 'maxParkedDomains', 'maxaddon' => 'maxAddonDomains', 'hasshell' => 'hasSSHAccess' ) as $k => $method )
		{
			$val = call_user_func( array( $account, $method ) );
			if ( ( $val === NULL and $this->$k == -1 ) or $val == $this->$k )
			{
				$update[ $k ] = $newPackage->$k;
			}
		}
		
		if ( !empty( $update ) )
		{
			$account->edit( $update );
		}
		
		parent::onChange( $purchase, $newPackage, $chosenRenewalOption );
	}
}