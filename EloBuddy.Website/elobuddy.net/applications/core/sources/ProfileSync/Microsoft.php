<?php
/**
 * @brief		Microsoft Profile Sync
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
 * Microsoft Profile Sync
 */
class _Microsoft extends ProfileSyncAbstract
{
	/** 
	 * @brief	Login handler key
	 */
	public static $loginKey = 'Live';
	
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'windows';
	
	/**
	 * @brief	Authorization token
	 */
	protected $authToken = NULL;
		
	/**
	 * Get user data
	 *
	 * @return	string
	 */
	protected function authToken()
	{
		if ( $this->authToken === NULL and $this->member->live_token )
		{
			try
			{
				$loginHandler = \IPS\Login\LoginAbstract::load('live');
				
				$response = \IPS\Http\Url::external( 'https://login.live.com/oauth20_token.srf' )->request()->post( array(
					'client_id'		=> $loginHandler->settings['client_id'],
					'client_secret'	=> $loginHandler->settings['client_secret'],
					'refresh_token'	=> $this->member->live_token,
					'grant_type'	=> 'refresh_token'
				) )->decodeJson();
				
				if ( isset( $response['access_token'] ) and isset( $response['refresh_token'] ) )
				{
					$this->authToken = $response['access_token'];
					$this->member->live_token = $response['refresh_token'];
				}
				else
				{
					$this->member->live_token = NULL;
				}
				
				$this->member->save();
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				$this->member->live_token = NULL;
				$this->member->save();
			}
		}
		
		return $this->authToken;
	}

	/**
	 * Is connected?
	 *
	 * @return	bool
	 */
	public function connected()
	{
		return (bool) ( $this->member->live_id and $this->member->live_token );
	}
	
	/**
	 * Get photo
	 *
	 * @return	\IPS\Http\Url|null
	 */
	public function photo()
	{
		return \IPS\Http\Url::external( "https://apis.live.net/v5.0/{$this->member->live_id}/picture?type=large" );
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function name()
	{
		try
		{
			$user = \IPS\Http\Url::external( "https://apis.live.net/v5.0/{$this->member->live_id}?access_token={$this->authToken()}" )->request()->get()->decodeJson();
			if ( isset( $user['name'] ) )
			{
				return $user['name'];
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Disassociate
	 *
	 * @return	void
	 */
	protected function _disassociate()
	{
		$this->member->live_id = 0;
		$this->member->live_token = NULL;
		$this->member->save();
	}
}