<?php
/**
 * @brief		Tree Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Tree;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Tree Table
 */
class _Tree
{
	/**
	 * @brief	Title for tree table
	 */
	public $title = '';
	
	/**
	 * @brief	URL where the tree table is displayed
	 */
	public $url = '';
	
	/**
	 * @brief	Callback function to get the root rows
	 */
	public $getRoots;
	
	/**
	 * @brief	Callback function to get a single row by ID
	 */
	public $getRow;
	
	/**
	 * @brief	Callback function to get the parent ID for a row
	 */
	public $getRowParentId;
	
	/**
	 * @brief	Callback function to get the child rows for a row
	 */
	public $getChildren;
	
	/**
	 * @brief	Searchable?
	 */
	public $searchable = FALSE;
	
	/**
	 * @brief	If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 */
	protected $lockParents = FALSE;
	
	/**
	 * @brief	If true, root cannot be turned into sub-items, and other items cannot be turned into roots
	 */
	protected $protectRoots = FALSE;
	
	/**
	 * @brief	Number of roots to show per page (NULL for unlimited). In most cases this doesn't make sense, since it makes re-ordering impossible. But for trees which are not orderable and which may contain a lot of roots, you can set this value
	 */
	public $rootsPerPage = NULL;

	/**
	 * @brief	If using $rootsPerPage, a callback function that returns the total number of roots
	 */
	public $getTotalRoots = NULL;
		
	/**
	 * Constructor
	 *
	 * @param	string		$url					URL where the tree table is displayed
	 * @param	string		$title					Tree Table title
	 * @param	callback	$getRoots				Callback function to get the root rows
	 * @param	callback	$getRow					Callback function to get a single row by ID
	 * @param	callback	$getRowParentId			Callback function to get the parent ID for a row
	 * @param	callback	$getChildren			Callback function to get the child rows for a row
	 * @param	callback	$searchable				Show the search bar?
	 * @param	bool		$lockParents			If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 * @param	bool		$protectRoots			If true, root cannot be turned into sub-items, and other items cannot be turned into roots
	 * @param	int|null	$rootsPerPage			Number of roots to show per page (NULL for unlimited). In most cases this doesn't make sense, since it makes re-ordering impossible. But for trees which are not orderable and which may contain a lot of roots, you can set this value
	 * @param	callback	$getTotalRoots			If using $rootsPerPage, a callback function that returns the total number of roots
	 * @return	void
	 */
	public function __construct( $url, $title, $getRoots, $getRow, $getRowParentId, $getChildren, $getRootButtons=NULL, $searchable=FALSE, $lockParents=FALSE, $protectRoots=FALSE, $rootsPerPage = NULL, $getTotalRoots = NULL )
	{
		$this->url = $url;
		$this->title = $title;
		$this->getRoots = $getRoots;
		$this->getRow = $getRow;
		$this->getRowParentId = $getRowParentId;
		$this->getChildren = $getChildren;
		$this->getRootButtons = $getRootButtons ?: function(){ return array(); };
		$this->searchable = $searchable;
		$this->lockParents = $lockParents;
		$this->protectRoots = $protectRoots;
		$this->rootsPerPage = $rootsPerPage;
		$this->getTotalRoots = $getTotalRoots;
	}
	
	/**
	 * Display Table
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{
			/* Get rows */
			$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;
			$root = NULL;
			$rootParent = NULL;
			if( !\IPS\Request::i()->root )
			{
				$rows = call_user_func( $this->getRoots, $this->rootsPerPage ? array( ( $page - 1 ) * $this->rootsPerPage, $this->rootsPerPage ) : NULL );
			}
			else
			{
				$rows = call_user_func( $this->getChildren, \IPS\Request::i()->root );

				if ( \IPS\Request::i()->isAjax() )
				{
					return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, md5( uniqid() ) );
				}
				
				$root = call_user_func( $this->getRow, \IPS\Request::i()->root, TRUE );
				$rootParent = call_user_func( $this->getRowParentId, \IPS\Request::i()->root );
			}
			
			/* Pagination? */
			$pagination = '';
			if ( $this->rootsPerPage )
			{
				$totalNumber = call_user_func( $this->getTotalRoots );
				if ( $totalNumber )
				{
					$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $this->url, ceil( $totalNumber / $this->rootsPerPage ), $page, $this->rootsPerPage );
				}
			}
										
			/* Display */
			return \IPS\Theme::i()->getTemplate( 'trees', 'core' )->template( $this->url, $this->title, $root, $rootParent, $rows, call_user_func( $this->getRootButtons ), $this->lockParents, $this->protectRoots, $this->searchable, $pagination );
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
}