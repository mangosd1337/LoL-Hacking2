<?php
/**
 * @brief		A HTMLPurifier Attribute Definition used for attributes which must be internal URLs
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Mar 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * A HTMLPurifier Attribute Definition used for attributes which must be internal URLs
 */
class _HtmlPurifierInternalLinkDef extends \HTMLPurifier_AttrDef_URI
{
	/**
	 * @brief	Allowed bases
	 */
	protected $allowedBases = NULL;
	
	/**
	 * Constructor
	 *
     * @param	bool		$embeds_resource		Does the URI here result in an extra HTTP request?
     * @param	array|null	$allowedBases			If an array is provided, only URLs with the query strings set allowed - for example array( array( 'app' => 'core', 'module' => 'members', 'controller' => 'profile' ) ) will only allow URLs to profiles. If NULL, any internal URL beside the open proxy and attachment downloads is allowed
     */
    public function __construct( $embeds_resource = false, $allowedBases = NULL )
    {
	    $this->allowedBases = $allowedBases;
        return parent::__construct( $embeds_resource );
    }
	
	/**
	 * Validate
	 * 
     * @param	string					$uri
     * @param	\HTMLPurifier_Config	$config
     * @param	\HTMLPurifier_Context	$context
     * @return	bool|string
     */
    public function validate($uri, $config, $context)
    {
	    /* Create the URL */
	    $url = new \IPS\Http\Url( str_replace( '%7B___base_url___%7D/', \IPS\Settings::i()->base_url, $uri ) );
	    
	    /* If it's not internal, we can stop now */
	    if ( !$url->isInternal )
	    {
		    return FALSE;
	    }
	    	    
	    /* If we have allowed templates, check those... */
	    if ( $this->allowedBases )
	    {
		    $isOkay = FALSE;
		    $queryString = $url->isFriendly ? $url->getFriendlyUrlData() : $url->queryString;
		    
		    foreach ( $this->allowedBases as $requiredQueryString )
		    {
			    $queryStringDiff = array_diff( $requiredQueryString, $queryString );
			    if ( empty( $queryStringDiff ) )
			    {
				    $isOkay = TRUE;
				    break;
			    }
		    }
		    
		    if ( !$isOkay )
		    {
			    return FALSE;
		    }
	    }

	    /* Otherwise exclude the open proxy */
	    if ( ( isset( $url->queryString['section'] ) and $url->queryString['section'] == 'redirect' ) or ( isset( $url->queryString['controller'] ) and $url->queryString['controller'] == 'redirect' ) )
	    {
		    return FALSE;
	    }

	    /* And exclude the attachments downloading gateway */
	    if( mb_strpos( $url->data['path'], 'applications/core/interface/file/' ) !== FALSE )
	    {
	    	return FALSE;
	    }

	    /* And exclude uploaded files (we do this by checking the beginning of the URL against all upload configurations) */
	    foreach( static::getUploadUrls() as $uploadBaseUrl )
	    {
	    	if( mb_strpos( (string) $url, $uploadBaseUrl ) === 0 )
	    	{
	    		return FALSE;
	    	}
	    }
	    	    
	    /* Still here? We can pass up */
	    return parent::validate( $uri, $config, $context );
    }

    /**
     * @brief	Uploaded file base URLs (i.e. URL to uploads directory)
     */
    protected static $uploadUrls	= NULL;

    /**
     * Fetch and cache upload URLs
     *
     * @return	array
     */
    protected static function getUploadUrls()
    {
    	if( static::$uploadUrls !== NULL )
    	{
    		return static::$uploadUrls;
    	}

    	static::$uploadUrls	= array();

		foreach( \IPS\File::getConfigurations() as $configuration )
		{
			$class = \IPS\File::getClass( $configuration );

			if( $class->baseUrl() !== NULL )
			{
				$url = new \IPS\Http\Url( $class->baseUrl() );

				if( $url->isInternal )
				{
					static::$uploadUrls[ $class->baseUrl() ]	= $class->baseUrl();
				}
			}
		}

    	return static::$uploadUrls;
    }
}