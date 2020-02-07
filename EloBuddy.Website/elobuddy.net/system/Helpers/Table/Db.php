<?php
/**
 * @brief		Table Builder using a database table datasource
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Table;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List Table Builder using a database table datasource
 */
class _Db extends Table
{
	/**
	 * @brief	Database Table
	 */
	protected $table;
	
	/**
	 * @brief	Selects
	 */
	public $selects = array();
	
	/**
	 * @brief	Initial WHERE clause
	 */
	public $where;
	
	/**
	 * @brief	Initial GROUP BY clause
	 */
	protected $group;
	
	/**
	 * @brief	Force index clause
	 */
	protected $index;
	
	/**
	 * @brief	Joins
	 */
	public $joins = array();
	
	/**
	 * @brief	Key field
	 */
	public $keyField = NULL;

	/**
	 * @brief	Restrict select columns to only those listed - useful when grouping
	 */
	public $onlySelected = NULL;

	/**
	 * Constructor
	 *
	 * @param	array			$table				Database table
	 * @param	\IPS\Http\Url	$baseUrl			Base URL
	 * @param	array|null		$where				WHERE clause
	 * @param	string|null		$group				GROUP clause
	 * @param	array|null		$forceIndex			Index to force
	 * @return	void
	 */
	public function __construct( $table, \IPS\Http\Url $baseUrl, $where=NULL, $group=NULL, $forceIndex=NULL )
	{
		$this->table = $table;
		$this->where = $where;
		$this->group = $group;
		$this->index = $forceIndex;
		
		return parent::__construct( $baseUrl );
	}
	
	/**
	 * Get rows
	 *
	 * @param	array	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( $advancedSearchValues )
	{
		/* Specify filter in where clause */
		$where = $this->where ? is_array( $this->where ) ? $this->where : array( $this->where ) : array();

		if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
		{
			$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
		}
		
		/* Add quick search term to where clause if necessary */
		if ( $this->quickSearch !== NULL and \IPS\Request::i()->quicksearch )
		{
			if ( is_callable( $this->quickSearch ) )
			{
				$where[] = call_user_func( $this->quickSearch, trim( \IPS\Request::i()->quicksearch ) );
			}
			else
			{
				$columns = is_array( $this->quickSearch ) ? $this->quickSearch[0] : $this->quickSearch;
				$columns = is_array( $columns ) ? $columns : array( $columns );
				
				$_where = '';
				foreach ( $columns as $c )
				{
					$_where[] = "LOWER(`{$c}`) LIKE CONCAT( '%', ?, '%' )";
				}
				
				$where[] = array_merge( array( '(' . implode( ' OR ', $_where ) . ')' ), array_fill( 0, count( $_where ), mb_strtolower( trim( \IPS\Request::i()->quicksearch ) ) ) );
			}
		}

		/* Add advanced search */
		if ( !empty( $advancedSearchValues ) )
		{
			foreach ( $advancedSearchValues as $k => $v )
			{
				if ( isset( $this->advancedSearch[ $k ] ) )
				{
					$type = $this->advancedSearch[ $k ];

					if ( is_array( $type ) )
					{
						if ( isset( $type[2] ) )
						{
							$lambda = $type[2];
							$type = SEARCH_CUSTOM;
						}
						else
						{
							$options = $type[1];
							$type = $type[0];
						}
					}
				
					switch ( $type )
					{
						case SEARCH_CUSTOM:
							if ( $clause = call_user_func( $lambda, $v ) )
							{
								$where[] = $clause;
							}
							break;
					
						case SEARCH_CONTAINS_TEXT:
							$where[] = array( "{$k} LIKE ?", '%' . $v . '%' );
							break;
							
						case SEARCH_DATE_RANGE:
							if ( $v['start'] )
							{
								$where[] = array( "{$k}>?", $v['start']->getTimestamp() );
							}
							if ( $v['end'] )
							{
								$where[] = array( "{$k}<?", $v['end']->getTimestamp() );
							}
							break;
						
						case SEARCH_SELECT:
							if ( isset( $options['multiple'] ) AND $options['multiple'] === TRUE )
							{
								$where[] = array( \IPS\Db::i()->in( $k, $v ) );
							}
							else
							{
								$where[] = array( "{$k}=?", $v );
							}
							break;
							
						case SEARCH_MEMBER:
							$where[] = array( "{$k}=?", $v->member_id );
							break;
							
						case SEARCH_NODE:
							$nodeClass = $options['class'];
							$prop = isset( $options['searchProp'] ) ? $options['searchProp'] : '_id';
							if ( !is_array( $v ) )
							{
								$v = array( $v );
							}
							
							$values = array();
							foreach ( $v as $_v )
							{
								if ( !is_object( $_v ) )
								{
									if ( mb_substr( $_v, 0, 2 ) === 's.' )
									{
										$nodeClass = $nodeClass::$subnodeClass;
										$_v = mb_substr( $_v, 2 );
									}
									try
									{
										$_v = $nodeClass::load( $_v );
									}
									catch ( \OutOfRangeException $e )
									{
										continue;
									}
								}
								$values[] = $_v->$prop;
							}
							$where[] = array( \IPS\Db::i()->in( $k, $values ) );
							break;
						
						case SEARCH_NUMERIC:
							switch ( $v[0] )
							{
								case 'gt':
									$where[] = array( "{$k}>?", (float) $v[1] );
									break;
								case 'lt':
									$where[] = array( "{$k}<?", (float) $v[1] );
									break;
								case 'eq':
									$where[] = array( "{$k}=?", (float) $v[1] );
									break;
							}
							break;
							
						case SEARCH_BOOL:
							$where[] = array( "{$k}=?", (bool) $v );
							break;
					}
				}
			}
		}
		
		$selects = $this->selects;

		if( $this->onlySelected !== NULL )
		{
			foreach( $this->onlySelected as $column )
			{
				$selects[] = $column;
			}

			if( $this->group !== NULL )
			{
				$this->group = is_array( $this->group ) ? $this->group : array( $this->group );
				$this->group = array_unique( array_merge( $this->group, $this->onlySelected ) );
			}
		}
		else
		{
			if ( count( $this->joins ) )
			{
				foreach( $this->joins as $join )
				{
					if ( isset( $join['select'] ) )
					{
						$selects[] = $join['select'];
					}
				}
			}
		}
		
		/* Count results (for pagination) */
		$count = \IPS\Db::i()->select( 'count(*)', $this->table, $where );
		if ( count( $this->joins ) )
		{
			foreach( $this->joins as $join )
			{
				$count->join( $join['from'], ( isset( $join['where'] ) ? $join['where'] : null ), ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
			}
		}
		$count		= $count->first();

		/* Now get column headers */
		$query = \IPS\Db::i()->select( $this->onlySelected ? implode( ', ', $selects ) : '*', $this->table, $where, NULL, array( 0, 1 ), $this->group );
		
		if ( count( $this->joins ) )
		{
			foreach( $this->joins as $join )
			{
				$query->join( $join['from'], ( isset( $join['where'] ) ? $join['where'] : null ), ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
			}
		}
		
		if ( $this->index )
		{
			$query->forceIndex( $this->index );
		}

		try
		{
			$results	= $query->first();
		}
		catch( \UnderflowException $e )
		{
			$results	= array();
		}

		$this->pages = ceil( $count / $this->limit );

		/* What are we sorting by? */
		$orderBy = NULL;
		if ( $this->_isSqlSort( $results ) )
		{
			$orderBy = implode( ',', array_map( function( $v )
			{
				if ( ! mb_strstr( trim( $v ), ' ' ) )
				{
					return '`' . trim( $v ) . '`';
				}
				else
				{
					list( $field, $direction ) = explode( ' ', $v );
					return '`' . trim( $field ) . '` ' . ( mb_strtolower( $direction ) == 'asc' ? 'asc' : 'desc' );
				}
			}, explode( ',', $this->sortBy ) ) );
			
			$orderBy .= $this->sortDirection == 'asc' ? ' asc' : ' desc';
		}
		
		/* Run query */
		$rows = array();
		$select = \IPS\Db::i()->select(
			$this->onlySelected ? implode( ', ', $selects ) : ( ( count( $selects ) ) ? $this->table . '.*, ' . implode( ', ', $selects ) : '*' ),
			$this->table,
			$where,
			$orderBy,
			array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ), 
			$this->group
		);

		if ( $this->index )
		{
			$select->forceIndex( $this->index );
		}

		if ( count( $this->joins ) )
		{
			foreach( $this->joins as $join )
			{
				$select->join( $join['from'], $join['where'], ( isset( $join['type'] ) ) ? $join['type'] : 'LEFT' );
			}
		}
		if ( $this->keyField !== NULL )
		{
			$select->setKeyField( $this->keyField );
		}

		foreach ( $select as $rowId => $row )
		{
			/* Add in any 'custom' fields */
			$_row = $row;
			if ( $this->include !== NULL )
			{
				$row = array();
				foreach ( $this->include as $k )
				{
					$row[ $k ] = isset( $_row[ $k ] ) ? $_row[ $k ] : NULL;
				}
				
				if( !empty( $advancedSearchValues ) )
				{
					foreach ( $advancedSearchValues as $k => $v )
					{
						$row[ $k ] = isset( $_row[ $k ] ) ? $_row[ $k ] : NULL;
					}
				}
			}
			
			/* Loop the data */
			foreach ( $row as $k => $v )
			{
				/* Parse if necessary (NB: deliberately do this before removing the row in case we need to do some processing, but don't want the column to actually show) */
				if( isset( $this->parsers[ $k ] ) )
				{
					$v = call_user_func( $this->parsers[ $k ], $v, $_row );
				}
				else
				{
					$v = htmlspecialchars( $v, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE );
				}

				/* Are we including this one? */
				if( ( ( $this->include !== NULL and !in_array( $k, $this->include ) ) or ( $this->exclude !== NULL and in_array( $k, $this->exclude ) ) ) and !array_key_exists( $k, $advancedSearchValues ) )
				{
					unset( $row[ $k ] );
					continue;
				}
											
				/* Add to array */
				$row[ $k ] = $v;
			}

			/* Add in some buttons if necessary */
			if( $this->rowButtons !== NULL )
			{
				$row['_buttons'] = call_user_func( $this->rowButtons, $_row );
			}
			
			$rows[ $rowId ] = $row;
		}
		
		/* If we're sorting on a column not in the DB, do it manually */
		if ( $this->sortBy and $this->_isSqlSort( $results ) !== true )
		{
			$sortBy = $this->sortBy;
			$sortDirection = $this->sortDirection;
			uasort( $rows, function( $a, $b ) use ( $sortBy, $sortDirection )
			{
				if( $sortDirection === 'asc' )
				{
					return strnatcasecmp( mb_strtolower( $a[ $sortBy ] ), mb_strtolower(  $b[ $sortBy ] ) );
				}
				else
				{
					return strnatcasecmp( mb_strtolower(  $b[ $sortBy ] ), mb_strtolower( $a[ $sortBy ] ) );
				}
			});
		}

		/* Return */
		return $rows;
	}
	
	/**
	 * User set sortBy is suitable for an SQL sort operation
	 * @param	array	$count	Result of COUNT(*) query with field names included
	 * @return	boolean
	 */
	protected function _isSqlSort( $count )
	{
		if ( !$this->sortBy )
		{
			return false;
		}
		
		if ( mb_strstr( $this->sortBy, ',' ) )
		{
			foreach( explode( ',', $this->sortBy ) as $field )
			{
				$field = trim($field);
				
				if ( mb_strstr( $field, ' ' ) )
				{
					list( $field, $direction ) = explode( ' ', $field );
				}
				
				if ( !array_key_exists( trim($field), $count ) )
				{
					return false;
				}
			}
			
			return true;
		}
		elseif ( array_key_exists( $this->sortBy, $count ) )
		{
			return true;
		}
				
		return false;
	}

	/**
	 * What custom multimod actions are available
	 *
	 * @return	array
	 */
	public function customActions()
	{
		return array();
	}
}