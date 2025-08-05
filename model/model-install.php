<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Create Patrons Tips database tables
 * @since 0.5.0
 * @version 0.26.0
 * @global wpdb $wpdb
 */
function patips_create_tables() {
	global $wpdb;
	$wpdb->hide_errors();
	$collate = '';
	if ( $wpdb->has_cap( 'collation' ) ) {
		$collate = $wpdb->get_charset_collate();
	}
	
	$table_tiers_query = 'CREATE TABLE ' . PATIPS_TABLE_TIERS . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		title TEXT,
		description TEXT,
		price DECIMAL(18,4),
		icon_id BIGINT UNSIGNED,
		user_id BIGINT UNSIGNED,
		creation_date DATETIME,
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ) ) ' . $collate . ';';
	
	$table_restricted_terms_query = 'CREATE TABLE ' . PATIPS_TABLE_RESTRICTED_TERMS . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		tier_id BIGINT UNSIGNED,
		term_id BIGINT UNSIGNED,
		scope VARCHAR(128) NOT NULL DEFAULT "active",
		PRIMARY KEY ( id ),
		KEY tier_id ( tier_id ),
		KEY term_id ( term_id ) ) ' . $collate . ';';
	
	$table_tiers_products_query = 'CREATE TABLE ' . PATIPS_TABLE_TIERS_PRODUCTS . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		tier_id BIGINT UNSIGNED,
		product_id BIGINT UNSIGNED,
		frequency VARCHAR(128) NOT NULL DEFAULT "one_off",
		is_default TINYINT(1) UNSIGNED, 
		PRIMARY KEY ( id ),
		KEY tier_id ( tier_id ) ) ' . $collate . ';';

	$table_patrons_query = 'CREATE TABLE ' . PATIPS_TABLE_PATRONS . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		nickname VARCHAR(128), 
		user_id BIGINT, 
		user_email VARCHAR(128), 
		creation_date DATETIME, 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ),
		KEY tier_id ( user_id ) ) ' . $collate . ';';

	$table_patrons_history_query = 'CREATE TABLE ' . PATIPS_TABLE_PATRONS_HISTORY . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		patron_id BIGINT, 
		tier_id BIGINT, 
		date_start DATE, 
		date_end DATE, 
		period_start DATE, 
		period_end DATE, 
		period_nb TINYINT UNSIGNED NOT NULL DEFAULT 1, 
		period_duration TINYINT UNSIGNED NOT NULL DEFAULT 1, 
		order_id BIGINT,
		order_item_id BIGINT,
		subscription_id BIGINT,
		subscription_plugin VARCHAR(128),
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY ( id ),
		KEY patron_id ( patron_id ),
		KEY tier_id ( tier_id ) ) ' . $collate . ';';

	$table_meta_query = 'CREATE TABLE ' . PATIPS_TABLE_META . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		object_type VARCHAR(128), 
		object_id BIGINT UNSIGNED, 
		meta_key VARCHAR(255), 
		meta_value MEDIUMTEXT,
		PRIMARY KEY ( id ),
		KEY object_type ( object_type ),
		KEY object_id ( object_id ) ) ' . $collate . ';';
	
	$table_exports_query = 'CREATE TABLE ' . PATIPS_TABLE_EXPORTS . ' ( 
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
		user_id BIGINT UNSIGNED, 
		type VARCHAR(128), 
		args TEXT, 
		creation_date DATETIME, 
		expiration_date DATETIME, 
		sequence BIGINT UNSIGNED DEFAULT 0, 
		active TINYINT(1) UNSIGNED NOT NULL DEFAULT 1, 
		PRIMARY KEY ( id ) ) ' . $collate . ';';

	// Execute the queries
	if( ! function_exists( 'dbDelta' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}
	
	dbDelta( 
		$table_tiers_query 
		. $table_restricted_terms_query 
		. $table_tiers_products_query 
		. $table_patrons_query
		. $table_patrons_history_query
		. $table_meta_query
		. $table_exports_query
	);
}


/**
 * Remove Patrons Tips tables from database
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 */
function patips_drop_tables() {
	global $wpdb;
	$wpdb->hide_errors();
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_TIERS . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_RESTRICTED_TERMS . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_TIERS_PRODUCTS . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_PATRONS . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_PATRONS_HISTORY . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_META . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( 'DROP TABLE IF EXISTS ' . PATIPS_TABLE_EXPORTS . '; ' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}