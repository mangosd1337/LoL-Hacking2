<?php
/**
 * @brief		Log Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Mar 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Log Class
 */
class _Log extends \IPS\Patterns\ActiveRecord
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_log';
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$this->time = time();
	}
	
	/**
	 * @brief	Track if we are currently logging to prevent recursion for database issues
	 */
	static protected $currentlyLogging	= FALSE;

	/**
	 * Write a log message
	 *
	 * @param	\Exception|string	$message	An Exception object or a generic message to log
	 * @param	string|null			$category	An optional string identifying the type of log (for example "upgrade")
	 * @return	\IPS\Log|null
	 */
	public static function log( $message, $category = NULL )
	{
		/* Try to log it to the database */
		try
		{
			if( static::$currentlyLogging === TRUE )
			{
				throw new \RuntimeException;
			}

			static::$currentlyLogging	= TRUE;

			$log = new static;
			
			if ( $message instanceof \Exception )
			{
				$log->exception_class = get_class( $message );
				$log->exception_code = $message->getCode();

				if ( method_exists( $message, 'extraLogData' ) )
				{
					$log->message = $message->extraLogData() . "\n" . $message->getMessage();
				}
				else
				{
					$log->message = $message->getMessage();
				}

				$log->backtrace = $message->getTraceAsString();
			}
			else
			{
				if ( is_array( $message ) )
				{
					$message = var_export( $message, TRUE );
				}
				$log->message = $message;
				$log->backtrace = ( new \Exception )->getTraceAsString();
			}
			
			$log->category = $category;
			$log->save();

			static::$currentlyLogging	= FALSE;
			
			return $log;
		}
		/* If that fails, log to disk */
		catch ( \Exception $e )
		{
			if ( !\IPS\NO_WRITES )
			{
				/* What are we writing? */
				$date = date('r');
				if ( $message instanceof \Exception )
				{
					$messageToLog = $date . "\n" . get_class( $message ) . '::' . $message->getCode() . "\n" . $message->getMessage() . "\n" . $message->getTraceAsString();
				}
				else
				{
					if ( is_array( $message ) )
					{
						$message = var_export( $message, TRUE );
					}
					$messageToLog = $date . "\n" . $message . "\n" . ( new \Exception )->getTraceAsString();
				}
				
				/* Where are we writing it? */
				$dir = str_replace( '{root}', \IPS\ROOT_PATH, \IPS\LOG_FALLBACK_DIR );
				if ( !is_dir( $dir ) )
				{
					if ( !@mkdir( $dir ) or !@chmod( $dir, \IPS\IPS_FOLDER_PERMISSION ) )
					{
						return;
					}
				}
				
				/* Write it */
				$header = "<?php exit; ?>\n\n";
				$file = $dir . '/' . date( 'Y' ) . '_' . date( 'm' ) . '_' . date('d') . '_' . ( $category ?: 'nocategory' ) . '.php';
				if ( file_exists( $file ) )
				{
					@\file_put_contents( $file, "\n\n-------------\n\n" . $messageToLog, FILE_APPEND );
				}
				else
				{
					@\file_put_contents( $file, $header . $messageToLog );
				}
				@chmod( $file, \IPS\IPS_FILE_PERMISSION );
			}
		}
		
		return NULL;
	}
	
	/**
	 * Write a debug message
	 *
	 * @param	\Exception|string	$message	An Exception object or a generic message to log
	 * @param	string|null			$category	An optional string identifying the type of log (for example "upgrade")
	 * @return	\IPS\Log|null
	 */
	public static function debug( $message, $category = NULL )
	{
		if ( defined('\IPS\DEBUG_LOG') and \IPS\DEBUG_LOG )
		{
			return static::log( $message, $category );
		}
		return NULL;
	}
	
	/**
	 * Get fallback directory
	 *
	 * @return	string
	 */
	public static function fallbackDir()
	{
		return str_replace( '{root}', \IPS\ROOT_PATH, \IPS\LOG_FALLBACK_DIR );
	}
	
	/**
	 * Prune logs
	 * 
	 * @param	int		$days	Older than (days) to prune
	 * @return	void
	 */
	public static function pruneLogs( $days )
	{
		\IPS\Db::i()->delete( static::$databaseTable, array( 'time<?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $days . 'D' ) )->getTimestamp() ) );
		
		if ( !\IPS\NO_WRITES )
		{
			try
			{
				$it = new \DirectoryIterator( static::fallbackDir() );
			}
			catch ( Exception $e )
			{
				return;
			}
			
			foreach( $it as $file )
			{
				try
				{
					if( $file->isDot() or !$file->isFile() )
					{
						continue;
					}
			
					if( preg_match( "#.cgi$#", $file->getFilename(), $matches ) or ( preg_match( "#.php$#", $file->getFilename(), $matches ) and $file->getMTime() < ( time() - ( 60 * 60 * 24 * (int) $days ) ) ) )
					{
						@unlink( $file->getPathname() );
					}
				}
				catch ( Exception $e ) { }
			}
		}
	}
	
	/* !Legacy */
	
	/**
	 * @brief		The latest severity set by i()
	 * @deprecated
	 */
	static protected $severity = LOG_NOTICE;
	
	/**
	 * This method is deprecated but provided for code that may still use \IPS\Log::i()->write()
	 *
	 * @deprecated
	 * @param	int	$key	Severity level
	 * @return	\IPS\Log
	 */
	public static function i( $key )
	{
		static::$severity = $key;
		return new static;
	}
	
	/**
	 * This method is deprecated but provided for code that may still use \IPS\Log::i()->write()
	 *
	 * @deprecated
	 * @param	string	$message	Message to log
	 * @param	string	$suffix		Category
	 * @return	void
	 */
	public function write( $message, $suffix=NULL )
	{
		if ( static::$severity === LOG_DEBUG )
		{
			static::debug( $message, $suffix );
		}
		else
		{
			static::log( $message, $suffix );
		}
	}
}