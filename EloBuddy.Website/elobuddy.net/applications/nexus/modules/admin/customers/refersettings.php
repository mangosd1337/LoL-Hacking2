<?php
/**
 * @brief		Referral Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		15 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\customers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Referral Settings
 */
class _refersettings extends \IPS\Dispatcher\Controller
{	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function execute()
	{
		$tabsKey = md5( \IPS\Http\Url::internal("app=nexus&module=customers&controller=referrals") );
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\YesNo( 'cm_ref_on', \IPS\Settings::i()->cm_ref_on, FALSE, array( 'togglesOn' => array( 'nexus_com_rules' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_com_rules', \IPS\Settings::i()->nexus_com_rules, FALSE, array( 'togglesOff' => array( 'nexus_com_rules_alt' ) ), NULL, NULL, NULL, 'nexus_com_rules' ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'nexus_com_rules_alt', NULL, FALSE, array( 'app' => 'nexus', 'key' => 'nexus_com_rules_val', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'nexus_com_rules_alt', 'attachIds' => array( NULL, NULL, 'nexus_com_rules_alt' ) ) ), NULL, NULL, NULL, 'nexus_com_rules_alt' ) );

		if ( $values = $form->values() )
		{
			\IPS\Lang::saveCustom( 'nexus', 'nexus_com_rules_val', $values['nexus_com_rules_alt'] );
			unset( $values['nexus_com_rules_alt'] );
			
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=customers&controller=referrals') );
		}
		
		\IPS\Output::i()->output = $form;
	}
}