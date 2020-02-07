<?php
/**
 * @brief		Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		20 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\modules\admin\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_access' );
		
		$tabs = array();
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'forums', 'forums', 'forum_settings' ) )
		{
			$tabs['settings'] = 'forum_settings';
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'forums', 'forums', 'archive_manage' ) )
		{
			$tabs['archiving'] = 'archiving';
		}
		
		$activeTab = \IPS\Request::i()->tab ?: 'settings';
		$activeTabContents = call_user_func( array( $this, 'manage' . ucfirst( $activeTab ) ) );
		
		if( \IPS\Request::i()->isAjax() and !isset( \IPS\Request::i()->ajaxValidate ) )
		{
			\IPS\Output::i()->output = $activeTabContents;
		}
		else
		{		
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__forums_forums_settings');
			\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=forums&module=forums&controller=settings" ) );
		}
	}
	
	/**
	 * Archive settings
	 *
	 * @return	string
	 */
	protected function manageArchiving()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'archive_manage' );

		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_settings.js', 'forums', 'admin' ) );
		
		/* Init */
		$maxTopics = \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics' )->first();
		$existingRules = iterator_to_array( \IPS\Db::i()->select( '*', 'forums_archive_rules' ) );
		$existingCount = \IPS\Settings::i()->archive_on ? \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics', array_merge( array( array( 'topic_archive_status!=?', \IPS\forums\Topic::ARCHIVE_EXCLUDE ) ), \IPS\Application::load('forums')->archiveWhere( $existingRules ) ) )->first() : 0;
				
		/* Work out existing values */
		$existingValues = array();
		foreach ( $existingRules as $rule )
		{
			switch ( $rule['archive_field'] )
			{
				case 'lastpost':
					if ( $rule['archive_skip'] )
					{
						$existingValues['archive_not_last_post'] = array( $rule['archive_value'], $rule['archive_text'], $rule['archive_unit'], FALSE );
					}
					else
					{
						$existingValues['archive_last_post'] = array( $rule['archive_value'], $rule['archive_text'], $rule['archive_unit'], FALSE );
					}
					break;
				
				case 'forum':
					if ( $rule['archive_value'] == '+' )
					{
						$existingValues['archive_topic_forums'] = explode( ',', $rule['archive_text'] );
					}
					else
					{
						$existingValues['archive_topic_not_forums'] = explode( ',', $rule['archive_text'] );
					}
					break;
					
				case 'pinned':
				case 'featured':
				case 'state':
				case 'approved':
				case 'poll':
					$existingValues[ "archive_topic_{$rule['archive_field']}" ] = array( $rule['archive_value'] );
					break;
					
				case 'post':
				case 'view':
				case 'rating':
					if ( $rule['archive_skip'] )
					{
						$existingValues[ "archive_not_topic_{$rule['archive_field']}" ] = array( $rule['archive_value'], $rule['archive_text'], FALSE );
					}
					else
					{
						$existingValues[ "archive_topic_{$rule['archive_field']}" ] = array( $rule['archive_value'], $rule['archive_text'], FALSE );
					}
					break;
				
				case 'member':
					if ( $rule['archive_value'] == '+' )
					{
						$members = array();
						foreach( explode( ',', $rule['archive_text'] ) AS $v )
						{
							$members[] = \IPS\Member::load( $v )->name;
						}
						
						$existingValues['archive_topic_starter'] = implode( ',', $members );
					}
					else
					{
						$members = array();
						foreach( explode( ',', $rule['archive_text'] ) AS $v )
						{
							$members[] = \IPS\Member::load( $v )->name;
						}
						
						$existingValues['archive_topic_starter_not'] = implode( ',', $members );
					}
					break;
			}
		}
		
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->attributes['id'] = 'elArchiveForm';
		$form->addHeader( 'archive_settings' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'archive_on', \IPS\Settings::i()->archive_on, FALSE, array(
			'togglesOn'	=> array(
				'archive_storage_location',
				'archive_last_post',
				'archive_topic_forums',
				'archive_topic_pinned',
				'archive_topic_featured',
				'archive_topic_state',
				'archive_topic_approved',
				'archive_topic_poll',
				'archive_topic_post',
				'archive_topic_view',
				'archive_topic_rating',
				'archive_topic_starter',
				'archive_topic_not_forums',
				'archive_not_topic_post',
				'archive_not_topic_view',
				'archive_not_topic_rating',
				'archive_not_last_post',
				'archive_topic_starter_not',
				'form_header_archive_topics_where',
				'form_header_archive_topics_not_where'
			),
		) ) );
		
		$form->add( new \IPS\Helpers\Form\Radio( 'archive_storage_location', \IPS\Settings::i()->archive_remote_sql_host ? 'remote' : 'local', FALSE, array(
			'options'	=> array( 'local' => 'archive_storage_location_local', 'remote' => 'archive_storage_location_remote' ),
			'toggles'	=> array( 'remote' => array( 'archive_sql_host', 'archive_sql_user', 'archive_sql_pass', 'archive_sql_database', 'archive_sql_port', 'archive_sql_socket', 'archive_sql_tbl_prefix' ) )
		), NULL, NULL, NULL, 'archive_storage_location' ) );

		$form->add( new \IPS\Helpers\Form\Text( 'archive_remote_sql_host', \IPS\Settings::i()->archive_remote_sql_host ?: ini_get('mysqli.default_host') ?: 'localhost', FALSE, array(), NULL, NULL, NULL, 'archive_sql_host' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'archive_remote_sql_user', \IPS\Settings::i()->archive_remote_sql_user ?: ini_get('mysqli.default_user'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_user' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'archive_remote_sql_pass', \IPS\Settings::i()->archive_remote_sql_pass ?: ini_get('mysqli.default_pw'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_pass' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'archive_remote_sql_database', \IPS\Settings::i()->archive_remote_sql_database ?: NULL, FALSE, array(), NULL, NULL, NULL, 'archive_sql_database' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'archive_sql_port', \IPS\Settings::i()->archive_sql_port ?: ini_get('mysqli.default_port'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_port' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'archive_sql_socket', \IPS\Settings::i()->archive_sql_socket ?: ini_get('mysqli.default_socket'), FALSE, array(), NULL, NULL, NULL, 'archive_sql_socket' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'archive_sql_tbl_prefix', \IPS\Settings::i()->archive_sql_tbl_prefix ?: NULL, FALSE, array(), NULL, NULL, NULL, 'archive_sql_tbl_prefix' ) );
		
		$form->addHeader( 'archive_topics_where' );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_last_post', isset( $existingValues['archive_last_post'] ) ? $existingValues['archive_last_post'] : array( '>', 0, 'm', TRUE ), FALSE, array( 'getHtml' => array( $this, '_beforeAfterTimeAgo' ), 'validate' => array( $this, '_validateBeforeAfterTimeAgo' ) ), NULL, NULL, NULL, 'archive_last_post' ) );
		$form->add( new \IPS\Helpers\Form\Node( 'archive_topic_forums', isset( $existingValues['archive_topic_forums'] ) ? $existingValues['archive_topic_forums'] : 0, FALSE, array( 'class' => 'IPS\forums\Forum', 'zeroVal' => 'any', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
		{
			return $forum->sub_can_post and !$forum->redirect_url;
		} ), NULL, NULL, NULL, 'archive_topic_forums' ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'archive_topic_pinned', isset( $existingValues['archive_topic_pinned'] ) ? $existingValues['archive_topic_pinned'] : array( 1, 0 ), FALSE, array( 'options' => array( 1 => 'pinned', 0 => 'mod_confirm_unpin' ) ), NULL, NULL, NULL, 'archive_topic_pinned' ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'archive_topic_featured', isset( $existingValues['archive_topic_featured'] ) ? $existingValues['archive_topic_featured'] : array( 1, 0 ), FALSE, array( 'options' => array( 1 => 'featured', 0 => 'mod_confirm_unfeature' ) ), NULL, NULL, NULL, 'archive_topic_featured' ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'archive_topic_state', isset( $existingValues['archive_topic_state'] ) ? $existingValues['archive_topic_state'] : array( 'closed', 'open' ), FALSE, array( 'options' => array( 'closed' => 'locked', 'open' => 'unlocked' ) ), NULL, NULL, NULL, 'archive_topic_state' ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'archive_topic_approved', isset( $existingValues['archive_topic_approved'] ) ? $existingValues['archive_topic_approved'] : array( -1, 1 ), FALSE, array( 'options' => array( -1 => 'hidden', 1 => 'unhidden' ) ), NULL, NULL, NULL, 'archive_topic_approved' ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'archive_topic_poll', isset( $existingValues['archive_topic_poll'] ) ? $existingValues['archive_topic_poll'] : array( 1, 0 ), FALSE, array( 'options' => array( 1 => 'topic_has_poll', 0 => 'topic_does_not_have_poll' ) ), NULL, NULL, NULL, 'archive_topic_poll' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_topic_post', isset( $existingValues['archive_topic_post'] ) ? $existingValues['archive_topic_post'] : array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_topic_post' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_topic_view', isset( $existingValues['archive_topic_view'] ) ? $existingValues['archive_topic_view'] : array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_topic_view' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_topic_rating', isset( $existingValues['archive_topic_rating'] ) ? $existingValues['archive_topic_rating'] : array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_topic_rating' ) );
		$form->add( new \IPS\Helpers\Form\Member( 'archive_topic_starter', isset( $existingValues['archive_topic_starter'] ) ? $existingValues['archive_topic_starter'] : array(), FALSE, array( 'multiple' => 999999 ), NULL, NULL, NULL, 'archive_topic_starter' ) );
		$form->addHeader( 'archive_topics_not_where' );
		$form->add( new \IPS\Helpers\Form\Node( 'archive_topic_not_forums', isset( $existingValues['archive_topic_not_forums'] ) ? $existingValues['archive_topic_not_forums'] : array(), FALSE, array( 'class' => 'IPS\forums\Forum', 'multiple' => TRUE, 'permissionCheck' => function ( $forum )
		{
			return $forum->sub_can_post and !$forum->redirect_url;
		} ), NULL, NULL, NULL, 'archive_topic_not_forums' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_not_topic_post', isset( $existingValues['archive_not_topic_post'] ) ? $existingValues['archive_not_topic_post'] : array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_not_topic_post' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_not_topic_view', isset( $existingValues['archive_not_topic_view'] ) ? $existingValues['archive_not_topic_view'] : array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_not_topic_view' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_not_topic_rating', isset( $existingValues['archive_not_topic_rating'] ) ? $existingValues['archive_not_topic_rating'] : array( NULL, NULL, TRUE ), FALSE, array( 'getHtml' => array( $this, '_greaterThanLessThanField' ) ), NULL, NULL, NULL, 'archive_not_topic_rating' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'archive_not_last_post', isset( $existingValues['archive_not_last_post'] ) ? $existingValues['archive_not_last_post'] : array( '>', 0, 'm', TRUE ), FALSE, array( 'getHtml' => array( $this, '_beforeAfterTimeAgo' ), 'validate' => array( $this, '_validateBeforeAfterTimeAgo' ) ), NULL, NULL, NULL, 'archive_not_last_post' ) );
		$form->add( new \IPS\Helpers\Form\Member( 'archive_topic_starter_not', isset( $existingValues['archive_topic_starter_not'] ) ? $existingValues['archive_topic_starter_not'] : array(), FALSE, array( 'multiple' => 999999 ), NULL, NULL, NULL, 'archive_topic_starter_not' ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Translate into rules */
			$rules = array();
			foreach ( $values as $k => $v )
			{
				if ( in_array( $k, array( 'archive_last_post' ) ) )
				{
					if ( ( !isset( $v[3] ) or !$v[3] ) and $v[1] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'lastpost',
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> $v[2],
							'archive_skip'	=> 0,
						);
					}
				}
				elseif ( in_array( $k, array( 'archive_not_last_post' ) ) )
				{
					if ( ( !isset( $v[3] ) or !$v[3] ) and $v[1] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'lastpost',
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> $v[2],
							'archive_skip'	=> 1,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_forums' ) ) )
				{
					if ( !empty( $v ) )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'forum',
							'archive_value'	=> '+',
							'archive_text'	=> implode( ',', array_keys( $v ) ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_not_forums' ) ) )
				{
					if ( !empty( $v ) )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'forum',
							'archive_value'	=> '-',
							'archive_text'	=> implode( ',', array_keys( $v ) ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif ( in_array( $k, array( 'archive_topic_pinned', 'archive_topic_featured', 'archive_topic_state', 'archive_topic_approved', 'archive_topic_poll' ) ) )
				{
					if ( !empty( $v ) and count( $v ) == 1 )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> mb_substr( $k, 14 ),
							'archive_value'	=> array_pop( $v ),
							'archive_text'	=> '',
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_post', 'archive_topic_view', 'archive_topic_rating' ) ) )
				{
					if ( !isset( $v[2] ) and $v[1] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> mb_substr( $k, 14 ),
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_not_topic_post', 'archive_not_topic_view', 'archive_not_topic_rating' ) ) )
				{
					if ( !isset( $v[2] ) and $v[1] )
					{
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> mb_substr( $k, 18 ),
							'archive_value'	=> $v[0],
							'archive_text'	=> $v[1],
							'archive_unit'	=> '',
							'archive_skip'	=> 1,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_starter' ) ) )
				{
					if ( is_array( $v ) )
					{
						$ids = array();
						foreach( $v AS $member )
						{
							$ids[] = $member->member_id;
						}
						
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'member',
							'archive_value'	=> '+',
							'archive_text'	=> implode( ',', $ids ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
				elseif( in_array( $k, array( 'archive_topic_starter_not' ) ) )
				{
					if ( is_array( $v ) )
					{
						$ids = array();
						foreach( $v AS $member )
						{
							$ids[] = $member->member_id;
						}
						
						$rules[] = array(
							'archive_key'	=> md5( "forums_{$k}" ),
							'archive_app'	=> 'forums',
							'archive_field'	=> 'member',
							'archive_value'	=> '-',
							'archive_text'	=> implode( ',', $ids ),
							'archive_unit'	=> '',
							'archive_skip'	=> 0,
						);
					}
				}
			}
			
			/* Did we just want a new count? */
			if ( isset( \IPS\Request::i()->getCount ) )
			{
				if ( !$values['archive_on'] )
				{
					$count = 0;
				}
				else
				{				
					$count = \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics', array_merge( array( array( 'topic_archive_status!=?', \IPS\forums\Topic::ARCHIVE_EXCLUDE ) ), \IPS\Application::load('forums')->archiveWhere( $rules ) ) )->first();
				}
				\IPS\Output::i()->json( array( 'count' => $count, 'percentage' => ( $maxTopics * $count > 0 ) ? round( 100 / $maxTopics * $count ) : 0 ) );
			}
			
			/* No, we're actually saving */
			else
			{
				/* Check remote database */
				if ( $values['archive_storage_location'] === 'remote' )
				{
					try
					{
						$remoteDatabase = \IPS\Db::i( 'archive', array(
							'sql_host'		=> $values['archive_remote_sql_host'],
							'sql_user'		=> $values['archive_remote_sql_user'],
							'sql_pass'		=> $values['archive_remote_sql_pass'],
							'sql_database'	=> $values['archive_remote_sql_database'],
							'sql_port'		=> $values['archive_sql_port'],
							'sql_socket'	=> $values['archive_sql_socket'],
							'sql_tbl_prefix'=> $values['archive_sql_tbl_prefix'],
							'sql_utf8mb4'	=> isset( \IPS\Settings::i()->sql_utf8mb4 ) ? \IPS\Settings::i()->sql_utf8mb4 : FALSE
						) );
						
						if ( !$remoteDatabase->checkForTable('forums_archive_posts') )
						{
							$remoteDatabase->createTable( \IPS\Db::i()->getTableDefinition('forums_archive_posts') );
						}
					}
					catch ( \Exception $e )
					{
						$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'archive_remote_db_error', FALSE, array( 'sprintf' => array( "{$e->getMessage()} ({$e->getCode()})" ) ) );
						goto showForm; // Dinosaur attack!!!!
					}
				}
				
				/* Save settings */
				$settingValues = array();
				foreach ( array( 'archive_on', 'archive_remote_sql_host', 'archive_remote_sql_user', 'archive_remote_sql_user', 'archive_remote_sql_pass', 'archive_remote_sql_database', 'archive_sql_port', 'archive_sql_socket', 'archive_sql_tbl_prefix' ) as $k )
				{
					if ( $k !== 'archive_on' and $values['archive_storage_location'] !== 'remote' )
					{
						$settingValues[ $k ] = NULL;
					}
					else
					{
						$settingValues[ $k ] = $values[ $k ];
					}
				}
				$form->saveAsSettings( $settingValues );
				
				\IPS\Db::i()->delete( 'forums_archive_rules' );
				if ( count( $rules ) )
				{
					\IPS\Db::i()->insert( 'forums_archive_rules', $rules );
				}
				
				/* Do we need to unarchive some? */
				$whereClause = \IPS\Db::i()->compileWhereClause( \IPS\Application::load('forums')->archiveWhere( $rules ) );
				$_whereClause = array( 'topic_archive_status=? AND !( ' . $whereClause['clause'] . ')' );
				$_whereClause[] = \IPS\forums\Topic::ARCHIVE_DONE;
				foreach ( $whereClause['binds'] as $bind )
				{
					$_whereClause[] = $bind;
				}

                if( $values['archive_on'] )
                {
                    \IPS\Db::i()->update( 'core_tasks', array( 'enabled' => (int)$values['archive_on'] ), array( '`key`=?', 'archive' ) );
                }
				
				/* Log and redirect */
				\IPS\Session::i()->log( 'acplogs__archive_settings' );
				if ( \IPS\Db::i()->select( 'COUNT(*)', 'forums_topics', $values['archive_on'] ? $_whereClause : array( 'topic_archive_status=?', \IPS\forums\Topic::ARCHIVE_DONE ) )->first() )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=forums&module=forums&controller=settings&do=unarchive' ) );
				}
				else
				{				
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=forums&module=forums&controller=settings&tab=archiving' ) );
				}
			}
		}
		
		/* Display */
		showForm:
		return \IPS\Theme::i()->getTemplate( 'settings' )->archiveRules( $form, $maxTopics, $existingCount, ( $maxTopics * $existingCount > 0 ) ? round( 100 / $maxTopics * $existingCount ) : 0 );
	}
		
	/**
	 * Greater/Less Than X or Any
	 *
	 * @param	\IPS\Helpers\Form\Custom	$field	The field
	 * @return	string
	 */
	public function _greaterThanLessThanField( $field )
	{
		return \IPS\Theme::i()->getTemplate( 'settings' )->archiveRuleGtLt( $field->name, $field->value );
	}
	
	/**
	 * Before/After X days/months/years ago
	 *
	 * @param	\IPS\Helpers\Form\Custom	$field	The field
	 * @return	string
	 */
	public function _beforeAfterTimeAgo( $field )
	{
		return \IPS\Theme::i()->getTemplate( 'settings' )->archiveRuleTime( $field->name, $field->value );
	}
	
	/**
	 * Before/After X days/months/years ago
	 *
	 * @param	\IPS\Helpers\Form\Custom	$field	The field
	 * @return	string
	 */
	public function _validateBeforeAfterTimeAgo( $field )
	{
		if ( isset( $field->value[1] ) and $field->value[1] < 0 )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'form_number_min', FALSE, array( 'sprintf' => array( 0 ) ) ) );
		}
	}
	
	/**
	 * Unarchive topics that no longer match settings?
	 *
	 * @return	void
	 */
	public function unarchive()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'archive_manage' );
		
		if ( isset( \IPS\Request::i()->confirm ) )
		{
			if ( \IPS\Settings::i()->archive_on )
			{
				$whereClause = \IPS\Db::i()->compileWhereClause( \IPS\Application::load('forums')->archiveWhere( \IPS\Db::i()->select( '*', 'forums_archive_rules' ) ) );
				$_whereClause = array( 'topic_archive_status=? AND !( ' . $whereClause['clause'] . ')' );
				$_whereClause[] = \IPS\forums\Topic::ARCHIVE_DONE;
				foreach ( $whereClause['binds'] as $bind )
				{
					$_whereClause[] = $bind;
				}
				\IPS\Db::i()->update( 'forums_topics', array( 'topic_archive_status' => \IPS\forums\Topic::ARCHIVE_RESTORE ), $_whereClause );
			}
			else
			{
				\IPS\Db::i()->update( 'forums_topics', array( 'topic_archive_status' => \IPS\forums\Topic::ARCHIVE_RESTORE ), array( 'topic_archive_status=?', \IPS\forums\Topic::ARCHIVE_DONE ) );
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=forums&module=forums&controller=settings&tab=archiving' ) );
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->decision( 'uanrchive_settings_change', array(
				'restore_unmatched_settings'	=> \IPS\Http\Url::internal( 'app=forums&module=forums&controller=settings&do=unarchive&confirm=1' ),
				'leave_archived_topics'			=> \IPS\Http\Url::internal( 'app=forums&module=forums&controller=settings&tab=archiving' ),
			) );
		}
	}
	
	/**
	 * Settings
	 *
	 * @return	string
	 */
	protected function manageSettings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'forum_settings' );
		
		$form = new \IPS\Helpers\Form;
		$form->addHeader('forum_settings');
		$form->add( new \IPS\Helpers\Form\YesNo( 'forums_rss', \IPS\Settings::i()->forums_rss ) );
		$form->addHeader('topic_settings');
		$form->add( new \IPS\Helpers\Form\Custom( 'forums_popular_now', json_decode( \IPS\Settings::i()->forums_popular_now, TRUE ), FALSE, array(
			'getHtml'		=> function( $obj )
			{
				return \IPS\Theme::i()->getTemplate('settings')->popularNow( $obj->name, $obj->value );
			}
		) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'forums_posts_per_page', \IPS\Settings::i()->forums_posts_per_page , TRUE, array( 'min' => 2, 'max' => 250 ) ) );
		$form->addHeader('question_settings');
		$form->add( new \IPS\Helpers\Form\YesNo( 'forums_questions_downvote', \IPS\Settings::i()->forums_questions_downvote ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forums_answers_downvote', \IPS\Settings::i()->forums_answers_downvote ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'forums_new_questions', \IPS\Settings::i()->forums_new_questions, FALSE, array( 'options' => array( '0' => 'forums_new_questions_best', '1' => 'forums_new_questions_any' ) ) ) );

		if ( $values = $form->values() )
		{
			if ( isset( $values['forums_popular_now']['never'] ) )
			{
				$values['forums_popular_now'] = json_encode( array( 'posts' => 0, 'minutes' => 0 ) );
			}
			else
			{
				$values['forums_popular_now'] = json_encode( $values['forums_popular_now'] );
			}
						
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__forums_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=forums&module=forums&controller=settings&tab=settings' ), 'saved' );
		}
		
		return $form;
	}
}