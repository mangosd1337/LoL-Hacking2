<?php
/**
 * @brief		Hosting Exception
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Aug 2014
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
 * Hosting Exception
 */
class _Exception extends \RuntimeException
{
	/**
	 * @brief	Time
	 */
	protected $time;
	
	/**
	 * @brief	Server
	 */
	protected $server;
	
	/**
	 * @brief	Details
	 */
	protected $details;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\nexus\Hosting\Server	$server		Server
	 * @param	string						$message	Message
	 * @return	void
	 */
	public function __construct( \IPS\nexus\Hosting\Server $server, $message )
	{
		$this->time = new \IPS\DateTime;
		$this->server = $server;
		$this->details = $server->lastCallData;
		
		return parent::__construct( $message );
	} 
	
	/**
	 * Log
	 *
	 * @return	void
	 */
	public function log()
	{
		if ( \IPS\Settings::i()->nexus_hosting_error_emails )
		{
			\IPS\Email::buildFromTemplate( 'nexus', 'hostingError', array( $this->server, $this->getMessage(), \IPS\Http\Url::internal( '&app=nexus&module=hosting&controller=errors', 'admin' )->stripQueryString('adsess') ), \IPS\Email::TYPE_LIST )->send( explode( ',', \IPS\Settings::i()->nexus_hosting_error_emails ) );
		}
		
		\IPS\Db::i()->insert( 'nexus_hosting_errors', array(
			'e_time'	=> $this->time->getTimestamp(),
			'e_server'	=> $this->server->id,
			'e_message'	=> $this->getMessage(),
			'e_extra'	=> json_encode( $this->details )
		) );
	}
}