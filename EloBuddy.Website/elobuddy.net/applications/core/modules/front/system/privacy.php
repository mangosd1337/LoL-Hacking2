<?php
/**
 * @brief		Privacy Policy
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Jun 2013
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
 * Privacy Policy
 */
class _privacy extends \IPS\Dispatcher\Controller
{
	/**
	 * Privacy Policy
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( \IPS\Settings::i()->privacy_type == "none" )
		{
			\IPS\Output::i()->error( 'node_error', '', 404 ); /* @todo Error code */
		}

		/* Set Session Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=core&module=system&controller=privacy', NULL, 'privacy' ), array(), 'loc_viewing_privacy_policy' );
		
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('privacy') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('privacy');
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->privacy();
	}
}