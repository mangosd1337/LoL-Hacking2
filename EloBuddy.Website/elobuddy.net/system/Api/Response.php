<?php
/**
 * @brief		API Response
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
 * API Response
 */
class _Response
{
	/**
	 * @brief	HTTP Response Code
	 */
	public $httpCode;
	
	/**
	 * @brief	Data
	 */
	protected $data;

	/**
	 * Constructor
	 *
	 * @param	int		$httpCode	HTTP Response code
	 * @param	array	$data		Data to return
	 * @return	void
	 */
	public function __construct( $httpCode, $data )
	{
		$this->httpCode = $httpCode;
		$this->data = $data;
	}
	
	/**
	 * Data to output
	 *
	 * @retrun	string
	 */
	public function getOutput()
	{
		return $this->data;
	}
}