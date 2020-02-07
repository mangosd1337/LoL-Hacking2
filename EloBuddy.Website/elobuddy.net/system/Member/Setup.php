<?php
/**
 * @brief		Dummy Member Model used by installer
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		1 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dummy Member Model used by installer
 */
class _Setup
{
	/**
	 * @brief	Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * Get instance
	 *
	 * @return	\IPS\Member\Setup
	 */
	public static function i()
	{
		if ( static::$instance === NULL )
		{
			static::$instance = new self;
		}
		return static::$instance;
	}
	
	/**
	 * @brief	Language data
	 */
	protected $language = NULL;
	
	/**
	 * Is user an admin
	 *
	 * @return	boolean
	 */
	public function isAdmin()
	{
		return FALSE;
	}
	
	/**
	 * Is the user logged in?
	 *
	 * @return boolean
	 */
	public function loggedIn()
	{
		return $this;
	}
	
	
	
	/**
	 * Get language
	 *
	 * @return	\IPS\Lang
	 */
	public function language()
	{
		if ( $this->language === NULL )
		{
			$this->language = \IPS\Lang::constructFromData( array() );
			require( \IPS\ROOT_PATH . '/' . \IPS\CP_DIRECTORY . '/install/lang.php' );
			$this->language->words = $lang;
		}
		return $this->language;
	}
}