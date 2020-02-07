<?php
/**
 * @brief		Community Enhancements: IPS GeoIP Service
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
 * Community Enhancement
 */
class _GeoIP
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public $ips	= TRUE;

	/**
	 * @brief	Enhancement is enabled?
	 */
	public $enabled	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public $hasOptions	= FALSE;

	/**
	 * @brief	Icon data
	 */
	public $icon	= "ips.png"; 
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = \IPS\Settings::i()->ipsgeoip;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit()
	{
		$form = new \IPS\Helpers\Form;		
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipsgeoip', \IPS\Settings::i()->ipsgeoip ) );
		if ( $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_GeoIP' => TRUE ) );
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GeoIP', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( $enabled )
	{
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $enabled ), array( 'conf_key=?', 'ipsgeoip' ) );
		unset( \IPS\Data\Store::i()->settings );
	}
}