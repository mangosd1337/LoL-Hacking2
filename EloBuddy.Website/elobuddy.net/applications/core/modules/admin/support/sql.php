<?php
/**
 * @brief		sql
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * sql
 */
class _sql extends \IPS\Dispatcher\Controller
{	
	const SELECT_LIMIT = 100;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{	
		\IPS\Dispatcher::i()->checkAcpPermission( 'sql_toolbox' );	
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('sql_toolbox');
		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'global', 'core' )->message( 'sql_toolbox_warning', 'warning' );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=support&controller=support' ), \IPS\Member::loggedIn()->language()->addToStack('support') );
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=core&module=support&controller=sql' ), \IPS\Member::loggedIn()->language()->addToStack('sql_toolbox') );
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage()
	{		
		/* Get Tables */
		$tables = array();
		$result = \IPS\Db::i()->query("SHOW TABLE STATUS;");
		
		while ( $table = $result->fetch_assoc() )
		{
			$tables[] = array(
				'name'		=> $table['Name'],
				'rows'		=> $table['Rows'],
				'engine'	=> $table['Engine'],
			);
		}
		
		/* Create the table */
		$table = new \IPS\Helpers\Table\Custom( $tables, \IPS\Http\Url::internal( 'app=core&module=support&controller=sql' ) );
		$table->langPrefix = 'sql_table_';
		$table->sortBy = $table->sortBy ?: 'name';
		$table->sortDirection = $table->sortDirection ?: 'asc';
		$table->quickSearch = 'name';
		$table->limit = 200;
		
		/* Buttons */
		$table->rowButtons = function( $row )
		{
			return array(
				'view'	=> array(
					'icon'	=> 'search',
					'title'	=> 'view',
					'link'	=> \IPS\Http\Url::internal( 'app=core&module=support&controller=sql&do=query' )->setQueryString( 'q', "SELECT * FROM {$row['name']}" )
				),
			);
		};
		
		/* Query form */
		$form = new \IPS\Helpers\Form( 'form', 'run_query', \IPS\Http\Url::internal( 'app=core&module=support&controller=sql&do=query' ) );
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\TextArea( 'sql_toolbox_query', NULL, TRUE, array( 'class' => 'ipsField_codeInput' ) ) );
		
		/* Display */
		\IPS\Output::i()->output .= $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'support' ) ), 'queryFormTemplate' ) ) . \IPS\Theme::i()->getTemplate( 'global', 'core' )->block( 'title', (string) $table );
	}
	
	/**
	 * Run Query
	 *
	 * @return	void
	 */
	protected function query()
	{
		/* Select from table */
		$query = NULL;
		if ( isset( \IPS\Request::i()->q ) )
		{
			$query = urldecode( \IPS\Request::i()->q );
		}
		
		/* Query form */
		$form = new \IPS\Helpers\Form( 'form', 'run_query' );
		$form->class = 'ipsForm_vertical';
		$form->add( new \IPS\Helpers\Form\TextArea( 'sql_toolbox_query', $query, TRUE, array( 'class' => 'ipsField_codeInput' ) ) );
		if ( $values = $form->values() )
		{
			$query = $values['sql_toolbox_query'];
		}
		
		/* Run */
		$queries = array();
		$results = array();
		if ( $query )
		{
			/* Run each one */
			$queries = preg_split( "/;[\r\n|\n]+/", $query, -1, PREG_SPLIT_NO_EMPTY );
			foreach ( $queries as $k => $q )
			{
				try
				{
					/* Strip quotes */
					$q = trim( preg_replace( '@(--.*)|(#.*)|(((/\*)+?[\w\W]+?(\*/)+))@', '', $q ) );
					
					/* Check it's okay */
					if ( preg_match( "/^(DROP|FLUSH)/i", $q ) )
					{
						throw new \DomainException( 'sql_toolbox_not_allowed' );
					}
					if ( preg_match( "/^(?!SELECT)/i", preg_replace( "#\s{1,}#s", "", $q ) ) and ( preg_match( "/admin_login_logs/i", preg_replace( "#\s{1,}#s", "", $q ) ) || preg_match( "/admin_permission_rows/i", preg_replace( "#\s{1,}#s", "", $q ) ) ) )
					{
						throw new \DomainException( 'sql_toolbox_not_allowed' );
					}
										
					/* Run it */
					$result = \IPS\Db::i()->query( $q );
					
					/* Do we have results */
					if ( $result instanceof \mysqli_result )
					{
						if ( $result->num_rows )
						{
							/* Paginate */
							$pagination = NULL;
							if ( $result->num_rows > static::SELECT_LIMIT and !preg_match( "/^EXPLAIN/i", $q ) and !preg_match( "/^SHOW/i", $q ) and !preg_match( "/LIMIT[ 0-9,]+$/i", $q ) )
							{
								$page = isset( \IPS\Request::i()->page ) ? intval( \IPS\Request::i()->page ) : 1;

								if( $page < 1 )
								{
									$page = 1;
								}

								$pagination = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->pagination(
									\IPS\Http\Url::internal( 'app=core&module=support&controller=sql&do=query' )->setQueryString( 'q', $query ),
									ceil( $result->num_rows / static::SELECT_LIMIT ),
									$page,
									static::SELECT_LIMIT
								);
															
								if( mb_substr( $q, -1, 1 ) == ";" )
								{
									$q = mb_substr( $q, 0, -1 );
								}
								$q .= ' LIMIT ' . ( ( $page - 1 ) * static::SELECT_LIMIT ) . ', ' . static::SELECT_LIMIT;
								
								$result = \IPS\Db::i()->query( $q );
							}
							
							$results[ $k ] = \IPS\Theme::i()->getTemplate( 'support' )->table( $result, $pagination );
						}
						else
						{
							$results[ $k ] = \IPS\Theme::i()->getTemplate( 'global' )->message( 'no_results', 'info' );
						}
					}
					
					/* Nope, just a success message */
					else
					{
						$results[ $k ] = \IPS\Theme::i()->getTemplate( 'global' )->message( \IPS\Db::i()->info ?: \IPS\Member::loggedIn()->language()->addToStack('sql_toolbox_done'), 'success' );
					}
				}
				catch ( \Exception $e )
				{
					$results[ $k ] = \IPS\Theme::i()->getTemplate( 'global' )->message( $e->getMessage(), 'error' );
				}
			}
		}
		
		/* Display */
		\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate( 'support' )->toolboxResults( $form->customTemplate( array( call_user_func_array( array( \IPS\Theme::i(), 'getTemplate' ), array( 'support' ) ), 'queryFormTemplate' ) ), $queries, $results );
	}
}