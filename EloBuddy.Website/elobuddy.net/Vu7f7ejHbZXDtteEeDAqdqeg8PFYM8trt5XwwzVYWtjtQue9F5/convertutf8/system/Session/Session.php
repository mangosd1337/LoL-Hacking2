<?php
/**
 * @brief		Session Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		6th Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8;

/**
 * Session Handler
 */
class Session
{
	/**
	 * @brief	Tables
	 */
	public $tables = NULL;
	
	/**
	 * @brief	Singleton Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief  Database Table Columns
	 */
	protected static $tableTableColumns = array(
		array(
			'name'    => 'table_name',
			'type'    => 'TEXT', 
			'length'  => false,
			'null'    => false,
			'default' => null
		),
		array(
			'name'    => 'table_schema',
			'type'    => 'MEDIUMTEXT', 
			'length'  => false,
			'null'    => true,
			'default' => null
		)
	);
	
	/**
	 * @brief  Database Table Columns
	 */
	protected static $sessionTableColumns = array(
		array(
			'name'    => 'session_start',
			'type'    => 'INT', 
			'length'  => 10,
			'null'    => false,
			'default' => 0
		),
		array(
			'name'    => 'session_updated',
			'type'    => 'INT', 
			'length'  => 10,
			'null'    => false,
			'default' => 0
		),
		array(
			'name'    => 'session_status',
			'type'    => 'VARCHAR', 
			'length'  => 255,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_current_charset',
			'type'    => 'VARCHAR', 
			'length'  => 255,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_current_table',
			'type'    => 'VARCHAR', 
			'length'  => 255,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_current_pkey',
			'type'    => 'VARCHAR', 
			'length'  => 255,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_current_row',
			'type'    => 'VARCHAR', 
			'length'  => 255,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_completed_json',
			'type'    => 'MEDIUMTEXT', 
			'length'  => false,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_json',
			'type'    => 'MEDIUMTEXT', 
			'length'  => false,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_has_archive',
			'type'    => 'INT', 
			'length'  => 1,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_processing_archive',
			'type'    => 'INT', 
			'length'  => 1,
			'null'    => true,
			'default' => 0
		),
		array(
			'name'    => 'session_is_ipb',
			'type'    => 'INT', 
			'length'  => 1,
			'null'    => true,
			'default' => 0
		),
	 );

	/**
	 * @brief	Data Store
	 */
	protected $_data = NULL;
		
	/**
	 * @brief	Changed Columns
	 */
	public $changed = array();

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
			self::$instance->init();
		}
		
		return self::$instance;
	}
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* Check to see if the session table exists */
		if ( file_exists( ROOT_PATH . '/conf_global.php' ) )
		{
			require( ROOT_PATH . '/conf_global.php' );
		}
		
		$setDefault = false;
		
		if ( ! \IPSUtf8\Db::i('utf8')->checkForTable( 'convert_session_tables' ) )
		{
			\IPSUtf8\Db::i('utf8')->createTable( array(
		 		'name'    => 'convert_session_tables',
		 		'columns' => static::$tableTableColumns
		 	) );
		 	
		 	$setDefault = true;
		}

		if ( ! \IPSUtf8\Db::i('utf8')->checkForTable( 'convert_session' ) )
		{
			\IPSUtf8\Db::i('utf8')->createTable( array(
		 		'name'    => 'convert_session',
		 		'columns' => static::$sessionTableColumns
		 	) );
		 	
		 	$setDefault = true;
		}
		else
		{
			try
			{
				$this->_data = \IPSUtf8\Db::i('utf8')->select( '*', 'convert_session' )->first();
			
				if ( ! isset( $this->_data['session_json'] ) )
				{
					$setDefault = true;
				}
			}
			catch( \UnderflowException $ex )
			{
				$setDefault = true;
			}
		}
		
		if ( $setDefault === true )
		{
			$this->reset();
		}
		
		/* Populate tables */
		foreach( \IPSUtf8\Db::i('utf8')->select( '*', 'convert_session_tables' ) as $row )
		{
			$this->tables[ $row['table_name'] ] = json_decode( $row['table_schema'], true );
		}
	}
	
	/**
	 * Reset the session and conversion process
	 */
	public function reset()
	{
		$charSet    = null;
		$ipbVersion = null;
	
		/* Is this an IPB? */
		if ( \IPSUtf8\Db::i()->checkForTable('core_applications' ) )
		{
			try
			{
				$row = \IPSUtf8\Db::i()->select( '*', 'core_applications', array( 'app_directory=?', 'core' ) )->first();
				
				if ( ! empty( $row['app_directory'] ) )
				{
					$ipbVersion = $row['app_long_version'];
				}
			}
			catch( \UnderflowException $ex ) { }
		}
	
		if ( $ipbVersion >= 40000 )
		{
			/* >= 4.0 is UTF-8 */
			$charSet = 'utf-8';
		}
		else if ( $ipbVersion !== null )
		{
			/* Attempt to grab current CHARSET if this is an IPB 3 */
			if ( \IPSUtf8\Db::i()->checkForTable('core_sys_conf_settings' ) )
			{
				try
				{
					$row = \IPSUtf8\Db::i()->select( '*', 'core_sys_conf_settings', array( 'conf_key=?', 'gb_char_set' ) )->first();
					
					if ( ! empty( $row['conf_key'] ) )
					{
						$charSet = ( $row['conf_value'] ) ? $row['conf_value'] : $row['conf_default'];
					}
				}
				catch( \UnderflowException $ex ) { }
			}
		}

		$tableData = $this->_getTables();
		
		\IPSUtf8\Db::i('utf8')->delete( 'convert_session_tables' );
		
		foreach( $tableData['tables'] as $name => $schema )
		{
			\IPSUtf8\Db::i('utf8')->insert( 'convert_session_tables', array(
				'table_name'   => $name,
				'table_schema' => json_encode( $schema )
			) );
		}
		
		$this->_data = array(
			'session_start'   		     => time(),
			'session_updated' 		     => time(),
	 		'session_status'      	     => null,
	 		'session_current_charset'    => strtolower( $charSet ),
	 		'session_current_table'      => null,
	 		'session_current_pkey'       => null,
	 		'session_current_row'	     => 0,
	 		'session_completed_json'     => json_encode( array() ),
	 		'session_json'  		     => json_encode( $tableData['data'] ),
	 		'session_has_archive'	     => 0,
	 		'session_processing_archive' => 0,
	 		'session_is_ipb'			 => $ipbVersion === null ? false : true
	 		
	 	);
	 	
		/* Change database collation */
		require( ROOT_PATH . '/conf_global.php' );
		
		if ( ! empty( $INFO['archive_remote_sql_host'] ) AND ! empty( $INFO['archive_remote_sql_database'] ) AND ! empty( $INFO['archive_remote_sql_user'] ) )
		{
			$this->_data['session_has_archive'] = 1;
		}
	
	 	\IPSUtf8\Db::i('utf8')->delete( 'convert_session' );
		\IPSUtf8\Db::i('utf8')->insert( 'convert_session', $this->_data );
	}
	
	/**
	 * Update 'all_tables'
	 *
	 * @return void
	 */
	public function updateTableData()
	{
		$tableData = $this->_getTables();
		
		\IPSUtf8\Db::i('utf8')->delete( 'convert_session_tables' );
		
		foreach( $tableData['tables'] as $name => $schema )
		{
			\IPSUtf8\Db::i('utf8')->insert( 'convert_session_tables', array(
				'table_name'   => $name,
				'table_schema' => json_encode( $schema )
			) );
		}
		
		/* Populate tables */
		foreach( \IPSUtf8\Db::i('utf8')->select( '*', 'convert_session_tables' ) as $row )
		{
			$this->tables[ $row['table_name'] ] = json_decode( $row['table_schema'], true );
		}
		
		$this->save();
	}
	
	/**
	 * Grab all tables in the database that we're going to convert
	 *
	 * @return array
	 */
	protected function _getTables()
	{
		/* Grab all tables to convert */
		$stmt = \IPSUtf8\Db::i()->prepare( "SHOW TABLES" );
		$stmt->execute();
		$stmt->bind_result( $tableName );
		
		$tables    = array();
		$allTables = array();
		$data      = array( 'version' => \IPSUtf8\Convert::VERSION_ID, 'tableCount' => 0, 'totalCount' => 0, 'convertedCount' => 0, 'charSets' => array() );
		
		if ( defined( 'FORCE_CONVERT' ) AND FORCE_CONVERT === TRUE )
		{
			$data['force_conversion'] = 1;
		}
		
		while ( $stmt->fetch() === true )
		{
			/* Skip x_utf_ prefixed tables in case we don't have a prefix set */
			if ( mb_substr( $tableName, 0, 6 ) === 'x_utf_' )
			{
				continue;
			}
			
			if ( mb_substr( $tableName, 0, 5 ) === 'orig_' )
			{
				continue;
			}
			
			if ( mb_substr( $tableName, 0, mb_strlen( \IPSUtf8\Db::i()->prefix ) ) === \IPSUtf8\Db::i()->prefix )
			{
				$tableNameNoPrefix = $tableName;
				
				if ( \IPSUtf8\Db::i()->prefix )
				{
					$tableNameNoPrefix = mb_substr( $tableName, mb_strlen( \IPSUtf8\Db::i()->prefix ) );
				}
				
				$tables[] = $tableNameNoPrefix;
			}
		}
		
		foreach( $tables as $table )
		{
			/* Get count */
			$row   = \IPSUtf8\Db::i()->query( "SELECT COUNT(*) as count FROM `" . \IPSUtf8\Db::i()->prefix . "{$table}`" )->fetch_assoc();
			$count = $row['count'];
			
			$data['totalCount'] += $count;
			$data['tableCount']++;
			
			$row = \IPSUtf8\Db::i()->query( "SHOW CREATE TABLE `" . \IPSUtf8\Db::i()->prefix . "{$table}`" )->fetch_assoc();
				
			if ( preg_match( '#\scharset=([a-z0-9]+?)(\s|$)#i', $row['Create Table'], $matches ) )
			{
				$tblCharset = $matches[1];
				
				$data['charSets'][ mb_strtolower( $tblCharset ) ][] = $table;
			}
			else
			{
				$row = \IPSUtf8\Db::i()->query( "SHOW TABLE STATUS WHERE Name='" . \IPSUtf8\Db::i()->prefix . "{$table}'" )->fetch_assoc();
				
				if ( isset( $row['Collation'] ) )
				{
					$tblCharset = mb_substr( $row['Collation'], 0, strpos( $row['Collation'], '_' ) );
					
					$data['charSets'][ mb_strtolower( $tblCharset ) ][] = $table;
				}
				else
				{
					$data['charSets'][ mb_strtolower( \IPSUtf8\Convert::i()->database_charset ) ][] = $table;
				}
			}
			
			/* Make sure we can JSON Encode this table... MySQL Comments with UTF8 characters can cause it to fail */
			$definition = \IPSUtf8\Db::i()->getTableDefinition( $table );
			
			try
			{
				$test = @json_encode( $definition );
			}
			catch( \ErrorException $e )
			{
				$definition = static::arrayWalkRecursive( $definition, function( $value ) { return utf8_encode( $value ); } );
			}
			
			/* If the collation is missing, let's do a table status check and see if we can figure it out */
			if ( empty( $definition['collation'] ) )
			{
				$status = \IPSUtf8\Db::i()->query( "SHOW TABLE STATUS WHERE Name='" . \IPSUtf8\Db::i()->prefix . "{$table}'" )->fetch_assoc();
				
				if( isset( $status['Collation'] ) )
				{
					$definition['collation'] = $status['Collation'];
				}
			}
			
			/* Get other data */
			$allTables[ $table ] = array(
				'name'       => $table,
				'definition' => $definition,
				'count'      => $count,
				'charset'    => $tblCharset
			);
		}
		
		return array( 'tables' => $allTables, 'data' => $data );
	}
	
	/**
	 * Save the state of the conversion
	 */
	public function __destruct()
	{
		$this->save();
	}
	
	/**
	 * Get value from data store
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( $key )
	{
		if ( mb_substr( $key, 0, 8 ) != 'session_' )
		{
			$key = 'session_' . $key;
		}
		
		if( isset( $this->_data[ $key ] ) )
		{
			if ( mb_substr( $key, -5 ) == '_json' AND ! is_array( $this->_data[ $key ] ) )
			{
				$this->_data[ $key ] = json_decode( $this->_data[ $key ], true );
			}
			
			return $this->_data[ $key ];
		}
				
		return NULL;
	}
	
	/**
	 * Magic isset method
	 *
	 * @param	string	$key	Key
	 */
	public function __isset( $key )
	{
		if ( mb_substr( $key, 0, 8 ) != 'session_' )
		{
			$key = 'session_' . $key;
		}
		
		return isset( $this->_data[ $key ] );
	}
	
	/**
	 * Set value in data store
	 *
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( $key, $value )
	{
		if ( mb_substr( $key, 0, 8 ) != 'session_' )
		{
			$key = 'session_' . $key;
		}
		
		if( array_key_exists( $key, $this->_data ) )
		{
			$this->_data[ $key ] = $value;
			$this->changed[ $key ] = $value;
		}
	}
	
	/**
	 * Time taken for conversion
	 *
	 * @param	bool	$formatted		Return the time formatted or in seconds
	 * @return	mixed
	 */
	public function timeTaken( $formatted=false )
	{
		$seconds = ( $this->_data['session_updated'] - $this->_data['session_start'] );
		
		if ( $formatted )
		{
			$s = $seconds % 60;
			$m = floor( ( $seconds % 3600 ) / 60 );
			$h = floor( ( $seconds % 86400 ) / 3600 );
			
			if ( $h )
			{
				return "{$h} hour(s), {$m} minute(s) and {$s} seconds.";
			}
			else
			{
				return "{$m} minute(s) and {$s} seconds.";
			}
		}
		else
		{
			return $seconds;
		}
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		$this->_data['session_updated'] = time();
		$insert = array( 'session_updated' => time() );
		
		if ( ! empty( $this->changed ) )
		{			
			/* JSON encode if required */
			foreach ( array_merge( $this->_data, $this->changed ) as $k => $v )
			{
				$insert[ $k ] = ( mb_substr( $k, -5 ) == '_json' AND is_array( $v ) ) ? json_encode( $v, true ) : $v;
			}
		}
		else
		{
			foreach ( $this->_data as $k => $v )
			{
				$insert[ $k ] = ( mb_substr( $k, -5 ) == '_json' AND is_array( $v ) ) ? json_encode( $v, true ) : $v;
			}
		}
		
		/* Save */
		if ( count( $insert ) == count( static::$sessionTableColumns ) )
		{ 
			\IPSUtf8\Db::i('utf8')->update( 'convert_session', $insert );
		}
					
		/* Reset our log of what's changed */
		$this->changed = array();
		
	}

	/**
	 * Recursively apply a callback to an array - array_walk_recursive does not recurse into sub-sub-arrays, so we need a custom method
	 *
	 * @param	array		$array		The array
	 * @param	callback	$callback	The callback
	 * @return	array	The filtered array.
	 */
	public function arrayWalkRecursive( $array, $callback )
	{
		if ( ! is_array( $array ) )
		{
			trigger_error( "\$array is not an array in \IPSUtf8\Session::arrayWalkRecursive()", E_USER_ERROR );
		}
		
		foreach( $array AS $key => $value )
		{
			if ( is_array( $array[$key] ) )
			{
				$array[$key] = static::arrayWalkRecursive( $array[$key], $callback );
			}
			else
			{
				$array[$key] = $callback( $value );
			}
		}
		
		return $array;
	}
}