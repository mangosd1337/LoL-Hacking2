<?php
/**
 * @brief		Content Item Feed Widget
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	forums
 * @since		16 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Content Item Feed Widget
 */
abstract class _Widget extends \IPS\Widget\PermissionCache
{
	/**
	 * Class
	 */
	protected static $class;
			
	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
		/* Init */
		$class = static::$class;
 		if ( $form === null )
		{
	 		$form = new \IPS\Helpers\Form;
 		}
 		
 		/* Block title */ 		
		$form->add( new \IPS\Helpers\Form\Text( 'widget_feed_title', isset( $this->configuration['widget_feed_title'] ) ? $this->configuration['widget_feed_title'] : \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl' ) ) );

		/* Container */
		if ( isset( $class::$containerNodeClass ) )
		{
			$form->add( new \IPS\Helpers\Form\Node( 'widget_feed_container_' . $class::$title, isset( $this->configuration['widget_feed_container'] ) ? $this->configuration['widget_feed_container'] : 0, FALSE, array(
				'class'           => $class::$containerNodeClass,
				'zeroVal'         => 'all',
				'permissionCheck' => 'view',
				'multiple'        => true,
				'forceOwner'	  => false,
			) ) );
		}
		
		/* Use permissions? */
		if ( in_array( 'IPS\Content\Permissions', class_implements( $class ) ) )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'widget_feed_use_perms', isset( $this->configuration['widget_feed_use_perms'] ) ? $this->configuration['widget_feed_use_perms'] : TRUE, FALSE ) );
		}
		
		/* Types */
		if ( in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			$types = array(
				'any'    => 'mod_confirm_either',
				'open'   => 'mod_confirm_unlock',
				'closed' => 'mod_confirm_lock'
			);

			$form->add( new \IPS\Helpers\Form\Radio( 'widget_feed_status_locked', isset( $this->configuration['widget_feed_status_locked'] ) ? $this->configuration['widget_feed_status_locked'] : 'any', FALSE, array( 'options' => $types ) ) );
		}
		if ( in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			$types = array(
				'any'       => 'mod_confirm_either',
				'pinned'    => 'mod_confirm_pin',
				'notpinned' => 'mod_confirm_unpin'
			);

			$form->add( new \IPS\Helpers\Form\Radio( 'widget_feed_status_pinned', isset( $this->configuration['widget_feed_status_pinned'] ) ? $this->configuration['widget_feed_status_pinned'] : 'any', FALSE, array( 'options' => $types ) ) );
		}
		if ( in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			$types = array(
				'any'         => 'mod_confirm_either',
				'featured'    => 'mod_confirm_feature',
				'notfeatured' => 'mod_confirm_unfeature'
			);

			$form->add( new \IPS\Helpers\Form\Radio( 'widget_feed_status_featured', isset( $this->configuration['widget_feed_status_featured'] ) ? $this->configuration['widget_feed_status_featured'] : 'any', FALSE, array( 'options' => $types ) ) );
		}
		if ( in_array( 'IPS\Content\Hideable', class_implements( $class ) ) )
		{
			$types = array(
				'any'         => 'mod_confirm_either',
				'visible'     => 'mod_confirm_visible',
				'hidden'      => 'mod_confirm_hidden'
			);
	
			$form->add( new \IPS\Helpers\Form\Radio( 'widget_feed_comment_status_visible', isset( $this->configuration['widget_feed_comment_status_visible'] ) ? $this->configuration['widget_feed_comment_status_visible'] : 'any', FALSE, array( 'options' => $types ) ) );
		}
		
		/* Author */
		$author = NULL;
		try
		{
			if ( isset( $this->configuration['widget_feed_author'] ) and is_array( $this->configuration['widget_feed_author'] ) )
			{
				foreach( $this->configuration['widget_feed_author']  as $id )
				{
					$author[ $id ] = \IPS\Member::load( $id );
				}
			}
		}
		catch( \OutOfRangeException $ex ) { }
		$form->add( new \IPS\Helpers\Form\Member( 'widget_feed_author', $author, FALSE, array( 'multiple' => NULL ) ) );
		
		/* Minimum comments/reviews */
		if ( isset( $class::$commentClass ) )
		{
			if ( $class::$firstCommentRequired )
			{
				$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_min_posts', isset( $this->configuration['widget_feed_min_posts'] ) ? $this->configuration['widget_feed_min_posts'] : 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) ) );
			}
			else
			{
				$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_min_comments', isset( $this->configuration['widget_feed_min_comments'] ) ? $this->configuration['widget_feed_min_comments'] : 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) ) );
			}
		}
		if ( isset( $class::$reviewClass ) )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_min_reviews', isset( $this->configuration['widget_feed_min_reviews'] ) ? $this->configuration['widget_feed_min_reviews'] : 0, FALSE, array( 'unlimitedLang' => 'any', 'unlimited' => 0 ) ) );
		}
		
		/* Rating */
		if ( in_array( 'IPS\Content\Ratings', class_implements( $class ) ) and isset( $class::$databaseColumnMap['rating_average'] ) )
		{
			$form->add( new \IPS\Helpers\Form\Rating( 'widget_feed_min_rating', isset( $this->configuration['widget_feed_min_rating'] ) ? $this->configuration['widget_feed_min_rating'] : 0, FALSE, array() ) );
		}
		
		/* Number to show */
 		$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_show', isset( $this->configuration['widget_feed_show'] ) ? $this->configuration['widget_feed_show'] : 5, TRUE ) );
 		
 		/* Date restrict */
 		$form->add( new \IPS\Helpers\Form\Number( 'widget_feed_restrict_days', isset( $this->configuration['widget_feed_restrict_days'] ) ? $this->configuration['widget_feed_restrict_days'] : -1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('widget_feed_restrict_days_suffix') ) );
 		
 		/* Sort */
 		$sortOptions = array();
 		foreach ( array( 'updated', 'title', 'num_comments', 'date', 'views', 'rating_average' ) as $k )
 		{
	 		if ( isset( $class::$databaseColumnMap[ $k ] ) )
	 		{
		 		$sortOptions[ $class::$databaseColumnMap[ $k ] ] = 'sort_' . $k;
	 		}
 		}
		$form->add( new \IPS\Helpers\Form\Select( 'widget_feed_sort_on', isset( $this->configuration['widget_feed_sort_on'] ) ? $this->configuration['widget_feed_sort_on'] : $class::$databaseColumnMap['updated'], FALSE, array( 'options' => $sortOptions ) ), NULL, NULL, NULL, 'widget_feed_sort_on' );

		$form->add( new \IPS\Helpers\Form\Select( 'widget_feed_sort_dir', isset( $this->configuration['widget_feed_sort_dir'] ) ? $this->configuration['widget_feed_sort_dir'] : 'desc', FALSE, array(
            'options' => array(
	            'desc'   => 'descending',
	            'asc'    => 'ascending'
            )
        ) ) );

 		return $form;
 	}
 	
 	/**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( $values )
 	{
	 	$class = static::$class;
	 	
 		if ( is_array( $values[ 'widget_feed_container_' . $class::$title ] ) )
 		{
	 		$values['widget_feed_container'] = array_keys( $values[ 'widget_feed_container_' . $class::$title ] );
			unset( $values[ 'widget_feed_container_' . $class::$title ] );
 		}
 		
 		if ( is_array( $values['widget_feed_author'] ) )
 		{
	 		$members = array();
	 		foreach( $values['widget_feed_author'] as $member )
	 		{
		 		$members[] = $member->member_id;
	 		}
	 		
	 		$values['widget_feed_author'] = $members;
 		}
 		
 		return $values;
 	}
 	
 	/**
	 * Get where clause
	 *
	 * @return	array
	 */
	protected function buildWhere()
	{
		$class = static::$class;
		$where = array();
				
		/* Container */
		if ( isset( $class::$containerNodeClass ) and !empty( $this->configuration['widget_feed_container'] ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['container'], $this->configuration['widget_feed_container'] ) );
		}
		
		/* Status */
		if ( isset( $this->configuration['widget_feed_status_locked'] ) and in_array( 'IPS\Content\Lockable', class_implements( $class ) ) )
		{
			if ( $this->configuration['widget_feed_status_locked'] == 'closed' )
			{
				$where[] = isset( $class::$databaseColumnMap['locked'] ) ? array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', 1 ) : array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['status'] . '=?', 'closed' );
			}
			elseif ( $this->configuration['widget_feed_status_locked'] == 'open' )
			{
				$where[] = isset( $class::$databaseColumnMap['locked'] ) ? array( $class::$databaseTable . '.' .  $class::$databasePrefix . $class::$databaseColumnMap['locked'] . '=?', 0 ) : array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['status'] . '=?', 'open' );
			}
		}

		if ( isset( $this->configuration['widget_feed_status_featured'] ) and in_array( 'IPS\Content\Featurable', class_implements( $class ) ) )
		{
			if ( $this->configuration['widget_feed_status_featured'] == 'notfeatured' )
			{
				$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['featured'] . '=0' );
			}
			elseif ( $this->configuration['widget_feed_status_featured'] == 'featured' )
			{
				$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['featured'] . '=1' );
			}
		}

		if ( isset( $this->configuration['widget_feed_status_pinned'] ) and in_array( 'IPS\Content\Pinnable', class_implements( $class ) ) )
		{
			if ( $this->configuration['widget_feed_status_pinned'] == 'notpinned' )
			{
				$where[] = array( $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . '=0' );
			}
			elseif ( $this->configuration['widget_feed_status_pinned'] == 'pinned' )
			{
				$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['pinned'] . '=1' );
			}
		}

		/* Author */
		if ( isset( $this->configuration['widget_feed_author'] ) and is_array( $this->configuration['widget_feed_author'] ) )
		{
			$where[] = array( \IPS\Db::i()->in( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['author'], $this->configuration['widget_feed_author'] ) );
		}
		
		/* Min comments/reviews */
		if ( isset( $this->configuration['widget_feed_min_posts'] ) and $this->configuration['widget_feed_min_posts'] )
		{
			$where[] = array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_comments'] . '>=?', (int) $this->configuration['widget_feed_min_posts'] );
		}
		if ( isset( $this->configuration['widget_feed_min_comments'] ) and $this->configuration['widget_feed_min_comments'] )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_comments'] . '>?', (int) $this->configuration['widget_feed_min_comments'] );
		}
		if ( isset( $this->configuration['widget_feed_min_reviews'] ) and $this->configuration['widget_feed_min_reviews'] )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['num_reviews'] . '>?', (int) $this->configuration['widget_feed_min_reviews'] );
		}
		
		/* Rating */
		if ( isset( $this->configuration['widget_feed_min_rating'] ) and $this->configuration['widget_feed_min_rating'] )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['rating_average'] . '>?', (int) $this->configuration['widget_feed_min_rating'] );
		}
		
		/* Limit to days */
		if ( isset( $this->configuration['widget_feed_restrict_days'] ) and $this->configuration['widget_feed_restrict_days'] > 0 )
		{
			$where[] = array(  $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnMap['date'] . '>?',  \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $this->configuration['widget_feed_restrict_days'] . 'D' ) )->getTimestamp() );
		}

		return $where;
	}
 	
	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render()
	{
		$class = static::$class;
		$where = $this->buildWhere();
		
		if ( isset( $this->configuration['widget_feed_tags'] ) and is_array( $this->configuration['widget_feed_tags'] ) and count( $this->configuration['widget_feed_tags'] ) )
		{
			$tagWhere = array();
			$binds    = array( $class::$application, $class::$module );
			foreach( $this->configuration['widget_feed_tags'] as $tag )
			{
				$tagWhere[] = "( ? LIKE CONCAT( tag_text, '%') )";
				$binds[] = $tag;
			}
			
			$where [] = array( 'primary_id_field IN (?)', \IPS\Db::i()->select( 'tag_meta_id', 'core_tags', array( array_merge( array( 'tag_meta_app=? AND tag_meta_area=? AND (' . implode( ' OR ', $tagWhere ) . ') and core_tags.tag_meta_id=' . $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId ), $binds ) ) ) );
		}

		/* What visible status are we checking? */
		$hidden	= \IPS\Content\Hideable::FILTER_AUTOMATIC;

		if( isset( $this->configuration['widget_feed_comment_status_visible'] ) )
		{
			switch( $this->configuration['widget_feed_comment_status_visible'] )
			{
				case 'visible':
					$hidden	= \IPS\Content\Hideable::FILTER_PUBLIC_ONLY;
				break;

				case 'hidden':
					$hidden	= \IPS\Content\Hideable::FILTER_ONLY_HIDDEN;
				break;
			}
		}
		
		$items = $class::getItemsWithPermission(
			$where,	/* Where clause */
			( isset( $this->configuration['widget_feed_sort_on'] ) and isset( $this->configuration['widget_feed_sort_dir'] ) ) ? ( $class::$databasePrefix . $this->configuration['widget_feed_sort_on'] . ' ' . $this->configuration['widget_feed_sort_dir'] ) : NULL, /* Order */
			isset( $this->configuration['widget_feed_show'] ) ? $this->configuration['widget_feed_show'] : 5, /* Limit */
			( !isset( $this->configuration['widget_feed_use_perms'] ) or $this->configuration['widget_feed_use_perms'] ) ? 'read' : NULL, /* Permission key to check against */
			$hidden /* Whether or not to include hidden items */
		);

		if ( count( $items ) )
		{
			return $this->output( $items, isset( $this->configuration['widget_feed_title'] ) ? $this->configuration['widget_feed_title'] : \IPS\Member::loggedIn()->language()->addToStack( $class::$title . '_pl' ) );
		}
		else
		{
			return '';
		}
	}
}