<?php
/**
 * @brief		general
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		17 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * general
 */
class _general extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'general_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Text( 'board_name', \IPS\Settings::i()->board_name, TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'site_online', \IPS\Settings::i()->site_online, FALSE, array(
			'togglesOff'	=> array( 'site_offline_message_id' ),
		) ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'site_offline_message', \IPS\Settings::i()->site_offline_message, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'onlineoffline', 'attachIds' => array( NULL, NULL, 'site_offline_message' ) ), NULL, NULL, NULL, 'site_offline_message_id' ) );
		$form->add( new \IPS\Helpers\Form\Address( 'site_address', \IPS\GeoLocation::buildFromJson( \IPS\Settings::i()->site_address ), FALSE ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'copyright_line', NULL, FALSE, array( 'app' => 'core', 'key' => 'copyright_line_value', 'placeholder' => \IPS\Member::loggedIn()->language()->addToStack('copyright_line_placeholder') ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'upgrade_email', explode( ',', \IPS\Settings::i()->upgrade_email ), FALSE, array( 'stackFieldType' => 'Email', 'maxItems' => 5 ) ) );
		
		if ( $values = $form->values() )
		{
			\IPS\Lang::saveCustom( 'core', "copyright_line_value", $values['copyright_line'] );
			unset( $values['copyright_line'] );
			
			$form->saveAsSettings();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__general_settings' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_settings_general');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_general', $form );
	}
}