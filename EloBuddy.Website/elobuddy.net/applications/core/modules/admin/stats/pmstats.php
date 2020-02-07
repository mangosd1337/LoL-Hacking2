<?php
/**
 * @brief		Messenger Stats
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
 * Messenger Stats
 */
class _pmstats extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage Members
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'messages_manage', 'core', 'members' );
		
		$chart = new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( 'app=core&module=stats&controller=pmstats' ), 'core_message_posts', 'msg_date', '', array( 'isStacked' => TRUE ) );
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('new_conversations'), 'number', 'SUM(msg_is_first_post)' );
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack('mt_replies'), 'number', '( COUNT(*) - SUM(msg_is_first_post) )' );
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('stats_messages_title');
		$chart->availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart' );
	
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_stats_pmstats');
		\IPS\Output::i()->output = (string) $chart;
	}
}