<?php
/**
 * @brief		Background Task: Move Files from one storage method to another
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 May 2014
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
 * Background Task: Move Files from one storage method to another
 */
class _MoveFiles
{
	
	/**
	 * @brief Number of files to move per cycle
	 */
	public $batch = 20;
	
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
		$exploded = explode( '_', $data['storageExtension'] );		
		$classname = "IPS\\{$exploded[2]}\\extensions\\core\\FileStorage\\{$exploded[3]}";
       
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[2] ) )
		{
			throw new \OutOfRangeException;
		}
		
		$extension = new $classname;

		for ( $i = 0; $i < $this->batch; $i++ )
		{
			try
			{
				$return = $extension->move( $offset, (int) $data['newConfiguration'], (int) $data['oldConfiguration'] );
				
				$offset++;
				
				/* Did we return a new offset? */
				if ( is_numeric( $return ) )
				{
					$offset = $return;
				}
			} 
			catch ( \UnderflowException $e )
			{
				/* Move is done so remove second config ID to remove lock */
				$row = \IPS\Db::i()->select( '*', 'core_sys_conf_settings', array( 'conf_key=?', 'upload_settings' ) )->first();
				$settings = json_decode( $row['conf_value'], TRUE );

				if ( isset( $settings[ $data['storageExtension'] ] ) )
				{
					$settings[ $data['storageExtension'] ] = $data['newConfiguration'];

					\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $settings ) ), array( 'conf_key=?', 'upload_settings' ) );
					unset( \IPS\Data\Store::i()->settings );
				}

				return NULL;
			}
			catch ( \Exception $e )
			{
				\IPS\Log::log( $e, 'move_files_bgtask' );
				$offset++;
				continue;
			}
		}
		
		return $offset;
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
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('moving_files', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $data['storageExtension'] ) ) ) ), 'complete' => $data['count'] ? round( ( 100 / $data['count'] * $offset ), 2 ) : 100 );
	}	
}