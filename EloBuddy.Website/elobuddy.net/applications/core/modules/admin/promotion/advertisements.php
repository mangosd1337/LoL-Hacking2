<?php
/**
 * @brief		Advertisements
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		24 Sep 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\promotion;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advertisements
 */
class _advertisements extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'advertisements_manage' );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Create the table */
		$table = new \IPS\Helpers\Table\Db( 'core_advertisements', \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements' ) );
		$table->langPrefix = 'ads_';

		/* Columns we need */
		$table->include = array( 'ad_title', 'ad_html', 'ad_impressions', 'ad_clicks', 'ad_start', 'ad_end', 'ad_maximum_value', 'ad_active' );
		$table->mainColumn = 'ad_html';
		$table->noSort	= array( 'ad_images', 'ad_html' );

		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'ad_start';
		$table->sortDirection = $table->sortDirection ?: 'desc';

		$table->quickSearch = 'ad_html';
		$table->advancedSearch = array(
			'ad_html'			=> \IPS\Helpers\Table\SEARCH_CONTAINS_TEXT,
			'ad_start'			=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'ad_end'			=> \IPS\Helpers\Table\SEARCH_DATE_RANGE,
			'ad_impressions'	=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			'ad_clicks'			=> \IPS\Helpers\Table\SEARCH_NUMERIC,
			);

		/* Filters */
		$table->filters = array(
			'ad_filters_active'				=> 'ad_active=1',
			'ad_filters_inactive'			=> 'ad_active=0',
		);

		/* If Nexus is installed, we get the pending filter too */
		if( \IPS\Application::appIsEnabled( 'nexus' ) )
		{
			$table->filters['ad_filters_pending']	= 'ad_active=-1';
		}
		
		/* Custom parsers */
		$table->parsers = array(
            'ad_title'			=> function( $val, $row )
            {
                return \IPS\Member::loggedIn()->language()->checkKeyExists( "core_advert_{$row['ad_id']}" ) ? \IPS\Member::loggedIn()->language()->addToStack( "core_advert_{$row['ad_id']}" ) : \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( 'ad_title_none', array( 'ipsType_light' ) );
            },
			'ad_html'			=> function( $val, $row )
			{
				if( $row['ad_html'] )
				{
					$preview	= \IPS\Theme::i()->getTemplate( 'promotion' )->advertisementIframePreview( $row['ad_id'] );
				}
				else
				{
					$advert = \IPS\core\Advertisement::constructFromData( $row );

					if( !count( $advert->_images ) )
					{
						return '';
					}
					
					$preview	= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->advertisementImage( $advert, \IPS\Http\Url::external( $advert->link ) );
				}

				return $preview;
			},
			'ad_active'			=> function( $val, $row )
			{
				return \IPS\Theme::i()->getTemplate( 'promotion' )->activeBadge( $row['ad_id'], ( $val == -1 ) ? 'ad_filters_pending' : ( ( $val == 0 ) ? 'ad_filters_inactive' : 'ad_filters_active' ), $val, $row );
			},
			'ad_clicks'			=> function( $val, $row )
			{
				return $row['ad_html'] ? \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( 'unavailable', array( 'ipsType_light' ) ) : $val;
			},
			'ad_start'			=> function( $val, $row )
			{
				return \IPS\DateTime::ts( $val )->localeDate();
			},
			'ad_end'			=> function( $val, $row )
			{
				return $val ? \IPS\DateTime::ts( $val )->localeDate() : \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( 'never', array( 'ipsType_light' ) );
			},
			'ad_maximum_value'	=> function( $val, $row )
			{
				if( $row['ad_maximum_value'] == '-1' )
				{
					return \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( 'unlimited', array( 'ipsType_light' ) );
				}

				if( $row['ad_maximum_unit'] == 'c' )
				{
					return \IPS\Member::loggedIn()->language()->addToStack( 'ad_maximum_value_c', FALSE, array( 'pluralize' => array( $row['ad_maximum_value'] ) ) );
				}
				else if( $row['ad_maximum_unit'] == 'i' )
				{
					return \IPS\Member::loggedIn()->language()->addToStack('ad_maximum_value_i', FALSE, array( 'pluralize' => array( $row['ad_maximum_value'] ) ) );
				}

				return $val;
			}
		);
		
		/* Specify the buttons */
		if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_add' ) )
		{
			$table->rootButtons = array(
				'add'	=> array(
					'icon'		=> 'plus',
					'title'		=> 'add',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements&do=form' ),
				)
			);
		}

		$table->rowButtons = function( $row )
		{
			$return = array();

			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_edit' ) )
			{
				if ( $row['ad_active'] == -1 )
				{
					$return['approve'] = array(
						'icon'		=> 'check',
						'title'		=> 'approve',
						'link'		=> \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements&do=toggle&status=1&id=' . $row['ad_id'] ),
						'hotkey'	=> 'a',
					);
				}
				
				$return['edit'] = array(
					'icon'		=> 'pencil',
					'title'		=> 'edit',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements&do=form&id=' . $row['ad_id'] ),
					'hotkey'	=> 'e',
				);
			}

			if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'promotion', 'advertisements_delete' ) )
			{
				$return['delete'] = array(
					'icon'		=> 'times-circle',
					'title'		=> 'delete',
					'link'		=> \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements&do=delete&id=' . $row['ad_id'] ),
					'data'		=> array( 'delete' => '' ),
				);
			}
			
			return $return;
		};
		
		/* Action Buttons */
		\IPS\Output::i()->sidebar['actions'] = array(
			'settings'	=> array(
				'title'		=> 'ad_settings',
				'icon'		=> 'cog',
				'link'		=> \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements&do=settings' ),
				'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => \IPS\Member::loggedIn()->language()->addToStack('ad_settings') )
			)
		);
		
		/* If Nexus is not installed, show message */
		if( !\IPS\Application::appIsEnabled( 'nexus' ) AND !\IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'promotion' )->advertisementMessage( );
		}

		/* Display */
		\IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'promotion/advertisements.css', 'core', 'admin' ) );
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('advertisements');
		\IPS\Output::i()->output	.= \IPS\Theme::i()->getTemplate( 'global' )->block( 'advertisements', (string) $table );
	}

	/**
	 * Advertisement settings
	 *
	 * @return	void
	 */
	protected function settings()
	{
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Select( 'ads_circulation', \IPS\Settings::i()->ads_circulation, TRUE, array( 'options' => array( 'random' => 'ad_circ_random', 'newest' => 'ad_circ_newest', 'oldest' => 'ad_circ_oldest', 'least' => 'ad_circ_leasti' ) ) ) );
		
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

			\IPS\Session::i()->log( 'acplog_ad_settings' );
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements' ), 'saved' );
		}
	
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('ad_settings');
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate('global')->block( 'ad_settings', $form, FALSE );
	}

	/**
	 * Delete an advertisement
	 *
	 * @return	void
	 */
	protected function delete()
	{
		/* Permission check */
		\IPS\Dispatcher::i()->checkAcpPermission( 'advertisements_delete' );

		/* Make sure the user confirmed the deletion */
		\IPS\Request::i()->confirmedDelete();
		
		/* Get our record */
		try
		{
			$record	= \IPS\core\Advertisement::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C157/2', 404, '' );
		}

		/* Delete the record */
		$record->delete();

        \IPS\Lang::deleteCustom( 'core', 'advert_' . $record->id );

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		/* Log and redirect */
		\IPS\Session::i()->log( 'acplog_ad_deleted', array() );
		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements' ), 'deleted' );
	}

	/**
	 * Toggle an advertisement state to active or inactive
	 *
	 * @note	This also takes care of approving a pending advertisement
	 * @return	void
	 */
	protected function toggle()
	{
		/* Get our record */
		try
		{
			$record	= \IPS\core\Advertisement::load( \IPS\Request::i()->id );
		}
		catch( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'node_error', '2C157/5', 404, '' );
		}

		/* Toggle the record */
		$record->active	= (int) \IPS\Request::i()->status;
		$record->save();
		
		/* Reset ads_exist setting */
		$adsExist = (bool) \IPS\Db::i()->select( 'COUNT(*)', 'core_advertisements', 'ad_active=1' )->first();
		if ( $adsExist != \IPS\Settings::i()->ads_exist )
		{
			\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $adsExist ), array( 'conf_key=?', 'ads_exist' ) );
			unset( \IPS\Data\Store::i()->settings );
		}

		/* Clear guest page caches */
		\IPS\Data\Cache::i()->clearAll();

		/* Log and redirect */
		if( $record->active == -1 )
		{
			\IPS\Session::i()->log( 'acplog_ad_approved', array() );
		}
		else if( $record->active == 1 )
		{
			\IPS\Session::i()->log( 'acplog_ad_enabled', array() );
		}
		else
		{
			\IPS\Session::i()->log( 'acplog_ad_disabled', array() );
		}

		\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements' )->setQueryString( 'filter', \IPS\Request::i()->filter ), \IPS\Request::i()->status ? 'ad_toggled_visible' : 'ad_toggled_notvisible' );
	}

	/**
	 * Add/edit an advertisement
	 *
	 * @return	void
	 */
	protected function form()
	{
		/* Are we editing? */
		if( isset( \IPS\Request::i()->id ) )
		{
			/* Permission check */
			\IPS\Dispatcher::i()->checkAcpPermission( 'advertisements_edit' );

			try
			{
				$record	= \IPS\core\Advertisement::load( \IPS\Request::i()->id );
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C157/1', 404, '' );
			}
		}
		else
		{
			/* Permission check */
			\IPS\Dispatcher::i()->checkAcpPermission( 'advertisements_add' );

			$record = new \IPS\core\Advertisement;
		}

		/* Start the form */
		$form	= new \IPS\Helpers\Form;
        $form->add( new \IPS\Helpers\Form\Translatable( 'ad_title', NULL, TRUE, array( 'app' => 'core', 'key' => ( !$record->id ) ? NULL : "core_advert_{$record->id}" ) ) );
        $form->add( new \IPS\Helpers\Form\Radio( 'ad_type', ( $record->id ) ? ( $record->html ? 'html' : 'image' ) : 'html', TRUE, array( 'options' => array( 'html' => 'ad_type_html', 'image' => 'ad_type_image' ), 'toggles' => array( 'html' => array( 'ad_html', 'ad_html_specify_https', 'ad_maximums_html' ), 'image' => array( 'ad_url', 'ad_image', 'ad_image_more', 'ad_clicks', 'ad_maximums_image' ) ) ), NULL, NULL, NULL, 'ad_type' ) );

		/* Show the fields for an HTML advertisement */
		$form->add( new \IPS\Helpers\Form\Codemirror( 'ad_html', ( $record->id ) ? $record->html : NULL, FALSE, array(), NULL, NULL, NULL, 'ad_html' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ad_html_specify_https', ( $record->id ) ? $record->html_https_set : FALSE, FALSE, array( 'togglesOn' => array( 'ad_html_https' ) ), NULL, NULL, NULL, 'ad_html_specify_https' ) );
		$form->add( new \IPS\Helpers\Form\Codemirror( 'ad_html_https', ( $record->id ) ? $record->html_https : NULL, FALSE, array(), NULL, NULL, NULL, 'ad_html_https' ) );

		/* Show the fields for an image advertisement */
		$form->add( new \IPS\Helpers\Form\Url( 'ad_url', ( $record->id ) ? $record->link : NULL, FALSE, array(), NULL, NULL, NULL, 'ad_url' ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'ad_image', ( $record->id ) ? ( ( isset( $record->_images['large'] ) and $record->_images['large'] ) ? \IPS\File::get( 'core_Advertisements', $record->_images['large'] ) : NULL ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Advertisements' ), NULL, NULL, NULL, 'ad_image' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'ad_image_more', ( $record->id ) ? ( ( ( isset( $record->_images['medium'] ) and $record->_images['medium'] ) OR ( ( isset( $record->_images['small'] ) and $record->_images['small']  ) ) ) ? TRUE : FALSE ) : FALSE, FALSE, array( 'togglesOn' => array( 'ad_image_small', 'ad_image_medium' ) ), NULL, NULL, NULL, 'ad_image_more' ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'ad_image_small', ( $record->id ) ? ( ( isset( $record->_images['small'] ) and $record->_images['small']  ) ? \IPS\File::get( 'core_Advertisements', $record->_images['small'] ) : NULL ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Advertisements' ), NULL, NULL, NULL, 'ad_image_small' ) );
		$form->add( new \IPS\Helpers\Form\Upload( 'ad_image_medium', ( $record->id ) ? ( ( isset( $record->_images['medium'] ) and $record->_images['medium']  ) ? \IPS\File::get( 'core_Advertisements', $record->_images['medium'] ) : NULL ) : NULL, FALSE, array( 'image' => TRUE, 'storageExtension' => 'core_Advertisements' ), NULL, NULL, NULL, 'ad_image_medium' ) );

		/* Add the location fields, remember to call extensions for additional locations.
			Array format: location => array of toggle fields to show */
		$defaultLocations	= array(
			'ad_global_header'	=> array(),
			'ad_global_footer'	=> array(),
			'ad_sidebar'		=> array(),
		);

		$currentValues	= ( $record->id ) ? json_decode( $record->additional_settings, TRUE ) : array();

		/* Now grab ad location extensions */
		foreach ( \IPS\Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
		{
			$result	= $extension->getSettings( $currentValues );

			$defaultLocations	= array_merge( $defaultLocations, $result['locations'] );

			if( isset( $result['settings'] ) )
			{
				foreach( $result['settings'] as $setting )
				{
					$form->add( $setting );
				}
			}
		}
		
		$defaultLocations['_ad_custom_'] = array('ad_location_custom');

		/* Add the locations to the form, and make sure the toggles get set properly */
		$locations = array();
		$customLocations = array();
		if ( $record->id )
		{
			$locations = explode( ',', $record->location );
			$customLocations = array_diff( $locations, array_keys( $defaultLocations ) );
			if ( !empty( $customLocations ) )
			{
				$locations[] = '_ad_custom_';
			}
			$locations = array_intersect( $locations, array_keys( $defaultLocations ) );
		}
		
		$form->add( new \IPS\Helpers\Form\CheckboxSet( 'ad_location',
			$locations,
			TRUE,
			array(
				'options'			=> array_combine( array_keys( $defaultLocations ), array_keys( $defaultLocations ) ),
				'toggles'			=> $defaultLocations,
			),
			NULL,
			NULL,
			NULL,
			'ad_location'
		) );
		
		$form->add( new \IPS\Helpers\Form\Stack( 'ad_location_custom', $customLocations, FALSE, array(), NULL, NULL, NULL, 'ad_location_custom' ) );

		/* Generic fields universally available for both types of ads */
		$form->add( new \IPS\Helpers\Form\Select( 'ad_exempt', ( $record->id ) ? ( ( $record->exempt == '*' ) ? '*' : json_decode( $record->exempt, TRUE ) ) : '*', FALSE, array( 'options' => \IPS\Member\Group::groups(), 'parse' => 'normal', 'multiple' => TRUE, 'unlimited' => '*', 'unlimitedLang' => 'everyone' ) ) );
		$form->add( new \IPS\Helpers\Form\Date( 'ad_start', ( $record->id ) ? \IPS\DateTime::ts( $record->start ) : new \IPS\DateTime, TRUE ) );
		$form->add( new \IPS\Helpers\Form\Date( 'ad_end', ( $record->id ) ? ( $record->end ? \IPS\DateTime::ts( $record->end ) : 0 ) : 0, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'indefinitely' ) ) );

		/* Number of clicks, number of impressions */
		if( $record->id )
		{
			$form->add( new \IPS\Helpers\Form\Number( 'ad_impressions', ( $record->id ) ? $record->impressions : 0, FALSE, array(), NULL, NULL, NULL, 'ad_impressions' ) );
			$form->add( new \IPS\Helpers\Form\Number( 'ad_clicks', ( $record->id ) ? $record->clicks : 0, FALSE, array(), NULL, NULL, NULL, 'ad_clicks' ) );
		}

		/* Click/impression maximum cutoffs, toggled depending upon HTML or image type ad */
		$form->add( new \IPS\Helpers\Form\Number( 'ad_maximums_html', ( $record->id ) ? $record->maximum_value : -1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, \IPS\Member::loggedIn()->language()->addToStack('ad_max_impressions'), 'ad_maximums_html' ) );
		$form->add( new \IPS\Helpers\Form\Custom( 'ad_maximums_image', array( 'value' => ( $record->id ) ? $record->maximum_value : -1, 'type' => ( $record->id ) ? $record->maximum_unit : 'i' ), FALSE, array(
			'getHtml'	=> function( $element )
			{
				return \IPS\Theme::i()->getTemplate( 'promotion', 'core' )->imageMaximums( $element->name, $element->value['value'], $element->value['type'] );
			},
			'formatValue' => function( $element )
			{
				if( !is_array( $element->value ) AND $element->value == -1 )
				{
					return array( 'value' => -1, 'type' => 'i' );
				}

				return array( 'value' => $element->value['value'], 'type' => $element->value['type'] );
			}
		), NULL, NULL, NULL, 'ad_maximums_image' ) );

		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$locations = $values['ad_location'];
			$customKey = array_search( '_ad_custom_', $locations );
			if ( $customKey !== FALSE )
			{
				unset( $locations[ $customKey ] );
				$locations = array_merge( $locations, $values['ad_location_custom'] );
			}
			
			/* Let us start with the easy stuff... */
			$record->location				= implode( ',', $locations );
			$record->html					= ( $values['ad_type'] == 'html' ) ? $values['ad_html'] : NULL;
			$record->link					= ( $values['ad_type'] == 'image' ) ? $values['ad_url'] : NULL;
			$record->impressions			= ( isset( $values['ad_impressions'] ) ) ? $values['ad_impressions'] : 0;
			$record->clicks					= ( $values['ad_type'] == 'image' AND isset( $values['ad_clicks'] ) ) ? $values['ad_clicks'] : 0;
			$record->active					= ( $record->id ) ? $record->active : 1;
			$record->html_https				= ( $values['ad_type'] == 'html' ) ? $values['ad_html_https'] : NULL;
			$record->start					= $values['ad_start'] ? $values['ad_start']->getTimestamp() : 0;
			$record->end					= $values['ad_end'] ? $values['ad_end']->getTimestamp() : 0;
			$record->exempt					= ( $values['ad_exempt'] == '*' ) ? '*' : json_encode( $values['ad_exempt'] );
			$record->images					= NULL;
			$record->maximum_value			= ( $values['ad_type'] == 'html' ) ? $values['ad_maximums_html'] : $values['ad_maximums_image']['value'];
			$record->maximum_unit			= ( $values['ad_type'] == 'html' ) ? 'i' : $values['ad_maximums_image']['type'];
			$record->additional_settings	= NULL;
			$record->html_https_set			= ( $values['ad_type'] == 'html' ) ? $values['ad_html_specify_https'] : 0;

			/* Figure out the ad_images */
			$images	= array();

			if( $values['ad_type'] == 'image' )
			{
				$images = array( 'large' => (string) $values['ad_image'] );

				if( isset( $values['ad_image_small'] ) AND $values['ad_image_small'] )
				{
					$images['small']	= (string) $values['ad_image_small'];
				}

				if( isset( $values['ad_image_medium'] ) AND $values['ad_image_medium'] )
				{
					$images['medium']	= (string) $values['ad_image_medium'];
				}
			}
			else
			{
				/* Did they upload images and then switch back to an html type ad, by chance? */
				if( isset( $values['ad_image'] ) AND $values['ad_image'] )
				{
					$values['ad_image']->delete();
				}

				if( isset( $values['ad_image_small'] ) AND $values['ad_image_small'] )
				{
					$values['ad_image_small']->delete();
				}

				if( isset( $values['ad_image_medium'] ) AND $values['ad_image_medium'] )
				{
					$values['ad_image_medium']->delete();
				}
			}

			/* If we are editing, and we changed from image -> html, clean up old images */
			if( $record->id AND count( $record->_images ) AND $values['ad_type'] == 'html' )
			{
				\IPS\File::get( 'core_Advertisements', $record->_images['large'] )->delete();

				if( isset( $record->_images['small'] ) )
				{
					\IPS\File::get( 'core_Advertisements', $record->_images['small'] )->delete();
				}

				if( isset( $record->_images['medium'] ) )
				{
					\IPS\File::get( 'core_Advertisements', $record->_images['medium'] )->delete();
				}
			}

			$record->images	= json_encode( $images );

			/* Any additional settings to save? */
			$additionalSettings	= array();

			foreach ( \IPS\Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
			{
				$settings	= $extension->parseSettings( $values );

				$additionalSettings	= array_merge( $additionalSettings, $settings );
			}

			$record->additional_settings	= json_encode( $additionalSettings );

			/* Insert or update */
			if( $record->id )
			{
				\IPS\Session::i()->log( 'acplog_ad_edited', array() );
			}
			else
			{
				\IPS\Session::i()->log( 'acplog_ad_added', array() );
			}
			$record->save();
			
			/* Save if any exist */
			$adsExist = (bool) \IPS\Db::i()->select( 'COUNT(*)', 'core_advertisements', 'ad_active=1' )->first();
			if ( $adsExist != \IPS\Settings::i()->ads_exist )
			{
				\IPS\Db::i()->update( 'core_sys_conf_settings', array( 'conf_value' => $adsExist ), array( 'conf_key=?', 'ads_exist' ) );
				unset( \IPS\Data\Store::i()->settings );
			}

			/* Clear guest page caches */
			\IPS\Data\Cache::i()->clearAll();

            /* Set the title */
            \IPS\Lang::saveCustom( 'core', 'core_advert_' . $record->id, $values[ 'ad_title' ] );

			/* Redirect */
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=promotion&controller=advertisements' ), 'saved' );
		}

		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack( ( !isset( \IPS\Request::i()->id ) ) ? 'add_advertisement' : 'edit_advertisement' );
		\IPS\Output::i()->output 	= \IPS\Theme::i()->getTemplate('global')->block( ( !isset( \IPS\Request::i()->id ) ) ? 'add_advertisement' : 'edit_advertisement', $form );
	}

	/**
	 * Show advertisement HTML code
	 *
	 * @return	void
	 */
	protected function getHtml()
	{
		/* Are we editing? */
		if( \IPS\Request::i()->id )
		{
			try
			{
				$record	= \IPS\core\Advertisement::load( \IPS\Request::i()->id );
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'node_error', '2C157/6', 404, '' );
			}
		}
		else
		{
			\IPS\Output::i()->error( 'node_error', '2C157/7', 404, '' );
		}

		if( $record->html )
		{
			if( \IPS\Request::i()->isSecure() AND $record->html_https_set )
			{
				$preview	= $record->html_https;
			}
			else
			{
				$preview	= $record->html;
			}
		}
		else
		{
			$advert = \IPS\core\Advertisement::constructFromData( $record );
			$preview	= \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->advertisementImage( $advert );
		}

		$preview	= preg_replace( "/<script(?:[^>]*?)?>.*<\/script>/ims", \IPS\Theme::i()->getTemplate( 'global' )->shortMessage( 'ad_script_disabled', array( 'ipsType_light' ) ), $preview );

		\IPS\Output::i()->sendOutput( \IPS\Theme::i()->getTemplate( 'global', 'core', 'admin' )->blankTemplate( $preview ) );
	}
	
	/**
	 * Adsense Help
	 *
	 * @return	void
	 */
	protected function adsense()
	{
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('google_adsense_header');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('promotion')->adsenseHelp();
	}
}