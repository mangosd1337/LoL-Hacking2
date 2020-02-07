<?php
/**
 * @brief		Notifications Table Helper
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 July 2014
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
 * Notifications Table Helper
 */
class _Table extends \IPS\Helpers\Table\Table
{
	/**
	 * @brief	Sort options
	 */
	public $sortOptions = array( 'updated_time' );
	
	/**
	 * @brief	Rows
	 */
	protected static $rows = null;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url	Base URL
	 * @return	void
	 */
	public function __construct( \IPS\Http\Url $url=NULL )
	{
		/* Init */	
		parent::__construct( $url );

		$this->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'system', 'core', 'front' ), 'notificationsTable' );
		$this->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'system', 'core', 'front' ), 'notificationsRows' );
	}

	/**
	 * Set member
	 *
	 * @param	\IPS\Member	$member		The member to filter by
	 * @return	void
	 */
	public function setMember( \IPS\Member $member )
	{
		$this->where[] = array( 'member=?', $member->member_id );
	}

	/**
	 * Get rows
	 *
	 * @param	array	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( $advancedSearchValues=NULL )
	{
		if ( static::$rows === NULL )
		{
			/* Check sortBy */
			$this->sortBy = in_array( $this->sortBy, $this->sortOptions ) ? $this->sortBy : 'updated_time';
	
			/* What are we sorting by? */
			$sortBy = $this->sortBy . ' ' . ( mb_strtolower( $this->sortDirection ) == 'asc' ? 'asc' : 'desc' );
	
			/* Specify filter in where clause */
			$where = isset( $this->where ) ? is_array( $this->where ) ? $this->where : array( $this->where ) : array();

			/* Limit applications */
			$where[] = array( "notification_app IN('" . implode( "','", array_keys( \IPS\Application::enabledApplications() ) ) . "')" );
			
			if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
			{
				$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
			}
	
			/* Get Count */
			$count = \IPS\Db::i()->select( 'COUNT(*) as cnt', 'core_notifications', $where )->first();
	  		$this->pages = ceil( $count / $this->limit );
	
			/* Get results */
			$it = \IPS\Db::i()->select( '*', 'core_notifications', $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ) );
			$rows = iterator_to_array( $it );
	
			foreach( $rows as $index => $row )
			{
				try
				{
					$notification   = \IPS\Notification\Inline::constructFromData( $row );
					static::$rows[ $index ]	= array( 'notification' => $notification, 'data' => $notification->getData() );
				}
				catch ( \LogicException $e ) { }
			}
		}
		
		/* Return */
		return static::$rows;
	}

	/**
	 * Return the table headers
	 *
	 * @param	array|NULL	$advancedSearchValues	Advanced search values
	 * @return	array
	 */
	public function getHeaders( $advancedSearchValues )
	{
		return array();
	}
}