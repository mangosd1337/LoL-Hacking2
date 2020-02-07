<?php
/**
 * @brief		Stats Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Nov 2013
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
 * Stats Widget
 */
class _stats extends \IPS\Widget\StaticCache
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'stats';
	
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
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
 	{
 		if ( $form === null )
 		{
	 		$form = new \IPS\Helpers\Form;
 		} 

		$mostOnline = json_decode( \IPS\Settings::i()->most_online, TRUE );
		$form->add( new \IPS\Helpers\Form\Number( 'stats_most_online', $mostOnline['count'], TRUE ) );
		
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
 		if ( \IPS\Member::loggedIn()->isAdmin() and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_recount_content' ) )
 		{
 			$mostOnline = array( 'count' => $values['stats_most_online'], 'time' => time() );
 			\IPS\Settings::i()->most_online = json_encode( $mostOnline );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $mostOnline ) ), array( 'conf_key=?', 'most_online' ) );

			unset( $values['stats_most_online'] );
			unset( \IPS\Data\Store::i()->settings );

 			\IPS\Widget::deleteCaches( 'stats', 'core' );
 		}

 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$stats = array();
		$mostOnline = json_decode( \IPS\Settings::i()->most_online, TRUE );

		/* fetch only successful registered members ; if this needs to be changed, please review the other areas where we have the name<>? AND email<>? condition */
		$where = array( 'name<>? AND email<>?', '', '' );

		/* Member count */
		$stats['member_count'] = \IPS\Db::i()->select( 'COUNT(*)', 'core_members', $where )->first();
		
		/* Most online */
		$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_sessions', array( 'login_type<>? and running_time > ?', \IPS\Session\Front::LOGIN_TYPE_SPIDER, \IPS\DateTime::create()->sub( new \DateInterval( 'PT30M' ) )->getTimeStamp() ) )->first();
		if( $count > $mostOnline['count'] )
		{
			$mostOnline = array( 'count' => $count, 'time' => time() );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $mostOnline ) ), array( 'conf_key=?', 'most_online' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
		$stats['most_online'] = $mostOnline;
				
		/* Last Registered Member */
		$exclude = array();
		$where   = array( 'name IS NOT NULL AND name != \'\' AND temp_ban != -1' );
		foreach( \IPS\Member\Group::groups() as $id => $group )
		{
			if ( $group->g_hide_online_list )
			{
				$exclude[] = $group->g_id;
			}
		}
		if ( count( $exclude ) )
		{
			$where[] = '( ! ( ' . \IPS\Db::i()->in( 'member_group_id', $exclude ) . ' ) )';
		}
		$where[] = '( ! ' . \IPS\Db::i()->bitwiseWhere( \IPS\Member::$bitOptions['members_bitoptions'], 'bw_is_spammer' ) . ' )';
		$where[] = 'core_validating.new_reg IS NULL';
		try
		{
			$stats['last_registered'] = \IPS\Member::constructFromData( 
					\IPS\Db::i()->select( 'core_members.*, core_validating.new_reg', 'core_members', array( implode( ' AND ', $where ) ), 'core_members.member_id DESC', array( 0, 1 ) )->join(
							'core_validating',
							"core_validating.member_id=core_members.member_id"
						)->first() );
		}
		catch( \UnderflowException $ex )
		{
			$stats['last_registered'] = NULL;
		}
		
		/* Display */		
		return $this->output( $stats );
	}
}