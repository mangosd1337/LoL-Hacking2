<?php
/**
 * @brief		Support Request Model
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		8 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\Support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Request Model
 */
class _Request extends \IPS\Content\Item implements \IPS\Content\ReadMarkers
{
	/**
	 * @brief	Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	Application
	 */
	public static $application = 'nexus';
	
	/**
	 * @brief	Module
	 */
	public static $module = 'support';
	
	/**
	 * @brief	Database Table
	 */
	public static $databaseTable = 'nexus_support_requests';
	
	/**
	 * @brief	Database Prefix
	 */
	public static $databasePrefix = 'r_';
	
	/**
	 * @brief	Database Column Map
	 */
	public static $databaseColumnMap = array(
		'title'				=> 'title',
		'author'			=> 'member',
		'date'				=> 'started',
		'num_comments'		=> 'replies',
		'last_comment'		=> 'last_reply',
		'last_comment_by'	=> 'last_reply_by'
	);
	
	/**
	 * @brief	Title
	 */
	public static $title = 'support_request';
	
	/**
	 * @brief	Form language prefix
	 */
	public static $formLangPrefix = 'support_';
	
	/**
	 * @brief	Comment Class
	 */
	public static $commentClass = 'IPS\nexus\Support\Reply';
	
	/**
	 * @brief	[Content\Item]	First "comment" is part of the item?
	 */
	public static $firstCommentRequired = TRUE;

	/**
	 * @brief	Node Class
	 */
	public static $containerNodeClass = 'IPS\nexus\Support\Department';
	
	/* !Generic */
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	\IPS\Node\Model|NULL	$container	Container
	 * @return	void
	 */
	public static function incrementPostCount( \IPS\Node\Model $container = NULL )
	{
		return FALSE;
	}
	
	/**
	 * My Filters
	 *
	 * @param	array|null	$_filters	Filters (NULL to get from cookie)
	 * @return	array
	 */
	public static function myFilters( $_filters=null )
	{
		$myFilters = array();
		$myDepartmentIds = NULL;
		$myStatusIds = NULL;
		
		if ( $_filters === null and isset( \IPS\Request::i()->cookie['support_filters'] ) )
		{
			$_filters = json_decode( \IPS\Request::i()->cookie['support_filters'], TRUE );			
		}
		if ( $_filters !== null )
		{
			$myDepartmentIds = array_filter( $_filters['departments'] );
			$myStatusIds = array_filter( $_filters['statuses'] );
		}
		
		if ( !$myStatusIds )
		{
			$myStatusIds = iterator_to_array( \IPS\Db::i()->select( 'status_id', 'nexus_support_statuses', array( 'status_open=1' ) ) );
		}
		
		if ( !$myDepartmentIds )
		{
			$myDepartmentIds = iterator_to_array( \IPS\Db::i()->select( 'dpt_id', 'nexus_support_departments', "dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) ) );
		}
		
		$myFilters = \IPS\Db::i()->in( 'r_department', $myDepartmentIds ) . ' AND ' . \IPS\Db::i()->in( 'r_status', $myStatusIds );
		
		return array( 'whereClause' => $myFilters, 'departments' => $myDepartmentIds, 'statuses' => $myStatusIds );
	}
	
	/**
	 * Change Filters
	 *
	 * @param	array	$currentFilters	The current filters
	 * @param	int		$id				The ID to set/unset
	 * @return	array
	 */
	public static function changeFilters( $currentFilters, $id )
	{
		$index = array_search( $id, $currentFilters );
		if ( $index === FALSE )
		{
			$currentFilters[] = $id;
		}
		else
		{
			unset( $currentFilters[ $index ] );
		}
		return $currentFilters;
	}
	
	/**
	 * Support Desk Staff
	 *
	 * @return	array
	 */
	public static function staff()
	{
		/* Get details of the admin groups and members (this data is also in the data store so should be fast) */
		$administrators = \IPS\Member::administrators();
		$hash = json_encode( $administrators );
		
		/* If we don't have a supportStaff datastore, or if the administrator groups/members has changed since we stored it, work it out... */
		if ( !isset( \IPS\Data\Store::i()->supportStaff ) or \IPS\Data\Store::i()->supportStaff['hash'] !== $hash )
		{
			$members = array();
			
			/* Get the members who are admins for being in an admin group */
			if ( count( $administrators['g'] ) )
			{
				foreach ( \IPS\Db::i()->select( array( 'member_id', 'name' ), 'core_members', '( ' . \IPS\Db::i()->in( 'member_group_id', array_keys( $administrators['g'] ) ) . ' ) OR ( ' . \IPS\Db::i()->findInSet( 'mgroup_others', array_keys( $administrators['g'] ) ) . ' )' ) as $row )
				{
					$members[ $row['member_id'] ] = $row['name'];
				}
			}
			
			/* Get the members who are admins for being member-level admins */
			foreach ( \IPS\Db::i()->select( array( 'member_id', 'name' ), 'core_members', \IPS\Db::i()->findInSet( 'member_id', array_keys( $administrators['m'] ) ) ) as $row )
			{
				$members[ $row['member_id'] ] = $row['name'];
			}
			
			/* Sort alphabetically */
			asort( $members );
			
			/* Save to data store */
			\IPS\Data\Store::i()->supportStaff = array( 'hash' => $hash, 'members' => $members );
		}
		
		/* Return value from data store */
		return \IPS\Data\Store::i()->supportStaff['members'];
	}
	
	/**
	 * Table
	 *
	 * @param	\IPS\Http\Url	$url		URL where table is being displayed
	 * @param	array			$where		Initial where clause
	 * @param	string|NULL		$sortBy		If specified, overrides the sort column
	 * @param	string|NULL		$sortDir	If specified, overrides the sort firection
	 * @return	\IPS\Helpers\Table\Db
	 */
	public static function table( \IPS\Http\Url $url, $where, $sortBy=NULL, $sortDir=NULL )
	{
		$table = new \IPS\Helpers\Table\Db( 'nexus_support_requests', $url, $where );
		$table->joins = array(
			array(
				'select'	=> 'sev_position',
				'from'		=> 'nexus_support_severities',
				'where'		=> 'sev_id=r_severity'
			),
			array(
				'select'	=> 'dpt_position, dpt_staff',
				'from'		=> 'nexus_support_departments',
				'where'		=> 'dpt_id=r_department'
			),
			array(
				'select'	=> 'status_position',
				'from'		=> 'nexus_support_statuses',
				'where'		=> 'status_id=r_status'
			),
			array(
				'select'	=> 'request_id',
				'from'		=> 'nexus_support_tracker',
				'where'		=> array( 'request_id=r_id AND member_id=?', \IPS\Member::loggedIn()->member_id )
			)
		);
		$table->include = array( 'r_id', 'r_title', 'sev_position', 'dpt_position', 'r_member', 'r_replies', 'r_started', 'r_staff', 'r_last_new_reply', 'r_last_reply_by', 'r_last_reply', 'r_last_staff_reply', 'status_position' );
		$table->keyField = 'r_id';
		$table->extra = array();
		$highlightCutoff = NULL;
		if ( \IPS\Settings::i()->support_old_number )
		{
			$table->include[] = '_highlight';
			$highlightCutoff = \IPS\DateTime::create()->sub( new \DateInterval( 'PT' . \IPS\Settings::i()->support_old_number . 'M' ) )->getTimestamp();
			
		}
		if ( !$table->sortBy )
		{
			$table->sortBy = $sortBy ?: 'sev_position asc, r_last_new_reply';
		}

		if ( !$table->sortDirection )
		{
			$table->sortDirection = $sortDir ?: 'asc';
		}
		$table->parsers = array(
			'r_title'	=> function( $val, $row ) use ( $table )
			{
				$filter = NULL;
				$sort = NULL;
				if ( !isset( \IPS\Request::i()->member ) and !isset( \IPS\Request::i()->quicksearch ) and !isset( \IPS\Request::i()->advanced_search_submitted ) )
				{
					$filter = $table->filter;
					$sort = $table->sortBy . ' ' . $table->sortDirection;
				}

				return \IPS\Theme::i()->getTemplate('support')->link( \IPS\nexus\Support\Request::load( $row['r_id'] ), $row['request_id'], $filter, $sort );
			},
			'sev_position'=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate('support')->severity( \IPS\nexus\Support\Severity::load( $row['r_severity'] ) );
			},
			'dpt_position'=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate('support')->department( \IPS\nexus\Support\Department::load( $row['r_department'] ) );
			},
			'r_member'	=> function( $val, $row )
			{
				if ( $val )
				{
					return \IPS\Theme::i()->getTemplate('global')->userLink( \IPS\Member::load( $val ) );
				}
				else
				{
					return htmlspecialchars( $row['r_email'], \IPS\HTMLENTITIES, 'UTF-8', FALSE );
				}
			},
			'r_last_reply_by'	=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate('global')->userLink( \IPS\Member::load( $val ) );
			},
			'r_replies'	=> function( $val )
			{
				return --$val;
			},
			'r_started'	=> function( $val )
			{
				return \IPS\DateTime::ts( $val )->relative();
			},
			'r_staff' => function ( $val )
			{
				if ( $val )
				{
					return \IPS\Member::load( $val )->name;
				}
			},
			'r_last_new_reply'	=> function( $val )
			{
				return \IPS\DateTime::ts( $val )->relative();
			},
			'r_last_reply'	=> function( $val )
			{
				return \IPS\DateTime::ts( $val )->relative();
			},
			'r_last_staff_reply'	=> function( $val )
			{
				return $val ? \IPS\DateTime::ts( $val )->relative() : \IPS\Member::loggedIn()->language()->addToStack('never');
			},
			'status_position'		=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate('support')->status( \IPS\nexus\Support\Status::load( $row['r_status'] ) );
			},
			'_highlight'	=> function( $val, $row ) use ( $table, $highlightCutoff )
			{
				if ( $row[ \IPS\Settings::i()->support_old_stat ] < $highlightCutoff )
				{
					$table->extra[] = $row['r_id'];
				}
			}
		);
		
		return $table;
	}
		
	/* !Getters/Setters */
	
	/**
	 * Set Default Values
	 *
	 * @return	void
	 */
	public function setDefaultValues()
	{
		$emailKey = '';
		for ( $i = 0; $i < 3; $i++ )
		{
			do
			{
				$num   = mt_rand( 48, 122 );
			}
			while ( in_array( $num, array( 58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 94, 95, 96 ) ) );
			
			$emailKey .= chr( $num );
		}
		
		$this->email_key = $emailKey;
		$this->last_new_reply = time();
		$this->staff = NULL;
		$this->purchase = NULL;
	}
		
	/**
	 * Get department
	 *
	 * @return	\IPS\nexus\Support\Department
	 */
	public function get_department()
	{
		return Department::load( $this->_data['department'] );
	}
	
	/**
	 * Set department
	 *
	 * @param	\IPS\nexus\Support\Department
	 * @return	void
	 */
	public function set_department( Department $department )
	{
		$this->_data['department'] = $department->id;
	}
		
	/**
	 * Get purchase
	 *
	 * @return	\IPS\nexus\Purchase
	 */
	public function get_purchase()
	{
		try
		{
			return $this->_data['purchase'] ? \IPS\nexus\Purchase::load( $this->_data['purchase'] ) : NULL;
		}
		catch ( \OutOfRangeException $e )
		{
			return NULL;
		}
	}
	
	/**
	 * Set purchase
	 *
	 * @param	\IPS\nexus\Purchase
	 * @return	void
	 */
	public function set_purchase( \IPS\nexus\Purchase $purchase = NULL )
	{
		$this->_data['purchase'] = $purchase ? $purchase->id : 0;
	}
	
	/**
	 * Get status
	 *
	 * @return	\IPS\nexus\Support\Status
	 */
	public function get_status()
	{
		return Status::load( $this->_data['status'] );
	}
	
	/**
	 * Set purchase
	 *
	 * @param	\IPS\nexus\Support\Status
	 * @return	void
	 */
	public function set_status( Status $status )
	{
		$this->_data['status'] = $status->id;
	}
	
	/**
	 * Get severity
	 *
	 * @return	\IPS\nexus\Support\Severity
	 */
	public function get_severity()
	{
		return Severity::load( $this->_data['severity'] );
	}
	
	/**
	 * Set severity
	 *
	 * @param	\IPS\nexus\Support\Severity
	 * @return	void
	 */
	public function set_severity( Severity $severity )
	{
		$this->_data['severity'] = $severity->id;
	}
	
	/**
	 * Set last reply by
	 *
	 * @param	int	$lastReplyBy
	 * @return	void
	 */
	public function set_last_reply_by( $lastReplyBy )
	{
		if ( array_key_exists( $lastReplyBy, static::staff() ) )
		{
			$this->_data['last_staff_reply'] = time();
			\IPS\Db::i()->update( 'nexus_support_views', array( 'view_reply' => time() ), array( 'view_rid=? AND view_member=?', $this->id, $lastReplyBy ) );
		}
		elseif ( !isset( $this->_data['last_reply_by'] ) or $lastReplyBy != $this->_data['last_reply_by'] )
		{
			$this->_data['last_new_reply'] = time();
		}
		
		$this->_data['last_reply_by'] = $lastReplyBy;
	}
	
	/**
	 * Get staff
	 *
	 * @return	\IPS\Member|NULL
	 */
	public function get_staff()
	{
		return $this->_data['staff'] ? \IPS\Member::load( $this->_data['staff'] ) : NULL;
	}

	/**
	 * Set staff
	 *
	 * @param	\IPS\Member|NULL	$member	The staff member to assign to
	 * @return	void
	 */
	public function set_staff( \IPS\Member $member = NULL )
	{
		$this->_data['staff'] = $member ? $member->member_id : 0;
	}
	
	/**
	 * Get notify
	 *
	 * @return	array
	 */
	public function get_notify()
	{
		return isset( $this->_data['notify'] ) ? json_decode( $this->_data['notify'], TRUE ) : array();
	}

	/**
	 * Set notify
	 *
	 * @param	array	$notify	Notify data
	 * @return	void
	 */
	public function set_notify( array $notify )
	{
		$this->_data['notify'] = json_encode( $notify );
	}
	
	/**
	 * Get custom fields
	 *
	 * @return	array
	 */
	public function get_cfields()
	{
		return $this->_data['cfields'] ? json_decode( $this->_data['cfields'], TRUE ) : array();
	}

	/**
	 * Set custom fields
	 *
	 * @param	array	$values	Values
	 * @return	void
	 */
	public function set_cfields( array $values )
	{
		$this->_data['cfields'] = json_encode( $values );
	}
	
	/**
	 * Get PPI invoice
	 *
	 * @return	\IPS\nexus\Invoice|NULL
	 */
	public function get_ppi_invoice()
	{
		return $this->_data['ppi_invoice'] ? \IPS\nexus\Invoice::load( $this->_data['ppi_invoice'] ) : NULL;
	}
	
	/**
	 * Set PPI invoice
	 *
	 * @param	\IPS\nexus\Invoice|NULL
	 * @return	void
	 */
	public function set_ppi_invoice( \IPS\nexus\Invoice $invoice = NULL )
	{
		$this->_data['ppi_invoice'] = $invoice ? $invoice->id : NULL;
	}
	
	/* !Forms */
		
	/**
	 * Get elements for add/edit form
	 *
	 * @param	\IPS\Content\Item|NULL	$item		The current item if editing or NULL if creating
	 * @param	\IPS\Node\Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @return	array
	 */
	public static function formElements( $item=NULL, \IPS\Node\Model $container=NULL )
	{
		/* Account */
		if ( isset( \IPS\Request::i()->account ) and ( \IPS\Request::i()->account == \IPS\nexus\Customer::loggedIn()->member_id or array_key_exists( \IPS\Request::i()->account, iterator_to_array( \IPS\nexus\Customer::loggedIn()->parentContacts( array( 'support=1' ) ) ) ) ) )
		{
			$account = \IPS\nexus\Customer::load( \IPS\Request::i()->account );
			$purchaseWhere = array( array( 'ps_member=?', $account->member_id ) );
		}
		else
		{
			$account = \IPS\nexus\Customer::loggedIn();
			$purchaseWhere = array();
			foreach ( $account->parentContacts() as $contact )
			{
				foreach ( array_filter( $contact->purchaseIds() ) as $id )
				{
					$purchaseWhere[] = "ps_id={$id}";
				}
			}
			if ( count( $purchaseWhere ) )
			{
				$purchaseWhere = array( array( "ps_member={$account->member_id} OR ( " . implode( ' OR ', $purchaseWhere ) . ' )' ) );
			}
			else
			{
				$purchaseWhere = array( array( 'ps_member=?', $account->member_id ) );
			}
		}
						
		/* Basic elements */
		$return = parent::formElements( $item, $container );
		$content = $return['content'];
		unset( $return['content'] );
		unset( $return['container'] );
		
		/* Init */
		$availableDepartments = array();
		$doNotShowPurchasesForDepartments = array();
		foreach ( Department::roots( NULL, NULL, 'dpt_open=1' ) as $department )
		{
			if ( !\IPS\nexus\Customer::loggedIn()->member_id or !count( \IPS\Db::i()->select( '*', 'nexus_purchases', array_merge( $purchaseWhere, array( array( 'ps_app=? AND ps_type=?', 'nexus', 'package' ) ), array( \IPS\Db::i()->in( 'ps_item_id', explode( ',', $department->packages ) ) ) ) ) ) )
			{
				if ( $department->require_package )
				{
					continue;
				}
				else
				{
					$doNotShowPurchasesForDepartments[] = $department->id;
				}
			}
			
			$availableDepartments[ $department->id ] = $department;
		}
		if ( !count( $availableDepartments ) )
		{
			\IPS\Output::i()->error( \IPS\nexus\Customer::loggedIn()->member_id ? 'no_module_permission' : 'no_module_permission_guest', '1X248/1', 403, 'no_support_departments' );
		}
		$departmentToggles = array();
		
		/* What custom fields need to be triggered by departments? */
		$customFields = \IPS\nexus\Support\CustomField::roots( NULL, NULL, "sf_departments='*' OR " . \IPS\Db::i()->findInSet( 'sf_departments', array_keys( $availableDepartments ) ) );
		foreach ( $customFields as $field )
		{
			if ( $field->departments and $field->departments !== '*' )
			{
				foreach ( explode( ',', $field->departments ) as $departmentId )
				{
					if ( !isset( $departmentToggles[ $departmentId ] ) )
					{
						$departmentToggles[ $departmentId ] = array();
					}
					$departmentToggles[ $departmentId ][] = "nexus_cfield_{$field->id}";
				}
			}
		}
		
		/* What purchase fields need to be triggered by departments? */
		$departments = array();
		$purchaseFields = array();
		$ppiDepartments = array();
		foreach ( $availableDepartments as $department )
		{
			$departments[ $department->id ] = $department->_title;
			
			if ( !isset( $departmentToggles[ $department->id ] ) )
			{
				$departmentToggles[ $department->id ] = array();
			}
			$departmentToggles[ $department->id ][] = "department_message_{$department->id}";
			
			if ( $department->ppiCost() and !\IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=? AND ps_member=?', 'nexus', 'ppi', $department->id, \IPS\Member::loggedIn()->member_id ) )->first() )
			{
				$content->required = FALSE;
				if ( $content->error === 'form_required' )
				{
					$content->error = NULL;
				}
				$ppiDepartments[] = $department->id;
			}
			else
			{
				$departmentToggles[ $department->id ][] = static::$formLangPrefix . 'content_editor';
				if ( $department->packages and !in_array( $department->id, $doNotShowPurchasesForDepartments ) )
				{
					$allowedPackageIds = explode( ',', $department->packages );
					$allowedPackages = $department->serializedPackageIds();		
					$departmentToggles[ $department->id ][] = 'support_purchase_' . $allowedPackages;		
					if ( !isset( $purchaseFields[ $allowedPackages ] ) )
					{				
						$field = new \IPS\Helpers\Form\Node(
							'support_purchase_' . $allowedPackages,
							isset( \IPS\Request::i()->purchase ) ? \IPS\Request::i()->purchase : NULL,
							$department->require_package ? NULL : FALSE,
							array(
								'class'				=> 'IPS\nexus\Purchase',
								'where'				=> $purchaseWhere,
								'permissionCheck'	=> function( $node ) use ( $allowedPackageIds )
								{
									return $node->active and $node->app === 'nexus' and $node->type === 'package' and in_array( $node->item_id, $allowedPackageIds );
								}
							),
							$department->require_package ? function( $val ) use ( $allowedPackages, $departmentToggles )
							{								
								if ( !$val and in_array( 'support_purchase_' . $allowedPackages, $departmentToggles[ \IPS\Request::i()->support_department ] ) )
								{
									throw new \DomainException('support_purchase_required');
								}
							} : NULL,
							NULL,
							NULL,
							'support_purchase_' . $allowedPackages
						);
						$field->label = \IPS\Member::loggedIn()->language()->addToStack('support_purchase');
						$purchaseFields[ $allowedPackages ] = $field;
					}					
				}
			}
		}
				
		/* Do severities need to be triggered by departments? */
		$defaultSeverityId = Severity::load( TRUE, 'sev_default' )->id;
		$severityFields = array();
		foreach ( $availableDepartments as $department )
		{
			if ( count( $department->availableSeverities() ) > 1 )
			{
				$serialized = "severity_{$department->serializedSeverityIds()}";
				
				if ( !isset( $severityFields[ $serialized ] ) )
				{
					$options = array();
					foreach ( $department->availableSeverities() as $severity )
					{
						$options[ $severity->id ] = "nexus_severity_{$severity->id}";
					}
					
					$field = new \IPS\Helpers\Form\Radio( 'support_' . $serialized, in_array( $defaultSeverityId, array_keys( $options ) ) ? $defaultSeverityId : NULL, FALSE, array( 'options' => $options ), NULL, NULL, NULL, 'support_' . $serialized );
					$field->label = \IPS\Member::loggedIn()->language()->addToStack('support_severity');
					$severityFields[ $serialized ] = $field;
				}
				
				if ( !isset( $departmentToggles[ $department->id ] ) )
				{
					$departmentToggles[ $department->id ] = array();
				}
				$departmentToggles[ $department->id ][] = 'support_' . $serialized;
			}
		}
				
		/* Add department */
		$return['department'] = new \IPS\Helpers\Form\Select( 'support_department', isset( \IPS\Request::i()->department ) ? \IPS\Request::i()->department : NULL, TRUE, array( 'options' => $departments, 'toggles' => $departmentToggles ) );
		foreach ( $availableDepartments as $department )
		{
			if ( \IPS\Member::loggedIn()->language()->checkKeyExists("nexus_department_{$department->id}_desc") )
			{
				$return[ "department_message_{$department->id}" ] = \IPS\Member::loggedIn()->language()->addToStack("nexus_department_{$department->id}_desc");
			}
		}
		
		/* Severity */
		$return = array_merge( $return, $severityFields );
		
		/* Purchase */
		$return = array_merge( $return, $purchaseFields );
		
		/* Custom Fields */
		foreach ( $customFields as $field )
		{
			$validation = NULL;
			if ( $field->required )
			{
				$validation = function( $val ) use ( $field, $departmentToggles ) {
					if ( $val === '' and ( !isset( \IPS\Request::i()->support_department ) or in_array( "nexus_cfield_{$field->id}", $departmentToggles[ \IPS\Request::i()->support_department ] ) ) )
					{
						throw new \DomainException('form_required');
					}
				};
			}
			
			$return[] = $field->buildHelper( NULL, $validation );
		}
				
		/* Return */
		$return['content'] = $content;
		return $return;
	}
		
	/**
	 * Process create/edit form
	 *
	 * @param	array				$values	Values from form
	 * @return	void
	 */
	public function processForm( $values )
	{
		parent::processForm( $values );
				
		/* Pay-Per-Incident? */
		$department = Department::load( $values['support_department'] );
		if ( $ppiCost = $department->ppiCost() and $ppiCost->amount->compare( new \IPS\Math\Number('0') ) === 1 )
		{
			try
			{
				$purchase = \IPS\nexus\Purchase::constructFromData( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'ppi', $department->id ) )->first() );				
				$this->ppi_invoice = $purchase->original_invoice;
				$purchase->delete();
			}
			catch ( \UnderflowException $e )
			{
				$invoice = new \IPS\nexus\Invoice;
				$invoice->member = \IPS\nexus\Customer::loggedIn();
				$invoice->return_uri = "app=nexus&module=support&controller=home&do=create&department={$department->id}&title=" . urlencode( $values['support_title'] );
	
				$item = new \IPS\nexus\extensions\nexus\Item\SupportCharge( \IPS\Member::loggedIn()->language()->get( 'nexus_department_' . $department->_id ), $ppiCost );
				$item->id = $department->id;
				if ( $department->ppi_tax )
				{
					try
					{
						$item->tax = \IPS\nexus\Tax::load( $department->ppi_tax );
					}
					catch ( \OutOfRangeException $e ) {}
				}
				$invoice->addItem( $item );
				
				$invoice->save();
				\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
			}
		}
		$this->department = $department;
		
		/* Purchase */
		if ( $department->packages and isset( $values[ 'support_purchase_' . $department->serializedPackageIds() ] ) )
		{
			$purchase = $values[ 'support_purchase_' . $department->serializedPackageIds() ];
			if ( $purchase )
			{
				$this->purchase = $values[ 'support_purchase_' . $department->serializedPackageIds() ];
			}
		}
		
		/* Account */
		if ( isset( \IPS\Request::i()->account ) and ( \IPS\Request::i()->account == \IPS\nexus\Customer::loggedIn()->member_id or array_key_exists( \IPS\Request::i()->account, iterator_to_array( \IPS\nexus\Customer::loggedIn()->parentContacts( array( 'support=1' ) ) ) ) ) )
		{
			$this->member = \IPS\nexus\Customer::load( \IPS\Request::i()->account )->member_id;
		}
		elseif ( $this->purchase )
		{
			$this->member = $this->purchase->member->member_id;
		}
		
		/* Status */
		if ( !isset( $values['support_status'] ) )
		{
			$this->status = Status::load( TRUE, 'status_default_member' );
		}
		
		/* Selected Severity */
		$defaultSeverity = Severity::load( TRUE, 'sev_default' );
		if ( isset( $values[ 'support_severity_' . $department->serializedSeverityIds() ] ) )
		{
			$this->severity = Severity::load( $values[ 'support_severity_' . $department->serializedSeverityIds() ] );
		}
		else
		{
			$availableSeverities = $department->availableSeverities();			
			if ( count( $availableSeverities ) === 1 )
			{
				$this->severity = array_pop( $availableSeverities );
			}
			else
			{
				$this->severity = $defaultSeverity;
			}
		}
				
		/* Purchase sets its own severity? */
		if ( $this->severity->id === $defaultSeverity->id and $this->purchase )
		{
			if ( $overrideSeverity = $this->purchase->supportSeverity() )
			{
				$this->severity = $overrideSeverity;
			}
			elseif ( $parent = $this->purchase->parent() and $overrideSeverity = $parent->supportSeverity() )
			{
				$this->severity = $overrideSeverity;
			}
			else
			{
				foreach ( $this->purchase->children() as $childPurchase )
				{
					if ( $overrideSeverity = $childPurchase->supportSeverity() )
					{
						$this->severity = $overrideSeverity;
						break;
					}
				}
			}
		}
		
		/* Custom Fields */
		$customFieldObjects = CustomField::roots();
		$cfields = array();
		foreach ( $values as $k => $v )
		{
			if ( mb_substr( $k, 0, 13 ) === 'nexus_cfield_' )
			{
				$k = mb_substr( $k, 13 );
				$class = $customFieldObjects[ $k ]->buildHelper();
				$cfields[ $k ] = $class::stringValue( $v );
			}
		}
		$this->cfields = $cfields;
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	\IPS\Content\Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	public function processAfterCreate( $comment, $values )
	{
		parent::processAfterCreate( $comment, $values );
		
		$this->afterCreateLog( $comment );
	}
	
	/**
	 * Log that the request was created
	 *
	 * @param	\IPS\nexus\Support\Reply	The first message
	 * @return	void
	 */
	public function afterCreateLog( $reply )
	{
		if ( $this->member )
		{
			$data = array( 'id' => $this->id, 'title' => $this->title );
			
			if ( $reply->type === Reply::REPLY_STAFF )
			{
				$data['type'] = 'staff';
			}
			elseif ( $reply->type === Reply::REPLY_EMAIL )
			{
				$data['type'] = 'email';
			}
			
			/* We pass the third parameter here because this can be called from CLI (i.e. if a cron job runs the incoming email task)
				which will cause an exception in Customer::log() trying to load the currently logged in user, as there won't be one */
			\IPS\nexus\Customer::load( $this->member )->log( 'support', $data, $reply->author()->member_id ? $reply->author() : FALSE );
		}
	}
	
	/**
	 * Set buttons
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @param	string				$key	'note' or 'reply'
	 * @return	void
	 */
	protected function _staffFormButtons( \IPS\Helpers\Form $form, $key )
	{
		if ( isset( \IPS\Request::i()->sort ) )
		{
			$form->hiddenValues['sort'] = \IPS\Request::i()->sort;
			$form->hiddenValues['filter'] = \IPS\Request::i()->filter;
			
			$form->addButton( $key . '_and_return_to_list', 'submit', NULL, 'ipsButton ipsButton_alternate', array( 'name' => 'goto', 'value' => 'list' ) );
			
			if ( $next = $this->nextPrevious( \IPS\Request::i()->sort, \IPS\Request::i()->filter ) )
			{
				$form->addButton( $key . '_and_go_to_next', 'submit', NULL, 'ipsButton ipsButton_alternate', array( 'name' => 'goto', 'value' => 'next' ) );
			}
		}
		else
		{
			$form->addButton( $key . '_and_go_to_list', 'submit', NULL, 'ipsButton ipsButton_alternate', array( 'name' => 'goto', 'value' => 'list' ) );
		}
	}
	
	/**
	 * Redirect after submission
	 *
	 * @param	bool	$pending	If the submitted comment is pending
	 * @return	void
	 */
	protected function _staffFormRedirect( $pending=FALSE )
	{
		if ( !$pending and isset( \IPS\Request::i()->goto ) )
		{
			switch ( \IPS\Request::i()->goto )
			{
				case 'next':
					if ( $next = $this->nextPrevious( \IPS\Request::i()->sort, \IPS\Request::i()->filter ) )
					{
						\IPS\Output::i()->redirect( $next->acpUrl()->setQueryString( array( 'sort' => \IPS\Request::i()->sort, 'filter' => \IPS\Request::i()->filter ) ) );
					}
				break;
				
				case 'list':
					$listUrl = \IPS\Http\Url::internal("app=nexus&module=support&controller=requests");
					if ( isset( \IPS\Request::i()->sort ) )
					{
						preg_match( '/^(([a-z_]+(, )?)+)( ((a|de)sc))?$/i', trim( \IPS\Request::i()->sort ), $matches );
						if ( isset( $matches[1] ) )
						{
							$listUrl = $listUrl->setQueryString( 'sortby', $matches[1] );
						}
						if ( isset( $matches[5] ) )
						{
							$listUrl = $listUrl->setQueryString( 'sortdirection', $matches[5] );
						}
					}
					if ( isset( \IPS\Request::i()->filter ) )
					{
						$listUrl = $listUrl->setQueryString( 'filter', \IPS\Request::i()->filter );
					}
					\IPS\Output::i()->redirect( $listUrl );
				break;
			}
		}
		
		$lastPageUrl = $this->acpUrl();
		$order = isset( \IPS\Request::i()->order ) ? \IPS\Request::i()->order : ( isset( \IPS\Request::i()->cookie['support_order'] ) ? \IPS\Request::i()->cookie['support_order'] : 'desc' );
		if ( $order === 'asc' )
		{
			$lastPageUrl = $lastPageUrl->setQueryString( 'page', $this->commentPageCount() );
		}
		if ( $pending )
		{
			$lastPageUrl = $lastPageUrl->setQueryString( 'pending', 1 );
		}

		\IPS\Output::i()->redirect( $lastPageUrl );
	}
	
	/**
	 * Add Note Form
	 *
	 * @return	\IPS\Helpers\Form
	 */
	public function noteForm()
	{
		$form = new \IPS\Helpers\Form( 'note', 'add_note' );
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Editor( 'note', NULL, TRUE, array( 'app' => 'nexus', 'key' => 'Support', 'minimize' => 'support__note_placeholder', 'autoSaveKey' =>  "req{$this->id}-note" ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'department', $this->department->id, FALSE, array( 'options' => \IPS\nexus\Support\Department::rootsAsArray() ), NULL, NULL, NULL, 'staffNoteDepartment' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'status', $this->status->id, FALSE, array( 'options' => \IPS\nexus\Support\Status::rootsAsArray() ), NULL, NULL, NULL, 'staffNoteStatus' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'assign_to', $this->staff ? $this->staff->member_id : 0, FALSE, array( 'options' => array( 0 => 'unassigned' ) + static::staff() ), NULL, NULL, NULL, 'staffNoteAssign' ) );

		/* Buttons */
		$this->_staffFormButtons( $form, 'note' );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$message = new Reply;
			$message->request = $this->id;
			$message->member = \IPS\Member::loggedIn()->member_id;
			$message->type = Reply::REPLY_HIDDEN;
			$message->post = $values['note'];
			$message->hidden = TRUE;
			$message->date = time();
			$message->ip_address = \IPS\Request::i()->ipAddress();
			$message->save();
			
			\IPS\File::claimAttachments( "req{$this->id}-note", $this->id, $message->id );
			
			/* Update the request */
			$this->department = Department::load( $values['department'] );
			$this->status = Status::load( $values['status'] );
			$this->staff = $values['assign_to'] ? \IPS\Member::load( $values['assign_to'] ) : NULL;
			$this->save();

			$message->sendNotifications();

			/* Redirect */
			$this->_staffFormRedirect();
		}
		return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'support', 'nexus' ) ), 'noteForm' ), $this );
	}
	
	/**
	 * Staff Reply Form
	 *
	 * @return	\IPS\Helpers\Form
	 */
	public function staffReplyForm()
	{	
		/* Init */
		$form = new \IPS\Helpers\Form( 'reply', 'reply' );
		$form->attributes = array( 'data-controller' => 'nexus.admin.support.staffreply' );
		$lastReply = $this->comments( 1, 0, 'date', 'desc' );
		$lastReplyId = $lastReply->id;
		$form->hiddenValues['latestReply'] = $lastReplyId;
				
		/* Stock Actions */
		$stockActions = array( 0 => '' );
		foreach ( \IPS\nexus\Support\StockAction::roots( NULL, NULL, "action_show_in='*' OR " . \IPS\Db::i()->findInSet( 'action_show_in', array( $this->department->id ) ) ) as $action )
		{
			$stockActions[ $action->id ] = $action->_title;
		}
		
		/* To/Cc/Bcc */
		$defaultRecipients = $this->getDefaultRecipients();
		$form->add( new \IPS\Helpers\Form\Email( 'to', $defaultRecipients['to'], FALSE, array( 'disabled' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'cc', $defaultRecipients['cc'], FALSE, array( 'autocomplete' => array( 'unique' => TRUE, 'forceLower' => TRUE ) ) ) );
		$form->add( new \IPS\Helpers\Form\Text( 'bcc', $defaultRecipients['bcc'], FALSE, array( 'autocomplete' => array( 'unique' => TRUE, 'forceLower' => TRUE ) ) ) );
		
		/* Attributes */
		if ( count( \IPS\nexus\Support\StockAction::roots() ) )
		{
			$form->add( new \IPS\Helpers\Form\Select( 'stock_action', NULL, FALSE, array( 'options' => $stockActions ), NULL, NULL, NULL, 'stock_action' ) );
		}
		$form->add( new \IPS\Helpers\Form\Editor( 'message', NULL, TRUE, array( 'app' => 'nexus', 'key' => 'Support', 'minimize' => 'support__comment_placeholder', 'autoSaveKey' => "req{$this->id}-reply" ) ) );
		$form->add( new \IPS\Helpers\Form\Select( 'department', $this->department->id, FALSE, array( 'options' => \IPS\nexus\Support\Department::rootsAsArray() ), NULL, NULL, NULL, 'staffReplyDepartment' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'status', \IPS\nexus\Support\Status::load( TRUE, 'status_default_staff' )->id, FALSE, array( 'options' => \IPS\nexus\Support\Status::rootsAsArray() ), NULL, NULL, NULL, 'staffReplyStatus' ) );
		$form->add( new \IPS\Helpers\Form\Select( 'assign_to', $this->staff_lock ? ( $this->staff ? $this->staff->member_id : 0 ) : 0, FALSE, array( 'options' =>  array( 0 => 'unassigned' ) + static::staff() ), NULL, NULL, NULL, 'staffReplyAssign' ) );
		
		/* Buttons */
		$this->_staffFormButtons( $form, 'reply' );
		
		/* Handle Submissions */
		if ( $values = $form->values() )
		{
			/* Update notify */
			$notify = $this->notify;
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
			$this->notify = $notify;
			
			/* Are we blocking this? */
			$pending = FALSE;
			if ( \IPS\Request::i()->latestReply != $lastReplyId )
			{
				$pending = TRUE;
			}
						
			/* Create the message */
			$message = new Reply;
			$message->request = $this->id;
			$message->member = \IPS\Member::loggedIn()->member_id;
			$message->type = $pending ? Reply::REPLY_PENDING : Reply::REPLY_STAFF;
			$message->post = $values['message'];
			$message->hidden = $pending;
			$message->date = time();
			$message->cc = implode( ',', $values['cc'] );
			$message->bcc = implode( ',', $values['bcc'] );
			$message->ip_address = \IPS\Request::i()->ipAddress();
			$message->save();
			\IPS\File::claimAttachments( "req{$this->id}-reply", $this->id, $message->id );
			$message->postCreate();
						
			/* Update the request */
			$newDepartment = Department::load( $values['department'] );
			if ( $this->department != $newDepartment )
			{
				$this->log( 'department', $this->department, $newDepartment );
				$this->department = $newDepartment;
			}
			$this->status = Status::load( $values['status'] );
			$newStaff = $values['assign_to'] ? \IPS\Member::load( $values['assign_to'] ) : NULL;
			if ( $this->staff != $newStaff )
			{
				$this->log( 'staff', $this->staff, $newStaff );
				$this->staff = $newStaff;
			}
			$this->save();
			
			/* Send notifications */
			if ( !$pending )
			{
				$message->sendCustomerNotifications( $values['to'] ?: $defaultRecipients['to'], $values['cc'], $values['bcc'] );
				$message->sendNotifications();
			}
			
			/* Redirect */
			$this->_staffFormRedirect( $pending );
		}
		
		/* Display */
		return $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'support', 'nexus' ) ), 'staffReplyForm' ), $this );
	}
	
	/**
	 * Validate email
	 *
	 * @param	string	$email	Email address
	 * @return	void
	 * @throws	\DomainException
	 */
	public static function _validateEmail( $email )
	{
		if ( $email and filter_var( $email, FILTER_VALIDATE_EMAIL ) === FALSE )
		{
			throw new \InvalidArgumentException('form_email_bad');
		}
	}
	
	/* !Permissions */
	
	/**
	 * Get items with permisison check
	 *
	 * @param	array		$where				Where clause
	 * @param	string		$order				MySQL ORDER BY clause (NULL to order by date)
	 * @param	int|array	$limit				Limit clause
	 * @param	string|NULL	$permissionKey		A key which has a value in the permission map (either of the container or of this class) matching a column ID in core_permission_index or NULL to ignore permissions
	 * @param	mixed		$includeHiddenItems	Include hidden items? NULL to detect if currently logged in member has permission, -1 to return public content only, TRUE to return unapproved content and FALSE to only return unapproved content the viewing member submitted
	 * @param	int			$queryFlags			Select bitwise flags
	 * @param	\IPS\Member	$member				The member (NULL to use currently logged in member)
	 * @param	bool		$joinContainer		If true, will join container data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinComments		If true, will join comment data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$joinReviews		If true, will join review data (set to TRUE if your $where clause depends on this data)
	 * @param	bool		$countOnly			If true will return the count
	 * @param	array|null	$joins				Additional arbitrary joins for the query
	 * @param	mixed		$skipPermission		If you are getting records from a specific container, pass the container to reduce the number of permission checks necessary or pass TRUE to skip conatiner-based permission. You must still specify this in the $where clause
	 * @param	bool		$joinTags			If true, will join the tags table
	 * @param	bool		$joinAuthor			If true, will join the members table for the author
	 * @param	bool		$joinLastCommenter	If true, will join the members table for the last commenter
	 * @param	bool		$showMovedLinks		If true, moved item links are included in the results
	 * @return	\IPS\Patterns\ActiveRecordIterator|int
	 */
	public static function getItemsWithPermission( $where=array(), $order=NULL, $limit=10, $permissionKey='read', $includeHiddenItems=\IPS\Content\Hideable::FILTER_AUTOMATIC, $queryFlags=0, \IPS\Member $member=NULL, $joinContainer=FALSE, $joinComments=FALSE, $joinReviews=FALSE, $countOnly=FALSE, $joins=NULL, $skipPermission=FALSE, $joinTags=TRUE, $joinAuthor=TRUE, $joinLastCommenter=TRUE, $showMovedLinks=FALSE )
	{	
		/* Get customer object */
		if ( !$member )
		{
			$member = \IPS\nexus\Customer::loggedIn();
		}
		elseif ( !( $member instanceof \IPS\nexus\Customer ) )
		{
			$member = \IPS\nexus\Customer::load( $member->member_id );
		}
		$extraClause = array( 'r_member=?', $member->member_id );
		
		/* Work out the clause for parent alternative contacts */
		$alternativeContactWhere = array();
		foreach ( $member->parentContacts() as $contact )
		{
			if ( $contact->support )
			{
				$alternativeContactWhere[] = '( r_member=' . $contact->main_id->member_id . ' )';
			}
			else
			{
				$alternativeContactWhere[] = '( r_member=' . $contact->main_id->member_id . ' AND ' . \IPS\Db::i()->in( 'r_purchase', $contact->purchaseIds() ) . ' )';
			}
		}
		if ( count( $alternativeContactWhere ) )
		{
			$extraClause[0] = '( ' . $extraClause[0] . ' OR ( ' . implode( ' OR ', $alternativeContactWhere ) . ' ) )';
		}		
		
		/* Work out the clause for admins */
		if ( ( !\IPS\Dispatcher::hasInstance() or \IPS\Dispatcher::i()->controllerLocation === 'admin' ) and $member->isAdmin() )
		{
			$extraClause[0] = '( ' . $extraClause[0] . " OR dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', Department::staffDepartmentPerms( $member ) ) . ' )';
			$joins[] = array(
				'from'		=> 'nexus_support_departments',
				'where'		=> 'dpt_id=r_department'
			);
		}
		
		/* Do it */
		$where[] = $extraClause;
		return parent::getItemsWithPermission( $where, $order, $limit, $permissionKey, $includeHiddenItems, $queryFlags, $member, $joinContainer, $joinComments, $joinReviews, $countOnly, $joins, $skipPermission, $joinTags, $joinAuthor, $joinLastCommenter, $showMovedLinks );
	}
	
	/**
	 * Whether we're viewing the last page of reviews/comments on this item
	 *
	 * @param	string	$type		"reviews" or "comments"
	 * @return	boolean
	 */
	public function isLastPage( $type='comments' )
	{
		/* If we are viewing as an administrator, we can be viewing either as oldest first or newest first, so we need to adjust here so Read Markers can be properly updated (if sorting newest to oldest, then page 1 would be the last page) */
		if ( \IPS\Dispatcher::hasInstance() AND \IPS\Dispatcher::i()->controllerLocation === 'admin' )
		{
			if ( ( isset( \IPS\Request::i()->cookie['support_order'] ) AND \IPS\Request::i()->cookie['support_order'] === 'asc' ) OR ( isset( \IPS\Request::i()->order ) AND \IPS\Request::i()->order === 'asc' ) )
			{
				/* Sorting oldest to newest - we can just pass off to the main method */
				return parent::isLastPage( $type );
			}
			else
			{
				/* Sorting by newest to oldest - if we are on page 1, or a page isn't specified, then yes we are on the last page. */
				if ( !isset( \IPS\Request::i()->page ) OR \IPS\Request::i()->page == 1 )
				{
					return TRUE;
				}
				else
				{
					return FALSE;
				}
			}
		}
		
		/* Still here? We're viewing as a user, which always shows oldest to newest. */
		return parent::isLastpage( $type );
	}
	
	/**
	 * Can view?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for or NULL for the currently logged in member
	 * @return	bool
	 */
	public function canView( $member=NULL )
	{
		/* Get customer object */
		if ( !$member )
		{
			$member = \IPS\nexus\Customer::loggedIn();
		}
		elseif ( !( $member instanceof \IPS\nexus\Customer ) )
		{
			$member = \IPS\nexus\Customer::load( $member->member_id );
		}
		
		/* Owner */
		if ( $member->member_id == $this->author()->member_id )
		{
			return TRUE;
		}
		
		/* Staff */
		elseif ( ( !\IPS\Dispatcher::hasInstance() or \IPS\Dispatcher::i()->controllerLocation === 'admin' ) and $member->isAdmin() and ( $this->department->staff === '*' or count( array_intersect( explode( ',', $this->department->staff ), Department::staffDepartmentPerms( $member ) ) ) ) )
		{
			return TRUE;
		}
		
		/* Altcontact */
		elseif ( in_array( $this->author()->member_id, array_keys( iterator_to_array( $member->parentContacts( $this->purchase ? array( 'support=1 OR ' . \IPS\Db::i()->findInSet( 'purchases', array( $this->purchase->id ) ) ) : array( 'support=1' ) ) ) ) ) )
		{
			return TRUE;
		}		
		
		return FALSE;
	}
	
	/**
	 * Can view hidden comments on this item?
	 *
	 * @param	\IPS\Member|NULL	$member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canViewHiddenComments( $member=NULL )
	{
		if ( $member === NULL )
		{
			$member = \IPS\Member::loggedIn();
		}
		
		$return = parent::canViewHiddenComments( $member );
		
		/* Is the member a staff member with access to this department? They may not be a moderator on the front-end, so the parent method will return false */
		if ( $return === FALSE and ( !\IPS\Dispatcher::hasInstance() or \IPS\Dispatcher::i()->controllerLocation === 'admin' ) and $member->isAdmin() and ( $this->department->staff === '*' or count( array_intersect( explode( ',', $this->department->staff ), Department::staffDepartmentPerms( $member ) ) ) ) )
		{
			$return = TRUE;
		}
		
		return $return;
	}
	
	
	/**
	 * Can comment?
	 *
	 * @param	\IPS\Member\NULL	$member	The member (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canComment( $member=NULL )
	{
		return parent::canComment( $member ) and !$this->status->is_locked;
	}
	
	/**
	 * Should new comments be moderated?
	 *
	 * @param	\IPS\Member	$member	The member posting
	 * @return	bool
	 */
	public function moderateNewComments( \IPS\Member $member )
	{
		return FALSE; // Even if the member is on mod queue, that doesn't apply to support requests
	}
	
	/* !Notifications */
	
	/**
	 * Get default to/cc/bcc
	 *
	 * @return	array
	 */
	public function getDefaultRecipients()
	{
		$to = $this->member ? $this->author()->email : $this->email;
		$cc = array();
		$bcc = array();
		foreach ( $this->notify as $notify )
		{
			try
			{
				$email = ( $notify['type'] === 'm' ) ? \IPS\Member::load( $notify['value'] )->email : $notify['value'];
			}
			catch ( \OutOfRangeException $e )
			{
				continue;
			}
			if ( $email != $to )
			{
	 			if ( isset( $notify['bcc'] ) and $notify['bcc'] )
				{
					$bcc[] = $email;
				}
				else
				{
					$cc[] = $email;
				}
			}
		}
		
		return array( 'to' => $to, 'cc' => $cc, 'bcc' => $bcc );
	}
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications()
	{		
		$staffIds = array_keys( static::staff() );
				
		foreach ( \IPS\Db::i()->select( 'staff_id', 'nexus_support_notify', array( array( 'type=?', 'n' ), array( "(departments='*' OR " . \IPS\Db::i()->findInSet( 'departments', array( $this->department->id ) ) . ')' ) ) ) as $staffId )
		{
			if ( in_array( $staffId, $staffIds ) )
			{
				$member = \IPS\Member::load( $staffId );

				if ( $this->department->staff === '*' or count( array_intersect( \IPS\nexus\Support\Department::staffDepartmentPerms( $member ), explode( ',', $this->department->staff ) ) ) )
				{
					$fromEmail = ( $this->department->email ) ? $this->department->email : \IPS\Settings::i()->email_out;
					switch ( \IPS\Settings::i()->nexus_sout_from )
					{
						case 'staff':
							$fromName = $this->supportAuthor()->name();
							break;
						case 'dpt':
							$fromName = $email->language->get( 'nexus_department_' . $this->department->_id );
							break;
						default:
							$fromName = \IPS\Settings::i()->nexus_sout_from;
							break;
					}
					
					\IPS\Email::buildFromTemplate( 'nexus', 'staffNotifyNew', array( $this, $this->comments( 1, 0, 'date', 'asc', NULL, FALSE ) ), \IPS\Email::TYPE_LIST )
						->setUnsubscribe( 'nexus', 'unsubscribeStaffNotify' )
						->send( $member, array(), array(), $fromEmail, $fromName );
				}
			}
			else
			{
				\IPS\Db::i()->delete( 'nexus_support_notify', array( 'staff_id=?', $staffId ) );
			}
		}
	}
	
	/* !Other */
	
	/**
	 * Get replies, and the log, for staff view
	 *
	 * @param	string	$orderDirection	"asc" or "desc"
	 * @return	\IPS\Patterns\UnionIterator
	 */
	public function repliesAndLog( $orderDirection='asc' )
	{
		$orderDirection = ( $orderDirection === 'desc' ) ? $orderDirection : 'asc';
		$replies = $this->comments( NULL, NULL, 'date', $orderDirection );
		
		$where = array( array( 'rlog_request=?', $this->id ) );
		$first = NULL;
		foreach ( $replies as $last )
		{
			if ( $first === NULL )
			{
				$first = $last;
			}
		}
		if ( $first )
		{
			if ( $orderDirection === 'asc' )
			{
				$where[] = array( 'rlog_date>?', $first->date );
				
				if ( $firstOnNextPage = $this->comments( 1, NULL, 'date', 'asc', NULL, NULL, \IPS\DateTime::ts( $last->date ) ) )
				{
					$where[] = array( 'rlog_date<?', $firstOnNextPage->date );
				}
			}
			else
			{
				$where[] = array( 'rlog_date>?', $last->date );
				
				if ( $firstOnNextPage = $this->comments( 1, NULL, 'date', 'asc', NULL, NULL, NULL, array( 'reply_date<?', $last->date ) ) )
				{
					$where[] = array( 'rlog_date<?', $firstOnNextPage->date );
				}
			}
		}
		
		$iterator = new \IPS\Patterns\UnionIterator( $orderDirection );
		$iterator->attachIterator( new \ArrayIterator( $replies ), 'date' );
		$iterator->attachIterator( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_support_request_log', $where, 'rlog_date ' . $orderDirection ), 'IPS\nexus\Support\Log' ), 'date' );		
		return $iterator;
	}
	
	/**
	 * @brief	Author
	 */
	protected $_author;
	
	/**
	 * Get author
	 *
	 * @return	\IPS\nexus\Support\Author
	 */
	public function supportAuthor()
	{
		if ( $this->_author === NULL )
		{
			if ( $this->member )
			{
				try
				{
					$this->_author = new Author\Member( \IPS\nexus\Customer::load( $this->member ) );
				}
				catch ( \OutOfRangeException $e )
				{
					$this->_author = new Author\Member( new \IPS\nexus\Customer );
				}
			}
			else
			{
				$this->_author = new Author\Email( $this->email );
			}
		}
		return $this->_author;
	}
	
	/**
	 * @brief	Staff views
	 */
	protected $_staffViews;
	
	/**
	 * Staff views
	 *
	 * @return	array
	 */
	public function staffViews()
	{
		if ( $this->_staffViews === NULL )
		{
			$this->_staffViews = iterator_to_array( \IPS\Db::i()->select( '*', 'nexus_support_views', array( 'view_rid=?', $this->id ), 'view_last DESC' )->setKeyField( 'view_member' ) );
		}
		return $this->_staffViews;
	}
	
	/**
	 * Set staff view
	 *
	 * @param	\IPS\Member	$staff	The staff member viewing
	 * @return	void
	 */
	public function setStaffView( \IPS\Member $staff )
	{
		$this->staffViews();
		
		if ( !isset( $this->_staffViews[ $staff->member_id ] ) )
		{
			$view = array(
				'view_rid'		=> $this->id,
				'view_member'	=> $staff->member_id,
				'view_first'	=> time(),
				'view_last'		=> time(),
				'view_reply'	=> 0
			);
			\IPS\Db::i()->insert( 'nexus_support_views', $view, TRUE );
		}
		else
		{
			$view = $this->_staffViews[ $staff->member_id ];
			$view['view_last'] = time();
			\IPS\Db::i()->update( 'nexus_support_views', array( 'view_last' => time() ), array( 'view_rid=? AND view_member=?', $this->id, $staff->member_id ) );
			unset( $this->_staffViews[ $staff->member_id ] );
		}
		array_unshift( $this->_staffViews, $view );
	}
	
	/**
	 * Next/Previous Cache
	 */
	protected $nextPrevious = array();
	
	/**
	 * Get Next/Previous Request (for viewing in the ACP)
	 *
	 * @param	string		$sort		ORDER BY clause
	 * @param	string		$filter		The filter to use
	 * @param	bool		$previous	If true, will return previous (otherwise will return next)
	 * @return	\IPS\nexus\Support\Request|NULL
	 */
	public function nextPrevious( $sort, $filter=NULL, $previous=FALSE )
	{
		if ( !isset( $this->nextPrevious[ md5( $sort . $filter . $previous ) ] ) )
		{
			/* Init */
			$select = array( 'nexus_support_requests.*, nexus_support_departments.dpt_staff' );
			$joins = array( 'nexus_support_departments' => 'dpt_id=r_department' );
			$where = array( array( 'r_id<>?', $this->id ) );
			
			/* Validate the sort */
			if ( !preg_match( '/^(([a-z_]+(, )?)+)( ((a|de)sc))?$/i', trim( $sort ), $matches ) )
			{
				return NULL;
			}
			$lastColumn = NULL;
			$ascDesc= isset( $matches[5] ) ? mb_strtolower( trim( $matches[5] ) ) : 'desc';
			foreach ( explode( ',', $matches[1] ) as $column )
			{
				$column = trim( $column );
				
				if ( !in_array( $column, array( 'r_title', 'sev_position', 'dpt_position', 'r_member', 'r_replies', 'r_started', 'r_last_new_reply', 'r_last_reply', 'r_last_staff_reply', 'status_position' ) ) )
				{
					return NULL;
				}
							
				if ( $column === 'sev_position' )
				{
					$select[] = 'nexus_support_severities.sev_position';
					$joins['nexus_support_severities'] = 'sev_id=r_severity';
				}
				
				if ( $column === 'dpt_position' )
				{
					$select[] = 'nexus_support_departments.dpt_position';
				}
				
				if ( $column === 'status_position' )
				{
					$select[] = 'nexus_support_statuses.status_position';
					$joins['nexus_support_statuses'] = 'status_id=r_status';
				}
				
				if ( mb_substr( $column, 0, 2 ) === 'r_' )
				{
					$lastColumn = mb_substr( $column, 2 );
				}
			}
			if ( !$lastColumn )
			{
				return NULL;
			}
					
			/* Build the where */
			$where[] = array( "( dpt_staff='*' OR " . \IPS\Db::i()->findInSet( 'dpt_staff', \IPS\nexus\Support\Department::staffDepartmentPerms() ) . ')' );
			switch ( $filter )
			{
				case 'my_departments':
					$myFilters = static::myFilters();
					$where[] = array( $myFilters['whereClause'] );
					break;
				case 'tracked':
					$where[] = 'request_id IS NOT NULL';
					break;
				case 'my_last_replies':
					$where[] = \IPS\Db::i()->in( 'r_id', iterator_to_array( \IPS\Db::i()->select( 'DISTINCT(reply_request)', 'nexus_support_replies', array( 'reply_member=?', \IPS\Member::loggedIn()->member_id ), 'reply_date DESC', 100 ) ) );
					$select = array( 'nexus_support_tracker.request_id' );
					$joins['nexus_support_tracker'] = array( 'request_id=r_id AND member_id=?', \IPS\Member::loggedIn()->member_id );
					break;
			}
			
			/* And stick the next/previous where on */
			if ( $previous )
			{
				$symbol = ( $ascDesc === 'asc' ? '<' : '>' );
			}
			else
			{
				$symbol = ( $ascDesc === 'asc' ? '>' : '<' );
			}
			$where[] = array( "r_{$lastColumn}{$symbol}?", $this->$lastColumn );
						
			/* Build the query */
			$select = \IPS\Db::i()->select( implode( ', ', $select ), 'nexus_support_requests', $where, $sort, 1 );
			foreach ( $joins as $table => $clause )
			{
				$select->join( $table, $clause );
			}
			
			/* Return */
			try
			{
				$this->nextPrevious[ md5( $sort . $filter . $previous ) ] = static::constructFromData( $select->first() );
			}
			catch ( \UnderflowException $e )
			{
				$this->nextPrevious[ md5( $sort . $filter . $previous ) ] = NULL;
			}
		}
		return $this->nextPrevious[ md5( $sort . $filter . $previous ) ];
	}
	
	/**
	 * Log that something happened
	 *
	 * @param	string	$action		What changed
	 * @param	mixed	$old		Old value
	 * @param	mixed	$new		New value
	 * @return	void
	 */
	public function log( $action, $old, $new )
	{
		$log = new \IPS\nexus\Support\Log;
		$log->request = $this;
		$log->member = \IPS\Member::loggedIn();
		$log->action = $action;
		$log->old = $old;
		$log->new = $new;
		$log->date = \IPS\DateTime::create();
		$log->save();
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse		int								id				ID number
	 * @apiresponse		string							title			Title
	 * @apiresponse		\IPS\Member						member			If the support request was created by a member, the member object
	 * @apiresponse		string							email			If the support request was created by an email which does not belong to a member, that email address
	 * @apiresponse		\IPS\nexus\Support\Status		status			Status
	 * @apiresponse		\IPS\nexus\Support\Department	department		Department
	 * @apiresponse		\IPS\nexus\Support\Severity		severity		Severity
	 * @apiresponse		\IPS\Member						staff			Assigned staff member
	 * @apiresponse		\IPS\nexus\Purchase				purchase		Associated purchase
	 * @apiresponse		\IPS\nexus\Invoice				ppiInvoice		If this is a pay-per-incident support request, the associated invoice
	 * @apiresponse		int								replies			Number of replies
	 * @apiresponse		\IPS\nexus\Support\Reply		firstMessage	The first message
	 */
	public function apiOutput()
	{
		$firstMessage = $this->comments( 1, 0, 'date', 'asc' );
		return array(
			'id'			=> $this->id,
			'title'			=> $this->title,
			'member'		=> $this->member ? \IPS\Member::load( $this->member )->apiOutput() : null,
			'email'			=> $this->email ?: null,
			'status'		=> $this->status->apiOutput(),		
			'department'	=> $this->department->apiOutput(),
			'severity'		=> $this->severity ? $this->severity->apiOutput() : null,
			'staff'			=> $this->staff ? $this->staff->apiOutput() : null,
			'purchase'		=> $this->purchase ? $this->purchase->apiOutput() : null,
			'ppiInvoice'	=> $this->ppi_invoice ? $this->ppi_invoice->apiOutput() : null,
			'replies'		=> $this->replies,
			'firstMessage'	=> $firstMessage->apiOutput()
		);
	}
	
	/* !URLs */

	/**
	 * @brief	Cached URLs
	 */
	protected $_url	= array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function url( $action=NULL )
	{
		$_key	= md5( $action );

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = \IPS\Http\Url::internal( "app=nexus&module=support&controller=view&id={$this->id}", 'front', 'support_view' );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}

	/**
	 * Get ACP URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	\IPS\Http\Url
	 */
	public function acpUrl( $action=NULL )
	{
		$url = \IPS\Http\Url::internal( "app=nexus&module=support&controller=request&id={$this->id}", 'admin' );
		
		if( \IPS\Dispatcher::hasInstance() and \IPS\Dispatcher::i()->controllerLocation === 'admin' and isset( \IPS\Request::i()->sort ) )
		{
			$url = $url->setQueryString( 'sort', \IPS\Request::i()->sort );
		}
		
		return $url;
	}
	
	/* !Delete */

	/**
	 * Delete
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Db::i()->delete( 'nexus_support_tracker', array( 'request_id=?', $this->id ) );
		\IPS\Db::i()->delete( 'nexus_support_views', array( 'view_rid=?', $this->id ) );
		parent::delete();
	}
}