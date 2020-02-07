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

namespace IPS\core\setup\upg_31007;

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
		if( \IPS\Db::i()->checkForTable('core_like_cache') )
		{
			\IPS\Db::i()->dropTable( 'core_like_cache' );
		}

		if( \IPS\Db::i()->checkForTable('core_like') )
		{
			\IPS\Db::i()->dropTable( 'core_like' );
		}

		\IPS\Db::i()->createTable( array(
			'name'		=> 'core_like_cache',
			'columns'	=> array(
				array(
					'name'			=> 'like_cache_id',
					'type'			=> 'varchar',
					'length'		=> 32,
					'allow_null'	=> true,
					'default'		=> null
				),
				array(
					'name'			=> 'like_cache_app',
					'type'			=> 'varchar',
					'length'		=> 150,
					'allow_null'	=> false,
					'default'		=> ''
				),
				array(
					'name'			=> 'like_cache_area',
					'type'			=> 'varchar',
					'length'		=> 200,
					'allow_null'	=> false,
					'default'		=> ''
				),
				array(
					'name'			=> 'like_cache_rel_id',
					'type'			=> 'bigint',
					'length'		=> 20,
					'unsigned'		=> true,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'like_cache_data',
					'type'			=> 'text',
					'length'		=> null,
					'allow_null'	=> true,
					'default'		=> null
				),
			),
			'indexes'	=> array(
				array(
					'type'		=> 'primary',
					'columns'	=> array( 'like_cache_id' )
				)
			)
		)	);

		\IPS\Db::i()->createTable( array(
			'name'		=> 'core_like',
			'columns'	=> array(
				array(
					'name'			=> 'like_id',
					'type'			=> 'varchar',
					'length'		=> 32,
					'allow_null'	=> true,
					'default'		=> null
				),
				array(
					'name'			=> 'like_lookup_id',
					'type'			=> 'varchar',
					'length'		=> 32,
					'allow_null'	=> true,
					'default'		=> null
				),
				array(
					'name'			=> 'like_app',
					'type'			=> 'varchar',
					'length'		=> 150,
					'allow_null'	=> false,
					'default'		=> ''
				),
				array(
					'name'			=> 'like_area',
					'type'			=> 'varchar',
					'length'		=> 200,
					'allow_null'	=> false,
					'default'		=> ''
				),
				array(
					'name'			=> 'like_rel_id',
					'type'			=> 'bigint',
					'length'		=> 20,
					'unsigned'		=> true,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'like_member_id',
					'type'			=> 'int',
					'length'		=> 10,
					'unsigned'		=> true,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'like_is_anon',
					'type'			=> 'int',
					'length'		=> 1,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'like_added',
					'type'			=> 'int',
					'length'		=> 10,
					'unsigned'		=> true,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'like_notify_do',
					'type'			=> 'int',
					'length'		=> 1,
					'allow_null'	=> false,
					'default'		=> 0
				),
				array(
					'name'			=> 'like_notify_meta',
					'type'			=> 'text',
					'length'		=> null,
					'allow_null'	=> true,
					'default'		=> null
				),
				array(
					'name'			=> 'like_notify_freq',
					'type'			=> 'int',
					'length'		=> 10,
					'unsigned'		=> true,
					'allow_null'	=> false,
					'default'		=> 0
				),
			),
			'indexes'	=> array(
				array(
					'type'		=> 'primary',
					'columns'	=> array( 'like_id' )
				),
				array(
					'type'		=> 'key',
					'name'		=> 'find_rel_favs',
					'columns'	=> array( 'like_lookup_id', 'like_is_anon', 'like_added' )
				),
				array(
					'type'		=> 'key',
					'name'		=> 'like_member_id',
					'columns'	=> array( 'like_member_id', 'like_added' )
				),
			)
		)	);

		/* Finish */
		return TRUE;
	}
}