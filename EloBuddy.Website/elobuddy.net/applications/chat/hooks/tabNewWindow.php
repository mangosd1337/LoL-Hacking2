//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class chat_hook_tabNewWindow extends _HOOK_CLASS_
{
	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs()
	{
		parent::baseJs();

		if( !\IPS\Settings::i()->ipchat_new_window )
		{
			return;
		}

\IPS\Output::i()->endBodyCode .= <<<HTML
<script type='text/javascript'>
  $('[data-role="navBarItem"][data-navapp="chat"][data-navext="Chat"] a').click( function( e ){
  	e.preventDefault();

	var joinWith = '?';
	
	if ( $( e.currentTarget ).attr('href').indexOf('?') != -1 )
	{
		joinWith = '&';
	}

  	window.open( $( e.currentTarget ).attr('href') + joinWith + '_popup=1', '_blank', "height=480,width=1200,location=0,menubar=0,toolbar=0" );
  	return false;
  });
</script>
HTML;
	}

	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{		
		/* Is this a popup? */
		if ( \IPS\Request::i()->_popup == 1 AND \IPS\Dispatcher::i()->application->directory == 'chat' )
		{
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( \IPS\Output::i()->output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		
		parent::finish();
	}
}