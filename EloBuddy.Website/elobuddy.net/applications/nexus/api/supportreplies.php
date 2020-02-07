<?php
/**
 * @brief		Support Replies API
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Support Replies API
 */
class _supportreplies extends \IPS\Content\Api\CommentController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\nexus\Support\Reply';
	
	/**
	 * GET /nexus/supportreplies
	 * Get list of support replies
	 *
	 * @apiparam	int		staffReplies	If 1, only replies by staff will be included. If 0, only replies by non-staff.
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only replies by those members are returned
	 * @apiparam	string	departments		Comma-delimited list of department IDs
	 * @apiparam	string	statuses		Comma-delimited list of status IDs
	 * @apiparam	string	severities		Comma-delimited list of severity IDs
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\nexus\Support\Reply>
	 */
	public function GETindex()
	{
		/* Init */
		$where = array();
		
		/* Type */
		if ( isset( \IPS\Request::i()->staffReplies ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'reply_type', \IPS\Request::i()->staffReplies ? array( \IPS\nexus\Support\Reply::REPLY_STAFF, \IPS\nexus\Support\Reply::REPLY_HIDDEN ) : array( \IPS\nexus\Support\Reply::REPLY_MEMBER, \IPS\nexus\Support\Reply::REPLY_ALTCONTACT, \IPS\nexus\Support\Reply::REPLY_EMAIL ) ) );
		}
		else
		{
			$where[] = array( 'reply_type<>?', \IPS\nexus\Support\Reply::REPLY_PENDING );
		}
		
		/* Departments */
		if ( isset( \IPS\Request::i()->departments ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'r_department', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->departments ) ) ) ) );
		}
		
		/* Statuses */
		if ( isset( \IPS\Request::i()->statuses ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'r_status', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->statuses ) ) ) ) );
		}

		/* Severities */
		if ( isset( \IPS\Request::i()->severities ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'r_severity', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->statuses ) ) ) ) );
		}

		/* Return */
		return $this->_list( $where );
	}
	
	/**
	 * GET /nexus/supportreplies/{id}
	 * View information about a specific reply
	 *
	 * @param		int		$id			ID Number
	 * @throws		2X314/1	INVALID_ID	The reply ID does not exist
	 * @return		\IPS\nexus\Support\Reply
	 */
	public function GETitem( $id )
	{
		try
		{
			return new \IPS\Api\Response( 200, \IPS\nexus\Support\Reply::load( $id )->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X314/1', 404 );
		}
	}
	
	/**
	 * POST /nexus/supportreplies
	 * Create a reply
	 *
	 * @reqapiparam	int			request				The ID number of the request the reply is for
	 * @reqapiparam	int			author				The ID number of the member making the post (0 for guest)
	 * @reqapiparam	string		message				The reply content as HTML (e.g. "<p>This is a post.</p>")
	 * @apiparam	datetime	date				The date/time that should be used for the topic/post post date. If not provided, will use the current date/time
	 * @apiparam	string		ip_address			The IP address that should be stored for the topic/post. If not provided, will use the IP address from the API request
	 * @throws		2X314/2		NO_REQUEST		The forum ID does not exist
	 * @throws		1X314/3		NO_AUTHOR		The author ID does not exist
	 * @throws		1X314/4		NO_MESSAGE		No message was supplied
	 * @return		\IPS\nexus\Support\Reply
	 */
	public function POSTindex()
	{
		/* Get request */
		try
		{
			$request = \IPS\forums\Topic::load( \IPS\Request::i()->request );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'NO_REQUEST', '2X314/2', 403 );
		}
		
		/* Get author */
		if ( \IPS\Request::i()->author )
		{
			$author = \IPS\Member::load( \IPS\Request::i()->author );
			if ( !$author->member_id )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1X314/3', 403 );
			}
		}
		else
		{
			$author = new \IPS\Member;
			$author->name = \IPS\Request::i()->author_name;
		}
		
		/* Check we have a post */
		if ( !\IPS\Request::i()->message )
		{
			throw new \IPS\Api\Exception( 'NO_MESSAGE', '1X314/4', 403 );
		}
		
		/* Do it */
		return $this->_create( $request, $author, 'message' );
	}
	
	/**
	 * POST /nexus/supportreplies/{id}
	 * Edit a reply
	 *
	 * @param		int			$id			ID Number
	 * @apiparam	int			author		The ID number of the member making the post (0 for guest)
	 * @apiparam	string		message		The post content as HTML (e.g. "<p>This is a post.</p>")
	 * @throws		2X314/5		INVALID_ID	The post ID does not exist
	 * @throws		1X314/6		NO_AUTHOR	The author ID does not exist
	 * @return		\IPS\forums\Topic\Post
	 */
	public function POSTitem( $id )
	{
		try
		{
			/* Load */
			$post = \IPS\nexus\Support\Reply::load( $id );
			
			/* Do it */
			try
			{
				return $this->_edit( $post, 'message' );
			}
			catch ( \InvalidArgumentException $e )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1X314/6', 400 );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X314/5', 404 );
		}
	}
		
	/**
	 * DELETE /nexus/supportreplies/{id}
	 * Deletes a reply
	 *
	 * @param		int			$id							ID Number
	 * @throws		2X314/7		INVALID_ID					The post ID does not exist
	 * @throws		1X314/8		CANNOT_DELETE_FIRST_POST	You cannot delete the first reply to a request. Delete the request itself instead.
	 * @return		void
	 */
	public function DELETEitem( $id )
	{
		try
		{
			$reply = \IPS\nexus\Support\Reply::load( $id );
			
			if ( $reply->isFirst() )
			{
				throw new \IPS\Api\Exception( 'CANNOT_DELETE_FIRST_POST', '1X314/8', 403 );
			}
			
			$reply->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X314/7', 404 );
		}
	}
}