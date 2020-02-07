<?php
/**
 * @brief		Dynamic Chart Helper
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Chart;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dynamic Chart Helper
 */
class _Dynamic extends \IPS\Helpers\Chart
{
	/**
	 * @brief	URL
	 */
	public $url;
	
	/**
	 * @brief	Database Table
	 */
	protected $table;
	
	/**
	 * @brief	Database column that contains date
	 */
	protected $dateField;
	
	/**
	 * @brief	Where clauses
	 */
	public $where	= array();
	
	/**
	 * @brief	Extra column to group by (useful for multi-data line charts)
	 */
	public $groupBy	= '';

	/**
	 * @brief	Group by keys for the series
	 */
	protected $groupByKeys = array();
	
	/**
	 * @brief	$timescale (daily, weekly, monthly)
	 */
	public $timescale = 'monthly';

	/**
	 * @brief	Unique identifier for URLs
	 */
	public $identifier	= '';
	
	/**
	 * @brief	Start Date
	 */
	public $start;
	
	/**
	 * @brief	End Date
	 */
	public $end;
	
	/**
	 * @brief	Series
	 */
	protected $series = array();
	
	/**
	 * @brief	Title
	 */
	public $title;
	
	/**
	 * @brief	Google Chart Options
	 */
	public $options = array();
	
	/**
	 * @brief	Type
	 */
	public $type;
	
	/**
	 * @brief	Available Types
	 */
	public $availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart', 'PieChart', 'Table' );
	
	/**
	 * @brief	Available Filters
	 */
	public $availableFilters = array();
	
	/**
	 * @brief	Current Filters
	 */
	public $currentFilters = array();
	
	/**
	 * @brief	Table Columns
	 */
	public $tableInclude = array();
	
	/**
	 * @brief	Table Column Formatters
	 */
	public $tableParsers = array();
	
	/**
	 * @brief	Plot zeros
	 */
	public $plotZeros = TRUE;
	
	/**
	 * @brief	Value for number formatter
	 */
	public $format = NULL;
		
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url	$url			The URL the chart will be displayed on
	 * @param	string			$table			Database Table
	 * @param	string			$dateField		Database column that contains date
	 * @param	string			$title			Title
	 * @param	array			$options		Options
	 * @param	string			$defaultType	The default chart type
	 * @param	string			$defaultTimescale	The default timescale to use
	 * @param	array			$defaultTimes	The default start/end times to use
	 * @param	string			$identifier		If there will be more than one chart per page, provide a unique identifier
	 * @see		<a href='https://google-developers.appspot.com/chart/interactive/docs/gallery'>Charts Gallery - Google Charts - Google Developers</a>
	 * @return	void
	 */
	public function __construct( \IPS\Http\Url $url, $table, $dateField, $title='', $options=array(), $defaultType='LineChart', $defaultTimescale='monthly', $defaultTimes=array( 'start' => 0, 'end' => 0 ), $tableInclude=array(), $identifier='' )
	{
		if ( !isset( $options['chartArea'] ) )
		{
			$options['chartArea'] = array(
				'left'	=> '50',
				'width'	=> '75%'
			);
		}
		
		$this->baseURL		= $url;
		$this->table		= $table;
		$this->dateField	= $dateField;
		$this->title		= $title;
		$this->options		= $options;
		$this->timescale	= $defaultTimescale;
		$this->start		= $defaultTimes['start'];
		$this->end			= $defaultTimes['end'];
		$this->identifier	= \substr( md5( $table . $dateField ), 0, 6 ) . $identifier;

		if ( isset( \IPS\Request::i()->type[ $this->identifier ] ) and in_array( \IPS\Request::i()->type[ $this->identifier ], $this->availableTypes ) )
		{
			$this->type = \IPS\Request::i()->type[ $this->identifier ];
			$url = $url->setQueryString( 'type', array( $this->identifier => $this->type ) );
		}
		else
		{
			$this->type = $defaultType;
		}
		
		if ( $this->type === 'PieChart' or $this->type === 'GeoChart' )
		{
			$this->addHeader( 'key', 'string' );
			$this->addHeader( 'value', 'number' );
		}
		else
		{
			$this->addHeader( \IPS\Member::loggedIn()->language()->addToStack('date'), 'date' );
		}

		if ( isset( \IPS\Request::i()->timescale[ $this->identifier ] ) and in_array( \IPS\Request::i()->timescale[ $this->identifier ], array( 'daily', 'weekly', 'monthly' ) ) )
		{
			$this->timescale = \IPS\Request::i()->timescale[ $this->identifier ];
			$url = $url->setQueryString( 'timescale', array( $this->identifier => \IPS\Request::i()->timescale[ $this->identifier ] ) );
		}

		if ( isset( \IPS\Request::i()->start[ $this->identifier ] ) and \IPS\Request::i()->start[ $this->identifier ] )
		{
			try
			{
				if ( is_numeric( \IPS\Request::i()->start[ $this->identifier ] ) )
				{
					$this->start = \IPS\DateTime::ts( \IPS\Request::i()->start[ $this->identifier ] );
				}
				else
				{
					$this->start = new \IPS\DateTime( \IPS\Request::i()->start[ $this->identifier ], new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
				}
				
				$url = $url->setQueryString( 'start', array( $this->identifier => $this->start->getTimestamp() ) );
			}
			catch ( \Exception $e ) {}
		}

		if ( isset( \IPS\Request::i()->end[ $this->identifier ] ) and \IPS\Request::i()->end[ $this->identifier ] )
		{
			try
			{
				if ( is_numeric( \IPS\Request::i()->end[ $this->identifier ] ) )
				{
					$this->end = \IPS\DateTime::ts( \IPS\Request::i()->end[ $this->identifier ] );
				}
				else
				{
					$this->end = new \IPS\DateTime( \IPS\Request::i()->end[ $this->identifier ], new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
				}
				
				$url = $url->setQueryString( 'end', array( $this->identifier => $this->end->getTimestamp() ) );
			}
			catch ( \Exception $e ) {}
		}
		
		if ( !empty( $tableInclude ) )
		{
			$this->tableInclude = $tableInclude;
			$this->availableTypes[] = 'Table';
		}
		
		if ( isset( \IPS\Request::i()->filters[ $this->identifier ] ) )
		{
			$url = $url->setQueryString( 'filters', '' );
		}
		
		$this->url = $url;
	}
	
	/**
	 * Add Series
	 *
	 * @param	string	$name		Name
	 * @param	string	$type		Type of value
	 *	@li	string
	 *	@li	number
	 *	@li	boolean
	 *	@li	date
	 *	@li	datetime
	 *	@li	timeofday
	 * @param	string	$sql		SQL expression to get value
	 * @param	bool	$filterable	If TRUE, will show as a filter option to be toggled on/off
	 * @param	string	$groupByKey	If $this->groupBy is set, the raw key value
	 * @return	void
	 */
	public function addSeries( $name, $type, $sql, $filterable=TRUE, $groupByKey=NULL )
	{
		$filterKey	= $groupByKey ?: $name;
		if ( $groupByKey !== NULL )
		{
			$filterKey = $groupByKey;
		}
		else
		{
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $name );
			$filterKey = $name;
		}
		
		if ( !$filterable or !isset( \IPS\Request::i()->filters[ $this->identifier ] ) or in_array( $filterKey, \IPS\Request::i()->filters[ $this->identifier ] ) )
		{
			if ( $this->type !== 'PieChart' and $this->type !== 'GeoChart' )
			{
				$this->addHeader( $name, $type );
			}

			if( $this->groupBy )
			{
				$this->groupByKeys[]	= $groupByKey;
			}

			$this->series[ $filterKey ] = $sql;
			
			if ( $filterable )
			{
				$this->currentFilters[] = $filterKey;
				
				if ( isset( \IPS\Request::i()->filters[ $this->identifier ] ) )
				{
					$this->url = $this->url->setQueryString( 'filters', array( $this->identifier => $this->currentFilters ) );
				}
			}
		}
		
		if ( $filterable )
		{
			$this->availableFilters[ $filterKey ] = $name;
		}
	}
	
	/**
	 * HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{
			/* Init data */
			$data = array();
			if ( $this->start )
			{
				$date = clone $this->start;
				while ( $date->getTimestamp() < ( $this->end ? $this->end->getTimestamp() : time() ) )
				{
					switch ( $this->timescale )
					{
						case 'daily':
							$data[ $date->format( 'Y-n-j' ) ] = array();
	
							if( $this->groupBy )
							{
								foreach( $this->groupByKeys as $key )
								{
									$data[ $date->format( 'Y-n-j' ) ][ $key ]	= 0;
								}
							}
							$date->add( new \DateInterval( 'P1D' ) );
							break;
							
						case 'weekly':
							/* o is the ISO year number, which we need when years roll over.
								@see http://php.net/manual/en/function.date.php#106974 */
							$data[ $date->format( 'o-W' ) ] = array();
	
							if( $this->groupBy )
							{
								foreach( $this->groupByKeys as $key )
								{
									$data[ $date->format( 'o-W' ) ][ $key ]	= 0;
								}
							}
							$date->add( new \DateInterval( 'P7D' ) );
							break;
							
						case 'monthly':
							$data[ $date->format( 'Y-n' ) ] = array();
	
							if( $this->groupBy )
							{
								foreach( $this->groupByKeys as $key )
								{
									$data[ $date->format( 'Y-n' ) ][ $key ]	= 0;
								}
							}
							$date->add( new \DateInterval( 'P1M' ) );
							break;
					}
				}
			}
			
			/* Get data */
			$output = '';
			if ( !empty( $this->series ) )
			{
				/* Work out where clause */
				$where = $this->where;
				$where[] = array( "{$this->dateField}>?", 0 );
				if ( $this->start )
				{
					$where[] = array( "{$this->dateField}>?", $this->start->getTimestamp() );
				}
				if ( $this->end )
				{
					$where[] = array( "{$this->dateField}<?", $this->end->getTimestamp() );
				}
				
				/* What's our SQL time? */
				switch ( $this->timescale )
				{
					case 'daily':
						$timescale = '%Y-%c-%e';
						break;
					
					case 'weekly':
						$timescale = '%Y-%u';
						break;
						
					case 'monthly':
						$timescale = '%Y-%c';
						break;
				}
				
				/* Table... */
				if ( $this->type === 'Table' )
				{
					$table = new \IPS\Helpers\Table\Db( $this->table, $this->url, $where );
					$table->include = $this->tableInclude;
					$table->parsers = $this->tableParsers;
					$table->sortBy = $table->sortBy ?: $this->dateField;
					$output = (string) $table;
				}
				
				/* Pie Chart */
				if ( $this->type === 'PieChart' or $this->type === 'GeoChart' )
				{						
					$keys = array_unique( $this->series );
					$key = array_pop( $keys );
					
					$stmt = \IPS\Db::i()->select(
						"{$key}" . ( $this->groupBy ? ", {$this->groupBy}" : '' ),
						$this->table,
						$where,
						$this->dateField . ' ASC',
						NULL,
						$this->groupBy ? ( ( $this->groupBy == $this->dateField ) ? $this->groupBy : array( $this->groupBy, $this->dateField ) ) : NULL
					)->setKeyField( $this->groupBy )->setValueField( $key );
										
					foreach ( $stmt as $k => $v )
					{
						if( count( $this->availableFilters ) and !in_array( $k, $this->currentFilters ) )
						{
							continue;
						}
						$this->addRow( array( 'key' => $this->availableFilters[ $k ], 'value' => $v ) );
					}
					
					$output = $this->render( $this->type, $this->options, $this->format );
				}
				
				/* Graph */
				else
				{
					/* Fetch */
					$stmt = \IPS\Db::i()->select(
						"DATE_FORMAT( FROM_UNIXTIME( IFNULL( {$this->dateField}, 0 ) ), '{$timescale}' ) AS time, " . implode( ', ', array_unique( $this->series ) ) . ( $this->groupBy ? ", " . $this->groupBy : '' ),
						$this->table,
						$where,
						'time ASC',
						NULL,
						$this->groupBy ? array( 'time', $this->groupBy ) : 'time'
					);
																				
					foreach ( $stmt as $row )
					{
						$result	= array();
			
						if( $this->groupBy )
						{
							if( count( $this->availableFilters ) AND !in_array( $row[ $this->groupBy ], $this->currentFilters ) )
							{
								continue;
							}
						}
			
						foreach( $this->series as $column )
						{
							if( $this->groupBy )
							{
								if( empty( $data[ $row['time'] ] ) )
								{
									$result	= array( $row[ $this->groupBy ] => $row[ $column ] );
								}
								else
								{
									$result	= $data[ $row['time'] ];
									$result[ $row[ $this->groupBy ] ] = $row[ $column ];
								}
							}
							else
							{
								$result[ $column ]	= $row[ $column ];
							}
						}
			
						$data[ $row['time'] ] = $result;
					}

					ksort( $data, SORT_NATURAL );
					
					/* Add to graph */
					$min = NULL;
					$max = NULL;
					foreach ( $data as $time => $d )
					{
						$datetime = new \IPS\DateTime;

						if ( \IPS\Member::loggedIn()->timezone )
						{
							try
							{
								$datetime->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
							}
							catch ( \Exception $e )
							{
								\IPS\Member::loggedIn()->timezone	= null;
								\IPS\Member::loggedIn()->save();
							}
						}

						$datetime->setTime( 0, 0, 0 );
						$exploded = explode( '-', $time );
						switch ( $this->timescale )
						{
							case 'daily':
								$datetime->setDate( (float) $exploded[0], $exploded[1], $exploded[2] );
								//$datetime = $datetime->localeDate();
								break;
								
							case 'weekly':
								$datetime->setISODate( (float) $exploded[0], $exploded[1] );
								//$datetime = $datetime->localeDate();
								break;
								
							case 'monthly':
								$datetime->setDate( (float) $exploded[0], $exploded[1], 1 );
								//$datetime = $datetime->format( 'F Y' );
								break;
						}
									
						if ( empty( $d ) )
						{
							if ( empty( $this->series ) )
							{
								$this->addRow( array( $datetime ) );
							}
							else
							{
								$this->addRow( array_merge( array( $datetime ), array_fill( 0, count( $this->series ), 0 ) ) );
							}
						}
						else
						{
							if( $this->groupBy )
							{
								$_values	= array();
								foreach ( $this->series as $id => $col )
								{
									$_values[ $id ] = ( isset( $d[ $id ] ) ) ? $d[ $id ] : ( $this->plotZeros ? 0 : NULL );
								}
								
								$this->addRow( array_merge( array( $datetime ), $_values ) );
							}
							else
							{
								if( count($d) < count($this->series) )
								{
									$this->addRow( array_merge( array( $datetime ), $d, array_fill( 0, count($this->series) - count($d), 0 ) ) );
								}
								else
								{
									$this->addRow( array_merge( array( $datetime ), $d ) );
								}
							}
						}
					}
					
					if ( count( $data ) === 1 )
					{
						$this->options['domainAxis']['type'] = 'category';
					}

					$output = $this->render( $this->type, $this->options, $this->format );
				}
			}
			else
			{
				$output = \IPS\Member::loggedIn()->language()->addToStack('chart_no_results');
			}

			/* Display */
			if ( \IPS\Request::i()->noheader )
			{
				return $output;
			}
			else
			{
				return \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->dynamicChart( $this, $output );
			}
		}
		catch ( \Exception $e )
		{
			\IPS\IPS::exceptionHandler( $e );
		}
		catch ( \Throwable $e )
		{
			\IPS\IPS::exceptionHandler( $e );
		}
	}
		
	/**
	 * Flip URL Filter
	 *
	 * @param	string	$filter	The Filter
	 * @return	\IPS\Http\Url
	 */
	public function flipUrlFilter( $filter )
	{
		$filters = $this->currentFilters;
		
		if ( in_array( $filter, $filters ) )
		{
			unset( $filters[ array_search( $filter, $filters ) ] );
		}
		else
		{
			$filters[] = $filter;
		}
		
		return $this->url->setQueryString( 'filters', array( $this->identifier => $filters ) );
	}
}