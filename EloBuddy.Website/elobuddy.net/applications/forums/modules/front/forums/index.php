<?php
/**
 * @brief		index
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		28 Jul 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\modules\front\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * index
 */
class _index extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_browse.js', 'gallery' ) );
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_forum.js', 'forums' ) );
		
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Load into memory */
		\IPS\forums\Forum::loadIntoMemory();
						
		/* Is there only one forum? */
		if ( $theOnlyForum = \IPS\forums\Forum::theOnlyForum() )
		{
			$controller = new \IPS\forums\modules\front\forums\forums( $this->url );
			return $controller->_forum( $theOnlyForum );
		}
		
		/* Prepare output */
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'global_core.js', 'core', 'global' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'forums' );
		\IPS\Output::i()->linkTags['canonical'] = (string) \IPS\Http\Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' );
		\IPS\Output::i()->metaTags['og:title'] = \IPS\Settings::i()->board_name;
		\IPS\Output::i()->metaTags['og:type'] = 'website';
		\IPS\Output::i()->metaTags['og:url'] = (string) \IPS\Http\Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' );
		
		/* Set Online Location */
		$permissions = \IPS\Dispatcher::i()->module->permissions();
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=forums&module=forums&controller=index', 'front', 'forums' ), explode( ',', $permissions['perm_view'] ), 'loc_forums_index' );
		
		/* Display */
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'index' )->index();
	}

}