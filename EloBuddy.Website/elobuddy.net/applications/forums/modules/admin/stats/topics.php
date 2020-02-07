<?php
/**
 * @brief		topics
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		18 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * topics
 */
class _topics extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'topics_manage' );
		
		$chart = new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( "app=forums&module=stats&controller=topics" ), 'forums_topics', 'start_date', '', array( 'isStacked' => FALSE ) );
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack( 'stats_new_topics' ), 'number', 'COUNT(*)', FALSE );
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack( 'stats_topics_title' );
		$chart->availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart' );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'menu__forums_stats_topics' );
		\IPS\Output::i()->output = (string) $chart;
	}
}