<?php
/**
 * @brief		Date range input class for Form Builder
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
 * Date range input class for Form Builder
 */
class _DateRange extends FormAbstract
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
		'start'		=> array(
			'min'				=> NULL,
			'max'				=> NULL,
			'disabled'			=> FALSE,
			'time'				=> FALSE,
			'unlimited'			=> NULL,
			'unlimitedLang'		=> 'indefinite',
			'unlimitedToggles'	=> array(),
			'unlimitedToggleOn'	=> TRUE,
		),
		'end'		=> array(
			'min'				=> NULL,
			'max'				=> NULL,
			'disabled'			=> FALSE,
			'time'				=> FALSE,
			'unlimited'			=> NULL,
			'unlimitedLang'		=> 'indefinite',
			'unlimitedToggles'	=> array(),
			'unlimitedToggleOn'	=> TRUE,
		),
	);

	/**
	 * @brief	Start Date Object
	 */
	public $start = NULL;
	
	/**
	 * @brief	End Date Object
	 */
	public $end = NULL;
	
	/**
	 * Constructor
	 * Creates the two date objects
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array() )
	{
		$this->start = new \IPS\Helpers\Form\Date( "{$name}[start]", isset( $defaultValue['start'] ) ? $defaultValue['start'] : NULL, FALSE, isset( $options['start'] ) ? $options['start'] : array() );
		$this->end = new \IPS\Helpers\Form\Date( "{$name}[end]", isset( $defaultValue['end'] ) ? $defaultValue['end'] : NULL, FALSE, isset( $options['end'] ) ? $options['end'] : array() );
		
		call_user_func_array( 'parent::__construct', func_get_args() );
	}
	
	/**
	 * Format Value
	 *
	 * @return	array
	 */
	public function formatValue()
	{
		/* The start time may be offset a few hours depending on the users timezone, let's fix that now */
		$start = $this->start->formatValue();
		if ( $start instanceof \IPS\DateTime and $this->options['start']['time'] === FALSE )
		{
			$start->setTime( 00, 00, 00 );
		}

		/* The end date needs to be 23:59:59 rather than 00:00:00 as we need to go right up to the end of the day */
		$end = $this->end->formatValue();
		if ( $end instanceof \IPS\DateTime and $this->options['end']['time'] === FALSE )
		{
			$end->setTime( 23, 59, 59 );
		}

		/* Return */
		return array(
			'start'	=> $start,
			'end'	=> $end
		);
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->dateRange( $this->start->html(), $this->end->html() );
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