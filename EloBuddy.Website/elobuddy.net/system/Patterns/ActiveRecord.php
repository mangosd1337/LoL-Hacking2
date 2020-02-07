<?php
/**
 * @brief		Active Record Pattern
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Patterns;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Active Record Pattern
 */
abstract class _ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = '';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';

	/**
	 * @brief	[ActiveRecord] Database table
	 * @note	This MUST be over-ridden
	 */
	public static $databaseTable	= '';
		
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array();
	
	/**
	 * @brief	Bitwise keys
	 */
	protected static $bitOptions = array();

	/**
	 * @brief	[ActiveRecord] Multiton Store
	 * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
	 */
	protected static $multitons	= array();
	
	/**
	 * @brief	[ActiveRecord] Database Connection
	 */
	public static function db()
	{
		return \IPS\Db::i();
	}
	
	/**
	 * Load Record
	 *
	 * @see		\IPS\Db::build
	 * @param	int|string	$id					ID
	 * @param	string		$idField			The database column that the $id parameter pertains to (NULL will use static::$databaseColumnId)
	 * @param	mixed		$extraWhereClause	Additional where clause(s) (see \IPS\Db::build for details)
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function load( $id, $idField=NULL, $extraWhereClause=NULL )
	{
		/* If we didn't specify an ID field, assume the default */
		if( $idField === NULL )
		{
			$idField = static::$databasePrefix . static::$databaseColumnId;
		}
		
		/* If we did, check it's valid */
		elseif( !in_array( $idField, static::$databaseIdFields ) )
		{
			throw new \InvalidArgumentException;
		}
				
		/* Does that exist in the multiton store? */
		if( $idField === static::$databasePrefix . static::$databaseColumnId and !empty( static::$multitons[ $id ] ) )
		{
			return static::$multitons[ $id ];
		}
		
		/* If not, find it */
		else
		{
			/* Load it */
			try
			{
				$row = static::constructLoadQuery( $id, $idField, $extraWhereClause )->first();
			}
			catch ( \UnderflowException $e )
			{
				throw new \OutOfRangeException;
			}
			
			/* If it doesn't exist in the multiton store, set it */
			if( !isset( static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ] ) )
			{
				static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ] = static::constructFromData( $row );
			}
			
			/* And return it */
			return static::$multitons[ $row[ static::$databasePrefix . static::$databaseColumnId ] ];
		}
	}

	/**
	 * Load record based on a URL
	 *
	 * @param	\IPS\Http\Url	$url	URL to load from
	 * @return	static
	 * @throws	\InvalidArgumentException
	 * @throws	\OutOfRangeException
	 */
	public static function loadFromUrl( \IPS\Http\Url $url )
	{
		$qs = array_merge( $url->queryString, $url->getFriendlyUrlData() );
		
		if ( isset( $qs['id'] ) )
		{
			return static::load( $qs['id'] );
		}
		
		throw new \InvalidArgumentException;
	}

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
		$where = array( array( '`' . $idField . '`=?', $id ) );
		if( $extraWhereClause !== NULL )
		{
			if ( !is_array( $extraWhereClause ) or !is_array( $extraWhereClause[0] ) )
			{
				$extraWhereClause = array( $extraWhereClause );
			}
			$where = array_merge( $where, $extraWhereClause );
		}
		
		return static::db()->select( '*', static::$databaseTable, $where );
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
			if( static::$databasePrefix AND mb_strpos( $k, static::$databasePrefix ) === 0 )
			{
				$k = \substr( $k, \strlen( static::$databasePrefix ) );
			}

			$obj->_data[ $k ] = $v;
		}
		
		$obj->changed = array();
		
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
	
	/**
	 * Get which IDs are already loaded
	 *
	 * @return	array
	 */
	public static function multitonIds()
	{
		if ( is_array( static::$multitons ) )
		{
			return array_keys( static::$multitons );
		}
		return array();
	}
	
	/**
	 * @brief	Data Store
	 */
	protected $_data = array();
	
	/**
	 * @brief	Is new record?
	 */
	protected $_new = TRUE;
		
	/**
	 * @brief	Changed Columns
	 */
	public $changed = array();
	
	/**
	 * Constructor - Create a blank object with default values
	 *
	 * @return	void
	 */
	public function __construct()
	{						
		$this->setDefaultValues();
	}
	
	/**
	 * Set Default Values (overriding $defaultValues)
	 *
	 * @return	void
	 */
	protected function setDefaultValues()
	{
		
	} 
		
	/**
	 * Get value from data store
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( $key )
	{
		if( method_exists( $this, 'get_'.$key ) )
		{
			return call_user_func( array( $this, 'get_'.$key ) );
		}
		elseif( isset( $this->_data[ $key ] ) or isset( static::$bitOptions[ $key ] ) )
		{
			if ( isset( static::$bitOptions[ $key ] ) )
			{
				if ( !isset( $this->_data[ $key ] ) or !( $this->_data[ $key ] instanceof Bitwise ) )
				{
					$values = array();
					foreach ( static::$bitOptions[ $key ] as $k => $map )
					{
						$values[ $k ] = isset( $this->_data[ $k ] ) ? $this->_data[ $k ] : 0;
					}
					$this->_data[ $key ] = new Bitwise( $values, static::$bitOptions[ $key ], method_exists( $this, "setBitwise_{$key}" ) ? array( $this, "setBitwise_{$key}" ) : NULL );
				}
			}
			return $this->_data[ $key ];
		}
				
		return NULL;
	}
	
	/**
	 * Set value in data store
	 *
	 * @see		\IPS\Patterns\ActiveRecord::save
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( $key, $value )
	{
		if( method_exists( $this, 'set_'.$key ) )
		{
			$oldValues = $this->_data;
			
			call_user_func( array( $this, 'set_'.$key ), $value );
						
			foreach( $this->_data as $k => $v )
			{				
				if( !array_key_exists( $k, $oldValues ) or ( $v instanceof \IPS\Patterns\Bitwise and !( $oldValues[ $k ] instanceof \IPS\Patterns\Bitwise ) ) or $oldValues[ $k ] !== $v )
				{
					$this->changed[ $k ]	= $v;
				}
			}
			
			unset( $oldValues );
		}
		else
		{
			if ( !array_key_exists( $key, $this->_data ) or $this->_data[ $key ] !== $value )
			{
				$this->changed[ $key ] = $value;
			}
			
			$this->_data[ $key ] = $value;
		}
	}
	
	/**
	 * Is value in data store?
	 *
	 * @param	mixed	$key	Key
	 * @return	bool
	 */
	public function __isset( $key )
	{
		return isset( $this->_data[ $key ] );
	}
	
	/**
	 * @brief	By default cloning will create a new ActiveRecord record, but if you truly want an object copy you can set this to TRUE first and a direct copy will be returned
	 */
	public $skipCloneDuplication	= FALSE;

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

		$primaryKey = static::$databaseColumnId;
		$this->$primaryKey = NULL;
		
		$this->_new = TRUE;
		$this->save();
	}
	
	/**
	 * Save Changed Columns
	 *
	 * @return	void
	 */
	public function save()
	{
		if ( $this->_new )
		{
			$data = $this->_data;
		}
		else
		{
			$data = $this->changed;
		}

		foreach ( array_keys( static::$bitOptions ) as $k )
		{			
			if ( $this->$k instanceof Bitwise )
			{
				foreach( $this->$k->values as $field => $value )
				{ 
					if ( isset( $data[ $field ] ) or $this->$k->originalValues[ $field ] != intval( $value ) )
					{
						$data[ $field ] = intval( $value );
					}
				}
			}
		}

		if ( $this->_new )
		{
			$insert = array();
			if( static::$databasePrefix === NULL )
			{
				$insert = $data;
			}
			else
			{
				$insert = array();
				foreach ( $data as $k => $v )
				{
					$insert[ static::$databasePrefix . $k ] = $v;
				}
			}
			
			$insertId = static::db()->insert( static::$databaseTable, $insert );
			
			$primaryKey = static::$databaseColumnId;
			if ( $this->$primaryKey === NULL and $insertId )
			{
				$this->$primaryKey = $insertId;
			}
			
			$this->_new = FALSE;

			static::$multitons[ $this->$primaryKey ] = $this;
		}
		elseif( !empty( $data ) )
		{
			/* Set the column names with a prefix */
			if( static::$databasePrefix === NULL )
			{
				$update = $data;
			}
			else
			{
				$update = array();

				foreach ( $data as $k => $v )
				{
					$update[ static::$databasePrefix . $k ] = $v;
				}
			}
						
			/* Work out the ID */
			$idColumn = static::$databaseColumnId;

			/* Save */
			static::db()->update( static::$databaseTable, $update, array( static::$databasePrefix . $idColumn . '=?', $this->$idColumn ) );
			
			/* Reset our log of what's changed */
			$this->changed = array();
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		$idColumn = static::$databaseColumnId;
		static::db()->delete( static::$databaseTable, array( static::$databasePrefix . $idColumn . '=?', $this->$idColumn ) );
	}
	
	/**
	 * Get follow data
	 *
	 * @param	string					$area			Area
	 * @param	int						$id				ID
	 * @param	int						$privacy		static::FOLLOW_PUBLIC + static::FOLLOW_ANONYMOUS
	 * @param	array					$frequencyTypes	array( 'immediate', 'daily', 'weekly' )
	 * @param	\IPS\DateTime|int|NULL	$date			Only users who started following before this date will be returned. NULL for no restriction
	 * @param	int|array				$limit			LIMIT clause
	 * @param	string					$order			Column to order by
	 * @param	int						$flags			Flags to pass to select (e.g. \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS)
	 * @return	\IPS\Db\Select
	 * @throws	|\BadMethodCallException
	 */
	protected static function _followers( $area, $id, $privacy, $frequencyTypes, $date, $limit, $order=NULL, $flags=\IPS\Db::SELECT_SQL_CALC_FOUND_ROWS )
	{
		/* Initial where clause */
		$where[] = array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', static::$application, $area, $id );
	
		/* Public / Anonymous */
		if ( !( $privacy & static::FOLLOW_PUBLIC ) )
		{
			$where[] = array( 'follow_is_anon=1' );
		}
		elseif ( !( $privacy & static::FOLLOW_ANONYMOUS ) )
		{
			$where[] = array( 'follow_is_anon=0' );
		}
	
		/* Specific type */
		if ( $frequencyTypes != array( 'immediate', 'daily', 'weekly' ) )
		{
			$where[] = array( \IPS\Db::i()->in( 'follow_notify_freq', $frequencyTypes ) );
		}
		
		/* Since */
		if( $date !== NULL )
		{
			$where[] = array( 'follow_added<?', ( $date instanceof \IPS\DateTime ) ? $date->getTimestamp() : intval( $date ) );
		}

		/* Cache the results as this may be called multiple times in one page load */
		static $cache	= array();
		$_hash			= md5( json_encode( $where ) . $order . json_encode( $limit ) . $flags );

		if( isset( $cache[ $_hash ] ) )
		{
			return $cache[ $_hash ];
		}
	
		/* Get */
		if ( $order === 'name' )
		{
			$cache[ $_hash ]	= \IPS\Db::i()->select( 'core_follow.*, core_members.name', 'core_follow', $where, 'name ASC', $limit, NULL, NULL, $flags )->join( 'core_members', array( 'core_members.member_id=core_follow.follow_member_id' ) );
		}
		else
		{
			$cache[ $_hash ]	= \IPS\Db::i()->select( 'core_follow.*', 'core_follow', $where, $order, $limit, NULL, NULL, $flags );
		}

		return $cache[ $_hash ];
	}
	
	/**
	 * Cover Photo
	 *
	 * @return	\IPS\Helpers\CoverPhoto
	 */
	public function coverPhoto()
	{
		$photoCol = static::$databaseColumnMap[ 'cover_photo' ];
		$photoOffset = static::$databaseColumnMap[ 'cover_photo_offset' ];
		$photo = new \IPS\Helpers\CoverPhoto;
		if ( isset( static::$databaseColumnMap['cover_photo'] ) and $this->$photoCol )
		{
			$photo->file = \IPS\File::get( static::$coverPhotoStorageExtension, $this->$photoCol );
			$photo->offset = $this->$photoOffset;
		}
		$photo->editable = $this->canEdit();
		$photo->object = $this;
	
		return $photo;
	}
}