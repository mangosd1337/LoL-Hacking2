<?php
/**
 * @brief		view_updates Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Nov 2015
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
 * view_updates Task
 */
class _viewupdates extends \IPS\Task
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
		$this->runUntilTimeout(function(){
			try
			{
				$result = \IPS\Db::i()->select( 'classname, id, COUNT(*) AS count', 'core_view_updates', NULL, NULL, 20, array( 'classname', 'id' ) );

				/* If there's no more, break the loop and stop */
				if( !count( $result ) )
				{
					return FALSE;
				}

				foreach( $result as $row )
				{
					$class = $row['classname'];
					if ( class_exists( $class ) and in_array( 'IPS\Content\Views', class_implements( $class ) ) AND isset( $class::$databaseColumnMap['views'] ) )
					{
						try
						{
							\IPS\Db::i()->update(
								$class::$databaseTable,
								"`{$class::$databasePrefix}{$class::$databaseColumnMap['views']}`=`{$class::$databasePrefix}{$class::$databaseColumnMap['views']}`+{$row['count']}",
								array( "{$class::$databasePrefix}{$class::$databaseColumnId}=?", $row['id'] )
							);
						}
						catch( \IPS\Db\Exception $e )
						{
							/* Table to update no longer exists */
							if( $e->getCode() == 1146 )
							{
								\IPS\Db::i()->delete( 'core_view_updates', array( 'classname=?', $row['classname'] ) );
							}
						}
					}

					\IPS\Db::i()->delete( 'core_view_updates', array( 'classname=? AND id=?', $row['classname'], $row['id'] ) );
				}

				/* If we're here we just ran some, so we can return true to see if there are any more to run */
				return TRUE;
			}
			catch ( \UnderflowException $e )
			{
				return FALSE;
			}
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