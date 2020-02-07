<?php
/**
 * @brief		logs
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Chat
 * @since		15 Mar 2015
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\chat\modules\admin\chat;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * logs
 */
class _logs extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'logs_manage' );
		parent::execute();
	}

	/**
	 * Show logs
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'chat_log_archive', \IPS\Http\Url::internal( 'app=chat&module=chat&controller=logs' ) );
		$table->langPrefix = 'chatlog_';

		/* Column stuff */
		$table->include = array( 'log_time', 'log_user', 'log_message' );
		$table->mainColumn = 'log_message';
		$table->widths = array( 'log_time' => '20', 'log_user' => '20', 'log_message' => '60' );

		/* Sort stuff */
		$table->sortBy = $table->sortBy ?: 'log_time';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		/* Search */
		$table->quickSearch = 'log_message';
		$table->advancedSearch = array(
			'log_user'		=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'log_time'		=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			);

		/* Formatters */
		$self = $this;

		$table->parsers = array(
			'log_time'			=> function( $val, $row )
			{
				$date	= \IPS\DateTime::ts( $val );

				return $date->localeDate() . ' ' . $date->localeTime( FALSE );
			},
			'log_user'	=> function( $val, $row ) use ( $self )
			{
				return $self->cleanMessage( $val );
			},
			'log_message'	=> function( $val, $row ) use ( $self )
			{
				$val = $self->cleanMessage( $val );

				if( $row['log_code'] == 2 )
				{
					$_extra	= explode( '_', $row['log_extra'] );

					if( $_extra[0] == 1 )
					{
						$val	= \IPS\Member::loggedIn()->language()->addToStack('chatlog__hasentered', FALSE, array( 'sprintf' => $row['log_user'] ) );
					}
					else
					{
						$val	= \IPS\Member::loggedIn()->language()->addToStack('chatlog__hasleft', FALSE, array( 'sprintf' => $row['log_user'] ) );
					}
				}
				else if( $row['log_code'] == 5 )
				{
					$val	= \IPS\Member::loggedIn()->language()->addToStack('chatlog__kicked', FALSE, array( 'sprintf' => $val ) );
				}
				
				if( mb_strpos( $val, '/me' ) === 0 )
				{
					$val	= mb_substr( $val, 4 );
				}
				
				if( mb_strpos( $row['log_extra'], 'private=' ) === 0 )
				{
					$_user	= mb_substr( $row['log_extra'], mb_strrpos( $row['log_extra'], '=' ) + 1 );
					$_uid	= $_user;

					$_user	= \IPS\Member::load( $_user );
					
					$val	= \IPS\Member::loggedIn()->language()->addToStack('chatlog__private', FALSE, array( 'sprintf' => array( $_user->name, $val ) ) );
				}

				return $val;
			}
		);

		/* Root buttons */
		\IPS\Output::i()->sidebar['actions']['settings'] = array(
			'title'		=> 'prunesettings',
			'icon'		=> 'cog',
			'link'		=> \IPS\Http\Url::internal( 'app=chat&module=chat&controller=logs&do=settings' ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('prunesettings') )
		);

		/* Display */
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('ipschat_log_archive');
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}

	/**
	 * Clean "special" characters
	 *
	 * @param	string		String to clean
	 * @return	string		Cleaned string
	 */
	public function cleanMessage( $string )
	{
		$string	= str_replace( "__N__"  , "\n", $string ); 
		$string	= str_replace( "__C__"  , ",", $string ); 
		$string	= str_replace( "__E__"  , "=", $string ); 
		$string	= str_replace( "__PS__" , "+", $string ); 
		$string	= str_replace( "__A__"  , "&", $string ); 
		$string	= str_replace( "__P__"  , "%", $string );
		
		return $string;
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	public function settings()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Number( 'ipchat_log_prune', \IPS\Settings::i()->ipchat_log_prune, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('after'), \IPS\Member::loggedIn()->language()->addToStack('days') ) );
		if ( $form->values() )
		{
			$form->saveAsSettings();

			\IPS\Session::i()->log( 'acplog__chatlog_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=chat&module=chat&controller=logs' ), 'saved' );
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'settings', $form, FALSE );
	}
}