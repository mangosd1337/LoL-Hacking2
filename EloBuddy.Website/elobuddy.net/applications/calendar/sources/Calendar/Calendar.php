<?php
/**
 * @brief		Calendar Node
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Calendar
 * @since		18 Dec 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Calendar Node
 */
class _Calendar extends \IPS\Node\Model implements \IPS\Node\Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
		
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'calendar_calendars';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'cal_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'calendars';
			
	/**
	 * @brief	[Node] ACP Restrictions
	 * @code
	 	array(
	 		'app'		=> 'core',				// The application key which holds the restrictrions
	 		'module'	=> 'foo',				// The module key which holds the restrictions
	 		'map'		=> array(				// [Optional] The key for each restriction - can alternatively use "prefix"
	 			'add'			=> 'foo_add',
	 			'edit'			=> 'foo_edit',
	 			'permissions'	=> 'foo_perms',
	 			'delete'		=> 'foo_delete'
	 		),
	 		'all'		=> 'foo_manage',		// [Optional] The key to use for any restriction not provided in the map (only needed if not providing all 4)
	 		'prefix'	=> 'foo_',				// [Optional] Rather than specifying each  key in the map, you can specify a prefix, and it will automatically look for restrictions with the key "[prefix]_add/edit/permissions/delete"
	 * @endcode
	 */
	protected static $restrictions = array(
		'app'		=> 'calendar',
		'module'	=> 'calendars',
		'prefix' => 'calendars_'
	);
	
	/** 
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'calendar';
	
	/** 
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'calendar';
	
	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
		'view' 				=> 'view',
		'read'				=> 2,
		'add'				=> 3,
		'reply'				=> 4,
		'review'			=> 7,
		'askrsvp'			=> 5,
		'rsvp'				=> 6,
	);

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'calendar_calendar_';
	
	/** 
	 * @brief	[Node] Moderator Permission
	 */
	public static $modPerm = 'calendar_calendars';
	
	/**
	 * @brief	Content Item Class
	 */
	public static $contentItemClass = 'IPS\calendar\Event';

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'calperm_';
		
	/**
	 * [Node] Set whether or not this node is enabled
	 *
	 * @param	bool|int	$enabled	Whether to set it enabled or disabled
	 * @return	void
	 */
	protected function set__enabled( $enabled )
	{
	}

	/**
	 * Get SEO name
	 *
	 * @return	string
	 */
	public function get_title_seo()
	{
		if( !$this->_data['title_seo'] )
		{
			$this->title_seo	= \IPS\Http\Url::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'calendar_calendar_' . $this->id ) );
			$this->save();
		}

		return $this->_data['title_seo'] ?: \IPS\Http\Url::seoTitle( \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( 'calendar_calendar_' . $this->id ) );
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		$form->add( new \IPS\Helpers\Form\Translatable( 'cal_title', NULL, TRUE, array( 'app' => 'calendar', 'key' => ( $this->id ? "calendar_calendar_{$this->id}" : NULL ) ) ) );
		$form->add( new \IPS\Helpers\Form\Color( 'cal_color', $this->id ? $this->color : $this->_generateColor(), TRUE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cal_moderate', $this->id ? $this->moderate : FALSE, FALSE ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cal_allow_comments', $this->id ? $this->allow_comments : TRUE, FALSE, array( 'togglesOn' => array( 'cal_comment_moderate' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cal_comment_moderate', $this->id ? $this->comment_moderate : FALSE, FALSE, array(), NULL, NULL, NULL, 'cal_comment_moderate' ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cal_allow_reviews', $this->id ? $this->allow_reviews : FALSE, FALSE, array( 'togglesOn' => array( 'cal_review_moderate' ) ) ) );
		$form->add( new \IPS\Helpers\Form\YesNo( 'cal_review_moderate', $this->id ? $this->review_moderate : FALSE, FALSE, array(), NULL, NULL, NULL, 'cal_review_moderate' ) );
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( !$this->id )
		{
			$this->save();
		}

		if( isset( $values['cal_title'] ) )
		{
			\IPS\Lang::saveCustom( 'calendar', 'calendar_calendar_' . $this->id, $values['cal_title'] );
			$values['title_seo']	= \IPS\Http\Url::seoTitle( $values['cal_title'][ \IPS\Lang::defaultLanguage() ] );

			unset( $values['cal_title'] );
		}

		return $values;
	}

	/**
	 * @brief	Cached URL
	 */
	protected $_url	= NULL;
	
	/**
	 * @brief	URL Base
	 */
	public static $urlBase = 'app=calendar&module=calendar&controller=view&id=';
	
	/**
	 * @brief	URL Base
	 */
	public static $urlTemplate = 'calendar_calendar';
	
	/**
	 * @brief	SEO Title Column
	 */
	public static $seoTitleColumn = 'title_seo';

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return	void
	 */
	public function delete()
	{
		\IPS\Db::i()->delete( 'calendar_import_feeds', array( 'feed_calendar_id=?', $this->id ) );

		return parent::delete();
	}

	/**
	 * @brief Default colors
	 */
	protected static $colors	= array(
		'#6E4F99',		// Purple
		'#4F994F',		// Green
		'#4F7C99',		// Blue
		'#F3F781',		// Yellow
		'#DF013A',		// Red
		'#FFBF00',		// Orange
	);

	/**
	 * Grab the next available color
	 *
	 * @return	string
	 */
	public function _generateColor()
	{
		foreach( static::$colors as $color )
		{
			foreach( static::roots( NULL ) as $calendar )
			{
				if( mb_strtolower( $color ) == mb_strtolower( $calendar->color ) )
				{
					continue 2;
				}
			}

			return $color;
		}

		/* If we're still here, all of our pre-defined codes are used...generate something random */
		return '#' . str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT ) . 
			str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT ) . 
			str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT );
	}

	/**
	 * Add the appropriate CSS to the page output
	 *
	 * @return void
	 */
	public static function addCss()
	{
		$output	= '';

		foreach( static::roots() as $calendar )
		{
			$output	.= "a.cEvents_style{$calendar->id}, .cEvents_style{$calendar->id} a, .cCalendarIcon.cEvents_style{$calendar->id} {
	background-color: {$calendar->color};
}\n";
		}

		\IPS\Output::i()->headCss	= \IPS\Output::i()->headCss . $output;
	}
}