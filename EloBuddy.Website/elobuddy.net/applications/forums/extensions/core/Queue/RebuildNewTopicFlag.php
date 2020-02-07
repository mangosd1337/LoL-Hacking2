<?php
/**
 * @brief		Background Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Converter
 * @since		26 Feb 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class _RebuildNewTopicFlag
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		try
		{
			$data['count'] = \IPS\Db::i()->select( 'MAX(tid)', 'forums_topics' )->first();
		}
		catch( \Exception $e )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return NULL;
		}
		
		return $data;
	}
	
	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int|null				New offset or NULL if complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		if ( !class_exists( 'IPS\forums\Topic' ) OR !\IPS\Application::appisEnabled( 'forums' ) )
		{
			throw new \OutOfRangeException;
		}
		
		$last = NULL;
		
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'forums_topics', array( "tid>?", $offset ), "tid ASC", array( 0, 50 ) ), 'IPS\forums\Topic' ) AS $topic )
		{
			if ( $topic->isArchived() == FALSE )
			{
				try
				{
					/* Set first post */
					$topic->topic_firstpost = \IPS\Db::i()->select( 'pid', 'forums_posts', array( 'topic_id=?', $topic->tid ), 'post_date ASC', 1 )->first();
					$topic->save();

					/* Reset new_topic value for topic */
					\IPS\Db::i()->update( 'forums_posts', array( 'new_topic' => 0 ), array( 'topic_id=?', $topic->tid ) );
					\IPS\Db::i()->update( 'forums_posts', array( 'new_topic' => 1 ), array( 'pid=?', $topic->topic_firstpost ) );
				}
				/* Underflow exception may occur if the topic doesn't have any posts for an unknown reason */
				catch( \UnderflowException $e ) {}
			}
			else
			{
				try
				{
					/* Set first post */
					$topic->topic_firstpost = \IPS\forums\Topic\ArchivedPost::db()->select( 'archive_id', 'forums_archive_posts', array( "archive_topic_id=?", $topic->tid ), "archive_content_date ASC" )->first();
					$topic->save();

					/* Reset new_topic value for topic */
					\IPS\forums\Topic\ArchivedPost::db()->update( 'forums_archive_posts', array( 'archive_is_first' => 0 ), array( 'archive_topic_id=?', $topic->tid ) );
					\IPS\forums\Topic\ArchivedPost::db()->update( 'forums_archive_posts', array( 'archive_is_first' => 1 ), array( 'archive_id=?', $topic->topic_firstpost ) );
				}
				/* Underflow exception may occur if the topic doesn't have any posts for an unknown reason */
				catch( \UnderflowException $e ) {}
			}

			$last = $topic->tid;
		}
		
		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		return array( 'text' =>  \IPS\Member::loggedIn()->language()->addToStack('queue_rebuilding_new_topic_flag'), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}