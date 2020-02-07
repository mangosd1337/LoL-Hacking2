<?php
/**
 * @brief		Template Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Class
 */
abstract class _Template
{
	/**
	 * @brief	Application key
	 */
	public $app = NULL;
	
	/**
	 * @brief	Template Location
	 */
	public $templateLocation = NULL;
	
	/**
	 * @brief	Template Name
	 */
	public $templateName = NULL;
		
	/**
	 * Contructor
	 *
	 * @param	string	$app				Application Key
	 * @param	string	$templateLocation	Template location (admin/public/etc.)
	 * @param	string	$templateName		Template Name
	 * @return	void
	 */
	public function __construct( $app, $templateLocation, $templateName )
	{
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
	}
	
	/**
	 * Return the app/location/group params
	 *
	 * @return array
	 */
	public function getParams()
	{
		return array( 'app' => $this->app, 'location' => $this->templateLocation, 'group' => $this->templateName );
	}
	
	/**
	 * Hook Data
	 *
	 * @return array
	 */
	public static function hookData()
	{
		return array();
	}
}