<?php
/**
 * @brief		Build/Tools Dispatcher
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		2 Apr 2013
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
 * @brief	Build/Tools Dispatcher
 */
class _Build extends \IPS\Dispatcher
{
	/**
	 * @brief Controller Location
	 */
	public $controllerLocation = 'front';
	public $application        = 'core';
	public $module		       = 'system';
	
	/**
	 * @brief Step
	 */
	public $step = 1;
	
	/**
	 * Initiator
	 *
	 * @return	void
	 */
	public function init()
	{
		$modules = \IPS\Application\Module::modules();
		$this->application = \IPS\Application::load('core');
		$this->module      = $modules['core']['front']['system'];
		$this->controller  = 'build';
		
		return true;
	}

	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run()
	{
		if ( isset( \IPS\Request::i()->force ) )
		{
			if ( isset( \IPS\Data\Store::i()->builder_building ) )
			{
				unset( \IPS\Data\Store::i()->builder_building );
			}
		}
		else
		{
			if ( isset( \IPS\Data\Store::i()->builder_building ) and ! empty( \IPS\Data\Store::i()->builder_building ) )
			{
				/* We're currently rebuilding */
				if ( time() - \IPS\Data\Store::i()->builder_building < 180  )
				{
					print "Builder is already running. To force a rebuild anyway, add &force=1 on the end of your URL";
					exit();
				}
			}
			
			\IPS\Data\Store::i()->builder_building = time();
		}
		
		unset( \IPS\Data\Store::i()->settings );
		
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 0 ), array( 'conf_key=?', 'site_online' ) );
		\IPS\Settings::i()->site_online	= 0;
		unset( \IPS\Data\Store::i()->settings );
	}
	
	/**
	 * Done
	 *
	 * @return	void
	 */
	public function buildDone()
	{
		if ( isset( \IPS\Data\Store::i()->builder_building ) )
		{
			unset( \IPS\Data\Store::i()->builder_building );
		}

		unset( \IPS\Data\Store::i()->settings );
		
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 1 ), array( 'conf_key=?', 'site_online' ) );
		\IPS\Settings::i()->site_online	= 1;
		unset( \IPS\Data\Store::i()->settings );
	}
}