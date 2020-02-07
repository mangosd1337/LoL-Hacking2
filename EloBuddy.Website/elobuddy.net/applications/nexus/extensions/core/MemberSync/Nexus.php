<?php
/**
 * @brief		Member Sync
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		31 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\extensions\core\MemberSync;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Member Sync
 */
class _Nexus
{	
	/**
	 * Account Created
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	void
	 */
	public function onCreateAccount( $member )
	{
		if ( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'front' and isset( \IPS\Request::i()->cookie['referred_by'] ) )
		{
			$referredBy = \IPS\Member::load( \IPS\Request::i()->cookie['referred_by'] );
			if ( $referredBy->member_id )
			{
				\IPS\Db::i()->insert( 'nexus_referrals', array(
					'member_id'		=> $member->member_id,
					'referred_by'	=> \IPS\Request::i()->cookie['referred_by'],
					'amount'		=> '',
				) );
			}
		}
		
		if ( $member->email )
		{
			\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_member' => $member->member_id ), array( 'r_email=? AND r_member=0', $member->email ) );
			\IPS\Db::i()->update( 'nexus_support_replies', array( 'reply_member' => $member->member_id ), array( 'reply_email=? AND reply_member=0', $member->email ) );
		}
	}
	
	/**
	 * Member has logged on
	 *
	 * @param	\IPS\Member	$member		Member that logged in
	 * @param	\IPS\Http\Url	$redirectUrl	The URL to send the user back to
	 * @return	void
	 */
	public function onLogin( $member, $returnUrl )
	{
		if ( isset( \IPS\Request::i()->cookie['cm_reg'] ) and \IPS\Request::i()->cookie['cm_reg'] )
		{			
			try
			{
				$invoice = \IPS\nexus\Invoice::load( \IPS\Request::i()->cookie['cm_reg'] );
				
				\IPS\Request::i()->setCookie( 'cm_reg', 0 );
				
				if ( !$invoice->member->member_id )
				{
					$invoice->member = $member;
					$invoice->save();
				}
				
				if ( $invoice->member->member_id === $member->member_id )
				{
					\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
				}
			}
			catch ( \Exception $e )
			{
				\IPS\Request::i()->setCookie( 'cm_reg', 0 );
			}
		}
	}
	
	/**
	 * Member is merged with another member
	 *
	 * @param	\IPS\Member	$member		Member being kept
	 * @param	\IPS\Member	$member2	Member being removed
	 * @return	void
	 */
	public function onMerge( $member, $member2 )
	{
		\IPS\Db::i()->update( 'nexus_customer_addresses', array( 'member' => $member->member_id ), array( 'member=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_customer_cards', array( 'card_member' => $member->member_id ), array( 'card_member=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_customer_history', array( 'log_member' => $member->member_id ), array( 'log_member=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_invoices', array( 'i_member' => $member->member_id ), array( 'i_member=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_member' => $member->member_id ), array( 'ps_member=?', $member2->member_id ) );		
		\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_pay_to' => $member->member_id ), array( 'ps_pay_to=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_referrals', array( 'member_id' => $member->member_id ), array( 'member_id=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_referrals', array( 'referred_by' => $member->member_id ), array( 'referred_by=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_transactions', array( 't_member' => $member->member_id ), array( 't_member=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_alternate_contacts', array( 'main_id' => $member->member_id ), array( 'main_id=?', $member2->member_id ) );
		\IPS\Db::i()->update( 'nexus_alternate_contacts', array( 'alt_id' => $member->member_id ), array( 'alt_id=?', $member2->member_id ) );
	}
	
	/**
	 * Member is deleted
	 *
	 * @param	\IPS\Member	$member	The member
	 * @return	void
	 */
	public function onDelete( $member )
	{
		\IPS\Db::i()->delete( 'nexus_customer_addresses', array( 'member=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'nexus_customer_cards', array( 'card_member=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'nexus_customer_history', array( 'log_member=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'nexus_customers', array( 'member_id=?', $member->member_id ) );
		\IPS\Db::i()->delete( 'nexus_referrals', array( 'referred_by=?', $member->member_id ) ); // Don't delete the record for the referrer if this is the referree as that's still relevant for the referree's account
		\IPS\Db::i()->delete( 'nexus_alternate_contacts', array( 'main_id=? OR alt_id =?', $member->member_id, $member->member_id ) );
		
		foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_member=?', $member->member_id ) ), 'IPS\nexus\Purchase' ) as $purchase )
		{
			$purchase->delete();
		}
		
		\IPS\Db::i()->update( 'nexus_purchases', array( 'ps_pay_to' => 0 ), array( 'ps_pay_to=?', $member->member_id ) );
	}
	
	/**
	 * Email address is changed
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param 	string		$new	New email address
	 * @param 	string		$old	Old email address
	 * @return	void
	 */
	public function onEmailChange( $member, $new, $old )
	{
		\IPS\nexus\Customer::load( $member->member_id )->log( 'info', array( 'email' => $old ) );
	}
	
	/**
	 * Password is changed
	 *
	 * @param	\IPS\Member	$member	The member
	 * @param 	string		$new	New password
	 * @return	void
	 */
	public function onPassChange( $member, $new )
	{
		\IPS\nexus\Customer::load( $member->member_id )->log( 'info', array( 'password' => '' ) );
	}


	/**
	 * Member account has been updated
	 *
	 * @param	$member		\IPS\Member	Member updating profile
	 * @param	$changes	array		The changes
	 * @return	void
	 */
	public function onProfileUpdate( $member, $changes )
	{
		unset ( \IPS\Data\Store::i()->supportStaff );
	}
}
