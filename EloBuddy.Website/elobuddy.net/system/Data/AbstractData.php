<?php
/**
 * @brief		Abstract Data Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Sep 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Data;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract Data Class
 */
abstract class _AbstractData
{
	/**
	 * Configuration
	 *
	 * @param	array	$configuration	Existing settings
	 * @return	array	\IPS\Helpers\Form\FormAbstract elements
	 */
	public static function configuration( $configuration )
	{
		return array();
	}
	
	/**
	 * @brief	Data Store
	 */
	protected $_data = array();
	
	/**
	 * @brief	Keys that exist
	 */
	protected $_exists = array();

	/**
	 * Magic Method: Get
	 *
	 * @param	string	$key	Key
	 * @return	string	Value from the _datastore
	 * @throws	\OutOfRangeException
	 */
	public function __get( $key )
	{	
		if( !isset( $this->_data[ $key ] ) )
		{						
			if( $this->exists( $key ) )
			{
				$value = json_decode( $this->get( $key ), TRUE );
				if ( \IPS\CACHING_LOG )
				{
					$this->log[] = array( 'get', $key, version_compare( PHP_VERSION, '5.4.0' ) >= 0 ? json_encode( $value, JSON_PRETTY_PRINT ) : json_encode( $value ), var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
				}
				$this->_data[ $key ] = $value;
			}
			else
			{
				throw new \OutOfRangeException;
			}
		}
		
		return $this->_data[ $key ];
	}

	/**
	 * Magic Method: Set
	 *
	 * @param	string	$key	Key
	 * @param	string	$value	Value
	 * @return	void
	 */
	public function __set( $key, $value )
	{
		if ( \IPS\CACHING_LOG )
		{
			$this->log[] = array( 'set', $key, version_compare( PHP_VERSION, '5.4.0' ) >= 0 ? json_encode( $value, JSON_PRETTY_PRINT ) : json_encode( $value ), var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
		}
		
		if ( $this->set( $key, json_encode( $value ) ) )
		{
			$this->_data[ $key ] = $value;
			$this->_exists[ $key ] = $key;
		}
		else
		{
			/* We do this so that the logs will reflect whether the cache (apc, memcache, etc.) or datastore has failed, since both
				datastore and caching extend this base class */
			$classname = explode( '\\', get_class( $this ) );
			$classarea = array_pop( $classname );
			$namespace = array_pop( $classname );

			\IPS\Log::log( "Could not write to {$namespace}-{$classarea} ({$key})", 'datastore' );
		}
	}

	/**
	 * Magic Method: Isset
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	public function __isset( $key )
	{
		if( isset( $this->_data[ $key ] ) or in_array( $key, $this->_exists ) )
		{
			return TRUE;
		}
		else
		{
			$return = $this->exists( $key );
			if ( $return )
			{
				$this->_exists[ $key ] = $key;
			}
			if ( \IPS\CACHING_LOG )
			{
				$this->log[] = array( 'check', $key, var_export( $return, TRUE ), var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
			}
			return $return;
		}
	}
		
	/**
	 * Magic Method: Unset
	 *
	 * @param	string	$key	Key
	 * @return	void
	 */
	public function __unset( $key )
	{
		if ( \IPS\CACHING_LOG )
		{
			$this->log[] = array( 'delete', $key, NULL, var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
		}
		$this->delete( $key );
		unset( $this->_data[ $key ] );
		unset( $this->_exists[ $key ] );
	}
}