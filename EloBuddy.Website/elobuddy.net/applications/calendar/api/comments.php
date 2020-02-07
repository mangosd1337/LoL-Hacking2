<?php
/**
 * @brief		Calendar Events Comments API
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		8 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Calendar Events Comments API
 */
class _comments extends \IPS\Content\Api\CommentController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\calendar\Event\Comment';
	
	/**
	 * GET /calendar/comments
	 * Get list of comments
	 *
	 * @apiparam	string	calendars		Comma-delimited list of calendar IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only topics started by those members are returned
	 * @apiparam	int		locked			If 1, only comments from events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured		If 1, only comments from  events which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\calendar\Event\Comment>
	 */
	public function GETindex()
	{
		return $this->_list( array(), 'calendars' );
	}
	
	/**
	 * GET /calendar/comments/{id}
	 * View information about a specific comment
	 *
	 * @param		int		$id			ID Number
	 * @throws		2L297/1	INVALID_ID	The comment ID does not exist
	 * @return		\IPS\calendar\Event\Comment
	 */
	public function GETitem( $id )
	{
		try
		{
			return new \IPS\Api\Response( 200, \IPS\calendar\Event\Comment::load( $id )->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L297/1', 404 );
		}
	}
	
	/**
	 * POST /calendar/comments
	 * Create a comment
	 *
	 * @reqapiparam	int			event				The ID number of the event the comment is for
	 * @reqapiparam	int			author				The ID number of the member making the comment (0 for guest)
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		content				The comment content as HTML (e.g. "<p>This is a comment.</p>")
	 * @apiparam	datetime	date				The date/time that should be used for the comment date. If not provided, will use the current date/time
	 * @apiparam	string		ip_address			The IP address that should be stored for the comment. If not provided, will use the IP address from the API request
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @throws		2L297/3		INVALID_ID	The comment ID does not exist
	 * @throws		1L297/4		NO_AUTHOR	The author ID does not exist
	 * @throws		1L297/5		NO_CONTENT	No content was supplied
	 * @return		\IPS\calendar\Event\Comment
	 */
	public function POSTindex()
	{
		/* Get topic */
		try
		{
			$event = \IPS\calendar\Event::load( \IPS\Request::i()->event );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L297/3', 403 );
		}
		
		/* Get author */
		if ( \IPS\Request::i()->author )
		{
			$author = \IPS\Member::load( \IPS\Request::i()->author );
			if ( !$author->member_id )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1L297/4', 404 );
			}
		}
		else
		{
			$author = new \IPS\Member;
			$author->name = \IPS\Request::i()->author_name;
		}
		
		/* Check we have a post */
		if ( !\IPS\Request::i()->content )
		{
			throw new \IPS\Api\Exception( 'NO_CONTENT', '1L297/5', 403 );
		}
		
		/* Do it */
		return $this->_create( $event, $author );
	}
	
	/**
	 * POST /calendar/comments/{id}
	 * Edit a comment
	 *
	 * @param		int			$id				ID Number
	 * @apiparam	int			author			The ID number of the member making the comment (0 for guest)
	 * @apiparam	string		author_name		If author is 0, the guest name that should be used
	 * @apiparam	string		content			The comment content as HTML (e.g. "<p>This is a comment.</p>")
	 * @apiparam	int			hidden				1/0 indicating if the topic should be hidden
	 * @throws		2L297/6		INVALID_ID			The comment ID does not exist
	 * @throws		1L297/7		NO_AUTHOR			The author ID does not exist
	 * @return		\IPS\calendar\Event\Comment
	 */
	public function POSTitem( $id )
	{
		try
		{
			/* Load */
			$comment = \IPS\calendar\Event\Comment::load( $id );
						
			/* Do it */
			try
			{
				return $this->_edit( $comment );
			}
			catch ( \InvalidArgumentException $e )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1L297/7', 400 );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L297/6', 404 );
		}
	}
		
	/**
	 * DELETE /calendar/comments/{id}
	 * Deletes a comment
	 *
	 * @param		int			$id			ID Number
	 * @throws		2L297/2		INVALID_ID	The comment ID does not exist
	 * @return		void
	 */
	public function DELETEitem( $id )
	{
		try
		{			
			\IPS\calendar\Event\Comment::load( $id )->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L297/2', 404 );
		}
	}
}