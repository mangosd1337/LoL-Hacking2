<?php
/**
 * @brief		Background Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		18 Mar 2015
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
 * Background Task to fix an issue where topics are not hidden, but the first post is hidden with -1.
 */
class _HiddenFirstPost
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$data['count'] = \IPS\Db::i()->select( 'COUNT(*)', 'forums_posts', 'new_topic = 1 and queued = -1' )->first();
		
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
		$select = \IPS\Db::i()->select( '*', 'forums_posts', 'new_topic = 1 and queued = -1', 'topic_id ASC', array( 0, 50 ) );
		if ( !count( $select ) )
		{
			throw new \OutOfRangeException;
		}
		
		$done = 0;
		foreach( new \IPS\Patterns\ActiveRecordIterator( $select, 'IPS\forums\Topic\Post' ) as $post )
		{
			\IPS\Db::i()->update( 'forums_posts', array( 'queued' => 2 ), array( 'topic_id=?', $post->topic_id ) );
			$item = $post->item();
			$item->hide( NULL );
			$done++;
		}
		
		return $offset + $done;
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
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('queue_fixing_hidden_first_post'), 'complete' => 100 / ( $data['count'] ?: 1 + $offset ) * $offset );
	}	
}