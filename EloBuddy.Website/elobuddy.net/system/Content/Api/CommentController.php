<?php
/**
 * @brief		Base API endpoint for Content Comments
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		8 Dec 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Base API endpoint for Content Comments
 */
class _CommentController extends \IPS\Api\Controller
{
	/**
	 * List
	 *
	 * @param	array	$where	Extra WHERE clause
	 * @return	\IPS\Api\PaginatedResponse
	 */
	protected function _list( $where = array(), $containerParam = 'categories' )
	{
		$class = $this->class;
		$itemClass = $class::$itemClass;
		
		/* Containers */
		if ( isset( \IPS\Request::i()->$containerParam ) )
		{
			$where[] = array( \IPS\Db::i()->in( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'], array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->$containerParam ) ) ) ) );
		}
		
		/* Authors */
		if ( isset( \IPS\Request::i()->authors ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['author'], array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->authors ) ) ) ) );
		}
		
		/* Pinned? */
		if ( isset( \IPS\Request::i()->pinned ) AND in_array( 'IPS\Content\Pinnable', class_implements( $itemClass ) ) )
		{
			if ( \IPS\Request::i()->pinned )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['pinned'] . "=1" );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['pinned'] . "=0" );
			}
		}
		
		/* Featured? */
		if ( isset( \IPS\Request::i()->featured ) AND in_array( 'IPS\Content\Featurable', class_implements( $itemClass ) ) )
		{
			if ( \IPS\Request::i()->featured )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['featured'] . "=1" );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['featured'] . "=0" );
			}
		}
		
		/* Locked? */
		if ( isset( \IPS\Request::i()->locked ) AND in_array( 'IPS\Content\Lockable', class_implements( $itemClass ) ) )
		{
			if ( isset( static::$databaseColumnMap['locked'] ) )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['locked'] . '=?', intval( \IPS\Request::i()->locked ) );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['state'] . '=?', \IPS\Request::i()->locked ? 'closed' : 'open' );
			}
		}
		
		/* Hidden */
		if ( isset( \IPS\Request::i()->hidden ) AND in_array( 'IPS\Content\Hideable', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->hidden )
			{
				if ( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['hidden'] . '<>0' );
				}
				else
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['approved'] . '<>1' );
				}
			}
			else
			{
				if ( isset( $class::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['hidden'] . '=0' );
				}
				else
				{
					$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['approved'] . '=1' );
				}
			}
		}
		
		/* Has poll? */
		if ( isset( \IPS\Request::i()->hasPoll ) AND in_array( 'IPS\Content\Polls', class_implements( $itemClass ) ) )
		{
			if ( \IPS\Request::i()->hasPoll )
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['poll'] . ">0" );
			}
			else
			{
				$where[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['poll'] . "=0" );
			}
		}
		
		/* Sort */
		if ( isset( \IPS\Request::i()->sortBy ) and in_array( \IPS\Request::i()->sortBy, array( 'date' ) ) )
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnMap[ \IPS\Request::i()->sortBy ];
		}
		if ( isset( \IPS\Request::i()->sortBy ) and in_array( \IPS\Request::i()->sortBy, array( 'title' ) ) )
		{
			$sortBy = $itemClass::$databasePrefix . $itemClass::$databaseColumnMap[ \IPS\Request::i()->sortBy ];
		}
		else
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnId;
		}
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		/* Return */
		return new \IPS\Api\PaginatedResponse(
			200,
			\IPS\Db::i()->select( '*', $class::$databaseTable, $where, "{$sortBy} {$sortDir}", NULL, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )->join( $itemClass::$databaseTable, $itemClass::$databasePrefix . $itemClass::$databaseColumnId . '=' . $class::$databasePrefix . $class::$databaseColumnMap['item'] ),
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			$class
		);
	}
	
	/**
	 * Create
	 *
	 * @param	\IPS\Content\Item	$item			Content Item
	 * @param	\IPS\Member			$author			Author
	 * @param	string				$contentParam	The parameter that contains the content body
	 * @return	\IPS\Api\Response
	 */
	protected function _create( \IPS\Content\Item $item, \IPS\Member $author, $contentParam='content' )
	{
		/* Work out the date */
		$date = \IPS\Request::i()->date ? new \IPS\DateTime( \IPS\Request::i()->date ) : \IPS\DateTime::create();
		
		/* Create post */
		$class = $this->class;
		if ( in_array( 'IPS\Content\Review', class_parents( $class ) ) )
		{
			$comment = $class::create( $item, \IPS\Request::i()->$contentParam, FALSE, intval( \IPS\Request::i()->rating ), $author->member_id ? NULL : $author->name, $author, $date, \IPS\Request::i()->ip_address ?: \IPS\Request::i()->ipAddress(), isset( \IPS\Request::i()->hidden ) ? \IPS\Request::i()->hidden : NULL );
		}
		else
		{
			$comment = $class::create( $item, \IPS\Request::i()->$contentParam, FALSE, $author->member_id ? NULL : $author->name, NULL, $author, $date, \IPS\Request::i()->ip_address ?: \IPS\Request::i()->ipAddress(), isset( \IPS\Request::i()->hidden ) ? \IPS\Request::i()->hidden : NULL );
		}
		
		/* Index */
		if ( $item instanceof \IPS\Content\Searchable )
		{
			if ( $item::$firstCommentRequired and !$comment->isFirst() )
			{
				$firstCommentField = $item::$databaseColumnMap['first_comment_id'];
				if ( in_array( 'IPS\Content\Searchable', class_implements( $class ) ) )
				{					
					\IPS\Content\Search\Index::i()->index( $class::load( $item->$firstCommentField ) );
				}
			}
			else
			{
				\IPS\Content\Search\Index::i()->index( $item );
			}
		}
		if ( $comment instanceof \IPS\Content\Searchable )
		{
			\IPS\Content\Search\Index::i()->index( $comment );
		}
		
		/* Return */
		return new \IPS\Api\Response( 201, $comment->apiOutput() );
	}
	
	/**
	 * Edit
	 *
	 * @param	\IPS\Content\Comment		$comment		The comment
	 * @param	string						$contentParam	The parameter that contains the content body
	 * @throws	InvalidArgumentException	Invalid author
	 * @return	\IPS\Api\Response
	 */
	protected function _edit( $comment, $contentParam='content' )
	{
		/* Hidden */
		if ( isset( \IPS\Request::i()->hidden ) )
		{			
			if ( \IPS\Request::i()->hidden )
			{
				$comment->hide( FALSE );
			}
			else
			{
				$comment->unhide( FALSE );
			}
		}
		
		/* Change author */
		if ( isset( \IPS\Request::i()->author ) )
		{
			$authorIdColumn = $comment::$databaseColumnMap['author'];
			$authorNameColumn = $comment::$databaseColumnMap['author_name'];
			
			/* Just renaming the guest */
			if ( !$comment->$authorIdColumn and ( !isset( \IPS\Request::i()->author ) or !\IPS\Request::i()->author ) and isset( \IPS\Request::i()->author_name ) )
			{
				$comment->$authorNameColumn = \IPS\Request::i()->author_name;
			}
			
			/* Actually changing the author */
			else
			{
				try
				{
					$member = \IPS\Member::load( \IPS\Request::i()->author );
					if ( !$member->member_id )
					{
						throw new \InvalidArgumentException;
					}
					
					$comment->changeAuthor( $member );
				}
				catch ( \OutOfRangeException $e )
				{
					throw new \InvalidArgumentException;
				}
			}
		}
		
		/* Post value */
		if ( isset( \IPS\Request::i()->$contentParam ) )
		{
			$contentColumn = $comment::$databaseColumnMap['content'];
			$comment->$contentColumn = \IPS\Request::i()->$contentParam;
		}
		
		/* Rating */
		$ratingChanged = FALSE;
		if ( isset( \IPS\Request::i()->rating ) )
		{
			$ratingChanged = TRUE;
			$ratingColumn = $comment::$databaseColumnMap['rating'];
			$comment->$ratingColumn = intval( \IPS\Request::i()->rating );
		}
		
		/* Save and return */
		$comment->save();
		
		/* Recalculate ratings */
		if ( $ratingChanged )
		{
			$itemClass = $comment::$itemClass;
			$ratingField = $itemClass::$databaseColumnMap['rating'];
			
			$comment->item()->$ratingField = $comment->item()->averageReviewRating() ?: 0;
			$comment->item()->save();
		}
		
		/* Return */
		return new \IPS\Api\Response( 200, $comment->apiOutput() );

	}
}