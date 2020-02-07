<?php
/**
 * @brief		4.0.0 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Feb 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_100014;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.0.0 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Convert Commerce ads to normal ads
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		if ( !\IPS\Db::i()->checkForTable('nexus_ads') )
		{
			return TRUE;
		}
		
		$offset = isset( \IPS\Request::i()->extra ) ? \IPS\Request::i()->extra : 0;
		
		$select = \IPS\Db::i()->select( '*', 'nexus_ads', NULL, 'ad_id', array( $offset, 100 ) );
		if ( count( $select ) )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 1 ), array( 'conf_key=?', 'ads_exist' ) );
			
			foreach ( $select as $ad )
			{
				$locations = explode( ',', trim( $ad['ad_locations'], ',' ) );
				foreach ( $locations as $k => $v )
				{
					$locations[ $k ] = str_replace( '_code', '', $v );
				}

				$adShow = '*';

				if( $ad['ad_exempt'] )
				{
					$adShow	= json_encode( array_diff( array_keys( \IPS\Member\Group::groups() ), explode( ',', $ad['ad_exempt'] ) ) );
				}
				
				\IPS\Db::i()->insert( 'core_advertisements', array(
					'ad_location'			=> implode( ',', $locations ),
					'ad_html'				=> $ad['ad_html'] ?: NULL,
					'ad_images'				=> $ad['ad_image'] ? json_encode( array( 'large' => $ad['ad_image'] ) ) : NULL,
					'ad_link'				=> $ad['ad_link'],
					'ad_impressions'		=> $ad['ad_impressions'],
					'ad_clicks'				=> $ad['ad_clicks'],
					'ad_exempt'				=> $adShow,
					'ad_active'				=> $ad['ad_active'],
					'ad_html_https'			=> NULL,
					'ad_start'				=> $ad['ad_start'],
					'ad_end'				=> $ad['ad_end'],
					'ad_maximum_value'		=> $ad['ad_expire'] ?: -1,
					'ad_maximum_unit'		=> $ad['ad_expire_unit'],
					'ad_additional_settings'=> json_encode( array() ),
					'ad_html_https_set'		=> 0,
					'ad_member'				=> $ad['ad_member'],
				) );
			}
			
			return $offset + 100;
		}
		else
		{
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
		return "Upgrading advertisements...";
	}
}