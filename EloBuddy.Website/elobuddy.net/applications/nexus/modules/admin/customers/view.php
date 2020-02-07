<?php
/**
 * @brief		View Customer
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		11 Feb 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\admin\customers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * View
 */
class _view extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Member
	 */
	protected $member;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_view' );
		
		try
		{
			$this->member = \IPS\nexus\Customer::load( \IPS\Request::i()->id );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2X233/1', 404, '' );
		}
		
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'customer.css', 'nexus', 'admin' ) );
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'support.css', 'nexus', 'admin' ) );
		\IPS\Output::i()->title = "{$this->member->cm_name}";		
		
		parent::execute();
	}

	/**
	 * View Customer
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$member = $this->member;
		
		/* Notes */
		$notes = NULL;
		$noteCount = 0;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_view' ) )
		{
			$noteCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_notes', array( 'note_member=?', $this->member->member_id ) )->first();
			
			$notes = new \IPS\Helpers\Table\Db( 'nexus_notes', $this->member->acpUrl()->setQueryString( array( 'tab' => 'notes', 'support' => isset( \IPS\Request::i()->support ) ? \IPS\Request::i()->support : 0 ) ), array( 'note_member=?', $this->member->member_id ) );
			$notes->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'notes' );
			$notes->sortBy = 'note_date';
			
			$notes->parsers = array(
				'note_member'	=> function( $val )
				{
					return \IPS\Member::load( $val );
				},
				'note_text'		=> function( $val )
				{
					return $val;
				}
			);
			
			$notes->rowButtons = function( $row ) use ( $member )
			{
				$return = array();
				if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_edit' ) )
				{
					if ( !isset( \IPS\Request::i()->support ) or !\IPS\Request::i()->support )
					{
						$return['edit'] = array(
							'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'noteForm', 'note_id' => $row['note_id'], 'support' => isset( \IPS\Request::i()->support ) ? \IPS\Request::i()->support : 0 ) ),
							'title'	=> 'edit',
							'icon'	=> 'pencil',
							'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit_note') )
						);
					}
					else
					{
						$return['edit'] = array(
							'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'noteForm', 'note_id' => $row['note_id'], 'support' => isset( \IPS\Request::i()->support ) ? \IPS\Request::i()->support : 0 ) ),
							'title'	=> 'edit',
							'icon'	=> 'pencil',
						);
					}
				}
				if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_delete' ) )
				{
					$return['delete'] = array(
						'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'deleteNote', 'note_id' => $row['note_id'], 'support' => isset( \IPS\Request::i()->support ) ? \IPS\Request::i()->support : 0 ) ),
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'data'	=> array( 'delete' => '' )
					);
				}
				return $return;
			};
			
			if ( ( !isset( \IPS\Request::i()->support ) or !\IPS\Request::i()->support ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customer_notes_add' ) )
			{
				$notes->rootButtons = array(
					'add'	=> array(
						'link'	=> $this->member->acpUrl()->setQueryString( array( 'do' => 'noteForm', 'support' => isset( \IPS\Request::i()->support ) ? \IPS\Request::i()->support : 0 ) ),
						'title'	=> 'add',
						'icon'	=> 'plus',
						'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add_note') )
					)
				);
			}
			
			if ( \IPS\Request::i()->view === 'notes' )
			{
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->customerPopup( $notes );
				return;
			}
			else
			{
				$notes->limit = 2;
				$notes->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'notesOverview' );
				$notes->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'notesOverviewRows' );
			}
		}
		
		/* Purchases */
		$purchaseCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_show=1', $this->member->member_id ) )->first();
		$purchaseRootCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_show=1 AND ps_parent=0', $this->member->member_id ) )->first();
		$purchases = \IPS\nexus\Purchase::tree( $this->member->acpUrl()->setQueryString( 'tab', 'purchases' ), array( array( 'ps_member=?', $this->member->member_id ) ) );
		$purchases->rootsPerPage = 15;
		$purchases->getTotalRoots = function()
		{
			return NULL;
		};
		if ( \IPS\Request::i()->isAjax() and \IPS\Request::i()->tab === 'purchases' )
		{
			\IPS\Output::i()->output = $purchases;
			return;
		}
		
		/* Invoices */
		$invoiceCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_invoices', array( 'i_member=?', $this->member->member_id ) )->first();
		$invoices = \IPS\nexus\Invoice::table( array( 'i_member=?', $this->member->member_id ), $this->member->acpUrl()->setQueryString( 'tab', 'invoices' ), 'c' );
		$invoices->limit = 15;
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'invoices_add' ) )
		{
			$invoices->rootButtons = array(
				'add'	=> array(
					'link'	=> \IPS\Http\Url::internal( "app=nexus&module=payments&controller=invoices&do=generate&member={$this->member->member_id}" ),
					'title'	=> 'add',
					'icon'	=> 'plus',
				)
			);
		}

		$invoices->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'invoicesTable' );
		$invoices->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'invoicesTableRows' );
		/*if ( \IPS\Request::i()->isAjax() and \IPS\Request::i()->tab === 'invoices' )
		{
			\IPS\Output::i()->output = $invoices;
			return;
		}*/
		
		/* Addresses */
		if ( \IPS\Request::i()->view === 'addresses' )
		{
			$addresses = new \IPS\Helpers\Table\Db( 'nexus_customer_addresses', $this->member->acpUrl()->setQueryString( 'tab', 'addresses' ), array( 'member=?', $this->member->member_id ) );
			$addresses->sortBy = 'primary_billing, primary_shipping, added';
			$addresses->include = array( 'address', 'primary_billing', 'primary_shipping' );
			$addresses->parsers = array( 'address' => function( $val )
			{
				return \IPS\GeoLocation::buildFromJson( $val )->toString( '<br>' );
			} );
			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_details' ) )
			{
				$addresses->rootButtons = array(
					'add'	=> array(
						'link'	=> $this->member->acpUrl()->setQueryString( 'do', 'addressForm' ),
						'title'	=> 'add',
						'icon'	=> 'plus',
						'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add_address') )
					)
				);
				$addresses->rowButtons = function( $row ) use ( $member )
				{
					return array(
						'edit'	=> array(
							'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'addressForm', 'address_id' => $row['id'] ) ),
							'title'	=> 'edit',
							'icon'	=> 'pencil',
							'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('edit_address') )
						),
						'delete'	=> array(
							'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'deleteAddress', 'address_id' => $row['id'] ) ),
							'title'	=> 'delete',
							'icon'	=> 'times-circle',
							'data'	=> array( 'delete' => '' )
						)
					);
				};
			}
		
			$addresses->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'addressTable' );
			$addresses->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'addressTableRows' );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->customerPopup( $addresses );
			return;
		}
		else
		{
			$primaryBillingAddress = NULL;
			try
			{
				$primaryBillingAddress = \IPS\nexus\Customer\Address::constructFromData( \IPS\Db::i()->select( '*', 'nexus_customer_addresses', array( 'member=? AND primary_billing=1', $this->member->member_id ) )->first() )->address;
			}
			catch ( \UnderflowException $e ) { }
		}
		$addressCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_addresses', array( 'member=?', $this->member->member_id ) )->first();
		
		/* Credit Cards */
		$cards = '';
		$cardCount = 0;
		if ( count( \IPS\nexus\Gateway::cardStorageGateways() ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_cards' ) )
		{
			$cardCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_cards', array( 'card_member=?', $this->member->member_id ) )->first();
			$cards = array();
			foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_customer_cards', array( 'card_member=?', $this->member->member_id ), NULL, \IPS\Request::i()->view === 'cards' ? NULL : 2 ), 'IPS\nexus\Customer\CreditCard' ) as $card )
			{
				try
				{
					$cardData = $card->card;
					$cards[ $card->id ] = array(
						'id'			=> $card->id,
						'card_type'		=> $cardData->type,
						'card_number'	=> $cardData->lastFour,
						'card_expire'	=> str_pad( $cardData->expMonth , 2, '0', STR_PAD_LEFT ). '/' . $cardData->expYear
					);
				}
				catch ( \Exception $e ) { }
			}
			$cards = new \IPS\Helpers\Table\Custom( $cards, $this->member->acpUrl()->setQueryString( 'tab', 'cards' ) );
			$cards->rootButtons = array(
				'add'	=> array(
					'link'	=> $this->member->acpUrl()->setQueryString( 'do', 'addCard' ),
					'title'	=> 'add',
					'icon'	=> 'plus',
					'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add_card') )
				)
			);
			$cards->rowButtons = function( $row ) use ( $member )
			{
				return array(
					'delete'	=> array(
						'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'deleteCard', 'card_id' => $row['id'] ) ),
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'data'	=> array( 'delete' => '' )
					)
				);
			};
			
			if ( \IPS\Request::i()->view === 'cards' )
			{
				$cards->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'cardsTable' );
				$cards->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'cardsTableRows' );

				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->customerPopup( $cards );
				return;
			}
			else
			{
				$cards->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'cardsOverview' );
				$cards->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'cardsOverviewRows' );
			}
		}
		
		/* Billing Agreements */
		$billingAgreements = '';
		$billingAgreementCount = 0;
		if ( count( \IPS\nexus\Gateway::billingAgreementGateways() ) and \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'payments', 'billingagreements_view' ) )
		{
			$billingAgreementCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_billing_agreements', array( 'ba_member=? AND ba_canceled=0', $this->member->member_id ) )->first();
			$billingAgreements = array();
			foreach ( \IPS\Db::i()->select( '*', 'nexus_billing_agreements', array( 'ba_member=? AND ba_canceled=0', $this->member->member_id ), NULL, \IPS\Request::i()->view === 'billingagreements' ? NULL : 2 ) as $billingAgreement )
			{
				$billingAgreements[ $billingAgreement['ba_id'] ] = array(
					'id'						=> $billingAgreement['ba_id'],
					'gw_id'						=> $billingAgreement['ba_gw_id'],
					'started'					=> $billingAgreement['ba_started'],
					'next_cycle'				=> $billingAgreement['ba_next_cycle'],
				);
			}
			$billingAgreements = new \IPS\Helpers\Table\Custom( $billingAgreements, $this->member->acpUrl()->setQueryString( 'tab', 'billingagreements' ) );
			$billingAgreements->parsers = array(
				'started'	=> function( $val ) {
					return $val ? \IPS\DateTime::ts( $val )->relative() : null;
				},
				'next_cycle'	=> function( $val ) {
					return $val ? \IPS\DateTime::ts( $val )->relative() : null;
				},
			);
			$billingAgreements->rowButtons = function( $row, $id )
			{
				return array(
					'view'	=> array(
						'link'	=> \IPS\Http\Url::internal("app=nexus&module=payments&controller=billingagreements&id={$id}"),
						'title'	=> 'view',
						'icon'	=> 'search',
					)
				);
			};
			if ( \IPS\Request::i()->view === 'billingagreements' )
			{
				$billingAgreements->exclude = array( 'id', 'last_transaction_currency' );
				$billingAgreements->langPrefix = 'ba_';
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->customerPopup( $billingAgreements );
				return;
			}
			else
			{
				$billingAgreements->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'billingAgreementsOverview' );
				$billingAgreements->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'billingAgreementsOverviewRows' );
			}
		}
		
		/* Alternative Contacts */
		$altContactCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_alternate_contacts', array( 'main_id=?', $this->member->member_id ) )->first();
		$alternativeContacts = new \IPS\Helpers\Table\Db( 'nexus_alternate_contacts', $this->member->acpUrl()->setQueryString( 'tab', 'alternatives' ), array( 'main_id=?', $this->member->member_id ) );
		$alternativeContacts->langPrefix = 'altcontactTable_';
		$alternativeContacts->include = array( 'alt_id', 'purchases', 'billing', 'support' );
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_edit_details' ) )
		{
			$alternativeContacts->parsers = array(
				'alt_id'	=> function( $val )
				{
					return \IPS\nexus\Customer::load( $val )->name;
				},
				'email'		=> function ( $val, $row )
				{
					return \IPS\nexus\Customer::load( $row['alt_id'] )->email;
				},
				'purchases'	=> function( $val )
				{
					return implode( '<br>', array_map( function( $id )
					{
						try
						{
							return \IPS\Theme::i()->getTemplate('purchases')->link( \IPS\nexus\Purchase::load( $id ) );
						}
						catch ( \OutOfRangeException $e )
						{
							return '';
						}
					}, explode( ',', $val ) ) );
				},
				'billing'	=> function( $val )
				{
					return $val ? "<i class='fa fa-check'></i>" : "<i class='fa fa-times'></i>";
				},
				'support'	=> function( $val )
				{
					return $val ? "<i class='fa fa-check'></i>" : "<i class='fa fa-times'></i>";
				}
			);
			
			$alternativeContacts->rootButtons = array(
				'add'	=> array(
					'link'	=> $this->member->acpUrl()->setQueryString( 'do', 'alternativeContactForm' ),
					'title'	=> 'add',
					'icon'	=> 'plus',
					'data'	=> array( 'ipsDialog' => true, 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('add_address') )
				)
			);
			$alternativeContacts->rowButtons = function( $row ) use ( $member )
			{
				return array(
					'edit'	=> array(
						'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'alternativeContactForm', 'alt_id' => $row['alt_id'] ) ),
						'title'	=> 'edit',
						'icon'	=> 'pencil',
						'data'	=> array( 'ipsDialog' => true )
					),
					'delete'	=> array(
						'link'	=> $member->acpUrl()->setQueryString( array( 'do' => 'deleteAlternativeContact', 'alt_id' => $row['alt_id'] ) ),
						'title'	=> 'delete',
						'icon'	=> 'times-circle',
						'data'	=> array( 'delete' => '' )
					)
				);
			};
		}
		if ( \IPS\Request::i()->view === 'alternatives' )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->customerPopup( $alternativeContacts );
			return;
		}
		else
		{
			$alternativeContacts->include[] = 'email';
			$alternativeContacts->limit = 2;
			$alternativeContacts->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'altContactsOverview' );
			$alternativeContacts->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'altContactsOverviewRows' );
		}
		
		/* Parent Alternative Contacts */
		$parents = array();
		foreach ( \IPS\Db::i()->select( 'main_id', 'nexus_alternate_contacts', array( 'alt_id=?', $this->member->member_id ) ) as $row )
		{
			$parents[] = \IPS\nexus\Customer::load( $row );
		}
		
		/* Referrals */
		try
		{
			$referredBy = \IPS\nexus\Customer::load( \IPS\Db::i()->select( 'referred_by', 'nexus_referrals', array( 'member_id=?', $this->member->member_id ) )->first() );
		}
		catch ( \UnderflowException $e )
		{
			$referredBy = NULL;
		}
		$referCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_referrals', array( 'referred_by=?', $this->member->member_id ) )->first();
		$referrals = new \IPS\Helpers\Table\Db( 'nexus_referrals', $this->member->acpUrl()->setQueryString( 'tab', 'referrals' ), array( 'referred_by=?', $this->member->member_id ) );
		$referrals->langPrefix = 'ref_';
		$referrals->include = array( 'member_id', 'amount' );
		$referrals->sortBy = $referrals->sortBy ?: 'member_id';
		$referrals->parsers = array(
			'member_id'	=> function( $v )
			{
				try
				{
					return \IPS\nexus\Customer::load( $v )->link();
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'email'	=> function( $v, $row )
			{
				try
				{
					return \IPS\nexus\Customer::load( $row['member_id'] )->email;
				}
				catch ( \OutOfRangeException $e )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('deleted_member');
				}
			},
			'amount'	=> function( $v ) use ( $member )
			{
				$return = array();
				if ( $v )
				{
					foreach ( json_decode( $v, TRUE ) as $currency => $amount )
					{
						$return[] = new \IPS\nexus\Money( $amount, $currency );
					}
				}
				else
				{
					$return[] = new \IPS\nexus\Money( 0, $member->defaultCurrency() );
				}
				return implode( '<br>', $return );
			}
		);

		if ( \IPS\Request::i()->isAjax() and \IPS\Request::i()->view === 'referrals' )
		{
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->customerPopup( $referrals );
			return;
		}
		else
		{
			$referrals->include[] = 'email';
			$referrals->limit = 2;
			$referrals->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'referralsOverview' );
			$referrals->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'referralsOverviewRows' );
		}
		
		/* Support Requests */
		$supportCount = \IPS\Db::i()->select( 'COUNT(*)', 'nexus_support_requests', array( 'r_member=?', $this->member->member_id ) )->first();
		$support = \IPS\nexus\Support\Request::table( $this->member->acpUrl()->setQueryString( 'tab', 'support' ), array( 'r_member=?', $this->member->member_id ), 'r_started', 'desc' );
		$support->limit = 15;
		$support->exclude = array( 'r_member' );
		$support->tableTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'supportTable' );
		$support->rowsTemplate = array( \IPS\Theme::i()->getTemplate('customers'), 'supportTableRows' );
		
		/* History */
		$history = new \IPS\nexus\Customer\History( $this->member->acpUrl()->setQueryString( 'do', 'history' ), array( 'log_member=?', $this->member->member_id ) );
		$history->limit = 16;
		
		/* Standing */
		$standing = array();
		$time = time();
		foreach ( \IPS\nexus\Money::currencies() as $currency )
		{
			/* Total spent */
			$comparisons = \IPS\Db::i()->select( 'AVG(xval) AS avg, MAX(xval) AS max, MIN(xval) AS min', \IPS\Db::i()->select( "(SUM(t_amount)-SUM(t_partial_refund)) as xval", 'nexus_transactions', array( '( t_status=? OR t_status=? ) AND t_currency=? AND t_member<>0', \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, $currency ), NULL, NULL, 't_member' ) )->first();
			$val = \IPS\Db::i()->select( 'SUM(t_amount)-SUM(t_partial_refund)', 'nexus_transactions', array( 't_member=? AND ( t_status=? OR t_status=? ) AND t_currency=?', $this->member->member_id, \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, $currency ) )->first();
			$standing[ \IPS\Member::loggedIn()->language()->addToStack( 'total_spent_currency', FALSE, array( 'sprintf' => array( $currency ) ) ) ] = array(
				'value'		=> new \IPS\nexus\Money( $val ?: '0', $currency ),
				'avg'		=> new \IPS\nexus\Money( $comparisons['avg'], $currency ),
				'highest'	=> new \IPS\nexus\Money( $comparisons['max'], $currency ),
				'lowest'	=> new \IPS\nexus\Money( $comparisons['min'], $currency ),
				'lowval'	=> $comparisons['min'],
				'highval'	=> $comparisons['max'],
				'avgval'	=> $comparisons['avg'],
				'thisval'	=> $val,
				'avgpct'	=> $comparisons['max'] ? round( ( ( $comparisons['avg'] - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
				'thispct'	=> $comparisons['max'] ? round( ( ( $val - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
				'type'		=> 'totalspent'
			);
			
			/* Average monthly spend */
			$sum = "( SUM(t_amount) / ( ( {$time} - core_members.joined ) / 2592000 ) )";
			$where = array( array( "( t_status=? OR t_status=? ) AND ({$time} - core_members.joined > 2592000) AND t_currency=?", \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, $currency ) );
			try
			{
				$val = \IPS\Db::i()->select( "{$sum} as sum", 'nexus_transactions', array_merge( $where, array( array( 't_member=?', $this->member->member_id ) ) ), 'joined', NULL, 'joined' )->join( 'core_members', 'member_id=t_member' )->first();
			}
			catch( \UnderflowException $e )
			{
				$val = 0;
			}
			$comparisons = \IPS\Db::i()->select( 'AVG(xval) AS avg, MAX(xval) AS max, MIN(xval) AS min', \IPS\Db::i()->select( "{$sum} as xval", 'nexus_transactions', array_merge( $where, array( array( 't_member<>0' ) ) ), NULL, NULL, array( 't_member', 'joined' ) ) )->join( 'core_members', 'member_id=t_member' )->first();
			$standing[ \IPS\Member::loggedIn()->language()->addToStack( 'average_spend_currency', FALSE, array( 'sprintf' => array( $currency ) ) ) ] = array(
				'value'		=> new \IPS\nexus\Money( $val, $currency ),
				'avg'		=> new \IPS\nexus\Money( $comparisons['avg'], $currency ),
				'highest'	=> new \IPS\nexus\Money( $comparisons['max'], $currency ),
				'lowest'	=> new \IPS\nexus\Money( $comparisons['min'], $currency ),
				'lowval'	=> $comparisons['min'],
				'highval'	=> $comparisons['max'],
				'avgval'	=> $comparisons['avg'],
				'thisval'	=> $val,
				'avgpct'	=> $comparisons['max'] ? round( ( ( $comparisons['avg'] - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
				'thispct'	=> $comparisons['max'] ? round( ( ( $val - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
				'type'		=> 'avgspent'
			);			
		}
		
		$sum = "ROUND( ( AVG( i_paid - i_date ) / 86400 ), 2 )";
		$where = array( array( "i_status=? AND i_date>0 AND i_paid>0", \IPS\nexus\Invoice::STATUS_PAID ) );
		$val = \IPS\Db::i()->select( "{$sum} as sum", 'nexus_invoices', array_merge( $where, array( array( 'i_member=?', $this->member->member_id ) ) ) )->join( 'core_members', 'member_id=i_member' )->first();
		$comparisons = \IPS\Db::i()->select( 'AVG(xval) AS avg, MAX(xval) AS max, MIN(xval) AS min', \IPS\Db::i()->select( "{$sum} as xval", 'nexus_invoices', array_merge( $where, array( array( 'i_member<>0' ) ) ), NULL, NULL, array( 'i_member', 'joined' ) ) )->join( 'core_members', 'member_id=i_member' )->first();
		$standing[ \IPS\Member::loggedIn()->language()->addToStack( 'average_time_to_pay' ) ] = array(
			'value'		=> \IPS\Member::loggedIn()->language()->formatNumber( $val, 2 ) . ' ' . \IPS\Member::loggedIn()->language()->addToStack('days'),
			'avg'		=> \IPS\Member::loggedIn()->language()->formatNumber( $comparisons['avg'], 2 ) . ' ' . \IPS\Member::loggedIn()->language()->addToStack('days'),
			'highest'	=> \IPS\Member::loggedIn()->language()->formatNumber( $comparisons['max'], 2 ) . ' ' . \IPS\Member::loggedIn()->language()->addToStack('days'),
			'lowest'	=> \IPS\Member::loggedIn()->language()->formatNumber( $comparisons['min'], 2 ) . ' ' . \IPS\Member::loggedIn()->language()->addToStack('days'),
			'lowval'	=> $comparisons['min'],
			'highval'	=> $comparisons['max'],
			'avgval'	=> $comparisons['avg'],
			'thisval'	=> $val,
			'avgpct'	=> floatval( $comparisons['max'] ) ? round( ( ( $comparisons['avg'] - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
			'thispct'	=> floatval( $comparisons['max'] ) ? round( ( ( $val - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
			'type'		=> 'timetopay'
		);
		
		$sum = "( COUNT(*) / ( ( {$time} - core_members.joined ) / 2592000 ) )";
		try
		{
			$val = \IPS\Db::i()->select( "{$sum} as sum", 'nexus_support_requests', array( 'r_member=?', $this->member->member_id ), 'joined', NULL, 'joined' )->join( 'core_members', 'member_id=r_member' )->first();
		}
		catch( \UnderflowException $e )
		{
			$val = 0;
		}
		$comparisons = \IPS\Db::i()->select( 'AVG(xval) AS avg, MAX(xval) AS max, MIN(xval) AS min', \IPS\Db::i()->select( "{$sum} as xval", 'nexus_support_requests', 'r_member<>0', NULL, NULL, 'joined' ) )->join( 'core_members', 'member_id=r_member' )->first();
		$standing[ \IPS\Member::loggedIn()->language()->addToStack( 'average_monthly_support_requests' ) ] = array(
			'value'		=> \IPS\Member::loggedIn()->language()->formatNumber( $val, 2 ),
			'avg'		=> \IPS\Member::loggedIn()->language()->formatNumber( $comparisons['avg'], 2 ),
			'highest'	=> \IPS\Member::loggedIn()->language()->formatNumber( $comparisons['max'], 2 ),
			'lowest'	=> \IPS\Member::loggedIn()->language()->formatNumber( $comparisons['min'], 2 ),
			'lowval'	=> $comparisons['min'],
			'highval'	=> $comparisons['max'],
			'avgval'	=> $comparisons['avg'],
			'thisval'	=> $val,
			'avgpct'	=> $comparisons['max'] ? round( ( ( $comparisons['avg'] - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
			'thispct'	=> $comparisons['max'] ? round( ( ( $val - $comparisons['min'] ) / $comparisons['max'] ) * 100, 1 ) : 0,
			'type'		=> 'support'
		);	
		
		/* Sparkline */
		$rows = array();
		$oneYearAgo = \IPS\DateTime::create()->sub( new \DateInterval('P1Y') );
		$date = clone $oneYearAgo;
		$endOfThisMonth = mktime( 23, 59, 59, date('n'), date('t'), date('Y') );
		while ( $date->getTimestamp() < $endOfThisMonth )
		{
			foreach ( \IPS\nexus\Money::currencies() as $currency )
			{
				$rows[ $date->format( 'n Y' ) ][ $currency ] = 0;
			}
			$date->add( new \DateInterval( 'P1M' ) );
		}
		$sparkline = new \IPS\Helpers\Chart;
		foreach( \IPS\Db::i()->select( 'DATE_FORMAT( FROM_UNIXTIME(t_date), \'%c %Y\' ) AS time, SUM(t_amount)-SUM(t_partial_refund) AS amount, t_currency', 'nexus_transactions', array( array( "t_member=? AND ( t_status=? OR t_status=? ) AND t_date>?", $this->member->member_id, \IPS\nexus\Transaction::STATUS_PAID, \IPS\nexus\Transaction::STATUS_PART_REFUNDED, $oneYearAgo->getTimestamp() ) ), NULL, NULL, array( 'time', 't_currency' ) ) as $row )
		{
			if ( isset( $rows[ $row['time'] ][ $row['t_currency'] ] ) ) // Currency may no longer exist
			{
				$rows[ $row['time'] ][ $row['t_currency'] ] += $row['amount'];
			}
		}
		$sparkline->addHeader( \IPS\Member::loggedIn()->language()->addToStack('date'), 'date' );
		foreach ( \IPS\nexus\Money::currencies() as $currency )
		{
			$sparkline->addHeader( $currency, 'number' );
		}
		foreach ( $rows as $time => $row )
		{
			$datetime = new \IPS\DateTime;
			$datetime->setTime( 0, 0, 0 );
			$exploded = explode( ' ', $time );
			$datetime->setDate( $exploded[1], $exploded[0], 1 );

			foreach( $row as $currency => $value )
			{
				$row[ $currency ] = number_format( $value, 2, '.', '' );
			}
			
			$sparkline->addRow( array_merge( array( $datetime ), $row ) );
		}
				
		$sparkline = $sparkline->render( 'LineChart', array(
			'backgroundColor'	=> '#F3F3F3',
			'chartArea'			=> array(
				'left'				=> 0,
				'top'				=> 0,	
				'width'				=> '100%',
				'height'			=> '100%',
			),
			'hAxis'				=> array(
				'baselineColor'		=> '#F3F3F3',
				'gridlines'			=> array(
					'count'				=> 0,
				)
			),
			'height'			=> 60 * ceil( ( ( count( \IPS\nexus\Money::currencies() ) * 2 ) + 2 ) / 4 ),
			'legend'			=> array(
				'position'			=> 'none',
			),
			'vAxis'				=> array(
				'baselineColor'		=> '#F3F3F3',
				'gridlines'			=> array(
					'count'				=> 0,
				)
			),
		) );
		
		/* Display */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'nexus', 'customers', 'customers_void' ) )
		{
			\IPS\Output::i()->sidebar['actions']['void'] = array(
				'title'		=> 'void_account',
				'icon'		=> 'times',
				'link'		=> $member->acpUrl()->setQueryString( 'do', 'void' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('void_account') )
			);
		}

		\IPS\Output::i()->sidebar['actions']['edit'] = array(
			'title'		=> 'customer_edit_member',
			'icon'		=> 'pencil',
			'link'		=> \IPS\Http\Url::internal( 'app=core&module=members&controller=members&do=edit&id=' . $this->member->member_id )
		);
		
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('customers')->view( $member, (string) $notes, $noteCount, $primaryBillingAddress, (string) $cards, $cardCount, $parents, (string) $alternativeContacts, $altContactCount, $purchases, $purchaseCount, $invoices, $invoiceCount, $history, $referredBy, $referrals, $referCount, $standing, $support, $supportCount, $sparkline, $purchaseRootCount, $billingAgreements, $billingAgreementCount, $addressCount );
	}
	
	/**
	 * View Purchase List
	 *
	 * @return	void
	 */
	protected function purchaseList()
	{
		$purchases = \IPS\nexus\Purchase::tree( $this->member->acpUrl()->setQueryString( 'do', 'purchaseList' ), array( array( 'ps_member=?', $this->member->member_id ) ) );
		$purchases->rootsPerPage = 25;
		$purchases->getTotalRoots = function() 
		{
			return \IPS\Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_member=? AND ps_show=1 AND ps_parent=0', $this->member->member_id ) )->first();
		};
		\IPS\Output::i()->output = $purchases;
		\IPS\Output::i()->title	 = \IPS\Member::loggedIn()->language()->addToStack( 'members_purchases', FALSE, array( 'sprintf' => array( $this->member->cm_name ) ) );
	}
	
	/**
	 * View History
	 *
	 * @return	void
	 */
	public function history()
	{	
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		$history = new \IPS\nexus\Customer\History( $this->member->acpUrl()->setQueryString( 'do', 'history' ), array( 'log_member=?', $this->member->member_id ) );
		$history->limit = 30;
		
		\IPS\Output::i()->output = $history;
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack( 'customers_all_history', FALSE, array( 'sprintf' => array( $this->member->cm_name ) ) );
		
		\IPS\Output::i()->sidebar['actions'][] = array(
			'icon'	=> 'arrow-left',
			'title'	=> 'view_account',
			'link'	=> $this->member->acpUrl()
		);
	}
		
	/**
	 * Edit Customer Fields
	 *
	 * @return	void
	 */
	public function edit()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		
		$form = new \IPS\Helpers\Form;
		
		$form->add( new \IPS\Helpers\Form\Text( 'cm_first_name', $this->member->cm_first_name, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Text( 'cm_last_name', $this->member->cm_last_name, TRUE ) );
		
		foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
		{
			$column = $field->column;
			$form->add( $field->buildHelper( $this->member->$column ) );
		}
		
		if ( $values = $form->values( TRUE ) )
		{
			$changes = array();
			foreach ( array( 'cm_first_name', 'cm_last_name' ) as $k )
			{
				if ( $values[ $k ] != $this->member->$k )
				{
					/* We only need to log this once, so do it if it isn't set */
					if ( !isset( $changes['name'] ) )
					{
						$changes['name'] = $this->member->cm_name;
					}
					
					$this->member->$k = $values[ $k ];
				}
			}
			foreach ( \IPS\nexus\Customer\CustomField::roots() as $field )
			{
				$column = $field->column;
				if ( $this->member->$column != $values["nexus_ccfield_{$field->id}"] )
				{
					$changes['other'][] = array( 'name' => 'nexus_ccfield_' . $field->id, 'value' => $field->displayValue( $values["nexus_ccfield_{$field->id}"] ), 'old' => $this->member->$column );
					$this->member->$column = $values["nexus_ccfield_{$field->id}"];
				}
				
				if ( $field->type === 'Editor' )
				{
					$field->claimAttachments( $this->member->member_id );
				}
			}
			if ( !empty( $changes ) )
			{
				$this->member->log( 'info', $changes );
			}
			$this->member->save();
			\IPS\Output::i()->redirect( $this->member->acpUrl() );
		}
		
		\IPS\Output::i()->output = $form;
	}
	
	/**
	 * Edit Credits
	 *
	 * @return	void
	 */
	public function credits()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_edit_credit' );
		
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		foreach ( \IPS\nexus\Money::currencies() as $currency )
		{
			$form->add( new \IPS\Helpers\Form\Number( $currency, isset( $this->member->cm_credits[ $currency ] ) ? $this->member->cm_credits[ $currency ]->amount : 0, FALSE, array( 'min' => 0, 'decimals' => \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ) ), NULL, NULL, $currency ) );
		}
		
		if ( $values = $form->values() )
		{
			$credits = $this->member->cm_credits;
			foreach ( $values as $currency => $amount )
			{
				$amount = new \IPS\Math\Number( number_format( $amount, \IPS\nexus\Money::numberOfDecimalsForCurrency( $currency ), '.', '' ) );
				if ( ( isset( $this->member->cm_credits[ $currency ] ) and $this->member->cm_credits[ $currency ]->amount->compare( $amount ) !== 0 ) or $amount )
				{
					$this->member->log( 'comission', array( 'type' => 'manual', 'old' => isset( $this->member->cm_credits[ $currency ] ) ? $this->member->cm_credits[ $currency ]->amountAsString() : 0, 'new' => (string) $amount, 'currency' => $currency ) );
				}
				$credits[ $currency ] = new \IPS\nexus\Money( $amount, $currency );
			}
			$this->member->cm_credits = $credits;
			$this->member->save();
			\IPS\Output::i()->redirect( $this->member->acpUrl() );
		}
		
		\IPS\Output::i()->output = $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'forms', 'core', 'front' ) ), 'popupTemplate' ) );
	}
	
	/**
	 * Add/Edit Note
	 *
	 * @return	void
	 */
	public function noteForm()
	{
		$noteId = NULL;
		$note = NULL;
		if ( \IPS\Request::i()->note_id )
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'customer_notes_edit' );
			$noteId = intval( \IPS\Request::i()->note_id );
			try
			{
				$note = \IPS\Db::i()->select( 'note_text', 'nexus_notes', array( 'note_id=?', \IPS\Request::i()->note_id ) )->first();
			}
			catch ( \UnderflowException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2X233/3', 404, '' );
			}
		}
		else
		{
			\IPS\Dispatcher::i()->checkAcpPermission( 'customer_notes_add' );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\Editor( 'customer_note', $note, TRUE, array(
			'app'			=> 'nexus',
			'key'			=> 'Customer',
			'autoSaveKey'	=> $noteId ? "nexus-note-{$this->member->member_id}-{$noteId}" : "nexus-note-{$this->member->member_id}-new",
			'attachIds'		=> $noteId ? array( $this->member->member_id, $noteId, 'note' ) : NULL
		) ) );
		if ( $values = $form->values() )
		{
			if ( \IPS\Request::i()->note_id )
			{
				\IPS\Db::i()->update( 'nexus_notes', array(
					'note_text'	=> $values['customer_note']
				) );
				
				$this->member->log( 'note', 'edited' );
			}
			else
			{
				$noteId = \IPS\Db::i()->insert( 'nexus_notes', array(
					'note_member'	=> $this->member->member_id,
					'note_text'		=> $values['customer_note'],
					'note_author'	=> \IPS\Member::loggedIn()->member_id,
					'note_date'		=> time(),
				) );
				
				\IPS\File::claimAttachments( "nexus-note-{$this->member->member_id}-new", $this->member->id, $noteId, 'note' );
				
				$this->member->log( 'note', 'added' );
			}
			
			if ( isset( \IPS\Request::i()->support ) and \IPS\Request::i()->support )
			{
				try
				{
					\IPS\Output::i()->redirect( \IPS\nexus\Support\Request::load( \IPS\Request::i()->support )->acpUrl() );
				}
				catch ( \OutOfRangeException $e ) {}
			}
			\IPS\Output::i()->redirect( $this->member->acpUrl() );
		}
		\IPS\Output::i()->output = $form;
	}
	
	/** 
	 * Delete Note
	 *
	 * @return	void
	 */
	public function deleteNote()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customer_notes_delete' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		\IPS\Db::i()->delete( 'nexus_notes', array( 'note_id=?', \IPS\Request::i()->note_id ) );
		$this->member->log( 'note', 'deleted' );
		
		if ( isset( \IPS\Request::i()->support ) and \IPS\Request::i()->support )
		{
			try
			{
				\IPS\Output::i()->redirect( \IPS\nexus\Support\Request::load( \IPS\Request::i()->support )->acpUrl() );
			}
			catch ( \OutOfRangeException $e ) {}
		}
		\IPS\Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/**
	 * Add Address
	 *
	 * @return	void
	 */
	public function addressForm()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		
		if ( \IPS\Request::i()->address_id )
		{
			try
			{
				$address = \IPS\nexus\Customer\Address::load( \IPS\Request::i()->address_id );
				if ( $address->member !== $this->member )
				{
					throw new \OutOfRangeException;
				}
			}
			catch ( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2X233/2', 404, '' );
			}
		}
		else
		{
			$address = new \IPS\nexus\Customer\Address;
			$address->member = $this->member;
			$address->primary_billing = ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_addresses', array( 'member=? AND primary_billing=1', $this->member->member_id ) )->first() == 0 );
			$address->primary_shipping = ( \IPS\Db::i()->select( 'COUNT(*)', 'nexus_customer_addresses', array( 'member=? AND primary_shipping=1', $this->member->member_id ) )->first() == 0 );
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Address( 'address', $address->address, TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'primary_billing', $address->primary_billing ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'primary_shipping', $address->primary_shipping ) );
		if ( $values = $form->values() )
		{
			if ( $address->id )
			{
				if ( $values['address'] != $address->address )
				{
					$this->member->log( 'address', array( 'type' => 'edit', 'new' => json_encode( $values['address'] ), 'old' => json_encode( $address->address ) ) );
				}
				if ( $values['primary_billing'] and !$address->primary_billing )
				{
					\IPS\Db::i()->update( 'nexus_customer_addresses', array( 'primary_billing' => 0 ), array( 'member=?', $this->member->member_id ) );
					$this->member->log( 'address', array( 'type' => 'primary_billing', 'details' => json_encode( $values['address'] ) ) );
				}
				if ( $values['primary_shipping'] and !$address->primary_shipping )
				{
					\IPS\Db::i()->update( 'nexus_customer_addresses', array( 'primary_shipping' => 0 ), array( 'member=?', $this->member->member_id ) );
					$this->member->log( 'address', array( 'type' => 'primary_shipping', 'details' => json_encode( $values['address'] ) ) );
				}
			}
			else
			{
				$this->member->log( 'address', array( 'type' => 'add', 'details' => json_encode( $values['address'] ) ) );
			}
			
			$address->address = $values['address'];
			$address->primary_billing = $values['primary_billing'];
			$address->primary_shipping = $values['primary_shipping'];
			$address->save();
			
			\IPS\Output::i()->redirect( $this->member->acpUrl() );
		}
		\IPS\Output::i()->output = $form;
	}
	
	/** 
	 * Delete Address
	 *
	 * @return	void
	 */
	public function deleteAddress()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_edit_details' );
		
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		try
		{
			$address = \IPS\nexus\Customer\Address::load( \IPS\Request::i()->address_id );
			$this->member->log( 'address', array( 'type' => 'delete', 'details' => json_encode( $address->address ) ) );
			$address->delete();
		}
		catch ( \OutOfRangeException $e ) { }
		\IPS\Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/** 
	 * Add Card
	 *
	 * @return	void
	 */
	public function addCard()
	{
		$form = \IPS\nexus\Customer\CreditCard::create( $this->member );
		if ( $form instanceof \IPS\nexus\Customer\CreditCard )
		{
			$this->member->log( 'card', array( 'type' => 'add', 'number' => $form->card->lastFour ) );
			\IPS\Output::i()->redirect( $this->member->acpUrl() );
		}
		else
		{
			\IPS\Output::i()->output = $form;
		}
		
	}
	
	/** 
	 * Delete Card
	 *
	 * @return	void
	 */
	public function deleteCard()
	{
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();

		try
		{
			$card = \IPS\nexus\Customer\CreditCard::load( \IPS\Request::i()->card_id );
			$this->member->log( 'card', array( 'type' => 'delete', 'number' => $card->card->lastFour ) );
			$card->delete();
		}
		catch ( \OutOfRangeException $e ) { }
		\IPS\Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/**
	 * Add/Edit Alternative Contact
	 *
	 * @return	void
	 */
	public function alternativeContactForm()
	{
		$existing = NULL;
		if ( isset( \IPS\Request::i()->alt_id ) )
		{
			try
			{
				$existing = \IPS\nexus\Customer\AlternativeContact::constructFromData( \IPS\Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', $this->member->member_id, \IPS\Request::i()->alt_id ) )->first() );
			}
			catch ( \UnderflowException $e ) {}
		}
				
		$form = new \IPS\Helpers\Form;
		if ( !$existing )
		{
			$form->add( new \IPS\Helpers\Form\Member( 'altcontact_member_admin', NULL, TRUE ) );
		}
		$form->add( new \IPS\Helpers\Form\Node( 'altcontact_purchases_admin', $existing ? iterator_to_array( $existing->purchases ) : NULL, FALSE, array( 'class' => 'IPS\nexus\Purchase', 'forceOwner' => $this->member, 'multiple' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'altcontact_support_admin', $existing ? $existing->support : FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'altcontact_billing_admin', $existing ? $existing->billing : FALSE ) );
		if ( $values = $form->values() )
		{
			if ( $existing )
			{
				$altContact = $existing;
				$this->member->log( 'alternative', array( 'type' => 'edit', 'alt_id' => $altContact->alt_id->member_id, 'alt_name' => $altContact->alt_id->name, 'purchases' => json_encode( $values['altcontact_purchases_admin'] ? $values['altcontact_purchases_admin'] : array() ), 'billing' => $values['altcontact_billing_admin'], 'support' => $values['altcontact_support_admin'] ) );
			}
			else
			{
				$altContact = new \IPS\nexus\Customer\AlternativeContact;
				$altContact->main_id = $this->member;
				$altContact->alt_id = $values['altcontact_member_admin'];
				$this->member->log( 'alternative', array( 'type' => 'add', 'alt_id' => $values['altcontact_member_admin']->member_id, 'alt_name' => $values['altcontact_member_admin']->name, 'purchases' => json_encode( $values['altcontact_purchases_admin'] ? $values['altcontact_purchases_admin'] : array() ), 'billing' => $values['altcontact_billing_admin'], 'support' => $values['altcontact_support_admin'] ) );		
			}
			$altContact->purchases = $values['altcontact_purchases_admin'] ? $values['altcontact_purchases_admin'] : array();
			$altContact->billing = $values['altcontact_billing_admin'];
			$altContact->support = $values['altcontact_support_admin'];
			$altContact->save();
			
			\IPS\Output::i()->redirect( $this->member->acpUrl() );
		}
		\IPS\Output::i()->output = $form;
	}
	
	/** 
	 * Delete Alternative Contact
	 *
	 * @return	void
	 */
	public function deleteAlternativeContact()
	{
		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		try
		{
			$contact = \IPS\nexus\Customer\AlternativeContact::constructFromData( \IPS\Db::i()->select( '*', 'nexus_alternate_contacts', array( 'main_id=? AND alt_id=?', $this->member->member_id, \IPS\Request::i()->alt_id ) )->first() );
			$this->member->log( 'alternative', array( 'type' => 'delete', 'alt_id' => $contact->alt_id->member_id, 'alt_name' => $contact->alt_id->name ) );
			$contact->delete();
		}
		catch ( \OutOfRangeException $e ) { }
		\IPS\Output::i()->redirect( $this->member->acpUrl() );
	}
	
	/** 
	 * Void Account
	 *
	 * @return	void
	 */
	public function void()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'customers_void' );
		
		if ( isset( \IPS\Request::i()->process ) )
		{
			$values = array(
				'void_refund_transactions'	=> \IPS\Request::i()->trans,
				'void_cancel_purchases' 	=> \IPS\Request::i()->purch,
			);
		}
		else
		{		
			$form = new \IPS\Helpers\Form( 'void_account', 'void_account' );
			$form->ajaxOutput = TRUE;
			$form->addMessage( 'void_account_warning', 'ipsMessage ipsMessage_warning' );
			$form->add( new \IPS\Helpers\Form\YesNo( 'void_refund_transactions', TRUE ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'void_cancel_purchases', TRUE ) );
			$form->add( new \IPS\Helpers\Form\YesNo( 'void_cancel_invoices', TRUE ) );
			$form->add( new \IPS\Helpers\Form\Node( 'void_resolve_support', \IPS\Settings::i()->nexus_autoresolve_status, FALSE, array( 'class' => 'IPS\nexus\Support\Status', 'zeroVal' => 'do_not_change' ) ) );
			if ( $this->member->member_id != \IPS\Member::loggedIn()->member_id )
			{
				$form->add( new \IPS\Helpers\Form\YesNo( 'void_ban_account', TRUE ) );
			}
			$form->add( new \IPS\Helpers\Form\Editor( 'void_add_note', NULL, FALSE, array(
				'app'			=> 'nexus',
				'key'			=> 'Customer',
				'autoSaveKey'	=> "nexus-note-{$this->member->member_id}-new",
				'minimize'		=> 'void_add_note_placeholder'
			) ) );
			
			if ( $values = $form->values() )
			{
				if ( $values['void_cancel_invoices'] )
				{
					\IPS\Db::i()->update( 'nexus_invoices', array( 'i_status' => \IPS\nexus\Invoice::STATUS_CANCELED ), array( 'i_member=?', $this->member->member_id ) );
				}
				if ( $values['void_resolve_support'] )
				{
					\IPS\Db::i()->update( 'nexus_support_requests', array( 'r_status' => $values['void_resolve_support']->_id ), array( 'r_member=?', $this->member->member_id ) );
				}
				if ( $this->member->member_id != \IPS\Member::loggedIn()->member_id and $values['void_ban_account'] )
				{
					$this->member->temp_ban = -1;
					$this->member->save();
				}
				if ( $values['void_add_note'] )
				{
					$noteId = \IPS\Db::i()->insert( 'nexus_notes', array(
						'note_member'	=> $this->member->member_id,
						'note_text'		=> $values['void_add_note'],
						'note_author'	=> \IPS\Member::loggedIn()->member_id,
						'note_date'		=> time(),
					) );
					
					\IPS\File::claimAttachments( "nexus-note-{$this->member->member_id}-new", $this->member->id, $noteId, 'note' );
				}
				
				if ( !$values['void_refund_transactions'] and !$values['void_cancel_purchases'] )
				{
					\IPS\Output::i()->redirect( $this->member->acpUrl() );
				}
			}
		}
		
		if ( $values )
		{
			$member = $this->member;
			
			\IPS\Output::i()->output = new \IPS\Helpers\MultipleRedirect( $this->member->acpUrl()->setQueryString( array(
				'do'		=>	'void',
				'process'	=> 1,
				'trans'		=> $values['void_refund_transactions'],
				'purch'		=> $values['void_cancel_purchases'],
			) ), function( $data ) use ( $member )
			{		
				if ( $data == 0 )
				{
					$data = array( 'trans' => 0, 'purch' => 0, 'fail' => array() );
				}
				
				$done = 0;
				
				if ( \IPS\Request::i()->trans )
				{
					foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_transactions', array( 't_member=?', $member->member_id ), 't_id', array( $data['trans'], 10 ) ), 'IPS\nexus\Transaction' ) as $transaction )
					{
						if ( in_array( $transaction->status, array( $transaction::STATUS_PENDING, $transaction::STATUS_WAITING ) ) )
						{
							$transaction->status = $transaction::STATUS_REVIEW;
							$transaction->save();
						}
						elseif ( in_array( $transaction->status, array( $transaction::STATUS_PAID, $transaction::STATUS_HELD, $transaction::STATUS_REVIEW, $transaction::STATUS_PART_REFUNDED ) ) )
						{
							try
							{
								$transaction->refund();
							}
							catch ( \Exception $e )
							{
								$data['fail'][] = $transaction->id;
							}
						}
						
						$data['trans']++;
						$done++;
						if ( $done >= 10 )
						{
							return array( $data, \IPS\Member::loggedIn()->language()->addToStack('processing') );
						}
					}
				}
				
				if ( \IPS\Request::i()->purch )
				{
					foreach ( new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_purchases', array( 'ps_member=?', $member->member_id ), 'ps_id', array( $data['purch'], 10 ) ), 'IPS\nexus\Purchase' ) as $purchase )
					{
						$purchase->cancelled = TRUE;
						$purchase->can_reactivate = FALSE;
						$purchase->save();
						
						$data['purch']++;
						$done++;
						if ( $done >= 10 )
						{
							return array( $data, \IPS\Member::loggedIn()->language()->addToStack('processing') );
						}
					}
				}
				
				$_SESSION['voidAccountFails'] = $data['fail'];
				return NULL;
			}, function() use ( $member )
			{
				if ( count( $_SESSION['voidAccountFails'] ) )
				{
					\IPS\Output::i()->redirect( $member->acpUrl()->setQueryString( 'do', 'voidFails' ) );
				}
				else
				{
					\IPS\Output::i()->redirect( $member->acpUrl() );
				}
			} );
			return;
		}
		else
		{
			\IPS\Output::i()->output = $form;
		}
	}
	
	/** 
	 * Void Account Results
	 *
	 * @return	void
	 */
	public function voidFails()
	{
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'customers' )->voidFails( $_SESSION['voidAccountFails'] );
	}
}