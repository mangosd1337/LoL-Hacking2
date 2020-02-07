<?php
/**
 * @brief		API output for custom fields
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 Mar 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\ProfileFields\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API output for custom fields groups
 */
class _Field
{
	/**
	 * @brief	Name
	 */
	protected $name;
	
	/**
	 * @brief	Value
	 */
	protected $value;
	
	/**
	 * Constructor
	 *
	 * @param	string	$name	Group name
	 * @param	array	$value	Values
	 */
	public function __construct( $name, $value )
	{
		$this->name = $name;
		$this->value = $value;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse	string		name	Field name
	 * @apiresponse	string		value	Value
	 */
	public function apiOutput()
	{
		return array( 'name' => $this->name, 'value' => $this->value );
	}
}