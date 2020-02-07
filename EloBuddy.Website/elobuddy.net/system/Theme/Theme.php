<?php
/**
 * @brief		Skin Set
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		16 Apr 2013
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
 * Skin set
 */
class _Theme extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_themes';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'set_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[Node] Parent Node ID Database Column
	 */
	public static $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'order';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'menu__core_customization_themes';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'core_theme_set_title_';
	
	/**
	 * @brief	IN_DEV "theme"
	 */
	protected static $inDevTheme = NULL;
	
	/**
	 * @brief	Setup "theme"
	 */
	protected static $setupSkin = NULL;
	
	/**
	 * @brief	Member's "theme"
	 */
	public static $memberTheme = NULL;
	
	/**
	 * @brief	[SkinSets] Store theme set parent/id relationship for parent/child recursion
	 */
	public static $themeSetRelationships = array();
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll = FALSE;
	
	/**
	 * @brief	[SkinSets] Stores the default theme set id
	 */
	public static $defaultFrontendThemeSet = 0;
	
	/**
	 * @brief	[SkinSets] Stores the default ACP theme set id
	 */
	public static $defaultAcpThemeSet = 0;
	
	/**
	 * @brief	[SkinSets] Templates already loaded and evald via getTemplate()
	 */
	public static $calledTemplates = array();
	
	/**
	 * @brief	[SkinSets] Some CSS files are built from a directory to save on http requests. They are saved as {$location}_{$folder}.css (so front_responsive.css for example)
	 */
	protected static $buildGrouping = array(
			'css'  => array(
				'core' => array(
					'global'=> array( 'framework', 'responsive' ),
					'front' => array( 'custom' ),
					'admin' => array( 'core', 'responsive' )
					)
				)
			);
	
	/**
	 * @brief	Return type for getRawTemplates/getRawCss: Return all
	 */
	const RETURN_ALL = 1;
	
	/**
	 * @brief	Return type for getRawTemplates/getRawCss: Return groups and names as a tree
	 */
	const RETURN_BIT_NAMES = 2;
	
	/**
	 * @brief	Return type for getRawTemplates/getRawCss: Return groups and names as a tree with array of data without content
	 */
	const RETURN_ALL_NO_CONTENT = 4;
	
	/**
	 * @brief	Return type for getRawTemplates/getRawCss: Returns bit names as a flat array
	 */
	const RETURN_ARRAY_BIT_NAMES = 8;
	
	/**
	 * @brief	Return type for getRawTemplates/getRawCss: Uses DB if not IN_DEV, otherwise uses disk .phtml look up
	 */
	const RETURN_NATIVE = 16;

	/**
	 * @brief	Type for templates
	 */
	const TEMPLATES = 1;
	
	/**
	 * @brief	Type for CSS
	 */
	const CSS = 2;
	
	/**
	 * @brief	Type for Images
	 */
	const IMAGES = 4;
	
	/**
	 * @brief Bit option for theme settings
	 */
	const THEME_KEY_VALUE_PAIRS = 1;
	
	/**
	 * @brief Bit option for theme settings
	 */
	const THEME_ID_KEY = 2;
	
	/**
	 * Get currently logged in member's theme
	 *
	 * @return	\IPS\Theme
	 */
	public static function i()
	{
		if ( \IPS\Dispatcher::hasInstance() AND class_exists( '\IPS\Dispatcher', FALSE ) and \IPS\Dispatcher::i()->controllerLocation === 'setup' )
		{
			if ( static::$setupSkin === NULL )
			{
				static::$setupSkin = new \IPS\Theme\Setup\Theme;
			}
			return static::$setupSkin;
		}
		else if ( \IPS\Theme::designersModeEnabled() )
		{
			if ( \IPS\IN_DEV )
			{
				die("Please disable IN_DEV while in Designer's Mode");
			}
			if ( static::$memberTheme === NULL )
			{
				static::themes();
		
				static::$memberTheme = new \IPS\Theme\Advanced\Theme;

				/* Add in the default theme properties (_data array, etc) */
				foreach( static::$multitons[ \IPS\Theme\Advanced\Theme::$currentThemeId ] as $k => $v )
				{
					static::$memberTheme->$k = $v;
				}
			}

			return static::$memberTheme;
		}
		else if ( \IPS\IN_DEV )
		{
			if ( static::$inDevTheme === NULL )
			{
				static::$inDevTheme = new \IPS\Theme\Dev\Theme;
		
				static::themes();
		
				/* Add in the default theme properties (_data array, etc) */
				$default = ( isset( static::$multitons[1] ) ) ? static::$multitons[1] : reset( static::$multitons );

				foreach( $default as $k => $v )
				{
					static::$inDevTheme->$k = $v;
				}
			}
			
			return static::$inDevTheme;
		}
		else
		{
			if ( static::$memberTheme === NULL )
			{
				static::themes();

				$column	= 'skin';

				if( \IPS\Dispatcher::hasInstance() )
				{
					$column	= ( \IPS\Dispatcher::i()->controllerLocation == 'admin' ) ? 'acp_skin' : 'skin';
				}
				
				$setId = NULL;
				if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation == 'front' )
				{
					$setId = \IPS\Session\Front::i()->getTheme();
					if ( $setId )
					{
						if( ! \IPS\Request::i()->isAjax() )
						{
							/* Not an ajax call, so reset theme_id */
							$setId = NULL;
							\IPS\Session\Front::i()->setTheme(0);
						}
						else
						{
							try
							{
								if ( static::load( $setId )->canAccess() !== true )
								{
									$setId = NULL;
								}
							} catch ( \OutOfRangeException $ex ){}
						}

					}
				}
				if ( ! $setId and \IPS\Member::loggedIn()->$column and array_key_exists( \IPS\Member::loggedIn()->$column, static::themes() ) )
				{
					$setId = \IPS\Member::loggedIn()->$column;
						
					if ( static::load( $setId )->canAccess() !== true )
					{
						$setId = ( $column == 'skin' ? static::defaultTheme() : static::defaultAcpTheme() );
						
						/* Restore default theme for member */
						\IPS\Member::loggedIn()->$column = $setId;

						if( \IPS\Member::loggedIn()->member_id )
						{
							\IPS\Member::loggedIn()->save();
						}
					}
				}
				else if ( ! $setId )
				{
					$setId = ( $column == 'skin' ? static::defaultTheme() : static::defaultAcpTheme() );
				}
				
				if ( isset( \IPS\Request::i()->cookie['vseThemeId'] ) AND \IPS\Member::loggedIn()->isAdmin() AND \IPS\Member::loggedIn()->members_bitoptions['bw_using_skin_gen'] )
				{
					$setId = intval( \IPS\Request::i()->cookie['vseThemeId'] );
					
					if ( ! empty( $setId ) )
					{
						$ok = false;
						
						try
						{
							$theme = static::load( $setId );
							
							if ( $theme->by_skin_gen )
							{
								$ok = true;
							}
						}
						catch( \OutOfRangeException $ex )
						{
							$ok = false;
						}	
						
						if ( $ok !== true )
						{
							/* Update the current member */
							\IPS\Member::loggedIn()->members_bitoptions['bw_using_skin_gen'] = 0;
							\IPS\Member::loggedIn()->save();
							
							\IPS\Request::i()->setCookie( 'vseThemeId', 0 );
						
							$setId = static::defaultTheme();
						}
					}
					else
					{
						$setId = static::defaultTheme();
					}
				}
				
				static::$memberTheme = static::load( $setId );
			}
			
			return static::$memberTheme;
		}
	}
	
	/**
	 * Themes
	 *
	 * @return	array
	 */
	public static function themes()
	{
		if ( !static::$gotAll )
		{
			static::$gotAll = true;
			static::$themeSetRelationships = array();
			
			if ( isset( \IPS\Data\Store::i()->themes ) )
			{
				$rows = \IPS\Data\Store::i()->themes;
			}
			else
			{
				$rows = iterator_to_array( \IPS\Db::i()->select( '*', 'core_themes', NULL, 'set_order' )->setKeyField('set_id') );
				\IPS\Data\Store::i()->themes = $rows;
			}
			
			foreach( $rows as $id => $theme )
			{
				if ( $theme['set_is_default'] )
				{
					static::$defaultFrontendThemeSet = $theme['set_id'];
				}
				if ( isset( $theme['set_is_acp_default'] ) and $theme['set_is_acp_default'] )
				{
					static::$defaultAcpThemeSet = $theme['set_id'];
				}
				
				static::$themeSetRelationships[ $theme['set_parent_id'] ][ $theme['set_id'] ] = $theme;
				
				static::$multitons[ $theme['set_id'] ] = static::constructFromData( $theme );
			}
		}
		return static::$multitons;
	}

	/**
	 * Returns only the visible themes for the member
	 *
	 * @return array|bool
	 */
	public static function getThemesWithAccessPermission()
	{
		$visibleThemes = array();

		foreach ( static::themes() AS $themeId => $theme )
		{
			if ( $theme->canAccess() )
			{
				$visibleThemes[$theme->id] = $theme;
			}
		}

		return $visibleThemes;
	}
	
	/**
	 * Fetch the master theme object
	 *
	 * @return	\IPS\Theme
	 */
	public static function master()
	{
		static::themes();
		$default = static::$multitons[ static::$defaultFrontendThemeSet ];
		return static::constructFromData( array_merge( static::$themeSetRelationships[ $default->parent_id ][ $default->id ], array( 'set_id' => 0 ) ) );
	}

	/**
	 * Is designer's mode enabled?
	 *
	 * @return boolean
	 */
	public static function designersModeEnabled()
	{
		return (boolean) \IPS\Settings::i()->theme_designers_mode;
	}

	/**
	 * Default Frontend Skin Set ID
	 *
	 * @return	int
	 */
	public static function defaultTheme()
	{
		if ( !static::$gotAll )
		{
			static::themes();
		}
	
		return static::$defaultFrontendThemeSet;
	}
	
	/**
	 * Default ACP Skin Set ID
	 *
	 * @return	int
	 */
	public static function defaultAcpTheme()
	{
		if ( !static::$gotAll )
		{
			static::themes();
		}
	
		return static::$defaultAcpThemeSet ?: static::$defaultFrontendThemeSet;
	}

	/**
	 * Switches the currently initialized theme during execution
	 *
	 * @param   int     $themeId        Id of the theme to switch to
	 * @param	boolean	$persistent		Allow to persist through ajax calls
	 * @return  boolean
	 * @note    This will not check to ensure the member has permission to view the theme
	 */
	public static function switchTheme( $themeId, $persistent=TRUE )
	{
		static::themes();

		try
		{
			$class = get_class( static::$memberTheme );
			static::$memberTheme = $class::load( $themeId );
			
			/* Store the ID in sessions so ajax loads correct theme */
			if ( $persistent )
			{
				\IPS\Session\Front::i()->setTheme( $themeId );
			}
			
			/* Flush loaded CSS */
			\IPS\Output::i()->cssFiles = array();

			\IPS\Dispatcher\Front::baseCss();

			/* App CSS */
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( \IPS\Dispatcher\Front::i()->application->directory . '.css', \IPS\Dispatcher\Front::i()->application->directory, \IPS\Dispatcher\Front::i()->controllerLocation ) );
			if ( \IPS\Theme::i()->settings['responsive'] )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( \IPS\Dispatcher\Front::i()->application->directory . '_responsive.css', \IPS\Dispatcher\Front::i()->application->directory, \IPS\Dispatcher\Front::i()->controllerLocation ) );
			}
		}
		catch( \OutOfRangeException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Get CSS
	 * This method is used to return the built CSS stored in the file objects system
	 *
	 * @param	string		$file		Filename
	 * @param	string|null	$app		Application
	 * @param	string|null	$location	Location (e.g. 'admin', 'front')
	 * @return	array		URLs to CSS files
	 */
	public function css( $file, $app=NULL, $location=NULL )
	{
		$app      = $app      ?: \IPS\Request::i()->app;
		$location = $location ?: \IPS\Dispatcher::i()->controllerLocation;
		$paths    = explode( '/', $file );
		$name     = array_pop( $paths );
		$path     = ( count( $paths ) ) ? implode( '/', $paths ) : '.';
				
		if ( $location === 'interface' )
		{
			return array( rtrim( \IPS\Http\Url::baseUrl( \IPS\Http\Url::PROTOCOL_RELATIVE ), '/' ) . "/applications/{$app}/interface/{$file}" );
		}

		$key = static::makeBuiltTemplateLookupHash( $app, $location, $path . '/' . $name );

		if ( in_array( $key, array_keys( $this->css_map ) ) )
		{
			if ( $this->css_map[ $key ] !== null )
			{
				return array( \IPS\File::get( 'core_Theme', $this->css_map[ $key ] )->url );
			}
			else
			{
				return array();
			}
		}
		else
		{
			/* We're setting up, do nothing to avoid compilation requests when tables are incomplete */
			if ( ! isset( \IPS\Settings::i()->setup_in_progress ) OR \IPS\Settings::i()->setup_in_progress )
			{
				return array();
			}
			
			/* Map doesn't exist, try and create it */
			if ( $this->compileCss( $app, $location, $path, $name ) === NULL )
			{
				/* Still building */
				return array();
			}
			
			/* Still not here? Then add a key but as null to prevent it from attempting to rebuild on every single page
			 * load thus hitting the DB multiple times */
			$cssMap = $this->css_map;
			if ( ! in_array( $key, array_keys( $this->css_map ) ) )
			{
				$cssMap[ $key ] = null;
				
				$this->css_map = $cssMap;
				$this->save();
			}
			else
			{
				return array( \IPS\File::get( 'core_Theme', $this->css_map[ $key ] )->url );
			}
		}
		
		return array();
	}

	/**
	 * Get Theme Resource (image, font, theme-specific JS, etc)
	 *
	 * @param	string		$path		Path to resource
	 * @param	string|null	$app		Application key
	 * @param	string|null	$location	Location
	 * @return	string		URL to resource
	 */
	public function resource( $path, $app=NULL, $location=NULL, $noProtocol=FALSE )
	{
		$app      = $app      ?: \IPS\Request::i()->app;
		$location = $location ?: \IPS\Dispatcher::i()->controllerLocation;
		$paths    = explode( '/', $path );
		$name     = array_pop( $paths );
		$path     = ( count( $paths ) ) ? ( '/' . implode( '/', $paths ) . '/' ) : '/';
		$key      = static::makeBuiltTemplateLookupHash($app, $location, $path) . '_' .$name;
				
		if ( $location === 'interface' )
		{
			return rtrim( \IPS\Http\Url::baseUrl( \IPS\Http\Url::PROTOCOL_RELATIVE ), '/' ) . "/applications/{$app}/interface/{$path}";
		}
				
		if ( in_array( $key, array_keys( $this->resource_map ) ) )
		{
			if ( $this->resource_map[ $key ] === NULL )
			{
				return NULL;
			}
			else
			{
				$url = \IPS\File::get( 'core_Theme', $this->resource_map[ $key ] )->url;

				if( $noProtocol )
				{
					$url = str_replace( array( 'http://', 'https://' ), '//', $url );
				}

				return $url;
			}
		}
		
		/* Still here? Map doesn't exist, try and create it */
		$resourceMap = $this->resource_map;
		
		try
		{
			/* We're setting up, do nothing to avoid compilation requests when tables are incomplete */
			if ( ! isset( \IPS\Settings::i()->setup_in_progress ) OR \IPS\Settings::i()->setup_in_progress )
			{
				return NULL;
			}
			
			$flagKey  = 'resource_compiling_' . $this->_id . '_' . md5( $app . '_' . $location . '_' . $path );
			
			if ( isset( \IPS\Data\Store::i()->$flagKey ) and ! empty( \IPS\Data\Store::i()->$flagKey ) )
			{
				/* We're currently rebuilding */
				if ( time() - \IPS\Data\Store::i()->$flagKey < 30  )
				{
					return NULL;
				}
			}
	
			$resource = \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? AND resource_app=? AND resource_location=? AND resource_path=? AND resource_name=?', $this->id, $app, $location, $path, $name ) )->first();
		
			$resourceMap[ $key ] = (string) \IPS\File::create( 'core_Theme', $key, $resource['resource_data'], 'set_resources_' . $this->id, FALSE, NULL, FALSE );
			
			\IPS\Db::i()->update( 'core_theme_resources', array( 'resource_added' => time(), 'resource_filename' => $resourceMap[ $key ] ), array( 'resource_id=?', $resource['resource_id'] ) );
			
			/* Save map */
			$this->resource_map = $resourceMap;
			$this->save();
			
			unset( \IPS\Data\Store::i()->$flagKey );

			$url = \IPS\File::get( 'core_Theme', $resourceMap[ $key ] )->url;

			if( $noProtocol )
			{
				$url = str_replace( array( 'http://', 'https://' ), '//', $url );
			}

			return $url;
		}
		catch( \UnderflowException $e )
		{
			/* Doesn't exist, add null entry to map to prevent it from being rebuilt on each page load */
			$resourceMap[ $key ] = null;
			
			/* Save map */
			$this->resource_map = $resourceMap;
			$this->save();
			
			return NULL;
		}
	}
	
	/**
	 * Get a template
	 *
	 * @param	string	$group				Template Group
	 * @param	string	$app				Application key (NULL for current application)
	 * @param	string	$location		    Template Location (NULL for current template location)
	 * @return	\IPS\Output\Template
	 * @throws	\UnexpectedValueException
	 */
	public function getTemplate( $group, $app=NULL, $location=NULL )
	{
		/* Do we have an application? */
		if( $app === NULL )
		{
			$app = \IPS\Dispatcher::i()->application->directory;
		}
			
		/* How about a template location? */
		if( $location === NULL )
		{
			$location = \IPS\Dispatcher::i()->controllerLocation;
		}

		$key = \strtolower( 'template_' . $this->id . '_' . static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . static::cleanGroupName( $group ) );

		/* Still here */
		/* We cannot use isset( static::$calledTemplates[ $key ] ) here because it fails with NULL while in_array does not */
		if ( !in_array( $key, array_keys( static::$calledTemplates ) ) )
		{
			/* If we don't have a compiled template, do that now */
			if ( !isset( \IPS\Data\Store::i()->$key ) )
			{
				if ( $this->compileTemplates( $app, $location, $group ) === NULL )
				{
					/* Rebuild in progress */
					\IPS\Log::log( "Template store key: {$key} rebuilding and requested again ({$app}, {$location}, {$group})", "template_store_building" );

					/* Since we can't do anything else, this ends up just being an uncaught exception - show the error page right away
						to avoid the unnecessary logging */
					//throw new \ErrorException( 'templates_already_rebuilding' );
					\IPS\IPS::genericExceptionPage();
				}
			}

			/* Still no key? */
			if ( ! isset( \IPS\Data\Store::i()->$key ) )
			{
				\IPS\Log::log( "Template store key: {$key} missing ({$app}, {$location}, {$group})", "template_store_missing" );

				throw new \ErrorException( 'template_store_missing' );
			}

			/* Load compiled template */
			$compiledGroup = \IPS\Data\Store::i()->$key;
			
			if ( \IPS\DEBUG_TEMPLATES )
			{
				static::runDebugTemplate( $key, $compiledGroup );
			}
			else
			{
				try
				{
					if ( @eval( $compiledGroup ) === FALSE )
					{
						throw new \UnexpectedValueException;
					}
				}
				catch ( \ParseError $e )
				{
					throw new \UnexpectedValueException;
				}
			}
			
			/* Hooks */
			$class = 'class_' . $app . '_' . $location . '_' . $group;
			if ( isset( \IPS\IPS::$hooks[ "\\IPS\\Theme\\class_{$app}_{$location}_{$group}" ] ) )
			{
				foreach ( \IPS\IPS::$hooks[ "\\IPS\\Theme\\class_{$app}_{$location}_{$group}" ] as $id => $data )
				{
					if ( file_exists( \IPS\ROOT_PATH . '/' . $data['file'] ) )
					{
						if ( class_exists( "IPS\\Theme\\{$data['class']}", FALSE ) )
						{
							$class = $data['class'];
							continue;
						}

						$contents = "namespace IPS\\Theme;\n\n" . str_replace( '_HOOK_CLASS_', $class, file_get_contents( \IPS\ROOT_PATH . '/' . $data['file'] ) );

						try
						{
							if( eval( $contents ) !== FALSE )
							{
								$class = $data['class'];
							}
						}
						catch ( \ParseError $e ) { }
					}
				}
			}
			$class = "\\IPS\\Theme\\{$class}";

			/* Init */
			static::$calledTemplates[ $key ] = new \IPS\Theme\SandboxedTemplate( new $class( $app, $location, $group ) );
		}

		return static::$calledTemplates[ $key ];
	}
	
	/*! Active Record */
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );

		/* Extra set up */
		$logo = json_decode( $obj->logo_data, true );
		
		$obj->_data['logo'] = array( 'front' => null, 'sharer' => null, 'favicon' => null );
		
		if ( is_array( $logo ) )
		{
			foreach( array( 'front', 'sharer', 'favicon' ) as $type )
			{
				if ( isset( $logo[ $type ] ) )
				{
					$obj->_data['logo'][ $type ] = $logo[ $type ];
				}
			}
		}

		if ( $settings = json_decode( $obj->_data['template_settings'], true ) )
		{
			$obj->_data['settings'] = $settings;
		}
		else
		{
			/* No settings here */
			$obj->_data['settings'] = array();
		}
		
		if ( ! is_array( $obj->_data['resource_map'] ) )
		{
			if ( $imgMap = json_decode( $obj->_data['resource_map'], true ) )
			{
				$obj->_data['resource_map'] = $imgMap;
			}
			else
			{
				$obj->_data['resource_map'] = array();
			}
		}
		
		if ( ! is_array( $obj->_data['css_map'] ) )
		{
			if ( $cssMap = json_decode( $obj->_data['css_map'], true ) )
			{
				$obj->_data['css_map'] = $cssMap;
			}
			else
			{
				$obj->_data['css_map'] = array();
			}
		}

		return $obj;
	}
	
	/**
	 * Save resource map
	 *
	 * @param	$value		array	Value to save
	 */
	public function set_resource_map( $value )
	{
		if ( is_array( $value ) )
		{
			$this->_data['resource_map'] = json_encode( $value );
		}
	}
	
	/**
	 * Save CSS map
	 *
	 * @param	$value		array	Value to save
	 */
	public function set_css_map( $value )
	{
		if ( is_array( $value ) )
		{
			$this->_data['css_map'] = json_encode( $value );
		}
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	function save()
	{
		if ( ! $this->editor_skin )
		{
			$this->editor_skin = 'ips';
		}
		
		parent::save();
		unset( \IPS\Data\Store::i()->themes );
		
		/* Reset map arrays */
		if ( !isset( $this->_data['resource_map'] ) OR  ! is_array( $this->_data['resource_map'] ) )
		{
			if ( isset( $this->_data['resource_map'] ) AND $imgMap = json_decode( $this->_data['resource_map'], true ) )
			{
				$this->_data['resource_map'] = $imgMap;
			}
			else
			{
				$this->_data['resource_map'] = array();
			}
		}
		
		if ( !isset( $this->_data['css_map'] ) OR ! is_array( $this->_data['css_map'] ) )
		{
			if ( isset( $this->_data['css_map'] ) AND $cssMap = json_decode( $this->_data['css_map'], true ) )
			{
				$this->_data['css_map'] = $cssMap;
			}
			else
			{
				$this->_data['css_map'] = array();
			}
		}
	}
	
	/*! Node */

	/**
	 * Get sharer logo
	 *
	 * @return	string|null
	 */
	public function get_logo_sharer()
	{		
		return $this->logoImage( 'sharer' );
	}

	/**
	 * Get header logo
	 *
	 * @return	string|null
	 */
	public function get_logo_front()
	{		
		return $this->logoImage( 'front' );
	}

	/**
	 * Get favicon logo
	 *
	 * @return	string|null
	 */
	public function get_logo_favicon()
	{		
		return $this->logoImage( 'favicon' );
	}

	/**
	 * Return logo image
	 *
	 * @param	string	$type	Type of logo image
	 * @return	string|null
	 */
	protected function logoImage( $type )
	{
		if( isset( \IPS\Theme::i()->logo[ $type ]['url'] ) )
		{
			try
			{
				return \IPS\File::get( 'core_Theme', \IPS\Theme::i()->logo[ $type ]['url'] )->url;
			}
			catch( \Exception $e )
			{
				return '';
			}	
		}

		return '';
	}

	/**
	 * [Node] Get Description
	 *
	 * @return	string|null
	 */
	protected function get__description()
	{		
		if( $this->author_name or $this->version )
		{
			return \IPS\Theme::i()->getTemplate( 'customization', 'core' )->themeDescription( $this->author_name, $this->author_url, $this->version );
		}

		return NULL;
	}

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge()
	{
		/* Is there an update to show? */
		$badge	= NULL;

		if ( static::designersModeEnabled() )
		{
			if ( $this->is_default or $this->is_acp_default )
			{
				if ( $this->is_default and $this->is_acp_default )
				{
					$message = 'theme_is_default_with_id';
				}
				elseif ( $this->is_default )
				{
					$message = 'theme_is_front_default_with_id';
				}
				else
				{
					$message = 'theme_is_acp_default_with_id';
				}
				
				$badge	= array(
					0	=> 'positive ipsPos_right',
					1	=> \IPS\Member::loggedIn()->language()->addToStack( $message, FALSE, array( 'sprintf' => array( $this->id ) ) )
				);
			}
			else
			{
				$badge = array(
					0 => 'style7 ipsPos_right',
					1 => \IPS\Member::loggedIn()->language()->addToStack('theme_with_id', FALSE, array( 'sprintf' => array( $this->id ) ) )
				);
			}
		}
		else
		{
			if ( $this->update_data )
			{
				$data	= json_decode( $this->update_data, TRUE );

				if( !empty($data['longversion']) AND $data['longversion'] > $this->long_version )
				{
					$released	= NULL;

					if( $data['released'] AND intval($data['released']) == $data['released'] AND \strlen($data['released']) == 10 )
					{
						$released	= (string) \IPS\DateTime::ts( $data['released'] )->localeDate();
					}
					else if( $data['released'] )
					{
						$released	= $data['released'];
					}

					$badge	= array(
							0	=> 'positive ipsPos_right',
							1	=> '',
							2	=> \IPS\Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $data['version'], $data['updateurl'], $released )
					);
				}
			}
			else if ( $this->is_default or $this->is_acp_default )
			{
				if ( $this->is_default and $this->is_acp_default )
				{
					$message = 'default_no_parenthesis';
				}
				elseif ( $this->is_default )
				{
					$message = 'default_front_no_parenthesis';
				}
				else
				{
					$message = 'default_acp_no_parenthesis';
				}
				
				$badge	= array(
							0	=> 'positive ipsPos_right',
							1	=> $message
					);
			}
		}

		return $badge;
	}
	
	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'fa-globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return ( $this->by_skin_gen ) ? 'magic' : '';
	}

	/**
	 * [Node] Clone the theme set
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}
		
		$title      = \IPS\Member::loggedIn()->language()->get( static::$titleLangPrefix . $this->_id );
		$originalId = $this->get__id();
		
		/* Unset custom properties */
		foreach( array( 'settings', 'resource_map', 'css_map', 'logo', 'name_translated', 'title' ) as $f )
		{
			unset( $this->_data[ $f ] );
		}
		$this->is_default = FALSE;
		$this->is_acp_default = FALSE;
		
		parent::__clone();
		
		/* Dynamically produce insert list so we don't have to update each time the table changes */
		$templateTable  = \IPS\Db::i()->getTableDefinition( 'core_theme_templates', TRUE );
		$cssTable       = \IPS\Db::i()->getTableDefinition( 'core_theme_css', TRUE );
		$templateFields = array_keys( $templateTable['columns'] );
		$cssFields      = array_keys( $cssTable['columns'] );
		
		array_walk( $templateFields, function( &$name, $i, $setId )
		{
			switch( $name )
			{
				case 'template_id':
					$name = 'null';
					break;
				case 'template_set_id':
					$name = $setId;
					break;
				case 'template_unique_key':
					$name = "MD5( CONCAT( {$setId}, ';', template_app, ';', template_location, ';', template_group, ';', LOWER(template_name) ) )";
					break;
			}
		}, $this->id );
		
		array_walk( $cssFields, function( &$name, $i, $setId )
		{
			switch( $name )
			{
				case 'css_id':
					$name = 'null';
					break;
				case 'css_set_id':
					$name = $setId;
					break;
				case 'css_unique_key':
					$name = "MD5( CONCAT( {$setId}, ';', css_app, ';', css_location, ';', css_path, ';', css_name ) )";
					break;
			}
		}, $this->id );
		
		/* Insert new language bit */
		\IPS\Lang::saveCustom( 'core', "core_theme_set_title_" . $this->id, sprintf( \IPS\Member::loggedIn()->language()->get( 'theme_clone_copy_of' ), $title ) );
		
		/* Copy across any template bits */
		\IPS\Db::i()->insert( 'core_theme_templates', \IPS\Db::i()->select( implode(',', $templateFields ), 'core_theme_templates', array( 'template_set_id=?', $originalId ) ) );
		
		/* Copy across any CSS bits */
		\IPS\Db::i()->insert( 'core_theme_css', \IPS\Db::i()->select( implode(',', $cssFields ), 'core_theme_css', array( 'css_set_id=?', $originalId ) ) );
		
		/* Copy across any settings */
		$settingFields = array();

		foreach( \IPS\Db::i()->select( '*', 'core_theme_settings_fields', array( 'sc_set_id=?', $originalId ) ) as $row )
		{
			$settingFields[ $row['sc_id'] ] = $row;
		}

		$settingValues = iterator_to_array( \IPS\Db::i()->select( 'sv_id, sv_value', 'core_theme_settings_values', array( \IPS\Db::i()->in( 'sv_id', array_keys( $settingFields ) ) ) )->setKeyField('sv_id')->setValueField('sv_value') );

		foreach( $settingFields as $id => $row )
		{
			$origId = $row['sc_id'];
			unset( $row['sc_id'] );

			$row['sc_set_id'] = $this->id;

			$newId = \IPS\Db::i()->insert( 'core_theme_settings_fields', $row );

			\IPS\Db::i()->insert( 'core_theme_settings_values', array(
				'sv_id'    => $newId,
				'sv_value' => ( array_key_exists( $origId, $settingValues ) ) ? $settingValues[ $origId ] : $row['sc_default']
			) );
		}

		/* Copy across resources */
		$this->copyResourcesFromSet( $originalId );

		/* Make sure data objects are loaded correctly */
		static::$gotAll = false;

		/* Save css/img maps */
		\IPS\Theme::load( $this->id )->saveSet();
		
		/* Copy logos */
		$this->logo_data = NULL;
		$this->copyLogosFromSet( $originalId );
		
		\IPS\Session::i()->log( 'acplogs__themeset_created', array( sprintf( \IPS\Member::loggedIn()->language()->get( 'theme_clone_copy_of' ), \IPS\Member::loggedIn()->language()->get( 'core_theme_set_title_' . $originalId ) ) => FALSE ) );
	}
	
	/**
	 * [Node] Delete the theme set
	 *
	 * @return	void
	 */
	public function delete()
	{
		if ( \IPS\IN_DEV AND $this->_id == 1 )
		{
			\IPS\Output::i()->error( 'theme_error_not_available_in_dev', '2S140/1', 403, '' );
		}
		
		if ( $this->is_default or $this->is_acp_default )
		{
			\IPS\Output::i()->error( 'core_theme_cannot_delete_default_theme', '2S162/1', 403, '' );
		}
		
		/* Clear out existing built bits */
		\IPS\File::getClass('core_Theme')->deleteContainer( 'css_built_' . $this->_id );
		\IPS\File::getClass('core_Theme')->deleteContainer( 'set_resources_' . $this->_id );
		
		$templates = $this->getRawTemplates();
			
		foreach( $templates as $app => $v )
		{
			foreach( $templates[ $app ] as $location => $groups )
			{
				foreach( $templates[ $app ][ $location ] as $group => $bits )
				{
					foreach( $templates[ $app ][ $location ][ $group ] as $name => $data )
					{
						/* Store it */
						$key = \strtolower( 'template_' . $this->_id . '_' .static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . static::cleanGroupName( $group ) );
							
						unset( \IPS\Data\Store::i()->$key );
					}
				}
			}
		}
		
		\IPS\Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=?', $this->_id ) );
		\IPS\Db::i()->delete( 'core_theme_css', array( 'css_set_id=?', $this->_id ) );
		\IPS\Db::i()->delete( 'core_theme_templates', array( 'template_set_id=?', $this->_id ) );
		\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'word_theme=?', $this->_id ) );

		/* Delete theme settings */
		$settingFields = array();
		foreach( \IPS\Db::i()->select( '*', 'core_theme_settings_fields', array( 'sc_set_id=?', $this->_id ) ) as $row )
		{
			$settingFields[ $row['sc_id'] ] = $row;
		}

		if ( count( $settingFields ) )
		{
			\IPS\Db::i()->delete( 'core_theme_settings_values', array( \IPS\Db::i()->in( 'sv_id', array_keys( $settingFields ) ) ) );
		}

		/** reset member skin  */
		\IPS\Db::i()->update( 'core_members', array( 'skin' => 0 ), array('skin=?', $this->_id ) );

		\IPS\Db::i()->delete( 'core_theme_settings_fields', array( 'sc_set_id=?', $this->_id ) );

		\IPS\Session::i()->log( 'acplogs__themeset_delete', array( "core_theme_set_title_{$this->_id}" => true ) );
	
		parent::delete();
		unset( \IPS\Data\Store::i()->themes );
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'theme_sets_manage' );
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'customization/themes.css', 'core', 'admin' ) );
		\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_customization.js', 'core', 'admin' ) );

		/* General */
		if ( $this->id )
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('core_theme_editing_set', FALSE, array( 'sprintf' => array( $this->_title ) ) );
			
			$form->addTab( 'theme_set_tab__general' );
		}
		else
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('theme_set_add_button');
			
			$form->hiddenValues['theme_type'] = \IPS\Request::i()->type;
			$form->addTab( ( \IPS\Request::i()->type === 'vse' ) ? 'theme_set_tab__new_vse_set' : 'theme_set_tab__new_custom_set' );
		}
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'core_theme_set_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? "core_theme_set_title_{$this->id}" : NULL ) ) ) );

		$class = get_called_class();

		$form->add( new \IPS\Helpers\Form\Node( 'core_theme_parent_id', intval( $this->parent_id ), FALSE, array(
			'class'          => '\IPS\Theme',
			'subnodes'        => FALSE,
			'zeroVal'         => 'core_theme_parent_id_none',
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}

				return !isset( \IPS\Request::i()->id ) or ( $node->id != \IPS\Request::i()->id and !$node->isChildOf( $node::load( \IPS\Request::i()->id ) ) );
			} ) ) );
		
		$id = $this->id;
		$form->add( new \IPS\Helpers\Form\YesNo( 'core_theme_set_is_default' , $this->is_default, false, array( 'togglesOff'  => array('core_theme_set_permissions') ), function( $val ) use ( $id )
		{
			$where = array( array( 'set_is_default=1' ) );
			if ( $id )
			{
				$where[] = array('set_id<>?', $id );
			}
			
			if ( !$val and !\IPS\Db::i()->select( 'COUNT(*)', 'core_themes', $where )->first() )
			{
				throw new \DomainException('core_theme_set_is_default_error');
			}
		} ) );
		
		$form->add( new \IPS\Helpers\Form\Select(
				'core_theme_set_permissions',
				( $this->id ) ? ( $this->permissions === '*' ? '*' : explode( ",", $this->permissions ) ) : '*',
				FALSE,
				array( 'options' => \IPS\Member\Group::groups(), 'multiple' => TRUE, 'parse' => 'normal', 'unlimited' => '*', 'unlimitedLang' => 'all' ),
				NULL,
				NULL,
				NULL,
				'core_theme_set_permissions'
		) );
		
		$form->add( new \IPS\Helpers\Form\YesNo( 'core_theme_set_is_acp_default' , $this->is_acp_default, false, array(), function( $val ) use ( $id )
		{
			$where = array( array( 'set_is_acp_default=1' ) );
			if ( $id )
			{
				$where[] = array('set_id<>?', $id );
			}
			
			if ( !$val and !\IPS\Db::i()->select( 'COUNT(*)', 'core_themes', $where )->first() )
			{
				throw new \DomainException('core_theme_set_is_default_error');
			}
		} ) );
				
		if ( \IPS\IN_DEV OR \IPS\Theme::designersModeEnabled() )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'theme_template_export_author_name', $this->author_name, false ) );
			$form->add( new \IPS\Helpers\Form\Text( 'theme_template_export_author_url' , $this->author_url, false ) );
			$form->add( new \IPS\Helpers\Form\Text( 'theme_update_check' , $this->update_check, false ) );
				
			$form->add( new \IPS\Helpers\Form\Text( 'theme_template_export_version'        , $this->version ? $this->version : '1.0'    , true, array( 'placeholder' => '1.0.0' ) ) );
			$form->add( new \IPS\Helpers\Form\Number( 'theme_template_export_long_version' , $this->long_version ? $this->long_version : 10000, true ) );
		}
		
		/* Logo */
		$form->addTab( 'theme_set_tab__logo' );
		
		/* SITE LOGO */
		$form->addHeader( 'core_theme_set_logo_manage' );
		
		$form->add( new \IPS\Helpers\Form\Upload( 'core_theme_set_logo', ( isset( $this->logo['front']['url'] ) ? \IPS\File::get( 'core_Theme', $this->logo['front']['url'] ) : NULL ), FALSE, array( 'image' => true, 'storageExtension' => 'core_Theme' ), NULL, NULL, NULL, 'core_theme_set_logo' ) );

		/* SHARER LOGO */
		$form->addHeader( 'core_theme_set_sharer_logo_manage' );
			
		$form->add( new \IPS\Helpers\Form\Upload( 'core_theme_set_sharer_logo', ( isset( $this->logo['sharer']['url'] ) ? \IPS\File::get( 'core_Theme', $this->logo['sharer']['url'] ) : NULL ), FALSE, array( 'image' => true, 'storageExtension' => 'core_Theme' ), NULL, NULL, NULL, 'core_theme_set_sharer_logo' ) );
			
		/* FAVICO LOGO */
		$form->addHeader( 'core_theme_set_favico_logo_manage' );
		
		$form->add( new \IPS\Helpers\Form\Upload( 'core_theme_set_favico_logo', ( isset( $this->logo['favicon']['url'] ) ? \IPS\File::get( 'core_Theme', $this->logo['favicon']['url'] ) : NULL ), FALSE, array( 'allowedFileTypes' => array('ico'), 'storageExtension' => 'core_Theme' ), NULL, NULL, NULL, 'core_theme_set_favico_logo' ) );
		
		/* EDITOR */
		$form->addTab('core_theme_set_editor_header');
		if ( \IPS\IN_DEV )
		{
			$form->addMessage( \IPS\Member::loggedIn()->language()->get('core_theme_set_editor_dev'), 'ipsMessage ipsMessage_warning' );
		}
		$form->addMessage( \IPS\Member::loggedIn()->language()->addToStack( 'core_theme_set_editor_blurb', FALSE, array( 'sprintf' => array( \IPS\Helpers\Form\Editor::ckeditorVersion() ) ) ) );
		if ( !\IPS\NO_WRITES and class_exists( 'ZipArchive', FALSE ) )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'core_theme_set_editor', 'existing', FALSE, array(
				'options' => array( 'existing' => 'core_theme_set_editor_existing_sel', 'new' => 'core_theme_set_editor_new_sel' ),
				'toggles' => array( 'existing' => array( 'core_theme_set_editor_existing' ), 'new' => array( 'core_theme_set_editor_new' ) )
			) ) );
		}
		else
		{
			\IPS\Member::loggedIn()->language()->words['core_theme_set_editor_existing_desc'] = \IPS\Member::loggedIn()->language()->get('core_theme_set_editor_existing_instr');
		}
		$skins = array();
		if ( is_dir( \IPS\ROOT_PATH . '/applications/core/interface/ckeditor/ckeditor/skins' ) )
		{
			foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/core/interface/ckeditor/ckeditor/skins' ) as $f )
			{
				if ( !$f->isDot() and $f->isDir() )
				{
					$_name				= (string) $f;

					if( \IPS\Member::loggedIn()->language()->checkKeyExists( 'ckeditor_theme_' . $_name ) )
					{
						$skins[ $_name ]	= \IPS\Member::loggedIn()->language()->addToStack( 'ckeditor_theme_' . $_name );
					}
					else
					{
						$skins[ $_name ]	= $_name;
					}
				}
			}
		}
		$form->add( new \IPS\Helpers\Form\Select( 'core_theme_set_editor_existing', $this->editor_skin, FALSE, array( 'options' => $skins ), NULL, NULL, NULL, 'core_theme_set_editor_existing' ) );
		
		if ( !\IPS\NO_WRITES and class_exists( 'ZipArchive', FALSE ) )
		{
			if ( !is_writable( \IPS\ROOT_PATH . '/applications/core/interface/ckeditor/ckeditor/skins' ) )
			{
				$form->add( new \IPS\Helpers\Form\Custom( 'core_theme_set_editor_new', NULL, TRUE, array(
					'getHtml' => function()
					{
						return \IPS\Theme::i()->getTemplate( 'global' )->message( 'editor_skin_nowrite', 'error' );
					},
					'getValue' => function()
					{
						return NULL;
					}
				), NULL, NULL, NULL, 'core_theme_set_editor_new' ) );
			}
			else
			{
				$form->add( new \IPS\Helpers\Form\Upload( 'core_theme_set_editor_new', NULL, FALSE, array( 'allowedFileTypes' => array( 'zip' ), 'temporary' => TRUE ), NULL, NULL, NULL, 'core_theme_set_editor_new' ) );
			}
		}
		
		/* THEME SETTINGS */
		if ( $this->id )
		{
			if ( static::designersModeEnabled() )
			{
				\IPS\Theme\Advanced\Theme::loadLanguage( $this->id );
			}
			
			$customTabs = array();
			foreach( \IPS\Db::i()->select( 'sc.*, sv.sv_value', array( 'core_theme_settings_fields', 'sc' ), array( 'sc.sc_set_id=?', $this->id ), 'sc_order' )
				->join( array( 'core_theme_settings_values', 'sv' ), 'sv.sv_id=sc.sc_id' ) as $data )
			{
				$customTabs[ $data['sc_tab_key'] ][] = $data;
			}
			
			ksort( $customTabs );
			
			foreach( $customTabs as $tabKey => $data )
			{
				$form->addTab( 'theme_custom_tab_' . $tabKey );
				
				if ( \IPS\IN_DEV AND $this->_id === 1 )
				{
					$form->addHeader( 'core_theme_default_dev_reminder' );
				}
				
				foreach( $data as $row )
				{
					if ( $field = $this->getCustomSettingField( $row ) )
					{
						$form->add( $field );
					}
				}
			}
		}
	}
	
	/**
	 * Custom Settings
	 *
	 * @param	array	$row	Row from core_theme_settings_fields
	 * @return	\IPS\Helpers\Form\FormAbstract
	 */
	public function getCustomSettingField( $row )
	{
		if ( $row['sc_condition'] )
		{
			if ( !@eval( $row['sc_condition'] ) )
			{
				return NULL;
			}
		}
		
		$value = ( isset( $row['sv_value'] ) ) ? $row['sv_value'] : $row['sc_default'];
		$row['sc_type'] = ( empty( $row['sc_type'] ) ) ? 'Text' : $row['sc_type'];
		
		if ( $row['sc_type'] === 'other' )
		{
			$theme = $this;
			$field = eval( $row['sc_content'] );
		}
		else
		{					
			$class = '\IPS\Helpers\Form\\' . $row['sc_type'];

			$options = array();
			switch ( $row['sc_type'] )
			{
				case 'Select':
					$options['multiple'] = $row['sc_multiple'];
					// No break
				case 'Radio':
					$content = json_decode( $row['sc_content'], true );
					$values  = array();
					
					foreach( $content as $data )
					{
						if ( isset( $data['key'] ) )
						{
							$values[ $data['key'] ] = $data['value'];
						}
						else
						{
							$values[] = $data;
						}
					}
					
					$options['options'] = $values;
				break;

				case 'Editor':
					$options['app']			= 'core';
					$options['key']			= 'Admin';
					$options['autoSaveKey']	= 'autosave_' . $row['sc_id'];
				break;

				case 'TextArea':
					$options['rows'] = 8;
				break;
				case 'Upload':
					$options['storageExtension'] = 'core_Theme';
					
					if ( $value )
					{
						try
						{
							$value = \IPS\File::get( $options['storageExtension'], $value );
						}
						catch ( \OutOfRangeException $e )
						{
							$value = NULL;
						}
					}
				break;
			}

			$suffix = ( $row['sc_type'] == 'Color' and $row['sv_value'] and ( $row['sc_default'] != $row['sv_value'] ) ) ? \IPS\Theme::i()->getTemplate( 'customization', 'core', 'admin' )->themeSettingRevert( $this->id, $row ) : NULL;
			
			$field = new $class( "core_theme_setting_title_{$row['sc_id']}", $value, false, $options, NULL, NULL, $suffix, 'theme_setting_' . $row['sc_key'] );
		}
		
		if ( \IPS\IN_DEV OR \IPS\Theme::designersModeEnabled() )
		{
			$field->label = \IPS\Theme::i()->getTemplate( 'customization', 'core', 'admin' )->themeSettingLabelWithKey( $row );
		}
		else
		{
			$field->label = \IPS\Member::loggedIn()->language()->addToStack( $row['sc_title'] );
		}
		
		return $field;
	}
	
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$parentButtons = array();
		$buttons       = array();

		foreach( parent::getButtons( $url, $subnode ) as $button )
		{
			$parentButtons[ $button['title'] ] = $button;
		}
		
		if ( $this->by_skin_gen AND \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_easy_editor' ) )
		{
			$buttons[] = array(
					'icon'	   => 'magic',
					'title'    => 'core_theme_launch_vse_tooltip',
					'link'	   => \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=launchvse&id={$this->_id}" ),
					'target'   => '_blank'
			);
			
			$buttons['edit'] = $parentButtons['edit'];
		}
		
		/* Add in our buttons */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_templates_manage' ) )
		{
			$buttons['edit_templates'] = array(
				'icon'	=> 'code',
				'title'	=> 'theme_set_manage_templates_css',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=templates&id={$this->_id}" )
			);
		}
		
		if ( ! $this->by_skin_gen )
		{
			$parentButtons['edit']['data'] = array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title );
			$buttons['edit'] = $parentButtons['edit'];
		}

		$buttons['resources'] = array(
			'icon'	=> 'file-image-o',
			'title'	=> 'theme_set_manage_resources',
			'link'	=> \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=resources&set_id={$this->_id}" ),
			'data'	=> array()
		);

		$buttons['copy'] = $parentButtons['copy'];
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_download_upload' ) )
		{
			$buttons['upload'] = array(
					'icon'	=> 'upload',
					'title'	=> 'theme_set_import',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=importForm&id={$this->_id}" ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('theme_set_import_title', FALSE, array( 'sprintf' => array( $this->name ) ) ) )
			);
			
			$buttons['download'] = array(
					'icon'	=> 'download',
					'title'	=> 'theme_set_export',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=exportForm&id={$this->_id}" . ( ( \IPS\IN_DEV or \IPS\Theme::designersModeEnabled() ) ? '' : '&form_submitted=1' ) )
			);
		}
				
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			$buttons['member_theme_set'] = array(
					'icon'	=> 'user',
					'title'	=> 'theme_set_members',
					'link'	=> $url->setQueryString( array( 'do' => 'setMembers', 'id' => $this->is_default ? 0 : $this->_id ) ),
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title )
			);
		}
		
		if ( $this->by_skin_gen )
		{
			$buttons['convert_to_full'] = array(
					'icon'	=> 'exchange',
					'title'	=> 'theme_set_convert_vsecustom_link',
					'link'	=> $url->setQueryString( array( 'do' => 'convertToCustom', 'id' => $this->_id ) ),
					'data' 	=> array( 'confirm' => '', 'confirmMessage' => \IPS\Member::loggedIn()->language()->addToStack('theme_set_convert_vsecustom') )
			);
		}
		
		if ( $this->id !== static::defaultTheme() )
		{
			$buttons['delete'] = $parentButtons['delete'];
		}
		
		if ( \IPS\IN_DEV OR \IPS\Theme::designersModeEnabled() )
		{
			$buttons['theme_settings'] = array(
				'icon'	=> 'cog',
				'title'	=> 'theme_set_custom_setting',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=customization&controller=themesettings&set_id={$this->_id}" )
			);
			
			if ( $this->is_default OR \IPS\Theme::designersModeEnabled() )
			{
				$buttons['dev_import'] = array(
						'icon'	=> 'cogs',
						'title'	=> 'theme_set_import_master',
						'link'	=> \IPS\Http\Url::internal( "app=core&module=customization&controller=themes&do=devImport&id={$this->_id}" )
				);
			}
			
			/*$buttons[] = array(
					'icon'	=> 'cogs',
					'title'	=> 'theme_set_build',
					'link'	=> \IPS\Http\Url::internal( "{$url}&do=build&id={$this->_id}" )
			);*/
		}

		if ( \IPS\Theme::designersModeEnabled() )
		{
			foreach( array( 'delete', 'copy', 'convert_to_full', 'upload', 'resources', 'edit_templates' ) as $btn )
			{
				if ( isset( $buttons[ $btn ] ) )
				{
					unset( $buttons[ $btn ] );
				}
			}
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function saveForm( $values )
	{
		$creating = FALSE;

		/* Create if necessary */
		if ( ! $this->id )
		{
			$creating = TRUE;
			$this->parent_array = '[]';
			$this->child_array  = '[]';
			$this->parent_id    = ( $values['core_theme_parent_id'] instanceof \IPS\Theme ) ? $values['core_theme_parent_id']->id : 0;
			$this->by_skin_gen  = ( \IPS\Request::i()->theme_type === 'vse' ) ? 1 : 0;
			$this->long_version = \IPS\Application::load( 'core' )->long_version;
			$this->save();
			
			/* Copy across resources */
			$this->copyResourcesFromSet( $this->parent_id );
		}
		
		if ( isset( $values['core_theme_set_new_import'] ) )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['core_theme_set_new_import'], $tempFile );
			
			/* Store values */
			$key = 'core_theme_import_' . md5_file( $tempFile );
			\IPS\Data\Store::i()->$key = array( 'apps'  		=> 'all',
										        'resources'		=> true,
										        'html'			=> true,
										        'css'			=> true );
			
			/* Initate a redirector */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=customization&controller=themes&do=import&file=' . urlencode( $tempFile ) . '&key=' . md5_file( $tempFile ) . '&id=' . $this->id ) );
		}
		else
		{
			/* Name */
			\IPS\Lang::saveCustom( 'core', "core_theme_set_title_{$this->id}", $values['core_theme_set_title'] );
			
			/* CKEditor Skin */
			if ( isset( $values['core_theme_set_editor'] ) and $values['core_theme_set_editor'] === 'new' and $values['core_theme_set_editor_new'] )
			{
				/* Get the theme name */
				$zip = zip_open( $values['core_theme_set_editor_new'] );
				$name = zip_entry_name( zip_read( $zip ) );
				$values['core_theme_set_editor_existing'] = mb_substr( $name, 0, mb_strpos( $name, '/' ) );
				zip_close( $zip );
				
				/* Extract it */
				$zip = new \ZipArchive;
				$zip->open( $values['core_theme_set_editor_new'] );
				$zip->extractTo( \IPS\ROOT_PATH . '/applications/core/interface/ckeditor/ckeditor/skins' );
				$zip->close();
				
				/* Delete the temp file */
				unlink( $values['core_theme_set_editor_new'] );
			}
			
			$dataChanged = false;
			$save        = array();

			/* SITE LOGO */
			if ( $values['core_theme_set_logo'] )
			{
				$url   = (string) $values['core_theme_set_logo'];
				$image = \IPS\Image::create( \IPS\File::get( 'core_Theme', $url )->contents() );
				
				$this->_data['logo']['front'] = array( 'url' => $url, 'width' => $image->width, 'height' => $image->height );
				
				$dataChanged = true;
			}
			else
			{
				$dataChanged = true;
				$this->_data['logo']['front'] = NULL;
			}
			
			/* SHARER LOGO */
			if ( $values['core_theme_set_sharer_logo'] )
			{
				$url   = (string) $values['core_theme_set_sharer_logo'];
				$image = \IPS\Image::create( \IPS\File::get( 'core_Theme', $url )->contents() );
			
				$this->_data['logo']['sharer'] = array( 'url' => $url, 'width' => $image->width, 'height' => $image->height );
			
				$dataChanged = true;
			}
			else
			{
				$dataChanged = true;
				$this->_data['logo']['sharer'] = NULL;
			}
			
			/* FAVICON LOGO */
			if ( $values['core_theme_set_favico_logo'] )
			{
				$url   = (string) $values['core_theme_set_favico_logo'];
					
				$this->_data['logo']['favicon'] = array( 'url' => $url );
					
				$dataChanged = true;
			}
			else
			{
				$dataChanged = true;
				$this->_data['logo']['favicon'] = NULL;
			}
			
			if ( $values['core_theme_set_is_default'] )
			{
				\IPS\Db::i()->update( 'core_themes', array( 'set_is_default' => 0 ), array( 'set_id<>?', $this->id ) );
				$dataChanged = true;
			}
			if ( $values['core_theme_set_is_acp_default'] )
			{
				\IPS\Db::i()->update( 'core_themes', array( 'set_is_acp_default' => 0 ), array( 'set_id<>?', $this->id ) );
				$dataChanged = true;
			}
			
			if ( $dataChanged OR ( \IPS\IN_DEV OR \IPS\Theme::designersModeEnabled() ) )
			{
				if ( \IPS\IN_DEV OR \IPS\Theme::designersModeEnabled() )
				{
					$save['set_author_name']  = $values['theme_template_export_author_name'];
					$save['set_author_url']   = $values['theme_template_export_author_url'];
					$save['set_update_check'] = $values['theme_update_check'];
					$save['set_version']      = $values['theme_template_export_version'];
					$save['set_long_version'] = $values['theme_template_export_long_version'];
				}
				$this->save();
				$this->saveSet( $save );
			}
			
			$this->editor_skin = $values['core_theme_set_editor_existing'];
		}

		$changedSettings = false;

		if ( $creating === FALSE )
		{
			$themeSettings = $this->getThemeSettings( static::THEME_ID_KEY );

			$save = array();
			$json = array();
			foreach( $themeSettings as $settingId => $row )
			{
				if ( isset( $values["core_theme_setting_title_{$row['sc_id']}"] ) )
				{
					$field       = $this->getCustomSettingField( $row, TRUE );
					$stringValue = $field::stringValue( $values["core_theme_setting_title_{$row['sc_id']}"] );
					
					if ( $values["core_theme_setting_title_{$row['sc_id']}"] and get_class( $field ) == 'IPS\Helpers\Form\Upload' )
					{
						if ( is_array( $values["core_theme_setting_title_{$row['sc_id']}"] ) )
						{
							$items = array();
							foreach( $values["core_theme_setting_title_{$row['sc_id']}"] as $obj )
							{
								$items[] = (string) $obj;
							}
							$save[ $row['sc_id'] ] = implode( ',', $items );
						}
						else
						{
							$save[ $row['sc_id'] ] = (string) $values["core_theme_setting_title_{$row['sc_id']}"];
							$json[ $row['sc_key'] ] = '<fileStore.core_Theme>/' . $save[ $row['sc_id'] ];
						}
					}
					else
					{
						$save[ $row['sc_id'] ]  = $stringValue;
						$json[ $row['sc_key'] ] = $save[ $row['sc_id'] ];
					}
				}
			}
			
			if ( count( $save ) )
			{
				foreach( $save as $id => $value )
				{
					if ( $themeSettings[ $id ]['_value'] != $value )
					{
						$changedSettings = true;
					}

					\IPS\Db::i()->delete( 'core_theme_settings_values', array( 'sv_id=?', $id ) );
					\IPS\Db::i()->insert( 'core_theme_settings_values', array( 'sv_id' => $id, 'sv_value' => (string) $value ) );

					if ( \IPS\IN_DEV AND $this->id === 1 )
					{
						\IPS\Db::i()->update( 'core_theme_settings_fields', array( 'sc_default' => (string) $value ), array( 'sc_set_id=? and sc_id=?', 1, $id ) );
					}
				}

				\IPS\Db::i()->update( 'core_themes', array( 'set_template_settings' => json_encode( $json ) ), array( 'set_id=?', $this->id ) );

				if ( \IPS\IN_DEV AND $this->id === 1 )
				{
					\IPS\Theme\Dev\Theme::writeThemeSettingsToDisk();
				}
			}
		}

		/* Designers mode needs specialist attention */
		if ( $creating and \IPS\Theme::designersModeEnabled() AND \IPS\IN_DEV === FALSE )
		{
			\IPS\Theme\Advanced\Theme::$currentThemeId = $this->id;

			foreach( \IPS\Application::applications() as $app )
			{
				\IPS\Theme\Advanced\Theme::exportTemplates( $app->directory );
				\IPS\Theme\Advanced\Theme::exportCss( $app->directory );
				\IPS\Theme\Advanced\Theme::exportResources( $app->directory );
			}
		}

		/* Clear out compiled CSS so CSS theme plugins are up to date and rebuild parent/child trees */
		$themeSetToBuild = static::load( $this->id );
		
		if ( $changedSettings )
		{
			\IPS\File::getClass('core_Theme')->deleteContainer( 'css_built_' . $this->id );
			$this->css_map = array();
			
			foreach( $themeSetToBuild->allChildren() as $id => $child )
			{
				\IPS\File::getClass('core_Theme')->deleteContainer( 'css_built_' . $child->id );
				$child->css_map = array();
				$child->save();
			}
		}
		
		$this->is_default  = $values['core_theme_set_is_default'];
		$this->is_acp_default  = $values['core_theme_set_is_acp_default'];
		$this->permissions = ( $values['core_theme_set_permissions'] === '*' ) ? '*' : implode( ',', $values['core_theme_set_permissions'] );
		$this->parent_id   = ( $values['core_theme_parent_id'] instanceof \IPS\Theme ) ? $values['core_theme_parent_id']->id : 0;
		
		$this->save();
		
		if ( $creating === TRUE AND $this->id )
		{
			$this->installThemeSettings();
			
			if ( $this->parent_id )
			{
				$this->copyLogosFromSet( $this->parent_id );
			}
		}
		
		/* Log */
		if ( $creating === FALSE )
		{
			\IPS\Session::i()->log( 'acplogs__themeset_updated', array( "core_theme_set_title_{$this->id}" => true ) );
		}
		else
		{
			\IPS\Session::i()->log( 'acplogs__themeset_created', array( "core_theme_set_title_{$this->id}" => true ) );
		}
	}
	
	/**
	 * Build resource map of "human URL" to File Object URL
	 *
	 * @param	string|array	$app	App (e.g. core, forum)
	 * @return	void
	 */
	public function buildResourceMap( $app=NULL )
	{
		$flagKey = 'resource_compiling_' . $this->_id . '_' . $app;
		
		if ( isset( \IPS\Data\Store::i()->$flagKey ) and ! empty( \IPS\Data\Store::i()->$flagKey ) )
		{
			/* We're currently rebuilding */
			if ( time() - \IPS\Data\Store::i()->$flagKey < 30  )
			{
				return null;
			}
		}

		\IPS\Data\Store::i()->$flagKey = time();
		
		$resourceMap = $this->resource_map;
	
		$where = ( $app !== null ) ? array( 'resource_set_id=? and resource_app=?', $this->_id, $app ) : array('resource_set_id=?', $this->_id );
		$keysSeen = array();

		foreach ( \IPS\Db::i()->select( '*', 'core_theme_resources', $where ) as $row )
		{
			$name = static::makeBuiltTemplateLookupHash( $row['resource_app'], $row['resource_location'], $row['resource_path'] ) . '_' . $row['resource_name'];

			if ( $row['resource_filename'] )
			{
				$keysSeen[]         = $name;
				$resourceMap[$name] = $row['resource_filename'];
			}
			else
			{
				/* If there is no filename, then it has yet to be compiled so do not add it to the resource map as it prevents it being compiled later */
				unset( $resourceMap[$name] );
			}
		}

		$this->resource_map = $resourceMap;
		$this->save();
		
		unset( \IPS\Data\Store::i()->$flagKey );
	}

	/**
	 * Copy all resources from set $id to this set
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	int		$id		ID to copy from. 0 is the 'master' resources (same as the default when first installed)
	 * @return  void
	 */
	public function copyResourcesFromSet( $id=0 )
	{
		\IPS\Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=?', $this->_id ) );
		
		$resourceMap = array();
		
		foreach ( \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=?', $id ) ) as $data )
		{
			$key = static::makeBuiltTemplateLookupHash($data['resource_app'], $data['resource_location'], $data['resource_path']) . '_' . $data['resource_name'];
			
			if ( $data['resource_data'] )
			{ 
				$fileName = (string) \IPS\File::create( 'core_Theme', $key, $data['resource_data'], 'set_resources_' . $this->_id, FALSE, NULL, FALSE );
				
				\IPS\Db::i()->insert( 'core_theme_resources', array(
						'resource_set_id'      => $this->_id,
						'resource_app'         => $data['resource_app'],
						'resource_location'    => $data['resource_location'],
						'resource_path'        => $data['resource_path'],
						'resource_name'        => $data['resource_name'],
						'resource_added'	   => time(),
						'resource_filename'    => $fileName,
						'resource_data'        => $data['resource_data'],
						'resource_plugin'	   => isset( $data['resource_plugin'] ) ? $data['resource_plugin'] : NULL,
						'resource_user_edited' => $data['resource_user_edited']
				) );
			}
			
			$resourceMap[ $key ] = $fileName;
		}
		
		/* Update theme map */
		$this->resource_map = $resourceMap;
		$this->save();
	}
	
	/**
	 * Copy all logos from a set
	 *
	 * @param	int		$id			ID to copy from
	 * @param	boolean	$cloning	Are we cloning?
	 * @return  void
	 */
	public function copyLogosFromSet( $id )
	{
		try
		{
			$original = static::load( $id );
		}
		catch( \OutOfRangeException $e )
		{
			throw new \OutOfRangeException("CANNOT_LOAD_THEME");
		}
		
		if ( $original->logo_data === NULL )
		{
			return;
		}
		
		$currentLogos = json_decode( $this->logo_data, TRUE );
		$logos        = array();
		foreach ( json_decode( $original->logo_data, TRUE ) as $file => $data )
		{
			if ( isset( $currentLogos[ $file ] ) )
			{
				continue;
			}
			
			if( isset( $data['url'] ) and $data['url'] )
			{
				try
				{
					/* Create new file */
					$original = \IPS\File::get( 'core_Theme', $data['url'] );
					$original->contents();
					$image = \IPS\Image::create( $original->contents() );
					$newImage = \IPS\File::create( 'core_Theme', $original->originalFilename, $original->contents() );

					$logos[$file] = array( 'url' => (string) $newImage, 'width' => $image->width, 'height' => $image->height );
				}
				catch ( \Exception $e ) { }
			}
		}
		
		$this->logo_data = json_encode( $logos );
		$this->save();
	}
	
	/**
	 * It installs theme settings. What did you really expect?
	 *
	 * @return void
	 */
	public function installThemeSettings()
	{
		/* Make sure theme setting fields are clear */
		\IPS\Db::i()->delete( 'core_theme_settings_fields', array( 'sc_set_id=?', $this->id ) );
		
		if ( ! $this->parent_id )
		{
			foreach( \IPS\Application::applications() as $appKey => $data )
			{
				/* Root skin? Add default theme settings */
				if ( ! file_exists( \IPS\ROOT_PATH . "/applications/{$appKey}/data/themesettings.json" ) )
				{
					continue;
				}
				
				$json = json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$appKey}/data/themesettings.json" ), true );
				
				/* Add */
				foreach( $json as $key => $data)
				{
					$insertId = \IPS\Db::i()->insert( 'core_theme_settings_fields', array(
						'sc_set_id'		 => $this->id,
						'sc_key'		 => $data['sc_key'],
						'sc_tab_key'	 => $data['sc_tab_key'],
						'sc_type'		 => $data['sc_type'],
						'sc_multiple'	 => $data['sc_multiple'],
						'sc_default'	 => $data['sc_default'],
						'sc_content'	 => $data['sc_content'],
						'sc_show_in_vse' => ( isset( $data['sc_show_in_vse'] ) ) ? $data['sc_show_in_vse'] : 0,
						'sc_updated'	 => time(),
						'sc_app'		 => $appKey,
						'sc_title'		 => $data['sc_title'],
						'sc_order'		 => $data['sc_order'],
						'sc_condition'	 => $data['sc_condition'],
					) );
				}
			}
		}
		else
		{
			$parent = \IPS\Theme::load( $this->parent_id );
			$themeSettings = $parent->getThemeSettings( static::THEME_ID_KEY );

			$save = array();
			
			foreach( $themeSettings as $settingId => $row )
			{
				$insertId = \IPS\Db::i()->insert( 'core_theme_settings_fields', array(
					'sc_set_id'      => $this->id,
					'sc_key'         => $row['sc_key'],
					'sc_tab_key'     => $row['sc_tab_key'],
					'sc_type'        => $row['sc_type'],
					'sc_multiple'    => $row['sc_multiple'],
					'sc_default'     => $row['sc_default'],
					'sc_content'     => $row['sc_content'],
					'sc_show_in_vse' => ( isset( $row['sc_show_in_vse'] ) ) ? $row['sc_show_in_vse'] : 0,
					'sc_updated'     => time(),
					'sc_app'	     => $row['sc_app'],
					'sc_title'		 => $row['sc_title'],
					'sc_order'		 => $row['sc_order'],
					'sc_condition'	 => $row['sc_condition'],
				) );
				
				\IPS\Db::i()->insert( 'core_theme_settings_values', array( 'sv_id' => $insertId, 'sv_value' => (string) $row['_value'] ) );
			}
		}
		
		$themeSettings = $this->getThemeSettings();
		
		$json = array();
		foreach( $themeSettings as $settingId => $row )
		{
			$json[ $row['sc_key'] ]	= $row['_value'];
		}
		
		$this->template_settings = json_encode( $json );
		$this->save();
	}
	
	/**
	 * Return all children
	 *
	 * @param	$theme	\IPS\Theme item
	 * @return	array
	 */
	 public function allChildren( &$return=array() )
	 {
		 foreach( $this->children() as $child )
		 {
			 $return[ $child->id ] = $child;
			 
			 $child->allChildren( $return );
		 }
		 
		 return $return;
	 }
	 
	/**
	 * Compile CSS ready for non IN_DEV use. This replaces any HTML logic such as {resource="foo.png"} with full URLs
	 *
	 * @param string|array	$app		CSS app (e.g. core, forum)
	 * @param string|array	$location	CSS location (e.g. admin,global,front)
	 * @param string|array	$group		CSS group (e.g. custom, framework)
	 * @param string		$name		CSS name (e.g. foo.css)
	 * @return boolean|null
	 */
	public function compileCss( $app=null, $location=null, $group=null, $name=null )
	{
		$flagKey = 'css_compiling_' . $this->_id . '_' . md5( $app . ',' . $location . ',' . $name );

		if ( isset( \IPS\Data\Store::i()->$flagKey ) and ! empty( \IPS\Data\Store::i()->$flagKey ) )
		{
			/* We're currently rebuilding */
			if ( time() - \IPS\Data\Store::i()->$flagKey < 30  )
			{
				return NULL;
			}
		}
		
		\IPS\Data\Store::i()->$flagKey = time();
		
		/* Deconstruct build grouping */
		if ( $name !== null )
		{
			if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) )
			{
				foreach( static::$buildGrouping['css'][ $app ][ $location ] as $grouped )
				{
					if ( str_replace( '.css', '', $name ) == $grouped )
					{
						$group = $grouped;
					}
				}
			}
		}

		$css    = $this->getRawCss( $app, $location, $group );
		$cssMap = $this->css_map;
		
		if ( $name === null )
		{
			/* Clear out existing built bits */
			\IPS\File::getClass('core_Theme')->deleteContainer( 'css_built_' . $this->_id );
			
			$cssMap = array();
		}

		foreach( $css as $app => $v )
		{
			foreach( $css[ $app ] as $location => $paths )
			{
				$built = array();
	
				foreach( $css[ $app ][ $location ] as $path => $data )
				{
					foreach( $css[ $app ][ $location ][ $path ] as $cssName => $cssData )
					{
						if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) AND in_array( $path,  static::$buildGrouping['css'][ $app ][ $location ] ) )
						{
							if ( $name === null OR $name == ( $path . '.css' ) )
							{
								$key = static::makeBuiltTemplateLookupHash( $app, $location, $path );
								
								if ( isset( $built[ $key ] ) )
								{
									$built[ $key ]['css_content'] .= "\n\n" . $cssData['css_content'];
								}
								else
								{
									$cssData['css_name'] = $path . '.css';
									$cssData['css_path'] = '.';
		
									$built[ $key ] = $cssData;
								}
							}
						}
						else
						{
							if ( $name === null OR $name == $cssData['css_name'] )
							{
								$store  = static::makeBuiltTemplateLookupHash( $app, $location, $cssData['css_path'] . '/' . $cssData['css_name'] );
					
								$cssMap[ $store ] = (string) static::writeCss( $cssData );
							}
						}
					}
				}
				
				/* Write combined css */
				if ( count( $built ) )
				{
					foreach( $built as $id => $cssData )
					{
						$store = static::makeBuiltTemplateLookupHash( $app, $location, $cssData['css_path'] . '/' . $cssData['css_name'] );

						$cssMap[ $store ] = (string) static::writeCss( $cssData );
					}
				}
			}
		}

		$this->css_map = $cssMap;
		$this->save();
	
		unset( \IPS\Data\Store::i()->$flagKey );

		return TRUE;
	}
	
	/**
	 * Build Templates ready for non IN_DEV use
	 * This fetches all templates in a group, converts HTML logic into ready to eval PHP and stores as a single PHP class per template group
	 *
	 * @param	string|array	$app		Templates app (e.g. core, forum)
	 * @param	string|array	$location	Templates location (e.g. admin,global,front)
	 * @param	string|array	$group		Templates group (e.g. forms, members)
	 * @return	boolean|null
	 */
	public function compileTemplates( $app=null, $location=null, $group=null )
	{
		$flagKey = 'template_compiling_' . $this->_id . '_' . md5( $app . ',' . $location . ',' . $group );
		if ( isset( \IPS\Data\Store::i()->$flagKey ) and ! empty( \IPS\Data\Store::i()->$flagKey ) )
		{
			/* We're currently rebuilding */
			if ( time() - \IPS\Data\Store::i()->$flagKey < 30  )
			{
				return NULL;
			}
		}

		\IPS\Data\Store::i()->$flagKey = time();

		$templates = $this->getRawTemplates( $app, $location, $group );

		foreach( $templates as $app => $v )
		{
			foreach( $templates[ $app ] as $location => $groups )
			{
				foreach( $templates[ $app ][ $location ] as $group => $bits )
				{
					/* Any template hooks? */
					$templateHooks = array();
					if( isset( \IPS\IPS::$hooks[ "\\IPS\\Theme\\class_{$app}_{$location}_{$group}" ] ) )
					{
						foreach ( \IPS\IPS::$hooks[ "\\IPS\\Theme\\class_{$app}_{$location}_{$group}" ] as $k => $data )
						{
							if ( !class_exists( "IPS\\Theme\\{$data['class']}_tmp", FALSE ) )
							{
								/* Like code hooks, we should only attempt to load the files contents if it actually exists */
								/* @see https://community.invisionpower.com/4bugtrack/archived-reports/ipsipsmonkeypatch-r6333/ */
								if ( file_exists( \IPS\ROOT_PATH . '/' . $data['file'] ) )
								{
									try
									{
										if ( eval( "namespace IPS\\Theme;\n\n" . str_replace( array( ' extends _HOOK_CLASS_', 'parent::hookData()' ), array( '_tmp', 'array()' ), file_get_contents( \IPS\ROOT_PATH . '/' . $data['file'] ) ) ) !== FALSE )
										{
											$class = "IPS\\Theme\\{$data['class']}_tmp";
											$templateHooks = array_merge_recursive( $templateHooks, $class::hookData() );
										}
									}
									catch ( \ParseError $e ) { }
								}
							}
						}
					}

					/* Build all the functions */
					$functions = array();
					foreach( $templates[ $app ][ $location ][ $group ] as $name => $data )
					{
						if ( isset( $templateHooks[ $name ] ) )
						{							
							$data['template_content'] = static::themeHooks( $data['template_content'], $templateHooks[ $name ] );
						}
						
						$functions[ $name ] = static::compileTemplate( $data['template_content'], $name, $data['template_data'], true, false, $app, $location, $group );
					}
				
					/* Put them in a class */
					$template = <<<EOF
namespace IPS\Theme;
class class_{$app}_{$location}_{$group} extends \IPS\Theme\Template
{
		
EOF;
					$template .= implode( "\n\n", $functions );
		
					$template .= <<<EOF
}
EOF;

					/* Store it */
					$key = \strtolower( 'template_' . $this->_id . '_' .static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . static::cleanGroupName( $group ) );
					\IPS\Data\Store::i()->$key = $template;
				}
			}
		}

		unset( \IPS\Data\Store::i()->$flagKey );

		return TRUE;
	}

	/**
	 * Clean the group name
	 *
	 * @param   string      $name       The name to clean
	 * @return  string
	 */
	public static function cleanGroupName( $name )
	{
		return str_replace( '-', '_', \IPS\Http\Url::seoTitle( $name ) );
	}

	/**
	 * Find templates in a template group
	 *
	 * @param	string		$group		Template group to search in
	 * @param	string|null	$app		Application key
	 * @param	string|null	$location	Template location
	 * @return	array
	 */
	public static function findTemplatesByGroup( $group, $app=NULL, $location=NULL )
	{
		/*
			Upon review, designers mode should not be used by developers, and subsequently the areas calling this method shouldn't see new templates only available
			in designers mode before they are imported to the database.
		if ( \IPS\Theme::designersModeEnabled() )
		{
			return \IPS\Theme\Advanced\Theme::findTemplatesByGroup( $group, $app, $location );
		}
		else*/ if ( \IPS\IN_DEV )
		{
			return \IPS\Theme\Dev\Theme::findTemplatesByGroup( $group, $app, $location );
		}

		$where	= array( array( 'template_group=?', $group ) );

		if( $app !== NULL )
		{
			$where[]	= array( 'template_app=?', $app );
		}

		if( $location !== NULL )
		{
			$where[]	= array( 'template_location=?', $location );
		}

		$results	= array();

		foreach( \IPS\Db::i()->select( 'template_name', 'core_theme_templates', $where ) as $result )
		{
			$results[ $result['template_name'] ]	= $result['template_name'];
		}

		return array_unique( $results );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions()
	{
		return false;
	}
	
	/**
	 * Delete CSS
	 * If the CSS is customized in this theme set, it will just delete the custom CSS giving the appearance of a revert.
	 * If the CSS is new in this theme set, then it will be deleted completely.
	 *
	 * @param	int $itemId	Specific CSS ID to remove
	 * @return	array
	 * @throws	\UnderflowException
	 */
	public function deleteCssById( $itemId )
	{
		$children = array( $this->_id );

		foreach( $this->allChildren() as $id => $child )
		{
			$children[] = $child->_id;
		}
		
		try
		{
			$css = \IPS\Db::i()->select( '*', 'core_theme_css', array( 'css_id=?', (int) $itemId ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			throw new \UnderflowException;
		}
		
		static::deleteCompiledCss( $css['css_app'], $css['css_location'], $css['css_path'], $css['css_name'], $css['css_set_id'] );
		
		/* Inherited from master */
		if ( $css['css_added_to'] == 0 )
		{
			/* Inherited from master css */
			if ( $css['css_set_id'] > 0 )
			{
				/* Clear any existing CSS if it's been modified from set 0 */
				\IPS\Db::i()->delete( 'core_theme_css', array( 'css_id=?', (int) $itemId ) );
			}
		}
		else
		{
			/* This is CSS unique to this theme set, so remove it from this set and all children */
			\IPS\Db::i()->delete( 'core_theme_css', array(
											"css_app=? AND css_location=? AND css_path=? AND css_name=? AND css_set_id IN(" . implode( ',', $children ) . ")",
											$css['css_app'], $css['css_location'], $css['css_path'], $css['css_name'] ) );
		}
	
		$csses = $this->getRawCss( array( $css['css_app'] ), array( $css['css_location'] ), array( $css['css_path'] ) );
		
		if ( isset( $csses[ $css['css_app'] ][ $css['css_location'] ][ $css['css_path'] ][ $css['css_name'] ] ) )
		{
			return $csses[ $css['css_app'] ][ $css['css_location'] ][ $css['css_path'] ][ $css['css_name'] ];
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Save CSS
	 * Saves a CSS template
	 *
	 * @param	array	$data	array( 'app' => .., 'location' => .., 'group' => .., 'name' => .., 'set_id' => .., 'item_id' => .., 'position' => .., 'content' => .., 'variables' => .. )
	 * @return	int		Template ID
	 * @throws	\OutOfBoundsException
	 * @throws	\UnderflowException
	 */
	public function saveCss( $data )
	{
		$css      = array();
		$children = array( $this->_id );

		foreach( $this->allChildren() as $child )
		{
			$children[] = $child->_id;
		}
		
		/* Save */
		if ( ! empty( $data['item_id'] ) )
		{
			try
			{
				$css = \IPS\Db::i()->select( '*', 'core_theme_css', array( 'css_id=?', (int) $data['item_id'] ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				throw new \UnderflowException;
			}
		}
		else
		{
			if ( mb_substr( $data['name'], -4 ) != '.css' )
			{
				$data['name'] .= '.css';
			}
			
			/* Set up some default data here */
			$css = array( 'css_app'         => $data['app'],
						  'css_path'        => ( empty( $data['group'] ) ) ? '.' : $data['group'],
						  'css_location'    => $data['location'],
						  'css_name'	    => $data['name'],
						  'css_version'     => \IPS\Theme::load( $data['set_id'] )->long_version,
						  'css_user_edited' => \IPS\Application::load( $data['app'] )->long_version,
					      'css_position'	=> 0,
						  'css_content'     => $data['content'],
						  'css_added_to'    => $data['set_id'] );
		}
		
		/* Unique key */
		$css['css_unique_key'] = md5( $data['set_id'] . ';' . $css['css_app'] . ';' . $css['css_location'] . ';' . $css['css_path'] . ';' . $css['css_name'] );
		
		/* Test to make sure CSS isn't broken */
		try
		{
			static::makeProcessFunction( static::fixResourceTags( $data['content'], $css['css_location'] ), 'css_' . md5( uniqid() . time() ), '', FALSE, TRUE );
		}
		catch( \InvalidArgumentException $e )
		{
			throw new \InvalidArgumentException( $e->getMessage() );
		}

		if ( ! empty( $data['item_id'] ) AND $data['set_id'] == $css['css_set_id'] )
		{
			\IPS\Db::i()->update( 'core_theme_css', array( 'css_attributes'  => ( ! empty( $data['attributes'] ) ) ? $data['attributes'] : '',
														   'css_content'     => $data['content'],
														   'css_user_edited' =>  \IPS\Application::load( $data['app'] )->long_version,
														   'css_updated'     => time() ), array('css_id=?', (int)$data['item_id'] ) );
	
			$newCssId = $data['item_id'];
		}
		else
		{
			/* First modification in this css set */
			$newCssId = \IPS\Db::i()->insert( 'core_theme_css', array( 'css_set_id'      => $data['set_id'],
																	   'css_path'        => $css['css_path'],
																	   'css_location'    => $css['css_location'],
																	   'css_app'    	 => $css['css_app'],
																	   'css_content'     => $data['content'],
																	   'css_name'        => $css['css_name'],
																	   'css_attributes'  => ( ! empty( $data['attributes'] ) ) ? $data['attributes'] : '',
																	   'css_added_to'    => $css['css_added_to'],
																	   'css_position'    => intval( $css['css_position'] ),
																	   'css_version'     => \IPS\Theme::load( $data['set_id'] )->long_version,
																	   'css_user_edited' => \IPS\Application::load( $css['css_app'] )->long_version,
																	   'css_unique_key'  => $css['css_unique_key'],
																	   'css_updated'     => time() ) );
		}
		
		static::deleteCompiledCss( $css['css_app'], $css['css_location'], $css['css_path'], $css['css_name'], $data['set_id'] );
	
		return $newCssId;
	}
	
	/**
	 * Delete a template bit
	 * If the template is customized in this theme set, it will just delete the custom template bit giving the appearance of a revert.
	 * If the template is new in this theme set, then it will be deleted from templates completely.
	 *
	 * @param 	int		$itemId	Actual template ID to remove
	 * @return	array	Template bit row
	 * @throws	\UnderflowException
	 */
	public function deleteTemplateById( $itemId )
	{
		$children = array( $this->_id );

		foreach( $this->allChildren() as $child )
		{
			$children[] = $child->_id;
		}
		
		$template = \IPS\Db::i()->select( '*', 'core_theme_templates', array( 'template_id=?', (int) $itemId ) )->first();
		
		/* Master template bit? */
		if ( $template['template_set_id'] == 0 AND $template['template_added_to'] == 0 )
		{
			return false;
		}
		
		static::deleteCompiledTemplate( $template['template_app'], $template['template_location'], $template['template_group'], $template['template_set_id'] );
		
		/* What to do */
		if ( $template['template_added_to'] == $this->_id  )
		{
			/* Does it exist in set ID 0? */
			$select = \IPS\Db::i()->select( 'template_id', 'core_theme_templates', array(
				"template_set_id=? AND template_user_added=? AND template_name=? AND template_group=? AND template_app=? AND template_location=?",
				0, 0, $template['template_name'], $template['template_group'], $template['template_app'], $template['template_location']
			) );

			/* Is from master theme? */
			if ( count( $select ) )
			{
				\IPS\Db::i()->delete( 'core_theme_templates', array( 'template_id=?', (int) $itemId ) );
			}
			else
			{
				# Remove it from ALL template sets
				\IPS\Db::i()->delete( 'core_theme_templates', array(
											 		 		"template_name=? AND template_group=? AND template_app=? AND template_location=?",
											 		 		 $template['template_name'], $template['template_group'], $template['template_app'], $template['template_location']
											 		 		) );
			}
		}
		else
		{
			\IPS\Db::i()->delete( 'core_theme_templates', array( 'template_id=?', (int) $itemId ) );
		}

		$templates = $this->getRawTemplates( array( $template['template_app'] ), array( $template['template_location'] ), array( $template['template_group'] ) );
		
		if ( isset( $templates[ $template['template_app'] ][ $template['template_location'] ][ $template['template_group'] ][ $template['template_name'] ] ) )
		{
			return $templates[ $template['template_app'] ][ $template['template_location'] ][ $template['template_group'] ][ $template['template_name'] ];
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * User can access this theme?
	 *
	 * @return	bool
	 */
	public function canAccess()
	{
		if ( $this->is_default )
		{
			return true;
		}

		return (bool) ( $this->permissions === '*' OR \IPS\Member::loggedIn()->inGroup( explode( ',', $this->permissions ) ) );
	}
	
	/**
	 * Save Template
	 * Saves a HTML template
	 *
	 * @param	array	$data	array( 'app' => .., 'location' => .., 'group' => .., 'name' => .., 'set_id' => .., 'item_id' => ..,  'content' => .., 'variables' => .. )
	 * @return	int		Template ID
	 * @throws	\OutOfBoundsException
	 * @throws	\UnderflowException
	 */
	public function saveTemplate( $data )
	{
		$template = array();
		$children = array( $this->_id );

		foreach( $this->allChildren() as $child )
		{
			$children[] = $child->_id;
		}
		
		/* Save */
		if ( $data['item_id'] )
		{
			try
			{
				$template = \IPS\Db::i()->select( '*', 'core_theme_templates', array( 'template_id=?', (int) $data['item_id'] ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				throw new \UnderflowException;
			}
		}
		else
		{
			/* Set up some default data here */
			$template = array( 'template_app'        => $data['app'],
							   'template_group'      => $data['group'],
							   'template_location'   => $data['location'],
							   'template_name'	     => $data['name'],
							   'template_added_to'   => $data['set_id'],
							   'template_user_added' => 1 );
		}
		
		/* Unique key */
		$template['template_unique_key'] = md5( $data['set_id'] . ';' . $template['template_app'] . ';' . $template['template_location'] . ';' . $template['template_group'] . ';' . mb_strtolower( $template['template_name'] ) );
		
		/* Test to make sure CSS isn't broken */
		try
		{
			static::makeProcessFunction( $data['content'], 'template_' . md5( uniqid() . time() ), $data['variables'], TRUE, FALSE );
		}
		catch( \InvalidArgumentException $e )
		{
			throw new \InvalidArgumentException( 'core_theme_template_parse_error' );
		}
	
		if ( $data['item_id'] AND $data['set_id'] == $template['template_set_id'] )
		{
			$save = array(
				'template_content'		=> $data['content'],
				'template_user_edited'	=> \IPS\Application::load( $template['template_app'] )->long_version,
				'template_version'		=> \IPS\Theme::load( $template['template_set_id'] )->long_version,
				'template_updated'		=> time()
			);

			if( $data['variables'] )
			{
				$save['template_data']	= $data['variables'];
			}

			\IPS\Db::i()->update( 'core_theme_templates', $save, array( 'template_id=?', (int) $data['item_id'] ) );
		
			$newTemplateId = $data['item_id'];
		}
		else
		{
			/* First modification in this template set or new bit being added */
			$newTemplateId = \IPS\Db::i()->insert( 'core_theme_templates', array( 'template_set_id'      => $data['set_id'],
																				  'template_group'       => $template['template_group'],
																				  'template_location'    => $template['template_location'],
																				  'template_app'    	 => $template['template_app'],
																				  'template_content'     => $data['content'],
																				  'template_name'        => $template['template_name'],
																				  'template_data'        => $data['variables'] ?: $template['template_data'],
																				  'template_added_to'    => $template['template_added_to'],
																				  'template_user_added'  => $template['template_user_added'],
																				  'template_user_edited' => \IPS\Application::load( $template['template_app'] )->long_version,
																				  'template_removable'   => 1,
																				  'template_version'     => \IPS\Theme::load( $data['set_id'] )->long_version,
																				  'template_unique_key'  => $template['template_unique_key'],
																				  'template_updated'     => time() ) );
		}
		
		/* If this is a user-added template bit and we're editing it in the same theme, update master */
		if ( $template['template_id'] && $template['template_user_added'] && $template['template_added_to'] == $data['set_id'] )
		{
			\IPS\Db::i()->update( 'core_theme_templates',
				array( 'template_content' => $data['content'] ),
				array( "template_set_id=? AND template_group=? AND template_name=? AND template_app=? AND template_location=?", 0, $template['template_group'], $template['template_name'], $template['template_app'], $template['template_location'] )
			);
		}
		
		static::deleteCompiledTemplate( $template['template_app'], $template['template_location'], $template['template_group'], $data['set_id'] );
		
		return $newTemplateId;
	}
	
	/**
	 * Returns theme setting DB data with a special array _value which holds the 'true' value fo this setting.
	 *
	 * @param	$flags		Bit option flags
	 * @return	array
	 */
	public function getThemeSettings( $flags=0 )
	{
		$settings = array();
		
		$rows = \IPS\Db::i()->select( 'sc.*, sv.sv_value', array('core_theme_settings_fields', 'sc'), array( 'sc.sc_set_id=?', $this->id ) )
			->join( array('core_theme_settings_values', 'sv'), 'sv.sv_id=sc.sc_id' );
			
		foreach ( $rows as $row )
		{
			$row['_value'] = ( array_key_exists( 'sv_value', $row ) AND $row['sv_value'] !== NULL ) ? $row['sv_value'] : $row['sc_default'];

			if ( $row['_value'] and $row['sc_type'] === 'Upload' )
			{
				try
				{
					$row['_value'] = (string) \IPS\File::get( 'core_Theme', $row['_value'] )->url;
				}
				catch( \Exception $ex )
				{

				}
			}

			if ( $flags & static::THEME_ID_KEY )
			{
				$settings[ $row['sc_id'] ] = $row;
			}
			else if ( $flags & static::THEME_KEY_VALUE_PAIRS )
			{
				$settings[ $row['sc_key'] ] = $row['_value'];
			}
			else
			{
				$settings[ $row['sc_key'] ] = $row;
			}
		}
		
		return $settings;
	}
	
	/**
	 * Get raw CSS. Raw means {resource..} tags and uncompiled
	 *
	 * @param string|array	$app				CSS app (e.g. core, forum)
	 * @param string|array	$location			CSS location (e.g. admin,global,front)
	 * @param string|array	$path				CSS group (e.g. custom, framework)
	 * @param int|constant	$returnType			Determines the content returned
	 * @param boolean		$returnThisSetOnly  Returns rows unique to this set only
	 * @return array
	 */
	public function getRawCss( $app=array(), $location=array(), $path=array(), $returnType=null, $returnThisSetOnly=false )
	{
		$returnType = ( $returnType === null )   ? static::RETURN_ALL   : $returnType;
		$app        = ( is_string( $app )      AND $app != ''      ) ? array( $app )      : $app;
		$location   = ( is_string( $location ) AND $location != '' ) ? array( $location ) : $location;
		$path       = ( is_string( $path )     AND $path != ''    )  ? array( $path )     : $path;
		$where      = array();
		$css	    = array();
	
		$parents = array( $this->_id );

		try
		{
			$allParents = array();
			foreach( $this->parents() as $parent )
			{
				$allParents[] = $parent->_id;
			}
			
			if ( count( $allParents ) )
			{
				foreach( array_reverse( $allParents ) as $id )
				{
					$parents[] = $id;
				}
			}
		}
		catch( \OutOfRangeException $e ) { }

		/* Append master theme set */
		array_push( $parents, 0 );
		
		$where[] = "css_set_id IN (" . implode( ',' , $parents ) . ")";
		
		if ( is_array( $app ) AND count( $app ) )
		{
			$where[] = "css_app IN ('" . implode( "','", $app ) . "')";
		}
	
		if ( is_array( $location ) AND count( $location ) )
		{
			$where[] = "css_location IN ('" . implode( "','", $location ) . "')";
		}
	
		if ( is_array( $path ) AND count( $path ) )
		{
			$where[] = "css_path IN ('" . implode( "','", $path ) . "')";
		}
		
		$select = ( $returnType & static::RETURN_BIT_NAMES ) ? 'css_app, css_location, css_path, css_set_id, css_id, css_name, css_modules, css_attributes, css_removed, css_added_to, css_hidden' : '*';
	
		foreach(
			\IPS\Db::i()->select(
				$select . ', INSTR(\',' . implode( ',' , $parents ) . ',\', CONCAT(\',\',css_set_id,\',\') ) as theorder',
				'core_theme_css',
				implode( " AND ", $where ),
				'css_location, css_path, css_name, theorder desc'
			)
			as $row
		) {
			/* App installed? */
			if ( ! \IPS\Application::appIsEnabled( $row['css_app'] ) )
			{
				continue;
			}
			
			/* This set only? */
			if ( $returnThisSetOnly === true )
			{
				if ( $row['css_set_id'] != $this->_id )
				{
					continue;
				}
			}
			
			/* CSS has been removed up the tree? */
			if ( ! empty( $row['css_removed'] ) )
			{
				continue;
			}
			
			/* CSS not to be included */
			if ( ! empty( $row['css_hidden'] ) )
			{
				continue;
			}

			if ( $row['css_set_id'] === 0 )
			{
				$row['InheritedValue'] = 'original';
			}
			else if ( $row['css_set_id'] != $this->_id )
			{
				$row['InheritedValue'] = 'inherit';
			}
			else if ( $row['css_added_to'] != 0 )
			{
				$row['InheritedValue'] = 'custom';
			}
			else
			{
				$row['InheritedValue'] = ( $row['css_user_edited'] < \IPS\Application::load( $row['css_app'] )->long_version ) ? 'outofdate' : 'changed';
			}
			
			/* ensure set ID is correct */
			$row['css_set_id']  = $this->_id;
			$row['CssKey']      = str_replace( '.css', '', $row['css_app'] . '_' . $row['css_location'] . '_' . $row['css_path'] . '_' . $row['css_name'] );
			$row['jsDataKey']   = str_replace( '.', '--', $row['CssKey'] );
			
			if ( $returnType & static::RETURN_ALL_NO_CONTENT )
			{
				unset( $row['css_content'] );
				$css[ $row['css_app'] ][ $row['css_location'] ][ $row['css_path'] ][ $row['css_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_ALL )
			{
				$css[ $row['css_app'] ][ $row['css_location'] ][ $row['css_path'] ][ $row['css_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_BIT_NAMES )
			{
				$css[ $row['css_app'] ][ $row['css_location'] ][ $row['css_path'] ][] = $row['css_name'];
			}
			else if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
			{
				$css[] = $row['css_name'];
			}
		}
	
		if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
		{
			sort( $css );
			return $css;
		}
		
		ksort( $css );
	
		/* Pretty sure Mark can turn this into a closure */
		foreach( $css as $k => $v )
		{
			ksort( $css[ $k ] );
				
			foreach( $css[ $k ] as $ak => $av )
			{
				ksort( $css[ $k ][ $ak ] );
					
				if ( $returnType & static::RETURN_ALL )
				{
					foreach( $css[ $k ][ $ak ] as $bk => $bv )
					{
						ksort( $css[ $k ][ $ak ][ $bk ] );
					}
				}
			}
		}

		return $css;
	}
	
	/**
	 * Get raw templates. Raw means HTML logic and variables are still in {{format}}
	 *
	 * @param string|array	$app				Template app (e.g. core, forum)
	 * @param string|array	$location			Template location (e.g. admin,global,front)
	 * @param string|array	$group				Template group (e.g. login, share)
	 * @param int|constant	$returnType			Determines the content returned
	 * @param boolean		$returnThisSetOnly  Returns rows unique to this set only
	 * @return array
	 */
	public function getRawTemplates( $app=array(), $location=array(), $group=array(), $returnType=null, $returnThisSetOnly=false )
	{
		$returnType = ( $returnType === null )  ? static::RETURN_ALL   : $returnType;
		$app        = ( is_string( $app )      AND $app != ''      ) ? array( $app )      : $app;
		$location   = ( is_string( $location ) AND $location != '' ) ? array( $location ) : $location;
		$group      = ( is_string( $group )    AND $group != ''    ) ? array( $group )    : $group;
		$where      = array();
		$templates  = array();

		$parents = array( $this->_id );

		try
		{
			$allParents = array();
			foreach( $this->parents() as $parent )
			{
				$allParents[] = $parent->_id;
			}
			
			if ( count( $allParents ) )
			{
				foreach( array_reverse( $allParents ) as $id )
				{
					$parents[] = $id;
				}
			}
		}
		catch( \OutOfRangeException $e ) { }

		/* Append master theme set */
		array_push( $parents, 0 );
		
		$where[] = "template_set_id IN (" . implode( ',' , $parents ) . ")";
		
		if ( is_array( $app ) AND count( $app ) )
		{
			$where[] = "template_app IN ('" . implode( "','", $app ) . "')";
		}
		
		if ( is_array( $location ) AND count( $location ) )
		{
			$where[] = "template_location IN ('" . implode( "','", $location ) . "')";
		}
		
		if ( is_array( $group ) AND count( $group ) )
		{
			$where[] = "template_group IN ('" . implode( "','", $group ) . "')";
		}
	
		$select = ( $returnType & static::RETURN_BIT_NAMES ) ? 'template_added_to, template_app, template_location, template_group, template_set_id, template_id, template_name, template_data' : '*';
		
		foreach(
			\IPS\Db::i()->select(
				$select . ', INSTR(\',' . implode( ',' , $parents ) . ',\', CONCAT(\',\',template_set_id,\',\') ) as theorder',
				'core_theme_templates',
				implode( " AND ", $where ),
				'template_location, template_group, template_name, theorder desc'
			)
			as $row
		) {
			/* App installed? */
			if ( ! \IPS\Application::appIsEnabled( $row['template_app'] ) )
			{
				continue;
			}
			
			/* This set only? */
			if ( $returnThisSetOnly === true )
			{
				if ( $row['template_set_id'] != $this->_id )
				{
					continue;
				}
			}
			
			if ( $row['template_set_id'] === 0 and ! $row['template_user_added'] )
			{
				$row['InheritedValue'] = 'original';
			}
			else if ( $row['template_user_added'] and in_array( $row['template_added_to'], array_values( $allParents ) ) )
			{
				$row['InheritedValue'] = 'inherit';
			}
			else if ( $row['template_user_added'] and $row['template_added_to'] != $this->_id )
			{
				$row['InheritedValue'] = 'original';
			}
			else if ( $row['template_user_added'] )
			{
				$row['InheritedValue'] = 'custom';
			}
			else
			{
				$row['InheritedValue'] = ( $row['template_user_edited'] < \IPS\Application::load( $row['template_app'] )->long_version ) ? 'outofdate' : 'changed';
			}
			
			/* ensure set ID is correct */
			$row['template_set_id'] = $this->_id;
			$row['TemplateKey']     = $row['template_app'] . '_' . $row['template_location'] . '_' . $row['template_group'] . '_' . $row['template_name'];
			$row['jsDataKey']       = str_replace( '.', '--', $row['TemplateKey'] );
			
			if ( $returnType & static::RETURN_ALL_NO_CONTENT )
			{
				unset( $row['template_content'] );
				$templates[ $row['template_app'] ][ $row['template_location'] ][ $row['template_group'] ][ $row['template_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_ALL )
			{
				$templates[ $row['template_app'] ][ $row['template_location'] ][ $row['template_group'] ][ $row['template_name'] ] = $row;
			}
			else if ( $returnType & static::RETURN_BIT_NAMES )
			{
				$templates[ $row['template_app'] ][ $row['template_location'] ][ $row['template_group'] ][] = $row['template_name'];
			}
			else if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
			{
				$templates[] = $row['template_name'];
			}
		}
	
		if ( $returnType & static::RETURN_ARRAY_BIT_NAMES )
		{
			sort( $templates );
			return $templates;
		}
		
		ksort( $templates );

		/* Pretty sure Mark can turn this into a closure */
		foreach( $templates as $k => $v )
		{
			ksort( $templates[ $k ] );
			
			foreach( $templates[ $k ] as $ak => $av )
			{
				ksort( $templates[ $k ][ $ak ] );
			
				if ( $returnType & static::RETURN_ALL )
				{
					foreach( $templates[ $k ][ $ak ] as $bk => $bv )
					{
						ksort( $templates[ $k ][ $ak ][ $bk ] );
					}
				}
			}
		}

		return $templates;
	}
	
	/**
	 * Save the visual skin editor CSS
	 *
	 * @param	string $vseCss		VSE Generated CSS
	 * @param	string $customCss		User added CSS
	 * @param	array  $settings		Settings to update
	 * @param	array  $values		JSON string of data from the VSE
	 * @return	void
	 */
	public function vseSave( $vseCss, $customCss, $settings, $values )
	{
		$css = $this->getRawCss( 'core', 'front', 'custom', static::RETURN_ALL, true );

		$vse = array( 'app' 	 => 'core',
					  'location' => 'front',
					  'group'    => 'custom',
					  'name'	 => 'vse.css',
					  'set_id'   => $this->id,
					  'content'  => $vseCss
		);
		
		$custom = array( 'app' 	    => 'core',
						 'location' => 'front',
						 'group'    => 'custom',
						 'name'	    => 'custom.css',
						 'set_id'   => $this->id,
						 'content'  => $customCss
		);
		
		/* Do we have vse already? */
		if ( isset( $css['core']['front']['custom']['vse.css'] ) )
		{
			$vse['item_id'] = $css['core']['front']['custom']['vse.css']['css_id'];
		}
		
		/* Do we have custom already? */
		if ( isset( $css['core']['front']['custom']['custom.css'] ) )
		{
			$custom['item_id'] = $css['core']['front']['custom']['custom.css']['css_id'];
		}
		
		$this->saveCss( $vse );
		$this->saveCss( $custom );
		
		$this->skin_gen_data = $values;
		$this->save();
		
		/* Settings first */
		$themeSettings      = $this->getThemeSettings( static::THEME_ID_KEY );
		$themeSettingsByKey = array();
		
		foreach( $themeSettings as $themeSettingId => $themeSettingData )
		{
			$themeSettingsByKey[ $themeSettingData['sc_key'] ] = $themeSettingData;
		}
		
		if ( is_array( $settings ) )
		{
			foreach( $settings as $key => $value )
			{
				if ( \stristr( $key, 'core_theme_setting_title_' ) )
				{ 
					$keyId = str_replace( 'core_theme_setting_title_', '', $key );
				
					if ( isset( $themeSettings[ $keyId ] ) )
					{
						\IPS\Db::i()->delete('core_theme_settings_values', array( 'sv_id=?', $keyId ) );
						\IPS\Db::i()->insert('core_theme_settings_values', array( 'sv_id' => $keyId, 'sv_value' => (string)$value ) );
					}
				}
				else
				{
					if ( isset( $themeSettingsByKey[ $key ] ) )
					{
						\IPS\Db::i()->delete('core_theme_settings_values', array( 'sv_id=?', $themeSettingsByKey[ $key ]['sc_id'] ) );
						\IPS\Db::i()->insert('core_theme_settings_values', array( 'sv_id' => $themeSettingsByKey[ $key ]['sc_id'], 'sv_value' => (string)$value ) );
					}
				}
			}
			
			$this->saveSet();
		}
		
		static::deleteCompiledCss( 'core', 'front', 'custom', null, $this->id );
	}
	
	/**
	 * Save a theme set
	 *
	 * @param	array $data	Skin set data
	 * @return	void
	 */
	public function saveSet( $data=array() )
	{
		$save    = array();
		$fields  = array( 'name', 'key', 'parent_id', 'permissions', 'is_default', 'is_acp_default', 'author_name', 'author_url', 'resource_dir', 'emo_dir', 'hide_from_list', 'order', 'version', 'long_version', 'update_check' );

		foreach( $fields as $k )
		{
			if ( isset( $data[ 'set_' . $k ] ) )
			{
				$save[ 'set_' . $k ] = $data[ 'set_' . $k ];
			}
		}
		
		if ( ! $this->_id )
		{
			$save['set_long_version'] = ( ! empty( $save['set_long_version'] ) ) ? (int) $save['set_long_version'] : \IPS\Application::load( 'core' )->long_version;
		}
		else if ( isset( $save['set_long_version'] ) )
		{
			$save['set_long_version'] = intval( $save['set_long_version'] );
		}
		
		foreach( array( 'front', 'sharer', 'favicon' ) as $icon )
		{
			if ( isset( $data['logo'][ $icon ] ) )
			{
				$this->_data['logo'][ $icon ] = $data['logo'][ $icon ];
			}
		}
		
		if ( isset( $data['set_name'] ) )
		{
			\IPS\Lang::saveCustom( 'core', "core_theme_set_title_{$this->_id}", $data['set_name'] );
		}
		
		$save['set_logo_data'] = ( isset( $this->_data['logo'] ) ? json_encode( $this->_data['logo'] ) : '{}' );
		
		if ( isset( $data['set_css_map'] ) )
		{
			$save['set_css_map'] = json_encode( $data['set_css_map'] );
		}
		
		if ( isset( $data['set_resource_map'] ) )
		{
			$save['set_resource_map'] = json_encode( $data['set_resource_map'] );
		}
		
		$json          = array();
		$themeSettings = $this->getThemeSettings();
		
		foreach ( $themeSettings as $key => $row )
		{
			$json[ $row['sc_key'] ] = $row['_value'];
		}
		
		$save['set_template_settings'] = json_encode( $json );
		
		\IPS\Db::i()->update( 'core_themes', $save, array( 'set_id=?', (int) $this->_id ) );
	}
	
	/**
	 * Copies all current theme templates and CSS to the history table for use with diff and/or conflict checking
	 * when importing new templates.
	 *
	 * @return void
	 */
	public function saveHistorySnapshot()
	{
		/* Remove all current template records for this theme set */
		\IPS\Db::i()->delete( 'core_theme_content_history', array( 'content_set_id=?', $this->id ) );
	
		/* Templates */
		\IPS\Db::i()->insert( 'core_theme_content_history', \IPS\Db::i()->select( "null, template_set_id, 'template', template_app, template_location, template_group, template_name, template_data, template_content, IFNULL(template_version, 10000), template_updated", 'core_theme_templates', array( 'template_set_id=?', $this->id ) ) );
	
		/* CSS */
		\IPS\Db::i()->insert( 'core_theme_content_history', \IPS\Db::i()->select( "null, css_set_id, 'css', css_app, css_location, css_path, css_name, css_attributes, css_content, IFNULL(css_version, 10000), css_updated", 'core_theme_css', array( 'css_set_id=?', $this->id ) ) );
	}
	
	/**
	 * Get diffs. Returns an array of CSS and template diffs between latest version and previous version.
	 *
	 * @return	array	array( 'templates' => array(), 'css' => array() )
	 */
	public function getDiff()
	{
		$templates = array();
		$css       = array();
		$results   = array( 'template' => array(), 'css' => array() );
		$history   = array( 'template' => array(), 'css' => array() );
		
		require_once \IPS\ROOT_PATH . "/system/3rd_party/Diff/class.Diff.php";
		
		foreach( \IPS\Db::i()->select(
				"*, MD5( CONCAT( content_app, '.', content_location, '.', content_path, '.', content_name ) ) as bit_key",
				'core_theme_content_history',
				array( 'content_set_id=?', $this->id )
		)->setKeyField('bit_key') as $key => $data )
		{
			if ( $data['content_type'] == 'template' )
			{
				$history['template'][ $key ] = $data;
			}
			else
			{
				$history['css'][ $key ] = $data;
			}
		}

		$results['templates'] = iterator_to_array( \IPS\Db::i()->select(
				'*, MD5( CONCAT( template_app, ".", template_location, ".", template_group, ".", template_name ) ) as bit_key',
				'core_theme_templates',
				array( 'template_set_id=?', $this->id )
		)->setKeyField('bit_key') );
		
		$results['css'] = iterator_to_array( \IPS\Db::i()->select(
				'*, MD5( CONCAT( css_app, ".", css_location, ".", css_path, ".", css_name ) ) as bit_key',
				'core_theme_css',
				array( 'css_set_id=?', $this->id )
		)->setKeyField('bit_key') );
		
		/*header( "Content-type: text/plain");
		
		foreach( $results['templates'] as $key => $row )
		{
			print $row['bit_key'] . ' ' . $row['template_name'];

			if ( isset( $history['template'][ $key ] ) )
			{
				print ' = ' . $history['template'][ $key ]['content_name'];
			}
			
			print "\n";
		}
		
		exit();*/
		
		/* Find changed and new template bits */
		foreach( $results['templates'] as $key => $data )
		{
			$data['added']   = false;
			$data['deleted'] = false;
			
			if ( isset( $history['template'][ $key ] ) )
			{
				$data['oldHumanVersion'] = \IPS\Application::load( $history['template'][ $key ]['content_app'] )->getHumanVersion( $history['template'][ $key ]['content_long_version'] );
				$data['newHumanVersion'] = \IPS\Application::load( $results['templates'][ $key ]['template_app'] )->getHumanVersion( $results['templates'][ $key ]['template_version'] );
				
				if ( md5( $history['template'][ $key ]['content_content'] ) != md5( $data['template_content'] ) )
				{
					$data['diff'] = \Diff::toTable( \Diff::compare( $history['template'][ $key ]['content_content'], $data['template_content'] ) );
				}
				else
				{
					unset( $results['templates'][ $key ] );
					unset( $history['template'][ $key ] );
					continue;
				}
			}
			else
			{
				$data['added'] = true;
				$data['diff']  = \Diff::toTable( \Diff::compare( '', $data['template_content'] ) );
			}
			
			$templates[ $data['template_app'] ][ $data['template_location'] ][ $data['template_group'] ][ $data['template_name'] ] = $data;
		}
		
		/* Find changed and new CSS bits */
		foreach( $results['css'] as $key => $data )
		{
			$data['added']   = false;
			$data['deleted'] = false;
			
			if ( isset( $history['css'][ $key ] ) )
			{
				$data['oldHumanVersion'] = \IPS\Application::load( $history['css'][ $key ]['content_app'] )->getHumanVersion( $history['css'][ $key ]['content_long_version'] );
				$data['newHumanVersion'] = \IPS\Application::load( $results['css'][ $key ]['css_app'] )->getHumanVersion( $results['css'][ $key ]['css_version'] );
				
				if ( md5( $history['css'][ $key ]['content_content'] ) != md5( $data['css_content'] ) )
				{
					$data['diff'] = \Diff::toTable( \Diff::compare( $history['css'][ $key ]['content_content'], $data['css_content'] ) );
				}
				else
				{
					unset( $results['css'][ $key ] );
					unset( $history['css'][ $key ] );
					continue;
				}
			}
			else
			{
				$data['added'] = true;
				$data['diff']  = \Diff::toTable( \Diff::compare( '', $data['css_content'] ) );
			}
			
			$css[ $data['css_app'] ][ $data['css_location'] ][ $data['css_path'] ][ $data['css_name'] ] = $data;
		}
	
		/* Find deleted template bits */
		foreach( array_diff( array_keys( $history['template'] ), array_keys( $results['templates'] ) ) as $key )
		{
			$data = $history['template'][ $key ];
			
			$templates[ $data['content_app'] ][ $data['content_location'] ][ $data['content_path'] ][ $data['content_name'] ] = array(
				'template_app' 		=> $data['content_app'],
				'template_location' => $data['content_location'],
				'template_group'    => $data['content_path'],
				'template_name'     => $data['content_name'],
				'template_content'  => $data['content_content'],
				'diff'    			=> \Diff::toTable( \Diff::compare( $history['template'][ $key ]['content_content'], '' ) ),
				'added'				=> false,
				'deleted'			=> true
			);
		}
		
		/* Find deleted CSS bits */
		foreach( array_diff( array_keys( $history['css'] ), array_keys( $results['css'] ) ) as $key )
		{
			$data = $history['css'][ $key ];
			
			$css[ $data['content_app'] ][ $data['content_location'] ][ $data['content_path'] ][ $data['content_name'] ] = array(
				'css_app' 		=> $data['content_app'],
				'css_location' 	=> $data['content_location'],
				'css_path'    	=> $data['content_path'],
				'css_name'     	=> $data['content_name'],
				'css_content'  	=> $data['content_content'],
				'diff'    		=> \Diff::toTable( \Diff::compare( $history['css'][ $key ]['content_content'], '' ) ),
				'added'			=> false,
				'deleted'		=> true
			);
		}
			
		/* Now sort */
		foreach( $templates as $k => $v )
		{
			ksort( $templates[ $k ] );
				
			foreach( $templates[ $k ] as $ak => $av )
			{
				ksort( $templates[ $k ][ $ak ] );
					
				foreach( $templates[ $k ][ $ak ] as $bk => $bv )
				{
					ksort( $templates[ $k ][ $ak ][ $bk ] );
				}
			}
		}
		
		foreach( $css as $k => $v )
		{
			ksort( $css[ $k ] );
		
			foreach( $css[ $k ] as $ak => $av )
			{
				ksort( $css[ $k ][ $ak ] );
					
				foreach( $css[ $k ][ $ak ] as $bk => $bv )
				{
					ksort( $css[ $k ][ $ak ][ $bk ] );
				}
			}
		}
		
		return array( 'templates' => $templates, 'css' => $css );
	}
	
	/**
	 * Delete compiled templates
	 * Removes compiled templates bits for all themes that match the arguments
	 *
	 * @param	string		$app		Application Directory (core, forums, etc)
	 * @param	string|null	$location	Template location (front, admin, global, etc)
	 * @param	string|null	$group		Template group (forms, messaging, etc)
	 * @param	int|null	$themeId	Limit to a specific theme (and children)
	 * @return 	void
	 */
	public static function deleteCompiledTemplate( $app=null, $location=null, $group=null, $themeId=null )
	{
		$where     = array();
		$themeSets = array( 0 );

		if ( $app !== NULL )
		{
			$where[] = array( 'template_app=?', $app );
		}

		if ( $location !== null )
		{
			$where[] = array( \IPS\Db::i()->in( 'template_location', ( is_array( $location ) ) ? $location : array( $location ) ) );
		}
		
		if ( $group !== null )
		{
			$where[] = array( \IPS\Db::i()->in( 'template_group', ( is_array( $group ) ) ? $group : array( $group ) ) );
		}

		if ( ! empty( $themeId ) )
		{
			$themeSet  = static::load( $themeId );
			$themeSets = array( $themeId => $themeSet ) + $themeSet->allChildren();
			
			$where[] = array( \IPS\Db::i()->in( 'template_set_id', array_keys( $themeSets ) ) );
		}
				
		foreach(
			\IPS\Db::i()->select(
				"template_app, template_location, template_group, MD5( CONCAT(',', template_app, ',', template_location, ',', template_group) ) as group_key",
				'core_theme_templates',
				$where,
				NULL, NULL, array( 'group_key', 'template_group', 'template_app', 'template_location' )
			)
			as $groupKey => $data
		){
			/* ... remove from each theme */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					$key = \strtolower( 'template_' . $set->id . '_' .static::makeBuiltTemplateLookupHash( $data['template_app'], $data['template_location'], $data['template_group'] ) . '_' . static::cleanGroupName( $data['template_group'] ) );
			
					unset( \IPS\Data\Store::i()->$key );
				}
			}
		}
	}
	
	/**
	 * Delete compiled Css
	 * Removes compiled Css for all themes that match the arguments
	 *
	 * @param	string|null	$app		CSS Directory (core, forums, etc)
	 * @param	string|null	$location	CSS location (front, admin, global, etc)
	 * @param	string|null	$path		CSS path (forms, messaging, etc)
	 * @param	string|null $name		CSS file to remove
	 * @param	int|null	$themeId	Limit to a specific theme (and children)
	 * @return 	void
	 */
	public static function deleteCompiledCss( $app=null, $location=null, $path=null, $name=null, $themeId=null )
	{
		$where     = array();
		$themeSets = array( 0 );

		if ( $themeId !== null )
		{
			$themeSet  = static::load( $themeId );
			$themeSets = array( $themeId => $themeSet ) + $themeSet->allChildren();	
				
			$where[] = array( \IPS\Db::i()->in( 'css_set_id', array_keys( $themeSets ) ) );
		}

		if ( $app === null )
		{
			/* Each theme... */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					\IPS\File::getClass( 'core_Theme')->deleteContainer('css_built_' . $set->_id );

					$set->css_map = array();
					$set->save();
				}
			}

			/* Done */
			return;
		}

		/* Deconstruct build grouping */
		if ( $name !== null )
		{
			if ( isset( static::$buildGrouping['css'][ $app ][ $location ] ) )
			{
				foreach( static::$buildGrouping['css'][ $app ][ $location ] as $grouped )
				{
					if ( str_replace( '.css', '', $name ) == $grouped )
					{
						$path = $grouped;
						$name = null;
					}
				}
			}
		}

		if ( $app !== null )
		{
			$where[] = array( \IPS\Db::i()->in( 'css_app', ( is_array( $app ) ) ? $app : array( $app ) ) );
		}
		if ( $location !== null )
		{
			$where[] = array( \IPS\Db::i()->in( 'css_location', ( is_array( $location ) ) ? $location : array( $location ) ) );
		}
	
		if ( $path !== null )
		{
			$where[] = array( 'css_path=?', $path );
		}

		$css = iterator_to_array( \IPS\Db::i()->select( "*", 'core_theme_css', $where )->setKeyField('css_id') );
		if ( count( $css ) )
		{
			/* Each theme... */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					$built = array();
					$map   = $set->css_map;
					
					foreach( $css as $cssId => $data )
					{
						if ( isset( static::$buildGrouping['css'][ $data['css_app'] ][ $data['css_location'] ] ) AND in_array( $data['css_path'], static::$buildGrouping['css'][ $data['css_app'] ][ $data['css_location'] ] ) )
						{
							$key = static::makeBuiltTemplateLookupHash( $data['css_app'], $data['css_location'], $data['css_path'] );
					
							if ( ! isset( $built[ $key ] ) )
							{
								$data['css_name']    = $data['css_path'] . '.css';
								$data['css_path']    = '.';
								$data['css_content'] = '';
								
								$built[ $key ] = $data;
							}
						}
						else
						{
							/* ... remove the CSS Files */
							$key = static::makeBuiltTemplateLookupHash( $data['css_app'], $data['css_location'], $data['css_path'] . '/' . $data['css_name'] );
							
							if ( isset( $map[ $key ] ) )
							{
								\IPS\File::get( 'core_Theme', $map[ $key ] )->delete();
								unset( $map[ $key ] );
							}
						}
					}
				
					/* Write combined css */
					if ( count( $built ) )
					{ 
						foreach( $built as $id => $cssData )
						{
							$key = static::makeBuiltTemplateLookupHash( $cssData['css_app'], $cssData['css_location'], $cssData['css_path'] . '/' . $cssData['css_name'] );
					
							if ( isset( $map[ $key ] ) )
							{
								\IPS\File::get( 'core_Theme', $map[ $key ] )->delete();
								unset( $map[ $key ] );
							}
						}
					}
					
					/* Update mappings */
					$set->css_map = $map;
					$set->save();
				}
			}
		}
	}
	
	/**
	 * Delete compiled resources
	 * Removes stored resource file objects and associated mappings but doesn't actually remove the resource
	 * row from the database.
	 *
	 * @param	string|null	$app		App Directory (core, forums, etc)
	 * @param	string|null	$location	location (front, admin, global, etc)
	 * @param	string|null	$path		Path (forms, messaging, etc)
	 * @param	string|null $name		Resource file to remove
	 * @param	int|null	$themeId	Limit to a specific theme (and children)
	 * @return 	void
	 */
	public static function deleteCompiledResources( $app=null, $location=null, $path=null, $name=null, $themeId=null )
	{
		$query     = array();
		$themeSets = null;
		$map       = array();
		
		if ( ! empty( $themeId ) )
		{
			$themeSet    = static::load( $themeId );
			$themeSets = array( $themeId => $themeSet ) + $themeSet->allChildren();
		}

		if ( $app === null )
		{
			/* Each theme... */
			foreach( static::themes() as $id => $set )
			{
				if ( $themeId === null OR in_array( $id, array_keys( $themeSets ) ) )
				{
					\IPS\File::getClass( 'core_Theme' )->deleteContainer('set_resources_' . $set->_id );

					$set->resource_map = array();
					$set->save();
				}
			}
		}

		if ( $app !== NULL )
		{
			$query[] = \IPS\Db::i()->in( 'resource_app', ( is_array( $app ) ) ? $app : array( $app ) );
		}

		if ( $location !== null )
		{
			$query[] = \IPS\Db::i()->in( 'resource_location', ( is_array( $location ) ) ? $location : array( $location ) );
		}
		
		if ( $path !== null )
		{
			$query[] = \IPS\Db::i()->in( 'resource_path', ( is_array( $path ) ) ? $path : array( $path ) );
		}
		
		if ( $themeId !== null )
		{
			$query[] = \IPS\Db::i()->in( 'resource_set_id', array_keys( $themeSets ) );
		}

		if ( $app !== NULL )
		{
			foreach ( \IPS\Db::i()->select( "*", 'core_theme_resources', array( implode( ' AND ', $query ) ) ) as $row )
			{
				try
				{
					if ( !isset( $set ) OR !isset( $map[ $set->id ] ) )
					{
						$set = static::load( $row['resource_set_id'] );

						$map[ $set->id ] = $set->resource_map;
					}

					$name = static::makeBuiltTemplateLookupHash( $row['resource_app'], $row['resource_location'], $row['resource_path'] ) . '_' . $row['resource_name'];

					if ( isset( $map[ $set->id ][ $name ] ) )
					{
						unset( $map[ $set->id ][ $name ] );
					}
				}
				catch ( \OutOfRangeException $ex )
				{
					$map[$row['resource_set_id']] = array();
				}

				try
				{
					if ( $row['resource_filename'] )
					{
						\IPS\File::get( 'core_Theme', $row['resource_filename'] )->delete();
					}
				}
				catch ( \InvalidArgumentException $ex )
				{
				}
			}
		}

		\IPS\Db::i()->update( 'core_theme_resources', array( 'resource_filename' => null ), ( count( $query ) ? array( implode( ' AND ', $query ) ) : NULL ) );
		
		/* Update mappings */
		foreach( $map as $setId => $data )
		{
			try
			{
				$set = static::load( $setId );
				$set->resource_map = $data;
				$set->save();
				$set->saveSet();
			}
			catch( \OutOfRangeException $ex ) { }
		}
	}
	
	/**
	 * Process Theme Hooks
	 *
	 * @param	string	$rawContent			The current (uncompiled) template bit contents
	 * @param	array	$hookData			Hook data
	 * @return	string						The (uncompiled) template bit contents, with theme hooks
	 */
	public static function themeHooks( $rawContent, $hookData )
	{
		/* Encode any {{PHP code}}, {$var}s and {tag=""} tags to stop phpQuery encoding it */
		$phpQueryI = 0;
		$phpQueryStore = array();
		$content = preg_replace_callback( array( '/{{?(?>[^{}]|(?R))*}?}/', '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/' ), function( $matches ) use ( &$phpQueryI, &$phpQueryStore )
		{
			$phpQueryStore[ ++$phpQueryI ] = $matches[0];
			return 'he-' . $phpQueryI . '--';
		}, $rawContent );
												
		/* Swap out certain tags that confuse phpQuery */
		$content = preg_replace( '/<(\/)?(html|head|body)(>| (.+?))/', '<$1temp$2$3', $content );
		$content = str_replace( '<!DOCTYPE html>', '<tempdoctype></tempdoctype>', $content );
			
		/* Load phpQuery  */
		require_once \IPS\ROOT_PATH . '/system/3rd_party/phpQuery/phpQuery.php';
		libxml_use_internal_errors(TRUE);
		$phpQuery = \phpQuery::newDocumentHTML( '<ipscontent id="ipscontent">' . $content . '</ipscontent>' );
					
		/* Loop through all the hooks on this template bit */
		foreach ( $hookData as $hook )
		{
			/* Encode */
			if ( isset( $hook['content'] ) )
			{
				$hook['content'] = preg_replace_callback( array( '/{{?.+?}?}/', '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/' ), function( $matches ) use ( &$phpQueryI, &$phpQueryStore )
				{
					$phpQueryStore[ ++$phpQueryI ] = $matches[0];
					return 'he-' . $phpQueryI . '--';
				}, $hook['content'] );
			}
								
			/* If the selector uses [attribute=""] syntax, we need to make the attribute lowercase since phpQuery is sensitive to that */
			$hook['selector'] = preg_replace_callback( '/\[([^\s\/<>\'"=]+)(=("[^"&]*"|\'[^\'&]*\'|[^\s=\'"<>`]*))?\]/i', function( $matches )
			{
				return '[' . mb_strtolower( $matches[1] ) . ( isset( $matches[2] ) ? $matches[2] : '' ) . ']';
			}, $hook['selector'] );
			
			/* Do stuff */
			$results = \pq( '#ipscontent ' . preg_replace( '/\b(html|head|body)\b/', 'temp$1', $hook['selector'] ) );

			switch ( $hook['type'] )
			{
				case 'add_before':
					$results->before( $hook['content'] );
					break;
				
				case 'add_inside_start':
					$results->prepend( $hook['content'] );
					break;
				
				case 'add_inside_end':
					$results->append( $hook['content'] );
					break;
				
				case 'add_after':
					$results->after( $hook['content'] );
					break;
					
				case 'add_class':
					foreach ( $hook['css_classes'] as $cssClass )
					{
						$results->addClass( $cssClass );
					}
					break;
					
				case 'remove_class':
					foreach ( $hook['css_classes'] as $cssClass )
					{
						$results->removeClass( $cssClass );
					}
					break;
					
				case 'add_attribute':
					foreach ( $hook['attributes_add'] as $attribute )
					{
						$results->attr( $attribute['key'], $attribute['value'] );
					}
					break;
					
				case 'remove_attribute':
					foreach ( $hook['attributes_remove'] as $attr )
					{
						$results->removeAttr( $attr );
					}
					break;
				
				case 'replace':
					$results->replaceWith( $hook['content'] );
					break;
			}
		}
						
		/* Put our {{PHP code}} back */
		$return = preg_replace_callback( '/he-(.+?)--/', function( $matches ) use ( $phpQueryStore )
		{
			return isset( $phpQueryStore[ $matches[1] ] ) ? $phpQueryStore[ $matches[1] ] : '';
		}, $phpQuery->find( '#ipscontent' )->html() );
		
		/* Swap back certain tags that confuse phpQuery */
		$return = preg_replace( '/<(\/)?temp(html|head|body)(.*?)>/', '<$1$2$3>', $return );
		$return = str_replace( '<tempdoctype></tempdoctype>', '<!DOCTYPE html>', $return );
										
		/* Return */
		return $return;
	}
	
	protected static function encodeForPhpQuery()
	{


	}

	/**
	 * Run the template content via the compile and eval methods to see if there's any broken
	 * syntax
	 *
	 * @param   string  $content        The template content
	 * @param   string  $params         The template params
	 * @return  false                   False if the template is good
	 * @throws  \LogicException          If template has issues, $e->getMessage() has the details
	 */
	public static function checkTemplateSyntax( $content, $params='' )
	{
		ob_start();
		static::makeProcessFunction( $content, 'unique_function_so_it_doesnt_look_in_function_exists_' . uniqid(), $params );
		$return = ob_get_contents();
		ob_end_clean();

		if ( $return )
		{
			throw new \LogicException( $return );
		}

		return false;
	}
	
	/**
	 * Make process function
	 * Parses template into executable function and evals it.
	 *
	 * @param	string	$content		Content with variables and parse tags
	 * @param	string	$functionName	Desired function name
	 * @param	string	$params			Parameter list
	 * @param	bool	$isHTML			If TRUE, HTML will automatically be escaped
	 * @param	bool	$isCSS			If TRUE, the plugins will be checked for $canBeUsedInCss
	 * @return	string	Function name to eval
	 */
	public static function makeProcessFunction( $content, $functionName, $params='', $isHTML=TRUE, $isCSS=FALSE )
	{
		static::runProcessFunction( static::compileTemplate( $content, $functionName, $params, $isHTML, $isCSS ), $functionName );
	}
	
	/**
	 * Make process function
	 * Parses template into executable function and evals it.
	 *
	 * @param	string	$content		Compiled content with variables and parse tags
	 * @param	string	$functionName	Desired function name
	 */
	public static function runProcessFunction( $content, $functionName )
	{
		/* If it's already been built, we don't need to do it again */
		if( function_exists( 'IPS\Theme\\' . $functionName ) )
		{
			return;
		}

		/* Build Function */
		$function = 'namespace IPS\Theme;' . "\n" . $content;

		if ( \IPS\IN_DEV AND mb_strstr( $content, '{{{PRINT}}}' ) !== FALSE )
		{
			@header("Content-type: text/plain");
			print $function;
			exit();
		}
		
		/* Make it */
		if ( \IPS\DEBUG_TEMPLATES )
		{
			static::runDebugTemplate( $functionName, $function );
		}
		else
		{
			eval( $function );
		}
	}
	
	/**
	 * Run the template as a PHP file, not an eval to debug errors
	 *
	 * @param	string	$functionName	Function name
	 * @param	string	$content		Compiled content with variables and parse tags
	 */
	protected static function runDebugTemplate( $functionName, $content )
	{
		$temp = tempnam( \IPS\TEMP_DIRECTORY, $functionName );
		\file_put_contents( $temp, "<?php\n" . $content );
		include $temp;
		register_shutdown_function( function( $temp ) {
			unlink( $temp );
		}, $temp );
	}
	
	/**
	 * Expand shortcuts
	 *
	 * @param	string	$content		Content with shortcuts
	 * @return	string	Content with shortcuts expanded
	 */
	public static function expandShortcuts( $content )
	{
		/* Parse shortcuts */
		foreach ( array( 'request' => 'i', 'member' => 'loggedIn', 'settings' => 'i', 'output' => 'i' ) as $class => $function )
		{
			$content = preg_replace( '/(^|[^\$\\\])' . $class . "\.(\S+?)/", '$1\IPS\\' . ucfirst( $class ) . '::' . $function . '()->$2', $content );
		}
		
		foreach( array( 'theme' => '\IPS\\Theme::i()->settings', 'cookie' => '\IPS\\Request::i()->cookie' ) as $shortcut => $array )
		{
			$content = preg_replace( '/(^|[^\$\\\])' . $shortcut .'\.(.([a-zA-Z0-9_]+))/', '$1'. $array . '[\'$2\']', $content );
		}
		
		return $content;
	}
	
	/**
	 * Process template into executable code.
	 *
	 * @param	string	$content		Content with variables and parse tags
	 * @param	string	$functionName	Desired function name
	 * @param	string	$params			Parameter list
	 * @param	bool	$isHTML			If TRUE, HTML will automatically be escaped
	 * @param	bool	$isCSS			If TRUE, the plugins will be checked for $canBeUsedInCss
	 * @return	string	Function name to eval
	 * @throws	\InvalidArgumentException
	 */
	public static function compileTemplate( $content, $functionName, $params='', $isHTML=TRUE, $isCSS=FALSE, $app=null, $location=null, $group=null )
	{
		if ( \IPS\IN_DEV )
		{
			$content = str_replace( '{{{PRINT}}}', '', $content );
		}

		$calledClass = get_called_class();

		if( $functionName == 'theme_core_front_global_footer' or ( $functionName == 'footer' and $app == 'core' and $location == 'front' and $group == 'global' ) )
		{
			$content = $content . "\n<p id='elCopyright'>
	<span id='elCopyright_userLine'>{lang=\"copyright_line_value\"}</span>
	{{if !\$licenseData = \IPS\IPS::licenseKey() or !isset(\$licenseData['products']['copyright']) or !\$licenseData['products']['copyright']}}<a rel='nofollow' title='Community Software by Invision Power Services, Inc.' href='http://www.invisionpower.com/'>Community Software by Invision Power Services, Inc.</a>{{endif}}
</p>";
		}

		/* Parse out {{code}} tags */
		$content = preg_replace_callback( '/{{(.+?)}}/', function( $matches )
		{
			/* Parse shortcuts */
			$matches[1] = \IPS\Theme::expandShortcuts( $matches[1] );
	
			/* Make conditionals and loops valid PHP */
			if( $matches[1] === 'else' )
			{
				$matches[1] .= ':';
			}
			elseif( \substr( $matches[1], 0, 3 ) === 'end' )
			{
				$matches[1] .= ';';
			}
			elseif( in_array( \substr( $matches[1], 0, 4 ), array( 'for ', 'for(' ) ) )
			{
				$matches[1] = 'for (' . \substr( $matches[1], 3 ) . ' ):';
			}
			else
			{
				foreach ( array( 'if', 'elseif', 'foreach' ) as $tag )
				{
					if( \substr( $matches[1], 0, \strlen( $tag ) ) === $tag )
					{
						$matches[1] = $tag .' (' . \substr( $matches[1], \strlen( $tag ) ) . ' ):';
					}
				}
			}
	
			return "\nCONTENT;\n\n{$matches[1]}\n\$return .= <<<CONTENT\n";
		}, $content );

		/* Make it into a lovely function - templates created by plugins get try/catches so they can't break things */
		if ( $app == 'core' and $location == 'global' and $group == 'plugins' )
		{
			$function = <<<PHP
	function {$functionName}( {$params} ) {
		\$return = '';
		try
		{
			\$return .= <<<CONTENT\n
{$content}
CONTENT;\n
		}
		catch ( \Exception \$exception )
		{
			\IPS\Log::log( \$exception, "template_{$functionName}" );
		}
		return \$return;
}
PHP;
			
		}
		else
		{
			$function = <<<PHP
	function {$functionName}( {$params} ) {
		\$return = '';
		\$return .= <<<CONTENT\n
{$content}
CONTENT;\n
		return \$return;
}
PHP;
		}

		/* Parse {plugin="foo"} tags */
		$function = preg_replace_callback
		(
			'/\{([a-z]+?=([\'"]).+?\\2 ?+)}/',
			function( $matches ) use ( $functionName, $isCSS, $calledClass )
			{
				/* Work out the plugin and the values to pass */
				preg_match_all( '/(.+?)='.$matches[2].'([^' . $matches[2] . ']*)'.$matches[2].'\s?/', $matches[1], $submatches );

				$plugin = array_shift( $submatches[1] );
				$pluginClass = 'IPS\\Output\\Plugin\\' . ucfirst( $plugin );

				$value = array_shift( $submatches[2] );
				$options = array();

				foreach ( $submatches[1] as $k => $v )
				{
					$options[ $v ] = $submatches[2][ $k ];
				}

				/* Work out if this plugin belongs to an application, and if so, include it */
				if( !class_exists( $pluginClass ) )
				{
					foreach ( \IPS\Application::applications() as $app )
					{
						if ( file_exists( \IPS\ROOT_PATH . "/applications/{$app->directory}/extensions/core/OutputPlugins/" . ucfirst( $plugin ) . ".php" ) )
						{
							$pluginClass = 'IPS\\' . $app->directory . '\\extensions\\core\\OutputPlugins\\' . ucfirst( $plugin );
						}
					}
				}
				
				/* Still doesn't exist? */
				if( ! class_exists( $pluginClass ) )
				{
					return $matches[0];
				}
				
				/* can be used in CSS? */
				if ( $isCSS AND $pluginClass::$canBeUsedInCss !== TRUE )
				{
					throw new \InvalidArgumentException( 'invalid_plugin:' . $plugin );
				}

				$code = call_user_func( array( $pluginClass, 'runPlugin' ), $value, $options, $functionName, $calledClass );

				if( !is_array( $code ) )
				{
					$code = array( 'return' => $code );
				}
				if( !isset( $code['pre'] ) )
				{
					$code['pre'] = '';
				}
				
				if( !isset( $code['post'] ) )
				{
					$code['post'] = '';
				}

				$return = <<<PHP
\nCONTENT;\n
{$code['pre']}
PHP;
	if ( $code['return'] )
	{
		$return .= <<<PHP
\$return .= {$code['return']};
PHP;
	}
	$return .= <<<PHP
{$code['post']}
\$return .= <<<CONTENT\n
PHP;
			return $return;
			},
			$function
		);

		/* Escape output */
		preg_match_all( '#\$return\s{0,}(?:\.)?=\s{0,}<<<CONTENT\n(.+?)CONTENT;(\n|$)#si', $function, $matches, PREG_SET_ORDER );
		foreach( $matches as $id => $match )
		{
			$all     = $match[0];
			$content = $match[1];
			$rawFinds = array();
			$rawReplaces = array();

			if ( $isHTML === TRUE )
			{
				preg_match_all( '#\{\$([^\}]+?)\}#', $content, $varMatches, PREG_SET_ORDER );

				foreach ( $varMatches as $id => $var )
				{
					if ( \stristr( $var[1], '|raw' ) )
					{
						$rawFinds[]    = $var[0];
						$rawReplaces[] = str_ireplace( '|raw', '', $var[0] );
					}
					else
					{
						if ( \stristr( $var[1], '|doubleencode' ) )
						{
							$replace = "\nCONTENT;\n\$return .= htmlspecialchars( \$" . str_ireplace( '|doubleencode', '', $var[1] ) . ", ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', TRUE );\n\$return .= <<<CONTENT\n";
						}
						else
						{
							$replace = "\nCONTENT;\n\$return .= htmlspecialchars( \$" . $var[1] . ", ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE );\n\$return .= <<<CONTENT\n";
						}

						$all = str_replace( $var[0], $replace, $all );
					}
				}
				$all = str_replace( $rawFinds, $rawReplaces, $all );

				if ( $all != $match[0] )
				{
					$function = str_replace( $match[0], $all, $function );
				}
			}
			else if ( $isCSS )
			{
				/* Preserve backslashes */
				$all = str_replace( $content, str_replace( '\\', '\\\\', $content ), $all );

				if ( $all != $match[0] )
				{
					$function = str_replace( $match[0], $all, $function );
				}
			}
		}

		return $function;
	}
		
	/**
	 * Returns a location hash for selecting templates
	 * Used when building templates to core_theme_templates_built and also when selecting
	 * from that same table.
	 *
	 * @param	string	$app
	 * @param	string	$location
	 * @param	string	$group
	 * @return	string	Md5 Key
	 */
	public static function makeBuiltTemplateLookupHash( $app, $location, $group )
	{
		return md5( mb_strtolower( $app ) . ';' . mb_strtolower( $location ) . ';' . mb_strtolower( $group ) );
	}
	
	/**
	 * Clears theme files from \IPS\File and the store.
	 * @note    This does not remove rows from the theme database tables.
	 *
	 * @param	int		$bit    Bitwise options for files to remove
	 * @return	void
	 */
	public static function clearFiles( $bit )
	{
		if ( $bit & static::TEMPLATES )
		{
			static::deleteCompiledTemplate();
		}

		if ( $bit & static::CSS )
		{
			static::deleteCompiledCss();
		}

		if ( $bit & static::IMAGES )
		{
			static::deleteCompiledResources();
		}
		
		foreach( static::themes() as $id => $theme )
		{
			/* Remove files, but don't fail if we can't */
			try
			{
				\IPS\File::getClass('core_Theme')->deleteContainer( 'set_resources_' . $theme->id );
				\IPS\File::getClass('core_Theme')->deleteContainer( 'css_built_' . $theme->id );
			}
			catch( \Exception $e ){}
			
			/* Clear map */
			$theme->resource_map = array();
			$theme->css_map = array();
			$theme->save();
		}
	}
	
	/**
	 * Add resource
	 * Adds a resource to each theme set
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @note	$data['content'] should be the raw binary data, not base64_encoded data
	 * @param	array	$data	        Array of data (app, location, path, name, content, [plugin])
	 * @param   boolean $addToMaster    Add to master set 0
	 * @throws	\InvalidArgumentException
	 * @return	void
	 */
	public static function addResource( $data, $addToMaster=FALSE )
	{
		if ( empty( $data['app'] ) OR empty( $data['location'] ) OR empty( $data['path'] ) OR empty( $data['name'] ) )
		{
			throw new \InvalidArgumentException;
		}

		$name     = static::makeBuiltTemplateLookupHash( $data['app'], $data['location'], $data['path'] ) . '_' . $data['name'];
				
		if ( $addToMaster )
		{
			\IPS\Db::i()->insert( 'core_theme_resources', array(
                 'resource_set_id'   => 0,
                 'resource_app'      => $data['app'],
                 'resource_location' => $data['location'],
                 'resource_path'     => $data['path'],
                 'resource_name'     => $data['name'],
                 'resource_added'	  => time(),
                 'resource_filename' => NULL,
                 'resource_data'     => $data['content'],
                 'resource_plugin'	  => isset( $data['plugin'] ) ? $data['plugin'] : NULL
             ) );
		}

		foreach( static::themes() as $id => $theme )
		{
			$resource = NULL;
			try
			{
				$resource = \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_app=? and resource_location=? and resource_path=? and resource_name=?', $theme->id, $data['app'], $data['location'], $data['path'], $data['name'] ) )->first();
			}
			catch( \UnderflowException $ex ) { }

			if ( $resource !== NULL and isset( $resource['resource_user_edited'] ) )
			{
				if ( $resource['resource_user_edited'] )
				{
					continue;
				}
			}

			/* Clear out old rows */
			\IPS\Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=? and resource_app=? and resource_location=? and resource_path=? and resource_name=?', $theme->id, $data['app'], $data['location'], $data['path'], $data['name'] ) );

			$resourceMap = $theme->resource_map;
			
			if ( $data['content'] )
			{
				$fileName = (string) \IPS\File::create( 'core_Theme', $name, $data['content'], 'set_resources_' . $theme->id, FALSE, NULL, FALSE );
	
				\IPS\Db::i()->insert( 'core_theme_resources', array(
						'resource_set_id'   => $theme->id,
						'resource_app'      => $data['app'],
						'resource_location' => $data['location'],
						'resource_path'     => $data['path'],
						'resource_name'     => $data['name'],
						'resource_added'	 => time(),
						'resource_filename' => $fileName,
						'resource_data'     => $data['content'],
						'resource_plugin'	 => isset( $data['plugin'] ) ? $data['plugin'] : NULL
				) );
			}
			
			$key = static::makeBuiltTemplateLookupHash($data['app'], $data['location'], $data['path']) . '_' . $data['name'];
			
			$resourceMap[ $key ] = $fileName;
			
			/* Update theme map */
			$theme->resource_map = $resourceMap;
			$theme->save();
		}
	}
	
	/**
	 * Add CSS.
	 * As CSS files have inheritance, this will always go to theme set 0. A check is first made to
	 * ensure we're not overwriting an existing master CSS file.
	 *
	 * @param	array	$data	Data to insert (app, location, path, name, content, [added_to], [plugin])
	 * @throws	\InvalidArgumentException
	 * @throws	\OverflowException
	 * @return	int		Insert Id
	 */
	public static function addCss( $data )
	{
		if ( empty( $data['app'] ) OR empty( $data['location'] ) OR empty( $data['path'] ) OR empty( $data['name'] ) )
		{
			throw new \InvalidArgumentException;
		}
		
		/* Check for existing */
		try
		{
			$check = \IPS\Db::i()->select( 'css_id, css_plugin', 'core_theme_css', array(
				'css_app=? AND css_location=? AND css_path=? AND css_name=LOWER(?) AND css_set_id=?',
				mb_strtolower( $data['app'] ),
				mb_strtolower( $data['location'] ),
				mb_strtolower( $data['path'] ),
				mb_strtolower( $data['name'] ),
				isset( $data['set_id'] ) ? $data['set_id'] : 0
			) )->first();
			
			if ( isset( $data['plugin'] ) and $data['plugin'] == $check['css_plugin'] )
			{
				throw new \OverflowException;
			}
			else if ( empty( $data['plugin'] ) )
			{
				throw new \OverflowException;
			}
		}
		catch( \UnderflowException $e )
		{
			/* That's ok, it doesn't exist */
		}
		
		/* Insert */
		$saveSetId = isset( $data['set_id'] ) ? $data['set_id'] : 0;
		$insertId = \IPS\Db::i()->insert( 'core_theme_css', array(
				'css_set_id'	 => $saveSetId,
				'css_app'		 => mb_strtolower( $data['app'] ),
				'css_location'   => mb_strtolower( $data['location'] ),
				'css_path'		 => mb_strtolower( $data['path'] ),
				'css_name'       => mb_strtolower( $data['name'] ),
				'css_content'    => $data['content'],
				'css_added_to'   => ( isset( $data['added_to'] ) ) ? intval( $data['added_to'] ) : 0,
				'css_updated' 	 => time(),
				'css_version'    => \IPS\Application::load('core')->long_version,
				'css_plugin'	 => isset( $data['plugin'] ) ? $data['plugin'] : NULL,
				'css_unique_key' => md5( $saveSetId . ';' . mb_strtolower( $data['app'] ) . ';' . mb_strtolower( $data['location'] ) . ';' . mb_strtolower( $data['path'] ) . ';' . mb_strtolower( $data['name'] ) )
		) );
		
		return $insertId;
	}
	
	/**
	 * Add a template
	 * As templates have inheritance, this will always go to theme set 0. A check is first made to
	 * ensure we're not overwriting an existing master template bit.
	 *
	 * @param	array	$data	Data to insert (app, location, group, name, variables, content, [added_to])
	 * @throws	\InvalidArgumentException
	 * @throws	\OverflowException
	 * @return	int		Insert Id
	 */
	public static function addTemplate( $data )
	{
		if ( empty( $data['app'] ) OR empty( $data['location'] ) OR empty( $data['group'] ) OR empty( $data['name'] ) )
		{
			throw new \InvalidArgumentException;
		}
	
		/* Check for existing */
		$check = \IPS\Db::i()->select( 'template_id', 'core_theme_templates', array(
			'template_app=? AND template_location=? AND template_group=? AND template_name=LOWER(?) AND template_set_id=?',
			mb_strtolower( $data['app'] ),
			mb_strtolower( $data['location'] ),
			mb_strtolower( $data['group'] ),
			mb_strtolower( $data['name'] ),
			0
		) );
		if ( count( $check ) > 0 )
		{
			throw new \OverflowException;
		}
	
		/* Insert */
		$insertId = \IPS\Db::i()->insert( 'core_theme_templates', array(
				'template_set_id'	  => 0,
				'template_app'		  => mb_strtolower( $data['app'] ),
				'template_location'   => mb_strtolower( $data['location'] ),
				'template_group'	  => mb_strtolower( $data['group'] ),
				'template_name'       => $data['name'],
				'template_data'       => $data['variables'],
				'template_content'    => $data['content'],
				'template_added_to'   => ( isset($data['added_to']) ) ? intval( $data['added_to'] ) : 0,
				'template_updated'	  => time(),
				'template_user_added' => ( isset($data['_default_template']) ) ? 0 : 1,
				'template_version'    => \IPS\Application::load('core')->long_version,
				'template_plugin'	  => isset( $data['plugin'] ) ? $data['plugin'] : NULL,
				'template_unique_key' => md5( '0;' . mb_strtolower( $data['app'] ) . ';' . mb_strtolower( $data['location'] ) . ';' . mb_strtolower( $data['group'] ) . ';' . mb_strtolower( $data['name'] ) )
		), TRUE );
	
		return $insertId;
	}
	
	/**
	 * Remove templates completely from the system.
	 * Used by hooks/application manager, etc.
	 *
	 * @param	string		$app		Application Key
	 * @param	string|null	$location	Location
	 * @param	string|null $group		Group
	 * @param	int|null	$plugin		Plugin ID
	 * @param	bool		$doAll		Delete all - by default only the master set is cleared
	 * @return	void
	 */
	public static function removeTemplates( $app, $location=NULL, $group=NULL, $plugin=NULL, $doAll=FALSE )
	{
		static::deleteCompiledTemplate( $app, $location, $group );

		$where = array( array( 'template_app=?', $app ) );
		
		if ( $location !== NULL )
		{
			$where[] = array( 'template_location=?', $location );
		}
		
		if ( $group !== NULL )
		{
			$where[] = array( 'template_group=?', $group );
		}
		
		if ( $plugin !== NULL )
		{
			$where[] = array( 'template_plugin=?', $plugin );
		}
		else
		{
			$where[] = array( 'template_plugin IS NULL' );
		}
		
		/* Coming from build script */
		if ( !$doAll )
		{
			if ( $plugin )
			{
				$where[] = array( 'template_set_id=0' );
			}
			else
			{
				$where[] = array( '(template_set_id=0 and template_user_added=0)' );
			}
		}
		
		\IPS\Db::i()->delete( 'core_theme_templates', $where );
	}
	
	/**
	 * Remove CSS completely from the system.
	 * Used by hooks/application manager, etc.
	 *
	 * @param	string		$app		Application Key
	 * @param	string|null	$location	Location
	 * @param	string|null $path		Group
	 * @param	int|null	$plugin		Plugin ID
	 * @param	bool		$doAll		Delete all - by default only the master set is cleared
	 * @return	void
	 */
	public static function removeCss( $app, $location=NULL, $path=NULL, $plugin=NULL, $doAll=FALSE )
	{
		static::deleteCompiledCss( $app, $location, $path );

		$where = array( array( 'css_app=?', $app ) );
		
		if ( $location !== NULL )
		{
			$where[] = array( 'css_location=?', $location );
		}
		
		if ( $path !== NULL )
		{
			$where[] = array( 'css_path=?', $path );
		}
		
		if ( $plugin !== NULL )
		{
			$where[] = array( 'css_plugin=?', $plugin );
		}
		else
		{
			$where[] = array( 'css_plugin IS NULL' );
		}
		
		/* Coming from build script */
		if ( !$doAll )
		{
			$where[] = array( '(css_set_id=0 and css_added_to=0)' );
		}
		
		\IPS\Db::i()->delete( 'core_theme_css', $where );
	}
	
	/**
	 * Remove resources completely from the system.
	 * Used by hooks/application manager, etc.
	 *
	 * @param	string		$app		Application Key
	 * @param	string|null	$location	Location
	 * @param	string|null $path		Path
	 * @param	int|null	$plugin		Plugin ID
	 * @param	bool		$doAll		Delete all - by default only the master set is cleared
	 * @return void
	 */
	public static function removeResources( $app, $location=NULL, $path=NULL, $plugin=NULL, $doAll=FALSE )
	{
		static::deleteCompiledResources( $app, $location, $path );

		$where = array( array( 'resource_app=?', $app ) );
		
		if ( $location !== NULL )
		{
			$where[] = array( 'resource_location=?', $location );
		}
		
		if ( $path !== NULL )
		{
			$where[] = array( 'resource_path=?', $path );
		}
		
		if ( $plugin !== NULL )
		{
			$where[] = array( 'resource_plugin=?', $plugin );
		}
		else
		{
			$where[] = array( 'resource_plugin IS NULL' );
		}
		
		/* Coming from build script */
		if ( !$doAll )
		{
			$where[] = array( '(resource_set_id=0 OR resource_user_edited=0)' );
		}

		\IPS\Db::i()->delete( 'core_theme_resources', $where );

		foreach( static::themes() as $id => $set )
		{
			$set->buildResourceMap( $app );
		}
	}

	/**
	 * Because css still has {resource} tags when built, and the building is done via the ACP,
	 * Tags without a set "location" parameter are set to "admin" incorrectly.
	 *
	 * @param   string	$css		CSS Text
	 * @param   string  $location   CSS Location
	 * @return  string	Fixed CSS
	 */
	public static function fixResourceTags( $css, $location )
	{
		preg_match_all( '#\{resource=([\'"])(\S+?)\\1([^\}]+?)?\}#i', $css, $items, PREG_SET_ORDER );
	
		foreach( $items as $id => $attr )
		{
			/* Has manually added params */
			if ( isset( $attr[3] ) )
			{
				if ( ! \strstr( $attr[3], 'location=' ) )
				{
					$new = \str_replace( $attr[3], $attr[3] . ' location="' . $location . '"', $attr[0] );
		
					$css = \str_replace( $attr[0], $new, $css );
				}
			}
			else
			{
				$new = \str_replace( '}',  ' location="' . $location . '"}', $attr[0] );
				$css = \str_replace( $attr[0], $new, $css );
			}
		}
	
		return $css;
	}
	
	/**
	 * Inserts a built record
	 *
	 * @param	array	$css	css_* table data
	 * @return	object	IPS\File
	 */
	protected static function writeCss( $css )
	{
		$css['css_path']    = ( empty( $css['css_path'] ) ) ? '.' : $css['css_path'];
	
		$functionName = "css_" . $css['css_app'] . '_' . $css['css_location'] . '_' . str_replace( array( '-', '/', '.' ), '_', $css['css_path'] . '_' . $css['css_name'] );

		if ( !function_exists( $functionName ) )
		{
			static::makeProcessFunction( static::fixResourceTags( $css['css_content'], $css['css_location'] ), $functionName, '', FALSE, TRUE );
		}
		
		$content = static::minifyCss( call_user_func( 'IPS\\Theme\\'. $functionName ) );
		$name    = static::makeBuiltTemplateLookupHash( $css['css_app'], $css['css_location'], $css['css_path'] . '/' .$css['css_name'] ) . '_' . $css['css_name'];
		
		/* Replace any <fileStore.xxx> tags in the CSS */
		\IPS\Output::i()->parseFileObjectUrls( $content );
		
		return \IPS\File::create( 'core_Theme', $name, $content, 'css_built_' . $css['css_set_id'] );
	}
	
	/**
	 * Minifies CSS
	 *
	 * @param   string  $content	Content to minify
	 * @return	string  $content	Minified
	 */
	public static function minifyCss( $content )
	{
		/* Comments */
		$content = preg_replace( '#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $content );
	
		/* Multiple spaces, tabs and newlines */
		$content = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $content );
		$content = preg_replace( '!\s+!', ' ', $content );

		/* Some more space removal */
		$content = str_replace( ' {', '{', $content );
		$content = str_replace( '{ ', '{', $content );
		$content = str_replace( ' }', '}', $content );
		$content = str_replace( '} ', '}', $content );
		$content = str_replace( '; ', ';', $content );
		$content = str_replace( ': ', ':', $content );
	
		return $content;
	}
}

