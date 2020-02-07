<?php
/**
 * @brief		reCAPTCHA
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		5 Dec 2014
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
class _Recaptcha2
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
		$publicKey = \IPS\Settings::i()->recaptcha2_public_key;
		$theme = \IPS\Theme::i()->settings['recaptcha2_theme'];
		$lang = preg_replace( '/^(.+?)\..*$/', '$1', \IPS\Member::loggedIn()->language()->short );
		
		return <<<HTML
		<div data-ipsCaptcha data-ipsCaptcha-service='recaptcha2' data-ipsCaptcha-key="{$publicKey}" data-ipsCaptcha-lang="{$lang}" data-ipsCaptcha-theme="{$theme}">
			<noscript>
			  <div style="width: 302px; height: 352px;">
			    <div style="width: 302px; height: 352px; position: relative;">
			      <div style="width: 302px; height: 352px; position: absolute;">
			        <iframe src="https://www.google.com/recaptcha/api/fallback?k={$publicKey}" frameborder="0" scrolling="no" style="width: 302px; height:352px; border-style: none;">
			        </iframe>
			      </div>
			      <div style="width: 250px; height: 80px; position: absolute; border-style: none; bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">
			        <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 80px; border: 1px solid #c1c1c1; margin: 0px; padding: 0px; resize: none;" value="">
			        </textarea>
			      </div>
			    </div>
			  </div>
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
			$response = \IPS\Http\Url::external( 'https://www.google.com/recaptcha/api/siteverify' )->request()->post( array(
				'secret'		=> \IPS\Settings::i()->recaptcha2_private_key,
				'response'		=> trim( \IPS\Request::i()->__get('g-recaptcha-response') ),
				'remoteip'		=> \IPS\Request::i()->ipAddress(),
			) )->decodeJson( TRUE );
			
			return (bool) $response['success'];
		}
		catch( \RuntimeException $e )
		{
			if( $e->getMessage() == 'BAD_JSON' )
			{
				return FALSE;
			}
			else
			{
				throw $e;
			}
		}
	}

}