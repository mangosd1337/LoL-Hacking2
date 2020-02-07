<?php
/**
 * @brief		Key/Value input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
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
 * Key/Value input class for Form Builder
 */
class _KeyValue extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @see		\IPS\Helpers\Form\Date::$defaultOptions
	 * @code
	 	$defaultOptions = array(
	 		'start'			=> array( ... ),
	 		'end'			=> array( ... ),
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'key'		=> array(
			'minLength'			=> NULL,
			'maxLength'			=> NULL,
			'size'				=> 20,
			'disabled'			=> FALSE,
			'autocomplete'		=> NULL,
			'placeholder'		=> NULL,
			'regex'				=> NULL,
			'nullLang'			=> NULL,
			'accountUsername'	=> FALSE,
			'trim'				=> TRUE,
		),
		'value'		=> array(
			'minLength'			=> NULL,
			'maxLength'			=> NULL,
			'size'				=> NULL,
			'disabled'			=> FALSE,
			'autocomplete'		=> NULL,
			'placeholder'		=> NULL,
			'regex'				=> NULL,
			'nullLang'			=> NULL,
			'accountUsername'	=> FALSE,
			'trim'				=> TRUE,
		),
	);

	/**
	 * @brief	Key Object
	 */
	public $keyField = NULL;
	
	/**
	 * @brief	Value Object
	 */
	public $valueField = NULL;
	
	/**
	 * Constructor
	 * Creates the two date objects
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
	{
		$options = array_merge( $this->defaultOptions, $options );
		
		$this->keyField = new \IPS\Helpers\Form\Text( "{$name}[key]", isset( $defaultValue['key'] ) ? $defaultValue['key'] : NULL, FALSE, isset( $options['key'] ) ? $options['key'] : array() );
		$this->valueField = new \IPS\Helpers\Form\Text( "{$name}[value]", isset( $defaultValue['value'] ) ? $defaultValue['value'] : NULL, FALSE, isset( $options['value'] ) ? $options['value'] : array() );
		
		call_user_func_array( 'parent::__construct', func_get_args() );
	}
	
	/**
	 * Format Value
	 *
	 * @return	array
	 */
	public function formatValue()
	{
		return array(
			'key'	=> $this->keyField->formatValue(),
			'value'	=> $this->valueField->formatValue()
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->keyValue( $this->keyField->html(), $this->valueField->html() );
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
		$this->keyField->validate();
		$this->valueField->validate();
		
		if( $this->customValidationCode !== NULL )
		{
			call_user_func( $this->customValidationCode, $this->value );
		}
	}
}