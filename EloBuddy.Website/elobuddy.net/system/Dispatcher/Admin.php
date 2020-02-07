<?php
/**
 * @brief		Admin CP Dispatcher
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
 * Admin CP Dispatcher
 */
class _Admin extends \IPS\Dispatcher\Standard
{
	/**
	 * Controller Location
	 */
	public $controllerLocation = 'admin';
	
	/**
	 * @brief	Cached Menu
	 */
	protected $menu = NULL;
	
	/**
	 * @brief	Search Keywords
	 */
	public $searchKeywords = array();
	
	/**
	 * @brief	ACP Restrictions (for search keyword editing)
	 */
	public $moduleRestrictions = array();
	
	/**
	 * @brief	ACP Restriction for the current menu item (for search keyword editing)
	 */
	public $menuRestriction = NULL;
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		\IPS\Output::i()->sidebar['appmenu'] = '';
		
		/* Sync stuff when in developer mode */
		if ( \IPS\IN_DEV )
		{
			 \IPS\Developer::sync();
		}

		if ( \IPS\Member::loggedIn()->member_id )
		{
			/* Build the menu */
			$menu = $this->buildMenu();

			/* Do we need to figure out the default? */
			if ( !isset( \IPS\Request::i()->app ) )
			{
				foreach ( $menu['tabs'] as $app => $appData )
				{
					if ( isset( $menu['defaults'][ $app ] ) )
					{
						parse_str( $menu['defaults'][ $app ], $defaultQueryString );
						foreach ( $defaultQueryString as $k => $v )
						{
							\IPS\Request::i()->$k = $v;
						}
						break;
					}
				}
			}
		}
		
		/* Call parent */
		static::baseCss();
		static::baseJs();

		/* Stuff needed for output */
		if ( !\IPS\Request::i()->isAjax() )
		{
			/* Special grouped CSS files */
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'core.css', 'core', 'admin' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'responsive.css', 'core', 'front' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'responsive.css', 'core', 'admin' ) );

			/* JS */
			\IPS\Output::i()->globalControllers[] = 'core.admin.core.app';
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin.js' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery-ui.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery-touchpunch.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery.menuaim.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery.nestedSortable.js', 'core', 'interface' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_core.js', 'core', 'front' ) );

			if ( \IPS\Member::loggedIn()->member_id )
			{
				/* These are just defaults in case we hit an immediate error, e.g. app or controller doesn't exist */
				\IPS\Output::i()->sidebar['sidebar'] = \IPS\Theme::i()->getTemplate( 'global', 'core' )->sidebar( array(), 'core_overview' );
				\IPS\Output::i()->sidebar['appmenu'] = \IPS\Theme::i()->getTemplate( 'global', 'core' )->appmenu( $menu, 'core' );
				\IPS\Output::i()->sidebar['mobilenav'] = \IPS\Theme::i()->getTemplate( 'global', 'core' )->mobileNavigation( $menu, 'core' );
			}

		}

		try
		{
			parent::init();
		}
		catch ( \DomainException $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '2S100/' . $e->getCode(), $e->getCode() === 4 ? 403 : 404, '' );
		}
		
		/* Check we're logged in and we have ACP access */
		if( ( !\IPS\Member::loggedIn()->member_id or !\IPS\Member::loggedIn()->isAdmin() )
				and ( $this->module->key !== 'system' or $this->controller !== 'login' )
				and ( !\IPS\ENFORCE_ACCESS )
		)
		{
			$url = \IPS\Http\Url::internal( "app=core&module=system&controller=login", 'admin', NULL, array(), \IPS\Settings::i()->logins_over_https );

			if ( \IPS\Session::i()->error )
			{
				$url = $url->setQueryString( 'error', \IPS\Session::i()->error->getMessage() );
			}
			
			if( !\IPS\Request::i()->isAjax() )
			{
				$url = $url->setQueryString( 'ref', base64_encode( preg_replace( '!adsess=((\w){32}|&)!', "", $_SERVER['QUERY_STRING'] ) ) );
			}
			else if( isset( $_SERVER['HTTP_REFERER'] ) )
			{
				$previous = preg_replace( "/^(.+?)\/\?/", "", $_SERVER['HTTP_REFERER'] );
				$url = $url->setQueryString( 'ref', base64_encode( preg_replace( '!adsess=.*?(&|$)!', "", $previous ) ) );
			}

			\IPS\Output::i()->redirect( $url );
		}
		
		/* Permission Check */
		if ( ( $this->module->key !== 'system' or !in_array( $this->controller, array( 'login', 'language', 'theme', 'livesearch', 'editor', 'ajax' ) ) ) and ( $this->module->key !== 'members' or $this->controller !== 'members' or !in_array( \IPS\Request::i()->do, array( 'adminDetails', 'adminEmail', 'adminPassword' ) ) ) and !\IPS\Member::loggedIn()->hasAcpRestriction( $this->application, $this->module ) )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2S107/1', 403, '' );
		}
		
		/* ACP search keywords */
		if ( \IPS\IN_DEV )
		{
			foreach ( \IPS\Db::i()->select( '*', 'core_acp_search_index' ) as $word )
			{
				$this->searchKeywords[ $word['url'] ]['lang_key'] = $word['lang_key'];
				$this->searchKeywords[ $word['url'] ]['restriction'] = $word['restriction'];
				$this->searchKeywords[ $word['url'] ]['keywords'][] = $word['keyword'];
			}

			$restrictions = array();

			$file = \IPS\ROOT_PATH . "/applications/{$this->application->directory}/data/acprestrictions.json";
			if ( file_exists( $file ) )
			{
				$restrictions = json_decode( file_get_contents( $file ), TRUE );
			}

			$this->moduleRestrictions[''] = 'acpmenu_norestriction';
			if ( isset( $restrictions[ $this->module->key ] ) )
			{
				foreach ( $restrictions[ $this->module->key ] as $key => $values )
				{
					$this->moduleRestrictions[ $key ] = array_combine( $values, $values );
				}
			}
									
			$appMenu = $this->application->acpMenu();
			if ( isset( $appMenu[ $this->module->key ] ) )
			{
				foreach ( $appMenu[ $this->module->key ] as $menuItem )
				{
					if ( $menuItem['restriction'] and $menuItem['controller'] == $this->controller and ( !$menuItem['do'] or ( isset( \IPS\Request::i()->do ) and $menuItem['do'] == \IPS\Request::i()->do ) ) )
					{
						$this->menuRestriction = $menuItem['restriction'];
					}
				}
			}
		}
		
		/* More stuff needed for output */
		if ( !\IPS\Request::i()->isAjax() )
		{
			/* Menu and base navigation */
			if ( \IPS\Member::loggedIn()->member_id )
			{
				/* Work out what tab we're on */
				$currentTab = NULL;
				foreach ( $this->application->acpMenu() as $moduleKey => $items )
				{
					if ( $moduleKey === $this->module->key )
				  	{
				  		foreach ( $items as $itemKey => $item )
				  		{
					  		if ( !$currentTab )
					  		{
					  			$currentTab = $item['tab'];
					  		}
				  			if ( $item['controller'] === $this->controller )
				  			{
					  			break;
					  		}
				  		}
				  	}
				}
				if ( !$currentTab )
				{
					$currentTab = $this->application->directory;
				}

				/* Display */
				if( $this->controller !== 'login' )
				{
					if ( isset( $menu['tabs'][ $currentTab ] ) )
					{
						\IPS\Output::i()->sidebar['sidebar'] = \IPS\Theme::i()->getTemplate( 'global', 'core' )->sidebar( $menu['tabs'][ $currentTab ], $this->application->directory . '_' . $this->module->key );
					}
					\IPS\Output::i()->sidebar['appmenu'] = \IPS\Theme::i()->getTemplate( 'global', 'core' )->appmenu( $menu, $currentTab );
					\IPS\Output::i()->sidebar['mobilenav'] = \IPS\Theme::i()->getTemplate( 'global', 'core' )->mobileNavigation( $menu, $currentTab );
				}
			}
		}
	}
	
	/**
	 * Build Menu
	 *
	 * @param	bool	If TRUE, will rebuild
	 * @return	array
	 */
	public function buildMenu( $rebuild=FALSE )
	{
		if ( $this->menu === NULL or $rebuild === TRUE )
		{
			$this->menu = array( 'tabs' => array(), 'defaults' => array() );
			
			foreach ( \IPS\Application::applications() as $app )
			{
				if ( \IPS\Application::appIsEnabled( $app->directory ) and \IPS\Application::load( $app->directory )->canAccess() )
				{
					foreach ( $app->acpMenu() as $moduleKey => $items )
					{
						if ( \IPS\Member::loggedIn()->hasAcpRestriction( $app, $moduleKey ) )
					  	{				  		
					  		foreach ( $items as $itemKey => $item )
					  		{
					  			if ( !$item['restriction'] or \IPS\Member::loggedIn()->hasAcpRestriction( $app, $moduleKey, $item['restriction'] ) )
					  			{  				
					  				if ( !isset( $this->menu['defaults'][ $item['tab'] ] ) )
					  				{
					  					$this->menu['defaults'][ $item['tab'] ] = "app={$app->directory}&module={$moduleKey}&controller={$item['controller']}";
					  				}
					  				
					  				$this->menu['tabs'][ $item['tab'] ][ "{$app->directory}_{$moduleKey}" ][ $itemKey ] = "app={$app->directory}&module={$moduleKey}&controller={$item['controller']}" . ( $item['do'] ? "&do={$item['do']}" : '' );
					  			}
					  		}
					  	}
					}
				}
			}
		}
				
		$acpTabOrder	= $this->_getAcpTabOrder();
			
		if ( $acpTabOrder !== NULL )
		{
			$_apps	= array_keys( $acpTabOrder );
			uksort( $this->menu['tabs'], function($a, $b) use ( $_apps )
			{
				return array_search( $a, $_apps ) - array_search( $b, $_apps );
			} );
			
			foreach( $acpTabOrder as $app => $submenu )
			{
				if ( !empty( $submenu ) )
				{
					if( isset( $this->menu['tabs'][ $app ] ) )
					{
						uksort( $this->menu['tabs'][ $app ], function($a, $b) use ( $submenu )
						{
							return array_search( $a, $submenu ) - array_search( $b, $submenu );
						} );
					}
					
					if ( isset( $this->menu['defaults'] ) )
					{
						uksort( $this->menu['defaults'], function($a, $b) use ( $acpTabOrder )
						{
							return array_search( $a, $acpTabOrder );
						} );
					}
				}
			}
		}
		
		return $this->menu;
	}

	/**
	 * @brief	Cached ACP tab order
	 */
	protected $acpTabOrder	= NULL;

	/**
	 * Figure out the ACP tab order
	 *
	 * @return array
	 */
	public function _getAcpTabOrder()
	{
		if( $this->acpTabOrder !== NULL )
		{
			return $this->acpTabOrder;
		}
			
		if ( isset( \IPS\Request::i()->cookie['acpTabs'] ) )
		{ 
			$this->acpTabOrder = json_decode( \IPS\Request::i()->cookie['acpTabs'], TRUE );
		}
		else
		{
			try
			{
				$this->acpTabOrder = json_decode( \IPS\Db::i()->select( 'data', 'core_acp_tab_order', array( 'id=?', \IPS\Member::loggedIn()->member_id ) )->first(), TRUE );
			}
			catch( \UnderflowException $ex )
			{ 
				$this->acpTabOrder = array( 'core' => array(), 'community' => array(), 'members' => array(), 'nexus' => array(), 'content' => array(), 'stats' => array(), 'customization' => array() );
			}
			
			\IPS\Request::i()->setCookie( 'acpTabs', json_encode( $this->acpTabOrder ) );
		}

		return $this->acpTabOrder;
	}
	
	/**
	 * Do we have permission to use this module?
	 *
	 * @param	\IPS\Application	$app		Application
	 * @param	\IPS\Module|string	$module		Module
	 * @return	bool
	 */
	public function hasPermission( $app, $module )
	{
		return \IPS\Member::loggedIn()->hasAcpRestriction( $app, $module );
	}
	
	/**
	 * Check ACP Permission
	 *
	 * @param	string					$key		Permission Key
	 * @param	\IPS\Application|null	$app		Application (NULL will default to current)
	 * @param	\IPS\Module|string|null	$module		Module (NULL will default to current)
	 * @return	void
	 */
	public function checkAcpPermission( $key, $app=NULL, $module=NULL )
	{
		if ( !\IPS\Member::loggedIn()->hasAcpRestriction( ( $app ?: $this->application ), ( $module ?: $this->module ), $key ) )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2S107/2', 403, '' );
		}
	}
}
