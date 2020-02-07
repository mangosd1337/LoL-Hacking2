<?php
/**
 * @brief		Sameboxed Template
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
 * Sameboxed Template Class
 */
class _SandboxedTemplate
{
	/**
	 * @brief	Template
	 */
	public $template;
	
	/**
	 * Contructor
	 *
	 * @param	\IPS\Theme\Template	$template
	 * @return	void
	 */
	public function __construct( $template )
	{
		$this->template = $template;
	}
	
	/**
	 * Call
	 *
	 * @return string
	 */
	public function __call( $name, $args )
	{
		if ( !method_exists( $this->template, $name ) )
		{
			return "<span style='background:black;color:white;padding:6px;'>[[Template {$this->template->app}/{$this->template->templateLocation}/{$this->template->templateName}/{$name} does not exist. This theme may be out of date. Run the support tool in the AdminCP to restore the default theme.]]</span>";
		}
		else
		{
			try
			{
				return call_user_func_array( array( $this->template, $name ), $args );
			}
			catch ( \ErrorException $e )
			{
				\IPS\Log::log( $e, 'template_error' );
				
				return "<span style='background:black;color:white;padding:6px;'>[[Template {$this->template->app}/{$this->template->templateLocation}/{$this->template->templateName}/{$name} is throwing an error. This theme may be out of date. Run the support tool in the AdminCP to restore the default theme.]]</span>";
			}
		}
	}
}