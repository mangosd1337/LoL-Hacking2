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

namespace IPSUtf8\Db;

/**
 * Exception class for database errors
 */
class Exception extends \RuntimeException
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
	 * @todo	Log it
	 */
	public function __construct( $message = null, $code = 0, $previous = null, $query=NULL, $binds=array() )
	{
		$this->query = $query;
		$this->binds = $binds;
		
		return parent::__construct( $message, $code, $previous );
	}
	
	/**
	 * Send to IPS?
	 *
	 * @return	bool
	 */
	public function shouldSendDiagnosticsReport()
	{
		/* Low-end server errors */
		if ( $this->getCode() < 1046 or in_array( $this->getCode(), array( 1129, 1130, 1194, 1195, 1203 ) ) )
		{
			return FALSE;
		}
		
		/* Low-end client errors */
		if ( $this->getCode() >= 2000 and $this->getCode() < 2029 )
		{
			return FALSE;
		}
		
		return TRUE;
	}
}