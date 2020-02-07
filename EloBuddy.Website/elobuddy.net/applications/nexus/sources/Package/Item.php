<?php
/**
 * @brief		Nexus Package Content Item Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Model
 */
class _Item extends \IPS\Content\Item implements \IPS\Content\Featurable, \IPS\Content\Shareable, \IPS\Content\Embeddable
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'store';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'nexus_packages';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'p_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Node Class
	 */
	public static $containerNodeClass = 'IPS\nexus\Package\Group';
	
	/**
	 * @brief	Review Class
	 */
	public static $reviewClass = 'IPS\nexus\Package\Review';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'title'					=> 'name',
		'container'				=> 'group',
		'featured'				=> 'featured',
		'num_reviews'			=> 'reviews',
		'unapproved_reviews'	=> 'unapproved_reviews',
		'hidden_reviews'		=> 'hidden_reviews',
		'rating'				=> 'rating'
	);
	
	/**
	 * @brief	Title
	 */
	public static $title = 'product';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'archive';
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get_title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack("nexus_package_{$this->id}");
	}
	
	/**
	 * Get description
	 *
	 * @return	string
	 */
	public function content()
	{
		return \IPS\Member::loggedIn()->language()->get("nexus_package_{$this->id}_desc"); // Has to be get() rather than addToStack() so we can reliably strips tags, etc.
	}
	
	/**
	 * Can view?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
		if ( !$this->store )
		{
			return FALSE;
		}
		
		$member = $member ?: \IPS\Member::loggedIn();
		return $this->member_groups === '*' or $member->inGroup( explode( ',', $this->member_groups ) );
	}
	
	/**
	 * Get items with permisison check
	 *
	 * @param	array		$where				Where clause
	 * @param	string		$order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array	$limit				Limit clause
	 * @param	string|NULL	$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param	mixed		$includeHiddenItems	Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly			If true will return the count
	 * @param	array|null	$joins				Additional arbitrary joins for the query
	 * @param	mixed		$skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param	bool		$joinTags			If true, will join the tags table
	 * @param	bool		$joinAuthor			If true, will join the members table for the author
	 * @param	bool		$joinLastCommenter	If true, will join the members table for the last commenter
	 * @param	bool		$showMovedLinks		If true, moved item links are included in the results
	 * @return	\IPS\Patterns\ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( $where=array(), $order=NULL, $limit=10, $permissionKey='read', $includeHiddenItems=\IPS\Content\Hideable::FILTER_AUTOMATIC, $queryFlags=0, \IPS\Member $member=NULL, $joinContainer=FALSE, $joinComments=FALSE, $joinReviews=FALSE, $countOnly=FALSE, $joins=NULL, $skipPermission=FALSE, $joinTags=TRUE, $joinAuthor=TRUE, $joinLastCommenter=TRUE, $showMovedLinks=FALSE )
	{
		$member = $member ?: \IPS\Member::loggedIn();

    	$where[] = "( p_member_groups='*' OR " . \IPS\Db::i()->findInSet( 'p_member_groups', $member->groups ) . ' )';
		$where[] = array( 'p_store=?', 1 );

    	$return = parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
		$return->classname = 'IPS\nexus\Package';
		return $return;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=nexus&module=store&controller=product&id={$this->id}", 'front', 'store_product', \IPS\Http\Url::seoTitle( \IPS\Member::loggedIn()->language()->get( 'nexus_package_' . $this->id ) ) );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}

	/**
	 * Can review?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canReview( $member=NULL )
	{
		if ( !$this->reviewable )
		{
			return FALSE;
		}
		
		$member = $member ?: \IPS\Member::loggedIn();
		
		if ( !parent::canReview( $member ) )
		{
			return FALSE;
		}
		
		if ( !\IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_member=?', 'nexus', 'package', $this->id, $member->member_id ) )->first() )
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * Should new reviews be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewReviews( \IPS\Member $member )
	{
		if ( $this->review_moderate )
		{
			return TRUE;
		}
		
		return parent::moderateNewReviews( $member );
	}
	
	/**
	 * Images
	 *
	 * @return	\IPS\File\Iterator
	 */
	public function images()
	{
		return new \IPS\File\Iterator( \IPS\Db::i()->select( 'image_location', 'nexus_package_images', array( 'image_product=?', $this->id ),'image_primary desc' ), 'nexus_Products', NULL, TRUE );
	}
	
	/* !Embeddable */
	
	/**
	 * Get content for embed
	 *
	 * @param	array	$params	Additional parameters to add to URL
	 * @return	string
	 */
	public function embedContent( $params )
	{
		return \IPS\Theme::i()->getTemplate( 'global', 'nexus' )->embedProduct( $this, $this->url()->setQueryString( $params ), $this->embedImage() );
	}
		
	/**
	 * Get image for embed
	 *
	 * @return	\IPS\File|NULL
	 */
	public function embedImage()
	{
		$product = \IPS\nexus\Package::load( $this->id );
		return $product->_data['image'] ? \IPS\File::get( 'nexus_Products', $product->_data['image'] ) : NULL;
	}

	/**
	 * Get mapped value
	 *
	 * @param	string	$key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( $key )
	{
		if ( $key === 'title' )
		{
			return $this->title;
		}
		return parent::mapped($key);
	}
}