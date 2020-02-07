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
class Mb extends \IPSUtf8\Text\Charset
{
	/**
	 * Converts a text string from its current charset to a destination charset using mb_convert_encoding.  If mb* functions are not available, logs an error to self::$errors.
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

		if ( function_exists( 'mb_convert_encoding' ) )
		{
			$encodings	= array_map( 'strtolower', mb_list_encodings() );
			
			if( in_array( strtolower( $to ), $encodings ) AND in_array( strtolower( $from ), $encodings ) )
			{
				$text = mb_convert_encoding( $string, $to, $from );
			}
			else
			{
				static::$errors[]	= "NO_MB_FUNCTION";
			}
		}
		else
		{
			static::$errors[]	= "NO_MB_FUNCTION";
		}

		return $text ? $text : $string;
	}
}