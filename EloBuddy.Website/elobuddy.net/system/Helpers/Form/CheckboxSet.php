<?php
/**
 * @brief		Checkbox Set class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Jul 2013
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
 * Checkbox Set class for Form Builder
 */
class _CheckboxSet extends Select
{
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$args = func_get_args();		
		$args[3]['multiple'] = TRUE;

		call_user_func_array( 'parent::__construct', $args );
		
		if ( !isset( $this->options['descriptions'] ) )
		{
			$this->options['descriptions'] = array();
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		/* Get descriptions */
		$descriptions = $this->options['descriptions'];
		if ( $this->options['parse'] === 'lang' )
		{
			foreach ( $this->options['options'] as $k => $v )
			{
				$key = "{$v}_desc";
				if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $key ) )
				{
					$descriptions[ $k ] = \IPS\Member::loggedIn()->language()->addToStack( $key );
				}
			}
		}
		
		/* Translate labels back to keys? */
		if ( $this->options['returnLabels'] )
		{
			$value = array();
			if ( !is_array( $this->value ) )
			{
				$this->value = explode( ',', $this->value );
			}
			foreach ( $this->value as $v )
			{
				$value[] = array_search( $v, $this->options['options'] );
			}
		}
		else
		{
			$value = $this->value;
		}

		/* If the value is NULL or an empty string, i.e. from a custom field, then we should not convert it into an array because the
			value will evaluate to 0 with an == check and the first option in the checkbox set will always be selected erroneously */
		if ( ! is_array( $value ) AND $value !== NULL AND $value !== '' )
		{
			$value = array( $value );
		}
		
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->checkboxset( $this->name, $value, $this->required, $this->parseOptions(), $this->options['multiple'], $this->options['class'], $this->options['disabled'], $this->options['toggles'], NULL, $this->options['unlimited'], $this->options['unlimitedLang'], $this->options['unlimitedToggles'], $this->options['unlimitedToggleOn'], $descriptions );
	}
	
	/**
	 * Get value
	 *
	 * @return	array
	 */
	public function getValue()
	{
		/* We need the array keys.  For custom fields we will have 0 => 1, 1 => 1 if checkboxes 0 and 1 are checked, while for Content we will have
			customKey => 1, customOtherKey => 1 - we always need the array keys */
		$value = is_array( parent::getValue() ) ? array_keys( parent::getValue() ) : array();
		if ( $this->options['returnLabels'] )
		{
			if ( is_array( $value ) )
			{
				$return = array();
				foreach ( $value as $k => $v )
				{
					$return[ $k ] = $this->options['options'][ $v ];
				}
				return $return;
			}
			else
			{
				return $this->options['options'][ $value ];
			}
		}

		return $value;
	}
}