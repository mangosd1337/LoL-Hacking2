<?php
/**
 * @brief		API Splash Page
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		03 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\applications;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API Splash Page
 */
class _api extends \IPS\Dispatcher\Controller
{
	/**
	 * Call
	 *
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		/* Check htaccess is correct */
		if ( \IPS\Settings::i()->use_friendly_urls and \IPS\Settings::i()->htaccess_mod_rewrite )
		{
			$url = \IPS\Http\Url::external( rtrim( \IPS\Settings::i()->base_url, '/' ) . '/api/core/hello' );
		}
		else
		{
			$url = \IPS\Http\Url::external( rtrim( \IPS\Settings::i()->base_url, '/' ) . '/api/index.php?/core/hello' );
		}
		try
		{
			$response = $url->request()->login( 'test', '' )->get()->decodeJson();
			if ( $response['errorMessage'] != 'INVALID_API_KEY' and $response['errorMessage'] != 'TOO_MANY_REQUESTS_WITH_BAD_KEY' )
			{
				throw new \Exception;
			}
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_applications_api');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'api' )->htaccess( isset( \IPS\Request::i()->recheck ), $url );
			return;
		}
		
		/* Work out tabs */
		$tabs = array();
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'api_manage' ) )
		{
			$tabs['apiKeys'] = 'api_keys';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'api_logs' ) )
		{
			$tabs['apiLogs'] = 'api_logs';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'api_reference' ) )
		{
			$tabs['apiReference'] = 'api_reference';
		}

		if ( isset( \IPS\Request::i()->tab ) and isset( $tabs[ \IPS\Request::i()->tab ] ) )
		{
			$activeTab = \IPS\Request::i()->tab;
		}
		else
		{
			$_tabs = array_keys( $tabs ) ;
			$activeTab = array_shift( $_tabs );
		}
		
		/* Route */
		$classname = 'IPS\core\modules\admin\applications\\' . $activeTab;
		$class = new $classname;
		$class->url = \IPS\Http\Url::internal("app=core&module=applications&controller=api&tab={$activeTab}");
		$class->execute();
		if ( $method !== 'manage' or \IPS\Request::i()->isAjax() )
		{
			return;
		}
		
		/* Help button */
		\IPS\Output::i()->sidebar['actions']['help']	= array(
			'icon'		=> 'question-circle',
			'title'		=> 'help',
			'link'		=> \IPS\Http\Url::ips( 'docs/restapi' ),
			'target'	=> 'blank'
		);
		
		/* Output */
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'system/api.css', 'core', 'admin' ) );
		\IPS\Output::i()->jsFiles  = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_system.js', 'core', 'admin' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_applications_api');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, \IPS\Output::i()->output, \IPS\Http\Url::internal( "app=core&module=applications&controller=api" ) );
	}
	
	/**
	 * Download .htaccess file
	 *
	 * @return	void
	 */
	protected function htaccess()
	{
		$dir = rtrim( str_replace( \IPS\CP_DIRECTORY . '/index.php', '', $_SERVER['PHP_SELF'] ), '/' ) . '/api/';
		$path = $dir . 'index.php';
		if( \strpos( $dir, ' ' ) !== FALSE )
		{
			$dir = '"' . $dir . '"';
			$path = '"' . $path . '"';
		}

		$htaccess = <<<FILE
<IfModule mod_setenvif.c>
SetEnvIf Authorization .+ HTTP_AUTHORIZATION=$0
</IfModule>
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$dir}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* index.php [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
</IfModule>
FILE;

		\IPS\Output::i()->sendOutput( $htaccess, 200, 'application/x-htaccess', array( 'Content-Disposition' => 'attachment; filename=.htaccess' ) );
	}
}