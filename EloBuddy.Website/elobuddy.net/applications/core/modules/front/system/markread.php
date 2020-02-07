<?php
/**
 * @brief		Mark site as read
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 May 2014
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
 * Mark site as read
 */
class _markread extends \IPS\Dispatcher\Controller
{
	/**
	 * Mark site as read
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Session::i()->csrfCheck();
		
		if ( \IPS\Member::loggedIn()->member_id )
		{
			\IPS\Member::loggedIn()->markAllAsRead();
		}
		
		/* Don't redirect to an external domain unless explicitly requested, and don't redirect back to ACP */
		\IPS\Output::i()->redirect( ( !empty( $_SERVER['HTTP_REFERER'] ) ) ? \IPS\Http\Url::external( $_SERVER['HTTP_REFERER'] ) : \IPS\Http\Url::internal( '' ), 'core_site_marked_as_read' );
	}
}