<?php
/**
 * @brief		Member input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member input class for Form Builder
 */
class _Member extends Text
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'multiple'	=> 1,	// Maximum number of members. NULL for any. Default is 1.
	 	);
	 * @endcode
	 */
	public $childDefaultOptions = array(
		'multiple'	=> 1,
	);


	/**
	 * Constructor
	 * Adds 'confirm' to the available options
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		$args = func_get_args();
		
		$this->defaultOptions['autocomplete'] = array(
			'source' 				=>	'app=core&module=system&controller=ajax&do=findMember',
			'resultItemTemplate' 	=> 'core.autocomplete.memberItem',
			'unique'				=> true,
			'minAjaxLength'			=> 3,
			'disallowedCharacters'  => array()
		);
		if( isset( $args[3] ) and array_key_exists( 'multiple', $args[3] ) and $args[3]['multiple'] > 0 )
		{
			$this->defaultOptions['autocomplete']['maxItems'] = $args[3]['multiple'];
		}
		elseif ( !isset( $args[3] ) or !array_key_exists( 'multiple', $args[3] ) )
		{
			$this->defaultOptions['autocomplete']['maxItems'] = $this->childDefaultOptions['multiple'];
		}
		
		call_user_func_array( 'parent::__construct', $args );
	}
	
	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$value = $this->value;
		if ( is_array( $this->value ) )
		{
			$value = array();
			foreach ( $this->value as $v )
			{
				$value[] = ( $v instanceof \IPS\Member ) ? $v->name : $v;
			}
			$value = implode( ',', $value );
		}
		elseif ( $value instanceof \IPS\Member )
		{
			$value = $value->name;
		}
		
		/* 10/19/15 - adding htmlspecialchars around value if autocomplete is enabled so that html tag characters can be used (e.g. for members) */
		/* This value is decoded by the JS widget before use. See https://community.invisionpower.com/4bugtrack/active-reports/cannot-pm-certain-members-r6181/ */
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->text( $this->name, 'text', ( $this->options['autocomplete'] ? htmlspecialchars( $value, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ) : $value ), $this->required, $this->options['maxLength'], $this->options['size'], $this->options['disabled'], $this->options['autocomplete'], $this->options['placeholder'], NULL, NULL );
	}
	
	/**
	 * Format Value
	 *
	 * @return	\IPS\Member|NULL|FALSE
	 */
	public function formatValue()
	{
		if ( $this->value and !( $this->value instanceof \IPS\Member ) )
		{
			$return = array();
			
			foreach ( is_array( $this->value ) ? $this->value : explode( ',', $this->value ) as $v )
			{
				if ( $v instanceof \IPS\Member )
				{
					$return[ $v->member_id ] = $v;
				}
				elseif( $v )
				{
					$v = html_entity_decode( $v, ENT_QUOTES, 'UTF-8' );

					$member = \IPS\Member::load( $v, 'name' );
					if ( $member->member_id )
					{
						if ( $this->options['multiple'] === 1 )
						{
							return $member;
						}
						$return[ $member->member_id ] = $member;
					}
				}
			}

			if ( !empty( $return ) )
			{
				return ( $this->options['multiple'] === NULL or $this->options['multiple'] == 0 ) ? $return : array_slice( $return, 0, $this->options['multiple'] );
			}
		}
		
		return $this->value;
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		parent::validate();
		
		if ( $this->value !== '' and !( $this->value instanceof \IPS\Member ) and !is_array( $this->value ) )
		{
			throw new \InvalidArgumentException('form_member_bad');
		}
	}
	
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return	string
	 */
	public static function stringValue( $value )
	{
		if ( is_array( $value ) )
		{
			return implode( ',', array_map( function( $v )
			{
				return $v->member_id;
			}, $value ) );
		}
		
		return $value ? (string) $value->member_id : NULL;
	}
}