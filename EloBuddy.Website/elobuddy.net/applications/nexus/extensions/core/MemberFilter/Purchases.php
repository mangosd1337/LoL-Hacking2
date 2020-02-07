<?php
/**
 * @brief		Member filter extension
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		20 Apr 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member filter extension
 */
class _Purchases
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
			new \IPS\Helpers\Form\Node( 'nexus_bm_filters_packages', isset( $criteria['nexus_bm_filters_packages'] ) ? array_filter( array_map( function( $val )
			{
				try
				{
					return \IPS\nexus\Package::load( $val );
				}
				catch ( \OutOfRangeException $e )
				{
					return NULL;
				}
			}, explode( ',', $criteria['nexus_bm_filters_packages'] ) ) ) : 0, FALSE, array( 'zeroVal' => 'nexus_bm_filters_packages_none', 'multiple' => TRUE, 'class' => 'IPS\nexus\Package\Group', 'zeroValTogglesOff' => array( 'nexus_bm_filters_types' ), 'permissionCheck' => function( $node )
			{
				return !( $node instanceof \IPS\nexus\Package\Group );
			} ) ),
			new \IPS\Helpers\Form\CheckboxSet( 'nexus_bm_filters_type', isset( $criteria['nexus_bm_filters_type'] ) ? explode( ',', $criteria['nexus_bm_filters_type'] ) : array( 'active' ), FALSE, array( 'options' => array( 'active' => 'nexus_bm_filters_type_active', 'expired' => 'nexus_bm_filters_type_expired', 'canceled' => 'nexus_bm_filters_type_canceled' ) ), NULL, NULL, NULL, 'nexus_bm_filters_types' )
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
		if ( is_array( $post['nexus_bm_filters_packages'] ) )
		{
			$ids = array();
			foreach ( $post['nexus_bm_filters_packages'] as $package )
			{
				$ids[] = $package->id;
			}
			
			return array( 'nexus_bm_filters_packages' => implode( ',', $ids ), 'nexus_bm_filters_type' => implode( ',', $post['nexus_bm_filters_type'] ) );
		}
		
		return FALSE;
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param	mixed				$data	The array returned from the save() method
	 * @return	string|array|NULL	Where clause
	 */
	public function getQueryWhereClause( $data )
	{
		if ( isset( $data['nexus_bm_filters_packages'] ) and $data['nexus_bm_filters_packages'] )
		{
			return 'nexus_purchases.ps_id IS NOT NULL';
		}
		return NULL;
	}
	
	/**
	 * Callback for member retrieval database query
	 * Can be used to set joins
	 *
	 * @param	mixed			$data	The array returned from the save() method
	 * @param	\IPS\Db\Query	$query	The query
	 * @return	void
	 */
	public function queryCallback( $data, &$query )
	{
		if ( isset( $data['nexus_bm_filters_packages'] ) and $data['nexus_bm_filters_packages'] )
		{
			$types = array();
			$_types = explode( ',', $data['nexus_bm_filters_type'] );
			if ( in_array( 'active', $_types ) )
			{
				$types[] = 'nexus_purchases.ps_active=1';
			}
			if ( in_array( 'expired', $_types ) )
			{
				$types[] = '( nexus_purchases.ps_active=0 AND nexus_purchases.ps_cancelled=0 )';
			}
			if ( in_array( 'canceled', $_types ) )
			{
				$types[] = 'nexus_purchases.ps_cancelled=1';
			}
			
			
			if ( !empty( $types ) )
			{
				$types = implode( ' OR ', $types );
				
				$query->join( 'nexus_purchases', "nexus_purchases.ps_app='nexus' AND nexus_purchases.ps_type='package' AND nexus_purchases.ps_member=core_members.member_id AND " . \IPS\Db::i()->in( 'nexus_purchases.ps_item_id', explode( ',', $data['nexus_bm_filters_packages'] ) ) . " AND ( {$types} )" );
			}
		}
	}
}