<?php
/**
 * @brief		expectedOutputMonitoring Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		12 Sep 2014
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
 * expectedOutputMonitoring Task
 */
class _expectedOutputMonitoring extends \IPS\Task
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
		$rules = \IPS\nexus\Hosting\EOM::roots();
		
		if ( !count( $rules ) )
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'expectedOutputMonitoring' ) );
			return NULL;
		}
		
		foreach ( $rules as $rule )
		{
			try
			{
				$output = \IPS\Http\Url::external( $rule->url )->request()->get();
				switch ( $rule->type )
				{
					case 'c':
						$match = ( mb_strpos( $output, $rule->value ) !== FALSE );
						break;
						
					case 'e':
						$match = ( $output == $rule->value );
						
					case 'n':
						$match = ( mb_strpos( $output, $rule->value ) === FALSE );
						break;
				}
								
				if ( !$match )
				{
					$email = \IPS\Email::buildFromTemplate( 'nexus', 'monitoring_eom', array( $rule ), \IPS\Email::TYPE_LIST );
					$email->send( json_decode( $rule->notify, TRUE ) );
				}
			}
			catch ( \Exception $e )
			{
				throw new \IPS\Task\Exception( $this, $e->getMessage() );
			}
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