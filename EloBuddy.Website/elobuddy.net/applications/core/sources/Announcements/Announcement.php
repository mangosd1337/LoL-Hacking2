<?php
/**
 * @brief		Announcement model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Announcements;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Announcements Model
 */
class _Announcement extends \IPS\Content\Item
{
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_announcements';
	
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'announce_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Database ID Fields
	 */
	protected static $databaseIdFields = array( 'id' );
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
		
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
			'title'			=> 'title',
			'date'			=> 'start',
			'author'		=> 'member_id',
			'views'			=> 'views',
			'content'		=> 'content'
	);
	
	/**
	 * @brief	Title
	 */
	public static $title = 'announcement';
	
	/**
	 * @brief	Title
	 */
	public static $icon = 'bullhorn';

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_seo_title()
	{
		if( !$this->_data['seo_title'] )
		{
			$this->seo_title	= \IPS\Http\Url::seoTitle( $this->title );
			$this->save();
		}

		return $this->_data['seo_title'] ?: \IPS\Http\Url::seoTitle( $this->title );
	}

	/**
	 * Display Form
	 *
	 * @return	\IPS\Helpers\Form
	 */
	public static function form( $announcement )
	{
		/* Build the form */
		$form = new \IPS\Helpers\Form( NULL, 'save' );
		$form->class = 'ipsForm_vertical';
		
		$form->add( new \IPS\Helpers\Form\Text( 'announce_title', ( $announcement ) ? $announcement->title : NULL, TRUE, array( 'maxLength' => 255 ) ) );
		$form->add( new \IPS\Helpers\Form\Date( 'announce_start', ( $announcement ) ? $announcement->start : new \IPS\DateTime ) );
		$form->add( new \IPS\Helpers\Form\Date( 'announce_end', ( $announcement ) ? $announcement->end : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'indefinitely' ) ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'announce_content', ( $announcement ) ? $announcement->content : NULL, TRUE, array( 'app' => 'core', 'key' => 'Announcement', 'autoSaveKey' => ( $announcement ? 'editAnnouncement__' . $announcement->id : 'createAnnouncement' ), 'attachIds' => $announcement ? array( $announcement->id, NULL, 'announcement' ) : NULL ) ) );
		
		/* Apps */
		$apps = array();
		
		foreach( \IPS\Application::applications() as $key => $data )
		{
			if ( $key != 'core' )
			{
				$apps[ $key ] = $data->_title;
			}
		}
		$apps['core'] = 'announce_other_areas';
		
		$toggles = array();
		$formFields = array();
		
		foreach ( \IPS\Application::allExtensions( 'core', 'Announcements', TRUE, 'core' ) as $key => $extension )
		{
			$app = mb_substr( $key, 0, mb_strpos( $key, '_' ) );

			if( method_exists( $extension, 'getSettingField' ) )
			{
				/* Grab our fields and add to the form */
				$field	= $extension->getSettingField( $announcement );
				
				$toggles[ $app ][] = $field->name;
				$formFields[] = $field;
			}
		}
		
		$form->add( new \IPS\Helpers\Form\Select( 'announce_app', ( $announcement ) ? $announcement->app : '*', TRUE, array( 'options' => $apps,'toggles' => $toggles, 'unlimited' => "*", 'unlimitedLang' => "everywhere" ) ) );

		foreach( $formFields as $field )
		{
			$form->add( $field );
		}
	
		return $form;
	}
	
	/**
	 * Create from form
	 *
	 * @param	array	$values	Values from form
	 * @param	\IPS\core\Announcements\Announcement|NULL $current	Current announcement
	 * @return	\IPS\core\Announcements\Announcement
	 */
	public static function _createFromForm( $values, $current )
	{
		if( $current )
		{
			$obj = static::load( $current->id );
		}
		else
		{
			$obj = new static;
			$obj->member_id = \IPS\Member::loggedIn()->member_id;
		}

		$obj->title		= $values['announce_title'];
		$obj->seo_title = \IPS\Http\Url::seoTitle( $values['announce_title'] );
		$obj->content	= $values['announce_content'];
		$obj->start		= $values['announce_start'] ? $values['announce_start']->getTimestamp() : time();
		$obj->end		= $values['announce_end'] ? $values['announce_end']->getTimestamp() : 0;
		$obj->app		= $values['announce_app'] ? $values['announce_app'] : "*";

        if( in_array( $obj->app, array_keys(\IPS\Application::applications() ) ) )
        {
            foreach ( \IPS\Application::load( $obj->app )->extensions( 'core', 'Announcements' ) as $key => $extension )
            {
                if( method_exists( $extension, 'getSettingField' ) )
                {
                    $field	= $extension->getSettingField( array() );
                    $obj->ids = is_array( $values[ $field->name ] ) ? implode( ",", array_keys( $values[ $field->name ] ) ) : $values[ $field->name ];
                    $obj->location = mb_substr( $key, mb_strpos( $key, '_' ) );
                }
            }
        }
        else
        {
            $obj->location = "*";
            $obj->ids = NULL;
        }
		
		$obj->save();
		
		if( !$current )
		{
			\IPS\File::claimAttachments( 'createAnnouncement', $obj->id, NULL, 'announcement' );
		}
		
		return $obj;
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
			if( $action )
			{
				$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=announcements&id={$this->id}", 'front', 'modcp_announcements' );
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'action', $action );
			}
			else
			{
				$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=system&controller=announcement&id={$this->id}", 'front', 'announcement', $this->seo_title );
			}
		}
	
		return $this->_url[ $_key ];
	}

	/**
	 * Get owner
	 *
	 * @return	\IPS\Member
	 */
	public function owner()
	{
		return \IPS\Member::load( $this->member_id );
	}
	
	/**
	 * Unclaim attachments
	 *
	 * @return	void
	 */
	protected function unclaimAttachments()
	{
		\IPS\File::unclaimAttachments( 'core_Admin', $this->id, NULL, 'announcement' );
	}

	/**
	 * Check Moderator Permission
	 *
	 * @param	string						$type		'edit', 'hide', 'unhide', 'delete', etc.
	 * @param	\IPS\Member|NULL			$member		The member to check for or NULL for the currently logged in member
	 * @param	\IPS\Node\Model|NULL		$container	The container
	 * @return	bool
	 */
	public static function modPermission( $type, \IPS\Member $member = NULL, \IPS\Node\Model $container = NULL )
	{
		if( in_array( $type, array( 'move', 'merge', 'lock', 'unlock', 'feature', 'unfeature', 'pin', 'unpin' ) ) )
		{
			return FALSE;
		}

		if( $type == 'hide' OR $type == 'unhide' OR $type == 'active' OR $type == 'inactive' )
		{
			return TRUE;
		}

		return parent::modPermission( $type, $member, $container );
	}

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters()
	{
		return array(
			'active', 'inactive'
		);
	}

	/**
	 * Get content table states
	 *
	 * @return string
	 */
	public function tableStates()
	{
		$states = explode( ' ', parent::tableStates() );

		if( !$this->active )
		{
			$states[]	= "inactive";
		}
		else
		{
			$states[]	= "active";
		}

		return implode( ' ', $states );
	}

	/**
	 * Do Moderator Action
	 *
	 * @param	string				$action	The action
	 * @param	\IPS\Member|NULL	$member	The member doing the action (NULL for currently logged in member)
	 * @param	string|NULL			$reason	Reason (for hides)
	 * @return	void
	 * @throws	\OutOfRangeException|\InvalidArgumentException|\RuntimeException
	 */
	public function modAction( $action, \IPS\Member $member = NULL, $reason = NULL )
	{
		if( static::modPermission( $action, $member ) )
		{
			if( $action == 'active' )
			{
				\IPS\Session::i()->modLog( 'modlog__action_announceactive', array( static::$title => TRUE, $this->url()->__toString() => FALSE, $this->mapped('title') => FALSE ), $this );

				$this->active	= 1;
				$this->save();

				\IPS\Widget::deleteCaches( 'announcements', 'core' );

				return;
			}

			if( $action == 'inactive' )
			{
				\IPS\Session::i()->modLog( 'modlog__action_announceinactive', array( static::$title => TRUE, $this->url()->__toString() => FALSE, $this->mapped('title') => FALSE ), $this );

				$this->active	= 0;
				$this->save();

				\IPS\Widget::deleteCaches( 'announcements', 'core' );

				return;
			}
		}

		return parent::modAction( $action, $member, $reason );
	}

	/**
	 * Return any custom multimod actions this content item class supports
	 *
	 * @return	array
	 */
	public function customMultimodActions()
	{
		if( !$this->active )
		{
			return array( "active" );
		}
		else
		{
			return array( "inactive" );
		}
	}

	/**
	 * Return any available custom multimod actions this content item class supports
	 *
	 * @note	Return in format of array( array( 'action' => ..., 'icon' => ..., 'language' => ... ) )
	 * @return	array
	 */
	public static function availableCustomMultimodActions()
	{
		return array(
			array(
				'groupaction'	=> 'active',
				'icon'			=> 'eye',
				'grouplabel'	=> 'announce_active_status',
				'action'		=> array(
					array(
						'action'	=> 'active',
						'icon'		=> 'eye',
						'label'		=> 'announce_mark_active'
					),
					array(
						'action'	=> 'inactive',
						'icon'		=> 'eye',
						'label'		=> 'announce_mark_inactive'
					)
				)
			)
		);
	}
}