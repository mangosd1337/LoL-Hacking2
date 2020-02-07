<?php
/**
 * @brief		Renewal Term Object
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		13 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Purchase;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Renewal Term Object
 */
class _RenewalTerm
{
	/**
	 * @brief	Cost
	 */
	public $cost;
	
	/**
	 * @brief	Interval
	 */
	public $interval;
	
	/**
	 * @brief	Tax
	 */
	public $tax;
	
	/**
	 * @brief	Add to base price?
	 */
	public $addToBase = FALSE;
	
	/**
	 * @brief	Grace period
	 */
	public $gracePeriod;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\nexus\Money|array	$cost			Cost
	 * @param	\DateInterval			$interval		Interval
	 * @param	\IPS\nexus\Tax|NULL		$tax			Tax
	 * @param	bool					$addToBase		Add to base?
	 * @param	\DateInterval|NULL		$gracePeriod	Grace period
	 * @return	void
	 */ 
	public function __construct( $cost, \DateInterval $interval, \IPS\nexus\Tax $tax = NULL, $addToBase = FALSE, \DateInterval $gracePeriod = NULL )
	{
		$this->cost = $cost;
		$this->interval = $interval;
		$this->tax = $tax;
		$this->addToBase = $addToBase;
		$this->gracePeriod = $gracePeriod;
	}
	
	/**
	 * Get term
	 *
	 * @return	array
	 */
	public function getTerm()
	{
		if( $this->interval->y )
		{
			return array( 'term' => $this->interval->y, 'unit' => 'y' );
		}
		elseif( $this->interval->m )
		{
			return array( 'term' => $this->interval->m, 'unit' => 'm' );
		}
		else
		{
			return array( 'term' => $this->interval->d, 'unit' => 'd' );
		}
	}
	
	/**
	 * Get term unit
	 *
	 * @return	string
	 */
	public function getTermUnit()
	{
		$term = $this->getTerm();
		$lang = \IPS\Member::loggedIn()->language();
		switch( $term['unit'] )
		{
			case 'd':
				return $lang->pluralize( $lang->get('renew_days'), array( $term['term'] ) );
			case 'm':
				return $lang->pluralize( $lang->get('renew_months'), array( $term['term'] ) );
			case 'y':
				return $lang->pluralize( $lang->get('renew_years'), array( $term['term'] ) );
		}
	}
	
	/**
	 * Number of days
	 *
	 * @return	int
	 */
	public function days()
	{
		$days = 0;
		if ( $this->interval->y )
		{
			$days += ( 365 * $this->interval->y );
		}
		if ( $this->interval->m )
		{
			$days += ( ( 365 / 12 ) * $this->interval->m );
		}
		if ( $this->interval->d )
		{
			$days += $this->interval->d;
		}
		return number_format( $days, 2, '.', '' );
	}
	
	/**
	 * Calculate cost per day
	 *
	 * @return	\IPS\nexus\Money	Cost per day
	 */
	public function costPerDay()
	{
		$days = $this->days();
		if ( !$days )
		{
			return 0;
		}
		else
		{
			return new \IPS\nexus\Money( $this->cost->amount->divide( new \IPS\Math\Number("{$days}") ), $this->cost->currency );
		}
	}
	
	/**
	 * Get the combined cost of this term and another term (used for grouping)
	 *
	 * @param	\IPS\nexus\Purchase\RenewalTerm	$term	Term to add
	 * @return	\IPS\nexus\Money
	 * @throws	\DomainException
	 */
	public function add( RenewalTerm $term )
	{
		/* They need to have the same currency */
		if ( $this->cost->currency !== $term->cost->currency )
		{
			throw new \DomainException('currencies_dont_match');
		}
		
		/* Get some details */
		$thisTerm = $this->getTerm();
		$otherTerm = $term->getTerm();
		$adjustedCost = $term->cost->amount;
		
		/* If they're not based on the same term, try to normalise as best we can */
		if ( $thisTerm['unit'] != $otherTerm['unit'] )
		{
			switch ( $thisTerm['unit'] )
			{
				case 'd':
					switch ( $otherTerm['unit'] )
					{
						case 'm':
							$otherTerm['term'] *= ( 365 / 12 );
							break;
							
						case 'y':
							$otherTerm['term'] *= 365;
							break;
					}
					break;
				case 'm':
					switch ( $otherTerm['unit'] )
					{
						case 'd':
							$thisTerm['term'] *= ( 365 / 12 );
							break;
							
						case 'y':
							$otherTerm['term'] *= 12;
							break;
					}
					break;
				case 'y':
					switch ( $otherTerm['unit'] )
					{
						case 'd':
							$thisTerm['term'] *= 365;
							break;
							
						case 'm':
							$thisTerm['term'] *= 12;
							break;
					}
					break;
			}
		}
			
		/* If they're not the same term, adjust */
		if ( $thisTerm['term'] != $otherTerm['term'] )
		{
			$adjustedCost = $adjustedCost->multiply( ( ( new \IPS\Math\Number("{$thisTerm['term']}") )->divide( new \IPS\Math\Number("{$otherTerm['term']}") ) ) );
		}
		
		/* And return */
		return new \IPS\nexus\Money( $this->cost->amount->add( $adjustedCost ), $this->cost->currency );
	}
	
	/**
	 * Get the cost of this term subtract another term (used for grouping)
	 *
	 * @param	\IPS\nexus\Purchase\RenewalTerm	$term	Term to subtract
	 * @return	\IPS\nexus\Money
	 * @throws	\DomainException
	 */
	public function subtract( RenewalTerm $term )
	{
		$term->cost->amount = $term->cost->amount->multiply( new \IPS\Math\Number( '-1' ) );
		return $this->add( $term );
	}
	
	/**
	 * Times quantity (used to describe a combined renewal cost)
	 *
	 * @param	int	$n	The number to times by
	 * @return	string
	 */
	public function times( $n )
	{
		$cost = new \IPS\nexus\Money( $this->cost->amount->multiply( new \IPS\Math\Number("{$n}") ), $this->cost->currency );
		return sprintf( \IPS\Member::loggedIn()->language()->get( 'renew_option'), $cost, $this->getTermUnit() );
	}
	
	/**
	 * To String
	 *
	 * @return	string
	 */
	public function __toString()
	{
		//return \IPS\Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array( $this->cost, $this->getTermUnit() ) ) );
		return sprintf( \IPS\Member::loggedIn()->language()->get( 'renew_option'), $this->cost, $this->getTermUnit() )	;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse		string				term		'd' for days; 'w' for weeks; 'm' for months; 'y' for years
	 * @apiresponse		int					unit		The number for term. For example, if the renewal term is every 6 months, term will be 'm' and unit will be 6
	 * @apiresponse		\IPS\nexus\Money	price		The renewal price
	 * @apiresponse		\IPS\nexus\Tax		taxClass	If the renewal price is taxed, the tax class that applies
	 */
	public function apiOutput()
	{
		$term = $this->getTerm();
		return array(
			'term'			=> $term['term'],
			'unit'			=> $term['unit'],
			'price'			=> $this->cost->apiOutput(),
			'taxClass'		=> $this->tax ? $this->tax->apiOutput() : null,
		);
	}
}