<?php
/**
 * @brief		Support Content in sitemaps
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		26 Dec 2013
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
 * Support Content in sitemaps
 */
class _Content extends \IPS\Content\ExtensionGenerator
{
	/**
	 * @brief	If TRUE, will prevent comment classes being included
	 */
	protected static $contentItemsOnly = TRUE;
	
	const RECOMMENDED_NODE_PRIORIY = 0.6;
	const RECOMMENDED_ITEM_PRIORITY = 1;
	const RECOMMENDED_ITEM_LIMIT = -1;
	
	/**
	 * @brief	Recommended Settings
	 */
	public $recommendedSettings = array();
	
	/**
	 * Add settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings()
	{
		$class = $this->class;
		if ( $class::$includeInSitemap === FALSE )
		{
			return array();
		}
		
		if ( isset( $class::$containerNodeClass ) )
		{
			$nodeClass = $class::$containerNodeClass;
			$this->recommendedSettings["sitemap_{$nodeClass::$nodeTitle}_priority"] = self::RECOMMENDED_NODE_PRIORIY;
		}
		
		$this->recommendedSettings["sitemap_{$class::$title}_count"] = (string) self::RECOMMENDED_ITEM_LIMIT;
		$this->recommendedSettings["sitemap_{$class::$title}_priority"] = self::RECOMMENDED_ITEM_PRIORITY;
	
		$settings = \IPS\Settings::i()->sitemap_content_settings ? json_decode( \IPS\Settings::i()->sitemap_content_settings, TRUE ) : array();
	
		\IPS\Member::loggedIn()->language()->words[ 'sitemap_core_Content_' . mb_substr( str_replace( '\\', '_', $class ), 4 ) ] = \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl', FALSE );
		\IPS\Member::loggedIn()->language()->words[ "sitemap_{$class::$title}_priority_desc" ] = \IPS\Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic_desc', FALSE );
	
		$return = array();
		if ( isset( $class::$containerNodeClass ) )
		{
			\IPS\Member::loggedIn()->language()->words[ "sitemap_{$nodeClass::$nodeTitle}_priority_desc" ] = \IPS\Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic_desc', FALSE );
			$return["sitemap_{$nodeClass::$nodeTitle}_priority"] = new \IPS\Helpers\Form\Select( "sitemap_{$nodeClass::$nodeTitle}_priority", isset( $settings["sitemap_{$nodeClass::$nodeTitle}_priority"] ) ? $settings["sitemap_{$nodeClass::$nodeTitle}_priority"] : $this->recommendedSettings["sitemap_{$nodeClass::$nodeTitle}_priority"], FALSE, array( 'options' => \IPS\Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, "sitemap_{$nodeClass::$nodeTitle}_priority" );
			$return["sitemap_{$nodeClass::$nodeTitle}_priority"]->label	= \IPS\Member::loggedIn()->language()->addToStack( 'sitemap_priority_container' );
		}
		
		$return["sitemap_{$class::$title}_count"]	 = new \IPS\Helpers\Form\Number( "sitemap_{$class::$title}_count", isset( $settings["sitemap_{$class::$title}_count"] ) ? $settings["sitemap_{$class::$title}_count"] : $this->recommendedSettings["sitemap_{$class::$title}_count"], FALSE, array( 'min' => '-1', 'unlimited' => '-1' ), NULL, NULL, NULL, "sitemap_{$class::$title}_count" );
		$return["sitemap_{$class::$title}_count"]->label	= \IPS\Member::loggedIn()->language()->addToStack( 'sitemap_number_generic' );
		$return["sitemap_{$class::$title}_priority"] = new \IPS\Helpers\Form\Select( "sitemap_{$class::$title}_priority", isset( $settings["sitemap_{$class::$title}_priority"] ) ? $settings["sitemap_{$class::$title}_priority"] : $this->recommendedSettings["sitemap_{$class::$title}_priority"], FALSE, array( 'options' => \IPS\Sitemap::$priorities, 'unlimited' => '-1', 'unlimitedLang' => 'sitemap_dont_include' ), NULL, NULL, NULL, "sitemap_{$class::$title}_priority" );
		$return["sitemap_{$class::$title}_priority"]->label	= \IPS\Member::loggedIn()->language()->addToStack( 'sitemap_priority_generic' );
		
		return $return;
	}
	
	/**
	 * Save settings for ACP configuration
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function saveSettings( $values )
	{
		if ( $values['sitemap_configuration_info'] )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( array() ) ), array( 'conf_key=?', 'sitemap_content_settings' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
		else
		{
			$class = $this->class;

			if ( $class::$includeInSitemap === FALSE )
			{
				return;
			}

			$toSave = \IPS\Settings::i()->sitemap_content_settings ? json_decode( \IPS\Settings::i()->sitemap_content_settings, TRUE ) : array();
			
			if ( isset( $class::$containerNodeClass ) )
			{
				$nodeClass = $class::$containerNodeClass;
				$toSave["sitemap_{$nodeClass::$nodeTitle}_priority"] = $values["sitemap_{$nodeClass::$nodeTitle}_priority"];	
			}
			
			foreach ( array( "sitemap_{$class::$title}_count", "sitemap_{$class::$title}_priority" ) as $k )
			{
				$toSave[ $k ] = $values[ $k ];
			}
			
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => json_encode( $toSave ) ), array( 'conf_key=?', 'sitemap_content_settings' ) );
			unset( \IPS\Data\Store::i()->settings );
			\IPS\Settings::i()->sitemap_content_settings = json_encode( $toSave );
		}
	}

	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames()
	{
		$class = $this->class;
	
		if ( $class::$includeInSitemap === FALSE )
		{
			return array();
		}

		$files = array();
		$settings = \IPS\Settings::i()->sitemap_content_settings ? json_decode( \IPS\Settings::i()->sitemap_content_settings, TRUE ) : array();
		
		/* Check that guests can access the content at all */
		try
		{
			$module = \IPS\Application\Module::get( $class::$application, $class::$module, 'front' );
			if ( !$module->can( 'view', new \IPS\Member ) )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			return array();
		}

		if ( isset( $class::$containerNodeClass ) )
		{
			$nodeClass = $class::$containerNodeClass;
			
			/* We need one file for the nodes */
			$files[] = 'sitemap_content_' . str_replace( '\\', '_', mb_substr( $nodeClass, 4 ) );
		}

		/* And however many for the content items */
		$contentItems = $class::getItemsWithPermission( $class::sitemapWhere(), NULL, NULL, 'read', \IPS\Content\Hideable::FILTER_PUBLIC_ONLY, 0, new \IPS\Member, NULL, NULL, NULL, TRUE );
		$requestedCount = isset( $settings["sitemap_{$class::$title}_count"] ) ? $settings["sitemap_{$class::$title}_count"] : 0;

		/* Choose which count to use to calculate the number of filess */
		$usedCount = ( $requestedCount > $contentItems OR $requestedCount <= 0 ) ? $contentItems : $requestedCount;

		$count = ceil( $usedCount / \IPS\Sitemap::MAX_PER_FILE );
		for( $i=1; $i <= $count; $i++ )
		{
			$files[] = 'sitemap_content_' . str_replace( '\\', '_', mb_substr( $class, 4 ) ) . '_' . $i;
		}

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
		$class = $this->class;
		if ( isset( $class::$containerNodeClass ) )
		{
			$nodeClass = $class::$containerNodeClass;
		}
		$entries	= array();
		$lastId		= 0;
		$settings	= \IPS\Settings::i()->sitemap_content_settings ? json_decode( \IPS\Settings::i()->sitemap_content_settings, TRUE ) : array();
		
		if ( isset( $nodeClass ) and $filename == 'sitemap_content_' . str_replace( '\\', '_', mb_substr( $nodeClass, 4 ) ) )
		{
			$select = array();
			if ( in_array( 'IPS\Content\Permissions', class_implements( $nodeClass ) ) or in_array( 'IPS\Node\Permissions', class_implements( $nodeClass ) ) )
			{
				$select = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', $nodeClass::$databaseTable, array( '(' . \IPS\Db::i()->findInSet( "perm_{$nodeClass::$permissionMap['read']}", array( \IPS\Settings::i()->guest_group ) ) . ' OR ' . "perm_{$nodeClass::$permissionMap['read']}=? )", '*' ) )->join( 'core_permission_index', array( "core_permission_index.app=? AND core_permission_index.perm_type=? AND core_permission_index.perm_type_id={$nodeClass::$databaseTable}.{$nodeClass::$databasePrefix}{$nodeClass::$databaseColumnId}", $nodeClass::$permApp, $nodeClass::$permType ) ), $nodeClass );
			}
			else if ( $nodeClass::$ownerTypes !== NULL and is_subclass_of( $nodeClass, 'IPS\Node\Model' ) )
			{
                $select = $nodeClass::roots( 'read', new \IPS\Member );
			}

			foreach ( $select as $node )
			{
				$data = array( 'url' => $node->url() );
				
				$priority = intval( isset( $settings["sitemap_{$nodeClass::$nodeTitle}_priority"] ) ? $settings["sitemap_{$nodeClass::$nodeTitle}_priority"] : self::RECOMMENDED_NODE_PRIORIY );
				if ( $priority !== -1 )
				{
					$data['priority'] = $priority;
					$entries[] = $data;
				}
			}
		}
		else
		{
			$exploded	= explode( '_', $filename );
			$block		= (int) array_pop( $exploded );
			$totalLimit	= isset( $settings["sitemap_{$class::$title}_count"] ) AND $settings["sitemap_{$class::$title}_count"] ? $settings["sitemap_{$class::$title}_count"] : self::RECOMMENDED_ITEM_LIMIT;
			$offset		= ( $block - 1 ) * \IPS\Sitemap::MAX_PER_FILE;
			$limit		= \IPS\Sitemap::MAX_PER_FILE;

			if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
			{
				$limit = $totalLimit - $offset;
			}

			/* Create limit clause */
			$limitClause	= array( $offset, $limit );

			$where	= $class::sitemapWhere();

			/* Try to fetch the highest ID built in the last sitemap, if it exists */
			try
			{
				$lastId = \IPS\Db::i()->select( 'last_id', 'core_sitemap', array( array( 'sitemap=?', implode( '_', $exploded ) . '_' . ( $block - 1 ) ) ) )->first();

				if( $lastId > 0 )
				{
					$where[]		= array( $class::$databasePrefix . $class::$databaseColumnId . ' > ?', $lastId );
					$limitClause	= $limit;
				}
			}
			catch( \UnderflowException $e ){}

			$idColumn = $class::$databaseColumnId;
			foreach ( $class::getItemsWithPermission( $where, $class::$databasePrefix . $class::$databaseColumnId .' ASC', $limitClause, 'read', \IPS\Content\Hideable::FILTER_PUBLIC_ONLY, 0, new \IPS\Member, TRUE ) as $item )
			{
				$data = array( 'url' => $item->url() );
				$priority = ( $item->sitemapPriority() ?: ( intval( isset( $settings["sitemap_{$class::$title}_priority"] ) ? $settings["sitemap_{$class::$title}_priority"] : self::RECOMMENDED_ITEM_PRIORITY ) ) );
				if ( $priority !== -1 )
				{
					$data['priority'] = $priority;
					$entries[] = $data;
				}

				$lastId = $item->$idColumn;
			}
		}

		$sitemap->buildSitemapFile( $filename, $entries, $lastId );
	}
}