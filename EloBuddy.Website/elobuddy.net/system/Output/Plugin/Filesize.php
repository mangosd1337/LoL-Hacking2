<?php
/**
 * @brief		Template Plugin - Filesize
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Filesize
 */
class _Filesize
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = FALSE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	array		array( 'pre' => Code to eval before 'return', 'return' => Code to eval to return desired value )
	 */
	public static function runPlugin( $data, $options )
	{
		return '\IPS\Output\Plugin\Filesize::humanReadableFilesize( ' . $data . ( ( isset( $options['decimal'] ) and $options['decimal'] ) ? ', TRUE' : '' ) . ' )';
	}
	
	/**
	 * Get human readable filesize (to 2SF)
	 *
	 * @param	int		$sizeInBytes	Size in bytes
	 * @param	bool	$decimal		If TRUE, will calculate based on decimal figures rather than binary
	 * @param	bool	$json			If TRUE, will format for json
	 * @return	string
	 */
	public static function humanReadableFilesize( $sizeInBytes, $decimal=FALSE, $json=FALSE )
	{
		$sizeInBytes = floatval( $sizeInBytes );
		
		foreach ( array( 'Y' => 80, 'Z' => 70, 'E' => 60, 'P' => 50, 'T' => 40, 'G' => 30, 'M' => 20, 'k' => 10 ) as $sig => $pow )
		{
			$raised = $decimal ? pow( 1000, $pow / 10 ) : pow( 2, $pow );
			if ( $sizeInBytes >= $raised )
			{
				$format = array( 'sprintf' => round( ( $sizeInBytes / $raised ), 1 ) );

				if( $json === TRUE )
				{
					$format['json'] = TRUE;
				}

				return \IPS\Member::loggedIn()->language()->addToStack( 'filesize_' . $sig, FALSE, $format );
			}
		}

		$format = array( 'sprintf' => $sizeInBytes );

		if( $json === TRUE )
		{
			$format['json'] = TRUE;
		}

		return \IPS\Member::loggedIn()->language()->addToStack( 'filesize_b', FALSE, $format );
	}
}