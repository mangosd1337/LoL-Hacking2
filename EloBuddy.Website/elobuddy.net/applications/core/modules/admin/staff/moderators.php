<?php
/**
 * @brief		moderators
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		24 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\staff;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * moderators
 */
class _moderators extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_moderators', \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators' ) );
		
		/* Columns */
		$table->langPrefix = 'moderators_';
		$table->joins = array(
			array( 'select' => "IF(core_moderators.type= 'g', w.word_custom, m.name) as name", 'from' => array( 'core_members', 'm' ), 'where' => "m.member_id=core_moderators.id AND core_moderators.type='m'" ),
			array( 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_group_', core_moderators.id ) AND core_moderators.type='g' AND w.lang_id=" . \IPS\Member::loggedIn()->language()->id )
		);
		$table->include = array( 'type', 'id', 'updated' );
		$table->parsers = array(
			'type'	=> function( $val, $row )
			{
				return "<i class='fa fa-" . ( ( $val === 'm' ) ? 'user' : 'group' ) . "' data-ipstooltip _title=' " . ( ( $val === 'm' ) ?  \IPS\Member::loggedIn()->language()->addToStack('acprestrictions_member') :  \IPS\Member::loggedIn()->language()->addToStack('acprestrictions_group') ) .  "'></i>";
			},
			'id'		=> function( $val, $row )
			{
				return $row['type'] === 'g' ? \IPS\Member\Group::load( $row['id'] )->formattedName : htmlentities( $row['name'], \IPS\HTMLENTITIES, 'UTF-8', FALSE );
			},
			'updated'	=> function( $val )
			{
				return ( $val ) ? \IPS\DateTime::ts( $val )->localeDate() : \IPS\Member::loggedIn()->language()->addToStack('never');
			}
		);
		$table->mainColumn = 'name';
		$table->quickSearch = array( array( 'name', 'word_custom' ), 'id' );
		$table->classes = array( 'type' => 'icon' );
		
		/* Sorting */
		$table->sortBy = $table->sortBy ?: 'updated';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		/* Buttons */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) or \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
		{
			$table->rootButtons = array(
				'add'	=> array(
					'icon'	=> 'plus',
					'link'	=> \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=add' ),
					'title'	=> 'add_moderator',
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add_moderator') )
				),
			);
		}
		$table->rowButtons = function( $row )
		{
			$buttons = array(
				'edit'	=> array(
					'icon'	=> 'pencil',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=staff&controller=moderators&do=edit&id={$row['id']}&type={$row['type']}" ),
					'title'	=> 'edit',
					'class'	=> '',
				),
				'delete'	=> array(
					'icon'	=> 'times-circle',
					'link'	=> \IPS\Http\Url::internal( "app=core&module=staff&controller=moderators&do=delete&id={$row['id']}&type={$row['type']}" ),
					'title'	=> 'delete',
					'data'	=> array( 'delete' => '' ),
				)
			);
			
			if ( $row['type'] === 'm' )
			{
				if ( !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_edit_member' ) )
				{
					unset( $buttons['edit'] );
				}
				if ( !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_delete_member' ) )
				{
					unset( $buttons['delete'] );
				}
			}
			else
			{
				if ( !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_edit_group' ) )
				{
					unset( $buttons['edit'] );
				}
				if ( !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_delete_group' ) )
				{
					unset( $buttons['delete'] );
				}
			}

			return $buttons;
		};
		
		/* Buttons for logs */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'restrictions_moderatorlogs' ) )
		{
			\IPS\Output::i()->sidebar['actions']['actionLogs'] = array(
					'title'		=> 'modlogs',
					'icon'		=> 'search',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ),
			);
		}

		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('moderators');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'moderators', (string) $table );
	}
	
	/**
	 * Add
	 *
	 * @return	void
	 */
	protected function add()
	{
		$form = new \IPS\Helpers\Form();
				
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'moderators_type', NULL, TRUE, array( 'options' => array( 'g' => 'group', 'm' => 'member' ), 'toggles' => array( 'g' => array( 'moderators_group' ), 'm' => array( 'moderators_member' ) ) ) ) );
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) )
		{
			$form->add( new \IPS\Helpers\Form\Select( 'moderators_group', NULL, FALSE, array( 'options' => \IPS\Member\Group::groups( TRUE, FALSE ), 'parse' => 'normal' ), NULL, NULL, NULL, 'moderators_group' ) );
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_group' ) )
		{
			$form->add( new \IPS\Helpers\Form\Member( 'moderators_member', NULL, ( \IPS\Request::i()->moderators_type === 'member' ), array(), NULL, NULL, NULL, 'moderators_member' ) );
		}
		
		if ( $values = $form->values() )
		{
			$rowId = NULL;
			
			if ( $values['moderators_type'] === 'g' or !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'moderators_add_member' ) )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_add_group' );
				$rowId = $values['moderators_group'];
			}
			elseif ( $values['moderators_member'] )
			{
				\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_add_member' );
				$rowId = $values['moderators_member']->member_id;
			}

			if ( $rowId !== NULL )
			{
				try
				{
					$current = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", $rowId, $values['moderators_type'] ) )->first();
				}
				catch( \UnderflowException $e )
				{
					$current	= array();
				}

				if ( !count( $current ) )
				{
					$current = array(
						'id'		=> $rowId,
						'type'		=> $values['moderators_type'],
						'perms'		=> '*',
						'updated'	=> time()
					);
					
					\IPS\Db::i()->insert( 'core_moderators', $current );
					
					foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
					{
						$ext->onChange( $current, $values );
					}
					
					\IPS\Session::i()->log( 'acplog__moderator_created', array( ( $values['moderators_type'] == 'g' ? \IPS\Member::loggedIn()->language()->get( "core_group_{$values['moderators_group']}" ) : $values['moderators_member']->name ) => FALSE ) );

					unset (\IPS\Data\Store::i()->moderators);
				}

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=staff&controller=moderators&do=edit&id={$current['id']}&type={$current['type']}" ) );
			}
		}

		\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('add_moderator');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('global')->block( 'add_moderator', $form, FALSE );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit()
	{
		try
		{
			$current = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", intval( \IPS\Request::i()->id ), \IPS\Request::i()->type ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C118/2', 404, '' );
		}

		/* Load */
		try
		{
			$_name = ( $current['type'] === 'm' ) ? \IPS\Member::load( $current['id'] )->name : \IPS\Member\Group::load( $current['id'] )->name;
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C118/2', 404, '' );
		}

		$currentPermissions = ( $current['perms'] === '*' ) ? '*' : ( $current['perms'] ? json_decode( $current['perms'], TRUE ) : array() );
				
		/* Define content field toggles */
		$toggles = array( 'view_future' => array(), 'future_publish' => array(), 'pin' => array(), 'unpin' => array(), 'feature' => array(), 'unfeature' => array(), 'edit' => array(), 'hide' => array(), 'unhide' => array(), 'view_hidden' => array(), 'move' => array(), 'lock' => array(), 'unlock' => array(), 'reply_to_locked' => array(), 'delete' => array(), 'split_merge' => array() );
		foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
		{
			if ( $ext instanceof \IPS\Content\ModeratorPermissions )
			{
				foreach ( $ext->actions as $s )
				{
					$class = $ext::$class;
					$toggles[ $s ][] = "can_{$s}_{$class::$title}";
				}
				
				if ( isset( $class::$commentClass ) )
				{
					foreach ( $ext->commentActions as $s )
					{
						$commentClass = $class::$commentClass;
						$toggles[ $s ][] = "can_{$s}_{$commentClass::$title}";
					}
				}
				
				if ( isset( $class::$reviewClass ) )
				{
					foreach ( $ext->reviewActions as $s )
					{
						$reviewClass = $class::$reviewClass;
						$toggles[ $s ][] = "can_{$s}_{$reviewClass::$title}";
					}
				}
			}
		}

		/* We need to remember which keys are 'nodes' so we can adjust values upon submit */
		$nodeFields = array();
		
		/* Build */
		$form = new \IPS\Helpers\Form;
		$extensions = array();
		foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE, 'core' ) as $k => $ext )
		{
			$extensions[ $k ] = $ext;
		}
		
		if ( isset( $extensions['core_General'] ) )
		{
			$meFirst = array( 'core_General' => $extensions['core_General'] );
			unset( $extensions['core_General'] );
			$extensions = $meFirst + $extensions;
		}
		
		foreach( $extensions as $k => $ext )
		{
			$form->addTab( 'modperms__' . $k );
						
			foreach ( $ext->getPermissions( $toggles ) as $name => $data )
			{
				/* Class */
				$type = is_array( $data ) ? $data[0] : $data;
				$class = '\IPS\Helpers\Form\\' . ( $type );

				/* Remember 'nodes' */
				if( $type == 'Node' )
				{
					$nodeFields[ $name ]	= $name;
				}
				
				/* Current Value */
				if ( $currentPermissions === '*' )
				{
					switch ( $type )
					{
						case 'YesNo':
							$currentValue = TRUE;
							break;
							
						case 'Number':
							$currentValue = -1;
							break;
						
						case 'Node':
							$currentValue = 0;
							break;
					}
				}
				else
				{
					$currentValue = ( isset( $currentPermissions[ $name ] ) ? $currentPermissions[ $name ] : NULL );

					/* We translate nodes to -1 so the moderator permissions merging works as expected allowing "all" to override individual node selections */
					if( $type == 'Node' AND $currentValue == -1 )
					{
						$currentValue = 0;
					}
				}
				
				/* Options */
				$options = is_array( $data ) ? $data[1] : array();
				if ( $type === 'Number' )
				{
					$options['unlimited'] = -1;
				}
				
				/* Prefix/Suffix */
				$prefix = NULL;
				$suffix = NULL;
				if ( is_array( $data ) )
				{
					if ( isset( $data[2] ) )
					{
						$prefix = $data[2];
					}
					if ( isset( $data[3] ) )
					{
						$suffix = $data[3];
					}
				}
				
				/* Add */
				$form->add( new $class( $name, $currentValue, FALSE, $options, NULL, $prefix, $suffix, $name ) );
			}
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			foreach ( $values as $k => $v )
			{
				/* For node fields, if the value is 0 translate it to -1 so mod permissions can merge properly */
				if( in_array( $k, $nodeFields ) )
				{
					/* If nothing is checked we have '', but if 'all' is checked then the value is 0 */
					if( $v === 0 )
					{
						$v = -1;
						$values[ $k ] = $v;
					}
				}

				if ( is_array( $v ) )
				{
					foreach ( $v as $l => $w )
					{
						if ( $w instanceof \IPS\Node\Model )
						{
							$values[ $k ][ $l ] = $w->_id;
						}
					}
				}
			}
			
			if ( $currentPermissions == '*' )
			{
				$changed = $values;
			}
			else
			{
				$changed = array();
				foreach ( $values as $k => $v )
				{
					if ( !isset( $currentPermissions[ $k ] ) or $currentPermissions[ $k ] != $v )
					{
						$changed[ $k ] = $v;
					}
				}
			}
			
			\IPS\Db::i()->update( 'core_moderators', array( 'perms' => json_encode( $values ), 'updated' => time() ), array( array( "id=? AND type=?", $current['id'], $current['type'] ) ) );
			foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
			{
				$ext->onChange( $current, $changed );
			}
			
			\IPS\Session::i()->log( 'acplog__moderator_edited', array( ( $current['type'] == 'g' ? \IPS\Member::loggedIn()->language()->get( "core_group_{$current['id']}" ) : \IPS\Member::load( $current['id'] )->name ) => FALSE ) );
			$currentPermissions = $values;

			unset (\IPS\Data\Store::i()->moderators);

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators' ), 'saved' );
		}
		
		/* Warning if they have all permissions */
		if ( $currentPermissions === '*' )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( 'moderators_allperm_warning', 'warning' );
		}
		/* Or a button to give all */
		else
		{
			\IPS\Output::i()->sidebar['actions'] = array(
				'all'	=> array(
					'title'		=> 'moderators_allperm',
					'icon'		=> 'asterisk',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=allperms&id=' ) . $current['id'] . '&type=' . $current['type'],
					'data'		=> array( 'confirm' => '' )
				),
			);
		}
		
		/* Display */
		\IPS\Output::i()->title		= $_name;
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_members.js', 'core', 'admin' ) );
		\IPS\Output::i()->output 	.= \IPS\Theme::i()->getTemplate( 'members' )->moderatorPermissions( $_name, $form );
	}
	
	/**
	 * Give All Permissions
	 *
	 * @return	void
	 */
	protected function allperms()
	{
		/* Load */
		$current = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", intval( \IPS\Request::i()->id ), \IPS\Request::i()->type ) )->first();
		if ( !$current )
		{
			\IPS\Output::i()->error( 'node_error', '2C118/3', 404, '' );
		}
		
		/* Set */
		\IPS\Db::i()->update( 'core_moderators', array( 'perms' => '*', 'updated' => time() ), array( array( "id=? AND type=?", $current['id'], $current['type'] ) ) );
		foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
		{
			$ext->onChange( $current, '*' );
		}

		unset ( \IPS\Data\Store::i()->moderators );

		/* Log and redirect */
		\IPS\Session::i()->log( 'acplog__moderator_edited', array( ( $current['type'] == 'g' ? \IPS\Member::loggedIn()->language()->get( "core_group_{$current['id']}" ) : \IPS\Member::load( $current['id'] )->name ) => FALSE ) );
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=edit&id=' . $current['id'] . '&type=' . $current['type'] ) );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		/* Load */
		try
		{
			$current = \IPS\Db::i()->select( '*', 'core_moderators', array( "id=? AND type=?", intval( \IPS\Request::i()->id ), \IPS\Request::i()->type ) )->first();
		}
		catch( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C118/4', 404, '' );
		}

		/* Check acp restrictions */
		if ( $current['type'] === 'm' )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_delete_member' );
		}
		else
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'moderators_delete_group' );
		}

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Delete */
		\IPS\Db::i()->delete( 'core_moderators', array( array( "id=? AND type=?", $current['id'], $current['type'] ) ) );
		foreach ( \IPS\Application::allExtensions( 'core', 'ModeratorPermissions', FALSE ) as $k => $ext )
		{
			$ext->onDelete( $current );
		}

		unset (\IPS\Data\Store::i()->moderators);
		
		/* Log and redirect */
		\IPS\Session::i()->log( 'acplog__moderator_deleted', array( ( $current['type'] == 'g' ? \IPS\Member::loggedIn()->language()->get( "core_group_{$current['id']}" ) : \IPS\Member::load( $current['id'] )->name ) => FALSE ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators' ) );
	}
	
	/**
	 * Action Logs
	 *
	 * @return	void
	 */
	protected function actionLogs()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'restrictions_moderatorlogs' );
	
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_moderator_logs', \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ) );
		$table->langPrefix = 'modlogs_';
		$table->include = array( 'member_id', 'action', 'ip_address', 'ctime' );
		$table->mainColumn = 'action';
		$table->parsers = array(
				'member_id'	=> function( $val )
				{
					try
					{
						return htmlentities( \IPS\Member::load( $val )->name, \IPS\HTMLENTITIES, 'UTF-8', FALSE );
					}
					catch ( \OutOfRangeException $e )
					{
						return '';
					}
				},
				'action'	=> function( $val, $row )
				{
					if ( $row['lang_key'] )
					{
						$langKey = $row['lang_key'];
						$params = array();
                        $note = json_decode( $row['note'], TRUE );
                        if ( !empty( $note ) )
                        {
                            foreach ($note as $k => $v)
                            {
                                $params[] = $v ? \IPS\Member::loggedIn()->language()->addToStack($k) : $k;
                            }
                        }
						return \IPS\Member::loggedIn()->language()->addToStack( $langKey, FALSE, array( 'sprintf' => $params ) );
					}
					else
					{
						return $row['note'];
					}
				},
				'ip_address'=> function( $val )
				{
					if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_ip' ) )
					{
						return "<a href='" . \IPS\Http\Url::internal( "app=core&module=members&controller=ip&ip={$val}" ) . "'>{$val}</a>";
					}
					return $val;
				},
				'ctime'		=> function( $val )
				{
					return (string) \IPS\DateTime::ts( $val );
				}
		);
		$table->sortBy = $table->sortBy ?: 'ctime';
		$table->sortDirection = $table->sortDirection ?: 'desc';
	
		/* Search */
		$table->advancedSearch	= array(
				'member_id'			=> \IPS\Helpers\Table\SEARCH_MEMBER,
				'ip_address'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
				'ctime'				=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
		);
	
		/* Add a button for settings */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'staff', 'restrictions_moderatorlogs_prune' ) )
		{
			\IPS\Output::i()->sidebar['actions'] = array(
					'settings'	=> array(
							'title'		=> 'prunesettings',
							'icon'		=> 'cog',
							'link'		=> \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogSettings' ),
							'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('prunesettings') )
					),
			);
		}
	
		/* Display */
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ), \IPS\Member::loggedIn()->language()->addToStack( 'modlogs' ) );
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'modlogs' );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'modlogs', (string) $table );
	}
	
	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function actionLogSettings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'restrictions_moderatorlogs_prune' );
	
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Number( 'prune_log_moderator', \IPS\Settings::i()->prune_log_moderator, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days'), 'prune_log_moderator' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__moderatorlog_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=staff&controller=moderators&do=actionLogs' ), 'saved' );
		}
	
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('moderatorlogssettings');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate('global')->block( 'moderatorlogssettings', $form, FALSE );
	}
}
