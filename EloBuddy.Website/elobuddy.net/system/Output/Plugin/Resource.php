<?php
/**
 * @brief		Template Plugin - Theme Resource (image, font, theme-specific JS, etc)
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
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
 * Template Plugin - Theme Resource (image, font, theme-specific JS, etc)
 */
class _Resource
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = TRUE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data		The initial data from the tag
	 * @param	array		$options    Array of options
	 * @param	string		$context	The name of the calling function
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options, $context )
	{	
		$exploded = explode( '_', $context );
		$app      = ( isset( $options['app'] )      ? $options['app']      : ( isset( $exploded[1] ) ? $exploded[1] : '' ) );
		$location = ( isset( $options['location'] ) ? $options['location'] : ( isset( $exploded[2] ) ? $exploded[2] : '' ) );
		$noProtocol =  ( isset( $options['noprotocol'] ) ) ? $options['noprotocol'] : "false";

		return "\\IPS\\Theme::i()->resource( \"{$data}\", \"{$app}\", '{$location}', {$noProtocol} )";
	}
}