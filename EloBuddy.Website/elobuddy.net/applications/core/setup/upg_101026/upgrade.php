<?php
/**
 * @brief		4.1.9 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		05 Feb 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_101026;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.9 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Fix incorrectly converted warn logs (if any)
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$perCycle	= 1000;
		$did		= 0;
		$doneSoFar	= intval( \IPS\Request::i()->extra );

		/* Try to prevent timeouts to the extent possible */
		$cutOff			= \IPS\core\Setup\Upgrade::determineCutoff();

		foreach( \IPS\Db::i()->select( '*', 'core_members_warn_logs', array( "wl_mq LIKE '%TH' OR wl_rpa LIKE '%TH' OR wl_suspend LIKE '%TH'" ), 'wl_id ASC', array( 0, $perCycle ) ) as $log )
		{
			if( $cutOff !== null AND time() >= $cutOff )
			{
				return ( $doneSoFar + $did );
			}

			$did++;

			/* Fix incorrectly stored dateinterval values */
			$update	= array();

			if( \strpos( $log['wl_mq'], 'TH' ) )
			{
				$update['wl_mq'] = preg_replace( "/^P([0-9]+?)TH$/", "PT$1H", $log['wl_mq'] );
			}

			if( \strpos( $log['wl_rpa'], 'TH' ) )
			{
				$update['wl_rpa'] = preg_replace( "/^P([0-9]+?)TH$/", "PT$1H", $log['wl_rpa'] );
			}

			if( \strpos( $log['wl_suspend'], 'TH' ) )
			{
				$update['wl_suspend'] = preg_replace( "/^P([0-9]+?)TH$/", "PT$1H", $log['wl_suspend'] );
			}

			if( count( $update ) )
			{
				\IPS\Db::i()->update( 'core_members_warn_logs', $update, "wl_id=" . $log['wl_id'] );
			}
		}

		if( $did )
		{
			return ( $doneSoFar + $did );
		}
		else
		{
			unset( $_SESSION['_step1Count'] );

			return TRUE;
		}
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		$doneSoFar = isset( \IPS\Request::i()->extra ) ? \IPS\Request::i()->extra : 0;

		if( !isset( $_SESSION['_step1Count'] ) )
		{
			$_SESSION['_step1Count'] = \IPS\Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( "wl_mq LIKE '%TH' OR wl_rpa LIKE '%TH' OR wl_suspend LIKE '%TH'" ) )->first();
		}

		return "Fixing member warnings (Fixed so far: " . ( ( $doneSoFar > $_SESSION['_step1Count'] ) ? $_SESSION['_step1Count'] : $doneSoFar ) . ' out of ' . $_SESSION['_step1Count'] . ')';
	}
	
	/*
	 * Trigger background task
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2()
	{
		\IPS\Task::queue( 'core', 'RecountPollVotes', array(), 4 );

		return TRUE;
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step2CustomTitle()
	{
		return "Recounting poll votes";
	}
	
	/**
	 * Rebuild search index
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish()
	{
		\IPS\Content\Search\Index::i()->rebuild();
		
		return TRUE;
	}
}