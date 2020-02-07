<?php
/**
 * @brief		Custom Field Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		11 Apr 2013
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
 * Custom Field Node
 */
abstract class _CustomField extends \IPS\Node\Model
{
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static $langKey;

	/**
	 * @brief	[CustomField] Column Map
	 */
	public static $databaseColumnMap = array(
		'not_null'	=> 'not_null'
	);
	
	/**
	 * @brief	[CustomField] Additional Field Classes
	 */
	public static $additionalFieldTypes = array();
	
	/**
	 * @brief	[CustomField] Additional Field Toggles
	 */
	public static $additionalFieldToggles = array();
	
	/**
	 * Get
	 *
	 * @param	string	$key	Key
	 * @return	mixed	$value	Value
	 */
	public function __get( $key )
	{
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$key = static::$databaseColumnMap[ $key ];
		}
		
		return parent::__get( $key );
	}
	
	/**
	 * Set
	 *
	 * @param	string	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( $key, $value )
	{
		if ( isset( static::$databaseColumnMap[ $key ] ) )
		{
			$key = static::$databaseColumnMap[ $key ];
		}

		if( $value instanceof \IPS\Node\Model )
		{
			$value = $value->_id;
		}
		
		return parent::__set( $key, $value );
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{		
		$form->addHeader( 'pfield_settings' );
		$form->add( new \IPS\Helpers\Form\Translatable( 'pf_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'pf_desc', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ? static::$langKey . '_' . $this->id . '_desc' : NULL ) ) ) );
		
		if ( isset( static::$parentNodeClass ) )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'pf_group_id', $this->group_id, TRUE, array( 'class' => static::$parentNodeClass, 'subnodes' => FALSE  ) ) );
		}
		
		$options = array_merge( array(
			'Address'		=> 'pf_type_Address',
			'Checkbox'		=> 'pf_type_Checkbox',
			'CheckboxSet'	=> 'pf_type_CheckboxSet',
			'Codemirror'	=> 'pf_type_Codemirror',
			'Color'			=> 'pf_type_Color',
			'Date'			=> 'pf_type_Date',
			'Editor'		=> 'pf_type_Editor',
			'Email'			=> 'pf_type_Email',
			'Member'		=> 'pf_type_Member',
			'Number'		=> 'pf_type_Number',
			'Password'		=> 'pf_type_Password',
			'Poll'			=> 'pf_type_Poll',
			'Radio'			=> 'pf_type_Radio',
			'Rating'		=> 'pf_type_Rating',
			'Select'		=> 'pf_type_Select',
			'Tel'			=> 'pf_type_Tel',
			'Text'			=> 'pf_type_Text',
			'TextArea'		=> 'pf_type_TextArea',
			'Upload'		=> 'pf_type_Upload',
			'Url'			=> 'pf_type_Url',
			'YesNo'			=> 'pf_type_YesNo',
		), static::$additionalFieldTypes );
		
		$toggles = array(
			'CheckboxSet'	=> array( 'pf_content', 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Codemirror'	=> array( 'pf_not_null', 'pf_max_input', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Email'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Member'		=> array( 'pf_not_null', 'pf_multiple', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Password'		=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Select'		=> array( 'pf_not_null', 'pf_content', 'pf_multiple', 'pf_search_type', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Tel'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Text'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'TextArea'		=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Url'			=> array( 'pf_not_null', 'pf_max_input', 'pf_input_format', 'pf_search_type', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Radio'			=> array( 'pf_content', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Address'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Color'			=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Date'			=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Editor'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format', 'pf_search_type' ),
			'Number'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Poll'			=> array( 'pf_not_null' ),
			'Rating'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
			'Upload'		=> array( 'pf_not_null', "{$form->id}_header_pfield_displayoptions", 'pf_format' ),
		);
		foreach ( static::$additionalFieldTypes as $k => $v )
		{
			$toggles[ $k ] = isset( static::$additionalFieldToggles[ $k ] ) ? static::$additionalFieldToggles[ $k ] : array( 'pf_not_null' );
		}
		
		ksort( $options );

		if ( !$this->_new )
		{
			\IPS\Member::loggedIn()->language()->words['pf_type_warning']	= \IPS\Member::loggedIn()->language()->addToStack('custom_field_change');
			
			foreach ( $toggles as $k => $_toggles )
			{
				if ( !$this->canKeepValueOnChange( $k ) )
				{
					$toggles[ $k ][] = 'form_' . $this->id . '_pf_type_warning';
				}
			}
		}

		$form->add( new \IPS\Helpers\Form\Select( 'pf_type', $this->id ? $this->type : 'Text', TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'pf_content', $this->id ? json_decode( $this->content, TRUE ) : array(), FALSE, array( 'removeEmptyValues' => FALSE ), NULL, NULL, NULL, 'pf_content' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'pf_multiple', $this->id ? $this->multiple : FALSE, FALSE, array(), NULL, NULL, NULL, 'pf_multiple' ) );

        $requiredColumn = ( isset( $this::$databaseColumnMap['not_null'] ) and $this::$databaseColumnMap['not_null'] ) ? $this::$databaseColumnMap['not_null'] : NULL;

        $form->add( new \IPS\Helpers\Form\YesNo( 'pf_not_null', $this->id ? $this->$requiredColumn : TRUE, FALSE, array(), NULL, NULL, NULL, 'pf_not_null' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'pf_max_input', $this->id ? $this->max_input : 0, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'pf_max_input' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'pf_input_format', $this->id ? $this->input_format : NULL, FALSE, array( 'placeholder' => '/[A-Z0-9]+/i' ), function( $val )
		{
			if ( $val AND @preg_match( $val, NULL ) === false )
			{
				throw new \DomainException('form_bad_value');
			}
		}, NULL, NULL, 'pf_input_format' ) );
		$form->addHeader( 'pfield_displayoptions' );
		$form->add( new \IPS\Helpers\Form\Select( 'pf_search_type', $this->id ? $this->search_type : 'loose', FALSE, array( 'options' => array( 'exact' => 'pf_search_type_exact', 'loose' => 'pf_search_type_loose', '' => 'pf_search_type_none' ) ), NULL, NULL, NULL, 'pf_search_type' ) );
	}
	
	/**
	 * Does the change mean wiping the value?
	 *
	 * @param	string	$newType	The new type
	 * @return	array
	 */
	protected function canKeepValueOnChange( $newType )
	{
		if ( in_array( $newType, array_keys( static::$additionalFieldTypes ) ) )
		{
			return $newType === $this->type;
		}
		
		switch ( $this->type )
		{
			case 'Address':
				return in_array( $newType, array( 'Address' ) );
			
			case 'Checkbox':
				return in_array( $newType, array( 'Checkbox', 'YesNo' ) );
				
			case 'CheckboxSet':
				return in_array( $newType, array( 'CheckboxSet', 'Select' ) );
			
			case 'Code':
			case 'Codemirror':
				return in_array( $newType, array( 'Code', 'Codemirror', 'Editor', 'TextArea' ) );
				
			case 'Color':
				return in_array( $newType, array( 'Color', 'Text' ) );
				
			case 'Date':
				return in_array( $newType, array( 'Date', 'Text' ) );
				
			case 'Editor':
				return in_array( $newType, array( 'Code', 'Editor', 'TextArea' ) );
			
			case 'Email':
				return in_array( $newType, array( 'Email', 'Password', 'Text' ) );
				
			case 'Item':
				return in_array( $newType, array( 'Item', 'TextArea' ) );
			
			case 'Member':
				return in_array( $newType, array( 'Member', 'Text', 'TextArea', 'Editor' ) );
				
			case 'Number':
				return in_array( $newType, array( 'Number', 'Password', 'Rating', 'Text', 'Tel' ) );
				
			case 'Password':
				return in_array( $newType, array( 'Number', 'Password', 'Text', 'Tel' ) );
				
			case 'Poll':
				return in_array( $newType, array( 'Poll' ) );
				
			case 'Radio':
				return in_array( $newType, array( 'Radio', 'Select' ) );
				
			case 'Rating':
				return in_array( $newType, array( 'Number', 'Password', 'Rating', 'Text', 'Tel' ) );
				
			case 'Select':
				return in_array( $newType, array( 'Select', 'CheckboxSet' ) );
				
			case 'Tel':
				return in_array( $newType, array( 'Password', 'Rating', 'Text', 'Tel' ) );
				
			case 'Text':
				return in_array( $newType, array( 'Email', 'Password', 'Text', 'Tel', 'Url', 'Editor', 'TextArea', 'Code', 'Codemirror' ) );

			case 'TextArea':
				return in_array( $newType, array( 'Code', 'Editor', 'TextArea' ) );

			case 'Upload':
				return in_array( $newType, array( 'Upload' ) );

			case 'Url':
				return in_array( $newType, array( 'Text', 'Url' ) );
			
			case 'YesNo':
				return in_array( $newType, array( 'Checkbox', 'YesNo' ) );
		}
		
		return FALSE;
	}


	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		$column = isset( $this->column ) ? $this->column : "field_{$this->id}";

		/* Checkbox sets are always multiple */
		if ( isset( $values['pf_type'] ) AND $values['pf_type'] === 'CheckboxSet' )
		{
			$values['pf_multiple'] = TRUE;
		}

		if( isset( $values['pf_multiple'] ) )
		{
			$values['pf_multiple']	= $values['pf_multiple'] ? TRUE : FALSE;
		}

		/* Add/Update the content table */
		if ( isset( static::$contentDatabaseTable ) AND isset( $values['pf_type'] ) )
		{
			$columnDefinition = array( 'name' => $column );
			switch ( $values['pf_type'] )
			{
				case 'CheckboxSet':
				case 'Member':
					if ( $values['pf_multiple'] )
					{
						$columnDefinition['type']	= 'TEXT';
					}
					else
					{
						$columnDefinition['type']	= 'INT';
						$columnDefinition['length']	= 10;
					}
					break;
					
				case 'Date':
				case 'Poll':
					$columnDefinition['type'] = 'INT';
					$columnDefinition['length'] = 10;
					break;
				
				case 'Editor':
				case 'TextArea':
				case 'Upload':
				case 'Address':
				case 'Codemirror':
				case 'Select':
					$columnDefinition['type'] = 'TEXT';
					break;
				
				case 'Email':
				case 'Password':
				case 'Tel':
				case 'Text':
				case 'Url':
				case 'Color':
				case 'Radio':
				case 'Number':
					$columnDefinition['type'] = 'VARCHAR';
					$columnDefinition['length'] = 255;
					break;
				
				case 'YesNo':
				case 'Checkbox':
				case 'Rating':
					$columnDefinition['type'] = 'TINYINT';
					$columnDefinition['length'] = 1;
					break;
			}
			if ( isset( $values['pf_max_input'] ) and $values['pf_max_input'] )
			{
				$columnDefinition['length'] = $values['pf_max_input'];
			}
			
			if ( !$this->id )
			{
				$this->save();
				$columnDefinition['name'] = isset( $this->column ) ? $this->column : "field_{$this->id}";
				
				\IPS\Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );
				
				if ( $values['pf_type'] != 'Upload' )
				{
					if ( $columnDefinition['type'] == 'TEXT' )
					{
						\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
					}
					else
					{
						\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $columnDefinition['name'], 'columns' => array( $columnDefinition['name'] ) ) );
					}
				}
			}
			elseif( !$this->canKeepValueOnChange( $values['pf_type'] ) )
			{
				try
				{
					\IPS\Db::i()->dropIndex( static::$contentDatabaseTable, $column );
					\IPS\Db::i()->dropColumn( static::$contentDatabaseTable, $column );
				} 
				catch ( \IPS\Db\Exception $e )
				{

				}

				\IPS\Db::i()->addColumn( static::$contentDatabaseTable, $columnDefinition );

				if ( $values['pf_type'] != 'Upload' )
				{
					if ( $columnDefinition['type'] == 'TEXT' )
					{
						\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $column, 'columns' => array( $column ) ) );
					}
					else
					{
						\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $column, 'columns' => array( $column ) ) );
					}
				}
			}
			else
			{
				\IPS\Db::i()->dropIndex( static::$contentDatabaseTable, $column );

				\IPS\Db::i()->changeColumn( static::$contentDatabaseTable, $column, $columnDefinition );

				if ( $columnDefinition['type'] == 'TEXT' )
				{
					\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $column, 'columns' => array( $column ) ) );
				}
				else
				{
					\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $column, 'columns' => array( $column ) ) );
				}
			}
		}
		elseif ( !$this->id )
		{
			$this->save();
		}
				
		/* Save the name and description */
		if( isset( $values['pf_title'] ) )
		{
			\IPS\Lang::saveCustom( 'core', static::$langKey . '_' . $this->id, $values['pf_title'] );
			unset( $values['pf_title'] );
		}

		if( isset( $values['pf_desc'] ) )
		{
			\IPS\Lang::saveCustom( 'core', static::$langKey . '_' . $this->id . '_desc', $values['pf_desc'] );
			unset( $values['pf_desc'] );
		}
		
		/* And the other fields */
		if ( isset( static::$parentNodeClass ) AND isset( $values['pf_group_id'] ) )
		{
			$values['group_id'] = is_object( $values['pf_group_id'] ) ? $values['pf_group_id']->_id : $values['pf_group_id'];
		}
		
		/* "Required" means nothing for radio and checkbox */
		if( isset( $values['pf_type'] ) and in_array( $values['pf_type'], array( 'Radio', 'Checkbox' ) ) )
		{
			$values['pf_not_null'] = FALSE;
		}

		/* Translate keys */
		foreach ( array( 'type', 'content', 'multiple', 'not_null', 'max_input', 'input_format', 'search_type', 'format' ) as $k )
		{
			if ( array_key_exists( "pf_{$k}", $values ) )
			{
				$_value = $values[ "pf_{$k}" ];
				unset( $values[ "pf_{$k}" ] );
				
				$values[ static::$databasePrefix . $k ] = ( $k === 'content' ? json_encode( $_value ) : $_value );
			}
		}
		
		unset( $values['pf_change_fieldtype'] );

		return $values;
	}
	
	/**
	 * [Node] Get Node Title
	 *
	 * @return	string
	 */
	protected function get__title()
	{
		if ( !$this->id )
		{
			return '';
		}
		
		return \IPS\Member::loggedIn()->language()->addToStack( static::$langKey . '_' . $this->id );
	}

	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		return FALSE;
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions()
	{
		return false;
	}
	
	/**
	 * [ActiveRecord] Duplicate
	 *
	 * @return	void
	 */
	public function __clone()
	{
		if ( $this->skipCloneDuplication === TRUE )
		{
			return;
		}

		$oldId = $this->id;
		parent::__clone();
		$column = isset( $this->column ) ? $this->column : "field_{$this->id}";
		
		if ( isset( static::$contentDatabaseTable ) )
		{
			$definition = \IPS\Db::i()->getTableDefinition( static::$contentDatabaseTable, TRUE );

			$fieldDefinition = $definition['columns']["field_" . $oldId];
			$fieldDefinition['name'] = $column;

			\IPS\Db::i()->addColumn( static::$contentDatabaseTable, $fieldDefinition );

			if ( $this->type != 'Upload' )
			{
				if ( $fieldDefinition['type'] == 'TEXT' )
				{
					\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'fulltext', 'name' => $column, 'columns' => array( $column ) ) );
				}
				else
				{
					\IPS\Db::i()->addIndex( static::$contentDatabaseTable, array( 'type' => 'key', 'name' => $column, 'columns' => array( $column ) ) );
				}
			}
		}

		\IPS\Lang::saveCustom( 'core', static::$langKey . '_' . $this->id, iterator_to_array( \IPS\Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$langKey . '_' . $oldId ) )->setKeyField( 'lang_id' )->setValueField( 'word_custom' ) ) );
		\IPS\Lang::saveCustom( 'core', static::$langKey . '_' . $this->id . '_desc', iterator_to_array( \IPS\Db::i()->select( 'word_custom, lang_id', 'core_sys_lang_words', array( 'word_key=?', static::$langKey . '_' . $oldId . '_desc' ) )->setKeyField( 'lang_id' )->setValueField( 'word_custom' ) ) );
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		$column = isset( $this->column ) ? $this->column : "field_{$this->id}";

		parent::delete();
		
		\IPS\Lang::deleteCustom( 'core', static::$langKey . '_' . $this->id );
		\IPS\Lang::deleteCustom( 'core', static::$langKey . '_' . $this->id . '_desc' );
		
		if ( isset( static::$contentDatabaseTable ) )
		{
			\IPS\Db::i()->dropColumn( static::$contentDatabaseTable, $column );
		}
	}
	
	/**
	 * Build Form Helper
	 *
	 * @param	mixed		$value					The value
	 * @param	callback	$customValidationCode	Custom validation code
	 * @return \IPS\Helpers\Form\FormAbstract
	 */
	public function buildHelper( $value=NULL, $customValidationCode=NULL )
	{
		$class = '\IPS\Helpers\Form\\' . $this->type;
			
		$options = array();
		switch ( $this->type )
		{
			case 'Date':
				// If the user has a custom profile field specifying a date (for example, their anniversary), you would expect
				// that date to display the same to all users regardless of their timezone.
				// To faciliate this, the timestamp for the inputted date UTC is saved, rather that the inputted date in the submitting user's timezone
				// displayValue will then not apply the viewing user's timestamp.
				$options['timezone'] = new \DateTimeZone('UTC');
				
				if ( is_numeric( $value ) )
				{
					$value = \IPS\DateTime::ts( $value ); 
				}
				break;
			
			case 'Email':
			case 'Password':
			case 'Tel':
			case 'Text':
			case 'TextArea':
			case 'Url':
				$options['maxLength']	= $this->max_input ?: NULL;
				$options['regex']		= $this->input_format ?: NULL;
				break;
			
			case 'CheckboxSet':
				$options['multiple'] = TRUE;
				$options['noDefault'] = TRUE;
				$options['options'] = json_decode( $this->content, TRUE );

				if ( $this->multiple )
				{
					if( $value !== NULL AND $value !== '' )
					{
						$value = explode( ',', htmlspecialchars( $value, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) );
					}

					$options['multiple'] = $this->multiple;
				}
				break;
			case 'Select':
				$options['options'] = json_decode( $this->content, TRUE );
				$options['options'] = ( $options['options'] ) ? array_combine( $options['options'], $options['options'] ) : array();
				
				$requiredColumn = ( isset( $this::$databaseColumnMap['not_null'] ) and $this::$databaseColumnMap['not_null'] ) ? $this::$databaseColumnMap['not_null'] : NULL;
				if ( !$this->$requiredColumn AND !$this->multiple )
				{
					array_unshift( $options['options'], '' );
				}

				if ( $this->multiple )
				{
					$options['noDefault'] = TRUE;
					
					if( $value !== NULL AND $value !== '' )
					{
						$values = explode( ',', $value );
						$value = array();
						foreach( $values as $val )
						{
							if ( is_numeric( $val ) and intval( $val ) == $val )
							{
								$value[] = intval( $val );
							}
							else
							{
								$value[] = $val;
							}
						}
					}
										
					$options['multiple'] = $this->multiple;
				}
				else
				{
					if ( is_numeric( $value ) and intval( $value ) == $value )
					{
						$value = intval( $value );
					}
				}

				break;

			case 'Radio':
				$options['returnLabels'] = TRUE;
				$options['options'] = json_decode( $this->content, TRUE );
				break;
				
			case 'Upload':
				$options['storageExtension'] = static::$uploadStorageExtension;
				if( $value )
				{
					try
					{
						$value = \IPS\File::get( static::$uploadStorageExtension, $value );
					}
					catch ( \OutOfRangeException $e )
					{
						$value = NULL;
					} 
				}
				break;
				
			case 'Editor':
				$options = static::$editorOptions;
				
				if ( !isset( $options['autoSaveKey'] ) )
				{
					$options['autoSaveKey'] = md5( get_class( $this ) . '-' . $this->id );
				}
				break;
			
			case 'Address':
				$value = \IPS\GeoLocation::buildFromJson( $value );
				break;
				
			case 'Member':
				$options['multiple'] = $this->multiple ? NULL : 1;
				if ( $value )
				{
					$value = array_map( function( $id )
					{
						return \IPS\Member::load( $id );
					}, explode( ',', $value ) );
				}
				break;
				
			case 'Poll':
				if ( $value )
				{
					try
					{
						$value = \IPS\Poll::load( $value );
					}
					catch ( \OutOfRangeException $e )
					{
						$value = NULL;
					} 
				}
				break;
		}
		
		/* Editor form field names and IDs should differ */
		if ( $this->type == 'Editor' )
		{
			return new $class( static::$langKey . '_' . $this->id, $value, $this->not_null, $options, $customValidationCode, NULL, NULL, static::$langKey . '_' . $this->id . '_editor' );
		}
		else
		{
			return new $class( static::$langKey . '_' . $this->id, $value, $this->not_null, $options, $customValidationCode, NULL, NULL, static::$langKey . '_' . $this->id );
		}
	}
	
	/**
	 * Claim attachments for an editor field
	 *
	 * @param	int|NULL	$id1	ID 1	(ID 2 will be the field ID)
	 * @param	mixed		$id3	ID 3
	 * @return	void
	 */
	public function claimAttachments( $id1 = NULL, $id3 = NULL )
	{
		$options = static::$editorOptions;
		
		if ( !isset( $options['autoSaveKey'] ) )
		{
			$options['autoSaveKey'] = md5( get_class( $this ) . '-' . $this->id );
		}

		\IPS\File::claimAttachments( $options['autoSaveKey'], $id1, $this->id, $id3 );
	}
	
	/**
	 * Display Value
	 *
	 * @param	mixed	$value	The value
	 * @return	string
	 */
	public function displayValue( $value=NULL )
	{
		switch ( $this->type )
		{
			case 'Address':
				return \IPS\GeoLocation::buildFromJson( $value )->toString( ', ' );
			
			case 'Checkbox':
			case 'YesNo':
				return $value ? \IPS\Member::loggedIn()->language()->addToStack('yes') : \IPS\Member::loggedIn()->language()->addToStack('no');
			
			case 'CheckboxSet':
			case 'Select':
				if ( isset( $this->extra[ $value ] ) )
				{
					$value = $this->extra[ $value ];
				}
				else
				{
					$options	= json_decode( $this->content, TRUE );

					/* Options are based on numeric indices, however when creating the original form field we may have added
						a dummy option to the beginning - we need to do the same here or else index '5' is not the same index '5'
						we originally selected from the form element */
					if( $this->type == 'Select' )
					{
						$requiredColumn = ( isset( $this::$databaseColumnMap['not_null'] ) and $this::$databaseColumnMap['not_null'] ) ? $this::$databaseColumnMap['not_null'] : NULL;
						if ( !$this->$requiredColumn AND !$this->multiple )
						{
							array_unshift( $options, '' );
						}
					}

					if( $this->multiple )
					{
						$_values = array();

						foreach( explode( ',', $value ) as $_value )
						{
							$_values[]	= ( isset( $options[ $_value ] ) ) ? $options[ $_value ] : $_value;
						}

						$value = implode( ',', $_values );
					}
					else
					{
						$value		= ( isset( $options[ $value ] ) ) ? $options[ $value ] : $value;
					}
				}
				
				if ( $this->multiple )
				{
					return implode( '<br>', explode( ',', htmlspecialchars( $value, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) ) );
				}
				else
				{
					return htmlspecialchars( $value, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE );
				}
			
			case 'Codemirror':
				return \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->prettyprint( $value );
			
			case 'Color':
				return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->colorDisplay( $value );
			
			case 'Date':
				return \IPS\DateTime::ts( $value, TRUE )->localeDate(); // See buildHelper for why we ignore the timezone
			
			case 'Editor':
				return $value;
				
			case 'Member':
				return implode( '<br>', array_map( function( $id )
				{
					return \IPS\Member::load( $id )->link();
				}, explode( ',', $value ) ) );
				
			case 'Poll':
				return $value ? ( (string) \IPS\Poll::load( $value ) ) : NULL;
			
			case 'Rating':
				return \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->rating( $this->options['max'], $value );
				
			case 'TextArea':
				return nl2br( htmlspecialchars( $value, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ) );
				
			case 'Url':
				$munged = ( $value ) ? \IPS\Http\Url::external( $value )->makeSafeForAcp() : NULL;
				return ( $value ) ? \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $munged, TRUE, $value ) : NULL;
							
			case 'Upload':
				$file = \IPS\File::get( static::$uploadStorageExtension, $value );
				$downloadUrl = \IPS\Http\Url::internal( 'applications/core/interface/file/cfield.php', 'none' )->setqueryString( array(
					'storage'	=> $file->storageExtension,
					'path'		=> (string) $file,
				) );
				return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->uploadDisplay( $file, $downloadUrl );

			case 'Ftp':
				return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->ftpDisplay( $value );


			default:
				return htmlspecialchars( $value, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE );
		}
	}
}