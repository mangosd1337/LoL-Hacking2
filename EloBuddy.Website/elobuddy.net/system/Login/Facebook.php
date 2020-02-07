<?php
/**
 * @brief		Facebook Login Handler
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
 * Facebook Login Handler
 */
class _Facebook extends LoginAbstract
{
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'facebook-square';
	
	/** 
	 * @brief	Share Service
	 */
	public static $shareService = 'facebook';
	
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
		$url = \IPS\Http\Url::internal( 'applications/core/interface/facebook/auth.php', 'none' );
		if ( $destination )
		{
			$url = $url->setQueryString( 'ref', base64_encode( $destination ) );
		}

		if ( $ucp )
		{
			$state = "ucp-" . \IPS\Session::i()->csrfKey;
		}
		else
		{
			$state = \IPS\Dispatcher::i()->controllerLocation . "-" . \IPS\Session::i()->csrfKey;
		}

		$scope = 'email';

		if ( \IPS\Settings::i()->profile_comments )
		{
			if ( isset( $this->settings['allow_status_import'] ) and $this->settings['allow_status_import'] )
			{
				$scope .= ',user_posts';
			}
			
			if ( isset( $this->settings['autoshare'] ) and $this->settings['autoshare'] )
			{			
				$scope .= ',publish_actions';
			}
		}
				
		return \IPS\Theme::i()->getTemplate( 'login', 'core', 'global' )->facebook( "https://www.facebook.com/dialog/oauth?client_id={$this->settings['app_id']}&amp;scope={$scope}&amp;redirect_uri=".urlencode( $url ) . "&amp;state={$state}"  );
	}
	
	/**
	 * Authenticate
	 *
	 * @param	string			$url	The URL for the login page
	 * @param	\IPS\Member		$member	If we want to integrate this login method with an existing member, provide the member object
	 * @return	\IPS\Member
	 * @throws	\IPS\Login\Exception
	 */
	public function authenticate( $url, $member=NULL )
	{
		$url = $url->setQueryString( 'loginProcess', 'facebook' );
		
		try
		{
			/* CSRF Check */
			if ( \IPS\Request::i()->state !== \IPS\Session::i()->csrfKey )
			{
				throw new \IPS\Login\Exception( 'CSRF_FAIL', \IPS\Login\Exception::INTERNAL_ERROR );
			}
			
			/* Get a token */
			try
			{
				$redirectUri = \IPS\Http\Url::internal( 'applications/core/interface/facebook/auth.php', 'none' );
				if ( isset( \IPS\Request::i()->ref ) )
				{
					$redirectUri = $redirectUri->setQueryString( 'ref', \IPS\Request::i()->ref );
				}				
				
				$response = \IPS\Http\Url::external( "https://graph.facebook.com/oauth/access_token" )->request()->post( array(
					'client_id'		=> $this->settings['app_id'],
					'redirect_uri'	=> (string) $redirectUri,
					'client_secret'	=> $this->settings['app_secret'],
					'code'			=> \IPS\Request::i()->code
				) )->decodeQueryString('access_token');
			}
			catch( \RuntimeException $e )
			{
				throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
			}
			
			/* Now exchange it for a one that will last a bit longer in case the user wants to use syncing */
			try
			{
				$response = \IPS\Http\Url::external( "https://graph.facebook.com/oauth/access_token" )->request()->post( array(
					'grant_type'		=> 'fb_exchange_token',
					'client_id'			=> $this->settings['app_id'],
					'client_secret'		=> $this->settings['app_secret'],
					'fb_exchange_token'	=> $response['access_token']
				) )->decodeQueryString('access_token');				
			}
			catch( \RuntimeException $e )
			{
				throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
			}

			/* Set up appsecret_proof https://developers.facebook.com/docs/graph-api/securing-requests */
			$appSecretProof = hash_hmac( 'sha256', $response['access_token'], $this->settings['app_secret'] );

			/* Get the user data */
			$userData = \IPS\Http\Url::external( "https://graph.facebook.com/me?fields=email,id,name&access_token={$response['access_token']}&appsecret_proof={$appSecretProof}" )->request()->get()->decodeJson();

			/* Find or create member */
			$member = $this->createOrUpdateAccount( $member ?: \IPS\Member::load( $userData['id'], 'fb_uid' ), array(
				'fb_uid'	=> $userData['id'],
				'fb_token'	=> $response['access_token']
			), $this->settings['real_name'] ? $userData['name'] : NULL, ( isset( $userData['email'] ) AND $userData['email'] ) ? $userData['email'] : NULL, $response['access_token'], array( 'photo' => TRUE, 'cover' => TRUE, 'status' => '' ) );
			
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
		$userData = \IPS\Http\Url::external( "https://graph.facebook.com/me?access_token={$details}" )->request()->get()->decodeJson();
		$member->fb_uid = $userData['id'];
		$member->fb_token = $details;
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
				'link'		=> \IPS\Http\Url::ips( 'docs/login_facebook' ),
				'target'	=> '_blank',
				'class'		=> ''
			),
		);
		
		
		$return = array(
			'app_id'				=> new \IPS\Helpers\Form\Text( 'login_facebook_app', ( isset( $this->settings['app_id'] ) ) ? $this->settings['app_id'] : '', TRUE ),
			'app_secret'			=> new \IPS\Helpers\Form\Text( 'login_facebook_secret', ( isset( $this->settings['app_secret'] ) ) ? $this->settings['app_secret'] : '', TRUE ),
			'real_name'				=> new \IPS\Helpers\Form\YesNo( 'login_real_name', ( isset( $this->settings['real_name'] ) ) ? $this->settings['real_name'] : FALSE, TRUE )
		);
		
		if ( \IPS\Settings::i()->profile_comments )
		{
			$return['allow_status_import'] = new \IPS\Helpers\Form\YesNo( 'login_facebook_allow_status_import', ( isset( $this->settings['allow_status_import'] ) ) ? $this->settings['allow_status_import'] : FALSE, FALSE );
		}
		
		return $return;
	}
	
	/**
	 * Test Settings
	 *
	 * @return	bool
	 * @throws	\InvalidArgumentException
	 */
	public function testSettings()
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
		return ( $member->fb_uid and $member->fb_token );
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