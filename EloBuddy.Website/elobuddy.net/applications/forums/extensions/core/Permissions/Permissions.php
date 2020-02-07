<?php
/**
 * @brief		Permissions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		16 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\Permissions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Permissions
 */
class _Permissions
{
	/**
	 * Get node classes
	 *
	 * @return	array
	 */
	public function getNodeClasses()
	{		
		return array(
			'IPS\forums\Forum' => function( $current, $group )
			{
				$rows = array();
				
				foreach( \IPS\forums\Forum::roots( NULL ) AS $root )
				{
					\IPS\forums\Forum::populatePermissionMatrix( $rows, $root, $group, $current );
				}
				
				return $rows;
			}
		);
	}

}