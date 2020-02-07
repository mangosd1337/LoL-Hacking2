<?php
/**
 * @brief		4.0.13 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Aug 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_100044;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.13 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Remove purchases from not existing members
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$offset = isset( \IPS\Request::i()->extra ) ? \IPS\Request::i()->extra : 0;

		$select = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', NULL, 'ps_id', array( $offset, 500) ), 'IPS\nexus\Purchase' );
		if ( count ( $select ) )
		{
			foreach ( $select as $purchase )
			{
				$member = \IPS\Member::load( $purchase->ps_member );
				
				if ( !$member->member_id )
				{
					$purchase->delete();
				}
			}
			return $offset + 500;

		}
		else
		{
			unset( $_SESSION['_step1Count'] );
			return TRUE;
		}
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}