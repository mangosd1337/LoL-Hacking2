<?php
/**
 * @brief		Number Class for precise math
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Deb 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Math;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Number Class for precise math
 */
class _Number implements \JsonSerializable
{
	/* !Bootstrap */
	
	/**
	 * @brief	Positive
	 */
	protected $positive = TRUE;
	
	/**
	 * @brief	Number before decimal point
	 */
	protected $beforeDecimalPoint = 0;
	
	/**
	 * @brief	Number of decimal places
	 */
	protected $numberOfDecimalPlaces = 0;
	
	/**
	 * @brief	After decimal point
	 */
	protected $afterDecimalPoint = 0;
	
	/**
	 * Constructor
	 *
	 * @string	$number	The number, as a string, using "." for decimal points
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	public function __construct( $number )
	{
		/* Check it's valid */
		if ( !is_string( $number ) )
		{
			throw new \InvalidArgumentException('NOT_A_STRING');
		}
		if ( !preg_match( '/^([+-])?(\d*)(\.(\d*))?$/', $number, $matches ) )
		{
			throw new \InvalidArgumentException('NOT_VALID_NUMBER');
		}
		
		/* Set properties */
		if ( isset( $matches[1] ) and $matches[1] === '-' )
		{
			$this->positive = FALSE;
		}		
		$this->beforeDecimalPoint = isset( $matches[2] ) ? intval( $matches[2] ) : 0;
		$this->numberOfDecimalPlaces = isset( $matches[4] ) ? mb_strlen( $matches[4] ) : 0;
		$this->afterDecimalPoint = isset( $matches[4] ) ? intval( $matches[4] ) : 0;
		
		/* If we have x.y00, simplify to x.y */
		$this->_simplifyDecimalPlaces();
	}
	
	/**
	 * Number is positive?
	 *
	 * @return	bool
	 */
	public function isPositive()
	{	
		return $this->positive;
	}
	
	/**
	 * Number is zero?
	 *
	 * @return	bool
	 */
	public function isZero()
	{
		return ( !$this->beforeDecimalPoint and !$this->afterDecimalPoint );
	}
	
	/**
	 * Number is greater than zero?
	 *
	 * @return	bool
	 */
	public function isGreaterThanZero()
	{
		return $this->isPositive() and ( $this->beforeDecimalPoint or $this->afterDecimalPoint );
	}
	
	/* !Basic Math */
	
	/**
	 * Add
	 *
	 * @param	\IPS\Math\Number	$number	Number to add
	 * @return	\IPS\Math\Number
	 */
	public function add( Number $number )
	{			
		/* This method handles adding two positive numbers, if either (or both) are negative, pass off to subtract() as appropriate */
		if ( !$this->positive )
		{
			if ( !$number->positive )
			{
				/* Both numbers are negative, return -(abs($x)+abs($y)) */
				return $this->multiply( new Number( '-1' ) )->add( $number->multiply( new Number( '-1' ) ) )->multiply( new Number( '-1' ) );
			}
			else
			{
				/* This number is negative, but the number we're adding is positive, return $y-abs($x) */
				return $number->subtract( $this->multiply( new Number( '-1' ) ) );
			}
		}
		elseif ( !$number->positive )
		{
			/* This number is positive, but the number we're adding is negative, return $x-abs($y) */
			return $this->subtract( $number->multiply( new Number( '-1' ) ) );
		}
		
		/* Work out which number has more numbers are the decimal */
		$biggerDecimalPlaces = max( array( $this->numberOfDecimalPlaces, $number->numberOfDecimalPlaces ) );
		
		/* If these are two integers, just do it natively */
		if ( !$biggerDecimalPlaces )
		{
			return new Number( (string) ( $this->beforeDecimalPoint + $number->beforeDecimalPoint ) );
		}
		
		/* Otherwise, add the two absolute values together */
		$paddedThis = intval( "{$this->beforeDecimalPoint}" . str_pad( str_pad( $this->afterDecimalPoint, $this->numberOfDecimalPlaces, '0', STR_PAD_LEFT ), $biggerDecimalPlaces, '0' ) );
		$paddedNumber = intval( "{$number->beforeDecimalPoint}" . str_pad( str_pad( $number->afterDecimalPoint, $number->numberOfDecimalPlaces, '0', STR_PAD_LEFT ), $biggerDecimalPlaces, '0' ) );
		$absoluteResult = $paddedThis + $paddedNumber;

		/* ... and then add the decimal back in the correct place */
		$return = new Number( mb_substr( $absoluteResult, 0, -$biggerDecimalPlaces ) . '.' . mb_substr( $absoluteResult, -$biggerDecimalPlaces ) );
		
		/* Simplify and return */
		$return->_simplifyDecimalPlaces();
		return $return;
	}
	
	/**
	 * Subtract
	 *
	 * @param	\IPS\Math\Number	$number	Number to subtract
	 * @return	\IPS\Math\Number
	 */
	public function subtract( $number )
	{
		/* This method handles subtracting two positive numbers, if either (or both) are negative, pass off to add() as appropriate */
		if ( !$this->positive )
		{
			if ( !$number->positive )
			{
				/* Both numbers are negative, return abs(abs($x)-abs($y)) */
				return $this->multiply( new Number( '-1' ) )->subtract( $number->multiply( new Number( '-1' ) ) )->multiply( new Number( '-1' ) );
			}
			else
			{
				/* This number is negative, but the number we're adding is positive, return -$y+abs($x) */
				return $number->add( $this->multiply( new Number( '-1' ) ) )->multiply( new Number( '-1' ) );
			}
		}
		elseif ( !$number->positive )
		{
			/* This number is positive, but the number we're adding is negative, return $x+abs($y) */
			return $this->add( $number->multiply( new Number( '-1' ) ) );
		}
		
		/* Work out which number has more numbers are the decimal */
		$biggerDecimalPlaces = max( array( $this->numberOfDecimalPlaces, $number->numberOfDecimalPlaces ) );
		
		/* If these are two integers, just do it natively */
		if ( !$biggerDecimalPlaces )
		{
			return new Number( (string) ( $this->beforeDecimalPoint - $number->beforeDecimalPoint ) );
		}
		
		/* Otherwise, add the two absolute values together */
		$paddedThis = intval( "{$this->beforeDecimalPoint}" . str_pad( $this->afterDecimalPoint, $biggerDecimalPlaces, '0' ) );
		$paddedNumber = intval( "{$number->beforeDecimalPoint}" . str_pad( $number->afterDecimalPoint, $biggerDecimalPlaces, '0' ) );
		$absoluteResult = $paddedThis - $paddedNumber;
		
		/* ... and then add the decimal back in the correct place */
		if ( $absoluteResult < 0 )
		{
			$absoluteResult *= -1;
			$return = new Number( '-' . mb_substr( $absoluteResult, 0, -$biggerDecimalPlaces ) . '.' . mb_substr( $absoluteResult, -$biggerDecimalPlaces ) );
		}
		else
		{
			$return = new Number( mb_substr( $absoluteResult, 0, -$biggerDecimalPlaces ) . '.' . mb_substr( $absoluteResult, -$biggerDecimalPlaces ) );
		}
		
		/* Simplify and return */
		$return->_simplifyDecimalPlaces();
		return $return;
	}
	
	/**
	 * Multiple
	 *
	 * @param	\IPS\Math\Number	$number	Number to multiply by
	 * @return	\IPS\Math\Number
	 */
	public function multiply( $number )
	{
		/* Multiply the absolute numbers */
		$absoluteResult = intval( "{$this->beforeDecimalPoint}" . ( $this->afterDecimalPoint ? str_pad( $this->afterDecimalPoint, $this->numberOfDecimalPlaces, '0', STR_PAD_LEFT ) : '' ) ) * intval( "{$number->beforeDecimalPoint}" . ( $number->afterDecimalPoint ? str_pad( $number->afterDecimalPoint, $number->numberOfDecimalPlaces, '0', STR_PAD_LEFT ) : '' ) );		
		
		/* Work out where the decimal point goes */
		$numberOfDecimalPlaces = $this->numberOfDecimalPlaces + $number->numberOfDecimalPlaces;		
		$result = $numberOfDecimalPlaces ? ( mb_substr( $absoluteResult, 0, -$numberOfDecimalPlaces ) . '.' . mb_substr( $absoluteResult, mb_strlen( $absoluteResult ) - $numberOfDecimalPlaces ) ) : "{$absoluteResult}";
			
		/* Work out if the result is positive or negative */
		$positiveOrNegative = '';
		if ( $this->positive xor $number->positive )
		{
			$positiveOrNegative = '-';
		}
		
		/* Return */
		return new Number( "{$positiveOrNegative}{$result}" );
	}
	
	/**
	 * @brief	Truncates the result and does not round
	 */
	const ROUND_TRUNCATE = 0;
	
	/**
	 * @brief	Uses normal rules for rounding (>=0.5 rounds up, <0.5 rounds down)
	 */
	const ROUND_NORMAL = 1;
	
	/**
	 * @brief	Rounds any decimal up
	 */
	const ROUND_UP = 2;
	
	/**
	 * Divide
	 *
	 * @param	\IPS\Math\Number	$number			Number to divide by
	 * @param	int					$precision		The precision to work to
	 * @param	int|NULL			$round			Handles how to round the result. See ROUND_* constants
	 * @return	\IPS\Math\Number
	 * @throws	\RuntimeException
	 */
	public function divide( $number, $precision = 3, $round = \IPS\Math\Number::ROUND_TRUNCATE )
	{
		/* Can't divide by 0 */
		if ( $number->compare( new static('0') ) === 0 )
		{
			throw new \RuntimeException('DIVIDE_BY_ZERO');
		}
		
		/* If we want to round, go up a precision */
		if ( $round !== static::ROUND_TRUNCATE )
		{
			$precision++;
		}
		
		/* Work out the remainder from ( $x * ( 10 ^ $precision ) / $number ) */
		$result = 0;
		$this->multiply( new Number( (string) pow( 10, $precision ) ) )->modulus( $number, $result );
		
		/* Round it */
		if ( $round !== static::ROUND_TRUNCATE )
		{
			$lastDigit = intval( mb_substr( $result, -1 ) );
			$result = mb_substr( $result, 0, -1 );

			if ( $lastDigit !== 0 )
			{
				if ( $round === static::ROUND_NORMAL )
				{
					if ( $lastDigit >= 5 )
					{
						$result++;
					}
					else
					{
						$result--;
					}
				}
				elseif ( $round === static::ROUND_UP )
				{
					$result++;
				}
			}
			
			$precision--;
		}
		
		/* Work out if the result is positive or negative */
		$positiveOrNegative = '';
		if ( $this->positive xor $number->positive )
		{
			$positiveOrNegative = '-';
		}
		
		/* Put the decimal back in the correct place */
		$result = str_pad( $result, $precision, '0', STR_PAD_LEFT );
		$return = new Number( $positiveOrNegative . mb_substr( $result, 0, -$precision ) . '.' . mb_substr( $result, -$precision ) );
		
		/* Simplify and return */
		$return->_simplifyDecimalPlaces();
		return $return;
	}
	
	/**
	 * Modulus
	 *
	 * @param	\IPS\Math\Number	$number			Number to get modulus of
	 * @param	int					$precision		
	 * @return	\IPS\Math\Number
	 */
	public function modulus( $number, &$divides = 0 )
	{
		/* Start with positiver numbers */
		$remainder = clone $this;
		if ( !$remainder->positive )
		{
			$remainder = $remainder->multiply( new Number('-1') );
		}
		$negative = FALSE;
		if ( !$number->positive )
		{
			$negative = TRUE;
			$number = $number->multiply( new Number('-1') );
		}
		
		/* While the number is greater than this number... */
		while ( $remainder->compare( $number ) === 1 )
		{
			/* Add one to the amount we can divide by */
			$divides++;
			
			/* And then subtract the number from what's remaining */
			$remainder = $remainder->subtract( $number );
		}
		
		/* If the number is now equal to this number, add one more one and specify the remainder as 0 */
		if ( $remainder->compare( $number ) === 0 )
		{
			$divides++;
			$remainder = new Number('0');
		}
		
		/* Return */
		return $negative ? $remainder->multiply( new Number('-1') ) : $remainder;
	}
	
	/**
	 * Compare
	 *
	 * @param	\IPS\Math\Number	$number			Number to divide by
	 * @return	int					Returns 0 if the two numbers are equal, 1 if this number is larger than the $number, -1 otherwise
	 */
	public function compare( $number )
	{
		list( $number1, $number2 ) = static::_normaliseTwoNumbers( $this, $number );
		
		if ( $number1->positive and !$number2->positive )
		{
			return 1;
		}
		elseif ( !$number1->positive and $number2->positive )
		{
			return -1;
		}
		
		if ( $number1->beforeDecimalPoint === $number2->beforeDecimalPoint )
		{
			if ( $number1->afterDecimalPoint === $number2->afterDecimalPoint )
			{
				return 0;
			}
			elseif ( $number1->afterDecimalPoint > $number2->afterDecimalPoint )
			{
				return 1;
			}
			else
			{
				return -1;
			}
		}
		elseif ( $number1->beforeDecimalPoint > $number2->beforeDecimalPoint )
		{
			return 1;
		}
		else
		{
			return -1;
		}
	}
	
	/**
	 * Round to a number of decimal places
	 *
	 * @param	int		$decimalPlaces	The number of decimal places to round to
	 * @param	int		$round			Handles how to round. See ROUND_* constants
	 * @return	\IPS\Math\Number
	 */
	public function round( $decimalPlaces, $round = \IPS\Math\Number::ROUND_NORMAL )
	{
		/* Start with a clone */
		$return = clone $this;
		$return->_simplifyDecimalPlaces();
		
		/* We only need to do this if the places we're rounding to is less than what we have */
		if ( $return->numberOfDecimalPlaces > $decimalPlaces )
		{
			/* Set the number of places */
			$return->numberOfDecimalPlaces = $decimalPlaces;
			
			/* Work out what we're doing */
			switch ( $round )
			{
				case static::ROUND_TRUNCATE:
					$up = FALSE;
					break;
				case static::ROUND_UP:
					$up = TRUE;
					break;
				case static::ROUND_NORMAL:
				default:
					$up = ( intval( mb_substr( $return->afterDecimalPoint, $decimalPlaces, 1 ) ) >= 5 );
					break;
			}
					
			/* Do it */
			$return->afterDecimalPoint = intval( mb_substr( $return->afterDecimalPoint, 0, $decimalPlaces ) );
			if ( $up )
			{
				$return->afterDecimalPoint++;
			}
		}
		
		/* And return */
		$return->_simplifyDecimalPlaces();
		return $return;
	}
	
	/**
	 * Calculate percentage
	 *
	 * @param	int|\IPS\Math\Number	$percentage	The percentage
	 * @return	\IPS\Math\Number
	 */
	public function percentage( $percentage )
	{
		if ( !( $percentage instanceof static ) )
		{
			$percentage = new static( "{$percentage}" );
		}
		
		return $this->multiply( $percentage->divide( new static( '100' ) ) );
	}
	
	/* !Array math */
	
	/**
	 * Get sum of numbers
	 *
	 * @param	array	$numbers	array of \IPS\Math\Number objects
	 * @return	\IPS\Math\Number
	 */
	public static function sum( array $numbers )
	{
		$result = new static('0');
		foreach ( $numbers as $number )
		{
			$result = $result->add( $number );
		}
		return $result;
	}
	
	/**
	 * Get product of numbers
	 *
	 * @param	array	$numbers	array of \IPS\Math\Number objects
	 * @return	\IPS\Math\Number
	 */
	public static function product( array $numbers )
	{
		$result = new static('0');
		foreach ( $numbers as $number )
		{
			$result = $result->multiply( $number );
		}
		return $result;
	}
	
	/* !Utility Methods */
	
	/**
	 * Normalise two numbers
	 * Makes the number of decimal places for both numbers equal so that math can be done on them
	 *
	 * @param	\IPS\Math\Number	$number1	Number 1
	 * @param	\IPS\Math\Number	$number2	Number 2
	 * @return	array
	 */
	protected static function _normaliseTwoNumbers( Number $number1, Number $number2 )
	{
		$number1 = clone $number1;
		$number2 = clone $number2;
				
		if ( $number1->numberOfDecimalPlaces != $number2->numberOfDecimalPlaces )
		{
			if ( $number1->numberOfDecimalPlaces > $number2->numberOfDecimalPlaces )
			{
				$number2->_normaliseDecimalPlaces( $number1->numberOfDecimalPlaces );
			}
			else
			{
				$number1->_normaliseDecimalPlaces( $number2->numberOfDecimalPlaces );
			}
		}
		
		return array( $number1, $number2 );
	}
	
	/**
	 * Makes the number of decimal places a specific number
	 *
	 * @param	int	$to	The number of decimal places
	 * @return	void
	 */
	protected function _normaliseDecimalPlaces( $to )
	{
		$this->afterDecimalPoint *= ( ( $to - $this->numberOfDecimalPlaces ) * 10 );
		$this->numberOfDecimalPlaces = $to;
	}
	
	/**
	 * Simplifies the number of decimal places to the lowest number necessary
	 *
	 * @return	void
	 */
	protected function _simplifyDecimalPlaces()
	{
		if ( $this->afterDecimalPoint === 0 )
		{
			$this->numberOfDecimalPlaces = 0;
			
			if ( $this->beforeDecimalPoint === 0 )
			{
				$this->positive = TRUE;
			}
			return;
		}
		
		while ( ( $this->afterDecimalPoint % 10 ) === 0 )
		{
			$this->numberOfDecimalPlaces--;
			$this->afterDecimalPoint /= 10;
		}
	}
	
	/* !Output */
	
	/**
	 * Convert to string
	 * Not locale aware - uses '.' for decimal points and no thousand separator
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return ( $this->positive ? '' : '-' ) . $this->beforeDecimalPoint . ( $this->numberOfDecimalPlaces ? ( '.' . str_pad( $this->afterDecimalPoint, $this->numberOfDecimalPlaces, '0', STR_PAD_LEFT ) ) : '' );
	}
	
	/**
	 * Value for json_encode
	 *
	 * @return	string
	 */
	public function jsonSerialize()
	{
		return (string) $this;
	}
}