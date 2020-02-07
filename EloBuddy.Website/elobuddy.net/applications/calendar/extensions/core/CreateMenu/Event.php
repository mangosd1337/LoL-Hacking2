<?php
/**
 * @brief		Create Menu Extension
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		23 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\extensions\core\CreateMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension
 */
class _Event
{
	/**
	 * Get Items
	 *
	 * @return	array
	 */
	public function getItems()
	{		
		if ( \IPS\calendar\Calendar::canOnAny( 'add' ) )
		{
			return array( 'event' => array( 'link' => \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&_new=1", 'front', 'calendar_submit' ) ) );
		}

		return array();
	}
}