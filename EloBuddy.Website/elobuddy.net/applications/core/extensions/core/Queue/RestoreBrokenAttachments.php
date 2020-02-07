<?php
/**
 * @brief		Background Task: Restore broken attachments
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Jun 2014
 * @version		SVN_VERSION_NUMBER
 * @note		Early betas/RCs of 4.0.0 may have caused attachment maps to break, this will restore them.
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Restore broken attachments
 */
class _RestoreBrokenAttachments
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= 50;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$classname = $data['class'];

		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new \OutOfRangeException;
		}

		\IPS\Log::debug( "Getting preQueueData for " . $classname, 'RestoreBrokenAttachments' );

		try
		{			
			$data['count']		= \IPS\Db::i()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, 
				array( $classname::$databasePrefix . $classname::$databaseColumnMap['content'] . " LIKE '%attachment%' OR " . $classname::$databasePrefix . $classname::$databaseColumnMap['content'] . " LIKE '%ipsAttach%'" ) )->first();

			$data['realCount']	= \IPS\Db::i()->select( 'COUNT(*)', $classname::$databaseTable, 
				array( $classname::$databasePrefix . $classname::$databaseColumnMap['content'] . " LIKE '%attachment%' OR " . $classname::$databasePrefix . $classname::$databaseColumnMap['content'] . " LIKE '%ipsAttach%'" ) )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}

		\IPS\Log::debug( "PreQueue count for " . $classname . " is " . $data['count'], 'RestoreBrokenAttachments' );

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;
		
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
	public function run( &$data, $offset )
	{
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}

		/* Make sure there's even content to parse */
		if( !isset( $classname::$databaseColumnMap['content'] ) )
		{
			throw new \OutOfRangeException;
		}

		\IPS\Log::debug( "Running " . $classname . ", with an offset of " . $offset, 'RestoreBrokenAttachments' );
		
		$select   = \IPS\Db::i()->select( '*', $classname::$databaseTable, array( array( $classname::$databasePrefix . $classname::$databaseColumnId . ' > ? AND (' . $classname::$databasePrefix . $classname::$databaseColumnMap['content'] . " LIKE '%attachment%' OR " . $classname::$databasePrefix . $classname::$databaseColumnMap['content'] . " LIKE '%ipsAttach%')", $offset ) ), $classname::$databasePrefix . $classname::$databaseColumnId . ' ASC', array( 0, $this->rebuild ) );
		$iterator = new \IPS\Patterns\ActiveRecordIterator( $select, $classname );
		$last     = NULL;
		
		foreach( $iterator as $item )
		{
			if( isset( $classname::$itemClass ) )
			{
				$itemClass		= $classname::$itemClass;
				$module			= ucfirst( $itemClass::$module );
				$itemIdColumn	= $itemClass::$databaseColumnId;
				$idColumn		= $classname::$databaseColumnId;

				$id1			= $item->item()->$itemIdColumn;
				$id2			= $item->$idColumn;
			}
			else
			{
				$module			= $classname::$module;
				$idColumn		= $classname::$databaseColumnId;
				$id1			= $item->$idColumn;
				$id2			= 0;
			}

			/* Set the area */
			$this->area = $classname::$application . '_' . ucfirst( $module );
			$this->idOne	= $id1;
			$this->idTwo	= $id2;

			/* Get the existing attachments map */
			$this->existingAttachments = iterator_to_array( \IPS\Db::i()->select( '*', 'core_attachments_map', array( array( 'location_key=?', $this->area ), array( 'id1=?', $id1 ), array( 'id2=?', $id2 ) ) )->setKeyField( 'attachment_id' ) );
			$this->mappedAttachments = array_keys( $this->existingAttachments );

			$contentColumn	= $classname::$databaseColumnMap['content'];

			try
			{
				/* Initiate a DOMDocument, force it to use UTF-8 */
				libxml_use_internal_errors(TRUE);
				$source = new \DOMDocument( '1.0', 'UTF-8' );
				@$source->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $item->$contentColumn );

				/* Loop */
				foreach ( $source->childNodes as $node )
				{				
					if ( $node instanceof \DOMElement )
					{
						$this->parseNode( $node );
					}
				}
			}
			catch( \InvalidArgumentException $e ){}

			$item->save();

			$last = $item->$idColumn;
			$data['indexed']++;
		}

		return $last;
	}

	/**
	 * @brief	Area for attachment mapping
	 */
	public $area	= NULL;

	/**
	 * @brief	ID1 for attachment mapping
	 */
	public $idOne	= NULL;

	/**
	 * @brief	ID2 for attachment mapping
	 */
	public $idTwo	= NULL;
	
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
		$class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !\IPS\Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \OutOfRangeException;
		}
		
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_stuff', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}

	/**
	 * @brief	Mapped attachments
	 */
	public $mappedAttachments = array();

	/**
	 * @brief	Existing attachments
	 */
	protected $existingAttachments = array();

	/**
	 * Parse Node
	 *
	 * @param	\DOMElement			$node		The node to parse
	 * @return	bool				If the node contains any contents
	 */
	protected function parseNode( $node )
	{
		$added = FALSE;

		/* Is this a text node? */
		if ( $node instanceof \DOMElement )
		{	
			/* Is it a link to an attachment? */
			if ( ( $node->tagName === 'a' and preg_match( '#^' . preg_quote( rtrim( \IPS\Settings::i()->base_url, '/' ), '#' ) . '/applications/core/interface/file/attachment\.php\?id=(\d+)$#', $node->getAttribute('href'), $matches ) ) or ( $node->tagName === 'img' and $matches[1] = $node->getAttribute('data-fileid') ) )
			{
				if ( isset( $matches[1] ) )
				{
					try
					{
						$attachment = \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $matches[1] ) )->first();
						
						if ( !in_array( $attachment['attach_id'], $this->mappedAttachments ) )
						{
							\IPS\Db::i()->replace( 'core_attachments_map', array(
								'attachment_id'	=> $attachment['attach_id'],
								'location_key'	=> $this->area,
								'id1'			=> $this->idOne,
								'id2'			=> $this->idTwo ? $this->idTwo : NULL,
								'id3'			=> NULL,
								'temp'			=> NULL
							) );
							
							$this->mappedAttachments[] = $attachment['attach_id'];
						}
						
						$added = TRUE;
					}
					catch ( \UnderflowException $e ) { }
				}
			}

			/* Loop children */
			if ( $node->hasChildNodes() )
			{
				foreach ( $node->childNodes as $child )
				{
					if ( $this->parseNode( $child ) )
					{
						$added = TRUE;
					}
				}
			}
		}

		return $added;
	}
}