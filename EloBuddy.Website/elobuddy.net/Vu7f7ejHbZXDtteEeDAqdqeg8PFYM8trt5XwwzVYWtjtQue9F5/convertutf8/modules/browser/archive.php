<?php
/**
 * @brief		Web conversion process
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\modules\browser;
use \IPSUtf8\Output\Browser\Template;

/**
 * Web Conversion process
 */
class archive extends \IPSUtf8\Dispatcher\Controller
{	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function manage()
	{
		$output = NULL;
		
		switch( \IPSUtf8\Session::i()->status )
		{
			default:
				$this->welcome();
			break;
			case 'completed':
				/* Regardless of current status, if the archive table is not utf8 we need to convert it */
				if ( \IPSUtf8\Convert\Archive::i()->getNonUtf8Tables( TRUE ) !== NULL )
				{
					$this->welcome();
				}
				else
				{
					$this->completed();
				}
			break;
		}
	}
	
	/**
	 * Process
	 *
	 * @return	void
	 */
	public function process()
	{
		if ( isset( \IPSUtf8\Request::i()->use_utf8mb4 ) )
		{
			$sessionData = \IPSUtf8\Session::i()->json;
				
			$sessionData['use_utf8mb4'] = ( ! empty( \IPSUtf8\Request::i()->use_utf8mb4 ) ) ? 1 : 0;
			
			\IPSUtf8\Session::i()->json = $sessionData;
			\IPSUtf8\Session::i()->save();
		}
		
		\IPSUtf8\Convert\Archive::i()->process( 100 );
		
		$json    = \IPSUtf8\Session::i()->json;
		$percent = round( ($json['convertedCount'] / \IPSUtf8\Convert\Archive::i()->getTotalRowsToConvert()) * 100, 2 );
		$msg     = "Processing " . \IPSUtf8\Session::i()->current_table . ' (Total: ' . $percent . '%)';
		
		if ( in_array( \IPSUtf8\Convert\Archive::ARCHIVE_TABLE, array_keys( \IPSUtf8\Session::i()->completed_json ) ) )
		{
			\IPSUtf8\Session::i()->status = 'completed';
			\IPSUtf8\Session::i()->save();
		}

		if ( ! \IPSUtf8\Request::i()->isAjax() )
		{
			\IPSUtf8\Output\Browser::i()->output = Template::process( \IPSUtf8\Session::i()->status, $percent, $msg );
		}
		else
		{
			\IPSUtf8\Output\Browser::i()->sendOutput( json_encode( array( \IPSUtf8\Session::i()->status, $percent, $msg ) ), 200, 'application/json' );
		}
	}
	
	/**
	 * Completed
	 *
	 * @return	void
	 */
	public function reset()
	{
		\IPSUtf8\Session::i()->reset();
		return $this->welcome();
	}
	
	/**
	 * Completed
	 *
	 * @return	void
	 */
	public function completed()
	{
		return $this->finish();
	}
	
	/**
	 * We're finished! (In a good way)
	 *
	 * @return	void
	 */
	public function finish()
	{
		\IPSUtf8\Convert\Archive::i()->renameTables();
		\IPSUtf8\Session::i()->status         = null;
		\IPSUtf8\Session::i()->completed_json = array();
		\IPSUtf8\Session::i()->reset();
		
		\IPSUtf8\Output\Browser::i()->output = Template::finished();
	}
	
	/**
	 * Welcome page
	 *
	 * @return	void
	 */
	public function welcome()
	{
		if ( ! is_writable( THIS_PATH . '/tmp' ) )
		{
			\IPSUtf8\Output\Browser::i()->error("Please ensure that '" . THIS_PATH . '/tmp' . "' is writable.");
			exit();
		}
		
		if ( ! count( \IPSUtf8\Session::i()->has_archive ) )
		{
			\IPSUtf8\Output\Browser::i()->error("Cannot locate a separate database for the post archive.");
			exit();
		}

		
		if ( ! count( \IPSUtf8\Session::i()->tables ) )
		{
			\IPSUtf8\Output\Browser::i()->error("No tables found for processing. Please check to ensure the correct database name and prefix are being used.");
			exit();
		}
		
		$json    = \IPSUtf8\Session::i()->json;
		$percent = ( \IPSUtf8\Convert\Archive::i()->getTotalRowsToConvert() ) ? round( ($json['convertedCount'] / \IPSUtf8\Convert\Archive::i()->getTotalRowsToConvert() ) * 100, 2 ) : 100;
		
		$isUtf8     = (boolean) ( \IPSUtf8\Convert\Archive::i()->databaseIsUtf8() );
		$processing = (boolean) ( \IPSUtf8\Session::i()->status === 'processing' );
		
		\IPSUtf8\Output\Browser::i()->output = Template::welcome( $isUtf8, \IPSUtf8\Session::i()->status, $percent, 'archive' );
	}
	
}