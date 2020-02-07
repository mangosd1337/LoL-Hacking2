<?php
/**
 * @brief		Upgrader: Applications
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
 * Upgrader: Applications
 */
class _applications extends \IPS\Dispatcher\Controller
{
	/**
	 * Show Form
	 *
	 * @todo	[Upgrade] This may not accurately detect older versions - need to test and adjust
	 * @return	void
	 */
	public function manage()
	{
		$apps			= array();
		$defaultTicks	= array();
		
		/* Update old app keys or everything gets confused */
		\IPS\Db::i()->update( 'core_applications', array( 'app_directory' => 'cms' ), array( 'app_directory=?', 'ccs' ) );
		\IPS\Db::i()->update( 'core_applications', array( 'app_directory' => 'chat' ), array( 'app_directory=?', 'ipchat' ) );

		/* We had a bug in an earlier beta where the version may not have updated properly, so we need to account for that but it has to happen before we load version files */
		/* @todo We may want to remove those down the road as it should only affect users who have upgraded to early betas */
		if( \IPS\Db::i()->checkForTable( 'core_widgets' ) AND !\IPS\Db::i()->checkForColumn( 'core_widgets', 'embeddable' ) )
		{
			\IPS\Db::i()->update( 'core_applications', array( 'app_version' => '4.0.0 Beta 1', 'app_long_version' => '100001' ) );
		}

		/* Clear any caches or else we might not see new versions */
		if ( isset( \IPS\Data\Store::i()->applications ) )
		{
			unset( \IPS\Data\Store::i()->applications );
		}
		if ( isset( \IPS\Data\Store::i()->modules ) )
		{
			unset( \IPS\Data\Store::i()->modules );
		}
		
		$haltUpgrade	= FALSE;

		foreach( \IPS\Application::applications() as $app => $data )
		{
			$path = \IPS\ROOT_PATH . '/applications/' . $app;

			/* Skip incomplete apps */
			if ( ! is_dir( $path . '/data' ) )
			{
				continue;
			}
			
			/* See if there are any errors */
			$errors = array();
			
			if ( file_exists( $path . '/setup/requirements.php' ) )
			{
				require $path . '/setup/requirements.php';
			}

			/* Figure out of an upgrade is even available */
			$currentVersion		= \IPS\Application::load( $app )->long_version;
			$availableVersion	= \IPS\Application::getAvailableVersion( $app );

			if ( empty( $errors ) AND $availableVersion > $currentVersion )
			{
				$defaultTicks[] = $app;
			}
			
			$name = $data->_title;
			
			/* Get app name */
			if ( file_exists( $path . '/data/lang.xml' ) )
			{
				$xml = new \XMLReader;
				$xml->open( $path . '/data/lang.xml' );
				$xml->read();
				
				$xml->read();
				while ( $xml->read() )
				{
					if ( $xml->getAttribute('key') === '__app_' . $app )
					{
						$name = $xml->readString();
						break;
					}
				}
			}

			if( count( $errors ) )
			{
				$haltUpgrade	= TRUE;
			}
			
			$apps[ $app ] = array(
				'name'		=> $name,
				'disabled'	=> ( !empty( $errors ) OR $availableVersion <= $currentVersion ),
				'errors'	=> $errors,
				'current'	=> \IPS\Application::load( $app )->version,
				'available'	=> \IPS\Application::getAvailableVersion( $app, TRUE ),
				'force'		=> ( $app === 'core' and $availableVersion > $currentVersion )
			);
		}

		/* Bring core app to top */
		$system['core'] = $apps['core'];
		unset( $apps['core'] );
		$apps = array_merge( $system, $apps );

		if( count( $defaultTicks ) )
		{
			$form = new \IPS\Helpers\Form( 'applications', 'continue' );
			$form->add( new \IPS\Helpers\Form\Custom( 'apps', $defaultTicks, TRUE, array(
				'getHtml'	=> function( $element ) use ( $apps )
				{
					return \IPS\Theme::i()->getTemplate( 'forms' )->apps( $apps, $element->value );
				},
				'validate'	=> function( $element ) use ( $defaultTicks )
				{
					$uninstallable = ( $element->value === NULL ) ? NULL : array_diff( array_keys( $element->value ), $defaultTicks );

					if ( !empty( $uninstallable ) )
					{
						throw new \DomainException;
					}
				}
			) ) );

			if ( $values = $form->values() )
			{
				$_SESSION['apps'] = array();
				
				if ( $apps['core']['force'] )
				{
					$_SESSION['apps']['core'] = 'core';
				}

				if( count( $values['apps'] ) )
				{
					foreach ( $values['apps'] as $k => $v )
					{
						if ( !is_int( $k ) )
						{
							$_SESSION['apps'][$k] = $k;
						}
					}
				}
				
				$warnings = array();
				$coreVersion = array_key_exists( 'core', $_SESSION['apps'] ) ? \IPS\Application::getAvailableVersion( 'core' ) : \IPS\Application::load( 'core' )->long_version;
				foreach ( \IPS\Application::$ipsApps as $key )
				{
					try
					{
						$appVersion = array_key_exists( $key, $_SESSION['apps'] ) ? \IPS\Application::getAvailableVersion( $key ) : \IPS\Application::load( $key )->long_version;
						if ( $appVersion != $coreVersion )
						{
							$warnings[] = $key;
						}
					}
					catch( \OutOfRangeException $e )
					{
						/* The application is not installed */
						continue;
					}
				}
								
				if ( count( $warnings ) )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "controller=applications&do=warning" )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'warnings' => implode( ',', $warnings ) ) ) );
				}

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "controller=customoptions" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
			}

			if( $haltUpgrade )
			{
				$form->actionButtons = array( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( "continue", 'submit', null, 'ipsButton ipsButton_disabled', array( 'disabled' => 'disabled' ) ) );
			}
		}
		else
		{
			$form	= \IPS\Theme::i()->getTemplate( 'forms' )->noapps();
		}

		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('applications');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'applications', $form );
	}
	
	/**
	 * Show Warning
	 *
	 * @return	void
	 */
	public function warning()
	{
		$apps = array();
		foreach ( explode( ',', \IPS\Request::i()->warnings ) as $key )
		{
			try
			{
				$name = \IPS\Application::load( $key )->_title;
				$path = \IPS\ROOT_PATH . '/applications/' . $key;
				if ( file_exists( $path . '/data/lang.xml' ) )
				{
					$xml = new \XMLReader;
					$xml->open( $path . '/data/lang.xml' );
					$xml->read();
					
					$xml->read();
					while ( $xml->read() )
					{
						if ( $xml->getAttribute('key') === '__app_' . $key )
						{
							$name = $xml->readString();
							break;
						}
					}
				}
				$apps[] = $name;
			}
			catch ( \Exception $e ) { }
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('applications');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'applications', \IPS\Theme::i()->getTemplate( 'global' )->appWarnings( $apps ) );
	}
}