<?php
/**
 * @brief		Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		18 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\modules\admin\calendars;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Settings
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
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('settings');

		$form = new \IPS\Helpers\Form;

		$form->add( new \IPS\Helpers\Form\Radio( 'calendar_default_view', \IPS\Settings::i()->calendar_default_view, TRUE, array( 'options' => array( 'month' => 'cal_df_month', 'week' => 'cal_df_week', 'day' => 'cal_df_day', 'stream' => 'cal_df_stream' ) ) ) );

		$options	= array_combine( array_keys( \IPS\calendar\Date::$dateFormats ), array_map( function( $val ){ return "calendar_df_" . $val; }, array_keys( \IPS\calendar\Date::$dateFormats ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'calendar_date_format', \IPS\Settings::i()->calendar_date_format, TRUE, array( 'options' => $options, 'unlimited' => '-1', 'unlimitedLang' => "calendar_custom_df", 'unlimitedToggles' => array( 'calendar_date_format_custom' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'calendar_date_format_custom', \IPS\Settings::i()->calendar_date_format_custom, FALSE, array(), NULL, NULL, NULL, 'calendar_date_format_custom' ) );

		$form->add( new \IPS\Helpers\Form\YesNo( 'show_bday_calendar', \IPS\Settings::i()->show_bday_calendar ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipb_calendar_mon', \IPS\Settings::i()->ipb_calendar_mon ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'calendar_rss_feed', \IPS\Settings::i()->calendar_rss_feed, FALSE, array( 'togglesOn' => array( 'calendar_rss_feed_days' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'calendar_rss_feed_days', \IPS\Settings::i()->calendar_rss_feed_days, FALSE, array( 'unlimited' => -1 ), NULL, NULL, NULL, 'calendar_rss_feed_days' ) );

		if ( $values = $form->values() )
		{
			if( $values['calendar_date_format'] == -1 AND !$values['calendar_date_format_custom'] )
			{
				$form->error	= \IPS\Member::loggedIn()->language()->addToStack('calendar_no_date_format');
				\IPS\Output::i()->output = $form;
				return;
			}

			$form->saveAsSettings();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__calendar_settings' );
		}

		\IPS\Output::i()->output = $form;
	}
}