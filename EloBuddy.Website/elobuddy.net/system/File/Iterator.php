<?php
/**
 * @brief		File IteratorIterator
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Oct 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\File;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File IteratorIterator
 */
class _Iterator extends \IteratorIterator implements \Countable
{
	/**
	 * @brief	Stroage Extension
	 */
	protected $storageExtension;
	
	/**
	 * @brief	URL Field
	 */
	protected $urlField;
	
	/**
	 * @brief	URLs Only
	 */
	protected $fileUrlsOnly;
	
	/**
	 * @brief	Used to restore 'real' names when filenames cleaned (eg adh1029_file.php back to fi^le.php)
	 */
	protected $replaceNameField;
	
	/**
	 * Constructor
	 *
	 * @param	Traversable $iterator			The iterator
	 * @param	string		$storageExtension	The storage extension
	 * @param	string|NULL	$urlField			If passed a string, will look for an element with that key in the data returned from the iterator
	 * @param	string|NULL	$replaceNameField	If passed a string, it will replace the originalFilename with the data in the array. Used to restore 'real' names when filenames cleaned (eg adh1029_file.php back to fi^le.php)
	 * @return	void
	 */
	public function __construct( \Traversable $iterator, $storageExtension, $urlField=NULL, $fileUrlsOnly=FALSE, $replaceNameField=NULL )
	{
		$this->storageExtension = $storageExtension;
		$this->urlField = $urlField;
		$this->fileUrlsOnly = $fileUrlsOnly;
		$this->replaceNameField = $replaceNameField;
		return parent::__construct( $iterator );
	}
	
	/**
	 * Get current
	 *
	 * @return	\IPS\File
	 */
	public function current()
	{
		try
		{
			$data = $this->data();
			$urlField = NULL;
			
			if ( $this->urlField )
			{
				if ( !is_string( $this->urlField ) and is_callable( $this->urlField ) )
				{
					$urlField = call_user_func( $this->urlField, $data );
				}
				else
				{
					$urlField = $this->urlField;
				}
			}

			$obj = \IPS\File::get( $this->storageExtension, $urlField ? $data[ $urlField ] : $data );
			
			if ( $this->replaceNameField and ! empty( $data[ $this->replaceNameField ] ) )
			{
				$obj->originalFilename = $data[ $this->replaceNameField ];
			}
			
			return ( $this->fileUrlsOnly ) ? (string) $obj->url : $obj;
		}
		catch ( \Exception $e )
		{
			$this->next();
			return $this->current();
		}
	}
	
	/**
	 * Get data
	 *
	 * @return	mixed
	 */
	public function data()
	{
		return parent::current();
	}
	
	/**
	 * Get count
	 *
	 * @return	int
	 */
	public function count()
	{
		return $this->getInnerIterator()->count();
	}
}