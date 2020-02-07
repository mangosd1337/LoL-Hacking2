<?php
/**
 * @brief		Member filter extension: member groups
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 June 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member filter: Member group
 */
class _Group
{
	/**
	 * Get Setting Field
	 *
	 * @param	mixed	$criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( $criteria )
	{
		return array(
					new \IPS\Helpers\Form\Select( 'bmf_members_groups', isset( $criteria['groups'] ) ? explode( ',', $criteria['groups'] ) : 'all', FALSE,
							array( 'options' => array_combine( array_keys( \IPS\Member\Group::groups( TRUE, FALSE ) ), array_map( function( $_group ) { return (string) $_group; }, \IPS\Member\Group::groups( TRUE, FALSE ) ) ), 'multiple' => true, 'unlimited' => 'all', 'unlimitedLang' => 'all_groups' ) ),
					);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return	mixed			False, or an array of data to use later when filtering the members
	 * @throws \LogicException
	 */
	public function save( $post )
	{
		return ( empty( $post['bmf_members_groups'] ) OR $post['bmf_members_groups'] == 'all' ) ? array( 'groups' => NULL ) : array( 'groups' => implode( ',', $post['bmf_members_groups'] ) );
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param	mixed				$data	The array returned from the save() method
	 * @return	string|array|NULL	Where clause
	 */
	public function getQueryWhereClause( $data )
	{
		if ( $data['groups'] )
		{
			$_groups	= explode( ',', $data['groups'] );
			$_set		= array();

			foreach( $_groups as $_group )
			{
				$_set[]	= "FIND_IN_SET(" . $_group . ",mgroup_others)";
			}

			if( count($_set) )
			{
				return "( member_group_id IN(" . $data['groups'] . ") OR " . implode( ' OR ', $_set ) . ' )';
			}
			
			return NULL;
		}
	}
}