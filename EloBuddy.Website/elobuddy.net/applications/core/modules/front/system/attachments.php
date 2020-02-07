<?php
/**
 * @brief		My Attachments Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * My Attachments Controller
 */
class _attachments extends \IPS\Dispatcher\Controller
{
	/**
	 * Manage
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Logged in and can upload only */
		if ( !\IPS\Member::loggedIn()->member_id or \IPS\Member::loggedIn()->group['g_attach_max'] == 0 )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C229/1', 403, '' );
		}
		
		/* Build Table */
		$table = new \IPS\Helpers\Table\Db( 'core_attachments', \IPS\Http\Url::internal( 'app=core&module=system&controller=attachments', 'front', 'attachments' ), array( array( 'attach_member_id=?', \IPS\Member::loggedIn()->member_id ) ) );
		$table->include = array( 'attach_id', 'attach_location', 'attach_date', 'attach_file', 'attach_filesize', 'attach_is_image', 'attach_content', 'attach_hits' );
		$table->rowsTemplate = array( \IPS\Theme::i()->getTemplate('myAttachments'), 'rows' );
		$table->joins = array();
		
		/* Sort */
		$table->sortOptions = array( 'attach_date' => 'attach_date', 'attach_file' => 'attach_file', 'attach_filesize' => 'attach_filesize' );
		$table->sortBy = $table->sortBy ?: 'attach_date';
		if ( $table->sortBy === 'attach_file' )
		{
			$table->sortDirection = 'asc';
		}
		$table->filters = array( 'images' => array( 'attach_is_image=1' ), 'files' => array( 'attach_is_image=0' ) );

		/* Get the associated content */
		$self = $this;
		$table->parsers = array( 'attach_content' => function( $val, $row ) use ( $self )
		{
			return \IPS\core\extensions\core\EditorMedia\Attachment::getLocations( $row['attach_id'] );
		} );
			
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('my_attachments');
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('my_attachments') );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('myAttachments')->template( (string) $table, \IPS\Db::i()->select( 'SUM(attach_filesize)', 'core_attachments', array( 'attach_member_id=?', \IPS\Member::loggedIn()->member_id ) )->first(), \IPS\Db::i()->select( 'COUNT(*)', 'core_attachments', array( 'attach_member_id=?', \IPS\Member::loggedIn()->member_id ) )->first() );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		/* Security check */
		\IPS\Session::i()->csrfCheck();

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Load and check permission */
		try
		{
			$attachment = \IPS\Db::i()->select( '*', 'core_attachments', array( 'attach_id=?', \IPS\Request::i()->id ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C229/4', 404, '' );
		}
		
		/* Check it belongs to us */
		if ( $attachment['attach_member_id'] !== \IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C229/5', 403, '' );
		}
		
		/* And we can delete it */
		if ( !\IPS\Member::loggedIn()->group['gbw_delete_attachments'] )
		{
			\IPS\core\extensions\core\EditorMedia\Attachment::getLocations( $attachment['attach_id'] );
			if ( count( \IPS\core\extensions\core\EditorMedia\Attachment::$locations[ $attachment['attach_id'] ] ) )
			{
				\IPS\Output::i()->error( 'no_module_permission', '2C229/8', 403, '' );
			}
		}
		
		/* Delete */
		try
		{
			\IPS\File::get( 'core_Attachment', $attachment['attach_location'] )->delete();
			if ( $attachment['attach_thumb_location'] )
			{
				\IPS\File::get( 'core_Attachment', $attachment['attach_thumb_location'] )->delete();
			}
		}
		catch ( \Exception $e ) { }
		\IPS\Db::i()->delete( 'core_attachments', array( 'attach_id=?', $attachment['attach_id'] ) );
		\IPS\Db::i()->delete( 'core_attachments_map', array( 'attachment_id=?', $attachment['attach_id'] ) );

		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			/* Redirect */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=attachments', 'front', 'attachments' ) );
		}
	}
}