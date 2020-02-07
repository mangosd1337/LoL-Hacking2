<?php
/**
 * @brief		Database Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8;

/**
 * @brief	Database Class
 * @note	All functionality MUST be supported by MySQL 5.1.3 and higher. All references to the MySQL manual are therefore the 5.1 version.
 */
class Db extends \mysqli
{
	/**
	 * @brief	Datatypes
	 */
	public static $dataTypes = array(
		'database_column_type_numeric'	=> array(
			'TINYINT'	=> 'TINYINT [±127 ⊻ 255] [1B]',
			'SMALLINT'	=> 'SMALLINT [±3.3e4 ⊻ 6.6e4] [2B]',
			'MEDIUMINT'	=> 'MEDIUMINT [±8.4e6 ⊻ 1.7e7] [3B]',
			'INT'		=> 'INT [±2.1e9 ⊻ 4.3e9] [4B]',
			'BIGINT'	=> 'BIGINT [±9.2e18 ⊻ 1.8e19] [8B]',
			'DECIMAL'	=> 'DECIMAL',
			'FLOAT'		=> 'FLOAT',
			'BIT'		=> 'BIT',
			
		),
		'database_column_type_datetime'	=> array(
			'DATE'		=> 'DATE',
			'DATETIME'	=> 'DATETIME',
			'TIMESTAMP'	=> 'TIMESTAMP',
			'TIME'		=> 'TIME',
			'YEAR'		=> 'YEAR',
		),
		'database_column_type_string'	=> array(
			'CHAR'		=> 'CHAR [M≤6.6e4] [(M*w)B]',
			'VARCHAR'	=> 'VARCHAR [M≤6.6e4] [(L+(1∨2))B]',
			'TINYTEXT'	=> 'TINYTEXT [256B] [(L+1)B]',
			'TEXT'		=> 'TEXT [64kB] [(L+2)B]',
			'MEDIUMTEXT'=> 'MEDIUMTEXT [16MB] [(L+3)B]',
			'LONGTEXT'	=> 'LONGTEXT [4GB] [(L+4)B]',
			'BINARY'	=> 'BINARY [M≤6.6e4] [(M)B]',
			'VARBINARY'	=> 'VARBINARY [M≤6.6e4] [(L+(1∨2))B]',
			'TINYBLOB'	=> 'TINYBLOB [256B] [(L+1)B]',
			'BLOB'		=> 'BLOB [64kB] [(L+2)B]',
			'MEDIUMBLOB'=> 'MEDIUMBLOB [16MB] [(L+3)B]',
			'BIGBLOB'	=> 'BIGBLOB [4GB] [(L+4)B]',
			'ENUM'		=> 'ENUM [6.6e4] [(1∨2)B]',
			'SET'		=> 'SET [64] [(1∨2∨3∨4∨8)B]',
		)
	);

	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Return Query
	 */
	public $returnQuery = FALSE;

	/**
	 * Get instance
	 *
	 * @param	mixed	$identifier			Identifier
	 * @param	array	$connectionSettings	Connection settings (use when initiating a new connection)
	 * @return	IPSUtf8\Db
	 */
	public static function i( $identifier=NULL, $connectionSettings=array() )
	{
		/* Did we pass a null value? */
		$identifier = ( $identifier === NULL ) ? '__MAIN' : $identifier;
	
		/* Don't have an instance? */
		if( !isset( self::$multitons[ $identifier ] ) )
		{
			/* Load the default settings if necessary */
			if ( $identifier === '__MAIN' OR $identifier == 'utf8' )
			{
				require( ROOT_PATH . '/conf_global.php' );
				$connectionSettings = $INFO;
			}
			
			if ( isset( $connectionSettings['sql_tbl_prefix'] ) )
			{
				$connectionSettings['sql_tbl_prefix'] = str_replace( 'x_utf_', '', $connectionSettings['sql_tbl_prefix'] );
			}
			
			/* UTF8 Connection settings */
			if ( mb_substr( $identifier, 0, 4 ) == 'utf8' )
			{
				$connectionSettings['sql_tbl_prefix'] = 'x_utf_' . $connectionSettings['sql_tbl_prefix'];
			}
			
			/* Connect */
			$classname = get_called_class();
			self::$multitons[ $identifier ] = @new $classname(
				$connectionSettings['sql_host'],
				$connectionSettings['sql_user'],
				$connectionSettings['sql_pass'],
				$connectionSettings['sql_database'],
				( isset( $connectionSettings['sql_port'] ) and $connectionSettings['sql_port']) ? $connectionSettings['sql_port'] : NULL,
				( isset( $connectionSettings['sql_socket'] ) and $connectionSettings['sql_socket'] ) ? $connectionSettings['sql_socket'] : NULL
				);
			
			/* If the connection failed, throw an exception */	
			if( $error = mysqli_connect_error() )
			{
				throw new \IPSUtf8\Db\Exception( $error, self::$multitons[ $identifier ]->connect_errno );
			}
			
			/* If we succeeded, set the charset */
			if ( mb_substr( $identifier, 0, 4 ) == 'utf8' )
			{
				/* UTF8MB4? */
				if ( isset( $connectionSettings['sql_utf8mb4'] ) and $connectionSettings['sql_utf8mb4'] )
				{
					self::$multitons[ $identifier ]->charset = 'utf8mb4';
					self::$multitons[ $identifier ]->collation = 'utf8mb4_unicode_ci';
					self::$multitons[ $identifier ]->binaryCollation = 'utf8mb4_bin';
				}
				
				/* If we succeeded, set the charset */
				if ( !self::$multitons[ $identifier ]->set_charset( self::$multitons[ $identifier ]->charset ) )
				{
					/* Fallback to UTF8 */
					self::$multitons[ $identifier ]->charset = 'utf8';
					self::$multitons[ $identifier ]->collation = 'utf8_unicode_ci';
					self::$multitons[ $identifier ]->binaryCollation = 'utf8_bin';
					
					self::$multitons[ $identifier ]->set_charset( 'utf8' );
				}
			}
			
			/* Set the prefix */
			if ( isset( $connectionSettings['sql_tbl_prefix'] ) )
			{
				self::$multitons[ $identifier ]->prefix = $connectionSettings['sql_tbl_prefix'];
			}
		}
		
		/* Return */
		return self::$multitons[ $identifier ];
	}
	
	/**
	 * @brief	Charset
	 */
	protected $charset = 'utf8';
	
	/**
	 * @brief	Collation
	 */
	protected $collation = 'utf8_unicode_ci';
	
	/**
	 * @brief	Binary Collation
	 */
	protected $binaryCollation = 'utf8_bin';
	
	/**
	 * @brief	Default MySQL Engine
	 */
	protected $defaultEngine = NULL;

	/**
	 * @brief	Table Prefix
	 */
	public $prefix = '';
	
	/**
	 * Run a query
	 *
	 * @param	string	$query	The query
	 * @return	mixed
	 * @see		<a href="http://uk1.php.net/manual/en/mysqli.query.php">mysqli::query</a>
	 * @throws	\IPS\Db\Exception
	 */
	public function query( $query )
	{
		/* Should we return the query instead of executing it? */
		if( $this->returnQuery === TRUE )
		{
			$this->returnQuery	= FALSE;
			return $query;
		}

		$return = parent::query( $query );
		if ( $return === FALSE )
		{
			throw new \IPSUtf8\Db\Exception( $this->error, $this->errno );
		}
		return $return;
	}
	
	/**
	 * Apparently, get_charset can be unavailable
	 *
	 * @return string
	 */
	public function getCharset()
	{
		if ( method_exists( $this, 'get_charset' ) )
		{
			return static::get_charset()->charset;
		}
		else
		{
			return static::character_set_name();
		}
	}
	
	/**
	 * Overload this method so we can change the charset if required
	 *
	 * @param	string	Charset
	 */
	public function set_charset( $charset )
	{
		if ( mb_substr( $charset, 0, 4 ) == 'utf8' )
		{
			if ( $charset === 'utf8mb4' )
			{
				$this->charset         = 'utf8mb4';
				$this->collation       = 'utf8mb4_unicode_ci';
				$this->binaryCollation = 'utf8mb4_bin';
			}
			else
			{
				$this->charset			= 'utf8';
				$this->collation		= 'utf8_unicode_ci';
				$this->binaryCollation	= 'utf8_bin';
			}
		}
		
		return parent::set_charset( $charset );
	}
	
	/** 
	 * Run Prepared SQL Statement
	 *
	 * @param	string	$query	SQL Statement
	 * @param	array	$_binds	Variables to bind
	 * @return	\mysqli_stmt
	 */
	public function preparedQuery( $query, array $_binds )
	{
		/* Init Bind object */
		$bind = new Db\Bind();
		
		/* Sort out subqueries */
		$binds = array();
		$i = 0;
		foreach ( $_binds as $bindVal )
		{
			$i++;
			if ( $bindVal instanceof \IPSUtf8\Db\Select )
			{
				$pos = 0;
				for ( $j=0; $j<$i; $j++ )
				{
					$pos = mb_strpos( $query, '?', $pos ) + 1;
				}					
				$query = mb_substr( $query, 0, $pos - 1 ) . $bindVal->query . mb_substr( $query, $pos );
				$i--;
				
				foreach ( $bindVal->binds as $v )
				{
					$binds[] = $v;
				}
			}
			else
			{
				$binds[] = $bindVal;
			}
		}
		
		/* Loop values to bind */
		$i = 0;
		foreach ( $binds as $bindVal )
		{
			$i++;
			switch ( gettype( $bindVal ) )
			{
				case 'boolean':
				case 'integer':
					$bind->add( 'i', $bindVal );
					break;
					
				case 'double':
					$bind->add( 'd', $bindVal );
					break;
												
				case 'string':
					$bind->add( 's', $bindVal );
					break;
					
				case 'object':
					if( method_exists( $bindVal, '__toString' ) )
					{
						$bind->add( 's', (string) $bindVal );
						break;
					}
					// Deliberately no break
					
				case 'NULL':
				case 'array':
				case 'resource':
				case 'unknown type':
				default:
					/* For NULL values, you can't bind, so we adjust the query to actually pass a NULL value */
					$pos = 0;
					for ( $j=0; $j<$i; $j++ )
					{
						$pos = mb_strpos( $query, '?', $pos ) + 1;
					}					
					$query = mb_substr( $query, 0, $pos - 1 ) . 'NULL' . mb_substr( $query, $pos );
					$i--;					
					break;
			}
		}
		
		/* Add a backtrace to the query so we know where it came from if it causes issues */
		$comment = '??';
		$line = '?';
		foreach( debug_backtrace( FALSE ) as $b )
		{
			if ( isset( $b['line'] ) )
			{
				$line = $b['line'];
			}
			
			if( isset( $b['class'] ) and $b['class'] !== 'IPSUtf8\_Db' )
			{
				$comment = "{$b['class']}::{$b['function']}:{$line}";
				break;
			}
		}
		$_query = $query;
		$query = "/*{$comment}*/ {$query}";
		
		/* Prepare */		
		$stmt = parent::prepare( $query );
		if( $stmt === FALSE )
		{
			throw new \IPSUtf8\Db\Exception( $this->error, $this->errno, NULL, $_query, $binds );
		}
		
		/* Bind values */
		if( $bind->haveBinds() === TRUE )
		{
			call_user_func_array( array( $stmt, 'bind_param' ), $bind->get() );
		}
		
		/* Execute */
		$stmt->execute();
		if ( $stmt->error )
		{
			throw new \IPSUtf8\Db\Exception( $stmt->error, $stmt->errno, NULL, $_query, $binds );
		}
		$stmt->store_result();
				
		/* Return a Statement object */
		return $stmt;
	}
	
	const SELECT_DISTINCT = 1;
	const SELECT_SQL_CALC_FOUND_ROWS = 2;
	
	/**
	 * Build SELECT statement
	 *
	 * @param	array|string		$columns	The columns (as an array) to select or an expression
	 * @param	array|string		$table		The table to select from. Either (string) table_name or (array) ( name, alias )
	 * @param	array|string|NULL	$where		WHERE clause (see example)
	 * @param	string|NULL			$order		ORDER BY clause
	 * @param	array|int			$limit		Rows to fetch or array( offset, limit )
	 * @param	string|NULL			$group		Column to GROUP BY
	 * @param	array|string|NULL	$having		HAVING clause (same format as WHERE clause)
	 * @param	int					$flags		Bitwise flags
	 *	@li	\IPSUtf8\Db::SELECT_DISTINCT			Will use SELECT DISTINCT
	 *	@li	\IPSUtf8\DB::SELECT_SQL_CALC_FOUND_ROWS	Will add SQL_CALC_FOUND_ROWS
	 * @return	\IPSUtf8\Db\Select
	 * 
	 */
	public function select( $columns=NULL, $table, $where=NULL, $order=NULL, $limit=NULL, $group=NULL, $having=NULL, $flags=0 )
	{
		$binds = array();
		$query = 'SELECT ';
		
		/* Flags */
		if ( $flags & static::SELECT_DISTINCT )
		{
			$query .= 'DISTINCT ';
		}
		if ( $flags & static::SELECT_SQL_CALC_FOUND_ROWS )
		{
			$query .= 'SQL_CALC_FOUND_ROWS ';
		}
		
		/* Columns */
		if ( is_string( $columns ) )
		{
			$query .= $columns;
		}
		else
		{
			$query .= implode( ', ', array_map( function( $col )
			{
				return '`' . $col . '`';
			}, $columns ) );
		}
		
		/* Tables */
		if ( is_array( $table ) )
		{
			$query .= " FROM `{$this->prefix}{$table[0]}` AS `{$table[1]}`";
		}
		else
		{
			$query .= " FROM `{$this->prefix}{$table}` AS `{$table}`";
		}
		
		/* WHERE */
		if ( $where )
		{
			$where = $this->compileWhereClause( $where );
			$query .= ' WHERE ' . $where['clause'];
			$binds = $where['binds'];
		}
		
		/* Group? */
		if( $group )
		{
			$query .= " GROUP BY `{$group}`";
		}
				
		/* Having? */
		if( $having )
		{
			$having = $this->compileWhereClause( $having );
			$query .= ' HAVING ' . $having['clause'];
			$binds = array_merge( $binds, $having['binds'] );
		}
		
		/* Order? */
		if( $order )
		{
			$query .= ' ORDER BY ' . $order;
		}
		
		/* Limit */
		if( $limit )
		{
			$query .= $this->compileLimitClause( $limit );
		}
		
		/* Return */
		return new \IPSUtf8\Db\Select( $query, $binds, $this );
	}
	
	/**
	 * Build UNION statement
	 *
	 * @param	array			$selects	Array of \IPSUtf8\Db\Select objects
	 * @param	string|NULL		$order		ORDER BY clause
	 * @param	array|int		$limit		Rows to fetch or array( offset, limit )
	 * @return	\IPSUtf8\Db|Select
	 */
	public function union( $selects, $order, $limit )
	{
		/* Combine selects */
		$query = array();
		$binds = array();
		foreach ( $selects as $s )
		{
			$query[] = '( ' . $s->query . ' )';
			$binds = array_merge( $binds, $s->binds );
		}
		$query = implode( ' UNION ', $query );
		
		/* Order? */
		if( $order )
		{
			$query .= ' ORDER BY ' . $order;
		}
		
		/* Limit */
		if( $limit )
		{
			$query .= $this->compileLimitClause( $limit );
		}
		
		/* Return */
		return new \IPSUtf8\Db\Select( $query, $binds, $this );
	}
	
	/**
	 * Run INSERT statement and return insert ID
	 *
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/insert.html'>INSERT Syntax</a>
	 * @param	string					$table				Table name
	 * @param	array|\IPSUtf8\Db\Select	$set			Values to insert or array of values to set for multiple rows (NB, if providing multiple rows, they MUST all contain the same columns) or a statement to do INSERT INTO SELECT FROM
	 * @param	bool					$odkUpdate			Append an ON DUPLICATE KEY UPDATE clause to the query.  Similar to the replace() method but updates if a record is found, instead of delete and reinsert.
	 * @see		\IPSUtf8\Db::replace()
	 * @param	bool					$bulkInsertNoPrep	Turns off prepared statements for bulk inserts
	 * @return	int
	 * @throws	\IPSUtf8\Db\Exception
	 */
	public function insert( $table, $set, $odkUpdate=FALSE, $bulkInsertNoPrep=FALSE, $tableDefinition=NULL )
	{
		/* Is a statement? */
		if ( $set instanceof \IPSUtf8\Db\Select )
		{
			$query = "INSERT " . ( $odkUpdate ? " IGNORE " : '' ) . " INTO `{$this->prefix}{$table}` " . $set->query;
			$binds = $set->binds;
			$odkUpdate = false;
		}
		
		/* Nope, normal array */
		else
		{
			/* Is this just one row? */
			foreach ( $set as $k => $v )
			{
				if ( !is_array( $v ) )
				{
					$set = array( $set );
				}
				break;
			}
			
			/* Compile */
			$columns = NULL;
			$values = array();
			$binds = array();
			foreach ( $set as $row )
			{
				if ( $columns === NULL )
				{
					$columns = array_map( function( $val ){ return "`{$val}`"; }, array_keys( $row ) );
				}
				
				if ( count( $set ) > 1 AND $bulkInsertNoPrep )
				{
					if ( $tableDefinition === NULL )
					{
						$tableDefinition = $this->getTableDefinition( $table );
					}
					
					foreach( $row as $k => $v )
					{
						if ( isset( $tableDefinition['definition']['columns'][ $k ] ) )
						{
							$isInt = in_array( \mb_strtolower( $tableDefinition['definition']['columns'][ $k ]['type'] ), array( 'integer', 'int', 'smallint', 'tinyint', 'mediumint', 'bigint', 'decimal', 'numeric', 'float', 'double' ) );
							
							if ( $isInt )
							{
								if ( empty( $v ) )
								{
									
									if ( ! empty( $tableDefinition['definition']['columns'][ $k ]['allow_null'] ) AND $v !== 0 )
									{
										$row[ $k ] = 'null';
									}
									else
									{
										$row[ $k ] = 0;
									}
								}
							}
							else
							{
								if ( empty( $v ) )
								{
									if ( ! empty( $tableDefinition['definition']['columns'][ $k ]['allow_null'] ) AND $v !== '' )
									{
										$row[ $k ] = 'null';
									}
									else
									{
										$row[ $k ] = "''";
									}
								}
								else
								{
									$row[ $k ] = "'" . $this->real_escape_string( $v ) . "'";
								}
							}
						}
						else
						{
							if ( ! ctype_digit( (string) $v ) )
							{
								if ( empty( $v ) )
								{
									$row[ $k ] = 'null';
								}
								else
								{
									$row[ $k ] = "'" . $this->real_escape_string( $v ) . "'";
								}
							}
						}
					}
					
					$values[] = '(' . implode( ', ', $row ) . ')';
				}
				else
				{
					$binds    = array_merge( $binds, array_values( $row ) );
					$values[] = '( ' . implode( ', ', array_fill( 0, count( $columns ), '?' ) ) . ' )';
				}
			}
			
			/* Construct query */
			$query = "INSERT INTO `{$this->prefix}{$table}` ( " . implode( ', ', $columns ) . ' ) VALUES ' . implode( ', ', $values );
		}
		
		/* Add "ON DUPLICATE KEY UPDATE" */
		if( $odkUpdate )
		{
			$query	.= " ON DUPLICATE KEY UPDATE " . implode( ', ', array_map( function( $val ){ return "{$val}=VALUES({$val})"; }, $columns ) );
		}
		
		/* Run */
		if ( count( $set ) > 1 AND $bulkInsertNoPrep )
		{
			$stmt = $this->query( $query );
			return $this->insert_id;
		}
		else
		{
			$stmt = $this->preparedQuery( $query, $binds );
			return $stmt->insert_id;
		}
	}
	
	/**
	 * Run REPLACE statament and return number of affected rows
	 *
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/replace.html'>REPLACE Syntax</a>
	 * @param	string	$table	Table name
	 * @param	array	$set	Values to insert
	 * @return	\IPSUtf8\Db\Statement
	 * @throws	\IPSUtf8\Db\Exception
	 */
	public function replace( $table, $set )
	{
		$columns = implode( ', ', array_map( function( $val ){ return "`{$val}`"; }, array_keys( $set ) ) );
		$query = "REPLACE INTO `{$this->prefix}{$table}` ( " . $columns . " ) VALUES ( " . implode( ', ', array_fill( 0, count( $set ), '?' ) ) . " )";

		$stmt = $this->preparedQuery( $query, array_values( $set ) );
		return $stmt->affected_rows;
	}
	
	/**
	 * Run UPDATE statement and return number of affected rows
	 *
	 * @see		\IPSUtf8\Db::build
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/update.html'>UPDATE Syntax</a>
	 * @param	string|array	$table		Table Name, or array( Table Name => Identifier )
	 * @param	string|array	$set		Values to set (keys should be the table columns) or pre-formatted SET clause
	 * @param	mixed			$where		WHERE clause (see \IPSUtf8\Db::build for details)
	 * @param	array			$joins		Tables to join (see \IPSUtf8\Db::build for details)
	 * @return	int
	 * @throws	\IPSUtf8\Db\Exception
	 */
	public function update( $table, $set, $where='', $joins=array() )
	{
		$binds = array();
		
		/* Work out table */
		$table = is_array( $table ) ? "`{$this->prefix}{$table[0]}` {$this->prefix}{$table[1]}" : "`{$this->prefix}{$table}`";
		
		/* Work out joins */
		foreach ( $joins as $join )
		{
			$type = ( isset( $join['type'] ) and in_array( strtoupper( $join['type'] ), array( 'LEFT', 'INNER', 'RIGHT' ) ) ) ? strtoupper( $join['type'] ) : 'LEFT';
			$_table = is_array( $join['from'] ) ? "`{$this->prefix}{$join['from'][0]}` {$this->prefix}{$join['from'][1]}" : $join['from'];
			
			$on = $this->compileWhereClause( $join['where'] );
			$binds = array_merge( $binds, $on['binds'] );

			$joins[] = "{$type} JOIN {$_table} ON {$on['clause']}";
		}
		$joins = empty( $joins ) ? '' : ( ' ' . implode( "\n", $joins ) );
		
		/* Work out SET clause */
		if ( is_array( $set ) )
		{
			$_set = array();
			foreach ( $set as $k => $v )
			{
				$_set[] = "`{$k}`=?";
				$binds[] = $v;
			}
			$set = implode( ',', $_set );
		}
		
		/* Compile where clause */
		if ( $where !== '' )
		{
			$_where = $this->compileWhereClause( $where );
			$where = 'WHERE ' . $_where['clause'];
			$binds = array_merge( $binds, $_where['binds'] );
		}
				
		/* Run it */
		$stmt = $this->preparedQuery( "UPDATE {$table} {$joins} SET {$set} {$where}", $binds );
		return $stmt->affected_rows;
	}
	
	/**
	 * Run DELETE statement and return number of affected rows
	 *
	 * @see		\IPSUtf8\Db::build
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/delete.html'>DELETE Syntax</a>
	 * @param	string				$table		Table Name
	 * @param	string|array|\IPSUtf8\Db\Statement|null	$where		WHERE clause (see \IPSUtf8\Db::build for details)
	 * @param	string|null			$order		ORDER BY clause (see \IPSUtf8\Db::build for details)
	 * @param	int|array|null		$limit		LIMIT clause (see \IPSUtf8\Db::build for details)
	 * @param	string|null			$statementColumn	If \IPSUtf8\Db\Statement is passed, this is the name of the column that results are being loaded from
	 * @return	\IPSUtf8\Db\Statement
	 * @throws	\IPSUtf8\Db\Exception
	 */
	public function delete( $table, $where=NULL, $order=NULL, $limit=NULL, $statementColumn=NULL )
	{
		/* Basic query */
		$query = "DELETE FROM `{$this->prefix}{$table}`";

		/* Is a statement? */
		if ( $where instanceof \IPSUtf8\Db\Statement )
		{
			$query .= ' WHERE ' . $statementColumn . ' IN(' . $where->query . ')';
			$binds = $where->binds;
		}

		/* Add where clause */
		else
		{
			$binds = array();
			if ( $where !== NULL )
			{
				$_where = $this->compileWhereClause( $where );
				$query .= ' WHERE ' . $_where['clause'];
				$binds = $_where['binds'];
			}
		}
		
		/* Order? */
		if( $order !== NULL )
		{
			$query .= ' ORDER BY ' . $order;
		}
		
		/* Limit */
		if( $limit !== NULL )
		{
			$query .= $this->compileLimitClause( $limit );
		}
		
		/* Run it */
		$stmt = $this->preparedQuery( $query, $binds );		
		return $stmt->affected_rows;
	}
			
	/**
	 * Compile WHERE clause
	 *
	 * @param	string|array	$data	See \IPSUtf8\Db::build for details
	 * @return	array	Array containing the WHERE clause and the values to be bound - array( 'clause' => '1=1', 'binds' => array() )
	 */
	public function compileWhereClause( $data )
	{
		$return = array( 'clause' => '1=1', 'binds' => array() );
		
		if( is_string( $data ) )
		{
			$return['clause'] = $data;
		}
		elseif ( is_array( $data ) and ! empty( $data ) )
		{
			if ( is_string( $data[0] ) )
			{
				$data = array( $data );
			}
		
			$clauses = array();
			foreach ( $data as $bit )
			{
				if( !is_array( $bit ) )
				{
					$clauses[] = $bit;
				}
				else
				{
					$clauses[] = array_shift( $bit );
					$return['binds'] = array_merge( $return['binds'], $bit );
				}
			}
			
			$return['clause'] = implode( ' AND ', $clauses );
		}
		
		return $return;
	}
	
	/**
	 * Compile LIMIT clause
	 *
	 * @param	int|array	$data	See \IPSUtf8\Db::build for details
	 * @return	string
	 */
	protected function compileLimitClause( $data )
	{
		$limit = NULL;
		if( is_array( $data ) )
		{
			$offset = intval( $data[0] );
			$limit  = intval( $data[1] );
		}
		else
		{
			$offset = intval( $data );
		}

		if( $limit !== NULL )
		{
			return " LIMIT {$offset},{$limit}";
		}
		else
		{
			return " LIMIT {$offset}";
		}
	}
	
	/**
	 * Compile column definition
	 *
	 * @code
	 	\IPSUtf8\Db::i()->compileColumnDefinition( array(
	 		'name'			=> 'column_name',		// Column name
	 		'type'			=> 'VARCHAR',			// Data type (do not specify length, etc. here)
	 		'length'		=> 255,					// Length. May be required or optional depending on data type.
	 		'decimals'		=> 2,					// Decimals. May be required or optional depending on data type.
	 		'values'		=> array( 0, 1 ),		// Acceptable values. Required for ENUM and SET data types.
	 		'null'			=> FALSE,				// (Optional) Specifies whether or not NULL vavlues are allowed. Defaults to TRUE.
	 		'default'		=> 'Default Value',		// (Optional) Default value
	 		'comment'		=> 'Column Comment',	// (Optional) Column comment
	 		'unsigned'		=> TRUE,				// (Optional) Will specify UNSIGNED for numeric types. Defaults to FALSE.
	 		'zerofill'		=> TRUE,				// (Optional) Will specify ZEROFILL for numeric types. Defaults to FALSE.
	 		'auto_increment'=> TRUE,				// (Optional) Will specify auto_increment. Defaults to FALSE.
	 		'binary'		=> TRUE,				// (Optional) Will specify BINARY for TEXT types. Defaults to FALSE.
	 		'primary'		=> TRUE,				// (Optional) Will specify PRIMARY KEY. Defaults to FALSE.
	 		'unqiue'		=> TRUE,				// (Optional) Will specify UNIQUE. Defaults to FALSE.
	 		'key'			=> TRUE,				// (Optional) Will specify KEY. Defaults to FALSE.
	 	) );
	 * @endcode
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/create-table.html'>MySQL CREATE TABLE syntax</a>
	 * @param	array	$data	Column Data (see \IPSUtf8\Db::createTable for details)
	 * @return	string
	 */	
	public function compileColumnDefinition( $data )
	{
		/* Specify name and type */
		$definition = "`{$data['name']}` {$data['type']} ";
		
		/* Some types specify length */
		if(
			in_array( $data['type'], array( 'VARCHAR', 'VARBINARY' ) )
			or
			(
				isset( $data['length'] ) and $data['length']
				and
				in_array( $data['type'], array( 'BIT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC', 'CHAR', 'BINARY' ) )
			)
		) {
			$definition .= "({$data['length']}";
			
			/* And some of those specify decimals (which may or may not be optional) */					
			if( in_array( $data['type'], array( 'REAL', 'DOUBLE', 'FLOAT' ) ) or ( in_array( $data['type'], array( 'DECIMAL', 'NUMERIC' ) ) and isset( $data['decimals'] ) ) )
			{
				$definition .= ',' . $data['decimals'];
			}
			
			$definition .= ') ';
		}
		
		/* Numeric types can be UNSIGNED and ZEROFILL */
		if( in_array( $data['type'], array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC' ) ) )
		{
			if( isset( $data['unsigned'] ) and $data['unsigned'] === TRUE )
			{
				$definition .= 'UNSIGNED ';
			}
			if( isset( $data['zerofill'] ) and $data['zerofill'] === TRUE )
			{
				$definition .= 'ZEROFILL ';
			}
		}
		
		/* ENUM and SETs have values */
		if( in_array( $data['type'], array( 'ENUM', 'SET' ) ) )
		{
			$values = array();
			foreach ( $data['values'] as $v )
			{
				$values[] = "'{$this->escape_string( $v )}'";
			} 
			
			$definition .= '(' . implode( ',', $values ) . ') ';
		}
				
		/* Some types can be binary or not */
		if( isset( $data['binary'] ) and $data['binary'] === TRUE and in_array( $data['type'], array( 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT' ) ) )
		{
			$definition .= 'BINARY ';
		}
		
		/* Text types specify a character set and collation */
		if( in_array( $data['type'], array( 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'ENUM', 'SET' ) ) )
		{
			$definition .= "CHARACTER SET {$this->charset} COLLATE {$this->collation} ";
		}
		
		/* NULL? */
		if( isset( $data['allow_null'] ) and $data['allow_null'] === FALSE )
		{
			$definition .= 'NOT NULL ';
		}
		else
		{
			$definition .= 'NULL ';
		}
				
		/* Default value */
		if( isset( $data['default'] ) and !in_array( $data['type'], array( 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BLOB', 'MEDIUMBLOB', 'BIGBLOB' ) ) )
		{
			$defaultValue = in_array( $data['type'], array( 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'INTEGER', 'BIGINT', 'REAL', 'DOUBLE', 'FLOAT', 'DECIMAL', 'NUMERIC', 'BIT' ) ) ? floatval( $data['default'] ) : ( ! in_array( $data['default'], array( 'CURRENT_TIMESTAMP' ) ) ? '\'' . $this->escape_string( $data['default'] ) . '\'' : $data['default'] );
			
			/* Strict Mode isn't nice */
			$toTest = trim( $defaultValue, "'" );
			if ( in_array( $data['type'], array( 'DATETIME' ) ) AND empty( $toTest ) ) # array here in case we need to expand later
			{
				$defaultValue = '\'0000-00-00 00:00:00\'';
			}
			
			$definition .= "DEFAULT {$defaultValue} ";
		}
		
		/* auto_increment? */
		if( isset( $data['auto_increment'] ) and $data['auto_increment'] === TRUE )
		{
			$definition .= 'AUTO_INCREMENT ';
		}
		
		/* Index? */
		if( isset( $data['primary'] ) )
		{
			$definition .= 'PRIMARY KEY ';
		}
		elseif( isset( $data['unique'] ) )
		{
			$definition .= 'UNIQUE ';
		}
		if( isset( $data['key'] ) )
		{
			$definition .= 'KEY ';
		}
		
		/* Comment */
		if( isset( $data['comment'] ) )
		{
			$definition .= "COMMENT '{$this->escape_string( $data['comment'] )}'";
		}
							
		/* Return */
		return $definition;
	}
	
	/**
	 * Compile index definition
	 *
	 * @code
	 	\IPSUtf8\Db::i()->compileIndexDefinition( array(
	 		'type'		=> 'key',				// "primary", "unique", "fulltext" or "key"
	 		'name'		=> 'index_name',		// Index name. Not required if type is "primary"
	 		'length'	=> 200,					// Index length (used when taking part of a text field, for example)
	 		'columns	=> array( 'column' )	// Columns to be in the index
	 	) );
	 * @endcode
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/create-index.html'>MySQL CREATE INDEX syntax</a>
	 * @see		\IPSUtf8\Db::createTable
	 * @param	array	$data	Index Data (see \IPSUtf8\Db::createTable for details)
	 * @return	string
	 */
	public function compileIndexDefinition( $data )
	{
		$definition = '';
		
		/* Specify type */
		switch ( $data['type'] )
		{
			case 'primary':
				$definition .= 'PRIMARY KEY ';
				break;
				
			case 'unique':
				$definition .= "UNIQUE KEY `{$data['name']}` ";
				break;
				
			case 'fulltext':
				$definition .= "FULLTEXT KEY `{$data['name']}` ";
				break;
				
			default:
				$definition .= "KEY `{$data['name']}` ";
				break;
		}
		
		/* Specify columns */
		$definition .= '(' . implode( ',', array_map( function ( $val, $len )
		{
			return ( ! empty( $len ) ) ? "`{$val}`({$len})" : "`{$val}`";
		}, $data['columns'], ( ( isset( $data['length'] ) AND is_array( $data['length'] ) ) ? $data['length'] : array_fill( 0, count( $data['columns'] ), null ) ) ) ) . ')';
		
		/* Return */
		return $definition;
	}
	
	/**
	 * Does table exist?
	 *
	 * @param	string	$name	Table Name
	 * @return	bool
	 */
	public function checkForTable( $name )
	{
		return ( $this->query( "SHOW TABLES LIKE '". $this->escape_string( "{$this->prefix}{$name}" ) . "'" )->num_rows > 0 );
	}
	
	/**
	 * Does index exist?
	 *
	 * @param	string	$name	Table Name
	 * @param	string	$index	Index Name
	 * @return	bool
	 */
	public function checkForIndex( $name, $index )
	{
		return ( $this->query( "SHOW INDEXES FROM ". $this->escape_string( "{$this->prefix}{$name}" ) . " WHERE Key_name LIKE '". $this->escape_string( $index ) . "'" )->num_rows > 0 );
	}
	
	/**
	 * Create Table
	 *
	 * @code
	 	\IPSUtf8\Db::createTable( array(
	 		'name'			=> 'table_name',	// Table name
	 		'columns'		=> array( ... ),	// Column data - see \IPSUtf8\Db::compileColumnDefinition for details
	 		'indexes'		=> array( ... ),	// (Optional) Index data - see \IPSUtf8\Db::compileIndexDefinition for details
	 		'comment'		=> '...',			// (Optional) Table comment
	 		'engine'		=> 'MEMORY',		// (Optional) Engine to use - will default to not specifying one, unless a FULLTEXT index is specified, in which case MyISAM is forced
	 		'temporary'		=> TRUE,			// (Optional) Will sepcify CREATE TEMPORARY TABLE - defaults to FALSE
	 		'if_not_exists'	=> TRUE,			// (Optional) Will sepcify CREATE TABLE name IF NOT EXISTS - defaults to FALSE
	 	) );
	 * @endcode 
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/create-table.html'>MySQL CREATE TABLE syntax</a>
	 * @see		\IPSUtf8\Db::compileColumnDefinition
	 * @see		\IPSUtf8\Db::compileIndexDefinition
	 * @param	array	$data	Table Definition (see code sample for details)
	 * @throws	\IPSUtf8\Db\Exception
	 * @return	void
	 */
	public function createTable( $data )
	{
		/* Start with a basic CREATE TABLE */
		$query = 'CREATE ';
		if( isset( $data['temporary'] ) and $data['temporary'] === TRUE )
		{
			$query.= 'TEMPORARY ';
		}
		$query .= 'TABLE ';
		if( isset( $data['if_not_exists'] ) and $data['if_not_exists'] === TRUE )
		{
			$query.= 'IF NOT EXISTS ';
		}
				
		/* Add in our create definition */
		$query .= "`{$this->prefix}{$data['name']}` (\n\t";
		$createDefinitons = array();
		foreach ( $data['columns'] as $field )
		{
			$createDefinitons[] = $this->compileColumnDefinition( $field );
		}
		if( isset( $data['indexes'] ) )
		{
			foreach ( $data['indexes'] as $index )
			{
				if( $index['type'] === 'fulltext' )
				{
					$data['engine'] = 'MYISAM';
				}
				$createDefinitons[] = $this->compileIndexDefinition( $index );
			}
		}
		$query .= implode( ",\n\t", $createDefinitons );
		$query .= "\n)\n";
		
		/* Specifying a particular engine? */
		if( isset( $data['engine'] ) and $data['engine'] )
		{
			$query .= "ENGINE {$data['engine']} ";
		}
		
		/* Specify UTF8 */
		$query .= "CHARACTER SET {$this->charset} COLLATE {$this->collation} ";
		
		/* Add comment */
		if( isset( $data['comment'] ) )
		{
			$query .= "COMMENT '{$this->escape_string( $data['comment'] )}'";
		}

		/* Do it */
		return $this->query( $query );
	}
	
	/**
	 * Rename table
	 *
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/rename-table.html'>
	 * @param	string	$oldName	The current table name
	 * @param	string	$newName	The new name
	 * @return	void
	 */
	public function renameTable( $oldName, $newName )
	{
		return $this->query( "RENAME TABLE `{$this->prefix}{$this->escape_string( $oldName )}` TO `{$this->prefix}{$this->escape_string( $newName )}`" );
	}
	
	/**
	 * Alter Table
	 * Can only update the comment and engine
	 *
	 * @param	string			$table		Table name
	 * @param	string|null		$comment	Table comment. NULL to not change
	 * @param	string|null		$engine		Engine to use. NULL to not change
	 * @return	void
	 */
	public function alterTable( $table, $comment=NULL, $engine=NULL )
	{
		if ( $comment === NULL and $engine === NULL )
		{
			return;
		} 
		
		$query = "ALTER TABLE `{$this->prefix}{$this->escape_string( $table )}` ";
		if ( $comment !== NULL )
		{
			$query .= "COMMENT='{$this->escape_string( $comment )}' ";
		}
		if ( $engine !== NULL )
		{
			$query .= "ENGINE={$engine}";
		}
				
		return $this->query( $query );
	}
	
	/**
	 * Drop table
	 *
	 * @see		<a href='http://dev.mysql.com/doc/refman/5.1/en/drop-table.html'>DROP TABLE Syntax</a>
	 * @param	string|array	$table		Table Name(s)
	 * @param	bool			$ifExists	Adds an "IF EXISTS" clause to the query
	 * @param	bool			$temporary	Table is temporary?
	 * @return	void
	 */
	public function dropTable( $table, $ifExists=FALSE, $temporary=FALSE )
	{
		$prefix = $this->prefix;
		
		return $this->query(
			  'DROP '
			. ( $temporary ? 'TEMPORARY ' : '' )
			. 'TABLE '
			. ( $ifExists ? 'IF EXISTS ' :'' )
			. implode( ', ', array_map(
				function( $val ) use ( $prefix )
				{
					return '`' . $prefix . $val . '`';
				},
				( is_array( $table ) ? $table : array( $table ) )
			) )
		);
	}
	
	/**
	 * Get the table definition for an existing table
	 *
	 * @see		\IPSUtf8\Db::createTable
	 * @param	string	$table	Table Name
	 * @return	array	Table definition - see IPSUtf8\Db::createTable for details
	 * @throws	\OutOfRangeException
	 * @throws	\IPSUtf8\Db\Exception
	 */
	public function getTableDefinition( $table )
	{
		/* Set name */
		$definition = array(
			'name'		=> $table,
		);
	
		/* Fetch columns */
		$query = $this->query( "SHOW FULL COLUMNS FROM `{$this->prefix}" . $this->escape_string( $table ) . '`' );
		
		if ( $query->num_rows === 0 )
		{
			throw new \OutOfRangeException;
		}
		while ( $row = $query->fetch_assoc() )
		{
			/* Set basic information */
			$columnDefinition = array(
				'name' => $row['Field'],
				'type'		=> '',
				'length'	=> 0,
				'decimals'	=> NULL,
				'values'	=> array()
			);
				
			/* Parse the type */
			if( mb_strpos( $row['Type'], '(' ) !== FALSE )
			{
				/* First, we need to protect the enum options as they may have spaces before splitting */
				preg_match( '/(.+?)\((.+?)\)/', $row['Type'], $matches );
				$options = $matches[2];
				$type = preg_replace( '/(.+?)\((.+?)\)/', "$1(___TEMP___)", $row['Type'] );
				$typeInfo = explode( ' ', $type );
				$typeInfo[0] = str_replace( "___TEMP___", $options, $typeInfo[0] );

				/* Now we match out the options */
				preg_match( '/(.+?)\((.+?)\)/', $typeInfo[0], $matches );
				$columnDefinition['type'] = mb_strtoupper( $matches[1] );
				
				if( $columnDefinition['type'] === 'ENUM' or $columnDefinition['type'] === 'SET' )
				{
					preg_match_all( "/'(.+?)'/", $matches[2], $enum );
					$columnDefinition['values'] = $enum[1];
				}
				else
				{						
					$lengthInfo = explode( ',', $matches[2] );
					$columnDefinition['length'] = intval( $lengthInfo[0] );
					if( isset( $lengthInfo[1] ) )
					{
						$columnDefinition['decimals'] = intval( $lengthInfo[1] );
					}
				}
			}
			else
			{
				$typeInfo = explode( ' ', $row['Type'] );

				$columnDefinition['type'] = mb_strtoupper( $typeInfo[0] );
				$columnDefinition['length'] = 0;
			}
			
			/* unsigned? */
			$columnDefinition['unsigned'] = in_array( 'unsigned', $typeInfo );
			
			/* zerofill? */
			$columnDefinition['zerofill'] = in_array( 'zerofill', $typeInfo );
			
			/* binary? */
			$columnDefinition['binary'] = ( $row['Collation'] === $this->binaryCollation );
			
			/* Allow NULL? */
			$columnDefinition['allow_null'] = ( $row['Null'] === 'YES' );
						
			/* Default value */
			$columnDefinition['default'] = $row['Default'];
			//if ( $columnDefinition['default'] === NULL and $columnDefinition['type'] != 'DATETIME' and !$columnDefinition['allow_null'] and mb_strpos( $row['Extra'], 'auto_increment' ) === FALSE )
			//{
			//	$columnDefinition['default'] = '';
			//}
			
			/* auto_increment */
			$columnDefinition['auto_increment'] = mb_strpos( $row['Extra'], 'auto_increment' ) !== FALSE;
			
			/* Comment */
			$columnDefinition['comment'] = $row['Comment'] ?: '';
			
			/* Collation */
			$columnDefinition['collation'] = $row['Collation'] ?: NULL;
			
			/* Add it in the definition */
			ksort( $columnDefinition );
			$definition['columns'][ $columnDefinition['name'] ] = $columnDefinition;
		}
		
		/* Fetch indexes */
		$indexes = array();
		$query = $this->query( "SHOW INDEXES FROM `{$this->prefix}{$table}`" );
		while ( $row = $query->fetch_assoc() )
		{
			$length = ( isset( $row['Sub_part'] ) AND ! empty( $row['Sub_part'] ) ) ? intval( $row['Sub_part'] ) : null;
			
			if( isset( $indexes[ $row['Key_name'] ] ) )
			{
				$indexes[ $row['Key_name'] ]['length'][]  = $length;
				$indexes[ $row['Key_name'] ]['columns'][] = $row['Column_name'];
			}
			else
			{
				$type = 'key';
				if( $row['Key_name'] === 'PRIMARY' )
				{
					$type = 'primary';
				}
				elseif( $row['Index_type'] === 'FULLTEXT' )
				{
					$definition['engine'] = 'MYISAM';
					$type = 'fulltext';
				}
				elseif( !$row['Non_unique'] )
				{
					$type = 'unique';
				}
				
				$indexes[ $row['Key_name'] ] = array(
					'type'		=> $type,
					'name'		=> $row['Key_name'],
					'length'	=> array( $length ),
					'columns'	=> array( $row['Column_name'] )
					);
			}
		}
		$definition['indexes'] = $indexes;
		
		/* Finally, get the table comment */
		$row = $this->query( "SHOW TABLE STATUS LIKE '{$table}'" )->fetch_assoc();
		
		$definition['comment']   = $row['Comment'];
		$definition['collation'] = $row['Collation'];
		$definition['engine']	 = $row['Engine'];
		
		if ( ! isset( $definition['engine'] ) )
		{
			$definition['engine'] = $this->defaultEngine();
		}
		
		/* Return */
		return $definition;
	}
	
	/**
	 * Fetches the default engine
	 *
	 * @return	string
	 */
	public function defaultEngine()
	{
		if ( $this->defaultEngine === NULL )
		{
			/* If this is an IPB, we should use what is defined in conf_global.php */
			if ( file_exists( ROOT_PATH . '/conf_global.php' ) )
			{
				require( ROOT_PATH . '/conf_global.php' );
				if ( isset( $INFO['mysql_tbl_type'] ) )
				{
					if ( mb_strtolower( $INFO['mysql_tbl_type'] ) == 'myisam' )
					{
						$this->defaultEngine = 'MyISAM';
					}
					else
					{
						$this->defaultEngine = 'InnoDB';
					}
					
					return $this->defaultEngine;
				}
			}
			
			$query = $this->query( "SHOW ENGINES" );
			
			while ( $row = $query->fetch_assoc() )
			{
				if ( \mb_strtolower( $row['Support'] ) === 'default' )
				{
					$this->defaultEngine = \mb_strtolower( $row['Engine'] );
					break;
				}
			}
		}
		
		return $this->defaultEngine;
	}
	
	/**
	 * Add column to table in database
	 *
	 * @see		\IPSUtf8\Db::compileColumnDefinition
	 * @param	string	$table			Table name
	 * @param	array	$definition		Column Definition (see \IPSUtf8\Db::compileColumnDefinition for details)
	 * @return	void
	 */
	public function addColumn( $table, $definition )
	{
		return $this->query( "ALTER TABLE `{$this->prefix}{$this->escape_string( $table )}` ADD COLUMN {$this->compileColumnDefinition( $definition )}" );
	}
	
	/**
	 * Modify an existing column
	 *
	 * @see		\IPSUtf8\Db::compileColumnDefinition
	 * @param	string	$table			Table name
	 * @param	string	$column			Column name
	 * @param	array	$definition		New column definition (see \IPSUtf8\Db::compileColumnDefinition for details)
	 * @return	void
	 */
	public function changeColumn( $table, $column, $definition )
	{
		return $this->query( "ALTER TABLE `{$this->prefix}{$this->escape_string( $table )}` CHANGE COLUMN `{$this->escape_string( $column )}` {$this->compileColumnDefinition( $definition )}" );
	}
	
	/**
	 * Drop a column
	 *
	 * @param	string	$table			Table name
	 * @param	string	$column			Column name
	 * @return	void
	 */
	public function dropColumn( $table, $column )
	{
		return $this->query( "ALTER TABLE `{$this->prefix}{$this->escape_string( $table )}` DROP COLUMN `{$this->escape_string( $column )}`;" );
	}
	
	/**
	 * Add index to table in database
	 *
	 * @see		\IPSUtf8\Db::compileIndexDefinition
	 * @param	string	$table			Table name
	 * @param	array	$definition		Index Definition (see \IPSUtf8\Db::compileIndexDefinition for details)
	 * @return	void
	 */
	public function addIndex( $table, $definition )
	{	
		return $this->query( "ALTER TABLE `{$this->prefix}{$this->escape_string( $table )}` ADD {$this->compileIndexDefinition( $definition )}" );
	}
	
	/**
	 * Modify an existing index
	 *
	 * @see		\IPSUtf8\Db::compileIndexDefinition
	 * @param	string	$table			Table name
	 * @param	string	$index			Index name
	 * @param	array	$definition		New index definition (see \IPSUtf8\Db::compileIndexDefinition for details)
	 * @return	void
	 */
	public function changeIndex( $table, $index, $definition )
	{
		$this->dropIndex( $table, $index );
		$this->addIndex( $table, $definition );
	}
	
	/**
	 * Drop an index
	 *
	 * @param	string	$table			Table name
	 * @param	string	$index			Column name
	 * @return	void
	 */
	public function dropIndex( $table, $index )
	{
		return $this->query( "ALTER TABLE `{$this->prefix}{$this->escape_string( $table )}` DROP INDEX `{$this->escape_string( $index )}`;" );
	}
	
	/**
	 * Find In Set
	 *
	 * @param	string	$column	Column name
	 * @param	array	$values	Acceptable values
	 * @return 	string	Where clause
	 */
	public function in( $column, $values )
	{
		$where = array();
		$in	= array();
		
		foreach( $values as $i )
		{
			if ( $i and is_numeric( $i ) )
			{
				$where[] = "FIND_IN_SET(" . $i . "," . $column . ")";
			}
			else if ( $i and is_string( $i ) )
			{
				$in[] = "'" . $this->real_escape_string( $i ) . "'";
			}
		}
		
		$return = array();
		
		if ( ! empty( $where ) )
		{
			$return[] = '( ' . implode( " OR ", $where ) . ' )';
		}
		
		if ( ! empty( $in ) )
		{
			$return[] = $column . ' IN(' . implode( ',', $in ) . ')';
		}
		
		if ( count( $return ) )
		{
			return '(' . implode( ' OR ', $return ) . ')';
		}
		else
		{
			return '1=1';
		}
	}

}