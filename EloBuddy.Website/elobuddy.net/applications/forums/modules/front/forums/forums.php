<?php
/**
 * @brief		Forum Index
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		08 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forum Index
 */
class _forums extends \IPS\Dispatcher\Controller
{
	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		\IPS\forums\Forum::loadIntoMemory();
		
		$forum = NULL;
		try
		{
			$this->_forum( \IPS\forums\Forum::loadAndCheckPerms( \IPS\Request::i()->id ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F176/1', 404, '' );
		}
	}
	
	/**
	 * Show Forum
	 *
	 * @param	\IPS\forums\Forum	$forum	The forum to show
	 * @return	void
	 */
	public function _forum( $forum )
	{
		/* Password protected */
		if ( $form = $forum->passwordForm() )
		{
			\IPS\Output::i()->title = $forum->_stripTagsTitle;
			\IPS\Output::i()->output = $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'forums', 'forums', 'front' ), 'forumPasswordPopup' ) );
			return;
		}
		
		/* We can read? */
		if ( $forum->sub_can_post and !$forum->permission_showtopic and !$forum->can('read') )
		{
			\IPS\Output::i()->error( $forum->errorMessage(), '1F176/3', 403, '' );
		}
		
		/* Theme */
		$forum->setTheme();

        /* Users can see topics posted by other users? */
        $where = array();
        if ( !$forum->can_view_others and !\IPS\Member::loggedIn()->modPermission( 'can_read_all_topics' ) )
        {
            $where[] = array( 'starter_id = ?', \IPS\Member::loggedIn()->member_id );
        }

		/* Init table (it won't show anything until after the password check, but it sets navigation and titles) */
		$table = new \IPS\Helpers\Table\Content( 'IPS\forums\Topic', $forum->url(), $where, $forum, NULL, 'view', isset( \IPS\Request::i()->rss ) ? FALSE : TRUE, isset( \IPS\Request::i()->rss ) ? FALSE : TRUE );
		$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'forums', 'forums', 'front' ), 'forumTable' );
		$table->classes = array( 'cTopicList' );
		$table->title = \IPS\Member::loggedIn()->language()->addToStack( ( $forum->forums_bitoptions['bw_enable_answers'] ) ? 'count_questions_in_forum' : 'count_topics_in_forum', FALSE, array( 'pluralize' => array( $forum->topics ) ) );
		if ( $forum->forums_bitoptions['bw_enable_answers'] )
		{
			$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'forums', 'forums', 'front' ), 'questionRow' );
		}
		else
		{
			$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'forums', 'forums', 'front' ), 'topicRow' );
		}
		$table->hover = TRUE;
		$table->sortOptions['num_replies']	= $table->sortOptions['num_comments'];
		unset( $table->sortOptions['num_comments'] );
		
		/* If there's only one forum we actually don't want the nav */
		if ( \IPS\forums\Forum::theOnlyForum() )
		{
			\IPS\Output::i()->breadcrumb = isset( \IPS\Output::i()->breadcrumb['module'] ) ? array( 'module' => \IPS\Output::i()->breadcrumb['module'] ) : array();
		}

		/* Redirect? */
		if ( $forum->redirect_url )
		{
			$forum->redirect_hits++;
			$forum->save();
			\IPS\Output::i()->redirect( $forum->redirect_url );
		}
		
		/* Custom Search */
		$filterOptions = array(
			'all'			=> 'all_topics',
			'open'			=> 'open_topics',
			'popular'		=> 'popular_now',
			'poll'			=> 'poll',
			'locked'		=> 'locked_topics',
			'moved'			=> 'moved_topics',
		);
		$timeFrameOptions = array(
			'show_all'			=> 'show_all',
			'today'				=> 'today',
			'last_5_days'		=> 'last_5_days',
			'last_7_days'		=> 'last_7_days',
			'last_10_days'		=> 'last_10_days',
			'last_15_days'		=> 'last_15_days',
			'last_20_days'		=> 'last_20_days',
			'last_25_days'		=> 'last_25_days',
			'last_30_days'		=> 'last_30_days',
			'last_60_days'		=> 'last_60_days',
			'last_90_days'		=> 'last_90_days',
		);
		
		if ( \IPS\Member::loggedIn()->member_id )
		{
			$filterOptions['starter'] = $forum->forums_bitoptions['bw_enable_answers'] ? 'questions_i_asked' : 'topics_i_started';
			$filterOptions['replied'] = $forum->forums_bitoptions['bw_enable_answers'] ? 'questions_i_posted_in' : 'topics_i_posted_in';

			if ( \IPS\Member::loggedIn()->member_id AND \IPS\Member::loggedIn()->last_visit)
			{
				$timeFrameOptions['since_last_visit'] = \IPS\Member::loggedIn()->language()->addToStack('since_last_visit', FALSE, array( 'sprintf' => array( \IPS\DateTime::ts( \IPS\Member::loggedIn()->last_visit ) ) ) );
			}

		}
		
		if ( $forum->forums_bitoptions['bw_enable_answers'] )
		{
			$table->filters = array(
				'questions_with_best_answers'		=> 'topic_answered_pid>0',
				'questions_without_best_answers'	=> 'topic_answered_pid=0',
			);
			
			$table->sortOptions['question_rating'] = 'forums_topics.question_rating';
		}

		/* Are we a moderator? */
		if( \IPS\forums\Topic::modPermission( 'unhide', NULL, $forum ) )
		{
			$filterOptions['queued_topics']	= 'queued_topics';
			$filterOptions['queued_posts']	= 'queued_posts';
		}
		
		$table->advancedSearch = array(
			'topic_type'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $filterOptions ) ),
			'sort_by'		=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => array(
				'last_post'		=> 'last_post',
				'replies'		=> 'replies',
				'views'			=> 'views',
				'topic_title'	=> 'topic_title',
				'last_poster'	=> 'last_poster',
				'topic_started'	=> 'topic_started',
				'topic_starter'	=> $forum->forums_bitoptions['bw_enable_answers'] ? 'question_asker' : 'topic_starter',
				) )
			),
			'sort_direction'=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => array(
				'asc'			=> 'asc',
				'desc'			=> 'desc',
				) )
			),
			'time_frame'	=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => $timeFrameOptions ) ),
		);
		$table->advancedSearchCallback = function( $table, $values )
		{
			/* Type */
			switch ( $values['topic_type'] )
			{
				case 'open':
					$table->where[] = array( 'state=?', 'open' );
					break;
				case 'popular':
					$table->where[] = array( 'popular_time IS NOT NULL AND popular_time>?', time() );
					break;
				case 'poll':
					$table->where[] = array( 'poll_state<>0' );
					break;
				case 'locked':
					$table->where[] = array( 'state=?', 'closed' );
					break;
				case 'moved':
					$table->where[] = array( 'state=?', 'link' );
					break;
				case 'starter':
					$table->where[] = array( 'starter_id=?', \IPS\Member::loggedIn()->member_id );
					break;
				case 'replied':
					$table->joinComments = TRUE;
					$table->where[] = array( 'forums_posts.author_id=?', \IPS\Member::loggedIn()->member_id );
					break;
				case 'answered':
					$table->where[] = array( 'topic_answered_pid<>0' );
					break;
				case 'unanswered':
					$table->where[] = array( 'topic_answered_pid=0' );
					break;
				case 'queued_topics':
					$table->where[] = array( 'approved=0' );
					break;
				case 'queued_posts':
					$table->where[] = array( 'topic_queuedposts>0' );
					break;
			}
			
			if ( ! isset( $values['sort_by'] ) )
			{
				$values['sort_by'] = 'last_post';
			}
			
			/* Sort */
			switch ( $values['sort_by'] )
			{
				case 'last_post':
				case 'views':
					$table->sortBy = $values['sort_by'];
					break;
				case 'replies':
					$table->sortBy = 'posts';
					break;
				case 'topic_title':
				case 'title':
					$table->sortBy = 'title';
					break;
				case 'last_poster':
					$table->sortBy = 'last_poster_name';
					break;
				case 'topic_started':
					$table->sortBy = 'start_date';
					break;
				case 'topic_starter':
					$table->sortBy = 'starter_name';
					break;
			}
			$table->sortDirection = $values['sort_direction'];
			
			/* Cutoff */
			$days = NULL;
			
			if ( isset( $values['time_frame'] ) )
			{
				switch ( $values['time_frame'] )
				{
					case 'today':
						$days = 1;
						break;
					case 'last_5_days':
						$days = 5;
						break;
					case 'last_7_days':
						$days = 7;
						break;
					case 'last_10_days':
						$days = 10;
						break;
					case 'last_15_days':
						$days = 15;
						break;
					case 'last_20_days':
						$days = 20;
						break;
					case 'last_25_days':
						$days = 25;
						break;
					case 'last_30_days':
						$days = 30;
						break;
					case 'last_60_days':
						$days = 60;
						break;
					case 'last_90_days':
						$days = 90;
						break;
					case 'since_last_visit':
						$table->where[] = array( 'forums_topics.last_post>?', \IPS\Member::loggedIn()->last_visit );
						break;
				}
			}

			if ( $days !== NULL )
			{
				$table->where[] = array( 'forums_topics.last_post>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $days . 'D' ) )->getTimestamp() );
			}
		};
		\IPS\Request::i()->sort_direction	= \IPS\Request::i()->sort_direction ?: mb_strtolower( $table->sortDirection );
		
		/* Saved actions */
		foreach ( \IPS\forums\SavedAction::actions( $forum ) as $action )
		{
			$table->savedActions[ $action->_id ] = $action->_title;
		}
		
		/* RSS */
		if ( \IPS\Settings::i()->forums_rss and $forum->topics )
		{
			/* Show the link */
			$rssUrl = \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums&id={$forum->_id}&rss=1", 'front', 'forums_rss', array( $forum->name_seo ) );
			if ( $forum->forums_bitoptions['bw_enable_answers'] )
			{
				$rssTitle = \IPS\Member::loggedIn()->language()->addToStack( 'forum_rss_title_questions', FALSE, array( 'sprintf' => array( $forum->_stripTagsTitle ) ) );
			}
			else
			{
				$rssTitle = \IPS\Member::loggedIn()->language()->addToStack( 'forum_rss_title_topics', FALSE, array( 'sprintf' => array( $forum->_stripTagsTitle ) ) );
			}
			\IPS\Output::i()->rssFeeds[ $rssTitle ] = $rssUrl;
			
			/* Or actually show RSS feed */
			if ( isset( \IPS\Request::i()->rss ) )
			{
				/* Set the title, either topics or questions */
				if ( $forum->forums_bitoptions['bw_enable_answers'] )
				{
					$rssTitle = sprintf( \IPS\Member::loggedIn()->language()->get( 'forum_rss_title_questions'), \IPS\Member::loggedIn()->language()->get( "forums_forum_{$forum->id}" ) );
				}
				else
				{
					$rssTitle = sprintf( \IPS\Member::loggedIn()->language()->get( 'forum_rss_title_topics'), \IPS\Member::loggedIn()->language()->get( "forums_forum_{$forum->id}" ) );
				}
				
				/* Don't include "moved" links in the RSS feed, as there is no content to include ( $topic->content() results in BadMethodCallException) */
				$table->where[]	= array( 'forums_topics.moved_to IS NULL' );
				
				/* Can we view the content (permission_showtopic may allow us to view the list, but not the content)? */
				$canViewContent = $forum->can('read');

				/* Build the document */
				$document = \IPS\Xml\Rss::newDocument( $forum->url(), $rssTitle, $rssTitle );
				foreach ( $table->getRows( array() ) as $topic )
				{
					if ( !$topic->hidden() )
					{
						$document->addItem( $topic->title, $topic->url(), $canViewContent ? $topic->content() : NULL, \IPS\DateTime::ts( $topic->start_date ), $topic->tid );
					}
				}
				
				/* Display - note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
				\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			}
		}

		/* Online User Location */
		$permissions = $forum->permissions();
		\IPS\Session::i()->setLocation( $forum->url(), explode( ",", $permissions['perm_view'] ), 'loc_forums_viewing_forum', array( "forums_forum_{$forum->id}" => TRUE ) );
				
		/* Show Forum */
		if ( isset( \IPS\Request::i()->advancedSearchForm ) )
		{
			\IPS\Output::i()->output = (string) $table;
			return;
		}
		\IPS\Output::i()->contextualSearchOptions[ \IPS\Member::loggedIn()->language()->addToStack( 'search_contextual_item', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'forums_sg' ) ) ) ) ] = array( 'type' => 'forums_topic', 'nodes' => $forum->_id );

		$forumOutput = '';

		if ( $forum->forums_bitoptions['bw_enable_answers'] )
		{	
			$featuredTopic = NULL;

			foreach ( \IPS\forums\Topic::featured( 1, 'RAND()', $forum ) as $featuredTopic )
			{
				break;
			}
			
			$popularQuestions = \IPS\forums\Topic::getItemsWithPermission( array( array( 'forum_id=?', $forum->id ), array( 'start_date>?', \IPS\DateTime::ts( time() - ( 86400 * 30 ) )->getTimestamp() ), array( 'question_rating>0' ) ), 'question_rating DESC', 5 );
			$newQuestionsWhere = array( array( 'forum_id=?', $forum->id ) );

			if ( !\IPS\Settings::i()->forums_new_questions )
			{
				$newQuestionsWhere[] = array( 'topic_answered_pid=0' );
			}
			else
			{
				$newQuestionsWhere[] = array( '( forums_topics.posts IS NULL OR forums_topics.posts=1 )' );
			}
			
			$newQuestions = \IPS\forums\Topic::getItemsWithPermission( $newQuestionsWhere, 'start_date DESC', 5 );			
			$forumOutput = \IPS\Theme::i()->getTemplate( 'forums' )->qaForum( (string) $table, $popularQuestions, $newQuestions, $featuredTopic, $forum );
		}
		else if( $forum->sub_can_post )
		{
			$forumOutput = (string) $table;
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'forums' )->forumDisplay( $forum, $forumOutput );
	}

	/**
	 * Add Topic
	 *
	 * @return	void
	 */
	protected function add()
	{
		if ( !isset( \IPS\Request::i()->id ) )
		{
			$this->_selectForum();
			return;
		}

		try
		{
			$forum = \IPS\forums\Forum::loadAndCheckPerms( \IPS\Request::i()->id );
			$forum->setTheme();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2F173/2', 403, 'no_module_permission_guest' );
		}
		
		if ( $forum->forums_bitoptions['bw_enable_answers'] )
		{
			\IPS\Member::loggedIn()->language()->words['topic_mainTab'] = \IPS\Member::loggedIn()->language()->addToStack( 'question_mainTab', FALSE );
		}
		
		$form = \IPS\forums\Topic::create( $forum );

		$hasModOptions = false;

		if ( \IPS\forums\Topic::modPermission( 'lock', NULL, $forum ) or
			 \IPS\forums\Topic::modPermission( 'pin', NULL, $forum ) or
			 \IPS\forums\Topic::modPermission( 'hide', NULL, $forum ) or
			 \IPS\forums\Topic::modPermission( 'feature', NULL, $forum ) )
		{
			$hasModOptions = TRUE;
		}
		
		$formTemplate = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'submit', 'forums' ) ), 'createTopicForm' ), $forum, $hasModOptions, NULL );
		
		if ( \IPS\forums\Topic::moderateNewItems( \IPS\Member::loggedIn() ) )
		{
			$formTemplate = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->modQueueMessage( \IPS\Member::loggedIn()->warnings( 5, NULL, 'mq' ), \IPS\Member::loggedIn()->mod_posts ) . $formTemplate;
		}

		$title = $forum->forums_bitoptions['bw_enable_answers'] ? 'ask_new_question' : 'create_new_topic';

		/* Online User Location */
		$permissions = $forum->permissions();
		\IPS\Session::i()->setLocation( $forum->url(), explode( ",", $permissions['perm_view'] ), 'loc_forums_creating_topic', array( "forums_forum_{$forum->id}" => TRUE ) );
		
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'submit' )->createTopic( $formTemplate, $forum, $title );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( $title );
		
		if ( !\IPS\forums\Forum::theOnlyForum() )
		{
			try
			{
				foreach( $forum->parents() as $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
			}
			catch( \UnderflowException $e ) {}
			\IPS\Output::i()->breadcrumb[] = array( $forum->url(), $forum->_title );
		}
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( ( $forum->forums_bitoptions['bw_enable_answers'] ) ? 'ask_new_question' : 'create_new_topic' ) );
	}
	
	/**
	 * Create Category Selector
	 *
	 * @return	void
	 */
	protected function createMenu()
	{
		$this->_selectForum();
	}
	
	/**
	 * Mark Read
	 *
	 * @return	void
	 */
	protected function markRead()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$forum		= \IPS\forums\Forum::load( \IPS\Request::i()->id );
			$returnTo	= $forum;

			if( \IPS\Request::i()->return )
			{
				$returnTo	= \IPS\forums\Forum::load( \IPS\Request::i()->return );
			}
			
			if ( \IPS\Request::i()->fromForum )
			{
				\IPS\forums\Topic::markContainerRead( $forum, NULL, FALSE );
			}
			else
			{
				\IPS\forums\Topic::markContainerRead( $forum );
			}

			\IPS\Output::i()->redirect( ( \IPS\Request::i()->return OR \IPS\Request::i()->fromForum ) ? $returnTo->url() : \IPS\Http\Url::internal( 'app=forums&module=forums&controller=index', NULL, 'forums' ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2F173/3', 403, 'no_module_permission_guest' );
		}
	}

	/**
	 * Shows the forum selector for creating a topic outside of specific forum
	 *
	 * @return	void
	 */
	protected function _selectForum()
	{
		$form = new \IPS\Helpers\Form( 'select_forum', 'continue' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Node( 'forum', NULL, TRUE, array(
			'url'					=> \IPS\Http\Url::internal( 'app=forums&module=forums&controller=forums&do=createMenu' ),
			'class'					=> 'IPS\forums\Forum',
			'permissionCheck'		=> function( $node )
			{
				if ( $node->can( 'view' ) )
				{
					if ( $node->can( 'add' ) )
					{
						return TRUE;
					}
					
					return FALSE;
				}
				
				return NULL;
			}
		) ) );

		if ( $values = $form->values() )
		{
			\IPS\Output::i()->redirect( $values['forum']->url()->setQueryString( 'do', 'add' ) );
		}
		
		\IPS\Output::i()->title			= \IPS\Member::loggedIn()->language()->addToStack( 'select_forum' );
		\IPS\Output::i()->breadcrumb[]	= array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'select_forum' ) );
		\IPS\Output::i()->output		= \IPS\Theme::i()->getTemplate( 'forums' )->forumSelector( $form );
	}
}