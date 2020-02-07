<?php
/**
 * @brief		Archive table conversion module
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Tools
 * @since		4 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Convert;

/**
 * Conversion class
 */
class Archive extends \IPSUtf8\Convert
{
	/**
	 * @brief	Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Tables we need to convert
	 */
	protected static $nonUtf8Tables = NULL;
	
	/**
	 * @brief	Tables we need to convert
	 */
	protected static $nonUtf8Collations = NULL;
	
	/**
	 * @brief	Number of rows we need to convert
	 */
	protected static $rowsToConvert = NULL;
	
	/**
	 * @brief	Table definition
	 */
	protected static $tableDefinition = NULL;
	
	/**
	 * @brief	Post archive table name
	 */
	const ARCHIVE_TABLE = 'forums_archive_posts';
	
	/**
	 * @brief	Native Database object
	 */
	protected static $db = NULL;
	
	/**
	 * @brief	UTF-8 Database object
	 */
	protected static $utf = NULL;
	
	/**
	 * Get instance
	 *
	 * @return	Output
	 */
	public static function i()
	{
		if ( self::$instance === NULL )
		{ 
			self::$instance = new self();
		
			require( ROOT_PATH . '/conf_global.php' );
			
			if ( $INFO['archive_remote_sql_database'] )
			{
				/* Set up two new database connections */
				static::$db = \IPSUtf8\Db::i('archive', array(
					'sql_host' 	     => $INFO['archive_remote_sql_host'],
					'sql_user' 	     => $INFO['archive_remote_sql_user'],
					'sql_pass'       => $INFO['archive_remote_sql_pass'],
					'sql_database' 	 => $INFO['archive_remote_sql_database'],
					'sql_tbl_prefix' => $INFO['sql_tbl_prefix']
				) );
			
				$sessionData = \IPSUtf8\Session::i()->json;
				
				static::$utf = \IPSUtf8\Db::i('utf8-archive', array(
					'sql_host' 	     => $INFO['archive_remote_sql_host'],
					'sql_user' 	     => $INFO['archive_remote_sql_user'],
					'sql_pass'       => $INFO['archive_remote_sql_pass'],
					'sql_database' 	 => $INFO['archive_remote_sql_database'],
					'sql_tbl_prefix' => $INFO['sql_tbl_prefix'],
					'sql_utf8mb4'    => ( isset( $sessionData['use_utf8mb4'] ) AND ! empty( $sessionData['use_utf8mb4'] ) )
				) );
				
				static::$tableDefinition = \IPSUtf8\Db::i('archive')->getTableDefinition( self::ARCHIVE_TABLE );
				if ( preg_match( '#\scharset=([a-z0-9]+?)(\s|$)#i', $row['Create Table'], $matches ) )
				{
					static::$tableDefinition['charset'] = mb_strtolower( $matches[1] );
				}
				static::$tableDefinition['definition'] = static::$tableDefinition;
			}
			
			self::$instance->init();
		}
		
		return static::$instance;
	}

	/**
	 * Set the current work table
	 * 
	 * @return void
	 */
	public function getTable()
	{
		$table = self::$tableDefinition;
		
		\IPSUtf8\Session::i()->current_table = self::ARCHIVE_TABLE;
		\IPSUtf8\Session::i()->current_pkey  = 'archive_id';
		\IPSUtf8\Session::i()->save();
		
		if ( in_array( self::ARCHIVE_TABLE, array_keys( \IPSUtf8\Session::i()->completed_json ) ) )
		{
			return null;
		}
		
		static::log( "Continuing with " . $table['name'] . ' (PKEY: ' . \IPSUtf8\Session::i()->current_pkey . ')' );
			
		if ( ! \IPSUtf8\Db::i('utf8-archive')->checkForTable( $table['name'] ) )
		{
			if ( \IPSUtf8\Db::i('utf8-archive')->createTable( $this->checkTable( $table['definition'] ) ) === false )
			{
				throw new \RuntimeException( \IPSUtf8\Db::i('utf8-archive')->error . "\n" . var_export( $table['definition'], true ) );
			}
		}
			
		return $table;
	}
	
	/**
	 * Return tables that need converting
	 *
	 * @param	boolean	$force	Force a recount
	 * @return array
	 */
	public function getNonUtf8Tables( $force=false )
	{
		if ( static::$nonUtf8Tables === NULL or $force === TRUE )
		{
			$row = static::$db->query( "SHOW CREATE TABLE `" . \IPSUtf8\Db::i('archive')->prefix . self::ARCHIVE_TABLE . "`" )->fetch_assoc();
			$tblCharset = null;
			
			if ( preg_match( '#\scharset=([a-z0-9]+?)(\s|$)#i', $row['Create Table'], $matches ) )
			{
				$tblCharset = mb_strtolower( $matches[1] );
			}

			if ( $tblCharset !== 'utf8' )
			{
				static::$nonUtf8Tables = array( self::ARCHIVE_TABLE );
			}
		}
		
		return static::$nonUtf8Tables;
	}
	
	/**
	 * Returns total rows to convert
	 *
	 * @return array
	 */
	public function getTotalRowsToConvert()
	{
		if ( static::$rowsToConvert === NULL )
		{
			static::$rowsToConvert = static::$db->select('COUNT(*) as count', self::ARCHIVE_TABLE )->setKeyField('count')->first();
		}
	
		return static::$rowsToConvert;
	}
	
	/**
	 * Returns whether the DB has the correct collation
	 *
	 * @return 	bool
	 */
	public function getNonUtf8CollationTables()
	{
		if ( static::$nonUtf8Collations === null )
		{
			static::$nonUtf8Collations = array();
			
			/* Best check the tables, then */
			foreach( self::$tableDefinition['definition']['columns'] as $name => $column )
			{
				if ( $column['collation'] and ! in_array( $column['collation'], array( 'utf8_unicode_ci', 'utf8mb4_unicode_ci' ) ) )
				{
					static::$nonUtf8Collations[] = self::$tableDefinition['name'];
					break;
				}
			}
			
		}
		
		return static::$nonUtf8Collations;
	}
	
		/**
	 * Return a list of numeric columns that need checking
	 *
	 * @param	string	$name	Table
	 * @return	array	Array of columns that need checking as they can contain INT
	 */
	public static function getNumericColumns( $name )
	{
		if ( ! is_string( $name ) )
		{
			return false;
		}
		
		$return = array();
		
		foreach( self::$tableDefinition['definition']['columns'] as $col => $val )
		{
			if ( in_array( mb_strtolower( $val['type'] ), static::$numericCols ) )
			{
				$return[] = $val['name'];
			}
		}
		
		return $return;
	}
	
	/**
	 * Return a list of columns that need converting
	 *
	 * @param	string	$name	Table
	 * @return	array	Array of columns that need converting as they can contain text
	 */
	public static function getConvertableColumns( $name )
	{
		if ( ! is_string( $name ) )
		{
			return false;
		}
		
		$return = array();
		
		foreach( self::$tableDefinition['definition']['columns'] as $col => $val )
		{
			if ( in_array( mb_strtolower( $val['type'] ), static::$convertCols ) )
			{
				$return[] = $val['name'];
			}
		}
		
		return $return;
	}
	
	/**
	 * Rename the tables
	 *
	 * @return null
	 */
	public function renameTables()
	{
		/* No need to bother with fancy stuffs to find the tables - we already know what they are */
		\IPSUtf8\Db::i('archive')->query( "RENAME TABLE `" . \IPSUtf8\Db::i('archive')->prefix . self::ARCHIVE_TABLE . "` TO `orig_" . \IPSUtf8\Db::i('archive')->prefix . self::ARCHIVE_TABLE . "`" );
		
		/* Sleep for a second to avoid a race condition */
		sleep(1);
		
		\IPSUtf8\Db::i('utf8-archive')->query( "RENAME TABLE `" . \IPSUtf8\Db::i('utf8-archive')->prefix . self::ARCHIVE_TABLE . "` TO `" . \IPSUtf8\Db::i('archive')->prefix . self::ARCHIVE_TABLE . "`" );
	}
}
