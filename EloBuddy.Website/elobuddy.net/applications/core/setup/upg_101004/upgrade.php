<?php
/**
 * @brief		4.1.0 Beta 5 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Oct 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_101004;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.1.0 Beta 5 Upgrade Code
 */
class _Upgrade
{
	/**
	 * If there are follows for member records that no longer exists, this causes an error when trying to send the non-existent user a notification later
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$toRunQueries	= array(
			array(
				'table'	=> 'core_follow',
				'query'	=> "DELETE FROM " . \IPS\Db::i()->prefix . "core_follow WHERE follow_member_id NOT IN(SELECT member_id FROM " . \IPS\Db::i()->prefix . "core_members)",
			)
		);

		$toRun = \IPS\core\Setup\Upgrade::runManualQueries( $toRunQueries );
		
		if ( count( $toRun ) )
		{
			$mr = \IPS\core\Setup\Upgrade::adjustMultipleRedirect( array( 'extra' => array( '_upgradeStep' => 2 ) ) );

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
	public function step1CustomTitle()
	{
		return "Removing orphaned follows";
	}
}