<?php
/**
 * @brief		severities
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		09 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * severities
 */
class _severities extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Support\Severity';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'severities_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		parent::manage();
		
		if ( isset( \IPS\Request::i()->nexus_severities ) )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => \IPS\Request::i()->nexus_severities ), array( 'conf_key=?', 'nexus_severities' ) );
			\IPS\Settings::i()->nexus_severities = \IPS\Request::i()->nexus_severities;
			unset( \IPS\Data\Store::i()->settings );
			\IPS\Session::i()->log( 'acplogs__severity_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=support&controller=settings&tab=severities') );
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('support')->severities( \IPS\Output::i()->output );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		try
		{
			$node = \IPS\nexus\Support\Severity::load( \IPS\Request::i()->id );
			if ( $node->default )
			{
				\IPS\Output::i()->error( 'cannot_delete_default_severity', '1X211/1', 403, '' );
			}
			
			if ( $node->canDelete() )
			{
				if ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( 'r_severity=?', $node->id ) ) )
				{
					$form = new \IPS\Helpers\Form( 'delete', 'delete' );
					$form->add( new \IPS\Helpers\Form\Node( 'set_existing_requests_to', NULL, TRUE, array( 'class' => 'IPS\nexus\Support\Severity', 'permissionCheck' => function( $_node ) use ( $node )
					{
						return $node->id != $_node->id;
					} ) ) );
					if ( $values = $form->values() )
					{
						/* Update */
						\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_severity' => $values['set_existing_requests_to']->id ), array( 'r_severity=?', $node->id ) );
						
						/* Delete it */
						\IPS\Session::i()->log( 'acplog__node_deleted', array( $this->title => TRUE, $node->titleForLog() => FALSE ) );
						$node->delete();
				
						/* Clear out member's cached "Create Menu" contents */
						\IPS\Member::clearCreateMenu();
						
						/* Boink */
						if( \IPS\Request::i()->isAjax() )
						{
							\IPS\Output::i()->json( "OK" );
						}
						else
						{
							\IPS\Output::i()->redirect( $this->url->setQueryString( array( 'root' => ( $node->parent() ? $node->parent()->_id : '' ) ) ), 'deleted' );
						}
					}
					
					\IPS\Output::i()->output = $form;
					return;
				}
				else
				{
					return parent::delete();
				}
			}
			else
			{
				return parent::delete();
			}
		}
		catch ( \OutOfRangeException $e )
		{
			return parent::delete();
		}		
	}
}