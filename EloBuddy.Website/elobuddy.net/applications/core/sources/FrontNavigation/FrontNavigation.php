<?php
/**
 * @brief		Front Navigation Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Core
 * @since		30 Jun 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Custom Item
 */
class _FrontNavigation
{
	/**
	 * @brief	Singleton Instances
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static $instance = NULL;

	/**
	 * Get instance
	 *
	 * @return	static
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		return static::$instance;
	}
	
	/**
	 * Get data store
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public static function frontNavigation( $noStore=FALSE )
	{
		if ( $noStore or !isset( \IPS\Data\Store::i()->frontNavigation ) )
		{
			$frontNavigation = array( 0 => array(), 1 => array() );
			$select = \IPS\Db::i()->select( '*', 'core_menu', NULL, 'position' );
			if ( count( $select ) )
			{
				foreach ( $select as $item )
				{
					if ( \IPS\Application::appIsEnabled( $item['app'] ) )
					{
						$frontNavigation[ intval( $item['parent'] ) ][ $item['id'] ] = $item;
					}
				}
			}
			elseif ( !$noStore )
			{
				static::buildDefaultFrontNavigation();
				return static::frontNavigation();
			}
			if ( $noStore )
			{
				return $frontNavigation;
			}
			\IPS\Data\Store::i()->frontNavigation = $frontNavigation;
		}
		return \IPS\Data\Store::i()->frontNavigation;
	}
	
	/**
	 * Delete front navigation items by application
	 *
	 * @param	\IPS\Application	$app	Application deleted
	 * @return	void
	 */
	public static function deleteByApplication( \IPS\Application $app )
	{
		foreach( \IPS\Db::i()->select( '*', 'core_menu', array( array( 'extension=?', 'CustomItem' ) ) ) as $row )
		{
			$config = json_decode( $row['config'], TRUE );
		
			if ( isset( $config['menu_custom_item_url'] ) and $config['menu_custom_item_url'] and isset( $config['internal'] ) and $config['internal'] )
			{
				try
				{
					$data = \IPS\Http\Url::internal( $config['menu_custom_item_url'], NULL, $config['internal'] )->getFriendlyUrlData();
					
					if ( ! empty( $data['app'] ) and $data['app'] === $app->directory )
					{
						\IPS\Db::i()->delete( 'core_menu', array( 'id=?', $row['id'] ) );
					}
				}
				catch( \Exception $e ) { }
			}
		}
		
		\IPS\Db::i()->delete( 'core_menu', array( 'app=?', $app->directory ) );
		
		unset( \IPS\Data\Store::i()->frontNavigation );
	}
		
	/**
	 * Build default front navigation
	 *
	 * @return	void
	 */
	public static function buildDefaultFrontNavigation()
	{		
		\IPS\Db::i()->delete( 'core_menu' );
		
		$position = 1;
				
		/* Browse */
		\IPS\Db::i()->insert( 'core_menu', array(
			'id'			=> 1,
			'app'			=> 'core',
			'extension'		=> 'CustomItem',
			'config'		=> json_encode( array( 'menu_custom_item_url' => '', 'internal' => '' ) ),
			'position'		=> $position++,
			'parent'		=> NULL,
			'permissions'	=> '*',
		) );

		/* Activity */
		\IPS\Db::i()->insert( 'core_menu', array(
			'id'			=> 2,
			'app'			=> 'core',
			'extension'		=> 'CustomItem',
			'config'		=> json_encode( array( 'menu_custom_item_url' => 'app=core&module=discover&controller=streams', 'internal' => 'discover_all' ) ),
			'position'		=> $position++,
			'parent'		=> NULL,
			'permissions'	=> '*',
		) );
		
		/* Loop */
		$waiting = array();
		foreach ( \IPS\Application::applications() as $app )
		{
			if ( \IPS\Application::appIsEnabled( $app->directory ) )
			{
				$defaultNavigation = $app->defaultFrontNavigation();
				foreach ( $defaultNavigation as $type => $tabs )
				{
					foreach ( $tabs as $config )
					{
						switch ( $type )
						{
							case 'rootTabs':
								$parent = NULL;
								break;
							case 'browseTabs':
								$parent = 1;
								break;
							case 'activityTabs':
								$parent = 2;
								break;
						}
						
						$config['real_app'] = $app->directory;
						if ( !isset( $config['app'] ) )
						{
							$config['app'] = $app->directory;
						}
						
						if ( $type == 'browseTabsEnd' )
						{
							$waiting[] = $config;
						}
						else
						{
							static::insertMenuItem( $parent, $config, $position );
						}
					}
				}
			}
		}
		foreach ( $waiting as $config )
		{
			static::insertMenuItem( 1, $config, $position );
		}
	}
	
	/**
	 * Insert a menu item
	 *
	 * @param	int		$parent			Parent ID
	 * @param	array	$config			Configuration
	 * @param	int		$position		Position
	 * @param	bool	$isMenuChild	Is item in a menu?
	 * @return	void
	 */
	public static function insertMenuItem( $parent, $config, $position, $isMenuChild=FALSE )
	{		
		$insertedId = \IPS\Db::i()->insert( 'core_menu', array(
			'app'			=> $config['app'],
			'extension'		=> $config['key'],
			'config'		=> json_encode( isset( $config['config'] ) ? $config['config'] : array() ),
			'position'		=> $position++,
			'parent'		=> $parent,
			'permissions'	=> '*',
			'is_menu_child'	=> $isMenuChild,
		) );
		
		if ( isset( $config['title'] ) )
		{
			\IPS\Lang::copyCustom( $config['real_app'], $config['title'], "menu_item_{$insertedId}" );
		}
		
		if ( isset( $config['children'] ) )
		{
			foreach ( $config['children'] as $childConfig )
			{
				$childConfig['real_app'] = $config['real_app'];
				if ( !isset( $childConfig['app'] ) )
				{
					$childConfig['app'] = $config['real_app'];
				}
						
				static::insertMenuItem( $insertedId, $childConfig, $position, $config['app'] == 'core' and $config['key'] == 'Menu' );
			}
		}
	}
	
	/**
	 * @brief	The active primary navigation bar
	 */
	public $activePrimaryNavBar = NULL;
	
	/**
	 * Get roots
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function roots( $noStore=FALSE )
	{
		$frontNavigation = static::frontNavigation( $noStore );
		$return = array();
		foreach ( $frontNavigation[0] as $item )
		{
			$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
			$object = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'] );
			if ( !$this->activePrimaryNavBar )
			{
				$this->activePrimaryNavBar = $item['id'];
			}
			$return[ $item['id'] ] = $object;
		}
		return $return;
	}
	
	/**
	 * Get sub-bars
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function subBars( $noStore=FALSE )
	{
		$frontNavigation = static::frontNavigation( $noStore );
		$return = array();
		$parentIDs = array();
		// Changed so that empty sub bars don't add an array to their parent, allowing us to do count( $subBars ) and figure
		// out if there's any to show.
		foreach ( $frontNavigation[0] as $item )
		{
			$parentIDs[] = $item['id'];
		}

		foreach ( $parentIDs as $i )
		{
			if ( isset( $frontNavigation[$i] ) )
			{
				foreach ( $frontNavigation[$i] as $item )
				{
					if ( empty( $item['is_menu_child'] ) )
					{
						$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
						if ( class_exists( $class ) )
						{
							$return[ $item['parent'] ][ $item['id'] ] = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'] );
						}
					}
				}
			}
		}
		return $return;
	}
}