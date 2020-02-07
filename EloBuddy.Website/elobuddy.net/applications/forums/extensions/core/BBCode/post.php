<?php
/**
 * @brief		BBCode Tag
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		08 Feb 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\extensions\core\BBCode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * BBCode Tag
 */
class _post
{	
	/**
	 * Permission Check
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string		$area	The Editor area
	 * @return	bool
	 */
	public function permissionCheck( \IPS\Member $member, $area )
	{
		return \IPS\Text\Parser::canUse( $member, 'ipsLink', $area );
	}
	
	/**
	 * Get Configuration
	 *
	 * @code
	 	return array(
	 		'tag'			=> 'span',						// The HTML tag to use
	 		'attributes'	=> array( ... )					// Key/Value pairs of attributes to use (optional) - can use {option} to get the [tag=option] value
	 		'callback'		=> function( $node, $matches, $document )	// A callback to modify the DOMNode object
	 		{
	 			...
	 		},
	 		'block'			=> FALSE,						// If this is a block-level tag (optional, default false)
	 		'single'		=> FALSE,						// If this is a single tag, with no content (optional, default false)
	 	)
	 * @endcode
	 * @return	array
	 */
	public function getConfiguration()
	{
		return array(
			'tag' 		=> 'a',
			'callback'	=> function( \DOMElement $node, $matches, \DOMDocument $document )
			{
				try
				{
					$node->setAttribute( 'href', (string) \IPS\forums\Topic\Post::load( intval( $matches[2] ) )->url() );
				}
				catch ( \Exception $e ) {}
				
				return $node;
			},
		);
	}
}