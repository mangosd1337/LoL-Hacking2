<?php
/**
 * @brief		eNom Domain Registrar
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\DomainRegistrar;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * eNom Domain Registrar
 */
class _Enom
{
	/**
	 * @brief	Username
	 */
	protected $username;
	
	/**
	 * @brief	Password
	 */
	protected $password;
	
	/**
	 * @brief	URL
	 */
	protected $url;
	
	/**
	 * Constructor
	 *
	 * @param	string	$username	eNom Reseller Username
	 * @param	string	$password	eNom Reseller Password
	 * @return	void
	 */
	public function __construct( $username, $password )
	{
		$this->username = $username;
		$this->password = $password;
		
		$this->url = \IPS\Http\Url::external( 'http://' . ( \IPS\NEXUS_TEST_GATEWAYS ? 'resellertest.enom.com' : 'reseller.enom.com' ) . '/interface.asp' );
	}
	
	/**
	 * Check Domain Availablility
	 *
	 * @param	string	$sld	SLD
	 * @param	string	$tld	TLD
	 * @return	bool
	 * @throws	\RuntimeException
	 */
	public function check( $sld, $tld )
	{
		$response = $this->url->request()->post( array(
			'command'		=> 'check',
			'uid'			=> $this->username,
			'pw'			=> $this->password,
			'sld'			=> $sld,
			'tld'			=> $tld,
			'responsetype'	=> 'xml',
		) )->decodeXml();
		
		if ( $response->errors )
		{
			throw new \RuntimeException( (string) $response->errors->Err1 );
		}
		
		return (string) $response->RRPCode === '210';
	}
	
	/**
	 * Register Domain
	 *
	 * @param	string				$sld			SLD
	 * @param	string				$tld			TLD
	 * @param	array				$nameservers	Nameservers
	 * @param	\IPS\nexus\Customer	$customer		The customer
	 * @param	\IPS\GeoLocation	$billingAddress	Billing Address
	 * @return	bool
	 * @throws	\RuntimeException
	 */
	public function register( $sld, $tld, array $nameservers, \IPS\nexus\Customer $customer, \IPS\GeoLocation $billingAddress )
	{
		$send = array(
			'command'	=> 'purchase',
			'UID'		=> $this->username,
			'PW'		=> $this->password,
			'SLD'		=> $sld,
			'TLD'		=> $tld,
			);
		$i = 1;
		foreach ( $nameservers as $s )
		{
			$send["NS{$i}"] = $s;
			$i++;
		}
		$send['NumYears'] = 1;
		$send['IgnoreNSFail'] = 'Yes';
		$send['UseWireTransfer'] = 'Yes';
		$send['responsetype'] = 'xml';
		
		$response = $this->url->request( \IPS\LONG_REQUEST_TIMEOUT )->post( $send )->decodeXml();

		if ( $response->errors )
		{
			throw new \RuntimeException( (string) $response->errors->Err1 );
		}
		
		return (string) $response->RRPCode === '200';
	}
	
	/**
	 * Renew Domain
	 *
	 * @param	string	$sld	SLD
	 * @param	string	$tld	TLD
	 * @param	int		$years	Years to renew for
	 * @return	bool
	 * @throws	\RuntimeException
	 */
	public function renew( $sld, $tld, $years )
	{
		$response = $this->url->request( \IPS\LONG_REQUEST_TIMEOUT )->post( array(
			'command'			=> 'extend',
			'UID'				=> $this->username,
			'PW'				=> $this->password,
			'SLD'				=> $sld,
			'TLD'				=> $tld,
			'responsetype'		=> 'xml',
		) )->decodeXml();
		
		return (string) $response->RRPCode === '200';
	}
}