<?php
/**
 * @brief		Payouts
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		07 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\payments;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Payouts
 */
class _payouts extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'payouts_manage' );
		parent::execute();
	}

	/**
	 * Payout Requests
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Redirect to settings if we haven't set it up */
		if ( !\IPS\Settings::i()->nexus_payout )
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=payouts&do=settings' ) );
		}
		
		/* Build table */
		$table = \IPS\nexus\Payout::table( array(), \IPS\Http\Url::internal('app=nexus&module=payments&controller=payouts') );
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('menu__nexus_payments_payouts');
		if( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'payouts_settings' ) )
		{
			\IPS\Output::i()->sidebar['actions'] = array(
				'settings'	=> array(
					'icon'		=> 'cog',
					'title'		=> 'account_credit_settings',
					'link'		=> \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=payouts&do=settings' )
				),
			);
			
			foreach ( \IPS\Db::i()->select( 'po_gateway', 'nexus_payouts', array( 'po_status=?', \IPS\nexus\Payout::STATUS_PENDING ), NULL, NULL, 'po_gateway' ) as $gateway )
			{
				$classname = 'IPS\nexus\Gateway\\' . $gateway . '\Payout';
				if ( class_exists( $classname ) and method_exists( $classname, 'massProcess' ) )
				{
					\IPS\Output::i()->sidebar['actions'][] = array(
						'icon'		=> 'check',
						'title'		=> \IPS\Member::loggedIn()->language()->addToStack( 'account_credit_mass_payout', TRUE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( 'payout__admin_' . $gateway ) ) ) ),
						'link'		=> \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=payouts&do=massprocess&gateway=' . $gateway )
					);
				}
			}
		}
		\IPS\Output::i()->output = (string) $table;
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function view()
	{
		try
		{
			$payout = \IPS\nexus\Payout::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X200/2', 404, '' );
		}
				
		\IPS\Output::i()->sidebar['actions'] = $payout->buttons( 'v' );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'payout_number', FALSE, array( 'sprintf' => array( $payout->id ) ) );
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('payouts')->view( $payout );
	}
	
	/**
	 * Approve
	 *
	 * @return	void
	 */
	protected function process()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'payouts_process' );
		
		try
		{
			$payout = \IPS\nexus\Payout::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X200/3', 404, '' );
		}
		
		if ( $payout->status !== $payout::STATUS_PENDING )
		{
			\IPS\Output::i()->error( 'err_payout_not_pending', '1X200/4', 403, '' );
		}
		
		try
		{
			$payout->process();
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '1X200/1', 500, '' );
		}
		
		$payout->status = $payout::STATUS_COMPLETE;
		$payout->processed_by = \IPS\Member::loggedIn();
		$payout->save();
		\IPS\Session::i()->log( 'acplogs__payout_processed', array( $payout->id => FALSE ) );
		$payout->member->log( 'payout', array( 'type' => 'processed', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
		
		\IPS\Email::buildFromTemplate( 'nexus', 'payoutComplete', array( $payout ), \IPS\Email::TYPE_TRANSACTIONAL )->send( $payout->member );
				
		$this->_redirect( $payout );
	}
	
	/**
	 * Mass Approve
	 *
	 * @return	void
	 */
	protected function massprocess()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'payouts_process' );
		
		$classname = 'IPS\nexus\Gateway\\' . \IPS\Request::i()->gateway . '\Payout';
		if ( !class_exists( $classname ) or !method_exists( $classname, 'massProcess' ) )
		{
			\IPS\Output::i()->error( 'generic_error', '2X200/8', 403, '' );
		}
		
		$payouts = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_payouts', array( 'po_status=? AND po_gateway=?', \IPS\nexus\Payout::STATUS_PENDING, \IPS\Request::i()->gateway ) ), $classname );
		
		try
		{	
			$classname::massProcess( $payouts );
		}
		catch ( \Exception $e )
		{
			\IPS\Output::i()->error( $e->getMessage(), '1X200/9', 403, '' );
		}
		
		foreach ( $payouts as $payout )
		{
			$payout->status = $payout::STATUS_COMPLETE;
			$payout->completed = new \IPS\DateTime;
			$payout->processed_by = \IPS\Member::loggedIn();
			$payout->save();
			\IPS\Session::i()->log( 'acplogs__payout_processed', array( $payout->id => FALSE ) );
			$payout->member->log( 'payout', array( 'type' => 'processed', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
			
			\IPS\Email::buildFromTemplate( 'nexus', 'payoutComplete', array( $payout ), \IPS\Email::TYPE_TRANSACTIONAL )->send( $payout->member );
		}
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=payouts') );
	}
	
	/**
	 * Cancel
	 *
	 * @return	void
	 */
	protected function cancel()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'payouts_cancel' );
		
		try
		{
			$payout = \IPS\nexus\Payout::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X200/6', 404, '' );
		}
		
		if ( $payout->status !== $payout::STATUS_PENDING )
		{
			\IPS\Output::i()->error( 'err_payout_not_pending', '1X200/5', 403, '' );
		}
		
		$payout->status = $payout::STATUS_CANCELED;
		$payout->completed = new \IPS\DateTime;
		$payout->processed_by = \IPS\Member::loggedIn();
		$payout->save();
		\IPS\Session::i()->log( 'acplogs__payout_cancelled', array( $payout->id => FALSE ) );
		$payout->member->log( 'payout', array( 'type' => 'cancel', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
		
		if ( \IPS\Request::i()->prompt )
		{
			$credits = $payout->member->cm_credits;
			$credits[ $payout->currency ]->amount += $payout->amount->amount;
			$payout->member->cm_credits = $credits;
			$payout->member->save();
		}
		
		$this->_redirect( $payout );
	}
	
	/**
	 * Delete
	 *
	 * @return	void
	 */
	protected function delete()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'payouts_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		try
		{
			$payout = \IPS\nexus\Payout::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X200/7', 404, '' );
		}
		
		$payout->delete();
		\IPS\Session::i()->log( 'acplogs__payout_deleted', array( $payout->id => FALSE ) );
		$payout->member->log( 'payout', array( 'type' => 'dismissed', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=payouts') );
	}
	
	/**
	 * Payout Settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'payouts_settings' );
		
		$options = array();
		$toggles = array();
		$fields = array();
		foreach ( new \DirectoryIterator( \IPS\ROOT_PATH . '/applications/nexus/sources/Gateway' ) as $file )
		{
			if ( $file->isDir() and !$file->isDot() and file_exists( \IPS\ROOT_PATH . '/applications/nexus/sources/Gateway/' . $file . '/Payout.php' ) )
			{
				$options[ (string) $file ] = 'payout__admin_' . $file;
				$toggles[ (string) $file ] = array( 'form_header_payout_settings' );
				
				$class = 'IPS\nexus\Gateway\\' . (string) $file . '\\Payout';
				foreach ( $class::settings() as $field )
				{
					if ( !$field->htmlId )
					{
						$field->htmlId = md5(uniqid());
					}
					
					$fields[] = $field;
					$toggles[ (string) $file ][] = $field->htmlId;
				}
			}
		}
		
		$groups = array();
		foreach ( \IPS\Member\Group::groups( TRUE, FALSE ) as $group )
		{
			$groups[ $group->g_id ] = $group->name;
		}
		
		$settings = \IPS\Settings::i()->nexus_payout ? json_decode( \IPS\Settings::i()->nexus_payout, TRUE ) : array();
		$settings = is_array( $settings ) ? array_keys( $settings ) : array();
		
		$form = new \IPS\Helpers\Form;
		$form->addMessage('payout_description');
		$form->addHeader('commission_settings');
		$form->add( new \IPS\Helpers\Form\Select( 'nexus_no_commission', explode( ',', \IPS\Settings::i()->nexus_no_commission ), FALSE, array( 'multiple' => TRUE, 'options' => $groups ) ) );
		$form->addHeader('withdrawal_methods');
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'nexus_payout', $settings, FALSE, array( 'options' => $options, 'toggles' => $toggles ) ) );
		foreach ( $fields as $field )
		{
			$form->add( $field );
		}
		$form->addHeader('payout_settings');
		$form->add( new \IPS\nexus\Form\Money( 'nexus_payout_min', \IPS\Settings::i()->nexus_payout_min ? json_decode( \IPS\Settings::i()->nexus_payout_min, TRUE ) : '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_payout_min' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'nexus_payout_approve', \IPS\Settings::i()->nexus_payout_approve, FALSE, array(), NULL, NULL, NULL, 'nexus_payout_approve' ) );
		$form->addHeader('topup_settings');
		$form->add( new \IPS\Helpers\Form\YesNo( 'allow_topups', \IPS\Settings::i()->nexus_min_topup, FALSE, array( 'togglesOn' => array( 'nexus_min_topup', 'nexus_max_credit' ) ) ) );
		$form->add( new \IPS\nexus\Form\Money( 'nexus_min_topup', \IPS\Settings::i()->nexus_min_topup ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_min_topup' ) );
		$form->add( new \IPS\nexus\Form\Money( 'nexus_max_credit', \IPS\Settings::i()->nexus_max_credit ?: '*', FALSE, array( 'unlimitedLang' => 'no_restriction' ), NULL, NULL, NULL, 'nexus_max_credit' ) );
		if ( $values = $form->values() )
		{
			$payoutSettings = array();
			foreach ( $values['nexus_payout'] as $k )
			{
				$payoutSettings[ $k ] = array();
				foreach ( $values as $l => $v )
				{
					if ( mb_substr( $l, 0, mb_strlen( $k ) ) === mb_strtolower( $k ) )
					{
						$payoutSettings[ $k ][ mb_substr( $l, mb_strlen( $k ) + 1 ) ] = $v;
						unset( $values[ $l ] ); 
					}
				}
			}
						
			$values['nexus_payout'] = json_encode( $payoutSettings );
			$values['nexus_payout_min'] = is_array( $values['nexus_payout_min'] ) ? json_encode( $values['nexus_payout_min'] ) : '*';
			
			if ( $values['allow_topups'] )
			{
				$values['nexus_min_topup'] = $values['nexus_min_topup'] == '*' ? '*' : json_encode( $values['nexus_min_topup'] );
				$values['nexus_max_credit'] = $values['nexus_max_credit'] == '*' ? '*' : json_encode( $values['nexus_max_credit'] );
			}
			else
			{
				$values['nexus_min_topup'] = '';
				$values['nexus_max_credit'] = '';
			}
			unset( $values['allow_topups'] );
			
			$values['nexus_no_commission'] = implode( ',', $values['nexus_no_commission'] );
			
			$form->saveAsSettings( $values );
			\IPS\Session::i()->log( 'acplogs__payout_settings' );
			
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=payouts'), 'saved' );
		}
		
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('account_credit_settings');
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Redirect
	 *
	 * @param	\IPS\nexus\Payout	$payout	The payout
	 * @return	void
	 */
	protected function _redirect( \IPS\nexus\Payout $payout )
	{
		if ( isset( \IPS\Request::i()->r ) )
		{
			switch ( mb_substr( \IPS\Request::i()->r, 0, 1 ) )
			{
				case 'v':
					\IPS\Output::i()->redirect( $payout->acpUrl() );
					break;
				
				case 'c':
					\IPS\Output::i()->redirect( $payout->member->acpUrl() );
					break;
				
				case 't':
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal('app=nexus&module=payments&controller=payouts')->setQueryString( 'filter', \IPS\Request::i()->filter ) );
					break;
					
			}
		}
		
		\IPS\Output::i()->redirect( $payout->acpUrl() );
	}
}