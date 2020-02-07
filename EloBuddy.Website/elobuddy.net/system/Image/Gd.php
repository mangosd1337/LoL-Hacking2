<?php
/**
 * @brief		Image Class - GD
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		19 Feb 2013
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
 * Image Class - GD
 */
class _GD extends \IPS\Image
{	
	/**
	 * @brief	Image Resource
	 */
	public $image;

	/**
	 * Constructor
	 *
	 * @param	string	$contents	Contents
	 * @return	void
	 * @throws	\InvalidArgumentException
	 */
	public function __construct( $contents )
	{
		/* Create the resource */
		$this->image = @imagecreatefromstring( $contents );
		if ( $this->image === FALSE )
		{
			if ( $error = error_get_last() )
			{
				throw new \InvalidArgumentException( $error['message'], $error['type'] );
			}

			throw new \InvalidArgumentException;
		}

		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );
	    
	    /* Try to maintain any transparency */
    	imagealphablending( $this->image, true );
		imagesavealpha( $this->image, true );
	}
	
	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		if( is_resource( $this->image ) )
		{
			imagedestroy( $this->image );
		}
	}
	
	/**
	 * Get Contents
	 *
	 * @return	string
	 */
	public function __toString()
	{
		ob_start();
		switch( $this->type )
		{
			case 'gif':
				if ( $this->isAnimatedGif )
				{
					return (string) $this->contents;
				}
				imagegif( $this->image );
			break;
			
			case 'jpeg':
				$quality	= \IPS\Settings::i()->image_jpg_quality ?: 85;

				imagejpeg( $this->image, NULL, $quality );
			break;
			
			case 'png':
				$quality	= \IPS\Settings::i()->image_png_quality_gd ?: NULL;

				imagepng( $this->image, NULL, $quality );
			break;
		}
		return ob_get_clean();
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
		return $this->_manipulate( $width, $height, FALSE );
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
		return $this->_manipulate( $width, $height, TRUE );
	}

	/**
	 * Resize and/or crop image
	 *
	 * @param	int		$width			Width (in pixels)
	 * @param	int		$height			Height (in pixels)
	 * @param	bool	$crop			Crop image to provided dimensions
	 * @return	void
	 */
	public function _manipulate( $width, $height, $crop=FALSE )
	{
		if ( $this->isAnimatedGif )
		{
			return $this->image;
		}
			
		/* Create a new canvas */
		$width = ceil( $width );
		$height = ceil( $height );
		$newImage = imagecreatetruecolor( $width, $height );
		switch( $this->type )
		{			
			case 'gif':
				imagealphablending( $newImage, FALSE );
				$transindex = imagecolortransparent( $this->image );
				if( $transindex >= 0 ) 
				{
					$transcol	= @imagecolorsforindex( $this->image, $transindex );
					$transindex	= imagecolorallocatealpha( $newImage, $transcol['red'], $transcol['green'], $transcol['blue'], 127 );
					imagefill( $newImage, 0, 0, $transindex );
				}
			break;
			
			case 'jpeg':
				imagealphablending( $newImage, TRUE );
			break;
			
			case 'png':
				imagealphablending( $newImage, FALSE );
				imagesavealpha( $newImage, TRUE );
			break;
		}
		
		/* Crop the image? */
		if( $crop === TRUE )
		{
			/* First, downsize the image */
			$ratio	= ( $this->width / $this->height );

			if ( $width / $height > $ratio ) 
			{
				$nheight	= $width / $ratio;
				$nwidth		= $width;
			}
			else
			{
				$nwidth		= $height * $ratio;
				$nheight	= $height;
			}

			$this->resizeToMax( $nwidth, $nheight );

			/* Then we use imagecopy which will crop */
			imagecopy( $newImage, $this->image, 0, 0, 0, 0, $this->width, $this->height );
		}
		else
		{
			/* Copy the image resampled */
			imagecopyresampled( $newImage, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height );
		}
		
		/* Replace */
		imagedestroy( $this->image );
		$this->image = $newImage;
		
		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );
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
		/* Create a new canvas */
		$newImage = imagecreatetruecolor( ( $point2X - $point1X > 0 ) ? $point2X - $point1X : 0, ( $point2Y - $point1Y > 0 ) ? $point2Y - $point1Y : 0 );
		switch( $this->type )
		{			
			case 'gif':
				imagealphablending( $newImage, FALSE );
				$transindex = imagecolortransparent( $this->image );
				if( $transindex >= 0 ) 
				{
					$transcol	= @imagecolorsforindex( $this->image, $transindex );
					$transindex	= imagecolorallocatealpha( $newImage, $transcol['red'], $transcol['green'], $transcol['blue'], 127 );
					imagefill( $newImage, 0, 0, $transindex );
				}
			break;
			
			case 'jpeg':
				imagealphablending( $newImage, TRUE );
			break;
			
			case 'png':
				imagealphablending( $newImage, FALSE );
				imagesavealpha( $newImage, TRUE );
			break;
		}
		
		/* Then we use imagecopy which will crop */
		imagecopy( $newImage, $this->image, 0, 0, $point1X, $point1Y, $point2X - $point1X, $point2Y - $point1Y );
		
		/* Replace */
		imagedestroy( $this->image );
		$this->image = $newImage;
		
		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );
	}
	
	/**
	 * Impose image
	 *
	 * @param	\IPS\Image	$image	Image to impose
	 * @param	int			$x		Location to impose to, x axis
	 * @param	int			y		Location to impose to, y axis
	 * @return	void
	 */
	public function impose( $image, $x=0, $y=0 )
	{
		imagecopy( $this->image, $image->image, $x, $y, 0, 0, $image->width, $image->height );
	}

	/**
	 * Rotate image
	 *
	 * @param	int		$angle	Angle of rotation
	 * @return	void
	 */
	public function rotate( $angle )
	{
		$this->image	= imagerotate( $this->image, $angle, 0 );

		/* Set width/height */
		$this->width = imagesx( $this->image );
		$this->height = imagesy( $this->image );
		
		/* Try to maintain any transparency */
    	imagealphablending( $this->image, true );
		imagesavealpha( $this->image, true );
	}
	
	/**
	 * Get Image Orientation
	 *
	 * @return	int|NULL
	 */
	public function getImageOrientation()
	{
		if ( static::exifSupported() )
		{
			$exif = $this->parseExif();
			
			if ( isset( $exif['IFD0.Orientation'] ) )
			{
				return $exif['IFD0.Orientation'];
			}
		}
		
		return NULL;
	}
	
	/**
	 * Set Image Orientation
	 *
	 * @param	int		$orientation	The orientation
	 * @return	void
	 */
	public function setImageOrientation( $orientation )
	{
		/* Note, GD does not require orientation to be set after rotation */
	}
}