<?php
/**
 * @brief		Staff directory management
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Sep 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\staff;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Staff directory management
 */
class _directory extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\core\StaffDirectory\Group';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'staffdirectory/staffdirectory.css', 'core', 'admin' ) );
		return parent::execute();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'staff_add_group';
			unset( $buttons['add']['data'] );
		}
		
		return $buttons;
	}
}