<?php
/**
 * @brief		Content Extension Generator
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		26 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Extension Generator
 */
abstract class _ExtensionGenerator
{
	/**
	 * @brief	If TRUE, will prevent comment classes being included
	 */
	protected static $contentItemsOnly = FALSE;
	
	/**
	 * Generate Extensions
	 *
	 * @return	array
	 */
	public static function generate()
	{
		$return = array();
		
		foreach ( \IPS\Content::routedClasses( FALSE, FALSE, static::$contentItemsOnly ) as $_class )
		{
			$obj = new static;
			$obj->class = $_class;
			\IPS\Member::loggedIn()->language()->words[ 'ipAddresses__core_Content_' . str_replace( '\\', '_', mb_substr( $_class, 4 ) ) ] = \IPS\Member::loggedIn()->language()->addToStack( $_class::$title . '_pl', FALSE );
			$return[ 'Content_' . str_replace( '\\', '_', mb_substr( $_class, 4 ) ) ] = $obj;
		}
		
		return $return;
	}
	
	/**
	 * @brief	Content Class
	 */
	public $class;
}