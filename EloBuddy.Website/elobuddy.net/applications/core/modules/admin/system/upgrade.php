<?php
/**
 * @brief		upgrade
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * upgrade
 */
class _upgrade extends \IPS\Dispatcher\Controller
{	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'upgrade_manage' );
		
		if ( \IPS\NO_WRITES )
		{
			\IPS\Output::i()->error( 'no_writes', '1C287/1', 403, '' );
		}
		
		$wizard = new \IPS\Helpers\Wizard( array(
			'upgrade_confirm_update'	=> array( $this, '_selectVersion' ),
			'upgrade_login'				=> array( $this, '_login' ),
			'upgrade_ftp_details'		=> array( $this, '_ftpDetails' ),
			'upgrade_extract_update'	=> array( $this, '_extractUpdate' ),
			'upgrade_upgrade'			=> array( $this, '_upgrade' ),
		), \IPS\Http\Url::internal( 'app=core&module=system&controller=upgrade' ) );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('ips_suite_upgrade');
		\IPS\Output::i()->output = $wizard;
	}
	
	/**
	 * Select Version
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _selectVersion( $data )
	{		
		/* Check latest version */
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
			$coreApp->update_version = json_encode( $response );
			$coreApp->update_last_check = time();
			$coreApp->save();
		}
		catch ( \Exception $e ) { }
		
		/* Build form */
		$form = new \IPS\Helpers\Form( 'select_version' );
		$options = array();
		$descriptions = array();
		$latestVersion = 0;
		foreach( \IPS\Application::load( 'core' )->availableUpgrade() as $possibleVersion )
		{
			$options[ $possibleVersion['longversion'] ] = $possibleVersion['version'];
			$descriptions[ $possibleVersion['longversion'] ] = $possibleVersion;
			if ( $latestVersion < $possibleVersion['longversion'] )
			{
				$latestVersion = $possibleVersion['longversion'];
			}
		}
		if ( \IPS\TEST_DELTA_ZIP )
		{
			$options['test'] = 'x.y.z';
			$descriptions['test'] = array(
				'releasenotes'	=> '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis scelerisque rhoncus leo. In eu ultricies magna. Vivamus nec est vitae felis iaculis mollis non ac ante. In vitae erat quis urna volutpat vulputate. Integer ultrices tellus felis, at posuere nulla faucibus nec. Fusce malesuada nunc purus, luctus accumsan nulla rhoncus ut. Nam ac pharetra magna. Nam semper augue at mi tempus, sed dapibus metus cursus. Suspendisse potenti. Curabitur at pulvinar metus, sed pharetra elit.</p>',
				'security'		=> FALSE,
				'updateurl'		=> '',
			);
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'version', $latestVersion, TRUE, array( 'options' => $options, '_details' => $descriptions ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Check requirements */
			try
			{
				$requirements = \IPS\Http\Url::ips('requirements')->setQueryString( 'version', $values['version'] )->request()->get()->decodeJson();
				$phpVersion = PHP_VERSION;
				$mysqlVersion = \IPS\Db::i()->server_info;
				if ( !( version_compare( $phpVersion, $requirements['php']['required'] ) >= 0 ) )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'requirements_php_version_fail', FALSE, array( 'sprintf' => array( $mysqlVersion, $requirements['mysql']['required'], $requirements['mysql']['recommended'] ) ) ), '1C287/2' );
				}
				if ( !( version_compare( $mysqlVersion, $requirements['mysql']['required'] ) >= 0 ) )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'requirements_mysql_version_fail', FALSE, array( 'sprintf' => array( $mysqlVersion, $requirements['mysql']['required'], $requirements['mysql']['recommended'] ) ) ), '1C287/3', 403, '' );
				}
			}
			catch ( \Exception $e ) {}

			/* Return */
			return array( 'version' => $values['version'] );
		}
		
		/* Display */
		return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'system' ) ), 'upgradeSelectVersion' ) );
	}
	
	/**
	 * Login
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _login( $data )
	{
		/* If we're just testing, we can skip this step */
		if ( \IPS\TEST_DELTA_ZIP and $data['version'] == 'test' )
		{
			$data['key'] = 'test';
			return $data;
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form( 'login', 'continue' );
		$form->hiddenValues['version'] = $data['version'];
		$form->add( new \IPS\Helpers\Form\Email( 'ips_email_address', NULL ) );
		$form->add( new \IPS\Helpers\Form\Password( 'ips_password', NULL ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{		
			$key = \IPS\IPS::licenseKey();
						
			$url = \IPS\Http\Url::ips( 'build/' . $key['key'] )->setQueryString( 'ip', \IPS\Request::i()->ipAddress() );
			if ( \IPS\USE_DEVELOPMENT_BUILDS )
			{
				$url = $url->setQueryString( 'development', 1 );
			}
			elseif ( isset( $values['version'] ) )
			{
				$url = $url->setQueryString( 'versionToDownload', $values['version'] );
			}
			if ( \IPS\CP_DIRECTORY !== 'admin' )
			{
				$url = $url->setQueryString( 'cp_directory', \IPS\CP_DIRECTORY );
			}
			
			try
			{
				$response = $url->request( \IPS\LONG_REQUEST_TIMEOUT )->login( $values['ips_email_address'], $values['ips_password'] )->get();
				switch ( $response->httpResponseCode )
				{
					case 200:
						if ( !preg_match( '/^ips_[a-z0-9]{5}$/', (string) $response ) )
						{
							\IPS\Log::log( (string) $response, 'auto_upgrade' );
							$form->error = \IPS\Member::loggedIn()->language()->addToStack('download_upgrade_error');
							break;
						}
						else
						{
							$data['key'] = (string) $response;
							return $data;
						}
					
					case 304:
						if ( \IPS\Db::i()->select( 'MIN(app_long_version)', 'core_applications', \IPS\Db::i()->in( 'app_directory', \IPS\Application::$ipsApps ) )->first() < \IPS\Application::getAvailableVersion('core') )
						{
							$data['key'] = NULL;
							return $data;
						}
						$form->error = \IPS\Member::loggedIn()->language()->addToStack('download_upgrade_nothing');
						break;
					
					default:
						$form->error = (string) $response;
				}
				
			}
			catch ( \Exception $exception )
			{
				\IPS\Log::log( $exception, 'auto_upgrade' );
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('download_upgrade_error');
			}
		}
		
		return (string) $form;
	}
	
	/**
	 * Get FTP Details
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _ftpDetails( $data )
	{
		if ( \IPS\DELTA_FORCE_FTP or !is_writable( \IPS\ROOT_PATH . '/init.php' ) or !is_writable( \IPS\ROOT_PATH . '/applications/core/Application.php' ) or !is_writable( \IPS\ROOT_PATH . '/system/Db/Db.php' ) )
		{
			/* If the server does not have the Ftp extension, we can't do this and have to prompt the user to downlad manually... */
			if ( !function_exists( 'ftp_connect' ) )
			{
				return \IPS\Theme::i()->getTemplate('system')->upgradeDeltaFailed( 'ftp', isset( $data['key'] ) ? \IPS\Http\Url::ips("download/{$data['key']}") : NULL );
			}
			/* Otherwise, we can ask for FTP details... */
			else
			{
				/* If they've clicked the button to manually apply patch, let them do that */
				if ( isset( \IPS\Request::i()->manual ) )
				{
					$data['manual'] = TRUE;
					return $data;
				}
				/* Otherwise, carry on */
				else
				{
					/* Define the method we will use to validate the FTP details */
					$validateCallback = function( $ftp ) {
						try
						{
							if ( file_get_contents( \IPS\ROOT_PATH . '/conf_global.php' ) != $ftp->download( 'conf_global.php' ) )
							{
								throw new \DomainException('delta_upgrade_ftp_details_no_match');
							}
						}
						catch ( \IPS\Ftp\Exception $e )
						{
							throw new \DomainException('delta_upgrade_ftp_details_err');
						}
					};
					
					/* If we have details stored, retreive them */
					if ( \IPS\Settings::i()->upgrade_ftp_details and $decoded = @json_decode( \IPS\Text\Encrypt::fromCipher( \IPS\Settings::i()->upgrade_ftp_details )->decrypt(), TRUE ) )
					{
						$defaultDetails = $decoded;
					}
					/* Otherwise, guess the server/username/password for the user's benefit */
					else
					{
						$defaultDetails = array(
							'server'	=> \IPS\Http\Url::internal('')->data['host'],
							'un'		=> @get_current_user(),
							'path'		=> str_replace( '/home/' . @get_current_user(), '', \IPS\ROOT_PATH )
						);
					}
											
					/* Build the form */
					$form = new \IPS\Helpers\Form( 'ftp_details', 'continue' );
					$form->add( new \IPS\Helpers\Form\Ftp( 'delta_upgrade_ftp_details', $defaultDetails, TRUE, array( 'rejectUnsupportedSftp' => TRUE ), $validateCallback ) );
					$form->add( new \IPS\Helpers\Form\Checkbox( 'delta_upgrade_ftp_remember', TRUE ) );
					
					/* Handle submissions */
					if ( $values = $form->values() )
					{
						if ( $values['delta_upgrade_ftp_remember'] )
						{
							\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Text\Encrypt::fromPlaintext( json_encode( $values['delta_upgrade_ftp_details'] ) )->cipher ), array( 'conf_key=?', 'upgrade_ftp_details' ) );
							unset( \IPS\Data\Store::i()->settings );
						}
						
						$data['ftpDetails'] = $values['delta_upgrade_ftp_details'];
						return $data;
					}
					
					/* Display the form */
					return \IPS\Theme::i()->getTemplate('system')->upgradeDeltaFtp( (string) $form );
				}
			}
		}
		else
		{
			return $data;
		}
	}
	
	/**
	 * Download & Extract Update
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _extractUpdate( $data )
	{
		/* Download & Extract */
		if ( $data['key'] and !isset( \IPS\Request::i()->check ) )
		{
			/* If we've asked to do it manually, just show that screen */
			if ( $data['manual'] )
			{
				return \IPS\Theme::i()->getTemplate('system')->upgradeDeltaFailed( NULL, isset( $data['key'] ) ? \IPS\Http\Url::ips("download/{$data['key']}") : NULL );;
			}
					
			/* Muliple Redirector */
			$url = \IPS\Http\Url::internal('app=core&module=system&controller=upgrade');
			return (string) new \IPS\Helpers\MultipleRedirect( $url, function( $mrData ) use ( $data )
			{
				/* Init */
				if ( !is_array( $mrData ) )
				{
					return array( array( 'status' => 'download' ), \IPS\Member::loggedIn()->language()->addToStack('delta_upgrade_processing') );
				}
				/* Download */
				elseif ( $mrData['status'] == 'download' )
				{
					if ( !isset( $mrData['tmpFileName'] ) )
					{					
						$mrData['tmpFileName'] = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' ) . '.zip';
						
						return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('delta_upgrade_downloading'), 0 );
					}
					else
					{
						if ( \IPS\TEST_DELTA_ZIP and $data['version'] == 'test' )
						{
							\file_put_contents( $mrData['tmpFileName'], file_get_contents( \IPS\TEST_DELTA_ZIP ) );
							$mrData['status'] = 'extract';
							return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('delta_upgrade_extracting'), 0 );
						}
						else
						{
							if ( !isset( $mrData['range'] ) )
							{
								$mrData['range'] = 0;
							}
							$startRange = $mrData['range'];
							$endRange = $startRange + 1000000 - 1;
							
							$response = \IPS\Http\Url::ips("download/{$data['key']}")->request( \IPS\LONG_REQUEST_TIMEOUT )->setHeaders( array( 'Range' => "bytes={$startRange}-{$endRange}" ) )->get();

							\IPS\Log::debug( "Fetching download [range={$startRange}-{$endRange}] with a response code: " . $response->httpResponseCode, 'auto_upgrade' );
				
							if ( $response->httpResponseCode == 404 )
							{
								if ( isset( $mrData['tmpFileName'] ) )
								{
									@unlink( $mrData['tmpFileName'] );
								}

								\IPS\Log::log( "Cannot fetch delta download: " . var_export( $response, TRUE ), 'auto_upgrade' );
								
								return array( \IPS\Theme::i()->getTemplate('system')->upgradeDeltaFailed( NULL, isset( $data['key'] ) ? \IPS\Http\Url::ips("download/{$data['key']}") : NULL ) );
							}
							elseif ( $response->httpResponseCode == 206 )
							{
								$totalFileSize = intval( mb_substr( $response->httpHeaders['Content-Range'], mb_strpos( $response->httpHeaders['Content-Range'], '/' ) + 1 ) );
								$fh = \fopen( $mrData['tmpFileName'], 'a' );
								\fwrite( $fh, (string) $response );
								\fclose( $fh );
		
								$mrData['range'] = $endRange + 1;
								return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('delta_upgrade_downloading'), 100 / $totalFileSize * $mrData['range'] );
							}
							else
							{
								$mrData['status'] = 'extract';
								return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('delta_upgrade_extracting'), 0 );
							}
						}
					}
				}
				/* Extract */
				elseif ( $mrData['status'] == 'extract' )
				{
					try
					{
						/* Log what we're doing */
						\IPS\Log::debug( "Attempting to extract", 'auto_upgrade' );
						
						/* If we're using FTP, make the connection */
						$ftp = NULL;
						if ( isset( $data['ftpDetails'] ) )
						{
							$ftp = \IPS\Helpers\Form\Ftp::connectFromValue( $data['ftpDetails'] );
						}

						/* Open the zip */
						$zip = \IPS\Archive\Zip::fromLocalFile( $mrData['tmpFileName'], $data['key'] . '/' );
						
						/* Extract some files */
						$numberToDo = 20;
						if ( !isset( $mrData['extractNumber'] ) )
						{
							$mrData['extractNumber'] = 0;
						}
						$offset = $mrData['extractNumber'];
												
						if ( $zip->extract( \IPS\ROOT_PATH, $numberToDo, $offset, $ftp ) === TRUE )
						{
							if ( isset( $mrData['tmpFileName'] ) )
							{
								@unlink( $mrData['tmpFileName'] );
							}
							
							return NULL;
						}
						else
						{
							$mrData['extractNumber'] += $numberToDo;
							$numberOfFiles = $zip->numberOfFiles();
							
							\IPS\Log::debug( "Extracting files {$mrData['extractNumber']} / {$numberOfFiles}", 'auto_upgrade' );
						
							return array( $mrData, \IPS\Member::loggedIn()->language()->addToStack('delta_upgrade_extracting'), 100 / $numberOfFiles * $mrData['extractNumber'] );
						}
					}
					catch ( \RuntimeException $e )
					{
						\IPS\Log::log( $e, 'auto_upgrade' );
						
						\IPS\Session\Admin::i()->log( ( $e->getCode() === \IPS\Archive\Exception::COULD_NOT_OPEN ) ? 'delta_upgrade_zip_fail' : ( $e->getCode() === \IPS\Archive\Exception::COULD_NOT_WRITE ? 'delta_upgrade_write_fail' : '' ) );
						
						if ( isset( $mrData['tmpFileName'] ) )
						{
							@unlink( $mrData['tmpFileName'] );
						}
						
						return array( \IPS\Theme::i()->getTemplate('system')->upgradeDeltaFailed( NULL, isset( $data['key'] ) ? \IPS\Http\Url::ips("download/{$data['key']}") : NULL ) );
					}
				}
			},
			function()
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=system&controller=upgrade&check=1') );
			} );
		}
				
		/* Run md5 check */
		try
		{
			$files = \IPS\Application::md5Check();
			if ( count( $files ) )
			{
				\IPS\Log::debug( "MD5 check of delta download failed with " . count( $files ) . " reported as modified", 'auto_upgrade' );

				return \IPS\Theme::i()->getTemplate('system')->upgradeDeltaFailed( $files, isset( $data['key'] ) ? \IPS\Http\Url::ips("download/{$data['key']}") : NULL );
			}
		}
		catch ( \Exception $e ) {}
						
		/* Nope, we're good! */
		return $data;
	}
	
	/**
	 * Upgrade
	 *
	 * @param	array	$data	Wizard data
	 * @return	string|array
	 */
	public function _upgrade( $data )
	{
		\IPS\Output::i()->redirect( 'upgrade/?adsess=' . \IPS\Request::i()->adsess );
	}
}