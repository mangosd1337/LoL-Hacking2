<?php
/**
 * @brief		Dedicated Server
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\nexus\Item;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Dedicated Server
 */
class _DedicatedServer extends \IPS\nexus\extensions\nexus\Item\CustomPackage
{
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Application
	 */
	public static $type = 'dedi';
	
	/**
	 * @brief	Icon
	 */
	public static $icon = 'server';
	
	/**
	 * @brief	Title
	 */
	public static $title = 'dedicated_server';
	
	/**
	 * Generate Invoice Form
	 *
	 * @param	\IPS\Helpers\Form	$form		The form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	void
	 */
	public static function form( \IPS\Helpers\Form $form, \IPS\nexus\Invoice $invoice )
	{
		$groups = array();
		foreach ( \IPS\Member\Group::groups() as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		$form->addTab('package_settings');
		$form->add( new \IPS\Helpers\Form\Node( 'dedicated_server', NULL, TRUE, array( 'class' => 'IPS\nexus\Hosting\Server', 'permissionCheck' => function( $server )
		{
			return $server->type == 'none';
		} ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_base_price', 0, TRUE, array(), NULL, NULL, $invoice->currency ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_tax', 0, FALSE, array( 'class' => 'IPS\nexus\Tax', 'zeroVal' => 'do_not_tax' ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_renews', FALSE, FALSE, array( 'togglesOn' => array( 'p_renew_options', 'p_renew' ) ), NULL, NULL, NULL, 'p_renews' ) );
		$form->add( new \IPS\nexus\Form\RenewalTerm( 'p_renew_options', NULL, FALSE, array( 'currency' => $invoice->currency ), NULL, NULL, NULL, 'p_renew_options' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_renew', FALSE, FALSE, array( 'togglesOn' => array( 'p_renewal_days', 'p_renewal_days_advance' ) ), NULL, NULL, NULL, 'p_renew' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_renewal_days', -1, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'any_time' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days_before_expiry'), 'p_renewal_days' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'p_renewal_days_advance', -1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('days'), 'p_renewal_days_advance' ) );
		
		$form->addTab( 'package_benefits' );
		unset( $groups[ \IPS\Settings::i()->guest_group ] );
		$form->add( new \IPS\Helpers\Form\Select( 'p_primary_group', '*', FALSE, array( 'options' => $groups, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_primary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_return_primary', TRUE, FALSE, array(), NULL, NULL, NULL, 'p_return_primary' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'p_secondary_group', '*', FALSE, array( 'options' => $groups, 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'do_not_change', 'unlimitedToggles' => array( 'p_return_secondary' ), 'unlimitedToggleOn' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_return_secondary', TRUE, FALSE, array(), NULL, NULL, NULL, 'p_return_secondary' ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_support_severity', 0, FALSE, array( 'class' => 'IPS\nexus\Support\Severity', 'zeroVal' => 'none' ), NULL, NULL, NULL, 'p_support_severity' ) );
		
		$form->addTab('package_client_area_display');
		$form->add( new \IPS\Helpers\Form\Editor( 'p_page', NULL, FALSE, array(
			'app'			=> 'nexus',
			'key'			=> 'Admin',
			'autoSaveKey'	=> "nexus-new-pkg-pg",
			'attachIds'		=> NULL, 'minimize' => 'p_page_placeholder'
		), NULL, NULL, NULL, 'p_desc_editor' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'p_support', FALSE, FALSE, array( 'togglesOn' => array( 'p_support_department' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Node( 'p_support_department', 0, FALSE, array( 'class' => 'IPS\nexus\Support\Department', 'zeroVal' => 'none' ), NULL, NULL, NULL, 'p_support_department' ) );
	}
	
	/**
	 * Create the package
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	\IPS\nexus\Package
	 */
	protected static function _createPackage( array $values, \IPS\nexus\Invoice $invoice )
	{
		$values['p_type'] = 'dedi';
		$values['p_name'] = $values['dedicated_server']->hostname;
		return parent::_createPackage( $values, $invoice );
	}
	
	/**
	 * Generate Invoice Form: Second Step
	 *
	 * @param	\IPS\Helpers\Form	$form		The form
	 * @param	\IPS\nexus\Invoice	$invoice	The invoice
	 * @return	bool
	 */
	public static function formSecondStep( array $values, \IPS\Helpers\Form $form, \IPS\nexus\Invoice $invoice )
	{
		return FALSE;
	}
}