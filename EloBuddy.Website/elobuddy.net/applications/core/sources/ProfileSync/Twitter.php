<?php
/**
 * @brief            Twitter Profile Sync
 * @author           <a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) 2001 - 2016 Invision Power Services, Inc.
 * @license          http://www.invisionpower.com/legal/standards/
 * @package          IPS Community Suite
 * @since            13 Jun 2013
 * @version          SVN_VERSION_NUMBER
 */

namespace IPS\core\ProfileSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Twitter Profile Sync
 */
class _Twitter extends ProfileSyncAbstract
{
	/**
	 * @brief    Login handler key
	 */
	public static $loginKey = 'Twitter';

	/**
	 * @brief    Icon
	 */
	public static $icon = 'twitter';

	/**
	 * @brief    User data
	 */
	protected $user = NULL;

	/**
	 * Get user data
	 *
	 * @return    array
	 */
	protected function user()
	{
		if ( ( $this->user === NULL ) and $this->member->twitter_token )
		{
			try
			{
				$loginHandler = \IPS\Login\LoginAbstract::load( 'twitter' );

				$this->user = $loginHandler->sendRequest(
					'get', "https://api.twitter.com/1.1/account/verify_credentials.json", array(),
					$this->member->twitter_token, $this->member->twitter_secret
				)->decodeJson();

				if ( isset( $this->user['errors'] ) )
				{
					throw new \Exception;
				}
			}
			catch ( \Exception $e )
			{
				$this->member->twitter_token  = '';
				$this->member->twitter_secret = '';
				$this->member->save();

				\IPS\Log::log( isset( $this->user['errors'] ) ? var_export( $this->user['errors'] ) : $e, 'twitter' );
			}
		}

		return $this->user;
	}

	/**
	 * Is connected?
	 *
	 * @return    bool
	 */
	public function connected()
	{
		return ( $this->member->twitter_id and $this->member->twitter_token and $this->member->twitter_secret );
	}

	/**
	 * Get photo
	 *
	 * @return    \IPS\Http\Url
	 */
	public function photo()
	{
		return $this->resourceUrl( $this->user(), 'profile_image_url' );
	}

	/**
	 * Get cover photo
	 *
	 * @return    \IPS\File|NULL
	 */
	public function cover()
	{
		if ( $coverUrl = $this->resourceUrl( $this->user(), 'profile_background_image_url' ) )
		{
			try
			{
				return $coverUrl->import( 'core_Profile' );
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				\IPS\Log::log( $e, 'twitter' );
			}
		}

		return NULL;
	}

	/**
	 * Get name
	 *
	 * @return    string
	 */
	public function name()
	{
		$user = $this->user();
		if ( isset( $user['screen_name'] ) )
		{
			return $user['screen_name'];
		}
	}

	/**
	 * Get status
	 *
	 * @return    \IPS\core\Statuses\Status|null
	 */
	public function status()
	{
		$user = $this->user();

		if ( !empty( $user['status'] ) )
		{
			$status = \IPS\core\Statuses\Status::createItem(
				$this->member, $this->member->ip_address, new \IPS\DateTime( $user['status']['created_at'] )
			);

			$status->content = $this->_parseStatusText( $user['status']['text'] );

			return $status;
		}

		return NULL;
	}

	/**
	 * Disassociate
	 *
	 * @return    void
	 */
	protected function _disassociate()
	{
		$this->member->twitter_id     = 0;
		$this->member->twitter_token  = '';
		$this->member->twitter_secret = '';
		$this->member->save();
	}

	/**
	 * Get an HTTPS or HTTP resource URL (HTTPS if available and we are using it locally, otherwise HTTP)
	 *
	 * @param   array       $user
	 * @param   string      $fieldName
	 * @return  \IPS\Http\Url|NULL
	 */
	protected function resourceUrl( $user, $fieldName )
	{
		$scheme = \IPS\Http\Url::internal( '' )->data['scheme'];

		if ( ( $scheme === 'https' ) or \IPS\Settings::i()->logins_over_https )
		{
			if ( !empty( $user[ $fieldName . '_https' ] ) )
			{
				return \IPS\Http\Url::external( $user[ $fieldName . '_https' ] );
			}
		}

		if ( !empty( $user[ $fieldName ] ) )
		{
			return \IPS\Http\Url::external( $user[ $fieldName ] );
		}

		return NULL;
	}
}