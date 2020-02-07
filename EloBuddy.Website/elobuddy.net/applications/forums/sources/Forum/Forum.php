<?php
/**
 * @brief		Forum Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		7 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forum Node
 */
class _Forum extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'forums_forums';
			
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = 'parent_id';
	
	/**
	 * @brief	[Node] Parent ID Root Value
	 * @note	This normally doesn't need changing though some legacy areas use -1 to indicate a root node
	 */
	public static $databaseColumnParentRootValue = -1;
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'forums';
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'forums',
		'module'	=> 'forums',
		'prefix' 	=> 'forums_',
		'map'		=> array( 'permissions' => 'forums_perms' ),
	);
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'forums';
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'forum';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'reply'				=> 4,
		'attachments'		=> 5
	);
	
	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_forum_';
	
	/**
	 * @brief	Bitwise values for forums_bitoptions field
	 */
	public static $bitOptions = array(
		'forums_bitoptions' => array(
			'forums_bitoptions' => array(
				'bw_disable_tagging'		=> 1,
				'bw_disable_prefixes'		=> 2,
				'bw_enable_answers'			=> 4,
				'bw_enable_answers_member'	=> 8,
			)
		)
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'forums_forum_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = '_desc';
	
	/**
	 * @brief	[Node] Moderator Permission
	 */
	public static $modPerm = 'forums';
	
	/**
	 * @brief	Content Item Class
	 */
	public static $contentItemClass = 'IPS\forums\Topic';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'comments';
	
	/**
	 * Form fields prefix with "forum_" but the database columns do not have this prefix - let's strip for the massChange feature
	 *
	 * @param	string	$k	Key
	 * @param	mixed	$v	Value
	 * @return	void
	 */
	public function __set( $k, $v )
	{
		if( mb_strpos( $k, "forum_" ) === 0 AND $k !== 'forum_allow_rating' )
		{
			$k = preg_replace( "/^forum_(.+?)$/", "$1", $k );
			$this->$k	= $v;
			return;
		}

		parent::__set( $k, $v );
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_name_seo()
	{
		if( !$this->_data['name_seo'] )
		{
			$this->name_seo	= \IPS\Http\Url::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'forums_forum_' . $this->id ) );
			$this->save();
		}

		return $this->_data['name_seo'] ?: \IPS\Http\Url::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'forums_forum_' . $this->id ) );
	}

	/**
	 * Get number of items
	 *
	 * @return	int
	 */
	protected function get__items()
	{
		return (int) $this->topics;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Items
	 * @return	int
	 */
	protected function set__items( $val )
	{
		$this->topics = (int) $val;
	}
	
	/**
	 * Get number of comments
	 *
	 * @return	int
	 */
	protected function get__comments()
	{
		return (int) $this->posts;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Comments
	 * @return	int
	 */
	protected function set__comments( $val )
	{
		$this->posts = (int) $val;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return	int
	 */
	protected function get__unapprovedItems()
	{
		return $this->queued_topics;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return	int
	 */
	protected function get__unapprovedComments()
	{
		return $this->queued_posts;
	}
	
	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @param	int	$val	Unapproved Items
	 * @return	void
	 */
	protected function set__unapprovedItems( $val )
	{
		$this->queued_topics = $val;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @param	int	$val	Unapproved Comments
	 * @return	void
	 */
	protected function set__unapprovedComments( $val )
	{
		$this->queued_posts = $val;
	}
	
	/**
	 * Get default sort key
	 *
	 * @return	string
	 */
	public function get__sortBy()
	{
		return $this->sort_key;
	}
	
	/**
	 * Last Poster ID Column
	 */
	protected static $lastPosterIdColumn = 'last_poster_id';
	
	/**
	 * Set last comment
	 *
	 * @param	\IPS\Content\Comment	$comment	The latest comment or NULL to work it out
	 * @return	void
	 */
	public function setLastComment( \IPS\Content\Comment $comment=NULL )
	{
		if ( $comment === NULL )
		{
			try
			{
				/* We prefer fetching post, joining topic, etc. but that is not efficient and causes a temp table and filesort against posts table so we'll lean on the cached last_post value for the topic */
				$select = \IPS\Db::i()->select( '*', 'forums_topics', array( "forums_topics.forum_id=? AND forums_topics.approved=1 AND forums_topics.state != ?", $this->id, 'link' ), 'forums_topics.last_post DESC', 1 )->first();
				$topic = \IPS\forums\Topic::constructFromData( $select );

				$this->last_post = $topic->last_post;
				$this->last_poster_id = (int) $topic->last_poster_id;
				$this->last_poster_name = $topic->last_poster_name;
				$this->seo_last_name = \IPS\Http\Url::seoTitle( $this->last_poster_name );
				$this->last_title = $topic->title;
				$this->seo_last_title = \IPS\Http\Url::seoTitle( $this->last_title );
				$this->last_id = $topic->tid;
				return;
			}
			catch ( \UnderflowException $e )
			{
				$this->last_post = NULL;
				$this->last_poster_id = 0;
				$this->last_poster_name = '';
				$this->last_title = NULL;
				$this->last_id = NULL;
				return;
			}
		}
				
		$this->last_post = $comment->mapped('date');
		$this->last_poster_id = (int) $comment->author()->member_id;
		$this->last_poster_name = $comment->author()->member_id ? $comment->author()->name : $comment->mapped('author_name');
		$this->seo_last_name = \IPS\Http\Url::seoTitle( $this->last_poster_name );
		$this->last_title = $comment->item()->title;
		$this->seo_last_title = \IPS\Http\Url::seoTitle( $this->last_title );
		$this->last_id = $comment->item()->tid;
	}
	
	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @return	\IPS\DateTime|NULL
	 */
	public function getLastCommentTime()
	{
        if( !$this->can_view_others and !\IPS\Member::loggedIn()->modPermission( 'can_read_all_topics' ) )
        {
            try
            {
                $select = \IPS\Db::i()->select('*', 'forums_posts', array("forums_posts.queued=0 AND forums_topics.forum_id={$this->id} AND forums_topics.approved=1 AND forums_topics.starter_id=?", \IPS\Member::loggedIn()->member_id), 'forums_posts.post_date DESC', 1)->join('forums_topics', 'forums_topics.tid=forums_posts.topic_id')->first();
            }
            catch ( \UnderflowException $e )
            {
                return NULL;
            }

            return $select['last_post'] ?  \IPS\DateTime::ts( $select['last_post'] ) : NULL;
        }

		return $this->last_post ? \IPS\DateTime::ts( $this->last_post ) : NULL;
	}
	
	/**
	 * Set the comment/approved/hidden counts
	 *
	 * @return void
	 */
	public function resetCommentCounts()
	{
		parent::resetCommentCounts();
		
		/* Archiving? */
		if ( \IPS\Settings::i()->archive_on )
		{
			$this->_comments += \IPS\forums\Topic\ArchivedPost::db()->select( 'COUNT(*)', 'forums_archive_posts', array( 'archive_forum_id=?', $this->id ) )->first();
		}
	}
		
	/**
	 * Get last post data
	 *
	 * @return	array|NULL
	 */
	public function lastPost()
	{
		if ( !$this->loggedInMemberHasPasswordAccess() )
		{
			return NULL;
		}
		elseif
		(
			!$this->can_view_others
			and
			(
				!(
					\IPS\Member::loggedIn()->modPermission( 'forums' ) === -1
					or
					(
						is_array( \IPS\Member::loggedIn()->modPermission( 'forums' ) )
						and
						in_array( $this->_id, \IPS\Member::loggedIn()->modPermission( 'forums' ) )
					)
				)
				and
				!\IPS\Member::loggedIn()->modPermission( 'can_read_all_topics' )
			)
		)
		{
			try
			{
				$lastPost = \IPS\forums\Topic\Post::constructFromData( \IPS\Db::i()->select( '*', 'forums_posts', array( 'topic_id=? AND queued=0', \IPS\Db::i()->select( 'tid', 'forums_topics', array( 'forum_id=? AND approved=1 AND starter_id=?', $this->_id, \IPS\Member::loggedIn()->member_id ), 'last_post DESC', 1 )->first() ), 'post_date DESC', 1 )->first() );
				$result = array(
					'author'		=> $lastPost->author(),
					'topic_url'		=> $lastPost->item()->url(),
					'topic_title'	=> $lastPost->item()->title,
					'date'			=> $lastPost->post_date
				);
			}
			catch ( \UnderflowException $e )
			{
				$result = NULL;
			}

			foreach( $this->children() as $child )
			{
				$childLastPost = $child->lastPost();

				if( $result === NULL OR ( $childLastPost !== NULL AND $childLastPost['date'] > $result['date'] ) )
				{
					$result = $childLastPost;
				}
			}

			return $result;
		}
		elseif ( !$this->permission_showtopic and !$this->can('view') )
		{
			$return = NULL;

			if( !$this->sub_can_post )
			{
				foreach( $this->children() as $child )
				{
					$childLastPost = $child->lastPost();

					if( $return === NULL OR ( $childLastPost !== NULL AND $childLastPost['date'] > $return['date'] ) )
					{
						$return = $childLastPost;
					}
				}
			}

			return $return;
		}
		else
		{
			$result	= NULL;

			if( $this->last_post )
			{
				if ( $this->last_poster_id )
				{
					$lastAuthor = \IPS\Member::load( $this->last_poster_id );
				}
				else
				{
					$lastAuthor = new \IPS\Member;
					if ( $this->last_poster_name )
					{
						$lastAuthor->name = $this->last_poster_name;
					}
				}
				
				$result = array(
					'author'		=> $lastAuthor,
					'topic_url'		=> \IPS\Http\Url::internal( "app=forums&module=forums&controller=topic&id={$this->last_id}", 'front', 'forums_topic', array( $this->seo_last_title ) ),
					'topic_title'	=> $this->last_title,
					'date'			=> $this->last_post
				);
			}

			foreach( $this->children() as $child )
			{
				$childLastPost = $child->lastPost();

				if( $result === NULL OR ( $childLastPost !== NULL AND $childLastPost['date'] > $result['date'] ) )
				{
					$result = $childLastPost;
				}
			}
			
			if ( $this->sub_can_post and !$this->permission_showtopic and !$this->can('read') AND !is_null( $result ) )
			{
				$result['topic_title'] = NULL;
			}
			
			return $result;
		}
	}
	
	/**
	 * Permission Types
	 *
	 * @return	array
	 */
	public function permissionTypes()
	{
		if ( !$this->sub_can_post )
		{
			return array( 'view' => 'view' );
		}
		return static::$permissionMap;
	}
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns()
	{
		$return = parent::basicDataColumns();
		$return[] = 'forums_bitoptions';
		$return[] = 'password';
		$return[] = 'password_override';
		$return[] = 'min_posts_view';
		return $return;
	}
	
	/**
	 * Check if the currently logged in member has access to a password protected forum
	 *
	 * @return	bool
	 */
	public function loggedInMemberHasPasswordAccess()
	{
		if ( $this->password === NULL )
		{
			return TRUE;
		}
		
		if ( \IPS\Member::loggedIn()->inGroup( explode( ',', $this->password_override ) ) )
		{
			return TRUE;
		}
		
		if ( isset( \IPS\Request::i()->cookie[ 'ipbforumpass_' . $this->id ] ) and \IPS\Login::compareHashes( md5( $this->password ), \IPS\Request::i()->cookie[ 'ipbforumpass_' . $this->id ] ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Password Form
	 *
	 * @return	\IPS\Helpers\Form|NULL
	 * @note	Return of NULL indicates password has been provided correctly
	 */
	public function passwordForm()
	{
		/* Already have access? */
		if ( $this->loggedInMemberHasPasswordAccess() && !isset( \IPS\Request::i()->passForm ) )
		{
			return NULL;
		}
		
		/* Build form */
		$password = $this->password;
		$form = new \IPS\Helpers\Form( 'forum_password', 'continue' );
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Password( 'password', NULL, TRUE, array(), function( $val ) use ( $password )
		{
			if ( $val != $password )
			{
				throw new \DomainException( 'forum_password_bad' );
			}
		} ) );
		
		/* If we got the value, it's fine */
		if ( $form->values() )
		{
			/* Set Cookie */
			$this->setPasswordCookie( $password );
			
			/* If we have a topic ID, redirect to it */
			if ( isset( \IPS\Request::i()->topic ) )
			{
				try
				{
					\IPS\Output::i()->redirect( \IPS\forums\Topic::loadAndCheckPerms( \IPS\Request::i()->topic )->url() );
				}
				catch ( \OutOfRangeException $e ) { }
			}
			
			/* Make sure passForm isn't returned on the URL if viewing the forum */
			if ( isset( \IPS\Request::i()->module ) and isset( \IPS\Request::i()->controller ) and \IPS\Request::i()->module === 'forums' and \IPS\Request::i()->controller === 'forums' )
			{
				\IPS\Output::i()->redirect( $this->url() );
			}
			
			/* Return */
			return NULL;
		}
		
		/* Return */
		return $form;
	}
	
	/**
	 * Set Password Cookie
	 *
	 * @param	string	$password	Password to set for forum
	 * @return	void
	 */
	public function setPasswordCookie( $password )
	{
		\IPS\Request::i()->setCookie( 'ipbforumpass_' . $this->id, md5( $password ), \IPS\DateTime::create()->add( new \DateInterval( 'P7D' ) ) );
	}
	
	/**
	 * Set Theme
	 *
	 * @return	void
	 */
	public function setTheme()
	{
		if ( $this->skin_id )
		{
			\IPS\Theme::switchTheme( $this->skin_id );
		}
		
		if ( !$this->viglink )
		{
			\IPS\Settings::i()->viglink_enabled = FALSE;
		}
	}
	
	/**
	 * Load into memory (taking permissions into account)
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	array				$where				Additional where clause
	 * @return	void
	 */
	public static function loadIntoMemory( $permissionCheck='view', $member=NULL, $where = array() )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		if ( in_array( $permissionCheck, array( 'add', 'reply' ) ) )
		{
			$where[] = array( 'sub_can_post=1' );
			$where[] = array( 'min_posts_post<=?', $member->member_posts );
		}
		
		if ( $permissionCheck == 'view' )
		{
			$where[] = array( 'min_posts_view<=?', $member->member_posts );
			if ( !$member->member_id )
			{
				$where[] = array( 'sub_can_post=0 OR can_view_others=1' );
			}
		}
		
		if ( in_array( $permissionCheck, array( 'read', 'add' ) ) )
		{
			$whereString = 'password=? OR ' . \IPS\Db::i()->findInSet( 'forums_forums.password_override', $member->groups );
			$whereParams = array( NULL );
			if ( $member === \IPS\Member::loggedIn() )
			{
				foreach ( \IPS\Request::i()->cookie as $k => $v )
				{
					if ( mb_substr( $k, 0, 13 ) === 'ipbforumpass_' )
					{
						$whereString .= ' OR ( forums_forums.id=? AND MD5(forums_forums.password)=? )';
						$whereParams[] = (int) mb_substr( $k, 13 );
						$whereParams[] = $v;
					}
				}
			}
			$where[] = array_merge( array( '( ' . $whereString . ' )' ), $whereParams );
		}
		
		return parent::loadIntoMemory( $permissionCheck, $member, $where );
	}
	
	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission		A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	\IPS\Member|\IPS\Member\Group|NULL	$member			The member or group to check (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public function can( $permission, $member=NULL )
	{
		if ( !$this->sub_can_post and in_array( $permission, array( 'add', 'reply' ) ) )
		{
			return FALSE;
		}

		$_member = $member ?: \IPS\Member::loggedIn();
		if ( $permission == 'view' and $this->min_posts_view and $this->min_posts_view > $_member->member_posts )
		{
			return FALSE;
		}
		if ( !$_member->member_id and $this->sub_can_post and !$this->can_view_others )
		{
			return FALSE;
		}
		if ( in_array( $permission, array( 'add', 'reply' ) ) and $this->min_posts_post and $this->min_posts_post >= $_member->member_posts )
		{
			return FALSE;
		}
						
		$return = parent::can( $permission, $member );
		
		if ( $return === TRUE and $this->password !== NULL and in_array( $permission, array( 'read', 'add' ) ) and ( ( $member !== NULL and $member !== \IPS\Member::loggedIn() ) or !$this->loggedInMemberHasPasswordAccess() ) )
		{
			return FALSE;
		}
		
		return $return;
	}
	
	/**
	 * Get "No Permission" error message
	 *
	 * @return	string
	 */
	public function errorMessage()
	{
		if ( \IPS\Member::loggedIn()->language()->checkKeyExists( "forums_forum_{$this->id}_permerror" ) )
		{
			$message = trim( \IPS\Member::loggedIn()->language()->get( "forums_forum_{$this->id}_permerror" ) );
			if ( $message and $message != '<p></p>' )
			{
				return $message;
			}
		}
		
		return 'node_error_no_perm';
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );
		
		if ( isset( $buttons['permissions'] ) )
		{
			$buttons['permissions']['data'] = NULL;
		}
		
		if ( !$this->sub_can_post and isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'forums_add_child_cat';
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$groups = array();
		foreach ( \IPS\Member\Group::groups() as $k => $v )
		{
			$groups[ $k ] = $v->name;
		}
		$groupsNoGuests = array();
		foreach ( \IPS\Member\Group::groups( TRUE, FALSE ) as $k => $v )
		{
			$groupsNoGuests[ $k ] = $v->name;
		}
				
		$form->addTab( 'forum_settings' );
		$form->addHeader( 'forum_settings' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'forum_name', NULL, TRUE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'forum_description', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_desc" : NULL ), 'editor' => array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-forum-{$this->id}" : "forums-new-forum" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'description' ) : NULL, 'minimize' => 'forum_description_placeholder' ) ) ) );
		
		$type = 'normal';
		if ( $this->id )
		{
			if ( $this->redirect_url )
			{
				$type = 'redirect';
			}
			elseif ( !$this->sub_can_post )
			{
				$type = 'category';
			}
			elseif ( $this->forums_bitoptions['bw_enable_answers'] )
			{
				$type = 'qa';
			}
		}
		elseif ( !isset( \IPS\Request::i()->parent ) )
		{
			$type = 'category';
		}
				
		$id = $this->id ?: 'new';
		$form->add( new \IPS\Helpers\Form\Radio( 'forum_type', $type, TRUE, array(
			'options' => array(
				'normal' 	=> 'forum_type_normal',
				'qa' 		=> 'forum_type_qa',
				'category'	=> 'forum_type_category',
				'redirect'	=> 'forum_type_redirect'
			),
			'toggles'	=> array(
				'normal'	=> array( // make sure when adding here that you also add to qa below
					'forum_password_on',
					'forum_ipseo_priority',
					'forum_viglink',
					'forum_min_posts_view',
					'forum_can_view_others',
					'forum_permission_showtopic',
					'forum_permission_custom_error',
					"form_{$id}_header_permissions",
					"form_{$id}_tab_forum_display",
					"form_{$id}_tab_posting_settings",
					"form_{$id}_header_forum_display_topic",
					'forum_preview_posts',
					'forum_icon',
					'forum_sort_key'
				),
				'qa'	=> array(
					'forum_password_on',
					'forum_ipseo_priority',
					'forum_viglink',
					'forum_min_posts_view',
					'forum_can_view_others_qa',
					'forum_permission_showtopic_qa',
					'forum_permission_custom_error',
					"form_{$id}_header_permissions",
					"form_{$id}_tab_forum_display",
					"form_{$id}_tab_posting_settings",
					"form_{$id}_header_forum_display_question",
					'forum_can_view_others_qa',
					'bw_enable_answers_member',
					'forum_qa_rate_questions',
					'forum_qa_rate_answers',
					'forum_preview_posts_qa',
					'forum_icon',
					'forum_sort_key_qa'
				),
				'redirect'	=> array(
					'forum_password_on',
					'forum_redirect_url',
					'forum_redirect_hits'
				),
			)
		) ) );

		$class = get_called_class();

		$form->add( new \IPS\Helpers\Form\Node( 'forum_parent_id', ( !$this->id AND $this->parent_id === -1 ) ? NULL : ( $this->parent_id === -1 ? 0 : $this->parent_id ), FALSE, array(
			'class'		      	=> '\IPS\forums\Forum',
			'disabled'	      	=> array(),
			'zeroVal'         	=> 'node_no_parentf',
			'zeroValTogglesOff'	=> array( 'form_new_forum_type', 'forum_icon' ),
			'permissionCheck' => function( $node ) use ( $class )
			{
				if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
				{
					return FALSE;
				}

				return !isset( \IPS\Request::i()->id ) or ( $node->id != \IPS\Request::i()->id and !$node->isChildOf( $node::load( \IPS\Request::i()->id ) ) );
			}
		), function( $val )
		{
			if ( !$val and \IPS\Request::i()->forum_type !== 'category' )
			{
				throw new \DomainException('forum_parent_id_error');
			}
		} ) );
		
		$form->add( new \IPS\Helpers\Form\Upload( 'forum_icon', $this->icon ? \IPS\File::get( 'forums_Icons', $this->icon ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'forums_Icons' ), NULL, NULL, NULL, 'forum_icon' ) );
		
		$form->add( new \IPS\Helpers\Form\Url( 'forum_redirect_url', $this->id ? $this->redirect_url : array(), FALSE, array( 'placeholder' => 'http://www.example.com/' ), NULL, NULL, NULL, 'forum_redirect_url' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'forum_redirect_hits', $this->id ? $this->redirect_hits : 0, FALSE, array(), NULL, NULL, NULL, 'forum_redirect_hits' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_password_on', $this->id ? ( $this->password !== NULL ) : FALSE, FALSE, array( 'togglesOn' => array( 'forum_password', 'forum_password_override' ) ), NULL, NULL, NULL, 'forum_password_on' ) );
		$form->add( new \IPS\Helpers\Form\Password( 'forum_password', $this->password, FALSE, array(), NULL, NULL, NULL, 'forum_password' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'forum_password_override', $this->id ? explode( ',', $this->password_override ) : array(), FALSE, array( 'options' => $groups, 'multiple' => TRUE ), NULL, NULL, NULL, 'forum_password_override' ) );
		if ( count( \IPS\Theme::themes() ) > 1 )
		{
			$themes = array( 0 => 'forum_skin_id_default' );
			foreach ( \IPS\Theme::themes() as $theme )
			{
				$themes[ $theme->id ] = $theme->_title;
			}
			$form->add( new \IPS\Helpers\Form\Select( 'forum_skin_id', $this->id ? $this->skin_id : 0, FALSE, array( 'options' => $themes ), NULL, NULL, NULL, 'forum_skin_id' ) );
		}
		
		$form->add( new \IPS\Helpers\Form\Select( 'forum_ipseo_priority', $this->id ? $this->ipseo_priority : '-1', FALSE, array(
			'options' => array(
				'1'		=> '1',
				'0.9'	=> '0.9',
				'0.8'	=> '0.8',
				'0.7'	=> '0.7',
				'0.6'	=> '0.6',
				'0.5'	=> '0.5',
				'0.4'	=> '0.4',
				'0.3'	=> '0.3',
				'0.2'	=> '0.2',
				'0.1'	=> '0.1',
				'0'		=> 'sitemap_do_not_include',
				'-1'	=> 'sitemap_default_priority'
			)
		), NULL, NULL, NULL, 'forum_ipseo_priority' ) );
		
		if ( \IPS\Settings::i()->viglink_enabled )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'forum_viglink', $this->id ? $this->viglink : TRUE, FALSE, array(), NULL, NULL, NULL, 'forum_viglink' ) );
		}
		
		$form->addHeader( 'permissions' );
		$form->add( new \IPS\Helpers\Form\Number( 'forum_min_posts_view', $this->id ? $this->min_posts_view : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'no_restriction' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('approved_posts_comments'), 'forum_min_posts_view' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_can_view_others', $this->id ? $this->can_view_others : TRUE, FALSE, array(), NULL, NULL, NULL, 'forum_can_view_others' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_can_view_others_qa', $this->id ? $this->can_view_others : TRUE, FALSE, array(), NULL, NULL, NULL, 'forum_can_view_others_qa' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_permission_showtopic', $this->permission_showtopic ?: 0, FALSE, array(), NULL, NULL, NULL, 'forum_permission_showtopic' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_permission_showtopic_qa', $this->permission_showtopic ?: 0, FALSE, array(), NULL, NULL, NULL, 'forum_permission_showtopic_qa' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'forum_permission_custom_error', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_permerror" : NULL ), 'editor' => array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-permerror-{$this->id}" : "forums-new-permerror" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'permerror' ) : NULL, 'minimize' => 'forum_permerror_placeholder' ) ), NULL, NULL, NULL, 'forum_permission_custom_error' ) );
		
		$form->addTab( 'forum_display' );
		$form->addHeader( 'forum_display_forum' );
		
		$sortOptions = array( 'last_post' => 'sort_updated', 'last_real_post' => 'sort_last_comment', 'posts' => 'sort_num_comments', 'views' => 'sort_views', 'title' => 'sort_title', 'starter_name' => 'sort_author_name', 'last_poster_name' => 'sort_last_comment_name', 'start_date' => 'sort_date' );
		$sortOptionsQA = array( 'question_rating' => 'sort_question_rating' );

		$form->add( new \IPS\Helpers\Form\Select( 'forum_sort_key', $this->id ? $this->sort_key : 'last_post', FALSE, array( 'options' => $sortOptions ), NULL, NULL, NULL, 'forum_sort_key' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'forum_sort_key_qa', $this->id ? $this->sort_key : 'last_post', FALSE, array( 'options' => array_merge( $sortOptions, $sortOptionsQA ) ), NULL, NULL, NULL, 'forum_sort_key_qa' ) );

		$form->add( new \IPS\Helpers\Form\Radio( 'forum_show_rules', $this->id ? $this->show_rules : 0, FALSE, array(
			'options' => array(
				0	=> 'forum_show_rules_none',
				1	=> 'forum_show_rules_link',
				2	=> 'forum_show_rules_full'
			),
			'toggles'	=> array(
				1	=> array(
					'forum_rules_title',
					'forum_rules_text'
				),
				2	=> array(
					'forum_rules_title',
					'forum_rules_text'
				),
			)
		) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'forum_rules_title', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_rulestitle" : NULL ) ), NULL, NULL, NULL, 'forum_rules_title' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'forum_rules_text', NULL, FALSE, array( 'app' => 'forums', 'key' => ( $this->id ? "forums_forum_{$this->id}_rules" : NULL ), 'editor' => array( 'app' => 'forums', 'key' => 'Forums', 'autoSaveKey' => ( $this->id ? "forums-rules-{$this->id}" : "forums-new-rules" ), 'attachIds' => $this->id ? array( $this->id, NULL, 'rules' ) : NULL ) ), NULL, NULL, NULL, 'forum_rules_text' ) );
		
		$form->addHeader( 'forum_display_topic' );
		$form->addHeader( 'forum_display_question' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'bw_enable_answers_member', $this->id ? $this->forums_bitoptions['bw_enable_answers_member'] : TRUE, FALSE, array(), NULL, NULL, NULL, 'bw_enable_answers_member' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'forum_qa_rate_questions', $this->id ? (  ( $this->qa_rate_questions == '*' or $this->qa_rate_questions === NULL ) ? '*' : explode( ',', $this->qa_rate_questions ) ) : '*', FALSE, array( 'options' => $groupsNoGuests, 'unlimited' => '*', 'multiple' => TRUE ), NULL, NULL, NULL, 'forum_qa_rate_questions' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'forum_qa_rate_answers', $this->id ? (  ( $this->qa_rate_answers == '*' or $this->qa_rate_answers === NULL ) ? '*' : explode( ',', $this->qa_rate_answers ) ) : '*', FALSE, array( 'options' => $groupsNoGuests, 'unlimited' => '*', 'multiple' => TRUE ), NULL, NULL, NULL, 'forum_qa_rate_answers' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_forum_allow_rating', $this->id ? $this->forum_allow_rating : FALSE, FALSE, array(), NULL, NULL, NULL, 'forum_allow_rating' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_disable_sharelinks', $this->id ? !$this->disable_sharelinks : TRUE, FALSE, array(), NULL, NULL, NULL, 'forum_disable_sharelinks' ) );
		
		
		$form->addTab( 'posting_settings' );
		$form->addHeader('posts');
		
		$previewPosts = array();
		if ( $this->id )
		{
			switch ( $this->preview_posts )
			{
				case 1:
					$previewPosts = array( 'topics', 'posts' );
					break;
				case 2:
					$previewPosts = array( 'topics' );
					break;
				case 3:
					$previewPosts = array( 'posts' );
					break;
			}
		}
		
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'forum_preview_posts', $previewPosts, FALSE, array( 'options' => array( 'topics' => 'forum_preview_posts_topics', 'posts' => 'forum_preview_posts_posts' ) ), NULL, NULL, NULL, 'forum_preview_posts' ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'forum_preview_posts_qa', $previewPosts, FALSE, array( 'options' => array( 'topics' => 'forum_preview_posts_questions', 'posts' => 'forum_preview_posts_answers' ) ), NULL, NULL, NULL, 'forum_preview_posts_qa' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_inc_postcount', $this->id ? $this->inc_postcount : TRUE, FALSE, array() ) );
		$form->addHeader( 'polls' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'forum_allow_poll', $this->id ? $this->allow_poll : TRUE, FALSE, array() ) );
		$form->addHeader( 'posting_requirements' );
		$form->add( new \IPS\Helpers\Form\Number( 'forum_min_posts_post', $this->id ? $this->min_posts_post : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'no_restriction' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('approved_posts_comments') ) );
		
		if ( \IPS\Settings::i()->tags_enabled )
		{
			$form->addHeader( 'tags' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'bw_disable_tagging', !$this->forums_bitoptions['bw_disable_tagging'], FALSE, array( 'togglesOn' => array( 'bw_disable_prefixes', 'forum_tag_predefined' ) ), NULL, NULL, NULL, 'bw_disable_tagging' ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'bw_disable_prefixes', !$this->forums_bitoptions['bw_disable_prefixes'], FALSE, array(), NULL, NULL, NULL, 'bw_disable_prefixes' ) );
			if ( !\IPS\Settings::i()->tags_open_system )
			{
				$form->add( new \IPS\Helpers\Form\Text( 'forum_tag_predefined', $this->tag_predefined ?: NULL, FALSE, array( 'autocomplete' => array( 'unique' => 'true' ), 'nullLang' => 'forum_tag_predefined_unlimited' ), NULL, NULL, NULL, 'forum_tag_predefined' ) );
			}
		}
	}
	
	/**
	 * [Node] Can this node have children?
	 *
	 * @return bool
	 */
	public function canAdd()
	{
		if ( $this->redirect_on )
		{
			return FALSE;
		}
		return parent::canAdd();
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		/* Type */
		if ( isset( $values['forum_parent_id'] ) AND $values['forum_parent_id'] === 0 )
		{
			$values['forum_type'] = 'category';
		}
		
		if ( isset( $values['forum_type'] ) )
		{
			if( $values['forum_type'] !== 'redirect' )
			{
				$values['sub_can_post'] = ( $values['forum_type'] !== 'category' );
				$values['redirect_on'] = FALSE;
				$values['forum_redirect_url'] = NULL;
				
				if ( $values['forum_type'] === 'qa' )
				{
					$values['forums_bitoptions']['bw_enable_answers'] = TRUE;
					$values['forum_preview_posts'] = $values['forum_preview_posts_qa'];
					$values['forum_can_view_others'] = $values['forum_can_view_others_qa'];
					$values['forum_permission_showtopic'] = $values['forum_permission_showtopic_qa'];
					$values['forum_sort_key'] = $values['forum_sort_key_qa'];
				}
				else
				{
					$values['forums_bitoptions']['bw_enable_answers'] = FALSE;
				}
			}
			else
			{
				$values['sub_can_post'] = FALSE;
				$values['redirect_on'] = TRUE;
			}

			unset( $values['forum_can_view_others_qa'] );
			unset( $values['forum_permission_showtopic_qa'] );
			unset( $values['forum_preview_posts_qa'] );
			unset( $values['forum_sort_key_qa'] );
		}
		
		if ( isset( $values['forum_parent_id'] ) )
		{
			if ( $values['forum_parent_id'] )
			{
				$values['forum_parent_id'] = is_scalar( $values['forum_parent_id'] ) ? intval( $values['forum_parent_id'] ) : intval( $values['forum_parent_id']->id );
			}
			else
			{
				$values['forum_parent_id'] = -1;
			}
		}
		
		/* Bitwise */
		foreach ( array( 'bw_disable_tagging', 'bw_disable_prefixes', 'bw_enable_answers_member' ) as $k )
		{
			if( isset( $values[ $k ] ) )
			{
				$values['forums_bitoptions'][ $k ] = ( $k == 'bw_enable_answers_member' ) ? $values[ $k ] : !$values[ $k ];
				unset( $values[ $k ] );
			}
		}
		
		/* Remove forum_ prefix */
		$_values = $values;
		$values = array();
		foreach ( $_values as $k => $v )
		{
			if( mb_substr( $k, 0, 6 ) === 'forum_' )
			{
				$values[ mb_substr( $k, 6 ) ] = $v;
			}
			else
			{
				$values[ $k ]	= $v;
			}
		}
		
		/* Implode */
		foreach ( array( 'password_override', 'tag_predefined', 'qa_rate_questions', 'qa_rate_answers' ) as $k )
		{
			if ( isset( $values[ $k ] ) )
			{
				$values[ $k ] = ( is_array( $values[ $k ] ) ) ? implode( ',', $values[ $k ] ) : $values[ $k ];
			}
		}

		/* Set forum password to NULL if not there */
		if ( isset( $values['password'] ) AND ( $values['password'] === '' or !$values['password_on'] ) )
		{
			$values['password'] = NULL;
		}
		
		/* Reverse */
		if( isset( $values['disable_sharelinks'] ) )
		{
			$values['disable_sharelinks'] = !$values['disable_sharelinks'];
		}
		
		/* Moderation */
		if( isset( $values['preview_posts'] ) )
		{
			if ( in_array( 'topics', $values['preview_posts'] ) and in_array( 'posts', $values['preview_posts'] ) )
			{
				$values['preview_posts'] = 1;
			}
			elseif ( in_array( 'topics', $values['preview_posts'] ) )
			{
				$values['preview_posts'] = 2;
			}
			elseif ( in_array( 'posts', $values['preview_posts'] ) )
			{
				$values['preview_posts'] = 3;
			}
			else
			{
				$values['preview_posts'] = 0;
			}
		}

		if ( !$this->id )
		{
			$this->save();
		}

		foreach ( array( 'name' => "forums_forum_{$this->id}", 'description' => "forums_forum_{$this->id}_desc", 'rules_title' => "forums_forum_{$this->id}_rulestitle", 'rules_text' => "forums_forum_{$this->id}_rules", 'permission_custom_error' => "forums_forum_{$this->id}_permerror" ) as $fieldKey => $langKey )
		{
			if ( array_key_exists( $fieldKey, $values ) )
			{
				\IPS\Lang::saveCustom( 'forums', $langKey, $values[ $fieldKey ] );
				
				if ( $fieldKey === 'name' )
				{
					$this->name_seo = \IPS\Http\Url::seoTitle( $values[ $fieldKey ][ \IPS\Lang::defaultLanguage() ] );
					$this->save();
				}
				
				unset( $values[ $fieldKey ] );
			}
		}
		
		/* Just for toggles */
		foreach ( array( 'type', 'password_on' ) as $k )
		{
			if( isset( $values[ $k ] ) )
			{
				unset( $values[ $k ] );
			}
		}
		
		/* Update index */
		if( $this->can_view_others !== NULL and isset( $values['can_view_others'] ) and $values['can_view_others'] != $this->can_view_others )
		{
			$this->can_view_others = $values['can_view_others'];
			$this->updateSearchIndexPermissions();
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		\IPS\File::claimAttachments( 'forums-new-forum', $this->id, NULL, 'description', TRUE );
		\IPS\File::claimAttachments( 'forums-new-permerror', $this->id, NULL, 'permerror', TRUE );
		\IPS\File::claimAttachments( 'forums-new-rules', $this->id, NULL, 'rules', TRUE );
	}
	
	/**
	 * Can a value be copied to this node?
	 *
	 * @return	bool
	 */
	public function canCopyValue( $key, $value )
	{
		if ( mb_strpos( $key, 'forum_' ) === 0 )
		{
			$key = mb_substr( $key, 6 );
		}
		return parent::canCopyValue( $key, $value );
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static $urlBase = 'app=forums&module=forums&controller=forums&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static $urlTemplate = 'forums_forum';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static $seoTitleColumn = 'name_seo';
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\File::unclaimAttachments( 'forums_Forums', $this->id );
		parent::delete();
		
		foreach ( array( 'rules_title' => "forums_forum_{$this->id}_rulestitle", 'rules_text' => "forums_forum_{$this->id}_rules", 'permission_custom_error' => "forums_forum_{$this->id}_permerror" ) as $fieldKey => $langKey )
		{
			\IPS\Lang::deleteCustom( 'forums', $langKey );
		}
	}
	
	/**
	 * Get template for node tables
	 *
	 * @return	callable
	 */
	public static function nodeTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'index', 'forums' ), 'forumTableRow' );
	}

	/**
	 * Get template for managing this nodes follows
	 *
	 * @return	callable
	 */
	public static function manageFollowNodeRow()
	{
		return array( \IPS\Theme::i()->getTemplate( 'global', 'forums' ), 'manageFollowNodeRow' );
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}
		
		$oldId = $this->id;
		$oldIcon = $this->icon;
		
		$this->show_rules = 0;

		parent::__clone();

		foreach ( array( 'rules_title' => "forums_forum_{$this->id}_rulestitle", 'rules_text' => "forums_forum_{$this->id}_rules", 'permission_custom_error' => "forums_forum_{$this->id}_permerror" ) as $fieldKey => $langKey )
		{
			$oldLangKey = str_replace( $this->id, $oldId, $langKey );
			\IPS\Lang::saveCustom( 'forums', $langKey, iterator_to_array( \IPS\Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', $oldLangKey ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}
		
		if ( $oldIcon )
		{
			try
			{
				$icon = \IPS\File::get( 'forums_Icons', $oldIcon );
				$newIcon = \IPS\File::create( 'forums_Icons', $icon->originalFilename, $icon->contents() );
				$this->icon = (string) $newIcon;
			}
			catch ( \Exception $e )
			{
				$this->icon = NULL;
			}
			
			$this->save();
		}
	}
		
	/**
	 * If there is only one forum (and it isn't a redirect forum or password protected), that forum, or NULL
	 *
	 * @return	\IPS\forums\Forum||NULL
	 */
	public static function theOnlyForum()
	{
		if ( count( \IPS\forums\Forum::roots() ) === 1 )
		{
			foreach ( \IPS\forums\Forum::roots() as $root )
			{
				if ( $root->childrenCount() === 1 )
				{
					foreach ( $root->children() as $forum )
					{
						if ( !$forum->redirect_url and !$forum->password )
						{
							return $forum;
						}
					}
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * Search Index Permissions
	 * Overloaded here so we can discard categories as they should not influence permissions overall.
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions()
	{
		/* Compare both read and view */
		$result	= static::_getPermissions( $this );

		/* And then loop up the parents too... */
		foreach ( $this->parents() as $parent )
		{
			$parentResult = static::_getPermissions( $parent );
			
			if( $result == '*' )
			{
				$result	= $parentResult;
			}
			else if( $parentResult != '*' )
			{
				$result	= implode( ',', array_intersect( explode( ',', $result ), explode( ',', $parentResult ) ) );
			}
		}

		return $result;
	}
	
	/**
	 * Get which permission keys can access all topics in a forum which
	 * can normally only show topics to the author
	 * 
	 * @return	array
	 */
	public function permissionsThatCanAccessAllTopics()
	{
		$normal		= $this->searchIndexPermissions();
		$return		= array();
		$members	= array();
		
		foreach ( \IPS\Db::i()->select( '*', 'core_moderators' ) as $moderator )
		{
			if ( $moderator['perms'] === '*' or in_array( 'can_read_all_topics', explode( ',', $moderator['perms'] ) ) )
			{
				if( $moderator['type'] === 'g' )
				{
					$return[] = $moderator['id'];
				}
				else
				{
					$members[] = "m{$moderator['id']}";
				}
			}
		}
		
		$return = ( $normal == '*' ) ? array_unique( $return ) : array_intersect( explode( ',', $normal ), array_unique( $return ) );
	
		if( count( $members ) )
		{
			$return = array_merge( $return, $members );
		}
		
		return $return;
	}
	
	/**
	 * Update search index permissions
	 *
	 * @return  void
	 */
	protected function updateSearchIndexPermissions()
	{
		if ( $this->can_view_others )
		{
			return parent::updateSearchIndexPermissions();
		}
		else
		{
			$permissions = implode( ',', $this->permissionsThatCanAccessAllTopics() );
			\IPS\Content\Search\Index::i()->massUpdate( 'IPS\forums\Topic', $this->_id, NULL, $permissions, NULL, NULL, NULL, NULL, NULL, TRUE );
			\IPS\Content\Search\Index::i()->massUpdate( 'IPS\forums\Topic\Post', $this->_id, NULL, $permissions, NULL, NULL, NULL, NULL, NULL, TRUE );
		}
	}
	
	/**
	 * Mass move content items in this node to another node
	 *
	 * @param	\IPS\Node\Model|null	$node	New node to move content items to, or NULL to delete
	 * @param	array|null				$data	Additional filters to mass move by
	 * @return	NULL|int
	 */
	public function massMoveorDelete( $node=NULL, $data=NULL )
	{
		/* If we are mass deleting, let parent handle it. Also do this the slow way if we can't view other topics in the destination forum, because we need to
			adjust search index permissions on a row-by-row basis in that case. */
		if( !$node OR !$node->can_view_others )
		{
			return parent::massMoveorDelete( $node, $data );
		}

		/* If this is not a true mass move of contents of one container to another, then let parent handle it normally */
		if( isset( $data['additional'] ) AND 
			( isset( $data['additional']['author'] ) OR ( isset( $data['additional']['no_comments'] ) AND $data['additional']['no_comments'] > 0 ) OR
			( isset( $data['additional']['num_comments'] ) AND $data['additional']['num_comments'] > 0 ) OR isset( $data['additional']['state'] ) OR
			( isset( $data['additional']['pinned'] ) AND $data['additional']['pinned'] === TRUE ) OR ( isset( $data['additional']['featured'] ) AND $data['additional']['featured'] === TRUE ) ) 
		)
		{
			return parent::massMoveorDelete( $node, $data );
		}

		/* Can we allow the mass move? */
		if(	!$node->sub_can_post or $node->redirect_url )
		{
			throw new \InvalidArgumentException;
		}

		/* Adjust the node counts */
		$contentItemClass = static::$contentItemClass;

		if( $this->_futureItems !== NULL )
		{
			$node->_futureItems		= $node->_futureItems + $this->_futureItems;
			$this->_futureItems		= 0;
		}

		if ( $this->_items !== NULL )
		{
			$node->_items			= $node->_items + $this->_items;
			$this->_items			= 0;
		}

		if ( $this->_unapprovedItems !== NULL )
		{
			$node->_unapprovedItems	= $node->_unapprovedItems + $this->_unapprovedItems;
			$this->_unapprovedItems	= 0;
		}

		if ( isset( $contentItemClass::$commentClass ) and $this->_comments !== NULL )
		{
			$node->_comments		= $node->_comments + $this->_comments;
			$this->_comments		= 0;

			if( $this->_unapprovedComments !== NULL and isset( $contentItemClass::$databaseColumnMap['unapproved_comments'] ) )
			{
				$node->_unapprovedComments	= $node->_unapprovedComments + $this->_unapprovedComments;
				$this->_unapprovedComments	= 0;
			}
		}
		if ( isset( $contentItemClass::$reviewClass ) and $this->_reviews !== NULL )
		{
			$node->_reviews			= $node->_reviews + $this->_reviews;
			$this->_reviews			= 0;

			if( $this->_unapprovedReviews !== NULL and isset( $contentItemClass::$databaseColumnMap['unapproved_reviews'] ) )
			{
				$node->_unapprovedReviews	= $node->_unapprovedReviews + $this->_unapprovedReviews;
				$this->_unapprovedReviews	= 0;
			}
		}

		/* Do the move */
		\IPS\Db::i()->update( 'forums_topics', array( 'forum_id' => $node->_id ), array( 'forum_id=?', $this->_id ) );
		\IPS\Db::i()->update( 'forums_question_ratings', array( 'forum' => $node->_id ), array( 'forum=?', $this->_id ) );

		/* Rebuild tags */
		if ( in_array( 'IPS\Content\Tags', class_implements( $contentItemClass ) ) )
		{
			\IPS\Db::i()->update( 'core_tags', array(
				'tag_aap_lookup'		=> md5( static::$permApp . ';' . static::$permType . ';' . $node->_id ),
				'tag_meta_parent_id'	=> $node->_id
			), array( 'tag_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );

			if ( isset( static::$permissionMap['read'] ) )
			{
				\IPS\Db::i()->update( 'core_tags_perms', array(
					'tag_perm_aap_lookup'	=> md5( static::$permApp . ';' . static::$permType . ';' . $node->_id ),
					'tag_perm_text'			=> \IPS\Db::i()->select( 'perm_' . static::$permissionMap['read'], 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', static::$permApp, static::$permType, $node->_id ) )->first()
				), array( 'tag_perm_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );
			}
		}

		/* Rebuild node data */
		$node->setLastComment();
		$node->setLastReview();
		$node->save();
		$this->setLastComment();
		$this->setLastReview();
		$this->save();

		/* Add to search index */
		if ( in_array( 'IPS\Content\Searchable', class_implements( $contentItemClass ) ) )
		{
			/* Grab permissions...we already account for !can_view_others by letting the parent handle this the old fashioned way in that case at the start of the method */
			$permissions = $node->searchIndexPermissions();

			/* Do the update */
			\IPS\Content\Search\Index::i()->massUpdate( $contentItemClass, $this->_id, NULL, $permissions, NULL, $node->_id );

			foreach ( array( 'commentClass', 'reviewClass' ) as $class )
			{
				if ( isset( $contentItemClass::$$class ) )
				{
					$className = $contentItemClass::$$class;
					if ( in_array( 'IPS\Content\Searchable', class_implements( $className ) ) )
					{
						\IPS\Content\Search\Index::i()->massUpdate( $className, $this->_id, NULL, $permissions, NULL, $node->_id );
					}
				}
			}
		}

		/* Update caches */
		\IPS\Widget::deleteCaches( NULL, static::$permApp );

		/* Log */
		\IPS\Session::i()->modLog( 'modlog__action_massmove', array( $contentItemClass::$title . '_pl_lc' => TRUE, $node->url()->__toString() => FALSE, $node->_title => FALSE ) );

		return NULL;
	}
	
	/**
	 * Number of unapproved topics/posts in forum and all subforums
	 *
	 * @return	array
	 */
	public function unapprovedContentRecursive()
	{
		$return = array( 'topics' => $this->queued_topics, 'posts' => $this->queued_posts );
		
		foreach ( $this->children() as $child )
		{
			$childCounts = $child->unapprovedContentRecursive();
			$return['topics'] += $childCounts['topics'];
			$return['posts'] += $childCounts['posts'];
		}
		
		return $return;
	}

	/**
	 * Disabled permissions
	 * Allow node classes to define permissions that are unselectable in the permission matrix
	 *
	 * @return array	array( {group_id} => array( 'read', 'view', 'perm_7' );
	 */
	public function disabledPermissions()
	{
		$disabled  = array();
		$guestGroup = \IPS\Member\Group::load( \IPS\Settings::i()->guest_group );

		if( !$this->can_view_others )
		{
			$disabled[ $guestGroup->g_id ][] = 'view';
		}

		return $disabled;
	}
	
	/**
	 * The permission key or function used when building a node selector
	 * in search or stream functions.
	 *
	 * @return string|callable function
	 */
	public static function searchableNodesPermission()
	{
		return function( $node )
		{
			if ( $node->can( 'view' ) and $node->sub_can_post )
			{
				return TRUE;
			}
			
			return FALSE;
		};
	}
	
	/**
	 * @brief	Cached unsearchable node ids
	 */
	protected static $unsearchableNodeIds	= FALSE;

	/**
	 * Return either NULL for no restrictions, or a list of container IDs we cannot search in because of app specific permissions and configuration
	 * You do not need to check for 'view' permissions against the logged in member here. The Query search class does this for you.
	 * This method is intended for more complex set up items, like needing to have X posts to see a forum, etc.
	 * This is used for search and the activity stream.
	 * We return a list of IDs and not node objects for memory efficiency.
	 *
	 * return 	null|array
	 */
	public static function unsearchableNodeIds()
	{
		if( static::$unsearchableNodeIds !== FALSE )
		{
			return static::$unsearchableNodeIds;
		}

		/* For memory efficiency, we query the database directly rather than manage nodes */
		$forums = iterator_to_array( \IPS\Db::i()->select( 'id', 'forums_forums', array( 'min_posts_view > ?', \IPS\Member::loggedIn()->member_posts ) )->setKeyField('id') );
		static::$unsearchableNodeIds	= count( $forums ) ? $forums : NULL;
		return static::$unsearchableNodeIds;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse	int			id			ID number
	 * @apiresponse	string		name		Forum name
	 * @apiresponse	int			topics		Number of topics in forum
	 * @apiresponse	string		url			URL
	 */
	public function apiOutput()
	{
		return array(
			'id'		=> $this->id,
			'name'		=> $this->_title,
			'topics'	=> $this->topics,
			'url'		=> (string) $this->url()
		);
	}
}