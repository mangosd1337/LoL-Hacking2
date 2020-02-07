<?php
/**
 * @brief		Checkout Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		28 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Checkout Settings
 */
class _checkoutsettings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'checkout_settings' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage()
	{		
		$form = new \IPS\Helpers\Form;
		$form->addHeader( 'checkout_settings' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_https', \IPS\Settings::i()->nexus_https, FALSE, array(), function( $val )
		{
			if ( $val )
			{
				try
				{
					\IPS\Http\Url::internal( '', 'front', NULL, array(), TRUE )->request()->get();
				}
				catch ( \IPS\Http\Request\Exception $e )
				{
					throw new \DomainException('nexus_https_err');
				}
			}
		} ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_split_payments_on', \IPS\Settings::i()->nexus_split_payments != -1, FALSE, array( 'togglesOn' => array( 'nexus_split_payments' ) ) ) );
		$form->add( new \IPS\nexus\Form\Money( 'nexus_split_payments', \IPS\Settings::i()->nexus_split_payments ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_split_payments' ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'nexus_tac', \IPS\Settings::i()->nexus_tac, FALSE, array(
			'options'	=> array(
				'none'		=> 'nexus_tac_none',
				'button'	=> 'nexus_tac_button',
				'checkbox'	=> 'nexus_tac_checkbox'
			),
			'toggles'	=> array(
				'button'	=> array( 'nexus_tac_link' ),
				'checkbox'	=> array( 'nexus_tac_link' )
			)
		) ) );
		$form->add( new \IPS\Helpers\Form\Url( 'nexus_tac_link', \IPS\Settings::i()->nexus_tac_link, FALSE, array(), NULL, NULL, NULL, 'nexus_tac_link' ) );
		$form->addHeader( 'nexus_checkreg' );
		$form->addMessage( 'nexus_checkreg_desc' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_checkreg_usernames', \IPS\Settings::i()->nexus_checkreg_usernames ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_checkreg_captcha', \IPS\Settings::i()->nexus_checkreg_captcha ) );
		if ( \IPS\Settings::i()->reg_auth_type != 'none' )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_checkreg_validate', \IPS\Settings::i()->nexus_checkreg_validate ) );
		}
		if ( $values = $form->values() )
		{
			$values['nexus_split_payments'] = $values['nexus_split_payments_on'] ? ( $values['nexus_split_payments'] == '*' ? 0 : json_encode( $values['nexus_split_payments'] ) ) : -1;
			$form->saveAsSettings( $values );
			
			\IPS\Session::i()->log( 'acplogs__checkout_settings' );		
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=paymentsettings&tab=checkoutsettings' ) );
		}
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('checkout_settings');
		\IPS\Output::i()->output = $form;
	}
}