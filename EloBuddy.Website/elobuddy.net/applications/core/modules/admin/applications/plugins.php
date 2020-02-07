<?php
/**
 * @brief		Plugins
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jul 2013
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
 * Plugins
 */
class _plugins extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\Plugin';
		
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'plugins_view' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( \IPS\Settings::i()->disable_all_plugins )
		{
			\IPS\Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'undo',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=reenableAll' ),
				'title'	=> 'plugins_reenable_all',
			);
		}
		else
		{
			\IPS\Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'times',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=disableAll' ),
				'title'	=> 'plugins_disable_all',
			);
		}
		
		parent::manage();
	}
	
	/**
	 * Disable All
	 *
	 * @return	void
	 */
	protected function disableAll()
	{
		$disabledPlugins = array();
		
		foreach ( \IPS\Plugin::plugins() as $plugin )
		{
			if ( $plugin->enabled )
			{
				$plugin->enabled = FALSE;
				$plugin->save();
				
				$disabledPlugins[] = $plugin->id;
			}
		}
		
		\IPS\Settings::i()->disable_all_plugins = implode( ',', $disabledPlugins );
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Settings::i()->disable_all_plugins ), array( 'conf_key=?', 'disable_all_plugins' ) );
		unset( \IPS\Data\Store::i()->settings );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins' ) );
	}

	
	/**
	 * Re-enable All
	 *
	 * @return	void
	 */
	protected function reenableAll()
	{
		foreach ( explode( ',', \IPS\Settings::i()->disable_all_plugins ) as $plugin )
		{			
			try
			{
				$plugin = \IPS\Plugin::load( $plugin );
				$plugin->enabled = TRUE;
				$plugin->save();
			}
			catch ( \Exception $e ) {}
		}
		
		\IPS\Settings::i()->disable_all_plugins = '';
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_key=?', 'disable_all_plugins' ) );
		unset( \IPS\Data\Store::i()->settings );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins' ) );
	}

	/**
	 * Toggle Enabled/Disable
	 *
	 * @return	void
	 */
	protected function enableToggle()
	{
		if ( \IPS\NO_WRITES )
		{
			\IPS\Output::i()->error( 'no_writes', '1C145/G', 403, '' );
		}
		
		/* Remove plugin.js so it can be rebuilt with only active plugins */
		\IPS\Output\Javascript::deleteCompiled( 'core', 'plugins', 'plugins.js' );
		
		return parent::enableToggle();
	}

	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$buttons = parent::_getRootButtons();
		
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'plugins_install' ) and !\IPS\IN_DEV )
 		{
			$buttons['install']  = array(
				'icon'	=> 'upload',
				'title'	=> 'install_new_plugin',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=install" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('install') )
				);
		}
		return $buttons;
	}
	
	/**
	 * Install Form
	 *
	 * @return	void
	 */
	public function install()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'plugins_install' );
		
		if ( \IPS\NO_WRITES )
		{
			\IPS\Output::i()->error( 'no_writes', '1C145/1', 403, '' );
		}
		if( !is_writable( \IPS\ROOT_PATH . "/plugins/" ) )
		{
			\IPS\Output::i()->error( 'plugin_dir_not_write', '4C145/2', 403, '' );
		}

		/* Build form */
		$form = new \IPS\Helpers\Form( NULL, 'install' );
		if ( isset( \IPS\Request::i()->id ) )
		{
			$form->hiddenValues['id'] = \IPS\Request::i()->id;
		}
		$form->add( new \IPS\Helpers\Form\Upload( 'plugin_upload', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );
		$activeTabContents = $form;
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Already installed? */
			$xml = new \XMLReader;
			$xml->open( $values['plugin_upload'] );
			if ( !@$xml->read() )
			{
				\IPS\Output::i()->error( 'xml_upload_invalid', '2C145/D', 403, '' );
			}
			
			if ( !isset( \IPS\Request::i()->id ) )
			{
				try
				{
					$id = \IPS\Db::i()->select( 'plugin_id', 'core_plugins', array( 'plugin_name=? AND plugin_author=?', $xml->getAttribute('name'), $xml->getAttribute('author') ) )->first();
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'plugin_already_installed', FALSE, array( 'sprintf' => array( (string) \IPS\Http\Url::internal("app=core&module=applications&controller=plugins&do=install&id={$id}") ) ) ), '1C145/F', 403, '' );
				}
				catch ( \UnderflowException $e ) { }
			}

			/* Move it to a temporary location */
			$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['plugin_upload'], $tempFile );
											
			/* Initate a redirector */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=doInstall&file=' . urlencode( $tempFile ) . '&key=' . md5_file( $tempFile ) . ( isset( \IPS\Request::i()->id ) ? '&id=' . \IPS\Request::i()->id : '' ) ) );
		}
		
		/* Display */
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Install
	 *
	 * @return	void
	 */
	public function doInstall()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'plugins_install' );

		if ( !file_exists( \IPS\Request::i()->file ) or md5_file( \IPS\Request::i()->file ) !== \IPS\Request::i()->key )
		{
			\IPS\Output::i()->error( 'generic_error', '3C145/3', 500, '' );
		}
		
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=doInstall&file=' . urlencode( \IPS\Request::i()->file ) . '&key=' .  \IPS\Request::i()->key . '&id=' . \IPS\Request::i()->id ),
			function( $data )
			{
				/* Open XML file */
				$xml = new \XMLReader;
				$xml->open( \IPS\Request::i()->file );
				$new = FALSE;
				
				$xml->read();
				$version = $xml->getAttribute('version');
				
				/* Initial insert */
				if ( !is_array( $data ) )
				{
					if( !$xml->getAttribute('name') )
					{
						\IPS\Output::i()->error( 'xml_upload_invalid', '2C145/E', 403, '' );
					}

					if ( isset( \IPS\Request::i()->id ) )
					{
						try
						{
							$plugin = \IPS\Plugin::load( \IPS\Request::i()->id );

							/* Disable the plugin to prevent errors if core classes are extended */
							$plugin->enabled = FALSE;
							$plugin->save();

							/* If we're upgrading, remove current HTML, CSS, etc. We'll insert again in a moment */
							\IPS\Theme::removeTemplates( 'core', 'global', 'plugins', $plugin->id );
							\IPS\Theme::removeCss( 'core', 'front', 'custom', $plugin->id );
							\IPS\Theme::removeResources( 'core', 'global', 'plugins', $plugin->id );
						}
						catch ( \OutOfRangeException $e )
						{
							$plugin = new \IPS\Plugin;
							$new = TRUE;
						}
					}
					else
					{
						$plugin = new \IPS\Plugin;
						$new = TRUE;
					}

					$currentVersionId = $plugin->version_long;

					$plugin->name = $xml->getAttribute('name');
					$plugin->update_check = $xml->getAttribute('update_check');
					$plugin->author = $xml->getAttribute('author');
					$plugin->website = $xml->getAttribute('website');

					if ( !$plugin->location )
					{
						$directory = \mb_strtolower( preg_replace( '#[^a-zA-Z0-9_]#', '', $plugin->name ) );
						$plugin->location = file_exists( \IPS\ROOT_PATH . "/plugins/" . $directory ) ? 'p' . mb_substr( md5( uniqid() ), 0, 10 ) : $directory;
					}

					$plugin->version_long = $xml->getAttribute('version_long');
					$plugin->version_human = $xml->getAttribute('version_human');
					$plugin->save();
					
					if ( !file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}" ) )
					{
						\mkdir( \IPS\ROOT_PATH . "/plugins/{$plugin->location}" );
						chmod( \IPS\ROOT_PATH . "/plugins/{$plugin->location}", \IPS\IPS_FOLDER_PERMISSION );
					}
					\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/index.html", '' );
					
					return array( array(
						'id'               => $plugin->id,
						'currentVersionId' => $currentVersionId,
						'storeKeys'        => array(),
						'setUpClasses'     => array(),
						'step'             => 0,
						'upgradeData'      => array(),
						'done'             => array(),
						'isNew'            => $new
					), \IPS\Member::loggedIn()->language()->addToStack('processing') );
				}
				
				/* Load plugin */
				$plugin = \IPS\Plugin::load( $data['id'] );
				
				/* Skip to whatever we're doing */
				$xml->read();
				while ( TRUE )
				{
					if ( !in_array( $xml->name, $data['done'] ) )
					{
						/* What are we doing? */
						$step = $xml->name;
						switch ( $step )
						{
							case 'plugin':
								break 2;
							
							/* Hooks */
							case 'hooks':
								
								/* Make the directory, or if we're upgrading, empty it */
								if ( !file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks" ) )
								{
									\mkdir( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks" );
									chmod( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks", \IPS\IPS_FOLDER_PERMISSION );
								}
								else
								{
									foreach ( \IPS\Db::i()->select( 'class', 'core_hooks', array( 'plugin=? AND type=?', $plugin->id, 'S' ) ) as $class )
									{
										$data['recompileTemplates'][ $class ] = $class;
									}
									\IPS\Db::i()->delete( 'core_hooks', array( 'plugin=?', $plugin->id ) );
									foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks" ) as $file )
									{
										if ( !$file->isDot() )
										{
											unlink( $file->getPathname() );
										}
									}
									\IPS\Plugin\Hook::writeDataFile();
								}
								
								/* Loop hooks */
								while ( $xml->read() and $xml->name == 'hook' )
								{
									/* Make up a filename */
								  	$filename = $xml->getAttribute( 'filename' ) ?: md5( uniqid() . mt_rand() );
								  	
								  	/* Insert into DB */
								  	$insertId = \IPS\Db::i()->insert( 'core_hooks', array( 'plugin' => $plugin->id, 'type' => $xml->getAttribute('type'), 'class' => $xml->getAttribute('class'), 'filename' => $filename ) );
								  	
								   	/* Write contents */
								  	\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks/{$filename}.php", preg_replace( '/class hook(\d+?) extends _HOOK_CLASS_/', "class hook{$insertId} extends _HOOK_CLASS_", $xml->readString() ) );
								  	
								  	/* If that was a skin hook, trash our compiled version of that template */
								  	if ( $xml->getAttribute('type') == 'S' )
								  	{
								  		$class = $xml->getAttribute('class');
								  		$data['recompileTemplates'][ $class ] = $class;
								  	}
								  								
									/* Move onto next */
									$xml->read();
									$xml->next();
									
								}
								break;
								
							/* Settings */
							case 'settings':
								
								$inserts = array();
								while ( $xml->read() and $xml->name == 'setting' )
								{
									$xml->read();
									$key = $xml->readString();
									$xml->next();
									$value = $xml->readString();
									
									if( isset( \IPS\Settings::i()->$key ) )
									{
										\IPS\Db::i()->update( 'core_sys_conf_settings', array(
											'conf_default'	=> $value,
											'conf_plugin'	=> $plugin->id
										), array( 'conf_key=?', $key ) );
									}
									else
									{
										$inserts[] = array(
											'conf_key'		=> $key,
											'conf_value'	=> $value,
											'conf_default'	=> $value,
											'conf_plugin'	=> $plugin->id
										);
									}
									
									$xml->next();
								}
								
								if( count( $inserts ) )
								{
									\IPS\Db::i()->insert( 'core_sys_conf_settings', $inserts , TRUE );
								}
								
								unset( \IPS\Data\Store::i()->settings );
								break;
								
							/* Settings code */
							case 'settingsCode':
								\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/settings.php", $xml->readString() );
								break;
								
							/* Tasks */
							case 'tasks':
								
								/* Make the directory */
								if ( !file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/tasks" ) )
								{
									\mkdir( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/tasks" );
									chmod( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/tasks", \IPS\IPS_FOLDER_PERMISSION );
								}
								
								/* Loop tasks */
								while ( $xml->read() and $xml->name == 'task' )
								{
									$key = $xml->getAttribute('key');
									
								  	/* Insert into DB */
								  	try
								  	{
									  	$task = \IPS\Task::load( $key, 'key', array( 'plugin=?', $plugin->id ) );
								  	}
								  	catch ( \OutOfRangeException $e )
								  	{
									  	$task = new \IPS\Task;
									}
								  	$task->plugin = $plugin->id;
								  	$task->key = $key;
								  	$task->frequency = $xml->getAttribute('frequency');
								  	$task->save();
								  									  	
								  	/* Write contents */
								  	\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/tasks/{$key}.php", $xml->readString() );
							
									/* Move onto next */
									$xml->read();
									$xml->next();
								}
								
								break;
								
							/* Files */
							case 'htmlFiles':
							case 'cssFiles':
							case 'jsFiles':
							case 'resourcesFiles':
								$class = ( \IPS\Theme::designersModeEnabled() ) ? '\IPS\Theme\Advanced\Theme' : '\IPS\Theme';
								
								while ( $xml->read() and in_array( $xml->name, array( 'html', 'css', 'js', 'resources' ) ) )
								{
									switch ( $xml->name )
									{
										case 'html':
											$name = $xml->getAttribute('filename');
											$content = base64_decode( $xml->readString() );

											preg_match('/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', $content, $matches );
											$output = preg_replace( '/^<ips:template parameters="(.+?)?"([^>]+?)>(\r\n?|\n)/', '', $content );

											$class::addTemplate( array(
												'app'		=> 'core',
												'location'	=> 'global',
												'group'		=> 'plugins',
												'name'		=> mb_substr( $name, 0, -6 ),
												'variables'	=> $matches[1],
												'content'	=> $output,
												'plugin'	=> $plugin->id,
												'_default_template' => TRUE
											), TRUE );
											
											break;
											
										case 'css':
											$class::addCss( array(
												'app'		=> 'core',
												'location'	=> 'front',
												'path'		=> 'custom',
												'name'		=> $xml->getAttribute('filename'),
												'content'	=> base64_decode( $xml->readString() ),
												'plugin'	=> $plugin->id
											), TRUE );
																						
											break;
											
										case 'js':
											$name = $xml->getAttribute('filename');
											try
											{
												$js = \IPS\Output\Javascript::find( 'core', 'plugins', '/', $name );
												$js->delete();
											}
											catch ( \OutOfRangeException $e ) {}

											$js = new \IPS\Output\Javascript;
											$js->plugin  = $plugin->id;
											$js->name    = $name;
											$js->content = base64_decode( $xml->readString() );
											$js->version = $plugin->version_long;
											$js->save();
											
											break;
											
										case 'resources':
											$class::addResource( array(
												'app'		=> 'core',
												'location'	=> 'global',
												'path'		=> '/plugins/',
												'name'		=> $xml->getAttribute('filename'),
												'content'	=> base64_decode( $xml->readString() ),
												'plugin'	=> $plugin->id
											) );
											break;
									}

									$xml->read();
									$xml->next();
								}
							
								break;
								
							/* Lang */
							case 'lang':
								/* Fetch existing language keys */
								$existingLanguageKeys = iterator_to_array( \IPS\Db::i()->select( 'word_key', 'core_sys_lang_words', array( 'word_plugin=? and lang_id=?', $plugin->id, \IPS\Lang::defaultLanguage() ) ) );
								$keysToDelete         = $existingLanguageKeys;
								$inserts = array();
								while ( $xml->read() and $xml->name == 'word' )
								{
									$key = $xml->getAttribute('key');
									$js = $xml->getAttribute('js');
									$value = $xml->readString();
									foreach ( \IPS\Lang::languages() as $lang )
									{
										if ( count( $existingLanguageKeys ) and in_array( $key, $existingLanguageKeys ) )
										{
											/* Exists so do not delete */
											$keysToDelete = array_diff( $keysToDelete, array( $key ) );
											
											\IPS\Db::i()->update( 'core_sys_lang_words', array(
													'word_default'			=> $value,
													'word_default_version'	=> $plugin->version_long,
													'word_js'				=> $js
												),
												array( 'lang_id=? and word_plugin=? and word_key=?', $lang->id, $plugin->id, $key )
											);
										}
										else
										{
											$inserts[] = array(
												'lang_id'				=> $lang->id,
												'word_app'				=> NULL,
												'word_plugin'			=> $plugin->id,
												'word_key'				=> $key,
												'word_default'			=> $value,
												'word_custom'			=> NULL,
												'word_default_version'	=> $plugin->version_long,
												'word_custom_version'	=> NULL,
												'word_js'				=> $js,
												'word_export'			=> 1,
											);
										}
									}
									
									if ( !$xml->isEmptyElement )
									{
										$xml->read();
										$xml->next();
									}
								}
																
								if( count( $inserts ) )
								{
									\IPS\Db::i()->replace( 'core_sys_lang_words', $inserts );
								}
								
								if ( count( $keysToDelete ) )
								{
									\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'word_plugin=? AND ' . \IPS\Db::i()->in( 'word_key', $existingLanguageKeys ), $plugin->id ) );
								}
								
								break;
							
							/* Versions */
							case 'versions':
								
								while ( $xml->read() and $xml->name == 'version' )
								{
									$class = $xml->readString();

									if ( $class AND $xml->getAttribute('long') )
									{
										if ( $xml->getAttribute('long') == 10000 AND $data['isNew'] )
										{
											/* Installing, so use install file which is bundled with <version long="10000"> */
											$key   = 'plugin_' . $plugin->id . '_setup_install_class';
											\IPS\Data\Store::i()->$key = $class;

											$data['storeKeys'][]    = $key;
											$data['setUpClasses']['install'] = 'install';
										}
										else if ( $data['currentVersionId'] < $xml->getAttribute('long') )
										{
											$key = 'plugin_' . $plugin->id . '_setup_' . $xml->getAttribute('long') . '_class';
											\IPS\Data\Store::i()->$key = $class;

											$data['storeKeys'][]    = $key;
											$data['setUpClasses'][ $xml->getAttribute('long') ] = $xml->getAttribute('long');
										}
									}

									$xml->read();
									$xml->next();
								}

								break;

							/* Uninstall Code */
							case 'uninstall':
								/* Write contents */
								\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/uninstall.php", $xml->readString() );
								break;

							/* Sidebar Widgets */
							case 'widgets':
							
								/* Make the directory */
								if ( !file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/widgets" ) )
								{
									\mkdir( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/widgets" );
									chmod( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/widgets", \IPS\IPS_FOLDER_PERMISSION );
								}
															
								/* Loop widgets */
								while ( $xml->read() and $xml->name == 'widget' )
								{
									/** delete the widgets cache */
									$data['storeKeys'][] = 'widgets';

									$key = $xml->getAttribute('key');
										
									/* Insert into DB */
									try
									{
										$widget = \IPS\Widget::load( $plugin, 'key', array( 'plugin=?', $plugin->id ) );
										$widget->plugin = $plugin->id;
										$widget->key			= 	$key;
										$widget->class			= 	$xml->getAttribute('class');
										$widget->default_area 	=	$xml->getAttribute('default_area');
										$widget->allow_reuse 	=	intval( $xml->getAttribute('allow_reuse') );
										$widget->menu_style 	=	$xml->getAttribute('menu_style');
										$widget->embeddable 	=	intval( $xml->getAttribute('embeddable') );
										$widget->restrict		= 	explode( ",", $xml->getAttribute('restrict') );
										$widget->save();
									}
									catch ( \OutOfRangeException $e )
									{
										$inserts[] = array(
											'plugin'	   => $plugin->id,
											'key'		   => $key,
											'class'		   => $xml->getAttribute('class'),
											'restrict'     => json_encode( explode( ",", $xml->getAttribute('restrict') ) ),
											'default_area' => $xml->getAttribute('default_area'),
											'allow_reuse'  => intval( $xml->getAttribute('allow_reuse') ),
											'menu_style'   => $xml->getAttribute('menu_style'),
											'embeddable'   => intval( $xml->getAttribute('embeddable') ),
										);
										\IPS\Db::i()->insert( 'core_widgets', $inserts, TRUE );
									}

									/* Write contents */
									$contents = $xml->readString();
									$contents = str_replace( '<{ID}>', $plugin->id, $contents );
									$contents = str_replace( '<{LOCATION}>', $plugin->location, $contents );
									\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/widgets/{$key}.php", $contents );
										
									/* Move onto next */
									$xml->read();
									$xml->next();
								}
							
								break;
						}
						
						/* Move on */
						$data['done'][] = $step;
						return array( $data, \IPS\Member::loggedIn()->language()->addToStack('plugins_install_setup_done_step', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'plugin_step_' . $step ) ) ) ) );
					}
					else
					{
						$xml->next();
					}
				}

				/* Do upgrade classes */
				if ( count( $data['setUpClasses'] ) )
				{
					\IPS\Log::debug( "Plugin setup: found " . count( $data['setUpClasses'] ). " set up classes to run ", 'plugin_setup' );

					/* Grab class and run it, step by step */
					$versionToRun = current( $data['setUpClasses'] );
					$key          = 'plugin_' . $plugin->id . '_setup_' . $versionToRun . '_class';
					$class       = ( $versionToRun === 'install' ) ? 'ips_plugins_setup_install' : 'ips_plugins_setup_upg_' . $versionToRun;

					\IPS\Log::debug( "Plugin setup: looking for class key " . $key, 'plugin_setup' );

					if ( isset( \IPS\Data\Store::i()->$key ) )
					{
						\IPS\Log::debug( "Plugin setup: found class key " . $key . " class " . $class, 'plugin_setup' );
						\IPS\Log::debug( \IPS\Data\Store::i()->$key, 'plugin_setup' );

						/* As this is to be evaled, make sure PHP tags aren't there */
						\IPS\Data\Store::i()->$key = preg_replace( '/^<' . '?php(\n)/', '', \IPS\Data\Store::i()->$key );

						eval( \IPS\Data\Store::i()->$key );

						\IPS\Log::debug( "Plugin setup: looking for class " . $class, 'plugin_setup' );

						if ( class_exists( $class ) )
						{
							$upgrader = new $class();

							$stepToRun = $data['step'] + 1;
							$method    = 'step' . $stepToRun;
							$more      = FALSE;

							\IPS\Log::debug( "Plugin setup: looking for class key " . $key . ", " . $method, 'plugin_setup' );

							if ( method_exists( $upgrader, $method ) )
							{
								\IPS\Log::debug( "Plugin setup: Running " . $key . ", " . $method, 'plugin_setup' );

								$result = $upgrader->$method();

								if ( $result === TRUE )
								{
									$method = 'step' . ( $stepToRun + 1 );

									if ( method_exists( $upgrader, $method ) )
									{
										\IPS\Log::debug( "Plugin setup: Running " . $key . ", next method " . $method . " found", 'plugin_setup' );

										/* Hit it on the next redirect */
										$data['step']++;
										$more = TRUE;
									}
								}
								/* If the result is an array with 'html' key, we show that */
								else if( is_array( $result ) AND isset( $result['html'] ) )
								{
									return $result['html'];
								}
								else if ( ! empty( $result ) )
								{
									$data['upgradeData'] = $result;
									$more = TRUE;
								}
							}

							/* Go for another hit on multiredirector */
							if ( $more )
							{
								return array( $data, \IPS\Member::loggedIn()->language()->addToStack('plugins_install_setup_method', FALSE, array( 'sprintf' => array( $versionToRun ) ) ) );
							}
						}
					}

					\IPS\Log::debug( "Plugin setup: Class " . $versionToRun . " completed", 'plugin_setup' );

					/* Done this class completely */
					$data['step'] = 0;
					unset( $data['setUpClasses'][ $versionToRun ] );

					\IPS\Log::debug( json_encode( $data ), 'plugin_setup' );

					/* Go for another hit on multiredirector */
					return array( $data, \IPS\Member::loggedIn()->language()->addToStack('plugins_install_setup_method', FALSE, array( 'sprintf' => array( $versionToRun ) ) ) );
				}

				\IPS\Log::debug( "Plugin setup: All set up classes run", 'plugin_setup' );

				/* All set up classes are done, so delete stored data so far */
				if ( count( $data['storeKeys'] ) )
				{
					foreach( $data['storeKeys'] as $sk )
					{
						if ( isset( \IPS\Data\Store::i()->$sk ) )
						{
							unset( \IPS\Data\Store::i()->$sk );
						}
					}

					$data['storeKeys'] = array();
				}

				/* Update data file */
				\IPS\Plugin\Hook::writeDataFile();

				/* Recompile CSS */
				\IPS\Theme::deleteCompiledCss( 'core', 'front', 'custom' );
															
				/* Recompile templates */
				\IPS\Theme::deleteCompiledTemplate( 'core', 'global', 'plugins' );
				if ( isset( $data['recompileTemplates'] ) )
				{
					foreach ( $data['recompileTemplates'] as $k )
					{
						$exploded = explode( '_', $k );
						\IPS\Theme::deleteCompiledTemplate( $exploded[1], $exploded[2], $exploded[3] );
					}
				}

				/* Clear javascript map to rebuild automatically */
				unset( \IPS\Data\Store::i()->javascript_file_map, \IPS\Data\Store::i()->javascript_map );

				/* Clear guest page caches */
				\IPS\Data\Cache::i()->clearAll();

				/* Log */
				\IPS\Session::i()->log( 'acplog__plugin_installed', array( $plugin->name => FALSE ) );

				/* Re-enable the plugin */
				$plugin->enabled = TRUE;
				$plugin->save();
				
				/* All done */
				return NULL;
			},
			function()
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins' ) );
			}
		);
	}
	
	/**
	 * Edit Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'plugins_edit' );
		
		/* Load */
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/4', 404, '' );
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form;
		eval( file_get_contents( \IPS\ROOT_PATH . '/plugins/' . $plugin->location . '/settings.php' ) );
		
		/* Display */
		if ( $form->values() )
		{
			\IPS\Session::i()->log( 'acplog__plugin_settings', array( $plugin->name => FALSE ) );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins" ), 'saved' );
		}
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Developer Mode
	 *
	 * @return	void
	 */
	protected function developer()
	{
		if( !\IPS\IN_DEV )
		{
			\IPS\Output::i()->error( 'not_in_dev', '2C145/C', 403, '' );
		}
	
		/* Load */
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/4', 404, '' );
		}
		
		/* Get tab contents */
		$activeTab = \IPS\Request::i()->tab ?: 'hooks';
		$activeTabContents = call_user_func( array( $this, '_manage'.ucfirst( $activeTab ) ), $plugin );
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Work out tabs */
		$tabs = array();
		$tabs['info'] = 'plugin_information';
		$tabs['hooks'] = 'plugin_hooks';
		$tabs['settings'] = 'dev_settings';
		$tabs['tasks'] = 'dev_tasks';
		$tabs['versions'] = 'dev_versions';
		$tabs['widgets'] = 'dev_widgets';
		
		/* Display */
		if ( $activeTabContents )
		{
			/* Add Download Button */
			\IPS\Output::i()->sidebar['actions'] = array(
				'download' => array(
					'icon'	=> 'download',
					'title'	=> 'download',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=download&id={$plugin->id}" ),
				)
			);
			
			\IPS\Output::i()->title		= $plugin->name;
			\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}" ) );
		}
	}
	
	/**
	 * Developer Mode: Plugin Information
	 *
	 * @param	\IPS\Plugin	$plugin	The plugin
	 * @return	string
	 */
	protected function _manageInfo( $plugin )
	{
		$form = new \IPS\Helpers\Form;
		$plugin->form( $form );
		if ( $values = $form->values() )
		{
			$plugin->saveForm( $plugin->formatFormValues( $values ) );
			$plugin->save();
		}
		return $form;
	}
	
	/**
	 * Developer Mode: Hooks
	 *
	 * @param	\IPS\Plugin	$plugin	The plugin
	 * @return	string
	 */
	protected function _manageHooks( $plugin )
	{
		return \IPS\Plugin\Hook::devTable(
			\IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=hooks" ),
			$plugin->id,
			\IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks"
		);
	}
	
	/**
	 * Edit Hook
	 *
	 * @return	string
	 */
	protected function editHook()
	{
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->id );
			\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=hooks" ), $plugin->_title );
			\IPS\Plugin\Hook::load( \IPS\Request::i()->hook )->editForm( \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id=" . $plugin->id . "&tab=hooks" ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/5', 404, '' );
		}
	}
	
	/**
	 * Show Template Tree
	 *
	 * @return	void
	 */
	protected function templateTree()
	{
		$exploded = explode( '_', \IPS\Request::i()->class );
		$bits = \IPS\Theme::load( \IPS\Theme::defaultTheme() )->getRawTemplates( $exploded[1], $exploded[2], $exploded[3], \IPS\Theme::RETURN_ALL );
		
		$document = new \DOMDocument();
		$document->strictErrorChecking = FALSE;
		libxml_use_internal_errors(TRUE);

		/* Get the template content */
		$code	= $bits[ $exploded[1] ][ $exploded[2] ][ $exploded[3] ][ \IPS\Request::i()->template ]['template_content'];

		/* We need to fix special wrapping html tags or dom document will mess with them */
		$code	= preg_replace( '/<(\/?)(html|head|body)(>|\s)/', '<$1x_$2_x$3', $code );

		/* Fix else statement - basic replacement */
		$code	= str_replace( "{{else}}", "<else>", $code );

		/* Fix if/foreach/for tags...htmlspecialchars the content in case there is a -> which will break things */
		$code	= preg_replace_callback( '/\{\{(if|foreach|for)\s+?(.+?)\}\}/i', function( $matches )
		{
			return '<' . $matches[1] . ' code="' . htmlspecialchars( $matches[2], \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) . '">';
		}, $code );

		/* Fix ending if/foreach/for tags */
		$code	= preg_replace( '/\{\{end(if|foreach|for)\}\}/i', '</$1>', $code );

		/* Fix regular template tags such as url to htmlspecialchars the content */
		$code	= preg_replace_callback( '/\{([a-z]+?=([\'"]).+?\\2 ?+)}/', function( $matches )
		{
			return htmlspecialchars( $matches[0], \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE );
		}, $code );

		/* Strip any raw replacement tags remaining */
		$code	= preg_replace( '/\{{(.+?)}}/', '', $code );

		/* Now fix <if/foreach/for tags that are embedded into attributes */
		while( preg_match( '/\<([^>]+?)(\<(\/*?)(if|foreach|for|else).*?\>([^>]*?)(\<\/\4\>))/ms', $code ) )
		{
			$code	= preg_replace_callback( '/\<([^>]+?)(\<(\/*?)(if|foreach|for|else).*?\>([^>]*?)(\<\/\4\>))/ms', function( $matches )
			{
				return str_replace( $matches[2], '', $matches[0] );
			}, $code );
		}

		/* Fix embedded single quotes */
		$code	= preg_replace_callback( '/=\'([^\']+?){.+?}([^\']+?)\'/', function( $matches )
		{
			return "=''";
		}, $code );
		
		/* Ensure attributes with variable data are encoded before the cheeky DOM parser gets to it */
		$code	= preg_replace_callback( '/=([\'"])(\{.+?\})([\'"])/', function( $matches )
		{
			return "=" . $matches[1] . htmlspecialchars( $matches[2], \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) . $matches[3];
		}, $code );

		/* Now load the HTML - doctype necessary sometimes */
		$document->loadHTML( '<!DOCTYPE html><ipscontent id="ipscontent">' . $code . '</ipscontent>' );
	
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'applications' )->themeHookEditorTreeRoot( $document->getElementById('ipscontent') );
	}
	
	/**
	 * Get the CSS selector for a node
	 *
	 * @param	DOMNode	$node	The node
	 * @return	string
	 */
	public static function getSelector( \DOMNode $node )
	{
		$bits = array();
		while ( TRUE )
		{
			if ( $node->tagName == 'ipscontent' )
			{
				break;
			}
			elseif ( in_array( $node->tagName, array( 'if', 'foreach', 'for', 'else' ) ) )
			{
				$node = $node->parentNode;
			}
			else
			{
				if ( $node->hasAttributes() )
				{
					if ( $node->attributes->getNamedItem('id') AND $node->attributes->getNamedItem('id')->nodeValue AND mb_strpos( $node->attributes->getNamedItem('id')->nodeValue, '$' ) === FALSE )
					{
						$bits[] = '#' . $node->attributes->getNamedItem('id')->nodeValue;
						break;
					}
					else
					{
						$bit = preg_replace( '/^(x_)?([a-z]+)(_x)?$/i', '$2', $node->nodeName );
						for ( $i = 0; $i < $node->attributes->length; ++$i )
						{
							if ( $node->attributes->item( $i )->nodeName === 'class' AND $node->attributes->item( $i )->nodeValue AND mb_strpos( $node->attributes->item( $i )->nodeValue, '$' ) === FALSE  AND mb_strpos( $node->attributes->item( $i )->nodeValue, '{' ) === FALSE )
							{
								foreach ( array_filter( explode( ' ', $node->attributes->item( $i )->nodeValue ) ) as $class )
								{
									$bit .= '.' . $class;
								}
							}
							elseif ( !in_array( $node->attributes->item( $i )->nodeName, array( 'href', 'src', 'value', 'class', 'id' ) ) AND $node->attributes->item( $i )->nodeValue AND mb_strpos( $node->attributes->item( $i )->nodeValue, '$' ) === FALSE  AND mb_strpos( $node->attributes->item( $i )->nodeValue, '{' ) === FALSE )
							{
								if ( $node->attributes->item( $i )->nodeValue )
								{
									$bit .= "[{$node->attributes->item( $i )->nodeName}='{$node->attributes->item( $i )->nodeValue}']";
								}
								else
								{
									$bit .= "[{$node->attributes->item( $i )->nodeName}]";
								}
							}
						}
						$bits[] = $bit;
					}
				}
				else
				{
					$bits[] = preg_replace( '/^(x_)?([a-z]+)(_x)?$/i', '$2', $node->tagName );
				}
				
				$node = $node->parentNode;
			}
		}
		return implode( ' > ', array_reverse( $bits ) );
	}

	/**
	 * Manage Settings
	 *
	 * @param	\IPS\Plugin	$plugin	The plugin
	 * @return	string
	 */
	protected function _manageSettings( $plugin )
	{
		$file = \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/settings.json";
		
		$matrix = new \IPS\Helpers\Form\Matrix;
		$matrix->langPrefix = 'dev_settings_';
		$matrix->columns = array(
			'key'		=> array( 'Text', NULL, TRUE ),
			'default'	=> array( 'Text' )
		);
		$matrix->rows = file_exists( $file ) ? json_decode( file_get_contents( $file ), TRUE ) : array();
				
		if ( $values = $matrix->values() )
		{
			if ( !empty( $matrix->addedRows ) )
			{
				$insert = array();
				foreach ( $matrix->addedRows as $key )
				{
					$insert[] = array( 'conf_key' => $values[ $key ]['key'], 'conf_value' => $values[ $key ]['default'], 'conf_default' => $values[ $key ]['default'], 'conf_plugin' => $plugin->id );
				}

				\IPS\Db::i()->insert( 'core_sys_conf_settings', $insert );
			}
			if ( !empty( $matrix->changedRows ) )
			{
				foreach ( $matrix->changedRows as $key )
				{
					\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_default' => $values[ $key ]['default'] ), array( 'conf_key=?', $values[ $key ]['key'] ) );
				}
			}
			if ( !empty( $matrix->removedRows ) )
			{
				$delete = array();
				foreach ( $matrix->removedRows as $key )
				{
					$delete[] = $matrix->rows[ $key ]['key'];
				}
				
				\IPS\Db::i()->delete( 'core_sys_conf_settings', \IPS\Db::i()->in( 'conf_key', $delete ) );
			}

			unset( \IPS\Data\Store::i()->settings );
						
			\file_put_contents( $file, json_encode( array_filter( array_values( $values ), function ( $v )
			{
				return (bool) $v['key'];
			} ) ) );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=settings" ) );
		}
		
		return $matrix;
	}
	
	/**
	 * Manage Tasks
	 *
	 * @param	\IPS\Plugin	$plugin	The plugin
	 * @return	string
	 */
	protected function _manageTasks( $plugin )
	{
		return \IPS\Task::devTable(
			\IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/tasks.json",
			\IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=tasks" ),
			\IPS\ROOT_PATH . "/plugins/{$plugin->location}/tasks",
			$plugin->location,
			'pluginTasks',
			$plugin->version_long,
			$plugin->id
		);
	}
	
	/**
	 * Manage Widgets
	 *
	 * @param	\IPS\Plugin	$plugin	The plugin
	 * @return	string
	 */
	protected function _manageWidgets( $plugin )
	{
		return \IPS\Widget::devTable(
				\IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/widgets.json",
				\IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=widgets" ),
				\IPS\ROOT_PATH . "/plugins/{$plugin->location}/widgets",
				$plugin->location,
				"plugins\\" . $plugin->location,
				$plugin->version_long,
				$plugin->id
		);
	}
	
	/**
	 * Manage Versions
	 *
	 * @param	\IPS\Plugin	$plugin	The plugin
	 * @return	string
	 */
	protected function _manageVersions( $plugin )
	{
		if ( !file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json" ) )
		{
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json", json_encode( array( '10000' => '1.0.0' ) ) );
		}
		$versions = array();
		foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json" ) ) as $long => $human )
		{
			$versions[] = array(
				'versions_long'		=> $long,
				'versions_human'	=> $human
			);
		}
		
		$table = new \IPS\Helpers\Table\Custom( $versions, \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=versions" ) );
		
		$table->rootButtons = array(
			'add' => array(
				'title'	=> 'versions_add',
				'icon'	=> 'plus',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=addVersion&plugin={$plugin->id}" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('versions_add') )
			)
		);
		
		$table->sortBy = $table->sortBy ?: 'versions_long';
		$table->sortDirection = $table->sortDirection ?: 'desc';
				
		$table->rowButtons = function( $row ) use ( $plugin )
		{
			return array(
				'delete' => array(
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=deleteVersion&plugin={$plugin->id}&id={$row['versions_long']}" ),
					'data'	=> array( 'delete' => '' )
				)
			);
		};
		
		return (string) $table;
	}
	
	/**
	 * Versions: Add Version
	 *
	 * @return	void
	 */
	protected function addVersion()
	{
		/* Load Plugin */
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->plugin );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/8', 404, '' );
		}
		
		/* Load existing versions.json file */
		$json = json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json" ), TRUE );
				
		/* Build form */
		$form = new \IPS\Helpers\Form( 'versions_add' );
		$defaults = array( 'human' => '1.0.0', 'long' => '10000' );
		foreach ( array_reverse( $json, TRUE ) as $long => $human )
		{
			$exploded = explode( '.', $human );
			$defaults['human'] = "{$exploded[0]}.{$exploded[1]}." . ( $exploded[2] + 1 );
			$defaults['long'] = $long + 1;
			break;
		}
		$form->add( new \IPS\Helpers\Form\Text( 'versions_human', $defaults['human'], TRUE, array(), function( $val )
		{
			if ( !preg_match( '/^\d*\.\d*\.\d*( (Alpha|Beta|RC)\s?[a-zA-Z0-9]*)?$/', $val ) )
			{
				throw new \DomainException( 'versions_human_error' );
			}
		} ) );
		$form->add( new \IPS\Helpers\Form\Text( 'versions_long', $defaults['long'], TRUE, array(), function( $val ) use ( $json )
		{
			if ( !preg_match( '/^\d*$/', $val ) )
			{
				throw new \DomainException( 'form_number_bad' );
			}
			if( $val < 10000 )
			{
				throw new \DomainException( 'versions_long_too_low' );
			}
			if( isset( $json[ $val ] ) )
			{
				throw new \DomainException( 'versions_long_exists' );
			}
		} ) );
		
		/* Has the form been submitted? */
		if( $values = $form->values() )
		{
			$json[ $values['versions_long'] ] = $values['versions_human'];
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/setup/{$values['versions_long']}.php", preg_replace( '/(<\?php\s)\/*.+?\*\//s', '$1', str_replace(
				array(
					'{version_human}',
					'{app}',
					'{version_long}',
				),
				array(
					$values['versions_human'],
					'plugins',
					$values['versions_long'],
				),
				file_get_contents( \IPS\ROOT_PATH . "/applications/core/data/defaults/UpgradePlugin.txt" )
			) ) );
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json", json_encode( $json ) );
			
			krsort( $json );
			foreach ( $json as $long => $human )
			{
				$plugin->version_long = $long;
				$plugin->version_human = $human;
				$plugin->save();
				break;
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=versions" ) );
		}
					
		/* If not, show it */
		\IPS\Output::i()->output = $form;
	}
		
	/**
	 * Delete Version
	 *
	 * @return	void
	 */
	protected function deleteVersion()
	{
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Load Plugin */
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->plugin );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/A', 404, '' );
		}
		
		/* Load existing versions.json file */
		$json = json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json" ), TRUE );
		
		/* Unset */
		if ( isset( $json[ intval( \IPS\Request::i()->id ) ] ) )
		{
			unset( $json[ intval( \IPS\Request::i()->id ) ] );
		}
		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/setup/" . intval( \IPS\Request::i()->id ) . ".php" ) )
		{
			unlink( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/setup/" . intval( \IPS\Request::i()->id ) . ".php" );
		}
		
		/* Write */
		\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json", json_encode( $json ) );
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$plugin->id}&tab=versions" ) );
	}
		
	/**
	 * Developer Mode: Download
	 *
	 * @return	void
	 */
	public function download()
	{
		/* Load */
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/B', 404, '' );
		}
				
		/* Init */
		$xml = \IPS\Xml\SimpleXML::create('plugin');
		$xml->addAttribute( 'name', $plugin->name );
		$xml->addAttribute( 'version_long', $plugin->version_long );
		$xml->addAttribute( 'version_human', $plugin->version_human );
		$xml->addAttribute( 'author', $plugin->author );
		$xml->addAttribute( 'website', $plugin->website );
		$xml->addAttribute( 'update_check', $plugin->update_check );

		/* Get Hooks */
		$hooks = $xml->addChild( 'hooks' );
		foreach ( \IPS\Db::i()->select( '*', 'core_hooks',  array( 'plugin=?', $plugin->id ) ) as $hook )
		{
			$hookNode = $hooks->addChild( 'hook', \IPS\Plugin::addExceptionHandlingToHookFile( \IPS\ROOT_PATH . '/plugins/' . $plugin->location . '/hooks/' . $hook['filename'] . '.php' ) );
			$hookNode->addAttribute( 'type', $hook['type'] );
			$hookNode->addAttribute( 'class', $hook['class'] );
			$hookNode->addAttribute( 'filename', $hook['filename'] );
		}
		
		/* Get Settings */
		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/settings.json" ) )
		{
			$settings = json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/settings.json" ), TRUE );
			if ( !empty( $settings ) )
			{
				$xml->addChild( 'settings', $settings );
			}
		}

		/* Uninstall Code */
		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/uninstall.php" ) )
		{
				$xml->addChild( 'uninstall', file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/uninstall.php" ) );
		}

		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/settings.php" ) )
		{
			$xml->addChild( 'settingsCode', file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/settings.php" ) );
		}
		
		/* Get tasks */
		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/tasks.json" ) )
		{
			$tasksNode = $xml->addChild( 'tasks' );
			foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/tasks.json" ), TRUE ) as $key => $frequency )
			{
				$taskNode = $tasksNode->addChild( 'task', file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/tasks/{$key}.php" ) );
				$taskNode->addAttribute( 'key', $key );
				$taskNode->addAttribute( 'frequency', $frequency );
			}
		}
		
		/* Get sidebar widgets */
		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/widgets.json" ) )
		{
			$widgetsNode = $xml->addChild( 'widgets' );
			foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/widgets.json" ), TRUE ) as $key => $json )
			{
				$content = file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/widgets/{$key}.php" );
				$content = str_replace( "namespace IPS\\plugins\\{$plugin->location}\\widgets", "namespace IPS\\plugins\\<{LOCATION}>\\widgets", $content );
				$content = str_replace( "public \$plugin = '{$plugin->id}';", "public \$plugin = '<{ID}>';", $content );
				$widgetNode = $widgetsNode->addChild( 'widget', $content );
				$widgetNode->addAttribute('key', $key );
				
				foreach ($json as $dataKey => $value)
				{
					if( is_array( $value ) )
					{
						$value = implode( ",", $value );
					}

					$widgetNode->addAttribute( $dataKey, $value );
				}
			}
			
		}
		
		/* Get HTML, CSS, JS, Resources */
		foreach ( array( 'html' => 'phtml', 'css' => 'css', 'js' => 'js', 'resources' => '*' ) as $k => $ext )
		{
			$resourcesNode = $xml->addChild( "{$k}Files" );
			foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/{$k}" ) as $file )
			{
				if ( !$file->isDot() and mb_substr( $file, 0, 1 ) != '.' and ( $ext === '*' or mb_substr( $file, - ( mb_strlen( $ext ) + 1 ) ) === ".{$ext}" ) )
				{
					$resourcesNode->addChild( $k, base64_encode( file_get_contents( $file->getPathname() ) ) )->addAttribute( 'filename', $file );
				}
			}
		}
		
		/* Get language strings */
		if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/lang.php" ) or file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/jslang.php" ) )
		{
			$langNode = $xml->addChild( 'lang' );
			foreach ( array( 'lang' => 0, 'jslang' => 1 ) as $file => $js )
			{
				if ( file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/{$file}.php" ) )
				{
					require \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/{$file}.php";
					foreach ( $lang as $k => $v )
					{
						$word = $langNode->addChild( 'word', $v );
						$word->addAttribute( 'key', $k );
						$word->addAttribute( 'js', $js );
					}
				}
			}
		}
		
		/* Get versions */
		$versionsNode = $xml->addChild( 'versions' );
		$versions = json_decode( file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/versions.json" ), TRUE );
		ksort( $versions );

		foreach ( $versions as $k => $v )
		{
			$setupFile = ( $k == 10000 ) ? 'install.php' : $k . '.php';

			$node = $versionsNode->addChild( 'version', file_exists( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/setup/" . $setupFile ) ? file_get_contents( \IPS\ROOT_PATH . "/plugins/{$plugin->location}/dev/setup/" . $setupFile ) : '' );
			$node->addAttribute( 'long', $k );
			$node->addAttribute( 'human', $v );
		}
		
		/* Build */
		\IPS\Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'attachment', $plugin->name . '.xml' ) ), FALSE, FALSE, FALSE );
	}

	/**
	 * View plugin details
	 *
	 * @return	void
	 */
	public function details()
	{
		/* Load */
		try
		{
			$plugin = \IPS\Plugin::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C145/B', 404, '' ); //@todo duplicate error code
		}

		/* Output */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'plugin_details' );
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'plugins' )->details( $plugin );
	}
}