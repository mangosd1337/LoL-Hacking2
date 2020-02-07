<?php
/**
 * @brief		IN_DEV Skin Set
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Email\Theme;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IN_DEV Skin set
 */
class _Theme extends \IPS\Theme
{
	/**
	 * @brief	Template Classes
	 */
	protected $templates;
	
	/**
	 * @brief	[SkinSets] Templates already loaded and evald via getTemplate()
	 */
	public static $calledTemplates = array();
	
	/**
	 * Get currently logged in member's theme
	 *
	 * @return	static
	 */
	public static function i()
	{
		return new self;
	}
	
	/**
	 * Write IN_DEV files
	 * 
	 * @param	boolean		$force		TRUE to rewrite templates, FALSE to check if exists first
	 * @return void
	 */
	public static function writeInDev( $force=FALSE )
	{
		$path = \IPS\ROOT_PATH . '/applications/content/dev/html';
		
		if ( ! \IPS\IN_DEV OR ! is_writeable( $path ) )
		{
			throw new \DomainException();
		}
		
		foreach( \IPS\Db::i()->select( '*', 'content_templates', array( 'template_master=?', 1 ) ) as $template )
		{
			if ( ! is_dir( $path . '/' . $template['template_location'] ) )
			{
				mkdir( $path . '/' . $template['template_location'] );
				@chmod( $path . '/' . $template['template_location'], \IPS\IPS_FOLDER_PERMISSION );
			}
			
			if ( ! is_dir( $path . '/' . $template['template_location'] . '/' . $template['template_group'] ) )
			{
				mkdir( $path . '/' . $template['template_location'] . '/' . $template['template_group'] );
				@chmod( $path . '/' . $template['template_location'] . '/' . $template['template_group'], \IPS\IPS_FOLDER_PERMISSION );
			}
			
			$container = NULL;
			
			if ( ! file_exists( $path . '/' . $template['template_location'] . '/' . $template['template_group'] . '/' . $template['template_key'] . '.phtml' ) OR $force === TRUE )
			{
				try
				{
					if ( $template['template_container'] )
					{
						$item = \IPS\content\Templates\Container::load( $template['template_container'] );
						
						$container = array(
							'name'      => $item->name,
							'type'      => $item->type,
							'parent_id' => $item->parent_id,
							'key'		=> $item->key
						);
					}
				}
				catch( \OutofRangeException $ex ) { }
				
				$data = json_encode( array(
					'title' 	=> $template['template_title'],
					'desc'  	=> $template['template_desc'],
					'rel_id'    => $template['template_rel_id'],
					'container' => $container	
				) );
				
				$write  = '<ips:template parameters="' . $template['template_params'] . '" data="' . $data . '" />' . "\n";
				$write .= $template['template_content'];
				
				if ( ! @\file_put_contents( $path . '/' . $template['template_location'] . '/' . $template['template_group'] . '/' . $template['template_key'] . '.phtml', $write ) )
				{
					throw new \RuntimeException( \IPS\Member::loggedIn()->language()->addToStack( 'content_theme_dev_cannot_write_template', FALSE, array( 'sprintf' => array( $path . '/' . $template['template_location'] . '/' . $template['template_group'] . '/' . $template['template_key'] ) ) ) );
				}
				else
				{
					@chmod( $path . '/' . $template['template_location'] . '/' . $template['template_group'] . '/' . $template['template_key'] . '.phtml', 0777 );
				}
			}	
		}
	}

	/**
	 * Get a template
	 *
	 * @param	string	$group				Template Group
	 * @param	string	$app				Application key (NULL for current application)
	 * @param	string	$location		    Template Location (NULL for current template location)
	 * @return	\IPS\Theme\Template
	 */
	public function getTemplate( $group, $app=NULL, $location=NULL )
	{
		/* Do we have an application? */
		if( $app === NULL )
		{
			$app = \IPS\Dispatcher::i()->application->directory;
		}
		
		/* How about a template location? */
		if( $location === NULL )
		{
			$location = \IPS\Dispatcher::i()->controllerLocation;
		}
	
		/* Get template */
		if ( \IPS\IN_DEV )
		{
			if ( ! isset( $this->templates[ $app ][ $location ][ $group ] ) )
			{
				$this->templates[ $app ][ $location ][ $group ] = new \IPS\Email\Theme\Template( $app, $location, $group );
			}
			
			return $this->templates[ $app ][ $location ][ $group ];
		}
		else
		{
			$key = \strtolower( 'template_email_' . static::makeBuiltTemplateLookupHash( $app, $location, $group ) . '_' . $group );

			/* Still here */
			if ( !in_array( $key, array_keys( self::$calledTemplates ) ) )
			{
				/* If we don't have a compiled template, do that now */
				if ( !isset( \IPS\Data\Store::i()->$key ) )
				{
					$functions = array( 'html' => array(), 'plain' => array() );
					$templatesSeen = array();

					/* Get it from the database */
					foreach( \IPS\Db::i()->select( '*', 'core_email_templates', array( "template_app=? AND template_name LIKE '" . \IPS\Db::i()->real_escape_string( $group ) . "__%'", $app ), 'template_parent DESC' ) as $data )
					{
						$_key = $data['template_app'] . '.' . $data['template_name'];

						if( in_array( $_key, $templatesSeen ) )
						{
							continue;
						}

						$templatesSeen[] = $_key;

						$funcName = mb_substr( $data['template_name'], ( mb_strlen( $group ) + 2 ) );
						$functions['html'][ $funcName ]  = static::compileTemplate( $data['template_content_html'], $funcName, $data['template_data'], true );
						$functions['plain'][ $funcName ] = static::compileTemplate( $data['template_content_plaintext'], $funcName, $data['template_data'], true );
					}
					
					/* Put them in a class */
					foreach( array( 'html', 'plain' ) as $type )
					{
						$var = 'template_' . $type;
						${$var} = <<<EOF
namespace IPS\Theme;
class class_email_{$app}_{$type}_{$group}
{
		
EOF;
						${$var} .= implode( "\n\n", $functions[ $type ] );
		
						${$var} .= <<<EOF
}
EOF;
						$storeKey = \strtolower( 'template_email_' . static::makeBuiltTemplateLookupHash( $app, $type, $group ) . '_' . $group );
						\IPS\Data\Store::i()->$storeKey = ${$var};
					}
				}
				
				/* Load compiled template */
				$compiledGroup = \IPS\Data\Store::i()->$key;
				
				try
				{
					if ( @eval( $compiledGroup ) === FALSE )
					{
						throw new \UnexpectedValueException;
					}
				}
				catch ( \ParseError $e )
				{
					throw new \UnexpectedValueException;
				}
				
				/* Hooks */
				$class = 'class_email_' . $app . '_' . $location . '_' . $group;
				$class = "\IPS\Theme\\{$class}";
								
				/* Init */
				self::$calledTemplates[ $key ] = new $class();
			}
		
			return self::$calledTemplates[ $key ];
		}
	}
	
	/**
	 * Returns the path for the IN_DEV .phtml files
	 * @param string 	 	  $app			Application Key
	 * @param string|null	  $location		Location
	 * @param string|null 	  $path			Path or Filename
	 * @return string
	 */
	protected static function _getHtmlPath( $app, $location=null, $path=null )
	{
		return rtrim( \IPS\ROOT_PATH . "/applications/{$app}/dev/html/{$location}/{$path}", '/' ) . '/';
	}
}