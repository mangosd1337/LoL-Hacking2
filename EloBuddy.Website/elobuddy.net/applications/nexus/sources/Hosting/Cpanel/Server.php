<?php
/**
 * @brief		CPanel/WHM Server Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		6 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Hosting\Cpanel;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * CPanel/WHM Server Model
 */
class _Server extends \IPS\nexus\Hosting\Server
{
	/**
	 * Additional fields for the server form
	 *
	 * @param	\IPS\nexus\Hosting\Server	$server	The server bring edited
	 * @return	array
	 */
	public static function extraFormFields( \IPS\nexus\Hosting\Server $server )
	{
		$class = get_class();
		return array(
			'server_cpanel_username'	=> new \IPS\Helpers\Form\Text( 'server_cpanel_username', $server->username ?: 'root', NULL, array(), function( $val )
			{
				if ( !$val and \IPS\Request::i()->server_type == 'cpanel' )
				{
					throw new \DomainException('form_required');
				}
			}, NULL, NULL, 'server_cpanel_username' ),
			'server_cpanel_key'	=> new \IPS\Helpers\Form\TextArea( 'server_cpanel_key', $server->access, NULL, array( 'rows' => 20 ), function( $val ) use ( $class )
			{
				if ( \IPS\Request::i()->server_type == 'cpanel' )
				{
					if ( !$val )
					{
						throw new \DomainException('form_required');
					}
					else
					{
						$server = new $class;
						$server->hostname = \IPS\Request::i()->server_hostname;
						$server->ip = \IPS\Request::i()->server_ip;
						$server->username = \IPS\Request::i()->server_cpanel_username;
						$server->access = $val;
						$server->extra = array( 'call' => \IPS\Request::i()->server_cpanel_call );

						try
						{
							$response = $server->api( 'gethostname' );
						}
						catch ( \IPS\nexus\Hosting\Exception $e )
						{
							throw new \DomainException( $e->getMessage() );
						}

						if ( $response['hostname'] !== $server->hostname )
						{
							throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'server_cpanel_key_hostname_err', FALSE, array( 'sprintf' => array( $response['hostname'] ) ) ) );
						}

					}
				}
			}, NULL, NULL, 'server_cpanel_key' ),
			'server_cpanel_call' => new \IPS\Helpers\Form\Radio( 'server_cpanel_call', isset( $server->extra['call'] ) ? $server->extra['call'] : 'hostname', NULL, array( 'options' => array( 'hostname' => 'server_hostname', 'ip' => 'server_ip' ) ), NULL, NULL, NULL, 'server_cpanel_call' )
		);
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public static function _formatFormValues( $values )
	{
		if( isset( $values['server_cpanel_username'] ) )
		{
			$values['server_username'] = $values['server_cpanel_username'];
			unset( $values['server_cpanel_username'] );
		}

		if( isset( $values['server_cpanel_key'] ) )
		{
			$values['server_access'] = trim( $values['server_cpanel_key'] );
			unset( $values['server_cpanel_key'] );
		}

		if( isset( $values['server_cpanel_call'] ) )
		{
			$values['extra'] = array( 'call' => $values['server_cpanel_call'] );
			unset( $values['server_cpanel_call'] );
		}

		return $values;
	}
	
	/**
	 * Check if username is acceptable
	 *
	 * @param	string	$username	The username to check
	 * @return	bool
	 */
	public function checkUsername( $username )
	{
		if (
			in_array( $username, array( 'root', 'virtfs', 'roundcube', 'horde', 'spamassassin', 'eximstats', 'cphulkd', 'modsec', 'all', 'dovecot', 'tomcat', 'postgres', 'mailman', 'proftpd', 'cpbackup', 'files', 'dirs', 'tmp', 'toor', 'munin' ) )
			OR mb_substr( $username, 0, 4 ) == 'test'
			OR mb_substr( $username, -7 ) == 'assword'
		) { 
			return FALSE;
		}
				
		return true;
	}
	
	/**
	 * List Accounts
	 *
	 * @return	array	'username' => array( 'domain' => 'example.com', 'disklimit' => 100000, 'diskused' => 50000, 'active' => TRUE )
	 */
	public function listAccounts()
	{
		$return = array();
	
		$response = $this->api( 'listaccts' );		
		if( !$response['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['statusmsg'] );
		}
		
		foreach ( $response['acct'] as $account )
		{
			$return[ $account['user'] ] = array(
				'domain'	=> $account['domain'],
				'disklimit'	=> intval( str_replace( 'M', '', $account['disklimit'] ) ) * 1000000,
				'diskused'	=> intval( str_replace( 'M', '', $account['diskused'] ) ) * 1000000,
				'active'	=> !$account['suspended'],
				);
		}
		
		return $return;
	}
	
	/**
	 * Reboot Server
	 *
	 * @return	void
	 */
	public function reboot()
	{
		$this->api( 'reboot' );
	}
	
	/**
	 * Retry Error
	 *
	 * @param	array	$details	Details
	 * @return	void
	 */
	public function retryError( $details )
	{
		$response = $this->api( $details['function'], $details['params'] );		
		
		if ( isset( $response['result'][0] ) and !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this, $response['result'][0]['statusmsg'] );
		}
	}
	
	/**
	 * API Call
	 *
	 * @param	string	$function		Function Name
	 * @param	array	$params			Parameters to send
	 * @param	int		$timeout		Timeout limit
	 * @return void
	 */
	public function api( $function, $params=array(), $timeout=90 )
	{
		$this->lastCallData	= array( 'function' => $function, 'params' => $params );
		
		$apiCall = isset( $this->extra['call'] ) ? str_replace( 'server_', '', $this->extra['call'] ) : 'ip';
		
		try
		{
			$response = \IPS\Http\Url::external( 'https://' . $this->$apiCall . ':2087/json-api/' . $function )
				->setQueryString( $params )
				->request( $timeout )
				->setHeaders( array( 'Authorization' => "WHM {$this->username}:" . preg_replace( "'(\r|\n)'", '', $this->access ) ) )
				->get()
				->decodeJson();
			
			if ( isset( $response['error'] ) )
			{
				throw new \IPS\nexus\Hosting\Exception( $this, $response['error'] );
			}
			elseif ( isset( $response['cpanelresult']['error'] ) )
			{
				throw new \IPS\nexus\Hosting\Exception( $this, $response['cpanelresult']['error'] );
			}
			
			return $response;
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			throw new \IPS\nexus\Hosting\Exception( $this, $e->getMessage() );
		}
	}
}