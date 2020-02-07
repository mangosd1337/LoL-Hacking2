<?php
/**
 * @brief		Member Sync
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		31 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\MemberSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Sync
 */
class _System
{
	/**
	 * Member is merged with another member
	 *
	 * @param	\IPS\Member	$member		Member being kept
	 * @param	\IPS\Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( $member, $member2 )
	{
		/* Standard stuff */
		foreach ( array(
				'core_admin_logs'				=> 'member_id',
				'core_advertisements'			=> 'ad_member',
				'core_announcements'			=> 'announce_member_id',
				'core_attachments'				=> 'attach_member_id',
				'core_dnames_change'			=> 'dname_member_id',
				'core_edit_history'				=> 'member',
				'core_error_logs'				=> 'log_member',
				'core_follow'					=> 'follow_member_id',
				'core_ignored_users'			=> 'ignore_owner_id',
				'core_ignored_users'			=> 'ignore_ignore_id',
				'core_incoming_emails'			=> 'rule_added_by',
				'core_members_warn_logs'		=> 'wl_member',
				'core_members_warn_logs'		=> 'wl_moderator',
				'core_message_posts'			=> 'msg_author_id',
				'core_message_topic_user_map'	=> 'map_user_id',
				'core_message_topics'			=> 'mt_starter_id',
				'core_moderator_logs'			=> 'member_id',
				'core_notification_preferences'	=> 'member_id',
				'core_notifications'			=> 'member',
				'core_polls'					=> 'starter_id',
				'core_ratings'					=> 'member',
				'core_rc_comments'				=> 'comment_by',
				'core_rc_index'					=> 'first_report_by',
				'core_rc_index'					=> 'author',
				'core_rc_reports'				=> 'report_by',
				'core_reputation_index'			=> 'member_id',
				'core_soft_delete_log'			=> 'sdl_obj_member_id',
				'core_sys_social_group_members'	=> 'member_id',
				'core_sys_social_groups'		=> 'owner_id',
				'core_tags'						=> 'tag_member_id',
				'core_upgrade_history'			=> 'upgrade_mid',
				'core_voters'					=> 'member_id',
			) as $table => $column
		)
		{
			\IPS\Db::i()->update( $table, array( $column => $member->member_id ), array( $column . '=?', $member2->member_id ), array(), NULL, \IPS\Db::IGNORE );
		}
		
		/* Admin/Mod */
		\IPS\Db::i()->update( 'core_admin_permission_rows', array( 'row_id' => $member->member_id ), array( 'row_id=? AND row_id_type=?', $member2->member_id, 'member' ), array(), NULL, \IPS\Db::IGNORE );
		\IPS\Db::i()->update( 'core_leaders', array( 'leader_type_id' => $member->member_id ), array( 'leader_type_id=? AND leader_type=?', $member2->member_id, 'm' ), array(), NULL, \IPS\Db::IGNORE );
		\IPS\Db::i()->update( 'core_moderators', array( 'id' => $member->member_id ), array( 'id=? AND type=?', $member2->member_id, 'm' ), array(), NULL, \IPS\Db::IGNORE );

		/* Followers */
		\IPS\Db::i()->update( 'core_follow', array( 'follow_rel_id' => $member->member_id ), array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', 'core', 'member', $member2->member_id ), array(), NULL, \IPS\Db::IGNORE );
						
		/* Delete duplicate stuff */
		\IPS\Db::i()->delete( 'core_item_markers', array( 'item_member_id=?', $member2->member_id ) );
		\IPS\Db::i()->delete( 'core_sessions', array( 'member_id=?', $member2->member_id ) );
		\IPS\Db::i()->delete( 'core_sys_cp_sessions', array( 'session_member_id=?', $member2->member_id ) );
		\IPS\Db::i()->delete( 'core_validating', array( 'member_id=?', $member2->member_id ) );
		\IPS\Db::i()->query( 'DELETE row1 FROM ' . \IPS\Db::i()->prefix . 'core_reputation_index row1, ' . \IPS\Db::i()->prefix . 'core_reputation_index row2 WHERE row1.id > row2.id AND row1.member_id = row2.member_id AND row1.app = row2.app AND row1.type = row2.type AND row1.type_id = row2.type_id' );
		\IPS\Db::i()->delete( 'core_message_topic_user_map', array( 'map_user_id=?', $member2->member_id ) );
		\IPS\Db::i()->query( 'DELETE row1 FROM ' . \IPS\Db::i()->prefix . 'core_message_topic_user_map row1, ' . \IPS\Db::i()->prefix . 'core_message_topic_user_map row2 WHERE row1.map_id > row2.map_id AND row1.map_user_id = row2.map_user_id AND row1.map_topic_id = row2.map_topic_id' );
		\IPS\Db::i()->query( 'DELETE row1 FROM ' . \IPS\Db::i()->prefix . 'core_follow row1, ' . \IPS\Db::i()->prefix . 'core_follow row2 WHERE row1.follow_added > row2.follow_added AND row1.follow_app = "core" AND row1.follow_area = "member" AND ( row1.follow_member_id = row2.follow_member_id OR row1.follow_member_id=row2.follow_rel_id )' );
		\IPS\Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=follow_member_id', 'core', 'member') );

		/* Set warning level */
		$member->warn_level += $member2->warn_level;
		$member->save();

		/* Recount notifications */
		$member->recountNotifications();

		unset( \IPS\Data\Cache::i()->advertisements );
	}
	
	/**
	 * Member is deleted
	 *
	 * @param	$member	\IPS\Member	The member
	 * @return	void
	 */
	public function onDelete( $member )
	{
		/* We have to remove notifications for these, otherwise once we remove the actual items below any existing notifications will throw an
			uncaught exception since the status or reply object won't be loaded */
		foreach( \IPS\Db::i()->select( 'reply_id', 'core_member_status_replies', array( 'reply_member_id=?', $member->member_id ) ) as $reply )
		{
			\IPS\Db::i()->delete( 'core_notifications', array( 'item_class=? AND item_id=?', 'IPS\\core\\Statuses\\Reply', $reply ) );
		}

		\IPS\Db::i()->delete( 'core_search_index', array( 'index_class=? AND index_author=?', 'IPS\\core\\Statuses\\Reply', $member->member_id ) );

		foreach( \IPS\Db::i()->select( 'status_id', 'core_member_status_updates', array( 'status_member_id=? OR status_author_id=?', $member->member_id, $member->member_id ) ) as $status )
		{
			\IPS\Db::i()->delete( 'core_notifications', array( 'item_class=? AND item_id=?', 'IPS\\core\\Statuses\\Status', $status ) );
		}

		\IPS\Db::i()->delete( 'core_search_index', array( 'index_class=? AND index_author=?', 'IPS\\core\\Statuses\\Status', $member->member_id ) );

		/* Generic deletes */
		foreach ( array(
			'core_dnames_change'			=> 'dname_member_id',
			'core_error_logs'				=> 'log_member',
			'core_follow'					=> 'follow_member_id',
			'core_ignored_users'			=> 'ignore_owner_id',
			'core_ignored_users'			=> 'ignore_ignore_id',
			'core_item_markers'				=> 'item_member_id',
			'core_members_warn_logs'		=> 'wl_member',
			'core_notification_preferences'	=> 'member_id',
			'core_notifications'			=> 'member',
			'core_pfields_content'			=> 'member_id',
			'core_ratings'					=> 'member',
			'core_reputation_index'			=> 'member_id',
			'core_reputation_index'			=> 'member_received',
			'core_sessions'					=> 'member_id',
			'core_sys_cp_sessions'			=> 'session_member_id',
			'core_sys_social_groups'		=> 'owner_id',
			'core_sys_social_group_members'	=> 'member_id',
			'core_validating'				=> 'member_id',
            'core_member_status_updates'    => 'member_id',
            'core_member_status_updates'    => 'status_author_id',
            'core_member_status_replies'    => 'reply_member_id',
		) as $table => $column )
		{
			\IPS\Db::i()->delete( $table, array( $column . '=?', $member->member_id ) );
		}
		
		\IPS\Db::i()->update( 'core_announcements', array( 'announce_member_id' => 0 ), array( 'announce_member_id=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_attachments', array( 'attach_member_id' => 0 ), array( 'attach_member_id=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_edit_history', array( 'member' => 0 ), array( 'member=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_incoming_emails', array( 'rule_added_by' => 0 ), array( 'rule_added_by=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_members_warn_logs', array( 'wl_moderator' => 0 ), array( 'wl_moderator=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_moderator_logs', array( 'member_id' => 0 ), array( 'member_id=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_polls', array( 'starter_id' => 0 ), array( 'starter_id=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_soft_delete_log', array( 'sdl_obj_member_id' => 0 ), array( 'sdl_obj_member_id=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_upgrade_history', array( 'upgrade_mid' => 0 ), array( 'upgrade_mid=?', $member->member_id ) );
		\IPS\Db::i()->update( 'core_voters', array( 'member_id' => 0 ), array( 'member_id=?', $member->member_id ) );
		
		\IPS\Db::i()->delete( 'core_acp_tab_order', array( 'id=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'core_admin_permission_rows', array( 'row_id=? AND row_id_type=?', $member->member_id, 'member' ) );
		\IPS\Db::i()->delete( 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', 'core', 'member', $member->member_id ) );
		\IPS\Db::i()->delete( 'core_leaders', array( 'leader_type_id=? AND leader_type=?', $member->member_id, 'm' ) );
		\IPS\Db::i()->delete( 'core_moderators', array( 'id=? AND type=?', $member->member_id, 'm' ) );

		\IPS\File::unclaimAttachments( 'core_Signatures', $member->member_id );
		\IPS\File::unclaimAttachments( 'core_Staffdirectory', $member->member_id );
	}
}