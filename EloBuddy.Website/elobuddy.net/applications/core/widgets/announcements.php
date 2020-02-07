<?php
/**
 * @brief		Announcements Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Nov 2013
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
 * Announcements Widget
 */
class _announcements extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'announcements';
	
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
 		
		$form->add( new \IPS\Helpers\Form\Number( 'toshow', isset( $this->configuration['toshow'] ) ? $this->configuration['toshow'] : 5, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Select( 'sort_by', isset( $this->configuration['sort_by'] ) ? $this->configuration['sort_by'] : 'asc', TRUE, array( 'options' => array( 'asc' => 'ascending', 'desc' => 'descending' ) ) ) );

		return $form;
 	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$announcements = array();

		$where = array();
		$where[] = array( 'announce_active=?', 1 );
		$where[] = array( 'announce_start<? AND ( announce_end=0 OR announce_end>? )', time(), time() );
		
		$clauses = array();
		$params = array();
		foreach ( \IPS\Dispatcher::i()->application->extensions( 'core', 'Announcements' ) as $key => $extension )
		{
			$id = $extension::$idField;
			if ( isset( \IPS\Request::i()->$id ) )
			{
				if ( \IPS\Dispatcher::i()->dispatcherController instanceof \IPS\Content\Controller )
				{
					foreach( \IPS\Dispatcher::i()->application->extensions( 'core', 'ContentRouter' ) AS $k => $contentRouter )
					{
						foreach( $contentRouter->classes AS $class )
						{
							try
							{
								$clauses[] = '( announce_location=? AND ( ' . \IPS\Db::i()->findInSet( 'announce_ids', array( $class::load( \IPS\Request::i()->$id )->mapped('container') ) ) . ' OR announce_ids=? ) )';
								$params[] = $key;
								$params[] = '0';
							}
							catch( \OutOfRangeException $e ){}
							break 2;
						}
					}
				}
				else
				{
					$clauses[] = '( announce_location=? AND ( ' . \IPS\Db::i()->findInSet( 'announce_ids', array( \IPS\Request::i()->$id ) ) . ' OR announce_ids=? ) )';
					$params[] = $key;
					$params[] = '0';
				}
			}
			else
			{
				$clauses[] = '( announce_location=? AND announce_ids=? )';
				$params[] = $key;
				$params[] = '0';
			}
		}
		
		$where[] = array_merge( array( '( announce_app=? OR ( announce_app=?' . ( count( $clauses ) ? ( ' AND ( ' . implode( ' OR ', $clauses ) . ' ) ' ) : '' ) . ' ) )', '*',  \IPS\Dispatcher::i()->application->directory ), $params );
		
		$direction = isset( $this->configuration['sort_by'] ) ? $this->configuration['sort_by'] : 'asc';
		$limit     = isset( $this->configuration['toshow'] ) ? $this->configuration['toshow'] : 5;
				
		foreach( \IPS\Db::i()->select( '*' ,'core_announcements', $where, 'announce_start ' . $direction, array( 0, $limit ) ) as $row )
		{
			$announcements[] = \IPS\core\Announcements\Announcement::constructFromData($row);
		}
		
		if ( !count( $announcements ) )
		{
			return '';
		}

		return $this->output( $announcements );
	}
}