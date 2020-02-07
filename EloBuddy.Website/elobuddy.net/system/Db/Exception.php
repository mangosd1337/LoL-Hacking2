<?php
/**
 * @brief		Exception class for database errors
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Db;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Exception class for database errors
 */
class _Exception extends \RuntimeException
{
	/**
	 * @brief	Query
	 */
	public $query;
	
	/**
	 * @brief	Binds
	 */
	public $binds = array();
	
	/**
	 * Constructor
	 *
	 * @param	string			$message	MySQL Error message
	 * @param	int				$code		MySQL Error Code
	 * @param	\Exception|NULL	$previous	Previous Exception
	 * @param	string|NULL		$query		MySQL Query that caused exception
	 * @param	array			$binds		Binds for query
	 * @return	void
	 * @see		<a href='https://bugs.php.net/bug.php?id=30471'>Recursion "bug" with var_export()</a>
	 */
	public function __construct( $message = null, $code = 0, $previous = null, $query=NULL, $binds=array() )
	{
		$this->query = $query;
		$this->binds = $binds;
		
		if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation !== 'setup' )
		{
			$log = array( $message );
			
			if ( ! empty( $query ) )
			{
				$log[] = $query;
			}
			
			if ( count( $binds ) )
			{
				$log[] = print_r( $binds, TRUE );
			}

			$backtrace	= debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
			$_error_string	= '';

			if ( count( $backtrace ) )
			{
				$_error_string = "\n | File                                                                       | Function                                                                      | Line No.          |";
				$_error_string .= "\n |----------------------------------------------------------------------------+-------------------------------------------------------------------------------+-------------------|";
				
				foreach( $backtrace as $i => $data )
				{
					if ( !isset( $data['file'] ) )
					{
						$data['file'] = '';
					}
					
					if ( !isset( $data['line'] ) )
					{
						$data['line'] = '';
					}
					
					if ( !isset( $data['class'] ) )
					{
						$data['class'] = '';
					}
					
					if ( defined('IPS\\ROOT_PATH') )
					{
						$data['file'] = str_replace( \IPS\ROOT_PATH, '', $data['file'] );
					}
					
					/* Reset */
					$data['func'] = "[" . $data['class'] . '].' . $data['function'];
					
					/* Pad right */
					$data['file'] = str_pad( $data['file'], 75 );
					$data['func'] = str_pad( $data['func'], 78 );
					$data['line'] = str_pad( $data['line'], 18 );
					
					$_error_string .= "\n | " . $data['file'] . "| " . $data['func'] . '| ' . $data['line'] . '|';
					$_error_string .= "\n '----------------------------------------------------------------------------+-------------------------------------------------------------------------------+-------------------'";
				}
			}
			
			\IPS\Log::log( implode( "\n", $log ) . $_error_string, 'sql' );
		}
		
		return parent::__construct( $message, $code, $previous );
	}
	
	/**
	 * Is this a server issue?
	 *
	 * @return	bool
	 */
	public function isServerError()
	{
		/* Low-end server errors */
		if ( $this->getCode() < 1046 or in_array( $this->getCode(), array( 1129, 1130, 1194, 1195, 1203 ) ) )
		{
			return TRUE;
		}
		
		/* Low-end client errors */
		if ( $this->getCode() >= 2000 and $this->getCode() < 2029 )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Additional log data?
	 *
	 * @return	string
	 */
	public function extraLogData()
	{
		return \IPS\Db::_replaceBinds( $this->query, $this->binds );
	}
}