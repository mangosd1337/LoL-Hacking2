<?php
/**
 * @brief		Dashboard extension: Online Users
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Online Users
 */
class _OnlineUsers
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return TRUE;
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		/* Init Chart */
		$chart = new \IPS\Helpers\Chart;
		
		/* Specify headers */
		$chart->addHeader( \IPS\Member::loggedIn()->language()->get('chart_app'), "string" );
		$chart->addHeader( \IPS\Member::loggedIn()->language()->get('chart_members'), "number" );
		
		/* Add Rows */
		$online = \IPS\Db::i()->select( "COUNT(*) AS count, current_appcomponent",
				'core_sessions',
				array( 'running_time>?', \IPS\DateTime::create()->sub( new \DateInterval('PT30M') )->getTimestamp() ),
				NULL,
				NULL,
				'current_appcomponent'
		);

		foreach ( $online as $row )
		{
			/* Only show if the application is still installed and enabled */
			if( !\IPS\Application::appIsEnabled( $row['current_appcomponent'] ) )
			{
				continue;
			}

			$chart->addRow( array( \IPS\Member::loggedIn()->language()->addToStack( "__app_" . $row['current_appcomponent']), $row['count'] ) );
		}
		
		/* Output */
		return \IPS\Theme::i()->getTemplate( 'dashboard' )->onlineUsers( $online, $chart->render( 'PieChart', array( 'backgroundColor' 	=> '#fafafa', 'chartArea' => array( 'width' =>"90%", 'height' => "90%" ) ) ) );
	}
}