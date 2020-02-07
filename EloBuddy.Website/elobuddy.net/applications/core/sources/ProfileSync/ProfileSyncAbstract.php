<?php
/**
 * @brief		Profile Sync Abstract Class
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
 * Profile Sync Interface
 */
abstract class _ProfileSyncAbstract
{	
	/**
	 * @brief	Login handlers
	 */
	protected static $loginHandlers = NULL;
	
	/**
	 * Get services
	 *
	 * @return	array
	 */
	public static function services()
	{
		$services = array();
		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/core/sources/ProfileSync' ) as $file )
		{
			if ( !$file->isDot() and mb_substr( $file, -4 ) === '.php' and mb_substr( $file, 0, 2 ) != '._' )
			{
				if ( class_exists( 'IPS\core\ProfileSync\\' . mb_substr( $file, 0, -4 ) ) )
				{
					$class = 'IPS\core\ProfileSync\\' . mb_substr( $file, 0, -4 );

					if ( $class::enabled() )
					{
						$services[ mb_substr( $file, 0, -4 ) ] = $class;
					}
				}
			}
		}
		return $services;
	}
	
	/**
	 * Enabled?
	 *
	 * @return	bool
	 */
	public static function enabled()
	{
		if ( isset( static::$loginKey ) )
		{
			if ( static::$loginHandlers === NULL )
			{
				static::$loginHandlers =  iterator_to_array( \IPS\Db::i()->select( '*', 'core_login_handlers' )->setKeyField( 'login_key' ) );
			}
			
			if ( isset( static::$loginHandlers[ static::$loginKey ] ) )
			{
				return (bool) static::$loginHandlers[ static::$loginKey ]['login_enabled'];
			}
		}
		
		return FALSE;
	}
	
	/**
	 * @brief	Member
	 */
	protected $member;
	
	/**
	 * Constructor
	 *
	 * \IPS\Member	$member	The member
	 */
	public function __construct( \IPS\Member $member )
	{
		$this->member = $member;
	}
	
	/**
	 * Is connected?
	 *
	 * @return	bool
	 */
	abstract public function connected();
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	abstract public function name();
	
	/**
	 * Member can import statuses?
	 *
	 * @return	array
	 */
	public function canImportStatus( \IPS\Member $member )
	{
		return \IPS\Settings::i()->profile_comments and method_exists( $this, 'status' ) and $member->canAccessModule( \IPS\Application\Module::get( 'core', 'members', 'front' ) ) and $member->pp_setting_count_comments and !$member->members_bitoptions['bw_no_status_update'] and !$member->group['gbw_no_status_import'];
	}
		
	/**
	 * Get settings
	 *
	 * @return	array
	 */
	public function settings()
	{
		$settings = $this->member->profilesync ? json_decode( $this->member->profilesync, TRUE ) : array();
		return ( isset( $settings[ static::$loginKey ] ) ? $settings[ static::$loginKey ] : array(
			'photo'			=> FALSE,
			'cover'			=> FALSE,
			'status'		=> ''
		) );
	}
	
	/**
	 * Get settings as string
	 *
	 * @return	array
	 */
	public function settingsDesc()
	{
		$settings = $this->settings();
		$return = array();
		if ( $settings['photo'] or $settings['cover'] )
		{
			$_return = array();
			if ( $settings['photo'] )
			{
				$_return[] = \IPS\Member::loggedIn()->language()->addToStack('sync_settings_photo');
			}
			if ( $settings['cover'] )
			{
				$_return[] = \IPS\Member::loggedIn()->language()->addToStack('sync_settings_cover');
			}
			
			if ( count( $_return ) > 1 )
			{
				$return[] = \IPS\Member::loggedIn()->language()->formatList( $_return, \IPS\Member::loggedIn()->language()->get('sync_settings_syncing') );
			}
			else
			{
				$return[] = $_return[0];
			}
		}
		if ( $settings['status'] and \IPS\Settings::i()->profile_comments )
		{
			$langKey = 'sync_settings_status_' . $settings['status'];
			$return[] = \IPS\Member::loggedIn()->language()->addToStack( $langKey );
		}
		
		return empty( $return ) ? \IPS\Member::loggedIn()->language()->addToStack('profilesync_not_syncing') : \IPS\Member::loggedIn()->language()->formatList( $return );
	}
	
	/**
	 * Save Settings
	 *
	 * @param	array	$values	Values from form
	 * @return	void
	 */
	public function save( $values )
	{
		/* Get existing settings */
		$settings = $this->member->profilesync ? json_decode( $this->member->profilesync, TRUE ) : array(
			static::$loginKey => array(
				'photo'			=> FALSE,
				'cover'			=> FALSE,
				'status'		=> ''
			)
		);
		
		/* Set the new values for this service */
		$settings[ static::$loginKey ] = array(
			'photo'		=> isset( $values['profilesync_photo'] ) ? $values['profilesync_photo'] : ( isset( $settings[ static::$loginKey ]['photo'] ) ? $settings[ static::$loginKey ]['photo'] : FALSE ),
			'cover'		=> isset( $values['profilesync_cover'] ) ? $values['profilesync_cover'] : ( isset( $settings[ static::$loginKey ]['cover'] ) ? $settings[ static::$loginKey ]['cover'] : FALSE ),
			'status'	=> isset( $values['profilesync_status'] ) ? ( $values['profilesync_status'] ? 'import' : '' ) : ( isset( $settings[ static::$loginKey ]['status'] ) ? $settings[ static::$loginKey ]['status'] : FALSE ),
		);
		
		/* We cannot have more than one service syncing photos/cover photos - otherwise they conflict */
		foreach ( array( 'photo', 'cover' ) as $type )
		{
			if ( $settings[ static::$loginKey ][ $type ] )
			{
				foreach ( $settings as $service => $serviceSettings )
				{
					if ( $service != static::$loginKey and $serviceSettings[ $type ] )
					{
						$settings[ $service ][ $type ] = FALSE;
					}
				}
			}
		}
				
		/* Finalise */	
		$this->member->profilesync = json_encode( $settings );
		
		/* If we're enabling status import, reset last sync time so we import the latest status */
		if ( isset( $values['profilesync_status'] ) AND $values['profilesync_status'] == 'import' )
		{
			$this->member->profilesync_lastsync = 0;
		}
		
		$this->member->save();
		$this->sync();
	}
	
	/**
	 * Sync
	 *
	 * @return	void
	 */
	public function sync()
	{
		$settings = $this->settings();
		if ( $settings['photo'] )
		{
			$class = get_called_class();
			try
			{
				if ( $photo = $this->photo() )
				{
					if ( $photo instanceof \IPS\File )
					{
						$this->member->pp_main_photo = (string) $photo;
						$this->member->pp_thumb_photo = NULL;
						$this->member->pp_photo_type = 'custom';
					}
					else
					{
						$this->member->pp_main_photo = (string) $photo;
						$this->member->pp_thumb_photo = NULL;
						$this->member->pp_photo_type = 'sync-' . mb_substr( $class, mb_strrpos( $class, '\\' ) + 1 );
					}
				}
			}
			catch ( \RuntimeException $e ) {}
		}
		if ( $settings['cover'] and $this->member->group['gbw_allow_upload_bgimage'] )
		{
			/* Download and import the synced cover photo */
			try
			{
				$coverPhoto = (string) $this->cover();
			}
			catch ( \RuntimeException $e ) {}

			/* Did we get a cover photo returned? */
			if ( !empty( $coverPhoto ) )
			{
				/* Delete any existing */
				if ( $this->member->pp_cover_photo )
				{
					try
					{
						\IPS\File::get( 'core_Profile', $this->member->pp_cover_photo )->delete();
					}
					catch ( \Exception $e ) { }
				}

				/* Save */
				$this->member->pp_cover_photo = $coverPhoto;
			}
		}
		/* We check against canCreate() to ensure we check all appropriate restrictions, including group and member restrictions */
		if ( $settings['status'] and $settings['status'] == 'import' and \IPS\core\Statuses\Status::canCreate( $this->member ) and $this->canImportStatus( $this->member ) )
		{
			if ( $status = $this->status() )
			{
				if ( $status->date > $this->member->profilesync_lastsync )
				{	
					try
					{
						$status->member_id = $this->member->member_id;
						$status->imported = TRUE;
						$status->save();
						
						\IPS\Content\Search\Index::i()->index( $status );
					}
					catch ( \Exception $e )
					{
						// May fail if status contains invalid data (like utf8mb4 characters if the database doesn't support that, etc)
						// In this case, we will just skip over the status
					}
				}
			}
		}
		
		$this->member->profilesync_lastsync = time();
		$this->member->save();
	}
	
	/**
	 * Disassociate
	 *
	 * @return	void
	 */
	public function disassociate()
	{
		$settings = $this->member->profilesync ? json_decode( $this->member->profilesync, TRUE ) : array();
		$class = get_called_class();
		unset( $settings[ $class::$loginKey ] );
		$this->member->profilesync = empty( $settings ) ? NULL : json_encode( $settings );
		
		$this->_disassociate();
	}
	
	/**
	 * Parse status text - ensures valid and safe HTML, filters profanity, etc.
	 *
	 * @param	string	$value
	 * @return	void
	 */
	protected function _parseStatusText( $value )
	{
		/* Make sure utf8mb4 characters won't cause us issues */
		$value = \IPS\Text\Parser::utf8mb4SafeDecode( $value );
		
		/* Parse */		
		$value = \IPS\Text\Parser::parseStatic( $value, FALSE, NULL, $this->member, 'core_Members' );
		
		/* Return */
		return $value;
	}
}