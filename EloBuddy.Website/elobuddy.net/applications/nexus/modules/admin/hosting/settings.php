<?php
/**
 * @brief		Hosting Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\hosting;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Hosting Settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init */
		$form = new \IPS\Helpers\Form;
		
		/* Account Management */
		$form->addTab('hosting_account_management');
		$form->add( new \IPS\Helpers\Form\Number( 'nexus_hosting_terminate', \IPS\Settings::i()->nexus_hosting_terminate, TRUE, array( 'unlimited' => -1, 'unlimitedLang' => 'forever' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days') ) );
		
		/* Domains */		
		$form->addTab('hosting_settings_domains');
		$form->addHeader('domain_options');
		$optionsVal = array();
		$domainPrices = array();
		if ( \IPS\Settings::i()->nexus_enom_un and $domainPrices = json_decode( \IPS\Settings::i()->nexus_domain_prices, TRUE ) and count( $domainPrices ) )
		{
			$optionsVal[] = 'enom';
		}
		if ( \IPS\Settings::i()->nexus_hosting_subdomains )
		{
			$optionsVal[] = 'sub';
		}
		if ( \IPS\Settings::i()->nexus_hosting_allow_own_domain )
		{
			$optionsVal[] = 'own';
		}
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'domain_options', $optionsVal, FALSE, array(
			'options'	=> array(
				'enom'	=> 'domain_options_enom',
				'sub'	=>'domain_options_sub',
				'own'	=> 'domain_options_own'
			),
			'toggles'	=> array(
				'enom'	=> array( 'form_header_nexus_domain_prices', 'nexus_domain_prices', 'nexus_domain_tax' ),
				'sub'	=> array( 'form_header_nexus_hosting_sub', 'nexus_hosting_subdomains' ),
				'own'	=> array()
			),
			'disabled'	=> \IPS\Settings::i()->nexus_enom_un ? array() : array( 'enom' )
		) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'nexus_hosting_nameservers', explode( ',', \IPS\Settings::i()->nexus_hosting_nameservers ), FALSE, array(), NULL, NULL, NULL, 'nexus_hosting_nameservers' ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'nexus_hosting_subdomains', explode( ',', \IPS\Settings::i()->nexus_hosting_subdomains ), FALSE, array(), NULL, NULL, NULL, 'nexus_hosting_subdomains' ) );
		$form->addHeader('nexus_domain_prices');
		$matrix = new \IPS\Helpers\Form\Matrix;
		$matrix->columns = array(
			'domain_tld'	=> array( 'Text' ),
			'domain_price'	=> function( $key, $value, $data )
			{
				return new \IPS\nexus\Form\Money( $key, $value, FALSE, array() );
			},
		);
		if ( count( $domainPrices ) )
		{
			foreach ( $domainPrices as $tld => $prices )
			{
				$matrix->rows[] = array( 'domain_tld' => $tld, 'domain_price' => $prices );
			}
		}
		else
		{
			$matrix->rows = array( array( 'domain_tld' => 'com', 'domain_price' => NULL ) );
		}
		$form->addMatrix( 'nexus_domain_prices', $matrix );
		$form->add( new \IPS\Helpers\Form\Node( 'nexus_domain_tax', \IPS\Settings::i()->nexus_domain_tax, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ), NULL, NULL, NULL, 'nexus_domain_tax' ) );
		
		/* Bandwidth */
		$form->addTab('nexus_hosting_bandwidth');
		$form->addMessage( 'nexus_hosting_bandwidth_blurb' );
		$bandwidthMatrix = new \IPS\Helpers\Form\Matrix;
		$bandwidthMatrix->columns = array(
			'bandwidth_amount'	=> array( 'Number' ),
			'bandwidth_price'	=> function( $key, $value, $data )
			{
				return new \IPS\nexus\Form\Money( $key, $value, FALSE, array() );
			},
		);
		$bandwidthPrices = json_decode( \IPS\Settings::i()->nexus_hosting_bandwidth, TRUE );
		if ( count( $bandwidthPrices ) )
		{
			foreach ( $bandwidthPrices as $amount => $prices )
			{
				$bandwidthMatrix->rows[] = array( 'bandwidth_amount' => $amount, 'bandwidth_price' => $prices );
			}
		}
		else
		{
			$bandwidthMatrix->rows = array();
		}
		$form->addMatrix( 'nexus_hosting_bandwidth', $bandwidthMatrix );
		
		/* Handle Submissions */
		if ( $values = $form->values() )
		{
			$domainPrices = array();
			if ( in_array( 'enom', $values['domain_options'] ) )
			{
				foreach ( $values['nexus_domain_prices'] as $option )
				{
					if ( $option['domain_tld'] )
					{
						$domainPrices[ $option['domain_tld'] ] = $option['domain_price'];
					}
				}
			}
			$values['nexus_domain_prices'] = json_encode( $domainPrices );
			$values['nexus_hosting_allow_own_domain'] = in_array( 'own', $values['domain_options'] );
			
			$values['nexus_hosting_nameservers'] = implode( ',', $values['nexus_hosting_nameservers'] );
			$values['nexus_hosting_subdomains'] = in_array( 'sub', $values['domain_options'] ) ? implode( ',', $values['nexus_hosting_subdomains'] ) : '';
			
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => (bool) $values['nexus_hosting_terminate'] ), "`key`='terminateHosting'" );
			
			$bandwidthPrices = array();

			foreach ( $values['nexus_hosting_bandwidth'] as $key => $option )
			{
				if ( $option['bandwidth_amount'] )
				{
					$bandwidthPrices[ $option['bandwidth_amount'] ] = $option['bandwidth_price'];
				}
			}
			$values['nexus_hosting_bandwidth'] = json_encode( $bandwidthPrices );
			
			unset( $values['domain_options'] );
			$form->saveAsSettings( $values );
			
			\IPS\Session::i()->log( 'acplogs__hosting_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=hosting&controller=settings" ) );
		}
		
		/* Display */
		if ( !\IPS\Settings::i()->nexus_enom_un )
		{
			\IPS\Member::loggedIn()->language()->words['domain_options_enom_desc'] = \IPS\Member::loggedIn()->language()->get( 'domain_options_enom_dis' );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('hosting_settings');
		\IPS\Output::i()->output = $form;
	}
}