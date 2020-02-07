<?php
/**
 * @brief		Referrals
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		15 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\customers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Referrals
 */
class _referrals extends \IPS\Dispatcher\Controller
{
	/**
	 * Call
	 *
	 * @return	void
	 */
	public function __call( $method, $args )
	{
		$tabs = array();
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'referrals_manage' ) )
		{
			$tabs['refersettings'] = 'settings';
		}
		if( \IPS\Settings::i()->cm_ref_on and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'referrals_commission_rules' ) )
		{
			$tabs['commissionrules'] = 'commission_rules';
		}
		if( \IPS\Settings::i()->cm_ref_on and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'referrals_banners' ) )
		{
			$tabs['referralbanners'] = 'referral_banners';
		}
		if ( isset( \IPS\Request::i()->tab ) and isset( $tabs[ \IPS\Request::i()->tab ] ) )
		{
			$activeTab = \IPS\Request::i()->tab;
		}
		else
		{
			$_tabs = array_keys( $tabs ) ;
			$activeTab = array_shift( $_tabs );
		}
		
		$classname = 'IPS\nexus\modules\admin\customers\\' . $activeTab;
		$class = new $classname;
		$class->url = \IPS\Http\Url::internal("app=nexus&module=customers&controller=referrals&tab={$activeTab}");
		$class->execute();
		
		if ( $method !== 'manage' or \IPS\Request::i()->isAjax() )
		{
			return;
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_customers_referrals');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, \IPS\Output::i()->output, \IPS\Http\Url::internal( "app=nexus&module=customers&controller=referrals" ) );
	}
}