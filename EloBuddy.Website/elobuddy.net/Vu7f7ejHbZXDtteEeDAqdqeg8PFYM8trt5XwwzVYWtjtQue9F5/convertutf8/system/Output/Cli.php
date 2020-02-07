<?php
/**
 * @brief		Output Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Output;

/**
 * Output Class
 */
class Cli extends \IPSUtf8\Output
{
	/**
	 * @Brief STDIN Handle
	 */
	protected static $stdin = null;
	
	/**
	 * @Brief Progress bar has been drawn
	 */
	protected static $barDrawn = false;
	
	/**
	 * @Brief Response
	 */
	public $response = null;

	/**
	 * Display Error Screen
	 *
	 * @param	string	$message		language string for error message
	 * @return	void
	 */
	public function error( $message )
	{
		/* Send output */
		parent::error( $message );
		exit();
	}
	
	/**
	 * Display a CLI progress bar
	 *
	 * @param	string	$table	    Table processing
	 * @param	int		$total	    Total number
	 * @param	int		$current	Current number processed
	 * @param	int		$size		Total size of the bar
	 * @return string
	 */
	public function progressBar( $table, $total, $current, $size=50 )
	{
		$percent       = ( $current > 0 AND $total > 0 ) ? round( ($current / $total) * 100, 0 ) : 0;
		$percentString = str_pad( $percent, 6, ' ', STR_PAD_LEFT );
		$totalSize     = $size + 9 + 33; # 23 is 'Processing: tablename'
		$output        = '';
		
		if ( $table !== null )
		{
			$tableName = ( mb_strlen( $table ) > 20 ) ? mb_substr( $table, 0, 20 ) : str_pad( $table, 20 );
		}
		else
		{
			$tableName = str_repeat( ' ', 20 );
		}
		
		/* No progress bar for you! */
		if ( \IPSUtf8\Request::i()->isBasicClient() === true )
		{
			return "Processing: " . $tableName . '; Total complete: ' . $percent . "%\n";
		}
		
		/* Remove previous bar */
		if ( static::$barDrawn === true ) 
		{
			for ( $i = $totalSize; $i > 0; $i-- )
			{ 
				$output .= "\x08"; 
			} 
		} 
		
		$output .= "Processing: " . $tableName . ' ';
		
		/* Print the bar */
	 	for ( $i = 0; $i <= $size; $i++ ) 
	 	{ 
		 	if ( $i <= ( $current / $total * $size ) )
		 	{
			 	$output .= "\033[42m \033[0m";
		 	}
		 	else
		 	{
			 	$output .= "\033[47m \033[0m";
		 	}
		}
		
		$output .= ' ' . $percentString . '%';
		
		static::$barDrawn = true;
		
		return $output;
	}
	
	/**
	 * Send output and listen for a keypress
	 *
	 * @param	string	$output			Content to output
	 * @param	int		$httpStatusCode	HTTP Status Code
	 * @param	string	$contentType	HTTP Content-type
	 * @param	array	$httpHeaders	Additional HTTP Headers
	 * @return	void
	 */
	public function sendOutput( $output='', $httpStatusCode=200, $contentType='text/plain', $httpHeaders=array() )
	{
		if ( mb_stristr( $output, "<warning>" ) )
		{
			if ( \IPSUtf8\Request::i()->isBasicClient() === true )
			{
				$output = str_replace( "<warning>" , "!! ", $output );
				$output = str_replace( "</warning>", " !!", $output );
			}
			else
			{
				$output = str_replace( "<warning>" , "\033[1;37m\033[41m", $output );
				$output = str_replace( "</warning>", "\033[0m", $output );
			}
		}
		
		if ( mb_stristr( $output, "<good>" ) )
		{
			if ( \IPSUtf8\Request::i()->isBasicClient() === true )
			{
				$output = str_replace( "<good>" , "", $output );
				$output = str_replace( "</good>", "", $output );
			}
			else
			{
				$output = str_replace( "<good>" , "\033[1;37m\033[42m", $output );
				$output = str_replace( "</good>", "\033[0m", $output );
			}
		}
		
		if ( mb_stristr( $output, "<choice>" ) )
		{
			if ( \IPSUtf8\Request::i()->isBasicClient() === true )
			{
				$output = str_replace( "<choice>" , "", $output );
				$output = str_replace( "</choice>", "", $output );
			}
			else
			{
				$output = str_replace( "<choice>" , "\033[1;32m", $output );
				$output = str_replace( "</choice>", "\033[0m", $output );
			}
		}
		
		if ( $httpStatusCode != 101 )
		{
			$output .= "\n";
		}
		
		$window = fopen('php://stdout', 'w');
		fwrite( $window, $output );
		fclose( $window );
		
		if ( static::$stdin === null )
		{
			static::$stdin = fopen('php://stdin', 'r');
		}
		
		if ( $httpStatusCode === 100 )
		{
			$this->response = trim( fgets( static::$stdin ) );
		}
	}
}