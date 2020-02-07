<?php
/**
 * @brief		Codemirror class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		8 Jul 2013
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
 * Codemirror class for Form Builder
 */
class _Codemirror extends TextArea
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'minLength'		=> 1,			// Minimum number of characters. NULL is no minimum. Default is NULL.
	 		'maxLength'		=> 255,			// Maximum number of characters. NULL is no maximum. Default is NULL.
	 		'disabled'		=> FALSE,		// Disables input. Default is FALSE.
	 		'placeholder'	=> 'e.g. ...',	// A placeholder (NB: Will only work on compatible browsers)
	 		'tags'			=> array(),		// An array of extra insertable tags in key => value pair with key being what is inserted and value serving as a description
	 		'mode'			=> 'php'		// Formatting mode. Default is htmlmixed.
	        'height'        => 300      // Height of code mirror editor
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'minLength'		=> NULL,
		'maxLength'		=> NULL,
		'disabled'		=> FALSE,
		'placeholder'	=> NULL,
		'tags'			=> array(),
		'mode'			=> 'htmlmixed',
		'nullLang'		=> NULL,
		'height'        => 300
	);

	/**
	 * Constructor
	 * Sets that the field is required if there is a minimum length and vice-versa
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		/* Call parent constructor */
		call_user_func_array( 'parent::__construct', func_get_args() );

		/* We don't support this feature */
		$this->options['nullLang']	= NULL;

		/* Append our necessary JS/CSS */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'codemirror/diff_match_patch.js', 'core', 'interface' ) );	
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'codemirror/codemirror.js', 'core', 'interface' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		if ( $this->options['height'] )
		{
			$this->options['height'] = is_numeric( $this->options['height'] ) ? $this->options['height'] . 'px' : $this->options['height'];
		}

		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->codemirror( $this->name, $this->value, $this->required, $this->options['maxLength'], $this->options['disabled'], '', $this->options['placeholder'], $this->options['tags'], $this->options['mode'], $this->htmlId ? "{$this->htmlId}-input" : NULL, $this->options['height'] );
	}
}