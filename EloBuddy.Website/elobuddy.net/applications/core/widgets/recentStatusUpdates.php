<?php
/**
 * @brief		recentStatusUpdates Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		04 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * recentStatusUpdates Widget
 */
class _recentStatusUpdates extends \IPS\Widget\PermissionCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'recentStatusUpdates';
	
	/**
	 * @brief	App
	 */
	public $app = 'core';
		
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
		if ( !\IPS\Settings::i()->profile_comments )
		{
			return '';
		}

		if( !\IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'status' ) ) )
		{
			return '';
		}
		
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_core.js', 'core' ) );
		
		$statuses = new \IPS\Patterns\ActiveRecordIterator(
			\IPS\Db::i()->select(
					'*',
					'core_member_status_updates',
					array( 'status_approved = 1' ),
					'status_date DESC',
					array( 0, isset( $this->configuration['number_to_show'] ) ? $this->configuration['number_to_show'] : 5  )
			),
			'\IPS\core\Statuses\Status'
		);

		return $this->output( $statuses );
	}
}