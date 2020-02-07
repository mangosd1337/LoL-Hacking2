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

namespace IPSUtf8\Output;

/**
 * Output Class
 */
class Browser extends \IPSUtf8\Output
{
	public static $url = null;
	
	/**
	 * Constructor
	 */
	public static function i()
	{
		if ( static::$url === null )
		{	
			static::$url = 'index.php';
		}
		
		return parent::i();
	}
	
	/**
	 * Redirect
	 *
	 * @param	string		$url			Partial URL to redirect to
	 * @return	void
	 */
	public function redirect( $url )
	{
		$this->sendOutput( '', 303, '', array( "Location: {$url}" ) );
	}
	
	/**
	 * Display Error Screen
	 *
	 * @param	string	$message		language string for error message
	 * @return	void
	 */
	public function error( $message )
	{
		/* Send output */
		$this->sendOutput( \IPSUtf8\Output\Browser\Template::wrapper( "IPS Converter", \IPSUtf8\Output\Browser\Template::error( $message ), true ), 500 );
		exit();
	}
	
	/**
	 * Send output
	 *
	 * @param	string	$output			Content to output
	 * @param	int		$httpStatusCode	HTTP Status Code
	 * @param	string	$contentType	HTTP Content-type
	 * @param	array	$httpHeaders	Additional HTTP Headers
	 * @return	void
	 */
	public function sendOutput( $output='', $httpStatusCode=200, $contentType='text/html', $httpHeaders=array() )
	{
		/* Set HTTP status */
		if( isset( $_SERVER['SERVER_PROTOCOL'] ) and \strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
		{
			header( "HTTP/1.0 {$httpStatusCode} " . self::$httpStatuses[ $httpStatusCode ] );
		}
		else
		{
			header( "HTTP/1.1 {$httpStatusCode} " . self::$httpStatuses[ $httpStatusCode ] );
		}

		while( @ob_end_clean() );

		/* Buffer output */
		if ( $output )
		{
			if( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) and \strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false and (bool) ini_get('zlib.output_compression') === false )
			{
				ob_start('ob_gzhandler');
			}
			else
			{
				ob_start();
			}
			
			print $output;
		}
		
		/* Send headers */
		header( "Content-type: {$contentType};charset=UTF-8" );
		foreach ( $httpHeaders as $header )
		{
			header( $header );
		}
		header( "Connection: close" );
		
		/* Flush and exit */
		@ob_end_flush();
		@flush();
		
		exit;
	}
}