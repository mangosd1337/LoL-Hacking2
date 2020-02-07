<?php
/**
 * @brief		Abstract Storage Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		07 May 2013
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
 * Abstract Storage Class
 */
abstract class _Store extends AbstractData
{
	/**
	 * @brief	Instance
	 */
	protected static $instance;
	
	/**
	 * Get instance
	 *
	 * @return	\IPS\Data\Store
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = 'IPS\Data\Store\\' . \IPS\STORE_METHOD;
			static::$instance = new $classname( json_decode( \IPS\STORE_CONFIG, TRUE ) );
		}
		
		return static::$instance;
	}
	
	/**
	 * @brief	Always needed Store keys
	 */
	public $initLoad = array();
	
	/**
	 * @brief	Template store keys
	 */
	public $templateLoad = array();
	
	/**
	 * @brief	Log
	 */
	public $log	= array();
		
	/**
	 * Load mutiple
	 * Used so if it is known that several are going to be needed, they can all be loaded into memory at the same time
	 *
	 * @param	array	$keys	Keys
	 * @return	void
	 */
	public function loadIntoMemory( array $keys )
	{
		
	}
	
	/**
	 * Magic Method: Get
	 *
	 * @param	string	$key	Key
	 * @return	string	Value from the _datastore
	 */
	public function __get( $key )
	{
		/* Try to get it from the cache store... */
		try
		{
			/* If caching is enabled, and this isn't the special map of hashes... */ 
			if ( \IPS\CACHE_METHOD !== 'None' and $key !== 'cacheKeys' )
			{
				/* And exists in the caching engine, and we know the hash of the correct value... */
				$cacheKeys = ( isset( $this->cacheKeys ) and is_array( $this->cacheKeys ) ) ? $this->cacheKeys : array();
				if ( isset( $cacheKeys[ $key ] ) and isset( \IPS\Data\Cache::i()->$key ) )
				{				
					/* Get it... */
					$value = \IPS\Data\Cache::i()->$key;
					
					/* But only use it if the hash matches */
					if( \IPS\Login::compareHashes( $cacheKeys[ $key ], md5( json_encode( $value ) ) ) )
					{
						return $value;
					}
				}
			}
			
			/* Still here? throw an exception so we get it from the data storage engine */
			throw new \OutOfRangeException;
		}
		
		/* If we couldn't get it from the cache engine, get it from the data storage engine */
		catch ( \OutOfRangeException $e )
		{
			/* Actually get it... */
			$value = parent::__get( $key );
			
			/* If caching is enabled, and this isn't the special map of hashes... */
			if ( \IPS\CACHE_METHOD !== 'None' and $key !== 'cacheKeys' )
			{
				/* Set it in the caching engine... */
				\IPS\Data\Cache::i()->$key = $value;

				/* And set the hash in the cacheKeys hash map */
				$cacheKeys = ( isset( $this->cacheKeys ) and is_array( $this->cacheKeys ) ) ? $this->cacheKeys : array();
				$cacheKeys[ $key ] = md5( json_encode( $value ) );
				$this->cacheKeys = $cacheKeys;
			}
			
			return $value;
		}
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
		/* Actually set it in the data storage engine */
		parent::__set( $key, $value );
		
		/* If caching is enabled, and this isn't the special map of hashes... */
		if ( \IPS\CACHE_METHOD !== 'None' and $key !== 'cacheKeys' )
		{
			/* Then also set it in the cache... */
			\IPS\Data\Cache::i()->$key = $value;
		
			/* And set the hash in the cacheKeys hash map */
			if ( ( isset( $this->cacheKeys ) and is_array( $this->cacheKeys ) ) )
			{
				$cacheKeys = is_array( $this->cacheKeys ) ? $this->cacheKeys : array();
				$cacheKeys[ $key ] = md5( json_encode( $value ) );
				$this->cacheKeys = $cacheKeys;
			}
			else
			{
				$this->cacheKeys = array( $key => md5( json_encode( $value ) ) );
			}
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
		/* If caching is enabled, and this isn't the special map of hashes, try to get it from the cache store... */
		if ( \IPS\CACHE_METHOD !== 'None' and $key !== 'cacheKeys' )
		{
			/* Does it exist in the caching engine? */
			if ( isset( \IPS\Data\Cache::i()->$key ) )
			{
				/* Get it... */
				$value = \IPS\Data\Cache::i()->$key;
								
				/* But only use it if the hash matches */
				$cacheKeys = ( isset( $this->cacheKeys ) and is_array( $this->cacheKeys ) ) ? $this->cacheKeys : array();
				if ( isset( $cacheKeys[ $key ] ) and \IPS\Login::compareHashes( $cacheKeys[ $key ], md5( json_encode( $value ) ) ) )
				{
					return TRUE;
				}
			}			
		}
		
		/* If we're still here, check the data storage engine... */
		return parent::__isset( $key );
	}
		
	/**
	 * Magic Method: Unset
	 *
	 * @param	string	$key	Key
	 * @return	void
	 */
	public function __unset( $key )
	{
		/* Unset it in the data storage engine */
		parent::__unset( $key );
		
		/* If caching is enabled, and this isn't the special map of hashes, remove it from the cache store... */
		if ( \IPS\CACHE_METHOD !== 'None' and $key !== 'cacheKeys' )
		{
			/* Remove it from the cache store... */
			unset( \IPS\Data\Cache::i()->$key );
			
			/* And from the special map of hashes */
			$cacheKeys = is_array( $this->cacheKeys ) ? $this->cacheKeys : array();
			unset( $cacheKeys[ $key ] );
			$this->cacheKeys = $cacheKeys;
		}
	}
}