<?php
/**
 * @brief		File Storage Extension: Referral Banners
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\FileStorage;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Storage Extension: Referral Banners
 */
class _ReferralBanners
{
	/**
	 * Count stored files
	 *
	 * @return	int
	 */
	public function count()
	{
		return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_referral_banners', 'rb_upload=1' )->first();
	}
	
	/**
	 * Move stored files
	 *
	 * @param	int			$offset					This will be sent starting with 0, increasing to get all files stored by this extension
	 * @param	int			$storageConfiguration	New storage configuration ID
	 * @param	int|NULL	$oldConfiguration		Old storage configuration ID
	 * @throws	\Underflowexception				When file record doesn't exist. Indicating there are no more files to move
	 * @return	void
	 */
	public function move( $offset, $storageConfiguration, $oldConfiguration=NULL )
	{
		$record = \IPS\Db::i()->select( '*', 'nexus_referral_banners', 'rb_upload=1', 'rb_id', array( $offset, 1 ) )->first();

		try
		{
			$file = \IPS\File::get( $oldConfiguration ?: 'nexus_ReferralBanners', $record['rb_url'] )->move( $storageConfiguration );
			
			if ( (string) $file != $record['rb_url'] )
			{
				\IPS\Db::i()->update( 'nexus_referral_banners', array( 'rb_url' => (string) $file ), array( 'rb_id=?', $record['rb_id'] ) );
			}
		}
		catch( \Exception $e )
		{
			/* Any issues are logged */
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
		$record = \IPS\Db::i()->select( '*', 'nexus_referral_banners', 'rb_upload=1', 'rb_id', array( $offset, 1 ) )->first();
		
		if ( $new = \IPS\File::repairUrl( $record['rb_url'] ) )
		{
			\IPS\Db::i()->update( 'nexus_referral_banners', array( 'rb_url' => $new ), array( 'rb_id=?', $record['rb_id'] ) );
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
		try
		{
			\IPS\Db::i()->select( '*', 'nexus_referral_banners', array( 'rb_url=? and rb_upload=1', (string) $file ) )->first();
			return TRUE;
		}
		catch ( \UnderflowException $e )
		{
			return FALSE;
		}
	}

	/**
	 * Delete all stored files
	 *
	 * @return	void
	 */
	public function delete()
	{
		foreach( \IPS\Db::i()->select( '*', 'nexus_referral_banners', 'rb_upload=1' ) as $banner )
		{
			try
			{
				\IPS\File::get( 'nexus_ReferralBanners', $banner['rb_url'] )->delete();
			}
			catch( \Exception $e ){}
		}
	}
}