<?php
/**
 * @brief		Password input class for Form Builder
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
 * Password input class for Form Builder
 */
class _Password extends Text
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'validateFor'	=> \IPS\Member::loggedIn(),	// If an \IPS\Member object is provided, the password will be checked if it is valid for that account. Default is NULL.
	 		'confirm'		=> 'password1',				// If the name of another element in the form is provided, will check is the values match. Default is NULL.
	 	);
	 * @endcode
	 */
	public $childDefaultOptions = array(
		'validateFor'	=> NULL,
		'confirm'		=> NULL
	);
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
		
		/* Password length */
		if ( mb_strlen( $this->value ) < 3 AND ( $this->required OR $this->value ) )
		{
			throw new \InvalidArgumentException( 'err_password_length' );
		}
		
		/* Is valid for member? */
		if ( $this->options['validateFor'] !== NULL )
		{
			$valid = FALSE;
			
			foreach( \IPS\Login::handlers( TRUE ) as $k => $handler )
			{
				if ( $handler->authTypes & \IPS\Login::AUTH_TYPE_USERNAME )
				{
					try
					{
						$handler->authenticate( array( 'auth' => $this->options['validateFor']->name, 'password' => $this->value ) );
						$valid = TRUE;
					}
					catch ( \Exception $e ) {}
				}
				if ( $handler->authTypes & \IPS\Login::AUTH_TYPE_EMAIL )
				{
					try
					{
						$handler->authenticate( array( 'auth' => $this->options['validateFor']->email, 'password' => $this->value ) );
						$valid = TRUE;
					}
					catch ( \Exception $e ) {}
				}
			}
			
			if ( !$valid )
			{
				throw new \InvalidArgumentException( 'login_err_bad_password' );
			}
		}
		
		/* Matches the other one? */
		if ( $this->options['confirm'] !== NULL )
		{
			$confirmKey = $this->options['confirm'];
			if ( $this->value !== \IPS\Request::i()->$confirmKey )
			{
				throw new \InvalidArgumentException( 'form_password_confirm' );
			}
		}
	}
}