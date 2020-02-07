//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class chat_hook_tabCount extends _HOOK_CLASS_
{
	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs()
	{
		parent::baseJs();

		if ( !isset( \IPS\Data\Store::i()->chatters ) )
		{
			\IPS\Data\Store::i()->chatters = array();
		}
		$chatters = \IPS\Data\Store::i()->chatters;
		$seen = array();

		$count = 0;

		if( is_array( $chatters ) AND count( $chatters ) )
		{
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

			$count = count( $chatters );
		}

		if( !$count )
		{
			return;
		}

\IPS\Output::i()->endBodyCode .= <<<HTML
<script type='text/javascript'>
  $('[data-role="navBarItem"][data-navApp="chat"][data-navExt="Chat"] > a').append( $('<span/>').addClass('ipsNotificationCount').text('{$count}') );
</script>
HTML;
	}
}