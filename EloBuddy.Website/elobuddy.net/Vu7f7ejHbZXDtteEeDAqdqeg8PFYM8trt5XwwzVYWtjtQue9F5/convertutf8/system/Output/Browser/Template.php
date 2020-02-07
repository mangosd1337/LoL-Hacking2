<?php
/**
 * @brief		Template Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		9 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Output\Browser;

/**
 * Template Class
 */
class Template
{
	public static function wrapper( $title="IPS Converter", $content="", $isError=false )
	{
		$iniTime        = @ini_get('max_execution_time');
		$execTime       = ( is_numeric( $iniTime ) ) ? $iniTime : 30;
		$url            = \IPSUtf8\Output\Browser::$url;
		$title          = ( empty( $title ) ) ? 'IPS Converter' : $title;
		$active         = array( 'convert' => '', 'tools' => '', 'archive' => '' );
		$hasArchive     = \IPSUtf8\Session::i()->has_archive;
		$controller     = empty( \IPSUtf8\Request::i()->controller ) ? 'browser' : \IPSUtf8\Request::i()->controller;
		$version        = \IPSUtf8\Convert::VERSION_ID;
        $copyrightDate  = date('Y');
			
		if ( $controller == 'browser' )
		{
			$active['convert'] = 'active';
		}
		else if ( $controller === 'archive' )
		{
			$active['archive'] = 'active';
		}
		else
		{
			$active['tools'] = 'active';
		}
		
		$html = <<<EOFHTML
<!DOCTYPE html>
<html>
  <head>
    <title>{$title}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="css/jumbotron.css" rel="stylesheet" media="screen">
  </head>
  <body data-url="{$url}">
    <div class="container">
      <div class="header">
EOFHTML;

if ( ! $isError )
{
	$html .= <<<EOFHTML
        <ul class="nav nav-pills pull-right">
          <li class="{$active['convert']}"><a href="{url="?controller=browser"}">Convert</a></li>
EOFHTML;
		if ( $hasArchive )
		{
			$html .= <<<EOFHTML
          <li class="{$active['archive']}"><a href="{url="?controller=archive"}">Archive</a></li>
EOFHTML;
        }
        
        $html .= <<<EOFHTML
          <li class="{$active['tools']}"><a href="{url="?controller=tools"}">Tools</a></li>
        </ul>
EOFHTML;
}
$html .= <<<EOFHTML
        <h3 class="text-muted">IPS UTF8 Converter</h3>
      </div>
      {$content}
      <div class="footer">
        <p>UTF8 Converter v{$version} &copy; {$copyrightDate} Invision Power Services, Inc.</p>
      </div>
    </div>
    <script type="text/javascript">
    	var ipsSettings = { maxExecTime: {$execTime}, 'controller': '{$controller}' };
    </script>
    <script src="js/jquery.min.js"></script>
    <script src="js/app.js?v=1"></script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
<div class="modal fade" id="confirmModal" data-url="" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Confirm</h4>
      </div>
      <div class="modal-body">
        <p id="confirmModalText">Please confirm this action</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" data-modal="submit">Confirm</button>
      </div>
    </div>
  </div>
</div>
EOFHTML;
	
		return static::parse( $html );
	}
	
	/**
	 * Tools page
	 */
	public static function tools( $isUtf8=false, $msg )
	{
		$html = <<<EOFHTML
	   <div class="jumbotron">
        <h1>Tools</h1>
EOFHTML;

if ( $msg !== null )
{
	$html .= <<<EOFHTML
	<p>
		<div class='alert alert-info'>{$msg}</div>
	</p>
	<p>&nbsp;</p>
EOFHTML;
}

if ( ! isset( \IPSUtf8\Request::i()->do ) )
{
	if ( $isUtf8 === true )
	{
		$html .= <<<EOFHTML
	<p class="text-info">The database is set to UTF-8.</p>
	 <p><a class="btn btn-lg btn-info" href="{url="?controller=tools&amp;do=collation"}">Check and fix UTF-8 collation</a></p>
EOFHTML;
	}
	else
	{
		$html .= <<<EOFHTML
	<p class="text-info">Please <a href="{url="?controller=browser"}">convert to UTF-8</a> first.</p>
EOFHTML;
	}
}

$html .= <<<EOFHTML
      </div>
EOFHTML;

$code = implode( "<br />", \IPSUtf8\Convert::i()->getDebugString() );

$html .= <<<EOFHTML
	   <div class="jumbotron">
        <h1>Info</h1>
        <code>
        {$code}
        </code>
       </div>
EOFHTML;

		return static::parse( $html );
	}
	
	/**
	 * Error page
	 */
	public static function error( $msg )
	{
		$html = <<<EOFHTML
	   <div class="jumbotron">
        <h1>Error</h1>
        <p class="lead">{$msg}</p>
       </div>
EOFHTML;

		return static::parse( $html );
	}
	
	/**
	 * Error page explaining about database being IPS4 already
	 */
	public static function errorAlreadyIPS4()
	{
		$html = <<<EOFHTML
	   <p style="text-align:left">
	   	The target database is already IPS4. There is no conversion required and the UTF8 converter is locked.
	   	<br>To override this and unlock the UTF8 converter, please create a file called "constants.php" and upload it to your /admin/convertutf8/ directory with the following contents:
	   </p>
	   <p><textarea class="form-control" style="height:90px;">&lt;?php
	   define( 'BYPASS_SAFETY_LOCK', true );
	   
	   </textarea>
	   </p>
	   <p class="text-danger">Please remember to remove the constants.php file when you are finished!</p>
	   
EOFHTML;

		return static::parse( $html );
	}
	
	/**
	 * Welcome page
	 */
	public static function welcome( $isUtf8=false, $status, $percent=false, $controller='browser' )
	{
		$convertClass		= ( $controller === 'browser' ) ? '\IPSUtf8\Convert' : '\IPSUtf8\Convert\Archive';
		$origUtf8Charset	= \IPSUtf8\Db::i('utf8')->getCharset();
		$canUseMb4			= ( version_compare( \IPSUtf8\Db::i()->server_info, '5.5.3', '>=' ) AND \IPSUtf8\Db::i('utf8')->set_charset('utf8mb4') !== FALSE );
		\IPSUtf8\Db::i('utf8')->set_charset( $origUtf8Charset );
		$badCollations		= $convertClass::i()->getNonUtf8CollationTables();
		$badTablesCnt		= count( $convertClass::i()->getNonUtf8Tables() );
		$path				= ROOT_PATH;
		$dbCharSetIsCorrect	= TRUE;
		$isUtf8mb4			= ( $isUtf8 === TRUE AND \IPSUtf8\Convert::i()->database_charset == 'utf8mb4' );
		
		if ( file_exists( ROOT_PATH . '/conf_global.php' ) )
		{
			require_once( ROOT_PATH . '/conf_global.php' );
			if ( !isset( $INFO['sql_charset'] ) OR ! in_array( $INFO['sql_charset'], array( 'utf8', 'utf8mb4' ) ) )
			{
				$dbCharSetIsCorrect = FALSE;
			}
		}
		
		if ( isset( \IPSUtf8\Request::i()->convert_anyway ) AND \IPSUtf8\Request::i()->convert_anyway == 1 )
		{
			$badTablesCnt = \IPSUtf8\Session::i()->json['tableCount'];
		}
		
		$html = <<<EOFHTML
	   <div class="jumbotron">
        <h1>Welcome</h1>
EOFHTML;

if ( $controller === 'archive' )
{
	$html .= <<<EOFHTML
	<p class="text-info">The converter has detected that you have an archive table in a different database.</p>
EOFHTML;
}

if ( $status === 'processing' )
{
$html .= <<<EOFHTML
	<p class="text-warning">Conversion in progress ({$percent}% complete).</p>
EOFHTML;
}
else if ( $isUtf8 !== true )
{
$html .= <<<EOFHTML
	<p class="lead">You have {$badTablesCnt} table(s) in this database that are not UTF-8.\nYou must convert these tables to UTF-8 before you can proceed with the upgrade.</p>
EOFHTML;
}
else if ( $isUtf8 === true )
{
	if ( count( $badCollations ) )
	{
		$cnt = count( $badCollations );
		$html .= <<<EOFHTML
	<p class="text-info">
		The database is set to UTF-8, however {$cnt} table(s) have incorrect collations and need fixing.
		<p><a class="btn btn-lg btn-info" href="{url="?controller=tools&amp;do=collation"}">Fix UTF-8 collations</a></p>
	</p>
EOFHTML;
	} 
	else
	{
		$html .= <<<EOFHTML
		
	<p class="text-info">
		The database tables are UTF-8, collations are correct and there is nothing to convert. You can <strong><a href="../upgrade">proceed with the upgrade</strong>.
	</p>
EOFHTML;
	}
}

if ( ! $isUtf8 and $canUseMb4 )
{	
	$buttonLabel = ( $isUtf8 ) ? "Convert anyway using 4-Byte UTF-8 (utf8mb4)" : "Start using 4-Byte UTF-8 (utf8mb4)";
	
	$html .= <<<EOFHTML
	<p>
		<a data-status="Start" class="btn btn-lg btn-info" href="{url="?controller={$controller}&amp;do=process&use_utf8mb4=1"}">{$buttonLabel}</a>
		<br />
		<div class='small'>
			<small>
			Some non-common symbols (such as historical scripts, music symbols and Emoji) require more space in the database to be stored. If you choose to convert using 4-Byte UTF-8, these characters will be able to be used, but the database will use more disk space.
			</small>
		</div>
	</p>
EOFHTML;
}

if ( ! $isUtf8 )
{
$label    = 'Start using UTF-8 (utf8)';
	$selector = 'btn-success';
	$more     = <<<EOFHTML
			<br />
			<div class='small'>
				<small>
				Choose this if you don't wish to store non-common symbols (such as historical scripts, music symbols and Emoji) and wish to use less disk space.
				</small>
			</div>
			<br>
			<div class='small'>
				<small>
					Please note that you should turn your community offline before starting any conversion on a live site.
				</small>
			</div>
			<br>
			<div class='small'>
				<small>
					If you have a large community, or are experiencing browser timeouts during conversion, you may wish to consider running the conversion via the Command Line.<br>
					<code>
					php {$path}/admin/convertutf8/cli.php
					</code>
				</small>
			</div>
EOFHTML;
}

if ( $status === 'processing' )
{
	$label    = 'Continue Conversion';
	$selector = 'btn-warning';
	$more     = '';
}
else if ( ! $isUtf8 )
{
	$label    = "Convert as UTF-8 (utf8)";
	$selector = 'btn-info';
}

if ( $label and $selector )
{
	$html .= <<<EOFHTML
        <p>
        	<a data-status="{$status}" class="btn btn-lg {$selector}" href="{url="?controller={$controller}&amp;do=process"}">{$label}</a>
        	{$more}
        </p>
EOFHTML;
}

if ( $status === 'processing' )
{
	$html .= <<<EOFHTML
        <p><small><a data-status="reset" data-confirm="true" class='text-danger' href="{url="?controller={$controller}&amp;do=reset"}">Reset conversion and restart</a></small></p>
EOFHTML;
}
$html .= <<<EOFHTML
      </div>
EOFHTML;

		return static::parse( $html );
	}
	
	/**
	 * Process page
	 */
	public static function process( $status, $percent, $msg )
	{
		$utfPrefix = \IPSUtf8\Db::i('utf8')->prefix . \IPSUtf8\Db::i()->prefix;
		
		$html = <<<EOFHTML
	   <div class="jumbotron" data-init="processInit">
        <h1>Converting</h1>
       	<p>&nbsp;</p>
       	<p>
       		<div class="progress progress-striped active">
	   			<div id="progressBar" data-start="{$percent}" class="progress-bar" role="progressbar" aria-valuenow="{$percent}" aria-valuemin="0" aria-valuemax="100" style="width: {$percent}%">
	   			<span class='sr-only'>{$msg}</span>
	   			</div>
	   		</div>
	   	</p>
        <p id="message">{$msg}</p>
      </div>
EOFHTML;

		return static::parse( $html );
	}
	
	/**
	 * Completed page
	 */
	public static function completed( $timeTaken )
	{
		$html = <<<EOFHTML
	   <div class="jumbotron">
        <h1>Almost there!</h1>
        <p class="lead">The database has now been converted!</p>
        <p><small>Conversion took {$timeTaken}</small></p>
		<p>&nbsp;</p>
        <p><a class="btn btn-lg btn-info" href="{url="?controller=browser&amp;do=finish"}">Click here to finish</a></p>
      </div>
EOFHTML;

		return static::parse( $html );
	}
	
	/**
	 * Finish page
	 */
	public static function finished( $updated )
	{
		$utfPrefix   	= \IPSUtf8\Db::i('utf8')->prefix;
		$normalPrefix	= \IPSUtf8\Db::i()->prefix;
		$sessionData	= \IPSUtf8\Session::i()->json;
		$sql_charset	= ( array_key_exists( 'utf8mb4', $sessionData['charSets'] ) ) ? 'utf8mb4' : 'utf8';

		/* Phil made me give him credit for some changes in this code */
		$html = <<<EOFHTML
	   <div class="jumbotron">
        <h1>Finished!</h1>
        <p class="lead">The database has now been converted and the tables correctly renamed!</p>
EOFHTML;

		if ( \IPSUtf8\Request::i()->controller === 'browser' )
		{
			if( !$updated )
			{
				$utf8mb4 = ( $sql_charset == 'utf8mb4' ) ? "<br>\$INFO['sql_utf8mb4'] = true;" : '';
$html .= <<<EOFHTML
        <p>You can now <strong>complete the conversion</strong> by editing conf_global.php to add <br><code>\$INFO['sql_charset'] = '{$sql_charset}';{$utf8mb4}</code>.<br>Afterwards, you can <strong><a href="../upgrade">proceed with the upgrade</strong>.</p>
      </div>
EOFHTML;
			}
			else
			{
$html .= <<<EOFHTML
        <p>You can now <strong><a href="../upgrade">proceed with the upgrade</strong>.</p>
      </div>
EOFHTML;
			}
		}
		else if ( \IPSUtf8\Request::i()->controller === 'archive' )
		{
$html .= <<<EOFHTML
        <p>You can now <strong>complete the conversion</strong> by converting the <a href="{url="?controller=browser"}">main database</a> if you've not already done so.</p>
      </div>
EOFHTML;
		}
		return static::parse( $html );
	}
	
	/**
	 * Basic HTML parsing
	 *
	 * @param	string	$html	Raw HTML
	 * @return	string
	 */
	protected static function parse( $html )
	{
		/* Parse {plugin="foo"} tags */
		$html = preg_replace_callback
		(
			'/\{([a-z]+?=([\'"]).+?\\2 ?+)}/',
			function( $matches )
			{
				/* Work out the plugin and the values to pass */
				preg_match_all( '/(.+?)='.$matches[2].'(.+?)'.$matches[2].'\s?/', $matches[1], $submatches );

				$plugin = array_shift( $submatches[1] );
				$value  = array_shift( $submatches[2] );
				$options = array();

				foreach ( $submatches[1] as $k => $v )
				{
					$options[ $v ] = $submatches[2][ $k ];
				}

				switch( $plugin )
				{
					case 'url':
						return \IPSUtf8\Output\Browser::$url . $value;
					break;
				}
			},
			$html
		);
		
		return $html;

	}

}