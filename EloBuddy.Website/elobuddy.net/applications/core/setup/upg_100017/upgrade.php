<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Feb 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\setup\upg_100017;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 RC 4 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Step 1
	 * Fix furl definitions while retaining custom ones
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		/* We want to retain customizations, but ONLY customizations in the "Settings" furl configuration */
		$furlDefinitions	= json_decode( \IPS\Settings::i()->furl_configuration, TRUE );
		$defaultConfig		= \IPS\Http\Url::furlDefinition( TRUE );
		$customDefinitions	= array();

		if( is_array( $furlDefinitions ) AND count( $furlDefinitions ) )
		{
			foreach( $furlDefinitions as $k => $v )
			{
				if( $v['friendly'] != $defaultConfig[ $k ]['friendly'] )
				{
					$customDefinitions[ $k ] = array(
						'friendly'	=> $v['friendly'],
						'real'		=> $v['real'],
						'custom'	=> true
					);
				}
			}
		}

		$customDefinitions	= count( $customDefinitions ) ? json_encode( $customDefinitions ) : NULL;
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $customDefinitions ), array( 'conf_key=?', 'furl_configuration' ) );
		unset( \IPS\Data\Store::i()->settings );

		return TRUE;
	}

	/**
	 * Custom title for this step
	 *
	 * @return string
	 */
	public function step1CustomTitle()
	{
		return "Updating FURL definitions";
	}
}