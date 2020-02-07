<?php
/**
 * @brief		Upload Custom Field Download Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Feb 2016
 * @version		SVN_VERSION_NUMBER
 */

require_once str_replace( 'applications/core/interface/file/cfield.php', '', str_replace( '\\', '/', __FILE__ ) ) . 'init.php';

try
{
	/* Get the extension */
	list( $app, $extension ) = explode( '_', \IPS\Request::i()->storage );
	$classname = 'IPS\\' . $app . '\extensions\core\FileStorage\\' . $extension;	
	if ( !class_exists( $classname ) )
	{
		throw new \RuntimeException;
	}
	$extension = new $classname;
	
	/* Check the file is valid */
	$file = \IPS\File::get( \IPS\Request::i()->storage, \IPS\Request::i()->path );
	if ( !$extension->isValidFile( $file->url ) )
	{
		throw new \RuntimeException;
	}
	
	/* Send headers and print file */
	\IPS\Output::i()->sendStatusCodeHeader( 200 );
	\IPS\Output::i()->sendHeader( "Content-type: " . \IPS\File::getMimeType( $file->originalFilename ) . ";charset=UTF-8" );
	foreach( array_merge( \IPS\Output::getCacheHeaders( time(), 360 ), array( "Content-Disposition" => \IPS\Output::getContentDisposition( 'attachment', $file->originalFilename ), "X-Content-Type-Options" => "nosniff" ) ) as $key => $header )
	{
		\IPS\Output::i()->sendHeader( $key . ': ' . $header );
	}
	\IPS\Output::i()->sendHeader( "Content-Length: " . $file->filesize() );
	$file->printFile();
	exit;
}
catch ( \Exception $e )
{
	\IPS\Dispatcher\Front::i();
	\IPS\Output::i()->sendOutput( '', 404 );
}