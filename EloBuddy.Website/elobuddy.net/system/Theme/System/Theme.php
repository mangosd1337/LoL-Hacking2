<?php
/**
 * @brief		Basic template support
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme\System;
	
/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Basic themes
 */
class _Theme extends \IPS\Theme\Dev\Theme
{
	/**
	 * @brief	Template Classes
	 */
	 protected $templates;
	 
	 /**
	 * Get currently logged in member's theme
	 *
	 * @return	\IPS\Theme
	 */
	public static function i()
	{
		if ( static::$memberTheme === null )
		{
			static::themes();
			static::$memberTheme = new self;
					
			if ( \IPS\Dispatcher::i()->controllerLocation === 'front' )
			{
				/* Add in the default theme properties (_data array, etc) */
				foreach( static::$multitons[1] as $k => $v )
				{
					static::$memberTheme->$k = $v;
				}
			}
		}
		
		return static::$memberTheme;
	}
	
	/**
	 * Get a template
	 *
	 * @param	string	$group				Template Group
	 * @param	string	$app				Application key (NULL for current application)
	 * @param	string	$location		    Template Location (NULL for current template location)
	 * @return	\IPS\Theme\Template
	 */
	public function getTemplate( $group, $app=NULL, $location=NULL )
	{
		/* Do we have an application? */
		if ( $app === NULL )
		{
			$app = \IPS\Dispatcher::i()->application->directory;
		}
		
		return new \IPS\Theme\System\Template( $app, $location, $group );
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( $app, $location=null, $path=null )
	{
		return rtrim( \IPS\ROOT_PATH . "/applications/{$app}/data/html/{$location}/{$path}", '/' ) . '/';
	}
	
	/**
	 * Returns the namespace for the template class
	 * @return string
	 */
	protected static function _getTemplateNamespace()
	{
		return 'IPS\\Theme\\Basic\\';
	}

}