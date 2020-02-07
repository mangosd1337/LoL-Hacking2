<?php
/**
 * @brief		Account Credit
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		08 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\clients;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Account Credit
 */
class _credit extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2X239/1', 403, '' );
		}
		
		if ( mb_strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get' and \IPS\Settings::i()->nexus_https and \IPS\Request::i()->url()->data['scheme'] !== 'https' )
		{
			\IPS\Output::i()->redirect( new \IPS\Http\Url( preg_replace( '/^http:/', 'https:', \IPS\Request::i()->url() ) ) );
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'clients.css', 'nexus' ) );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit', array(), \IPS\Settings::i()->nexus_https ), \IPS\Member::loggedIn()->language()->addToStack('client_credit') );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('client_credit');
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}
	
	/**
	 * View
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Get Balance(s) */
		$balance = array_filter( \IPS\nexus\Customer::loggedIn()->cm_credits, function( $val )
		{
			return $val->amount;
		} );
				
		/* Pending Withdrawls */
		$perPage = 10;
		$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

		if( $page < 1 )
		{
			$page = 1;
		}

		$pastWithdrawalsSelect = \IPS\Db::i()->select( '*', 'nexus_payouts', array( 'po_member=?', \IPS\nexus\Customer::loggedIn()->member_id ), 'po_date DESC', array( ( $page - 1 ) * $perPage, $perPage ), NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
		$pastWithdrawalsPagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
			\IPS\Http\Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit', array(), \IPS\Settings::i()->nexus_https ),
			ceil( $pastWithdrawalsSelect->count( TRUE ) / $perPage ),
			$page,
			$perPage
		);
		$pastWithdrawals = new \IPS\Patterns\ActiveRecordIterator( $pastWithdrawalsSelect, 'IPS\nexus\Payout' );
		
		/* Can we withdraw? */
		$canWithdraw = FALSE;
		$withdrawOptions = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
		if ( count( $withdrawOptions ) )
		{
			foreach ( \IPS\nexus\Money::currencies() as $currency )
			{
				if ( isset( $balance[ $currency ] ) )
				{
					$canWithdraw = TRUE;
				}
			}
		}
		
		/* Can we topup? */
		$canTopup = FALSE;
		if ( \IPS\Settings::i()->nexus_min_topup )
		{
			$minimumTopupAmounts = ( \IPS\Settings::i()->nexus_min_topup and \IPS\Settings::i()->nexus_min_topup !== '*' ) ? json_decode( \IPS\Settings::i()->nexus_min_topup, TRUE ) : NULL;
			$maximumBalanceAmounts = ( \IPS\Settings::i()->nexus_max_credit and \IPS\Settings::i()->nexus_max_credit !== '*' ) ? json_decode( \IPS\Settings::i()->nexus_max_credit, TRUE ) : NULL;
			
			$maximums = array();
			foreach ( array_merge( array( \IPS\nexus\Customer::loggedIn() ), iterator_to_array( \IPS\nexus\Customer::loggedIn()->parentContacts( array('billing=1') ) ) ) as $account )
			{
				if ( $account instanceof \IPS\nexus\Customer\AlternativeContact )
				{
					$account = $account->main_id;
				}
				
				foreach ( $account->cm_credits as $value )
				{
					if ( $maximumBalanceAmounts === NULL or $value->amount->compare( new \IPS\Math\Number( number_format( $maximumBalanceAmounts[ $value->currency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $value->currency ), '.', '' ) ) ) === -1 )
					{
						$canTopup = TRUE;
					}
				}
			}
		}
				
		/* Display */
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('clients')->credit( $balance, $pastWithdrawals, $pastWithdrawalsPagination, $canWithdraw, $canTopup );
	}
	
	/**
	 * Withdraw
	 *
	 * @return	void
	 */
	protected function withdraw()
	{
		$withdrawForm = NULL;
		$withdrawOptions = json_decode( \IPS\Settings::i()->nexus_payout, TRUE );
		if ( count( $withdrawOptions ) )
		{
			$withdrawForm = new \IPS\Helpers\Form( 'withdraw', 'withdraw_credit' );
			
			$minimumWithdrawalsAmounts = ( \IPS\Settings::i()->nexus_payout_min and \IPS\Settings::i()->nexus_payout_min !== '*' ) ? json_decode( \IPS\Settings::i()->nexus_payout_min, TRUE ) : NULL;
			
			$balance = array_filter( \IPS\nexus\Customer::loggedIn()->cm_credits, function( $val )
			{
				return $val->amount;
			} );
				
			$currencyOptions = array();
			if ( count( $balance ) > 1 )
			{
				$currencyToggles = array();
				foreach ( \IPS\nexus\Money::currencies() as $currency )
				{
					if ( isset( $balance[ $currency ] ) )
					{
						$currencyOptions[ $currency ] = $currency;
						$currencyToggles[ $currency ] = array( 'withdraw_amount_' . $currency );
					}
				}
				
				$withdrawForm->add( new \IPS\Helpers\Form\Radio( 'withdraw_currency', NULL, TRUE, array( 'options' => $currencyOptions, 'toggles' => $currencyToggles ) ) );
			}
			
			$canWithdraw = FALSE;
			foreach ( \IPS\nexus\Money::currencies() as $currency )
			{
				if ( !isset( $balance[ $currency ] ) )
				{
					continue;
				}
				
				$min = $minimumWithdrawalsAmounts ? ( $minimumWithdrawalsAmounts[ $currency ]['amount'] ) : NULL;				
				$max = $balance[ $currency ]->amount;
				
				$field = new \IPS\Helpers\Form\Number( 'withdraw_amount_' . $currency, (string) $max, count( $currencyOptions ) ? NULL : TRUE, array( 'min' => $min, 'max' => (string) $max, 'decimals' => \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ) ), count( $currencyOptions ) ? NULL : function( $val ) use ( $currency )
				{
					if ( !$val and \IPS\Request::i()->withdraw_currency === $currency )
					{
						throw new \DomainException('form_required');
					}
				}, NULL, $currency, 'withdraw_amount_' . $currency );
				$field->label = \IPS\Member::loggedIn()->language()->addToStack('withdraw_amount');
				$withdrawForm->add( $field );
				
				$canWithdraw = TRUE;
			}
						
			if ( $canWithdraw )
			{
				$options = array();
				$toggles = array();
				$fields = array();
				foreach ( $withdrawOptions as $k => $settings )
				{
					$options[ $k ] = $k === 'Manual' ? $settings['name'] : 'withdraw__' . $k;
					$toggles[ $k ] = array();
					$class = 'IPS\nexus\Gateway\\' . $k . '\Payout';
					foreach ( $class::form() as $field )
					{
						if ( !$field->htmlId )
						{
							$field->htmlId = $field->name;
							$toggles[ $k ][] = $field->htmlId;
						}
						$fields[] = $field;
					}
				}
				
				if ( count( $withdrawOptions ) > 1 )
				{
					$withdrawForm->add( new \IPS\Helpers\Form\Radio( 'withdraw_method', NULL, TRUE, array( 'options' => $options, 'toggles' => $toggles ) ) );
				}
				
				foreach ( $fields as $field )
				{
					$withdrawForm->add( $field );
				}
				if ( $values = $withdrawForm->values() )
				{					
					$currencies = array_keys( $balance );
					$currency = count( $currencyOptions ) ? $values['withdraw_currency'] : array_pop( $currencies );
					
					$withdrawMethods = array_keys( $options );
					if ( count( $withdrawMethods ) === 1 )
					{
						$key = array_pop( $withdrawMethods );
						$class = 'IPS\nexus\Gateway\\' . $key . '\Payout';
					}
					else
					{
						$class = 'IPS\nexus\Gateway\\' . $values['withdraw_method'] . '\Payout';
					}
					
					$payout = new $class;
					$payout->amount = new \IPS\nexus\Money( $values[ 'withdraw_amount_' . $currency ], $currency );
					$payout->member = \IPS\nexus\Customer::loggedIn();
					try
					{
						$payout->data = $payout->getData( $values );
					}
					catch ( \DomainException $e )
					{
						\IPS\Output::i()->error( $e->getMessage(), '1X239/2', 403, '' );
					}
					$payout->ip = \IPS\Request::i()->ipAddress();
					$payout->save();
					
					$credits = \IPS\nexus\Customer::loggedIn()->cm_credits;
					$credits[ $currency ]->amount = $credits[ $currency ]->amount->subtract( new \IPS\Math\Number( number_format( $values[ 'withdraw_amount_' . $currency ], \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) ) );
					\IPS\nexus\Customer::loggedIn()->cm_credits = $credits;
					\IPS\nexus\Customer::loggedIn()->save();
					
					if ( !\IPS\Settings::i()->nexus_payout_approve and !$payout::$requiresApproval )
					{
						try
						{
							$payout->process();
							\IPS\nexus\Customer::loggedIn()->log( 'payout', array( 'type' => 'autoprocess', 'amount' => $values[ 'withdraw_amount_' . $currency ], 'currency' => $currency, 'payout_id' => $payout->id ) );
							\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=credit&withdraw=success', 'front', 'clientscredit', array(), \IPS\Settings::i()->nexus_https ) );
						}
						catch ( \Exception $e ) {}
					}
					
					\IPS\nexus\Customer::loggedIn()->log( 'payout', array( 'type' => 'request', 'amount' => $values[ 'withdraw_amount_' . $currency ], 'currency' => $currency, 'payout_id' => $payout->id ) );
					\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=credit&withdraw=pending', 'front', 'clientscredit', array(), \IPS\Settings::i()->nexus_https ) );
				}
			}
			else
			{
				$withdrawForm = NULL;
			}
		}
		
		if ( !$withdrawForm )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2X239/3', 403, '' );
		}
		
		\IPS\Output::i()->output = $withdrawForm->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Topup
	 *
	 * @return	void
	 */
	protected function topup()
	{
		$topupForm = NULL;
		if ( \IPS\Settings::i()->nexus_min_topup )
		{
			$minimumTopupAmounts = ( \IPS\Settings::i()->nexus_min_topup and \IPS\Settings::i()->nexus_min_topup !== '*' ) ? json_decode( \IPS\Settings::i()->nexus_min_topup, TRUE ) : NULL;
			$maximumBalanceAmounts = ( \IPS\Settings::i()->nexus_max_credit and \IPS\Settings::i()->nexus_max_credit !== '*' ) ? json_decode( \IPS\Settings::i()->nexus_max_credit, TRUE ) : NULL;
			
			$maximums = array();
			foreach ( array_merge( array( \IPS\nexus\Customer::loggedIn() ), iterator_to_array( \IPS\nexus\Customer::loggedIn()->parentContacts( array('billing=1') ) ) ) as $account )
			{
				if ( $account instanceof \IPS\nexus\Customer\AlternativeContact )
				{
					$account = $account->main_id;
				}
				
				foreach ( $account->cm_credits as $value )
				{
					$max = $maximumBalanceAmounts ? ( ( new \IPS\Math\Number( number_format( $maximumBalanceAmounts[ $value->currency ]['amount'], \IPS\nexus\Money::numberOfDecimalsForCurrency( $value->currency ), '.', '' ) ) )->subtract( $value->amount ) ) : NULL;
					if ( is_null( $max ) or $max->isGreaterThanZero() )
					{
						$maximums[ $account->member_id ][ $value->currency ] = is_null( $max ) ? NULL : (string) $max;
					}
				}
			}
			
			if ( count( $maximums ) )
			{
				$topupForm = new \IPS\Helpers\Form( 'topup', 'checkout' );
				
				$accountOptions = array();
				foreach ( $maximums as $accountId => $data )
				{
					$currency = NULL;
					if ( count( $data ) === 1 )
					{
						$currencies = array_keys( $data );
						$currency = array_pop( $currencies );
					}
					
					$accountOptions[ $accountId ] = $accountId === \IPS\nexus\Customer::loggedIn()->id ? \IPS\nexus\Customer::loggedIn()->language()->addToStack( 'my_account', FALSE, array( 'sprintf' => array( \IPS\nexus\Customer::loggedIn()->cm_name ) ) ) : \IPS\nexus\Customer::load( $accountId )->cm_name;
					$accountOptionToggles[ $accountId ] = ( count( $data ) > 1 ) ? array( 'topup_currency_' . $accountId ) : array( 'topup_amount_' . $accountId . '_' . $currency );
				}
				$topupForm->add( new \IPS\Helpers\Form\Radio( 'topup_account', NULL, TRUE, array( 'options' => $accountOptions, 'parse' => 'none', 'toggles' => $accountOptionToggles ) ) );
				
				foreach ( $maximums as $accountId => $data )
				{
					if ( count( $data ) > 1 )
					{
						$currencyToggles = array();
						foreach ( $data as $currency => $maximum )
						{
							$currencyToggles[ $currency ] = array( 'topup_amount_' . $accountId . '_' . $currency );
						}
						
						$field = new \IPS\Helpers\Form\Radio( 'topup_currency_' . $accountId, NULL, NULL, array( 'options' => array_combine( array_keys( $data ), array_keys( $data ) ), 'toggles' => $currencyToggles ), NULL, NULL, NULL, 'topup_currency_' . $accountId );
						$field->label = \IPS\Member::loggedIn()->language()->addToStack( 'topup_currency' );
						$topupForm->add( $field );
					}
					
					foreach ( $data as $currency => $maximum )
					{
						$min = $minimumTopupAmounts ? ( $minimumTopupAmounts[ $currency ]['amount'] ) : 0.01;
						
						$field = new \IPS\Helpers\Form\Number( 'topup_amount_' . $accountId . '_' . $currency, NULL, NULL, array( 'decimals' => \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ) ), function( $val ) use ( $currency, $accountOptions, $accountId, $data, $min, $maximum )
						{
							if ( count( $accountOptions ) === 1 or \IPS\Request::i()->topup_account == $accountId )
							{
								$key = "topup_currency_{$accountId}";
								if ( count( $data ) === 1 or \IPS\Request::i()->$key == $currency )
								{
									if ( !$val )
									{
										throw new \DomainException('form_required');
									}
									elseif ( !is_null( $min ) and $val < $min )
									{
										throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'form_number_min', FALSE, array( 'sprintf' => array( $min ) ) ) );
									}
									elseif ( $maximum and $val > $maximum )
									{
										throw new \DomainException( \IPS\Member::loggedIn()->language()->addToStack( 'form_number_max', FALSE, array( 'sprintf' => array( $maximum ) ) ) );
									}
								}
							}
						}, NULL, $currency, 'topup_amount_' . $accountId . '_' . $currency );
						$field->label = \IPS\Member::loggedIn()->language()->addToStack('topup_amount');
						$topupForm->add( $field );		
					}
				}
				
				if ( $values = $topupForm->values() )
				{

					$account = isset( $values['topup_account'] ) ? \IPS\nexus\Customer::load( $values['topup_account'] ) : \IPS\nexus\Customer::loggedIn();
					if ( isset( $values[ 'topup_currency_' . $account->member_id ] ) )
					{
						$currency = $values[ 'topup_currency_' . $account->member_id ];
					}
					else
					{
						$currencies = array_keys( $maximums[ $account->member_id ] );
						$currency = array_pop( $currencies );
					}
					
					$invoice = new \IPS\nexus\Invoice;
					$invoice->member = $account;
					$invoice->currency = $currency;
					$invoice->return_uri = 'app=nexus&module=clients&controller=credit';
					$invoice->addItem( new \IPS\nexus\extensions\nexus\Item\AccountCreditIncrease( \IPS\nexus\Customer::loggedIn()->language()->get('account_credit'), new \IPS\nexus\Money( $values["topup_amount_{$account->member_id}_{$currency}"], $currency ) ) );
					\IPS\Output::i()->redirect( $invoice->checkoutUrl() );
				}
			}
		}
		
		if ( !$topupForm )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2X239/4', 403, '' );
		}
		
		\IPS\Output::i()->output = $topupForm->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Cancel Pending Payout
	 *
	 * @return	void
	 */
	protected function cancel()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$payout = \IPS\nexus\Payout::load( \IPS\Request::i()->id );
			if ( $payout->member->member_id === \IPS\nexus\Customer::loggedIn()->member_id and $payout->status === $payout::STATUS_PENDING )
			{
				$payout->status = $payout::STATUS_CANCELED;
				$payout->save();
				
				$credits = $payout->member->cm_credits;
				$credits[ $payout->amount->currency ]->amount = $credits[ $payout->amount->currency ]->amount->add( $payout->amount->amount );
				$payout->member->cm_credits = $credits;
				$payout->member->save();
				
				\IPS\nexus\Customer::loggedIn()->log( 'payout', array( 'type' => 'cancel', 'amount' => $payout->amount->amount, 'currency' => $payout->amount->currency, 'payout_id' => $payout->id ) );
			}
		}
		catch ( \OutOfRangeException $e ) { }
		
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=nexus&module=clients&controller=credit', 'front', 'clientscredit', array(), \IPS\Settings::i()->nexus_https ) );
	}	
}