<?php
/**
 * @brief		referralbanners
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Aug 2014
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
 * referralbanners
 */
class _referralbanners extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\ReferralBanner';
	
	/**
	 * Title can contain HTML?
	 */
	public $_titleHtml = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'referralbanners_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage()
	{
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'referral_banners_blurb', TRUE, TRUE );
		return parent::manage();
	}
	
	/**
	 * Form
	 *
	 * @return	void
	 */
	public function form()
	{
		parent::form();
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('referral_banners');
	}
}