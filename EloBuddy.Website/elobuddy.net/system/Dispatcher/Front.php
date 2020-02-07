<?php
/**
 * @brief		Front-end Dispatcher
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front-end Dispatcher
 */
class _Front extends \IPS\Dispatcher\Standard
{
	/**
	 * Controller Location
	 */
	public $controllerLocation = 'front';
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* Set up in progress? */
		if ( isset( \IPS\Settings::i()->setup_in_progress ) AND \IPS\Settings::i()->setup_in_progress )
		{
			if( isset( $_SERVER['SERVER_PROTOCOL'] ) and \strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
			{
				header( "HTTP/1.0 503 Service Unavailable" );
			}
			else
			{
				header( "HTTP/1.1 503 Service Unavailable" );
			}
					
			require \IPS\ROOT_PATH . '/' . \IPS\UPGRADING_PAGE;
			exit;
		}

		/* Get cached page if available */
		$this->checkCached();
				
		/* Sync stuff when in developer mode */
		if ( \IPS\IN_DEV )
		{
			 \IPS\Developer::sync();
		}
		
		/* Base CSS */
		static::baseCss();

		/* Base JS */
		static::baseJs();

		/* FURLs only apply when calling to index.php */
		$_calledScript	= str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] );
		$_scriptParts	= explode( '/', $_calledScript );
		array_pop( $_scriptParts );
		$_calledScript	= implode( '/', $_scriptParts );

		/* If script_filename was /index.php then calledscript will be empty */
		if( $_calledScript === '' )
		{
			$_calledScript = '/';
		}

		/* Are we going to check for FURLs */
		$processFurls	= FALSE;

		/* If the request was to the local index.php gateway, then yes */
		if( $_calledScript == str_replace( '\\', '/', \IPS\ROOT_PATH ) )
		{
			$processFurls	= TRUE;
		}
		else
		{
			/* If not, check if the request came in through an index.php one directory level up - if so, this was likely
				from the Pages index.php gateway and we still need to process FURLs */
			$forumsPath = explode( '/', str_replace( '\\', '/', \IPS\ROOT_PATH ) );
			array_pop( $forumsPath );

			if( $_calledScript == implode( '/', $forumsPath ) )
			{
				$processFurls	= TRUE;
			}
		}

		/* Handle friendly URLs */
		if ( \IPS\Settings::i()->use_friendly_urls and $processFurls === TRUE )
		{
			try
			{
				try
				{
					foreach ( \IPS\Request::i()->url()->getFriendlyUrlData( \IPS\Settings::i()->seo_r_on and !\IPS\Request::i()->isAjax() and mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and !\IPS\ENFORCE_ACCESS ) as $k => $v )
					{
						if( $k == 'module' )
						{
							$this->_module	= NULL;
						}
						else if( $k == 'controller' )
						{
							$this->_controller	= NULL;
						}
								
						\IPS\Request::i()->$k = $v;
					}
				}
				catch ( \OutOfRangeException $e )
				{
					if( \IPS\Settings::i()->seo_r_on and !\IPS\Request::i()->isAjax() and mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and !\IPS\ENFORCE_ACCESS )
					{
						$defaultApplication = \IPS\Db::i()->select( 'app_directory', 'core_applications', 'app_default=1' )->first();
						$furlDefinitionFile = \IPS\ROOT_PATH . "/applications/{$defaultApplication}/data/furl.json";
						if ( file_exists( $furlDefinitionFile ) )
						{
							$furlDefinition = json_decode( preg_replace( '/\/\*.+?\*\//s', '', file_get_contents( $furlDefinitionFile ) ), TRUE );
							if ( isset( $furlDefinition['topLevel'] ) and $furlDefinition['topLevel'] )
							{
								$baseUrl = parse_url( \IPS\Settings::i()->base_url );
								$url = \IPS\Request::i()->url();
								$query = \IPS\Settings::i()->htaccess_mod_rewrite ? ( isset( $url->data['path'] ) ? $url->data['path'] : '' ) : ( isset( $url->data['query'] ) ? ltrim( $url->data['query'], '/' )  : '' );
								$query = preg_replace( '#^(' . preg_quote( rtrim( $baseUrl['path'], '/' ), '#' ) . ')/(index.php)?(?:(?:\?/|\?))?(.+?)?$#', '$3', $query );
								
								if ( mb_substr( $query, 0, mb_strlen( $furlDefinition['topLevel'] ) ) === $furlDefinition['topLevel'] )
								{
									$target = preg_replace( '/(' . preg_quote( \IPS\Settings::i()->base_url, '/' ) . '(index.php\?\/)?)(' . preg_quote( $furlDefinition['topLevel'], '/' ) . ')\/?/', '$1', (string) $url );

									\IPS\Output::i()->redirect( new \IPS\Http\Url( $target ) );
								}
							}
						}
					}
					
					if ( !\IPS\Request::i()->isAjax() )
					{
						throw $e;
					}
				}
				catch ( \DomainException $e )
				{
					\IPS\Output::i()->redirect( $e->getMessage() );
				}
			}
			catch ( \Exception $e )
			{
				$this->application = \IPS\Application::load('core');
				$this->setDefaultModule();
				
				if ( \IPS\Member::loggedIn()->isBanned() )
				{
					\IPS\Output::i()->sidebar = FALSE;
					\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
				}
				
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'app.js' ) );
				
				\IPS\Output::i()->error( 'requested_route_404', '1S160/2', 404, '' );
			}
		}
		
		/* Perform some legacy URL conversions*/
		static::convertLegacyParameters();

		/* Run global init */
		try
		{
			parent::init();
		}
		catch ( \DomainException $e )
		{	
			// If this is a "no permission", and they're validating - show the validating screen instead
			if( $e->getCode() === 6 and \IPS\Member::loggedIn()->member_id and \IPS\Member::loggedIn()->members_bitoptions['validating'] )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ) );
			}
			// Otherwise show the error
			else
			{
				\IPS\Output::i()->error( $e->getMessage(), '2S100/' . $e->getCode(), $e->getCode() === 4 ? 403 : 404, '' );
			}
		}
		
		/* Enable sidebar by default (controllers can turn it off if needed) */
		\IPS\Output::i()->sidebar['enabled'] = ( \IPS\Request::i()->isAjax() ) ? FALSE : TRUE;
		
		/* Are we online? */
		if ( !( $this->application->directory == 'core' and $this->module->key == 'system' and ( $this->controller == 'login' /* Because you can login when offline */ or $this->controller == 'embed' /* Because the offline message can contain embedded media */ or $this->controller == 'lostpass' or $this->controller == 'register' ) ) AND !\IPS\Settings::i()->site_online AND $this->controllerLocation == 'front' AND !\IPS\Member::loggedIn()->group['g_access_offline'] )
		{
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( \IPS\Member::loggedIn()->language()->addToStack( 'offline_unavailable', FALSE, array( 'sprintf' => array( \IPS\Settings::i()->board_name ) ) ), 503 );
			}
			
			\IPS\Output::i()->showOffline();
		}
		
		/* Member Ban? */
		$ipBanned = \IPS\Request::i()->ipAddressIsBanned();
		if ( $ipBanned or $banEnd = \IPS\Member::loggedIn()->isBanned() )
		{
			if ( !$ipBanned and !\IPS\Member::loggedIn()->member_id )
			{
				if ( $this->notAllowedBannedPage() )
				{
					$url = \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' );
					if ( \IPS\Request::i()->url() != \IPS\Settings::i()->base_url )
					{
						$url = $url->setQueryString( 'ref', base64_encode( \IPS\Request::i()->url() ) );
					}
					\IPS\Output::i()->redirect( $url );
				}
			}
			else
			{

				\IPS\Output::i()->sidebar = FALSE;
				\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
				if( $this->controller !== 'contact' )
				{
					$message = 'member_banned';
					if ( !$ipBanned and $banEnd instanceof \IPS\DateTime )
					{
						$message = \IPS\Member::loggedIn()->language()->addToStack( 'member_banned_temp', FALSE, array( 'htmlsprintf' => array( $banEnd->html() ) ) );
					}
					\IPS\Output::i()->error( $message, '2S160/4', 403, '' );
				}
			}
		}
		
		/* Do we need more info from the member or do they need to validate? */
		if( \IPS\Member::loggedIn()->member_id )
		{
			/* Need their name or email... */
			if( ( \IPS\Member::loggedIn()->real_name === '' or !\IPS\Member::loggedIn()->email ) and $this->controller !== 'register' )
			{ 
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=complete' )->setQueryString( 'ref', base64_encode( \IPS\Request::i()->url() ) ) );
			}
			/* Need them to validate... */
			elseif( \IPS\Member::loggedIn()->members_bitoptions['validating'] and $this->controller !== 'register' and $this->controller !== 'login' and $this->controller != 'redirect' and $this->controller !== 'contact' and $this->controller !== 'privacy' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=validating', 'front', 'register' ) );
			}
			
			/* Need them to reconfirm terms/privacy policy */
			elseif ( ( \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_privacy'] or \IPS\Member::loggedIn()->members_bitoptions['must_reaccept_terms'] ) and $this->controller !== 'register' and $this->controller !== 'ajax' )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=register&do=reconfirm', 'front', 'register' )->setQueryString( 'ref', base64_encode( \IPS\Request::i()->url() ) ) );
			}
		}
		
		/* Permission Check */
		if ( !\IPS\Member::loggedIn()->canAccessModule( $this->module ) )
		{
			\IPS\Output::i()->error( ( \IPS\Member::loggedIn()->member_id ? 'no_module_permission' : 'no_module_permission_guest' ), '2S100/2', 403, 'no_module_permission_admin' );
		}
		
		/* Stuff for output */
		if ( !\IPS\Request::i()->isAjax() )
		{
			/* Base Navigation. We only add the module not the app as most apps don't have a global base (for example, in Nexus, you want "Store" or "Client Area" to be the base). Apps can override themselves in their controllers. */
			foreach( \IPS\Application::applications() as $directory => $application )
			{
				if( $application->default )
				{
					$defaultApplication	= $directory;
					break;
				}
			}

			if( !isset( $defaultApplication ) )
			{
				$defaultApplication = 'core';
			}
			
			if ( $this->module->key != 'system' AND $this->application->directory != $defaultApplication )
			{
				\IPS\Output::i()->breadcrumb['module'] = array( \IPS\Http\Url::internal( 'app=' . $this->application->directory . '&module=' . $this->module->key . '&controller=' . $this->module->default_controller, 'front', $this->module->key ), $this->module->_title );
			}
			
			/* Figure out what the global search is */
			if ( !$this->application->default )
			{
				foreach ( $this->application->extensions( 'core', 'ContentRouter' ) as $object )
				{
					if ( count( $object->classes ) === 1 )
					{
						$classes = $object->classes;
						foreach ( $classes as $class )
						{
							if ( is_subclass_of( $class, 'IPS\Content\Searchable' ) and $class::$includeInSiteSearch )
							{
								$type = mb_strtolower( str_replace( '\\', '_', mb_substr( array_pop( $classes ), 4 ) ) );
								\IPS\Output::i()->defaultSearchOption = array( $type, "{$type}_pl" );
								break;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Define that the page should load even if the user is banned and not logged in
	 *
	 * @return	bool
	 */
	protected function notAllowedBannedPage()
	{
		return !\IPS\Member::loggedIn()->group['g_view_board'] and !$this->application->allowGuestAccess( $this->module, $this->controller, \IPS\Request::i()->do );
	}

	/**
	 * Check cache for this page
	 *
	 * @return	void
	 */
	protected function checkCached()
	{
		/* If this is a guest and there's a full cached page, we can serve that */
		if( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and !isset( \IPS\Request::i()->csrfKey ) and !\IPS\Session\Front::loggedIn() and !isset( \IPS\Request::i()->cookie['noCache'] ) and !\IPS\Request::i()->ipAddressIsBanned() and \IPS\CACHE_PAGE_TIMEOUT )
		{
			/* Which language? */
			if ( isset( \IPS\Request::i()->cookie['language'] ) )
			{
				$language = \IPS\Request::i()->cookie['language'];
			}
			else
			{
				/* HTTP_ACCEPT_LANGUAGE is only available via http, thus trying to enable FURLs fails as we send out a socket request to test .htaccess */
				$language = ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) ? \IPS\Lang::autoDetectLanguage( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) : NULL;
				
				if ( $language === NULL )
				{
					$language = \IPS\Lang::defaultLanguage();
				}
			}
			
			/* Which theme? */
			$theme = isset( \IPS\Request::i()->cookie['theme'] ) ? \IPS\Request::i()->cookie['theme'] : \IPS\Theme::defaultTheme();
			
			/* Get cache */
			try
			{
				$cache = \IPS\Data\Cache::i()->getWithExpire( 'page_' . md5( ( !empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ) . '/' . $_SERVER['REQUEST_URI'] ) . "_{$language}_{$theme}", TRUE );
				$sessionId = isset( \IPS\Request::i()->cookie['IPSSessionFront'] ) ? \IPS\Request::i()->cookie['IPSSessionFront'] : \IPS\Session::i()->id;
				
				\IPS\Output::i()->sendOutput( str_replace( '{{csrfKey}}', md5( '&& 0&' . $sessionId ), $cache['output'] ), $cache['code'], $cache['contentType'], $cache['httpHeaders'], FALSE, TRUE );
			}
			catch ( \OutOfRangeException $e ) {}
		}
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public static function convertLegacyParameters()
	{
		foreach( \IPS\Application::applications() as $directory => $application )
		{
			if( method_exists( $application, 'convertLegacyParameters' ) )
			{
				$application->convertLegacyParameters();
			}
		}
	}

	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{
		/* Sidebar Widgets */
		if( !\IPS\Request::i()->isAjax() )
		{
			$widgets = array();
			
			if ( ! isset( \IPS\Output::i()->sidebar['widgets'] ) OR ! is_array( \IPS\Output::i()->sidebar['widgets'] ) )
			{
				\IPS\Output::i()->sidebar['widgets'] = array();
			}
			
			try
			{
				$widgetConfig = \IPS\Db::i()->select( '*', 'core_widget_areas', array( 'app=? AND module=? AND controller=?', $this->application->directory, $this->module->key, $this->controller ) );
				foreach( $widgetConfig as $area )
				{
					$widgets[ $area['area'] ] = json_decode( $area['widgets'], TRUE );
				}
			}
			catch ( \UnderflowException $e ) {}
			
			if ( \IPS\Output::i()->allowDefaultWidgets )
			{
				foreach( \IPS\Widget::appDefaults( $this->application ) as $widget )
				{
					/* If another app has already defined this area, don't overwrite it */
					if ( isset( $widgets[ $widget['default_area'] ] ) )
					{
						continue;
					}
	
					$widget['unique']	= $widget['key'];
					
					$widgets[ $widget['default_area'] ][] = $widget;
				}
			}
					
			if( count( $widgets ) )
			{
				if ( \IPS\CACHE_METHOD === 'None' )
				{
					$templateLoad = array();
					foreach ( $widgets as $areaKey => $area )
					{
						foreach ( $area as $widget )
						{
							if ( isset( $widget['app'] ) and $widget['app'] )
							{
								$templateLoad[] = array( $widget['app'], 'front', 'widgets' );
								$templateLoad[] = 'template_' . \IPS\Theme::i()->id . '_' . \IPS\Theme::makeBuiltTemplateLookupHash( $widget['app'], 'front', 'widgets' ) . '_widgets';
							}
						}
					}
	
					if( count( $templateLoad ) )
					{
						\IPS\Data\Store::i()->loadIntoMemory( $templateLoad );
					}
				}
				
				$widgetObjects = array();
				$storeLoad = array();
				foreach ( $widgets as $areaKey => $area )
				{
					foreach ( $area as $widget )
					{
						try
						{
							$appOrPlugin = isset( $widget['plugin'] ) ? \IPS\Plugin::load( $widget['plugin'] ) : \IPS\Application::load( $widget['app'] );

							if( isset( $widget['plugin'] ) and !$appOrPlugin->enabled )
							{
								continue;
							}
							
							$_widget = \IPS\Widget::load( $appOrPlugin, $widget['key'], ( ! empty($widget['unique'] ) ? $widget['unique'] : uniqid() ), ( isset( $widget['configuration'] ) ) ? $widget['configuration'] : array(), ( isset( $widget['restrict'] ) ? $widget['restrict'] : null ), ( $areaKey == 'sidebar' ) ? 'vertical' : 'horizontal' );
							if ( \IPS\CACHE_METHOD === 'None' and isset( $_widget->cacheKey ) )
							{
								$storeLoad[] = $_widget->cacheKey;
							}
							$widgetObjects[ $areaKey ][] = $_widget;
						}
						catch ( \Exception $e )
						{
							\IPS\Log::log( $e, 'dispatcher' );
						}
					}
				}

				if( \IPS\CACHE_METHOD === 'None' and count( $storeLoad ) )
				{
					\IPS\Data\Store::i()->loadIntoMemory( $storeLoad );
				}
				
				foreach ( $widgetObjects as $areaKey => $_widgets )
				{
					foreach ( $_widgets as $_widget )
					{
						\IPS\Output::i()->sidebar['widgets'][ $areaKey ][] = $_widget;
					}
				}
			}
		}
		
		/* Meta tags */
		\IPS\Output::i()->buildMetaTags();
		
		/* Finish */
		parent::finish();
	}

	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs()
	{
		parent::baseJs();

		/* Stuff for output */
		if ( !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->globalControllers[] = 'core.front.core.app';
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front.js' ) );

			if ( \IPS\Member::loggedIn()->members_bitoptions['bw_using_skin_gen'] AND ( isset( \IPS\Request::i()->cookie['vseThemeId'] ) AND \IPS\Request::i()->cookie['vseThemeId'] ) and \IPS\Member::loggedIn()->isAdmin() and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_easy_editor' ) )
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_vse.js', 'core', 'front' ) );
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'vse/vsedata.js', 'core', 'interface' ) );
				\IPS\Output::i()->globalControllers[] = 'core.front.vse.window';
			}

			/* Can we edit widget layouts? */
			if( \IPS\Member::loggedIn()->modPermission('can_manage_sidebar') )
			{
				\IPS\Output::i()->globalControllers[] = 'core.front.widgets.manager';
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_widgets.js', 'core', 'front' ) );
			}

			/* Are we editing meta tags? */
			if( isset( $_SESSION['live_meta_tags'] ) and $_SESSION['live_meta_tags'] and \IPS\Member::loggedIn()->isAdmin() )
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_system.js', 'core', 'front' ) );
			}
		}
	}

	/**
	 * Base CSS
	 *
	 * @return	void
	 */
	public static function baseCss()
	{
		parent::baseCss();

		/* Stuff for output */
		if ( !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'core.css', 'core', 'front' ) );
			if ( \IPS\Theme::i()->settings['responsive'] )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'core_responsive.css', 'core', 'front' ) );
			}
			
			if ( \IPS\Member::loggedIn()->members_bitoptions['bw_using_skin_gen'] AND ( isset( \IPS\Request::i()->cookie['vseThemeId'] ) AND \IPS\Request::i()->cookie['vseThemeId'] ) and \IPS\Member::loggedIn()->isAdmin() and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'customization', 'theme_easy_editor' ) )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/vse.css', 'core', 'front' ) );
			}

			/* Are we editing meta tags? */
			if( isset( $_SESSION['live_meta_tags'] ) and $_SESSION['live_meta_tags'] and \IPS\Member::loggedIn()->isAdmin() )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/meta_tags.css', 'core', 'front' ) );
			}
			
			/* Query log? */
			if ( \IPS\QUERY_LOG )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/query_log.css', 'core', 'front' ) );
			}
			if ( \IPS\CACHING_LOG )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/caching_log.css', 'core', 'front' ) );
			}
		}
	}
}