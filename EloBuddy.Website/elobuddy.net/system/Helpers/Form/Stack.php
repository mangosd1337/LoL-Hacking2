<?php
/**
 * @brief		Stack input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Apr 2013
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
 * Stack input class for Form Builder
 */
class _Stack extends FormAbstract
{
	/**
	 * @brief	Default Options
	 */
	protected $defaultOptions = array(
		'stackFieldType'	=> 'Text',
        'removeEmptyValues' => TRUE,
        'maxItems'			=> NULL
	);

	/**
	 * Constructor: Store the form element to stack
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @param	string			$name					Name
	 * @param	mixed			$defaultValue			Default value
	 * @param	bool			$required				Required?
	 * @param	array			$options				Type-specific options
	 * @param	callback		$customValidationCode	Custom validation code
	 * @param	string			$prefix					HTML to show before input field
	 * @param	string			$suffix					HTML to show after input field
	 * @param	string			$id						The ID to add to the row
	 * @return	void
	 */
	public function __construct( $name, $defaultValue=NULL, $required=FALSE, $options=array(), $customValidationCode=NULL, $prefix=NULL, $suffix=NULL, $id=NULL )
	{	
		call_user_func_array( 'parent::__construct', func_get_args() );
		
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery-ui.js', 'core', 'interface' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery-touchpunch.js', 'core', 'interface' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery.menuaim.js', 'core', 'interface' ) );
		
		/* Test for javascript disabled add stack */
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' )
		{
			if ( ( \IPS\Request::i()->valueFromArray('form_remove_stack') !== NULL OR isset( \IPS\Request::i()->form_add_stack ) ) and \IPS\Request::i()->csrfKey === \IPS\Session::i()->csrfKey )
			{
				$this->reloadForm = true;
			}
		}
	}

	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$fields		= array();
		$classType	= mb_strpos( $this->options['stackFieldType'], '\\' ) === FALSE ? ( "\\IPS\\Helpers\\Form\\" . $this->options['stackFieldType'] ) : $this->options['stackFieldType'];
		$remove     = \IPS\Request::i()->valueFromArray('form_remove_stack');
		
		/* The JS fallback needs a unique key for the field to remove */
		$remove = ( is_array( $remove ) ) ? key( $remove ) : null;
		
		if( count($this->value) )
		{
			foreach( $this->value as $k => $v )
			{
				$class = ( new $classType( $this->name . '[' . count($fields) . ']', $v, FALSE, $this->options, $this->customValidationCode, $this->prefix, $this->suffix, $this->htmlId  . '_' . count($fields) ) );
				$html = $class->html();
				
				if( !\IPS\Login::compareHashes( $remove, md5( $html ) ) )
				{
					$fields[] = $html;
				}
			}
		}
		else
		{
			$class = ( new $classType( $this->name . '[0]', NULL, FALSE, $this->options, $this->customValidationCode, $this->prefix, $this->suffix, $this->htmlId  . '_' . count($fields) ) );
			$html = $class->html();
			
			if( !\IPS\Login::compareHashes( $remove, md5( $html ) ) )
			{
				$fields[] = $html;
			}
		}
		
		/* We hit the add stack button with JS disabled */
		if ( $this->reloadForm === TRUE AND $remove === NULL )
		{
			$class = ( new $classType( $this->name . '[]', NULL, FALSE, $this->options, $this->customValidationCode, $this->prefix, $this->suffix, $this->htmlId  . '_' . count($fields) ) );
			$html     = $class->html();
			$fields[] = $html;
		}

		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->stack( $this->name, $fields, $this->options );
	}

	/**
	 * Format Value
	 *
	 * @return	mixed
	 */
	public function formatValue()
	{
		$values		= array();
		$classType	= mb_strpos( $this->options['stackFieldType'], '\\' ) === FALSE ? ( "\\IPS\\Helpers\\Form\\" . $this->options['stackFieldType'] ) : $this->options['stackFieldType'];
		$name		= $this->name;

		if( mb_substr( $name, 0, 8 ) !== '_new_[x]' and isset( \IPS\Request::i()->$name ) )
		{
			if( is_array( \IPS\Request::i()->$name ) AND count( \IPS\Request::i()->$name ) )
			{ 
				foreach( \IPS\Request::i()->$name as $k => $v )
				{
					$class = ( new $classType( $this->name . '[]', $v, FALSE, $this->options, $this->customValidationCode, $this->prefix, $this->suffix, $this->htmlId ) );
					$values[]	= $class->value;
				}
			}

            return ( $this->options['removeEmptyValues'] ) ? array_filter( $values ) : $values;
		}
		else if ( is_array( $this->value ) AND count( $this->value ) )
		{
			foreach( $this->value as $k => $v )
			{
				$class = ( new $classType( $this->name . '[]', $v, FALSE, $this->options, $this->customValidationCode, $this->prefix, $this->suffix, $this->htmlId ) );
				$values[]	= $class->value;
			}

			return ( $this->options['removeEmptyValues'] ) ? array_filter( $values ) : $values;
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		if ( empty( $this->value ) and $this->required )
		{
			throw new \InvalidArgumentException('form_required');
		}
		
		if ( $this->options['maxItems'] !== NULL and count( $this->value ) > $this->options['maxItems'] )
		{
			throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'form_tags_max', FALSE, array( 'pluralize' => array( $this->options['maxItems'] ) ) ) );
		}

		parent::validate();
	}
}