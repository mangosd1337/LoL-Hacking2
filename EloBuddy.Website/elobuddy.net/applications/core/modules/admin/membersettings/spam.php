<?php
/**
 * @brief		Spam Prevention Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Apr 2013
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
 * Spam Prevention Settings
 */
class _spam extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* Get tab content */
		$this->activeTab = \IPS\Request::i()->tab ?: 'captcha';

		\IPS\Dispatcher::i()->checkAcpPermission( 'spam_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Work out output */
		$activeTabContents = call_user_func( array( $this, '_manage'.ucfirst( $this->activeTab  ) ) );
		
		/* If this is an AJAX request, just return it */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Build tab list */
		$tabs = array();
		$tabs['captcha']	= 'spamprevention_captcha';
		$tabs['flagging']	= 'spamprevention_flagging';
		$tabs['service']	= 'enhancements__core_SpamMonitoring';

		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'qanda_manage' ) )
		{
			$tabs['qanda']		= 'qanda_settings';
		}
				
		/* Add a button for logs */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'spam_service_log' ) )
		{
			\IPS\Output::i()->sidebar['actions']['errorLog'] = array(
					'title'		=> 'spamlogs',
					'icon'		=> 'exclamation-triangle',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=serviceLogs' ),
			);
		}
			
		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_membersettings_spam');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, $this->activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=membersettings&controller=spam" ) );
	}

	/**
	 * Return the CAPTCHA options - abstracted for third parties
	 *
	 * @param	string	$type	'options' for the select options, 'toggles' for the toggles
	 * @return	array
	 */
	protected function getCaptchaOptions( $type='options' )
	{
		switch( $type )
		{
			case 'options':
				return array( 'none' => 'captcha_type_none', 'recaptcha2' => 'captcha_type_recaptcha2', 'recaptcha' => 'captcha_type_recaptcha', 'keycaptcha' => 'captcha_type_keycaptcha' );
			break;

			case 'toggles':
				return array(
					'none'			=> array( 'bot_antispam_type_warning' ),
					'recaptcha2'	=> array( 'guest_captcha', 'recaptcha2_public_key', 'recaptcha2_private_key' ),
					'recaptcha'		=> array( 'guest_captcha', 'recaptcha_public_key', 'recaptcha_private_key' ),
					'keycaptcha'	=> array( 'guest_captcha', 'keycaptcha_privatekey' )
				);
			break;
		}

		return array();
	}
	
	/**
	 * Show CAPTCHA settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageCaptcha()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Radio( 'bot_antispam_type', \IPS\Settings::i()->bot_antispam_type, TRUE, array(
			'options'	=> $this->getCaptchaOptions( 'options' ),
			'toggles'	=> $this->getCaptchaOptions( 'toggles' ),
		), NULL, NULL, NULL, 'bot_antispam_type' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'recaptcha2_public_key', \IPS\Settings::i()->recaptcha2_public_key, FALSE, array(), NULL, NULL, NULL, 'recaptcha2_public_key' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'recaptcha2_private_key', \IPS\Settings::i()->recaptcha2_private_key, FALSE, array(), NULL, NULL, NULL, 'recaptcha2_private_key' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'recaptcha_public_key', \IPS\Settings::i()->recaptcha_public_key, FALSE, array(), NULL, NULL, NULL, 'recaptcha_public_key' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'recaptcha_private_key', \IPS\Settings::i()->recaptcha_private_key, FALSE, array(), NULL, NULL, NULL, 'recaptcha_private_key' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'keycaptcha_privatekey', \IPS\Settings::i()->keycaptcha_privatekey, FALSE, array(), NULL, NULL, NULL, 'keycaptcha_privatekey' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'guest_captcha', \IPS\Settings::i()->guest_captcha, FALSE, array(), NULL, NULL, \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'membertools_delete' ) ? ( "<a href='" . \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=deleteGuestContent' ) . "' data-ipsDialog data-ipsDialog-title='" . \IPS\Member::loggedIn()->language()->addToStack('member_delete_guest_content'). "'>" . \IPS\Member::loggedIn()->language()->addToStack('member_delete_guest_content') . "</a>" ) : '', 'guest_captcha' ) );

		/* Save values */
		if ( $form->values() )
		{
			$form->saveAsSettings();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__spamprev_settings' );
		}

		return $form;
	}

	/**
	 * Show spammer flagging settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageFlagging()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'spm_option', explode( ',', \IPS\Settings::i()->spm_option ), FALSE, array(
			'options' 	=> array( 'disable' => 'spm_option_disable', 'unapprove' => 'spm_option_unapprove', 'delete' => 'spm_option_delete', 'ban' => 'spm_option_ban' ),
		) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'spm_notify', \IPS\Settings::i()->spm_notify ) );
		
		/* Save values */
		if ( $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplogs__spamprev_settings' );
		}

		return $form;
	}

	/**
	 * Show IPS Spam Service settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageService()
	{
		$licenseData = \IPS\IPS::licenseKey();
		
		/* Build Form */
		$actions = array( 1 => 'spam_service_act_1', 2 => 'spam_service_act_2', 3 => 'spam_service_act_3', 4 => 'spam_service_act_4' );
		$form = new \IPS\Helpers\Form;
		$form->addHeader( 'enhancements__core_SpamMonitoring' );

		$disabled = FALSE;
		if( !$licenseData or !$licenseData['products']['spam'] or strtotime( $licenseData['expires'] ) < time() )
		{
			$disabled = TRUE;
			if( !\IPS\Settings::i()->ipb_reg_number )
			{
				\IPS\Member::loggedIn()->language()->words['spam_service_enabled_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'spam_service_nokey', FALSE, array( 'sprintf' => array( \IPS\Http\Url::internal( 'app=core&module=settings&controller=licensekey', null ) ) ) );
			}
			else
			{
				\IPS\Member::loggedIn()->language()->words['spam_service_enabled_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'spam_service_noservice' );
			}
		}
		
		$form->add( new \IPS\Helpers\Form\YesNo( 'spam_service_enabled', \IPS\Settings::i()->spam_service_enabled, FALSE, array( 'disabled' => $disabled, 'togglesOn' => array( 'spam_service_send_to_ips', 'spam_service_action_0', 'spam_service_action_1', 'spam_service_action_2', 'spam_service_action_3', 'spam_service_action_4' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'spam_service_send_to_ips', \IPS\Settings::i()->spam_service_send_to_ips, FALSE, array( 'disabled' => $disabled ), NULL, NULL, NULL, 'spam_service_send_to_ips' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'spam_service_action_1', \IPS\Settings::i()->spam_service_action_1, FALSE, array( 'disabled' => $disabled, 'options' => $actions ), NULL, NULL, NULL, 'spam_service_action_1' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'spam_service_action_2', \IPS\Settings::i()->spam_service_action_2, FALSE, array( 'disabled' => $disabled, 'options' => $actions ), NULL, NULL, NULL, 'spam_service_action_2' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'spam_service_action_3', \IPS\Settings::i()->spam_service_action_3, FALSE, array( 'disabled' => $disabled, 'options' => $actions ), NULL, NULL, NULL, 'spam_service_action_3' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'spam_service_action_4', \IPS\Settings::i()->spam_service_action_4, FALSE, array( 'disabled' => $disabled, 'options' => $actions ), NULL, NULL, NULL, 'spam_service_action_4' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'spam_service_action_0', \IPS\Settings::i()->spam_service_action_0, FALSE, array( 'disabled' => $disabled, 'options' => $actions ), NULL, NULL, NULL, 'spam_service_action_0' ) );
		if ( $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_SpamMonitoring' => TRUE ) );
		}

		return $form;
	}

	/**
	 * Show question and answer challenge settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageQanda()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'qanda_manage' );

		/* Create the table */
		$table					= new \IPS\Helpers\Table\Db( 'core_question_and_answer', \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&tab=qanda' ) );
		$table->include			= array( 'qa_question' );
		$table->joins			= array(
										array( 'select' => 'w.word_custom', 'from' => array( 'core_sys_lang_words', 'w' ), 'where' => "w.word_key=CONCAT( 'core_question_and_answer_', core_question_and_answer.qa_id ) AND w.lang_id=" . \IPS\Member::loggedIn()->language()->id )
									);
		$table->parsers			= array(
										'qa_question'		=> function( $val, $row )
										{
											return ( $row['word_custom'] ? $row['word_custom'] : $row['qa_question'] );
										}
									);
		$table->mainColumn		= 'qa_question';
		$table->sortBy			= $table->sortBy ?: 'qa_question';
		$table->quickSearch		= array( 'word_custom', 'qa_question' );
		$table->sortDirection	= $table->sortDirection ?: 'asc';
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'qanda_add' ) )
		{
			$table->rootButtons	= array(
				'add'	=> array(
					'icon'		=> 'plus',
					'title'		=> 'qanda_add_question',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=question' ),
				)
			);
		}

		$table->rowButtons		= function( $row )
		{
			$return	= array();
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'qanda_edit' ) )
			{
				$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=question&id=' ) . $row['qa_id'],
				);
			}
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'membersettings', 'qanda_delete' ) )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=delete&id=' ) . $row['qa_id'],
					'data'		=> array( 'delete' => '' ),
				);
			}
			
			return $return;
		};

		return (string) $table;
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function question()
	{
		/* Init */
		$id			= 0;
		$question	= array();

		/* Start the form */
		$form	= new \IPS\Helpers\Form;

		/* Load question */
		try
		{
			$id	= intval( \IPS\Request::i()->id );
			$form->hiddenValues['id'] = $id;
			$question	= \IPS\Db::i()->select( '*', 'core_question_and_answer', array( 'qa_id=?', $id ) )->first();

			\IPS\Dispatcher::i()->checkAcpPermission( 'qanda_edit' );
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'qanda_add' );
		}

		$form->add( new \IPS\Helpers\Form\Translatable( 'qa_question', NULL, TRUE, array( 'app' => 'core', 'key' => ( $id ? "core_question_and_answer_{$id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'qa_answers', $id ? json_decode( $question['qa_answers'], TRUE ) : array(), TRUE ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$save = array(
				'qa_answers'	=> json_encode( $values['qa_answers'] ),
			);
			
			if ( $id )
			{
				\IPS\Db::i()->update( 'core_question_and_answer', $save, array( 'qa_id=?', $question['qa_id'] ) );

				\IPS\Session::i()->log( 'acplogs__question_edited' );
			}
			else
			{
				$id	= \IPS\Db::i()->insert( 'core_question_and_answer', $save );
				\IPS\Session::i()->log( 'acplogs__question_added' );
			}
				
			\IPS\Lang::saveCustom( 'core', "core_question_and_answer_{$id}", $values['qa_question'] );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&tab=qanda' ), 'saved' );
		}

		/* Display */
		\IPS\Output::i()->title	 		= \IPS\Member::loggedIn()->language()->addToStack('qanda_settings');
		\IPS\Output::i()->breadcrumb[]	= array( NULL, \IPS\Output::i()->title );
		\IPS\Output::i()->output 		= \IPS\Theme::i()->getTemplate( 'global' )->block( \IPS\Output::i()->title, $form );
	}

	/**
	 * Delete
	 *
	 * @return void
	 */
	protected function delete()
	{
		$id = intval( \IPS\Request::i()->id ); 
		\IPS\Dispatcher::i()->checkAcpPermission( 'qanda_delete' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		\IPS\Db::i()->delete( 'core_question_and_answer', array( 'qa_id=?', $id ) );
		\IPS\Session::i()->log( 'acplogs__question_deleted' );
		
		\IPS\Lang::deleteCustom( 'core', "core_question_and_answer_{$id}" );

		/* And redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=membersettings&controller=spam&tab=qanda" ) );
	}
	
	/**
	 * Spam Service Log
	 *
	 * @return	void
	 */
	protected function serviceLogs()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'spam_service_log' );
		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_spam_service_log', \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=serviceLogs' ) );
	
		$table->langPrefix = 'spamlogs_';
	
		/* Columns we need */
		$table->include = array( 'log_date', 'log_code', 'email_address', 'ip_address' );
	
		$table->sortBy	= $table->sortBy ?: 'log_date';
		$table->sortDirection	= $table->sortDirection ?: 'DESC';
	
		/* Search */
		$table->advancedSearch = array(
				'email_address'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
				'ip_address'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
				'log_code'			=> \IPS\Helpers\Table\SEARCH_NUMERIC,
		);

		$table->quickSearch = 'email_address';
	
		/* Custom parsers */
		$table->parsers = array(
				'log_date'				=> function( $val, $row )
				{
					return \IPS\DateTime::ts( $val )->localeDate();
				},
		);
	
		/* Add a button for settings */
		\IPS\Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
						'title'		=> 'prunesettings',
						'icon'		=> 'cog',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=serviceLogSettings' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('prunesettings') )
				),
		);
	
		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('spamlogs');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'spamlogs', $table );
	}
	
	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function serviceLogSettings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'spam_service_log' );
		
		$form = new \IPS\Helpers\Form;
	
		$form->add( new \IPS\Helpers\Form\Number( 'prune_log_spam', \IPS\Settings::i()->prune_log_spam, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days'), 'prune_log_spam' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__spamlog_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=membersettings&controller=spam&do=serviceLogs' ), 'saved' );
		}
	
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('spamlogssettings');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate('global')->block( 'spamlogssettings', $form, FALSE );
	}
}