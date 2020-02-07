<?php
/**
 * @brief		Announcement
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		09 Oct 2013
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
 * Announcement
 */
class _announcement extends \IPS\Content\Controller
{
	/**
	 * [Content\Controller]	Class
	 */
	protected static $contentModel = 'IPS\core\Announcements\Announcement';
	
	/**
	 * View Announcement
	 *
	 * @return	void
	 */
	protected function manage()
	{
		parent::manage();
		
		/* Load announcement */
		try
		{
			$announcement = \IPS\core\Announcements\Announcement::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'announcement_missing', '2C199/1', 404, '' );
		}
		
		/* Set Session Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=core&module=system&controller=announcement&id=' . $announcement->id, NULL, 'announcement', $announcement->seo_title  ), array(), 'loc_viewing_announcement', array( $announcement->title => FALSE ) );
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( $announcement->title );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->announcement( $announcement );
	}
}