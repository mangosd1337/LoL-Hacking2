<?php
/**
 * @brief		Chat Application Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2014 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		03 Jan 2014
 * @version		
 */
 
namespace IPS\chat;

/**
 * Chat Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther()
	{
		/* Set non guests to be able to access */
		foreach( \IPS\Member\Group::groups( TRUE, FALSE ) as $group )
		{
			$group->chat_access = TRUE;
			$group->save();
		}
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'comment';
	}
	
	/**
	 * Is chat online? Returns error message if not
	 *
	 * @return string|TRUE
	 */
	public function offlineMessage()
	{
		if( \IPS\Settings::i()->ipschat_online )
		{
			$times = explode( ',', \IPS\Settings::i()->ipschat_online );

			if( count( $times ) AND $times[0] )
			{
				/* Do we need to adjust the time zone? */
				if( isset( $times[2] ) AND $times[2] != \IPS\Member::loggedIn()->timezone )
				{
					$start = new \IPS\DateTime( '2015-01-01 ' . $times[0], new \DateTimeZone( $times[2] ) );
					$start->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
					$times[0] = $start->format( 'H:i' );

					$end = new \IPS\DateTime( '2015-01-01 ' . $times[1], new \DateTimeZone( $times[2] ) );
					$end->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
					$times[1] = $end->format( 'H:i' );
				}

				$currentTime = new \IPS\DateTime( NULL, new \DateTimeZone( ( isset( $times[2] ) ) ? $times[2] : \IPS\Member::loggedIn()->timezone ) );
				$currentTime->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
				$currentTime = $currentTime->format( 'H:i' );

				/* Convert times to floats, as that is simple to compare */
				$start			= str_replace( ':', '.', $times[0] );
				$end			= str_replace( ':', '.', $times[1] );
				$currentTime	= str_replace( ':', '.', $currentTime );

				if( $end > $start )
				{
					if( !( $currentTime >= $start ) OR !( $currentTime < $end ) )
					{
						return \IPS\Member::loggedIn()->language()->addToStack( 'chat_currently_offline', FALSE, array( 'sprintf' => $start ) );
					}
				}
				else
				{
					if( !( $currentTime >= $start ) AND $currentTime >= $end )
					{
						return \IPS\Member::loggedIn()->language()->addToStack( 'chat_currently_offline', FALSE, array( 'sprintf' => $start ) );
					}
				}
			}
		}
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Chat' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
}