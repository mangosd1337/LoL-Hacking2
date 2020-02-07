<?php
/**
 * @brief		Dispatcher (CLI)
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Tools
 * @since		4 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Dispatcher;

/**
 * CLI Dispatcher
 */
class Cli extends \IPSUtf8\Dispatcher
{
	/**
	 * Run
	 */
	public function run()
	{
		$obj = new \IPSUtf8\modules\cli\cli;
		$obj->execute();
	}
	
	/**
	 * Init
	 */
	public function init()
	{
		
	}

	
}
