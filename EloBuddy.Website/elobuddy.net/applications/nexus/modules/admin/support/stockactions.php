<?php
/**
 * @brief		stockactions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		15 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * stockactions
 */
class _stockactions extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Support\StockAction';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'stockactions_manage' );
		parent::execute();
	}
}