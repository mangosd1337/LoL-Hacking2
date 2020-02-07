<?php
/**
 * @brief		Magic Template Class for IN_DEV mode
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Theme\Dev;

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
		
		if ( $this->app === 'core' and $this->templateLocation === 'global' and $this->templateName === 'plugins' )
		{
			$this->sourceFolder = \IPS\ROOT_PATH . '/plugins';
		}
		else
		{
			$this->sourceFolder = \IPS\ROOT_PATH . "/applications/{$app}/dev/html/{$templateLocation}/{$templateName}/";
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
		/* What are we calling this? */
		$functionName = "theme_{$this->app}_{$this->templateLocation}_{$this->templateName}_{$bit}";

		/* If it doesn't exist, build it */
		if( !function_exists( 'IPS\\Theme\\'.$functionName ) )
		{
			/* Find the file */
			$file = NULL;
			if ( $this->sourceFolder === \IPS\ROOT_PATH . '/plugins' )
			{
				foreach ( new \GlobIterator( $this->sourceFolder . '/*/dev/html/' . $bit . '.phtml' ) as $file )
				{
					break;
				}
			}
			else
			{
				$file = $this->sourceFolder . $bit . '.phtml';
			}
			
			/* Get the content */
			if ( $file === NULL or !file_exists( $file ) )
			{
				throw new \BadMethodCallException( 'NO_TEMPLATE_FILE - ' . $file );
			}
			
			$output = file_get_contents( $file );
			
			/* Parse the header tag */
			if ( !preg_match( '/^<ips:template parameters="(.+?)?" \/>(\r\n?|(\r\n?|\n))/', $output, $matches ) )
			{
				throw new \BadMethodCallException( 'NO_HEADER - ' . $file );
			}
			
			/* Strip it */
			$output = preg_replace( '/^<ips:template parameters="(.+?)?" \/>(\r\n?|\n)/', '', $output );
			
			if ( \IPS\IN_DEV and \IPS\DEBUG_TEMPLATES )
			{
				$output = "<!-- " . $functionName . " -->" . $output;
			}
			
			if ( \IPS\IN_DEV AND get_called_class() !== 'IPS\Theme\System\Template' )
			{
				/* Template names that will allow inline style="" attributes */
				$allowedInlineStyle = array(
					'theme_core_admin_forms_widthheight', 'theme_core_global_forms_matrixRows', 'theme_core_admin_tables_table',
					'theme_core_admin_dashboard_dashboard', 'theme_core_front_messaging_template', 'theme_core_front_global_error',
					'theme_core_front_system_notifications', 'theme_core_front_system_coppaConsent', 'theme_calendar_front_view_view',
					'theme_core_global_global_poll', 'theme_forums_admin_settings_archiveRules', 'theme_core_front_system_test_menus',
					'theme_core_front_global_thumbImage', 'theme_downloads_front_browse_index',	'theme_core_front_system_test_submit', 
					'theme_core_front_system_test_galleryHome', 'theme_core_front_system_test_galleryAlbum', 'theme_core_front_system_test_galleryView', 'theme_downloads_front_submit_topic'
				);
	
				$allowedStyleBlocks = array(
					'globalTemplate', 'blankTemplate', 'loginTemplate', 'redirect', 'includeCSS',
					'dashboard', 'profile', 'profileHeader', 'diffExportWrapper', 'coppaConsent', 'attendees',
					'printInvoice', 'giftvoucherPrint'
				);

				$allowedScriptBlocks = array(
					'globalTemplate', 'blankTemplate', 'loginTemplate', 'includeJS', 'dashboard',
					'onlineUsers', 'registrations', 'viglink', 'linkedin', 'reddit', 'poll', 
					'giftvoucherPrint', 'packingLabel', 'packingSheet', 'streamWrapper'
				);
	
				/* Check we're not being naughty */
				if( preg_match( "/<.+?style=['\"].+?>/i", $output ) and !in_array( $functionName, $allowedInlineStyle ) && $this->app != 'documentation' )
				{
					//trigger_error( "There is inline CSS in {$functionName}. Please move all styling into CSS files.", E_USER_ERROR );
				}
				if( !in_array( $bit, $allowedStyleBlocks ) and preg_match( "/<style.*?>/i", $output ) && $this->app != 'documentation' )
				{
				//	trigger_error( "There is a style block in {$functionName}. Please move all styling into CSS files.", E_USER_ERROR );
				}
				if( preg_match( '/<[^>]+?\son(blur|change|click|contextmenu|copy|cut|dblclick|error|focus|focusin|focusout|hashchange|keydown|keypress|keyup|load|mousedown|mouseenter|mouseleave|mousemove|mouseout|mouseover|mouseup|mousewheel|paste|reset|resize|scroll|select|submit|textinput|unload|wheel)=[\'\"].+?>/i', $output ) )
				{
					trigger_error( "There is a inline JavaScript {$functionName}. Please move all JavaScript into JS files.", E_USER_ERROR );
				}
				if( !in_array( $bit, $allowedScriptBlocks ) and $this->templateName !== 'embed' and preg_match( "/<script((?!src).)*>/i", $output ) && $this->app != 'documentation' && $this->app != 'chat')
				{
					trigger_error( "There is a script block in {$functionName}. Please move all JavaScript into JS files.", E_USER_ERROR );
				}
				
				/* Hooks */
				$hookData = static::hookData();
				if ( isset( $hookData[ $bit ] ) )
				{
					$output = \IPS\Theme::themeHooks( $output, $hookData[ $bit ] );
				}
			}
			
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