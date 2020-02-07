<?php
/**
 * @brief		Upgrade steps
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_31003;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrade steps
 */
class _Upgrade
{
	/**
	 * Step 1
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		if( !\IPS\Db::i()->checkForColumn( 'skin_replacements', 'replacement_master_key' ) )
		{
			\IPS\Db::i()->addColumn( 'skin_replacements', array(
				'name'			=> 'replacement_master_key',
				'type'			=> 'VARCHAR',
				'length'		=> 100,
				'allow_null'	=> false,
				'default'		=> ''
			) );

			\IPS\Db::i()->update( 'skin_replacements', array( 'replacement_master_key' => 'root' ), array( 'replacement_set_id=?', 0 ) );
		}

		/* Finish */
		return TRUE;
	}
}