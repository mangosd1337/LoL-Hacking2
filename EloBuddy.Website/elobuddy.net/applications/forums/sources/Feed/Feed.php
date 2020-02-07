<?php
/**
 * @brief		Feed Import Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		4 Fed 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Feed Import Node
 */
class _Feed extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'forums_rss_import';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'rss_import_';
				
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'rss_import';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'forums',
		'module'	=> 'forums',
		'prefix'	=> 'rss_'
	);
	
	/**
	 * [Node] Get title
	 *
	 * @return	string
	 */
	protected function get__title()
	{
		return $this->title;
	}
	
	/**
	 * [Node] Get description
	 *
	 * @return	string
	 */
	protected function get__description()
	{
		return $this->url;
	}
	
	/**
	 * [Node] Get whether or not this node is enabled
	 *
	 * @note	Return value NULL indicates the node cannot be enabled/disabled
	 * @return	bool|null
	 */
	protected function get__enabled()
	{
		return $this->enabled;
	}

	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
		if ( $enabled )
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'rssimport' ) );
		}
		$this->enabled = $enabled;
	}
	
	/**
	 * [Node] Get buttons to display in tree
	 * Example code explains return value
	 *
	 * @code
	 	array(
	 		array(
	 			'icon'	=>	'plus-circle', // Name of FontAwesome icon to use
	 			'title'	=> 'foo',		// Language key to use for button's title parameter
	 			'link'	=> \IPS\Http\Url::internal( 'app=foo...' )	// URI to link to
	 			'class'	=> 'modalLink'	// CSS Class to use on link (Optional)
	 		),
	 		...							// Additional buttons
	 	);
	 * @endcode
	 * @param	string	$url		Base URL
	 * @param	bool	$subnode	Is this a subnode?
	 * @return	array
	 */
	public function getButtons( $url, $subnode=FALSE )
	{
		$buttons = parent::getButtons( $url, $subnode );
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction('rss_run') )
		{
			$buttons = array_merge( array( 'run' => array(
				'icon'	=> 'refresh',
				'title'	=> 'rss_run',
				'link'	=> $url->setQueryString( array( 'do' => 'run', 'id' => $this->_id ) )
			) ), $buttons );
		}
		
		return $buttons;
	}
	
	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->addHeader('rss_import_url');
		$form->add( new \IPS\Helpers\Form\Url( 'rss_import_url', $this->url, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'rss_import_auth_user', $this->auth_user ) );
		$form->add( new \IPS\Helpers\Form\Password( 'rss_import_auth_pass', $this->auth_pass ) );
		$form->addHeader('rss_import_details');
		$form->add( new \IPS\Helpers\Form\Node( 'rss_import_forum_id', $this->forum_id, TRUE, array( 'class' => 'IPS\forums\Forum', 'permissionCheck' => function ( $forum )
		{
			return $forum->sub_can_post and !$forum->redirect_url;
		} ) ) );
		$form->add( new \IPS\Helpers\Form\Member( 'rss_import_mid', \IPS\Member::load( $this->mid ), TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'rss_import_showlink', $this->showlink ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'rss_import_topic_open', $this->topic_open, FALSE, array( 'options' => array( 1 => 'unlocked', 0 => 'locked' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'rss_import_topic_hide', $this->topic_hide, FALSE, array( 'options' => array( 0 => 'unhidden', 1 => 'hidden' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'rss_import_topic_pre', $this->topic_pre, FALSE, array( 'trim' => FALSE ) ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['rss_import_url'] ) )
		{
			try
			{
				$request = $values['rss_import_url']->request();
				
				if ( $values['rss_import_auth_user'] or $values['rss_import_auth_pass'] )
				{
					$request = $request->login( $values['rss_import_auth_user'], $values['rss_import_auth_pass'] );
				}
				
				$response = $request->get();
				
				if ( $response->httpResponseCode == 401 )
				{
					throw new \DomainException('rss_import_auth');
				}
				
				$response = $response->decodeXml();
				if ( !( $response instanceof \IPS\Xml\Rss ) and !( $response instanceof \IPS\Xml\Atom ) )
				{
					throw new \DomainException('rss_import_invalid');
				}
			}
			catch ( \IPS\Http\Request\Exception $e )
			{
				throw new \DomainException( 'form_url_bad' );
			}
			catch ( \ErrorException $e )
			{
				throw new \DomainException( 'rss_import_invalid' );
			}

			$values['title'] = (string) $request->get()->decodeXml()->channel->title;
		}
		
		if( isset( $values['rss_import_forum_id'] ) )
		{
			$values['forum_id'] = $values['rss_import_forum_id']->_id;
			unset( $values['rss_import_forum_id'] );
		}

		if( isset( $values['rss_import_mid'] ) )
		{
			$values['mid'] = $values['rss_import_mid']->member_id;
			unset( $values['rss_import_mid'] );
		}

		if( isset( $values['rss_import_auth_user'] ) OR isset( $values['rss_import_auth_pass'] ) )
		{
			$values['auth'] = ( $values['rss_import_auth_user'] or $values['rss_import_auth_pass'] );
		}
		
		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		$this->run();
	}

	/**
	 * Run Import
	 *
	 * @return	void
	 * @throws	\IPS\Http\Url\Exception
	 */
	public function run()
	{
		/* Skip this if the member is restricted from posting */
		if( \IPS\Member::load( $this->mid )->restrict_post or \IPS\Member::load( $this->mid )->members_bitoptions['unacknowledged_warnings'] )
		{
			return;
		}

		$previouslyImportedGuids = iterator_to_array( \IPS\Db::i()->select( 'rss_imported_guid', 'forums_rss_imported', array( 'rss_imported_impid=?', $this->id ) ) );
		
		$request = \IPS\Http\Url::external( $this->url )->request();
		if ( $this->auth )
		{
			$request = $request->login( $this->auth_user, $this->auth_pass );
		}
		$request = $request->get();
		
		$container = \IPS\forums\Forum::load( $this->forum_id );
		
		$i = 0;
		$inserts = array();
		$request = $request->decodeXml();

		if( !( $request instanceof \IPS\Xml\RssOne ) AND !( $request instanceof \IPS\Xml\Rss ) AND !( $request instanceof \IPS\Xml\Atom ) )
		{
			throw new \RuntimeException( 'rss_import_invalid' );
		}

		foreach ( $request->articles( $this->id ) as $guid => $article )
		{
			if ( !in_array( $guid, $previouslyImportedGuids ) )
			{
				$topic = \IPS\forums\Topic::createItem( \IPS\Member::load( $this->mid ), NULL, $article['date'], $container, $this->topic_hide );
				$topic->title = $this->topic_pre . $article['title'];
				if ( !$this->topic_open )
				{
					$topic->state = 'closed';
				}
				$topic->save();
				
				/* Add to search index */
				\IPS\Content\Search\Index::i()->index( $topic );

				$readMoreLink = '';
				if ( $article['link'] and $this->showlink )
				{
					$rel = array();

					if( \IPS\Settings::i()->posts_add_nofollow )
					{
						$rel['nofollow'] = 'nofollow';
					}
					 
					if( \IPS\Settings::i()->links_external )
					{
						$rel['external'] = 'external';
					}

					$linkRelPart = '';
					if ( count ( $rel ) )
					{
						$linkRelPart = 'rel="' .  implode($rel, ' ') . '"';
					}

					$readMoreLink = "<p><a href='{$article['link']}' {$linkRelPart}>{$this->showlink}</a></p>";
				}
				
				$member  = \IPS\Member::load( $this->mid );
				$content = \IPS\Text\Parser::parseStatic( $article['content'] . $readMoreLink, TRUE, NULL, $member, 'forums_Forums', TRUE, !(bool) $member->group['g_dohtml'] );
				
				$post = \IPS\forums\Topic\Post::create( $topic, $content, TRUE, NULL, \IPS\forums\Topic\Post::incrementPostCount( $container ), $member, $article['date'] );
				$topic->topic_firstpost = $post->pid;
				$topic->save();
				
				$inserts[] = array(
					'rss_imported_guid'	=> $guid,
					'rss_imported_tid'	=> $topic->tid,
					'rss_imported_impid'=> $this->id
				);
				
				$i++;
				
				if ( $i >= 10 )
				{
					break;
				}
			}
		}
		
		if( count( $inserts ) )
		{
			\IPS\Db::i()->insert( 'forums_rss_imported', $inserts );
		}

		$this->last_import = time();
		$this->save();

		$container->setLastComment();
		$container->save();
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Db::i()->delete( 'forums_rss_imported', array( "rss_imported_impid=?", $this->id ) );		
		return parent::delete();
	}

	/**
	 * Search
	 *
	 * @param	string		$column	Column to search
	 * @param	string		$query	Search query
	 * @param	string|null	$order	Column to order by
	 * @param	mixed		$where	Where clause
	 * @return	array
	 */
	public static function search( $column, $query, $order=NULL, $where=array() )
	{	
		if ( $column === '_title' )
		{
			$column = 'rss_import_title';
		}
		if ( $order === '_title' )
		{
			$order = 'rss_import_title';
		}
		return parent::search( $column, $query, $order, $where );
	}
}