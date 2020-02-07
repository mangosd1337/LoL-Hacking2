<?php
/**
 * @brief		SMTP Email Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		17 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Email\Outgoing;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SMTP Email Class
 */
class _SMTP extends \IPS\Email
{
	/**
	 * @brief	SMTP Protocol ("tls", "ssl" or "plain")
	 */
	protected $smtpProtocol;
	
	/**
	 * @brief	SMTP Host
	 */
	protected $smtpHost;
	
	/**
	 * @brief	SMTP Port
	 */
	protected $smtpPort;
	
	/**
	 * @brief	SMTP Username
	 */
	protected $smtpUser;
	
	/**
	 * @brief	SMTP Password
	 */
	protected $smtpPass;
	
	/**
	 * @brief	SMTP Connections
	 */
	protected static $smtp = array();
	
	/**
	 * @brief	Connection Key
	 */
	protected $connectionKey;
	
	/**
	 * @brief	Log
	 */
	protected $log = '';
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct( $smtpProtocol, $smtpHost, $smtpPort, $smtpUser, $smtpPass )
	{
		$this->smtpProtocol = $smtpProtocol;
		$this->smtpHost = $smtpHost;
		$this->smtpPort = $smtpPort;
		$this->smtpUser = $smtpUser;
		$this->smtpPass = $smtpPass;
		$this->connectionKey = md5( $smtpProtocol . $smtpHost . $smtpPort . $smtpUser . $smtpPass );
	}
	
	/**
	 * Connect to server
	 *
	 * @return void
	 */
	public function connect()
	{
		/* Do we already have a connection? */
		if( array_key_exists( $this->connectionKey, static::$smtp ) )
		{
			return;
		}

		/* Connect */
		$connection = @fsockopen( ( ( $this->smtpProtocol == 'ssl' ) ? 'ssl://' : '' ) . $this->smtpHost, $this->smtpPort, $errno, $errstr );
		if ( !$connection )
		{
			throw new \IPS\Email\Outgoing\Exception( $errstr, $errno );
		}
		static::$smtp[ $this->connectionKey ] = $connection;
		register_shutdown_function(function( $object ){
			$object->_sendCommand( 'quit' );
			@fclose( static::$smtp[ $object->connectionKey ] );
			unset( static::$smtp[ $object->connectionKey ] );
		}, $this );

		/* Check the initial response is okay */
		$announce		= $this->_getResponse();
		$responseCode	= mb_substr( $announce, 0, 3 );
		if ( $responseCode != 220 )
		{
			throw new \IPS\Email\Outgoing\Exception( \IPS\Member::loggedIn()->language()->addToStack('smtpmail_fsock_error_initial', FALSE, array( 'sprintf' => array( $responseCode ) ) ) );
		}

		/* HELO/EHLO */
		try
		{
			$helo = 'EHLO';
			$responseCode = $this->_sendCommand( 'EHLO ' . $this->smtpHost, 250 );
		}
		catch ( \IPS\Email\Outgoing\Exception $e )
		{
			$helo = 'HELO';
			$responseCode = $this->_sendCommand( 'HELO ' . $this->smtpHost, 250 );
		}

		/* Is TLS being used? */
		if( $this->smtpProtocol == 'tls' )
		{
			$this->_sendCommand( 'STARTTLS', 220 );

			if ( !@\stream_socket_enable_crypto( static::$smtp[ $this->connectionKey ], TRUE, STREAM_CRYPTO_METHOD_TLS_CLIENT ) )
			{
				throw new \IPS\Email\Outgoing\Exception( \IPS\Member::loggedIn()->language()->addToStack('smtpmail_tls_failed') );
			}

			/* Exchange server (at least) wants EHLO resending for STARTTLS */
			$this->_sendCommand( $helo . ' ' . $this->smtpHost, 250 );
		}

		/* Authenticate */
		if ( $this->smtpUser )
		{
			$responseCode = $this->_sendCommand( 'AUTH LOGIN', 334 );
			$responseCode = $this->_sendCommand( base64_encode( $this->smtpUser ), 334 );
			$responseCode = $this->_sendCommand( base64_encode( $this->smtpPass ), 235 );
		}
	}
		
	/**
	 * Send the email
	 * 
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param	mixed	$cc					Addresses to CC (can also be email, member or array of either)
	 * @param	mixed	$bcc				Addresses to BCC (can also be email, member or array of either)
	 * @param	mixed	$fromEmail			The email address to send from. If NULL, default setting is used
	 * @param	mixed	$fromName			The name the email should appear from. If NULL, default setting is used
	 * @param	array	$additionalHeaders	Additional headers to send
	 * @return	void
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	public function _send( $to, $cc=array(), $bcc=array(), $fromEmail = NULL, $fromName = NULL, $additionalHeaders = array() )
	{
		$fromEmail = $fromEmail ?: \IPS\Settings::i()->email_out;
		$this->_sendCompiled( $fromEmail, array_map( 'trim', explode( ',', static::_parseRecipients( $to, TRUE ) ) ), $this->compileFullEmail( $to, $cc, $bcc, $fromEmail, $fromName, $additionalHeaders ) );
	}
	
	/**
	 * Send an email
	 * 
	 * @param	string	$fromEmail			The email address to send from
	 * @param	array	$toEmails			Array of email addresses to send to
	 * @param	string	$email				The full email (with headers, etc.)
	 * @param	array	$additionalHeaders	Additional headers to send
	 * @return	void
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	protected function _sendCompiled( $fromEmail, $toEmails, $email )
	{
		$this->connect();
		
		$this->_sendCommand( "MAIL FROM:<{$fromEmail}>", 250 );
		
		foreach ( $toEmails as $toEmail )
		{
			$this->_sendCommand( "RCPT TO:<{$toEmail}>", 250 );
		}
				
		$this->_sendCommand( 'DATA', 354 );
		$this->_sendCommand( $email . "\r\n.", 250 );
	}
	
	/**
	 * Send SMTP Command
	 *
	 * @param	string	$command			The command
	 * @param	int		$expectedResponse	The expected response code. Will throw an exception if different.
	 * @return	string	Response
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	protected function _sendCommand( $command, $expectedResponse=NULL )
	{
		/* Log */
		$this->log .= "> {$command}\r\n";
		
		/* Send */
		fputs( static::$smtp[ $this->connectionKey ], $command . "\r\n" );
		
		/* Read */
		$response = $this->_getResponse();
		
		/* Get response code */
		$code = intval( mb_substr( $response, 0, 3 ) );
		if ( $expectedResponse !== NULL and $code !== $expectedResponse )
		{
			throw new \IPS\Email\Outgoing\Exception( $response, $code );
		}
		
		/* Return */
		return $response;
	}

	/**
	 * Get response
	 *
	 * @return	string	Response
	 */
	protected function _getResponse()
	{
		/* Read */
		$response = '';
		while ( $line = @fgets( static::$smtp[ $this->connectionKey ], 515 ) )
		{			
			$response .= $line;
			if ( mb_substr($line, 3, 1) == " " )
			{
				break;
			}
		}
		
		/* Log */
		$this->log .= $response;
		
		/* Return */
		return $response;
	}
	
	/**
	 * Return the SMTP log
	 *
	 * @return string
	 */
	public function getLog()
	{
		return $this->log;
	}
}