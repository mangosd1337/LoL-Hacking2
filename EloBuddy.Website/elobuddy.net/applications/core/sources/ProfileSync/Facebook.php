<?php
/**
 * @brief		Facebook Profile Sync
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
 * Facebook Profile Sync
 */
class _Facebook extends ProfileSyncAbstract
{
	/** 
	 * @brief	Login handler key
	 */
	public static $loginKey = 'Facebook';
	
	/** 
	 * @brief	Icon
	 */
	public static $icon = 'facebook-square';
			
	/**
	 * Is connected?
	 *
	 * @return	bool
	 */
	public function connected()
	{
		return ( $this->member->fb_uid and $this->member->fb_token );
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
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}/picture?type=large&redirect=false" )->request()->get()->decodeJson();
			if ( isset( $response['data']['is_silhouette'] ) AND $response['data']['is_silhouette'] === false )
			{
				$file = \IPS\Http\Url::external( $response['data']['url'] )->import( 'core_Profile' );
				$photo = \IPS\Image::create( $file->contents() );
				
				if ( ( $photo->width > $photo->height ) OR ( $photo->height > $photo->width ) )
				{
					if ( $photo->width > $photo->height )
					{
						$difference = $photo->width - $photo->height;
						$photo->cropToPoints( ceil( $difference / 2 ), 0, ceil( $photo->width - ( $difference / 2 ) ), $photo->height );
						$file->replace( (string) $photo );
					}
					else if ( $photo->height > $photo->width )
					{
						$difference = $photo->height - $photo->width;
						$photo->cropToPoints( 0, ceil( $difference / 2 ), $photo->width, ceil( $photo->height - ( $difference / 2 ) ) );
						$file->replace( (string) $photo );
					}
				}
				
				return $file;
			}
			else
			{
				return NULL;
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'facebook' );

			return NULL;
		}
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
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}?access_token={$this->member->fb_token}&fields=cover" )->request()->get()->decodeJson();
			if ( isset( $response['cover'] ) )
			{
				return \IPS\Http\Url::external( $response['cover']['source'] )->import( 'core_Profile' );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'facebook' );

			return NULL;
		}
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
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}?access_token={$this->member->fb_token}" )->request()->get()->decodeJson();
			if ( isset( $response['name'] ) )
			{
				return $response['name'];
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'facebook' );

			return NULL;
		}
	}
	
	/**
	 * Member can import statuses?
	 *
	 * @return	array
	 */
	public function canImportStatus( \IPS\Member $member )
	{
		if ( parent::canImportStatus( $member ) )
		{
			$loginHandlers = \IPS\Login::handlers( TRUE );
			if ( isset( $loginHandlers['Facebook'] ) AND isset( $loginHandlers['Facebook']->settings['allow_status_import'] ) )
			{
				return $loginHandlers['Facebook']->settings['allow_status_import'];
			}
		}
		return FALSE;
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
			$response = \IPS\Http\Url::external( "https://graph.facebook.com/{$this->member->fb_uid}/feed?access_token={$this->member->fb_token}" )->request()->get()->decodeJson();			
			if ( !empty( $response['data'] ) )
			{				
				$statusData = array_shift( $response['data'] );

				if( isset( $statusData['message'] ) )
				{
					$status = \IPS\core\Statuses\Status::createItem( $this->member, $this->member->ip_address, new \IPS\DateTime( $statusData['updated_time'] ) );
					$status->content = $this->_parseStatusText( nl2br( $statusData['message'], FALSE ) );
					return $status;
				}
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Log::log( $e, 'facebook' );
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
		$this->member->fb_uid = 0;
		$this->member->fb_token = NULL;
		$this->member->save();
	}
}