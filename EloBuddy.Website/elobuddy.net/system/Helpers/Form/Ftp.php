<?php
/**
 * @brief		FTP Details input class for Form Builder
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	Nexus
 * @since		17 Apr 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * FTP input class for Form Builder
 */
class _Ftp extends \IPS\Helpers\Form\FormAbstract
{	
	/**
	 * @brief	Default Options
	 * @code
	 		'validate'				=> TRUE,		// Should details be validated?
	 		'rejectUnsupportedSftp'	=> FALSE,		// If SFTP deatils are provided, but the server doesn't support it, should validation fail?
	 	);
	 * @endcode
	 */
	protected $defaultOptions = array(
		'validate'				=> TRUE,
		'rejectUnsupportedSftp'	=> FALSE,
	);
		
	/** 
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html()
	{
		\IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'global_forms.js', 'nexus', 'global' ) );
		$value = is_array( $this->value ) ? $this->value : json_decode( \IPS\Text\Encrypt::fromTag( $this->value )->decrypt(), TRUE );
		return \IPS\Theme::i()->getTemplate( 'forms', 'core', 'global' )->ftp( $this->name, $value );
	}
	
	/** 
	 * Validate
	 *
	 * @param	array	$value	The value
	 * @return	\IPS\Ftp
	 */
	public static function connectFromValue( $value )
	{
		if ( $value['protocol'] == 'sftp' )
		{
			$ftp = new \IPS\Ftp\Sftp( $value['server'], $value['un'], $value['pw'], $value['port'] );
		}
		else
		{
			$ftp = new \IPS\Ftp( $value['server'], $value['un'], $value['pw'], $value['port'], ( $value['protocol'] == 'ssl_ftp' ) );
		}
		
		$ftp->chdir( $value['path'] );
		
		return $ftp;
	}
	
	/** 
	 * Validate
	 *
	 * @return	string
	 */
	public function validate()
	{
		if ( $this->value['server'] or $this->value['un'] or $this->value['pw'] )
		{
			if ( $this->options['validate'] )
			{
				try
				{
					$ftp = static::connectFromValue( $this->value );
				}
				catch ( \IPS\Ftp\Exception $e )
				{
					throw new \DomainException( 'ftp_err-' . $e->getMessage() );
				}
				catch ( \BadMethodCallException $e )
				{
					// This means we tried an SFTP connection, but the server doesn't support it. We'll have to assume it's correct unless we've specifically set not to
					if ( $this->options['rejectUnsupportedSftp'] )
					{
						throw new \DomainException( 'ftp_err_no_ssl' );
					}
				}
			}
			
			if( $this->customValidationCode !== NULL )
			{
				call_user_func( $this->customValidationCode, $ftp );
			}
		}
		elseif ( $this->required )
		{
			throw new \DomainException( 'form_required' );
		}
	}
	
	/**
	 * String Value
	 *
	 * @param	mixed	$value	The value
	 * @return	string
	 */
	public static function stringValue( $value )
	{
		return \IPS\Text\Encrypt::fromPlaintext( json_encode( $value ) )->tag();
	}
}