<?php
/**
 * @brief		Application builder custom filter iterator
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		8 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Application;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Custom filter iterator for application building
 */
class _BuilderIterator extends \RecursiveIteratorIterator
{
	/**
	 * @brief	The application
	 */
	protected $application;


	/**
	 * Constructor
	 *
	 * @param \IPS\Application $application
	 */
	public function __construct( \IPS\Application $application )
	{
		$this->application = $application;
		parent::__construct( new BuilderFilter( new \RecursiveDirectoryIterator( \IPS\ROOT_PATH . "/applications/" . $application->directory, \RecursiveDirectoryIterator::SKIP_DOTS ) ) );
	}
	
	/**
	 * Current key
	 *
	 * @return	void
	 */
	public function key()
	{
		return mb_substr( parent::current(), mb_strlen( \IPS\ROOT_PATH . "/applications/" . $this->application->directory ) + 1 );
	}
	
	/**
	 * Current value
	 *
	 * @return	void
	 */
	public function current()
	{
		$file = (string) parent::current();
		
		if ( mb_substr( $file, mb_strlen( \IPS\ROOT_PATH . "/applications/" . $this->application->directory ) + 1, 6 ) === 'hooks/' )
		{
			$temporary = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
			\file_put_contents( $temporary, \IPS\Plugin::addExceptionHandlingToHookFile( $file ) );
			
			return $temporary;
		}
		else
		{
			return $file;
		}
	}
}