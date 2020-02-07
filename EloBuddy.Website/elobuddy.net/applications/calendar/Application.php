<?php
/**
 * @brief		Calendar Application Class
 * @author		<a href=''>Invision Power Services, Inc.</a>
 * @copyright	(c) 2013 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		18 Dec 2013
 * @version		
 */
 
namespace IPS\calendar;

/**
 * Core Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* Requesting iCal/RSS Subscription, but guests are required to login */
		if( \IPS\Request::i()->module == 'calendar' and \IPS\Request::i()->controller == 'view' and in_array( \IPS\Request::i()->do, array( 'rss', 'download' ) ) )
		{
			/* Validate RSS/Download key */
			if( \IPS\Request::i()->member )
			{
				$member = \IPS\Member::load( \IPS\Request::i()->member );
				if( !\IPS\Login::compareHashes( md5( ( $member->members_pass_hash ?: $member->email ) . $member->members_pass_salt ), (string) \IPS\Request::i()->key ) )
				{
					\IPS\Output::i()->error( 'node_error', '2L217/1', 404, '' );
				}
			}

			/* Output */
			if( \IPS\Request::i()->do == 'download' )
			{
				$this->download( \IPS\Request::i()->member ? $member : NULL );
			}

			$this->rss( \IPS\Request::i()->member ? $member : NULL );
		}

		/* Reset first day of week */
		if( \IPS\Settings::i()->ipb_calendar_mon )
		{
			\IPS\Output::i()->jsVars['date_first_day'] = 1;
		}
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'calendar';
	}

	/**
	 * Latest events RSS
	 *
	 * @return	void
	 * @note	There is a hard limit of the most recent 500 events updated
	 */
	public function download( $member=NULL )
	{
		$feed	= new \IPS\calendar\Icalendar\ICSParser;
		$calendar = NULL;

		/* Are we viewing a specific calendar only? */
		if( \IPS\Request::i()->id )
		{
			$calendar = \IPS\calendar\Calendar::load( \IPS\Request::i()->id );

			if ( !$calendar->can( 'view' ) )
			{
				throw new \OutOfRangeException;
			}
		}

		$where = array();

		if( $calendar !== NULL )
		{
			$where[] = array( 'event_calendar_id=?', $calendar->id );
		}

		foreach( \IPS\calendar\Event::getItemsWithPermission( $where, 'event_lastupdated DESC', 500, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, $member ) as $event )
		{
			$feed->addEvent( $event );
		}

		$ics = $feed->buildICalendarFeed( $this->_calendar );

		\IPS\Output::i()->sendHeader( "Content-type: text/calendar; charset=UTF-8" );
		\IPS\Output::i()->sendHeader( 'Content-Disposition: inline; filename=calendarEvents.ics' );

		print $ics;
		exit;
	}

	/**
	 * Latest events RSS
	 *
	 * @return	void
	 */
	public function rss( $member=NULL )
	{
		if( !\IPS\Settings::i()->calendar_rss_feed )
		{
			\IPS\Output::i()->error( 'event_rss_feed_off', '2L182/1', 404, 'event_rss_feed_off_admin' );
		}

		/* Load member */
		if ( $member === NULL )
		{
			$member = \IPS\Member::loggedIn();
		}

		$rssTitle = $member->language()->get('calendar_rss_title');
		$document = \IPS\Xml\Rss::newDocument( \IPS\Http\Url::internal( 'app=calendar&module=calendar&controller=view', 'front', 'calendar' ), $rssTitle, $rssTitle );

		$_today	= \IPS\calendar\Date::getDate();

		$endDate = NULL;

		if( \IPS\Settings::i()->calendar_rss_feed_days > 0 )
		{
			$endDate = $_today->adjust( "+" . \IPS\Settings::i()->calendar_rss_feed_days . " days" );
		}

		foreach ( \IPS\calendar\Event::retrieveEvents( $_today, $endDate, NULL, NULL, FALSE ) as $event )
		{
			$document->addItem( $event->title, $event->url(), $event->content, $event->nextOccurrence( $_today, 'startDate' ), $event->id );
		}

		/* @note application/rss+xml is not a registered IANA mime-type so we need to stick with text/xml for RSS */
		\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Calendar' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}
}