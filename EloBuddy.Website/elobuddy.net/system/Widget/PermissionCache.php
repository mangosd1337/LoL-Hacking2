<?php
/**
 * @brief		Widget PermissionCache Class: Used for widgets whose output depends on
 * 				the permissions of the user viewing
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Widget;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Widget PermissionCache Class
 */
class _PermissionCache extends \IPS\Widget
{
	/**
	 * @brief	cacheKey
	 */
	public $cacheKey = "";
	
	/**
	 * Constructor
	 *
	 * @param	String				$uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param	null|string|array	$access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	null|string			$orientation			Orientation (top, bottom, right, left)
	 * @param	boolean				$allowReuse				If true, when the block is used, it will remain in the sidebar so it can be used again.
	 * @param	string				$menuStyle				Menu is a drop down menu, modal is a bigger modal panel.
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );

		/* For permissions based cache we need to store once per language and permission config */
		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( json_encode( $configuration ) . "_" . \IPS\Member::loggedIn()->language()->id . "_" . \IPS\Member::loggedIn()->skin . "_" . json_encode( \IPS\Member::loggedIn()->groups ) . "_" . $orientation );
	}
}