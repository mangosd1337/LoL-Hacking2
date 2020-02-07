<?php

$SQL[] = "ALTER TABLE cal_calendars DROP cal_permissions,
	ADD cal_title_seo VARCHAR( 255 ) NULL DEFAULT NULL,
	ADD cal_comment_moderate TINYINT( 1 ) NOT NULL DEFAULT '0',
	ADD cal_rsvp_owner TINYINT( 1 ) NOT NULL DEFAULT '0';";

$SQL[] = "CREATE TABLE cal_event_ratings (
  rating_id int(10) NOT NULL auto_increment,
  rating_eid int(10) NOT NULL default '0',
  rating_member_id mediumint(8) NOT NULL default '0',
  rating_value smallint(6) NOT NULL default '0',
  rating_ip_address varchar(46) NOT NULL,
  PRIMARY KEY  (rating_id),
  KEY rating_eid (rating_eid,rating_member_id),
  KEY rating_ip_address (rating_ip_address)
);";

$SQL[] = "CREATE TABLE cal_event_comments (
  comment_id int(10) NOT NULL auto_increment,
  comment_eid int(10) NOT NULL default '0',
  comment_mid mediumint(8) NOT NULL default '0',
  comment_date int(11) NOT NULL default '0',
  comment_approved tinyint(1) NOT NULL default '0',
  comment_text mediumtext,
  comment_append_edit tinyint(1) NOT NULL default '0',
  comment_edit_time int(11) NOT NULL default '0',
  comment_edit_name varchar(255) default NULL,
  ip_address varchar(46) default NULL,
  comment_author varchar(255) default NULL,
  PRIMARY KEY  (comment_id),
  KEY comment_eid (comment_eid),
  KEY ip_address (ip_address)
);";

$SQL[] = "CREATE TABLE cal_event_rsvp (
  rsvp_id int(11) NOT NULL auto_increment,
  rsvp_event_id int(11) NOT NULL default '0',
  rsvp_member_id int(11) NOT NULL default '0',
  rsvp_date int(11) NOT NULL default '0',
  PRIMARY KEY  (rsvp_id),
  KEY rsvp_event_id (rsvp_event_id),
  KEY rsvp_member_id (rsvp_member_id)
);";

$SQL[] = "CREATE TABLE cal_import_feeds (
  feed_id int(11) NOT NULL auto_increment,
  feed_title varchar(255) default NULL,
  feed_url text,
  feed_added int(11) NOT NULL default '0',
  feed_lastupdated int(11) NOT NULL default '0',
  feed_recache_freq int(11) NOT NULL default '0',
  feed_calendar_id int(11) NOT NULL default '0',
  feed_member_id INT NOT NULL DEFAULT '0',
  feed_next_run INT NOT NULL DEFAULT '0',
  PRIMARY KEY  (feed_id),
  KEY feed_calendar_id (feed_calendar_id),
  KEY feed_next_run (feed_next_run)
);";

$SQL[] = "CREATE TABLE cal_import_map (
  import_id int(11) NOT NULL auto_increment,
  import_feed_id int(11) NOT NULL default '0',
  import_event_id int(11) NOT NULL default '0',
  import_guid varchar(255) NOT NULL default '0',
  PRIMARY KEY  (import_id),
  KEY import_feed_id (import_feed_id),
  KEY import_event_id (import_event_id),
  KEY import_guid (import_guid)
);";



$SQL[] = "RENAME TABLE cal_events TO cal_events_bak;";

$SQL[] = "CREATE TABLE cal_events (
  event_id int(10) unsigned NOT NULL auto_increment,
  event_calendar_id int(10) unsigned NOT NULL default '0',
  event_member_id mediumint(8) unsigned NOT NULL default '0',
  event_content mediumtext,
  event_title varchar(255) NOT NULL default '',
  event_smilies tinyint(1) NOT NULL default '0',
  event_comments int NOT NULL default '0',
  event_rsvp tinyint(1) NOT NULL default '0',
  event_perms text,
  event_private tinyint(1) NOT NULL default '0',
  event_approved tinyint(1) NOT NULL default '0',
  event_saved int(10) unsigned NOT NULL default '0',
  event_lastupdated int(10) unsigned NOT NULL default '0',
  event_recurring int(2) unsigned NOT NULL default '0',
  event_start_date datetime NOT NULL,
  event_end_date datetime default NULL,
  event_title_seo VARCHAR( 255 ) NULL DEFAULT NULL,
  event_rating_total INT NOT NULL DEFAULT '0',
  event_rating_hits INT NOT NULL DEFAULT '0',
  event_rating_avg INT NOT NULL DEFAULT '0',
  event_attachments INT NOT NULL DEFAULT '0',
  event_post_key VARCHAR( 32 ) NULL DEFAULT NULL,
  event_comments_pending INT NOT NULL DEFAULT '0',
  event_sequence INT NOT NULL DEFAULT '0',
  PRIMARY KEY  (event_id),
  KEY approved (event_calendar_id , event_approved , event_start_date , event_end_date),
  KEY event_member_id (event_member_id, event_approved, event_private, event_lastupdated)
);";

$SQL[] = "INSERT INTO cal_events SELECT event_id, event_calendar_id, event_member_id, event_content, event_title, event_smilies, 0, 0, event_perms, event_private, event_approved, event_unixstamp, event_unixstamp,
event_recurring, FROM_UNIXTIME(event_unix_from), CASE WHEN event_unix_to > 0 THEN FROM_UNIXTIME(event_unix_to) ELSE NULL END, '', 0, 0, 0, 0, MD5( CONCAT( event_id, event_title ) ), 0, 1 FROM " . \IPS\Db::i()->prefix . "cal_events_bak;";

$SQL[] = "DROP TABLE cal_events_bak;";
