<?php
/**
 * @brief		4.1.12 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		14 Apr 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_101030;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.12 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Fix donation goal tallies
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$toRun = \IPS\core\Setup\Upgrade::runManualQueries( array( array(
			'table' => 'nexus_donate_logs',
			'query' => "UPDATE " . \IPS\Db::i()->prefix . "nexus_donate_goals SET d_current=(SELECT SUM(dl_amount) FROM " . \IPS\Db::i()->prefix . "nexus_donate_logs WHERE " . \IPS\Db::i()->prefix . "nexus_donate_logs.dl_goal=" . \IPS\Db::i()->prefix . "nexus_donate_goals.d_id)"
		) ) );
		
		if ( count( $toRun ) )
		{
			$mr = \IPS\core\Setup\Upgrade::adjustMultipleRedirect( array( 1 => 'nexus', 'extra' => array( '_upgradeStep' => 2 ) ) );

			/* Queries to run manually */
			return array( 'html' => \IPS\Theme::i()->getTemplate( 'forms' )->queries( $toRun, \IPS\Http\Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => $mr ) ) ) );
		}
		
		return TRUE;
	}
	
	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step8CustomTitle()
	{
		return "Adjusting Nexus donation goals";
	}
}