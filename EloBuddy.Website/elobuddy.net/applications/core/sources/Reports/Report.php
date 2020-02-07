<?php
/**
 * @brief		Report Index Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Reports;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Report Model
 */
class _Report extends \IPS\Content\Item implements \IPS\Content\ReadMarkers
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'core_rc_index';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = '';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Database ID Fields
	 */
	protected static $databaseIdFields = array( 'content_id' );
	
	/* !\IPS\Content\Item */
	
	/**
	 * @brief	Application
	 */
	public static $application = 'core';
	
	/**
	 * @brief	Application
	 */
	public static $module = 'modcp';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'date'			=> 'first_report_date',
		'author'		=> 'first_report_by',
		'author_count'	=> 'num_reports',
		'title'			=> 'title',
		'last_comment'	=> 'last_updated',
		'num_comments'	=> 'num_comments',
	);
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static $formLangPrefix = 'report_';
	
	/**
	 * @brief	Comment Class
	 */
	public static $commentClass = 'IPS\core\Reports\Comment';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'report';
	
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
	 * Get mapped value
	 *
	 * @param	string	$key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( $key )
	{
		if ( $key === 'title' )
		{
			$class = $this->_data['class'];

			if( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
			{
				$class = $class::$itemClass;
			}

			return mb_substr( strip_tags( parent::mapped( 'title' ) ), 0, 85 );
		}
		
		return parent::mapped( $key );
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
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=core&module=modcp&tab=reports&action=view&id={$this->id}", 'front', 'modcp_report' );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'action', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/* !\IPS\Helpers\Table */
		
	/**
	 * Method to add extra data to objects in this
	 * class when displaying in a table view
	 *
	 * @param	array	$rows	Array of objects of this class
	 * @return	void
	 */
	public static function tableGetRows( $rows )
	{
		$types = array();
		
		foreach ( $rows as $row )
		{
			$types[ $row->class ][ $row->content_id ] = $row;
		}
						
		foreach ( $types as $class => $objects )
		{
			if ( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
			{
				$itemClass = $class::$itemClass;
				$databaseTable = $class::$databaseTable;
				$itemDatabaseTable = $itemClass::$databaseTable;
				$itemTitleField = $itemClass::$databaseColumnMap['title']; # Strange PHP issue can cause this to be lost when added to the query below.
				
				foreach( \IPS\Db::i()->select(
					"{$databaseTable}.{$class::$databasePrefix}{$class::$databaseColumnId}, {$databaseTable}.{$class::$databasePrefix}{$class::$databaseColumnMap['item']} AS itemId, {$itemClass::$databaseTable}.{$itemClass::$databasePrefix}{$itemTitleField} AS title",
					$databaseTable,
					\IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnId, array_keys( $objects ) )
					)->join(
						$itemDatabaseTable,
						"{$itemDatabaseTable}.{$itemClass::$databasePrefix}{$itemClass::$databaseColumnId}={$databaseTable}.{$class::$databasePrefix}{$class::$databaseColumnMap['item']}"
					)->setKeyField( $class::$databasePrefix . $class::$databaseColumnId ) as $k => $data
				)
				{
					$objects[ $k ]->_data = array_merge( $objects[ $k ]->_data, $data );
				}
			}
			elseif ( in_array( 'IPS\Content\Item', class_parents( $class ) ) )
			{
				foreach( \IPS\Db::i()->select(
					"{$class::$databasePrefix}{$class::$databaseColumnId}, {$class::$databasePrefix}{$class::$databaseColumnMap['title']} AS title",
					$class::$databaseTable,
					\IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnId, array_keys( $objects ) )
					)->setKeyField( $class::$databasePrefix . $class::$databaseColumnId ) as $k => $data
				)
				{
					$objects[ $k ]->_data = array_merge( $objects[ $k ]->_data, $data );
				}
			}
		}
	}
	
	/**
	 * Method to get description for table view
	 *
	 * @return	string
	 */
	public function tableDescription()
	{
		$className = $this->class;
		try
		{
			$reportedContent = $className::load( $this->content_id );

			if( $reportedContent instanceof \IPS\Content\Comment )
			{
				$container = ( $reportedContent->item()->containerWrapper() !== NULL ) ? $reportedContent->item()->container() : NULL;
			}
			else
			{
				$container = ( $reportedContent->containerWrapper() !== NULL ) ? $reportedContent->container() : NULL;
			}
		}
		catch ( \OutOfRangeException $ex )
		{
			$container = NULL;
		}

		return \IPS\Theme::i()->getTemplate( 'tables', 'core', 'front' )->icon( $className, $container );
	}

	/**
	 * Get content table states
	 *
	 * @return string
	 */
	public function tableStates()
	{
		$states = explode( ' ', parent::tableStates() );
		$states[] = "report_status_" . $this->status;

		return implode( ' ', $states );
	}
	
	/**
	 * Stats for table view
	 *
	 * @return	array
	 */
	public function stats( $includeFirstCommentInCommentCount=TRUE )
	{
		return array_merge( parent::stats( $includeFirstCommentInCommentCount ), array( 'num_reports' => $this->num_reports ) );
	}
	
	/**
	 * Icon for table view
	 *
	 * @return	array
	 */
	public function tableIcon()
	{
		return \IPS\Theme::i()->getTemplate( 'modcp', 'core', 'front' )->reportToggle( $this );
	}

	/**
	 * Gets a special class for the row
	 *
	 * @return	string
	 */
	public function tableClass()
	{
		switch ( $this->status )
		{
			case 2:
				return 'warning';
			break;
			case 1:
				return 'new';
			break;
		}

		return '';
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
		if ( mb_substr( $action, 0, -1 ) === 'report_status_' )
		{
			$this->status = mb_substr( $action, -1 );
			$this->save();
		}
		else
		{
			return parent::modAction( $action, $member, $reason );
		}
	}
	
	/**
	 * Return any custom multimod actions this content item class supports
	 *
	 * @return	array
	 */
	public function customMultimodActions()
	{
		return array_diff( array( 'report_status_1', 'report_status_2', 'report_status_3', ), array( 'report_status_' . $this->status ) );
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
				'groupaction'	=> 'report_status',
				'icon'			=> 'flag',
				'grouplabel'	=> 'mark_as',
				'action'		=> array(
					array(
						'action'	=> 'report_status_1',
						'icon'		=> 'flag',
						'label'		=> 'report_status_1'
					),
					array(
						'action'	=> 'report_status_2',
						'icon'		=> 'exclamation-triangle',
						'label'		=> 'report_status_2'
					),
					array(
						'action'	=> 'report_status_3',
						'icon'		=> 'check-circle',
						'label'		=> 'report_status_3'
					)
				)
			)
		);
	}
	
	/* !\IPS\core\Reports\report */
	
	/**
	 * Get reports
	 *
	 * @return	array
	 */
	public function reports()
	{
		return iterator_to_array( \IPS\Db::i()->select( '*', 'core_rc_reports', array( 'rid=?', $this->id ), 'date_reported' ) );
	}
	
	/**
	 * Rebuild
	 *
	 * @return	void
	 */
	public function rebuild()
	{
		$numReports = \IPS\Db::i()->select( 'COUNT(*)', 'core_rc_reports', array( 'rid=?', $this->id ) )->first();
		if ( !$numReports )
		{
			$this->delete();
		}
		$this->num_reports = $numReports;
		
		$numComments = \IPS\Db::i()->select( 'COUNT(*)', 'core_rc_comments', array( 'rid=?', $this->id ) )->first();
		$this->num_comments = $numComments;
		
		$this->save();
	}
	
	/**
	 * Delete Report
	 *
	 * @return	void
	 */
	public function delete()
	{
		parent::delete();
	
		\IPS\Db::i()->delete( 'core_rc_reports', array( 'rid=?', $this->id ) );
	}

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public static function getTableFilters()
	{
		return array(
			'read', 'unread', 'report_status_1', 'report_status_2', 'report_status_3'
		);
	}
}