<?php
/**
 * @brief		Support Requests API
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		4 Dec 2015
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
 * @brief	Support Requests API
 */
class _supportrequests extends \IPS\Content\Api\ItemController
{
	/**
	 * Class
	 */
	protected $class = 'IPS\nexus\Support\Request';
	
	/**
	 * GET /nexus/supportrequests
	 * Get list of support requests
	 *
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only support requests belonging to those members (or emails specified by email param) are returned
	 * @apiparam	string	email			Comma-delimited list of email addresses - if provided, only support requests created by those emails (or members specified by authors param) are returned
	 * @apiparam	string	departments		Comma-delimited list of department IDs
	 * @apiparam	string	statuses		Comma-delimited list of status IDs
	 * @apiparam	string	severities		Comma-delimited list of severity IDs
	 * @apiparam	string	purchases		Comma-delimited list of purchase IDs - if provided, only support requests associated with one of the provided purchase IDs are returned
	 * @apiparam	string	staff			Comma-delimited list of member IDs - if provided, only support requests assigned to the staff members with one of the provided IDs are returned
	 * @apiparam	int		hidden			If 1, only replies which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortBy			What to sort by. Can be 'date', 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @return		\IPS\Api\PaginatedResponse<IPS\nexus\Support\Request>
	 */
	public function GETindex()
	{
		/* Init */
		$where = array();
		
		/* Authors */
		if ( isset( \IPS\Request::i()->authors ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'r_member', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->customers ) ) ) ) );
		}
		if ( isset( \IPS\Request::i()->authors ) and isset( \IPS\Request::i()->emails ) )
		{
			$where[] = array( '( ' . \IPS\Db::i()->in( 'r_member', array_filter( explode( ',', \IPS\Request::i()->authors ) ) ) . ' OR ' . \IPS\Db::i()->in( 'r_email', array_filter( explode( ',', \IPS\Request::i()->emails ) ) ) . ' )' );
		}
		elseif ( isset( \IPS\Request::i()->authors ) )
		{
			$where[] = array( '( ' . \IPS\Db::i()->in( 'r_member', array_filter( explode( ',', \IPS\Request::i()->authors ) ) ) . ' OR r_email<>? )', '' );
		}
		elseif ( isset( \IPS\Request::i()->emails ) )
		{
			$where[] = array( '( r_member>0 OR ' . \IPS\Db::i()->findInSet( 'r_email', array_filter( explode( ',', \IPS\Request::i()->emails ) ) ) . ' )' );
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

		/* Purchases */
		if ( isset( \IPS\Request::i()->purchases ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'r_purchase', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->purchases ) ) ) ) );
		}
		
		/* Staff */
		if ( isset( \IPS\Request::i()->staff ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'r_staff', array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->staff ) ) ) ) );
		}
		
		/* Return */
		return $this->_list( $where );
	}
		
	/**
	 * GET /nexus/supportrequests/{id}
	 * Get information about and replies to a specific support request
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only posts which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @throws		2X313/1	INVALID_ID	The topic ID does not exist
	 * @return		\IPS\nexus\Support\Request
	 */
	public function GETitem( $id )
	{
		try
		{
			return new \IPS\Api\Response( 200, \IPS\nexus\Support\Request::load( $id )->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X313/1', 404 );
		}
	}
	
	/**
	 * GET /nexus/supportrequests/{id}/replies
	 * Get replies to a specific support request
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only replies which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @throws		2X313/2	INVALID_ID	The topic ID does not exist
	 * @return		\IPS\Api\PaginatedResponse<IPS\nexus\Support\Reply>
	 */
	public function GETitem_replies( $id )
	{
		try
		{
			return $this->_comments( $id, 'IPS\nexus\Support\Reply', array( array( 'reply_type<>?', \IPS\nexus\Support\Reply::REPLY_PENDING ) ) );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X313/2', 404 );
		}
	}
	
	
	/**
	 * Create or update topic
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @return	\IPS\Content\Item
	 */
	protected function _createOrUpdate( \IPS\Content\Item $item )
	{
		/* Department, statuc, etc */
		foreach ( array( 'department' => 'IPS\nexus\Support\Department', 'status' => 'IPS\nexus\Support\Status', 'severity' => 'IPS\nexus\Support\Severity', 'purchase' => 'IPS\nexus\Purchase', 'staff' => 'IPS\Member'  ) as $key => $class )
		{
			if ( isset( \IPS\Request::i()->$key ) )
			{
				try
				{
					$object = $class::load( \IPS\Request::i()->$key );
					$item->$key = $object;
				}
				catch ( \OutOfRangeException $e ) {}
			}
		}
				
		/* Pass up */
		return parent::_createOrUpdate( $item );
	}
		
	/**
	 * POST /nexus/supportrequests
	 * Create a support request
	 *
	 * @reqapiparam	string		title				The support request title
	 * @reqapiparam	int			department			Department ID number
	 * @reqapiparam	int			account				The ID number of the member creating the support request - not required if email is provided
	 * @reqapiparam	string		email				The email address creating the support request - not required if account is provided
	 * @reqapiparam	string		message				The content as HTML (e.g. "<p>This is a support request.</p>")
	 * @apiparam	int			status				Status ID number. If not provided, will use the default.
	 * @apiparam	int			severity			Severity ID number. If not provided, will use the default.
	 * @apiparam	int			purchase			Associated purchase ID number.
	 * @apiparam	int			staff				Assigned staff ID number.
	 * @apiparam	datetime	date				The date/time that should be used for the support request date. If not provided, will use the current date/time
	 * @throws		1X313/3		NO_DEPARTMENT		The forum ID does not exist
	 * @throws		1X313/4		NO_AUTHOR			The author ID does not exist
	 * @throws		1X313/5		NO_TITLE			No title was supplied
	 * @throws		1X313/4		NO_POST				No post was supplied
	 * @return		\IPS\forums\Topic
	 */
	public function POSTindex()
	{
		/* Get department */
		try
		{
			$department = \IPS\nexus\Support\Department::load( \IPS\Request::i()->department );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'NO_DEPARTMENT', '1X313/3', 400 );
		}
		
		/* Get author */
		if ( \IPS\Request::i()->account )
		{
			$author = \IPS\Member::load( \IPS\Request::i()->account );
			if ( !$author->member_id )
			{
				throw new \IPS\Api\Exception( 'NO_AUTHOR', '1X313/4', 400 );
			}
		}
		else
		{
			$author = new \IPS\Member;
		}
		
		/* Check we have a title and a post */
		if ( !\IPS\Request::i()->title )
		{
			throw new \IPS\Api\Exception( 'NO_TITLE', '1X313/5', 400 );
		}
		if ( !\IPS\Request::i()->message )
		{
			throw new \IPS\Api\Exception( 'NO_POST', '1X313/4', 400 );
		}
		
		/* Create */
		$item = $this->_create( NULL, $author, 'message' );
		
		/* Defaults */
		if ( !$item->_data['status'] )
		{
			$item->status = \IPS\nexus\Support\Status::load( TRUE, 'status_default_member' );
		}
		if ( !$item->_data['severity'] )
		{
			$item->severity = \IPS\nexus\Support\Severity::load( TRUE, 'sev_default' );
		}
		
		/* Email */
		if ( isset( \IPS\Request::i()->email ) )
		{
			$item->email = \IPS\Request::i()->email;
		}  
		
		/* Do it */
		return new \IPS\Api\Response( 201, $item->apiOutput() );
	}
	
	/**
	 * POST /nexus/supportrequests/{id}
	 * Edit a support request
	 *
	 * @apiparam	string		title				The support request title
	 * @apiparam	int			department			Department ID number
	 * @apiparam	int			status				Status ID number. If not provided, will use the default.
	 * @apiparam	int			severity			Severity ID number. If not provided, will use the default.
	 * @apiparam	int			purchase			Associated purchase ID number.
	 * @apiparam	int			staff				Assigned staff ID number.
	 * @throws		2X313/7		INVALID_ID	The topic ID does not exist
	 * @return		\IPS\nexus\Support\Request
	 */
	public function POSTitem( $id )
	{
		try
		{
			$request = \IPS\nexus\Support\Request::load( $id );
						
			/* Process */
			$this->_createOrUpdate( $request );
			
			/* Save and return */
			$request->save();
			return new \IPS\Api\Response( 200, $request->apiOutput() );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X313/7', 404 );
		}
	}
	
	/**
	 * DELETE /nexus/supportrequests/{id}
	 * Delete a support request
	 *
	 * @param		int		$id			ID Number
	 * @throws		1F294/5	INVALID_ID	The topic ID does not exist
	 * @return		void
	 */
	public function DELETEitem( $id )
	{
		try
		{
			\IPS\nexus\Support\Request::load( $id )->delete();
			
			return new \IPS\Api\Response( 200, NULL );
		}
		catch ( \OutOfRangeException $e )
		{
			throw new \IPS\Api\Exception( 'INVALID_ID', '2X313/8', 404 );
		}
	}
}