CREATE TABLE IF NOT EXISTS `exp_ratings` (
	`rating_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`rated_rating_id`		int(10) unsigned		NOT NULL DEFAULT 0,
	`entry_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`channel_id`			int(4) unsigned			NOT NULL DEFAULT 0,
	`rating_author_id`		int(10) unsigned		NOT NULL DEFAULT 0,
	`quarantine`			char(1)					NOT NULL DEFAULT 'n',
	`collection`			varchar(50)				NOT NULL DEFAULT '',
	`status`				char(10)				NOT NULL DEFAULT 'open',
	`name`					varchar(50)				NOT NULL DEFAULT '',
	`email`					varchar(50)				NOT NULL DEFAULT '',
	`url`					varchar(75)				NOT NULL DEFAULT '',
	`location`				varchar(50)				NOT NULL DEFAULT '',
	`ip_address`			varchar(40)				NOT NULL DEFAULT '',
	`rating_date`			int(10)	unsigned		NOT NULL DEFAULT 0,
	`edit_date`				int(10)	unsigned		NOT NULL DEFAULT 0,
	`rating_review_count`	int(10)	unsigned		NOT NULL DEFAULT 0,
	`rating_review`			text,
	`rating_helpful_y`		int(10)					NOT NULL DEFAULT 0,
	`rating_helpful_n`		int(10)					NOT NULL DEFAULT 0,
	`rating`				int	unsigned			NULL	 DEFAULT NULL,
	`review`				text,
	`notify`				char(1)					NOT NULL DEFAULT 'n',
	`duplicate`				CHAR(1)					NOT NULL DEFAULT 'n',
	`sticky`				char(1)					NOT NULL DEFAULT 'n',
	PRIMARY KEY				(`rating_id`),
	KEY						`rated_rating_id` (`rated_rating_id`),
	KEY						`entry_id` (`entry_id`),
	KEY						`channel_id` (`channel_id`),
	KEY						`rating_author_id` (`rating_author_id`),
	KEY						`collection` (`collection`),
	KEY						`status` (`status`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_cache` (
	`cache_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`name`					varchar(30)				NOT NULL DEFAULT '',
	`date`					int(10)	unsigned		NOT NULL DEFAULT 0,
	`array`					text,
	PRIMARY KEY				(`cache_id`),
	KEY						`name` (`name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_fields` (
	`field_id`				int(6) unsigned			NOT NULL AUTO_INCREMENT,
	`field_name`			varchar(32)				NOT NULL DEFAULT '',
	`field_label`			varchar(50)				NOT NULL DEFAULT '',
	`field_type`			varchar(12)				NOT NULL DEFAULT 'text',
	`field_list_items`		text,
	`field_maxl`			smallint(3)				NOT NULL DEFAULT '150',
	`field_search`			char(1)					NOT NULL DEFAULT 'n',
	`field_fmt`				varchar(40)				NOT NULL DEFAULT 'none',
	`field_order`			int(3) unsigned			NOT NULL DEFAULT 1,
	PRIMARY KEY				(`field_id`),
	KEY						`field_name` (`field_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_params` (
	`params_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`date`					int(10)					NOT NULL DEFAULT 0,
	`hash`					varchar(25)				NOT NULL DEFAULT '',
	`data`					text,
	PRIMARY KEY				(`params_id`),
	KEY						`date` (`date`),
	KEY						`hash` (`hash`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_preferences` (
	`preference_id`			int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`preference_name`		varchar(100)			NOT NULL DEFAULT '',
	`preference_value`		varchar(100)			NOT NULL DEFAULT '',
	`site_id`				int(4) unsigned			NOT NULL DEFAULT '1',
	PRIMARY KEY (`preference_id`),
	KEY	`site_id` (`site_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_quarantine` (
	`quarantine_id`			int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`rating_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`entry_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`channel_id`			int(4) unsigned			NOT NULL DEFAULT 0,
	`member_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`rating_author_id`		int(10) unsigned		NOT NULL DEFAULT 0,
	`status`				varchar(10)				NOT NULL DEFAULT 'open',
	`entry_date`			int(10)					NOT NULL DEFAULT 0,
	`edit_date`				int(10)					NOT NULL DEFAULT 0,
	PRIMARY KEY				(`quarantine_id`),
	KEY						`rating_id` (`rating_id`),
	KEY						`entry_id` (`entry_id`),
	KEY						`channel_id` (`channel_id`),
	KEY						`member_id` (`member_id`),
	KEY						`status` (`status`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_reviews` (
	`review_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`site_id`				int(4) unsigned			NOT NULL DEFAULT 1,
	`rating_id`				int(10) unsigned 		NOT NULL DEFAULT 0,
	`entry_id`				int(10) unsigned 		NOT NULL DEFAULT 0,
	`channel_id`			int(4) unsigned 		NOT NULL DEFAULT 0,
	`author_id`				int(10) unsigned 		NOT NULL DEFAULT 0,
	`ip_address`			varchar(16)				NOT NULL DEFAULT '',
	`status`				varchar(10)				NOT NULL DEFAULT 'open',
	`name`					varchar(50)				NOT NULL DEFAULT '',
	`email`					varchar(50)				NOT NULL DEFAULT '',
	`url`					varchar(75)				NOT NULL DEFAULT '',
	`location`				varchar(50)				NOT NULL DEFAULT '',
	`rating_helpful`		char(1)					NOT NULL DEFAULT '',
	`rating_review`			text,
	`review_date`			int(20)					NULL	 DEFAULT NULL,
	PRIMARY KEY				(`review_id`),
	KEY						`rating_id` (`rating_id`),
	KEY						`entry_id` (`entry_id`),
	KEY						`channel_id` (`channel_id`),
	KEY						`author_id` (`author_id`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_stats` (
	`stat_id`				int(10) unsigned		NOT NULL AUTO_INCREMENT,
	`entry_id`				int(10) unsigned		NOT NULL DEFAULT 0,
	`channel_id`			int(4) unsigned			NOT NULL DEFAULT 0,
	`member_id`				int unsigned			NOT NULL DEFAULT 0,
	`collection`			varchar(50)				NOT NULL DEFAULT '',
	`last_rating_date`		int(10) unsigned		NOT NULL DEFAULT 0,
	`count`					int unsigned			NULL	 DEFAULT NULL,
	`sum`					int unsigned			NULL	 DEFAULT NULL,
	`avg`					float unsigned			NULL	 DEFAULT NULL,
	`count_1`				int unsigned			NULL	 DEFAULT NULL,
	`sum_1`					int unsigned			NULL	 DEFAULT NULL,
	`avg_1`					float unsigned			NULL	 DEFAULT NULL,
	`count_2`				int unsigned			NULL	 DEFAULT NULL,
	`sum_2`					int unsigned			NULL	 DEFAULT NULL,
	`avg_2`					float unsigned			NULL	 DEFAULT NULL,
	PRIMARY KEY				(`stat_id`),
	KEY						`entry_id` (`entry_id`),
	KEY						`channel_id` (`channel_id`),
	KEY						`member_id` (`member_id`),
	KEY						`count` (`count`),
	KEY						`sum` (`sum`),
	KEY						`avg` (`avg`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;

CREATE TABLE IF NOT EXISTS `exp_rating_templates` (
	`template_id`			int(6) unsigned			NOT NULL AUTO_INCREMENT,
	`enable_template`		char(1)					NOT NULL DEFAULT 'y',
	`wordwrap`				char(1)					NOT NULL DEFAULT 'y',
	`template_name`			varchar(150)			NOT NULL DEFAULT '',
	`template_label`		varchar(150)			NOT NULL DEFAULT '',
	`subject`				varchar(80)				NOT NULL DEFAULT '',
	`message`				text,
	PRIMARY KEY				(`template_id`),
	KEY						`template_name` (`template_name`)
) CHARACTER SET utf8 COLLATE utf8_general_ci ;;
