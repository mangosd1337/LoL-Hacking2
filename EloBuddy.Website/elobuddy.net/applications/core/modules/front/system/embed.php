<?php
/**
 * @brief		Embed iframe display
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Embed iframe display
 */
class _embed extends \IPS\Content\Controller
{
	/**
	 * Embed iframe display
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Check cache */
		$cacheKey = 'embed_' . md5( \IPS\Request::i()->url );
		try
		{
			$return = \IPS\Data\Cache::i()->getWithExpire( $cacheKey, TRUE );
		}
		
		/* Not in cache - fetch */
		catch ( \OutOfRangeException $e )
		{
			$return = \IPS\Text\Parser::embeddableMedia( \IPS\Request::i()->url, TRUE );
			
			/* And cache */
			\IPS\Data\Cache::i()->storeWithExpire( $cacheKey, $return, \IPS\DateTime::create()->add( new \DateInterval('P1D') ), TRUE );
		}
		
		/* Output */
		\IPS\Output::i()->sendOutput( $return, 200 );
	}
}