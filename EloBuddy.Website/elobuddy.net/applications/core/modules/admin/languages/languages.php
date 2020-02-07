<?php
/**
 * @brief		languages
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\languages;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * languages
 */
class _languages extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\Lang';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'lang_words' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'flags.css', 'core', 'global' ) );
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'languages', 'lang_words' ) )
		{
			\IPS\Output::i()->sidebar['actions'][] = array(
				'icon'	=> 'globe',
				'title'	=> 'lang_vle',
				'link'	=> \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages&do=vle' ),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('lang_vle') )
			);
		}
		
		parent::manage();
	}
	
	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form()
	{
		/* If we have no ID number, this is the add form, which is handled differently to the edit */
		if ( !\IPS\Request::i()->id )
		{
			$tab = \IPS\Request::i()->tab ?: 'new';
			
			/* CREATE NEW */
			$max = \IPS\Db::i()->select( 'MAX(lang_order)', 'core_sys_lang' )->first();
			if ( $tab === 'new' )
			{
				/* Build form */
				$form = new \IPS\Helpers\Form;
				$lang = new \IPS\Lang( array( 'lang_short' => 'en_US' ) );
				$lang->form( $form );
				$activeTabContents = $form;
				
				/* Handle submissions */
				if ( $values = $form->values() )
				{
					/* Find the correct locale */
					if ( !isset($values['lang_short']) OR $values['lang_short'] === 'x' )
					{
						$values['lang_short'] = $values['lang_short_custom'];
					}
					unset( $values['lang_short_custom'] );

					/* reset default language if we want this to be default */
					if( isset( $values['lang_default'] ) and $values['lang_default'] )
					{
						\IPS\Db::i()->update( 'core_sys_lang', array( 'lang_default' => 0 ) );
					}

					/* Add "UTF8" if we can */
					$currentLocale = setlocale( LC_ALL, '0' );

					foreach ( array( "{$values['lang_short']}.UTF-8", "{$values['lang_short']}.UTF8" ) as $l )
					{
						$test = setlocale( LC_ALL, $l );
						if ( $test !== FALSE )
						{
							$values['lang_short'] = $l;
							break;
						}
					}

					foreach( explode( ";", $currentLocale ) as $locale )
					{
						$parts = explode( "=", $locale );
						if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
						{
							setlocale( constant( $parts[0] ), $parts[1] );
						}
					}
					
					/* Insert the actual language */
					$values['lang_order'] = ++$max;
					$insertId = \IPS\Db::i()->insert( 'core_sys_lang', $values );
					
					/* Copy over language strings */
					$default = \IPS\Lang::defaultLanguage();
					$prefix = \IPS\Db::i()->prefix;
					$defaultStmt = \IPS\Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, NULL AS `word_custom`, `word_default_version`, NULL AS `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=1" );
					$defaultStmt->execute();
					$customStmt = \IPS\Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=0" );
					$customStmt->execute();

					unset( \IPS\Data\Store::i()->languages );

					/* Clear guest page caches */
					\IPS\Data\Cache::i()->clearAll();

					/* Log */
					\IPS\Session::i()->log( 'acplogs__lang_created', array( $values['lang_title'] => FALSE ) );
					
					/* Redirect */
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ), 'saved' );
				}
			}
			
			/* UPLOAD */
			else
			{
				/* Build form */
				$form = new \IPS\Helpers\Form;
				$form->add( new \IPS\Helpers\Form\Upload( 'lang_upload', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );
				\IPS\Lang::localeField( $form );
				
				$activeTabContents = $form;
				
				/* Handle submissions */
				if ( $values = $form->values() )
				{
					/* Move it to a temporary location */
					$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
					move_uploaded_file( $values['lang_upload'], $tempFile );
					
					/* Work out locale */
					if( isset( $values['lang_short_custom'] ) )
					{
						if ( !isset($values['lang_short']) OR $values['lang_short'] === 'x' )
						{
							$locale = $values['lang_short_custom'];
						}
						else
						{
							$locale = $values['lang_short'];
						}
					}
										
					/* Initate a redirector */
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ), 'locale' => $locale ) ) );
				}
			}
			
			/* Display */
			if( \IPS\Request::i()->isAjax() and \IPS\Request::i()->existing and !isset( \IPS\Request::i()->ajaxValidate ) )
			{
				\IPS\Output::i()->output = $activeTabContents;
			}
			else
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->tabs( array( 'new' => 'add', 'upload' => 'upload' ), $tab, $activeTabContents, \IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=form&existing=1" ) );
			}
		}
		/* If it's an edit, we can just let the node controller handle it */
		else
		{
			return parent::form();
		}
	}
	
	/**
	 * Toggle Enabled/Disable
	 *
	 * @return	void
	 */
	protected function enableToggle()
	{
		/* Load Language */
		try
		{
			$language = \IPS\Lang::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '3C126/1', 404, '' );
		}
		/* Check we're not locked */
		if( $language->_locked or !$language->canEdit() )
		{
			\IPS\Output::i()->error( 'node_noperm_enable', '2C126/2', 403, '' );
		}
		
		/* Check if any members are using this */
		if ( !\IPS\Request::i()->status )
		{
			$count = \IPS\Db::i()->select( 'count(*)', 'core_members', array( 'language=?', $language->_id ) )->first();
			if ( $count['count'] )
			{
				if ( \IPS\Request::i()->isAjax() )
				{
					\IPS\Output::i()->json( false, 500 );
				}
				else
				{
					$options = array();
					foreach ( \IPS\Lang::languages() as $lang )
					{
						if ( $lang->id != $language->_id )
						{
							$options[ $lang->id ] = $lang->title;
						}
					}
					
					$form = new \IPS\Helpers\Form;
					$form->add( new \IPS\Helpers\Form\Select( 'lang_change_to', \IPS\Lang::defaultLanguage(), TRUE, array( 'options' => $options ) ) );
					
					if ( $values = $form->values() )
					{
						\IPS\Db::i()->update( 'core_members', array( 'language' => $values['lang_change_to'] ), array( 'language=?', $language->_id ) );
					}
					else
					{
						\IPS\Output::i()->output = $form;
						return;
					}
				}
			}
		}
		
		/* Do it */
		\IPS\Db::i()->update( 'core_sys_lang', array( 'lang_enabled' => (bool) \IPS\Request::i()->status ), array( 'lang_id=?', $language->_id ) );
		unset( \IPS\Data\Store::i()->languages );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		/* Log */
		if ( \IPS\Request::i()->status )
		{
			\IPS\Session::i()->log( 'acplog__node_enabled', array( 'menu__core_languages_languages' => TRUE, $language->title => FALSE ) );
		}
		else
		{
			\IPS\Session::i()->log( 'acplog__node_disabled', array( 'menu__core_languages_languages' => TRUE, $language->title => FALSE ) );
		}
		
		/* Redirect */
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( (bool) \IPS\Request::i()->status );
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ), 'saved' );
		}
	}
	
	/**
	 * Visual Language Editor
	 *
	 * @return	void
	 */
	protected function vle()
	{
		if( \IPS\IN_DEV )
		{
			\IPS\Member::loggedIn()->language()->words['lang_vle_editor_warning']	= \IPS\Member::loggedIn()->language()->addToStack( 'dev_lang_vle_editor_warn', FALSE );
		}

		$form = new \IPS\Helpers\Form();
		$form->add( new \IPS\Helpers\Form\YesNo( 'lang_vle_editor', ( isset( \IPS\Request::i()->cookie['vle_editor'] ) and \IPS\Request::i()->cookie['vle_editor'] ) and !\IPS\IN_DEV, FALSE, array( 'disabled' => \IPS\IN_DEV ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'lang_vle_keys', isset( \IPS\Request::i()->cookie['vle_keys'] ) and \IPS\Request::i()->cookie['vle_keys'] ) );
		
		if ( $values = $form->values() )
		{
			foreach ( array( 'vle_editor', 'vle_keys' ) as $k )
			{
				if ( $values[ 'lang_' . $k ] )
				{
					\IPS\Request::i()->setcookie( $k, 1 );
				}
				elseif ( isset( \IPS\Request::i()->cookie[ $k ] ) )
				{
					\IPS\Request::i()->setcookie( $k, 0 );
				}
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ) );
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Translate
	 *
	 * @return	void
	 */
	protected function translate()
	{
		if ( isset( \IPS\Request::i()->cookie['vle_editor'] ) and \IPS\Request::i()->cookie['vle_editor'] )
		{
			\IPS\Output::i()->error( 'no_translate_with_vle', '1C126/8', 403, '' );
		}

		try
		{
			$lang = \IPS\Lang::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C126/3', 404, '' );
		}
		
		$where = array(
			array( 'lang_id=? AND word_export=1', \IPS\Request::i()->id ),
		);
		
		$table = new \IPS\Helpers\Table\Db( 'core_sys_lang_words', \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages&do=translate&id=' . \IPS\Request::i()->id ), $where );
		$table->langPrefix = 'lang_';
		$table->classes = array( 'cTranslateTable' );
		
		$table->include = array( 'word_app', 'word_plugin', 'word_theme', 'word_key', 'word_default', 'word_custom' );

		$table->parsers = array(
			'word_app' => function( $val, $row )
			{
				try
				{
					return \IPS\Application::load( $row['word_app'] )->_title;
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( \IPS\Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge_neutral' ) );
				}
				catch ( \InvalidArgumentException $e )
				{
					return \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( \IPS\Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge_neutral' ) );
				}
				catch ( \UnexpectedValueException $e )
				{
					return \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( \IPS\Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge_neutral' ) );
				}
			},
			'word_plugin' => function( $val, $row )
			{
				try
				{
					return \IPS\Plugin::load( $row['word_plugin'] )->name;
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( \IPS\Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge_neutral' ) );
				}
			},
			'word_theme' => function( $val, $row )
			{
				try
				{
					return \IPS\Theme::load( $row['word_theme'] )->_title;
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( \IPS\Member::loggedIn()->language()->addToStack('translate_na'), array( 'ipsBadge', 'ipsBadge_neutral' ) );
				}
			},
			'word_custom'	=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate( 'customization' )->langString( $val, $row['word_key'], $row['lang_id'], $row['word_js'] );
			},
		);
		
		$table->sortBy = $table->sortBy ?: 'word_key';
		$table->sortDirection = $table->sortDirection ?: 'asc';

		$table->quickSearch = array( array( 'word_default', 'word_key' ), 'word_default' );
		$table->advancedSearch = array(
			'word_key'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'word_default'	=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'word_custom'	=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
		);
		
		$table->filters = array(
			'lang_filter_translated'	=> 'word_custom IS NOT NULL',
			'lang_filter_untranslated'	=> 'word_custom IS NULL',
			'lang_filter_out_of_date'	=> 'word_custom IS NOT NULL AND word_custom_version<word_default_version'
		);
		
		$table->widths = array( 'word_key' => 15, 'word_default' => 35, 'word_custom' => 50 );
		
		\IPS\Member::loggedIn()->language()->words['lang_word_custom'] = $lang->title;
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'customization/languages.css', 'core', 'admin' ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_core.js' ) );
		\IPS\Output::i()->title = $lang->title;
		\IPS\Output::i()->output = (string) $table;
	}
	
	/**
	 * Translate Word
	 *
	 * @return	void
	 */
	protected function translateWord()
	{
		try
		{
			$lang = \IPS\Lang::load( \IPS\Request::i()->lang );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C126/4', 404, '' );
		}
		
		$word = \IPS\Db::i()->select( '*', 'core_sys_lang_words', array( 'lang_id=? AND word_key=? AND word_js=?', \IPS\Request::i()->lang, \IPS\Request::i()->key, (int) \IPS\Request::i()->js ) )->first();
		
		$form = new \IPS\Helpers\Form;
		$form->addDummy( 'lang_word_default', $word['word_default'] );
		$form->add( new \IPS\Helpers\Form\Text( 'lang_word_custom', $word['word_custom'] ) );
		
		if ( $values = $form->values() )
		{
			$version = 0;
			try
			{
				if ( $word['word_app'] )
				{
					$version = \IPS\Application::load( $word['word_app'] )->long_version;
				}
				elseif ( $word['word_plugin'] )
				{
					$version = \IPS\Plugin::load( $word['word_plugin'] )->version_long;
				}
				elseif ( $word['word_theme'] )
				{
					$version = \IPS\Theme::load( $word['word_theme'] )->long_version;
				}
			}
			catch ( \OutOfRangeException $e ) { }
			
			\IPS\Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => ( $values['lang_word_custom'] ? urldecode( $values['lang_word_custom'] ) : NULL ), 'word_custom_version' => ( $values['lang_word_custom'] ? $version : NULL ) ), array( 'word_id=?', $word['word_id'] ) );
			\IPS\Session::i()->log( 'acplogs__lang_translate', array( $word['word_key'] => FALSE, $lang->title => FALSE ) );
			
			if ( $word['word_js'] )
			{
				\IPS\Output::clearJsFiles( 'global', 'root', 'js_lang_' . $word['lang_id'] . '.js' );
			}

			if ( $word['word_key'] === '_list_format_' )
			{
				unset( \IPS\Data\Store::i()->listFormats );
			}

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array() );
			}
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages&do=translate&id=' . $word['lang_id'] ) );
			}
		}
		
		\IPS\Member::loggedIn()->language()->words['lang_word_custom'] = $lang->title;
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Copy
	 *
	 * @return	void
	 */
	protected function copy()
	{
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=copy&id=" . intval( \IPS\Request::i()->id ) ),
			function( $data )
			{
				if ( !is_array( $data ) )
				{
					$lang = \IPS\Db::i()->select( '*', 'core_sys_lang',  array( 'lang_id=?', \IPS\Request::i()->id ) )->first();
					unset( $lang['lang_id'] );

					$lang['lang_title'] = $lang['lang_title'] . ' ' . \IPS\Member::loggedIn()->language()->get('copy_noun');
					$lang['lang_default'] = FALSE;

					$insertId = \IPS\Db::i()->insert( 'core_sys_lang', $lang );
					
					\IPS\Session::i()->log( 'acplog__node_copied', array( 'menu__core_languages_languages' => TRUE, $lang['lang_title'] => FALSE ) );
					
					$words = \IPS\Db::i()->select( 'count(*)', 'core_sys_lang_words', array( 'lang_id=?', \IPS\Request::i()->id ) )->first();
					
					return array( array( 'id' => $insertId, 'done' => 0, 'total' => $words ), \IPS\Member::loggedIn()->language()->addToStack('copying'), 1 );
				}
				else
				{
					$words = \IPS\Db::i()->select(  '*', 'core_sys_lang_words', array( 'lang_id=?', \IPS\Request::i()->id ), 'word_id', array( $data['done'], 100 ) );
					if ( !count( $words  ) )
					{
						return NULL;
					}
					else
					{
						foreach ( $words as $row )
						{
							unset( $row['word_id'] );
							$row['lang_id'] = $data['id'];
							\IPS\Db::i()->replace( 'core_sys_lang_words', $row );
						}
					}
					
					
					$data['done'] += 100;
					return array( $data, \IPS\Member::loggedIn()->language()->addToStack('copying'), ( 100 / $data['total'] * $data['done'] ) );
				}
			},
			function()
			{
				unset( \IPS\Data\Store::i()->languages );

				/* Clear guest page caches */
				\IPS\Data\Cache::i()->clearAll();

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ) );
			}
		);
	}
	
	/**
	 * Download
	 *
	 * @return	void
	 */
	protected function download()
	{
		/* Load language */
		try
		{
			$lang = \IPS\Lang::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C126/5', 404, '' );
		}

		$count = 0;
		try
		{
			$count = \IPS\Db::i()->select( 'COUNT(word_id)', 'core_sys_lang_words', array( 'lang_id=? AND word_export=1 AND word_custom IS NOT NULL', $lang->id ), 'word_id', NULL, 'word_id' )->first();
		}
		catch ( \UnderflowException $e ) {}

		if ( $count < 1 )
		{
			\IPS\Output::i()->error( 'core_lang_download_empty', '1C126/7', 404, '' );
		}
		
		/* Init */
		$xml = new \XMLWriter;
		$xml->openMemory();
		$xml->setIndent( TRUE );
		$xml->startDocument( '1.0', 'UTF-8' );
				
		/* Root tag */
		$xml->startElement('language');
		$xml->startAttribute('name');
		$xml->text( $lang->title );
		$xml->endAttribute();
		$xml->startAttribute('rtl');
		$xml->text( $lang->isrtl );
		$xml->endAttribute();
		
		/* Loop applications */
		foreach ( \IPS\Application::applications() as $app )
		{
			/* Initiate the <app> tag */
			$xml->startElement('app');
			
			/* Set key */
			$xml->startAttribute('key');
			$xml->text( $app->directory );
			$xml->endAttribute();
			
			/* Set version */
			$xml->startAttribute('version');
			$xml->text( $app->long_version );
			$xml->endAttribute();
			
			/* Add words */
			foreach ( \IPS\Db::i()->select( '*', 'core_sys_lang_words', array( 'lang_id=? AND word_app=? AND word_export=1 AND word_custom IS NOT NULL', $lang->id, $app->directory ), 'word_id' ) as $row )
			{
				/* Start */
				$xml->startElement( 'word' );
				
				/* Add key */
				$xml->startAttribute('key');
				$xml->text( $row['word_key'] );
				$xml->endAttribute();

				/* Is this a javascript string? */
				$xml->startAttribute('js');
				$xml->text( $row['word_js'] );
				$xml->endAttribute();
								
				/* Write value */
				if ( preg_match( '/<|>|&/', $row['word_custom'] ) )
				{
					$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row['word_custom'] ) );
				}
				else
				{
					$xml->text( $row['word_custom'] );
				}
				
				/* End */
				$xml->endElement();
			}
			
			/* </app> */
			$xml->endElement();
		}
		
		/* Finish */
		$xml->endDocument();
		\IPS\Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => \IPS\Output::getContentDisposition( 'attachment', $lang->title . '.xml' ) ) );
	}
	
	/**
	 * Upload new version
	 *
	 * @return	void
	 */
	public function uploadNewVersion()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'lang_words' );
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Upload( 'lang_upload', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['lang_upload'], $tempFile );
								
			/* Initate a redirector */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages&do=import&file=' . urlencode( $tempFile ) . '&key=' . md5_file( $tempFile ) . '&into=' . \IPS\Request::i()->id ) );
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	protected function import()
	{
		if ( !file_exists( \IPS\Request::i()->file ) or md5_file( \IPS\Request::i()->file ) !== \IPS\Request::i()->key )
		{
			\IPS\Output::i()->error( 'generic_error', '3C126/6', 500, '' );
		}
		
		$url = \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages&do=import' )->setQueryString( array( 'file' => \IPS\Request::i()->file, 'key' => \IPS\Request::i()->key, 'locale' => \IPS\Request::i()->locale ) );
		if ( isset( \IPS\Request::i()->into ) )
		{
			$url = $url->setQueryString( 'into', \IPS\Request::i()->into );
		}
		
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			$url,
			function( $data )
			{
				/* Open XML file */
				$xml = new \XMLReader;
				$xml->open( \IPS\Request::i()->file );
				$xml->read();
				
				/* If this is the first batch, create the language record */
				if ( !is_array( $data ) )
				{
					/* Create the record */
					if ( isset( \IPS\Request::i()->into ) )
					{
						$insertId = \IPS\Request::i()->into;
					}
					else
					{
						/* Add "UTF8" if we can */
						$currentLocale = setlocale( LC_ALL, '0' );

						foreach ( array( \IPS\Request::i()->locale . ".UTF-8", \IPS\Request::i()->locale . ".UTF8" ) as $l )
						{
							$test = setlocale( LC_ALL, $l );
							if ( $test !== FALSE )
							{
								\IPS\Request::i()->locale = $l;
								break;
							}
						}

						foreach( explode( ";", $currentLocale ) as $locale )
						{
							$parts = explode( "=", $locale );
							if( in_array( $parts[0], array( 'LC_ALL', 'LC_COLLATE', 'LC_CTYPE', 'LC_MONETARY', 'LC_NUMERIC', 'LC_TIME' ) ) )
							{
								setlocale( constant( $parts[0] ), $parts[1] );
							}
						}

						/* Insert the language pack record */
						$max = \IPS\Db::i()->select( 'MAX(lang_order)', 'core_sys_lang' )->first();
						$insertId = \IPS\Db::i()->insert( 'core_sys_lang', array(
							'lang_short'	=> \IPS\Request::i()->locale,
							'lang_title'	=> $xml->getAttribute('name'),
							'lang_isrtl'	=> $xml->getAttribute('rtl'),
							'lang_order'	=> $max['max'] + 1
						) );
					
						/* Copy over default language strings */
						$default = \IPS\Lang::defaultLanguage();
						$prefix = \IPS\Db::i()->prefix;
						$defaultStmt = \IPS\Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, NULL AS `word_custom`, `word_default_version`, NULL AS `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=1" );
						$defaultStmt->execute();
						$customStmt = \IPS\Db::i()->prepare( "INSERT INTO `{$prefix}core_sys_lang_words` ( `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` ) SELECT {$insertId} AS `lang_id`, `word_app`, `word_key`, `word_default`, `word_custom`, `word_default_version`, `word_custom_version`, `word_js`, `word_export` FROM `{$prefix}core_sys_lang_words` WHERE `lang_id`={$default} AND `word_export`=0" );
						$customStmt->execute();
					}
					
					/* Log */
					\IPS\Session::i()->log( 'acplogs__lang_created', array( $xml->getAttribute('name') => FALSE ) );
					
					/* Start importing */
					$data = array( 'apps' => array(), 'id' => $insertId );
					return array( $data, \IPS\Member::loggedIn()->language()->get('processing') );
				}
				
				/* Move to correct app */
				$appKey = NULL;
				$version = NULL;
				$xml->read();
				while ( $xml->read() )
				{
					$appKey = $xml->getAttribute('key');
					if ( !array_key_exists( $appKey, $data['apps'] ) )
					{
						/* Get version */
						$version = $xml->getAttribute('version');
						
						/* Import */
						$xml->read();
						while ( $xml->read() and $xml->name == 'word' )
						{
							\IPS\Db::i()->insert( 'core_sys_lang_words', array(
								'word_app'				=> $appKey,
								'word_key'				=> $xml->getAttribute('key'),
								'lang_id'				=> $data['id'],
								'word_custom'			=> $xml->readString(),
								'word_custom_version'	=> $version,
								'word_js'				=> (int) $xml->getAttribute('js'),
								'word_export'			=> 1,
							), TRUE );
							$xml->next();
						}
						
						/* Done */
						$data['apps'][ $appKey ] = TRUE;
						return array( $data, \IPS\Member::loggedIn()->language()->get('processing') );
					}
					else
					{
						$xml->next();
					}
				}
							
				/* All done */
				return NULL;
			},
			function()
			{
				unset( \IPS\Data\Store::i()->languages );

				/* Clear guest page caches */
				\IPS\Data\Cache::i()->clearAll();

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ) );
			}
		);
	}
	
	/**
	 * Developer Import
	 *
	 * @return	void
	 */
	protected function devimport()
	{
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( "app=core&module=languages&controller=languages&do=devimport&id=" . intval( \IPS\Request::i()->id ) ),
			function ( $data )
			{
				if ( !is_array( $data ) )
				{
					\IPS\Db::i()->delete( 'core_sys_lang_words', array( 'lang_id=? AND word_export=1', \IPS\Request::i()->id ) );
					return array( array(), \IPS\Member::loggedIn()->language()->addToStack('lang_dev_importing'), 1 );
				}
								
				$done = FALSE;
				foreach ( \IPS\Application::applications() as $appKey => $app )
				{
					if ( !array_key_exists( $appKey, $data ) )
					{
						$words = array();
						$lang = array();
						require \IPS\ROOT_PATH . "/applications/{$app->directory}/dev/lang.php";
						foreach ( $lang as $k => $v )
						{
							\IPS\Db::i()->replace( 'core_sys_lang_words', array(
								'lang_id'				=> \IPS\Request::i()->id,
								'word_app'				=> $app->directory,
								'word_key'				=> $k,
								'word_default'			=> $v,
								'word_custom'			=> NULL,
								'word_default_version'	=> $app->long_version,
								'word_custom_version'	=> NULL,
								'word_js'				=> 0,
								'word_export'			=> 1,
							) );
						}
												
						$data[ $appKey ] = 0;
						$done = TRUE;
						break;
					}
					elseif ( $data[ $appKey ] === 0 )
					{
						$words = array();
						$lang = array();
						require \IPS\ROOT_PATH . "/applications/{$app->directory}/dev/jslang.php";
						foreach ( $lang as $k => $v )
						{
							\IPS\Db::i()->replace( 'core_sys_lang_words', array(
								'lang_id'				=> \IPS\Request::i()->id,
								'word_app'				=> $app->directory,
								'word_key'				=> $k,
								'word_default'			=> $v,
								'word_custom'			=> NULL,
								'word_default_version'	=> $app->long_version,
								'word_custom_version'	=> NULL,
								'word_js'				=> 1,
								'word_export'			=> 1,
							) );
						}

						$data[ $appKey ] = 1;
						$done = TRUE;
						break;
					}
				}
				
				if ( $done === FALSE )
				{
					return NULL;
				}
				
				return array( $data, \IPS\Member::loggedIn()->language()->addToStack('lang_dev_importing'), ( 100 / ( count( \IPS\Application::applications() ) * 2 ) * count( $data ) ) );
			},
			function ()
			{
				unset( \IPS\Data\Store::i()->languages );
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ), 'saved' );
			}
		);
	}
	
	/**
	 * Set Members
	 *
	 * @return	void
	 */
	public function setMembers()
	{
		$form = new \IPS\Helpers\Form;
		$form->hiddenvalues['id'] = \IPS\Request::i()->id;
		$form->add( new \IPS\Helpers\Form\Select( 'member_reset_where', '*', TRUE, array( 'options' => \IPS\Member\Group::groups( TRUE, FALSE ), 'multiple' => TRUE, 'parse' => 'normal', 'unlimited' => '*', 'unlimitedLang' => 'all' ) ) );
		
		if ( $values = $form->values() )
		{
			if ( $values['member_reset_where'] === '*' )
			{
				$where = NULL;
			}
			else
			{
				$where = \IPS\Db::i()->in( 'member_group_id', $values['member_reset_where'] );
			}
			
			if ( $where )
			{
				\IPS\Db::i()->update( 'core_members', array( 'language' => \IPS\Request::i()->id ), $where );
			}
			else
			{
				\IPS\Member::updateAllMembers( array( 'language' => \IPS\Request::i()->id ) );
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=languages&controller=languages' ), 'reset' );
		}

		\IPS\Output::i()->output = $form;
	}
}