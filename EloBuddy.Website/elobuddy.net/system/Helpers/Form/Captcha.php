<?php
/**
 * @brief		Captcha class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Apr 2013
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
 * Captcha class for Form Builder
 */
class _Captcha extends FormAbstract
{
	/**
	 * CAPTCHA Class
	 */
	protected $captcha = NULL;
	
	/**
	 * Constructor
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		$params = func_get_args();
		if ( !isset( $params[0] ) )
		{
			$params[0] = 'captcha_field';
		}
		
		if ( \IPS\Settings::i()->bot_antispam_type != 'none' )
		{
			$class = '\IPS\Helpers\Form\Captcha\\' . ucfirst( \IPS\Settings::i()->bot_antispam_type );
			if ( !class_exists( $class ) )
			{
				\IPS\Output::i()->error( 'unexpected_captcha', '4S262/1', 500, 'unexpected_captcha_admin' );
			}
			$this->captcha = new $class;
		}
		
		call_user_func_array( 'parent::__construct', $params );
		$this->required = TRUE;
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		if ( $this->captcha === NULL )
		{
			return '';
		}
		return parent::__toString();
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		if ( $this->captcha === NULL )
		{
			return '';
		}
		return $this->captcha->getHtml();
	}
	
	/**
	 * Get Value
	 *
	 * @return	bool|null	TRUE/FALSE indicate if the test passed or not. NULL indicates the test failed, but the captcha system will display an error so we don't have to.
	 */
	public function getValue()
	{
		if ( $this->captcha === NULL )
		{
			return TRUE;
		}
		else
		{
			/* If we previously did an AJAX validate which is still valid, return true */
			if ( isset( $_SESSION[ 'captcha-val-' . $this->name ] ) and $_SESSION[ 'captcha-val-' . $this->name ] > ( time() - 60 ) )
			{
				unset( $_SESSION[ 'captcha-val-' . $this->name ] );
				return TRUE;
			}
			/* Otherwise, check with service */
			else
			{
				/* Check */
				$return = $this->captcha->verify();
				
				/* If it's valid and we're doing an AJAX validate, save that in the session so the next request doesn't check again */
				if ( $return and \IPS\Request::i()->isAjax() )
				{
					$_SESSION[ 'captcha-val-' . $this->name ] = time();
				}
				
				/* Return */
				return $return;
			}
		}
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		if ( $this->value !== TRUE )
		{
			throw new \InvalidArgumentException( 'form_bad_captcha' );
		}
		
		return TRUE;
	}
}