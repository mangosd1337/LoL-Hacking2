<?php
/**
 * @brief		Username & Password input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Apr 2014
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
 * Username & Password input class for Form Builder
 */
class _UserPass extends \IPS\Helpers\Form\FormAbstract
{	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$value = is_array( $this->value ) ? $this->value : json_decode( \IPS\Text\Encrypt::fromTag( $this->value )->decrypt(), TRUE );
		return \IPS\Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->usernamePassword( $this->name, $value );
	}
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return	string
	 */
	public static function stringValue( $value )
	{
		return \IPS\Text\Encrypt::fromPlaintext( json_encode( $value ) )->tag();
	}
}