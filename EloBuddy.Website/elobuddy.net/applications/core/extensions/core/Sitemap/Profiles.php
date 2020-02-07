<?php
/**
 * @brief		Support profiles in sitemaps
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support profiles in sitemaps
 */
class _Profiles
{
	/**
	 * @brief	Recommended Settings
	 */
	public $recommendedSettings = array(
		'sitemap_profiles_count'	=> -1,
		'sitemap_profiles_priority'	=> 0.6
	);
	
	/**
	 * Settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings()
	{
		return array(
			'sitemap_profiles_count'	=> new \IPS\Helpers\Form\Number( 'sitemap_profiles_count', \IPS\Settings::i()->sitemap_profiles_count, FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, 'sitemap_profiles_count' ),
			'sitemap_profiles_priority'	=> new \IPS\Helpers\Form\Select( 'sitemap_profiles_priority', \IPS\Settings::i()->sitemap_profiles_priority, FALSE, array( 'options' => \IPS\Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, 'sitemap_profiles_priority' )
		);
	}

	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames()
	{
		/* First, we need to make sure we are actually including profiles in the sitemap */
		if ( \IPS\Settings::i()->sitemap_profiles_count == 0 )
		{
			return array();
		}
		
		/* Then, make sure the module is enabled. */
		$profileModule = \IPS\Application\Module::get( 'core', 'members', 'front' );
		if( !$profileModule->visible )
		{
			return array();
		}
		
		/* And that Guests can view the profile module */
		if( !$profileModule->can( 'view', new \IPS\Member ) )
		{
			return array();
		}
		
		/* Get a count of how many files we'll be generating */
		$count = \IPS\Db::i()->select( 'COUNT(*)', 'core_members' )->first();
		$count = ( $count > \IPS\Sitemap::MAX_PER_FILE ) ? ceil( $count / \IPS\Sitemap::MAX_PER_FILE ) : 1;
		
		/* If we are not storing all profiles, limit the count */
		if ( \IPS\Settings::i()->sitemap_profiles_count > -1 AND $count > \IPS\Settings::i()->sitemap_profiles_count )
		{
			$count = \IPS\Settings::i()->sitemap_profiles_count;
		}

		/* Generate the file names */
		$files	= array();
		for( $i=1; $i <= $count; $i++ )
		{
			$files[]	= "sitemap_profiles_" . $i;
		}

		/* Return */
		return $files;
	}

	/**
	 * Generate the sitemap
	 *
	 * @param	string			$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	\IPS\Sitemap	$sitemap	Sitemap object reference
	 * @return	void
	 */
	public function generateSitemap( $filename, $sitemap )
	{
		/* Which file are we building? */
		$_info		= explode( '_', $filename );
		$index		= array_pop( $_info ) - 1;
		$entries	= array();
		$start		= \IPS\Sitemap::MAX_PER_FILE * $index;
		$limit		= \IPS\Sitemap::MAX_PER_FILE;

		/* Have we already maxed out?  We shouldn't really hit this because getFilenames() already factors in the max, but best to check */
		if( \IPS\Settings::i()->sitemap_profiles_count > -1 AND \IPS\Settings::i()->sitemap_profiles_count < $start )
		{
			return;
		}

		/* Do we need less than 10k? */
		if( \IPS\Settings::i()->sitemap_profiles_count > -1 AND \IPS\Settings::i()->sitemap_profiles_count < $start + $limit )
		{
			$limit	= \IPS\Settings::i()->sitemap_profiles_count - $start;
		}

		/* Retrieve the members */
		foreach( \IPS\Db::i()->select( '*', 'core_members', NULL, 'member_id', array( $start, $limit ) ) as $row )
		{
			$entry	= array(
							'url'	=> \IPS\Http\Url::internal( "app=core&module=members&controller=profile&id={$row['member_id']}", 'front', 'profile', $row['members_seo_name'] )
							);

			if( \IPS\Settings::i()->sitemap_profiles_priority > 0 )
			{
				$entry['priority']	= \IPS\Settings::i()->sitemap_profiles_priority;
				$entries[]	= $entry;
			}
		}
		
		/* Build the file */
		$sitemap->buildSitemapFile( $filename, $entries );
	}
}