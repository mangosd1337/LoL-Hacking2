<?php
/**
 * @brief		Form builder helper to allow user-created "groups" of other users
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Form builder helper to allow user-created "groups" of other users
 */
class _SocialGroup extends Member
{
	/**
	 * @brief	Default Options
	 * @code
	 	$childDefaultOptions = array(
	 		'owner'			=> 1,		// \IPS\Member object or member ID who "owns" this group
	 		'multiple'	=> 1,	// Maximum number of members. NULL for any. Default is 1.
	 	);
	 * @endcode
	 */
	public $childDefaultOptions = array(
		'multiple'	=> NULL,
		'owner'		=> NULL,
	);

	/**
	 * @brief	Group ID, if already set
	 */
	public $groupId	= NULL;

	/**
	 * Constructor
	 * Sets that the field is required if there is a minimum length and vice-versa
	 *
	 * @see		\IPS\Helpers\Form\Abstract::__construct
	 * @return	void
	 */
	public function __construct()
	{
		/* Call parent constructor */
		call_user_func_array( 'parent::__construct', func_get_args() );

		/* If we are editing, the value will be a group ID we need to load */
		if( is_int( $this->value ) )
		{
			$this->groupId	= $this->value;

			$values = array();

			foreach( \IPS\Db::i()->select( '*', 'core_sys_social_group_members', array( 'group_id=?', $this->value ) )->setKeyField('member_id')->setValueField('member_id') as $k => $v )
			{
				$values[ $k ] = \IPS\Member::load( $v );
			}
			
			$this->value = $values;
		}

		/* Make sure we have an owner...fall back to logged in member */
		if( !$this->options['owner'] )
		{
			$this->options['owner']	= \IPS\Member::loggedIn();
		}
		else if( !$this->options['owner'] instanceof \IPS\Member AND is_int( $this->options['owner'] ) )
		{
			$this->options['owner']	= \IPS\Member::load( $this->options['owner'] );
		}
	}

	/**
	 * Save the social group
	 *
	 * @return	void
	 */
	public function saveValue()
	{
		/* Delete any existing entries */
		if( $this->groupId )
		{
			\IPS\Db::i()->delete( 'core_sys_social_group_members', array( 'group_id=?', $this->groupId ) );
		}
		else if( $this->value )
		{
			$this->groupId	= \IPS\Db::i()->insert( 'core_sys_social_groups', array( 'owner_id' => $this->options['owner']->member_id ) );
		}

		if( $this->value )
		{
			$inserts = array();
			foreach( $this->value as $member )
			{
				$inserts[] = array( 'group_id' => $this->groupId, 'member_id' => $member->member_id );
			}
			
			if( count( $inserts ) )
			{
				\IPS\Db::i()->insert( 'core_sys_social_group_members', $inserts );
			}
		}

		$this->value	= $this->groupId;
	}
}