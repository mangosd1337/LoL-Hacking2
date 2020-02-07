<?php
/**
 * @brief		Background Task: Send Follow Notifications
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Send Follow Notifications
 */
class _Follow
{
	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int|null				New offset or NULL if complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( &$data, $offset )
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		$item = $classname::load( $data['item'] );
		$sentTo = isset( $data['sentTo'] ) ? $data['sentTo'] : array();
		$newOffset = $item->sendNotificationsBatch( $offset, $sentTo, isset( $data['extra'] ) ? $data['extra'] : NULL );
		$data['sentTo'] = $sentTo;
		return $newOffset;
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
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		$item = $classname::loadAndCheckPerms( $data['item'] );
		$numberofFollowers = intval( $item->notificationRecipients()->count( TRUE ) );
		if ( $numberofFollowers )
		{
			$complete = round( 100 / $numberofFollowers * $offset, 2 );
		}
		else
		{
			$complete = 100;
		}
		
		$title = ( $item instanceof \IPS\Content\Comment ) ? $item->item()->mapped('title') : $item->mapped('title');
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('backgroundQueue_follow', FALSE, array( 'htmlsprintf' => array( "<a href='{$item->url()}' target='_blank'>{$title}</a>" ) ) ), 'complete' => $complete );
	}	
}