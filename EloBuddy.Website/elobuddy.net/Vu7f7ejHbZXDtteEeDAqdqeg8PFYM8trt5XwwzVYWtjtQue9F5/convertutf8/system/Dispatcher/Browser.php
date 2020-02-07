<?php
/**
 * @brief		Dispatcher (Web)
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Tools
 * @since		4 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Dispatcher;
use \IPSUtf8\Output\Browser\Template;

/**
 * Web Dispatcher
 */
class Browser extends \IPSUtf8\Dispatcher
{
	/**
	 * Run
	 */
	public function run()
	{
		/* Is this already IPS4 or has the lock been removed? */
		if( ! \BYPASS_SAFETY_LOCK and \IPSUtf8\Convert::i()->databaseIsIPS4() )
		{
			\IPSUtf8\Output\Browser::i()->error( \IPSUtf8\Output\Browser\Template::errorAlreadyIPS4() );
		}
		
		$this->controller = ( \IPSUtf8\Request::i()->controller ) ? \IPSUtf8\Request::i()->controller : 'browser';
		
		$fileLocation = \THIS_PATH . '/modules/browser/' . $this->controller . '.php';
		$classname = 'IPSUtf8\\modules\\browser\\' . $this->controller;
		
		if( !file_exists( $fileLocation ) )
		{
			\IPSUtf8\Output\Browser::i()->error( "Page doesn't exist" );
		}
		
		/* Run */
		$this->controllerObj = new $classname;
		if( !( $this->controllerObj instanceof \IPSUtf8\Dispatcher\Controller ) )
		{
			\IPSUtf8\Output\Browser::i()->error( "Page doesn't exist" );
		}
		
		$this->controllerObj->execute();

		/* Output */
		if ( \IPSUtf8\Request::i()->isAjax() )
		{
			\IPSUtf8\Output\Browser::i()->sendOutput( Template::blankTemplate( \IPSUtf8\Output\Browser::i()->output ), 200, 'text/html', \IPSUtf8\Output\Browser::i()->httpHeaders );
		}
		else
		{
			\IPSUtf8\Output\Browser::i()->sendOutput( Template::wrapper( \IPSUtf8\Output\Browser::i()->title, \IPSUtf8\Output\Browser::i()->output ), 200, 'text/html', \IPSUtf8\Output\Browser::i()->httpHeaders );
		}
	}
	
	/**
	 * Init
	 */
	public function init()
	{
		
	}

	
}
