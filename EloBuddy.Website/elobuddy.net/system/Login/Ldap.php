<?php
/**
 * @brief		LDAP Login Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Mar 2013
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
 * LDAP Login Handler
 */
class _Ldap extends LoginAbstract
{
	/**
	 * @brief	Authentication types
	 */
	public $authTypes = 0;
	
	/**
	 * @brief	LDAP Resource
	 */
	protected $ldap;
	
	/**
	 * Initiate
	 *
	 * @return	void
	 */
	public function init()
	{
		if ( $this->settings['uid_field'] )
		{
			$this->authTypes += \IPS\Login::AUTH_TYPE_USERNAME;
		}
		if ( $this->settings['email_field'] )
		{
			$this->authTypes += \IPS\Login::AUTH_TYPE_EMAIL;
		}
	}
	
	/**
	 * Authenticate
	 *
	 * @param	array	$values	Values from from
	 * @return	\IPS\Member
	 * @throws	\IPS\Login\Exception
	 */
	public function authenticate( $values )
	{
		/* Get user */
		$result = $this->getUser( $values['auth'], $values['auth'] );
		if ( !$result )
		{
			throw new \IPS\Login\Exception( \IPS\Member::loggedIn()->language()->addToStack('login_err_no_account', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('username') ) ) ), \IPS\Login\Exception::NO_ACCOUNT );
		}
		
		/* Find or create member */
		$member = \IPS\Member::load( $values['auth'], $result['type'] );
		
		/* Check Password */
		if ( !@ldap_bind( $this->ldap, ldap_get_dn( $this->ldap, $result['resource'] ), ( $this->settings['pw_required'] ? $values['password'] : '' ) ) )
		{
			throw new \IPS\Login\Exception( 'login_err_bad_password', \IPS\Login\Exception::BAD_PASSWORD, NULL, $member );
		}

		/* Return or create member */
		if ( !$member->member_id )
		{
			$userData = ldap_get_attributes( $this->ldap, $result['resource'] );
			
			$member = $this->createOrUpdateAccount(
				NULL,
				array(),
				( $this->settings['uid_field'] and isset( $userData[ $this->settings['uid_field'] ][0] ) ) ? $userData[ $this->settings['uid_field'] ][0] : NULL, 
				( $this->settings['email_field'] and isset( $userData[ $this->settings['email_field'] ][0] ) ) ? $userData[ $this->settings['email_field'] ][0] : NULL
			);
		}
		return $member;
	}
	
	/**
	 * Connect to LDAP server
	 *
	 * @return	resource
	 * @throws	\RuntimeException
	 */
	protected function ldap()
	{
		/* Connect to server */
		if ( !$this->settings['server_port'] )
		{
			$this->ldap = ldap_connect( $this->settings['server_host'] );
		}
		else
		{
			$this->ldap = ldap_connect( $this->settings['server_host'], $this->settings['server_port'] );
		}
		
		/* Specify Protocol Version */
		ldap_set_option( $this->ldap, LDAP_OPT_PROTOCOL_VERSION, $this->settings['server_protocol'] );
		
		/* OPT Referrals */
		if ( $this->settings['opt_referrals'] )
		{
			ldap_set_option( $this->ldap, LDAP_OPT_REFERRALS, true );
		}
		else
		{
			ldap_set_option( $this->ldap, LDAP_OPT_REFERRALS, false );
		}

		/* Bind to directory */
		if ( $this->settings['server_user'] or $this->settings['server_pass'] )
		{
			$bind = ldap_bind( $this->ldap, $this->settings['server_user'], $this->settings['server_pass'] );
		}
		else
		{
			$bind = ldap_bind( $this->ldap );
		}

		if ( $bind === FALSE )
		{
			throw new \RuntimeException( 'ldap_err_bind' );
		}

		return $this->ldap;
	}
	
	/**
	 * Get a user
	 *
	 * @param	string	$username	The username
	 * @paeam	string	$email		The email address
	 * @return	array('resource' => resource, 'type' => 'name'|'email)|FALSE
	 * @throws	\IPS\Login\Exception
	 */
	protected function getUser( $username=NULL, $email=NULL )
	{
		$result = NULL;
		$type = NULL;
		
		/* Connect */
		try
		{
			$this->ldap = $this->ldap();
		}
		catch ( \RuntimeException $e )
		{
			throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
		}
		
		/* Try email address */
		if ( $email and $this->authTypes & \IPS\Login::AUTH_TYPE_EMAIL )
		{
			$result = $this->getUserWithFilter("{$this->settings['email_field']}={$email}");
			if ( $result )
			{
				$type = 'email';
			}
		}
		
		/* Try username */
		if ( !$result and $username and $this->authTypes & \IPS\Login::AUTH_TYPE_USERNAME )
		{
			$result = $this->getUserWithFilter("{$this->settings['uid_field']}={$username}{$this->settings['un_suffix']}"); 
			if ( $result )
			{
				$type = 'name';
			}
		}
				
		/* Return */
		return $result ? array( 'resource' => $result, 'type' => $type ) : FALSE;
	}
	
	/**
	 * Get user with filter
	 *
	 * @param	string	$filter	Filter
	 * @return	resource|FALSE
	 */
	protected function getUserWithFilter( $filter )
	{
		/* Add any additional filter */
		if ( $this->settings['filter'] )
		{
			$filter = ( mb_substr( $this->settings['filter'], 0, 1 ) === '(' ) ? "(&({$filter}){$this->settings['filter']})" : "(&({$filter})({$this->settings['filter']}))";
		}
							
		/* Get user */
		$search = ldap_search( $this->ldap, $this->settings['base_dn'], $filter );
		$result = ldap_first_entry( $this->ldap, $search );
				
		/* Return */
		return $result;
	}

	/**
	 * ACP Settings Form
	 *
	 * @param	string	$url	URL to redirect user to after successful submission
	 * @return	array	List of settings to save - settings will be stored to core_login_handlers.login_settings DB field
	 * @code
	 	return array( 'savekey'	=> new \IPS\Helpers\Form\[Type]( ... ), ... );
	 * @endcode
	 */
	public function acpForm()
	{
		return array(
			'server_protocol'	=> new \IPS\Helpers\Form\Select( 'ldap_server_protocol', $this->settings['server_protocol'], TRUE, array( 'options' => array( 3 => 3, 2 => 2 ) ) ),
			'server_host'		=> new \IPS\Helpers\Form\Text( 'ldap_server_host', $this->settings['server_host'], TRUE ),
			'server_port'		=> new \IPS\Helpers\Form\Number( 'ldap_server_port', $this->settings['server_port'] ),
			'opt_referrals'		=> new \IPS\Helpers\Form\YesNo( 'ldap_opt_referrals', $this->settings['opt_referrals'] ?: FALSE, TRUE ),
			'server_user'		=> new \IPS\Helpers\Form\Text( 'ldap_server_user', $this->settings['server_user'] ),
			'server_pass'		=> new \IPS\Helpers\Form\Text( 'ldap_server_pass', $this->settings['server_pass'] ),
			'base_dn'			=> new \IPS\Helpers\Form\Text( 'ldap_base_dn', $this->settings['base_dn'], TRUE ),
			'uid_field'			=> new \IPS\Helpers\Form\Text( 'ldap_uid_field', $this->settings['uid_field'] ?: 'uid' ),
			'email_field'		=> new \IPS\Helpers\Form\Text( 'ldap_email_field', $this->settings['email_field'] ?: 'mail' ),
			'un_suffix'			=> new \IPS\Helpers\Form\Text( 'ldap_un_suffix', $this->settings['un_suffix'] ),
			'pw_required'		=> new \IPS\Helpers\Form\YesNo( 'ldap_pw_required', $this->settings['pw_required'] ?: TRUE, TRUE ),
			'filter'			=> new \IPS\Helpers\Form\Text( 'ldap_filter', $this->settings['filter'] ),
		);
	}
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	\LogicException
	 */
	public function testSettings()
	{
		if ( !extension_loaded('ldap') )
		{
			throw new \InvalidArgumentException( 'login_ldap_err' );
		}
		
		try
		{
			$this->ldap();
		}
		catch ( \RuntimeException $e )
		{
			throw new \InvalidArgumentException( 'login_ldap_err_connect' );
		}
		
		return TRUE;
	}
	
	/**
	 * Can a member sign in with this login handler?
	 * Used to ensure when a user disassociates a social login that they have some other way of logging in
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool
	 */
	public function canProcess( \IPS\Member $member )
	{
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_USERNAME and $member->name and $this->usernameIsInUse( $member->name ) )
		{
			return TRUE;
		}
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_EMAIL and $member->email and $this->emailIsInUse( $member->email ) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Can a member change their email/password with this login handler?
	 *
	 * @param	string		$type	'username' or 'email' or 'password'
	 * @param	\IPS\Member	$member	The member
	 * @return	bool
	 */
	public function canChange( $type, \IPS\Member $member )
	{
		return $this->canProcess( $member );
	}
	
	/**
	 * Email is in use?
	 * Used when registering or changing an email address to check the new one is available
	 *
	 * @param	string				$email		Email Address
	 * @param	\IPS\Member|NULL	$exclude	Member to exclude
	 * @return	bool|NULL Boolean indicates if email is in use (TRUE means is in use and thus not registerable) or NULL if this handler does not support such an API
	 */
	public function emailIsInUse( $email, \IPS\Member $exclude=NULL )
	{
		if ( $exclude )
		{
			return NULL;
		}
		
		try
		{
			return (bool) $this->getUser( NULL, $email );
		}
		catch ( \IPS\Login\Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Username is in use?
	 * Used when registering or changing an username to check the new one is available
	 *
	 * @param	string	$username	Username
	 * @return	bool|NULL			Boolean indicates if username is in use (TRUE means is in use and thus not registerable) or NULL if this handler does not support such an API
	 */
	public function usernameIsInUse( $username )
	{
		try
		{
			return (bool) $this->getUser( $username, NULL );
		}
		catch ( \IPS\Login\Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Change Email Address
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	string		$oldEmail	Old Email Address
	 * @param	string		$newEmail	New Email Address
	 * @return	void
	 * @throws	\Exception
	 */
	public function changeEmail( \IPS\Member $member, $oldEmail, $newEmail )
	{
		$user = $this->getUser( NULL, $member->email );
		if ( $user )
		{
			ldap_modify( $this->ldap, ldap_get_dn( $this->ldap, $user['resource'] ), array( $this->settings['email_field'] => $newEmail ) );
		}
	}
	
	/**
	 * Change Password
	 *
	 * @param	\IPS\Member	$member			The member
	 * @param	string		$newPassword	New Password
	 * @return	void
	 * @throws	\Exception
	 */
	public function changePassword( \IPS\Member $member, $newPassword )
	{
		$user = $this->getUser( $member->name, $member->email );
		if ( $user )
		{	
			ldap_modify( $this->ldap, ldap_get_dn( $this->ldap, $user['resource'] ), array( 'userPassword' => "{SHA}" . base64_encode( pack( "H*", sha1( $newPassword ) ) ) ) );
		}
	}
	
	
	/**
	 * Change Username
	 *
	 * @param	\IPS\Member	$member			The member
	 * @param	string		$oldUsername	Old Username
	 * @param	string		$newUsername	New Username
	 * @return	void
	 * @throws	\Exception
	 */
	public function changeUsername( \IPS\Member $member, $oldUsername, $newUsername )
	{
		$user = $this->getUser( $member->name, $member->email );
		if ( $user )
		{
			ldap_modify( $this->ldap, ldap_get_dn( $this->ldap, $user['resource'] ), array( $this->settings['uid_field'] => $newUsername . $this->settings['un_suffix'] ) );
		}
	}
}