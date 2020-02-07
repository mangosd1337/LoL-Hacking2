<?php
/**
 * @brief		Package Group Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Package Group
 */
class _Group extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_package_groups';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'pg_';
	
	/**
	 * @brief	[Node] Parent ID Database Column
	 */
	public static $databaseColumnParent = 'parent';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'menu__nexus_store_packages';
	
	/**
	 * @brief	[Node] Subnode class
	 */
	public static $subnodeClass = 'IPS\nexus\Package';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'nexus_pgroup_';
	
	/**
	 * @brief	[Node] Description suffix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}_{$descriptionLangSuffix}" as the key
	 */
	public static $descriptionLangSuffix = '_desc';
								
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
	protected static $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'store',
		'prefix'	=> 'packages_',
	);
	
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
		
		if ( isset( $qs['cat'] ) )
		{
			return static::load( $qs['cat'] );
		}
		
		throw new \InvalidArgumentException;
	}
		
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{		
		$form->add( new \IPS\Helpers\Form\Translatable( 'pg_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_pgroup_{$this->id}" : NULL ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'pg_desc', NULL, FALSE, array(
			'app'		=> 'nexus',
			'key'		=> ( $this->id ? "nexus_pgroup_{$this->id}_desc" : NULL ),
			'editor'	=> array(
				'app'			=> 'nexus',
				'key'			=> 'Admin',
				'autoSaveKey'	=> ( $this->id ? "nexus-group-{$this->id}" : "nexus-new-group" ),
				'attachIds'		=> $this->id ? array( $this->id, NULL, 'pgroup' ) : NULL, 'minimize' => 'pg_desc_placeholder'
			)
		) ) );

		$class = get_called_class();

		$form->add( new \IPS\Helpers\Form\Node( 'pg_parent', $this->id ? $this->parent : 0, TRUE, array( 'class' => 'IPS\nexus\Package\Group', 'subnodes' => FALSE, 'zeroVal' => 'no_parent', 'permissionCheck' => function( $node ) use ( $class )
		{
			if( isset( $class::$subnodeClass ) AND $class::$subnodeClass AND $node instanceof $class::$subnodeClass )
			{
				return FALSE;
			}

			return !isset( \IPS\Request::i()->id ) or ( $node->id != \IPS\Request::i()->id and !$node->isChildOf( $node::load( \IPS\Request::i()->id ) ) );
		} ) ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'pg_image', $this->image ? \IPS\File::get( 'nexus_PackageGroups', $this->image ) : NULL, FALSE, array( 'storageExtension' => 'nexus_PackageGroups', 'image' => TRUE ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['pg_parent'] ) )
		{
			$values['parent'] = $values['pg_parent'] ? $values['pg_parent']->id : 0;
		}

		if( isset( $values['pg_image'] ) )
		{
			$values['image'] = (string) $values['pg_image'];
		}
		
		if ( !$this->id )
		{
			$this->save();
			\IPS\File::claimAttachments( 'nexus-new-group', $this->id, NULL, 'pgroup', TRUE );
		}
		elseif( isset( $values['pg_name'] ) OR isset( $values['pg_desc'] ) )
		{
			$this->save();
		}
		
		if( isset( $values['pg_name'] ) )
		{
			\IPS\Lang::saveCustom( 'nexus', "nexus_pgroup_{$this->id}", $values['pg_name'] );
			unset( $values['pg_name'] );
		}

		if( isset( $values['pg_desc'] ) )
		{
			\IPS\Lang::saveCustom( 'nexus', "nexus_pgroup_{$this->id}_desc", $values['pg_desc'] );
			unset( $values['pg_desc'] );
		}

		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;

	/**
	 * Get URL
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		if( $this->_url === NULL )
		{
			$this->_url = \IPS\Http\Url::internal( "app=nexus&module=store&controller=store&cat={$this->id}", 'front', 'store_group', \IPS\Http\Url::seoTitle( \IPS\Member::loggedIn()->language()->get( 'nexus_pgroup_' . $this->id ) ) );
		}

		return $this->_url;
	}
	
	/**
	 * Get full image URL
	 *
	 * @return string
	 */
	public function get_image()
	{
		return ( isset( $this->_data['image'] ) ) ? (string) \IPS\File::get( 'nexus_Products', $this->_data['image'] )->url : NULL;
	}
	
	/**
	 * Does this group have subgroups?
	 *
	 * @param	mixed	$_where	Additional WHERE clause
	 * @return	bool
	 */
	public function hasSubgroups( $_where=array() )
	{
		return ( $this->childrenCount( NULL, NULL, FALSE, $_where ) > 0 );
	}
	
	/**
	 * Does this group have packages?
	 *
	 * @param	\IPS\Member|NULL|FALSE	$member	The member to perform the permission check for, or NULL for currently logged in member, or FALSE for no permission check
	 * @param	mixed					$_where	Additional WHERE clause
	 * @return	bool
	 */
	public function hasPackages( $member=NULL, $_where=array() )
	{
		return ( $this->childrenCount( $member === FALSE ? FALSE : 'view', $member, NULL, $_where ) > 0 );
	}

	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldImage = $this->image;

		parent::__clone();

		if ( $oldImage )
		{
			try
			{
				$icon = \IPS\File::get( 'nexus_PackageGroups', $oldImage );
				$newIcon = \IPS\File::create( 'nexus_PackageGroups', $icon->originalFilename, $icon->contents() );
				$this->image = (string) $newIcon;
			}
			catch ( \Exception $e )
			{
				$this->pg_image = NULL;
			}

			$this->save();
		}
	}


}