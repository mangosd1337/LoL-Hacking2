<?php
/**
 * @brief		Lost Password
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		26 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Lost Password
 */
class _lostpass extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Build the form */
		$form =  new \IPS\Helpers\Form( "lostpass", 'request_password' );
		$form->add( new \IPS\Helpers\Form\Email( 'email_address', NULL, TRUE, array(), function( $val ){
			
			/* Check email exists */
			$member = \IPS\Member::load( $val, 'email' );
			
			if( !$member->member_id )
			{
				throw new \LogicException( 'lost_pass_no_email' );
			}
		}) );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('lost_password');
		
		/* Handle the reset */
		if ( $values = $form->values() )
		{
			/* Load the member */
			$member = \IPS\Member::load( $values['email_address'], 'email' );
			
			/* If we have an existing validation record, we can just reuse it */
			$sendEmail = TRUE;
			try
			{
				$existing = \IPS\Db::i()->select( array( 'vid', 'email_sent' ), 'core_validating', array( 'member_id=? AND lost_pass=1', $member->member_id ) )->first();
				$vid = $existing['vid'];
				
				/* If we sent a lost password email within the last 15 minutes, don't send another one otherwise someone could be a nuisence */
				if ( $existing['email_sent'] and $existing['email_sent'] > ( time() - 900 ) )
				{
					$sendEmail = FALSE;
				}
				else
				{
					\IPS\Db::i()->update( 'core_validating', array( 'email_sent' => time() ), array( 'vid=?', $vid ) );
				}
			}
			catch ( \UnderflowException $e )
			{
				$vid = md5( $member->members_pass_hash . \IPS\Login::generateRandomString() );
				
				\IPS\Db::i()->insert( 'core_validating', array(
					'vid'         => $vid,
					'member_id'   => $member->member_id,
					'entry_date'  => time(),
					'lost_pass'   => 1,
					'ip_address'  => $member->ip_address,
					'email_sent'  => time(),
				) );
			}
						
			/* Send email */
			if ( $sendEmail )
			{
				\IPS\Email::buildFromTemplate( 'core', 'lost_password_init', array( $member, $vid ), \IPS\Email::TYPE_TRANSACTIONAL )->send( $member );
				$message = "lost_pass_confirm";
			}
			else
			{
				$message = "lost_pass_too_soon";
			}
			
			/* Show confirmation page with further instructions */
			\IPS\Output::i()->sidebar['enabled'] = FALSE;
			\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->lostPassConfirm( $message );
		}
		else
		{
			\IPS\Output::i()->sidebar['enabled'] = FALSE;
			\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->lostPass( $form );
		}
	}
	
	/**
	 * Validate
	 *
	 * @return	void
	 */
	protected function validate()
	{
		try
		{
			$record = \IPS\Db::i()->select( '*', 'core_validating', array( 'vid=? AND member_id=? AND lost_pass=1', \IPS\Request::i()->vid, \IPS\Request::i()->mid ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'no_validation_key', '2S151/1', 410, '' );
		}
		
		/* Show form for new password */
		$form =  new \IPS\Helpers\Form( "resetpass", 'save' );
		$form->add( new \IPS\Helpers\Form\Password( 'password', NULL, TRUE, array() ) );
		$form->add( new \IPS\Helpers\Form\Password( 'password_confirm', NULL, TRUE, array( 'confirm' => 'password' ) ) );

		/* Set new password */
		if ( $values = $form->values() )
		{
			/* Get the member */
			$member = \IPS\Member::load( $record['member_id'] );

			/* Reset the failed logins storage - we don't need to save because the login handler will do that for us later */
			$member->failed_logins		= array();

			/* Now reset the member's password */
			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				/* We cannot update our password in some login handlers, that's ok */
				try
				{
					$handler->changePassword( $member, $values['password'] );
				}
				catch( \BadMethodCallException $e ){}
			}
			
			/* Delete validating record and log in */
			\IPS\Db::i()->delete( 'core_validating', array( 'member_id=? AND lost_pass=1', $member->member_id ) );
			
			/* Log in and redirect */
			\IPS\Session::i()->setMember( $member );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
		}

		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->resetPass( $form );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'lost_password' );
	}
}