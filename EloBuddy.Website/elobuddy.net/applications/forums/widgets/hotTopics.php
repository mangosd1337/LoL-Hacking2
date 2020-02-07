<?php
/**
 * @brief		hotTopics Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		2 Sep 2014
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
 * hotTopics Widget
 */
class _hotTopics extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'hotTopics';
	
	/**
	 * @brief	App
	 */
	public $app = 'forums';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';

	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
 		if ( $form === null )
		{
	 		$form = new \IPS\Helpers\Form;
 		}

 		$form->add( new \IPS\Helpers\Form\Number( 'number_to_show', isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5, TRUE ) );
 		
 		return $form;
 	} 

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$topics = \IPS\forums\Topic::getItemsWithPermission( array( array( 'popular_time > ? AND forums_forums.password IS NULL AND forums_forums.can_view_others=1', time() ) ), NULL, isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5, 'read', \IPS\Content\Hideable::FILTER_PUBLIC_ONLY );
		if ( count( $topics ) )
		{
			return $this->output( $topics );
		}
		else
		{
			return '';
		}
	}
}