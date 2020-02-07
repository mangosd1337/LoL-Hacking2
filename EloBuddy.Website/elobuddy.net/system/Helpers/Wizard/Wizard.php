<?php
/**
 * @brief		Wizard Helper
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jul 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Wizard Helper
 *
 * @code
	\IPS\Output::i()->output = new \IPS\Helpers\Wizard(
		array(
			'first_step'	=> function( $data )
			{
				$form = new \IPS\Helpers\Form;
				$form->add( ... );
				
				if ( $values = $form->values() )
				{
					return $values;
				}
				
				return $form;
			},
			'second_step'	=> function ( $data )
			{
				// $data contains the form values from the previous step
			}
		),
		\IPS\Http\Url::internal( 'app=example&module=example&controller=example&do=wizard' )
	);
 * @endcode
 */
class _Wizard
{
	/**
	 * @brief	Steps
	 */
	protected $steps = array();
	
	/**
	 * @brief	Base URL
	 */
	protected $baseUrl;
	
	/**
	 * @brief	Show steps?
	 */
	protected $showSteps = TRUE;
	
	/**
	 * @brief	Key used for \IPS\Data\Store
	 */
	protected $dataKey = TRUE;
	
	/**
	 * @brief	Template
	 */
	public $template = NULL;
	
	/**
	 * Constructor
	 *
	 * @param	array			$steps			An array of callback functions. Each function should return either a string to output or (if the step is done) an array (which can be blank) of arbitrary data to retain between steps (which will be passed to each callback function). The keys should be langauge keys for the title of the step.
	 * @param	\IPS\Http\Url	$baseUrl		The base URL (used when moving between steps)
	 * @param	array			$initialData	The initial data
	 * @return	void
	 */
	public function __construct( $steps, $baseUrl, $showSteps=TRUE, $initialData=NULL )
	{
		$this->steps = $steps;
		$this->baseUrl = $baseUrl;
		$this->showSteps = $showSteps;
		$this->template = array( \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' ), 'wizard' );
				
		if ( isset( \IPS\Request::i()->_new ) )
		{
			unset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] );
            unset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] );
            
            if ( !is_null( $initialData ) )
            {
	            $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] = $initialData;
            }
			
			if ( !\IPS\Request::i()->isAjax() )
			{
				\IPS\Output::i()->redirect( $baseUrl );
			}
		}
	}

	/**
	 * Render
	 *
	 * @return	string
	 */
	public function __toString()
	{
		try
		{
			$stepKeys = array_keys( $this->steps );

			/* Get our data */
			$data = array();
			if ( isset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] ) )
			{
				$data = $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ];
			}

			/* What step are we on? */
			$activeStep = NULL;
			if ( isset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] ) )
			{
				$activeStep = $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ];

				if ( isset( \IPS\Request::i()->_step ) and in_array( \IPS\Request::i()->_step, $stepKeys ) )
				{
					foreach ( $stepKeys as $key )
					{
						if ( $key == $activeStep )
						{
							break;
						}
						elseif ( $key == \IPS\Request::i()->_step )
						{
							$activeStep = $key;
							break;
						}
					}
				}
			}
			else
			{
				foreach ( $stepKeys as $key )
				{
					$activeStep = $key;
					break;
				}
			}

			/* Get it's output */
			$output = call_user_func( $this->steps[ $activeStep ], $data );
			while ( is_array( $output ) )
			{
				$data = array_merge( $data, $output );

				$nextStep = NULL;
				$foundJustDone = FALSE;

				foreach ( $stepKeys as $key )
				{
					if ( $foundJustDone )
					{
						$activeStep = $key;
						break;
					}
					elseif ( $key == $activeStep )
					{
						$foundJustDone = TRUE;
					}
				}

				/* Update the Wizard session data */
				$_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] = $activeStep;
				$_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] = $data;

				$output = call_user_func( $this->steps[ $activeStep ], $data );
			}

			/* Display */
			return call_user_func( $this->template, $stepKeys, $activeStep, $output, $this->baseUrl, $this->showSteps );
		}
		catch ( \Exception $e )
		{
			\IPS\IPS::exceptionHandler( $e );
		}
		catch ( \Throwable $e )
		{
			\IPS\IPS::exceptionHandler( $e );
		}
	}
}