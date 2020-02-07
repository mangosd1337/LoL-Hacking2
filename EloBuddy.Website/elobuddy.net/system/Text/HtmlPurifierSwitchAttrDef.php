<?php
/**
 * @brief		A HTMLPurifier Attribute Definition which imitates HTMLPurifier_AttrDef_Switch but allows checking if certain attributes exist
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 May 2016
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
 * A HTMLPurifier Attribute Definition which imitates HTMLPurifier_AttrDef_Switch but allows checking if certain attributes exist
 */
class _HtmlPurifierSwitchAttrDef extends \HTMLPurifier_AttrDef_Switch
{
	public $required = FALSE;
	
	/**
	 * @brief	Attributes
	 */
	protected $attributes = array();
	
	/**
	 * Constructor
	 *
     * @param	string					$tag			The tag name to check
     * @param	array					$attributes		The attributes to check on
     * @param	\HTMLPurifier_AttrDef	$with_tag		If $tag matches and all $attributes are present, this definition will be used
     * @param	\HTMLPurifier_AttrDef	$without_tag	Otherwise, this definition will be used
     */
    public function __construct( $tag, $attributes, $with_tag, $without_tag )
    {
	    $this->attributes = $attributes;
		parent::__construct( $tag, $with_tag, $without_tag );
    }
    
	/**
	 * Validate
	 * 
     * @param	string					$string
     * @param	\HTMLPurifier_Config	$config
     * @param	\HTMLPurifier_Context	$context
     * @return	bool|string
     */
    public function validate( $string, $config, $context )
    {
	    $token = $context->get('CurrentToken', true);
	    
        if ( count( array_diff( $this->attributes, array_keys( $token->attr ) ) ) )
        {
			return $this->withoutTag->validate( $string, $config, $context );
        }
        
		return parent::validate( $string, $config, $context );
	}
}