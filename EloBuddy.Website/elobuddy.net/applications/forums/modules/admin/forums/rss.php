<?php
/**
 * @brief		Feeds
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		04 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\modules\admin\forums;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * rss
 */
class _rss extends \IPS\Node\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rss_manage' );
		parent::execute();
	}

	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\forums\Feed';
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['link'] = $buttons['add']['link']->setQueryString( '_new', TRUE );
		}
		
		return $buttons;
	}
	
	/**
	 * Add/Edit Form
	 *
	 * @return void
	 */
	protected function form()
	{
		$nodeClass = $this->nodeClass;
		if ( !\IPS\Request::i()->id and $nodeClass::canAddRoot() )
		{
			\IPS\Output::i()->output = new \IPS\Helpers\Wizard( array(
				'rss_import_url' => function( $data )
				{
					$form = new \IPS\Helpers\Form;
					$form->add( new \IPS\Helpers\Form\Url( 'rss_import_url', NULL, TRUE ) );
					$form->add( new \IPS\Helpers\Form\Text( 'rss_import_auth_user' ) );
					$form->add( new \IPS\Helpers\Form\Password( 'rss_import_auth_pass' ) );
					if ( $values = $form->values() )
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
								$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'rss_import_auth' );
								return $form;
							}
							
							$response = $response->decodeXml();
							if ( !( $response instanceof \IPS\Xml\Rss ) and !( $response instanceof \IPS\Xml\Atom ) )
							{
								$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'rss_import_invalid' );
								return $form;
							}
						}
						catch ( \IPS\Http\Request\Exception $e )
						{
							$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'form_url_bad' );
							return $form;
						}
						catch ( \Exception $e )
						{
							$form->error = \IPS\Member::loggedIn()->language()->addToStack( 'rss_import_invalid' );
							return $form;
						}
						
						return $values;
					}
					return $form;
				},
				'rss_import_preview' => function( $data )
				{
					if ( isset( \IPS\Request::i()->continue ) )
					{
						return $data;
					}
					
					$request = \IPS\Http\Url::external( $data['rss_import_url'] )->request();
					if ( $data['rss_import_auth_user'] or $data['rss_import_auth_pass'] )
					{
						$request = $request->login( $data['rss_import_auth_user'], $data['rss_import_auth_pass'] );
					}
					return \IPS\Theme::i()->getTemplate( 'feeds' )->importPreview( $request->get()->decodeXml()->articles() );
				},
				'rss_import_details' => function ( $data )
				{
					$request = \IPS\Http\Url::external( $data['rss_import_url'] )->request();
					if ( $data['rss_import_auth_user'] or $data['rss_import_auth_pass'] )
					{
						$request = $request->login( $data['rss_import_auth_user'], $data['rss_import_auth_pass'] );
					}
										
					$form = new \IPS\Helpers\Form;
					$form->add( new \IPS\Helpers\Form\Node( 'rss_import_forum_id', NULL, TRUE, array( 'class' => 'IPS\forums\Forum', 'permissionCheck' => function ( $forum )
					{
						return $forum->sub_can_post and !$forum->redirect_url;
					} ) ) );
					$form->add( new \IPS\Helpers\Form\Member( 'rss_import_mid', \IPS\Member::loggedIn(), TRUE ) );
					$form->add( new \IPS\Helpers\Form\Text( 'rss_import_showlink',  \IPS\Member::loggedIn()->language()->addToStack('rss_import_showlink_default') ) );
					$form->add( new \IPS\Helpers\Form\Radio( 'rss_import_topic_open', NULL, FALSE, array( 'options' => array( 1 => 'unlocked', 0 => 'locked' ) ) ) );
					$form->add( new \IPS\Helpers\Form\Radio( 'rss_import_topic_hide', NULL, FALSE, array( 'options' => array( 0 => 'unhidden', 1 => 'hidden' ) ) ) );
					$form->add( new \IPS\Helpers\Form\Text( 'rss_import_topic_pre', NULL, FALSE, array( 'trim' => FALSE ) ) );
					
					if ( $values = $form->values() )
					{
						$import = new \IPS\forums\Feed;
						$import->enabled = TRUE;
						$import->title = $request->get()->decodeXml()->title();
						$import->url = (string) $data['rss_import_url'];
						$import->forum_id = $values['rss_import_forum_id']->_id;
						$import->mid = $values['rss_import_mid']->member_id;
						$import->pergo = 10;
						$import->time = 30;
						$import->showlink = $values['rss_import_showlink'];
						$import->topic_open = $values['rss_import_topic_open'];
						$import->topic_hide = $values['rss_import_topic_hide'];
						$import->topic_pre = $values['rss_import_topic_pre'];
						$import->auth = ( $data['rss_import_auth_user'] or $data['rss_import_auth_pass'] );
						$import->auth_user = ( $data['rss_import_auth_user'] or $data['rss_import_auth_pass'] ) ? $data['rss_import_auth_user'] : NULL;
						$import->auth_pass = ( $data['rss_import_auth_user'] or $data['rss_import_auth_pass'] ) ? $data['rss_import_auth_pass'] : NULL;
						$import->save();
						try
						{
							$import->run();
						}
						catch( \Exception $e )
						{
							\IPS\Output::i()->error( 'rss_run_error', '3F181/3', 500, '' );
						}
						\IPS\Db::i()->update( 'core_tasks', array( 'enabled' => 1 ), array( '`key`=?', 'rssimport' ) );
						\IPS\Session::i()->log( 'acplog__node_created', array( (string) $import->title => TRUE, (string) $import->title => FALSE ) );
						\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=forums&module=forums&controller=rss' ) );
					}
					
					return $form;
				}
			), \IPS\Http\Url::internal('app=forums&module=forums&controller=rss&do=form') );
		}
		else
		{
			return parent::form();
		}
	}
	
	/**
	 * Run
	 *
	 * @return	void
	 */
	public function run()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'rss_run' );
		
		try
		{
			$feed = \IPS\forums\Feed::load( \IPS\Request::i()->id );
			$feed->run();
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2F181/1', 404, '' );
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( 'rss_run_error', '3F181/2', 500, '' );
		}
		
		\IPS\Session::i()->log( 'acplog__rss_ran', array( $feed->title => FALSE ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=forums&module=forums&controller=rss' ) );
	}
}