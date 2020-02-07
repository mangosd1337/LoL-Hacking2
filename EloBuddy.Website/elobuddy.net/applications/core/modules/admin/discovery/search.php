<?php
/**
 * @brief		Search settings
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		14 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\discovery;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search settings
 */
class _search extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'search_manage' );
		parent::execute();
	}

	/**
	 * Manage Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		/* Rebuild button */
		\IPS\Output::i()->sidebar['actions'] = array(
			'rebuildIndex'	=> array(
				'title'		=> 'search_rebuild_index',
				'icon'		=> 'undo',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=discovery&controller=search&do=queueIndexRebuild' ),
				'data'		=> array( 'confirm' => '', 'confirmSubMessage' => \IPS\Member::loggedIn()->language()->get('search_rebuild_index_confirm') ),
			),
		);
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Number( 'search_index_timeframe', \IPS\Settings::i()->search_index_timeframe, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'all' ), NULL, \IPS\Member::loggedIn()->language()->addToStack('search_index_timeframe_prefix'), \IPS\Member::loggedIn()->language()->addToStack('search_index_timeframe_suffix'), 'search_index_timeframe' ) );
		
		if ( $values = $form->values() )
		{
			$indexPrune = \IPS\Settings::i()->search_index_timeframe;
			
			/* Go ahead and save... */
			$form->saveAsSettings();
			\IPS\Session::i()->log( 'acplogs__search_settings' );
			
			/* And re-index if setting updated */
			if( $indexPrune != $values['search_index_timeframe'] )
			{				
				\IPS\Content\Search\Index::i()->rebuild();
			}
		}
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('menu__core_discovery_search');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'menu__core_discovery_search', $form );
	}
	
	/**
	 * Queue an index rebuild
	 *
	 * @return	void
	 */
	protected function queueIndexRebuild()
	{
		/* Clear MySQL minimum word length cached value */
		unset( \IPS\Data\Store::i()->mysqlMinWord );
		unset( \IPS\Data\Store::i()->mysqlMaxWord );

		\IPS\Content\Search\Index::i()->rebuild();
	
		\IPS\Session::i()->log( 'acplogs__queued_search_index' );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=discovery&controller=search' ), 'search_index_rebuilding' );
	}
}