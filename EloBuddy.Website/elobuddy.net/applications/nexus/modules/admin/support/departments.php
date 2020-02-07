<?php
/**
 * @brief		Support Departments
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 Apr 2014
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
class _departments extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Support\Department';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'departments_manage' );
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
			$node = \IPS\nexus\Support\Department::load( \IPS\Request::i()->id );

			if ( $node->canDelete() and \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( 'r_department=?', $node->id ) ) )
			{
				$form = new \IPS\Helpers\Form( 'delete', 'delete' );
				$form->add( new \IPS\Helpers\Form\Node( 'move_existing_requests_to', NULL, TRUE, array( 'class' => 'IPS\nexus\Support\Department', 'permissionCheck' => function( $_node ) use ( $node )
				{
					return $node->id != $_node->id;
				} ) ) );
				if ( $values = $form->values() )
				{
					\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_department' => $values['move_existing_requests_to']->id ), array( 'r_department=?', $node->id ) );
					\IPS\Request::i()->form_submitted = TRUE;
					\IPS\Request::i()->wasConfirmed = TRUE;
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