<?php
/**
 * @brief		Support Author Model - Member
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		10 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Support\Author;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Author Model - Member
 */
class _Member
{
	/**
	 * @brief	Customer object
	 */
	protected $customer;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\nexus\Customer	$customer	Customer object
	 * @return	void
	 */
	public function __construct( \IPS\nexus\Customer $customer )
	{
		$this->customer = $customer;
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function name()
	{
		return $this->customer->cm_name;
	}
	
	/**
	 * Get email
	 *
	 * @return	string
	 */
	public function email()
	{
		return $this->customer->email;
	}
		
	/**
	 * Get photo
	 *
	 * @return	string
	 */
	public function photo()
	{
		return $this->customer->photo;
	}
	
	/**
	 * Get url
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		return $this->customer->acpUrl();
	}
	
	/**
	 * Get meta data
	 *
	 * @return	array
	 */
	public function meta()
	{		
		return array(
			$this->customer->email,
			\IPS\Member::loggedIn()->language()->addToStack( 'transaction_customer_since', FALSE, array( 'sprintf' => array( $this->customer->joined->localeDate() ) ) ),
			\IPS\Member::loggedIn()->language()->addToStack( 'transaction_spent', FALSE, array( 'sprintf' => array( $this->customer->totalSpent() ) ) ),
		);
	}
	
	/**
	 * Get nuber of notes
	 *
	 * @return	array
	 */
	public function noteCount()
	{		
		return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_notes', array( 'note_member=?', $this->customer->member_id ) )->first();
	}
	
	/**
	 * Get latest invoices
	 *
	 * @return	\IPS\Patterns\ActiveRecordIterator|NULL
	 */
	public function invoices( $limit=10 )
	{		
		return new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_invoices', array( 'i_member=?', $this->customer->member_id ), 'i_date DESC', $limit, NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS ), 'IPS\nexus\Invoice' );
	}
	
	/**
	 * Support Requests
	 *
	 * @param	int							$limit		Number to get
	 * @para,	\IPS\nexus\Support\Request	$exclude	A request to exclude
	 * @param	string						$order		Order clause
	 * @return	\IPS\Patterns\ActiveRecordIterator
	 */
	public function supportRequests( $limit, \IPS\nexus\Support\Request $exclude = NULL, $order='r_started DESC' )
	{
		$where = array( array( 'r_member=?', $this->customer->member_id ) );
		if ( $exclude )
		{
			$where[] = array( 'r_id<>?', $exclude->id );
		}
		
		return \IPS\nexus\Support\Request::getItemsWithPermission( $where, $order, $limit, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
	}
}