<?php
/**
 * @brief		Submit Event Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		8 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\modules\front\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Submit Event Controller
 */
class _submit extends \IPS\Dispatcher\Controller
{
	/**
	 * Submit Event
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'submit_event' );
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'add_cal_event_header' ) );
		
		$calendar = NULL;
		if ( isset( \IPS\Request::i()->calendar ) )
		{
			try
			{
				$calendar = \IPS\calendar\Calendar::loadAndCheckPerms( \IPS\Request::i()->calendar );
			}
			catch ( \OutOfRangeException $e ) { }
		}
		$form = \IPS\calendar\Event::create( $calendar );

		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_submit.js', 'calendar', 'front' ) );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'submit' )->submitPage( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'submit', 'calendar' ) ), 'submitForm' ) ) );

		if ( \IPS\calendar\Event::moderateNewItems( \IPS\Member::loggedIn() ) )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->modQueueMessage( \IPS\Member::loggedIn()->warnings( 5, NULL, 'mq' ), \IPS\Member::loggedIn()->mod_posts ) . \IPS\Output::i()->output;
		}
	}
}