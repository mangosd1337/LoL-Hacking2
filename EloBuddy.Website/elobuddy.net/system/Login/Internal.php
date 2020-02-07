<?php
/**
 * @brief		Internal Login Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Mar 2013
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
 * Internal Login Handler
 */
class _Internal extends LoginAbstract
{
	/**
	 * @brief	Authentication types
	 */
	public $authTypes;
	
	/**
	 * Initiate
	 *
	 * @return	void
	 */
	public function init()
	{
		$this->authTypes = $this->settings['auth_types'] ?: \IPS\Login::AUTH_TYPE_USERNAME;
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
		/* Get member(s) */
		$members = array();
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_USERNAME )
		{
			$_member = \IPS\Member::load( $values['auth'], 'name', NULL );
			if ( $_member->member_id )
			{
				$members[] = $_member;
			}
			
			$_legacyMember = \IPS\Member::load( \IPS\Request::legacyEscape( $values['auth'] ), 'name', NULL );
			if ( $_legacyMember->member_id )
			{
				$members[] = $_legacyMember;
			}
		}
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_EMAIL )
		{
			$_member = \IPS\Member::load( $values['auth'], 'email' );
			if ( $_member->member_id )
			{
				$members[] = $_member;
			}
			
			$_legacyMember = \IPS\Member::load( \IPS\Request::legacyEscape( $values['auth'] ), 'email' );
			if ( $_legacyMember->member_id )
			{
				$members[] = $_legacyMember;
			}
		}
		
		/* If we didn't match any, throw an exception */
		if ( empty( $members ) )
		{
			throw new \IPS\Login\Exception( \IPS\Member::loggedIn()->language()->addToStack('login_err_no_account', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $this->getLoginType( $this->authTypes ) ) ) ) ), \IPS\Login\Exception::NO_ACCOUNT );
		}
		
		/* Check the password for each possible account */
		foreach ( $members as $member )
		{
			if ( \IPS\Login::compareHashes( $member->members_pass_hash, $member->encryptedPassword( $values['password'] ) ) )
			{				
				/* If it's the old style, convert it to the new */
				if ( mb_strlen( $member->members_pass_salt ) !== 22 )
				{
					$member->members_pass_salt = $member->generateSalt();
					$member->members_pass_hash = $member->encryptedPassword( $values['password'] );					
					$member->save();
				}
				
				/* Return */
				return $member;
			}
		}

		/* Still here? Throw a password incorrect exception */
		throw new \IPS\Login\Exception( 'login_err_bad_password', \IPS\Login\Exception::BAD_PASSWORD, NULL, $member );
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
			'auth_types'	=> new \IPS\Helpers\Form\Select( 'login_auth_types', $this->settings['auth_types'], TRUE, array( 'options' => array(
				\IPS\Login::AUTH_TYPE_USERNAME => 'username',
				\IPS\Login::AUTH_TYPE_EMAIL	=> 'email_address',
				\IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL => 'username_or_email',
			) ) )
		);
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		return TRUE;
	}
	
	/**
	 * [Node] Get whether or not this node is locked to current enabled/disabled status
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__locked()
	{
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
		if ( !$member->members_pass_hash )
		{
			return FALSE;
		}
		
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_USERNAME and $member->name )
		{
			return TRUE;
		}
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_EMAIL and $member->email )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return	bool
	 */
	public function canDelete()
	{
		return FALSE; # this is bad, mkay
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
		/* We can only change the internal password if we haave one (i.e. we didn't sign in with a social service) because we need the existing password */
		if ( $type === 'password' )
		{
			return (bool) $member->members_pass_hash;
		}
		
		/* But we can always change username and email */
		return TRUE;
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
		$member = \IPS\Member::load( $email, 'email' );
		return (bool) ( $member->member_id and $member != $exclude );
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
		$member = \IPS\Member::load( $username, 'name' );
		return (bool) $member->member_id;
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
		$member->email = $newEmail;
		$member->save();
		$member->memberSync( 'onEmailChange', array( $newEmail, $oldEmail ) );
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
		/* Clear any current sessions for this user. This resolves an issue where a user resets their password in one location, but a malicious
			user with access to their account can retain access in another location. */
		\IPS\Db::i()->delete( 'core_sessions', array( 'member_id=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'core_sys_cp_sessions', array( 'session_member_id=?', $member->member_id ) );

		$member->members_pass_salt	= $member->generateSalt();
		$member->members_pass_hash	= $member->encryptedPassword( $newPassword );
		$member->member_login_key	= '';
		/* When we reset the login key and call checkLoginKey(), it will save for us. Note that we are NOT sending an updated pass_hash cookie
			here, and that is instead the responsibility of any controllers that result in the password being updated. This is a central class
			and we may not be updating the current viewing user's password at this point. */
		$member->checkLoginKey();
		$member->memberSync( 'onPassChange', array( $newPassword ) );
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
		$member->name = $newUsername;
		$member->save();
	}
}