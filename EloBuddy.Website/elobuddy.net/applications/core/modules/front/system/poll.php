<?php
/**
 * @brief		Poll View Voters Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Jan 2014
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
 * Poll View Voters Controller
 */
class _poll extends \IPS\Dispatcher\Controller
{
	/**
	 * View log
	 *
	 * @return	void
	 */
	protected function voters()
	{
		try
		{
			$poll = \IPS\Poll::load( \IPS\Request::i()->id );
			if ( !$poll->canSeeVoters() )
			{
				\IPS\Output::i()->error( 'node_error', '2C174/2', 403, '' );
			}
			
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pollVoters( $poll->getVotes( \IPS\Request::i()->question, \IPS\Request::i()->option ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C174/1', 404, '' );
		}		
	}
}