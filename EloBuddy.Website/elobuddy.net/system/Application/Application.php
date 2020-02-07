<?php
/**
 * @brief		Application Class
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
 * @brief	Abstract class that applications extend and use to handle application data
 */
class _Application extends \IPS\Node\Model
{
	/**
	 * @brief	IPS Applications
	 */
	public static $ipsApps = array(
		'blog',
		'calendar',
		'chat',
		'cms',
		'core',
		'downloads',
		'forums',
		'gallery',
		'nexus'
		);

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll	= FALSE;

	/**
	 * @brief	Defined versions
	 */
	protected $definedVersions	= NULL;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = '__app_';

	/**
	 * @brief	Defined theme locations for the theme system
	 */
	public $themeLocations = array('admin', 'front', 'global');
	
	/**
	 * Set default
	 *
	 * @return void
	 */
	public function setAsDefault()
	{
		/* Get the FURL definition */
		$furlDefinition = \IPS\Http\Url::furlDefinition();

		try
		{
			/* Add the top-level directory to all the FURLs for the old default app */
			$previousDefaultApp = \IPS\Db::i()->select( 'app_directory', 'core_applications', 'app_default=1' )->first();
			if( file_exists( \IPS\ROOT_PATH . "/applications/{$previousDefaultApp}/data/furl.json" ) )
			{
				$oldDefaultAppDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', \file_get_contents( \IPS\ROOT_PATH . "/applications/{$previousDefaultApp}/data/furl.json" ) ), TRUE );
				if ( $oldDefaultAppDefinition['topLevel'] )
				{
					foreach ( $oldDefaultAppDefinition['pages'] as $k => $data )
					{
						if ( isset( $furlDefinition[ $k ] ) )
						{
							$furlDefinition[ $k ]['without_top_level'] = $furlDefinition[ $k ]['friendly'];
							$furlDefinition[ $k ]['friendly'] = rtrim( $oldDefaultAppDefinition['topLevel'] . '/' . $furlDefinition[ $k ]['friendly'], '/' );
						}
					}
				}
			}
		}
		catch ( \UnderflowException $e ){}

		
		/* And remove it from the new */
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/furl.json" ) )
		{
			$newDefaultAppDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', \file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/furl.json" ) ), TRUE );
			if ( $newDefaultAppDefinition['topLevel'] )
			{
				foreach ( $newDefaultAppDefinition['pages'] as $k => $data )
				{
					if ( isset( $furlDefinition[ $k ] ) )
					{
						unset( $furlDefinition[ $k ]['without_top_level'] );
						$furlDefinition[ $k ]['with_top_level'] = $furlDefinition[ $k ]['friendly'];
						$furlDefinition[ $k ]['friendly'] = rtrim( preg_replace( '/^' . preg_quote( $newDefaultAppDefinition['topLevel'] ) . '\/?/', '', $furlDefinition[ $k ]['friendly'] ), '/' );
					}
				}
			}
		}
				
		/* Save the new FURL definition */		
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $furlDefinition ) ), array( 'conf_key=?', 'furl_configuration' ) );
		
		/* Actually update the database */
		\IPS\Db::i()->update( 'core_applications', array( 'app_default' => 0 ) );
		\IPS\Db::i()->update( 'core_applications', array( 'app_default' => 1 ), array( 'app_id=?', $this->id ) );
		
		/* Clear cached data */
		unset( \IPS\Data\Store::i()->applications );
		unset( \IPS\Data\Store::i()->settings );
		\IPS\Member::clearCreateMenu();

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();
	}
	
	/**
	 * Get Applications
	 *
	 * @return	array
	 */
	public static function applications()
	{
		if( static::$gotAll === FALSE )
		{
			if ( isset( \IPS\Data\Store::i()->applications ) )
			{
				$rows = \IPS\Data\Store::i()->applications;
			}
			else
			{
				$rows = iterator_to_array( \IPS\Db::i()->select( '*', 'core_applications', NULL, 'app_position' ) );
				\IPS\Data\Store::i()->applications = $rows;
			}
			
			static::$multitons = array();
			
			foreach ( $rows as $row )
			{
				try
				{
					static::$multitons[ $row['app_directory'] ] = static::constructFromData( $row );
				}
				catch( \UnexpectedValueException $e )
				{
					if ( mb_stristr( $e->getMessage(), 'Missing:' ) )
					{
						/* Ignore this, the app is in the table, but not 4.0 compatible */
						continue;
					}
				}
			}
			
			static::$gotAll = TRUE;
		}
		
		return static::$multitons;
	}

	/**
	 * Get enabled applications
	 *
	 * @return	array
	 */
	public static function enabledApplications()
	{
		$applications	= static::applications();
		$enabled		= array();

		foreach( $applications as $key => $application )
		{
			if( $application->enabled )
			{
				$enabled[ $key ] = $application;
			}
		}
		
		return $enabled;
	}
	
	/**
	 * Does an application exist and is it enabled? Note: does not check if offline for a particular member
	 *
	 * @see		\IPS\Application::canAccess()
	 * @param	string	$key	Application key
	 * @return	bool
	 */
	public static function appIsEnabled( $key )
	{
		$applications = static::applications();
		
		if ( !array_key_exists( $key, $applications ) )
		{
			return FALSE;
		}

		if ( ! file_exists( \IPS\ROOT_PATH . '/applications/' . $key . '/Application.php' ) )
		{
			return FALSE;
		}
				
		return $applications[ $key ]->enabled;
	}
	 
	/**
	 * Load Record
	 *
	 * @see		\IPS\Db::build
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function load( $id, $idField=NULL, $extraWhereClause=NULL )
	{
		static::applications(); // Load all applications so we can grab the data from the cache
		return parent::load( $id, $idField, $extraWhereClause );
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @note	This is overridden to prevent UnexpectedValue exceptions when there is an old application record in core_applications without an Application.php file
	 * @return	array
	 */
	public static function roots( $permissionCheck='view', $member=NULL, $where=array() )
	{
		return static::applications();
	}

	/**
	 * Get all extensions
	 *
	 * @param	\IPS\Application|string		$app				The app key of the application which owns the extension
	 * @param	string						$extension			Extension Type
	 * @param	\IPS\Member|bool			$checkAccess		Check access permission for application against supplied member (or logged in member, if TRUE) before including extension
	 * @param	string|NULL					$firstApp			If specified, the application with this key will be returned first
	 * @param	string|NULL					$firstExtensionKey	If specified, the extension with this key will be returned first
	 * @param	bool						$construct			Should an object be returned? (If false, just the classname will be returned)
	 * @return	array
	 */
	public static function allExtensions( $app, $extension, $checkAccess=TRUE, $firstApp=NULL, $firstExtensionKey=NULL, $construct=TRUE )
	{
		$extensions = array();
	
		/* Get applications */
		$apps = static::applications();

		if ( $firstApp !== NULL )
		{
			$apps = static::$multitons;

			usort( $apps, function( $a, $b ) use ( $firstApp )
			{
				if ( $a->directory === $firstApp )
				{
					return -1;
				}
				if ( $b->directory === $firstApp )
				{
					return 1;
				}
				return 0;
			} );
		}
		
		/* Get extensions */
		foreach ( $apps as $application )
		{
			if ( !static::appIsEnabled( $application->directory ) )
			{
				continue;
			}
						
			if( $checkAccess !== FALSE )
			{
				if( !$application->canAccess( $checkAccess === TRUE ? NULL : $checkAccess ) )
				{
					continue;
				}
			}

			$_extensions = array();
			
			foreach ( $application->extensions( $app, $extension, $construct, $checkAccess ) as $key => $class )
			{
				$_extensions[ $application->directory . '_' . $key ] = $class;
			}

			if ( $firstExtensionKey !== NULL AND array_key_exists( $application->directory . '_' . $firstExtensionKey, $_extensions ) )
			{
				uksort( $_extensions, function( $a, $b ) use ( $application, $firstExtensionKey )
				{
					if ( $a === $application->directory . '_' . $firstExtensionKey )
					{
						return -1;
					}
					if ( $b === $application->directory . '_' . $firstExtensionKey )
					{
						return 1;
					}
					return 0;
				} );
			}

			$extensions = array_merge( $extensions, $_extensions );
		}
		
		/* Return */
		return $extensions;
	}

	/**
	 * Retrieve a list of applications that contain a specific type of extension
	 *
	 * @param	\IPS\Application|string		$app				The app key of the application which owns the extension
	 * @param	string						$extension			Extension Type
	 * @param	\IPS\Member|bool			$checkAccess		Check access permission for application against supplied member (or logged in member, if TRUE) before including extension
	 * @return	array
	 */
	public static function appsWithExtension( $app, $extension, $checkAccess=TRUE )
	{
		$_apps	= array();

		foreach( static::applications() as $application )
		{
			if ( static::appIsEnabled( $application->directory ) )
			{
				/* If $checkAccess is false we don't verify access to the app */
				if( $checkAccess !== FALSE )
				{
					/* If we passed true, we want to check current member, otherwise pass the member in directly */
					if( $application->canAccess( ( $checkAccess === TRUE ) ? NULL : $checkAccess ) !== TRUE )
					{
						continue;
					}
				}

				if( count( $application->extensions( $app, $extension ) ) )
				{
					$_apps[ $application->directory ] = $application;
				}
			}
		}

		return $_apps;
	}
	
	/**
	 * Get available version for an application
	 * Used by the installer/upgrader
	 *
	 * @param	string		$appKey	The application key
	 * @param	bool		$human	Return the human-readable version instead
	 * @return	int|null
	 */
	public static function getAvailableVersion( $appKey, $human=FALSE )
	{
		$versionsJson = \IPS\ROOT_PATH . "/applications/{$appKey}/data/versions.json";

		$_versions	= $human ? array_values( json_decode( file_get_contents( $versionsJson ), TRUE ) ) : array_keys( json_decode( file_get_contents( $versionsJson ), TRUE ) );
		if ( file_exists( $versionsJson ) and $versionsJson = $_versions )
		{
			return array_pop( $versionsJson );
		}
		
		return NULL;
	}

	/**
	 * Get all defined versions for an application
	 *
	 * @return	array
	 */
	public function getAllVersions()
	{
		if( $this->definedVersions !== NULL )
		{
			return $this->definedVersions;
		}

		$this->definedVersions	= array();

		$versionsJson = \IPS\ROOT_PATH . "/applications/{$this->directory}/data/versions.json";

		if ( file_exists( $versionsJson ) )
		{
			$this->definedVersions	= json_decode( file_get_contents( $versionsJson ), TRUE );
		}
		
		return $this->definedVersions;
	}
	
	/**
	 * Return the human version of an INT long version
	 *
	 * @param 	int 	$longVersion	Long version (10001)
	 * @return	string|false			Long Version (1.1.1 Beta 1)
	 */
	public function getHumanVersion( $longVersion )
	{
		$this->getAllVersions();
		
		if ( isset( $this->definedVersions[ $longVersion ] ) )
		{
			return $this->definedVersions[ (int) $longVersion ];
		}
		
		return false;
	}
	
	/**
	 * The available version we can upgrade to
	 *
	 * @return	array
	 */
	public function availableUpgrade( $latestOnly=FALSE )
	{
		$update = array();
		
		if( $this->update_version )
		{
			$versions = json_decode( $this->update_version, TRUE );
			if ( is_array( $versions ) and !isset( $versions[0] ) and isset( $versions['longversion'] ) )
			{
				$versions = array( $versions );
			}
			
			$update = array();
			foreach ( $versions as $data )
			{
				if( !empty($data['longversion']) AND $data['longversion'] > $this->long_version )
				{
					if( $data['released'] AND intval($data['released']) == $data['released'] AND \strlen($data['released']) == 10 )
					{
						$data['released']	= (string) \IPS\DateTime::ts( $data['released'] )->localeDate();
					}
						
					$update[]	= $data;
				}
			}
		}
		
		if ( !empty( $update ) and $latestOnly )
		{
			$update = array_pop( $update );
		}
		
		return $update;
	}
	
	/**
	 * MD5 check (returns path to files which do not match)
	 *
	 * @retrun	array
	 * @throws	\IPS\Http\Request\Exception
	 */
	public static function md5Check()
	{		
		/* For Community in the Cloud customers we cannot do this because they have encoded files
			and the encoder produces different output each time it runs, so every Cloud customer
			has different files, even for the same version */
		$key = \IPS\IPS::licenseKey();
		if ( $key['cloud'] )
		{
			return array();
		}
		
		/* For everyone else, get the correct md5 sums for each file... */
		$correctMd5s = \IPS\Http\Url::ips( 'md5' )->request()->get()->decodeJson();
				
		/* And return whichever ones don't match */
		return static::_md5sumCheckerIterator( \IPS\ROOT_PATH, $correctMd5s, 1 );
	}
	
	/**
	 * MD5 check directory
	 *
	 * @param	string	$directory		Directory to look through
	 * @param	array	$correctMd5s	The correct md5 hashes
	 * @param	int		$depth			How deep into the folder structure we are
	 * @return	array
	 */
	public static function _md5sumCheckerIterator( $directory, $correctMd5s, $depth = 1 )
	{
		$return = array();
		foreach( new \DirectoryIterator( $directory ) as $file )
		{
			if ( mb_substr( $file, 0, 1 ) === '.' or mb_substr( $file, 0, 1 ) === '_' or $file == 'index.html' )
			{
				continue;
			}
			
			if ( $file->isDir() )
			{
				/* We only want to check directories which are ours (not other random directories they have on the server) and we can ignore datastore, plugins and uploads */
				if ( $depth === 1 and !in_array( $file->getFilename(), array( \IPS\CP_DIRECTORY, 'api', 'applications', 'system' ) ) )
				{
					continue;
				}
				
				/* If this is an application directory but the application has been disabled then we shouldn't check it */
				if( mb_strpos( $file->getPathname(), \IPS\ROOT_PATH . DIRECTORY_SEPARATOR . 'applications' ) !== FALSE AND $file->getFilename() != 'applications' )
				{
					$applications = static::applications();

					/* If the directory name is a valid application key and the application is not enabled, don't check it */
					if( isset( $applications[ $file->getFilename() ] ) AND !static::appIsEnabled( $file->getFilename() ) )
					{
						continue;
					}
				}

				$return = array_merge( $return, static::_md5sumCheckerIterator( $file->getPathname(), $correctMd5s, $depth + 1 ) );
			}
			elseif ( mb_substr( $file, -4 ) === '.php' )
			{
				$fullPath = $file->getPathname();
				$shortPath = mb_substr( $fullPath, mb_strlen( \IPS\ROOT_PATH ) );
									
				if ( $shortPath != '/init.php' and isset( $correctMd5s[ $shortPath ] ) and !\IPS\Login::compareHashes( md5( preg_replace( '#\s#', '', utf8_decode( file_get_contents( $fullPath ) ) ) ), $correctMd5s[ $shortPath ] ) ) // We strip whitespace since FTP in ASCII mode will change the whitespace characters
				{
					$return[] = $fullPath;
				}
			}
		}
		return $return;
	}
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_applications';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'app_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'directory';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'app_id' );
		
	/**
	 * @brief	[Node] Subnode class
	 */
	public static $subnodeClass = 'IPS\Application\Module';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'applications_and_modules';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static $restrictions = array( 'app' => 'core', 'module' => 'applications', 'prefix' => 'app_' );
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		/* Load class */
		if( !file_exists( \IPS\ROOT_PATH . '/applications/' . $data['app_directory'] . '/Application.php' ) )
		{
			if( !\IPS\Dispatcher::hasInstance() OR \IPS\Dispatcher::i()->controllerLocation !== 'setup' )
			{
				throw new \UnexpectedValueException( "Missing: " . '/applications/' . $data['app_directory'] . '/Application.php' );
			}
			else
			{
				$className = "\\IPS\\{$data['app_directory']}\\Application";

				if( !class_exists( $className ) )
				{
					$code = <<<EOF
namespace IPS\\{$data['app_directory']};
class Application extends \\IPS\\Application{}
EOF;
					eval( $code );
				}
			}
		}
		else
		{
			require_once \IPS\ROOT_PATH . '/applications/' . $data['app_directory'] . '/Application.php';
		}

		/* Initiate an object */
		$classname = 'IPS\\' . $data['app_directory'] . '\\Application';
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix )
			{
				$k = \substr( $k, \strlen( static::$databasePrefix ) );
			}
			
			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
				
		/* Return */
		return $obj;
	}
	
	/**
	 * @brief	Modules Store
	 */
	protected $modules = NULL;
	
	/**
	 * Get Modules
	 *
	 * @see		static::$modules
	 * @param	string	$location	Location (e.g. "admin" or "front")
	 * @return	array
	 */
	public function modules( $location=NULL )
	{
		/* Don't have an instance? */
		if( $this->modules === NULL )
		{
			$modules = \IPS\Application\Module::modules();
			$this->modules = array_key_exists( $this->directory, $modules ) ? $modules[ $this->directory ] : array();
		}
		
		/* Return */
		return isset( $this->modules[ $location ] ) ? $this->modules[ $location ] : array();
	}
	
	/**
	 * Returns the ACP Menu JSON for this application.
	 *
	 * @return array
	 */
	public function acpMenu()
	{
		return json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/acpmenu.json" ), TRUE );
	}
	
	/**
	 * ACP Menu Numbers
	 *
	 * @param	array	$queryString	Query String
	 * @return	int
	 */
	public function acpMenuNumber( $queryString )
	{
		return 0;
	}
	
	/**
	 * Get Extensions
	 *
	 * @param	\IPS\Application|string		$app		    The app key of the application which owns the extension
	 * @param	string						$extension	    Extension Type
	 * @param	bool						$construct	    Should an object be returned? (If false, just the classname will be returned)
	 * @param	\IPS\Member|bool			$checkAccess	Check access permission for extension against supplied member (or logged in member, if TRUE)
	 * @return	array
	 */
	public function extensions( $app, $extension, $construct=TRUE, $checkAccess=FALSE )
	{		
		$app = ( is_string( $app ) ? $app : $app->directory );
		
		$classes = array();
		$directory = \IPS\ROOT_PATH . "/applications/{$this->directory}/extensions/{$app}/{$extension}";
		
		if ( is_dir( $directory ) )
		{
			$dir = new \DirectoryIterator( $directory );
						
			foreach ( $dir as $file )
			{
				/* Macs create copies of files with "._" prefix which breaks when we just load up all files in a dir, ignore those */
				if ( !$file->isDir() and !$file->isDot() and mb_substr( $file, -4 ) === '.php' AND mb_substr( $file, 0, 2 ) != '._' )
				{
					$classname = 'IPS\\' . $this->directory . '\extensions\\' . $app . '\\' . $extension . '\\' . mb_substr( $file, 0, -4 );
					
					if ( method_exists( $classname, 'generate' ) )
					{
						$classes = array_merge( $classes, $classname::generate() );
					}
					elseif ( !$construct )
					{
						$classes[ mb_substr( $file, 0, -4 ) ] = $classname;
					}
					else
					{
						try
						{							
							$classes[ mb_substr( $file, 0, -4 ) ] = new $classname( $checkAccess === TRUE ? \IPS\Member::loggedIn() : ( $checkAccess === FALSE ? NULL : $checkAccess ) );
						}
						catch( \RuntimeException $e ){}
					}
				}
			}
		}
		
		return $classes;
	}

	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title()
	{
		$key = "__app_{$this->directory}";
		return \IPS\Member::loggedIn()->language()->addToStack( $key );
	}
	
	/**
	 * [Node] Get Node Icon
	 *
	 * @return	string
	 */
	protected function get__icon()
	{
		return 'cubes';
	}
			
	/**
	 * [Node] Does this node have children?
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	bool				$subnodes			Include subnodes?
	 * @param	array				$_where				Additional WHERE clause
	 * @return	bool
	 */
	public function hasChildren( $permissionCheck='view', $member=NULL, $subnodes=TRUE, $_where=array() )
	{
		return $subnodes;
	}

	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return	bool
	 */
	public function canDelete()
	{
		if( \IPS\NO_WRITES or !static::restrictionCheck( 'delete' ) )
		{
			return FALSE;
		}

		if( $this->_data['protected'] )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{
			$this->_url = \IPS\Http\Url::internal( "app={$this->directory}" );
		}

		return $this->_url;
	}

	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	array(
	 				'icon.png'			// Path to icon
	 				'core'				// Application icon belongs to
	 			),
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url	Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		/* Get normal buttons */
		$buttons	= parent::getButtons( $url );
		$edit = NULL;
		$uninstall = NULL;
		if( \IPS\IN_DEV and isset( $buttons['edit'] ) )
		{
			$edit = $buttons['edit'];
		}
		unset( $buttons['edit'] );
		unset( $buttons['copy'] );
		if( isset( $buttons['delete'] ) )
		{
			$buttons['delete']['title']	= 'uninstall';
			$buttons['delete']['data']	= array( 'delete' => '' );
			
			$uninstall = $buttons['delete'];
			unset( $buttons['delete'] );
		}
		
		/* Default */
		if( $this->enabled )
		{
			$buttons['default']	= array(
				'icon'		=> $this->default ? 'star' : 'star-o',
				'title'		=> 'make_default_app',
				'link'		=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=setAsDefault" ),
			);
		}
		
		/* Online/offline */
		if( !$this->protected )
		{
			$buttons['offline']	= array(
				'icon'	=> 'lock', 
				'title'	=> 'permissions',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&id={$this->_id}&do=permissions" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-forceReload' => 'true', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('permissions') )
			);
		}
		
		/* View Details */
		$buttons['details']	= array(
			'icon'	=> 'search',
			'title'	=> 'app_view_details',
			'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&do=details&id={$this->_id}" ),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('app_view_details') )
		);
		
		/* Upgrade */
		if( !$this->protected )
		{
			$buttons['upgrade']	= array(
				'icon'	=> 'upload',
				'title'	=> 'upload_new_version',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=upload" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('upload_new_version') )
			);
		}
		
		/* Uninstall */
		if ( $uninstall )
		{
			$buttons['delete'] = $uninstall;
			if ( $this->default )
			{
				$buttons['delete']['data'] = array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('uninstall') );
			}
		}
				
		/* Developer */
		if( \IPS\IN_DEV )
		{			
			if ( $edit )
			{
				$buttons['edit'] = $edit;
			}
			
			$buttons['compilejs'] = array(
				'icon'	=> 'cog',
				'title'	=> 'app_compile_js',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=compilejs" )
			);
			
			$buttons['build'] = array(
				'icon'	=> 'cog',
				'title'	=> 'app_build',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=build" )
			);
			
			$buttons['export']	= array(
				'icon'	=> 'download',
				'title'	=> 'download',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=download" ),
				'data'	=> array(
					'controller'	=> 'core.admin.system.buildApp',
					'downloadURL'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=download&type=download" ),
					'buildURL'		=> \IPS\Http\Url::internal( "app=core&module=applications&controller=applications&appKey={$this->_id}&do=download&type=build" ),
				)
			);

			$buttons['developer']	= array(
				'icon'	=> 'cogs',
				'title'	=> 'developer_mode',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=developer&appKey={$this->_id}" ),
			);
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		if ( $this->directory == 'core' )
		{
			return TRUE;
		}
		return $this->enabled and ( !in_array( $this->directory, static::$ipsApps ) or $this->long_version == \IPS\Application::load('core')->long_version );
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
		if ( \IPS\NO_WRITES )
	    {
			throw new \RuntimeException;
	    }
		
		$this->enabled = $enabled;
		$this->save();
		\IPS\Plugin\Hook::writeDataFile();
	}
	
	/**
	 * [Node] Get whether or not this node is locked to current enabled/disabled status
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__locked()
	{
		if ( $this->directory == 'core' )
		{
			return TRUE;
		}
		if ( !$this->_enabled and in_array( $this->directory, static::$ipsApps ) and $this->long_version != \IPS\Application::load('core')->long_version )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * [Node] Get Node Description
	 *
	 * @return	string|null
	 */
	protected function get__description()
	{
		if ( $this->_locked and $this->directory != 'core' )
		{
			return \IPS\Member::loggedIn()->language()->addToStack('app_force_disabled');
		}
		elseif ( $this->disabled_groups )
		{
			$groups = array();
			if ( $this->disabled_groups != '*' )
			{				
				foreach ( explode( ',', $this->disabled_groups ) as $groupId )
				{
					try
					{
						$groups[] = \IPS\Member\Group::load( $groupId )->name;
					}
					catch ( \OutOfRangeException $e ) { }
				}
			}
			
			if ( empty( $groups ) )
			{
				return \IPS\Member::loggedIn()->language()->addToStack('app_offline_to_all');
			}
			else
			{
				return \IPS\Member::loggedIn()->language()->addToStack( 'app_offline_to_groups', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $groups ) ) ) );
			}
		}
		return NULL;
	}

	/**
	 * Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function get__badge()
	{
		if( $this->update_version )
		{
			$data	= json_decode( $this->update_version, TRUE );
			
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

				return array(
					0	=> 'new',
					1	=> '',
					2	=> \IPS\Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $data['version'], $data['updateurl'], $released )
				);
			}
		}

		return NULL;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node?
	 *
	 * @return	bool
	 * @note	Modules are added via the developer center and should not be added by a regular admin via the standard node controller
	 */
	public function canAdd()
	{
		return false;
	}

	/**
	 * [Node] Does the currently logged in user have permission to add aa root node?
	 *
	 * @return	bool
	 * @note	If IN_DEV is on, the admin can create a new application
	 */
	public static function canAddRoot()
	{
		return ( \IPS\IN_DEV ) ? true : false;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 * @note	We don't allow permissions to be set for applications - they are handled by modules and by the enabled/disabled mode
	 */
	public function canManagePermissions()
	{
		return false;
	}
	
	/**
	 * Add or edit an application
	 *
	 * @param	\IPS\Helpers\Form	$form	Form object we can add our fields to
	 * @return	void
	 */
	public function form( &$form )
	{
		if ( !$this->directory )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'app_title', NULL, FALSE, array( 'app' => 'core', 'key' => ( !$this->directory ) ? NULL : "__app_{$this->directory}" ) ) );
		}

		$form->add( new \IPS\Helpers\Form\Text( 'app_directory', $this->directory, TRUE, array( 'disabled' => $this->id ? TRUE : FALSE, 'regex' => '/^[a-zA-Z][a-zA-Z0-9]+$/' ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'app_author', $this->author ) );
		$form->add( new \IPS\Helpers\Form\Url( 'app_website', $this->website ) );
		$form->add( new \IPS\Helpers\Form\Url( 'app_update_check', $this->update_check ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'app_protected', $this->protected, FALSE ) );

		if( !$this->id )
		{
			$form->add( new \IPS\Helpers\Form\Custom( 'app_versions', array(), TRUE, array( 'getHtml' => function( $element )
			{
				return \IPS\Theme::i()->getTemplate( 'applications' )->versionFormField();
			} ), NULL, NULL, NULL, 'app_versions' ) );
		}
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		/* New application stuff */
		if ( !$this->id )
		{
			/* Check dir is writable */
			if( !is_writable( \IPS\ROOT_PATH . '/applications/' ) )
			{
				\IPS\Output::i()->error( 'app_dir_not_write', '4S134/2', 403, '' );
			}
			
			/* Check key isn't in use */
			$values['app_directory'] = mb_strtolower( $values['app_directory'] );
			try
			{
				$test = \IPS\Application::load( $values['app_directory'] );
				\IPS\Output::i()->error( 'app_error_key_used', '1S134/1', 403, '' );
			}
			catch ( \OutOfRangeException $e ) { }

			/* Make sure version info has been supplied */
			if( ( !$values['app_versions'][0] or !$values['app_versions'][1] ) or !is_numeric( $values['app_versions'][1] ) )
			{
				\IPS\Output::i()->error( 'app_error_invalid_version', '1S134/3', 403, '' );
			}

			/* Attempt to create the basic directory structure for the developer */
			if( is_writable( \IPS\ROOT_PATH . '/applications/' ) )
			{
				/* If we can make the root dir, we can create the subfolders */
				if( @mkdir( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] ) )
				{
					@chmod( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'], \IPS\FOLDER_PERMISSION_NO_WRITE );

					/* Create directories */
					foreach ( array( 'data', 'dev', 'dev/css', 'dev/email', 'dev/html', 'dev/resources', 'dev/js', 'extensions', 'extensions/core', 'hooks', 'interface', 'modules', 'modules/admin', 'modules/front', 'setup', '/setup/upg_' . $values['app_versions'][1], 'sources', 'tasks' ) as $f )
					{
						@mkdir( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/' . $f );
						@chmod( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/' . $f, \IPS\FOLDER_PERMISSION_NO_WRITE );
						\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/' . $f . '/index.html', '' );
					}

					/* Create files */
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/schema.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/settings.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/tasks.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/themesettings.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/acpmenu.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/modules.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/widgets.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/acpsearch.json', '{}' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/hooks.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/setup/upg_' . $values['app_versions'][1] . '/queries.json', '[]' );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/versions.json', json_encode( array( $values['app_versions'][1] => $values['app_versions'][0] ) ) );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/dev/lang.php', '<?' . "php\n\n\$lang = array(\n\t'__app_{$values['app_directory']}'\t=> \"{$values['app_title']}\"\n);\n" );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/dev/jslang.php', '<?' . "php\n\n\$lang = array(\n\n);\n" );
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/Application.php', str_replace(
						array(
							'{app}',
							'{website}',
							'{author}',
							'{year}',
							'{subpackage}',
							'{date}'
						),
						array(
							$values['app_directory'],
							$values['app_website'],
							$values['app_author'],
							date('Y'),
							$values['app_title'],
							date( 'd M Y' ),
						),
						file_get_contents( \IPS\ROOT_PATH . "/applications/core/data/defaults/Application.txt" )
					) );
	
					@\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $values['app_directory'] . '/data/application.json', json_encode( array(
						'application_title'	=> $values['app_title'],
						'app_author'		=> $values['app_author'],
						'app_directory'		=> $values['app_directory'],
						'app_protected'		=> $values['app_protected'],
						'app_website'		=> $values['app_website'],
						'app_update_check'	=> $values['app_update_check'],
					) ) );
				}
			}
			
			/* Enable it */
			$values['enabled']		= TRUE;
			$values['app_added']	= time();
		}

		/* Save it */
		if( isset( $values['app_versions'] ) )
		{
			$values['app_version'] = $values['app_versions'][0];
			$values['app_long_version'] = $values['app_versions'][1];
			unset( $values['app_versions'] );
		}

		if( isset( $values['app_title'] ) )
		{
			unset( $values['app_title'] );
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		/* Clear out member's cached "Create Menu" contents */
		\IPS\Member::clearCreateMenu();
		unset( \IPS\Data\Store::i()->applications );
		unset( \IPS\Data\Store::i()->settings );
	}

	/**
	 * Install database changes from the schema.json file
	 *
	 * @param	bool	$skipInserts	Skip inserts
	 * @throws \Exception
	 */
	public function installDatabaseSchema( $skipInserts=FALSE )
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/schema.json" ) )
		{
			$schema	= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/schema.json" ), TRUE );

			foreach( $schema as $table => $definition )
			{
				/* Look for missing tables first */
				if( !\IPS\Db::i()->checkForTable( $table ) )
				{
					\IPS\Db::i()->createTable( $definition );
				}
				else
				{
					/* If the table exists, look for missing columns */
					if( is_array( $definition['columns'] ) AND count( $definition['columns'] ) )
					{
						/* Get the table definition first */
						$tableDefinition = \IPS\Db::i()->getTableDefinition( $table );

						foreach( $definition['columns'] as $column )
						{
							/* Column does not exist in the table definition?  Add it then. */
							if( empty($tableDefinition['columns'][ $column['name'] ]) )
							{
								\IPS\Db::i()->addColumn( $table, $column );
							}
						}
					}
				}

				if ( isset( $definition['inserts'] ) AND !$skipInserts )
				{
					foreach ( $definition['inserts'] as $insertData )
					{
						$adminName = \IPS\Member::loggedIn()->name;
						try
						{
							\IPS\Db::i()->insert( $definition['name'], array_map( function( $column ) use( $adminName ) {
	                              if( !is_string( $column ) )
	                              {
	                                  return $column;
	                              }

	                              $column = str_replace( '<%TIME%>', time(), $column );
	                              $column = str_replace( '<%ADMIN_NAME%>', $adminName, $column );
	                              $column = str_replace( '<%IP_ADDRESS%>', $_SERVER['REMOTE_ADDR'], $column );
	                              return $column;
	                          }, $insertData ) );
						}
						catch( \IPS\Db\Exception $e )
						{}
					}
				}
			}
		}
		
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/install/queries.json" ) )
		{
			$schema	= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/install/queries.json" ), TRUE );

			ksort($schema);

			foreach( $schema as $instruction )
			{
				if ( $instruction['method'] === 'addColumn' )
				{
					/* Check to see if it exists first */
					$tableDefinition = \IPS\Db::i()->getTableDefinition( $instruction['params'][0] );
					
					if ( ! empty( $tableDefinition['columns'][ $instruction['params'][1]['name'] ] ) )
					{
						/* Run an alter instead */
						\IPS\Db::i()->changeColumn( $instruction['params'][0], $instruction['params'][1]['name'], $instruction['params'][1] );
						continue;
					}
				}
				
				try
				{
					if( isset( $instruction['params'][1] ) and is_array( $instruction['params'][1] ) )
					{
						$groups	= array_filter( iterator_to_array( \IPS\Db::i()->select( 'g_id', 'core_groups' ) ), function( $groupId ) {
							if( $groupId == 2 )
							{
								return FALSE;
							}

							return TRUE;
						});

						foreach( $instruction['params'][1] as $column => $value )
						{
							if( $value === "<%NO_GUESTS%>" )
							{
								$instruction['params'][1][ $column ]	= implode( ",", $groups );
							}
						}
					}

					call_user_func_array( array( \IPS\Db::i(), $instruction['method'] ), $instruction['params'] );
				}
				catch( \Exception $e )
				{
					if( $instruction['method'] == 'insert' )
					{
						return;
					}

					throw $e;
				}
			}
		}
	}

	/**
	 * Install database changes from an upgrade schema file
	 *
	 * @param	int		$version		Version to execute database updates from
	 * @param	int		$lastJsonIndex	JSON index to begin from
	 * @param	int		$limit			Limit updates
	 * @param	bool	$return			Check table size first and return queries for larger tables instead of running automatically
	 * @return	array					Returns an array: ( count: count of queries run, queriesToRun: array of queries to run)
	 * @note	We ignore some database errors that shouldn't prevent us from continuing.
	 * @li	1007: Can't create database because it already exists
	 * @li	1008: Can't drop database because it does not exist
	 * @li	1050: Can't rename a table as it already exists
	 * @li	1051: Can't drop a table because it doesn't exist
	 * @li	1060: Can't add a column as it already exists
	 * @li	1062: Can't add an index as index already exists
	 * @li	1062: Can't add a row as PKEY already exists
	 * @li	1091: Can't drop key or column because it does not exist
	 */
	public function installDatabaseUpdates( $version=0, $lastJsonIndex=0, $limit=50, $return=FALSE )
	{
		$toReturn    = array();
		$tableCounts = array();
		$count  = 0;

		/* Try to prevent timeouts to the extent possible */
		$cutOff			= null;

		if( $maxExecution = @ini_get( 'max_execution_time' ) )
		{
			/* If max_execution_time is set to "no limit" we should add a hard limit to prevent browser timeouts */
			if ( $maxExecution == -1 )
			{
				$maxExecution = 30;
			}
			$cutOff	= time() + ( $maxExecution * .5 );
		}

		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/upg_{$version}/queries.json" ) )
		{
			$schema	= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/upg_{$version}/queries.json" ), TRUE );
			
			ksort($schema, SORT_NUMERIC);

			foreach( $schema as $jsonIndex => $instruction['params'] )
			{
				if ( $lastJsonIndex AND ( $jsonIndex <= $lastJsonIndex ) )
				{
					continue;
				}
				
				if ( $count >= $limit )
				{
					return array( 'count' => $count, 'queriesToRun' => $toReturn );
				}
				else if( $cutOff !== null AND time() >= $cutOff )
				{
					return array( 'count' => $count, 'queriesToRun' => $toReturn );
				}
				
				$_SESSION['lastJsonIndex'] = $jsonIndex;
				
				$count++;

				$_table	= $instruction['params']['params'][0];

				if ( ! is_string( $_table ) )
				{
					$_table	= $instruction['params']['params'][0]['name'];
				}
				
				if ( ! isset( $tableCounts[ $_table ] ) and \IPS\Db::i()->checkForTable( $_table ) )
				{
					$tableCounts[ $_table ] = \IPS\Db::i()->select( 'count(*)', $_table )->first();
				}
				
				/* If we are deleting stuff, then make sure the counts are recounted after */
				if ( $instruction['params']['method'] == 'delete' and isset( $tableCounts[ $_table ] ) )
				{
					unset( $tableCounts[ $_table ] );
				}

				/* Check table size first and store query if requested */
				if( $return === TRUE )
				{
					if( 
						/* Only run manually if we have a table row count */
						isset( $tableCounts[ $_table ] ) AND 
						/* And only if the row count is greater than the manual threshold */
						$tableCounts[ $_table ] > \IPS\UPGRADE_MANUAL_THRESHOLD AND 
						/* And if it's not a drop table, insert or rename table query */
						!in_array( $instruction['params']['method'], array( 'dropTable', 'insert', 'renameTable' ) ) AND
						/* ANNNNNDDD only if the method is not delete or there's a where clause, i.e. a truncate table statement does not run manually */
						( $instruction['params']['method'] != 'delete' OR isset( $instructions['params']['params'][1] ) )
						)
					{
						\IPS\Log::debug( "Big table " . $_table . ", storing query to run manually", 'upgrade' );

						\IPS\Db::i()->returnQuery = TRUE;
						$query = call_user_func_array( array( \IPS\Db::i(), $instruction['params']['method'] ), $instruction['params']['params'] );

						if( $query )
						{
							$toReturn[] = $query;

							if ( $instruction['params']['method'] == 'renameTable' )
							{
								$tableCounts[ $instruction['params']['params'][1] ] = $tableCounts[ $_table ];

								foreach( $toReturn as $k => $v )
								{
									$toReturn[ $k ]	= preg_replace( "/\`" . \IPS\Db::i()->prefix . $_table . "\`/", "`" . \IPS\Db::i()->prefix . $instruction['params']['params'][1] . "`", $v );
								}
							}

							return array( 'count' => $count, 'queriesToRun' => $toReturn );
						}
					}
				}

				try
				{
					call_user_func_array( array( \IPS\Db::i(), $instruction['params']['method'] ), $instruction['params']['params'] );
				}
				catch( \IPS\Db\Exception $e )
				{

					/* If the issue is with a create table other than exists, we should just throw it */
					if ( $instruction['params']['method'] == 'createTable' and ! in_array( $e->getCode(), array( 1007, 1050 ) ) )
					{
						throw $e;
					}
					
					/* Can't change a column as it doesn't exist */
					if ( $e->getCode() == 1054 )
					{
						if ( $instruction['params']['method'] == 'changeColumn' )
						{
							if ( \IPS\Db::i()->checkForTable( $instruction['params']['params'][0] ) )
							{
								/* Does the column exist already? */
								if ( \IPS\Db::i()->checkForColumn( $instruction['params']['params'][0], $instruction['params']['params'][2]['name'] ) )
								{
									/* Just make sure it's up to date */
									\IPS\Db::i()->changeColumn( $instruction['params']['params'][0], $instruction['params']['params'][2]['name'], $instruction['params']['params'][2] );
									continue;
								}
								else
								{
									/* The table exists, so lets just add the column */
									\IPS\Db::i()->addColumn( $instruction['params']['params'][0], $instruction['params']['params'][2] );
								
									continue;
								}
							}
						}
						
						throw $e;
					}
					/* Can't rename a table as it doesn't exist */
					else if ( $e->getCode() == 1017 )
					{
						if ( $instruction['params']['method'] == 'renameTable' )
						{
							if ( \IPS\Db::i()->checkForTable( $instruction['params']['params'][1] ) )
							{
								/* The table we are renaming to *does* exist */
								continue;
							}
						}
						
						throw $e;
					}
					/* Possibly trying to change a column to not null that has NULL values */
					else if ( $e->getCode() == 1138 )
					{
						if ( $instruction['params']['method'] == 'changeColumn' and ! $instruction['params']['params'][2]['allow_null'] )
						{
							$currentDefintion = \IPS\Db::i()->getTableDefinition( $instruction['params']['params'][1] );
							$column = $currentDefintion[ $instruction['params']['params'][2]['name'] ];
							
							if ( isset( $currentDefintion['columns'][ $column ] ) AND $currentDefintion['columns'][ $column ]['allow_null'] )
							{
								\IPS\Db::i()->update( $instruction['params']['params'][1], array( $column => '' ), array( $column . ' IS NULL' ) );
								
								/* Just make sure it's up to date */
								\IPS\Db::i()->changeColumn( $instruction['params']['params'][0], $instruction['params']['params'][2]['name'], $instruction['params']['params'][2] );
								
								continue;
							}
						}
						
						throw $e;
					}
					/* If the error isn't important we should ignore it */
					else if( !in_array( $e->getCode(), array( 1007, 1008, 1050, 1060, 1061, 1062, 1091, 1051 ) ) )
					{
						throw $e;
					}
				}
			}
		}

		return array( 'count' => $count, 'queriesToRun' => $toReturn );
	}

	/**
	 * Rebuild common data during an install or upgrade. This is a shortcut method which
	 * * Installs module data from JSON file
	 * * Installs task data from JSON file
	 * * Installs setting data from JSON file
	 * * Installs ACP live search keywords from JSON file
	 * * Installs hooks from JSON file
	 * * Updates latest version in the database
	 *
	 * @param	bool	$skipMember		Skip clearing member cache clearing
	 * @return void
	 */
	public function installJsonData( $skipMember=FALSE )
	{
		/* Rebuild modules */
		$this->installModules();

		/* Rebuild tasks */
		$this->installTasks();

		/* Rebuild settings */
		$this->installSettings();
		
		/* Rebuild sidebar widgets */
		$this->installWidgets();

		/* Rebuild search keywords */
		$this->installSearchKeywords();
		
		/* Rebuild hooks */
		$this->installHooks();

		/* Update app version data */
		$versions		= $this->getAllVersions();
		$longVersions	= array_keys( $versions );
		$humanVersions	= array_values( $versions );

		if( count($versions) )
		{
			$latestLVersion	= array_pop( $longVersions );
			$latestHVersion	= array_pop( $humanVersions );

			\IPS\Db::i()->update( 'core_applications', array( 'app_version' => $latestHVersion, 'app_long_version' => $latestLVersion ), array( 'app_directory=?', $this->directory ) );
		}

		unset( \IPS\Data\Store::i()->applications );

		if( !$skipMember )
		{
			\IPS\Member::clearCreateMenu();
		}
	}

	/**
	 * Install the application's modules
	 *
	 * @return	void
	 */
	public function installModules()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/modules.json" ) )
		{
			$currentModules	= array();
			$moduleStore = array();

			foreach ( \IPS\Db::i()->select( '*', 'core_modules', array( 'sys_module_application=?', $this->directory ) ) as $row )
			{
				$currentModules[ $row['sys_module_area'] ][ $row['sys_module_key'] ] = array(
					'default_controller'	=> $row['sys_module_default_controller'],
					'protected'				=> $row['sys_module_protected']
				);
				$moduleStore[ $row['sys_module_area'] ][ $row['sys_module_key'] ] = $row;
			}
			
			$insert	= array();
			$update	= array();

			$position = 0;
			foreach( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/modules.json" ), TRUE ) as $area => $modules )
			{
				foreach ( $modules as $key => $data )
				{
					if ( !isset( $currentModules[ $area ][ $key ] ) )
					{
						$module = new \IPS\Application\Module;
					}
					elseif ( $currentModules[ $area ][ $key ] != $data )
					{
						$module = \IPS\Application\Module::constructFromData( $moduleStore[ $area ][ $key ] );
					}
					else
					{
						continue;
					}

					$module->application = $this->directory;
					$module->key = $key;
					$module->protected = intval( $data['protected'] );
					$module->visible = TRUE;
					$module->position = ++$position;
					$module->area = $area;
					$module->default_controller = $data['default_controller'];
					$module->default = isset( $data['default'] ) and $data['default'];
					$module->save( TRUE );
				}
			}
		}
	}

	/**
	 * Install the application's tasks
	 *
	 * @return	void
	 */
	public function installTasks()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/tasks.json" ) )
		{
			foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/tasks.json" ), TRUE ) as $key => $frequency )
			{
				\IPS\Db::i()->replace( 'core_tasks', array(
					'app'		=> $this->directory,
					'key'		=> $key,
					'frequency'	=> $frequency,
					'next_run'	=> \IPS\DateTime::create()->add( new \DateInterval( $frequency ) )->getTimestamp()
				) );
			}
		}
	}
	
	/**
	 * Install the application's extension data where required
	 *
	 * @param	bool	$newInstall	TRUE if the community is being installed for the first time (opposed to an app being added)
	 * @return	void
	 */
	public function installExtensions( $newInstall=FALSE )
	{
		/* File storage */
		$settings = json_decode( \IPS\Settings::i()->upload_settings, TRUE );
		
		try
		{
			$fileSystem = \IPS\Db::i()->select( '*', 'core_file_storage', array( 'method=?', 'FileSystem' ), 'id ASC' )->first();
		}
		catch( \UnderflowException $ex )
		{
			$fileSystem = \IPS\Db::i()->select( '*', 'core_file_storage', NULL, 'id ASC' )->first();
		}
		
		foreach( $this->extensions( 'core', 'FileStorage' ) as $key => $path )
		{
			$settings[ 'filestorage__' . $this->directory . '_' . $key ] = $fileSystem['id'];
		}
		
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $settings ) ), array( 'conf_key=?', 'upload_settings' ) );
		\IPS\Settings::i()->upload_settings = json_encode( $settings );
		unset( \IPS\Data\Store::i()->settings );
		
		$inserts = array();
		foreach( $this->extensions( 'core', 'Notifications' ) as $key => $class )
		{
			if ( method_exists( $class, 'getConfiguration' ) )
			{
				$defaults = $class->getConfiguration( NULL );
				
				foreach( $defaults AS $k => $config )
				{
					$inserts[] = array(
						'notification_key'	=> $k,
						'default'			=> implode( ',', $config['default'] ),
						'disabled'			=> implode( ',', $config['disabled'] ),
					);
				}
			}
		}
		
		if( count( $inserts ) )
		{
			\IPS\Db::i()->insert( 'core_notification_defaults', $inserts );
		}
		
		/* Install Menu items */
		if ( !$newInstall )
		{
			$defaultNavigation = $this->defaultFrontNavigation();
			foreach ( $defaultNavigation as $type => $tabs )
			{
				foreach ( $tabs as $config )
				{
					$config['real_app'] = $this->directory;
					if ( !isset( $config['app'] ) )
					{
						$config['app'] = $this->directory;
					}
					
					\IPS\core\FrontNavigation::insertMenuItem( NULL, $config, \IPS\Db::i()->select( 'MAX(position)', 'core_menu' )->first() );
				}
			}
			unset( \IPS\Data\Store::i()->frontNavigation );
		}
	}

	/**
	 * Install the application's settings
	 *
	 * @return	void
	 */
	public function installSettings()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/settings.json" ) )
		{
			$currentDefaults = iterator_to_array( \IPS\Db::i()->select( '*', 'core_sys_conf_settings' )->setKeyField('conf_key')->setValueField('conf_default') );

			$insert	= array();
			$update	= array();

			foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/settings.json" ), TRUE ) as $setting )
			{
				if ( ! array_key_exists( $setting['key'], $currentDefaults ) )
				{
					$insert[]	= array( 'conf_key' => $setting['key'], 'conf_value' => $setting['default'], 'conf_default' => $setting['default'], 'conf_app' => $this->directory );
				}
				elseif ( $currentDefaults[ $setting['key'] ] != $setting['default'] )
				{
					$update[]	= array( array( 'conf_default' => $setting['default'] ), array( 'conf_key=?', $setting['key'] ) );
				}
			}

			if ( !empty( $insert ) )
			{
				\IPS\Db::i()->insert( 'core_sys_conf_settings', $insert, TRUE );
			}
			
			foreach ( $update as $data )
			{
				\IPS\Db::i()->update( 'core_sys_conf_settings', $data[0], $data[1] );
			}
			
			unset( \IPS\Data\Store::i()->settings );
		}
	}

	/**
	 * Install the application's language strings
	 *
	 * @param	int|null		$offset Offset to begin import from
	 * @param	int|null		$limit	Number of rows to import
	 * @return	int				Rows inserted
	 */
	public function installLanguages( $offset=null, $limit=null )
	{
		$languages	= array_keys( \IPS\Lang::languages() );
		$inserted	= 0;
		
		$current = array();
		foreach( $languages as $languageId )
		{
			foreach( iterator_to_array( \IPS\Db::i()->select( 'word_key, word_default, word_js', 'core_sys_lang_words', array( 'word_app=? AND lang_id=?', $this->directory, $languageId ) ) ) as $word )
			{
				$current[ $languageId ][ $word['word_key'] . '-.-' . $word['word_js'] ] = $word['word_default'];
			}
		}

		if ( !$offset and file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/installLang.json" ) )
		{
			$inserts = array();
			foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/installLang.json" ), TRUE ) as $key => $default )
			{
				foreach( $languages as $languageId )
				{
					if ( !isset( $current[ $languageId ][ $key . '-.-0' ] ) )
					{
						$inserts[]	= array(
							'word_app'				=> $this->directory,
							'word_key'				=> $key,
							'lang_id'				=> $languageId,
							'word_default'			=> $default,
							'word_custom'			=> $default,
							'word_default_version'	=> $this->long_version,
							'word_custom_version'	=> $this->long_version,
							'word_js'				=> 0,
							'word_export'			=> 0,
						);
					}
				}
			}
			
			if ( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
			}
		}
		
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/lang.xml" ) )
		{			
			/* Open XML file */
			$xml = new \XMLReader;
			$xml->open( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/lang.xml" );
			$xml->read();

			/* Get the version */
			$xml->read();
			$xml->read();
			$version	= $xml->getAttribute('version');

			/* Get all installed languages */
			$inserts	 = array();
			$batchSize   = 25;
			$batchesDone = 0;
			$i           = 0;
			
			/* Try to prevent timeouts to the extent possible */
			$cutOff			= null;

			if( $maxExecution = @ini_get( 'max_execution_time' ) )
			{
				/* If max_execution_time is set to "no limit" we should add a hard limit to prevent browser timeouts */
				if ( $maxExecution == -1 )
				{
					$maxExecution = 30;
				}

				$cutOff	= time() + ( $maxExecution * .5 );
			}

			/* Start looping through each word */
			while ( $xml->read() )
			{
				if( $xml->name != 'word' OR $xml->nodeType != \XMLReader::ELEMENT )
				{
					continue;
				}

				if( $cutOff !== null AND time() >= $cutOff )
				{
					return $inserted;
				}
				
				$i++;
				
				if ( $offset !== null )
				{
					if ( $i - 1 < $offset )
					{
						$xml->next();
						continue;
					}
				}

				$inserted++;
				
				$key = $xml->getAttribute('key');
				$value = $xml->readString();
				foreach( $languages as $languageId )
				{
					if ( !isset( $current[ $languageId ][ $key . '-.-' . (int) $xml->getAttribute('js') ] ) or $current[ $languageId ][ $key . '-.-' . (int) $xml->getAttribute('js') ] != $value )
					{
						$inserts[]	= array(
							'word_app'				=> $this->directory,
							'word_key'				=> $key,
							'lang_id'				=> $languageId,
							'word_default'			=> $value,
							'word_default_version'	=> $version,
							'word_js'				=> (int) $xml->getAttribute('js'),
							'word_export'			=> 1,
						);
					}
				}
				
				$done = ( $limit !== null AND $i === ( $limit + $offset ) );
				
				if ( $done OR $i % $batchSize === 0 )
				{
					if ( count( $inserts ) )
					{
						\IPS\Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
						$inserts = array();
					}
					$batchesDone++;
				}
				
				if ( $done )
				{
					break;
				}
				
				$xml->next();
			}
			
			if ( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_sys_lang_words', $inserts, TRUE );
			}
		}

		return $inserted;
	}

	/**
	 * Install the application's email templates
	 *
	 * @return	void
	 */
	public function installEmailTemplates()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/emails.xml" ) )
		{
			/* First, delete any existing non-customized email templates for this app */
			\IPS\Db::i()->delete( 'core_email_templates', array( 'template_app=? AND template_parent=0', $this->directory ) );

			/* Open XML file */
			$xml = new \XMLReader;
			$xml->open( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/emails.xml" );
			$xml->read();

			/* Start looping through each word */
			while ( $xml->read() and $xml->name == 'template' )
			{
				if( $xml->nodeType != \XMLReader::ELEMENT )
				{
					continue;
				}

				$insert	= array(
					'template_parent'	=> 0,
					'template_app'		=> $this->directory,
					'template_edited'	=> 0,
				);

				while ( $xml->read() and $xml->name != 'template' )
				{
					if( $xml->nodeType != \XMLReader::ELEMENT )
					{
						continue;
					}

					switch( $xml->name )
					{
						case 'template_name':
							$insert['template_name']				= $xml->readString();
							$insert['template_key']					= md5( $this->directory . ';' . $insert['template_name'] );
						break;

						case 'template_data':
							$insert['template_data']				= $xml->readString();
						break;

						case 'template_content_html':
							$insert['template_content_html']		= $xml->readString();
						break;

						case 'template_content_plaintext':
							$insert['template_content_plaintext']	= $xml->readString();
						break;
					}
				}

				\IPS\Db::i()->replace( 'core_email_templates', $insert );
			}

			/* Now re-associate customized email templates */
			foreach( \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_app=? AND template_parent>0', $this->directory ) ) as $template )
			{
				/* Find the real parent now */
				try
				{
					$parent = \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_app=? and template_name=? and template_parent=0', $template['template_app'], $template['template_name'] ) )->first();

					/* And now update this template */
					\IPS\Db::i()->update( 'core_email_templates', array( 'template_parent' => $parent['template_id'] ), array( 'template_id=?', $template['template_id'] ) );
					\IPS\Db::i()->update( 'core_email_templates', array( 'template_edited' => 1 ), array( 'template_id=?', $parent['template_id'] ) );
				}
				catch( \UnderflowException $ex ) { }
			}

			\IPS\Data\Cache::i()->clearAll();
			\IPS\Data\Store::i()->clearAll();
		}
	}

	/**
	 * Install the application's skin templates, CSS files and resources
	 *
	 * @param	bool	$update		If set to true, do not overwrite current theme setting values
	 * @return	void
	 */
	public function installSkins( $update=FALSE )
	{
		/* Clear old caches */
		\IPS\Data\Cache::i()->clearAll();
		\IPS\Data\Store::i()->clearAll();

		/* Install the stuff */
		$this->installThemeSettings( $update );
		$this->clearTemplates();
		$this->installTemplates( $update );
	}

	/**
	 * Install the application's theme settings
	 *
	 * @param	bool	$update		If set to true, do not overwrite current theme setting values
	 * @return	void
	 */
	public function installThemeSettings( $update=FALSE )
	{
		if ( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/themesettings.json" ) )
		{
			unset( \IPS\Data\Store::i()->themes );
			$currentSettings	= iterator_to_array( \IPS\Db::i()->select( '*', 'core_theme_settings_fields', array( 'sc_set_id=? AND sc_app=?', \IPS\Theme::defaultTheme(), $this->directory ) )->setKeyField('sc_key') );
			$json				= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/themesettings.json" ), TRUE );
			
			/* Add */
			foreach( $json as $key => $data)
			{
				$insertedSetting = FALSE;
				
				if ( ! isset( $currentSettings[ $data['sc_key'] ] ) )
				{
					$insertedSetting = TRUE;
					
					$currentId = \IPS\Db::i()->insert( 'core_theme_settings_fields', array(
						'sc_set_id'		 => \IPS\Theme::defaultTheme(),
						'sc_key'		 => $data['sc_key'],
						'sc_tab_key'	 => $data['sc_tab_key'],
						'sc_type'		 => $data['sc_type'],
						'sc_multiple'	 => $data['sc_multiple'],
						'sc_default'	 => $data['sc_default'],
						'sc_content'	 => $data['sc_content'],
						'sc_show_in_vse' => ( isset( $data['sc_show_in_vse'] ) ) ? $data['sc_show_in_vse'] : 0,
						'sc_updated'	 => time(),
						'sc_app'		 => $this->directory,
						'sc_title'		 => $data['sc_title'],
						'sc_order'		 => $data['sc_order'],
						'sc_condition'	 => $data['sc_condition'],
					) );
					
					$currentSettings[ $data['sc_key'] ] = $data;
				}
				else
				{
					/* Update */
					\IPS\Db::i()->update( 'core_theme_settings_fields', array(
						'sc_tab_key'	 => $data['sc_tab_key'],
						'sc_type'		 => $data['sc_type'],
						'sc_multiple'	 => $data['sc_multiple'],
						'sc_default'	 => $data['sc_default'],
						'sc_show_in_vse' => ( isset( $data['sc_show_in_vse'] ) ) ? $data['sc_show_in_vse'] : 0,
						'sc_content'	 => $data['sc_content'],
						'sc_title'		 => $data['sc_title'],
						'sc_order'		 => $data['sc_order'],
						'sc_condition'	 => $data['sc_condition'],
					), array( 'sc_set_id=? AND sc_key=? AND sc_app=?', \IPS\Theme::defaultTheme(), $data['sc_key'], $this->directory ) );
			
					$currentId = $currentSettings[ $data['sc_key'] ]['sc_id'];
				}

				/* Are we updating the value? */
				if( $update === FALSE OR $insertedSetting === TRUE )
				{
					\IPS\Db::i()->delete('core_theme_settings_values', array('sv_id=?', $currentId ) );
					\IPS\Db::i()->insert('core_theme_settings_values', array( 'sv_id' => $currentId, 'sv_value' => (string)$data['sc_default'] ) );
				}
			}
		
			if ( $update )
			{
				$defaultCurrentSettings = $currentSettings;
				foreach( \IPS\Theme::themes() as $theme )
				{
					if ( $theme->id == \IPS\Theme::defaultTheme() )
					{
						$currentSettings = $defaultCurrentSettings;
					}
					else
					{
						$currentSettings = iterator_to_array( \IPS\Db::i()->select( '*', 'core_theme_settings_fields', array( 'sc_set_id=?', $theme->id ) )->setKeyField('sc_key') );
					}
					
					$added           = FALSE;
					$save            = json_decode( $theme->template_settings, TRUE );

					/* Add */
					foreach( $json as $key => $data )
					{
						if ( ! isset( $currentSettings[ $data['sc_key'] ] ) )
						{
							$added = TRUE;
							$save[ $data['sc_key'] ] = $data['sc_default'];

							\IPS\Db::i()->insert( 'core_theme_settings_fields', array(
								'sc_set_id'		 => $theme->id,
								'sc_key'		 => $data['sc_key'],
								'sc_tab_key'	 => $data['sc_tab_key'],
								'sc_type'		 => $data['sc_type'],
								'sc_multiple'	 => $data['sc_multiple'],
								'sc_default'	 => $data['sc_default'],
								'sc_content'	 => $data['sc_content'],
								'sc_show_in_vse' => ( isset( $data['sc_show_in_vse'] ) ) ? $data['sc_show_in_vse'] : 0,
								'sc_updated'	 => time(),
								'sc_app'		 => $this->directory,
								'sc_title'		 => $data['sc_title'],
								'sc_order'		 => $data['sc_order'],
								'sc_condition'	 => $data['sc_condition'],
							) );
						}
						else
						{
							/* Update */
							\IPS\Db::i()->update( 'core_theme_settings_fields', array(
								'sc_type'		 => $data['sc_type'],
								'sc_multiple'	 => $data['sc_multiple'],
								'sc_default'	 => $data['sc_default'],
								'sc_show_in_vse' => ( isset( $data['sc_show_in_vse'] ) ) ? $data['sc_show_in_vse'] : 0,
								'sc_content'	 => $data['sc_content'],
								'sc_title'		 => $data['sc_title'],
								'sc_condition'	 => $data['sc_condition'],
							), array( 'sc_set_id=? AND sc_key=?', $theme->id, $data['sc_key'] ) );
							
							$currentId = $currentSettings[ $data['sc_key'] ]['sc_id'];
							
							try
							{
								$currentValue = \IPS\Db::i()->select( 'sv_value', 'core_theme_settings_values', array( array( 'sv_id=?', $currentId ) ) )->first();
							}
							catch( \UnderFlowException $ex )
							{
								$currentValue = $currentSettings[ $data['sc_key'] ]['sc_default'];
							}
							
							/* Are we using the existing default? If so, update it */
							if ( ( $data['sc_default'] != $currentSettings[ $data['sc_key'] ]['sc_default'] ) and ( $currentValue == $currentSettings[ $data['sc_key'] ]['sc_default'] ) )
							{
								$added = TRUE;
								$save[ $data['sc_key'] ] = $data['sc_default'];
							
								\IPS\Db::i()->delete('core_theme_settings_values', array('sv_id=?', $currentId ) );
								\IPS\Db::i()->insert('core_theme_settings_values', array( 'sv_id' => $currentId, 'sv_value' => (string)$data['sc_default'] ) );
							}
						}
					}

					if ( $added )
					{
						$theme->template_settings = json_encode( $save );
						$theme->save();
					}
				}
			}
		}
	}

	/**
	 * Clear out existing templates before installing new ones
	 *	
	 * @return	void
	 */
	public function clearTemplates()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/theme.xml" ) )
		{
			unset( \IPS\Data\Store::i()->themes );
			\IPS\Theme::removeTemplates( $this->directory );
			\IPS\Theme::removeCss( $this->directory );
			\IPS\Theme::removeResources( $this->directory );
		}
	}

	/**
	 * Install the application's templates
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	bool		$update	If set to true, do not overwrite current theme setting values
	 * @param	int|null	$offset Offset to begin import from
	 * @param	int|null	$limit	Number of rows to import	
	 * @return	int			Rows inserted
	 */
	public function installTemplates( $update=FALSE, $offset=null, $limit=null )
	{
		$i			= 0;
		$inserted	= 0;
		
		if ( \IPS\Dispatcher::hasInstance() AND class_exists( '\IPS\Dispatcher', FALSE ) and \IPS\Dispatcher::i()->controllerLocation === 'setup' )
		{
			$class = '\IPS\Theme';
		}
		else
		{
			$class = ( \IPS\Theme::designersModeEnabled() ) ? '\IPS\Theme\Advanced\Theme'  : '\IPS\Theme';
		}
		
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/theme.xml" ) )
		{
			unset( \IPS\Data\Store::i()->themes );
			
			/* Try to prevent timeouts to the extent possible */
			$cutOff			= null;

			if( $maxExecution = @ini_get( 'max_execution_time' ) )
			{
				/* If max_execution_time is set to "no limit" we should add a hard limit to prevent browser timeouts */
				if ( $maxExecution == -1 )
				{
					$maxExecution = 30;
				}
				
				$cutOff	= time() + ( $maxExecution * .5 );
			}

			/* Open XML file */
			$xml = new \XMLReader;
			$xml->open( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/theme.xml" );
			$xml->read();

			while( $xml->read() )
			{
				if( $xml->nodeType != \XMLReader::ELEMENT )
				{
					continue;
				}

				if( $cutOff !== null AND time() >= $cutOff )
				{
					break;
				}

				$i++;

				if ( $offset !== null )
				{
					if ( $i - 1 < $offset )
					{
						$xml->next();
						continue;
					}
				}

				$inserted++;

				if( $xml->name == 'template' )
				{
					$template	= array(
						'app'		=> $this->directory,
						'group'		=> $xml->getAttribute('template_group'),
						'name'		=> $xml->getAttribute('template_name'),
						'variables'	=> $xml->getAttribute('template_data'),
						'content'	=> $xml->readString(),
						'location'	=> $xml->getAttribute('template_location'),
						'_default_template' => true
					);

					try
					{
						$class::addTemplate( $template );
					}
					catch( \OverflowException $e )
					{
						if ( ! $update )
						{
							throw $e;
						}
					}
				}
				else if( $xml->name == 'css' )
				{
					$css	= array(
						'app'		=> $this->directory,
						'location'	=> $xml->getAttribute('css_location'),
						'path'		=> $xml->getAttribute('css_path'),
						'name'		=> $xml->getAttribute('css_name'),
						'content'	=> $xml->readString(),
						'_default_template' => true
					);

					try
					{
						$class::addCss( $css );
					}
					catch( \OverflowException $e )
					{
						if( ! $update )
						{
							throw $e;
						}
					}
				}
				else if( $xml->name == 'resource' )
				{
					$resource	= array(
						'app'		=> $this->directory,
						'location'	=> $xml->getAttribute('location'),
						'path'		=> $xml->getAttribute('path'),
						'name'		=> $xml->getAttribute('name'),
						'content'	=> base64_decode( $xml->readString() ),
					);

					$class::addResource( $resource, TRUE );
				}

				if( $limit !== null AND $i === ( $limit + $offset ) )
				{
					break;
				}
			}
		}

		return $inserted;
	}
	
	/**
	 * Install the application's javascript
	 *
	 * @param	int|null	$offset Offset to begin import from
	 * @param	int|null	$limit	Number of rows to import	
	 * @return	int			Rows inserted
	 */
	public function installJavascript( $offset=null, $limit=null )
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/javascript.xml" ) )
		{
			return \IPS\Output\Javascript::importXml( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/javascript.xml", $offset, $limit );
		}
	}
	
	/**
	 * Install the application's ACP search keywords
	 *
	 * @return	void
	 */
	public function installSearchKeywords()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/acpsearch.json" ) )
		{
			\IPS\Db::i()->delete( 'core_acp_search_index', array( 'app=?', $this->directory ) );
			
			$inserts	= array();
			$maxInserts	= 50;

			foreach( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/acpsearch.json" ), TRUE ) as $url => $data )
			{
				foreach ( $data['keywords'] as $word )
				{
					$inserts[] = array(
						'url'			=> $url,
						'keyword'		=> $word,
						'app'			=> $this->directory,
						'lang_key'		=> $data['lang_key'],
						'restriction'	=> $data['restriction'] ?: NULL
					);

					if( count( $inserts ) >= $maxInserts )
					{
						\IPS\Db::i()->insert( 'core_acp_search_index', $inserts );
						$inserts = array();
					}
				}
			}
			
			if( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_acp_search_index', $inserts );
			}
		}
	}
	
	/**
	 * Install hooks
	 *
	 * @return	void
	 */
	public function installHooks()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/hooks.json" ) )
		{
			\IPS\Db::i()->delete( 'core_hooks', array( 'app=?', $this->directory ) );
			
			$inserts = array();
			$templatesToRecompile = array();
			foreach( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/hooks.json" ), TRUE ) as $filename => $data )
			{
				$inserts[] = array(
					'app'			=> $this->directory,
					'type'			=> $data['type'],
					'class'			=> $data['class'],
					'filename'		=> $filename
				);
				
				if ( $data['type'] === 'S' )
				{
					$templatesToRecompile[ $data['class'] ] = $data['class'];
				}
			}
			
			if( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_hooks', $inserts );
			}
			
			\IPS\Plugin\Hook::writeDataFile();
			
			foreach ( $templatesToRecompile as $k )
			{
				$exploded = explode( '_', $k );
				\IPS\Theme::deleteCompiledTemplate( $exploded[1], $exploded[2], $exploded[3] );
			}
		}
	}
	
	/**
	 * Install the application's widgets
	 *
	 * @return	void
	 */
	public function installWidgets()
	{
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/widgets.json" ) )
		{
			$currentWidgets = iterator_to_array( \IPS\Db::i()->select( '`key`', 'core_widgets', array( 'app=?', $this->directory ) ) );
	
			$inserts = array();
			foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/widgets.json" ), TRUE ) as $key => $json )
			{
				if ( ! in_array( $key, $currentWidgets ) )
				{
					$inserts[] = array(
							'app'		   => $this->directory,
							'key'		   => $key,
							'class'		   => $json['class'],
							'restrict'     => json_encode( $json['restrict'] ),
							'default_area' => ( isset( $json['default_area'] ) ? $json['default_area'] : NULL ),
							'allow_reuse'  => ( isset( $json['allow_reuse'] ) ? $json['allow_reuse'] : 0 ),
							'menu_style'   => ( isset( $json['menu_style'] ) ? $json['menu_style'] : 'menu' ),
							'embeddable'   => ( isset( $json['embeddable'] ) ? $json['embeddable'] : 0 ),
					);
				}
			}
			
			if( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_widgets', $inserts, TRUE );
				unset( \IPS\Data\Store::i()->widgets );
			}
		}
	}

	/**
	 * Install 'other' items. Left blank here so that application classes can override for app
	 *  specific installation needs. Always run as the last step.
	 *
	 * @return void
	 */
	public function installOther()
	{

	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array(),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
	
	/**
	 * Database check
	 *
	 * @return	array	Queries needed to correct database in the following format ( table => x, query = x );
	 */
	public function databaseCheck()
	{
		$db = \IPS\Db::i();
		$changesToMake = array();
		
		/* Loop the tables in the schema */
		foreach( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/schema.json" ), TRUE ) as $tableName => $tableDefinition )
		{
			$tableChanges	= array();
			$needIgnore		= false;

			/* Get our local definition of this table */
			try
			{
				$localDefinition	= \IPS\Db::i()->getTableDefinition( $tableName );
				$localDefinition	= \IPS\Db::i()->normalizeDefinition( $localDefinition );
				$compareDefinition	= \IPS\Db::i()->normalizeDefinition( $tableDefinition );
				$tableDefinition	= \IPS\Db::i()->updateDefinitionIndexLengths( $tableDefinition );

				if ( $compareDefinition != $localDefinition )
				{
					/* Normalise it a little to prevent unnecessary conflicts */
					foreach ( $tableDefinition['columns'] as $k => $c )
					{
						foreach ( array( 'length', 'decimals' ) as $i )
						{
							if ( isset( $c[ $i ] ) )
							{
								$tableDefinition['columns'][ $k ][ $i ] = intval( $c[ $i ] );
							}
							else
							{
								$tableDefinition['columns'][ $k ][ $i ] = NULL;
							}
						}
						
						if ( !isset( $c['values'] ) )
						{
							$tableDefinition['columns'][ $k ]['values'] = array();
						}
						
						if ( $c['type'] === 'BIT' )
						{
							$tableDefinition['columns'][ $k ]['default'] = ( is_null($c['default']) ) ? NULL : "b'{$c['default']}'";
						}
						
						ksort( $tableDefinition['columns'][ $k ] );
					}
					
					$dropped = array();

					/* Loop the columns */
					foreach ( $tableDefinition['columns'] as $columnName => $columnData )
					{
						/* If it doesn't exist in the local database, create it */
						if ( !isset( $localDefinition['columns'][ $columnName ] ) )
						{
							$tableChanges[] = "ADD COLUMN {$db->compileColumnDefinition( $columnData )}";
						}
						/* Or if it's wrong, change it */
						elseif ( $columnData != $localDefinition['columns'][ $columnName ] )
						{
							/* First check indexes to see if any need to be adjusted */
							foreach( $localDefinition['indexes'] as $indexName => $indexData )
							{
								/* We skip the primary key as it can cause errors related to auto-increment */
								if( $indexName == 'PRIMARY' )
								{
									if ( isset( $tableDefinition['columns'][ $indexData['columns'][0] ] ) and isset( $tableDefinition['columns'][ $indexData['columns'][0] ]['auto_increment'] ) and $tableDefinition['columns'][ $indexData['columns'][0] ]['auto_increment'] === TRUE )
									{
										continue;
									}
								}

								foreach( $indexData['columns'] as $indexColumn )
								{
									/* If the column we are about to adjust is included in this index, see if it needs adjusting */
									if( $indexColumn == $columnName AND !in_array( $indexName, $dropped ) )
									{
										$thisIndex = $db->updateDefinitionIndexLengths( $compareDefinition );

										if( !isset( $thisIndex['indexes'][ $indexName ] ) )
										{
											$tableChanges[] = "DROP INDEX `{$db->escape_string( $indexName )}`";
											$dropped[]		= $indexName;
										}
										elseif( $thisIndex['indexes'][ $indexName ] !== $localDefinition['indexes'][ $indexName ] )
										{
											$tableChanges[] = "DROP INDEX `{$db->escape_string( $indexName )}`";
											$tableChanges[] = "ADD {$db->compileIndexDefinition( $thisIndex['indexes'][ $indexName ] )}";
											$dropped[]		= $indexName;

											if( $tableDefinition['indexes'][ $indexName ]['type'] == 'unique' OR $tableDefinition['indexes'][ $indexName ]['type'] == 'primary' )
											{
												$needIgnore = TRUE;
											}
										}
									}
								}
							}

							/* If we are about to adjust the column to not allow NULL values then adjust those values first... */
							if( isset( $columnData['allow_null'] ) and $columnData['allow_null'] === FALSE )
							{
								$defaultValue = "''";
								
								/* Default value */
								if( isset( $columnData['default'] ) and !in_array( \strtoupper( $columnData['type'] ), array( 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BLOB', 'MEDIUMBLOB', 'BIGBLOB', 'LONGBLOB' ) ) )
								{
									if( $columnData['type'] == 'BIT' )
									{
										$defaultValue = "{$columnData['default']}";
									}
									else
									{
										$defaultValue = in_array( $columnData['type'], array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC' ) ) ? floatval( $columnData['default'] ) : ( ! in_array( $columnData['default'], array( 'CURRENT_TIMESTAMP', 'BIT' ) ) ? '\'' . $db->escape_string( $columnData['default'] ) . '\'' : $columnData['default'] );
									}
								}

								$changesToMake[] = array( 'table' => $tableName, 'query' => "UPDATE `{$db->prefix}{$db->escape_string( $tableName )}` SET `{$db->escape_string( $columnName )}`={$defaultValue} WHERE `{$db->escape_string( $columnName )}` IS NULL;" );
							}

							$tableChanges[] = "CHANGE COLUMN `{$db->escape_string( $columnName )}` {$db->compileColumnDefinition( $columnData )}";
						}
					}
					
					/* Loop the index */
					foreach ( $compareDefinition['indexes'] as $indexName => $indexData )
					{
						if( in_array( $indexName, $dropped ) )
						{
							continue;
						}

						if ( !isset( $localDefinition['indexes'][ $indexName ] ) )
						{
							$tableChanges[] = "{$db->buildIndex( $tableName, $tableDefinition['indexes'][ $indexName ] )}";

							if( $tableDefinition['indexes'][ $indexName ]['type'] == 'unique' OR $tableDefinition['indexes'][ $indexName ]['type'] == 'primary' )
							{
								$needIgnore = TRUE;
							}
						}
						elseif ( $indexData != $localDefinition['indexes'][ $indexName ] )
						{
							$tableChanges[] = ( ( $indexName == 'PRIMARY KEY' ) ? "DROP " . $indexName . ", " : "DROP INDEX `" . $db->escape_string( $indexName ) . "`, " ) . $db->buildIndex( $tableName, $tableDefinition['indexes'][ $indexName ] );

							if( $tableDefinition['indexes'][ $indexName ]['type'] == 'unique' OR $tableDefinition['indexes'][ $indexName ]['type'] == 'primary' )
							{
								$needIgnore = TRUE;
							}
						}
					}
				}

				if( count( $tableChanges ) )
				{
					if( $needIgnore )
					{
						$changesToMake[] = array( 'table' => $tableName, 'query' => "CREATE TABLE `{$db->prefix}{$db->escape_string( $tableName )}_new` LIKE `{$db->prefix}{$db->escape_string( $tableName )}`;" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "ALTER TABLE `{$db->prefix}{$db->escape_string( $tableName )}_new` " . implode( ", ", $tableChanges ) . ";" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "INSERT IGNORE INTO `{$db->prefix}{$db->escape_string( $tableName )}_new` SELECT * FROM `{$db->prefix}{$db->escape_string( $tableName )}`;" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "DROP TABLE `{$db->prefix}{$db->escape_string( $tableName )}`;" );
						$changesToMake[] = array( 'table' => $tableName, 'query' => "RENAME TABLE `{$db->prefix}{$db->escape_string( $tableName )}_new` TO `{$db->prefix}{$db->escape_string( $tableName )}`;" );
					}
					else
					{
						$changesToMake[] = array( 'table' => $tableName, 'query' => "ALTER TABLE `{$db->prefix}{$db->escape_string( $tableName )}` " . implode( ", ", $tableChanges ) . ";" );
					}
				}
			}
			/* If the table doesn't exist, create it */
			catch ( \OutOfRangeException $e )
			{
				$changesToMake[] = array( 'table' => $tableName, 'query' => $db->_createTableQuery( $tableDefinition ) );
			}
		}
		
		/* And loop any install routine for columns added to other tables */
		if ( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/install/queries.json" ) )
		{
			foreach( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/install/queries.json" ), TRUE ) as $query )
			{
				switch ( $query['method'] )
				{
					/* Add column */
					case 'addColumn':
						$localDefinition = \IPS\Db::i()->getTableDefinition( $query['params'][0] );
						if ( !isset( $localDefinition['columns'][ $query['params'][1]['name'] ] ) )
						{
							$changesToMake[] = array( 'table' => $query['params'][0], 'query' => "ALTER TABLE `{$db->prefix}{$query['params'][0]}` ADD COLUMN {$db->compileColumnDefinition( $query['params'][1] )}" );
						}
						else
						{
							$correctDefinition = $db->compileColumnDefinition( $query['params'][1] );
							if ( $correctDefinition != $db->compileColumnDefinition( $localDefinition['columns'][ $query['params'][1]['name'] ] ) )
							{
								$tableChanges[] = array( 'table' => $query['params'][0], 'query' => "ALTER TABLE `{$db->prefix}{$query['params'][0]}` CHANGE COLUMN `{$db->escape_string( $columnName )}` {$db->compileColumnDefinition( $columnData )}" );
							}
						}
						break;
				}
			}
		}
		
		/* Return */
		return $changesToMake;
	}
	
	/**
	 * Build application for release
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function build()
	{
		/* Write the application data to the application.json file */
		$applicationData	= array(
			'application_title'	=> \IPS\Member::loggedIn()->language()->get('__app_' . $this->directory ),
			'app_author'		=> $this->author,
			'app_directory'		=> $this->directory,
			'app_protected'		=> $this->protected,
			'app_website'		=> $this->website,
			'app_update_check'	=> $this->update_check,
		);
		
		\IPS\Application::writeJson( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/application.json', $applicationData );

		/* Update app version data */
		$versions		= $this->getAllVersions();
		$longVersions	= array_keys( $versions );
		$humanVersions	= array_values( $versions );

		if( count($versions) )
		{
			$latestLVersion	= array_pop( $longVersions );
			$latestHVersion	= array_pop( $humanVersions );

			\IPS\Db::i()->update( 'core_applications', array( 'app_version' => $latestHVersion, 'app_long_version' => $latestLVersion ), array( 'app_directory=?', $this->directory ) );

			$this->long_version = $latestLVersion;
			$this->version		= $latestHVersion;
		}

		/* Take care of languages for this app */
		$this->buildLanguages();
		$this->installLanguages();

		/* Take care of skins for this app */
		$this->buildThemeTemplates();
		$this->installSkins( TRUE );

		/* Take care of emails for this app */
		$this->buildEmailTemplates();
		$this->installEmailTemplates();
		
		/* Take care of javascript for this app */
		$this->buildJavascript();
		$this->installJavascript();
		
		/* Take care of hooks for this app */
		$this->buildHooks();

		foreach( $this->extensions( 'core', 'Build' ) as $builder )
		{
			$builder->build();
		}
	}

	/**
	 * Build skin templates for an app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildThemeTemplates()
	{
		/* Delete compiled items */
		\IPS\Theme::deleteCompiledTemplate( $this->directory );
		\IPS\Theme::deleteCompiledCss( $this->directory );
		\IPS\Theme::deleteCompiledResources( $this->directory );
		
		\IPS\Theme::i()->importDevHtml( $this->directory, 0 );
		\IPS\Theme::i()->importDevCss( $this->directory, 0 );

		/* Build XML and write to app directory */
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );
		
		/* Root tag */
		$xml->startElement('theme');
		$xml->startAttribute('name');
		$xml->text( "Default" );
		$xml->endAttribute();
		$xml->startAttribute('author_name');
		$xml->text( "Invision Power Services, Inc" );
		$xml->endAttribute();
		$xml->startAttribute('author_url');
		$xml->text( "http://www.invisionpower.com" );
		$xml->endAttribute();
		
		/* Skin settings */
		foreach (
			\IPS\Db::i()->select(
				'core_theme_settings_fields.*',
				'core_theme_settings_fields',
				array( 'sc_set_id=? AND sc_app=?', 1, $this->directory ), 
				'sc_key ASC'
			)
			as $row
		)
		{
			/* Initiate the <fields> tag */
			$xml->startElement('field');
			
			unset( $row['sc_id'], $row['sc_set_id'] );
			
			foreach( $row as $k => $v )
			{
				if ( $k != 'sc_content' )
				{
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}
			
			/* Write value */
			if ( preg_match( '/<|>|&/', $row['sc_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row['sc_content'] ) );
			}
			else
			{
				$xml->text( $row['sc_content'] );
			}
			
			/* Close the <fields> tag */
			$xml->endElement();
		}
		
		/* Templates */
		foreach ( \IPS\Db::i()->select( '*', 'core_theme_templates', array( 'template_set_id=? AND template_user_added=? AND template_app=?', 0, 0 , $this->directory ), 'template_group, template_name, template_location' ) as $template )
		{
			/* Initiate the <template> tag */
			$xml->startElement('template');
			
			foreach( $template as $k => $v )
			{
				if ( in_array( \substr( $k, 9 ), array('app', 'location', 'group', 'name', 'data' ) ) )
				{
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}
			
			/* Write value */
			if ( preg_match( '/<|>|&/', $template['template_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $template['template_content'] ) );
			}
			else
			{
				$xml->text( $template['template_content'] );
			}
			
			/* Close the <template> tag */
			$xml->endElement();
		}

		/* Css */
		foreach ( \IPS\Db::i()->select( '*', 'core_theme_css', array( 'css_set_id=? AND css_added_to=? AND css_app=?', 0, 0 , $this->directory ), 'css_path, css_name, css_location' ) as $css )
		{
			$xml->startElement('css');

			foreach( $css as $k => $v )
			{
				if ( in_array( \substr( $k, 4 ), array('app', 'location', 'path', 'name', 'attributes' ) ) )
				{
					$xml->startAttribute( $k );
					$xml->text( $v );
					$xml->endAttribute();
				}
			}

			/* Write value */
			if ( preg_match( '/<|>|&/', $css['css_content'] ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $css['css_content'] ) );
			}
			else
			{
				$xml->text( $css['css_content'] );
			}
			
			$xml->endElement();
		}
		
		/* Resources */
		$_resources	= $this->_buildThemeResources();
		
		foreach ( $_resources as $data )
		{
			$xml->startElement('resource');
					
			$xml->startAttribute('name');
			$xml->text( $data['resource_name'] );
			$xml->endAttribute();
			
			$xml->startAttribute('app');
			$xml->text( $data['resource_app'] );
			$xml->endAttribute();
			
			$xml->startAttribute('location');
			$xml->text( $data['resource_location'] );
			$xml->endAttribute();
			
			$xml->startAttribute('path');
			$xml->text( $data['resource_path'] );
			$xml->endAttribute();
			
			/* Write value */
			$xml->text( base64_encode( $data['resource_data'] ) );
			
			$xml->endElement();
		}
		
		/* Finish */
		$xml->endDocument();
		
		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/theme.xml', $xml->outputMemory() );
		}
		else
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}
	}

	/**
	 * Build Resources ready for non IN_DEV use
	 *
	 * @return	array
	 */
	protected function _buildThemeResources()
	{
		$resources = array();
		$path	= \IPS\ROOT_PATH . "/applications/" . $this->directory . "/dev/resources/";

		\IPS\Theme::i()->importDevResources( $this->directory, 0 );

		if ( is_dir( $path ) )
		{
			foreach( new \DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDot() || \substr( $location->getFilename(), 0, 1 ) === '.' )
				{
					continue;
				}

				if ( $location->isDir() )
				{
					$resources	= $this->_buildResourcesRecursive( $location->getFilename(), '/', $resources );
				}
			}
		}

		return $resources;
	}
	
	/**
	 * Build Resources ready for non IN_DEV use (Iterable)
	 * Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded.
	 *
	 * @param	string	$location	Location Folder Name
	 * @param	string	$path		Path
	 * @param	array	$resources	Array of resources to append to
	 * @return	array
	 */
	protected function _buildResourcesRecursive( $location, $path='/', $resources=array() )
	{
		$root = \IPS\ROOT_PATH . "/applications/{$this->directory}/dev/resources/{$location}";
	
		foreach( new \DirectoryIterator( $root . $path ) as $file )
		{
			if ( $file->isDot() || \substr( $file->getFilename(), 0, 1 ) === '.' || $file == 'index.html' )
			{
				continue;
			}
	
			if ( $file->isDir() )
			{
				$resources	= $this->_buildResourcesRecursive( $location, $path . $file->getFilename() . '/', $resources );
			}
			else
			{
				$resources[] = array(
					'resource_app'		=> $this->directory,
					'resource_location'	=> $location,
					'resource_path'		=> $path,
					'resource_name'		=> $file->getFilename(),
					'resource_data'		=> \file_get_contents( $root . $path . $file->getFilename() ),
					'resource_added'	=> time()
				);
			}
		}

		return $resources;
	}

	/**
	 * Build languages for an app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildLanguages()
	{
		/* Create the lang.xml file */
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );
				
		/* Root tag */
		$xml->startElement('language');

		/* Initiate the <app> tag */
		$xml->startElement('app');
		
		/* Set key */
		$xml->startAttribute('key');
		$xml->text( $this->directory );
		$xml->endAttribute();
		
		/* Set version */
		$xml->startAttribute('version');
		$xml->text( $this->long_version );
		$xml->endAttribute();
		
		/* Import the language files */
		$lang	= array();

		require \IPS\ROOT_PATH . "/applications/{$this->directory}/dev/lang.php";
		foreach ( $lang as $k => $v )
		{
			/* Start */
			$xml->startElement( 'word' );
			
			/* Add key */
			$xml->startAttribute('key');
			$xml->text( $k );
			$xml->endAttribute();

			/* Add javascript flag */
			$xml->startAttribute('js');
			$xml->text( 0 );
			$xml->endAttribute();
							
			/* Write value */
			if ( preg_match( '/<|>|&/', $v ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $v ) );
			}
			else
			{
				$xml->text( $v );
			}
			
			/* End */
			$xml->endElement();
		}

		$lang	= array();

		require \IPS\ROOT_PATH . "/applications/{$this->directory}/dev/jslang.php";
		foreach ( $lang as $k => $v )
		{
			/* Start */
			$xml->startElement( 'word' );
			
			/* Add key */
			$xml->startAttribute('key');
			$xml->text( $k );
			$xml->endAttribute();

			/* Add javascript flag */
			$xml->startAttribute('js');
			$xml->text( 1 );
			$xml->endAttribute();
							
			/* Write value */
			if ( preg_match( '/<|>|&/', $v ) )
			{
				$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $v ) );
			}
			else
			{
				$xml->text( $v );
			}
			
			/* End */
			$xml->endElement();
		}

		/* Finish */
		$xml->endDocument();
			
		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/lang.xml', $xml->outputMemory() );
		}
		else
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}
	}

	/**
	 * Build email templates for an app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildEmailTemplates()
	{
		/* Where are we looking? */
		$path = \IPS\ROOT_PATH . "/applications/{$this->directory}/dev/email";
		
		/* We create an array and store the templates temporarily so we can merge plaintext and HTML together */
		$templates		= array();
		$templateKeys	= array();

		/* Loop over files in the directory */
		if ( is_dir( $path ) )
		{
			foreach( new \DirectoryIterator( $path ) as $location )
			{
				if ( $location->isDir() and mb_substr( $location, 0, 1 ) !== '.' and ( $location->getFilename() === 'plain' or $location->getFilename() === 'html' ) )
				{
					foreach( new \DirectoryIterator( $path . '/' . $location->getFilename() ) as $sublocation )
					{
						if ( $sublocation->isDir() and mb_substr( $sublocation, 0, 1 ) !== '.' )
						{
							foreach( new \DirectoryIterator( $path . '/' . $location->getFilename() . '/' . $sublocation->getFilename() ) as $file )
							{
								if ( $file->isDot() or !$file->isFile() or mb_substr( $file, 0, 1 ) === '.' or $file->getFilename() === 'index.html' )
								{
									continue;
								}
								
								$data = $this->_buildEmailTemplateFromInDev( $path . '/' . $location->getFilename() . '/' . $sublocation->getFilename(), $file, $sublocation->getFilename() . '__' );
								$extension = mb_substr( $file->getFilename(), mb_strrpos( $file->getFilename(), '.' ) + 1 );
								$type = ( $extension === 'txt' ) ? "plaintext" : "html";
								
								if ( ! isset( $templates[ $data['template_name'] ] ) )
								{
									$templates[ $data['template_name'] ] = array();
								}
				
								$templates[ $data['template_name'] ] = array_merge( $templates[ $data['template_name'] ], $data );
				
								/* Delete the template in the store */
								$key = $templates[ $data['template_name'] ]['template_key'] . '_email_' . $type;
								unset( \IPS\Data\Store::i()->$key );
				
								/* Remember our templates */
								$templateKeys[]	= $data['template_key'];
							}
						}
					}

				}
				else
				{
					if ( $location->isDot() or !$location->isFile() or mb_substr( $location, 0, 1 ) === '.' or $location->getFilename() === 'index.html' )
					{
						continue;
					}
					
					$data = $this->_buildEmailTemplateFromInDev( $path, $location );
					$extension = mb_substr( $location->getFilename(), mb_strrpos( $location->getFilename(), '.' ) + 1 );
					$type = ( $extension === 'txt' ) ? "plaintext" : "html";
					
					if ( ! isset( $templates[ $data['template_name'] ] ) )
					{
						$templates[ $data['template_name'] ]	= array();
					}
	
					$templates[ $data['template_name'] ] = array_merge( $templates[ $data['template_name'] ], $data );
	
					/* Delete the template in the store */
					$key = $templates[ $data['template_name'] ]['template_key'] . '_email_' . $type;
					unset( \IPS\Data\Store::i()->$key );
	
					/* Remember our templates */
					$templateKeys[]	= $data['template_key'];
				}
			}
		}

		/* Clear out invalid templates */
		\IPS\Db::i()->delete( 'core_email_templates', array( "template_app=? AND template_key NOT IN('" . implode( "','", $templateKeys ) . "')", $this->directory ) );

		/* If we have any templates, put them in the database */
		if( count($templates) )
		{
			foreach( $templates as $template )
			{
				\IPS\Db::i()->insert( 'core_email_templates', $template, TRUE );
			}

			/* Build the executable copies */
			$this->parseEmailTemplates();
		}

		$xml = \IPS\Xml\SimpleXML::create('emails');

		/* Templates */
		foreach ( \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_parent=? AND template_app=?', 0, $this->directory ), 'template_key ASC' ) as $template )
		{
			$forXml = array();
			foreach( $template as $k => $v )
			{
				if ( in_array( \substr( $k, 9 ), array('app', 'name', 'content_html', 'data', 'content_plaintext' ) ) )
				{
					$forXml[ $k ] = $v;
				}
			}
			
			$xml->addChild( 'template', $forXml );
		}

		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/emails.xml', $xml->asXML() );
		}
		else
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}
	}
	
	/**
	 * Imports an IN_DEV email template into the database
	 *
	 * @param	string		$path			Path to file
	 * @param	object		$file			DirectoryIterator File Object
	 * @param	string|null	$namePrefix		Name prefix
	 * @return  array
	 */
	protected function _buildEmailTemplateFromInDev( $path, $file, $namePrefix='' )
	{
		/* Get the content */
		$html	= file_get_contents( $path . '/' . $file->getFilename() );
		$params	= array();
		
		/* Parse the header tag */
		preg_match( '/^<ips:template parameters="(.+?)?" \/>(\r\n?|\n)/', $html, $params );
		
		/* Strip the params tag */
		$html	= str_replace( $params[0], '', $html );
		
		/* Figure out some details */
		$extension = mb_substr( $file->getFilename(), mb_strrpos( $file->getFilename(), '.' ) + 1 );
		$name	= $namePrefix . str_replace( '.' . $extension, '', $file->getFilename() );
		$type	= ( $extension === 'txt' ) ? "plaintext" : "html";

		$return = array(
			'template_app'				=> $this->directory,
			'template_name'				=> $name,
			'template_data'				=> ( isset( $params[1] ) ) ? $params[1] : '',
			'template_content_' . $type	=> $html,
			'template_key'				=> md5( $this->directory . ';' . $name ),
		);

		return $return;
	}
	
	/**
	 * Build javascript for this app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildJavascript()
	{
		/* Remove existing file object maps */
		$map = isset( \IPS\Data\Store::i()->javascript_map ) ? \IPS\Data\Store::i()->javascript_map : array();
		$map[ $this->directory ] = array();
		
		\IPS\Data\Store::i()->javascript_map = $map;
		
		$xml = \IPS\Output\Javascript::createXml( $this->directory );
	
		/* Write it */
		if ( is_writable( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data' ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/javascript.xml', $xml->outputMemory() );
		}
		else
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}
	}
	
	/**
	 * Build hooks for an app
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function buildHooks()
	{
		/* Build data */
		$data = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_hooks', array( 'app=?', $this->directory ) ) as $hook )
		{
			$data[ $hook['filename'] ] = array(
				'type'		=> $hook['type'],
				'class'		=> $hook['class'],
			);
		}
				
		/* Write it */
		try
		{
			\IPS\Application::writeJson( \IPS\ROOT_PATH . '/applications/' . $this->directory . '/data/hooks.json', $data );
		}
		catch ( \RuntimeException $e )
		{
			throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack('dev_could_not_write_data') );
		}
	}

	/**
	 * Compile email template into executable template
	 *
	 * @return	void
	 */
	public function parseEmailTemplates()
	{
		foreach( \IPS\Db::i()->select( '*','core_email_templates', NULL, 'template_parent DESC' ) as $template )
		{
			/* Rebuild built copies */
			$htmlFunction	= 'namespace IPS\Theme;' . "\n" . \IPS\Theme::compileTemplate( $template['template_content_html'], "email_html_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );
			$ptFunction		= 'namespace IPS\Theme;' . "\n" . \IPS\Theme::compileTemplate( $template['template_content_plaintext'], "email_plaintext_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );

			$key	= $template['template_key'] . '_email_html';
			\IPS\Data\Store::i()->$key = $htmlFunction;

			$key	= $template['template_key'] . '_email_plaintext';
			\IPS\Data\Store::i()->$key = $ptFunction;
		}
	}
	
	/**
	 * Write JSON file
	 *
	 * @param	string	$file	Filepath
	 * @param	array	$data	Data to write
	 * @return	void
	 * @throws	\RuntimeException	Could not write
	 */
	public static function writeJson( $file, $data )
	{
		/* Format the JSON if we can (JSON_PRETTY_PRINT) is only available on PHP 5.4+ */
		if ( version_compare( PHP_VERSION, '5.4.0' ) >= 0 )
		{
			$json = json_encode( $data, JSON_PRETTY_PRINT );
			
			/* No idea why, but for some people blank structures have line breaks in them and for some people they don't
				which unecessarily makes version control think things have changed - so let's make it the same for everyone */
			$json = preg_replace( '/\[\s*\]/', '[]', $json );
			$json = preg_replace( '/\{\s*\}/', '{}', $json );
		}
		else
		{
			$json = json_encode( $data );
		}
		
		/* Write it */
		if( \file_put_contents( $file, $json ) === FALSE )
		{
			throw new \RuntimeException;
		}
		@chmod( $file, 0777 );
	}

	/**
	 * Can the user access this application?
	 *
	 * @param	\IPS\Member|NULL	$member		Member we are checking against or NULL for currently logged on user
	 * @return	bool
	 */
	public function canAccess( $member=NULL )
	{
		/* If it's not enabled, we can't */
		if( !$this->enabled )
		{
			return FALSE;
		}

		/* If all groups have access, we can */
		if( $this->disabled_groups === NULL )
		{
			return TRUE;
		}
		
		/* If all groups have access, we can */
		if( $this->disabled_groups == '*' )
		{
			return FALSE;
		}

		/* Check member */
		$member	= ( $member === NULL ) ? \IPS\Member::loggedIn() : $member;
		$memberGroups	= array_merge( array( $member->member_group_id ), array_filter( explode( ',', $member->mgroup_others ) ) );
		$accessGroups	= explode( ',', $this->disabled_groups );

		/* Are we in an allowed group? */
		if( count( array_intersect( $accessGroups, $memberGroups ) ) )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * Can manage the widgets
	 *
	 * @param	\IPS\Member|NULL	$member		Member we are checking against or NULL for currently logged on user
	 * @return 	boolean
	 */
	public function canManageWidgets( $member=NULL )
	{
		/* Check member */
		$member	= ( $member === NULL ) ? \IPS\Member::loggedIn() : $member;
		
		return $member->modPermission('can_manage_sidebar');
	}
	
	/**
	 * Save Changes
	 *
	 * @param	bool	$skipMember		Skip clearing member cache clearing
	 * @return	void
	 */
	public function save( $skipMember=FALSE )
	{
		parent::save();
		unset( \IPS\Data\Store::i()->applications );
		unset( \IPS\Data\Store::i()->frontNavigation );

		/* Clear out member's cached "Create Menu" contents */
		if( !$skipMember )
		{
			\IPS\Member::clearCreateMenu();
		}
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* Get our uninstall callback script(s) if present. They are stored in an array so that we only create one object per extension, instead of one each time we loop. */
		$uninstallExtensions	= array();
		foreach( $this->extensions( 'core', 'Uninstall', TRUE ) as $extension )
		{
			$uninstallExtensions[]	= $extension;
		}

		/* Call preUninstall() so that application may perform any necessary cleanup before other data is removed (i.e. database tables) */
		foreach( $uninstallExtensions as $extension )
		{
			if( method_exists( $extension, 'preUninstall' ) )
			{
				$extension->preUninstall( $this->directory );
			}
		}

		/* Call onOtherAppUninstall so that other applications may perform any necessary cleanup */
		foreach( static::allExtensions( 'core', 'Uninstall', FALSE ) as $extension )
		{
			if( method_exists( $extension, 'onOtherAppUninstall' ) )
			{
				$extension->onOtherAppUninstall( $this->directory );
			}
		}

		$templatesToRecompile = array();

		/* Note any templates that will need recompiling */
		foreach ( \IPS\Db::i()->select( 'class', 'core_hooks', array( 'app=? AND type=?', $this->directory, 'S' ) ) as $class )
		{
			$templatesToRecompile[ $class ] = $class;
		}
		
		/* Delete menu items */
		\IPS\core\FrontNavigation::deleteByApplication( $this );
		
		/* Delete data from shared tables */
		\IPS\Content\Search\Index::i()->removeApplicationContent( $this );
		\IPS\Db::i()->delete( 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id IN(?)', 'core', 'module', \IPS\Db::i()->select( 'sys_module_id', 'core_modules', array( 'sys_module_application=?', $this->directory ) ) ) );
		\IPS\Db::i()->delete( 'core_modules', array( 'sys_module_application=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_dev', array( 'app_key=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_hooks', array( 'app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_item_markers', array( 'item_app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_permission_index', array( 'app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_upgrade_history', array( 'upgrade_app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_admin_logs', array( 'appcomponent=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_sys_conf_settings', array( 'conf_app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_queue', array( 'app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_follow', array( 'follow_app=?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_view_updates', array( "classname LIKE CONCAT( ?, '%' )", "IPS\\\\{$this->directory}" ) );

		$classes = array();
		foreach( $this->extensions( 'core', 'ContentRouter' ) AS $contentRouter )
		{
			foreach ( $contentRouter->classes as $class )
			{
				$classes[]	= $class;

				if ( isset( $class::$commentClass ) )
				{
					$classes[]	= $class::$commentClass;
				}

				if ( isset( $class::$reviewClass ) )
				{
					$classes[]	= $class::$reviewClass;
				}
			}
		}

		if( count( $classes ) )
		{
			$queueWhere = array();
			$queueWhere[] = array( 'app=?', 'core' );
			$queueWhere[] = array( \IPS\Db::i()->in( '`key`', array( 'rebuildPosts', 'RebuildReputationIndex' ) ) );

			foreach ( \IPS\Db::i()->select( '*', 'core_queue', $queueWhere ) as $queue )
			{
				$queue['data'] = json_decode( $queue['data'], TRUE );
				if( in_array( $queue['data']['class'], $classes ) )
				{
					\IPS\Db::i()->delete( 'core_queue', array( 'id=?', $queue['id'] ) );
				}
			}

			\IPS\Db::i()->delete( 'core_notifications', \IPS\Db::i()->in( 'item_class', $classes ) );
		}

		unset( \IPS\Data\Store::i()->settings );

		/* Delete tasks and task logs */
		\IPS\Db::i()->delete( 'core_tasks_log', array( 'task IN(?)', \IPS\Db::i()->select( 'id', 'core_tasks', array( 'app=?', $this->directory ) ) ) );
		\IPS\Db::i()->delete( 'core_tasks', array( 'app=?', $this->directory ) );

		/* Delete reports */
		\IPS\Db::i()->delete( 'core_rc_reports', array( 'rid IN(?)', \IPS\Db::i()->select('id', 'core_rc_index', \IPS\Db::i()->in( 'class', $classes ) ) ) );
		\IPS\Db::i()->delete( 'core_rc_comments', array( 'rid IN(?)', \IPS\Db::i()->select('id', 'core_rc_index', \IPS\Db::i()->in( 'class', $classes ) ) ) );
		\IPS\Db::i()->delete( 'core_rc_index', \IPS\Db::i()->in('class', $classes) );

		/* Delete language strings */
		\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'word_app=?', $this->directory ) );

		/* Delete email templates */
		$emailTemplates	= \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_app=?', $this->directory ) );

		if( $emailTemplates->count() )
		{
			foreach( $emailTemplates as $template )
			{
				if( $template['template_content_html'] )
				{
					$k = $template['template_key'] . '_email_html';
					unset( \IPS\Data\Store::i()->$k );
				}

				if( $template['template_content_plaintext'] )
				{
					$k = $template['template_key'] . '_email_plaintext';
					unset( \IPS\Data\Store::i()->$k );
				}
			}

			\IPS\Db::i()->delete( 'core_email_templates', array( 'template_app=?', $this->directory ) );
		}

		/* Delete skin template/CSS/etc. */
		\IPS\Theme::removeTemplates( $this->directory, NULL, NULL, NULL, TRUE );
		\IPS\Theme::removeCss( $this->directory, NULL, NULL, NULL, TRUE );
		\IPS\Theme::removeResources( $this->directory, NULL, NULL, NULL, TRUE );
		
		/* Delete theme settings */
		$valueIds = iterator_to_array( \IPS\Db::i()->select( 'sc_id', 'core_theme_settings_fields', array( array( 'sc_app=?', $this->directory ) ) ) );
		
		\IPS\Db::i()->delete( 'core_theme_settings_fields', array( 'sc_app=?', $this->directory ) );
		
		if ( count( $valueIds ) )
		{
			\IPS\Db::i()->delete( 'core_theme_settings_values', array( 'sv_id IN(?)', implode( ',', $valueIds ) ) );
		}
		
		unset( \IPS\Data\Store::i()->themes );
		
		/* Delete any stored files */
		foreach( $this->extensions( 'core', 'FileStorage', TRUE ) as $extension )
		{
			$extension->delete();
		}

		$notificationTypes = array();
		foreach( $this->extensions( 'core', 'Notifications' ) as $key => $class )
		{
			if ( method_exists( $class, 'getConfiguration' ) )
			{
				$defaults = $class->getConfiguration( NULL );

				foreach( $defaults AS $k => $config )
				{
					$notificationTypes[] =  $k;
				}
			}
		}

		if( count( $notificationTypes ) )
		{
			\IPS\Db::i()->delete( 'core_notification_defaults', "notification_key IN('" . implode( "','", $notificationTypes ) . "')");
			\IPS\Db::i()->delete( 'core_notification_preferences', "notification_key IN('" . implode( "','", $notificationTypes ) . "')");
		}

		/* Delete database tables */
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/schema.json" ) )
		{
			$schema	= @json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/data/schema.json" ), TRUE );

			if( is_array( $schema ) AND count( $schema ) )
			{
				foreach( $schema as $tableName => $definition )
				{
					try
					{
						\IPS\Db::i()->dropTable( $tableName, TRUE );
					}
					catch( \IPS\Db\Exception $e )
					{
						/* Ignore "Cannot drop table because it does not exist" */
						if( $e->getCode() <> 1051 )
						{
							throw $e;
						}
					}
				}
			}
		}
		
		/* Revert other database changes performed by installation */
		if( file_exists( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/install/queries.json" ) )
		{
			$schema	= json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$this->directory}/setup/install/queries.json" ), TRUE );

			ksort($schema);

			foreach( $schema as $instruction )
			{
				switch ( $instruction['method'] )
				{
					case 'addColumn':
						try
						{
							\IPS\Db::i()->dropColumn( $instruction['params'][0], $instruction['params'][1]['name'] );
						}
						catch( \IPS\Db\Exception $e )
						{
							/* Ignore "Cannot drop key because it does not exist" */
							if( $e->getCode() <> 1091 )
							{
								throw $e;
							}
						}
					break;

					case 'addIndex':
						try
						{
							\IPS\Db::i()->dropIndex( $instruction['params'][0], $instruction['params'][1]['name'] );
						}
						catch( \IPS\Db\Exception $e )
						{
							/* Ignore "Cannot drop key because it does not exist" */
							if( $e->getCode() <> 1091 )
							{
								throw $e;
							}
						}
					break;
				}
			}
		}

		/* delete widgets */
		\IPS\Db::i()->delete( 'core_widgets', array( 'app = ?', $this->directory ) );
		\IPS\Db::i()->delete( 'core_widget_areas', array( 'app = ?', $this->directory ) );

		/* clean up widget areas table */
		foreach ( \IPS\Db::i()->select( '*', 'core_widget_areas' ) as $row )
		{
			$data = json_decode( $row['widgets'], true );

			foreach ( $data as $key => $widget)
			{
				if ( isset( $widget['app'] ) and $widget['app'] == $this->directory )
				{
					unset( $data[$key]) ;
				}
			}

			\IPS\Db::i()->update( 'core_widget_areas', array( 'widgets' => json_encode( $data ) ), array( 'id=?', $row['id'] ) );
		}
		
		/* Clean up widget trash table */
		$trash = array();
		foreach( \IPS\Db::i()->select( '*', 'core_widget_trash' ) AS $garbage )
		{
			$data = json_decode( $garbage['data'], TRUE );
			
			if ( isset( $data['app'] ) AND $data['app'] == $this->directory )
			{
				$trash[] = $garbage['id'];
			}
		}
		
		\IPS\Db::i()->delete( 'core_widget_trash', \IPS\Db::i()->in( 'id', $trash ) );


		/* Call postUninstall() so that application may perform any necessary cleanup after other data is removed */
		foreach( $uninstallExtensions as $extension )
		{
			if( method_exists( $extension, 'postUninstall' ) )
			{
				$extension->postUninstall( $this->directory );
			}
		}
		
		/* Delete from DB */
		\IPS\File::unclaimAttachments( 'core_Admin', $this->id, NULL, 'appdisabled' );
		parent::delete();

		/* Rebuild hooks file */
		\IPS\Plugin\Hook::writeDataFile();
		foreach ( $templatesToRecompile as $k )
		{
			$exploded = explode( '_', $k );
			\IPS\Theme::deleteCompiledTemplate( $exploded[1], $exploded[2], $exploded[3] );
		}

		/* Clear out member's cached "Create Menu" contents */
		\IPS\Member::clearCreateMenu();
		unset( \IPS\Data\Store::i()->modules );
		unset( \IPS\Data\Store::i()->applications );
		unset( \IPS\Data\Store::i()->settings );
		unset( \IPS\Data\Store::i()->widgets );
	}

	/**
	 * Return an array of version upgrade folders this application contains
	 *
	 * @param	int		$start	If provided, only upgrade steps above this version will be returned
	 * @return	array
	 */
	public function getUpgradeSteps( $start=0 )
	{
		$path	= \IPS\ROOT_PATH . "/applications/{$this->directory}/setup";

		if( !is_dir( $path ) )
		{
			return array();
		}

		$versions	= array();

		foreach( new \DirectoryIterator( $path ) as $file )
		{
			if( $file->isDir() AND !$file->isDot() )
			{
				if( mb_substr( $file->getFilename(), 0, 4 ) == 'upg_' )
				{
					$_version	= intval( mb_substr( $file->getFilename(), 4 ) );

					if( $_version > $start )
					{
						$versions[]	= $_version;
					}
				}
			}
		}

		/* Sort the versions lowest to highest */
		sort( $versions, SORT_NUMERIC );

		return $versions;
	}
	
	/**
	 * Can view page even when user is a guest when guests cannot access the site
	 *
	 * @param	\IPS\Application\Module	$module			The module
	 * @param	string					$controller		The controller
	 * @param	string|NULL				$do				To "do" parameter
	 * @return	bool
	 */
	public function allowGuestAccess( \IPS\Application\Module $module, $controller, $do )
	{
		return FALSE;
	}
}