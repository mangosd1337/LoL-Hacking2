<?php
/**
 * @brief		Hook Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Hook Model
 */
class _Hook extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_hooks';
	
	/**
	 * Generate dev table
	 *
	 * @param $url
	 * @param $appOrPluginId
	 * @param $hookDir
	 * @return \IPS\Helpers\Table\Db
	 */
	public static function devTable( $url, $appOrPluginId, $hookDir )
	{
		switch ( \IPS\Request::i()->hookTable )
		{
			/* Create Hook */
			case 'add':
			
				/* Get the theme groups */
				$groups = array();
				foreach ( \IPS\Theme::load( \IPS\Theme::defaultTheme() )->getRawTemplates( null, null, null, \IPS\Theme::RETURN_ALL_NO_CONTENT ) as $app => $locations )
				{
					foreach ( $locations as $location => $_groups )
					{
						foreach ( $_groups as $group => $data )
						{
							$groups[ $app ][ $app . '_' . $location . '_' . $group ] = "{$location}: {$group}";
						}
					}
				}
				
				/* Build Form */
				$form = new \IPS\Helpers\Form();
				$form->add( new \IPS\Helpers\Form\Radio( 'plugin_hook_type', NULL, TRUE, array(
					'options'	=> array( 'C' => 'plugin_hook_type_c', 'S' => 'plugin_hook_type_s' ),
					'toggles'	=> array( 'C' => array( 'plugin_hook_class' ), 'S' => array( 'plugin_hook_group' ) )
				) ) );
				$form->add( new \IPS\Helpers\Form\Text( 'plugin_hook_class', NULL, FALSE, array(), function( $val )
				{
					if ( $val and !class_exists( 'IPS\\' . $val ) )
					{
						throw new \DomainException('plugin_hook_class_err');
					}
				}, 'IPS\\', NULL, 'plugin_hook_class' ) );
				$form->add( new \IPS\Helpers\Form\Select( 'plugin_hook_group', NULL, FALSE, array( 'options' => $groups, 'parse' => 'raw' ), NULL, NULL, NULL, 'plugin_hook_group' ) );
				$form->add( new \IPS\Helpers\Form\Text( 'plugin_hook_location', NULL, FALSE, array( 'maxLength' => 32, 'regex' => '/^[a-z0-9_]*$/i' ), NULL, NULL, '.php' ) );
				
				/* Handle submissions */
				if ( $values = $form->values() )
				{
					$hook = new static;
					if ( is_int( $appOrPluginId ) )
					{
						$hook->plugin = $appOrPluginId;
					}
					else
					{
						$hook->app = $appOrPluginId;
					}
					$hook->type = $values['plugin_hook_type'];
					$hook->class = ( $values['plugin_hook_type'] == 'C' ) ? ( '\IPS\\' . $values['plugin_hook_class'] ) : ( '\IPS\Theme\class_' . $values['plugin_hook_group'] );
					$hook->filename = $values['plugin_hook_location'] ?: md5( uniqid() );
					$hook->save();
					
					$abstract = '';
					if ( $values['plugin_hook_type'] == 'C' )
					{
						$reflection = new \ReflectionClass( $hook->class );
						$abstract = $reflection->isAbstract() ? 'abstract ' : '';
					}
					
					$classname = is_int( $appOrPluginId ) ? "hook{$hook->id}" : "{$appOrPluginId}_hook_{$hook->filename}";

					/* Work out the contents */
					$contents = str_replace(
						array(
							'{abstract}',
							'{classname}',
						),
						array(
							$abstract,
							$classname,
						),
						\file_get_contents( \IPS\ROOT_PATH . '/applications/core/data/defaults/Hook.txt' )
					);

					\file_put_contents( $hookDir . "/{$hook->filename}.php", $contents );
					static::writeDataFile();
					
					\IPS\Output::i()->redirect( $url );
				}
				
				/* Display */
				return $form;
								
			/* Delete Hook */
			case 'delete':
				
				try
				{
					$hook = static::load( \IPS\Request::i()->hook )->delete();
					\IPS\Output::i()->redirect( $url );
				}
				catch ( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'node_error', '2C145/7', 404, '' );
				}
			
			/* Table */
			default:
				$table = new \IPS\Helpers\Table\Db( static::$databaseTable, $url, ( is_string( $appOrPluginId ) ? array( 'app=?', $appOrPluginId ) : array( 'plugin=?', $appOrPluginId ) ) );
				$table->include = array( 'filename', 'class' );
				$table->langPrefix = 'plugin_hook_';
				$table->sortBy = $table->sortBy ?: 'class';

				/* Custom parsers */
				$table->parsers = array(
					'filename'	=> function( $val, $row )
					{
						return $val . '.php';
					}
				);
				
				$table->rootButtons = array(
					'add' => array(
						'title'	=> 'plugin_create_hook',
						'icon'	=> 'plus',
						'link'	=> $url->setQueryString( 'hookTable', 'add' ),
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('plugin_create_hook') )
					)
				);
				
				$table->rowButtons = function( $row ) use ( $url )
				{
					return array(
						'edit' => array(
							'title'	=> 'edit',
							'icon'	=> 'pencil',
							'link'	=> $url->setQueryString( 'do', 'editHook' )->setQueryString( 'hook', $row['id'] ),
						),
						'delete' => array(
							'title'	=> 'delete',
							'icon'	=> 'times-circle',
							'link'	=> $url->setQueryString( 'hookTable', 'delete' )->setQueryString( 'hook', $row['id'] ),
							'data'	=> array( 'delete' => '' )
						)
					);
				};
				
				return $table;
		}
	}
	
	/**
	 * Write data
	 *
	 * @return	void
	 */
	public static function writeDataFile()
	{
		if ( \IPS\NO_WRITES )
	    {
			throw new \RuntimeException; // Everywhere that calls this method should check in advance, but this is here just in case
	    }
	    
		$hooks = array();
		
		$stmt = \IPS\Db::i()->select(
			'core_hooks.*, core_plugins.*, core_applications.*',
			'core_hooks',
			'core_plugins.plugin_enabled=1 OR core_applications.app_enabled=1',
			'plugin_order'
		)->join(
			'core_plugins',
			'core_hooks.plugin=core_plugins.plugin_id'
		)->join(
			'core_applications',
			'core_hooks.app=core_applications.app_directory '
		);
		
		foreach ( $stmt as $row )
		{
			if ( $row['plugin'] )
			{
				$hooks[ trim( $row['class'] ) ][ $row['id'] ] = array(
					'file'	=> "plugins/{$row['plugin_location']}/hooks/{$row['filename']}.php",
					'class'	=> "hook{$row['id']}",
				);
			}
			else
			{
				$hooks[ trim( $row['class'] ) ][ $row['id'] ] = array(
					'file'	=> "applications/{$row['app']}/hooks/{$row['filename']}.php",
					'class'	=> $row['app'] . '_hook_' . $row['filename'],
				);
			}			
		}
		
		\file_put_contents( \IPS\ROOT_PATH . '/plugins/hooks.php', "<?php\nreturn " . var_export( $hooks, TRUE ) . ';' );
	}
	
	/**
	 * Get filename
	 *
	 * @return	string
	 * @throws	|Excepttion
	 */
	protected function getFilename()
	{
		if ( $this->plugin )
		{
			$plugin = \IPS\Plugin::load( $this->plugin );
			return \IPS\ROOT_PATH . "/plugins/{$plugin->location}/hooks/{$this->filename}.php";
		}
		else
		{
			return \IPS\ROOT_PATH . "/applications/{$this->app}/hooks/{$this->filename}.php";
		}
	}
	
	/**
	 * Edit form
	 *
	 * @return	string
	 */
	public function editForm( $url )
	{
		$url = $url->setQueryString( 'tab', NULL )->setQueryString( 'hook', $this->id );

		if( !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'system/plugins.css' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'controllers/system' ) );
			\IPS\Output::i()->title = $this->class;
			\IPS\Output::i()->breadcrumb[] = array( $url, \IPS\Member::loggedIn()->language()->addToStack('plugin_hooks') );
			\IPS\Output::i()->breadcrumb[] = array( NULL, $this->filename . '.php' );
		}
		
		/* Code Hooks */
		if ( $this->type === 'C' )
		{
			/* Build Form */
			$form = new \IPS\Helpers\Form;
			$form->add( new \IPS\Helpers\Form\Codemirror( 'code', file_get_contents( $this->getFilename() ), TRUE, array( 'mode' => 'php' ), array( $this, 'validate' ) ) );
			
			/* Handle submissions */
			if ( $values = $form->values() )
			{				
				\file_put_contents( $this->getFilename(), $values['code'] );
				static::writeDataFile();
				\IPS\Output::i()->redirect( $url );
			}
			
			/* Create Sidebar */
			$reflection = new \ReflectionClass( $this->class );
			$data = array();
			$defaultProperties = $reflection->getDefaultProperties();
			foreach ( $reflection->getProperties() as $property )
			{
				$property->signature = json_encode( '^\s+?((public|protected|private|static)\s+)+?\$' . $property->name . '(\s+?=.+?)?;\s*$' );
				$val = $property->name === 'multitons' ? array() : $defaultProperties[ $property->name ]; // It will try to use the current values rather than array()
				$property->codeToInject = json_encode( "\n\t{$property->getDocComment()}\n\t" . ( $property->isPublic() ? 'public ' : 'protected ' ) . ( $property->isStatic() ? 'static ' : '' ) . '$' . $property->name . ( $defaultProperties[ $property->name ] !== NULL ? ( ' = ' . var_export( $val, TRUE ) ) : '' ) . ";\n" );
				$data[ $property->getDeclaringClass()->getName() ]['properties'][ $property->getName() ] = $property;
			}
			foreach ( $reflection->getMethods() as $method )
			{
				if ( !$method->isInternal() )
				{
					$parameters = array();
					foreach ( $method->getParameters() as $parameter )
					{
						try
						{
							$parameters[] = ( $parameter->getClass() ? ( '\\' . $parameter->getClass()->getName() . ' ' ) : '' ) . ( $parameter->isPassedByReference() ? '&' : '' ) . '$' . $parameter->name . ( $parameter->isOptional() ? ( '=' . var_export( $parameter->getDefaultValue(), TRUE ) ) : '' );
						}
						catch ( \ReflectionException $e ) {}
					}
					
					$method->signature = json_encode( 'function ' . $method->name . '\(' );
					$method->codeToInject = json_encode( "\n\t{$method->getDocComment()}\n\t" . ( $method->isFinal() ? 'final  ' : '' ) . ( $method->isAbstract() ? 'abstract ' : '' ) . ( $method->isStatic() ? 'static ' : '' ) . ( $method->isPublic() ? 'public ' : 'protected ' ) . "function {$method->name}(" . ( empty( $parameters ) ? '' : ( ' ' . implode(', ', $parameters ) . ' ' ) ) . ")\n\t{\n\t\treturn call_user_func_array( 'parent::{$method->name}', func_get_args() );\n\t}\n" );
					
					$data[ $method->getDeclaringClass()->getName() ]['methods'][ $method->getName() ] = $method;
				}
			}
			$form->addSidebar( \IPS\Theme::i()->getTemplate( 'applications' )->codeHookSidebar( $data ) );
			
			/* Display */
			\IPS\Output::i()->output = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'applications' ), 'codeHookEditor' ) );
		}
		
		/* Theme Hooks */
		else
		{
			/* Init */
			$tabbedContent = '';
			$form = NULL;
			
			/* Get existing hook data */
			eval( "namespace IPS;\n\n" . str_replace( array( ' extends _HOOK_CLASS_', 'parent::hookData()' ), array( '', 'array()' ), file_get_contents( $this->getFilename() ) ) );
			$class = $this->app ? "\IPS\\{$this->app}_hook_{$this->filename}" : "\IPS\hook{$this->id}";
			$hookData = method_exists( $class, 'hookData' ) ? $class::hookData() : array();
			
			/* Which template group are we looking at? */
			$exploded = explode( '_', $this->class );
			$bits = \IPS\Theme::load( \IPS\Theme::defaultTheme() )->getRawTemplates( $exploded[1], $exploded[2], $exploded[3], \IPS\Theme::RETURN_ALL | \IPS\Theme::RETURN_NATIVE );
			
			/* If we have selected a template, show the form */
			if ( \IPS\Request::i()->template )
			{
				/* Deleting? */
				if ( isset( \IPS\Request::i()->delete ) )
				{
					unset( $hookData[ \IPS\Request::i()->template ][ \IPS\Request::i()->delete ] );

					if( isset( $hookData[ \IPS\Request::i()->template ] ) AND !count( $hookData[ \IPS\Request::i()->template ] ) )
					{
						unset( $hookData[ \IPS\Request::i()->template ] );
					}

					$this->_writeThemeHookData( $hookData );

					/* Boink */
					\IPS\Output::i()->redirect( $url->setQueryString( 'template', \IPS\Request::i()->template )->setQueryString( 'do', 'editHook' )->stripQueryString( 'delete' ) );
				}
				
				/* Build tabs of created hooks */
				$tabs = array( 'new' => 'add' );
				if ( isset( $hookData[ \IPS\Request::i()->template ] ) )
				{
					foreach ( $hookData[ \IPS\Request::i()->template ] as $i => $h )
					{
						$tabs[ 'sel' . $i ] = $h['selector'];
					}
				}
				
				/* Is this an edit? */
				$current = NULL;
				if ( isset( \IPS\Request::i()->tab ) and \IPS\Request::i()->tab != 'new' )
				{
					$current = $hookData[ \IPS\Request::i()->template ][ \substr( \IPS\Request::i()->tab, 3 ) ];
				}
				
				$_idSuffix	= $current ? md5( $current['selector'] ) : '';

				/* Build form for current tab */
				$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : '__new__';
				$form = new \IPS\Helpers\Form;
				if ( $current )
				{
					$form->addButton( 'delete', 'link', $url->setQueryString( 'do', 'editHook' )->setQueryString( 'template', \IPS\Request::i()->template )->setQueryString( 'delete', \substr( \IPS\Request::i()->tab, 3 ) ), 'ipsButton ipsButton_alternate' );
				}
				$form->class = 'ipsForm_vertical';
				$form->attributes['data-controller'] = 'core.admin.system.themeHook';
				$form->add( new \IPS\Helpers\Form\Text( 'plugin_theme_hook_selector', ( $current ? $current['selector'] : NULL ), TRUE, array( 'placeholder' => '#element' ), NULL, NULL, "<a href='#' data-ipsDialog data-ipsDialog-fixed data-ipsDialog-url='" . \IPS\Http\Url::internal( 'app=core&module=applications&controller=plugins&do=templateTree&class=' . urlencode( $this->class ) . "&template=" . urlencode( \IPS\Request::i()->template ) ) . "' data-ipsDialog-title='". \IPS\Member::loggedIn()->language()->addToStack('plugin_theme_hook_select') ."' class='ipsButton ipsButton_light ipsButton_small ipsHide' data-action='showTemplate'>" . \IPS\Member::loggedIn()->language()->addToStack('plugin_theme_hook_select') . "</a>", 'plugin_theme_hook_selector' . $_idSuffix ) );
				$form->add( new \IPS\Helpers\Form\Radio( 'plugin_theme_hook_type', ( $current ? $current['type'] : NULL ), TRUE, array(
					'options' => array(
						'add_before'		=> 'plugin_theme_hook_type_add_before',
						'add_inside_start'	=> 'plugin_theme_hook_type_add_inside_start',
						'add_inside_end'	=> 'plugin_theme_hook_type_add_inside_end',
						'add_after'			=> 'plugin_theme_hook_type_add_after',
						'add_class'			=> 'plugin_theme_hook_type_add_class',
						'remove_class'		=> 'plugin_theme_hook_type_remove_class',
						'add_attribute'		=> 'plugin_theme_hook_type_add_attribute',
						'remove_attribute'	=> 'plugin_theme_hook_type_remove_attribute',
						'replace'			=> 'plugin_theme_hook_type_replace',
					),
					'toggles'	=> array(
						'add_before'		=> array( 'cm-' . \IPS\Request::i()->template . $_idSuffix ),
						'add_inside_start'	=> array( 'cm-' . \IPS\Request::i()->template . $_idSuffix ),
						'add_inside_end'	=> array( 'cm-' . \IPS\Request::i()->template . $_idSuffix ),
						'add_after'			=> array( 'cm-' . \IPS\Request::i()->template . $_idSuffix ),
						'add_class'			=> array( 'plugin_theme_hook_css_class' . $_idSuffix ),
						'remove_class'		=> array( 'plugin_theme_hook_css_class' . $_idSuffix ),
						'add_attribute'		=> array( 'plugin_theme_hook_attribute_keys' . $_idSuffix ),
						'remove_attribute'	=> array( 'plugin_theme_hook_attribute_names' . $_idSuffix ),
						'replace'			=> array( 'cm-' . \IPS\Request::i()->template . $_idSuffix ),
					),
				), NULL, NULL, NULL, 'plugin_theme_hook_type' . $_idSuffix ) );
				$options = array();
				if ( trim( $bits[ $exploded[1] ][ $exploded[2] ][ $exploded[3] ][ \IPS\Request::i()->template ]['template_data'] ) !== '' )
				{
					foreach( explode( ',', trim( $bits[ $exploded[1] ][ $exploded[2] ][ $exploded[3] ][ \IPS\Request::i()->template ]['template_data'] ) ) as $tag )
					{
						$tag = '{' . trim( preg_replace( '/^(.*)=.*$/', '$1', $tag ) ) . '}';
						$options['tags'][ $tag ] = $tag;
					}
				}
				$form->add( new \IPS\Helpers\Form\Codemirror( 'plugin_theme_hook_content', ( isset( $current['content'] ) ? $current['content'] : NULL ), NULL, $options, NULL, NULL, NULL, 'cm-' . \IPS\Request::i()->template . $_idSuffix ) );
				$form->add( new \IPS\Helpers\Form\Stack( 'plugin_theme_hook_css_class', ( isset( $current['css_classes'] ) ? $current['css_classes'] : NULL ), NULL, array(), NULL, NULL, NULL, 'plugin_theme_hook_css_class' . $_idSuffix ) );
				$form->add( new \IPS\Helpers\Form\Stack( 'plugin_theme_hook_attribute_keys', ( isset( $current['attributes_add'] ) ? $current['attributes_add'] : NULL ), NULL, array( 'stackFieldType' => 'KeyValue' ), NULL, NULL, NULL, 'plugin_theme_hook_attribute_keys' . $_idSuffix ) );
				$form->add( new \IPS\Helpers\Form\Stack( 'plugin_theme_hook_attribute_names', ( isset( $current['attributes_remove'] ) ? $current['attributes_remove'] : NULL ), NULL, array(), NULL, NULL, NULL, 'plugin_theme_hook_attribute_names' . $_idSuffix ) );

				/* Handle Submissions */
				if ( $values = $form->values() )
				{
					$template = \IPS\Request::i()->template;
					
					/* Add */
					$save = array(
						'selector'	=> $values['plugin_theme_hook_selector'],
						'type'		=> $values['plugin_theme_hook_type'],
					);
					switch ( $values['plugin_theme_hook_type'] )
					{
						case 'add_before':
						case 'add_inside_start':
						case 'add_inside_end':
						case 'add_after':
						case 'replace':
							$save['content'] = $values['plugin_theme_hook_content'];
							break;
						case 'add_class':
						case 'remove_class':
							$save['css_classes'] = $values['plugin_theme_hook_css_class'];
							break;
						case 'add_attribute':
							$save['attributes_add'] = $values['plugin_theme_hook_attribute_keys'];
							break;
						case 'remove_attribute':
							$save['attributes_remove'] = $values['plugin_theme_hook_attribute_names'];
							break;
					}
					if ( isset( \IPS\Request::i()->tab ) and \IPS\Request::i()->tab != 'new' )
					{
						$hookData[ $template ][ \substr( \IPS\Request::i()->tab, 3 ) ] = $save;
					}
					else
					{
						$hookData[ $template ][] = $save;
					}

					/* Write */
					$this->_writeThemeHookData( $hookData );
					
					/* Boink */
					\IPS\Output::i()->redirect( $url->setQueryString( 'template', $template )->setQueryString( 'do', 'editHook' ) );
				}
				
				/* Construct tab interface */
				if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->tab ) )
				{
					\IPS\Output::i()->output = $form;
					return;
				}
				$tabbedContent = \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $form, $url->setQueryString( 'do', 'editHook' )->setQueryString( 'template', \IPS\Request::i()->template ) );
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->output = $tabbedContent;
					return;
				}
			}
			
			/* Display */
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->textBlock( \IPS\Member::loggedIn()->language()->addToStack( 'plugin_hook_info_' . $this->type, FALSE, array( 'sprintf' => array( $this->getFilename() ) ) ), 'info', NULL, FALSE ) . \IPS\Theme::i()->getTemplate( 'applications' )->themeHookEditor( $bits[ $exploded[1] ][ $exploded[2] ][ $exploded[3] ], $hookData, $this->id, \IPS\Request::i()->template, $tabbedContent, $url );
		}
	}
	
	/**
	 * Write theme hook data
	 *
	 * @param	array	$hookData	Hook data
	 * @return	void
	 */
	protected function _writeThemeHookData( $hookData )
	{
		/* Build */
		if( !count( $hookData ) )
		{
			$hookData = "/* !Hook Data - DO NOT REMOVE */\n\n/* End Hook Data */";
		}
		else
		{
			$hookData = "/* !Hook Data - DO NOT REMOVE */\npublic static function hookData() {\n return array_merge_recursive( " . var_export( $hookData, TRUE ) . ", parent::hookData() );\n}\n/* End Hook Data */";
		}
		
		/* Write */
		$contents = file_get_contents( $this->getFilename() );
		if ( \strpos( $contents, '/* !Hook Data - DO NOT REMOVE */' ) !== FALSE )
		{
			$contents = preg_replace( '/\/\* !Hook Data - DO NOT REMOVE \*\/.*\/\* End Hook Data \*\//s', $hookData, $contents );
		}
		else
		{
			$contents = preg_replace( '/(_HOOK_CLASS_\s+{)/', "$1\n\n" . $hookData, $contents );
		}
		
		\file_put_contents( $this->getFilename(), $contents );
		static::writeDataFile();
	}
	
	/**
	 * Validate
	 *
	 * @param	string	$val	Code
	 * @return	bool
	 * @throws	\LogicException
	 */
	public function validate( $val )
	{
		try
		{
			if ( eval( str_replace( 'extends _HOOK_CLASS_', '', $val ) ) === FALSE )
			{
				throw new \DomainException( 'plugin_hook_code_err_parse' );
			}
		}
		catch ( \ParseError $e )
		{
			throw new \DomainException( 'plugin_hook_code_err_parse' );
		}
		
		$classname = ( $this->plugin ) ? 'hook' . $this->id  : $this->app . '_hook_' . $this->filename;
		if ( !class_exists( $classname , FALSE ) )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack('plugin_hook_code_err_class', FALSE, array( 'sprintf' => array( $classname ) ) ) );
		}
						
		return TRUE;
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
		@unlink( $this->getFilename() );
		static::writeDataFile();
	}
}