<?php
/**
 * @brief		Moderator Control Panel Extension: IP Tools
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		02 Oct 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\ModCp;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	IP Tools
 */
class _IPTools extends \IPS\core\modules\admin\members\ip
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string
	 */
	public function getTab()
	{
		if ( ! \IPS\Member::loggedIn()->modPermission('can_use_ip_tools') )
		{
			return null;
		}
		
		return 'ip_tools';
	}
	
	/**
	 * Get content to display
	 *
	 * @return	string
	 */
	public function manage()
	{
		if ( ! \IPS\Member::loggedIn()->modPermission('can_use_ip_tools') )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2C250/1', 403, '' );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'modcp_ip_tools' );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' ), \IPS\Member::loggedIn()->language()->addToStack( 'modcp_ip_tools' ) );
		
		if ( isset( \IPS\Request::i()->ip ) and filter_var( \IPS\Request::i()->ip, FILTER_VALIDATE_IP ) )
		{
			$ip = \IPS\Request::i()->ip;
			\IPS\Output::i()->title = $ip;
			
			$url =  \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' )->setQueryString( 'ip', $ip );
			\IPS\Output::i()->breadcrumb[] = array( $url, $ip );

			if ( isset( \IPS\Request::i()->area ) )
			{
				\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack( 'ipAddresses__' .  \IPS\Request::i()->area ) );

				$exploded = explode( '_', \IPS\Request::i()->area );
				$extensions = \IPS\Application::load( $exploded[0] )->extensions( 'core', 'IpAddresses' );

				/* If the extension no longer exists (application uninstalled) then fall back */
				if( isset( $extensions[ mb_substr( \IPS\Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ] ) )
				{
					$class = $extensions[ mb_substr( \IPS\Request::i()->area, mb_strlen( $exploded[0] ) + 1 ) ];
				}
				else
				{
					$class = array_pop( $extensions );
				}

				\IPS\Output::i()->output = $class->findByIp( $ip, $url->setQueryString( 'area', \IPS\Request::i()->area ) );
			}
			else
			{
				$geolocation = NULL;
				$map = NULL;
				try
				{
					$geolocation = \IPS\GeoLocation::getByIp( $ip );
					$map = $geolocation->map()->render( 400, 350, 0.6 );
				}
				catch ( \Exception $e ) {}
				
				$contentCounts = array();
				$otherCounts = array();
				foreach ( \IPS\Application::allExtensions( 'core', 'IpAddresses' ) as $k => $ext )
				{					
					$count = $ext->findByIp( $ip );
					if ( $count !== NULL )
					{			
						if ( isset( $ext->class ) )
						{
							$class = $ext->class;
							if ( isset( $class::$databaseColumnMap['ip_address'] ) )
							{
								$contentCounts[ $k ] = $count;
							}
						}
						else
						{
							$otherCounts[ $k ] = $count;
						}
					}
				}
				
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'members', 'core', 'global' )->ipLookup( $url, $geolocation, $map, array_merge( $otherCounts, $contentCounts ) );
			}
		}
		else
		{
			$form = new \IPS\Helpers\Form( 'form', 'continue' );
			$form->class = 'ipsForm_vertical';
			
			$form->add( new \IPS\Helpers\Form\Text( 'ip_address', NULL, TRUE, array(), function( $val )
			{
				if( filter_var( $val, FILTER_VALIDATE_IP ) === false )
				{
					throw new \DomainException('ip_address_bad');
				}
			} ) );
			
			if ( $values = $form->values() )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=modcp&controller=modcp&tab=ip_tools", 'front', 'modcp_ip_tools' )->setQueryString( 'ip', $values['ip_address'] ) );
			}
		
			return \IPS\Theme::i()->getTemplate( 'modcp', 'core', 'front' )->iptools( $form );
		}
	}
}