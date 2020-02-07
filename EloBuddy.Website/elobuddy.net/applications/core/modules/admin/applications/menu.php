<?php
/**
 * @brief		Menu Manager
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Jun 2015
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
 * menu
 */
class _menu extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'menu_manage' );
		\IPS\Output::i()->responsive = FALSE;
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'system/menumanager.css', 'core', 'admin' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_system.js', 'core', 'admin' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery.nestedSortable.js', 'core', 'interface' ) );
		parent::execute();
	}

	/**
	 * Manage Menu
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Buttons */
		\IPS\Output::i()->sidebar['actions']['publish'] = array(
			'icon'	=> 'cloud-upload',
			'link'	=> \IPS\Http\Url::internal('app=core&module=applications&controller=menu&do=publish'),
			'title'	=> 'menu_manager_publish',
			'class' => ( isset( \IPS\Data\Store::i()->frontNavigation ) && \IPS\Data\Store::i()->frontNavigation == \IPS\core\FrontNavigation::frontNavigation( TRUE ) ) ? 'ipsButton_disabled' : '',
			'data' 	=> array(
				'action' => 'publishMenu'
			)
		);
		\IPS\Output::i()->sidebar['actions']['restore'] = array(
			'icon'	=> 'refresh',
			'link'	=> \IPS\Http\Url::internal('app=core&module=applications&controller=menu&do=restore'),
			'title'	=> 'menu_manager_revert',
			'data' 	=> array(
				'action' => 'restoreMenu'
			)
		);

		/* Get items from the database */
		$items = array( 0 => array(), 1 => array() );
		$menus = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_menu', NULL, 'position' ) as $item )
		{
			if ( \IPS\Application::appIsEnabled( $item['app'] ) )
			{
				if ( !$item['is_menu_child'] )
				{
					$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
					$items[ intval( $item['parent'] ) ][ $item['id'] ] = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'] );
				}
				if ( $item['app'] == 'core' and $item['extension'] == 'Menu' )
				{
					$class = 'IPS\\' . $item['app'] . '\extensions\core\FrontNavigation\\' . $item['extension'];
					$menus[ $item['id'] ] = new $class( json_decode( $item['config'], TRUE ), $item['id'], $item['permissions'] );
				}
			}
		}
				
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__core_applications_menu');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'applications' )->menuManager( $items, $menus );
	}
	
	/**
	 * Publish Menu
	 *
	 * @return	void
	 */
	protected function publish()
	{
		if ( \IPS\Db::i()->select( 'COUNT(*)', 'core_menu' )->first() )
		{
			unset( \IPS\Data\Store::i()->frontNavigation );
		}
		else
		{
			\IPS\Data\Store::i()->frontNavigation = array( 0 => array(), 1 => array() );
		}

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		\IPS\Session::i()->log( 'acplog__menu_published' );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=applications&controller=menu'), 'menu_manager_published' );
	}
	
	/**
	 * Add Menu Item
	 *
	 * @return	void
	 */
	protected function form()
	{
		/* What menu items have we already configured? */
		$current = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_menu', NULL, 'position' ) as $item )
		{
			if ( !isset( $current[ $item['app'] ][ $item['extension'] ] ) )
			{
				$current[ $item['app'] ][ $item['extension'] ] = 0;
			}
			$current[ $item['app'] ][ $item['extension'] ]++;
		}
		
		/* Are we editing an existing item? */
		$existing = NULL;
		if ( \IPS\Request::i()->id )
		{
			try
			{
				$existing = \IPS\Db::i()->select( '*', 'core_menu', array( 'id=?', \IPS\Request::i()->id ) )->first();
			}
			catch ( \OutOfRangeException $e ) { }
		}
		
		/* What options are available? */		
		$options = array();
		$toggles = array();
		$fieldNames = array();
		$extraFields = array();
		foreach ( \IPS\Application::allExtensions( 'core', 'FrontNavigation', FALSE, 'core', NULL, FALSE ) as $key => $class )
		{
			if ( method_exists( $class, 'typeTitle' ) )
			{
				$exploded = explode( '_', $key );
				if ( $class::allowMultiple() or !isset( $current[ $exploded[0] ][ $exploded[1] ] ) or ( $existing and $existing['app'] == $exploded[0] and $existing['extension'] == $exploded[1] ) )
				{
					$options[ $key ] = $class::typeTitle();
					
					foreach ( $class::configuration( $existing ? json_decode( $existing['config'], TRUE ) : array(), $existing ? $existing['id'] : NULL ) as $field )
					{
						if ( !$field->htmlId )
						{
							$field->htmlId = md5( uniqid() );
						}
						
						$toggles[ $key ][ $field->name ] = $field->htmlId;
						
						$fieldNames[ $key ][] = $field->name;
						$extraFields[] = $field;
					}
					
					if ( $class::permissionsCanInherit() )
					{
						$toggles[ $key ][] = 'menu_manager_access_type';
					}
					else
					{
						$toggles[ $key ][] = 'menu_manager_access';
					}
				}
			}
		}
		
		/* Create the form */
		$form = new \IPS\Helpers\Form( 'menu_item', 'save_menu_item' );
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Radio( 'menu_manager_extension', $existing ? "{$existing['app']}_{$existing['extension']}" : NULL, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		foreach ( $extraFields as $field )
		{
			$form->add( $field );
		}
		$groups = array();
		foreach ( \IPS\Member\Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		$form->add( new \IPS\Helpers\Form\Radio( 'menu_manager_access_type', ( $existing and $existing['permissions'] ) ? 1 : 0, TRUE, array(
			'options'	=> array( 0 => 'menu_manager_access_type_inherit', 1 => 'menu_manager_access_type_override' ),
			'toggles'	=> array( 1 => array( 'menu_manager_access' ) )
		), NULL, NULL, NULL, 'menu_manager_access_type' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'menu_manager_access', $existing ? ( $existing['permissions'] == '*' ? '*' : explode( ',', $existing['permissions'] ) ) : '*', NULL, array( 'multiple' => TRUE, 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'everyone' ), NULL, NULL, NULL, 'menu_manager_access' ) );
		
		if( !$existing ){
			$form->hiddenValues['newItem'] = TRUE;
		}

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$exploded = explode( '_', $values['menu_manager_extension'] );
			$class = 'IPS\\' . $exploded[0] . '\extensions\core\FrontNavigation\\' . $exploded[1];
			
			$config = array();
			if ( isset( $fieldNames[ $values['menu_manager_extension'] ] ) )
			{
				foreach ( $values as $k => $v )
				{
					if ( in_array( $k, $fieldNames[ $values['menu_manager_extension'] ] ) )
					{
						$config[ $k ] = $v;
					}
				}
			}
			
			$save = array(
				'app'			=> $exploded[0],
				'extension'		=> $exploded[1],
				'config'		=> json_encode( $config ),
				'parent'		=> $existing ? $existing['parent'] : ( \IPS\Request::i()->parent ?: NULL ),
				'is_menu_child'	=> FALSE
			);
			if ( $save['parent'] )
			{
				try
				{
					$parent = \IPS\Db::i()->select( '*', 'core_menu', array( 'id=?', $save['parent'] ) )->first();
					if ( $parent['app'] === 'core' and $parent['extension'] === 'Menu' )
					{
						$save['is_menu_child'] = TRUE;
					}
				}
				catch ( \UnderflowException $e ) { }
			}
			
			if ( $class::permissionsCanInherit() )
			{
				if ( $values['menu_manager_access_type'] )
				{
					$save['permissions'] = $values['menu_manager_access'] == '*' ? '*' : implode( ',', $values['menu_manager_access'] );
				}
				else
				{
					$save['permissions'] = '';
				}
			}
			else
			{
				$save['permissions'] = $values['menu_manager_access'] == '*' ? '*' : implode( ',', $values['menu_manager_access'] );
			}
			if ( $existing )
			{
				$id = $existing['id'];
				
				$_config = $class::parseConfiguration( $config, $id );
				
				if ( $_config != $config )
				{
					$save['config'] = json_encode( $_config );
				}
				
				\IPS\Db::i()->update( 'core_menu', $save, array( 'id=?', $id ) );
			}
			else
			{
				try
				{
					$save['position'] = \IPS\Db::i()->select( 'MAX(position)', 'core_menu', array( 'parent=?', \IPS\Request::i()->parent ) )->first() + 1;
				}
				catch ( \UnderflowException $e )
				{
					$save['position'] = 1;
				}
				
				$id = \IPS\Db::i()->insert( 'core_menu', $save );
				
				$_config = $class::parseConfiguration( $config, $id );
			
				if ( $_config != $config )
				{
					\IPS\Db::i()->update( 'core_menu', array( 'config' => json_encode( $_config ) ), array( 'id=?', $id ) );
				}
			}
				
			if( \IPS\Request::i()->isAjax() )
			{
				$item = new $class( $_config, $id, $save['permissions'] );
				$output = array(
					'menu_item' => \IPS\Theme::i()->getTemplate( 'applications' )->menuItem( $item, $id ),
					'id' => $id
				);
				
				/* If the item has a parent and it's a dropdown menu, then the menu_item
					we need to return needs to be the one used for dropdown menus */
				if ( $save['parent'] )
				{
					try
					{
						$parent = \IPS\Db::i()->select( '*', 'core_menu', array( 'id=?', $save['parent'] ) )->first();
						$parentClass = 'IPS\\' . $parent['app'] . '\extensions\core\FrontNavigation\\' . $parent['extension'];
						$parentItem = new $parentClass( json_decode( $parent['config'], TRUE ), $parent['id'], $parent['permissions'] );

						if ( $parent['app'] === 'core' and $parent['extension'] === 'Menu' )
						{
							$output['menu_item'] = \IPS\Theme::i()->getTemplate( 'applications' )->menuManagerDropdownItem( $item, $id );
						}
					}
					catch ( \UnderflowException $e ) { }
				}

				/* If this is a dropdown item, we also need to return the HTML for the dropdown edit screen */
				if ( $exploded[1] == 'Menu' )
				{
					$output['dropdown_menu'] = \IPS\Theme::i()->getTemplate( 'applications' )->menuManagerDropdown( $item, $id, ( isset( $parentItem ) ? $parentItem : 0 ) );
				}
				
				\IPS\Output::i()->json( $output );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=applications&controller=menu') );
			}
		}
		
		/* Display */
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'applications', 'core' ) ), 'menuManagerForm' ) );
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( \IPS\Output::i()->output );
		}
	}
	
	/**
	 * Remove an item
	 *
	 * @return	void
	 */
	protected function remove()
	{
		static::_remove( intval( \IPS\Request::i()->id ) );
		\IPS\Output::i()->json('OK');
	}
	
	/**
	 * Remove a menu item
	 *
	 * @param	int	$id	ID of item to remove
	 * @return	void
	 */
	protected static function _remove( $id )
	{
		foreach ( \IPS\Db::i()->select( 'id', 'core_menu', array( 'parent=?', $id ) ) as $child )
		{
			static::_remove( $child );
		}
		\IPS\Db::i()->delete( 'core_menu', array( 'id=?', $id ) );
	}
	
	/**
	 * Reorder Items
	 *
	 * @return	void
	 */
	protected function reorder()
	{
		$positions = array();
		foreach ( \IPS\Request::i()->menu_order as $id => $parentId )
		{
			if ( !isset( $positions[ $parentId ] ) )
			{
				$positions[ $parentId ] = 1;
			}

			$save = array(
				'position' => $positions[ $parentId ]++,
				'parent'   => NULL
			);

			if ( $parentId != 'null' )
			{
				try
				{
					$parent         = \IPS\Db::i()->select( '*', 'core_menu', array( 'id=?', $parentId ) )->first();
					$save['parent'] = $parent['id'];

					if ( $parent['app'] === 'core' and $parent['extension'] === 'Menu' )
					{
						$save['is_menu_child'] = TRUE;
					}
				}
				catch ( \UnderflowException $e ) {}
			}

			\IPS\Db::i()->update( 'core_menu', $save, array( 'id=?', $id ) );
		}
		\IPS\Output::i()->json('OK');
	}
	
	/**
	 * Restore Default Menu
	 *
	 * @return	void
	 */
	protected function restore()
	{
		\IPS\core\FrontNavigation::buildDefaultFrontNavigation();
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=applications&controller=menu'), 'menu_manager_reverted' );
	}
}