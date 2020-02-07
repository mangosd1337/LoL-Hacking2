<?php
/**
 * @brief		Authorize.Net Exception
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Gateway\AuthorizeNet;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * AuthorizeNet Exception
 */
class _Exception extends \DomainException
{
	/**
	 * Constructor
	 *
	 * @param	int	$reasonCode	Response Reason Code
	 * @return	void
	 */
	public function __construct( $reasonCode )
	{
		switch ( $reasonCode )
		{				
			case 2:
			case 3:
			case 4:
			case 41:
			case 45:
				$message = \IPS\Member::loggedIn()->language()->get( 'card_refused' );
				break;
				
			case 6:
			case 37:
				$message = \IPS\Member::loggedIn()->language()->get( 'card_number_invalid' );
				break;
				
			case 8:
				$message = \IPS\Member::loggedIn()->language()->get( 'card_expire_expired' );
				break;
			
			case 44:
			case 65:
			case 78:
				$message = \IPS\Member::loggedIn()->language()->get( 'ccv_invalid' );
				break;
							
			default:
				$message = \IPS\Member::loggedIn()->language()->get( 'gateway_err' );
				break;
		}
		
		return parent::__construct( $message, $reasonCode );
	}
}