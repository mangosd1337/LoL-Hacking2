<?php
/**
 * @brief		DOM Parser
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		8 Feb 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Text;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * DOM Parser
 */
class _DOMParser
{
	/**
	 * @brief	Callback to parse a DOMElement object
	 */
	protected $elementParser;
	
	/**
	 * @brief	Callback to parse a DOMText object
	 */
	protected $textParser;
	
	/**
	 * Parse
	 *
	 * @param	string			$value			Contents to parse
	 * @param	callback		$elementParser	Callback to parse a DOMElement object. Is expected to call $parent->appendChild( $element ) after doing any parsing and call $parser->_parseDomNodeList().	function ( \DOMElement $element, \DOMNode $parent, \IPS\Text\DOMParser $parser )
	 * @param	callback|NULL	$textParser		Callback to parse a DOMText object. Is expected to call $parent->appendChild( $element ) after doing any parsing.											function ( \DOMText $textNode, \DOMNode $parent, \IPS\Text\DOMParser $parser )
	 * @retrun	string
	 */
	public static function parse( $value, $elementParser, $textParser = NULL )
	{
		return static::getDocumentBodyContents( ( new static( $elementParser, $textParser ) )->parseValueIntoDocument( $value ) );
	}
	
	/**
	 * Constructor
	 *
	 * @param	string			$value
	 * @param	callback		$elementParser	Callback to parse a DOMElement object. Is expected to call $parent->appendChild( $element ) after doing any parsing and call $parser->_parseDomNodeList().	function ( \DOMElement $element, \DOMNode $parent, \IPS\Text\DOMParser $parser )
	 * @param	callback|NULL	$textParser		Callback to parse a DOMText object. Is expected to call $parent->appendChild( $element ) after doing any parsing.											function ( \DOMText $textNode, \DOMNode $parent, \IPS\Text\DOMParser $parser )
	 * @retrun	string
	 */
	public function __construct( $elementParser, $textParser = NULL )
	{
		$this->elementParser = $elementParser;
		$this->textParser = $textParser;
	}
	
	/**
	 * Parse Value into DOMDocument
	 *
	 * @param	string			$value			Contents to parse
	 * @return	\DOMDocument
	 */
	public function parseValueIntoDocument( $value )
	{
		/* Load the value into a DOMDocument */
		libxml_use_internal_errors(TRUE);
		$source = new \DOMDocument( '1.0', 'UTF-8' );
		$source->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $value );

		/* Create a new DOMDocument which we will move nodes into */
		$document = new \DOMDocument( '1.0', 'UTF-8' );
		
		/* Parse */
		$this->_parseDomNode( $source, $document );
		
		/* Return */
		return $document;
	}
	
	/**
	 * Parse DOMNode
	 *
	 * @param	\DOMNode	$node	The node from the source document to parse
	 * @param	\DOMNode	$parent	The node from the new document which will be this node's parent
	 * @return	void
	 */
	public function _parseDomNode( \DOMNode $node, \DOMNode &$parent )
	{
		switch ( $node->nodeType )
		{
			/* This is the main DOMDocument object and it contains HTML. We just need to loop children */
			case XML_HTML_DOCUMENT_NODE:
				$this->_parseDomNodeList( $node->childNodes, $parent );
				break;
				
			/* This is a HTML element (e.g. <html>, <p>, <a>, etc.) represented as a DOMElement object. Parse it. */
			case XML_ELEMENT_NODE:
				call_user_func( $this->elementParser, $node, $parent, $this );
				break;
						
			/* This is text represented as a DOMText object. Parse it. */
			case XML_TEXT_NODE:
				if ( $this->textParser )
				{
					$function = $this->textParser;
					$function( $node, $parent, $this );
				}
				else
				{
					$parent->appendChild( $parent->ownerDocument->importNode( $node ) );
				}
				break;
				
			/* This is text represented as a DOMCharacterData object, for example, the
				contents of a <script> tag - we just insert it */
			case XML_CDATA_SECTION_NODE:
				$parent->appendChild( $parent->ownerDocument->importNode( $node ) );
				break;
			
			/* These types of nodes are ignored */
			case XML_DOCUMENT_TYPE_NODE:	// DOMDocumentType
			case XML_ATTRIBUTE_NODE:		// DOMAttr
			case XML_ENTITY_REF_NODE:		// DOMEntityReference
			case XML_ENTITY_NODE:			// DOMEntity
			case XML_PI_NODE:				// DOMProcessingInstruction
			case XML_COMMENT_NODE:			// DOMComment
			case XML_DOCUMENT_NODE:			// DOMDocument but not a HTML document
			case XML_DOCUMENT_FRAG_NODE:	// DOMDocumentFragment
			case XML_NOTATION_NODE:			// DOMNotation
			case XML_DTD_NODE:
			case XML_ELEMENT_DECL_NODE:
			case XML_ATTRIBUTE_DECL_NODE:
			case XML_ENTITY_DECL_NODE:
			case XML_NAMESPACE_DECL_NODE:
			default:
				break;				
		}
	}
	
	/**
	 * Loop child nodes of a node and parse them
	 *
	 * @param	\DOMNodeList	$children	The child nodes from the source document
	 * @param	\DOMNode		$parent		The node from the new document which will be the parent of all these nodes
	 * @return	void
	 */
	public function _parseDomNodeList( \DOMNodeList $children, \DOMNode $parent )
	{
		foreach ( $children as $child )
		{
			$this->_parseDomNode( $child, $parent );
		}
	}
	
	/**
	 * Get body contents from document
	 *
	 * @param	\DOMDocument	$document	The document
	 * @return	string
	 */
	public static function getDocumentBody( \DOMDocument $document )
	{
		return $document->getElementsByTagName('body')->item(0);
	}
	
	/**
	 * Get body contents from document
	 *
	 * @param	\DOMDocument	$document	The document
	 * @return	string
	 */
	public static function getDocumentBodyContents( \DOMDocument $document )
	{
		if ( $body = static::getDocumentBody( $document ) )
		{
			return \substr( $document->saveHTML( $body ), 6, -7 );
		}
		else
		{
			return '';
		}
	}
	
}