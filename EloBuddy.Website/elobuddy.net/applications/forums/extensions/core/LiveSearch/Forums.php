<?php
/**
 * @brief		ACP Live Search Extension
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		08 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\LiveSearch;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Live Search Extension
 */
class _Forums
{
	/**
	 * Check we have access
	 *
	 * @return	void
	 */
	public function hasAccess()
	{
		/* Check Permissions */
		return \IPS\Member::loggedIn()->hasAcpRestriction( 'forums', 'forums', 'forums_manage' );
	}

	/**
	 * Get the search results
	 *
	 * @param	string	Search Term
	 * @return	array 	Array of results
	 */
	public function getResults( $searchTerm )
	{
		/* Init */
		$results = array();
		$searchTerm = mb_strtolower( $searchTerm );

		/* Start with categories */
		if( $this->hasAccess() )
		{
			/* Perform the search */
			$forums = \IPS\Db::i()->select(
							"*",
							'forums_forums',
							array( "word_custom LIKE CONCAT( '%', ?, '%' ) AND lang_id=?", $searchTerm, \IPS\Member::loggedIn()->language()->id ),
							NULL,
							NULL
					)->join(
							'core_sys_lang_words',
							"word_key=CONCAT( 'forums_forum_', id )"
						);
			
			/* Format results */
			foreach ( $forums as $forum )
			{
				$forum = \IPS\forums\Forum::constructFromData( $forum );
				
				$results[] = \IPS\Theme::i()->getTemplate( 'livesearch', 'forums', 'admin' )->forum( $forum );
			}
		}

		return $results;
	}
}