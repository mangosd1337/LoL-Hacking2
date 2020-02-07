<?php
/**
 * @brief		Advanced Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 June 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advanced Settings
 */
class _advanced extends \IPS\Dispatcher\Controller
{
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage' );
		parent::execute();
	}
	
	/**
	 * Manage: Works out tab and fetches content
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Work out output */
		\IPS\Request::i()->tab = isset( \IPS\Request::i()->tab ) ? \IPS\Request::i()->tab : 'settings';
		if ( $pos = mb_strpos( \IPS\Request::i()->tab, '-' ) )
		{
			$activeTabContents = call_user_func( array( $this, '_manage'.ucfirst( mb_substr( \IPS\Request::i()->tab, 0, $pos ) ) ), mb_substr( \IPS\Request::i()->tab, $pos + 1 ) );
		}
		else
		{
			$activeTabContents = call_user_func( array( $this, '_manage'.ucfirst( \IPS\Request::i()->tab  ) ) );
		}
		
		/* If this is an AJAX request, just return it */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Build tab list */
		$tabs = array();
		$tabs['settings'] = 'server_environment';
		if ( \IPS\Settings::i()->use_friendly_urls )
		{
			$tabs['furl']  = 'furls';
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'datastore' ) )
		{
			$tabs['datastore'] = 'data_store';
		}
			
		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_settings_advanced');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, \IPS\Request::i()->tab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced" ) );
	}

	/**
	 * Data store management
	 *
	 * @return	string
	 */
	protected function _manageDatastore()
	{
		/* Are we just checking the constants? */
		if ( isset( \IPS\Request::i()->checkConstants ) )
		{			
			/* If we've changed anything, explain to the admin they have to update */
			if ( \IPS\Request::i()->store_method !== \IPS\STORE_METHOD or \IPS\Request::i()->store_config !== \IPS\STORE_CONFIG or \IPS\Request::i()->cache_method !== \IPS\CACHE_METHOD or \IPS\Request::i()->cache_config !== \IPS\CACHE_CONFIG or \IPS\Request::i()->cache_guest_page != \IPS\CACHE_PAGE_TIMEOUT )
			{
				$downloadUrl = \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=downloadDatastoreConstants' )->setQueryString( array( 'store_method' => \IPS\Request::i()->store_method, 'store_config' => \IPS\Request::i()->store_config, 'cache_method' => \IPS\Request::i()->cache_method, 'cache_config' => \IPS\Request::i()->cache_config, 'cache_guest_page' => \IPS\Request::i()->cache_guest_page ) );
				$checkUrl = \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore&checkConstants=1' )->setQueryString( array( 'store_method' => \IPS\Request::i()->store_method, 'store_config' => \IPS\Request::i()->store_config, 'cache_method' => \IPS\Request::i()->cache_method, 'cache_config' => \IPS\Request::i()->cache_config, 'cache_guest_page' => \IPS\Request::i()->cache_guest_page ) );
				return \IPS\Theme::i()->getTemplate( 'settings' )->dataStoreChange( $downloadUrl, $checkUrl, TRUE );
			}
			/* Otherwise just log and redirect */
			else
			{
				/* Clear it */
				\IPS\Data\Cache::i()->clearAll();
				\IPS\Data\Store::i()->clearAll();
				
				/* Enable/disable the clearcaches task */
				\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => intval( \IPS\CACHE_METHOD === 'None' and \IPS\CACHE_PAGE_TIMEOUT ) ), "`key`='clearcache'" );
				
				/* Log and redirect */
				\IPS\Session::i()->log( 'acplogs__datastore_settings_updated' );
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore' ), 'saved' );
			}
		}
		
		/* Check permission */
		\IPS\Dispatcher::i()->checkAcpPermission( 'datastore' );
		
		/* Init */
		$form = new \IPS\Helpers\Form;

		/* Cold storage */
		$extra = array();
		$toggles = array();
		$disabled = array();
		$storeConfigurationFields = array();
		$options = array(
			'FileSystem'	=> 'datastore_method_FileSystem',
			'Database'		=> 'datastore_method_Database',
		);
		$existingConfiguration = json_decode( \IPS\STORE_CONFIG, TRUE );
		foreach ( $options as $k => $v )
		{
			$class = 'IPS\Data\Store\\' . $k;
			if ( !$class::supported() )
			{
				$disabled[] = $k;
				\IPS\Member::loggedIn()->language()->words["datastore_method_{$k}_desc"] = \IPS\Member::loggedIn()->language()->addToStack('datastore_method_disableddesc', FALSE, array( 'sprintf' => array( $k ) ) );
			}
			else
			{
				foreach ( $class::configuration( $k === \IPS\STORE_METHOD ? $existingConfiguration : array() ) as $inputKey => $input )
				{
					if ( !$input->htmlId )
					{
						$input->htmlId = md5( uniqid() );
					}
					
					$extra[] = $input;
					$toggles[ $k ][] = $input->htmlId;
					$storeConfigurationFields[ $k ][ $inputKey ] = $input->name;
				}
			}
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'datastore_method', \IPS\STORE_METHOD, TRUE, array(
			'options'	=> $options,
			'toggles'	=> $toggles,
			'disabled'	=> $disabled,
		) ) );
		foreach ( $extra as $input )
		{
			$form->add( $input );
		}

		/* Cache */
		$extra = array();
		$toggles = array();
		$disabled = array();
		$cacheConfigurationFields = array();
		$options = array(
			'None'			=> 'datastore_method_None',
			'Apc'			=> 'datastore_method_Apc',
			'Memcache'		=> 'datastore_method_Memcache',
			'Redis'			=> 'datastore_method_Redis',
			'Wincache'		=> 'datastore_method_Wincache',
			'Xcache'		=> 'datastore_method_Xcache',
		);
		if ( \IPS\TEST_CACHING )
		{
			$options['Test'] = 'datastore_method_Test';
		}
		$existingConfiguration = json_decode( \IPS\CACHE_CONFIG, TRUE );
		foreach ( $options as $k => $v )
		{
			$class = 'IPS\Data\Cache\\' . $k;
			if ( !$class::supported() )
			{
				$disabled[] = $k;
				\IPS\Member::loggedIn()->language()->words["datastore_method_{$k}_desc"] = \IPS\Member::loggedIn()->language()->addToStack('datastore_method_disableddesc', FALSE, array( 'sprintf' => array( $k ) ) );
			}
			else
			{				
				foreach ( $class::configuration( $k === \IPS\CACHE_METHOD ? $existingConfiguration : array() ) as $inputKey => $input )
				{
					if ( !$input->htmlId )
					{
						$input->htmlId = md5( uniqid() );
					}
					
					$extra[] = $input;
					$toggles[ $k ][] = $input->htmlId;
					$cacheConfigurationFields[ $k ][ $inputKey ] = $input->name;
				}
			}
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'cache_method', \IPS\CACHE_METHOD, TRUE, array(
			'options'	=> $options,
			'toggles'	=> $toggles,
			'disabled'	=> $disabled,
		) ) );
		foreach ( $extra as $input )
		{
			$form->add( $input );
		}
		$form->add( new \IPS\Helpers\Form\Number( 'cache_guest_page', \IPS\CACHE_PAGE_TIMEOUT, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'disable' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('for'), \IPS\Member::loggedIn()->language()->addToStack('seconds'), 'cache_guest_page' ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Work out configuration */
			$storeConfiguration = array();
			if ( isset( $storeConfigurationFields[ $values['datastore_method'] ] ) )
			{
				foreach ( $storeConfigurationFields[ $values['datastore_method'] ] as $k => $fieldName )
				{
					$storeConfiguration[ $k ] = $values[ $fieldName ];
				}
			}
			$cacheConfiguration = array();
			if ( isset( $cacheConfigurationFields[ $values['cache_method'] ] ) )
			{
				foreach ( $cacheConfigurationFields[ $values['cache_method'] ] as $k => $fieldName )
				{
					$cacheConfiguration[ $k ] = $values[ $fieldName ];
				}
			}
			
			/* If we've changed anything, explain to the admin they have to update */
			if ( $values['datastore_method'] !== \IPS\STORE_METHOD or str_replace( '\\/', '/', json_encode( $storeConfiguration ) ) !== \IPS\STORE_CONFIG or $values['cache_method'] !== \IPS\CACHE_METHOD or json_encode( $cacheConfiguration ) !== \IPS\CACHE_CONFIG or $values['cache_guest_page'] !== \IPS\CACHE_PAGE_TIMEOUT )
			{
				/* Connect to cache engine if we can and invalidate any existing caches */
				try
				{
					$classname = 'IPS\Data\Cache\\' . $values['cache_method'];
					
					if ( $classname::supported() )
					{
						$instance = new $classname( $cacheConfiguration );
						$instance->clearAll();
					}
				}
				catch( \Exception $e ){}
				
				/* Enable/disable the clearcaches task */
				\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => intval( $values['cache_method'] === 'None' and $values['cache_guest_page'] ) ), "`key`='clearcache'" );
				
				/* Display */
				$downloadUrl = \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=downloadDatastoreConstants' )->setQueryString( array( 'store_method' => $values['datastore_method'], 'store_config' => str_replace( '\\/', '/', json_encode( $storeConfiguration ) ), 'cache_method' => $values['cache_method'], 'cache_config' => json_encode( $cacheConfiguration ), 'cache_guest_page' => $values['cache_guest_page'] ) );
				$checkUrl = \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore&checkConstants=1' )->setQueryString( array( 'store_method' => $values['datastore_method'], 'store_config' => str_replace( '\\/', '/', json_encode( $storeConfiguration ) ), 'cache_method' => $values['cache_method'], 'cache_config' => json_encode( $cacheConfiguration ), 'cache_guest_page' => $values['cache_guest_page'] ) );
				return \IPS\Theme::i()->getTemplate( 'settings' )->dataStoreChange( $downloadUrl, $checkUrl );
			}
			/* Otherwise just log and redirect */
			else
			{
				\IPS\Session::i()->log( 'acplogs__datastore_settings_updated' );
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=datastore' ), 'saved' );
			}
		}

		return $form;
	}
	
	/**
	 * Download constants.php
	 *
	 * @return	void
	 */
	protected function downloadDatastoreConstants()
	{
		$output = "<?php\n\n";
		foreach ( \IPS\IPS::defaultConstants() as $k => $v )
		{
			$val = constant( 'IPS\\' . $k );
			if ( $val !== $v and !in_array( $k, array( 'STORE_METHOD', 'STORE_CONFIG', 'CACHE_METHOD', 'CACHE_CONFIG', 'CACHE_PAGE_TIMEOUT', 'SUITE_UNIQUE_KEY' ) ) )
			{
				$output .= "define( '{$k}', " . var_export( $val, TRUE ) . " );\n";
			}
		}
		
		$output .= "\n";
		$output .= "define( 'STORE_METHOD', " . var_export( \IPS\Request::i()->store_method, TRUE ) . " );\n";
		$output .= "define( 'STORE_CONFIG', " . var_export( \IPS\Request::i()->store_config, TRUE ) . " );\n";
		$output .= "define( 'CACHE_METHOD', " . var_export( \IPS\Request::i()->cache_method, TRUE ) . " );\n";
		$output .= "define( 'CACHE_CONFIG', " . var_export( \IPS\Request::i()->cache_config, TRUE ) . " );\n";
		$output .= "define( 'CACHE_PAGE_TIMEOUT', " . var_export( (int) \IPS\Request::i()->cache_guest_page, TRUE ) . " );\n";
		$output .= "define( 'SUITE_UNIQUE_KEY', " . var_export( mb_substr( md5( uniqid() ), 10, 10 ), TRUE ) . " );\n"; // Regenerate the unique key so there's no conflicts
		$output .= "\n\n\n";
		
		\IPS\Output::i()->sendOutput( $output, 200, 'text/x-php', array( 'Content-Disposition' => 'attachment; filename=constants.php' ) );
	}
	
	/**
	 * Settings
	 *
	 * @return	string
	 */
	protected function _manageSettings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_server' );
		
		/* Generate a cron key if we don't have one */
		if ( !\IPS\Settings::i()->task_cron_key )
		{
			\IPS\Settings::i()->task_cron_key = md5( uniqid() );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Settings::i()->task_cron_key ), array( 'conf_key=?', 'task_cron_key' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
		
		/* Sort stuff out for the cron setting */
		\IPS\Member::loggedIn()->language()->words['task_method_cron_warning'] = sprintf( \IPS\Member::loggedIn()->language()->get( 'task_method_cron_warning', FALSE ), PHP_BINDIR . '/php -d memory_limit=-1 -d max_execution_time=0 ' . \IPS\ROOT_PATH . '/applications/core/interface/task/task.php ' . \IPS\Settings::i()->task_cron_key );
		\IPS\Member::loggedIn()->language()->words['task_method_web_warning'] = sprintf( \IPS\Member::loggedIn()->language()->get( 'task_method_web_warning', FALSE ), \IPS\Http\Url::internal( 'applications/core/interface/task/web.php?key=' . \IPS\Settings::i()->task_cron_key, 'none' ) );
		$options = array( 
			'options'	=> array(
				'normal'	=> 'task_method_normal',
				'cron'		=> 'task_method_cron',
				'web'		=> 'task_method_web',
			),
			'toggles' => array( 
				'cron' => array( 'task_use_cron_cron_warning' ), 
				'web' => array( 'task_use_cron_web_warning' )
			) 
		);

		/* Build and show form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Radio( 'task_use_cron', \IPS\Settings::i()->task_use_cron, FALSE, $options, function ( $val )
		{
			$cronFile = \IPS\ROOT_PATH . '/applications/core/interface/task/task.php';
			if ( $val == 'cron' and ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) !== 'WIN' AND !is_executable( $cronFile ) ) )
			{
				throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack('task_use_cron_executable', FALSE, array( 'sprintf' => array( $cronFile ) ) ) );
			}
		}, NULL, NULL, 'task_use_cron' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'widget_cache_ttl', ( isset( \IPS\Settings::i()->widget_cache_ttl ) ) ? \IPS\Settings::i()->widget_cache_ttl : 60, FALSE, array( 'min' => 60 ), NULL, \IPS\Member::loggedIn()->language()->addToStack('for'), \IPS\Member::loggedIn()->language()->addToStack('seconds') ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'auto_polling_enabled', \IPS\Settings::i()->auto_polling_enabled, FALSE ) );
				
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();			
			\IPS\Session::i()->log( 'acplogs__advanced_server_edited' );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=settings' ), 'saved' );
		}
		return $form;
	}
	
	/**
	 * Tasks
	 *
	 * @return	void
	 */
	protected function tasks()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		
		$table = new \IPS\Helpers\Table\Db( 'core_tasks', \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ) );
		$table->langPrefix = 'task_manager_';
		$table->include = array( 'app', 'key', 'frequency', 'next_run' );
		$table->mainColumn = 'key';
		$table->quickSearch = 'key';
		$table->sortBy = $table->sortBy ?: 'next_run';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->quickSearch = function( $val )
		{
			$matches = \IPS\Member::loggedIn()->language()->searchCustom( 'task__', $val, TRUE );
			if ( count( $matches ) )
			{
				return \IPS\Db::i()->in( '`key`', array_keys( $matches ) );
			}
			else
			{
				return '1=0';
			}
		};
		
		$table->parsers = array(
			'app'	=> function( $val, $row )
			{
				try
				{
					return $val ? \IPS\Application::load( $val )->_title : \IPS\Plugin::load( $row['plugin'] )->name;
				}
				catch ( \OutOfRangeException $e )
				{
					return NULL;
				}
			},
			'key'	=> function( $val )
			{
				$langKey = 'task__' . $val;
				if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $langKey ) )
				{
					return $val . '<br><span class="ipsType_light">' . \IPS\Member::loggedIn()->language()->addToStack( $langKey ) . '</span>';
				}
				return $val;
			},
			'frequency' => function ( $v )
			{
				$interval = new \DateInterval( $v );
				$return = array();
				foreach ( array( 'y' => 'years', 'm' => 'months', 'd' => 'days', 'h' => 'hours', 'i' => 'minutes', 's' => 'seconds' ) as $k => $v )
				{
					if ( $interval->$k )
					{
						$return[] = \IPS\Member::loggedIn()->language()->addToStack( 'every_x_' . $v, FALSE, array( 'pluralize' => array( $interval->format( '%' . $k ) ) ) );
					}
				}
				
				return \IPS\Member::loggedIn()->language()->formatList( $return );
			},
			'next_run' => function ( $v, $row )
			{
				if ( !$row['enabled'] )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('task_manager_disabled');
				}
				elseif ( $row['running'] )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('task_manager_running');
				}
				else
				{
					return (string) \IPS\DateTime::ts( $row['next_run'] ?: time() );
				}
			}
		);
		
		$table->rowButtons = function( $row )
		{
			if ( $row['running'] )
			{
				$return = array( 'unlock' => array(
					'icon'	=> 'unlock',
					'title'	=> 'task_manager_unlock',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=unlockTask&id={$row['id']}" )
				) );
			}
			else
			{
				$return = array( 'run' => array(
					'icon'	=> 'play-circle',
					'title'	=> 'task_manager_run',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=runTask&id={$row['id']}" )
				) );
			}
			$return['logs'] = array(
				'icon'	=> 'search',
				'title'	=> 'task_manager_logs',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=taskLogs&id={$row['id']}" )
			);
			return $return;
		};
		
		/* Add a button for settings */
		\IPS\Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
						'title'		=> 'settings',
						'icon'		=> 'cog',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=taskSettings' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('settings') )
				),
		);
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('task_manager');
		\IPS\Output::i()->output = (string) $table;
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function taskSettings()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Number( 'prune_log_tasks', \IPS\Settings::i()->prune_log_tasks, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days'), 'prune_log_tasks' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__tasklog_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), 'saved' );
		}
	
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('task_settings');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate('global')->block( 'task_settings', $form, FALSE );
	}
	
	/**
	 * Run Task
	 *
	 * @return	void
	 */
	protected function runTask()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('task_manager');
		
		try
		{
			$task = \IPS\Task::load( \IPS\Request::i()->id );
			if ( $task->running and !\IPS\IN_DEV )
			{
				\IPS\Output::i()->error( 'task_manager_locked', '2C124/2', 403, '' );
			}
			
			$output = $task->run();
			
			if ( $output === NULL )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), 'task_manager_ran' );
			}
			else
			{
				if ( is_array( $output ) )
				{
					$output = implode( "\n", array_map( array( \IPS\Member::loggedIn()->language(), 'addToStack' ), $output ) );
				}
				elseif ( !is_string( $output ) and !is_numeric( $output ) )
				{
					$output = var_export( $output, TRUE );
				}
				else
				{
					$output = \IPS\Member::loggedIn()->language()->addToStack( $output, FALSE );
				}
				
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'advancedsettings' )->taskResult( TRUE, $output, $task->id );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C124/1', 404, '' );
		}
		catch ( \IPS\Task\Exception $e )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'advancedsettings' )->taskResult( FALSE, \IPS\Member::loggedIn()->language()->addToStack( $e->getMessage(), FALSE ), $task->id );
		}
	}
	
	/**
	 * Unlock Task
	 *
	 * @return	void
	 */
	protected function unlockTask()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		
		try
		{
			\IPS\Task::load( \IPS\Request::i()->id )->unlock();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=tasks' ), 'task_manager_unlocked' );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C124/3', 404, '' );
		}
	}
	
	/**
	 * View task logs
	 *
	 * @return	void
	 */
	protected function taskLogs()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_tasks' );
		
		try
		{
			$task = \IPS\Task::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C124/4', 404, '' );
		}
		
		$table = new \IPS\Helpers\Table\Db( 'core_tasks_log', \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=taskLogs&id={$task->id}" ), array( 'task=?', $task->id ) );
		$table->langPrefix = 'task_manager_';
		
		$table->include = array( 'time', 'log' );
		$table->parsers = array(
			'time'	=> function( $val )
			{
				return (string) \IPS\DateTime::ts( $val );
			},
			'log'	=> function ( $val, $row )
			{
				$val = json_decode( $val );
				if ( is_array( $val ) )
				{
					$val = implode( "\n", array_map( array( \IPS\Member::loggedIn()->language(), 'addToStack' ), $val ) );
				}
				elseif ( !is_string( $val ) and !is_numeric( $val ) )
				{
					$val = var_export( $val, TRUE );
				}
				else
				{
					$val = \IPS\Member::loggedIn()->language()->addToStack( $val, FALSE );
				}
				return $row['error'] ? \IPS\Theme::i()->getTemplate( 'global' )->message( $val, 'error' ) : $val;
			}
		);
		
		$table->sortBy = $table->sortBy ?: 'time';
		
		$table->quickSearch = 'log';
		$table->advancedSearch = array(
			'time'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'log'	=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT
		);

		\IPS\Output::i()->title = $task->key;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->message( 'tasklogs_blurb', 'info' ) . $table;
	}
	
	/**
	 * FURLs
	 *
	 * @return string
	 */
	protected function _manageFurl()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );
		
		if ( \IPS\IN_DEV and !\IPS\DEV_USE_FURL_CACHE )
		{
			\IPS\Output::i()->error( 'furl_in_dev', '1C124/5', 403, '' );
		}

		$definition = \IPS\Http\Url::furlDefinition();
		
		$table = new \IPS\Helpers\Table\Custom( $definition, \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ) );
		$table->include = array( 'friendly', 'real' );
		$table->limit   = 100;
		$table->langPrefix = 'furl_';
		$table->mainColumn = 'real';
		$table->parsers = array(
			'friendly'	=> function( $val )
			{
				$val = preg_replace( '/{[@#](.+?)}/', '<strong><em>$1</em></strong>', $val );
				$val = preg_replace( '/{\?(\d+?)?}/', '<em>??</em>', $val );
				return "<span class='ipsType_light ipsResponsive_hideTablet'>" . \IPS\Settings::i()->base_url . ( \IPS\Settings::i()->htaccess_mod_rewrite ? '' : 'index.php?/' ) . "</span>{$val}";
			},
			'real' => function( $val, $row )
			{
				preg_match_all( '/{([@#])(.+?)}/', $row['friendly'], $matches );
				if ( !empty( $matches[0] ) )
				{
					foreach ( $matches[0] as $i => $m )
					{
						$val .= '&' . $matches[ 2 ][ $i ] . '=<strong><em>' . ( $matches[ 1 ][ $i ] == '#' ? '123' : 'abc' ) . '</em></strong>';
					}
					$val .= '</strong>';
				}
				
				return "<span class='ipsType_light ipsResponsive_hideTablet'>" . \IPS\Settings::i()->base_url . "index.php?</span>{$val}";
			}
		);
		$table->quickSearch = 'friendly';
		$table->advancedSearch = array(
			'friendly'	=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'real'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT
		);
		
		$table->rootButtons = array(
			'add'		=> array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=furlForm' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add') )
			)
		);

		if( \IPS\Settings::i()->furl_configuration AND $config = json_decode( \IPS\Settings::i()->furl_configuration, TRUE ) AND count( $config ) )
		{
			$table->rootButtons['revert'] = array(
				'icon'	=> 'undo',
				'title'	=> 'furl_revert',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&do=furlRevert' ),
				'data'	=> array( 'confirm' => '' )
			);
		}

		$table->rowButtons = function( $row, $k ) use ( $definition )
		{
			$return = array(
				'edit'	=> array(
					'icon'	=> 'pencil',
					'title'	=> 'edit',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=furlForm&key={$k}" ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit') )
				)
			);

			if( isset( $definition[ $k ]['custom'] ) )
			{
				$return['revert'] = array(
					'icon'	=> 'undo',
					'title'	=> 'revert',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=settings&controller=advanced&do=furlDelete&key={$k}" ),
					'data'	=> array( 'confirm' => '', 'confirmMessage' => \IPS\Member::loggedIn()->language()->addToStack('revert_confirm') )
				);
			}

			return $return;
		};

		return ( \IPS\Request::i()->advancedSearchForm ? '' : \IPS\Theme::i()->getTemplate('global')->message( 'furl_warning', 'warning' ) ) . $table;
	}
	
	/**
	 * Add/Edit FURL
	 *
	 * @return	void
	 */
	protected function furlForm()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );
		
		$current	= NULL;
		$config		= \IPS\Http\Url::furlDefinition();
		if ( \IPS\Request::i()->key )
		{
			$current = ( isset( $config[ \IPS\Request::i()->key ] ) ) ? $config[ \IPS\Request::i()->key ] : NULL;
		}

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'furl_friendly', $current ? $current['friendly'] : '', FALSE, array(), NULL, \IPS\Settings::i()->base_url . ( \IPS\Settings::i()->htaccess_mod_rewrite ? '' : 'index.php?/' ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'furl_real', $current ? $current['real'] : '', FALSE, array(), NULL, \IPS\Settings::i()->base_url . 'index.php?' ) );
		
		if ( $values = $form->values() )
		{
			$furl = \IPS\Settings::i()->furl_configuration ? json_decode( \IPS\Settings::i()->furl_configuration, TRUE ) : array();
			
			$currentDefinition = \IPS\Http\Url::furlDefinition();
			$save = \IPS\Request::i()->key ? $currentDefinition[ \IPS\Request::i()->key ] : array();
			
			$save['friendly'] = $values['furl_friendly'];
			$save['real'] = $values['furl_real'];
			$save['custom'] = true;
						
			if ( \IPS\Request::i()->key )
			{
				$furl[ \IPS\Request::i()->key ] = $save;
			}
			else
			{
				$key = 'key' . ( count( $furl ) + 1 );
				$furl[ $key ] = $save;
			}
			
			\IPS\Session::i()->log( 'acplogs__advanced_furl_edited' );
			
			$newValue = json_encode( $furl );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $newValue ), array( 'conf_key=?', 'furl_configuration' ) );
			unset( \IPS\Data\Store::i()->settings );
			
			/* Clear Sidebar Caches */
			\IPS\Widget::deleteCaches();

			/* Clear create menu caches */
			\IPS\Member::clearCreateMenu();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ), 'saved' );
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Delete FURL
	 *
	 * @return	void
	 */
	protected function furlDelete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		$furlDefinition = \IPS\Settings::i()->furl_configuration ? json_decode( \IPS\Settings::i()->furl_configuration, TRUE ) : array();
		if( isset( $furlDefinition[ \IPS\Request::i()->key ] ) )
		{
			unset( $furlDefinition[ \IPS\Request::i()->key ] );
			$newValue = json_encode( $furlDefinition );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $newValue ), array( 'conf_key=?', 'furl_configuration' ) );
			unset( \IPS\Data\Store::i()->settings );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();
		}
		
		\IPS\Session::i()->log( 'acplogs__advanced_furl_deleted' );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ), 'saved' );
	}
	
	/**
	 * Revert FURL customisation
	 *
	 * @return	void
	 */
	protected function furlRevert()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advanced_manage_furls' );

		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => NULL ), array( 'conf_key=?', 'furl_configuration' ) );
		unset( \IPS\Data\Store::i()->settings );
		unset( \IPS\Data\Store::i()->furl_configuration );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();
		
		\IPS\Session::i()->log( 'acplogs__advanced_furl_reverted' );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=advanced&tab=furl' ), 'saved' );
	}
}