<?php
/**
 * @brief		Upgrader: Continue Upgrade
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 Nov 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Continue Upgrade
 */
class _continueupgrade extends \IPS\Dispatcher\Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Best check for the upgrade file first */
		$json = json_decode( @file_get_contents( \IPS\ROOT_PATH . '/uploads/logs/upgrader_data.cgi' ), TRUE );
			
		if ( is_array( $json ) and isset( $json['session'] ) and isset( $json['data'] ) )
		{
			$url = \IPS\Http\Url::internal( "controller=upgrade" )->setQueryString( 'key', $_SESSION['uniqueKey'] );
			$mr  = array();
			
			/* Update session */
			foreach( $json['session'] as $k => $v )
			{
				if ( $k !== 'uniqueKey' )
				{
					$_SESSION[ $k ] = $v;
				}
			}
			
			\IPS\Output::i()->redirect( $url->setQueryString( 'mr', base64_encode( urlencode( json_encode( $json['data'] ) ) ) )->setQueryString( 'mr_continue', 1 ) );
		}
		else
		{
			\IPS\Output::i()->error( 'cannot_continue_upgrade', '', 403, '' );
		}
	}
}