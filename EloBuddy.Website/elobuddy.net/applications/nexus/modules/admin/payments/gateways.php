<?php
/**
 * @brief		Payment Gateways
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Feb 2014
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
 * Payment Gateways
 */
class _gateways extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Gateway';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'gateways_manage' );
		parent::execute();
	}
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['link'] = $buttons['add']['link']->setQueryString( '_new', TRUE );
		}
		
		return $buttons;
	}
	
	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form()
	{
		if ( \IPS\Request::i()->id )
		{
			return parent::form();
		}
		else
		{
			if ( \IPS\IN_DEV )
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plupload/moxie.js', 'core', 'interface' ) );
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plupload/plupload.dev.js', 'core', 'interface' ) );
			}
			else
			{
				\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'plupload/plupload.full.min.js', 'core', 'interface' ) );
			}
			\IPS\Output::i()->output = new \IPS\Helpers\Wizard( array(
				'gateways_gateway'	=> function( $data )
				{
					$options = array();
					foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/nexus/sources/Gateway' ) as $file )
					{
						if ( $file->isDir() and !$file->isDot() )
						{
							if ( ( (string) $file === 'Test' and !\IPS\NEXUS_TEST_GATEWAYS ) or (string) $file === 'SagePay' )
							{
								continue;
							}
							$options[ (string) $file ] = 'gateway__' . $file;
						}
					}
					
					$form = new \IPS\Helpers\Form;
					$form->add( new \IPS\Helpers\Form\Radio( 'gateways_gateway', TRUE, NULL, array( 'options' => $options ) ) );
					if ( $values = $form->values() )
					{
						return array( 'gateway' => $values['gateways_gateway'] );
					}
					return $form;
				},
				'gateways_details'	=> function( $data )
				{
					$form = new \IPS\Helpers\Form('gw');
					$form->add( new \IPS\Helpers\Form\Translatable( 'paymethod_name', \IPS\Member::loggedIn()->language()->addToStack( 'gateway__' . $data['gateway'] ), TRUE ) );
					$class = 'IPS\nexus\Gateway\\' . $data['gateway'];
					$obj = new $class;
					$obj->gateway = $data['gateway'];
					$obj->active = TRUE;
					$obj->settings( $form );
					if ( $values = $form->values() )
					{

						$settings = array();
						foreach ( $values as $k => $v )
						{
							if ( $k !== 'paymethod_name' )
							{
								$settings[ mb_substr( $k, mb_strlen( $data['gateway'] ) + 1 ) ] = $v;
							}
						}
						try
						{
							$settings = $obj->testSettings( $settings );
						}
						catch ( \InvalidArgumentException $e )
						{
							$form->error = $e->getMessage();
							return $form;
						}
						$obj->settings = json_encode( $settings );
						$obj->save();
						\IPS\Lang::saveCustom( 'nexus', "nexus_paymethod_{$obj->id}", $values['paymethod_name'] );

						$values = $obj->formatFormValues( $values );

						\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=paymentsettings&tab=gateways') );
					}
					return $form;
				}
			), \IPS\Http\Url::internal('app=nexus&module=payments&controller=paymentsettings&tab=gateways&do=form') );
		}
	}
}