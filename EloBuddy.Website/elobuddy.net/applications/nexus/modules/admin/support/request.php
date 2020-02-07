<?php
/**
 * @brief		Support Request View
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		09 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\support;
use \IPS\nexus\Support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Request View
 */
class _request extends \IPS\nexus\modules\front\support\view
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_manage' );
		parent::execute();
	}
	
	/**
	 * View Item
	 *
	 * @return	void
	 */
	protected function manage()
	{	
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal('app=nexus&module=support&controller=requests'), \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_support_requests') );
		parent::manage();
				
		/* AJAX responders */
		if ( \IPS\Request::i()->isAjax() )
		{
			/* Popup which has the merge button */
			if ( isset( \IPS\Request::i()->popup ) )
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'support' )->requestPopup( $this->request );
			}
			/* Stock Action data */
			elseif( isset( \IPS\Request::i()->stockActionData ) )
			{
				if ( \IPS\Request::i()->stockActionData )
				{
					try
					{
						$action = \IPS\nexus\Support\StockAction::load( \IPS\Request::i()->stockActionData );
						$data = array();
						
						if ( $action->department )
						{
							$data['department'] = $action->department->id;
						}
						else
						{
							$data['department'] = $this->request->department->id;
						}
						
						if ( $action->status )
						{
							$data['status'] = $action->status->id;
						}
						else
						{
							$data['status'] = \IPS\nexus\Support\Status::load( TRUE, 'status_default_staff' )->id;
						}
						
						if ( $action->staff )
						{
							$data['assign_to'] = $action->staff->member_id;
						}
						else
						{
							$data['assign_to'] = $this->request->staff_lock ? ( $this->request->staff ? $this->request->staff->member_id : 0 ) : 0;
						}
						
						if ( $action->message )
						{
							$data['message'] = $action->message;
						}
						
						\IPS\Output::i()->json( $data );
					}
					catch ( \Exception $e )
					{
						\IPS\Output::i()->json( $e->getMessage(), 500 );
					}
				}
				else
				{
					\IPS\Output::i()->json( array(
						'department'	=> $this->request->department->id,
						'status'		=> \IPS\nexus\Support\Status::load( TRUE, 'status_default_staff' )->id,
						'assign_to'		=> $this->request->staff_lock ? ( $this->request->staff ? $this->request->staff->member_id : 0 ) : 0
					)	);
				}
			}
			/* Purchase tree */
			else
			{
				\IPS\Output::i()->output = \IPS\nexus\Purchase::tree( $this->request->acpUrl(), array(), 's.' . $this->request->id, $this->request->purchase );
			}
			return;
		}
		
		/* Setting Order? */
		if ( isset( \IPS\Request::i()->order ) )
		{
			\IPS\Request::i()->setCookie( 'support_order', \IPS\Request::i()->order, \IPS\DateTime::create()->add( new \DateInterval('P1Y') ) );
		}
		
		/* Views */
		$this->request->setStaffView( \IPS\Member::loggedIn() );

		/* Are we tracking? */
		try
		{
			$trackLang = \IPS\Db::i()->select( 'notify', 'nexus_support_tracker', array( 'member_id=? AND request_id=?', \IPS\Member::loggedIn()->member_id, $this->request->id ) )->first() ? 'tracking_notify' : 'tracking_no_notify';
		}
		catch ( \UnderflowException $e )
		{
			$trackLang = 'not_tracking';
		}

		/* Init buttons */
		\IPS\Output::i()->sidebar['actions'] = array(
			'status'	=> array(
				'icon'			=> 'tag',
				'title'			=> $this->request->status->_title,
				'menu'			=> array(),
				'menuClass' => 'ipsMenu_selectable ipsMenu_narrow',
				'data'			=> array( 'controller' => 'nexus.admin.support.metamenu' )
			),
			'severity'	=> array(
				'icon'			=> 'exclamation',
				'title'			=> $this->request->severity->_title,
				'menu'			=> array(),
				'menuClass' => 'ipsMenu_selectable',
				'data'			=> array( 'controller' => 'nexus.admin.support.metamenu' )
			),
			'department'	=> array(
				'icon'			=> 'folder',
				'title'			=> $this->request->department->_title,
				'menu'			=> array(),
				'menuClass' => 'ipsMenu_selectable ipsMenu_narrow',
				'data'			=> array( 'controller' => 'nexus.admin.support.metamenu' )
			),
			'staff'	=> array(
				'icon'			=> 'user',
				'title'			=> $this->request->staff ? $this->request->staff->name : 'unassigned',
				'menu'			=> array(),
				'menuClass' => 'ipsMenu_selectable',
				'data'			=> array( 'controller' => 'nexus.admin.support.metamenu', 'role' => 'staffMenu' ),
			),
			'track'	=> array(
				'icon'			=> 'star',
				'title'			=> $trackLang,
				'menu'			=> array(
					array(
						'class'	=> $trackLang === 'not_tracking' ? 'ipsMenu_itemChecked' : '',
						'title'	=> 'not_tracking',
						'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'track', 'track' => 0 ) )
					),
					array(
						'class'	=> $trackLang === 'tracking_no_notify' ? 'ipsMenu_itemChecked' : '',
						'title'	=> 'tracking_no_notify',
						'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'track', 'track' => 1, 'notify' => 0 ) )
					),
					array(
						'class'	=> $trackLang === 'tracking_notify' ? 'ipsMenu_itemChecked' : '',
						'title'	=> 'tracking_notify',
						'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'track', 'track' => 1, 'notify' => 1 ) )
					)
				),
				'menuClass' => 'ipsMenu_selectable',
				'data'			=> array( 'controller' => 'nexus.admin.support.metamenu' )
			)
		);
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'requests_delete' ) )
		{
			\IPS\Output::i()->sidebar['actions']['delete'] = array(
				'icon'			=> 'times-circle',
				'title'			=> 'delete',
				'link'			=> $this->request->acpUrl()->setQueryString( 'do', 'delete' ),
				'data'			=> array( 'confirm' => '' )
			);
		}
		
		/* Populate statuses */
		foreach ( Support\Status::roots() as $status )
		{
			\IPS\Output::i()->sidebar['actions']['status']['menu'][] = array(
				'class'	=> $status->id === $this->request->status->id ? 'ipsMenu_itemChecked' : '',
				'title'	=> $status->_title,
				'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'status', 'status' => $status->id ) )
			);
		}
		
		/* Populate severities */
		if ( count( Support\Severity::roots() ) < 2 )
		{
			unset( \IPS\Output::i()->sidebar['actions']['severity'] );
		}
		else
		{
			foreach ( Support\Severity::roots() as $severity )
			{
				\IPS\Output::i()->sidebar['actions']['severity']['menu'][] = array(
					'class'	=> $severity->id === $this->request->severity->id ? 'ipsMenu_itemChecked' : '',
					'title'	=> $severity->_title,
					'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'severity', 'severity' => $severity->id ) ),
					'data'	=> array( 'group' => 'severities' ),
				);
			}
			
			if ( $this->request->member and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'requests_block_sev' ) )
			{
				\IPS\Output::i()->sidebar['actions']['severity']['menu'][] = array( 'hr' => TRUE );
				\IPS\Output::i()->sidebar['actions']['severity']['menu'][] = array(
					'class'	=> !$this->request->author()->cm_no_sev ? 'ipsMenu_itemChecked' : '',
					'title'	=> \IPS\Member::loggedIn()->language()->addToStack( 'cm_no_sev_off', FALSE, array( 'sprintf' => array( $this->request->supportAuthor()->name() ) ) ),
					'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'noSev', 'no_sev' => 0 ) ),
					'data'	=> array( 'group' => 'no_sev', 'noSet' => 'true' ),
				);
				\IPS\Output::i()->sidebar['actions']['severity']['menu'][] = array(
					'class'	=> $this->request->author()->cm_no_sev ? 'ipsMenu_itemChecked' : '',
					'title'	=> \IPS\Member::loggedIn()->language()->addToStack( 'cm_no_sev_on', FALSE, array( 'sprintf' => array( $this->request->supportAuthor()->name() ) ) ),
					'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'noSev', 'no_sev' => 1 ) ),
					'data'	=> array( 'group' => 'no_sev', 'noSet' => 'true' ),
				);
			}
		}
		
		/* Populate departments */
		foreach ( Support\Department::roots() as $department )
		{
			\IPS\Output::i()->sidebar['actions']['department']['menu'][] = array(
				'class'	=> $department->id === $this->request->department->id ? 'ipsMenu_itemChecked' : '',
				'title'	=> $department->_title,
				'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'department', 'department' => $department->id ) )
			);
		}
		
		/* Populate staff */
		foreach ( Support\Request::staff() as $id => $name )
		{
			\IPS\Output::i()->sidebar['actions']['staff']['menu'][] = array(
				'class'	=> ( $this->request->staff and $id === $this->request->staff->member_id ) ? 'ipsMenu_itemChecked' : '',
				'title'	=> $name,
				'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'staff', 'staff' => $id ) ),
				'data'	=> array( 'group' => 'staff', 'id' => $id ),
			);
		}
		\IPS\Output::i()->sidebar['actions']['staff']['menu'][] = array(
			'class'	=> !$this->request->staff ? 'ipsMenu_itemChecked' : '',
			'title'	=> 'unassigned',
			'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'staff', 'staff' => 0 ) ),
			'data'	=> array( 'group' => 'staff', 'id' => 0 ),
		);
		\IPS\Output::i()->sidebar['actions']['staff']['menu'][] = array( 'hr' => TRUE );
		\IPS\Output::i()->sidebar['actions']['staff']['menu'][] = array(
			'class'	=> $this->request->staff_lock ? 'ipsMenu_itemChecked' : '',
			'title'	=> \IPS\Member::loggedIn()->language()->addToStack( 'request_staff_lock_on' ),
			'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'staffLock', 'lock' => 1 ) ),
			'data'	=> array( 'group' => 'staff_lock', 'noSet' => 'true' ),
		);
		\IPS\Output::i()->sidebar['actions']['staff']['menu'][] = array(
			'class'	=> !$this->request->staff_lock ? 'ipsMenu_itemChecked' : '',
			'title'	=>\IPS\Member::loggedIn()->language()->addToStack( 'request_staff_lock_off' ),
			'link'	=> $this->request->acpUrl()->setQueryString( array( 'do' => 'staffLock', 'lock' => 0 ) ),
			'data'	=> array( 'group' => 'staff_lock', 'noSet' => 'true' ),
		);
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'request_x', FALSE, array( 'sprintf' => "#{$this->request->id}" ) );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_support.js', 'nexus', 'admin' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'support.css', 'nexus', 'admin' ) );
		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'support_responsive.css', 'nexus', 'admin' ) );
		}
	}
	
	/**
	 * Pending Response Send/Discard
	 *
	 * @return	void
	 */
	protected function pending()
	{	
		try
		{
			$message = Support\Reply::loadAndCheckPerms( \IPS\Request::i()->response );
			
			if ( \IPS\Request::i()->send )
			{
				$message->sendPending();
			}
			else
			{
				$message->delete();
			}
			
			\IPS\Output::i()->redirect( $message->item()->acpUrl()->setQueryString( array( 'filter' => \IPS\Request::i()->filter, 'sort' => \IPS\Request::i()->sort ) ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/1', 404, '' );
		}
	}
	
	/**
	 * Edit Title
	 *
	 * @return	void
	 */
	protected function editTitle()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			
			$form = new \IPS\Helpers\Form;
			$form->add( new \IPS\Helpers\Form\Text( 'support_title', $request->title, TRUE, array( 'maxLength' => 255 ) ) );
			
			if ( $values = $form->values() )
			{
				$request->title = $values['support_title'];
				$request->save();
				\IPS\Output::i()->redirect( $request->acpUrl() );
			}
			
			\IPS\Output::i()->output = $form;
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/2', 404, '' );
		}
	}
	
	/**
	 * Edit Custom Fields
	 *
	 * @return	void
	 */
	protected function cfields()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$customFieldValues = $request->cfields;
			
			$form = new \IPS\Helpers\Form;
			foreach ( $request->department->customFields() as $field )
			{
				$form->add( $field->buildHelper( isset( $customFieldValues[ $field->id ] ) ? $customFieldValues[ $field->id ] : NULL ) );
			}
						
			if ( $values = $form->values( TRUE ) )
			{
				$save = array();
				foreach ( $values as $k => $v )
				{
					$save[ mb_substr( $k, 13 ) ] = $v;
				}
				$request->cfields = $save;				
				$request->save();
				\IPS\Output::i()->redirect( $request->acpUrl() );
			}
			
			\IPS\Output::i()->output = $form;
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/3', 404, '' );
		}
	}
	
	/**
	 * Set Status
	 *
	 * @return	void
	 */
	protected function status()
	{
		try
		{	
			/* Init */		
			$return = array();
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$status = Support\Status::load( \IPS\Request::i()->status );
			$oldStatus = $request->status;
			$request->status = $status;
			
			/* Assign it if we have to */
			if ( $status->assign )
			{
				if ( $request->staff and $request->staff->member_id != \IPS\Member::loggedIn()->member_id )
				{
					$return['alert'] = \IPS\Member::loggedIn()->language()->addToStack( 'you_have_stolen_request', FALSE, array( 'sprintf' => array( $request->staff->name ) ) );
				}
				$request->staff = \IPS\Member::loggedIn();
				$return['staff'] = array( 'id' => \IPS\Member::loggedIn()->member_id, 'name' => \IPS\Member::loggedIn()->name );
			}
			/* Or set the previous status was "Working", release our assigning */
			elseif ( $oldStatus->assign and $request->staff )
			{
				$request->staff = NULL;
				$return['staff'] = array( 'id' => 0, 'name' => \IPS\Member::loggedIn()->language()->addToStack('unassigned') );
			}
			$request->save();
			
			/* Log */
			if ( $status->log )
			{
				$request->log( 'status', $oldStatus, $status );
			}
			
			/* Return */
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( $return );
			}
			else
			{
				\IPS\Output::i()->redirect( $request->acpUrl() );
			}
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/4', 404, '' );
		}
	}
	
	/**
	 * Set Severity
	 *
	 * @return	void
	 */
	protected function severity()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$new = Support\Severity::load( \IPS\Request::i()->severity );
			$request->log( 'severity', $request->severity, $new );
			$request->severity = $new;
			$request->save();
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array() );
			}
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/5', 404, '' );
		}
	}
	
	/**
	 * Control member's permission to set severities
	 *
	 * @return	void
	 */
	protected function noSev()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_block_sev' );
		
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$request->author()->cm_no_sev = \IPS\Request::i()->no_sev;
			$request->author()->save();
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array() );
			}
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/6', 404, '' );
		}
	}
	
	/**
	 * Staff Lock
	 *
	 * @return	void
	 */
	protected function staffLock()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$request->staff_lock = \IPS\Request::i()->lock;
			$request->save();
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array( 'assign_to' => ( $request->staff_lock ? ( $request->staff ? $request->staff->member_id : 0 ) : 0 ) ) );
			}
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/7', 404, '' );
		}
	}
	
	/**
	 * Set Department
	 *
	 * @return	void
	 */
	protected function department()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$new = Support\Department::load( \IPS\Request::i()->department );
			$request->log( 'department', $request->department, $new );
			$request->department = $new;
			$request->save();
			if ( \IPS\Request::i()->isAjax() )
			{
				$stockActionOptions = array();
				$stockActions = array( 0 => '' );
				foreach ( \IPS\nexus\Support\StockAction::roots( NULL, NULL, "action_show_in='*' OR " . \IPS\Db::i()->findInSet( 'action_show_in', array( $request->department->id ) ) ) as $action )
				{
					$stockActions[ $action->id ] = $action->_title;
				}
				
				\IPS\Output::i()->json( array( 'department' => \IPS\Request::i()->department, 'stockActions' => $stockActions, 'requiresPurchase' => (bool) $request->department->require_package ) );
			}
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/8', 404, '' );
		}
	}
	
	/**
	 * Set Staff
	 *
	 * @return	void
	 */
	protected function staff()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$new = \IPS\Request::i()->staff ? \IPS\Member::load( \IPS\Request::i()->staff ) : NULL;
			$request->log( 'staff', $request->staff, $new );
			$request->staff = $new;
			$request->save();
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array( 'assign_to' => ( $request->staff_lock ? ( $request->staff ? $request->staff->member_id : 0 ) : 0 ) ) );
			}
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/9', 404, '' );
		}
	}
	
	/**
	 * Track
	 *
	 * @return	void
	 */
	protected function track()
	{
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			
			if ( \IPS\Request::i()->track )
			{
				\IPS\Db::i()->insert( 'nexus_support_tracker', array(
					'member_id'		=> \IPS\Member::loggedIn()->member_id,
					'request_id'	=> $request->id,
					'notify'		=> \IPS\Request::i()->notify
				), TRUE );
			}
			else
			{
				\IPS\Db::i()->delete( 'nexus_support_tracker', array( 'member_id=? AND request_id=?', \IPS\Member::loggedIn()->member_id, $request->id ) );
			}
			
			if ( \IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->json( array() );
			}
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/A', 404, '' );
		}
	}
	
	/**
	 * Associate
	 *
	 * @return	void
	 */
	protected function associate()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'purchases_view', 'nexus', 'customers' );
		
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			
			$form = new \IPS\Helpers\Form;
			$form->class = 'ipsForm_vertical ipsForm_noLabels';
			$form->add( new \IPS\Helpers\Form\Node( 'associated_purchase', $request->purchase, TRUE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => $request->author(), 'zeroVal' => 'none' ) ) );
			if ( $values = $form->values() )
			{
				$request->log( 'purchase', $request->purchase, $values['associated_purchase'] ?: NULL );
				$request->purchase = $values['associated_purchase'] ?: NULL;
				$request->save();
				\IPS\Output::i()->redirect( $request->acpUrl() );
			}
			
			\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core', 'front' ) ), 'popupTemplate' ) );			
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/B', 404, '' );
		}
	}
	
	/**
	 * Merge
	 *
	 * @return	void
	 */
	protected function merge()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_merge' );
		
		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$request->mergeIn( array( Support\Request::loadAndCheckPerms( \IPS\Request::i()->merge ) ) ) ;
			\IPS\Output::i()->redirect( $request->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/C', 404, '' );
		}
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		try
		{
			$request = Support\Request::loadAndCheckPerms( \IPS\Request::i()->id );
			$request->delete();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=support&controller=requests" ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/D', 404, '' );
		}
	}
	
	/**
	 * Delete Reply
	 *
	 * @return	void
	 */
	protected function deleteReply()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_reply_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		try
		{
			$reply = Support\Reply::loadAndCheckPerms( \IPS\Request::i()->id );
			$reply->delete();
			\IPS\Output::i()->redirect( $reply->item()->acpUrl() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/G', 404, '' );
		}
	}
	
	/**
	 * View Feedback
	 *
	 * @return	void
	 */
	protected function feedback()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_ratings_feedback' );
		
		try
		{
			$reply = \IPS\nexus\Support\Reply::loadAndCheckPerms( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/E', 404, '' );
		}
		
		$rating = NULL;
		try
		{
			$rating = \IPS\Db::i()->select( '*', 'nexus_support_ratings', array( 'rating_reply=?', $reply->id ) )->first();
		}
		catch ( \UnderflowException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X208/F', 404, '' );
		}
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'support' )->feedback( $rating );
	}
}