<?php
/**
 * @brief		Registration Stats
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 June 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Registration Stats
 */
class _registrationstats extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage Members
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'registrations_manage', 'core', 'members' );
		
		$chart	= new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( 'app=core&module=stats&controller=registrationstats' ), 'core_members', 'joined', '', array( 'isStacked' => FALSE ) );
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('stats_new_registrations'), 'number', 'COUNT(*)', FALSE );
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_registrations_title');
		$chart->availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart' );

		/* fetch only successful registered members ; if this needs to be changed, please review the other areas where we have the name<>? AND email<>? condition */
		$chart->where[] = array( '( name<>? AND email<>? )', '', '' );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_registrationstats');
		\IPS\Output::i()->output = (string) $chart;
	}
}
