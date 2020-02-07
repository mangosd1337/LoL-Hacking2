<?php
/**
 * @brief		Moderator Permissions: Content
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\ModeratorPermissions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions: Content
 */
class _ContentGenerator extends \IPS\Content\ExtensionGenerator
{
	/**
	 * @brief	If TRUE, will prevent comment classes being included
	 */
	protected static $contentItemsOnly = TRUE;
			
	/**
	 * Get Permissions
	 *
	 * @param	array	$toggles	Toggle data
	 * @code
	 	return array(
	 		'key'	=> 'YesNo',	// Can just return a string with type
	 		'key'	=> array(	// Or an array for more options
	 			'YesNo',			// Type
	 			array( ... ),		// Options (as defined by type's class)
	 			'prefix',			// Prefix
	 			'suffix',			// Suffix
	 		),
	 		...
	 	);
	 * @endcode
	 * @return	array
	 */
	public function getPermissions( $toggles )
	{
		/* Init */
		$class = $this->class;
		$return = array();

		/* Node selector */
		if ( isset( $class::$containerNodeClass ) )
		{
			$containerNodeClass = $class::$containerNodeClass;
			
			if ( isset( $containerNodeClass::$modPerm ) )
			{
				$return[ $containerNodeClass::$modPerm ] = array( 'Node', array( 'class' => $containerNodeClass, 'zeroVal' => 'all', 'multiple' => TRUE, 'forceOwner' => FALSE ) );
			}
		}

		/* Content items */
		if ( in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			$return[ "can_pin_{$class::$title}" ] = 'YesNo';
			$return[ "can_unpin_{$class::$title}" ] = 'YesNo';
		}
		if ( in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			$return[ "can_feature_{$class::$title}" ] = 'YesNo';
			$return[ "can_unfeature_{$class::$title}" ] = 'YesNo';
		}
		$return[ "can_edit_{$class::$title}" ] = 'YesNo';
		if ( in_array( 'IPS\Content\Hideable', class_implements( $class ) ) )
		{
			$return[ "can_hide_{$class::$title}" ] = 'YesNo';
			$return[ "can_unhide_{$class::$title}" ] = 'YesNo';
			$return[ "can_view_hidden_{$class::$title}" ] = 'YesNo';
		}
		if ( in_array( 'IPS\Content\FuturePublishing', class_implements( $class ) ) )
		{
			$return[ "can_future_publish_{$class::$title}" ] = 'YesNo';
			$return[ "can_view_future_{$class::$title}" ] = 'YesNo';
		}
		if ( isset( $class::$containerNodeClass ) )
		{
			$return[ "can_move_{$class::$title}" ] = 'YesNo';
		}
		if ( in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			$return[ "can_lock_{$class::$title}" ] = 'YesNo';
			$return[ "can_unlock_{$class::$title}" ] = 'YesNo';
			$return[ "can_reply_to_locked_{$class::$title}" ] = 'YesNo';
		}
		$return[ "can_delete_{$class::$title}" ] = 'YesNo';
		if ( $class::$firstCommentRequired )
		{
			$return[ "can_split_merge_{$class::$title}" ] = 'YesNo';
		}
		
		/* Comments */
		if ( isset( $class::$commentClass ) )
		{
			$commentClass = $class::$commentClass;
			$return[ "can_edit_{$commentClass::$title}" ] = 'YesNo';
			if ( in_array( 'IPS\Content\Hideable', class_implements( $class::$commentClass ) ) )
			{
				$return[ "can_hide_{$commentClass::$title}" ] = 'YesNo';
				$return[ "can_unhide_{$commentClass::$title}" ] = 'YesNo';
				$return[ "can_view_hidden_{$commentClass::$title}" ] = 'YesNo';
			}
			$return[ "can_delete_{$commentClass::$title}" ] = 'YesNo';
		}
		
		/* Reviews */
		if ( isset( $class::$reviewClass ) )
		{
			$reviewClass = $class::$reviewClass;
			$return[ "can_edit_{$reviewClass::$title}" ] = 'YesNo';
			if ( in_array( 'IPS\Content\Hideable', class_implements( $class::$reviewClass ) ) )
			{
				$return[ "can_hide_{$reviewClass::$title}" ] = 'YesNo';
				$return[ "can_unhide_{$reviewClass::$title}" ] = 'YesNo';
				$return[ "can_view_hidden_{$reviewClass::$title}" ] = 'YesNo';
			}
			$return[ "can_delete_{$reviewClass::$title}" ] = 'YesNo';
		}
		
		/* Other */
		foreach ( \IPS\Application::load( $class::$application )->extensions( 'core', 'ContentModeratorPermissions' ) as $ext )
		{
			$return = array_merge( $return, $ext->getPermissions( $toggles ) );
		}
		
		/* Return */
		return $return;
	}
	
	/**
	 * After change
	 *
	 * @param	array	$moderator	The moderator
	 * @param	array	$changed	Values that were changed
	 * @return	void
	 */
	public function onChange( $moderator, $changed )
	{
		$class = $this->class;
		foreach ( \IPS\Application::load( $class::$application )->extensions( 'core', 'ContentModeratorPermissions' ) as $ext )
		{
			$ext->onChange( $moderator, $changed );
		}
	}
	
	/**
	 * After delete
	 *
	 * @param	array	$moderator	The moderator
	 * @return	void
	 */
	public function onDelete( $moderator )
	{
		$class = $this->class;
		foreach ( \IPS\Application::load( $class::$application )->extensions( 'core', 'ContentModeratorPermissions' ) as $ext )
		{
			$ext->onDelete( $moderator );
		}
	}
}