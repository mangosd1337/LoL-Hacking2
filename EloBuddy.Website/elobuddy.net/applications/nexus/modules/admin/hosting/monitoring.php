<?php
/**
 * @brief		Monitoring Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\hosting;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Monitoring Settings
 */
class _monitoring extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Hosting\EOM';

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Init Tabs */
		$tabs = array();
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'monitoring_settings' ) )
		{
			$tabs['settings'] = 'settings';
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'monitoring_eom_view' ) )
		{
			$tabs['eom'] = 'expected_output_monitoring';
		}
		$keys = array_keys( $tabs );
		$activeTab = ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $tabs ) ) ? \IPS\Request::i()->tab : array_shift( $keys );
		
		/* Work out contents */
		$activeTabContents = NULL;
		switch ( $activeTab )
		{
			/* Settings */
			case 'settings':
			
				/* Build Form */
				$form = new \IPS\Helpers\Form;
				$form->addHeader('monitoring_header');
				$form->addMessage('monitoring_blurb');
				$form->add( new \IPS\Helpers\Form\YesNo( 'monitoring_on', \IPS\Settings::i()->monitoring_script, FALSE, array( 'togglesOn' => array( 'monitoring_script', 'monitoring_alert', 'monitoring_from', 'monitoring_backup', 'monitoring_allowed_fails', 'monitoring_panic', 'form_header_network_status_header', 'network_status' ) ) ) );
				$form->add( new \IPS\Helpers\Form\Url( 'monitoring_script', \IPS\Settings::i()->monitoring_script, NULL, array( 'placeholder' => 'http://www.example.com/monitor_master.php' ), NULL, NULL, NULL, 'monitoring_script' ) );
				$form->add( new \IPS\Helpers\Form\Url( 'monitoring_backup', \IPS\Settings::i()->monitoring_backup, FALSE, array( 'placeholder' => 'http://backup.example.com/monitor_master.php' ), NULL, NULL, NULL, 'monitoring_backup' ) );
				$form->add( new \IPS\Helpers\Form\Stack( 'monitoring_alert', explode( ',', \IPS\Settings::i()->monitoring_alert ), NULL, array(), NULL, NULL, NULL, 'monitoring_alert' ) );
				$form->add( new \IPS\Helpers\Form\Email( 'monitoring_from', \IPS\Settings::i()->monitoring_from ?: \IPS\Settings::i()->email_out, NULL, array(), NULL, NULL, NULL, 'monitoring_from' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'monitoring_allowed_fails', \IPS\Settings::i()->monitoring_allowed_fails, FALSE, array(), NULL, NULL, NULL, 'monitoring_allowed_fails' ) );
				$form->add( new \IPS\Helpers\Form\Number( 'monitoring_panic', \IPS\Settings::i()->monitoring_panic, FALSE, array(), NULL, NULL, NULL, 'monitoring_panic' ) );
				$form->addHeader('network_status_header');
				$form->add( new \IPS\Helpers\Form\Radio( 'network_status', \IPS\Settings::i()->network_status, FALSE, array( 'options' => array( 2 => 'network_status_2', 1 => 'network_status_1', 0 => 'network_status_0' ), 'toggles' => array( 2 => array( 'network_status_text' ), 1 => array( 'network_status_text' ) ) ), NULL, NULL, NULL, 'network_status' ) );
				$form->add( new \IPS\Helpers\Form\Translatable( 'network_status_text', NULL, FALSE, array( 'app' => 'nexus', 'key' => 'network_status_text_val', 'editor' => array( 'app' => 'nexus', 'key' => 'Admin', 'autoSaveKey' => 'network_status_text', 'attachIds' => array( NULL, NULL, 'network_status_text' ) ) ), NULL, NULL, NULL, 'network_status_text' ) );
				
				/* Handle submissions */
				if ( $values = $form->values() )
				{
					if ( !$values['monitoring_on'] )
					{
						$values['monitoring_script'] = '';
						\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), "`key`='monitor'" );
					}
					else
					{
						\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='monitor'" );
					}
					unset( $values['monitoring_on'] );
					
					$values['monitoring_alert'] = implode( ',', $values['monitoring_alert'] );
					
					\IPS\Lang::saveCustom( 'nexus', 'network_status_text_val', $values['network_status_text'] );
					unset( $values['network_status_text'] );
					
					$form->saveAsSettings( $values );
					
					\IPS\Session::i()->log( 'acplogs__host_mon_settings' );
					
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=hosting&controller=monitoring&tab=settings' ), 'saved' );
				}
				
				/* Display */
				$activeTabContents = (string) $form;
			break;
			
			/* EOM */
			case 'eom':
				parent::manage();
				$activeTabContents = \IPS\Theme::i()->getTemplate('forms', 'core')->blurb( 'eom_blurb', TRUE, TRUE ) . \IPS\Output::i()->output;
			break;
		}
		
		/* Display */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		else
		{
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_hosting_monitoring');
			\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->tabs( $tabs, $activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=nexus&module=hosting&controller=monitoring" ) );
		}
	}
	
	/**
	 * Download Master Monitoring Script
	 *
	 * @return	void
	 */
	public function downloadMaster()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'monitoring_settings' );
		\IPS\Output::i()->sendOutput( file_get_contents( \IPS\ROOT_PATH . '/applications/nexus/data/monitoring/master.txt' ), 200, 'text/plain', array( 'Content-Disposition' => 'attachment; filename=monitor_master.php' ) );
	}
	
	/**
	 * Download Remote Monitoring Script
	 *
	 * @return	void
	 */
	public function downloadRemote()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'monitoring_settings' );
		\IPS\Output::i()->sendOutput( file_get_contents( \IPS\ROOT_PATH . '/applications/nexus/data/monitoring/remote.txt' ), 200, 'text/plain', array( 'Content-Disposition' => 'attachment; filename=monitor_remote.php' ) );
	}
}