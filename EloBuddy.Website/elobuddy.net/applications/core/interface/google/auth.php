<?php
/**
 * @brief		Google Login Handler Redirect URI Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/google/auth.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
$base = explode( '-', \IPS\Request::i()->state );
if ( $base[0] == 'ucp' )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service=Google&loginProcess=google&base=ucp&state={$base[1]}&code=" . urlencode( \IPS\Request::i()->code ), 'front', 'settings_google' ) );
}
else
{
	$destination = \IPS\Http\Url::internal( "app=core&module=system&controller=login&loginProcess=google&base={$base[0]}&state={$base[1]}&code=" . urlencode( \IPS\Request::i()->code ), $base[0], 'login', NULL, \IPS\Settings::i()->logins_over_https );
	if ( !empty( $base[2] ) )
	{
		$destination = $destination->setQueryString( 'ref', $base[2] );
	}
		
	\IPS\Output::i()->redirect( $destination );
}