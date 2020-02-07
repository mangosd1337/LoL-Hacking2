<?php
/**
 * @brief		Output Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8;

/**
 * Output Class
 */
abstract class Output
{
	/**
	 * @brief	HTTP Statuses
	 * @link	http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	 */
	protected static $httpStatuses = array( 100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 429 => 'Too Many Requests', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported' );
	
	/**
	 * @brief	Singleton Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * Get instance
	 *
	 * @return	\IPS\Request
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		return static::$instance;
	}
	
	/**
	 * @brief	Additional HTTP Headers
	 */
	public $httpHeaders = array();
	
	/**
	 * @brief	Stored Page Title
	 */
	public $title = '';
	
	/**
	 * @brief	Stored Content to output
	 */
	public $output = '';
	
	
	/**
	 * Display Error Screen
	 *
	 * @param	string	$message		language string for error message
	 * @return	void
	 */
	public function error( $message )
	{
		/* Send output */
		$this->sendOutput( "Error: " . $message );
	}
}