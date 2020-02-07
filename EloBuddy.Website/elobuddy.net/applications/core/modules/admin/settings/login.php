<?php
/**
 * @brief		Login Handlers
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login Handlers
 */
class _login extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\Login\LoginAbstract';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'login_manage' );
		
		return parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'login_settings',
				'icon'		=> 'cog',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=login&do=settings' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('login_settings') )
			),
			'forcelogout'	=> array(
				'title'		=> 'force_all_logout',
				'icon'		=> 'lock',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=login&do=forceLogout' ),
				'data'		=> array( 'confirm' => '' ),
			),
		);
		
		/* Show IPS Connect "master" information */
		$masterUrl	= \IPS\Http\Url::internal( 'applications/core/interface/ipsconnect/ipsconnect.php', 'none' );
		$masterKey	= \IPS\CONNECT_MASTER_KEY ?: md5( md5( \IPS\Settings::i()->sql_user . \IPS\Settings::i()->sql_pass ) . \IPS\Settings::i()->board_start );

		\IPS\Output::i()->output		= \IPS\Theme::i()->getTemplate( 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'login_ipsconnect_info', FALSE, array( 'sprintf' => array( $masterUrl, $masterKey ) ) ), 'info', NULL, FALSE );

		return parent::manage();
	}

	/**
	 * Force all users to be logged out
	 *
	 * @return	void
	 */
	protected function forceLogout()
	{
		\IPS\Db::i()->update( 'core_members', array( 'member_login_key' => '' ) );
		\IPS\Db::i()->delete( 'core_sessions' );

		\IPS\Session::i()->log( 'acplogs__logout_force' );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=login' ), 'logged_out_force' );
	}
	
	/**
	 * Toggle Enabled/Disable
	 * Overridden so we can check the settings are okay before we enable
	 *
	 * @return	void
	 */
	protected function enableToggle()
	{
		$node = \IPS\Login\LoginAbstract::load( \IPS\Request::i()->id );

		if ( \IPS\Request::i()->status )
		{
			if ( method_exists( $node, 'testSettings' ) )
			{
				try
				{
					$node->testSettings();
				}
				catch ( \Exception $e )
				{
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=settings&controller=login&do=form&id={$node->key}" ) );
				}
			}
		}
		else
		{
			$others = \IPS\Db::i()->select( 'count(*)', 'core_login_handlers', array( 'login_enabled=? and login_key<>?', 1, $node->key ) )->first();

			if( !$others )
			{
				\IPS\Output::i()->error( 'login_atleast_one', '1C166/3', 403, '' );
			}
		}

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		return parent::enableToggle();
	}

	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		$node = \IPS\Login\LoginAbstract::load( \IPS\Request::i()->id );

		$others = \IPS\Db::i()->select( 'count(*)', 'core_login_handlers', array( 'login_enabled=? and login_key<>?', 1, $node->key ) )->first();

		if( !$others )
		{
			\IPS\Output::i()->error( 'login_atleast_one', '1C166/3', 403, '' );
		}

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		return parent::delete();
	}
	
	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		/* Build Form */
		$form = new \IPS\Helpers\Form();
		$form->addHeader( 'ipb_bruteforce_attempts' );
		$form->add( new \IPS\Helpers\Form\Number( 'ipb_bruteforce_attempts', \IPS\Settings::i()->ipb_bruteforce_attempts, FALSE, array( 'min' => 0, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('failed_logins'), 'ipb_bruteforce_attempts' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'ipb_bruteforce_period', \IPS\Settings::i()->ipb_bruteforce_period, FALSE, array( 'min' => 0, 'max' => 10000, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('minutes'), 'ipb_bruteforce_period' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipb_bruteforce_unlock', \IPS\Settings::i()->ipb_bruteforce_unlock, FALSE, array(), NULL, NULL, NULL, 'ipb_bruteforce_unlock' ) );
		$form->addHeader( 'login_settings' );
		
		/* Check if the site works over HTTPS before we allow them to change that */
		$httpsValue = \IPS\Settings::i()->logins_over_https;
		$httpsDisabled = FALSE;
		$toggles = array();
		if ( mb_substr( \IPS\Settings::i()->base_url, 0, 8 ) === 'https://' )
		{
			$httpsValue = TRUE;
			$httpsDisabled = TRUE;
			\IPS\Member::loggedIn()->language()->words['logins_over_https_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'logins_over_https_enabled', FALSE );
		}
		else
		{
			try
			{
				$response = \IPS\Http\Url::external( 'https://' . mb_substr( \IPS\Settings::i()->base_url, 7 ) )->request()->get();
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				$httpsValue = FALSE;
				$httpsDisabled = TRUE;
				$toggles[] = 'form_logins_over_https_warning';
				\IPS\Member::loggedIn()->language()->words['logins_over_https_desc'] = \IPS\Member::loggedIn()->language()->addToStack( 'logins_over_https_disabled', FALSE, array( 'sprintf' => array( $e->getMessage() ) ) );
			}
		}
		$form->add( new \IPS\Helpers\Form\YesNo( 'logins_over_https', $httpsValue, FALSE, array( 'disabled' => $httpsDisabled, 'togglesOn' => $toggles ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'disable_anonymous', !\IPS\Settings::i()->disable_anonymous, FALSE, array(), NULL, NULL, NULL, 'disable_anonymous' ) );
		
		/* Save */
		if ( $values = $form->values() )
		{
			$values['disable_anonymous'] = $values['disable_anonymous'] ? FALSE : TRUE;
			
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__login_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=login' ), 'saved' );
		}
		
		/* Display */
		\IPS\Output::i()->output		= \IPS\Theme::i()->getTemplate( 'global' )->block( 'login_settings', $form, FALSE );
		\IPS\Output::i()->title			= \IPS\Member::loggedIn()->language()->addToStack( 'login_settings' );
		\IPS\Output::i()->breadcrumb[]	= array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'login_settings' ) );
	}

	/**
	 * Manually reprocess IPS Connect requests that are queued
	 *
	 * @return	void
	 */
	protected function retryConnect()
	{
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('try_slave_request_again');

		\IPS\Output::i()->output	= new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( "app=core&module=settings&controller=login&do=retryConnect&slave=" . \IPS\Request::i()->slave ),
			function( $data )
			{
				/* On first cycle get data */
				if ( !is_array( $data ) )
				{
					/* Get total number of failures to process for progress bar */
					$data	= array( 'done' => 0, 'total' => count( \IPS\Db::i()->select( 'id', 'core_ipsconnect_queue', array( 'slave_id=?', intval( \IPS\Request::i()->slave ) ) ) ) );
				}

				/* Get the next request */
				try
				{
					$request	= \IPS\Db::i()->select( 'q.*, s.*', array( 'core_ipsconnect_queue', 'q' ), array( 'q.slave_id=?', intval( \IPS\Request::i()->slave ) ), 'q.id asc', array( 0, 1 ) )
						->join( array( 'core_ipsconnect_slaves', 's' ), "s.slave_id=q.slave_id" )
						->first();

					/* Try to process request again */
					try
					{
						parse_str( $request['request_url'], $variables );

						$response = \IPS\Http\Url::external( $request['slave_url'] )
							->setQueryString( $variables )
							->request()
							->get();

						if( $response->httpResponseCode !== NULL AND $response->httpResponseCode > 200 )
						{
							/* Update fail count */
							\IPS\Db::i()->update( 'core_ipsconnect_queue', 'fail_count=fail_count+1', array( 'id=?', $request['id'] ) );

							/* Show error message */
							\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'connect_failure_details', FALSE, array( 'sprintf' => 
								array( \IPS\Member::loggedIn()->language()->addToStack('connect_slavefail_http', FALSE, array( 'sprintf' => array( $response->httpResponseCode ) ) ) )
							) ), '4C166/1', 503, '' );
						}

						$response	= $response->decodeJson();

						/* If this is a registered slave but the slave is telling us Connect is disabled, remove it */
						if( isset( $response['status'] ) AND $response['status'] == 'DISABLED' )
						{
							\IPS\Db::i()->delete( 'core_ipsconnect_slaves', array( 'slave_id=?', $request['slave_id'] ) );
							\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Db::i()->select( 'COUNT(*)', 'core_ipsconnect_slaves' )->first() ), array( 'conf_key=?', 'connect_slaves' ) );
							unset( \IPS\Data\Store::i()->settings );
						}
						else if( !isset( $response['status'] ) OR $response['status'] != 'SUCCESS' )
						{
							throw new \RuntimeException( $response['status'] );
						}

						/* If we are here, request succeeded - remove from queue */
						\IPS\Db::i()->delete( 'core_ipsconnect_queue', array( 'id=?', $request['id'] ) );
					}
					catch( \RuntimeException $e )
					{
						/* Update fail count */
						\IPS\Db::i()->update( 'core_ipsconnect_queue', 'fail_count=fail_count+1', array( 'id=?', $request['id'] ) );

						/* The request failed - show the admin why */
						\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack('connect_failure_details', FALSE, array( 'sprintf' => array( $e->getMessage() ) ) ), '4C166/2', 503, '' );
					}

					$data['done']++;

					/* Return null to indicate we are done */
					return array( $data, 'connect_retry_success', round( $data['done'] / $data['total'] ) );
				}
				catch( \UnderflowException $e )
				{
					\IPS\Session::i()->log( 'acplog__connect_queue_cleared' );

					/* Disable task */
					\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), "`key`='ipsconnect'" );

					return NULL;
				}
			},
			function()
			{
				/* And redirect back to the dashboard */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=overview&controller=dashboard' ), 'connect_queue_cleared' );
			}
		);
	}
}