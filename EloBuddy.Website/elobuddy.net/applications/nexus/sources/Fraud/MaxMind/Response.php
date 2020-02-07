<?php
/**
 * @brief		MaxMind Response
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Fraud\MaxMind;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MaxMind Response
 */
class _Response
{
	/**
	 * @brief	Data
	 */
	protected $data = array();
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Response
	 * @return	void
	 */
	public function __construct( \IPS\Http\Response $data = NULL )
	{
		if ( $data )
		{
			foreach ( explode( ';', $data ) as $row )
			{
				$exploded = explode( '=', $row );
				$this->data[ $exploded[0] ] = $exploded[1];
			}
		}
	}
	
	/**
	 * Get data
	 *
	 * @param	string	$key	Key
	 * @return	mixed
	 */
	public function __get( $key )
	{
		if ( isset( $this->data[ $key ] ) )
		{
			return $this->data[ $key ];
		}
		return NULL;
	}
	
	/**
	 * Build from JSON
	 *
	 * @param	string	$json	JSON data
	 * @return	\IPS\nexus\Fraud\MaxMind\Response
	 */
	public static function buildFromJson( $json )
	{
		$obj = new static;
		$obj->data = json_decode( $json, TRUE );
		return $obj;
	}
	
	/**
	 * JSON encoded
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return json_encode( $this->data );
	}
	
	/**
	 * proxyScore as percentage
	 *
	 * @return	int
	 */
	public function proxyScorePercentage()
	{
		return ( 100 - 10 ) / 3 * $this->proxyScore + ( $this->proxyScore > 3 ? ( 10 * ( $this->proxyScore - 3 ) ) : 0 );
	}

	/**
	 * minFraud error codes
	 *
	 * @see https://dev.maxmind.com/minfraud/#Error_Reporting
	 *
	 * @var array
	 */
	protected static $responseErrors =  array( 'INVALID_LICENSE_KEY', 'IP_REQUIRED', 'IP_NOT_FOUND ', 'MAX_REQUESTS_REACHED', 'LICENSE_REQUIRED ');

	/**
	 * Does the response include an error?
	 *
	 * @return bool
	 */
	public function error()
	{
		if ( $this->err AND in_array($this->err, static::$responseErrors ) )
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Does the response include a warning?
	 *
	 * @return bool
	 */
	public function warning()
	{
		if ( $this->err AND !in_array($this->err, static::$responseErrors ) )
		{
			return TRUE;
		}
		return FALSE;
	}
}