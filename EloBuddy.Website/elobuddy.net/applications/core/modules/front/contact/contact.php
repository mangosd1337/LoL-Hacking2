<?php
/**
 * @brief		Contact Form
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\contact;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Contact Form
 */
class _contact extends \IPS\Dispatcher\Controller
{

	/**
	 * Method
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';

		$form = new \IPS\Helpers\Form( 'contact', 'send' );
		$form->class = 'ipsForm_vertical';
		
		$form->add( new \IPS\Helpers\Form\Editor( 'contact_text', NULL, TRUE, array(
				'app'			=> 'core',
				'key'			=> 'Contact',
				'autoSaveKey'	=> 'contact-' . \IPS\Member::loggedIn()->member_id,
				//'minimize'		=> 'x',
		) ) );
		
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			$form->add( new \IPS\Helpers\Form\Text( 'contact_name', NULL, TRUE ) );
			$form->add( new \IPS\Helpers\Form\Email( 'email_address', NULL, TRUE ) );
			$form->add( new \IPS\Helpers\Form\Captcha );
		}
		
		if ( $values = $form->values() )
		{
			/* Send the message */
			$fromName = ( \IPS\Member::loggedIn()->member_id ) ? \IPS\Member::loggedIn()->name : $values['contact_name'];
			$fromEmail = ( \IPS\Member::loggedIn()->member_id ) ? \IPS\Member::loggedIn()->email : $values['email_address'];

			$mail = \IPS\Email::buildFromTemplate( 'core', 'contact_form', array( \IPS\Member::loggedIn(), $fromName, $fromEmail, $values['contact_text'] ), \IPS\Email::TYPE_TRANSACTIONAL );
			$mail->send( \IPS\Settings::i()->email_in, array(), array(), NULL, $fromName, array( 'Reply-To' => \IPS\Email::encodeHeader( $fromName, ( \IPS\Member::loggedIn()->member_id ? \IPS\Member::loggedIn()->email : $values['email_address'] ) ) ) );
			
			if( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( 'OK' );
			}

			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('message_sent');
			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'system' )->contactDone();
		}
		else
		{
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('contact');
			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'system' )->contact( $form );	
		}	
		
	}
}