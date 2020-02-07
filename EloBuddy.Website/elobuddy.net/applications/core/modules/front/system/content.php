<?php
/**
 * @brief		"Content" functions Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Apr 2013
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
 * "Content" functions Controller
 */
class _content extends \IPS\Dispatcher\Controller
{
	/**
	 * Find content
	 *
	 * @return	void
	 */
	protected function find()
	{
		if ( ! \IPS\Request::i()->content_class AND ! \IPS\Request::i()->content_id AND ! \IPS\Request::i()->content_commentid )
		{
			\IPS\Output::i()->error( 'node_error', '2S226/1', 404, '' );
		}
		
		$class = 'IPS\\' . implode( '\\', explode( '_', \IPS\Request::i()->content_class ) );

		if ( ! class_exists( $class ) or ! in_array( 'IPS\Content', class_parents( $class ) ) )
		{
			\IPS\Output::i()->error( 'node_error', '2S226/2', 404, '' );
		}
		
		try
		{
			$item = $class::load( \IPS\Request::i()->content_id );
			$commentClass = $class::$commentClass;
			
			$comment = $commentClass::load( \IPS\Request::i()->content_commentid );
		}
		catch( \OutOfRangeException $ex )
		{
			\IPS\Output::i()->error( 'node_error', '2S226/3', 404, '' );
		}
		
		/* Make sure we have permission to see this */
		if ( ( $comment->item()->tid == $item->tid ) AND $item->canView() AND $comment->canView() )
		{
			\IPS\Output::i()->redirect( $comment->url() );
		}
		else
		{
			\IPS\Output::i()->error( 'node_error', '2S226/4', 404, '' );
		}
	}
}