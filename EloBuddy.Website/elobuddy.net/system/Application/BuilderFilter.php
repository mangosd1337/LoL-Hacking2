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
class _BuilderFilter extends \RecursiveFilterIterator
{
	public function accept()
	{
		return !( $this->isDir() && in_array( $this->getFilename(), $this->getDirectoriesToIgnore() ) );
	}


	/**
	 * returns the skipped directories
	 *
	 * @return array
	 */
	protected function getDirectoriesToIgnore()
	{
		return array(
			'.git',
			'.svn',
			'dev'
		);
	}
}