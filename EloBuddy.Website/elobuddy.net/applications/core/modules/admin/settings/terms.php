<?php
/**
 * @brief		Manage Terms & Privacy Policy
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		07 Jun 2013
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
 * terms
 */
class _terms extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'terms_manage' );
		parent::execute();
	}

	/**
	 * Manage Terms & Privacy Policy
	 *
	 * @return	void
	 */
	protected function manage()
	{
 		$form = new \IPS\Helpers\Form;
		$form->addHeader( 'terms_guidelines' );
		$form->add( new \IPS\Helpers\Form\Radio( 'gl_type', \IPS\Settings::i()->gl_type, FALSE, array(
				'options' => array(
						'internal' => 'gl_internal',
						'external' => 'gl_external',
						'none' => "gl_none" ),
				'toggles' => array(
						'internal'	=> array( 'gl_guidelines_id' ),
						'external'	=> array( 'gl_link' ),
						'none'		=> array(),
				)
		) ) );
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'gl_guidelines', NULL, FALSE, array( 'app' => 'core', 'key' => 'guidelines_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'Guidelines', 'attachIds' => array( NULL, NULL, 'gl_guidelines' ) ) ), NULL, NULL, NULL, 'gl_guidelines_id' ) );
		$form->add( new \IPS\Helpers\Form\Url( 'gl_link', \IPS\Settings::i()->gl_link, FALSE, array(), NULL, NULL, NULL, 'gl_link'  ) );
		$form->addHeader( 'terms_privacy');
		$form->add( new \IPS\Helpers\Form\Radio( 'privacy_type', \IPS\Settings::i()->privacy_type, FALSE, array(
				'options' => array(
						'internal' => 'privacy_internal',
						'external' => 'privacy_external',
						'none' => "privacy_none" ),
				'toggles' => array(
						'internal'	=> array( 'privacy_text_id' ),
						'external'	=> array( 'privacy_link' ),
						'none'		=> array(),
				)
		) ) );
		
		$form->add( new \IPS\Helpers\Form\Translatable( 'privacy_text', NULL, FALSE, array( 'app' => 'core', 'key' => 'privacy_text_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'Privacy', 'attachIds' => array( NULL, NULL, 'privacy_text' ) ) ), NULL, NULL, NULL, 'privacy_text_id' ) );
		$form->add( new \IPS\Helpers\Form\Url( 'privacy_link', \IPS\Settings::i()->privacy_link, FALSE, array(), NULL, NULL, NULL, 'privacy_link' ) );
			
		$form->addHeader( 'terms_registration' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'reg_rules', NULL, FALSE, array( 'app' => 'core', 'key' => 'reg_rules_value', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'RegistrationRules', 'attachIds' => array( NULL, NULL, 'reg_rules' ) ) ), NULL, NULL, NULL, 'reg_rules_id' ) );
		
		if ( $values = $form->values() )
		{
			/* What were our previous values? */
			$existingPrivacyPolicy = iterator_to_array( \IPS\Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'privacy_text_value' ) ) );
			$existingRegistrationTerms = iterator_to_array( \IPS\Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'reg_rules_value' ) ) );
			
			/* Save */
			foreach ( array( 'gl_guidelines' => 'guidelines_value', 'privacy_text' => 'privacy_text_value', 'reg_rules' => 'reg_rules_value' ) as $k => $v )
			{
				\IPS\Lang::saveCustom( 'core', $v, $values[ $k ] );
				unset( $values[ $k ] );
			}
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();
			
			/* Log */
			\IPS\Session::i()->log( 'acplogs__terms_edited' );
			
			/* Do we need to ask the admin if they want to ask members to reconfirm? */
			$changedPrivacyPolicy = $existingPrivacyPolicy != iterator_to_array( \IPS\Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'privacy_text_value' ) ) );
			$changedRegistrationTerms = $existingRegistrationTerms != iterator_to_array( \IPS\Db::i()->select( 'word_custom', 'core_sys_lang_words', array( 'word_key=?', 'reg_rules_value' ) ) );
			if ( $changedPrivacyPolicy or $changedRegistrationTerms )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=settings&controller=terms&do=reconfirm')->setQueryString( array(
					'privacy'	=> intval( $changedPrivacyPolicy ),
					'reg'		=> intval( $changedRegistrationTerms )
				) ) );
			}
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_settings_terms');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_terms', $form );
	}
	
	/**
	 * Ask the admin if they want users to re-confirm
	 *
	 * @return	void
	 */
	protected function reconfirm()
	{
		$form = new \IPS\Helpers\Form;
		
		$form->addMessage( 'admin_reconfirm_blurb' );
				
		if ( \IPS\Request::i()->reg )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'admin_reconfirm_reg_terms', FALSE ) );
		}
		
		if ( \IPS\Request::i()->privacy )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'admin_reconfirm_privacy', FALSE ) );
		}
		
		if ( $values = $form->values() )
		{
			if ( isset( $values['admin_reconfirm_reg_terms'] ) and $values['admin_reconfirm_reg_terms'] )
			{
				\IPS\Member::updateAllMembers( array( "members_bitoptions2 = members_bitoptions2 | " . \IPS\Member::$bitOptions['members_bitoptions']['members_bitoptions2']['must_reaccept_terms'] ) );
			}
			if ( isset( $values['admin_reconfirm_privacy'] ) and $values['admin_reconfirm_privacy'] )
			{
				\IPS\Member::updateAllMembers( array( "members_bitoptions2 = members_bitoptions2 | " . \IPS\Member::$bitOptions['members_bitoptions']['members_bitoptions2']['must_reaccept_privacy'] ) );
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=core&module=settings&controller=terms') );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_settings_terms');
		\IPS\Output::i()->output	= $form;
	}
}