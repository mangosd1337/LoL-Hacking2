<?php
/**
 * @brief		ACP Live Search Extension: Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Settings
 */
class _Settings
{
	/**
	 * Check we have access
	 *
	 * @return	void
	 */
	public function hasAccess()
	{
		/* Check Permissions */
		return TRUE;
	}

	/**
	 * Get the search results
	 *
	 * @param	string	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( $searchTerm )
	{
		$results = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_acp_search_index', array( "keyword LIKE CONCAT( '%', ?, '%' )", mb_strtolower( $searchTerm ) ) ) as $word )
		{
			if( !\IPS\Application::appIsEnabled( $word['app'] ) )
			{
				continue;
			}
			
			$url = \IPS\Http\Url::internal( $word['url'] );
			if ( !$word['restriction'] or \IPS\Member::loggedIn()->hasAcpRestriction( $url->queryString['app'], $url->queryString['module'], $word['restriction'] ) )
			{
				$results[ $word['url'] ] = \IPS\Theme::i()->getTemplate('livesearch')->generic( $url, $word['lang_key'] );
			}
		}
					
		return $results;
	}
}