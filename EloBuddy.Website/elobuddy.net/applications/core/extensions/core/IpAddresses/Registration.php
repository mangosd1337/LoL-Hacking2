<?php
/**
 * @brief		IP Address Lookup: Registration
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Registration
 */
class _Registration
{
	/** 
	 * Find Records by IP
	 *
	 * @param	string			$ip			The IP Address
	 * @param	\IPS\Http\Url	$baseUrl	URL table will be displayed on or NULL to return a count
	 * @return	\IPS\Helpers\Table|int|null
	 */
	public function findByIp( $ip, \IPS\Http\Url $baseUrl = NULL )
	{
		/* Return count */
		if ( $baseUrl === NULL )
		{
			return \IPS\Db::i()->select( 'COUNT(*)', 'core_members', array( "ip_address LIKE CONCAT( ?, '%' )", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new \IPS\Helpers\Table\Db( 'core_members', $baseUrl, array( "ip_address LIKE CONCAT( ?, '%' )", $ip ) );
		$table->langPrefix = 'members_';
				
		/* Columns we need */
		$table->include = array( 'photo', 'name', 'email', 'joined', 'member_group_id' );
		$table->mainColumn = 'name';
		$table->noSort	= array( 'photo' );
		
		if( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'front' )
		{
			$table->include = array_merge( array( 'member_id' ), $table->include );
			$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'modcp', 'core', 'front' ), 'memberManagementRow' );
		}
				
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'joined';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'photo'				=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate( 'global', 'core' )->userPhoto( \IPS\Member::constructFromData( $row ), 'mini' );
			},
			'joined'			=> function( $val, $row )
			{
				return \IPS\DateTime::ts( $val )->localeDate();
			},
			'member_group_id'	=> function( $val, $row )
			{
				return \IPS\Member\Group::load( $val )->formattedName;
			}
		);
		
		/* Buttons */
		$table->rowButtons = function( $row )
		{
            $member = \IPS\Member::load( $row['member_id'] );
			return array(
				'edit'	=> array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'front' ) ? $member->url()->setQueryString( array( 'do' => 'edit' ) ) : $member->acpUrl(),
				),
			);
		};
		
		/* Return */
		return (string) $table;
	}
	
	/**
	 * Find IPs by Member
	 *
	 * @code
	 	return array(
	 		'::1' => array(
	 			'ip'		=> '::1'// string (IP Address)
		 		'count'		=> ...	// int (number of times this member has used this IP)
		 		'first'		=> ... 	// int (timestamp of first use)
		 		'last'		=> ... 	// int (timestamp of most recent use)
		 	),
		 	...
	 	);
	 * @endcode
	 * @param	\IPS\Member	$member	The member
	 * @return	array
	 */
	public function findByMember( $member )
	{
		return \IPS\Db::i()->select( 'ip_address AS ip, 1 AS count, joined AS first, joined AS last', 'core_members', array( 'member_id=?', $member->member_id ) )->setKeyField( 'ip' );
	}	
}