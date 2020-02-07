<?php
/**
 * @brief		Support Wizard
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Wizard
 */
class _support extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'get_support' );
		parent::execute();
	}

	/**
	 * Support Wizard
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('get_support');
		\IPS\Output::i()->output = \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'support' )->support( new \IPS\Helpers\Wizard( array( 'type_of_problem' => array( $this, '_typeOfProblem' ), 'self_service' => array( $this, '_selfService' ), 'contact_support' => array( $this, '_contactSupport' ) ), \IPS\Http\Url::internal('app=core&module=support&controller=support') ) );
		
		\IPS\Output::i()->sidebar['actions']['systemcheck'] = array(
			'icon'	=> 'search',
			'link'	=> \IPS\Http\Url::internal( 'app=core&module=support&controller=support&do=systemCheck' ),
			'title'	=> 'requirements_checker',
		);		
	}
	
	/**
	 * phpinfo
	 *
	 * @return	void
	 */
	protected function phpinfo()
	{
		phpinfo();
		exit;
	}
	
	/**
	 * System Check
	 *
	 * @return	void
	 */
	protected function systemCheck()
	{
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('requirements_checker');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'support' )->healthcheck( \IPS\core\Setup\Upgrade::systemRequirements() );
	}
	
	/**
	 * Step 1: Type of problem
	 *
	 * @param	mixed	$data	Wizard data
	 * @return	string|array
	 */
	public function _typeOfProblem( $data )
	{
		$form = new \IPS\Helpers\Form( 'form', 'continue' );
		$form->class = 'ipsForm_horizontal ipsPad';
		$form->add( new \IPS\Helpers\Form\Radio( 'type_of_problem_select', NULL, TRUE, array(
			'options' 	=> array( 'advice' => 'type_of_problem_advice', 'issue' => 'type_of_problem_issue' ),
			'toggles'	=> array( 'advice' => array( 'support_advice_search' ) )
		) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'support_advice_search', NULL, NULL, array(), function( $val )
		{
			if ( !$val and \IPS\Request::i()->type_of_problem_select === 'advice' )
			{
				throw new \DomainException('form_required');
			}
		}, NULL, NULL, 'support_advice_search' ) );
		if ( $values = $form->values() )
		{
			return array( 'type' => $values['type_of_problem_select'], 'keyword' => $values['support_advice_search'] );
		}
		return (string) $form;
	}
	
	/**
	 * Step 2: Self Service
	 *
	 * @param	mixed	$data	Wizard data
	 * @return	string|array
	 */
	public function _selfService( $data )
	{
		/* Advice */
		if ( $data['type'] === 'advice' )
		{
			if ( isset( \IPS\Request::i()->next ) )
			{
				return $data;
			}
			
			$searchResults = array();
			if ( $data['keyword'] )
			{
				$search = new \IPS\core\extensions\core\LiveSearch\Settings;
				$searchResults = $search->getResults( $data['keyword'] );
			}
			
			$guides = array();
			try
			{
				$guides = \IPS\Http\Url::ips( 'guides/' . urlencode( $data['keyword'] ) )->request()->get()->decodeJson();
			}
			catch ( \Exception $e ) { }
			
			if ( count( $searchResults ) or count( $guides ) )
			{
				return \IPS\Theme::i()->getTemplate( 'support' )->advice( $searchResults, $guides );
			}
			else
			{
				return $data;
			}
		}
		
		/* Issue */
		else
		{			
			if ( isset( \IPS\Request::i()->serviceDone ) )
			{
				return $data;
			}
			
			$baseUrl = \IPS\Http\Url::internal('app=core&module=support&controller=support&_step=self_service');

			$overrideThingToTry = NULL;
			if ( isset( \IPS\Request::i()->next ) )
			{
				$overrideThingToTry = \IPS\Request::i()->next;
				$baseUrl = $baseUrl->setQueryString( 'next', $overrideThingToTry );
			}

			$possibleSolutions = array( '_requirementsChecker', '_databaseChecker', '_clearCaches', '_connectionChecker', '_md5sumChecker', '_upgradeCheck', '_disableAdvertisements', '_disableThirdParty', '_knowledgebase' );
			$self = $this;

			return new \IPS\Helpers\MultipleRedirect(
				$baseUrl,
				function( $thingToTry ) use ( $self, $possibleSolutions, $overrideThingToTry )
				{					
					if ( !is_null( $overrideThingToTry ) and $overrideThingToTry > $thingToTry )
					{
						$thingToTry = $overrideThingToTry;
					}

					if ( isset( $possibleSolutions[ $thingToTry ] ) )
					{						
						$test = call_user_func( array( $self, $possibleSolutions[ $thingToTry ] ), $thingToTry );
						if ( is_string( $test ) )
						{
							return array( $test );
						}
						else
						{
							return array( $thingToTry + 1, \IPS\Member::loggedIn()->language()->addToStack( 'looking_for_problems' ) );
						}
					}
					else
					{
						return NULL;
					}
				},
				function() use ( $baseUrl )
				{
					\IPS\Output::i()->redirect( $baseUrl->setQueryString( 'serviceDone', 1 ) );
				}
			);
		}
	}
	
	/**
	 * Step 2: Self Service - Clear Caches
	 *
	 * @param	int	$thingToTry	The current ID
	 * @return	string|NULL
	 */
	public function _clearCaches( $thingToTry )
	{
		/* Clear JS Maps first */
		\IPS\Output::clearJsFiles();
		
		/* Reset theme maps to make sure bad data hasn't been cached by visits mid-setup */
		foreach( \IPS\Theme::themes() as $id => $set )
		{
			/* Update mappings */
			$set->css_map = array();
			$set->save();
		}
		
		\IPS\Data\Store::i()->clearAll();
		\IPS\Data\Cache::i()->clearAll();

		\IPS\Member::clearCreateMenu();
		
		return \IPS\Theme::i()->getTemplate( 'support' )->tryNow( ++$thingToTry, 'support_caches_cleared' );
	}
	
	/**
	 * Step 2: Self Service - Database Checker
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _databaseChecker( $id )
	{
		$changesToMake = array();
		$db = \IPS\Db::i();

		/* Loop Apps */
		foreach ( \IPS\Application::applications() as $app )
		{
			$changesToMake = array_merge( $changesToMake, $app->databaseCheck() );
		}
		
		/* Display */
		if ( $changesToMake )
		{
			if ( isset( \IPS\Request::i()->run ) )
			{
				$erroredQueries = array();
				$errors         = array();
				foreach ( $changesToMake as $query )
				{
					try
					{
						\IPS\Db::i()->query( $query['query'] );
					}
					catch ( \Exception $e )
					{
						$erroredQueries[] = $query['query'];
						$errors[] = $e->getMessage();
					}
				}
				if ( count( $erroredQueries ) )
				{
					return \IPS\Theme::i()->getTemplate( 'support' )->databaseChecker( $id, ++$id, $erroredQueries, $errors );
				}
				else
				{
					return \IPS\Theme::i()->getTemplate( 'support' )->tryNow( ++$id, 'database_changes_made' );
				}
			}
			else
			{
				$queries = array();
				foreach ( $changesToMake as $query )
				{
					$queries[] = $query['query'];
				}
				return \IPS\Theme::i()->getTemplate( 'support' )->databaseChecker( $id, ++$id, $queries );
			}
		}
		elseif ( isset( \IPS\Request::i()->recheck ) )
		{
			return \IPS\Theme::i()->getTemplate( 'support' )->tryNow( ++$id, 'database_changes_made' );
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Step 2: Self Service - md5sum Checker
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _md5sumChecker( $id )
	{	
		try
		{
			$files = \IPS\Application::md5Check();
			if ( count( $files ) )
			{
				return \IPS\Theme::i()->getTemplate( 'support' )->md5sum( $files, $id );
			}
		}
		catch ( \Exception $e ) {}
		return NULL;
	}
	
	/**
	 * Step 2: Self Service - Requirements Checker (Includes File Permissions)
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _requirementsChecker( $id )
	{		
		$check = \IPS\core\Setup\Upgrade::systemRequirements();
		foreach ( $check['requirements'] as $group => $requirements )
		{
			foreach ( $requirements as $requirement )
			{
				if ( !$requirement['success'] )
				{
					return \IPS\Theme::i()->getTemplate( 'support' )->tryNow( $id, $requirement['message'], '', FALSE );
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * Step 2: Self Service - Connection Checker
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _connectionChecker( $id )
	{	
		try
		{
			\IPS\Http\Url::ips( 'connectionCheck' )->request()->get();
			return NULL;
		}
		catch ( \Exception $e )
		{
			return \IPS\Theme::i()->getTemplate( 'support' )->connectionChecker( ++$id );
		}
	}
	
	/**
	 * Step 2: Self Service - Check for Uprade
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _upgradeCheck( $id )
	{
		try
		{
			$response = \IPS\Http\Url::ips('updateCheck')->request()->get()->decodeJson();
			if ( $response['longversion'] > \IPS\Application::load('core')->long_version )
			{
				return \IPS\Theme::i()->getTemplate( 'support' )->upgrade( ++$id, $response['updateurl'] );
			}
		}
		catch ( \Exception $e ) { }

		return NULL;
	}
	
	/**
	 * Step 2: Self Service - Disable advertisements
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _disableAdvertisements( $id )
	{
		if ( isset( \IPS\Request::i()->enableAds ) )
		{
			foreach ( explode( ',', \IPS\Request::i()->enableAds ) as $ad )
			{			
				try
				{
					$ad = \IPS\core\Advertisement::load( $ad );
					$ad->active = 1;
					$ad->save();
				}
				catch ( \Exception $e ) {}
			}

			return NULL;
		}
		
		$disabledAds = array();
		
		/* Loop ads */
		foreach( \IPS\Db::i()->select( '*' ,'core_advertisements', array( 'ad_active=?', 1 ) ) as $ad )
		{
			$ad = \IPS\core\Advertisement::constructFromData( $ad );

			$ad->active = 0;
			$disabledAds[] = $ad->id;
		}

		/* Do any? */
		if ( count( $disabledAds ) )
		{
			return \IPS\Theme::i()->getTemplate( 'support' )->disabledAdvertisements( $id, '&enableAds=' . implode( ',', $disabledAds ) );
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Step 2: Self Service - Disable 3rd party apps/plugins
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _disableThirdParty( $id )
	{
		/* Button to put stuff back */
		if ( isset( \IPS\Request::i()->deleteTheme ) or isset( \IPS\Request::i()->enableApps ) or isset( \IPS\Request::i()->enablePlugins ) )
		{
			/* Theme */
			if ( isset( \IPS\Request::i()->deleteTheme ) )
			{
				try
				{
					\IPS\Theme::load( \IPS\Request::i()->deleteTheme )->delete();
				}
				catch ( \Exception $e ) {}
	
				if( isset( $_SESSION['old_acp_theme'] ) )
				{
					\IPS\Member::loggedIn()->acp_skin = $_SESSION['old_acp_theme'];
					\IPS\Member::loggedIn()->save();
	
					unset( $_SESSION['old_acp_theme'] );
				}
			}
			
			/* Apps */
			foreach ( explode( ',', \IPS\Request::i()->enableApps ) as $app )
			{			
				try
				{
					$app = \IPS\Application::load( $app );
					$app->enabled = TRUE;
					$app->save();
				}
				catch ( \Exception $e ) {}
			}
			
			/* Plugins */
			foreach ( explode( ',', \IPS\Request::i()->enablePlugins ) as $plugin )
			{			
				try
				{
					$plugin = \IPS\Plugin::load( $plugin );
					$plugin->enabled = TRUE;
					$plugin->save();
				}
				catch ( \Exception $e ) {}
			}
			
			/* Editor Plugins */
			if ( isset( \IPS\Request::i()->restoreEditor ) )
			{
				$editorConfigutation = \IPS\Data\Store::i()->editorConfiguationToRestore;
				
				\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $editorConfigutation['extraPlugins'] ), array( 'conf_key=?', 'ckeditor_extraPlugins' ) );
				\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $editorConfigutation['toolbars'] ), array( 'conf_key=?', 'ckeditor_toolbars' ) );
				unset( \IPS\Data\Store::i()->settings );
				
				unset( \IPS\Data\Store::i()->editorConfiguationToRestore );
			}
			
			\IPS\Data\Cache::i()->clearAll();
			return NULL;
		}
		
		/* Init */
		$restoredDefaultTheme = FALSE;
		$disabledApps = array();
		$disabledPlugins = array();
		$disabledAppNames = array();
		$disabledPluginNames = array();
		
		/* Do we need to restore the default theme? */
		if ( \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates', 'template_set_id>0' )->first() or \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_css', 'css_set_id>0' )->first() )
		{
			$newTheme = new \IPS\Theme;
			$newTheme->permissions = \IPS\Member::loggedIn()->member_group_id;
			$newTheme->save();
			$newTheme->installThemeSettings();
			$newTheme->copyResourcesFromSet();
			
			\IPS\Lang::saveCustom( 'core', "core_theme_set_title_" . $newTheme->id, "IPS Support" );
			
			\IPS\Member::loggedIn()->skin = $newTheme->id;

			if( \IPS\Member::loggedIn()->acp_skin !== NULL )
			{
				$_SESSION['old_acp_theme'] = \IPS\Member::loggedIn()->acp_skin;
				\IPS\Member::loggedIn()->acp_skin = $newTheme->id;
			}

			\IPS\Member::loggedIn()->save();
			
			$restoredDefaultTheme = TRUE;
		}
		
		/* Do we need to disable any third party apps/plugins? */
		if ( !\IPS\NO_WRITES )
		{		
			/* Loop Apps */
			foreach ( \IPS\Application::applications() as $app )
			{
				if ( $app->enabled and !in_array( $app->directory, \IPS\Application::$ipsApps ) )
				{
					$app->enabled = FALSE;
					$app->save();
					
					$disabledApps[] = $app->directory;
					$disabledAppNames[] = $app->_title;
				}
			}
			
			/* Look Plugins */
			foreach ( \IPS\Plugin::plugins() as $plugin )
			{
				if ( $plugin->enabled )
				{
					$plugin->enabled = FALSE;
					$plugin->save();
					
					$disabledPlugins[] = $plugin->id;
					$disabledPluginNames[] = $plugin->_title;
				}
			}
		}
		
		/* Do we need to revert the editor? */
		$restoredEditor = FALSE;
		if ( \IPS\Settings::i()->ckeditor_extraPlugins or \IPS\Settings::i()->ckeditor_toolbars != \IPS\Db::i()->select( 'conf_default', 'core_sys_conf_settings', array( 'conf_key=?', 'ckeditor_toolbars' ) )->first() )
		{
			\IPS\Data\Store::i()->editorConfiguationToRestore = array(
				'extraPlugins' 	=> \IPS\Settings::i()->ckeditor_extraPlugins,
				'toolbars'		=> \IPS\Settings::i()->ckeditor_toolbars,
			);
			
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_key=?', 'ckeditor_extraPlugins' ) );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => '' ), array( 'conf_key=?', 'ckeditor_toolbars' ) );
			unset( \IPS\Data\Store::i()->settings );
			
			$restoredEditor = TRUE;
		}
				
		/* Did we do anything? */
		if ( $restoredDefaultTheme or count( $disabledApps ) or count( $disabledPlugins ) or $restoredEditor )
		{
			\IPS\Data\Cache::i()->clearAll();
			return \IPS\Theme::i()->getTemplate( 'support' )->thirdParty( $id, $disabledAppNames, $disabledPluginNames, $restoredDefaultTheme, $restoredEditor, '&enableApps=' . implode( ',', $disabledApps ) . '&enablePlugins=' . implode( ',', $disabledPlugins ) . ( $restoredDefaultTheme ? '&deleteTheme=' . $newTheme->id : '' ) . ( $restoredEditor ? '&restoreEditor=1' : '' ) );
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Step 2: Self Service - Knowledgebase
	 *
	 * @param	int	$id	The current ID
	 * @return	string|NULL
	 */
	public function _knowledgebase( $id )
	{
		$kb = array();
		try
		{
			$kb = \IPS\Http\Url::ips('kb')->request()->get()->decodeJson();
		}
		catch ( \Exception $e ) { }
		
		if ( count( $kb ) )
		{
			return \IPS\Theme::i()->getTemplate( 'support' )->knowledgebase( ++$id, $kb );
		}
		return NULL;
	}
	
	/**
	 * Step 3: Contact Support
	 *
	 * @param	mixed	$data	Wizard data
	 * @return	string|array
	 */
	public function _contactSupport( $data )
	{
		$licenseData = \IPS\IPS::licenseKey();
		if ( !$licenseData or strtotime( $licenseData['expires'] ) < time() )
		{
			return \IPS\Theme::i()->getTemplate( 'global' )->message( 'get_support_no_license', 'warning' );
		}
		
		try
		{
			$supportedVerions = \IPS\Http\Url::ips('support/versions')->request()->get()->decodeJson();
			
			if ( \IPS\Application::load('core')->long_version > $supportedVerions['max'] )
			{
				return \IPS\Theme::i()->getTemplate( 'global' )->message( 'get_support_unsupported_prerelease', 'warning' );
			}
			if ( \IPS\Application::load('core')->long_version < $supportedVerions['min'] )
			{
				return \IPS\Theme::i()->getTemplate( 'global' )->message( 'get_support_unsupported_obsolete', 'warning' );
			}
		}
		catch ( \Exception $e ) {}
		
		$form = new \IPS\Helpers\Form( 'contact_support', 'contact_support_submit' );
		$form->class = 'ipsForm_vertical ipsPad';
		
		$extraOptions = array( 'admin' => 'support_request_admin' );
		if ( $this->_supportLog( TRUE ) )
		{
			$form->hiddenValues['_log'] = '1';
			$extraOptions['log'] = 'support_request_log';
		}

		$form->add( new \IPS\Helpers\Form\Text( 'support_request_title', NULL, TRUE, array( 'maxLength' => 128 ) ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'support_request_body', NULL, TRUE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'acp-support-request' ) ) );
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'support_request_extra', array( 'admin', 'log' ), FALSE, array( 'options' => $extraOptions ) ) );
		if ( $values = $form->values() )
		{			
			$admin = NULL;
			if ( in_array( 'admin', $values['support_request_extra'] ) )
			{
				$password = '';
				$length = rand( 8, 15 );
				for ( $i = 0; $i < $length; $i++ )
				{
					do {
						$key = rand( 33, 126 );
					} while ( in_array( $key, array( 34, 39, 60, 62, 92 ) ) );
					$password .= chr( $key );
				}
				
				$supportAccount = \IPS\Member::load( 'nobody@invisionpower.com', 'email' );
				if ( !$supportAccount->member_id )
				{
					$name = 'IPS Support';
					$_supportAccount = \IPS\Member::load( $name, 'name' );
					if ( $_supportAccount->member_id )
					{
						$number = 2;
						while ( $_supportAccount->member_id )
						{
							$name = "IPS Support {$number}";
							$_supportAccount = \IPS\Member::load( $name, 'name' );
							$number++;
						}
					}
					
					$supportAccount = new \IPS\Member;
					$supportAccount->name = $name;
					$supportAccount->email = 'nobody@invisionpower.com';
					$supportAccount->member_group_id = \IPS\Settings::i()->admin_group;
				}
				
				$supportAccount->members_pass_salt = $supportAccount->generateSalt();
				$supportAccount->members_pass_hash = $supportAccount->encryptedPassword( $password );
				$supportAccount->save();
				
				$admin = json_encode( array( 'name' => $supportAccount->name, 'email' => $supportAccount->email, 'password' => $password, 'dir' => \IPS\CP_DIRECTORY ) );
			}
			
			$log = NULL;
			if ( in_array( 'log', $values['support_request_extra'] ) )
			{
				$log = $this->_supportLog( FALSE );
			}
			
			$key = md5( \IPS\Http\Url::internal('app=core&module=support&controller=support') );
			unset( $_SESSION["wizard-{$key}-step"] );
			unset( $_SESSION["wizard-{$key}-data"] );

			\IPS\Output::i()->parseFileObjectUrls( $values['support_request_body'] );

			$response = \IPS\Http\Url::ips('support')->request()->login( \IPS\Settings::i()->ipb_reg_number, '' )->post( array(
				'title'		=> $values['support_request_title'],
				'message'	=> $values['support_request_body'],
				'admin'		=> $admin,
				'log'		=> $log,
				'_log'		=> intval( isset( $values['_log'] ) )
			) );
									
			switch ( $response->httpResponseCode )
			{
				case 200:
				case 201:
					return \IPS\Theme::i()->getTemplate( 'global' )->message( \IPS\Member::loggedIn()->language()->addToStack( 'get_support_done', FALSE, array( 'pluralize' => array( intval( (string) $response ) ) ) ), 'success' );
				
				case 401:
				case 403:
					return \IPS\Theme::i()->getTemplate( 'global' )->message( 'get_support_no_license', 'warning' );
				
				case 429:
					return \IPS\Theme::i()->getTemplate( 'global' )->message( 'get_support_duplicate', 'error' );
				
				case 502:
				default:
					return \IPS\Theme::i()->getTemplate( 'global' )->message( 'get_support_error', 'error' );
			}
		}
		return (string) $form;
	}
	
	/**
	 * Get the log that will be sent
	 *
	 * @param	bool	$checkOnly		If TRUE, will just check if there is one to send
	 * @return	string
	 */
	protected function _supportLog( $checkOnly )
	{
		$logsToSend = array();
		$oneDayAgo = \IPS\DateTime::create()->sub( new \DateInterval('P1D') );
		
		/* Get logs from database */
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_log', array( 'time>?', $oneDayAgo->getTimestamp() ), 'time DESC' ), 'IPS\Log' ) as $log )
		{
			$logsToSend[ $log->time . '-' . md5( uniqid() ) ] = array( 'title' => $log->category ? "DB: {$log->category}" : '??', 'content' => date( 'r', $log->time ) . ( $log->exception_class ? ( $log->exception_class . '::' . $log->exception_code . "\n" ) : '' ) . "\n" . $log->message . "\n" . $log->backtrace );
		}
		
		/* And from file system */
		$dir = \IPS\Log::fallbackDir();
		if ( !\IPS\NO_WRITES and is_dir( $dir ) )
		{
			foreach ( new \DirectoryIterator( $dir ) as $file )
			{
				if ( !$file->isDir() and !$file->isDot() and mb_substr( $file, -4 ) === '.php' and $file->getMTime() > $oneDayAgo->getTimestamp() )
				{
					$logsToSend[ $file->getMTime() . '-' . md5( uniqid() ) ] = array( 'title' => (string) $file, 'content' => file_get_contents( $file->getPathname() ) );
				}
			}
		}
		
		/* If we're just checking, return */
		if ( $checkOnly )
		{
			return !empty( $logsToSend );
		}
		
		/* Sort */
		krsort( $logsToSend );
		
		/* Return */
		$output = '';
		foreach ( $logsToSend as $data )
		{
			$output .= "/************************/\n/* " . $data['title'] . " \n/************************/\n\n";
			$output .= $data['content'];
			$output .= "\n\n\n";
		}
		return $output;
	}
	
	/**
	 * View the log that will be sent
	 *
	 * @return	void
	 */
	public function supportLog()
	{
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('support')->logToSend( $this->_supportLog( FALSE ) );
	}
}