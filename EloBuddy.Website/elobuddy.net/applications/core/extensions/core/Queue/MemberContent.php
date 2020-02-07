<?php
/**
 * @brief		Background Task: Perform actions on all a member's content
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Delete or move content
 */
class _MemberContent
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|NULL
	 */
	public function preQueueData( $data )
	{
		$classname = $data['class'];
		
		/* Check the app is enabled */
		if ( ! \IPS\Application::appIsEnabled( $classname::$application ) )
		{
			return NULL;
		}
		
		/* Check the app supports what we're doing */
		if ( !$data['member_id'] and !isset( $classname::$databaseColumnMap['author_name'] ) )
		{
			return NULL;
		}
		if ( $data['action'] == 'hide' and !in_array( 'IPS\Content\Hideable', class_implements( $classname ) ) )
		{
			return NULL;
		}
		
		/* Get count */
		$data['originalCount'] = \IPS\Db::i()->select( 'COUNT(*)', $classname::$databaseTable, static::_getWhere( $data ) )->first();
		if ( !$data['originalCount'] )
		{
			return NULL;
		}
		
		return $data;
	}
	
	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int|null				New offset or NULL if complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		$select = \IPS\Db::i()->select( '*', $classname::$databaseTable, static::_getWhere( $data ), $classname::$databasePrefix.$classname::$databaseColumnId, array( 0, 100 ) );
		if ( count( $select ) )
		{
			foreach ( new \IPS\Patterns\ActiveRecordIterator( $select, $classname ) as $item )
			{
				/* If this is the first comment on an item where a first comment is required (e.g. posts) do nothing, as when we get to the item, that will handle it */
				if ( $item instanceof \IPS\Content\Comment )
				{
					$itemClass = $item::$itemClass;
					if ( $itemClass::$firstCommentRequired and $item->isFirst() )
					{
						continue;
					}
				}
				
				/* Do the action... */
				try
				{
					switch ( $data['action'] )
					{
						case 'hide':
							$item->hide( isset( $data['initiated_by_member_id'] ) ? \IPS\Member::load( $data['initiated_by_member_id'] ) : NULL );
							break;
							
						case 'delete':
							$item->delete( isset( $data['initiated_by_member_id'] ) ? \IPS\Member::load( $data['initiated_by_member_id'] ) : NULL );
							break;
						
						case 'merge':
							$item->changeAuthor( \IPS\Member::load( $data['merge_with_id'] ) );
							break;
					}
				}
				catch( \ErrorException $e )
				{
					
				}
			}
			
			return ( $offset + 100 < $data['originalCount'] ) ? $offset + 100 : NULL;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		$member = \IPS\Member::load( $data['member_id'] );
		if ( $member->member_id )
		{
			$member = $member->link();
		}
		else
		{
			$member = $data['name'];
		}
				
		$text = \IPS\Member::loggedIn()->language()->addToStack( 'backgroundQueue_membercontent_' . $data['action'], FALSE, array( 'htmlsprintf' => array( $member, \IPS\Member::loggedIn()->language()->addToStack( $classname::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) ) ) ) );
		
		return array( 'text' => $text, 'complete' => $data['originalCount'] ? ( round( 100 / $data['originalCount'] * $offset, 2 ) ) : 100 );
	}
	
	/**
	 * Get where clause
	 *
	 * @param	array	$data
	 * @return	array
	 */
	protected static function _getWhere( $data )
	{
		$classname = $data['class'];
		$where = array( array( $classname::$databasePrefix . $classname::$databaseColumnMap['author'] . '=?', $data['member_id'] ) );
		
		if ( !$data['member_id'] )
		{
			$where = array( $classname::$databasePrefix . $classname::$databaseColumnMap['author_name'] . '=?', $data['name'] );
		}
		
		if ( $data['action'] == 'hide' )
		{
			if ( isset( $classname::$databaseColumnMap['approved'] ) )
			{
				$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['approved'] . '=?', 1 );
			}
			else
			{
				$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['hidden'] . '=?', 0 );
			}
		}
		
		return $where;
	}
}