<?php
/**
 * @brief		Status Update Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Statuses;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Status Update Model
 */
class _Status extends \IPS\Content\Item implements \IPS\Content\ReportCenter, \IPS\Content\Reputation, \IPS\Content\Lockable, \IPS\Content\Hideable, \IPS\Content\Searchable, \IPS\Content\Shareable
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_member_status_updates';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'status_';
	
	/**
	 * @brief	[Content\Comment]	Icon
	 */
	public static $icon = 'comment-o';
	
	/**
	 * @brief	Number of comments per page
	 */
	public static $commentsPerPage = 3;

	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[Content\Item]	Include the ability to search this content item in global site searches
	 */
	public static $includeInSiteSearch = FALSE;
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array|string
	 */
	public static function basicDataColumns()
	{
		return '*';
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		\IPS\Widget::deleteCaches( 'recentStatusUpdates', 'core' );	
	}
	
	/**
	 * Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_member_status_replies', array( 'reply_status_id=?', $this->id ) ), 'IPS\core\Statuses\Reply' ) AS $reply )
		{
			$reply->delete();
		}
		
		parent::delete();
		\IPS\Widget::deleteCaches( 'recentStatusUpdates', 'core' );	
	}
	
	/* !\IPS\Content\Item */

	/**
	 * @brief	Title
	 */
	public static $title = 'member_status';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'date'			=> 'date',
		'author'		=> 'author_id',
		'num_comments'	=> 'replies',
		'locked'		=> 'is_locked',
		'approved'		=> 'approved',
		'ip_address'	=> 'author_ip',
		'content'		=> 'content',
		'title'			=> 'content',
	);
	
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'members';
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static $formLangPrefix = 'status_';
	
	/**
	 * @brief	Comment Class
	 */
	public static $commentClass = 'IPS\core\Statuses\Reply';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = FALSE;
	
	/**
	 * @brief	Reputation Type
	 */
	public static $reputationType = 'status_id';
	
		/**
	 * @brief	[Content]	Key for hide reasons
	 */
	public static $hideLogKey = 'status_status';
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @return	void
	 */
	public static function incrementPostCount( \IPS\Node\Model $container = NULL )
	{
		return FALSE;
	}
	
	/**
	 * Get Title
	 *
	 * @return	string
	 */
	public function get_title()
	{
		return strip_tags( $this->mapped('content') );
	}
	
	/**
	 * Get container ID for search index
	 *
	 * @return	int
	 */
	public function searchIndexContainer()
	{
		return $this->member_id;
	}
	
	/**
	 * Get mapped value
	 *
	 * @param	string	$key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( $key )
	{
		if ( $key === 'title' )
		{
			/* We do not want the content (which is mapped to title in $databaseColumnMap) to be added into core_search_index.index_title as the field is only varchar(255) */
			return mb_substr( $this->title, 0, 85 );
		}
		
		return parent::mapped( $key );
	}

	/**
	 * Can a given member create a status update?
	 *
	 * @param \IPS\Member $member
	 * @return bool
	 */
	public static function canCreateFromCreateMenu( \IPS\Member $member = null)
	{
		if ( !$member )
		{
			$member = \IPS\Member::loggedIn();
		}

		/* Can we access the module? */
		if ( !parent::canCreate( $member, NULL, FALSE ) )
		{
			return FALSE;
		}

		/* We have to be logged in */
		if ( !$member->member_id )
		{
			return FALSE;
		}
		
		if ( !$member->pp_setting_count_comments or !\IPS\Settings::i()->profile_comments )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Can a given member reply to a status update?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreateReply( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		/* Can we access the module? */
		if ( !parent::canCreate( $member, $container, $showError ) )
		{
			return FALSE;
		}

		/* We have to be logged in */
		if ( !$member->member_id )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Can a given member create this type of content?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		$profileOwner = isset( \IPS\Request::i()->id ) ? \IPS\Member::load( \IPS\Request::i()->id ) : \IPS\Member::loggedIn();
		$error	= 'no_module_permission';
		$return	= TRUE;

		/* Can we access the module? */
		if ( !parent::canCreate( $member, $container, $showError ) )
		{			
			$return = FALSE;
		}
		
		/* We have to be logged in */
		if ( !$member->member_id )
		{
			$return = FALSE;
		}
		
		/* And not restricted */
		if ( $member->members_bitoptions['bw_no_status_update'] or $member->group['gbw_no_status_update'] )
		{
			$return = FALSE;
		}
		
		/* Is the user being ignored */
		if ( $profileOwner->isIgnoring( $member, 'messages' ) )
		{
			$return = FALSE;
		}

		if ( !$profileOwner->pp_setting_count_comments )
		{	
			$return = FALSE;
		}
		
		if ( !\IPS\Settings::i()->profile_comments )
		{
			$return = FALSE;
		}

		/* Return */
		if ( $showError and !$return )
		{
			\IPS\Output::i()->error( $error, '2C322/1', 403 );
		}
		return $return;
	}

	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item				The current item if editing or NULL if creating
	 * @param	\IPS\Node\Model|NULL	$container			Container (e.g. forum), if appropriate
	 * @param	bool 					$fromCreateMenu		false to deactivate the minimize feature
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL , $fromCreateMenu=FALSE)
	{
		$formElements = parent::formElements( $item, $container );
		
		unset( $formElements['title'] );

		if ( $fromCreateMenu )
		{
			$minimize = NULL;
		}
		else
		{
			$member = isset( \IPS\Request::i()->id ) ? \IPS\Member::load( \IPS\Request::i()->id ) : \IPS\Member::loggedIn();

			$minimize = ( $member->member_id != \IPS\Member::loggedIn()->member_id ) ?
				\IPS\Member::loggedIn()->language()->addToStack( static::$formLangPrefix . '_update_placeholder_other', FALSE, array( 'sprintf' => array( $member->name ) ) ) :
				static::$formLangPrefix . '_update_placeholder';
		}

		$formElements['status_content'] = new \IPS\Helpers\Form\Editor( static::$formLangPrefix . 'content' . ( $fromCreateMenu ? '_ajax' : '' ), ( $item ) ? $item->content : NULL, TRUE, array(
				'app'			=> static::$application,
				'key'			=> 'Members',
				'autoSaveKey' 	=> 'status',
				'minimize'		=> $minimize,
			), '\IPS\Helpers\Form::floodCheck' );
		
		return $formElements;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array					$values				Values from form
	 * @param	\IPS\Node\Model|NULL	$container			Container (e.g. forum), if appropriate
	 * @param	bool					$sendNotification	TRUE to automatically send new content notifications (useful for items that may be uploaded in bulk)
	 * @return	\IPS\Content\Item
	 */
	public static function createFromForm( $values, \IPS\Node\Model $container = NULL, $sendNotification = TRUE )
	{
		/* Create */
		$status = parent::createFromForm( $values, $container, $sendNotification );
		\IPS\File::claimAttachments( 'status', $status->id );
		
		/* Return */
		return $status;
	}
	
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( $values )
	{
		parent::processForm( $values );
		
		/* Work out which profile we are posting too, but only if we are NOT coming from the Create menu or the status updates widget (neither of which allow posting to another profile */
		/* @todo The dependency on \IPS\Request here needs to be moved to the controller */
		$this->member_id = ( isset( \IPS\Request::i()->id ) AND ( isset( \IPS\Request::i()->controller ) AND \IPS\Request::i()->controller != 'ajaxcreate' ) ) ? \IPS\Request::i()->id : \IPS\Member::loggedIn()->member_id;
		
		if ( !$this->_new )
		{
			$oldContent = $this->content;
		}
		$this->content	= $values['status_content'];
		if ( !$this->_new )
		{
			$this->sendAfterEditNotifications( $oldContent );
		}		
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		if( $this->_url === NULL )
		{
			$member = \IPS\Member::load( $this->member_id );
			$this->_url = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$member->member_id}&status={$this->id}&type=status", 'front', 'profile', array( $member->members_seo_name ) );
		}
		
		$return = $this->_url;
		if ( $action )
		{
			if ( $action == 'edit' )
			{
				$return = $return->setQueryString( 'do', 'editStatus' );
			}
			else
			{
				$return = $return->setQueryString( array( 'do' => $action, 'type' => 'status' ) );
			}

			if ( $action == 'moderate' AND \IPS\Request::i()->controller == 'feed' )
			{
				$return = $return->setQueryString( '_fromFeed', 1 );
			}
		}
	
		return $return;
	}
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications()
	{
		parent::sendNotifications();

		/* Notify when somebody comments on my profile */
		if( $this->author()->member_id != $this->member_id )	
		{
			$notification = new \IPS\Notification( \IPS\Application::load( 'core' ), 'profile_comment', $this, array( $this ) );
			$member = \IPS\Member::load( $this->member_id );
			$notification->recipients->attach( $member );
			
			$notification->send();
		}

		/* Notify when a follower posts a status update */
		if ( $this->author()->member_id == $this->member_id )
		{
			$notification	= new \IPS\Notification( \IPS\Application::load( 'core' ), 'new_status', $this, array( $this ) );
			$followers		= \IPS\Member::load( $this->member_id )->followers( 3, array( 'immediate' ), $this->mapped('date'), NULL );

			if( count( $followers ) )
			{
				foreach( $followers AS $follower )
				{
					$notification->recipients->attach( \IPS\Member::load( $follower['follow_member_id'] ) );
				}
			}
			
			$notification->send();
		}
	}
	
	/**
	 * Should new items be moderated?
	 *
	 * @param	\IPS\Member		$member		The member posting
	 * @param	\IPS\Node\Model	$container	The container
	 * @return	bool
	 */
	public static function moderateNewItems( \IPS\Member $member, \IPS\Node\Model $container = NULL )
	{
		if ( $member->moderateNewContent() or \IPS\Settings::i()->profile_comment_approval )
		{
			return TRUE;
		}

		return parent::moderateNewItems( $member, $container );
	}
	
	/**
	 * Should new comments be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewComments( \IPS\Member $member )
	{
		return ( $member->moderateNewContent() or \IPS\Settings::i()->profile_comment_approval );
	}
	
	/**
	 * Can delete?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
	
		/* Profile owner should always be able to delete */
		if ( $member->member_id == $this->member_id )
		{
			return TRUE;
		}
		
		return parent::canDelete( $member );
	}
	
	/**
	 * Get comments to display
	 *
	 * @return	array
	 */
	public function commentsForDisplay()
	{
		/* Init */
		$limit = static::getCommentsPerPage();
		$numberOfComments = $this->commentCount();
		
		/* If there is more than 3 comments, we want to display the LAST 3 on page 1, the 3 before that on page 2, etc */
		if ( $numberOfComments >= $limit )
		{
			/* Work out what page we're looking at, but only if we are actually paginating through comments */
			/* @future we should probably remove the dependancy on \IPS\Request::i()->page here, as the status may be display on a page that isn't necessarily the profile (ex: My Activity) */
			$page = ( isset( \IPS\Request::i()->page ) AND isset( \IPS\Request::i()->status ) ) ? intval( \IPS\Request::i()->page ) : 1;
			if( $page < 1 )
			{
				$page = 1;
			}
			
			/* Start by making the offset to be the $numberOfComments - ( 3 * $page )
				For example, if there's 5 comments, and we're on page 1, the offset will be 2 */
			$offset = $numberOfComments - ( $limit * $page );
			
			/* However, if we've got to the start, set teh offset to 0 and adjust the limit to get whatever is left */
			if ( $offset < 0 )
			{
				$limit += $offset;
				$offset = 0;
			}
			
			/* Is limit still in the negatives? Just reset it. */
			if ( $limit < 0 )
			{
				$limit = static::getCommentsPerPage();
			}
		}
		
		/* If there's less than 3 comments, just display those */
		else
		{
			$offset = 0;
		}
		
		/* Return */
		$return = parent::comments( $limit, $offset );
		
		/* If the limit is 1, comments() returns an object, but we want an array */
		return ( $limit == 1 ) ? array( $return ) : $return;
	}
	
	/**
	 * Get template for content tables
	 *
	 * @return	callable
	 */
	public static function contentTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'statuses', 'core' ), 'statusContentRows' );
	}
	
	/**
	 * Get HTML for search result display
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$authorData		Basic data about the author. Only includes columns returned by \IPS\Member::columnsForPhoto()
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @param	array		$reputationData	Array of people who have given reputation and the reputation they gave
	 * @param	int|NULL	$reviewRating	If this is a review, the rating
	 * @param	bool		$iPostedIn		If the user has posted in the item
	 * @param	string		$view			'expanded' or 'condensed'
	 * @param	bool		$asItem	Displaying results as items?
	 * @param	bool		$canIgnoreComments	Can ignore comments in the result stream? Activity stream can, but search results cannot.
	 * @return	string
	 */
	public static function searchResult( array $indexData, array $authorData, array $itemData, array $containerData = NULL, array $reputationData, $reviewRating, $iPostedIn, $view, $asItem, $canIgnoreComments=FALSE )
	{			
		$status = static::constructFromData( $itemData );
		$status->reputation = $reputationData;
		
		$profileOwner = isset( $itemData['profile'] ) ? $itemData['profile'] : NULL;
		$profileOwnerData = $profileOwner ?: $authorData;
		$status->_url = \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$profileOwnerData['member_id']}&status={$itemData['status_id']}&type=status", 'front', 'profile', array( $profileOwnerData['members_seo_name'] ) );
		
		return \IPS\Theme::i()->getTemplate( 'statuses', 'core', 'front' )->statusContainer( $status, $authorData, $profileOwner, $view == 'condensed' );
	}

	/**
	 * Get number of comments to show per page
	 *
	 * @return int
	 */
	public static function getCommentsPerPage()
	{
		return static::$commentsPerPage;
	}
	
	/**
	 * Share this content using a share service
	 *
	 * @param	string	$className	The share service classname
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	protected function autoShare( $className )
	{
		$className::publish( html_entity_decode( trim( strip_tags( $this->content ) ), ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8' ) );
	}
}