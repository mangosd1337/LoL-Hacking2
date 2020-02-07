<?php
/**
 * @brief		User CP Controller
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 Jun 2013
 * @version		SVN_VERSION_NUMBER
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * User CP Controller
 */
class _settings extends \IPS\Dispatcher\Controller
{

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Output::i()->sidebar['enabled'] = FALSE;
		parent::execute();
	}

	/**
	 * Settings
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* Only logged in members */
		if ( !\IPS\Member::loggedIn()->member_id )
		{
			\IPS\Output::i()->error( 'no_module_permission_guest', '2C122/1', 403, '' );
		}
		
		/* Work out output */
		$area = \IPS\Request::i()->area ?: 'overview';
		if ( method_exists( $this, "_{$area}" ) )
		{
			$output = call_user_func( array( $this, "_{$area}" ) );
		}
				
		/* What can we do? */
		$canChangeEmail = FALSE;
		$canChangePassword = FALSE;
		$canChangeUsername = FALSE;
		foreach ( \IPS\Login::handlers( TRUE ) as $k => $handler )
		{
			if ( \IPS\Member::loggedIn()->group['g_dname_changes'] and $handler->canChange( 'username', \IPS\Member::loggedIn() ) )
			{
				$canChangeUsername = TRUE;
			}
			if ( $handler->canChange( 'email', \IPS\Member::loggedIn() ) )
			{
				$canChangeEmail = TRUE;
			}
			if ( $handler->canChange( 'password', \IPS\Member::loggedIn() ) )
			{
				$canChangePassword = TRUE;
			}
		}

		$sigLimits = explode( ":", \IPS\Member::loggedIn()->group['g_signature_limits'] );
		$canChangeSignature = (bool) ( \IPS\Settings::i()->signatures_enabled && !$sigLimits[0]	);
				
		/* Add sync services */
		$services = \IPS\core\ProfileSync\ProfileSyncAbstract::services();
		
		/* Display */
		\IPS\Output::i()->title = \IPS\Member::loggedIn()->language()->addToStack('settings');
		\IPS\Output::i()->breadcrumb[] = array( NULL, \IPS\Member::loggedIn()->language()->addToStack('settings') );
		if ( !\IPS\Request::i()->isAjax() )
		{
			if ( \IPS\Request::i()->service )
			{
				$area = "{$area}_" . \IPS\Request::i()->service;
			}
            
            \IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings.css' ) );
            
            if ( \IPS\Theme::i()->settings['responsive'] )
            {
                \IPS\Output::i()->cssFiles	= array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'styles/settings_responsive.css' ) );
            }
            
			\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'system' )->settings( $area, $output, $canChangeEmail, $canChangePassword, $canChangeUsername, $canChangeSignature, $services );
		}
		else
		{
			\IPS\Output::i()->output = $output;
		}
	}
	
	/**
	 * Overview
	 *
	 * @return	string
	 */
	protected function _overview()
	{
		$services = array();

		foreach ( \IPS\core\ProfileSync\ProfileSyncAbstract::services() as $key => $class )
		{
			$services[$key] = new $class( \IPS\Member::loggedIn() );
		}
				
		return \IPS\Theme::i()->getTemplate( 'system' )->settingsOverview( $services );
	}
	
	/**
	 * Email
	 *
	 * @return	string
	 */
	protected function _email()
	{
		if( \IPS\Member::loggedIn()->isAdmin() )
		{
			return \IPS\Theme::i()->getTemplate( 'system' )->settingsEmail();
		}
		
		/* Do we have any pending validation emails? */
		try
		{
			$pending = \IPS\Db::i()->select( '*', 'core_validating', array( 'member_id=? AND email_chg=1', \IPS\Member::loggedIn()->member_id ), 'entry_date DESC' )->first();
		}
		catch( \UnderflowException $e )
		{
			$pending = null;
		}
		
		/* Build the form */
		$form = new \IPS\Helpers\Form;
		$form->addDummy( 'current_email', htmlspecialchars( \IPS\Member::loggedIn()->email, \IPS\HTMLENTITIES, 'UTF-8', FALSE ) );
		$form->add( new \IPS\Helpers\Form\Email( 'new_email', '', TRUE, array( 'accountEmail' => TRUE ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'current_password', '', TRUE, array( 'validateFor' => \IPS\Member::loggedIn() ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			$oldEmail = \IPS\Member::loggedIn()->email;

			/* Change the email */
			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				/* We cannot update our email address in some login handlers, that's ok */
				try
				{
					$handler->changeEmail( \IPS\Member::loggedIn(), $oldEmail, $values['new_email'] );
				}
				catch( \BadMethodCallException $e ){}
			}
						
			/* Delete any pending validation emails */
			if ( $pending['vid'] )
			{
				\IPS\Db::i()->delete( 'core_validating', array( 'member_id=? AND email_chg=1', \IPS\Member::loggedIn()->member_id ) );
			}
						
			/* Send a validation email if we need to */
			if ( \IPS\Settings::i()->reg_auth_type == 'user' or \IPS\Settings::i()->reg_auth_type == 'admin_user' )
			{
				$vid = \IPS\Login::generateRandomString();
				
				\IPS\Db::i()->insert( 'core_validating', array(
					'vid'			=> $vid,
					'member_id'		=> \IPS\Member::loggedIn()->member_id,
					'entry_date'	=> time(),
					'email_chg'		=> TRUE,
					'ip_address'	=> \IPS\Request::i()->ipAddress(),
					'prev_email'	=> \IPS\Member::loggedIn()->email,
					'email_sent'	=> time(),
				) );

				\IPS\Member::loggedIn()->members_bitoptions['validating'] = TRUE;
				\IPS\Member::loggedIn()->save();
				
				\IPS\Email::buildFromTemplate( 'core', 'email_change', array( \IPS\Member::loggedIn(), $vid ), \IPS\Email::TYPE_TRANSACTIONAL )->send( \IPS\Member::loggedIn() );
							
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( '' ) );
			}
			
			/* Or just redirect */
			else
			{
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings&area=email', 'front', 'settings' ), 'email_changed' );
			}
		}
		
		return \IPS\Theme::i()->getTemplate( 'system' )->settingsEmail( $form );
	}
	
	/**
	 * Password
	 *
	 * @return	string
	 */
	protected function _password()
	{
		if( \IPS\Member::loggedIn()->isAdmin() )
		{
			return \IPS\Theme::i()->getTemplate( 'system' )->settingsPassword();
		}
		
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\Password( 'current_password', '', TRUE, array( 'validateFor' => \IPS\Member::loggedIn() ) ) );
		$form->add( new \IPS\Helpers\Form\Password( 'new_password', '', TRUE ) );
		$form->add( new \IPS\Helpers\Form\Password( 'confirm_new_password', '', TRUE, array( 'confirm' => 'new_password' ) ) );
		
		if ( $values = $form->values() )
		{
			foreach ( \IPS\Login::handlers( TRUE ) as $handler )
			{
				/* We cannot update our password in some login handlers, that's ok */
				try
				{
					$handler->changePassword( \IPS\Member::loggedIn(), $values['new_password'] );
				}
				catch( \BadMethodCallException $e ){}
			}

			/* If we have a pass_hash cookie, update it so we stay logged in on the next page load */
			if( isset( \IPS\Request::i()->cookie['pass_hash'] ) )
			{
				$expire = new \IPS\DateTime;
				$expire->add( new \DateInterval( 'P7D' ) );
				\IPS\Request::i()->setCookie( 'pass_hash', \IPS\Member::loggedIn()->member_login_key, $expire );
			}

			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings&area=password', 'front', 'settings' ), 'password_changed' );
		}
		
		return \IPS\Theme::i()->getTemplate( 'system' )->settingsPassword( $form );
	}
	
	/**
	 * Username
	 *
	 * @return	string
	 */
	protected function _username()
	{
		/* Check they have permission to change their username */
		if( !\IPS\Member::loggedIn()->group['g_dname_changes'] )
		{
			\IPS\Output::i()->error( 'username_err_nochange', '1C122/4', 403, '' );
		}
				
		if ( \IPS\Member::loggedIn()->group['g_displayname_unit'] )
		{
			if ( \IPS\Member::loggedIn()->group['gbw_displayname_unit_type'] )
			{
				if ( \IPS\Member::loggedIn()->joined->diff( \IPS\DateTime::create() )->days < \IPS\Member::loggedIn()->group['g_displayname_unit'] )
				{
					\IPS\Output::i()->error(
						\IPS\Member::loggedIn()->language()->addToStack( 'username_err_days', FALSE, array( 'sprintf' => array(
						\IPS\Member::loggedIn()->joined->add(
							new \DateInterval( 'P' . \IPS\Member::loggedIn()->group['g_displayname_unit'] . 'D' )
						)->localeDate()
						), 'pluralize' => array( \IPS\Member::loggedIn()->group['g_displayname_unit'] ) ) ),
					'1C122/5', 403, '' );
				}
			}
			else
			{
				if ( \IPS\Member::loggedIn()->member_posts < \IPS\Member::loggedIn()->group['g_displayname_unit'] )
				{
					\IPS\Output::i()->error( 
						\IPS\Member::loggedIn()->language()->addToStack( 'username_err_posts' , FALSE, array( 'sprintf' => array(
						( \IPS\Member::loggedIn()->group['g_displayname_unit'] - \IPS\Member::loggedIn()->member_posts )
						), 'pluralize' => array( \IPS\Member::loggedIn()->group['g_displayname_unit'] ) ) ),
					'1C122/6', 403, '' );
				}
			}
		}
		
		/* How many changes */
		$nameCount = \IPS\Db::i()->select( 'COUNT(*) as count, MIN(dname_date) as min_date', 'core_dnames_change', array(
			'dname_member_id=? AND dname_date>?',
			\IPS\Member::loggedIn()->member_id,
			\IPS\DateTime::create()->sub( new \DateInterval( 'P' . \IPS\Member::loggedIn()->group['g_dname_date'] . 'D' ) )->getTimestamp()
		) )->first();

		if ( \IPS\Member::loggedIn()->group['g_dname_changes'] != -1 and $nameCount['count'] >= \IPS\Member::loggedIn()->group['g_dname_changes'] )
		{
			return \IPS\Theme::i()->getTemplate( 'system' )->settingsUsernameLimitReached( \IPS\Member::loggedIn()->language()->addToStack('username_err_limit', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->group['g_dname_date'] ), 'pluralize' => array( \IPS\Member::loggedIn()->group['g_dname_changes'] ) ) ) );
		}
		else
		{
			/* Build form */
			$form = new \IPS\Helpers\Form;
			$form->add( new \IPS\Helpers\Form\Text( 'new_username', '', TRUE, array( 'accountUsername' => \IPS\Member::loggedIn() ) ) );
						
			/* Handle submissions */
			if ( $values = $form->values() )
			{
				$oldName = \IPS\Member::loggedIn()->name;
				
				foreach ( \IPS\Login::handlers( TRUE ) as $handler )
				{
					/* We cannot update our username in some login handlers, that's ok */
					try
					{
						$handler->changeUsername( \IPS\Member::loggedIn(), $oldName, $values['new_username'] );
					}
					catch( \BadMethodCallException $e ){}
				}

				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings&area=username', 'front', 'settings' ), 'username_changed' );
			}
		}

		return \IPS\Theme::i()->getTemplate( 'system' )->settingsUsername( $form, $nameCount['count'], \IPS\Member::loggedIn()->group['g_dname_changes'], $nameCount['min_date'] ? \IPS\DateTime::ts( $nameCount['min_date'] ) : \IPS\Member::loggedIn()->joined, \IPS\Member::loggedIn()->group['g_dname_date'] );
	}
	
	/**
	 * Signature
	 *
	 * @return	string
	 */
	protected function _signature()
	{
		/* Check they have permission to change their signature */
		$sigLimits = explode( ":", \IPS\Member::loggedIn()->group['g_signature_limits']);
		
		if( !\IPS\Settings::i()->signatures_enabled && !$sigLimits[0] )
		{
			\IPS\Output::i()->error( 'signatures_disabled', '2C122/C', 403, '' );
		}
		
		/* Check limits */
		if ( \IPS\Member::loggedIn()->group['g_sig_unit'] )
		{
			/* Days */
			if ( \IPS\Member::loggedIn()->group['gbw_sig_unit_type'] )
			{
				if ( \IPS\Member::loggedIn()->joined->diff( \IPS\DateTime::create() )->days < \IPS\Member::loggedIn()->group['g_sig_unit'] )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->pluralize(
							sprintf(
									\IPS\Member::loggedIn()->language()->get('sig_err_days'),
									\IPS\Member::loggedIn()->joined->add(
											new \DateInterval( 'P' . \IPS\Member::loggedIn()->group['g_sig_unit'] . 'D' )
									)->localeDate()
							), array( \IPS\Member::loggedIn()->group['g_sig_unit'] ) ),
							'1C122/D', 403, '' );
				}
			}
			/* Posts */
			else
			{
				if ( \IPS\Member::loggedIn()->member_posts < \IPS\Member::loggedIn()->group['g_sig_unit'] )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->pluralize(
							sprintf(
									\IPS\Member::loggedIn()->language()->get('sig_err_posts'),
									( \IPS\Member::loggedIn()->group['g_sig_unit'] - \IPS\Member::loggedIn()->member_posts )
							), array( \IPS\Member::loggedIn()->group['g_sig_unit'] ) ),
							'1C122/E', 403, '' );
				}
			}
		}
	
		/* Build form */
		$form = new \IPS\Helpers\Form;
		$form->add( new \IPS\Helpers\Form\YesNo( 'view_sigs', \IPS\Member::loggedIn()->members_bitoptions['view_sigs'], FALSE ) );
		$form->add( new \IPS\Helpers\Form\Editor( 'signature', \IPS\Member::loggedIn()->signature, FALSE, array( 'app' => 'core', 'key' => 'Signatures', 'autoSaveKey' => "frontsig-" .\IPS\Member::loggedIn()->member_id, 'attachIds' => array( \IPS\Member::loggedIn()->member_id ) ) ) );
		
		/* Handle submissions */
		if ( $values = $form->values() )
		{
			if( $values['signature'] )
			{
				/* Check Limits */
				$signature = new \DOMDocument;
				$signature->loadHTML( $values['signature'] );
				
				$errors = array();
				
				/* Links */
				if ( is_numeric( $sigLimits[4] ) and $signature->getElementsByTagName('a')->length > $sigLimits[4] )
				{
					$errors[] = \IPS\Member::loggedIn()->language()->addToStack('sig_num_links_exceeded');
				}
				
				/* Number of Images */
				if ( is_numeric( $sigLimits[1] ) and $signature->getElementsByTagName('img')->length > $sigLimits[1] )
				{
					$errors[] = \IPS\Member::loggedIn()->language()->addToStack('sig_num_images_exceeded');
				}
				
				/* Size of images */
				if ( ( is_numeric( $sigLimits[2] ) and $sigLimits[2] ) or ( is_numeric( $sigLimits[3] ) and $sigLimits[3] ) )
				{
					foreach ( $signature->getElementsByTagName('img') as $image )
					{
						$attachId	= $image->getAttribute('data-fileid');
						$checkSrc	= TRUE;

						if( $attachId )
						{
							try
							{
								$attachment = \IPS\Db::i()->select( 'attach_location, attach_thumb_location', 'core_attachments', array( 'attach_id=?', $attachId ) )->first();
								$imageProperties = \IPS\File::get( 'core_Attachment', $attachment['attach_thumb_location'] ?: $attachment['attach_location'] )->getImageDimensions();

								$checkSrc	= FALSE;
							}
							catch( \UnderflowException $e ){}
						}

						if( $checkSrc )
						{
							$src = $image->getAttribute('src');
							\IPS\Output::i()->parseFileObjectUrls( $src );

							$imageProperties = @getimagesize( $src );
						}
						
						if( is_array( $imageProperties ) AND count( $imageProperties ) )
						{
							if( $imageProperties[0] > $sigLimits[2] OR $imageProperties[1] > $sigLimits[3] )
							{
								$errors[] = \IPS\Member::loggedIn()->language()->addToStack( 'sig_imagetoobig', FALSE, array( 'sprintf' => array( $src, $sigLimits[2], $sigLimits[3] ) ) );
							}
						}
						else
						{
							$errors[] = \IPS\Member::loggedIn()->language()->addToStack( 'sig_imagenotretrievable', FALSE, array( 'sprintf' => array( $src ) ) );
						}
					}
				}
				
				/* Lines */
				if ( is_numeric( $sigLimits[5] ) and ( $signature->getElementsByTagName('p')->length + $signature->getElementsByTagName('br')->length ) > $sigLimits[5] )
				{
					$errors[] = \IPS\Member::loggedIn()->language()->addToStack('sig_num_lines_exceeded');
				}
			}
			
			if( !empty( $errors ) )
			{
				$form->error = \IPS\Member::loggedIn()->language()->addToStack('sig_restrictions_exceeded');
				$form->elements['']['signature']->error = \IPS\Member::loggedIn()->language()->formatList( $errors );
				
				return \IPS\Theme::i()->getTemplate( 'system' )->settingsSignature( $form, $sigLimits );
			}
			
			\IPS\Member::loggedIn()->signature = $values['signature'];
			\IPS\Member::loggedIn()->members_bitoptions['view_sigs'] = $values['view_sigs'];
			
			\IPS\Member::loggedIn()->save();
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal( 'app=core&module=system&controller=settings&area=signature', 'front', 'settings' ), 'signature_changed' );
		}

		return \IPS\Theme::i()->getTemplate( 'system' )->settingsSignature( $form, $sigLimits );
	}
	
	/**
	 * Profile Sync
	 *
	 * @return	string
	 */
	protected function _profilesync()
	{
		$service = \IPS\Request::i()->service;
		$class = 'IPS\core\ProfileSync\\' . $service;
		if ( !class_exists( $class ) or !isset( $class::$loginKey ) )
		{
			\IPS\Output::i()->error( 'page_doesnt_exist', '2C122/B', 404, '' );
		}
				
		$obj = new $class( \IPS\Member::loggedIn() );
		
		if ( $obj->connected() )
		{
			if ( isset( \IPS\Request::i()->sync ) )
			{
				$obj->sync();
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service={$service}", 'front', "settings_{$service}" ), 'profilesync_synced' );
			}
			elseif ( isset( \IPS\Request::i()->disassociate ) )
			{
				/* CSRF check */
				\IPS\Session::i()->csrfCheck();

				/* Check they have another way of signing in */
				$isOkay = FALSE;
				foreach ( \IPS\Login::handlers() as $handler )
				{
					if ( $handler->key != $obj::$loginKey and $handler->canProcess( \IPS\Member::loggedIn() ) )
					{
						$isOkay = TRUE;
						break;
					}
				}
				if ( !$isOkay )
				{
					\IPS\Output::i()->error( \IPS\Member::loggedIn()->language()->addToStack( 'profilesync_cannot_disassociate', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack("profilesync__{$service}") ) ) ), '1C122/I', 403, '' );
				}
				
				/* Do it */
				$obj->disassociate();
				\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service={$service}", 'front', "settings_{$service}" ), 'profile_disassociated' );
			}
						
			$serviceName = 'profilesync__' . $service;
			$headline = ( $obj->name() ) ? \IPS\Member::loggedIn()->language()->addToStack( 'profilesync_headline', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $serviceName ), $obj->name() ) ) ) : \IPS\Member::loggedIn()->language()->addToStack( 'profilesync_headline_no_name', FALSE, array( 'sprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $serviceName ) ) ) );
			
			$settings = $obj->settings();
			
			$form = new \IPS\Helpers\Form;
			$form->class = 'ipsForm_vertical';
			if ( method_exists( $obj, 'photo' ) )
			{
				$form->add( new \IPS\Helpers\Form\Checkbox( 'profilesync_photo', $settings['photo'], FALSE, array( 'labelSprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $serviceName ) ) ) ) );
			}

			if ( method_exists( $obj, 'cover' ) )
			{
				$form->add( new \IPS\Helpers\Form\Checkbox( 'profilesync_cover', $settings['cover'], FALSE, array( 'labelSprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $serviceName ) ) ) ) );
			}
								
			if ( $obj->canImportStatus( \IPS\Member::loggedIn() ) )
			{
				$form->add( new \IPS\Helpers\Form\Checkbox( 'profilesync_status', $settings['status'] == 'import', FALSE, array( 'labelSprintf' => array( \IPS\Member::loggedIn()->language()->addToStack( $serviceName ) ) ) ) );
			}
						
			if ( $values = $form->values() )
			{
				$obj->save( $values );
			}
			
			$photo = ( method_exists( $obj, 'photo' ) ) ? $obj->photo() : NULL;
			return \IPS\Theme::i()->getTemplate( 'system' )->settingsProfileSync( $photo instanceof \IPS\File ? $photo->url : $photo, $headline, method_exists( $obj, 'status' ) ? $obj->status() : NULL, $form, $service, "profilesync__{$service}", ( $settings['photo'] or $settings['cover'] or $settings['status'] ) );
		}
		else
		{
			$login = \IPS\Login\LoginAbstract::load( $class::$loginKey );
			$url = \IPS\Http\Url::internal( "app=core&module=system&controller=settings&area=profilesync&service={$service}", 'front', "settings_{$service}" );
			
			if ( \IPS\Request::i()->loginProcess )
			{
				try
				{
					$login->authenticate( $url, \IPS\Member::loggedIn() );
					\IPS\Output::i()->redirect( $url );
				}
				catch ( \IPS\Login\Exception $e ) { }
			}
			
			return \IPS\Theme::i()->getTemplate( 'system' )->settingsProfileSyncLogin( $login->loginForm( $url, TRUE ),  "profilesync__{$service}" );
		}
	}
	
	/**
	 * Disable All Signatures
	 *
	 * @return	void
	 */
	protected function toggleSigs()
	{
		if ( !\IPS\Settings::i()->signatures_enabled )
		{
			\IPS\Output::i()->error( 'signatures_disabled', '2C122/F', 403, '' );
		}
			
		\IPS\Session::i()->csrfCheck();
			
		if ( \IPS\Member::loggedIn()->members_bitoptions['view_sigs'] )
		{
			\IPS\Member::loggedIn()->members_bitoptions['view_sigs'] = 0;
		}
		else
		{
			\IPS\Member::loggedIn()->members_bitoptions['view_sigs'] = 1;
		}
		
		\IPS\Member::loggedIn()->save();
		
		if ( \IPS\Request::i()->isAjax() )
		{
			\IPS\Output::i()->json( 'OK' );
		}
		
		$redirectUrl = ( !empty( $_SERVER['HTTP_REFERER'] ) ) ? \IPS\Http\Url::external( $_SERVER['HTTP_REFERER'] ) : \IPS\Http\Url::internal( "app=core&module=system&controller=settings", 'front', 'settings' );
		\IPS\Output::i()->redirect( $redirectUrl, 'signature_pref_toggled' );
	}
}