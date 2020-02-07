<?php
/**
 * @brief		Width/Height input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		28 Mar 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Width/Height input class for Form Builder
 */
class _WidthHeight extends FormAbstract
{	
	/**
	 * @brief	Default Options
	 * @code
	 	$defaultOptions = array(
	 		'unlimited'			=> array( 0, 0 ),	// If any value other than NULL is provided, an "Unlimited" checkbox will be displayed. If checked, the values specified will be sent.
	 		'image'				=> NULL,			// If an \IPS\File object is provided, the image will be shown for resizing rather than a div
	 		'resizableDiv'		=> FALSE,			// If set to false, the resizable div will not be displayed (useful if you expect dimensions to be large)
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'unlimited'			=> NULL,
		'image'				=> NULL,
		'resizableDiv'		=> TRUE,
	);
	
	/**
	 * Constructor
	 * Sets an appropriate default value
	 *
	 * @return	void
	 */
	public function __construct()
	{
		call_user_func_array( 'parent::__construct', func_get_args() );
		
		if ( $this->value === NULL )
		{
			if ( $this->options['image'] !== NULL )
			{
				$image = \IPS\Image::create( $this->options['image']->contents() );
				$this->value = array( $image->width, $image->height );
			}
			else
			{
				$this->value = array( 100, 100 );
			}
		}
	}
	
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		return \IPS\Theme::i()->getTemplate( 'forms', 'core' )->widthheight( $this->name, $this->value[0], $this->value[1], $this->options['unlimited'], $this->options['image'] ? $this->options['image'] : NULL, $this->options['resizableDiv'] );
	}
	
	/**
	 * Get Value
	 *
	 * @return	mixed
	 */
	public function getValue()
	{
		$name = $this->name;
		$value = \IPS\Request::i()->$name;
		if ( $this->options['unlimited'] !== NULL and isset( $value['unlimited'] ) )
		{
			return $this->options['unlimited'];
		}
		return \IPS\Request::i()->$name;
	}
}