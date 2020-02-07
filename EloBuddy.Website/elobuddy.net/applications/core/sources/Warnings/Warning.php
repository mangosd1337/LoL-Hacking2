<?php
/**
 * @brief		Warning Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Warnings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Warning Model
 */
class _Warning extends \IPS\Content\Item
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_members_warn_logs';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'wl_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;

	/**
	 * @brief	Title
	 */
	public static $title = 'warning';
	
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
	 * Load record based on a URL
	 *
	 * @param	\IPS\Http\Url	$url	URL to load from
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function loadFromUrl( \IPS\Http\Url $url )
	{
		return static::load( $url->queryString['w'] );
	}
	
	/**
	 * Undo warning
	 *
	 * @return	void
	 */
	public function undo()
	{
		$member = \IPS\Member::load( $this->member );
		
		/* Take off the points */
		if ( ( !$this->expire_date or $this->expire_date == -1 ) or $this->expire_date > time() )
		{
			$member->warn_level -= $this->points;
			if ( $member->warn_level < 0 )
			{
				$member->warn_level = 0;
			}
		}
		
		/* Undo the actions */
		foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $w => $m )
		{
			if ( $this->$w and ( $this->$w < time() or $this->$w == -1 ) )
			{
				try
				{
					$latest = \IPS\Db::i()->select( '*', 'core_members_warn_logs', array( "wl_member=? AND wl_{$w}<>0 AND wl_id !=?", $member->member_id, $this->id ), 'wl_date DESC' )->first();
					$member->$m = $latest[ 'wl_' . $w ];
				}
				catch ( \UnderflowException $e )
				{
					$member->$m = 0;
				}
			}
		}
		
		/* Save */
		$member->save();
	}
	
	/**
	 * Delete warning
	 *
	 * @return	void
	 */
	public function delete()
	{		
		/* Unaknowledged Warnings? */
		$member = \IPS\Member::load( $this->member );
		
		if ( \IPS\Settings::i()->warnings_acknowledge )
		{
			$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( "wl_member=? AND wl_id<>? AND wl_acknowledged=0", $member->member_id, $this->id ) )->first();
			$member->members_bitoptions['unacknowledged_warnings'] = (bool) $count;
		}
		else
		{
			$member->members_bitoptions['unacknowledged_warnings'] = FALSE;
		}
		$member->save();
		
		/* Delete */
		parent::delete();
	}
	
	/**
	 * Unclaim attachments
	 *
	 * @return	void
	 */
	protected function unclaimAttachments()
	{
		\IPS\File::unclaimAttachments( 'core_Modcp', $this->id, NULL, 'member' );
		\IPS\File::unclaimAttachments( 'core_Modcp', $this->id, NULL, 'mod' );
	}
	
	/**
	 * Get Content for Warning
	 *
	 * @return	\IPS\Content|NULL
	 */
	public function content()
	{		
		if ( $this->content_app and $this->content_module )
		{
			if ( $this->content_app === 'core' and $this->content_module === 'messaging' )
			{
				try
				{
					if ( $this->content_id2 )
					{
						return \IPS\core\Messenger\Message::load( $this->content_id2 );
					}
					else
					{
						return \IPS\core\Messenger\Conversation::load( $this->content_id2 );
					}
				}
				catch ( \OutOfRangeException $e )
				{
					return NULL;
				}
			}
			else
			{
				$extensions = \IPS\Application::load( $this->content_app )->extensions( 'core', 'ContentRouter' );
				foreach ( $extensions as $ext )
				{
					foreach ( $ext->classes as $class )
					{
						if ( $class::$module == $this->content_module )
						{
							try
							{
								return $class::load( $this->content_id1 );
							}
							catch ( \OutOfRangeException $e )
							{
								return NULL;
							}
						}
						elseif ( $commentClass = $class::$commentClass and $class::$module . '-comment' == $this->content_module )
						{
							try
							{
								return $commentClass::load( $this->content_id2 );
							}
							catch ( \OutOfRangeException $e )
							{
								return NULL;
							}
						}
						if ( isset( $class::$reviewClass ) AND $reviewClass = $class::$reviewClass and $class::$module . '-review' == $this->content_module )
						{
							try
							{
								return $reviewClass::load( $this->content_id2 );
							}
							catch ( \OutOfRangeException $e )
							{
								return NULL;
							}
						}
					}
				}
			}
		}
		return NULL;
	}

		
	/* !\IPS\Content\Item */
	
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
		
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'date'		=> 'date',
		'author'	=> 'moderator',
	);
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static $formLangPrefix = 'warn_';
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	int						$container	Container (e.g. forum) ID, if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL )
	{
		/* Get the reasons */
		$reasons = array();
        $roots = \IPS\core\Warnings\Reason::roots();
		foreach ( $roots as $reason )
		{
			$reasons[ $reason->_id ] = $reason->_title;
		}
		if ( \IPS\Member::loggedIn()->modPermission('warnings_enable_other') )
		{
			$reasons['other'] = 'core_warn_reason_other';
		}

		/* Build the form */
		$elements[] = new \IPS\Helpers\Form\Select( 'warn_reason', NULL, TRUE, array( 'options' => $reasons ) );
		$elements[] = new \IPS\Helpers\Form\Number( 'warn_points', 0, FALSE, array( 'valueToggles' => array( 'warn_remove' ), 'disabled' => ( !$roots[1]->points_override ) ) );
		$elements[] = new \IPS\Helpers\Form\Date( 'warn_remove', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'never' ), NULL, NULL, NULL, 'warn_remove' );
		$elements[] = new \IPS\Helpers\Form\Editor( 'warn_member_note', NULL, FALSE, array( 'app' => 'core', 'key' => 'Modcp', 'autoSaveKey' => "warn-member-" . \IPS\Request::i()->id, 'attachIds' => ( $item === NULL ? NULL : array( $item->id, NULL, 'member' ) ), 'minimize' => 'warn_member_note_placeholder' ) );
		$elements[] = new \IPS\Helpers\Form\Editor( 'warn_mod_note', NULL, FALSE, array( 'app' => 'core', 'key' => 'Modcp', 'autoSaveKey' => "warn-mod-" . \IPS\Request::i()->id, 'attachIds' => ( $item === NULL ? NULL : array( $item->id, NULL, 'mod' ) ), 'minimize' => 'warn_mod_note_placeholder' ) );
		$elements[] = new \IPS\Helpers\Form\CheckboxSet( 'warn_punishment', array(), FALSE, array(
			'options' 	=> array( 'mq' => 'warn_mq', 'rpa' => 'warn_rpa', 'suspend' => 'warn_suspend' ),
			'toggles'	=> array( 'mq' => array( 'form_warn_mq' ), 'rpa' => array( 'form_warn_rpa' ), 'suspend' => array( 'form_warn_suspend' ) ),
		) );
		$elements[] = new \IPS\Helpers\Form\Date( 'warn_mq', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), function( $val )
			{
				if( $val )
				{
					$now = new \IPS\DateTime;
	
					if( $val !== -1 and $val->getTimestamp() < $now->getTimestamp() )
					{
						throw new \DomainException( 'error_date_not_future' );
					}
				}
				
			}, \IPS\Member::loggedIn()->language()->addToStack('until'), NULL, NULL, 'warn_mq' );
		
		$elements[] = new \IPS\Helpers\Form\Date( 'warn_rpa', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), function( $val )
			{
				if( $val )
				{
					$now = new \IPS\DateTime;
					
					if( $val !== -1 and $val->getTimestamp() < $now->getTimestamp() )
					{
						throw new \DomainException( 'error_date_not_future' );
					}
				}
				
			}, \IPS\Member::loggedIn()->language()->addToStack('until'), NULL, NULL, 'warn_rpa' );
		
		$elements[] = new \IPS\Helpers\Form\Date( 'warn_suspend', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), function( $val )
			{
				if( $val )
				{
					$now = new \IPS\DateTime;
					
					if( $val !== -1 and $val->getTimestamp() < $now->getTimestamp() )
					{
						throw new \DomainException( 'error_date_not_future' );
					}
				}
				
			}, \IPS\Member::loggedIn()->language()->addToStack('until'), NULL, NULL, 'warn_suspend' );
		
		/* Return */
		return $elements;
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( $values )
	{		
		$this->member = \IPS\Request::i()->member;
		
		foreach( $values['warn_punishment'] AS $p )
		{
			if ( $values['warn_' . $p ] === NULL )
			{
				\IPS\Output::i()->error( 'no_warning_action_time', '1C150/2', 403, '' );
			}
		}
		
		/* Work out points and expiry date */
		$this->expire_date = NULL;
		$this->points = $values['warn_points'];
		if ( is_numeric( $values['warn_reason'] ) )
		{
			$reason = \IPS\core\Warnings\Reason::load( $values['warn_reason'] );
			if ( !$reason->points_override )
			{
				$this->points = $reason->points;
			}
			if ( !$reason->remove_override )
			{
				if ( $reason->remove_override == -1 )
				{
					$this->points = -1;
				}
				else
				{
					if ( $reason->remove != -1 )
					{
						$expire = \IPS\DateTime::create();
						if ( $reason->remove_unit == 'h' )
						{
							$expire->add( new \DateInterval( "PT{$reason->remove}H" ) );
						}
						else
						{
							$expire->add( new \DateInterval( "P{$reason->remove}D" ) );
						}
						$this->expire_date = $expire->getTimestamp();
					}
					else
					{
						$this->expire_date = -1;
					}
				}
			}
			else
			{
				if ( $values['warn_remove'] instanceof \IPS\DateTime )
				{
					$this->expire_date = $values['warn_remove']->getTimestamp();
				}
				else
				{
					$this->expire_date = $values['warn_remove'];
				}
			}
			
			$this->reason = $values['warn_reason'];
		}
		else
		{
			$this->reason = 0;
			
			if ( $values['warn_remove'] instanceof \IPS\DateTime )
			{
				$this->expire_date = $values['warn_remove']->getTimestamp();
			}
			else
			{
				$this->expire_date = $values['warn_remove'];
			}
		}
		if ( !$this->points )
		{
			$this->expire_date = -1;
		}
				
		/* If we can't override the action, change it back */
		try
		{
			$action = \IPS\Db::i()->select( '*', 'core_members_warn_actions', array( 'wa_points<=?', ( \IPS\Member::load( $this->member )->warn_level + $this->points ) ), 'wa_points DESC', 1 )->first();
			if ( !$action['wa_override'] )
			{
				foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
				{
					if ( $action[ 'wa_' . $k ] == -1 )
					{
						$values[ 'warn_' . $k ] = -1;
					}
					elseif ( $action[ 'wa_' . $k ] )
					{
						$values[ 'warn_' . $k ] = \IPS\DateTime::create()->add( new \DateInterval( $action[ 'wa_' . $k . '_unit' ] == 'h' ? "PT{$action[ 'wa_' . $k ]}H" : "P{$action[ 'wa_' . $k ]}D" ) );
					}
					else
					{
						$values[ 'warn_' . $k ] = NULL;
					}
				}
			}
		}
		catch ( \UnderflowException $e )
		{
			if ( !\IPS\Member::loggedIn()->modPermission('warning_custom_noaction') )
			{
				foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
				{
					$values[ 'warn_' . $k ] = NULL;
				}
			}
		}
		
		/* Set notes */
		$this->note_member = $values['warn_member_note'];
		$this->note_mods = $values['warn_mod_note'];
		
		/* Set acknowledged */
		$this->acknowledged = !\IPS\Settings::i()->warnings_acknowledge;
		
		/* Construct referrer */
		$ref = \IPS\Request::i()->ref ? json_decode( base64_decode( \IPS\Request::i()->ref ), TRUE ) : NULL;
		$this->content_app	= isset( $ref['app'] ) ? $ref['app'] : NULL;
		$this->content_module	= isset( $ref['module'] ) ? $ref['module'] : NULL;
		$this->content_id1	= isset( $ref['id_1'] ) ? $ref['id_1'] : NULL;
		$this->content_id2	= isset( $ref['id_2'] ) ? $ref['id_2'] : NULL;
		
		/* Work out the timeframes for the penalities */
		if ( count( $values['warn_punishment'] ) )
		{
			foreach ( array( 'mq', 'rpa', 'suspend' ) as $f )
			{
				if ( !in_array( $f, $values['warn_punishment'] ) )
				{
					continue;
				}
				
				if ( $values[ 'warn_' . $f ] instanceof \IPS\DateTime )
				{
					$difference = \IPS\DateTime::create()->diff( $values[ 'warn_' . $f ] );
					$period = 'P';
					foreach ( array( 'y' => 'Y', 'm' => 'M', 'd' => 'D' ) as $k => $v )
					{
						if ( $difference->$k )
						{
							$period .= $difference->$k . $v;
						}
					}
					$time = '';
					foreach ( array( 'h' => 'H', 'i' => 'M', 's' => 'S' ) as $k => $v )
					{
						if ( $difference->$k )
						{
							$time .= $difference->$k . $v;
						}
					}
					if ( $time )
					{
						$period .= 'T' . $time;
					}
					
					$this->$f = $period;
				}
				else
				{
					$this->$f = $values[ 'warn_' . $f ];
				}
			}
		}

		parent::processBeforeCreate( $values );
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( $comment, $values )
	{
		\IPS\File::claimAttachments( "warn-member-{$this->member}", $this->id, NULL, 'member' );
		\IPS\File::claimAttachments( "warn-mod-{$this->member}", $this->id, NULL, 'mod' );
		
		$member = \IPS\Member::load( $this->member );
		if ( $this->points )
		{
			$member->warn_level += $this->points;
		}
		foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $k => $v )
		{
			if ( $this->$k )
			{
				if ( $this->$k != -1 )
				{
					$member->$v = \IPS\DateTime::create()->add( new \DateInterval( $this->$k ) )->getTimestamp();
				}
				else
				{
					$member->$v = $this->$k;
				}
			}
		}
		$member->members_bitoptions['unacknowledged_warnings'] = (bool) \IPS\Settings::i()->warnings_acknowledge;
		$member->save();
		
		parent::processAfterCreate( $comment, $values );
	}
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications()
	{
		$moderators = array( 'm' => array(), 'g' => array() );
		foreach ( \IPS\Db::i()->select( '*', 'core_moderators' ) as $mod )
		{
			$canView = FALSE;
			
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
			}
			
			if ( $canView === FALSE )
			{
				$perms = json_decode( $mod['perms'], TRUE );
				
				if ( isset( $perms['mod_see_warn'] ) AND $perms['mod_see_warn'] === TRUE )
				{
					$canView = TRUE;
				}
			}
			
			if ( $canView === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}
		
		$notification = new \IPS\Notification( \IPS\Application::load('core'), 'warning_mods', $this, array( $this ) );
		
		foreach ( \IPS\Db::i()->select( '*', 'core_members', '(' . ( count( $moderators['m'] ) ? \IPS\Db::i()->in( 'member_id', $moderators['m'] ) . ' OR ' : '' ) . \IPS\Db::i()->in( 'member_group_id', $moderators['g'] ) . ' OR ' . \IPS\Db::i()->findInSet( 'mgroup_others', $moderators['g'] ) . ' ) AND member_id!=' . \IPS\Member::loggedIn()->member_id ) as $member )
		{
			$notification->recipients->attach( \IPS\Member::constructFromData( $member ) );
		}

		$notification->send();

		\IPS\Email::buildFromTemplate( 'core', 'warning', array( $this ), \IPS\Email::TYPE_TRANSACTIONAL )->send( \IPS\Member::load( $this->member ) );
	}

	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=system&controller=warnings&id={$this->member}&w={$this->id}", 'front', 'warn_view', \IPS\Member::load( $this->member )->members_seo_name );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
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
			return \IPS\Member::loggedIn()->language()->addToStack( "core_warn_reason_{$this->reason}" );
		}
		return parent::mapped( $key );
	}
	
	/* !Permissions */
	
	/**
	 * Can give warning?
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param	bool		$showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 * @note	If we can't see warnings, we can't issue them either
	 */
	public static function canCreate( \IPS\Member $member, \IPS\Node\Model $container=NULL, $showError=FALSE )
	{
		$return = $member->modPermission('mod_can_warn') && $member->modPermission('mod_see_warn');
		if ( !$return and $showError )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C150/1', 403, '' );
		}
		
		return $return;
	}
	
	/**
	 * Does a member have permission to access?
	 *
	 * @param	\IPS\Member	$member	The member to check for
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return $member->modPermission('mod_see_warn') or $this->member === $member->member_id;
	}
	
	/**
	 * Does a member have permission to view the details of the warning?
	 *
	 * @param	\IPS\Member	$member	The member to check for
	 * @return	bool
	 */
	public function canViewDetails( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
				
		if ( $member->modPermission('mod_see_warn') )
		{
			return TRUE;
		}
		
		if ( \IPS\Settings::i()->warn_show_own and $this->member === $member->member_id )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Can acknowledge?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canAcknowledge( $member=NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		return $this->member === $member->member_id;
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
		return ( $member->modPermission('mod_revoke_warn') and $member->modPermission('mod_see_warn') );
	}
	
	/* !\IPS\Helpers\Table */
	
	public $tableHoverUrl = TRUE;
	
	/**
	 * Icon for table view
	 *
	 * @return	array
	 */
	public function tableIcon()
	{
		return \IPS\Theme::i()->getTemplate( 'modcp', 'core', 'front' )->warningRowPoints( $this->points );
	}
		
	/**
	 * Method to get description for table view
	 *
	 * @return	string
	 */
	public function tableDescription()
	{
		return $this->note_mods;
	}
}