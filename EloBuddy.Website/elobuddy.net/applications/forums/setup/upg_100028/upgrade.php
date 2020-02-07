<?php
/**
 * @brief		4.0.5 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		5 May 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\setup\upg_100028;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.5 Upgrade Code
 */
class _Upgrade
{
	
	/**
	 * Make sure all theme settings are applied to every theme.
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
    {
	    \IPS\core\Setup\Upgrade::repairFileUrls('forums');
		\IPS\Task::queue( 'core', 'RebuildContentImages', array( 'class' => 'IPS\forums\Topic' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildContentImages', array( 'class' => 'IPS\forums\Topic\Post' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildContentImages', array( 'class' => 'IPS\forums\Topic\ArchivedPost' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildNonContentImages', array( 'extension' => 'forums_Forums' ), 3, array( 'extension' ) );

        return TRUE;
    }

}