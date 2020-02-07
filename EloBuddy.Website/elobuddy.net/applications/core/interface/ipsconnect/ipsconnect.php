<?php
/**
 * @brief		IPS Connect remote connections API
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		30 May 2013
 * @version		SVN_VERSION_NUMBER
 * @note		Backwards compatibility is not possible due to the way password storage has changed
 */

require_once str_replace( 'applications/core/interface/ipsconnect/ipsconnect.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

/**
 * IPS Connect remote connections API class
 */
class ipsConnect
{
	/**
	 * @brief	Our URL
	 */
	protected $url	= '';

	/**
	 * @brief	Our Key
	 */
	protected $key	= '';

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		/* Set up some things we will use */
		\IPS\Session\Front::i();
		$this->url	= \IPS\Http\Url::internal( 'applications/core/interface/ipsconnect/ipsconnect.php', 'none' );
		$this->key	= \IPS\CONNECT_MASTER_KEY ?: md5( md5( \IPS\Settings::i()->sql_user . \IPS\Settings::i()->sql_pass ) . \IPS\Settings::i()->board_start );
	}

	/**
	 * Dispatcher
	 *
	 * @return	void
	 * @note	Any methods may be remotely callable unless they are prefixed with an underscore
	 */
	public function dispatch()
	{
		/* What are we doing? */
		$action	= \IPS\Request::i()->do;

		/* If this is a slave call...make sure we're actually a slave */
		if( \IPS\Request::i()->slaveCall == 1 )
		{
			$noJson = ( $action == 'crossLogin' OR $action == 'logout' );
			
			try
			{
				$connect	= \IPS\Db::i()->select( '*', 'core_login_handlers', array( 'login_key=?', 'Ipsconnect' ) )->first();

				if( !$connect['login_enabled'] )
				{	
					$this->_output( "DISABLED", array(), $noJson );
				}
			}
			catch( \UnderflowException $e )
			{
				$this->_output( "DISABLED", array(), $noJson );
			}
		}

		if( mb_strpos( $action, '_' ) !== 0 AND method_exists( $this, $action ) )
		{
			$params	= $this->$action();

			$this->_output( "SUCCESS", ( is_array( $params ) ) ? $params : array() );
		}
		else
		{
			$this->_log( "INVALID_ACTION" );
			$this->_output( "INVALID_ACTION" );
		}
	}

	/**
	 * Verify the application settings and register the slave in our database
	 *
	 * @return	NULL
	 */
	public function verifySettings()
	{
		$this->_genericKeyCheck();

		\IPS\Db::i()->replace( 'core_ipsconnect_slaves', array( 'slave_url' => \IPS\Request::i()->url, 'slave_key' => \IPS\Request::i()->ourKey, 'slave_last_access' => time() ) );
		\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Db::i()->select( 'COUNT(*)', 'core_ipsconnect_slaves' )->first() ), array( 'conf_key=?', 'connect_slaves' ) );
		unset( \IPS\Data\Store::i()->settings );

		$this->_log( "NEW_SLAVE_REGISTERED", (array) \IPS\Request::i() );

		return NULL;
	}

	/**
	 * Fetch the user's password salt in order to allow the slave to generate the correct password hash
	 *
	 * @return	array
	 */
	public function fetchSalt()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		if( !\IPS\Request::i()->id OR !\IPS\Request::i()->idType )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}

		if( \IPS\Request::i()->idType == 1 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'name' );
		}
		else if( \IPS\Request::i()->idType == 2 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'email' );
		}
		else if( \IPS\Request::i()->idType == 3 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'email' );

			if( !$member->member_id )
			{
				$member		= \IPS\Member::load( \IPS\Request::i()->id, 'name' );
			}
		}

		if( !$member->member_id )
		{
			$this->_output( "ACCOUNT_NOT_FOUND" );
		}

		return array( 'pass_salt' => $member->members_pass_salt );
	}

	/**
	 * Validate the user's login credentials
	 *
	 * @return	array
	 */
	public function login()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		if( !\IPS\Request::i()->id OR !\IPS\Request::i()->idType )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}

		if( \IPS\Request::i()->idType == 1 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'name' );
		}
		else if( \IPS\Request::i()->idType == 2 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'email' );
		}
		else if( \IPS\Request::i()->idType == 3 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'email' );

			if( !$member->member_id )
			{
				$member		= \IPS\Member::load( \IPS\Request::i()->id, 'name' );
			}
		}
		else
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->idType );
		}
		
		if( !$member->member_id )
		{
			$this->_output( "ACCOUNT_NOT_FOUND" );
		}

		if ( $member->members_pass_hash != \IPS\Request::i()->password )
		{
			$this->_log( "WRONG_AUTH", $member );
			$this->_output( "WRONG_AUTH" );
		}

		$this->_log( \IPS\Request::i()->fetchId ? "CONNECT_ID_FOUND" : "SUCCESSFUL_LOGIN", $member );

		return array(
			'connect_status'			=> ( $member->members_bitoptions['validating'] ) ? 'VALIDATING' : 'SUCCESS',
			'email'						=> $member->email,
			'name'						=> $member->name,
			'connect_id'				=> $member->member_id,
			'connect_revalidate_url'	=> $member->ipsconnect_revalidate_url,
		);
	}
	
	/**
	 * Get a user's IPSConnect ID
	 *
	 * @return	array
	 */
	public function fetchId()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );
		
		if( !\IPS\Request::i()->id OR !\IPS\Request::i()->idType )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}
		
		if( \IPS\Request::i()->idType == 1 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'name' );
		}
		else if( \IPS\Request::i()->idType == 2 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'email' );
		}
		else if( \IPS\Request::i()->idType == 3 )
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, 'email' );

			if( !$member->member_id )
			{
				$member		= \IPS\Member::load( \IPS\Request::i()->id, 'name' );
			}
		}
		else
		{
			$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->idType );
		}
		
		if( !$member->member_id )
		{
			$this->_output( "ACCOUNT_NOT_FOUND" );
		}
		
		return array(
			'connect_id' => $member->member_id,
		);
	}

	/**
	 * Log the user in to each slave installation one by one
	 *
	 * @return	void
	 * @note	A user will actually reach this destination, it is not a server-to-server call endpoint
	 */
	public function crossLogin()
	{
		if ( !$this->_genericKeyCheck( \IPS\Request::i()->id, TRUE, (bool) \IPS\Request::i()->slaveCall ) )
		{
			\IPS\Output::i()->redirect( \IPS\Request::i()->returnTo );
		}

		if( !\IPS\Request::i()->id )
		{
			$this->_output( "REQUEST_MISSING_DATA", array(), TRUE );
		}

		/* Fetch the user */
		$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		/* If we are not returning to master from a slave (i.e. first hit to master, or a hit on a slave) log the user in */
		if( !\IPS\Request::i()->slaveReturn AND $member->member_id )
		{
			\IPS\Session::i()->setMember( $member );

			$member->checkLoginKey();

			$expire = new \IPS\DateTime;
			$expire->add( new \DateInterval( 'P1Y' ) );
			\IPS\Request::i()->setCookie( 'member_id', $member->member_id, $expire );
			\IPS\Request::i()->setCookie( 'pass_hash', $member->member_login_key, $expire );
		}

		/* If this was a slave call, we just need to return to the master now */
		if( \IPS\Request::i()->slaveCall )
		{
			\IPS\Output::i()->redirect( \IPS\Request::i()->returnTo );
		}
		/* This means we're at the master...figure out which slaves we need to send to */
		else
		{
			$start	= (int) \IPS\Request::i()->start;
			$select	= \IPS\Db::i()->select( '*', 'core_ipsconnect_slaves', array( 'slave_url!=?', \IPS\Request::i()->url ), 'slave_id', array( $start, 1 ) );

			if( $select->count() )
			{
				foreach( $select as $row )
				{
					$start++;

					$url	= (string) \IPS\Http\Url::external( $row['slave_url'] )->setQueryString( 
						array( 
							'do'		=> 'crossLogin', 
							'key'		=> md5( $row['slave_key'] . $member->member_id ), 
							'id'		=> $member->member_id, 
							'slaveCall'	=> 1,
							'url'		=> \IPS\Request::i()->url,
							'returnTo'	=> (string) \IPS\Http\Url::internal( 'applications/core/interface/ipsconnect/ipsconnect.php', 'none' )->setQueryString( 
								array( 
									'do'			=> 'crossLogin', 
									'key'			=> \IPS\Request::i()->key, 
									'id'			=> \IPS\Request::i()->id, 
									'slaveReturn'	=> 1,
									'start'			=> $start,
									'origReturn'	=> \IPS\Request::i()->origReturn ?: \IPS\Request::i()->returnTo
								)
							)
						)
					);

					\IPS\Output::i()->redirect( $url );
				}
			}

			/* Callback capability for custom class extensions */
			if( method_exists( $this, '_postCrossLogin' ) )
			{
				$this->_postCrossLogin();
			}

			/* We have hit all the slave installs now, time to go home */
			\IPS\Output::i()->redirect( \IPS\Request::i()->origReturn ?: \IPS\Request::i()->returnTo );
		}
	}

	/**
	 * Log the user out of each installation
	 *
	 * @return	void
	 * @note	A user will actually reach this destination, it is not a server-to-server call endpoint
	 */
	public function logout()
	{
		if ( !$this->_genericKeyCheck( \IPS\Request::i()->id, TRUE, (bool) \IPS\Request::i()->slaveCall ) )
		{
			\IPS\Output::i()->redirect( \IPS\Request::i()->returnTo );
		}

		if( !\IPS\Request::i()->id )
		{
			$this->_output( "REQUEST_MISSING_DATA", array(), TRUE );
		}

		/* Fetch the user */
		$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		/* If this is a slave hit log the user out */
		if( \IPS\Request::i()->slaveCall AND $member->member_id )
		{
			\IPS\Session\Front::i();
			\IPS\Request::i()->setCookie( 'member_id', NULL );
			\IPS\Request::i()->setCookie( 'pass_hash', NULL );
			session_destroy();
		}

		/* If this was a slave call, we just need to return to the master now */
		if( \IPS\Request::i()->slaveCall )
		{
			\IPS\Output::i()->redirect( \IPS\Request::i()->returnTo );
		}
		/* This means we're at the master...figure out which slaves we need to send to */
		else
		{
			$start	= (int) \IPS\Request::i()->start;
			$select	= \IPS\Db::i()->select( '*', 'core_ipsconnect_slaves', array( 'slave_url!=?', \IPS\Request::i()->url ), 'slave_id', array( $start, 1 ) );

			if( $select->count() )
			{
				foreach( $select as $row )
				{
					$start++;

					$url	= (string) \IPS\Http\Url::external( $row['slave_url'] )->setQueryString( 
						array( 
							'do'		=> 'logout', 
							'key'		=> md5( $row['slave_key'] . $member->member_id ), 
							'id'		=> $member->member_id, 
							'slaveCall'	=> 1,
							'url'		=> \IPS\Request::i()->url,
							'returnTo'	=> (string) \IPS\Http\Url::internal( 'applications/core/interface/ipsconnect/ipsconnect.php', 'none' )->setQueryString( 
								array( 
									'do'			=> 'logout', 
									'key'			=> \IPS\Request::i()->key, 
									'id'			=> \IPS\Request::i()->id, 
									'slaveReturn'	=> 1,
									'start'			=> $start,
									'origReturn'	=> \IPS\Request::i()->origReturn ?: \IPS\Request::i()->returnTo
								)
							)
						)
					);

					\IPS\Output::i()->redirect( $url );
				}
			}

			/* Callback capability for custom class extensions */
			if( method_exists( $this, '_postCrossLogout' ) )
			{
				$this->_postCrossLogout();
			}

			/* Log out of master */
			\IPS\Session\Front::i();
			\IPS\Request::i()->setCookie( 'member_id', NULL );
			\IPS\Request::i()->setCookie( 'pass_hash', NULL );
			session_destroy();

			/* We have hit all the slave installs now, time to go home */
			\IPS\Output::i()->redirect( \IPS\Request::i()->origReturn ?: \IPS\Request::i()->returnTo );
		}
	}

	/**
	 * Create the user's account in the local database
	 *
	 * @return	NULL
	 */
	public function register()
	{
		$this->_genericKeyCheck();

		if( !\IPS\Request::i()->name OR !\IPS\Request::i()->email OR !\IPS\Request::i()->pass_hash OR !\IPS\Request::i()->pass_salt )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}
		
		/* Does this user already exist? */
		$member = \IPS\Member::load( \IPS\Request::i()->email, 'email' );

		if ( !$member->member_id )
		{
			$member								= new \IPS\Member;
			$member->name						= \IPS\Request::i()->name;
			$member->email						= \IPS\Request::i()->email;
			$member->member_group_id			= \IPS\Settings::i()->member_group;
			$member->members_pass_hash			= \IPS\Request::i()->pass_hash;
			$member->members_pass_salt			= \IPS\Request::i()->pass_salt;
			if ( isset( \IPS\Request::i()->id ) and \IPS\Request::i()->id )
			{
				$member->ipsconnect_id				= \IPS\Request::i()->id;
			}
			$member->ipsconnect_revalidate_url	= \IPS\Request::i()->revalidateUrl;
			$member->save();
			$this->_log( "CREATED_ACCOUNT", $member );
		}
		else
		{
			$this->_log( "ACCOUNT_EXISTS", $member );
		}

		/* Do we have any slaves to call? */
		$this->_propagateRequest( array( 'do' => 'register', 'key' => '_SERVER_', 'name' => $member->name, 'email' => $member->email, 'pass_hash' => $member->members_pass_hash, 'pass_salt' => $member->members_pass_salt, 'revalidateUrl' => \IPS\Request::i()->revalidateUrl ) );

		return array( 'connect_id' => $member->member_id );
	}

	/**
	 * Validate the user
	 *
	 * @return	NULL
	 */
	public function validate()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		$member	= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $member->member_id )
		{
			$member->ipsconnect_revalidate_url	= '';
			$member->save();

			$member->validationComplete();

			$this->_log( "VALIDATED_ACCOUNT", $member );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'validate', 'key' => '_SERVER_ID_', 'id' => $member->member_id ) );
		}

		return NULL;
	}

	/**
	 * Delete an account
	 *
	 * @return	NULL
	 */
	public function delete()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		$member	= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $member->member_id )
		{
			$member->delete();

			$this->_log( "DELETED_ACCOUNT", $member );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'delete', 'key' => '_SERVER_ID_', 'id' => $member->member_id ) );
		}

		return NULL;
	}

	/**
	 * Ban an account
	 *
	 * @return	NULL
	 */
	public function ban()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		$member	= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $member->member_id )
		{
			$member->temp_ban	= ( \IPS\Request::i()->status == 1 ) ? -1 : 0;
			$member->save();

			$this->_log( "BANNED_ACCOUNT", $member );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'ban', 'key' => '_SERVER_ID_', 'id' => $member->member_id, 'status' => \IPS\Request::i()->status ) );
		}

		return NULL;
	}

	/**
	 * Merge two accounts together
	 *
	 * @return	NULL
	 */
	public function merge()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		/* Merge the accounts */
		$memberToKeep	= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );
		$memberToRemove	= \IPS\Member::load( \IPS\Request::i()->remove, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $memberToKeep->member_id AND $memberToRemove->member_id )
		{
			$memberToKeep->merge( $memberToRemove );

			$this->_log( "MERGED_ACCOUNT", array( 'member' => $memberToKeep, 'removed' => $memberToRemove ) );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'merge', 'key' => '_SERVER_ID_', 'id' => $memberToKeep->member_id, 'remove' => $memberToRemove->member_id ) );

			/* Remove the second account */
			$memberToRemove->delete();
		}

		return NULL;
	}

	/**
	 * Check if a given email address is being used
	 *
	 * @return	array
	 */
	public function checkEmail()
	{
		$this->_genericKeyCheck();

		$member	= \IPS\Member::load( \IPS\Request::i()->email, 'email' );

		return array( 'used' => $member->member_id ? 1 : 0 );
	}

	/**
	 * Check if a given name is being used
	 *
	 * @return	array
	 */
	public function checkName()
	{
		$this->_genericKeyCheck();

		$member	= \IPS\Member::load( \IPS\Request::i()->name, 'name' );

		return array( 'used' => $member->member_id ? 1 : 0 );
	}

	/**
	 * Change a user's email address
	 *
	 * @return	NULL
	 */
	public function changeEmail()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		if( !\IPS\Request::i()->email )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}

		$check		= \IPS\Member::load( \IPS\Request::i()->email, 'email' );
		$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $check->member_id )
		{
			$this->_log( "BAD_EMAIL_CHANGE_ATTEMPT", array( 'member' => $member, 'usedBy' => $check ) );
			$this->_output( "EMAIL_IN_USE" );
		}

		if( $member->member_id )
		{
			$oldEmail		= $member->email;

			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				try
				{
					$handler->changeEmail( $member, $oldEmail, \IPS\Request::i()->email );
				}
				catch( \BadMethodCallException $e ) {}
			}

			$this->_log( "CHANGED_EMAIL", array( 'member' => $member, 'oldEmail' => $oldEmail ) );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'changeEmail', 'key' => '_SERVER_ID_', 'id' => $member->member_id, 'email' => \IPS\Request::i()->email ) );
		}

		return NULL;
	}

	/**
	 * Update a user's password
	 *
	 * @return	NULL
	 */
	public function changePassword()
	{
		$this->_genericKeyCheck( \IPS\Request::i()->id );

		if( !\IPS\Request::i()->pass_salt OR ! \IPS\Request::i()->pass_hash )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}

		$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $member->member_id )
		{
			$member->members_pass_salt	= \IPS\Request::i()->pass_salt;
			$member->members_pass_hash	= \IPS\Request::i()->pass_hash;
			$member->member_login_key	= '';
			$member->save();

			$this->_log( "CHANGED_PASSWORD", $member );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'changePassword', 'key' => '_SERVER_ID_', 'id' => $member->member_id, 'pass_hash' => \IPS\Request::i()->pass_hash, 'pass_salt' => \IPS\Request::i()->pass_salt ) );
		}

		return NULL;
	}

	/**
	 * Change a user's name
	 *
	 * @return	NULL
	 */
	public function changeName()
	{
		if( \IPS\CONNECT_NOSYNC_NAMES === TRUE )
		{
			return NULL;
		}

		$this->_genericKeyCheck( \IPS\Request::i()->id );

		if( !\IPS\Request::i()->name )
		{
			$this->_log( "REQUEST_MISSING_DATA", (array) \IPS\Request::i() );
			$this->_output( "REQUEST_MISSING_DATA" );
		}

		$check		= \IPS\Member::load( \IPS\Request::i()->name, 'name' );
		$member		= \IPS\Member::load( \IPS\Request::i()->id, \IPS\Request::i()->slaveCall ? 'ipsconnect_id' : NULL );

		if( $check->member_id )
		{
			$this->_log( "BAD_NAME_CHANGE_ATTEMPT", array( 'member' => $member, 'usedBy' => $check ) );
			$this->_output( "USERNAME_IN_USE" );
		}

		if( $member->member_id )
		{
			$oldName		= $member->name;

			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				try
				{
					$handler->changeUsername( $member, $member->name, \IPS\Request::i()->name );
				}
				catch( \BadMethodCallException $e ) {}
			}

			$this->_log( "CHANGED_NAME", array( 'member' => $member, 'oldName' => $oldName ) );

			/* Do we have any slaves to call? */
			$this->_propagateRequest( array( 'do' => 'changeName', 'key' => '_SERVER_ID_', 'id' => $member->member_id, 'name' => \IPS\Request::i()->name ) );
		}

		return NULL;
	}

	/**
	 * Propagate a request to all registered slaves
	 *
	 * @param	array	$queryString	The query string to send
	 * @return	void
	 */
	protected function _propagateRequest( $queryString )
	{
		/* Only do this if we're not a slave that is being called */
		if( !\IPS\Request::i()->slaveCall )
		{
			$select	= \IPS\Db::i()->select( '*', 'core_ipsconnect_slaves' );

			if( $select->count() )
			{
				foreach( $select as $row )
				{
					if( $row['slave_url'] != \IPS\Request::i()->url )
					{
						$key	= '';

						if( isset( $queryString['key'] ) )
						{
							if( $queryString['key'] == '_SERVER_' )
							{
								$key	= $row['slave_key'];
							}
							else if( $queryString['key'] == '_SERVER_ID_' )
							{
								$key	= md5( $row['slave_key'] . $queryString['id'] );
							}
						}

						try
						{
							$response = \IPS\Http\Url::external( $row['slave_url'] )
								->setQueryString( array_merge( $queryString, array( 'slaveCall' => 1, 'key' => $key ?: ( ( isset( $queryString['key'] ) ) ? $queryString['key'] : '' ) ) ) )
								->request()
								->get()
								->decodeJson();

							/* If this is a registered slave but the slave is telling us Connect is disabled, remove it */
							if( isset( $response['status'] ) AND $response['status'] == 'DISABLED' )
							{
								\IPS\Db::i()->delete( 'core_ipsconnect_slaves', array( 'slave_id=?', $row['slave_id'] ) );
								\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Db::i()->select( 'COUNT(*)', 'core_ipsconnect_slaves' )->first() ), array( 'conf_key=?', 'connect_slaves' ) );
								unset( \IPS\Data\Store::i()->settings );
							}
							else if( !isset( $response['status'] ) OR $response['status'] != 'SUCCESS' )
							{
								throw new \RuntimeException( $response['status'] );
							}
						}
						catch( \RuntimeException $e )
						{
							\IPS\Db::i()->insert( 'core_ipsconnect_queue', array( 'slave_id' => $row['slave_id'], 'request_url' => http_build_query( array_merge( $queryString, array( 'slaveCall' => 1, 'key' => $key ?: ( ( isset( $queryString['key'] ) ) ? $queryString['key'] : '' ) ) ) ) ) );

							/* Make sure task is enabled */
							\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), "`key`='ipsconnect'" );
						}
					}
				}
			}
		}
	}

	/**
	 * Check the provided key matches our registered key
	 *
	 * @param	string|NULL	$extra			If provided, we should check against md5 of master key and this value
	 * @param	bool		$noJson			If TRUE, a full error screen is output
	 * @param	bool		$returnOnError	If TRUE, a bool is returned rather than an error shown on error - used when looping through slaves so if one has an error (e.g. has been disabled) we just skip over it rather than erroring out
	 * @return	bool
	 */
	protected function _genericKeyCheck( $extra=NULL, $noJson=FALSE, $returnOnError=FALSE )
	{
		if( $extra !== NULL AND !\IPS\Login::compareHashes( md5( $this->key . $extra ), (string) \IPS\Request::i()->key ) )
		{
			if ( $returnOnError )
			{
				return FALSE;
			}
			
			$this->_log( "BAD_KEY" );
			$this->_output( "BAD_KEY", array(), $noJson );
		}
		else if( $extra === NULL AND !\IPS\Login::compareHashes( (string) $this->key, (string) \IPS\Request::i()->key ) )
		{
			if ( $returnOnError )
			{
				return FALSE;
			}
			
			$this->_log( "BAD_KEY" );
			$this->_output( "BAD_KEY", array(), $noJson );
		}
		
		return TRUE;
	}

	/**
	 * Log activity as appropriate
	 *
	 * @param	string	$message	Message to log
	 * @param	array	$data		Other data to log
	 * @return	void
	 */
	protected function _log( $message, $data=NULL )
	{
		/* If we are a slave and the request is just propagating, just return - we don't need to log */
		if( \IPS\Request::i()->slaveCall )
		{
			return;
		}

		$log	= array( $message );

		if( $data !== NULL )
		{
			$log[]	= json_encode( $data );
		}

		\IPS\Log::log( implode( "\n", $log ), 'ipsconnect' );
	}

	/**
	 * Output to the screen
	 *
	 * @param	string	$status		Status of request
	 * @param	array	$data		Other data to send with the request
	 * @param	bool	$noJson		If TRUE, a full error screen is output
	 * @return	void
	 */
	protected function _output( $status, $data=array(), $noJson=FALSE )
	{
		/* Update last access time */
		if( \IPS\Request::i()->url )
		{
			\IPS\Db::i()->update( 'core_ipsconnect_slaves', array( 'slave_last_access' => time() ), array( 'slave_url=?', \IPS\Request::i()->url ) );
		}

		if( $noJson === TRUE )
		{
			\IPS\Output::i()->error( 'login_connect_generic_error', '2S165/1', 403, '' );
		}
		else
		{
			\IPS\Output::i()->json( array_merge( array( 'status' => $status ), $data ), 200 );
		}
	}
}

/* If we have a custom.php in this directory with a class ipsConnect_custom (which should extend this class) we will load that instead */
$className	= 'ipsConnect';

if( file_exists( str_replace( '/ipsconnect.php', '/custom.php', str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ) ) ) )
{
	require_once( str_replace( '/ipsconnect.php', '/custom.php', str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ) ) );

	if( class_exists( 'ipsConnect_custom' ) )
	{
		$className	= 'ipsConnect_custom';
	}
}

$connect	= new $className;
$connect->dispatch();
