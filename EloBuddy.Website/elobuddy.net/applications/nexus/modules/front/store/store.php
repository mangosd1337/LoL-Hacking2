<?php
/**
 * @brief		Browse Store
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		29 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\nexus\modules\front\store;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Browse Store
 */
class _store extends \IPS\Dispatcher\Controller
{
	/**
	 * @brief	Products per page
	 */
	protected static $productsPerPage = 50;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store.css', 'nexus' ) );

		if ( \IPS\Theme::i()->settings['responsive'] )
		{
			\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'store_responsive.css', 'nexus', 'front' ) );
		}
		
		parent::execute();
	}

	/**
	 * Browse Store
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Work out currenty */
		if ( isset( \IPS\Request::i()->currency ) and in_array( \IPS\Request::i()->currency, \IPS\nexus\Money::currencies() ) )
		{
			if ( isset( \IPS\Request::i()->csrfKey ) and \IPS\Request::i()->csrfKey === \IPS\Session\Front::i()->csrfKey )
			{
				$_SESSION['cart'] = array();
				$_SESSION['currency'] = \IPS\Request::i()->currency;
			}
			$currency = \IPS\Request::i()->currency;
		}
		else
		{
			$currency = \IPS\nexus\Customer::loggedIn()->defaultCurrency();
		}
		
		/* If we have a category, display it */
		if ( isset( \IPS\Request::i()->cat ) )
		{
			try
			{
				$category = \IPS\nexus\Package\Group::loadAndCheckPerms( \IPS\Request::i()->cat );
				
				if ( \IPS\Request::i()->view )
				{
					\IPS\Request::i()->setCookie( 'storeView', ( \IPS\Request::i()->view == 'list' ) ? 'list' : 'grid', \IPS\DateTime::ts( time() )->add( new \DateInterval( 'P1Y' ) ) );
					\IPS\Request::i()->cookie['storeView'] = ( \IPS\Request::i()->view == 'list' ) ? 'list' : 'grid';
				}

				$subcategories = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( '*', 'nexus_package_groups', array( 'pg_parent=?', $category->id ), 'pg_position ASC' ), 'IPS\nexus\Package\Group' );
				
				$joins = array();
				switch ( \IPS\Request::i()->sortby )
				{
					case 'name':
						$joins['core_sys_lang_words'] = array( "word_app='nexus' AND word_key=CONCAT( 'nexus_package_', p_id ) AND lang_id=?", \IPS\Member::loggedIn()->language()->id );
						$sortBy = 'word_custom';
						break;
						
					case 'price_low':
					case 'price_high':
						$joins['nexus_package_base_prices'] = array( 'id=p_id' );
						$sortBy = \IPS\Request::i()->sortby == 'price_low' ? $currency : ( $currency . ' DESC' );
						break;
						
					case 'rating':
						$sortBy = 'p_rating DESC';
						break;
						
					default:
						$sortBy = 'p_position';
						break;
				}
				
				$currentPage = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

				if( $currentPage < 1 )
				{
					$currentPage = 1;
				}

				$select = \IPS\Db::i()->select( '*', 'nexus_packages', array(
					array( 'p_group=?', $category->id ),
					array( 'p_store=1' ),
					array( "( p_member_groups='*' OR " . \IPS\Db::i()->findInSet( 'p_member_groups', \IPS\Member::loggedIn()->groups ) . ' )' )
				), $sortBy, array( ( $currentPage - 1 ) * static::$productsPerPage, static::$productsPerPage ), NULL, NULL, \IPS\Db::SELECT_SQL_CALC_FOUND_ROWS );
				foreach ( $joins as $table => $clause )
				{
					$select->join( $table, $clause );
				}
				
				$packages = new \IPS\Patterns\ActiveRecordIterator( $select, 'IPS\nexus\Package' );
				$totalCount = \IPS\Db::i()->select( 'count(*)', 'nexus_packages', array(
					array( 'p_group=?', $category->id ),
					array( 'p_store=1' ),
					array( "( p_member_groups='*' OR " . \IPS\Db::i()->findInSet( 'p_member_groups', \IPS\Member::loggedIn()->groups ) . ' )' )
				) )->first();
				
				$totalPages = ceil( $packages->count( TRUE ) / static::$productsPerPage );
				if ( $totalPages and $currentPage > $totalPages )
				{
					\IPS\Output::i()->redirect( $category->url()->setQueryString( 'page', $totalPages ), NULL, 303 );
				} 
				$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination( $category->url(), $totalPages, $currentPage, static::$productsPerPage );
				
				$packagesWithCustomFields = array();
				foreach ( iterator_to_array( \IPS\Db::i()->select( 'cf_packages', 'nexus_package_fields', 'cf_purchase=1' ) ) as $ids )
				{
					$packagesWithCustomFields = array_merge( $packagesWithCustomFields, array_filter( explode( ',', $ids ) ) );
				}
				
				\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate('store')->categorySidebar( $category, $subcategories );				
				\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->category( $category, $subcategories, $packages, $pagination, $packagesWithCustomFields, $totalCount );
				
				foreach ( $category->parents() as $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( NULL, $category->_title );
				\IPS\Output::i()->title = $category->_title;
				return;
			}
			catch ( \OutofRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '1X241/1', 404, '' );
			}
		}
		
		/* Otherwise, display the index */
		else
		{
			/* New Products */
			$newProducts = array();
			$nexus_store_new = explode( ',', \IPS\Settings::i()->nexus_store_new );
			if ( $nexus_store_new[0] )
			{
				$newProducts = \IPS\nexus\Package\Item::getItemsWithPermission( array( array( 'p_date_added>?', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $nexus_store_new[1] . 'D' ) )->getTimestamp() ) ), 'p_date_added DESC', $nexus_store_new[0] );
			}
			
			/* Popular Products */
			$popularProducts = array();
			$nexus_store_popular = explode( ',', \IPS\Settings::i()->nexus_store_popular );
			if ( $nexus_store_popular[0] )
			{
				$where = array();
				$where[] = array( 'ps_app=? AND ps_type=? AND ps_start>?', 'nexus', 'package', \IPS\DateTime::create()->sub( new \DateInterval( 'P' . $nexus_store_popular[1] . 'D' ) )->getTimestamp() );
				$where[] = "( p_member_groups='*' OR " . \IPS\Db::i()->findInSet( 'p_member_groups', \IPS\Member::loggedIn()->groups ) . ' )';
				$where[] = array( 'p_store=?', 1 );

				$popularIds = \IPS\Db::i()->select( 'nexus_purchases.ps_item_id', 'nexus_purchases', $where, 'COUNT(ps_item_id) DESC', $nexus_store_popular[0], 'ps_item_id' )->join( 'nexus_packages', 'ps_item_id=p_id' );
				if( count( $popularIds ) )
				{
					$popularProducts = new \IPS\Patterns\ActiveRecordIterator( \IPS\Db::i()->select( 'nexus_packages.*', 'nexus_packages', array( \IPS\Db::i()->in( 'p_id', iterator_to_array( $popularIds ) ) ), 'FIELD(p_id, ' . implode( ',', iterator_to_array($popularIds) ) . ')' ), 'IPS\nexus\Package' );
				}
			}
						
			/* Display */
			\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate('store')->categorySidebar( );
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->index( \IPS\nexus\Customer::loggedIn()->cm_credits[ $currency ], $newProducts, $popularProducts );
			\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('module__nexus_store');
		}
	}
	
	/**
	 * Registration Packages
	 *
	 * @return	void
	 */
	public function register()
	{
		\IPS\Output::i()->bodyClasses[] = 'ipsLayout_minimal';
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->register( \IPS\nexus\Package\Item::getItemsWithPermission( array( array( 'p_reg=1' ) ), 'p_date_added DESC' ) );
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('sign_up');
	}
	
	/**
	 * View Cart
	 *
	 * @return	void
	 */
	protected function cart()
	{
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('store')->cart();
	}
}