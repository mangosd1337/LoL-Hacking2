<?php
/**
 * @brief		iCalendar ICS Parser
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		19 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\Icalendar;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * iCalendar ICS Parser
 * @todo	[Future] Tags we do not support but may want to look into supporting: EXDATE, EXRULE, RDATE
 */
class _ICSParser
{
	/**
	 * Parse an RRULE into an array of recurrence data we can use
	 *
	 * @param	string		$rrule		The ICS RRULE value
	 * @param	string		$timezone	Timezone to treat recurring end date as
	 * @return	array
	 * @todo	[Future] We do not support HOURLY, MINUTELY or SECONDLY "FREQ" values
	 * @todo	[Future] We do not support the properties BYSETPOS, BYYEARDAY, BYMONTH, BYHOUR, BYMINUTE, BYSECOND, BYWEEKNO, WKST
	 * @todo	[Future] We do not support complex BYDAY values, such as 1TU or -1FR
	 * @todo	[Future] We do not support multiple values for BYMONTHDAY, nor do we support negative values
	 * @throws	\InvalidArgumentException
	 * @note	Some implementations separate with : and some with ; so we have to support both
	 */
	public static function parseRrule( $rrule, $timezone=NULL )
	{
		$rule		= array_map( 'trim', explode( ';', str_replace( ':', ';', $rrule ) ) );
		$dayNames	= array();
		$repeatData	= array(
			'event_repeat'				=> FALSE,		/* Enable/disable checkbox */
			'event_repeats'				=> NULL,		/* Daily, weekly, monthly, yearly */
			'event_repeat_freq'			=> NULL,		/* Repeat every 1 day, 2 days, 3 days, etc. */
			'repeat_end_occurrences'	=> NULL,		/* Ends after x occurrences (if this and end_date are both empty, that means repeat never ends) */
			'repeat_end_date'			=> NULL,		/* Ends on x date (which is separate from the event end date - e.g. jan 9 2014 3pm to jan 10 2014 3pm, repeat annually until jan 9 2019) */
		);

		foreach( \IPS\calendar\Date::getDayNames() as $day )
		{
			$repeatData['repeat_freq_on_' . $day['ical'] ]	= NULL;		/* If repeating weekly, this is the days of the week as checkboxes (e.g. repeat every wed, fri and sat) */
			$dayNames[] = $day['ical'];
		}

		foreach( $rule as $ruleData )
		{
			$_ruleData	= explode( '=', $ruleData );

			switch( $_ruleData[0] )
			{
				default:
					throw new \InvalidArgumentException( $_ruleData[0] );
				break;

				case 'FREQ':
					$frequency	= mb_strtolower( $_ruleData[1] );

					if( in_array( $frequency, array( 'daily', 'weekly', 'monthly', 'yearly' ) ) )
					{
						$repeatData['event_repeats']	= $frequency;
						$repeatData['event_repeat']		= TRUE;
					}
					else
					{
						/* We don't support less than daily*/
						throw new \InvalidArgumentException( 'FREQ' );
					}
				break;

				case 'BYDAY':
					$days		= explode( ',', $_ruleData[1] );

					foreach( $days as $day )
					{
						foreach( $dayNames as $dayName )
						{
							$dayPos	= \stripos( $day, $dayName );

							if( $dayPos !== FALSE )
							{
								/* We only support basic day recurrence values, not the negative/positive numbers */
								if( $dayPos > 0 )
								{
									throw new \InvalidArgumentException( 'BYDAY' );
								}
								else
								{
									$repeatData['repeat_freq_on_' . $dayName ]	= TRUE;
								}

								break;
							}
						}
					}
				break;

				case 'BYMONTHDAY':
					$values	= array_map( 'trim', explode( ',', $_ruleData[1] ) );

					if( count( $values ) > 1 OR \stripos( $values[0], '-' ) !== FALSE )
					{
						throw new \InvalidArgumentException( 'BYMONTHDAY' );
					}
				break;

				case 'COUNT':
					$repeatData['repeat_end_occurrences']	= (int) $_ruleData[1];
				break;

				case 'INTERVAL':
					$repeatData['event_repeat_freq']		= (int) $_ruleData[1];
				break;

				case 'UNTIL':
					$repeatData['repeat_end_date']			= new \IPS\calendar\Date( $_ruleData[1], $timezone ? new \DateTimeZone( $timezone ) : NULL );
				break;
			}
		}

		/* If no repeat frequency specified, default to 1 (e.g. every week, every day, etc.) */
		if( $repeatData['event_repeats'] AND !$repeatData['event_repeat_freq'] )
		{
			$repeatData['event_repeat_freq']	= 1;
		}

		return $repeatData;
	}

	/**
	 * Create an RRULE value from an array of data
	 *
	 * @note	This is basically the opposite of parseRrule
	 * @see		self::parseRrule()
	 * @param	array		$repeat		The event repeat data
	 * @return	string|NULL
	 */
	public static function buildRrule( $repeat )
	{
		/* If checkbox was not checked, just return */
		if( !isset( $repeat['event_repeat'] ) OR !$repeat['event_repeat'] )
		{
			return NULL;
		}

		/* The basics - always expected */
		$rrule	= array(
			'FREQ=' . mb_strtoupper( $repeat['event_repeats'] ),
			'INTERVAL=' . $repeat['event_repeat_freq']
		);

		/* Other possible properties - not all will be present */
		if( isset( $repeat['repeat_end_occurrences'] ) AND $repeat['repeat_end_occurrences'] )
		{
			$rrule[]	= 'COUNT=' . $repeat['repeat_end_occurrences'];
		}

		if( isset( $repeat['repeat_end_date'] ) AND $repeat['repeat_end_date'] )
		{
			$rrule[]	= 'UNTIL=' . \IPS\calendar\Date::createFromForm( $repeat['repeat_end_date'], NULL, $repeat['event_timezone'] )->setTimezone( new \DateTimeZone( 'UTC' ) )->modifiedIso8601();
		}

		/* By-day rule */
		$days	= array();

		foreach( \IPS\calendar\Date::getDayNames() as $day )
		{
			if( isset( $repeat['repeat_freq_on_' . $day['ical'] ] ) AND $repeat['repeat_freq_on_' . $day['ical'] ] )
			{
				$days[]	= $day['ical'];
			}
		}

		if( count( $days ) )
		{
			$rrule[]	= 'BYDAY=' . implode( ',', $days );
		}

		return implode( ';', $rrule );
	}

	/**
	 * @brief	Calendar we are importing to
	 */
	protected $calendar		= NULL;

	/**
	 * @brief	Member we are importing as
	 */
	protected $member		= NULL;

	/**
	 * @brief	Feed we are importing from
	 */
	protected $feed			= NULL;

	/**
	 * Perform some basic error checking
	 *
	 * @param	string		$content	The ICS contents
	 * @return	bool
	 * @throws	\UnexpectedValueException
	 * @note	This is abstracted as a static method so we can perform error checking prior to saving the feed
	 */
	public static function isValid( $content )
	{
		/* Perform some basic error checking */
		if( !$content )
		{
			throw new \UnexpectedValueException( "NO_CONTENT" );
		}

		$_raw	= preg_replace( "#(\n\r|\r|\n){1,}#", "\n", $content );
		$_raw	= explode( "\n", $_raw );
		
		if( !count($_raw) )
		{
			throw new \UnexpectedValueException( "NO_CONTENT" );
		}
		
		if( $_raw[0] != 'BEGIN:VCALENDAR' )
		{
			throw new \UnexpectedValueException( "BAD_CONTENT" );
		}

		return TRUE;
	}

	/**
	 * Parse the supplied contents (which may come from a URL or an uploaded file) and import events
	 *
	 * @param	string						$content	The ICS contents
	 * @param	int|\IPS\calendar\Calendar	$calendar	The calendar to import to
	 * @param	int|\IPS\Member				$member		The member the imported events should be 'from'
	 * @param	int							$feed		The feed we are importing (used to detect and prevent duplicate imports)
	 * @return	array		Number of events imported and skipped
	 * @throws	\UnexpectedValueException
	 */
	public function parse( $content, $calendar, $member, $feed=NULL )
	{
		/* Load the calendar */
		$this->calendar	= ( $calendar instanceof \IPS\calendar\Calendar ) ? $calendar : \IPS\calendar\Calendar::load( $calendar );
		
		/* And the member */
		$this->member	= ( $member instanceof \IPS\Member ) ? $member : \IPS\Member::load( $member );

		/* And the feed */
		if( $feed !== NULL )
		{
			$this->feed		= \IPS\calendar\Icalendar::load( $feed );
		}

		/* Perform some basic error checking */
		static::isValid( $content );

		$_raw	= preg_replace( "#(\n\r|\r|\n){1,}#", "\n", $content );
		$_raw	= explode( "\n", $_raw );

		/* Store the raw data we will parse */
		$this->_rawIcsData	= $_raw;
		
		/* Now loop and start parsing */
		foreach( $this->_rawIcsData as $k => $v )
		{
			$line	= explode( ':', $v );
			
			switch( $line[0] )
			{
				case 'BEGIN':
					$this->_parseBeginBlock( $line[1], $k );
				break;
				
				/* Unsupported at this time */
				case 'CALSCALE':
				case 'METHOD':
				case 'X-WR-TIMEZONE':
				case 'X-WR-RELCALID':
				default:
				break;
			}
		}

		/* Convert the raw ICS data to GMT now */
		if( count($this->_parsedIcsData) )
		{
			$this->_parsedIcsData	= $this->_convertToGmt( $this->_parsedIcsData );
		}
		
		/* Now loop over the results in order to insert */
		$_imported	= 0;
		$_skipped	= 0;

		// Leave this here - useful for debugging
		// print_r($this->_parsedIcsData);exit;

		if ( count( $this->_parsedIcsData ) )
		{
			/* Loop over the events */
			foreach( $this->_parsedIcsData['events'] as $event )
			{
				/* Quickly, if we don't support the recurrence data provided let's just skip this event */
				if( isset( $event['recurr'] ) )
				{
					try
					{
						$rrule = static::parseRrule( $event['recurr'] );
					}
					catch( \InvalidArgumentException $e )
					{
						continue;
					}
				}
				
				$event['uid']		= $event['uid'] ? $event['uid'] : md5( implode( ',', $event['start'] ) . implode( ',', $event['end'] ) );

				/* Figure out some times */
				$event_unix_from	= $event['start']['gmt_ts'];
				$event_unix_to		= isset( $event['end'] ) ? $event['end']['gmt_ts'] : NULL;
				$event_all_day		= ( $event['start']['type'] == 'DATE' ) ? 1 : 0;
				
				/* End dates in iCalendar format are "exclusive", meaning they are actually the day ahead. */
				/* @link	http://microformats.org/wiki/dtend-issue */
				/* Only adjust end date if end date is not equal too the start date already. */
				/* @see Ticket 876817 */
				
				if( $event_unix_to AND $event['end']['type'] == 'DATE' AND ( $event_unix_from != $event_unix_to ) )
				{
					$event_unix_to	-= 86400;
				}
				
				/* It is a single day event if end date is equal to start date */
				if( $event_unix_from == $event_unix_to )
				{
					$event_unix_to	= 0;
				}

				/* If there is a duration, calculate the end date again */
				if ( ! $event_unix_to AND isset( $event['duration'] ) AND $event['duration'] )
				{
					preg_match( "#(\d+?)H#is", $event['duration'], $match );
					$hour   = $match[1] ? $match[1] : 0;

					preg_match( "#(\d+?)M#is", $event['duration'], $match );
					$minute = $match[1] ? $match[1] : 0;

					preg_match( "#(\d+?)S#is", $event['duration'], $match );
					$second = $match[1] ? $match[1] : 0;

					$event_unix_to	= $event_unix_from + ( $hour * 3600 ) + ( $minute * 60 ) + $second;
				}

				/* If this is an all day event, adjust the timestamps */
				if( $event_all_day )
				{
					$event_unix_from	= gmmktime( 0, 0, 0, gmstrftime( '%m', $event_unix_from ), gmstrftime( '%d', $event_unix_from ), gmstrftime( '%Y', $event_unix_from ) );
					$event_unix_to		= $event_unix_to ? gmmktime( 0, 0, 0, gmstrftime( '%m', $event_unix_to ), gmstrftime( '%d', $event_unix_to ), gmstrftime( '%Y', $event_unix_to ) ) : 0;
				}

				/* If we are missing crucial data, skip this event */
				if( !$event_unix_from OR ( ( !isset( $event['description'] ) OR !$event['description'] ) AND ( !isset( $event['summary'] ) OR !$event['summary'] ) ) )
				{
					$_skipped++;
					continue;
				}
								
				/* Update previously imported events, if possible */
				$eventId	= NULL;

				if( $event['uid'] )
				{
					try
					{
						/* If we are importing an ICS file, feed here will be null. */
						if ( is_null( $this->feed ) )
						{
							/* Bubble up so we can create a new event */
							throw new \UnderflowException;
						}
						
						$eventId	= \IPS\Db::i()->select( 'import_event_id', 'calendar_import_map', array( array( 'import_guid=? and import_feed_id=?', $event['uid'], $this->feed->id ) ) )->first();
						
						try
						{
							$newEvent	= \IPS\calendar\Event::load( $eventId );
						}
						catch( \OutOfRangeException $e )
						{
							/* This event seems to have been deleted - skip it. */
							$_skipped++;
							continue;
						}

						$_skipped++;
					}
					catch( \UnderflowException $e )
					{
						$newEvent	= new \IPS\calendar\Event;

						/* Basics */
						$newEvent->post_key		= md5( uniqid() );
						$newEvent->member_id	= $this->member->member_id;
						$newEvent->calendar_id	= $this->calendar->id;
						$newEvent->approved		= 1;

						/* RSVP ? */
						$newEvent->rsvp			= ( $this->feed === NULL ) ? 1 : (int) $this->feed->allow_rsvp;

						/* Time */
						$newEvent->saved		= ( isset( $event['created'] ) AND $event['created'] AND $event['created'] < time() ) ? $event['created'] : time();
					}
				}

				/* Basics */
				$newEvent->title		= ( isset( $event['summary'] ) AND $event['summary'] ) ? $event['summary'] : mb_substr( strip_tags( $event['description'] ), 0, 100 );
				$newEvent->content		= ( isset( $event['description'] ) AND $event['description'] ) ? nl2br( $event['description'] ) : $event['summary'] . ( isset( $event['location'] ) ? '<br>' . $event['location'] : '' );
				$newEvent->sequence		= ( isset( $event['sequence'] ) ? intval( $event['sequence'] ) : 0 );

				/* Times and dates */
				$newEvent->lastupdated	= ( isset( $event['last_modified'] ) AND $event['last_modified'] AND $event['last_modified'] < time() ) ? $event['last_modified'] : ( ( isset( $event['created'] ) AND $event['created'] AND $event['created'] < time() ) ? $event['created'] : time() );
				$newEvent->all_day		= $event_all_day;
				$newEvent->recurring	= ( isset( $event['recurr'] ) ) ? $event['recurr'] : NULL;
				$newEvent->start_date	= gmstrftime( "%Y-%m-%d %H:%M:00", $event_unix_from );
				$newEvent->end_date		= $event_unix_to ? gmstrftime( "%Y-%m-%d %H:%M:00", $event_unix_to ) : NULL;

				/* Geolocation? */
				if( isset( $event['geo'] ) AND is_array( $event['geo'] ) AND count( $event['geo'] ) )
				{
					try
					{
						$newEvent->location	= json_encode( \IPS\GeoLocation::getByLatLong( $event['geo']['lat'], $event['geo']['long'] ) );
					}
					catch( \BadFunctionCallException $e ){}
				}

				/* Save */
				$newEvent->save();
	 			
				/* Increment counter */
				$_imported++;

				/* Add to index */
				\IPS\Content\Search\Index::i()->index( $newEvent );
				
				/* Update map */
				if( $this->feed !== NULL AND !$eventId )
				{
					\IPS\Db::i()->insert( 'calendar_import_map', array(
						'import_feed_id'	=> $this->feed->id,
						'import_event_id'	=> $newEvent->id,
						'import_guid'		=> $event['uid'],
					)	);
				}

				/* Add any event attendees that are members of our installation */
				if( !$eventId AND isset($event['attendee']) AND count($event['attendee']) )
				{
					foreach( $event['attendee'] as $attendee )
					{
						if( $attendee['email'] )
						{
							$_loadedMember	= \IPS\Member::load( $attendee['email'] );
							
							if( $_loadedMember->member_id )
							{
								\IPS\Db::i()->insert( 'calendar_event_rsvp', array(
									'rsvp_member_id'	=> $_loadedMember->member_id,
									'rsvp_event_id'		=> $newEvent->id,
									'rsvp_date'			=> time(),
								)	);
							}
						}
					}
				}
			}
		}

		/* Increment post counts */
		$this->member->member_posts	= $this->member->member_posts + $_imported;
		$this->member->member_last_post = time();
		$this->member->save();

		/* Return the data */
		return array( 'skipped' => $_skipped, 'imported' => $_imported );
	}

	/**
	 * @brief	Array of calendar events we are adding to an iCalendar export
	 */
	protected $_events		= array();

	/**
	 * Add an event
	 *
	 * @param	\IPS\calendar\Event	$event 	Event data
	 * @return	void
	 */
	public function addEvent( $event )
	{
		if( $event->id )
		{
			$this->_events[ $event->id ]	= $event;
		}
	}

	/**
	 * Remove an event
	 *
	 * @param	int 	$eventId	Event id
	 * @return	void
	 */
	public function removeEvent( $eventId )
	{
		if( $eventId )
		{
			unset( $this->_events[ $eventId ] );
		}
	}

	/**
	 * Build iCalendar feed and return
	 *
	 * @param	int|\IPS\calendar\Calendar|NULL		$calendar	The calendar the feed belongs to
	 * @return	string		iCalendar feed (can be downloaded or sent as webcal subscription)
	 */
	public function buildICalendarFeed( $calendar=NULL )
	{
		/* Load the calendar */
		$this->calendar	= ( $calendar instanceof \IPS\calendar\Calendar ) ? $calendar : ( $calendar !== NULL ? \IPS\calendar\Calendar::load( $calendar ) : NULL );

		/* Start formatting the output */
		$output	 = "BEGIN:VCALENDAR\r\n";
		$output	.= "VERSION:2.0\r\n";
		$output	.= "PRODID:-//IP.Board Calendar " . \IPS\Application::load( 'calendar' )->version . "//EN\r\n";
		$output	.= "METHOD:PUBLISH\r\n";
		$output	.= "CALSCALE:GREGORIAN\r\n";
		if( $this->calendar !== NULL )
		{
			$output	.= "X-WR-CALNAME:" . $this->_encodeSpecialCharacters( $this->calendar->_title ) . "\r\n";
		}
		
		/* Add the time zones to the export */
		$output	.= $this->_addTimezones();
		
		/* Then add the events */
		$output	.= $this->_addEvents();
		
		/* Finalize the output */
		$output	.= "END:VCALENDAR\r\n";
		
		/* And return */
		return $output;
	}

	/**
	 * Build the VTIMEZONE parts of the iCalendar feed
	 *
	 * @return	string
	 */
	protected function _addTimezones()
	{
		/* Initialize */
		$output	= '';

		/* Get the years that all events span */
		$years	= array();
		
		if( count( $this->_events ) )
		{
			foreach( $this->_events as $event )
			{
				$years[ $event->_start_date->year ]	= $event->_start_date->year;
				
				if( $event->_end_date )
				{
					$_startTime	= $event->_start_date->getTimestamp();

					while( $_startTime < $event->_end_date->getTimestamp() )
					{
						$years[ $event->_start_date->year ]	= $event->_start_date->year;
						$years[ $event->_end_date->year ]	= $event->_end_date->year;
						
						$_startTime	+= 2592000;	// add one month
					}
				}
			}
		}
		
		/* Now add the timezones */
		foreach( $years as $year )
		{
			$_daylight_start	= strtotime( 'last Sunday of March ' . $year );
			$_standard_start	= strtotime( 'last Sunday of October ' . $year );
			$_daylight			= gmmktime( 2, 0, 0, 3 , gmdate( 'j', $_daylight_start ), $year );
			$_standard			= gmmktime( 2, 0, 0, 10, gmdate( 'j', $_standard_start ), $year );
			
			$output	.= "BEGIN:VTIMEZONE\r\n";
			$output	.= "TZID:Europe/London\r\n";
			$output	.= "TZURL:http://tzurl.org/zoneinfo/Europe/London\r\n";
			$output	.= "X-LIC-LOCATION:Europe/London\r\n";

			$output	.= "BEGIN:DAYLIGHT\r\n";
			$output	.= "TZOFFSETFROM:+0000\r\n";
			$output	.= "TZOFFSETTO:+0100\r\n";
			$output	.= "TZNAME:BST\r\n";
			$output	.= "DTSTART:" . \IPS\calendar\Date::ts( $_daylight )->modifiedIso8601( TRUE, TRUE ) . "\r\n"; 
			$output	.= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
			$output	.= "END:DAYLIGHT\r\n";

			$output	.= "BEGIN:STANDARD\r\n";
			$output	.= "TZOFFSETFROM:+0100\r\n";
			$output	.= "TZOFFSETTO:+0000\r\n";
			$output	.= "TZNAME:GMT\r\n";
			$output	.= "DTSTART:" . \IPS\calendar\Date::ts( $_standard )->modifiedIso8601( TRUE, TRUE ) . "\r\n"; 
			$output	.= "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
			$output	.= "END:STANDARD\r\n";

			$output	.= "END:VTIMEZONE\r\n";
		}
		
		/* Return the final output */
		return $output;
	}

	/**
	 * Return a UID for iCalendar
	 *
	 * @param	\IPS\calendar\Event 	$event	Event
	 * @param	string
	 */
	protected static function _buildUid( $event )
	{
		$baseUrl = \IPS\Http\Url::internal('');
		return $event->id . '-' . $event->calendar_id . '-' . md5( (string) $baseUrl ) . '@' . $baseUrl->data['host'];
	}

	/**
	 * Build the VEVENT parts of the iCalendar feed
	 *
	 * @return	string
	 */
	protected function _addEvents()
	{
		/* Basic Init */
		$output	= '';

		/* Loop over the events */
		if( count( $this->_events ) )
		{
			foreach( $this->_events as $event )
			{
				/* Normal stuff */
				$output	.= "BEGIN:VEVENT\r\n";
				$output	.= "SUMMARY:" . $this->_encodeSpecialCharacters( $event->title ) . "\r\n";
				$output	.= "DTSTAMP:" . \IPS\calendar\Date::ts( $event->saved )->modifiedIso8601( TRUE, TRUE ) . "\r\n";
				$output	.= "SEQUENCE:" . $event->sequence . "\r\n";
				$output	.= "UID:" . static::_buildUid( $event ) . "\r\n";
				$output	.= $this->_foldLines( "ORGANIZER;CN=\"" . $this->_encodeSpecialCharacters( $event->author()->name, false ) . '":' . \IPS\Settings::i()->email_out ) . "\r\n";

				/* Attachments */
				$attachments	= array();

				preg_match_all( "/(http.+?attachment\.php\?id=(\d+))/i", $event->content, $matches );

				if( is_array($matches) AND count($matches) )
				{
					foreach( $matches[2] as $k => $v )
					{
						$attachments[ $v ]	= $matches[0][ $k ];
					}
				}

				if( count( $attachments ) )
				{
					foreach( \IPS\Db::i()->select( '*', 'core_attachments', 'attach_id IN(' . implode( ',', array_keys( $attachments ) ) . ')' ) as $attachment )
					{
						$file		= \IPS\File::get( 'core_Attachment', $attachment['attach_location'] );
						$output	.= "ATTACH;FMTTYPE=" . \IPS\File::getMimeType( $file->originalFilename ) . ":" . $attachments[ $attachment['attach_id'] ] . "\r\n";
					}
				}

				/* Description */
				$output	.= "DESCRIPTION:" . $this->_encodeSpecialCharacters( $event->content ) . "\r\n";

				/* Add the times/dates */
				if( $event->_end_date )
				{
					if( $event->all_day )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $event->_start_date->modifiedIso8601( FALSE ) . "\r\n";
						$output	.= "DTEND;VALUE=DATE:" . $event->_end_date->modifiedIso8601( FALSE ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART:" . $event->_start_date->modifiedIso8601( TRUE, TRUE ) . "\r\n";
						$output	.= "DTEND:" . $event->_end_date->modifiedIso8601( TRUE, TRUE ) . "\r\n";
					}
				}
				else
				{
					if( $event->all_day )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $event->_start_date->modifiedIso8601( FALSE ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART:" . $event->_start_date->modifiedIso8601( TRUE, TRUE ) . "\r\n";
					}
				}
				
				/* Is this event recurring? */
				if ( $event->recurring )
				{
					$output	.= "RRULE:" . $event->recurring . "\r\n";
				}
				
				/* Any attendees to the event? */
				try
				{
					foreach( $event->attendees( \IPS\calendar\Event::RSVP_YES ) as $attendee )
					{
						$output	.= $this->_foldLines( "ATTENDEE;CN=\"" . $this->_encodeSpecialCharacters( $attendee->name, false ) . '";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:' . \IPS\Settings::i()->email_out ) . "\r\n";
					}
				}
				catch( \BadMethodCallException $e ){}

				/* End */
				$output	.= "END:VEVENT\r\n";
			}
		}

		/* And return the combined output now */
		return $output;
	}

	/**
	 * Encode special characters in a string for iCalendar
	 *
	 * @param	string		$text		String to encode
	 * @param	bool		$lineFold	Line-fold
	 * @return	string		Encoded string
	 */
	protected function _encodeSpecialCharacters( $text, $lineFold=true )
	{
		$text	= strip_tags( str_replace( array( "<br>", "<br />" ), "\n", $text ) );
		$text	= str_replace( "\\", "\\\\", $text );
		$text	= str_replace( "\n" , '\\n', $text );
		$text	= str_replace( "\r" , '\\n', $text );
		$text	= str_replace( ','  , '\,', $text );
		$text	= str_replace( ';'  , '\;', $text );
		$text	= str_replace( '"', '\"', $text );
		
		if( $lineFold )
		{
			$text	= $this->_foldLines( $text );
		}
		
		return $text;
	}
	
	/**
	 * Fold lines per RFC2445
	 *
	 * @param	string		$text	String to fold
	 * @return	string
	 * @link	https://gist.github.com/81747
	 */
	protected function _foldLines( $text )
	{
		$return	= array();
		$_extra	= 15; /* Takes into account line beginning, i.e. "DESCRIPTION:" */
		
		while( \strlen($text) > 60 )
		{
			$space	= 75 - $_extra; /* Remove line beginning - subsequent loops this will be tab character */
			$mbcc	= $space;
			
			while( $mbcc )
			{
				$line	= mb_substr( $text, 0, $mbcc );	/* Get first chunk of chars */
				$octet	= \strlen( $line ); /* Determine how long this really is (3-byte letters could triple the size) */
				
				/* Too long ? */
				if( $octet > $space )
				{
					if( $mbcc - ( $octet - $space ) < 1 )
					{
						$mbcc -= round( $mbcc / 3 );
					}
					else
					{
						$mbcc -= $octet - $space;
					}
				}
				else
				{
					$return[]	= $line;
					$_extra		= 1;
					$text		= mb_substr( $text, $mbcc );
					break;
				}
			}
		}
		
		/* Anything left? */
		if( !empty($text) )
		{
			$return[]	= $text;
		}
		
		/* Return now */
		return implode( "\r\n\t", $return );
	}

	/**
	 * @brief	Type of begin block we are currently parsing
	 */
	protected $_begin			= '';
	
	/**
	 * @brief	Raw iCalendar feed data after parsing
	 */
	protected $_parsedIcsData	= array();
	
	/**
	 * @brief	Raw iCalendar data before parsing
	 */
	protected $_rawIcsData		= array();
	
	/**
	 * @brief	Temp: Current timezone ID we are parsing
	 */
	protected $_tzId			= '';

	/**
	 * @brief	Temp: Current timezone ID we are beginning to parse
	 */
	protected $_tzBegin			= '';

	/**
	 * @brief	Earliest timestamp from feed
	 */
	protected $_feedEarliest	= 0;

	/**
	 * @brief	Latest timestamp from feed
	 */
	protected $_feedLatest		= 0;

	/**
	 * Un-encode special characters in a string coming from iCalendar feed
	 *
	 * @param	string		$text	String to unencode
	 * @return	string		Unencoded string
	 * @link	http://community.invisionpower.com/tracker/issue-33787-formatting-problem-with-imported-ics-file-calendar-app/
	 */
	protected function _unencodeSpecialCharacters( $text )
	{
		/* Reverse encoding */
		if( \stripos( $text, 'encoding=' ) === 0 )
		{
			preg_match( "#encoding=(.+?):(.+?)$#i", $text, $matches );
			
			if( $matches[1] )
			{
				switch( mb_strtolower($matches[1]) )
				{
					case 'base64':
						$text	= base64_decode( $matches[2] );
					break;
					
					case 'quoted-printable':
						$text	= quoted_printable_decode( $matches[2] );
					break;
				}
			}
			else
			{
				$text	= mb_substr( $text, mb_strpos( $text, ':' ) );
			}
		}

		$text	= str_replace( '\\n', "\n", $text );
		$text	= str_replace( '\,', "," , $text );
		$text	= str_replace( '\;', ";" , $text );
		$text	= str_replace( '\:', ":" , $text );
		$text	= str_replace( 'DQUOTE', '"' , $text );

		return $text;
	}

	/**
	 * Unfold lines per RFC2445 4.1
	 *
	 * @param	string		$string	Starting string
	 * @param	int			$line	Starting line number
	 * @return	string
	 */
	protected function _unfoldLines( $string, $line )
	{
		/* Recursively unfold lines as needed */
		if( isset( $this->_rawIcsData[ $line + 1 ] ) AND ( mb_substr( $this->_rawIcsData[ $line + 1 ], 0, 1 ) == ' ' OR mb_substr( $this->_rawIcsData[ $line + 1 ], 0, 1 ) == "\t" ) )
		{
			$string	.= ltrim( $this->_rawIcsData[ $line + 1 ] );
			$string	= $this->_unfoldLines( $string, $line + 1 );
		}
		
		return $string;
	}

	/**
	 * Unparse time information from iCalendar datetime info
	 *
	 * @param	string	$string		iCalendar line
	 * @return	array 	Time information
	 */
	protected function _unparseTimeInfo( $string )
	{
		if( mb_strpos( $string, '=' ) !== false )
		{
			$_tmp	= explode( '=', $string );
			$_key	= explode( ';', $_tmp[0] );
			$tmp	= explode( ':', $_tmp[1] );
		}
		else
		{
			$_tmp	= array();
			$_key	= array();
			$tmp	= explode( ':', $string );
			$tmp[0]	= 'DATETIME';
			
			/* The date string may be in the format of "DTSTART;20160219T140000Z" (for example) */
			if ( !isset( $tmp[1] ) )
			{
				if ( mb_strpos( $string, ';' ) !== false )
				{
					$tmp = explode( ';', $string );
					$tmp[0] = 'DATETIME';
				}
			}
		}
		
		$tzid	= '';

		/* Got a TZID? */
		if ( count( $_key ) AND $_key[1] == 'TZID' )
		{
			$tzid	= $tmp[0];
		}

		/* Is it a date? */
		if ( $tmp[0] == 'DATE' )
		{
			$timestamp	= strtotime( $tmp[1] );
		}
		else 
		{
			$timestamp	= '?';
		}

		$return  = array(
						'type'		=> $tmp[0],
						'raw'		=> $tmp[1],
						'raw_ts'	=> strtotime( $tmp[1] ),
						'ts'		=> $timestamp,
						'tzid'		=> str_replace( '"', '', $tzid ),
						);
						
		/* Is this the earliest or latest timestamp? */
		if ( ( $this->_feedEarliest == 0 ) OR ( $return['raw_ts'] < $this->_feedEarliest ) )
		{
			$this->_feedEarliest	= $return['raw_ts'];
		}
		
		if ( ( $this->_feedLatest == 0 ) OR ( $return['raw_ts'] > $this->_feedLatest ) )
		{
			$this->_feedLatest		= $return['raw_ts'];
		}
		
		/* Return our results */
		return $return;
	}

	/**
	 * Parse a 'BEGIN:' block in an iCalendar feed
	 *
	 * @param	string	$type	Type of 'BEGIN' object
	 * @param	int		$start	Line number
	 * @return	void
	 */
	protected function _parseBeginBlock( $type, $start )
	{
		switch( $type )
		{
			case 'VCALENDAR':
				$this->_begin	= 'VCALENDAR';
				$this->_processVcalendarObject( $start + 1 );
			break;
			
			case 'VTIMEZONE':
				$this->_begin	= 'VTIMEZONE';
				$this->_processTimezoneObject( $start + 1 );
			break;
			
			case 'STANDARD':
				if ( $this->_begin	== 'VTIMEZONE' )
				{
					$this->_processTimezoneTypeObject( $start + 1, 'STANDARD' );
				}
			break;
			
			case 'DAYLIGHT':
				if ( $this->_begin	== 'VTIMEZONE' )
				{
					$this->_processTimezoneTypeObject( $start + 1, 'DAYLIGHT' );
				}
			break;
			
			case 'VEVENT':
				$this->_begin	= 'VEVENT';
				$this->_processEventObject( $start + 1 );
			break;
			
			/* Anything else is unsupported at this time */
			default:
			break;
		}
	}

	/**
	 * @brief	Keep track of object we are parsing inside an event object
	 */
	protected $currentlyParsing = NULL;

	/**
	 * Parse event object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @return	void
	 * @link	http://community.invisionpower.com/resources/bugs.html/_/ip-calendar/recurring-events-can-sometimes-be-skipped-in-ics-r41033
	 */
	protected function _processEventObject( $start )
	{
		/* Init */
		$_break	= false;
		$_event	= array();

		$this->currentlyParsing	= 'EVENT';

		/* Loop over the lines */
		$_recid	= null;

		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			/* Unparse and get content */
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];

			if( $this->currentlyParsing != 'EVENT' AND !in_array( $_type, array( 'END', 'BEGIN' ) ) )
			{
				continue;
			}
			
			switch( $_type )
			{
				case 'CLASS':
					$_event['access_class']			= $_data;
				break;
				
				case 'CREATED':
					if( !isset( $_event['created'] ) OR !$_event['created'] )
					{
						$_event['created']			= strtotime( $_data );
					}
				break;
				
				case 'SUMMARY':
					/* @link	http://community.invisionpower.com/tracker/issue-32941-ical-summary/ */
					if( mb_strpos( $_data, 'LANGUAGE=' ) === 0 )
					{
						$_data	= preg_replace( "/^LANGUAGE=(.+?):(.+?)$/i", "\\2", $_data );
					}

					$_event['summary']				= $this->_unencodeSpecialCharacters( $_data );
				break;

				case 'DESCRIPTION':
					if( mb_strpos( $_data, 'LANGUAGE=' ) === 0 )
					{
						$_data	= preg_replace( "/^LANGUAGE=(.+?):(.+?)$/i", "\\2", $_data );
					}

					$_event['description']			= $this->_unencodeSpecialCharacters( $_data );
				break;
				
				case 'DURATION':
					$_event['duration']				= $_data;
				break;

				case 'DTSTART':
					$_event['start']				= $this->_unparseTimeInfo( $this->_rawIcsData[$i] );
				break;
				
				case 'DTEND':
					$_event['end']					= $this->_unparseTimeInfo( $this->_rawIcsData[$i] );
				break;
				
				case 'DTSTAMP':
					$_event['created']				= strtotime( $_data );
				break;
				
				case 'LAST-MODIFIED':
					$_event['last_modified']		= strtotime( $_data );
				break;

				case 'TRANSP':
					$_event['time_transparent']		= $_data;
				break;								

				case 'GEO':
					$_geo							= explode( ":", $_data );
					$_event['geo']					= array( 'lat' => $_geo[0], 'long' => $_geo[1] );
				break;

				case 'ORGANIZER':
					if ( $_data )
					{
						$line							= explode( ':', $_data );
						$_event['organizer']			= array( 'name' => str_replace( 'CN=', '', $line[0] ), 'email' => $line[1] );
					}
				break;

				case 'ATTENDEE':
					$line							= explode( ':', $_data );
					$_email							= '';
					
					foreach( $line as $_line )
					{
						$_line	= str_replace( 'cn=', '', mb_strtolower($_line) );

						if( filter_var( $_line, FILTER_VALIDATE_EMAIL ) !== FALSE )
						{
							$_email	= $_line;
						}
					}

					$_event['attendee'][]			= array( 'name' => str_replace( 'CN=', '', $line[0] ), 'email' => $_email );
				break;
				
				case 'UID':
					$_event['uid']					= $_data;
				break;
				
				case 'STATUS':
					$_event['status']				= $_data;
				break;
				
				case 'LOCATION':
					$_event['location']				= $this->_unencodeSpecialCharacters( $_data );
				break;

				case 'SEQUENCE':
					$_event['sequence']				= intval($_data);
				break;
				
				case 'RRULE':
					$_event['recurr']				= $_data;
				break;
				
				case 'BEGIN':
					$this->currentlyParsing	= $_data;
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'RECURRENCE-ID':
					$_recid	= $_data;
				break;
				
				case 'END':
					if( $this->currentlyParsing == 'EVENT' )
					{
						$_break	= true;
					}
					else
					{
						$this->currentlyParsing	= 'EVENT';
					}
				break;
			}
			
			if( $_break )
			{
				if( $_recid )
				{
					$_event['uid']	= md5( $_event['uid'] . $_recid );
				}

				$this->_parsedIcsData['events'][] = $_event;
				break;
			}
		}
	}
	
	/**
	 * Parse core vcalendar object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @return	void
	 */
	protected function _processVcalendarObject( $start )
	{
		/* Loop over the lines */
		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			/* Unparse and get the data */
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'PRODID':
					$this->_parsedIcsData['core']['product']		= $_data;
				break;

				case 'VERSION':
					$this->_parsedIcsData['core']['version']		= $_data;
				break;

				case 'BEGIN':
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'X-WR-CALNAME':
					$this->_parsedIcsData['core']['calendar_name']	= $_data;
				break;
				
				case 'END':
					return;
				break;
			}
		}
	}
	
	/**
	 * Parse a timezone object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @param	string	$type	Type of time zone object to parse
	 * @return	void
	 */
	protected function _processTimezoneTypeObject( $start, $type )
	{
		/* Init */
		$type	= mb_strtolower( $type );
		$break	= FALSE;
		
		/* Loop over the lines */
		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			/* Unparse and get the data */
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'DTSTART':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['start']			= strtotime( $_data );
				break;
				
				case 'TZOFFSETTO':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['tz_offset_to']		= $_data;
				break;
				
				case 'TZOFFSETFROM':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['tz_offset_from']	= $_data;
				break;
				
				case 'TZNAME':
					$this->_parsedIcsData['timezones'][ $this->_tzId ][ $type ]['tz_name']			= $_data;
				break;
				
				case 'END':
					return;
				break;
			}
		}
	}
	
	/**
	 * Parse a timezone object in an ical feed
	 *
	 * @param	int		$start	Line number
	 * @return	void
	 */
	protected function _processTimezoneObject( $start )
	{
		/* Loop over the lines */
		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			/* Unparse and get the data */
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if( !$tmp )
			{
				continue;
			}
							
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'TZID':
					$this->_tzId	= $_data;
					$this->_parsedIcsData['timezones'][ $_data ]	= array();
				break;

				case 'LAST-MODIFIED':
					$this->_parsedIcsData['timezones'][ $this->_tzId ]['last_modified']	= $_data;
				break;

				case 'BEGIN':
					$this->_tzBegin = $_data;
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'END':
					if ( $this->_tzBegin == $_data )
					{
						return;
					}
				break;
			}
		}
	}

	/**
	 * Unformat content from incoming ical feed
	 *
	 * @param	string	$string	String content to unparse
	 * @param	int		$line	Line number
	 * @return	mixed	Array of data, or false
	 */
	protected function _unparseContent( $string, $line )
	{
		/* If the line starts with a space it was folded (skip it) */
		if( \substr( $this->_rawIcsData[ $line ], 0, 1 ) == ' ' )
		{
			return false;
		}
		
		/* Process */
		$_temp	= preg_split( "/(:|;)/", $string );
		$_type	= array_shift( $_temp );
		$_data	= implode( ':', $_temp );
		
		/* Unfold lines if necessary */
		$_data	= $this->_unfoldLines( $_data, $line );
		
		/* Return the data */
		return array( 'type' => $_type, 'data' => $_data );
	}

	/**
	 * Convert times to GMT based on timezones
	 *
	 * @param	array 	$data	Parsed data
	 * @return	array
	 */
	protected function _convertToGmt( $data )
	{
		/* Get timezones */
		$timezones = array();

		if ( isset( $data['timezones'] ) AND is_array( $data['timezones'] ) AND count( $data['timezones'] ) )
		{
			foreach( $data['timezones'] as $type => $_data )
			{
				$timezones[ $type ] = $_data;
			}
		}

		/* Fix events */
		if ( is_array( $data['events'] ) AND count( $data['events'] ) )
		{
			foreach( $data['events'] as $id => $event )
			{
				foreach( array( 'start', 'end' ) as $method )
				{
					/* Set up constraints */
					if ( isset( $event[ $method ]['tzid'] ) AND isset( $timezones[ $event[ $method ]['tzid'] ] ) AND is_array( $timezones[ $event[ $method ]['tzid'] ] ) )
					{
						$_standard	= ( isset($timezones[ $event[ $method ]['tzid'] ]['standard']['start'] ) ) ? intval( $timezones[ $event[ $method ]['tzid'] ]['standard']['start'] ) : 0;
						$_daylight	= ( isset($timezones[ $event[ $method ]['tzid'] ]['daylight']['start'] ) ) ? intval( $timezones[ $event[ $method ]['tzid'] ]['daylight']['start'] ) : 0;
						$_offset	= 0;
					
						if ( isset( $event[ $method ]['tzid'] ) AND $event[ $method ]['raw_ts'] )
						{
							//if ( $event[ $method ]['raw_ts'] < $_daylight )
							//{
							//	$_offset = $timezones[ $event[ $method ]['tzid'] ]['standard']['tz_offset_to'];
							//}
							//else if ( $event[ $method ]['raw_ts'] > $_standard )
							//{
							//	$_offset = $timezones[ $event[ $method ]['tzid'] ]['standard']['tz_offset_to'];
							//}
							//else
							//{
							//	$_offset = $timezones[ $event[ $method ]['tzid'] ]['daylight']['tz_offset_to'];
							//}
							
							$_offset = $timezones[ $event[ $method ]['tzid'] ]['standard']['tz_offset_to'];
						
							$event[ $method ]['gmt_ts']		= $event[ $method ]['raw_ts'] - ( $_offset / 100 * 3600 );
							$event[ $method ]['offset']		= $_offset;
							$event[ $method ]['gmt_rfc']	= gmdate( 'r', $event[ $method ]['gmt_ts'] );
						}
					}
					else
					{
						if ( isset( $event[ $method ]['raw_ts'] ) )
						{
							$event[ $method ]['gmt_ts']		= $event[ $method ]['raw_ts'];
							$event[ $method ]['offset']		= 0;
							$event[ $method ]['gmt_rfc']	= gmdate( 'r', $event[ $method ]['gmt_ts'] );
						}
					}
				}
				
				$data['events'][ $id ] = $event;
			}
		}

		return $data;
	}
}
