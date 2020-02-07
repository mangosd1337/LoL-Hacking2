<?php
/**
 * @brief		Front Session Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Session;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Session Handler
 */
class _Front extends \IPS\Session
{
	const LOGIN_TYPE_MEMBER = 0;
	const LOGIN_TYPE_ANONYMOUS = 1;
	const LOGIN_TYPE_GUEST = 2;
	const LOGIN_TYPE_SPIDER = 3;
	
	/**
	 * Guess if the user is logged in
	 *
	 * This is a lightweight check that does not rely on other classes. It is only intended
	 * to be used by the guest caching mechanism so that it can check if the user is logged
	 * in before other classes are initiated.
	 *
	 * This method MUST NOT be used for other purposes as it IS NOT COMPLETELY ACCURATE.
	 *
	 * @return	bool
	 */
	public static function loggedIn()
	{
		return isset( \IPS\Request::i()->cookie['member_id'] ) and \IPS\Request::i()->cookie['member_id'];
	}
	
	/**
	 * @brief	Session Data
	 */
	protected $data	= array();
	
	/**
	 * @brief	Needs saving?
	 */
	protected $save	= TRUE;
	
	/**
	 * Open Session
	 *
	 * @param	string	$savePath	Save path
	 * @param	string	$sessionName Session Name
	 * @return	void
	 */
	public function open( $savePath, $sessionName )
	{
		return TRUE;
	}
	
	/**
	 * Read Session
	 *
	 * @param	string	$sessionId	Session ID
	 * @return	string
	 */
	public function read( $sessionId )
	{
		$session = NULL;
		
		/* Get user agent info */
		$this->userAgent	= \IPS\Http\Useragent::parse();
		
		/* Get from the database */
		try
		{
			/* If it looks like we're logged in, join the member row to save a query later */
			if ( static::loggedIn() )
			{
				$session = \IPS\Db::i()->select( '*', 'core_sessions', array( 'id=?', $sessionId ), NULL, NULL, NULL, NULL, \IPS\Db::SELECT_MULTIDIMENSIONAL_JOINS )->join( 'core_members', 'core_members.member_id=core_sessions.member_id' )->first();
				if ( $session['core_members']['member_id'] )
				{
					\IPS\Member::constructFromData( $session['core_members'], FALSE );
				}
				$session = $session['core_sessions'];
			}
			/* If we're not logged in, just look at the session */
			else
			{
				/* Spiders match by IP and useragent */
				if ( $this->userAgent->spider )
				{
					$session = \IPS\Db::i()->select( '*', 'core_sessions', array( 'id=? OR ( ip_address=? AND browser=? )', $sessionId, \IPS\Request::i()->ipAddress(), $_SERVER['HTTP_USER_AGENT'] ) )->first();
					$sessionId = $session['id'];
				}
				/* Normal users don't */
				else
				{
					$session = \IPS\Db::i()->select( '*', 'core_sessions', array( 'id=?', $sessionId ) )->first();
				}
			}
		}
		catch ( \UnderflowException $e ) { }
		
		/* Only use sessions with matching IP address */
		if( \IPS\Settings::i()->match_ipaddress and $session['ip_address'] != \IPS\Request::i()->ipAddress() )
		{
			$session = NULL;
		}

		/* Store this so plugins can access */
		$this->sessionData	= $session;
		
		/* Got one? */
		if ( $session )
		{
			/* If this is a guest and the "running time" on this is less than 30 seconds ago, or if a member and less than 15 seconds ago, we don't need a database write */
			if ( ( !$session['member_id'] and $session['running_time'] > ( time() - 30 ) ) or ( $session['member_id'] and $session['running_time'] > ( time() - 15 ) ) )
			{
				$this->save = FALSE;
			}
						
			/* Set member */
			try
			{
				$this->member = \IPS\Member::load( (int) $session['member_id'] );
			}
			catch ( \OutOfRangeException $e )
			{
				$this->member = new \IPS\Member;
			}
		}
		/* We might be able to get the member from a cookie */
		else
		{
			$this->member = new \IPS\Member;
		}

		/* If we don't have a member, check the cookies */
		if ( !$this->member->member_id and isset( \IPS\Request::i()->cookie['member_id'] ) and isset( \IPS\Request::i()->cookie['pass_hash'] ) )
		{
			try
			{
				$member = \IPS\Member::load( (int) \IPS\Request::i()->cookie['member_id'] );
				if ( $member->member_login_key AND \IPS\Request::i()->cookie['pass_hash'] AND \IPS\Login::compareHashes( (string) $member->member_login_key, (string) \IPS\Request::i()->cookie['pass_hash'] ) )
				{
					$this->member = $member;
					
					/* Renew those cookies */
					$expire = new \IPS\DateTime;
					$expire->add( new \DateInterval( 'P7D' ) );
					\IPS\Request::i()->setCookie( 'member_id', $member->member_id, $expire );
					\IPS\Request::i()->setCookie( 'pass_hash', $member->member_login_key, $expire );
					
					if( isset( \IPS\Request::i()->cookie['anon_login'] ) and \IPS\Request::i()->cookie['anon_login'] )
					{
						\IPS\Request::i()->setCookie( 'anon_login', 1, $expire );
					}
				}
				else
				{
					$this->member = new \IPS\Member;
					\IPS\Request::i()->setCookie( 'member_id', NULL );
					\IPS\Request::i()->setCookie( 'pass_hash', NULL );
				}
			}
			catch ( \OutOfRangeException $e )
			{
				$this->member = new \IPS\Member;
				\IPS\Request::i()->setCookie( 'member_id', NULL );
				\IPS\Request::i()->setCookie( 'pass_hash', NULL );
			}
		}
								
		/* Work out the type */
		if ( $this->member->member_id )
		{
			if ( ( $session and $session['login_type'] === static::LOGIN_TYPE_ANONYMOUS ) or isset( \IPS\Request::i()->cookie['anon_login'] ) and \IPS\Request::i()->cookie['anon_login'] )
			{
				$type = static::LOGIN_TYPE_ANONYMOUS;
			}
			else
			{
				$type = static::LOGIN_TYPE_MEMBER;
			}
		}
		else
		{
			$type = $this->userAgent->spider ? static::LOGIN_TYPE_SPIDER : static::LOGIN_TYPE_GUEST;
		}
		
		/* Set data */
		$this->data = array(
			'id'						=> $sessionId,
			'member_name'				=> $this->member->member_id ? $this->member->name : '',
			'seo_name'					=> $this->member->member_id ? ( $this->member->members_seo_name ?: '' ) : '',
			'member_id'					=> $this->member->member_id ?: 0,
			'ip_address'				=> \IPS\Request::i()->ipAddress(),
			'browser'					=> isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			/* We do not want ajax calls to update running time as this affects appearance of being online. If no session exists, we do not want ajax polling to trigger an online list hit so we set running time for time - 31 minutes as
			   online lists look for running times less than 30 minutes. */
			'running_time'				=> ( \IPS\Request::i()->isAjax() ) ? ( $session ? $session['running_time'] : time() - 1860 ) : time(), 
			'login_type'				=> $type,
			'member_group'				=> ( $this->member->member_id ) ? $this->member->member_group_id : \IPS\Settings::i()->guest_group,
			'current_appcomponent'		=> ( \IPS\Request::i()->isAjax() ) ? ( $session ? $session['current_appcomponent'] : '' ) : '',
			'current_module'			=> ( \IPS\Request::i()->isAjax() ) ? ( $session ? $session['current_module'] : '' ) : '',
			'current_controller'		=> ( \IPS\Request::i()->isAjax() ) ? ( $session ? $session['current_controller'] : NULL ) : NULL,
			'current_id'				=> intval( \IPS\Request::i()->id ),
			'uagent_key'				=> $this->userAgent->useragentKey,
			'uagent_version'			=> $this->userAgent->useragentVersion ?: '',
			'uagent_type'				=> $this->userAgent->spider ? 'search' : 'browser',
			'search_thread_id'			=> $session ? intval( $session['search_thread_id'] ) : 0,
			'search_thread_time'		=> $session ? $session['search_thread_time'] : 0,
			'data'						=> $session ? $session['data'] : NULL,
			'location_url'				=> $session ? $session['location_url'] : NULL,
			'location_lang'				=> $session ? $session['location_lang'] : NULL,
			'location_data'				=> $session ? $session['location_data'] : NULL,
			'location_permissions'		=> $session ? $session['location_permissions'] : NULL,
			'theme_id'					=> $session ? $session['theme_id'] : 0,
		);

		/* Is this a spider? */
		if( $this->userAgent->spider )
		{
			/* Is this Facebook? Do we need to treat them as a user of a different group? */
			if( $this->userAgent->useragentKey == 'facebook' )
			{
				if( \IPS\core\ShareLinks\Service::load( 'facebook', 'share_key' )->enabled )
				{
					if( $this->userAgent->facebookIpVerified( \IPS\Request::i()->ipAddress() ) AND \IPS\Settings::i()->fbc_bot_group != \IPS\Settings::i()->guest_group )
					{
						$this->member->member_group_id	= \IPS\Settings::i()->fbc_bot_group;
					}
				}
			}
		}

		return $this->data['data'];
	}

	/**
	 * Set Session Member
	 *
	 * @param	\IPS\Member	$member	Member object
	 * @return	void
	 */
	public function setMember( $member )
	{
		parent::setMember( $member );

		/* Make sure login key has been set */
		$member->checkLoginKey();

		/* Set the cookie */
		$expire = new \IPS\DateTime;
		$expire->add( new \DateInterval( 'P7D' ) );
		\IPS\Request::i()->setCookie( 'member_id', $member->member_id, $expire );

		/* Make sure session handler saves during write() */
		$this->save = TRUE;
	}

	/**
	 * Write Session
	 *
	 * @param	string	$sessionId	Session ID
	 * @param	string	$data		Session Data
	 * @return	bool
	 */
	public function write( $sessionId, $data )
	{
		if ( $data !== $this->data['data'] or $this->data['member_id'] != $this->member->member_id )
		{
			$this->save = TRUE;
		}
		
		/* Don't update if instant notifications are checking to reduce overhead on the session table */
		if ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->app ) and \IPS\Request::i()->app === 'core' and isset( \IPS\Request::i()->controller ) and \IPS\Request::i()->controller === 'ajax' and isset( \IPS\Request::i()->do ) and \IPS\Request::i()->do === 'instantNotifications' )
		{
			$this->save = FALSE;
		}

		$this->data['member_name']	= $this->member->member_id ? $this->member->name : '';
		$this->data['member_id']	= $this->member->member_id ?: NULL;
		$this->data['data']			= $data;
		$this->setLocationData();

		$key = "session_{$sessionId}";
		
		if ( $this->save === TRUE and ( !empty( \IPS\Request::i()->cookie ) or $this->userAgent->spider or $this->member->member_id ) ) // If a guest and cookies are disabled we do not write to database to prevent duplicate sessions unless it's a search engine, which we deal with separately
		{
			\IPS\Db::i()->replace( 'core_sessions', $this->data, TRUE );
		}
		
		return TRUE;
	}

	/**
	 * Set the search start
	 *
	 * @return	void
	 */
	public function startSearch()
	{
		$this->data['search_thread_id']		= \IPS\Db::i()->thread_id;
		$this->data['search_thread_time']	= time();
	}

	/**
	 * Set the search end
	 *
	 * @return	void
	 */
	public function endSearch()
	{
		$this->data['search_thread_id']		= 0;
		$this->data['search_thread_time']	= 0;
	}
	
	/**
	 * Set a theme ID
	 *
	 * @param	int		$themeId		The theme id, of course
	 * @return	void
	 */
	public function setTheme( $themeId )
	{
		if( !\IPS\Dispatcher::hasInstance() OR \IPS\Request::i()->isAjax() )
		{
			return;
		}
		
		$this->data['theme_id'] = $themeId;
		
		$this->save = TRUE;
	}
	
	/**
	 * Get the theme ID
	 *
	 * @return	int
	 */
	public function getTheme()
	{
		if ( isset( $this->data['theme_id'] ) and $this->data['theme_id'] )
		{
			return $this->data['theme_id'];
		}
		
		return NULL;
	}
	
	/**
	 * Set basic location data
	 *
	 * @return	void
	 */
	public function setLocationData()
	{
		if( !\IPS\Dispatcher::hasInstance() OR \IPS\Request::i()->isAjax() )
		{
			return;
		}

		$this->data['current_appcomponent']	= \IPS\Dispatcher::i()->application ? \IPS\Dispatcher::i()->application->directory : '';
		$this->data['current_module']		= \IPS\Dispatcher::i()->module ? \IPS\Dispatcher::i()->module->key : '';
		$this->data['current_controller']	= \IPS\Dispatcher::i()->controller;
		$this->data['current_id']			= intval( \IPS\Request::i()->id );
	}

	/**
	 * Set the session location
	 *
	 * @param	\IPS\Http\Url	$url		URL
	 * @param	array			$groupIds	Permission data
	 * @param	string			$lang		Language string
	 * @param	array			$data		Language data. Keys are the words, value is a boolean indicating if it's a language key (TRUE) or should be displayed as-is (FALSE)
	 * @return	void
	 */
	public function setLocation( \IPS\Http\Url $url, $groupIds, $lang, $data=array() )
	{
		if( !\IPS\Dispatcher::hasInstance() OR \IPS\Request::i()->isAjax() )
		{
			return;
		}

		$this->data['location_url'] = (string) $url;
		$this->data['location_lang'] = $lang;
		$this->data['location_data'] = json_encode( $data );
        $this->data['current_id'] = intval( \IPS\Request::i()->id );
		
		if ( !$this->data['current_appcomponent'] )
		{
			$this->setLocationData();
		}
		
		/* Some places use 0 to mean no permission at all but this is lost in the code below */
		if ( $groupIds === 0 )
		{
			$groupIds = (string) $groupIds;
		}		
	
		$groupIds = is_string( $groupIds ) ? explode( ',', $groupIds ) : ( $groupIds ?: NULL );
				
		$app = \IPS\Application::load( $this->data['current_appcomponent'] );
		if ( !$app->enabled )
		{			
			$groupIds = $groupIds ? array_intersect( $groupIds, explode( ',', $app->disabled_groups ) ) : explode( ',', $app->disabled_groups );
		}
		
		$modulePermissions = \IPS\Application\Module::get( $this->data['current_appcomponent'], $this->data['current_module'], 'front' )->permissions();
		if ( $modulePermissions['perm_view'] !== '*' )
		{
			$groupIds = $groupIds ? array_intersect( $groupIds, explode( ',', $modulePermissions['perm_view'] ) ) : explode( ',', $modulePermissions['perm_view'] );
		}

		$this->data['location_permissions'] = ( $groupIds !== NULL ) ? ( is_string( $groupIds ) ? $groupIds : implode( ',', $groupIds ) ) : NULL;
	}
	
	/**
	 * Get the session location
	 * 
	 * @param	array			$row		Row from sessions
	 * @return	string|null
	 */
	public static function getLocation( $row )
	{
		$location = NULL;

		if( !$row['location_lang'] )
		{
			return $location;
		}

		if ( $row['location_permissions'] === NULL or $row['location_permissions'] === '*' or \IPS\Member::loggedIn()->inGroup( explode( ',', $row['location_permissions'] ), TRUE ) )
		{
			$sprintf = array();
			$data = json_decode( $row['location_data'], TRUE );

			if ( !empty( $data ) )
			{				
				foreach ( $data as $key => $parse )
				{
					$value		= htmlspecialchars( $parse ? \IPS\Member::loggedIn()->language()->get( $key ) : $key, \IPS\HTMLENTITIES, 'UTF-8', FALSE );
					$sprintf[]	= $value;
				}
			}

			$location = \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $row['location_lang'], \IPS\HTMLENTITIES, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $sprintf ) );
			
			$location	= "<a href='" . htmlspecialchars( $row['location_url'], \IPS\HTMLENTITIES, 'UTF-8', FALSE ) . "'>" . $location . "</a>";
		}
		
		return $location;
	}
	
	/**
	 * Set the session as anonymous
	 *
	 * @return	void
	 */
	public function setAnon()
	{
		$this->data['login_type'] = static::LOGIN_TYPE_ANONYMOUS;
	}
	
	/**
	 * Set the session as anonymous
	 *
	 * @return	void
	 */
	public function getAnon()
	{
		return (bool) $this->data['login_type'] == static::LOGIN_TYPE_ANONYMOUS;
	}
	
	/**
	 * Close Session
	 *
	 * @return	bool
	 */
	public function close()
	{
		return TRUE;
	}
	
	/**
	 * Destroy Session
	 *
	 * @param	string	$sessionId	Session ID
	 * @return	bool
	 */
	public function destroy( $sessionId )
	{		
		$key = "session_{$sessionId}";
		
		if ( isset( $_SESSION['wizardKey'] ) )
		{
			$dataKey = $_SESSION['wizardKey'];
			unset( \IPS\Data\Store::i()->$dataKey );
		}
		
		\IPS\Db::i()->delete( 'core_sessions', array( 'id=?', $sessionId ) );
		return TRUE;
	}
	
	/**
	 * Garbage Collection
	 *
	 * @param	int		$lifetime	Unix timestamp of the oldest session to keep
	 * @return	bool
	 */
	public function gc( $lifetime )
	{
		//static::clearSessions( $lifetime );
		return TRUE;
	}

	/**
	 * Clear sessions - abstracted so it can be called externally without initiating a session
	 *
	 * @param	int		$timeout	Sessions older than the number of seconds provided will be deleted
	 * @return void
	 */
	public static function clearSessions( $timeout )
	{
		//\IPS\Db::i()->delete( 'core_sessions', array( 'running_time<?', ( time() - $timeout ) ) );
	}
}
