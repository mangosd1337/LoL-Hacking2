<?php
/**
 * @brief		External Database Login Handler
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
 * External Database Login Handler
 */
class _External extends LoginAbstract
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
		/* Build where clause */
		switch ( $this->authTypes )
		{
			case \IPS\Login::AUTH_TYPE_USERNAME:
				$where = array( "{$this->settings['db_col_user']}=?", $values['auth'] );
				break;
			
			case \IPS\Login::AUTH_TYPE_EMAIL:
				$where = array( "{$this->settings['db_col_email']}=?", $values['auth'] );
				break;
			
			case \IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL:
				$where = array( "{$this->settings['db_col_user']}=? OR {$this->settings['db_col_email']}=?", $values['auth'], $values['auth'] );
				break;
				
		}
		if ( $this->settings['db_extra'] )
		{
			$where[] = array( $this->settings['db_extra'] );
		}
		
		/* Fetch result */
		try
		{
			$result = $this->externalDb()->select( '*', $this->settings['db_table'], $where )->first();
		}
		catch ( \IPS\Db\Exception $e )
		{
			throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
		}
		catch ( \UnderflowException $e )
		{
			switch ( $this->authTypes )
			{
				case \IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL:
					$type = 'username_or_email';
					break;
					
				case \IPS\Login::AUTH_TYPE_USERNAME:
					$type = 'username';
					break;
					
				case \IPS\Login::AUTH_TYPE_EMAIL:
					$type = 'email_address';
					break;
			}
			
			throw new \IPS\Login\Exception( \IPS\Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $type ) ) ) ), \IPS\Login\Exception::NO_ACCOUNT );
		}
		
		/* Get a local account if one exists */
		$member = NULL;
		if ( $this->settings['db_col_user'] )
		{
			$_member = \IPS\Member::load( $result[ $this->settings['db_col_user'] ], 'name' );
			if ( $_member->member_id )
			{
				$member = $_member;
			}
		}
		if ( $this->settings['db_col_email'] )
		{
			$_member = \IPS\Member::load( $result[ $this->settings['db_col_email'] ], 'email' );
			if ( $_member->member_id )
			{
				$member = $_member;
			}
		}
		
		/* If the password doesn't match, throw an exception */
		if ( !\IPS\Login::compareHashes( $this->encryptedPassword( $values['password'] ), $result[ $this->settings['db_col_pass'] ] ) )
		{
			throw new \IPS\Login\Exception( 'login_err_bad_password', \IPS\Login\Exception::BAD_PASSWORD, NULL, $member );
		}
		
		/* Or, create a member */
		if ( $member === NULL )
		{
			$member = $this->createOrUpdateAccount( NULL, array(), $this->settings['db_col_user'] ? $result[ $this->settings['db_col_user'] ] : NULL, $this->settings['db_col_email'] ? $result[ $this->settings['db_col_email'] ] : NULL );
		}
		
		/* Return */
		return $member;
	}
	
	/**
	 * Encrypt Password
	 *
	 * @param	string	$password	The password
	 * @return	bool
	 */
	protected function encryptedPassword( $password )
	{
		switch ( $this->settings['db_encryption'] )
		{
			case 'md5':
				return md5( $password );
				
			case 'sha1':
				return sha1( $password );
			
			default:
				return $password;
		}
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
			'login_external_conn',
			'sql_host'		=>  new \IPS\Helpers\Form\Text( 'login_external_host', $this->settings['sql_host'] ?: 'localhost', TRUE ),
			'sql_user'		=>  new \IPS\Helpers\Form\Text( 'login_external_user', $this->settings['sql_user'], TRUE ),
			'sql_pass'		=>  new \IPS\Helpers\Form\Text( 'login_external_pass', $this->settings['sql_pass'], TRUE ),
			'sql_database'	=>  new \IPS\Helpers\Form\Text( 'login_external_database', $this->settings['sql_database'], TRUE ),
			'sql_port'		=>  new \IPS\Helpers\Form\Number( 'login_external_port', $this->settings['sql_port'], FALSE ),
			'sql_socket'	=>  new \IPS\Helpers\Form\Text( 'login_external_socket', $this->settings['sql_socket'], FALSE ),
			'login_external_schema',
			'db_table'		=>  new \IPS\Helpers\Form\Text( 'login_external_table', $this->settings['db_table'], TRUE ),
			'db_col_user'	=>  new \IPS\Helpers\Form\Text( 'login_external_username', $this->settings['db_col_user'], FALSE, array(), function( $val )
					{
						if ( !$val and \IPS\Request::i()->login_auth_types & \IPS\Login::AUTH_TYPE_USERNAME )
						{
							throw new \DomainException('login_external_username_err');
						}
					} ),
			'db_col_email'	=>  new \IPS\Helpers\Form\Text( 'login_external_email', $this->settings['db_col_email'], FALSE, array(), function( $val )
					{
						if ( !$val and \IPS\Request::i()->login_auth_types & \IPS\Login::AUTH_TYPE_EMAIL )
						{
							throw new \DomainException('login_external_email_err');
						}
					} ),
			'db_col_pass'	=>  new \IPS\Helpers\Form\Text( 'login_external_password', $this->settings['db_col_pass'], TRUE ),
			'db_encryption'	=>  new \IPS\Helpers\Form\Select( 'login_external_encryption', $this->settings['db_encryption'], TRUE, array( 'options' => array(
						'md5'		=> 'MD5',
						'sha1'		=> 'SHA1',
						'plaintext'	=> 'login_external_encryption_plain',
					) ) ),
			'db_extra'		=>  new \IPS\Helpers\Form\Text( 'login_external_extra', isset( $this->settings['db_extra'] ) ? $this->settings['db_extra'] : '' ),
			'login_settings',
			'auth_types'	=> new \IPS\Helpers\Form\Select( 'login_auth_types', $this->settings['auth_types'], TRUE, array( 'options' => array(
				\IPS\Login::AUTH_TYPE_USERNAME => 'username',
				\IPS\Login::AUTH_TYPE_EMAIL	=> 'email_address',
				\IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL => 'username_or_email',
			) ) ),
		);
	}
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	\IPS\Db\Exception
	 */
	public function testSettings()
	{
		$select = array( $this->settings['db_col_pass'] );
				
		if ( $this->settings['db_col_user'] )
		{
			$select[] = $this->settings['db_col_user'];
		}
		
		if ( $this->settings['db_col_email'] )
		{
			$select[] = $this->settings['db_col_email'];
		}
		
		try
		{
			$result = $this->externalDb()->select( implode( ',', $select ), $this->settings['db_table'], ( isset( $this->settings['db_extra'] ) AND  $this->settings['db_extra'] != '' ) ? array( $this->settings['db_extra'] ) : NULL )->first();
		}
		catch ( \UnderflowException $e )
		{
			// It's possible that no users exist, which is fine
		}
		
		return TRUE;
	}
	
	/**
	 * Get DB Connection
	 *
	 * @return	bool
	 * @throws	\IPS\Db\Exception
	 */
	protected function externalDb()
	{
		return \IPS\Db::i( 'external_login', $this->settings );
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
			$this->externalDb()->select( $this->settings['db_col_email'], $this->settings['db_table'],  array( "{$this->settings['db_col_email']}=?", $email ) )->first();
			return TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
		catch ( \IPS\Db\Exception $e )
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
			$result = $this->externalDb()->select( $this->settings['db_col_user'], $this->settings['db_table'], array( "{$this->settings['db_col_user']}=?", $username ) )->first();
			return TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
		catch ( \IPS\Db\Exception $e )
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
	 * @throws	\IPS\Db\Exception
	 */
	public function changeEmail( \IPS\Member $member, $oldEmail, $newEmail )
	{
		if ( $this->settings['db_col_email'] )
		{
			$this->externalDb()->update( $this->settings['db_table'], array( $this->settings['db_col_email'] => $newEmail ), array( $this->settings['db_col_email'] . '=?', $oldEmail ) );
		}
	}
	
	/**
	 * Change Password
	 *
	 * @param	\IPS\Member	$member			The member
	 * @param	string		$newPassword	New Password
	 * @return	void
	 * @throws	\IPS\Db\Exception
	 */
	public function changePassword( \IPS\Member $member, $newPassword )
	{
		$where = '1=0';
		switch ( $this->authTypes )
		{
			case \IPS\Login::AUTH_TYPE_USERNAME:
				$where = array( "{$this->settings['db_col_user']}=?", $member->name );
				break;
			
			case \IPS\Login::AUTH_TYPE_EMAIL:
			case \IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL:
				$where = array( "{$this->settings['db_col_email']}=?", $member->email );
				break;
			
				
		}
		
		$this->externalDb()->update( $this->settings['db_table'], array( $this->settings['db_col_pass'] => $this->encryptedPassword( $newPassword ) ), $where );
	}
	
	/**
	 * Change Username
	 *
	 * @param	\IPS\Member	$member			The member
	 * @param	string		$oldUsername	Old Username
	 * @param	string		$newUsername	New Username
	 * @return	void
	 * @throws	\IPS\Db\Exception
	 */
	public function changeUsername( \IPS\Member $member, $oldUsername, $newUsername )
	{
		if ( $this->settings['db_col_user'] )
		{
			$this->externalDb()->update( $this->settings['db_table'], array( $this->settings['db_col_user'] => $newUsername ), array( $this->settings['db_col_user'] . '=?', $oldUsername ) );
		}
	}
}
