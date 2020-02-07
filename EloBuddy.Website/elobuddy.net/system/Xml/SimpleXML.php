<?php
/**
 * @brief		Class for managing small XML files
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
 * Class for managing small XML files
 */
class _SimpleXML extends \SimpleXMLElement
{	
	/**
	 * Create New XML Document
	 *
	 * @param	string		$rootElement	Name of Root Element
	 * @param	string|NULL	$xmlns			Namespace
	 * @return	\IPS\Xml\SimpleXML
	 */
	public static function create( $rootElement, $xmlns=NULL )
	{
		$string = $xmlns ? "<?xml version='1.0' encoding='UTF-8'?><{$rootElement} xmlns='{$xmlns}'></{$rootElement}>" : "<?xml version='1.0' encoding='UTF-8'?><{$rootElement}></{$rootElement}>";
		$class = @simplexml_load_string( $string, get_called_class() );
		return $class->subtype();
	}
	
	/**
	 * Load File
	 *
	 * @param	string	$filename	Filename of XML file
	 * @return	\IPS\Xml\SimpleXML
	 * @throws	\InvalidArgumentException
	 */
	public static function loadFile( $filename )
	{
		$class = @simplexml_load_file( $filename, get_called_class() );
		if ( $class === FALSE )
		{
			throw new \InvalidArgumentException;
		}
		return $class->subtype();
	}
	
	/**
	 * Load String
	 *
	 * @param	string	$xml	XML
	 * @return	\IPS\Xml\SimpleXML
	 * @throws	\InvalidArgumentException
	 */
	public static function loadString( $xml )
	{
		$class = @simplexml_load_string( $xml, get_called_class() );
		if ( $class === FALSE )
		{
			throw new \InvalidArgumentException;
		}
		return $class->subtype();
	}
	
	/**
	 * Get appropriate subtype
	 *
	 * @return	\IPS\Xml\SimpleXML
	 */
	protected function subtype()
	{
		if ( get_called_class() === 'IPS\Xml\SimpleXML' )
		{
			if ( $this->getName() === 'rss' )
			{
				return \IPS\Xml\Rss::loadString( $this->asXml() );
			}
			if ( $this->getName() === 'feed' )
			{
				return \IPS\Xml\Atom::loadString( $this->asXml() );
			}
			if ( mb_strtolower( $this->getName() ) === 'rdf' )
			{
				/* Verify this is an RSS 1.0 document */
				if( in_array( 'http://purl.org/rss/1.0/', $this->getNamespaces( true ) ) )
				{
					return \IPS\Xml\RssOne::loadString( $this->asXml() );
				}
			}
		}

		return $this;
	}
	
	/**
	 * Add Child
	 * Modified to support passing other data types (including multi-dimensional arrays and other \IPS\XML\SimpleXML objects) as values
	 *
	 * @see		<a href='http://www.php.net/manual/en/simplexmlelement.addchild.php'>SimpleXMLElement::addChild</a>
	 * @param	string		$name	Element Name
	 * @param	mixed		$value	Value
	 * @param	string|null	$ns		Namespace
	 * @return	void
	 */
	public function addChild( $name, $value=NULL, $ns=NULL )
	{		
		/* Arrays are possibly numerically indexed, which XML won't have */
		if( gettype( $name ) === 'integer' )
		{
			$name = $this->getName();
			
			/* If the name ends in s (e.g. <elements>) - we'll name it's children without the s (e.g. <element>) */
			if( mb_substr( $name, -2 ) === 'es' )
			{
				$name = mb_substr( $name, 0, mb_strlen( $name ) - 2 );
			}
			if( mb_substr( $name, -1 ) === 's' )
			{
				$name = mb_substr( $name, 0, mb_strlen( $name ) - 1 );
			}				
		}
		
		/* If it's not an array, we can just let the default SimpleXML method handle this */
		if( !is_array( $value ) and !( $value instanceof \IPS\Xml\SimpleXML ) )
		{			
			/* Unless it's a boolean value, then we should cast that to an integer */
			if( gettype( $value ) === 'boolean' )
			{
				$value = intval( $value );
			}
			
			/* Needs CDATA? */
			if ( preg_match( '/<|>|&/', $value ) )
			{
				$element = parent::addChild( $name, '', $ns );

				$node = dom_import_simplexml( $element ); 
				$no = $node->ownerDocument; 
				$node->appendChild( $no->createCDATASection( $value ) );
				
				return $element;
			}
			else
			{
				return parent::addChild( $name, $value, $ns );
			}
			
			/* Return */
		}
		/* If it is an array, we need to be a little clever */
		else
		{			
			/* Create an element with a blank value */
			$element = parent::addChild( $name, '', $ns );
			
			/* And loop through each value and rerun this method for each */
			foreach ( $value as $k => $v )
			{
				/* If the value is an XML text element, just get the value */
				if ( $v instanceof \IPS\Xml\SimpleXML and count( $v->children() ) === 0 )
				{
					$v = (string) $v;
				}
				
				/* And add it */
				$element->addChild( $k, $v, $ns );
			}
		}
	}
	
	/**
	 * Remove Node
	 *
	 * @return	void
	 */
	public function removeNode()
	{
		
	}
	
	/**
	 * Format XML in a readable way (normally only used for debugging purposes)
	 *
	 * @return	string
	 */
	public function format()
	{
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		@$dom->loadXML( $this->asXML() );
		return $dom->saveXML();
	}
}