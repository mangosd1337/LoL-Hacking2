<?php
/**
 * @brief		Archive Exception Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Archive;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Login Exception Class
 */
class _Exception extends \RuntimeException
{
	const COULD_NOT_OPEN = 1;
	const COULD_NOT_WRITE = 2;
}