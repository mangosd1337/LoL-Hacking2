<?php
/**
 * @brief		Singleton Pattern
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Patterns;

/**
 * Singleton Pattern
 */
class Singleton implements \Iterator
{
	/**
	 * Get instance
	 *
	 * @return	\IPS\Request
	 */
	public static function i()
	{
		if( static::$instance === NULL )
		{
			$classname = get_called_class();
			static::$instance = new $classname;
		}
		
		return static::$instance;
	}
	
	/**
	 * @brief	Data Store
	 */
	protected $data = array();

	/**
	 * Magic Method: Get
	 *
	 * @param	mixed	$key	Key
	 * @return	mixed	Value from the datastore
	 */
	public function __get( $key )
	{	
		if( !isset( $this->data[ $key ] ) )
		{
			return NULL;
		}
		
		return $this->data[ $key ];
	}
	
	/**
	 * Magic Method: Set
	 *
	 * @param	mixed	$key	Key
	 * @param	mixed	$value	Value
	 * @return	void
	 */
	public function __set( $key, $value )
	{
		$this->data[ $key ] = $value;
	}
	
	/**
	 * Magic Method: Isset
	 *
	 * @param	mixed	$key	Key
	 * @return	bool
	 */
	public function __isset( $key )
	{
		return isset( $this->data[ $key ] );
	}
	
	/**
	 * Iterator: Rewind
	 *
	 * @return	void
	 */
	function rewind()
	{
        reset( $this->data );
    }
    
    /**
     * Iterator: Current
     *
     * @return	mixed
     */
    function current()
    {
        return current( $this->data );
    }
    
    /**
     * Iterator: Key
     *
     * @return	mixed
     */
    function key()
    {
        return key( $this->data );
    }
    
    /**
     * Iterator: Next
     *
     * @return	void
     */
    function next()
    {
       next( $this->data );
    }

    /**
     * Iterator: Valid
     *
     * @return	bool
     */
    function valid()
    {
    	return key( $this->data ) !== null;
    }
}