<?php
/**
 * @brief		Upgrader: Pre-upgrade check
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		27 Feb 2015
 * @version		SVN_VERSION_NUMBER
 */

/* Clear settings store in case we've already been here */
unset( \IPS\Data\Store::i()->settings );

/* Do this the easy way first... */
if( isset( \IPS\Settings::i()->gb_char_set ) AND ! empty( \IPS\Settings::i()->gb_char_set ) AND mb_strtolower( \IPS\Settings::i()->gb_char_set ) !== 'utf-8' )
{
	$output = \IPS\Theme::i()->getTemplate( 'global' )->convertUtf8();
	return;
}

/* Check for non-UTF8 database tables */
$convert    = FALSE;
$prefix = \IPS\Db::i()->prefix;
$tables	= \IPS\Db::i()->query( "SHOW TABLES LIKE '{$prefix}%';" );
while ( $table = $tables->fetch_assoc() )
{
	$tableName	= array_pop( $table );

	if( mb_strpos( $tableName, "orig_" ) === 0 )
	{
		continue;
	}
	
	if( mb_strpos( $tableName, "x_utf_" ) === 0 )
	{
		continue;
	}
	
	/* Ticket 909929 - SHOW TABLES LIKE 'ibf_%' can match tables like ibf3_table_name */
	if ( $prefix and mb_strpos( $tableName, $prefix ) === FALSE )
	{
		continue;
	}

    $definition = \IPS\Db::i()->getTableDefinition( preg_replace( '/^' . $prefix . '/', '', $tableName ), FALSE, TRUE );

    if( isset( $definition['collation'] ) AND !in_array( $definition['collation'], array( 'utf8_unicode_ci', 'utf8mb4_unicode_ci' ) ) )
	{
		$convert = TRUE;
		break;
	}

	$columns	= \IPS\Db::i()->query( "SHOW FULL COLUMNS FROM `{$tableName}`;" );

	while ( $column = $columns->fetch_assoc() )
	{
		if ( $column['Collation'] and !in_array( $column['Collation'], array( 'utf8_unicode_ci', 'utf8mb4_unicode_ci' ) ) )
		{
			$convert = TRUE;
			break 2;
		}
	}
}

if ( $convert === TRUE )
{
	$output = \IPS\Theme::i()->getTemplate( 'global' )->convertUtf8();
}