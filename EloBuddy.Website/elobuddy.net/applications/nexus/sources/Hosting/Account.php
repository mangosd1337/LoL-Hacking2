<?php
/**
 * @brief		Hosting Account Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		6 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Hosting;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Account Account Model
 */
class _Account extends \IPS\Patterns\ActiveRecord
{
	/* !ActiveRecord */
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_hosting_accounts';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'ps_id';
	
	/**
	 * Construct ActiveRecord from database row
	 *
	 * @param	array	$data							Row from database table
	 * @param	bool	$updateMultitonStoreIfExists	Replace current object in multiton store if it already exists there?
	 * @return	static
	 */
	public static function constructFromData( $data, $updateMultitonStoreIfExists = TRUE )
	{
		/* Initiate an object */
		$classname = 'IPS\\nexus\Hosting\\' . ucfirst( \IPS\nexus\Hosting\Server::load( $data['account_server'] )->type ) . '\\Account';
		$obj = new $classname;
		$obj->_new = FALSE;
		
		/* Import data */
		foreach ( $data as $k => $v )
		{
			if( static::$databasePrefix )
			{
				$k = \substr( $k, \strlen( static::$databasePrefix ) );
			}
			
			$obj->_data[ $k ] = $v;
		}
		$obj->changed = array();
				
		/* Return */
		return $obj;
	}
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		/* Set a random password */
		$password = '';
		foreach ( range( 1, 12 ) as $j )
		{
			do
			{
				$chr = rand( 33, 122 );
			}
			while ( in_array( $chr, array( 34, 37, 38, 39, 44, 46, 47, 58, 59, 60, 61, 62, 64, 91, 92, 93, 94, 96 ) ) );

			$password .= chr( $chr );
		}
		$this->password = $password;
		
		/* By default the account exists */
		$this->exists = TRUE;
	}
	
	/**
	 * Set Server
	 *
	 * @param	\IPS\nexus\Hosting\Server	$server	The server
	 * @return	void
	 */
	public function set_server( \IPS\nexus\Hosting\Server $server )
	{
		$this->_data['account_server'] = $server->id;
	}
	
	/**
	 * Get Server
	 *
	 * @return	\IPS\nexus\Hosting\Server
	 */
	public function get_server()
	{
		return \IPS\nexus\Hosting\Server::load( $this->_data['account_server'] );
	}
	
	/**
	 * Set Domain
	 *
	 * @param	string	$domain	The domain
	 * @return	void
	 */
	public function set_domain( $domain )
	{
		$this->_data['account_domain'] = $domain;
	}
	
	/**
	 * Get Domain
	 *
	 * @return	string
	 */
	public function get_domain()
	{
		return $this->_data['account_domain'];
	}
	
	/**
	 * Set Username
	 *
	 * @param	string	$username	The username
	 * @return	void
	 */
	public function set_username( $username )
	{
		$this->_data['account_username'] = $username;
	}
	
	/**
	 * Get Username
	 *
	 * @return	string
	 */
	public function get_username()
	{
		return $this->_data['account_username'];
	}
	
	/**
	 * Set Password
	 *
	 * @param	string	$password	The password
	 * @return	void
	 */
	public function set_password( $password )
	{
		$this->_data['account_password'] = \IPS\Text\Encrypt::fromPlaintext( $password )->tag();
	}
	
	/**
	 * Get Password
	 *
	 * @return	string
	 */
	public function get_password()
	{
		return \IPS\Text\Encrypt::fromTag( $this->_data['account_password'] )->decrypt();
	}
	
	/**
	 * Set If Account Exists
	 *
	 * @param	bool	$exists	If the account exists
	 * @return	void
	 */
	public function set_exists( $exists )
	{
		$this->_data['account_exists'] = intval( $exists );
	}
	
	/**
	 * Get If Account Exists
	 *
	 * @return	bool
	 */
	public function get_exists()
	{
		return (bool) $this->_data['account_exists'];
	}
	
	/**
	 * Get Purchase
	 *
	 * @return	\IPS\nexus\Purchase
	 */
	public function purchase()
	{
		return \IPS\nexus\Purchase::load( $this->_data['ps_id'] );
	}
	
	/* !Account */
	
	/**
	 * Get FTP Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function ftpLink()
	{
		return \IPS\Http\Url::external( 'ftp://' . $this->username . ':' . urlencode( $this->password ) . '@' . $this->server->hostname );
	}
	
	/* !abstract */
			
	/**
	 * Create
	 *
	 * @param	\IPS\nexus\Package\Hosting	$package	The package
	 * @param	\IPS\nexus\Customer			$customer	The customer
	 * @return	void
	 */
	public function create( \IPS\nexus\Package\Hosting $package, \IPS\nexus\Customer $customer )
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get diskspace allowance (in bytes)
	 *
	 * @return	int|NULL
	 */
	public function diskspaceAllowance()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get diskspace currently in use (in bytes)
	 *
	 * @return	int
	 */
	public function diskspaceInUse()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get monthly bandwidth allowance (in bytes)
	 *
	 * @return	int|NULL
	 */
	public function monthlyBandwidthAllowance()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get bandwidth used this month (in bytes)
	 *
	 * @return	int
	 */
	public function bandwidthUsedThisMonth()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max FTP accounts
	 *
	 * @return	int|NULL
	 */
	public function maxFtpAccounts()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max databases
	 *
	 * @return	int|NULL
	 */
	public function maxDatabases()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max email accounts
	 *
	 * @return	int|NULL
	 */
	public function maxEmailAccounts()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max mailing lists
	 *
	 * @return	int|NULL
	 */
	public function maxMailingLists()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max subdomains
	 *
	 * @return	int|NULL
	 */
	public function maxSubdomains()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max parked domains
	 *
	 * @return	int|NULL
	 */
	public function maxParkedDomains()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get max addon domains
	 *
	 * @return	int|NULL
	 */
	public function maxAddonDomains()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Has SSH access?
	 *
	 * @return	int|NULL
	 */
	public function hasSSHAccess()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Get Control Panel Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function controlPanelLink()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Edit Priviledges
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	public function edit( $values )
	{	
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Change bandwidth limit
	 *
	 * @param	float	$newLimit	New Limit (in bytes)
	 * @param	string|NULL	$username		Username. Only needed if has been changed on server but not locally
	 * @return	void
	 */
	public function changeBandwidthLimit( $newLimit, $username = NULL )
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Change Password
	 *
	 * @param	string		$newPassword	New Password
	 * @param	string|NULL	$username		Username. Only needed if has been changed on server but not locally
	 * @return	void
	 */
	public function changePassword( $newPassword, $username = NULL )
	{	
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Suspend
	 *
	 * @return	void
	 */
	public function suspend()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Unsuspend
	 *
	 * @return	void
	 */
	public function unsuspend()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Terminate
	 *
	 * @return	void
	 */
	public function terminate()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
}