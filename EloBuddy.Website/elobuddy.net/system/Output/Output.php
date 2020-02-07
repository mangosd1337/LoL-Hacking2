<?php
/**
 * @brief		Output Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
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
 * Output Class
 */
class _Output
{
	/**
	 * @brief	HTTP Statuses
	 * @link	http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
	 */
	public static $httpStatuses = array( 100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 429 => 'Too Many Requests', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported' );
	
	/**
	 * @brief	Singleton Instance
	 */
	protected static $instance = NULL;
	
	/**
	 * @brief	Global javascript bundles
	 */
	public static $globalJavascript = array( 'admin.js', 'front.js', 'framework.js', 'library.js', 'map.js' );
	
	/**
	 * @brief	Javascript map of file object URLs
	 */
	protected static $javascriptObjects = null;
	
		/**
	 * @brief	File object classes
	 */
	protected static $fileObjectClasses = array();
	
	/**
	 * @brief	Meta tags for the current page
	 */
	public $metaTags	= array();
	
	/**
	 * @brief	Other <link rel=""> tags
	 */
	public $linkTags = array();
	
	/**
	 * @brief	RSS feeds for the current page
	 */
	public $rssFeeds = array();

	/**
	 * @brief	Custom meta tag page title
	 */
	public $metaTagsTitle	= '';

	/**
	 * @brief	Requested URL fragment for meta tag editing
	 */
	public $metaTagsUrl	= '';
	
	/**
	 * Get instance
	 *
	 * @return	\IPS\Output
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		/* Inline Message */
		if( isset( $_SESSION['inlineMessage'] ) )
		{
			if( !\IPS\Request::i()->isAjax() )
			{
				static::$instance->inlineMessage = $_SESSION['inlineMessage'];
				$_SESSION['inlineMessage'] = NULL;
			}
		}

		return static::$instance;
	}
	
	/**
	 * @brief	Additional HTTP Headers
	 */
	public $httpHeaders = array(
		'X-XSS-Protection' => '0',	// This is so when we post contents with scripts (which is possible in the editor, like when embedding a Twitter tweet) the broswer doesn't block it
	);
	
	/**
	 * @brief	Stored Page Title
	 */
	public $title = '';
	
	/**
	 * @brief	Stored Content to output
	 */
	public $output = '';
	
	/**
	 * @brief	URLs for CSS files to include
	 */
	public $cssFiles = array();
	
	/**
	 * @brief	URLs for JS files to include
	 */
	public $jsFiles = array();
	
	/**
	 * @brief	URLs for JS files to include with async="true"
	 */
	public $jsFilesAsync = array();
	
	/**
	 * @brief	Other variables to hand to the JavaScript
	 */
	public $jsVars = array();
	
	/**
	 * @brief	Other raw JS - this is included inside an existing <script> tag already, so you should omit wrapping tags
	 */
	public $headJs = '';

	/**
	 * @brief	Raw CSS to output, used to send custom CSS that may need to be dynamically generated at runtime
	 */
	public $headCss = '';

	/**
	 * @brief	Anything set in this property will be output right before </body> - useful for certain third party scripts that need to be output at end of page
	 */
	public $endBodyCode = '';
	
	/**
	 * @brief	Breadcrumb
	 */
	public $breadcrumb = array();
	
	/**
	 * @brief	Page is responsive?
	 */
	public $responsive = TRUE;
	
	/**
	 * @brief	Sidebar
	 */
	public $sidebar = array();
	
	/**
	 * @brief	Global controllers
	 */
	public $globalControllers = array();
	
	/**
	 * @brief	Additional CSS classes to add to body tag
	 */
	public $bodyClasses = array();
	
	/**
	 * @brief	Elements that can be hidden from view
	 */
	public $hiddenElements = array();
	
	/**
	 * @brief	Inline message
	 */
	public $inlineMessage = '';
	
	/**
	 * @brief	Page Edit URL
	 */
	public $editUrl	= NULL;
	
	/**
	 * @brief	<base target="">
	 */
	public $base	= NULL;
	
	/**
	 * @brief	Allow default widgets with this output
	 */
	public $allowDefaultWidgets = TRUE;
	
	/**
	 * Get a JS bundle
	 *
	 * JS Bundle Cheatsheet
	 * library.js (this is jQuery, mustache, underscore, jstz, etc)
	 * framework.js (this is ui/*, utils/*, ips.model.js, ips.controller.js and the editor controllers)
     * admin.js or front.js (these are controllers, templates and models which are used everywhere for that location)
	 * app.js (this is all models for a single application)
	 * {location}_{section}.js (this is all controllers and templates for this section called ad-hoc when needed)
	 *
	 * @param	string		$file		Filename
	 * @param	string|null	$app		Application
	 * @param	string|null	$location	Location (e.g. 'admin', 'front')
	 * @return	array		URL to JS files
	 */
	public function js( $file, $app=NULL, $location=NULL )
	{
		$file = trim( $file, '/' );
			 
		if ( $location === 'interface' AND mb_substr( $file, -3 ) === '.js' )
		{
			/* @see http://community.invisionpower.com/4bugtrack/some-js-files-get-loaded-with-a-double-in-the-url-r3558/ */
			return array( rtrim( \IPS\Http\Url::baseUrl( \IPS\Http\Url::PROTOCOL_RELATIVE ), '/' ) . "/applications/{$app}/interface/{$file}?v=" . ( defined( \IPS\CACHEBUST_KEY ) ? \IPS\CACHEBUST_KEY : time() ) );
		}
		elseif ( \IPS\IN_DEV )
		{
			return \IPS\Output\Javascript::inDevJs( $file, $app, $location );
		}
		else
		{
			if ( class_exists( 'IPS\Dispatcher', FALSE ) and \IPS\Dispatcher::i()->controllerLocation === 'setup' )
			{
				return array();
			}
			
			if ( $app === null OR $app === 'global' )
			{
				if ( in_array( $file, static::$globalJavascript ) )
				{
					/* Global bundle (admin.js, front.js, library.js, framework.js, map.js) */
					return array( static::_getJavascriptFileObject( 'global', 'root', $file )->url );
				}
				
				/* Languages JS file */
				if ( mb_substr( $file, 0, 8 ) === 'js_lang_' )
				{
					return array( static::_getJavascriptFileObject( 'global', 'root', $file )->url );
				}
			}
			else
			{
				$app      = $app      ?: \IPS\Request::i()->app;
				$location = $location ?: \IPS\Dispatcher::i()->controllerLocation;

				/* plugin.js */
				if ( $app === 'core' and $location === 'plugins' and $file === 'plugins.js' )
				{
					$pluginsJs = static::_getJavascriptFileObject( 'core', 'plugins', 'plugins.js' );
					
					if ( $pluginsJs !== NULL )
					{
						return array( $pluginsJs->url );
					}
				}
				/* app.js - all models and ui */
				else if ( $file === 'app.js' )
				{
					$fileObj = static::_getJavascriptFileObject( $app, $location, 'app.js' );
					
					if ( $fileObj !== NULL )
					{
						return array( $fileObj->url );
					}
				}
				/* {location}_{section}.js */
				else if ( mb_strstr( $file, '_') AND mb_substr( $file, -3 ) === '.js' )
				{
					list( $location, $key ) = explode( '_',  mb_substr( $file, 0, -3 ) );
						
					if ( ( $location == 'front' OR $location == 'admin' OR $location == 'global' ) AND ! empty( $key ) )
					{
						$fileObj = static::_getJavascriptFileObject( $app, $location, $location . '_' . $key . '.js' );
						
						if ( $fileObj !== NULL )
						{
							return array( $fileObj->url );
						}
					}
				}
			}
		}
		
		return array();
	}
	
	/**
	 * Removes JS files from \IPS\File
	 *
	 * @param	string|null	$app		Application
	 * @param	string|null	$location	Location (e.g. 'admin', 'front')
	 * @param	string|null	$file		Filename
	 * @return	void
	 */
	public static function clearJsFiles( $app=null, $location=null, $file=null )
	{
		$javascriptObjects = ( isset( \IPS\Data\Store::i()->javascript_map ) ) ? \IPS\Data\Store::i()->javascript_map : array();
			
		if ( $location === null and $file === null )
		{
			if ( $app === null or $app === 'global' )
			{
				try
				{
					\IPS\File::getClass('core_Theme')->deleteContainer( 'javascript_global' );
				} catch( \Exception $e ) { }
				
				unset( $javascriptObjects['global'] );
			}
			
			foreach( \IPS\Application::applications() as $key => $data )
			{
				if ( $app === null or $app === $key )
				{
					try
					{
						\IPS\File::getClass('core_Theme')->deleteContainer( 'javascript_' . $key );
					} catch( \Exception $e ) { }
					
					unset( $javascriptObjects[ $key ] );
				}
			}
		}
		
		if ( $file )
		{
			$key = md5( $app .'-' . $location . '-' . $file );
			
			if ( isset( $javascriptObjects[ $app ] ) and is_array( $javascriptObjects[ $app ] ) and in_array( $key, array_keys( $javascriptObjects[ $app ] ) ) )
			{
				if ( $javascriptObjects[ $app ][ $key ] !== NULL )
				{
					\IPS\File::get( 'core_Theme', $javascriptObjects[ $app ][ $key ] )->delete();
					
					unset( $javascriptObjects[ $app ][ $key ] );
				}
			}
		}
		
		\IPS\Data\Store::i()->javascript_map = $javascriptObjects;
	}

	/**
	 * Check page title and modify as needed
	 *
	 * @param	string	$title	Page title
	 * @return	string
	 */
	public function getTitle( $title )
	{
		if( $this->metaTagsTitle )
		{
			$title	= $this->metaTagsTitle;
		}

		if( !\IPS\Settings::i()->site_online )
		{
			$title	= sprintf( \IPS\Member::loggedIn()->language()->get( 'offline_title_wrap' ), $title );
		}

		return $title;
	}

	/**
	 * Retrieve cache headers
	 *
	 * @param	int		$lastModified	Last modified timestamp
	 * @param	int		$cacheSeconds	Number of seconds to cache for
	 * @return	array
	 */
	public static function getCacheHeaders( $lastModified, $cacheSeconds )
	{
		return array(
			'Date'			=> \IPS\DateTime::ts( time(), TRUE )->rfc1123(),
			'Last-Modified'	=> \IPS\DateTime::ts( $lastModified, TRUE )->rfc1123(),
			'Expires'		=> \IPS\DateTime::ts( ( time() + $cacheSeconds ), TRUE )->rfc1123(),
			'Cache-Control'	=> "max-age=" . $cacheSeconds . ", public",
			'Pragma'		=> "public",
		);
	}

	/**
	 * Retrieve Content-disposition header. Formats filename according to requesting client.
	 *
	 * @param	string		$disposition	Disposition: attachment or inline
	 * @param	string		$filename		Filename
	 * @return	string
	 * @see		<a href='http://code.google.com/p/browsersec/wiki/Part2#Downloads_and_Content-Disposition'>Browser content-disposition handling</a>
	 * @see		<a href='http://community.invisionpower.com/resources/bugs.html/_/4-0-0/downloads-special-characters-in-filenames-r46080'>Chrome does not need name encoded anymore</a>
	 */
	public static function getContentDisposition( $disposition='attachment', $filename=NULL )
	{
		if( $filename === NULL )
		{
			return $disposition;
		}

		$return	= $disposition . '; filename';

		if ( !\IPS\Dispatcher::hasInstance() )
		{
			\IPS\Session\Front::i();
		}
		
		switch( \IPS\Session::i()->userAgent->useragentKey )
		{
			case 'firefox':
			case 'opera':
				$return	.= "*=UTF-8''" . rawurlencode( $filename );
			break;

			case 'explorer':
			//case 'chrome':
				$return	.= '="' . rawurlencode( $filename ) . '"';
			break;

			default:
				$return	.= '="' . $filename . '"';
			break;
		}

		return $return;
	}
	
	/**
	 * Return a JS file object, recompiling it first if doesn't exist.
	 *
	 * @param	string|null	$app		Application
	 * @param	string|null	$location	Location (e.g. 'admin', 'front')
	 * @param	string		$file		Filename
	 * @return	string					URL to JS file object
	 */
	protected static function _getJavascriptFileObject( $app, $location, $file )
	{
		$key = md5( $app .'-' . $location . '-' . $file );

		$javascriptObjects = ( isset( \IPS\Data\Store::i()->javascript_map ) ) ? \IPS\Data\Store::i()->javascript_map : array();

		if ( isset( $javascriptObjects[ $app ] ) and in_array( $key, array_keys( $javascriptObjects[ $app ] ) ) )
		{
			if ( $javascriptObjects[ $app ][ $key ] === NULL )
			{
				return NULL;
			}
			else
			{
				return \IPS\File::get( 'core_Theme', $javascriptObjects[ $app ][ $key ] );
			}
		}
		
		/* We're setting up, do nothing to avoid compilation requests when tables are incomplete */
		if ( ! isset( \IPS\Settings::i()->setup_in_progress ) OR \IPS\Settings::i()->setup_in_progress )
		{
			return NULL;
		}
			
		/* Still here? */
		if ( \IPS\Output\Javascript::compile( $app, $location, $file ) === NULL )
		{
			/* Rebuild already in progress */
			return NULL;
		}

		/* The map may have changed */
		$javascriptObjects = ( isset( \IPS\Data\Store::i()->javascript_map ) ) ? \IPS\Data\Store::i()->javascript_map : array();
		
		/* Test again */
		if ( isset( $javascriptObjects[ $app ] ) and in_array( $key, array_keys( $javascriptObjects[ $app ] ) ) and $javascriptObjects[ $app ][ $key ] )
		{
			return \IPS\File::get( 'core_Theme', $javascriptObjects[ $app ][ $key ] );
		}
		else
		{
			/* Still not there, set this map key to null to prevent repeat access attempts */
			$javascriptObjects[ $app ][ $key ] = null;
			
			\IPS\Data\Store::i()->javascript_map = $javascriptObjects;
		}
		
		return NULL;
	}
	
	/**
	 * Display Error Screen
	 *
	 * @param	string			$message		language key for error message
	 * @param	mixed			$code			Error code
	 * @param	int				$httpStatusCode	HTTP Status Code
	 * @param	string			$adminMessage	language key for error message to show to admins
	 * @param	array			$httpHeaders	Additional HTTP Headers
	 * @param	string			$extra			Additional information (such backtrace or API error) which will be shown to admins
	 * @return	void
	 */
	public function error( $message, $code, $httpStatusCode=500, $adminMessage=NULL, $httpHeaders=array(), $extra=NULL )
	{
		/* If we just logged out, we don't want to show a "no permission" error */
		if ( isset( \IPS\Request::i()->_fromLogout ) )
		{
			$this->redirect( \IPS\Settings::i()->base_url );
		}
		
		/* If we're in an external script, just show a simple message */
		if ( !\IPS\Dispatcher::hasInstance() )
		{
			\IPS\Session\Front::i();

			$this->sendOutput( \IPS\Member::loggedIn()->language()->get( $message ), $httpStatusCode, 'text/html', $httpHeaders, FALSE );
			return;
		}
		
		/* Work out the title */
		$title = "{$httpStatusCode}_error_title";
		$title = \IPS\Member::loggedIn()->language()->checkKeyExists( $title ) ? \IPS\Member::loggedIn()->language()->addToStack( $title ) : \IPS\Member::loggedIn()->language()->addToStack( 'error_title' );

		/* If we're in setup, just display it */
		if ( \IPS\Dispatcher::i()->controllerLocation === 'setup' )
		{
			$this->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( $title, \IPS\Theme::i()->getTemplate( 'global', 'core' )->error( $title, $message, $code, $extra ) ), $httpStatusCode, 'text/html', $httpHeaders, FALSE );
		}
		
		/* Are we an administrator logged in as a member? */
		$member = \IPS\Member::loggedIn();
		if ( isset( $_SESSION['logged_in_as_key'] ) )
		{
			try
			{
				$_member = \IPS\Member::load( $_SESSION['logged_in_from']['id'] );
				if ( $_member->member_id == $_SESSION['logged_in_from']['id'] )
				{
					$member = $_member;
				}
			}
			catch ( \OutOfRangeException $e ) { }
		}
		
		/* Which message are we showing? */
		if( $member->isAdmin() and $adminMessage )
		{
			$message = $adminMessage;
		}
		if ( \IPS\Member::loggedIn()->language()->checkKeyExists( $message ) )
		{
			$message = \IPS\Member::loggedIn()->language()->addToStack( $message );
		}
		
		/* Replace language stack keys with actual content */
		\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $message );
								
		/* Log */
		$level = intval( \substr( $code, 0, 1 ) );
		if( !\IPS\Session::i()->userAgent->spider )
		{
			if( $code and \IPS\Settings::i()->error_log_level and $level >= \IPS\Settings::i()->error_log_level )
			{
				\IPS\Db::i()->insert( 'core_error_logs', array(
					'log_member'		=> \IPS\Member::loggedIn()->member_id ?: 0,
					'log_date'			=> time(),
					'log_error'			=> $message,
					'log_error_code'	=> $code,
					'log_ip_address'	=> \IPS\Request::i()->ipAddress(),
					'log_request_uri'	=> $_SERVER['REQUEST_URI'],
					) );
			}

			if( \IPS\Settings::i()->error_notify_level and $level >= \IPS\Settings::i()->error_notify_level )
			{
				\IPS\Email::buildFromTemplate( 'core', 'error_log', array( $code, $message ), \IPS\Email::TYPE_TRANSACTIONAL )->send( \IPS\Settings::i()->email_in );
			}
		}
			
		/* If this is an AJAX request, send a JSON response */
		if( \IPS\Request::i()->isAjax() )
		{
			$this->json( $message, $httpStatusCode );
		}
				
		/* Send output */
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		$this->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( $title, \IPS\Theme::i()->getTemplate( 'global', 'core' )->error( $title, $message, $code, $extra, $member ), array( 'app' => \IPS\Dispatcher::i()->application ? \IPS\Dispatcher::i()->application->directory : NULL, 'module' => \IPS\Dispatcher::i()->module ? \IPS\Dispatcher::i()->module->key : NULL, 'controller' => \IPS\Dispatcher::i()->controller ) ), $httpStatusCode, 'text/html', $httpHeaders, FALSE, FALSE );
	}

	/**
	 * Send a header.  This is abstracted in an effort to better isolate code for testing purposes.
	 *
	 * @param	string	$header	Text to send as a fully formatted header string
	 * @return	void
	 */
	public function sendHeader( $header )
	{
		/* If we are running our test suite, we don't want to send browser headers */
		if( \IPS\ENFORCE_ACCESS === true AND mb_strtolower( php_sapi_name() ) == 'cli' )
		{
			return;
		}

		header( $header );
	}

	/**
	 * Send a header.  This is abstracted in an effort to better isolate code for testing purposes.
	 *
	 * @param	int	$httpStatusCode	HTTP Status Code
	 * @return	void
	 */
	public function sendStatusCodeHeader( $httpStatusCode )
	{
		/* Set HTTP status */
		if( isset( $_SERVER['SERVER_PROTOCOL'] ) and \strstr( $_SERVER['SERVER_PROTOCOL'], '/1.0' ) !== false )
		{
			$this->sendHeader( "HTTP/1.0 {$httpStatusCode} " . static::$httpStatuses[ $httpStatusCode ] );
		}
		else
		{
			$this->sendHeader( "HTTP/1.1 {$httpStatusCode} " . static::$httpStatuses[ $httpStatusCode ] );
		}
	}

	/**
	 * Send output
	 *
	 * @param	string	$output				Content to output
	 * @param	int		$httpStatusCode		HTTP Status Code
	 * @param	string	$contentType		HTTP Content-type
	 * @param	array	$httpHeaders		Additional HTTP Headers
	 * @param	bool	$cacheThisPage		Can/should this page be cached?
	 * @param	bool	$pageIsCached		Is the page from a cache? If TRUE, no language parsing will be done
	 * @param	bool	$parseFileObjects	Should <fileStore.xxx> and <___base_url___> be replaced in the output?
	 * @return	void
	 */
	public function sendOutput( $output='', $httpStatusCode=200, $contentType='text/html', $httpHeaders=array(), $cacheThisPage=TRUE, $pageIsCached=FALSE, $parseFileObjects=TRUE )
	{
		/* Replace language stack keys with actual content */
		if ( \IPS\Dispatcher::hasInstance() and !in_array( $contentType, array( 'text/javascript', 'text/css', 'application/json' ) ) and $output and !$pageIsCached )
		{
			\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $output );
		}
		
		/* Parse file storage URLs */
		if ( $output and $parseFileObjects )
		{
			$this->parseFileObjectUrls( $output );
		}

		/* Full page caching for guests */
		if (
			\IPS\CACHE_PAGE_TIMEOUT and													// Page caching is enabled
			$cacheThisPage and															// Some pages can specify not to be cached (for example, when displaying a cached page, you don't want it recached)
			!isset( \IPS\Request::i()->cookie['noCache'] ) and							// A noCache cookie might get set to not cache a particular guest (for example, if they have added items to their cart in the store)
			mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and					// Is a HTTP GET request (don't cache output for POSTs)
			!isset( \IPS\Request::i()->csrfKey ) and 									// CSRF key isn't present (which would be like a POST request)
			$contentType == 'text/html' and												// Output is HTML (don't cache RSS feeds, JSON output, etc)
			\IPS\Dispatcher::hasInstance() and class_exists( 'IPS\Dispatcher', FALSE ) and \IPS\Dispatcher::i()->controllerLocation === 'front' and	// Is a normal, front-end page (necessary to know if user is logged in)
			!\IPS\Member::loggedIn()->member_id											// User is not logged in
		) {
			\IPS\Data\Cache::i()->storeWithExpire( 'page_' . md5( ( !empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] ) . '/' . $_SERVER['REQUEST_URI'] ) . '_' . \IPS\Member::loggedIn()->language()->id . '_' . \IPS\Theme::i()->id, array(
				'output'		=> str_replace( \IPS\Session::i()->csrfKey, '{{csrfKey}}', $output ),
				'code'			=> $httpStatusCode,
				'contentType'	=> $contentType,
				'httpHeaders'	=> $httpHeaders,
				'lastUpdated'	=> time()
			), \IPS\DateTime::create()->add( new\DateInterval( 'PT' . \IPS\CACHE_PAGE_TIMEOUT . 'S' ) ), TRUE );
		}
		
		/* Query Log (has to be done after parseOutputForDisplay because runs queries and after page caching so the log isn't misleading) */
		if ( \IPS\QUERY_LOG or \IPS\CACHING_LOG )
		{
			/* Close the session and run tasks now so we can see those queries */
			session_write_close();
			if ( \IPS\Dispatcher::hasInstance() )
			{
				\IPS\Dispatcher::i()->__destruct();
			}
			
			/* And run */
			$cachingLog = \IPS\Data\Cache::i()->log;
			$queryLog = \IPS\Db::i()->log;
			if ( \IPS\QUERY_LOG )
			{
				$output = str_replace( '<!--ipsQueryLog-->', \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->queryLog( $queryLog ), $output );
			}
			if ( \IPS\CACHING_LOG )
			{
				$output = str_replace( '<!--ipsCachingLog-->', \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->cachingLog( $cachingLog ), $output );
			}
		}

		/* Remove anything from the output buffer that should not be there as it can confuse content-length */
		@ob_end_clean();

		/* Trim any blank spaces before the beginning of output */
		$output = ltrim( $output );
				
		/* Set HTTP status */
		$this->sendStatusCodeHeader( $httpStatusCode );
				
		/* Start buffering */
		ob_start();
		
		/* If the browser supports gzip, gzip the content - we do this ourselves so that we can send Content-Length even with mod_gzip */
		if ( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) and \strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false )
		{
			if ( function_exists( 'gzencode' ) and (bool) ini_get('zlib.output_compression') === false )
			{
				$output = gzencode( $output ); // mod_gzip will encode pages, but we want to encode ourselves so that Content-Length is correct
				$this->sendHeader("Content-Encoding: gzip"); // Tells the server we've alredy encoded so it doesn't need to
			}
		}
		
		/* Output */
		print $output;

		/* Update advertisement impression counts, if appropriate */
		\IPS\core\Advertisement::updateImpressions();

		/* Send headers */
		$this->sendHeader( "Content-type: {$contentType};charset=UTF-8" );

		/* Send content-length header, but only if not using zlib.output_compression, because in that case the length we send in the header
			will not match the length of the actual content sent to the browser, breaking things (particularly json) */
		if( (bool) ini_get('zlib.output_compression') === false )
		{
			$size = ob_get_length();
			$this->sendHeader( "Content-Length: {$size}" ); // Makes sure the connection closes after sending output so that tasks etc aren't holding it open
		}
		
		foreach ( $httpHeaders as $key => $header )
		{
			$this->sendHeader( $key . ': ' . $header );
		}
		$this->sendHeader( "Connection: close" );

		/* If we are running our test suite, we don't want to output or exit, which will allow the test suite to capture the response */
		if( \IPS\ENFORCE_ACCESS === true AND mb_strtolower( php_sapi_name() ) == 'cli' )
		{
			return;
		}

		/* Flush and exit */
		@ob_end_flush();
		@flush();

		/* If using PHP-FPM, close the request so that __destruct tasks are run after data is flushed to the browser
			@see http://www.php.net/manual/en/function.fastcgi-finish-request.php */
		if( function_exists( 'fastcgi_finish_request' ) )
		{
			fastcgi_finish_request();
		}

		exit;
	}
	
	/**
	 * Send JSON output
	 *
	 * @param	string	$data	Data to be JSON-encoded
	 * @param	int		$httpStatusCode		HTTP Status Code
	 * @return	void
	 */
	public function json( $data, $httpStatusCode=200 )
	{
		\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $data );
		return $this->sendOutput( json_encode( \IPS\Member::loggedIn()->language()->stripVLETags( $data ) ), $httpStatusCode, 'application/json' );
	}
	
	/**
	 * Redirect
	 *
	 * @param	\IPS\Http\Url	$url			URL to redirect to
	 * @param	string			$message		Optional message to display
	 * @param	int				$httpStatusCode	HTTP Status Code
	 * @param	bool			$forceScreen	If TRUE, an intermediate screen will be shown
	 * @return	void
	 */
	public function redirect( $url, $message='', $httpStatusCode=301, $forceScreen=FALSE )
	{
		if( \IPS\Request::i()->isAjax() )
		{
			if ( $message !== '' )
			{
				$message =  \IPS\Member::loggedIn()->language()->checkKeyExists( $message ) ? \IPS\Member::loggedIn()->language()->addToStack( $message ) : $message;
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $message );
			}

			$this->json( array(
					'redirect' => (string) $url,
					'message' => $message
			)	);
		}
		elseif ( $forceScreen === TRUE or ( $message and !$url->isInternal ) )
		{
			/* We cannot send a 3xx status code without a Location header, or some browsers (cough IE) will not actually redirect. We are showing
				an intermediary page performing the redirect through a meta refresh tag, so a 200 status is appropriate in this case. */
			$httpStatusCode = ( mb_substr( $httpStatusCode, 0, 1 ) == 3 ) ? 200 : $httpStatusCode;

			$this->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->redirect( $url, $message ), $httpStatusCode );
		}
		else
		{
			if ( $message )
			{
				$message = \IPS\Member::loggedIn()->language()->addToStack( $message );
				\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $message );
				$_SESSION['inlineMessage'] = $message;
			}

			/* Send location and no-cache headers to prevent redirects from being cached */
			$headers = array(
				"Location"		=> (string) $url,
				"Cache-Control"	=> "no-cache, no-store, must-revalidate",
				"Pragma"		=> "no-cache",
				"Expires"		=> "0",
			);

			$this->sendOutput( '', $httpStatusCode, '', $headers );
		}
	}
	
	/**
	 * Replace the {{fileStore.xxxxxx}} urls to the actual URLs
	 *
	 * @param	string	$output		The compiled output
	 * @return void
	 */
	public function parseFileObjectUrls( &$output )
	{
		if ( \stristr( $output, '<fileStore.' ) )
		{
			preg_match_all( '#<fileStore.([\d\w\_]+?)>#', $output, $matches, PREG_SET_ORDER );
			
			foreach( $matches as $index => $data )
			{
				if ( isset( $data[1] ) )
				{
					if ( ! isset( static::$fileObjectClasses[ $data[1] ] ) )
					{
						try
						{
							static::$fileObjectClasses[ $data[1] ] = \IPS\File::getClass( $data[1] );
						}
						catch ( \RuntimeException $e )
						{
							static::$fileObjectClasses[ $data[1] ] = NULL;
						}
					}
					
					if ( static::$fileObjectClasses[ $data[1] ] )
					{
						$output = str_replace( $data[0], static::$fileObjectClasses[ $data[1] ]->baseUrl(), $output );
					}
				}
			}
		}
		
		/* ___base_url___ is a bit dramatic but it prevents accidental replacements with tags called base_url if a third party app or hook uses it */
		$output = str_replace( '<___base_url___>', rtrim( \IPS\Settings::i()->base_url, '/' ), $output );
	}
	
	/**
	 * Show Offline
	 *
	 * @return	void
	 */
	public function showOffline()
	{
		$this->bodyClasses[] = 'ipsLayout_minimal';
		$this->bodyClasses[] = 'ipsLayout_minimalNoHome';
		
		$this->output = \IPS\Theme::i()->getTemplate( 'system', 'core' )->offline( \IPS\Settings::i()->site_offline_message );
		$this->title  = \IPS\Settings::i()->board_name;
		
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		
		$this->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( $this->title, $this->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 503 );
	}
	
	/**
	 * Checks and rebuilds JS map if it is broken
	 *
	 * @param	string	$app	Application
	 * @return	void
	 */
	protected function _checkJavascriptMap( $app )
	{
		$javascriptObjects = ( isset( \IPS\Data\Store::i()->javascript_map ) ) ? \IPS\Data\Store::i()->javascript_map : array();

		if ( ! is_array( $javascriptObjects ) OR ! count( $javascriptObjects ) OR ! isset( $javascriptObjects[ $app ] ) )
		{
			/* Map is broken or missing, recompile all JS */
			\IPS\Output\Javascript::compile( $app );
		}
	}

	/**
	 * Fetch meta tags for the current page.  Must be called before sendOutput() in order to reset title.
	 *
	 * @return	void
	 */
	public function buildMetaTags()
	{
		/* Set basic ones */
		$this->metaTags['og:site_name'] = \IPS\Settings::i()->board_name;
		$this->metaTags['og:locale'] = preg_replace( "/^([a-zA-Z0-9\-_]+?)(?:\..*?)$/", "$1", \IPS\Member::loggedIn()->language()->short );
		
		/* Add the site name to the title */
		if( \IPS\Settings::i()->board_name )
		{
			$this->title .= ' - ' . \IPS\Settings::i()->board_name;
		}
		
		/* Add Admin-specified ones */
		if( !$this->metaTagsUrl )
		{
			$this->metaTagsUrl	= \IPS\Request::i()->url()->getFurlQuery();
	
			if ( isset( \IPS\Data\Store::i()->metaTags ) )
			{
				$rows = \IPS\Data\Store::i()->metaTags;
			}
			else
			{
				$rows = iterator_to_array( \IPS\Db::i()->select( '*', 'core_seo_meta' ) );
				\IPS\Data\Store::i()->metaTags = $rows;
			}
						
			if( is_array( $rows ) )
			{
				foreach ( $rows as $row )
				{
					if( \strpos( $row['meta_url'], '*' ) !== FALSE )
					{
						if( preg_match( "#^" . str_replace( '\*', '(.*)', trim( preg_quote( $row['meta_url'], '#' ), '/' ) ) . "$#i", trim( $this->metaTagsUrl, '/' ) ) )
						{
							$_tags	= json_decode( $row['meta_tags'], TRUE );
		
							if( is_array( $_tags ) )
							{
								foreach( $_tags as $_tagName => $_tagContent )
								{
									$this->metaTags[ $_tagName ]	= $_tagContent;
								}
							}
		
							/* Are we setting page title? */
							if( $row['meta_title'] )
							{
								$this->title			= $row['meta_title'];
								$this->metaTagsTitle	= $row['meta_title'];
							}
						}
					}
					else
					{
						if( trim( $row['meta_url'], '/' ) == trim( $this->metaTagsUrl, '/' ) )
						{
							$_tags	= json_decode( $row['meta_tags'], TRUE );
							
							if ( is_array( $_tags ) )
							{
								foreach( $_tags as $_tagName => $_tagContent )
								{
									$this->metaTags[ $_tagName ]	= $_tagContent;
								}
							}
							
							/* Are we setting page title? */
							if( $row['meta_title'] )
							{
								$this->title			= $row['meta_title'];
								$this->metaTagsTitle	= $row['meta_title'];
							}
						}
					}
				}
			}
		}
		
		$baseUrl = parse_url( \IPS\Settings::i()->base_url );	

		foreach( $this->metaTags as $name => $value )
		{
			if ( ! is_array( $value ) )
			{
				$value = array( $value );
			}
			
			foreach( $value as $tag )
			{
				if ( mb_substr( $tag, 0, 2 ) === '//' )
				{
					/* Try to preserve http vs https */
					if( isset( $baseUrl['scheme'] ) )
					{
						$tag = str_replace( '//', $baseUrl['scheme'] . '://', $tag );
					}
					else
					{
						$tag = str_replace( '//', 'http://', $tag );
					}
					
					$this->metaTags[ $name ] = $tag;
				}
			}
		}
	}
	
	/**
	 * License Warning
	 *
	 * @return	string|NULL		"none" = no license key. "expired" = license key expired. NULL = no error
	 */
	public function licenseKeyWarning()
	{
		if ( !\IPS\Settings::i()->ipb_reg_number and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'licensekey_manage' ) )
		{
			return 'none';
		}
		else
		{
			$licenseKey = \IPS\IPS::licenseKey();
			if ( ( $licenseKey === NULL or ( isset( $licenseKey['legacy'] ) and $licenseKey['legacy'] ) ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'licensekey_manage' ) )
			{
				return 'none';
			}
			elseif ( ( ( isset( $licenseKey['expires'] ) and strtotime( $licenseKey['expires'] ) < time() ) or ! isset( $licenseKey['active'] ) or !$licenseKey['active'] ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'licensekey_manage' ) )
			{
				return 'expired';
			}
		}
		
		return NULL;
	}

	/**
	 * @brief	Global search menu options
	 */
	protected $globalSearchMenuOptions	= NULL;
	
	/**
	 * @brief	Contextual search menu options
	 */
	public $contextualSearchOptions = array();
	
	/**
	 * @brief	Default search option
	 */
	public $defaultSearchOption	= array( 'all', 'search_everything' );

	/**
	 * Retrieve options for search menu
	 *
	 * @return	array
	 */
	public function globalSearchMenuOptions()
	{
		if( $this->globalSearchMenuOptions === NULL )
		{
			foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
			{
				if( is_subclass_of( $class, 'IPS\Content\Searchable' ) )
				{
					if ( $class::$includeInSiteSearch )
					{
						$type	= mb_strtolower( str_replace( '\\', '_', mb_substr( $class, 4 ) ) );
						$this->globalSearchMenuOptions[ $type ] = $type . '_pl';
					}
				}
			}
		}
		
		/* This is also supported, but is not a content item class implementing \Searchable */
		if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) )
		{
			$this->globalSearchMenuOptions['core_members'] = 'core_members_pl';
		}

		return $this->globalSearchMenuOptions;
	}

	/**
	 * Include a file and return the output
	 *
	 * @param	string	$path	Path or URL
	 * @return	string
	 */
	public static function safeInclude( $path )
	{
		ob_start();
		include( \IPS\ROOT_PATH . DIRECTORY_SEPARATOR . $path );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
}