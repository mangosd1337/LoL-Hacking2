<?php
/**
 * @brief		Number range input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Jul 2014
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
 * Number range input class for Form Builder
 */
class _NumberRange extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @see		\IPS\Helpers\Form\Number::$defaultOptions
	 * @code
	 	$defaultOptions = array(
	 		'start'			=> array( ... ),
	 		'end'			=> array( ... ),
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'start'		=> array(
			'min'				=> NULL,
			'max'				=> NULL,
			'disabled'			=> FALSE,
			'unlimited'			=> NULL,
			'unlimitedLang'		=> 'any',
			'unlimitedToggles'	=> array(),
			'unlimitedToggleOn'	=> TRUE,
		),
		'end'		=> array(
			'min'				=> NULL,
			'max'				=> NULL,
			'disabled'			=> FALSE,
			'unlimited'			=> NULL,
			'unlimitedLang'		=> 'any',
			'unlimitedToggles'	=> array(),
			'unlimitedToggleOn'	=> TRUE,
		),
	);

	/**
	 * @brief	Start Number Object
	 */
	public $start = NULL;
	
	/**
	 * @brief	End Number Object
	 */
	public $end = NULL;
	
	/**
	 * Constructor
	 * Creates the two number objects
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
	{		
		$this->start = new \IPS\Helpers\Form\Number( "{$name}[start]", isset( $defaultValue['start'] ) ? $defaultValue['start'] : NULL, FALSE, isset( $options['start'] ) ? $options['start'] : array() );
		$this->end = new \IPS\Helpers\Form\Number( "{$name}[end]", isset( $defaultValue['end'] ) ? $defaultValue['end'] : NULL, FALSE, isset( $options['end'] ) ? $options['end'] : array() );
		
		call_user_func_array( 'parent::__construct', func_get_args() );
	}
	
	/**
	 * Format Value
	 *
	 * @return	array
	 */
	public function formatValue()
	{
		/* Return */
		return array(
			'start'	=> $this->start->formatValue(),
			'end'	=> $this->end->formatValue(),
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->numberRange( $this->start->html(), $this->end->html() );
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\LengthException
	 * @return	TRUE
	 */
	public function validate()
	{
		$this->start->validate();
		$this->end->validate();
		
		if ( $this->required and $this->value['start'] === NULL and $this->value['end'] === NULL )
		{
			throw new \InvalidArgumentException('form_required');
		}
		
		if( $this->customValidationCode !== NULL )
		{
			call_user_func( $this->customValidationCode, $this->value );
		}
	}
}