<?php
/**
 * @brief		Magic Template Class for IN_DEV mode
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Email\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Magic Template Class for IN_DEV mode
 */
class _Template extends \IPS\Theme\Template
{
	/**
	 * @brief	Source Folder
	 */
	public $sourceFolder = NULL;
	
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
		parent::__construct( $app, $templateLocation, $templateName );
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
		
		if ( \IPS\IN_DEV )
		{
			$this->sourceFolder = \IPS\ROOT_PATH . "/applications/{$app}/dev/email/{$templateLocation}/{$templateName}/";
		}
	}
	
	/**
	 * Magic Method: Call Template Bit
	 *
	 * @param	string	$bit	Template Bit Name
	 * @param	array	$params	Parameters
	 * @return	string
	 */
	public function __call( $bit, $params )
	{
		if ( \IPS\IN_DEV )
		{
			/* What are we calling this? */
			$functionName = "theme_{$this->app}_{$this->templateLocation}_{$this->templateName}_{$bit}";

			/* If it doesn't exist, build it */
			if( !function_exists( 'IPS\\Theme\\'.$functionName ) )
			{
				/* Find the file */
				$file = $this->sourceFolder . $bit . ( ( $this->templateLocation == 'html' ) ? '.phtml' : '.txt' );
				
				/* Get the content */
				if ( $file === NULL or !file_exists( $file ) )
				{
					throw new \BadMethodCallException( 'NO_TEMPLATE_FILE - ' . $file );
				}
				
				$output = file_get_contents( $file );
				
				/* Parse the header tag */
				if ( !preg_match( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $output, $matches ) )
				{
					throw new \BadMethodCallException( 'NO_HEADER - ' . $file );
				}
				
				/* Strip it */
				$output = preg_replace( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', '', $output );
				
				/* Make it into a lovely function */
				\IPS\Theme::makeProcessFunction( $output, $functionName, ( isset( $matches[1] ) ? $matches[1] : '' ), TRUE );
			}
			
			/* Run it */
			ob_start();
			$return = call_user_func_array( 'IPS\\Theme\\'.$functionName, $params );
			if( $error = ob_get_clean() )
			{
				echo "<strong>{$functionName}</strong><br>{$error}<br><br><pre>{$output}";
				exit;
			}
			
			/* Return */
			return $return;
		}
	}
}