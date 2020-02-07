<?php
/**
 * @brief		Custom Package Field Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		1 May 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Package;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Custom Profile Field Node
 */
class _CustomField extends \IPS\CustomField
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_package_fields';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'cf_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static $langKey = 'nexus_pfield';
	
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
		'module'	=> 'store',
		'prefix'	=> 'package_fields_',
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'custom_package_fields';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'nexus_pfield_';
	
	/**
	 * @brief	[CustomField] Column Map
	 */
	public static $databaseColumnMap = array(
		'content'	=> 'extra',
		'not_null'	=> 'required'
	);
	
	/**
	 * @brief	[CustomField] Additional Field Classes
	 */
	public static $additionalFieldTypes = array(
		'UserPass'	=> 'sf_type_UserPass',
		'Ftp'		=> 'sf_type_Ftp'
	);
	
	/**
	 * @brief	[CustomField] Upload Field Storage Extension
	 */
	public static $uploadStorageExtension = 'nexus_PurchaseFields';
	
	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static $editorOptions = array( 'app' => 'nexus', 'key' => 'Purchases' );
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		parent::form( $form );
		
		$packages = array();
		foreach ( array_filter( explode( ',', $this->packages ) ) as $id )
		{
			try
			{
				$packages[] = \IPS\nexus\Package::load( $id );
			}
			catch ( \OutOfRangeException $e ) { }
		}
		$form->add( new \IPS\Helpers\Form\Node( 'cf_packages', $packages, FALSE, array( 'class' => 'IPS\nexus\Package\Group', 'noParentNodes' => 'custom_packages', 'multiple' => TRUE, 'permissionCheck' => function( $node )
		{
			return !( $node instanceof \IPS\nexus\Package\Group );
		} ) ), 'pf_desc' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cf_sticky', $this->sticky, FALSE ), 'pf_type' );
		
		$form->addHeader( 'display_settings' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cf_purchase', $this->purchase, FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cf_editable', $this->editable, FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cf_required', $this->required ?: FALSE, FALSE, array(), NULL, NULL, NULL, 'pf_not_null' ) );

		unset( $form->elements[''][1] );	
		unset( $form->elements['']['pf_not_null'] );
		unset( $form->elements['']['pf_max_input'] );
		unset( $form->elements['']['pf_input_format'] );		
		unset( $form->elements[''][2] );
		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_format'] );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['cf_packages'] ) AND is_array( $values['cf_packages'] ) )
		{
			$values['packages'] = implode( ',', array_map( function( $node ){ return $node->id; }, $values['cf_packages'] ) );
			unset( $values['cf_packages'] );
		}

		return parent::formatFormValues( $values );
	}
	
	/**
	 * Build Form Helper
	 *
	 * @param	mixed		$value					The value
	 * @param	callback	$customValidationCode	Custom validation code
	 * @return \IPS\Helpers\Form\FormAbstract
	 */
	public function buildHelper( $value=NULL, $customValidationCode=NULL )
	{
		if ( $this->type === 'UserPass' )
		{
			$class = 'IPS\nexus\Form\\' . $this->type;
			return new $class( static::$langKey . '_' . $this->id, $value, $this->not_null, array(), NULL, NULL, NULL, static::$langKey . '_' . $this->id );
		}
		
		return parent::buildHelper( $value, $customValidationCode );
	}
	
	/**
	 * Display Value
	 *
	 * @param	mixed	$value	The value
	 * @return	string
	 */
	public function displayValue( $value=NULL )
	{
		if ( $this->type === 'UserPass' )
		{
			return \IPS\Theme::i()->getTemplate( 'forms', 'nexus', 'global' )->usernamePasswordDisplay( json_decode( \IPS\Text\Encrypt::fromTag( $value )->decrypt(), TRUE ) );
		}
		
		return parent::displayValue( $value );
	}
}