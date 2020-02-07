<?php
/**
 * @brief		Email template management
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		02 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Email template management
 */
class _emails extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emails_manage' );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Create the table */
		$table					= new \IPS\Helpers\Table\Db( 'core_email_templates', \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails' ), array( array( "template_parent=?", 0 ) ) );
		$table->langPrefix		= 'emailtpl_';
		$table->mainColumn		= 'template_name';
		$table->include			= array( 'template_name', 'template_app' );
		$table->quickSearch		= 'template_name';
		$table->limit			= 100;

		/* Default sort options */
		$table->sortBy			= $table->sortBy ?: 'template_name';
		$table->sortDirection	= $table->sortDirection ?: 'desc';

		/* Custom parsers */
		$table->parsers			= array(
			'template_name'	=> function( $val, $row )
			{
				return \IPS\Member::loggedIn()->language()->addToStack( 'emailtpl_' . $val );
			},
			'template_app'	=> function( $val, $row )
			{
				return \IPS\Member::loggedIn()->language()->addToStack( '__app_' . $val );
			}
		);

		$table->rowButtons = function( $row )
		{		
			$return = array();

			/* There isn't a separate permission option because literally the only thing you can do with email templates is edit the */
			$return['edit'] = array(
				'icon'		=> 'pencil',
				'title'		=> 'edit',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=form&key=' ) . $row['template_key'],
			);

			if( $row['template_edited'] )
			{
				$return['revert'] = array(
					'icon'		=> 'undo',
					'title'		=> 'revert',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=revert&key=' ) . $row['template_key'],
					'data'		=> array( 'delete' => '', 'delete-warning' => \IPS\Member::loggedIn()->language()->addToStack('email_revert_confirm') )
				);

				$return['export'] = array(
					'icon'		=> 'download',
					'title'		=> 'download',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=export&key=' ) . $row['template_key'],
				);
			}

			return $return;
		};

		/* Buttons */
		\IPS\Output::i()->sidebar['actions'] = array(
				'upload'	=> array(
						'title'		=> 'upload_email_template',
						'icon'		=> 'upload',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=import' ),
						'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('upload_email_template') )
                ),
                'preview' => array(
                    'title'		=> 'preview_email_wrapper',
                    'icon'		=> 'search',
                    'link'		=> \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=preview' ),
                    'data'		=> array( 'ipsDialog' => \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=preview' ), 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('preview_email_wrapper') )
                ),
		);

		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('emailtpl_header');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'title', (string) $table );
	}

	/**
	 * Export a customized email template
	 *
	 * @return	void
	 */
	public function export()
	{
		/* Get the template info */
		try
		{
			$template	= \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_parent>0 AND template_key=?', \IPS\Request::i()->key ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'email_template_not_found', '3S128/3', 403, '' );
		}

		$xml = \IPS\Xml\SimpleXML::create('emails');

		$xml->addChild( 'template', array(
			'template_app'					=> $template['template_app'],
			'template_name'					=> $template['template_name'],
			'template_content_html'			=> $template['template_content_html'],
			'template_content_plaintext'	=> $template['template_content_plaintext'],
			'template_data'					=> $template['template_data'],
			'template_key'					=> $template['template_key'],
		) );

		$name = addslashes( str_replace( array( ' ', '.', ',' ), '_', $template['template_name'] ) . '.xml' );

		\IPS\Output::i()->sendOutput( $xml->asXML(), 200, 'application/xml', array( 'Content-Disposition' => \IPS\Output::getContentDisposition( 'attachment', $name ) ) );
	}

	/**
	 * Form to import an email template
	 *
	 * @return	void
	 */
	public function import()
	{
		$form = new \IPS\Helpers\Form( 'form', 'upload' );
		
		$form->add( new \IPS\Helpers\Form\Upload( 'email_template_file', NULL, TRUE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ) ) );

		if ( $values = $form->values() )
		{
			/* Open XML file */
			$xml = \IPS\Xml\SimpleXML::loadFile( $values['email_template_file'] );

			if( !count($xml->template) )
			{
				\IPS\Output::i()->error( 'email_template_badform', '1S128/4', 403, '' );
			}

			foreach( $xml->template as $template )
			{
				$update	= array(
					'template_name'					=> (string) $template->template_name,
					'template_data'					=> (string) $template->template_data,
					'template_app'					=> (string) $template->template_app,
					'template_content_html'			=> (string) $template->template_content_html,
					'template_content_plaintext'	=> (string) $template->template_content_plaintext,
					'template_key'					=> (string) $template->template_key,
				);

				try
				{
					$existing = \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_parent=0 AND template_key=?', $update['template_key'] ) )->first();
				}
				catch ( \UnderflowException $e )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack('email_template_noexist', FALSE, array( 'sprintf' => array( $update['template_name'] ) ) ), '1S128/5', 403, '' );
				}
				
				$update['template_parent']	= $existing['template_id'];
				\IPS\Db::i()->replace( 'core_email_templates', $update );
				\IPS\Db::i()->update( 'core_email_templates', array( 'template_edited' => 1 ), array( 'template_id=?', $existing['template_id'] ) );
			}
			
			/* Redirect */
			\IPS\Session::i()->log( 'acplogs__emailtemplate_updated' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails' ), 'email_template_uploaded' );
		}

		/* Display */
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( \IPS\Member::loggedIn()->language()->addToStack('upload_email_template'), $form, FALSE );
	}

	/**
	 * Restore an email template to the original unedited version
	 *
	 * @return	void
	 */
	public function revert()
	{
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		/* Get the template info for the log */
		$template	= \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_parent=0 AND template_key=?', \IPS\Request::i()->key ) )->first();

		/* Revert any user-edited copies of the specified template */
		\IPS\Db::i()->delete( 'core_email_templates', array( 'template_parent>0 AND template_key=?', \IPS\Request::i()->key ) );

		/* Reset edited flag on parent */
		\IPS\Db::i()->update( 'core_email_templates', array( 'template_edited' => 0 ), array( 'template_id=?', $template['template_id'] ) );

		/* Rebuild template */
		$this->buildTemplate( $template );

		/* Log and redirect */
		\IPS\Session::i()->log( 'acplog__emailtpl_reverted', array( $template['template_name'] => FALSE ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails' ), 'reverted' );
	}

	/**
	 * Edit an email template
	 *
	 * @return	void
	 */
	public function form()
	{
		/* Get the template */
		if( empty( \IPS\Request::i()->key ) )
		{
			\IPS\Output::i()->error( 'emailtpl_nofind', '3S128/1', 403, '' );
		}
		
		try
		{
			$template = \IPS\Db::i()->select( '*', 'core_email_templates', array( 'template_key=?', \IPS\Request::i()->key ), 'template_parent DESC', 1 )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'emailtpl_nofind', '3S128/2', 403, '' );
		}

		/* Figure out tags for form helpers */
		$_tags	= explode( ",", $template['template_data'] );
		$tags	= array();

		foreach( $_tags as $_tag )
		{
			$_tag	= explode( '=', $_tag );

			$tags[ '{' . trim( $_tag[0] ) . '}' ]	= '';
		}

		/* Start the form */
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Codemirror( 'content_html', $template['template_content_html'], TRUE, array( 'tags' => $tags ) ) );
		$form->add( new \IPS\Helpers\Form\TextArea( 'content_plaintext', $template['template_content_plaintext'], FALSE, array( 'tags' => $tags, 'rows' => 14 ) ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$save = array(
				'template_name'					=> $template['template_name'],
				'template_data'					=> $template['template_data'],
				'template_key'					=> $template['template_key'],
				'template_content_html'			=> $values['content_html'],
				'template_content_plaintext'	=> $values['content_plaintext'],
				'template_app'					=> $template['template_app'],
				'template_parent'				=> $template['template_parent'] ?: $template['template_id'],
			);

			if ( !empty($template['template_parent']) )
			{
				\IPS\Db::i()->update( 'core_email_templates', $save, array( 'template_id=?', $template['template_id'] ) );
			}
			else
			{
				\IPS\Db::i()->insert( 'core_email_templates', $save );
				\IPS\Db::i()->update( 'core_email_templates', array( 'template_edited' => 1 ), array( 'template_id=?', $template['template_id'] ) );
			}

			$this->buildTemplate( $save );

			\IPS\Session::i()->log( 'acplogs__emailtpl_edited', array( $template['template_name'] => FALSE ) );

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails' ), 'saved' );
		}

		/* Output */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('emailtpl_edit');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'emailtpl_edit', $form );
	}

	/**
	 * Build a template for later execution
	 *
	 * @param	array 	$template	Template data from core_email_templates
	 * @return	void
	 */
	public function buildTemplate( $template )
	{
		$htmlFunction	= 'namespace IPS\Theme;' . "\n" . \IPS\Theme::compileTemplate( $template['template_content_html'], "email_html_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );
		$ptFunction		= 'namespace IPS\Theme;' . "\n" . \IPS\Theme::compileTemplate( $template['template_content_plaintext'], "email_plaintext_{$template['template_app']}_{$template['template_name']}", $template['template_data'] );

		$key	= $template['template_key'] . '_email_html';
		\IPS\Data\Store::i()->$key = $htmlFunction;

		$key	= $template['template_key'] . '_email_plaintext';
		\IPS\Data\Store::i()->$key = $ptFunction;
	}

	/**
	 * Simple wrapper for the email out, so that we can show it in an iframe
	 *
	 * @return	void
	 */
    public function preview()
    {
        \IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('emailtpl_edit');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'customization', 'core', 'admin' )->emailFrame( \IPS\Http\Url::internal( 'app=core&module=customization&controller=emails&do=emailPreview' ) );
    }

    /**
	 * Outputs the raw email preview HTML (to be called inside an iframe)
	 *
	 * @return	void
	 */
    public function emailPreview()
    {
		$email = \IPS\Email::buildFromContent( '', \IPS\Member::loggedIn()->language()->addToStack( 'email_preview_content' ) );
		\IPS\Output::i()->sendOutput( $email->compileContent( 'html' ) );
    }
}