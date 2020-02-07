<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Jan 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_100015;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 RC 2 Upgrade Code
 */
class _Upgrade
{
/**
	 * Finish - This is run after all apps have been upgraded
	 *
	 * @return	mixed	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 * @note	We opted not to let users run this immediately during the upgrade because of potential issues (it taking a long time and users stopping it or getting frustrated) but we can revisit later
	 */
	public function finish()
	{
		/* Unprotect Forums app, otherwise it cannot be turned offline post-upgrade */
		\IPS\Db::i()->update( 'core_applications', array( 'app_protected' => 0 ), array( 'app_directory=?', 'forums' ) );

		/* Rebuild content to update counts */
		\IPS\Task::queue( 'core', 'RebuildItems', array( 'class' => 'IPS\core\Statuses\Status' ), 3, array( 'class' ) );
		\IPS\Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\core\Statuses\Status', 'count' => 0 ), 4, array( 'class' ) );
	}
}