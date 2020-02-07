<?php
/**
 * @brief		forumStatistics Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		27 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * forumStatistics Widget
 */
class _forumStatistics extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'forumStatistics';
	
	/**
	 * @brief	App
	 */
	public $app = 'forums';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$stats = array();
		
		$stats['total_posts']	= \IPS\Db::i()->select( "COUNT(*)", 'forums_posts', array( 'queued = ?', 0 ) )->first();
		
		if ( \IPS\Settings::i()->archive_on )
		{
			$stats['total_posts'] += \IPS\forums\Topic\ArchivedPost::db()->select( 'COUNT(*)', 'forums_archive_posts', array( 'archive_queued = ?', 0 ) )->first();
		}
		
		$stats['total_topics']	= \IPS\Db::i()->select( "COUNT(*)", 'forums_topics', array( 'approved = ?', 1 ) )->first();
		
		return $this->output( $stats );
	}
}