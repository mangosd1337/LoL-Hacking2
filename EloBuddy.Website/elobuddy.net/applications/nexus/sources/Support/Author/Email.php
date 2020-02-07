<?php
/**
 * @brief		Support Author Model - Email
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		15 Apr 2014
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
 * Support Author Model - Email
 */
class _Email
{
	/**
	 * @brief	Email Address
	 */
	protected $email;
	
	/**
	 * Constructor
	 *
	 * @param	string	$email	Email address
	 * @return	void
	 */
	public function __construct( $email )
	{
		$this->email = $email;
	}
	
	/**
	 * Get name
	 *
	 * @return	string
	 */
	public function name()
	{
		return $this->email;
	}
		
	/**
	 * Get photo
	 *
	 * @return	string
	 */
	public function photo()
	{
		if ( \IPS\Settings::i()->allow_gravatars )
		{
			return \IPS\Http\Url::external( "https://secure.gravatar.com/avatar/" . md5( trim( mb_strtolower( $this->email ) ) ) )->setQueryString( 'd', (string) \IPS\Theme::i()->resource( 'default_photo.png', 'core', 'global' ) )->makeSafeForAcp( TRUE );
		}
		return \IPS\Theme::i()->resource( 'default_photo.png', 'core', 'global' );
	}
	
	/**
	 * Get email
	 *
	 * @return	string
	 */
	public function email()
	{
		return $this->email;
	}
	
	/**
	 * Get url
	 *
	 * @return	\IPS\Http\Url
	 */
	public function url()
	{
		return NULL;
	}
	
	/**
	 * Get meta data
	 *
	 * @return	array
	 */
	public function meta()
	{		
		return array( );
	}
	
	/**
	 * Get latest invoices
	 *
	 * @return	\IPS\Patterns\ActiveRecordIterator|NULL
	 */
	public function invoices( $limit=10 )
	{		
		return NULL;
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
		$where = array( array( 'r_email=?', $this->email ) );
		if ( $exclude )
		{
			$where[] = array( 'r_id<>?', $exclude->id );
		}
				
		return \IPS\nexus\Support\Request::getItemsWithPermission( $where, $order, $limit, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
	}
}