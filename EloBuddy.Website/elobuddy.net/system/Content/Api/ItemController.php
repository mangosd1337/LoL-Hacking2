<?php
/**
 * @brief		Base API endpoint for Content Items
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
 * @brief	Base API endpoint for Content Items
 */
class _ItemController extends \IPS\Api\Controller
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
		
		/* Containers */
		if ( isset( \IPS\Request::i()->$containerParam ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['container'], array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->$containerParam ) ) ) ) );
		}
		
		/* Authors */
		if ( isset( \IPS\Request::i()->authors ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['author'], array_map( 'intval', array_filter( explode( ',', \IPS\Request::i()->authors ) ) ) ) );
		}
		
		/* Pinned? */
		if ( isset( \IPS\Request::i()->pinned ) AND in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->pinned )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . "=1" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . "=0" );
			}
		}
		
		/* Featured? */
		if ( isset( \IPS\Request::i()->featured ) AND in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->featured )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['featured'] . "=1" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['featured'] . "=0" );
			}
		}
		
		/* Locked? */
		if ( isset( \IPS\Request::i()->locked ) AND in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			if ( isset( $class::$databaseColumnMap['locked'] ) )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', intval( \IPS\Request::i()->locked ) );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['state'] . '=?', \IPS\Request::i()->locked ? 'closed' : 'open' );
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
		if ( isset( \IPS\Request::i()->hasPoll ) AND in_array( 'IPS\Content\Polls', class_implements( $class ) ) )
		{
			if ( \IPS\Request::i()->hasPoll )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['poll'] . ">0" );
			}
			else
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['poll'] . "=0" );
			}
		}
		
		/* Sort */
		if ( isset( \IPS\Request::i()->sortBy ) and in_array( \IPS\Request::i()->sortBy, array( 'date', 'title' ) ) )
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnMap[ \IPS\Request::i()->sortBy ];
		}
		else
		{
			$sortBy = $class::$databasePrefix . $class::$databaseColumnId;
		}
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		/* Return */
		return new \IPS\Api\PaginatedResponse(
			200,
			\IPS\Db::i()->select( '*', $class::$databaseTable, $where, "{$sortBy} {$sortDir}", NULL, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS ),
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			$class
		);
	}
	
	/**
	 * View
	 *
	 * @param	int	$id	ID Number
	 * @return	\IPS\Api\Response
	 */
	protected function _view( $id )
	{
		$class = $this->class;
		return new \IPS\Api\Response( 200, $class::load( $id )->apiOutput() );
	}
	
	/**
	 * Create or update item
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @return	\IPS\Content\Item
	 */
	protected function _createOrUpdate( \IPS\Content\Item $item )
	{
		/* Title */
		if ( isset( \IPS\Request::i()->title ) and isset( $item::$databaseColumnMap['title'] ) )
		{
			$titleColumn = $item::$databaseColumnMap['title'];
			$item->$titleColumn = \IPS\Request::i()->title;
		}
		
		/* Tags */
		if ( ( isset( \IPS\Request::i()->prefix ) or isset( \IPS\Request::i()->tags ) ) and in_array( 'IPS\Content\Tags', class_implements( get_class( $item ) ) ) )
		{
			$tags = isset( \IPS\Request::i()->tags ) ? array_filter( explode( ',', \IPS\Request::i()->tags ) ) : $item->tags();
			if ( isset( \IPS\Request::i()->prefix ) )
			{
				if ( \IPS\Request::i()->prefix )
				{
					$tags['prefix'] = \IPS\Request::i()->prefix;
				}
			}
			elseif ( $existingPrefix = $item->prefix() )
			{
				$tags['prefix'] = $existingPrefix;
			}

			/* we need to save the item before we set the tags because setTags requires that the item exists */
			$idColumn = $item::$databaseColumnId;
			if ( !$item->$idColumn )
			{
				$item->save();
			}

			$item->setTags( $tags );
		}
		
		/* Open/closed */
		if ( isset( \IPS\Request::i()->locked ) and in_array( 'IPS\Content\Lockable', class_implements( get_class( $item ) ) ) )
		{
			if ( isset( $item::$databaseColumnMap['locked'] ) )
			{
				$lockedColumn = $item::$databaseColumnMap['locked'];
				$item->$lockedColumn = intval( \IPS\Request::i()->locked );
			}
			else
			{
				$stateColumn = $item::$databaseColumnMap['status'];
				$item->$stateColumn = \IPS\Request::i()->locked ? 'closed' : 'open';
			}
		}
		
		/* Hidden */
		if ( isset( \IPS\Request::i()->hidden ) and in_array( 'IPS\Content\Hideable', class_implements( get_class( $item ) ) ) )
		{
			$idColumn = $item::$databaseColumnId;
			if ( \IPS\Request::i()->hidden )
			{
				if ( $item->$idColumn )
				{
					$item->hide( FALSE );
				}
				else
				{
					if ( isset( $item::$databaseColumnMap['hidden'] ) )
					{
						$hiddenColumn = $item::$databaseColumnMap['hidden'];
						$item->$hiddenColumn = \IPS\Request::i()->hidden;
					}
					else
					{
						$approvedColumn = $item::$databaseColumnMap['approved'];
						$item->$approvedColumn = ( \IPS\Request::i()->hidden == -1 ) ? -1 : 0;
					}
				}
			}
			else
			{
				if ( $item->$idColumn )
				{
					$item->unhide( FALSE );
				}
				else
				{
					if ( isset( $item::$databaseColumnMap['hidden'] ) )
					{
						$hiddenColumn = $item::$databaseColumnMap['hidden'];
						$item->$hiddenColumn = 0;
					}
					else
					{
						$approvedColumn = $item::$databaseColumnMap['approved'];
						$item->$approvedColumn = 1;
					}
				}
			}
		}
		
		/* Pinned */
		if ( isset( \IPS\Request::i()->pinned ) and in_array( 'IPS\Content\Pinnable', class_implements( get_class( $item ) ) ) )
		{
			$pinnedColumn = $item::$databaseColumnMap['pinned'];
			$item->$pinnedColumn = intval( \IPS\Request::i()->pinned );
		}
		
		/* Featured */
		if ( isset( \IPS\Request::i()->featured ) and in_array( 'IPS\Content\Featurable', class_implements( get_class( $item ) ) ) )
		{
			$featuredColumn = $item::$databaseColumnMap['featured'];
			$item->$featuredColumn = intval( \IPS\Request::i()->featured );
		}
		
		/* Return */
		return $item;
	}
	
	/**
	 * Create
	 *
	 * @param	\IPS\Node\Model	$container			Container
	 * @param	\IPS\Member		$author				Author
	 * @param	string			$firstPostParam		The parameter which contains the body for the first comment
	 * @return	\IPS\Content\Item
	 */
	protected function _create( \IPS\Node\Model $container = NULL, \IPS\Member $author, $firstPostParam = 'post' )
	{
		$class = $this->class;
		
		/* Work out the date */
		$date = \IPS\Request::i()->date ? new \IPS\DateTime( \IPS\Request::i()->date ) : \IPS\DateTime::create();
		
		/* Create item */
		$item = $class::createItem( $author, \IPS\Request::i()->ip_address ?: \IPS\Request::i()->ipAddress(), $date, $container );
		$this->_createOrUpdate( $item );
		$item->save();
		
		/* Create post */
		if ( $class::$firstCommentRequired )
		{
			$commentClass = $item::$commentClass;
			$post = $commentClass::create( $item, \IPS\Request::i()->$firstPostParam, TRUE, $author->member_id ? NULL : $author->name, NULL, $author, $date );
			if ( isset( $class::$databaseColumnMap['first_comment_id'] ) )
			{
				$firstCommentColumn = $class::$databaseColumnMap['first_comment_id'];
				$commentIdColumn = $commentClass::$databaseColumnId;
				$item->$firstCommentColumn = $post->$commentIdColumn;
				$item->save();
			}
		}
		
		/* Index */
		if ( $item instanceof \IPS\Content\Searchable )
		{
			\IPS\Content\Search\Index::i()->index( $item );
		}
		
		/* Send notifications */
		if ( !$item->hidden() )
		{
			$item->sendNotifications();
		}
		elseif( $item->hidden() !== -1 )
		{
			$item->sendUnapprovedNotification();
		}
		
		/* Output */
		return $item;
	}
	
	/**
	 * View Comments or Reviews
	 *
	 * @param	int		$id				ID Number
	 * @param	string	$commentClass	The class
	 * @param	array	$where			Base where clause
	 * @return	\IPS\Api\PaginatedResponse
	 */
	protected function _comments( $id, $commentClass, $where = array() )
	{
		/* Init */
		$itemClass = $this->class;
		$item = $itemClass::load( $id );
		$itemIdColumn = $itemClass::$databaseColumnId;
		$where [] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=?', $item->$itemIdColumn );
		
		/* Hidden? */
		if ( isset( \IPS\Request::i()->hidden ) AND in_array( 'IPS\Content\Hideable', class_implements( $commentClass ) ) )
		{
			if ( \IPS\Request::i()->hidden )
			{
				if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '<>0' );
				}
				else
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '<>1' );
				}
			}
			else
			{
				if ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=0' );
				}
				else
				{
					$where[] = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=1' );
				}
			}
		}
		
		if ( $commentClass::commentWhere() !== NULL )
		{
			$where[] = $commentClass::commentWhere();
		}
		
		/* Sort */
		$sortBy = $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['date'];
		$sortDir = ( isset( \IPS\Request::i()->sortDir ) and in_array( mb_strtolower( \IPS\Request::i()->sortDir ), array( 'asc', 'desc' ) ) ) ? \IPS\Request::i()->sortDir : 'asc';
		
		return new \IPS\Api\PaginatedResponse(
			200,
			\IPS\Db::i()->select( '*', $commentClass::$databaseTable, $where, "{$sortBy} {$sortDir}", NULL, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS ),
			isset( \IPS\Request::i()->page ) ? \IPS\Request::i()->page : 1,
			$commentClass
		);
	}
}