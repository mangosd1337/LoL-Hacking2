<?php
/**
 * @brief		poll Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		21 Jul 2015
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
 * poll Widget
 */
class _poll extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'poll';
	
	/**
	 * @brief	App
	 */
	public $app = 'forums';
		
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
		// Use this to perform any set up and to assign a template that is not in the following format:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
		// If you are creating a plugin, uncomment this line:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'plugins', 'core', 'global' ), $this->key ) );
		// And then create your template at located at plugins/<your plugin>/dev/html/poll.phtml
		
		
		parent::init();
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
 		if ( $form === null )
		{
	 		$form = new \IPS\Helpers\Form;
 		}

 		$form->add( new \IPS\Helpers\Form\Item( 'widget_poll_tid', ( isset( $this->configuration['widget_poll_tid'] ) ? $this->configuration['widget_poll_tid'] : NULL ), TRUE, array(
		    'class'     => '\IPS\forums\Topic',
		    'maxItems'  => 1,
		    'where'     => array( array( 'poll_state IS NOT NULL' ) )
	    ) ) );

		return $form;
 	} 
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( $values )
 	{
	    $item = array_pop( $values['widget_poll_tid'] );
	    $values['widget_poll_tid'] = $item->tid;
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		if ( empty( $this->configuration['widget_poll_tid'] ) )
		{
			return '';
		}

		try
		{
			$topic = \IPS\forums\Topic::loadAndCheckPerms( $this->configuration['widget_poll_tid'] );
			$poll  = $topic->getPoll();
			$poll->displayTemplate = array( \IPS\Theme::i()->getTemplate( 'widgets', 'forums', 'front' ), 'pollWidget' );
			$poll->url = $topic->url();

			return $this->output( $topic, $poll );
		}
		catch( \Exception $ex )
		{
			return '';
		}
	}
}