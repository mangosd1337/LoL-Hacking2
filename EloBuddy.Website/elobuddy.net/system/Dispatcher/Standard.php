<?php
/**
 * @brief		Standard Dispatcher (For Front-End and ACP)
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Nov 2013
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
 * Standard Dispatcher
 */
abstract class _Standard extends \IPS\Dispatcher
{
	/**
	 * Application
	 */
	public $application;
	
	/**
	 * Module
	 */
	public $module;
	
	/**
	 * Controller
	 */
	public $controller;
	
	/**
	 * Base CSS
	 *
	 * @return	void
	 */
	public static function baseCss()
	{
		if ( !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'framework.css', 'core', 'global' ) );
			if ( \IPS\Theme::i()->settings['responsive'] )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'responsive.css', 'core', 'global' ) );
			}
		}

		if ( count( \IPS\Lang::languages() ) > 1 )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'flags.css', 'core', 'global' ) );
		}
	}

	/**
	 * Output the basic javascript files every page needs
	 *
	 * @return void
	 */
	protected static function baseJs()
	{
		/* Stuff for output */
		if ( !\IPS\Request::i()->isAjax() )
		{
			/* JS */
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'library.js' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'js_lang_' . \IPS\Member::loggedIn()->language()->id . '.js' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'framework.js' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'global_core.js', 'core', 'global' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plugins.js', 'core', 'plugins' ) );
			\IPS\Output::i()->jsVars['date_format'] = \IPS\Member::loggedIn()->language()->preferredDateFormat();
			\IPS\Output::i()->jsVars['date_first_day'] = 0;
			\IPS\Output::i()->jsVars['remote_image_proxy'] = intval( \IPS\Settings::i()->remote_image_proxy );
		}
	}
	
	/**
	 * Finish
	 *
	 * @return	void
	 */
	public function finish()
	{		
		/* If we're still here - output */
		if ( ! \IPS\Request::i()->isAjax() )
		{
			/* Load all models for this app and location */
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'app.js', \IPS\Dispatcher::i()->application->directory ) );
			/* Map.js must come last as it will always have the correct file names */
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'map.js' ) );
		}
		
		parent::finish();
	}
	
	/**
	 * Init
	 *
	 * @return	void
	 * @throws	\DomainException
	 */
	public function init()
	{
		/* Force HTTPs? */
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' )
		{
			$baseUrl = new \IPS\Http\Url( \IPS\Settings::i()->base_url );

			if( $baseUrl->data['scheme'] === 'https://' and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
			{
				\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
			}

			if( $baseUrl->data['host'] !== \IPS\Request::i()->url()->data['host'] )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::createFromArray( array_merge( \IPS\Request::i()->url()->data, array( 'host' => $baseUrl->data['host'] ) ) ) );
			}
		}

		/* Set locale */
		\IPS\Member::loggedIn()->language()->setLocale();

		/* Set Application */
		if ( isset( \IPS\Request::i()->app ) )
		{
			try
			{
				$this->application = \IPS\Application::load( \IPS\Request::i()->app );
			}
			catch ( \OutOfRangeException $e )
			{
				$applications = \IPS\Application::applications();
			
				foreach( $applications as $application )
				{
					if( $application->default )
					{
						$this->application = $application;
					}
				}
				
				if( !isset( $this->application ) )
				{
					$this->application = array_shift( $applications );
				}
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'app.js' ) );
				throw new \DomainException( 'requested_route_404', 5 );
			}
		}
		else
		{
			$applications = \IPS\Application::applications();
			
			foreach( $applications as $application )
			{
				if( $application->default )
				{
					$this->application = $application;
				}
			}
			
			if( !isset( $this->application ) )
			{
				$this->application = array_shift( $applications );
			}
		}
		
		/* Init Application */
		if( !$this->application->canAccess( \IPS\Member::loggedIn() ) AND $this->controllerLocation != 'admin' )
		{
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'app.js' ) );
			$message = $this->application->disabled_message ?: 'generic_offline_message';
			throw new \DomainException( $message, 4 );
		}
		if ( method_exists( $this->application, 'init' ) )
		{
			$this->application->init();
		}
		
		/* Set Module */
		if ( isset( \IPS\Request::i()->module ) )
		{
			try
			{
				$this->module = \IPS\Application\Module::get( $this->application->directory, \IPS\Request::i()->module, static::i()->controllerLocation );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'app.js' ) );
				throw new \DomainException( 'requested_route_404', 6 );
			}
		}
		else
		{
			$this->setDefaultModule();
		}
				
		/* Set controller */
		$this->controller = isset( \IPS\Request::i()->controller ) ? \IPS\Request::i()->controller : $this->module->default_controller;

		if( is_array( $this->controller ) )
		{
			$this->controller	= NULL;
			throw new \DomainException( 'requested_route_404', 7 );
		}

		/* Set classname */
		$this->classname = 'IPS\\' . $this->application->directory . '\\modules\\' . $this->controllerLocation . '\\' . $this->module->key . '\\' . $this->controller;
		
		/* Base Templates, CSS and JS */
		if ( !\IPS\Request::i()->isAjax() )
		{
			/* Templates */
			if ( \IPS\CACHE_METHOD === 'None' )
			{
				\IPS\Data\Store::i()->templateLoad[] = array( 'core', $this->controllerLocation, 'global' );
				\IPS\Data\Store::i()->templateLoad[] = array( 'core', 'global', 'global' );
				\IPS\Data\Store::i()->templateLoad[] = array( 'core', 'global', 'forms' );
				\IPS\Data\Store::i()->templateLoad[] = array( 'core', $this->controllerLocation, 'forms' );
				$templateLoad = array();
				foreach ( \IPS\Data\Store::i()->templateLoad as $data )
				{
					$templateLoad[] = 'template_' . \IPS\Theme::i()->id . '_' . \IPS\Theme::makeBuiltTemplateLookupHash( $data[0], $data[1], $data[2] ) . '_' . $data[2];
				}
				\IPS\Data\Store::i()->loadIntoMemory( $templateLoad );
			}
			
			/* App JS */
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'app.js' ) );
			
			/* App CSS */
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( $this->application->directory . '.css', $this->application->directory, $this->controllerLocation ) );
			if ( \IPS\Theme::i()->settings['responsive'] )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( $this->application->directory . '_responsive.css', $this->application->directory, $this->controllerLocation ) );
			}

			/* VLE */
			if ( isset( \IPS\Request::i()->cookie['vle_editor'] ) and \IPS\Request::i()->cookie['vle_editor'] )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'customization/visuallanguage.css', 'core', 'admin' ) );
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'global_customization.js', 'core', 'global' ) );
				\IPS\Output::i()->globalControllers[] = 'core.global.customization.visualLang';
			}			
		}
	}
	
	/**
	 * Set default module
	 *
	 * @return void
	 */
	protected function setDefaultModule()
	{
		$modules = $this->application->modules( static::i()->controllerLocation );
		foreach( $modules as $module )
		{
			if( $module->default )
			{
				$this->module = $module;
				break;
			}
		}

		if( $this->module === NULL )
		{
			$this->module = array_shift( $modules );
		}
	}

	/**
	 * Destructor
	 * Runs tasks
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		/* If you visit and you are redirected to installer, this code should not run */
		if( !file_exists( \IPS\ROOT_PATH . '/conf_global.php' ) )
		{
			return;
		}

		if ( \IPS\Session\Front::loggedIn() and \IPS\Settings::i()->task_use_cron == 'normal' )
		{
			$task = \IPS\Task::queued();
			if ( $task )
			{
				$task->runAndLog();
			}
		}
	}
}