<?php
/**
 * @brief		Hidebale Interface for Content Models/Comments
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		6 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Hidebale Interface for Content Models/Comments
 *
 * @note	Content classes will gain special functionality by implementing this interface
 */
interface Hideable
{
	/**
	 * @brief	Filter: Determine if the member is a moderator with permission to view hidden content (default)
	 */
	const FILTER_AUTOMATIC		= NULL;

	/**
	 * @brief	Filter: Show hidden content regardless of permissions (use very cautiously)
	 */
	const FILTER_SHOW_HIDDEN	= TRUE;

	/**
	 * @brief	Filter: Only return unapproved content that the current viewing user submitted
	 */
	const FILTER_OWN_HIDDEN		= FALSE;

	/**
	 * @brief	Filter: Only return hidden content (used primarily for widgets when you select 'hidden', respects permission to view hidden content)
	 */
	const FILTER_ONLY_HIDDEN	= 0;

	/**
	 * @brief	Filter: Only return public content (approved and not hidden, useful when things may get cached)
	 */
	const FILTER_PUBLIC_ONLY	= -1;
}