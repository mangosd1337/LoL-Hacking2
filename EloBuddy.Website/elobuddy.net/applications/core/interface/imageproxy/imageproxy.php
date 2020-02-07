<?php
/**
* @brief		Image Proxy
* @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
* @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
* @license		http://www.invisionpower.com/legal/standards/
* @package		IPS Community Suite
* @since		29 Jun 2015
* @version		SVN_VERSION_NUMBER
*/

/* Init */
require_once str_replace( 'applications/core/interface/imageproxy/imageproxy.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';
$url = \IPS\Request::i()->img;

/* Check for a valid key */
if ( !\IPS\Login::compareHashes( hash_hmac( "sha256", $url, \IPS\SITE_SECRET_KEY ?: md5( \IPS\Settings::i()->sql_pass . \IPS\Settings::i()->board_url . \IPS\Settings::i()->sql_database ) ), (string) \IPS\Request::i()->key ) )
{
	\IPS\Output::i()->sendOutput( NULL, 500 );
}


/* Check the cache */
try
{
	$cacheEntry = \IPS\Db::i()->select( '*', 'core_image_proxy', array( 'md5_url=?', md5( $url ) ) )->first();
	
	if ( $cacheEntry )
	{
		/* Set the cache expiration time */
		$cacheExpires = new \DateTime;  // Use of \DateTime is intentional, do not replace with \IPS\DateTime
		$cacheExpires->setTimestamp( (int) $cacheEntry['cache_time'] );
		$cacheExpires->add( new \DateInterval( sprintf( 'P%dD', \IPS\Settings::i()->image_proxy_cache_period ) ) );

		$file = \IPS\File::get( 'core_Attachment', $cacheEntry['location'] );
	}
	else
	{
		\IPS\Output::i()->sendOutput( NULL, 500 );
	}
}

/* Not in cache - fetch and store */
catch ( \UnderflowException $e )
{
	/* Set the cache expiration time */
	$cacheExpires = new \DateTime;  // Use of \DateTime is intentional, do not replace with \IPS\DateTime
	$cacheExpires->add( new \DateInterval( sprintf( 'P%dD', \IPS\Settings::i()->image_proxy_cache_period ) ) );

	/* First, let's store a placeholder row that we will later update - this prevents being able to DOS the server if the image is crazy */
	\IPS\Db::i()->insert( 'core_image_proxy', array(
		'md5_url'		=> md5( $url ),
		'location'		=> NULL,
		'cache_time'	=> time(),
	) );

	try
	{
		$output = \IPS\Http\Url::external( mb_substr( $url, 0, 2 ) === '//' ? "http:{$url}" : $url )->request()->get();
		/* Check it's a valid image */
		$image = \IPS\Image::create( (string) $output );
	}
	catch ( \Exception $e )
	{
		\IPS\Output::i()->sendOutput( NULL, 500 );
	}
	
	/* Work out an appropriate filename */
	$extension = mb_substr( $url, mb_strrpos( $url, '.' ) + 1 );
	if ( in_array( $extension, \IPS\Image::$imageExtensions ) )
	{
		$filename = mb_substr( $url, mb_strrpos( $url, '/' ) + 1 );
		if ( mb_strlen( $filename ) > 200 )
		{
			$filename = mb_substr( $filename, 0, 150 ) . '.' . $extension;
		}
	}
	else
	{
		$filename = md5( uniqid() ) . '.' . $image->type;
	}
	
	/* Cache */
	$file = \IPS\File::create( 'core_Attachment', $filename, (string) $output, 'imageproxy' );
	\IPS\Db::i()->replace( 'core_image_proxy', array(
		'md5_url'		=> md5( $url ),
		'location'		=> (string) $file,
		'cache_time'	=> time(),
	) );
}

/* Output */
\IPS\Output::i()->sendOutput( $file->contents(), 200, \IPS\File::getMimeType( $file->filename ), array(
	'cache-control' => 'public, max_age=' . max( ( $cacheExpires->getTimestamp() - time() ), 0 ) . ', must-revalidate',
	'expires' => $cacheExpires->format( 'D, d M Y H:i:s \G\M\T' )
) );