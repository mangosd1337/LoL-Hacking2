<?php
/**
 * @brief		Expected Output Monitoring Rule
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		12 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Hosting;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Expected Output Monitoring Rule
 */
class _EOM extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_eom';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'eom_';
		
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'expected_output_monitoring';
			
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
		'module'	=> 'hosting',
		'prefix' 	=> 'monitoring_eom_'
	);

	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function get__title()
	{
		return (string) $this->url;
	}
		
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->add( new \IPS\Helpers\Form\Url( 'eom_url', $this->url, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'eom_type', $this->type, TRUE, array( 'options' => array( 'c' => 'eom_type_c', 'e' => 'eom_type_e', 'n' => 'eom_type_n' ) ) ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'eom_value', $this->value, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'eom_notify', json_decode( $this->notify, TRUE ), TRUE ) );
	
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['eom_notify'] ) )
		{
			$values['eom_notify'] = json_encode( $values['eom_notify'] );
		}
		
		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='expectedOutputMonitoring'" );
	}
}