<?php
/**
 * @brief		Customer Feedback Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		23 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Customer Feedback Settings
 */
class _customerfeedback extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customerfeedback_manage' );
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_support_satisfaction', \IPS\Settings::i()->nexus_support_satisfaction ) );
		
		if ( $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplogs__customerfeedback_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=support&controller=settings&tab=customerfeedback' ), 'saved' );
		}
		
		\IPS\Output::i()->output = $form;
	}
}