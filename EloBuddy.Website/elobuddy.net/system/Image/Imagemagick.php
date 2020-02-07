<?php
/**
 * @brief		Image Class - ImageMagick
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		06 Mar 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Image;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Image Class - ImageMagick
 */
class _Imagemagick extends \IPS\Image
{
	/**
	 * @brief	Temporary filename
	 */
	protected $tempFile;
	
	/**
	 * @brief	Imagick object
	 */
	protected $imagick;
	
	/**
	 * Constructor
	 *
	 * @param	string	$contents	Contents
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	public function __construct( $contents )
	{
		$this->tempFile = tempnam( \IPS\TEMP_DIRECTORY, 'imagick' );
		\file_put_contents( $this->tempFile, $contents );
		
		try
		{
			$this->imagick = new \Imagick( $this->tempFile );
		}
		catch ( \ImagickException $e )
		{
			throw new \InvalidArgumentException( $e->getMessage(), $e->getCode() );
		}

		/* Set quality (if image format is JPEG) */
		if ( in_array( $this->imagick->getImageFormat(), array( 'jpg', 'jpeg' ) ) )
		{
			$this->imagick->setImageCompressionQuality( (int) \IPS\Settings::i()->image_jpg_quality ?: 85 );
		}

		/* Set width/height */
		$this->setDimensions();
	}
	
	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		unlink( $this->tempFile );
	}
	
	/**
	 * Get Contents
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return (string) $this->imagick->getImagesBlob();
	}
	
	/**
	 * Resize
	 *
	 * @param	int		$width			Width (in pixels)
	 * @param	int		$height			Height (in pixels)
	 * @return	void
	 */
	public function resize( $width, $height )
	{
		$format = $this->imagick->getImageFormat();

		if( mb_strtolower( $format ) == 'gif' )
		{
			$this->imagick	= $this->imagick->coalesceImages();

			foreach( $this->imagick as $frame )
			{
				$frame->thumbnailImage( $width, $height );
			}

			$this->imagick	= $this->imagick->deconstructImages();
		}
		else
		{
			$this->imagick->thumbnailImage( $width, $height );
		}

		/* Set width/height */
		$this->setDimensions();
	}

	/**
	 * Crop to a given width and height (will attempt to downsize first)
	 *
	 * @param	int		$width			Width (in pixels)
	 * @param	int		$height			Height (in pixels)
	 * @return	void
	 */
	public function crop( $width, $height )
	{
		$this->imagick->cropThumbnailImage( $width, $height );

		/* Set width/height */
		$this->setDimensions();
	}
	
	/**
	 * Crop at specific points
	 *
	 * @param	int		$point1X		x-point for top-left corner
	 * @param	int		$point1Y		y-point for top-left corner
	 * @param	int		$point2X		x-point for bottom-right corner
	 * @param	int		$point2Y		y-point for bottom-right corner
	 * @return	void
	 */
	public function cropToPoints( $point1X, $point1Y, $point2X, $point2Y )
	{
		$this->imagick->cropImage( $point2X - $point1X, $point2Y - $point1Y, $point1X, $point1Y );

		/* Set width/height */
		$this->setDimensions();
	}
	
	/**
	 * Impose image
	 *
	 * @param	\IPS\Image	$image	Image to impose
	 * @param	int			$x		Location to impose to, x axis
	 * @param	int			$y		Location to impose to, y axis
	 * @return	void
	 */
	public function impose( $image, $x=0, $y=0 )
	{
		$this->imagick->compositeImage( $image->imagick, \Imagick::COMPOSITE_DEFAULT, $x, $y );
	}

	/**
	 * Rotate image
	 *
	 * @param	int		$angle	Angle of rotation
	 * @return	void
	 */
	public function rotate( $angle )
	{
		$this->imagick->rotateImage( new \ImagickPixel('#00000000'), $angle );

		/* Set width/height */
		$this->setDimensions();
	}

	/**
	 * Set the image width and height
	 *
	 * @return	void
	 */
	protected function setDimensions()
	{
		/* Set width/height */
		$this->width = $this->imagick->getImageWidth();
		$this->height = $this->imagick->getImageHeight();
	}
	
	/**
	 * Get Image Orientation
	 *
	 * @return	int|NULL
	 */
	/**
	 * Get Image Orientation
	 *
	 * @return	int|NULL
	 */
	public function getImageOrientation()
	{
		try
		{
			/* This method does not exist in ImageMagick < 6.6.4 */
			return ( method_exists( $this->imagick, 'getImageOrientation' ) ) ? $this->imagick->getImageOrientation() : NULL;
		}
		catch( \ImagickException $e )
		{
			return NULL;
		}
	}

	/**
	 * Set image orientation
	 *
	 * @param	int		$orientation The orientation
	 * @return	void
	 */
	public function setImageOrientation( $orientation )
	{
		if( method_exists( $this->imagick, 'getImageOrientation' ) )
		{
			$this->imagick->setImageOrientation($orientation);
		}
	}
}