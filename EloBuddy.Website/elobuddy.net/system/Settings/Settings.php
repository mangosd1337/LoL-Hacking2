<?php
/**
 * @brief		Settings Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings class
 */
class _Settings extends \IPS\Patterns\Singleton
{
	/**
	 * @brief	Singleton Instances
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Data Store
	 */
	protected $data = NULL;
	
	/**
	 * @brief	Settings loaded?
	 */
	protected $loaded = FALSE;
		
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	protected function __construct()
	{
		if ( file_exists( \IPS\ROOT_PATH . '/conf_global.php' ) )
		{
			require( \IPS\ROOT_PATH . '/conf_global.php' );
			if ( isset( $INFO ) )
			{
				if( isset( $INFO['board_url'] ) AND !isset( $INFO['base_url'] ) )
				{
					$INFO['base_url']	= $INFO['board_url'];
				}
				if( isset( $INFO['base_url'] ) )
				{
					/* Upgraded boards may not have trailing slash */
					if( mb_substr( $INFO['base_url'], -1, 1 ) !== '/' )
					{
						$INFO['base_url']	= $INFO['base_url'] . '/';
					}
				}

				if( isset( $INFO['use_friendly_urls'] ) )
				{
					unset( $INFO['use_friendly_urls'] );
				}
				
				$this->data = $INFO;
			}
		}
	}
	
	/**
	 * Magic Method: Get
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( $key )
	{	
		$return = parent::__get( $key );
		
		if ( $return === NULL and !$this->loaded )
		{
			$this->loadFromDb();
			return parent::__get( $key );
		}
		
		return $return;
	}
	
	/**
	 * Get from conf_global.php
	 * Useful when you need to get a value from conf_global.php without loading the DB, such as in the installer
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value
	 */
	public function getFromConfGlobal( $key )
	{	
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : NULL;
	}
	
	/**
	 * Magic Method: Isset
	 *
	 * @param	mixed	$key	Key
	 * @return	bool
	 */
	public function __isset( $key )
	{
		$return = parent::__isset( $key );
		
		if ( $return === FALSE and !$this->loaded )
		{
			$this->loadFromDb();
			return parent::__isset( $key );
		}
		
		return $return;
	}
	
	/**
	 * Load Settings
	 *
	 * @return	void
	 */
	protected function loadFromDb()
	{
		if ( isset( \IPS\Data\Store::i()->settings ) )
		{
			$settings = \IPS\Data\Store::i()->settings;
		}
		else
		{		
			foreach ( \IPS\Db::i()->select( 'conf_key, conf_default, conf_value', 'core_sys_conf_settings' )->setKeyField( 'conf_key' ) as $k => $data )
			{
				$settings[ $k ] = ( $data['conf_value'] === '' ) ? $data['conf_default'] : $data['conf_value'];
			}
			\IPS\Data\Store::i()->settings = $settings;
		}

		/* We don't want to 'cache' what is in conf_global */
		$this->data = array_merge( $this->data, $settings );

		$this->loaded = TRUE;
	}
}