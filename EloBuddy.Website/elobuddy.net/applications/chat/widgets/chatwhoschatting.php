<?php
/**
 * @brief		chatwhoschatting Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		15 Mar 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\chat\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * chatwhoschatting Widget
 */
class _chatwhoschatting extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'chatwhoschatting';
	
	/**
	 * @brief	App
	 */
	public $app = 'chat';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if( !\IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'chat', 'chat' ) ) )
		{
			return '';
		}

		$chatters = ( isset( \IPS\Data\Store::i()->chatters ) ) ? \IPS\Data\Store::i()->chatters : array();

		/* It's possible to get duplicates from the chat server if a user rejoins the room, so weed them out */
		$seen = array();

		foreach( $chatters as $uid => $chatter )
		{
			/* Have we already seen this? Remove duplicates */
			if( in_array( $chatter['forumUserID'], $seen ) )
			{
				unset( $chatters[ $uid ] );
			}

			/* If the last update was longer than 120 seconds ago, they're gone */
			if( $chatter['last_update'] < time() - 120 )
			{
				unset( $chatters[ $uid ] );
			}

			$seen[ $chatter['forumUserID'] ] = $chatter['forumUserID'];
		}

		return $this->output( $chatters );
	}
}