<?php
/**
 * @brief		GeoLocation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * GeoLocation
 */
class _GeoLocation
{
	/**
	 * @brief	Country Code List
	 */
	public static $countries = array(
		'AF', // Afghanistan
		'AX', // Åland Islands
		'AL', // Albania
		'DZ', // Algeria
		'AS', // American Samoa
		'AD', // Andorra
		'AO', // Angola
		'AI', // Anguilla
		'AQ', // Antarctica
		'AG', // Antigua and Barbuda
		'AR', // Argentina
		'AM', // Armenia
		'AW', // Aruba
		'AU', // Australia
		'AT', // Austria
		'AZ', // Azerbaijan
		'BS', // Bahamas
		'BH', // Bahrain
		'BD', // Bangladesh
		'BB', // Barbados
		'BY', // Belarus
		'BE', // Belgium
		'BZ', // Belize
		'BJ', // Benin
		'BM', // Bermuda
		'BT', // Bhutan
		'BO', // Bolivia, Plurinational State Of
		'BA', // Bosnia and Herzegovina
		'BW', // Botswana
		'BV', // Bouvet Island
		'BR', // Brazil
		'IO', // British Indian Ocean Territory
		'BN', // Brunei Darussalam
		'BG', // Bulgaria
		'BF', // Burkina Faso
		'BI', // Burundi
		'KH', // Cambodia
		'CM', // Cameroon
		'CA', // Canada
		'CV', // Cape Verde
		'BQ', // Caribbean Netherlands
		'KY', // Cayman Islands
		'CF', // Central African Republic
		'TD', // Chad
		'CL', // Chile
		'CN', // China
		'CX', // Christmas Island
		'CC', // Cocos (Keeling) Islands
		'CO', // Colombia
		'KM', // Comoros
		'CG', // Congo
		'CD', // Congo, The Democratic Republic Of The
		'CK', // Cook Islands
		'CR', // Costa Rica
		'CI', // Côte d’Ivoire
		'HR', // Croatia
		'CU', // Cuba
		'CW', // Curaçao
		'CY', // Cyprus
		'CZ', // Czech Republic
		'DK', // Denmark
		'DJ', // Djibouti
		'DM', // Dominica
		'DO', // Dominican Republic
		'EC', // Ecuador
		'EG', // Egypt
		'SV', // El Salvador
		'GQ', // Equatorial Guinea
		'ER', // Eritrea
		'EE', // Estonia
		'ET', // Ethiopia
		'FK', // Falkland Islands (Malvinas)
		'FO', // Faroe Islands
		'FJ', // Fiji
		'FI', // Finland
		'FR', // France
		'GF', // French Guiana
		'PF', // French Polynesia
		'TF', // French Southern Territories
		'GA', // Gabon
		'GM', // Gambia
		'GE', // Georgia
		'DE', // Germany
		'GH', // Ghana
		'GI', // Gibraltar
		'GR', // Greece
		'GL', // Greenland
		'GD', // Grenada
		'GP', // Guadeloupe
		'GU', // Guam
		'GT', // Guatemala
		'GG', // Guernsey
		'GN', // Guinea
		'GW', // Guinea-Bissau
		'GY', // Guyana
		'HT', // Haiti
		'HM', // Heard Island and McDonald Islands
		'VA', // Holy See (Vatican City State)
		'HN', // Honduras
		'HK', // Hong Kong
		'HU', // Hungary
		'IS', // Iceland
		'IN', // India
		'ID', // Indonesia
		'IR', // Iran, Islamic Republic Of
		'IQ', // Iraq
		'IE', // Ireland
		'IM', // Isle Of Man
		'IL', // Israel
		'IT', // Italy
		'JM', // Jamaica
		'JP', // Japan
		'JE', // Jersey
		'JO', // Jordan
		'KZ', // Kazakhstan
		'KE', // Kenya
		'KI', // Kiribati
		'KP', // Korea, Democratic People's Republic Of
		'KR', // Korea, Republic Of
		'KW', // Kuwait
		'KG', // Kyrgyzstan
		'LA', // Laos People's Democratic Republic
		'LV', // Latvia
		'LB', // Lebanon
		'LS', // Lesotho
		'LR', // Liberia
		'LY', // Libya
		'LI', // Liechtenstein
		'LT', // Lithuania
		'LU', // Luxembourg
		'MO', // Macao
		'MK', // Macedonia, The Former Yugoslav Republic Of
		'MG', // Madagascar
		'MW', // Malawi
		'MY', // Malaysia
		'MV', // Maldives
		'ML', // Mali
		'MT', // Malta
		'MH', // Marshall Islands
		'MQ', // Martinique
		'MR', // Mauritania
		'MU', // Mauritius
		'YT', // Mayotte
		'MX', // Mexico
		'FM', // Micronesia, Federated States Of
		'MD', // Moldova, Republic Of
		'MC', // Monaco
		'MN', // Mongolia
		'ME', // Montenegro
		'MS', // Montserrat
		'MA', // Morocco
		'MZ', // Mozambique
		'MM', // Myanmar
		'NA', // Namibia
		'NR', // Nauru
		'NP', // Nepal
		'NL', // Netherlands
		'NC', // New Caledonia
		'NZ', // New Zealand
		'NI', // Nicaragua
		'NE', // Niger
		'NG', // Nigeria
		'NU', // Niue
		'NF', // Norfolk Island
		'MP', // Northern Mariana Islands
		'NO', // Norway
		'OM', // Oman
		'PK', // Pakistan
		'PW', // Palau
		'PS', // Palestine, State of
		'PA', // Panama
		'PG', // Papua New Guinea
		'PY', // Paraguay
		'PE', // Peru
		'PH', // Philippines
		'PN', // Pitcairn
		'PL', // Poland
		'PT', // Portugal
		'PR', // Puerto Rico
		'QA', // Qatar
		'RE', // Réunion
		'RO', // Romania
		'RU', // Russian Federation
		'RW', // Rwanda
		'BL', // Saint Barthélemy
		'SH', // Saint Helena, Ascension and Tristan da Cunha
		'KN', // Saint Kitts and Nevis
		'LC', // Saint Lucia
		'MF', // Saint Martin
		'PM', // Saint Pierre and Miquelon
		'VC', // Saint Vincent and The Grenadines
		'WS', // Samoa
		'SM', // San Marino
		'ST', // Sao Tome and Principe
		'SA', // Saudi Arabia
		'SN', // Senegal
		'RS', // Serbia
		'SC', // Seychelles
		'SL', // Sierra Leone
		'SG', // Singapore
		'SX', // Sint Maarten
		'SK', // Slovakia
		'SI', // Slovenia
		'SB', // Solomon Islands
		'SO', // Somalia
		'ZA', // South Africa
		'GS', // South Georgia and The South Sandwich Islands
		'SS', // South Sudan
		'ES', // Spain
		'LK', // Sri Lanka
		'SD', // Sudan
		'SR', // Suriname
		'SJ', // Svalbard and Jan Mayen
		'SZ', // Swaziland
		'SE', // Sweden
		'CH', // Switzerland
		'SY', // Syrian Arab Republic
		'TW', // Taiwan, Province Of China
		'TJ', // Tajikistan
		'TZ', // Tanzania, United Republic Of
		'TH', // Thailand
		'TL', // Timor-Leste
		'TG', // Togo
		'TK', // Tokelau
		'TO', // Tonga
		'TT', // Trinidad and Tobago
		'TN', // Tunisia
		'TR', // Turkey
		'TM', // Turkmenistan
		'TC', // Turks and Caicos Islands
		'TV', // Tuvalu
		'UG', // Uganda
		'UA', // Ukraine
		'AE', // United Arab Emirates
		'GB', // United Kingdom
		'US', // United States
		'UM', // United States Minor Outlying Islands
		'UY', // Uruguay
		'UZ', // Uzbekistan
		'VU', // Vanuatu
		'VE', // Venezuela, Bolivarian Republic Of
		'VN', // Vietnam
		'VG', // Virgin Islands, British
		'VI', // Virgin Islands, U.S.
		'WF', // Wallis and Futuna
		'EH', // Western Sahara
		'YE', // Yemen
		'ZM', // Zambia
		'ZW', // Zimbabwe
	);
	
	/**
	 * @brief	State List
	 */
	public static $states = array(
		'AU' => array(
			'Australian Capital Territory',
			'New South Wales',
			'Northern Territory',
			'Queensland',
			'South Australia',
			'Tasmania',
			'Victoria',
			'Western Australia',
			),
		'BR' => array(
			'Acre',
			'Alagoas',
			'Amapá',
			'Amazonas',
			'Bahia',
			'Ceará',
			'Distrito Federal',
			'Espírito Santo',
			'Goiás',
			'Maranhão',
			'Mato Grosso',
			'Mato Grosso do Sul',
			'Minas Gerais',
			'Paraná',
			'Paraíba',
			'Pará',
			'Pernambuco',
			'Piauí',
			'Rio de Janeiro',
			'Rio Grande do Norte',
			'Rio Grande do Sul',
			'Rondônia',
			'Roraima',
			'Santa Catarina',
			'Sergipe',
			'São Paulo',
			'Tocantins'
		),
		'CA' => array(
			"Alberta",
			"British Columbia",
			"Manitoba",
			"New Brunswick",
			"Newfoundland and Labrador",
			"Northwest Territories",
			"Nova Scotia",
			"Nunavut",
			"Ontario",
			"Prince Edward Island",
			"Quebec",
			"Saskatchewan",
			"Yukon",
			),
		'PT' => array(
			"Aveiro",
			"Azores",
			"Beja",
			"Braga",
			"Braganca",
			"Castelo Branco",
			"Coimbra",
			"Evora",
			"Faro",
			"Guarda",
			"Leiria",
			"Lisboa",
			"Madeira Islands",
			"Portalegre",
			"Porto",
			"Santarem",
			"Setubal",
			"Viana do Castelo",
			"Vila Real",
			"Viseu",
			),
		'US' => array(
			"Alabama",
			"Alaska",
			"American Samoa",
			"Arizona",
			"Arkansas",
			"California",
			"Colorado",
			"Connecticut",
			"Delaware",
			"District of Columbia",
			"Federated States Of Micronesia",
			"Florida",
			"Georgia",
			"Guam",
			"Hawaii",
			"Idaho",
			"Illinois",
			"Indiana",
			"Iowa",
			"Kansas",
			"Kentucky",
			"Louisiana",
			"Maine",
			"Marshall Islands",
			"Maryland",
			"Massachusetts",
			"Michigan",
			"Minnesota",
			"Mississippi",
			"Missouri",
			"Montana",
			"Nebraska",
			"Nevada",
			"New Hampshire",
			"New Jersey",
			"New Mexico",
			"New York",
			"North Carolina",
			"North Dakota",
			"Northern Mariana Islands",
			"Ohio",
			"Oklahoma",
			"Oregon",
			"Palau",
			"Pennsylvania",
			"Puerto Rico",
			"Rhode Island",
			"South Carolina",
			"South Dakota",
			"Tennessee",
			"Texas",
			"Utah",
			"Vermont",
			"Virgin Islands",
			"Virginia",
			"Washington",
			"West Virginia",
			"Wisconsin",
			"Wyoming",
			"Armed Forces - Americas",
			"Armed Forces - Europe",
			"Armed Forces - Pacific",
			),
		);

	/**
	 * @brief	Latitude
	 */
	public $lat;
	
	/**
	 * @brief	Longitude
	 */
	public $long;
	
	/**
	 * @brief	Address Lines
	 */
	public $addressLines = array( NULL );
	
	/**
	 * @brief	City
	 */
	public $city;
	
	/**
	 * @brief	Region
	 */
	public $region;
	
	/**
	 * @brief	Country (2 character code)
	 */
	public $country;
	
	/**
	 * @brief	Postal Code
	 */
	public $postalCode;
		
	/**
	 * @brief	Map
	 */
	protected $map;
	
	/**
	 * Get by IP address
	 *
	 * @param	string	$ip	IP Address
	 * @return	\IPS\GeoLocation
	 * @throws	\BadFunctionCallException		Service is not available
	 * @throws	\IPS\Http\Request\Exception		Error communicating with external service
	 * @throws	\RuntimeException				Error within the external service 
	 * @throws	\OutOfRangeException			IP address is not in database
	 */
	public static function getByIp( $ip )
	{
		/* If the service is not turned on - throw an exception */
		if ( !\IPS\Settings::i()->ipsgeoip )
		{
			throw new \BadFunctionCallException;
		}
		
		/* If the license key is invalid or expired the service won't work, so throw an exception */
		$licenseData = \IPS\IPS::licenseKey();
		if( !$licenseData or !$licenseData['active'] )
		{
			throw new \BadFunctionCallException;
		}
		
		/* Check the cache */
		try
		{
			$data = \IPS\Db::i()->select( 'data', 'core_geoip_cache', array( 'ip_address=?', $ip ) )->first();
			if ( !$data )
			{
				throw new \UnderflowException;
			}
		}
		
		/* Not in the cache - get from tge external service */
		catch ( \UnderflowException $e )
		{
			/* Fetch */
			$response = \IPS\Http\Url::ips( 'geoip/' . urlencode( $ip ) )->request()->login( \IPS\Settings::i()->ipb_reg_number, '' )->get();
			
			/* If it's a 404, the IP doesn't exist, we still store NULL to prevent multiple calls */
			if ( $response->httpResponseCode == 404 )
			{
				$data = NULL;
			}
			
			/* If it's anything other than a 200, log it and throw exception */
			elseif ( $response->httpResponseCode != 200 )
			{
				\IPS\Log::log( "GeoIP Error\n\nRequested IP: {$ip}\n\nResponse:\n" . print_r( $response, TRUE ), 'geoip' );
				throw new \RuntimeException;
			} 
			
			/* Otherwise it's fine */
			else
			{
				$data = (string) $response;
			}
			
			/* Cache */
			\IPS\Db::i()->replace( 'core_geoip_cache', array(
				'ip_address'	=> $ip,
				'data'			=> $data,
				'date'			=> time()
			) );
		}
		
		/* If there's nothing, throw an exception */
		if ( !$data )
		{
			throw new \OutOfRangeException;
		}
		
		/* Return */
		return static::buildFromJson( $data );
	}

	/**
	 * Get by latitude and longitude
	 *
	 * @param	float	$lat	Latitude
	 * @param	float	$long	Longitude
	 * @return	\IPS\GeoLocation
	 * @throws	\BadFunctionCallException
	 * @throws	\IPS\Http\Request\Exception
	 */
	public static function getByLatLong( $lat, $long )
	{
		if ( \IPS\Settings::i()->googlemaps )
		{
			$data = \IPS\Http\Url::external( ( \IPS\Request::i()->isSecure()  ? 'https://' : 'http://' ) . "maps.googleapis.com/maps/api/geocode/json?latlng=" . urlencode( $lat ) . "," . urlencode( $long ) . "&sensor=false" )->request()->get()->decodeJson();

			$obj = new static;
			$obj->lat			= $lat;
			$obj->long			= $long;

			$_address	= '';

			/* Make sure the response from Google is valid */
			if( isset( $data['results'] ) AND is_array( $data['results'] ) AND count( $data['results'] ) )
			{
				foreach( $data['results'][0]['address_components'] as $component )
				{
					if( $component['types'][0] == 'street_number' )
					{
						$_address	= $component['long_name'];
					}
					elseif( $component['types'][0] == 'route' )
					{
						$_address	.= " " . $component['long_name'];
					}

					if( $component['types'][0] == 'postal_code' )
					{
						$obj->postalCode	= $component['long_name'];
					}

					if( $component['types'][0] == 'country' )
					{
						$obj->country	= $component['short_name'];
					}

					if( $component['types'][0] == 'administrative_area_level_1' )
					{
						$obj->region	= $component['short_name'];
					}

					if( $component['types'][0] == 'locality' )
					{
						$obj->city	= $component['short_name'];
					}
				}
			}

			if( $_address )
			{
				$obj->addressLines	= array( $_address );
			}

			return $obj;
		}
		else
		{
			throw new \BadFunctionCallException;
		}
	}

	/**
	 * Get the latitude and longitude for the current object. Address must be set.
	 *
	 * @return	void
	 * @throws	\BadMethodCallException
	 */
	public function getLatLong()
	{
		if ( \IPS\Settings::i()->googlemaps AND $this->toString() )
		{
			try
			{
				$data = \IPS\Http\Url::external( ( \IPS\Request::i()->isSecure()  ? 'https://' : 'http://' ) . "maps.googleapis.com/maps/api/geocode/json?address=" . urlencode( $this->toString() ) . "&sensor=false" )->request()->get()->decodeJson();
			}
			catch( \RuntimeException $e )
			{
				return;
			}
			
			if ( !count( $data['results'] ) )
			{
				return;
			}

			$this->lat	= $data['results'][0]['geometry']['location']['lat'];
			$this->long	= $data['results'][0]['geometry']['location']['lng'];
		}
		else
		{
			throw new \BadFunctionCallException;
		}
	}
	
	/**
	 * Build from JSON
	 *
	 * @param 	string	$json	JSON data
	 * @return	|IPS\GeoLocation
	 */
	public static function buildFromJson( $json )
	{
		$json = json_decode( $json, TRUE );
		$obj = new static;
		if ( !empty( $json ) )
		{
			foreach ( $json as $k => $v )
			{
				$obj->$k = $v;
			}
		}
		return $obj;
	}
	
	/**
	 * Get location
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->toString();
	}
	
	/**
	 * Convert to string
	 *
	 * @param	string	$separator	Separator
	 * @param	string|null	$name	Optional name to add to the address
	 * @return	string
	 * @note	While some places like France capitalize the surname, this cannot be done automatically because the surname could be supplied as the first or last
	 	name value, and the name could contain more than one string that constitutes the surname.
	 */
	public function toString( $separator=', ', $name=NULL )
	{
		$output	= array();

		if( $name !== NULL )
		{
			$output[]	= $name;
		}

		foreach ( array( 'addressLines', 'city', 'region', 'postalCode' ) as $k )
		{
			if ( $this->$k )
			{
				if ( is_array( $this->$k ) )
				{
					foreach ( $this->$k as $v )
					{
						if( $v )
						{
							$output[] = htmlspecialchars( $v, \IPS\HTMLENTITIES, 'UTF-8', FALSE );
						}
					}
				}
				else
				{
					$output[] = htmlspecialchars( $this->$k, \IPS\HTMLENTITIES, 'UTF-8', FALSE );
				}
			}
		}
		if ( $this->country and $this->country !== static::buildFromJson( \IPS\Settings::i()->site_address )->country )
		{
			$output[] = \IPS\Member::loggedIn()->language()->get( htmlspecialchars( 'country-' . $this->country, \IPS\HTMLENTITIES, 'UTF-8', FALSE ), FALSE, array( 'strtoupper' => TRUE ) );
		}
				
		if ( !empty( $output ) )
		{
			return implode( $separator, $output );
		}
		elseif ( $this->lat and $this->long )
		{
			return "{$this->lat},{$this->long}";
		}
		
		return '';
	}
	
	/**
	 * Build Map
	 *
	 * @return	\IPS\GeoLocation\Map
	 * @throws	\BadMethodCallException
	 */
	public function map()
	{
		if ( $this->map === NULL )
		{
			if ( \IPS\Settings::i()->googlemaps )
			{
				$this->map = new \IPS\GeoLocation\Maps\Google( $this );
			}
			else
			{
				throw new \BadMethodCallException;
			}
		}
		
		return $this->map;
	}

	/**
	 * Return value to use in template
	 *
	 * @param	string	$data	Data to parse
	 * @return	string
	 */
	public static function parseForOutput( $data )
	{
		$address	= json_decode( $data, TRUE );
		$mapper		= new static;
		
		if ( is_array( $address ) )
		{
			foreach( $address as $k => $v )
			{
				$mapper->$k = $v;
			}
		}
		
		return (string) $mapper;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse	float		lat				Latitude
	 * @apiresponse	float		long			Longitude
	 * @apiresponse	[string]	addressLines	Lines of the street address
	 * @apiresponse	string		city			City
	 * @apiresponse	string		region			State/Region
	 * @apiresponse	string		country			2-letter country code
	 * @apiresponse	string		postalCode		ZIP/Postal Code
	 */
	public function apiOutput()
	{
		return $this;
	}
}
