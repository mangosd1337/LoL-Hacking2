<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		15 Mar 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\chat\setup\upg_100021;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 RC 5 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Step 1
	 * Rebuild reputation received
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* Set access permission */
		if( \IPS\Settings::i()->ipschat_group_access )
		{
			\IPS\Db::i()->update( 'core_groups', array( 'chat_access' => 1 ), 'g_id IN(' . \IPS\Settings::i()->ipschat_group_access . ')' );
		}
		else
		{
			\IPS\Db::i()->update( 'core_groups', array( 'chat_access' => 1 ) );
		}

		if( \IPS\Settings::i()->ipschat_mods )
		{
			\IPS\Db::i()->update( 'core_groups', array( 'chat_moderate' => 1 ), 'g_id IN(' . \IPS\Settings::i()->ipschat_mods . ')' );
		}

		if( \IPS\Settings::i()->ipschat_private )
		{
			\IPS\Db::i()->update( 'core_groups', array( 'chat_private' => 1 ), 'g_id IN(' . \IPS\Settings::i()->ipschat_private . ')' );
		}

		/* Add rules text */
		\IPS\Lang::saveCustom( 'core', "ipschat_rules", \IPS\Text\Parser::utf8mb4SafeDecode( \IPS\Settings::i()->ipschat_rules ) );

		/* Add online/offline start value */
		\IPS\Db::i()->replace( 'core_sys_conf_settings', array(
			'conf_key'		=> 'ipschat_online',
			'conf_default'	=> '',
			'conf_value'	=> implode( ',', array( 0 => \IPS\Settings::i()->ipschat_online_start, 1 => \IPS\Settings::i()->ipschat_online_end ) ),
			'conf_app'		=> 'chat',
		) );

		/* Remove old settings */
		\IPS\Db::i()->delete( 'core_sys_conf_settings', "conf_key IN ('ipschat_group_access', 'ipschat_mods', 'ipschat_private', 'ipschat_online', 
			'ipschat_account_key', 'ipchat_htc_zero', 'ipchat_max_messages', 'ipschat_offline_msg', 'ipschat_offline_groups', 'ipschat_hide_chatting', 
			'ipschat_whos_chatting', 'ipchat_htc_view', 'ipschat_format_names', 'ipschat_online_start', 'ipschat_online_end', 'ipschat_rules')" );
		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Converting and cleaning up settings";
	}
}