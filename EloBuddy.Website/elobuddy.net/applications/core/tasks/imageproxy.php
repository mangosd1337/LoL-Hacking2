<?php
/**
 * @brief		imageproxy Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Nov 2015
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
 * imageproxy Task
 */
class _imageproxy extends \IPS\Task
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
		if ( !\IPS\Settings::i()->remote_image_proxy )
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'imageproxy' ) );
			return NULL;
		}
		
		$this->runUntilTimeout(function()
		{
			$select = \IPS\Db::i()->select( 'location', 'core_image_proxy', array( 'cache_time<?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->image_proxy_cache_period . 'D' ) )->getTimestamp() ), 'cache_time ASC', 10 );
			if ( !$select->count() )
			{
				return FALSE;
			}
			
			foreach ( $select as $location )
			{
				try
				{
					\IPS\File::get( 'core_Attachment', $location )->delete();
				}
				catch ( \Exception $e ) { }
				
				\IPS\Db::i()->delete( 'core_image_proxy', array( 'location=?', $location ) );
			}
			
			return TRUE;
		});
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