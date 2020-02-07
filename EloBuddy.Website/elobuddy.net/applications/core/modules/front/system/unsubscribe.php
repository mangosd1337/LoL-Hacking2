<?php
/**
 * @brief		Allow users to unsubscribe from site updates
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Unsubscribe
 */
class _unsubscribe extends \IPS\Dispatcher\Controller
{
	/**
	 * Unsubscribe the user
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Get the member being requested */
		if( empty( \IPS\Request::i()->email ) )
		{
			\IPS\Output::i()->error( 'no_user_to_unsubscribe', '2S127/3', 404, '' );
		}

		$member	= \IPS\Member::load( \IPS\Request::i()->email, 'email' );

		if( !$member->member_id )
		{
			\IPS\Output::i()->error( 'no_user_to_unsubscribe', '2S127/2', 404, '' );
		}

		/* Verify the key is correct */
		if ( \IPS\Login::compareHashes( md5( $member->email . ':' . $member->members_pass_hash ), (string) \IPS\Request::i()->key ) )
		{
			/* Set the member not to receive future emails */
			$member->allow_admin_mails	= 0;
			$member->save();

			/* And then show them a confirmation screen */
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('unsubscribed');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->unsubscribed();
		}
		else
		{
			/* Key did not match */
			\IPS\Output::i()->error( 'no_user_to_unsubscribe', '3S127/4', 403, '' );
		}
	}
}