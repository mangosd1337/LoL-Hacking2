<?php
/**
 * @brief		Conversion module
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Tools
 * @since		4 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Text\Module;

/**
 * Native MB Conversion class
 */
class Iconv extends \IPSUtf8\Text\Charset
{
	/**
	 * Converts a text string from its current charset to a destination charset using iconv
	 *
	 * @param	string		Text string
	 * @param	string		Text string char set (original)
	 * @param	string		Desired character set (destination)
	 * @return	@e string
	 */
	public function convert( $string, $from, $to='UTF-8' )
	{
		if ( static::needsConverting( $string, $from, $to ) === false )
		{
			return $string;
		}

		if ( function_exists( 'iconv' ) )
		{
			$text = iconv( $from, $to.'//TRANSLIT', $string );
		}
		else
		{
			static::$errors[]	= "NO_ICONV_FUNCTION";
		}
		
		return $text ? $text : $string;
	}
}