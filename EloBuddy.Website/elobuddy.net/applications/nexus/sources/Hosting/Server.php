<?php
/**
 * @brief		Hosting Server Model
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
 * Hosting Server Model
 */
class _Server extends \IPS\Node\Model
{
	/* !ActiveRecord */
	
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'nexus_hosting_servers';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'server_';
	
	/**
	 * @brief	[ActiveRecord] Database ID Fields
	 */
	protected static $databaseIdFields = array( 'server_hostname', 'server_dedicated' );
	
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
		$classname = 'IPS\\nexus\Hosting\\' . ucfirst( $data['server_type'] ) . '\\Server';
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
	 * Get Title
	 *
	 * @return	string
	 */
	public function get__title()
	{
		return $this->hostname;
	}
	
	/**
	 * Get extra information
	 *
	 * @return	mixed
	 */
	public function get_extra()
	{
		return isset( $this->_data['extra'] ) ? json_decode( $this->_data['extra'], TRUE ) : array();
	}
	
	/**
	 * Set extra information
	 *
	 * @param	mixed	$extra	The data
	 * @return	void
	 */
	public function set_extra( $extra )
	{
		$this->_data['extra'] = json_encode( $extra );
	}
	
	/* !Node */
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'menu__nexus_hosting_servers';
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'nexus',
		'module'	=> 'hosting',
		'prefix' 	=> 'servers_'
	);
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$types = array();
		$extraFields = array();
		$toggles = array();
		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/nexus/sources/Hosting' ) as $file )
		{
			if ( !$file->isDot() and mb_substr( $file, 0, 1 ) !== '.' and $file->isDir() )
			{
				$types[ mb_strtolower( $file ) ] = 'nexus_server_' . $file;
				
				$class = 'IPS\nexus\Hosting\\' . ucfirst( $file ) . '\Server';
				$extraFields[ mb_strtolower( mb_substr( $file, 0, -4 ) ) ] = $class::extraFormFields( $this );
				$toggles[ mb_strtolower( $file ) ] = array_keys( $extraFields[ mb_strtolower( mb_substr( $file, 0, -4 ) ) ] );
			}
		}
		
		$form->addHeader( 'server_type' );
		$form->add( new \IPS\Helpers\Form\Radio( 'server_type', $this->type, TRUE, array( 'options' => $types, 'toggles' => $toggles ) ) );
		foreach ( $extraFields as $type => $fields )
		{
			foreach ( $fields as $id => $field )
			{
				$form->add( $field );
			}
		}
		$form->addHeader( 'server_network_information' );
		$form->add( new \IPS\Helpers\Form\Text( 'server_hostname', $this->hostname, TRUE, array( 'placeholder' => 'server.example.net' ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'server_ip', $this->ip, TRUE, array( 'placeholder' => '123.456.789.000' ), function( $val )
		{
			if ( !filter_var( $val, FILTER_VALIDATE_IP ) )
			{
				throw new \DomainException('form_bad_value');
			}
		} ) );
		$form->addHeader( 'server_accounts' );
		$form->add( new \IPS\Helpers\Form\Node( 'server_queues', explode( ',', $this->queues ), FALSE, array( 'class' => 'IPS\nexus\Hosting\Queue', 'multiple' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'server_max_accounts', $this->id ? $this->max_accounts : 0, FALSE, array( 'unlimited' => 0 ) ) );
		$nameservers = $this->nameservers ? explode( ',', $this->nameservers ) : array();
		$form->add( new \IPS\Helpers\Form\Radio( 'server_nameservers_default', count( $nameservers ) ? 0 : 1, FALSE, array( 'options' => array( 1 => 'server_nameservers_default_yes', 0 => 'server_nameservers_default_no' ), 'toggles' => array( 0 => array( 'server_nameservers' ) ) ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'server_nameservers', $nameservers, NULL, array( 'placeholder' => 'ns.example.net' ), NULL, NULL, NULL, 'server_nameservers' ) );
		$form->addHeader( 'server_reporting' );
		$form->add( new \IPS\Helpers\Form\YesNo( 'server_monitor_on', \IPS\Settings::i()->monitoring_script and $this->monitor, FALSE, array( 'disabled' => !\IPS\Settings::i()->monitoring_script, 'togglesOn' => array( 'server_monitor' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Url( 'server_monitor', $this->monitor ?: '', NULL, array( 'placeholder' => 'http://server.example.net/monitor_remote.php' ), function( $val )
		{
			if ( !$val and \IPS\Request::i()->sever_monitor_on )
			{
				throw new \DomainException('form_required');
			}
		}, NULL, NULL, 'server_monitor' ) );
		$currencies = \IPS\nexus\Money::currencies();
		$form->add( new \IPS\Helpers\Form\Number( 'server_cost', $this->cost, FALSE, array(), NULL, NULL, array_shift( $currencies ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['server_queues'] ) )
		{
			$values['server_queues'] = is_array( $values['server_queues'] ) ? implode( ',', array_keys( $values['server_queues'] ) ) : NULL;
		}

		if( isset( $values['server_nameservers'] ) )
		{
			$values['server_nameservers'] = implode( ',', $values['server_nameservers'] );
		}

		unset( $values['server_nameservers_default'] );
		unset( $values['server_monitor_on'] );
				
		$values = call_user_func( array( 'IPS\nexus\Hosting\\' . ucfirst( $values['server_type'] ) . '\Server', '_formatFormValues' ), $values );
		
		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/nexus/sources/Hosting' ) as $file )
		{
			if ( !$file->isDot() and mb_substr( $file, 0, 1 ) !== '.' and $file->isDir() )
			{
				$class = 'IPS\nexus\Hosting\\' . ucfirst( $file ) . '\Server';
				foreach ( array_keys( $class::extraFormFields( $this ) ) as $k )
				{
					unset( $values[ $k ] );
				}
			}
		}
		
		return $values;
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public static function _formatFormValues( $values )
	{
		return $values;
	}
	
	/* !Server */
	
	/**
	 * Last API call data
	 */
	public $lastCallData;
	
	/**
	 * Get nameservers
	 *
	 * @return	array
	 */
	public function nameservers()
	{
		return explode( ',', $this->nameservers ?: \IPS\Settings::i()->nexus_hosting_nameservers );
	}
	
	/**
	 * Monitoring: Server Online
	 *
	 * @param	mixed	$version	Version number returned from script
	 * @return	void
	 */
	public function monitoringOnline( $version )
	{
		foreach ( explode( ',', \IPS\Settings::i()->monitoring_alert ) as $address )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'monitoring_online', array( $this, urlencode( $address ) ), \IPS\Email::TYPE_LIST );
			$email->send( $address );
		}
	}
	
	/**
	 * Monitoring: Server Offline
	 *
	 * @return	void
	 */
	public function monitoringOffline()
	{
		foreach ( explode( ',', \IPS\Settings::i()->monitoring_alert ) as $address )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'monitoring_offline', array( $this, urlencode( $address ) ), \IPS\Email::TYPE_LIST );
			$email->send( $address );
		}
	}
	
	/**
	 * Monitoring: Server Offline - Second Notification
	 *
	 * @return	void
	 */
	public function monitoringPanic()
	{
		foreach ( explode( ',', \IPS\Settings::i()->monitoring_alert ) as $address )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'monitoring_panic', array( $this, urlencode( $address ) ), \IPS\Email::TYPE_LIST );
			$email->send( $address );
		}
	}
	
	/**
	 * Monitoring: Server Reset
	 *
	 * @param	string	$by	Email address
	 * @return	void
	 */
	public function monitoringAcknowledged( $by )
	{
		foreach ( explode( ',', \IPS\Settings::i()->monitoring_alert ) as $address )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'monitoring_acknowledged', array( $this, urlencode( $address ), $by ), \IPS\Email::TYPE_LIST );
			$email->send( $address );
		}
	}
	
	/**
	 * Monitoring: Server Reset
	 *
	 * @param	string	$by	Email address
	 * @return	void
	 */
	public function monitoringReset( $by )
	{
		foreach ( explode( ',', \IPS\Settings::i()->monitoring_alert ) as $address )
		{
			$email = \IPS\Email::buildFromTemplate( 'nexus', 'monitoring_reset', array( $this, urlencode( $address ), $by ), \IPS\Email::TYPE_LIST );
			$email->send( $address );
		}
	}
	
	/* !abstract */
	
	/**
	 * Additional fields for the server form
	 *
	 * @param	\IPS\nexus\Hosting\Server	$server	The server bring edited
	 * @return	array
	 */
	public static function extraFormFields( \IPS\nexus\Hosting\Server $server )
	{
		return array();
	}
	
	/**
	 * Check if username is acceptable
	 *
	 * @param	string	$username	The username to check
	 * @return	bool
	 */
	public function checkUsername( $username )
	{
		return TRUE;
	}
	
	/**
	 * List Accounts
	 *
	 * @return	array	'username' => array( 'domain' => 'example.com', 'disklimit' => 100000, 'diskused' => 50000, 'active' => TRUE )
	 */
	public function listAccounts()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Reboot Server
	 *
	 * @return	void
	 */
	public function reboot()
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
	
	/**
	 * Retry Error
	 *
	 * @param	array	$details	Details
	 * @return	void
	 */
	public function retryError( $details )
	{
		throw new \IPS\nexus\Hosting\Exception( $this, 'DUMMY_SERVER' );
	}
}