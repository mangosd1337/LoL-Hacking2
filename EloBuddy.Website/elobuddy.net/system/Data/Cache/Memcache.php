<?php
/**
 * @brief		Memcache Cache Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		17 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Data\Cache;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Memcache Cache Class
 */
class _Memcache extends \IPS\Data\Cache
{
	/**
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported()
	{
		return ( class_exists( 'Memcached' ) OR class_exists( 'Memcache' ) );
	}
	
	/**
	 * Configuration
	 *
	 * @param	array	$configuration	Existing settings
	 * @return	array	\IPS\Helpers\Form\FormAbstract elements
	 */
	public static function configuration( $configuration )
	{
		return array(
			'servers'	=> new \IPS\Helpers\Form\Stack( 'datastore_memcache_servers', isset( $configuration['servers'] ) ? $configuration['servers'] : array(), FALSE, array( 'placeholder' => '127.0.0.1:11211' ), function( $val )
			{
				if ( \IPS\Request::i()->cache_method === 'Memcache' )
				{
					if ( empty( $val ) )
					{
						throw new \DomainException( 'datastore_memcache_servers_err' );
					}
					else
					{
						$test = new \IPS\Data\Cache\Memcache( array( 'servers' => $val ) );
						if ( $test->link->getStats() === FALSE )
						{
							throw new \DomainException( 'datastore_memcache_servers_err2' );
						}
					}
				}
			} )
		);
	}

	/**
	 * @brief	Connection resource
	 */
	protected $link = null;
	
	/**
	 * @brief	Keys that do not exist
	 */
	protected $doesNotExist = array();

	/**
	 * Get the server link
	 *
	 * @return \Memcache|\Memcached
	 */
	protected function _getLink()
	{
		if( class_exists( 'Memcached' ) )
		{
			return new \Memcached;
		}
		else
		{
			return new \Memcache;
		}
	}

	/**
	 * Constructor
	 *
	 * @param	array	$configuration	Configuration
	 * @return	void
	 */
	public function __construct( $configuration )
	{
		/* Connect and add the servers that are defined to the pool */
		$this->link	= $this->_getLink();

		$configuration['servers'] = ( !is_array( $configuration['servers'] ) ) ? array( $configuration['servers'] ) : $configuration['servers'];

		foreach( $configuration['servers'] as $server )
		{
			$exploded = explode( ':', $server );
			$this->link->addServer( $exploded[0], isset( $exploded[1] ) ? $exploded[1] : 11211 );
		}
	}

	/**
	 * Abstract Method: Get
	 *
	 * @param	string	$key	Key
	 * @return	string	Value from the _datastore
	 */
	protected function get( $key )
	{
		if( !isset( $this->cache[ $key ] ) )
		{
			$value = $this->link->get( \IPS\SUITE_UNIQUE_KEY . '_' . $key );
			
			if( method_exists( $this->link, 'getResultCode' ) and $this->link->getResultCode() == \Memcached::RES_NOTFOUND )
			{
				$this->doesNotExist[] = $key;
				throw new \OutOfRangeException;
			}
			
			$this->cache[ $key ] = $value;
		}
		
		return $this->cache[ $key ];
	}
	
	/**
	 * Abstract Method: Set
	 *
	 * @param	string			$key	Key
	 * @param	string			$value	Value
	 * @para,	\IPS\DateTime	$expire	Expreation time, or NULL for no expiration
	 * @return	bool
	 */
	protected function set( $key, $value, \IPS\DateTime $expire = NULL )
	{
		if ( in_array( $key, $this->doesNotExist ) )
		{
			unset( $this->doesNotExist[ array_search( $key, $this->doesNotExist ) ] );
		}
		$this->cache[ $key ] = $value;

		if ( $this->link instanceof \Memcached )
		{
			$result = (bool) $this->link->set( \IPS\SUITE_UNIQUE_KEY . '_' . $key, $value, $expire ? $expire->getTimestamp() : 0 );
			if ( !$result )
			{
				$error = $this->link->getResultCode();
				/** will log the error code returned by memcached; lookup: http://php.net/manual/en/memcached.getresultcode.php */
				\IPS\Log::debug( 'error code ' . $error, 'memcached_set_error' );
			}
			return $result;
		}
		else
		{
			return (bool) $this->link->set( \IPS\SUITE_UNIQUE_KEY . '_' . $key, $value, MEMCACHE_COMPRESSED, $expire ? $expire->getTimestamp() : 0 );
		}

	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	protected function exists( $key )
	{
		if ( in_array( $key, $this->doesNotExist ) )
		{
			return FALSE;
		}
		elseif ( isset( $this->cache[ $key ] ) )
		{
			return TRUE;
		}
		else
		{
			$value = $this->link->get( \IPS\SUITE_UNIQUE_KEY . '_' . $key );
						
			if( method_exists( $this->link, 'getResultCode' ) )
			{
				if ( $this->link->getResultCode() == \Memcached::RES_NOTFOUND or $value === FALSE )
				{
					$this->doesNotExist[] = $key;
					return FALSE;
				}
			}
			else
			{
				if ( $value === FALSE )
				{
					$this->doesNotExist[] = $key;
					return FALSE;
				}
			}
			
			$this->cache[ $key ] = $value;
			return TRUE;
		}
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	protected function delete( $key )
	{
		return (bool) $this->link->delete( \IPS\SUITE_UNIQUE_KEY . '_' . $key );
	}

	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if( $this->link )
		{
			if( method_exists( $this->link, 'quit' ) )
			{
				$this->link->quit();
			}
			elseif( method_exists( $this->link, 'close' ) )
			{
				$this->link->close();
			}
		}
	}
	
	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll()
	{
		parent::clearAll();
		$this->link->flush();
	}
}