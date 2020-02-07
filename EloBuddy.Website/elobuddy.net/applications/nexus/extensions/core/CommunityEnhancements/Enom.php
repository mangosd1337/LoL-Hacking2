<?php
/**
 * @brief		Community Enhancements: eNom
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class _Enom
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public $icon	= "enom.gif";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = (bool) \IPS\Settings::i()->nexus_enom_un;
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit()
	{
		$form = new \IPS\Helpers\Form;		
		$form->add( new \IPS\Helpers\Form\Text( 'nexus_enom_un', \IPS\Settings::i()->nexus_enom_un, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'nexus_enom_pw', \IPS\Settings::i()->nexus_enom_pw, TRUE ) );
		if ( $values = $form->values() )
		{
			try
			{
				$enom = new \IPS\nexus\DomainRegistrar\Enom( $values['nexus_enom_un'], $values['nexus_enom_pw'] );
				$enom->check( 'example', 'com' );
				$form->saveAsSettings( $values );

				\IPS\Output::i()->inlineMessage	= \IPS\Member::loggedIn()->language()->addToStack('saved');
			}
			catch ( \RuntimeException $e )
			{
				$form->error = $e->getMessage();
			}			
		}
		
		\IPS\Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> \IPS\Http\Url::ips( 'docs/enom' ),
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
	 * @throws	\LogicException
	 */
	public function toggle( $enabled )
	{
		if ( $enabled )
		{
			throw new \LogicException;
		}
		else
		{	
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_key=? OR conf_key=?', 'nexus_enom_un', 'nexus_enom_pw' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
	}
}