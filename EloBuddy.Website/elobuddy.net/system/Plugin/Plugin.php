<?php
/**
 * @brief		Plugin Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Plugin Model
 */
class _Plugin extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons = array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_plugins';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'plugin_';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'order';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'plugins';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	/**
	 * @brief	Have fetched all?
	 */
	protected static $gotAll = FALSE;
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'applications',
		'map'		=> array(
			'add'			=> 'plugins_install',
 			'edit'			=> 'plugins_edit',
 			'permissions'	=> 'plugins_edit',
 			'delete'		=> 'plugins_uninstall'
		),
	);
	
	/**
	 * Get Plugins
	 *
	 * @return	array
	 */
	public static function plugins()
	{
		if( self::$gotAll === FALSE )
		{
			if ( isset( \IPS\Data\Cache::i()->plugins ) )
			{
				$rows = \IPS\Data\Cache::i()->plugins;
			}
			else
			{	
				$rows = iterator_to_array( \IPS\Db::i()->select( '*', 'core_plugins' ) );
				\IPS\Data\Cache::i()->plugins = $rows;
			}
			
			foreach ( $rows as $row )
			{
				if ( !isset( self::$multitons[ $row['plugin_id'] ] ) )
				{
					self::$multitons[ $row['plugin_id'] ] = static::constructFromData( $row );
				}
			}
			
			self::$gotAll = TRUE;
		}
		
		return self::$multitons;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add aa root node?
	 *
	 * @return	bool
	 */
	public static function canAddRoot()
	{
		return ( \IPS\IN_DEV ) ? true : false;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		return FALSE;
	}
		
	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy()
	{
		return FALSE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return	bool
	 */
	public function canDelete()
	{
		if( \IPS\NO_WRITES )
		{
			return FALSE;
		}

		return parent::canDelete();
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->addHeader( 'plugin_details' );
		$form->add( new \IPS\Helpers\Form\Text( 'plugin_name', $this->name, TRUE, array( 'maxLength' => 32 ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'plugin_location', $this->location, FALSE, array( 'disabled' => $this->_id ? TRUE : FALSE, 'maxLength' => 32, 'regex' => '/^[a-z][a-z0-9]*$/i' ) ) );
		$form->add( new \IPS\Helpers\Form\Url( 'plugin_update_check', $this->update_check ) );
		
		$form->addHeader( 'plugin_author_details' );
		$form->add( new \IPS\Helpers\Form\Text( 'plugin_author', $this->author, FALSE, array( 'maxLength' => 255 ) ) );
		$form->add( new \IPS\Helpers\Form\Url( 'plugin_website', $this->website, FALSE, array( 'maxLength' => 255 ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( !$this->_id AND isset( $values['plugin_location'] ) )
		{
			$values['plugin_location'] = $values['plugin_location'] ?: ( 'p' . mb_substr( md5( uniqid() ), 0, 10 ) );

			@\mkdir( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}" );
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/index.html", '' );
			@\chmod( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}", \IPS\IPS_FOLDER_PERMISSION );

			$defaultSettings = <<<'CODE'
//<?php

$form->add( new \IPS\Helpers\Form\Text( 'plugin_example_setting', \IPS\Settings::i()->plugin_example_setting ) );

if ( $values = $form->values() )
{
	$form->saveAsSettings();
	return TRUE;
}

return $form;
CODE;

			foreach ( array( 'hooks', 'dev', 'dev/html', 'dev/css', 'dev/js', 'dev/resources', 'dev/setup' ) as $k )
			{
				@\mkdir( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/{$k}" );
				\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/hooks/index.html", '' );
				@\chmod( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/hooks", \IPS\IPS_FOLDER_PERMISSION );
			}

			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/settings.rename.php", $defaultSettings );
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/dev/jslang.php", "<?php\n\n\$lang = array(\n\n\n\n);\n" );
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/dev/lang.php", "<?php\n\n\$lang = array(\n\n\n\n);\n" );
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/dev/versions.json", json_encode( array( '10000' => '1.0.0' ) ) );
			\file_put_contents( \IPS\ROOT_PATH . "/plugins/{$values['plugin_location']}/dev/setup/install.php", preg_replace( '/(<\?php\s)\/*.+?\*\//s', '$1', str_replace(
				array(
					'{version_human} Upgrade',
					'{app}',
					'upg_{version_long}',
					'class Upgrade'
				),
				array(
					'Install',
					'plugins',
					'install',
					'class Install'
				),
				file_get_contents( \IPS\ROOT_PATH . "/applications/core/data/defaults/UpgradePlugin.txt" )
			) ) );
		}

		return $values;
	}

	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		$writeDataFile = FALSE;
		if ( array_key_exists( 'enabled', $this->changed ) )
		{
			$writeDataFile = TRUE;
		}
		
		parent::save();
		
		if ( $writeDataFile )
		{
			\IPS\Plugin\Hook::writeDataFile();
			
			/* Clear templates to rebuild automatically */
			\IPS\Theme::deleteCompiledTemplate();
			
			/* Clear javascript map to rebuild automatically */
			unset( \IPS\Data\Store::i()->javascript_file_map, \IPS\Data\Store::i()->javascript_map );
		}
		
		unset( \IPS\Data\Cache::i()->plugins );
	}

	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title()
	{
		return $this->name;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	array(
	 				'icon.png'			// Path to icon
	 				'core'				// Application icon belongs to
	 			),
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url	Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = array();
		$defaultButtons = parent::getButtons( $url );
		
		/* Add a settings button */
		if ( file_exists( \IPS\ROOT_PATH . '/plugins/' . $this->location . '/settings.php' ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'plugins_edit' ) )
		{
			$buttons['settings']	= array(
				'icon'	=> 'pencil',
				'title'	=> 'edit',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=settings&id={$this->_id}" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => $this->_title, 'ipsDialog-flashMessage' => \IPS\Member::loggedIn()->language()->addToStack('saved') )
			);
		}
		
		/* Upgrade */
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'applications', 'plugins_install' ) )
		{
			$buttons['upgrade']	= array(
				'icon'	=> 'upload',
				'title'	=> 'theme_set_import',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=install&id={$this->_id}" ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('theme_set_import') ),
			);
		}
		
		/* And an uninstall */
		if( isset( $defaultButtons['delete'] ) )
		{
			$buttons['uninstall']	= array(
				'icon'	=> 'times-circle',
				'title'	=> 'uninstall',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=delete&id={$this->_id}" ),
				'data'	=> array( 'delete' => '', 'delete-warning' => \IPS\Member::loggedIn()->language()->addToStack('plugin_uninstall_warning') ),
			);
			unset( $defaultButtons['delete'] );
		}
				
		/* Add in default ones */
		$buttons = array_merge( $buttons, $defaultButtons );
		
		/* Remove edit - it will be in the developer center */
		if ( isset( $buttons['edit'] ) )
		{
			unset( $buttons['edit'] );
		}

		/* View Details */
		$buttons['details']	= array(
			'icon'	=> 'search',
			'title'	=> 'plugin_details',
			'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=details&id={$this->_id}" ),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('plugin_details') )
		);

		/* Specify developer mode */
		if( \IPS\IN_DEV )
		{
			$buttons['developer']	= array(
				'icon'	=> 'cogs',
				'title'	=> 'developer_mode',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=developer&id={$this->_id}" ),
			);

			$buttons['download']	= array(
				'icon'	=> 'download',
				'title'	=> 'download',
				'link'	=> \IPS\Http\Url::internal( "app=core&module=applications&controller=plugins&do=download&id={$this->_id}" ),
			);
		}

		
		/* Return */
		return $buttons;
	}
	
	/**
	 * Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	public function get__badge()
	{
		/* Is there an update to show? */
		$badge	= NULL;

		if( $this->update_check_data )
		{
			$data	= json_decode( $this->update_check_data, TRUE );
			if( !empty( $data['longversion'] ) AND $data['longversion'] > $this->version_long )
			{
				$released	= NULL;

				if( $data['released'] AND intval( $data['released'] ) == $data['released'] AND \strlen( $data['released'] ) == 10 )
				{
					$released	= (string) \IPS\DateTime::ts( $data['released'] )->localeDate();
				}
				else if( $data['released'] )
				{
					$released	= $data['released'];
				}

				$badge	= array(
					0	=> 'new',
					1	=> '',
					2	=> \IPS\Theme::i()->getTemplate( 'global', 'core' )->updatebadge( $data['version'], $data['updateurl'], $released )
				);
			}
		}

		return $badge;
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		return $this->enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
		$this->enabled	= $enabled;
		
		if ( \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_css', array( 'css_plugin=?', $this->id ) )->first() )
		{
			\IPS\Db::i()->update( 'core_theme_css', array( 'css_hidden' => !$enabled ), array( 'css_plugin=?', $this->id ) );
			\IPS\Theme::deleteCompiledCss( 'core', 'front', 'custom' );
		}
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Theme::removeTemplates( 'core', 'global', 'plugins', $this->id );
		\IPS\Theme::removeCss( 'core', 'front', 'custom', $this->id );
		\IPS\Theme::removeResources( 'core', 'global', 'plugins', $this->id );

		/* Get which templates need recompiling */
		$recompileTemplates = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_hooks', array( 'plugin=? AND type=?', $this->id, 'S' ) ) as $hook )
		{
			$recompileTemplates[ $hook['class'] ] = $hook['class'];
		}
		
		/* Remove the plugin directory */
		if ( file_exists( \IPS\ROOT_PATH . '/plugins/' . $this->location ) )
		{
			if ( file_exists( \IPS\ROOT_PATH . '/plugins/' . $this->location . '/uninstall.php') )
			{
				require_once \IPS\ROOT_PATH . '/plugins/' . $this->location . '/uninstall.php';
			}

			$iterator = new \RecursiveDirectoryIterator( \IPS\ROOT_PATH . '/plugins/' . $this->location, \FilesystemIterator::SKIP_DOTS );
			foreach ( new \RecursiveIteratorIterator( $iterator, \RecursiveIteratorIterator::CHILD_FIRST ) as $file )
			{  
				if ( $file->isDir() )
				{  
					@rmdir( $file->getPathname() );  
				}
				else
				{  
					@unlink( $file->getPathname() );  
				}  
			}
			$dir = \IPS\ROOT_PATH . '/plugins/' . $this->location;
			$handle = opendir( $dir );
			closedir ( $handle );
			@rmdir( $dir );
		}
		
		/* Delete stuff */
		\IPS\Db::i()->delete( 'core_hooks', array( 'plugin=?', $this->id ) );
		\IPS\Db::i()->delete( 'core_sys_conf_settings', array( 'conf_plugin=?', $this->id ) );
		\IPS\Db::i()->delete( 'core_tasks', array( 'plugin=?', $this->id ) );
		$hasResources = \IPS\Db::i()->delete( 'core_theme_resources', array( 'resource_plugin=?', $this->id ) );
		$hasCss = \IPS\Db::i()->delete( 'core_theme_css', array( 'css_plugin=?', $this->id ) );
		$hasTemplates = \IPS\Db::i()->delete( 'core_theme_templates', array( 'template_plugin=?', $this->id ) );
		$hasJs = \IPS\Db::i()->delete( 'core_javascript', array( 'javascript_plugin=?', $this->id ) );
		\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'word_plugin=?', $this->id ) );

		/* Remove widgets */
		\IPS\Db::i()->delete( 'core_widgets', array( 'plugin=?', $this->id ) );

		/* Remove widgets from page configurations */
		foreach ( \IPS\Db::i()->select( '*', 'core_widget_areas' ) as $area )
		{
			$widgets = json_decode( $area['widgets'], TRUE );
			$newWidgets = array();

			foreach ( $widgets as $widget )
			{
				if( !isset( $widget['plugin'] ) or $widget['plugin'] != $this->id )
				{
					$newWidgets[] = $widget;
				}
			}
			\IPS\Db::i()->update( 'core_widget_areas', array( 'widgets' => json_encode( $newWidgets ) ), array( 'id=?', $area['id'] ) );
		}

		unset( \IPS\Data\Store::i()->settings );
		
		/* Write the data file */
		\IPS\Plugin\Hook::writeDataFile();

		if ( $hasCss )
		{
			\IPS\Theme::deleteCompiledCss( 'core', 'front', 'custom' );
		}

		/* Recompile Templates */
		foreach ( $recompileTemplates as $k )
		{
			$exploded = explode( '_', $k );
			\IPS\Theme::deleteCompiledTemplate( $exploded[1], $exploded[2], $exploded[3] );
		}
		if ( $hasTemplates )
		{
			\IPS\Theme::deleteCompiledTemplate( 'core', 'global', 'plugins' );
		}
		
		/* Resources */
		if ( $hasResources )
		{
			\IPS\Theme::deleteCompiledResources( 'core', 'global', 'plugins' );
		}
		
		/* Clear javascript map to rebuild automatically */
		if ( $hasJs )
		{
			unset( \IPS\Data\Store::i()->javascript_file_map, \IPS\Data\Store::i()->javascript_map );
		}

		/* Finish */
		parent::delete();

		unset( \IPS\Data\Cache::i()->plugins );
	}

	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( $column, $query, $order=NULL, $where=array() )
	{
		if ( $column === '_title' )
		{
			$column	= 'plugin_name';
		}

		if( $order == '_title' )
		{
			$order	= 'plugin_name';
		}

		return parent::search( $column, $query, $order, $where );
	}
	
	/**
	 * Add try/catch statements to the contents of a hook file for distribution
	 *
	 * @param	string	$file	The location of the hook file on disk
	 * @return	string	Contents
	 */
	public static function addExceptionHandlingToHookFile( $file )
	{
		$contents = '';
			
		$depth = 0;
		$inHereDoc = NULL;
		$inThemeHooks = FALSE;
		$fh = fopen( $file, 'r' );
		while ( $line = fgets( $fh ) )
		{
			if ( $inThemeHooks )
			{
				$inThemeHooks = !( trim( $line ) == '/* End Hook Data */' );
			}
			else
			{
				$inThemeHooks = ( trim( $line ) == '/* !Hook Data - DO NOT REMOVE */' );
			}
			
			if ( !$inThemeHooks )
			{
				$openBraces = mb_substr_count( $line, '{' );
				$closeBraces = mb_substr_count( $line, '}' );
									
				$depth += $openBraces;
				$depth -= $closeBraces;
				
				$tabs = str_repeat( "\t", \substr_count( $line, "\t" ) + 1 );
				
				if ( $depth == 2 and $closeBraces )
				{
					$contents .= "{$tabs}}\n{$tabs}catch ( \RuntimeException \$e )\n{$tabs}{\n{$tabs}\tif ( method_exists( get_parent_class(), __FUNCTION__ ) )\n{$tabs}\t{\n{$tabs}\t\treturn call_user_func_array( 'parent::' . __FUNCTION__, func_get_args() );\n{$tabs}\t}\n{$tabs}\telse\n{$tabs}\t{\n{$tabs}\t\tthrow \$e;\n{$tabs}\t}\n{$tabs}}\n";
					$depth--;
				}
				
				if ( !$inHereDoc and $depth > 2 )
				{
					$contents .= "\t";
				}
				$contents .= $line;
				
				if ( !$inHereDoc )
				{
					if ( preg_match( '/<<<\'?([A-Z][A-Z0-9_]+)\'?$/i', trim( $line ), $matches ) )
					{
						$inHereDoc = $matches[1];
					}
				}
				
				if ( $depth == 2 and $openBraces )
				{
					$contents .= "{$tabs}try\n{$tabs}{\n";
					$depth++;
				}
				
				if ( $inHereDoc and trim( $line ) == $inHereDoc . ';' )
				{
					$inHereDoc = NULL;
				}
			}
			else
			{
				$contents .= $line;
			}			
		}
		
		return $contents;
	}
}