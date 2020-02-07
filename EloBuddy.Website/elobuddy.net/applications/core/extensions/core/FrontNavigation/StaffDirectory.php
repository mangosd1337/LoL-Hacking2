<?php
/**
 * @brief		Front Navigation Extension: Staff Directory
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		30 Jun 2015
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
 * Front Navigation Extension: Staff Directory
 */
class _StaffDirectory extends \IPS\core\FrontNavigation\FrontNavigationAbstract
{
	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('staff');
	}
	
	/**
	 * Can access?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		return parent::canView() and !\IPS\Settings::i()->staff_directory_empty and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'staffdirectory' ) );
	}
			
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function title()
	{
		return \IPS\Member::loggedIn()->language()->addToStack('staff');
	}
	
	/**
	 * Get Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function link()
	{
		return \IPS\Http\Url::internal( "app=core&module=staffdirectory&controller=directory", 'front', 'staffdirectory' );
	}
	
	/**
	 * Is Active?
	 *
	 * @return	bool
	 */
	public function active()
	{
		return \IPS\Dispatcher::i()->application->directory === 'core' and \IPS\Dispatcher::i()->module->key === 'staffdirectory' and \IPS\Dispatcher::i()->controller === 'directory';
	}
}