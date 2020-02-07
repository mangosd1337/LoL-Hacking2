<?php
/**
 * @brief		Calendar-specific date functions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		30 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Calendar-specific date functions
 */
class _Date extends \IPS\DateTime
{
	/**
	 * @brief	Data retrieved from get_date() with timezone accounted for
	 */
	protected $dateInformation	= array();

	/**
	 * @brief	Information about the first day of the month we are working with
	 */
	protected $firstDayOfMonth	= array();

	/**
	 * @brief	Information about the last day of the month we are working with
	 */
	protected $lastDayOfMonth	= array();

	/**
	 * @brief	Information about the first day of the week we are working with
	 */
	protected $firstDayOfWeek	= array();

	/**
	 * @brief	Information about the last day of the week we are working with
	 */
	protected $lastDayOfWeek	= array();

	/**
	 * @brief	Information about the previous month
	 */
	protected $lastMonth	= array();

	/**
	 * @brief	Information about the next month
	 */
	protected $nextMonth	= array();

	/**
	 * @brief	Timezone offset for the current user
	 */
	public $offset	= NULL;

	/**
	 * @brief	Custom date formatting options for calendar
	 */
	public static $dateFormats	= array(
		'locale' => "%x",
		'd_sm_y' => "%d %b %Y",
		'd_lm_y' => "%d %B %Y",
		'sm_d_y' => "%b %d, %Y",
		'lm_d_y' => "%B %d, %Y",
	);

	/**
	 * @brief	Cache date objects we've created through getDate()
	 */
	protected static $dateObjects	= array();

	/**
	 * Creates a new object to represent the requested date
	 *
	 * @param	int|NULL	$year	Year, or NULL for current year
	 * @param	int|NULL	$month	Month, or NULL for current month
	 * @param	int|NULL	$day	Day, or NULL for current day
	 * @param	int			$hour	Hour (defaults to 0)
	 * @param	int			$minute	Minutes (defaults to 0)
	 * @param	int			$second	Seconds (defaults to 0)
	 * @param	int			$offset	The offset from GMT (NULL to calculate automatically based on member's current time)
	 * @return	\IPS\calendar\Date
	 * @throws	\InvalidArgumentException
	 */
	public static function getDate( $year=NULL, $month=NULL, $day=NULL, $hour=0, $minute=0, $second=0, $offset=0 )
	{
		/* Check cache first */
		$_key = md5( json_encode( func_get_args() ) );

		if( isset( static::$dateObjects[ $_key ] ) )
		{
			return static::$dateObjects[ $_key ];
		}

		/* Get our time zone offset */
		if ( !$offset and \IPS\Member::loggedIn()->timezone )
		{
			$offset	= \IPS\DateTime::create()->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) )->getOffset();
		}

		/* Set appropriate defaults for values not supplied, which is normal */
		if( $year === NULL )
		{
			$year = date( 'Y', time() + $offset );
		}

		if( $day === NULL )
		{
			$day = ( $month !== NULL ) ? 1 : date( 'd', time() + $offset );
		}

		if( $month === NULL )
		{
			$month = date( 'm', time() + $offset );
		}

		/* If the date is not valid, that means a bad value was supplied */
		if( !checkdate( $month, $day, $year ) )
		{
			throw new \InvalidArgumentException;
		}

		/* Create the timestamp */
		$timeStamp	= gmmktime( $hour, $minute, $second, $month, $day, $year );

		/* Store the information and return the object */
		$obj	= static::ts( $timeStamp - $offset );
		$obj->dateInformation	= getdate( $timeStamp );
		$obj->offset			= $offset;

		static::$dateObjects[ $_key ] = $obj;

		return static::$dateObjects[ $_key ];
	}

	/**
	 * Create a new datetime object
	 *
	 * @note	We override to update dateInformation and stored offset
	 * @param	string				$time			Time
	 * @param	\DateTimeZone|null	$timezone		Timezone
	 * @return	\IPS\calendar\Date
	 */
	public function __construct( $time="now", $timezone=NULL )
	{
		if ( $timezone )
		{
			$result	= parent::__construct( $time, $timezone );
		}
		else
		{
			$result	= parent::__construct( $time );
		}

		$this->dateInformation	= getdate( $this->getTimestamp() );
		$this->offset			= $this->getOffset();

		return $result;
	}

	/**
	 * Sets the time zone for the DateTime object
	 *
	 * @note	We override to update dateInformation and stored offset
	 * @param	\DateTimeZone	$timezone		New timezone
	 * @return	\IPS\calendar\Date|FALSE
	 */
	public function setTimezone( $timezone )
	{
		$result	= parent::setTimezone( $timezone );

		$this->dateInformation	= getdate( $this->getTimestamp() );
		$this->offset			= $this->getOffset();

		return $result;
	}

	/**
	 * Returns a date object created based on an arbitrary string. Used for both relative time strings and SQL datetime values.
	 *
	 * @note	Datetime values are stored in the database normalized to UTC
	 * @param	string	$datetime		String-based date/time
	 * @param	bool	$forceUTC		Force timezone to UTC (necessary when passing a datetime retrieved from the database)
	 * @return	\IPS\calendar\Date
	 */
	public static function parseTime( $datetime, $forceUTC=FALSE )
	{
		/* Create an \IPS\DateTime object from the datetime value passed in */
		$timezone		= ( $forceUTC === TRUE ) ? new \DateTimeZone( 'UTC' ) : ( \IPS\Member::loggedIn()->timezone ? new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) : NULL );
		$datetime		= new \IPS\DateTime( $datetime, $timezone );

		/* Now correct it back if necessary */
		if( $forceUTC === TRUE AND \IPS\Member::loggedIn()->timezone )
		{
			$datetime->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
		}

		return static::getDate( $datetime->format('Y'), $datetime->format('m'), $datetime->format('d'), $datetime->format('H'), $datetime->format('i'), $datetime->format('s'), $datetime->getOffset() );
	}

	/**
	 * Adjusts the date and returns a new date object representing the adjustment
	 *
	 * @param	string	$adjustment		String to turn into a \DateInterval object which will be applied to the current date/time (supports most strtotime adjustments)
	 * @return	\IPS\calendar\Date
	 * @see		<a href='http://www.php.net/manual/en/dateinterval.createfromdatestring.php'>DateInterval::createFromDateString() docs</a>
	 */
	public function adjust( $adjustment )
	{
		$datetime		= \IPS\DateTime::ts( $this->dateInformation[0] )->setTimezone( new \DateTimeZone( "UTC" ) );
		$datetime->add( \DateInterval::createFromDateString( $adjustment ) );

		return static::getDate( gmdate( 'Y', $datetime->getTimestamp() ), gmdate( 'm', $datetime->getTimestamp() ), gmdate( 'd', $datetime->getTimestamp() ), gmdate( 'H', $datetime->getTimestamp() ), gmdate( 'i', $datetime->getTimestamp() ), gmdate( 's', $datetime->getTimestamp() ) );
	}

	/**
	 * Get the localized day names in correct order
	 *
	 * @return	array
	 * @see		<a href='http://stackoverflow.com/questions/7765469/retrieving-day-names-in-php'>Get localized day names in PHP</a>
	 */
	public static function getDayNames()
	{
		$dayNames	= array();
		$startDay	= \IPS\Settings::i()->ipb_calendar_mon ? 'Monday' : 'Sunday';

		for( $i = 0; $i < 7; $i++ )
		{
			$_time		= strtotime( 'next ' . $startDay . ' +' . $i . ' days' );
			$_abbr		= \IPS\Member::loggedIn()->language()->convertString( strftime( '%a', $_time ) );

			$dayNames[]	= array( 'full' => \IPS\Member::loggedIn()->language()->convertString( strftime( '%A', $_time ) ), 'english' => date( 'l', $_time ), 'abbreviated' => $_abbr, 'letter' => mb_substr( $_abbr, 0, 1 ), 'ical' => mb_strtoupper( mb_substr( date( 'D', $_time ), 0, 2 ) ) );
		}

		return $dayNames;
	}

	/**
	 * @brief List of timezones and the PHP timezone IDs to utilize. Note that this is only used during submit.
	 */
	public static $timezones	= array(
		'-12',
		'-11',
		'-10',
		'-9.5',
		'-9',
		'-8',
		'-7',
		'-6',
		'-5',
		'-4.5',
		'-4',
		'-3.5',
		'-3',
		'-2',
		'-1',
		'0',
		'1',
		'2',
		'3',
		'3.5',
		'4',
		'4.5',
		'5',
		'5.5',
		'5.75',
		'6',
		'6.5',
		'7',
		'8',
		'9',
		'9.5',
		'10',
		'10.5',
		'11',
		'11.5',
		'12',
		'12.75',
		'13',
		'14',
	);

	/**
	 * Get an array of time zones with GMT offset information supplied in a user-friendly format
	 *
	 * @return	array
	 */
	public static function getTimezones()
	{
		$return		= array();

		if( \IPS\Member::loggedIn()->timezone )
		{
			$return[ \IPS\Member::loggedIn()->timezone ]	= array( 'text' => \IPS\Member::loggedIn()->timezone, 'short' => \IPS\Member::loggedIn()->timezone );
		}

		foreach( static::$timezones as $_offset )
		{
			$sprintf = ( \strpos( $_offset, '-' ) === 0 ) ? '- ' . \substr( $_offset, 1 ) : '+ ' . $_offset;
			$return[ $_offset !== '0' ? $_offset : 'GMT' ]	= array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('timezone_offset_' . $_offset ), 'short' => \IPS\Member::loggedIn()->language()->addToStack('timezone_offset_short', FALSE, array( 'sprintf' => array( $sprintf ) ) ) );
		}

		return $return;
	}

	/**
	 * Return a date object based on supplied values, factoring in the timezone offset which could be in the "friendly" timezone format
	 *
	 * @param	string	$date		Date as a textual string, from the date form helper
	 * @param	string	$time		Time as a textual string
	 * @param	string	$timezone	Timezone chosen
	 * @return	\IPS\calendar\Date
	 */
	public static function createFromForm( $date, $time, $timezone )
	{
		/* Correct date */
		$date = \IPS\Helpers\Form\Date::_convertDateFormat( $date );

		/* Fix time inconsistencies */
		if( $time )
		{
			$time	= mb_strtolower( $time );

			/* If they typed in 'am', convert '12' to 00, and then strip 'am' */
			if( \strpos( $time, 'am' ) !== FALSE )
			{
				if( \strpos( $time, '12' ) === 0 )
				{
					$time	= substr_replace( $time, '00', 0, 2 );
				}

				$time	= str_replace( 'am', '', $time );
			}
			/* If they typed in 'pm', add 12 to anything other than 12 and strip 'pm' */
			else if( \strpos( $time, 'pm' ) !== FALSE )
			{
				$_timeBits		= explode( ':', $time );
				$_timeBits[0]	= $_timeBits[0] < 12 ? ( $_timeBits[0] + 12 ) : $_timeBits[0];
				$time			= implode( ':', $_timeBits );

				$time	= str_replace( 'pm', '', $time );
			}

			/* Make sure we have 3 pieces and that all are 2 digits */
			$_timeBits		= explode( ':', $time );
			
			if ( count( $_timeBits ) < 3 )
			{
				while( count( $_timeBits ) < 3 )
				{
					$_timeBits[]	= '00';
				}
			}

			foreach( $_timeBits as $k => $v )
			{
				$_timeBits[ $k ]	= str_pad( trim( $v ), 2, '0', STR_PAD_LEFT );
			}

			/* Avengers assemble! */
			$time	= implode( ':', $_timeBits );
		}
		
		/* Default timezone - fairly straightforward */
		if( \IPS\Member::loggedIn()->timezone AND $timezone == \IPS\Member::loggedIn()->timezone )
		{
			$dateObject	= new static( $date . ( $time ? ' ' . $time : '' ), new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
		}
		else
		{
			/* If time is supplied, we are going to create a date-time string containing timezone offset info, so reformat date since it might be m/d/yyyy for instance */
			$date	= new static( $date, \IPS\Member::loggedIn()->timezone ? new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) : NULL );
			$date	= $date->format( 'Y-m-d' );

			/* Reformat the time to give us what we want */
			if( $time )
			{
				if( $timezone == 'GMT' )
				{
					$time	= 'T' . $time . '+00:00';
				}
				else
				{
					/* Handle incrementals */
					$timezone	= str_replace( array( '.5', '.75' ), array( ':30', ':45' ), $timezone );
					$timezone	= explode( ':', $timezone );
                    
                    $hours = ( mb_substr( $timezone[0], 0, 1 ) == '-' ) ? '-' . str_pad( ltrim( $timezone[0], "-" ), 2, '0', STR_PAD_LEFT ) : '+' . str_pad( $timezone[0], 2, '0', STR_PAD_LEFT );
                    $time	= 'T' . $time . $hours .':' . ( isset( $timezone[1] ) ? str_pad( $timezone[1], 2, '0', STR_PAD_LEFT ) : '00' );
				}
			}
			else
			{
				$time	= 'T00:00:00+00:00';
			}

			$dateObject	= new static( $date . $time );
		}
		
		return $dateObject->setTimezone( new \DateTimeZone( 'UTC' ) );
	}

	/**
	 * Modified version of ISO-8601 used by iCalendar - omits the timezone identifier and all dashes and colons
	 *
	 * @param	bool	$includeTime		Whether to include time or not
	 * @param	bool	$includeIdentifier	Whether to include 'Z' at the end or not
	 * @return	string
	 * @see		<a href='http://www.kanzaki.com/docs/ical/dateTime.html'>DateTime explanation</a>
	 */
	public function modifiedIso8601( $includeTime=TRUE, $includeIdentifier=FALSE )
	{
		if( $includeTime )
		{
			return date( 'Ymd', $this->getTimestamp() ) . 'T' . date( 'His', $this->getTimestamp() ) . ( $includeIdentifier ? 'Z' : '' );
		}
		else
		{
			return date( 'Ymd', $this->getTimestamp() );
		}
	}

	/**
	 * Return the date for use in calendar (used instead of localeDate() to allow admin to configure)
	 *
	 * @return	string
	 */
	public function calendarDate()
	{
		if( \IPS\Settings::i()->calendar_date_format == -1 AND \IPS\Settings::i()->calendar_date_format_custom )
		{
			return \IPS\Member::loggedIn()->language()->convertString( strftime( \IPS\Settings::i()->calendar_date_format_custom, $this->getTimestamp() + $this->offset ) );
		}
		elseif( isset( static::$dateFormats[ \IPS\Settings::i()->calendar_date_format ] ) )
		{
			return \IPS\Member::loggedIn()->language()->convertString( strftime( static::$dateFormats[ \IPS\Settings::i()->calendar_date_format ], $this->getTimestamp() + $this->offset ) );
		}
		else
		{
			return static::localeDate();
		}
	}

	/**
	 * Return the MySQL-style datetime value
	 *
	 * @param	bool	$includeTime	Whether to include time or not
	 * @return	string
	 */
	public function mysqlDatetime( $includeTime=TRUE )
	{
		if( $includeTime )
		{
			return date( 'Y-m-d H:i:s', isset( $this->dateInformation[0] ) ? $this->dateInformation[0] : $this->getTimestamp() );
		}
		else
		{
			return date( 'Y-m-d', isset( $this->dateInformation[0] ) ? $this->dateInformation[0] : $this->getTimestamp() );
		}
	}

	/**
	 * Retrieve the birthdays for the date
	 *
	 * @param	bool		$day	Whether to limit to the current day or not
	 * @param	bool		$count	Flag to indicate we only need the count
	 * @note	When $count is TRUE we return an array of dates => integers (counts), when it is FALSE we return an array of dates => array( member objects ) 
	 * @return	array
	 */
	public function getBirthdays( $day=FALSE, $count=FALSE )
	{
		$birthdays	= array();

		/* Is the setting enabled? */
		if( !\IPS\Settings::i()->show_bday_calendar )
		{
			return $birthdays;
		}

		/* Get where clause */
		$where = $this->getBirthdaysWhere( $day );

		/* Now, do we just want the count? */
		if( $count )
		{
			$members	= \IPS\Db::i()->select( 'COUNT(*) as total, bday_month, bday_day', 'core_members', $where, NULL, NULL, array( 'bday_month', 'bday_day' ) );

			foreach( $members as $member )
			{
				$birthdays[ str_pad( $member['bday_month'], 2, 0, STR_PAD_LEFT ) . str_pad( $member['bday_day'], 2, 0, STR_PAD_LEFT ) ] = $member['total'];
			}
		}
		else
		{
			$members	= \IPS\Db::i()->select( '*', 'core_members', $where, 'name ASC', array( 0, 5 ) );

			if( $members->count() )
			{
				foreach( $members as $member )
				{
					/* If it's not leap year we do the nice thing and show Feb 29th birthdays on Feb 28th */
					if( $this->mon == 2 AND !$this->leapYear() AND $member['bday_month'] == 2 AND $member['bday_day'] == 29 )
					{
						$member['bday_day']	= 28;
					}
					
					$member = \IPS\Member::constructFromData( $member );

					$birthdays[ str_pad( $member->bday_month, 2, 0, STR_PAD_LEFT ) . str_pad( $member->bday_day, 2, 0, STR_PAD_LEFT ) ][] = $member;
				}
			}
		}

		return $birthdays;
	}

	/**
	 * Return the where clause to fetch birthdays
	 *
	 * @param	bool		$day	Whether to limit to the current day or not
	 * @return	array
	 */
	public function getBirthdaysWhere( $day=FALSE )
	{
		/* We always limit by month */
		$where		= array( array( 'bday_month=?', $this->mon ) );

		/* Are we also limiting by day? */
		if( $day === TRUE )
		{
			/* February 29th is special */
			if( $this->mon == 2 AND $this->mday == 28 AND !$this->leapYear() )
			{
				$where[]	= array( 'bday_day IN(28,29)' );
			}
			else
			{
				$where[]	= array( 'bday_day=?', $this->mday );
			}
		}

		/* Exclude banned users */
		$where[] = array( 'temp_ban=?', 0 );

		/* Groups with 'g_view_board' off are also 'banned', so factor those in too */
		$goodGroups = array();

		foreach( \IPS\Member\Group::groups() as $group )
		{
			if( $group->g_view_board )
			{
				$goodGroups[] = $group->g_id;
			}
		}

		$where[] = array( "member_group_id IN(" . implode( ',', $goodGroups ) . ")" );

		return $where;
	}

	/**
	 * Magic method to make retrieving certain data easier
	 *
	 * @param	mixed	$key	Value we tried to retrieve
	 * @return	mixed
	 */
	public function __get( $key )
	{
		return $this->_findValue( $key, $this->dateInformation );
	}

	/**
	 * Retrieve information about the first day of the month
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @return	mixed
	 */
	public function firstDayOfMonth( $key )
	{
		if( !isset( $this->firstDayOfMonth[0] ) )
		{
			$this->firstDayOfMonth	= getdate( gmmktime( 0, 0, 0, $this->mon, 1, $this->year ) );
		}

		return $this->_findValue( $key, $this->firstDayOfMonth );
	}

	/**
	 * Retrieve information about the last day of the month
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @return	mixed
	 */
	public function lastDayOfMonth( $key )
	{
		if( !isset( $this->lastDayOfMonth[0] ) )
		{
			$this->lastDayOfMonth	= getdate( gmmktime( 0, 0, 0, $this->mon + 1, 0, $this->year ) );
		}

		return $this->_findValue( $key, $this->lastDayOfMonth );
	}

	/**
	 * Retrieve information about the previous month
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @return	mixed
	 */
	public function lastMonth( $key )
	{
		if( !isset( $this->lastMonth[0] ) )
		{
			$this->lastMonth	= getdate( gmmktime( 0, 0, 0, $this->mon - 1, 1, $this->year ) );
		}

		return $this->_findValue( $key, $this->lastMonth );
	}

	/**
	 * Retrieve information about the next month
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @return	mixed
	 */
	public function nextMonth( $key )
	{
		if( !isset( $this->nextMonth[0] ) )
		{
			$this->nextMonth	= getdate( gmmktime( 0, 0, 0, $this->mon + 1, 1, $this->year ) );
		}

		return $this->_findValue( $key, $this->nextMonth );
	}

	/**
	 * Retrieve information about the first day of the week we are working with
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @return	mixed
	 */
	public function firstDayOfWeek( $key )
	{
		if( !isset( $this->firstDayOfWeek[0] ) )
		{
			$this->firstDayOfWeek	= getdate( gmmktime( 0, 0, 0, $this->mon, $this->mday - $this->wday, $this->year ) );
		}

		return $this->_findValue( $key, $this->firstDayOfWeek );
	}

	/**
	 * Retrieve information about the last day of the week we are working with
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @return	mixed
	 */
	public function lastDayOfWeek( $key )
	{
		if( !isset( $this->lastDayOfWeek[0] ) )
		{
			$this->lastDayOfWeek	= getdate( gmmktime( 0, 0, 0, $this->mon, $this->mday + ( 6 - $this->wday ), $this->year ) );
		}

		return $this->_findValue( $key, $this->lastDayOfWeek );
	}

	/**
	 * Returns whether the date falls in a leap year or not
	 *
	 * @return bool
	 */
	public function leapYear()
	{
		return (bool) gmdate( 'L', $this->dateInformation[0] );
	}

	/**
	 * Returns whether the current locale uses AM/PM or 24 hour format
	 *
	 * @return	bool
	 * @link	<a href='http://stackoverflow.com/questions/6871258/how-to-determine-if-current-locale-has-24-hour-or-12-hour-time-format-in-php'>Check for 24 hour locale use</a>
	 */
	public static function usesAmPm()
	{
		return ( \substr( gmstrftime( '%X', 57600 ), 0, 2) != 16 );
	}

	/**
	 * Get the 12 hour version of an hour value
	 *
	 * @param	int		Hour	The hour value between 0 and 23
	 * @return	int
	 */
	public static function getTwelveHour( $hour )
	{
		if( $hour == 0 )
		{
			return 12;
		}
		else if( $hour > 12 )
		{
			return $hour - 12;
		}
		else
		{
			return $hour;
		}
	}

	/**
	 * Get the AM/PM value for the current locale
	 *
	 * @param	int		Hour	The hour value between 0 and 23
	 * @return	string
	 */
	public static function getAmPm( $hour )
	{
		return gmstrftime( '%p', $hour * 60 * 60 );
	}

	/**
	 * Find a value in the supplied array and return it. Also supports a few 'special' keys.
	 *
	 * @param	mixed	$key	Key (from getdate()) we want to retrieve
	 * @param	array	$data	Array to look for the key in
	 * @return	mixed
	 */
	protected function _findValue( $key, $data )
	{
		if( isset( $data[ $key ] ) )
		{
			if( $key == 'wday' AND \IPS\Settings::i()->ipb_calendar_mon )
			{
				return ( $data[ $key ] == 0 ) ? 6: ( $data[ $key ] - 1 );
			}

			if( $key == 'mon' OR $key == 'mday' )
			{
				return str_pad( $data[ $key ], 2, '0', STR_PAD_LEFT );
			}

			return $data[ $key ];
		}

		if( $key == 'monthName' AND isset( $data[0] ) )
		{
			return mb_convert_case( \IPS\Member::loggedIn()->language()->convertString( strftime( '%B', $data[0] ) ), MB_CASE_TITLE );
		}

		if( $key == 'monthNameShort' AND isset( $data[0] ) )
		{
			return mb_convert_case( \IPS\Member::loggedIn()->language()->convertString( strftime( '%b', $data[0] ) ), MB_CASE_TITLE );
		}

		if( $key == 'dayName' AND isset( $data[0] ) )
		{
			return mb_convert_case( \IPS\Member::loggedIn()->language()->convertString( strftime( '%A', $data[0] ) ), MB_CASE_TITLE );
		}

		if( $key == 'dayNameShort' AND isset( $data[0] ) )
		{
			return mb_convert_case( \IPS\Member::loggedIn()->language()->convertString( strftime( '%a', $data[0] ) ), MB_CASE_TITLE );
		}

		return NULL;
	}

	/**
	 * Format the time according to the user's locale (without the date)
	 *
	 * @param	bool	$seconds	If TRUE, will include seconds
	 * @param	bool	$minutes	If TRUE, will include minutes
	 * @return	string
	 */
	public function localeTime( $seconds=TRUE, $minutes=TRUE )
	{
		return \IPS\Member::loggedIn()->language()->convertString( strftime( $this->localeTimeFormat( $seconds, $minutes ), $this->getTimestamp() + $this->offset ) );
	}
}