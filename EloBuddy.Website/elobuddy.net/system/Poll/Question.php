<?php
/**
 * @brief		Poll Question
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 Dec 2015
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
 * Poll Question
 */
class _Question
{
	/**
	 * @brief	Data
	 */
	protected $data;
	
	/**
	 * Constructor
	 *
	 * @param	array	$data	Data
	 * @return	void
	 */
	public function __construct( $data )
	{
		$this->data = $data;
	}
	
	/**
	 * Get output for API
	 *
	 * @return	array
	 * @apiresponse	string	question	The question
	 * @apiresponse	object	options		Each of the options and how many votes they have had
	 */
	public function apiOutput()
	{
		return array(
			'question'	=> $this->data['question'],
			'options'	=> array_combine( $this->data['choice'], $this->data['votes'] )
		);
	}
}