<?php
/**
 * @brief		Multiple Redirector
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		3 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Multiple Redirector
 */
class _MultipleRedirect
{
	/**
	 * @brief	URL
	 */
	protected $url = '';
	
	/**
	 * @brief	Output
	 */
	protected $output = '';
	
	/**
	 * @brief	Prevents the final redirect
	 */
	public $noFinalRedirect = FALSE;

	/**
	 * Constructor
	 *
	 * @param	string		$url			The URL where the redirector takes place
	 * @param	callback	$callback		The function to run - should return an array with three elements, or NULL to indicate the process is finished or a string to display -
	 *	@li	Data to pass back to itself for the next "step"
	 *	@li	A message to display to the user
	 *	@li	[Optional] A number between 	1 and 100 for a progress bar
	 * @param	callback	$finished		Code to run when finished
	 * @param	bool		$finalRedirect	If FALSE, will not force a real redirect to the finished method
	 * @return	void
	 */
	public function __construct( $url, $callback, $finished, $finalRedirect=TRUE )
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'global_core.js', 'core', 'global' ) );
		
		$this->url = $url;
		 
		if ( isset( \IPS\Request::i()->mr ) and ! isset( \IPS\Request::i()->mr_continue ) )
		{
			$data = json_decode( urldecode( base64_decode( \IPS\Request::i()->mr ) ), TRUE );
					
			if ( $data === '__done' )
			{
				call_user_func( $finished );
				return;
			}
			
			try
			{ 
				$response = call_user_func( $callback, $data );
				
				if ( $response === NULL )
				{
					if ( !\IPS\Request::i()->isAjax() )
					{
						call_user_func( $finished );
						return;
					}
					else
					{
						\IPS\Output::i()->json( array( base64_encode( urlencode( json_encode( $finalRedirect ? '__done' : '__done__' ) ) ) ) );
					}
				}
				elseif ( is_string( $response[0] ) )
				{
					$this->output = $response[0];
					if ( \IPS\Request::i()->isAjax() )
					{
						\IPS\Output::i()->json( array( 'custom' => $response[0] ) );
					}
				}
				else
				{
					if ( !\IPS\Request::i()->isAjax() )
					{
						\IPS\Output::i()->redirect( $this->url->setQueryString( 'mr', base64_encode( urlencode( json_encode( $response[0] ) ) ) ), $response[1], 303, TRUE );
					}
					else
					{
						$response[0] = base64_encode( json_encode( $response[0] ) );
						\IPS\Output::i()->json( $response );
					}
				}
			}
			catch ( \Exception $e )
			{
				if ( \IPS\IN_DEV )
				{
					\IPS\Output::i()->error( $e->getMessage() . ' ' . $e->getLine() . ' ' . str_replace( "\n", "<br>", $e->getTraceAsString() ), '1S111/1', 403, '' );
				}

				\IPS\Log::log( $e, 'multiredirect' );
				\IPS\Output::i()->error( $e->getMessage(), '1S111/1', 403, '' );
			}
		}
	}
	
	/**
	 * Get Starting HTML
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->output ?: \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->multipleRedirect( $this->url, ( ( isset( \IPS\Request::i()->mr_continue ) and isset( \IPS\Request::i()->mr ) ) ? \IPS\Request::i()->mr : base64_encode( json_encode( 0 ) ) ) );
	}
}