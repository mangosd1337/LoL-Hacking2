<?php
/**
 * @brief		Class for managing RSS documents
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Xml;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Class for managing RSS documents
 */
class _Rss extends SimpleXML
{	
	/**
	 * Create RSS document
	 *
	 * @param	\IPS\Http\Url	$url			URL to document
	 * @param	string			$title			Channel Title
	 * @param	string			$description	Channel Description
	 * @return	void
	 * @see		<a href='http://cyber.law.harvard.edu/rss/languages.html'>Allowable values for language in RSS</a>
	 */
	public static function newDocument( \IPS\Http\Url $url, $title, $description )
	{
		$xml = new static( '<rss version="2.0" />' );
		
		$channel = $xml->addChild( 'channel' );
		$channel->addChild( 'title', $title );
		$channel->addChild( 'link', (string) $url );
		$channel->addChild( 'description', $description );
		
		/* Previously we were regexing and whitelisting language codes for some reason - we should just send the language code always.
			@see https://community.invisionpower.com/4bugtrack/active-reports/4x-problems-with-the-right-alignment-system-rss-and-emails-r7792/ */
		$locale = mb_strtolower( \IPS\Member::loggedIn()->language()->short );
		$locale = mb_substr( $locale, 0, mb_strpos( str_replace( '-', '_', $locale ), '_' ) );
		$channel->addChild( 'language', $locale );
	
		return $xml;
	}
	
	/**
	 * Add Item
	 *
	 * @param	string				$title			Item title
	 * @param	\IPS\Http\Url|NULL	$link			Item link
	 * @param	string|NULL			$description	Item description/content
	 * @param	\IPS\DateTime|NULL	$date			Item date
	 * @param	string				$guid			Item ID
	 * @return	void
	 * @todo	[Future] The feed will validate now, but unrecognized attribute values cause warnings when validating. Also, the validator recommends using an Atom feed with the atom:link attribute.
	 */
	public function addItem( $title = NULL, \IPS\Http\Url $link = NULL, $description = NULL, \IPS\DateTime $date = NULL, $guid = NULL )
	{
		if ( $title === NULL and $description === NULL )
		{
			throw new \InvalidArgumentException;
		}
		
		$item = $this->channel->addChild( 'item' );
		
		if ( $title !== NULL )
		{
			$item->addChild( 'title', $title );
		}
		
		$item->addChild( 'link', $link->rfc3986() );
		
		if ( $description !== NULL )
		{
			$description = preg_replace_callback( "/\s+?(srcset|src)=(['\"])\/\/([^'\"]+?)(['\"])/ims", function( $matches ){
				$baseUrl = parse_url( \IPS\Settings::i()->base_url );
	
				/* Try to preserve http vs https */
				if( isset( $baseUrl['scheme'] ) )
				{
					$url = $baseUrl['scheme'] . '://' . $matches[3];
				}
				else
				{
					$url = 'http://' . $matches[3];
				}
		
				return " {$matches[1]}={$matches[2]}{$url}{$matches[2]}";
			}, $description );
		
			$item->addChild( 'description', $description );
		}
		
		if ( $guid !== NULL )
		{
			$item->addChild( 'guid', $guid )->addAttribute( 'isPermaLink', 'false' );
		}
		
		if ( $date !== NULL )
		{
			$item->addChild( 'pubDate', $date->format('r') );
		}
	}
	
	/**
	 * Get title
	 *
	 * @return	string
	 */
	public function title()
	{
		return $this->channel->title;
	}
	
	/**
	 * Get articles
	 *
	 * @param	mixed	$guidKey	In previous versions, we encoded a key with the GUID. For legacy purposes, this can be passed here.
	 * @return	array
	 */
	public function articles( $guidKey=NULL )
	{
		$articles	= array();
		$items		= $this->getItems();
		foreach ( $items as $item )
		{
			/* In theory this could be an RSS .9 document without any description, we'll just treat this as if there are no articles */
			if( !$item->description )
			{
				continue;
			}

			$link = NULL;
			if ( isset( $item->link ) AND $item->link )
			{
				try
				{
					$link = \IPS\Http\Url::external( $item->link );
				}
				catch ( \Exception $e ) {  }
			}
			
			if ( isset( $item->guid ) )
			{
				$guid = $item->guid;
			}
			else
			{
				$guid = '';
				foreach ( array( 'title', 'link', 'description' ) as $k )
				{
					if ( isset( $item->$k ) )
					{
						$guid .= $item->$k;
					}
				}
				$guid = preg_replace( "#\s|\r|\n#is", "", $guid );
			}
			$guid = md5( $guidKey . $guid );

			$text = (string) $item->description;

			/* If there is a <content:encoded> tag, get the contents of that instead of description */
			if( count( $item->children( 'content', true ) ) AND count( $item->children( 'content', true )->encoded ) )
			{
				$text = (string) $item->children( 'content', true )->encoded[0];
			}

			/* Some feeds may not provide a pubDate for the Item or Channel in the feed. Work out which one exists and if neither do, use the current time */
			$pubDate = $this->getDate( $item );
			
			if ( $pubDate === NULL AND $this->channel->pubDate )
			{
				$pubDate = \IPS\DateTime::ts( strtotime( $this->channel->pubDate ), TRUE );
			}

			$articles[ $guid ] = array(
				'title'		=> ( (string) $item->title ) ?: ( mb_substr( $text, 0, 47 ) . '...' ),
				'content'	=> (string) $text,
				'date'		=> $pubDate ?: \IPS\DateTime::create(),
				'link'		=> $link
			);
		}
		return $articles;
	}

	/**
	 * Fetch the date
	 *
	 * @param	object	$item	RSS item
	 * @return	NULL|\IPS\DateTime
	 */
	protected function getDate( $item )
	{
		$pubDate = NULL;
		if ( $item->pubDate )
		{
			$pubDate = \IPS\DateTime::ts( strtotime( $item->pubDate ), TRUE );
		}

		return $pubDate;
	}

	/**
	 * Fetch the items
	 *
	 * @return	array
	 */
	protected function getItems()
	{
		return $this->channel->item;
	}
}