<?php
/**
 * @brief		Text Parser
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Jun 2013
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
 * Text Parser
 */
class _Parser
{
	/* !Parser: Bootstrap */
	
	/**
	 * @brief	If parsing BBCode, the supported BBCode tags
	 */
	protected $bbcode = NULL;
	
	/**
	 * @brief	Attachment IDs
	 */
	protected $attachIds = NULL;
		
	/**
	 * @brief	Rows from core_attachments_map containing attachments which belong to the content being edited - as they are found by the parser, they will be removed so we are left with attachments that have been removed
	 */
	public $existingAttachments = array();
	
	/**
	 * @brief	Attachment IDs
	 */
	public $mappedAttachments = array();
	
	/**
	 * @brief	If parsing BBCode or attachments, the member posting
	 */
	protected $member = NULL;
	
	/**
	 * @brief	If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 */
	protected $area = NULL;
	
	/**
	 * @brief	Loose Profanity Filters
	 */
	protected $looseProfanity = array();
	
	/**
	 * @brief	Exact Profanity Filters
	 */
	protected $exactProfanity = array();
	
	/**
	 * @brief	Case-sensitive Acronyms
	 */
	protected $caseSensitiveAcronyms = array();
	
	/**
	 * @brief	Case-insensitive Acronyms
	 */
	protected $caseInsensitiveAcronyms = array();
	
	/**
	 * @brief	If cleaning HTML, the HTMLPurifier object
	 */
	protected $htmlPurifier = NULL;
				
	/**
	 * Constructor
	 *
	 * @param	bool				$bbcode				Parse BBCode?
	 * @param	array|null			$attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param	\IPS\Member|null	$member				If parsing BBCode or attachments, the member posting. NULL will use currently logged in member.
	 * @param	string|bool			$area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param	bool				$filterProfanity	Remove profanity?
	 * @param	bool				$cleanHtml			If TRUE, HTML will be cleaned through HTMLPurifier
	 * @param	callback			$htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @param	bool				$parseAcronyms		Parse acronyms?
	 * @return	void
	 */
	public function __construct( $bbcode=FALSE, $attachIds=NULL, $member=NULL, $area=FALSE, $filterProfanity=TRUE, $cleanHtml=TRUE, $htmlPurifierConfig=NULL, $parseAcronyms=TRUE )
	{
		/* Set the member and area */
		if ( $bbcode or $attachIds )
		{
			$this->member = $member ?: \IPS\Member::loggedIn();
			$this->area = $area;
		}
		
		/* Get available BBCodes */
		if ( $bbcode )
		{
			$this->bbcode = static::bbcodeTags( $this->member, $this->area );
		}
		
		/* Get attachments */
		$this->attachIds = $attachIds;
		if ( is_array( $attachIds ) )
		{			
			$where = array( array( 'location_key=?', $area ) );
			$i = 1;
			foreach ( $attachIds as $id )
			{
				$where[] = array( "id{$i}=?", $id );
				$i++;
			}
			$this->existingAttachments = iterator_to_array( \IPS\Db::i()->select( '*', 'core_attachments_map', $where )->setKeyField( 'attachment_id' ) );
			$this->mappedAttachments = array_keys( $this->existingAttachments );
		}
						
		/* Get profanity filters */
		if ( $filterProfanity )
		{
			$this->looseProfanity = iterator_to_array( \IPS\Db::i()->select( '*', 'core_profanity_filters', array( 'm_exact=?', FALSE ) )->setKeyField('type')->setValueField('swop') );
			$this->exactProfanity = iterator_to_array( \IPS\Db::i()->select( '*', 'core_profanity_filters', array( 'm_exact=?', TRUE ) )->setKeyField('type')->setValueField('swop') );
		}
		
		/* Get HTMLPurifier Configuration */
		if ( $cleanHtml )
		{
			require_once \IPS\ROOT_PATH . "/system/3rd_party/HTMLPurifier/HTMLPurifier.auto.php";
			$this->htmlPurifier = new \HTMLPurifier( $this->_htmlPurifierConfiguration( $htmlPurifierConfig ) );
		}
				
		/* Get acronyms */
		if ( $parseAcronyms )
		{
			$this->caseSensitiveAcronyms = iterator_to_array( \IPS\Db::i()->select( array( 'a_short', 'a_long' ), 'core_acronyms', array( 'a_casesensitive=1' ) )->setKeyField( 'a_short' )->setValueField( 'a_long' ) );
			
			$this->caseInsensitiveAcronyms = array();
			foreach ( \IPS\Db::i()->select( array( 'a_short', 'a_long' ), 'core_acronyms', array( 'a_casesensitive=0' ) )->setKeyField( 'a_short' )->setValueField( 'a_long' ) as $k => $v )
			{
				$this->caseInsensitiveAcronyms[ mb_strtolower( $k ) ] = $v;
			} 
		}
	}
	
	/**
	 * Parse
	 *
	 * @param	string	$value	HTML to parse
	 * @return	string
	 */
	public function parse( $value )
	{				
		/* Clean HTML */
		if ( $value and $this->htmlPurifier )
		{
			$value = $this->htmlPurifier->purify( $value );
		}
		
		/* BBCode, Profanity, etc. */
		if ( $value )
		{
			$value = $this->_parseContent( $value );
		}
						
		/* Clean HTML */
		if ( $value and $this->htmlPurifier )
		{
			$value = $this->htmlPurifier->purify( $value );
		}
		
		/* Replace any {fileStore.whatever} tags with <fileStore.whatever> */
		$value = $this->replaceFileStoreTags( $value );
		
		/* Return */
		return $value;
	}
	
	/**
	 * Replace {fileStore.xxx} with <fileStore.xxx> 
	 *
	 * @param	string	$value	HTML to parse
	 * @return	string
	 */
	public function replaceFileStoreTags( $value )
	{	
		/* Replace {fileStore.xxx} with <fileStore.xxx> */
		$value = preg_replace( '#<([^>]+?)(srcset)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
		$value = preg_replace( '#<([^>]+?)(href|src)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
		
		/* Replace {___base_url___} with <___base_url___> */
		$value = preg_replace( '#<([^>]+?)(href|src|srcset|data\-fileid|data\-ipshover\-target)=(\'|")%7B___base_url___%7D/#i', '<\1\2=\3<___base_url___>/', $value );
		
		/* Return */
		return $value;
	}
	
	/* !Parser: HTMLPurifier */
	
	/**
	 * Get HTML Purifier Configuration
	 *
	 * @param	callback			$callback	A function which will be passed the HTMLPurifier_Config object to customise it
	 * @return	\HTMLPurifier_Config
	 */
	protected function _htmlPurifierConfiguration( $callback = NULL )
	{
		/* Start with a base configruation */
		$config = \HTMLPurifier_Config::createDefault();

		/* HTMLPurifier by default caches data to disk which we cannot allow. Register our custom
			cache definiton to use \IPS\Data\Store instead */
		$definitionCacheFactory	= \HTMLPurifier_DefinitionCacheFactory::instance();
		$definitionCacheFactory->register( 'IPSCache', "HtmlPurifierDefinitionCache" );
		require_once( \IPS\ROOT_PATH . '/system/Text/HtmlPurifierDefinitionCache.php' );
		$config->set( 'Cache.DefinitionImpl', 'IPSCache' );
		
		/* Allow iFrames from services we allow. We limit this to a whitelist because to allow any iframe would
			open us to phishing and other such security issues */
		$config->set( 'HTML.SafeIframe', true );
		$config->set( 'URI.SafeIframeRegexp', static::safeIframeRegexp() );
		
		/* Set allowed CSS classes.  We limit this to a whitelist because to allow any iframe would open
			us to phishing (for example, someone posts something which, by using our CSS classes, looks like a
			login form), and general annoyances */
		$config->set( 'Attr.AllowedClasses', static::getAllowedCssClasses() );

		/* Callback */
		if ( $callback )
		{
			call_user_func( $callback, $config );
		}
		
		/* HTML Definition */
		$htmlDefinition = $config->getHTMLDefinition( TRUE );
		$this->_htmlPurifierModifyHtmlDefinition( $htmlDefinition );
		
		/* CSS Definition */
		$cssDefinition = $config->getCSSDefinition();
		$this->_htmlPurifierModifyCssDefinition( $cssDefinition );
		
		/* Return */
		return $config;
	}
	
	/**
	 * Customize HTML Purifier HTML Definition
	 *
	 * @param	HTMLPurifier_HTMLDefinition	$def	The definition
	 * @return	void
	 */
	protected function _htmlPurifierModifyHtmlDefinition( \HTMLPurifier_HTMLDefinition $def )
	{
		/* Links (set by _parseAElement) */
		$def->addAttribute( 'a', 'rel', 'Text' );
		
		/* srcset for emoticons (used by _parseImgElement) */
		$def->addAttribute( 'img', 'srcset', new HtmlPurifierSrcsetDef( TRUE ) );
		
		/* Quotes (used by BBCode and ipsquote editor plugin) */
		$def->addAttribute( 'blockquote', 'data-ipsquote', 'Bool' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-timestamp', 'Number' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-username', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentapp', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentclass', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contenttype', 'Text' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentid', 'Number' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-contentcommentid', 'Number' );
		$def->addAttribute( 'blockquote', 'data-ipsquote-userid', 'Number' );
		$def->addAttribute( 'blockquote', 'data-cite', 'Text' );
		
		/* Spoilers (used by BBCode and ipsspoiler editor plugin) */
		$def->addAttribute( 'div', 'data-ipsspoiler', 'Bool' );
		
		/* Mentions (used by BBCode and ipsmentions editor plugin) */
		$def->addAttribute( 'a', 'data-ipshover', new HtmlPurifierSwitchAttrDef( 'a', array( 'data-ipshover-target' ), new \HTMLPurifier_AttrDef_HTML_Bool(''), new \HTMLPurifier_AttrDef_Enum( array() ) ) );
		$def->addAttribute( 'a', 'data-ipshover-target', new HtmlPurifierInternalLinkDef( TRUE, array( array( 'app' => 'core', 'module' => 'members', 'controller' => 'profile', 'do' => 'hovercard' ) ) ) );
		$def->addAttribute( 'a', 'data-mentionid', 'Number' );
		$def->addAttribute( 'a', 'contenteditable', 'Enum#false' );
		
		/* Emoticons (used by the ipsautolink plugin) */
		$def->addAttribute( 'img', 'data-emoticon', 'Bool' ); // Identifies emoticons and stops lightbox running on them
		
		/* Attachments (set by _parseAElement, _parseImgElement and "insert existing attachment") - Gallery/Downloads use the full URL rather than an ID, hence Text */
		$def->addAttribute( 'a', 'data-fileid', 'Text' );
		$def->addAttribute( 'img', 'data-fileid', 'Text' );
		
		/* Existing media (inserted with data-extension by the JS so that _getFile is able to locate) */
		$def->addAttribute( 'img', 'data-extension', 'Text' );
		$def->addAttribute( 'a', 'data-extension', 'Text' );
		
		/* iFrames (used by embeddableMedia) */
		$def->addAttribute( 'iframe', 'data-controller', new \HTMLPurifier_AttrDef_Enum( array( 'core.front.core.autosizeiframe' ) ) ); // used in core/global/embed/oframe.phtml
		$def->addAttribute( 'iframe', 'data-embedcontent', 'Text' ); // used in embeddableMedia
        $def->addAttribute( 'iframe', 'allowfullscreen', 'Text' ); // Some services will specify this property
		
		/* data-controllers */
		$allowedDivDataControllers = array(
			'core.front.core.articlePages', 	// [page] (set by _parseContent)
		);
		if( \IPS\Settings::i()->editor_allowed_datacontrollers )
		{
			$allowedDivDataControllers = array_merge( $allowedDivDataControllers, explode( ',', \IPS\Settings::i()->editor_allowed_datacontrollers ) );
		}
		$def->addAttribute( 'div', 'data-controller', new \HTMLPurifier_AttrDef_Enum( $allowedDivDataControllers, TRUE ) );

		/* [page] (set by _parseContent) */
		$def->addAttribute( 'div', 'data-role', new \HTMLPurifier_AttrDef_Enum( array( 'contentPage' ), TRUE ) );
		$def->addAttribute( 'hr', 'data-role', new \HTMLPurifier_AttrDef_Enum( array( 'contentPageBreak' ), TRUE ) );
		
		/* data-munge-src used by _removeMunge() */
		$def->addAttribute( 'img', 'data-munge-src', 'Text' );
		$def->addAttribute( 'iframe', 'data-munge-src', 'Text' );
	}
	
	/**
	 * Customize HTML Purifier CSS Definition
	 *
	 * @param	HTMLPurifier_HTMLDefinition	$def	The definition
	 * @return	void
	 */
	protected function _htmlPurifierModifyCssDefinition( \HTMLPurifier_CSSDefinition $def )
	{
		/* Do not allow negative margins */
		$margin = $def->info['margin-right'] = $def->info['margin-left'] = $def->info['margin-bottom'] = $def->info['margin-top'] = new \HTMLPurifier_AttrDef_CSS_Composite(
            array(
                new \HTMLPurifier_AttrDef_CSS_Length( 0 ),
                new \HTMLPurifier_AttrDef_CSS_Percentage( TRUE ),
                new \HTMLPurifier_AttrDef_Enum(array('auto'))
            )
        );
        $def->info['margin'] = new \HTMLPurifier_AttrDef_CSS_Multiple( $margin );
        
        /* Don't allow white-space:nowrap */
        $def->info['white-space'] = new \HTMLPurifier_AttrDef_Enum(
            array( 'normal', 'pre', 'pre-wrap', 'pre-line')
        );
	}
	
	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return	array
	 */
	protected static function safeIframeRegexp()
	{
		$return = array();

		/*
			Some local URLs
			Allowed: Any URLs which go through the front-end, e.g.:
				site.com/?app=core&module=system&controller=embed&url=whatever
				site.com/index.php?app=core&module=system&controller=embed&url=whatever
				site.com/topic/1-test/?do=embed
				site.com/index.php?/topic/1-test/?do=embed
				site.com/index.php?app=forums&module=forums&controller=topic&id=1&do=embed
			Not Allowed: Anything which goes to any other endpoint - e.g.:
				site.com/core/interface/file/attachment.php - this would automatically cause files to be downloaded
			Not Allowed: URLs to the open proxy:
				site.com/index.php?app=core&module=system&controller=redirect
		 */
		$return[] = '(https?:)?//' . preg_quote( str_replace( array( 'http://', 'https://' ), '', \IPS\Settings::i()->base_url ), '%' ) . '\/?(\?|index\.php\?|[^\.]*\/\?)((?!(controller|section)=redirect).)*$';		
		$return[] = preg_quote( '%7B___base_url___%7D', '%' ) . '\/?(\?|index\.php\?|[^\.]*\/\?)((?!(controller|section)=redirect).)*$';
		
		/* Other embeddable URLs */
		foreach ( static::allowedIFrameBases() as $base )
		{
			$return[] = '(https?:)?//' . preg_quote( $base, '%' );
		}
					
		/* Return */	
		return '%^(' . implode( '|', $return ) . ')%';
	}
			
	/**
	 * Get URL bases (whout schema) that we'll allow iframes from
	 *
	 * @return	array
	 */
	protected static function allowedIFrameBases()
	{
		$return = array();
				
		/* Our default embed options */		
		$return = array_merge( $return, array(
			'www.youtube.com/embed/',
			'player.vimeo.com/video/',
			'www.hulu.com/embed.html',
			'www.collegehumor.com/e/',
			'embed-ssl.ted.com/',
			'vine.co/v/',
			'gfycat.com/ifr/',
			'embed.spotify.com/'
		) );
		
		/* Extra admin-defined options */
		if ( \IPS\Settings::i()->editor_allowed_iframe_bases )
		{
			$return = array_merge( $return, explode( ',', \IPS\Settings::i()->editor_allowed_iframe_bases ) );
		}
		
		return $return;
	}
	
	/**
	 * Get allowed CSS classes
	 *
	 * @return	array
	 */
	protected function getAllowedCssClasses()
	{		
		/* Init */
		$return = array();
		
		/* Quotes (used by BBCode and ipsquote editor plugin) */
		$return[] = 'ipsQuote';
		$return[] = 'ipsQuote_citation';
		$return[] = 'ipsQuote_contents';
		
		/* Code (used by BBCode and ipscode editor plugin) */
		$return[] = 'ipsCode';
		$return[] = 'prettyprint';
		$return[] = 'prettyprinted';
		$return[] = 'lang-auto';
		$return[] = 'lang-javascript';
		$return[] = 'lang-php';
		$return[] = 'lang-css';
		$return[] = 'lang-html';
		$return[] = 'lang-xml';
		$return[] = 'tag';
		$return[] = 'pln';
		$return[] = 'atn';
		$return[] = 'atv';
		$return[] = 'pun';
		$return[] = 'com';
		$return[] = 'kwd';
		$return[] = 'str';
		$return[] = 'lit';
		
		/* Spoiler (used by BBCode and ipsspoiler editor plugin) */
		$return[] = 'ipsSpoiler';
		$return[] = 'ipsSpoiler_header';
		$return[] = 'ipsSpoiler_contents';
		$return[] = 'ipsStyle_spoiler';
		
		/* Images and attachments (used when attachments are inserted into the editor) */
		$return[] = 'ipsImage';
		$return[] = 'ipsImage_thumbnailed';
		$return[] = 'ipsAttachLink';
		$return[] = 'ipsAttachLink_image';
		$return[] = 'ipsAttachLink_left';
		$return[] = 'ipsAttachLink_right';
		
		/* Embeds (used by various return values of embeddedMedia) */
		$return[] = 'ipsEmbedded';
		$return[] = 'ipsEmbeddedVideo';
		$return[] = 'ipsEmbeddedVideo_limited';
		$return[] = 'ipsEmbeddedOther';

		/* Links (Used to replace disallowed URLs */
		$return[] = 'ipsType_noLinkStyling';
		
		/* Custom */
		if( \IPS\Settings::i()->editor_allowed_classes )
		{
			$return = array_merge( $return, explode( ',', \IPS\Settings::i()->editor_allowed_classes ) );
		}
		
		return $return;
	}
	
	/* !Parser: Main Parser */
	
	/**
	 * @brief	The closing BBCode tags we are looking for and how many are open
	 */
	protected $closeTagsForOpenBBCode = array();
	
	/**
	 * @brief	Open Inline BBCode tags
	 */
	protected $openInlineBBCode = array();
	
	/**
	 * @brief	All open Block-Level BBCode tags
	 */
	protected $openBlockBBCodeByTag = array();
	
	/**
	 * @brief	All open Block-Level BBCode tags in the order they were created
	 */
	protected $openBlockBBCodeInOrder = array();
		
	/**
	 * @brief	Open Block-Level BBCode tags
	 */
	protected $openBlockDepth = NULL;
	
	/**
	 * @brief	This is used to stop BBCode parsing temporarily (such as in [code] tags)
	 */
	protected $bbcodeParse = TRUE;
	
	/**
	 * @brief	If we have opened a BBCode tag which we don't parse other BBCode inside, the string at which we will resume parsing
	 */
	protected $resumeBBCodeParsingOn = NULL;
	
	/**
	 * @brief	Does the content contain [page] tags?
	 */
	protected $containsPageTags = FALSE;
	
	/**
	 * Parse BBCode, Profanity, etc. by loading into a DOMDocument
	 *
	 * @param	string	$value	HTML to parse
	 * @return	string
	 */
	protected function _parseContent( $value )
	{
		/* This fix resolves an issue using <br> mode where BBCode tags are wrapped in P tags like so: <p>[tag]</p><p>Content</p><p>[/tag]</p> tags.
		   The fix just removes the </p><p> tags inside block BBCode tags, so our example ends up parsing like so: <p>[tag]<br><br>Content<br><br>[/tag]</p>
		   We will want to find a more elegant fix for this at some point */
		if ( $this->bbcode !== NULL and ! \IPS\Settings::i()->editor_paragraph_padding )
		{
			$blockTags = array();
			foreach( $this->bbcode as $tag => $data )
			{
				if ( ! empty( $data['block'] ) )
				{
					$blockTags[] = $tag;
				}
			}

			if ( count( $blockTags ) )
			{
				/* If we are inside block tags, ensure that </p> <p> tags are converted to <br> to prevent parser confusion */
				preg_match_all( '#\[(' . implode( '|', $blockTags ) . ')\](.+?)\[/\1\]#si', $value, $matches, PREG_SET_ORDER );
				
				foreach( $matches as $id => $match )
				{
					$value = str_replace( $match[0], preg_replace( '#</p>\s{0,}<p([^>]+?)?'.'>#i', '<br><br>', $match[0] ), $value );
				}
			}
		}

		/* Parse */
		$parser = new DOMParser( array( $this, '_parseDomElement' ), array( $this, '_parseDomText' ) );
		$document = $parser->parseValueIntoDocument( $value );
				
		/* [page] tags need to be handled specially */
		if ( $this->bbcode !== NULL and static::canUse( $this->member, 'Page', $this->area ) and $this->containsPageTags )
		{
			$body = DOMParser::getDocumentBody( $document );
			
			$bodyWithPages = $this->_parseContentWithSeparationTag(
				$body,
				function ( \DOMDocument $document ) {
					$mainDiv = $document->createElement('div');
					$mainDiv->setAttribute( 'data-controller', 'core.front.core.articlePages' );
					return $mainDiv;
				},
				function ( \DOMDocument $document ) {
					$subDiv = $document->createElement('div');
					$subDiv->setAttribute( 'data-role', 'contentPage' );
					$hr = $document->createElement('hr');
					$hr->setAttribute( 'data-role', 'contentPageBreak' );
					$subDiv->appendChild( $hr );
					return $subDiv;
				},
				'[page]'
			);
			
			$newBody = new \DOMElement('body');
			$body->parentNode->replaceChild( $newBody, $body );
			$newBody->appendChild( $bodyWithPages );			
 		}

 		/* Return */
 		return DOMParser::getDocumentBodyContents( $document );

	}
	
	/**
	 * Parse HTML element (e.g. <html>, <p>, <a>, etc.)
	 *
	 * @param	\DOMElement	$element	The element from the source document to parse
	 * @param	\DOMNode	$parent		The node from the new document which will be this node's parent
	 * @return	void
	 */
	public function _parseDomElement( \DOMElement $element, \DOMNode $parent, \IPS\Text\DOMParser $parser )
	{
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtStartOfNode( $parent );
		
		/* Import the element as it is */
		$ownerDocument = $parent->ownerDocument ?: $parent;
		$newElement = $ownerDocument->importNode( $element );
		
		/* Element-specific parsing */
		$this->_parseElement( $newElement );
		
		/* Append */
		$parent->appendChild( $newElement );
		
		/* <pre> tags don't parse BBCode inside */
		$resumeBBCodeAfterPre = FALSE;
		if ( $newElement->tagName == 'pre' and $this->bbcodeParse )
		{
			$this->bbcodeParse = FALSE;
			$resumeBBCodeAfterPre = TRUE;
		}
		
		/* Loop children */
		$parser->_parseDomNodeList( $element->childNodes, $newElement );
		
		/* <pre> tags don't parse BBCode inside */
		if ( $newElement->tagName == 'pre' and $resumeBBCodeAfterPre )
		{
			$this->bbcodeParse = TRUE;
		}
		
		/* If we did have children, but now we don't (for example, the entire content is a block-level BBCode), drop this element to avoid unintentional whitespace */
		if ( $newElement->parentNode and $element->childNodes->length and !$newElement->childNodes->length )
		{
			$parent->removeChild( $newElement );
		}
		
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtEndOfNode( $parent );
	}
		
	/**
	 * Parse Text
	 *
	 * @param	\DOMText	$textNode	The text from the source document to parse
	 * @param	\DOMNode	$parent		The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @return	void
	 */
	public function _parseDomText( \DOMText $textNode, \DOMNode &$parent, \IPS\Text\DOMParser $parser )
	{		
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtStartOfNode( $parent );
				
		/* Init */
		$text = $textNode->wholeText;
		$breakPoints = array();
		
		/* Contains [page] tags? */
		if ( mb_strpos( $text, '[page]' ) !== FALSE )
		{
			$this->containsPageTags = TRUE;
		}
		
		/* If we are parsing BBCode, we will look for opening (e.g. "[foo=bar]") and closing (e.g. "[/foo]") tags */
		if ( $this->bbcode !== NULL and count( $this->bbcode ) )
		{
			/* First, if we have any single-tag BBCodes (e.g. "[img=URL]") expressed as normal BBCodes (e.g. "[img]URL[/img]") - fix that */
			foreach ( $this->bbcode as $tag => $bbcode )
			{
				if ( isset( $bbcode['single'] ) and $bbcode['single'] )
				{
					if ( isset( $bbcode['attributes'] ) and in_array( '{option}', $bbcode['attributes'] ) )
					{
						$text = preg_replace( '/\[(' . preg_quote( $tag, '/' ) . ')\](.+?)\[\/' . preg_quote( $tag, '/' ) . '\]/i', '[$1=$2]', $text );
					}
					else
					{
						$text = preg_replace( '/\[(' . preg_quote( $tag, '/' ) . ')\]\s*\[\/' . preg_quote( $tag, '/' ) . '\]/i', '[$1]', $text );
					}
				}
			}
			
			/* And add our regex to the breakpoints */
			$breakPoints[] = '(\[\/?(?:' . implode( '|', array_map( function ( $value ) { return preg_quote( $value, '/' ); }, array_keys( $this->bbcode ) ) ) . ')(?:[=\s].+?)?\])';
		}
		
		/* If we have any acronyms, they also need to be breakpoints */
		if ( count( $this->caseSensitiveAcronyms ) or count( $this->caseInsensitiveAcronyms ) )
		{
			$breakPoints[] = '((?=<^|\b)(?:' . implode( '|', array_merge( array_map( function ( $value ) { return preg_quote( $value, '/' ); }, array_keys( $this->caseSensitiveAcronyms ) ), array_map( function ( $value ) { return preg_quote( $value, '/' ); }, array_keys( $this->caseInsensitiveAcronyms ) ) ) ) . ')(?=\b|$))';
		}
						
		/* Loop through each section */
		if ( count( $breakPoints ) )
		{
			$sections = array_values( array_filter( preg_split( '/' . implode( '|', $breakPoints ) . '/iu', $text, null, PREG_SPLIT_DELIM_CAPTURE ), function( $val ) { return $val !== ''; } ) );
			foreach( $sections as $sectionId => $section )
			{
				$this->_parseTextSection( $section, $parent, ++$sectionId, count( $sections ) );
			}		
		}
		else
		{
			$this->_parseTextSection( $textNode->wholeText, $parent, 1, 1 );
		}
				
		/* Adjust parent for block BBCode */
		$this->_adjustParentForBlockBBCodeAtEndOfNode( $parent );
	}
	
	/**
	 * Parse a section of text after it has been split into relevant sections
	 *
	 * @param	string		$section		The text from the source document to parse
	 * @param	\DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param	int			$sectionId		The position of this section out of all the sections in the node - used to indicate if there's text before/after this section
	 * @param	int			$sectionCount	The total number of sections in the node - used to indicate if there's text before/after this section
	 * @return	void
	 */
	protected function _parseTextSection( $section, \DOMNode &$parent, $sectionId, $sectionCount )
	{		
		/* If it's empty, skip it */
		if ( $section === '' )
		{
			return;
		}
								
		/* If this restarts parsing, do that */
		if ( $section == $this->resumeBBCodeParsingOn )
		{
			$this->bbcodeParse = TRUE;
			$this->resumeBBCodeParsingOn = NULL;
		}
		
		/* Start of BBCode tag? */
		if ( $this->bbcode !== NULL and $this->bbcodeParse and preg_match( '/^\[([a-z\*]+?)(?:([=\s])(.+?))?\]$/i', $section, $matches ) and array_key_exists( mb_strtolower( $matches[1] ), $this->bbcode ) )
		{
			/* What was the option? */
			$option = NULL;
			if ( isset( $matches[3] ) )
			{
				$option = $matches[3];
				
				/* If it's [foo="bar"] then we strip the quotes, (if it's [foo bar="baz"] then we don't) */
				if ( !preg_match( '/^\s*$/', $matches[2] ) )
				{
					$option = trim( $option, '"\'' );
				}
			}
			
			/* Send to _openBBcode */
			$this->_openBBCode( mb_strtolower( $matches[1] ), $option, $parent, $sectionId, $sectionCount );
		}
		
		/* End of BBCode tag? */
		elseif ( $this->bbcodeParse and array_key_exists( mb_strtolower( $section ), $this->closeTagsForOpenBBCode ) )
		{
			$this->_closeBBCode( mb_substr( mb_strtolower( $section ), 2, -1 ), $parent, $sectionId, $sectionCount );
		}
		
		/* Normal text */
		else
		{	
			/* HTMLPurifier will strip carrage returns, but if HTML posting is enabled this doesn't happen which
				leaves blank spaces - so we need to strip here */
			if ( !$this->htmlPurifier )
			{
				$section = str_replace( "\r", '', $section );
			}
			
			/* Profanity */
			foreach ( $this->exactProfanity as $bad => $good )
			{
				$section = preg_replace( '/(?=<^|\b)(' . preg_quote( $bad, '/' ) . ')(?=\b|$)/iu', $good, $section );
			}
			$section = str_ireplace( array_keys( $this->looseProfanity ), array_values( $this->looseProfanity ), $section );
			
			/* Note what $parent is */
			$originalParent = $parent;
						
			/* Acronym? */
			if ( array_key_exists( $section, $this->caseSensitiveAcronyms ) )
			{
				$parent = $parent->appendChild( new \DOMElement( 'abbr' ) );
				$parent->setAttribute( 'title', $this->caseSensitiveAcronyms[ $section ] );
			}
			elseif ( array_key_exists( mb_strtolower( $section ), $this->caseInsensitiveAcronyms ) )
			{
				$parent = $parent->appendChild( new \DOMElement( 'abbr' ) );
				$parent->setAttribute( 'title', $this->caseInsensitiveAcronyms[ mb_strtolower( $section ) ] );
			}
			
			/* Insert the text */
			$this->_insertNodeApplyingInlineBBcode( new \DOMText( $section ), $parent );
			
			/* Restore the parent */
			$parent = $originalParent;
		}
	}
	
	/**
	 * Open BBCode tag
	 *
	 * @param	string		$tag			The tag (e.g. "b")
	 * @param	string|NULL	$option			If an option was provided (e.g. "[foo=bar]"), it's value
	 * @param	\DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param	int			$sectionId		The position of this section out of all the sections in the node - used to indicate if there's text before/after this section
	 * @param	int			$sectionCount	The total number of sections in the node - used to indicate if there's text before/after this section
	 * @return	void
	 */
	protected function _openBBCode( $tag, $option, \DOMNode &$parent, $sectionId, $sectionCount )
	{
		/* Get definiton */
		$bbcode = $this->bbcode[ $tag ];
		
		/* Get the document */
		$document = $parent->ownerDocument ?: $parent;
				
		/* Create the element */
		$bbcodeElement = $document->createElement( $bbcode['tag'] );
		
		/* Add any attributes */
		if ( isset( $bbcode['attributes'] ) )
		{
			foreach ( $bbcode['attributes'] as $k => $v )
			{				
				$bbcodeElement->setAttribute( $k, str_replace( '{option}', ( $option ?: ( isset( $bbcode['defaultOption'] ) ? $bbcode['defaultOption'] : '' ) ), $v ) );
			}
		}
		
		/* Callback */
		if ( isset( $bbcode['callback'] ) )
		{
			$bbcodeElement = call_user_func( $bbcode['callback'], $bbcodeElement, array( 2 => $option ), $document );
		}
				
		/* Stop parsing? ([code] blocks make it so BBCode isn't parsed inside them) */
		if ( isset( $bbcode['noParse'] ) and $bbcode['noParse'] )
		{
			$this->bbcodeParse = FALSE;
			$this->resumeBBCodeParsingOn = "[/{$tag}]";
		}
		
		/* Parse it */
		$this->_parseElement( $bbcodeElement );
		
		/* Single only? */
		if ( isset( $bbcode['single'] ) and $bbcode['single'] )
		{			
			$this->_insertNodeApplyingInlineBBcode( $bbcodeElement, $parent );
		}
		
		/* Or with content? */
		else
		{
			/* Block level? */
			if ( isset( $bbcode['block'] ) and $bbcode['block'] )
			{
				/* Insert the block level element */
				$lastOpennedBlockId = NULL;
				if ( !empty( $this->openBlockBBCodeInOrder ) )
				{
					$openBBCodeBlocks = array_keys( $this->openBlockBBCodeInOrder );
					$lastOpennedBlockId = array_pop( $openBBCodeBlocks );
				}
				if ( $lastOpennedBlockId and list( $id, $tagName ) = explode( '-', $lastOpennedBlockId ) and isset( $this->bbcode[ $tagName ]['noChildren'] ) and $this->bbcode[ $tagName ]['noChildren'] )
				{
					$parent->appendChild( $bbcodeElement );
				}
				else
				{
					$parent->parentNode->appendChild( $bbcodeElement );
				}
				
				/* Callback */
				$blockElement = $bbcodeElement;
				if ( isset( $bbcode['getBlockContentElement'] ) )
				{
					$blockElement = call_user_func( $bbcode['getBlockContentElement'], $bbcodeElement );
				}
				
				/* Create an element of the same type (normally <p>) to go in the block-level element for any content left (e.g. "<p>[center]This needs to be centered</p>") and set the parent being used to it */
				if ( $sectionId != $sectionCount )
				{
					if ( !isset( $bbcode['noChildren'] ) or !$bbcode['noChildren'] )
					{
						$contentElement = $parent->cloneNode( FALSE );
						$blockElement->appendChild( $contentElement );
						$parent = $contentElement;
					}
					else
					{
						$parent = $bbcodeElement;
					}
				}
	
				/* Add to $openBlockBBcode for closing later */
				$id = uniqid() . '-' . $tag;
				$this->openBlockBBCodeByTag[ $tag ][ $id ] = $bbcodeElement;
				
				/* Add to $penBlockBBCodeInOrder to that so _parseDomElement() will use that as the parent for subsequent elements */
				$this->openBlockBBCodeInOrder[ $id ] = $blockElement;
			}
			
			/* Inline */
			else
			{
				$this->openInlineBBCode[ $tag ][] = $bbcodeElement;
			}
						
			/* Add it to the array */
			if ( !isset( $this->closeTagsForOpenBBCode[ "[/{$tag}]" ] ) )
			{
				$this->closeTagsForOpenBBCode[ "[/{$tag}]" ] = 0;
			}
			$this->closeTagsForOpenBBCode[ "[/{$tag}]" ]++;
		
		}
	}
		
	/**
	 * Close BBCode tag
	 *
	 * @param	string		$tag			The tag (e.g. "b")
	 * @param	\DOMNode	$parent			The node from the new document which will be this node's parent - passed by reference and may be modified for siblings
	 * @param	int			$sectionId		The position of this section out of all the sections in the node - used to indicate if there's text before/after this section
	 * @param	int			$sectionCount	The total number of sections in the node - used to indicate if there's text before/after this section
	 * @return	void
	 */
	protected function _closeBBCode( $tag, \DOMNode &$parent, $sectionId, $sectionCount )
	{
		/* Get definition */
		$bbcode = $this->bbcode[ $tag ];
		
		/* Block level? */
		if ( isset( $bbcode['block'] ) and $bbcode['block'] )
		{
			/* Find the block we're closing */
			foreach ( $this->openBlockBBCodeByTag[ $tag ] as $key => $block ) { } // Just sets $key and $block for the last one
						
			/* Create a content element to go after the block-level element for any remaining text in this DOMText node (e.g. "<p>[/center]This should not be centered</p>") and set the parent being used to it */
			if ( $block->previousSibling and $block->previousSibling instanceof \DOMText ) // Happens for noChildren tags - e.g. "[list]Foo[list]Bar[/list]Baz[/list]"
			{
				$parent = $block->parentNode;
			}
			else
			{
				if ( $block->previousSibling )
				{
					$contentElement = $block->previousSibling->cloneNode( FALSE );
				}
				else
				{
					$contentElement = $parent->ownerDocument->createElement('p');
				}
				$block->parentNode->appendChild( $contentElement );
				$parent = $contentElement;
			}
			
			/* Remove it from the list of open blocks */
			unset( $this->openBlockBBCodeByTag[ $tag ][ $key ] );
			unset( $this->openBlockBBCodeInOrder[ $key ] );
			
			/* Finished callback? */
			if ( isset( $bbcode['finishedCallback'] ) and $bbcode['finishedCallback'] )
			{		
				$newBlock = call_user_func( $bbcode['finishedCallback'], $block );
				if ( $block->parentNode )
				{
					$block->parentNode->replaceChild( $newBlock, $block );
				}
				else
				{
					$parent->ownerDocument->getElementsByTagName('body')->item(0)->appendChild( $newBlock );
				}
			}
		}
		
		/* Inline */
		else
		{
			array_pop( $this->openInlineBBCode[ $tag ] );
			if ( empty( $this->openInlineBBCode[ $tag ] ) )
			{
				unset( $this->openInlineBBCode[ $tag ] );
			}
		}
		
		/* Remove from array of open BBCodes */
		$this->closeTagsForOpenBBCode["[/{$tag}]"]--;
		if ( !$this->closeTagsForOpenBBCode["[/{$tag}]"] )
		{
			unset( $this->closeTagsForOpenBBCode["[/{$tag}]"]  );
		}
	}
	
	/**
	 * Insert a node to a parent while applying inline BBCode 
	 *
	 * @param	\DOMNode	$node	Node to insert
	 * @param	\DOMNode	$parent	Parent to insert into
	 * @return	void
	 */
	protected function _insertNodeApplyingInlineBBcode( \DOMNode $node, \DOMNode $parent )
	{
		/* Apply any open inline BBCode elements */
		if ( $this->bbcodeParse )
		{
			foreach ( $this->openInlineBBCode as $tag => $elements )
			{
				foreach ( $elements as $bbcodeElement )
				{
					$parent = $parent->appendChild( $bbcodeElement->cloneNode( TRUE ) );
				}
			}
		}
		
		/* Insert the text */
		$parent->appendChild( $node );
	}
	
	/**
	 * Adjust for Block-Level BBCode if necessary at start of the node
	 *
	 * @param	\DOMNode	$parent		The node from the new document which will be the working node's parent. Passed by reference and will be modified if there is an open block-level BBCode
	 * @return	void
	 */
	protected function _adjustParentForBlockBBCodeAtStartOfNode( \DOMNode &$parent )
	{
		/* If we have an open block-level BBCode element, and we're not already on a child
			of one we have already moved, insert this element into that instead of the
			defined parent */
		if ( count( $this->openBlockBBCodeInOrder ) )
		{
			if ( !$this->openBlockDepth )
			{
				$openBlocks = $this->openBlockBBCodeInOrder;
				$parent = array_pop( $openBlocks );
			}
			$this->openBlockDepth++;
		}
	}
	
	/**
	 * Adjust for Block-Level BBCode if necessary at end of the node
	 *
	 * @return	void
	 */
	protected function _adjustParentForBlockBBCodeAtEndOfNode()
	{
		/* If we have an open block-level BBCode element, decrease the depth we're at */
		if ( $this->openBlockDepth )
		{
			$this->openBlockDepth--;
		}
	}
	
	/* !Parser: Element-Specific Parsing */
	
	/**
	 * Element-Specific Parsing
	 *
	 * @param	\DOMElement	$element	The element
	 * @return	void
	 */
	protected function _parseElement( \DOMElement $element )
	{
		/* Element-Specific */
		switch ( $element->tagName )
		{
			case 'a':
				$this->_parseAElement( $element );
				break;
				
			case 'img':
				$this->_parseImgElement( $element );
				break;
				
			case 'iframe':
				$this->_parseIframeElement( $element );
				break;
		}
		
		/* Anything which has a URL may need swapping out */
		foreach ( array( 'href', 'src', 'srcset', 'data-ipshover-target', 'data-fileid', 'cite', 'action', 'longdesc', 'usemap', 'poster' ) as $attribute )
		{
			if ( $element->hasAttribute( $attribute ) )
			{
				if ( preg_match( '#^(' . preg_quote( rtrim( \IPS\Settings::i()->base_url, '/' ), '#' ) . ')/(.+?)$#', $element->getAttribute( $attribute ), $matches ) )
				{
					$element->setAttribute( $attribute, '%7B___base_url___%7D/' . $matches[2] );
				}
			}
		}
		foreach ( array( 'srcset', 'style' ) as $attribute )
		{
			if ( $element->hasAttribute( $attribute ) )
			{
				if ( mb_strpos( $element->getAttribute( $attribute ), \IPS\Settings::i()->base_url ) )
				{
					$element->setAttribute( $attribute, str_replace( \IPS\Settings::i()->base_url, '%7B___base_url___%7D', $element->getAttribute( $attribute ) ) );
				}
			}
		}
	}
	
	/**
	 * Parse <a> element
	 *
	 * @param	\DOMElement	$element	The element
	 * @return	void
	 */
	protected function _parseAElement( \DOMElement $element )
	{
		/* If it's not allowed, remove the href */
		if ( !$this->isAllowedUrl( $element->getAttribute('href') ) )
		{
			$element->removeAttribute( 'href' );
			$element->setAttribute( 'class', 'ipsType_noLinkStyling' );
			return;
		}
				
		/* Attachment? */
		if ( $attachment = $this->_getAttachment( $element->getAttribute('href') ) )
		{
			$element->setAttribute( 'data-fileid', $attachment['attach_id'] );
			$element->setAttribute( 'href', str_replace( static::$fileObjectClasses['core_Attachment']->baseUrl(), '{fileStore.core_Attachment}', $element->getAttribute('href') ) );
			
			$this->_logAttachment( $attachment );
		}
		
		/* Some other media? */
		elseif ( $element->getAttribute('data-extension') and $file = $this->_getFile( $element->getAttribute('data-extension'), $element->getAttribute('href') ) )
		{
			$element->setAttribute( 'href', '{fileStore.' . $file->storageExtension . '}/' . (string) $file );
		}
		
		/* Add rels */
		$element->setAttribute( 'rel', implode( ' ', $this->_getRelAttributes( new \IPS\Http\Url( $element->getAttribute('href') ) ) ) );
	}
	
	/**
	 * Parse <img> element
	 *
	 * @param	\DOMElement	$element	The element
	 * @return	bool
	 */
	protected function _parseImgElement( \DOMElement $element )
	{
		/* When editing content in the AdminCP, images and iframes get the src munged. When we save, we need to put that back */
		$this->_removeMunge( $element );

		/* If it's not allowed, remove the src */
		if ( !$this->isAllowedUrl( $element->getAttribute('src') ) )
		{
			$element->removeAttribute('src');
			return;
		}

		/* Is it an emoticon? */
		if ( $emoticon = $this->_getEmoticon( $element->getAttribute('src') ) )
		{
			$element->setAttribute( 'src', str_replace( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '{fileStore.core_Emoticons}', $element->getAttribute('src') ) );
			$element->setAttribute( 'alt', $emoticon['typed'] );
			
			if( $emoticon['image_2x'] and $emoticon['width'] and $emoticon['height'] )
			{
				$element->setAttribute( 'srcset', str_replace( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '%7BfileStore.core_Emoticons%7D', $emoticon['image_2x'] ) );
				$element->setAttribute( 'width', $emoticon['width'] );
				$element->setAttribute( 'height', $emoticon['height'] );
			}
		}
		
		/* Or an attachment? */
		elseif ( $attachment = $this->_getAttachment( $element->getAttribute('src') ) )
		{
			$element->setAttribute( 'data-fileid', $attachment['attach_id'] );
			$element->setAttribute( 'src', str_replace( static::$fileObjectClasses['core_Attachment']->baseUrl(), '{fileStore.core_Attachment}', $element->getAttribute('src') ) );
			$element->setAttribute( 'alt', $attachment['attach_file'] );
			
			$this->_logAttachment( $attachment );
		}
		
		/* Or some other media? */
		elseif ( $element->getAttribute('data-extension') and $file = $this->_getFile( $element->getAttribute('data-extension'), $element->getAttribute('src') ) )
		{
			$element->setAttribute( 'src', '{fileStore.' . $file->storageExtension . '}/' . (string) $file );
			$element->setAttribute( 'alt', $file->originalFilename );
		}
		
		/* Nope, regular image */
		else
		{
			/* We need an alt (HTMLPurifier handles this normally, but it may not always run) */
			if ( !$element->getAttribute('alt') )
			{
				$element->setAttribute( 'alt', mb_substr( basename( $element->getAttribute('src') ), 0, 40 ) );
			}
		
			/* Image proxy */
			if ( \IPS\Settings::i()->remote_image_proxy or !\IPS\Settings::i()->allow_remote_images )
			{
				$imageSrc = new \IPS\Http\Url( $element->getAttribute('src') );
				if ( !$imageSrc->isInternal and !$imageSrc->isLocalhost() ) // We don't use the image proxy for internal resources as it may cause protected resources to be exposed because the server thinks it's an internal request
				{
					if ( !\IPS\Settings::i()->allow_remote_images )
					{
						$element->setAttribute( 'src', '' );
					}
					else
					{					
						$newUrl = new \IPS\Http\Url( \IPS\Settings::i()->base_url . "applications/core/interface/imageproxy/imageproxy.php" );
						$newUrl = $newUrl->setQueryString( array(
							'img' 	=> (string) $imageSrc,
							'key'	=> hash_hmac( "sha256", (string) $imageSrc, \IPS\SITE_SECRET_KEY ?: md5( \IPS\Settings::i()->sql_pass . \IPS\Settings::i()->board_url . \IPS\Settings::i()->sql_database ) )
						) );
		
						$element->setAttribute( 'src', (string) $newUrl );
					}
				}
			}
		}
	}
	
	/**
	 * Parse <iframe> element
	 *
	 * @param	\DOMElement	$element	The element
	 * @return	bool
	 */
	protected function _parseIframeElement( \DOMElement $element )
	{
		$this->_removeMunge( $element );
	}
	
	/**
	 * When editing content in the AdminCP, images and iframes get the src munged. When we save, we need to put that back
	 *
	 * @param	\DOMElement	$element	The element
	 * @return	bool
	 */
	protected function _removeMunge( \DOMElement $element )
	{
		if ( $originalUrl = $element->getAttribute('data-munge-src') )
		{
			$element->removeAttribute( 'src' );
			$element->setAttribute( 'src', $originalUrl );
			$element->removeAttribute( 'data-munge-src' );
		}
	}
	
	/* !Parser: Element-Specific Parsing: URLs */
	
	/**
	 * Get "rel" attribute values for a URL
	 *
	 * @param	\IPS\Http\Url	$url	The URL
	 * @return	array
	 */
	protected function _getRelAttributes( \IPS\Http\Url $url )
	{
		$rels = array();
		
		/* norewrite */
		if ( \IPS\Settings::i()->viglink_norewrite and $this->member->inGroup( explode( ',', \IPS\Settings::i()->viglink_norewrite ) ) )
		{
			$rels[] = 'norewrite';
		}
		
		/* external / nofollow */
		if ( !$url->isInternal )
		{
			$rels[] = 'external';
			
			/* Do we also want to add nofollow? */
			if( \IPS\Settings::i()->posts_add_nofollow and ( !\IPS\Settings::i()->posts_add_nofollow_exclude or ( isset( $url->data['host'] ) and !in_array( preg_replace( '/^www\./', '', $url->data['host'] ), array_map( function( $val ) {
				return preg_replace( '/^www\./', '', $val );
			}, json_decode( \IPS\Settings::i()->posts_add_nofollow_exclude ) ) ) ) ) )
			{
				$rels[] = 'nofollow';
			}
		}
		
		return $rels;
	}
	
	/**
	 * Is allowed URL
	 *
	 * @param	string	$url	The URL
	 * @return	bool
	 */
	public function isAllowedUrl( $url )
	{
		if ( \IPS\Settings::i()->ipb_url_filter_option != 'none' )
		{
			$links = \IPS\Settings::i()->ipb_url_filter_option == "black" ? \IPS\Settings::i()->ipb_url_blacklist : \IPS\Settings::i()->ipb_url_whitelist;
	
			if( $links )
			{
				$linkValues = array();
				$linkValues = explode( "," , $links );
	
				if( \IPS\Settings::i()->ipb_url_filter_option == 'white' )
				{
					$linkValues[]	= "http://" . parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST ) . "/*";
					$linkValues[]	= "https://" . parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST ) . "/*";
				}
	
				if ( !empty( $linkValues ) )
				{
					$goodUrl = FALSE;
	
					foreach( $linkValues as $link )
					{
						if( !trim($link) )
						{
							continue;
						}
	
						$link = preg_quote( $link, '/' );
						$link = str_replace( '\*', "(.*?)", $link );
	
						if ( \IPS\Settings::i()->ipb_url_filter_option == "black" )
						{
							if( preg_match( '/' . $link . '/i', $url ) )
							{
								return false;
							}
						}
						else
						{
							if ( preg_match( '/' . $link . '/i', $url ) )
							{
								$goodUrl = TRUE;
							}
						}
					}
	
					if ( ! $goodUrl AND \IPS\Settings::i()->ipb_url_filter_option == "white" )
					{
						return false;
					}
				}
			}
		}
	
		return true;
	}
	
	/* !Parser: Element-Specific Parsing: File System */
	
	/**
	 * @brief	Stored file object classes
	 */
	protected static $fileObjectClasses = array();
	
	/**
	 * Get emoticon data from URL
	 *
	 * @param	string	$url	The URL
	 * @return	array|NULL
	 */
	protected function _getEmoticon( $url )
	{
		if ( !isset( static::$fileObjectClasses['core_Emoticons'] ) )
		{
			static::$fileObjectClasses['core_Emoticons'] = \IPS\File::getClass('core_Emoticons' );
		}
				
		if ( preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
		{
			foreach ( \IPS\Helpers\Form\Editor::getEmoticons() as $key => $emoticon )
			{
				if ( $emoticon['image'] == $url )
				{
					$emoticon['typed'] = $key;
					return $emoticon;
				}
			}
		}
		
		return NULL;
	}
	
	/**
	 * Get attachment data from URL
	 *
	 * @param	string	$url	The URL
	 * @return	array|NULL
	 */
	protected function _getAttachment( $url )
	{
		/* We need the storage extension */
		if ( !isset( static::$fileObjectClasses['core_Attachment'] ) )
		{
			static::$fileObjectClasses['core_Attachment'] = \IPS\File::getClass('core_Attachment' );
		}
		
		/* If it's URL to applications/core/interface/file/attachment.php, it's definitely an attachment */
		if ( preg_match( '#^(?:http:|https:)?' . preg_quote( rtrim( str_replace( array( 'http://', 'https://' ), '//', \IPS\Settings::i()->base_url ), '/' ), '#' ) . '/applications/core/interface/file/attachment\.php\?id=(\d+)$#', $url, $matches ) )
		{
			try
			{
				return \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $matches[1] ) )->first();
			}
			catch ( \UnderflowException $e ) { }
		}
		
		/* Otherwise, we need to see if it matches the actual attachment storage URL */
		if ( preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Attachment']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
		{
			try
			{
				return \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_location=? OR attach_thumb_location=?', $matches[2], $matches[2] ) )->first();
			}
			catch ( \UnderflowException $e ) { }
		}
		
		/* Nope, not an attachment */
		return NULL;
	}
	
	/**
	 * Log that at attachment is being used in the content
	 *
	 * @param	array	$attachment	Attachment data
	 * @return	void
	 */
	protected function _logAttachment( $attachment )
	{
		if ( isset( $this->existingAttachments[ $attachment['attach_id'] ] ) )
		{
			unset( $this->existingAttachments[ $attachment['attach_id'] ] );
		}
		elseif ( $attachment['attach_member_id'] === $this->member->member_id and !in_array( $attachment['attach_id'], $this->mappedAttachments ) )
		{
			\IPS\Db::i()->replace( 'core_attachments_map', array(
				'attachment_id'	=> $attachment['attach_id'],
				'location_key'	=> $this->area,
				'id1'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[0] ) ) ? $this->attachIds[0] : NULL,
				'id2'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[1] ) ) ? $this->attachIds[1] : NULL,
				'id3'			=> ( is_array( $this->attachIds ) and isset( $this->attachIds[2] ) ) ? $this->attachIds[2] : NULL,
				'temp'			=> is_string( $this->attachIds ) ? $this->attachIds : NULL
			) );
			
			$this->mappedAttachments[] = $attachment['attach_id'];
		}
	}
	
	/**
	 * Get file data
	 *
	 * @param	string	$extension	The extension
	 * @param	string	$url		The URL
	 * @return	\IPS\File|NULL
	 */
	protected function _getFile( $extension, $url )
	{
		if ( !isset( static::$fileObjectClasses[ $extension ] ) )
		{
			static::$fileObjectClasses[ $extension ] = \IPS\File::getClass( $extension );
		}
		
		if ( preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses[ $extension ]->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $url, $matches ) )
		{
			return \IPS\File::get( $extension, $url );
		}
		
		return NULL;
	}
	
	/* !BBCode */
	
	/**
	 * Get BBCode Tags
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string|bool	$area	The Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @code
	 	return array(
	 		'font'	=> array(																	// Key represents the BBCode tag (e.g. [font])
		 		'tag'			=> 'span',															// The HTML tag to use
		 		'attributes'	=> array( ... )														// Key/Value pairs of attributes to use (optional) - can use {option} to get the [tag=option] value
		 		'defaultOption'	=> '..',															// Value to use for {option} if one isn't specified
		 		'block'			=> FALSE,															// If this is a block-level tag (optional, default false)
		 		'single'		=> FALSE,															// If this is a single tag, with no content (optional, default false)
		 		'noParse'		=> FALSE,															// If other BBCode shouldn't be parsed inside (optional, default false)
		 		'noChildren'	=> FALSE,															// If it is not appropriate for this element to have child elements (for example <pre> can't have <p>s inside) (optional, default false)
		 		'callback'		=> function( \DOMElement $node, $matches, \DOMDocument $document )	// A callback to modify the DOMNode object (optional)
		 		{
		 			...
		 			return $node;
		 		},
		 		'getBlockContentElement' => function( \DOMElement $node )							// If the callback modifies, an additional callback can be specified to provide the node which children should go into (for example, spoilers have a header, and we want children to go into the content body) (optional)
		 		{
			 		...
			 		return $node;
			 	},
		 		'finishedCallback'	=> function( \DOMElement $originalNode )						// A callback which is ran after all children have been parsed for any additional parsing (optional)
		 		{
			 		...
			 		return $node;
			 	},
	 	)
	 * @endcode
	 * @return	array
	 */
	public function bbcodeTags( \IPS\Member $member, $area )
	{
		$return = array();
		
		/* Acronym */
		$return['acronym'] = array( 'tag' => 'abbr', 'attributes' => array( 'title' => '{option}' ) );
		
		/* Background */
		if ( static::canUse( $member, 'BGColor', $area ) )
		{
			$return['background'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'background-color:{option}' ) );
		}
		
		/* Bold */
		if ( static::canUse( $member, 'Bold', $area ) )
		{
			$return['b'] = array( 'tag' => 'strong' );
		}
		
		/* Code */
		if ( static::canUse( $member, 'ipsCode', $area ) )
		{
			$code = array( 'tag' => 'pre', 'attributes' => array( 'class' => 'ipsCode' ), 'block' => TRUE, 'noParse' => TRUE, 'noChildren' => TRUE, 'finishedCallback' => function( \DOMElement $originalNode ) {
								
				/* Parse breaks - with BBCode we'll be getting things like [code]line1<br>line2[/code] so we need to make sure they're formatted properly */
				$contents = \substr( \IPS\Text\DOMParser::parse(
					$originalNode->ownerDocument->saveHtml( $originalNode ),
					/* DOMElement Parse */
					function ( \DOMElement $element, \DOMNode $parent, \IPS\Text\DOMParser $parser )
					{
						/* Control elements get insetred normally */
						if ( $parent instanceof \DOMDocument or in_array( $parent->tagName, array( 'html', 'head', 'body' ) ) )
						{
							$ownerDocument = $parent->ownerDocument ?: $parent;
							$newElement = $ownerDocument->importNode( $element );
							
							$parent->appendChild( $newElement );
						
							$parser->_parseDomNodeList( $element->childNodes, $newElement );
						}
						/* Everything else becomes a direct child of the <pre> */
						else
						{
							/* With "\n"s inserted appropriately */
							if ( $element->tagName == 'br' or $element->tagName == 'p' )
							{
								$parent->appendChild( new \DOMText("\n") );
							}
							
							$parser->_parseDomNodeList( $element->childNodes, $parent );
						}
					},
					/* DOMText Parse */
					function ( \DOMText $textNode, \DOMNode $parent, \IPS\Text\DOMParser $parser ) {
						/* CKEditor will send "\n<br>" so just strip those so we don't get double breaks */
						$text = str_replace( "\n", '', $textNode->textContent );
						
						/* CKEditor will also send "<br>\t" so also strip any whitespace after a break
							CKEditor doesn't actually have a way to indent individual lines */
						if ( $previousSibling = $textNode->previousSibling and $previousSibling instanceof \DOMElement and $previousSibling->tagName == 'br' )
						{
							$text = preg_replace( '/^\s/', '', $text );
						}
						
						/* Insert */
						$parent->appendChild( new \DOMText( $text ) );
					}
				), 21, -6 );
				
				/* Create a new <pre> with those contents */
				$return = $originalNode->ownerDocument->createElement( 'pre' );
				$return->appendChild( new \DOMText( html_entity_decode( $contents ) ) ); // We have to decode HTML entities otherwise they'll be double-encoded. Test with "[code]<strong>Test</strong>[/code]"
				$return->setAttribute( 'class', 'ipsCode' );
				return $return;
			} );
						
			$return['code'] = $code;
			$return['codebox'] = $code;
			$return['html'] = $code;
			$return['php'] = $code;
			$return['sql'] = $code;
			$return['xml'] = $code;
		}
		
		/* Color */
		if ( static::canUse( $member, 'TextColor', $area ) )
		{
			$return['color'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'color:{option}' ) );
		}
		
		/* Font */
		if ( static::canUse( $member, 'Font', $area ) )
		{
			$return['font'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'font-family:{option}' ) );
		}
		
		/* HR */
		$return['hr'] = array( 'tag' => 'hr', 'single' => TRUE );

		/* Image */
		if ( static::canUse( $member, 'ipsImage', $area ) )
		{
			$return['img'] = array( 'tag' => 'img', 'attributes' => array( 'src' => '{option}', 'class' => 'ipsImage' ), 'single' => TRUE );
		}
		
		/* Indent */
		if ( static::canUse( $member, 'Indent', $area ) )
		{
			$return['indent'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'margin-left:{option}px' ), 'block' => FALSE, 'defaultOption' => 25 );
		}
		
		/* Italics */
		if ( static::canUse( $member, 'Italic', $area ) )
		{
			$return['i'] = array( 'tag' => 'em' );
		}
		
		/* Justify */
		if ( static::canUse( $member, 'JustifyLeft', $area ) )
		{
			$return['left'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:left' ), 'block' => TRUE );
		}
		if ( static::canUse( $member, 'JustifyCenter', $area ) )
		{
			$return['center'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:center' ), 'block' => TRUE );
		}
		if ( static::canUse( $member, 'JustifyRight', $area ) )
		{
			$return['right'] = array( 'tag' => 'div', 'attributes' => array( 'style' => 'text-align:right' ), 'block' => TRUE );
		}
		
		/* Links */
		if ( static::canUse( $member, 'ipsLink', $area ) )
		{
			/* Email */
			$return['email'] = array( 'tag' => 'a', 'attributes' => array( 'href' => 'mailto:{option}' ) );
			
			/* Member */
			$return['member'] = array(
				'tag'		=> 'a',
				'attributes'=> array( 'contenteditable' => 'false', 'data-ipsHover' => '' ),
				'callback'	=> function( \DOMElement $node, $matches, \DOMDocument $document )
				{
					try
					{
						$member = \IPS\Member::load( $matches[2], 'name' );
						if ( $member->member_id != 0 )
						{
							$node->setAttribute( 'href',  $member->url() );
							$node->setAttribute( 'data-ipsHover-target',  $member->url()->setQueryString( 'do', 'hovercard' ) );
							$node->setAttribute( 'data-mentionid',  $member->member_id );
							$node->appendChild( $document->createTextNode( '@' . $member->name ) );
						}

					}
					catch ( \Exception $e ) {}
					
					return $node;
				},
				'single'	=> TRUE,
			);
			
			/* Links */
			$return['url'] = array( 'tag' => 'a', 'attributes' => array( 'href' => '{option}' ) );
		}
				
		/* List */
		if ( static::canUse( $member, 'BulletedList', $area ) or static::canUse( $member, 'NumberedList', $area ) )
		{
			$return['list'] = array(
				'tag' => 'ul',
				'callback' => function( $node, $matches, $document )
				{
					/* Set the main attributes for our <ul> or <ol> element */
					if ( isset( $matches[2] ) )
					{
						$node = $document->createElement( 'ol' );
						switch ( $matches[2] )
						{
							case '1':
								$node->setAttribute( 'style', 'list-style-type: decimal' );
								break;
							case '0':
								$node->setAttribute( 'style', 'list-style-type: decimal-leading-zero' );
								break;
							case 'a':
								$node->setAttribute( 'style', 'list-style-type: lower-alpha' );
								break;
							case 'A':
								$node->setAttribute( 'style', 'list-style-type: upper-alpha' );
								break;
							case 'i':
								$node->setAttribute( 'style', 'list-style-type: lower-roman' );
								break;
							case 'I':
								$node->setAttribute( 'style', 'list-style-type: upper-roman' );
								break;
						}
					}
															
					return $node;
				},
				'finishedCallback'	=> function( \DOMElement $originalNode ) {
					
					/* If the [/list] was in it's own paragraph, that empty paragraph will be present. Remove it */
					if ( $originalNode->lastChild and $originalNode->lastChild->nodeType === XML_ELEMENT_NODE and !$originalNode->lastChild->childNodes->length )
					{
						$originalNode->removeChild( $originalNode->lastChild );
					}
					
					/* Do it */
					return $this->_parseContentWithSeparationTag(
						$originalNode,
						function ( \DOMDocument $document ) use ( $originalNode ) {
							return $document->importNode( $originalNode->cloneNode() );
						},
						function ( \DOMDocument $document ) {
							return new \DOMElement('li');
						},
						'[*]'
					);
				},
				'block' => TRUE,
				'noChildren' => TRUE
			);
		}
				
		/* Quote */
		if ( static::canUse( $member, 'ipsQuote', $area ) )
		{
			$return['quote'] = array(
				'tag' => 'blockquote',
				'callback' => function( \DOMElement $node, $matches, \DOMDocument $document )
				{
					/* What options do we have? */
					$options = array();
					if ( isset( $matches[2] ) and $matches[2] )
					{
						preg_match_all('/\s?(.+?)=[\'"](.+?)[\'"$]/', trim( $matches[2] ), $_options );
						foreach ( $_options[0] as $k => $v )
						{
							$options[ $_options[1][ $k ] ] = $_options[2][ $k ];
						}
					}
					
					/* Set the main attributes for our <blockquote> element */
					$node->setAttribute( 'class', 'ipsQuote' );
					$node->setAttribute( 'data-ipsQuote', '' );
					if ( isset( $options['name'] ) and $options['name'] )
					{
						$node->setAttribute( 'data-ipsQuote-username', $options['name'] );
					}
					if ( isset( $options['date'] ) and $options['date'] )
					{
						$node->setAttribute( 'data-ipsQuote-timestamp', strtotime( $options['date'] ) );
					}
					if ( \IPS\Application::appIsEnabled('forums') and isset( $options['post'] ) and $options['post'] )
					{
						try
						{
							$post = \IPS\forums\Topic\Post::load( $options['post'] );
							
							$node->setAttribute( 'data-ipsQuote-contentapp', 'forums' );
							$node->setAttribute( 'data-ipsQuote-contenttype', 'forums' );
							$node->setAttribute( 'data-ipsQuote-contentclass', 'forums_Topic' );
							$node->setAttribute( 'data-ipsQuote-contentid', $post->item()->tid );
							$node->setAttribute( 'data-ipsQuote-contentcommentid', $post->pid );
						}
						catch ( \OutOfRangeException $e ) {}
					}
					
					/* Create the citation element */
					$citation = $document->createElement('div');
					$citation->setAttribute( 'class', 'ipsQuote_citation' );
					$node->appendChild( $citation );
					
					/* Create the content element */
					$contents = $document->createElement('div');
					$contents->setAttribute( 'class', 'ipsQuote_contents' );
					$node->appendChild( $contents );
					
					return $node;
				},
				'getBlockContentElement' => function( \DOMElement $node )
				{
					foreach ( $node->childNodes as $child )
					{
						if ( $child instanceof \DOMElement and $child->getAttribute('class') == 'ipsQuote_contents' )
						{
							return $child;
						}
					}
					return $node;
				},
				'block' => TRUE
			);
		}
		
		/* Size */
		if ( static::canUse( $member, 'FontSize', $area ) )
		{
			$return['size'] = array(
				'tag'		=> 'span',
				'callback'	=> function( $node, $matches )
				{
					switch ( $matches[2] )
					{
						case 1:
							$node->setAttribute( 'style', 'font-size:8px' );
							break;
						case 2:
							$node->setAttribute( 'style', 'font-size:10px' );
							break;
						case 3:
							$node->setAttribute( 'style', 'font-size:12px' );
							break;
						case 4:
							$node->setAttribute( 'style', 'font-size:14px' );
							break;
						case 5:
							$node->setAttribute( 'style', 'font-size:18px' );
							break;
						case 6:
							$node->setAttribute( 'style', 'font-size:24px' );
							break;
						case 7:
							$node->setAttribute( 'style', 'font-size:36px' );
							break;
						case 8:
							$node->setAttribute( 'style', 'font-size:48px' );
							break;
					}
					return $node;
				}
			);
		}
		
		/* Spoiler */
		if ( static::canUse( $member, 'ipsSpoiler', $area ) )
		{
			$return['spoiler'] = array(
				'tag' => 'div',
				'callback' => function( \DOMElement $node, $matches, \DOMDocument $document )
				{
					/* Set the main attributes for our <div> element */
					$node->setAttribute( 'class', 'ipsSpoiler' );
					$node->setAttribute( 'data-ipsSpoiler', '' );
					
					/* Create the citation element */
					$header = $document->createElement('div');
					$header->setAttribute( 'class', 'ipsSpoiler_header' );
					$node->appendChild( $header );
					$headerSpan = $document->createElement('span');
					$header->appendChild( $headerSpan );
					
					/* Create the content element */
					$contents = $document->createElement('div');
					$contents->setAttribute( 'class', 'ipsSpoiler_contents' );
					$node->appendChild( $contents );
					
					return $node;		
				},
				'getBlockContentElement' => function( \DOMElement $node )
				{
					foreach ( $node->childNodes as $child )
					{
						if ( $child instanceof \DOMElement and $child->getAttribute('class') == 'ipsSpoiler_contents' )
						{
							return $child;
						}
					}
					return $node;
				},
				'block' => TRUE
			);
		}
		
		/* Strike */
		if ( static::canUse( $member, 'Strike', $area ) )
		{
			$return['s'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:line-through' ) );
			$return['strike'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:line-through' ) );
		}
		
		/* Subscript */
		if ( static::canUse( $member, 'Subscript', $area ) )
		{
			$return['sub'] = array( 'tag' => 'sub' );
		}
		
		/* Superscript */
		if ( static::canUse( $member, 'Superscript', $area ) )
		{
			$return['sup'] = array( 'tag' => 'sup' );
		}
		
		/* Underline */
		if ( static::canUse( $member, 'Underline', $area ) )
		{
			$return['u'] = array( 'tag' => 'span', 'attributes' => array( 'style' => 'text-decoration:underline' ) );
		}
		
		/* App-Specific */
		foreach ( \IPS\Application::allExtensions( 'core', 'BBCode', $member ) as $key => $bbcode )
		{
			if ( $bbcode->permissionCheck( $member, $area ) )
			{
				list( $app, $tag ) = explode( '_', $key );
				$return[ $tag ] = $bbcode->getConfiguration();
			}
		}
		
		return $return;
	}
	
	/**
	 * @brief	Cached permissions
	 */
	protected static $permissions = array();
	
	/**
	 * Can use plugin?
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param	string		$key	Plugin key
	 * @param	string		$area	The Editor area
	 * @return	bool
	 */
	public static function canUse( \IPS\Member $member, $key, $area )
	{
		$permissionSettings = json_decode( \IPS\Settings::i()->ckeditor_permissions, TRUE );
		
		if ( !isset( static::$permissions[ $member->member_id ][ $key ] ) )
		{
			if ( !isset( $permissionSettings[ $key ] ) )
			{
				static::$permissions[ $member->member_id ][ $key ] = TRUE;
			}
			else
			{
				$val = TRUE;
				if ( $permissionSettings[ $key ]['groups'] !== '*' )
				{
					if ( !$member->inGroup( $permissionSettings[ $key ]['groups'] ) )
					{
						$val = FALSE;
					}
				}
				if ( $permissionSettings[ $key ]['areas'] !== '*' )
				{
					if ( !in_array( $area, $permissionSettings[ $key ]['areas'] ) )
					{
						$val = FALSE;
					}
				}
				static::$permissions[ $member->member_id ][ $key ] = $val;
			}
		}
		
		return static::$permissions[ $member->member_id ][ $key ];
	}
	
	/**
	 * Parse content looking for a separation tag
	 * Used for lists where [*] breaks and whole content where [page] breaks
	 *
	 * @param	\DOMNode	$originalNode	The node containing the content we want to examine
	 * @param	callback	$mainElementCreator	A callback which returns a \DOMElement to be the main element. Is passed \DOMDocument as a parameter
	 * @param	callback	$subElementCreator		A callback which returns a \DOMElement to be a new sub element. Is passed \DOMDocument as a parameter
	 * @return	\DOMElement
	 */
	protected function _parseContentWithSeparationTag( \DOMNode $originalNode, $mainElementCreator, $subElementCreator, $separator )
	{
		/* Create a copy of the node */
		$workingDocument = new \DOMDocument;
		$workingNode = $workingDocument->importNode( $originalNode, TRUE );
		
		/* Create a fresh <ul> with a single <li> inside */
		$mainElement = call_user_func( $mainElementCreator, $originalNode->ownerDocument );
		$currentSubElement = $mainElement->appendChild( call_user_func( $subElementCreator, $originalNode->ownerDocument ) );
		
		/* Parse */
		$ignoreNextSeparator = TRUE;
		foreach ( $workingNode->childNodes as $node )
		{
			$this->_parseContentWithSeparationTagLoop( $mainElement, $currentSubElement, $separator, $subElementCreator, $node, $mainElement, $ignoreNextSeparator );
		}
				
		/* Return */
		return $mainElement;
	}
	
	/**
	 * Loop for _parseContentWithSeparationTag
	 *
	 * @param	\DOMElement	$mainElement			The main element all of our content is going into
	 * @param	\DOMElement	$currentSubElement		The current sub element that nodes go into. When a $separator is detected, a new one is created
	 * @param	string		$separator				The separator, e.g. "[*]" or "[page]"
	 * @param	callback	$subElementCreator		A callback which returns a \DOMElement to be a new sub element
	 * @param	\DOMNode	$node					The current node we're examining
	 * @param	\DOMNode	$parent					The parent of the current node we're examining
	 * @param	bool		$ignoreNextSeparator	If we have [list][*]Foo[/list] We want to ignore the first [*] so we don't end up with <ul><li></li><li>Foo</li></ul> - this keeps track of that
	 * @return	void
	 */
	protected function _parseContentWithSeparationTagLoop( \DOMElement $mainElement, \DOMElement &$currentSubElement, $separator, $subElementCreator, \DOMNode $node, \DOMNode &$parent, &$ignoreNextSeparator )
	{
		/* If the node is an element... */
		if ( $node->nodeType === XML_ELEMENT_NODE )
		{			
			/* Ignore any preceeding <br>s */
			if ( $ignoreNextSeparator and $node->tagName == 'br' )
			{
				return;
			}
			
			/* Import and insert */
			$newElement = $mainElement->ownerDocument->importNode( $node );
			if ( $parent->isSameNode( $mainElement ) )
			{
				$currentSubElement->appendChild( $newElement );
			}
			else
			{
				$parent->appendChild( $newElement );
			}
			
			/* Loop children */
			foreach ( $node->childNodes as $child )
			{
				$this->_parseContentWithSeparationTagLoop( $mainElement, $currentSubElement, $separator, $subElementCreator, $child, $newElement, $ignoreNextSeparator );
			}
		}
		
		/* Or if it's text... */
		elseif ( $node->nodeType === XML_TEXT_NODE )
		{			
			/* Ignore any closing tag */
			$text = $node->wholeText;
			$text = str_replace( preg_replace( '/\[(.+?)\]/', '[/$1]', $separator ), '', $text );
			
			/* Break it up where we find the separator... */			
			foreach ( array_filter( preg_split( '/(' . preg_quote( $separator, '/' ) . ')/', $text, null, PREG_SPLIT_DELIM_CAPTURE ), 'trim' ) as $textSection )
			{
				/* If this section the separator... */
				if ( $textSection === $separator )
				{
					/* Unless we're ignoring it, create a new element... */
					if ( !$ignoreNextSeparator )
					{
						/* Strip any extrenous <br> from the last one */
						if ( $currentSubElement->lastChild and $currentSubElement->lastChild->nodeType === XML_ELEMENT_NODE and $currentSubElement->lastChild->tagName == 'br' )
						{
							$currentSubElement->removeChild( $currentSubElement->lastChild );
						}
						
						/* Create a new one */
						$currentSubElement = $mainElement->appendChild( call_user_func( $subElementCreator, $mainElement->ownerDocument ) );
						if ( !$parent->isSameNode( $mainElement ) )
						{
							$parent = $parent->cloneNode( FALSE );
							$currentSubElement->appendChild( $parent );
						}
					}
					/* Or if we are, then don't ignore the next one */
					else
					{
						$ignoreNextSeparator = FALSE;
					}
				}
				else
				{
					/* Insert */				
					if ( $parent->isSameNode( $mainElement ) )
					{
						$currentSubElement->appendChild( new \DOMText( $textSection ) );
					}
					else
					{
						$parent->appendChild( new \DOMText( $textSection ) );
					}
					
					/* If we're meant to be ignoring the next separator, but we have found content before it, remove that flag */
					if ( $ignoreNextSeparator )
					{
						$ignoreNextSeparator = FALSE;
					}
				}
			}
		}
	}	
		
	/* !Embeddable Media */
	
	/**
	 * Get OEmbed Services
	 * Implemented in this way so it's easy for hook authors to override if they wanted to
	 *
	 * @see		<a href="http://www.oembed.com">oEmbed</a>
	 * @return	array
	 */
	protected static function oembedServices()
	{
		return array(
			'youtube.com'					=> 'https://www.youtube.com/oembed',
			'm.youtube.com'					=> 'https://www.youtube.com/oembed',
			'youtu.be'						=> 'https://www.youtube.com/oembed',
			'flickr.com'					=> 'https://www.flickr.com/services/oembed/',
			'flic.kr'						=> 'https://www.flickr.com/services/oembed/',
			'hulu.com'						=> 'http://www.hulu.com/api/oembed.json',
			'vimeo.com'						=> 'https://vimeo.com/api/oembed.json',
			'collegehumor.com'				=> 'http://www.collegehumor.com/oembed.json',
			'twitter.com'					=> 'https://api.twitter.com/1/statuses/oembed.json',
			'instagr.am'					=> 'https://api.instagram.com/oembed',
			'instagram.com'					=> 'https://api.instagram.com/oembed',
			'soundcloud.com'				=> 'https://soundcloud.com/oembed',
			'open.spotify.com'				=> 'https://embed.spotify.com/oembed/',
			'play.spotify.com'				=> 'https://embed.spotify.com/oembed/',
			'ted.com'						=> 'https://www.ted.com/services/v1/oembed',
			'vine.co'						=> 'https://vine.co/oembed.json',
			'facebook.com'					=> 'https://www.facebook.com/plugins/post/oembed.json',
			'gfycat.com'					=> 'https://api.gfycat.com/v1/oembed',
		);
	}
	
	/**
	 * @brief	External link request timeout
	 */
	public static $requestTimeout = \IPS\DEFAULT_REQUEST_TIMEOUT;
	
	/**
	 * Convert URL to embed HTML
	 *
	 * @param	string		$url		The URL
	 * @param	bool		$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @return	string|null	HTML embded code, or NULL if URL is not embeddable
	 */
	public static function embeddableMedia( $url, $iframe=FALSE )
	{
		/* Create URL object */
		try
		{
			$url = new \IPS\Http\Url( $url );
		}
		catch ( \InvalidArgumentException $e )
		{
			return NULL;
		}
		
		/* Internal */
		if ( $url->isInternal )
		{
			/* Internal Embed */
			if ( $embedCode = static::_internalEmbed( $url, $iframe ) )
			{
				return $embedCode;
			}
			
			/* Image? */
			if ( $embedCode = static::_imageEmbed( $url, $iframe ) )
			{
				return $embedCode;
			}
		}
		
		/* External */
		else
		{
			/* oEmbed? */
			if ( $embedCode = static::_oembedEmbed( $url, $iframe ) )
			{
				return $embedCode;
			}
			
			/* Other services */
			if ( $embedCode = static::_customEmbed( $url, $iframe ) )
			{
				return $embedCode;
			}
			
			/* Image? */
			if ( \IPS\Settings::i()->allow_remote_images and $embedCode = static::_imageEmbed( $url, $iframe ) )
			{
				return $embedCode;
			}
		}
		
		/* Still here? It's not embeddable */
		return NULL;
	}
	
	/**
	 * oEmbed Embed Code
	 *
	 * @param	\IPS\Http\Url	$url	URL
	 * @param	bool			$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @return	string|null
	 */
	protected static function _oembedEmbed( \IPS\Http\Url $url, $iframe=FALSE )
	{
		/* Strip the "www." from the domain */
		$domain = $url->data['host'];
		if ( mb_substr( $domain, 0, 4 ) === 'www.' )
		{
			$domain = mb_substr( $domain, 4 );
		}
		if( !$domain )
		{
			return null;
		}
		
		/* Get oEmbed Services */
		$oembedServices = static::oembedServices();
				
		/* If the URL's domain is in the list... */
		if ( array_key_exists( $domain, $oembedServices ) )
		{
			/* Call oEmbed Service */
			try
			{
				$response = \IPS\Http\Url::external( $oembedServices[ $domain ] )
					->setQueryString( array(
						'format'	=> 'json',
						'url'		=> (string) $url,
						'scheme'	=> ( $url->data['scheme'] === 'https' or \IPS\Request::i()->isSecure() ) ? 'https' : null
					) )
					->request( static::$requestTimeout )
					->get()
					->decodeJson();
			}
			/* If it error'd (connection error or unexpected response), we'll not embed this */
			catch ( \Exception $e )
			{
				return NULL;
			}
			
			/* Gfycat reports as video but it doesn't use the aspect ratio we account for in videos, so treat it as rich */
			if ( $domain == 'gfycat.com' )
			{
				$response['type'] = 'rich';
			}
			
			/* We need a type otherwise we can't embed */
			if( !isset( $response['type'] ) )
			{
				return NULL;
			}

			/* The "type" parameter is a way for services to indicate the type of content they are retruning. It is not strict, but we use it to identify the best styles to apply. */
			switch ( $response['type'] )
			{
				/* Static photo - show an <img> tag, linked if necessary, using .ipsImage to be responsive. Similar outcome to if a user had used the "insert image from URL" button */
				case 'photo':
					return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->photo( $response['url'], $url, $response['title'] );
				
				/* Video - insert the provided HTML directly (it will be a video so there's nothing we need to prevent from being edited), using .ipsEmbeddedVideo to make it responsive */
				case 'video':
                    $response['html'] = str_replace( 'allowfullscreen', 'allowfullscreen="true"', $response['html'] );
					return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->video( $response['html'] );
				
				/* Other - show an <iframe> with the provided HTML inside, using .ipsEmbeddedOther to make the width right and data-controller="core.front.core.autoSizeIframe" to make the height right */
				case 'rich':						
					if ( $iframe )
					{
						return $response['html'];
					}
					else
					{
						return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->iframe( (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=embed', 'front' )->setQueryString( 'url', (string) $url ) );
					}
				
				/* Link - none of the defautl services use this, but provided for completeness. Just inserts an <a> tag */
				case 'link':
					return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->link( $response['url'], $response['title'] );
			}
		}
		
		/* Still here? It's not an oEmbed URL */
		return NULL;
	}
	
	/**
	 * Custom (services which don't support oEmbed but we still want to support) Embed Code
	 *
	 * @param	\IPS\Http\Url	$url		URL
	 * @param	bool			$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @return	string|null
	 */
	protected static function _customEmbed( \IPS\Http\Url $url, $iframe=FALSE )
	{
		/* Google+ is the only one we support here now */
		if ( $url->data['host'] === 'plus.google.com' and preg_match( '/^https:\/\/plus\.google\.com\/.+?\/posts\//i', (string) $url, $matches ) )
		{
			if ( $iframe )
			{
				return \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->blankTemplate( \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->google( (string) $url ) );
			}
			else
			{
				return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->iframe( (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=embed', 'front' )->setQueryString( 'url', (string) $url ) );
			}
		}
		
		/* So if it's not that, just return */
		return NULL;
	}
	
	/**
	 * Internal Embed Code
	 *
	 * @param	\IPS\Http\Url	$url		URL
	 * @param	bool			$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @return	string|null
	 */
	protected static function _internalEmbed( \IPS\Http\Url $url, $iframe=FALSE )
	{
		/* If this URL has a #comment-123 fragment, change it to the findComment URL so the comment embeds rather than the item */
		if ( isset( $url->data['fragment'] ) and mb_strstr( $url->data['fragment'], 'comment-' ) and empty( $url->queryString['do'] ) )
		{
			$url = $url->setQueryString( array( 'do' => 'findComment', 'comment' => str_replace( 'comment-', '', $url->data['fragment'] ) ) );
		}
		
		/* Get the "real" query string (whatever the query string is, plus what we can get from decoding the FURL) */
		try
		{
			$qs = array_merge( $url->queryString, $url->getFriendlyUrlData() );
		}
		catch( \OutOfRangeException $e )
		{
			/* getFriendlyUrlData() can throw an OutOfRangeException if it doesn't recognize the URL */
			$qs = $url->queryString;
		}
		
		/* We need an app, and it needs to not be an RSS link */
		if ( !isset( $qs['app'] ) )
		{
			return NULL;
		}
		
		/* It needs to not be an RSS link */
		if ( isset( $qs['do'] ) and $qs['do'] === 'rss' )
		{
			return NULL;
		}
		
		/* Load the application, but be aware it could be an old invalid URL if this is an upgrade */
		try
		{
			$application = \IPS\Application::load( $qs['app'] );
		}
		catch( \OutOfRangeException $e )
		{
			return NULL;
		}
		
		/* Loop through our content classes and see if we can find one that matches */
		foreach ( $application->extensions( 'core', 'ContentRouter' ) as $key => $extension )
		{
			/* We need to check the class itself, along wiht owned nodes (Blogs, etc.) and anything else which isn't 
				normally part of the content item system but the app wants to be embeddable (Commerce product reviews, etc) */
			$classes = $extension->classes;
			if ( isset( $extension->ownedNodes ) )
			{
				$classes = array_merge( $classes, $extension->ownedNodes );
			}
			if ( isset( $extension->embeddableContent ) )
			{
				$classes = array_merge( $classes, $extension->embeddableContent );
			}
			
			/* But we're only interested in classes which implement IPS\Content\Embeddable */
			$classes = array_filter( $classes, function( $class ) {
				return in_array( 'IPS\Content\Embeddable', class_implements( $class ) );
			} );
						
			/* So for each of those... */
			foreach ( $classes as $class )
			{
				/* Try to load it */
				try
				{
					$item = $class::loadFromURL( $url );
				}
				catch ( \Exception $e )
				{
					continue;
				}
				
				/* It needs to be embeddable... */
				if( !( $item instanceof \IPS\Content\Embeddable ) )
				{
					continue;
				}
				
				/* The URL needs to actually match... */
				$urlDiff = array_diff_assoc( $qs, array_merge( $item->url()->queryString, $item->url()->getFriendlyUrlData() ) );
				if ( count( array_intersect( array( 'app', 'module', 'controller' ), array_keys( $urlDiff ) ) ) )
				{
					continue;
				}
				
				/* Okay, get teh correct embed URL! */
				$preview = new \IPS\Http\Url( (string) $url );
				if ( \IPS\Settings::i()->use_friendly_urls )
				{
					$preview = $preview->stripArguments();
				}
				$preview = $preview->setQueryString( 'do', 'embed' );
				if( isset( $url->queryString['do'] ) and $url->queryString['do'] == 'findComment' )
				{
					$preview = $preview->setQueryString( 'embedComment', $url->queryString['comment'] );
				}
				elseif( isset( $url->queryString['do'] ) and $url->queryString['do'] == 'findReview' )
				{
					$preview = $preview->setQueryString( 'embedReview', $url->queryString['review'] );
				}
				if( isset( $url->queryString['do'] ) )
				{
					$preview = $preview->setQueryString( 'embedDo', $url->queryString['do'] );
				}
				if ( isset( $url->queryString['page'] ) AND $url->queryString['page'] > 1 )
				{
					$preview = $preview->setQueryString( 'page', $url->queryString['page'] );
				}
				
				/* And return */
				return "<iframe src='{$preview}' frameborder='0' data-embedContent></iframe>";
			}
		}
		
		/* Still here? Not an internal embed */
		return NULL;
	}
	
	/**
	 * Image Embed Code
	 *
	 * @param	\IPS\Http\Url	$url		URL
	 * @param	bool			$iframe		Some services need to be in iFrames so they cannot be edited in the editor. If TRUE, will return contents for iframe, if FALSE, return the iframe.
	 * @return	string|null
	 */
	protected static function _imageEmbed( \IPS\Http\Url $url, $iframe=FALSE )
	{
		/* What's the extension? */
		$extension = mb_substr( $url, mb_strrpos( $url, '.' ) + 1 );
		$questionPos = mb_strpos( $extension, '?' );
		if ( $questionPos !== FALSE )
		{
			$extension = mb_substr( $extension, 0, $questionPos );
		}
		$hashPos = mb_strpos( $extension, '#' );
		if ( $hashPos !== FALSE )
		{
			$extension = mb_substr( $extension, 0, $hashPos );
		}
		
		/* Is that an image extension? */
		if ( !in_array( mb_strtolower( $extension ), \IPS\Image::$imageExtensions ) )
		{
			return NULL;
		}
		
		/* Get the image */
		try
		{
			$imageContents = $url->request()->get();
		}
		/* If it times out or we can't connect, we can't embed */
		catch ( \IPS\Http\Request\Exception $e )
		{
			return NULL;
		}
				
		/* Get the image dimension - try getimagesize first, as it is more efficient when available */
		try
		{
			/* Need to write a temporary file */
			$temporary = tempnam( \IPS\TEMP_DIRECTORY, 'image' );
			\file_put_contents( $temporary, $imageContents );
			
			/* Then we can use getimagesize */
			$dims = @getimagesize( $temporary );
			
			/* And delete it */
			if ( @is_file( $temporary ) )
			{
				@unlink( $temporary );
			}
			
			/* Check that the dimensions returned were valid */
			if( $dims === FALSE OR !isset( $dims[0] ) OR !$dims[0] )
			{
				throw new \RuntimeException;
			}
		}
		/* If that fails, then we'll create an image object */
		catch( \RuntimeException $e )
		{
			/* Try to create the image object */
			try
			{
				$image = \IPS\Image::create( $imageContents );
			}
			/* If it fails, it's not a valid image */
			catch ( \Exception $e )
			{
				return NULL;
			}
			
			/* Set the dimensions */
			$dims = array( 0 => $image->width, 1 => $image->height );
		}
		
		/* Adjust the width/height according to our maximum dimensions */
		$maxImageDims	= \IPS\Settings::i()->attachment_image_size ? explode( 'x', \IPS\Settings::i()->attachment_image_size ) : array( 1000, 750 );
		$width = NULL;
		$height = NULL;
		if ( is_array( $dims ) AND ( $dims[0] > 0 AND $dims[1] > 0 ) )
		{
			/* Check width first */
			if ( $dims[0] > $maxImageDims[0] )
			{
				$width	= $maxImageDims[0];
				$height = floor( $dims[1] / $dims[0] * $width );
			}
			
			/* Then height */
			if ( $height > $maxImageDims[1] )
			{
				$width	= floor( $maxImageDims[1] * ( $width / $height ) );
				$height = $maxImageDims[1];
			}
		}
		
		/* And return the embed */
		return \IPS\Theme::i()->getTemplate( 'embed', 'core', 'global' )->photo( $url, NULL, NULL, $width, $height );
	}
	
	/* !Utility Methods */
		
	/**
	 * Parse statically
	 *
	 * @param	string				$value				The value to parse
	 * @param	bool				$bbcode				Parse BBCode?
	 * @param	array|null			$attachIds			array of ID numbers to idenfity content for attachments if the content has been saved - the first two must be int or null, the third must be string or null. If content has not been saved yet, an MD5 hash used to claim attachments after saving.
	 * @param	\IPS\Member|null	$member				If parsing BBCode or attachments, the member posting. NULL will use currently logged in member.
	 * @param	string|bool			$area				If parsing BBCode or attachments, the Editor area we're parsing in. e.g. "core_Signatures". A boolean value will allow or disallow all BBCodes that are dependant on area.
	 * @param	bool				$filterProfanity	Remove profanity?
	 * @param	bool				$cleanHtml			If TRUE, HTML will be cleaned through HTMLPurifier
	 * @param	callback			$htmlPurifierConfig	A function which will be passed the HTMLPurifier_Config object to customise it - see example
	 * @return	string
	 * @see		__construct
	 */
	public static function parseStatic( $value, $bbcode=FALSE, $attachIds=NULL, $member=NULL, $area=FALSE, $filterProfanity=TRUE, $cleanHtml=TRUE, $htmlPurifierConfig=NULL )
	{
		$obj = new self( $bbcode, $attachIds, $member, $area, $filterProfanity, $cleanHtml, $htmlPurifierConfig );
		return $obj->parse( $value );
	}
		
	/**
	 * Remove specific elements, useful for cleaning up content for display or truncating
	 *
	 * @param	string				$value			The value to parse
	 * @param	array|string		$elements		Element to remove, or array of elements to remove. Can be in format "element[attribute=value]"
	 * @return	string
	 */
	public static function removeElements( $value, $elements=array( 'blockquote', 'img', 'a' ) )
	{
		/* Init */
		$elementsToRemove = is_string( $elements ) ? array( $elements ) : $elements;
		
		/* Do it */
		return DOMParser::parse( $value, function( \DOMElement $element, \DOMNode $parent, \IPS\Text\DOMParser $parser ) use ( $elementsToRemove )
		{
			/* Check all of the $elementsToRemove */
			foreach( $elementsToRemove as $definition )
			{
				/* If this is in the element[attribute=value] format... */
				if ( mb_strstr( $definition, '[' ) and mb_strstr( $definition, '=' ) )
				{
					/* Break it up */
					preg_match( '#^([a-z]+?)\[([^\]]+?)\]$#i', $definition, $matches );
					
					/* If the element tag name matches the first bit... */
					if( $element->tagName == $matches[1] )
					{
						/* Break up the definition into name and value */
						list( $attribute, $value ) = explode( '=', trim( $matches[2] ) );
						
						/* Remove quotes */
						$value = str_replace( array( '"', "'" ), '', $value );
						
						/* If it matches, return to skip this element. */
						if ( $element->getAttribute( $attribute ) == $value )
						{
							return;
						}
					}
				}
				/* Or if it's just in normal format, check it and if it matches, return to skip this element. */
				else if ( $element->tagName == $definition )
				{
					return;
				}
			}
			
			/* If we're still here, it's fine and we can import it */
			$ownerDocument = $parent->ownerDocument ?: $parent;
			$newElement = $ownerDocument->importNode( $element );
			$parent->appendChild( $newElement );
			
			/* And continue to children */
			$parser->_parseDomNodeList( $element->childNodes, $newElement );
		} );		
	}
	
	/**
	 * Munge resources in ACP
	 *
	 * @param	string	$value	The value to parse
	 * @return	string
	 */
	public static function mungeResources( $value )
	{
		if ( !$value )
		{
			return '';
		}
		
		return DOMParser::parse( $value, function( \DOMElement $element, \DOMNode $parent, \IPS\Text\DOMParser $parser )
		{
			/* Munge */
			if ( $element->tagName === 'img' OR $element->tagName === 'iframe' )
			{
				$localDomain	= parse_url( \IPS\Settings::i()->base_url, PHP_URL_HOST );
				$currentSrc		= $element->getAttribute('src');
				$srcDomain		= parse_url( $currentSrc, PHP_URL_HOST );
				if( $localDomain != $srcDomain )
				{
					$element->removeAttribute( 'src' );

					$key = hash_hmac( "sha256", $currentSrc, \IPS\SITE_SECRET_KEY ?: md5( \IPS\Settings::i()->sql_pass . \IPS\Settings::i()->board_url . \IPS\Settings::i()->sql_database ) );
					$element->setAttribute( 'src', (string) \IPS\Http\Url::internal( 'app=core&module=system&controller=redirect&url=' . urlencode( $currentSrc ) . '&key=' . $key . '&resource=1', 'front' ) );
					$element->setAttribute( 'data-munge-src', $currentSrc );
				}
			}
			
			/* Import it */
			$ownerDocument = $parent->ownerDocument ?: $parent;
			$newElement = $ownerDocument->importNode( $element );
			$parent->appendChild( $newElement );
			
			/* And continue to children */
			$parser->_parseDomNodeList( $element->childNodes, $newElement );
		} );
	}
	
	/**
	 * @brief	Emoticons
	 */
	protected static $emoticons = NULL;
	
	/**
	 * Rebuild attachment urls
	 *
	 * @param	string		$textContent	Content
	 * @return	mixed	False, or rebuilt content
	 */
	public static function rebuildAttachmentUrls( $textContent )
	{
		$rebuilt	= FALSE;
		
		$textContent = preg_replace( '#<([^>]+?)(href|src)=(\'|")<fileStore\.([\d\w\_]+?)>/#i', '<\1\2=\3%7BfileStore.\4%7D/', $textContent );
		$textContent = preg_replace( '#<([^>]+?)(href|src)=(\'|")<___base_url___>/#i', '<\1\2=\3%7B___base_url___%7D/', $textContent );
		$textContent = preg_replace( '#<([^>]+?)(data-(fileid|ipshover\-target))=(\'|")<___base_url___>/#i', '<\1\2=\3%7B___base_url___%7D/', $textContent );
		
		/* Create DOMDocument */
		libxml_use_internal_errors(TRUE);
		$content = new \DOMDocument( '1.0', 'UTF-8' );
		@$content->loadHTML( "<head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'/></head>" . $textContent );
		
		$xpath = new \DOMXpath( $content );
		
		foreach ( $xpath->query('//img') as $image )
		{
			if( $image->getAttribute( 'data-fileid' ) )
			{
				try
				{
					$attachment	= \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', $image->getAttribute( 'data-fileid' ) ) )->first();
					$image->setAttribute( 'src', '{fileStore.core_Attachment}/' . ( $attachment['attach_thumb_location'] ? $attachment['attach_thumb_location'] : $attachment['attach_location'] ) );
					
					$anchor = $image->parentNode;
					$anchor->setAttribute( 'href', '{fileStore.core_Attachment}/' . $attachment['attach_location'] );
					
					$rebuilt = TRUE;
				}
				catch ( \Exception $e ) { }
			}
			else
			{
				if ( ! isset( static::$fileObjectClasses['core_Emoticons'] ) )
				{
					static::$fileObjectClasses['core_Emoticons'] = \IPS\File::getClass('core_Emoticons' );
				}
				
				if ( static::$emoticons === NULL )
				{
					static::$emoticons = array();
		
					try
					{
						foreach ( \IPS\Db::i()->select( 'image, image_2x, width, height', 'core_emoticons' ) as $row )
						{
							static::$emoticons[] = $row;
						}
					}
					catch( \IPS\Db\Exception $ex )
					{
						/* The image_2x column was added in 4.1 so may not exist if Parser is used in previous upgrade modules */
						foreach ( \IPS\Db::i()->select( 'image, NULL as image_2x, 0 as width, 0 as height', 'core_emoticons' ) as $row )
						{
							static::$emoticons[] = $row;
						}
					}
				}

				if ( ( $image->tagName === 'img' and preg_match( '#^(' . preg_quote( rtrim( static::$fileObjectClasses['core_Emoticons']->baseUrl(), '/' ), '#' ) . ')/(.+?)$#', $image->getAttribute('src'), $matches ) ) )
				{
					foreach( static::$emoticons as $emo )
					{
						if ( $emo['image'] == $matches[2] )
						{
							$image->setAttribute( 'src', '{fileStore.core_Emoticons}/' . $matches[2] );

							if( $emo['image_2x'] && $emo['width'] && $emo['height'] )
							{
								/* Retina emoticons require a width and height for proper scaling */
								$image->setAttribute( 'srcset', '%7BfileStore.core_Emoticons%7D/' . $emo['image_2x'] . ' 2x' );
								$image->setAttribute( 'width', $emo['width'] );
								$image->setAttribute( 'height', $emo['height'] );
							}
							$rebuilt = TRUE;
						}
					}
				}
			}
		}

		if( $rebuilt )
		{
			$value = $content->saveHTML();
			
			$value = preg_replace( '/<meta http-equiv(?:[^>]+?)>/i', '', preg_replace( '/^<!DOCTYPE.+?>/', '', str_replace( array( '<html>', '</html>', '<body>', '</body>', '<head>', '</head>' ), '', $value ) ) );
			
			/* A few more replacements for dynamic file object URLs */
			$value = preg_replace( '#<([^>]+?)(href|src|srcset)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
			$value = preg_replace( '#<([^>]+?)(srcset)=(\'|")%7BfileStore\.([\d\w\_]+?)%7D/#i', '<\1\2=\3<fileStore.\4>/', $value );
			$value = preg_replace( '#<([^>]+?)(href|src|srcset)=(\'|")%7B___base_url___%7D/#i', '<\1\2=\3<___base_url___>/', $value );
			$value = preg_replace( '#<([^>]+?)(data-(fileid|ipshover\-target))=(\'|")%7B___base_url___%7D/#i', '<\1\2=\3<___base_url___>/', $value );
			
			return $value;
		}

		return FALSE;
	}
	
	/**
	 * Perform a safe html_entity_decode if you are not using UTF-8 MB4
	 *
	 * @param	string	$value	Value to html entity decode
	 * @return	string
	 */
	public static function utf8mb4SafeDecode( $value )
	{
		$value = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );

		if ( \IPS\Settings::i()->getFromConfGlobal('sql_utf8mb4') !== TRUE )
		{
			$value = preg_replace_callback( '/[\x{10000}-\x{10FFFF}]/u', function( $mb4Character ) {
				return mb_convert_encoding( $mb4Character[0], 'HTML-ENTITIES', 'UTF-8' );
			}, $value );
		}

		return $value;
	}
}