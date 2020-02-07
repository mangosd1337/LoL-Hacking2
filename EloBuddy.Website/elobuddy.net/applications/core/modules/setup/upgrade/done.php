<?php
/**
 * @brief		Upgrader: Finished Screen
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
 * Upgrader: Finished Screen
 */
class _done extends \IPS\Dispatcher\Controller
{
	/**
	 * Finished
	 *
	 * @return	void
	 */
	public function manage()
	{
		\IPS\Output::clearJsFiles();
		
		if ( file_exists( \IPS\ROOT_PATH . '/uploads/logs/upgrader_data.cgi' ) )
		{
			@unlink( \IPS\ROOT_PATH . '/uploads/logs/upgrader_data.cgi' );
		}
		
		/* Reset theme maps to make sure bad data hasn't been cached by visits mid-setup */
		foreach( \IPS\Theme::themes() as $id => $set )
		{
			/* Update mappings */
			$set->css_map = array();
			$set->save();
		}

		/* Delete some variables we stored in our session */
		unset( $_SESSION['apps'] );

		if( isset( $_SESSION['upgrade_options'] ) )
		{
			unset( $_SESSION['upgrade_options'] );
		}
		
		if( isset( $_SESSION['sqlFinished'] ) )
		{
			unset( $_SESSION['sqlFinished'] );
		}

		unset( $_SESSION['key'] );
		
		/* And show the complete page - the template handles this step special already so we don't have to output anything */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('done');
	}
}