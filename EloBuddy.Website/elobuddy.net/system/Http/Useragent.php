<?php
/**
 * @brief		User-Agent Management Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Aug 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Http;
 
/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * User-Agent Management Class
 */
class _Useragent
{
	/**
	 * @brief	Search engine spider?
	 */
	public $spider				= FALSE;

	/**
	 * @brief	Mobile or tablet device?
	 */
	public $mobile				= FALSE;

	/**
	 * @brief	Browser or user agent key
	 */
	public $useragentKey		= NULL;
	
	/**
	 * @brief	Browser version
	 */
	public $useragentVersion	= NULL;

	/**
	 * @brief	Full user agent string
	 */
	public $useragent			= NULL;

	/**
	 * Constructor
	 *
	 * @param	string	$userAgent	The user agent string
	 * @return	void
	 */
	protected function __construct( $userAgent )
	{
		$this->useragent	= $userAgent;
	}

	/**
	 * Constructor
	 *
	 * @param	string	$userAgent	The user agent to parse (defaults to $_SERVER['HTTP_USER_AGENT'] if none supplied)
	 * @return	\IPS\Http\Useragent
	 */
	public static function parse( $userAgent=NULL )
	{
		$userAgent	= $userAgent ?: ( ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) ? $_SERVER['HTTP_USER_AGENT'] : '' );

		$obj	= new static( $userAgent );
		$obj->parseUserAgent();

		return $obj;
	}
	
	/**
	 * Human-Readable Browser Name
	 *
	 * @return	string
	 */
	public function __toString()
	{
		if ( \IPS\Member::loggedIn()->language()->checkKeyExists( 'useragent-' . $this->useragentKey ) )
		{
			return \IPS\Member::loggedIn()->language()->get( 'useragent-' . $this->useragentKey ) . ( $this->useragentVersion ? " {$this->useragentVersion}" : '' );
		}
		else
		{
			return $this->useragentKey;
		}
	}
	
	/**
	 * Parse the user agent data
	 *
	 * @return	void
	 */
	public function parseUserAgent()
	{
		/* Is this a spider? */
		foreach( array_merge( $this->searchEngineUseragents, $this->mobileEngineUseragents, $this->standardEngineUseragents ) as $key => $regex )
		{
			if( is_array( $regex ) )
			{
				foreach( $regex as $_expression )
				{
					if( preg_match( "#" . $_expression . "#im", $this->useragent, $matches ) )
					{
						$this->useragentKey	= $key;

						if( isset( $matches[1] ) AND !empty( $matches[1] ) )
						{
							$this->useragentVersion	= $matches[1];
						}

						break 2;
					}
				}
			}
			else
			{
				if( preg_match( "#" . $regex . "#im", $this->useragent, $matches ) )
				{
					$this->useragentKey	= $key;

					if( isset( $matches[1] ) AND !empty( $matches[1] ) )
					{
						$this->useragentVersion	= $matches[1];
					}

					break;
				}
			}
		}

		/* Set mobile/spider flag as appropriate */
		if( $this->useragentKey )
		{
			if( array_key_exists( $this->useragentKey, $this->searchEngineUseragents ) )
			{
				$this->spider	= TRUE;
			}
			else if( array_key_exists( $this->useragentKey, $this->mobileEngineUseragents ) )
			{
				$this->mobile	= TRUE;
			}
		}
		else
		{
			$this->useragentKey	= 'unknown';
		}
	}

	/**
	 * @brief	List of search engine user agent strings with regex to parse out the data.
	 * @note	Matches will be checked based on the order of this list - put more specific matches first and more generic matches later
	 * @note	If you wish to capture a version, be sure to have just ONE capturing parenthesis group (i.e. $matches[1])
	 * @note	You may put multiple regex definitions for a single key into an array
	 * @note	If the UA matches an entry in this array, $this->spider will be set to TRUE
	 */
	protected $searchEngineUseragents	= array(
		'about'			=> "Libby[_/ ]([0-9.]{1,10})",
		'adsense'		=> array( "Mediapartners-Google/([0-9.]{1,10})", "Mediapartners-Google" ),
		'ahrefs'		=> "AhrefsBot",
		'alexa'			=> "^ia_archive",
		'altavista'		=> "Scooter[ /\-]*[a-z]*([0-9.]{1,10})",
		'ask'			=> "Ask[ \-]?Jeeves",
		'baidu'			=> "^baiduspider\-",
		'bing'			=> array( "bingbot[ /]([0-9.]{1,10})", "msnbot(?:-media)?[ /]([0-9.]{1,10})" ),
		'brandwatch'	=> "magpie-crawler",
		'excite'		=> "Architext[ \-]?Spider",
		'google'		=> array( "Googl(?:e|ebot)(?:-Image|-Video|-News)?/([0-9.]{1,10})", "Googl(?:e|ebot)(?:-Image|-Video|-News)?/?" ),
		'googlemobile'	=> array( "Googl(?:e|ebot)(?:-Mobile)?/([0-9.]{1,10})", "Googl(?:e|ebot)(?:-Mobile)?/" ),
		'facebook'		=> "facebookexternalhit/([0-9.]{1,10})",
		'infoseek'		=> array( "SideWinder[ /]?([0-9a-z.]{1,10})", "Infoseek" ),
		'inktomi'		=> "slurp@inktomi\.com",
		'internetseer'	=> "^InternetSeer\.com",
		'look'			=> "www\.look\.com",
		'looksmart'		=> "looksmart-sv-fw",
		'lycos'			=> "Lycos_Spider_",
		'majestic'		=> "MJ12bot\/v([0-9.]{1,10})",
		'msproxy'		=> "MSProxy[ /]([0-9.]{1,10})",
		'webcrawl'		=> "webcrawl\.net",
		'websense'		=> "(?:Sqworm|websense|Konqueror/3\.(?:0|1)(?:\-rc[1-6])?; i686 Linux; 2002[0-9]{4})",
		'yahoo'			=> "Yahoo(?:.*?)(?:Slurp|FeedSeeker)",
		'yandex'		=> "Yandex(?:[^\/]+?)\/([0-9.]{1,10})",
	);

	/**
	 * @brief	List of mobile device user agent strings with regex to parse out the data.
	 * @note	Matches will be checked based on the order of this list - put more specific matches first and more generic matches later
	 * @note	If you wish to capture a version, be sure to have just ONE capturing parenthesis group (i.e. $matches[1])
	 * @note	You may put multiple regex definitions for a single key into an array
	 * @note	If the UA matches an entry in this array, $this->mobile will be set to TRUE
	 */
	protected $mobileEngineUseragents	= array(
		'transformer'	=> ";\s+?Transformer\s+?",
		'android'		=> "\sandroid\s+?([A-Za-z0-9.]{1,10}).+?mobile",
		'firefoxmobile'	=> "Mozilla(?:[^\(]+?)\((?:android|tablet)",
		'androidtablet'	=> "\sandroid\s+?([A-Za-z0-9.]{1,10})",
		'blackberry'	=> "blackberry\s?(\d+?)[\/;]",
		'iphone'		=> "iphone;",
		'ipodtouch'		=> "ipod;",
		'iPad'			=> "iPad;",
		'sonyericsson'	=> "^SonyEricsson[A-Za-z0-9]",
		'nokia'			=> "Nokia[A-Za-z0-9]",
		'motorola'		=> "^mot-[A-Za-z0-9]",
		'samsung'		=> "^samsung-[A-Za-z0-9]",
		'siemens'		=> "^sie-[A-Za-z0-9]",
		'htc'			=> "(?:htc-|htc_)([A-Za-z0-9.]{1,10})",
		'lg'			=> "^(?:lg|lge)-[A-Za-z0-9]",
		'palm'			=> "(?:palmsource/| pre/[0-9.]{1,10}|palm webos)",
		'operamini'		=> "opera (?:mobi|mini)",
		'windows7'		=> "Windows Phone OS ([^;]+);",
		'windows8'		=> "Windows Phone ([^;]+);",
	);

	/**
	 * @brief	List of other user agent strings (desktop browsers) with regex to parse out the data.
	 * @note	Matches will be checked based on the order of this list - put more specific matches first and more generic matches later
	 * @note	If you wish to capture a version, be sure to have just ONE capturing parenthesis group (i.e. $matches[1])
	 * @note	You may put multiple regex definitions for a single key into an array
	 */
	protected $standardEngineUseragents	= array(
		'amaya'			=> "amaya/([0-9.]{1,10})",
		'aol'			=> "aol[ /\-]([0-9.]{1,10})",
		'camino'		=> "camino/([0-9.+]{1,10})",
		'chimera'		=> "chimera/([0-9.+]{1,10})",
		'chrome'		=> "Chrome/([0-9.]{1,10})",
		'curl'			=> "curl[ /]([0-9.]{1,10})",
		'firebird'		=> "Firebird/([0-9.+]{1,10})",
		'firefox'		=> "Firefox/([0-9.+]{1,10})",
		'lotus'			=> "Lotus[ \-]?Notes[ /]([0-9.]{1,10})",
		'konqueror'		=> "konqueror/([0-9.]{1,10})",
		'lynx'			=> "lynx/([0-9a-z.]{1,10})",
		'maxthon'		=> " Maxthon[\);]",
		'omniweb'		=> "omniweb/[ a-z]?([0-9.]{1,10})$",
		'opera'			=> "opera[ /]([0-9.]{1,10})",
		'safari'		=> "version/([0-9.]{1,10})\s+?safari/([0-9.]{1,10})",
		'webtv'			=> "webtv[ /]([0-9.]{1,10})",
		'netscape'		=> "^mozilla/([0-4]\.[0-9.]{1,10})",
		'mozilla'		=> "^mozilla/([5-9]\.[0-9a-z.]{1,10})",
		'xbox'			=> "\(compatible; MSIE[ /]([0-9.]{1,10})(.*)Xbox",
		'explorer'		=> "\(compatible; MSIE[ /]([0-9.]{1,10})",
	);

	/**
	 * @brief	List of Facebook IP addresses
	 * @see		<a href='https://developers.facebook.com/docs/ApplicationSecurity/#facebook_scraper'>Facebook application security</a>
	 * @note	List pulled via suggested whois command on Sep 26 2013
	 */
	protected $facebookIpRange	= array(
		'204.15.20.0/22', '69.63.176.0/20', '66.220.144.0/20', '66.220.144.0/21', '69.63.184.0/21', '69.63.176.0/21', '74.119.76.0/22', '69.171.255.0/24', '173.252.64.0/18',
		'69.171.224.0/19', '69.171.224.0/20', '103.4.96.0/22', '69.63.176.0/24', '173.252.64.0/19', '173.252.70.0/24', '31.13.64.0/18', '31.13.24.0/21', '66.220.152.0/21', 
		'66.220.159.0/24', '69.171.239.0/24', '69.171.240.0/20', '31.13.64.0/19', '31.13.64.0/24', '31.13.65.0/24', '31.13.67.0/24', '31.13.68.0/24', '31.13.69.0/24', 
		'31.13.70.0/24', '31.13.71.0/24', '31.13.72.0/24', '31.13.73.0/24', '31.13.74.0/24', '31.13.75.0/24', '31.13.76.0/24', '31.13.77.0/24', '31.13.96.0/19', '31.13.66.0/24',
		'173.252.96.0/19', '69.63.178.0/24', '31.13.78.0/24', '31.13.79.0/24', '31.13.80.0/24', '31.13.82.0/24', '31.13.83.0/24', '31.13.84.0/24', '31.13.85.0/24', '31.13.86.0/24',
		'31.13.87.0/24', '31.13.88.0/24', '31.13.89.0/24', '31.13.90.0/24', '31.13.91.0/24', '31.13.92.0/24', '31.13.93.0/24', '31.13.94.0/24', '31.13.95.0/24', '69.171.253.0/24',
		'69.63.186.0/24', '31.13.81.0/24', '69.63.184.0/21', '2620:0:1c00::/40',
		'2a03:2880::/32', '2401:DB00::/32', '2a03:2880:fffe::/48', '2a03:2880:ffff::/48', '2620:0:1cff::/48', '2a03:2880:f000::/48', '2a03:2880:f001::/48', '2a03:2880:f002::/48',
		'2a03:2880:f003::/48', '2a03:2880:f004::/48', '2a03:2880:f005::/48', '2a03:2880:f006::/48', '2a03:2880:f007::/48', '2a03:2880:f008::/48', '2a03:2880:f009::/48', '2a03:2880:f00a::/48',
		'2a03:2880:f00b::/48', '2a03:2880:f00c::/48', '2a03:2880:f00d::/48', '2a03:2880:f00e::/48', '2a03:2880:f00f::/48', '2a03:2880:f010::/48', '2a03:2880:f011::/48', '2a03:2880:f012::/48',
		'2a03:2880:f013::/48', '2a03:2880:f014::/48', '2a03:2880:f015::/48', '2a03:2880:f016::/48'
	);

	/**
	 * Verify a supplied IP address is within the Facebook range
	 *
	 * @param	string	$ip		IP address to check
	 * @return	bool
	 * @see		<a href='http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet'>Stackoverflow: check IPv6 against CIDR</a>
	 * @see		<a href='http://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php5'>Stackoverflow: check IPv4 against CIDR</a>
	 */
	public function facebookIpVerified( $ip )
	{
		/* Is this an IPv6 address? */
		if( \strpos( $ip, ':' ) !== FALSE )
		{
			$ip	= $this->_convertCompressedIpv6ToBits( inet_pton( $ip ) );

			foreach( $this->facebookIpRange as $range )
			{
				if( \strpos( $range, ':' ) === FALSE )
				{
					continue;
				}

				list( $net, $maskBits )	= explode( '/', $range );

				$net	= $this->_convertCompressedIpv6ToBits( inet_pton( $net ) );

				if( $_checkIp == $_checkNet )
				{
					return TRUE;
				}
			}
		}
		else
		{
			foreach( $this->facebookIpRange as $range )
			{
				if( \strpos( $range, ':' ) !== FALSE )
				{
					continue;
				}

				list( $net, $maskBits )	= explode( '/', $range );

				if( ( ip2long( $ip ) & ~( ( 1 << ( 32 - $maskBits ) ) - 1 ) ) == ip2long( $net ) )
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Convert an IPv6 address to bits
	 *
	 * @param	string	$ip		Compressed IPv6 address
	 * @return	array
	 * @see		<a href='http://stackoverflow.com/questions/7951061/matching-ipv6-address-to-a-cidr-subnet'>Stackoverflow: check IPv6 against CIDR</a>
	 */
	protected function _convertCompressedIpv6ToBits( $ip )
	{
		$unpackedAddress	= unpack( 'A16', $ip );
		$unpackedAddress	= str_split( $unpackedAddress[1] );
		$ipAddress			= '';

		foreach( $unpackedAddress as $char )
		{
			$ipAddress	.= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		return $ipAddress;
	}
}