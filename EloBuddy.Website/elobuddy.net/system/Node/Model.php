<?php
/**
 * @brief		Node Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Node Model
 */
abstract class _Model extends \IPS\Patterns\ActiveRecord
{
	/* !Abstract Properties */
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = NULL;
	
	/**
	 * @brief	[Node] Parent ID Root Value
	 * @note	This normally doesn't need changing though some legacy areas use -1 to indicate a root node
	 */
	public static $databaseColumnParentRootValue = 0;
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = NULL;
	
	/**
	 * @brief	[Node] Enabled/Disabled Column
	 */
	public static $databaseColumnEnabledDisabled = NULL;

	/**
	 * @brief	[Node] If the node can be "owned", the owner "type" (typically "member" or "group") and the associated database column
	 */
	public static $ownerTypes = NULL;
	
	/**
	 * @brief	[Node] Sortable?
	 */
	public static $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static $subnodeClass = NULL;
		
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = FALSE;
	
	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = NULL;
	
	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = NULL;

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as the key
	 */
	public static $titleLangPrefix = NULL;
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = NULL;

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = '';

	/**
	 * @brief	[Node] By mapping appropriate columns (rating_average and/or rating_total + rating_hits) allows to cache rating values
	 */
	public static $ratingColumnMap	= array();
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = NULL;
	
	/* !Static Methods */
	
	/**
	 * @brief	Cache for roots
	 */
	protected static $rootsResult = array();

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @return	array
	 */
	public static function roots( $permissionCheck='view', $member=NULL, $where=array() )
	{
		$usingPermssions = ( in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) and $permissionCheck !== NULL );

		if ( $usingPermssions )
		{
			$member = $member ?: \IPS\Member::loggedIn();
			$cacheKey = md5( get_called_class() . $permissionCheck . $member->member_id . json_encode( $where ) );
		}
		else
		{
			$cacheKey = md5( get_called_class() . $permissionCheck . json_encode( $where ) );
		}

		/* Check the cache first */
		if( !$where and isset( static::$rootsResult[ $cacheKey ] ) )
		{
			return static::$rootsResult[ $cacheKey ];
		}
						
		/* Specify that we only want the ones without a parent */
		if( static::$databaseColumnParent !== NULL )
		{
			$where[] = array( static::$databasePrefix . static::$databaseColumnParent . '=?', static::$databaseColumnParentRootValue );
		}
		
		/* Permission check? */
		if ( $usingPermssions )
		{
			$where[] = array( '(' . \IPS\Db::i()->findInSet( 'core_permission_index.perm_' . static::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'core_permission_index.perm_' . static::$permissionMap[ $permissionCheck ] . '=? )', '*' );
			if ( static::$databaseColumnEnabledDisabled )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnEnabledDisabled . '=1' );
			}
		}

		/* Specify the order */
		$order = NULL;
		if( static::$databaseColumnOrder !== NULL )
		{
			$order = static::$databasePrefix . static::$databaseColumnOrder;
		}
		
		/* Select */
		$select = \IPS\Db::i()->select( '*', static::$databaseTable, $where, $order );
		
		if ( $usingPermssions )
		{
			$select->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType ) );
		}
		$select->setKeyField( static::$databasePrefix . static::$databaseColumnId );

		/* Fetch */
		$nodes = array();
		foreach( $select as $k => $data )
		{
			try
			{
				$nodes[ $k ] = static::constructFromData( $data );
			}
			catch ( \Exception $e ) { }
		}
		
		/* Set cache */
		static::$rootsResult[ $cacheKey ] = $nodes;
		
		/* Return */
		return static::$rootsResult[ $cacheKey ];
	}
	
	/**
	 * Get a count of all nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULL to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @return	array
	 */
	public static function countWhere( $permissionCheck='view', $member=NULL, $where=array() )
	{
		/* Permission check? */
		$usingPermssions = ( in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) and $permissionCheck !== NULL );
		if ( $usingPermssions )
		{
			$member = $member ?: \IPS\Member::loggedIn();

			$where[] = array( '(' . \IPS\Db::i()->findInSet( 'core_permission_index.perm_' . static::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'core_permission_index.perm_' . static::$permissionMap[ $permissionCheck ] . '=? )', '*' );
			if ( static::$databaseColumnEnabledDisabled )
			{
				$where[] = array( static::$databasePrefix . static::$databaseColumnEnabledDisabled . '=1' );
			}
		}

		/* Select */
		$select = \IPS\Db::i()->select( 'COUNT(*)', static::$databaseTable, $where );
		if ( $usingPermssions )
		{
			$select->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType ) );
		}
		
		/* Return */
		return $select->first();
	}
	
	/**
	 * Fetch All Root Nodes as array
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @return	array
	 */
	public static function rootsAsArray( $permissionCheck='view', $member=NULL, $where=array() )
	{
		$return = array();
		foreach ( static::roots( $permissionCheck, $member, $where ) as $node )
		{
			$return[ $node->_id ] = $node->_title;
		}
		return $return;
	}
	
	/**
	 * @brief	Cache for owned noded
	 */
	protected static $ownedNodesCache = array();

	/**
	 * Fetch all nodes owned by a given user
	 *
	 * @param	\IPS\Member|NULL	$member		The member whose nodes to load
	 * @param	array				$where		Initial where clause
	 * @return	array
	 * @throws	\RuntimeException
	 */
	public static function loadByOwner( $member=NULL, $where=array() )
	{
		/* Can these nodes even be owned? */
		if( static::$ownerTypes === NULL )
		{
			throw new \RuntimeException;
		}

		/* Load member */
		$member = $member === NULL ? \IPS\Member::loggedIn() : $member;

		if( is_int( $member ) )
		{
			$member	= \IPS\Member::load( $member );
		}

		/* Check the cache first */
		if( isset( static::$ownedNodesCache[ md5( get_called_class() . $member->member_id . json_encode( $where ) ) ] ) )
		{
			return static::$ownedNodesCache[ md5( get_called_class() . $member->member_id . json_encode( $where ) ) ];
		}

		/* Specify the order */
		$order = NULL;
		if( static::$databaseColumnOrder !== NULL )
		{
			$order = static::$databasePrefix . static::$databaseColumnOrder;
		}
		
		/* Select */
		if( isset( static::$ownerTypes['member'] ) and isset( static::$ownerTypes['group'] ) )
		{
			$where[] = array( '(' . \IPS\Db::i()->findInSet( static::$databasePrefix . static::$ownerTypes['group']['ids'], $member->groups ) . ' OR ' . static::$databasePrefix . static::$ownerTypes['member'] . '=? )', $member->member_id );
		}
		elseif( isset( static::$ownerTypes['member'] ) )
		{
			$where[] = array( static::$databasePrefix . static::$ownerTypes['member'] . '=?', $member->member_id );
		}
		else
		{
			$where[] = array( \IPS\Db::i()->findInSet( static::$databasePrefix . static::$ownerTypes['group']['ids'], $member->groups ) );
		}

		$select = \IPS\Db::i()->select( '*', static::$databaseTable, $where, $order );
		
		$select->setKeyField( static::$databasePrefix . static::$databaseColumnId );

		/* Fetch */
		$nodes = array();
		foreach( $select as $k => $data )
		{
			$nodes[ $k ] = static::constructFromData( $data );
		}
		
		/* Set cache */
		static::$ownedNodesCache[ md5( get_called_class(). $member->member_id  . json_encode( $where ) ) ] = $nodes;
		
		/* Return */
		return static::$ownedNodesCache[ md5( get_called_class(). $member->member_id  . json_encode( $where ) ) ];
	}

	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( $column, $query, $order, $where=array() )
	{
		if ( $column === '_title' AND static::$titleLangPrefix !== NULL )
		{
			$return = array();
			foreach ( \IPS\Member::loggedIn()->language()->searchCustom( static::$titleLangPrefix, $query ) as $key => $value )
			{
				try
				{
					$return[ $key ] = static::load( $key );
				}
				catch ( \OutOfRangeException $e ) { }
			}

			return $return;
		}

		$nodes = array();
		foreach( \IPS\Db::i()->select( '*', static::$databaseTable, array_merge( array( array( "{$column} LIKE CONCAT( '%', ?, '%' )", $query ) ), $where ), $order ) as $k => $data )
		{
			$nodes[ $k ] = static::constructFromData( $data );
		}
		return $nodes;
	}
	
	/**
	 * Last Poster ID Column
	 */
	protected static $lastPosterIdColumn;
			
	/**
	 * Load into memory (taking permissions into account)
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	array				$where				Additional where clause
	 * @return	void
	 */
	public static function loadIntoMemory( $permissionCheck='view', $member=NULL, $where = array() )
	{
		/* Init */
		$member = $member ?: \IPS\Member::loggedIn();
		$cacheKey = md5( $permissionCheck . $member->member_id . TRUE . json_encode( NULL ) . json_encode( array() ) );
		$rootsCacheKey = md5( get_called_class() . $permissionCheck . $member->member_id . json_encode( array() ) );
		
		/* Exclude disabled */
		if ( static::$databaseColumnEnabledDisabled )
		{
			$where[] = array( static::$databasePrefix . static::$databaseColumnEnabledDisabled . '=1' );
		}
		
		/* Run query */
		$order = static::$databaseColumnOrder !== NULL ? static::$databasePrefix . static::$databaseColumnOrder : NULL;
		if ( in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) and $permissionCheck !== NULL )
		{
			$where[] = array( '(' . \IPS\Db::i()->findInSet( 'perm_' . static::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . static::$permissionMap[ $permissionCheck ] . '=? )', '*' );
			
			$select = \IPS\Db::i()->select( '*', static::$databaseTable, $where, $order, NULL, NULL, NULL, \IPS\Db::SELECT_MULTIDIMENSIONAL_JOINS )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType ) );
		}
		else
		{
			$select = \IPS\Db::i()->select( '*', static::$databaseTable, NULL, $order, NULL, NULL, NULL, \IPS\Db::SELECT_MULTIDIMENSIONAL_JOINS );
		}
		
		/* Join last poster */
		if ( static::$lastPosterIdColumn )
		{
			$select->join( 'core_members', 'core_members.member_id=' . static::$databaseTable . '.' . static::$databasePrefix . static::$lastPosterIdColumn );
		}
		
		/* Put into a tree */
		$childrenResults = array();		
		foreach ( $select as $row )
		{
			/* If the class does not implement permissions or last poster ID nest the result */
			if( !isset( $row[ static::$databaseTable ] ) )
			{
				$row[ static::$databaseTable ] = $row;
			}

			/* If we have member data, store it to prevent an extra query later */
			if ( isset( $row['core_members'] ) )
			{
				\IPS\Member::constructFromData( $row['core_members'], FALSE );
			}
			
			/* Create object */
			$obj = static::constructFromData( isset( $row['core_permission_index'] ) ? array_merge( $row[ static::$databaseTable ], $row['core_permission_index'] ) : $row[ static::$databaseTable ], FALSE );

			/* Put into tree */
			$obj->_childrenResults[ $cacheKey ] = array();
			if ( $row[ static::$databaseTable ][ static::$databasePrefix . static::$databaseColumnParent ] === static::$databaseColumnParentRootValue )
			{
				static::$rootsResult[ $rootsCacheKey ][ $obj->_id ] = $obj;
			}
			else
			{
				$childrenResults[ $row[ static::$databaseTable ][ static::$databasePrefix . static::$databaseColumnParent ] ][ $obj->_id ] = $obj;
			}
		}
		
		/* And set the multitons */
		foreach ( $childrenResults as $parentId => $children )
		{
			if( isset( static::$multitons[ $parentId ] ) )
			{
				static::$multitons[ $parentId ]->_childrenResults[ $cacheKey ] = $children;
			}
		}
	}

	/**
	 * Get URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if ( isset( static::$urlBase ) and isset( static::$urlTemplate ) and isset( static::$seoTitleColumn ) )
		{
			if( $this->_url === NULL )
			{
				$seoTitleColumn = static::$seoTitleColumn;
				$this->_url = \IPS\Http\Url::internal( static::$urlBase . $this->_id, 'front', static::$urlTemplate, array( $this->$seoTitleColumn ) );
			}
	
			return $this->_url;
		}
		throw new \BadMethodCallException;
	}
	
	/**
	 * Columns needed to query for search result / stream view
	 *
	 * @return	array
	 */
	public static function basicDataColumns()
	{
		return array( static::$databasePrefix . static::$databaseColumnId, static::$databasePrefix . static::$seoTitleColumn );
	}
	
	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @return	\IPS\Http\Url
	 */
	public static function urlFromIndexData( $indexData, $itemData, $containerData )
	{
		return \IPS\Http\Url::internal( static::$urlBase . $indexData['index_container_id'], 'front', static::$urlTemplate, array( $containerData[ static::$databasePrefix . static::$seoTitleColumn ] ) );
	}

	/**
	 * Get URL from index data
	 *
	 * @param	array		$indexData		Data from the search index
	 * @param	array		$itemData		Basic data about the item. Only includes columns returned by item::basicDataColumns()
	 * @param	array|NULL	$containerData	Basic data about the container. Only includes columns returned by container::basicDataColumns()
	 * @return	\IPS\Http\Url
	 */
	public static function titleFromIndexData( $indexData, $itemData, $containerData )
	{
		return \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $indexData['index_container_id'] );
	}

	/* !Getters */
	
	/**
	 * [Node] Get ID Number
	 *
	 * @return	int
	 */
	protected function get__id()
	{
		$idColumn = static::$databaseColumnId;
		return $this->$idColumn;
	}

	/**
	 * [Node] Get the title to store in the log
	 *
	 * @return	string|null
	 */
	public function titleForLog()
	{
		if ( static::$titleLangPrefix )
		{
			try
			{
				return \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( static::$titleLangPrefix . $this->_id );
			}
			catch ( \UnderflowException $e )
			{
				return static::$titleLangPrefix . $this->_id;
			}
		}
		else
		{
			return $this->_title;
		}
	}

	/**
	 * [Node] Get Title
	 *
	 * @return	string|null
	 */
	protected function get__title()
	{
		if ( static::$titleLangPrefix )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->_id );
		}
		return '';
	}
	
	/**
	 * Node titles can contain HTML. Apparently.
	 *
	 * @return string
	 */
	public function get__stripTagsTitle()
	{
		if ( static::$titleLangPrefix )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->_id, NULL, array( 'striptags' => true ) );
		}
		return '';
	}
	
	/**
	 * [Node] Get Node Description
	 *
	 * @return	string|null
	 */
	protected function get__description()
	{
		return NULL;
	}
	
	/**
	 * [Node] Get content table description 
	 *
	 * @return	string
	 */
	protected function get_description()
	{
		if ( static::$titleLangPrefix and static::$descriptionLangSuffix )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->id . static::$descriptionLangSuffix );
		}
		return NULL;
	}
	
	/**
	 * [Node] Get content table meta description 
	 *
	 * @return	string
	 */
	public function metaDescription()
	{
		if ( static::$titleLangPrefix and static::$descriptionLangSuffix )
		{
			return \IPS\Member::loggedIn()->language()->addToStack( static::$titleLangPrefix . $this->id . static::$descriptionLangSuffix, FALSE, array( 'striptags' => TRUE ) );
		}
		return NULL;
	}

	/**
	 * [Node] Get content table meta title
	 *
	 * @return	string
	 */
	public function metaTitle()
	{
		return $this->_stripTagsTitle;
	}
		
	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge()
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return array(
				0	=> 'ipsBadge ipsBadge_intermediary',
				1	=> 'node_move_delete_queued',
			);
		}
		
		return NULL;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe', the 'fa fa-' is added automatically so you do not need this here)
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return NULL;
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		if ( $col = static::$databaseColumnEnabledDisabled )
		{
			return (bool) $this->$col;
		}
		return NULL;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
		if ( $col = static::$databaseColumnEnabledDisabled )
		{
			$this->$col = $enabled;
		}
	}

	/**
	 * [Node] Get whether or not this node is locked to current enabled/disabled status
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__locked()
	{
		return NULL;
	}
	
	/**
	 * [Node] Get position
	 *
	 * @return	int
	 */
	protected function get__position()
	{
		$orderColumn = static::$databaseColumnOrder;
		return $this->$orderColumn;
	}
	
	/**
	 * [Node] Get number of content items
	 *
	 * @return	int
	 */
	protected function get__items()
	{
		return NULL;
	}
	
	/**
	 * Set number of items
	 *
	 * @param	int	$val	Items
	 * @return	void
	 */
	protected function set__items( $val )
	{
		
	}
	
	/**
	 * [Node] Get number of content comments
	 *
	 * @return	int
	 */
	protected function get__comments()
	{
		return NULL;
	}
	
	/**
	 * Set number of content comments
	 *
	 * @param	int	$val	Comments
	 * @return	void
	 */
	protected function set__comments( $val )
	{
		
	}
	
	/**
	 * [Node] Get number of content reviews
	 *
	 * @return	int
	 */
	protected function get__reviews()
	{
		return NULL;
	}
	
	/**
	 * Set number of content reviews
	 *
	 * @param	int	$val	Reviews
	 * @return	void
	 */
	protected function set__reviews( $val )
	{
		
	}

	/**
	 * [Node] Get number of future publishing items
	 *
	 * @return	int
	 */
	protected function get__futureItems()
	{
		return NULL;
	}

	/**
	 * [Node] Get number of unapproved content items
	 *
	 * @return	int
	 */
	protected function get__unnapprovedItems()
	{
		return NULL;
	}
	
	/**
	 * [Node] Get number of unapproved content comments
	 *
	 * @return	int
	 */
	protected function get__unapprovedComments()
	{
		return NULL;
	}
	
	/**
	 * [Node] Get number of unapproved content reviews
	 *
	 * @return	int
	 */
	protected function get__unapprovedReviews()
	{
		return NULL;
	}
	
	/**
	 * Get sort key
	 *
	 * @return	string
	 */
	public function get__sortBy()
	{
		return NULL;
	}
	
	/**
	 * Get sort order
	 *
	 * @return	string
	 */
	public function get__sortOrder()
	{
		foreach ( array( 'title', 'author_name', 'last_comment_name' ) as $k )
		{
			$contentItemClass = static::$contentItemClass;
			if ( isset( $contentItemClass::$databaseColumnMap[ $k ] ) and $this->_sortBy === $contentItemClass::$databaseColumnMap[ $k ] )
			{
				return 'ASC';
			}
		}
		
		return 'DESC';
	}
	
	/**
	 * Get default filter
	 *
	 * @return	string
	 */
	public function get__filter()
	{
		return NULL;
	}

	/**
	 * [Node] Return the owner if this node can be owned
	 *
	 * @throws	\RuntimeException
	 * @return	\IPS\Member|null
	 */
	public function owner()
	{
		if( static::$ownerTypes['member'] === NULL and static::$ownerTypes['group'] === NULL )
		{
			throw new \RuntimeException;
		}

		if ( static::$ownerTypes['member'] )
		{
			$column	= static::$ownerTypes['member'];
			if( $this->$column )
			{
				return \IPS\Member::load( $this->$column );
			}
		}

		return NULL;
	}
	
	/**
	 * Set last comment
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The latest comment or NULL to work it out
	 * @return	int
	 */
	public function setLastComment( \IPS\Content\Comment $comment=NULL )
	{
		// Don't do anything by default, but nodes could extract data
	}
	
	/**
	 * Get last comment time
	 *
	 * @note	This should return the last comment time for this node only, not for children nodes
	 * @return	\IPS\DateTime|NULL
	 */
	public function getLastCommentTime()
	{
		return NULL;
	}
	
	/**
	 * Set last review
	 *
	 * @param	\IPS\Content\Review|NULL	$review	The latest review or NULL to work it out
	 * @return	int
	 */
	public function setLastReview( \IPS\Content\Review $review=NULL )
	{
		// Don't do anything by default, but nodes could extract data
	}
	
	/* !Parent/Children/Siblings */
	
	/**
	 * [Node] Get Parent
	 *
	 * @return	static|null
	 */
	public function parent()
	{
		if ( isset( static::$parentNodeClass ) )
		{
			$parentNodeClass = static::$parentNodeClass;
			$parentColumn = static::$parentNodeColumnId;
			if( $this->$parentColumn )
			{
				return $parentNodeClass::load( $this->$parentColumn );
			}
		}
		
		if( static::$databaseColumnParent !== NULL )
		{
			$parentColumn = static::$databaseColumnParent;
			if( $this->$parentColumn !== static::$databaseColumnParentRootValue )
			{
				return static::load( $this->$parentColumn );
			}
		}
		
		return NULL;
	}
	
	/**
	 * [Node] Get parent list
	 *
	 * @return	\SplStack
	 */
	public function parents()
	{
		$stack = new \SplStack;
		
		$working = $this;
		while ( $working = $working->parent() )
		{

			if( ! $working instanceof \IPS\Node\Model )
			{
				return $stack;
			}

			$stack->push( $working );
		}
		
		return $stack;
	}
	
	/**
	 * Is this node a child (or sub child, or sub-sub-child etc) of another node?
	 *
	 * @param	\IPS\Node\Model	$node	The node to check
	 * @return	bool
	 */
	public function isChildOf( \IPS\Node\Model $node )
	{
		foreach ( $this->parents() as $parent )
		{
			if ( $parent == $node )
			{
				return TRUE;
			}
		}
		return FALSE;
	}
		
	/**
	 * [Node] Does this node have children?
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	bool				$subnodes			Include subnodes? NULL to *only* check subnodes
	 * @param	mixed				$_where				Additional WHERE clause
	 * @return	bool
	 */
	public function hasChildren( $permissionCheck='view', $member=NULL, $subnodes=TRUE, $_where=array() )
	{
		return ( $this->childrenCount( $permissionCheck, $member, $subnodes, $_where ) > 0 );
	}
		
	/**
	 * @brief	Cache for get__children
	 * @see		\IPS\Node\Model::get__children
	 */
	protected $_childrenResults = array();

	/**
	 * [Node] Get Number of Children
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	bool				$subnodes			Include subnodes? NULL to *only* check subnodes
	 * @param	mixed				$_where				Additional WHERE clause
	 * @return	int
	 */
	public function childrenCount( $permissionCheck='view', $member=NULL, $subnodes=TRUE, $_where=array() )
	{
		/* We almost universally need the children after getting the count, so let's just cut to the chase and run one query instead of 2 */
		return count( $this->children( $permissionCheck, $member, $subnodes, NULL, $_where ) );
	}

	/**
	 * [Node] Fetch Child Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULL to not check permissions
	 * @param	\IPS\Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	bool				$subnodes			Include subnodes? NULL to *only* check subnodes
	 * @param	array|NULL			$skip				Children IDs to skip
	 * @param	mixed				$_where				Additional WHERE clause
	 * @return	array
	 */
	public function children( $permissionCheck='view', $member=NULL, $subnodes=TRUE, $skip=null, $_where=array() )
	{
		$children = array();

		/* Load member */
		if ( $permissionCheck !== NULL )
		{
			$member = ( $member === NULL ) ? \IPS\Member::loggedIn() : $member;
			$cacheKey	= md5( $permissionCheck . $member->member_id . $subnodes . json_encode( $skip ) . json_encode( $_where ) );
		}
		else
		{
			$cacheKey	= md5( $subnodes . json_encode( $skip ) . json_encode( $_where ) );
		}
		if( isset( $this->_childrenResults[ $cacheKey ] ) )
		{
			return $this->_childrenResults[ $cacheKey ];
		}

		/* What's our ID? */
		$idColumn = static::$databaseColumnId;
				
		/* True children */
		if( $subnodes !== NULL and static::$databaseColumnParent !== NULL )
		{
			/* Specify our parent ID */
			$where = $_where;
			$where[] = array( static::$databasePrefix . static::$databaseColumnParent . '=?', $this->$idColumn );
			
			if ( is_array( $skip ) and count( $skip ) )
			{
				$where[] = array( '( ! ' . \IPS\Db::i()->in( static::$databasePrefix . static::$databaseColumnId, $skip ) . ' )' );
			}
			
			/* Permission check? */
			if ( $this instanceof \IPS\Node\Permissions and $permissionCheck !== NULL )
			{
				$where[] = array( '(' . \IPS\Db::i()->findInSet( 'perm_' . static::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . static::$permissionMap[ $permissionCheck ] . '=? )', '*' );
				if ( static::$databaseColumnEnabledDisabled )
				{
					$where[] = array( static::$databasePrefix . static::$databaseColumnEnabledDisabled . '=1' );
				}
				
				$select = \IPS\Db::i()->select( '*', static::$databaseTable, $where, static::$databaseColumnOrder ? ( static::$databasePrefix . static::$databaseColumnOrder ) : NULL )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType ) );
			}
			/* Nope - normal */
			else
			{
				$select = \IPS\Db::i()->select( '*', static::$databaseTable, $where, static::$databaseColumnOrder ? ( static::$databasePrefix . static::$databaseColumnOrder ) : NULL );
			}
						
			/* Get em! */
			foreach( $select as $row )
			{
				$row = static::constructFromData( $row );

				if ( $row instanceof \IPS\Node\Permissions and $permissionCheck !== NULL )
				{
					if( $row->can( $permissionCheck ) )
					{
						$children[]	= $row;
					}
				}
				else
				{
					$children[] = $row;
				}
			}
		}
				
		/* Subnodes */
		if( ( $subnodes === TRUE or $subnodes === NULL ) and static::$subnodeClass !== NULL )
		{
			$subnodeClass = static::$subnodeClass;
			
			/* Specify our parent node ID */
			$where = $_where;
			$where[] = array( $subnodeClass::$databasePrefix . $subnodeClass::$parentNodeColumnId . '=?', $this->$idColumn );
			
			/* If our subnodes can have children themselves, we only want the root ones */
			if( $subnodeClass::$databaseColumnParent !== NULL )
			{
				$where[] = array( $subnodeClass::$databasePrefix . $subnodeClass::$databaseColumnParent . '=?', $subnodeClass::$databaseColumnParentRootValue );
			}
						
			/* Permission check? */
			if ( in_array( 'IPS\Node\Permissions', class_implements( $subnodeClass ) ) and $permissionCheck !== NULL )
			{
				$where[] = array( '(' . \IPS\Db::i()->findInSet( 'perm_' . $subnodeClass::$permissionMap[ $permissionCheck ], $member->groups ) . ' OR ' . 'perm_' . $subnodeClass::$permissionMap[ $permissionCheck ] . '=? )', '*' );
				if ( $subnodeClass::$databaseColumnEnabledDisabled )
				{
					$where[] = array( $subnodeClass::$databasePrefix . $subnodeClass::$databaseColumnEnabledDisabled . '=1' );
				}
				
				$select =\IPS\Db::i()->select( '*', $subnodeClass::$databaseTable, $where, $subnodeClass::$databaseColumnOrder ? ( $subnodeClass::$databasePrefix . $subnodeClass::$databaseColumnOrder ) : NULL )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . $subnodeClass::$databaseTable . "." . $subnodeClass::$databasePrefix . $subnodeClass::$databaseColumnId, $subnodeClass::$permApp, $subnodeClass::$permType ) );
			}
			/* Nope - normal */
			else
			{
				$select = \IPS\Db::i()->select( '*', $subnodeClass::$databaseTable, $where, $subnodeClass::$databaseColumnOrder ? ( $subnodeClass::$databasePrefix . $subnodeClass::$databaseColumnOrder ) : NULL );
			}
						
			/* Get em! */
			foreach( $select as $row )
			{
				$row = $subnodeClass::constructFromData( $row );

				if ( $row instanceof \IPS\Node\Permissions and $permissionCheck !== NULL )
				{
					if( $row->can( $permissionCheck ) )
					{
						$children[]	= $row;
					}
				}
				else
				{
					$children[] = $row;
				}
			}
		}

		$this->_childrenResults[ $cacheKey ]	= $children;
		
		/* Return */
		return $children;
	}
		
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = array();
		
		if ( $subnode )
		{
			$url = $url->setQueryString( array( 'subnode' => 1 ) );
		}
		
		if( $this->canAdd() )
		{
			$buttons['add'] = array(
				'icon'	=> 'plus-circle',
				'title'	=> static::$nodeTitle . '_add_child',
				'link'	=> $url->setQueryString( array( 'subnode' => (int) isset( static::$subnodeClass ), 'do' => 'form', 'parent' => $this->_id ) )
				);
		}
		
		if( $this->canEdit() )
		{
			$buttons['edit'] = array(
				'icon'	=> 'pencil',
				'title'	=> 'edit',
				'link'	=> $url->setQueryString( array( 'do' => 'form', 'id' => $this->_id ) ),
				'data'	=> ( static::$modalForms ? array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit') ) : array() ),
				'hotkey'=> 'e return'
				);
		}
		
		if( $this->canManagePermissions() )
		{
			$buttons['permissions'] = array(
				'icon'	=> 'lock',
				'title'	=> 'permissions',
				'link'	=> $url->setQueryString( array( 'do' => 'permissions', 'id' => $this->_id ) ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('permissions') )
				);
		}
		
		if( $this->canCopy() )
		{
			$buttons['copy'] = array(
				'icon'	=> 'files-o',
				'title'	=> 'copy',
				'link'	=> $url->setQueryString( array( 'do' => 'copy', 'id' => $this->_id ) ),
				'data' => ( $this->hasChildren( NULL, NULL, TRUE ) ) ? array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('copy') ) : array()
				);
		}
		
		if( $this->canDelete() )
		{
			if ( isset( static::$contentItemClass ) AND $this->getContentItemCount() )
			{
				$buttons['empty'] = array(
					'icon'	=> 'trash-o',
					'title'	=> 'empty',
					'link'	=> $url->setQueryString( array( 'do' => 'delete', 'id' => $this->_id, 'deleteNode' => 0 ) ),
					'data' 	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('empty') ),
					'hotkey'=> 'd'
				);
			}
				
			$buttons['delete'] = array(
				'icon'	=> 'times-circle',
				'title'	=> 'delete',
				'link'	=> $url->setQueryString( array( 'do' => 'delete', 'id' => $this->_id, 'deleteNode' => 1 ) ),
				'data' 	=> ( $this->hasChildren( NULL, NULL, TRUE ) or ( isset( static::$contentItemClass ) AND $this->getContentItemCount() ) ) ? array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('delete') ) : array( 'delete' => '' ),
				'hotkey'=> 'd'
			);
		}
	
		return $buttons;
	}
		
	/* !ACP Restrictions */
	
	/**
	 * ACP Restrictions Check
	 *
	 * @param	string	$key	Restriction key to check
	 * @return	bool
	 */
	protected static function restrictionCheck( $key )
	{
		if( !\IPS\Member::loggedIn()->isAdmin() )
		{
			return FALSE;
		}

		if ( static::$restrictions !== NULL )
		{
			$_key = NULL;
			if ( isset( static::$restrictions['prefix'] ) )
			{
				$_key = static::$restrictions['prefix'] . $key;
			}
			if ( isset( static::$restrictions['map'][ $key ] ) )
			{
				$_key = static::$restrictions['map'][ $key ];
			}
			elseif ( isset( static::$restrictions['all'] ) )
			{
				$_key = static::$restrictions['all'];
			}
			
			if ( $_key === NULL )
			{
				return FALSE;
			}

			return \IPS\Member::loggedIn()->hasAcpRestriction( static::$restrictions['app'], static::$restrictions['module'], $_key );
		}

		return TRUE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add aa root node?
	 *
	 * @return	bool
	 */
	public static function canAddRoot()
	{
		return static::restrictionCheck( 'add' );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		/* If there is no parent/child relationship and no subnode class, you can't add a child */
		if( static::$databaseColumnParent === NULL AND static::$subnodeClass === NULL )
		{
			return FALSE;
		}
		
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return FALSE;
		}

		return static::restrictionCheck( 'add' );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit this node?
	 *
	 * @return	bool
	 */
	public function canEdit()
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return FALSE;
		}
		
		if( static::restrictionCheck( 'edit' ) )
		{
			return TRUE;
		}

		if( isset( static::$ownerTypes['member'] ) and static::$ownerTypes['member'] !== NULL )
		{
			$column	= static::$ownerTypes['member'];

			if( $this->$column and $this->$column == \IPS\Member::loggedIn()->member_id )
			{
				return TRUE;
			}
		}

		if( isset( static::$ownerTypes['group'] ) and static::$ownerTypes['group'] !== NULL )
		{
			$column	= static::$ownerTypes['group']['ids'];
		
			$value = $this->$column;
			if( count( array_intersect( explode( ",", $value ), \IPS\Member::loggedIn()->groups ) ) )
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy()
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return FALSE;
		}
		
		return ( !$this->parent() and static::canAddRoot() ) or ( $this->parent() and $this->parent()->canAdd() );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions()
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return FALSE;
		}
		
		return ( static::$permApp !== NULL and static::$permType !== NULL and static::restrictionCheck( 'permissions' ) );
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to delete this node?
	 *
	 * @return	bool
	 */
	public function canDelete()
	{
		if ( $this->deleteOrMoveQueued() === TRUE )
		{
			return FALSE;
		}
		
		if( static::restrictionCheck( 'delete' ) )
		{
			return TRUE;
		}

		if( static::$ownerTypes['member'] !== NULL )
		{
			$column	= static::$ownerTypes['member'];

			if( $this->$column == \IPS\Member::loggedIn()->member_id )
			{
				return TRUE;
			}
		}
		
		if( static::$ownerTypes['group'] !== NULL )
		{
			$column	= static::$ownerTypes['group']['ids'];

			$value = $this->$column;
			if( count( array_intersect( explode( ",", $value ), \IPS\Member::loggedIn()->groups ) ) )
			{
				return TRUE;
			}
		}

		return FALSE;
	}
	
	/* !Front-end permissions */
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array( 'view' => 'view' );
	
	/**
	 * @brief	Permissions
	 */
	protected $_permissions = NULL;
	
	/**
	 * @brief	Permissions when we first loaded them from the DB
	 */
	protected $_originalPermissions = NULL;
	
	/**
	 * Construct Load Query
	 *
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to
	 * @param	mixed		$extraWhereClause	Additional where clause(s)
	 * @return	\IPS\Db\Select
	 */
	protected static function constructLoadQuery( $id, $idField, $extraWhereClause )
	{
		if ( in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) )
		{
			$where = array( array( static::$databaseTable . '.' . $idField . '=?', $id ) );
			if( $extraWhereClause !== NULL )
			{
				if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
				{
					$extraWhereClause = array( $extraWhereClause );
				}
				$where = array_merge( $where, $extraWhereClause );
			}
			
			return \IPS\Db::i()->select(
				static::$databaseTable . '.*, core_permission_index.perm_id, core_permission_index.perm_view, core_permission_index.perm_2, core_permission_index.perm_3, core_permission_index.perm_4, core_permission_index.perm_5, core_permission_index.perm_6, core_permission_index.perm_7',
				static::$databaseTable,
				$where
			)->join(
				'core_permission_index',
				array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType )
			);
		}
		else
		{
			return parent::constructLoadQuery( $id, $idField, $extraWhereClause );
		}
	}
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		if ( in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) )
		{
			/* Does that exist in the multiton store? */
			$obj = NULL;
			if ( isset( static::$databaseColumnId ) )
			{
				$idField = static::$databasePrefix . static::$databaseColumnId;
				$id = $data[ $idField ];
				
				if( isset( static::$multitons[ $id ] ) )
				{
					if ( !$updateMultitonStoreIfExists )
					{
						return static::$multitons[ $id ];
					}
					$obj = static::$multitons[ $id ];
				}
			}
			
			/* Initiate an object */
			if ( !$obj )
			{
				$classname = get_called_class();
				$obj = new $classname;
				$obj->_new  = FALSE;
				$obj->_data = array();
			}
			 
			/* Import data */
			foreach ( $data as $k => $v )
			{
				if ( in_array( $k, array( 'perm_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' ) ) )
				{
					$obj->_permissions[ $k ] = $v;
				}
				else
				{
					if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
					{
						$k = \substr( $k, \strlen( static::$databasePrefix ) );
					}
		
					$obj->_data[ $k ] = $v;
				}
			}
			$obj->changed = array();
			$obj->_originalPermissions = $obj->_permissions;
			
			/* Init */
			if ( method_exists( $obj, 'init' ) )
			{
				$obj->init();
			}
			
			/* If it doesn't exist in the multiton store, set it */
			if( isset( static::$databaseColumnId ) and !isset( static::$multitons[ $id ] ) )
			{
				static::$multitons[ $id ] = $obj;
			}
					
			/* Return */
			return $obj;
		}
		else
		{
			return parent::constructFromData( $data, $updateMultitonStoreIfExists );
		}
	}

	/**
	 * Load and check permissions
	 *
	 * @param	mixed	$id		ID
	 * @param	string	$perm	Permission Key
	 * @return	static
	 * @throws	\OutOfRangeException
	 */
	public static function loadAndCheckPerms( $id, $perm='view' )
	{
		$obj = static::load( $id );
		
		if ( !$obj->can( $perm ) )
		{
			throw new \OutOfRangeException;
		}
		
		return $obj;
	}
	
	/**
	 * The permission key or function used when building a node selector
	 * in search or stream functions.
	 *
	 * @return string|callable function
	 */
	public static function searchableNodesPermission()
	{
		return 'view';
	}
	
	/**
	 * Return either NULL for no restrictions, or a list of container IDs we cannot search in because of app specific permissions and configuration
	 * You do not need to check for 'view' permissions against the logged in member here. The Query search class does this for you.
	 * This method is intended for more complex set up items, like needing to have X posts to see a forum, etc.
	 * This is used for search and the activity stream.
	 * We return a list of IDs and not node objects for memory efficiency.
	 *
	 * return 	null|array
	 */
	public static function unsearchableNodeIds()
	{
		return NULL;
	}
	
	/**
	 * Set the permission index permissions
	 *
	 * @param	array	$insert	Permission data to insert
	 * @param	object	\IPS\Helpers\Form\Matrix
	 * @return  void
	 */
	public function setPermissions( $insert, \IPS\Helpers\Form\Matrix $matrix )
	{
		/* Delete current rows */
		\IPS\Db::i()->delete( 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', static::$permApp, static::$permType, $this->_id ) );
		
		/* Insert */
		\IPS\Db::i()->insert( 'core_permission_index', $insert );
		
		/* Update tags permission cache */
		if ( isset( static::$permissionMap['read'] ) )
		{
			\IPS\Db::i()->update( 'core_tags_perms', array( 'tag_perm_text' => $insert[ 'perm_' . static::$permissionMap['read'] ] ), array( 'tag_perm_aap_lookup=?', md5( static::$permApp . ';' . static::$permType . ';' . $this->_id ) ) );
		}

		/* Make sure this object resets the permissions internally */
		$this->_permissions = NULL;
		$this->permissions();
				
		/* Update search index */
		$this->updateSearchIndexPermissions();
	}
	
	/**
	 * Update search index permissions
	 *
	 * @return  void
	 */
	protected function updateSearchIndexPermissions()
	{
		if ( isset( static::$contentItemClass ) )
		{
			$contentItemClass = static::$contentItemClass;
			if ( in_array( 'IPS\Content\Searchable', class_implements( $contentItemClass ) ) )
			{
				\IPS\Content\Search\Index::i()->massUpdate( $contentItemClass, $this->_id, NULL, $this->searchIndexPermissions() );
			}
			foreach ( array( 'commentClass', 'reviewClass' ) as $class )
			{
				if ( isset( $contentItemClass::$$class ) )
				{
					$className = $contentItemClass::$$class;
					if ( in_array( 'IPS\Content\Searchable', class_implements( $className ) ) )
					{
						\IPS\Content\Search\Index::i()->massUpdate( $className, $this->_id, NULL, $this->searchIndexPermissions() );
					}
				}
			}
		}
	}

	/**
	 * @brief	Cached canOnAny permission check
	 */
	protected static $_canOnAny	= array();

	/**
	 * Check permissions on any node
	 *
	 * For example - can be used to check if the user has
	 * permission to create content in any node to determine
	 * if there should be a "Submit" button
	 *
	 * @param	mixed								$permission		A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	\IPS\Member|\IPS\Member\Group|NULL	$member			The member or group to check (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public static function canOnAny( $permission, $member=NULL )
	{
		/* If this is not permission-dependant, return TRUE */
		if ( !in_array( 'IPS\Node\Permissions', class_implements( get_called_class() ) ) )
		{
			return TRUE;
		}
		
		/* Check it exists */
		if ( !isset( static::$permissionMap[ $permission ] ) )
		{
			throw \OutOfBoundsException;
		}

		/* Load member */
		if ( $member === NULL )
		{
			$member = \IPS\Member::loggedIn();
		}
		
		/* Restricted */
		if ( $member->restrict_post )
		{
			return FALSE;
		}

		$_key = md5( get_called_class() );

		/* Have we already cached the check? */
		if( isset( static::$_canOnAny[ $_key ] ) )
		{
			return static::$_canOnAny[ $_key ];
		}

		/* Return */
		$where = array(  );
		if ( static::$databaseColumnEnabledDisabled )
		{
			static::$_canOnAny[ $_key ]	= (bool) \IPS\Db::i()->select( 'COUNT(*)', static::$databaseTable, array( static::$databasePrefix . static::$databaseColumnEnabledDisabled . '=1 AND (' . \IPS\Db::i()->findInSet( 'core_permission_index.perm_' . static::$permissionMap[ $permission ], $member->groups ) . ' OR core_permission_index.perm_' . static::$permissionMap[ $permission ] . "='*' )" ) )
				->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id=" . static::$databaseTable . "." . static::$databasePrefix . static::$databaseColumnId, static::$permApp, static::$permType ) )
				->first();
		}
		else
		{
			static::$_canOnAny[ $_key ]	= (bool) \IPS\Db::i()->select( 'COUNT(*)', 'core_permission_index', array( 'core_permission_index.app=? AND core_permission_index.perm_type=? AND (' . \IPS\Db::i()->findInSet( 'core_permission_index.perm_' . static::$permissionMap[ $permission ], $member->groups ) . ' OR core_permission_index.perm_' . static::$permissionMap[ $permission ] . "='*' )", static::$permApp, static::$permType ) )->first();
		}

		return static::$_canOnAny[ $_key ];
	}
	
	/**
	 * Disabled permissions
	 * Allow node classes to define permissions that are unselectable in the permission matrix
	 *
	 * @return array	array( {group_id} => array( 'read', 'view', 'perm_7' );
	 */
	public function disabledPermissions()
	{
		return array();
	}
	
	/**
	 * Permission Types
	 *
	 * @return	array
	 */
	public function permissionTypes()
	{
		return static::$permissionMap;
	}
	
	/**
	 * Get permissions
	 *
	 * @return	array
	 */
	public function permissions()
	{
		if ( $this->_permissions === NULL )
		{
			try
			{
				$this->_permissions = \IPS\Db::i()->select( array( 'perm_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' ), 'core_permission_index', array( "app=? AND perm_type=? AND perm_type_id=?", static::$permApp, static::$permType, $this->_id ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				\IPS\Db::i()->insert( 'core_permission_index', array(
					'app'			=> static::$permApp,
					'perm_type'		=> static::$permType,
					'perm_type_id'	=> $this->_id,
					'perm_view'		=> ''
				) );
				return $this->permissions();
			}
		}
		return $this->_permissions;
	}
	
	/**
	 * Search Index Permissions
	 *
	 * @return	string	Comma-delimited values or '*'
	 * 	@li			Number indicates a group
	 *	@li			Number prepended by "m" indicates a member
	 *	@li			Number prepended by "s" indicates a social group
	 */
	public function searchIndexPermissions()
	{
		if( $this instanceof \IPS\Node\Permissions )
		{
			/* Compare both read and view */
			$result	= static::_getPermissions( $this );

			/* And then loop up the parents too... */
			foreach ( $this->parents() as $parent )
			{
				$parentResult = static::_getPermissions( $parent );

				if( $result == '*' )
				{
					$result	= $parentResult;
				}
				else if( $parentResult != '*' )
				{
					$result	= implode( ',', array_intersect( explode( ',', $result ), explode( ',', $parentResult ) ) );
				}
			}

			return $result;
        }
        return '*';
	}

	/**
	 * Retrieve the computed permissions
	 *
	 * @param	\IPS\Node\Model	$node	Node
	 * @return	string
	 */
	protected static function _getPermissions( $node )
	{
		$permissions = $node->permissions();
		$permissionTypes = $node->permissionTypes();

		/* Compare both read and view */

		if( !isset( $permissionTypes['read'] ) )
		{
			return $permissions[ 'perm_' . $permissionTypes['view'] ];
		}
		
		if( $permissions[ 'perm_' . $permissionTypes['view'] ] == '*' )
		{
			return $permissions[ 'perm_' . $permissionTypes['read'] ];
		}
		else if( $permissions[ 'perm_' . $permissionTypes['read'] ] == '*' )
		{
			return $permissions[ 'perm_' . $permissionTypes['view'] ];
		}
		else
		{
			return implode( ',', array_intersect( explode( ',', $permissions[ 'perm_' . $permissionTypes['view'] ] ), explode( ',', $permissions[ 'perm_' . $permissionTypes['read'] ] ) ) );
		}
	}

	/**
	 * Populate the Permission Matrix for the Permissions extension
	 *
	 * @param	array					Our current rows array we need to populate.
	 * @param	\IPS\Node\Model			The node to merge in.
	 * @param	\IPS\Member\Group|int	The group currently being adjusted.
	 * @param	array					Current permissions.
	 * @param	int						Our current depth level
	 * @return	array
	 * @throws
	 *	@li	BadMethodCallException
	 */
	public static function populatePermissionMatrix( &$rows, $node, $group, $current, $level=0 )
	{
		if ( !in_array( 'IPS\Node\Permissions', class_implements( $node ) ) )
		{
			throw new \BadMethodCallException;
		}
		
		$group = ( $group instanceof \IPS\Member\Group ) ? $group->g_id : $group;
		
		$rows[ $node->_id ] = array( '_level' => $level, 'label' => $node->_title );
		
		$disabledPermissions = $node->disabledPermissions();
		foreach( $node->permissionTypes() AS $k => $v )
		{
			$value = ( ( isset( $current[ $node->_id ] ) ) AND ( $current[ $node->_id ]['perm_' . $v ] === '*' OR in_array( $group, explode( ',', $current[ $node->_id ]['perm_' . $v ] ) ) ) );
			
			$disabled = FALSE;
			if ( array_key_exists( $group, $disabledPermissions ) and is_array( $disabledPermissions[ $group ] ) )
			{
				$disabled = in_array( $v, array_values( $disabledPermissions[ $group ] ) );
			}
			
			if ( $disabled === FALSE )
			{
				$disabled = ( $group == \IPS\Settings::i()->guest_group AND in_array( $k, array('review', 'rate' ) ) ) ? TRUE : FALSE;
			}
			
			if ( $disabled )
			{
				$value = NULL;
			}
			
			$rows[ $node->_id ] = array_merge( $rows[ $node->_id ], array( static::$permissionLangPrefix . 'perm__' . $k => $value ) );
		}
		
		if ( $node->hasChildren( NULL ) === TRUE )
		{
			$level++;
			foreach( $node->children( NULL ) AS $child )
			{
				static::populatePermissionMatrix( $rows, $child, $group, $current, $level );
			}
			$level--;
		}
	}
	
	/**
	 * Check permissions
	 *
	 * @param	mixed								$permission		A key which has a value in static::$permissionMap['view'] matching a column ID in core_permission_index
	 * @param	\IPS\Member|\IPS\Member\Group|NULL	$member			The member or group to check (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\OutOfBoundsException	If $permission does not exist in static::$permissionMap
	 */
	public function can( $permission, $member=NULL )
	{
		/* If it's disabled, return FALSE */
		if ( $this->_enabled === FALSE )
		{
			return FALSE;
		}
		
		/* If this is not permission-dependant, return TRUE */
		if ( !( $this instanceof \IPS\Node\Permissions ) )
		{
			return TRUE;
		}
		
		/* Check it exists */
		if ( !isset( static::$permissionMap[ $permission ] ) )
		{
			throw new \OutOfBoundsException;
		}

		/* Load member */
		if ( $member === NULL )
		{
			$member = \IPS\Member::loggedIn();
		}

		/* If this is an owned node, we don't have permission if we don't own it */
		if( static::$ownerTypes['member'] !== NULL AND in_array( $permission, array( 'add', 'edit', 'delete' ) ) )
		{
			if( $member instanceof \IPS\Member\Group )
			{
				return FALSE;
			}

			$column	= static::$ownerTypes['member'];

			if( $member->member_id !== $this->$column )
			{
				return FALSE;
			}
		}

		/* If we are checking view permissions, make sure we can view parent too */
		if( $permission == 'view' )
		{
			try
			{
				foreach( $this->parents() as $parent )
				{
					if( !$parent->can( $permission, $member ) )
					{
						return FALSE;
					}
				}
			}
			/* If parent or parents do not exist, we cannot view - happens sometimes with upgrades due to old bugs */
			catch( \OutOfRangeException $e )
			{
				return FALSE;
			}
		}
		
		/* If we're checking add permissions - make sure we are not over our posts per day limit */
		if ( in_array( $permission, array( 'add', 'reply', 'review' ) ) AND $member instanceof \IPS\Member )
		{
			if ( $member->checkPostsPerDay() === FALSE )
			{
				return FALSE;
			}
		}

		/* Return */
		$permissions = $this->permissions();

		if( $member instanceof \IPS\Member\Group )
		{
			return ( $permissions[ 'perm_' . static::$permissionMap[ $permission ] ] === '*' or ( $permissions[ 'perm_' . static::$permissionMap[ $permission ] ] and in_array( $member->g_id, explode( ',', $permissions[ 'perm_' . static::$permissionMap[ $permission ] ] ) ) ) );
		}
		else
		{
			return ( $permissions[ 'perm_' . static::$permissionMap[ $permission ] ] === '*' or ( $permissions[ 'perm_' . static::$permissionMap[ $permission ] ] and $member->inGroup( explode( ',', $permissions[ 'perm_' . static::$permissionMap[ $permission ] ] ) ) ) );
		}
	}

	/**
	 * @brief	Disable the copy button - useful when the forms are very distinctly different
	 */
	public $noCopyButton	= FALSE;

	/**
	 * [ActiveRecord] Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		parent::save();
		
		if ( $this instanceof \IPS\Node\Permissions and $this->_permissions !== NULL and $this->_permissions != $this->_originalPermissions )
		{
			if ( !isset( $this->_permissions['perm_id'] ) )
			{
				foreach ( array( 'app' => static::$permApp, 'perm_type' => static::$permType, 'perm_type_id' => $this->_id ) as $k => $v )
				{
					if ( !isset( $this->_permissions[ $k ] ) )
					{
						$this->_permissions[ $k ] = $v;
					}
				}
				
				\IPS\Db::i()->replace( 'core_permission_index', $this->_permissions );
			}
			else
			{
				\IPS\Db::i()->update( 'core_permission_index', $this->_permissions, array( 'perm_id=?', $this->_permissions['perm_id'] ) );
			}
		}
	}

	/**
	 * Mass move content items in this node to another node
	 *
	 * @param	\IPS\Node\Model|null	$node	New node to move content items to, or NULL to delete
	 * @param	array|null				$data	Additional filters to mass move by
	 * @return	NULL|int
	 */
	public function massMoveorDelete( $node=NULL, $data=NULL )
	{
		$contentItemClass = static::$contentItemClass;

		$where = array();
		if ( isset( $data['additional'] ) AND count( $data['additional'] ) )
		{
			if ( isset( $data['additional']['author'] ) )
			{
				$where[]			= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['author'] . '=?', $data['additional']['author'] );
			}
			
			if ( isset( $data['additional']['no_comments'] ) AND $data['additional']['no_comments'] > 0 )
			{
				$lastCommentField	= $contentItemClass::$databaseColumnMap['last_comment'];
				$field				= is_array( $lastCommentField ) ? array_pop( $lastCommentField ) : $lastCommentField;
				$where[]			= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['num_comments'] . '<=? AND ' . $contentItemClass::$databasePrefix . $field . '<?', $contentItemClass::$firstCommentRequired ? 1 : 0, $data['additional']['no_comments'] );
			}
			
			if ( isset( $data['additional']['num_comments'] ) AND $data['additional']['num_comments'] > 0 )
			{
				$where[]			= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['num_comments'].'<?', $data['additional']['num_comments'] );
			}
			
			if ( isset( $data['additional']['state'] ) )
			{
				if ( isset( $contentItemClass::$databaseColumnMap['locked'] ) )
				{
					$where[]		= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['locked'].'=?', $data['additional']['state'] == 'locked' ? 1 : 0 );
				}
				else
				{
					$where[]		= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['status'].'=?', $data['additional']['state'] == 'locked' ? 'closed' : 'open' );
				}
			}
			
			if ( isset( $data['additional']['pinned'] ) AND $data['additional']['pinned'] === TRUE )
			{
				$where[]			= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['pinned'].'!=?', 1 );
			}
			
			if ( isset( $data['additional']['featured'] ) AND $data['additional']['featured'] === TRUE )
			{
				$where[]			= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['featured'].'!=?', 1 );
			}
		}
		
		$select = $this->getContentItems( 100, 0, $where );
		
		if ( count( $select ) )
		{
			foreach ( $select as $item )
			{
				if ( $node )
				{
					$item->move( $node );
				}
				else
				{
					$item->delete();
				}
			}
			
			return 100;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Set the comment/approved/hidden counts
	 *
	 * @return void
	 */
	public function resetCommentCounts()
	{
		if ( !isset( static::$contentItemClass ) )
		{
			return false;
		}
		
		/* Update container */
		$itemClass 		 = static::$contentItemClass;
		$idColumn		 = static::$databaseColumnId;
		$itemIdColumn    = $itemClass::$databaseColumnId;
		$commentClass    = $itemClass::$commentClass;
		$commentIdColumn = $commentClass::$databaseColumnId;
		
		$containerWhere    = array( array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=?', $this->_id ) );
		$anyContainerWhere = $containerWhere;
		
		if ( in_array( 'IPS\Content\Hideable', class_implements( $itemClass ) ) )
		{
			if ( isset( $itemClass::$databaseColumnMap['approved'] ) )
			{
				$containerWhere[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['approved'] . '=?', 1 );
			}
			elseif ( isset( $itemClass::$databaseColumnMap['hidden'] ) )
			{
				$containerWhere[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['hidden'] . '=?', 0 );
			}
		}
		if ( $this->_items !== NULL )
		{
			$this->_items = \IPS\Db::i()->select( 'COUNT(*)', $itemClass::$databaseTable, $containerWhere )->first();
		}
		if ( $this->_comments !== NULL )
		{
			$commentWhere = array(
				array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . ' = ' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemIdColumn ),
				array( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=?', $this->_id ) 
			);

			if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
			{
				$commentWhere[] = array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=?', 1 );
			}
			elseif ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
			{
				$commentWhere[] = array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=?', 0 );
			}

			$this->_comments = \IPS\Db::i()->select( 'COUNT(*)', array(
					array( $commentClass::$databaseTable, $commentClass::$databaseTable ),
					array( $itemClass::$databaseTable, $itemClass::$databaseTable )
				), $commentWhere )->first();
		}
		
		if ( in_array( 'IPS\Content\Hideable', class_implements( $itemClass ) ) )
		{
			if ( $this->_unapprovedItems !== NULL )
			{
				$hiddenContainerWhere = array( array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=?', $this->_id ) );
				
				if ( isset( $itemClass::$databaseColumnMap['approved'] ) )
				{
					$hiddenContainerWhere[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['approved'] . '=?', 0 );
				}
				elseif ( isset( $itemClass::$databaseColumnMap['hidden'] ) )
				{
					$hiddenContainerWhere[] = array( $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['hidden'] . '=?', 1 );
				}
				
				$this->_unapprovedItems = \IPS\Db::i()->select( 'COUNT(*)', $itemClass::$databaseTable, $hiddenContainerWhere )->first();
			}
			
			$commentWhere = array( array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . ' = ' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemIdColumn ) );
			if ( $this->_unapprovedComments !== NULL )
			{
				if ( $itemClass::$firstCommentRequired )
				{
					/* Only look in non-hidden items otherwise this count will be added to */
					$commentWhere = array_merge( $commentWhere, $containerWhere );
				}
				else
				{
					$commentWhere = array_merge( $commentWhere, $anyContainerWhere );
				}
				
				if ( isset( $commentClass::$databaseColumnMap['approved'] ) )
				{
					$commentWhere[] = array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['approved'] . '=?', 0 );
				}
				elseif ( isset( $commentClass::$databaseColumnMap['hidden'] ) )
				{
					$commentWhere[] = array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['hidden'] . '=?', 1 );
				}
				
				$this->_unapprovedComments = \IPS\Db::i()->select( 'COUNT(*)', array(
					array( $commentClass::$databaseTable, $commentClass::$databaseTable ),
					array( $itemClass::$databaseTable, $itemClass::$databaseTable )
				), $commentWhere )->first();
			}
		}
	}
	
	/**
	 * Retrieve content item count (if applicable) for a node.
	 *
	 * @return	int|bool
	 */
	public function getContentItemCount()
	{
		if ( !isset( static::$contentItemClass ) )
		{
			return false;
		}
		
		$contentItemClass = static::$contentItemClass;
		$idColumn = static::$databaseColumnId;

		return (int) \IPS\Db::i()->select( 'COUNT(*)', $contentItemClass::$databaseTable, array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['container'] . '=?', $this->$idColumn ) )->first();
	}
	
	/**
	 * Retrieve content items (if applicable) for a node.
	 *
	 * @param	int	$limit	The limit
	 * @param	int	$offset	The offset
	 * @param	array $additionalWhere Additional where clauses
	 * @return	\IPS\Patterns\ActiveRecordIterator
	 * @throws	\BadMethodCallException
	 */
	public function getContentItems( $limit, $offset, $additionalWhere = array() )
	{
		if ( !isset( static::$contentItemClass ) )
		{
			throw new \BadMethodCallException;
		}
		
		$contentItemClass = static::$contentItemClass;
		
		$where		= array();
		$where[]	= array( $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['container'] . '=?', $this->_id );
		
		if ( count( $additionalWhere ) )
		{
			foreach( $additionalWhere AS $clause )
			{
				$where[] = $clause;
			}
		}
		
		$contentItemClass = static::$contentItemClass;
		$limit	= ( $offset !== NULL ) ? array( $offset, $limit ) : NULL;
		return new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', $contentItemClass::$databaseTable, $where, $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId, $limit ), $contentItemClass );
	}
	
	/**
	 * @brief Cached array of IDs a member has posted in
	 */
	protected $contentPostedIn = array();

	/**
	 * Retrieve an array of IDs a member has posted in.
	 *
	 * @param	\IPS\Member|NULL	$member	The member (NULL for currently logged in member)
	 * @param	array|NULL			$inSet	If supplied, checks will be restricted to only the ids provided
	 * @param   array|NULL          $additionalWhere    Additional where clause
	 * @param	array|NULL			$commentJoinWhere	Additional join clause for comments table
	 * @return	array				An array of content item ids
	 */
	public function contentPostedIn( $member=NULL, $inSet=NULL, $additionalWhere=NULL, $commentJoinWhere=NULL )
	{ 
		if ( $member === NULL )
		{
			$member = \IPS\Member::loggedIn();
		}

		if( !$member->member_id )
		{
			return array();
		}
		
		if ( !isset( static::$contentItemClass ) )
		{ 
			return array();
		}

		$_key	= md5( $member->member_id . json_encode( $inSet ) );

		if( isset( $this->contentPostedIn[ $_key ] ) )
		{
			return $this->contentPostedIn[ $_key ];
		}
		
		$contentItemClass	= static::$contentItemClass;
		$idColumn			= static::$databaseColumnId;
		
		if ( !$contentItemClass::$commentClass )
		{
			return array();
		}
		
		$commentClass = $contentItemClass::$commentClass;
		
		if ( $contentItemClass::$firstCommentRequired AND is_array( $inSet ) AND $additionalWhere === NULL AND $commentJoinWhere === NULL )
		{
			/* We can do this from one table */
			$contentItemClass = static::$contentItemClass;
			$commentClass     = $contentItemClass::$commentClass;
			
			$where	= array(
				array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . ' IN(' . implode( ',', $inSet ) . ')' ),
				array( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . '=? ', $member->member_id )
			);
		
			$items = \IPS\Db::i()->select( $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'], $commentClass::$databaseTable, $where);
			
			$ids = array();
			foreach( $items AS $item )
			{
				$ids[$item] = $item;
			}
		}
		else
		{
			$where = array();
		
			if ( $contentItemClass::$firstCommentRequired )
			{
				$where[] = array( '(' . $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . '=? )', $member->member_id );
			}
			else
			{
				$where[] = array( $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['container'] . '=?', $this->$idColumn );
				$where[] = array( '(' . $commentClass::$databaseTable . '.' . $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['author'] . '=? OR ' . $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnMap['author'] . '=?)', $member->member_id, $member->member_id );
			}
	
			/* Distinct will trigger a temporary table, so only use it if we need it */
			$distinct = \IPS\Db::SELECT_DISTINCT;
			
			if( is_array( $inSet ) AND count( $inSet ) )
			{
				$where[] = array( $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId . ' IN(' . implode( ',', $inSet ) . ')' );
	
				/* If we are already filtering by a specific set of ids, we don't need distinct */
				$distinct = NULL;
			}
	
			if ( $additionalWhere )
			{
				$where[] = $additionalWhere;
			}
	
			$joinClause = array( $commentClass::$databasePrefix . $commentClass::$databaseColumnMap['item'] . '=' . $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId );
	
			$items = \IPS\Db::i()->select( $contentItemClass::$databaseTable . '.' . $contentItemClass::$databasePrefix . $contentItemClass::$databaseColumnId, $contentItemClass::$databaseTable, $where, NULL, NULL, NULL, NULL, $distinct );
			$items->join( $commentClass::$databaseTable, ( $commentJoinWhere !== NULL ) ? array( $joinClause, $commentJoinWhere ) : $joinClause );
			
			$ids = array();
			foreach( $items AS $item )
			{
				$ids[$item] = $item;
			}
		}
		
		$this->contentPostedIn[ $_key ]	= $ids;
		
		return $ids;
	}

	/**
	 * Alter permissions for an individual group
	 *
	 * @param	int|\IPS\Member\Group	$group	Group to alter
	 * @param	array					$permissions	Array map of permission key => boolean value
	 * @return	void
	 */
	public function changePermissions( $group, $permissions )
	{
		/* Get our group ID */
		$groupId	= ( $group instanceof \IPS\Member\Group ) ? $group->g_id : (int) $group;

		/* Get all groups - we will need it to adjust permissions we are adding or taking away */
		$allGroups	= \IPS\Member\Group::groups();

		/* Set a flag so we know if we actually need to update anything later (i.e. in the search index) */
		$hasChange	= FALSE;

		/* Update permissions */
		foreach( $permissions as $permissionKey => $newValue )
		{
			if( !$this->_permissions )
			{
				$this->permissions();
			}

			$existing	= $this->_permissions[ 'perm_' . static::$permissionMap[ $permissionKey ] ];
			$updated	= array();

			/* Are we removing permission? */
			if( !$newValue )
			{
				if( $existing == '*' )
				{
					foreach( $allGroups as $_group )
					{
						if( $_group->g_id != $groupId )
						{
							$updated[]	= $_group->g_id;
						}
						else
						{
							/* This group was previously allowed and now it is not */
							$hasChange	= TRUE;
						}
					}
				}
				else if( $existing )
				{
					$existing	= explode( ',', $existing );

					foreach( $existing as $_existing )
					{
						if( $_existing != $groupId )
						{
							$updated[]	= $_existing;
						}
						else
						{
							/* This group was previously allowed and now it is not */
							$hasChange	= TRUE;
						}
					}
				}

				$updated	= implode( ',', $updated );
			}

			/* Or are we giving permission? */
			else
			{
				if( $existing != '*' )
				{
					$existing	= explode( ',', $existing );

					if( !in_array( $groupId, $existing ) )
					{
						/* This group was previously not allowed and now it is */
						$hasChange	= TRUE;
					}

					$updated	= array_unique( array_merge( $existing, array( $groupId ) ) );
					$updated	= ( count( $updated ) == count( $allGroups ) ) ? '*' : implode( ',', $updated );
				}
			}
			
			if( !is_array( $updated ) )
			{
				$this->_permissions[ 'perm_' . static::$permissionMap[ $permissionKey ] ]	= $updated;
			}
		}

		/* Save */
		$this->save();
		
		/* Update search index if anything has changed */
		if( $hasChange )
		{
			$this->updateSearchIndexPermissions();
		}
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		if ( $this instanceof \IPS\Node\Permissions )
		{
			$this->_permissions = \IPS\Db::i()->select( array( 'perm_id', 'perm_view', 'perm_2', 'perm_3', 'perm_4', 'perm_5', 'perm_6', 'perm_7' ), 'core_permission_index', array( "app=? AND perm_type=? AND perm_type_id=?", static::$permApp, static::$permType, $this->_id ) )->first();
			unset( $this->_permissions['perm_id'] );
		}

		$oldId = $this->_id;
		
		parent::__clone();
		
		if ( static::$titleLangPrefix )
		{
			\IPS\Lang::saveCustom( ( static::$permApp !== NULL ) ? static::$permApp : 'core', static::$titleLangPrefix . $this->_id, iterator_to_array( \IPS\Db::i()->select( 'CONCAT(word_custom, \' ' . \IPS\Member::loggedIn()->language()->get('copy_noun') . '\') as word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $oldId ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}
		elseif ( method_exists( $this, 'get__title' ) and method_exists( $this, 'set__title' ) )
		{
			$this->_title = $this->_title . ' ' . \IPS\Member::loggedIn()->language()->get('copy_noun');
		}

		if( isset( static::$descriptionLangSuffix ) )
		{
			\IPS\Lang::saveCustom( ( static::$permApp !== NULL ) ? static::$permApp : 'core', static::$titleLangPrefix . $this->_id . static::$descriptionLangSuffix, iterator_to_array( \IPS\Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$titleLangPrefix . $oldId . static::$descriptionLangSuffix ) )->setKeyField( 'lang_id' )->setValueField('word_custom') ) );
		}

		if( isset( static::$databaseColumnOrder ) )
		{
			$orderColumn = static::$databaseColumnOrder;
			$order = \IPS\Db::i()->select( array( "MAX( `" . static::$databasePrefix . static::$databaseColumnOrder . "` )" ), static::$databaseTable, array() )->first();
			$this->$orderColumn = $order + 1;
		}
		
		$this->_items = 0;
		$this->_comments = 0;
		$this->_reviews = 0;
		foreach ( array( 'Items', 'Comments', 'Reviews' ) as $k )
		{
			$k = "unapproved{$k}";
			if ( $this->$k !== NULL )
			{
				$this->$k = 0;
			}
		}
		$this->setLastComment();
		$this->setLastReview();
		$this->save();
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		if ( $this instanceof \IPS\Node\Permissions )
		{
			\IPS\Db::i()->delete( 'core_permission_index', array( "app=? AND perm_type=? AND perm_type_id=?", static::$permApp, static::$permType, $this->_id ) );
		}

		if ( $this instanceof \IPS\Node\Ratings )
		{
			\IPS\Db::i()->delete( 'core_ratings', array( "class=? AND item_id=?", get_called_class(), $this->_id ) );
		}

		\IPS\Db::i()->delete( 'core_follow', array( "follow_app=? AND follow_area=? AND follow_rel_id=?", static::$permApp, static::$permType, $this->_id ) );

		/* Delete lang strings */
		if ( static::$titleLangPrefix )
		{
			\IPS\Lang::deleteCustom( ( static::$permApp !== NULL ) ? static::$permApp : 'core', static::$titleLangPrefix . $this->_id );
		}

		if( isset( static::$descriptionLangSuffix ) )
		{
			\IPS\Lang::deleteCustom( ( static::$permApp !== NULL ) ? static::$permApp : 'core', static::$titleLangPrefix . $this->_id . static::$descriptionLangSuffix );
		}

		return parent::delete();
	}
	
	/**
	 * @brief	Cache for current follow data, used on "My Followed Content" screen
	 */
	public $_followData;

	/* !ACP forms */

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		return $values;
	}

	/**
	 * [Node] Save Add/Edit Form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function saveForm( $values )
	{		
		foreach ( $values as $k => $v )
		{
			if( $k == 'csrfKey' )
			{
				continue;
			}

			if ( isset( static::$databasePrefix ) and mb_substr( $k, 0, mb_strlen( static::$databasePrefix ) ) === static::$databasePrefix )
			{
				$k = mb_substr( $k, mb_strlen( static::$databasePrefix ) );
			}
			
			if ( is_array( $v ) )
			{
				/* Handle bitoptions */
				if( is_array( static::$bitOptions ) AND array_key_exists( $k, static::$bitOptions ) )
				{
					$options = $this->$k;
					foreach( $v as $_k => $_v )
					{
						$options[ $_k ]	= $_v;
					}
					$this->$k = $options;

					continue;
				}
				else if( !method_exists( $this, 'set_' . $k ) )
				{
					$v = implode( ',', $v );
				}
			}
			
			$this->$k = $v;
		}
													
		$this->save();
		$this->postSaveForm( $values );
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
	}
	
	/**
	 * Can a value be copied to this node?
	 *
	 * @return	bool
	 */
	public function canCopyValue( $key, $value )
	{
		if ( $key === static::$databasePrefix . static::$databaseColumnParent and $value )
		{
			if ( is_scalar( $value ) )
			{
				try
				{
					$value = static::load( $value );
				}
				catch ( \OutOfRangeException $e )
				{
					return TRUE;
				}
			}
			
			if ( $this->_id === $value->_id )
			{
				return FALSE;
			}
			
            foreach( $this->children( NULL ) as $obj )
            {
               if ( $obj->_id === $value->_id )
               {
	               return FALSE;
               }
            }
		}
		
		return TRUE;
	}

	/**
	 * Should we show the form to delete or move content?
	 *
	 * @return bool
	 */
	public function showDeleteOrMoveForm()
	{
		/* Do we have any children or content? */
		$hasContent = FALSE;
		if ( isset( static::$contentItemClass ) )
		{
			$hasContent	= $this->getContentItemCount();
		}
		else if ( method_exists( $this, 'getItemCount' ) )
		{
			$hasContent = $this->getItemCount();
		}

		return (bool) $hasContent;
	}

	/**
	 * Form to delete or move content
	 *
	 * @param	bool	$showMoveToChildren	If TRUE, will show "move to children" even if there are no children
	 * @return	\IPS\Helpers\Form
	 */
	public function deleteOrMoveForm( $showMoveToChildren=FALSE )
	{
		$hasContent = FALSE;
		if ( isset( static::$contentItemClass ) )
		{
			$hasContent	= (bool) $this->getContentItemCount();
		}
		
		$form = new \IPS\Helpers\Form( 'form', 'delete' );
		$form->addMessage( 'node_delete_blurb' );
		if ( $showMoveToChildren or $this->hasChildren( NULL, NULL, TRUE ) )
		{
			\IPS\Member::loggedIn()->language()->words['node_move_children'] = sprintf( \IPS\Member::loggedIn()->language()->get( 'node_move_children', FALSE ), \IPS\Member::loggedIn()->language()->addToStack( static::$nodeTitle, FALSE, array( 'strtolower' => TRUE) ) );
			$form->add( new \IPS\Helpers\Form\Node( 'node_move_children', 0, TRUE, array( 'class' => get_class( $this ), 'disabled' => array( $this->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'node_delete_children', 'subnodes' => FALSE ) ) );
		}
		if ( $hasContent )
		{
			$contentItemClass	= static::$contentItemClass;
			$form->add( new \IPS\Helpers\Form\Node( 'node_move_content', 0, TRUE, array( 'class' => get_class( $this ), 'disabled' => array( $this->_id ), 'disabledLang' => 'node_move_delete', 'zeroVal' => 'node_delete_content', 'subnodes' => FALSE, 'permissionCheck' => function( $node )
			{
				return array_key_exists( 'add', $node->permissionTypes() );
			} ) ) );
			
			if ( \IPS\Request::i()->deleteNode != 1 )
			{
				$form->add( new \IPS\Helpers\Form\Member( 'node_move_author', NULL, FALSE ) );
				
				if ( isset( $contentItemClass::$commentClass ) )
				{
					$form->add( new \IPS\Helpers\Form\Date( 'node_move_no_comments', NULL, FALSE ) );
					$form->add( new \IPS\Helpers\Form\Number( 'node_move_comments_less', NULL, FALSE ) );
				}
				
				if ( in_array( 'IPS\Content\Lockable', class_implements( $contentItemClass ) ) )
				{
					$form->add( new \IPS\Helpers\Form\Select( 'node_move_state', 'any', FALSE, array( 'options' => array( 'open' => 'open', 'locked' => 'locked', 'any' => 'any' ) ) ) );
				}
				
				if ( in_array( 'IPS\Content\Pinnable', class_implements( $contentItemClass ) ) )
				{
					$form->add( new \IPS\Helpers\Form\YesNo( 'node_move_not_pinned', FALSE, FALSE ) );
				}
				
				if ( in_array( 'IPS\Content\Featurable', class_implements( $contentItemClass ) ) )
				{
					$form->add( new \IPS\Helpers\Form\YesNo( 'node_move_not_featured', FALSE, FALSE ) );
				}
			}
		}
		
		return $form;
	}
	
	/**
	 * Handle submissions of form to delete or move content
	 *
	 * @param	array	$values			Values from form
	 * @param	bool	$deleteWhenDone	Delete the node when done?
	 * @return	void
	 */
	public function deleteOrMoveFormSubmit( $values, $deleteWhenDone=TRUE )
	{
		$nodesToQueue = array( $this );
				
		if ( isset( $values['node_move_children'] ) )
		{
			/* If we are moving children, we don't need to act on children of children as their parent reference should not change */
			if ( $values['node_move_children'] )
			{
				foreach ( $this->children( NULL ) as $child )
				{
					if ( $values['node_move_children'] )
					{
						$parentColumn = static::$databaseColumnParent;
						$child->$parentColumn = \IPS\Request::i()->node_move_children;
						$child->setLastComment();
						$child->setLastReview();
						$child->save();
					}
				}
			}
			/* However if we are deleting, we need to delete children of children (and their children, etc.) too */
			else
			{
				$nodeToCheck = $this;

				while( $nodeToCheck->hasChildren( NULL ) )
				{
					foreach ( $nodeToCheck->children( NULL ) as $nodeToCheck )
					{
						$nodesToQueue[] = $nodeToCheck;
					}
				}
			}
		}

		foreach ( $nodesToQueue as $_node )
		{
			if ( $deleteWhenDone and in_array( 'IPS\Node\Permissions', class_implements( $_node ) ) )
			{
				\IPS\Db::i()->update( 'core_permission_index', array( 'perm_view' => '' ), array( "app=? AND perm_type=? AND perm_type_id=?", $_node::$permApp, $_node::$permType, $_node->_id ) );
			}
			
			$additional = array();
			if ( !$deleteWhenDone )
			{
				if ( isset( $values['node_move_author'] ) AND $values['node_move_author'] instanceof \IPS\Member )
				{
					$additional['author'] = $values['node_move_author']->member_id;
				}
					
				if ( isset( $values['node_move_no_comments'] ) AND $values['node_move_no_comments'] instanceof \IPS\DateTime )
				{
					$additional['no_comments'] = $values['node_move_no_comments']->getTimestamp();
				}
					
				if ( isset( $values['node_move_comments_less'] ) AND $values['node_move_comments_less'] > 0 )
				{
					$additional['num_comments'] = $values['node_move_comments_less'];
				}
					
				if ( isset( $values['node_move_state'] ) AND $values['node_move_state'] != 'any' )
				{
					$additional['state'] = $values['node_move_state'];
				}
					
				if ( isset( $values['node_move_not_pinned'] ) AND $values['node_move_not_pinned'] )
				{
					$additional['pinned'] = TRUE;
				}
					
				if ( isset( $values['node_move_not_featured'] ) AND $values['node_move_not_featured'] )
				{
					$additional['featured'] = TRUE;
				}
			}
			
			if ( isset( $values['node_move_content'] ) and $values['node_move_content'] )
			{
				\IPS\Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => get_class( $_node ), 'id' => $_node->_id, 'moveToClass' => get_class( $values['node_move_content'] ), 'moveTo' => $values['node_move_content']->_id, 'deleteWhenDone' => $deleteWhenDone, 'additional' => $additional ) );
			}
			else
			{
				\IPS\Task::queue( 'core', 'DeleteOrMoveContent', array( 'class' => get_class( $_node ), 'id' => $_node->_id, 'deleteWhenDone' => $deleteWhenDone, 'additional' => $additional ) );
			}
		}
	}

	/**
	 * Is this node currently queued for deleting or moving content?
	 *
	 * @return	bool
	 */
	public function deleteOrMoveQueued()
	{
		/* If we already know, don't bother */
		if ( is_null( $this->queued ) )
		{
			$this->queued = FALSE;
			
			if( !isset( static::$contentItemClass ) )
			{
				return $this->queued;
			}
			
			foreach( \IPS\Db::i()->select( 'data', 'core_queue', array( 'app=? AND `key`=?', 'core', 'DeleteOrMoveContent' ) ) AS $row )
			{
				$data = json_decode( $row, TRUE );
				if ( $data['class'] === get_class( $this ) AND $data['id'] == $this->_id )
				{
					$this->queued = TRUE;
				}
			}
		}
		
		return $this->queued;
	}

	/**
	 * @brief	Flag for currently queued
	 */
	protected $queued = NULL;
	
	/* !Ratings */

	/**
	 * Can Rate?
	 *
	 * @param	\IPS\Member|NULL		$member		The member to check for (NULL for currently logged in member)
	 * @return	bool
	 * @throws	\BadMethodCallException
	 */
	public function canRate( \IPS\Member $member = NULL )
	{
		$member = $member ?: \IPS\Member::loggedIn();
		
		switch ( $member->group['g_topic_rate_setting'] )
		{
			case 2:
				return TRUE;
			case 1:
				try
				{
					$idColumn = static::$databaseColumnId;
					\IPS\Db::i()->select( '*', 'core_ratings', array( 'class=? AND item_id=? AND member=?', get_called_class(), $this->$idColumn, $member->member_id ) )->first();
					return FALSE;
				}
				catch ( \UnderflowException $e )
				{
					return TRUE;
				}
				break;
			default:
				return FALSE;
		}
	}

	/**
	 * Get average rating
	 *
	 * @return	int
	 * @throws	\BadMethodCallException
	 */
	public function averageRating()
	{
		if ( !( $this instanceof \IPS\Node\Ratings ) )
		{
			throw new \BadMethodCallException;
		}
				
		if ( isset( static::$ratingColumnMap['rating_average'] ) )
		{
			$column	= static::$ratingColumnMap['rating_average'];
			return $this->$column;
		}
		elseif ( isset( static::$ratingColumnMap['rating_total'] ) and isset( static::$ratingColumnMap['rating_hits'] ) )
		{
			$hits	= static::$ratingColumnMap['rating_hits'];
			$total	= static::$ratingColumnMap['rating_total'];
			return $this->$hits ? round( $this->$total / $this->$hits, 1 ) : 0;
		}
		else
		{
			$idColumn = static::$databaseColumnId;
			return round( \IPS\Db::i()->select( 'AVG(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first(), 1 );
		}
	}

	/**
	 * Display rating (will just display stars if member cannot rate)
	 *
	 * @return	string
	 * @throws	\BadMethodCallException
	 */
	public function rating()
	{
		if ( !( $this instanceof \IPS\Node\Ratings ) )
		{
			throw new \BadMethodCallException;
		}
		
		if ( $this->canRate() )
		{
			$idColumn = static::$databaseColumnId;
						
			$form = new \IPS\Helpers\Form('rating');
			$form->add( new \IPS\Helpers\Form\Rating( 'rating', $this->averageRating() ) );
			
			if ( $values = $form->values() )
			{
				\IPS\Db::i()->insert( 'core_ratings', array(
					'class'		=> get_called_class(),
					'item_id'	=> $this->$idColumn,
					'member'	=> \IPS\Member::loggedIn()->member_id,
					'rating'	=> $values['rating'],
					'ip'		=> \IPS\Request::i()->ipAddress()
				), TRUE );
				
				if ( isset( static::$ratingColumnMap['rating_average'] ) )
				{
					$column = static::$ratingColumnMap['rating_average'];
					$this->$column = round( \IPS\Db::i()->select( 'AVG(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first(), 1 );
				}
				if ( isset( static::$ratingColumnMap['rating_total'] ) )
				{
					$column = static::$ratingColumnMap['rating_total'];
					$this->$column = \IPS\Db::i()->select( 'SUM(rating)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first();
				}
				if ( isset( static::$ratingColumnMap['rating_hits'] ) )
				{
					$column = static::$ratingColumnMap['rating_hits'];
					$this->$column = \IPS\Db::i()->select( 'COUNT(*)', 'core_ratings', array( 'class=? AND item_id=?', get_called_class(), $this->$idColumn ) )->first();
				}

				$this->save();
				
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( 'OK' );
				}
			}
			
			return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'ratingTemplate' ) );
		}
		else
		{
			return \IPS\Theme::i()->getTemplate( 'global', 'core' )->rating( 'veryLarge', $this->averageRating() );
		}
	}
	
	/* !Tables */

	/**
	 * Get template for node tables
	 *
	 * @return	callable
	 */
	public static function nodeTableTemplate()
	{
		return array( \IPS\Theme::i()->getTemplate( 'tables', 'core' ), 'nodeRows' );
	}

	/**
	 * Get template for managing this nodes follows
	 *
	 * @return	callable
	 */
	public static function manageFollowNodeRow()
	{
		return array( \IPS\Theme::i()->getTemplate( 'tables', 'core' ), 'manageFollowNodeRow' );
	}		
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse	int			id			ID number
	 * @apiresponse	string		name		Name
	 * @apiresponse	string		url			URL
	 */
	public function apiOutput()
	{
		return array(
			'id'		=> $this->id,
			'name'		=> $this->_title,
			'url'		=> (string) $this->url()
		);
	}
}