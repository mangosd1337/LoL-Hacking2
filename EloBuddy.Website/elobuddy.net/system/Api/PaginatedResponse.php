<?php
/**
 * @brief		PaginatedAPI Response
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Response
 */
class _PaginatedResponse extends Response
{
	/**
	 * @brief	HTTP Response Code
	 */
	public $httpCode;
	
	/**
	 * @brief	Select query
	 */
	protected $select;
	
	/**
	 * @brief	Current page
	 */
	protected $page = 1;
	
	/**
	 * @brief	Results per page
	 */
	protected $resultsPerPage = 25;
	
	/**
	 * @brief	ActiveRecord class
	 */
	protected $activeRecordClass;
	
	/**
	 * Constructor
	 *
	 * @param	int				$httpCode			HTTP Response code
	 * @param	\IPS\Db\Select	$select				Select query
	 * @param	int				$page				Current page
	 * @param	string			$activeRecordClass	ActiveRecord class
	 * @return	void
	 */
	public function __construct( $httpCode, $select, $page, $activeRecordClass )
	{
		$this->httpCode = $httpCode;
		$this->page = $page;
		$this->select = $select;
		$this->activeRecordClass = $activeRecordClass;
	}
	
	/**
	 * Data to output
	 *
	 * @retrun	string
	 */
	public function getOutput()
	{
		$results = array();
		$this->select->query .= \IPS\Db::i()->compileLimitClause( array( ( $this->page - 1 ) * $this->resultsPerPage, $this->resultsPerPage ) );
		
		if ( $this->activeRecordClass )
		{
			foreach ( new \IPS\Patterns\ActiveRecordIterator( $this->select, $this->activeRecordClass ) as $result )
			{
				$results[] = $result->apiOutput();
			}
		}
		else
		{
			foreach ( $this->select as $result )
			{
				$results[] = $result;
			}
		}
		
		$count = (int) $this->select->count( TRUE );		
		return array(
			'page'			=> $this->page,
			'perPage'		=> $this->resultsPerPage,
			'totalResults'	=> $count,
			'totalPages'	=> ceil( $count / $this->resultsPerPage ),
			'results'		=> $results
		);
	}
}