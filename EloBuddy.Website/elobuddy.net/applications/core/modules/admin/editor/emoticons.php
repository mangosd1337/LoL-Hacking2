<?php
/**
 * @brief		Emoticons
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		02 May 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\editor;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Emoticons
 */
class _emoticons extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emoticons_manage' );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'customization/emoticons.css', 'core', 'admin' ) );
		parent::execute();
	}

	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_editor_emoticons');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->block( 'menu__core_editor_emoticons', \IPS\Theme::i()->getTemplate( 'customization' )->emoticons( $this->_getEmoticons() ) );
		
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'editor', 'emoticons_edit' ) )
		{
			\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('admin_customization.js', 'core', 'admin') );
		}
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'editor', 'emoticons_add' ) )
		{
			\IPS\Output::i()->sidebar['actions'] = array(
				'add'	=> array(
					'icon'	=> 'plus-circle',
					'title'	=> 'emoticons_add',
					'link'	=> \IPS\Http\Url::internal( 'app=core&module=editor&controller=emoticons&do=add' ),
					'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('emoticons_add') )
				),
			);
		}
	}
	
	/**
	 * Add
	 *
	 * @return	void
	 */
	protected function add()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emoticons_add' );
	
		$groups = iterator_to_array( \IPS\Db::i()->select( "emo_set, CONCAT( 'core_emoticon_group_', emo_set ) as emo_set_name", 'core_emoticons', null, null, null, 'emo_set' )->setKeyField('emo_set')->setValueField('emo_set_name') );

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Upload( 'emoticons_upload', NULL, TRUE, array( 'multiple' => TRUE, 'image' => TRUE, 'storageExtension' => 'core_Emoticons', 'storageContainer' => 'emoticons', 'obscure' => FALSE ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'emoticons_add_group', 'create', TRUE, array(
			'options'	=> array( 'create' => 'emoticons_add_create', 'existing' => 'emoticons_add_existing' ),
			'toggles'	=> array( 'create' => array( 'emoticons_add_newgroup' ), 'existing' => array( 'emoticons_add_choosegroup' ) ),
			'disabled'	=> empty($groups)
		) ) );
		$form->add( new \IPS\Helpers\Form\Translatable( 'emoticons_add_newgroup', NULL, FALSE, array(), function( $value )
		{
			if ( \IPS\Request::i()->emoticons_add_group === 'create' )
			{
				foreach ( \IPS\Lang::languages() as $lang )
				{
					if ( $lang->default )
					{
						if( ! $value[ $lang->id ] )
						{		
							throw new \InvalidArgumentException('form_required');
						}
					}
				}
			}
		}, NULL, NULL, 'emoticons_add_newgroup' ) );
		
		if ( !empty( $groups ) )
		{
			$form->add( new \IPS\Helpers\Form\Select( 'emoticons_add_choosegroup', NULL, FALSE, array( 'options' => $groups ), NULL, NULL, NULL, 'emoticons_add_choosegroup' ) );
		}
		
		if ( $values = $form->values() )
		{
			if ( $values['emoticons_add_group'] === 'create' )
			{
				$position = 0;
				$setId = md5( uniqid() );
				\IPS\Lang::saveCustom( 'core', "core_emoticon_group_{$setId}", $values['emoticons_add_newgroup'] );
                \IPS\Session::i()->log( 'acplog__emoticon_group_created', array( "core_emoticon_group_{$setId}" => TRUE ) );
			}
			else
			{
				$setId = $values['emoticons_add_choosegroup'];
				$position = \IPS\Db::i()->select( 'MAX(emo_position)', 'core_emoticons', array( 'emo_set=?', $setId ) )->first( );
				$position = $position['pos'];
			}
					
			if ( !is_array( $values['emoticons_upload'] ) )
			{
				$values['emoticons_upload'] = array( $values['emoticons_upload'] );
			}
			
			$inserts = array();
			$images2x = array();
			foreach ( $values['emoticons_upload'] as $file )
			{
				/* Is it "retina" */
				if( \mb_stristr( $file->filename, '@2x' ) )
				{
					$filename_2x = preg_replace( "/^(.+?)\.[0-9a-f]{32}(?:\..+?)$/i", "$1", str_replace( '@2x', '', $file->filename ) );

					$images2x[ $this->_getRawFilename( $filename_2x ) ] = (string) $file;
					continue;
				}

				$filename	= preg_replace( "/^(.+?)\.[0-9a-f]{32}(?:\..+?)$/i", "$1", $file->filename );

				$inserts[] = array(
					'typed'			=> ':' . $this->_getRawFilename( $filename ) . ':',
					'image'			=> (string) $file,
					'clickable'		=> TRUE,
					'emo_set'		=> $setId,
					'emo_position'	=> ++$position,
				);
			}

			if( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_emoticons', $inserts );
			}

			/* Add 2x */
			if( count( $images2x ) )
			{
				foreach( \IPS\Db::i()->select( '*', 'core_emoticons', array( 'emo_set=?', $setId ) ) as $emo )
				{
					$file = \IPS\File::get( 'core_Emoticons', $emo['image'] );
					$filename = $this->_getRawFilename( $file->filename );

					/* There isn't an original for the 2x emo */
					if( !isset( $images2x[ $filename ] ) )
					{
						continue;
					}

					/* Get the dimensions of the smaller emoticon */
					$imageDimensions = $file->getImageDimensions();

					\IPS\Db::i()->update( 'core_emoticons', array(
						'image_2x' => $images2x[ $filename ],
						'width' => $imageDimensions[0],
						'height' => $imageDimensions[1]
					), 'id=' . $emo['id'] );

					unset( $images2x[ $filename ] );
				}

				/* Delete any unused 2x files */
				foreach( $images2x as $img )
				{
					\IPS\File::get( 'core_Emoticons', $img )->delete();
				}
			}

			unset( \IPS\Data\Store::i()->emoticons );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

            \IPS\Session::i()->log( 'acplog__emoticons_added', array( "core_emoticon_group_{$setId}" => TRUE ) );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=editor&controller=emoticons' ), 'saved' );
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'emoticons_add', $form, FALSE );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	protected function edit()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emoticons_edit' );		

		$position = 0;
		$set = NULL;
		
		if ( \IPS\Request::i()->isAjax() )
		{
			$i = 1;
			if ( isset( \IPS\Request::i()->setOrder ) )
			{
				foreach ( \IPS\Request::i()->setOrder as $set )
				{
					$set = preg_replace( '/^core_emoticon_group_/', '', $set );
					
					\IPS\Db::i()->update( 'core_emoticons', array( 'emo_set_position' => $i ), array( 'emo_set=?', $set ) );
					$i++;
				}
			}
			else
			{			
				$emoticons = $this->_getEmoticons( TRUE );
				
				foreach ( $emoticons as $group => $emos )
				{
					if( isset( \IPS\Request::i()->$group ) AND is_array( \IPS\Request::i()->$group ) )
					{
						foreach( \IPS\Request::i()->$group as $id )
						{
							\IPS\Db::i()->update( 'core_emoticons', array( 'emo_position' => $i, 'emo_set' => str_replace( 'core_emoticon_group_', '', $group ) ), array( 'id=?', $id ) );
							$i++;
						}
					}
				}
			}
			unset( \IPS\Data\Store::i()->emoticons );
			\IPS\Output::i()->json( 'OK' );
			return;
		}

		$emoticons = $this->_getEmoticons( FALSE );

		foreach ( \IPS\Request::i()->emo as $id => $data )
		{
			if ( isset( $emoticons[ $id ] ) )
			{
				if ( !$data['name'] )
				{
					continue;
				}

				if ( !isset( $data['order'] ) )
				{
					if ( !isset( $orders[ $data['set'] ] ) )
					{
						$orders[ $data['set'] ] = 0;
					}
					$data['order'] = ++$orders[ $data['set'] ];
				}
			
				if ( $emoticons[ $id ]['typed'] !== $data['name'] or $data['order'] != $emoticons[ $id ]['emo_position'] )
				{
					$save = array( 'typed' => $data['name'] );
					if ( isset( $data['order'] ) )
					{
						$save['emo_position'] = $data['order'];
					}
					if ( $set !== NULL )
					{
						$save['emo_set'] = str_replace( 'core_emoticon_group_', '', $data['set'] );
					}

					\IPS\Db::i()->update( 'core_emoticons', $save, array( 'id=?', $id ) );
				}
			}
		}

		unset( \IPS\Data\Store::i()->emoticons );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

        \IPS\Session::i()->log( 'acplog__emoticons_edited' );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=editor&controller=emoticons' ), 'saved' );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emoticons_delete' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		try
		{
			$emoticon = \IPS\Db::i()->select( '*', 'core_emoticons', array( 'id=?', \IPS\Request::i()->id ) )->first();
			if ( $emoticon['id'] )
			{
				\IPS\File::get( 'core_Emoticons', $emoticon['image'] )->delete();
				\IPS\File::get( 'core_Emoticons', $emoticon['image_2x'] )->delete();
			}

			\IPS\Db::i()->delete( 'core_emoticons', array( 'id=?', (int) \IPS\Request::i()->id ) );

			/* delete the group name, if there are no other emoticons in this group */
			$emoticons = \IPS\Db::i()->select( 'COUNT(*) as count', 'core_emoticons', array( 'emo_set =?', $emoticon['emo_set'] ) )->first();

			if ( $emoticons == 0 )
			{
				\IPS\Lang::deleteCustom( 'core', 'core_emoticon_group_'. $emoticon['emo_set'] );
			}

	        \IPS\Session::i()->log( 'acplog__emoticon_deleted' );

			unset( \IPS\Data\Store::i()->emoticons );

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();
		}
		catch ( \UnderflowException $e ) { }

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=editor&controller=emoticons' ), 'saved' );
	}
	
	/**
	 * Delete set
	 *
	 * @return	void
	 */
	public function deleteSet()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emoticons_delete' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		$set = preg_replace( '/^core_emoticon_group_/', '', \IPS\Request::i()->key );
		
		foreach ( \IPS\Db::i()->select( '*', 'core_emoticons', array( 'emo_set=?', $set ) ) as $emoticon )
		{
			try
			{
				\IPS\File::get( 'core_Emoticons', $emoticon['image'] )->delete();
				\IPS\File::get( 'core_Emoticons', $emoticon['image_2x'] )->delete();
			}
			catch ( \UnderflowException $e ) { }
		}
		
		\IPS\Db::i()->delete( 'core_emoticons', array( 'emo_set=?', $set ) );
		\IPS\Lang::deleteCustom( 'core', 'core_emoticon_group_'. $set );

		unset( \IPS\Data\Store::i()->emoticons );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=editor&controller=emoticons' ), 'saved' );
	}

	/**
	 * Edit group title
	 *
	 * @return	void
	 */
	protected function editTitle()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'emoticons_edit' );

		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Translatable( 'emoticons_add_newgroup', NULL, FALSE, array( 'app' => 'core', 'key' => \IPS\Request::i()->key ), NULL, NULL, NULL, 'emoticons_add_newgroup' ) );
		
		if ( $values = $form->values() )
		{
			\IPS\Lang::saveCustom( 'core', \IPS\Request::i()->key, $values['emoticons_add_newgroup'] );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=editor&controller=emoticons' ), 'saved' );
		}
		
		if( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output = $form;
			return;
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->block( 'emoticons_edit_groupname', $form, FALSE );
	}

	/**
	 * Get Emoticons
	 *
	 * @param	bool	$group	Group by their group?
	 * @return	array
	 */
	protected function _getEmoticons( $group=TRUE )
	{
		$emoticons = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_emoticons', NULL, 'emo_set_position,emo_position' ) as $row )
		{			
			if ( $group )
			{
				$emoticons[ 'core_emoticon_group_' . $row['emo_set'] ][ $row['id'] ] = $row;
			}
			else
			{
				$emoticons[ $row['id'] ] = $row;
			}
		}
		
		return $emoticons;
	}

	/**
	 * Returns the filename and extension for given emoticon path
	 *
	 * @param	string		$path		Emoticon path
	 * @return	array
	 */
	protected function _getRawFilename( $path )
	{
		$parts = explode( '/', $path );
		$filenamePart = array_pop( $parts );
		$filename = mb_substr( $filenamePart, 0, mb_strrpos( $filenamePart, '.' ) );

		return $filename;
	}
}