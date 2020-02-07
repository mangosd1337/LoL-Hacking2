<?php
/**
 * @brief		Forums Application Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2014 Invision Power Services, Inc.
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		07 Jan 2014
 * @version		
 */
 
namespace IPS\forums;

/**
 * Forums Application Class
 */
class _Application extends \IPS\Application
{
	/**
	 * Init
	 *
	 * @param	void
	 */
	public function init()
	{
		/* If the viewing member cannot view the board (ex: guests must login first), then send a 404 Not Found header here, before the Login page shows in the dispatcher */
		if ( !\IPS\Member::loggedIn()->group['g_view_board'] and ( \IPS\Request::i()->module == 'forums' and \IPS\Request::i()->controller == 'forums' and isset( \IPS\Request::i()->rss ) ) )
		{
			\IPS\Output::i()->error( 'node_error', '2F219/1', 404, '' );
		}
	}
	
	/**
	 * Archive Query
	 *
	 * @param	array	$rules	Rules
	 * @return	array
	 */
	public static function archiveWhere( $rules )
	{
		$where = array();
		foreach ( $rules as $rule )
		{
			$clause = NULL;
			
			switch ( $rule['archive_field'] )
			{
				case 'lastpost':
					$clause = array( 'last_post' . $rule['archive_value'] . '?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $rule['archive_text'] . mb_strtoupper( $rule['archive_unit'] ) ) )->getTimestamp() );
					break;
				
				case 'forum':
					if ( $rule['archive_text'] )
					{
						$clause = array( 'forum_id ' . ( $rule['archive_value'] == '+' ? 'IN' : 'NOT IN' ) . '(' . $rule['archive_text'] . ')' );
					}
					break;
					
				case 'pinned':
				case 'featured':
				case 'state':
				case 'approved':
					$clause = array( $rule['archive_field'] . '=?', $rule['archive_value'] );
					break;
				
				case 'poll':
					if ( $rule['archive_value'] )
					{
						$clause = array( 'poll_state>0' );
					}
					else
					{
						$clause = array( 'poll_state=0 or poll_state IS NULL' );
					}
					break;
					
				case 'post':
				case 'view':
					$clause = array( $rule['archive_field'] . 's' . $rule['archive_value'] . '?', $rule['archive_text'] );
					break;
				
				case 'rating':
					$clause = array( 'ROUND(topic_rating_total/topic_rating_hits)' . $rule['archive_value'] . '?', $rule['archive_text'] );
					break;
				
				case 'member':
					$clause = array( 'starter_id ' . ( $rule['archive_value'] == '+' ? 'IN' : 'NOT IN' ) . '(' . $rule['archive_text'] . ')' );
					break;
				
			}
			
			if ( $clause )
			{
				if ( $rule['archive_skip'] )
				{
					$clause[0] = ( '!(' . $clause[0] . ')' );
					$where[] = $clause;
				}
				else
				{
					$where[] = $clause;
				}
			}
		}
		
		return $where;
	}

	/**
	 * [Node] Get Icon for tree
	 *
	 * @note	Return the class for the icon (e.g. 'globe')
	 * @return	string|null
	 */
	protected function get__icon()
	{
		return 'comments';
	}
	
	/**
	 * Install 'other' items.
	 *
	 * @return void
	 */
	public function installOther()
	{
		\IPS\Content\Search\Index::i()->index( \IPS\forums\Topic::load( 1 ) );
		\IPS\Content\Search\Index::i()->index( \IPS\forums\Topic\Post::load( 1 ) );
	}
	
	/**
	 * Default front navigation
	 *
	 * @code
	 	
	 	// Each item...
	 	array(
			'key'		=> 'Example',		// The extension key
			'app'		=> 'core',			// [Optional] The extension application. If ommitted, uses this application	
			'config'	=> array(...),		// [Optional] The configuration for the menu item
			'title'		=> 'SomeLangKey',	// [Optional] If provided, the value of this language key will be copied to menu_item_X
			'children'	=> array(...),		// [Optional] Array of child menu items for this item. Each has the same format.
		)
	 	
	 	return array(
		 	'rootTabs' 		=> array(), // These go in the top row
		 	'browseTabs'	=> array(),	// These go under the Browse tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'browseTabsEnd'	=> array(),	// These go under the Browse tab after all other items on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Browse tab may not exist)
		 	'activityTabs'	=> array(),	// These go under the Activity tab on a new install or when restoring the default configuraiton; or in the top row if installing the app later (when the Activity tab may not exist)
		)
	 * @endcode
	 * @return array
	 */
	public function defaultFrontNavigation()
	{
		return array(
			'rootTabs'		=> array(),
			'browseTabs'	=> array( array( 'key' => 'Forums' ) ),
			'browseTabsEnd'	=> array(),
			'activityTabs'	=> array()
		);
	}

	/**
	 * Perform some legacy URL parameter conversions
	 *
	 * @return	void
	 */
	public function convertLegacyParameters()
	{
		/* Convert &showtopic= @link https://community.invisionpower.com/4bugtrack/active-reports/double-redirects-in-legacyredirect-method-may-cause-seo-issues-r8934/ */
		if ( isset( \IPS\Request::i()->showtopic ) and is_numeric( \IPS\Request::i()->showtopic ) )
		{
			$base        = NULL;
			$seoTemplate = NULL;
			$seoTitles   = array();

			try
			{
				$topic = \IPS\forums\Topic::load( \IPS\Request::i()->showtopic );

				if ( $topic->canView() )
				{
					$base        = 'front';
					$seoTemplate = 'forums_topic';
					$seoTitles   = array( $topic->title_seo );
				}
			} catch( \Exception $e ) {}

			$url = \IPS\Http\Url::internal( 'app=forums&module=forums&controller=topic&id=' . \IPS\Request::i()->showtopic, $base, $seoTemplate, $seoTitles );

			if ( isset( \IPS\Request::i()->p ) or isset( \IPS\Request::i()->findpost ) )
			{
				$url = $url->setQueryString( array( 'do' => 'findComment', 'comment' => \IPS\Request::i()->p ?: \IPS\Request::i()->findpost ) );
			}
			elseif ( isset( \IPS\Request::i()->page ) )
			{
				$url = $url->setQueryString( array( 'page' => \IPS\Request::i()->page ) );
			}
			\IPS\Output::i()->redirect( $url );
		}

		/* Convert &showforum= */
		if ( isset( \IPS\Request::i()->showforum ) and is_numeric( \IPS\Request::i()->showforum ) )
		{
			$base        = NULL;
			$seoTemplate = NULL;
			$seoTitles   = array();

			try
			{
				$forum = \IPS\forums\Forum::load( \IPS\Request::i()->showforum );

				if ( $forum->can( 'view' ) )
				{
					$base        = 'front';
					$seoTemplate = 'forums_forum';
					$seoTitles   = array( $forum->name_seo );
				}
			} catch ( \Exception $e ) {}

			$url = \IPS\Http\Url::internal( 'app=forums&module=forums&controller=forums&id=' . \IPS\Request::i()->showforum, $base, $seoTemplate, $seoTitles );
			\IPS\Output::i()->redirect( $url );
		}
	}
}