<?php
/**
 * @brief		Hosting Servers
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		06 Aug 2014
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
 * Hosting Servers
 */
class _servers extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Hosting\Server';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'servers_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Initiate the table */
		$table = new \IPS\Helpers\Table\Db( 'nexus_hosting_servers', \IPS\Http\Url::internal('app=nexus&module=hosting&controller=servers') );
		if ( \IPS\Settings::i()->monitoring_script )
		{
			$table->include = array( 'server_monitor_icon', 'server_hostname', 'server_ip', 'server_num_accounts', 'server_cost', 'server_income' );
			$table->widths = array( 'server_monitor_icon' => 5 );
			$table->noSort = array( 'server_monitor_icon' );
		}
		else
		{
			$table->include = array( 'server_hostname', 'server_ip', 'server_num_accounts', 'server_cost', 'server_income' );
		}
		$table->sortBy = $table->sortBy ?: 'server_hostname';
		$table->quickSearch = 'server_hostname';

		/* Buttons */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'servers_add' ) )
		{
			$table->rootButtons = array(
				'add'	=> array(
					'title'	=> 'add',
					'icon'	=> 'plus',
					'link'	=> \IPS\Http\Url::internal('app=nexus&module=hosting&controller=servers&do=form')
				)
			);
		}
		$table->rowButtons = function( $row )
		{
			$return = array();
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'servers_audit' ) )
			{
				$return['list'] = array(
					'title'	=> 'view_accounts',
					'icon'	=> 'search',
					'link'	=> \IPS\Http\Url::internal( 'app=nexus&module=hosting&controller=servers&do=view&id=' . $row['server_id'] )
				);
			}
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'servers_edit' ) )
			{
				$return['edit'] = array(
					'title'	=> 'edit',
					'icon'	=> 'pencil',
					'link'	=> \IPS\Http\Url::internal( 'app=nexus&module=hosting&controller=servers&do=form&id=' . $row['server_id'] )
				);
			}
			
			if ( !$row['server_dedicated'] and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'servers_reboot' ) )
			{
				$return['reboot'] = array(
					'title'	=> 'reboot',
					'icon'	=> 'refresh',
					'link'	=> \IPS\Http\Url::internal( 'app=nexus&module=hosting&controller=servers&do=reboot&id=' . $row['server_id'] )
				);
			}
			
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'hosting', 'servers_delete' ) )
			{
				$return['delete'] = array(
					'title'	=> 'delete',
					'icon'	=> 'times-circle',
					'link'	=> \IPS\Http\Url::internal( 'app=nexus&module=hosting&controller=servers&do=delete&id=' . $row['server_id'] ),
					'data'	=> array( 'delete' => '' )
				);
			}
			
			return $return;
		};

		/* Work out the monthly income for each server */
		$serverIncomes = array();
		foreach ( array(
			'd'	=> ( 365 / 12 ),
			'w'	=> ( 365 / 12 / 7 ),
			'm'	=> 1,
			'y'	=> ( 1 / 12 ),
		) as $key => $multiplier )
		{
			foreach (
				\IPS\Db::i()->select(
					'account_server, ps_renewal_currency, SUM( ps_renewal_price / ps_renewals ) * ' . $multiplier . ' AS amount',
					'nexus_hosting_accounts',
					array( 'account_exists=1 AND ps_renewal_unit=?', $key ),
					NULL,
					NULL,
					array( 'account_server', 'ps_renewal_currency' )
				)->join( 'nexus_purchases', 'nexus_hosting_accounts.ps_id=nexus_purchases.ps_id' )
				as $row
			)
			{
				if ( !isset( $serverIncomes[ $row['account_server'] ][ $row['ps_renewal_currency'] ] ) )
				{
					$serverIncomes[ $row['account_server'] ][ $row['ps_renewal_currency'] ] = 0;
				}
				
				$serverIncomes[ $row['account_server'] ][ $row['ps_renewal_currency'] ] += $row['amount'];
			}
		}
		
		/* Parse the columns */
		$currencies = \IPS\nexus\Money::currencies();
		$costCurrency = array_shift( $currencies );
		$table->parsers = array(
			'server_monitor_icon'	=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate('hosting')->serverStatus( $row );	
			},
			'server_num_accounts' => function( $val, $row )
			{
				if ( $row['server_dedicated'] )
				{
					try
					{
						return \IPS\Theme::i()->getTemplate('purchases')->link( \IPS\nexus\Purchase::load( $row['server_dedicated'] ) );
					}
					catch ( \OutOfRangeException $e )
					{
						return '-';
					}
				}
				else
				{
					return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_hosting_accounts', array( 'account_server=? AND account_exists=1', $row['server_id'] ) )->first() . ( $row['server_max_accounts'] ? ( ' / ' . $row['server_max_accounts'] ) : '' );
				}
			},
			'server_cost' => function ( $val ) use ( $costCurrency )
			{
				return new \IPS\nexus\Money( $val, $costCurrency );
			},
			'server_income' => function( $val, $row ) use ( $serverIncomes, $costCurrency )
			{
				if ( $row['server_dedicated'] )
				{
					try
					{
						$purchase = \IPS\nexus\Purchase::load( $row['server_dedicated'] );
												
						$months = 0;
						$months += ( $purchase->renewals->interval->y / 12 );
						$months += ( $purchase->renewals->interval->m );
						$months += ( $purchase->renewals->interval->d * ( 365 / 12 ) );
												
						return new \IPS\nexus\Money( $purchase->renewals->cost->amount->multiply( new \IPS\Math\Number("{$months}" ) ), $purchase->renewals->cost->currency );
					}
					catch ( \OutOfRangeException $e )
					{
						return '-';
					}
				}
				else
				{
					$return = array();
					if ( isset( $serverIncomes[ $row['server_id'] ] ) )
					{
						foreach ( $serverIncomes[ $row['server_id'] ] as $currency => $amount )
						{
							$return[] = new \IPS\nexus\Money( $amount, $currency );
						}
					}
					else
					{
						return new \IPS\nexus\Money( 0, $costCurrency );
					}
					return implode( '<br>', $return );
				}
			}
		);
		
		/* Add filters for the queues */
		foreach ( \IPS\nexus\Hosting\Queue::queues() as $queue )
		{
			$table->filters[ $queue->name ] = \IPS\Db::i()->findInSet( 'server_queues', array( $queue->id ) );
		}
		
		/* Display */
		\IPS\Output::i()->sidebar['actions'][] = array(
			'icon'	=> 'check-circle',
			'title'	=> 'hserv_audit',
			'link'	=> \IPS\Http\Url::internal( "app=nexus&module=hosting&controller=servers&do=audit" ),
		);
		\IPS\Output::i()->output = (string) $table;
	}
	
	/**
	 * View accounts
	 *
	 * @return	void
	 */
	protected function view()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'servers_audit' );
		
		try
		{
			$server = \IPS\nexus\Hosting\Server::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X243/3', 403, '' );
		}
				
		\IPS\Output::i()->title = $server->hostname;
		\IPS\Output::i()->output = call_user_func_array( array( \IPS\Theme::i()->getTemplate( 'hosting' ), 'viewAccounts' ), $this->_audit( $server ) );
	}
	
	/**
	 * Reboot a server
	 *
	 * @return	void
	 */
	protected function reboot()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'servers_reboot' );
		
		try
		{
			\IPS\nexus\Hosting\Server::load( \IPS\Request::i()->id )->reboot();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X243/1', 404, '' );
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '3X243/2', 500, '' );
		}
		
		\IPS\Session::i()->log( 'acplogs__server_reboot', array( $server->hostname => FALSE ) );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=hosting&controller=servers'), 'rebooting' );
	}
	
	/**
	 * Audit all servers
	 *
	 * @return	void
	 */
	protected function audit()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'servers_audit' );

		$self = $this;

		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal('app=nexus&module=hosting&controller=servers&do=audit'),
			function( $data ) use ( $self )
			{
				if ( $data === 0 )
				{
					$_SESSION['serverAudit'] = array();
					$data = array( 'offset' => 0, 'bad' => array() );
				}

				try
				{
					$select = \IPS\Db::i()->select( '*', 'nexus_hosting_servers', array(), 'server_hostname', array( $data['offset'], 1 ) );
					$server = \IPS\nexus\Hosting\Server::constructFromData( $select->first() );
					
					$results = $self->_audit( $server );
					foreach ( array( 1, 2, 3, 6, 7, 8 ) as $i )
					{
						if ( !empty( $results[ $i ] ) )
						{
							$data['bad'][ $server->id ] = $server->hostname;
							break;
						}
					}
					
					$data['offset']++;
					return array( $data, \IPS\Member::loggedIn()->language()->get('hserv_audit_running'), 100 / $select->count(TRUE) * $data['offset'] );
				}
				catch ( \UnderflowException $e )
				{
					$_SESSION['serverAudit'] = $data['bad'];
					return NULL;
				}
			},
			function()
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=hosting&controller=servers&do=auditResults') );
			}
		);
	}
	
	/**
	 * Audit all servers
	 *
	 * @return	void
	 */
	protected function auditResults()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'servers_audit' );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'hosting' )->audit( $_SESSION['serverAudit'] );
	}
	
	/**
	 * Audit a server
	 *
	 * @param	\IPS\nexus\Hosting\Server	$server
	 * @return	array
	 */
	protected function _audit( \IPS\nexus\Hosting\Server $server )
	{
		/* Get accounts from server */
		try
		{
			$serverAccounts = $server->listAccounts();
		}
		catch ( \IPS\nexus\Hosting\Exception $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '3X243/4', 403, '' );
		}
		
		/* Init */
		$notPresentOnServer		= array();
		$notPresentInDb			= array();
		$domainsDontMatch		= array();
		$expiredButnotSuspended = array();
		$diskSpaceAllocated		= 0;
		$diskSpaceInUse			= 0;
		$accounts				= array();
		$suspendedButNotExpired = array();
		$doesNotResolveCorrectly= array();
				
		/* Get accounts in the database */
		foreach ( \IPS\Db::i()->select( 'nexus_hosting_accounts.*, nexus_purchases.ps_active', 'nexus_hosting_accounts', array( 'account_server=?', $server->id ), 'account_username' )->join( 'nexus_purchases', 'nexus_hosting_accounts.ps_id=nexus_purchases.ps_id' ) as $row )
		{			
			/* If we don't think it exists, check if it does */
			if ( !$row['account_exists'] )
			{
				if ( isset( $serverAccounts[ $row['account_username'] ] ) )
				{
					$row['account_exists'] = 1;
					\IPS\Db::i()->update( 'nexus_hosting_accounts', array( 'account_exists' => 1 ), "ps_id={$row['ps_id']}" );
				}
			}
			
			// Now check it
			if ( $row['account_exists'] )
			{
				// Is it in there?
				if ( isset( $serverAccounts[ $row['account_username'] ] ) )
				{
					$accounts[] = array_merge( $row, $serverAccounts[ $row['account_username'] ] );
	
					// Does the domain match?
					if ( $serverAccounts[ $row['account_username'] ]['domain'] != $row['account_domain'] )
					{
						$row['domain'] = $serverAccounts[ $row['account_username'] ]['domain'];
						$domainsDontMatch[] = $row;
					}
					
					// Does the status match?
					if ( $row['ps_active'] and !$serverAccounts[ $row['account_username'] ]['active'] )
					{
						$suspendedButNotExpired[] = $row;
					}
					elseif ( !$row['ps_active'] and $serverAccounts[ $row['account_username'] ]['active'] )
					{
						$expiredButnotSuspended[] = $row;
					}
					
					// Does it resolve correctly? */
					if ( gethostbyname( $row['account_domain'] ) !== $server->ip )
					{
						$doesNotResolveCorrectly[] = $row;
					}
					
					// Get disk use
					if( $diskSpaceAllocated == -1 or $serverAccounts[ $row['account_username'] ]['disklimit'] == 'unlimited' )
					{
						$diskSpaceAllocated = -1;
					}
					else
					{
						$diskSpaceAllocated += $serverAccounts[ $row['account_username'] ]['disklimit'];
					}
					$diskSpaceInUse += $serverAccounts[ $row['account_username'] ]['diskused'];
					
					// Note that we have it
					unset( $serverAccounts[ $row['account_username'] ] );
				}
				else
				{
					if ( !$row['ps_active'] )
					{
						\IPS\Db::i()->update( 'nexus_hosting_accounts', array( 'account_exists' => 0 ), "ps_id={$row['ps_id']}" );
					}
					else
					{
						$notPresentOnServer[] = $row;
					}
				}
			}
		}
		$notPresentInDb = $serverAccounts;
		
		/* Return */
		return array( $server, $notPresentOnServer, $notPresentInDb, $domainsDontMatch, $diskSpaceAllocated, $diskSpaceInUse, $expiredButnotSuspended, $suspendedButNotExpired, $doesNotResolveCorrectly, $accounts );
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form()
	{
		$availableTypes = array();

		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/nexus/sources/Hosting' ) as $file )
		{
			if (!$file->isDot() and mb_substr($file, 0, 1) !== '.' and $file->isDir())
			{
				$availableTypes[] = (string) $file;
			}
		}

		if( isset( \IPS\Request::i()->server_type ) and in_array( \IPS\Request::i()->server_type, $availableTypes ) )
		{
			$this->nodeClass = "IPS\\nexus\\Hosting\\" . ucfirst( \IPS\Request::i()->server_type ) . "\\Server";
		}

		parent::form();
	}
}