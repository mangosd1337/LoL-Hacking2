<?php
/**
 * @brief		streams
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		02 Jul 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\front\discover;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * streams
 */
class _streams extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		if ( isset( \IPS\Request::i()->_nodeSelectName ) )
		{
			return $this->getContainerNodeElement();
		}
		
		/* Initiate the breadcrumb */
		\IPS\Output::i()->breadcrumb = array( array( \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' ), \IPS\Member::loggedIn()->language()->addToStack('activity') ) );

		/* Necessary CSS/JS */
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_streams.js', 'core' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/streams.css' ) );
		
		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/streams_responsive.css', 'core', 'front' ) );
		}
		
		/* Execute */
		return parent::execute();
	}
	
	/**
	 * View Stream
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* If this request is from an auto-poll, kill it and exit */
		if ( !\IPS\Settings::i()->auto_polling_enabled && \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->after ) )
		{
			\IPS\Output::i()->json( array( 'error' => 'auto_polling_disabled' ) );
			return;
		}
		
		/* RSS validate? */
		$member = NULL;
		
		if ( isset( \IPS\Request::i()->rss ) )
		{
			$member = \IPS\Member::load( \IPS\Request::i()->member );
			if ( !\IPS\Login::compareHashes( md5( ( $member->members_pass_hash ?: $member->email ) . $member->members_pass_salt ), (string) \IPS\Request::i()->key ) )
			{
				$member = NULL;
			}
		}
		
		$form = NULL;
		/* Viewing a particular stream? */
		if ( isset( \IPS\Request::i()->id ) )
		{
			/* Get it */
			try
			{
				$stream = \IPS\core\Stream::load( \IPS\Request::i()->id );
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C280/1', 404, '' );
			}
			
			/* Suitable for guests? */
			if ( !\IPS\Member::loggedIn()->member_id and !$member and !( ( $stream->ownership == 'all' or $stream->ownership == 'custom' ) and $stream->read == 'all' and $stream->follow == 'all' and $stream->date_type != 'last_visit' ) )
			{
				\IPS\Output::i()->error( 'stream_no_permission', '2C280/3', 403, '' );
			}
			
			if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->default ) )
			{
				\IPS\Session::i()->csrfCheck();
				
				if ( \IPS\Request::i()->default )
				{
					\IPS\Member::loggedIn()->defaultStream = $stream->_id;
				}
				else
				{
					\IPS\Member::loggedIn()->defaultStream = NULL;
				}
				
				if ( \IPS\Request::i()->isAjax() )
				{
					$defaultStream = \IPS\core\Stream::defaultStream();
					
					if ( ! $defaultStream )
					{
						\IPS\Output::i()->json( array( 'title' => NULL ) );
					}
					else
					{
						\IPS\Output::i()->json( array(
							'url'   	=> $defaultStream->url(),
							'title' 	=> htmlspecialchars( $defaultStream->_title, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ),
							'tooltip'	=> $defaultStream->_title, // We need to pass this individually for the tooltip that shows, but the JS itself will escape any entities
							'id'    	=> $defaultStream->_id
						 ) );
					}
				}
				
				\IPS\Output::i()->redirect( $stream->url() );
			}
			
			$form = $this->_buildForm( $stream );
			
			/* Set title and breadcrumb */
			\IPS\Output::i()->breadcrumb[] = array( $stream->url(), $stream->_title );
			\IPS\Output::i()->title = $stream->_title;
		}
		
		/* Or just everything? */
		else
		{
			if ( \IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->default ) )
			{
				\IPS\Session::i()->csrfCheck();

				if ( \IPS\Request::i()->default )
				{
					\IPS\Member::loggedIn()->defaultStream = 0;
				}
				else
				{
					\IPS\Member::loggedIn()->defaultStream = NULL;
				}
				
				if ( \IPS\Request::i()->isAjax() )
				{
					$defaultStream = \IPS\core\Stream::defaultStream();
					
					if ( ! $defaultStream )
					{
						\IPS\Output::i()->json( array( 'title' => NULL ) );
					}
					else
					{
						\IPS\Output::i()->json( array(
							'url'   => \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' ),
							'title' => htmlspecialchars( $defaultStream->_title, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ),
							'id'    => $defaultStream->_id
						 ) );
					}
				}
				
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_all' ) );
			}

			/* Start with a blank stream */
			$stream = \IPS\core\Stream::allActivityStream();

			/* Set the title to "All Activity" */
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('all_activity');
		}
		
		/* Look for url params that can come from view switch or load more button */	
		/* but only if we haven't submitted the form on this request */
		if( !\IPS\Request::i()->stream_submitted )
		{
			$streamFields = array( 'include_comments', 'classes', 'ownership', 'custom_members', 'read', 'follow', 'followed_types', 'date_type', 'date_start', 'date_end', 'date_relative_days', 'sort', 'tags', 'unread_links' );
			
			/* Build and format field values */
			$_values = array();
			foreach ( \IPS\Request::i() as $requestKey => $requestField )
			{
				$field = str_replace( 'stream_', '', $requestKey );
				
				if ( $field == 'custom_members' and isset( \IPS\Request::i()->stream_custom_members ) )
				{
					$members = NULL;
					foreach( explode( ',', \IPS\Request::i()->stream_custom_members ) as $name )
					{
						try
						{
							$members[] = \IPS\Member::load( $name, 'name' );
						}
						catch( \OutOfRangeException $e ) { }
					}
					
					$_values['stream_custom_members'] = $members;
				}
				else if ( in_array( $field, $streamFields ) && ( $field == 'classes' || $field == 'followed_types' ) && is_array( \IPS\Request::i()->{ 'stream_' . $field } ) )
				{
					/* Some array values will come in as key=1 params, so we only need the keys here */
					$_values[ $requestKey ] = array_keys( $requestField );
				}
				else
				{
					$_values[ $requestKey ] = $requestField;
				}				
			}
			
			$formattedValues = $stream->formatFormValues( $_values );
			
			/* Overwrite stream config if present in the request */
			foreach ( $streamFields as $k )
			{	
				$requestKey = 'stream_' . $k;
				
				if ( isset( \IPS\Request::i()->$requestKey ) )
				{
					if ( $stream->$k != $formattedValues[ $k ] )
					{
						$stream->$k = $formattedValues[ $k ];
						$stream->baseUrl = $stream->baseUrl->setQueryString( 'stream_' . $k, \IPS\Request::i()->$requestKey );
					}
				}			
			}
			
			/* Containers are special */
			if ( isset( \IPS\Request::i()->stream_containers ) and is_array( \IPS\Request::i()->stream_containers ) )
			{ 
				/* Remove null/'' values as we no longer want to restrict by container for this class if that occurs */
				$cleanedContainers = array();
				foreach( \IPS\Request::i()->stream_containers as $class => $containers )
				{
					if ( $containers )
					{
						$cleanedContainers[ $class ] = $containers;
					}
				}
				
				if ( count( array_diff_assoc( $cleanedContainers, $stream::containersToUrl( $stream->containers ) ) ) )
				{
					$stream->containers = $stream::containersFromUrl( $cleanedContainers );
					$stream->baseUrl = $stream->baseUrl->setQueryString( 'stream_containers', $cleanedContainers );
				}
			}
		}		
		
		/* Build the query */
		$query = $stream->query( $member );
		
		/* Set page or the before/after date */
		$currentPage = 1;
		if ( isset( \IPS\Request::i()->page ) AND intval( \IPS\Request::i()->page ) > 0 )
		{
			$currentPage = \IPS\Request::i()->page;
			$query->setPage( $currentPage );
		}
		if ( isset( \IPS\Request::i()->latest ) )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( \IPS\DateTime::ts( \IPS\Request::i()->latest )  );
			}
			else
			{
				$query->filterByCreateDate( \IPS\DateTime::ts( \IPS\Request::i()->latest ) );
			}
			
			$query->setLimit(350);
		}
		else if ( isset( \IPS\Request::i()->before ) )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( NULL, \IPS\DateTime::ts( \IPS\Request::i()->before ) );
			}
			else
			{
				$query->filterByCreateDate( NULL, \IPS\DateTime::ts( \IPS\Request::i()->before ) );
			}
		}
		if ( isset( \IPS\Request::i()->after ) )
		{
			if ( $stream->id and !$stream->include_comments )
			{
				$query->filterByLastUpdatedDate( \IPS\DateTime::ts( \IPS\Request::i()->after ) );
			}
			else
			{
				$query->filterByCreateDate( \IPS\DateTime::ts( \IPS\Request::i()->after ) );
			}
		}

		/* Get the results */
		$results = $query->search( NULL, $stream->tags ? explode( ',', $stream->tags ) : NULL, ( $stream->include_comments ? \IPS\Content\Search\Query::TAGS_MATCH_ITEMS_ONLY + \IPS\Content\Search\Query::TERM_OR_TAGS : \IPS\Content\Search\Query::TERM_OR_TAGS ) );
		
		/* Load data we need like the authors, etc */
		$results->init();
		
		/* Add in extra stuff? */
		if ( !isset( \IPS\Request::i()->id ) )
		{
			/* Is anything turned on? */
			$extra = array();
			foreach ( array( 'register', 'follow_member', 'follow_content', 'photo', 'like', 'rep_neg' ) as $k )
			{
				$key = "all_activity_{$k}";
				if ( \IPS\Settings::i()->$key )
				{
					$extra[] = $k;
				}
			}
			if ( !empty( $extra ) )
			{
				$results = $results->addExtraItems( $extra, NULL, ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->after ) ) ? \IPS\DateTime::ts( \IPS\Request::i()->after ) : NULL, ( \IPS\Request::i()->isAjax() and isset( \IPS\Request::i()->before ) ) ? \IPS\DateTime::ts( \IPS\Request::i()->before ) : NULL );
			}
		}
		
		/* Condensed or expanded? */
		$view = 'expanded';
		$streamID = ( \IPS\Request::i()->id ) ? \IPS\Request::i()->id : 'all';

		if ( ( isset( \IPS\Request::i()->cookie['stream_view_' . $streamID] ) and \IPS\Request::i()->cookie['stream_view_' . $streamID] == 'condensed' ) or ( isset( \IPS\Request::i()->view ) and \IPS\Request::i()->view == 'condensed' ) )
		{
			$view = 'condensed';
		}

		/* If this is an AJAX request, just show the results */
		if ( \IPS\Request::i()->isAjax() )
		{
			$output = \IPS\Theme::i()->getTemplate('streams')->streamItems( $results, TRUE, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );

			$return = array(
				'title' => htmlspecialchars( $stream->_title, \IPS\HTMLENTITIES | ENT_QUOTES, 'UTF-8', FALSE ),
				'blurb' => $stream->blurb(),
				'config' => json_encode( $stream->config() ),
				'count' => count( $results ),
				'results' => $output,
				'id' => ( $stream->id ) ? $stream->id : '',
				'url' => $stream->url()
			);

			\IPS\Output::i()->json( $return );
			return;
		}
		
		/* Display - RSS */
		if ( \IPS\Settings::i()->activity_stream_rss and isset( \IPS\Request::i()->rss ) )
		{
			$document = \IPS\Xml\Rss::newDocument( $stream->baseUrl, $stream->_title, sprintf( \IPS\Member::loggedIn()->language()->get( 'stream_rss_title' ), \IPS\Settings::i()->board_name, $stream->_title ) );
			
			foreach ( $results as $result )
			{
				if ( $result instanceof \IPS\Content\Search\Result\Content )
				{
					$result->addToRssFeed( $document );
				}
			}
			
			\IPS\Output::i()->sendOutput( $document->asXML(), 200, 'text/xml' );
			return;
		}
		
		/* Display - HTML */
		else
		{			
			/* What's the RSS Link? */
			$rssLink = NULL;
			if ( \IPS\Settings::i()->activity_stream_rss )
			{
				if ( isset( \IPS\Request::i()->id ) )
				{
					$rssLink = \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams&id={$stream->id}", 'front', 'discover_rss' );
					if ( \IPS\Member::loggedIn()->member_id )
					{
						$rssLink = $rssLink->setQueryString( 'member', \IPS\Member::loggedIn()->member_id )->setQueryString( 'key', md5( ( \IPS\Member::loggedIn()->members_pass_hash ?: \IPS\Member::loggedIn()->email ) . \IPS\Member::loggedIn()->members_pass_salt ) );
					}
				}
				else
				{
					/* It's all activity! */
					$rssLink = \IPS\Http\Url::internal( "app=core&module=discovery&controller=streams", 'front', 'discover_rss_all_activity' );
				}
			}
			
			/* Display */
			$output = \IPS\Theme::i()->getTemplate('streams')->stream( $stream, $results, $stream->id ? FALSE : TRUE, TRUE, ( $stream->id and !$stream->include_comments ) ? 'last_comment' : 'date', $view );
			
			\IPS\Output::i()->jsVars['stream_config'] = $stream->config();
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('streams')->streamWrapper( $stream, $output, $form, $rssLink, isset( \IPS\Request::i()->id ) and $stream->member and \IPS\Member::loggedIn()->member_id and $stream->member != \IPS\Member::loggedIn()->member_id );
		}
	}
	
	/**
	 * Create a new stream
	 *
	 * @return	void
	 */
	public function create()
	{
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C280/A', 403, '' );
		}
		
		$stream = new \IPS\core\Stream;
		$stream->member = \IPS\Member::loggedIn()->member_id;
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'create_new_stream' );
		\IPS\Output::i()->output = $this->_buildForm( $stream );
	}
	
	/**
	 * Edit a stream's title
	 *
	 * @return void
	 */
	public function edit()
	{
		\IPS\Session::i()->csrfCheck();
		
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C280/6', 403, '' );
		}
		
		try
		{
			$stream = \IPS\core\Stream::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C280/7', 404, '' );
		}
		
		if ( $stream->member != \IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_permission', '2C280/8', 403, '' );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Text( 'stream_title', $stream->title ) );
		
		if ( $values = $form->values() )
		{
			if ( isset( $values['stream_title'] ) and $values['stream_title'] )
			{
				$stream->title = $values['stream_title'];
				$stream->save();
				$this->_rebuildStreams();
				\IPS\Output::i()->redirect( $stream->url() );
			}	
		}
		
		/* Output */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core' )->genericBlock( $form, NULL, 'ipsPad' ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
		}

		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'stream_edit_title' );
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Copy a stream
	 *
	 * @return	void
	 */
	public function copy()
	{
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C280/4', 403, '' );
		}
		
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$stream = clone \IPS\core\Stream::load( \IPS\Request::i()->id );
			$stream->member = \IPS\Member::loggedIn()->member_id;
			$stream->save();
			$this->_rebuildStreams();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C280/5', 404, '' );
		}

		\IPS\Output::i()->redirect( $stream->url() );
	}

	/**
	 * Deletes a new stream
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		try
		{
			$stream = \IPS\core\Stream::load( \IPS\Request::i()->id );
			if ( !$stream->member or $stream->member != \IPS\Member::loggedIn()->member_id )
			{
				throw new \OutOfRangeException;
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C280/2', 404, '' );
		}
		
		$stream->delete();
		$this->_rebuildStreams();

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=discover&controller=streams", 'front', 'discover_all' ) );
	}
	
	/**
	 * Get the container Node form element HTML
	 *
	 * @return string
	 */
	protected function getContainerNodeElement()
	{
		$currentContainers = array();
		$stream = NULL;
		if ( isset( \IPS\Request::i()->id ) )
		{
			$stream = \IPS\core\Stream::load( \IPS\Request::i()->id );
			$currentContainers = $stream->containers ? json_decode( $stream->containers, TRUE ) : array();
			
			if ( isset( \IPS\Request::i()->stream_containers ) )
			{
				$currentContainers = json_decode( $stream::containersFromUrl( \IPS\Request::i()->stream_containers ), TRUE );
			}
		}
 
		foreach ( \IPS\Content::routedClasses( TRUE, FALSE, TRUE ) as $class )
		{
			$classes[ $class ] = $class::$title . '_pl';
			if ( isset( $class::$containerNodeClass ) and $class == \IPS\Request::i()->className )
			{
				$url = $stream ? $stream->baseUrl->setQueryString( 'className', $class ) : \IPS\Request::i()->url()->setQueryString( 'className', $class );
				$containerClass = $class::$containerNodeClass;
				$field = new \IPS\Helpers\Form\Node( 'stream_containers_' . $class::$title, isset( $currentContainers[ $class ] ) ? $currentContainers[ $class ] : array(), NULL, array( 'url' => $url, 'class' => $class::$containerNodeClass, 'multiple' => TRUE, 'permissionCheck' => $containerClass::searchableNodesPermission() ), NULL, NULL, NULL, 'stream_containers_' . $class::$title );
				$field->label = \IPS\Member::loggedIn()->language()->addToStack( 'stream_narrow_by_container_label', NULL, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $containerClass::$nodeTitle ) ) ) );
				
				return \IPS\Output::i()->json( array( 'node' => \IPS\Theme::i()->getTemplate('streams')->filterFormContentTypeContent( $field, $class, \IPS\Request::i()->key ) ) );
			}
		}

		return NULL;
	}
	
	/**
	 * Build form
	 *
	 * @param	\IPS\core\Stream	$stream	The stream
	 * @return	string
	 */
	protected function _buildForm( \IPS\core\Stream &$stream )
	{
		/* Build form */
		$form = new \IPS\Helpers\Form( 'stream', 'continue', ( $stream->id ? $stream->url() : NULL ) );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
				
		$stream->form( $form, 'Text', !$stream->id );
		$redirectAfterSave = FALSE;		
		
		/* Note if it's custom */
		if ( $stream->member && \IPS\Member::loggedIn()->member_id )
		{
			$form->hiddenValues['__custom_stream'] = TRUE;
		}
		
		if ( $stream->member )
		{
			$form->hiddenValues['__stream_owner'] = $stream->member;
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* As the node container form elements are not in the actual form, we need to work some magic. And by magic, I do mean a sort of hacky thing. */
			foreach ( \IPS\Request::i() as $k => $v )
			{
				if ( mb_substr( $k, 0, 18 ) == 'stream_containers_' and $v )
				{
					$vals = explode( ',', $v );
					$values[ $k ] = array_combine( $vals, $vals );
				}
			}
		
			/* Update only */
			if ( isset( \IPS\Request::i()->updateOnly ) )
			{
				$formattedValues = $stream->formatFormValues( $values );
					
				foreach ( array( 'include_comments', 'classes', 'containers', 'ownership', 'custom_members', 'read', 'follow', 'followed_types', 'date_type', 'date_start', 'date_end', 'date_relative_days', 'sort', 'tags', 'unread_links' ) as $k )
				{
					$requestKey = 'stream_' . $k;
					if ( isset( $formattedValues[ $k ] ) AND $stream->$k != $formattedValues[ $k ] )
					{
						$stream->$k = $formattedValues[ $k ];
						$stream->baseUrl = $stream->baseUrl->setQueryString( 'stream_' . $k, \IPS\Request::i()->$requestKey );
					}
				}
			}			
			/* Update & Save */
			else
			{
				if ( !$stream->id )
				{
					$stream->position = \IPS\Db::i()->select( 'MAX(position)', 'core_streams', array( 'member=?', \IPS\Member::loggedIn()->member_id )  )->first() + 1;
					$redirectAfterSave = TRUE;
				}
				else
				{
					if ( !$stream->member or $stream->member != \IPS\Member::loggedIn()->member_id )
					{
						\IPS\Output::i()->error( 'no_module_permission', '2C280/9', 403, '' );
					}
				}
			
				$stream->saveForm( $stream->formatFormValues( $values ) );
				
				$this->_rebuildStreams();
				
				if( $redirectAfterSave )
				{
					\IPS\Output::i()->redirect( $stream->url() );
				}
			}
		}
		
		/* Display */
		return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'streams', 'core' ) ), $stream->id ? 'filterInlineForm' : 'filterCreateForm' ) );
	}
	
	/**
	 * Rebuild logged in member's streams
	 *
	 * @return	void
	 */
	protected function _rebuildStreams()
	{
		$default = \IPS\Member::loggedIn()->defaultStream;
		\IPS\Member::loggedIn()->member_streams = json_encode( array( 'default' => $default, 'streams' => iterator_to_array( \IPS\Db::i()->select( 'id, title', 'core_streams', array( 'member=?', \IPS\Member::loggedIn()->member_id ) )->setKeyField('id')->setValueField('title') ) ) );
		\IPS\Member::loggedIn()->save();
	}
}