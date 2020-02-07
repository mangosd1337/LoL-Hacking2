<?php
/**
 * @brief		Login
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login
 */
class _login extends \IPS\Dispatcher\Controller
{
	/**
	 * Log In
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Did we just log in? */
		if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->_fromLogin ) )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('') );
		}
		
		/* Force HTTPs? */
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->logins_over_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=login", 'front', 'login', NULL, \IPS\Settings::i()->logins_over_https ) );
		}
		
		/* Init login class */
		$login = new \IPS\Login( \IPS\Http\Url::internal( "app=core&module=system&controller=login", 'front', 'login', NULL, \IPS\Settings::i()->logins_over_https ) );

		/* Process */
		$error = isset( \IPS\Request::i()->_err ) ? \IPS\Request::i()->_err : NULL;
		try
		{
			$member = $login->authenticate();
			if ( $member !== NULL )
			{
				$this->_doLogin( $member, $login->flags['signin_anonymous'], $login->flags['remember_me'] );
				
				if ( \IPS\Request::i()->isAjax() )
				{
					$response	= array( 'status' => 'success' );

					if( !empty( \IPS\Request::i()->ref ) )
					{
						$response['redirect']	= \IPS\Request::i()->ref;
					}

					\IPS\Output::i()->json( $response );
				}
				else
				{
					\IPS\Output::i()->redirect( \IPS\Login::getDestination()->setQueryString( '_fromLogin', 1 ) );
				}
			}
		}
		catch ( \IPS\Login\Exception $e )
		{
			if ( $e->getCode() === \IPS\Login\Exception::MERGE_SOCIAL_ACCOUNT )
			{
				$e->member = $e->member->member_id;
				$_SESSION['linkAccounts'] = json_encode( $e );
				
				$linkUrl = \IPS\Http\Url::internal( 'app=core&module=system&controller=login&do=link', 'front', 'login' );
				if ( isset( \IPS\Request::i()->ref ) )
				{
					$linkUrl = $linkUrl->setQueryString( 'ref', \IPS\Request::i()->ref );
				}				
				\IPS\Output::i()->redirect( $linkUrl );
			}
			
			$error = $e->getMessage();
		}

		if ( \IPS\Request::i()->isAjax() && $error )
		{
			\IPS\Output::i()->json( array( 'status' => 'error', 'error' => $error ), 401 );			
		}
		else
		{
			/* Display Login Form */
			\IPS\Output::i()->allowDefaultWidgets = FALSE;
			\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
			\IPS\Output::i()->sidebar['enabled'] = FALSE;
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('login');
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->login( $login->forms(), $error );
		}
		
		/* Set Session Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', NULL, 'login' ), array(), 'loc_logging_in' );
	}
	
	/**
	 * Process the login
	 *
	 * @param	\IPS\Member	$member		The member
	 * @param	bool		$anonymous	If the login is anonymous
	 * @param	bool		$rememberMe	If the "remember me" checkbox was checked
	 * @return	void
	 */
	protected function _doLogin( $member, $anonymous=FALSE, $rememberMe=TRUE )
	{
		if ( $anonymous and !\IPS\Settings::i()->disable_anonymous )
		{
			\IPS\Session::i()->setAnon();
			\IPS\Request::i()->setCookie( 'anon_login', 1 );
		}
		
		\IPS\Session::i()->setMember( $member );
		
		if ( $rememberMe )
		{
			$expire = new \IPS\DateTime;
			$expire->add( new \DateInterval( 'P7D' ) );
			\IPS\Request::i()->setCookie( 'member_id', $member->member_id, $expire );
			\IPS\Request::i()->setCookie( 'pass_hash', $member->member_login_key, $expire );

			if ( $anonymous and !\IPS\Settings::i()->disable_anonymous )
			{
				\IPS\Request::i()->setCookie( 'anon_login', 1, $expire );
			}
		}

		$member->memberSync( 'onLogin', array( \IPS\Login::getDestination() ) );
	}
	
	/**
	 * Link Accounts
	 *
	 * @return	void
	 */
	protected function link()
	{
		if ( !isset( $_SESSION['linkAccounts'] ) )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		$details = json_decode( $_SESSION['linkAccounts'], TRUE );
				
		$member = \IPS\Member::load( $details['member'] );
		if ( !$member->member_id )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ) );
		}
		
		$form = new \IPS\Helpers\Form( 'link_accounts', 'login' );
		if ( isset( \IPS\Request::i()->ref ) )
		{
			$form->hiddenValues['ref'] = \IPS\Request::i()->ref;
		}
		$form->addDummy( 'email_address', htmlspecialchars( isset( $details['email'] ) ? $details['email'] : $member->email, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) );
		$form->add( new \IPS\Helpers\Form\Password( 'password', NULL, TRUE, array( 'validateFor' => $member ) ) );
		if ( $values = $form->values() )
		{
			try
			{
				$class = 'IPS\Login\\' . ucfirst( $details['handler'] );
				$class::link( $member, $details['details'] );
				
				unset( $_SESSION['linkAccounts'] );			
				$this->_doLogin( $member );
				
				if ( isset( \IPS\Request::i()->ref ) )
				{
					try
					{
						$ref = new \IPS\Http\Url( base64_decode( \IPS\Request::i()->ref ) );
						if ( $ref->isInternal )
						{
							\IPS\Output::i()->redirect( $ref );
						}
					}
					catch ( \Exception $e ) { }
				}
				
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
			}
			catch ( \IPS\Login\Exception $e )
			{
				$form->error = $e->getMessage();
			}			
		}
		
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('login');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->mergeSocialAccount( $details['handler'], $member, $form );
	}
	
	/**
	 * Log Out
	 *
	 * @return void
	 */
	protected function logout()
	{
		\IPS\Session::i()->csrfCheck();
		
		if( !empty( $_SERVER['HTTP_REFERER'] ) )
		{
			$referrer = new \IPS\Http\Url( $_SERVER['HTTP_REFERER'] );
			$redirectUrl = ( $referrer->isInternal and ( !isset( $referrer->queryString['do'] ) or $referrer->queryString['do'] != 'validating' ) ) ? $referrer : \IPS\Http\Url::internal('');
		}
		else
		{
			$redirectUrl = \IPS\Http\Url::internal( '' );
		}

		$member			= \IPS\Member::loggedIn();
		
		/* Are we logging out back to an admin user? */
		if( isset( $_SESSION['logged_in_as_key'] ) )
		{
			$key = $_SESSION['logged_in_as_key'];
			unset( \IPS\Data\Store::i()->$key );
			unset( $_SESSION['logged_in_as_key'] );
			unset( $_SESSION['logged_in_from'] );
			
			\IPS\Output::i()->redirect( $redirectUrl );
		}
		
		\IPS\Request::i()->setCookie( 'member_id', NULL );
		\IPS\Request::i()->setCookie( 'pass_hash', NULL );
		\IPS\Request::i()->setCookie( 'anon_login', NULL );

		foreach( \IPS\Request::i()->cookie as $name => $value )
		{
			if( mb_strpos( $name, "ipbforumpass_" ) !== FALSE )
			{
				\IPS\Request::i()->setCookie( $name, NULL );
			}
		}
		
		session_destroy();

		/* Login handler callback */
		foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
		{
			try
			{
				$handler->logoutAccount( $member, $redirectUrl );
			}
			catch( \BadMethodCallException $e ) {}
		}

		/* Member sync callback */
		$member->memberSync( 'onLogout', array( $redirectUrl ) );
		
		\IPS\Output::i()->redirect( $redirectUrl->setQueryString( '_fromLogout', 1 ) );
	}
	
	/**
	 * Log in as user
	 *
	 * @return void
	 */
	protected function loginas()
	{
		if ( !\IPS\Request::i()->key or \IPS\Data\Store::i()->admin_login_as_user != \IPS\Request::i()->key )
		{
			\IPS\Output::i()->error( 'invalid_login_as_user_key', '3S167/1', 403, '' );
		}
	
		/* Load member and admin user */
		$member = \IPS\Member::load( \IPS\Request::i()->id );
		$admin 	= \IPS\Member::load( \IPS\Request::i()->admin );
		
		/* Not logged in as admin? */
		if ( $admin->member_id != \IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=login", 'front', 'login', NULL, \IPS\Settings::i()->logins_over_https )->setQueryString( array( 'ref' => base64_encode( \IPS\Request::i()->url() ), '_err' => 'login_as_user_login' ) ) );
		}
		
		/* Do it */
		$_SESSION['logged_in_from']			= array( 'id' => $admin->member_id, 'name' => $admin->name );
		$unique_id							= \IPS\Login::generateRandomString();
		$_SESSION['logged_in_as_key']		= $unique_id;
		\IPS\Data\Store::i()->$unique_id	= $member->member_id;
		
		/* Ditch the key */
		unset( \IPS\Data\Store::i()->admin_login_as_user );
		
		/* Redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
	}
}