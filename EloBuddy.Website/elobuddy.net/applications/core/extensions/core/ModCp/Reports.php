<?php
/**
 * @brief		Moderator Control Panel Extension: Reports
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		24 Oct 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Report Center
 */
class _Reports extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\core\Reports\Report';
	
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string
	 */
	public function getTab()
	{
		/* Check Permissions */
		if ( ! \IPS\Member::loggedIn()->modPermission('can_view_reports') )
		{
			return null;
		}
		
		return 'reports';
	}
		
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{		
		if ( !\IPS\Member::loggedIn()->modPermission( 'can_view_reports' ) )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C139/1', 403, '' );
		}
		
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/modcp.css' ) );
		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/modcp_responsive.css' ) );
		}
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_modcp.js', 'core' ) );
		
		parent::execute();
	}
	
	/**
	 * Overview
	 *
	 * @return	void
	 */
	public function manage()
	{		
		$where = '( perm_id IN (?) OR perm_id IS NULL )';
		if( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->overview ) )
		{
			$where .= " AND status IN( 1,2 )";
		}

		/* fetch only reports for content of enabled applications */
		$where .= " AND " . \IPS\Db::i()->in( 'class', array_merge( array( 'IPS\core\Messenger\Conversation', 'IPS\core\Messenger\Message' ), array_values( \IPS\Content::routedClasses( FALSE, TRUE ) ) ) );

		$table = new \IPS\Helpers\Table\Content( '\IPS\core\Reports\Report', \IPS\Http\Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports', NULL, 'modcp_reports' ), array( array( $where, \IPS\Db::i()->select( 'perm_id', 'core_permission_index', \IPS\Db::i()->findInSet( 'perm_view', array_merge( array( \IPS\Member::loggedIn()->member_group_id ), array_filter( explode( ',', \IPS\Member::loggedIn()->mgroup_others ) ) ) ) . " OR perm_view='*'" ) ) ) );
		$table->sortBy = $table->sortBy ?: 'first_report_date';
		$table->sortDirection = 'desc';
		
		/* Title is a special case in the Report center class that isn't available in the core_rc_index table so attempting to sort on it throws an error and does nothing */
		unset( $table->sortOptions['title'] );
		
		$table->filters = array( 'report_status_1' => array( 'status=1' ), 'report_status_2' => array( 'status=2' ), 'report_status_3' => array( 'status=3' ) );
		
		$table->title = \IPS\Member::loggedIn()->language()->addToStack( 'report_center_header' );

		if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->overview ) )
		{
			$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'modcp', 'core' ), 'reportListOverview' );
			\IPS\Output::i()->json( array( 'data' => (string) $table ) );
		}
		else
		{
			\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'modcp_reports' ) );
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'modcp_reports' );
			return  \IPS\Theme::i()->getTemplate( 'modcp' )->reportList( (string) $table );
		}
	}
	
	/**
	 * View a report
	 *
	 * @return	void
	 */
	public function view()
	{
		/* Load Report */
		try
		{
			$report = \IPS\core\Reports\Report::loadAndCheckPerms( \IPS\Request::i()->id );
			$report->markRead();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C139/3', 404, '' );
		}
		
		/* Setting status? */
		if( isset( \IPS\Request::i()->setStatus ) and in_array( \IPS\Request::i()->setStatus, range( 1, 3 ) ) )
		{
			\IPS\Session::i()->csrfCheck();
			
			$report->status = (int) \IPS\Request::i()->setStatus;
			$report->save();
		}

		/* Deleting? */
		if( isset( \IPS\Request::i()->_action ) and \IPS\Request::i()->_action == 'delete' and $report->canDelete() )
		{
			\IPS\Session::i()->csrfCheck();
			
			$report->delete();
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=modcp&controller=modcp&tab=reports', NULL, 'modcp_reports' ) );
		}

		/* Load */
		$comment = NULL;
		$item = NULL;
		$ref = NULL;
		try
		{
			$thing = call_user_func( array( $report->class, 'load' ), $report->content_id );
			if ( $thing instanceof \IPS\Content\Comment )
			{
				$comment = $thing;
				$item = $comment->item();
				
				$class = $report->class;
				$itemClass = $class::$itemClass;
				$ref = $thing->warningRef();
			}
			else
			{
				$item = $thing;
				$itemClass = $report->class;
				$ref = $thing->warningRef();
			}
		}
		catch ( \OutOfRangeException $e ) { }
		
		/* Next/Previous Links */
		$permSubQuery = \IPS\Db::i()->select( 'perm_id', 'core_permission_index', \IPS\Db::i()->findInSet( 'perm_view', array_merge( array( \IPS\Member::loggedIn()->member_group_id ) , array_filter( explode( ',', \IPS\Member::loggedIn()->mgroup_others ) ) ) ) . " or perm_view='*'" );

		$prevReport	= NULL;
		$prevItem	= NULL;
		$nextReport	= NULL;
		$nextItem	= NULL;
		
		/* Prev */
		try
		{
			$prevReport = \IPS\Db::i()->select( 'id, class, content_id', 'core_rc_index', array( '( perm_id IN (?) OR perm_id IS NULL ) AND first_report_date>?', $permSubQuery, $report->first_report_date ), 'first_report_date ASC', 1 )->first();
			
			try
			{
				$prevItem = call_user_func( array( $prevReport['class'], 'load' ), $prevReport['content_id'] );
			}
			catch (\OutOfRangeException $e) {}
				
			if ( $prevItem instanceof \IPS\Content\Comment )
			{
				$prevItem = $prevItem->item();
			}
		}
		catch ( \UnderflowException $e ) {}
		
		/* Next */
		try
		{
			$nextReport = \IPS\Db::i()->select( 'id, class, content_id', 'core_rc_index', array( '( perm_id IN (?) OR perm_id IS NULL ) AND first_report_date<?', $permSubQuery, $report->first_report_date ), 'first_report_date DESC', 1 )->first();

			try
			{
				$nextItem = call_user_func( array( $nextReport['class'], 'load' ), $nextReport['content_id'] );
			}
			catch (\OutOfRangeException $e) {}
 				
			if ( $nextItem instanceof \IPS\Content\Comment )
			{
				$nextItem = $nextItem->item();
			}
		}
		catch ( \UnderflowException $e ) {}

		/* Display */
		if ( \IPS\Request::i()->isAjax() and !isset( \IPS\Request::i()->_contentReply ) and !isset( \IPS\Request::i()->getUploader ) AND !isset( \IPS\Request::i()->page ) )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'modcp' )->reportPanel( $report, $comment, $ref );
		}
		else
		{
			$sprintf = $item ? htmlspecialchars( $item->mapped('title'), \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) : \IPS\Member::loggedIn()->language()->addToStack('content_deleted');
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'modcp_reports_view', FALSE, array( 'sprintf' => array( $sprintf ) ) );
			\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=modcp&controller=reports', 'front', 'modcp_reports' ), \IPS\Member::loggedIn()->language()->addToStack( 'modcp_reports' ) );
			\IPS\Output::i()->breadcrumb[] = array( NULL, $item ? $item->mapped('title') : \IPS\Member::loggedIn()->language()->addToStack( 'content_deleted' ) );
			return \IPS\Theme::i()->getTemplate( 'modcp' )->report( $report, $comment, $item, $ref, $prevReport, $prevItem, $nextReport, $nextItem );
		}
	}
	
	/**
	 * Redirect to the original content for a report
	 *
	 * @return	void
	 */
	public function find()
	{		
		try
		{
			$report = \IPS\core\Reports\Report::loadAndCheckPerms( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C139/4', 404, '' );
		}
		
		$comment = call_user_func( array( $report->class, 'load' ), $report->content_id );
		$url = \IPS\Request::i()->parent ? $comment->item()->url() : $comment->url();
		$url = $url->setQueryString( '_report', $report->id );
		
		\IPS\Output::i()->redirect( $url );
	}
}