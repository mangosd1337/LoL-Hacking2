<?php
/**
 * @brief		Inline Notification Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		6 Sep 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Notification;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Inline Notification Model
 */
class _Inline extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_notifications';
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->sent_time = time();
		$this->updated_time = time();
	}
	
	/**
	 * Get sent time
	 *
	 * @return	\IPS\DateTime
	 */
	public function get_sent_time()
	{
		return \IPS\DateTime::ts( $this->_data['sent_time'] );
	}
	
	/**
	 * Get updated time
	 *
	 * @return	\IPS\DateTime
	 */
	public function get_updated_time()
	{
		return \IPS\DateTime::ts( $this->_data['updated_time'] );
	}
	
	/**
	 * Get member
	 *
	 * @return	\IPS\Member
	 */
	public function get_member()
	{
		return \IPS\Member::load( $this->_data['member'] );
	}
	
	/**
	 * Set member
	 *
	 * @param	|IPS\Member	$member	The member
	 * @return	void
	 */
	public function set_member( \IPS\Member $member )
	{
		$this->_data['member'] = $member->member_id;
	}
	
	/**
	 * Get member data
	 *
	 * @return	array
	 */
	public function get_member_data()
	{
		return json_decode( $this->_data['member_data'], TRUE );
	}
	
	/**
	 * Set member data
	 *
	 * @param	mixed	$data	Member data
	 * @return	void
	 */
	public function set_member_data( $data )
	{
		$this->_data['member_data'] = $data ? json_encode( $data ) : NULL;
	}
	
	/**
	 * Get item
	 *
	 * @return	object|NULL
	 */
	public function get_item()
	{
		if ( $this->_data['item_class'] and $this->_data['item_id'] )
		{
			try
			{
				$class = $this->_data['item_class'];
				if ( class_exists( $class ) )
				{
					return $class::load( $this->_data['item_id'] );
				}
			}
			catch ( \OutOfRangeException $e )
			{
				return NULL;
			}
		}
		return NULL;
	}
	
	/**
	 * Set item
	 *
	 * @param	object	$item	The item
	 * @return	void
	 */
	public function set_item( $item )
	{
		$idColumn = $item::$databaseColumnId;
		$this->_data['item_class'] = get_class( $item );
		$this->_data['item_id'] = $item->$idColumn;
	}
	
	/**
	 * Get subitem
	 *
	 * @return	object|NULL
	 */
	public function get_item_sub()
	{
		if ( $this->_data['item_sub_class'] and $this->_data['item_sub_id'] )
		{
			try
			{
				$class = $this->_data['item_sub_class'];
				if ( class_exists( $class ) )
				{
					return $class::load( $this->_data['item_sub_id'] );
				}
			}
			catch ( \OutOfRangeException $e )
			{
				return NULL;
			}
		}
		return NULL;
	}
	
	/**
	 * Get application
	 *
	 * @return	\IPS\Application
	 */
	public function get_notification_app()
	{
		return \IPS\Application::load( $this->_data['notification_app'] );
	}
	
	/**
	 * Set application
	 *
	 * @param	mixed	$data	Member data
	 * @return	void
	 */
	public function set_notification_app( \IPS\Application $app )
	{
		$this->_data['notification_app'] = $app->directory;
	}
	
	/**
	 * Get extra data
	 *
	 * @return	array
	 */
	public function get_extra()
	{
		return $this->_data['extra'] ? json_decode( $this->_data['extra'], TRUE ) : array();
	}
	
	/**
	 * Set extra data
	 *
	 * @param	mixed	$data	Member data
	 * @return	void
	 */
	public function set_extra( $data )
	{
		$this->_data['extra'] = $data ? json_encode( $data ) : NULL;
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		$this->member->recountNotifications();
	}
	
	/**
	 * Get data from extension
	 *
	 * @return	array
	 * @throws	\RuntimeException
	 */
	public function getData()
	{
		$method = "parse_{$this->notification_key}";
		
		foreach ( $this->notification_app->extensions( 'core', 'Notifications' ) as $class )
		{
			if ( method_exists( $class, $method ) )
			{
				$return = $class->$method( $this );
				
				if ( !isset( $return['unread'] ) )
				{
					$return['unread'] = !$this->read_time;
				}
				
				return $return;
			}
		}
		throw new \RuntimeException;
	}
}