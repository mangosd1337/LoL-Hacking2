<?php
/**
 * @brief		updatecheck Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		14 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * updatecheck Task
 */
class _updatecheck extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * @return	mixed	Message to log or NULL
	 */
	public function execute()
	{
		$fails = array();
		
		/* Do IPS apps */
		$versions = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_applications', \IPS\Db::i()->in( 'app_directory', \IPS\Application::$ipsApps ) ) as $app )
		{
			if ( $app['app_enabled'] )
			{
				$versions[] = $app['app_long_version'];
			}
		}
		$version = min( $versions );
		$url = \IPS\Http\Url::ips('updateCheck');
		if ( \IPS\USE_DEVELOPMENT_BUILDS )
		{
			$url = $url->setQueryString( 'development', 1 );
		}
		try
		{
			$response = $url->setQueryString( 'version', $version )->request()->get()->decodeJson();
			$coreApp = \IPS\Application::load('core');
			$currentDetails = json_decode( $coreApp->update_version, TRUE );
			if ( $currentDetails['version'] != $response['version'] and \IPS\Settings::i()->upgrade_email )
			{
				\IPS\Email::buildFromTemplate( 'core', 'upgrade', array( $response['version'], $response['releasenotes'], $response['security'] ), \IPS\Email::TYPE_LIST )->send( explode( ',', \IPS\Settings::i()->upgrade_email ) );
			}
			$coreApp->update_version = json_encode( $response );
			$coreApp->update_last_check = time();
			$coreApp->save();
		}
		catch ( \Exception $e ) { }

		/* Do everything else */
		foreach ( \IPS\Db::i()->union(
			array(
				\IPS\Db::i()->select( "'core_applications' AS `table`, app_directory AS `id`, app_update_check AS `url`, app_update_last_check AS `last`, app_long_version AS `current`", 'core_applications', "( app_update_check<>'' AND app_update_check IS NOT NULL )" ),
				\IPS\Db::i()->select( "'core_plugins' AS `table`, plugin_id AS id, plugin_update_check as url, plugin_update_check_last AS last, plugin_version_long AS `current`", 'core_plugins', "plugin_update_check<>'' AND plugin_update_check IS NOT NULL" ),
				\IPS\Db::i()->select( "'core_themes' AS `table`, set_id AS `id`, set_update_check AS `url`, set_update_last_check AS `last`, set_long_version AS `current`", 'core_themes', "set_update_check<>'' AND set_update_check IS NOT NULL" )
			),
			'last ASC',
			3
		) as $row )
		{
			switch ( $row['table'] )
			{
				case 'core_applications':
					$dataColumn = 'app_update_version';
					$timeColumn = 'app_update_last_check';
					$idColumn	= 'app_directory';

                    /* Account for legacy applications */
                    try
                    {
						$key = "__app_{$row['id']}";
                        $source = \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( $key );
                    }
                    catch( \UnexpectedValueException $e )
                    {
                        continue 2;
                    }
                    catch( \UnderflowException $e )
                    {
                    	continue 2;
                    }
					break;

				case 'core_plugins':
					$dataColumn = 'plugin_update_check_data';
					$timeColumn = 'plugin_update_check_last';
					$idColumn	= 'plugin_id';
					$source = \IPS\Plugin::load( $row['id'] )->name;
					break;

				case 'core_themes':
					$dataColumn = 'set_update_data';
					$timeColumn = 'set_update_last_check';
					$idColumn	= 'set_id';
					$key = "core_theme_set_title_{$row['id']}";
					$source = \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( $key );
					break;
			}

			try
			{
				/* Query the applications update URL */
				$url = \IPS\Http\Url::external( $row['url'] )->setQueryString( array( 'version' => $row['current'], 'ips_version' => $version ) );
				$response = $url->request()->get()->decodeJson();

				/* Did we get all the information we need? */
				if ( !isset( $response['version'], $response['longversion'], $response['released'], $response['updateurl'] ) )
				{
					throw new \RuntimeException( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'update_check_missing' ) );
				}

				/* Save the latest version data and move on to the next app */
				\IPS\Db::i()->update( $row['table'], array(
					$dataColumn => json_encode( array(
						'version'		=> $response['version'],
						'longversion'	=> $response['longversion'],
						'released'		=> $response['released'],
						'updateurl'		=> $response['updateurl'],
						'releasenotes'	=> isset( $response['releasenotes'] ) ? $response['releasenotes'] : NULL
					) ),
					$timeColumn	=> time()
				), array( "{$idColumn}=?", $row['id'] ) );
			}
			/* \RuntimeException catches BAD_JSON and \IPS\Http\Request\Exception both */
			catch ( \RuntimeException $e )
			{
				$fails[] = $source . ": " . $e->getMessage();
			}
		}
		
		if ( !empty( $fails ) )
		{
			return $fails;
		}
		
		return NULL;
	}
}