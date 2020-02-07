<?php
/**
 * @brief		Calendar Events Reviews API
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
 * @brief	Calendar Events Reviews API
 */
class _reviews extends \IPS\Content\Api\CommentController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\calendar\Event\Review';
	
	/**
	 * GET /calendar/reviews
	 * Get list of reviews
	 *
	 * @apiparam	string	calendars		Comma-delimited list of calendar IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only topics started by those members are returned
	 * @apiparam	int		locked			If 1, only reviews from events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only reviews which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured		If 1, only reviews from  events which are featured are returned, if 0 only not featured
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\calendar\Event\Review>
	 */
	public function GETindex()
	{
		return $this->_list( array(), 'calendars' );
	}
	
	/**
	 * GET /calendar/reviews/{id}
	 * View information about a specific review
	 *
	 * @param		int		$id			ID Number
	 * @throws		2L298/1	INVALID_ID	The review ID does not exist
	 * @return		\IPS\calendar\Event\Review
	 */
	public function GETitem( $id )
	{
		try
		{
			return new \IPS\Api\Response( 200, \IPS\calendar\Event\Review::load( $id )->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L298/1', 404 );
		}
	}
	
	/**
	 * POST /calendar/reviews
	 * Create a review
	 *
	 * @reqapiparam	int			event				The ID number of the event the review is for
	 * @reqapiparam	int			author				The ID number of the member making the review (0 for guest)
	 * @apiparam	string		author_name			If author is 0, the guest name that should be used
	 * @reqapiparam	string		content				The review content as HTML (e.g. "<p>This is a review.</p>")
	 * @apiparam	datetime	date				The date/time that should be used for the review date. If not provided, will use the current date/time
	 * @apiparam	string		ip_address			The IP address that should be stored for the review. If not provided, will use the IP address from the API request
	 * @apiparam	int			hidden				0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @reqapiparam	int			rating				Star rating
	 * @throws		2L298/2		INVALID_ID	The forum ID does not exist
	 * @throws		1L298/3		NO_AUTHOR	The author ID does not exist
	 * @throws		1L298/4		NO_CONTENT	No content was supplied
	 * @throws		1L298/8		INVALID_RATING	The rating is not a valid number up to the maximum rating
	 * @return		\IPS\calendar\Event\Review
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
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L298/2', 403 );
		}
		
		/* Get author */
		if ( \IPS\Request::i()->author )
		{
			$author = \IPS\Member::load( \IPS\Request::i()->author );
			if ( !$author->member_id )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1L298/3', 404 );
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
			throw new \IPS\Api\Exception( 'NO_CONTENT', '1L298/4', 403 );
		}
		
		/* Check we have a rating */
		if ( !\IPS\Request::i()->rating or !in_array( (int) \IPS\Request::i()->rating, range( 1, \IPS\Settings::i()->reviews_rating_out_of ) ) )
		{
			throw new \IPS\Api\Exception( 'INVALID_RATING', '1L298/8', 403 );
		}
		
		/* Do it */
		return $this->_create( $event, $author );
	}
	
	/**
	 * POST /calendar/reviews/{id}
	 * Edit a review
	 *
	 * @param		int			$id				ID Number
	 * @apiparam	int			author			The ID number of the member making the review (0 for guest)
	 * @apiparam	string		author_name		If author is 0, the guest name that should be used
	 * @apiparam	string		content			The review content as HTML (e.g. "<p>This is a review.</p>")
	 * @apiparam	int			hidden			1/0 indicating if the topic should be hidden
	 * @apiparam	int			rating			Star rating
	 * @throws		2L298/5		INVALID_ID		The review ID does not exist
	 * @throws		1L298/6		NO_AUTHOR		The author ID does not exist
	 * @throws		1L298/9		INVALID_RATING	The rating is not a valid number up to the maximum rating
	 * @return		\IPS\calendar\Event\Review
	 */
	public function POSTitem( $id )
	{
		try
		{
			/* Load */
			$comment = \IPS\calendar\Event\Review::load( $id );
			
			/* Check */
			if ( isset( \IPS\Request::i()->rating ) and !in_array( (int) \IPS\Request::i()->rating, range( 1, \IPS\Settings::i()->reviews_rating_out_of ) ) )
			{
				throw new \IPS\Api\Exception( 'INVALID_RATING', '1L298/9', 403 );
			}						
			/* Do it */
			try
			{
				return $this->_edit( $comment );
			}
			catch ( \InvalidArgumentException $e )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1L298/6', 400 );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L298/5', 404 );
		}
	}
		
	/**
	 * DELETE /calendar/reviews/{id}
	 * Deletes a review
	 *
	 * @param		int			$id			ID Number
	 * @throws		2L298/7		INVALID_ID	The review ID does not exist
	 * @return		void
	 */
	public function DELETEitem( $id )
	{
		try
		{			
			\IPS\calendar\Event\Review::load( $id )->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2L298/7', 404 );
		}
	}
}