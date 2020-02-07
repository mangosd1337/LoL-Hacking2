<?php
/**
 * @brief		Admin CP Member Form
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		15 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\MemberForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Member Form
 */
class _Notifications
{
	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member				$member	Existing Member
	 * @return	void
	 */
	public function process( &$form, $member )
	{
		$form->add( new \IPS\Helpers\Form\YesNo( 'allow_admin_mails', $member->allow_admin_mails ) );

		$_autoTrack	= array();
		if( $member->auto_follow['content'] )
		{
			$_autoTrack[]	= 'content';
		}
		if( $member->auto_follow['comments'] )
		{
			$_autoTrack[]	= 'comments';
		}
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'auto_track', $_autoTrack, FALSE, array( 'options' => array( 'content' => 'auto_track_content', 'comments' => 'auto_track_comments' ), 'multiple' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Radio( 'auto_track_type', ( $member->auto_follow['method'] and in_array( $member->auto_follow['method'], array( 'immediate', 'daily', 'weekly' ) ) ) ? $member->auto_follow['method'] : 'immediate', FALSE, array( 'options' => array(
			//'none'		=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_none'),
			'immediate'	=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_immediate'),
			//'offline'	=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_offline'),
			'daily'		=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_daily'),
			'weekly'	=> \IPS\Member::loggedIn()->language()->addToStack('follow_type_weekly')
		) ), NULL, NULL, NULL, 'auto_track_type' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'show_pm_popup', $member->members_bitoptions['show_pm_popup'] ) );
		
		$form->addMatrix( 'notifications', \IPS\Notification::buildMatrix( $member ) );
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member			$member	The member
	 * @return	void
	 */
	public function save( $values, &$member )
	{
		$member->allow_admin_mails = (int) $values['allow_admin_mails'];
		$member->auto_track = json_encode( array(
			'content'	=> ( is_array( $values['auto_track'] ) AND in_array( 'content', $values['auto_track'] ) ) ? 1 : 0,
			'comments'	=> ( is_array( $values['auto_track'] ) AND in_array( 'comments', $values['auto_track'] ) ) ? 1 : 0,
			'method'	=> $values['auto_track_type']
		)	);
		$member->members_bitoptions['show_pm_popup'] = $values['show_pm_popup'];
		
		\IPS\Notification::saveMatrix( $member, $values );
	}
}