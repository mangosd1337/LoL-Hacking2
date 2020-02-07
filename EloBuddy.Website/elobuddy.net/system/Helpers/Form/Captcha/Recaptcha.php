<?php
/**
 * @brief		reCAPTCHA
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form\Captcha;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reCAPTCHA
 */
class _Recaptcha
{
	/**
	 * @brief	Error
	 */
	protected $error;

	/**
	 * Display
	 *
	 * @return	string
	 */
	public function getHtml()
	{
		$publicKey = \IPS\Settings::i()->recaptcha_public_key;
		$theme = \IPS\Theme::i()->settings['recaptcha_theme'];
		$lang = preg_replace( '/^(.+?)\..*$/', '$1', \IPS\Member::loggedIn()->language()->short );
		
		return <<<HTML
		<div data-ipsCaptcha data-ipsCaptcha-service='recaptcha' data-ipsCaptcha-key="{$publicKey}" data-ipsCaptcha-lang="{$lang}" data-ipsCaptcha-theme="{$theme}">
			<noscript>
				<iframe src="//www.google.com/recaptcha/api/noscript?k={$publicKey}&hl={$lang}&error={$this->error}"
				 height="300" width="500" frameborder="0"></iframe><br>
					<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
					<input type="hidden" name="recaptcha_response_field" value="manual_challenge">
			</noscript>
		</div>
HTML;
	}
	
	/**
	 * Verify
	 *
	 * @return	bool|null	TRUE/FALSE indicate if the test passed or not. NULL indicates the test failed, but the captcha system will display an error so we don't have to.
	 */
	public function verify()
	{
		try
		{
			$response = \IPS\Http\Url::external( 'http://www.google.com/recaptcha/api/verify' )->request()->post( array(
				'privatekey'	=> \IPS\Settings::i()->recaptcha_private_key,
				'remoteip'		=> \IPS\Request::i()->ipAddress(),
				'challenge'		=> \IPS\Request::i()->recaptcha_challenge_field,
				'response'		=> \IPS\Request::i()->recaptcha_response_field
			) );
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			return FALSE;
		}
		
		$response = explode( "\n", $response );

		if ( $response[0] === 'true' )
		{
			return true;
		}
		else
		{
			$this->error = $response[1];
			return null;
		}
	}

}