<?php
/**
 * @brief		Daily Cleanup Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Daily Cleanup Task
 */
class _cleanup extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\RuntimeException
	 */
	public function execute()
	{
		/* Delete old password reset requests */
		\IPS\Db::i()->delete( 'core_validating', array( 'lost_pass=1 AND email_sent < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) )->getTimestamp() ) );
		
		/* Delete old validating members */
		if ( \IPS\Settings::i()->validate_day_prune )
		{
			$select = \IPS\Db::i()->select( 'core_validating.member_id, core_members.member_posts', 'core_validating', array( 'core_validating.new_reg=1 AND core_validating.coppa_user<>1 AND core_validating.entry_date<? AND core_validating.lost_pass<>1 AND core_validating.user_verified=0 AND core_members.member_posts < 1', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->validate_day_prune . 'D' ) )->getTimestamp() ) )->join( 'core_members', 'core_members.member_id=core_validating.member_id' );

			foreach ( $select as $row )
			{
				try
				{
					\IPS\Member::load( $row['member_id'] )->delete();
				}
				catch ( \OutOfRangeException $e )
				{
					\IPS\Db::i()->delete( 'core_validating', array( 'member_id=?', $row['member_id'] ) );
				}
			}
		}

		/* Delete edit history past prune date */
		if( \IPS\Settings::i()->edit_log_prune > 0 )
		{
			\IPS\Db::i()->delete( 'core_edit_history', array( 'time < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->edit_log_prune . 'D' ) )->getTimestamp() ) );
		}

		/* Delete task logs older than the prune-since date */
		if( \IPS\Settings::i()->prune_log_tasks )
		{
			\IPS\Db::i()->delete( 'core_tasks_log', array( 'time < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_tasks . 'D' ) )->getTimestamp() ) );
		}

		/* Delete email error logs older than the prune-since date */
		if( \IPS\Settings::i()->prune_log_email_error )
		{
			\IPS\Db::i()->delete( 'core_mail_error_logs', array( 'mlog_date < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_email_error . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and admin logs */
		if( \IPS\Settings::i()->prune_log_admin )
		{
			\IPS\Db::i()->delete( 'core_admin_logs', array( 'ctime < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_admin . 'D' ) )->getTimestamp() ) );
		}

		/* ...and moderators logs */
		if( \IPS\Settings::i()->prune_log_moderator )
		{
			\IPS\Db::i()->delete( 'core_moderator_logs', array( 'ctime < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_moderator . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and error logs */
		if( \IPS\Settings::i()->prune_log_error )
		{
			\IPS\Db::i()->delete( 'core_error_logs', array( 'log_date < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_error . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and system logs */
		if( \IPS\Settings::i()->prune_log_system )
		{
			\IPS\Log::pruneLogs( \IPS\Settings::i()->prune_log_system );
		}
		
		/* ...and spam service logs */
		if( \IPS\Settings::i()->prune_log_spam )
		{
			\IPS\Db::i()->delete( 'core_spam_service_log', array( 'log_date < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_spam . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and admin login logs */
		if( \IPS\Settings::i()->prune_log_adminlogin )
		{
			\IPS\Db::i()->delete( 'core_admin_login_logs', array( 'admin_time < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_log_adminlogin . 'D' ) )->getTimestamp() ) );
		}
		
		/* ...and geoip cache */
		\IPS\Db::i()->delete( 'core_geoip_cache', array( 'date < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'PT12H' ) )->getTimestamp() ) );

		/* ...and API logs */
		if( \IPS\Settings::i()->api_log_prune )
		{
			\IPS\Db::i()->delete( 'core_api_logs', array( 'date < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->api_log_prune . 'D' ) )->getTimestamp() ) );
		}
		
		/* Delete old notifications */
		if ( \IPS\Settings::i()->prune_notifications )
		{
			$memberIds	= array();

			foreach( \IPS\Db::i()->select( 'member', 'core_notifications', array( 'sent_time < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_notifications . 'M' ) )->getTimestamp() ) ) as $member )
			{
				$memberIds[ $member ]	= $member;
			}

			\IPS\Db::i()->delete( 'core_notifications', array( 'sent_time < ?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->prune_notifications . 'M' ) )->getTimestamp() ) );

			foreach( $memberIds as $member )
			{
				\IPS\Member::load( $member )->recountNotifications();
			}
		}
				
		/* Delete moved links */
		if ( \IPS\Settings::i()->topic_redirect_prune )
		{
			foreach ( \IPS\Content::routedClasses( FALSE, FALSE, TRUE ) as $class )
			{
				if ( isset( $class::$databaseColumnMap['moved_on'] ) )
				{
					foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', $class::$databaseTable, array( $class::$databasePrefix . $class::$databaseColumnMap['moved_on'] . '>0 AND ' . $class::$databasePrefix . $class::$databaseColumnMap['moved_on'] . '<?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->topic_redirect_prune . 'D' ) )->getTimestamp() ), $class::$databasePrefix . $class::$databaseColumnId, 100 ), $class ) as $item )
					{
						$item->delete();
					}
				}
			}
		}
		
		/* Remove warnings points */
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_members_warn_logs', array( 'wl_expire_date>0 AND wl_expire_date<?', time() ), 'wl_date ASC', 25 ), 'IPS\core\Warnings\Warning' ) as $warning )
		{
			$member = \IPS\Member::load( $warning->member );
			$member->warn_level -= $warning->points;
			$member->save();
			
			$warning->expire_date = 0;
			$warning->save();
		}
		
		/* Delete any incompleted accounts (for example, someone has clicked "Sign in with Twitter" and never provided a username or email */
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_members', array( array( 'name=? OR email=?', '', '' ), array( 'joined<? AND last_visit=0', \IPS\DateTime::create()->sub( new \DateInterval( 'PT1H' ) )->getTimestamp() ) ) ), 'IPS\Member' ) as $incompleteMember )
		{
			$incompleteMember->delete();
		}
		
		/* Prune search index */
		if ( \IPS\Settings::i()->search_index_timeframe )
		{
			try
			{
				\IPS\Content\Search\Index::i()->prune( \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->search_index_timeframe . 'D' ) ) );
			}
			catch ( \UnexpectedValueException $e ) { }
		}
		
		/* Remove widgets */
		\IPS\Widget::emptyTrash();

		/* Reset expired "moderate content till.." timestamps */
		\IPS\Db::i()->update( 'core_members', array( 'mod_posts' => 0 ), array( 'mod_posts != -1 and mod_posts <?', time() ) );


		/* Set expired announcements inactive */
		\IPS\Db::i()->update( 'core_announcements', array( 'announce_active' => 0 ), array( 'announce_active = 1 and announce_end <?', time() ) );

		return NULL;
	}
}