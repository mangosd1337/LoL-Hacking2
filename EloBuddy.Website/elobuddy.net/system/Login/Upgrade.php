<?php
/**
 * @brief		Internal Login Handler
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		13 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Login;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrade Login Handler
 */
class _Upgrade extends \IPS\Login\Internal
{
	/**
	 * Authenticate
	 *
	 * @param	array	$values	Values from from
	 * @return	\IPS\Member
	 * @throws	\IPS\Login\Exception
	 */
	public function authenticate( $values )
	{
		/* Get member(s) */
		$members = array();
		
		$table = 'core_members';
		
		if ( \IPS\Db::i()->checkForTable( 'members' ) AND !\IPS\Db::i()->checkForTable( 'core_members' ) )
		{
			$table = 'members';
		}
		
		foreach( \IPS\Db::i()->select('*', $table, array( 'name=? or email=?', \IPS\Request::legacyEscape( $values['auth'] ), \IPS\Request::legacyEscape( $values['auth'] ) ) ) as $_member )
		{
			$members[] = \IPS\Member::constructFromData( $_member );
		}
				
		/* If we didn't match any, throw an exception */
		if ( empty( $members ) )
		{
			throw new \IPS\Login\Exception( \IPS\Member::loggedIn()->language()->addToStack('login_err_no_account', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $this->getLoginType( $this->authTypes ) ) ) ) ), \IPS\Login\Exception::NO_ACCOUNT );
		}
		
		/* Check the password for each possible account */
		foreach ( $members as $member )
		{
			if ( \IPS\Login::compareHashes( $member->members_pass_hash, $member->encryptedPassword( $values['password'] ) ) OR
				\IPS\Login::compareHashes( $member->members_pass_hash, $member->encryptedPassword( \IPS\Request::legacyEscape( $values['password'] ) ) ) )
			{
				/* Return */
				return $member;
			}
		}

		/* Still here? Throw a password incorrect exception */
		throw new \IPS\Login\Exception( 'login_err_bad_password', \IPS\Login\Exception::BAD_PASSWORD, NULL, $member );
	}
}