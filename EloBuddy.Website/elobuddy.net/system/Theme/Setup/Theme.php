<?php
/**
 * @brief		Setup Skin Set
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme\Setup;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IN_DEV Skin set
 */
class _Theme extends \IPS\Theme
{
	/**
	 * Constructor
	 * Stops the DB being queried which doesn't exist yet
	 *
	 * @return	void
	 */
	public function __construct() { }
	
	/**
	 * Get template
	 *
	 * @param	string	$template			Template name
	 * @param	string	$app				Application key (NULL for current application)
	 * @param	string	$templateLocation	Template location (NULL for current template location)
	 * @return	\IPS\Theme\Template
	 */
	public function getTemplate( $template, $app=NULL, $templateLocation=NULL )
	{
		$obj = new \IPS\Theme\Dev\Template( $template, 'core', 'setup' );
		$obj->sourceFolder = \IPS\ROOT_PATH . "/" . \IPS\CP_DIRECTORY . "/" . \IPS\Dispatcher::i()->setupLocation . "/html/{$template}/";
		return $obj;
	}
}