<?php
/**
 * @brief		Expire Invoices Task
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		01 Apr 2014
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
 * Expire Invoices Task
 */
class _expireInvoices extends \IPS\Task
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
        $expireDate = \IPS\DateTime::create();
        if( \IPS\Settings::i()->cm_invoice_expireafter )
        {
            $expireDate->sub( new \DateInterval( 'P' . \IPS\Settings::i()->cm_invoice_expireafter . 'D' )  );
        }
		else
		{
			// they don't expire so return only NULL
			return NULL;
		}

		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_invoices', array( 'i_status=? AND i_date<?', \IPS\nexus\Invoice::STATUS_PENDING, $expireDate->getTimestamp() ), 'i_date ASC', 100 ), 'IPS\nexus\Invoice' ) as $invoice )
		{
			$invoice->status = $invoice::STATUS_EXPIRED;
			$invoice->save();

            try
            {
                $invoice->member->log( 'invoice', array(
                    'type'	=> 'expire',
                    'id'	=> $invoice->id,
                    'title'	=> $invoice->title,
                ), FALSE );
            }
            catch ( \OutOfRangeException $exception ) {}
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