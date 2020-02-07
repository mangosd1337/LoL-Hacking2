<?php
/**
 * @brief		Moderator Permissions Interface for Content Models
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		1 Oct 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Moderator Permissions Interface for Content Models
 */
class _ModeratorPermissions
{
	/**
	 * @brief	Actions
	 */
	public $actions = array();
	
	/**
	 * @brief	Comment Actions
	 */
	public $commentActions = array();
	
	/**
	 * @brief	Review Actions
	 */
	public $reviewActions = array();
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$class = static::$class;
		if ( in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			$this->actions[] = 'pin';
			$this->actions[] = 'unpin';
		}
		if ( in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			$this->actions[] = 'feature';
			$this->actions[] = 'unfeature';
		}
		$this->actions[] = 'edit';
		if ( in_array( 'IPS\Content\Hideable', class_implements( $class ) ) )
		{
			$this->actions[] = 'hide';
			$this->actions[] = 'unhide';
			$this->actions[] = 'view_hidden';
		}
		if ( isset( $class::$containerNodeClass ) )
		{
			$this->actions[] = 'move';
		}
		if ( in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			$this->actions[] = 'lock';
			$this->actions[] = 'unlock';
			$this->actions[] = 'reply_to_locked';
		}
		$this->actions[] = 'delete';
		
		if ( isset( $class::$commentClass ) )
		{
			$this->commentActions = array( 'edit' );
			if ( in_array( 'IPS\Content\Hideable', class_implements( $class::$commentClass ) ) )
			{
				$this->commentActions[] = 'hide';
				$this->commentActions[] = 'unhide';
				$this->commentActions[] = 'view_hidden';
			}
			$this->commentActions[] = 'delete';
		}
		
		if ( isset( $class::$reviewClass ) )
		{
			$this->reviewActions = array( 'edit' );
			if ( in_array( 'IPS\Content\Hideable', class_implements( $class::$reviewClass ) ) )
			{
				$this->reviewActions[] = 'hide';
				$this->reviewActions[] = 'unhide';
				$this->reviewActions[] = 'view_hidden';
			}
			$this->reviewActions[] = 'delete';
		}
	}
	
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
		$class = static::$class;
		$containerNodeClass = $class::$containerNodeClass;
		
		$return = array();
		
		$return[ $containerNodeClass::$modPerm ] = array( 'Node', array( 'class' => $containerNodeClass, 'zeroVal' => 'all', 'multiple' => TRUE ) );
		
		foreach ( $this->actions as $k )
		{
			$return[ "can_{$k}_{$class::$title}" ] = 'YesNo';
		}
		
		if ( isset( $class::$commentClass ) )
		{
			$commentClass = $class::$commentClass;
			foreach ( $this->commentActions as $k )
			{
				$return[ "can_{$k}_{$commentClass::$title}" ] = 'YesNo';
			}
		}
		
		if ( isset( $class::$reviewClass ) )
		{
			$reviewClass = $class::$reviewClass;
			foreach ( $this->reviewActions as $k )
			{
				$return[ "can_{$k}_{$reviewClass::$title}" ] = 'YesNo';
			}
		}
		
		return $return;
	}
}