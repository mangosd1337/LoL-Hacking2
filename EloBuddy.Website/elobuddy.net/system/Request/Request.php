<?php
/**
 * @brief		HTTP Request Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * HTTP Request Class
 */
class _Request extends \IPS\Patterns\Singleton
{
	/**
	 * @brief	Singleton Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Cookie data
	 */
	public $cookie = array();
	
	/**
	 * Constructor
	 *
	 * @return	void
	 * @note	We do not unset $_COOKIE as it is needed by session handling
	 */
	public function __construct()
	{
		if ( $_SERVER['REQUEST_METHOD'] == 'PUT' )
		{
			parse_str( file_get_contents('php://input'), $params );
			$this->parseIncomingRecursively( $params );
		}
		else
		{
			$this->parseIncomingRecursively( $_GET );
			$this->parseIncomingRecursively( $_POST );
		}
						
		array_walk_recursive( $_COOKIE, array( $this, 'clean' ) );

		/* If we have a cookie prefix, we have to strip it first */
		if( \IPS\COOKIE_PREFIX !== NULL )
		{
			foreach( $_COOKIE as $key => $value )
			{
				if( \IPS\COOKIE_PREFIX !== null )
				{
					if( mb_strpos( $key, \IPS\COOKIE_PREFIX ) === 0 )
					{
						$this->cookie[ preg_replace( "/^" . \IPS\COOKIE_PREFIX . "(.+?)/", "$1", $key ) ]	= $value;
					}
				}
				else
				{
					$this->cookie[ $key ]	= $value;
				}
			}
		}
		else
		{
			$this->cookie = $_COOKIE;
		}
	}

	/**
	 * Parse Incoming Data
	 *
	 * @param	array	$data	Data
	 * @return	void
	 */
	protected function parseIncomingRecursively( $data )
	{
		foreach( $data as $k => $v )
		{
			if ( is_array( $v ) )
			{
				array_walk_recursive( $v, array( $this, 'clean' ) );
			}
			else
			{
				$this->clean( $v, $k );
			}

			/* We used to call $this->$k = $v but that resulted in breaking our cookie array if a &cookie=1 parameter was passed in the URL */
			$this->data[ $k ] = $v;
		}
	}
	
	/**
	 * Clean Value
	 *
	 * @param	mixed	$v	Value
	 * @param	mixed	$k	Key
	 * @return	mixed
	 */
	protected function clean( &$v, $k )
	{
		/* Remove NULL bytes and the RTL control byte */
		$v = str_replace( array( "\0", "\u202E" ), '', $v );
		
		/* Undo magic quote madness */
		if ( get_magic_quotes_gpc() === 1 )
		{
			$v = stripslashes( $v );
		}
	}
	
	/**
	 * Get value from array
	 *
	 * @param	string	Key with square brackets (e.g. "foo[bar]")
	 * @return	mixed	Value
	 */
	public function valueFromArray( $key )
	{
		$array = $this->data;
		
		while ( $pos = mb_strpos( $key, '[' ) )
		{
			preg_match( '/^(.+?)\[([^\]]+?)?\](.*)?$/', $key, $matches );
			
			if ( !array_key_exists( $matches[1], $array ) )
			{
				return NULL;
			}
				
			$array = $array[ $matches[1] ];
			$key = $matches[2] . $matches[3];
		}
		
		if ( !isset( $array[ $key ] ) )
		{
			return NULL;
		}
				
		return $array[ $key ];
	}
	
	/**
	 * Is this an AJAX request?
	 *
	 * @return	bool
	 */
	public function isAjax()
	{
		return ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' );
	}

	/**
	 * Is this an SSL/Secure request?
	 *
	 * @return	bool
	 * @see		<a href='http://community.invisionpower.com/resources/bugs.html/_/ip-board/ipboard-cant-detect-https-if-behind-an-ssl-terminating-load-balancer-r42909'>Load balancer SSL</a>
	 * @note	A common technique to check for SSL is to look for $_SERVER['SERVER_PORT'] == 443, however this is not a correct check. Nothing requires SSL to be on port 443, or http to be on port 80.
	 * @see		<a href='http://community.invisionpower.com/resources/bugs.html/_/ips-4-0/ipsrequestissecure-r45082'>Zeus load balancers set HTTP_SSLSESSIONID</a>
	 */
	public function isSecure()
	{
		if( !empty( $_SERVER['HTTPS'] ) AND mb_strtolower( $_SERVER['HTTPS'] ) == 'on' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) AND mb_strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) == 'https' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) AND mb_strtolower( $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'] ) == 'https' )
		{
			return TRUE;
		}
		else if ( !empty( $_SERVER['HTTP_X_FORWARDED_HTTPS'] ) AND mb_strtolower( $_SERVER['HTTP_X_FORWARDED_HTTPS'] ) == 'https' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_FRONT_END_HTTPS'] ) AND mb_strtolower( $_SERVER['HTTP_FRONT_END_HTTPS'] ) == 'on' )
		{
			return TRUE;
		}
		else if( !empty( $_SERVER['HTTP_SSLSESSIONID'] ) )
		{
			return TRUE;
		}

		return FALSE;
	}
	
	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get current URL
	 *
	 * @return	\IPS\Http\Url
	 * @see		init.php
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{			
			/* Work out the query string. We need to urldecode the friendly url slug because browsers will send the value percent-encoded, which we don't want as it turns, for example, à into a percent-encoded character
			   but we don't want to urlencode the query string, because that will already be urlencoded */
			$path = urldecode( ( $_SERVER['QUERY_STRING'] AND mb_strpos( $_SERVER['REQUEST_URI'], $_SERVER['QUERY_STRING'] ) !== FALSE ) ? mb_substr( $_SERVER['REQUEST_URI'], 0, -mb_strlen( $_SERVER['QUERY_STRING'] ) ) : $_SERVER['REQUEST_URI'] ) . $_SERVER['QUERY_STRING'];
			
			/* Return */
			$this->_url	= new \IPS\Http\Url( ( ( $this->isSecure() ) ? 'https://' : 'http://' ) . ( !empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ) . $path, TRUE );
		}

		return $this->_url;
	}

	
	/**
	 * Get IP Address
	 *
	 * @return	string
	 */
	public function ipAddress()
	{
		$addrs = array();
		
		if ( \IPS\Settings::i()->xforward_matching )
		{
			if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			{
				foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $x_f )
				{
					$addrs[] = trim( $x_f );
				}
			}

			if( isset( $_SERVER['HTTP_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_CLIENT_IP'];
			}

			if( isset( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) )
			{
				$addrs[] = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			}

			if( isset( $_SERVER['HTTP_PROXY_USER'] ) )
			{
				$addrs[] = $_SERVER['HTTP_PROXY_USER'];
			}
		}
		
		if ( isset( $_SERVER['REMOTE_ADDR'] ) )
		{
			$addrs[] = $_SERVER['REMOTE_ADDR'];
		}
		
		foreach ( $addrs as $ip )
		{
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) )
			{
				return $ip;
			}
		}

		return '';
	}
	
	/**
	 * IP address is banned?
	 *
	 * @return	bool
	 */
	public function ipAddressIsBanned()
	{
		if ( isset( \IPS\Data\Store::i()->bannedIpAddresses ) )
		{
			$bannedIpAddresses = \IPS\Data\Store::i()->bannedIpAddresses;
		}
		else
		{
			$bannedIpAddresses = iterator_to_array( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'ip' ) ) );
			\IPS\Data\Store::i()->bannedIpAddresses = $bannedIpAddresses;
		}
		foreach ( $bannedIpAddresses as $ip )
		{
			if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( trim( $ip ), '/' ) ) . '$/', $this->ipAddress() ) )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Set a cookie
	 *
	 * @param	string				$name		Name
	 * @param	mixed				$value		Value
	 * @param	\IPS\DateTime|null	$expire		Expiration date, or NULL for on session end
	 * @param	bool				$httpOnly	When TRUE the cookie will be made accessible only through the HTTP protocol
	 * @param	string|null			$domain		Domain to set to. If NULL, will be detected automatically.
	 * @param	string|null			$path		Path to set to. If NULL, will be detected automatically.
	 * @return	bool
	 */
	public function setCookie( $name, $value, $expire=NULL, $httpOnly=TRUE, $domain=NULL, $path=NULL )
	{
		/* Work out the path and if cookies should be SSL only */
		$sslOnly	= FALSE;

		if( \IPS\COOKIE_PATH !== NULL AND $path === NULL )
		{
			$path	= \IPS\COOKIE_PATH;
		}

		if ( $path === NULL )
		{
			$path = mb_substr( \IPS\Settings::i()->base_url, mb_strpos( \IPS\Settings::i()->base_url, ( !empty( $_SERVER['SERVER_NAME'] ) ) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'] ) + mb_strlen( ( !empty( $_SERVER['SERVER_NAME'] ) ) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'] ) );
			$path = mb_substr( $path, mb_strpos( $path, '/' ) );
		}

		if( mb_substr( \IPS\Settings::i()->base_url, 0, 5 ) == 'https' AND \IPS\COOKIE_BYPASS_SSLONLY !== TRUE )
		{
			$sslOnly	= TRUE;
		}

		/* Are we forcing a cookie domain? */
		if( \IPS\COOKIE_DOMAIN !== NULL AND $domain === NULL )
		{
			$domain	= \IPS\COOKIE_DOMAIN;
		}
		
		$realName = $name;
		
		/* What about a prefix? */
		if( \IPS\COOKIE_PREFIX !== NULL )
		{
			$name	= \IPS\COOKIE_PREFIX . $name;
		}
				
		/* Set the cookie */
		if ( setcookie( $name, $value, $expire ? $expire->getTimestamp() : 0, $path, $domain ?: '', $sslOnly, $httpOnly ) === TRUE )
		{
			$this->cookie[ $realName ] = $value;

			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Returns the request method
	 *
	 * @return boolean
	 */
	public function requestMethod()
	{
		return mb_strtoupper( $_SERVER['REQUEST_METHOD'] );
	}
	
	/**
	 * Flood Check
	 *
	 * @return	void
	 */
	public static function floodCheck()
	{
		/* Flood control */
		if( \IPS\Member::loggedIn()->group['g_search_flood'] )
		{
			if( isset( $_SESSION['lastSearch'] ) and ( time() - $_SESSION['lastSearch'] ) < \IPS\Member::loggedIn()->group['g_search_flood'] )
			{
				$secondsToWait = \IPS\Member::loggedIn()->group['g_search_flood'] - ( time() - $_SESSION['lastSearch'] );
				\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'search_flood_error', FALSE, array( 'pluralize' => array( $secondsToWait ) ) ), '1C205/3', 429, \IPS\Member::loggedIn()->language()->addToStack( 'search_flood_error_admin', FALSE, array( 'pluralize' => array( $secondsToWait ) ) ), array( 'Retry-After' => \IPS\DateTime::create()->add( new \DateInterval( 'PT' . $secondsToWait . 'S' ) )->format('r') ) );
			}
	
			$_SESSION['lastSearch'] = time();
		}
	}

	/**
	 * Confirmation check
	 *
	 * @return	void
	 */
	public function confirmedDelete()
	{
		/* The confirmation dialogs will send form_submitted=1, as will displaying a form, so we check for this.
			If the admin (or user) simply visited a delete URL directly, this would not be included in the request. */
		if ( ! isset( \IPS\Request::i()->wasConfirmed ) )
		{
			$form = new \IPS\Helpers\Form( 'form', 'delete' );
			$form->hiddenValues['wasConfirmed']	= 1;
			$form->addMessage( 'delete_confirm_detail', 'ipsMessage ipsMessage_warning' );

			/* We call sendOutput() to show the form now */
			\IPS\Output::i()->output	= $form;
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'delete_confirm' );
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}

		/* If we are here, we're all good! */
		return TRUE;
	}
	
	/**
	 * Old IPB escape-on-input routine
	 *
	 * @param	string	$val	The unescaped text
	 * @return	string			The IPB3-style escaped text
	 */
	public static function legacyEscape( $val )
	{
    	$val = str_replace( "&"			, "&amp;"         , $val );
    	$val = str_replace( "<!--"		, "&#60;&#33;--"  , $val );
    	$val = str_replace( "-->"		, "--&#62;"       , $val );
    	$val = str_ireplace( "<script"	, "&#60;script"   , $val );
    	$val = str_replace( ">"			, "&gt;"          , $val );
    	$val = str_replace( "<"			, "&lt;"          , $val );
    	$val = str_replace( '"'			, "&quot;"        , $val );
    	$val = str_replace( "\n"		, "<br />"        , $val );
    	$val = str_replace( "$"			, "&#036;"        , $val );
    	$val = str_replace( "!"			, "&#33;"         , $val );
    	$val = str_replace( "'"			, "&#39;"         , $val );
    	$val = str_replace( "\\"		, "&#092;"        , $val );
    	
    	return $val;
	}
}