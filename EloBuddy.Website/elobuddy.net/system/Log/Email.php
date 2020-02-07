<?php
/**
 * @brief		Email Log Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Log;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email log class
 */
class _Email extends \IPS\Log\Disk
{
	/**
	 * Log
	 *
	 * @param		string			$message		The message
	 * @param		string			$suffix			Unique key for this log
	 * @return		void
	 */
	public function write( $message, $suffix=NULL )
	{
		$date       = date( 'r' );
		$ip         = $this->getIpAddress();
		$url        = \IPS\Request::i()->url();
		$fileSuffix = ( $suffix !== NULL ) ? '_' . $suffix : '';
		
		$text = <<<MSG
Hello,

An error has occured at {$url}. Here is the message:

------------------------------------------------------------------------
{$date} (Severity: {$this->severity})
{$ip} - {$url}
{$message}
------------------------------------------------------------------------

You are receiving this email as you have chosen to receive emails for certain
errors. You can disable this by editing the constant LOG_METHOD in constants.php

MSG;

		$html = <<<MSG
Hello,
<p>An error has occured at {$url}. Here is the message:</p>
<hr>
<br />{$date} (Severity: {$this->severity})
<br />{$ip} - {$url}
<br /><pre>{$message}</pre>
<hr>
<p>You are receiving this email as you have chosen to receive emails for certain
errors. You can disable this by editing the constant LOG_METHOD in constants.php</p>

MSG;
		/* Log to disk first so it's saved if email fails */
		parent::write( $message, $suffix );
		
		if ( isset( $this->config['to'] ) )
		{
			$subSuffix = ( $suffix !== NULL ) ? mb_strtoupper( $suffix ) . ': ' : '';
			
			try
			{
				$email = \IPS\Email::buildFromContent( $subSuffix . ( ( isset( $this->config['subject'] ) ) ? $this->config['subject'] : "New error log" ), $html, $text );
				$email->from = $this->config['to'];
				$email->useWrapper = false;
				$email->send( $this->config['to'] );
			}
			catch( Exception $e )
			{
				/* So bad something failed setting up email class, retreat! */
				@mail( $this->config['to'], $subSuffix . ( ( isset( $this->config['subject'] ) ) ? $this->config['subject'] : "New error log" ), $text );
			}
		}
			
	}
}