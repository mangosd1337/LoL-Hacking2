<?php
/**
 * @brief		Number input class for Form Builder
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
 * Text input class for Form Builder
 */
class _Number extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'min'				=> 0,			// Minimum number. NULL is no minimum. Default is 0.
	 		'max'				=> 100,			// Maximum number. NULL is no maximum. Default is NULL.
	 		'unlimited'			=> -1,			// If any value other than NULL is provided, an "Unlimited" checkbox will be displayed. If checked, the value specified will be sent.
	 		'unlimitedLang'		=> 'unlimited',	// Language string to use for unlimited checkbox label
	 		'unlimitedToggles'	=> array(...),	// Names of other input fields that should show/hide when the "Unlimited" checkbox is toggled.
	 		'unlimitedToggleOn'	=> TRUE,		// Whether the toggles should show on unlimited checked (TRUE) or unchecked(FALSE). Default is TRUE
	 		'valueToggles'		=> array(...),	// Names of other input fields that should show/hide if the value is/isn't 0.
	 		'decimals'			=> 2,			// Number of decimal places (or TRUE to allow any number)
	 		'step'				=> NULL,		// Increase step for supported browsers. NULL will cause the value to be based off "decimals". Default is NULL.
	 		'range'				=> TRUE,		// Use a range selection for supported browsers? Default is FALSE.
	 		'disabled'			=> FALSE,		// Disables input. Default is FALSE.
	 		'endSuffix'			=> '...',		// A suffix to go after the unlimited checkbox
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'min'				=> 0,
		'max'				=> NULL,
		'unlimited'			=> NULL,
		'unlimitedLang'		=> 'unlimited',
		'unlimitedToggles'	=> array(),
		'unlimitedToggleOn'	=> TRUE,
		'valueToggles'		=> array(),
		'decimals'			=> 0,
		'step'				=> NULL,
		'range'				=> FALSE,
		'disabled'			=> FALSE,
		'endSuffix'			=> NULL,
	);
	
	/**
	 * Constructor
	 * Formats the value appropriately
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @param	string		$name					Name
	 * @param	mixed		$defaultValue			Default value
	 * @param	bool		$required				Required?
	 * @param	array		$options				Type-specific options
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
	{
		/* Specify there's a value if the unlimited element contains a value */
		$unlimitedName = "{$name}_unlimited";		
		if( isset( $options['unlimited'] ) and $options['unlimited'] !== NULL and isset( \IPS\Request::i()->$unlimitedName ) )
		{
			\IPS\Request::i()->$name = $options['unlimited'];
		}
		
		/* Work out the step */
		$args = func_get_args();
		if ( !isset( $options['base'] ) and isset( $options['decimals'] ) )
		{
			if ( $options['decimals'] === TRUE )
			{
				$args[3]['step'] = 'any';
			}
			else
			{
				$args[3]['step'] = 1 / ( pow( 10, $options['decimals'] ) );
			}
		}
		
		/* Call parent constructor */
		call_user_func_array( 'parent::__construct', $args );
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		/* Display */
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->number( $this->name, $this->value, $this->required, $this->options['unlimited'], $this->options['range'], $this->options['min'], $this->options['max'], $this->options['step'], $this->options['decimals'], $this->options['unlimitedLang'], $this->options['disabled'], $this->suffix, $this->options['unlimitedToggles'], $this->options['unlimitedToggleOn'], $this->options['valueToggles'] );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue()
	{
		$name = $this->name;
		
		if ( $pos = mb_strpos( $name, '[' ) )
		{
			$_name = mb_substr( preg_replace( '/\[(.+?)\]/', '[$1_unlimited]', $name, 1 ), 0, $pos );
			$val = \IPS\Request::i()->$_name;		
			preg_match_all( '/\[(.+?)\]/', $name, $matches );
			foreach ( $matches[1] as $_name )
			{
				if ( isset( $val[ $_name ] ) )
				{
					$val = $val[ $_name ];
				}
				else
				{
					$val = FALSE;
					break;
				}
			}
						
			return $val;
		}
		else
		{
			$unlimitedName = "{$name}_unlimited";
			$unlimitedChecked = isset( \IPS\Request::i()->$unlimitedName );
		}
				
		/* Unlimited? */
		if ( $this->options['unlimited'] !== NULL and isset( \IPS\Request::i()->$unlimitedName ) )
		{
			return $this->options['unlimited'];
		}
		
		/* Get value */
		return \IPS\Request::i()->$name;
	}
	
	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue()
	{		
		/* Get value */
		$value = $this->value;
		
		/* If this is the "unlimited" value, we don't need to format it */
		if ( $this->options['unlimited'] !== NULL and $value === $this->options['unlimited'] )
		{
			return $value;
		}
	
		/* Convert decimal point and thousand separators to a way PHP understand */
		$value = str_replace( trim( \IPS\Member::loggedIn()->language()->locale['thousands_sep'] ), '', $value );
		$value = str_replace( trim( \IPS\Member::loggedIn()->language()->locale['decimal_point'] ), '.', $value );
		
		/* If it's not numeric, throw an exception */
		if ( ( !is_numeric( $value ) and $value !== '' and $this->required === FALSE ) or ( !is_numeric( $value ) and $this->required === TRUE ) )
		{
			throw new \InvalidArgumentException( 'form_number_bad' );
		}
				
		/* Convert to int/float */
		if ( $this->options['decimals'] )
		{
			$value = floatval( $value );
			if ( is_int( $this->options['decimals'] ) )
			{
				$value = round( $value, $this->options['decimals'] );
			}
		}
		else
		{
			$value = intval( $value );
		}

		/* Return */
		return $value;
	}
	
	/**
	 * Validate
	 *
	 * @throws	\LengthException
	 * @return	TRUE
	 */
	public function validate()
	{	
		parent::validate();
						
		if ( $this->options['unlimited'] === NULL or $this->value !== $this->options['unlimited'] )
		{		
			if ( $this->options['min'] !== NULL and $this->value < $this->options['min'] )
			{
				throw new \LengthException( \IPS\Member::loggedIn()->language()->addToStack('form_number_min', FALSE, array( 'sprintf' => array( $this->options['min'] ) ) ) );
			}
			if ( $this->options['max'] !== NULL and $this->value > $this->options['max'] )
			{
				throw new \LengthException( \IPS\Member::loggedIn()->language()->addToStack('form_number_max', FALSE, array( 'sprintf' => array( $this->options['max'] ) ) ) );
			}
		}
	}
}