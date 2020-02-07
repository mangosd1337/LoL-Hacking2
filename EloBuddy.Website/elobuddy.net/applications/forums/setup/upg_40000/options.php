<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Forums
 * @since		5 Jun 2014
 * @version		SVN_VERSION_NUMBER
 */

$options	= array(
	new \IPS\Helpers\Form\Radio( '40000_qa_forum', 0, TRUE, array( 'options' => array( 0 => '40000_qa_forum_0', 1 => '40000_qa_forum_1' ) ) )
);