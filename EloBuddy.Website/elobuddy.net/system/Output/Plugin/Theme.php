<?php
/**
 * @brief		Template Plugin - Theme Setting
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
 * Template Plugin - Theme settings
 */
class _Theme
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static $canBeUsedInCss = TRUE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( $data, $options )
	{
		switch( $data )
		{
			case 'logo_front':
				return "\IPS\Theme::i()->logo_front";
			break;
			case 'logo_sharer':
				return "\IPS\Theme::i()->logo_sharer";
			break;
			case 'logo_favicon':
				return "\IPS\Theme::i()->logo_favicon";
			break;
			default:
				return ( isset( \IPS\Theme::i()->settings[ $data ] ) ) ? "\IPS\Theme::i()->settings['{$data}']" : '';
			break;
		}
	}
}