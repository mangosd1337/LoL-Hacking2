<?php
/**
 * @brief		Sidebar Widget Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Nov 2013
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
 * Sidebar Widget Class
 */
abstract class _Widget
{
	/**
	 * @brief	The number of widgets that can be expired per request (to prevent loads of rebuilds on a single request which would slow the page down). Deliberately hardcoded.
	 */
	protected static $expirePerRequest = 1;
	
	/**
	 * @brief	Configuration
	 */
	public $configuration = array();
	
	/**
	 * @brief	Access. Array of allowed apps that execute the widgets. Null for no restriction
	 */
	protected $access = null;
	
	/**
	 * @brief	Custom template callback
	 */
	public $template = null;
	
	/**
	 * @brief	Orientation
	 */
	protected $orientation = null;
	
	/**
	 * @brief	Menu style
	 */
	public $menuStyle = 'menu';
	
	/**
	 * @brief	Allow block to be reused
	 */
	public $allowReuse = false;

	/**
	 * @brief	Unique key for this widget
	 */
	public $uniqueKey = NULL;
	
	/**
	 * @brief	Prevent caching for this block
	 */
	public $neverCache = FALSE;
	
	/**
	 * Constructor
	 *
	 * @param	String				$uniqueKey			Unique key for this specific instance
	 * @param	array				$configuration		Widget custom configuration
	 * @param	null|string|array	$access				Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	string				$orientation		Horizontal or vertical orientation
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		$this->configuration = $configuration;
		$this->orientation = $orientation;
		
		if ( $access !== null and is_string( $access ) )
		{
			$test = json_decode( $access, true );
			
			if ( is_array( $test ) AND count( $test ) )
			{
				$this->access = $test;
			}
		}
		else if ( is_array( $access ) AND count( $access ) )
		{
			$this->access = $access;
		}

		$this->init();

		$this->uniqueKey  = ( empty( $uniqueKey ) ) ? ( $this->key ?: uniqid() ) : $uniqueKey;
	}
	
	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init()
	{
		if ( $this->app )
		{
			$this->template( array( \IPS\Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
		}
	}
	
	/**
	 * Constructor
	 *
	 * @param	string|array	$app	Application key (core,cms,gallery, etc)
	 * @return	boolean
	 */
	public function isExecutableByApp( $app )
	{
		if ( $this->access === null or ( is_array( $this->access ) and ! count($this->access ) ) )
		{
			return true;
		}
		else
		{
			if ( is_string( $app ) )
			{
				$checkApps = array( $app );
			}
			else
			{
				$checkApps = $app;
			}
			
			foreach( $checkApps as $check )
			{
				if ( in_array( $check, $this->access ) )
				{
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Fetch the application for this widget
	 *
	 * @return	\IPS\Application
	 */
	public function application()
	{
		return \IPS\Application::load( $this->app );
	}

	/**
	 * Fetch the title for this widget
	 *
	 * @return	string
	 */
	public function title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack( 'block_' . $this->key );
	}
	
	/**
	 * Fetch the description for this widget
	 *
	 * @return	string
	 */
	public function description()
	{
		return \IPS\Member::loggedIn()->language()->addToStack( 'block_' . $this->key . '_desc' );
	}
	
	/**
	 * Set the template for this widget
	 *
	 * @param	Array|Function		$callback		Function to use for template callback
	 * @return	string
	 */
	public function template( $callback )
	{
		$this->template = $callback;
	}
	
	/**
	 * Get Template Location
	 * Returns the template app/location/group/name params
	 
	 * @return array
	 */
	public function getTemplateLocation()
	{
		$class = get_class( $this->template[0] );
		if ( $class === 'IPS\Theme\Dev\Template' )
		{
			$params = $this->template[0]->getParams();
		}
		else
		{
			$params = array( 'app' => $this->template[0]->template->app, 'location' => $this->template[0]->template->templateLocation, 'group' => $this->template[0]->template->templateName );
		}

		return array_merge( $params, array( 'name' => $this->template[1] ) );
	}

	/**
	 * Get HTML using the template (language strings not parsed)
	 *
	 * @return	string
	 */
	public function output()
	{
		$args = func_get_args();
		$args['orientation'] = $this->orientation;

		$output = call_user_func_array( $this->template, $args );

		return $output;
	}
	
	/**
	 * Efficient way to see if a widget has configuration
	 *
	 * @return boolean
	 */
	public function hasConfiguration()
	{
		return method_exists( $this, 'configuration' );
	}
	
	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete()
	{
		/* Does nothing by default but can be overridden */
	}
	
	/**
	 * Factory Method
	 *
	 * @param	\IPS\Application|\IPS\Plugin	$parent				Widget application or plugin
	 * @param	String							$widgetKey			Widget key used to load class
	 * @param	String							$uniqueKey			Unique key for this specific instance
	 * @param	Array							$configuration		Current configuration
	 * @param	null|string|array				$access				Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	string							$orientation		Horizontal or vertical orientation
	 * @return	\IPS\Widget
	 * @throws	\OutOfRangeException
	 */
	public static function load( $parent, $widgetKey, $uniqueKey, $configuration=array(), $access=null, $orientation=null )
	{
		/* If our parent is not enabled, do not attempt to use this widget - both \IPS\Application and \IPS\Plugin have get__enabled() methods, so this covers both */
		if ( $parent->_enabled === FALSE )
		{
			throw new \OutOfRangeException;
		}
		
		$class = NULL;
		if ( $parent instanceof \IPS\Application )
		{
			$class = '\IPS\\' . $parent->directory . '\widgets\\' . $widgetKey;
		}
		else
		{
			if ( file_exists( \IPS\ROOT_PATH . '/plugins/' . $parent->location . '/widgets/' . $widgetKey . '.php' ) )
			{
				require_once \IPS\ROOT_PATH . '/plugins/' . $parent->location . '/widgets/' . $widgetKey . '.php';
				$class = '\IPS\\plugins\\' . $parent->location . '\\widgets\\' . $widgetKey;
			}
		}
		
		/* Return */
		if ( class_exists( $class ) )
		{
			return new $class( $uniqueKey, $configuration, $access, $orientation );
		}
		
		throw new \OutOfRangeException;
	}
	
	/**
	 * Dev Table
	 *
	 * @param	string			$json				Path to JSON file
	 * @param	\IPS\Http\Url	$url				URL to page
	 * @param	string			$widgetDirectory	Directory where PHP files are stored
	 * @param	string			$subpackage			The value to use for the subpackage in the widget file's header
	 * @param	string			$namespace			The namespace for the widget file
	 * @param	int				$version			The application/plugin's current version
	 * @param	int|string		$appKeyOrPluginId	If widget belongs to an application, it's key, or if a plugin, it's ID
	 * @return	string
	 */
	public static function devTable( $json, $url, $widgetDirectory, $subpackage, $namespace, $version, $appKeyOrPluginId )
	{
		if ( !file_exists( $json ) )
		{
			\file_put_contents( $json, json_encode( array() ) );
		}
	
		switch ( \IPS\Request::i()->widgetTable )
		{
			case 'form':
				
				$current = NULL;
				if ( isset( \IPS\Request::i()->key ) )
				{
					$widgets = json_decode( file_get_contents( $json ), TRUE );
					if ( array_key_exists( \IPS\Request::i()->key, $widgets ) )
					{
						$current = array(
								'dev_widget_key'			=> \IPS\Request::i()->key,
								'dev_widget_class'			=> $widgets[ \IPS\Request::i()->key ]['class'],
								'dev_widget_restrict'		=> $widgets[ \IPS\Request::i()->key ]['restrict'],
								'dev_widget_default_area'	=> isset($widgets[ \IPS\Request::i()->key ]['default_area']) ? $widgets[ \IPS\Request::i()->key ]['default_area'] : NULL,
								'dev_widget_allow_reuse'	=> isset($widgets[ \IPS\Request::i()->key ]['allow_reuse'])  ? $widgets[ \IPS\Request::i()->key ]['allow_reuse']  : 0,
								'dev_widget_menu_style'		=> isset($widgets[ \IPS\Request::i()->key ]['menu_style'])   ? $widgets[ \IPS\Request::i()->key ]['menu_style']   : 'menu',
						        'dev_widget_embeddable'     => isset($widgets[ \IPS\Request::i()->key ]['embeddable'])   ? $widgets[ \IPS\Request::i()->key ]['embeddable']   : 1,

						);
					}
					unset( $widgets );
				}
	
				$form = new \IPS\Helpers\Form;
				$form->add( new \IPS\Helpers\Form\Text( 'dev_widget_key', $current ? $current['dev_widget_key'] : NULL, TRUE, array( 'maxLength' => 255, 'regex' => '/^[a-z][a-z0-9]*$/i' ), function( $val ) use ( $current )
				{
                    if( mb_strpos( $val, "_" ) !== FALSE )
                    {
                        throw new \DomainException( 'dev_widget_key_err_alpha' );
                    }

					$where = array( array( '`key`=?', $val ) );
					if ( isset( $current['dev_widget_key'] ) )
					{
						$where[] = array( '`key`<>?', $current['dev_widget_key'] );
					}
						
					if ( \IPS\Db::i()->select( 'count(*)', 'core_widgets', $where )->first() )
					{
                        throw new \DomainException( 'dev_widget_key_err' );
					}
				} ) );
				
				$classes = array( '\IPS\Widget' => '\IPS\Widget' );
				\IPS\Member::loggedIn()->language()->words[ "\\IPS\\Widget" . '_desc' ] = \IPS\Member::loggedIn()->language()->get( 'widget_class_default' );
				foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . "/system/Widget" ) as $file )
				{
					if ( !$file->isDot() and $file->getExtension() === 'php' and $file != 'Widget.php' )
					{
						$classes[ "\\IPS\\Widget\\" .  mb_substr( $file, 0, -4 ) ] = "\\IPS\\Widget\\" .  mb_substr( $file, 0, -4 );
						try
						{
							\IPS\Member::loggedIn()->language()->words[ "\\IPS\\Widget\\" .  mb_substr( $file, 0, -4 ) . '_desc' ] = \IPS\Member::loggedIn()->language()->get( 'widget_class_' . mb_substr( $file, 0, -4 ) );
						}
						catch ( \UnderflowException $e ) { }
					}
				}
				$form->add( new \IPS\Helpers\Form\Radio( 'dev_widget_class', ( $current ? $current['dev_widget_class'] : NULL ), TRUE, array( 'options' => $classes ) ) );
				
				$form->add( new \IPS\Helpers\Form\CheckboxSet( 'dev_widget_restrict', ( ( $current and !empty( $current['dev_widget_restrict'] ) ) ? $current['dev_widget_restrict'] : ( $current['dev_widget_restrict'] === FALSE ? array() : array( 'sidebar', 'cms' ) ) ), FALSE, array(
					'options' => array(
						'sidebar'	=> \IPS\Member::loggedIn()->language()->addToStack('dev_widget_restrict_sidebar'),
						'cms'       => \IPS\Member::loggedIn()->language()->addToStack('dev_widget_restrict_cms'),
					),
					'multiple' => true ) ) );
				
				$form->add( new \IPS\Helpers\Form\Radio( 'dev_widget_default_area', ( $current ? ( $current['dev_widget_default_area'] ?: 'none' ) : 'none' ), FALSE, array(
						'options' => array(
								'none'		=> \IPS\Member::loggedIn()->language()->addToStack('none'),
								'sidebar'	=> \IPS\Member::loggedIn()->language()->addToStack('dev_widget_default_area_sidebar'),
								'header'	=> \IPS\Member::loggedIn()->language()->addToStack('dev_widget_default_area_header'),
								'footer'	=> \IPS\Member::loggedIn()->language()->addToStack('dev_widget_default_area_footer'),
								
						),
						'multiple' => false ) ) );
				
				$form->add( new \IPS\Helpers\Form\Radio( 'dev_widget_menu_style', ( $current ? $current['dev_widget_menu_style'] : 'menu' ), FALSE, array(
					'options' => array(
						'menu'	=> \IPS\Member::loggedIn()->language()->addToStack('dev_widget_menu_style_menu'),
						'modal'       => \IPS\Member::loggedIn()->language()->addToStack('dev_widget_menu_style_modal'),
				) ) ) );
				
				$form->add( new \IPS\Helpers\Form\YesNo( 'dev_widget_allow_reuse', ( $current ? $current['dev_widget_allow_reuse'] : 0 ), FALSE ) );

				$form->add( new \IPS\Helpers\Form\YesNo( 'dev_widget_embeddable', ( $current ? $current['dev_widget_embeddable'] : 0 ), FALSE ) );

				if ( $values = $form->values() )
				{
					/* Write PHP file */
					$widgetFile =  $widgetDirectory . "/{$values['dev_widget_key']}.php";
					if ( !file_exists( $widgetFile ) )
					{
						if ( !is_dir( $widgetDirectory ) )
						{
							mkdir( $widgetDirectory );
							chmod( $widgetDirectory, \IPS\IPS_FOLDER_PERMISSION );
						}
	
						\file_put_contents( $widgetFile, str_replace(
								array(
										'{key}',
										"{subpackage}\n",
										'{date}',
										'{namespace}',
										'{version_long}',
										'{class}',
										'{appkey}',
										'{pluginid}'
								),
								array(
										$values['dev_widget_key'],
										( $subpackage != 'core' ) ? ( " * @subpackage\t" . $subpackage . "\n" ) : '',
										date( 'd M Y' ),
										$namespace,
										$version,
										$values['dev_widget_class'],
										is_string( $appKeyOrPluginId ) ? $appKeyOrPluginId : NULL,
										is_numeric( $appKeyOrPluginId ) ? $appKeyOrPluginId : NULL,
								),
								file_get_contents( \IPS\ROOT_PATH . "/applications/core/data/defaults/Widget.txt" )
						) );
					}
						
					/* Add to DB */
					\IPS\Db::i()->replace( 'core_widgets', array(
							'app'			=> is_string( $appKeyOrPluginId ) ? $appKeyOrPluginId : NULL,
							'plugin'		=> is_numeric( $appKeyOrPluginId ) ? $appKeyOrPluginId : NULL,
							'key'			=> $values['dev_widget_key'],
							'class'			=> $values['dev_widget_class'],
							'restrict'		=> ( ! count( $values['dev_widget_restrict'] ) ? FALSE : json_encode( array_values( $values['dev_widget_restrict'] ) ) ),
							'default_area'	=> ( $values['dev_widget_default_area'] === 'none' ) ? '' : $values['dev_widget_default_area'],
							'allow_reuse'	=> $values['dev_widget_allow_reuse'],
							'menu_style'    => $values['dev_widget_menu_style'],
					        'embeddable'    => $values['dev_widget_embeddable']
					) );
					unset( \IPS\Data\Store::i()->widgets );
						
					/* Add to JSON file */
					$widgets = json_decode( file_get_contents( $json ), TRUE );
					$widgets[ $values['dev_widget_key'] ] = array(
						'class'    	   => $values['dev_widget_class'],
						'restrict' 	   => ( ! count( $values['dev_widget_restrict'] ) ? FALSE : array_values( $values['dev_widget_restrict'] ) ),
						'default_area' => ( $values['dev_widget_default_area'] === 'none' ) ? '' : $values['dev_widget_default_area'],
						'allow_reuse'  => $values['dev_widget_allow_reuse'],
						'menu_style'   => $values['dev_widget_menu_style'],
						'embeddable'   => $values['dev_widget_embeddable']
					);
					
					\IPS\Application::writeJson( $json, $widgets );
						
					/* Redirect */
					\IPS\Output::i()->redirect( $url, 'saved' );
				}
	
				return $form;
	
			case 'delete':
	
				$widgets = json_decode( file_get_contents( $json ), TRUE );
				if ( array_key_exists( \IPS\Request::i()->key, $widgets ) )
				{
					unset( $widgets[ \IPS\Request::i()->key ] );
					\file_put_contents( $json, json_encode( $widgets ) );
						
					if ( file_exists( $widgetDirectory . "/" . \IPS\Request::i()->key . ".php" ) )
					{
						unlink( $widgetDirectory . "/" . \IPS\Request::i()->key . ".php" );
					}
						
					\IPS\Db::i()->delete( 'core_widgets', array( ( is_string( $appKeyOrPluginId ) ? 'app' : 'plugin' ) . '=? AND `key`=?', $appKeyOrPluginId, \IPS\Request::i()->key ) );
					unset( \IPS\Data\Store::i()->widgets );
				}
				\IPS\Output::i()->redirect( $url, 'saved' );
					
			default:
	
				$data = array();
				foreach ( json_decode( file_get_contents( $json ), TRUE ) as $k => $json )
				{
					$data[ $k ] = array(
							'dev_widget_key'		=> $k,
							'dev_widget_class'		=> $json['class'],
							'dev_widget_restrict' 	=> $json['restrict'] === FALSE ? \IPS\Member::loggedIn()->language()->addToStack('dev_widget_nowhere') : ( ( ( count( $json['restrict'] ) > 0 and count( $json['restrict'] ) !== 2 ) ? implode( ',', array_map( function( $val ) { return \IPS\Member::loggedIn()->language()->addToStack('dev_widget_restrict_'.$val); }, $json['restrict'] ) ) : \IPS\Member::loggedIn()->language()->addToStack('everywhere') ) ),
							'dev_widget_area'		=> isset($json['default_area']) ? ( $json['default_area'] ? \IPS\Member::loggedIn()->language()->addToStack( 'dev_widget_default_area_' . $json['default_area'] ) : \IPS\Member::loggedIn()->language()->addToStack('none') ) : 'sidebar',
							'dev_widget_embeddable'	=> isset($json['embeddable'])   ? ( $json['embeddable'] ? '&#10003;' : '&#10007;' ) : '&#10007;'
					);
				}
	
				$table = new \IPS\Helpers\Table\Custom( $data, $url );
				$table->rootButtons = array(
						'add' => array(
								'icon'	=> 'plus',
								'title'	=> 'add',
								'link'	=> $url->setQueryString( 'widgetTable', 'form' ),
								'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add') )
						)
				);
				$table->rowButtons = function( $row ) use ( $url )
				{
					return array(
							'edit' => array(
									'icon'	=> 'pencil',
									'title'	=> 'edit',
									'link'	=> $url->setQueryString( 'widgetTable', 'form' )->setQueryString( 'key', $row['dev_widget_key'] ),
									'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit') )
							),
							'delete' => array(
									'icon'	=> 'times-circle',
									'title'	=> 'delete',
									'link'	=> $url->setQueryString( 'widgetTable', 'delete' )->setQueryString( 'key', $row['dev_widget_key'] ),
									'data'	=> array( 'delete' => '' )
							)
					);
				};
	
				return $table;
					
		}
	}
	
	/**
	 * Get all cache keys (for all possible permissions, etc.)
	 *
	 * @param	String	$key				Widget key
	 * @param	String	$app				Parent application
	 * @param	String	$plugin				Parent plugin
	 * @return	array
	 * @note	This method does not take responsibility for checking if caches are expired
	 */
	public static function getCaches( $key=NULL, $app=NULL, $plugin=NULL )
	{
		$caches = array();

		try
		{
			foreach( \IPS\Db::i()->select( '*', 'core_widgets', static::_buildWhere( $key, $app, $plugin ) ) as $widget )
			{
				if( $widget['caches'] )
				{
					$json = json_decode( $widget['caches'], TRUE );
					
					if ( ! is_array( $json ) )
					{
						return array();
					}
					
					foreach ( $json as $cKey => $time )
					{
						$caches[ $cKey ] = $time;
					}
				}
			}
			
		}
		catch ( \UnderflowException $e )
		{
		}

		return $caches;
	}

	
	/**
	 * Delete caches
	 *
	 * @param	String	$key				Widget key
	 * @param	String	$app				Parent application
	 * @param	String	$plugin				Parent plugin
	 * @return	void
	 */
	public static function deleteCaches( $key=NULL, $app=NULL, $plugin=NULL )
	{
		$caches = static::getCaches( $key, $app, $plugin );

		foreach ( $caches as $cacheKey => $time )
		{
			unset( \IPS\Data\Store::i()->$cacheKey );
		}

		\IPS\Db::i()->update( 'core_widgets', array( 'caches' => NULL ), static::_buildWhere( $key, $app, $plugin ) );
		unset( \IPS\Data\Store::i()->widgets );
	}

	/**
	 * Store this widget instance as trash incase we need to fetch the configuration
	 * when another column is updated due to a widget being moved from one column to another.
	 *
	 * @param   string  $uniqueId       Widget's Unique ID
	 * @param   array   $data           Widget Data
	 * @return  void
	 */
	public static function trash( $uniqueId, $data )
	{
		\IPS\Db::i()->delete( 'core_widget_trash', array( 'id=?', $uniqueId ) );
		\IPS\Db::i()->insert( 'core_widget_trash', array(
			'id'    => $uniqueId,
		    'data'  => json_encode( $data ),
		    'date'  => time()
		) );
	}

	/**
	 * Fetch the configuration for this unqiue ID. Looks in active tables and trash. When a widget is moved, saveOrder is called twice,
	 * once to remove the widget from column A and again to add it to column B. We store the widget removed from column A into the trash
	 * table.
	 *
	 * @param   string  $uniqueId   Widget's unique ID
	 * @return  array
	 */
	public static function getConfiguration( $uniqueId )
	{
		foreach( \IPS\Db::i()->select( '*', 'core_widget_areas' ) as $item )
		{
			$widgets = json_decode( $item['widgets'], TRUE );

			foreach( $widgets as $widget )
			{
				if ( $widget['unique'] == $uniqueId )
				{
					if ( isset( $widget['configuration'] ) )
					{
						return $widget['configuration'];
					}
				}
			}
		}

		/* Still here? rummage in the trash */
		try
		{
			$widget = \IPS\Db::i()->select( '*', 'core_widget_trash', array( 'id=?', $uniqueId ) )->first();

			$data = json_decode( $widget['data'], TRUE );

			if ( isset( $data['configuration'] ) )
			{
				return $data['configuration'];
			}
		}
		catch( \UnderflowException $ex ) { }

		return array();
	}	
	
	/**
	 * Get default widgets for an application
	 *
	 * @param	\IPS\Application	$app	The application
	 * @return	array
	 */
	public static function appDefaults( \IPS\Application $app )
	{
		if ( !isset( \IPS\Data\Store::i()->widgets ) )
		{
			$widgets = array();
			foreach ( \IPS\Db::i()->select( '*', 'core_widgets' ) as $row )
			{
				if ( $row['app'] )
				{
					$widgets[ $row['app'] ][ $row['key'] ] = $row;
				}
			}
			
			\IPS\Data\Store::i()->widgets = $widgets;
		}
		
		$return = array();
		if ( $app->directory != 'core' and isset( \IPS\Data\Store::i()->widgets['core']['announcements'] ) )
		{
			$return[] = \IPS\Data\Store::i()->widgets['core']['announcements'];
		}
		if ( isset( \IPS\Data\Store::i()->widgets[ $app->directory ] ) )
		{
			foreach ( \IPS\Data\Store::i()->widgets[ $app->directory ] as $widget )
			{
				if ( $widget['default_area'] )
				{
					$return[] = $widget;
				}
			}
		}
		
		return $return;
	}

	/**
	 * @brief	Cached output to prevent rendering widget twice
	 */
	protected $cachedOutput	= NULL;
	
	/**
	 * Convert the widget to HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		/* Wrap the whole thing in a try/catch because exceptions in __toString confuses PHP */
		try
		{
			/* Put the app check here as it needs to check the member's secondary groups but the PermissionCache only stores the primary group IDs */
			if ( $this->app )
			{
				if ( ! $this->application()->canAccess( \IPS\Member::loggedIn() ) )
				{
					return '';
				}
			}
							
			/* If we're not caching (e.g. dynamic blocks in Pages), just return it */
			if ( $this->neverCache === TRUE )
			{
				return static::parseOutput( $this->render() );
			}
						
			/* Otherwise, figure out what to display. Saved in $this->cachedOutput so if this is being used twice on the same page, we only do this once */
			if ( $this->cachedOutput === NULL )
			{
				/* Does this go in the store? Things like active users don't get stored, and if in developer or designer mode, nothing does */
				if ( isset( $this->cacheKey ) AND ( !isset( \IPS\Request::i()->cookie['vle_editor'] ) or !\IPS\Request::i()->cookie['vle_editor'] ) AND !\IPS\IN_DEV AND !\IPS\Theme::designersModeEnabled() )
				{		
					/* How long does the store last (in seconds)? Individual widgets can set a higher expiration where sensible (e.g. upcoming calendar events
						can be cached for a day regardless of this setting) but not lower */
					$expiration = \IPS\Settings::i()->widget_cache_ttl;
					if ( isset( $this->cacheExpiration ) and $this->cacheExpiration > $expiration )
					{
						$expiration = $this->cacheExpiration;
					}
					
					/* If we have the TTL set to 0, don't bother with the store */
					if ( $expiration )
					{							
						/* Add/update in the store if it isn't there or it's expired */
						$cacheKey = $this->cacheKey;
						if ( !isset( \IPS\Data\Store::i()->$cacheKey ) or ( $widget = \IPS\Data\Store::i()->$cacheKey and $widget['built'] < ( time() - $expiration ) and static::$expirePerRequest-- ) )
						{
							/* The render() call below may take a long time to run for some widgets - we don't want lots of users to call
								it simultaneously, so save a blank widget for now. For a second or two (until we've built and stored
								the correct output which is done right after calling render) users will see nothing, which isn't ideal
								but is better than killing the server */
							\IPS\Data\Store::i()->$cacheKey = array( 'built' => time(), 'html' => '' );
							
							/* Render and store */
							$content = $this->render();
							\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $content );
							\IPS\Data\Store::i()->$cacheKey = array( 'built' => time(), 'html' => $content ); // Corrects the blank output written above
							
							/* Log that cache key so if we need to delete all the caches for this widget later we have it */
							$caches = static::getCaches( $this->key, $this->app, $this->plugin );
							
							foreach( $caches as $key => $timeBuilt )
							{
								if ( $key === $cacheKey )
								{
									continue;
								}
								
								if ( $timeBuilt < ( time() - $expiration ) )
								{
									if ( isset( \IPS\Data\Store::i()->$key ) )
									{
										unset( \IPS\Data\Store::i()->$key );
									}
			
									unset( $caches[ $key ] );
								}
							}
							
							$caches[ $cacheKey ] = time();
							\IPS\Db::i()->update( 'core_widgets', array( 'caches' => json_encode( $caches ) ), static::_buildWhere( $this->key, $this->app, $this->plugin ) );
						}
						
						/* Then use what the store has */
						$widget = \IPS\Data\Store::i()->$cacheKey;
						$this->cachedOutput = $widget['html'];
					}
				}
				
				/* If we still don't have anything, go ahead and render */
				if( $this->cachedOutput === NULL )
				{
					$this->cachedOutput = $this->render();
					\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $this->cachedOutput );
				}
			}
			
			/* And render */
			return static::parseOutput( $this->cachedOutput );
		}
		catch ( \Exception $e )
		{
			\IPS\IPS::exceptionHandler( $e );
		}
		catch ( \Throwable $e )
		{
			\IPS\IPS::exceptionHandler( $e );
		}
	}

	/**
	 * Parse <time> tags to avoid caching with another's timezone
	 *
	 * @param  string $output HTML code which may contain the tag
	 * @return string
	 */
	public static function parseOutput( $output )
	{
		if ( mb_stristr( $output, '<time' ) )
		{
			$output = preg_replace_callback( '#<time([^>]+?)?>(\d{10})</time>#i', function( $matches )
			{
				$options = array();
				
				if ( $matches[1] )
				{
					preg_match_all( '#(\S+?)=["\'](.+?)["\']\s?#', $matches[1], $submatches, PREG_SET_ORDER );

					foreach( $submatches as $idx => $data )
					{
						$options[ str_replace( 'data-', '', $data[1] ) ] = $data[2];	
					}
				}
				
				$obj = \IPS\DateTime::ts( $matches[2] );
				$val = $obj->html();
				
				if ( isset( $options['dateonly'] ) )
				{
					$val = (string) $obj->localDate();	
				}
				else if ( isset( $options['norelative'] ) )
				{
					$val = (string) $obj;
				}
			
				return $val;
				
			}, $output );
		}
		
		return $output;
	}
	
	/**
	 * Empty the widget trash
	 *
	 * @param	int	$seconds	Seconds old to remove
	 * @return	void
	 */
	public static function emptyTrash( $seconds=86400 )
	{
		foreach( \IPS\Db::i()->select( '*', 'core_widget_trash', array( array( 'date < ?', time() - $seconds ) ) ) as $row )
		{
			$data = json_decode( $row['data'], TRUE );
			
			if ( ! empty( $data['app'] ) and ! empty( $data['key'] ) and ! empty( $data['unique'] ) )
			{
				try
				{
					$widget = static::load( \IPS\Application::load( $data['app'] ), $data['key'], $data['unique'], isset( $data['configuration'] ) ? $data['configuration'] : NULL );
					$widget->delete();
				}
				catch( \Exception $ex ) { }
			}
		}
		
		\IPS\Db::i()->delete( 'core_widget_trash', array( 'date < ?', time() - $seconds ) );
	}
	
	/**
	 * Build the where clause based on key, app, plugin
	 *
	 * @param	string	$key	Key
	 * @param	string	$app	Application
	 * @param	string	$plugin	Plugin
	 * @return	array
	 */
	protected static function _buildWhere( $key, $app, $plugin )
	{
		$where = array();
		
		if( $key )
		{
			$where[] = array( '`key`=?', (string) $key );
		}

		if( $app )
		{
			$where[] = array( 'app=?', (string) $app );
		}

		if( $plugin )
		{
			$where[] = array( 'plugin=?', (string) $plugin );
		}

		return $where;
	}
}