<?php
/**
 * @brief		Base API Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Base API Controller
 */
abstract class _Controller
{
	/**
	 * @brief	API Key
	 */
	protected $apiKey;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Api\Key	$apiKey	The API key being used to access
	 * @return	void
	 */
	public function __construct( \IPS\Api\Key $apiKey )
	{
		$this->apiKey = $apiKey;
	}
	
	/**
	 * Execute
	 *
	 * @param	array	$pathBits	The parts to the path called
	 * @param	bool	$shouldLog	Gets set to TRUE if this call should log
	 * @return	\IPS\Api\Response
	 * @throws	\IPS\Api\Exception
	 */
	public function execute( $pathBits, &$shouldLog )
	{
		$method = ( isset( $_SERVER['REQUEST_METHOD'] ) and in_array( mb_strtoupper( $_SERVER['REQUEST_METHOD'] ), array( 'GET', 'POST', 'PUT', 'DELETE' ) ) ) ? mb_strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
		$params = array();
		
		try
		{
			$endpointData = $this->_getEndpoint( $pathBits );
		}
		catch ( \RuntimeException $e )
		{
			throw new \IPS\Api\Exception( 'NO_ENDPOINT', '2S291/1', 404 );
		}
		
		if ( method_exists( $this, "{$method}{$endpointData['endpoint']}" ) )
		{
			preg_match( '/^IPS\\\(.+?)\\\api\\\(.+?)$/', get_called_class(), $matches );
			if ( !$this->apiKey->canAccess( $matches[1], $matches[2], "{$method}{$endpointData['endpoint']}" ) )
			{
				throw new \IPS\Api\Exception( 'NO_PERMISSION', '2S291/3', 403 );
			}
			$shouldLog = $this->apiKey->shouldLog( $matches[1], $matches[2], "{$method}{$endpointData['endpoint']}" );
			
			return call_user_func_array( array( $this, "{$method}{$endpointData['endpoint']}" ), $endpointData['params'] );
		}
		else
		{
			throw new \IPS\Api\Exception( 'BAD_METHOD', '3S291/2', 405 );
		}
	}
	
	/**
	 * Get endpoint data
	 *
	 * @param	array	$pathBits	The parts to the path called
	 * @return	array
	 * @throws	\RuntimeException
	 */
	protected function _getEndpoint( $pathBits )
	{
		$endpoint = NULL;
		$params = array();
		
		if ( count( $pathBits ) === 0 )
		{
			$endpoint = 'index';
		}
		elseif ( count( $pathBits ) === 1 )
		{
			$params[] = array_shift( $pathBits );
			$endpoint = 'item';
		}
		elseif ( count( $pathBits ) === 2 )
		{
			$params[] = array_shift( $pathBits );
			$endpoint = 'item_' . array_shift( $pathBits );
		}
		elseif ( count( $pathBits ) === 3 )
		{
			$params[] = array_shift( $pathBits );
			$endpoint = 'item_' . array_shift( $pathBits );
			$params[] = array_shift( $pathBits );
		}
		else
		{
			throw new \RuntimeException;
		}
		
		return array( 'endpoint' => $endpoint, 'params' => $params );
	}
	
	/**
	 * Get all endpoints
	 *
	 * @return	array
	 */
	public static function getAllEndpoints()
	{
		$return = array();
		foreach ( \IPS\Application::applications() as $app )
		{
			$apiDir = \IPS\ROOT_PATH . '/applications/' . $app->directory . '/api';
			if ( file_exists( $apiDir ) )
			{
				$directory = new \DirectoryIterator( $apiDir );
				foreach ( $directory as $file )
				{
					if ( !$file->isDot() and mb_substr( $file, 0, 1 ) != '.' )
					{
						$controllerName = mb_substr( $file, 0, -4 );
						$class = 'IPS\\' . $app->directory . '\\api\\' . $controllerName;
						$reflection = new \ReflectionClass( $class );
						foreach ( $reflection->getMethods() as $method )
						{
							if ( $method->getName() != 'execute' and !$method->isStatic() and $method->isPublic() and mb_substr( $method->getName(), 0, 1 ) != '_' )
							{
								$return[ $app->directory . '/' . $controllerName . '/' . $method->getName() ] = static::decodeDocblock( $method->getDocComment() );
							}
						}
					}
				}
			}
		}
		return $return;
	}
	
	/**
	 * Decode docblock
	 *
	 * @param	string	$comment	The docblock comment
	 * @return	array
	 */
	public static function decodeDocblock( $comment )
	{
		$comment = explode( "\n", $comment );
		array_shift( $comment );
		$title = preg_replace( '/^\s*\*\s+?/', '', array_shift( $comment ) );
		$description = '';
		while ( $nextLine = array_shift( $comment ) )
		{
			if ( preg_match( '/^\s*\*\s*\/?\s*$/', $nextLine ) )
			{
				break;
			}
			$description .= preg_replace( '/^\s*\*\s+?/', '', $nextLine ) . "\n";
		}
		
		$params = array();
		while ( $nextLine = array_shift( $comment ) )
		{
			if ( preg_match( '/^\s*\*\s*@([a-z]*)(\t+([^\t]*))?(\t+([^\t]*))?(\t+([^\t]*))?$/', $nextLine, $matches ) )
			{
				$details = array();
				foreach ( array( 3, 5, 7 ) as $k )
				{
					if ( isset( $matches[ $k ] ) )
					{
						$details[] = $matches[ $k ];
					}
				}
				$params[ $matches[1] ][] = $details;
			}
		}
		
		return array(
			'title'			=> $title,
			'description'	=> trim( $description ),
			'details'		=> $params
		);
	}

	/**
	 * Parses an endpoint key and modifies it for display
	 *
	 * @param	string	$endpoint	The endpoint (e.g: GET /core/members)
	 * @param 	string 	$size 		Size of badge to show
	 * @return	array
	 */
	public static function parseEndpointForDisplay( $endpoint, $size='small' )
	{
		$badgeStyles = array(
			'GET' => 'ipsBadge_positive',
			'POST' => 'ipsBadge_style2',
			'DELETE' => 'ipsBadge_negative',
			'PUT' => 'ipsBadge_intermediary'
		);

		$pieces = explode( ' ', $endpoint );
		$pieces[0] = "<span class='ipsBadge ipsBadge_{$size} " . $badgeStyles[ $pieces[0] ] . "'>" . $pieces[0] . "</span>";

		return implode( ' ', $pieces );
	}
}