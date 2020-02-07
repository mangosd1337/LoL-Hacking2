<?php
/**
 * @brief		Digest Class
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		08 May 2014
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\Digest;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Digest Class
 */
class _Digest
{
	/**
	 * @brief	[IPS\Member]	Digest member object
	 */
	public $member = NULL;
	
	/**
	 * @brief	Output to include in digest email template
	 */
	public $output = array( 'html' => '', 'plain' => '' );
	
	/**
	 * @brief	Frequency Daily/Weekly
	 */
	public $frequency = NULL;
	
	/**
	 * @brief	Is there anything to send?
	 */
	public $hasContent = FALSE;
	
	/**
	 * @brief	Mail Object
	 */
	protected $mail;
	
	/**
	 * Build Digest
	 *
	 * @param	array	$data	Array of follow records
	 * @return	void
	 */
	public function build( $data )
	{
		/* Banned members should not be emailed */
		if( $this->member->isBanned() )
		{
			return;
		}
		
		/* We just do it this way because for backwards-compatibility, template parsing expects an \IPS\Email object with a $language property
			This email is never actually sent and a new one is generated in send() */
		$this->mail = \IPS\Email::buildFromTemplate( 'core', 'digest', array( $this->member, $this->frequency ), \IPS\Email::TYPE_LIST );
		$this->mail->language = $this->member->language();

		$max	= ceil( 80 / count( array_keys( $data ) ) );
		$count	= 0;
		$total	= count( array_keys( $data ) );

		foreach( $data as $app => $area )
		{
			foreach( $area as $key => $follows )
			{
				$areaPlainOutput = NULL;
				$areaHtmlOutput = NULL;
				$added = FALSE;
				
				/* Following an item or node */
				$class = 'IPS\\' . $app . '\\' . ucfirst( $key );
				
				if ( class_exists( $class ) )
				{
					$parents = class_parents( $class );
					
					if ( in_array( 'IPS\Node\Model', $parents ) )
					{
						foreach ( $follows as $follow )
						{
							if ( property_exists( $class, 'contentItemClass' ) )
							{
								$itemClass= $class::$contentItemClass;
	
								foreach ( $itemClass::getItemsWithPermission( array( 
										array( $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['container'] . '=? AND ' . $itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['date'] . ' > ? AND ' . $itemClass::$databaseTable . '.' .$itemClass::$databasePrefix . $itemClass::$databaseColumnMap['author'] . '!=?', $follow['follow_rel_id'], $follow['follow_notify_sent'] ?: $follow['follow_added'], $follow['follow_member_id'] ) ),
										$itemClass::$databaseTable . '.' . $itemClass::$databasePrefix . $itemClass::$databaseColumnMap['date'] . ' ASC', 
										$max, 
										'read', 
										\IPS\Content\Hideable::FILTER_OWN_HIDDEN, 
										NULL, 
										$this->member, 
										TRUE
								) as $item )
								{
									try
									{
										$areaPlainOutput .= \IPS\Email::template( $app, 'digests__item', 'plaintext', array( $item, $this->mail ) );
										$areaHtmlOutput .= \IPS\Email::template( $app, 'digests__item', 'html', array( $item, $this->mail ) );

										$added = TRUE;
										++$count;
									}
									catch ( \BadMethodCallException $e ) {}
								}
							}
						}
					}
					else if ( in_array( 'IPS\Content\Item', $parents ) )
					{
						foreach ( $follows as $follow )
						{
							try
							{
								$item = $class::load( $follow['follow_rel_id'] );
								
								foreach( $item->comments( 5, NULL, 'date', 'asc', NULL, FALSE, \IPS\DateTime::ts( $follow['follow_notify_sent'] ?: $follow['follow_added'] ) ) as $comment )
								{
									/* If an app forgot digest templates, we don't want the entire task to fail to ever run again */
									if( !\IPS\IN_DEV AND
										( !method_exists( \IPS\Email\Theme\Theme::i()->getTemplate( 'digests', $app, 'plaintext' ), 'comment' ) OR 
										!method_exists( \IPS\Email\Theme\Theme::i()->getTemplate( 'digests', $app, 'html' ), 'comment' ) ) )
									{
										throw new \OutOfRangeException;
									}

									$areaPlainOutput .= \IPS\Email::template( $app, 'digests__comment', 'plaintext', array( $comment, $this->mail ) );
									$areaHtmlOutput .= \IPS\Email::template( $app, 'digests__comment', 'html', array( $comment, $this->mail ) );
									
									$added = TRUE;
									++$count;
								}
							}
							catch( \OutOfRangeException $e )
							{
							}
						}
					}

					/* Wrapper */
					if( $added )
					{
						$this->output['plain'] .= \IPS\Email::template( 'core', 'digests__areaWrapper', 'plaintext', array( $areaPlainOutput, $app, $key, $max, $count, $total, $this->mail ) );
						$this->output['html'] .= \IPS\Email::template( 'core', 'digests__areaWrapper', 'html', array( $areaHtmlOutput, $app, $key, $max, $count, $total, $this->mail ) );
					
						$this->hasContent = TRUE;
					}
				}
			}
		}
	}
	
	/**
	 * Send Digest
	 *
	 * @return	void
	 */
	public function send()
	{		
		if( $this->hasContent )
		{
			$this->mail->setUnsubscribe( 'core', 'unsubscribeDigest' );
			$subject = $this->mail->compileSubject( $this->member );
			$htmlContent = str_replace( "<%digest%>", $this->output['html'], $this->mail->compileContent( 'html', $this->member ) );
			$plaintextContent = str_replace( "<%digest%>", $this->output['plain'], $this->mail->compileContent( 'plaintext', $this->member ) );
			
			\IPS\Email::buildFromContent( $subject, $htmlContent, $plaintextContent, \IPS\Email::TYPE_LIST, FALSE )->send( $this->member );
		}
		
		/* After sending digest update core_follows to set notify_sent (don't forget where clause for frequency) */
		\IPS\Db::i()->update( 'core_follow', array( 'follow_notify_sent' => time() ), array( 'follow_member_id=? AND follow_notify_freq=?', $this->member->member_id, $this->frequency ) );	
	}
}