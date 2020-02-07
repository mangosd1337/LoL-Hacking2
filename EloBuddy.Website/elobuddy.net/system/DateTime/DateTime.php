<?php
/**
 * @brief		Date/Time Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		8 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Date/Time Class
 */
class _DateTime extends \DateTime
{
	/**
	 * Create from timestamp
	 *
	 * @param	int		$timestamp		UNIX Timestamp
	 * @param	bool	$bypassTimezone	Ignore timezone (useful for things like rfc1123() which forces to GMT anyways)
	 * @return	\IPS\DateTime
	 */
	public static function ts( $timestamp, $bypassTimezone=FALSE )
	{
		$obj = new static;
		$obj->setTimestamp( $timestamp );
		if ( !$bypassTimezone AND \IPS\Dispatcher::hasInstance() and \IPS\Member::loggedIn()->timezone )
		{
			$validTimezone = TRUE;
			if( in_array( \IPS\Member::loggedIn()->timezone, \DateTimeZone::listIdentifiers() ) )
			{
				try
				{
					$obj->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
				}
				catch ( \Exception $e )
				{
					$validTimezone = FALSE;
				}
			}
			else
			{
				$validTimezone = FALSE;
			}

			if( ! $validTimezone )
			{
				\IPS\Member::loggedIn()->timezone = null;

				if ( \IPS\Member::loggedIn()->member_id )
				{
					\IPS\Member::loggedIn()->save();
				}
			}

		}
		return $obj;
	}
	
	/**
	 * Create New
	 *
	 * @return	\IPS|DateTime
	 */
	public static function create()
	{
		return new static;
	}
	
	/**
	 * Format a DateInterval showing only the relevant pieces.
	 *
	 * @param	\DateInterval	$diff			The interval
	 * @param	int				$restrictParts	The maximum number of "pieces" to return.  Restricts "1 year, 1 month, 1 day, 1 hour, 1 minute, 1 second" to just "1 year, 1 month".  Pass 0 to not reduce.
	 * @param	\IPS\Lang::NULL	$language		The language to use, or NULL for currently logged in member
	 * @return	string 
	 */
	public static function formatInterval( \DateInterval $diff, $restrictParts=2, \IPS\Lang $language = NULL )
	{
		$language = $language ?: \IPS\Member::loggedIn()->language();

		/* Figure out what pieces we have.  Note that we are letting the language manager perform the formatting to implement better pluralization. */
		$format		= array();

		if( $diff->y !== 0 )
		{
			$format[] = $language->addToStack( 'f_years', FALSE, array( 'pluralize' => array( $diff->y ) ) );
		}

		if( $diff->m !== 0 )
		{
			$format[] = $language->addToStack( 'f_months', FALSE, array( 'pluralize' => array( $diff->m ) ) );
		}

		if( $diff->d !== 0 )
		{
			$format[] = $language->addToStack( 'f_days', FALSE, array( 'pluralize' => array( $diff->d ) ) );
		}

		if( $diff->h !== 0 )
		{
			$format[] = $language->addToStack( 'f_hours', FALSE, array( 'pluralize' => array( $diff->h ) ) );
		}

		if( $diff->i !== 0 )
		{
			$format[] = $language->addToStack( 'f_minutes', FALSE, array( 'pluralize' => array( $diff->i ) ) );
		}

		/* If we don't have anything but seconds, return "less than a minute ago" */
		if( !count($format) )
		{
			if( $diff->s !== 0 )
			{
				return $language->addToStack('less_than_a_minute');
			}
		}
		else if( $diff->s !== 0 )
		{
			$format[] = $language->addToStack( 'f_seconds', FALSE, array( 'pluralize' => array( $diff->s ) ) );
		}

		/* If we are still here, reduce the number of items in the $format array as appropriate */
		if( $restrictParts > 0 )
		{
			$useOnly	= array();
			$haveUsed	= 0;

			foreach( $format as $period )
			{
				$useOnly[]	= $period;
				$haveUsed++;

				if( $haveUsed >= $restrictParts )
				{
					break;
				}
			}

			$format	= $useOnly;
		}
		
		return $language->formatList( $format );
	}
	
	/**
	 * Format the date and time according to the user's locale
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return \IPS\Member::loggedIn()->language()->convertString( strftime( '%x ' . $this->localeTimeFormat(), $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ) );
	}
	
	/**
	 * Get HTML output
	 *
	 * @param	bool	$capialize	TRUE if by itself, FALSE if in the middle of a sentence 
	 * @return	string
	 */
	public function html( $capialize=TRUE, $short=FALSE )
	{
		$format = $short ? 1 : ( $capialize ? static::RELATIVE_FORMAT_NORMAL : static::RELATIVE_FORMAT_LOWER );

		return "<time datetime='{$this->rfc3339()}' title='{$this}' data-short='" . trim( $this->relative(1) ) . "'>" . trim( $this->relative( $format ) ) . "</time>";
	}

	/**
	 * Format the date according to the user's locale (without the time)
	 *
	 * @param \IPS\Member $member
	 * @return string
	 */
	public function localeDate( \IPS\Member $member = NULL )
	{
		$member	= ( $member === NULL ) ? \IPS\Member::loggedIn() : $member;
		return $member->language()->convertString( strftime( '%x', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ) );
	}

	/**
	 * Format the date to return month and day without a year
	 *
	 * @return	string
	 */
	public function dayAndMonth()
	{
		return \IPS\Member::loggedIn()->language()->addToStack(
			'_date_day_and_month',
			FALSE,
			array(
				'pluralize'	=> array(
					strftime( ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? '%d' : '%e', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ),
					strftime( '%m', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ),
				)
			)
		);
	}

	/**
	 * Get locale date, forced to 4-digit year format
	 *
	 * @return	string
	 */
	public function fullYearLocaleDate()
	{
		$timeStamp		= $this->getTimestamp() + $this->getTimezone()->getOffset( $this );
		$dateString		= strftime( '%x', $timeStamp );
		$twoDigitYear	= strftime( '%y', $timeStamp );
		$fourDigitYear	= strftime( '%Y', $timeStamp );
		$dateString		= preg_replace_callback( "/(\s|\/|,|-){$twoDigitYear}$/", function( $matches ) use ( $fourDigitYear ) {
			return $matches[1] . $fourDigitYear;
		}, $dateString );
		return \IPS\Member::loggedIn()->language()->convertString( $dateString );
	}
		
	/**
	 * Locale time format
	 *
	 * PHP always wants to use 24-hour format but some
	 * countries prefer 12-hour format, so we override
	 * specifically for them
	 *
	 * @param	bool	$seconds	If TRUE, will include seconds
	  * @param	bool	$minutes	If TRUE, will include minutes
	 * @return	string
	 */
	public function localeTimeFormat( $seconds=FALSE, $minutes=TRUE )
	{
		if ( in_array( preg_replace( '/\.UTF-?8$/', '', \IPS\Member::loggedIn()->language()->short ), array(
			'sq_AL', // Albanian - Albania
			'zh_SG', 'sgp', 'singapore', // Chinese - Singapore
			'zh_TW', 'twn', 'taiwan', // Chinese - Taiwan
			'en_AU', 'aus', 'australia', 'australian', 'ena', 'english-aus', // English - Australia
			'en_CA', 'can', 'canda', 'canadian', 'enc', 'english-can', // English - Canada
			'en_NZ', 'nzl', 'new zealand', 'new-zealand', 'nz', 'english-nz', 'enz', // English - New Zealand
			'en_PH', // English - Phillipines
			'en_ZA', // English - South Africa
			'en_US', 'american', 'american english', 'american-english', 'english-american', 'english-us', 'english-usa', 'enu', 'us', 'usa', 'america', 'united states', 'united-states', // English - United States
			'el_CY', // Greek - Cyprus
			'el_GR', 'grc', 'greece', 'ell', 'greek', // Greek - Greece
			'ms_MY', // Malay - Malaysia
			'ko_KR', 'kor', 'korean', // Korean - South Korea
			'es_MX', 'mex', 'mexico', 'esm', 'spanish-mexican', // Spanish - Mexico
		) ) )
		{
			if( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' )
			{
				return '%I' . ( $minutes ? ':%M' : '' ) . ( $seconds ? ':%S ' : ' ' ) . ' %p';
			}
			else
			{
				return '%l' . ( $minutes ? ':%M' : '' ) . ( $seconds ? ':%S ' : ' ' ) . ' %p';
			}
		}
		
		return '%H' . ( $minutes ? ':%M' : '' ) . ( $seconds ? ':%S ' : ' ' );
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
		return \IPS\Member::loggedIn()->language()->convertString( strftime( $this->localeTimeFormat( $seconds, $minutes ), $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ) );
	}
	
	const RELATIVE_FORMAT_NORMAL = 0;	// Yesterday at 2pm
	const RELATIVE_FORMAT_LOWER  = 2;	// yesterday at 2pm (e.g. "Edited yesterday at 2pm")
	const RELATIVE_FORMAT_SHORT  = 1;	// 1dy (for mobile view)

	/**
	 * Format the date relative to the current date/time
	 * e.g. "30 minutes ago"
	 *
	 * @param	int	$format	The format (see RELATIVE_FORMAT_* constants)
	 * @return	string
	 */
	public function relative( $format=0 )
	{
		$now		= static::create();
		$difference	= $this->diff( $now );
		$capitalKey = ( $format == static::RELATIVE_FORMAT_LOWER ) ? '' : '_c';
		
		/* In the past and from this year */
		if ( !$difference->invert and $now->format('Y') == $this->format('Y') )
		{
			/* More than a week ago: "March 4" */
            if ( $difference->m or $difference->d >= 6 )
			{
				return \IPS\Member::loggedIn()->language()->addToStack(
					$format == static::RELATIVE_FORMAT_SHORT ? '_date_this_year_short' : '_date_this_year_long',
					FALSE,
					array(
						'pluralize'	=> array(
							strftime( ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? '%d' : '%e', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ),
							strftime( '%m', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) )
						)
					)
				);
			}
			/* Less than a week but more than a day ago */
			elseif ( $difference->d )
			{
				$compare = clone $this;
				
				/* Short format: "1 dy" */
				if ( $format === static::RELATIVE_FORMAT_SHORT )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'f_days_short', FALSE, array( 'sprintf' => array( $difference->d ) ) );
				}
				/* Yesterday: "Yesterday at 23:56" */
				elseif ( $difference->d == 1 && ( $compare->add( new \DateInterval( 'P1D' ) )->format('Y-m-d') == $now->format('Y-m-d') ) )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( "_date_yesterday{$capitalKey}", FALSE, array( 'sprintf' => array( $this->localeTime( FALSE ) ) ) );
				}
				/* Other: "Wednesday at 23:56" */
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack( "_date_this_week{$capitalKey}", FALSE, array( 'sprintf' => array( $this->strFormat('%A'), $this->localeTime( FALSE ) ) ) );
				}
			}
			/* Less than a day but more than an hour ago */
			elseif ( $difference->h )
			{
				/* Short format: "1 hr" */
				if ( $format == static::RELATIVE_FORMAT_SHORT )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'f_hours_short', FALSE, array( 'sprintf' => array( $difference->h ) ) );
				}
				/* Long format: "1 hour ago" */
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack( "_date_ago{$capitalKey}", FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'f_hours', FALSE, array( 'pluralize' => array( $difference->h ) ) ) ) ) );
				}
			}
			/* Less than an hour but more than a minute ago */
			elseif ( $difference->i )
			{
				/* Short format: "4 min" */
				if ( $format == static::RELATIVE_FORMAT_SHORT )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'f_minutes_short', FALSE, array( 'sprintf' => array( $difference->i ) ) );
				}
				/* Short format: "4 minutes ago" */
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack( "_date_ago{$capitalKey}", FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'f_minutes', FALSE, array( 'pluralize' => array( $difference->i ) ) ) ) ) );
				}
			}
			/* Less than a minute ago */
			else
			{
				if ( $format == static::RELATIVE_FORMAT_SHORT )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'f_minutes_short', FALSE, array( 'sprintf' => array( 1 ) ) );
				}
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack( "_date_just_now{$capitalKey}" );
				}
			}
		}
		
		/* Anything else - "March 4, 1992" */
		return \IPS\Member::loggedIn()->language()->addToStack(
			$format == static::RELATIVE_FORMAT_SHORT ? '_date_last_year_short' : '_date_last_year_long',
			FALSE,
			array(
				'sprintf'	=> array(
					strftime( '%Y', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) )
				),
				'pluralize'	=> array(
					strftime( ( mb_strtoupper( mb_substr( PHP_OS, 0, 3 ) ) === 'WIN' ) ? '%d' : '%e', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ),
					strftime( '%m', $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ),
				)
			)
		);
	}

	/**
	 * Format times based on strftime() calls instead of date() calls, and convert to UTF-8 if necessary
	 *
	 * @param	string	$format	Format accepted by strftime()
	 * @return	string
	 */
	public function strFormat( $format )
	{
		return \IPS\Member::loggedIn()->language()->convertString( strftime( $format, $this->getTimestamp() + $this->getTimezone()->getOffset( $this ) ) );
	}

	/**
	 * Wrapper for format() so we can convert to UTF-8 if needed
	 *
	 * @param	string	$format	Format accepted by date()
	 * @return	string
	 */
	public function format( $format )
	{
		return \IPS\Member::loggedIn()->language()->convertString( parent::format( $format ) );
	}
	
	/**
	 * Format the date for the datetime attribute HTML <time> tags
	 * This will always be in UTC (so offset is not included) and so should never be displayed normally to users
	 *
	 * @return	string
	 */
	public function rfc3339()
	{
		return date( 'Y-m-d', $this->getTimestamp() ) . 'T' . date( 'H:i:s', $this->getTimestamp() ) . 'Z';
	}

	/**
	 * Format the date for the expires header
	 * This must be in english only and follow a very specific format in GMT (so offset is not included)
	 *
	 * @return	string
	 */
	public function rfc1123()
	{
		return gmdate( "D, d M Y H:i:s", $this->getTimestamp() ) . ' GMT';
	}
}