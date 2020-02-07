<?php
/**
 * @brief		Search Result not from Index
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Sep 2015
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search\Result;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Result not from Index
 */
class _Custom extends \IPS\Content\Search\Result
{
	/**
	 * @brief	HTML
	 */
	protected $html;
	
	/**
	 * @brief	Image
	 */
	protected $image;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\DateTime	$time	The time for this result
	 * @param	string			$html	HTML to display
	 * @param	string|NULL		$image	HTML for image to display
	 * @return	void
	 */
	public function __construct( \IPS\DateTime $time, $html, $image = NULL )
	{
		$this->createdDate = $time;
		$this->lastUpdatedDate = $time;
		$this->html = $html;
		$this->image = $image;
	}
	
	/**
	 * HTML
	 *
	 * @return	string
	 */
	public function html( $view = 'expanded' )
	{
		return \IPS\Theme::i()->getTemplate( 'streams', 'core' )->extraItem( $this->createdDate, $this->image, $this->html, $view );
	}
}