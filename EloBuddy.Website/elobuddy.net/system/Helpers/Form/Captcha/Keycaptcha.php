<?php
/**
 * @brief		keyCAPTCHA
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
 * keyCAPTCHA
 */
class _Keycaptcha
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
		$explodedKey	= explode( '0', \IPS\Settings::i()->keycaptcha_privatekey, 2 );
		$uniq			= md5( uniqid() );
		$sign			= md5( $uniq . \IPS\Request::i()->ipAddress . $explodedKey[0] );
		$sign2			= md5( $uniq . $explodedKey[0] );
						
		return <<<HTML
<input type='hidden' id='capcode' name='keycaptcha'>
<script type="text/javascript">
	// required
	var s_s_c_user_id = '{$explodedKey[1]}';
	var s_s_c_session_id = '{$uniq}';
	var s_s_c_captcha_field_id = 'capcode';
	var s_s_c_submit_button_id ='sbutton-#-r';
	var s_s_c_web_server_sign = '{$sign}';
	var s_s_c_web_server_sign2 = '{$sign2}';
</script>
<div data-ipsCaptcha data-ipsCaptcha-service='keycaptcha' id='div_for_keycaptcha'></div>
HTML;
	}
	
	/**
	 * Verify
	 *
	 * @return	bool|null	TRUE/FALSE indicate if the test passed or not. NULL indicates the test failed, but the captcha system will display an error so we don't have to.
	 */
	public function verify()
	{
		$explodedResponse	= explode( '|', \IPS\Request::i()->keycaptcha );
		$explodedKey		= explode( '0', \IPS\Settings::i()->keycaptcha_privatekey );
	
		if( \IPS\Login::compareHashes( $explodedResponse[0], md5( 'accept' . $explodedResponse[1] . $explodedKey[0] . $explodedResponse[2] ) ) )
		{
			if( (string) \IPS\Http\Url::external( $explodedResponse[2] )->request()->get() === '1' )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

}