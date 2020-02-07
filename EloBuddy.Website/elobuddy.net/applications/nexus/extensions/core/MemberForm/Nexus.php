<?php
/**
 * @brief		Admin CP Member Form
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		18 Sep 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\MemberForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Member Form
 */
class _Nexus
{
	/**
	 * Action Buttons
	 *
	 * @param	\IPS\Member	$member	The Member
	 * @return	array
	 */
	public function actionButtons( $member )
	{
		return array(
			'id'	=> array(
				'title'		=> 'view_customer_data',
				'icon'		=> 'dollar',
				'link'		=> \IPS\Http\Url::internal( "app=nexus&module=customers&controller=view&id={$member->member_id}" ),
				'class'		=> ''
			)
		);
	}

	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member				$member	Existing Member
	 * @return	void
	 */
	public function process( &$form, $member )
	{		
		
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
		
	}
}