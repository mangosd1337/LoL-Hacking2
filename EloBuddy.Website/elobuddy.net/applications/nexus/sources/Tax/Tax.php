<?php
/**
 * @brief		Tax Rate Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Tax Rate Node
 */
class _Tax extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_tax';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 't_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'order';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'tax_rates';
	
	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'nexus_tax_';
								
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'payments',
		'all'		=> 'tax_manage',
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$defaultVal = 0;
		if ( $this->rate )
		{
			foreach ( json_decode( $this->rate, TRUE ) as $rate )
			{
				if ( $rate['locations'] === '*' )
				{
					$defaultVal = $rate['rate'];
				}
			}
		}
		
		$form->addTab( 'tax_settings' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'tax_name', NULL, TRUE, array( 'app' => 'nexus', 'key' => $this->id ? "nexus_tax_{$this->id}" : NULL, 'placeholder' => \IPS\Member::loggedIn()->language()->addToStack('tax_name_placeholder') ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'tax_default', $defaultVal, TRUE, array( 'decimals' => 2 ), NULL, NULL, '%' ) );
		
		$form->addTab( 'tax_rates_tab' );
		$matrix = new \IPS\Helpers\Form\Matrix;
		$matrix->squashFields = FALSE;
		$matrix->columns = array(
			'tax_rate'		=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\Number( $key, $value, FALSE, array( 'decimals' => 2 ), NULL, NULL, '%' );
			},
			'tax_locations'	=> function( $key, $value, $data )
			{
				return new \IPS\nexus\Form\StateSelect( $key, $value, FALSE, array( 'options' => array( 'foo' ), 'multiple' => TRUE ) );
			}
		);
		
		$rates = json_decode( $this->rate, TRUE );
		if ( is_array( $rates ) )
		{
			foreach ( $rates as $rate )
			{
				if ( $rate['locations'] !== '*' )
				{
					$matrix->rows[] = array(
						'tax_rate'		=> $rate['rate'],
						'tax_locations'	=> $rate['locations']
					);
				}
			}
		}
		
		$form->addMatrix( 'rates', $matrix );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{		
		$rates = array();
		
		if( isset( $values['rates'] ) )
		{
			foreach ( $values['rates'] as $rate )
			{
				if ( $rate['tax_locations'] )
				{
					$rates[] = array(
						'locations' => $rate['tax_locations'],
						'rate'		=> $rate['tax_rate']
					);
				}
			}
		}

		if( isset( $values['tax_default'] ) )
		{
			$rates[] = array(
				'locations' => '*',
				'rate'		=> $values['tax_default']
			);
		}
		
		$values = array_merge( array( 'rate' => json_encode( $rates ) ), $values );

		unset( $values['tax_default'] );
		unset( $values['rates'] );

		if( isset( $values['tax_name'] ) )
		{
			$name = $values['tax_name'];
			unset( $values['tax_name'] );
			$this->save();
			\IPS\Lang::saveCustom( 'nexus', "nexus_tax_{$this->id}", $name );
		}

		return $values;
	}
	
	/**
	 * Get rate
	 *
	 * For example, if the rate for the location is 10%, will return 0.1
	 *
	 * @param	\IPS\GeoLocation|NULL	$billingAddress	The billing address
	 * @return	float
	 */
	public function rate( \IPS\GeoLocation $billingAddress = NULL )
	{
		$defaultVal = 0;
		foreach ( json_decode( $this->rate, TRUE ) as $rate )
		{
			if ( $rate['locations'] === '*' )
			{
				$defaultVal = $rate['rate'];
			}
			elseif ( $billingAddress and isset( $rate['locations'][ $billingAddress->country ] ) )
			{
				if ( $rate['locations'][ $billingAddress->country ] === '*' or in_array( $billingAddress->region, $rate['locations'][ $billingAddress->country ] ) )
				{
					return $rate['rate'] / 100;
				}
			}
		}

		return number_format( ( $defaultVal / 100 ), 5, '.', '' );
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse		int		id		ID Number
	 * @apiresponse		string	name	Name
	 */
	public function apiOutput()
	{
		return array(
			'id'	=> $this->id,
			'name'	=> $this->_title
		);
	}
}