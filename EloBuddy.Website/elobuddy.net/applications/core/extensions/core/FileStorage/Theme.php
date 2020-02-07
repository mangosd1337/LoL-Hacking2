<?php
/**
 * @brief		File Storage Extension: Theme
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Sep 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Theme
 */
class _Theme
{
	/**
	 * Some file storage engines need to store a gzip version of some files that can be served to a browser gzipped
	 */
	public static $storeGzipExtensions = array( 'css', 'js' );
	
	/**
	 * The configuration settings have been updated
	 *
	 * @return void
	 */
	public static function settingsUpdated()
	{
		/* Clear out CSS as custom URL may have changed */
		\IPS\Theme::deleteCompiledCss();
	}
	
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return 4; // While this isn't the number of files, it's the number of steps this will take to move them, which is all it's used for
	}	
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\UnderflowException					When file record doesn't exist. Indicating there are no more files to move
	 * @return	void|int							An offset integer to use on the next cycle, or nothing
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		switch ( $offset )
		{
			case 0:
				foreach ( \IPS\Member\Group::groups() as $group )
				{
					if ( $group->g_icon )
					{
						try
						{
							$group->g_icon = (string) \IPS\File::get( $oldConfiguration ?: 'core_Theme', $group->g_icon )->move( $storageConfiguration );
							$group->save();
						}
						catch( \Exception $e )
						{
							/* Any issues are logged */
						}
					}
				}
				return TRUE;

			case 1:
				foreach ( \IPS\Db::i()->select( '*', 'core_member_ranks' ) as $rank )
				{
					if ( $rank['icon'] )
					{
						try
						{
							\IPS\Db::i()->update( 'core_member_ranks', array( 'icon' => (string) \IPS\File::get( $oldConfiguration ?: 'core_Theme', $rank['icon'] )->move( $storageConfiguration ) ), array( 'id=?', $rank['id'] ) );
						}
						catch( \Exception $e )
						{
							/* Any issues are logged */
						}
					}
				}

				unset( \IPS\Data\Store::i()->ranks );
				return TRUE;

			case 2:
				foreach ( \IPS\Db::i()->select( '*', 'core_reputation_levels' ) as $rep )
				{
					try
					{
						if ( $rep['level_image'] )
						{
							\IPS\Db::i()->update( 'core_reputation_levels', array( 'level_image' => (string) \IPS\File::get( $oldConfiguration ?: 'core_Theme', $rep['level_image'] )->move( $storageConfiguration ) ), array( 'level_id=?', $rep['level_id'] ) );
						}
					}
					catch( \Exception $e )
					{
						/* Any issues are logged */
					}
				}
				unset( \IPS\Data\Store::i()->reputationLevels );
				return TRUE;

			case 3:
				/* Trash CSS and images */
				\IPS\Theme::clearFiles( \IPS\Theme::TEMPLATES + \IPS\Theme::CSS + \IPS\Theme::IMAGES );
				
				return TRUE;

			case 4:
				/* Move logos */
				foreach( \IPS\Theme::themes() as $id => $set )
				{
					$logos   = $set->logo;
					$changed = false;
					
					foreach( array( 'front', 'sharer', 'favicon' ) as $icon )
					{
						if ( isset( $logos[ $icon ] ) AND is_array( $logos[ $icon ] ) )
						{
							if ( ! empty( $logos[ $icon ]['url'] ) )
							{
								try
								{
									$logos[ $icon ]['url'] = (string) \IPS\File::get( $oldConfiguration ?: 'core_Theme', $logos[ $icon ]['url'] )->move( $storageConfiguration );
									$changed = true;
								}
								catch( \Exception $e )
								{
									/* Any issues are logged */
								}
							}
						}
					}
					
					if ( $changed === true )
					{
						$set->saveSet( array( 'logo' => $logos ) );
					}
				}
				
				/* Trash JS */
				\IPS\Output::clearJsFiles();
				
				/* All done */
				throw new \UnderflowException;

			default:
				/* Go away already */
				throw new \UnderflowException;
		}
	}
	
	/**
	 * Fix all URLs
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @return void
	 */
	public function fixUrls( $offset )
	{
		switch ( $offset )
		{
			case 0:
				foreach ( \IPS\Member\Group::groups() as $group )
				{
					if ( $new = \IPS\File::repairUrl( $group->g_icon ) )
					{
						try
						{
							$group->g_icon = $new;
							$group->save();
						}
						catch( \Exception $e )
						{
							/* Any issues are logged */
						}
					}
				}
				return TRUE;

			case 1:
				foreach ( \IPS\Db::i()->select( '*', 'core_member_ranks' ) as $rank )
				{
					if ( $new = \IPS\File::repairUrl( $rank['icon'] ) )
					{
						try
						{
							\IPS\Db::i()->update( 'core_member_ranks', array( 'icon' => $new ), array( 'id=?', $rank['id'] ) );
						}
						catch( \Exception $e )
						{
							/* Any issues are logged */
						}
					}
				}

				unset( \IPS\Data\Store::i()->ranks );
				return TRUE;

			case 2:
				foreach ( \IPS\Db::i()->select( '*', 'core_reputation_levels' ) as $rep )
				{
					try
					{
						if ( $new = \IPS\File::repairUrl( $rep['level_image'] ) )
						{
							\IPS\Db::i()->update( 'core_reputation_levels', array( 'level_image' => $new ), array( 'level_id=?', $rep['level_id'] ) );
						}
					}
					catch( \Exception $e )
					{
						/* Any issues are logged */
					}
				}
				unset( \IPS\Data\Store::i()->reputationLevels );
				return TRUE;

			case 3:
				/* Trash CSS and images */
				\IPS\Theme::clearFiles( \IPS\Theme::TEMPLATES + \IPS\Theme::CSS + \IPS\Theme::IMAGES );
				
				return TRUE;

			case 4:
				/* Move logos */
				foreach( \IPS\Theme::themes() as $id => $set )
				{
					$logos   = $set->logo;
					$changed = false;
					
					foreach( array( 'front', 'sharer', 'favicon' ) as $icon )
					{
						if ( isset( $logos[ $icon ] ) AND is_array( $logos[ $icon ] ) )
						{
							if ( ! empty( $logos[ $icon ]['url'] ) and $new = \IPS\File::repairUrl( $logos[ $icon ]['url'] ) )
							{
								try
								{
									$logos[ $icon ]['url'] = $new;
									$changed = true;
								}
								catch( \Exception $e )
								{
									/* Any issues are logged */
								}
							}
						}
					}
					
					if ( $changed === true )
					{
						$set->saveSet( array( 'logo' => $logos ) );
					}
				}
				
				/* Trash JS */
				\IPS\Output::clearJsFiles();
				
				/* All done */
				throw new \UnderflowException;
		}
	}

	/**
	 * Check if a file is valid
	 *
	 * @param	\IPS\Http\Url	$file		The file to check
	 * @return	bool
	 */
	public function isValidFile( $file )
	{
		/* Is it a group icon? */
		foreach ( \IPS\Member\Group::groups() as $group )
		{
			if ( $group->g_icon == (string) $file )
			{
				return TRUE;
			}
		}

		/* Is it a rank icon? */
		foreach ( \IPS\Db::i()->select( '*', 'core_member_ranks' ) as $rank )
		{
			if ( $rank['icon'] == (string) $file )
			{
				return TRUE;
			}
		}

		/* Is it a reputation level icon? */
		foreach ( \IPS\Db::i()->select( '*', 'core_reputation_levels' ) as $rep )
		{
			if ( $rep['level_image'] == (string) $file )
			{
				return TRUE;
			}
		}

		/* Is it a skin image? */
		foreach ( \IPS\Db::i()->select( '*', 'core_theme_resources' ) as $image )
		{
			if ( $image['resource_filename'] == (string) $file )
			{
				return TRUE;
			}
		}
		
		/* Is it JS? */
		if ( isset( \IPS\Data\Store::i()->javascript_map ) )
		{
			foreach( \IPS\Data\Store::i()->javascript_map as $app => $data )
			{
				foreach( \IPS\Data\Store::i()->javascript_map[ $app ] as $key => $js )
				{
					if ( $js == (string) $file )
					{
						return TRUE;
					}
				}
			}
		}

		/* Is it a skin logo image or CSS? */
		foreach( \IPS\Theme::themes() as $set )
		{
			foreach( array( 'front', 'sharer', 'favicon' ) as $icon )
			{
				if ( isset( $set->logo[ $icon ] ) AND is_array( $set->logo[ $icon ] ) )
				{
					if ( ! empty( $set->logo[ $icon ]['url'] ) AND $set->logo[ $icon ]['url'] == (string) $file )
					{
						return TRUE;
					}
				}
			}

			foreach( $set->css_map as $key => $css )
			{
				if ( $css == (string) $file )
				{
					return TRUE;
				}
			}
		}

		/* Not found? Then must not be valid */
		return FALSE;
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		// It's not possible to delete the core application, and this would break the entire site, so let's not bother with this
		return;
	}
}