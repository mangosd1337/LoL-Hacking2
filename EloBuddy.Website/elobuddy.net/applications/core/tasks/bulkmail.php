<?php
/**
 * @brief		bulkmail Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * bulkmail Task
 */
class _bulkmail extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * @return	mixed	Message to log or NULL
	 */
	public function execute()
	{
		try
		{
			\IPS\core\BulkMail\Bulkmailer::constructFromData( \IPS\Db::i()->select( '*', 'core_bulk_mail', 'mail_active=1', 'mail_start ASC', 1 )->first() )->send();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\core\BulkMail\Bulkmailer::updateTask( 0 );
		}
	}
}