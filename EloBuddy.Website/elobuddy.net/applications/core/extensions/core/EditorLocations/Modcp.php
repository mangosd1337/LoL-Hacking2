<?php
/**
 * @brief		Editor Extension: Mod CP
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		17 Jul 2013
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
 * Editor Extension: Mod CP
 */
class _Modcp
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
		if ( $member->modPermission( 'mod_see_warn' ) )
		{
			return TRUE;
		}
		elseif ( $id3 === 'member' )
		{
			$warning = \IPS\core\Warnings\Warning::load( $id1 );
			return $member->member_id === $warning->member;
		}
		
		return FALSE;
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
		return \IPS\core\Warnings\Warning::load( $id1 );
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
		return $this->performRebuild( $offset, $max, array( 'IPS\Text\LegacyParser', 'parseStatic' ) );
	}

	/**
	 * Rebuild attachment images in non-content item areas
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 */
	public function rebuildAttachmentImages( $offset, $max )
	{
		return $this->performRebuild( $offset, $max, array( 'IPS\Text\Parser', 'rebuildAttachmentUrls' ) );
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
        $did	= 0;

        foreach( \IPS\Db::i()->select( '*', 'core_members_warn_logs', NULL, 'wl_id ASC', array( $offset, $max ) ) as $log )
        {
            $did++;
            
            $update = array();
            foreach ( array( 'wl_note_member', 'wl_note_mods' ) as $k )
            {
	            if ( $log[ $k ] )
	            {
		            try
		            {
						if( $callback[1] == 'rebuildAttachmentUrls' )
						{
							$update[ $k ] = \IPS\Text\Parser::rebuildAttachmentUrls( $log[ $k ], \IPS\Member::load( $log['wl_moderator'] ) );
						}
						elseif( $callback[1] == 'parseStatic' )
						{
							$update[ $k ] = call_user_func( $callback, $log[ $k ], \IPS\Member::load( $log['wl_moderator'] ), FALSE, 'core_Modcp', $log['wl_id'], NULL, ( $k == 'wl_note_member' ? 'member' : 'mod' ) );
						}
		            }
		            catch( \InvalidArgumentException $e )
		            {
		                if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
		                {
		                    $update[ $k ] = preg_replace( "#\[/?([^\]]+?)\]#", '', $log[ $k ] );
		                }
		                else
		                {
		                    throw $e;
		                }
		            }
				}
			}

            if( count( $update ) )
            {
                \IPS\Db::i()->update( 'core_members_warn_logs', $update, array( 'wl_id=?', $log['wl_id'] ) );
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
		return \IPS\Db::i()->select( 'COUNT(*)', 'core_members_warn_logs' )->first();
	}
}