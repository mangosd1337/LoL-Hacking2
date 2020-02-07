<?php
/**
 * @brief		Support Statuses
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
 * Support Departments
 */
class _statuses extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Support\Status';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'statuses_manage' );
		parent::execute();
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
			$node = \IPS\nexus\Support\Status::load( \IPS\Request::i()->id );
			if ( $node->default_staff or $node->default_member )
			{
				\IPS\Output::i()->error( 'cannot_delete_default_status', '1X210/1', 403, '' );
			}

			if ( $node->canDelete() and \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( 'r_status=?', $node->id ) )->first() )
			{
				$form = new \IPS\Helpers\Form( 'delete', 'delete' );
				$form->add( new \IPS\Helpers\Form\Node( 'set_existing_requests_to', NULL, TRUE, array( 'class' => 'IPS\nexus\Support\Status', 'permissionCheck' => function( $_node ) use ( $node )
				{
					return $node->id != $_node->id;
				} ) ) );
				if ( $values = $form->values() )
				{
					\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_status' => $values['set_existing_requests_to']->id ), array( 'r_status=?', $node->id ) );
					return parent::delete();
				}
				
				\IPS\Output::i()->output = $form;
				return;
			}
		}
		catch ( \OutOfRangeException $e ){}
		
		return parent::delete();
	}
}