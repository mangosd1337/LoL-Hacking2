<?php
/**
 * @brief		Warning Reason Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		23 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Warnings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Warning Reason Node
 */
class _Reason extends \IPS\Node\Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'core_members_warn_reasons';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'wr_';
	
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static $databaseColumnId = 'id';
	
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'order';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'warn_reasons';
	
	/**
	 * @brief	[Node] Show forms modally?
	 */
	public static $modalForms = TRUE;
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static $restrictions = array(
		'app'		=> 'core',
		'module'	=> 'membersettings',
		'prefix'	=> 'reasons_',
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'core_warn_reason_';

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->add( new \IPS\Helpers\Form\Translatable( 'wr_name', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ? "core_warn_reason_{$this->id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Number( 'wr_points', $this->id ? $this->points : 0, TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'wr_points_override', $this->id ? $this->points_override : TRUE ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'wr_remove', $this->id ? array( $this->remove, $this->remove_unit ) : NULL, FALSE, array(
			'getHtml'	=> function( $element )
			{
				return \IPS\Theme::i()->getTemplate( 'members' )->warningTime( $element->name, $element->value, 'after', 'never' );
			}
			, 'unlimited' => -1, 'unlimitedLang' => 'never'
		) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'wr_remove_override', $this->id ? $this->remove_override : TRUE ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if( isset( $values['wr_remove'] ) )
		{
			if( isset( $values['wr_remove'][3] ) or $values['wr_remove'][0] < 1 )
			{
				$values['remove'] = -1;
			}
			else
			{
				$values['remove']			= (int) $values['wr_remove'][0];
				$values['remove_unit']		= $values['wr_remove'][1];
			}
			unset( $values['wr_remove'] );
		}

		if( !$this->id )
		{
			$values['order']			= 0;
			$this->save();			
		}

		if( isset( $values['wr_name'] ) )
		{
			\IPS\Lang::saveCustom( 'core', "core_warn_reason_{$this->id}", $values['wr_name'] );
			unset( $values['wr_name'] );
		}

		foreach( $values as $k => $v )
		{
			if( mb_substr( $k, 0, 3 ) === 'wr_' )
			{
				unset( $values[ $k ] );
				$values[ mb_substr( $k, 3 ) ] = $v;
			}
		}

		return $values;
	}

	/**
	 * [Node] Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( $values )
	{
		
	}
	
	/**
	 * [Node] Does the currently logged in user have permission to add a child node to this node?
	 *
	 * @return	bool
	 */
	public function canAdd()
	{
		return FALSE;
	}
}