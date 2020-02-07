<?php
/**
 * @brief		archive Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		24 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * archive Task
 */
class _archive extends \IPS\Task
{
	const PROCESS_PER_BATCH = 250;
	
	/**
	 * @brief	Storage database
	 */
	protected $storage;
	
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		/* If we're disabled, disable the task, but only if we don't have things that need restoring */
		$count = \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics', array( 'topic_archive_status=?', \IPS\forums\Topic::ARCHIVE_RESTORE ) )->first();
		
		$restoreOnly = FALSE;
		if ( !\IPS\Settings::i()->archive_on AND $count > 0 )
		{
			$restoreOnly = TRUE;
		}
		
		if ( !\IPS\Settings::i()->archive_on AND $restoreOnly !== TRUE )
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'archive' ) );
		}
		
		/* Otherwise, let's do this! */
		else
		{
			/* Init */
			$postsProcessed = 0;
			
			/* Connect to the remote DB if needed */
			$this->storage = !\IPS\Settings::i()->archive_remote_sql_host ? \IPS\Db::i() : \IPS\Db::i( 'archive', array(
				'sql_host'		=> \IPS\Settings::i()->archive_remote_sql_host,
				'sql_user'		=> \IPS\Settings::i()->archive_remote_sql_user,
				'sql_pass'		=> \IPS\Settings::i()->archive_remote_sql_pass,
				'sql_database'	=> \IPS\Settings::i()->archive_remote_sql_database,
				'sql_port'		=> \IPS\Settings::i()->archive_sql_port,
				'sql_socket'	=> \IPS\Settings::i()->archive_sql_socket,
				'sql_tbl_prefix'=> \IPS\Settings::i()->archive_sql_tbl_prefix,
				'sql_utf8mb4'	=> isset( \IPS\Settings::i()->sql_utf8mb4 ) ? \IPS\Settings::i()->sql_utf8mb4 : FALSE
			) );
						
			/* Do we have a topic we're working on? */
			if ( $restoreOnly !== TRUE )
			{
				try
				{
					/* Get the topic */
					$topic = \IPS\forums\Topic::constructFromData( \IPS\Db::i()->select( '*', 'forums_topics', array( 'topic_archive_status=?', \IPS\forums\Topic::ARCHIVE_WORKING ) )->first() );
									
					/* Do as many posts as we can */
					$offset = $this->storage->select( 'MAX(archive_id)', 'forums_archive_posts', array( 'archive_topic_id=?', $topic->tid ) )->first();
					$posts = \IPS\Db::i()->select( 'forums_posts.*,forums_topics.forum_id', 'forums_posts', array( 'pid>? AND topic_id=?', intval( $offset ), $topic->tid ), 'pid ASC', static::PROCESS_PER_BATCH, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )->join( 'forums_topics', 'tid=topic_id' );
					foreach ( $posts as $post )
					{					
						$this->archive( $post, $post['forum_id'] );
						$postsProcessed++;
					}
					
					/* If we did them all, mark that this topic is done and remove from search index */
					if ( $postsProcessed == $posts->count( TRUE ) )
					{
						$topic->topic_archive_status = \IPS\forums\Topic::ARCHIVE_DONE;
						$topic->save();
						
						\IPS\Db::i()->delete( 'forums_posts', array( 'topic_id=?', $topic->tid ) );
						
						\IPS\Content\Search\Index::i()->removeFromSearchIndex( $topic );
					}
				}
				catch ( \UnderflowException $e ) { }
			}
			
			/* Any that need to be restored? */
			if ( $postsProcessed < static::PROCESS_PER_BATCH )
			{
				/* Loop topics */
				foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'forums_topics', array( 'topic_archive_status=?', \IPS\forums\Topic::ARCHIVE_RESTORE ), 'tid ASC', 250 ), 'IPS\forums\Topic' ) as $topic )
				{					
					/* Do as many posts as we can */
					$offset = \IPS\Db::i()->select( 'MAX(pid)', 'forums_posts', array( 'topic_id=?', $topic->tid ) )->first();
					foreach ( $this->storage->select( '*', 'forums_archive_posts', array( 'archive_id>? AND archive_topic_id=?', intval( $offset ), $topic->tid ), 'archive_id ASC', static::PROCESS_PER_BATCH - $postsProcessed ) as $post )
					{						
						$this->unarchive( $post );
						\IPS\Content\Search\Index::i()->index( \IPS\forums\Topic\Post::load( $post['archive_id'] ) );
						$postsProcessed++;
						if ( $postsProcessed >= static::PROCESS_PER_BATCH )
						{
							break 2;
						}
					}
										
					/* Set that it's done */
					\IPS\Db::i()->update( 'forums_topics', array( 'topic_archive_status' => \IPS\forums\Topic::ARCHIVE_EXCLUDE ), array( 'tid=?', $topic->tid ) );
					$this->storage->delete( 'forums_archive_posts', array( 'archive_topic_id=?', $topic->tid ) );
				}
			}
						
			/* Can we do even more? */
			if ( $restoreOnly !== TRUE )
			{
				$query = array_merge( array( array( 'topic_archive_status=?', \IPS\forums\Topic::ARCHIVE_NOT ) ), \IPS\Application::load('forums')->archiveWhere( \IPS\Db::i()->select( '*', 'forums_archive_rules' ) ) );
				if ( $postsProcessed < static::PROCESS_PER_BATCH )
				{
					/* Loop topics */
					foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'forums_topics', $query, 'tid ASC', 250 ), 'IPS\forums\Topic' ) as $topic )
					{					
						/* First set it as working */
						\IPS\Db::i()->update( 'forums_topics', array( 'topic_archive_status' => \IPS\forums\Topic::ARCHIVE_WORKING ), array( 'tid=?', $topic->tid ) );
						
						/* Do as many posts as we can */
						foreach ( \IPS\Db::i()->select( '*', 'forums_posts', array( 'topic_id=?', $topic->tid ), 'pid ASC', static::PROCESS_PER_BATCH - $postsProcessed ) as $post )
						{						
							$this->archive( $post, $topic->forum_id );
							$postsProcessed++;
							if ( $postsProcessed >= static::PROCESS_PER_BATCH )
							{
								break 2;
							}
						}
											
						/* Set that it's done */
						\IPS\Db::i()->update( 'forums_topics', array( 'topic_archive_status' => \IPS\forums\Topic::ARCHIVE_DONE ), array( 'tid=?', $topic->tid ) );
						\IPS\Db::i()->delete( 'forums_posts', array( 'topic_id=?', $topic->tid ) );

						/* Remove from search index */
						\IPS\Content\Search\Index::i()->removeFromSearchIndex( $topic );
					}
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * Archive
	 *
	 * @param	array		$post		The post to be archived
	 * @param	int|null	$forumId	The forum id, if known
	 * @return	void
	 */
	protected function archive( $post, $forumId=NULL )
	{		
		/* Translate the fields */
		$archive = array(
			'archive_id'				=> intval( $post['pid'] ),
			'archive_author_id'			=> intval( $post['author_id'] ),
			'archive_author_name'		=> $post['author_name'],
			'archive_ip_address'		=> $post['ip_address'],
			'archive_content_date'		=> intval( $post['post_date'] ),
			'archive_content'			=> $post['post'],
			'archive_queued'			=> $post['queued'],
			'archive_topic_id'			=> intval( $post['topic_id'] ),
			'archive_is_first'			=> intval( $post['new_topic'] ),
			'archive_bwoptions'			=> $post['post_bwoptions'],
			'archive_added'				=> time(),
			'archive_attach_key'		=> $post['post_key'],
			'archive_html_mode'			=> $post['post_htmlstate'],
			'archive_show_edited_by'	=> $post['append_edit'],
			'archive_edit_time'			=> intval( $post['edit_time'] ),
			'archive_edit_name'			=> (string) $post['edit_name'],
			'archive_edit_reason'		=> $post['post_edit_reason'],
			'archive_field_int'			=> intval( $post['post_field_int'] ),
			'archive_forum_id'			=> $forumId ?: \IPS\Db::i()->select( 'forum_id', 'forums_topics', array( 'tid=?', intval( $post['topic_id'] ) ) )->first()
		);
		
		/* Insert */
		$this->storage->replace( 'forums_archive_posts', $archive );
		
		/* Update reports table */
		\IPS\Db::i()->update( 'core_rc_index', array( 'class' => 'IPS\forums\Topic\ArchivedPost' ), array( 'class=? AND content_id=?', 'IPS\forums\Topic\Post', $post['pid'] ) );
	}
	
	/**
	 * Unarchive
	 *
	 * @param	array	$post	The post to be unarchived
	 * @return	void
	 */
	protected function unarchive( $post )
	{
		/* Translate the fields */
		$row = array(
			'pid'				=> intval( $post['archive_id'] ),
			'author_id'			=> intval( $post['archive_author_id'] ),
			'author_name'		=> $post['archive_author_name'],
			'ip_address'		=> $post['archive_ip_address'],
			'post_date'			=> intval( $post['archive_content_date'] ),
			'post'				=> $post['archive_content'],
			'queued'			=> $post['archive_queued'],
			'topic_id'			=> intval( $post['archive_topic_id'] ),
			'new_topic'			=> intval( $post['archive_is_first'] ),
			'post_bwoptions'	=> $post['archive_bwoptions'],
			'post_key'			=> $post['archive_attach_key'],
			'post_htmlstate'	=> $post['archive_html_mode'],
			'append_edit'		=> $post['archive_show_edited_by'],
			'edit_time'			=> $post['archive_edit_time'],
			'edit_name'			=> $post['archive_edit_name'],
			'post_edit_reason'	=> $post['archive_edit_reason'],
			'post_field_int'	=> $post['archive_field_int'],
		);
		
		/* Insert */
		\IPS\Db::i()->replace( 'forums_posts', $row );
		
		/* Update reports table */
		\IPS\Db::i()->update( 'core_rc_index', array( 'class' => 'IPS\forums\Topic\Post' ), array( 'class=? AND content_id=?', 'IPS\forums\Topic\ArchivedPost', $post['archive_id'] ) );
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}