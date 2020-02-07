<?php
/**
 * @brief		Language Class
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
 * Language Class
 */
class _Lang extends \IPS\Node\Model
{
	/* !Lang - Static */

	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll = FALSE;
	
	/**
	 * @brief	Default language ID
	 */
	protected static $defaultLanguageId = NULL;
	
	/**
	 * @brief	Output lang stack
	 */
	public $outputStack	= array();
	
	/**
	 * @brief	lang key salt
	 */
	protected static $outputSalt	= NULL;

	/**
	 * @brief	Have all the words been loaded?
	 */
	protected $wordsLoaded	= FALSE;

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
		if( \IPS\Dispatcher::hasInstance() AND \IPS\Dispatcher::i()->controllerLocation == 'front' AND $idField === NULL AND $extraWhereClause === NULL )
		{
			$languages = static::languages();
			
			if ( !isset( $languages[ $id ] ) )
			{
				throw new \OutOfRangeException;
			}

			$languages[ $id ]->languageInit();
			return $languages[ $id ];
		}

		$result	= parent::load( $id, $idField, $extraWhereClause );
		$result->languageInit();

		return $result;
	}

	/**
	 * Languages
	 *
	 * @param	null|\IPS\Db\Select	$iterator	Select iterator
	 * @return	array
	 */
	public static function languages( $iterator=NULL )
	{
		if ( !static::$gotAll )
		{
			if( $iterator === NULL )
			{
				if ( isset( \IPS\Data\Store::i()->languages ) )
				{
					$rows = \IPS\Data\Store::i()->languages;
				}
				else
				{
					$rows = iterator_to_array( \IPS\Db::i()->select( '*', 'core_sys_lang', NULL, 'lang_order' )->setKeyField('lang_id') );
					\IPS\Data\Store::i()->languages = $rows;
				}
			}
			else
			{
				$rows	= iterator_to_array( $iterator );
			}
			
			foreach( $rows as $id => $lang )
			{
				if ( $lang['lang_default'] )
				{
					static::$defaultLanguageId = $lang['lang_id'];
				}
				static::$multitons[ $id ] = static::constructFromData( $lang );
			}
			
			static::$outputSalt = uniqid();

			static::$gotAll	= TRUE;
		}
		return static::$multitons;
	}

	/**
	 * Get the enabled languages
	 *
	 * @param	null|\IPS\Db\Select	$iterator	Select iterator
	 * @return array
	 */
	public static function getEnabledLanguages( $iterator=NULL )
	{
		$languages = static::languages($iterator);
		$enabledLanguages = array();
		foreach ( $languages AS $id => $lang )
		{
			if  ( $lang->enabled )
			{
				$enabledLanguages[$id] = $lang;
			}
		}

		return $enabledLanguages;
	}
	
	/**
	 * Get default language ID
	 *
	 * @return	int
	 */
	public static function defaultLanguage()
	{
		if ( !static::$gotAll )
		{
			static::languages();
		}
		return static::$defaultLanguageId;
	}
	
	/**
	 * Get language object for installer
	 *
	 * @return	static
	 */
	public static function setupLanguage()
	{
		$obj = new \IPS\Lang\Setup\Lang;
		require \IPS\ROOT_PATH . '/' . \IPS\CP_DIRECTORY . '/install/lang.php';
		$obj->words = $lang;
		$obj->set_short( ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? 'english' : 'en_US' );
		$obj->wordsLoaded = TRUE;
		return $obj;
	}

	/**
	 * Add upgrader language bits
	 *
	 * @return	void
	 */
	public static function upgraderLanguage()
	{
		$obj = new \IPS\Lang\Upgrade\Lang;
		require \IPS\ROOT_PATH . '/' . \IPS\CP_DIRECTORY . '/upgrade/lang.php';
		$obj->words = $lang;
		return $obj;
	}
	
	/**
	 * Auto detect language
	 *
	 * @param	string	$acceptLanguage	HTTP Accept-Language header
	 * @return	int|NULL	ID Of preferred language or NULL if could not be autodetected
	 */
	public static function autoDetectLanguage( $httpAcceptLanguage )
	{
		$preferredLanguage = NULL;
		
		if( mb_strpos( $httpAcceptLanguage, ',' ) )
		{
			$httpAcceptLanguage = explode( ',', $httpAcceptLanguage );
			$httpAcceptLanguage	= $httpAcceptLanguage[0];
		}
		$httpAcceptLanguage	= explode( '-', mb_strtolower( $httpAcceptLanguage ) );
		
		foreach ( static::languages() as $lang )
		{
			if( !$lang->enabled )
			{
				continue;
			}

			if ( preg_match( '/^\w{2}[-_]\w{2}($|\.)/i', $lang->short ) ) // This will only work for Unix-style locales
			{
				$langCode = \strtolower( \substr( $lang->short, 0, 2 ) );
				$countryCode = \strtolower( \substr( $lang->short, -2 ) );
				
				if ( $langCode === $httpAcceptLanguage[0] )
				{
					$preferredLanguage = $lang->id;
					
					/* Some browsers are silly and send HTTP_ACCEPT_LANGUAGE like this: en,en-US;q=0.9 */
					/* I'm looking at you, Opera */
					if ( isset( $httpAcceptLanguage[1] ) )
					{
						if ( $countryCode === $httpAcceptLanguage[1] )
						{
							break;
						}
					}
				}
			}
		}
		
		return $preferredLanguage;
	}
	
	/**
	 * Save translatable language strings
	 *
	 * @param	string			$app	Application key
	 * @param	string			$key	Word key
	 * @param	string|array	$values	The values
	 * @param	bool			$js		Expose to JavaScript?
	 * @return	void
	 */
	public static function saveCustom( $app, $key, $values, $js=FALSE )
	{
		$default = '';
		
		/* Values is a string, so use this value for all languages */
		if ( !is_array( $values ) )
		{
			$default = $values;
			$values  = array();
			
			foreach ( static::languages() as $lang )
			{
				$values[ $lang->id ] = $default;
			}
		}
		else
		{
			if ( count( $values ) == 0  )
			{
				return;
			}
			foreach ( static::languages() as $lang )
			{
				if ( !isset( $values[ $lang->id ] ) )
				{
					$values[ $lang->id ] = '';
				}
				else if ( $lang->default )
				{
					$default = $values[ $lang->id ];
				}
			}
		}
				
		$currentValues = iterator_to_array( \IPS\Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', $key ) )->setKeyField('lang_id') );
		foreach ( $values as $langId => $value )
		{
			if ( isset( $currentValues[ $langId ] ) )
			{
				\IPS\Db::i()->update( 'core_sys_lang_words', array( 'word_default' => $default, 'word_custom' => $value ), array( 'lang_id=? AND word_key=?', $langId, $key ) );
			}
			else
			{
				\IPS\Db::i()->replace( 'core_sys_lang_words', array(
					'lang_id'		=> $langId,
					'word_app'		=> $app,
					'word_key'		=> $key,
					'word_default'	=> $default,
					'word_custom'	=> $value,
					'word_js'		=> $js,
					'word_export'	=> FALSE,
				) );
			}
			
			if ( isset( static::$multitons[ $langId ] ) )
			{
				static::$multitons[ $langId ]->words[ $key ] = $value;
			}
			
			if ( $js )
			{
				\IPS\Output::clearJsFiles( 'global', 'root', 'js_lang_' . $langId . '.js' );
			}
			
			if ( $key === '_list_format_' )
			{
				unset( \IPS\Data\Store::i()->listFormats );
			}
		}
	}
	
	/**
	 * Copy custom values to a different key
	 *
	 * @param	string	$app	Application Key
	 * @param	string	$key	Word key
	 * @param	string	$newKey	New Word Key
	 * @param	string	$newApp	New Application Key, if different
	 * @return	void
	 */
	public static function copyCustom( $app, $key, $newKey, $newApp=NULL )
	{
		$values = array();
		foreach ( \IPS\Db::i()->select( 'lang_id, word_default, word_custom', 'core_sys_lang_words', array( 'word_app=? AND word_key=?', $app, $key ) )->setKeyField('lang_id') as $langId => $data )
		{
			$values[ $langId ] = $data['word_custom'] ?: $data['word_default'];
		}
		
		foreach( $values as $row )
		{
			static::saveCustom( $newApp ?: $app, $newKey, $values );
		}
	}
	
	/**
	 * Delete translatable language strings
	 *
	 * @param	string	$app	Application key
	 * @param	string	$key	Word key
	 * @return	void
	 */
	public static function deleteCustom( $app, $key )
	{
		\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'word_app=? AND word_key=?', $app, $key ) );
	}
	
	/**
	 * Validate a locale
	 *
	 * @param	string	$locale	The locale to test
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	public static function validateLocale( $locale )
	{
		if ( $locale != 'x' )
		{
			$success = FALSE;
			$currentLocale = setlocale( LC_ALL, '0' );
			foreach ( array( "{$locale}.UTF-8", "{$locale}.UTF8", $locale ) as $l )
			{
				$test = setlocale( LC_ALL, $l );
				
				if ( $test !== FALSE )
				{
					$success = TRUE;
					break;
				}
			}

			static::restoreLocale( $currentLocale );
			
			if ( $success === FALSE )
			{
				throw new \InvalidArgumentException( 'lang_short_err' );
			}
		}
	}
	
	/**
	 * Import IN_DEV languages to the database
	 *
	 * @param	string	$app	Application directory
	 * @return void
	 */
	public static function importInDev( $app )
	{
		/* Import the language files */
		$lang = array();
		
		/* Get all installed languages */
		$languages = array_keys( \IPS\Lang::languages() );
		$version   = \IPS\Application::load( $app )->long_version;
		 
		require \IPS\ROOT_PATH . "/applications/{$app}/dev/lang.php";
		
		\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'word_app=? AND word_export=1', $app ) );
		
		foreach ( $lang as $k => $v )
		{
			$inserts = array();
			foreach( $languages as $languageId )
			{
				$inserts[]	= array(
					'word_app'				=> $app,
					'word_key'				=> $k,
					'lang_id'				=> $languageId,
					'word_default'			=> $v,
					'word_custom'			=> NULL,
					'word_default_version'	=> $version,
					'word_custom_version'	=> NULL,
					'word_js'				=> 0,
					'word_export'			=> 1,
				);
			}
				
			\IPS\Db::i()->replace( 'core_sys_lang_words', $inserts );
		}

		$lang	= array();

		require \IPS\ROOT_PATH . "/applications/{$app}/dev/jslang.php";
		foreach ( $lang as $k => $v )
		{
			$inserts = array();
			foreach( $languages as $languageId )
			{
				$inserts[]	= array(
					'word_app'				=> $app,
					'word_key'				=> $k,
					'lang_id'				=> $languageId,
					'word_default'			=> $v,
					'word_custom'			=> NULL,
					'word_default_version'	=> $version,
					'word_custom_version'	=> NULL,
					'word_js'				=> 1,
					'word_export'			=> 1,
				);
			}
				
			\IPS\Db::i()->replace( 'core_sys_lang_words', $inserts );
		}
	}
	
	/* !Lang - Instance */
	
	/**
	 * @brief	Locale data
	 */
	public $locale = array();

	/**
	 * @brief	Codepage used, if Windows
	 */
	public $codepage	= NULL;
	
	/**
	 * Set the appropriate locale
	 *
	 * @return	void
	 * @note	<a href='https://bugs.php.net/bug.php?id=18556'>Turkish and some other locales do not work properly</a>
	 */
	public function setLocale()
	{
		$result	= setlocale( LC_ALL, $this->short );

		/* Some locales in some PHP versions break things drastically */
		if( in_array( 'ips\\db\\_select', get_declared_classes() ) AND !in_array( 'IPS\\Db\\_Select', get_declared_classes() ) )
		{
			setlocale( LC_CTYPE, 'en_US.UTF-8' );
		}

		/* If this is Windows, store the codepage as we will need it again later */
		if( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' )
		{
			$codepage	= preg_replace( "/^(.+?)\.(.+?)$/i", "$2", $result );

			if( $codepage !== $result )
			{
				$this->codepage	= $codepage;
			}
		}
	}
	
	/**
	 * Restore a previous locale
	 *
	 * @param	string	$previousLocale	Value from setlocale( LC_ALL, '0' )
	 * @return	void
	 */
	public static function restoreLocale( $previousLocale )
	{
		foreach( explode( ";", $previousLocale ) as $locale )
		{
			if( mb_strpos( $locale, '=' ) !== FALSE )
			{
				$parts = explode( "=", $locale );
				if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
				{
					setlocale( constant( $parts[0] ), $parts[1] );
				}
			}
			else
			{
				setLocale( LC_ALL, $locale );
			}
		}
	}
	
	/**
	 * Get the preferred date format for this locale
	 *
	 * @return	string
	 */
	public function preferredDateFormat()
	{
		/* Make sure the locale has been set, important for things like the js date_format variable */
		$this->setLocale();

		$date = new \IPS\DateTime('1992-03-04');
		return str_replace( array( '1992', '92', '03', '3', $date->strFormat('%B'), '04', ' 4', '4' ), array( 'yy', 'yy', 'mm', 'mm', 'mm', 'dd', 'dd', 'dd' ), $date->localeDate() );
	}

	/**
	 * Convert the character set for locale-aware strings on Windows systems
	 *
	 * @param	string	$text	Text to convert
	 * @return	string
	 */
	public function convertString( $text )
	{
		/* We only do this on Windows */
		if( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) !== 'WIN' )
		{
			return $text;
		}

		/* And only if iconv() exists */
		if( !function_exists( 'iconv' ) )
		{
			return $text;
		}

		/* And only if we have a codepage stored */
		if( !$this->codepage )
		{
			return $text;
		}

		/* Convert the codepage to UTF-8 and return */
		return iconv( "CP" . $this->codepage, "UTF-8", $text );
	}

	/**
	 * @brief	Words
	 */
	public $words = array();
	
	/**
	 * @brief	Original Words
	 */
	public $originalWords = array();
	
	/**
	 * Check Keys Exist
	 *
	 * @param	string	$key	Language key
	 * @return	bool
	 */
	public function checkKeyExists( $key )
	{
		if( isset( $this->words[ $key ] ) )
		{
			return TRUE;
		}
		else if ( array_key_exists( $key, $this->words ) and $this->words[ $key ] === NULL )
		{
			/* Language key has been preloaded but does not exist */
			return FALSE;
		}
		else if( $this->wordsLoaded or \IPS\IN_DEV )
		{
			return FALSE;
		}

		try
		{
			$lang = \IPS\Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( 'lang_id=? AND word_key=?', \IPS\Member::loggedIn()->language()->id, $key ) )->first();
		
			$value = $lang['word_custom'] ?: $lang['word_default'];
				
			$this->words[ $key ] = $value;
			
			return TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}	
	}
	
	/**
	 * Get Language String
	 *
	 * @param	string|array	$key	Language key or array of keys
	 * @return	string|array			Language string or array of key => string pairs
	 */
	public function get( $key )
	{
		$return     = array();
		$keysToLoad = array();
		
		if ( is_array( $key ) )
		{
			foreach( $key as $k )
			{
				if ( in_array( $k, array_keys( $this->words ) ) )
				{
					$return[ $k ] = $this->words[ $k ];
				}
				else
				{
					$keysToLoad[] = "'" . \IPS\Db::i()->real_escape_string( $k ) . "'";
				}
			}
			
			if ( ! count( $keysToLoad ) )
			{
				return $return;
			}
		}
		else
		{
			if ( isset( $this->words[ $key ] ) )
			{
				return $this->words[ $key ];
			}

			$keysToLoad = array( "'" . \IPS\Db::i()->real_escape_string( $key ) . "'" );
		}

		foreach( \IPS\Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( "lang_id=? AND word_key IN(" . implode( ",", $keysToLoad ) . ")", $this->id ) ) as $lang )
		{
			$value = $lang['word_custom'] ?: $lang['word_default'];
			
			$this->words[ $lang['word_key'] ] = $value;
			$return[ $lang['word_key' ] ]     = $value;
		}
		
		/* If we're using an array, fill any missings strings with NULL to prevent duplicate queries */
		if ( is_array( $key ) )
		{
			foreach( $key as $k )
			{
				if ( ! in_array( $k, $return ) and ! array_key_exists( $k, $this->words ) )
				{
					$return[ $k ] = NULL;
					$this->words[ $k ] = NULL;
				}
			}
		}
		
		if ( ! count( $return ) )
		{
			throw new \UnderflowException( ( is_string( $key ) ? 'lang_not_exists__' . $key : 'lang_not_exists__' . implode( ',', $key ) ) );
		}
		
		return is_string( $key ) ? $this->words[ $key ] : $return;
	}
	
	/**
	 * Add to output stack
	 *
	 * @param	string	$key	Language key
	 * @param	bool	$vle	Add VLE tags?
	 * @param	array	$options Options
	 * @return	string	Unique id
	 */
	public function addToStack( $key, $vle=TRUE, $options=array() )
	{
		/* Setup? */
		if( $this->wordsLoaded === TRUE )
		{
			return ( isset( $this->words[ $key ] ) ) ? $this->words[ $key ] : $key;
		}

		/* Get it */
		if( isset( $this->outputStack[ $key ] ) )
		{
			return htmlspecialchars( $key, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE );
		}
		
		$id = md5( 'ipslang_' . static::$outputSalt . $key . json_encode( $options ) );
		$this->outputStack[ $id ]['key']		= $key;
		$this->outputStack[ $id ]['options']	= $options;
		$this->outputStack[ $id ]['vle']		= $vle;
			
		/* Return */
		return $id;
	}
	
	/**
	 * Pluralize
	 *
	 * @param	string	$string	Language string to pluralize
	 * @param	array 	$params	Parameters to pluraizlie with
	 * @return	string
	 * @note	You can use the following wildcards to do special things
	 * @li	? is a fallback, so anything not matched will use it
	 * @li	* is a beginning wildcard, so anything that ENDS with the number supplied will match
	 * @li	% is an ending wildcard, so anything that BEGINS with the number supplied will match
	 * @li	# (optional) will be replaced with the actual value
	 * @example	{# [1:test][*2:tests][%3:no tests][?:finals]} will result in 1 test, 2 tests, 12 tests, 3 no tests, 35 no tests, 8 finals
	 */
	public function pluralize( $string, $params )
	{
		$i = 0;
		$numberFormatter = array( $this, 'formatNumber' );
		return preg_replace_callback( '/\{!?(\d+?)?#(.*?)\}/', function( $format ) use ( $params, $i, $numberFormatter )
        {
	        $originalNumber = $format[1];
			if ( !$format[1] or $format[1] == '!' )
			{
				$format[1] = $i;
				$i++;
			}

			/* Format now so that 0 is really 0 and not '' or null */
			$params[ $format[1] ]	= call_user_func( $numberFormatter, $params[ $format[1] ] );
			$fallback = NULL;
			$value = NULL; 
			
			/* This regex is tricky: It matches [ followed by anything NOT : until :, then any character, then everything that is not a [ until ] */
			preg_match_all( '/\[([^:]+):(.[^\[]*)\]/', $format[2], $matches );

			foreach ( $matches[1] as $k => $v )
			{
				if ( $v == '?' )
				{
					$fallback = str_replace( '#', $params[ $format[1] ], $matches[2][ $k ] );
				}
				elseif( ( mb_substr( $v, 0, 1 ) === '%' and ( mb_substr( $v, 1 ) == $params[ $format[1] ] ) ) )
				{
					$value = str_replace( '#', $params[ $format[1] ], $matches[2][ $k ] );
					// We don't break in case there is a better match
				}
				elseif( ( mb_substr( $v, 0, 1 ) === '*' and ( mb_substr( $v, -( mb_strlen( mb_substr( $v, 1 ) ) ) ) == mb_substr( $params[ $format[1] ], -( mb_strlen( mb_substr( $v, 1 ) ) ) ) ) ) )
				{
					$value = str_replace( '#', $params[ $format[1] ], $matches[2][ $k ] );
					// We don't break in case there is a better match
				}
				elseif ( ( $v == $params[ $format[1] ] ) )
				{
					$value = str_replace( '#', $params[ $format[1] ], $matches[2][ $k ] );
					break;
				}
			}
			
			$return = rtrim( ltrim( $format[0], '{' ), '}' );
			$return = str_replace( "!{$originalNumber}#", '', $return );
			$return = str_replace( array( "{$format[1]}#", '#' ), $params[ $format[1] ], $return );
			$return = preg_replace( '/\[.+\]/', ( $value === NULL ? $fallback : $value ), $return );
			return $return;
        }, $string );
	}
	
	/**
	 * Format Number
	 *
	 * @param	number	$number		The number to format
	 * @param	int		$decimals	Number of decimal places
	 * @return	string
	 * @note	number_format in PHP < 5.4.0 is not multibyte safe, so we have to work around this deficiency
	 */
	public function formatNumber( $number, $decimals=0 )
	{
		$placeholders	= array( '@', '~' );
		$replacements	= array( $this->locale['decimal_point'], $this->locale['thousands_sep'] );
		
		$result	= number_format( floatval( $number ), floatval( $decimals ), $placeholders[0], $placeholders[1] );

		return str_replace( $placeholders, $replacements, $result );
	}
	
	/**
	 * Format List
	 * Takes an array and returns a string, appropriate for the language (e.g. "a, b and c")
	 *
	 * Relies on the _list_format_ language string which should be an example list of three items using the keys a, b and c.
	 * Any can be capitalised to run ucfirst on that item
	 *
	 * Examples if $items = array( 'foo', 'bar', 'baz', 'moo' );
	 *	If _list_format_ is this:			Output will be this:
	 *	a, b and c							foo, bar, baz and moo
	 *	A, B und C							Foo, Bar, Baz und Moo
	 *	a; b; c.							foo; bar; baz; moo.
	 *
	 * @param	array	$items	The items for the list
	 * @param	string	$format	If provided, will override _list_format_
	 * @return	string
	 */
	public function formatList( $items, $format=NULL )
	{
		$items = array_values( $items );
		
		if ( $format === NULL )
		{
			if ( \IPS\IN_DEV )
			{
				$format = $this->words['_list_format_'];
			}
			else
			{
				if ( !isset( \IPS\Data\Store::i()->listFormats ) )
				{
					$formats = array();
					foreach ( \IPS\Db::i()->select( array( 'lang_id', 'word_custom', 'word_default' ), 'core_sys_lang_words', array( 'word_key=?', '_list_format_' ) ) as $row )
					{
						$formats[ $row['lang_id'] ] = $row['word_custom'] ?: $row['word_default'];
					}
					\IPS\Data\Store::i()->listFormats = $formats;
				}
				$format = \IPS\Data\Store::i()->listFormats[ $this->id ];
			}
		}
		
		preg_match( '/(^|^(.+?)\s)(a)(.+?\s)(b)(.+?\s)(c)(.+?)?$/i', $format, $matches );
		
		$return = $matches[1];
		for ( $i = 0; $i<count( $items ); $i++ )
		{
			$upper = FALSE;
			if ( $i == 0 )
			{
				$upper = ( $matches[3] === 'A' );
			}
			elseif ( $i == count( $items ) - 1 )
			{
				$upper = ( $matches[7] === 'C' );
			}
			else
			{
				$upper = ( $matches[5] === 'B' );
			}
			
			$return .= ( $upper ? ucfirst( $items[ $i ] ) : $items[ $i ] );
			
			if ( $i == count( $items ) - 2 )
			{
				$return .= $matches[6];
			}
			elseif ( $i != count( $items ) - 1 )
			{
				$return .= $matches[4];
			}
		}
		if ( isset( $matches[8] ) )
		{
			$return .= $matches[8];
		}
		
		return $return;
	}
	
	/**
	 * Search translatable language strings
	 *
	 * @param	string	$prefix				Prefix used
	 * @param	string	$query				Search query
	 * @param	bool	alsoSearchDefault	If TRUE, will also search the default value
	 * @return	array
	 */
	public function searchCustom( $prefix, $query, $alsoSearchDefault=FALSE )
	{
		$return = array();
		
		$where = array();
		$where[] = array( "lang_id=?", $this->id );
		$where[] = array( "word_key LIKE CONCAT( ?, '%' )", $prefix );
		if ( $alsoSearchDefault )
		{
			$where[] = array( "word_custom LIKE CONCAT( '%', ?, '%' ) OR ( word_custom IS NULL AND word_default LIKE CONCAT( '%', ?, '%' ) )", $query, $query );
		}
		else
		{
			$where[] = array( "word_custom LIKE CONCAT( '%', ?, '%' )", $query );
		}
		
		foreach ( \IPS\Db::i()->select( '*', 'core_sys_lang_words', $where ) as $row )
		{
			$return[ mb_substr( $row['word_key'], mb_strlen( $prefix ) ) ] = $this->get( $row['word_key'] );
		}
		
		return $return;
	}
	
	/**
	 * BCP 47
	 *
	 * @return	string
	 * @see		<a href="https://tools.ietf.org/html/bcp47">BCP 47 - Tags for Identifying Languages</a>
	 */
	public function bcp47()
	{
		if ( preg_match( '/^([a-z]{2})[-_]([a-z]{2})(.utf-?8)?$/i', $this->short, $matches ) )
		{
			return mb_strtolower( $matches[1] ) . '-' . mb_strtoupper( $matches[2] );
		}
		else
		{
			return mb_substr( $this->short, 0, 2 );
		}
	}
	
	/* !Node */
	
	/**
	 * @brief	Order Database Column
	 */
	public static $databaseColumnOrder = 'order';
	
	/**
	 * @brief	Node Title
	 */
	public static $nodeTitle = 'menu__core_languages_languages';
	
	/**
	 * @brief	ACP Restrictions
	 */
	protected static $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'languages',
		'all'		=> 'lang_packs'
	);
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( $column, $query, $order=NULL, $where=array() )
	{
		if ( $column === '_title' )
		{
			$column = 'lang_title';
		}
		if ( $order === '_title' )
		{
			$order = 'lang_title';
		}
		return parent::search( $column, $query, $order, $where );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add children for this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		return FALSE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return	bool
	 */
	public function canDelete()
	{
		return !$this->default;
	}
	
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->add( new \IPS\Helpers\Form\Text( 'lang_title', $this->title, TRUE, array( 'maxLength' => 255 ) ) );
		$this->localeField( $form, $this->id ? $this->short : 'en_US' );		
		$form->add( new \IPS\Helpers\Form\Select( 'lang_isrtl', $this->isrtl, FALSE, array( 'options' => array( FALSE => 'lang_isrtl_left', TRUE => 'lang_isrtl_right' ) ) ) );
		
		if ( !$this->default )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'lang_default', $this->default, FALSE ) );
		}
	}
	
	/**
	 * Add locale field to form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public static function localeField( &$form, $current='en_US' )
	{
		$commonLocales = json_decode( file_get_contents( \IPS\ROOT_PATH . '/system/Lang/locales.json' ), TRUE );
		natcasesort( $commonLocales );
		foreach ( $commonLocales as $k => $v )
		{
			try
			{
				static::validateLocale( $k );
			}
			catch ( \InvalidArgumentException $e )
			{
				unset( $commonLocales[ $k ] );
			}
		}
		
		if ( !empty( $commonLocales ) )
		{
			$form->add( new \IPS\Helpers\Form\Select( 'lang_short', array_key_exists( preg_replace( '/^(.+?)\..+?$/', '$1', $current ), $commonLocales ) ? preg_replace( '/^(.+?)\..+?$/', '$1', $current ) : 'x', TRUE, array(
				'options'	=> array_merge( $commonLocales, array( 'x' =>  \IPS\Member::loggedIn()->language()->addToStack('lang_short_other') ) ),
				'toggles'	=> array( 'x' => array( 'locale_custom' ) ),
				'parse'		=> 'raw'
			), '\IPS\Lang::validateLocale' ) );
		}
		else
		{
			$form->hiddenValues['lang_short'] = 'x';
		}
		
		$form->add( new \IPS\Helpers\Form\Text( 'lang_short_custom', !in_array( $current, $commonLocales ) ? $current : NULL, FALSE, array( 'placeholder' => 'en_US' ), '\IPS\Lang::validateLocale', NULL, NULL, 'locale_custom' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['lang_short_custom'] ) )
		{
			if ( !isset($values['lang_short']) OR $values['lang_short'] === 'x' )
			{
				$values['lang_short'] = $values['lang_short_custom'];
			}
			unset( $values['lang_short_custom'] );
		}
		
		if( isset( $values['lang_short'] ) )
		{
			$currentLocale = setlocale( LC_ALL, '0' );

			foreach ( array( "{$values['lang_short']}.UTF-8", "{$values['lang_short']}.UTF8" ) as $l )
			{
				$test = setlocale( LC_ALL, $l );
				if ( $test !== FALSE )
				{
					$values['lang_short'] = $l;
					break;
				}
			}

			static::restoreLocale( $currentLocale );
		}
		
		foreach ( $values as $k => $v )
		{
			$this->_data[ $k ] = $v;
			$this->changed[ mb_substr( $k, 5 ) ] = $v;
		}
		
		if( isset( $values['lang_default'] ) and $values['lang_default'] )
		{
			$this->enabled = TRUE;
			\IPS\Db::i()->update( 'core_sys_lang', array( 'lang_default' => 0 ) );
			unset( \IPS\Data\Store::i()->languages );
		}		
		
		return $values;
	}
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get__title()
	{
		return $this->title;
	}
	
	/**
	 * Get Icon
	 *
	 * @return	string
	 * @note	Works on Unix systems. Partial support for Windows systems.
	 */
	public function get__icon()
	{
		//if ( preg_match( '/^\w{2}[-_]\w{2}(\..+?)?$/', $this->short ) )
		//{
			$country = ( mb_strpos( $this->short, '_' ) !== FALSE ) ? mb_strtolower( mb_substr( $this->short, mb_strpos( $this->short, '_' ) + 1, 2 ) ) : mb_strtolower( mb_substr( $this->short, 0, 2 ) );
			return "ipsFlag ipsFlag-{$country}";
		//}
		return NULL;
	}
	
	/**
	 * Get enabled
	 *
	 * @return	bool
	 */
	public function get__enabled()
	{
		return (bool) $this->enabled;
	}
	
	/**
	 * Get locked
	 *
	 * @return	bool
	 */
	public function get__locked()
	{
		return (bool) $this->default;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = array();

		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) and ( !isset( \IPS\Request::i()->cookie['vle_editor'] ) or \IPS\Request::i()->cookie['vle_editor'] == 0 ) )
		{
			$buttons['translate'] = array(
				'icon'	=> 'globe',
				'title'	=> 'lang_translate',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=translate&id={$this->_id}" ),
			);
		}
		
		$buttons = array_merge( $buttons, parent::getButtons( $url, $subnode ) );
        
        if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
        {
            $buttons['upload'] = array(
                'icon'	=> 'upload',
                'title'=> 'upload_new_version',
                'link'	=> \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=uploadNewVersion&id={$this->_id}" ),
                'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->get('upload_new_version') )
            );
        }
		
		if ( $this->canEdit() )
		{
			$buttons['download'] = array(
				'icon'	=> 'download',
				'title'	=> 'download',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=download&id={$this->_id}" ),
			);
		}
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			$buttons[] = array(
					'icon'	=> 'user',
					'title'	=> 'language_set_members',
					'link'	=> $url->setQueryString( array( 'do' => 'setMembers', 'id' => $this->default ? 0 : $this->_id ) ),
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title )
			);
		}

		if ( \IPS\IN_DEV )
		{
			$buttons['devimport'] = array(
				'icon'	=> 'cogs',
				'title'	=> 'lang_dev_import',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=devimport&id={$this->_id}" ),
			);
		}
		
		return $buttons;
	}
	
	/* !ActiveRecord */

	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Default Values
	 */
	protected static $defaultValues = array(
		'lang_id'		=> 0,
		'lang_short'	=> 'en_US',
		'lang_title'	=> 'English (USA)',
		'lang_default'	=> TRUE,
		'lang_isrtl'	=> FALSE,
		'lang_protected'=> FALSE,
		'lang_order'	=> 0,
		'lang_enabled'	=> TRUE
	);
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_sys_lang';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'lang_';

	/**
	 * @brief	Has been initialized?
	 */
	protected $_initialized	= FALSE;

	/**
	 * Set words
	 *
	 * @return	void
	 */
	public function languageInit()
	{
		/* Only initialize once */
		if( $this->_initialized === TRUE )
		{
			return;
		}

		$this->_initialized	= TRUE;

		/* Set locale data */
		$this->set_short( $this->short );
		
		/* Get values from developer files */
		if ( \IPS\IN_DEV or \IPS\Settings::i()->theme_designers_mode )
		{
            /* Apps and plugins */
            if ( \IPS\IN_DEV )
            {
                try
                {
                    foreach ( \IPS\Application::applications() as $app )
                    {
                        if ( file_exists( \IPS\ROOT_PATH . "/applications/{$app->directory}/dev/lang.php" ) )
                        {
                            require \IPS\ROOT_PATH . "/applications/{$app->directory}/dev/lang.php";
                            $this->words = array_merge( $this->words, $lang );
                        }
                    }
                }
                catch( \UnexpectedValueException $ex )
                {
                    \IPS\Output\System::i()->error( $ex->getMessage(), 500 );
                }
            
                foreach ( \IPS\Plugin::plugins() as $plugin )
                {
                    if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/lang.php" ) )
                    {
                        require \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/lang.php";
                        $this->words = array_merge( $this->words, $lang );
                    }
                }
            }
            
            /* Themes */
            if ( \IPS\Settings::i()->theme_designers_mode )
            {
                foreach ( \IPS\Theme::themes() as $theme )
                {
                    if ( file_exists( \IPS\ROOT_PATH . '/themes/' . $theme->id . '/lang.php' ) )
                    {
                        require \IPS\ROOT_PATH . '/themes/' . $theme->id . '/lang.php';
                        $this->words = array_merge( $this->words, $lang );
                    }
                }
            }
                
            /* Allow custom strings to override the default strings */
            foreach( \IPS\Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( 'lang_id=? and word_export=?', $this->id, '0' ) ) as $bit )
            {
                $this->words[ $bit['word_key'] ]	= $bit['word_custom'] ?: $bit['word_default'];
            }
        }
	}
	
	/**
	 * Set locale data
	 *
	 * @param	string	$val	Locale
	 * @return	void
	 */
	public function set_short( $val )
	{
		$oldLocale = setlocale( LC_ALL, '0' );
		setlocale( LC_ALL, $val );

		/* Some locales in some PHP versions break things drastically */
		if( in_array( 'ips\\db\\_select', get_declared_classes() ) )
		{
			setlocale( LC_CTYPE, 'en_US.UTF-8' );
		}

		$this->locale = localeconv();
		
		foreach( explode( ";", $oldLocale ) as $locale )
		{
			if( mb_strpos( $locale, '=' ) !== FALSE )
			{
				$parts = explode( "=", $locale );
				if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
				{
					setlocale( constant( $parts[0] ), $parts[1] );
				}
			}
			else
			{
				setLocale( LC_ALL, $locale );
			}
		}
	}
    
    /**
     * Save Changed Columns
     *
     * @return	void
     */
    public function save()
    {
        parent::save();
        unset( \IPS\Data\Store::i()->languages );
    }
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
		\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'lang_id=?', $this->id ) );
        unset( \IPS\Data\Store::i()->languages );
	}
	
	/**
	 * Parse output and replace language keys
	 *
	 * @param	string	$output	Unparsed
	 * @return	void
	 */
	public function parseOutputForDisplay( &$output )
	{
		/* Do we actually have any? */
		if( !count( $this->outputStack ) )
		{
			return;
		}

		/* Parse out lang */
		$keys = array();

		foreach( $this->outputStack as $word => $values )
		{
			if( !isset( $this->words[ $values['key'] ] ) )
			{
				$keys[] = "'" . \IPS\Db::i()->real_escape_string( $values['key'] ) . "'";
			}
		}

		if( !$this->wordsLoaded === TRUE AND count( $keys ) and !\IPS\IN_DEV )
		{
			foreach( \IPS\Db::i()->select( 'word_key, word_default, word_custom', 'core_sys_lang_words', array( "lang_id=? AND word_key IN(" . implode( ",", $keys ) . ") and word_js=0", $this->id ) ) as $row )
			{
				$this->words[ $row['word_key'] ] = $row['word_custom'] ?: $row['word_default'];
			}

			foreach( $this->outputStack as $word => $values )
			{
				if( !isset( $this->words[ $values['key'] ] ) )
				{
					if( isset( $values['options']['returnBlank'] ) AND $values['options']['returnBlank'] === TRUE )
					{
						$this->words[ $values['key'] ]	= '';
					}
					else
					{
						$this->words[ $values['key'] ]	= $values['key'];
					}
				}
			}
		}

		/* Adjust for VLE */
		if ( isset( \IPS\Request::i()->cookie['vle_editor'] ) and \IPS\Request::i()->cookie['vle_editor'] and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$this->originalWords = $this->words;
		}
		if ( isset( \IPS\Request::i()->cookie['vle_keys'] ) and \IPS\Request::i()->cookie['vle_keys'] and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			$this->words = array_combine( array_keys( $this->words ), array_keys( $this->words ) );
		}
	
		$this->outputStack = array_reverse( $this->outputStack );
		
		$this->replaceWords( $output );
	}

	/**
	 * Emails require some additional work on top of replacing the language stack
	 *
	 * @param	string	$output	Unparsed
	 * @return	void
	 */
	public function parseEmail( &$output )
	{
		$this->parseOutputForDisplay( $output );
		
		$dir = ( $this->isrtl ) ? 'rtl' : 'ltr';
		
		if ( mb_stristr( $output, '{dir}' ) )
		{
			$output = preg_replace( '#(<td\s+?)dir=([\'"]){dir}([\'"])#i', '\1dir=\2' . $dir . '\3', $output );
			
			preg_match_all( '#<(body|html)([^>]+?)>#i', $output, $matches, PREG_SET_ORDER );
			
			foreach( $matches as $match )
			{
				if ( mb_stristr( $match[2], '{dir}' ) )
				{
					$parsed = str_replace( '{dir}', $dir, $match[0] );
					$output = str_replace( $match[0], $parsed, $output );
				}
			}
		}
	}
	
	/**
	 * Strip VLE tags, useful for AJAX responses where you can't edit the string anyways
	 *
	 * @param	string	$output	The output string
	 * @return	string
	 */
	public function stripVLETags( $output )
	{
		if( !is_array( $output ) )
		{
			return preg_replace( "/#VLE#.+?#\!#\[(.+?)\]#\!##/", "$1", $output );
		}

		$replacement = array();
		
		foreach ( $output as $key => $value )
		{
			$replacement[ $key ] = $this->stripVLETags( $value );
		}
		
		return $replacement;
	}

	/**
	 * Insert <wbr> tags into long words
	 *
	 * @param	string	$data	The string
	 * @return	string
	 */
	public static function wordbreak( $data )
	{
		$return = '';
	
		$charactersWithoutASpace = 0;
		$inEntity = FALSE;
	
		for ( $i = 0; $i < mb_strlen( $data ); $i++ )
		{
			$char = mb_substr( $data, $i, 1 );
				
			if ( $char !== ' ' )
			{
				$charactersWithoutASpace++;
			}
			else
			{
				$charactersWithoutASpace = 0;
			}
	
			if ( $char === '&' )
			{
				$inEntity = TRUE;
			}
				
			if ( !$inEntity AND $charactersWithoutASpace > 0 AND $charactersWithoutASpace % 20 == 0 )
			{
				$return .= '<wbr>';
			}
            
            if ( $inEntity and $char === ';' )
            {
                $inEntity = FALSE;
            }
				
			$return .= $char;
		}
	
		return $return;
	}
	
	/**
	 * Replace values in array recursively
	 *
	 * @param	string			$find		The string to find
	 * @param	string			$replace	The string to replace with
	 * @param	string|array	$haystack	The subject
	 * 
	 * @return	string|array
	 */
	public static function replace( $find, $replace, $haystack )
	{
		/* Reduce the number of str_replace with arrays */
		static $replaceTable = array();
			
		if ( ! is_array( $haystack ) )
		{
			$hash = md5( json_encode($find) . json_encode($replace) . $haystack );
			
			if ( isset( $replaceTable[ $hash ] ) )
			{
				return $replaceTable[ $hash ];
			}
			else
			{
				$output = str_replace( $find, $replace, $haystack );
				$replaceTable[ $hash ] = $output;
				return $output;
			}
		}
		
		$replacement = array();
		
		foreach ( $haystack as $key => $value )
		{
			$replacement[ $key ] = static::replace( $find, $replace, $value );
		}
		
		return $replacement;
	}


	/**
	 * Parse the output stack
	 *
	 * @param	string	$output	Unparsed
	 * @return	void
	 */
	public function replaceWords( &$output )
	{
		/* It's possible to call this method and not pass in any content - it's a waste of resources to run replacements on an empty string */
		if( !$output )
		{
			return;
		}

		$replacements = array();

		foreach ( $this->outputStack as $key => $values )
		{
			if ( isset( $values[ 'options' ][ 'returnBlank' ] ) AND $values[ 'options' ][ 'returnBlank' ] === true AND ( !isset( $this->words[ $values[ 'key' ] ] ) OR !$this->words[ $values[ 'key' ] ] ) )
			{
				$replacements[ $key ] = "";
				continue;
			}
			else
			{
				if ( isset( $this->words[ $values[ 'key' ] ] ) )
				{
					$replacement = $this->words[ $values[ 'key' ] ];
					
					/* Parse URLs */
					if ( mb_strpos( $replacement, "{external" ) !== false )
					{
						$replacement = preg_replace_callback(
							"/{external\.(.+?)}/",
							function ( $matches )
							{
								return \IPS\Http\Url::ips( 'docs/' . $matches[ 1 ] );
							},
							$replacement
						);
					}
		
					if ( mb_strpos( $replacement, "{internal" ) !== false )
					{
						$replacement = preg_replace_callback(
							"/{internal\.([a-zA-Z]+?)\.(.+?)\.(.+?)}/",
							function ( $matches )
							{
								return \IPS\Http\Url::internal( $matches[ 2 ], $matches[ 1 ], $matches[ 3 ] );
							},
							$replacement
						);
						$replacement = preg_replace_callback(
							"/{internal\.([a-zA-Z]+?)\.(.+?)}/",
							function ( $matches )
							{
								return \IPS\Http\Url::internal( $matches[ 2 ], $matches[ 1 ] );
							},
							$replacement
						);
						$replacement = preg_replace_callback(
							"/{internal\.(.+?)}/",
							function ( $matches )
							{
								return \IPS\Http\Url::internal( $matches[ 1 ] );
							},
							$replacement
						);
					}
		
					
					$sprintf     = array();

					if ( isset( $values[ 'options' ][ 'flipsprintf' ] ) AND $values[ 'options' ][ 'flipsprintf' ] === true )
					{
						if ( isset( $values[ 'options' ][ 'sprintf' ] ) and $values[ 'options' ][ 'sprintf' ] !== null )
						{
							$replacement                      = $values[ 'options' ][ 'sprintf' ];
							$values[ 'options' ][ 'sprintf' ] = array( $this->words[ $values[ 'key' ] ] );
						}

						if ( isset( $values[ 'options' ][ 'htmlsprintf' ] ) and $values[ 'options' ][ 'htmlsprintf' ] !== null )
						{
							$replacement                          = $values[ 'options' ][ 'htmlsprintf' ];
							$values[ 'options' ][ 'htmlsprintf' ] = array( $this->words[ $values[ 'key' ] ] );
						}
					}

					if ( isset( $values[ 'options' ][ 'sprintf' ] ) and $values[ 'options' ][ 'sprintf' ] !== null )
					{
						$sprintf = array_map(
							function ( $val ) use ( $replacement )
							{
								return htmlspecialchars( trim( $val ), ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', false );
							},
							( is_array(
								$values[ 'options' ][ 'sprintf' ]
							) ? $values[ 'options' ][ 'sprintf' ] : explode( ',', $values[ 'options' ][ 'sprintf' ] ) )
						);
					}

					if ( isset( $values[ 'options' ][ 'htmlsprintf' ] ) and $values[ 'options' ][ 'htmlsprintf' ] !== null )
					{
						$sprintf = array_merge(
							$sprintf,
							( is_array(
								$values[ 'options' ][ 'htmlsprintf' ]
							) ? $values[ 'options' ][ 'htmlsprintf' ] : explode(
								',',
								$values[ 'options' ][ 'htmlsprintf' ]
							) )
						);
					}

					if ( count( $sprintf ) )
					{
						try
						{
							$replacement = vsprintf( $replacement, $sprintf );
						}
						catch ( \ErrorException $e )
						{
							// If there's the wrong number of parameters because the tarnslator's done it wrong, we can just display the string
						}
					}

					if ( !empty( $values[ 'options' ][ 'pluralize' ] ) )
					{
						$replacement = \IPS\Member::loggedIn()->language()->pluralize(
							$replacement,
							$values[ 'options' ][ 'pluralize' ]
						);
					}
				}
				else
				{
					$replacement = $values[ 'key' ];
				}
			}

			if ( isset( $values[ 'options' ][ 'wordbreak' ] ) )
			{
				$replacement = \IPS\Lang::wordbreak( $replacement );
			}

			if ( isset( $values[ 'options' ][ 'strtoupper' ] ) )
			{
				$replacement = mb_strtoupper( $replacement );
			}

            if ( isset( $values[ 'options' ][ 'strtolower' ] ) )
            {
                $replacement = mb_strtolower( $replacement );
            }
            
            if ( isset( $values[ 'options' ][ 'json' ] ) )
            {
                $replacement = mb_substr( json_encode( $replacement ), 1, -1 );
            }

            if ( isset( $values[ 'options' ][ 'striptags' ] ) )
            {
                $replacement = strip_tags( $replacement );
            }

            if ( isset( $values[ 'options' ][ 'escape' ] ) )
            {
                $replacement = htmlspecialchars( $replacement, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', false );
            }

			/* Add VLE tags */
			if ( $values[ 'vle' ] and $replacement and isset( \IPS\Request::i(
					)->cookie[ 'vle_editor' ] ) and \IPS\Request::i()->cookie[ 'vle_editor' ] and \IPS\Member::loggedIn(
				)->hasAcpRestriction( 'core', 'languages', 'lang_words' )
			)
			{
				$replacement = "#VLE#{$values['key']}#!#[{$replacement}]#!##";
			}
			
			if ( isset( $values[ 'options' ][ 'returnInto' ] ) )
            {
                $replacement = sprintf( $values[ 'options' ][ 'returnInto' ], $replacement );
            }

			$replacements[ $key ] = $replacement;
		}

		/* We do this 4 times in case a replacement contains another replacement, etc. */
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		$output = static::replace( array_keys( $replacements ), array_values( $replacements ), $output );
		
		return $output;
	}
}