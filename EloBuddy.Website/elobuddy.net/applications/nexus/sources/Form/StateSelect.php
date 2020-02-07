<?php
/**
 * @brief		Country/State input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		12 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Country/State input class for Form Builder
 */
class _StateSelect extends \IPS\Helpers\Form\FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
			'unlimitedLang'	=> 'all_locations',	// Language string to use for "All locations" option. If NULL, will not be available.
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'unlimitedLang' => NULL
	);
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->stateSelect( $this->name, $this->value, $this->options['unlimitedLang'] );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue()
	{		
		if ( $this->options['unlimitedLang'] )
		{
			$unlimitedName = "{$this->name}_unlimited";
			if ( isset( \IPS\Request::i()->$unlimitedName ) )
			{
				return '*';
			}
		}
		
		return parent::getValue();
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function formatValue()
	{
		if ( is_array( $this->value ) )
		{
			$value = array();
			foreach ( $this->value as $k => $v )
			{
				if ( in_array( (string) $k, \IPS\GeoLocation::$countries ) )
				{
					$value[ $k ] = $v;
				}
				else
				{
					if ( preg_match( '/^([A-Z]{2})-(.*)$/', $v, $matches ) )
					{
						if ( !isset( $value[ $matches[1] ] ) )
						{
							$value[ $matches[1] ] = array();
						}
						$value[ $matches[1] ][] = $matches[2];
					}
					else
					{
						$value[ $v ] = '*';
					}
				}
			}
			return $value;
		}
		else
		{
			return $this->value;
		}
	}
}