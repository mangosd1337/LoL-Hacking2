<?php
/**
 * @brief		Number input class for Form Builder
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

/**
 * Text input class for Form Builder
 */
class _Node extends FormAbstract
{
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'url'				=> \IPS\Http\Url::internal(...)	// The URL that this form element will be displayed on
	 		'class'				=> '\IPS\core\Foo',				// The node class
	 		'permissionCheck'	=> 'add',						// If a permission key is provided, only nodes that the member has that permission for will be available. Alternatively, can be a callback to return if node can be selected.
	 		'zeroVal'			=> 'none',						// If provided, a checkbox allowing you to receive a value of 0 will be shown with the label given. Default is NULL.
	 		'zeroValTogglesOn'	=> array( ... ),				// Element IDs to toggle on when the zero val checkbox is checked
	 		'zeroValTogglesOff'	=> array( ... ),				// Element IDs to toggle on when the zero val checkbox is unchecked
	 		'multiple'			=> TRUE,						// If multiple values are supported. Defaults to FALSE
	 		'subnodes'			=> TRUE,						// Controls if subnodes should be included. Defaults to TRUE
	 		'togglePerm'		=> 'edit',						// If a permission key is provided, nodes that have this permission will toggle the element IDs in toggleIds on/off
	 		'toggleIds'			=> array(),						// Element IDs to toggle on/off when togglePerm is reached
	 		'forceOwner'		=> \IPS\Member::load(),			// If nodes are 'owned', the owner. NULL for currently logged in member, FALSE to not limit by owner
	 		'where'				=> array(),						// where clause to control which results to display
	 		'disabledIds'		=> array(),					    // Array of disabled IDs
	 		'noParentNodes'		=> 'Custom'						// If a value is provided, subnodes of this class which have no parent node will be added into a pseudo-group with the title provided. e.g. custom packages in Nexus do not belong to any package group
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'url'				=> NULL,
		'class'				=> NULL,
		'permissionCheck'	=> NULL,
		'zeroVal'			=> NULL,
		'multiple'			=> FALSE,
		'subnodes'			=> TRUE,
		'togglePerm'		=> NULL,
		'toggleIds'			=> array(),
		'zeroValTogglesOn'	=> array(),
		'zeroValTogglesOff'	=> array(),
		'forceOwner'		=> NULL,
		'where'				=> array(),
		'disabledIds'		=> array(),
		'noParentNodes'		=> NULL
	);
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		call_user_func_array( 'parent::__construct', func_get_args() );
		
		if ( !$this->options['url'] )
		{
			$this->options['url'] = \IPS\Request::i()->url();
		}
		$this->options['url'] = $this->options['url']->setQueryString( '_nodeSelectName', $this->name );
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		$nodeClass = $this->options['class'];
		$permCheck = is_string( $this->options['permissionCheck'] ) ? $this->options['permissionCheck'] : NULL;
		$disabledCallback = is_callable( $this->options['permissionCheck'] ) ? $this->options['permissionCheck'] : NULL;
		
		if ( !$permCheck and \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'front' )
		{
			$permCheck = 'view';
		}
		
		/* Are we getting some AJAX stuff? */
		if ( isset( \IPS\Request::i()->_nodeSelectName ) and  \IPS\Request::i()->_nodeSelectName === $this->name )
		{
			$disabled = null;
			
			if ( isset( \IPS\Request::i()->_disabled ) )
			{
				$disabled = json_decode( \IPS\Request::i()->_disabled, true );
				$disabled = ( $disabled === FALSE ) ? array() : $disabled;
			}
			
			switch ( \IPS\Request::i()->_nodeSelect )
			{
				case 'children':
					try
					{
						$node = $permCheck ? $nodeClass::loadAndCheckPerms( \IPS\Request::i()->_nodeId, 'view' ) : $nodeClass::load( \IPS\Request::i()->_nodeId );
						$children = $node->children( $permCheck, NULL, $this->options['subnodes'], $disabled, $this->options['where'] );
					}
					catch ( \Exception $e )
					{
						\IPS\Output::i()->json( NULL, 404 );
					}

					\IPS\Output::i()->json( array( 'viewing' => $node->_id, 'title' => $node->_title, 'output' => \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->nodeCascade( $children, FALSE, $permCheck, $this->options['subnodes'], $this->options['togglePerm'], $this->options['toggleIds'], $disabledCallback, FALSE, NULL, $this->options['class'], $this->options['where'], $this->options['disabledIds'] ) ) );
					break;
					
				case 'parent':					
					try
					{
						$node = $permCheck ? $nodeClass::loadAndCheckPerms( \IPS\Request::i()->_nodeId, 'view' ) : $nodeClass::load( \IPS\Request::i()->_nodeId );
						$parent = $node->parent();
						
						$children = $parent ? $parent->children( $permCheck, NULL, $this->options['subnodes'], $disabled, $this->options['where'] ) : $nodeClass::roots( NULL, NULL, $this->options['where'] );
					}
					catch ( \Exception $e )
					{
						\IPS\Output::i()->json( $e->getMessage(), 404 );
					}
					
					\IPS\Output::i()->json( array( 'viewing' => $parent ? $parent->_id : 0, 'title' => $parent ? $parent->_title : \IPS\Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle ), 'output' => \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->nodeCascade( $children, FALSE, $permCheck, $this->options['subnodes'], $this->options['togglePerm'], $this->options['toggleIds'], $disabledCallback, FALSE, NULL, $this->options['class'], $this->options['where'], $this->options['disabledIds'] ) ) );
					break;
					
				case 'search':
					$results = array();
					
					$_results = $nodeClass::search( '_title', \IPS\Request::i()->q, '_title' );
					foreach ( $_results as $node )
					{
						if ( ( !$permCheck or $node->can( $permCheck ) ) AND ! in_array( $node->_id, $disabled ) )
						{						
							$id = ( $node instanceof $nodeClass ? $node->_id : "s.{$node->_id}" );
							$results[ $id ] = $node;
						}
					}
					
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->nodeCascade( $results, TRUE, $permCheck, $this->options['subnodes'], $this->options['togglePerm'], $this->options['toggleIds'], $disabledCallback, FALSE, NULL, $this->options['class'], $this->options['where'], $this->options['disabled'] ) );
					break;
			}
		}
		
		/* Get initial nodes */
		$nodes		= array();
		$children	= array();
		$noParentNodes = array();
		if( isset( $nodeClass::$ownerTypes ) and $nodeClass::$ownerTypes !== NULL and $this->options['forceOwner'] !== FALSE )
		{
			$nodes = $nodeClass::loadByOwner( $this->options['forceOwner'] ?: \IPS\Member::loggedIn(), $this->options['where'] );
		}
		else
		{
			/* Recurse three levels in to allow for discoverability */
			foreach( $nodeClass::roots( NULL, NULL, $this->options['where'] ) AS $node )
			{
				$this->_populate( $nodes, $disabled, 0, $node, $children, 3 );
			}
			if ( $this->options['noParentNodes'] )
			{
				$subnodeClass = $nodeClass::$subnodeClass;
				$noParentNodes = iterator_to_array( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', $subnodeClass::$databaseTable, $subnodeClass::$databasePrefix . $subnodeClass::$parentNodeColumnId . '=0' )->setKeyField( $subnodeClass::$databasePrefix . $subnodeClass::$databaseColumnId ), $subnodeClass ) );
			}
		}

		if ( isset( $this->options['disabled'] ) and is_array( $this->options['disabled'] ) )
		{
			$this->options['url'] = $this->options['url']->setQueryString( '_disabled', json_encode( $this->options['disabled'] ) );
			
			foreach( $this->options['disabled'] as $id )
			{
				if ( isset( $nodes[ $id ] ) )
				{
					unset( $nodes[ $id ] );
				}
			}
		}
		
		/* What is selected? */
		if ( $this->options['zeroVal'] !== NULL and $this->value === 0 )
		{
			$selected = 0;
		}
		else
		{
			$selected = array();

			if ( is_array( $this->value ) )
			{
				foreach ( $this->value as $node )
				{
					$title = isset( $node::$titleLangPrefix ) ? \IPS\Member::loggedIn()->language()->addToStack( $node::$titleLangPrefix . $node->_id, FALSE, array( 'json' => TRUE, 'escape' => TRUE, 'striptags'=> TRUE ) ) : ( $node->_stripTagsTitle ? $node->_stripTagsTitle : $node->_title );
					$selected[ !( $node instanceof $nodeClass ) ? "{$node->_id}.s" : $node->_id ] = array( 'title' => $title, 'parents' => array_values( array_map( function( $val ){ 
						return isset( $val::$titleLangPrefix ) ? \IPS\Member::loggedIn()->language()->addToStack( $val::$titleLangPrefix . $val->_id, FALSE, array( 'json' => TRUE, 'escape' => TRUE, 'striptags'=> TRUE ) ) : ( $val->_stripTagsTitle ? $val->_stripTagsTitle : $val->_title ); 
					}, iterator_to_array( $node->parents() ) ) ) );
				}
			}
			elseif ( $this->value instanceof \IPS\Node\Model )
			{
				$class = get_class( $this->value );
				$title = isset( $nodeClass::$titleLangPrefix ) ? \IPS\Member::loggedIn()->language()->addToStack( $class::$titleLangPrefix . $this->value->_id, FALSE, array( 'json' => TRUE, 'escape' => TRUE, 'striptags'=> TRUE ) ) : ( $this->value->_stripTagsTitle ? $this->value->_stripTagsTitle : $this->value->_title );
				$selected[ !( $this->value instanceof $nodeClass ) ? "{$this->value->_id}.s" : $this->value->_id ] = array( 'title' => $title, 'parents' => array_values( array_map( function( $val ){ 
					return isset( $val::$titleLangPrefix ) ? \IPS\Member::loggedIn()->language()->addToStack( $val::$titleLangPrefix . $val->_id, FALSE, array( 'json' => TRUE, 'escape' => TRUE, 'striptags'=> TRUE ) ) : ( $val->_stripTagsTitle ? $val->_stripTagsTitle : $val->_title ); 
				}, iterator_to_array( $this->value->parents() ) ) ) );
			}
			
			$selected = json_encode( $selected );
		}

		/* Do we need the no-JS fallback? We only do this if we know JS is disabled as it's intensive and may cause memory exhaustion if there are lots of nodes - it's a last resort */
		$noJS = NULL;
		if ( isset( \IPS\Request::i()->_noJs ) )
		{
			$options = array();
			$_children = array();
			$disabled = ( isset( $this->options['disabled'] ) and is_array( $this->options['disabled'] ) ) ? $this->options['disabled'] : array();
			foreach ( $nodeClass::roots( 'view', NULL, $this->options['where'] ) as $root )
			{
				$this->_populate( $options, $disabled, 0, $root, $_children, NULL, NULL, TRUE );
			}
			
			$value = NULL;
			if ( $this->value !== 0 )
			{
				if ( $this->options['multiple'] )
				{
					$value = array();
					if ( is_array( $this->value ) )
					{
						$value = array_keys( $this->value );
					}
					elseif ( is_object( $this->value ) )
					{
						$value = array( $this->value->_id );
					}
				}
				else
				{
					if ( is_numeric( $this->value ) )
					{
						$value = $this->value;
					}
					elseif ( is_object( $this->value ) )
					{
						$value = array( $this->value->_id );
					}
				}
			}
																
			$noJS = \IPS\Theme::i()->getTemplate( 'forms', 'core' )->select( $this->name . ( $this->options['multiple'] ? '[]' : '' ), $value, $this->required, $options, $this->options['multiple'], '', $disabled );
		}

		/* Display */
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->node( $this->name, $selected, $this->options['multiple'], $this->options['url'], $nodeClass::$nodeTitle, $nodes, $this->options['zeroVal'], $noJS, $permCheck, $this->options['subnodes'], $this->options['togglePerm'], $this->options['toggleIds'], $disabledCallback, $this->options['zeroValTogglesOn'], $this->options['zeroValTogglesOff'], TRUE, $children, $this->options['class'], $this->options['where'], is_array( $this->options['disabledIds'] ) ? $this->options['disabledIds'] : array(), $this->options['noParentNodes'], $noParentNodes );
	}
	
	/**
	 * Populate array options
	 *
	 * @param	array					$options	The list of nodes
	 * @param	array|boolean			$disabled	Disabled options
	 * @param	int						$depth		The current depth
	 * @param	\IPS\Node\Model			$node		The node
	 * @param	int|NULL				$depthLimit	How deep the recursion should go
	 * @param	\IPS\Node\Model|NULL	$parent		If we are recursing on a child, this should be the parent node. Otherwise NULL if in the root.
	 * @return	void
	 */
	protected function _populate( &$nodes, &$disabled, $depth, $node, &$children, $depthLimit = NULL, $parent = NULL, $noJS = FALSE )
	{
		if ( ( !$this->options['permissionCheck'] OR $node->can('view') ) and ( empty( $this->options['disabled'] ) or !in_array( $node->_id, $this->options['disabled'] ) ) )
		{
			if ( $noJS )
			{
				$nodes[ $node->_id . ( !( $node instanceof $this->options['class'] ) ? '.s' : '' ) ] = str_repeat( '- ', $depth ) . $node->_title;
			}
			else
			{
				if ( $parent === NULL )
				{
					$nodes[ $node->_id ] = $node;
				}
				else
				{
					$children[ $parent->_id ][ $node->_id ] = $node;
				}
			}
			
			if ( $this->options['permissionCheck'] )
			{
				if ( is_string( $this->options['permissionCheck'] ) )
				{
					if ( !$node->can( $this->options['permissionCheck'] ) )
					{
						$disabled[] = $node->_id;
					}
				}
				elseif ( is_callable( $this->options['permissionCheck'] ) )
				{
					if ( !call_user_func( $this->options['permissionCheck'], $node ) )
					{
						$disabled[] = $node->_id;
					}
				}
			}
			
			if ( $depthLimit === NULL OR $depth < (int) $depthLimit )
			{
				foreach( $node->children( 'view', NULL, $this->options['subnodes'], NULL, $this->options['where'] ) AS $child )
				{
					$this->_populate( $nodes, $disabled, $depth + 1, $child, $children, $depthLimit, $node, $noJS );
				}
			}
		}
	}
		
	/**
	 * Get Value
	 *
	 * @return	string|int
	 */
	public function getValue()
	{
		$zeroValName = "{$this->name}-zeroVal";
		if ( $this->options['zeroVal'] !== NULL and isset( \IPS\Request::i()->$zeroValName ) )
		{
			return 0;
		}
		else
		{
			return parent::getValue();
		}
	}
	
	/**
	 * Format Value
	 *
	 * @return	\IPS\Node\Model|array|NULL
	 */
	public function formatValue()
	{		
		$nodeClass = $this->options['class'];
		$permCheck = $this->options['permissionCheck'];
				
		if ( $this->value and !( $this->value instanceof \IPS\Node\Model ) )
		{			
			$return = array();
			foreach ( is_array( $this->value ) ? $this->value : explode( ',', $this->value ) as $v )
			{
				if ( $v instanceof \IPS\Node\Model )
				{
					$return[ !( $v instanceof $nodeClass ) ? "s{$v->_id}" : $v->_id ] = $v;
				}	
				elseif( $v )
				{
					try
					{
						$exploded = explode( '.', $v );
						$node = call_user_func( array( ( isset( $exploded[1] ) and $exploded[1] === 's' ) ? $nodeClass::$subnodeClass : $nodeClass, 'load' ), $exploded[0] );
												
						if ( !$permCheck or ( is_string( $permCheck ) and $node->can( $permCheck ) ) or ( is_callable( $permCheck ) and call_user_func( $permCheck, $node ) ) )
						{
							$return[ isset( $exploded[1] ) ? "s{$node->_id}" : $node->_id ] = $node;
						}
					}
					catch ( \Exception $e ) {}
				}
			}
			
			if ( !empty( $return ) )
			{
				return $this->options['multiple'] ? $return : array_pop( $return );
			}
			else
			{
				return NULL;
			}
		}
		
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
}