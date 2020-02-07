<?php
/**
 * @brief		PHP Email Class
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
 * PHP Email Class
 */
class _PHP extends \IPS\Email
{
	/**
	 * Send the email
	 * 
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param	mixed	$cc					Addresses to CC (can also be email, member or array of either)
	 * @param	mixed	$bcc				Addresses to BCC (can also be email, member or array of either)
	 * @param	mixed	$fromEmail			The email address to send from. If NULL, default setting is used
	 * @param	mixed	$fromName			The name the email should appear from. If NULL, default setting is used
	 * @param	array	$additionalHeaders	The name the email should appear from. If NULL, default setting is used
	 * @return	void
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	public function _send( $to, $cc=array(), $bcc=array(), $fromEmail = NULL, $fromName = NULL, $additionalHeaders = array() )
	{		
		$boundary = "--==_mimepart_" . md5( uniqid() );
		
		$subject = $this->compileSubject( static::_getMemberFromRecipients( $to ) );
		$headers = array();
		foreach( $this->_compileHeaders( $subject, $to, $cc, $bcc, $fromEmail, $fromName, $additionalHeaders, $boundary ) as $k => $v )
		{
			if ( !in_array( $k, array( 'To', 'Subject' ) ) )
			{
				$headers[] = "{$k}: {$v}";
			}
		}
		
		try
		{			
			if ( !mail( $this->_parseRecipients( $to, TRUE ), static::encodeHeader( $subject ), $this->_compileMessage( static::_getMemberFromRecipients( $to ), $boundary, PHP_EOL ), implode( PHP_EOL, $headers ), \IPS\Settings::i()->php_mail_extra ) )
			{
				$error = error_get_last();
				preg_match( "/^(\d+)/", $error['message'], $matches );
				throw new \IPS\Email\Outgoing\Exception( $error['message'], isset( $matches[1] ) ? $matches[1] : NULL );
			}
		}
		catch ( \Exception $e )
		{
			throw new \IPS\Email\Outgoing\Exception( $e->getMessage() );
		}
	}
}