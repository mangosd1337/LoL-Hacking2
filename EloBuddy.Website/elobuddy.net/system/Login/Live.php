<?php
/**
 * @brief		Windows Live Login Handler
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
 * Windows Live Login Handler
 */
class _Live extends LoginAbstract
{
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'windows';

	/**
	 * Get Form
	 *
	 * @param	\IPS\Http\Url	$url			The URL for the login page
	 * @param	bool			$ucp			Is UCP? (as opposed to login form)
	 * @param	\IPS\Http\Url	$destination	The URL to redirect to after a successful login
	 * @return	string
	 */
	public function loginForm( \IPS\Http\Url $url, $ucp = FALSE, \IPS\Http\Url $destination = NULL )
	{
		return \IPS\Theme::i()->getTemplate( 'login', 'core', 'global' )->live( (string) \IPS\Http\Url::external( 'https://login.live.com/oauth20_authorize.srf' )->setQueryString( array(
			'client_id'		=> $this->settings['client_id'],
			'scope'			=> 'wl.signin wl.emails wl.offline_access',
			'response_type'	=> 'code',
			'redirect_uri'	=> (string) \IPS\Http\Url::internal( 'applications/core/interface/microsoft/auth.php', 'none' ),
			'state'			=> ( $ucp ? 'ucp' : \IPS\Dispatcher::i()->controllerLocation ) . '-' . \IPS\Session::i()->csrfKey . '-' . ( $destination ? base64_encode( $destination ) : '' )
		) ) );
	}
	
	/**
	 * Authenticate
	 *
	 * @param	string	$url	The URL for the login page
	 * @param	\IPS\Member		$member	If we want to integrate this login method with an existing member, provide the member object
	 * @return	\IPS\Member
	 * @throws	\IPS\Login\Exception
	 */
	public function authenticate( $url, $member=NULL )
	{		
		try
		{
			/* CSRF Check */
			if ( \IPS\Request::i()->state !== \IPS\Session::i()->csrfKey )
			{
				throw new \IPS\Login\Exception( 'CSRF_FAIL', \IPS\Login\Exception::INTERNAL_ERROR );
			}
			
			/* Send HTTP request */
			$response = \IPS\Http\Url::external( 'https://login.live.com/oauth20_token.srf' )->request()->post( array(
				'client_id'		=> $this->settings['client_id'],
				'redirect_uri'	=> (string) \IPS\Http\Url::internal( 'applications/core/interface/microsoft/auth.php', 'none' ),
				'client_secret'	=> $this->settings['client_secret'],
				'code'			=> \IPS\Request::i()->code,
				'grant_type'	=> 'authorization_code'
			) )->decodeJson();
						
			/* Check the response */
			if ( isset( $response['error'] ) or !isset( $response['access_token'] ) )
			{
				throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
			}
						
			/* Get user data */
			$userData = \IPS\Http\Url::external( "https://apis.live.net/v5.0/me?access_token={$response['access_token']}" )->request()->get()->decodeJson();
			
			/* Find or create member */
			$member = $this->createOrUpdateAccount( $member ?: \IPS\Member::load( $userData['id'], 'live_id' ), array(
				'live_id'		=> $userData['id'],
				'live_token'	=> $response['refresh_token'],
			), $this->settings['real_name'] ? $userData['name'] : NULL, $userData['emails']['preferred'], $response['access_token'], array( 'photo' => TRUE, 'cover' => FALSE, 'status' => '' ), 'IPS\core\ProfileSync\Microsoft' );
									
			/* Return */
			return $member;
		}
		catch ( \IPS\Http\Request\Exception $e )
   		{
	   		throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
   		}
	}
	
	/**
	 * Link Account
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	mixed		$details	Details as they were passed to the exception thrown in authenticate()
	 * @return	void
	 */
	public static function link( \IPS\Member $member, $details )
	{
		$userData = \IPS\Http\Url::external( "https://apis.live.net/v5.0/me?access_token={$details}" )->request()->get()->decodeJson();
		$member->live_id = $userData['id'];
		$member->live_token = $details;			
		$member->save();
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
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'help',
				'icon'		=> 'question-circle',
				'link'		=> \IPS\Http\Url::ips( 'docs/login_live' ),
				'target'	=> '_blank',
				'class'		=> ''
			),
		);
		
		return array(
			'client_id'		=> new \IPS\Helpers\Form\Text( 'login_live_client', ( isset( $this->settings['client_id'] ) ) ? $this->settings['client_id'] : '', TRUE ),
			'client_secret'	=> new \IPS\Helpers\Form\Text( 'login_live_secret', ( isset( $this->settings['client_secret'] ) ) ? $this->settings['client_secret'] : '', TRUE ),
			'real_name'		=> new \IPS\Helpers\Form\YesNo( 'login_real_name', ( isset( $this->settings['real_name'] ) ) ? $this->settings['real_name'] : FALSE, TRUE )
		);
	}
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	\IPS\Http\Request\Exception
	 * @throws	\UnexpectedValueException	If response code is not 200
	 */
	public function testSettings()
	{
		try
		{
			$response = \IPS\Http\Url::external( "https://login.live.com/oauth20_authorize.srf?client_id={$this->settings['client_id']}&scope=wl.signin%20wl.emails&response_type=code&redirect_uri=" )->request()->get();
			if ( $response->httpResponseCode != 200 )
			{
				throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('login_3p_bad', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('login_handler_Live') ) ) ) );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('login_3p_bad', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('login_handler_Live') ) ) ) );
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
		return ( $member->live_id and $member->live_token );
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
	
}