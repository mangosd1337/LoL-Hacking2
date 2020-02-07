<?php
/**
 * @brief		Login Exception Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		26 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login Exception Class
 */
class _Exception extends \DomainException
{
	const INTERNAL_ERROR = 1;
	const REGISTRATION_DENIED_BY_SPAM_SERVICE = 2;
	const REGISTRATION_DISABLED = 3;
	const BAD_PASSWORD = 4;
	const NO_ACCOUNT = 5;
	const MERGE_SOCIAL_ACCOUNT = 6;
	
	/**
	 * @brief	Member
	 */
	public $member = NULL;
	
	/**
	 * @bried	Handler
	 */
	public $handler = NULL;
	
	/**
	 * @brief	Details
	 */
	public $details = NULL;

	/**
	 * Constructor
	 *
	 * @param	string				$message	Message
	 * @param	int					$code		Code
	 * @param	\Exception|NULL		$previous	Previous Exception
	 * @param	\IPS\Member|null	$member		Member
	 * @return	void
	 */
	public function __construct( $message, $code=NULL, $previous=NULL, $member=NULL )
	{
		if ( $code === static::BAD_PASSWORD and $member !== NULL )
		{
			/* $member->failed_logins may not be an array explicitly when upgrading due to past bugs */
			$failedLogins = is_array( $member->failed_logins ) ? $member->failed_logins : array();
			$failedLogins[ \IPS\Request::i()->ipAddress() ][] = time();
			$member->failed_logins = $failedLogins;
			$member->save();
		}
		
		parent::__construct( $message, $code, $previous );
		$this->member = $member;
	}
}