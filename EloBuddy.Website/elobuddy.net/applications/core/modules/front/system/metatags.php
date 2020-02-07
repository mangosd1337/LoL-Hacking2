<?php
/**
 * @brief		Live meta tag editor
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 Sept 2013
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
 * Live meta tag editor
 */
class _metatags extends \IPS\Dispatcher\Controller
{
	/**
	 * Redirect the request appropriately
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$this->_checkPermissions();

		$_SESSION['live_meta_tags']	= TRUE;

		\IPS\Output::i()->redirect( \IPS\Http\Url::external( \IPS\Settings::i()->base_url ) );
	}

	/**
	 * Save a meta tag
	 *
	 * @return	void
	 */
	protected function save()
	{
		/* Check permissions and CSRF */
		$this->_checkPermissions();

		\IPS\Session::i()->csrfCheck();

		/* Delete any existing database entries, as we are about to re-insert */
		\IPS\Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', \IPS\Request::i()->meta_url ) );
		\IPS\Db::i()->delete( 'core_seo_meta', array( 'meta_url=?', trim( \IPS\Request::i()->meta_url, '/' ) ) );

		/* Start save array */
		$save	= array(
			'meta_url'		=> \IPS\Request::i()->meta_url,
			'meta_title'	=> \IPS\Request::i()->meta_tag_title,
		);

		$_tags	= array();

		/* Store the new meta tags */
		if( isset( \IPS\Request::i()->meta_tag_name ) AND is_array( \IPS\Request::i()->meta_tag_name ) )
		{
			foreach( \IPS\Request::i()->meta_tag_name as $k => $v )
			{
				if( $v AND ( $v != 'other' OR !empty( \IPS\Request::i()->meta_tag_name_other[ $k ] ) ) AND !isset( $_tags[ $v != 'other' ? $v : \IPS\Request::i()->meta_tag_name_other[ $k ] ] ) )
				{
					$_tags[ ( $v != 'other' ) ? $v : \IPS\Request::i()->meta_tag_name_other[ $k ] ]	= \IPS\Request::i()->meta_tag_content[ $k ];
				}
			}
		}

		$save['meta_tags']	= json_encode( $_tags );

		\IPS\Db::i()->insert( 'core_seo_meta', $save );
		unset( \IPS\Data\Store::i()->metaTags );

		/* Send back to the page */
		if( \IPS\Request::i()->isAjax() )
		{
			return;
		}

		\IPS\Output::i()->redirect( \IPS\Http\Url::external( \IPS\Settings::i()->base_url . \IPS\Request::i()->url ) );
	}

	/**
	 * Stop editing meta tags
	 *
	 * @return	void
	 */
	protected function end()
	{
		$_SESSION['live_meta_tags']	= FALSE;

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( \IPS\Request::i()->url ) );
	}

	/**
	 * Check permissions to use the tool
	 *
	 * @return	void
	 */
	protected function _checkPermissions()
	{
		if( !\IPS\Member::loggedIn()->member_id OR !\IPS\Member::loggedIn()->isAdmin() )
		{
			\IPS\Output::i()->error( 'meta_editor_no_admin', '2C155/1', 403, '' );
		}

		if( !\IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'seo_manage' ) )
		{
			\IPS\Output::i()->error( 'meta_editor_no_acpperm', '3C155/2', 403, '' );
		}
	}
}