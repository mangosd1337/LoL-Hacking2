<?php
/**
 * @brief		Facebook Login Handler Redirect URI Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/facebook/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
$state = explode( '-', \IPS\Request::i()->state );
if ( $state[0] == 'ucp' )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service=Facebook&loginProcess=facebook&state={$state[1]}&code=" . urlencode( \IPS\Request::i()->code ), 'front', 'settings_Facebook' ) );
}
else
{
	$destination = \IPS\Http\Url::internal( "app=core&module=system&controller=login&loginProcess=facebook&state={$state[1]}&code=" . urlencode( \IPS\Request::i()->code ), $state[0], 'login', NULL, \IPS\Settings::i()->logins_over_https );
	if ( isset( \IPS\Request::i()->ref ) )
	{
		$destination = $destination->setQueryString( 'ref', \IPS\Request::i()->ref );
	}
		
	\IPS\Output::i()->redirect( $destination );
}