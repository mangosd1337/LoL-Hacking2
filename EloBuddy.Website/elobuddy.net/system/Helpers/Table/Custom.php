<?php
/**
 * @brief		Table Builder using an array datasource
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
 * List Table Builder using an array datasource
 */
class _Custom extends Table
{
	/**
	 * @brief	Data
	 */
	protected $dataSource;
	
	/**
	 * @brief	Parse Source
	 */
	public $parseSource = TRUE;
	
	/**
	 * Number of results
	 */
	public $count = 0;
	
	/**
	 * Number of pages
	 */
	public $pages = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	array			$dataSource		Data source
	 * @param	\IPS\Http\Url	$baseUrl		Base URL
	 */
	public function __construct( $dataSource, \IPS\Http\Url $baseUrl )
	{
		$this->dataSource = $dataSource;
		$this->count      = count( $this->dataSource );
		
		return parent::__construct( $baseUrl );
	}
	
	/**
	 * Get rows
	 *
	 * @param	array	$advancedSearchValues	Values from the advanced search form
	 * @note	$advancedSearchValues is currently ignored
	 * @return	array
	 */
	public function getRows( $advancedSearchValues )
	{
		if ( ! $this->pages )
		{
			$this->pages = ceil( $this->count / $this->limit );
		}
		
		/* Get them */
		$rows = array();
		foreach ( $this->dataSource as $i => $data )
		{
			$row = $this->include ? array_combine( $this->include, array_fill( 0, count( $this->include ), NULL ) ) : array();

			/* Get the columns from the XML */
			foreach ( $data as $k => $v )
			{
				/* Are we including this one? */
				if( ( $this->include !== NULL and !in_array( $k, $this->include ) ) or ( $this->exclude !== NULL and in_array( $k, $this->exclude ) ) )
				{
					continue;
				}
				
				/* Add to array */
				$row[ $k ] = $v;
			}
			
			/* Add it to the array */
			$rows[ $i ] = $row;
		}
				
		/* Quicksearch */
		if ( $this->quickSearch !== NULL and \IPS\Request::i()->quicksearch )
		{
			$quickSearchColumn = $this->quickSearch;
			$rows = array_filter( $rows, is_callable( $this->quickSearch ) ? $this->quickSearch : function( $row ) use ( $quickSearchColumn )
			{
				return mb_strpos( mb_strtolower( $row[ $quickSearchColumn ] ), mb_strtolower( trim( \IPS\Request::i()->quicksearch ) ) ) !== FALSE;
			} );
		}

		/* Do we need to sort? */
		if( !empty( $rows ) and $this->sortBy and isset( $rows[ key( $rows ) ][ $this->sortBy ] ) )
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
		
		/* Limit */
		if ( $this->limit and $this->count > $this->limit )
		{
			$rows = array_slice( $rows, ( $this->limit * ( $this->page - 1 ) ), $this->limit );
		}

		/* Apply parsers */
		foreach( $rows as $i => $row )
		{
			foreach( $row as $k => $v )
			{
				/* Parse if necessary */
				if( isset( $this->parsers[ $k ] ) )
				{
					$v = call_user_func( $this->parsers[ $k ], $v, $this->dataSource[ $i ] );
				}
				else
				{
					$v = htmlspecialchars( $v, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE );
				}
				
				$rows[ $i ][ $k ] = $v;
			}
			
			/* Add in some buttons if necessary */
			if( $this->rowButtons !== NULL )
			{
				$rows[ $i ]['_buttons'] = call_user_func( $this->rowButtons, $row, $i );
			}
		}
		
		return $rows;
	}
}

