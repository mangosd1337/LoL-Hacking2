<?php
/**
 * @brief		Item selector for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/* @todo limit by container IDs */

/**
 * Content Item selector
 */
class _Item extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'class'				=> '\IPS\core\Foo',				// The \Content\Item class
	 		'permissionCheck'	=> 'read',						// If a permission key is provided, only content items that the member has that permission for will be available.
	 		'maxItems'			=> NULL,						// Maximum items allowed to select, or NULL for unlimited
	        'orderResults'      => NULL|array                   // NULL or array( field, 'asc' ) where field is a mappable content item field (date, title, etc)
	        'itemTemplate'      => NULL                         // NULL or array of template group and template name array( \IPS\Theme::i()->getTemplate( 'group', 'app', 'location'), 'name' )
			'containerIds'      => array                        // Array of container IDs to limit the search
	        'minAjaxLength'     => int                          // Number of characters to type before selector appears
	 *      'where'             => array                        // Array of additional where array( array( 'foo=?', 1 ) )
	 * );
	 * @encode
	 */
	protected $defaultOptions = array(
		'class'				=> NULL,
		'permissionCheck'   => 'read',
		'maxItems'      	=> NULL,
		'orderResults'      => NULL,
		'itemTemplate'      => NULL,
		'containerIds'      => array(),
		'minAjaxLength'     => 3,
		'where'             => array()
	);
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		call_user_func_array( 'parent::__construct', func_get_args() );
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		/* Display */
		$url = \IPS\Request::i()->url()->setQueryString( '_itemSelectName', $this->name );

		$template = NULL;
		if ( $this->options['itemTemplate'] === NULL )
		{
			$template = array( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' ), 'itemResult' );
		}

		/* Are we getting some AJAX stuff? */
		if ( isset( \IPS\Request::i()->_itemSelectName ) and \IPS\Request::i()->_itemSelectName === $this->name )
		{
			$results = array();
			$class   = $this->options['class'];
			$field   = $class::$databasePrefix . $class::$databaseColumnMap['title'];
			$where   = array( array( $field . " LIKE CONCAT('%', ?, '%')", \IPS\Request::i()->q ) );
			$idField = $class::$databaseColumnId;
			if ( isset( $class::$databaseColumnMap['container'] ) and is_array( $this->options['containerIds'] ) and count( $this->options['containerIds'] ) )
			{
				$where[] = array( \IPS\Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['container'], $this->options['containerIds'] ) );
			}

			if ( isset( $this->options['where'] ) and count( $this->options['where'] ) )
			{
				$where = array_merge( $where, $this->options['where'] );
			}

			foreach( $class::getItemsWithPermission( $where, 'LENGTH(' . $field . ') ASC', array( 0, 20 ), $this->options['permissionCheck'] ) as $item )
			{
				$results[] = array(
					'id'	=> $item->$idField,
					'html'  => call_user_func( $template, $item )
				);
			}

			\IPS\Output::i()->json( $results );
		}

		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->item( $this->name, $this->value, $this->options['maxItems'], $this->options['minAjaxLength'], $url, $template );
	}

	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue()
	{
		$name = $this->name . '_values';
		if ( isset( \IPS\Request::i()->$name ) )
		{
			return explode( ',', \IPS\Request::i()->$name );
		}
		else
		{
			return array();
		}
	}

	/**
	 * Format Value
	 *
	 * @return	\IPS\Node\Model|array|NULL
	 */
	public function formatValue()
	{
		$itemClass = $this->options['class'];
		$order     = NULL;
		$items     = array();

		if ( ! $this->value )
		{
			$this->value = array();
		}
		else
		{
			$this->value = ( is_string( $this->value ) ) ? explode( ',', $this->value ) : $this->value;
		}

		if ( count( $this->value ) )
		{
			if ( is_array( $this->options['orderResults'] ) )
			{
				if ( isset( $itemClass::$databaseColumnMap[ $this->options['orderResults'][0] ] ) )
				{
					$order = $itemClass::$databaseColumnMap[ $this->options['orderResults'][0] ] . ' ' . $this->options['orderResults'][1];
				}
			}

			$where = array( \IPS\Db::i()->in( $itemClass::$databaseColumnId, $this->value ) );
			foreach( $itemClass::getItemsWithPermission( array( $where ), $order, NULL, $this->options['permissionCheck'] ) as $item )
			{
				$items[ $item->_id ] = $item;
			}
		}

		$this->value = $items;

		return $this->value;
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
			return implode( ',', array_keys( $value ) );
		}
		elseif ( is_object( $value ) )
		{
			return $value->_id;
		}
		return (string) $value;
	}
	/**
	 * Validate
	 *
	 * @throws	\InvalidArgumentException
	 * @return	TRUE
	 */
	public function validate()
	{
		if( empty( $this->value ) and $this->required )
		{
			throw new \InvalidArgumentException('form_required');
		}
		
		parent::validate();
	}
}