<?php
/**
 * @brief		Email input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email input class for Form Builder
 */
class _Email extends Text
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'accountEmail' => TRUE,	// If TRUE, additional checks will be performed to ensure provided email address is acceptable for use on a user's account. If an \IPS\Member object, that member will be excluded
	 	);
	 * @endcode
	 */
	public $childDefaultOptions = array(
		'accountEmail'	=> FALSE,
	);
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @throws	\DomainException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
		
		/* Check it's generally an acceptable email */
		if ( $this->value !== '' and filter_var( $this->value, FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			throw new \InvalidArgumentException('form_email_bad');
		}
		
		/* If it's for a user account, do additional checks */
		if ( $this->options['accountEmail'] )
		{
			/* Check if it exists */
			foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
			{
				if ( $handler->emailIsInUse( $this->value, ( $this->options['accountEmail'] instanceof \IPS\Member ) ? $this->options['accountEmail'] : NULL ) === TRUE )
				{
					if ( \IPS\Member::loggedIn()->isAdmin() )
					{
						throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack('member_email_exists_admin', FALSE, array( 'sprintf' => array( $k ) ) ) );
					}
					else
					{
						throw new \DomainException( 'member_email_exists' );
					}
				}
			}
			
			/* Check it's not banned */
 			foreach ( \IPS\Db::i()->select( 'ban_content', 'core_banfilters', array( "ban_type=?", 'email' ) ) as $bannedEmail )
 			{	 			
	 			if ( preg_match( '/^' . str_replace( '\*', '.*', preg_quote( $bannedEmail, '/' ) ) . '$/i', $this->value ) )
	 			{
	 				throw new \DomainException( 'form_email_banned' );
	 			}
 			}
		}
	}
}