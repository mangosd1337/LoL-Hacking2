<?php
/**
 * @brief		Database SELECT Statement
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Db;

/**
 * @brief	Database SELECT Statement
 */
class Select implements \Iterator, \Countable
{
	/**
	 * @brief	The query
	 */
	public $query;
	
	/**
	 * @brief	Binds
	 */
	public $binds;
	
	/**
	 * @brief	The database object
	 */
	protected $db;
	
	/**
	 * @brief	The statement
	 */
	protected $stmt;
	
	/**
	 * Constuctor
	 *
	 * @param	string	$query	The query
	 * @param	array	$binds	Binds
	 * @param	\IPSUtf8\Db	$db		The database object
	 * @return	void
	 */
	public function __construct( $query, array $binds, \IPSUtf8\Db $db )
	{
		$this->query	= $query;
		$this->binds	= $binds;
		$this->db		= $db;
	}
		
	/**
	 * Add Join
	 *
	 * @param	array|string 	$table The table to select from. Either (string) table_name or (array) ( name, alias )
	 * @param
	 * @return	void
	 */
	public function join( $table, $on, $type='LEFT', $using=FALSE )
	{
		$query = '';
		$joinConditionIsOptional = TRUE;
		
		switch ( $type )
		{
			case 'INNER':
			case 'CROSS':
				$query .= 'INNER JOIN ';
				break;
				
			case 'STRAIGHT_JOIN':
				$query .= 'STRAIGHT_JOIN';
				if ( $using )
				{
					throw new \InvalidArgumentException; // USING cannot be used with STRAIGHT_JOIN
				}
				break;
				
			case 'LEFT':
			case 'RIGHT':
				$query .= $type . ' JOIN';
				$joinConditionIsOptional = FALSE;
				break;
		}
		
		if ( is_array( $table ) )
		{
			$query .= " `{$this->db->prefix}{$table[0]}` AS `{$table[1]}`";
		}
		else
		{
			$query .= " `{$this->db->prefix}{$table}` AS `{$table}`";
		}
		
		if ( $on )
		{
			if ( $using )
			{
				$query .= ' USING ( ' . implode( ', ', array_map( function( $col )
				{
					return '`' . $col . '`';
				}, $on ) ) . ' ) ';
			}
			else
			{
				$where = $this->db->compileWhereClause( $on );
				$query .= ' ON ' . $where['clause'];
				$this->binds = array_merge( $where['binds'], $this->binds );
			}
		}
		elseif ( !$joinConditionIsOptional )
		{
			throw new \InvalidArgumentException;
		}
		
		$this->query = preg_replace( '/(FROM `.+?` AS `.+?`)/', '$1 ' . $query, $this->query );
		
		return $this;
	}
	
	/**
	 * @brief	Columns in the resultset
	 */
	protected $columns = array();
	
	/**
	 * @brief	Key Field
	 */
	protected $keyField = NULL;
	
	/**
	 * @brief	Value Field
	 */
	protected $valueField = NULL;
	
	/**
	 * Set key field
	 *
	 * @param	string	$column	Column to treat as the key
	 * @return	\IPSUtf8\Db\Select (for daisy=chaining)
	 */
	public function setKeyField( $column )
	{
		if ( !$this->stmt )
		{
			$this->rewind();
		}
		
		if ( !in_array( $column, $this->columns ) )
		{
			throw new \InvalidArgumentException;
		}
		
		$this->keyField = $column;
		
		return $this;
	}
	
	/**
	 * Set value field
	 *
	 * @param	string	$column	Column to treat as the key
	 * @return	\IPSUtf8\Db\Select (for daisy=chaining)
	 */
	public function setValueField( $column )
	{
		if ( !$this->stmt )
		{
			$this->rewind();
		}
		
		if ( !in_array( $column, $this->columns ) )
		{
			throw new \InvalidArgumentException;
		}
		
		$this->valueField = $column;
		
		return $this;
	}
	
	/**
	 * @brief	The current row
	 */
	protected $row;
	
	/**
	 * @brief	The current key
	 */
	protected $key;
	
	/**
	 * Get first record
	 *
	 * @return	array
	 * @throws	\UnderflowException
	 */
	public function first()
	{
		$this->rewind();
		if ( !$this->valid() )
		{
			throw new \UnderflowException;
		}
		return $this->current();
	}
	
	/**
	 * [Iterator] Rewind - will (re-)execute statement
	 *
	 * @return	void
	 */
	public function rewind( $debug=FALSE )
	{
		/* Run the query */
		$this->stmt = $this->db->preparedQuery( $this->query, $this->binds );
		
		/* Populate $this->row which we read into */
		$this->row = array();
		$params = array();
    	$meta = $this->stmt->result_metadata();
    	while ( $field = $meta->fetch_field() )
    	{
    		$params[] = &$this->row[ $field->name ];
    	}
    	$this->columns = array_keys( $this->row );
    	call_user_func_array( array( $this->stmt, 'bind_result' ), $params );
    	
    	/* Get the first result */
    	$this->key = -1;
    	$this->next();
	}
	
	/**
	 * [Iterator] Get current row
	 *
	 * @return	array
	 */
	public function current()
	{
		if ( $this->valueField )
		{
			return $this->row[ $this->valueField ];
		}
		elseif ( count( $this->row ) === 1 )
		{
			foreach ( $this->row as $v )
			{
				return $v;
			}
		}
		else
		{
			$row = array();
			foreach ( $this->row as $k => $v )
			{
				$row[ $k ] = $v;
			}
			return $row;
		}
	}
	
	/**
	 * [Iterator] Get current key
	 *
	 * @return	mixed
	 */
	public function key()
	{
		if ( $this->keyField )
		{
			return $this->row[ $this->keyField ];
		}
		else
		{
			return $this->key;
		}
	}
	
	/**
	 * [Iterator] Fetch next result
	 *
	 * @return	void
	 */
	public function next()
	{
    	if ( $this->stmt->fetch() === NULL )
    	{
	    	$this->row = NULL;
    	}
    	$this->key++;
	}
	
	/**
	 * [Iterator] Is the current row valid?
	 *
	 * @return	bool
	 */
	public function valid()
	{
		return ( $this->row !== NULL );
	}
	
	/**
	 * [Countable] Get number of rows
	 *
	 * @param	bool	$allRows	If TRUE, will get the number of rows ignoring the limit. In order for this to work, the query must have been ran with SQL_CALC_FOUND_ROWS
	 * @return	int
	 */
	public function count( $allRows = FALSE )
	{
		if ( !$this->stmt )
		{
			$this->rewind();
		}
		
		if ( $allRows )
		{
			$result = $this->db->query( 'SELECT FOUND_ROWS() AS count;' )->fetch_assoc();
			return $result['count'];
		}
		else
		{
			return $this->stmt->num_rows;
		}
	}
}