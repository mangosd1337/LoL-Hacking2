<?php
/**
 * @brief		Nexus Incoming Email Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\IncomingEmail;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class _Support
{
	/**
	 * Handle email
	 *
	 * @param	\IPS\Email\Incoming\Email	$email	The email
	 * @return	bool
	 */
	public function process( \IPS\Email\Incoming\Email $email )
	{
		/* Is it from an actual member? */
		$member = \IPS\Member::load( $email->from, 'email' );
		
		/* Replying? */
		if ( $request = $this->_emailIsReplyForRequest( $email ) )
		{
			$this->_processReply( $email, $member, $request );
			return TRUE;
		}
		
		/* Do we have a department? */
		$department = $this->_getDepartment( $email );
		if ( !$department )
		{
			return FALSE;
		}
		
		/* Create request */
		$this->_processNewRequest( $email, $member, $department );
		return TRUE;
	}
	
	/**
	 * Is reply?
	 *
	 * @param	\IPS\Email\Incoming\Email	$email	The email
	 * @return	\IPS\nexus\Support\Request|NULL
	 */
	protected function _emailIsReplyForRequest( \IPS\Email\Incoming\Email $email )
	{
		if ( preg_match( '/\[SR(\d+?)\.(.+?)(\.(\d+))?\]/', $email->raw, $matches ) or preg_match( '/\[SR(\d+?)\.(.+?)(\.(\d+))?\]/', $email->message, $matches ) )
		{
			try
			{
				$request = \IPS\nexus\Support\Request::load( $matches[1] );
				if ( $request->email_key === $matches[2] )
				{
					return $request;
				}
			}
			catch ( \OutOfRangeException $e ) { }
		}
		return NULL;
	}
	
	/**
	 * Handle reply
	 *
	 * @param	\IPS\Email\Incoming\Email	$email		The email
	 * @param	\IPS\Member					$member		The member it's from
	 * @param	\IPS\nexus\Support\Request	$request	The support request to make it a reply of
	 * @return	void
	 */
	protected function _processReply( \IPS\Email\Incoming\Email $email, \IPS\Member $member, \IPS\nexus\Support\Request $request )
	{
		$staff = array_key_exists( $member->member_id, \IPS\nexus\Support\Request::staff() );
		
		$pending = FALSE;
		$newMessage = NULL;
		if ( $staff and isset( $matches[4] ) )
		{
			$newMessage = $request->comments( 1, 0, 'date', 'desc' );
			if ( $matches[4] != $newMessage->id )
			{
				$pending = TRUE;
			}
		}
		
		$request->status = \IPS\nexus\Support\Status::load( TRUE, $staff ? 'status_default_staff' : 'status_default_member' );
		$request->last_reply = time();
		$request->last_reply_by = (int) $member->member_id;
		$request->replies++;
		$request->save();
		
		$reply = new \IPS\nexus\Support\Reply;
		$reply->request = $request->id;
		$reply->member = (int) $member->member_id;
		$reply->type = $staff ? ( $pending ? $reply::REPLY_PENDING : $reply::REPLY_STAFF ) : $reply::REPLY_EMAIL;
		$reply->post = $email->message;
		$reply->date = time();
		$reply->email = $email->from;
		$reply->cc = implode( ',', $email->cc );
		$reply->raw = $email->raw;
		$reply->save();
		static::makeAndClaimAttachments( $email->attachments, $reply );
		
		if ( $pending )
		{
			$notifyEmail = \IPS\Email::buildFromTemplate( 'nexus', 'staffReplyPending', array( $reply, $newMessage ), \IPS\Email::TYPE_TRANSACTIONAL );
			$notifyEmail->send( $member );
		}
		else
		{
			if ( $staff )
			{
				$defaultRecipients = $request->getDefaultRecipients();
				$reply->sendCustomerNotifications( $defaultRecipients['to'], $defaultRecipients['cc'], $defaultRecipients['bcc'] );
			}
			$reply->sendNotifications();
		}
	}
	
	/**
	 * Get department
	 *
	 * @param	\IPS\Email\Incoming\Email	$email		The email
	 * @return	\IPS\nexus\Support\Department
	 */
	protected function _getDepartment( \IPS\Email\Incoming\Email $email )
	{
		foreach ( $email->to as $to )
		{
			try
			{
				return \IPS\nexus\Support\Department::load( $to, 'dpt_email' );
			}
			catch ( \OutOfRangeException $e ) { }
		}
		return NULL;
	}
	
	/**
	 * Handle new requst
	 *
	 * @param	\IPS\Email\Incoming\Email		$email		The email
	 * @param	\IPS\Member						$member		The member it's from
	 * @param	\IPS\nexus\Support\Department	$department	The department to put it in
	 * @return	void
	 */
	protected function _processNewRequest( \IPS\Email\Incoming\Email $email, \IPS\Member $member, \IPS\nexus\Support\Department $department )
	{
		/* Forwarded by a staff member? */
		if (
			preg_match( '/^FWD?: (.*)$/i', $email->subject, $matches ) // Was forwarded
			and	$member->member_id and array_key_exists( $member->member_id, \IPS\nexus\Support\Request::staff() ) // By a staff member
			and $originallyFromEmail = $this->_originalSenderOfForwardedMessage( $email ) // And we can get the email address of the original sender
		) {
			list( $request, $reply ) = $this->_createRequestFromStaffForward( $email, $member, $department, $originallyFromEmail );
		}
		
		/* Nope, normal request */
		else
		{
			list( $request, $reply ) = $this->_createNormalNewRequest( $email, $member, $department );
		}
		
		/* Send staff notifications */
		$request->afterCreateLog( $reply );
		$request->sendNotifications();
	}
	
	/**
	 * Handle new request for an email that was forwarded by a staff member
	 *
	 * @param	\IPS\Email\Incoming\Email		$email					The email
	 * @param	\IPS\Member						$member					The member it's from
	 * @param	\IPS\nexus\Support\Department	$department				The department to put it in
	 * @param	string							$originallyFromEmail	The email address of the original sender
	 * @return	[ \IPS\nexus\Support\Request, \IPS\nexus\Support\Reply ]
	 */
	protected function _createRequestFromStaffForward( \IPS\Email\Incoming\Email $email, \IPS\Member $member, \IPS\nexus\Support\Department $department, $originallyFromEmail )
	{
		/* Get the member for the original email */
		$originallyFrom = \IPS\Member::load( $originallyFromEmail, 'email' );
		
		/* Init request */
		$request = $this->_initRequest( $member, $department ); 
		preg_match( '/^FWD?: (.*)$/i', $email->subject, $matches );
		$request->title = $matches[1];
		$request->member = (int) $originallyFrom->member_id;
		$request->email = $originallyFromEmail;
		$request->save();
		
		/* Strip any headers from the forwarded message */
		$quoted = $email->quote;
		$haveInitialHeader = FALSE;
		$exploded = explode( '<br>', $quoted );
		foreach ( $exploded as $k => $line )
		{
			if ( $line )
			{
				if ( !$haveInitialHeader and preg_match( '/^.*:/', $line ) )
				{
					$haveInitialHeader = TRUE;
				}
				elseif ( !preg_match( '/^.*:.*$/', $line ) )
				{
					$quoted = implode( '<br>', array_splice( $exploded, $k ) );
					break;
				}
			}
		}
		$quoted = \IPS\Text\Parser::parseStatic( '<div>' . $quoted . '</div>' );
		
		/* Create the reply */
		$reply = $this->_createReply( $email, $request, $member, $quoted, $originallyFromEmail );
		
		/* Create a hidden note */
		if ( trim( $email->message ) or count( $email->attachments ) )
		{
			$note = new \IPS\nexus\Support\Reply;
			$note->request = $request->id;
			$note->member = (int) $member->member_id;
			$note->type = $reply::REPLY_HIDDEN;
			$note->post = $email->message;
			$note->date = time();
			$note->email = $email->from;
			$note->save();
			
			static::makeAndClaimAttachments( $email->attachments, $note );
		}
		
		/* Return */
		return array( $request, $reply );
	}
	
	/**
	 * Handle new request from a normal email
	 *
	 * @param	\IPS\Email\Incoming\Email		$email		The email
	 * @param	\IPS\Member						$member		The member it's from
	 * @param	\IPS\nexus\Support\Department	$department	The department to put it in
	 * @return	[ \IPS\nexus\Support\Request, \IPS\nexus\Support\Reply ]
	 */
	protected function _createNormalNewRequest( \IPS\Email\Incoming\Email $email, \IPS\Member $member, \IPS\nexus\Support\Department $department )
	{
		/* Init request */
		$request = $this->_initRequest( $member, $department ); 
		$request->title = $email->subject;
		$request->member = (int) $member->member_id;
		$request->email = $email->from;
		$request->save();
		
		/* Create the reply */
		$reply = $this->_createReply( $email, $request, $member, $email->message . $email->quote, $email->from );
		static::makeAndClaimAttachments( $email->attachments, $reply );
		
		/* Send confirmation email */
		if ( \IPS\Settings::i()->nexus_sout_autoreply )
		{
			$confirmationEmail = \IPS\Email::buildFromTemplate( 'nexus', 'emailConfirmation', array( $request ), \IPS\Email::TYPE_TRANSACTIONAL );
			$confirmationEmail->from = $request->department->email;
			switch ( \IPS\Settings::i()->nexus_sout_from )
			{
				case 'staff':
				case 'dpt':
					$confirmationEmail->fromName = $confirmationEmail->language->get( 'nexus_department_' . $request->department->_id );
					break;
				default:
					$confirmationEmail->fromName = \IPS\Settings::i()->nexus_sout_from;
					break;
			}
			$confirmationEmail->send( $member->member_id ? $member : $email->from );
		}
		
		/* Return */
		return array( $request, $reply );
	}
	
	/**
	 * Create a basic support request object
	 *
	 * @param	\IPS\Member						$member		The member it's from
	 * @param	\IPS\nexus\Support\Department	$department	The department to put it in
	 * @return	\IPS\nexus\Support\Request
	 */
	protected function _initRequest( \IPS\Member $member, \IPS\nexus\Support\Department $department )
	{
		$request = new \IPS\nexus\Support\Request;
		$request->department = $department;	
		$request->status = \IPS\nexus\Support\Status::load( TRUE, 'status_default_member' );
		$request->severity = \IPS\nexus\Support\Severity::load( TRUE, 'sev_default' );
		$request->started = time();
		$request->last_reply = time();
		$request->last_reply_by = (int) $member->member_id;
		$request->last_new_reply = time();
		$request->replies = 1;
		return $request;
	}
	
	/**
	 * Create a basic support reply object
	 *
	 * @param	\IPS\Email\Incoming\Email		$email		The email
	 * @param	\IPS\nexus\Support\Request		$request	The request it's for
	 * @param	\IPS\Member						$member		The member it's from
	 * @param	string							$message	The message body
	 * @param	string							$emailFrom	The email address it is from
	 * @return	\IPS\nexus\Support\Reply
	 */
	protected function _createReply( \IPS\Email\Incoming\Email $email, \IPS\nexus\Support\Request $request, \IPS\Member $member, $message, $emailFrom )
	{
		$reply = new \IPS\nexus\Support\Reply;
		$reply->request = $request->id;
		$reply->member = (int) $member->member_id;
		$reply->type = $reply::REPLY_EMAIL;
		$reply->post = $message;
		$reply->date = time();
		$reply->email = $emailFrom;
		$reply->cc = implode( ',', $email->cc );
		$reply->raw = $email->raw;
		$reply->save();
		return $reply;
	}
	
	/**
	 * If this message was forwarded by a staff member, get the
	 * email address it was sent to that staff member from (i.e.
	 * the user who should show as creating the support request)
	 *
	 * @param	\IPS\Email\Incoming\Email		$email		The email
	 * @return	bool
	 */
	protected function _originalSenderOfForwardedMessage( \IPS\Email\Incoming\Email $email )
	{
		$originallyFrom = NULL;
		if ( isset( $email->headers['original-recipient'] ) )
		{
			$originallyFrom = preg_replace( '/^.*;(.*)$/', '$1', $email->headers['original-recipient'] );
		}
		else
		{
			if ( preg_match( '/From:.+?(\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b)/i', $email->quote, $_matches ) )
			{
				$originallyFrom = $_matches[1];
			}
		}
				
		return $originallyFrom;
	}
	
	/**
	 * Make and claim attachments
	 *
	 * @param	array						$files	\IPS\File objects
	 * @param	\IPS\nexus\Support\Reply	$reply	The support request reply
	 * @return	void
	 */
	public static function makeAndClaimAttachments( array $files, \IPS\nexus\Support\Reply $reply )
	{
		foreach ( $files as $file )
		{
			$attachment = $file->makeAttachment('');
			
			\IPS\Db::i()->insert( 'core_attachments_map', array(
				'attachment_id'	=> $attachment['attach_id'],
				'location_key'	=> 'nexus_Support',
				'id1'			=> $reply->request,
				'id2'			=> $reply->id,
			) );
		}
	}
}