<?php
/**
 * @brief		digest Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		08 May 2014
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
 * digest Task
 */
class _digest extends \IPS\Task
{
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
		/* Doing daily or weekly this run? */
		$frequencies = array( 'daily', 'weekly' );
		$frequency = $frequencies[ array_rand( $frequencies ) ];
				
		/* Grab some members to send digests to. */ 
		$members = \IPS\Db::i()->select( 'follow_member_id', 'core_follow', array( 'follow_notify_do=1 AND follow_visible=1 AND follow_notify_freq = ? AND follow_notify_sent < ?', $frequency, ( $frequency == 'daily' ) ? time() - 86400 : time() - 604800 ), 'follow_member_id ASC', array( 0, 50 ), 'follow_member_id' );
		if( !count( $members ) )
		{
			/* Nothing to send */
			return NULL; 
		}
		
		/* Fetch the member's follows so we can build their digest */
		$follows = \IPS\Db::i()->select( '*', 'core_follow', array( 'follow_notify_do=1 AND follow_notify_freq=? AND follow_visible=1 AND follow_notify_sent < ? AND ' . \IPS\Db::i()->in( 'follow_member_id', iterator_to_array( $members ) ), $frequency, time() - 86400 ), 'follow_member_id ASC, follow_app ASC, follow_area ASC' );
		
		$groupedFollows = array();
		foreach( $follows as $follow )
		{
			$groupedFollows[ $follow['follow_member_id'] ][ $follow['follow_app'] ][ $follow['follow_area'] ][] = $follow; 
		}
		
		foreach( $groupedFollows as $id => $data )
		{
            $member = \IPS\Member::load( $id );
            if( !$member->email )
            {
                continue;
            }

			/* Build it */
			$digest = new \IPS\core\Digest\Digest;
			$digest->member = $member;
			$digest->frequency = $frequency;
			$digest->build( $data );
			
			/* Send it */
			$digest->send();
		}

		return NULL;
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