<?php
/**
 * @brief		Dashboard extension: Admin notes
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\Dashboard;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Dashboard extension: Admin notes
 */
class _AdminNotes
{
	/**
	 * Can the current user view this dashboard item?
	 *
	 * @return	bool
	 */
	public function canView()
	{
		return TRUE;
	}

	/**
	 * Return the block to show on the dashboard
	 *
	 * @return	string
	 */
	public function getBlock()
	{
		$form	= new \IPS\Helpers\Form( 'form', 'save', \IPS\Http\Url::internal( "app=core&module=overview&controller=dashboard&do=getBlock&appKey=core&blockKey=core_AdminNotes" )->csrf() );
		$form->add( new \IPS\Helpers\Form\TextArea( 'admin_notes', ( isset( \IPS\Settings::i()->acp_notes ) ) ? htmlspecialchars( \IPS\Settings::i()->acp_notes, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) : '' ) );

		if( $values = $form->values() )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $values['admin_notes'] ), array( 'conf_key=?', 'acp_notes' ) );
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => time() ), array( 'conf_key=?', 'acp_notes_updated' ) );
			unset( \IPS\Data\Store::i()->settings );

			if( \IPS\Request::i()->isAjax() )
			{
				return (string) \IPS\DateTime::ts( intval( \IPS\Settings::i()->acp_notes_updated ) );
			}
		}

		return $form->customTemplate( array( \IPS\Theme::i()->getTemplate( 'dashboard' ), 'adminnotes' ), ( isset( \IPS\Settings::i()->acp_notes_updated ) and \IPS\Settings::i()->acp_notes_updated ) ? (string) \IPS\DateTime::ts( intval( \IPS\Settings::i()->acp_notes_updated ) ) : '' );
	}
}