<?php
/**
 * @brief		1.4.0 Beta 1 Upgrade Code
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		19 Dec 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\setup\upg_14000;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 1.4.0 Beta 1 Upgrade Code
 */
class _Upgrade
{
	/**
	 * Package Images
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$offset = isset( \IPS\Request::i()->extra ) ? intval( \IPS\Request::i()->extra ) : 0;
		$pergo = 50;
		$select = \IPS\Db::i()->select( '*', 'nexus_packages', "p_image<>''", 'p_id', array( $offset, $pergo ) );
		if ( count( $select ) )
		{
			foreach ( $select as $row )
			{
				\IPS\Db::i()->insert( 'nexus_package_images', array(
					'image_product'		=> $row['p_id'],
					'image_location'	=> $row['p_image'],
					'image_primary'		=> 1,
				) );
			}
			
			return $offset + $pergo;
		}
		else
		{
			\IPS\Db::i()->dropColumn( 'nexus_packages', 'p_image' );
			return TRUE;
		}
	}
}