<?php
/**
 * @brief		Archived Post Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		24 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\Topic;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Post Model
 */
class _ArchivedPost extends Post
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Connection
	 */
	public static function db()
	{
		return ( !\IPS\Settings::i()->archive_remote_sql_host ) ? \IPS\Db::i() : \IPS\Db::i( 'archive', array(
			'sql_host'		=> \IPS\Settings::i()->archive_remote_sql_host,
			'sql_user'		=> \IPS\Settings::i()->archive_remote_sql_user,
			'sql_pass'		=> \IPS\Settings::i()->archive_remote_sql_pass,
			'sql_database'	=> \IPS\Settings::i()->archive_remote_sql_database,
			'sql_port'		=> \IPS\Settings::i()->archive_sql_port,
			'sql_socket'	=> \IPS\Settings::i()->archive_sql_socket,
			'sql_tbl_prefix'=> \IPS\Settings::i()->archive_sql_tbl_prefix,
			'sql_utf8mb4'	=> isset( \IPS\Settings::i()->sql_utf8mb4 ) ? \IPS\Settings::i()->sql_utf8mb4 : FALSE
		) );
	}
		
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'forums_archive_posts';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'archive_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'				=> 'topic_id',
		'author'			=> 'author_id',
		'author_name'		=> 'author_name',
		'content'			=> 'content',
		'date'				=> 'content_date',
		'ip_address'		=> 'ip_address',
		'edit_time'			=> 'edit_time',
		'edit_show'			=> 'append_edit',
		'edit_member_name'	=> 'edit_name',
		'edit_reason'		=> 'post_edit_reason',
		'hidden'			=> 'queued',
		'first'				=> 'is_first'
	);

	/**
	 * @brief	Bitwise values for post_bwoptions field
	 */
	public static $bitOptions = array(
		'bwoptions' => array(
			'bwoptions' => array(
				'best_answer'	=> 1
			)
		)
	);

	/**
	 * @brief	Database Column ID
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * Post count for member
	 *
	 * @param	\IPS\Member	$member	The memner
	 * @return	int
	 */
	public static function memberPostCount( \IPS\Member $member )
	{
		return static::db()->select( 'COUNT(*)', 'forums_archive_posts', array(
			array( 'archive_author_id=?', $member->member_id ),
			array( \IPS\Db::i()->in( 'archive_forum_id', iterator_to_array( \IPS\Db::i()->select( 'id', 'forums_forums', 'inc_postcount=0' ) ), TRUE ) )
		) )->first();
	}
	
	/**
	 * Joins (when loading comments)
	 *
	 * @param	\IPS\Content\Item	$item			The item
	 * @return	array
	 */
	public static function joins( \IPS\Content\Item $item )
	{
		$return = parent::joins( $item );
		
		unset( $return['author'] );
		unset( $return['author_pfields'] );
		
		return $return;
	}
	
	/**
	 * Can edit?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can hide?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canHide( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can unhide?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 */
	public function canUnhide( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can split?
	 *
	 * @param	\IPS\Member|NULL	$member The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canSplit( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can give reputation?
	 *
	 * @note	This method is also ran to check if a member can "unrep"
	 * @param	int					$type	1 for positive, -1 for negative
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canGiveReputation( $type, \IPS\Member $member = NULL )
	{
		return FALSE;
	}
}