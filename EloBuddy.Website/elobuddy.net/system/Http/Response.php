<?php
/**
 * @brief		HTTP Response Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Http;
 
/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * HTTP Response Class
 */
class _Response
{
	/**
	 * @brief	HTTP Response Version
	 */
	public $httpResponseVersion = NULL;
	
	/**
	 * @brief	HTTP Response Code
	 */
	public $httpResponseCode = NULL;
	
	/**
	 * @brief	HTTP Response Text
	 */
	public $httpResponseText = NULL;
	
	/**
	 * @brief	HTTP Headers
	 */
	public $httpHeaders = NULL;
	
	/**
	 * @brief	Response Content
	 */
	public $content = '';

	/**
	 * Constructor
	 *
	 * @param	string	$content	Response Content
	 * @return	void
	 */
	public function __construct( $content )
	{
		/* Do we have any content? */
		if( !$content )
		{
			return;
		}

		/* Extract the HTTP response information */
		do
		{
			if( !$content )
			{
				break;
			}

			$firstLineBreak = mb_strpos( $content, "\n" );
			if ( preg_match( "/^HTTP\/(.+?) (\d+?) (.+?)$/", mb_substr( $content, 0, $firstLineBreak ), $matches ) )
			{
				$this->httpResponseVersion = $matches[1];
				$this->httpResponseCode = $matches[2];
				$this->httpResponseText = $matches[3];
			}

			/* If we have received a 1xx response code, that means it's not the final response */
			if ( mb_substr( $this->httpResponseCode, 0, 1 ) == 1 )
			{
				$content = trim( mb_substr( $content, $firstLineBreak ) );
			}
			/* Or a 3xx response with another response */
			elseif ( mb_substr( $this->httpResponseCode, 0, 1 ) == 3 AND mb_strpos( mb_substr( $content, $firstLineBreak ), "HTTP/" ) !== FALSE )
			{
				$_content = trim( mb_substr( $content, $firstLineBreak ) );
				if ( $_content )
				{
					$content = $_content;
				}
			}
			else
			{
				break;
			}
		}
		while ( TRUE );

		/* Split into headers and content */
		$split  = preg_split( "/\r\n\r\n/", mb_substr( $content, $firstLineBreak ), 2 );
		if ( isset( $split[1] ) )
		{
			$this->content = $split[1];
		}
		foreach ( explode( "\n", $split[0] ) as $line )
		{
			$line = trim( $line );
						
			if ( $line !== '' and preg_match( "/^(.+?): (.+?)$/", $line, $matches ) )
			{
				$this->httpHeaders[ $matches[1] ] = $matches[2];
			}
		}
		
		/* Fix chunked encoding */
		if( isset( $this->httpHeaders['transfer-encoding'] ) )
		{
			$this->httpHeaders['Transfer-Encoding']	= $this->httpHeaders['transfer-encoding'];
		}
		
		if( isset( $this->httpHeaders['Transfer-Encoding'] ) and mb_strtolower( trim( $this->httpHeaders['Transfer-Encoding'] ) ) == 'chunked' )
		{
			if ( $decoded = $this->decodeChunked( $this->content ) )
			{
				$this->content = $decoded;
			}
		}
	}
	
	/**
	 * Decode chunked response body from HTTP 1.1 response
	 *
	 * @see		<a href='http://stackoverflow.com/questions/10793017/how-to-easily-decode-http-chunked-encoded-string-when-making-raw-http-request'>Decoding chunked response in PHP</a>
	 * @param	string	$body	Response body
	 * @return	string
	 * @note	We are working with bytes and intentionally are not using mb* functions
	 */
	public function decodeChunked( $body )
	{
		$response	= '';
		$initial	= $body;
		
		for( $response = ''; !empty($body); $body = trim($body) )
		{			
			$_pos		= \strpos( $body, "\r\n" );
			
			/* The last line could be 0000000 which is valid but doesn't have \r\n */
			if( trim( $body, '0' ) === '' )
			{
				return $response;
			}

			/* Make sure this is valid hex */
			if( $_pos === FALSE OR !preg_match( "/^([a-f0-9]+)$/mi", \substr( $body, 0, $_pos ) ) )
			{
				return $response ?: $initial;
			}
			
			$_len		= (int) hexdec( \substr( $body, 0, $_pos ) );

			if( $_len < 1 )
			{
				return $initial;
			}

			$response	.= \substr( $body, $_pos + 2, $_len );
			$body		= \substr( $body, $_pos + 2 + $_len );
			$body		= trim($body);		
		}

		return $response;
	}

	/**
	 * Magic Method: String Value
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->content;
	}
	
	/**
	 * Decode JSON
	 *
	 * @param	bool	$asArray	Whether to decode as an array or not
	 * @return	array
	 * @throws	\RuntimeException
	 *	@li			BAD_JSON
	 */
	public function decodeJson( $asArray=TRUE )
	{
		if ( !( $json = json_decode( $this->content, $asArray ) ) )
		{
			throw new \RuntimeException('BAD_JSON');
		}
		
		return $json;
	}
	
	/**
	 * Decode XML
	 *
	 * @return	\IPS\Xml\SimpleXML
	 * @throws	\RuntimeException
	 *	@li			BAD_XML
	 */
	public function decodeXml()
	{
		try
		{
			$xml = \IPS\Xml\SimpleXML::loadString( $this->content );
		}
		catch ( \InvalidArgumentException $e )
		{
			throw new \RuntimeException('BAD_XML');
		}
		
		return $xml;
	}
	
	/**
	 * Decode Query String
	 *
	 * @param	string	$expectedKey	If provided, the return value will check for an element with this key and if it isn't present, throw an Exception
	 * @return	array
	 * @throws	\RuntimeException
	 *	@li			EXPECTED_KEY_NOT_PRESENT
	 */
	public function decodeQueryString( $expectedKey=NULL )
	{
		@parse_str( $this->content, $queryString );
		
		if ( $expectedKey !== NULL and !array_key_exists( $expectedKey, $queryString ) )
		{
			throw new \RuntimeException( 'EXPECTED_KEY_NOT_PRESENT' );
		}
		
		return $queryString;
	}
}