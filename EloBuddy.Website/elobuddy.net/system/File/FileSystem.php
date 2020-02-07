<?php
/**
 * @brief		File Handler: File System
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		06 May 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\File;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Handler: File System
 */
class _FileSystem extends \IPS\File
{
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param	array	$configuration		Configuration if editing a setting, or array() if creating a setting.
	 * @return	array
	 */
	public static function settings( $configuration=array() )
	{
		$default = ( isset( $configuration['custom_url'] ) and ! empty( $configuration['custom_url'] ) ) ? TRUE : FALSE;
		
		return array(
			'dir'		 => array( 'type' => 'Text', 'default' => '{root}/uploads' ),
			'toggle'	 => array( 'type' => 'YesNo', 'default' => $default, 'options' => array(
				'togglesOn' => array( 'FileSystem_custom_url' )
			) ),
			'custom_url' => array( 'type' => 'Text', 'default' => '' ),
		);
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array	$values	The submitted values
	 * @return	void
	 * @throws	\LogicException
	 */
	public static function testSettings( &$values )
	{
		$values['dir'] = rtrim( $values['dir'], '\\/' );
		$testDir = str_replace( '{root}', \IPS\ROOT_PATH, $values['dir'] );
		$values['url'] = trim( str_replace( \IPS\ROOT_PATH, '', $testDir ), '\\/' );
		
		if ( empty( $values['toggle'] ) )
		{
			$values['custom_url'] = NULL;
		}

		if ( !$testDir )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'dir_not_provided', FALSE ) );
		}
		if ( !is_dir( $testDir ) )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'dir_does_not_exist', FALSE, array( 'sprintf' => array( $testDir ) ) ) );
		}
		if ( !is_writable( $testDir ) )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'dir_is_not_writable', FALSE, array( 'sprintf' => array( $testDir ) ) ) );
		}
		
		if ( ! empty( $values['custom_url'] ) )
		{
			if ( mb_substr( $values['custom_url'], 0, 2 ) !== '//' AND mb_substr( $values['custom_url'], 0, 4 ) !== 'http' )
			{
				$values['custom_url'] = '//' . $values['custom_url'];
			}
			
			$test = $values['custom_url'];
			
			if ( mb_substr( $test, 0, 2 ) === '//' )
			{
				$test = 'http:' . $test;
			}
			
			if ( filter_var( $test, FILTER_VALIDATE_URL ) === false )
			{
				throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'url_is_not_real', FALSE, array( 'sprintf' => array( $values['custom_url'] ) ) ) );
			}
		}
	}

	/**
	 * Determine if the change in configuration warrants a move process
	 *
	 * @param	array		$configuration	    New Storage configuration
	 * @param	array		$oldConfiguration   Existing Storage Configuration
	 * @return	boolean
	 */
	public static function moveCheck( $configuration, $oldConfiguration )
	{
		if ( str_replace( '{root}', \IPS\ROOT_PATH, $configuration['dir'] ) !== str_replace( '{root}', \IPS\ROOT_PATH, $oldConfiguration['dir'] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Display name
	 *
	 * @param	array	$settings	Configuration settings
	 * @return	string
	 */
	public static function displayName( $settings )
	{
		return \IPS\Member::loggedIn()->language()->addToStack( 'filehandler_display_name', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('filehandler__FileSystem'), str_replace( '{root}', \IPS\ROOT_PATH, $settings['dir'] ) ) ) );
	}

	/* !File Handling */

	/**
	 * @brief	Does this storage method support chunked uploads?
	 */
	public static $supportsChunking = TRUE;
	
	/**
	 * Constructor
	 *
	 * @param	array	$configuration	Storage configuration
	 * @return	void
	 */
	public function __construct( $configuration )
	{
		$this->container = 'monthly_' . date( 'Y' ) . '_' . date( 'm' );
		$configuration['dir'] = str_replace( '{root}', \IPS\ROOT_PATH, $configuration['dir'] );

		parent::__construct( $configuration );
	}

	/**
	 * @brief	Store the path to the file so we can just move it later
	 */
	protected $temporaryFilePath	= NULL;
	
	/**
	 * Set the file
	 *
	 * @param	string	$filepath	The path to the file on disk
	 * @return  void
	 */
	public function setFile( $filepath )
	{
		$this->temporaryFilePath	= $filepath;
	}
	
	/**
	 * Return the base URL
	 *
	 * @return string
	 */
	public function baseUrl()
	{
		$url = ( empty( $this->configuration['custom_url'] ) ) ? \IPS\Settings::i()->base_url . $this->configuration['url'] : $this->configuration['custom_url'];
		if ( \IPS\Request::i()->isSecure() )
		{
			$url = str_replace( 'http://', 'https://', $url );
		}
		return $url;
	}
	
	/**
	 * Move file to a different storage location
	 *
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param   int         $flags                  Bitwise Flags
	 * @return	\IPS\File
	 */
	public function move( $storageConfiguration, $flags=0 )
	{
		if ( $this->configurationId === $storageConfiguration and isset( $this->configuration['old_url'] ) )
		{
			/* We're just updating the URL, not actually moving the file */
			if ( mb_substr( $this->url, 0, mb_strlen( $this->configuration['old_url'] ) ) == $this->configuration['old_url'] )
			{
				$this->url = str_replace( $this->configuration['old_url'], $this->configuration['url'], $this->url );

				return $this;
			}

			/* Is this the new url, then? */
			if (  mb_substr( $this->url, 0, mb_strlen( $this->configuration['url'] ) ) != $this->configuration['url'] )
			{
				/* No? Something has gone wrong */
				throw new \RuntimeException('url_update_incorrect_url');
			}
			else
			{
				return $this;
			}
		}
		else if ( static::$storageConfigurations[ $storageConfiguration ]['method'] === 'FileSystem' )
		{
			$newConfig = json_decode( static::$storageConfigurations[ $storageConfiguration ]['configuration'], TRUE );

			/* Do both configs have the same path? */
			if ( str_replace( '{root}', \IPS\ROOT_PATH, $newConfig['dir'] ) == $this->configuration['dir'] )
			{
				/* Don't move */
				return $this;
			}
		}

		return parent::move( $storageConfiguration );
	}

	/**
	 * Print the contents of the file
	 *
	 * @param	int|null	$start		Start point to print from (for ranges)
	 * @param	int|null	$length		Length to print to (for ranges)
	 * @param	int|null	$throttle	Throttle speed
	 * @return	void
	 */
	public function printFile( $start=NULL, $length=NULL, $throttle=NULL )
	{
		$file	= $this->configuration['dir'] . '/' . $this->container . '/' . $this->filename;

		$this->sendFile( $file, $start, $length, $throttle );
	}

	/**
	 * Get Contents
	 *
	 * @param	bool	$refresh	If TRUE, will fetch again
	 * @return	string
	 * @throws  \RuntimeException
	 */
	public function contents( $refresh=FALSE )
	{
		if ( $this->contents === NULL or $refresh === TRUE )
		{
			if( file_exists( $this->configuration['dir'] . '/' . $this->container . '/' . $this->filename ) )
			{
				$this->contents = @file_get_contents( $this->configuration['dir'] . '/' . $this->container . '/' . $this->filename );
				
				if ( $this->contents === FALSE )
				{
					throw new \RuntimeException( 'COULD_NOT_OPEN_FILE' );
				}
			}
			else
			{
				throw new \RuntimeException( 'FILE_DOES_NOT_EXIST' );
			}
		}

		return $this->contents;
	}

	/**
	 * If the file is an image, get the dimensions
	 *
	 * @return	array
	 * @throws	\DomainException
	 * @throws  \RuntimeException
	 * @throws	\InvalidArgumentException
	 */
	public function getImageDimensions()
	{
		if( !$this->isImage() )
		{
			throw new \DomainException;
		}

		$file	= $this->configuration['dir'] . '/' . $this->container . '/' . $this->filename;
		
		if ( ! file_exists( $file ) )
		{
			throw new \RuntimeException;
		}
		
		if( ( $image = getimagesize( $file ) ) === FALSE )
		{
			return parent::getImageDimensions();
		}

		return array( $image[0], $image[1] );
	}
		
	/**
	 * Save File
	 *
	 * @return	void
	 * @throws	\RuntimeException
	 */
	public function save()
	{
		/* Make the folder */
		$folder = $this->configuration['dir'] . '/' . $this->getFolder();
				
		/* Save the file */
		if( $this->temporaryFilePath )
		{
			if( !@\rename( $this->temporaryFilePath, "{$folder}/{$this->filename}" ) OR @chmod( "{$folder}/{$this->filename}", \IPS\IPS_FILE_PERMISSION ) === FALSE )
			{
				throw new \RuntimeException( 'COULD_NOT_MOVE_FILE' );
			}
		}
		else
		{
			if ( $contents = $this->contents() )
			{				
				if ( !@\file_put_contents( "{$folder}/{$this->filename}", $contents ) OR @chmod( "{$folder}/{$this->filename}", \IPS\IPS_FILE_PERMISSION ) === FALSE )
				{					
					throw new \RuntimeException( 'COULD_NOT_WRITE_FILE' );
				}
			}
			else
			{
				$return = touch( "{$folder}/{$this->filename}" );
				@chmod( "{$folder}/{$this->filename}", \IPS\IPS_FILE_PERMISSION );
			}
		}
		
		/* Clear zend opcache if enabled */
		if ( function_exists( 'opcache_invalidate' ) )
		{
			@opcache_invalidate( "{$folder}/{$this->filename}" );
		}
		
		/* Set the URL */
		$this->url = new \IPS\Http\Url( $this->fullyQualifiedUrl( "{$this->container}/{$this->filename}" ) );
	}
		
	/**
	 * Delete
	 *
	 * @return	bool
	 */
	public function delete()
	{
		if( file_exists( "{$this->configuration['dir']}/{$this->container}/{$this->filename}" ) )
		{
			$result = @unlink( "{$this->configuration['dir']}/{$this->container}/{$this->filename}" );

			/* Clear zend opcache if enabled */
			if ( function_exists( 'opcache_invalidate' ) )
			{
				@opcache_invalidate( "{$this->configuration['dir']}/{$this->container}/{$this->filename}" );
			}

			return $result;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Delete Container
	 *
	 * @param	string	$container	Key
	 * @param	bool	$skipClear	Skip clearing the opcache, used for recursive calls since the parent call will do it
	 * @return	void
	 */
	public function deleteContainer( $container, $skipClear = FALSE )
	{
		$dir = $this->configuration['dir'] . '/' . $container;

		if ( is_dir( $dir ) )
		{
			foreach ( new \DirectoryIterator( $dir ) as $f )
			{
				if ( !$f->isDot() )
				{
					if( $f->isDir() )
					{
						$this->deleteContainer( $container . '/' . $f->getFilename(), TRUE );
					}
					else
					{
						unlink( $f->getPathname() );
						
						/* Clear zend opcache if enabled */
						if( $skipClear === FALSE and function_exists( 'opcache_invalidate' ) )
						{
							@opcache_invalidate( $f->getPathname() );
						}
					}
				}
			}

			rmdir( $dir );
		}
	}
	
	/**
	 * Append more contents in a chunked upload
	 *
	 * @param	string		$filename	The filename that was created
	 * @param	string		$chunk		The temp filename for the new chunk to append
	 * @param	string|NULL	$rename		If a value is provided, rename the file after appending (will be provided for the last chunk)
	 * @param	string|NULL	$container  Key to identify container for storage
	 * @return	string
	 * @throws	\RuntimeException
	 */
	public function chunkAppend( $filename, $chunk, $rename=NULL, $container=NULL )
	{
		$folder = $this->getFolder( $container );
		
		$file = fopen( "{$this->configuration['dir']}/{$folder}/{$filename}", 'ab' );
		$_chunk = fopen( $chunk, 'rb' );
		while ( $buffer = fread( $_chunk, 4096 ) )
		{
			\fwrite( $file, $buffer );
		}
		fclose( $file );
		@unlink( $chunk );

		/* Clear zend opcache if enabled */
		if ( function_exists( 'opcache_invalidate' ) )
		{
			@opcache_invalidate( "{$this->configuration['dir']}/{$folder}/{$filename}" );
		}

		if ( $rename )
		{
			$newFileName = static::obscureFilename( $rename );
			rename( "{$this->configuration['dir']}/{$folder}/{$filename}", "{$this->configuration['dir']}/{$folder}/{$newFileName}" );
			return "{$folder}/{$newFileName}";
		}

		return "{$folder}/{$filename}";
	}
	
	
	/* !File System Utility Methods */
	
	/**
	 * Get the path to the folder
	 *
	 * @param	string|null	$folderName	Folder name - if NULL, a monthly name will be used
	 * @return	string
	 */
	protected function getFolder( $folderName=NULL )
	{
		$folderName = $folderName ?: $this->container;
		$folder = $this->configuration['dir'] . '/' . $folderName;
		if( !is_dir( $folder ) )
		{
			if( @mkdir( $folder, \IPS\IPS_FOLDER_PERMISSION, TRUE ) === FALSE or @chmod( $folder, \IPS\IPS_FOLDER_PERMISSION ) === FALSE )
			{
				throw new \RuntimeException( 'COULD_NOT_CREATE_FOLDER' );
			}
			@\file_put_contents( $folder . '/index.html', '' );
		}
		
		return $folderName;
	}

	/**
	 * Remove orphaned files
	 *
	 * @param	int		$fileIndex	The file offset to start at in a listing
	 * @param	array	$engines	All file storage engine extension objects
	 * @return	array
	 */
	public function removeOrphanedFiles( $fileIndex, $engines )
	{
		/* Start off our results array */
		$results	= array(
			'_done'				=> FALSE,
			'fileIndex'			=> $fileIndex,
		);

		/* Some basic init */
		$checked	= 0;
		$skipped	= 0;

		/* We need to open our storage directory and start looping over it */
		$dir = $this->configuration['dir'];

		if ( is_dir( $dir ) )
		{
			$iterator	= new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS ) );

			foreach ( $iterator as $f )
			{
				/* We aren't checking directories */
				if( $f->isDir() OR $f->getFilename() == 'index.html' OR mb_substr( $f->getFilename(), 0, 1 ) === '.' OR mb_substr( $iterator->getSubPathname(), 0, 5 ) === 'logs/' )
				{
					continue;
				}

				/* Have we hit our limit?  If so we need to stop. */
				if( $checked >= 100 )
				{
					break;
				}

				/* Is there an offset?  If so we need to skip */
				if( $fileIndex > 0 AND $fileIndex > $skipped )
				{
					$skipped++;
					continue;
				}

				$checked++;

				/* Next we will have to loop through each storage engine type and call it to see if the file is valid */
				foreach( $engines as $engine )
				{
					/* If this file is valid for the engine, skip to the next file */
					try
					{
						if( $engine->isValidFile( $iterator->getSubPathname() ) )
						{
							continue 2;
						}
					}
					catch( \InvalidArgumentException $e )
					{
						continue 2;
					}
				}
								
				/* If we are still here, the file was not valid */
				$this->logOrphanedFile( $iterator->getSubPathname() );
			}
		}

		$results['fileIndex'] += $checked;

		/* Are we done? */
		if( !$checked OR $checked < 100 )
		{
			$results['_done']	= TRUE;
		}

		return $results;
	}

	/**
	 * Get filesize (in bytes)
	 *
	 * @return	string
	 */
	public function filesize()
	{
		return file_exists( $this->configuration['dir'] . '/' . $this->container . '/' . $this->filename ) ? @filesize( $this->configuration['dir'] . '/' . $this->container . '/' . $this->filename ) : NULL;
	}
}