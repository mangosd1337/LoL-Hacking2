<?php
/**
 * @brief		Create Menu Extension : Topic
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		14 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\CreateMenu;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create Menu Extension: Topic
 */
class _Topic
{
	/**
	 * Get Items
	 *
	 * @return	array
	 */
	public function getItems()
	{
		if ( \IPS\forums\Forum::canOnAny( 'add' ) )
		{
			if ( $theOnlyForum = \IPS\forums\Forum::theOnlyForum() )
			{
				return array(
					'topic' => array(
						'link' 			=> $theOnlyForum->url()->setQueryString( 'do', 'add' ),
					)
				);
			}
			else
			{
				return array(
					'topic' => array(
						'link' 			=> \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums&do=createMenu", 'front', 'topic_create' ),
						'extraData'		=> array( 'data-ipsDialog' => true, 'data-ipsDialog-size' => "narrow" ),
						'title' 		=> 'select_forum'
					)
				);
			}
		}
		
		return array();
	}
}