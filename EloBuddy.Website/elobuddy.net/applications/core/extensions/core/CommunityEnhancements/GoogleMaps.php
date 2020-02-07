<?php
/**
 * @brief		Community Enhancement: Google Maps
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement: Google Maps
 */
class _GoogleMaps
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public $ips	= FALSE;

	/**
	 * @brief	Enhancement is enabled?
	 */
	public $enabled	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public $icon	= "google_maps.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = \IPS\Settings::i()->googlemaps or \IPS\Settings::i()->googleplacesautocomplete;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit()
	{
		$form = new \IPS\Helpers\Form;		
		$form->add( new \IPS\Helpers\Form\YesNo( 'googlemaps', \IPS\Settings::i()->googlemaps ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'googleplacesautocomplete', \IPS\Settings::i()->googleplacesautocomplete ) );
		if ( $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_GoogleMaps' => TRUE ) );
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
		}
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> \IPS\Http\Url::ips( 'docs/googlemaps' ),
				'target'	=> '_blank'
			),
		);
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GoogleMaps', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( $enabled )
	{
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $enabled ), array( 'conf_key=?', 'googlemaps' ) );
		unset( \IPS\Data\Store::i()->settings );
	}
}