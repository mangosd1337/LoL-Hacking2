<?php
/**
 * @brief		Topic View
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
 * Topic View
 */
class _topic extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\forums\Topic';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_topic.js', 'forums' ) );
		parent::execute();
	}

	/**
	 * View Topic
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Load topic */
		\IPS\forums\Forum::loadIntoMemory();
		$topic = parent::manage();
		\IPS\Member::loggedIn()->language()->words['submit_comment'] = \IPS\Member::loggedIn()->language()->addToStack( 'submit_reply', FALSE );
		
		/* If there's only one forum we actually don't want the nav */
		if ( \IPS\forums\Forum::theOnlyForum() )
		{
			$topicBreadcrumb = array_pop( \IPS\Output::i()->breadcrumb );
			\IPS\Output::i()->breadcrumb = isset( \IPS\Output::i()->breadcrumb['module'] ) ? array( 'module' => \IPS\Output::i()->breadcrumb['module'] ) : array();
			\IPS\Output::i()->breadcrumb[] = $topicBreadcrumb;
		}

		/* If it failed, it might be because we want a password */
		if ( $topic === NULL )
		{
			$forum = NULL;
			try
			{
				$topic = \IPS\forums\Topic::load( \IPS\Request::i()->id );
				$forum = $topic->container();
				if ( $forum->can('view') and !$forum->loggedInMemberHasPasswordAccess() )
				{
					\IPS\Output::i()->redirect( $forum->url()->setQueryString( 'topic', \IPS\Request::i()->id ) );
				}
				
				if ( !$topic->canView() )
				{
					if ( $topic instanceof \IPS\Content\Hideable and $topic->hidden() )
					{
						/* If the item is hidden we don't want to show the custom no permission error as the conditions may not apply */
						\IPS\Output::i()->error( 'node_error', '2F173/O', 404, '' );
					}
					else
					{
						\IPS\Output::i()->error(  $forum ? $forum->errorMessage() : 'node_error_no_perm', '2F173/H', 403, '' );
					}
				}
			}
			catch ( \OutOfRangeException $e )
			{
				/* Nope, just a generic no access error */
				\IPS\Output::i()->error( 'node_error', '2F173/1', 404, '' );
			}
		}
		
		/* Legacy findpost redirect */
		if ( \IPS\Request::i()->findpost )
		{
			\IPS\Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => \IPS\Request::i()->findpost ) ), NULL, 301 );
		}
		elseif ( \IPS\Request::i()->p )
		{
			\IPS\Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => \IPS\Request::i()->p ) ), NULL, 301 );
		}
		
		if ( \IPS\Request::i()->view )
		{
			$this->_doViewCheck();
		}

		/* If this is an AJAX request fetch the comment form now. The HTML will be cached so calling here and then again in the template has no overhead
			and this is necessary if you entered into a topic with &queued_posts=1, approve the posts, then try to reply. Otherwise, clicking into the
			editor produces an error when the getUploader=1 call occurs, and submitting a reply results in an error. */
		if ( \IPS\Request::i()->isAjax() )
		{
			$topic->commentForm();
		}
	
		/* AJAX hover preview? */
		if ( \IPS\Request::i()->isAjax() and \IPS\Request::i()->preview )
		{
			$postClass = '\IPS\forums\Topic\Post';

			if( $topic->isArchived() )
			{
				$postClass = '\IPS\forums\Topic\ArchivedPost';
			}

			$firstPost = $postClass::load( $topic->topic_firstpost );
			
			$topicOverview = array( 'firstPost' => array( $topic->isQuestion() ? 'question_mainTab' : 'first_post', $firstPost ) );

			if ( $topic->posts > 1 )
			{
				$latestPost = $topic->comments( 1, 0, 'date', 'DESC' );
				$topicOverview['latestPost'] = array( $topic->isQuestion() ? 'latest_answer' : 'latest_post', $latestPost );
			
				$timeLastRead = $topic->timeLastRead();
				if ( $timeLastRead instanceof \IPS\DateTime AND $topic->unread() !== 0 )
				{
					$firstUnread = $topic->comments( 1, NULL, 'date', 'asc', NULL, NULL, $timeLastRead );
					if( $firstUnread instanceof \IPS\forums\Topic\Post AND $firstUnread->date !== $latestPost->date AND $firstUnread->date !== $firstPost->date )
					{
						$topicOverview['firstUnread'] = array( 'first_unread_post_hover', $topic->comments( 1, NULL, 'date', 'asc', NULL, NULL, $timeLastRead ) );
					}
				}			
			}

			if ( $topic->isQuestion() and $topic->topic_answered_pid )
			{
				$topicOverview['bestAnswer'] = array( 'best_answer_post', \IPS\forums\Topic\Post::load( $topic->topic_answered_pid ) );
			}

			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'forums' )->topicHover( $topic, $topicOverview ) );
			return;
		}
		
		$topic->container()->setTheme();
		
		/* Watch for votes */
		if ( $poll = $topic->getPoll() )
		{
			$poll->attach( $topic );
		}
				
		/* How are we sorting posts? */
		$question = NULL;
		$offset = NULL;
		$order = 'date';
		$orderDirection = 'asc';
		$where = NULL;
		if( \IPS\forums\Topic::modPermission( 'unhide', NULL, $topic->container() ) AND \IPS\Request::i()->queued_posts )
		{
			$where = 'queued=1';
			$queuedPagesCount = ceil( \IPS\Db::i()->select( 'COUNT(*)', 'forums_posts', array( 'topic_id=? AND queued=1', $topic->id ) )->first() / $topic->getCommentsPerPage() );
			$pagination = ( $queuedPagesCount > 1 ) ? $topic->commentPagination( array( 'queued_posts', 'sortby' ), 'pagination', $queuedPagesCount ) : NULL;
			
			if ( $topic->isQuestion() )
			{
				$question = $topic->comments( 1, 0 );
			}
		}
		else
		{
			if ( $topic->isQuestion() )
			{
				$question	= $topic->comments( 1, 0 );
				try
				{
					$question->warning = \IPS\core\Warnings\Warning::constructFromData( \IPS\Db::i()->select( '*', 'core_members_warn_logs', array( array( 'wl_content_app=? AND wl_content_module=? AND wl_content_id1=? AND wl_content_id2=?', 'forums', 'forums-comment', $question->topic_id, $question->pid ) ) )->first() );
				}
				catch ( \UnderflowException $e ) { }
						
				$page		= ( isset( \IPS\Request::i()->page ) ) ? intval( \IPS\Request::i()->page ) : 1;

				if( $page < 1 )
				{
					$page	= 1;
				}

				$offset		= ( ( $page - 1 ) * $topic::getCommentsPerPage() ) + 1;
				
				if ( ( !isset( \IPS\Request::i()->sortby ) or \IPS\Request::i()->sortby != 'date' ) )
				{
					if ( $topic->isArchived() )
					{
						$order = "archive_is_first desc, archive_bwoptions";
						$orderDirection = 'desc';
					}
					else
					{
						$order = "new_topic DESC, post_bwoptions DESC, post_field_int DESC, post_date";
						$orderDirection = 'ASC';
					}
				}
			}
			$pagination = ( $topic->commentPageCount() > 1 ) ? $topic->commentPagination( array( 'sortby' ) ) : NULL;
		}	
		$comments = $topic->comments( NUll, $offset, $order, $orderDirection, NULL, NULL, NULL, $where );
		$current  = current( $comments );
		reset( $comments );

		if( !count( $comments ) AND !$topic->isQuestion() )
		{
			\IPS\Output::i()->error( 'no_posts_returned', '2F173/L', 404, '' );
		}
		
		/* On pages 2 and more, it will show the first post on that page, and that is fine as the title has - Page 2 in it meaning its a different page */
		$metaDescription = $topic->isQuestion() ? $question->post : $current->post;
		
		/* Did we request a specific post? If so, reset descriptions. */
		if( isset( $_SESSION['_findComment'] ) )
		{
			$commentId	= $_SESSION['_findComment'];
			unset( $_SESSION['_findComment'] );

			try
			{
				$comment = \IPS\forums\Topic\Post::loadAndCheckPerms( $commentId );

				$metaDescription = $comment->content();
			}
			catch( \Exception $e ){}
		}
		
		$metaDescription = strip_tags( $metaDescription );
		\IPS\Output::i()->metaTags['description'] = \IPS\Output::i()->metaTags['og:description'] = mb_strlen( $metaDescription ) > 160 ? ( mb_substr( $metaDescription, 0, 157 ) . '...' ) : $metaDescription;

		/* Mark read */
		if( !$topic->isLastPage() )
		{
			$maxTime	= 0;

			foreach( $comments as $comment )
			{
				$maxTime	= ( $comment->mapped('date') > $maxTime ) ? $comment->mapped('date') : $maxTime;
			}

			$topic->markRead( NULL, $maxTime );
		}

		$votes		= array();
		$topicVotes = array();

		/* Get post ratings for this user */
		if ( $topic->isQuestion() && \IPS\Member::loggedIn()->member_id )
		{
			$votes		= $topic->answerVotes( \IPS\Member::loggedIn() );
			$topicVotes	= $topic->votes();
		}
		
		if ( $topic->isQuestion() )
		{
			\IPS\Member::loggedIn()->language()->words[ 'topic__comment_placeholder' ] = \IPS\Member::loggedIn()->language()->addToStack( 'question__comment_placeholder', FALSE );
		}
		
		/* Online User Location */
		\IPS\Session::i()->setLocation( $topic->url(), ( $topic->container()->password or !$topic->container()->can_view_others or $topic->container()->min_posts_view ) ? 0 : $topic->onlineListPermissions(), 'loc_forums_viewing_topic', array( $topic->title => FALSE ) );

		/* Next unread */
		try
		{
			$nextUnread	= $topic->nextUnread();
		}
		catch( \Exception $e )
		{
			$nextUnread	= NULL;
		}

		/* Show topic */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'topics' )->topic( $topic, $comments, $question, $votes, $nextUnread, $pagination, $topicVotes );
	}

	/**
	 * Check our view method and act accordingly (redirect if appropriate)
	 *
	 * @return	void
	 */
	protected function _doViewCheck()
	{
		try
		{
			$class	= static::$contentModel;
			$topic	= $class::loadAndCheckPerms( \IPS\Request::i()->id );
			
			switch( \IPS\Request::i()->view )
			{
				case 'getnewpost':
					\IPS\Output::i()->redirect( $topic->url( 'getNewComment' ) );
				break;
				
				case 'getlastpost':
					\IPS\Output::i()->redirect( $topic->url( 'getLastComment' ) );
				break;
			}
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/F', 403, '' );
		}
	}
	
	/**
	 * Edit topic
	 *
	 * @return	void
	 */
	public function edit()
	{
		try
		{
			$class = static::$contentModel;
			$topic = $class::loadAndCheckPerms( \IPS\Request::i()->id );
			$forum = $topic->container();
			$forum->setTheme();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2F173/D', 403, 'no_module_permission_guest' );
		}
		
		if ( $forum->forums_bitoptions['bw_enable_answers'] )
		{
			\IPS\Member::loggedIn()->language()->words['topic_mainTab'] = \IPS\Member::loggedIn()->language()->addToStack( 'question_mainTab', FALSE );
		}
		
		if ( !$topic->canEdit() and !\IPS\Request::i()->form_submitted ) // We check if the form has been submitted to prevent the user loosing their content
		{
			\IPS\Output::i()->error( 'edit_no_perm_err', '2F173/E', 403, '' );
		}
		
		$formElements = $class::formElements( $topic, $forum );

		$hasModOptions = FALSE;
		
		/* We used to just check against the ability to lock, however this may not be enough - a moderator could pin, for example, but not lock */
		foreach( array( 'lock', 'pin', 'hide', 'feature' ) AS $perm )
		{
			if ( $class::modPermission( $perm, NULL, $forum ) )
			{
				$hasModOptions = TRUE;
				break;
			}
		}
		
		$form = new \IPS\Helpers\Form( 'form', \IPS\Member::loggedIn()->language()->checkKeyExists( $class::$formLangPrefix . '_save' ) ? $class::$formLangPrefix . '_save' : 'save' );
		$form->class = 'ipsForm_vertical';
		
		if ( isset( $formElements['poll'] ) )
		{
			$form->addTab( $class::$formLangPrefix . 'mainTab' );
		}
		
		foreach( $formElements AS $k => $element )
		{
			if ( $k === 'poll' )
			{
				$form->addTab( $class::$formLangPrefix . 'pollTab' );
			}
			
			$form->add( $element );
		}
		
		if ( $values = $form->values() )
		{
			if ( $topic->canEdit() )
			{				
				$topic->processForm( $values );
				$topic->save();
				$topic->processAfterEdit( $values );

				/* Moderator log */
				\IPS\Session::i()->modLog( 'modlog__item_edit', array( $topic::$title => FALSE, $topic->url()->__toString() => FALSE, $topic::$title => TRUE, $topic->mapped( 'title' ) => FALSE ), $topic );

				\IPS\Output::i()->redirect( $topic->url() );
			}
			else
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('edit_no_perm_err');
			}
		}

		$formTemplate = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'submit', 'forums' ) ), 'createTopicForm' ), $forum, $hasModOptions, $topic );

		$title = $forum->forums_bitoptions['bw_enable_answers'] ? 'edit_question' : 'edit_topic';

		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'submit' )->createTopic( $formTemplate, $forum, $title );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( $title );
		
		if ( !\IPS\forums\Forum::theOnlyForum() )
		{
			try
			{
				foreach( $forum->parents() AS $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( $forum->url(), $forum->_title );
			}
			catch( \Exception $e ) {}
		}
		
		\IPS\Output::i()->breadcrumb[] = array( $topic->url(), $topic->mapped('title') );
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( $title ) );
	}

	/**
	 * Unarchive
	 *
	 * @return	void
	 */
	public function unarchive()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$topic = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
			if ( !$topic->canUnarchive() )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/B', 404, '' );
		}
		
		$topic->topic_archive_status = \IPS\forums\Topic::ARCHIVE_RESTORE;
		$topic->save();

		/* Log */
		\IPS\Session::i()->modLog( 'modlog__unarchived_topic', array( $topic->url()->__toString() => FALSE, $topic->mapped( 'title' ) => FALSE ), $topic );
		
		\IPS\Output::i()->redirect( $topic->url() );
	}
	
	/**
	 * Rate Question
	 *
	 * @return	void
	 */
	public function rateQuestion()
	{
		/* CSRF Check */
		\IPS\Session::i()->csrfCheck();
		
		/* Get the question */
		try
		{
			$question = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/8', 404, '' );
		}
		
		/* Voting up or down? */
		$rating = intval( \IPS\Request::i()->rating );
		if ( $rating !== 1 and $rating !== -1 )
		{
			\IPS\Output::i()->error( 'form_bad_value', '2F173/A', 403, '' );
		}
		
		/* Check we can cast this vote */
		if ( !$question->canVote( $rating ) )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2F173/9', 403, '' );
		}
		
		/* If we have an existing vote, we're just undoing that, otherwise, insert the vote */
		$ratings = $question->votes();
		if ( isset( $ratings[ \IPS\Member::loggedIn()->member_id ] ) )
		{
			\IPS\Db::i()->delete( 'forums_question_ratings', array( 'topic=? AND member=?', $question->tid, \IPS\Member::loggedIn()->member_id ) );
		}
		else
		{
			\IPS\Db::i()->insert( 'forums_question_ratings', array(
				'topic'		=> $question->tid,
				'forum'		=> $question->forum_id,
				'member'	=> \IPS\Member::loggedIn()->member_id,
				'rating'	=> $rating,
				'date'		=> time()
			), TRUE );
		}
		
		/* Rebuild count */
		$question->question_rating = \IPS\Db::i()->select( 'SUM(rating)', 'forums_question_ratings', array( 'topic=?', $question->tid ) )->first();
		$question->save();
		
		/* Redirect back */
		\IPS\Output::i()->redirect( $question->url() );
	}
	
	/**
	 * Rate Answer
	 *
	 * @return	void
	 */
	public function rateAnswer()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$question = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
			$answer = \IPS\forums\Topic\Post::loadAndCheckPerms( \IPS\Request::i()->answer );
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/4', 404, '' );
		}
		
		if ( !$answer->item()->can('read') or !$answer->canVote() )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2F173/5', 403, '' );
		}
		
		$rating = intval( \IPS\Request::i()->rating );
		if ( $rating !== 1 and $rating !== -1 )
		{
			\IPS\Output::i()->error( 'form_bad_value', '2F173/6', 403, '' );
		}

		/* Have we already rated ? */
		try
		{
			$rating = \IPS\Db::i()->select( '*', 'forums_answer_ratings', array( 'topic=? AND post=? AND member=?', $question->tid, $answer->pid, \IPS\Member::loggedIn()->member_id ) )->first();
			\IPS\Db::i()->delete( 'forums_answer_ratings', array( 'topic=? AND post=? AND member=?', $question->tid, $answer->pid, \IPS\Member::loggedIn()->member_id ) );
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Db::i()->insert( 'forums_answer_ratings', array(
				'post'		=> $answer->pid,
				'topic'		=> $question->tid,
				'member'	=> \IPS\Member::loggedIn()->member_id,
				'rating'	=> $rating,
				'date'		=> time()
			), TRUE );
		}

		$answer->post_field_int = (int) \IPS\Db::i()->select( 'SUM(rating)', 'forums_answer_ratings', array( 'post=?', $answer->pid ) )->first();
		$answer->save();

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( array( 'votes' => $answer->post_field_int, 'canVoteUp' => $answer->canVote(1), 'canVoteDown' => $answer->canVote(-1) ) );
		}
		else
		{
			\IPS\Output::i()->redirect( $answer->url() );
		}
	}
	
	/**
	 * Set Best Answer
	 *
	 * @return	void
	 */
	public function bestAnswer()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$topic = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
			if ( !$topic->canSetBestAnswer() )
			{
				throw new \OutOfRangeException;
			}
			
			$post = \IPS\forums\Topic\Post::loadAndCheckPerms( \IPS\Request::i()->answer );
			if ( $post->item() != $topic )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/7', 404, '' );
		}
		
		if ( $topic->topic_answered_pid )
		{
			try
			{
				$oldBestAnswer = \IPS\forums\Topic\Post::load( $topic->topic_answered_pid );
				$oldBestAnswer->post_bwoptions['best_answer'] = FALSE;
				$oldBestAnswer->save();
			}
			catch ( \Exception $e ) {}
		}
		
		$post->post_bwoptions['best_answer'] = TRUE;
		$post->save();
		
		$topic->topic_answered_pid = $post->pid;
		$topic->save();
		
		/* Log */
		if ( \IPS\Member::loggedIn()->modPermission('can_set_best_answer') )
		{
			\IPS\Session::i()->modLog( 'modlog__best_answer_set', array( $post->pid => FALSE ), $topic );
		}
		
		\IPS\Output::i()->redirect( $topic->url() );
	}
	
	/**
	 * Unset Best Answer
	 *
	 * @return	void
	 */
	public function unsetBestAnswer()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$topic = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
			if ( !$topic->canSetBestAnswer() )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/G', 404, '' );
		}
	
		if ( $topic->topic_answered_pid )
		{
			try
			{
				$post = \IPS\forums\Topic\Post::load( $topic->topic_answered_pid );
				$post->post_bwoptions['best_answer'] = FALSE;
				$post->save();
				
				if ( \IPS\Member::loggedIn()->modPermission('can_set_best_answer') )
				{
					\IPS\Session::i()->modLog( 'modlog__best_answer_unset', array( $topic->topic_answered_pid => FALSE ), $topic );
				}
			}
			catch ( \Exception $e ) {}
		}
	
		$topic->topic_answered_pid = FALSE;
		$topic->save();
	
		\IPS\Output::i()->redirect( $topic->url() );
	}
	
	/**
	 * Saved Action
	 *
	 * @return	void
	 */
	public function savedAction()
	{
		try
		{
			\IPS\Session::i()->csrfCheck();
			
			$topic = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
			$action = \IPS\forums\SavedAction::load( \IPS\Request::i()->action );
			$action->runOn( $topic );
			
			/* Log */
			\IPS\Session::i()->modLog( 'modlog__saved_action', array( 'forums_mmod_' . $action->mm_id => TRUE, $topic->url()->__toString() => FALSE, $topic->mapped( 'title' ) => FALSE ), $topic );
			\IPS\Output::i()->redirect( $topic->url() );
		}
		catch ( \LogicException $e )
		{
			
		}
	}

	/**
	 * Mark Topic Read
	 *
	 * @return	void
	 */
	public function markRead()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$topic = \IPS\forums\Topic::load( \IPS\Request::i()->id );
			$topic->markRead();

			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( "OK" );
			}
			else
			{
				\IPS\Output::i()->redirect( $topic->url() );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2F173/C', 403, 'no_module_permission_guest' );
		}
	}
	
	/**
	 * We need to use the custom widget poll template for ajax methods
	 *
	 * @return void
	 */
	public function widgetPoll()
	{
		try
		{
			$topic = \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/N', 403, '' );
		}
		
		$poll  = $topic->getPoll();
		$poll->displayTemplate = array( \IPS\Theme::i()->getTemplate( 'widgets', 'forums', 'front' ), 'pollWidget' );
		$poll->url = $topic->url();
		
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'widgets', 'forums', 'front' )->poll( $topic, $poll );
	}

	/**
	 * Edit Comment/Review
	 *
	 * @param	string					$commentClass	The comment/review class
	 * @param	\IPS\Content\Comment	$comment		The comment/review
	 * @param	\IPS\Content\Item		$item			The item
	 * @return	void
	 * @throws	\LogicException
	 */
	protected function _edit( $commentClass, $comment, $item )
	{
		\IPS\Member::loggedIn()->language()->words['edit_comment']		= \IPS\Member::loggedIn()->language()->addToStack( 'edit_reply', FALSE );

		return parent::_edit( $commentClass, $comment, $item );
	}
	
	/**
	 * Stuff that applies to both comments and reviews
	 *
	 * @param	string	$method	Desired method
	 * @param	array	$args	Arguments
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$class = static::$contentModel;
		
		try
		{
			$item = $class::load( \IPS\Request::i()->id );
			if ( !$item->canView() )
			{
				$forum = $item->container();
				\IPS\Output::i()->error( $forum ? $forum->errorMessage() : 'node_error_no_perm', '2F173/K', 403, '' );
			}
			
			if ( $item->isArchived() )
			{
				$class::$commentClass = $class::$archiveClass;
			}
			
			return parent::__call( $method, $args );
		}
		catch( \OutOfRangeException $e )
		{
			if ( isset( \IPS\Request::i()->do ) AND \IPS\Request::i()->do === 'findComment' AND isset( \IPS\Request::i()->comment ) )
			{
				try
				{
					$comment = \IPS\forums\Topic\Post::load( \IPS\Request::i()->comment );
					$topic   = \IPS\forums\Topic::load( $comment->topic_id );
					
					\IPS\Output::i()->redirect( $topic->url()->setQueryString( array( 'do' => 'findComment', 'comment' => \IPS\Request::i()->comment ) ), NULL, 301 );
				}
				catch( \Exception $e )
				{
					\IPS\Output::i()->error( 'node_error', '2F173/M', 404, '' );
				}
			}
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F173/I', 404, '' );
		}
	}
}