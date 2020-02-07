<?php
/**
 * @brief		API Exception
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Exception
 */
class _Exception extends \Exception
{
	/**
	 * @brief	Exception code
	 */
	public $exceptionCode;
	
	/**
	 * Constructor
	 *
	 * @param	string	$message	Error Message
	 * @param	string	$code		Code
	 * @param	int		$httpCode	HTTP Error code
	 * @return	void
	 */
	public function __construct( $message, $code, $httpCode )
	{
		$this->exceptionCode = $code;
		return parent::__construct( $message, $httpCode );
	}
}