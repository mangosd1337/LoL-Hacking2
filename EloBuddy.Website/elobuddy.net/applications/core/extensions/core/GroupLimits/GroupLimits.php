<?php
/**
 * @brief		Group Limits
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		07 Nov 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\extensions\core\GroupLimits;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Group Limits
 *
 * This extension is used to define which limit values "win" when a user has secondary groups defined
 *
 */
class _GroupLimits
{
	/**
	 * Get group limits by priority
	 *
	 * @return	array
	 */
	public function getLimits()
	{
		return array (
						'exclude' 		=> array( 'g_id', 'g_icon', 'prefix', 'suffix', 'g_promotion', 'g_bitoptions' ),
						'lessIsMore'	=> array( 'g_search_flood', 'g_pm_flood_mins' ),
						'neg1IsBest'	=> array( 'g_attach_max', 'g_max_bgimg_upload', 'g_max_messages', 'g_pm_perday', 'g_pm_flood_mins' ),
						'zeroIsBest'	=> array( 'g_edit_cutoff', 'g_displayname_unit', 'g_sig_unit', 'g_mod_preview', 'g_ppd_limit', 'g_ppd_unit', 'gbw_no_status_update', 'g_max_bgimg_upload', 'gbw_disable_prefixes', 'gbw_disable_tagging', 'g_attach_per_post' ),
						'callback'		=> array(
								'g_signature_limits' => function( $a, $b, $k )
								{
									/* No limits should win out */
									if( !$a[ $k ] )
									{
										return null;
									}
									
									/* We have limits */
									if( $b[ $k ] )
									{
										$values	= explode( ':', $b[ $k ] );
										$_cur	= explode( ':', $a[ $k ] );
										$_new 	= array();
										
										foreach( $values as $index => $value )
										{
											if( !$_cur[ $index ] or !$values[ $index ] )
											{
												$_new[ $index ]	= NULL;
											}
											/* If signatures are currently disabled but aren't in the group we are checking, signatures should not be disabled (1=disabled, 0=enabled) */
											elseif ( $index == 0 and $_cur[ $index ] > $values[ $index ] )
											{
												$_new[ $index ]	= $values[ $index ];
											}
											/* If signatures are currently enabled but aren't in the group we are checking, signatures should not be disabled */
											elseif ( $index == 0 and $_cur[ $index ] < $values[ $index ] )
											{
												$_new[ $index ]	= $_cur[ $index ];
											}
											else if( $_cur[ $index ] > $values[ $index ] )
											{
												$_new[ $index ]	= $_cur[ $index ];
											}
											else
											{
												$_new[ $index ]	= $values[ $index ];
											}
										}
																					
										ksort($_new);
										return implode( ':', $_new );
									}
									else
									{
										/* Set no limits */
										return NULL;
									}
								},
								'g_photo_max_vars'	=> function ( $a, $b, $k )
								{
									/* No limits should win out */
									if( !$a[ $k ] )
									{
										return NULL;
									}
										
									/* We have limits */
									if( $b[ $k ] )
									{
										$values	= explode( ':', $b[ $k ] );
										$_cur	= explode( ':', $a[ $k ] );
										$_new 	= array();
									
										foreach( $values as $index => $value )
										{
											if( !$_cur[ $index ] or !$values[ $index ] )
											{
												$_new[ $index ]	= NULL;
											}
											else if( $_cur[ $index ] > $values[ $index ] )
											{
												$_new[ $index ]	= $_cur[ $index ];
											}
											else
											{
												$_new[ $index ]	= $values[ $index ];
											}
										}
											
										ksort($_new);
										return implode( ':', $_new );
									}
									else
									{
										/* Set no limits */
										return NULL;
									}
								},
								'g_dname_date'		=> function( $a )
								{
									/* This is handled by g_dname_changes below */
									return $a['g_dname_date'];
								},
								'g_dname_changes'	=> function( $a, $b, $k )
								{
									$changes	= $b[ $k ];
									$timeFrame	= $a['g_dname_date'];

                                    if( $changes == -1 )
                                    {
                                        return array(
                                            'g_dname_date'		=> 0,
                                            'g_dname_changes'	=> $changes
                                        );
                                    }

									/* No time frame restriction */
									if( !$timeFrame )
									{
										/* This group allows more changes */
										if( $changes > $a[ $k ] )
										{
											return array(
												'g_dname_date'		=> 0,
												'g_dname_changes'	=> $changes
											);
										}
										
										/* Existing data is date restricted */
										else if( $a['g_dname_date'] )
										{
											if( $a[ $k ] )
											{
												$_compare	= round( $a['g_dname_date'] / $a[ $k ] );
												
												if( $_compare > $changes )
												{
													return array(
														'g_dname_date'		=> 0,
														'g_dname_changes'	=> $changes
													);
												}
											}
										}
									}
									
									/* Time frame restriction */
									else if( $changes )
									{
										$_compare	= round( $timeFrame / $changes );

										/* Existing has no time frame restriction */
										if( !$a['g_dname_date'] AND $a[ $k ] )
										{
											if( $_compare < $a[ $k ] )
											{
												return array(
													'g_dname_date'		=> $timeFrame,
													'g_dname_changes'	=> $changes
												);
											}
										}
										else if( !$a['g_dname_date'] )
										{
											return array(
												'g_dname_date'		=> $timeFrame,
												'g_dname_changes'	=> $changes
											);
										}
										else if( $a['g_dname_date'] AND $a[ $k ] )
										{
											$_oldCompare	= $a['g_dname_date'] / $a[ $k ];

											if( $_compare < $_oldCompare )
											{
												return array(
													'g_dname_date'		=> $timeFrame,
													'g_dname_changes'	=> $changes
												);
											}
										}
									}
								},
								'gbw_mod_post_unit_type'		=> function( $a )
								{
									/* This is handled by g_mod_post_unit below */
									return $a['gbw_mod_post_unit_type'];
								},
								'g_mod_post_unit'	=> function( $a, $b, $k, $member )
								{
									/* Have we met the current requirements? */
									if ( ( !$a['gbw_mod_post_unit_type'] and $a['g_mod_post_unit'] >= $member['member_posts'] ) or ( $a['gbw_mod_post_unit_type'] and time() >= ( $member['joined'] + ( $a['g_mod_post_unit'] * 3600 ) ) ) )
									{
										/* Yes - so let's stick with this */
										return $a['g_mod_post_unit'];
									}
									else
									{
										/* No - go with the new group */
										return array(
											'g_mod_post_unit'			=> $b[ $k ],
											'gbw_mod_post_unit_type'	=> $b['gbw_mod_post_unit_type']
										);
									}
								}
						)
				);
	}
}