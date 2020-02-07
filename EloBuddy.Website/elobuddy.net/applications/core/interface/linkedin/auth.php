<?php
/**
 * @brief		LinkedIn Account Login Handler Redirect URI Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/linkedin/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

if ( isset( \IPS\Request::i()->error ) and \IPS\Request::i()->error )
{
	\IPS\Dispatcher\Front::i();
	if ( \IPS\Request::i()->error == 'access_denied' )
	{
		/* user didn't proceed with login, we don't want to log this */
		\IPS\Output::i()->error( htmlentities( \IPS\Request::i()->error_description, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ), '1C271/2', 403 );
	}
	else
	{
		\IPS\Output::i()->error( htmlentities( \IPS\Request::i()->error_description, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ), '4C271/1', 403 );
	}
}

$state = explode( '-', \IPS\Request::i()->state );

if ( $state[0] == 'ucp' )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service=Linkedin&loginProcess=linkedin&state={$state[1]}&code=" . urlencode( \IPS\Request::i()->code ), 'front', 'settings_LinkedIn' ) );
}
else
{
	$destination = \IPS\Http\Url::internal( "app=core&module=system&controller=login&loginProcess=linkedin&state={$state[1]}&code=" . urlencode( \IPS\Request::i()->code ), \IPS\Request::i()->state, 'login', NULL, \IPS\Settings::i()->logins_over_https );
	if ( !empty( $state[2] ) )
	{
		$destination = $destination->setQueryString( 'ref', $state[2] );
	}
	
	\IPS\Output::i()->redirect( $destination );
}