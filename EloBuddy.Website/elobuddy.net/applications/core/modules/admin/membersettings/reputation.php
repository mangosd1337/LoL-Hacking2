<?php
/**
 * @brief		Reputation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reputation
 */
class _reputation extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'reps_manage' );
		return parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('reputation');
		
		/* Init */
		$activeTab = \IPS\Request::i()->tab ?: NULL;
		$activeTabContents = '';
		$tabs = array();
		
		/* Settings */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'reps_settings' ) )
		{
			$tabs['settings'] = 'reputation_settings';
		}
		
		/* Levels */		
		if ( \IPS\Settings::i()->reputation_enabled and \IPS\Settings::i()->reputation_show_profile )
		{
			$tabs['levels'] = 'reputation_levels';
		}
		
		/* Make sure we have a tab */
		if ( empty( $tabs ) )
		{
			\IPS\Output::i()->error( 'no_module_permission', '1C225/1', 403, '' );
		}
		elseif ( !$activeTab or !array_key_exists( $activeTab, $tabs ) )
		{
			$_tabs = array_keys( $tabs );
			$activeTab = array_shift( $_tabs );
		}
		
		/* Do it */
		if ( $activeTab === 'settings' )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'reps_settings' );
		
			/* Random for the "like" preview */
			$maxMemberId =  \IPS\Db::i()->select( 'MAX(member_id)', 'core_members' )->first();
			$names = array();
			foreach ( range( 1, ( $maxMemberId > 3 ) ? 3 : $maxMemberId ) as $i )
			{
				do
				{
					$randomMemberId = rand( 1, $maxMemberId );
				}
				while ( array_key_exists( $randomMemberId, $names ) );
								
				try
				{
					$where = array( array( 'member_id>=?', $randomMemberId ) );
					if ( !empty( $names ) )
					{
						$where[] = \IPS\Db::i()->in( 'member_id', array_keys( $names ), TRUE );
					}
					
					$member = \IPS\Member::constructFromData( \IPS\Db::i()->select( '*', 'core_members', $where, 'member_id ASC', 1 )->first() );
					$names[ $member->member_id ] = '<a>' . htmlentities( $member->name, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) . '</a>';
				}
				catch ( \Exception $e )
				{
					break;
				}
			}
			if ( count( $names ) == 3 )
			{
				$names[] = \IPS\Member::loggedIn()->language()->addToStack( 'like_blurb_others', FALSE, array( 'pluralize' => array( 2 ) ) );
			}
			if ( empty( $names ) )
			{
				$blurb = '';
			}
			else
			{
				$blurb = \IPS\Member::loggedIn()->language()->addToStack( 'like_blurb', FALSE, array( 'htmlsprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $names ) ), 'pluralize' => array( count( $names ) ) ) );
			}
			
			/* Build Form */
			$form = new \IPS\Helpers\Form();
			$form->add( new \IPS\Helpers\Form\YesNo( 'reputation_enabled', \IPS\Settings::i()->reputation_enabled, FALSE, array( 'togglesOn' => array( 'reputation_point_types', 'reputation_protected_groups', 'reputation_can_self_vote', 'reputation_highlight', 'reputation_show_profile' ) ) ) );
			$form->add( new \IPS\Helpers\Form\Radio( 'reputation_point_types', \IPS\Settings::i()->reputation_point_types, FALSE, array(
				'options'	=> array(
					'like'		=> \IPS\Theme::i()->getTemplate( 'settings' )->reputationLike( $blurb ),
					'both'		=> \IPS\Theme::i()->getTemplate( 'settings' )->reputationNormal( TRUE, TRUE ),
					'positive'	=> \IPS\Theme::i()->getTemplate( 'settings' )->reputationNormal( TRUE, FALSE ),
					'negative'	=> \IPS\Theme::i()->getTemplate( 'settings' )->reputationNormal( FALSE, TRUE ),
				),
				'toggles'	=> array(
					'both'		=> array( 'reputation_show_content' ),
					'positive'	=> array( 'reputation_show_content' ),
					'negative'	=> array( 'reputation_show_content' )
				),
				'parse'		=> 'raw'
			), NULL, NULL, NULL, 'reputation_point_types' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'reputation_show_content', \IPS\Settings::i()->reputation_show_content, FALSE, array(), NULL, NULL, NULL, 'reputation_show_content' ) );
			$form->add( new \IPS\Helpers\Form\Select( 'reputation_protected_groups', explode( ',', \IPS\Settings::i()->reputation_protected_groups ), FALSE, array( 'options' => \IPS\Member\Group::groups( TRUE, FALSE ), 'parse' => 'normal', 'multiple' => TRUE ), NULL, NULL, NULL, 'reputation_protected_groups' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'reputation_can_self_vote', \IPS\Settings::i()->reputation_can_self_vote, FALSE, array(), NULL, NULL, NULL, 'reputation_can_self_vote' ) );
			$form->add( new \IPS\Helpers\Form\Number( 'reputation_highlight', \IPS\Settings::i()->reputation_highlight, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('reputation_highlight_prefix'), \IPS\Member::loggedIn()->language()->addToStack('reputation_highlight_suffix'), 'reputation_highlight' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'reputation_show_profile', \IPS\Settings::i()->reputation_show_profile, FALSE, array(), NULL, NULL, NULL, 'reputation_show_profile' ) );
			
			/* Save */
			if ( $form->saveAsSettings() )
			{
				/* Clear guest page caches */
				\IPS\Data\Cache::i()->clearAll();

				\IPS\Session::i()->log( 'acplogs__rep_settings' );
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&tab=settings' ), 'saved' );
			}
			
			/* Display */
			$activeTabContents = (string) $form;
		}
		else
		{
			/* Create the table */
			$table = new \IPS\Helpers\Table\Db( 'core_reputation_levels', \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&tab=levels' ) );
			$table->langPrefix = 'rep_';
			
			/* Columns */
			$table->joins = array(
				array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_reputation_level_', core_reputation_levels.level_id ) AND w.lang_id=" . \IPS\Member::loggedIn()->language()->id )
			);
			$table->include = array( 'word_custom', 'level_image', 'level_points' );
			$table->mainColumn = 'word_custom';
			$table->quickSearch = 'word_custom';
			
			/* Sorting */
			$table->noSort = array( 'level_image' );
			$table->sortBy = $table->sortBy ?: 'level_points';
			$table->sortDirection = $table->sortDirection ?: 'asc';
			
			/* Parsers */
			$table->parsers = array(
				'level_image'	=> function( $val )
				{
					if ( $val )
					{
						return "<img src='" . \IPS\File::get( "core_Theme", $val )->url . "' alt=''>";
					}
					return '';
				}
			);
			
			/* Buttons */
			$table->rootButtons = array(
				'add'	=> array(
					'title'	=> 'add',
					'icon'	=> 'plus',
					'link'	=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&do=form' ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add') )
				),
			);
			$table->rowButtons = function( $row )
			{
				return array(
					'edit'	=> array(
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'link'	=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&do=form&id=' ) . $row['level_id'],
						'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit') )
					),
					'delete'	=> array(
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'link'	=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&do=delete&id=' ) . $row['level_id'],
						'data'	=> array( 'delete' => '' )
					),
				);
			};
			
			/* Display */
			$activeTabContents = \IPS\Theme::i()->getTemplate( 'forms' )->blurb( 'reputation_levels_blurb', TRUE, TRUE ) . (string) $table;
		}
			
		/* Display */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=membersettings&controller=reputation" ) );
		}
	}
	
	/**
	 * Add/Edit
	 *
	 * @return	void
	 */
	public function form()
	{
		$current = NULL;
		if ( \IPS\Request::i()->id )
		{
			$current = \IPS\Db::i()->select( '*', 'core_reputation_levels', array( 'level_id=?', \IPS\Request::i()->id ) )->first();
		}
	
		$form = new \IPS\Helpers\Form();
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'rep_level_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $current ? "core_reputation_level_{$current['level_id']}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'rep_level_image', ( $current and $current['level_image'] ) ? \IPS\File::get( 'core_Theme', $current['level_image'] ) : NULL, FALSE, array( 'storageExtension' => 'core_Theme' ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'rep_level_points', $current ? $current['level_points'] : 0, TRUE, array( 'min' => NULL ) ) );
		
		if ( $values = $form->values() )
		{
			$save = array(
				'level_image'	=> $values['rep_level_image'] ? str_replace( \IPS\ROOT_PATH . '/uploads/', '', $values['rep_level_image'] ) : '',
				'level_points'	=> $values['rep_level_points'],
			);
		
			if ( $current )
			{
				\IPS\Db::i()->update( 'core_reputation_levels', $save, array( 'level_id=?', $current['level_id'] ) );
				$id = $current['level_id'];
				\IPS\Session::i()->log( 'acplogs__rep_edited', array( $save['level_points'] => FALSE ) );
			}
			else
			{
				$id = \IPS\Db::i()->insert( 'core_reputation_levels', $save );
				\IPS\Session::i()->log( 'acplogs__rep_edited', array( $save['level_points'] => FALSE ) );
			}
			
			unset( \IPS\Data\Store::i()->reputationLevels );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Lang::saveCustom( 'core', "core_reputation_level_{$id}", $values['rep_level_title'] );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&tab=levels' ), 'saved' );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( $current ? "core_reputation_level_{$current['level_id']}" : 'add', $form, FALSE );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'reps_delete' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		try
		{
			$current = \IPS\Db::i()->select( '*', 'core_reputation_levels', array( 'level_id=?', \IPS\Request::i()->id ) )->first();
			
			\IPS\Session::i()->log( 'acplogs__rep_deleted', array( $current['level_points'] => FALSE ) );
			\IPS\Db::i()->delete( 'core_reputation_levels', array( 'level_id=?', \IPS\Request::i()->id ) );
			unset( \IPS\Data\Store::i()->reputationLevels );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Lang::deleteCustom( 'core', 'core_reputation_level_' . \IPS\Request::i()->id );
		}
		catch ( \UnderflowExceptiobn $e ) { }
				
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=reputation&tab=levels' ) );
	}
}