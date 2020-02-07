<?php
/**
 * @brief		Upgrader: System Check
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		20 May 2014
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: System Check
 */
class _systemcheck extends \IPS\Dispatcher\Controller
{
	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage()
	{
		/* Do we have an older upgrade session hanging around? */
		if ( ! isset( \IPS\Request::i()->skippreviousupgrade ) AND file_exists( \IPS\ROOT_PATH . '/uploads/logs/upgrader_data.cgi' ) )
		{
			$json = json_decode( @file_get_contents( \IPS\ROOT_PATH . '/uploads/logs/upgrader_data.cgi' ), TRUE );
			
			if ( is_array( $json ) and isset( $json['session'] ) and isset( $json['data'] ) )
			{
				\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('unfinished_upgrade');
				\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'global' )->unfinishedUpgrade( $json, @filemtime( \IPS\ROOT_PATH . '/uploads/logs/upgrader_data.cgi' ) );
				return;
			}
		}
		
		/* Do we need to disable designer mode? */
		if ( isset( \IPS\Request::i()->disableDesignersMode ) )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => 0 ), array( 'conf_key=?', 'theme_designers_mode' ) );
			unset( \IPS\Data\Store::i()->settings );
		}
		
		/* Get requirements */
		$requirements = \IPS\core\Setup\Upgrade::systemRequirements();
		$designersModeEnabled = ( \IPS\Application::load('core')->long_version >= 40000 and \IPS\Theme::designersModeEnabled() ) ? TRUE : FALSE;
		
		/* Can we just skip this screen? */
		$canProceed = FALSE;
		if ( !$designersModeEnabled )
		{
			$canProceed = !isset( $requirements['advice'] ) or !count( $requirements['advice'] );
			if ( $canProceed )
			{
				foreach ( $requirements['requirements'] as $k => $_requirements )
				{
					foreach ( $_requirements as $item )
					{
						if ( !$item['success'] )
						{
							$canProceed = FALSE;
						}
					}
				}
			}
		}
		if ( $canProceed )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal("controller=license&key={$_SESSION['uniqueKey']}") );
		}
		
		/* Display */
		\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack('healthcheck');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global' )->healthcheck( $requirements, $designersModeEnabled );
	}
}