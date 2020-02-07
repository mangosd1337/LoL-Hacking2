<?php
/**
 * @brief		License Key Model - MD5
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		30 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Purchase\LicenseKey;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * License Key Model - MD5
 */
class _Mdfive extends \IPS\nexus\Purchase\LicenseKey
{	
	/**
	 * Generates a License Key
	 *
	 * @return	string
	 */
	public function generate()
	{
		return md5( uniqid() );
	}
}