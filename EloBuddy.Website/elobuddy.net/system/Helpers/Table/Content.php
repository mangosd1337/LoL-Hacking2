<?php
/**
 * @brief		Table Builder using an \IPS\Content\Item class datasource
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		16 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Table;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * List Table Builder using an \IPS\Content\Item class datasource
 */
class _Content extends Table
{
	/**
	 * @brief	Database Table
	 */
	protected $class;
	
	/**
	 * @brief	Initial WHERE clause
	 */
	public $where;
	
	/**
	 * @brief	Container
	 */
	protected $container;
	
	/**
	 * @brief	Permission key to check
	 */
	protected $permCheck;

	/**
	 * @brief	Include hidden content flag with results
	 */
	protected $includeHiddenContent	= NULL;
	
	/**
	 * @brief	Sort options
	 */
	public $sortOptions = array();

	/**
	 * @brief	Honor the pinned flag for sorting. Will be set to false in stream-like views
	 */
	protected $honorPinned	= TRUE;
	
	/**
	 * @brief	Show moved links in the result set. This is desirable in controller generated views but not streams or widgets, etc.
	 */
	protected $showMovedLinks = FALSE;
	
	/**
	 * Number of results
	 */
	public $count = 0;

	/**
	 * @brief	Join container data in getItemsWithPermission
	 */
	public $joinContainer = FALSE;

	/**
	 * @brief	Join comment data in getItemsWithPermission
	 */
	public $joinComments = FALSE;
	
	/**
	 * @brief	Join review data in getItemsWithPermission
	 */
	public $joinReviews = FALSE;
	
	/**
	 * @brief	Advanced search callback
	 */
	public $advancedSearchCallback = NULL;
	
	/**
	 * Saved Actions (for multi-moderation)
	 */
	public $savedActions = array();
	
	/**
	 * @brief	Joins
	 */
	public $joins = array();
	
	/**
	 * @brief	Array of item IDs the current $member has posted in
	 */
	public $contentPostedIn = array();
				
	/**
	 * Constructor
	 *
	 * @param	string					$class				Content Class Name
	 * @param	\IPS\Http\Url			$baseUrl			Base URL
	 * @param	array|null				$where				WHERE clause (To restrict to a node, use $container instead)
	 * @param	\IPS\Node\Model|NULL	$container			The container
	 * @param	bool|null				$includeHidden		Flag to pass to getItemsWithPermission() method for $includeHiddenContent, defaults to NULL
	 * @param	string|NULL				$permCheck			Permission key to check
	 * @param	bool					$honorPinned		Show pinned topics at the top of the table
	 * @param	bool					$showMovedLinks		Show moved links in the result set.
	 * @return	void
	 */
	public function __construct( $class, \IPS\Http\Url $baseUrl, $where=NULL, \IPS\Node\Model $container=NULL, $includeHidden=\IPS\Content\Hideable::FILTER_AUTOMATIC, $permCheck='view', $honorPinned=TRUE, $showMovedLinks=FALSE )
	{
		/* Init */
		$this->include = array();
		$this->class = $class;
		$this->where = $where;
		$this->container = $container;
		$this->includeHiddenContent	= $includeHidden;
		$this->honorPinned	= $honorPinned;
		$this->showMovedLinks = $showMovedLinks;
		$this->permCheck = $permCheck;
		
		/* Init */
		parent::__construct( $baseUrl );
		
		$this->rowsTemplate = $class::contentTableTemplate();

		/* Set container */
		if ( $container !== NULL )
		{
			$this->where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['container'] . '=?', $container->_id );
			$this->sortDirection = ( $this->sortDirection !== NULL ) ? $this->sortDirection : $container->_sortOrder;

			if ( !$this->sortBy and $container->_sortBy !== NULL )
			{
				$this->sortBy =  $class::$databaseTable . '.' . $class::$databasePrefix . $container->_sortBy;
			}
			if ( !$this->filter )
			{
				$this->filter = $container->_filter;
			}
			
			if ( $this->includeHiddenContent === \IPS\Content\Hideable::FILTER_AUTOMATIC )
			{
				$this->includeHiddenContent = $class::canViewHiddenItems( \IPS\Member::loggedIn(), $container );
			}
			
			/* Set breadcrumb */
			foreach ( $container->parents() as $parent )
			{
				\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
			}
			\IPS\Output::i()->breadcrumb[] = array( NULL, $container->_title );
			
			/* Meta tags */
			\IPS\Output::i()->title = ( $this->page > 1 ) ? \IPS\Member::loggedIn()->language()->addToStack( 'title_with_page_number', FALSE, array( 'sprintf' => array( $container->metaTitle(), $this->page ) ) ) : $container->metaTitle();
			\IPS\Output::i()->metaTags['title'] = $container->metaTitle();
			\IPS\Output::i()->metaTags['description'] = $container->metaDescription();
			\IPS\Output::i()->linkTags['canonical'] = (string) $baseUrl;
		}

		/* Set available sort options */
		foreach ( array( 'updated', 'last_comment', 'title', 'rating', 'date', 'num_comments', 'num_reviews', 'views' ) as $k )
		{
			if ( isset( $class::$databaseColumnMap[ $k ] ) and !isset( $this->sortOptions[ $k ] ) )
			{
				$column = is_array( $class::$databaseColumnMap[ $k ] ) ? array_shift( $class::$databaseColumnMap[ $k ] ) : $class::$databaseColumnMap[ $k ];
				
				$this->sortOptions[ $k ] = $class::$databasePrefix . $column;
			}
		}
		if ( !$this->sortBy )
		{
			if ( isset( $class::$databaseColumnMap['updated'] ) )
			{
				$this->sortBy = $class::$databasePrefix . $class::$databaseColumnMap['updated'];
			}
			elseif ( isset( $class::$databaseColumnMap['last_comment'] ) )
			{
				$this->sortBy = $class::$databasePrefix . ( is_array( $class::$databaseColumnMap['last_comment'] ) ? $class::$databaseColumnMap['last_comment'][0] : $class::$databaseColumnMap['last_comment'] );
			}
			else
			{
				$this->sortBy = $class::$databasePrefix . $class::$databaseColumnMap['date'];
			}
		}

		/* Do any multi-mod */
		if ( isset( \IPS\Request::i()->modaction ) )
		{
			$this->multiMod();
		}
	}
	
	/**
	 * Get rows
	 *
	 * @param	array	$advancedSearchValues	Values from the advanced search form
	 * @return	array
	 */
	public function getRows( $advancedSearchValues )
	{
		/* Init */
		$class = $this->class;
		
		/* Check sortBy */
		$defaultSort = NULL;
		foreach ( array( 'last_comment', 'last_review', 'date' ) as $k )
		{
			if ( isset( $class::$databaseColumnMap[ $k ] ) )
			{
				if ( is_array( $class::$databaseColumnMap[ $k ] ) )
				{
					$cols = $class::$databaseColumnMap[ $k ];
					$defaultSort = $class::$databaseTable . '.' . $class::$databasePrefix . array_pop( $cols );
				}
				else
				{
					$defaultSort = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap[ $k ];
				}
				break;
			}
		}
		
		$compareSortBy = $this->sortBy;
		$lookFor       = $class::$databaseTable . '.' . $class::$databasePrefix;
		
		if ( mb_substr( $this->sortBy, 0, mb_strlen( $lookFor ) ) === $lookFor )
		{
			$compareSortBy = mb_substr( $this->sortBy, mb_strlen( $lookFor ) );
		}
		
		$this->sortBy = in_array( $compareSortBy, $this->sortOptions ) ? $this->sortBy : 
			( array_key_exists( $compareSortBy, $this->sortOptions ) ? $this->sortOptions[ $compareSortBy ] : $defaultSort );
			
		/* Callback? */
		if ( $this->advancedSearchCallback and !empty( $advancedSearchValues ) )
		{
			$obj = $this;
			call_user_func( $this->advancedSearchCallback, $obj, $advancedSearchValues );
		}
		
		/* What are we sorting by? */
		$sortBy = $this->sortBy . ' ' . ( mb_strtolower( $this->sortDirection ) == 'asc' ? 'asc' : 'desc' );
		if ( in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) and $this->honorPinned )
		{
			$column = $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['pinned'];
			$sortBy = "{$column} DESC, {$sortBy}";
		}
		
		/* Specify filter in where clause */
		$where = $this->where ? is_array( $this->where ) ? $this->where : array( $this->where ) : array();
		if ( $this->filter and isset( $this->filters[ $this->filter ] ) )
		{
			$where[] = is_array( $this->filters[ $this->filter ] ) ? $this->filters[ $this->filter ] : array( $this->filters[ $this->filter ] );
		}

		/* Get results */
		$this->count = $class::getItemsWithPermission( $where, $sortBy, NULL, $this->permCheck, $this->includeHiddenContent, 0, NULL, $this->joinContainer, $this->joinComments, $this->joinReviews, TRUE, $this->joins, FALSE, TRUE, TRUE, TRUE, $this->showMovedLinks );
		$it = $class::getItemsWithPermission( $where, $sortBy, array( ( $this->limit * ( $this->page - 1 ) ), $this->limit ), $this->permCheck, $this->includeHiddenContent, 0, NULL, $this->joinContainer, $this->joinComments, $this->joinReviews, FALSE, $this->joins, FALSE, TRUE, TRUE, TRUE, $this->showMovedLinks );
		$this->pages = ceil( $this->count / $this->limit );
		$rows = iterator_to_array( $it );
		
		/* Pre load the contentPostedIn data to save separate DISTINCT queries later and a query for each unread item */
		if ( isset( $class::$databaseColumnMap['container'] ) )
		{ 
			$containerItemIds = array();
			foreach( $rows as $item )
			{
				$colContainer = $class::$databaseColumnMap['container'];
				$colPrimary	  = $class::$databaseColumnId;
				
				$containerItemIds[ $item->$colContainer ][] = $item->$colPrimary;
			}
			
			$nodeClass = $class::$containerNodeClass;
			foreach( $containerItemIds as $container => $ids )
			{
				try
				{
					$node = $nodeClass::load( $container );
					$this->contentPostedIn = array_merge( $node->contentPostedIn( NULL, $ids ), $this->contentPostedIn );
					
				}
				catch( \OutOfRangeException $ex ) {}
			}
		}
	
		/* Pull in extra data */
		if ( method_exists( $class, 'tableGetRows' ) )
		{
			$class::tableGetRows( $rows );
		}
				
		/* Return */
		return $rows;
	}

	/**
	 * @brief	Return table filters
	 */
	public $showFilters	= TRUE;

	/**
	 * Return the filters that are available for selecting table rows
	 *
	 * @return	array
	 */
	public function getFilters()
	{
		if( $this->showFilters === FALSE )
		{
			return array();
		}

		$class = $this->class;

		if( method_exists( $class, 'getTableFilters' ) )
		{
			return $class::getTableFilters();
		}
		
		return array();
	}
	
	/**
	 * @brief	Disable moderation?
	 */
	public $noModerate = FALSE;
	
	/**
	 * Does the user have permission to use the multi-mod checkboxes?
	 *
	 * @param	string|null		$action		Specific action to check (hide/unhide, etc.) or NULL for a generic check
	 * @return	bool
	 */
	public function canModerate( $action=NULL )
	{
		if ( $this->noModerate )
		{
			return FALSE;
		}
		
		$class = $this->class;
		if ( $action )
		{
			return $class::modPermission( $action, \IPS\Member::loggedIn(), $this->container );
		}
		else
		{
			return $class::canSeeMultiModTools( \IPS\Member::loggedIn(), $this->container );
		}
	}

	/**
	 * What multimod actions are available
	 *
	 * @param	object	$item	Item
	 * @return	array
	 */
	public function multimodActions( $item )
	{
		$return = array();
		
		if ( $item instanceof \IPS\Content\Item )
		{
			if ( $item instanceof \IPS\Content\Featurable )
			{
				if ( $item->mapped('featured') and $item->canUnfeature() )
				{
					$return[] = 'unfeature';
				}
				elseif ( $item->canFeature() )
				{
					$return[] = 'feature';
				}
			}
			
			if ( $item instanceof \IPS\Content\Pinnable )
			{	
				if ( $item->mapped('pinned') and $item->canUnpin() )
				{
					$return[] = 'unpin';
				}
				elseif ( $item->canPin() )
				{
					$return[] = 'pin';
				}
			}
			
			if ( $item instanceof \IPS\Content\Hideable )
			{	
				if ( $item->hidden() === -1 and $item->canUnhide() )
				{
					$return[] = 'unhide';
				}
				elseif ( $item->hidden() === 1 and $item->canUnhide() )
				{
					$return[] = 'approve';
				}
				elseif ( $item->canHide() )
				{
					$return[] = 'hide';
				}
			}
			
			if ( $item instanceof \IPS\Content\Lockable )
			{	
				if ( $item->locked() AND $item->canUnlock() )
				{
					$return[] = 'unlock';
				}
				elseif ( $item->canLock() )
				{
					$return[] = 'lock';
				}
			}
					
			if ( isset( $item::$databaseColumnMap['container'] ) )
			{
				if ( $item->canMove() )
				{
					$return[] = 'move';
				}
			}
			
			if ( $item->canMerge() )
			{
				$return[] = 'merge';
			}
			
			if ( $item->canDelete() )
			{
				$return[] = 'delete';
			}

			/* Do we have any custom actions? */
			$return	= array_merge( $return, $item->customMultimodActions() );
		}
		
		foreach ( $this->savedActions as $k => $v )
		{
			$return[] = "savedAction-{$k}";
		}
		
		return $return;		
	}

	/**
	 * What custom multimod actions are available
	 *
	 * @return	array
	 */
	public function customActions()
	{
		$class = $this->class;
		return $class::availableCustomMultimodActions();
	}
	
	/**
	 * Multimod
	 *
	 * @return	void
	 */
	protected function multimod()
	{
		\IPS\Session::i()->csrfCheck();

		$class = $this->class;
		$params = array();
		
		/* Move: to where? */
		if ( \IPS\Request::i()->modaction == 'move' )
		{
			$form = new \IPS\Helpers\Form( 'form', 'move' );
			$form->class = 'ipsForm_vertical';
			$form->hiddenValues['modaction']	= 'move';
			$form->hiddenValues['moderate']	= \IPS\Request::i()->moderate;
			$form->add( new \IPS\Helpers\Form\Node( 'move_to', NULL, TRUE, array(
				'class' => $class::$containerNodeClass,
				'url' => \IPS\Request::i()->url()->setQueryString( 'modaction', 'move' ),
				'permissionCheck'	=> function( $node )
				{
					try
					{
						if ( $node->can( 'add' ) )
						{
							return true;
						}
					}
					catch( \OutOfBoundsException $e ) { }
					
					return false;
				}
			) ) );
			if ( isset( $class::$databaseColumnMap['moved_to'] ) )
			{
				$form->add( new \IPS\Helpers\Form\Checkbox( 'move_keep_link' ) );
				
				if ( \IPS\Settings::i()->topic_redirect_prune )
				{
					\IPS\Member::loggedIn()->language()->words['move_keep_link_desc'] = \IPS\Member::loggedIn()->language()->addToStack('_move_keep_link_desc', FALSE, array( 'pluralize' => array( \IPS\Settings::i()->topic_redirect_prune ) ) );
				}
			}
			
			if ( $values = $form->values() )
			{
				$params[] = $values['move_to'];
				$params[] = ( isset( $values['move_keep_link'] ) and $values['move_keep_link'] );
			}
			else
			{
				\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
				
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->sendOutput( \IPS\Output::i()->output  );
				}
				else
				{
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
				}
				return;
			}
		}
		
		/* Hide: ask for reason */
		if ( \IPS\Request::i()->modaction == 'hide' )
		{
			$form = new \IPS\Helpers\Form( 'form', 'hide' );
			$form->class = 'ipsForm_vertical';
			$form->hiddenValues['modaction']	= 'hide';
			$form->hiddenValues['moderate']	= \IPS\Request::i()->moderate;
			$form->add( new \IPS\Helpers\Form\Text( 'hide_reason' ) );
			if ( $values = $form->values() )
			{
				$params = $values['hide_reason'];
			}
			else
			{
				\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
				
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->sendOutput( \IPS\Output::i()->output  );
				}
				else
				{
					\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
				}
				return;
			}
		}
				
		/* Merge: what's the master? */
		if ( \IPS\Request::i()->modaction == 'merge' )
		{
			$ids = array_keys( \IPS\Request::i()->moderate );
			$options = array();
			$descriptions = array();
			foreach ( $ids as $id )
			{
				$item = $class::load( $id );
				$options[ $id ] = $item->mapped('title');
				$descriptions[ $id ] = \IPS\Member::loggedIn()->language()->addToStack( 'byline', FALSE, array( 'sprintf' => array( $item->author()->name ) ) ) . \IPS\DateTime::ts( $item->mapped('date') )->html();
			}
			
			if ( count( $options ) === 1 )
			{
				foreach ( $options as $id => $title )
				{
					\IPS\Output::i()->redirect( $class::load( $id )->url()->setQueryString( 'do', 'merge' ) );
				}
			}
			else
			{
				$form = new \IPS\Helpers\Form( 'form', 'merge' );
				$form->class = 'ipsForm_vertical';
				$form->hiddenValues['modaction']	= 'merge';
				$form->hiddenValues['moderate']	= \IPS\Request::i()->moderate;
				$form->add( new \IPS\Helpers\Form\Radio( 'merge_master', NULL, TRUE, array( 'options' => $options, 'descriptions' => $descriptions ) ) );
				if ( $values = $form->values() )
				{					
					$otherItems = array();
					foreach ( $ids as $id )
					{
						if ( $id != $values['merge_master'] )
						{
							$otherItems[] = $class::load( $id );
						}
					}
					
					$class::load( $values['merge_master'] )->mergeIn( $otherItems );
				}
				else
				{
					\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
					
					if ( \IPS\Request::i()->isAjax() )
					{
						\IPS\Output::i()->sendOutput( \IPS\Output::i()->output );
					}
					else
					{
						\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->globalTemplate( \IPS\Output::i()->title, \IPS\Output::i()->output, array( 'app' => \IPS\Dispatcher::i()->application->directory, 'module' => \IPS\Dispatcher::i()->module->key, 'controller' => \IPS\Dispatcher::i()->controller ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
					}
					return;
				}
			}
		}
				
		/* Everything else: just do it */
		else
		{
			foreach ( array_keys( \IPS\Request::i()->moderate ) as $id )
			{
				try
				{
					$object = $class::loadAndCheckPerms( $id );
					$object->modAction( \IPS\Request::i()->modaction, \IPS\Member::loggedIn(), $params );
				}
				catch ( \Exception $e ) {}
			}
		}
		
		\IPS\Output::i()->redirect( $this->baseUrl );
	}
	
	/**
	 * Return the table headers
	 *
	 * @param	array|NULL	$advancedSearchValues	Advanced search values
	 * @return	array
	 */
	public function getHeaders( $advancedSearchValues )
	{
		return array();
	}
	
	/**
	 * Return the container
	 *
	 * @return	\IPS\Node\Model
	 */
	public function container()
	{
		return $this->container;
	}
}