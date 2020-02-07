<?php
/**
 * @brief		Editor Extension: Admin
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Admin
 */
class _Admin
{
	/**
	 * Can we use HTML in this editor?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canUseHtml( $member )
	{
		return TRUE;
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
	 * @param	\IPS\Member	$member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @return	bool
	 */
	public function attachmentPermissionCheck( $member, $id1, $id2, $id3, $attachment )
	{
		try
		{
			switch ( $id3 )
			{				
				case 'pkg':
				case 'pkg-assoc':
					return \IPS\nexus\Package\Item::load( $id1 )->canView( $member );
					
				case 'pkg-pg':
					$customer = \IPS\nexus\Customer::load( $member->member_id );
					if ( count( \IPS\nexus\extensions\nexus\Item\Package::getPurchases( $customer, $id1, FALSE ) ) )
					{
						$options = array( 'type' => 'attach', 'id' => $attachment['attach_id'], 'name' => $attachment['attach_file'] );
						if ( isset( $_SERVER['HTTP_REFERER'] ) )
						{
							try
							{
								$purchase = \IPS\nexus\Purchase::loadFromUrl( new \IPS\Http\Url( $_SERVER['HTTP_REFERER'] ) );
								$options['ps_id'] = $purchase->id;
								$options['ps_name'] = $purchase->name;
							}
							catch ( \LogicException $e ) { }
						}
						
						$customer->log( 'download', $options );
						return TRUE;
					}
					else
					{
						return FALSE;
					}
					
				case 'invoice-header';
				case 'invoice-footer':
				case 'pgroup':
					return TRUE;
				
				case 'network_status_text':
					return (bool) \IPS\Settings::i()->network_status;
			};
		}
		catch ( \OutOfRangeException $e )
		{
			return FALSE;
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
		switch ( $id3 )
		{
			case 'pkg':
			case 'pkg-assoc':
			case 'pkg-pg':
				return \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&subnode=1&do=form&id={$id1}", 'admin' );
				
			case 'pgroup':
				return \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=form&id={$id1}", 'admin' );
				
			case 'invoice-header';
			case 'invoice-footer':
				return \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=invoices&do=settings', 'admin' );
				
			case 'network_status_text':
				return \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=networkStatus', 'front', 'nexus_network_status' );
		};
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
		return $this->performRebuild( $offset, $max, array( 'IPS\Text\Parser', 'rebuildAttachmentUrls' ) );
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
	 * Perform rebuild - abstracted as the call for rebuildContent() and rebuildAttachmentImages() is nearly identical
	 *
	 * @param	int|null	$offset		Offset to start from
	 * @param	int|null	$max		Maximum to parse
	 * @param	callable	$callback	Method to call to rebuild content
	 * @return	int			Number completed
	 */
	protected function performRebuild( $offset, $max, $callback )
	{
		/* We will do everything except pages first */
		if( !$offset )
		{
			/* Language bits */
			foreach( \IPS\Db::i()->select( '*', 'core_sys_lang_words', \IPS\Db::i()->in( 'word_key', array( 'nexus_com_rules_val', 'network_status_text_val' ) ) . " OR word_key LIKE 'nexus_donategoal_%_desc' OR word_key LIKE 'nexus_gateway_%_ins' OR word_key LIKE 'nexus_pgroup_%_desc' OR word_key LIKE 'nexus_package_%_desc' OR word_key LIKE 'nexus_package_%_page' OR word_key LIKE 'nexus_department_%_desc'", 'word_id ASC', array( $offset, $max ) ) as $word )
			{
				try
				{
					$rebuilt	= call_user_func( $callback, $word['word_custom'] );
				}
				catch( \InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
						$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $word['word_custom'] );
					}
					else
					{
						throw $e;
					}
				}

				if( $rebuilt !== FALSE )
				{
					\IPS\Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => $rebuilt ), 'word_id=' . $word['word_id'] );
				}
			}
			
			/* Settings */
			foreach ( array( 'nexus_invoice_header', 'nexus_invoice_footer' ) as $k )
			{
				try
				{
					$newMessage = call_user_func( $callback, \IPS\Settings::i()->$k );
				}
				catch( \InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
						$newMessage	= preg_replace( "#\[/?([^\]]+?)\]#", '', \IPS\Settings::i()->$k );
					}
					else
					{
						throw $e;
					}
				}
	
				if( $newMessage !== FALSE )
				{
					\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $newMessage ), array( "conf_key=?", $k ) );
					unset( \IPS\Data\Store::i()->settings );
				}
			}
		}

		/* Now do packages */
		$did	= 0;

		foreach( \IPS\Db::i()->select( '*', 'nexus_packages', null, 'p_id ASC', array( $offset, $max ) ) as $package )
		{
			$did++;

			/* Update */
			try
			{
				$rebuilt	= call_user_func( $callback, $package['p_page'] );
			}
			catch( \InvalidArgumentException $e )
			{
				if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $package['p_page'] );
				}
				else
				{
					throw $e;
				}
			}

			if( $rebuilt !== FALSE )
			{
				\IPS\Db::i()->update( 'nexus_packages', array( 'p_page' => $rebuilt ), array( 'p_id=?', $package['p_id'] ) );
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
		$count	= 2;

		$count	+= \IPS\Db::i()->select( 'COUNT(*) as count', 'core_sys_lang_words', \IPS\Db::i()->in( 'word_key', array( 'nexus_com_rules_val', 'network_status_text_val' ) ) . " OR word_key LIKE 'nexus_donategoal_%_desc' OR word_key LIKE 'nexus_gateway_%_ins' OR word_key LIKE 'nexus_pgroup_%_desc' OR word_key LIKE 'nexus_package_%_desc' OR word_key LIKE 'nexus_package_%_page' OR word_key LIKE 'nexus_department_%_desc'" )->first();

		$count	+= \IPS\Db::i()->select( 'COUNT(*) as count', 'nexus_packages' )->first();

		return $count;
	}
}