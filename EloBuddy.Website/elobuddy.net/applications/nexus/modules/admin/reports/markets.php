<?php
/**
 * @brief		Markets Report
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
 * Markets Report
 */
class _markets extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'markets_manage' );
		parent::execute();
	}

	/**
	 * View Chart
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$tabs['count'] = 'nexus_report_count';
		foreach ( \IPS\nexus\Money::currencies() as $currency )
		{
			$tabs[ $currency ] = \IPS\Member::loggedIn()->language()->addToStack( 'nexus_report_income', NULL, array( 'sprintf' => array( $currency ) ) );
		}
		
		$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : 'count';
				
		$chart = new \IPS\Helpers\Chart\Dynamic(
			\IPS\Http\Url::internal( 'app=nexus&module=reports&controller=markets&tab=' . $activeTab ),
			'nexus_invoices',
			'i_paid',
			'',
			array(),
			'GeoChart',
			'monthly',
			array( 'start' => 0, 'end' => 0 ),
			array(),
			$activeTab
		);
		$chart->availableTypes[] = 'GeoChart';
		$chart->where[] = array( 'i_status=? AND i_billcountry IS NOT NULL', \IPS\nexus\Invoice::STATUS_PAID );
		if ( $activeTab !== 'count' )
		{
			$chart->where[] = array( 'i_currency=?', $activeTab );
			$chart->format = $activeTab;
		}
		$chart->groupBy = 'i_billcountry';
				
		foreach ( \IPS\GeoLocation::$countries as $countryCode )
		{
			$chart->addSeries( \IPS\Member::loggedIn()->language()->get( 'country-' . $countryCode ), 'number', $activeTab === 'count' ? 'COUNT(*)' : 'SUM(i_total)', TRUE, $countryCode );
		}
		
		if ( $chart->type === 'GeoChart' )
		{
			$chart->options['height'] = 750;
			$chart->options['keepAspectRatio'] = true;
		}
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = (string) $chart;
		}
		else
		{	
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_reports_markets');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, (string) $chart, \IPS\Http\Url::internal( "app=nexus&module=reports&controller=markets" ) );
		}
	}
}