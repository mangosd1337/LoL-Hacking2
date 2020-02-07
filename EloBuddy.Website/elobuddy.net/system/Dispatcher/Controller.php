<?php
/**
 * @brief		Abstract class that Controllers should extend
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Dispatcher;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Abstract class that Controllers should extend
 */
abstract class _Controller
{
	/**
	 * @brief	Base URL
	 */
	public $url;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Http\Url|NULL	$url		The base URL for this controller or NULL to calculate automatically
	 * @return	void
	 */
	public function __construct( $url=NULL )
	{
		if ( $url === NULL )
		{
			$class		= get_called_class();
			$exploded	= explode( '\\', $class );
			$this->url = \IPS\Http\Url::internal( "app={$exploded[1]}&module={$exploded[4]}&controller={$exploded[5]}", \IPS\Dispatcher::i()->controllerLocation );
		}
		else
		{
			$this->url = $url;
		}
	}

	/**
	 * Force a specific method within a controller to execute.  Useful for unit testing.
	 *
	 * @param	null|string		$method		The specific method to call
	 * @return	mixed
	 */
	public function forceExecute( $method=NULL )
	{
		if( \IPS\ENFORCE_ACCESS === true and $method !== null )
		{
			if ( method_exists( $this, $method ) )
			{
				return call_user_func( array( $this, $method ) );
			}
			else
			{
				return $this->execute();
			}
		}

		return $this->execute();
	}

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		if( \IPS\Request::i()->do and preg_match( '/^[a-zA-Z\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', \IPS\Request::i()->do ) )
		{
			if ( method_exists( $this, \IPS\Request::i()->do ) or method_exists( $this, '__call' ) )
			{
				call_user_func( array( $this, \IPS\Request::i()->do ) );
			}
			else
			{
				\IPS\Output::i()->error( 'page_not_found', '2S106/1', 404, '' );
			}
		}
		else
		{
			if ( method_exists( $this, 'manage' ) or method_exists( $this, '__call' ) )
			{
				$this->manage();
			}
			else
			{
				\IPS\Output::i()->error( 'page_not_found', '2S106/2', 404, '' );
			}
		}
	}
	
	/**
	 * Embed
	 *
	 * @return	void
	 */
	protected function embed()
	{
		$title = \IPS\Member::loggedIn()->language()->addToStack('error_title');
		
		if( !isset( static::$contentModel ) )
		{
			$output = \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedUnavailable();
		}
		else
		{			
	        try
	        {
	            $class = static::$contentModel;
	            $params = array();
	            	            
	            if( \IPS\Request::i()->embedComment )
	            {
		            $commentClass = $class::$commentClass;
		            if ( isset( $class::$archiveClass ) )
		            {
			            $item = call_user_func( array( $class, 'loadAndCheckPerms' ), \IPS\Request::i()->id );
			            if ( $item->isArchived() )
			            {
				            $commentClass = $class::$archiveClass;
			            }
		            }
		            
		            $content = call_user_func( array( $commentClass, 'loadAndCheckPerms' ), \IPS\Request::i()->embedComment );
		            $title = $content->item()->mapped('title');
				}
				elseif( \IPS\Request::i()->embedReview )
	            {
		            $content = call_user_func( array( $class::$reviewClass, 'loadAndCheckPerms' ), \IPS\Request::i()->embedReview );
		            $title = $content->item()->mapped('title');
				}
				else
				{
	                if ( isset( \IPS\Request::i()->page ) and \IPS\Request::i()->page > 1 )
	                {
		                $params['page'] = intval( \IPS\Request::i()->page );
	                }
	                if ( isset( \IPS\Request::i()->embedDo ) )
	                {
		                $params['do'] = \IPS\Request::i()->embedDo;
	                }
					
		            $content = call_user_func( array( $class, 'loadAndCheckPerms' ), \IPS\Request::i()->id );
		            $title = $content instanceof \IPS\Node\Model ? $content->_title : $content->mapped('title');
				}
				
				if ( !( $content instanceof \IPS\Content\Embeddable ) )
				{
					$output = \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedUnavailable();
				}
				else
				{
					$output = $content->embedContent( $params );
				}
				
	        }
	        catch( \Exception $e )
	        {
	            $output = \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->embedNoPermission();
	        }
		}
		
		\IPS\Output::i()->base = '_parent';
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'embed.css' ) );
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output, $title ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
    }
}