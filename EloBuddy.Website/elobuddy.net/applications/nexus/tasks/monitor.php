<?php
/**
 * @brief		Server Monitoring Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Server Monitoring Task
 */
class _monitor extends \IPS\Task
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
		if ( \IPS\Settings::i()->monitoring_script )
		{
			/* Init */
			$url = \IPS\Http\Url::external( \IPS\Settings::i()->monitoring_script )->setQueryString( 'notify', base64_encode( \IPS\Settings::i()->monitoring_alert ) );
			if ( \IPS\Settings::i()->monitoring_backup )
			{
				$backupUrl = \IPS\Http\Url::external( \IPS\Settings::i()->monitoring_backup )->setQueryString( 'notify', base64_encode( \IPS\Settings::i()->monitoring_alert ) );
			}
								
			/* Loop Servers */	
			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_hosting_servers', "server_monitor<>'' AND server_monitor_acknowledged=0" ), 'IPS\nexus\Hosting\Server' ) as $server )
			{				
				/* Make the call */
				$response = NULL;
				try
				{
					$response = $url->setQueryString( 'check', $server->monitor )->request()->get();
					
					if ( $response == 'FAIL' or mb_strstr( $response, 'SUCCESS' ) === FALSE and \IPS\Settings::i()->monitoring_backup )
					{
						throw new \IPS\Http\Request\Exception;
					}
				}
				catch ( \IPS\Http\Request\Exception $e )
				{
					if ( \IPS\Settings::i()->monitoring_backup )
					{
						try
						{
							$response = $backupUrl->setQueryString( 'check', $server->monitor )->request()->get();
						}
						catch ( \IPS\Http\Request\Exception $e ) { }
					}
				}
				
				/* If it's online... */
				if ( $response and $response != 'FAIL' and mb_strstr( $response, 'SUCCESS' ) !== FALSE )
				{
                    $exploded = explode( "\n", $response );

                    /* If we previously sent an offline email, we can now send a "back online" one */
					if ( $server->monitor_fails > \IPS\Settings::i()->monitoring_allowed_fails )
					{
						$server->monitoringOnline( $exploded[1] );
					}
					
					/* Update Server */
					$server->monitor_fails = 0;
					$server->monitor_acknowledged = 0;
					$server->monitor_last_sucess = time();
					$server->monitor_version = $exploded[1];
					$server->save();
				}
				
				/* If it's offline... */
				else
				{
					$server->monitor_fails++;
					$server->save();
					
					if ( $server->monitor_fails == ( intval( \IPS\Settings::i()->monitoring_allowed_fails ) + 1 ) )
					{
						$server->monitoringOffline();
					}
					elseif ( $server->monitor_fails == intval( \IPS\Settings::i()->monitoring_allowed_fails ) and !$server->monitor_acknowledged )
					{
						$server->monitoringPanic();
					}
				}
			}
		}
		else
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'monitor' ) );
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