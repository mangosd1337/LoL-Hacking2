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

namespace IPSUtf8;

/**
 * Conversion class
 */
class Convert
{
	const VERSION_ID = '1.1.20';

	/**
	 * @brief	Data Store
	 */
	protected $_data = array();

	/**
	 * @brief	Instance
	 */
	protected static $instance = NULL;

	/**
	 * @brief	Convertable table fields
	 */
	protected static $convertCols = array( 'text', 'mediumtext', 'longtext', 'varchar', 'char', 'tinytext' );

	/**
	 * @brief	Numeric table fields
	 */
	protected static $numericCols = array( 'integer' => 11, 'int' => 11, 'smallint' => 6, 'tinyint' => 4, 'mediumint' => 8, 'bigint' => 20, 'decimal' => null, 'numeric' => null, 'float' => null, 'double' => null );

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
	 * @brief	Native Database object
	 */
	protected static $db = NULL;

	/**
	 * @brief	UTF-8 Database object
	 */
	protected static $utf = NULL;

	/**
	 * @brief	UTF-8 table engine
	 */
	protected static $utfTableEngine = NULL;

	/**
	 * @brief	Create don't populate
	 */
	protected static $createOnly = array( 'content_cache_posts', 'content_cache_sigs', 'sessions', 'topic_views', 'search_keywords' );
	
	/**
	 * @brief	Problem Tables that can cause an error due to serialization while using "fast" mode
	 */
	protected static $problemTables = array( 'cache_store' );

	/**
	 * @brief	MySQL supported character sets
	 */
	protected static $mysqlCharSets = NULL;

	/**
	 * Get instance
	 *
	 * @return	Output
	 */
	public static function i()
	{
		if ( self::$instance === NULL )
		{
			static::$db  = \IPSUtf8\Db::i();
			if ( defined( 'SOURCE_DB_CHARSET' ) AND SOURCE_DB_CHARSET !== NULL )
			{
				static::$db->set_charset( SOURCE_DB_CHARSET );
			}
			static::$utf = \IPSUtf8\Db::i('utf8');
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Get value from data store
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( $key )
	{
		if( isset( $this->_data[ $key ] ) )
		{
			return $this->_data[ $key ];
		}

		return NULL;
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
		if( array_key_exists( $key, $this->_data ) )
		{
			$this->_data[ $key ] = $value;
		}
	}

	/**
	 * Process a batch
	 *
	 * @param	int|null	Number of rows to limit per batch
	 * @return	boolean		True (I converted some) False (I didn't)
	 */
	public function process( $limit=null, $fromError=false )
	{
		/* MB4? */
		$sessionData = \IPSUtf8\Session::i()->json;

		if ( isset( $sessionData['use_utf8mb4'] ) AND $sessionData['use_utf8mb4'] )
		{
			static::$utf->set_charset( 'utf8mb4' );
		}

		if ( \IPSUtf8\Session::i()->current_table === NULL AND \IPSUtf8\Session::i()->is_ipb )
		{
			/* Lock tasks until the end of time so they do not attempt to add / remove things during conversion */
			if( static::$db->checkForTable('task_manager') )
			{
				static::$db->update( "task_manager", array( 'task_locked' => 2147483647 ) );
			}
			else
			{
				static::$db->update( "core_tasks", array( 'next_run' => 2147483647 ) );
			}
		}

		$convertCount   = 0;
		$table          = $this->getTable();
		$convertCols    = static::getConvertableColumns( $table['name'] );
		$numericCols    = static::getNumericColumns( $table['name'] );
		$tableCharSet   = \IPSUtf8\Convert::i()->database_charset;
		$currentCharSet = \IPSUtf8\Session::i()->current_charset;

		if ( ! empty( $table['charset'] ) )
		{
			$tableCharSet = $table['charset'];

			/* Latin1 is actually cp1252, not ISO-8859-1 @link http://dev.mysql.com/doc/refman/5.0/en/charset-we-sets.html */
			/* But only if our document character set is ISO-8859-1 or UTF-8. Otherwise, we're using the slow method which needs to real charset despite what the table is set too */
			if ( $tableCharSet === 'latin1' AND in_array( mb_strtolower( $currentCharSet ), array( 'iso-8859-1', 'utf-8' ) ) )
			{
				$currentCharSet = 'windows-1252';
			}
			
			if ( defined( 'FORCE_CONVERT_CHARSET' ) AND FORCE_CONVERT_CHARSET !== NULL )
			{
				$currentCharSet = FORCE_CONVERT_CHARSET;
			}
		}

		if ( !defined( 'SOURCE_DB_CHARSET' ) OR SOURCE_DB_CHARSET === NULL )
		{
			static::$db->set_charset( $tableCharSet );
		}

		/* Create table only */
		if ( IPB_LOCK and in_array( $table['name'], static::$createOnly ) )
		{
			static::log( 'No content conversion of ' . $table['name'] . ' required' );

			$this->getNextTable( $table['name'] );

			\IPSUtf8\Session::i()->status = 'processing';
			\IPSUtf8\Session::i()->json   = $sessionData;
			\IPSUtf8\Session::i()->save();

			return true;
		}

		while( $convertCount < $limit )
		{
			$sessionData  = \IPSUtf8\Session::i()->json;

			if ( $table === null )
			{
				if ( count( array_keys( \IPSUtf8\Session::i()->tables ) ) == count( array_keys( \IPSUtf8\Session::i()->completed_json ) ) )
				{
					\IPSUtf8\Session::i()->status = 'completed';
					\IPSUtf8\Session::i()->save();
					return false;
				}

				return false;
			}

			/* Got any fields to convert? */
			if ( count( $convertCols ) === 0 )
			{
				/* No data to convert, lets repopulate the easy way! */
				$this->preInsert( $table );

				static::$utf->delete( $table['name'] );
				static::$utf->insert( $table['name'], static::$db->select( '*', $table['name'] ) );

				$this->postInsert( $table );

				/* Update counts */
				$sessionData['convertedCount'] += $table['count'];
				$convertCount			       += $table['count'];

				static::log( 'No columns to convert in ' . $table['name'] . ' INSERT INTO FROM SELECT used' );

				$table        = $this->getNextTable( $table['name'] );
				$convertCols  = static::getConvertableColumns( $table['name'] );
				$numericCols  = static::getNumericColumns( $table['name'] );

				if ( $table === null or $convertCount >= $limit )
				{
					\IPSUtf8\Session::i()->status = 'processing';
					\IPSUtf8\Session::i()->json   = $sessionData;
					\IPSUtf8\Session::i()->save();

					return true;
				}
			}
			
			/* If the previous table had no columns to convert, then the table charset may be lost - reset it if it does not match */
			if ( $table !== NULL AND $tableCharSet !== $table['charset'] )
			{
				$tableCharSet = $table['charset'];
			}
			
			/* Are we reeeeeeeeally sure we know the charset? Let's check one more time */
			if ( ! $tableCharSet )
			{
				$createTable = static::$db->query( "SHOW CREATE TABLE `" . static::$db->prefix . $table['name'] . "`" )->fetch_assoc();
				
				if ( preg_match( '#\scharset=([a-z0-9]+?)(\s|$)#i', $createTable['Create Table'], $matches ) )
				{
					$tableCharSet = mb_strtolower( $matches[1] );
				}
			}

			/* Latin1 or ISO-8559-1? */
			if ( ! in_array( $table['name'], static::$problemTables ) AND ( in_array( $tableCharSet, $this->getMysqlCharSets() ) ) and ( \IPSUtf8\Session::i()->current_charset == 'utf-8' or \IPSUtf8\Session::i()->current_charset == 'iso-8859-1' ) AND ( !isset( $sessionData['force_conversion'] ) OR $sessionData['force_conversion'] !== 1 ) )
			{
				/* Convert the fast way! */
				$count = static::$utf->select( 'COUNT(*)', $table['name'] )->first();
				if ( $count > 0 )
				{
					/* TRUNCATE can hang so only do it if the table isn't empty (which it will be 99% of the time) */
					/* @see http://dba.stackexchange.com/questions/28055/truncate-table-statement-sometimes-hangs */
					static::log("TRUNCATE TABLE `" . static::$utf->prefix . $table['name'] . "`");

					static::$utf->query("TRUNCATE TABLE `" . static::$utf->prefix . $table['name'] . "`" );
				}

				/* Update counts */
				$sessionData['convertedCount'] += $table['count'];
				$convertCount			       += $table['count'];

				$select      	= array();
				$convertCols 	= static::getConvertableColumns( $table['name'] );
				$schematic		= static::getTableSchematic( $table['name'] );

				foreach( array_keys( $table['definition']['columns'] ) as $col )
				{
					if ( in_array( $col, $convertCols ) )
					{
						/* Even if the current charset is set to UTF-8, if the Table Charset is latin1, we need to use this method */
						/* ... but only if the actual column isn't using utf8 */
						$columnCollation = $schematic['definition']['columns'][$col]['collation'];
						if ( defined( 'UTF8_INSERT_ONLY' ) AND UTF8_INSERT_ONLY === TRUE AND $tableCharSet == 'utf8' )
						{
							$select[] = 'CAST( `' . $col . '` AS BINARY )';
						}
						else if ( \IPSUtf8\Session::i()->current_charset != 'utf-8' OR ( $tableCharSet != 'utf8' AND ! in_array( $columnCollation, array( 'utf8_unicode_ci', 'utf8mb4_unicode_ci' ) ) ) OR ( $tableCharSet == 'utf8' AND mb_substr( $columnCollation, 0, 5 ) == 'latin' ) )
						{
							if ( \IPSUtf8\Session::i()->current_charset == 'iso-8859-1' AND mb_substr( $columnCollation, 0, 5 ) == 'latin' )
							{
								/* Casting as binary truncates non-latin characters, so if we are using real Latin1, just convert */
								$select[] = 'CONVERT( `' . $col . '` USING utf8 )';
							}
							else
							{
								/* Otherwise cast to binary */
								$select[] = 'CONVERT( CAST(`' . $col . '` AS BINARY) USING utf8 )';
							}
						}
						else
						{
							/**
								This looks a little weird but bare with me. Semi-old databases (post-2.x but pre-4.x) can have UTF8 Tables and Columns, but contain Latin1 data.
								We need to try and sniff this out and apply a different type of conversion, because in this instance you cannot just extract
								as BINARY and then insert into the source. Instead, you need to convert to latin1, then to binary, then to utf8. But you can't
								do that on actual UTF8 data because it will then truncate multibyte characters so we need to compare the conversion against what
								is stored.
								Mind = blown.
							*/
							$collation = 'utf8_unicode_ci';
							$using = 'utf8';
							if ( $columnCollation == 'utf8mb4_unicode_ci' )
							{
								$collation = 'utf8mb4_unicode_ci';
								$using = 'utf8mb4';
							}
							
							$source_charset = 'latin1';
							if ( defined( 'SOURCE_DB_CHARSET' ) AND SOURCE_DB_CHARSET !== NULL )
							{
								$source_charset = SOURCE_DB_CHARSET;
							}
							
							$select[] = 'CASE WHEN STRCMP(CONVERT(CONVERT(CONVERT(`' . $col . '` USING ' . $source_charset . ') USING BINARY) USING ' . $using . '), `' . $col . '` COLLATE ' . $collation . ') = 0 THEN CONVERT(`' . $col . '` USING BINARY) ELSE CONVERT(CONVERT(CONVERT(`' . $col . '` USING ' . $source_charset . ') USING BINARY) USING ' . $using . ') END';
						}
					}
					else
					{
						$select[] = '`' . $col . '`';
					}
				}

				$this->preInsert( $table );

				$sql = "INSERT IGNORE INTO `" . static::$utf->prefix . $table['name'] . "` SELECT " . implode( ',', $select ) . " FROM `" . static::$db->prefix . $table['name'] . "`";

				static::log( $sql );

				static::$utf->query( $sql );

				$this->postInsert( $table );

				$table = $this->getNextTable( $table['name'] );

				\IPSUtf8\Session::i()->status = 'processing';
				\IPSUtf8\Session::i()->json   = $sessionData;
				\IPSUtf8\Session::i()->save();

				/* Always reset so we can test for no convertable columns above */
				return true;
			}

			/* If we have a work table, do we have a PKEY and a current row? */
			if ( \IPSUtf8\Session::i()->current_pkey )
			{
				$start = static::$utf->select('MAX(`' . \IPSUtf8\Session::i()->current_pkey . '`) as max', \IPSUtf8\Session::i()->current_table )->setKeyField('max')->first();

				$rows = static::$db->select(
							'*',
							$table['name'],
							array( \IPSUtf8\Session::i()->current_pkey . ' > ?', intval( $start ) ),
							\IPSUtf8\Session::i()->current_pkey . ' ASC',
							( $limit ? array( 0, ( $limit - $convertCount ) ) : null )
						);
			}
			else
			{
				$start = static::$utf->select('COUNT(*) as count', \IPSUtf8\Session::i()->current_table )->setKeyField('count')->first();

				/* Fetch via offset */
				$rows = static::$db->select(
							'*',
							$table['name'],
							null,
							null,
							( $limit ? array( $start, ( $limit - $convertCount ) ) : array( $start, 18446744073709551615 ) ) # No really, this is what MySQL recommends for offset, no limit (http://dev.mysql.com/doc/refman/5.1/en/select.html#id4651990)
						);
			}

			if ( !defined( 'FORCE_CONVERT_METHOD' ) OR FORCE_CONVERT_METHOD === NULL )
			{
				\IPSUtf8\Text\Charset::$method = 'internal';

				/* Optimise for latin character sets */
				if ( function_exists( 'mb_convert_encoding' ) )
				{
					if ( in_array( strtolower( $currentCharSet ), array_map( 'strtolower', mb_list_encodings() ) ) )
					{
						\IPSUtf8\Text\Charset::$method = 'mb';
					}
				}
			}

			$rowCount   = 0;
			$gotRows    = count( $rows );
			$batch      = array();
			$batchBytes = 0;
			$max        = ( $limit ) ?: 250;

			/* If we have a text column then the data set gets large */
			if ( $limit > 50 AND static::hasTextColumn( $table['name'] ) )
			{
				$max = 50;
			}

			/* Throttle inserts for other reasons? */
			$throttle = static::throttleInserts( $table['name'] );

			if ( $throttle !== false )
			{
				$max = ( $throttle < $limit ) ? $throttle : $limit;
			}

			/* Anything fetched? */
			if ( $gotRows === 0 )
			{
				try
				{
					static::$utf->query("ALTER TABLE `" . static::$utf->prefix . "{$table['name']}` ENABLE KEYS;");
					static::log( "{$table['name']} keys enabled." );
				}
				catch( \IPSUtf8\Db\Exception $e )
				{
					static::log( static::$utf->prefix . "{$table['name']} enable keys exception caught." );
				}
				
				$table        = $this->getNextTable( $table['name'] );
				$convertCols  = static::getConvertableColumns( $table['name'] );
				$numericCols  = static::getNumericColumns( $table['name'] );

				continue;
			}

			foreach( $rows as $row )
			{
				foreach( $convertCols as $col )
				{
					if ( isset( $row[ $col ] ) AND ! empty( $row[ $col ] ) )
					{
						$currentCharSet = ( defined( 'FORCE_CONVERT_CHARSET' ) AND FORCE_CONVERT_CHARSET != NULL ) ? FORCE_CONVERT_CHARSET : $currentCharSet;
						if ( static::isSerialized( $row[ $col ] ) AND \IPSUtf8\Text\Charset::i()->needsConverting( $row[ $col ], $currentCharSet, 'UTF-8' ) )
						{
							/* Store a copy in case it doesn't work */
							$original = $row[ $col ];
							$work = unserialize( $row[ $col ] );

							array_walk_recursive( $work, function( &$input, $key ) use ($currentCharSet)
							{
								$input = \IPSUtf8\Text\Charset::i()->convert( $input, $currentCharSet, 'UTF-8' );
							} );

							$row[ $col ] = serialize( $work );
							
							/* Did it work? */
							if ( ! @unserialize( $row[ $col ] ) )
							{
								/* Something went wrong... maybe we can figure it out */
								$work = unserialize( $original );
								array_walk_recursive( $work, function ( &$input, $key ) use ( $currentCharSet, $tableCharSet ) {
									if ( function_exists( 'mb_detect_encoding' ) )
									{
										$encoding = mb_detect_encoding( $input );
										$input = \IPSUtf8\Text\Charset::i()->convert( $input, $encoding, 'UTF-8' );
									}
									else
									{
										/* @todo expand */
										if ( $tableCharSet == 'latin1' )
										{
											$encoding = 'ISO-8859-1';
										}
										$input = \IPSUtf8\Text\Charset::i()->convert( $input, $encoding, 'UTF-8' );
									}
								} );
								
								$row[ $col ] = serialize( $work );
							}
						}
						else
						{
							$row[ $col ] = \IPSUtf8\Text\Charset::i()->convert( $row[ $col ], $currentCharSet, 'UTF-8' );
						}
					}
				}

				/* Numeric columns */
				foreach( $numericCols as $col )
				{
					if ( isset( $row[ $col ] ) AND ! empty( $row[ $col ] ) )
					{
						if ( $row[ $col ] === null OR ! is_numeric( $row[ $col ] ) )
						{
							$row[ $col ] = 0;
						}
					}
				}

				/* Roughly add the size of this insert in bytes */
				$tmp = $row; // Use a copy so the reference in array_walk doesn't overwrite the row to be inserted

				/* Make sure it's an associative array as topic_views only has 1 column so DB driver returns an indexed array only */
				if ( is_array( $tmp ) )
				{
					array_walk_recursive( $tmp, function( &$input, $key )
					{
						$input = htmlentities( (string) $input, ENT_QUOTES | ENT_IGNORE, 'utf-8', false );
					} );
				}

				$batchBytes += mb_strlen( @json_encode( $tmp ), '8bit');
				unset( $tmp );
				$batch[]     = $row;
				$nextBatch   = null;

				/* Give us an error margin to account for MySQL syntax */
				if ( $batchBytes > ( $this->_data['max_allowed_packet'] - ( ( $this->_data['max_allowed_packet'] / 100 ) * ( count( $batch ) * 0.3 ) ) ) )
				{
					/* Remove last row */
					$nextBatch = array_pop( $batch );

					static::log( "Max packet hit with " . $batchBytes . 'b with ' . count( $batch ) . ' rows' );
				}
				
				/* If we have moved the only row from $batch to $nextbatch, then process that now */
				if ( count( $nextBatch ) and ! count( $batch ) )
				{
					$batch     = $nextBatch;
					$nextBatch = array();
				}
				
				/* Got a batch to write? */
				if ( $nextBatch !== null OR ( count( $batch ) === $max ) OR ( $gotRows === count( $batch ) ) )
				{
					try
					{
						/* Optimise */
						$this->preInsert( $table );

						$insertId = static::$utf->insert( $table['name'], $batch, false, true, $table );

						if ( ! empty( static::$utf->error ) )
						{
							throw new \RuntimeException( static::$utf->error );
						}

						$this->postInsert( $table );

						/* Update counts */
						$sessionData['convertedCount'] += count( $batch );
						$convertCount += count( $batch );

						if ( \IPSUtf8\Session::i()->current_pkey )
						{
							\IPSUtf8\Session::i()->current_row = $insertId;
						}
						else
						{
							\IPSUtf8\Session::i()->current_row += count( $batch );
						}

						\IPSUtf8\Session::i()->status = 'processing';
						\IPSUtf8\Session::i()->json   = $sessionData;
						\IPSUtf8\Session::i()->save();

						static::log( count( $batch ) . " rows batch inserted using conversion method: " . \IPSUtf8\Text\Charset::$method . ', last insert ID ' . $insertId );

						$batch      = ( $nextBatch !== null ) ? array( $nextBatch ) : array();
						$batchBytes = 0;
					}
					catch( \IPSUtf8\Db\Exception $e )
					{
						$msg = $e->getMessage();

						\IPSUtf8\Session::i()->save();

						/* Trying to insert duplicate data, just let it go to the next in case there is a small overlap */
						if ( mb_stristr( $msg, 'duplicate entry' ) )
						{
							static::log( "Duplicate key failure on insert\n{$msg}\n" . var_export( $batch, true ) );
							return $this->heal( $limit, $convertCount );
						}
					}
				}
			}

			/* Anything fetched? */
			if ( $gotRows === 0 )
			{
				try
				{
					static::$utf->query("ALTER TABLE `" . static::$utf->prefix . "{$table['name']}` ENABLE KEYS;");
					static::log( "{$table['name']} keys enabled." );
				}
				catch( \IPSUtf8\Db\Exception $e )
				{
					static::log( static::$utf->prefix . "{$table['name']} enable keys exception caught." );
				}
				
				$table       = $this->getNextTable( $table['name'] );
				$convertCols = static::getConvertableColumns( $table['name'] );
			}
		}

		return true;
	}


	/**
	 * Pre insert
	 *
	 * @param	array	$table	Table definition data
	 * @return void
	 */
	public function preInsert( $table )
	{
		$row = static::$utf->query( "SHOW TABLE STATUS LIKE '" . static::$utf->prefix . $table['name'] . "'" )->fetch_assoc();
		static::$utfTableEngine	= $row['Engine'];

		if ( empty( static::$utfTableEngine ) )
		{
			static::$utfTableEngine = static::$utf->defaultEngine();
		}

		if ( \strtolower( static::$utfTableEngine ) === 'myisam' )
		{
			static::log( "Pre inserts for MyISAM table " . $table['name'] );

			try
			{
				//static::$utf->query("ALTER TABLE " . static::$utf->prefix . "{$table['name']} DISABLE KEYS;");
			}
			catch( \IPSUtf8\Db\Exception $e )
			{
				static::log( static::$utf->prefix . "{$table['name']} disable keys exception caught." );
			}
		}
		else if ( \strtolower( static::$utfTableEngine ) === 'innodb' )
		{
			static::log( "Pre inserts for InnoDB table " . $table['name'] );

			/* This doesn't seem necessary
			try
			{
				static::$utf->query("SET autocommit=0");
				static::$utf->query("SET unique_checks=0");
				static::$utf->query("SET foreign_key_checks=0");
			}
			catch ( Exception $e ) { }*/
		}
	}

	/**
	 * Post insert
	 *
	 * @param	array	$table	Table definition data
	 * @return void
	 */
	public function postInsert( $table )
	{
		if ( \strtolower( static::$utfTableEngine ) === 'myisam' )
		{
			static::log( "Post inserts for MyISAM table " . $table['name'] );

			try
			{
				//static::$utf->query("ALTER TABLE " . static::$utf->prefix . "{$table['name']} ENABLE KEYS;");
			}
			catch( \IPSUtf8\Db\Exception $e )
			{
				static::log( static::$utf->prefix . "{$table['name']} enable keys exception caught." );
			}
		}
		else if ( \strtolower( static::$utfTableEngine ) === 'innodb' )
		{
			static::log( "Post inserts for InnoDB table " . $table['name'] );
			
			/* This doesn't seem necessary
			try
			{
				static::$utf->query("SET autocommit=1");
				static::$utf->query("SET unique_checks=1");
				static::$utf->query("SET foreign_key_checks=1");
			}
			catch ( Exception $e ) { }*/
		}
	}

	/**
	 * Finish the conversion
	 *
	 * @return null
	 */
	public function finish()
	{
		$charset   = 'utf8';
		$collation = 'utf8_unicode_ci';

		/* MB4? */
		$sessionData = \IPSUtf8\Session::i()->json;

		if ( isset( $sessionData['use_utf8mb4'] ) AND ! empty( $sessionData['use_utf8mb4'] ) )
		{
			static::$utf->set_charset( 'utf8mb4' );

			$charset   = 'utf8mb4';
			$collation = 'utf8mb4_unicode_ci';
		}

		/* IPB 3? */
		if ( isset( \IPSUtf8\Session::i()->tables['core_sys_conf_settings'] ) AND isset( \IPSUtf8\Session::i()->tables['cache_store'] ) )
		{
			if ( static::$utf->checkForTable('core_sys_conf_settings' ) )
			{
				static::$utf->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_key=?', 'gb_char_set' ) );

				$rows = static::$utf->select( '*', 'core_sys_conf_settings', array( 'conf_add_cache=?', 1 ) );

				$settings = array();
				foreach( $rows as $row ) #row your boat
				{
					$value = $row['conf_value'] != "" ?  $row['conf_value'] : $row['conf_default'];

					if ( $value == '{blank}' )
					{
						$value = '';
					}

					$settings[ $row['conf_key'] ] = $value;
				}

				static::$utf->update( 'cache_store', array( 'cs_value' => serialize( $settings ) ), array( 'cs_key=?', 'settings' ) );
			}

			/* Update Language Locales */
			if ( static::$utf->checkForTable('core_sys_lang') )
			{
				/* Store current */
				$currentLocale = setlocale( LC_ALL, '0' );

				/* Loop through languages */
				$languages	= static::$utf->select( '*', 'core_sys_lang' );
				foreach( $languages AS $language )
				{
					$locale = explode( '.', $language['lang_short'] ); # We want to update even if a charset is already set.
					foreach( array( "{$locale[0]}.UTF8", "{$locale[0]}.UTF-8", "{$locale[0]}.utf8" ) AS $test )
					{
						$verify = setlocale( LC_ALL, $test );

						if ( $verify !== FALSE )
						{
							static::$utf->update( 'core_sys_lang', array( 'lang_short' => $test ), array( 'lang_id=?', $language['lang_id'] ) );
							break;
						}
					}
				}

				foreach( explode( ";", $currentLocale ) as $locale )
				{
					$parts = explode( "=", $locale );
					if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
					{
						setlocale( constant( $parts[0] ), $parts[1] );
					}
				}

				/* Clear Cache so it regenerates */
				static::$utf->update( 'cache_store', array( 'cs_value' => '' ), array( 'cs_key=?', 'lang_data' ) );
			}

			/* Unlock Tasks */
			if( static::$utf->checkForTable('task_manager') )
			{
				static::$utf->update( "task_manager", array( 'task_locked' => 0 ) );
			}
			else if( static::$utf->checkForTable('core_tasks') )
			{
				static::$utf->update( "core_tasks", array( 'next_run' => time() ) );
			}
		}

		/* Change database collation */
		require( ROOT_PATH . '/conf_global.php' );

		static::$utf->query( "ALTER DATABASE `" . $INFO['sql_database'] . "` DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation );
	}

	/**
	 * Restore the original tables.
	 *
	 * @return null
	 */
	public function restoreOriginalTables()
	{
		/* Grab all tables to convert */
		$stmt = static::$db->prepare( "SHOW TABLES" );
		$stmt->execute();
		$stmt->bind_result( $name );

		$tables = array();

		while ( $stmt->fetch() === true )
		{
			$tables[] = $name;
		}

		foreach( $tables as $name )
		{
			if ( mb_substr( $name, 0, 5 ) === 'orig_' )
			{
				$plainName = mb_substr( $name, 5 );

				static::$db->query( "DROP TABLE IF EXISTS `" . $plainName . "`" );

				$rename = "RENAME TABLE `" . $name . '` TO `' . $plainName . '`';

				static::$db->query( $rename );

				static::log( $rename );
			}
		}
	}

	/**
	 * Delete the original tables.
	 *
	 * @return null
	 */
	public function deleteOriginalTables()
	{
		/* Grab all tables to convert */
		$stmt = static::$utf->prepare( "SHOW TABLES" );
		$stmt->execute();
		$stmt->bind_result( $name );

		$tables = array();

		while ( $stmt->fetch() === true )
		{
			$tables[] = $name;
		}

		foreach( $tables as $name )
		{
			if ( mb_substr( $name, 0, 5 ) === 'orig_' )
			{
				static::$db->query( "DROP TABLE IF EXISTS `" . $name . "`" );

				static::log( "DROP TABLE IF EXISTS `" . $name . "`" );
			}
		}
	}

	/**
	 * Rename the tables
	 *
	 * @return null
	 */
	public function renameTables()
	{
		/* Grab all tables to convert */
		$stmt = static::$db->prepare( "SHOW TABLES" );
		$stmt->execute();
		$stmt->bind_result( $name );

		$tables     = array();
		$origPrefix = static::$db->prefix;

		if ( mb_substr( $origPrefix, 0, 6 ) === 'x_utf_' )
		{
			$origPrefix = mb_substr( $origPrefix, 6 );
		}

		while ( $stmt->fetch() === true )
		{
			$tables[] = $name;
		}

		foreach( $tables as $name )
		{
			$tableNameNoPrefix = $name;
			$isConvertedTable  = ( mb_substr( $name, 0, 6 ) === 'x_utf_' );
			$isOrigTable	   = ( mb_substr( $name, 0, 5 ) === 'orig_' );

			if ( $isOrigTable )
			{
				continue;
			}

			if ( ! $isConvertedTable and $origPrefix )
			{
				$tableNameNoPrefix = mb_substr( $name, mb_strlen( $origPrefix ) );
			}

			/* Rename original tables */
			if ( ( ! $isConvertedTable ) and mb_substr( $name, 0, mb_strlen( $origPrefix ) ) === $origPrefix )
			{
				static::$db->query( "RENAME TABLE `" . $origPrefix . $tableNameNoPrefix . '` TO `orig_' . $origPrefix . $tableNameNoPrefix . '`' );

				static::log( "RENAME TABLE `" . $origPrefix . $tableNameNoPrefix . '` TO `orig_' . $origPrefix . $tableNameNoPrefix . '`' );
			}

			/* Grab x_utf_ prefixed tables */
			if ( $isConvertedTable )
			{
				$tableName = mb_substr( $name, mb_strlen( static::$utf->prefix ) );

				/* Rename the new one */
				if ( $tableName !== 'convert_session' AND $tableName !== 'convert_session_tables' )
				{
					static::$utf->query( "RENAME TABLE `" . $name . '` TO `' . $origPrefix . $tableName . '`' );

					static::log( "RENAME TABLE `" . $name . '` TO `' . $origPrefix . $tableName . '`' );
				}
			}
		}

		/* Update table data */
		\IPSUtf8\Session::i()->updateTableData();
	}

	/**
	 * Go through the DB and fix the collation of UTF8 tables
	 *
	 * @return null
	 */
	public function fixCollation()
	{
		$charset   = 'utf8';
		$collation = 'utf8_unicode_ci';

		/* MB4? */
		$sessionData = \IPSUtf8\Session::i()->json;

		if ( isset( $sessionData['use_utf8mb4'] ) AND ! empty( $sessionData['use_utf8mb4'] ) )
		{
			static::$utf->set_charset( 'utf8mb4' );

			$charset   = 'utf8mb4';
			$collation = 'utf8mb4_unicode_ci';
		}

		/* Change database collation */
		require( ROOT_PATH . '/conf_global.php' );

		static::$utf->query( "ALTER DATABASE `" . $INFO['sql_database'] . "` DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation );

		foreach( \IPSUtf8\Session::i()->tables as $name => $data )
		{
			if ( in_array( $name, $this->getNonUtf8CollationTables() ) )
			{
				/* Skip if it's an _old table */
				if( mb_substr( $name, -4 ) == '_old' )
				{
					continue;
				}

				$cols      = static::getConvertableColumns( $data['name'] );
				$tableData = \IPSUtf8\Session::i()->tables[ $data['name'] ];

				if ( count( $cols ) )
				{
					$dropIndexes    = array();
					$addIndexes     = array();
					$modify         = array();
					$hasUnique		= FALSE;

					/* Changing collation can cause issues for duplicate entries in unique keys */
					if ( isset( $tableData['definition']['indexes'] ) )
					{
						foreach( $tableData['definition']['indexes'] as $key => $index )
						{
							if ( $index['type'] === 'unique' )
							{
								/* Make sure none of the columns in this index are auto_increment */
								foreach( $index['columns'] AS $k => $idxcol )
								{
									if ( $tableData['definition']['columns'][ $idxcol ]['auto_increment'] === TRUE )
									{
										continue 2;
									}
								}

								if ( static::$db->checkForIndex( $name, $key ) )
								{
									$hasUnique = TRUE;
									break;
								}
							}
						}
					}
				}

				/* We don't need to drop any indexes - just create a copy of the table and insert ignore */
				if ( $hasUnique === TRUE )
				{
					/* Let's try an alter first - below may not be necessary */
					try
					{
						static::$utf->query( "ALTER TABLE `" . static::$db->prefix . $name . "` CONVERT TO CHARACTER SET " . $charset . " COLLATE " . $collation );
						static::$utf->query( "ALTER TABLE `" . static::$db->prefix . $name . "` DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation );
					}
					catch( \IPSUtf8\Db\Exception $e )
					{
						/* Did not work - try the long way */
						static::$utf->query( "RENAME TABLE `" . static::$db->prefix . $name . "` TO `" . static::$db->prefix . $name . "_temp`" );
						static::log( $name . " has Unique Index - temp table created from main." );
						static::$utf->query( "CREATE TABLE `" . static::$db->prefix . $name . "` LIKE `" . static::$db->prefix . $name . "_temp`" );
						static::$utf->query( "ALTER TABLE `" . static::$db->prefix . $name . "` CONVERT TO CHARACTER SET " . $charset . " COLLATE " . $collation );
						static::$utf->query( "ALTER TABLE `" . static::$db->prefix . $name . "` DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation );
						static::log( $name . " created from temp table." );
						static::$utf->query( "REPLACE INTO `" . static::$db->prefix . $name . "` SELECT * FROM `" . static::$db->prefix . $name . "_temp`" );
						static::log( $name . " data copied to new table." );
						static::$utf->query( "DROP TABLE `" . static::$db->prefix . $name . "_temp`" );
						static::log( $name . "_temp dropped" );
					}
				}
				else
				{
					static::$utf->query( "ALTER TABLE `" . static::$db->prefix . $name . "` CONVERT TO CHARACTER SET " . $charset . " COLLATE " . $collation );
					static::$utf->query( "ALTER TABLE `" . static::$db->prefix . $name . "` DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation );
				}

				if ( count( $cols ) )
				{
					foreach( $cols as $col )
					{
						if ( isset( $tableData['definition']['columns'][ $col ] ) )
						{
							$colData = $tableData['definition']['columns'][ $col ];

							$modify[] = " MODIFY `" . $col . "` " .  $colData['type'] . ( ( is_numeric( $colData['length'] ) AND $colData['length'] > 0 ) ? "(" . $colData['length'] . ")" : '' ) . " CHARACTER SET " . $charset . " COLLATE " . $collation;
						}
					}
				}

				if ( count( $cols ) )
				{
					if ( count( $modify ) )
					{
						$query = "ALTER TABLE `" . static::$db->prefix . $name . "` " . implode( ',', $modify );

						static::log( $query );
						static::$utf->query( $query );
					}

					if ( /*count( $modify ) and*/ count( $addIndexes ) )
					{
						$query = "ALTER TABLE `" . static::$db->prefix . $name . "` " . implode( ',', $addIndexes );

						static::log( $query );
						static::$utf->query( $query );
					}
				}
			}
		}

		/* Update table data */
		\IPSUtf8\Session::i()->updateTableData();
		
		/* Update character set if we're in IP.Board */
		if ( static::$utf->checkForTable('core_sys_conf_settings' ) )
		{
			static::$utf->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_key=?', 'gb_char_set' ) );
		}
	}

	/**
	 * Something went wrong, so try and heal to restart progress
	 *
	 * @param	int		$limit			Process() method limit
	 * @param	int		$cycleCount		Items processed if this was in the middle of process()
	 * @return	bool|null
	 */
	public function heal( $limit=250, $cycleCount=0 )
	{
		if ( \IPSUtf8\Session::i()->current_table )
		{
			$table = \IPSUtf8\Session::i()->tables[ \IPSUtf8\Session::i()->current_table ];

			/* Table doesn't exist, so rewind back to the start of this table */
			if ( ! static::$utf->checkForTable( $table['name'] ) )
			{
				if ( static::$utf->createTable( $table['definition'] ) === false )
				{
					throw new \RuntimeException( static::$utf->error . "\n" . var_export( $table['definition'], true ) );
				}

				$completed = \IPSUtf8\Session::i()->completed_json;

				if ( isset( $completed[ \IPSUtf8\Session::i()->current_table ] ) )
				{
					unset( $completed[ \IPSUtf8\Session::i()->current_table ] );
				}

				/* Got a primary key so we can use a WHERE N > X query rather than a limit for efficiency? */
				if ( isset( $table['definition']['indexes']['PRIMARY'] ) )
				{
					$pkey = $table['definition']['indexes']['PRIMARY']['columns'][0];

					/* Is it numeric? */
					if ( mb_stristr( $table['definition']['columns'][ $pkey ]['type'], 'int' ) )
					{
						\IPSUtf8\Session::i()->current_pkey = $pkey;
					}
				}

				\IPSUtf8\Session::i()->status         = 'processing';
				\IPSUtf8\Session::i()->completed_json = $completed;
				\IPSUtf8\Session::i()->current_row    = 0;
				\IPSUtf8\Session::i()->save();

				/* Run again */
				return $this->process( ( $limit - $cycleCount ) );
			}

			if ( \IPSUtf8\Session::i()->current_pkey )
			{
				/* Get the latest row */
				$max = static::$utf->select('MAX(`' . \IPSUtf8\Session::i()->current_pkey . '`) as max', \IPSUtf8\Session::i()->current_table )->setKeyField('max')->first();
			}
			else
			{
				/* Get the count */
				$max = static::$utf->select('COUNT(*) as count', \IPSUtf8\Session::i()->current_table )->setKeyField('count')->first();
			}

			\IPSUtf8\Session::i()->status      = 'processing';
			\IPSUtf8\Session::i()->current_row = $max;
			\IPSUtf8\Session::i()->save();

			/* Run again */
			return $this->process( ( $limit - $cycleCount ), TRUE );
		}

		return null;
	}

	/**
	 * Set the current work table
	 *
	 * @return void
	 */
	public function getTable()
	{
		$table = null;

		if ( \IPSUtf8\Session::i()->current_table AND isset( \IPSUtf8\Session::i()->tables[ \IPSUtf8\Session::i()->current_table ] ) )
		{
			/* Table selected and conversion in progress */
			$table = \IPSUtf8\Session::i()->tables[ \IPSUtf8\Session::i()->current_table ];

			static::log( "Continuing with " . $table['name'] . ' (PKEY: ' . \IPSUtf8\Session::i()->current_pkey . ')' );

			if ( ! static::$utf->checkForTable( $table['name'] ) )
			{
				if ( static::$utf->createTable( $this->checkTable( $table['definition'] ) ) === false )
				{
					throw new \RuntimeException( static::$utf->error . "\n" . var_export( $table['definition'], true ) );
				}
				try
				{
					static::$utf->query("ALTER TABLE `" . static::$utf->prefix . "{$table['name']}` DISABLE KEYS;");
					static::log( "{$table['name']} keys disabled." );
				}
				catch( \IPSUtf8\Db\Exception $e )
				{
					static::log( static::$utf->prefix . "{$table['name']} disable keys exception caught." );
				}
			}

			return $table;
		}
		else
		{
			/* need to resolve an issue where if you only need to convert a few tables, these few will be named x_utf_ while others won't,
			   so for now, just convert all */
			$nonUtf8Tables = array_keys( \IPSUtf8\Session::i()->tables );//$this->getNonUtf8Tables();

			/* No table selected */
			if ( is_array( $nonUtf8Tables ) and count( $nonUtf8Tables ) )
			{
				if ( is_array( \IPSUtf8\Session::i()->completed_json ) )
				{
					$diff = array_diff( $nonUtf8Tables, array_keys( \IPSUtf8\Session::i()->completed_json ) );

					if ( count( $diff ) )
					{
						$table = \IPSUtf8\Session::i()->tables[ array_shift( $diff ) ];
					}
					else
					{
						return null;
					}
				}
				else
				{
					$table = array_shift( $nonUtf8Tables );
				}

				if ( isset( $table['name'] ) )
				{
					\IPSUtf8\Session::i()->current_table = $table['name'];

					/* Got a primary key so we can use a WHERE N > X query rather than a limit for efficiency? */
					if ( isset( $table['definition']['indexes']['PRIMARY'] ) )
					{
						$pkey = $table['definition']['indexes']['PRIMARY']['columns'][0];

						/* Is it numeric? */
						if ( mb_stristr( $table['definition']['columns'][ $pkey ]['type'], 'int' ) )
						{
							\IPSUtf8\Session::i()->current_pkey = $pkey;
						}
					}

					\IPSUtf8\Session::i()->save();

					/* Create the table for UTF8 goodness */
					static::$utf->dropTable( $table['name'], true );

					if ( static::$utf->createTable( $this->checkTable( $table['definition'] ) ) === false )
					{
						throw new \RuntimeException(  static::$utf->error . "\n" . var_export( $table['definition'], true ) );
					}

					static::log( "Created UTF8 table " . $table['name'] . ' (PKEY: ' . \IPSUtf8\Session::i()->current_pkey . ')' );

					try
					{
						static::$utf->query("ALTER TABLE `" . static::$utf->prefix . "{$table['name']}` DISABLE KEYS;");
						static::log( "{$table['name']} keys disabled." );
					}
					catch( \IPSUtf8\Db\Exception $e )
					{
						static::log( static::$utf->prefix . "{$table['name']} disable keys exception caught." );
					}

					return $table;
				}
			}
		}

		return null;
	}

	/**
	 * Attempt to fix issues with keys longer than 1000bytes
	 *
	 * @param	array	$definition		Table definition
	 * @return	array
	 */
	public function checkTable( $definition )
	{
		/* MB4? */
		$sessionData = \IPSUtf8\Session::i()->json;
		$length      = 0;
		$multiplier  = ( ! empty( $sessionData['use_utf8mb4'] ) ) ? 4 : 3;
		$needsFixing = array();
		$maxLen      = 1000;

		if ( \mb_strtolower( $definition['engine'] ) === 'innodb' )
		{
			$maxLen = 767;
		}

		if ( isset( $definition['indexes'] ) )
		{
			foreach( $definition['indexes'] as $key => $index )
			{
				foreach( $index['columns'] as $i => $column )
				{
					if ( isset( $definition['columns'][ $column ] ) and ( ( ! empty( $definition['columns'][ $column ]['length'] ) or in_array( mb_strtolower( $definition['columns'][ $column ]['type'] ), array( 'longtext', 'mediumtext', 'text' ) ) ) ) )
					{
						$length += (int) ( $definition['columns'][ $column ]['length'] ) ? $definition['columns'][ $column ]['length'] : 250;
					}
				}

				if ( $length * $multiplier > $maxLen )
				{
					foreach( $index['columns'] as $i => $column )
					{
						if ( isset( $definition['columns'][ $column ] ) and ( ( ! empty( $definition['columns'][ $column ]['length'] ) or in_array( mb_strtolower( $definition['columns'][ $column ]['type'] ), array( 'longtext', 'mediumtext', 'text' ) ) ) ) )
						{
							/* Column name, column length, column type  */
							$needsFixing[ $key ][ $i ] = array( $column, ( (int) $definition['columns'][ $column ]['length'] ) ? $definition['columns'][ $column ]['length'] : 250, $definition['columns'][ $column ]['type'] );
						}
					}
				}

				$length = 0;
			}
		}

		if ( count( $needsFixing ) )
		{
			foreach( $needsFixing as $key => $i )
			{
				$totalLength	= 0;
				$maxChars		= $maxLen / $multiplier;

				foreach( $i as $vals )
				{
					$totalLength += $vals[1];
				}

				if ( $totalLength > $maxChars )
				{
					/* Check each column can be reduced by the amount we need reducing */
					$debt = 0;
 
					$reduceEachBy = ( ( 100 / $totalLength ) * $maxChars ) / 100;

					/* Apply debt if we have any. We do not reduce integers */
					foreach( $i as $x => $vals )
					{
						if ( array_key_exists( mb_strtolower( $vals[2] ), static::$numericCols ) AND static::$numericCols[ mb_strtolower( $vals[2] ) ] !== NULL )
						{
							/* Don't go by "display" space, go by storage space, which is static regardless of supplied value ... an INT(4) still uses 11 chars */
							$debt += static::$numericCols[ mb_strtolower( $vals[2] ) ];
						}
					}

					/* Recalculate value to multiply index sub lengths with (subtracting debt) */
					$reduceEachBy = ( ( 100 / ( $totalLength - $debt ) ) * ( $maxChars - $debt ) ) / 100;

					foreach( $i as $x => $vals )
					{
						/* No length? */
						if ( empty( $vals[1] ) )
						{
							$vals[1] = 250;
						}

						if ( array_key_exists( mb_strtolower( $vals[2] ), static::$numericCols ) AND static::$numericCols[ mb_strtolower( $vals[2] ) ] !== NULL )
						{
							/* Preserve col len */
							continue;
						}

						$vals[1] = floor( $vals[1] * $reduceEachBy );	
						$i[ $x ] = $vals;
					}
				}

				foreach( $i as $x => $vals )
				{
					if ( $definition['columns'][ $definition['indexes'][ $key ]['columns'][ $x ] ]['length'] != $vals[1] )
					{
						$definition['indexes'][ $key ]['length'][ $x ] = $vals[1];
					}
				}
			}
		}

		return $definition;
	}

	/**
	 * Fetches the next table to process or false if nothing left to process
	 *
	 * @param	string	$currentTable		Name of table just processed
	 * @return	array	Array of table data
	 */
	public function getNextTable( $currentTable )
	{
		try
		{
			static::$utf->query("ALTER TABLE `" . static::$utf->prefix . "{$currentTable}` ENABLE KEYS;");
			static::log( "{$table['name']} keys enabled." );
		}
		catch( \IPSUtf8\Db\Exception $e )
		{
			static::log( static::$utf->prefix . "{$currentTable} enable keys exception caught." );
		}
		
		$completed   				= \IPSUtf8\Session::i()->completed_json;
		$completed[ $currentTable ] = $currentTable;

		\IPSUtf8\Session::i()->completed_json = $completed;
		\IPSUtf8\Session::i()->current_pkey   = null;
		\IPSUtf8\Session::i()->current_table  = null;
		\IPSUtf8\Session::i()->current_row    = 0;

		\IPSUtf8\Session::i()->save();

		return $this->getTable();
	}

	/**
	 * Init this class
	 */
	 public function init()
	 {
		/* Grab all tables to convert */
		$stmt = static::$db->query( "show variables" );

		$tables = array();

		while ( $row = $stmt->fetch_array( MYSQLI_ASSOC ) )
		{
			$key   = $row['Variable_name'];
			$value = $row['Value'];

			if ( $key == 'character_set_database' )
			{
				$this->_data['database_charset'] = strtolower( $value );
			}

			if ( $key == 'bulk_insert_buffer_size' )
			{
				$this->_data['bulk_insert_buffer_size'] = $value;
			}

			if ( $key == 'max_allowed_packet' )
			{
				$this->_data['max_allowed_packet'] = $value;
			}
		}

		if ( ! empty( $this->_data['max_allowed_packet'] ) AND ! empty( $this->_data['bulk_insert_buffer_size'] ) AND ( $this->_data['bulk_insert_buffer_size'] < $this->_data['max_allowed_packet'] ) )
		{
			$this->_data['max_allowed_packet'] = $this->_data['bulk_insert_buffer_size'];
		}

		if ( empty( $this->_data['max_allowed_packet'] ) AND empty( $this->_data['bulk_insert_buffer_size'] ) )
		{
			/* No value so use MySQL default of 1MB */
			$this->_data['max_allowed_packet'] = 1048576;
		}
	}

	/**
	 * Returns true if the table has a text or medium text column
	 *
	 * @param	string	$name		Name of table
	 * @return	boolean
	 */
	public static function hasTextColumn( $name )
	{
		$table = \IPSUtf8\Session::i()->tables[ $name ];

		foreach( $table['definition']['columns'] as $col => $val )
		{
			if ( in_array( mb_strtolower( $val['type'] ), array( 'mediumtext', 'text' ) ) )
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Tables with lots of dynamic columns can be slow
	 *
	 * @param	string	$name		Name of table
	 * @return	boolean
	 */
	public static function throttleInserts( $name )
	{
		$table = \IPSUtf8\Session::i()->tables[ $name ];
		$total = 0;
		$var   = 0;

		foreach( $table['definition']['columns'] as $col => $val )
		{
			$total++;

			if ( in_array( mb_strtolower( $val['type'] ), array( 'varchar' ) ) )
			{
				$var++;
			}
		}

		if ( $total > 70 )
		{
			return 50;
		}
		else if ( $total > 30 AND $var > 0 )
		{
			if ( ( $var / $total ) * 100 > 25 )
			{
				return 100;
			}
		}

		return false;
	}

	/**
	 * Returns whether the database is IPS4 already
	 *
	 * @return boolean
	 */
	public function databaseIsIPS4()
	{
		return (boolean) static::$db->checkForTable( 'core_members' );
	}
	
	/**
	 * Returns whether the DB is UTF8 already
	 *
	 * @return 	bool
	 */
	public function databaseIsUtf8()
	{
		$yeahProbably = false;

		if ( \IPSUtf8\Convert::i()->database_charset == 'utf8mb4' OR \IPSUtf8\Convert::i()->database_charset == 'utf8' OR \IPSUtf8\Session::i()->current_charset == 'utf-8' )
		{
			$yeahProbably = true;
		}

		/* Another check */
		if ( $yeahProbably === true )
		{
			/* Best check the tables, then */
			$json = \IPSUtf8\Session::i()->json;

			if ( count( $this->getNonUtf8Tables() ) )
			{
				$yeahProbably = false;
			}
		}

		return $yeahProbably;
	}

	/**
	 * Grab the character sets from MySQL
	 *
	 * @return array
	 */
	public function getMysqlCharSets()
	{
		if ( static::$mysqlCharSets === NULL )
		{
			static::$mysqlCharSets = array( 'latin1', 'utf8' );

			$stmt = static::$db->query( "show character set" );

			while ( $row = $stmt->fetch_array( MYSQLI_ASSOC ) )
			{
				static::$mysqlCharSets[] = $row['Charset'];
			}
		}

		return static::$mysqlCharSets;
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
			static::$nonUtf8Tables = array();

			/* Best check the tables, then */
			$json = \IPSUtf8\Session::i()->json;

			foreach( $json['charSets'] as $charSet => $tablesArray )
			{
				if ( ! empty( $json['force_conversion'] ) or ( $charSet != 'utf8' and $charSet != 'utf8mb4' ) )
				{
					static::$nonUtf8Tables = array_merge( static::$nonUtf8Tables, $tablesArray );
				}
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
			static::$rowsToConvert = 0;

			foreach( array_keys( \IPSUtf8\Session::i()->tables ) as $table )
			{
				static::$rowsToConvert += intval( \IPSUtf8\Session::i()->tables[ $table ]['count'] );
			}
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
			foreach( \IPSUtf8\Session::i()->tables as $table => $definition )
			{
				/* If the table itself is not utf8_unicode_ci, don't bother check columns */
				if ( $definition['definition']['collation'] AND !in_array( $definition['definition']['collation'], array( 'utf8_unicode_ci', 'utf8mb4_unicode_ci' ) ) )
				{
					static::$nonUtf8Collations[] = $table;
					continue;
				}

				foreach( $definition['definition']['columns'] as $name => $column )
				{
					if ( $column['collation'] and ! in_array( $column['collation'], array( 'utf8_unicode_ci', 'utf8mb4_unicode_ci' ) ) )
					{
						static::$nonUtf8Collations[] = $table;
						break;
					}
				}
			}
		}

		return static::$nonUtf8Collations;
	}

	/**
	 * Fetch debug information
	 *
	 * @return array
	 */
	public function getDebugString()
	{
		$data           = array();
		$tableCharSet   = \IPSUtf8\Convert::i()->database_charset;
		$currentCharSet = \IPSUtf8\Session::i()->current_charset;

		$json = \IPSUtf8\Session::i()->json;
		$data[] = "IP.Board Character Set: " . $currentCharSet;
		$data[] = "Database Character Set: " . $tableCharSet;
		$data[] = "Original table prefix: " . static::$db->prefix;
		$data[] = "Converted table prefix: " . static::$utf->prefix;

		foreach( $json['charSets'] as $charSet => $tablesArray )
		{
			$data[] = count( $tablesArray ) . ' tables are ' . $charSet;
		}

		$data[] = count( $this->getNonUtf8CollationTables() ) . " tables have incorrect collations";
		$data[] = "Can use 'dump' method: " . var_export( $this->canDump(), true );

		return $data;
	}

	/**
	 * Detect whether this is a serialized string or not
	 *
	 * @param	string	$string	The actual string
	 * @return	boolean
	 */
	public static function isSerialized( $string )
	{
		/* If it looks nothing like a serialized string, then return it */
		if ( ! preg_match( '#^a:\d+:\{#', $string ) )
		{
			return false;
		}

		/* Now make sure it's actually a serialized string */
		return ( boolean ) @unserialize( $string );
	}

	/**
	 * Log data
	 */
	public static function log( $message )
	{
		$file  = THIS_PATH . '/tmp/log_' . date('Y-m-d') . '.cgi';
		$isNew = false;

		if ( ! is_file( $file ) )
		{
			$isNew = true;
		}

		@file_put_contents( $file, "\n" . str_repeat( '-', 48 ) . "\n" . date('r') . "\n" . $message, FILE_APPEND );

		if ( $isNew )
		{
			@chmod( $file, 0777 );
		}
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

		$table  = \IPSUtf8\Session::i()->tables[ $name ];
		$return = array();

		foreach( $table['definition']['columns'] as $col => $val )
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

		$table  = \IPSUtf8\Session::i()->tables[ $name ];
		$return = array();

		foreach( $table['definition']['columns'] as $col => $val )
		{
			if ( in_array( mb_strtolower( $val['type'] ), static::$convertCols ) )
			{
				$return[] = $val['name'];
			}
		}

		return $return;
	}

	/**
	 * Can we use the fast dump method?
	 *
	 * @return boolean
	 */
	public static function canDump()
	{
		/* Best check the tables, then */
		$json     = \IPSUtf8\Session::i()->json;
		$tablesOk = true;

		foreach( $json['charSets'] as $charSet => $tablesArray )
		{
			if ( $charSet != 'utf8' and $charSet != 'utf8mb4' and mb_substr( $charSet, 0, 5) != 'latin' and $charSet != 'windows-1252' )
			{
				$tablesOk = false;
				break;
			}
		}

		if (
			( $tablesOk ) AND
			( \IPSUtf8\Session::i()->current_charset == 'utf-8' or \IPSUtf8\Session::i()->current_charset == 'iso-8859-1' ) AND
			( ( is_callable( 'exec' ) AND false === stripos( ini_get( 'disable_functions' ), 'exec' ) ) )
		)
		{
			try
			{
				@exec("iconv -l", $output );
			}
			catch( \ErrorException $e )
			{
				return false;
			}

			if ( ! array( $output ) or ! count( $output ) or ( ! stristr( implode( "\n", $output ), 'latin' ) ) )
			{
				return false;
			}

			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get Table Schematic
	 *
	 * @return	array
	 */
	protected static function getTableSchematic( $table )
	{
		return json_decode( \IPSUtf8\Db::i('utf8')->select( 'table_schema', 'convert_session_tables', array( "table_name=?", $table ) )->first(), TRUE );
	}
}
