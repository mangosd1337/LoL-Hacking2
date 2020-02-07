<?php
/**
 * @brief		Upgrader: Login
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 May 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: Login
 */
class _login extends \IPS\Dispatcher\Controller
{
	/**
	 * Show login form and/or process login form
	 *
	 * @todo	[Upgrade] Will also need to account for things in the input (e.g. password) that would be replaced, like & to &amp;
	 * @return	void
	 */
	public function manage()
	{
		/* Clear previous session data */
		if( !isset( \IPS\Request::i()->sessionCheck ) AND count( $_SESSION ) )
		{
			foreach( $_SESSION as $k => $v )
			{
				unset( $_SESSION[ $k ] );
			}
		}

		/* Store a session variable and then check it on the next page load to make sure PHP sessions are working */
		if( !isset( \IPS\Request::i()->sessionCheck ) )
		{
			$_SESSION['sessionCheck'] = TRUE;
			\IPS\Output::i()->redirect( \IPS\Request::i()->url()->setQueryString( 'sessionCheck', 1 ), NULL, 307 ); // 307 instructs the browser to resubmit the form as a POST request maintaining all the values from before
		}
		else
		{
			if( !isset( $_SESSION['sessionCheck'] ) OR !$_SESSION['sessionCheck'] )
			{
				\IPS\Output::i()->error( 'session_check_fail', '5C289/1', 500, '' );
			}
		}

		/* Are we automatically logging in? */
		if ( isset( \IPS\Request::i()->adsess ) )
		{
			$session = \IPS\Db::i()->select( '*', 'core_sys_cp_sessions', array( 'session_id=?',  \IPS\Request::i()->adsess ) )->first();
			$member = $session['session_member_id'] ? \IPS\Member::load( $session['session_member_id'] ) : new \IPS\Member;
			if ( $member->member_id and $this->_memberHasUpgradePermission( $member ) and ( !\IPS\Settings::i()->match_ipaddress or ( $session['session_ip_address'] === \IPS\Request::i()->ipAddress() ) ) )
			{
				$_SESSION['uniqueKey']	= \IPS\Login::generateRandomString();
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "controller=systemcheck" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
			}
		}
		
		$login = new \IPS\Login( \IPS\Http\Url::internal( "controller=login&start=1", NULL, NULL, NULL, \IPS\Settings::i()->logins_over_https ) );
		$login->flagOptions	= FALSE;
				
		/* < 4.0.0 */
		$legacy = FALSE;
		if( \IPS\Db::i()->checkForTable( 'login_methods' ) )
		{
			$legacy = TRUE;
		}
		
		/* Restoring a part finished upgrade means no log in hander rows even though table has been renamed */
		if ( \IPS\Db::i()->checkForTable( 'core_login_handlers' ) )
		{
			$legacy = FALSE;
			
			if ( ! \IPS\Db::i()->select( 'COUNT(*)', 'core_login_handlers' )->first() )
			{
				$legacy = TRUE;
			}
		}
		
		if ( $legacy === TRUE )
		{
			/* Force internal only as we don't have the framework installed (JS/templates, etc) at this point to run external log in modules */
			\IPS\Login\LoginAbstract::$databaseTable = 'login_methods';
			
			$login::$allHandlers['internal'] = \IPS\Login\LoginAbstract::constructFromData( array (
				'login_key'      => 'Upgrade',
				'login_enabled'  => 1,
				'login_settings' => '{"auth_types":"3"}',
				'login_order'    => 1,
				'login_acp'      => 1
			) );
			
			$login::$handlers = $login::$allHandlers;
		}

		$handlers = \IPS\Login::handlers();

		/* Process */
		$error = NULL;
		try
		{
			$member = $login->authenticate();
			if ( $member !== NULL )
			{
				/* Check permission */
				if ( !$this->_memberHasUpgradePermission( $member ) )
				{
					throw new \DomainException('login_upgrader_no_permission');
				}
				
				/* Create a unique session key and redirect */
				$_SESSION['uniqueKey']	= \IPS\Login::generateRandomString();

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "controller=systemcheck" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
			}
		}
		catch ( \Exception $e )
		{
			$error = $e->getMessage();
		}

		/* Output */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('login');
		\IPS\Output::i()->output 	.= \IPS\Theme::i()->getTemplate( 'forms' )->login( $login->forms( TRUE ), $error );
	}
	
	/**
	 * Can member log into upgrader?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	bool
	 */
	protected function _memberHasUpgradePermission( \IPS\Member $member )
	{
		/* 4.x */
		if ( \IPS\Db::i()->checkForTable( 'core_admin_permission_rows' ) )
		{
			/* This permission was added in 4.1.6, so if we have it, use it */
			if ( \IPS\Application::load('core')->long_version >= 101021 )
			{
				return $member->hasAcpRestriction( 'core', 'overview', 'upgrade_manage' );
			}
			/* Otherwise, let them in if they're an admin */
			else
			{
				return $member->isAdmin();
			}
		}
		/* 3.x */
		else
		{
			return (bool) \IPS\Db::i()->select( 'g_access_cp', 'groups', array( 'g_id=?', $member->member_group_id ) )->first();
		}
	}		
}