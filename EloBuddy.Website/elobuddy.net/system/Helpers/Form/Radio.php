<?php
/**
 * @brief		Radio Switch class for Form Builder
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
 * Radio Switch class for Form Builder
 */
class _Radio extends Select
{
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		call_user_func_array( 'parent::__construct', func_get_args() );
		if ( !isset( $this->options['descriptions'] ) )
		{
			$this->options['descriptions'] = array();
		}
		if ( !isset( $this->options['warnings'] ) )
		{
			$this->options['warnings'] = array();
		}

		/* If you haven't selected any radio options, then there is no input and the required flag is never checked (validate() is never called) */
		$_key = "radio_" . $this->name . "__empty";

		if( isset( \IPS\Request::i()->$_key ) )
		{
			try
			{
				$this->value = $this->getValue();
				$this->unformatted = $this->value;
				$this->value = $this->formatValue();
				$this->validate();
			}
			catch ( \LogicException $e )
			{
				$this->error = $e->getMessage();
			}
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$descriptions	= $this->options['descriptions'];
		$warnings		= $this->options['warnings'];
		if ( $this->options['parse'] === 'lang' )
		{
			foreach ( $this->options['options'] as $k => $v )
			{
				$key = "{$v}_desc";
				if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $key ) )
				{
					$descriptions[ $k ] = \IPS\Member::loggedIn()->language()->addToStack( $key );
				}

				$key = "{$v}_warning";
				if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $key ) )
				{
					$warnings[ $k ] = \IPS\Member::loggedIn()->language()->addToStack( $key );
				}
			}
		}
		
		/* Translate label back to key? */
		if ( $this->options['returnLabels'] )
		{
			$value = array_search( $this->value, $this->options['options'] );
			if ( $value === FALSE )
			{
				$value = $this->defaultValue;
			}
		}
		else
		{
			$value = $this->value;
		}
		
		if ( $this->options['parse'] === 'image' )
		{
			return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->radioImages( $this->name, $value, $this->required, $this->options['options'], $this->options['disabled'], $this->options['toggles'], $descriptions, $warnings, $this->options['userSuppliedInput'], $this->options['unlimited'], $this->options['unlimitedLang'], $this->htmlId );
		}
		else
		{
			return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->radio( $this->name, $value, $this->required, $this->parseOptions(), $this->options['disabled'], $this->options['toggles'], $descriptions, $warnings, $this->options['userSuppliedInput'], $this->options['unlimited'], $this->options['unlimitedLang'], $this->htmlId );
		}
	}

	/**
	 * Validate
	 *
	 * @throws	\OutOfRangeException
	 * @return	TRUE
	 */
	public function validate()
	{
		if( $this->value === null and $this->required )
		{
			throw new \InvalidArgumentException('form_required');
		}
		/* Field is not required and value was not supplied */
		else if( $this->value === null )
		{
			return true;
		}

		return parent::validate();
	}

	/**
	 * Get value
	 *
	 * @return	array
	 */
	public function getValue()
	{
		$value = parent::getValue();
		
		/* Disabled radio fields do not submit a value to the server */
		if( $this->options['disabled'] === TRUE or ( is_array( $this->options['disabled'] ) and in_array( $value, $this->options['disabled'] ) ) )
		{
			return $this->defaultValue;
		}
		
		return $value;
	}
}