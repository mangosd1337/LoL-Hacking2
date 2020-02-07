<?php
/**
 * @brief		Wincache Cache Class
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
 * Wincache Storage Class
 */
class _Wincache extends \IPS\Data\Cache
{
	/**
	 * Server supports this method?
	 *
	 * @return	bool
	 */
	public static function supported()
	{
		return function_exists('wincache_ucache_get');
	}

	/**
	 * Abstract Method: Get
	 *
	 * @param	string	$key	Key
	 * @return	string	Value from the _datastore
	 */
	protected function get( $key )
	{
		if( isset( $this->cache[ $key ] ) )
		{
			\IPS\Log::debug( "Get {$key} from Wincache (already loaded)", 'cache' );
			return $this->cache[ $key ];
		}

		\IPS\Log::debug( "Get {$key} from Wincache", 'cache' );

		$this->cache[ $key ]	= wincache_ucache_get( \IPS\SUITE_UNIQUE_KEY . '_' . $key );
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
		\IPS\Log::debug( "Set {$key} in Wincache", 'cache' );
		return (bool) wincache_ucache_set( \IPS\SUITE_UNIQUE_KEY . '_' . $key, $value, $expire ? ( $expire->getTimestamp() - time() ) : 0 );
	}
	
	/**
	 * Abstract Method: Exists?
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	protected function exists( $key )
	{
		if( isset( $this->cache[ $key ] ) )
		{
			\IPS\Log::debug( "Check exists {$key} from Wincache (already loaded)", 'cache' );
			return TRUE;
		}

		\IPS\Log::debug( "Check exists {$key} from Wincache", 'cache' );
		return wincache_ucache_exists( \IPS\SUITE_UNIQUE_KEY . '_' . $key );
	}
	
	/**
	 * Abstract Method: Delete
	 *
	 * @param	string	$key	Key
	 * @return	bool
	 */
	protected function delete( $key )
	{
		\IPS\Log::debug( "Delete {$key} from Wincache", 'cache' );

		return (bool) wincache_ucache_delete( \IPS\SUITE_UNIQUE_KEY . '_' . $key );
	}
	
	/**
	 * Abstract Method: Clear All Caches
	 *
	 * @return	void
	 */
	public function clearAll()
	{
		parent::clearAll();
		wincache_ucache_clear();
	}
}