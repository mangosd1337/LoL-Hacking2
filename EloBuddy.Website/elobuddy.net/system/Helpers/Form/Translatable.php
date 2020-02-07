<?php
/**
 * @brief		Translatable text input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Mar 2013
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
 * Translatable text input class for Form Builder
 */
class _Translatable extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'key'			=> 'foo',		// Language key
	 		'editor'		=> array(...),	// If this needs to be an editor rather than a textbox, all the options of \IPS\Helpers\Form\Editor are available here
	 		'textArea'		=> FALSE,		// Makes a textarea rather than a textbox
	 		'placeholder'	=> 'Example',	// Placeholder
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'key'			=> NULL,
		'editor'		=> NULL,
		'textArea'		=> FALSE,
		'placeholder'	=> NULL
	);
	
	/**
	 * @brief	Editors
	 */
	protected $editors = array();
	
	/**
	 * Constructor
	 * Sets that the field is required if there is a minimum length and vice-versa
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		/* Call parent constructor */
		call_user_func_array( 'parent::__construct', func_get_args() );
		
		/* Get current values */
		if ( $this->value === NULL )
		{
			$values = array();
			if ( $this->options['key'] )
			{
				foreach( \IPS\Db::i()->select( '*', 'core_sys_lang_words', array( 'word_key=?', $this->options['key'] ) )->setKeyField('lang_id') as $k => $v )
				{
					$v = $v['word_custom'] ?: $v['word_default'];
					if ( $v or !isset( $values[ $k ] ) )
					{
						$values[ $k ] = $v;
					}
				}
			}
			
			$this->value = $values;
		}
		elseif ( is_string( $this->value ) )
		{
			$values = array();
			foreach ( \IPS\Lang::getEnabledLanguages() as $lang )
			{
				$values[ $lang->id ] = $this->value;
			}
			$this->value = $values;
		}
		
		/* Init editors */
		if ( isset( $this->options['editor'] ) )
		{
			foreach ( \IPS\Lang::getEnabledLanguages() as $lang )
			{
				$options = $this->options['editor'];
				$options['autoSaveKey'] .= $lang->id;				
				$this->editors[ $lang->id ] = new Editor( "{$this->name}[{$lang->id}]", isset( $this->value[ $lang->id ] ) ? $this->value[ $lang->id ] : NULL, $this->required, $options );
			}
		}
			
		/* Add flags.css */
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'flags.css', 'core', 'global' ) );
	}
		
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{		
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->translatable( $this->name, \IPS\Lang::getEnabledLanguages(), $this->value, $this->editors, $this->options['placeholder'], $this->options['textArea'], $this->required );
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
		
		if ( $this->required )
		{
			if ( ! trim( $this->value[ \IPS\Lang::defaultLanguage() ] ) )
			{
				throw new \InvalidArgumentException('form_required');
			}
		}
		
		return TRUE;
	}
}