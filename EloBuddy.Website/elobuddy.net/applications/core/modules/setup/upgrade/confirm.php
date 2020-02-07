<?php
/**
 * @brief		Upgrader: Confirm
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 May 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Confirm
 */
class _confirm extends \IPS\Dispatcher\Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* reset a few things */
		$_SESSION['lastJsonIndex'] = 0;
		$_SESSION['lastSqlError']  = NULL;
		$_SESSION['sqlFinished']   = array();
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('confirmpage');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->confirm();
	}
}