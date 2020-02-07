<?php
/**
 * @brief		Registration
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\membersettings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Registration
 */
class _registration extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'registration_manage' );
		parent::execute();
	}

	/**
	 * General Member Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new \IPS\Helpers\Form;
		
		$form->addHeader('registration');
		$form->add( new \IPS\Helpers\Form\YesNo( 'allow_reg', \IPS\Settings::i()->allow_reg, FALSE, array( 'togglesOn' => array( 'new_reg_notify', 'reg_auth_type', 'form_header_coppa', 'use_coppa' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'new_reg_notify', \IPS\Settings::i()->new_reg_notify, FALSE, array(), NULL, NULL, NULL, 'new_reg_notify' ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'reg_auth_type', \IPS\Settings::i()->reg_auth_type, TRUE, array(
			'options'	=> array( 'user' => 'reg_auth_type_user', 'admin' => 'reg_auth_type_admin', 'admin_user' => 'reg_auth_type_admin_user', 'none' => 'reg_auth_type_none' ),
			'toggles'	=> array( 'user' => array( 'validate_day_prune' ), 'admin_user' => array( 'validate_day_prune' ) )
		), NULL, NULL, NULL, 'reg_auth_type' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'validate_day_prune', \IPS\Settings::i()->validate_day_prune, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days'), 'validate_day_prune' ) );
		
		$form->addHeader('coppa');
		$form->add( new \IPS\Helpers\Form\YesNo( 'use_coppa', \IPS\Settings::i()->use_coppa, FALSE, array( 'togglesOn' => array( 'coppa_fax', 'coppa_address' ) ), NULL, NULL, NULL, 'use_coppa' ) );
		$form->add( new \IPS\Helpers\Form\Tel( 'coppa_fax', \IPS\Settings::i()->coppa_fax, FALSE, array(), NULL, NULL, NULL, 'coppa_fax' ) );
		$form->add( new \IPS\Helpers\Form\Address( 'coppa_address', \IPS\GeoLocation::buildFromJson( \IPS\Settings::i()->coppa_address ), FALSE, array(), NULL, NULL, NULL, 'coppa_address' ) );
				
		if ( $form->values() )
		{
			$form->saveAsSettings();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__general_settings' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('membersettings_registration_title');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'membersettings_registration_title', $form );
	}
}