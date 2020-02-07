<?php
/**
 * @brief		Advanced theming mode
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		06 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme\Advanced;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advanced theming mode
 */
class _Theme extends \IPS\Theme\Dev\Theme
{
	/**
	 * [Brief] Currently building /theme/ files boolean
	 */
	public static $buildingFiles = false;
	
	/**
	 * [Brief] Currently selected theme ID
	 */
	public static $currentThemeId = NULL;
	
	/**
	 * [Brief] Themes that need writing out as flat files.
	 */
	public static $toBuild = null;
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		$column	= 'skin';

		if( \IPS\Dispatcher::hasInstance() )
		{
			$column	= ( \IPS\Dispatcher::i()->controllerLocation == 'admin' ) ? 'acp_skin' : 'skin';
		}

		if ( \IPS\Member::loggedIn()->$column and array_key_exists( \IPS\Member::loggedIn()->$column, \IPS\Theme::themes() ) )
		{
			$setId = \IPS\Member::loggedIn()->$column;
				
			if ( \IPS\Theme::load( $setId )->canAccess() !== true )
			{
				$setId =  ( $column == 'skin' ? \IPS\Theme::defaultTheme() : \IPS\Theme::defaultAcpTheme() );
				
				/* Restore default theme for member */
				\IPS\Member::loggedIn()->$column = $setId;
				\IPS\Member::loggedIn()->save();
			}
		}
		else
		{
			$setId = ( $column == 'skin' ? \IPS\Theme::defaultTheme() : \IPS\Theme::defaultAcpTheme() );
		}

		static::$currentThemeId = ( static::$currentThemeId ) ? static::$currentThemeId : $setId;

		/* Check to make sure the files are there */
		if ( static::$buildingFiles === FALSE and ( ! is_dir( static::_getHtmlPath('core') ) OR ! is_dir( static::_getCssPath('core') ) OR ! is_dir( static::_getResourcePath('core') ) ) )
		{
			/* Toggle the setting as something has gone wrong */
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 0 ), array( 'conf_key=?', 'theme_designers_mode' ) );
			unset( \IPS\Data\Store::i()->settings );

			\IPS\Settings::i()->theme_designers_mode = 0;

			/* Redirect to an error (redirect so we get a proper theme object after switching off designers mode) */
			if( \IPS\Dispatcher::hasInstance() AND \IPS\Dispatcher::i()->controllerLocation == 'admin' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=customization&controller=themes&do=designersmode' ) );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=designermode&do=missing&id=' . static::$currentThemeId, 'front' ) );
			}
		}
	}
	
	/**
	 * Load the custom language strings/values
	 *
	 * @param	int		$id		Theme ID
	 * @return	void
	 */
	public static function loadLanguage( $id )
	{
		if ( file_exists( \IPS\ROOT_PATH . "/themes/{$id}/lang.php" ) )
		{
			require \IPS\ROOT_PATH . "/themes/{$id}/lang.php";
			foreach ( $lang as $k => $v )
			{
				\IPS\Member::loggedIn()->language()->words[ $k ] = $v;
			}
		}
	}

	/**
	 * (re)import HTML templates into the template DB
	 *
	 * @param	string $app	Application Key
	 * @param	int	   $id	Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * 
	 * @return	void
	 */
	public static function importDevHtml( $app, $id )
	{
		static::$currentThemeId = $id;
		return parent::importDevHtml( $app, $id );
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
		static::$currentThemeId = $id;
		return parent::importDevCss( $app, $id );
	}
	
	/**
	 * Build Resources ready for non IN_DEV use
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	string|array	$app	App (e.g. core, forum)
	 * @param	int	   $id	Theme Set Id (0 if IN_DEV and not in advanced theming mode)
	 * 
	 * @return	void
	 */
	public static function importDevResources( $app=NULL, $id )
	{
		static::$currentThemeId = $id;
		return parent::importDevResources( $app, $id );
	}
	
	/**
	 * Get a template
	 *
	 * @param	string	$group				Template Group
	 * @param	string	$app				Application key (NULL for current application)
	 * @param	string	$location		    Template Location (NULL for current template location)
	 * @return	\IPS\Output\Template
	 */
	public function getTemplate( $group, $app=NULL, $location=NULL )
	{
		if ( static::$buildingFiles === true )
		{
			return ( \IPS\IN_DEV ) ? \IPS\Theme\Dev\Theme::getTemplate( $group, $app, $location ) : \IPS\Theme::getTemplate( $group, $app, $location );
		}

		return parent::getTemplate( $group, $app, $location );
	}
	
	/**
	 * Returns a list of theme IDs that need building
	 * 
	 * @return array
	 */
	public static function getToBuild()
	{
		if ( static::$toBuild === null )
		{
			foreach ( \IPS\Theme::themes() as $id => $set )
			{
				/* @todo consider using filestamps or compare template contents to determine if a theme ID needs updating */
				static::$toBuild[] = $id;
			}
		}

		return static::$toBuild;
	}
	
	/**
	 * Determines if we're currently in a multiple redirect during file building
	 * 
	 * @return boolean
	 */
	public static function buildingInProgress()
	{
		if ( \IPS\Dispatcher::i()->controllerLocation == 'front' AND \IPS\Request::i()->mr )
		{
			$data = json_decode( urldecode( base64_decode( \IPS\Request::i()->mr ) ), true );
				
			if ( isset( $data['buildingDesignersFiles'] ) )
			{
				return true;
			}
		}
	
		return false;
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
		$insertId = NULL;

		try
		{
			$insertId = parent::addTemplate( $data );
		}
		catch ( \OverflowException $e )
		{
			/* Do nothing. It exists */
			throw new \OverflowException;
		}
		catch ( \InvalidArgumentException $e )
		{
			throw new \InvalidArgumentException;
		}

		$currentThemeId = static::$currentThemeId;

		foreach( \IPS\Theme::themes() as $theme )
		{
			if ( is_dir( \IPS\ROOT_PATH . "/themes/" . $theme->id ) )
			{
				static::$currentThemeId = $theme->id;

				static::_writeThemeContainerDirectory( $data['app'], 'html' );
				static::_writeThemePathDirectory( $data['app'], 'html', $data['app'] . '/' . $data['location'] );
				static::_writeThemePathDirectory( $data['app'], 'html', $data['app'] . '/' . $data['location'] . '/' . $data['group'] );

				$pathToWrite = static::_getHtmlPath( $data['app'], $data['location'], $data['group'] );

				$write  = '<ips:template parameters="' . $data['variables'] . '" />' . "\n";
				$write .= $data['content'];

				if ( ! @\file_put_contents( $pathToWrite . $data['name'] . '.phtml', $write ) )
				{
					throw new \RuntimeException('core_theme_dev_cannot_write_template,' . $pathToWrite . $data['name'] . '.phtml' );
				}
				else
				{
					@chmod( $pathToWrite . '/' . $data['name'] . '.phtml', 0777 );
				}
			}
		}

		static::$currentThemeId = $currentThemeId;

		return $insertId;
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
		$insertId = NULL;

		try
		{
			$insertId = parent::addCss( $data );
		}
		catch ( \OverflowException $e )
		{
			/* Do nothing. It exists */
			#throw new \OverflowException;
		}
		catch ( \InvalidArgumentException $e )
		{
			throw new \InvalidArgumentException;
		}

		$currentThemeId = static::$currentThemeId;

		foreach( \IPS\Theme::themes() as $theme )
		{
			if ( is_dir( \IPS\ROOT_PATH . "/themes/" . $theme->id ) )
			{
				static::$currentThemeId = $theme->id;

				static::_writeThemeContainerDirectory( $data['app'], 'css' );
				static::_writeThemePathDirectory( $data['app'], 'css', $data['app'] . '/' . $data['location'] );
				static::_writeThemePathDirectory( $data['app'], 'css', $data['app'] . '/' . $data['location'] . '/' . $data['path'] );

				$pathToWrite = static::_getCssPath( $data['app'], $data['location'], $data['path'] );

				$write = ( empty( $data['content'] ) ) ? '/* No Content */' : $data['content'];

				if ( ! @\file_put_contents( $pathToWrite . '/' . $data['name'], $write ) )
				{
					\IPS\Output::i()->error( 'theme_dm_err_cant_write_css', '4S142/10', 403, '' );
				}
				else
				{
					@chmod( $pathToWrite . '/' . $data['name'], 0777 );
				}
			}
		}

		static::$currentThemeId = $currentThemeId;

		return $insertId;
	}

	/**
	 * Writes the /theme/{id}/ directory
	 * 
	 * @return string	Path created
	 */
	protected static function _writeThemeIdDirectory()
	{
		$dirToWrite = \IPS\ROOT_PATH . "/themes/" . static::$currentThemeId;
		
		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				\IPS\Output::i()->error( 'theme_dm_err_cant_write_theme_id', '4S142/3', 403, '' );
			}
			else
			{
				@chmod( $dirToWrite, \IPS\IPS_FOLDER_PERMISSION );
			}
		}
		
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			\IPS\Output::i()->error( 'theme_dm_err_cant_write_into_theme_id', '4S142/4', 403, '' );
		}
		
		/* Make sure previous versions of this file are removed */
		if ( file_exists( $dirToWrite . '/lang.php' ) )
		{
			unlink( $dirToWrite . '/lang.php' );
		}
			
		$languageStrings	= \IPS\Db::i()->select( 'word_key, word_default', 'core_sys_lang_words', array( 'word_theme=?', static::$currentThemeId ) )->setKeyField('word_key')->setValueField('word_default');

		if( count( $languageStrings ) )
		{
			$langToWrite = "\$lang = array(\n";

			foreach( $languageStrings as $key => $string )
			{
				$langToWrite	.= "'{$key}'\t\t=> '" . str_replace( "'", "\\'", $string ) . "',\n";
			}

			$langToWrite .= ");";
		}
		else
		{
			$langToWrite = "\$lang = array(\n\t\n);";
		}

		@\file_put_contents( $dirToWrite . '/lang.php', '<?' . "php\n\n{$langToWrite}\n" );
		
		return $dirToWrite;
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
		$dirToWrite = \IPS\ROOT_PATH . "/themes/" . static::$currentThemeId . '/' . $container;
		
		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				\IPS\Output::i()->error( 'theme_dm_err_cant_write_theme_id', '4S142/5', 403, '' );
			}
			else
			{
				@chmod( $dirToWrite, \IPS\IPS_FOLDER_PERMISSION );
			}
		}
	
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			\IPS\Output::i()->error( 'theme_dm_err_cant_write_into_theme_id', '4S142/6', 403, '' );
		}
		
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
		$dirToWrite = \IPS\ROOT_PATH . "/themes/" . static::$currentThemeId . '/' . $container . '/' . $path;

		if ( ! is_dir( $dirToWrite ) )
		{
			if ( ! @mkdir( $dirToWrite ) )
			{
				\IPS\Output::i()->error( 'theme_dm_err_cant_write_theme_id', '4S142/7', 403, '' );
			}
			else
			{
				@chmod( $dirToWrite, \IPS\IPS_FOLDER_PERMISSION );
			}
		}
	
		/* Check its writeable */
		if ( ! is_writeable( $dirToWrite ) )
		{
			\IPS\Output::i()->error( 'theme_dm_err_cant_write_into_theme_id', '4S142/8', 403, '' );
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
		static::_writeThemeIdDirectory();
		static::_writeThemeContainerDirectory( $app, 'resources');
		
		foreach( \IPS\Db::i()->select( '*', 'core_theme_resources', array( 'resource_set_id=?', static::$currentThemeId ) )->setKeyField('resource_id') as $resourceId => $resource )
		{
			static::_writeThemePathDirectory( $app, 'resources', $resource['resource_app'] );
			$pathToWrite = static::_writeThemePathDirectory( $app, 'resources', $resource['resource_app'] . '/' . $resource['resource_location'] );
			
			if ( $resource['resource_path'] != '/' )
			{
				$_path = '';
			
				foreach( explode( '/', trim( $resource['resource_path'], '/' ) ) as $dir )
				{
					$_path .= '/' . trim( $dir, '/' );
		
					$pathToWrite = static::_writeThemePathDirectory( $app, 'resources', $resource['resource_app'] . '/' . $resource['resource_location'] . $_path );
				}
			}

			if ( ! \file_put_contents( $pathToWrite . '/' . $resource['resource_name'], $resource['resource_data'] ) )
			{
				\IPS\Output::i()->error( 'theme_dm_err_cant_write_img', '4S142/A', 403, '' );
			}
			else
			{
				@chmod( $pathToWrite . '/' . $resource['resource_name'], 0777 );
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
		static::_writeThemeIdDirectory();
		static::_writeThemeContainerDirectory( $app, 'css');
	
		$css = static::load( static::$currentThemeId )->getRawCss();
	
		foreach( $css as $app => $data )
		{
			static::_writeThemePathDirectory( $app, 'css', $app );
				
			foreach( $css[ $app ] as $location => $data )
			{
				$pathToWrite = static::_writeThemePathDirectory( $app, 'css', $app . '/' . $location );

				foreach( $css[ $app ][ $location ] as $path => $data )
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
								
								$pathToWrite = static::_writeThemePathDirectory( $app, 'css', $app . '/' . $location . $_path );
							}
						}
						else
						{
							$pathToWrite = static::_writeThemePathDirectory( $app, 'css', $app . '/' . $location . '/' . $path );
						}
					}
					
					foreach( $css[ $app ][ $location ][ $path ] as $name => $data )
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
							\IPS\Output::i()->error( 'theme_dm_err_cant_write_css', '4S142/10', 403, '' );
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
		static::_writeThemeIdDirectory();
		static::_writeThemeContainerDirectory( $app, 'html');
	
		$templates = static::load( static::$currentThemeId )->getRawTemplates( $app );

		foreach( $templates as $app => $data )
		{
			static::_writeThemePathDirectory( $app, 'html', $app );
			
			foreach( $templates[ $app ] as $location => $data )
			{
				static::_writeThemePathDirectory( $app, 'html', $app . '/' . $location );
				
				foreach( $templates[ $app ][ $location ] as $group => $data )
				{
					$pathToWrite = static::_writeThemePathDirectory( $app, 'html', $app . '/' . $location . '/' . $group );
					
					foreach( $templates[ $app ][ $location ][ $group ] as $name => $data )
					{
						$write  = '<ips:template parameters="' . $data['template_data'] . '" />' . "\n";
						$write .= $data['template_content'];
						
						if ( ! @\file_put_contents( $pathToWrite . '/' . $data['template_name'] . '.phtml', $write ) )
						{
							\IPS\Output::i()->error( 'theme_dm_err_cant_write_template', '4S142/9', 403, '' );
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
	 * Returns the namespace for the template class
	 * 
	 * @return	string
	 */
	protected static function _getTemplateNamespace()
	{
		if ( static::$buildingFiles !== true )
		{
			return 'IPS\\Theme\\Advanced\\';
		}
		else
		{
			return parent::_getTemplateNamespace();
		}
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 * 
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( $app, $location=null, $path=null )
	{
		if ( static::$buildingFiles !== true )
		{
			return rtrim( \IPS\ROOT_PATH . "/themes/" . static::$currentThemeId . "/html/{$app}/{$location}/{$path}", '/' ) . '/';
		}
		else
		{
			return parent::_getHtmlPath( $app, $location, $path );
		}
	}
	
	/**
	 * Returns the path for the IN_DEV CSS file
	 * 
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getCssPath( $app, $location=null, $path=null )
	{
		if ( static::$buildingFiles !== true )
		{
			return rtrim( \IPS\ROOT_PATH . "/themes/" . static::$currentThemeId . "/css/{$app}/{$location}/{$path}", '/' ) . ( \stristr( $path, '.css' ) ? '' : '/' );
		}	
		else
		{
			return parent::_getCssPath( $app, $location, $path );
		}
	}
	
	/**
	 * Returns the path for the IN_DEV resource files
	 * 
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getResourcePath( $app, $location=null, $path=null )
	{
		if ( static::$buildingFiles !== true )
		{
			return rtrim( \IPS\ROOT_PATH . "/themes/" . static::$currentThemeId . "/resources/{$app}/{$location}/{$path}", '/' ) . ( \stristr( $path, '.' ) ? '' : '/' );
		}
		else
		{
			return parent::_getResourcePath( $app, $location, $path );
		}
	}
	
}