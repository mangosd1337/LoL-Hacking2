<?php
/**
 * @brief		Widget StaticCache Class: Used for when output does not regularly change
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
 * Widget StaticCache Class
 */
class _StaticCache extends \IPS\Widget
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
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );

		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( json_encode( $configuration ) . "_" . \IPS\Member::loggedIn()->language()->id . "_" . \IPS\Member::loggedIn()->skin . "_" . $orientation );
	}
}