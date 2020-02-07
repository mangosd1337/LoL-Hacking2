<?php
/**
 * @brief		Profile
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\members;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile
 */
class _profile extends \IPS\Helpers\CoverPhoto\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\core\Statuses\Status';
	
	/**
	 * Main execute entry point - used to override breadcrumb
	 *
	 * @return void
	 */
	public function execute()
	{
		/* Load Member */
		$this->member = \IPS\Member::load( \IPS\Request::i()->id );
		if ( !$this->member->member_id )
		{
			\IPS\Output::i()->error( 'node_error', '2C138/1', 404, '' );
		}
		
		/* Set breadcrumb */
		unset( \IPS\Output::i()->breadcrumb['module'] );
		\IPS\Output::i()->breadcrumb[] = array( $this->member->url(), $this->member->name );
		\IPS\Output::i()->sidebar['enabled'] = FALSE;

		if( !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_profile.js', 'core' ) );
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_statuses.js', 'core' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/profiles.css' ) );
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/streams.css' ) );

			if ( \IPS\Theme::i()->settings['responsive'] )
			{
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/profiles_responsive.css' ) );
			}
		}
		
		/* Go */
		parent::execute();
	}
	
	/**
	 * Change the users follow preference
	 *
	 * @return void
	 */
	protected function changeFollow()
	{
		if ( !\IPS\Member::loggedIn()->modPermission('can_modify_profiles') AND ( \IPS\Member::loggedIn()->member_id !== $this->member->member_id OR !$this->member->group['g_edit_profile'] ) )
		{
			\IPS\Output::i()->error( 'no_permission_edit_profile', '2C138/3', 403, '' );
		}

		\IPS\Session::i()->csrfCheck();

		\IPS\Member::loggedIn()->members_bitoptions['pp_setting_moderate_followers'] = ( \IPS\Request::i()->enabled == 1 ? FALSE : TRUE );
		\IPS\Member::loggedIn()->save();

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( $this->member->url(), 'follow_saved' );
		}
	}

	/**
	 * Show Profile
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Can we access this member's statuses */
		$canAccessSingleStatuses = ( \IPS\Settings::i()->profile_comments and \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'status' ) ) );
		$canAccessStatuses = $canAccessSingleStatuses and ( $this->member->pp_setting_count_comments or \IPS\Member::loggedIn()->member_id == $this->member->member_id );
		
		/* Are we loading a different page of comments? */
		if ( \IPS\Request::i()->status && \IPS\Request::i()->isAjax() && !\IPS\Request::i()->submitting && !\IPS\Request::i()->getUploader && $canAccessStatuses )
		{
			$status = \IPS\core\Statuses\Status::loadAndCheckPerms( \IPS\Request::i()->status );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'statuses' )->statusReplies( $status );
			return;
		}
		
		/* Get profile field values */
		try
		{
			$profileFieldValues	= \IPS\Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			$profileFieldValues = array();
		}
		
		/* Split the fields into sidebar and main fields */
		$mainFields = array();
		$sidebarFields = array();
		if( !empty( $profileFieldValues ) )
		{
			foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( 'pfd.*', array('core_pfields_data', 'pfd'), array('pfd.pf_admin_only=0'), 'pfg.pf_group_order, pfd.pf_position' )->join(
				array('core_pfields_groups', 'pfg'),
				"pfd.pf_group_id=pfg.pf_group_id"
			), 'IPS\core\ProfileFields\Field' ) as $field )
			{
				if( $profileFieldValues[ 'field_' . $field->id ] !== '' AND $profileFieldValues[ 'field_' . $field->id ] !== NULL )
				{
					/** check if the field isn't hidden and if it is hidden, show it only to admins and to the user */
					if( !$field->member_hide or ( \IPS\Member::loggedIn()->isAdmin() OR \IPS\Member::loggedIn()->member_id === $this->member->member_id ) )
					{
						if( $field->type == 'Editor' )
						{
							$mainFields['core_pfieldgroups_' . $field->group_id]['core_pfield_' . $field->id] = $field->displayValue( $profileFieldValues['field_' . $field->id] );
						}
						else
						{
							$sidebarFields['core_pfieldgroups_' . $field->group_id]['core_pfield_' . $field->id] = $field->displayValue( $profileFieldValues['field_' . $field->id] );
						}
					}
				}
			}
		}
		
		/* Work out the main content to display */
		if ( $canAccessSingleStatuses and \IPS\Request::i()->status and \IPS\Request::i()->type == 'status' )
		{
			try
			{
				$status = \IPS\core\Statuses\Status::loadAndCheckPerms( \IPS\Request::i()->status );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C138/E', 404, '' );
			}

			$mainContent = \IPS\Theme::i()->getTemplate( 'profile' )->singleStatus( $this->member, $status );
			
			\IPS\Output::i()->title			= \IPS\Member::loggedIn()->language()->get( 'viewing_single_status' );
			\IPS\Output::i()->breadcrumb[]	= array( NULL, \IPS\Member::loggedIn()->language()->get( 'viewing_single_status' ) );
		}
		else
		{
	
			/* What tabs are available? */
			$tabs = array( 'activity' => 'users_activity_feed' );
			foreach( $mainFields as $group => $fields )
			{
				foreach( $fields as $field => $value )
				{
					if ( $value )
					{
						$tabs["field_{$field}"] = $field;
					}
				}
			}
			$nodes = array();
			foreach ( \IPS\Application::allExtensions( 'core', 'Profile', TRUE, NULL, NULL, FALSE ) as $extension )
			{
				$profileExtension = new $extension( $this->member );
				if ( $profileExtension->showTab() )
				{
					preg_match( '/^IPS\\\(.+?)\\\extensions\\\core\\\Profile\\\(.+?)$/', $extension, $matches );
					$nodes[ "{$matches[1]}_{$matches[2]}" ] = $profileExtension;
					$tabs[ "node_{$matches[1]}_{$matches[2]}" ] = "profile_{$matches[1]}_{$matches[2]}";
				}
			}
	
			/* What tab are we on? */
			if ( !isset( \IPS\Request::i()->tab ) or !array_key_exists( \IPS\Request::i()->tab, $tabs ) )
			{
				$tab = 'activity';
			}
			else
			{
				$tab = \IPS\Request::i()->tab;
			}
	
			/* Work out the content */
			$tabContents = '';
			if ( $tab == 'activity' )
			{
				$latestActivity = \IPS\Content\Search\Query::init()->filterForProfile( $this->member )->setLimit( 15 )->setOrder( \IPS\Content\Search\Query::ORDER_NEWEST_CREATED )->search();
				$latestActivity->init( TRUE );
	
				$extra = array();
				foreach ( array( 'register', 'follow_member', 'follow_content', 'photo', 'like', 'rep_neg' ) as $k )
				{
					$key = "all_activity_{$k}";
					if ( \IPS\Settings::i()->$key )
					{
						$extra[] = $k;
					}
				}
				if ( !empty( $extra ) )
				{
					$latestActivity = $latestActivity->addExtraItems( $extra, $this->member );
				}
	
				$statusForm = NULL;
				if ( \IPS\core\Statuses\Status::canCreate( \IPS\Member::loggedIn() ) )
				{
					if ( isset( \IPS\Request::i()->status_content_ajax ) )
					{
						\IPS\Request::i()->status_content = \IPS\Request::i()->status_content_ajax;
					}
	
					$form = new \IPS\Helpers\Form( 'new_status', 'status_new' );
					foreach( \IPS\core\Statuses\Status::formElements() AS $k => $element )
					{
						$form->add( $element );
					}
	
					if ( $values = $form->values() )
					{
						$status = \IPS\core\Statuses\Status::createFromForm( $values );
	
						if ( \IPS\Request::i()->isAjax() )
						{
							\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'statuses', 'core', 'front' )->statusContainer( $status ) );
						}
						else
						{
							\IPS\Output::i()->redirect( $status->url() );
						}
					}
	
					$statusForm = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forms', 'core' ), 'statusTemplate' ) );
	
					if ( \IPS\core\Statuses\Status::moderateNewItems( \IPS\Member::loggedIn() ) )
					{
						$statusForm = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->modQueueMessage( \IPS\Member::loggedIn()->warnings( 5, NULL, 'mq' ), \IPS\Member::loggedIn()->mod_posts ) . $statusForm;
					}
				}
	
				$tabContents = \IPS\Theme::i()->getTemplate( 'profile' )->profileActivity( $this->member, $latestActivity, $statusForm );
			}
			elseif ( mb_substr( $tab, 0, 6 ) == 'field_' )
			{
				$fieldId = mb_substr( $tab, 6 );
				foreach( $mainFields as $group => $fields )
				{
					foreach( $fields as $field => $value )
					{
						if ( $field == $fieldId )
						{
							$tabContents = \IPS\Theme::i()->getTemplate( 'profile' )->fieldTab( $fieldId, $value );
						}
					}
				}
			}
			elseif ( mb_substr( $tab, 0, 5 ) == 'node_' )
			{
				$type = mb_substr( $tab, 5 );
				$tabContents = (string) $nodes[ $type ]->render();
			}

			/* If this is AJAX request to change the tab, just display that */
			if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->tab ) )
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $tabContents ) );
			}
	
			/* Otherwise wrap it in the tabs */
			$mainContent = \IPS\Theme::i()->getTemplate( 'profile' )->profileTabs( $this->member, $tabs, $tab, $tabContents );

			\IPS\Output::i()->title = $this->member->name;
		}
		
		/* Log a visit */
		if( \IPS\Member::loggedIn()->member_id and $this->member->member_id != \IPS\Member::loggedIn()->member_id and !\IPS\Session::i()->getAnon() )
		{
			$this->member->addVisitor( \IPS\Member::loggedIn() );
		}
		
		/* Update views */
		\IPS\Db::i()->update(
				'core_members',
				"`members_profile_views`=`members_profile_views`+1",
				array( "member_id=?", $this->member->member_id ),
				array(),
				NULL,
				\IPS\Db::LOW_PRIORITY
		);
		
		/* Get visitor data */
		$visitors = array();
		$visitorInfo = json_decode( $this->member->pp_last_visitors, TRUE );
		if ( is_array( $visitorInfo ) and $this->member->members_bitoptions['pp_setting_count_visitors'] )
		{
			foreach( array_reverse( $visitorInfo, TRUE ) as $id => $time )
			{
				$visitor = \IPS\Member::load( $id );
				if ( $visitor->member_id )
				{
					$visitors[$id]['member'] = $visitor;
					$visitors[$id]['visit_time'] = $time;
				}
			}
		}
		
		/* Get followers */
		$followers = $this->member->followers( ( \IPS\Member::loggedIn()->isAdmin() OR \IPS\Member::loggedIn()->member_id === $this->member->member_id ) ? \IPS\Member::FOLLOW_PUBLIC + \IPS\Member::FOLLOW_ANONYMOUS : \IPS\Member::FOLLOW_PUBLIC, array( 'immediate', 'daily', 'weekly' ), NULL, array( 0, 12 ) );
		
		/* Update online location */		
		$module = \IPS\Application\Module::get( 'core', 'members', 'front' )->permissions();
		\IPS\Session::i()->setLocation( $this->member->url(), explode( ",", $module['perm_view'] ), 'loc_viewing_profile', array( $this->member->name => FALSE ) );
		
		/* Work out add warning URL */
		$addWarningUrl = \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$this->member->member_id}", 'front', 'warn_add', array( $this->member->members_seo_name ) );
		if ( isset( \IPS\Request::i()->wr ) )
		{
			$addWarningUrl = $addWarningUrl->setQueryString( 'ref', \IPS\Request::i()->wr );
		}
		
		/* Output */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_profile.js', 'core' ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile' )->profile( $this->member, $mainContent, $visitors, $sidebarFields, $followers, $addWarningUrl );
	}

	/**
	 * Hovercard
	 *
	 * @return	void
	 */
	public function hovercard()
	{
		$addWarningUrl = \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&do=warn&id={$this->member->member_id}", 'front', 'warn_add', array( $this->member->members_seo_name ) );
		if ( isset( \IPS\Request::i()->wr ) )
		{
			$addWarningUrl = $addWarningUrl->setQueryString( 'ref', \IPS\Request::i()->wr );
		}
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'profile' )->hovercard( $this->member, $addWarningUrl ) );
	}
	
	/**
	 * Show Content
	 *
	 * @return	void
	 */
	public function content()
	{
		/* Get the different types */
		$types			= array();
		$hasCallback	= array();

		foreach ( \IPS\Application::allExtensions( 'core', 'ContentRouter', TRUE, NULL, NULL, TRUE ) as $router )
		{
			foreach( $router->classes as $class )
			{
				if( !isset( $class::$includeInUserProfiles ) OR !$class::$includeInUserProfiles )
				{
					continue;
				}
				
				/* Add CSS for this app */
				\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'profile.css', $class::$application ) );

				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;
								
				$supportsComments = ( in_array( 'IPS\Content\Item', class_parents( $class ) ) and $class::supportsComments( \IPS\Member::loggedIn() ) );
				if ( $supportsComments )
				{
					$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$commentClass, 4 ) ) ) ] = $class::$commentClass;
				}
				
				$supportsReviews = ( in_array( 'IPS\Content\Item', class_parents( $class ) ) and $class::supportsReviews( \IPS\Member::loggedIn() ) );
				if ( $supportsReviews )
				{
					$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$reviewClass, 4 ) ) ) ] = $class::$reviewClass;
				}

				if( method_exists( $router, 'customTableHelper' ) )
				{
					$hasCallback[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ]					= $router;

					if ( $supportsComments )
					{
						$hasCallback[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$commentClass, 4 ) ) ) ]	= $router;
					}

					if ( $supportsReviews )
					{
						$hasCallback[ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$reviewClass, 4 ) ) ) ]		= $router;
					}
				}
			}
		}

		/* What type are we looking at? */
		$currentAppModule = NULL;
		$currentType = NULL;
		if ( isset( \IPS\Request::i()->type ) )
		{
			foreach ( $types as $appModule => $_types )
			{
				if ( array_key_exists( \IPS\Request::i()->type, $_types ) )
				{
					$currentAppModule = $appModule;
					$currentType = \IPS\Request::i()->type;
					break;
				}
			}
		}

		$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		/* Build Output */
		if ( !$currentType )
		{
			$query = \IPS\Content\Search\Query::init()->filterByAuthor( $this->member )->setOrder( \IPS\Content\Search\Query::ORDER_NEWEST_UPDATED )->setPage( $page );
			$results = $query->search();
			$pagination = trim( \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $this->member->url()->setQueryString( array( 'do' => 'content' ) ), ceil( $results->count( TRUE ) / $query->resultsToGet ), $page, $query->resultsToGet ) );
			$output = \IPS\Theme::i()->getTemplate('profile')->userContentStream( $this->member, $results, $pagination );
		}
		else
		{
			$currentClass = $types[ $currentAppModule ][ $currentType ];
			$currentAppArray = explode( '_', $currentAppModule );
			$currentApp = $currentAppArray[0];
			if( isset( $hasCallback[ $currentType ] ) )
			{
				$output	= $hasCallback[ $currentType ]->customTableHelper( $currentClass, \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=content", 'front', 'profile_content', $this->member->members_seo_name )->setQueryString( array( 'type' => $currentType ) ), array( array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['author'] . '=?', $this->member->member_id ) ) );
			}
			else
			{
				$where = array();
				$where[] = array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['author'] . '=?', $this->member->member_id );
				if ( isset( $currentClass::$databaseColumnMap['state'] ) )
				{
					$where[] = array( $currentClass::$databaseTable . '.' . $currentClass::$databasePrefix . $currentClass::$databaseColumnMap['state'] . ' != ?', 'link' );
				}
				
				if( method_exists( $currentClass, 'commentWhere' ) AND $currentClass::commentWhere() !== NULL )
				{
					$where[] = $currentClass::commentWhere();
				}

				$output = new \IPS\Helpers\Table\Content( $currentClass, \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=content", 'front', 'profile_content', $this->member->members_seo_name )->setQueryString( array( 'type' => $currentType ) ), $where, NULL, NULL, 'read', FALSE );
			}
			
			if ( $currentType == 'core_statuses_status' )
			{
				$output->noModerate = TRUE;
			}
			
			$output->showFilters = FALSE;
			$output->classes[] = 'cProfileContent';
		}
		
		/* If we've clicked from the tab section */
		if ( \IPS\Request::i()->isAjax() && \IPS\Request::i()->change_section )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile' )->userContentSection( $this->member, $types, $currentAppModule, $currentType, (string) $output );
		}
		else
		{
			/* Display */
			\IPS\Output::i()->title = $this->member->name;
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/statuses.css' ) );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile' )->userContent( $this->member, $types, $currentAppModule, $currentType, (string) $output );
		}
	}
	
	/**
	 * Show Reputation
	 *
	 * @return	void
	 */
	public function reputation()
	{
		if ( !\IPS\Member::loggedIn()->group['gbw_view_reps'] )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C138/B', 403, '' );
		}
		
		/* Get the different types */
		$types = array();
		$hasCallback = array();
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'profile.css', $class::$application ) );
			
			if ( in_array( 'IPS\Content\Reputation', class_implements( $class ) ) )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) ) ] = $class;
			}
			
			if ( isset( $class::$commentClass ) and in_array( 'IPS\Content\Reputation', class_implements( $class::$commentClass ) ) )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$commentClass, 4 ) ) ) ] = $class::$commentClass;
			}
			
			if ( isset( $class::$reviewClass ) and in_array( 'IPS\Content\Reputation', class_implements( $class::$reviewClass ) ) )
			{
				$types[ $class::$application . '_' . $class::$module ][ mb_strtolower( str_replace( '\\', '_', mb_substr( $class::$reviewClass, 4 ) ) ) ] = $class::$reviewClass;
			}
		}
		
		/* What type are we looking at? */
		$currentAppModule = NULL;
		$currentType = NULL;
		if ( isset( \IPS\Request::i()->type ) )
		{
			foreach ( $types as $appModule => $_types )
			{
				if ( array_key_exists( \IPS\Request::i()->type, $_types ) )
				{
					$currentAppModule = $appModule;
					$currentType = \IPS\Request::i()->type;
					break;
				}
			}
		}		
		if ( $currentType === NULL )
		{
			foreach ( $types as $appModule => $_types )
			{
				foreach ( $_types as $key => $class )
				{
					$currentAppModule = $appModule;
					$currentType = $key;
					break 2;
				}
			}
		}
		$currentClass = $types[ $currentAppModule ][ $currentType ];
		$currentAppArray = explode( '_', $currentAppModule );
		$currentApp = $currentAppArray[0];
		
		/* Build Output */
		$url = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$this->member->member_id}&do=reputation&type={$currentType}", 'front', 'profile_reputation', array( $this->member->members_seo_name ) );
		$where = array( array( '( core_reputation_index.member_id=? OR core_reputation_index.member_received=? ) AND core_reputation_index.app=? AND core_reputation_index.type=?', $this->member->member_id, $this->member->member_id, $currentClass::$application, $currentClass::$reputationType ) );

		$table = new \IPS\Helpers\Table\Content( $currentClass, $url, $where, NULL, \IPS\Content\Hideable::FILTER_AUTOMATIC, 'read', FALSE );
		$table->joinContainer = TRUE;
		$table->sortOptions = array( 'rep_date' );
		$table->sortBy = 'rep_date';
		$table->joins = array(
			array( 'select' => "core_reputation_index.id AS rep_id, core_reputation_index.rep_date, core_reputation_index.rep_rating, core_reputation_index.member_received as rep_member_received, core_reputation_index.member_id as rep_member", 'from' => 'core_reputation_index', 'where' => array( "core_reputation_index.type_id=" . $currentClass::$databaseTable . "." . $currentClass::$databasePrefix . $currentClass::$databaseColumnId  ) ),
		);
		
		$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'profile', 'core' ), 'userReputationTable' );
		$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'profile', 'core' ), 'userReputationRows' );
		$table->showAdvancedSearch = FALSE;
		$table->include = array( 'obj', 'reputationData' );
		$table->parsers = array(
			'obj' => function( $val, $row ) use ( $currentClass )
			{
				try
				{
					$return = $currentClass::constructFromData( $row );
					if ( $return instanceof \IPS\Content\Comment )
					{
						$return->item(); // Prevent the exception later
					}
					return $return;
				}
				catch ( \OutOfRangeException $e )
				{
					if ( $row['rep_id'] )
					{
						\IPS\Db::i()->delete( 'core_reputation_index', array( 'id=?', $row['rep_id'] ) );
					}
				}

			},
			'reputationData' => function( $val, $row )
			{
				return array(
					'rep_date'				=> $row['rep_date'],
					'rep_rating'			=> $row['rep_rating'],
					'rep_member'			=> $row['rep_member'],
					'rep_member_received'	=> $row['rep_member_received'],
				);
			}
		);

		/* Display */
		if ( \IPS\Request::i()->isAjax() && \IPS\Request::i()->change_section )
		{
			\IPS\Output::i()->sendOutput( (string) $table );
		}
		else
		{		
			\IPS\Output::i()->title = $this->member->name;
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile' )->userReputation( $this->member, $types, $currentAppModule, $currentType, (string) $table );
		}
	}
	
	/**
	 * Toggle Visitors
	 *
	 * @return	void
	 */
	protected function visitors()
	{
		if ( !\IPS\Member::loggedIn()->modPermission('can_modify_profiles') AND ( \IPS\Member::loggedIn()->member_id !== $this->member->member_id OR !$this->member->group['g_edit_profile'] ) )
		{
			\IPS\Output::i()->error( 'no_permission_edit_profile', '2C138/3', 403, '' );
		}

		\IPS\Session::i()->csrfCheck();
		
		if ( \IPS\Request::i()->state == 0 )
		{
			$this->member->members_bitoptions['pp_setting_count_visitors']	= FALSE;
			$visitors = array();
		}
		else
		{
			$this->member->members_bitoptions['pp_setting_count_visitors']	= TRUE;

			/* Get visitor data */
			$visitors = array();
			$visitorInfo = json_decode( $this->member->pp_last_visitors, TRUE );
			if ( is_array( $visitorInfo ) )
			{
				foreach( $visitorInfo as $id => $time )
				{
					$visitor = \IPS\Member::load( $id );
					if ( $visitor->member_id )
					{
						$visitors[$id]['member'] = $visitor;
						$visitors[$id]['visit_time'] = $time;
					}
				}
			}	
		}

		$this->member->save();

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile', 'core' )->recentVisitorsBlock( $this->member, $visitors );
		}
		else
		{
			\IPS\Output::i()->redirect( $this->member->url(), 'saved' );
		}
	}
	
	/**
	 * Edit Status
	 *
	 * @return	void
	 */
	protected function editStatus()
	{
		try
		{
			$status = \IPS\core\Statuses\Status::loadAndCheckPerms( \IPS\Request::i()->status );
			if ( !$status->canEdit() )
			{
				throw new \DomainException;
			}
						
			$form = new \IPS\Helpers\Form( 'form', 'status_save' );
			$form->class = 'ipsForm_vertical';
			
			$formElements = \IPS\core\Statuses\Status::formElements( $status );
			$form->add( $formElements['status_content'] );

			if ( $values = $form->values() )
			{
				$status->processForm( $values );
				$status->save();
				$status->processAfterEdit( $values );

				\IPS\Output::i()->redirect( $status->url() );
			}
			
			\IPS\Output::i()->output = $form;
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C138/A', 404, '' );
		}
	}
	
	/**
	 * Edit Profile
	 *
	 * @return	void
	 */
	protected function edit()
	{
		/* Do we have permission? */
		if ( !\IPS\Member::loggedIn()->modPermission('can_modify_profiles') and ( \IPS\Member::loggedIn()->member_id !== $this->member->member_id or !$this->member->group['g_edit_profile'] ) )
		{
			\IPS\Output::i()->error( 'no_permission_edit_profile', '2S147/1', 403, '' );
		}
		
		$form = $this->buildEditForm();
		
		/* Handle the submission */
		if ( $values = $form->values() )
		{
			$this->_saveMember( $form, $values );

			\IPS\Output::i()->redirect( $this->member->url() );
		}
		
		/* Set Session Location */
		\IPS\Session::i()->setLocation( $this->member->url(), array(), 'loc_editing_profile', array( $this->member->name => FALSE ) );
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->editContentForm( \IPS\Member::loggedIn()->language()->addToStack( 'profile_edit' ), $form );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) ) );
	}


	/**
	 * Build Edit Form
	 *
	 * @return \IPS\Helpers\Form
	 */
	protected function buildEditForm()
	{
		/* Build the form */
		$form = new \IPS\Helpers\Form;

		/* The basics */
		$form->addTab( 'profile_edit_basic_tab', 'user');
		$form->addHeader( 'profile_edit_basic_header' );
		if( \IPS\Settings::i()->post_titlechange != -1 and ( isset( \IPS\Settings::i()->post_titlechange ) and $this->member->member_posts >= \IPS\Settings::i()->post_titlechange ) )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'member_title', $this->member->member_title, FALSE, array( 'maxLength' => 64 ) ) );
		}

		$form->add( new \IPS\Helpers\Form\Custom( 'bday', array( 'year' => $this->member->bday_year, 'month' => $this->member->bday_month, 'day' => $this->member->bday_day ), FALSE, array( 'getHtml' => function( $element )
		{
			return strtr( \IPS\Member::loggedIn()->language()->preferredDateFormat(), array(
					'dd'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_day( $element->name, $element->value, $element->error ),
					'mm'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_month( $element->name, $element->value, $element->error ),
					'yy'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
					'yyyy'	=> \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->bdayForm_year( $element->name, $element->value, $element->error ),
			) );
		} ) ) );
		if ( \IPS\Settings::i()->profile_comments and $this->member->canAccessModule( \IPS\Application\Module::get( 'core', 'status' ) ) AND !$this->member->members_bitoptions['bw_no_status_update'] )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'enable_status_updates', $this->member->pp_setting_count_comments ) );
		}

		/* Profile fields */
		try
		{
			$values = \IPS\Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch( \UnderflowException $e )
		{
			$values	= array();
		}

		foreach ( \IPS\core\ProfileFields\Field::fields( $values, \IPS\core\ProfileFields\Field::PROFILE ) as $group => $fields )
		{
			$form->addHeader( "core_pfieldgroups_{$group}" );
			foreach ( $fields as $field )
			{
				$form->add( $field );
			}
		}

		/* Moderator stuff */
		if ( ( \IPS\Member::loggedIn()->modPermission('can_modify_profiles') OR \IPS\Member::loggedIn()->modPermission('can_unban') ) AND \IPS\Member::loggedIn()->member_id != $this->member->member_id )
		{
			$form->add( new \IPS\Helpers\Form\Editor( 'signature',  $this->member->signature, FALSE, array( 'app' => 'core', 'key' => 'Signatures', 'autoSaveKey' => "frontsig-" . $this->member->member_id, 'attachIds' => array(  $this->member->member_id ) ) ) );

			if ( \IPS\Member::loggedIn()->modPermission('can_unban') )
			{
				$form->addTab( 'profile_edit_moderation', 'times' );

				if ( $this->member->mod_posts !== 0 )
				{
					$form->add( new \IPS\Helpers\Form\YesNo( 'remove_mod_posts', NULL, FALSE ) );
				}

				if ( $this->member->restrict_post !== 0 )
				{
					$form->add( new \IPS\Helpers\Form\YesNo( 'remove_restrict_post', NULL, FALSE ) );
				}

				if ( $this->member->temp_ban !== 0 )
				{
					$form->add( new \IPS\Helpers\Form\YesNo( 'remove_ban', NULL, FALSE ) );
				}
			}
		}

		return $form;
	}

	/**
	 * Save Member
	 *
	 * @param $form
	 * @param array $values
	 */
	protected function _saveMember( $form, array $values )
	{
		if( ( \IPS\Settings::i()->post_titlechange == -1 or ( isset( \IPS\Settings::i()->post_titlechange ) and $this->member->member_posts >= \IPS\Settings::i()->post_titlechange ) ) AND isset( $values['member_title'] ) )
		{
			$this->member->member_title = $values['member_title'];
		}

		if( $values['bday']  and ( ( $values['bday']['day'] and !$values['bday']['month'] ) or ( $values['bday']['month'] and !$values['bday']['day'] ) ) )
		{
			$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'bday_month_and_day_required' );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->editContentForm( \IPS\Member::loggedIn()->language()->addToStack( 'profile_edit' ), $form );
			return;
		}

		if ( $values['bday'] and $values['bday']['day'] and $values['bday']['month'] )
		{
			$this->member->bday_day		= $values['bday']['day'];
			$this->member->bday_month	= $values['bday']['month'];
			$this->member->bday_year	= $values['bday']['year'];
		}
		else
		{
			$this->member->bday_day = NULL;
			$this->member->bday_month = NULL;
			$this->member->bday_year = NULL;
		}

		if ( isset( $values['enable_status_updates'] ) )
		{
			$this->member->pp_setting_count_comments = $values['enable_status_updates'];

			if ( $values['enable_status_updates'] )
			{
				\IPS\Content\Search\Index::i()->massUpdate( 'IPS\core\Statuses\Status', NULL, NULL, '*', NULL, NULL, $this->member->member_id );
			}
			else
			{
				\IPS\Content\Search\Index::i()->massUpdate( 'IPS\core\Statuses\Status', NULL, NULL, '', NULL, NULL, $this->member->member_id );
			}
		}

		/* Profile Fields */
		try
		{
			$profileFields = \IPS\Db::i()->select( '*', 'core_pfields_content', array( 'member_id=?', $this->member->member_id ) )->first();
		}
		catch( \UnderflowException $e )
		{
			$profileFields = array();
		}

		/* If the row only contains one column (eg. member_id) then the result of the query is a string, we do not want this */
		if ( !is_array( $profileFields ) )
		{
			$profileFields = array();
		}

		$profileFields['member_id'] = $this->member->member_id;

		foreach ( \IPS\core\ProfileFields\Field::fields( $profileFields, \IPS\core\ProfileFields\Field::PROFILE ) as $group => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				$profileFields[ "field_{$id}" ] = $field::stringValue( $values[ $field->name ] );

				if ( $field instanceof \IPS\Helpers\Form\Editor )
				{
					\IPS\core\ProfileFields\Field::load( $id )->claimAttachments( $this->member->member_id );
				}
			}

			$this->member->changedCustomFields = $profileFields;
		}

		/* Moderator stuff */
		if ( \IPS\Member::loggedIn()->modPermission('can_modify_profiles') AND \IPS\Member::loggedIn()->member_id != $this->member->member_id)
		{
			if ( isset( $values['remove_mod_posts'] ) AND $values['remove_mod_posts'] )
			{
				$this->member->mod_posts = 0;
			}

			if ( isset( $values['remove_restrict_post'] ) AND $values['remove_restrict_post'] )
			{
				$this->member->restrict_post = 0;
			}

			if ( isset( $values['remove_ban'] ) AND $values['remove_ban'] )
			{
				$this->member->temp_ban = 0;
			}

			if ( isset( $values['signature'] ) )
			{
				$this->member->signature = $values['signature'];
			}
		}

		/* Save */
		\IPS\Db::i()->replace( 'core_pfields_content', $profileFields );
		$this->member->save();
	}

	/**
	 * Edit Photo
	 *
	 * @return	void
	 */
	protected function editPhoto()
	{
		if ( !\IPS\Member::loggedIn()->modPermission('can_modify_profiles') and ( \IPS\Member::loggedIn()->member_id !== $this->member->member_id or !$this->member->group['g_edit_profile'] ) )
		{
			\IPS\Output::i()->error( 'no_permission_edit_profile', '2C138/9', 403, '' );
		}

		$photoVars = explode( ':', $this->member->group['g_photo_max_vars'] );
		
		/* Init */
		$form = new \IPS\Helpers\Form;
		$form->ajaxOutput = TRUE;
		$toggles = array( 'custom' => array( 'member_photo_upload' ), 'url' => array( 'member_photo_url' ) );
		$extra = array();
		$options = array();

		/* Can we upload? */
		if ( $photoVars[0] )
		{
			$options['custom'] = 'member_photo_upload';
		}
		
		/* We can always use URLs */
		$options['url'] = 'member_photo_url';
		
		/* Can we use gallery images? */
		if ( \IPS\Application::appIsEnabled('gallery') AND $this->member->pp_photo_type == 'gallery_Images' )
		{
			$options['gallery_Images'] = 'member_gallery_image';
		}
		
		/* Can we use Gravatar? */
		if ( \IPS\Settings::i()->allow_gravatars )
		{
			$options['gravatar'] = 'member_photo_gravatar';
			if ( $this->member->member_id === \IPS\Member::loggedIn()->member_id or \IPS\Member::loggedIn()->modPermission('can_see_emails') )
			{
				$extra[] = new \IPS\Helpers\Form\Email( 'photo_gravatar_email_public', $this->member->pp_gravatar ?: $this->member->email, FALSE, array( 'maxLength' => 255 ), NULL, NULL, NULL, 'member_photo_gravatar' );
				$toggles['gravatar'] = array( 'member_photo_gravatar' );
			}
		}
		
		/* ProfileSync (Facebook, etc.) */
		foreach ( \IPS\core\ProfileSync\ProfileSyncAbstract::services() as $key => $class )
		{
			$obj = new $class( $this->member );
			if ( $obj->connected() )
			{
				$langKey = 'profilesync__' . $key;
				$options[ 'sync-' . $key ] = \IPS\Member::loggedIn()->language()->addToStack( 'member_photo_sync' , FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $langKey ) ) ) );
			}
		}
		
		/* And of course we can always not have a photo */
		$options['none'] = 'member_photo_none';
		
		/* Create that selection */
		$form->add( new \IPS\Helpers\Form\Radio( 'pp_photo_type', $this->member->pp_photo_type, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		
		/* Create the upload field */		
		if ( $photoVars[0] )
		{
			$form->add( new \IPS\Helpers\Form\Upload( 'member_photo_upload', NULL, FALSE, array( 'image' => array( 'maxWidth' => $photoVars[1], 'maxHeight' => $photoVars[2] ), 'storageExtension' => 'core_Profile', 'maxFileSize' => $photoVars[0] ? $photoVars[0] / 1024 : NULL ), NULL, NULL, NULL, 'member_photo_upload' ) );
		}
		
		/* Create the URL */
		$form->add( new \IPS\Helpers\Form\Url( 'member_photo_url', NULL, FALSE, array( 'file' => 'core_Profile', 'allowedMimes' => 'image/*', 'maxFileSize' => $photoVars[0] ? $photoVars[0] / 1024 : NULL ), NULL, NULL, NULL, 'member_photo_url' ) );
		
		/* Add additional elements */
		foreach ( $extra as $element )
		{
			$form->add( $element );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{			
			$this->member->pp_photo_type = $values['pp_photo_type'];
			
			/* If we are changing our photo, it is safe to assume that any services currently set to sync should no longer do so */
			$services = \IPS\core\ProfileSync\ProfileSyncAbstract::services();
			
			if ( !empty( $services ) )
			{
				foreach( $services AS $class )
				{
					$obj = new $class( $this->member );
					$obj->save( array( 'profilesync_photo' => FALSE ) );
				}
			}
			
			switch ( $values['pp_photo_type'] )
			{
				case 'custom':
					if ( $values['member_photo_upload'] )
					{
						if ( (string) $values['member_photo_upload'] !== '' )
						{
							$this->member->pp_photo_type  = 'custom';
							$this->member->pp_main_photo  = NULL;
							$this->member->pp_main_photo  = (string) $values['member_photo_upload'];
							
							$thumbnail = $values['member_photo_upload']->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE, TRUE );
							$this->member->pp_thumb_photo = (string) $thumbnail;
							
							$this->member->photo_last_update = time();
						}
					}
					break;
			
				case 'url':
					if( $values['member_photo_url'] )
					{
						$this->member->pp_photo_type = 'custom';
						$this->member->pp_main_photo = NULL;
						$this->member->pp_main_photo = (string) $values['member_photo_url'];
						
						$thumbnail = $values['member_photo_url']->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE, TRUE );
						$this->member->pp_thumb_photo = (string) $thumbnail;
						
						$this->member->photo_last_update = time();
					}
					break;
						
				case 'none':
					$this->member->pp_main_photo								= NULL;
					$this->member->members_bitoptions['bw_disable_gravatar']	= 1;
					$this->member->photo_last_update = NULL;
					break;
					
				case 'gravatar':
					$this->member->pp_gravatar = ( !isset( $values['photo_gravatar_email_public'] ) or $values['photo_gravatar_email_public'] === $this->member->email ) ? NULL : $values['photo_gravatar_email_public'];
					break;
						
				default:
					if ( mb_substr( $values['pp_photo_type'], 0, 5 ) === 'sync-' )
					{
						$class = 'IPS\core\ProfileSync\\' . mb_substr( $values['pp_photo_type'], 5 );
						$obj = new $class( $this->member );
						$obj->save( array( 'profilesync_photo' => TRUE ) );
					}
			}
							
			$this->member->save();
			
			if ( $this->member->pp_photo_type == 'custom' )
			{
				if ( \IPS\Request::i()->isAjax() )
				{					
					$this->cropPhoto();
					return;
				}
				else
				{
					\IPS\Output::i()->redirect( $this->member->url()->setQueryString( 'do', 'cropPhoto' ) );
				}
			}
			else
			{
				\IPS\Output::i()->redirect( $this->member->url() );
			}
		}
		
		/* Display */
		\IPS\Session::i()->setLocation( $this->member->url(), array(), 'loc_editing_profile', array( $this->member->name => FALSE ) );
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'editing_profile', FALSE, array( 'sprintf' => array( $this->member->name ) ) ) );
	}
	
	/**
	 * Get photo for cropping
	 * If the photo is on a different domain to the JS that handles cropping,
	 * it will be blocked because of CORS. See notes in Cropper documentation.
	 *
	 * @return	void
	 */
	protected function cropPhotoGetPhoto()
	{
		\IPS\Session::i()->csrfCheck();
		$original = \IPS\File::get( 'core_Profile', $this->member->pp_main_photo );
		$headers = array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'inline', $original->filename ) );
		\IPS\Output::i()->sendOutput( $original->contents(), 200, \IPS\File::getMimeType( $original->filename ), $headers );
	}
	
	/**
	 * Crop Photo
	 *
	 * @return	void
	 */
	protected function cropPhoto()
	{
		if( !$this->member->pp_main_photo )
		{
			\IPS\Output::i()->error( 'no_photo_to_crop', '2C138/C', 404, '' );
		}

		/* Get the photo */
		$original = \IPS\File::get( 'core_Profile', $this->member->pp_main_photo );
		$image = \IPS\Image::create( $original->contents() );
		
		/* Work out which dimensions to suggest */
		if ( $image->width < $image->height )
		{
			$suggestedWidth = $suggestedHeight = $image->width;
		}
		else
		{
			$suggestedWidth = $suggestedHeight = $image->height;
		}
		
		/* Build form */
		$form = new \IPS\Helpers\Form( 'photo_crop', 'save', $this->member->url()->setQueryString( 'do', 'cropPhoto' ) );
		$form->class = 'ipsForm_noLabels';
		$member = $this->member;
		$form->add( new \IPS\Helpers\Form\Custom('photo_crop', array( 0, 0, $suggestedWidth, $suggestedHeight ), FALSE, array(
			'getHtml'	=> function( $field ) use ( $original, $member )
			{
				return \IPS\Theme::i()->getTemplate('profile')->photoCrop( $field->name, $field->value, $member->url()->setQueryString( 'do', 'cropPhotoGetPhoto' )->csrf() );
			}
		) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			try
			{
				/* Create new file */
				$image->cropToPoints( $values['photo_crop'][0], $values['photo_crop'][1], $values['photo_crop'][2], $values['photo_crop'][3] );
				
				/* Delete the current thumbnail */					
				if ( $this->member->pp_thumb_photo )
				{
					try
					{
						\IPS\File::get( 'core_Profile', $this->member->pp_thumb_photo )->delete();
					}
					catch ( \Exception $e ) { }
				}
								
				/* Save the new */
				$cropped = \IPS\File::create( 'core_Profile', $original->filename, (string) $image );
				$this->member->pp_thumb_photo = (string) $cropped->thumbnail( 'core_Profile', \IPS\PHOTO_THUMBNAIL_SIZE, \IPS\PHOTO_THUMBNAIL_SIZE );
				$this->member->save();
								
				/* Edited member, so clear widget caches (stats, widgets that contain photos, names and so on) */
				\IPS\Widget::deleteCaches();
								
				/* Redirect */
				\IPS\Output::i()->redirect( $this->member->url() );
			}
			catch ( \Exception $e )
			{
				$form->error = 'photo_crop_bad';
			}
		}
		
		/* Display */
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Moderate
	 *
	 * @return	void
	 */
	protected function moderate()
	{
		\IPS\Session::i()->csrfCheck();

		try
		{		
			$item = ( \IPS\Request::i()->type == 'status' ) ? \IPS\core\Statuses\Status::load( \IPS\Request::i()->status ) : \IPS\core\Statuses\Reply::load( \IPS\Request::i()->reply );

			$item->modAction( \IPS\Request::i()->action, \IPS\Member::loggedIn() );
				
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( 'OK' );
			}
			else
			{
				if( \IPS\Request::i()->action == 'delete' )
				{
					\IPS\Output::i()->redirect( ( $item instanceof \IPS\core\Statuses\Status ) ? \IPS\Member::load( $item->member_id )->url()->setQueryString( array( 'do' => 'content', 'type' => 'core_statuses_status' ) ) : \IPS\core\Statuses\Status::load( $item->status_id )->url());
				}
				else
				{
					if ( isset( \IPS\Request::i()->_fromFeed ) )
					{
						\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=status&controller=feed' )->setFragment( 'status-' . $item->id ), 'mod_confirm_' . \IPS\Request::i()->action );
					}
					else
					{
						\IPS\Output::i()->redirect( $item->url()->setQueryString( 'tab', 'statuses' )->setFragment( 'status-' . $item->id ), 'mod_confirm_' . \IPS\Request::i()->action );
					}
				}
			}
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C138/5', 404, '' );
		}
	}
	
	/**
	 * Rep Status/Comment
	 *
	 * @return	void
	 */
	protected function rep()
	{		
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$item = ( \IPS\Request::i()->type == 'status' ) ? \IPS\core\Statuses\Status::load( \IPS\Request::i()->status ) : \IPS\core\Statuses\Reply::load( \IPS\Request::i()->reply );
							
			$type = intval( \IPS\Request::i()->rep ) === 1 ? 1 : -1;
			$item->giveReputation( $type );
				
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->reputationMini( $item->reputation(), $item->canGiveReputation( 1 ), $item->canGiveReputation( -1 ), $item->url('showRep'), $item->url('rep') ) );
			}
			else
			{
				\IPS\Output::i()->redirect( $item->url() );
			}
		}
		catch ( \DomainException $e )
		{
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array( 'error' => \IPS\Member::loggedIn()->language()->addToStack( $e->getMessage() ) ), 403 );
			}
			else
			{
				\IPS\Output::i()->error( $e->getMessage(), '1C138/2', 403, '' );
			}
		}
	}
	
	/**
	 * Show Comment/Review Rep
	 *
	 * @return	void
	 */
	protected function showRep()
	{
		try
		{
			$item = ( \IPS\Request::i()->type == 'status' ) ? \IPS\core\Statuses\Status::load( \IPS\Request::i()->status ) : \IPS\core\Statuses\Reply::load( \IPS\Request::i()->reply );
							
			\IPS\Output::i()->output = $item->reputationTable();
		}
		catch ( \LogicException $e )
		{
			\IPS\Output::i()->error( 'cannot_view_reputation', '2C138/8', 403, '' );
		}
	}
	
	/**
	 * Report Status
	 *
	 * @return	void
	 */
	protected function report()
	{
		try
		{
			$itemClass		= '\IPS\core\Statuses\Status';
			$commentClass	= '\IPS\core\Statuses\Reply';
			$item			= ( \IPS\Request::i()->type == 'status' ) ? $itemClass::load( \IPS\Request::i()->status ) : $commentClass::load( \IPS\Request::i()->reply );
			$canReport		= $item->canReport();
			
			if ( $canReport !== TRUE )
			{
				\IPS\Output::i()->error( 'generic_error', '1C138/6', 403, '' );
			}
			
			$form			= new \IPS\Helpers\Form( NULL, 'report_submit' );
			$form->class	= 'ipsForm_vertical';
			$idColumn		= ( \IPS\Request::i()->type == 'status' ) ? $itemClass::$databaseColumnId : $commentClass::$databaseColumnId;
			$autoSaveKey	= ( \IPS\Request::i()->type == 'status' ) ? "report-{$itemClass::$application}-{$itemClass::$module}-{$item->$idColumn}" : "report-{$itemClass::$application}-{$itemClass::$module}-{$item->status_id}-{$item->$idColumn}";
			$form->add( new \IPS\Helpers\Form\Editor( 'report_message', NULL, FALSE, array( 'app' => 'core', 'key' => 'Reports', 'autoSaveKey' => $autoSaveKey, 'minimize' => 'report_message_placeholder' ) ) );
			if( !\IPS\Member::loggedIn()->member_id )
			{
				$form->add( new \IPS\Helpers\Form\Captcha );
			}
			if ( $values = $form->values() )
			{
				$report = $item->report( $values['report_message'] );
				\IPS\File::claimAttachments( $autoSaveKey, $report->id );
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->sendOutput( \IPS\Member::loggedIn()->language()->addToStack('report_submit_success') );
				}
				else
				{
					\IPS\Output::i()->redirect( $item->url(), 'report_submit_success' );
				}
			}
			
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'report_content' );
			\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
		}
		catch( \LogicException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C138/7', 404, '' );
		}
	}
	
	/**
	 * Followers
	 *
	 * @return	void
	 */
	protected function followers()
	{
		$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$limit		= array( ( $page - 1 ) * 50, 50 );
		$followers	= $this->member->followers( ( \IPS\Member::loggedIn()->isAdmin() OR \IPS\Member::loggedIn()->member_id === $this->member->member_id ) ? \IPS\Member::FOLLOW_PUBLIC + \IPS\Member::FOLLOW_ANONYMOUS : \IPS\Member::FOLLOW_PUBLIC, array( 'immediate', 'daily', 'weekly' ), NULL, $limit, 'name' );

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('members_followers', FALSE, array( 'sprintf' => array( $this->member->name ) ) );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('members_followers', FALSE, array( 'sprintf' => array( $this->member->name ) ) ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'profile' )->allFollowers( $this->member, $followers );
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	protected function _coverPhotoStorageExtension()
	{
		return 'core_Profile';
	}
	
	/**
	 * Set Cover Photo
	 *
	 * @param	\IPS\Helpers\CoverPhoto	$photo	New Photo
	 * @return	void
	 */
	protected function _coverPhotoSet( \IPS\Helpers\CoverPhoto $photo )
	{
		$this->member->pp_cover_photo = (string) $photo->file;
		$this->member->pp_cover_offset = (int) $photo->offset;
		$this->member->save();
	}
	
	/**
	 * Get Cover Photo
	 *
	 * @return	\IPS\Helpers\CoverPhoto
	 */
	protected function _coverPhotoGet()
	{
		return $this->member->coverPhoto();
	}
}