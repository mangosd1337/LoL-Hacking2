<?php
/**
 * @brief		supportAutoresolve Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		25 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * supportAutoresolve Task
 */
class _supportAutoresolve extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
		if ( \IPS\Settings::i()->nexus_autoresolve_days AND \IPS\Settings::i()->nexus_autoresolve_status != '' )
		{
			/* Build where */
			$where = array();
			$where[] = array( \IPS\Db::i()->in( 'r_status', explode( ',', \IPS\Settings::i()->nexus_autoresolve_applicable ) ) );
			if ( \IPS\Settings::i()->nexus_autoresolve_departments !== '*' )
			{
				$where[] = array( \IPS\Db::i()->in( 'r_department', explode( ',', \IPS\Settings::i()->nexus_autoresolve_departments ) ) );
			}
			
			/* Resolve */
			$resolveWhere = array_merge( $where, array( array( 'r_last_reply<?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->nexus_autoresolve_days . 'D' ) )->getTimestamp() ) ) );
			$resolvedStatus = \IPS\nexus\Support\Status::load( \IPS\Settings::i()->nexus_autoresolve_status );
			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_support_requests', $resolveWhere ), 'IPS\nexus\Support\Request' ) as $request )
			{
				$request->status = $resolvedStatus;
				$request->save();
			}
			
			/* Warnings */
			if ( \IPS\Settings::i()->nexus_autoresolve_notify )
			{
				$warningWhere = array_merge( $where, array(
					array( 'r_last_reply<?', \IPS\DateTime::create()->sub( new \DateInterval( 'PT' . \IPS\Settings::i()->nexus_autoresolve_notify . 'H' ) )->getTimestamp() ),
					array( 'r_ar_notify=0' )
				) );
				foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_support_requests', $warningWhere ), 'IPS\nexus\Support\Request' ) as $request )
				{				
					$email = \IPS\Email::buildFromTemplate( 'nexus', 'autoresolveWarning', array( $request, \IPS\DateTime::ts( $request->last_reply )->add( new \DateInterval( 'P' . \IPS\Settings::i()->nexus_autoresolve_days . 'D' ) )->relative() ), \IPS\Email::TYPE_TRANSACTIONAL );
					$email->send( $request->email ?: $request->author() );
					
					$request->ar_notify = TRUE;
					$request->save();
				}
			}
		}
		else
		{
			$this->enabled = FALSE;
			$this->save();
		}
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}