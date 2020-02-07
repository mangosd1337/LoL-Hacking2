<?php
/**
 * @brief		Template Plugin - Hex to RGB. Takes a CSS hex code and returns an RGB string
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		05 Jan 2016
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
 * Template Plugin - hextorgb
 */
class _Hextorgb
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = TRUE;

	/**
	 * Run the plug-in. Take #ffffff and returns 255,255,255, for example
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options )
	{
		$output = array();

		/* If a theme setting key has been passed in, then use that as the value */
		if ( isset( \IPS\Theme::i()->settings[ $data ] ) )
		{
			$data = \IPS\Theme::i()->settings[ $data ];
		}

		/* Basic validation */
		if( !preg_match( "/^#?[0-9a-fA-F]+$/", $data ) OR ( \strlen( str_replace( '#', '', $data ) ) !== 3 AND \strlen( str_replace( '#', '', $data ) ) !== 6 ) )
		{
			return "htmlspecialchars( '" . $data . "', ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE )";
		}

		$data = str_replace( '#', '', $data );

		if ( \strlen( $data ) == 3 )
		{
			$output[] = hexdec( \substr( $data, 0, 1 ) . \substr( $data, 0, 1 ) ); // R
			$output[] = hexdec( \substr( $data, 1, 1 ) . \substr( $data, 1, 1 ) ); // G
			$output[] = hexdec( \substr( $data, 2, 1 ) . \substr( $data, 2, 1 ) ); // B
		}
		else
		{
			$output[] = hexdec( \substr( $data, 0, 2 ) ); // R
			$output[] = hexdec( \substr( $data, 2, 2 ) ); // G
			$output[] = hexdec( \substr( $data, 4, 2 ) ); // B
		}

		if( isset( $options['opacity'] ) )
		{
			return "'rgba(" . implode( ',', $output ) . "," . $options['opacity'] . ")'";
		}
		else
		{
			return "'rgb(" . implode( ',', $output ) . ")'";	
		}		
	}
}