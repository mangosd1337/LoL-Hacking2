<?php
/**
 * @brief		Support Requests
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
 * Support Requests
 */
class _requests extends \IPS\Dispatcher\Controller
{	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'support.css', 'nexus', 'admin' ) );
		
		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'support_responsive.css', 'nexus', 'admin' ) );
		}

		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Can we access anything? */
		if ( !count( \IPS\nexus\Support\Department::departmentsWithPermission() ) )
		{
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'departments_manage' ) )
			{
				\IPS\Output::i()->error( 'err_no_departments_with_perm1', '1X261/1', 403, '' );
			}
			else
			{
				\IPS\Output::i()->error( 'err_no_departments_with_perm2', '1X261/2', 403, '' );
			}
		}
		
		/* URL */
		$url = \IPS\Http\Url::internal( 'app=nexus&module=support&controller=requests' );
		$customer = NULL;
		$where = array();
		if ( isset( \IPS\Request::i()->member ) )
		{
			try
			{
				$customer = \IPS\nexus\Customer::load( \IPS\Request::i()->member );
				$url = $url->setQueryString( 'member', $customer->member_id );
				$where[] = array( 'r_member=?', $customer->member_id );
			}
			catch ( \OutOfRangeException $e ) { }
		}
		
		/* Permissions */
		$where[] = array( "( dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) . ')' );

		/* Filters */
		if ( isset( \IPS\Request::i()->assigned ) )
		{
			$where[] = array( 'r_staff=?', \IPS\Member::loggedIn()->member_id );
		}
		if ( isset( \IPS\Request::i()->tracked ) )
		{
			$where[] = array( 'request_id IS NOT NULL' );
		}
		if ( isset( \IPS\Request::i()->replied ) )
		{
			$where[] = array( 'r_id IN(?)', \IPS\Db::i()->select( 'DISTINCT(reply_request)', 'nexus_support_replies', array( 'reply_member=?', \IPS\Member::loggedIn()->member_id ) ) );
		}

		/* Departments / Statuses */
		$myFilters = \IPS\nexus\Support\Request::myFilters();
		if ( isset( \IPS\Request::i()->departments ) or isset( \IPS\Request::i()->statuses ) )
		{
			$newFilters = array( 'departments' => isset( \IPS\Request::i()->departments ) ? explode( ',', \IPS\Request::i()->departments ) : $myFilters['departments'], 'statuses' => isset( \IPS\Request::i()->statuses ) ? explode( ',', \IPS\Request::i()->statuses ) : $myFilters['statuses'] );
			\IPS\Request::i()->setCookie( 'support_filters', json_encode( $newFilters ), \IPS\DateTime::create()->add( new \DateInterval( 'P1Y' ) ) );
			$myFilters = \IPS\nexus\Support\Request::myFilters( $newFilters );
		}
		$where[] = $myFilters['whereClause'];
						
		/* Create the table */
		$table = \IPS\nexus\Support\Request::table( $url, $where );
		$table->classes[] = 'cNexusSupportTable';
		$table->tableTemplate  = array( \IPS\Theme::i()->getTemplate( 'support', 'nexus', 'admin' ), 'requestTable' );
		$table->rowsTemplate  = array( \IPS\Theme::i()->getTemplate( 'support', 'nexus', 'admin' ), 'requestRows' );

		/* Search */
		$table->quickSearch = 'r_title';
		$table->advancedSearch = array(
			'r_title_or_message'=> array( \IPS\Helpers\Table\SEARCH_CUSTOM, array(
				'getHtml'	=> function( $field )
				{
					return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->text( $field->name, 'text', $field->value, $field->required );
				}
			), function( $v )
			{
				$requests = iterator_to_array( \IPS\Db::i()->select( 'reply_request', 'nexus_support_replies', array( "MATCH(reply_post) AGAINST (? IN BOOLEAN MODE)", $v ) ) );

				return array( \IPS\Db::i()->in( 'r_id', $requests ) . ' OR r_title LIKE ?', "%{$v}%" );
			} ),
			'r_severity'		=> array( \IPS\Helpers\Table\SEARCH_NODE, array( 'class' => 'IPS\nexus\Support\Severity', 'multiple' => TRUE ) ),
			'r_department'		=> array( \IPS\Helpers\Table\SEARCH_NODE, array( 'class' => 'IPS\nexus\Support\Department', 'multiple' => TRUE ) ),
			'r_member'			=> \IPS\Helpers\Table\SEARCH_MEMBER,
			'r_email'			=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'r_replies'			=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			'r_started'			=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'r_last_new_reply'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'r_last_reply'		=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'r_last_reply_by'	=> \IPS\Helpers\Table\SEARCH_MEMBER,
			'r_last_staff_reply'=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'r_status'			=> array( \IPS\Helpers\Table\SEARCH_NODE, array( 'class' => 'IPS\nexus\Support\Status', 'multiple' => TRUE ) ),
			'r_staff'			=> array( \IPS\Helpers\Table\SEARCH_SELECT, array( 'options' => \IPS\nexus\Support\Request::staff(), 'multiple' => TRUE, 'noDefault' => TRUE ) ),
		);
				
		/* Display */
		\IPS\Output::i()->title		= $customer ? \IPS\Member::loggedIn()->language()->addToStack( 'members_support_requests', FALSE, array( 'sprintf' => array( $customer->cm_name ) ) ) : \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_support_requests');
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
		
		/* Create Button */
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'requests_create' ) )
		{
			$createUrl = \IPS\Http\Url::internal('app=nexus&module=support&controller=requests&do=create&_new=1');
			if ( $customer )
			{
				$createUrl = $createUrl->setQueryString( 'member', $customer->member_id );
			}
			\IPS\Output::i()->sidebar['actions']['create'] = array(
				'icon'	=> 'plus',
				'title'	=> 'add',
				'link'	=> $createUrl,
			);
		}
		
		/* Settings */
		\IPS\Output::i()->sidebar['actions']['notifications'] = array(
			'icon'	=> 'envelope',
			'title'	=> 'my_notifications',
			'link'	=> \IPS\Http\Url::internal('app=nexus&module=support&controller=requests&do=notifications'),
			'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('my_notifications') )
		);
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'support', 'support_display_settings' ) )
		{
			\IPS\Output::i()->sidebar['actions']['settings'] = array(
				'icon'	=> 'cog',
				'title'	=> 'display_settings',
				'link'	=> \IPS\Http\Url::internal('app=nexus&module=support&controller=requests&do=settings'),
				'data'	=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('display_settings') )
			);
		}
	}
	
	/**
	 * Reset my departments
	 *
	 * @return	void
	 */
	protected function resetMyDepartments()
	{
		\IPS\Request::i()->setCookie( 'support_filters', '', \IPS\DateTime::create()->sub( new \DateInterval( 'P1D' ) ) );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=support&controller=requests') );
	}
	
	/**
	 * My Notifications
	 *
	 * @return	void
	 */
	protected function notifications()
	{
		$types = array( 'support_notify_new' => 'n', 'support_notify_replies' => 'r', 'support_notify_assign' => 'a' );
		
		$existing = iterator_to_array( \IPS\Db::i()->select( '*', 'nexus_support_notify', array( 'staff_id=?', \IPS\Member::loggedIn()->member_id ) )->setKeyField('type')->setValueField('departments') );
		
		$myDepartments = array();
		foreach ( \IPS\nexus\Support\Department::roots( NULL, NULL, array( "dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) ) ) as $dpt )
		{
			$myDepartments[ $dpt->id ] = $dpt->_title;
		}
		
		$form = new \IPS\Helpers\Form;
		$form->addMessage( 'support_notify_blurb' );
		$form->addHeader('notify_me_when');
		foreach ( $types as $k => $t )
		{
			$form->add( new \IPS\Helpers\Form\Select( $k, isset( $existing[ $t ] ) ? ( $existing[ $t ] === '*' ? 0 : explode( ',', $existing[ $t ] ) ) : NULL, FALSE, array( 'options' => $myDepartments, 'multiple' => TRUE, 'unlimited' => 0, 'unlimitedLang' => 'all', 'noDefault' => TRUE ) ) );
		}
		
		if ( $values = $form->values() )
		{
			\IPS\Db::i()->delete( 'nexus_support_notify', array( 'staff_id=?', \IPS\Member::loggedIn()->member_id ) );
			
			foreach ( $types as $k => $t )
			{
				if ( $values[ $k ] === 0 or count( $values[ $k ] ) )
				{
					\IPS\Db::i()->insert( 'nexus_support_notify', array(
						'staff_id'		=> \IPS\Member::loggedIn()->member_id,
						'type'			=> $t,
						'departments'	=> ( $values[ $k ] === 0 ) ? '*' : implode( ',', $values[ $k ] )
					) );
				}
			}
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal("app=nexus&module=support&controller=requests") );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('my_notifications');
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Display Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'support_display_settings' );
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Custom( 'support_old_highlight', array( \IPS\Settings::i()->support_old_stat, \IPS\Settings::i()->support_old_number ), FALSE, array(
			'getHtml'	=> function( $obj )
			{
				$number = \IPS\Settings::i()->support_old_number;
				$numberType = $number ? 'minutes' : 'hours';
				if ( $number % 1440 === 0 )
				{
					$number /= 1440; # Fun fact: that's the first time I've used that operator
					$numberType = 'days';
				}
				elseif ( $number % 60 === 0 )
				{
					$number /= 60; # Fun fact: that's the second time I've used that operator
					$numberType = 'hours';
				}
				
				return \IPS\Theme::i()->getTemplate('support')->highlightSetting( $obj->name, $obj->value[0], $number, $numberType );
			}
		) ) );
		if ( $values = $form->values() )
		{
			$save = array();
			if ( isset( $values['support_old_highlight'][3] ) )
			{
				$save['support_old_number'] = 0;
			}
			else
			{
				$save['support_old_stat'] = $values['support_old_highlight'][0];
				switch ( $values['support_old_highlight'][2] )
				{
					case 'minutes':
						$save['support_old_number'] = $values['support_old_highlight'][1];
						break;
					case 'hours':
						$save['support_old_number'] = $values['support_old_highlight'][1] * 60;
						break;
					case 'days':
						$save['support_old_number'] = $values['support_old_highlight'][1] * 1440;
						break;
				}
			}
			
			$form->saveAsSettings( $save );
			\IPS\Session::i()->log( 'acplogs__support_display_settings' );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=support&controller=requests' ), 'saved' );
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Create
	 *
	 * @return	void
	 */
	protected function create()
	{
		/* Init */
		\IPS\Dispatcher::i()->checkAcpPermission( 'requests_create' );
		$steps = array();
		
		/* Owner */
		$steps['request_owner'] = function( $data )
		{
			$form = new \IPS\Helpers\Form( 'owner', 'continue' );
			$form->add( new \IPS\Helpers\Form\Radio( 'request_owner_type', 'member', TRUE, array(
				'options' 	=> array( 'member' => 'request_owner_member', 'email' => 'request_owner_email' ),
				'toggles'	=> array( 'member' => array( 'request_owner_member' ), 'email' => array( 'request_owner_email' ) )
			) ) );
			$form->add( new \IPS\Helpers\Form\Member( 'request_owner_member', isset( \IPS\Request::i()->member ) ? \IPS\Member::load( \IPS\Request::i()->member ) : NULL, FALSE, array(), NULL, NULL, NULL, 'request_owner_member' ) );
			$form->add( new \IPS\Helpers\Form\Email( 'request_owner_email', NULL, FALSE, array(), NULL, NULL, NULL, 'request_owner_email' ) );
			if ( $values = $form->values() )
			{
				if ( $values['request_owner_type'] === 'member' )
				{
					if ( !$values['request_owner_member'] )
					{
						$form->error = \IPS\Member::loggedIn()->language()->addToStack('request_owner_req');
						return (string) $form;
					}
					$data['member'] = $values['request_owner_member']->member_id;
				}
				else
				{
					if ( !$values['request_owner_email'] )
					{
						$form->error = \IPS\Member::loggedIn()->language()->addToStack('request_owner_req');
						return (string) $form;
					}
					
					$member = \IPS\Member::load( $values['request_owner_email'], 'email' );
					if ( $member->member_id )
					{
						$data['member'] = $member->member_id;
					}
					else
					{
						$data['email'] = $values['request_owner_email'];
					}
				}
				
				return $data;
			}
			
			return (string) $form;
		};
		
		/* Stock Actions */
		$stockActions = \IPS\nexus\Support\StockAction::roots();
		if ( count( $stockActions ) )
		{
			$steps['stock_action'] = function( $data ) use ( $stockActions )
			{
				$options = array( 0 => 'none' );
				foreach ( $stockActions as $action )
				{
					$options[ $action->id ] = $action->_title;
				}
				
				$form = new \IPS\Helpers\Form( 'stock_action', 'continue' );
				$form->add( new \IPS\Helpers\Form\Radio( 'stock_action', 0, FALSE, array(
					'options'	=> $options
				) ) );
				if ( $values = $form->values() )
				{
					$data['stock_action'] = $values['stock_action'];
					return $data;
				}
				
				return (string) $form;
			};
		}
		
		/* Request Details */
		$steps['request_details'] = function( $data ) use ( $stockActions )
		{
			$stockAction = NULL;
			if ( isset( $data['stock_action'] ) and isset( $stockActions[ $data['stock_action'] ] ) )
			{
				$stockAction = $stockActions[ $data['stock_action'] ];
			}
						
			$form = new \IPS\Helpers\Form( 'request_details', 'continue' );
			$form->add( new \IPS\Helpers\Form\Text( 'support_title', NULL, TRUE ) );
			$form->add( new \IPS\Helpers\Form\Node( 'support_department', $stockAction ? $stockAction->department : NULL, TRUE, array( 'class' => 'IPS\nexus\Support\Department' ) ) );
			if ( isset( $data['member'] ) )
			{
				$form->add( new \IPS\Helpers\Form\Node( 'support_purchase', 0, FALSE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => \IPS\Member::load( $data['member'] ), 'zeroVal' => 'none' ) ) );
			}
			$form->add( new \IPS\Helpers\Form\Node( 'r_status', $stockAction ? $stockAction->status : \IPS\nexus\Support\Status::load( TRUE, 'status_default_staff' ), TRUE, array( 'class' => 'IPS\nexus\Support\Status' ) ) );
			$form->add( new \IPS\Helpers\Form\Node( 'support_severity', \IPS\nexus\Support\Severity::load( TRUE, 'sev_default' ), TRUE, array( 'class' => 'IPS\nexus\Support\Severity' ) ) );
			$form->add( new \IPS\Helpers\Form\Select( 'r_staff', 0, FALSE, array( 'options' =>  array( 0 => 'unassigned' ) + \IPS\nexus\Support\Request::staff() ) ) );
			if ( $values = $form->values() )
			{
				$data['title'] = $values['support_title'];
				$data['department'] = $values['support_department']->id;
				if ( isset( $values['support_purchase'] ) and $values['support_purchase'] )
				{
					$data['purchase'] = $values['support_purchase']->id;
				}
				$data['status'] = $values['r_status']->id;
				$data['severity'] = $values['support_severity']->id;
				$data['staff'] = $values['r_staff'];
				return $data;
			}
			return (string) $form;
		};
		
		/* Custom Fields */
		$customFields = \IPS\nexus\Support\CustomField::roots();
		if ( count( $customFields ) )
		{
			$steps['custom_support_fields'] = function( $data )
			{
				$customFields = \IPS\nexus\Support\CustomField::roots( NULL, NULL, array( "sf_departments='*' OR " . \IPS\Db::i()->findInSet( 'sf_departments', array( $data['department'] ) ) ) );
				if ( count( $customFields ) )
				{
					$form = new \IPS\Helpers\Form( 'custom_fields', 'continue' );
					foreach ( $customFields as $field )
					{
						$form->add( $field->buildHelper() );
					}
					if ( $values = $form->values() )
					{
						$data['custom_fields'] = $values;
						return $data;
					}
					return (string) $form;
				}
				else
				{
					return $data;
				}
			};
		}
		
		/* Your Message */
		$steps['your_message'] = function( $data ) use ( $stockActions )
		{
			$stockAction = NULL;
			if ( isset( $data['stock_action'] ) and isset( $stockActions[ $data['stock_action'] ] ) )
			{
				$stockAction = $stockActions[ $data['stock_action'] ];
			}
			
			$form = new \IPS\Helpers\Form( 'your_message' );
			$form->add( new \IPS\Helpers\Form\Email( 'to', isset( $data['member'] ) ? \IPS\Member::load( $data['member'] )->email : $data['email'] ) );
			$form->add( new \IPS\Helpers\Form\Text( 'cc', array(), FALSE, array( 'autocomplete' => array( 'unique' => TRUE, 'forceLower' => TRUE ) ), array( 'IPS\nexus\Support\Request', '_validateEmail' ) ) );
			$form->add( new \IPS\Helpers\Form\Text( 'bcc', array(), FALSE, array( 'autocomplete' => array( 'unique' => TRUE, 'forceLower' => TRUE ) ), array( 'IPS\nexus\Support\Request', '_validateEmail' ) ) );
			$form->add( new \IPS\Helpers\Form\Editor( 'message', $stockAction ? $stockAction->message : NULL, TRUE, array( 'app' => 'nexus', 'key' => 'Support', 'autoSaveKey' => "new-req-message" ) ) );
			if ( $values = $form->values() )
			{
				$request = new \IPS\nexus\Support\Request;
				$request->started = time();
				$request->title = $data['title'];
				if ( isset( $data['member'] ) )
				{
					$request->member = $data['member'];
				}
				else
				{
					$request->email = $data['email'];
				}
				$request->department = \IPS\nexus\Support\Department::load( $data['department'] );
				if ( isset( $data['purchase'] ) and $data['purchase'] )
				{
					$request->purchase = \IPS\nexus\Purchase::load( $data['purchase'] );
				}
				$request->status = \IPS\nexus\Support\Status::load( $data['status'] );
				$request->severity = \IPS\nexus\Support\Severity::load( $data['severity'] );
				if ( isset( $data['staff'] ) and $data['staff'] )
				{
					$request->staff = \IPS\Member::load( $data['staff'] );
				}
				if ( isset( $data['custom_fields'] ) )
				{
					$cfields = array();
					$customFieldObjects = \IPS\nexus\Support\CustomField::roots();
					foreach ( $data['custom_fields'] as $k => $v )
					{
						if ( mb_substr( $k, 0, 13 ) === 'nexus_cfield_' )
						{
							$k = mb_substr( $k, 13 );
							$class = $customFieldObjects[ $k ]->buildHelper();
							$cfields[ $k ] = $class::stringValue( $v );
						}
					}

					$request->cfields = $cfields;
				}
				
				$notify = $request->notify;
				foreach ( $values['cc'] as $cc )
				{
					foreach ( $notify as $n )
					{
						if ( $n['value'] === $cc )
						{
							continue 2;
						}
					}
					$notify[] = array( 'type' => 'e', 'value' => $cc );
				}
				foreach ( $values['bcc'] as $cc )
				{
					foreach ( $notify as $k => $n )
					{
						if ( $n['value'] === $cc )
						{
							if ( $n['bcc'] )
							{
								unset( $notify[ $k ]['bcc'] );
							}
							
							continue 2;
						}
					}
					$notify[] = array( 'type' => 'e', 'value' => $cc, 'bcc' => 1 );
				}
				$request->notify = $notify;
				$request->last_reply = time();
				$request->last_new_reply = time();
				$request->last_staff_reply = time();
				$request->last_reply_by = \IPS\Member::loggedIn()->member_id;
				$request->replies = 1;
				$request->save();
				
				$reply = new \IPS\nexus\Support\Reply;
				$reply->request = $request->id;
				$reply->member = \IPS\Member::loggedIn()->member_id;
				$reply->type = $reply::REPLY_STAFF;
				$reply->post = $values['message'];
				$reply->date = time();
				$reply->cc = implode( ',', $values['cc'] );
				$reply->ip_address = \IPS\Request::i()->ipAddress();
				$reply->bcc = implode( ',', $values['bcc'] );
				$reply->save();

				\IPS\File::claimAttachments( 'new-req-message', $request->id, $reply->id );

				$request->processAfterCreate( $reply, $data );
				$reply->sendCustomerNotifications( $values['to'], $values['cc'], $values['bcc'] );
				$reply->sendNotifications();
				
				$url = NULL;
				if ( isset( $data['ref'] ) and isset( $data['transaction'] ) )
				{
					try
					{
						$transaction = \IPS\nexus\Transaction::load( $data['transaction'] );
						$extra = $transaction->extra;
						$extra['sr'] = $transaction->id;
						$transaction->extra = $extra;
						$transaction->save();
						
						switch ( $data['ref'] )
						{
							case 'v':
								\IPS\Output::i()->redirect( $transaction->acpUrl() );
								break;
								
							case 'i':
								\IPS\Output::i()->redirect( $transaction->invoice->acpUrl() );
								break;
							
							case 'c':
								\IPS\Output::i()->redirect( $transaction->member->acpUrl() );
								break;
							
							case 't':
								\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=transactions') );
								break;
						}
					}
					catch ( \OutOfRangeException $e ) { }
				}
				if ( !$url )
				{
					$url = $request->canView() ? $request->acpUrl() : \IPS\Http\Url::internal('app=nexus&module=support&controller=requests');
				}
				\IPS\Output::i()->redirect( $url, 'request_created' );
			}
			
			return $form;
		};
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('create_support_request');
		\IPS\Output::i()->output = new \IPS\Helpers\Wizard( $steps, \IPS\Http\Url::internal('app=nexus&module=support&controller=requests&do=create') );
	}
}