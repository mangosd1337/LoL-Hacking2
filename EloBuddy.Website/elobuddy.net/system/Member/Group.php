<?php
/**
 * @brief		Group Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Model
 */
class _Group extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_groups';
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'g_id';
	
	/**
	 * @brief	Bitwise keys
	 */
	protected static $bitOptions = array(
		'g_bitoptions'	=> array(
			'g_bitoptions'	=> array(
				'gbw_mod_post_unit_type'	=> 1, 			// Lift moderation after x. 1 is days, 0 is posts. Corresponds to g_mod_post_unit
				'gbw_ppd_unit_type'			=> 2, 			// Lift post-per-day limit after x. 1 is days, 0 is posts. Corresponds to g_ppd_unit
				'gbw_displayname_unit_type'	=> 4, 			// Username change restrictions. 1 is days, 0 is posts. Corresponds to g_displayname_unit
				'gbw_sig_unit_type'			=> 8, 			// Signature edit restrictions. 1 is days, 0 is posts. Corresponds to g_sig_unit
				'gbw_promote_unit_type'		=> 16, 			// Type of group promotion to use. 1 is days since joining, 0 is content count. Corresponds to g_promotion
				'gbw_no_status_update'		=> 32, 			// Can NOT post status updates
				// 64 is deprecated (previously gbw_soft_delete)
				'gbw_soft_delete_own'		=> 128, 		// Allow users of this group to hide their own submitted content
				// 256 is deprecated (previously gbw_soft_delete_own_topic)
				// 512 is deprecated (previously gbw_un_soft_delete)
				// 1024 is deprecated (previously gbw_soft_delete_see)
				// 2048 is deprecated (previously gbw_soft_delete_topic)
				// 4096 is deprecated (previously gbw_un_soft_delete_topic)
				// 8192 is deprecated (previously gbw_soft_delete_topic_see)
				// 16384 is deprecated (previously gbw_soft_delete_reason)
				// 32768 is deprecated (previously gbw_soft_delete_see_post)
				// 65536 is deprecated (previously gbw_allow_customization)
				// 131072 is deprecated (previously gbw_allow_url_bgimage)
				'gbw_allow_upload_bgimage'	=> 262144, 		// Can upload a cover photo?
				'gbw_view_reps'				=> 524288, 		// Can view who gave reputation?
				'gbw_no_status_import'		=> 1048576, 	// Can NOT import status updates from Facebook/Twitter
				'gbw_disable_tagging'		=> 2097152, 	// Can NOT use tags
				'gbw_disable_prefixes'		=> 4194304, 	// Can NOT use prefixes
				// 8388608 is deprecated (previously gbw_view_last_info)
				// 16777216 is deprecated (previously gbw_view_online_lists)
				// 33554432 is deprecated (previously gbw_hide_leaders_page)
				'gbw_pm_unblockable'		=> 67108864,	// Deprecated in favour of global unblockable setting
				'gbw_pm_override_inbox_full'=> 134217728,	// 1 means this group can send other members PMs even when that member's inbox is full
				'gbw_no_report'				=> 268435456,	// 1 means they CAN'T report content. 0 means they can.
				'gbw_cannot_be_ignored'		=> 536870912,	// 1 means they cannot be ignored. 0 means they can
				'gbw_delete_attachments'	=> 1073741824,	// 1 means they can delete attachments from the "My Attachments" screen
			)
		)
	);
		
	/**
	 * Load Record
	 *
	 * @see		\IPS\Db::build
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function load( $id, $idField=NULL, $extraWhereClause=NULL )
	{
		if ( ( $idField === NULL or $idField === 'g_id' ) and $extraWhereClause === NULL )
		{
			$groups = static::groupStore();
			if ( isset( $groups[ $id ] ) )
			{
				return parent::constructFromData( $groups[ $id ] );
			}
		}
			
		return parent::load( $id, $idField, $extraWhereClause );		
	}
	
	/**
	 * Group datastore
	 *
	 * @return	array
	 */
	protected static function groupStore()
	{
		if ( !isset( \IPS\Data\Store::i()->groups ) )
		{
			\IPS\Data\Store::i()->groups = iterator_to_array( \IPS\Db::i()->select( 'core_groups.*', 'core_groups', NULL, 'core_sys_lang_words.word_custom' )->join( 'core_sys_lang_words', array( "lang_id=? AND word_app=? AND word_key=CONCAT( 'core_group_', core_groups.g_id )", \IPS\Member::loggedIn()->language()->id, 'core' ) )->setKeyField( 'g_id' ) );
		}
		return \IPS\Data\Store::i()->groups;
	}
	
	/**
	 * @brief	Stored Groups
	 */
	protected static $allGroups;
		
	/**
	 * Get groups
	 *
	 * @param	bool	$showAdminGroups	Show admin groups. Used to restrict admin groups from being available when you cannot add/edit members in them.
	 * @param	bool	$showGuestGroups	Show guest groups. Used to remove the guest group from the available groups returned.
	 * @return	array
	 */
	public static function groups( $showAdminGroups=TRUE, $showGuestGroups=TRUE )
	{
		if ( !static::$allGroups )
		{
			static::$allGroups = iterator_to_array( new \IPS\Patterns\ActiveRecordIterator( new \ArrayIterator( static::groupStore() ), 'IPS\Member\Group' ) );
		}
		$groups = static::$allGroups;

		if ( !$showGuestGroups )
		{
			unset( $groups[ \IPS\Settings::i()->guest_group ] );
		}

		if( !$showAdminGroups )
		{
			$administrators = \IPS\Member::administrators();
			foreach( $groups as $k => $_group )
			{
				if ( isset( $administrators['g'][ $_group->g_id ] ) )
				{
					unset( $groups[ $k ] );
				}
			}
		}

		return $groups;
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		$oldId = $this->g_id;
		parent::__clone();

		/* Rebuild permission indexes */
		$perms = array( 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' );
		$where = array();
		foreach( $perms as $key )
		{
			$where[] = \IPS\Db::i()->findInSet( $key, array($oldId) );
		}

		foreach( \IPS\Db::i()->select( '*', 'core_permission_index', implode( ' OR ', $where ) ) as $index )
		{
			foreach( $perms as $key )
			{
				$groups = explode( ",", $index[$key] );
				if( in_array( $oldId, $groups ) and !in_array( $this->g_id, $groups ) )
				{
					$groups[] = $this->g_id;
				}

				$index[$key] = implode( ",", $groups );
			}
			\IPS\Db::i()->update( 'core_permission_index', $index, array( 'perm_id = ?', $index['perm_id'] ) );
		}

		\IPS\Lang::saveCustom( 'core', "core_group_{$this->g_id}", iterator_to_array( \IPS\Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', "core_group_{$oldId}" ) )->setKeyField('lang_id')->setValueField('word_custom') ) );
	}
	
	/**
	 * Get data
	 *
	 * @return	array
	 */
	public function data()
	{
		return $this->_data;
	}
	
	/**
	 * Magic Method: To String
	 * Returns group name
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->name;
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function get_name()
	{
		return \IPS\Member::loggedIn()->language()->addToStack( "core_group_{$this->g_id}" );
	}
	
	/**
	 * Get formatted name
	 *
	 * @return	string
	 */
	public function get_formattedName()
	{
		return $this->formatName( $this->name );
	}
	
	/**
	 * Format Name
	 *
	 * @return	string
	 */
	public function formatName( $name )
	{
		return ( $this->prefix . htmlspecialchars( $name, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) . $this->suffix );
	}

	/**
	 * Can use module
	 *
	 * @param	\IPS\Application\Module	$module	The module to test
	 * @return	bool
	 */
	public function canAccessModule( $module )
	{
		return $module->can( 'view', $this );
	}
	
	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return	void
	 */
	function save()
	{
		parent::save();
		unset( \IPS\Data\Store::i()->groups );
	}
		
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		if ( in_array( $this->g_id, array( \IPS\Settings::i()->guest_group, \IPS\Settings::i()->member_group, \IPS\Settings::i()->admin_group ) ) )
		{
			throw new \InvalidArgumentException;
		}

		/* remove group from mod & staff section */

		\IPS\Db::i()->delete( 'core_leaders', array( 'leader_type=? AND leader_type_id = ?', 'g', $this->g_id ) );
		\IPS\Db::i()->delete( 'core_moderators', array( 'type=? AND id = ?', 'g', $this->g_id ) );
		\IPS\Db::i()->delete( 'core_admin_permission_rows', array( 'row_id=? and row_id_type=?', $this->g_id, 'group' ) );

		/* Make sure no other groups have this group ID set to promote to */
		foreach( static::groups() as $group )
		{
			$promote = explode( '&', $group->g_promotion );

			if( $promote[0] == $this->g_id )
			{
				$group->g_promotion = '-1&' . $promote[1];
				$group->save();
			}
		}
		
		parent::delete();
		\IPS\Lang::deleteCustom( 'core', 'core_group_' . $this->g_id );
		unset( \IPS\Data\Store::i()->groups );
	}
	
	/**
	 * Get output for API
	 *
	 * @return		array
	 * @apiresponse	int			id				ID number
	 * @apiresponse	string		name			Name
	 * @apiresponse	string		formattedName	Name with formatting
	 */
	public function apiOutput()
	{
		return array(
			'id'				=> $this->g_id,
			'name'				=> $this->name,
			'formattedName'		=> $this->formattedName,
		);
	}
}