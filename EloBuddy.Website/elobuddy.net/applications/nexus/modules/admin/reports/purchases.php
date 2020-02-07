<?php
/**
 * @brief		Purchases Reports
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		14 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\reports;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Purchases Reports
 */
class _purchases extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'purchases_manage' );
		parent::execute();
	}

	/**
	 * View Chart
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$chart = new \IPS\Helpers\Chart\Dynamic(
			\IPS\Http\Url::internal('app=nexus&module=reports&controller=purchases'),
			'nexus_purchases',
			'ps_start'
		);
		$chart->where[] = array( 'ps_app=? AND ps_type=?', 'nexus', 'package' );
		$chart->groupBy = 'ps_item_id';
		
		$packages = array();
		foreach ( \IPS\Db::i()->select( 'p_id', 'nexus_packages' ) as $packageId )
		{
			$packages[ $packageId ] = \IPS\Member::loggedIn()->language()->get( 'nexus_package_' . $packageId );
		}
		
		asort( $packages );
		foreach ( $packages as $id => $name )
		{
			$chart->addSeries( $name, 'number', 'COUNT(*)', TRUE, $id );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_reports_purchases');
		\IPS\Output::i()->output = (string) $chart;
	}
}