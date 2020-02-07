<?php
/**
 * @brief		Front Navigation Extension: Menu Header
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Core
 * @since		22 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Menu Header
 */
class _MenuHeader
{
	/**
	 * @brief	The language string for the title
	 */
	protected $title;
	
	/**
	 * Constructor
	 *
	 * @param	string	$title		The language string for the title
	 * @return	void
	 */
	public function __construct( $title )
	{
		$this->title = $title;
	}
		
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		return TRUE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack( $this->title );
	}
		
	/**
	 * Children
	 *
	 * @param	bool	$noStore	If true, will skip datastore and get from DB (used for ACP preview)
	 * @return	array
	 */
	public function children( $noStore=FALSE )
	{
		return NULL;
	}
}