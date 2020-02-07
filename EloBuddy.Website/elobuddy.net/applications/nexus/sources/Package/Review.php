<?php
/**
 * @brief		Product Review
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		5 May 2014
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
 * Package Review
 */
class _Review extends \IPS\Content\Review implements \IPS\Content\ReportCenter, \IPS\Content\EditHistory, \IPS\Content\Hideable, \IPS\Content\Embeddable
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static $itemClass = 'IPS\nexus\Package\Item';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_reviews';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'review_';

	/**
	 * @brief	Title
	 */
	public static $title = 'product_reviews';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'archive';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'				=> 'product',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'text',
		'date'				=> 'date',
		'approved'			=> 'approved',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_date',
		'edit_show'			=> 'edit_show',
		'edit_member_name'	=> 'edit_member_name',
		'edit_reason'		=> 'edit_reason',
		'edit_member_id'	=> 'edit_member_id',
		'rating'			=> 'rating',
		'votes_total'		=> 'votes',
		'votes_helpful'		=> 'useful',
		'votes_data'		=> 'vote_data',
	);
	
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'store';
	
	/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'nexus-products';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = FALSE;
	
	/**
	 * @brief	Include In Sitemap
	 */
	public static $includeInSitemap = FALSE;
	
	/**
	 * Get content for header in content tables
	 *
	 * @return	callable
	 */
	public function contentTableHeader()
	{
		return \IPS\Theme::i()->getTemplate( 'global', static::$application )->commentTableHeader( $this, \IPS\nexus\Package::load( $this->item()->id ), $this->item() );
	}
}