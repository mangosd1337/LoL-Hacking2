<?php
/**
 * @brief		CLI conversion process
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\modules\cli;

/**
 * CLI Conversion process
 */
class cli
{
	/**
	 * @brief	Conversion class
	 */
	protected static $convertClass = NULL;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		static::$convertClass = '\IPSUtf8\Convert';
		
		$convertClass = static::$convertClass;
		
		if ( ! is_dir( THIS_PATH . '/tmp' ) )
		{
			@mkdir( THIS_PATH . '/tmp' );
			@chmod( THIS_PATH . '/tmp', 0777 );
		}
		
		if ( ! is_writable( THIS_PATH . '/tmp' ) )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( "<warning>Please ensure that '" . THIS_PATH . '/tmp' . "' is writable.</warning>");
			exit();
		}

		if ( \IPSUtf8\Request::i()->isRestore() )
		{
			$this->restore();
		}
		
		if ( \IPSUtf8\Request::i()->isDeleteOriginals() )
		{
			$this->deleteOriginals();
		}
		
		if ( IPB_LOCK and ! \IPSUtf8\Session::i()->is_ipb )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( "<warning>Cannot locate the IP.Board database tables. Please check to ensure the SQL Prefix if set, is correct in 'conf_global.php'.</warning>");
			exit();
		}
		
		if ( \IPSUtf8\Request::i()->isDumpMethod() )
		{
			$this->dumpMethod();
		}
		
		if ( \IPSUtf8\Request::i()->isInfo() )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( implode( "\n", $convertClass::i()->getDebugString() ) );
			exit();
		}
		
		$welcome = 'Welcome to the IPS UTF8 Conversion utility (v' . \IPSUtf8\Convert::VERSION_ID . ')';
		$intro   = "This utility will convert all the tables in this database to UTF-8.\nThe converted data is inserted into new tables prefixed with x_utf_ and the original data kept.";
			
		\IPSUtf8\Output\Cli::i()->sendOutput( str_repeat( '-', \strlen( $welcome ) ) . "\n" . $welcome . "\n" . str_repeat( '-', \strlen( $welcome ) ) );
		
		if ( \IPSUtf8\Session::i()->has_archive )
		{
			if ( ! count( \IPSUtf8\Convert\Archive::i()->getNonUtf8Tables ) )
			{
				\IPSUtf8\Output\Cli::i()->sendOutput( "You have an archive table in a different database, and it is UTF8 and collations are correct." );
			}
			else
			{
				\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>You have an archive table in a different database, convert this now?\n[y] Enter 'y' to convert (recommended)\n[x] Enter 'n' to skip conversion.</choice>", 100 );
				
				if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
				{
					/* Convert */
					while( \IPSUtf8\Convert\Archive::i()->process( 250 ) === true )
					{
						$json = \IPSUtf8\Session::i()->json;
				
						\IPSUtf8\Output\Cli::i()->sendOutput( \IPSUtf8\Output\Cli::i()->progressBar( \IPSUtf8\Session::i()->current_table, \IPSUtf8\Convert\Archive::i()->getTotalRowsToConvert(), $json['convertedCount'] ), 101 );
					}
					
					\IPSUtf8\Convert\Archive::i()->renameTables();
					\IPSUtf8\Session::i()->reset();
					
					\IPSUtf8\Output\Cli::i()->sendOutput( "Archive table converted" );
				}
			}
		}
		
		if ( ! count( \IPSUtf8\Session::i()->tables ) )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput("No tables found for processing. Please check to ensure the correct database name and prefix are being used.");
			exit();
		}

		if ( empty( \IPSUtf8\Session::i()->status ) )
		{
			if ( $convertClass::i()->databaseIsUtf8() )
			{ 
				/* Check collations */
				if ( ! count( $convertClass::i()->getNonUtf8CollationTables() ) )
				{
					/* We're golden! */
					\IPSUtf8\Output\Cli::i()->sendOutput("The database is set to UTF-8, collations are correct and doesn't need converting.\n<choice>[x] Enter 'x' to exit the conversion (RECOMMENDED).\n[y] Enter 'y' to perform a full conversion anyway</choice>", 100 );
					
					if ( \IPSUtf8\Output\Cli::i()->response === 'x' )
					{
						exit();
					}
					else if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
					{
						$sessionData = \IPSUtf8\Session::i()->json;
						$sessionData['force_conversion'] = 1;
						
						\IPSUtf8\Session::i()->json = $sessionData;
						\IPSUtf8\Session::i()->save();
						
						/* Force a recount of tables */
						$convertClass::i()->getNonUtf8Tables( true );
					}
				}
				else
				{
					$this->collationFix();
				}
			}
			
			\IPSUtf8\Output\Cli::i()->sendOutput( "\n" . count( $convertClass::i()->getNonUtf8Tables() ) . " table(s) are not UTF-8 and need converting.\n");
			$origUtf8Charset	= \IPSUtf8\Db::i('utf8')->getCharset();
			$canMb4 = (bool) \IPSUtf8\Db::i('utf8')->set_charset( 'utf8mb4' );
			\IPSUtf8\Db::i('utf8')->set_charset( $origUtf8Charset );
			if ( version_compare( \IPSUtf8\Db::i()->server_info, '5.5.3', '>=' ) AND $canMb4 !== FALSE )
			{
				\IPSUtf8\Output\Cli::i()->sendOutput("Use 4-byte UTF-8 Encoding (utf8mb4)?\nSome non-common symbols (such as historical scripts, music symbols and Emoji) require more space in the database to be stored. If you choose 'no', users will not be able to use these symbols on your site. If 'yes', these characters will be able to be used, but the database will use more disk space.\n<choice>[y] Enter 'y' for Yes\n[n] Enter 'n' for No\n[x] Enter 'x' to exit</choice>", 100 );
				
				$sessionData = \IPSUtf8\Session::i()->json;
				
				if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
				{
					$sessionData['use_utf8mb4'] = 1;
				}
				else if ( \IPSUtf8\Output\Cli::i()->response === 'n' )
				{
					$sessionData['use_utf8mb4'] = 0;
				}
				else
				{
					exit;
				}
				
				\IPSUtf8\Session::i()->json = $sessionData;
				\IPSUtf8\Session::i()->save();
			}
			
			
			\IPSUtf8\Output\Cli::i()->sendOutput( $intro );			
			\IPSUtf8\Output\Cli::i()->sendOutput( "Press enter to continue", 100 );
		}
		else if ( \IPSUtf8\Session::i()->status === 'completed' )
		{
			$this->completed();
		}
		else
		{
			$date  = date('D j F Y', \IPSUtf8\Session::i()->updated );
			$count = count( array_keys( \IPSUtf8\Session::i()->completed_json ) );
			$pcent = round( ( \IPSUtf8\Session::i()->json['convertedCount'] / $convertClass::i()->getTotalRowsToConvert() * 100 ) );
			
			$more = "<warning>You have a conversion session unfinished. The session was last updated on " . $date . " and has processed " . $count . " tables (" . $pcent . "%) so far.</warning>";
			 
			\IPSUtf8\Output\Cli::i()->sendOutput( $intro );
			\IPSUtf8\Output\Cli::i()->sendOutput( $more );
			
			\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>[c] Enter 'c' to continue\n[x] Enter 'x' to reset and start the conversion again.</choice>", 100 );
			
			if ( \IPSUtf8\Output\Cli::i()->response === 'x' )
			{
				\IPSUtf8\Output\Cli::i()->sendOutput( "Are you sure? This will re-start the conversion\n<choice>[y] Enter 'y' to re-start the conversion\n[c] Enter 'c' to continue the conversion.</choice>", 100 );
				
				if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
				{
					\IPSUtf8\Session::i()->reset();
					return $this->execute();
				}
			}
		}
		
		$json = \IPSUtf8\Session::i()->json;
		\IPSUtf8\Output\Cli::i()->sendOutput( \IPSUtf8\Output\Cli::i()->progressBar( \IPSUtf8\Session::i()->current_table, $convertClass::i()->getTotalRowsToConvert(), $json['convertedCount'] ), 101 );
			
		/* Convert */
		while( $convertClass::i()->process( 250 ) === true )
		{
			$json = \IPSUtf8\Session::i()->json;
			
			\IPSUtf8\Output\Cli::i()->sendOutput( \IPSUtf8\Output\Cli::i()->progressBar( \IPSUtf8\Session::i()->current_table, $convertClass::i()->getTotalRowsToConvert(), $json['convertedCount'] ), 101 );
		}
		
		/* Check for completed */
		if ( \IPSUtf8\Session::i()->status === 'completed' )
		{
			$json = \IPSUtf8\Session::i()->json;
			
			\IPSUtf8\Output\Cli::i()->sendOutput( \IPSUtf8\Output\Cli::i()->progressBar( \IPSUtf8\Session::i()->current_table, $convertClass::i()->getTotalRowsToConvert(), $convertClass::i()->getTotalRowsToConvert() ), 101 );
			$this->completed();
		}
	}
	
	/**
	 * Fix collations
	 *
	 */
	public function collationFix()
	{
		$convertClass = static::$convertClass;
		
		\IPSUtf8\Output\Cli::i()->sendOutput("The database is set to UTF-8 and all tables are UTF-8 but " . count( $convertClass::i()->getNonUtf8CollationTables() ) . " table(s) have incorrect collations and need fixing.\n<choice>[f] Enter 'f' to fix table and field collations (RECOMMENDED)\n[y] Enter 'y' to perform a full conversion\n[x] Enter 'x' to exit the conversion</choice>", 100 );
				
		if ( \IPSUtf8\Output\Cli::i()->response === 'x' )
		{
			exit();
		}
		else if ( \IPSUtf8\Output\Cli::i()->response === 'f' )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput("Running now. This can take a while to complete...");
			
			$convertClass::i()->fixCollation();
			
			\IPSUtf8\Output\Cli::i()->sendOutput("Collation checked and fixed where appropriate");
			
			exit();
		}
	}
	
	/**
	 * Process is completed
	 */
	public function completed()
	{
		$convertClass	= static::$convertClass;
		$sessionData	= \IPSUtf8\Session::i()->json;
		$sql_charset	= ( $sessionData['use_utf8mb4'] ) ? 'utf8mb4' : 'utf8';
		$utf8mb4		= ( $sessionData['use_utf8mb4'] ) ? "\n\$INFO['sql_utf8mb4'] = true;" : '';
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "\nConversion has completed in " . \IPSUtf8\Session::i()->timeTaken( true ) );

		\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>[f] Enter 'f' to finish\n[x] Enter 'x' to reset and start the conversion again.</choice>", 100 );
			
		if ( \IPSUtf8\Output\Cli::i()->response === 'x' )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( "Are you sure? This will re-start the conversion\n<choice>[y] Enter 'y' to re-start the conversion\n[f] Enter 'f' to finish the conversion.</choice>", 100 );
			
			if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
			{
				\IPSUtf8\Session::i()->reset();
				
				return $this->execute();
			}
		}
		
		$convertClass::i()->finish();
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "You can now test the conversion by:\n\nEditing conf_global.php to add:\n \$INFO['sql_charset'] = '" . $sql_charset . "';{$utf8mb4}\nChange \$INFO['sql_tbl_prefix'] to: \$INFO['sql_tbl_prefix'] = '" . \IPSUtf8\Db::i('utf8')->prefix . "';\n" );
		\IPSUtf8\Output\Cli::i()->sendOutput( "If you are happy with the conversion, you can have it permanently rename the tables so that you can restore your original 'sql_table_prefix' and delete the original tables." );
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>[y] Enter 'y' to rename your tables\n[x] Enter 'x' to reset and start the conversion again.</choice>", 100 );
		
		if ( \IPSUtf8\Output\Cli::i()->response === 'x' )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( "Are you sure? This will re-start the conversion\n<choice>[y] Enter 'y' to re-start the conversion\n[f] Enter 'f' to finish the conversion.</choice>", 100 );
			
			if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
			{
				\IPSUtf8\Session::i()->reset();
				
				return $this->execute();
			}
		}
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "Renaming tables, this may take a short while." );
		
		$convertClass::i()->renameTables();
		
		if ( count( $convertClass::i()->getNonUtf8CollationTables() ) )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( "Checking and fixing collations." );
			$convertClass::i()->fixCollation();
		}
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "You can now complete the conversion by editing conf_global.php to add \$INFO['sql_charset'] = '" . $sql_charset . "'; and to change \$INFO['sql_tbl_prefix'] to:\n\$INFO['sql_tbl_prefix'] = '" . \IPSUtf8\Db::i()->prefix . "';" );
		
		exit();
	}
	
	/**
	 * Restore tables orig_ back
	 *
	 * @return void
	 */
	protected function restore()
	{
		$convertClass = static::$convertClass;
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>Restore the original *non-UTF8* tables?\n[y] Enter 'y' to restore\n[x] Enter 'n' to exit.</choice>", 100 );
		
		if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
		{
			$convertClass::i()->restoreOriginalTables();
			
			\IPSUtf8\Session::i()->reset();
			
			\IPSUtf8\Output\Cli::i()->sendOutput( "Tables restored" );
		}
		
		exit();
	}
	
	/**
	 * Delete tables orig_
	 *
	 * @return void
	 */
	protected function deleteOriginals()
	{
		$convertClass = static::$convertClass;
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>DELETE the original *non-UTF8* tables?\n[y] Enter 'y' to delete\n[x] Enter 'n' to exit.</choice>", 100 );
		
		if ( \IPSUtf8\Output\Cli::i()->response === 'y' )
		{
			$convertClass::i()->deleteOriginalTables();
			
			\IPSUtf8\Session::i()->reset();
			
			\IPSUtf8\Output\Cli::i()->sendOutput( "Tables deleted" );
		}
		
		exit();
	}
	
	/**
	 * Dump method. No laughing at the back
	 *
	 * @return void
	 */
	protected function dumpMethod()
	{
		$convertClass = static::$convertClass;
		
		\IPSUtf8\Session::i()->reset();
		
		/* Names are getting silly now */
		if ( ! $convertClass::canDump() )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput("Sorry, you can not use this as either shell_exec or iconv are disabled. Please re-run without the --dump flag");
			exit();
		}
		
		\IPSUtf8\Output\Cli::i()->sendOutput("<warning>This method will OVERWRITE your existing database so please only perform this on a copy of your database.</warning>");
		\IPSUtf8\Output\Cli::i()->sendOutput( "<choice>[y] Enter 'y' to continue\n[n] Enter 'n' to exit.</choice>", 100 );
			
		if ( \IPSUtf8\Output\Cli::i()->response !== 'y' )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput("If you want to convert without overwriting your database, please re-run without the --dump flag");
			exit();
		}
		
		/* Lets continue as we're good to go */
		$myDump = 'mysqldump';
		if ( isset( $GLOBALS['argv'][2] ) )
		{
			$myDumpPath = str_replace( array( '/mysqldump', 'mysqldump' ), '', $GLOBALS['argv'][2] );
		}
		else
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( "Please enter the path to mysqldump. If you do not know, or it does not have a path, just press enter\n<choice>Enter the path to mysqldump</choice>", 100 );
			
			$myDumpPath = trim( str_replace( array( '/mysqldump', 'mysqldump' ), '', \IPSUtf8\Output\Cli::i()->response ), "\n" );
		}
		
		$myDump = $myDumpPath ? rtrim( $myDumpPath, '/' ) . '/' . $myDump : $myDump;
		$mySql  = $myDumpPath ? rtrim( $myDumpPath, '/' ) . '/mysql' : 'mysql';
		
		$output = shell_exec( $myDump . ' --help' );
		
		if ( stristr( $output, 'not found' ) )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput( $myDump . " could not be found.");
			exit();
		}
		
		$toConvert = $convertClass::i()->getNonUtf8Tables();
		
		if ( ! count( $toConvert ) )
		{
			\IPSUtf8\Output\Cli::i()->sendOutput("Nothing to convert!");
			exit();
		}
		
		require( ROOT_PATH . '/conf_global.php' );
		
		$socket = '';
		$port   = '';
		if ( isset( $INFO['sql_socket'] ) and ! empty($INFO['sql_socket']) )
		{
			$socket = ' --socket=' . $INFO['sql_socket'];
		}
		
		if ( isset( $INFO['sql_port'] ) and ! empty($INFO['sql_port']) )
		{
			$port = ' --port=' . $INFO['sql_port'];
		}
		
		foreach( $toConvert as $table )
		{
			//$command = $myDump . $socket . " --max_allowed_packet=500M --quick --add-drop-table -u " . $INFO['sql_user'] . " --password='" . $INFO['sql_pass'] . "' " . $INFO['sql_database'] . " " . $table . " | sed -e 's/CHARSET\\=latin1/CHARSET\\=utf8\\ COLLATE\\=utf8_general_ci/g' | iconv -f latin1 -t UTF-8 | " . $mySql . " -u " . $INFO['sql_user'] . " --password='" . $INFO['sql_pass'] . "' " . $INFO['sql_database'];
			
			$tableCharSet = \IPSUtf8\Convert::i()->database_charset;
			$tableData    = \IPSUtf8\Session::i()->tables[ $table ];
			
			if ( ! empty( $tableData['charset'] ) )
			{
				$tableCharSet = $tableData['charset'];
			}
			
			$sed = " -e 's/CHARSET\\=" . $tableCharSet . "/CHARSET\\=utf8\\ COLLATE\\=utf8_unicode_ci/g' ";
			
			$command = $myDump . $socket . $port . " --max_allowed_packet=500M --quick --add-drop-table -h " . $INFO['sql_host'] . " -u " . $INFO['sql_user'] . " --password='" . $INFO['sql_pass'] . "' " . $INFO['sql_database'] . " " . \IPSUtf8\Db::i()->prefix . $table . " | sed " . $sed . " | iconv -f " . $tableCharSet . " -t UTF-8 > tmp/{$table}.sql";
			
			\IPSUtf8\Output\Cli::i()->sendOutput("Running\n" . $command . "\nPlease be patient, this may take some time");
			
			exec( $command, $output );
			
			$command = $mySql . $port . " -h " . $INFO['sql_host'] . " -u " . $INFO['sql_user'] . " --password='" . $INFO['sql_pass'] . "' " . $INFO['sql_database'] . "< tmp/{$table}.sql";
			
			\IPSUtf8\Output\Cli::i()->sendOutput("Running\n" . $command . "\nPlease be patient, this may take some time");
			
			exec( $command, $output );
			
			shell_exec( "rm tmp/{$table}.sql" );
			
			if ( stristr( implode( "\n", $output ), 'Error' ) )
			{
				\IPSUtf8\Output\Cli::i()->sendOutput( "Failed:\n" . $output );
				exit();
			}
			
			/* Update timestamp */
			\IPSUtf8\Session::i()->save();
		}
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "\nConversion has completed in " . \IPSUtf8\Session::i()->timeTaken( true ) );
				
		/* Still here... then fix collations and update DB charset */
		\IPSUtf8\Output\Cli::i()->sendOutput("Fixing table collations...");
		
		\IPSUtf8\Session::i()->updateTableData();
		$convertClass::i()->fixCollation();
		
		\IPSUtf8\Output\Cli::i()->sendOutput("Finishing...");
		$convertClass::i()->finish();
		
		\IPSUtf8\Output\Cli::i()->sendOutput( "You can now complete the conversion by editing conf_global.php to add \$INFO['sql_charset'] = 'UTF8';" );
		exit();
	}
	
}