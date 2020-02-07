<?php
/**
 * @brief		Support Reply Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		9 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Reply Model
 */
class _Reply extends \IPS\Content\Comment implements \IPS\Content\Hideable
{
	const REPLY_MEMBER		= 'm';
	const REPLY_ALTCONTACT	= 'a';
	const REPLY_STAFF		= 's';
	const REPLY_HIDDEN		= 'h';
	const REPLY_EMAIL		= 'e';
	const REPLY_PENDING		= 'p';
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	[Content\Comment]	Item Class
	 */
	public static $itemClass = 'IPS\nexus\Support\Request';
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_support_replies';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'reply_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'item'			=> 'request',
		'author'		=> 'member',
		'content'		=> 'post',
		'date'			=> 'date',
		'ip_address'	=> 'ip_address',
		'hidden'		=> 'hidden'
	);
	
	/**
	 * @brief	[Content\Comment]	Comment Template
	 */
	public static $commentTemplate = array( array( 'support', 'nexus' ), 'replyContainer' );
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'life-ring';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'support_request';
	
	/**
	 * @brief	Rating data
	 */
	public $ratingData;
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		$obj = parent::constructFromData( $data, $updateMultitonStoreIfExists );
		if ( isset( $data['nexus_support_ratings'] ) and $data['nexus_support_ratings']['rating_rating'] )
		{
			$obj->ratingData = $data['nexus_support_ratings'];
		}
		return $obj;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->type = static::REPLY_MEMBER;
	}
	
	/**
	 * Create comment
	 *
	 * @param	\IPS\Content\Item		$item				The content item just created
	 * @param	string					$comment			The comment
	 * @param	bool					$first				Is the first comment?
	 * @param	string					$guestName			If author is a guest, the name to use
	 * @param	bool|NULL				$incrementPostCount	Increment post count? If NULL, will use static::incrementPostCount()
	 * @param	\IPS\Member|NULL		$member				The author of this comment. If NULL, uses currently logged in member.
	 * @param	\IPS\DateTime|NULL		$time				The time
	 * @param	string|NULL				$ipAddress			The IP address or NULL to detect automatically
	 * @param	int|NULL				$hiddenStatus		NULL to set automatically or override: 0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @return	static
	 */
	public static function create( $item, $comment, $first=FALSE, $guestName=NULL, $incrementPostCount=NULL, $member=NULL, \IPS\DateTime $time=NULL, $ipAddress=NULL, $hiddenStatus=NULL )
	{
		$obj = call_user_func_array( 'parent::create', func_get_args() );
		
		if ( $obj->type === static::REPLY_MEMBER and $obj->author()->member_id !== $obj->item()->author()->member_id )
		{
			$obj->type = static::REPLY_ALTCONTACT;
			$obj->save();
			
			$notify = $obj->item()->notify;
			$in = FALSE;
			foreach ( $notify as $n ) 
			{
				if ( $n['type'] === 'm' and $n['value'] == $obj->author()->member_id )
				{
					$in = TRUE;
				}
			}
			if ( !$in )
			{
				$notify[] = array( 'type' => 'm', 'value' => $obj->author()->member_id );
				$obj->item()->notify = $notify;
				$obj->item()->save();
			}
		}
						
		return $obj;
	}
	
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
	 * Joins (when loading comments)
	 *
	 * @param	\IPS\Content\Item	$item			The item
	 * @return	array
	 */
	public static function joins( \IPS\Content\Item $item )
	{
		$return = parent::joins( $item );
		if ( \IPS\Settings::i()->nexus_support_satisfaction )
		{
			$return['nexus_support_ratings'] = array(
				'select'	=> 'nexus_support_ratings.*',
				'from'		=> 'nexus_support_ratings',
				'where'		=> 'rating_reply=reply_id'
			);
		}
		return $return;
	}
	
	/**
	 * Can edit?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canEdit( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can delete?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( $member=NULL )
	{
		return FALSE;
	}
	
	/**
	 * Can split this comment off?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canSplit( $member=NULL )
	{
		return FALSE;
	}
		
	/**
	 * Get author
	 *
	 * @return	\IPS\nexus\Customer
	 */
	public function author()
	{
		if ( $this->member )
		{
			try
			{
				return \IPS\nexus\Customer::load( $this->member );
			}
			catch ( \OutOfRangeException $e ) { }
		}
		return new \IPS\nexus\Customer;
	}
	
	/**
	 * Can see moderation tools
	 *
	 * @note	This is used generally to control if the user has permission to see multi-mod tools. Individual content items may have specific permissions
	 * @param	\IPS\Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @param	\IPS\Node\Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function canSeeMultiModTools( \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		// If we decide to support multimod in future, we need to make sure it either doesn't show, or shows correctly when viewing a staff member's latest replies in the "Performance" section
		return FALSE;
	}
	
	/**
	 * Send if it's pending
	 *
	 * @return	void
	 */
	public function sendPending()
	{
		if ( $this->type === static::REPLY_PENDING )
		{
			$this->type = static::REPLY_STAFF;
			$this->save();
			
			$defaultRecipients = $this->item()->getDefaultRecipients();
			$this->sendCustomerNotifications( $defaultRecipients['to'], $defaultRecipients['cc'], $defaultRecipients['bcc'] );

			$this->sendNotifications();
		}
	}
	
	/**
	 * Send staff notifications
	 *
	 * @return	void
	 */
	public function sendNotifications()
	{
		$staffIds = array_keys( Request::staff() );
		$type = array( 'type=?', 'r' );
		if ( $assignedTo = $this->item()->staff )
		{
			$type[0] .= ' OR ( type=? AND staff_id=? )';
			$type[] = 'a';
			$type[] = $assignedTo->member_id;
		}
		$sentTo = array( $this->member );
		foreach ( \IPS\Db::i()->select( 'staff_id', 'nexus_support_notify', array( $type, array( "departments='*' OR " . \IPS\Db::i()->findInSet( 'departments', array( $this->item()->department->id ) ) ) ) ) as $staffId )
		{
			if ( in_array( $staffId, $staffIds ) )
			{
				/* The department may only be available to specific members OR groups - we need to load an \IPS\Member object here so that we can check both and send the notification as appropriate. */
				$staff				= \IPS\Member::load( $staffId );
				$departmentStaff	= $this->item()->department->staff;
				
				if ( !in_array( $staffId, $sentTo ) and ( $this->item()->department->staff === '*' or count( array_intersect( explode( ',', $departmentStaff ), Department::staffDepartmentPerms( $staff ) ) ) ) )
				{
					$fromEmail = ( $this->item()->department->email ) ? $this->item()->department->email : \IPS\Settings::i()->email_out;
					switch ( \IPS\Settings::i()->nexus_sout_from )
					{
						case 'staff':
							if ( $this->member )
							{
								$fromName = $this->author()->name;
							}
							break;
						case 'dpt':
							$fromName = $email->language->get( 'nexus_department_' . $this->item()->department->_id );
							break;
						default:
							$fromName = \IPS\Settings::i()->nexus_sout_from;
							break;
					}
										
					$email = \IPS\Email::buildFromTemplate( 'nexus', $this->type === static::REPLY_HIDDEN ? 'staffNotifyNote' : 'staffNotifyReply', array( $this ), \IPS\Email::TYPE_LIST )
						->setUnsubscribe( 'nexus', 'unsubscribeStaffNotify' )
						->send( $staff, array(), array(), $fromEmail, $fromName );
					
					$sentTo[] = $staffId;
				}
			}
			else
			{
				\IPS\Db::i()->delete( 'nexus_support_notify', array( 'staff_id=?', $staffId ) );
			}
		}
	}
	
	/**
	 * Send Customer Notifications
	 *
	 * @param	string	$to		Primary "To" email address
	 * @param	array	$cc		Emails to Cc
	 * @param	array	$bcc	Emails to Bcc
	 * @return	void
	 */
	public function sendCustomerNotifications( $to, $cc, $bcc )
	{
		if ( \IPS\Settings::i()->nexus_sout_chrome )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'staffReply', array( $this ), \IPS\Email::TYPE_TRANSACTIONAL );
		}
		else
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'staffReplyNoChrome', array( $this ), \IPS\Email::TYPE_TRANSACTIONAL, FALSE );
		}
		
		$fromEmail = $this->item()->department->email ?: \IPS\Settings::i()->email_out;
		switch ( \IPS\Settings::i()->nexus_sout_from )
		{
			case 'staff':
				$fromName = $this->author()->name;
				break;
			case 'dpt':
				$fromName = $email->language->get( 'nexus_department_' . $this->item()->department->_id );
				break;
			default:
				$fromName = \IPS\Settings::i()->nexus_sout_from;
				break;
		}
		
		$email->send( $to, $cc, $bcc, $fromEmail, $fromName );
	}
	
	/**
	 * Do stuff after creating (abstracted as comments and reviews need to do different things)
	 *
	 * @return	void
	 */
	public function postCreate()
	{
		$item = $this->item();
		
		if ( $this->type === Reply::REPLY_MEMBER or $this->type === Reply::REPLY_ALTCONTACT )
		{
			$item->status = Status::load( TRUE, 'status_default_member' );
		}
		
		return parent::postCreate();
	}
	
	/**
	 * Get ACP URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function acpUrl( $action=NULL )
	{
		$url = \IPS\Http\Url::internal( "app=nexus&module=support&controller=request&id={$this->id}", 'admin' );
		if( $action )
		{
			return $url->setQueryString( 'do', $action . 'Comment' )->setQueryString( 'comment', $this->id );
		}
		else
		{
			$commentPosition = \IPS\Db::i()->select( 'COUNT(*) AS position', static::$databaseTable, array( static::$databasePrefix . static::$databaseColumnMap['item'] . '=? AND ' . static::$databasePrefix . static::$databaseColumnId . '<=?', $this->request, $this->id ), static::$databasePrefix . static::$databaseColumnMap['date'] . ' asc' )->first();
			$page = ceil( $commentPosition / \IPS\nexus\Support\Request::getCommentsPerPage() );
			if ( $page != 1 )
			{
				$url = $url->setQueryString( 'page', $page );
			}
			return $url->setFragment( 'comment-' . $this->id );
		}
	}
}