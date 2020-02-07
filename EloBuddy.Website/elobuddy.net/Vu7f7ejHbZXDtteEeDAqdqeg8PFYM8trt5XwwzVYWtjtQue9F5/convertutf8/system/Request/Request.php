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

namespace IPSUtf8;

/**
 * HTTP Request Class
 */
class Request extends \IPSUtf8\Patterns\Singleton
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
		$this->parseIncomingRecursively( $_GET );
		$this->parseIncomingRecursively( $_POST );
				
		array_walk_recursive( $_COOKIE, array( $this, 'clean' ) );
		$this->cookie = $_COOKIE;
		
		unset( $_GET );
		unset( $_POST );
		unset( $_REQUEST );
	}
	
	/**
	 * Delete original tables
	 *
	 * @return boolean
	 */
	public function isDeleteOriginals()
	{
		return ( IS_CLI AND $GLOBALS['argv'][1] == '--deleteOriginals' );
	}	
	/**
	 * Detect if this is using the "dump" method
	 *
	 * @return boolean
	 */
	public function isDumpMethod()
	{
		return ( IS_CLI AND $GLOBALS['argv'][1] == '--dump' );
	}
	
	/**
	 * Detect if this is using the "info" method
	 *
	 * @return boolean
	 */
	public function isInfo()
	{
		return ( IS_CLI AND $GLOBALS['argv'][1] == '--info' );
	}
	
	/**
	 * Detect if this is using the "restore orig tables" method
	 *
	 * @return boolean
	 */
	public function isRestore()
	{
		return ( IS_CLI AND $GLOBALS['argv'][1] == '--restore' );
	}
	
	/**
	 * Detect if this is windows command prompt or another similar basic client
	 *
	 * @return boolean
	 */
	public function isBasicClient()
	{
		return ( IS_CLI AND $GLOBALS['argv'][1] == '--basic' );
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
					
			$this->$k = $v;
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
			preg_match( '/^(.+?)\[(.+?)?\](.*)?$/', $key, $matches );
			
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
	 * Get Current URL
	 *
	 * @return	string
	 */
	public function url()
	{
		return ( ( static::isSecure() ? 'https://' : 'http://' ) . $_SERVER['SERVER_NAME'] . ( ( $_SERVER['SERVER_PORT'] != 80 ) ? ":{$_SERVER['SERVER_PORT']}" : '' ) . $_SERVER['REQUEST_URI'] );
	}
	
	/**
	 * Get IP Address
	 *
	 * @return	string
	 */
	public function ipAddress()
	{
		if ( \IPS\Settings::i()->xforward_matching )
		{
			foreach( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $x_f )
			{
				$addrs[] = trim( $x_f );
			}
		
			$addrs[] = $_SERVER['HTTP_CLIENT_IP'];
			$addrs[] = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
			$addrs[] = $_SERVER['HTTP_PROXY_USER'];
		}
		
		$addrs[] = $_SERVER['REMOTE_ADDR'];
		
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
		/* Work out the path */
		if ( $path === NULL )
		{
			$path = mb_substr( \IPS\Settings::i()->base_url, mb_strpos( \IPS\Settings::i()->base_url, $_SERVER['SERVER_NAME'] ) + mb_strlen( $_SERVER['SERVER_NAME'] ) );
			$path = mb_substr( $path, mb_strpos( $path, '/' ) );
		}
		
		/* Set the cookie */
		if ( setcookie( $name, $value, $expire ? $expire->getTimestamp() : 0, $path, $domain ?: $_SERVER['SERVER_NAME'], FALSE, $httpOnly ) === TRUE )
		{
			$this->cookie[ $name ] = $value;
			return TRUE;
		}
		return FALSE;
	}

}