<?php
/**
 * @brief		Community Enhancements: Sparkpost integration
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 March 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancements: Sparkpost integration
 */
class _Sparkpost
{
	/**
	 * @brief	IPS-provided enhancement?
	 */
	public $ips	= FALSE;

	/**
	 * @brief	Enhancement is enabled?
	 */
	public $enabled	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public $icon	= "sparkpost.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( \IPS\Settings::i()->sparkpost_api_key && \IPS\Settings::i()->sparkpost_use_for );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Radio( 'sparkpost_use_for', \IPS\Settings::i()->sparkpost_use_for, TRUE, array(
					'options'	=> array(
										'0'	=> 'sparkpost_donot_use',
										'1'	=> 'sparkpost_bulkmail_use',
										'2'	=> 'sparkpost_all_use'
										),
					'toggles'	=> array(
										'0'	=> array(),
										'1'	=> array('sparkpost_api_key'),
										'2'	=> array('sparkpost_api_key'),
										)
				) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'sparkpost_api_key', \IPS\Settings::i()->sparkpost_api_key, FALSE, array(), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('sparkpost_api_key_suffix'), 'sparkpost_api_key' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'sparkpost_click_tracking', \IPS\Settings::i()->sparkpost_click_tracking ) );
		
		if ( $form->values() )
		{
			try
			{
				$this->testSettings( $form->values() );
			}
			catch ( \Exception $e )
			{
				\IPS\Output::i()->error( $e->getMessage(), '2S123/1', 500 );
			}

			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplog__enhancements_edited', array( 'enhancements__core_Sparkpost' => TRUE ) );
			\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
		}
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'		=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> \IPS\Http\Url::ips( 'docs/sparkpost' ),
				'target'	=> '_blank'
			),
		);
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	\DomainException
	 */
	public function toggle( $enabled )
	{
		/* If we're disabling, just disable */
		if( !$enabled )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 0 ), array( 'conf_key=?', 'sparkpost_use_for' ) );
			unset( \IPS\Data\Store::i()->settings );
		}

		/* Otherwise if we already have an API key, just toggle bulk mail on */
		if( $enabled && \IPS\Settings::i()->sparkpost_api_key )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 1 ), array( 'conf_key=?', 'sparkpost_use_for' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
		else
		{
			/* Otherwise we need to let them enter an API key before we can enable.  Throwing an exception causes you to be redirected to the settings page. */
			throw new \DomainException;
		}
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array 	$values	Form values
	 * @return	void
	 * @throws	\LogicException
	 */
	protected function testSettings( $values )
	{
		/* If we've disabled, just shut off */
		if( (int) $values['sparkpost_use_for'] === 0 )
		{
			if( \IPS\Settings::i()->mail_method == 'sparkpost' )
			{
				\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 'mail' ), array( 'conf_key=?', 'mail_method' ) );
				unset( \IPS\Data\Store::i()->settings );
			}

			return;
		}

		/* If we enable Sparkpost but do not supply an API key, this is a problem */
		if( !$values['sparkpost_api_key'] )
		{
			throw new \InvalidArgumentException( "sparkpost_enable_need_details" );
		}

		/* Test Sparkpost settings */
		try
		{
			$domain = mb_substr( \IPS\Settings::i()->email_out, mb_strpos( \IPS\Settings::i()->email_out, '@' ) + 1 );
			$sparkpost = new \IPS\Email\Outgoing\SparkPost( $values['sparkpost_api_key'] );
			$sendingDomains = $sparkpost->sendingDomains();
			if ( isset( $sendingDomains['errors'] ) )
			{
				throw new \DomainException('sparkpost_bad_credentials');
			}
			$isOkay = FALSE;
			foreach ( $sendingDomains['results'] as $sendingDomain )
			{
				if ( $sendingDomain['domain'] == $domain )
				{
					if ( $sendingDomain['status']['ownership_verified'] )
					{
						$isOkay = TRUE;
					}
					else
					{
						throw new \DomainException('sparkpost_domain_not_verified');
					}
				}
			}
			if ( !$isOkay )
			{
				throw new \DomainException('sparkpost_domain_not_registered');
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new \DomainException( 'sparkpost_bad_credentials' );
		}
	}
}