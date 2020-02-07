<?php
/**
 * @brief		Background processes 'Run Now'
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Jan 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background processes 'Run Now'
 */
class _background extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('background_process_run_title');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->backgroundProcessesRunNow();
	}
	
	/**
	 * Process
	 *
	 * @return	void
	 */
	protected function process()
	{
		$self = $this;
		$multiRedirect = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal('app=core&module=system&controller=background&do=process'),
			function( $data ) use ( $self )
			{
				/* Make sure the task is locked */
				$task = \IPS\Task::load('queue', 'key');
				$task->running = TRUE;
				$task->next_run = time() + 900;
				$task->save();
				
				if ( ! is_array( $data ) )
				{
					$count = $self->getCount();
					 
					return array( array( 'count' => $count, 'done' => 0 ), 'Starting...' );
				}
				else
				{
					try
					{			
						$queueData = \IPS\Db::i()->select( '*', 'core_queue', NULL, 'priority ASC, RAND()', 1 )->first();
						$newOffset = 0;
						try
						{
							$extensions = \IPS\Application::load( $queueData['app'] )->extensions( 'core', 'Queue', FALSE );
							if ( !isset( $extensions[ $queueData['key'] ] ) )
							{
								throw new \OutOfRangeException;
							}
							
							$class = new $extensions[ $queueData['key'] ];
							$json  = json_decode( $queueData['data'], TRUE );
							$newOffset = $class->run( $json, $queueData['offset'] );
				
							if ( is_null( $newOffset ) )
							{
								\IPS\Log::debug( "Finished " . json_encode( $queueData ), 'runQueue_log' );
								\IPS\Db::i()->delete( 'core_queue', array( 'id=?', $queueData['id'] ) );

								/* Do we have a post-completion callback? */
								if( method_exists( $class, 'postComplete' ) )
								{
									$class->postComplete( $queueData );
								}
								
								/* Update count */
								$data['count'] = $self->getCount();
							}
							else
							{
								\IPS\Db::i()->update( 'core_queue', array( 'offset' => $newOffset ), array( 'id=?', $queueData['id'] ) );
								
								$newData = json_encode( $json );
								
								/* Did it change?? */
								if ( $newData !== $json )
								{
									\IPS\Db::i()->update( 'core_queue', array( 'data' => $newData ), array( 'id=?', $queueData['id'] ) );
								}
							}
						}
						catch ( \OutOfRangeException $e )
						{ 
							\IPS\Log::debug( "Finished " . json_encode( $queueData ), 'runQueue_log' );
							
							\IPS\Db::i()->delete( 'core_queue', array( 'id=?', $queueData['id'] ) );

							/* Do we have a post-completion callback? */
							if( method_exists( $class, 'postComplete' ) )
							{
								$class->postComplete( $queueData );
							}

							/* Update count */
							$data['count'] = $self->getCount();
						}
					}
					catch ( \UnderflowException $e )
					{
						/* All done */
						return NULL;
					}
					
					$data['done'] = $data['done'] + ( $newOffset - $queueData['offset'] );
					$lang = array( $queueData['key'] );
					
					if ( isset( $json['class'] ) )
					{
						$lang[] = $json['class'];
					}
					else if ( isset( $json['extension'] ) )
					{
						$lang[] = $json['extension'];
					}
					else if ( isset( $json['storageExtension'] ) )
					{
						$lang[] = $json['storageExtension'];
					}
					
					if ( isset( $json['count'] ) )
					{
						$lang[] = " " . intval( $newOffset ) . ' / ' . $json['count'];
					}
					
					return array( $data, \IPS\Member::loggedIn()->language()->addToStack('background_processes_processing', FALSE, array( 'sprintf' => array( implode( ' - ', $lang ) ) ) ), ( $data['count'] ) ? round( ( 100 / $data['count'] * $data['done'] ), 2 ) : 100 );
				}
			},
			function()
			{
				/* Make sure the task is unlocked */
				$task = \IPS\Task::load('queue', 'key');
				$task->running = FALSE;
				$task->next_run = time() + 60;
				$task->save();
				
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=overview&controller=dashboard'), 'completed' );
			}
		);
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('background_process_run_title');
		\IPS\Output::i()->output = $multiRedirect;
	}
	
	/**
	 * Get the count of items to process
	 *
	 * @return int
	 */
	public function getCount()
	{
		$count = 0;
		foreach( \IPS\Db::i()->select( '*', 'core_queue' ) as $row )
		{
			if ( ! empty( $row['data'] ) )
			{
				$data = json_decode( $row['data'], TRUE );
				
				if ( isset( $data['count'] ) )
				{
					$count += intval( $data['count'] );
				}
				else
				{
					$count++;
				}
			}
		}
		
		return $count;
	}
}