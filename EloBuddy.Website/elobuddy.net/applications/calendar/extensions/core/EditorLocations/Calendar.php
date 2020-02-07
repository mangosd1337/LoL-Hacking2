<?php
/**
 * @brief		Editor Extension: Event text
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		7 Jan 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Calendar event text and comments
 */
class _Calendar
{
	/**
	 * Can we use HTML in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canUseHtml( $member )
	{
		return NULL;
	}
	
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( $member )
	{
		return NULL;
	}

	/**
	 * Permission check for attachments
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @return	bool
	 */
	public function attachmentPermissionCheck( $member, $id1, $id2, $id3, $attachment )
	{
		try
		{
			$event = \IPS\calendar\Event::load( $id1 );
			return $event->canView( $member );
		}
		catch ( \OutOfRangeException $e )
		{
			return FALSE;
		}
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	\IPS\calendar\Event\Review|\IPS\calendar\Event\Comment|\IPS\calendar\Event
	 * @throws	\LogicException
	 */
	public function attachmentLookup( $id1, $id2, $id3 )
	{
		if ( $id2 )
		{
			if ( $id3 === 'review' )
			{
				return \IPS\calendar\Event\Review::load( $id2 );
			}
			else
			{
				return \IPS\calendar\Event\Comment::load( $id2 );
			}
		}
		else
		{
			return \IPS\calendar\Event::load( $id1 );
		}
	}
}