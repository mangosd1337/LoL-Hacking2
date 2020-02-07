<?php
/**
 * @brief		Google Analytics Community Enhancements
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Aug 2013
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
 * Google Analytics Community Enhancement
 */
class _GoogleAnalytics
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public $icon	= "google_analytics.png";

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = \IPS\Settings::i()->ipbseo_ga_enabled;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipbseo_ga_enabled', \IPS\Settings::i()->ipbseo_ga_enabled, TRUE, array( 'togglesOn' => array( 'ipseo_ga' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Codemirror( 'ipseo_ga', \IPS\Settings::i()->ipseo_ga, FALSE, array(), NULL, NULL, NULL, 'ipseo_ga' ) );

		if ( $form->values() )
		{
			$this->testSettings();
			$form->saveAsSettings();

			\IPS\Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_GoogleAnalytics' => TRUE ) );
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
		}
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'signup_googleanalytics',
				'icon'		=> 'external-link-square',
				'link'		=> \IPS\Http\Url::ips( 'docs/googleanalytics' ),
				'target'	=> '_blank'
			),
		);
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GoogleAnalytics', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 */
	public function toggle( $enabled )
	{
		if ( $enabled )
		{
			$this->testSettings();
		}
		
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $enabled ), array( 'conf_key=?', 'ipbseo_ga_enabled' ) );
		unset( \IPS\Data\Store::i()->settings );
	}
	
	/**
	 * Test Settings
	 *
	 * @return	void
	 */
	protected function testSettings()
	{
	}
}