<?php
/**
 * @brief		Content Router extension: Topics
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		15 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Topics
 */
class _Topics
{	
	/**
	 * @brief	Content Item Classes
	 */
	public $classes = array();
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Member|NULL	$member		If checking access, the member to check for, or NULL to not check access
	 * @return	void
	 */
	public function __construct( \IPS\Member $member = NULL )
	{
		if ( $member === NULL or $member->canAccessModule( \IPS\Application\Module::get( 'forums', 'forums', 'front' ) ) )
		{
			$this->classes[] = 'IPS\forums\Topic';
		}
	}
}