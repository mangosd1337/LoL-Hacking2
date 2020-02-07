<?php
/**
 * @brief		View File Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		10 Oct 2013
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
 * View File Controller
 */
class _view extends \IPS\Content\Controller
{
	/**
	 * @brief Master chat server URL
	 */
	const MASTERSERVER	= "http://chatservice.ipsdns.com/";

	/**
	 * Init
	 *
	 * @return	void
	 */
	public function execute()
	{
		/* Check that the license allows access, etc. */
		$licenseData = \IPS\IPS::licenseKey();

		if( !$licenseData )
		{
			\IPS\Output::i()->error( 'ipschat_no_license', '4H268/1', 500, 'ipschat_no_license_admin' );
		}

		if( !isset( $licenseData['chat_limit'] ) OR $licenseData['chat_limit'] < 1 )
		{
			\IPS\Output::i()->error( 'ipschat_no_license', '4H269/1', 500, 'ipschat_no_license_access' );
		}

		/* Group access check */
		if( !\IPS\Member::loggedIn()->group['chat_access'] )
		{
			\IPS\Output::i()->error( 'ipschat_no_group_access', '2H269/2', 403, '' );
		}

		/* Is this a guest that was blocked? */
		if( !\IPS\Member::loggedIn()->member_id AND isset( \IPS\Request::i()->cookie['chat_blocked'] ) AND \IPS\Request::i()->cookie['chat_blocked'] )
		{
			\IPS\Output::i()->error( 'ipschat_no_access', '2H269/3', 403, '' );
		}

		/* Or a member? */
		if( \IPS\Member::loggedIn()->chat_banned )
		{
			\IPS\Output::i()->error( 'ipschat_no_access', '2H269/5', 403, '' );
		}

		/* Spiders also do not have access */
		if( \IPS\Session\Front::i()->userAgent->spider )
		{
			\IPS\Output::i()->error( 'ipschat_no_access', '2H269/4', 403, '' );
		}

		/* Figure out if chat is online due to time restrictions */
		if ( $offlineMessage = \IPS\Application::load('chat')->offlineMessage() )
		{
			\IPS\Output::i()->error( $offlineMessage, '1H269/H', 403, 'chat_offline_admin' );
		}

		parent::execute();
	}
	
	/**
	 * View Chat
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Initiate output */
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'jquery/jquery.rangyinputs.js', 'core', 'interface' ) );
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'buzz/buzz.min.js', 'core', 'interface' ) );
		\IPS\Output::i()->jsFiles	= array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_chat.js', 'chat', 'front' ) );
		\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'chat.css', 'chat', 'front' ) );

		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'chat_responsive.css', 'chat', 'front' ) );
		}

		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( 'chat_title' );

		/* Check rules */
		if( \IPS\Settings::i()->ipschat_enable_rules )
		{
			$proceed = FALSE;

			$form = new \IPS\Helpers\Form( 'chat_rules', 'accept_rules' );
			$form->class = 'ipsForm_vertical';
			$form->hiddenValues['proceed'] = TRUE;

			if( $values = $form->values() )
			{
				if( $values['proceed'] )
				{
					$proceed = TRUE;
				}
			}

			/* If we haven't agreed yet, show the form */
			if( !$proceed )
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->acceptRules( $form );
				return;
			}
		}

		/* Initiate our options */
		$options = array(
			'roomId'		=> 0,
			'userId'		=> 0,
			'userName'		=> $this->cleanUsername( \IPS\Member::loggedIn()->member_id ? \IPS\Member::loggedIn()->name : \IPS\Member::loggedIn()->language()->get('guest') ),
			'accessKey'		=> '',
			'serverHost'	=> '',
			'serverPath'	=> '',
		);

		/* Call master to retrieve info */
		try
		{
			$result = \IPS\Http\Url::external( self::MASTERSERVER . "gateway31.php?api_key=" . \IPS\Settings::i()->ipb_reg_number . "&user={$options['userName']}&level=" . \IPS\Member::loggedIn()->group['chat_moderate'] )->request()->get();
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Output::i()->error( 'generic_chatconnect_error', '4H268/2', 500, 'connect_gw_error_0' );
		}
		$results	= explode( ',', $result );
		
		
		/* Error? */
		if( $results[0] == 0 )
		{
			switch( $results[1] )
			{
				// No license key
				case 1:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/7', 500, 'connect_gw_error_1' );
				break;
				
				// No username
				case 2:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/8', 500, 'connect_gw_error_2' );
				break;
				
				// MySQL error
				case 3:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H268/3', 500, 'connect_gw_error_3' );
				break;

				// Invalid room ID
				case 5:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/A', 500, 'connect_gw_error_5' );
				break;

				// Chat room full
				case 6:
					$key = \IPS\IPS::licenseKey();
					\IPS\Output::i()->error( 'chat_room_full', ( $key['chat_limit'] ? '1' : '4' ) . 'H269/B', 403, $key['chat_limit'] ? 'license_no_chat' : NULL );
				break;
				
				// The chat room is on a server which is no longer online
				case 7:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/M', 500, 'connect_gw_error_7' );
				break;
				
				// The chat room has no hostname associated with the server it is on; this would be a misconfiguration issue on the chat master server
				case 8:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H268/4', 500, 'connect_gw_error_8' );
				break;
				
				// No online chat servers could be found
				case 10:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H268/5', 500, 'connect_gw_error_10' );
				break;
				
				// Other
				default:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/6', 500 );
				break;
			}
		}

		/* Set the options we just got from the master chat server */
		$options['roomId']	= intval($results[3]);
		$options['serverHost']	= $results[1];
		$options['serverPath']	= $results[2];

		/* If this is a guest, adjust the URL to send an identifier */
		$guestFlag	= ( !\IPS\Member::loggedIn()->member_id and isset( \IPS\Request::i()->cookie['chat_user_id'] ) ) ? "&userid=" . intval( \IPS\Request::i()->cookie['chat_user_id'] ) : '';

		/* Now join the actual chat room */
		try
		{
			$result		= \IPS\Http\Url::external( "http://{$options['serverHost']}{$options['serverPath']}/join31.php?api_key=" . \IPS\Settings::i()->ipb_reg_number . "&user={$options['userName']}&level=" . \IPS\Member::loggedIn()->group['chat_moderate'] . "&room={$options['roomId']}&forumid=" . \IPS\Member::loggedIn()->member_id . "{$guestFlag}&forumgroup=" . \IPS\Member::loggedIn()->member_group_id )->request()->get();
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			\IPS\Output::i()->error( 'generic_chatconnect_error', '4H269/N', 500, 'connect_gw_error_0' );
		}
		$results	= explode( ',', $result );

		/* Error? */
		if( $results[0] == 0 )
		{
			switch( $results[1] )
			{
				// No license key
				case 1:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/C', 500, 'connect_gw_error_1' );
				break;
				
				// No username
				case 2:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/D', 500, 'connect_gw_error_2' );
				break;
				
				// No room ID
				case 3:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/E', 500, 'connect_gw_error_3' );
				break;
				
				// MySQL error
				case 4:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/E', 500, 'connect_gw_error_4' );
				break;
				
				// Could not create room
				case 5:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/F', 500, 'connect_gw_error_5' );
				break;
				
				// License key mismatch
				case 6:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H269/G', 500, 'connect_gw_error_6' );
				break;

				// Chat room full
				case 7:
					$key = \IPS\IPS::licenseKey();
					\IPS\Output::i()->error( 'chat_room_full', ( $key['chat_limit'] ? '1' : '4' ) . 'H269/H', 500, $key['chat_limit'] ? 'license_no_chat' : NULL );
				break;
				
				// Other
				default:
					\IPS\Output::i()->error( 'generic_chatconnect_error', '5H268/6', 500, 'connect_gw_error_0' );
				break;
			}
		}
		
		/* Remember the important parts of this response */
		$options['userId']		= $results[1];
		$options['accessKey']	= $results[2];

		/* If this is a guest, set the chat user ID cookie */
		if( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Request::i()->setCookie( 'chat_user_id', $options['userId'], NULL, FALSE );
		}

		/* Fetch the online users list and build the map */
		$chatters	= array();

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

					$chatters[ $username ]		= array(
													'user_id'		=> $_thisRecord[0],
													'username'		=> htmlspecialchars( $this->uncleanUsername( $username ), ENT_QUOTES | \IPS\HTMLENTITIES, 'UTF-8', FALSE ),
													'formattedUsername'	=> \IPS\Member\Group::load( $member->member_group_id )->formatName( $member->name ),
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

		ksort($chatters);

		$forumIdMap		= array();

		foreach( $chatters as $chatter )
		{
			$forumIdMap[ $chatter['user_id'] ]		= $chatter;
		}

		/* Update the chatters list */
		if( count( $chatters ) )
		{
			\IPS\Data\Store::i()->chatters = $forumIdMap;
		}

		/* Get badwords list */
		$badwords = iterator_to_array( \IPS\Db::i()->select( 'm_exact, swop, type', 'core_profanity_filters' )->setKeyField( 'type' ) );

		/* Get groups */
		$groups		= array();

		foreach( \IPS\Member\Group::groups() as $group )
		{
			$groups[ $group->g_id ] = array( 'prefix' => $group->prefix, 'suffix' => $group->suffix, 'bypassBadwords' => $group->g_bypass_badwords );
		}
		
		/* Are we ignoring any chats? */
		if( \IPS\Member::loggedIn()->member_id )
		{
			$ignored = iterator_to_array( \IPS\Db::i()->select( 'ignore_ignore_id, ignore_id', 'core_ignored_users', array( 'ignore_owner_id=? and ignore_chats=1', \IPS\Member::loggedIn()->member_id ) )->setKeyField( 'ignore_ignore_id' )->setValueField( 'ignore_id' ) );
		}
		else
		{
			$ignored = array();
		}

		/* Emoticons */
		$emoticons = array();
		foreach ( \IPS\Db::i()->select( '*', 'core_emoticons', NULL, 'emo_set,emo_position' ) as $row )
		{
			$emoticons[ $row['typed'] ] = array( 'image' => (string) \IPS\File::get( 'core_Emoticons', $row['image'] )->url, 'width' => $row['width'], 'height' => $row['height'] );

			if( $row['image_2x'] )
			{
				$emoticons[ $row['typed'] ]['image_2x'] = (string) \IPS\File::get( 'core_Emoticons', $row['image_2x'] )->url . ' 2x';
			}
		}
		
		/* We store the chat options in our session in case we choose to leave the room */
		$_SESSION['chatOptions']	= $options;

		/* Display */
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'view' )->chat( $forumIdMap );
		\IPS\Output::i()->endBodyCode .= \IPS\Theme::i()->getTemplate( 'view' )->chatvars( $options, $forumIdMap, $groups, $badwords, $ignored, $emoticons );
	}

	/**
	 * Leave Chat
	 *
	 * @return	void
	 */
	protected function leave()
	{
		/* Check secure key */
		\IPS\Session::i()->csrfCheck();

		/* Call server */
		if( isset( $_SESSION['chatOptions'] ) )
		{
			$options = $_SESSION['chatOptions'];

			\IPS\Http\Url::external( "http://{$options['serverHost']}{$options['serverPath']}/leave.php?room={$options['roomId']}&user={$options['userId']}&access_key={$options['accessKey']}" )->request()->get();

			unset( $_SESSION['chatOptions'] );
		}

		/* Remove us from chatters list */
		$chatters = ( isset( \IPS\Data\Store::i()->chatters ) ) ? \IPS\Data\Store::i()->chatters : array();

		foreach( $chatters as $uid => $chatter )
		{
			if( $chatter['forumUserID'] == \IPS\Member::loggedIn()->member_id )
			{
				unset( $chatters[ $uid ] );
			}
		}

		\IPS\Data\Store::i()->chatters = $chatters;

		// If this is a popup, close it, otherwise send us back to the suite index
		if( \IPS\Request::i()->_popup == 1 )
		{
			\IPS\Output::i()->output = "<script type='text/javascript'>window.close();</script>";
		}
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "_leaveChat=1" ), 'leave_chat_room' );
		}
	}

	/**
	 * Clean username for chat
	 *
	 * @param	string		Username
	 * @return	string
	 */
	protected function cleanUsername( $username )
	{
		$username		= str_replace( "\r", '', $username );
		$username		= str_replace( "\n", '__N__', $username );
		$username		= str_replace( ",", '__C__', $username );
		$username		= str_replace( "=", '__E__', $username );
		$username		= str_replace( "+", '__PS__', $username );
		$username		= str_replace( "&", '__A__', $username );
		$username		= str_replace( "%", '__P__', $username );
		$username		= urlencode($username);
		
		return $username;
	}
	
	/**
	 * Unclean username for chat
	 *
	 * @param	string		Username
	 * @return	string
	 */
	protected function uncleanUsername( $username )
	{
		$username =			str_replace( "__PS__", "+", $username );
		$username = 		str_replace( "__E__", "=", $username );
		$username =			str_replace( "__P__", "%", $username );
		$username =			str_replace( "__A__", "&", $username );
		$username =			str_replace( "__C__", ",", $username );
		$username =			str_replace( "__N__", "\n", $username );
		
		return $username;
	}
}