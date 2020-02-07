<?php
/**
 * @brief		ACP Live Search Extension: Groups
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		30 Sept 2013
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
 * @brief	Groups
 */
class _Groups
{
	/**
	 * Check we have access
	 *
	 * @return	void
	 */
	public function hasAccess()
	{
		/* Check Permissions */
		return \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'groups_manage' );
	}
	
	/**
	 * Get the search results
	 *
	 * @param	string	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( $searchTerm )
	{
		/* Check we have access */
		if( !$this->hasAccess() )
		{
			return array();
		}

		/* Init */
		$results = array();
		$searchTerm = mb_strtolower( $searchTerm );
		
		/* Perform the search */
		$groups = \IPS\Db::i()->select(
						"*",
						'core_groups',
						array( "word_custom LIKE CONCAT( '%', ?, '%' ) AND lang_id=?", $searchTerm, \IPS\Member::loggedIn()->language()->id ),
						NULL,
						NULL
					)->join(
						'core_sys_lang_words',
						"word_key=CONCAT( 'core_group_', g_id )"
					);
		
		
		/* Format results */
		foreach ( $groups as $group )
		{
			$group = \IPS\Member\Group::constructFromData( $group );
			
			$results[] = \IPS\Theme::i()->getTemplate('livesearch')->group( $group );
		}
					
		return $results;
	}
}