<?php
/**
 * @brief		Member Sync for IPS Connect actions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\MemberSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Sync for IPS Connect actions
 */
class _ipsconnect
{
	/**
	 * Member has logged on
	 *
	 * @param	\IPS\Member	$member		Member that logged in
	 * @param	\IPS\Http\Url	$redirectUrl	The URL to send the user back to
	 * @return	void
	 */
	public function onLogin( $member, $returnUrl )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		/* We will redirect to our normal end point to handle this */
		\IPS\Output::i()->redirect( 
			\IPS\Http\Url::external( \IPS\Settings::i()->base_url . '/applications/core/interface/ipsconnect/ipsconnect.php' )->setQueryString( 
				array( 
					'do'		=> 'crossLogin', 
					'key'		=> md5( ( \IPS\CONNECT_MASTER_KEY ?: md5( md5( \IPS\Settings::i()->sql_user . \IPS\Settings::i()->sql_pass ) . \IPS\Settings::i()->board_start ) ) . $member->member_id ), 
					'url'		=> \IPS\Settings::i()->base_url . '/applications/core/interface/ipsconnect/ipsconnect.php', 
					'id'		=> $member->member_id, 
					'returnTo'	=> (string) $returnUrl
				)
			)
		);
	}

	/**
	 * Member has logged out
	 *
	 * @param	\IPS\Member		$member			Member that logged out
	 * @param	\IPS\Http\Url	$redirectUrl	The URL to send the user back to
	 * @return	void
	 */
	public function onLogout( $member, $returnUrl )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		\IPS\Output::i()->redirect( 
			\IPS\Http\Url::external( \IPS\Settings::i()->base_url . '/applications/core/interface/ipsconnect/ipsconnect.php' )->setQueryString( 
				array( 
					'do'		=> 'logout', 
					'key'		=> md5( ( \IPS\CONNECT_MASTER_KEY ?: md5( md5( \IPS\Settings::i()->sql_user . \IPS\Settings::i()->sql_pass ) . \IPS\Settings::i()->board_start ) ) . $member->member_id ), 
					'url'		=> \IPS\Settings::i()->base_url . '/applications/core/interface/ipsconnect/ipsconnect.php', 
					'id'		=> $member->member_id, 
					'returnTo'	=> (string) $returnUrl
				)
			)
		);
	}

	/**
	 * Member has validated
	 *
	 * @param	\IPS\Member	$member		Member validated
	 * @return	void
	 */
	public function onValidate( $member )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		$this->_propagateRequest( array( 
			'do'			=> 'validate', 
			'key'			=> '_SERVER_ID_', 
			'id'			=> $member->member_id, 
		) );
	}

	/**
	 * Member is merged with another member
	 *
	 * @param	\IPS\Member	$member		Member being kept
	 * @param	\IPS\Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( $member, $member2 )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		$this->_propagateRequest( array( 
			'do'			=> 'merge', 
			'key'			=> '_SERVER_ID_', 
			'id'			=> $member->member_id, 
			'remove'		=> $member2->member_id, 
		) );
	}
	
	/**
	 * Member is deleted
	 *
	 * @param	$member	\IPS\Member	Member being deleted
	 * @return	void
	 */
	public function onDelete( $member )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		$this->_propagateRequest( array( 
			'do'			=> 'delete', 
			'key'			=> '_SERVER_ID_', 
			'id'			=> $member->member_id, 
		) );
	}

	/**
	 * Member account has been created
	 *
	 * @param	$member	\IPS\Member	New member account
	 * @return	void
	 */
	public function onCreateAccount( $member )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		$this->_propagateRequest( array( 
			'do'			=> 'register', 
			'key'			=> '_SERVER_',
			'id'			=> $member->member_id,
			'name'			=> $member->name, 
			'email'			=> $member->email, 
			'pass_hash'		=> $member->members_pass_hash, 
			'pass_salt'		=> $member->members_pass_salt, 
			'revalidateUrl'	=> ( $member->members_bitoptions['validating'] == TRUE ) ? (string) \IPS\Http\Url::internal( "", 'front' ) : '' 
		) );
	}

	/**
	 * Member account has been updated
	 *
	 * @param	$member		\IPS\Member	Member updating profile
	 * @param	$changes	array		The changes
	 * @return	void
	 */
	public function onProfileUpdate( $member, $changes )
	{
		if( !$this->_checkStatus( $member ) )
		{
			return;
		}

		if( !count( $changes ) )
		{
			return;
		}

		/* We need to figure out what has changed first - not all changes are propagated */
		/* Email address */
		if( isset( $changes['email'] ) )
		{
			$this->_propagateRequest( array( 
				'do'			=> 'changeEmail', 
				'key'			=> '_SERVER_ID_', 
				'id'			=> $member->member_id, 
				'email'			=> $changes['email']
			) );
		}

		/* Username */
		if( \IPS\CONNECT_NOSYNC_NAMES !== TRUE )
		{
			if( isset( $changes['name'] ) )
			{
				$this->_propagateRequest( array( 
					'do'			=> 'changeName', 
					'key'			=> '_SERVER_ID_', 
					'id'			=> $member->member_id, 
					'name'			=> $changes['name']
				) );
			}
		}

		/* Password */
		if( isset( $changes['members_pass_hash'] ) )
		{
			$this->_propagateRequest( array( 
				'do'			=> 'changePassword', 
				'key'			=> '_SERVER_ID_', 
				'id'			=> $member->member_id, 
				'pass_hash'		=> $changes['members_pass_hash'],
				'pass_salt'		=> $changes['members_pass_salt'],
			) );
		}

		/* Ban status */
		if( isset( $changes['temp_ban'] ) )
		{
			$this->_propagateRequest( array( 
				'do'			=> 'ban', 
				'key'			=> '_SERVER_ID_', 
				'id'			=> $member->member_id, 
				'status'		=> ( $changes['temp_ban'] == -1 ) ? 1 : 0,
			) );
		}
	}

	/**
	 * We only need to push the requests to our slaves if we are the master and if the request
	 *	was not triggered by a connect call.
	 *
	 * @param	$member	\IPS\Member|NULL	Optional member account to check
	 * @return bool
	 */
	protected function _checkStatus( $member = NULL )
	{
		if( !\IPS\Settings::i()->connect_slaves )
		{
			return FALSE;
		}
		else
		{
			/* Was the change triggered by a connect call already? */
			if( mb_strrpos( $_SERVER['SCRIPT_FILENAME'], 'ipsconnect.php' ) !== FALSE )
			{
				return FALSE;
			}

			/* Are we also testing a member account? */
			if( $member !== NULL )
			{
				foreach( \IPS\Login::handlers( TRUE ) as $key => $handler )
				{
					if( $key == 'Internal' )
					{
						if( !$handler->canProcess( $member ) )
						{
							return FALSE;
						}

						break;
					}
				}
			}

			return TRUE;
		}
	}

	/**
	 * Propagate a request to all registered slaves
	 *
	 * @param	array	$queryString	The query string to send
	 * @return	void
	 */
	protected function _propagateRequest( $queryString )
	{
		$select	= \IPS\Db::i()->select( '*', 'core_ipsconnect_slaves' );

		if( $select->count() )
		{
			foreach( $select as $row )
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