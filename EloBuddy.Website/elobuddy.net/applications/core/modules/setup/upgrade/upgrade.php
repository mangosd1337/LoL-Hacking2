<?php
/**
 * @brief		Upgrader: Perform Upgrade
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 May 2014
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
 * Upgrader: Perform Upgrade
 */
class _upgrade extends \IPS\Dispatcher\Controller
{
	/**
	 * Upgrade
	 *
	 * @return	void
	 */
	public function manage()
	{
		$multipleRedirect = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( 'controller=upgrade' )->setQueryString( 'key', $_SESSION['uniqueKey'] ),
			function( $data )
			{
				try
				{
					$upgrader = new \IPS\core\Setup\Upgrade( array_keys( $_SESSION['apps'] ) );
				}
				catch ( \Exception $e )
				{
					\IPS\Output::i()->error( 'error', '2C222/1', 403, '' );
				}
				
				try
				{
					return $upgrader->process( $data );
				}
				catch( \Exception $e )
				{
					\IPS\Log::log( $e, 'upgrade_error' );
					
					/* Error thrown that we want to handle differently */
					$mrData = json_decode( urldecode( base64_decode( \IPS\Request::i()->mr ) ), true );
					
					if ( isset( $_SESSION['updatedData'] ) and isset( $_SESSION['updatedData'][1] ) )
					{
						$mrData = $_SESSION['updatedData'];
					}
					
					$continueData = $mrData;
					$retryData    = $mrData;
										
					if ( isset( $mrData['extra'] ) or isset( $_SESSION['lastJsonIndex'] ) )
					{
						$continueData['extra']['lastSqlId'] = ( isset($_SESSION['lastJsonIndex']) ? ( $_SESSION['lastJsonIndex'] + 1 ) : 0 );
						$retryData['extra']['lastSqlId']    = ( ( isset($_SESSION['lastJsonIndex']) and $_SESSION['lastJsonIndex'] > 1 ) ? $_SESSION['lastJsonIndex'] - 1 : 0 );
					}
					
					if ( isset( $mrData['extra']['_upgradeStep'] ) )
					{						
						$continueData['extra']['_upgradeStep'] += 1;
						$continueData['extra']['_upgradeData'] = 0;
					}
															
					$continueUrl = \IPS\Http\Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr' => base64_encode( urlencode( json_encode( $continueData ) ) ), 'mr_continue' => 1 ) );
					$retryUrl    = \IPS\Http\Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr' => base64_encode( urlencode( json_encode( $retryData ) ) ), 'mr_continue' => 1 ) );
					
					$error = \IPS\Theme::i()->getTemplate( 'global' )->upgradeError( $e, $continueUrl, $retryUrl );
					
					\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('error');
					\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'install', $error, FALSE );
					 
					/* If we're still here - output */
					if ( \IPS\Request::i()->isAjax() )
					{
						\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( \IPS\Output::i()->output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
					}
					else
					{
						\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
					}
				}
				catch( \BadMethodCallException $e )
				{
					/* Allow multi-redirect handle this */
					throw $e;
				}
			},
			function()
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'controller=done' ) );
			}
		);
	
		\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('upgrade');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'upgrade', $multipleRedirect, FALSE );
	}
}