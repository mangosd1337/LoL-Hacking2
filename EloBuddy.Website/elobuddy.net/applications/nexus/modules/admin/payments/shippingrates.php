<?php
/**
 * @brief		Shipping Rates
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		13 Feb 2014
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
 * Shipping
 */
class _shippingrates extends \IPS\Node\Controller
{
	/**
	 * Node Class
	 */
	protected $nodeClass = 'IPS\nexus\Shipping\FlatRate';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'shipmethods_manage' );
		parent::execute();
	}
	
	/**
	 * Warning about unconsecutive rules
	 *
	 * @return	void
	 */
	protected function warning()
	{
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->decision( 'ship_rates_' . \IPS\Request::i()->type, array(
			'ship_rates_go_back'		=> \IPS\Http\Url::internal( 'app=nexus&module=payments&controller=shippingrates&do=form&id=' . \IPS\Request::i()->id ),
			'ship_rates_save_anyway'	=> \IPS\Http\Url::internal( $this->url ),
		) );
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
		$haveNoLowerLimit = FALSE;
		$haveNoUpperLimit = FALSE;
		$lastUpper = NULL;
		$consecutive = TRUE;

		foreach ( json_decode( $new->rates, TRUE ) as $rate )
		{
			if ( !$haveNoLowerLimit )
			{
				if ( $rate['min'] === '*' )
				{
					$haveNoLowerLimit = TRUE;
				}
				elseif ( is_array( $min ) )
				{
					$allAreZero = TRUE;
					foreach ( $min as $val )
					{
						if ( $val !== 0 )
						{
							$allAreZero = FALSE;
						}
					}
					
					if ( $allAreZero )
					{
						$haveNoLowerLimit = TRUE;
					}
				}
				elseif ( $rate['min'] === 0 )
				{
					$haveNoLowerLimit = TRUE;
				}
			}
						
			if ( !$haveNoUpperLimit and $rate['max'] === '*' )
			{
				$haveNoUpperLimit = TRUE;
			}
			
			if ( $lastUpper !== NULL )
			{
				if ( is_array( $lastUpper ) )
				{
					foreach ( $lastUpper as $k => $v )
					{
						if ( ( $rate['max'][ $k ] - $v ) > 0.01 )
						{
							$consecutive = FALSE;
						}
					}
				}
				elseif ( ( $rate['max'] - $lastUpper ) > 0.01 )
				{
					$consecutive = FALSE;
				}
			}
			$lastUpper = $rate['max'];
		}
				
		if( !$haveNoLowerLimit )
		{
			\IPS\Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'warning', 'type' => 'missing_lower', 'id' => $new->_id ) ) );
		}
		elseif( !$haveNoUpperLimit )
		{
			\IPS\Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'warning', 'type' => 'missing_upper', 'id' => $new->_id ) ) );
		}
		elseif ( !$consecutive )
		{
			\IPS\Output::i()->redirect( $this->url->setQueryString( array( 'do' => 'warning', 'type' => 'unconsecutive', 'id' => $new->_id ) ) );
		}
		else
		{
			parent::_afterSave( $old, $new );
		}
	}
}