<?php
/**
 * @brief		Search Result from Index
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Sep 2015
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search\Result;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Result
 */
class _Content extends \IPS\Content\Search\Result
{
	/**
	 * Index Data
	 */
	protected $indexData;
	
	/**
	 * Author Data
	 */
	protected $authorData;
	
	/**
	 * Item Data
	 */
	protected $itemData;
	
	/**
	 * Author Data
	 */
	protected $containerData;
	
	/**
	 * Review Rating
	 */
	protected $reviewRating;
	
	/**
	 * If the user has posted in the item
	 */
	protected $iPostedIn;
	
	/**
	 * Constructor
	 *
	 * @param	array		$indexData		Data from index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	bool		$iPostedIn		If the user has posted in the item
	 * @return	void
	 */
	public function __construct( array $indexData, array $authorData, array $itemData, array $containerData = NULL, array $reputationData, $reviewRating = NULL, $iPostedIn = FALSE )
	{
		$this->createdDate = \IPS\DateTime::ts( $indexData['index_date_created'] );
		$this->lastUpdatedDate = \IPS\DateTime::ts( $indexData['index_date_updated'] );
		$this->indexData = $indexData;
		$this->authorData = $authorData;
		$this->itemData = $itemData;
		$this->containerData = $containerData;
		$this->reputationData = $reputationData;
		$this->reviewRating = $reviewRating;
		$this->iPostedIn = $iPostedIn;
	}
	
	/**
	 * HTML
	 *
	 * @param	string	$view	'expanded' or 'condensed'
	 * @param	bool	$asItem	Displaying results as items?
	 * @param	bool	$canIgnoreComments	Can ignore comments in the result stream? Activity stream can, but search results cannot.
	 * @return	string
	 */
	public function html( $view = 'expanded', $asItem = FALSE, $canIgnoreComments=FALSE )
	{
		return call_user_func( array( $this->indexData['index_class'], 'searchResult' ), $this->indexData, $this->authorData, $this->itemData, $this->containerData, $this->reputationData, $this->reviewRating, $this->iPostedIn, $view, $asItem, $canIgnoreComments );
	}
	
	/**
	 * Add to RSS feed
	 *
	 * @param	\IPS\Xml\Rss	$document	Document to add to
	 * @return	string
	 */
	public function addToRssFeed( \IPS\Xml\Rss $document )
	{
		$class = $this->indexData['index_class'];
		$object = $class::load( $this->indexData['index_object_id'] );
		$document->addItem( $object instanceof \IPS\Content\Comment ? $object->item()->mapped('title') : $object->mapped('title'), $object->url(), $this->indexData['index_content'], \IPS\DateTime::ts( $this->indexData['index_date_created'] ) );
	}
}