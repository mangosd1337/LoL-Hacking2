<?php
/**
 * @brief		New Support Request Volumme
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		25 Apr 2014
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
 * volume
 */
class _volume extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'volume_manage' );
		parent::execute();
	}

	/**
	 * View
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		$chart	= new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( 'app=nexus&module=support&controller=volume' ), 'nexus_support_requests', 'r_started', '',
			array(
				'vAxis'		=> array( 'title' => \IPS\Member::loggedIn()->language()->addToStack('support_requests_created') ),
			),
			'LineChart'
		);
		$chart->groupBy	= 'r_department';
		foreach( \IPS\nexus\Support\Department::roots() as $department )
		{
			$chart->addSeries( $department->_title, 'number', 'COUNT(*)', TRUE, $department->id );
		}
				
		\IPS\Output::i()->output = (string) $chart;
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_support_volume');
	}
}