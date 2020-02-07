<?php
/**
 * @brief		Search Results
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Results
 */
class _Results extends \ArrayIterator
{	
	/**
	 * @brief	Count
	 */
	protected $countAllRows;
	
	/**
	 * @brief	Has this been initated?
	 */
	protected $initated = FALSE;
		
	/**
	 * @brief	Author data
	 */
	protected $authorData = array();
	
	/**
	 * @brief	Item titles
	 */
	protected $itemData = array();
	
	/**
	 * @brief	Container data
	 */
	protected $containerData = array();
	
	/**
	 * @brief	Reputation data
	 */
	protected $reputationData = array();
	
	/**
	 * @brief	Review Ratings
	 */
	protected $reviewRatings = array();
	
	/**
	 * @brief	Index IDs of items I have posted in
	 */
	protected $iPostedIn = array();
	
	/**
	 * @brief	Tag values for index_ids returned from search
	 */
	protected $tags = array();
		
	/**
	 * Constructor
	 *
	 * @param	array				$results			The results
	 * @param	\IPS\Db\Select|int	$countAllRows		Count for all rows query or int
	 * @return	void
	 */
	public function __construct( $results, $countAllRows )
	{
		$this->countAllRows = $countAllRows;
		parent::__construct( $results );
	}
	
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function init()
	{
		/* Init */
		$membersToLoad = array();
		$itemsToLoad = array();
		$containersToLoad = array();
		$reputationIds = array();
		$reviewRatings = array();
		$itemIndexIds = array();
		$indexIds	  = array();
		
		/* Loop */
		foreach ( $this->getArrayCopy() as $result )
		{
			if ( $result['index_author'] )
			{
				$membersToLoad[ $result['index_author'] ] = $result['index_author'];
			}
			
			if ( in_array( 'IPS\Content\Comment', class_parents( $result['index_class'] ) ) )
			{
				$commentClass = $result['index_class'];
				$itemsToLoad[ $commentClass::$itemClass ][ $result['index_item_id'] ] = $result['index_item_id'];
			}
			else
			{
				$itemsToLoad[ $result['index_class'] ][ $result['index_item_id'] ] = $result['index_item_id'];
			}
			
			if ( $result['index_container_id'] )
			{
				$class = $result['index_class'];
				if ( $class == 'IPS\core\Statuses\Status' )
				{
					if ( $result['index_container_id'] != $result['index_author'] )
					{
						$membersToLoad[ $result['index_container_id'] ] = $result['index_container_id'];
					}
				}
				else
				{
					$itemClass = in_array( 'IPS\Content\Comment', class_parents( $class ) ) ? $class::$itemClass : $class;
					if ( isset( $itemClass::$containerNodeClass ) )
					{
						$containerClass = $itemClass::$containerNodeClass;
						if ( isset( $containerClass::$seoTitleColumn ) )
						{
							$containersToLoad[ $itemClass::$containerNodeClass ][ $result['index_container_id'] ] = $result['index_container_id'];
						}
					}
				}
			}
			
			if ( in_array( 'IPS\Content\Reputation', class_implements( $result['index_class'] ) ) )
			{
				$reputationIds[ $result['index_class'] ][ $result['index_object_id'] ] = $result['index_object_id'];
			}
			
			if ( in_array( 'IPS\Content\Review', class_parents( $result['index_class'] ) ) )
			{
				$reviewRatings[ $result['index_class'] ][ $result['index_object_id'] ] = $result['index_object_id'];
			}
			
			if ( $result['index_item_index_id'] )
			{
				$itemIndexIds[] = $result['index_item_index_id'];
			}
		}
						
		/* Load item data */
		foreach ( $itemsToLoad as $itemClass => $itemIds )
		{
			foreach ( \IPS\Db::i()->select( $itemClass::basicDataColumns(), $itemClass::$databaseTable, \IPS\Db::i()->in( $itemClass::$databasePrefix . $itemClass::$databaseColumnId, $itemIds ) )->setKeyField( $itemClass::$databasePrefix . $itemClass::$databaseColumnId ) as $itemId => $itemData )
			{
				$this->itemData[ $itemClass ][ $itemId ] = $itemData;
				
				if ( isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ] ) )
				{
					$memberId = $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ];
					$membersToLoad[ $memberId ] = $memberId;
				}
			}
			
			if ( method_exists( $itemClass, 'searchResultExtraData' ) )
			{
				foreach ( $itemClass::searchResultExtraData( $this->itemData[ $itemClass ] ) as $id => $extraData )
				{
					$this->itemData[ $itemClass ][ $id ]['extra'] = $extraData;
				}
			}
		}
				
		/* Load author data */
		if( count( $membersToLoad ) )
		{
			$this->authorData = iterator_to_array( \IPS\Db::i()->select( \IPS\Member::columnsForPhoto(), 'core_members', \IPS\Db::i()->in( 'member_id', $membersToLoad ) )->setKeyField( 'member_id' ) );
		}
		
		/* Load container data */
		foreach ( $containersToLoad as $containerClass => $containerIds )
		{
			$this->containerData[ $containerClass ] = iterator_to_array( \IPS\Db::i()->select( $containerClass::basicDataColumns(), $containerClass::$databaseTable, \IPS\Db::i()->in( $containerClass::$databasePrefix . $containerClass::$databaseColumnId, $containerIds ) )->setKeyField( $containerClass::$databasePrefix . $containerClass::$databaseColumnId ) );
		}
		
		/* Load reputation data */
		$reputation = array();
		if ( count( $reputationIds ) )
		{			
			$clause = array();
			$binds = array();
			foreach ( $reputationIds as $class => $ids )
			{
				$clause[] = "( app=? AND type=? AND " . \IPS\Db::i()->in( 'type_id', $ids ) . " )";
				$binds[] = $class::$application;
				$binds[] = $class::$reputationType;
			}
			
			$where = array( array_merge( array( implode( ' OR ', $clause ) ), $binds ) );
			switch( \IPS\Settings::i()->reputation_point_types )
			{
				case 'positive':
				case 'like':
					$where[] = array( 'rep_rating=?', "1" );
					break;					
				case 'negative':
					$where[] = array( 'rep_rating=?', "-1" );
					break;
			}
			
			foreach ( \IPS\Db::i()->select( array( 'app', 'type', 'type_id', 'member_id', 'rep_rating' ), 'core_reputation_index', $where ) as $rep )
			{
				$this->reputationData[ $rep['app'] ][ $rep['type'] ][ $rep['type_id'] ][ $rep['member_id'] ] = $rep['rep_rating'];
			}
		}
		
		/* Load rating reviews */
		foreach ( $reviewRatings as $reviewClass => $reviewIds )
		{
			$this->reviewRatings[ $reviewClass ] = iterator_to_array( \IPS\Db::i()->select( array( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId, $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] ), $reviewClass::$databaseTable, \IPS\Db::i()->in( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId, $reviewIds ) )->setKeyField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnId )->setValueField( $reviewClass::$databasePrefix . $reviewClass::$databaseColumnMap['rating'] ) );
		}
		
		/* Load data for the "I posted in this" stars */
		if ( count( $itemIndexIds ) and \IPS\Member::loggedIn()->member_id )
		{
			$this->iPostedIn = iterator_to_array( \IPS\Db::i()->select( 'index_item_index_id', 'core_search_index', array( array( \IPS\Db::i()->in( 'index_item_index_id', $itemIndexIds ) ), array( 'index_author=?', \IPS\Member::loggedIn()->member_id ) ), NULL, NULL, NULL, NULL, \IPS\Db::SELECT_DISTINCT ) );
		}
		
		/* Load tags and prefixes */
		if ( count( $itemIndexIds ) )
		{
			$this->tags = iterator_to_array( \IPS\Db::i()->select( 'index_id, GROUP_CONCAT(index_tag) as index_tags', 'core_search_index_tags', array( array( \IPS\Db::i()->in( 'index_id', $itemIndexIds ) ), array( 'index_is_prefix = 0' ) ), NULL, NULL, 'index_id' )->setKeyField('index_id') );
			$this->prefixes = iterator_to_array( \IPS\Db::i()->select( 'index_id, index_tag as index_prefix', 'core_search_index_tags', array( array( \IPS\Db::i()->in( 'index_id', $itemIndexIds ) ), array( 'index_is_prefix = 1' ) ) )->setKeyField('index_id') );
		}

		/* Set that we're initiated */
		$this->initated = TRUE;
	}
	
	/**
	 * Get current
	 *
	 * @return	\IPS\Patterns\ActiveRecord
	 */
	public function current()
	{
		$data = parent::current();

		$class = $data['index_class'];
		$itemClass = in_array( 'IPS\Content\Comment', class_parents( $class ) ) ? $class::$itemClass : $class;
		$containerClass = isset( $itemClass::$containerNodeClass ) ? $itemClass::$containerNodeClass : NULL;
		
		$reputationData = array();
		if ( in_array( 'IPS\Content\Reputation', class_implements( $class ) ) and isset( $this->reputationData[ $class::$application ][ $class::$reputationType ][ $data['index_object_id'] ] ) )
		{
			$reputationData = $this->reputationData[ $class::$application ][ $class::$reputationType ][ $data['index_object_id'] ];
		}
		
		$itemData = $this->itemData[ $itemClass ][ $data['index_item_id'] ];
		if ( $class == 'IPS\core\Statuses\Status' and $data['index_container_id'] != $data['index_author'] )
		{
			$itemData['profile'] = $this->authorData[ $data['index_container_id'] ];
		}
		if ( isset( $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ] ) )
		{
			$memberId = $itemData[ $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] ];
			if ( isset( $this->authorData[ $memberId ] ) )
			{
				$itemData['author'] = $this->authorData[ $memberId ];
			}
		}

		if ( isset( $this->tags[ $data['index_item_index_id'] ] ) )
		{
			$data['index_tags'] = $this->tags[ $data['index_item_index_id'] ]['index_tags'];
		}

		if ( isset( $this->prefixes[ $data['index_item_index_id'] ]) )
		{
			$data['index_prefix'] = $this->prefixes[ $data['index_item_index_id'] ]['index_prefix'];
		}
		
		return new Result\Content(
			$data,
			isset( $this->authorData[ $data['index_author'] ] ) ? $this->authorData[ $data['index_author'] ] : array( 'member_id' => 0, 'name' => \IPS\Member::loggedIn()->language()->addToStack('guest'), 'members_seo_name' => '', 'pp_main_photo' => NULL, 'pp_photo_type' => 'none' ),
			$itemData,
			( $containerClass and isset( $this->containerData[ $containerClass ] ) and isset( $this->containerData[ $containerClass ][ $data['index_container_id'] ] ) ) ? $this->containerData[ $containerClass ][ $data['index_container_id'] ] : NULL,
			$reputationData,
			isset( $this->reviewRatings[ $data['index_class'] ][ $data['index_object_id'] ] ) ? $this->reviewRatings[ $data['index_class'] ][ $data['index_object_id'] ] : NULL,
			in_array( $data['index_item_index_id'], $this->iPostedIn )
		);
	}
	
	/**
	 * Rewind
	 *
	 * @return	void
	 */
	public function rewind()
	{
		if ( !$this->initated )
		{
			$this->init();
		}
		return parent::rewind();
	}
	
	/**
	 * Get count
	 *
	 * @param	bool	$allRows	If TRUE, will get the number of rows ignoring the limit
	 * @return	int
	 */
	public function count( $allRows = FALSE )
	{
		if ( $allRows )
		{
			if ( is_integer( $this->countAllRows ) )
			{
				return $this->countAllRows;
			}
			else if ( gettype( $this->countAllRows ) === 'object' and get_class( $this->countAllRows ) === 'IPS\Db\Select' )
			{
				$this->countAllRows = $this->countAllRows->first();
				
				return $this->countAllRows;
			}
			else
			{
				throw new \LogicException;
			}
		}
		else
		{
			return parent::count();
		}
	}
	
	/**
	 * Add in "extra" items
	 *
	 * @param	array				$extra		Types ('register', 'follow_member', 'follow_content', 'photo', 'like', 'rep_neg')
	 * @param	\IPS\Member|NULL	$author		The author to limit extra items to
	 * @param	\IPS\DateTime|NULL	$lastTime	If provided, only items since this date is included. If NULL, it works out which to include based on what results are being shown
	 * @param	\IPS\DateTime|NULL	$firstTime	If provided, only items before this date is included. If NULL, it works out which to include based on what results are being shown
	 * @return	array
	 * @note	Each thing is limited to 10 items. Even though this may result in accuracies, the limit is necessary so that if there's more extra items that content it doesn't create lots of queries and slow loading
	 */
	public function addExtraItems( $extra, \IPS\Member $author = NULL, $lastTime=NULL, $firstTime = NULL )
	{
		/* Work out the timestamps */
		$results = iterator_to_array( $this );
		if ( $firstTime )
		{
			$firstTime = $firstTime->getTimestamp();
		}
		elseif ( isset( \IPS\Request::i()->page ) and \IPS\Request::i()->page != 1 )
		{
			foreach ( $results as $result )
			{
				$firstTime = $result->createdDate->getTimestamp();
				break;
			}
		}
		if ( $lastTime )
		{
			$lastTime = $lastTime->getTimestamp();
		}
		else
		{
			foreach ( $results as $result )
			{
				$lastTime = $result->createdDate->getTimestamp();
			}
		}
		
		/* We need at least a last time */
		if ( !$lastTime )
		{
			return $results;
		}
				
		/* Get the extra items... */
		$extraItems = array();
		if ( $lastTime )
		{
			/* Users registered */
			if ( in_array( 'register', $extra ) )
			{
				$where = array( array( 'joined>?', $lastTime ), array( 'name<>?', '' ) );
				if ( $firstTime )
				{
					$where[] = array( 'joined<?', $firstTime );
				}
				if ( $author )
				{
					$where[] = array( 'core_members.member_id=?', $author->member_id );
				}
				foreach ( \IPS\Db::i()->select( 'core_members.member_id, core_members.name, core_members.members_seo_name, core_members.joined, core_validating.new_reg', 'core_members', $where, 'joined DESC', 10 )
									  		   ->join( 'core_validating', 'core_validating.member_id=core_members.member_id' ) as $member )
				{
					if ( empty( $member['new_reg'] ) )
					{
						$extraItems[] = new Result\Custom( \IPS\DateTime::ts( $member['joined'] ),  \IPS\Member::loggedIn()->language()->addToStack( 'activity_member_joined', FALSE, array( 'htmlsprintf' => \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $member['member_id'], $member['name'], $member['members_seo_name'] ) ) ) );
					}
				}
			}
			
			/* Users changed photo */
			if ( in_array( 'photo', $extra ) )
			{
				$where = array( array( 'photo_last_update>?', $lastTime ) );
				if ( $firstTime )
				{
					$where[] = array( 'photo_last_update<?', $firstTime );
				}
				if ( $author )
				{
					$where[] = array( 'member_id=?', $author->member_id );
				}
				foreach ( \IPS\Db::i()->select( array_merge( \IPS\Member::columnsForPhoto(), array( 'photo_last_update' ) ), 'core_members', $where, 'photo_last_update DESC', 10 ) as $member )
				{					
					$extraItems[] = new Result\Custom(
						\IPS\DateTime::ts( $member['photo_last_update'] ),
						\IPS\Member::loggedIn()->language()->addToStack( 'activity_member_updated_photo', FALSE, array( 'htmlsprintf' => \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userLinkFromData( $member['member_id'], $member['name'], $member['members_seo_name'] ) ) ),
						\IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userPhotoFromData( $member['member_id'], $member['name'], $member['members_seo_name'], \IPS\Member::photoUrl( $member ), 'tiny' )
					);
				}
			}
			
			/* Follow */
			if ( in_array( 'follow_member', $extra ) or in_array( 'follow_content', $extra ) )
			{						
				$where = array( array( 'follow_is_anon=0' ), array( 'follow_added>?', $lastTime ) );
				if ( $firstTime )
				{
					$where[] = array( 'follow_added<?', $firstTime );
				}
				if ( in_array( 'follow_member', $extra ) xor in_array( 'follow_content', $extra ) )
				{
					if ( in_array( 'follow_member', $extra ) )
					{
						$where[] = array( 'follow_app=? AND follow_area=?', 'core', 'member' );
					}
					else
					{
						$where[] = array( '( follow_app!=? OR follow_area!=? )', 'core', 'member' );
					}
				}
				if ( $author )
				{
					if ( in_array( 'follow_member', $extra ) and in_array( 'follow_content', $extra ) )
					{
						$where[] = array( '( follow_member_id=? OR ( follow_app=? AND follow_area=? AND follow_rel_id=? ) )', $author->member_id, 'core', 'member', $author->member_id );
					}
					elseif ( in_array( 'follow_member', $extra ) )
					{
						$where[] = array( 'follow_rel_id=?', $author->member_id );
					}
					else
					{
						$where[] = array( 'follow_member_id=?', $author->member_id );
					}
				}
				
				/* If an application was not updated or is not installed, do not try to fetch it or we can get a class does not exist fatal error */
				$where[] = array( "follow_app IN('" . implode( "','", array_keys( \IPS\Application::enabledApplications() ) ) . "')" );

				foreach ( \IPS\Db::i()->select( array( 'follow_app', 'follow_area', 'follow_rel_id', 'follow_member_id', 'follow_added' ), 'core_follow', $where, 'follow_added DESC', 10 ) as $follow )
				{
					if ( $follow['follow_app'] == 'core' and $follow['follow_area'] == 'member' )
					{
						$extraItems[] = new Result\Custom(
							\IPS\DateTime::ts( $follow['follow_added'] ),
							\IPS\Member::loggedIn()->language()->addToStack( 'activity_member_followed', FALSE, array( 'htmlsprintf' => array( \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( \IPS\Member::load( $follow['follow_member_id'] ) ), \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( \IPS\Member::load( $follow['follow_rel_id'] ) ) ) ) )
						);
					}
					else
					{
						$class = 'IPS\\' . $follow['follow_app'] . '\\' . ucfirst( $follow['follow_area'] );
						try
						{
							if ( class_exists( $class ) )
							{
								$thingBeingFollowed = $class::loadAndCheckPerms( $follow['follow_rel_id'] );

								$extraItems[] = new Result\Custom(
									\IPS\DateTime::ts( $follow['follow_added'] ),
									\IPS\Member::loggedIn()->language()->addToStack( 'activity_member_followed', FALSE, array( 'htmlsprintf' => array( \IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( \IPS\Member::load( $follow['follow_member_id'] ) ), \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $thingBeingFollowed->url(), FALSE, $thingBeingFollowed instanceof \IPS\Node\Model ? $thingBeingFollowed->_title : $thingBeingFollowed->mapped('title'), FALSE ) ) ) )
								);
							}

						}
						catch ( \OutOfRangeException $e ) { }
					}
				}
			}
			
			/* Likes */
			if ( \IPS\Member::loggedIn()->group['gbw_view_reps'] and ( in_array( 'like', $extra ) or in_array( 'rep_neg', $extra ) ) )
			{
				$where = array( array( 'rep_date>?', $lastTime ) );
				if ( $firstTime )
				{
					$where[] = array( 'rep_date<?', $firstTime );
				}
				if ( !in_array( 'rep_neg', $extra ) or ( \IPS\Settings::i()->reputation_point_types != 'both' and \IPS\Settings::i()->reputation_point_types != 'negative' ) )
				{
					$where[] = array( 'rep_rating=1' );
				}
				elseif ( !in_array( 'like', $extra ) or ( \IPS\Settings::i()->reputation_point_types == 'negative' ) )
				{
					$where[] = array( 'rep_rating=-1' );
				}
				if ( $author )
				{
					$where[] = array( '( member_id=? OR member_received=? )', $author->member_id, $author->member_id );
				}

				/* get only the reputation data for installed applications */
				$where[] =  \IPS\Db::i()->in( 'app', array_keys( \IPS\Application::applications() ) ) ;

				foreach ( \IPS\Db::i()->select( '*', 'core_reputation_index', $where, 'rep_date DESC', 10 ) as $rep )
				{
					try
					{
						$thingBeingRepped = NULL;
						foreach ( \IPS\Application::load( $rep['app'] )->extensions( 'core', 'ContentRouter', TRUE, TRUE ) as $ext )
						{
							foreach ( $ext->classes as $class )
							{
								if ( in_array( 'IPS\Content\Reputation', class_implements( $class ) ) and $class::$reputationType == $rep['type'] )
								{
									$thingBeingRepped = $class::loadAndCheckPerms( $rep['type_id'] );
									break;
								}
								if ( isset( $class::$commentClass ) )
								{
									$commentClass = $class::$commentClass;
									if ( in_array( 'IPS\Content\Reputation', class_implements( $commentClass ) ) and $commentClass::$reputationType == $rep['type'] )
									{
										$thingBeingRepped = $commentClass::loadAndCheckPerms( $rep['type_id'] );
										break;
									}
								}
								if ( isset( $class::$reviewClass ) )
								{
									$reviewClass = $class::$reviewClass;
									if ( in_array( 'IPS\Content\Reputation', class_implements( $reviewClass ) ) and $reviewClass::$reputationType == $rep['type'] )
									{
										$thingBeingRepped = $reviewClass::loadAndCheckPerms( $rep['type_id'] );
										break;
									}
								}
							}
						}
						if ( !$thingBeingRepped )
						{
							throw new \OutOfRangeException;
						}
						
						if ( \IPS\Settings::i()->reputation_point_types == 'like' )
						{
							$lang = 'activity_member_liked';
						}
						elseif ( $rep['rep_rating'] > 0 )
						{
							$lang = 'activity_rep_pos';
						}
						else
						{
							$lang = 'activity_rep_neg';
						}
						
						$extraItems[] = new Result\Custom(
							\IPS\DateTime::ts( $rep['rep_date'] ),
							\IPS\Member::loggedIn()->language()->addToStack( $lang, FALSE, array( 'htmlsprintf' => array(
								\IPS\Theme::i()->getTemplate( 'global', 'core', 'front' )->userLink( \IPS\Member::load( $rep['member_id'] ) ),
								$thingBeingRepped->indefiniteArticle(),
								\IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $thingBeingRepped->url(), FALSE, $thingBeingRepped instanceof \IPS\Content\Item ? $thingBeingRepped->mapped('title') : $thingBeingRepped->item()->mapped('title'), FALSE )
							) ) )
						);
					}
					catch ( \OutOfRangeException $e ) { }
				}
			}
		}
		
		/* Merge them in */
		if ( !empty( $extraItems ) )
		{
			$results = array_merge( $results, $extraItems );
			uasort( $results, function( $a, $b )
			{
				if ( $a->createdDate->getTimestamp() == $b->createdDate->getTimestamp() )
				{
					return 0;
				}
				elseif( $a->createdDate->getTimestamp() < $b->createdDate->getTimestamp() )
				{
					return 1;
				}
				else
				{
					return -1;
				}
			} );
		}
		
		/* And return */
		return $results;
	}
}