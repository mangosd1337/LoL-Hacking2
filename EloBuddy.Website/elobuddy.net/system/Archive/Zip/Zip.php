<?php
/**
 * @brief		Zip Archive Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Archive;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Zip Archive Class
 */
abstract class _Zip extends \IPS\Archive
{
	/**
	 * Create object from local file
	 *
	 * @param	string	$path			Path to archive file
	 * @param	string	$containerName	The root folder name which should be ignored (with trailing slash)
	 * @return	\IPS\Archive
	 */
	public static function fromLocalFile( $path, $containerName = '' )
	{
		if ( extension_loaded( 'Zip' ) )
		{
			$object = Zip\ZipArchive::_fromLocalFile( $path );
		}
		else
		{
			$object = Zip\PclZip::_fromLocalFile( $path );
		}
		
		$object->containerName = $containerName;
		return $object;
	}
}