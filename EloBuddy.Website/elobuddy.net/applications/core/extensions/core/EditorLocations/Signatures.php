<?php
/**
 * @brief		Editor Extension: Signatures
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		07 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Signatures
 */
class _Signatures
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
	 * @param	\IPS\Member					$member	The member
	 * @param	\IPS\Helpers\Form\Editor	$field	The editor field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( $member, $field )
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
		return TRUE;
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return	\IPS\Http\Url|\IPS\Content|\IPS\Node\Model
	 * @throws	\LogicException
	 */
	public function attachmentLookup( $id1, $id2, $id3 )
	{
		return \IPS\Member::load( $id1 );
	}

	/**
	 * Rebuild attachment images in non-content item areas
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildAttachmentImages( $offset, $max )
	{
		return $this->performRebuild( $offset, $max, 'rebuildAttachmentUrls' );
	}

	/**
	 * Rebuild content post-upgrade
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildContent( $offset, $max )
	{
		return $this->performRebuild( $offset, $max, 'parseStatic' );
	}

	/**
	 * Perform rebuild - abstracted as the call for rebuildContent() and rebuildAttachmentImages() is nearly identical
	 *
	 * @param	int|null	$offset		Offset to start from
	 * @param	int|null	$max		Maximum to parse
	 * @param	callable	$callback	Method to call to rebuild content
	 * @return	int			Number completed
	 */
	protected function performRebuild( $offset, $max, $callback )
	{
		$did	 = 0;
		$rebuilt = FALSE;
		
		foreach( \IPS\Db::i()->select( '*', 'core_members', "signature IS NOT NULL AND signature != ''", 'member_id ASC', array( $offset, $max ) ) as $member )
		{
			$did++;

			/* Update */
			try
			{
				if( $callback == 'rebuildAttachmentUrls' )
				{
					$rebuilt = \IPS\Text\Parser::rebuildAttachmentUrls( $member['signature'], \IPS\Member::load( $member['member_id'] ) );
				}
				elseif( $callback == 'parseStatic' )
				{
					$rebuilt = \IPS\Text\LegacyParser::parseStatic( $member['signature'], \IPS\Member::load( $member['member_id'] ), FALSE, 'core_Signatures', $member['member_id'] );
				}
			}
			catch( \InvalidArgumentException $e )
			{
				if( $callback == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $member['signature'] );
				}
				else
				{
					throw $e;
				}
			}

			if( $rebuilt !== FALSE )
			{
				\IPS\Db::i()->update( 'core_members', array( 'signature' => $rebuilt ), array( 'member_id=?', $member['member_id'] ) );
			}
		}
		
		return $did;
	}

	/**
	 * Total content count to be used in progress indicator
	 *
	 * @return	int			Total Count
	 */
	public function contentCount()
	{
		return \IPS\Db::i()->select( 'COUNT(*) as count', 'core_members', "signature IS NOT NULL AND signature != ''" )->first();
	}
}