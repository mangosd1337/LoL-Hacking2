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
 * Background Task: Find orphaned files
 */
class _FindOrphanedFiles
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
		if ( ! $data['configurationId'] )
		{
			throw new \OutOfRangeException;
		}

		/* Check the configuration location and loop through x files looking for any that aren't mapped in any storage locations */
		try
		{
			$results = \IPS\File::orphanedFiles( $data['configurationId'], ( ! empty( $data['fileIndex'] ) ? $data['fileIndex'] : $offset ) );
		
			if ( $results['_done'] === TRUE )
			{
				\IPS\Task::queue( 'core', 'DeleteOrphanedFiles', array( 'configurationId' => $data['configurationId'], 'count' => $results['fileIndex'] ), 5, array( 'configurationId' ) );
				return NULL;
			}
			
			/* Amazon returns a key, not an integer */
			if ( is_numeric( $results['fileIndex'] ) )
			{
				return $results['fileIndex'];
			}
			else
			{
				$data['fileIndex'] = $results['fileIndex'];
				
				return 0;
			}
		}
		catch( \RuntimeException $ex )
		{
			throw new \OutOfRangeException;
		}
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
		$extensionName = '';
		try
		{
			$extensionName = \IPS\Db::i()->select( 'method', 'core_file_storage', array( 'id=?', $data['configurationId'] ) )->first();
		}
		catch( \Exception $e ) { }

		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('finding_orphaned_files', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $extensionName ) ) ) ), 'complete' => NULL );
	}	
}