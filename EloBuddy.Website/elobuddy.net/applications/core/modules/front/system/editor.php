<?php
/**
 * @brief		Editor AJAX functions Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		29 Apr 2013
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
 * Editor AJAX functions Controller
 */
class _editor extends \IPS\Dispatcher\Controller
{
	/**
	 * Preview iframe
	 *
	 * @return	void
	 */
	protected function preview()
	{
		$output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->preview( \IPS\Request::i()->editor_id );
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->blankTemplate( $output ) );
	}

	/**
	 * Link Dialog
	 *
	 * @return	void
	 */
	protected function link()
	{
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->link( \IPS\Request::i()->current, \IPS\Request::i()->editorId, isset( \IPS\Request::i()->block ) );
	}
	
	/**
	 * Code Dialog
	 *
	 * @return	void
	 */
	protected function code()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'codemirror/codemirror.css', 'core', 'interface' ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->code( \IPS\Request::i()->val, \IPS\Request::i()->editorId, md5( uniqid() ), \IPS\Request::i()->lang ?: 'html' );
	}
	
	/**
	 * Image Dialog
	 *
	 * @return	void
	 */
	protected function image()
	{
		$maxImageDims = \IPS\Settings::i()->attachment_image_size ? explode( 'x', \IPS\Settings::i()->attachment_image_size ) : array( 1000, 750 );
		$maxWidth = ( \IPS\Request::i()->actualWidth < $maxImageDims[0] ) ?  \IPS\Request::i()->actualWidth : $maxImageDims[0];
		$maxHeight = ( \IPS\Request::i()->actualHeight < $maxImageDims[1] ) ? \IPS\Request::i()->actualHeight : $maxImageDims[1];
		$ratio = round( \IPS\Request::i()->height / \IPS\Request::i()->width, 2 );

		if ( \IPS\Request::i()->width > $maxWidth )
		{			
			\IPS\Request::i()->width = $maxWidth;
			\IPS\Request::i()->height = floor( \IPS\Request::i()->width * $ratio );
		}
		if ( \IPS\Request::i()->height > $maxHeight )
		{
			\IPS\Request::i()->height = $maxHeight;
			\IPS\Request::i()->width = floor( \IPS\Request::i()->height * $ratio );
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->image( \IPS\Request::i()->editorId, \IPS\Request::i()->width, \IPS\Request::i()->height, $maxWidth, $maxHeight, \IPS\Request::i()->float, \IPS\Request::i()->link, $ratio );
	}
	
	/**
	 * AJAX validate link
	 *
	 * @return	void
	 */
	protected function validateLink()
	{
		/* CSRF check */
		\IPS\Session::i()->csrfCheck();

		/* Have we recently checked and validated this link? */
		$cacheKey = md5( \IPS\Request::i()->url . (bool) \IPS\Request::i()->noEmbed . (bool) \IPS\Request::i()->image );
		try
		{
			$cachedResult = \IPS\Data\Cache::i()->getWithExpire( $cacheKey, TRUE );

			\IPS\Output::i()->json( $cachedResult );
		}
		catch( \OutOfRangeException $e ){}

		/* Fetch the result and cache it, then return it */
		try
		{
			$title = NULL;
			$isEmbed = FALSE;
			$url = \IPS\Request::i()->url;
			if ( !\IPS\Request::i()->noEmbed and !\IPS\Request::i()->image and $embed = \IPS\Text\Parser::embeddableMedia( $url ) )
			{
				$insert = $embed;
				$isEmbed = TRUE;
			}
			else
			{
				$response = \IPS\Http\Url::external( $url )->request()->get();
				if ( \IPS\Request::i()->image and isset( $response->httpHeaders['Content-Type'] ) and mb_substr( $response->httpHeaders['Content-Type'], 0, 6 ) === 'image/' )
				{
					$insert = "<img src='{$url}' class='ipsImage'>";
				}
				else
				{
					if ( \IPS\Request::i()->image )
					{
						throw new \DomainException;
					}
					
					if ( \IPS\Request::i()->title )
					{
						$title = \IPS\Request::i()->title;
					}
					elseif ( isset( $response->httpHeaders['Content-Type'] ) and mb_substr( $response->httpHeaders['Content-Type'], 0, 9 ) === 'text/html' and preg_match( '/<title>(.+)<\/title>/', $response, $matches ) and isset( $matches[1] ) )
					{
						$title = $matches[1];
					}
					else
					{
						$title = $url;
					}
					
					$insert = "<a href='{$url}' ipsNoEmbed='true'>{$title}</a>";
				}
			}
			
			$result = array( 'preview' => trim( $insert ), 'title' => $title, 'embed' => $isEmbed );

			\IPS\Data\Cache::i()->storeWithExpire( $cacheKey, $result, \IPS\DateTime::create()->add( new\DateInterval( 'PT10S' ) ), TRUE );

			\IPS\Output::i()->json( $result );
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->json( $e->getMessage(), 500 );
		}
	}
			
	/**
	 * Get Emoticons Configuration
	 *
	 * @return	void
	 */
	protected function emoticons()
	{
		$emoticons = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_emoticons', NULL, 'emo_set_position,emo_position' ) as $row )
		{
			if ( !isset( $emoticons[ $row['emo_set'] ] ) )
			{
				$setLang = 'core_emoticon_group_' . $row['emo_set'];
				
				$emoticons[ $row['emo_set'] ] = array(
					'title'		=> \IPS\Member::loggedIn()->language()->addToStack( $setLang ),
					'total'		=> 0,
					'emoticons'	=> array(),
				);
			}
			
			$emoticons[ $row['emo_set'] ]['emoticons'][] = array(
				'src'	=> \IPS\File::get( 'core_Emoticons', $row['image'] )->url,
				'srcset' => $row['image_2x'] ? \IPS\File::get( 'core_Emoticons', $row['image_2x'] )->url . ' 2x' : FALSE,
				'height' => $row['height'] ?: FALSE,
				'width' => $row['width'] ?: FALSE,
				'text'	=> $row['typed']
			);
		}
		
		foreach ( $emoticons as $set => $data )
		{
			$emoticons[ $set ]['total'] = count( $data['emoticons'] );
		}
		
		\IPS\Output::i()->json( $emoticons );
	}
	
	/**
	 * My Media
	 *
	 * @return	void
	 */
	protected function myMedia()
	{
		/* Init */
		$perPage = 12;
		$search = isset( \IPS\Request::i()->search ) ? \IPS\Request::i()->search : null;
		
		/* Get all our available sources */
		$mediaSources = array();
		foreach ( \IPS\Application::allExtensions( 'core', 'EditorMedia' ) as $k => $class )
		{
			if ( $class->count( \IPS\Member::loggedIn(), isset( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : '' ) )
			{
				$mediaSources[] = $k;
			}
		}
		/* Work out what tab we're on */
		if ( !\IPS\Request::i()->tab )
		{
			if( !count( $mediaSources ) )
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMedia( \IPS\Request::i()->editorId, $mediaSources, NULL, NULL, NULL );
				return;
			}
			
			$sources = $mediaSources;
			\IPS\Request::i()->tab = array_shift( $sources );
		}

		$exploded = explode( '_', \IPS\Request::i()->tab );
		$classname = "IPS\\{$exploded[0]}\\extensions\\core\\EditorMedia\\{$exploded[1]}";
		$extension = new $classname;
		$url = \IPS\Http\Url::internal( "app=core&module=system&controller=editor&do=myMedia&tab=" . \IPS\Request::i()->tab . "&key=" . \IPS\Request::i()->key . "&postKey=" . \IPS\Request::i()->postKey . "&existing=1" );
		
		/* Count how many we have */
		$count = $extension->count( \IPS\Member::loggedIn(), isset( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : '', $search );

		$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		/* Display */
		if ( isset( \IPS\Request::i()->existing ) )
		{
			if ( isset( \IPS\Request::i()->search ) || ( isset( \IPS\Request::i()->page ) && \IPS\Request::i()->page !== 1 ) )
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMediaResults(
					$extension->get( \IPS\Member::loggedIn(), $search, isset( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : '', $page, $perPage ),
					\IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
						$url,
						ceil( $count / $perPage ),
						$page,
						$perPage
					),
					$url,
					\IPS\Request::i()->tab
				);
			}
			else
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMediaContent(
					$extension->get( \IPS\Member::loggedIn(), $search, isset( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : '', $page, $perPage ),
					\IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
						$url,
						ceil( $count / $perPage ),
						$page,
						$perPage
					),
					$url,
					\IPS\Request::i()->tab
				);
			}
		}
		else
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMedia( \IPS\Request::i()->editorId, $mediaSources, \IPS\Request::i()->tab, $url, \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->myMediaContent(
				$extension->get( \IPS\Member::loggedIn(), $search, isset( \IPS\Request::i()->postKey ) ? \IPS\Request::i()->postKey : '', $page, $perPage ),
				\IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
					$url,
					ceil( $count / $perPage ),
					$page,
					$perPage
				),
				$url,
				\IPS\Request::i()->tab
			) );
		}
	}
	
	/**
	 * Mentions
	 *
	 * @return	void
	 */
	protected function mention()
	{
		$results = '';
		foreach ( \IPS\Db::i()->select( '*', 'core_members', array( "name LIKE CONCAT( ?, '%' )", mb_strtolower( \IPS\Request::i()->input ) ), 'name', 10 ) as $row )
		{
			$results .= \IPS\Theme::i()->getTemplate( 'editor', 'core', 'global' )->mentionRow( \IPS\Member::constructFromData( $row ) );
		}
		
		\IPS\Output::i()->sendOutput( $results );
	}
}