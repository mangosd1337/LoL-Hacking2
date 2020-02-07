<?php
/**
 * @brief		posts
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
 * posts
 */
class _posts extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posts_manage' );
		
		$chart = new \IPS\Helpers\Chart\Dynamic( \IPS\Http\Url::internal( "app=forums&module=stats&controller=posts" ), 'forums_posts', 'post_date', '', array( 'isStacked' => FALSE ) );
		$chart->addSeries( \IPS\Member::loggedIn()->language()->addToStack( 'stats_new_posts' ), 'number', 'COUNT(*)', FALSE );
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack( 'stats_posts_title' );
		$chart->availableTypes = array( 'LineChart', 'ColumnChart', 'BarChart' );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'menu__forums_stats_posts' );
		\IPS\Output::i()->output = (string) $chart;
	}
}