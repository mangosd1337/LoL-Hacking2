<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		5 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

$options = array();

if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_staff' )->first() )
{
	$options[] = new \IPS\Helpers\Form\Custom( '40000_nexus_staff', null, FALSE, array( 'getHtml' => function( $element ) {
		return "";
	} ), function( $val ) {}, NULL, NULL, '40000_nexus_staff' );
}

if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_packages', "p_module<>'' AND p_module IS NOT NULL" )->first() )
{
	$options[] = new \IPS\Helpers\Form\Custom( '40000_nexus_modules', null, FALSE, array( 'getHtml' => function( $element ) {
		return "";
	} ), function( $val ) {}, NULL, NULL, '40000_nexus_modules' );
}