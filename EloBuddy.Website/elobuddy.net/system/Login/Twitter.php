<?php
/**
 * @brief		Twitter Login Handler
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
 * Twitter Login Handler
 */
class _Twitter extends LoginAbstract
{
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'twitter';
	
	/** 
	 * @brief	Share Service
	 */
	public static $shareService = 'twitter';
	
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
		$url = $url->setQueryString( 'loginProcess', 'twitter' );
		if ( $destination )
		{
			$url = $url->setQueryString( 'ref', base64_encode( $destination ) );
		}
		
		return \IPS\Theme::i()->getTemplate( 'login', 'core', 'global' )->twitter( $url );
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
		if ( isset( \IPS\Request::i()->denied ) )
		{
			throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
		}
		
		try
		{
			/* Get a request token */
			if ( !isset( \IPS\Request::i()->oauth_token ) )
			{
				$callback = $url->setQueryString( 'loginProcess', 'twitter' )->setQueryString( 'csrf', \IPS\Session::i()->csrfKey );
				if ( isset( \IPS\Request::i()->ref ) )
				{
					$callback = $callback->setQueryString( 'ref', \IPS\Request::i()->ref );
				}
				
				$response = $this->sendRequest( 'get', 'https://api.twitter.com/oauth/request_token', array( 'oauth_callback' => (string) $callback ) )->decodeQueryString('oauth_token');
				\IPS\Output::i()->redirect( "https://api.twitter.com/oauth/authenticate?oauth_token={$response['oauth_token']}" );
			}
			
			/* CSRF Check */
			if ( \IPS\Request::i()->csrf !== \IPS\Session::i()->csrfKey )
			{
				throw new \IPS\Login\Exception( 'CSRF_FAIL', \IPS\Login\Exception::INTERNAL_ERROR );
			}
			
			/* Authenticate */
			$response = $this->sendRequest( 'post', 'https://api.twitter.com/oauth/access_token', array( 'oauth_verifier' => \IPS\Request::i()->oauth_verifier ), \IPS\Request::i()->oauth_token )->decodeQueryString('user_id');

			/* What name are we using? */
			$member = $member ?: \IPS\Member::load( $response['user_id'], 'twitter_id' );
			$name = NULL;
			if ( !$member or !$member->member_id )
			{
				if ( $this->settings['name'] == 'screen' )
				{
					$name = $response['screen_name'];
				}
				elseif ( $this->settings['name'] == 'real' )
				{
					try
					{
						$user = $this->sendRequest( 'get', 'https://api.twitter.com/1.1/account/verify_credentials.json', array(), $response['oauth_token'], $response['oauth_token_secret'] )->decodeJson();
						$name = $user['name'];
					}
					catch ( \IPS\Http\Request\Exception $e ) { }
				}
			}
			
			/* Find or create member */
			$member = $this->createOrUpdateAccount( $member, array(
				'twitter_id'		=> $response['user_id'],
				'twitter_token'		=> $response['oauth_token'],
				'twitter_secret'	=> $response['oauth_token_secret']
			), $name, NULL, NULL, array( 'photo' => TRUE, 'cover' => TRUE, 'status' => '' ) );
						
			/* Return */
			return $member;		
		}
		catch ( \IPS\Login\Exception $e )
		{
			throw $e;
		}
		catch ( \Exception $e )
   		{
	   		throw new \IPS\Login\Exception( 'generic_error', \IPS\Login\Exception::INTERNAL_ERROR );
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
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'help',
				'icon'		=> 'question-circle',
				'link'		=> \IPS\Http\Url::ips( 'docs/login_twitter' ),
				'target'	=> '_blank',
				'class'		=> ''
			),
		);
		
		return array(
			'consumer_key'		=> new \IPS\Helpers\Form\Text( 'login_twitter_key', ( isset( $this->settings['consumer_key'] ) ) ? $this->settings['consumer_key'] : '', TRUE ),
			'consumer_secret'	=> new \IPS\Helpers\Form\Text( 'login_twitter_secret', ( isset( $this->settings['consumer_secret'] ) ) ? $this->settings['consumer_secret'] : '', TRUE ),
			'name'				=> new \IPS\Helpers\Form\Radio( 'login_twitter_name', ( isset( $this->settings['name'] ) ) ? $this->settings['name'] : 'any', TRUE, array( 'options' => array(
				'real'		=> 'login_twitter_name_real',
				'screen'	=> 'login_twitter_name_screen',
				'any'		=> 'login_twitter_name_any',
			) ) )
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
		try
		{
			$response = $this->sendRequest( 'get', 'https://api.twitter.com/oauth/request_token', array( 'oauth_callback' => (string) \IPS\Http\Url::internal( '', 'front' ) ) )->decodeQueryString('oauth_token');
			return TRUE;
		}
		catch ( \Exception $e )
		{
			throw new \InvalidArgumentException( \IPS\Member::loggedIn()->language()->addToStack('login_3p_bad', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack('login_handler_Twitter') ) ) ) );
		}
	}
	
	/**
	 * Send Request
	 *
	 * @param	string	$method			HTTP Method
	 * @param	string	$url			URL
	 * @param	array	$params			Parameters
	 * @param	string	$token			OAuth Token
	 * @return	\IPS\Http\Response
	 * @throws	\IPS\Http\Request\Exception
	 */
	public function sendRequest( $method, $url, $params=array(), $token='', $secret='' )
	{		
		/* Generate the OAUTH Authorization Header */
		$OAuthAuthorization = array_merge( array(
			'oauth_consumer_key'	=> $this->settings['consumer_key'],
			'oauth_nonce'			=> md5( \IPS\Login::generateRandomString() ),
			'oauth_signature_method'=> 'HMAC-SHA1',
			'oauth_timestamp'		=> time(),
			'oauth_token'			=> $token,
			'oauth_version'			=> '1.0'
		) );
		
		foreach ( $params as $k => $v )
		{
			if ( mb_substr( $k, 0, 6 ) === 'oauth_' )
			{
				$OAuthAuthorization = array_merge( array( $k => $v ), $OAuthAuthorization );
				unset( $params[ $k ] );
			}
		}

		$signatureBaseString = mb_strtoupper( $method ) . '&' . rawurlencode( $url ) . '&' . rawurlencode( http_build_query( $OAuthAuthorization ) ) . ( count( $params ) ? ( rawurlencode( '&' ) . rawurlencode( http_build_query( $params, NULL, NULL, PHP_QUERY_RFC3986 ) ) ) : '' );			
		$signingKey = rawurlencode( $this->settings['consumer_secret'] ) . '&' . rawurlencode( $secret ?: $token );			
		$OAuthAuthorizationEncoded = array();
		foreach ( $OAuthAuthorization as $k => $v )
		{
			$OAuthAuthorizationEncoded[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
			
			if ( $k === 'oauth_nonce' )
			{
				$signature = base64_encode( hash_hmac( 'sha1', $signatureBaseString, $signingKey, TRUE ) );
				$OAuthAuthorizationEncoded[] = rawurlencode( 'oauth_signature' ) . '="' . rawurlencode( $signature ) . '"';
			}
		}
		$OAuthAuthorizationHeader = 'OAuth ' . implode( ', ', $OAuthAuthorizationEncoded );

		/* Send the request */
		return \IPS\Http\Url::external( $url )->request()->setHeaders( array( 'Authorization' => $OAuthAuthorizationHeader ) )->$method( $params );
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
		return ( $member->twitter_id and $member->twitter_token and $member->twitter_secret );
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