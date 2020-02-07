<?php
/**
 * @brief		Runs tasks (web URL)
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		17 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

/* Init IPS Community Suite */
require_once str_replace( 'applications/core/interface/task/web.php', 'init.php', str_replace( '\\', '/', __FILE__ ) );

/* Set HTTP status */
$http = ( isset( $_SERVER['SERVER_PROTOCOL'] ) and \strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false ) ? '1.0' : '1.1';

/* Execute */
try
{
	/* Ensure applications set up correctly before task is executed. Pages, for example, needs to set up spl autoloaders first */
	\IPS\Application::applications();

	if( \IPS\Settings::i()->task_use_cron != 'web' )
	{
		throw new \OutOfRangeException( "Invalid Task Method" );
	}

	if( !\IPS\Login::compareHashes( (string) \IPS\Settings::i()->task_cron_key, (string) \IPS\Request::i()->key ) )
	{
		throw new \OutOfRangeException( "Invalid Key" );
	}

	$task = \IPS\Task::queued();

	if ( $task )
	{
		$task->runAndLog();
	}

	@header( "HTTP/{$http} 200 OK" );
	print "Task Ran";
}
catch ( \Exception $e )
{
	\IPS\Log::log( $e, 'uncaught_exception' );
	
	@header( "HTTP/{$http} 500 Internal Server Error" );
	echo "Exception:\n";
	print $e->getMessage();
}

/* Exit */
exit;