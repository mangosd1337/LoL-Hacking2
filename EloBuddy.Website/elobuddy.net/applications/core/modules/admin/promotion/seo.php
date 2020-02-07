<?php
/**
 * @brief		SEO
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		07 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\promotion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * SEO
 */
class _seo extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Active tab
	 */
	protected $activeTab	= '';

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'seo_manage' );

		/* Get tab content */
		$this->activeTab = \IPS\Request::i()->tab ?: 'urls';

		parent::execute();
	}

	/**
	 * SEO Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Work out output */
		$activeTabContents = call_user_func( array( $this, '_manage' . ucfirst( $this->activeTab ) ) );
		
		/* If this is an AJAX request, just return it */
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $activeTabContents;
			return;
		}
		
		/* Build tab list */
		$tabs = array();
		$tabs['urls']		= 'seo_tab_furls';
		$tabs['sitemap']	= 'seo_tab_sitemap';
		$tabs['metatags']	= 'seo_tab_metatags';
			
		/* Display */
		if ( $activeTabContents )
		{
			\IPS\Output::i()->sidebar['actions']['ga'] = array(
					'title'		=> 'configure_google_analytics',
					'icon'		=> 'cogs',
					'link'		=> \IPS\Http\Url::internal( "app=core&module=applications&controller=enhancements&do=edit&id=core_GoogleAnalytics" ),
			);

			
			\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_promotion_seo');
			\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate( 'global' )->tabs( $tabs, $this->activeTab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo" ) );
		}
	}

	/**
	 * Manage FURL definitions
	 *
	 * @return	string
	 */
	protected function _manageUrls()
	{
		$form = new \IPS\Helpers\Form;

		$form->add( new \IPS\Helpers\Form\YesNo( 'use_friendly_urls', \IPS\Settings::i()->use_friendly_urls, TRUE, array( 'togglesOn' => array( 'htaccess_mod_rewrite', 'seo_r_on' ), 'togglesOff' => array( 'use_friendly_urls_warning' ) ), NULL, NULL, NULL, 'use_friendly_urls' ) );
		
		$isApache = mb_stripos( $_SERVER['SERVER_SOFTWARE'], 'apache' ) !== FALSE;
		$form->add( new \IPS\Helpers\Form\YesNo( 'htaccess_mod_rewrite', \IPS\Settings::i()->htaccess_mod_rewrite, TRUE, array(), NULL, NULL, NULL, 'htaccess_mod_rewrite' ) );
		if ( !$isApache )
		{
			\IPS\Member::loggedIn()->language()->words['htaccess_mod_rewrite_desc']		= \IPS\Member::loggedIn()->language()->get('htaccess_mod_rewrite_desc_na');
		}
		if ( ( !isset( \IPS\Request::i()->htaccess_mod_rewrite ) and \IPS\Settings::i()->htaccess_mod_rewrite ) or \IPS\Request::i()->htaccess_mod_rewrite or \IPS\Request::i()->htaccess_mod_rewrite_checkbox )
		{
			try
			{
				$response = \IPS\Http\Url::external( \IPS\Settings::i()->base_url . 'login' )->request( NULL, NULL, FALSE )->get();
				if ( !in_array( mb_substr( $response->httpResponseCode, 0, 1 ), array( '2', '3' ) ) and ( \IPS\Settings::i()->site_online OR $response->httpResponseCode != 503 ) )
				{
					\IPS\Member::loggedIn()->language()->words['htaccess_mod_rewrite_warning']	= \IPS\Member::loggedIn()->language()->get( $isApache ? 'htaccess_mod_rewrite_err' : 'htaccess_mod_rewrite_err_na' );
				}
			}
			catch( \IPS\Http\Request\Exception $e )
			{
				\IPS\Member::loggedIn()->language()->words['htaccess_mod_rewrite_warning']	= \IPS\Member::loggedIn()->language()->get( $isApache ? 'htaccess_mod_rewrite_err' : 'htaccess_mod_rewrite_err_na' );
			}
		}

		$form->add( new \IPS\Helpers\Form\YesNo( 'seo_r_on', \IPS\Settings::i()->seo_r_on, TRUE, array( 'togglesOff' => array( 'seo_r_on_warning' ) ), NULL, NULL, NULL, 'seo_r_on' ) );
		
		/* Are we saving? */
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			\IPS\Member::clearCreateMenu();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplogs__seo_furl_settings' );
			
			/* Clear Sidebar Caches */
			\IPS\Widget::deleteCaches();
		}

		return $form;
	}

	/**
	 * Sitemap settings
	 *
	 * @return	string
	 */
	protected function _manageSitemap()
	{
		/* Init */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Url( 'sitemap_url', \IPS\Settings::i()->sitemap_url ?: \IPS\Settings::i()->base_url . 'sitemap.php', FALSE ) );

		/* Get extension settings */
		$useRecommendedSettings = TRUE;
		$extraSettings = array();
		$toggles = array();
		$recommendedSettings = array();
		foreach ( \IPS\Application::allExtensions( 'core', 'Sitemap', FALSE, 'core' ) as $extKey => $extension )
		{
			$toggles[] = "form_header_sitemap_{$extKey}";
			$recommendedSettings = array_merge( $recommendedSettings, $extension->recommendedSettings );
			foreach ( $extension->settings() as $k => $setting )
			{
				if ( $setting->value != $extension->recommendedSettings[ $k ] )
				{
					$useRecommendedSettings = FALSE;
				}
				
				$extraSettings[ $extKey ][] = $setting;
				$toggles[] = $setting->htmlId;
			}
		}
				
		/* Build form */
		$form->add( new \IPS\Helpers\Form\YesNo( 'sitemap_configuration_info', $useRecommendedSettings, FALSE, array( 'togglesOff' => $toggles ) ) );
		foreach ( $extraSettings as $header => $settings )
		{
			$form->addHeader( 'sitemap_' . $header );
			foreach ( $settings as $setting )
			{
				$form->add( $setting );
			}
		}

		/* Are we saving? */
		if ( $values = $form->values() )
		{
			if ( $values['sitemap_configuration_info'] )
			{
				$values = array_merge( $values, $recommendedSettings );
			}

			try
			{
				if( !( $values['sitemap_url'] instanceof \IPS\Http\Url ) )
				{
					throw new \RuntimeException;
				}

				$response = $values['sitemap_url']->setQueryString( 'testsettings', 1 )->request()->get();

				if ( $response->httpResponseCode != 200 or !mb_strpos( $values['sitemap_url'], "sitemap.php" ) )
				{
					$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'invalid_sitemap_url' );
				}

			}
			catch ( \RuntimeException $e )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'form_url_bad' );
			}

			if( !$form->error )
			{
				$form->saveAsSettings( $values );

				foreach( \IPS\Application::allExtensions( 'core', 'Sitemap', FALSE, 'core' ) as $extKey => $extension )
				{
					if( method_exists( $extension, 'saveSettings' ) )
					{
						$extension->saveSettings( $values );
					}
				}

				/* Clear guest page caches */
				\IPS\Data\Cache::i()->clearAll();

				\IPS\Session::i()->log( 'acplogs__seo_sitemap_settings' );
			}
		}

		return \IPS\Theme::i()->getTemplate( 'forms' )->blurb( 'sitemap_blurb' ) . $form;
	}

	/**
	 * Get the meta tag tree
	 *
	 * @return	string
	 */
	protected function _manageMetatags()
	{
		/* Are we deleting? */
		if ( isset( \IPS\Request::i()->delete ) )
		{
			/* Make sure the user confirmed the deletion */
			\IPS\Request::i()->confirmedDelete();

			if( isset( \IPS\Request::i()->root ) )
			{
				$meta	= \IPS\Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', (int) \IPS\Request::i()->root ) )->first();
				$tags	= array_diff_key( json_decode( $meta['meta_tags'], TRUE ), array( \IPS\Request::i()->delete => 1 ) );

				\IPS\Db::i()->update( 'core_seo_meta', array( 'meta_tags' => json_encode( $tags ) ), array( 'meta_id=?', (int) \IPS\Request::i()->root ) );
			}
			else
			{
				\IPS\Db::i()->delete( 'core_seo_meta', array( "meta_id=?", (int) \IPS\Request::i()->delete ) );
			}

			unset( \IPS\Data\Store::i()->metaTags );

			if ( \IPS\Request::i()->isAjax() )
			{
				return;
			}
		}

		/* Show tree */
		$url	= \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&tab=metatags" );
		$output	= new \IPS\Helpers\Tree\Tree(
			$url,
			\IPS\Member::loggedIn()->language()->addToStack('seo_tab_metatags'),
			/* Get Roots */
			function() use ( $url )
			{
				$rows = array();

				foreach ( \IPS\Db::i()->select( '*', 'core_seo_meta' ) as $row )
				{
					$rows[ $row['meta_url'] ] = \IPS\Theme::i()->getTemplate( 'trees' )->row( $url, $row['meta_id'], $row['meta_url'], TRUE, array(
						'edit'	=> array(
							'icon'		=> 'pencil',
							'title'		=> 'seo_meta_manage',
							'link'		=> \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&do=addMeta&id=" . $row['meta_id'] ),
							'hotkey'	=> 'e'
						),
						'delete'	=> array(
							'icon'		=> 'times-circle',
							'title'		=> 'delete',
							'link'		=> \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&tab=metatags&delete=" . $row['meta_id'] ),
							'data'		=> array( 'delete' => '' )
						)
					), "", NULL, NULL );
				}

				return $rows;
			},
			/* Get Row */
			function( $key, $root=FALSE ) use ( $url )
			{
				$meta	= \IPS\Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', $key ) )->first();

				return \IPS\Theme::i()->getTemplate( 'trees' )->row( $url, $key, $meta['meta_url'], TRUE, array(
					'edit'	=> array(
						'icon'		=> 'pencil',
						'title'		=> 'seo_meta_manage',
						'link'		=> \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&do=addMeta&id=" . $key ),
						'hotkey'	=> 'e'
					),
					'delete'	=> array(
						'icon'		=> 'times-circle',
						'title'		=> 'delete',
						'link'		=> \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&tab=metatags&delete=" . $key ),
						'data'		=> array( 'delete' => '' )
					)
				), '', NULL, NULL, $root );
			},
			/* Get Row's Parent ID */
			function( $id )
			{
				return NULL;
			},
			/* Get Children */
			function( $key ) use ( $url )
			{
				$meta	= \IPS\Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', $key ) )->first();
				$tags	= json_decode( $meta['meta_tags'], TRUE );
				$rows	= array();

				if( is_array( $tags ) )
				{
					foreach ( $tags as $name => $content )
					{
						$rows[] = \IPS\Theme::i()->getTemplate( 'trees' )->row( $url, $meta['meta_id'] . '-' . $name, $name, FALSE, array(
							'delete'	=> array(
								'icon'		=> 'times-circle',
								'title'		=> 'delete',
								'link'		=> \IPS\Http\Url::internal( $url . "&root={$key}&delete={$name}" ),
								'data'		=> array( 'delete' => '' )
							)
						), $content );
					}
				}

				return $rows;
			},
			/* Get Root Buttons */
			function()
			{
				return array(
					'add'		=> array(
						'icon'		=> 'plus',
						'title'		=> 'seo_meta_add',
						'link'		=> \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&do=addMeta" ),
					),
					'launch'	=> array(
						'icon'		=> 'magic',
						'title'		=> 'metatag_live_editor',
						'link'		=> \IPS\Http\Url::internal( "app=core&module=system&controller=metatags", "front" ),
						'target'	=> '_blank'
					),
				);
			},
			FALSE,
			TRUE,
			TRUE
		);

        /* Output or return */
        if ( ! \IPS\Request::i()->isAjax() )
        {
	        $output	= \IPS\Theme::i()->getTemplate( 'forms' )->blurb( "what_is_a_metatag", TRUE, TRUE ) . $output;
	    }

		return $output;
	}

	/**
	 * Form to add or edit a meta tag
	 *
	 * @return void
	 */
	public function addMeta()
	{
		$url	= NULL;
		$tags	= array();
		$title	= NULL;

		/* If we have a URL, load up the existing tags for it as we are "editing" */
		if( isset( \IPS\Request::i()->id ) )
		{
			$meta	= \IPS\Db::i()->select( '*', 'core_seo_meta', array( 'meta_id=?', (int) \IPS\Request::i()->id ) )->first();
			$tags	= json_decode( $meta['meta_tags'], TRUE );
			$url	= $meta['meta_url'];
			$title	= $meta['meta_title'];
		}

		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Text( 'metatag_url', $url, FALSE, array( 'placeholder' => 'profile/*' ), NULL, \IPS\Settings::i()->base_url ) );
		$form->hiddenValues['original_url']	= $url;

		$form->add( new \IPS\Helpers\Form\Text( 'metatag_title', $title, FALSE ) );

		/* Now add the rows */
		$matrix = new \IPS\Helpers\Form\Matrix();
		$matrix->manageable = TRUE;
		$matrix->langPrefix = 'metatags_';
		$matrix->columns = array(
			'name'		=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\Select( $key,
					$data['name'],
					TRUE,
					array( 'options' => array( 'keywords' => 'meta_keywords', 'description' => 'meta_description', 'robots' => 'meta_robots', 'other' => 'meta_other' ), 'toggles' => array( 'other' => array( 'other_' . preg_replace( "/[^a-zA-Z0-9\-_]/", "_", $key ) ) ), 'userSuppliedInput' => 'other' ),
					NULL,
					NULL,
					NULL,
					$key
				);
			},
			'content'	=> function( $key, $value, $data )
			{
				return new \IPS\Helpers\Form\TextArea( $key, $data['content'], FALSE );
			},
		);
		
		/* Add rows */
		if( count( $tags ) )
		{
			foreach( $tags as $tagName => $tagValue )
			{
				$matrix->rows[]	= array( 'name' => $tagName, 'content' => $tagValue );
			}
		}

		$form->addMatrix( 'metatag_tags', $matrix );

		/* Are we saving? */
		if ( $values = $form->values() )
		{
			$tags	= array();
			$url	= $values['metatag_url'] ?: '/';
			$title	= $values['metatag_title'];

			foreach( $values['metatag_tags'] as $index => $data )
			{
				if( !$data['name'] OR !$data['content'] )
				{
					continue;
				}

				$tags[ $data['name'] ]	= $data['content'];
			}

			\IPS\Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', $url ) );
			\IPS\Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', \IPS\Request::i()->original_url ) );

			if( $title or count( $tags ) )
			{
				\IPS\Db::i()->insert( 'core_seo_meta', array( 'meta_url' => $url, 'meta_title' => $title, 'meta_tags' => json_encode( $tags ) ) );
			}

			\IPS\Session::i()->log( 'acplogs__seo_metatag_settings' );
			
			unset( \IPS\Data\Store::i()->metaTags );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=promotion&controller=seo&tab=metatags" ) );
		}

		\IPS\Output::i()->title  = \IPS\Member::loggedIn()->language()->addToStack('seo_meta_add');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'seo_meta_add', $form );
	}
	
	/**
	 * Download .htaccess file
	 *
	 * @return	void
	 */
	protected function htaccess()
	{
		$dir = str_replace( \IPS\CP_DIRECTORY . '/index.php', '', $_SERVER['PHP_SELF'] );
		$path = $dir . 'index.php';

		if( \strpos( $dir, ' ' ) !== FALSE )
		{
			$dir = '"' . $dir . '"';
			$path = '"' . $path . '"';
		}

		$htaccess = <<<FILE
<IfModule mod_rewrite.c>
Options -MultiViews
RewriteEngine On
RewriteBase {$dir}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule \\.(js|css|jpeg|jpg|gif|png|ico|map)(\\?|$) {$dir}404error.php [L,NC]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$path} [L]
</IfModule>
FILE;

		\IPS\Output::i()->sendOutput( $htaccess, 200, 'application/x-htaccess', array( 'Content-Disposition' => 'attachment; filename=.htaccess' ) );
	}

}