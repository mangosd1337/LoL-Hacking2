<?php
/**
 * @brief		StumbleUpon share link
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Sept 2013
 * @version		SVN_VERSION_NUMBER
 * @see			<a href='http://www.stumbleupon.com/dt/badges/create'>StumbleUpon button documentation</a>
 */

namespace IPS\Content\ShareServices;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * StumbleUpon share link
 * @note	StumbleUpon does not provide a way to specify the language/locale
 */
class _Stumble
{
	/**
	 * @brief	URL to the content item
	 */
	protected $url		= NULL;
	
	/**
	 * @brief	Title of the content item
	 */
	protected $title	= NULL;
		
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url	$url	URL to the content [optional - if omitted, some services will figure out on their own]
	 * @param	string			$title	Default text for the content, usually the title [optional - if omitted, some services will figure out on their own]
	 * @return	void
	 */
	public function __construct( \IPS\Http\Url $url=NULL, $title=NULL )
	{
		$this->url		= $url;
		$this->title	= $title;
	}
		
	/**
	 * Determine whether the logged in user has the ability to autoshare
	 *
	 * @return	boolean
	 */
	public static function canAutoshare()
	{
		return false;
	}

	/**
	 * Add any additional form elements to the configuration form. These must be setting keys that the service configuration form can save as a setting.
	 *
	 * @param	\IPS\Helpers\Form	$form	Configuration form for this service
	 * @return	void
	 */
	public static function modifyForm( \IPS\Helpers\Form &$form )
	{
	}

	/**
	 * Return the HTML code to show the share link
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return \IPS\Theme::i()->getTemplate( 'sharelinks', 'core' )->stumble( urlencode( $this->url ) );
	}
}