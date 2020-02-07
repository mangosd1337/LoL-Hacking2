<?php
/**
 * @brief		Calendar Events API
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		8 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Calendar Events API
 */
class _events extends \IPS\Content\Api\ItemController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\calendar\Event';
	
	/**
	 * GET /calendar/events
	 * Get list of events
	 *
	 * @apiparam	string	calendars		Comma-delimited list of calendar IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only events started by those members are returned
	 * @apiparam	int		locked			If 1, only events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only events which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured		If 1, only events which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy			What to sort by. Can be 'date' for creation date, 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\calendar\Event>
	 */
	public function GETindex()
	{
		/* Where clause */
		$where = array();
				
		/* Return */
		return $this->_list( $where, 'calendars' );
	}
	
	/**
	 * GET /calendar/events/{id}
	 * View information about a specific event
	 *
	 * @param		int		$id			ID Number
	 * @throws		1F294/1	INVALID_ID	The event ID does not exist
	 * @return		\IPS\calendar\Event
	 */
	public function GETitem( $id )
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L296/1', 404 );
		}
	}
	
	/**
	 * POST /calendar/events
	 * Create an event
	 *
	 * @reqapiparam	int					calendar		The ID number of the calendar the event should be created in
	 * @reqapiparam	int					author			The ID number of the member creating the event (0 for guest)
	 * @reqapiparam	string				title			The event title
	 * @reqapiparam	string				description		The description as HTML (e.g. "<p>This is an event.</p>")
	 * @reqapiparam	datetime			start			The event start date/time
	 * @apiparam	datetime			end				The event end date/time
	 * @apiparam	string				recurrence		If this event recurs, the ICS recurrence definition
	 * @apiparam	bool				rsvp			If this event accepts RSVPs
	 * @apiparam	int					rsvpLimit		The number of RSVPs the event is limited to
	 * @apiparam	\IPS\GeoLocation	location		The location where the event is taking place
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date			The date/time that should be used for the event/post post date. If not provided, will use the current date/time
	 * @apiparam	string				ip_address		The IP address that should be stored for the event/post. If not provided, will use the IP address from the API request
	 * @apiparam	int					locked			1/0 indicating if the event should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					featured		1/0 indicating if the event should be featured
	 * @throws		1L296/6				NO_CALENDAR		The calendar ID does not exist
	 * @throws		1L296/7				NO_AUTHOR		The author ID does not exist
	 * @throws		1L296/8				NO_TITLE		No title was supplied
	 * @throws		1L296/9				NO_DESC			No description was supplied
	 * @throws		1L296/A				INVALID_START	The start date is invalid
	 * @throws		1L296/B				INVALID_END		The end date is invalid
	 * @return		\IPS\calendar\Event
	 */
	public function POSTindex()
	{
		/* Get calendar */
		try
		{
			$calendar = \IPS\calendar\Calendar::load( \IPS\Request::i()->calendar );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'NO_CALENDAR', '1L296/6', 400 );
		}
		
		/* Get author */
		if ( \IPS\Request::i()->author )
		{
			$author = \IPS\Member::load( \IPS\Request::i()->author );
			if ( !$author->member_id )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1L296/7', 400 );
			}
		}
		else
		{
			$author = new \IPS\Member;
		}
		
		/* Check we have a title and a description */
		if ( !\IPS\Request::i()->title )
		{
			throw new \IPS\Api\Exception( 'NO_TITLE', '1L296/8', 400 );
		}
		if ( !\IPS\Request::i()->description )
		{
			throw new \IPS\Api\Exception( 'NO_DESC', '1L296/9', 400 );
		}
		
		/* Validate dates */
		try
		{
			new \IPS\DateTime( \IPS\Request::i()->start );
		}
		catch ( \Exception $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_START', '1L296/A', 400 );
		}
		if ( isset( \IPS\Request::i()->end ) )
		{
			try
			{
				new \IPS\DateTime( \IPS\Request::i()->end );
			}
			catch ( \Exception $e )
			{
				throw new \IPS\Api\Exception( 'INVALID_END', '1L296/B', 400 );
			}
		}
		
		/* Do it */
		return new \IPS\Api\Response( 201, $this->_create( $calendar, $author )->apiOutput() );
	}
	
	/**
	 * POST /calendar/events/{id}
	 * Edit an event
	 *
	 * @reqapiparam	int					calendar		The ID number of the calendar the event should be created in
	 * @reqapiparam	int					author			The ID number of the member creating the event (0 for guest)
	 * @reqapiparam	string				title			The event title
	 * @reqapiparam	string				description		The description as HTML (e.g. "<p>This is an event.</p>")
	 * @reqapiparam	datetime			start			The event start date/time
	 * @apiparam	datetime			end				The event end date/time
	 * @apiparam	string				recurrence		If this event recurs, the ICS recurrence definition
	 * @apiparam	bool				rsvp			If this event accepts RSVPs
	 * @apiparam	int					rsvpLimit		The number of RSVPs the event is limited to
	 * @apiparam	\IPS\GeoLocation	location		The location where the event is taking place
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date			The date/time that should be used for the event/post post date. If not provided, will use the current date/time
	 * @apiparam	string				ip_address		The IP address that should be stored for the event/post. If not provided, will use the IP address from the API request
	 * @apiparam	int					locked			1/0 indicating if the event should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					featured		1/0 indicating if the event should be featured
	 * @throws		1L296/I				INVALID_ID		The event ID is invalid
	 * @throws		1L296/D				NO_CALENDAR		The calendar ID does not exist
	 * @throws		1L296/E				NO_AUTHOR		The author ID does not exist
	 * @throws		1L296/G				INVALID_START	The start date is invalid
	 * @throws		1L296/H				INVALID_END		The end date is invalid
	 * @return		\IPS\calendar\Event
	 */
	public function POSTitem( $id )
	{
		try
		{
			$event = \IPS\calendar\Event::load( $id );
			
			/* New calendar */
			if ( isset( \IPS\Request::i()->calendar ) and \IPS\Request::i()->calendar != $event->forum_id )
			{
				try
				{
					$newCalendar = \IPS\calendar\Calendar::load( \IPS\Request::i()->calendar );
					$event->move( $newCalendar );
				}
				catch ( \OutOfRangeException $e )
				{
					throw new \IPS\Api\Exception( 'NO_CALENDAR', '1L296/D', 400 );
				}
			}
			
			/* New author */
			if ( isset( \IPS\Request::i()->author ) )
			{				
				try
				{
					$member = \IPS\Member::load( \IPS\Request::i()->author );
					if ( !$member->member_id )
					{
						throw new \OutOfRangeException;
					}
					
					$event->changeAuthor( $member );
				}
				catch ( \OutOfRangeException $e )
				{
					throw new \IPS\Api\Exception( 'NO_AUTHOR', '1L296/E', 400 );
				}
			}
			
			/* Validate dates */
			if ( isset( \IPS\Request::i()->start ) )
			{
				try
				{
					new \IPS\DateTime( \IPS\Request::i()->start );
				}
				catch ( \Exception $e )
				{
					throw new \IPS\Api\Exception( 'INVALID_START', '1L296/G', 400 );
				}
			}
			if ( isset( \IPS\Request::i()->end ) )
			{
				try
				{
					new \IPS\DateTime( \IPS\Request::i()->end );
				}
				catch ( \Exception $e )
				{
					throw new \IPS\Api\Exception( 'INVALID_END', '1L296/H', 400 );
				}
			}
			
			/* Everything else */
			$this->_createOrUpdate( $event );
			
			/* Save and return */
			$event->save();
			return new \IPS\Api\Response( 200, $event->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '1L296/D', 404 );
		}
	}
	
	/**
	 * GET /calendar/events/{id}/comments
	 * Get comments on an event
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @throws		2L296/2	INVALID_ID	The event ID does not exist
	 * @return		\IPS\Api\PaginatedResponse<IPS\calendar\Event\Comment>
	 */
	public function GETitem_comments( $id )
	{
		try
		{
			return $this->_comments( $id, 'IPS\calendar\Event\Comment' );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L296/2', 404 );
		}
	}
	
	/**
	 * GET /calendar/events/{id}/reviews
	 * Get reviews on an event
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @throws		2L296/3	INVALID_ID	The event ID does not exist
	 * @return		\IPS\Api\PaginatedResponse<IPS\calendar\Event\Review>
	 */
	public function GETitem_reviews( $id )
	{
		try
		{
			return $this->_comments( $id, 'IPS\calendar\Event\Review' );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L296/3', 404 );
		}
	}
	
	/**
	 * GET /calendar/events/{id}/rsvps
	 * Get RSVPs on an event
	 *
	 * @param		int				$id				ID Number
	 * @throws		2L296/3			INVALID_ID		The event ID does not exist
	 * @return		array
	 * @apiresponse	[\IPS\Member]	attending		Members that have confirmed they are attending the event
	 * @apiresponse	[\IPS\Member]	notAttending	Members that have confirmed they are not attending the event
	 * @apiresponse	[\IPS\Member]	maybeAttending	Members that have said they may attend the event
	 */
	public function GETitem_rsvps( $id )
	{
		try
		{
			$event = \IPS\calendar\Event::load( $id );
			$attendees = $event->attendees();
			return new \IPS\Api\Response( 200, array(
				'attending'			=> array_values( array_map( function( $member ) {
					return $member->apiOutput();
				}, $attendees[1] ) ),
				'notAttending'		=> array_values( array_map( function( $member ) {
					return $member->apiOutput();
				}, $attendees[0] ) ),
				'maybeAttending'	=> array_values( array_map( function( $member ) {
					return $member->apiOutput();
				}, $attendees[2] ) ),
			) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L296/4', 404 );
		}
	}
	
	/**
	 * PUT /calendar/events/{id}/rsvps/{member_id}
	 * RSVP a member to an event
	 *
	 * @reqapiparam	int				response		0 = Not attending; 1 = attending; 2 = maybe attending
	 * @param		int				$id				Event ID NUmber
	 * @param		int				$memberId		Member ID NUmber
	 * @throws		2L296/J			INVALID_ID		The event ID does not exist
	 * @return		void
	 */
	public function PUTitem_rsvps( $id, $memberId )
	{
		if ( !isset( \IPS\Request::i()->response ) or !in_array( (int) \IPS\Request::i()->response, range( 0, 2 ) ) )
		{
			throw new \IPS\Api\Exception( 'INVALID_RESPONSE', '1L296/L', 400 );
		}
		
		try
		{
			$event = \IPS\calendar\Event::load( $id );
			
			try
			{
				$member = \IPS\Member::load( $memberId );
				if ( !$member->member_id )
				{
					throw new \OutOfRangeException;
				}
			}
			catch ( \OutOfRangeException $e )
			{
				throw new \IPS\Api\Exception( 'INVALID_MEMBER', '2L296/K', 404 );
			}
			
			\IPS\Db::i()->delete( 'calendar_event_rsvp', array( 'rsvp_event_id=? AND rsvp_member_id=?', $event->id, $member->member_id ) );
			
			\IPS\Db::i()->insert( 'calendar_event_rsvp', array(
				'rsvp_event_id'		=> $event->id,
				'rsvp_member_id'	=> $member->member_id,
				'rsvp_date'			=> time(),
				'rsvp_response'		=> (int) \IPS\Request::i()->response
			) );
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_EVENT', '2L296/J', 404 );
		}
	}
	
	/**
	 * DELETE /calendar/events/{id}/rsvps/{member_id}
	 * Remove a member from RSVP list
	 *
	 * @param		int		$id				Event ID NUmber
	 * @param		int		$memberId		Member ID NUmber
	 * @return		void
	 */
	public function DELETEitem_rsvps( $id, $memberId )
	{
		\IPS\Db::i()->delete( 'calendar_event_rsvp', array( 'rsvp_event_id=? AND rsvp_member_id=?', intval( $id ), intval( $memberId ) ) );
		return new \IPS\Api\Response( 200, NULL );
	}
	
	/**
	 * Create or update event
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @return	\IPS\Content\Item
	 */
	protected function _createOrUpdate( \IPS\Content\Item $item )
	{
		/* Start/End date */
		$startDate = new \IPS\DateTime( \IPS\Request::i()->start );
		$item->start_date = $startDate->format( 'Y-m-d H:i' );
		$item->end_date = NULL;
		if ( isset( \IPS\Request::i()->end ) )
		{
			$endDate = new \IPS\DateTime( \IPS\Request::i()->end );
			$item->end_date = (int) ( $startDate->format('H:i') == '00:00' and $endDate->format('H:i') == '00:00' );
		}
		else
		{
			$item->all_day = 1;
		}
		
		/* Recurrence */
		if ( isset( \IPS\Request::i()->recurrence ) )
		{
			$item->recurring = \IPS\Request::i()->recurrence;
		}
		
		/* Description */
		$item->content = \IPS\Request::i()->description;
		
		/* RSVP */
		if ( isset( \IPS\Request::i()->rsvp ) )
		{
			$item->rsvp = intval( \IPS\Request::i()->rsvp );
			if ( $item->rsvp and isset( \IPS\Request::i()->rsvpLimit ) and \IPS\Request::i()->rsvpLimit )
			{
				$item->rsvp_limit = \IPS\Request::i()->rsvpLimit;
			}
			else
			{
				$item->rsvp_limit = -1;
			}
		}
		
		/* Location */
		if ( isset( \IPS\Request::i()->location ) )
		{
			if ( \IPS\Request::i()->location )
			{
				$location = \IPS\GeoLocation::buildFromJson( json_encode( \IPS\Request::i()->location ) );
				if ( !$location->lat or !$location->long )
				{
					try
					{
						$location->getLatLong();
					}
					catch ( \Exception $e ) {}
				}
				$item->location = json_encode( $location );
			}
		}
		
		/* Pass up */
		return parent::_createOrUpdate( $item );
	}
		
	/**
	 * DELETE /calendar/events/{id}
	 * Delete a event
	 *
	 * @param		int		$id			ID Number
	 * @throws		2L296/5	INVALID_ID	The event ID does not exist
	 * @return		void
	 */
	public function DELETEitem( $id )
	{
		try
		{
			\IPS\calendar\Event::load( $id )->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L296/5', 404 );
		}
	}
}