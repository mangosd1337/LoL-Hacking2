<?php
/**
 * @brief		Packages
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\store;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Packages
 */
class _packages extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Package\Group';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'packages_manage' );
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'admin_store.js', 'nexus', 'admin' ) );
		parent::execute();
	}
		
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons()
	{
		$buttons = parent::_getRootButtons();
		
		if ( isset( $buttons['add'] ) )
		{
			$buttons['add']['title'] = 'create_new_group';
		}
		
		return $buttons;
	}
	
	/**
	 * Redirect after save
	 *
	 * @param	\IPS\Node\Model	$old	A clone of the node as it was before or NULL if this is a creation
	 * @param	\IPS\Node\Model	$node	The node now
	 * @return	void
	 */
	protected function _afterSave( \IPS\Node\Model $old = NULL, \IPS\Node\Model $new )
	{
		if ( !( $new instanceof \IPS\nexus\Package ) )
		{
			return parent::_afterSave( $old, $new );
		}
		
		$changes = array();
		if ( $old )
		{
			foreach ( $new::updateableFields() as $k )
			{
				if ( $old->$k != $new->$k )
				{
					$changes[ $k ] = $old->$k;
				}
			}
		}

		/* Clear cache */
		unset( \IPS\Data\Store::i()->nexusPackagesWithReviews );

		/* If something has changed, see if anyone has purchased */
		$purchases = 0;

		if( count( $changes ) )
		{
			$purchases = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'package', $new->id ) )->first();
		}
		
		/* Only show this screen if the package has been purchased. Otherwise even just copying a package and saving asks if you want to update
			existing purchases unnecessarily */
		if ( !empty( $changes ) AND $purchases )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->decision( 'product_change_blurb', array(
				'product_change_blurb_existing'	=> \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting&id={$new->_id}" )->setQueryString( 'changes', json_encode( $changes ) ),
				'product_change_blurb_new'		=> $this->url->setQueryString( array( 'root' => ( $new->parent() ? $new->parent()->_id : '' ) ) ),
			) );
		}
		else
		{
			return parent::_afterSave( $old, $new );
		}
	}
	
	/**
	 * Update Existing Purchases
	 *
	 * @return	void
	 */
	public function updateExisting()
	{		
		try
		{
			$package = \IPS\nexus\Package::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X249/1', 404, '' );
		}
		
		$changes = json_decode( \IPS\Request::i()->changes, TRUE );
		
		if ( !isset( \IPS\Request::i()->processing ) )
		{
			if ( isset( $changes['renew_options'] ) )
			{
				$matrix = new \IPS\Helpers\Form\Matrix( 'matrix', 'continue' );
				$matrix->manageable = FALSE;
				
				$newOptions = array( '-' => \IPS\Member::loggedIn()->language()->addToStack('do_not_change') );
				$existingRenewOptions = json_decode( $package->renew_options, TRUE );

				if( !is_array( $existingRenewOptions ) )
				{
					$existingRenewOptions = array();
				}

				foreach ( $existingRenewOptions as $k => $newOption )
				{
					$costs = array();
					foreach ( $newOption['cost'] as $data )
					{
						$costs[] = new \IPS\nexus\Money( $data['amount'], $data['currency'] );
					}
					
					switch ( $newOption['unit'] )
					{
						case 'd':
							$term = \IPS\Member::loggedIn()->language()->addToStack('renew_days', FALSE, array( 'pluralize' => array( $newOption['term'] ) ) );
							break;
						case 'm':
							$term = \IPS\Member::loggedIn()->language()->addToStack('renew_months', FALSE, array( 'pluralize' => array( $newOption['term'] ) ) );
							break;
						case 'y':
							$term = \IPS\Member::loggedIn()->language()->addToStack('renew_years', FALSE, array( 'pluralize' => array( $newOption['term'] ) ) );
							break;
					}
					
					$newOptions[ "o{$k}" ] = \IPS\Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $costs, \IPS\Member::loggedIn()->language()->get('or_list_format') ), $term ) ) );
				}
				$newOptions['z'] = \IPS\Member::loggedIn()->language()->addToStack('remove_renewal_no_expire_leave');
				$newOptions['y'] = \IPS\Member::loggedIn()->language()->addToStack('remove_renewal_no_expire_reactivate');
				$newOptions['x'] = \IPS\Member::loggedIn()->language()->addToStack('remove_renewal_expire');
				$matrix->columns = array(
					'customers_currently_paying' => function( $key, $value, $data )
					{
						return $data[0];
					},
					'now_pay' => function( $key, $value, $data ) use ( $newOptions )
					{
						return new \IPS\Helpers\Form\Select( $key, $data[1], TRUE, array( 'options' => $newOptions, 'noDefault' => TRUE ) );
					},
				);
				
				foreach ( json_decode( $changes['renew_options'], TRUE ) as $k => $oldOption )
				{
					$costs = array();
					foreach ( $oldOption['cost'] as $data )
					{
						$costs[] = new \IPS\nexus\Money( $data['amount'], $data['currency'] );
					}
					
					switch ( $oldOption['unit'] )
					{
						case 'd':
							$term = \IPS\Member::loggedIn()->language()->addToStack('renew_days', FALSE, array( 'pluralize' => array( $oldOption['term'] ) ) );
							break;
						case 'm':
							$term = \IPS\Member::loggedIn()->language()->addToStack('renew_months', FALSE, array( 'pluralize' => array( $oldOption['term'] ) ) );
							break;
						case 'y':
							$term = \IPS\Member::loggedIn()->language()->addToStack('renew_years', FALSE, array( 'pluralize' => array( $oldOption['term'] ) ) );
							break;
					}
					
					$matrix->rows[ $k ] = array( \IPS\Member::loggedIn()->language()->addToStack( 'renew_option', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->formatList( $costs, \IPS\Member::loggedIn()->language()->get('or_list_format') ), $term ) ) ), "o{$k}" );
				}
				$matrix->rows['x'] = array( 'any_other_amount', '-' );
				
				if ( $values = $matrix->values() )
				{	
					$renewOptions = json_decode( $changes['renew_options'], TRUE );
					$changes['renew_options'] = array();
					foreach ( $renewOptions as $k => $data )
					{
						$changes['renew_options'][ $k ] = array( 'old' => $data, 'new' =>  $values[ $k ]['now_pay'] );
					}
					$changes['renew_options']['x'] = array( 'old' => 'x', 'new' => $values['x']['now_pay'] );
				}
				else
				{
					\IPS\Output::i()->output = $matrix;
					return;
				}
			}
		}
		
		\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect(
			\IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=updateExisting&id=1&changes=secondary_group" )->setQueryString( array(
				'id'		=> \IPS\Request::i()->id,
				'changes'	=> json_encode( $changes ),
				'processing'=> 1
			) ),
			function( $offset ) use ( $package, $changes )
			{
				$select = \IPS\Db::i()->select( '*', 'nexus_purchases', array( "ps_app=? and ps_type=? and ps_item_id=?", 'nexus', 'package', $package->id ), 'ps_id', array( $offset, 1 ), NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
				
				try
				{
					$purchase = \IPS\nexus\Purchase::constructFromData( $select->first() );
					$total = $select->count( TRUE );
					
					$package->updatePurchase( $purchase, $changes );
					
					return array( ++$offset, \IPS\Member::loggedIn()->language()->get('processing'), 100 / $total * $offset );
				}
				catch ( \UnderflowException $e )
				{
					return NULL;
				}
				
			},
			function() use ( $package )
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages" )->setQueryString( array( 'root' => ( $package->parent() ? $package->parent()->_id : '' ) ) ) );
			}
		);
	}
	
	/**
	 * Build Product Options Table
	 *
	 * @return	array
	 */
	public function productoptions()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'packages_edit' );
		
		if ( !\IPS\Request::i()->fields or !\IPS\Request::i()->package )
		{
			\IPS\Output::i()->sendOutput('');
			return;
		}
		
		try
		{
			$package = \IPS\nexus\Package::load( \IPS\Request::i()->package );
		
			$fields = iterator_to_array( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_package_fields', \IPS\Db::i()->in( 'cf_id', explode( ',', \IPS\Request::i()->fields ) ) ), 'IPS\nexus\Package\CustomField' ) );
			$allTheOptions = array();
			foreach ( $fields as $field )
			{
				$options = array();
				foreach ( json_decode( $field->extra, TRUE ) as $option )
				{
					$options[] = json_encode( array( $field->id, $option ) );
				}
				$allTheOptions[ $field->id ] = $options;
			}
			$_rows = $this->arraycartesian( $allTheOptions );
			
			$rows = array();
			foreach ( $_rows as $_options )
			{
				$options = array();
				foreach ( $_options as $encoded )
				{
					$decoded = json_decode( $encoded, TRUE );
					$options[ $decoded[0] ] = $decoded[1];
				}
				$rows[ json_encode( $options ) ] = $options;
			}
			
			$existingValues = iterator_to_array( \IPS\Db::i()->select( '*', 'nexus_product_options', array( 'opt_package=?', $package->id ) )->setKeyField( 'opt_values' ) );
									
			\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate('store')->productOptionsTable( $fields, $rows, $existingValues, \IPS\Request::i()->renews ) );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->sendOutput( $e->getMessage(), 500 );
		}
	}
	
	/**
	 * Little function from the PHP manual comments
	 *
	 * @return	array
	 */
	protected function arraycartesian($_) {
	    if(count($_) == 0)
	        return array(array());
		foreach($_ as $k=>$a) {
	    	unset($_[$k]);
	    	break;
	    }
	    $c = $this->arraycartesian($_);
	    $r = array();
	    foreach($a as $v)
	        foreach($c as $p)
	            $r[] = array_merge(array($v), $p);
	    return $r;
	}
	
	/**
	 * View Purchases
	 *
	 * @return	void
	 */
	protected function viewPurchases()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'purchases_view', 'nexus', 'customers' );
		
		try
		{
			$package = \IPS\nexus\Package::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X249/2', 404, '' );
		}
		
		$table = new \IPS\Helpers\Table\Db( 'nexus_purchases', \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages&do=viewPurchases&id={$package->id}" ), array( array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'package', $package->id ) ) );
		$table->include = array( 'ps_id', 'ps_member', 'purchase_status', 'ps_start', 'ps_expire', 'ps_renewals' );
		$table->quickSearch = 'ps_id';
		$table->advancedSearch = array(
			'ps_member'	=> \IPS\Helpers\Table\SEARCH_MEMBER,
			'ps_start'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'ps_expire'	=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
		);
		$table->noSort = array( 'purchase_status' );
		$table->filters = array( 'active' => 'ps_active=1' );
		$table->parsers = array(
			'ps_member'	=> function( $val ) {
				try
				{
					return \IPS\nexus\Customer::load( $val )->link();
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'purchase_status' => function( $val, $row ) {
				$purchase = \IPS\nexus\Purchase::constructFromData( $row );
				if ( $purchase->cancelled )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('purchase_canceled');
				}
				elseif ( !$purchase->active )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('purchase_expired');
				}
				elseif ( $purchase->grace_period and $purchase->expire->getTimestamp() < time() )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('purchase_in_grace_period');
				}
				else
				{
					return \IPS\Member::loggedIn()->language()->addToStack('purchase_active');
				}
			},
			'ps_start'	=> function( $val ) {
				return \IPS\DateTime::ts( $val );
			},
			'ps_expire'	=> function( $val ) {
				return $val ? \IPS\DateTime::ts( $val ) : '--';
			},
			'ps_renewals' => function( $val, $row ) {
				$purchase = \IPS\nexus\Purchase::constructFromData( $row );
				return $purchase->grouped_renewals ? \IPS\Member::loggedIn()->language()->addToStack('purchase_grouped') : ( (string) ( $purchase->renewals ?: '--' ) );
			}
		);
		$table->rowButtons = function( $row ) {
			$purchase = \IPS\nexus\Purchase::constructFromData( $row );
			return array_merge( array(
				'view'	=> array(
					'link'	=> $purchase->acpUrl()->setQueryString( 'popup', true ),
					'title'	=> 'view',
					'icon'	=> 'search',
				)
			), $purchase->buttons() );
		};
		
		\IPS\Output::i()->title = $package->_title;
		\IPS\Output::i()->output = $table;
	}
}