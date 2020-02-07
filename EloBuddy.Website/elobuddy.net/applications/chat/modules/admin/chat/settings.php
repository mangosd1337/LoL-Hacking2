<?php
/**
 * @brief		settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		15 Mar 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\chat\modules\admin\chat;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		parent::execute();
	}

	/**
	 * Show the settings form
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new \IPS\Helpers\Form;

		$form->add( new \IPS\Helpers\Form\YesNo( 'ipchat_new_window', \IPS\Settings::i()->ipchat_new_window, TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipschat_enable_rules', \IPS\Settings::i()->ipschat_enable_rules, TRUE, array( 'togglesOn' => array( 'ipschat_rules_id' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'ipschat_rules_text', NULL, FALSE, array( 'app' => 'core', 'key' => 'ipschat_rules', 'editor' => array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'ipschat_rules', 'attachIds' => array( NULL, NULL, 'ipschat_rules' ) ) ), NULL, NULL, NULL, 'ipschat_rules_id' ) );

		$form->add( new \IPS\Helpers\Form\YesNo( 'ipchat_hide_usermessage', !\IPS\Settings::i()->ipchat_hide_usermessage, TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipchat_no_buffer', \IPS\Settings::i()->ipchat_no_buffer, TRUE ) );

		$form->add( new \IPS\Helpers\Form\Number( 'ipchat_inactive_minutes', \IPS\Settings::i()->ipchat_inactive_minutes, TRUE, array(), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('minutes') ) );
		$form->add( new \IPS\Helpers\Form\Select( 'ipchat_24hour', \IPS\Settings::i()->ipchat_24hour, TRUE, array( 'options' => array( '12' => 'chat_12hour', '24' => 'chat_24hour' ) ) ) );

		if( \IPS\Settings::i()->ipschat_online )
		{
			$times = explode( ',', \IPS\Settings::i()->ipschat_online );

			/* Do we need to adjust the time zone? */
			if( isset( $times[2] ) AND $times[2] != \IPS\Member::loggedIn()->timezone )
			{
				$start = new \IPS\DateTime( '2015-01-01 ' . $times[0], new \DateTimeZone( $times[2] ) );
				$start->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
				$times[0] = $start->format( 'H:i' );

				$end = new \IPS\DateTime( '2015-01-01 ' . $times[1], new \DateTimeZone( $times[2] ) );
				$end->setTimezone( new \DateTimeZone( \IPS\Member::loggedIn()->timezone ) );
				$times[1] = $end->format( 'H:i' );
			}
		}
		else
		{
			$times = array( 0 => '', 1 => '' );
		}

		$form->add( new \IPS\Helpers\Form\Custom( 'ipschat_online', $times, FALSE, array(
			'getHtml' => function( $element )
			{
				return \IPS\Theme::i()->getTemplate( 'settings' )->chatOnline( $element->value );
			},
			'formatValue' => function( $element )
			{
				$element->value[2] = \IPS\Member::loggedIn()->timezone;
				return $element->value;
			}
		), NULL, NULL, NULL, 'ipschat_online' ) );

		if ( $values = $form->values() )
		{
			\IPS\Lang::saveCustom( 'chat', "ipschat_rules", $values['ipschat_rules_text'] );
			unset( $values['ipschat_rules_text'] );
			$values['ipchat_hide_usermessage']	= !$values['ipchat_hide_usermessage'];
			$values['ipschat_online'] = implode( ',', $values['ipschat_online'] );
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__chat_settings' );
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__chat_chat_settings');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'menu__chat_chat_settings', $form );
	}
}