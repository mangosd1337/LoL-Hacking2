<?php
/**
 * @brief		Public sitemap gateway file
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

/**
 * Path to your IP.Board directory with a trailing /
 * Leave blank if you have not moved sitemap.php
 */
$_SERVER['SCRIPT_FILENAME']	= __FILE__;
$path	= '';

$_GET['app']		= 'core';
$_GET['module']		= 'sitemap';
$_GET['controller']	= 'sitemap';

require_once $path . 'init.php';

if ( \IPS\Request::i()->testsettings )
{
    exit;
}

\IPS\Dispatcher\Front::i()->run();