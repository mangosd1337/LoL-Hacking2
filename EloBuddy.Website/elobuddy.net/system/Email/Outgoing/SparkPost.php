<?php
/**
 * @brief		SparkPost Email Class
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
 * SparkPost Email Class
 */
class _SparkPost extends \IPS\Email
{
	/* !Configuration */
	
	/**
	 * @brief	The number of emails that can be sent in one "go"
	 */
	const MAX_EMAILS_PER_GO = 2000; // SparkPost "recommends" 2000 per cycle
	
	/**
	 * @brief	If sending a bulk email to more than MAX_EMAILS_PER_GO - does this
	 *			class require waiting between cycles? For "standard" classes like
	 *			PHP and SMTP, this will be TRUE - and will cause bulk mails to go
	 *			to a class. For APIs like SparkPost, this can be FALSE
	 */
	const REQUIRES_TIME_BREAK = FALSE;
	
	/**
	 * @brief	API Key
	 */
	protected $apiKey;
	
	/**
	 * Constructor
	 *
	 * @param	string	$apiKey	API Key
	 * @return	void
	 */
	public function __construct( $apiKey )
	{
		$this->apiKey = $apiKey;
	}
	
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
		/* SparkPost's API can't handle Cc or Bcc, so if we have either of those, we need to use SMTP */
		if ( $cc or $bcc )
		{
			$this->_sendWithSmtp( $to, $cc, $bcc, $fromEmail, $fromName, $additionalHeaders );
		}
		
		/* Otherwise we can use the API */
		else
		{
			$this->_sendWithApi( $to, $fromEmail, $fromName, $additionalHeaders );
		}
	}
	
	/**
	 * Send the email using SMTP
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
	public function _sendWithSmtp( $to, $cc=array(), $bcc=array(), $fromEmail = NULL, $fromName = NULL, $additionalHeaders = array() )
	{	
		$fromEmail = $fromEmail ?: \IPS\Settings::i()->email_out;
		
		$smtp = new Smtp( 'tls', 'smtp.sparkpostmail.com', 587, 'SMTP_Injection', $this->apiKey );
		
		$smtp->_send( $fromEmail, $smtp::_parseRecipients( $to, TRUE ), $smtp->compileFullEmail( $to, $cc, $bcc, $fromEmail, $fromName, $additionalHeaders ) );
	}
	
	/**
	 * Send the email using the SparkPost API
	 * 
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param	mixed	$fromEmail			The email address to send from. If NULL, default setting is used
	 * @param	mixed	$fromName			The name the email should appear from. If NULL, default setting is used
	 * @param	array	$additionalHeaders	Additional headers to send
	 * @return	void
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	public function _sendWithApi( $to, $fromEmail = NULL, $fromName = NULL, $additionalHeaders = array() )
	{		
		/* Compile the recipients */
		$recipients = array();
		foreach ( array_map( 'trim', explode( ',', static::_parseRecipients( $to, TRUE ) ) ) as $address )
		{
			$recipients[] = array( 'address' => array( 'email' => $address ) );
		}
		
		/* Compile the API call request data */
		$request = array(
			'recipients'	=> $recipients,
			'content'		=> array(
				'html'			=> static::_escapeTemplateTags( $this->compileContent( 'html', static::_getMemberFromRecipients( $to ) ) ),
				'text'			=> static::_escapeTemplateTags( $this->compileContent( 'plaintext', static::_getMemberFromRecipients( $to ) ) ),
				'subject'		=> static::_escapeTemplateTags( $this->compileSubject( static::_getMemberFromRecipients( $to ) ) ),
				'from'				=> array(
					'email'				=> $fromEmail ?: \IPS\Settings::i()->email_out,
					'name'				=> $fromEmail ?: \IPS\Settings::i()->board_name
				)
			),
			'options'			=> array(
				'transactional'		=> $this->type === static::TYPE_TRANSACTIONAL,
				'open_tracking'		=> (bool) \IPS\Settings::i()->sparkpost_click_tracking,
				'click_tracking'	=> (bool) \IPS\Settings::i()->sparkpost_click_tracking
			)
		);
		$request = $this->_modifyRequestDataWithHeaders( $request, $additionalHeaders );

		/* Make API call */
		$response = $this->_api( 'transmissions', $request );
		if ( isset( $response['errors'] ) )
		{
			throw new \IPS\Email\Outgoing\Exception( $response['errors'][0]['message'], ( isset( $response['errors'][0]['code'] ) ) ? $response['errors'][0]['code'] : NULL );
		}
	}
	
	/**
	 * Modify the request data that will be sent to the SparkPost API with header data
	 * 
	 * @param	array	$request			SparkPost API request data
	 * @param	array	$additionalHeaders	Additional headers to send
	 * @param	array	$allowedTags		The tags that we want to parse
	 * @return	array
	 */
	protected function _modifyRequestDataWithHeaders( $request, $additionalHeaders = array(), $allowedTags = array() )
	{
		/* Do we have a Reply-To? */
		if ( isset( $additionalHeaders['Reply-To'] ) )
		{
			$request['content']['reply_to'] = static::_escapeTemplateTags( $additionalHeaders['Reply-To'], $allowedTags );
			unset( $additionalHeaders['Reply-To'] );
		}
		
		/* Any other headers? */
		unset( $additionalHeaders['Subject'] );
		unset( $additionalHeaders['From'] );
		unset( $additionalHeaders['To'] );
		if ( count( $additionalHeaders ) )
		{
			$request['content']['headers'] = array_map( function( $v ) use ( $allowedTags ) {
				return static::_escapeTemplateTags( $v, $allowedTags );	
			}, $additionalHeaders );
		}
				
		/* Return */
		return $request;
	}
	
	/**
	 * Merge and Send
	 *
	 * @param	array			$recipients			Array where the keys are the email addresses to send to and the values are an array of variables to replace
	 * @param	mixed			$fromEmail			The email address to send from. If NULL, default setting is used. NOTE: This should always be a site-controlled domin. Some services like Sparkpost require the domain to be validated.
	 * @param	mixed			$fromName			The name the email should appear from. If NULL, default setting is used
	 * @param	array			$additionalHeaders	Additional headers to send. Merge tags can be used like in content.
	 * @param	\IPS|Lang		$language			The language the email content should be in
	 * @return	int				Number of successful sends
	 */
	public function mergeAndSend( $recipients, $fromEmail = NULL, $fromName = NULL, $additionalHeaders = array(), \IPS\Lang $language )
	{
		/* Work out recipients */
		$varNames = array();
		$recipientsForSparkpost = array();
		foreach ( $recipients as $address => $_vars )
		{
			$vars = array();
			foreach ( $_vars as $k => $v )
			{
				$vars[ $k ] = $v;
				
				if ( !in_array( $k, $varNames ) )
				{
					$varNames[] = $k;
				}
			}
			
			$recipientsForSparkpost[] = array( 'address' => array( 'email' => $address ), 'substitution_data' => $vars );
		}
									
		/* Put tags into SparkPost format */
		$htmlContent = str_replace( array( '*|', '|*' ), array( '{{', '}}' ), $this->compileContent( 'html', FALSE, $language ) );
		$plaintextContent = str_replace( array( '*|', '|*' ), array( '{{', '}}' ), $this->compileContent( 'plaintext', FALSE, $language ) );
		$subject = str_replace( array( '*|', '|*' ), array( '{{', '}}' ), $this->compileSubject( NULL, $language ) );
		$_additionalHeaders = array();
		foreach ( $additionalHeaders as $k => $v )
		{
			$_additionalHeaders[ $k ] = str_replace( array( '*|', '|*' ), array( '{{', '}}' ), $v );
		}
						
		/* Compile the API call request data */
		$request = array(
			'recipients'		=> $recipientsForSparkpost,
			'content'			=> array(
				'html'				=> static::_escapeTemplateTags( $htmlContent, $varNames ),
				'text'				=> static::_escapeTemplateTags( $plaintextContent, $varNames ),
				'subject'			=> static::_escapeTemplateTags( $subject, $varNames ),
				'from'				=> array(
					'email'				=> $fromEmail ?: \IPS\Settings::i()->email_out,
					'name'				=> $fromEmail ?: \IPS\Settings::i()->board_name
				)
			),
			'options'			=> array(
				'transactional'		=> $this->type === static::TYPE_TRANSACTIONAL,
				'open_tracking'		=> (bool) \IPS\Settings::i()->sparkpost_click_tracking,
				'click_tracking'	=> (bool) \IPS\Settings::i()->sparkpost_click_tracking
			)
		);
		$request = $this->_modifyRequestDataWithHeaders( $request, $_additionalHeaders, $varNames );
		
		/* Make API call */
		$response = $this->_api( 'transmissions', $request );
		return ( isset( $response['results'] ) and isset( $response['results']['total_accepted_recipients'] ) ) ? $response['results']['total_accepted_recipients'] : 0;
	}
	
	/**
	 * Make API call
	 *
	 * @param	string	$method	Method
	 * @param	string	$apiKey	API Key
	 * @param	array	$args	Arguments
	 * @throws  \IPS\Email\Outgoing\Exception   Indicates an invalid JSON response or HTTP error
	 * @return	array
	 */
	protected function _api( $method, $args=NULL )
	{
		$request = \IPS\Http\Url::external( 'https://api.sparkpost.com/api/v1/' . $method )
			->request( \IPS\LONG_REQUEST_TIMEOUT )
			->setHeaders( array( 'Content-Type' => 'application/json', 'Authorization' => $this->apiKey ) );

		try
		{
			if ( $args )
			{
				$response = $request->post( json_encode( $args ) );
			}
			else
			{
				$response = $request->get();
			}

			return $response->decodeJson();
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new \IPS\Email\Outgoing\Exception( $e->getMessage(), $e->getCode() );
		}
		/* Capture json decoding errors */
		catch ( \RuntimeException $e )
		{
			throw new \IPS\Email\Outgoing\Exception( $e->getMessage(), $e->getCode() );
		}
	}
	
	/**
	 * Escape template tags
	 *
	 * @param	string	$content		The content
	 * @param	array	$allowedTags	The tags that we want to parse
	 * @return	array
	 * @see		<a href="https://developers.sparkpost.com/api/#/introduction/substitutions-reference/escaping-start-and-end-tags">Escaping Start and End Tags</a>
	 */
	protected static function _escapeTemplateTags( $content , $allowedTags = array() )
	{
		if ( count( $allowedTags ) )
		{		
			$allowedTagsForOpening = implode( '|', array_map( function( $val ) {
				return preg_quote( $val . '}}', '/' );
			}, $allowedTags ) );
			
			$content = preg_replace_callback( '/{{{?(?!' . $allowedTagsForOpening . ')/', function( $matches ){
				if ( $matches[0] === '{{{' )
				{
					return '{{opening_triple_curly()}}';
				}
				else
				{
					return '{{opening_double_curly()}}';
				}
			}, $content );
		}
		else
		{
			$content = str_replace( '{{{', '{{opening_triple_curly()}}', $content );
			$content = preg_replace( '/{{(?!opening_triple_curly\(\)\}\})/', '{{opening_double_curly()}}', $content );
		}
		
		$allowedTags[] = 'opening_double_curly()';
		$allowedTags[] = 'opening_triple_curly()';
		$allowedTagsForClosing = implode( '|', array_map( function( $val ) {
			return preg_quote( '{{' . $val, '/' );
		}, $allowedTags ) );
		
		$content = preg_replace_callback( '/(?<!' . $allowedTagsForClosing . ')}}}?/', function( $matches ){
			if ( $matches[0] === '}}}' )
			{
				return '{{closing_triple_curly()}}';
			}
			else
			{
				return '{{closing_double_curly()}}';
			}
		}, $content );
		
		return $content;
	}
	
	/**
	 * Get sending domains
	 *
	 * @return	array
	 */
	public function sendingDomains()
	{
		return $this->_api( 'sending-domains' );
	}
	
}