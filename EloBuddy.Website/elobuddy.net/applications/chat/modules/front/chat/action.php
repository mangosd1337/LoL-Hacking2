<?php
/**
 * @brief		Action Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		23 Mar 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\chat\modules\front\chat;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Action Controller
 */
class _action extends \IPS\Content\Controller
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function execute()
	{
		parent::execute();
	}

	/**
	 * Add a user to the chat room
	 *
	 * @return	void
	 * @note	Forum (local) user ID is in \IPS\Request::i()->id and Chat user ID is in \IPS\Request::i()->user
	 */
	protected function addUser()
	{
		\IPS\Session::i()->csrfCheck();

		$member = \IPS\Member::load( intval( \IPS\Request::i()->id ) );

		$map	= array( 
				'user_id'			=> intval( \IPS\Request::i()->user ),
				'forumUserID'		=> intval( \IPS\Request::i()->id ),
				'prefix'			=> $member->group['prefix'],
				'suffix'			=> $member->group['suffix'],
				'photo'				=> $member->photo,
				'username'			=> $member->member_id ? $member->name : $member->loggedIn()->language()->get('guest'),
				'formattedUsername'	=> \IPS\Member\Group::load( $member->member_group_id )->formatName( $member->name ),
				'canBeIgnored'		=> $member->member_id ? !$member->group['gbw_cannot_be_ignored'] : 1,
				'canBypassBadwords'	=> $member->group['g_bypass_badwords'],
				'groupID'			=> $member->member_group_id,
				'profileUrl'		=> $member->url(),
				'canModerate'		=> $member->group['chat_moderate'],
				'seo_name'			=> $member->members_seo_name,
				'last_update'		=> time(),
			);

		/* Update the chatters list */
		$chatters = ( isset( \IPS\Data\Store::i()->chatters ) ) ? \IPS\Data\Store::i()->chatters : array();
		$chatters[ $map['user_id'] ] = $map;

		\IPS\Data\Store::i()->chatters = $chatters;

		/* Output */
		\IPS\Output::i()->json( array( 
			'html' => \IPS\Theme::i()->getTemplate( 'view' )->userRow( $map ),
			'user' => $map
		) );
	}

	/**
	 * Load user info
	 *
	 * @return	void
	 * @note	Forum (local) user ID is in \IPS\Request::i()->id
	 */
	protected function loadUserInfo()
	{
		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'view', 'chat' )->userInfo( \IPS\Member::load( intval( \IPS\Request::i()->id ) ) ), 200, 'text/html', \IPS\Output::i()->httpHeaders );
	}

	/**
	 * Ignore and unignore chats
	 *
	 * @return	void
	 * @note	Forum (local) user ID is in \IPS\Request::i()->id
	 */
	protected function blockUser()
	{
		\IPS\Session::i()->csrfCheck();

		/* Make sure there is an ID */
		if( !\IPS\Request::i()->id )
		{
			\IPS\Output::i()->json( 'OK' );
		}

		/* Make sure we can ignore this user */
		$user = \IPS\Member::load( \IPS\Request::i()->id );

		if( $user->group['gbw_cannot_be_ignored'] )
		{
			\IPS\Output::i()->error( 'ipschat_no_ignore', '3H269/K', 403, '' );
		}

		/* Update or insert the ignore */
		try
		{
			$existing = \IPS\Db::i()->select( '*', 'core_ignored_users', array( 'ignore_owner_id=? AND ignore_ignore_id=?', \IPS\Member::loggedIn()->member_id, \IPS\Request::i()->id ) )->first();

			\IPS\Db::i()->update( 'core_ignored_users', array( 'ignore_chats' => $existing['ignore_chats'] ? 0 : 1 ), array( 'ignore_id=?', $existing['ignore_id'] ) );
		}
		catch( \UnderflowException $e )
		{
			\IPS\Db::i()->insert( 'core_ignored_users', array(
				'ignore_owner_id'	=> \IPS\Member::loggedIn()->member_id,
				'ignore_ignore_id'	=> intval( \IPS\Request::i()->id ),
				'ignore_chats'		=> 1
			) );
		}

		\IPS\Output::i()->json( 'OK' );
	}

	/**
	 * Ban user
	 *
	 * @return	void
	 * @note	Forum (local) user ID is in \IPS\Request::i()->id
	 */
	protected function banUser()
	{
		\IPS\Session::i()->csrfCheck();
		
		/* Make sure there is an ID */
		if( !\IPS\Request::i()->id )
		{
			\IPS\Output::i()->json( 'OK' );
		}

		/* Make sure we can moderate */
		if( !\IPS\Member::loggedIn()->group['chat_moderate'] )
		{
			\IPS\Output::i()->error( 'ipschat_no_ban', '3H269/L', 403, '' );
		}

		/* Ban the user */
		$user = \IPS\Member::load( \IPS\Request::i()->id );
		$user->chat_banned = 1;
		$user->save();

		/* Remove us from chatters list */
		$chatters = ( isset( \IPS\Data\Store::i()->chatters ) ) ? \IPS\Data\Store::i()->chatters : array();

		foreach( $chatters as $uid => $chatter )
		{
			if( $chatter['forumUserID'] == $user->member_id )
			{
				unset( $chatters[ $uid ] );
			}
		}

		\IPS\Data\Store::i()->chatters = $chatters;

		\IPS\Output::i()->json( 'OK' );
	}

	/**
	 * Ping to keep us in chatters list
	 *
	 * @return	void
	 * @note	Forum (local) user ID is in \IPS\Request::i()->id
	 */
	protected function ping()
	{
		/* Still online? */
		if ( $offlineMessage = \IPS\Application::load('chat')->offlineMessage() )
		{
			\IPS\Output::i()->json( array( 'message' => $offlineMessage ), 500 );
		}
		
		/* Make sure there is an ID */
		if( !\IPS\Request::i()->id )
		{
			\IPS\Output::i()->json( 'OK' );
		}

		/* If the data store doesn't exist it was wiped, so recreate it */
		if( !isset( \IPS\Data\Store::i()->chatters ) )
		{
			$chatters	= array();
			$options = $_SESSION['chatOptions'];

			$result		= \IPS\Http\Url::external( "http://{$options['serverHost']}{$options['serverPath']}/list.php?room={$options['roomId']}&user={$options['userId']}&access_key={$options['accessKey']}" )->request()->get();

			if( $result )
			{
				$results	= explode( "~~||~~", $result );
				$status		= array_shift( $results );

				if( $status == 1 )
				{
					foreach( $results as $k => $v )
					{
						$_thisRecord	= explode( ',', $v );
						
						if( !$_thisRecord[0] )
						{
							continue;
						}

						$member		= \IPS\Member::load( intval( $_thisRecord[2] ) );
						$username	= str_replace( '~~#~~', ',', $_thisRecord[1] );

						$chatters[ $_thisRecord[0] ]	= array(
														'user_id'		=> $_thisRecord[0],
														'username'		=> htmlspecialchars( $username, ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ),
														'forumUserID'	=> $member->member_id,
														'canBeIgnored'	=> $member->member_id ? !$member->group['gbw_cannot_be_ignored'] : 1,
														'canBypassBadwords'	=> $member->group['g_bypass_badwords'],
														'groupID'		=> $member->member_group_id,
														'prefix'		=> str_replace( '"', '__DBQ__', $member->group['prefix'] ),
														'suffix'		=> str_replace( '"', '__DBQ__', $member->group['suffix'] ),
														'photo'			=> $member->photo,
														'profileUrl'	=> $member->url(),
														'canModerate'	=> $member->group['chat_moderate'],
														'seo_name'		=> $member->members_seo_name,
														'last_update'	=> time(),
													);
					}
				}
			}
		}
		else
		{
			$chatters = \IPS\Data\Store::i()->chatters;
		}

		/* Update us in chatters list - session will be auto-handled */
		if( isset( $chatters[ \IPS\Request::i()->id ] ) )
		{
			$chatters[ \IPS\Request::i()->id ]['last_update']	= time();

			\IPS\Data\Store::i()->chatters = $chatters;
		}

		/* Return a response */
		\IPS\Output::i()->json( 'OK' );
	}
}