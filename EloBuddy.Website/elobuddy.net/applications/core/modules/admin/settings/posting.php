<?php
/**
 * @brief		Manage Posting Settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Manage Posting Settings
 */
class _posting extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* Build tab list */
		$this->tabs = array();
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage' ) )
		{
			$this->tabs['general']			= 'posting_general';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_acronym' ) )
		{
			$this->tabs['acronymExpansion'] = 'acronym_expansion';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_polls' ) )
		{
			$this->tabs['polls'] = 'polls';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_profanity' ) )
		{
			$this->tabs['profanityFilters'] = 'profanity';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_tags' ) )
		{
			$this->tabs['tags'] = 'tags';
		}
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'settings', 'posting_manage_url' ) )
		{
			$this->tabs['urlFilters']			= 'url_settings';
		}

		/* Choose active tab */
		if ( isset( \IPS\Request::i()->tab ) and array_key_exists( \IPS\Request::i()->tab, $this->tabs ) )
		{
			$this->activeTab = \IPS\Request::i()->tab;
		}
		else
		{
			$keys = array_keys( $this->tabs );
			$this->activeTab = array_shift( $keys );
		}
		
		/* Run */
		parent::execute();
	}

	/**
	 * Manage Posting Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Work out output */
		$activeTabContents = call_user_func( array( $this, '_manage'.ucfirst( $this->activeTab  ) ) );
		
		/* If this is an AJAX request, just return it */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_settings_posting');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->tabs( $this->tabs, $this->activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=settings&controller=posting" ) );
	}
		
	/**
	 * Manage general posting settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageGeneral()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage' );
		
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->addHeader('posting_attachments');
		$form->add( new \IPS\Helpers\Form\Radio( 'attach_allowed_types', \IPS\Settings::i()->attach_allowed_types, FALSE, array(
			'options' 	=> array( 'all' => 'attach_allowed_types_all', 'images' => 'attach_allowed_types_images', 'none' => 'attach_allowed_types_none' ),
			'toggles'	=> array( 'all' => array( 'attach_allowed_extensions', 'attachment_resample_size', 'attachment_image_size' ), 'images' => array( 'attachment_resample_size', 'attachment_image_size' ) )
		) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'attach_allowed_extensions', \IPS\Settings::i()->attach_allowed_extensions ? explode( ',', \IPS\Settings::i()->attach_allowed_extensions ) : NULL, FALSE, array( 'nullLang' => 'no_restriction', 'autocomplete' => array( 'freeChoice' => TRUE, 'source' => array( 'doc', 'docx', 'log', 'msg', 'odt', 'pages', 'rtf', 'tex', 'txt', 'wpd', 'wps', 'csv', 'dat', 'gbr', 'ged', 'key', 'keychain', 'pps', 'ppt', 'pptx', 'sdf', 'tar', 'tax2012', 'tax2014', 'vcf', 'xml', 'aif', 'iff', 'm3u', 'm4a', 'mid', 'mp3', 'mpa', 'ra', 'wav', 'wma', '3g2', '3gp', 'asf', 'asx', 'avi', 'flv', 'm4v', 'mov', 'mp4', 'mpg', 'rm', 'srt', 'swf', 'vob', 'wmv', '3dm', '3ds', 'max', 'obj', 'bmp', 'dds', 'gif', 'jpg', 'png', 'psd', 'pspimage', 'tga', 'thm', 'tif', 'tiff', 'yuv', 'ai', 'eps', 'ps', 'svg', 'indd', 'pct', 'pdf', 'xlr', 'xls', 'xlsx', 'accdb', 'db', 'dbf', 'mdb', 'pdb', 'sql', 'apk', 'app', 'bat', 'cgi', 'com', 'exe', 'gadget', 'jar', 'pif', 'vb', 'wsf', 'dem', 'gam', 'nes', 'rom', 'sav', 'dwg', 'dxf', 'gpx', 'kml', 'kmz', 'asp', 'aspx', 'cer', 'cfm', 'csr', 'css', 'htm', 'html', 'js', 'jsp', 'php', 'rss', 'xhtml', 'crx', 'plugin', 'fnt', 'fon', 'otf', 'ttf', 'cab', 'cpl', 'cur', 'deskthemepack', 'dll', 'dmp', 'drv', 'icns', 'ico', 'lnk', 'sys', 'cfg', 'ini', 'prf', 'hqx', 'mim', 'uue', '7z', 'cbr', 'deb', 'gz', 'pkg', 'rar', 'rpm', 'sitx', 'tar.gz', 'zip', 'zipx', 'bin', 'cue', 'dmg', 'iso', 'mdf', 'toast', 'vcd', 'c', 'class', 'cpp', 'cs', 'dtd', 'fla', 'h', 'java', 'lua', 'm', 'pl', 'py', 'sh', 'sln', 'swift', 'vcxproj', 'xcodeproj', 'bak', 'tmp', 'crdownload', 'ics', 'msi', 'part', 'torrent' ) ) ), NULL, NULL, NULL, 'attach_allowed_extensions' ) );
		$form->addHeader('posting_images');
        $form->add( new \IPS\Helpers\Form\WidthHeight( 'attachment_resample_size', \IPS\Settings::i()->attachment_resample_size ? explode( 'x', \IPS\Settings::i()->attachment_resample_size ) : array( 0, 0 ), FALSE, array( 'resizableDiv' => FALSE, 'unlimited' => array( 0, 0 ) ), NULL, NULL, NULL, 'attachment_resample_size' ) );
		$current = ( isset( \IPS\Settings::i()->attachment_image_size ) ) ? explode( 'x', \IPS\Settings::i()->attachment_image_size ) : array( 1000, 750 );
		$form->add( new \IPS\Helpers\Form\WidthHeight( 'attachment_image_size', $current, FALSE, array( 'resizableDiv' => FALSE ), NULL, NULL, NULL, 'attachment_image_size' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'editor_embeds', \IPS\Settings::i()->editor_embeds, FALSE, array( 'togglesOn' => array( 'max_video_width' ) ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'max_video_width', \IPS\Settings::i()->max_video_width, FALSE, array( 'unlimited' => 0 ), NULL, NULL, 'px', 'max_video_width' ) );
		$form->addHeader('remote_images');
		$form->add( new \IPS\Helpers\Form\YesNo( 'allow_remote_images', \IPS\Settings::i()->allow_remote_images, FALSE, array( 'togglesOn' => array( 'remote_image_proxy' ) ) ) );
        $form->add( new \IPS\Helpers\Form\YesNo( 'remote_image_proxy', \IPS\Settings::i()->remote_image_proxy, FALSE, array( 'togglesOn' => array( 'remote_image_proxy_cache' ) ), NULL, NULL, NULL, 'remote_image_proxy' ) );
        $form->add( new \IPS\Helpers\Form\Number( 'image_proxy_cache_period', \IPS\Settings::i()->image_proxy_cache_period, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'indefinitely' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('for'), \IPS\Member::loggedIn()->language()->addToStack('days'), 'remote_image_proxy_cache' ) );
		$form->addHeader('posting_content');
		$form->add( new \IPS\Helpers\Form\Number( 'max_title_length', \IPS\Settings::i()->max_title_length, FALSE, array( 'max' => 255 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('max_title_length_suffix') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'merge_concurrent_posts', \IPS\Settings::i()->merge_concurrent_posts, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('minutes') ) );
		$form->add( new \IPS\Helpers\Form\Number( 'flood_control', \IPS\Settings::i()->flood_control, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'none' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('seconds') ) );
		$form->addHeader('edit_log');
		$form->add( new \IPS\Helpers\Form\Radio( 'edit_log', \IPS\Settings::i()->edit_log, FALSE, array(
			'options' => array(
				0	=> 'edit_log_none',
				1	=> 'edit_log_simple',
				2	=> 'edit_log_full'
			),
			'toggles'	=> array(
				2	=> array( 'edit_log_public', 'edit_log_prune' )
			)
		) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'edit_log_public', \IPS\Settings::i()->edit_log_public, FALSE, array(), NULL, NULL, NULL, 'edit_log_public' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'edit_log_prune', \IPS\Settings::i()->edit_log_prune, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'never' ), function( $value ){ if( $value < 1 AND $value != -1 ) { throw new \InvalidArgumentException('form_required'); } }, NULL, \IPS\Member::loggedIn()->language()->addToStack('days'), 'edit_log_prune' ) );
		
		$form->addHeader('posting_items');
		$form->add( new \IPS\Helpers\Form\Number( 'topic_redirect_prune', \IPS\Settings::i()->topic_redirect_prune, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days') ) );
		
		$hasReviewableApps = false;
		
		foreach ( \IPS\Application::allExtensions( 'core', 'ContentRouter', NULL, NULL, NULL, TRUE ) as $router )
		{
			foreach ( $router->classes as $class )
			{
				$classes[]	= $class;

				if ( isset( $class::$commentClass ) )
				{
					$hasReviewableApps = true;
					break 2;
				}
			}
		}
		
		if ( $hasReviewableApps )
		{
			$form->add( new \IPS\Helpers\Form\Radio( 'reviews_rating_out_of', \IPS\Settings::i()->reviews_rating_out_of, FALSE, array(
				'options' => array(
					5	=> 'reviews_rating_out_of_5',
					10	=> 'reviews_rating_out_of_10'
				)
			) ) );
		}
		
		/* Save values */
		if ( $values = $form->values() )
		{
			\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => (int) $values['allow_remote_images'] ), array( '`key`=?', 'imageproxy' ) );
			
			$clearCss = ( $values['max_video_width'] !== \IPS\Settings::i()->max_video_width );
			
			$values['attachment_resample_size'] = implode( 'x', $values['attachment_resample_size'] );	
			$values['attachment_image_size'] = implode( 'x', $values['attachment_image_size'] );	
			$values['attach_allowed_extensions'] = is_array( $values['attach_allowed_extensions'] ) ? implode( ',', array_unique( array_map( function( $val )
			{
				return ltrim( $val, '.' );
			}, $values['attach_allowed_extensions'] ) ) ) : '';		
			$values['max_video_width_css'] = ( $values['max_video_width'] ) ? $values['max_video_width'] . 'px' : 'none';
			
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__posting_general_settings' );
			
			if ( $clearCss )
			{
				\IPS\Theme::deleteCompiledCss();

				/* redirect so CSS can rebuild */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=general' ), 'saved' );
			}
		}
	
		return $form;
	}
	
	/**
	 * Show profanity filters
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageProfanityFilters()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );
		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_profanity_filters', \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=profanityFilters' ) );
		$table->langPrefix = 'profanity_';
		$table->mainColumn = 'type';
		
		/* Columns we need */
		$table->include = array( 'type', 'swop', 'm_exact' );

		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'type';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Filters */
		$table->filters = array(
			'profanity_filter_exact'		=> 'm_exact=1',
			'profanity_filter_loose'		=> 'm_exact=0',
		);
		
		/* Search */
		$table->quickSearch = 'type';
		
		/* Custom parsers */
		$table->parsers = array(
			'm_exact'				=> function( $val, $row )
			{
				return ( $val ) ? \IPS\Member::loggedIn()->language()->addToStack('profanity_filter_exact') : \IPS\Member::loggedIn()->language()->addToStack('profanity_filter_loose');
			}
		);
		
		/* Specify the root buttons */

		$table->rootButtons['add'] = array(
			'icon'		=> 'plus',
			'title'		=> 'profanity_add',
			'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=profanity' ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('profanity_add') )
		);

		$table->rootButtons['download'] = array(
			'icon'		=> 'download',
			'title'		=> 'download',
			'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=downloadProfanity' ),
			'data'		=> array( 'confirm' => '', 'confirmMessage' => \IPS\Member::loggedIn()->language()->addToStack('profanity_download'), 'confirmIcon' => 'info', 'confirmButtons' => json_encode( array( 'ok' => \IPS\Member::loggedIn()->language()->addToStack('download'), 'cancel' => \IPS\Member::loggedIn()->language()->addToStack('cancel') ) ) )
		);

		/* And the row buttons */
		$table->rowButtons = function( $row )
		{
			$return = array();

			$return['edit'] = array(
				'icon'		=> 'pencil',
				'title'		=> 'edit',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=profanity&id=' ) . $row['wid'],
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit') )
			);

			$return['delete'] = array(
				'icon'		=> 'times',
				'title'		=> 'delete',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=deleteProfanityFilters&id=' ) . $row['wid'],
				'data'		=> array( 'delete' => '' ),
			);
				
			return $return;
		};
				
		/* So people don't set up filters, test with their admin account and think it's not working */
		$groupsThatBypass = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'core_groups', array( 'g_bypass_badwords=1' ) ), 'IPS\Member\Group' );
		if ( count( $groupsThatBypass ) )
		{
			$names = array();
			foreach ( $groupsThatBypass as $group )
			{
				$names[] = $group->name;
			}
			$message = \IPS\Theme::i()->getTemplate( 'forms' )->blurb( \IPS\Member::loggedIn()->language()->addToStack( 'profanity_bypass_groups', FALSE, array( 'htmlsprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $names ) ) ) ), FALSE, TRUE );
		}
		else
		{
			$message = \IPS\Theme::i()->getTemplate( 'forms' )->blurb( 'profanity_no_bypass_groups', TRUE, TRUE );
		}
		
		/* Display */
		return $message . $table;
	}

	/**
	 * Show url filters
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageUrlFilters()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_url' );
		
		/* Build Form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Radio( 'ipb_url_filter_option', \IPS\Settings::i()->ipb_url_filter_option, FALSE, array(
			'options' => array(
				'none' => 'url_none',
				'black' => 'url_blacklist',
				'white' => "url_whitelist" ),
			'toggles' => array(
				'black'	=> array( 'ipb_url_blacklist' ),
				'white'	=> array( 'ipb_url_whitelist' ),
				'none'		=> array(),
			)
		) ) );
		
		$form->add( new \IPS\Helpers\Form\Stack( 'ipb_url_whitelist', \IPS\Settings::i()->ipb_url_whitelist ? explode( ",", \IPS\Settings::i()->ipb_url_whitelist ) : array(), FALSE, array(), NULL, NULL, NULL, 'ipb_url_whitelist' ) );
 		$form->add( new \IPS\Helpers\Form\Stack( 'ipb_url_blacklist', \IPS\Settings::i()->ipb_url_blacklist ? explode( ",", \IPS\Settings::i()->ipb_url_blacklist ) : array(), FALSE, array(), NULL, NULL, NULL, 'ipb_url_blacklist' ) );
		
		$form->add( new \IPS\Helpers\Form\YesNo( 'links_external', \IPS\Settings::i()->links_external ) );
 		$form->add( new \IPS\Helpers\Form\YesNo( 'posts_add_nofollow', \IPS\Settings::i()->posts_add_nofollow, FALSE, array( 'togglesOn' => array( 'posts_add_nofollow_exclude' ) ), NULL, NULL, NULL, 'posts_add_nofollow' ) );
 		$form->add( new \IPS\Helpers\Form\Stack( 'posts_add_nofollow_exclude', \IPS\Settings::i()->posts_add_nofollow_exclude ? json_decode( \IPS\Settings::i()->posts_add_nofollow_exclude ) : array(), FALSE, array( 'placeholder' => 'example.com' ), NULL, NULL, NULL, 'posts_add_nofollow_exclude' ) );
 		
		/* Save values */
		if ( $values = $form->values() )
		{
            $values['ipb_url_whitelist'] = implode( ",", $values['ipb_url_whitelist'] );
			$values['ipb_url_blacklist'] = implode( ",", $values['ipb_url_blacklist'] );
			$values['posts_add_nofollow_exclude'] = json_encode( $values['posts_add_nofollow_exclude'] );
			$form->saveAsSettings( $values );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__url_filter_settings' );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=urlFilters' ), 'saved' );
		}

		return $form;
	}
	
	/**
	 * Acronym expansion
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageAcronymExpansion()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_acronym' );
		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_acronyms', \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=acronymExpansion' ) );
		$table->langPrefix = 'acronym_';
		$table->mainColumn = 'a_short';
	
		/* Columns we need */
		$table->include = array( 'a_short', 'a_long', 'a_casesensitive' );
	
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'a_short';
		$table->sortDirection = $table->sortDirection ?: 'asc';
	
		/* Search */
		$table->quickSearch = 'a_short';
	
		/* Custom parsers */
		$table->parsers = array(
			'a_casesensitive'=> function( $val, $row )
			{
				return ( $val ) ? "<i class='fa fa-check'></i>" : "<i class='fa fa-times'></i>";
			},
		);
	
		/* Specify the buttons */
		$table->rootButtons = array(
				'add'	=> array(
						'icon'		=> 'plus',
						'title'		=> 'acronym_add',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=acronym' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('acronym_add') )
				)
		);
	
		$table->rowButtons = function( $row )
		{
			$return = array();
	
			$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=acronym&id=' ) . $row['a_id'],
			);
	
			$return['delete'] = array(
					'icon'		=> 'times',
					'title'		=> 'delete',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=deleteAcronym&id=' ) . $row['a_id'],
					'data'		=> array( 'delete' => '' ),
			);
	
			return $return;
		};
	
		return $table;
	}
	
	/**
	 * Manage poll settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _managePolls()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_polls' );
		
		$form = new \IPS\Helpers\Form();
		$form->addHeader('poll_creation');
		$form->add( new \IPS\Helpers\Form\Number( 'max_poll_questions', \IPS\Settings::i()->max_poll_questions ) );
		$form->add( new \IPS\Helpers\Form\Number( 'max_poll_choices', \IPS\Settings::i()->max_poll_choices ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'poll_allow_public', \IPS\Settings::i()->poll_allow_public ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ipb_poll_only', \IPS\Settings::i()->ipb_poll_only ) );
		$form->add( new \IPS\Helpers\Form\Number( 'startpoll_cutoff', \IPS\Settings::i()->startpoll_cutoff, FALSE, array( 'unlimited' => -1, 'unlimitedLang' => 'always' ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('hours') ) );
		$form->addHeader('poll_voting');
		$form->add( new \IPS\Helpers\Form\YesNo( 'allow_creator_vote', \IPS\Settings::i()->allow_creator_vote ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'poll_allow_vdelete', \IPS\Settings::i()->poll_allow_vdelete, FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'allow_result_view', \IPS\Settings::i()->allow_result_view, FALSE, array(), NULL, NULL, NULL, 'allow_result_view' ) );
		
		if ( $form->saveAsSettings() )
		{
			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__poll_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=polls' ), 'saved' );
		}
		
		return \IPS\Theme::i()->getTemplate( 'forms' )->blurb( 'polls_blurb' ) . $form;
	}
	
	/**
	 * Manage tag settings
	 *
	 * @return	string	HTML to display
	 */
	protected function _manageTags()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_tags' );
		
		$form = new \IPS\Helpers\Form();
				
		$form->add( new \IPS\Helpers\Form\YesNo( 'tags_enabled', \IPS\Settings::i()->tags_enabled, FALSE, array( 'togglesOn' => array( 'tags_can_prefix', 'tags_open_system', 'tags_predefined', 'tags_force_lower', 'tags_min', 'tags_max', 'tags_len_min', 'tags_len_max', 'tags_clean' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'tags_can_prefix', \IPS\Settings::i()->tags_can_prefix, FALSE, array(), NULL, NULL, NULL, 'tags_can_prefix' ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'tags_open_system', \IPS\Settings::i()->tags_open_system, FALSE, array( 'options' => array( 1 => 'tags_open_system_open', 0 => 'tags_open_system_closed' ) ), NULL, NULL, NULL, 'tags_open_system' ) );
		$form->add( new \IPS\Helpers\Form\Text( 'tags_predefined', \IPS\Settings::i()->tags_predefined, FALSE, array( 'autocomplete' => array( 'unique' => TRUE ) ), NULL, NULL, NULL, 'tags_predefined' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'tags_force_lower', \IPS\Settings::i()->tags_force_lower, FALSE, array(), NULL, NULL, NULL, 'tags_force_lower' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'tags_min', \IPS\Settings::i()->tags_min, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'tags_min_none', 'unlimitedToggles' => array( 'tags_min_req' ), 'unlimitedToggleOn' => FALSE ), NULL, NULL, NULL, 'tags_min' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'tags_min_req', \IPS\Settings::i()->tags_min_req, FALSE, array(), NULL, NULL, NULL, 'tags_min_req' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'tags_max', \IPS\Settings::i()->tags_max, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'tags_max' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'tags_len_min', \IPS\Settings::i()->tags_len_min, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack( 'characters' ), 'tags_len_min' ) );
		$form->add( new \IPS\Helpers\Form\Number( 'tags_len_max', \IPS\Settings::i()->tags_len_max, FALSE, array( 'unlimited' => 0 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack( 'characters' ), 'tags_len_max' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'tags_clean', \IPS\Settings::i()->tags_clean, FALSE, array(), NULL, NULL, NULL, 'tags_clean' ) );
		
		if ( ! \IPS\Settings::i()->tags_open_system and ! \IPS\Settings::i()->tags_predefined )
		{
			\IPS\Member::loggedIn()->language()->words['tags_predefined_warning'] = \IPS\Member::loggedIn()->language()->words['tags_predefined__warning'];
		}
			
		if ( $form->saveAsSettings() )
		{
			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__tag_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=tags' ), 'saved' );
		}
		
		return $form;
	}
	
	/**
	 * Add/Edit Profanity Filter
	 *
	 * @return	void
	 */
	public function profanity()
	{
		/* Permission check */
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );
		
		/* Init */
		$current = NULL;
		if ( \IPS\Request::i()->id )
		{
			try
			{
				$current = \IPS\Db::i()->select( '*', 'core_profanity_filters', array( 'wid=?', \IPS\Request::i()->id ) )->first();
			}
			catch ( \UnderflowException $e ) { }
		}
	
		/* Build form */
		$form = new \IPS\Helpers\Form();
		if ( !$current )
		{
			$form->addTab('add');
		}
		$form->add( new \IPS\Helpers\Form\Text( 'profanity_type', ( $current ) ? $current['type'] : NULL, NULL, array() ) );
		$form->add( new \IPS\Helpers\Form\Text( 'profanity_swop', ( $current ) ? $current['swop'] : NULL, NULL, array() ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'profanity_m_exact', ( $current ) ? $current['m_exact'] : NULL, FALSE, array(
			'options' => array(
				'1' => 'profanity_filter_exact',
				'0'	=> 'profanity_filter_loose' ) )
		) );
		if ( !$current )
		{
			$form->addTab('upload');
			$form->add( new \IPS\Helpers\Form\Upload( 'profanity_upload', NULL, NULL, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );
		}
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Upload */
			if ( isset( $values['profanity_upload'] ) and $values['profanity_upload'] )
			{
				/* Move it to a temporary location */
				$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
				move_uploaded_file( $values['profanity_upload'], $tempFile );
									
				/* Initate a redirector */
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=importProfanity&file=' . urlencode( $tempFile ) . '&key=' . md5_file( $tempFile ) ) );
			}
			
			/* Normal */
			else
			{
				if ( $values['profanity_type'] and $values['profanity_swop'] )
				{
					$save = array(
						'type'		=> $values['profanity_type'],
						'swop'		=> $values['profanity_swop'],
						'm_exact'	=> $values['profanity_m_exact']
					);
					
					if( $current )
					{
						\IPS\Db::i()->update( 'core_profanity_filters', $save, array( 'wid=?', $current['wid'] ) );
					}
					else
					{
						\IPS\Db::i()->insert( 'core_profanity_filters', $save );
					}

					/* Clear guest page caches */
					\IPS\Data\Cache::i()->clearAll();

					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=profanityFilters' ), 'saved' );
				}
				else
				{
					$form->error = \IPS\Member::loggedIn()->language()->addToStack('profanity_add_error');
				}
			}
	
		}
	
		/* Display */
		\IPS\Output::i()->title	 		= \IPS\Member::loggedIn()->language()->addToStack('profanity');
		\IPS\Output::i()->breadcrumb[]	= array( NULL, \IPS\Output::i()->title );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( \IPS\Output::i()->title, $form, FALSE );
	}
	
	/**
	 * Download Profanity Filters
	 *
	 * @return	void
	 */
	public function downloadProfanity()
	{
		/* Permission Check */
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );
		
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );
		$xml->startElement('badwordexport');
		$xml->startElement('badwordgroup');
		foreach ( \IPS\Db::i()->select( '*', 'core_profanity_filters' ) as $profanity )
		{
			$xml->startElement('badword');
			
			$xml->startElement('type');
			$xml->text( $profanity['type'] );
			$xml->endElement();
			
			$xml->startElement('swop');
			$xml->text( $profanity['swop'] );
			$xml->endElement();
			
			$xml->startElement('m_exact');
			$xml->text( $profanity['m_exact'] );
			$xml->endElement();
			
			$xml->endElement();
		}
		$xml->endElement();
		$xml->endElement();
		$xml->endDocument();
		
		\IPS\Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => \IPS\Output::getContentDisposition( 'attachment', sprintf( \IPS\Member::loggedIn()->language()->get('profanity_download_name'),  \IPS\Settings::i()->board_name ) . '.xml' ) ) );
	}
	
	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	protected function importProfanity()
	{
		if ( !file_exists( \IPS\Request::i()->file ) or md5_file( \IPS\Request::i()->file ) !== \IPS\Request::i()->key )
		{
			\IPS\Output::i()->error( 'generic_error', '3C256/1', 500, '' );
		}
		
		$url = \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&do=importProfanity&file=' . urlencode( \IPS\Request::i()->file ) . '&key=' .  \IPS\Request::i()->key );
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			$url,
			function( $data )
			{
				/* Open XML file */
				$xml = new \XMLReader;
				$xml->open( \IPS\Request::i()->file );
				$xml->read(); //badwordexport
				$xml->read(); //badwordexport
				$xml->read(); //badwordgroup
				$xml->read(); //badwordgroup
				$xml->read();
				
				/* Skip */
				for ( $i = 0; $i < $data; $i++ )
				{
					$xml->next();
					if ( !$xml->read() or $xml->name != 'badword' )
					{
						return NULL;
					}
				}
								
				/* Import */
				$save = array();
				$xml->read();
				$xml->read();
				$save['type'] = $xml->readString();
				$xml->next();
				$xml->read();
				$save['swop'] = $xml->readString();
				$xml->next();
				$xml->read();
				$save['m_exact'] = $xml->readString();
				try
				{
					$current = \IPS\Db::i()->select( 'wid', 'core_profanity_filters', array( 'type=?', $save['type'] ) )->first();
					\IPS\Db::i()->update( 'core_profanity_filters', $save, array( 'wid=?', $current ) );
				}
				catch ( \UnderflowException $e )
				{
					\IPS\Db::i()->insert( 'core_profanity_filters', $save );
				}
							
				/* Move to next */
				return array( ++$data, \IPS\Member::loggedIn()->language()->get('processing') );
			},
			function()
			{
				unset( \IPS\Data\Store::i()->languages );

				/* Clear guest page caches */
				\IPS\Data\Cache::i()->clearAll();

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=profanityFilters' ) );
			}
		);
	}
	
	/**
	 * Add/Edit Acronym
	 *
	 * @return	void
	 */
	public function acronym()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_acronym' );
		
		$current = NULL;
	
		if ( \IPS\Request::i()->id )
		{
			$current = \IPS\core\Acronym::load( \IPS\Request::i()->id );
		}
	
		/* Build form */
		$form = \IPS\core\Acronym::form( $current );
	
		if ( $values = $form->values() )
		{
			\IPS\core\Acronym::createFromForm( $values, $current );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=settings&controller=posting&tab=acronymExpansion' ), 'saved' );
		}
	
		/* Display */
		\IPS\Output::i()->title	 		= \IPS\Member::loggedIn()->language()->addToStack('acronym_a_short');
		\IPS\Output::i()->breadcrumb[]	= array( NULL, \IPS\Output::i()->title );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( \IPS\Output::i()->title, $form, FALSE );
	}
	
	/**
	 * Delete Profanity Filter
	 *
	 * @return	void
	 */
	public function deleteProfanityFilters()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_profanity' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		\IPS\Db::i()->delete( 'core_profanity_filters', array( 'wid=?', \IPS\Request::i()->id ) );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		/* And redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=settings&controller=posting&tab=profanityFilters" ) );
	}
	
	/**
	 * Delete Acronym
	 *
	 * @return	void
	 */
	public function deleteAcronym()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'posting_manage_acronym' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		\IPS\core\Acronym::load( \IPS\Request::i()->id )->delete();

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		/* And redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=settings&controller=posting&tab=acronymExpansion" ) );
	}

	/**
	 * Rebuild attachment thumbnails
	 *
	 * @return	void
	 */
	public function rebuildThumbnails()
	{
		\IPS\Task::queue( 'core', 'RebuildAttachmentThumbnails', array(), 4 );

		/* And redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=settings&controller=posting" ), 'thumbnails_rebuilt' );
	}
}