<?php
/**
 * @brief		Poll Votes Filter Iterator
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	
 * @since		21 Aug 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Poll;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Poll Votes Filter Iterator
 * @note	When we require PHP 5.4+ this can just be replaced with a CallbackFilterIterator
 */
class _Iterator extends \FilterIterator
{
	/**
	 * @brief	Question
	 */
	protected $question;
	
	/**
	 * @brief	Option
	 */
	protected $option;
	
	/**
	 * Constructor
	 *
	 * @param	\IPS\Patterns\ActiveRecordIterator	$iterator	Iterator
	 * @param	int|null							$question	Question
	 * @param	int|null							$option		Option
	 * @return	void
	 */
	public function __construct( \IPS\Patterns\ActiveRecordIterator $iterator, $question=NULL, $option=NULL )
	{
		$this->question	= $question;
		$this->option	= $option;
		return parent::__construct( $iterator );
	}
	
	/**
	 * Does this rule apply?
	 *
	 * @return	void
	 */
	public function accept()
	{	
		$row = $this->getInnerIterator()->current();
		
		if ( is_array( $row->member_choices[ $this->question ] ) )
		{
			return (bool) in_array( $this->option, $row->member_choices[ $this->question ] );
		}
		
		return $row->member_choices[ $this->question ] == $this->option;
	}
}