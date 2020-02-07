<?php
/**
 * @brief		IN_DEV Skin Set
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		16 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme\Dev;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IN_DEV Skin set
 */
class _Theme extends \IPS\Theme
{
	/**
	 * @brief	Template Classes
	 */
	protected $templates;
	
	/**
	 * @brief	Stored plugins
	 */
	protected static $plugins = array();

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
		$returnType = ( $returnType === null )  ? self::RETURN_ALL   : $returnType;
		$app        = ( is_string( $app )      AND $app != ''      ) ? array( $app )      : $app;
		$location   = ( is_string( $location ) AND $location != '' ) ? array( $location ) : $location;
		$group      = ( is_string( $group )    AND $group != ''    ) ? array( $group )    : $group;
		$where      = array();
		$templates  = array();
		
		if ( ! ( $returnType & static::RETURN_NATIVE ) )
		{
			return parent::getRawTemplates( $app, $location, $group, $returnType, $returnThisSetOnly );
		}
		
		$fixedLocations = array( 'admin', 'front', 'global' );
		$results	    = array();
		
		foreach( \IPS\Application::applications() as $appDir => $application )
		{
			if ( $app === NULL or ( in_array( $appDir, $app ) ) )
			{
				foreach( $fixedLocations as $_location )
				{
					if ( $location === NULL or ( in_array( $_location, $location ) ) ) # location?
					{
						foreach( new \DirectoryIterator( static::_getHtmlPath( $appDir, $_location ) ) as $file )
						{
							if ( $file->isDir() AND mb_substr( $file->getFilename(), 0, 1 ) !== '.' )
							{
								if ( $group === NULL or ( in_array( $file->getFilename(), $group ) ) )
								{
									foreach( new \DirectoryIterator( static::_getHtmlPath( $appDir, $_location, $file->getFilename() ) ) as $template )
									{
										if ( ! $template->isDir() AND mb_substr( $template->getFilename(), -6 ) === '.phtml' )
										{
											$results[] = str_replace( ".phtml", "", $template->getFilename() );
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		return array_unique( $results );
	}

	/**
	 * Get a template
	 *
	 * @param	string	$group				Template Group
	 * @param	string	$app				Application key (NULL for current application)
	 * @param	string	$location		    Template Location (NULL for current template location)
	 * @return	\IPS\Theme\Template
	 */
	public function getTemplate( $group, $app=NULL, $location=NULL )
	{
		/* Do we have an application? */
		if( $app === NULL )
		{
			if ( !\IPS\Dispatcher::hasInstance() )
			{
				throw new \RuntimeException('NO_APP');
			}
			$app = \IPS\Dispatcher::i()->application->directory;
		}
		
		/* How about a template location? */
		if( $location === NULL )
		{
			if ( !\IPS\Dispatcher::hasInstance() )
			{
				throw new \RuntimeException('NO_LOCATION');
			}
			$location = \IPS\Dispatcher::i()->controllerLocation;
		}
		
		/* Get template */
		if ( !isset( $this->templates[ $app ][ $location ][ $group ] ) )
		{
			$class = 'Template';
			if ( isset( \IPS\IPS::$hooks[ "\IPS\Theme\class_{$app}_{$location}_{$group}" ] ) )
			{
				foreach ( \IPS\IPS::$hooks[ "\IPS\Theme\class_{$app}_{$location}_{$group}" ] as $id => $data )
				{
					if( file_exists( \IPS\ROOT_PATH . '/' . $data['file'] ) )
					{
						$contents = "namespace " . rtrim( static::_getTemplateNamespace(), '\\' ) . ";\n\n" . str_replace( '_HOOK_CLASS_', $class, file_get_contents( \IPS\ROOT_PATH . '/' . $data['file'] ) );
						eval( $contents );
						$class = $data['class'];
					}
				}
			}
			$class = static::_getTemplateNamespace() . $class;
			
			$this->templates[ $app ][ $location ][ $group ] = new $class( $app, $location, $group );
		}
		return $this->templates[ $app ][ $location ][ $group ];
	}

	/**
	 * Get CSS
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

		if ( $location === 'interface' )
		{
			$path = \IPS\ROOT_PATH . "/applications/{$app}/interface/{$file}";
		}
		else
		{
			$path = static::_getCssPath($app, $location, $file);
		}

		if ( isset( self::$buildGrouping['css'][ $app ][ $location ] ) AND is_array( self::$buildGrouping['css'][ $app ][ $location ] ) )
		{
			foreach( self::$buildGrouping['css'][ $app ][ $location ] as $buildPath )
			{
				if ( mb_substr( $file, 0, -4 ) == $buildPath )
				{
					$path = static::_getCssPath($app, $location, $buildPath);
					$file = $buildPath;
				}
			}
		}

		$return = array();

		if ( is_dir( $path ) )
		{
			$bits     = explode( '/', $path );
			$fileName = array_pop( $bits ) . '.css';

			/* Load css/location/folderName/folderName.css first */
			if ( is_file( $path . '/' . $fileName ) )
			{
				$return[] = str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . ( str_replace( \IPS\ROOT_PATH . '/', '', static::_getCssPath( $app, $location, $file ) ) ) . $fileName;
			}

			$csses = array();
			foreach ( new \DirectoryIterator( $path ) as $f )
			{
				if ( !$f->isDot() and mb_substr( $f, -4 ) === '.css' and $f->getFileName() != $fileName )
				{
					$csses[] = ( str_replace( \IPS\ROOT_PATH . '/', '', static::_getCssPath( $app, $location, $file ) ) ) . $f;
				}
			}

			sort( $csses );

			if ( count( $csses ) )
			{
				if( \IPS\DEV_DEBUG_CSS )
				{
					foreach( $csses as $cssFile )
					{
						$return[] = str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . $cssFile;
					}
				}
				else
				{
					$return[] = str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . implode( ',', $csses );
				}
			}

			if ( $file === 'custom' and $app === 'core' )
			{
				foreach ( \IPS\Plugin::plugins() as $plugin )
				{
					if ( $plugin->enabled )
					{
						foreach ( new \GlobIterator( \IPS\ROOT_PATH . '/plugins/' . $plugin->location . '/dev/css/*' ) as $file )
						{
							$return[] = str_replace( '\\', '/', str_replace( \IPS\ROOT_PATH, rtrim( \IPS\Settings::i()->base_url, '/' ), $file ) );
						}
					}
				}
			}
		}
		elseif ( file_exists( $path ) )
		{
			$return[] = str_replace( \IPS\ROOT_PATH . '/', '', str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ) . "applications/core/interface/css/css.php?css=" . $path );
		}

		return $return;
	}

	
	/**
	 * Get JS
	 *
	 * @param	string		$file		Filename
	 * @param	string|null	$app		Application
	 * @param	string|null	$location	Location (e.g. 'admin', 'front')
	 * @return	array		URL to JS files
	 */
	public function js( $file, $app=NULL, $location=NULL )
	{
		$app      = $app      ?: \IPS\Request::i()->app;
		$location = $location ?: \IPS\Dispatcher::i()->controllerLocation;
		
		$return = array();
		if ( $app === 'core' and $location === 'global' and $file === 'plugins' )
		{
			foreach ( new \GlobIterator( \IPS\ROOT_PATH . '/plugins/*/dev/js/*' ) as $file )
			{
				$url = str_replace( \IPS\ROOT_PATH, rtrim( \IPS\Settings::i()->base_url, '/' ), $file );
				$return[] = str_replace( '\\', '/', $url );
			}
		}
		else
		{
			if ( $location === 'interface' )
			{
				$path = \IPS\ROOT_PATH . "/applications/{$app}/interface/{$file}";
			}
			else
			{
				$path = \IPS\ROOT_PATH . "/applications/{$app}/dev/js/{$location}/{$file}";
			}
					
			if ( is_dir( $path ) )
			{
				$bits     = explode( '/', $path );
				$fileName = 'ips.' . array_pop( $bits ) . '.js';
				
				if ( is_file( $path .'/' . $fileName ) )
				{
					$return[] = \IPS\Settings::i()->base_url . "/applications/{$app}/dev/js/{$location}/{$file}/{$fileName}";
				}

				foreach ( new \DirectoryIterator( $path ) as $f )
				{
					if ( !$f->isDot() and mb_substr( $f, -3 ) === '.js' and $f->getFileName() != $fileName )
					{
						$return[] = \IPS\Settings::i()->base_url . "/applications/{$app}/dev/js/{$location}/{$file}/{$f}";
					}
				}
			}
			else
			{			
				$return[] = str_replace( \IPS\ROOT_PATH, \IPS\Settings::i()->base_url, $path );
			}
		}
		
		return $return;
	}
	
	/**
	 * Get Theme Resource (resource, font, theme-specific JS, etc)
	 *
	 * @param	string		$path		Path to resource
	 * @param	string|null	$app		Application key
	 * @param	string|null	$location	Location
	 * @return	string		URL to resource
	 */
	public function resource( $path, $app=NULL, $location=NULL, $noProtocol=FALSE )
	{
		$baseUrl = \IPS\Settings::i()->base_url;
		$app = $app ?: \IPS\Dispatcher::i()->application->directory;
		$location = $location ?: \IPS\Dispatcher::i()->controllerLocation;
		
		if ( $location === 'interface' )
		{
			return \IPS\Settings::i()->base_url . "/applications/{$app}/interface/{$path}";
		}

		$url = $baseUrl . str_replace( \IPS\ROOT_PATH . '/', '', static::_getResourcePath( $app, $location, $path ) );
		if( $noProtocol )
		{
			$url = str_replace( array( 'http://', 'https://' ), '//', $url );
		}
		return $url;
	}
	
	/**
	 * (re)import HTML templates into the template DB
	 * 
	 * @param	string       $app	        Application Key
	 * @param	int	         $id	        Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * 
	 * @return	void
	 */
	public static function importDevHtml( $app, $id )
	{
		/* Clear out existing template bits */
		\IPS\Db::i()->delete( 'core_theme_templates', array( 'template_app=? AND ( template_set_id=? OR ( template_set_id=0 AND template_added_to=? ) )', $app, $id, $id ) );

		$themeLocations = \IPS\Application::load( $app )->themeLocations;

		/* Get existing template bits to see if we need to import */
		if ( $id > 0 )
		{
			$currentTemplates = \IPS\Theme::load( $id )->getRawTemplates( $app );
		}
		
		$path = static::_getHtmlPath( $app );

		if ( is_dir( $path ) )
		{
			foreach( new \DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDot() || mb_substr( $location->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}
				
				if ( $location->isDir() )
				{
					if ( ! in_array( $location->getFilename(), $themeLocations ) )
					{
						continue;
					}

					foreach( new \DirectoryIterator( $path . $location->getFilename() ) as $group )
					{
						if ( $group->isDot() || mb_substr( $group->getFilename(), 0, 1 ) === '.' )
						{
							continue;
						}
						
						if ( $group->isDir() )
						{
							foreach( new \DirectoryIterator( $path . $location->getFilename() . '/' . $group->getFilename() ) as $file )
							{
								if ( $file->isDot() || mb_substr( $file->getFilename(), -6 ) !== '.phtml')
								{
									continue;
								}
				
								/* Get the content */
								$html   = file_get_contents( $path . $location->getFilename() . '/' . $group->getFilename() . '/' . $file->getFilename() );
								$params = array();
								
								/* Parse the header tag */
								preg_match( '/^<ips:template parameters="(.+?)?"(.+?)?\/>(\r\n?|\n)/', $html, $params );
								
								/* Strip it */
								$html = ( isset($params[0]) ) ? str_replace( $params[0], '', $html ) : $html;
								
								$version = \IPS\Application::load( $app )->long_version;
								$save = array(
									'set_id'	  => $id,
									'added_to'    => 0,
									'user_added'  => 0,
									'user_edited' => 0
								);
								
								/* If we're syncing designer mode, check for actual changes */
								$name = preg_replace( '/[^a-zA-Z0-9_]/', '', str_replace( '.phtml', '', $file->getFilename() ) );

								if ( $id > 0 )
								{
									if ( isset( $currentTemplates[ $app ][ $location->getFilename() ][ $group->getFilename() ][ $name ] ) )
									{
										if( \IPS\Login::compareHashes( md5( trim( $html ) ), md5( trim( $currentTemplates[ $app ][ $location->getFilename() ][ $group->getFilename() ][ $name ]['template_content'] ) ) ) )
										{
											/* No change  */
											continue;
										}
										else
										{
											/* It has changed */
											$save['user_edited'] = $version;
										}
									}
									else
									{
										/* New template bit */
										$save['added_to']   = $id;
										$save['set_id']	    = 0;
										$save['user_added'] = 1;
									}
								}
								
								$uniqueKey = md5( $id . ';' . $app . ';' . $location->getFilename() . ';' . $group->getFilename() . ';' . ( str_replace( '.phtml', '', $file->getFilename() ) ) );
								
								\IPS\Db::i()->delete( 'core_theme_templates', array( 'template_unique_key=?', $uniqueKey ) );
								
								\IPS\Db::i()->insert( 'core_theme_templates', array( 'template_set_id'      => $save['set_id'],
																					 'template_app'		    => $app,
																					 'template_added_to'    => $save['added_to'],
																					 'template_location'    => $location->getFilename(),
																					 'template_group'       => $group->getFilename(),
																					 'template_name'	    => $name,
																					 'template_data'	    => ( isset( $params[1] ) ) ? $params[1] : '',
																					 'template_content'     => $html,
																					 'template_updated'     => time(),
																					 'template_user_added'  => $save['user_added'],
																					 'template_user_edited' => $save['user_edited'],
																					 'template_unique_key'  => $uniqueKey,
																					 'template_version'	    => ( isset( $master ) AND isset( $master[ $key ] ) ) ? $master[ $key ]['template_version'] : NULL,
																					 'template_removable'   => $id ) );
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * (re)import CSS into the CSS DB
	 *
	 * @param	string $app	Application Key
	 * @param	int	   $id	Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * 
	 * @return	void
	 */
	public static function importDevCss( $app, $id )
	{
		/* Clear out existing template bits */
		\IPS\Db::i()->delete( 'core_theme_css', array( 'css_app=? AND ( css_set_id=? OR ( css_added_to=0 AND css_set_id=? ) ) ', $app, $id, $id ) );
		
		$master = array();
		
		/* Get existing template bits to see if we need to import */
		$currentCss = NULL;
		
		if ( $id > 0 )
		{
			$currentCss = \IPS\Theme::load( $id )->getRawCss( $app );
		}
		
		$path = static::_getCssPath($app);
	
		if ( is_dir( $path ) )
		{
			foreach( new \DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDot() OR mb_substr( $location->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}
	
				if ( $location->isDir() )
				{
					static::_importDevCss( $app, $id, $currentCss, $location->getFilename() );
				}
			}
		}
	}
	
	/**
	 * (re)import CSS into the CSS DB (Iterable)
	 *
	 * @param	string	$app		Application Key
	 * @param	int		$id			Theme set ID
	 * @param	array	$master		Master CSS bits
	 * @param	string	$location	Location Folder Name
	 * @param	string	$path		Path
	 * @return	void
	 */
	protected static function _importDevCss( $app, $id, $currentCss, $location, $path='/' )
	{
		$root = static::_getCssPath( $app, $location );
		
		foreach( new \DirectoryIterator( $root . $path ) as $file )
		{
			if ( $file->isDot() OR mb_substr( $file->getFilename(), 0, 1 ) === '.' OR $file == 'index.html' )
			{
				continue;
			}
	
			if ( $file->isDir() )
			{
				static::_importDevCss( $app, $id, $currentCss, $location, $path . $file->getFilename() . '/' );
			}
			else
			{
				if ( mb_substr( $file->getFilename(), -4 ) !== '.css' )
				{
					continue;
				}

				/* Get the content */
				$css = file_get_contents( $root . $path . $file->getFilename() );
					
				/* Parse the header tag */
				preg_match( '#^/\*<ips:css([^>]+?)>\*/\n#', $css, $params );

				/* Strip it */
				if ( count( $params ) AND ! empty( $params[0] ) )
				{
					$css = str_replace( $params[0], '', $css );
				}

				$cssModule = '';
				$cssApp    = '';
				$cssPos    = 0;
				$cssHidden = 0;
				
				/* Tidy params */
				if ( count( $params ) AND ! empty( $params[1] ) )
				{
					preg_match_all( '#([\d\w]+?)=\"([^"]+?)"#i', $params[1], $items, PREG_SET_ORDER );
						
					foreach( $items as $id => $attr )
					{
						switch( trim( $attr[1] ) )
						{
							case 'module':
								$cssModule = trim( $attr[2] );
								break;
							case 'app':
								$cssApp = trim( $attr[2] );
								break;
							case 'position':
								$cssPos = intval( $attr[2] );
								break;
							case 'hidden':
								$cssHidden = intval( $attr[2] );
								break;
						}
					}
				}
			
				$trimmedPath = trim( $path, '/' );
				$finalPath   = ( ( ! empty( $trimmedPath ) ) ? $trimmedPath : '.' );
				$version     = \IPS\Application::load( $app )->long_version;
				$save        = array(
					'set_id'	  => $id,
					'added_to'    => 0,
					'user_edited' => 0
				);
							
				/* If we're syncing designer mode, check for actual changes */
				if ( $id > 0 )
				{
					$css = str_replace( '/* No Content */', '', $css );
					if ( isset( $currentCss[ $app ][ $location ][ $finalPath ][ $file->getFilename() ] ) )
					{
						if( \IPS\Login::compareHashes( md5( trim( $css ) ), md5( trim( $currentCss[ $app ][ $location ][ $finalPath ][ $file->getFilename() ]['css_content'] ) ) ) )
						{
							/* No change  */
							continue;
						}
						else
						{
							/* It has changed */
							$save['user_edited'] = $version;
							$save['added_to']   = $id;
							$save['set_id']	    = $id;
						}
					}
					else
					{
						/* New template bit */
						$save['added_to']   = $id;
						$save['set_id']	    = $id;
					}
				}
				
				$uniqueKey = md5( $id . ';' . $app . ';' . $location . ';' . $finalPath . ';' . $file->getFilename() );
				
				\IPS\Db::i()->delete( 'core_theme_css', array( 'css_unique_key=?', $uniqueKey ) );
				
				\IPS\Db::i()->insert( 'core_theme_css', array( 'css_set_id'    	 => $save['set_id'],
															   'css_app'		 => $app,
															   'css_added_to'	 => $save['added_to'],
															   'css_location'  	 => $location,
															   'css_path'		 => $finalPath,
															   'css_name'	     => $file->getFilename(),
															   'css_attributes'  => '',
															   'css_content'	 => $css,
															   'css_modules'	 => $cssModule,
															   'css_position'	 => $cssPos,
															   'css_user_edited' => $save['user_edited'],
															   'css_updated'   	 => time(),
															   'css_unique_key'  => $uniqueKey,
															   'css_hidden'		 => $cssHidden ) );
			}
		}
	}
	
	/**
	 * Build Resourcess ready for non IN_DEV use
	 * @param	string|array	$app	App (e.g. core, forum)
	 * @param	int	   			$id	Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * 
	 * @return	void
	 */
	public static function importDevResources( $app=NULL, $id )
	{
		foreach( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/' ) as $dir )
		{
			if ( $dir->isDot() || mb_substr( $dir->getFilename(), 0, 1 ) === '.' || $dir == 'index.html')
			{
				continue;
			}

			if ( $app === null OR $app == $dir->getFilename() )
			{
				\IPS\Theme::deleteCompiledResources( $dir->getFilename(), null, null, null, ( $id === 0 ? 1 : $id ) );
				
				if ( $id )
				{
					foreach( \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=? and resource_plugin IS NOT NULL', $id ) ) as $resource )
					{
						static::$plugins[ $resource['resource_name'] ] = $resource['resource_plugin'];
					}
				}
						
				\IPS\Db::i()->delete( 'core_theme_resources', array( 'resource_app=? AND resource_set_id=?', $dir->getFilename(), $id ) );
				
				$path = static::_getResourcePath( $dir->getFilename() );
					
				if ( is_dir( $path ) )
				{
					foreach( new \DirectoryIterator( $path ) as $location )
					{
						if ( $location->isDot() || mb_substr( $location->getFilename(), 0, 1 ) === '.' )
						{
							continue;
						}
							
						if ( $location->isDir() )
						{
							static::_importDevResources( $dir->getFilename(), $id, $location->getFilename() );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Build Resources ready for non IN_DEV use (Iterable)
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	string	$app		Application Key
	 * @param	int		$id			Theme Set Id
	 * @param	string	$location	Location Folder Name
	 * @param	string	$path		Path
	 * 
	 * @return	void
	 */
	public static function _importDevResources( $app, $id, $location, $path='/' )
	{
		$root   = static::_getResourcePath($app, $location);
		$master = array();
		$plugins = array();

		if ( $id )
		{
			foreach( \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=0 and resource_app=? and resource_location=? and resource_path=?', $app, $location, $path ) ) as $resource )
			{
				$master[ $resource['resource_name'] ] = md5( $resource['resource_data'] );
			}
		}

		foreach( new \DirectoryIterator( $root . $path ) as $file )
		{
			if ( $file->isDot() || mb_substr( $file->getFilename(), 0, 1 ) === '.' || $file == 'index.html' )
			{
				continue;
			}
	
			if ( $file->isDir() )
			{
				static::_importDevResources( $app, $id, $location, $path . $file->getFilename() . '/' );
			}
			else
			{
				/* files larger than 1.5mb don't base64_encode() as they may not get stored in the resources_data column */
				if ( filesize( $root . $path . $file->getFilename() ) > ( 1.5 * 1024 * 1024 ) )
				{
					\IPS\Log::log( $root . $path . $file->getFilename() . " too large to import", 'designers_mode_import' );
					continue;
				}
				
				$content = file_get_contents( $root . $path . $file->getFilename() );
				
				if ( ! base64_encode( $content ) )
				{
					\IPS\Log::log( $root . $path . $file->getFilename() . " could not be saved correctly", 'designers_mode_import' );
					continue;
				}
				
				$custom   = 0;
				$name     = self::makeBuiltTemplateLookupHash($app, $location, $path) . '_' . $file->getFilename();
				$fileName = (string) \IPS\File::create( 'core_Theme', $name, $content, 'set_resources_' . ( $id == 0 ? 1 : $id ) );
				
				if ( ! isset( $master[ $file->getFilename() ] ) or !\IPS\Login::compareHashes( md5( $content ), $master[ $file->getFilename() ] ) )
				{
					$custom = 1;
				}

				\IPS\Db::i()->insert( 'core_theme_resources', array(
						'resource_set_id'      => ( $id == 0 ? 1 : $id ),
						'resource_app'         => $app,
						'resource_location'    => $location,
						'resource_path'        => $path,
						'resource_name'        => $file->getFilename(),
						'resource_added'	   => time(),
						'resource_data'        => $content,
						'resource_filename'    => $fileName,
						'resource_plugin'	   => ( ( $app == 'core' and $location == 'global' and $path == '/plugins/' ) and isset( static::$plugins[ $file->getFilename() ] ) ) ? static::$plugins[ $file->getFilename() ] : NULL,
						'resource_user_edited' => $custom
				) );

				/* Store in master table */
				if ( $id == 0 )
				{
					\IPS\Db::i()->insert( 'core_theme_resources', array(
	                     'resource_set_id'   => 0,
	                     'resource_app'      => $app,
	                     'resource_location' => $location,
	                     'resource_path'     => $path,
	                     'resource_name'     => $file->getFilename(),
	                     'resource_added'	  => time(),
	                     'resource_data'     => $content,
	                     'resource_filename' => ''
	                 ) );
				}
			}
		}
	}
	
	
	/**
	 * Writes the /application/{app}/dev/{container}/ directory
	 *
	 * @param  string	$app		 Application Directory
	 * @param  string   $container	 Container directory (e.g. html/css/resources)
	 * @return string	Path created
	 * @throws	\RuntimeException
	 */
	protected static function _writeThemeContainerDirectory( $app, $container )
	{
		$dirToWrite = \IPS\ROOT_PATH . "/applications/" . $app . '/dev/' . $container;
	
		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				throw new \RuntimeException('core_theme_dev_cannot_make_dir,' . $dirToWrite);
			}
			else
			{
				@chmod( $dirToWrite, IPS_FOLDER_PERMISSION );
			}
		}
	
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			throw new \RuntimeException('core_theme_dev_not_writeable,' . $dirToWrite);
		}
		
		/* Make sure root directory is CHMOD correctly */
		@chmod( \IPS\ROOT_PATH . "/applications/" . $app . '/dev', 0777 );
	
		return $dirToWrite;
	}
	
	/**
	 * Writes the /application/{app}/dev/{container}/{path} directory
	 *
	 * @param  string $app		 	 Application Directory
	 * @param  string $container	 Path to create (e.g. admin, front)
	 * @return string Path created
	 * @throws	\RuntimeException
	 */
	protected static function _writeThemePathDirectory( $app, $container, $path )
	{
		$dirToWrite = \IPS\ROOT_PATH . "/applications/" . $app . '/dev/' . $container . '/' . $path;
	
		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				throw new \RuntimeException('core_theme_dev_cannot_make_dir,' . $dirToWrite);
			}
			else
			{
				@chmod( $dirToWrite, IPS_FOLDER_PERMISSION );
			}
		}
	
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			throw new \RuntimeException('core_theme_dev_not_writeable,' . $dirToWrite);
		}
	
		return $dirToWrite;
	}

	/**
	 * Write skin resources
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	string	$app		 Application Directory
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public static function exportResources( $app )
	{
		try
		{
			self::_writeThemeContainerDirectory( $app, 'img' );
		}
		catch( \RuntimeException $e )
		{
			throw new \RuntimeException( $e->getMessage() );
		}
		
		foreach( \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_app=? AND resource_set_id=?', $app, 1 ) )->setKeyField('resource_id') as $resourceId => $resource )
		{
			try
			{
				$pathToWrite = self::_writeThemePathDirectory( $app, 'img', $resource['resource_location'] );
			}
			catch( \RuntimeException $e )
			{
				throw new \RuntimeException( $e->getMessage() );
			}
				
			if ( $resource['resource_path'] != '/' )
			{
				$_path = '';
					
				foreach( explode( '/', trim( $resource['resource_path'], '/' ) ) as $dir )
				{
					$_path .= '/' . trim( $dir, '/' );
					
					try
					{
						$pathToWrite = self::_writeThemePathDirectory( $app, 'img', $resource['resource_location'] . $_path );
					}
					catch( \RuntimeException $e )
					{
						throw new \RuntimeException( $e->getMessage() );
					}
				}
			}

			try
			{
				if ( ! @\file_put_contents( $pathToWrite . '/' . $resource['resource_name'], $resource['resource_data'] ) )
				{
					throw new \RuntimeException('core_theme_dev_cannot_write_resource,' . $pathToWrite . '/' . $resource['resource_name']);
				}
				else
				{
					@chmod( $pathToWrite . '/' . $resource['resource_name'], 0777 );
				}
			}
			catch( \InvalidArgumentException $e )
			{
				
			}
		}
	}
	
	/**
	 * Write CSS into the appropriate theme directory as plain text CSS ({resource="foo.png"} intact)
	 *
	 * @param	string	$app		 Application Directory
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public static function exportCss( $app )
	{
		try
		{
			self::_writeThemeContainerDirectory( $app, 'css' );
		}
		catch( \RuntimeException $e )
		{
			throw new \RuntimeException( $e->getMessage() );
		}
		
		$css = static::master()->getRawCss();
	
		foreach( $css as $appDir => $data )
		{
			foreach( $css[ $appDir ] as $location => $data )
			{
				try
				{
					$pathToWrite = self::_writeThemePathDirectory( $app, 'css', $location );
				}
				catch( \RuntimeException $e )
				{
					throw new \RuntimeException( $e->getMessage() );
				}
					
				foreach( $css[ $appDir ][ $location ] as $path => $data )
				{
					if ( $path != '.' )
					{
						$_path = $path;
	
						if ( \strstr( $path, '/' ) )
						{
							$_path = '';
								
							foreach( explode( '/', $path ) as $dir )
							{
								$_path .= '/' . trim( $dir, '/' );
								
								try
								{
									$pathToWrite = self::_writeThemePathDirectory( $app, 'css', $location . $_path );
								}
								catch( \RuntimeException $e )
								{
									throw new \RuntimeException( $e->getMessage() );
								}
							}
						}
						else
						{
							try
							{
								$pathToWrite = self::_writeThemePathDirectory( $app, 'css', $location . '/' . $path );
							}
							catch( \RuntimeException $e )
							{
								throw new \RuntimeException( $e->getMessage() );
							}
						}
					}
						
					foreach( $css[ $appDir ][ $location ][ $path ] as $name => $data )
					{
						$params = array();
						$write  = '';
	
						if ( $data['css_hidden'] )
						{
							$params[] = 'hidden="1"';
						}
	
						if ( count( $params ) )
						{
							$write  .= '/*<ips:css ' . implode( ' ', $params ) . ' />*/' . "\n";
						}
	
						$write .= ( empty( $data['css_content'] ) ) ? '/* No Content */' : $data['css_content'];
							
						if ( ! @\file_put_contents( $pathToWrite . '/' . $data['css_name'], $write ) )
						{
							throw new \RuntimeException('core_theme_dev_cannot_write_css,' . $pathToWrite . '/' . $data['css_name']);
						}
						else
						{
							@chmod( $pathToWrite . '/' . $data['css_name'], 0777 );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Write templates into the appropriate theme directory as plain text templates ({{logic}} intact)
	 *
	 * @param	string	$app		 Application Directory
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public static function exportTemplates( $app )
	{
		try
		{
			self::_writeThemeContainerDirectory( $app, 'html' );
		}
		catch( \RuntimeException $e )
		{
			throw new \RuntimeException( $e->getMessage() );
		}
		
		$templates = static::master()->getRawTemplates();
	
		foreach( $templates as $appDir => $data )
		{
			foreach( $templates[ $app ] as $location => $data )
			{
				try
				{
					self::_writeThemePathDirectory( $app, 'html', $location );
				}
				catch( \RuntimeException $e )
				{
					throw new \RuntimeException( $e->getMessage() );
				}
					
				foreach( $templates[ $app ][ $location ] as $group => $data )
				{
					try
					{
						$pathToWrite = self::_writeThemePathDirectory( $app, 'html', $location . '/' . $group );
					}
					catch( \RuntimeException $e )
					{
						throw new \RuntimeException( $e->getMessage() );
					}
					
					foreach( $templates[ $app ][ $location ][ $group ] as $name => $data )
					{
						$write  = '<ips:template parameters="' . $data['template_data'] . '" />' . "\n";
						$write .= $data['template_content'];
	
						if ( ! @\file_put_contents( $pathToWrite . '/' . $data['template_name'] . '.phtml', $write ) )
						{
							throw new \RuntimeException('core_theme_dev_cannot_write_template,' . $pathToWrite . '/' . $data['css_name']);
						}
						else
						{
							@chmod( $pathToWrite . '/' . $data['template_name'] . '.phtml', 0777 );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Write JSON file so dev installs are synced
	 *
	 * @return void
	 */
	public static function writeThemeSettingsToDisk()
	{
		$data = array();
	
		foreach (
				\IPS\Db::i()->select(
						'sv.*, core_theme_settings_fields.*',
						'core_theme_settings_fields',
						array( 'sc_set_id=?', 1 )
				)->join(
						array('core_theme_settings_values', 'sv'),
						'sv.sv_id=core_theme_settings_fields.sc_id'
				)->setKeyField('sc_key') as $row
		)
		{
			$row['sc_default'] = ( $row['sv_value'] ) ? $row['sv_value'] : $row['sc_default'];
			
			unset( $row['sc_id'], $row['sc_set_id'], $row['sc_updated'], $row['sv_id'], $row['sv_value'] );

			$data[ $row['sc_app'] ][] = $row;
		}
		foreach( $data as $app => $json )
		{
			$file = \IPS\ROOT_PATH . "/applications/{$app}/data/themesettings.json";
				
			if( @\file_put_contents( $file, version_compare( PHP_VERSION, '5.4.0' ) >= 0 ? json_encode( $json, JSON_PRETTY_PRINT ) : json_encode( $json ) ) === FALSE )
			{
				\IPS\Output::i()->error( 'dev_could_not_write_data', '1C103/4', 403, '' );
			}
		}
	}
	
	/**
	 * Remove an entire theme directory
	 *
	 * @param	string		$dir		Path
	 * @return  void
	 */
	public static function removeThemeDirectory( $dir )
	{
		if ( is_dir( $dir ) )
		{
			foreach ( new \DirectoryIterator( $dir ) as $f )
			{
				if ( !$f->isDot() )
				{
					if ( $f->isDir() )
					{
						static::removeThemeDirectory( $f->getPathname() );
					}
					else
					{
						@unlink( $f->getPathname() );
					}
				}
			}

			$handle = opendir( $dir );
			closedir( $handle );
			rmdir( $dir );
		}	
	}
	
	/**
	 * Returns the namespace for the template class
	 * @return string
	 */
	protected static function _getTemplateNamespace()
	{
		return 'IPS\\Theme\\Dev\\';
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( $app, $location=null, $path=null )
	{
		return rtrim( \IPS\ROOT_PATH . "/applications/{$app}/dev/html/{$location}/{$path}", '/' ) . '/';
	}
	
	/**
	 * Returns the path for the IN_DEV CSS file
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getCssPath( $app, $location=null, $path=null )
	{
		return rtrim( \IPS\ROOT_PATH . "/applications/{$app}/dev/css/{$location}/{$path}", '/' ) . ( \stristr( $path, '.css' ) ? '' : '/' );
	}
	
	/**
	 * Returns the path for the IN_DEV resource files
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getResourcePath( $app, $location=null, $path=null )
	{
		if ( $app == 'core' and $location == 'global' and mb_substr( $path, 0, mb_strpos( $path, '/' ) ) === 'plugins' )
		{
			foreach ( new \GlobIterator( \IPS\ROOT_PATH . '/plugins/*/dev/resources/' . mb_substr( $path, mb_strpos( $path, '/' ) + 1 ) ) as $file )
			{
				return $file->getPathName();
			}
		}
		
		return rtrim( \IPS\ROOT_PATH . "/applications/{$app}/dev/resources/{$location}/{$path}", '/' ) . ( ( \stristr( $path, '.' ) || \stristr( $path, '{' ) ) ? '' : '/' );
	}

	/**
	 * Basic debugging output functionality
	 *
	 * @param	\Exception $e	Exception that we are outputting
	 * @return	void
	 */
	public static function varDumpException( $e )
	{
		echo '<pre>';
		print_r( $e );
		exit;
	}
}