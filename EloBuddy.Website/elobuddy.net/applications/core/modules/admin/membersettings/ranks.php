<?php
/**
 * @brief		ranks
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Apr 2013
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
 * ranks
 */
class _ranks extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'ranks_manage' );
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
		$table = new \IPS\Helpers\Table\Db( 'core_member_ranks', \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks' ) );
		
		/* Columns */
		$table->joins = array(
			array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_member_rank_', core_member_ranks.id ) AND w.lang_id=" . \IPS\Member::loggedIn()->language()->id )
		);
		
		$table->include    = array( 'word_custom', 'posts', 'pips' );
		$table->langPrefix = 'member_ranks_';
		$table->mainColumn = 'title';
		$table->noSort	   = array( 'pips' );
		
		$table->parsers = array(
			'pips' => function( $val, $row )
			{
				if ( $row['use_icon'] and $row['icon'] )
				{
					return "<img src='" . \IPS\File::get( 'core_Theme', $row['icon'] )->url . "' alt=''>";
				}
				else
				{
					return str_repeat("<span class='ipsPip'></span>", intval($val) );
				}
			}
		);
		
		$table->sortBy = $table->sortBy ?: 'posts';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		
		/* Root Buttons */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'ranks_manage' ) )
		{
			$table->rootButtons = array(
					'add'	=> array(
						'icon'		=> 'plus',
						'title'		=> 'member_ranks_add',
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('member_ranks_add') ),
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks&do=form' ),
					)
			);
		}
		
		/* Row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array();
				
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'ranks_manage' ) )
			{
				$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit') ),
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks&do=form&id=' ) . $row['id'],
				);
			}
				
				
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'ranks_manage' ) )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks&do=delete&id=' ) . $row['id'],
					'data'		=> array( 'delete' => '' ),
				);
			}
				
			return $return;
		};
		
		/* The incredibly complex search code */
		$table->quickSearch = 'word_custom';
		
		/* Show a message about the setting to allow members to set their own */
		switch ( \IPS\Settings::i()->post_titlechange )
		{
			case -1:
				$message = \IPS\Member::loggedIn()->language()->addToStack('rankown_none');
				break;
				
			case 0:
				$message = \IPS\Member::loggedIn()->language()->addToStack('rankown_always');
				break;
				
			default:
				$message = \IPS\Member::loggedIn()->language()->addToStack( 'rankown_limit', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->post_titlechange ) ) );
				break;
		}
		$message .= ' <a href="' . \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks&do=settings' ) . '" data-ipsDialog data-ipsDialog-title="' . \IPS\Member::loggedIn()->language()->addToStack('change') . '">' . \IPS\Member::loggedIn()->language()->addToStack('change') . '</a>';
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->message( $message, 'information', NULL, FALSE );
		
		/* Display */

		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('ranks');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'ranks', (string) $table );
	}
	
	/**
	 * Add/Edit Rank
	 */
	public function form()
	{
		$current = NULL;
		
		if ( \IPS\Request::i()->id )
		{
			$current = \IPS\Db::i()->select( '*', 'core_member_ranks', array( 'id=?', \IPS\Request::i()->id ) )->first();
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form();
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'member_ranks_word_custom', NULL, TRUE, array( 'app' => 'core', 'key' => ( $current ? "core_member_rank_{$current['id']}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'member_ranks_posts', $current ? $current['posts'] : 0, TRUE, array( 'min' => 0 ) ) );
		
		$form->add( new \IPS\Helpers\Form\Radio( 'member_use_icon', $current['use_icon'], FALSE, array(
			'parse'		=> 'raw',
			'options'	=> array( 0 => \IPS\Member::loggedIn()->language()->addToStack('member_ranks_pip_number', FALSE, array( 'htmlsprintf' => array( "<span class='ipsPip'></span>" ) ) ), 1 => \IPS\Member::loggedIn()->language()->addToStack('member_ranks_pip_icon') ),
			'toggles'	=> array( 0 => array('member_ranks_pips'), 1 => array('member_ranks_icon') )
		) ) );
		
		/* Pip Icon */
		$pip = NULL;
		if ( $current['icon'] )
		{
			$pip = \IPS\File::get( 'core_Theme', $current['icon'] );
		}
		$form->add( new \IPS\Helpers\Form\Number( 'member_ranks_num_pips' , $current ? $current['pips']  : 0, FALSE, array( 'min' => 0, 'max' => 100 ), NULL, NULL, NULL, 'member_ranks_pips' ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'member_ranks_icon', $pip, FALSE, array( 'image' => array( 'maxWidth' => 140, 'maxHeight' => 140 ), 'storageExtension' => 'core_Theme' ), NULL, NULL, NULL, 'member_ranks_icon' ) );
		
		if ( $values = $form->values() )
		{
			$save = array(
				'posts'    => intval( $values['member_ranks_posts'] ),
				'pips'	   => intval( $values['member_ranks_num_pips'] ),
				'use_icon' => intval( $values['member_use_icon'] ),
				'icon'		=> '',
			);
			
			if ( $values['member_ranks_icon'] instanceof \IPS\File )
			{
				$save['icon'] = (string) $values['member_ranks_icon'];
			}
			
			if ( $current )
			{
				\IPS\Db::i()->update( 'core_member_ranks', $save, array( 'id=?', $current['id'] ) );
				$id = $current['id'];
				\IPS\Session::i()->log( 'acplogs__rank_edited', array( $save['posts'] => FALSE ) );
			}
			else
			{
				$id = \IPS\Db::i()->insert( 'core_member_ranks', $save );
				\IPS\Session::i()->log( 'acplogs__rank_created', array( $save['posts'] => FALSE ) );
			}
				
			\IPS\Lang::saveCustom( 'core', "core_member_rank_{$id}", $values['member_ranks_word_custom'] );
			unset( \IPS\Data\Store::i()->ranks );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks' ), 'saved' );
		}
		
		/* Display */
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( $current ? "core_member_rank_{$current['id']}" : 'add', $form, FALSE );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'ranks_manage' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		try
		{
			$current = \IPS\Db::i()->select( '*', 'core_member_ranks', array( 'id=?', \IPS\Request::i()->id ) )->first();
			
			\IPS\Session::i()->log( 'acplogs__rank_deleted', array( $current['posts'] => FALSE ) );
			\IPS\Db::i()->delete( 'core_member_ranks', array( 'id=?', \IPS\Request::i()->id ) );
			\IPS\Lang::deleteCustom( 'core', 'core_member_rank_' . \IPS\Request::i()->id );
		}
		catch ( \UnderflowException $e ) { }

		unset( \IPS\Data\Store::i()->ranks );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks' ) );
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	public function settings()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Number( 'post_titlechange', \IPS\Settings::i()->post_titlechange, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'never_allow' ) ) );
		if ( $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=ranks' ), 'saved' );
		}
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'ranks', $form, FALSE );
	}
}