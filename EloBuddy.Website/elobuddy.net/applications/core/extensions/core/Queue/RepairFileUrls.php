<?php
/**
 * @brief		Background Task: Repair File URLs
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
 * Background Task: Repair File URLs
 */
class _RepairFileUrls
{
	
	/**
	 * @brief Number of files to fix per cycle
	 */
	public $batch = 500;
	
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
				if ( ! method_exists( $extension, 'fixUrls' ) )
				{
					return NULL;
				}
				
				$return = $extension->fixUrls( $offset );
				
				$offset++;
				
				/* Did we return a new offset? */
				if ( is_numeric( $return ) )
				{
					$offset = $return;
				}
			} 
			catch ( \UnderflowException $e )
			{
				return NULL;
			}
			catch ( \Exception $e )
			{
				\IPS\Log::log( $e, 'repair_files_bgtask' );
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
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('updating_storage_urls', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $data['storageExtension'] ) ) ) ), 'complete' => $data['count'] ? round( ( 100 / $data['count'] * $offset ), 2 ) : 100 );
	}	
}