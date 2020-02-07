<?php
/**
 * @brief		Announcements Extension: Calendar
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar	
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\extensions\core\Announcements;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Announcements Extension
 */
class _Calendars
{
	public static $idField = "id";
	
	public static $controllers = array();
	
	/**
	 * Get Setting Field
	 *
	 * @param	\IPS\core\Announcements\Announcement	$announcement
	 * @return	Form element
	 */
	public function getSettingField( $announcement )
	{
		return new \IPS\Helpers\Form\Node( 'announce_calendars', ( $announcement AND $announcement->ids ) ? explode( ",", $announcement->ids ) : 0, FALSE, array( 'class' => 'IPS\calendar\Calendar', 'zeroVal' => 'any', 'multiple' => TRUE ), NULL, NULL, NULL, 'announce_calendars' );
	}
}