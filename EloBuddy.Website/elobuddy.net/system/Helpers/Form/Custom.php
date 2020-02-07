<?php
/**
 * @brief		Custom input class for Form Builder
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
 * Custom input class for Form Builder
 */
class _Custom extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'getHtml'		=> function(){...}	// Function to get output
	 		'formatValue'	=> function(){...}	// Function to format value
	 		'validate'		=> function(){...}	// Function to validate
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'getHtml'		=> NULL,
		'formatValue'	=> NULL,
		'validate'		=> NULL,
	);

	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return call_user_func( $this->options['getHtml'], $this );
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function rowHtml( $form=NULL )
	{
		if ( isset( $this->options['rowHtml'] ) )
		{
			return call_user_func( $this->options['rowHtml'], $this, $form );
		}
		return parent::rowHtml( $form );
	}
	
	/** 
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue()
	{
		if ( $this->options['formatValue'] !== NULL )
		{
			return call_user_func( $this->options['formatValue'], $this );
		}
		else
		{
			return parent::formatValue();
		}
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
		
		if ( $this->options['validate'] )
		{
			call_user_func( $this->options['validate'], $this );
		}	
	}
}