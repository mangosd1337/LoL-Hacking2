<?php
/**
 * @brief		PayPal Stored Card
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\Stripe;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Stripe Stored Card
 */
class _CreditCard extends \IPS\nexus\Customer\CreditCard
{
	/**
	 * @brief	Card
	 */
	protected $_card;
	
	/**
	 * Get card
	 *
	 * @return	\IPS\nexus\CreditCard
	 */
	public function get_card()
	{
		if ( !$this->_card )
		{		
			$profiles = $this->member->cm_profiles;
			if ( !isset( $profiles[ $this->method->id ] ) )
			{
				throw new \UnexpectedValueException;
			}
			
			$response = $this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$this->data}", NULL, 'get' );
					
			$this->_card = new \IPS\nexus\CreditCard;
			$this->_card->lastFour = $response['last4'];
			switch ( $response['type'] )
			{
				case 'Visa':
					$this->_card->type = \IPS\nexus\CreditCard::TYPE_VISA;
					break;
				case 'American Express':
					$this->_card->type =  \IPS\nexus\CreditCard::TYPE_AMERICAN_EXPRESS;
					break;
				case 'MasterCard':
					$this->_card->type = \IPS\nexus\CreditCard::TYPE_MASTERCARD;
					break;
				case 'Discover':
					$this->_card->type =  \IPS\nexus\CreditCard::TYPE_DISCOVER;
					break;
				case 'JCB':
					$this->_card->type =  \IPS\nexus\CreditCard::TYPE_JCB;
					break;
				case 'Diners Club':
					$this->_card->type =  \IPS\nexus\CreditCard::TYPE_DINERS_CLUB;
					break;
			}
			$this->_card->expMonth = $response['exp_month'];
			$this->_card->expYear = $response['exp_year'];
		}		
		return $this->_card;
	}
	
	/**
	 * Set card
	 *
	 * @param	\IPS\nexus\CreditCard	$card	The card
	 * @return	void
	 */
	public function set_card( \IPS\nexus\CreditCard $card )
	{
		$profiles = $this->member->cm_profiles;
		
		if ( !isset( $profiles[ $this->method->id ] ) )
		{
			$response = $this->method->api( 'customers', array( 'metadata' => array( 'id' => $this->member->member_id ) ) );
			$profiles[ $this->method->id ] = $response['id'];
			$this->member->cm_profiles = $profiles;
			$this->member->save();
		}
				
		$response = $this->method->api( "customers/{$profiles[ $this->method->id ]}/cards", array(
			'card' => $card->token
		) );
		$this->data = $response['id'];
		
		$this->save();
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		$profiles = $this->member->cm_profiles;
		try
		{
			$this->method->api( "customers/{$profiles[ $this->method->id ]}/cards/{$this->data}", NULL, 'delete' );
		}
		catch ( \IPS\nexus\Gateway\Stripe\Exception $e ) { }
		return parent::delete();
	}
}