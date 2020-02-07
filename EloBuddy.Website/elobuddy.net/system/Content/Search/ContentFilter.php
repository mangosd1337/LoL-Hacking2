<?php
/**
 * @brief		Content Filter for Search Queries
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		6 Jul 2015
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Filter for Search Queries
 */
class _ContentFilter
{
	/**
	 * @brief	Item class
	 */
	public $itemClass;
	
	/**
	 * @brief	Classes to include
	 */
	public $classes = array();
	
	/**
	 * @brief	Container ID filter
	 */
	public $containerIdFilter = NULL;
	
	/**
	 * @brief	Container IDs
	 */
	public $containerIds = array();
	
	/**
	 * @brief	Item ID filter
	 */
	public $itemIdFilter = NULL;
	
	/**
	 * @brief	Item IDs
	 */
	public $itemIds = array();
	
	/**
	 * @brief	Minimum comments
	 */
	public $minimumComments = 0;
	
	/**
	 * @brief	Minimum reviews
	 */
	public $minimumReviews = 0;
	
	/**
	 * @brief	Minimum views
	 */
	public $minimumViews = 0;
	
	/**
	 * @brief	Only include results which are the first comment on an item requiring a comment
	 */
	public $onlyFirstComment = FALSE;
	
	/**
	 * @brief	Only include results which are the last comment on an item requiring a comment
	 */
	public $onlyLastComment = FALSE;
			
	/**
	 * Constructor
	 *
	 * @param	string	$itemClass			The item class
	 * @param	bool	$includeItems		Include items in results?
	 * @param	bool	$includeComments	Include comments in results?
	 * @param	bool	$includeReviews		Include reviews in results?
	 * @return	\IPS\Content\Search\ContainerFilter
	 */
	public static function init( $itemClass, $includeItems=TRUE, $includeComments=TRUE, $includeReviews=TRUE )
	{		
		$obj = new self;
		$obj->itemClass = $itemClass;
		
		if ( $includeItems and ( !isset( $itemClass::$firstCommentRequired ) OR !$itemClass::$firstCommentRequired ) )
		{
			$obj->classes[] = $itemClass;
		}
		
		if ( $includeComments and isset( $itemClass::$commentClass ) )
		{
			$obj->classes[] = $itemClass::$commentClass;
		}
		
		if ( $includeReviews and isset( $itemClass::$reviewClass ) )
		{
			$obj->classes[] = $itemClass::$reviewClass;
		}
		
		return $obj;
	}
	
	/**
	 * Only include results in containers
	 *
	 * @param	array	$ids	Acceptable container IDs
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function onlyInContainers( array $ids )
	{
		$this->containerIdFilter = TRUE;
		$this->containerIds = $ids;
		
		return $this;
	}
	
	/**
	 * Exclude results in containers
	 *
	 * @param	array	$ids	Acceptable container IDs
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function excludeInContainers( array $ids )
	{
		$this->containerIdFilter = FALSE;
		$this->containerIds = $ids;
		
		return $this;
	}
	
	/**
	 * Only include results in items
	 *
	 * @param	array	$ids	Acceptable item IDs
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function onlyInItems( array $ids )
	{
		$this->itemIdFilter = TRUE;
		$this->itemIds = $ids;
		
		return $this;
	}
	
	/**
	 * Exclude results in items
	 *
	 * @param	array	$ids	Acceptable container IDs
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function excludeInItems( array $ids )
	{
		$this->itemIdFilter = FALSE;
		$this->itemIds = $ids;
		
		return $this;
	}
	
	/**
	 * Set minimum number of comments
	 *
	 * @param	int	$minimumComments	The minimum number of comments
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function minimumComments( $minimumComments )
	{
		$this->minimumComments = $minimumComments;
		
		return $this;
	}
	
	/**
	 * Set minimum number of reviews
	 *
	 * @param	int	$minimumReviews	The minimum number of reviews
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function minimumReviews( $minimumReviews )
	{
		$this->minimumReviews = $minimumReviews;
		
		return $this;
	}
	
	/**
	 * Set minimum number of views
	 *
	 * @param	int	$minimumViews	The minimum number of views
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function minimumViews( $minimumViews )
	{
		$this->minimumViews = $minimumViews;
		
		return $this;
	}
	
	/**
	 * Only include results which are the first comment on an item requiring a comment
	 *
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function onlyFirstComment()
	{
		$this->onlyFirstComment = TRUE;
		
		return $this;
	}
	
	/**
	 * Only include results which are the last comment on an item requiring a comment
	 *
	 * @return	\IPS\Content\Search\ContainerFilter	(for daisy chaining)
	 */
	public function onlyLastComment()
	{
		$this->onlyLastComment = TRUE;
		
		return $this;
	}
}