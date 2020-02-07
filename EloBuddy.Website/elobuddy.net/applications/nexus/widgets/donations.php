<?php
/**
 * @brief		donations Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Apr 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * donations Widget
 */
class _donations extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'donations';
	
	/**
	 * @brief	App
	 */
	public $app = 'nexus';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Initialise this widget
	 *
	 * @return void
	 */ 
	public function init()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'donations.css', 'nexus' ) );
		return parent::init();
	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		return $this->output();
	}
}