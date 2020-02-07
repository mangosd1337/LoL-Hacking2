<?php
/**
 * @brief		Domain
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 Aug 2014
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
 * Domain
 */
class _Domain extends \IPS\nexus\Invoice\Item\Purchase
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'domain';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'globe';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'domain';
	
	/**
	 * Generate Invoice Form: First Step
	 *
	 * @param	\IPS\Helpers\Form	$form		The form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( \IPS\Helpers\Form $form, \IPS\nexus\Invoice $invoice )
	{	
		$domainPrices = json_decode( \IPS\Settings::i()->nexus_domain_prices, TRUE );
		$form->add( new \IPS\Helpers\Form\Custom( 'ha_domain_to_buy', NULL, NULL, array(
			'getHtml'	=> function( $field ) use ( $domainPrices, $invoice )
			{
				$prices = array();
				foreach ( $domainPrices as $tld => $_prices )
				{
					$prices[ $tld ] = new \IPS\nexus\Money( $_prices[ $invoice->currency ]['amount'], $invoice->currency );
				}
				
				return \IPS\Theme::i()->getTemplate( 'store', 'nexus', 'front' )->domainBuy( $field, $prices );
			},
			'validate'	=> function( $field )
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
		), NULL, NULL, NULL, 'ha_domain_to_buy' ) );
		
		$form->add( new \IPS\Helpers\Form\Stack( 'server_nameservers', explode( ',', \IPS\Settings::i()->nexus_hosting_nameservers ), TRUE ) );
	}
	
	/**
	 * Create From Form
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	\IPS\nexus\extensions\nexus\Item\Domain
	 */
	public static function createFromForm( array $values, \IPS\nexus\Invoice $invoice )
	{
		$domainPrices = json_decode( \IPS\Settings::i()->nexus_domain_prices, TRUE );
		$cost = new \IPS\nexus\Money( $domainPrices[ $values['ha_domain_to_buy']['tld'] ][ $invoice->currency ]['amount'], $invoice->currency );
		
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
		$domain->tax = $tax;
		$domain->extra = array_merge( $values['ha_domain_to_buy'], array( 'nameservers' => $values['server_nameservers'] ) );
		
		return $domain;
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
		$enom = new \IPS\nexus\DomainRegistrar\Enom( \IPS\Settings::i()->nexus_enom_un, \IPS\Settings::i()->nexus_enom_pw );
		try
		{
			$enom->register( $purchase->extra['sld'], $purchase->extra['tld'], $purchase->extra['nameservers'], $invoice->member, $invoice->billaddress );
		}
		catch ( \Exception $e )
		{
			\IPS\Log::log( $e, 'enom' );
		}
	}
	
	/**
	 * Admin can change expire date / renewal term?
	 *
	 * @param	\IPS\nexus\Purchase	$purchase	The purchase
	 * @return	bool
	 */
	public static function canChangeExpireDate( \IPS\nexus\Purchase $purchase )
	{
		return FALSE;
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
		return \IPS\DateTime::create()->add( new \DateInterval( 'P10Y' ) );
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
		$enom = new \IPS\nexus\DomainRegistrar\Enom( \IPS\Settings::i()->nexus_enom_un, \IPS\Settings::i()->nexus_enom_pw );
		try
		{
			$enom->renew( $purchase->extra['sld'], $purchase->extra['tld'], $cycles );
		}
		catch ( \Exception $e )
		{
			\IPS\Log::log( $e, 'enom' );
		}
	}
}