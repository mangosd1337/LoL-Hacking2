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

namespace IPSUtf8\Text;

/**
 * Conversion class
 * Refactored from IPB 3.x
 */
abstract class Charset
{
	/**
	 * @brief Array of error messages associated with the conversion
	 */
	public static $errors = array();
	
	/**
	 * @brief Conversion method to use
	 */
	public static $method = NULL;
	
	/**
	 * @brief Should characters be turned into numeric entities
	 */
	protected static $entities = false;
	
	/**
	 * @brief Path for character sets
	 */
	protected static $charsetPath = '';
	
	/**
	 * @brief Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * Constructor
	 */
	public static function i()
	{
		if ( self::$method === NULL )
		{
			/* Try and pick best available */
			if ( FORCE_CONVERT_METHOD === NULL )
			{
				if ( function_exists( 'mb_convert_encoding' ) )
				{
					self::$method = 'mb';
				}
				else if ( function_exists( 'iconv' ) )
				{
					self::$method = 'iconv';
				}
				else if ( function_exists( 'recode_string' ) )
				{
					self::$method = 'recode';
				}
				else
				{
					self::$method = 'internal';
				}
			}
			else
			{
				self::$method = FORCE_CONVERT_METHOD;
			}
		}
		
		self::$method = ucfirst( self::$method );
		
		if ( ! isset( static::$multitons[ self::$method ] ) )
		{
			$classname = "\\IPSUtf8\\Text\\Module\\" . self::$method;
			static::$multitons[ self::$method ] = new $classname;
		}
		
		return new static::$multitons[ self::$method ];
	}
	
	/**
	 * Checks a string to see if conversion is required
	 *
	 * @param	string		Text string
	 * @param	string		Text string char set (original)
	 * @param	string		Desired character set (destination)
	 * @return	@e string
	 */
	public static function needsConverting( $string, $from, $to='UTF-8' )
	{
		$from = strtolower($from);
		$to   = strtolower($to);
		
		/* Return bools, null, ints, etc. */
		if ( ! is_string( $string ) )
		{
			return false;
		}
		
		/* Return if latin only */
		if ( preg_match( '/^([a-zA-Z0-9_\-\.:;\[\]\{\}\!\@\£\$\%\^\&\*\(\)\+\=\"\'\<\>\/]+?)$/', $string ) )
		{
			return false;
		}
		
		if ( $to == $from )
		{
			return false;
		}
		
		if( ! $from )
		{
			return false;
		}
		
		if( !$string OR $string == '' )
		{
			return false;
		}		
		
		return true;
	}
}
