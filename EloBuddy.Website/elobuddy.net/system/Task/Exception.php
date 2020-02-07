<?php
/**
 * @brief		Task Exception
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Task;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Task Exception
 */
class _Exception extends \RuntimeException
{
	/**
	 * Constructor
	 *
	 * @param	\IPS\Task	$task		The task with the issue
	 * @param	string		$message	Error Message
	 * @return	void
	 */
	public function __construct( \IPS\Task $task, $message )
	{
		$task->running = FALSE;
		$task->next_run = \IPS\DateTime::create()->add( new \DateInterval( $task->frequency ) )->getTimestamp();
		$task->save();
		
		return parent::__construct( $message );
	}
}