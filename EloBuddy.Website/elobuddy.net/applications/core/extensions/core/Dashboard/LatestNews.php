<?php
/**
 * @brief		Dashboard extension: Latest News
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Latest News
 */
class _LatestNews
{
	/**
	* Can the current user view this dashboard item?
	*
	* @return	bool
	*/
	public function canView()
	{
		return TRUE;
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		$ipsNews = ( isset( \IPS\Data\Store::i()->ips_news ) ) ? json_decode( \IPS\Data\Store::i()->ips_news, TRUE ) : array();
		
		if( empty( $ipsNews ) or $ipsNews['time'] < ( time() - 43200 ) )
		{
			try
			{
				$this->refreshNews();
				$ipsNews = ( isset( \IPS\Data\Store::i()->ips_news ) ) ? json_decode( \IPS\Data\Store::i()->ips_news, TRUE ) : array();
			}
			catch ( \IPS\Http\Exception $e ) {}
			catch( \IPS\Http\Request\Exception $e ) {}
			catch( \RuntimeException $e ) {}
		}
		
		return \IPS\Theme::i()->getTemplate( 'dashboard' )->ipsNews( isset( $ipsNews['content'] ) ? $ipsNews['content'] : NULL );
	}

	/**
	 * Updates news store
	 *
	 * @return	void
	 * @throws	\IPS\Http\Request\Exception
	 */
	protected function refreshNews()
	{
		\IPS\Data\Store::i()->ips_news = json_encode( array(
			'content'	=> \IPS\Http\Url::ips( 'news' )->request()->get()->decodeJson(),
			'time'		=> time()
		) );
	}
}