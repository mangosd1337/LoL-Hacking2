<?php
/**
 * @brief		Calendar Views
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		23 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\modules\front\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Calendar Views
 */
class _view extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Calendar we are viewing
	 */
	protected $_calendar	= NULL;

	/**
	 * @brief	Date object for the current day
	 */
	protected $_today		= NULL;
	
	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* We aren't showing a sidebar in Calendar */
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\calendar\Calendar::addCss();

		/* Show the RSS link */
		if ( \IPS\Settings::i()->calendar_rss_feed )
		{
			$urls = $this->_downloadLinks();
			\IPS\Output::i()->rssFeeds['calendar_rss_title'] = $urls['rss'];
		}

		/* Is there only one calendar? */
		$roots	= \IPS\calendar\Calendar::roots();
		if ( count( $roots ) == 1 AND !isset( \IPS\Request::i()->id ) )
		{
			$roots	= array_shift( $roots );
			$this->_calendar	= \IPS\calendar\Calendar::loadAndCheckPerms( $roots->_id );
		}

		/* Are we viewing a specific calendar only? */
		if( \IPS\Request::i()->id )
		{
			try
			{
				$this->_calendar	= \IPS\calendar\Calendar::loadAndCheckPerms( \IPS\Request::i()->id );
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2L182/2', 404, '' );
			}
		}

		if( $this->_calendar !== NULL AND $this->_calendar->_id )
		{
			\IPS\Output::i()->contextualSearchOptions[ \IPS\Member::loggedIn()->language()->addToStack( 'search_contextual_item', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'calendar' ) ) ) ) ] = array( 'type' => 'calendar_event', 'nodes' => $this->_calendar->_id );
		}

		$this->_today	= \IPS\calendar\Date::getDate();

		/* Get the date jumper - do this first in case we need to redirect */
		$jump		= $this->_jump();

		/* If there is a view requested in the URL, use it */
		if( isset( \IPS\Request::i()->view ) )
		{
			if( method_exists( $this, '_view' . ucwords( \IPS\Request::i()->view ) ) )
			{
				$method	= "_view" . ucwords( \IPS\Request::i()->view );
				$this->$method( $jump );
			}
			else
			{
				$method	= "_view" . ucwords( \IPS\Settings::i()->calendar_default_view );
				$this->$method( $jump );
			}
		}
		/* Otherwise use ACP default preference */
		else
		{
			$method	= "_view" . ucwords( \IPS\Settings::i()->calendar_default_view );
			$this->$method( $jump, iterator_to_array( \IPS\calendar\Event::featured( 4, '_rand' ) ) );
		}

		/* Online User Location */
		if ($this->_calendar)
		{
			\IPS\Session::i()->setLocation( $this->_calendar->url(), array(), 'loc_calendar_viewing_calendar', array( "calendar_calendar_{$this->_calendar->id}" => TRUE ) );
		}
		else
		{
			\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=calendar', 'front', 'view' ), array(), 'loc_calendar_viewing_calendar_all' );
		}
	}
	
	/**
	 * Show month view
	 *
	 * @param	\IPS\Helpers\Form	$jump	Calendar jump
	 * @param	array	$featured	Featured events (only populated for the ACP-specified default view)
	 * @return	void
	 */
	protected function _viewMonth( $jump, $featured=array() )
	{
		/* Get the calendars we can view */
		$calendars	= \IPS\calendar\Calendar::roots();

		/* Get the month data */
		$day		= NULL;

		if( ( !\IPS\Request::i()->y OR \IPS\Request::i()->y == $this->_today->year ) AND ( !\IPS\Request::i()->m OR \IPS\Request::i()->m == $this->_today->mon ) )
		{
			$day	= $this->_today->mday;
		}

		try
		{
			$date		= \IPS\calendar\Date::getDate( \IPS\Request::i()->y ?: NULL, \IPS\Request::i()->m ?: NULL, $day );
		}
		catch( \Exception $e )
		{
			\IPS\Output::i()->error( 'error_bad_date', '3L182/3', 403, '' );
		}

		/* Get birthdays */
		$birthdays	= ( $this->_calendar === NULL OR count( $calendars ) == 1 ) ? $date->getBirthdays( FALSE, TRUE ) : array();

		/* Get the events within this range */
		$events		= \IPS\calendar\Event::retrieveEvents(
			\IPS\calendar\Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ),
			\IPS\calendar\Date::getDate( $date->lastDayOfMonth('year'), $date->lastDayOfMonth('mon'), $date->lastDayOfMonth('mday'), 23, 59, 59 ),
			$this->_calendar
		);

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count($events) === 0 )
		{
			\IPS\Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		/* @see http://community.invisionpower.com/4bugtrack/follow-button-adds-meta-title-and-link-tags-r3209/ */
		$output = \IPS\Theme::i()->getTemplate( 'browse' )->calendarMonth( $calendars, $date, $featured, $birthdays, $events, $this->_today, $this->_calendar, $jump );

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output, 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		else
		{
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_browse.js', 'calendar', 'front' ) );
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('cal_month_title', FALSE, array( 'sprintf' => array( $date->monthName, $date->year ) ) );

			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$calendars,
				$this->_calendar,
				$jump,
				$date,
				$featured,
				$this->_downloadLinks()
			);	
		}		
	}
	
	/**
	 * Show week view
	 *
	 * @param	\IPS\Helpers\Form	$jump	Calendar jump
	 * @param	array	$featured	Featured events (only populated for the ACP-specified default view)
	 * @return	void
	 */
	protected function _viewWeek( $jump, $featured=array() )
	{
		/* Get the calendars we can view */
		$calendars	= \IPS\calendar\Calendar::roots();

		/* Get the month data */
		$week		= \IPS\Request::i()->w ? explode( '-', \IPS\Request::i()->w ) : NULL;
		try
		{
			$date		= \IPS\calendar\Date::getDate( isset( $week[0] ) ? $week[0] : NULL, isset( $week[1] ) ? $week[1] : NULL, isset( $week[2] ) ? $week[2] : NULL );
		}
		catch( \Exception $e )
		{
			\IPS\Output::i()->error( 'error_bad_date', '3L182/4', 403, '' );
		}

		$nextWeek	= $date->adjust( '+1 week' );
		$lastWeek	= $date->adjust( '-1 week' );

		/* Get the days of the week - we do this in PHP to help keep template a little cleaner */
		$days	= array();

		for( $i = 0; $i < 7; $i++ )
		{
			$days[]	= \IPS\calendar\Date::getDate( $date->firstDayOfWeek('year'), $date->firstDayOfWeek('mon'), $date->firstDayOfWeek('mday') )->adjust( $i . ' days' );
		}

		/* Get birthdays */
		$birthdays	= ( $this->_calendar === NULL OR count( $calendars ) == 1 ) ? $date->getBirthdays( FALSE, TRUE ) : array();

		/* Get the events within this range */
		$events		= \IPS\calendar\Event::retrieveEvents(
			\IPS\calendar\Date::getDate( $date->firstDayOfWeek('year'), $date->firstDayOfWeek('mon'), $date->firstDayOfWeek('mday') ),
			\IPS\calendar\Date::getDate( $date->lastDayOfWeek('year'), $date->lastDayOfWeek('mon'), $date->lastDayOfWeek('mday'), 23, 59, 59 ),
			$this->_calendar
		);

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count($events) === 0 )
		{
			\IPS\Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		/* @see http://community.invisionpower.com/4bugtrack/follow-button-adds-meta-title-and-link-tags-r3209/ */
		$output = \IPS\Theme::i()->getTemplate( 'browse' )->calendarWeek( $calendars, $date, $featured, $birthdays, $events, $nextWeek, $lastWeek, $days, $this->_today, $this->_calendar, $jump );

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output, 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		else
		{
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('cal_week_title', FALSE, array( 'sprintf' => array( 
				$date->firstDayOfWeek('monthNameShort'), 
				$date->firstDayOfWeek('mday'),
				$date->firstDayOfWeek('year'),
				$date->lastDayOfWeek('monthNameShort'),
				$date->lastDayOfWeek('mday'),
				$date->lastDayOfWeek('year')
			) ) );

			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$calendars,
				$this->_calendar,
				$jump,
				$date, 
				$featured,
				$this->_downloadLinks()
			);	
		}		
	}
	
	/**
	 * Show day view
	 *
	 * @param	\IPS\Helpers\Form	$jump	Calendar jump
	 * @param	array	$featured	Featured events (only populated for the ACP-specified default view)
	 * @return	void
	 */
	protected function _viewDay( $jump, $featured=array() )
	{
		/* Get the calendars we can view */
		$calendars	= \IPS\calendar\Calendar::roots();

		/* Get the month data */
		try
		{
			$date		= \IPS\calendar\Date::getDate( \IPS\Request::i()->y ?: NULL, \IPS\Request::i()->m ?: NULL, \IPS\Request::i()->d ?: NULL );
		}
		catch( \Exception $e )
		{
			\IPS\Output::i()->error( 'error_bad_date', '3L182/5', 403, '' );
		}

		$tomorrow	= $date->adjust( '+1 day' );
		$yesterday	= $date->adjust( '-1 day' );

		/* Get birthdays */
		if( isset( \IPS\Request::i()->show ) AND \IPS\Request::i()->show == 'birthdays' AND \IPS\Settings::i()->show_bday_calendar )
		{
			$table = new \IPS\Helpers\Table\Db( 'core_members', \IPS\Request::i()->url()->setQueryString('show', 'birthdays'), $date->getBirthdaysWhere( TRUE ) );
			$table->sortBy			= 'name';
			$table->sortDirection	= 'asc';
			$table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'browse', 'calendar', 'front' ), 'birthdaysTable' );
			$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'browse', 'calendar', 'front' ), 'birthday' );

			\IPS\Output::i()->output = $table;
			return;
		}

		$birthdayCount	= ( $this->_calendar === NULL OR count( $calendars ) == 1 ) ? $date->getBirthdays( TRUE, TRUE ) : array();
		$birthdays		= ( $this->_calendar === NULL OR count( $calendars ) == 1 ) ? $date->getBirthdays( TRUE, FALSE ) : array();

		/* Get the events within this range */
		$events		= \IPS\calendar\Event::retrieveEvents( $date, $date, $this->_calendar );

		$dayEvents	= array_fill( 0, 23, array() );
		$dayEvents['allDay']	= array();
		$dayEvents['count']		= 0;

		foreach( $events as $day => $_events )
		{
			foreach( $_events as $type => $event )
			{
				foreach( $event as $_event )
				{
					$dayEvents['count']++;

					if( $_event->all_day )
					{
						$dayEvents['allDay'][ $_event->id ]	= $_event;
					}
					else
					{
						$dayEvents[ $_event->_start_date->hours ][ $_event->id ]	= $_event;
					}
				}
			}
		}

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( $dayEvents['count'] === 0 )
		{
			\IPS\Output::i()->metaTags['robots'] = 'noindex';
		}

		/* Display */
		/* @see http://community.invisionpower.com/4bugtrack/follow-button-adds-meta-title-and-link-tags-r3209/ */
		$output = \IPS\Theme::i()->getTemplate( 'browse' )->calendarDay( $calendars, $date, $featured, $birthdays, $birthdayCount, $dayEvents, $tomorrow, $yesterday, $this->_today, $this->_calendar, $jump );

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output, 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		else
		{
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('cal_month_day', FALSE, array( 'sprintf' => array( $date->monthName, $date->mday, $date->year ) ) );
			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$calendars,
				$this->_calendar,
				$jump,
				$date, 
				$featured,
				$this->_downloadLinks()
			);
		}
	}

	/**
	 * @brief	Stream per page
	 */
	public $streamPerPage	= 50;

	/**
	 * Show stream view
	 *
	 * @param	\IPS\Helpers\Form	$jump	Calendar jump
	 * @param	array	$featured	Featured events (only populated for the ACP-specified default view)
	 * @return	void
	 */
	protected function _viewStream( $jump, $featured=array() )
	{
		/* Get the calendars we can view */
		$calendars	= \IPS\calendar\Calendar::roots();

		/* Get the month data */
		$day		= NULL;

		if( ( !\IPS\Request::i()->y OR \IPS\Request::i()->y == $this->_today->year ) AND ( !\IPS\Request::i()->m OR \IPS\Request::i()->m == $this->_today->mon ) )
		{
			$day	= $this->_today->mday;
		}

		try
		{
			$date		= \IPS\calendar\Date::getDate( \IPS\Request::i()->y ?: NULL, \IPS\Request::i()->m ?: NULL, $day );
		}
		catch( \InvalidArgumentException $e )
		{
			\IPS\Output::i()->error( 'error_bad_date', '3L182/6', 403, '' );
		}

		/* Get the events within this range */
		$events		= \IPS\calendar\Event::retrieveEvents(
			\IPS\calendar\Date::getDate( $date->firstDayOfMonth('year'), $date->firstDayOfMonth('mon'), $date->firstDayOfMonth('mday') ),
			\IPS\calendar\Date::getDate( $date->lastDayOfMonth('year'), $date->lastDayOfMonth('mon'), $date->lastDayOfMonth('mday'), 23, 59, 59 ),
			$this->_calendar,
			NULL,
			FALSE
		);

		/* Pagination */
		$pagination = array(
			'page'  => ( isset( \IPS\Request::i()->page ) ) ? \IPS\Request::i()->page : 1,
			'pages' => ( count( $events ) > 0 ) ? ceil( count( $events ) / $this->streamPerPage ) : 1,
			'limit'	=> $this->streamPerPage
		);

		if( $pagination['page'] < 1 )
		{
			$pagination['page'] = 1;
		}

		/* If there are no events, tell search engines not to index the page but do NOT tell them not to follow links */
		if( count($events) === 0 )
		{
			\IPS\Output::i()->metaTags['robots'] = 'noindex';
		}
		else
		{
			$events = array_slice( $events, ( $pagination['page'] - 1 ) * $this->streamPerPage, $this->streamPerPage );
		}

		/* Display */
		/* @see http://community.invisionpower.com/4bugtrack/follow-button-adds-meta-title-and-link-tags-r3209/ */
		$output = \IPS\Theme::i()->getTemplate( 'browse' )->calendarStream( $calendars, $date, $featured, $events, $this->_calendar, $jump, $pagination );

		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( $output, 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}
		else
		{
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'cal_month_stream_title', FALSE, array( 'sprintf' => array( $date->monthName, $date->year ) ) );
			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->calendarWrapper(
				$output,
				$calendars,
				$this->_calendar,
				$jump,
				$date, 
				$featured,
				$this->_downloadLinks()
			);
		}
	}

	/**
	 * Generate keyed links for RSS/iCal download
	 *
	 * @return	array
	 */
	protected function _downloadLinks()
	{		
		$downloadLinks = array( 'iCalCalendar' => '', 'iCalAll' => \IPS\Http\Url::internal( 'app=calendar&module=calendar&controller=view&do=download', 'front', 'calendar_icaldownload' )->csrf(), 'rss' => \IPS\Http\Url::internal( 'app=calendar&module=calendar&controller=view&do=rss', 'front', 'calendar_rss' ) );

		if( $this->_calendar )
		{
			$downloadLinks['iCalCalendar'] = \IPS\Http\Url::internal( 'app=calendar&module=calendar&controller=view&id=' . $this->_calendar->id . '&do=download', 'front', 'calendar_calicaldownload', $this->_calendar->title_seo )->csrf();
		}

		if ( \IPS\Member::loggedIn()->member_id )
		{
			$key = md5( ( \IPS\Member::loggedIn()->members_pass_hash ?: \IPS\Member::loggedIn()->email ) . \IPS\Member::loggedIn()->members_pass_salt );

			if( $this->_calendar )
			{
				$downloadLinks['iCalCalendar'] = $downloadLinks['iCalCalendar']->setQueryString( array( 'member' => \IPS\Member::loggedIn()->member_id , 'key' => $key ) );
			}
			$downloadLinks['iCalAll'] = $downloadLinks['iCalAll']->setQueryString( array( 'member' => \IPS\Member::loggedIn()->member_id , 'key' => $key ) );
			$downloadLinks['rss'] = $downloadLinks['rss']->setQueryString( array( 'member' => \IPS\Member::loggedIn()->member_id , 'key' => $key ) );
		}

		return $downloadLinks;
	}

	/**
	 * Return jump form and redirect if appropriate
	 *
	 * @return	void
	 */
	protected function _jump()
	{
		/* Build the form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Date( 'jump_to', $this->_today, TRUE, array(), NULL, NULL, NULL, 'jump_to' ) );

		if( $values = $form->values() )
		{
			if( \IPS\Request::i()->goto )
			{
				$dateToGoTo = \IPS\DateTime::create();
			}
			else
			{
				$dateToGoTo = $values['jump_to'];
			}
			
			if ( $this->_calendar )
			{
				$url = \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=day&id={$this->_calendar->_id}&y={$dateToGoTo->format('Y')}&m={$dateToGoTo->format('m')}&d={$dateToGoTo->format('j')}", 'front', 'calendar_calday', $this->_calendar->title_seo );
			}
			else
			{
				$url = \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=view&view=day&y={$dateToGoTo->format('Y')}&m={$dateToGoTo->format('m')}&d={$dateToGoTo->format('j')}", 'front', 'calendar_day' );
			}
			
			\IPS\Output::i()->redirect( $url );
		}

		return $form;
	}
}