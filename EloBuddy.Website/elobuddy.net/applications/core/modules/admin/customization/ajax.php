<?php
/**
 * @brief		Customization AJAX actions
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		07 May 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Members AJAX actions
 */
class _ajax extends \IPS\Dispatcher\Controller
{
	/**
	 * Return a CSS or HTML menu
	 *
	 * @return	html
	 */
	public function loadMenu()
	{
		$id	        = \IPS\Request::i()->id;
		$t_type 	= \IPS\Request::i()->t_type     ?: 'templates';
		$t_app      = \IPS\Request::i()->t_app		?: 'core';
		$t_location = \IPS\Request::i()->t_location ?: ( ( \IPS\Request::i()->t_app ) ? false : 'front' );
		
		if ( $t_type == 'templates' )
		{
			$t_group    = \IPS\Request::i()->t_group ?: ( ( \IPS\Request::i()->t_app ) ? false : 'global' );
			$t_template = \IPS\Request::i()->t_name  ?: ( ( \IPS\Request::i()->t_app ) ? false : 'globalTemplate' );
		}
		else
		{
			$t_group    = \IPS\Request::i()->t_group ?: ( ( \IPS\Request::i()->t_app ) ? false : 'custom' );
			$t_template = \IPS\Request::i()->t_name  ?: ( ( \IPS\Request::i()->t_app ) ? false : 'custom.css' );
		}
		
		$theme = \IPS\Theme::load( $id );

		if ( $t_type == 'templates' )
		{
			$templateNames = $theme->getRawTemplates( '', '', '', \IPS\Theme::RETURN_ALL_NO_CONTENT );
		}
		else
		{
			$templateNames = $theme->getRawCss( '', '', '', \IPS\Theme::RETURN_ALL_NO_CONTENT );
		}
		
		if ( $t_type === 'templates' )
		{
			/* Remove Admin Templates */
			foreach( $templateNames as $app => $items )
			{
				foreach( $templateNames[ $app ] as $location => $items )
				{
					if ( $location == 'admin' )
					{
						unset( $templateNames[ $app ][ $location ] );
					}
				}
			}
		}
		else
		{
			if ( $theme->by_skin_gen )
			{
				/* Remove all but custom/custom.css */
				foreach( $templateNames as $app => $items )
				{
					foreach( $templateNames[ $app ] as $location => $items )
					{
						if ( $location === 'front' )
						{
							foreach( $templateNames[ $app ][ $location ] as $path => $items )
							{
								if ( $path != 'custom' )
								{
									unset( $templateNames[ $app ][ $location ][ $path ] );
								}
							}
						}
						else
						{
							unset( $templateNames[ $app ][ $location ] );
						}
					}
				}
			}
			else
			{
				/* Remove Admin Templates */
				foreach( $templateNames as $app => $items )
				{
					foreach( $templateNames[ $app ] as $location => $items )
					{
						if ( $location == 'admin' )
						{
							unset( $templateNames[ $app ][ $location ] );
						}
					}
				}
			}
		}

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'customization' )->templateEditorMenu( $theme, $templateNames, array( 'app' => $t_app, 'location' => $t_location, 'group' => $t_group, 'template' => $t_template, 'type' => $t_type) );
	}
	
	/**
	 * Return a CSS or HTML template as JSON
	 *
	 * @return	json
	 */
	public function loadTemplate()
	{
		$t_type 	= \IPS\Request::i()->t_type;
		$t_app      = \IPS\Request::i()->t_app;
		$t_location = \IPS\Request::i()->t_location;
		$t_group	= ( \IPS\Request::i()->t_group ) ?: '.';
		$t_name     = \IPS\Request::i()->t_name;
		$id	        = \IPS\Request::i()->id;
		
		$skinSet       = \IPS\Theme::load( $id );
	
		if ( $t_type == 'templates' )
		{
			$templateBits  = $skinSet->getRawTemplates( $t_app, $t_location, $t_group, \IPS\Theme::RETURN_ALL );
			$templateBit   = ( ! empty( $t_name ) ) ? $templateBits[ $t_app ][ $t_location ][ $t_group ][ $t_name ] : null;
			$templateBit['template_content'] = htmlentities( $templateBit['template_content'], \IPS\HTMLENTITIES, 'UTF-8', TRUE );
		}
		else
		{
			$templateNames = $skinSet->getRawCss( '', '', '', \IPS\Theme::RETURN_ALL_NO_CONTENT );
			$templateBits  = $skinSet->getRawCss( $t_app, $t_location, $t_group, \IPS\Theme::RETURN_ALL );
			$templateBit   = ( ! empty( $t_name ) ) ? $templateBits[ $t_app ][ $t_location ][ $t_group ][ $t_name ] : null;
			$templateBit['css_content'] = htmlentities( $templateBit['css_content'], \IPS\HTMLENTITIES, 'UTF-8', TRUE );
		}
		
		if ( $templateBit !== null )
		{
			\IPS\Output::i()->json( $templateBit );
		}
		else
		{
			\IPS\Output::i()->json( array( 'error' => true ) );
		}
	}
	
	/**
	 * [AJAX] Search templates
	 *
	 * @return	void
	 */
	public function searchtemplates()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'theme_templates_manage' );
		
		$theme = \IPS\Theme::load( \IPS\Request::i()->id );
		$parents = array( $theme->_id );
		
		$where = array();
		if ( \IPS\Request::i()->term )
		{
			$where[] = array( 'LOWER(template_content) LIKE ?', '%' . mb_strtolower( \IPS\Request::i()->term ) . '%' );
		}
		if ( in_array( 'inherited', explode( ',', \IPS\Request::i()->filters ) ) )
		{
			try
			{
				foreach( $theme->parents() as $parent )
				{
					$parents[] = $parent->_id;
				}
			}
			catch( \OutOfRangeException $e ) { }
		}
		array_push( $parents, 0 );
		if ( !in_array( 'unique', explode( ',', \IPS\Request::i()->filters ) ) )
		{
			$where[] = array( 'template_user_added=0' );
		}
		if ( !in_array( 'unmodified', explode( ',', \IPS\Request::i()->filters ) ) )
		{
			$where[] = array( 'template_user_edited>0 or template_added_to > 0' );
		}
		$where[] = array( 'template_set_id IN (' . implode( ',' , $parents ) . ')' );
		
		$select = \IPS\Db::i()->select(
			'*, INSTR(\',' . implode( ',' , $parents ) . ',\', CONCAT(\',\',template_set_id,\',\') ) as theorder',
			'core_theme_templates',
			$where,
			'template_location, template_group, template_name, theorder desc'
		);
		
		$return = array();
		foreach( $select as $result )
		{
			if ( $result['template_user_edited'] )
			{
				$outOfDate = $result['template_user_edited'] < \IPS\Application::load( $result['template_app'] )->long_version;
				
				if ( ( $outOfDate and !in_array( 'outofdate', explode( ',', \IPS\Request::i()->filters ) ) ) or ( !$outOfDate and !in_array( 'modified', explode( ',', \IPS\Request::i()->filters ) ) ) )
				{
					unset( $return[ $result['template_app'] ][ $result['template_location'] ][ $result['template_group'] ][ $result['template_name'] ] );
					continue;
				}
			}

			$return[ $result['template_app'] ][ $result['template_location'] ][ $result['template_group'] ][ $result['template_name'] ] = $result['template_name'];
		}
		
		\IPS\Output::i()->json( $return );
	}
	
	/**
	 * [AJAX] Search CSS
	 *
	 * @return	void
	 */
	public function searchcss()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'theme_templates_manage' );
		
		$theme = \IPS\Theme::load( \IPS\Request::i()->id );
		$parents = array( $theme->_id );

		$where = array();
		if ( \IPS\Request::i()->term )
		{
			$where[] = array( 'LOWER(css_content) LIKE ?', '%' . mb_strtolower( \IPS\Request::i()->term ) . '%' );
		}
		if ( in_array( 'inherited', explode( ',', \IPS\Request::i()->filters ) ) )
		{
			try
			{
				foreach( $theme->parents() as $parent )
				{
					$parents[] = $parent->_id;
				}
			}
			catch( \OutOfRangeException $e ) { }
		}
		array_push( $parents, 0 );
		if ( !in_array( 'unique', explode( ',', \IPS\Request::i()->filters ) ) )
		{
			$where[] = array( 'css_added_to=?', \IPS\Request::i()->id );
		}
		if ( !in_array( 'unmodified', explode( ',', \IPS\Request::i()->filters ) ) )
		{
			$where[] = array( 'css_user_edited>0' );
		}
		$where[] = array( 'css_set_id IN (' . implode( ',' , $parents ) . ')' );

		$return = array();
		foreach( \IPS\Db::i()->select(
			'*, INSTR(\',' . implode( ',' , $parents ) . ',\', CONCAT(\',\',css_set_id,\',\') ) as theorder',
			'core_theme_css',
			$where,
			'css_location, css_path, css_name, theorder desc'
		) as $result )
		{
			if ( $result['css_user_edited'] )
			{
				$outOfDate = $result['css_user_edited'] < \IPS\Application::load( $result['css_app'] )->long_version;
				
				if ( !$result['css_added_to'] and ( ( $outOfDate and !in_array( 'outofdate', explode( ',', \IPS\Request::i()->filters ) ) ) or ( !$outOfDate and !in_array( 'modified', explode( ',', \IPS\Request::i()->filters ) ) ) ) )
				{
					unset( $return[ $result['css_app'] ][ $result['css_location'] ][ $result['css_path'] ][ $result['css_name'] ] );
					continue;
				}
			}
			
			$return[ $result['css_app'] ][ $result['css_location'] ][ $result['css_path'] ][ $result['css_name'] ] = $result['css_name'];
		}
		
		\IPS\Output::i()->json( $return );
	}
}