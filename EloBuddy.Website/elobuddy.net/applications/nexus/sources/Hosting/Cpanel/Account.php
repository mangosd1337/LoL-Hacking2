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

namespace IPS\nexus\Hosting\Cpanel;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Hosting Account Model
 */
class _Account extends \IPS\nexus\Hosting\Account
{
	/**
	 * Create
	 *
	 * @param	\IPS\nexus\Package\Hosting	$package	The package
	 * @param	\IPS\nexus\Customer			$customer	The customer
	 * @return	void
	 */
	public function create( \IPS\nexus\Package\Hosting $package, \IPS\nexus\Customer $customer )
	{
		$response = $this->server->api( 'createacct', array(
			'username'		=> $this->username,
			'domain'		=> $this->domain,
			'quota'			=> ( $package->quota == -1 ) ? 'unlimited' : $package->quota,
			'password'		=> $this->password,
			'ip'			=> $package->ip ? 'y' : 'n',
			'cgi'			=> $package->cgi,
			'frontpage'		=> $package->frontpage,
			'hasshell'		=> $package->hasshell,
			'contactemail'	=> $customer->email,
			'maxftp'		=> ( $package->maxftp == -1 ) ? 'unlimited' : $package->maxftp,
			'maxsql'		=> ( $package->maxsql == -1 ) ? 'unlimited' : $package->maxsql,
			'maxpop'		=> ( $package->maxpop == -1 ) ? 'unlimited' : $package->maxpop,
			'maxlst'		=> ( $package->maxlst == -1 ) ? 'unlimited' : $package->maxlst,
			'maxsub'		=> ( $package->maxsub == -1 ) ? 'unlimited' : $package->maxsub,
			'maxpark'		=> ( $package->maxpark == -1 ) ? 'unlimited' : $package->maxpark,
			'maxaddon'		=> ( $package->maxaddon == -1 ) ? 'unlimited' : $package->maxaddon,
			'bwlimit'		=> ( $package->bwlimit == -1 ) ? 'unlimited' : $package->bwlimit,
			) );
			
		if ( !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
		}		
	}
	
	/**
	 * @brief	Account Summary
	 */
	protected $summary;
	
	/**
	 * Get Information
	 *
	 * @return	array
	 */
	protected function summary()
	{
		if ( $this->summary === NULL )
		{
			$response = $this->server->api( 'accountsummary', array( 'user' => $this->username ), 15 );
						
			if ( !$response['status'] )
			{
				throw new \IPS\nexus\Hosting\Exception( $this->server, $response['statusmsg'] );
			}
			
			$this->summary = $response['acct'][0];
		}
		
		return $this->summary;
	}
	
	/**
	 * Get diskspace allowance (in bytes)
	 *
	 * @return	int|NULL
	 */
	public function diskspaceAllowance()
	{
		$information = $this->summary();
		
		return ( $information['disklimit'] === 'unlimited' ) ? NULL : ( intval( $information['disklimit'] ) * 1000000 );
	}
	
	/**
	 * Get diskspace currently in use (in bytes)
	 *
	 * @return	int
	 */
	public function diskspaceInUse()
	{
		$information = $this->summary();
		
		return intval( $information['diskused'] ) * 1000000;
	}
	
	/**
	 * @brief	Bandwidth used this month
	 */
	protected $monthlyBandwidthAllowance;
	
	/**
	 * @brief	Bandwidth used this month
	 */
	protected $bandwidthUsedThisMonth;
	
	/**
	 * Get bandwidth information
	 *
	 * @return	array
	 */
	protected function bandwidth()
	{
		$response = $this->server->api( 'showbw' );
		foreach( $response['bandwidth'][0]['acct'] as $data )
		{
			if ( $data['user'] == $this->username )
			{
				$this->monthlyBandwidthAllowance = $data['limit'] / 1048576 * 1000000;
				$this->bandwidthUsedThisMonth = $data['totalbytes'] / 1048576 * 1000000;
				break;
			}
		}
	}
	
	/**
	 * Get monthly bandwidth allowance (in bytes)
	 *
	 * @return	int|NULL
	 */
	public function monthlyBandwidthAllowance()
	{
		if ( $this->monthlyBandwidthAllowance === NULL )
		{
			$this->bandwidth();
		}
		return ( $this->monthlyBandwidthAllowance === 'unlimited' ) ? NULL : $this->monthlyBandwidthAllowance;
	}
	
	/**
	 * Get bandwidth used this month (in bytes)
	 *
	 * @return	int
	 */
	public function bandwidthUsedThisMonth()
	{
		if ( $this->bandwidthUsedThisMonth === NULL )
		{
			$this->bandwidth();
		}
		return $this->bandwidthUsedThisMonth;
	}
	
	/**
	 * Get max FTP accounts
	 *
	 * @return	int|NULL
	 */
	public function maxFtpAccounts()
	{
		$information = $this->summary();
		return ( $information['maxftp'] === 'unlimited' ) ? NULL : $information['maxftp'];
	}
	
	/**
	 * Get max databases
	 *
	 * @return	int|NULL
	 */
	public function maxDatabases()
	{
		$information = $this->summary();
		return ( $information['maxsql'] === 'unlimited' ) ? NULL : $information['maxsql'];
	}
	
	/**
	 * Get max email accounts
	 *
	 * @return	int|NULL
	 */
	public function maxEmailAccounts()
	{
		$information = $this->summary();
		return ( $information['maxpop'] === 'unlimited' ) ? NULL : $information['maxpop'];
	}
	
	/**
	 * Get max mailing lists
	 *
	 * @return	int|NULL
	 */
	public function maxMailingLists()
	{
		$information = $this->summary();
		return ( $information['maxlst'] === 'unlimited' ) ? NULL : $information['maxlst'];
	}
	
	/**
	 * Get max subdomains
	 *
	 * @return	int|NULL
	 */
	public function maxSubdomains()
	{
		$information = $this->summary();
		return ( $information['maxsub'] === 'unlimited' ) ? NULL : $information['maxsub'];
	}
	
	/**
	 * Get max parked domains
	 *
	 * @return	int|NULL
	 */
	public function maxParkedDomains()
	{
		$information = $this->summary();
		return ( $information['maxparked'] === 'unlimited' ) ? NULL : $information['maxparked'];
	}
	
	/**
	 * Get max addon domains
	 *
	 * @return	int|NULL
	 */
	public function maxAddonDomains()
	{
		$information = $this->summary();
		return ( $information['maxaddons'] === 'unlimited' ) ? NULL : $information['maxaddons'];
	}
	
	/**
	 * Has SSH access?
	 *
	 * @return	int|NULL
	 */
	public function hasSSHAccess()
	{
		$information = $this->summary();
		return ( mb_substr( $information['shell'], -7 ) != 'noshell' );
	}
	
	/**
	 * Get Control Panel Link
	 *
	 * @return	\IPS\Http\Url
	 */
	public function controlPanelLink()
	{
		return \IPS\Http\Url::external( 'https://' . $this->server->hostname . ':2083' );
	}
	
	/**
	 * Edit Priviledges
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	public function edit( $values )
	{	
		/* Init */
		$data = array(
			'user'		=> $this->username,
			'DNS'		=> $values['account_domain'],
			'QUOTA'		=> ( $values['p_quota'] != -1 ) ? ( $values['p_quota'] * 1000000 ) : 'unlimited',
			'MAXFTP'	=> ( $values['p_maxftp'] != -1 ) ? ( $values['p_maxftp'] ) : 'unlimited',
			'MAXSQL'	=> ( $values['p_maxsql'] != -1 ) ? ( $values['p_maxsql'] ) : 'unlimited',
			'MAXPOP'	=> ( $values['p_maxpop'] != -1 ) ? ( $values['p_maxpop'] ) : 'unlimited',
			'MAXLST'	=> ( $values['p_maxlst'] != -1 ) ? ( $values['p_maxlst'] ) : 'unlimited',
			'MAXSUB'	=> ( $values['p_maxsub'] != -1 ) ? ( $values['p_maxsub'] ) : 'unlimited',
			'MAXPARK'	=> ( $values['p_maxpark'] != -1 ) ? ( $values['p_maxpark'] ) : 'unlimited',
			'MAXADDON'	=> ( $values['p_maxaddon'] != -1 ) ? ( $values['p_maxaddon'] ) : 'unlimited',
			'HASSHELL'	=> intval( $values['p_hasshell'] )
		);
		
		/* Making quota or bandwidth unlimited? */
		if ( $values['p_quota'] == -1 )
		{
			$data['QUOTA'] = 'unlimited';
		}
		if ( $values['p_bwlimit'] == -1 )
		{
			$data['BWLIMIT'] = 'unlimited';
		}
				
		/* Has the username changed? */
		if ( $values['account_username'] != $this->username )
		{
			$data['newuser'] = $values['account_username'];
		}
		
		/* Send */
		$response = $this->server->api( 'modifyacct', $data );
		if( !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
		}		
		
		/* Change quota? */
		if ( $values['p_quota'] != -1 and ( $this->diskspaceAllowance() === NULL or $values['p_quota'] * 1000000 != $this->diskspaceAllowance() ) )
		{
			$response = $this->server->api( 'editquota', array( 'user' => $values['account_username'], 'quota' => $values['p_quota'] ) );
			if( !$response['result'][0]['status'] )
			{
				throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
			}
		}
		
		/* Change bandwidth limit? */
		if ( ( $values['p_bwlimit'] != -1 and ( $this->monthlyBandwidthAllowance() === NULL or $values['p_bwlimit'] * 1000000 != $this->monthlyBandwidthAllowance() ) ) or ( $values['p_bwlimit'] != -1 and $this->monthlyBandwidthAllowance() !== NULL ) )
		{
			$this->changeBandwidthLimit( $values['p_bwlimit'] * 1000000, $values['account_username'] );
		}
		
		/* Change password? */
		if ( $values['account_password'] != (string) $this->password )
		{
			$this->changePassword( $values['account_password'], $values['account_username'] );
		}
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
		$response = $this->server->api( 'limitbw', array( 'user' => $username ?: $this->username, 'bwlimit' => floor( $newLimit / 1000000 ) ) );
		if( !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
		}
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
		$response = $this->server->api( 'passwd', array( 'user' => $username ?: $this->username, 'pass' => $newPassword ) );
		if( !$response['passwd'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['passwd'][0]['statusmsg'] );
		}
	}
	
	/**
	 * Suspend
	 *
	 * @return	void
	 */
	public function suspend()
	{
		$response = $this->server->api( 'suspendacct', array( 'user' => $this->username ) );
		if( !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
		}
	}
	
	/**
	 * Unsuspend
	 *
	 * @return	void
	 */
	public function unsuspend()
	{
		$response = $this->server->api( 'unsuspendacct', array( 'user' => $this->username ) );
		if( !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
		}
	}
	
	/**
	 * Terminate
	 *
	 * @return	void
	 */
	public function terminate()
	{
		$response = $this->server->api( 'removeacct', array( 'user' => $this->username ) );
		if( !$response['result'][0]['status'] )
		{
			throw new \IPS\nexus\Hosting\Exception( $this->server, $response['result'][0]['statusmsg'] );
		}
	}
}