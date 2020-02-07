<?php
/**
 * @brief		Topic Feed Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		16 Oct 2014
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
 * postFeed Widget
 */
class _postFeed extends \IPS\Content\WidgetComment
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'postFeed';
	
	/**
	 * @brief	App
	 */
	public $app = 'forums';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Class
	 */
	protected static $class = 'IPS\forums\Topic\Post';

	/**
	 * @brief	Moderator permission to generate caches on [optional]
	 */
	protected $moderatorPermissions	= array( 'can_view_hidden_content', 'can_view_hidden_post' );

	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere()
	{
		$where = parent::buildWhere();
		if ( !isset( $this->configuration['widget_feed_use_perms'] ) or $this->configuration['widget_feed_use_perms'] )
		{
			$where['container'][] = array( 'forums_forums.password IS NULL AND forums_forums.can_view_others=1' );
		}
		return $where;
	}
}