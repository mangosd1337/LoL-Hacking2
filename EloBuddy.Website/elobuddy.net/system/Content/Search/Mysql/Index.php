<?php
/**
 * @brief		MySQL Search Index
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search\Mysql;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * MySQL Search Index
 */
class _Index extends \IPS\Content\Search\Index
{
	/**
	 * Index an item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	void
	 */
	public function index( \IPS\Content\Searchable $object )
	{
		/* Get the index data */
		$indexData = $this->indexData( $object );

		/* If we got the data... */
		if( $indexData )
		{
			/* If nobody has permission to access it, just remove it */
			if ( !$indexData['index_permissions'] )
			{
				$this->removeFromSearchIndex( $object );
			}
			/* Otherwise, go ahead... */
			else
			{
				/* Adjust tags */
				$tags = NULL;
				$existingIndexId = NULL;
				if ( array_key_exists( 'index_tags', $indexData ) )
				{
					/* We need any existing index IDs */
					try
					{
						$existingIndexId = \IPS\Db::i()->select( 'index_id', 'core_search_index', array( 'index_class=? AND index_item_id=?', $indexData['index_class'], $indexData['index_item_id'] ) )->first();
					}
					catch( \Exception $e )
					{
						$existingIndexId =  NULL;
					}

					$tags = array_filter( array_merge ( array( $indexData['index_prefix'] ), explode( ',', $indexData['index_tags'] ) ) );
					$prefix = $indexData['index_prefix'];
					unset( $indexData['index_tags'] );
					unset( $indexData['index_prefix'] );
				}
				
				/* Index it */
				$newIndexId = \IPS\Db::i()->replace( 'core_search_index', $indexData, TRUE );
								
				/* If that was successful... */
				if ( $newIndexId )
				{
					if ( count( $tags ) )
					{
						/* Remove existing tags */
						if ( $existingIndexId )
						{
							\IPS\Db::i()->delete( 'core_search_index_tags', array( 'index_id=?', $existingIndexId ) );
						}

						foreach( $tags as $tag )
						{
							\IPS\Db::i()->replace( 'core_search_index_tags', array( 'index_id' => $newIndexId, 'index_tag' => $tag, 'index_is_prefix' => ( $tag == $prefix ) ) );
						}
					}
					
					$class = get_class( $object );
					$databaseColumnId = $object::$databaseColumnId;
					
					/* Set index_item_index_id on other index items */
					if ( $object instanceof \IPS\Content\Item )
					{
						$subClasses = array( $class );
						if ( isset( $class::$commentClass ) )
						{
							$subClasses[] = $class::$commentClass;
						}
						if ( isset( $class::$reviewClass ) )
						{
							$subClasses[] = $class::$reviewClass;
						}
						
						\IPS\Db::i()->update( 'core_search_index', array( 'index_item_index_id' => $newIndexId ), array( array( \IPS\Db::i()->in( 'index_class', $subClasses ) ), array( 'index_item_id=?', $object->$databaseColumnId ) ) );
					}
					elseif ( $object instanceof \IPS\Content\Comment )
					{
						$itemClass = $object::$itemClass;
						if ( $itemClass::$firstCommentRequired and $object->isFirst() )
						{						
							$itemColumnId = $class::$databaseColumnMap['item'];
							\IPS\Db::i()->update( 'core_search_index', array( 'index_item_index_id' => $newIndexId ), array( \IPS\Db::i()->in( 'index_class', array( $class, $class::$itemClass ) ) . ' AND index_item_id=?', $object->$itemColumnId ) );
						}
					}
					
					/* And also set core_follow.follow_index_id */
					if ( $object instanceof \IPS\Content\Comment )
					{
						$class = $class::$itemClass;
					}
					\IPS\Db::i()->update( 'core_follow', array( 'follow_index_id' => $newIndexId ), array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', $object::$application, mb_strtolower( mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 ) ), $object->$databaseColumnId ) );
				
					/* If this is the latest comment, unflag what was set before */
					if ( $indexData['index_is_last_comment'] and $indexData['index_item_id'] )
					{
						\IPS\Db::i()->update( 'core_search_index', array( 'index_is_last_comment' => 0 ), array( 'index_class=? AND index_item_id=? AND index_id<>?', $indexData['index_class'], $indexData['index_item_id'], $newIndexId ) );
					}
				}
			}
		}
	}
	
	/**
	 * Retrieve the search ID for an item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to add
	 * @return	void
	 */
	public function getIndexId( \IPS\Content\Searchable $object )
	{
		$databaseColumnId = $object::$databaseColumnId;
		return \IPS\Db::i()->select( 'index_id', 'core_search_index', array( 'index_class=? AND index_object_id=?', get_class( $object ),$object->$databaseColumnId ) )->first();
	}
	
	/**
	 * Remove item
	 *
	 * @param	\IPS\Content\Searchable	$object	Item to remove
	 * @return	void
	 */
	public function removeFromSearchIndex( \IPS\Content\Searchable $object )
	{
		$class = get_class( $object );
		$idColumn = $class::$databaseColumnId;
		
		/* Tags */
		\IPS\Db::i()->delete( 'core_search_index_tags', array( 'index_id IN( ? )', \IPS\Db::i()->select( 'index_id', 'core_search_index', array( 'index_class=? AND index_object_id=?', $class, $object->$idColumn ) ) ) );
		\IPS\Db::i()->delete( 'core_search_index', array( 'index_class=? AND index_object_id=?', $class, $object->$idColumn ) );
		
		if ( isset( $class::$commentClass ) )
		{
			$commentClass = $class::$commentClass;
			\IPS\Db::i()->delete( 'core_search_index_tags', array( 'index_id IN( ? )', \IPS\Db::i()->select( 'index_id', 'core_search_index', array( 'index_class=? AND index_item_id=?', $commentClass, $object->$idColumn ) ) ) );
			\IPS\Db::i()->delete( 'core_search_index', array( 'index_class=? AND index_item_id=?', $commentClass, $object->$idColumn ) );
		}
		
		if ( isset( $class::$reviewClass ) )
		{
			$reviewClass = $class::$reviewClass;
			\IPS\Db::i()->delete( 'core_search_index_tags', array( 'index_id IN( ? )', \IPS\Db::i()->select( 'index_id', 'core_search_index', array( 'index_class=? AND index_item_id=?', $reviewClass, $object->$idColumn ) ) ) );
			\IPS\Db::i()->delete( 'core_search_index', array( 'index_class=? AND index_item_id=?', $reviewClass, $object->$idColumn ) );
		}
	}
	
	/**
	 * Removes all content for a classs
	 *
	 * @param	string		$class 			The class
	 * @param	int|NULL	$containerId	The container ID to update, or NULL
	 * @return	void
	 */
	public function removeClassFromSearchIndex( $class, $containerId=NULL )
	{
		$where = array( array( 'index_class=?', $class ) );
		if ( $containerId !== NULL )
		{
			$where[] = array( 'index_container_id=?', $containerId );
		}
		
		\IPS\Db::i()->delete( 'core_search_index_tags', array( 'index_id IN( ? )', \IPS\Db::i()->select( 'index_id', 'core_search_index', $where ) ) );
		\IPS\Db::i()->delete( 'core_search_index', $where );
	}
	
	/**
	 * Mass Update (when permissions change, for example)
	 *
	 * @param	string				$class 						The class
	 * @param	int|NULL			$containerId				The container ID to update, or NULL
	 * @param	int|NULL			$itemId						The item ID to update, or NULL
	 * @param	string|NULL			$newPermissions				New permissions (if applicable)
	 * @param	int|NULL			$newHiddenStatus			New hidden status (if applicable) special value 2 can be used to indicate hidden only by parent
	 * @param	int|NULL			$newContainer				New container ID (if applicable)
	 * @param	int|NULL			$authorId					The author ID to update, or NULL
	 * @param	int|NULL			$newItemId					The new item ID (if applicable)
	 * @param	int|NULL			$newItemAuthorId			The new item author ID (if applicable)
	 * @param	bool				$addAuthorToPermissions		If true, the index_author_id will be added to $newPermissions - used when changing the permissions for a node which allows access only to author's items
	 * @return	void
	 */
	public function massUpdate( $class, $containerId = NULL, $itemId = NULL, $newPermissions = NULL, $newHiddenStatus = NULL, $newContainer = NULL, $authorId = NULL, $newItemId = NULL, $newItemAuthorId = NULL, $addAuthorToPermissions = FALSE )
	{
		$where = array( array( 'index_class=?', $class ) );
		if ( $containerId !== NULL )
		{
			$where[] = array( 'index_container_id=?', $containerId );
		}
		if ( $itemId !== NULL )
		{
			$where[] = array( 'index_item_id=?', $itemId );
		}
		if ( $authorId !== NULL )
		{
			$where[] = array( 'index_item_author=?', $authorId );
		}

		$update = array();
		if ( $newPermissions !== NULL )
		{
			$update['index_permissions'] = $newPermissions;
		}
		if ( $newContainer )
		{
			$update['index_container_id'] = $newContainer;
		}
		if ( $newItemId )
		{
			$update['index_item_id'] = $newItemId;
		}
		if ( $newItemAuthorId )
		{
			$update['index_item_author'] = $newItemAuthorId;
		}
		
		if ( count( $update ) )
		{
			\IPS\Db::i()->update( 'core_search_index', $update, $where );
		}
		if ( $addAuthorToPermissions )
		{
			$addAuthorToPermissionsWhere = $where;
			$addAuthorToPermissionsWhere[] = array( 'index_author<>0' );
			\IPS\Db::i()->update( 'core_search_index', "index_permissions = CONCAT( index_permissions, ',m.', index_author )", $addAuthorToPermissionsWhere );
		}
		
		if ( $newHiddenStatus !== NULL )
		{
			if ( $newHiddenStatus === 2 )
			{
				$where[] = array( 'index_hidden=0' );
			}
			else
			{
				$where[] = array( 'index_hidden=2' );
			}
			
			\IPS\Db::i()->update( 'core_search_index', array( 'index_hidden' => $newHiddenStatus ), $where );
		}
	}
	
	/**
	 * Update data for the first and last comment after a merge
	 * Sets index_is_last_comment on the last comment, and, if this is an item where the first comment is indexed rather than the item, sets index_title and index_tags on the first comment
	 *
	 * @param	\IPS\Content\Item	$item	The item
	 * @return	void
	 */
	public function rebuildAfterMerge( \IPS\Content\Item $item )
	{
		if ( $item::$commentClass )
		{
			$firstComment = $item->comments( 1, 0, 'date', 'asc', NULL, FALSE, NULL, NULL, TRUE );
			$lastComment = $item->comments( 1, 0, 'date', 'desc', NULL, FALSE, NULL, NULL, TRUE );
			
			$idColumn = $item::$databaseColumnId;
			$update = array( 'index_is_last_comment' => 0 );
			if ( $item::$firstCommentRequired )
			{
				$update['index_title'] = NULL;
			}
			\IPS\Db::i()->update( 'core_search_index', $update, array( 'index_class=? AND index_item_id=?', $item::$commentClass, $item->$idColumn ) );
	
			if ( $firstComment )
			{
				$this->index( $firstComment );
			}
			if ( $lastComment )
			{
				$this->index( $lastComment );
			}
		}
	}
	
	/**
	 * Prune search index
	 *
	 * @param	\IPS\DateTime|NULL	$cutoff	The date to delete index records from, or NULL to delete all
	 * @return	void
	 */
	public function prune( \IPS\DateTime $cutoff = NULL )
	{
		if ( $cutoff )
		{
			\IPS\Db::i()->delete( 'core_search_index_tags', array( 'index_id IN( ? )', \IPS\Db::i()->select( 'index_id', 'core_search_index', array( 'index_date_updated < ?', $cutoff->getTimestamp() ) ) ) );
			\IPS\Db::i()->delete( 'core_search_index', array( 'index_date_updated < ?', $cutoff->getTimestamp() ) );
		}
		else
		{
			\IPS\Db::i()->delete( 'core_search_index_tags' );
			\IPS\Db::i()->delete( 'core_search_index' );
		}
	}
}