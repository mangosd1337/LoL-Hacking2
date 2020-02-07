<?php
/**
 * @brief		Cover Photo Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\CoverPhoto;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Cover Photo Controller
 */
abstract class _Controller extends \IPS\Dispatcher\Controller
{	
	/**
	 * Upload Cover Photo
	 *
	 * @return	void
	 */
	protected function coverPhotoUpload()
	{	
		$photo = $this->_coverPhotoGet();
		if ( !$photo->editable )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2S216/1', 403, '' );
		}

		$form = new \IPS\Helpers\Form( 'coverPhoto' );
		$form->class = 'ipsForm_vertical ipsForm_noLabels';
		$form->add( new \IPS\Helpers\Form\Upload( 'cover_photo', NULL, TRUE, array( 'image' => TRUE, 'minimize' => FALSE, 'maxFileSize' => ( $photo->maxSize and $photo->maxSize != -1 ) ? $photo->maxSize / 1024 : NULL, 'storageExtension' => $this->_coverPhotoStorageExtension() ) ) );
		if ( $values = $form->values() )
		{
			try
			{
				$photo->delete();
			}
			catch ( \Exception $e ) { }
			
			$returnURL = ( isset( $_SERVER['HTTP_REFERER'] ) AND $_SERVER['HTTP_REFERER'] ) ? new \IPS\Http\Url( $_SERVER['HTTP_REFERER'] ) : \IPS\Request::i()->url()->stripQueryString( 'do' );
			$this->_coverPhotoSet( new \IPS\Helpers\CoverPhoto( $values['cover_photo'], 0 ) );
			\IPS\Output::i()->redirect( $returnURL->setQueryString( array( '_position' => 1 ) ) );
		}
		
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Remove Cover Photo
	 *
	 * @return	void
	 */
	protected function coverPhotoRemove()
	{
		\IPS\Session::i()->csrfCheck();
		$photo = $this->_coverPhotoGet();
		if ( !$photo->editable )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2S216/2', 403, '' );
		}
		
		try
		{
			$photo->delete();
		}
		catch ( \Exception $e ) { }
		
		$this->_coverPhotoSet( new \IPS\Helpers\CoverPhoto( NULL, 0 ) );
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( $_SERVER['HTTP_REFERER'] );
		}
	}
	
	/**
	 * Reposition Cover Photo
	 *
	 * @return	void
	 */
	protected function coverPhotoPosition()
	{
		\IPS\Session::i()->csrfCheck();
		$photo = $this->_coverPhotoGet();
		if ( !$photo->editable )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2S216/3', 403, '' );
		}
		
		$photo->offset = \IPS\Request::i()->offset;
		$this->_coverPhotoSet( $photo );
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		else
		{
			\IPS\Output::i()->redirect( $_SERVER['HTTP_REFERER'] );
		}
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	abstract protected function _coverPhotoStorageExtension();
	
	/**
	 * Set Cover Photo
	 *
	 * @param	\IPS\Helpers\CoverPhoto	$photo	New Photo
	 * @return	void
	 */
	abstract protected function _coverPhotoSet( \IPS\Helpers\CoverPhoto $photo );
	
	/**
	 * Get Cover Photo
	 *
	 * @return	\IPS\Helpers\CoverPhoto
	 */
	abstract protected function _coverPhotoGet();
}