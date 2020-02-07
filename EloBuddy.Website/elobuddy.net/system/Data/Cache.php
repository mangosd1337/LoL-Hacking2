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
abstract class _Cache extends AbstractData
{
	/**
	 * @brief	Instance
	 */
	protected static $instance;

	/**
	 * @brief	Caches already retrieved this instance
	 */
	protected $cache	= array();
	
	/**
	 * @brief	Log
	 */
	public $log	= array();

	/**
	 * Get instance
	 *
	 * @return	\IPS\Data\Cache
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = 'IPS\Data\Cache\\' . \IPS\CACHE_METHOD;
			
			if ( $classname::supported() )
			{
				static::$instance = new $classname( json_decode( \IPS\CACHE_CONFIG, TRUE ) );
			}
			else
			{
				static::$instance = new \IPS\Data\Cache\None( array() );
			}
		}
		
		return static::$instance;
	}
	
	/**
	 * Store value using cache method if available or falling back to the database
	 *
	 * @param	string			$key		Key
	 * @param	mixed			$value		Value
	 * @param	\IPS\DateTime	$expire		Expiration if using database
	 * @param	bool			$fallback	Use database if no caching method is available?
	 * @return	void
	 */
	public function storeWithExpire( $key, $value, \IPS\DateTime $expire, $fallback=FALSE )
	{
		$value = array( 'value' => $value, 'expires' => $expire->getTimestamp() );
		
		if ( \IPS\CACHING_LOG )
		{
			$this->log[] = array( 'set', $key, version_compare( PHP_VERSION, '5.4.0' ) >= 0 ? json_encode( $value, JSON_PRETTY_PRINT ) : json_encode( $value ), var_export( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), TRUE ) );
		}
		
		if ( $this->set( $key, json_encode( $value ), $expire ) )
		{
			$this->_data[ $key ] = $value;
			$this->_exists[ $key ] = $key;
		}
	}
	
	/**
	 * Get value using cache method if available or falling back to the database
	 *
	 * @param	string	$key	Key
	 * @param	bool	$fallback	Use database if no caching method is available?
	 * @return	mixed
	 * @throws	\OutOfRangeException
	 */
	public function getWithExpire( $key, $fallback=FALSE )
	{
		if ( !isset( $this->$key ) )
		{
			throw new \OutOfRangeException;
		}
		
		$data = $this->$key;
		if( count( $data ) and isset( $data['value'] ) and isset( $data['expires'] ) )
		{
			/* Is it expired? */
			if( $data['expires'] AND time() < $data['expires'] )
			{
				return $data['value'];
			}
			else
			{
				unset( $this->$key );
				throw new \OutOfRangeException;
			}
		}
		else
		{
			unset( $this->$key );
			throw new \OutOfRangeException;
		}
	}
	
	/**
	 * Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll()
	{
		/* cacheKeys stored md5 hashes of the correct value, and the cache
			is only used if the value it returns matches the hash, so clearing
			this out invalidates all caches, even if the caching engine
			does not allow us to actually clear them */
		\IPS\Data\Store::i()->cacheKeys = array();
	}
	
}