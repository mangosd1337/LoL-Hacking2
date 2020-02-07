<?php
/**
 * @brief		Reattempt queued IPS Connect requests
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Dec 2013
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
 * Reattempt queued IPS Connect requests
 */
class _connect extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\RuntimeException
	 */
	public function execute()
	{
		/* Get the next request */
		try
		{
			$request	= \IPS\Db::i()->select( 'q.*, s.*', array( 'core_ipsconnect_queue', 'q' ), array(), 'q.id asc', array( 0, 1 ) )
				->join( array( 'core_ipsconnect_slaves', 's' ), "s.slave_id=q.slave_id" )
				->first();

			/* Try to process request again */
			try
			{
				parse_str( $request['request_url'], $variables );

				$response = \IPS\Http\Url::external( $request['slave_url'] )
					->setQueryString( $variables )
					->request()
					->get();

				if( $response->httpResponseCode !== NULL AND $response->httpResponseCode > 200 )
				{
					/* Update fail count */
					\IPS\Db::i()->update( 'core_ipsconnect_queue', 'fail_count=fail_count+1', array( 'id=?', $request['id'] ) );

					return NULL;
				}

				$response	= $response->decodeJson();

				/* If this is a registered slave but the slave is telling us Connect is disabled, remove it */
				if( isset( $response['status'] ) AND $response['status'] == 'DISABLED' )
				{
					\IPS\Db::i()->delete( 'core_ipsconnect_slaves', array( 'slave_id=?', $request['slave_id'] ) );
					\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Db::i()->select( 'COUNT(*)', 'core_ipsconnect_slaves' )->first() ), array( 'conf_key=?', 'connect_slaves' ) );
					unset( \IPS\Data\Store::i()->settings );
				}

				/* If we are here, request succeeded - remove from queue */
				\IPS\Db::i()->delete( 'core_ipsconnect_queue', array( 'id=?', $request['id'] ) );
				return NULL;
			}
			catch( \RuntimeException $e )
			{
				/* Update fail count */
				\IPS\Db::i()->update( 'core_ipsconnect_queue', 'fail_count=fail_count+1', array( 'id=?', $request['id'] ) );

				return NULL;
			}
		}
		catch( \UnderflowException $e )
		{
			/* There are none left - disable task */
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), "`key`='ipsconnect'" );

			return NULL;
		}
		
		return NULL;
	}
}