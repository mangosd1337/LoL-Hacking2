<?php
/**
 * @brief		Converter Login Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		14 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */
namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( ! defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER[ 'SERVER_PROTOCOL' ] ) ? $_SERVER[ 'SERVER_PROTOCOL' ] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit();
}

/**
 * Converter Login Handler
 */
class _Convert extends LoginAbstract
{
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'arrow-right';
	
	/** 
	 * @brief	Auth types
	 */
	public $authTypes = NULL;

	/**
	 * Initiate
	 *
	 * @return	void
	 */
	public function init()
	{
		$this->authTypes = $this->settings['auth_types'] ?: \IPS\Login::AUTH_TYPE_USERNAME + \IPS\Login::AUTH_TYPE_EMAIL;
	}
	
	/**
	 * Authenticate
	 *
	 * @param	array	$values	Values from form
	 * @return	\IPS\Member
	 * @throws	\IPS\Login\Exception
	 */
	public function authenticate( $values )
	{
		/* Get member(s) */
		$members = array();
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_USERNAME )
		{
			$_member = \IPS\Member::load( $values[ 'auth' ], 'name', NULL );
			if ( $_member->member_id )
			{
				$members[] = $_member;
			}
		}
		if ( $this->authTypes & \IPS\Login::AUTH_TYPE_EMAIL )
		{
			$_member = \IPS\Member::load( $values[ 'auth' ], 'email' );
			if ( $_member->member_id )
			{
				$members[] = $_member;
			}
		}
		
		/* If we didn't match any, throw an exception */
		if ( empty( $members ) )
		{
			throw new \IPS\Login\Exception( \IPS\Member::loggedIn()->language()->addToStack( 'login_err_no_account', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $this->getLoginType( $this->authTypes ) ) ) ) ), \IPS\Login\Exception::NO_ACCOUNT );
		}
		
		/* Table switcher for new IPS4 converters */
		$table = 'conv_apps';
		if ( \IPS\Db::i()->checkForTable( 'convert_apps' ) )
		{
			$table = 'convert_apps';
		}
		
		$apps = \IPS\Db::i()->select( 'app_key', $table, array( 'login=?', 1 ) );
		
		foreach( $apps as $sw )
		{
			/* loop found members */
			foreach( $members as $member )
			{
				/* Check the app method exists */
				if ( !method_exists( $this, $sw ) )
				{
					continue;
				}
				
				if ( $this->$sw( $member, $values[ 'password' ] ) )
				{
					/*	Update password and return */
					if ( \IPS\Db::i()->checkForColumn( 'core_members', 'misc' ) )
					{
						$member->misc = "";
					}
					$member->conv_password = "";
					$member->members_pass_salt = $member->generateSalt();
					$member->members_pass_hash = $member->encryptedPassword( $values[ 'password' ] );
					$member->save();
					$member->memberSync( 'onPassChange', array( $values[ 'password' ] ) );
					
					return $member;
				}
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
	 * Can a member sign in with this login handler?
	 * Used to ensure when a user disassociates a social login that they have some other way of logging in
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool
	 */
	public function canProcess( \IPS\Member $member )
	{
		// Though this is not entirely true, once a user logs in with the Convert method,
		// a password for the Internal method is created for them so we want that to
		// be being used and not for this method to be depended on
		return FALSE;
	}
	
	/**
	 * Can a member change their email/password with this login handler?
	 *
	 * @param	string		$type	'email' or 'password'
	 * @param	\IPS\Member	$member	The member
	 * @return	bool
	 */
	public function canChange( $type, \IPS\Member $member )
	{
		return FALSE;
	}

	/**
	 * AEF
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function aef( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, md5( $member->misc . $password ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * BBPress Standalone
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function bbpress_standalone( $member, $password )
	{
		return $this->bbpress( $member, $password );
	}
	
	/**
	 * BBPress
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function bbpress( $member, $password )
	{
		$success = false;
		$password = html_entity_decode( $password );
		$hash = $member->conv_password;
		
		if ( \strlen( $hash ) == 32 )
		{
			$success = ( bool ) ( \IPS\Login::compareHashes( md5( $password ), $member->conv_password ) );
		}
		
		// Nope, not md5.
		if ( ! $success )
		{
			$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			$crypt = $this->hashCryptPrivate( $password, $hash, $itoa64, 'P' );
			if ( $crypt[ 0 ] == '*' )
			{
				$crypt = crypt( $password, $hash );
			}
			
			if ( $crypt == $hash )
			{
				$success = true;
			}
		}
		
		// Nope
		if ( ! $success )
		{
			// No - check against WordPress.
			// Note to self - perhaps push this to main bbpress method.
			$success = $this->wordpress( $member, $password );
		}
		
		return $success;
	}
	
	/**
	 * BBPress 2.3
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function bbpress23( $member, $password )
	{
		return $this->bbpress( $member, $password );
	}
	
	/**
	 * Community Server
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function cs( $member, $password )
	{
		$hash = $member->conv_password;
		
		$encodedHashPass = base64_encode( pack( "H*", sha1( base64_decode( $member->misc ) . $password ) ) );
		$single_md5_pass = md5( $password );
		
		if ( \IPS\Login::compareHashes( $encodedHashPass, $hash ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * CSAuth
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function CSAuth( $member, $password )
	{
		$wsdl = 'https://internal.auth.com/Service.asmx?wsdl';
		$dest = 'https://interal.auth.com/Service.asmx';
		$single_md5_pass = md5( $password );
		
		try
		{
			$client = new SoapClient( $wsdl, array( 'trace' => 1 ) );
			$client->__setLocation( $dest );
			$loginparams = array( 'username' => $member->name, 'password' => $password );
			$result = $client->AuthCS( $loginparams );
			
			switch( $result->AuthCSResult )
			{
				case 'SUCCESS' :
					return TRUE;
				case 'WRONG_AUTH' :
					return FALSE;
				default :
					return FALSE;
			}
		}
		catch( Exception $ex )
		{
			return FALSE;
		}
	}
	
	/**
	 * Discuz
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function discuz( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, md5( md5( $password ) . $member->misc ) ) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * FudForum
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function fudforum( $member, $password )
	{
		$success = false;
		$single_md5_pass = md5( $password );
		$hash = $member->conv_password;
		
		if ( \strlen( $hash ) == 40 )
		{
			$success = ( \IPS\Login::compareHashes( sha1( $member->misc . sha1( $password ) ), $hash ) ) ? TRUE : FALSE;
		}
		else
		{
			$success = ( \IPS\Login::compareHashes( $single_md5_pass, $hash ) ) ? TRUE : FALSE;
		}
		
		return $success;
	}
	
	/**
	 * FusionBB
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function fusionbb( $member, $password )
	{
		/* FusionBB Has multiple methods that can be used to check a hash, so we need to cycle through them */
	
		/* md5( md5( salt ) . md5( pass ) ) */
		if ( \IPS\Login::compareHashes( md5( md5( $member->misc ) . md5( $password ) ), $member->conv_password ) )
		{
			return TRUE;
		}
		
		/* md5( md5( salt ) . pass ) */
		if ( \IPS\Login::compareHashes( md5( md5( $member->misc ) . $password ), $member->conv_password ) )
		{
			return TRUE;
		}
		
		/* md5( pass ) */
		if ( \IPS\Login::compareHashes( md5( $password ), $member->conv_password ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Ikonboard
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function ikonboard( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, crypt( $password, $member->misc ) ) )
		{
			return TRUE;
		}
		else if ( \IPS\Login::compareHashes( $member->conv_password, md5( $password . mb_strtolower( $member->conv_password_extra ) ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Joomla
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function joomla( $member, $password )
	{
		/* Joomla 3 */
		if( preg_match( '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/', $member->conv_password ) )
		{
			$ph = new PasswordHash( 8, TRUE );
			return $ph->CheckPassword( $password, $member->conv_password ) ? TRUE : FALSE;
		}

		/* Joomla 2 */
		if ( \IPS\Login::compareHashes( $member->conv_password, md5( $password . $member->misc ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Kunena
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function kunena( $member, $password )
	{
		// Kunena authenticates using internal Joomla functions.
		// This is required, however, if the member only converts from
		// Kunena and not Joomla + Kunena.
		return $this->joomla( $member, $password );
	}
	
	/**
	 * PhpBB
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function phpbb( $member, $password )
	{
		$password = html_entity_decode( $password );
		$success = FALSE;
		$hash = $member->conv_password;
		
		/* phpBB 3.1 */
		if( preg_match( '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/', $hash ) )
		{
			$ph = new PasswordHash( 8, TRUE );
			$success = $ph->CheckPassword( $password, $member->conv_password ) ? TRUE : FALSE;
		}
		
		
		if ( $success === FALSE )
		{
			/* phpBB3 */
			$single_md5_pass = md5( $password );
			$itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
			
			if ( \strlen( $hash ) == 34 )
			{
				$success = ( $this->hashCryptPrivate( $password, $hash, $itoa64 ) === $hash ) ? TRUE : FALSE;
			}
			else
			{
				$success = ( \IPS\Login::compareHashes( $single_md5_pass, $hash ) ) ? TRUE : FALSE;
			}
		}
		
		/* phpBB2 */
		if ( ! $success )
		{
			$success = ( $this->hashCryptPrivate( $single_md5_hash, $hash, $itoa64 ) === $hash ? TRUE : FALSE );
		}
		
		return $success;
	}
	
	/**
	 * PHP Legacy (2.x)
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function phpbb_legacy( $member, $password )
	{
		return $this->phpbb( $member, $password );
	}
	
	/**
	 * Vanilla
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vanilla( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, md5( md5( str_replace( '&#39;', "'", html_entity_decode( $password ) ) ) . $member->misc ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Vbulletin 5.1
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vb51connect( $member, $password )
	{
		/* Which do we need to use? */
		$algo = explode( ':', $member->misc );
		
		switch( $algo[ 0 ] )
		{
			case 'blowfish':
				/* vBulletin uses PasswordHash, however they md5 once the password prior to checking */
				$md5_once_password = md5( $password );
				$ph = new PasswordHash( $algo[ 1 ], FALSE );
				return $ph->CheckPassword( $md5_once_password, $member->conv_password );
				break;
			
			case 'legacy':
				/* Legacy Passwords are stored in a format of HASH SALT so we need to explode on the space. */
				$token = explode( ' ', $member->conv_password );
				return $this->vbulletin( $member, $password, $token[ 1 ], $token[ 0 ] );
				break;
		}
	}
	
	/**
	 * Vbulletin 5
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vb5connect( $member, $password )
	{
		if ( \strpos( $member->misc, 'blowfish' ) === FALSE and \strpos( $member->misc, 'legacy' ) === FALSE )
		{
			return $this->vbulletin( $member, $password );
		}
		else
		{
			return $this->vb51connect( $member, $password );
		}
	}
	
	/**
	 * Vbulletin 4
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vbulletin( $member, $password, $salt = NULL, $hash = NULL )
	{
		if ( is_null( $salt ) )
		{
			$salt = $member->misc;
		}
		
		if ( is_null( $hash ) )
		{
			$hash = $member->conv_password;
		}
		
		$password = html_entity_decode( $password );
		if ( \IPS\Login::compareHashes( $hash, md5( md5( str_replace( '&#39;', "'", $password ) ) . $salt ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Vbulletin 3.8
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vbulletin_legacy( $member, $password )
	{
		return $this->vbulletin( $member, $password );
	}
	
	/**
	 * Vbulletin 3.6
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function vbulletin_legacy36( $member, $password )
	{
		return $this->vbulletin( $member, $password );
	}
	
	/**
	 * MyBB
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function mybb( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, md5( md5( $member->misc ) . md5( $password ) ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * SMF
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function smf( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, sha1( strtolower( $member->name ) . html_entity_decode( $password ) ) ) )
		{
			return TRUE;
		}
		else if ( \IPS\Login::compareHashes( $member->conv_password, sha1( strtolower( $member->name ) . $password ) ) )
		{
			return TRUE;
		}
		else
		{
			$ph = new PasswordHash(8, TRUE);
			return $ph->CheckPassword( strtolower( $member->name ) . $password, $member->conv_password ) ? TRUE : FALSE;
		}
	}
	
	/**
	 * SMF Legacy
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function smf_legacy( $member, $password )
	{
		if ( \IPS\Login::compareHashes( sha1( strtolower( $member->name ) . $password ), $member->conv_password ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Telligent
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function telligent_cs( $member, $password )
	{
		return $this->cs( $member, $password );
	}
	
	/**
	 * WoltLab 4.x
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function woltlab( $member, $password )
	{
		$testHash = FALSE;

		/* If it's not blowfish, then we don't have a salt for it. */
		if ( !preg_match( '/^\$2[ay]\$(0[4-9]|[1-2][0-9]|3[0-1])\$[a-zA-Z0-9.\/]{53}/', $member->conv_password ) )
		{
			$salt = mb_substr( $member->conv_password, 0, 29 );
			$testHash = crypt( crypt( $password, $salt ), $salt );
		}
		
		if (	$testHash AND \IPS\Login::compareHashes( $member->conv_password, $testHash ) )
		{
			return TRUE;
		}
		elseif ( $this->woltlab_legacy( $member, $password ) )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * WoltLab 3.x
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function woltlab_legacy( $member, $password )
	{
		if ( \IPS\Login::compareHashes( $member->conv_password, sha1( $member->misc . sha1( $member->misc . sha1( $password ) ) ) ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * WebWiz
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function webwiz( $member, $password )
	{
		$success = FALSE;
		
		if ( \IPS\Login::compareHashes( webWizAuth::HashEncode( $password . $member->misc ), $member->conv_password ) )
		{
			$success = TRUE;
		}
		
		return $success;
	}
	
	/**
	 * XenForo
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function xenforo( $member, $password )
	{
		$password = html_entity_decode( $password );
		
		// XF 1.2
		if ( $this->xenforo12( $member, $password ) )
		{
			return TRUE;
		}
		
		// XF 1.1
		if ( $this->xenforo11( $member, $password ) )
		{
			return TRUE;
		}
		
		// If they converted vB > XF > IPB then passwords may be in vB format still - try that.
		if ( $this->vbulletin( $member, $password ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * XenForo 1.2
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function xenforo12( $member, $password )
	{
		if ( !isset( \IPS\Settings::i()->xenforo_password_iterations ) )
		{
			\IPS\Settings::i()->xenforo_password_iterations = 10;
		}
		
		$ph = new PasswordHash( \IPS\Settings::i()->xenforo_password_iterations, false );
		
		if ( $ph->CheckPassword( $password, $member->conv_password ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * XenForo 1.1
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function xenforo11( $member, $password )
	{
		if ( extension_loaded( 'hash' ) )
		{
			$hashedPassword = hash( 'sha256', hash( 'sha256', $password ) . $member->misc );
		}
		else
		{
			$hashedPassword = sha1( sha1( $password ) . $member->misc );
		}
		
		if ( \IPS\Login::compareHashes( $member->conv_password, $hashedPassword ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * PHP Fusion
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function phpfusion( $member, $password )
	{
		return ( bool ) \IPS\Login::compareHashes( md5( md5( $password ) ), $member->conv_password );
	}
	
	/**
	 * fluxBB
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function fluxbb( $member, $password )
	{
		$success = false;
		$hash = $member->conv_password;
		
		if ( \strlen( $hash ) == 40 )
		{
			if ( \IPS\Login::compareHashes( $hash, sha1( $member->misc . sha1( $password ) ) ) )
			{
				$success = TRUE;
			}
			elseif ( \IPS\Login::compareHashes( $hash, sha1( $password ) ) )
			{
				$success = TRUE;
			}
		}
		else
		{
			$success = ( \IPS\Login::compareHashes( md5( $password ), $hash ) ) ? TRUE : FALSE;
		}
		
		return $success;
	}
	
	/**
	 * punBB
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function punbb( $member, $password )
	{
		$success = false;
		$hash = $member->conv_password;
		
		if ( \strlen( $hash ) == 40 )
		{
			if ( \IPS\Login::compareHashes( $hash, sha1( $member->misc . sha1( $password ) ) ) )
			{
				$success = TRUE;
			}
			elseif ( \IPS\Login::compareHashes( $hash, sha1( $password ) ) )
			{
				$success = TRUE;
			}
		}
		else
		{
			$success = ( md5( $password ) == $hash ) ? TRUE : FALSE;
		}
		
		return $success;
	}
	
	/**
	 * Simplepress Forum
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function simplepress( $member, $password )
	{
		return $this->wordpress( $member, $password );
	}
	
	/**
	 * UBB Threads
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function ubbthreads( $member, $password )
	{
		$hash = $member->members_pass_hash;
		$salt = $member->members_pass_salt;
		
		if ( \IPS\Login::compareHashes( md5( $password ), $hash ) )
		{
			return TRUE;
		}
		
		// Not using md5, UBB salts the password with the password
		// IPB already md5'd it though, *sigh*
		if ( \IPS\Login::compareHashes( md5( md5( $salt ) . crypt( $password, $password ) ), $hash ) )
		{
			return TRUE;
		}
		
		// Now standard IPB check.
		if ( \IPS\Login::compareHashes( md5( md5( $salt ) . md5( $password ) ), $hash ) )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Wordpress
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string	$password	Password from form
	 * @return	bool
	 */
	protected function wordpress( $member, $password )
	{
		$success = FALSE;
		
		// If the hash is still md5...
		if ( \strlen( $member->conv_password ) <= 32 )
		{
			$success = ( \IPS\Login::compareHashes( $member->conv_password, md5( $password ) ) ) ? TRUE : FALSE;
		}
		// New pass hash check
		else
		{
			// Init the pass class
			$ph = new PasswordHash;
			$ph->PasswordHash( 8, TRUE );
			
			// Check it
			$success = $ph->CheckPassword( $password, $member->conv_password ) ? TRUE : FALSE;
		}
		
		return $success;
	}
	
	/**
	 * Private crypt hashing for phpBB 3
	 *
	 * @access	public
	 * @param	string		Password
	 * @param	string 		Settings
	 * @param	string		Hash-lookup
	 * @return	string		phpBB3 password hash
	 */
	protected function hashCryptPrivate( $password, $setting, &$itoa64 )
	{
		$output	= '*';
	
		// Check for correct hash
		if ( \substr( $setting, 0, 3 ) != '$H$' )
		{
			return $output;
		}
	
		$count_log2 = \strpos( $itoa64, $setting[3] );
	
		if ( $count_log2 < 7 || $count_log2 > 30 )
		{
			return $output;
		}
	
		$count	= 1 << $count_log2;
		$salt	= \substr( $setting, 4, 8 );
	
		if ( \strlen($salt) != 8 )
		{
			return $output;
		}
	
		/**
		 * We're kind of forced to use MD5 here since it's the only
		 * cryptographic primitive available in all versions of PHP
		 * currently in use.  To implement our own low-level crypto
		 * in PHP would result in much worse performance and
		 * consequently in lower iteration counts and hashes that are
		 * quicker to crack (by non-PHP code).
		 */
		if ( PHP_VERSION >= 5 )
		{
			$hash = md5( $salt . $password, true );
	
			do
			{
				$hash = md5( $hash . $password, true );
			}
			while ( --$count );
		}
		else
		{
			$hash = pack( 'H*', md5( $salt . $password ) );
	
			do
			{
				$hash = pack( 'H*', md5( $hash . $password ) );
			}
			while ( --$count );
		}
	
		$output	= \substr( $setting, 0, 12 );
		$output	.= $this->_hashEncode64( $hash, 16, $itoa64 );
	
		return $output;
	}
	
	/**
	 * Private function to encode phpBB3 hash
	 *
	 * @access	protected
	 * @param	string		Input
	 * @param	count 		Iteration
	 * @param	string		Hash-lookup
	 * @return	string		phpbb3 password hash encoded bit
	 */
	protected function _hashEncode64($input, $count, &$itoa64)
	{
		$output	= '';
		$i		= 0;
	
		do
		{
			$value	= ord( $input[$i++] );
			$output	.= $itoa64[$value & 0x3f];
	
			if ( $i < $count )
			{
				$value |= ord($input[$i]) << 8;
			}
	
			$output .= $itoa64[($value >> 6) & 0x3f];
	
			if ( $i++ >= $count )
			{
				break;
			}
	
			if ( $i < $count )
			{
				$value |= ord($input[$i]) << 16;
			}
	
			$output .= $itoa64[($value >> 12) & 0x3f];
	
			if ($i++ >= $count)
			{
				break;
			}
	
			$output .= $itoa64[($value >> 18) & 0x3f];
		}
		while ( $i < $count );
	
		return $output;
	}
}

/**
 * Portable PHP password hashing framework.
 * @package phpass
 * @since 2.5
 * @version 0.1
 * @link http://www.openwall.com/phpass/
 */

#
# Written by Solar Designer <solar at openwall.com> in 2004-2006 and placed in
# the public domain.
#
# There's absolutely no warranty.
#
# Please be sure to update the Version line if you edit this file in any way.
# It is suggested that you leave the main version number intact, but indicate
# your project name (after the slash) and add your own revision information.
#
# Please do not change the "private" password hashing method implemented in
# here, thereby making your hashes incompatible.  However, if you must, please
# change the hash type identifier (the "$P$") to something different.
#
# Obviously, since this code is in the public domain, the above are not
# requirements (there can be none), but merely suggestions.
#


// This is needed for the SimplePress Forum login and for any other login based on this class (wordpress, bbpress, etc)
class PasswordHash
{
	var $itoa64;
	var $iteration_count_log2;
	var $portable_hashes;
	var $random_state;
	function PasswordHash( $iteration_count_log2, $portable_hashes )
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		
		if ( $iteration_count_log2 < 4 || $iteration_count_log2 > 31 )
			$iteration_count_log2 = 8;
		$this->iteration_count_log2 = $iteration_count_log2;
		
		$this->portable_hashes = $portable_hashes;
		
		$this->random_state = microtime() . ( function_exists( 'getmypid' ) ? getmypid() : '' ) . uniqid( rand(), TRUE );
	}
	function get_random_bytes( $count )
	{
		$output = '';
		if ( ( $fh = @fopen( '/dev/urandom', 'rb' ) ) )
		{
			$output = fread( $fh, $count );
			fclose( $fh );
		}
		
		if ( \strlen( $output ) < $count )
		{
			$output = '';
			for( $i = 0 ; $i < $count ; $i += 16 )
			{
				$this->random_state = md5( microtime() . $this->random_state );
				$output .= pack( 'H*', md5( $this->random_state ) );
			}
			$output = \substr( $output, 0, $count );
		}
		
		return $output;
	}
	function encode64( $input, $count )
	{
		$output = '';
		$i = 0;
		do
		{
			$value = ord( $input[ $i ++ ] );
			$output .= $this->itoa64[ $value & 0x3f ];
			if ( $i < $count )
				$value |= ord( $input[ $i ] ) << 8;
			$output .= $this->itoa64[ ( $value >> 6 ) & 0x3f ];
			if ( $i ++ >= $count )
				break;
			if ( $i < $count )
				$value |= ord( $input[ $i ] ) << 16;
			$output .= $this->itoa64[ ( $value >> 12 ) & 0x3f ];
			if ( $i ++ >= $count )
				break;
			$output .= $this->itoa64[ ( $value >> 18 ) & 0x3f ];
		}
		while ( $i < $count );
		
		return $output;
	}
	function gensalt_private( $input )
	{
		$output = '$P$';
		$output .= $this->itoa64[ min( $this->iteration_count_log2 + ( ( PHP_VERSION >= '5' ) ? 5 : 3 ), 30 ) ];
		$output .= $this->encode64( $input, 6 );
		
		return $output;
	}
	function crypt_private( $password, $setting )
	{
		$output = '*0';
		if ( \substr( $setting, 0, 2 ) == $output )
			$output = '*1';
		
		if ( \substr( $setting, 0, 3 ) != '$P$' )
			return $output;
		
		$count_log2 = \strpos( $this->itoa64, $setting[ 3 ] );
		if ( $count_log2 < 7 || $count_log2 > 30 )
			return $output;
		
		$count = 1 << $count_log2;
		
		$salt = \substr( $setting, 4, 8 );
		if ( \strlen( $salt ) != 8 )
			return $output;
			
			# We're kind of forced to use MD5 here since it's the only
			# cryptographic primitive available in all versions of PHP
			# currently in use.  To implement our own low-level crypto
			# in PHP would result in much worse performance and
			# consequently in lower iteration counts and hashes that are
			# quicker to crack (by non-PHP code).
		if ( PHP_VERSION >= '5' )
		{
			$hash = md5( $salt . $password, TRUE );
			do
			{
				$hash = md5( $hash . $password, TRUE );
			}
			while ( -- $count );
		}
		else
		{
			$hash = pack( 'H*', md5( $salt . $password ) );
			do
			{
				$hash = pack( 'H*', md5( $hash . $password ) );
			}
			while ( -- $count );
		}
		
		$output = \substr( $setting, 0, 12 );
		$output .= $this->encode64( $hash, 16 );
		
		return $output;
	}
	function gensalt_extended( $input )
	{
		$count_log2 = min( $this->iteration_count_log2 + 8, 24 );
		# This should be odd to not reveal weak DES keys, and the
		# maximum valid value is (2**24 - 1) which is odd anyway.
		$count = ( 1 << $count_log2 ) - 1;
		
		$output = '_';
		$output .= $this->itoa64[ $count & 0x3f ];
		$output .= $this->itoa64[ ( $count >> 6 ) & 0x3f ];
		$output .= $this->itoa64[ ( $count >> 12 ) & 0x3f ];
		$output .= $this->itoa64[ ( $count >> 18 ) & 0x3f ];
		
		$output .= $this->encode64( $input, 3 );
		
		return $output;
	}
	function gensalt_blowfish( $input )
	{
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits.  While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		
		$output = '$2a$';
		$output .= chr( ord( '0' ) + $this->iteration_count_log2 / 10 );
		$output .= chr( ord( '0' ) + $this->iteration_count_log2 % 10 );
		$output .= '$';
		
		$i = 0;
		do
		{
			$c1 = ord( $input[ $i ++ ] );
			$output .= $itoa64[ $c1 >> 2 ];
			$c1 = ( $c1 & 0x03 ) << 4;
			if ( $i >= 16 )
			{
				$output .= $itoa64[ $c1 ];
				break;
			}
			
			$c2 = ord( $input[ $i ++ ] );
			$c1 |= $c2 >> 4;
			$output .= $itoa64[ $c1 ];
			$c1 = ( $c2 & 0x0f ) << 2;
			
			$c2 = ord( $input[ $i ++ ] );
			$c1 |= $c2 >> 6;
			$output .= $itoa64[ $c1 ];
			$output .= $itoa64[ $c2 & 0x3f ];
		}
		while ( 1 );
		
		return $output;
	}
	function HashPassword( $password )
	{
		$random = '';
		
		if ( CRYPT_BLOWFISH == 1 && ! $this->portable_hashes )
		{
			$random = $this->get_random_bytes( 16 );
			$hash = crypt( $password, $this->gensalt_blowfish( $random ) );
			if ( \strlen( $hash ) == 60 )
				return $hash;
		}
		
		if ( CRYPT_EXT_DES == 1 && ! $this->portable_hashes )
		{
			if ( \strlen( $random ) < 3 )
				$random = $this->get_random_bytes( 3 );
			$hash = crypt( $password, $this->gensalt_extended( $random ) );
			if ( \strlen( $hash ) == 20 )
				return $hash;
		}
		
		if ( \strlen( $random ) < 6 )
			$random = $this->get_random_bytes( 6 );
		$hash = $this->crypt_private( $password, $this->gensalt_private( $random ) );
		if ( \strlen( $hash ) == 34 )
			return $hash;
			
			# Returning '*' on error is safe here, but would _not_ be safe
			# in a crypt(3)-like function used _both_ for generating new
			# hashes and for validating passwords against existing hashes.
		return '*';
	}
	function CheckPassword( $password, $stored_hash )
	{
		$hash = $this->crypt_private( $password, $stored_hash );
		if ( $hash[ 0 ] == '*' )
			$hash = crypt( $password, $stored_hash );
		
		return $hash == $stored_hash;
	}
}
class webWizAuth
{
	static public function HashEncode( $strSecret )
	{
		if ( \strlen( $strSecret ) == 0 || \strlen( $strSecret ) >= pow( 2, 61 ) )
		{
			return "0000000000000000000000000000000000000000";
		}
		
		//Initial Hex words are used for encoding Digest.
		//These can be any valid 8-digit hex value (0 to F)
		$strH[ 0 ] = "FB0C14C2";
		$strH[ 1 ] = "9F00AB2E";
		$strH[ 2 ] = "991FFA67";
		$strH[ 3 ] = "76FA2C3F";
		$strH[ 4 ] = "ADE426FA";
		
		for( $intPos = 1 ; $intPos <= \strlen( $strSecret ) ; $intPos = $intPos + 56 )
		{
			$strEncode = \substr( $strSecret, $intPos - 1, 56 ); //get 56 character chunks
			$strEncode = self::WordToBinary( $strEncode ); //convert to binary
			$strEncode = self::PadBinary( $strEncode ); //make it 512 bites
			$strEncode = self::BlockToHex( $strEncode ); //convert to hex value
			

			//Encode the hex value using the previous runs digest
			//If it is the first run then use the initial values above
			$strEncode = self::DigestHex( $strEncode, $strH[ 0 ], $strH[ 1 ], $strH[ 2 ], $strH[ 3 ], $strH[ 4 ] );
			
			//Combine the old digest with the new digest
			$strH[ 0 ] = self::HexAdd( \substr( $strEncode, 0, 8 ), $strH[ 0 ] );
			$strH[ 1 ] = self::HexAdd( \substr( $strEncode, 8, 8 ), $strH[ 1 ] );
			$strH[ 2 ] = self::HexAdd( \substr( $strEncode, 16, 8 ), $strH[ 2 ] );
			$strH[ 3 ] = self::HexAdd( \substr( $strEncode, 24, 8 ), $strH[ 3 ] );
			$strH[ 4 ] = self::HexAdd( \substr( $strEncode, \strlen( $strEncode ) - ( 8 ) ), $strH[ 4 ] );
		}
		
		//This is the final Hex Digest
		$function_ret = $strH[ 0 ] . $strH[ 1 ] . $strH[ 2 ] . $strH[ 3 ] . $strH[ 4 ];
		
		return $function_ret;
	}
	static protected function HexToBinary( $btHex )
	{
		switch( $btHex )
		{
			case "0" :
				$function_ret = "0000";
				break;
			case "1" :
				$function_ret = "0001";
				break;
			case "2" :
				$function_ret = "0010";
				break;
			case "3" :
				$function_ret = "0011";
				break;
			case "4" :
				$function_ret = "0100";
				break;
			case "5" :
				$function_ret = "0101";
				break;
			case "6" :
				$function_ret = "0110";
				break;
			case "7" :
				$function_ret = "0111";
				break;
			case "8" :
				$function_ret = "1000";
				break;
			case "9" :
				$function_ret = "1001";
				break;
			case "A" :
				$function_ret = "1010";
				break;
			case "B" :
				$function_ret = "1011";
				break;
			case "C" :
				$function_ret = "1100";
				break;
			case "D" :
				$function_ret = "1101";
				break;
			case "E" :
				$function_ret = "1110";
				break;
			case "F" :
				$function_ret = "1111";
				break;
			default :
				
				$function_ret = "2222";
				break;
		}
		return $function_ret;
	}
	static protected function BinaryToHex( $strBinary )
	{
		switch( $strBinary )
		{
			case "0000" :
				$function_ret = "0";
				break;
			case "0001" :
				$function_ret = "1";
				break;
			case "0010" :
				$function_ret = "2";
				break;
			case "0011" :
				$function_ret = "3";
				break;
			case "0100" :
				$function_ret = "4";
				break;
			case "0101" :
				$function_ret = "5";
				break;
			case "0110" :
				$function_ret = "6";
				break;
			case "0111" :
				$function_ret = "7";
				break;
			case "1000" :
				$function_ret = "8";
				break;
			case "1001" :
				$function_ret = "9";
				break;
			case "1010" :
				$function_ret = "A";
				break;
			case "1011" :
				$function_ret = "B";
				break;
			case "1100" :
				$function_ret = "C";
				break;
			case "1101" :
				$function_ret = "D";
				break;
			case "1110" :
				$function_ret = "E";
				break;
			case "1111" :
				$function_ret = "F";
				break;
			default :
				
				$function_ret = "Z";
				break;
		}
		return $function_ret;
	}
	static protected function WordToBinary( $strWord )
	{
		$strBinary = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strWord ) ; $intPos = $intPos + 1 )
		{
			$strTemp = \substr( $strWord, intval( $intPos ) - 1, 1 );
			$strBinary = $strBinary . self::IntToBinary( ord( $strTemp ) );
		}
		
		return $strBinary;
	}
	static protected function IntToBinary( $intNum )
	{
		$intNew = $intNum;
		$strBinary = '';
		while ( $intNew > 1 )
		{
			$dblNew = doubleval( $intNew ) / 2;
			$intNew = round( doubleval( $dblNew ) - 0.1, 0 );
			if ( doubleval( $dblNew ) == doubleval( $intNew ) )
			{
				$strBinary = "0" . $strBinary;
			}
			else
			{
				$strBinary = "1" . $strBinary;
			}
		}
		
		$strBinary = $intNew . $strBinary;
		$intTemp = \strlen( $strBinary ) % 8;
		
		for( $intNew = $intTemp ; $intNew <= 7 ; $intNew = $intNew + 1 )
		{
			$strBinary = "0" . $strBinary;
		}
		
		return $strBinary;
	}
	static protected function PadBinary( $strBinary )
	{
		$intLen = \strlen( $strBinary );
		$strBinary = $strBinary . "1";
		
		for( $intPos = \strlen( $strBinary ) ; $intPos <= 447 ; $intPos = $intPos + 1 )
		{
			$strBinary = $strBinary . "0";
		}
		
		$strTemp = self::IntToBinary( $intLen );
		
		for( $intPos = \strlen( $strTemp ) ; $intPos <= 63 ; $intPos = $intPos + 1 )
		{
			$strTemp = "0" . $strTemp;
		}
		
		return $strBinary . $strTemp;
	}
	static protected function BlockToHex( $strBinary )
	{
		$strHex = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strBinary ) ; $intPos = $intPos + 4 )
		{
			$strHex = $strHex . self::BinaryToHex( \substr( $strBinary, $intPos - 1, 4 ) );
		}
		return $strHex;
	}
	static protected function DigestHex( $strHex, $strH0, $strH1, $strH2, $strH3, $strH4 )
	{
		//Constant hex words are used for encryption, these can be any valid 8 digit hex value
		$strK[ 0 ] = "5A827999";
		$strK[ 1 ] = "6ED9EBA1";
		$strK[ 2 ] = "8F1BBCDC";
		$strK[ 3 ] = "CA62C1D6";
		
		//Hex words are used in the encryption process, these can be any valid 8 digit hex value
		$strH[ 0 ] = $strH0;
		$strH[ 1 ] = $strH1;
		$strH[ 2 ] = $strH2;
		$strH[ 3 ] = $strH3;
		$strH[ 4 ] = $strH4;
		
		//divide the Hex block into 16 hex words
		for( $intPos = 0 ; $intPos <= ( \strlen( $strHex ) / 8 ) - 1 ; $intPos = $intPos + 1 )
		{
			$strWords[ intval( $intPos ) ] = \substr( $strHex, ( intval( $intPos ) * 8 ) + 1 - 1, 8 );
		}
		
		//encode the Hex words using the constants above
		//innitialize 80 hex word positions
		for( $intPos = 16 ; $intPos <= 79 ; $intPos = $intPos + 1 )
		{
			$strTemp = $strWords[ intval( $intPos ) - 3 ];
			$strTemp1 = self::HexBlockToBinary( $strTemp );
			$strTemp = $strWords[ intval( $intPos ) - 8 ];
			$strTemp2 = self::HexBlockToBinary( $strTemp );
			$strTemp = $strWords[ intval( $intPos ) - 14 ];
			$strTemp3 = self::HexBlockToBinary( $strTemp );
			$strTemp = $strWords[ intval( $intPos ) - 16 ];
			$strTemp4 = self::HexBlockToBinary( $strTemp );
			$strTemp = self::BinaryXOR( $strTemp1, $strTemp2 );
			$strTemp = self::BinaryXOR( $strTemp, $strTemp3 );
			$strTemp = self::BinaryXOR( $strTemp, $strTemp4 );
			$strWords[ intval( $intPos ) ] = self::BlockToHex( self::BinaryShift( $strTemp, 1 ) );
		}
		
		//initialize the changing word variables with the initial word variables
		$strA[ 0 ] = $strH[ 0 ];
		$strA[ 1 ] = $strH[ 1 ];
		$strA[ 2 ] = $strH[ 2 ];
		$strA[ 3 ] = $strH[ 3 ];
		$strA[ 4 ] = $strH[ 4 ];
		
		//Main encryption loop on all 80 hex word positions
		for( $intPos = 0 ; $intPos <= 79 ; $intPos = $intPos + 1 )
		{
			$strTemp = self::BinaryShift( self::HexBlockToBinary( $strA[ 0 ] ), 5 );
			$strTemp1 = self::HexBlockToBinary( $strA[ 3 ] );
			$strTemp2 = self::HexBlockToBinary( $strWords[ intval( $intPos ) ] );
			
			switch( $intPos )
			{
				case 0 :
				case 1 :
				case 2 :
				case 3 :
				case 4 :
				case 5 :
				case 6 :
				case 7 :
				case 8 :
				case 9 :
				case 10 :
				case 11 :
				case 12 :
				case 13 :
				case 14 :
				case 15 :
				case 16 :
				case 17 :
				case 18 :
				case 19 :
					$strTemp3 = self::HexBlockToBinary( $strK[ 0 ] );
					$strTemp4 = self::BinaryOR( self::BinaryAND( self::HexBlockToBinary( $strA[ 1 ] ), self::HexBlockToBinary( $strA[ 2 ] ) ), self::BinaryAND( self::BinaryNOT( self::HexBlockToBinary( $strA[ 1 ] ) ), self::HexBlockToBinary( $strA[ 3 ] ) ) );
					break;
				case 20 :
				case 21 :
				case 22 :
				case 23 :
				case 24 :
				case 25 :
				case 26 :
				case 27 :
				case 28 :
				case 29 :
				case 30 :
				case 31 :
				case 32 :
				case 33 :
				case 34 :
				case 35 :
				case 36 :
				case 37 :
				case 38 :
				case 39 :
					$strTemp3 = self::HexBlockToBinary( $strK[ 1 ] );
					$strTemp4 = self::BinaryXOR( self::BinaryXOR( self::HexBlockToBinary( $strA[ 1 ] ), self::HexBlockToBinary( $strA[ 2 ] ) ), self::HexBlockToBinary( $strA[ 3 ] ) );
					break;
				case 40 :
				case 41 :
				case 42 :
				case 43 :
				case 44 :
				case 45 :
				case 46 :
				case 47 :
				case 48 :
				case 49 :
				case 50 :
				case 51 :
				case 52 :
				case 53 :
				case 54 :
				case 55 :
				case 56 :
				case 57 :
				case 58 :
				case 59 :
					$strTemp3 = self::HexBlockToBinary( $strK[ 2 ] );
					$strTemp4 = self::BinaryOR( self::BinaryOR( self::BinaryAND( self::HexBlockToBinary( $strA[ 1 ] ), self::HexBlockToBinary( $strA[ 2 ] ) ), self::BinaryAND( self::HexBlockToBinary( $strA[ 1 ] ), self::HexBlockToBinary( $strA[ 3 ] ) ) ), self::BinaryAND( self::HexBlockToBinary( $strA[ 2 ] ), self::HexBlockToBinary( $strA[ 3 ] ) ) );
					break;
				case 60 :
				case 61 :
				case 62 :
				case 63 :
				case 64 :
				case 65 :
				case 66 :
				case 67 :
				case 68 :
				case 69 :
				case 70 :
				case 71 :
				case 72 :
				case 73 :
				case 74 :
				case 75 :
				case 76 :
				case 77 :
				case 78 :
				case 79 :
					$strTemp3 = self::HexBlockToBinary( $strK[ 3 ] );
					$strTemp4 = self::BinaryXOR( self::BinaryXOR( self::HexBlockToBinary( $strA[ 1 ] ), self::HexBlockToBinary( $strA[ 2 ] ) ), self::HexBlockToBinary( $strA[ 3 ] ) );
					break;
			}
			
			$strTemp = self::BlockToHex( $strTemp );
			$strTemp1 = self::BlockToHex( $strTemp1 );
			$strTemp2 = self::BlockToHex( $strTemp2 );
			$strTemp3 = self::BlockToHex( $strTemp3 );
			$strTemp4 = self::BlockToHex( $strTemp4 );
			
			$strTemp = self::HexAdd( $strTemp, $strTemp1 );
			$strTemp = self::HexAdd( $strTemp, $strTemp2 );
			$strTemp = self::HexAdd( $strTemp, $strTemp3 );
			$strTemp = self::HexAdd( $strTemp, $strTemp4 );
			
			$strA[ 4 ] = $strA[ 3 ];
			$strA[ 3 ] = $strA[ 2 ];
			$strA[ 2 ] = self::BlockToHex( self::BinaryShift( self::HexBlockToBinary( $strA[ 1 ] ), 30 ) );
			$strA[ 1 ] = $strA[ 0 ];
			$strA[ 0 ] = $strTemp;
		}
		
		//Concatenate the final Hex Digest
		return $strA[ 0 ] . $strA[ 1 ] . $strA[ 2 ] . $strA[ 3 ] . $strA[ 4 ];
	}
	static protected function HexAdd( $strHex1, $strHex2 )
	{
		$n1 = hexdec( $strHex1 );
		$n2 = hexdec( $strHex2 );
		$sum = $n1 + $n2;
		return sprintf( "%08X", $sum );
	}
	static protected function BinaryShift( $strBinary, $intPos )
	{
		return \substr( $strBinary, \strlen( $strBinary ) - ( \strlen( $strBinary ) - intval( $intPos ) ) ) . \substr( $strBinary, 0, intval( $intPos ) );
	}
	
	// Function performs an exclusive or function on each position of two binary values
	static protected function BinaryXOR( $strBin1, $strBin2 )
	{
		$strBinaryFinal = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strBin1 ) ; $intPos = $intPos + 1 )
		{
			switch( \substr( $strBin1, intval( $intPos ) - 1, 1 ) )
			{
				case \substr( $strBin2, intval( $intPos ) - 1, 1 ) :
					$strBinaryFinal = $strBinaryFinal . "0";
					break;
				default :
					$strBinaryFinal = $strBinaryFinal . "1";
					break;
			}
		}
		
		return $strBinaryFinal;
	}
	
	// Function performs an inclusive or function on each position of two binary values
	static protected function BinaryOR( $strBin1, $strBin2 )
	{
		$strBinaryFinal = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strBin1 ) ; $intPos = $intPos + 1 )
		{
			if ( \substr( $strBin1, intval( $intPos ) - 1, 1 ) == "1" || \substr( $strBin2, intval( $intPos ) - 1, 1 ) == "1" )
			{
				$strBinaryFinal = $strBinaryFinal . "1";
			}
			else
			{
				$strBinaryFinal = $strBinaryFinal . "0";
			}
		}
		
		return $strBinaryFinal;
	}
	
	// Function performs an AND function on each position of two binary values
	static protected function BinaryAND( $strBin1, $strBin2 )
	{
		$strBinaryFinal = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strBin1 ) ; $intPos = $intPos + 1 )
		{
			if ( \substr( $strBin1, intval( $intPos ) - 1, 1 ) == "1" && \substr( $strBin2, intval( $intPos ) - 1, 1 ) == "1" )
			{
				$strBinaryFinal = $strBinaryFinal . "1";
			}
			else
			{
				$strBinaryFinal = $strBinaryFinal . "0";
			}
		}
		
		return $strBinaryFinal;
	}
	
	// Function makes each position of a binary value from 1 to 0 and 0 to 1
	static protected function BinaryNOT( $strBinary )
	{
		$strBinaryFinal = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strBinary ) ; $intPos = $intPos + 1 )
		{
			if ( \substr( $strBinary, intval( $intPos ) - 1, 1 ) == "1" )
			{
				$strBinaryFinal = $strBinaryFinal . "0";
			}
			else
			{
				$strBinaryFinal = $strBinaryFinal . "1";
			}
		}
		
		return $strBinaryFinal;
	}
	
	// Function Converts a 8 digit/32 bit hex value to its 32 bit binary equivalent
	static protected function HexBlockToBinary( $strHex )
	{
		$strTemp = '';
		for( $intPos = 1 ; $intPos <= \strlen( $strHex ) ; $intPos = $intPos + 1 )
		{
			$strTemp = $strTemp . self::HexToBinary( \substr( $strHex, intval( $intPos ) - 1, 1 ) );
		}
		
		return $strTemp;
	}
}