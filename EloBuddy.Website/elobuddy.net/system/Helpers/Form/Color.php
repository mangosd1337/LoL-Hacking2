<?php
/**
 * @brief		Color input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Color input class for Form Builder
 */
class _Color extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'disabled'		=> FALSE,		// Disables input. Default is FALSE.
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'disabled'		=> FALSE,
	);

	/**
	 * Constructor
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		/* Call parent constructor */
		call_user_func_array( 'parent::__construct', func_get_args() );
		
		/* Add the fallback for browsers that don't support the HTML5 color input type */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jscolor/jscolor.js', 'core', 'interface' ) );
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->color( $this->name, $this->value, $this->required, $this->options['disabled'] );
	}
	
	/**
	 * Format Value
	 *
	 * @return	string
	 */
	public function formatValue()
	{
		$manualName = $this->name . '_manual';

		/* If a manual value has been supplied, use that instead */
		if ( isset( \IPS\Request::i()->$manualName ) )
		{
			$value = \IPS\Request::i()->$manualName;
		}
		else
		{
			$value = $this->value;
		}

		if ( mb_substr( $value, 0, 1 ) !== '#' )
		{
			$value = '#' . $value;
		}
		
		return mb_strtolower( $value );
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
				
		if ( !preg_match( '/^(?:#)?(([a-f0-9]{3})|([a-f0-9]{6}))$/i', $this->value ) )
		{
			throw new \InvalidArgumentException('form_color_bad');
		}
	}
}