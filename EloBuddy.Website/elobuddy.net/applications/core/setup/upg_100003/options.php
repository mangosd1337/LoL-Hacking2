<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 Dec 2014
 * @version		SVN_VERSION_NUMBER
 */

/* Should friends be converted to followers ? */
$options	= array(
	new \IPS\Helpers\Form\Radio( '100003_follow_options', 'no_convert', TRUE, array( 'options' => array( 'no_convert' => '100003_no_convert', 'convert' => '100003_convert' ) ) )
);