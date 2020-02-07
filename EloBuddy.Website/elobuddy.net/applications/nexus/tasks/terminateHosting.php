<?php
/**
 * @brief		Terimate Expired Hosting Accounts Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Aug 2014
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
 * Terimate Expired Hosting Accounts Task
 */
class _terminateHosting extends \IPS\Task
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
		if ( \IPS\Settings::i()->nexus_hosting_terminate and \IPS\Settings::i()->nexus_hosting_terminate != -1 )
		{			
			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id IN(?) AND ps_active=0 AND ps_cancelled=0 AND ps_expire<?', 'nexus', 'package', \IPS\Db::i()->select( 'p_id', 'nexus_packages', array( 'p_type=?', 'hosting' ) ), \IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Settings::i()->nexus_hosting_terminate . 'D' ) )->getTimestamp() ) ), 'IPS\nexus\Purchase' ) as $purchase )
			{
				$purchase->cancelled = TRUE;
				$purchase->can_reactivate = FALSE;
				$purchase->save();
			}
		}
		else
		{
			$this->enabled = FALSE;
			$this->save();
		}
		
		return NULL;
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