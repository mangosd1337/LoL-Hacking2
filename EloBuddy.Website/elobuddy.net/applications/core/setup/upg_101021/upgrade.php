<?php
/**
 * @brief		4.1.6 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Social Suite
 * @since		30 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_101021;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.6 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Rebuild imported status updates to address XSS issue
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* Init */
		$perCycle	= 250;
		$did		= 0;
		$limit		= intval( \IPS\Request::i()->extra );
		$cutOff		= \IPS\core\Setup\Upgrade::determineCutoff();
		
		/* Loop */
		foreach( \IPS\Db::i()->select( array( 'status_id', 'status_member_id', 'status_content' ), 'core_member_status_updates', array( 'status_imported=?', 1 ), 'status_id', array( $limit, $perCycle ) ) as $row )
		{
			/* Timeout? */
			if( $cutOff !== null AND time() >= $cutOff )
			{
				return ( $limit + $did );
			}
			
			/* Step up */
			$did++;
			
			/* Rebuild the content */
			$content = \IPS\Text\Parser::parseStatic( $row['status_content'], FALSE, NULL, \IPS\Member::load( $row['status_member_id'] ), 'core_Members' );
			
			/* Save */
			\IPS\Db::i()->update( 'core_member_status_updates', array( 'status_content' => $content ), array( 'status_id=?', $row['status_id'] ) );
		}
		
		/* Did we do anything? */
		if( $did )
		{
			return ( $limit + $did );
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
		$limit = isset( \IPS\Request::i()->extra ) ? \IPS\Request::i()->extra : 0;

		if( !isset( $_SESSION['_step1Count'] ) )
		{
			$_SESSION['_step1Count'] = \IPS\Db::i()->select( 'COUNT(*)', 'core_member_status_updates', array( "status_imported=?", 1 ) )->first();
		}

		return "Fixing Imported Status Updates (Upgraded so far: " . ( ( $limit > $_SESSION['_step1Count'] ) ? $_SESSION['_step1Count'] : $limit ) . ' out of ' . $_SESSION['_step1Count'] . ')';
	}


}