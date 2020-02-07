<?php
/**
 * @brief		Public bootstrap
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */
 /*if($_SERVER['REMOTE_ADDR'] != '94.71.49.176'){
echo "Temporary unscheduled maintenance<br> ETA: 5 minutes";
exit;
}*/
$_SERVER['SCRIPT_FILENAME']	= __FILE__;
require_once 'init.php';
\IPS\Dispatcher\Front::i()->run();