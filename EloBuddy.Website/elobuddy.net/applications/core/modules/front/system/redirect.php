<?php
/**
 * @brief		External redirector with key checks
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		12 Jun 2013
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
 * Redirect
 */
class _redirect extends \IPS\Dispatcher\Controller
{
	/**
	 * Handle munged links
	 *
	 * @return	void
	 */
	protected function manage()
	{
		/* First check the key to make sure this actually came from HTMLPurifier */
		if ( \IPS\Login::compareHashes( hash_hmac( "sha256", \IPS\Request::i()->url, \IPS\SITE_SECRET_KEY ?: md5( \IPS\Settings::i()->sql_pass . \IPS\Settings::i()->board_url . \IPS\Settings::i()->sql_database ) ), (string) \IPS\Request::i()->key ) )
		{
			/* Construct the URL */
			$url = \IPS\Http\Url::external( \IPS\Request::i()->url );
			
			/* If it's a resource (image, etc.), we pull the actual contents to prevent the referrer being exposed (which is an issue in the ACP where the session ID is private) */
			if ( \IPS\Request::i()->resource )
			{
				/* Except if it's internal or localhost, we can't make a HTTP request to it because doing that would potentially allow access to secured resources because the server
					thinks the request is internal. We don't need to worry about about things on this domain getting access to the referrer anyway */
				if ( $url->isLocalhost() )
				{
					\IPS\Output::i()->redirect( $url, NULL, 303 );
				}
				/* For everything else, pull the contents... */
				else
				{
					/* It can't be protocol relative */
					if ( !$url->data['scheme'] )
					{
						$url = \IPS\Http\Url::external( 'http:' . \IPS\Request::i()->url );
					}

					/* Get the contents */
					try
					{
						$response = \IPS\Http\Url::external( $url )->request( \IPS\DEFAULT_REQUEST_TIMEOUT )->get();
					}
					catch ( \Exception $e )
					{
						\IPS\Output::i()->redirect( \IPS\Http\Url::internal('') );
					}
					
					/* Send output - we only want to pass along the content and content type. We can't allow the response
						to perform a redirect (which would cause the referrer to be exposed) and we don't want to pass
						along any of their headers which could include setting cookies, etc. */
					\IPS\Output::i()->sendOutput( $response->content, $response->httpResponseCode == 200 ? 200 : 404, isset( $response->httpHeaders['Content-Type'] ) ? $response->httpHeaders['Content-Type'] : 'unknown/unknown' );
				}
			}
			/* For everything else, we'll do a 303 redirect */
			else
			{
				\IPS\Output::i()->redirect( $url, \IPS\Member::loggedIn()->language()->addToStack('external_redirect'), 303, TRUE );
			}
		}
		/* If it doesn't validate, send the user to the index page */
		else
		{
			\IPS\Output::i()->redirect( \IPS\Http\Url::internal('') );
		}
	}

	/**
	 * Redirect an ACP click
	 *
	 * @note	The purpose of this method is to avoid exposing \IPS\CP_DIRECTORY to non-admins
	 * @return	void
	 */
	protected function admin()
	{
		if( \IPS\Member::loggedIn()->isAdmin() )
		{
			$queryString	= base64_decode( \IPS\Request::i()->_data );
			\IPS\Output::i()->redirect( new \IPS\Http\Url( \IPS\Http\Url::baseUrl() . \IPS\CP_DIRECTORY . '/?' . $queryString, TRUE ) );
		}

		\IPS\Output::i()->error( 'no_access_cp', '2C159/3', 403 );
	}

	/**
	 * Redirect an advertisement click
	 *
	 * @return	void
	 */
	protected function advertisement()
	{
		/* CSRF check */
		\IPS\Session::i()->csrfCheck();

		/* Get the advertisement */
		$advertisement	= array();

		if( isset( \IPS\Request::i()->ad ) )
		{
			try
			{
				$advertisement	= \IPS\core\Advertisement::load( \IPS\Request::i()->ad );
			}
			catch( \OutOfRangeException $e )
			{
				\IPS\Output::i()->error( 'ad_not_found', '2C159/2', 404, 'ad_not_found_admin' );
			}
		}

		if( !$advertisement->id OR !$advertisement->link )
		{
			\IPS\Output::i()->error( 'ad_not_found', '2C159/1', 404, 'ad_not_found_admin' );
		}

		/* We need to update click count for this advertisement. Does it need to be shut off too due to hitting click maximum?
			Note that this needs to be done as a string to do "col=col+1", which is why we're not using the ActiveRecord save() method.
			Updating by doing col=col+1 is more reliable when there are several clicks at nearly the same time. */
		$update	= "ad_clicks=ad_clicks+1";

		if( $advertisement->maximum_unit == 'c' AND $advertisement->maximum_value > -1 AND $advertisement->clicks + 1 >= $advertisement->maximum_value )
		{
			$update	.= ", ad_active=0";

			unset( \IPS\Data\Cache::i()->advertisements );
		}

		/* Update the database */
		\IPS\Db::i()->update( 'core_advertisements', $update, array( 'ad_id=?', $advertisement->id ) );

		/* And do the redirect */
		\IPS\Output::i()->redirect( \IPS\Http\Url::external( $advertisement->link ) );
	}
}