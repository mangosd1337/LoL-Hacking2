<?php
/**
 * @brief		Google Profile Sync
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\ProfileSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Google Profile Sync
 */
class _Google extends ProfileSyncAbstract
{
	/** 
	 * @brief	Login handler key
	 */
	public static $loginKey = 'Google';
	
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'google-plus';
	
	/**
	 * @brief	Authorization token
	 */
	protected $authToken = NULL;
	
	/**
	 * @brief	User data
	 */
	protected $user = NULL;
	
	/**
	 * Get user data
	 *
	 * @return	array
	 */
	protected function user()
	{
		if ( $this->user === NULL and $this->member->google_token )
		{
			try
			{
				$loginHandler = \IPS\Login\LoginAbstract::load('google');

				$response = \IPS\Http\Url::external( 'https://accounts.google.com/o/oauth2/token' )->request()->post( array(
					'client_id'		=> $loginHandler->settings['client_id'],
					'client_secret'	=> $loginHandler->settings['client_secret'],
					'refresh_token'	=> $this->member->google_token,
					'grant_type'	=> 'refresh_token'
				) )->decodeJson();

				if ( isset( $response['access_token'] ) )
				{
					$this->authToken = $response['access_token'];
					$this->user = $this->get( 'people/' . $this->member->google_id );
				}
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				$this->member->google_token = NULL;
				$this->member->save();

				\IPS\Log::log( $e, 'google' );
			}
		}

		return $this->user;
	}
	
	
	/**
	 * Is connected?
	 *
	 * @return	bool
	 */
	public function connected()
	{
		return (bool) ( $this->member->google_id and $this->member->google_token );
	}
	
	/**
	 * Get photo
	 *
	 * @return	\IPS\Http\Url|\IPS\File|NULL
	 */
	public function photo()
	{
		try
		{
			$user = $this->user();
			if ( isset( $user['image'] ) )
			{
				return \IPS\Http\Url::external( preg_replace( '/\?sz=\d+$/', '', $user['image']['url'] ) );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'google' );
		}

		return NULL;
	}
	
	/**
	 * Get cover photo
	 *
	 * @return	\IPS\Http\Url|NULL
	 */
	public function cover()
	{
		try
		{
			$user = $this->user();
			if ( isset( $user['cover'] ) )
			{
				return \IPS\Http\Url::external( $user['cover']['coverPhoto']['url'] )->import( 'core_Profile' );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'google' );
		}

		return NULL;
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function name()
	{
		$user = $this->user();

		if ( isset( $user['displayName'] ) )
		{
			return $user['displayName'];
		}
	}
	
	/**
	 * Get status
	 *
	 * @return	\IPS\core\Statuses\Status|null
	 */
	public function status()
	{
		try
		{
			$this->user();
			$statuses = $this->get( "people/me/activities/public" );
			if ( isset( $statuses['items'] ) )
			{
				foreach ( $statuses['items'] as $status )
				{
					if ( $status['object']['content'] )
					{
						$statusObj = \IPS\core\Statuses\Status::createItem( $this->member, $this->member->ip_address, new \IPS\DateTime( $status['published'] ) );
						$statusObj->content = $this->_parseStatusText( nl2br( $status['object']['content'], FALSE ) );
						return $statusObj;
					}
				}
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'google' );
		}

		return NULL;
	}
	
	/**
	 * Disassociate
	 *
	 * @return	void
	 */
	protected function _disassociate()
	{
		$this->member->google_id = 0;
		$this->member->google_token = NULL;
		$this->member->save();
	}
	
	/**
	 * Get API data
	 *
	 * @param	string	$uri	The API URI
	 * @return	array
	 * @throws	\IPS\Http\Request\Exception
	 */
	protected function get( $uri )
	{
		return \IPS\Http\Url::external( 'https://www.googleapis.com/plus/v1/' . $uri )->request()->setHeaders( array( 'Authorization' => "Bearer {$this->authToken}" ) )->get()->decodeJson();
	}
}