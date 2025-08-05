<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// CRUD - PATRONS

/**
 * Insert a patron
 * @since 0.6.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function patips_insert_patron( $data ) {
	global $wpdb;
	
	// Get current UTC datetime
	$utc_timezone = new DateTimeZone( 'UTC' );
	$current_datetime_object = new DateTime( 'now', $utc_timezone );
	
	$query = 'INSERT INTO ' . PATIPS_TABLE_PATRONS . ' ( nickname, user_id, user_email, creation_date, active ) '
			. ' VALUES ( NULLIF( %s, "" ), NULLIF( %d, 0 ), NULLIF( %s, "" ), NULLIF( %s, "" ), %d )';
	
	$variables = array(
		isset( $data[ 'nickname' ] )   ? $data[ 'nickname' ] : '',
		isset( $data[ 'user_id' ] )    ? $data[ 'user_id' ] : 0,
		isset( $data[ 'user_email' ] ) ? $data[ 'user_email' ] : '',
		$current_datetime_object->format( 'Y-m-d H:i:s' ),
		1
	);
	
	// Safely apply variables to the query
	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Insert
	$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$patron_id = $wpdb->insert_id;
	
	return $patron_id;
}


/**
 * Update a patron
 * @since 0.6.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function patips_update_patron( $data ) {
	global $wpdb;
	
	$query = 'UPDATE ' . PATIPS_TABLE_PATRONS
	       . ' SET '
	       . ' nickname = NULLIF( IFNULL( NULLIF( %s, "" ), nickname ), "null" ), '
	       . ' user_id = NULLIF( IFNULL( NULLIF( %d, 0 ), user_id ), -1 ), '
	       . ' user_email = NULLIF( IFNULL( NULLIF( %s, "" ), user_email ), "null" ), '
	       . ' creation_date = IFNULL( NULLIF( %s, "" ), creation_date ), '
	       . ' active = IFNULL( NULLIF( %d, -1 ), active ) '
	       . ' WHERE id = %d ';
	
	$variables = array(
		isset( $data[ 'nickname' ] )      ? $data[ 'nickname' ] : '',
		isset( $data[ 'user_id' ] )       ? $data[ 'user_id' ] : 0, 
		isset( $data[ 'user_email' ] )    ? $data[ 'user_email' ] : '', 
		isset( $data[ 'creation_date' ] ) ? $data[ 'creation_date' ] : '', 
		isset( $data[ 'active' ] )        ? $data[ 'active' ] : -1, 
		isset( $data[ 'id' ] )            ? $data[ 'id' ] : 0
	);
	
	// Safely apply variables to the query
	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Update
	$updated = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $updated;
}


/**
 * Anonymize patrons
 * @since 0.25.3
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $email
 * @return int|false
 */
function patips_anonymize_patrons( $email ) {
	global $wpdb;
	
	$query = 'UPDATE ' . PATIPS_TABLE_PATRONS
	       . ' SET '
	       . ' nickname = NULL, '
	       . ' user_email = NULL '
	       . ' WHERE user_email = %s ';
	
	// Safely apply variables to the query
	$query = $wpdb->prepare( $query, $email ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Update
	$updated = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $updated;
}


/**
 * Deactivate a patron
 * @since 0.6.0
 * @global wpdb $wpdb
 * @param int $patron_id
 * @return int|false
 */
function patips_deactivate_patron( $patron_id ) {
	global $wpdb;
	
	$deactivated = $wpdb->update( 
		PATIPS_TABLE_PATRONS, 
		array( 'active' => 0 ),
		array( 'id' => $patron_id ),
		array( '%d' ),
		array( '%d' )
	);
	
	return $deactivated;
}


/**
 * Activate a patron
 * @since 0.6.0
 * @global wpdb $wpdb
 * @param int $patron_id
 * @return int|false
 */
function patips_activate_patron( $patron_id ) {
	global $wpdb;
	
	$activated = $wpdb->update( 
		PATIPS_TABLE_PATRONS, 
		array( 'active' => 1 ),
		array( 'id' => $patron_id ),
		array( '%d' ),
		array( '%d' )
	);
	
	return $activated;
}


/**
 * Delete a patron
 * @since 0.6.0
 * @version 0.13.4
 * @global wpdb $wpdb
 * @param int $patron_id
 * @return int|false
 */
function patips_delete_patron( $patron_id ) {
	global $wpdb;
	
	$deleted = $wpdb->delete( 
		PATIPS_TABLE_PATRONS,
		array( 'id' => $patron_id ),
		array( '%d' )
	);
	
	if( ! $deleted ) { return $deleted; }
	
	// Delete metadata
	$wpdb->delete( 
		PATIPS_TABLE_META, 
		array( 
			'object_type' => 'patron',
			'object_id' => $patron_id
		), 
		array( '%s', '%d' ) 
	);
	
	// Delete patron's history
	patips_delete_patron_history( $patron_id );
	
	return $deleted;
}




// CRUD - PATRON HISTORY

/**
 * Insert new entries in patron history
 * @since 0.10.0 (was patips_insert_patron_tiers)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param int $patron_id
 * @param array $history_entries
 * @return int|false
 */
function patips_insert_patron_history( $patron_id, $history_entries ) {
	if( empty( $history_entries ) || ! is_array( $history_entries ) || ! $patron_id ) {
		return false;
	}

	global $wpdb;

	$query = 'INSERT INTO ' . PATIPS_TABLE_PATRONS_HISTORY 
	       . ' ( patron_id, tier_id, date_start, date_end, period_start, period_end, period_nb, period_duration, order_id, order_item_id, subscription_id, subscription_plugin, active ) '
	       . ' VALUES ';

	$i = 0;
	$variables = array();
	foreach( $history_entries as $history_entry ) {
		if( $i > 0 ) { $query .= ','; } 
		$query .= ' ( %d, %d, %s, %s, %s, %s, %d, %d, %d, %d, %d, %s, %d ) ';
		$variables[] = $patron_id;
		$variables[] = $history_entry[ 'tier_id' ];
		$variables[] = $history_entry[ 'date_start' ];
		$variables[] = $history_entry[ 'date_end' ];
		$variables[] = $history_entry[ 'period_start' ];
		$variables[] = $history_entry[ 'period_end' ];
		$variables[] = $history_entry[ 'period_nb' ];
		$variables[] = $history_entry[ 'period_duration' ];
		$variables[] = $history_entry[ 'order_id' ];
		$variables[] = $history_entry[ 'order_item_id' ];
		$variables[] = $history_entry[ 'subscription_id' ];
		$variables[] = $history_entry[ 'subscription_plugin' ];
		$variables[] = $history_entry[ 'active' ];
		$i++;
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$inserted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $inserted;
}


/**
 * Update existing patron history entries
 * @since 0.10.0 (was patips_update_existing_patron_tiers)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param int $patron_id
 * @param array $history_entries
 * @return int
 */
function patips_update_existing_patron_history( $patron_id, $history_entries ) {
	if( empty( $history_entries ) || ! is_array( $history_entries ) || ! $patron_id ) {
		return false;
	}

	global $wpdb;
	
	$updated = 0;
	foreach( $history_entries as $history_entry ) {
		$query = 'UPDATE ' . PATIPS_TABLE_PATRONS_HISTORY
		       . ' SET '
		       . ' tier_id = NULLIF( IFNULL( NULLIF( %d, 0 ), tier_id ), -1 ), '
		       . ' date_start = NULLIF( IFNULL( NULLIF( %s, "" ), date_start ), "null" ), '
		       . ' date_end = NULLIF( IFNULL( NULLIF( %s, "" ), date_end ), "null" ), '
		       . ' period_start = NULLIF( IFNULL( NULLIF( %s, "" ), period_start ), "null" ), '
		       . ' period_end = NULLIF( IFNULL( NULLIF( %s, "" ), period_end ), "null" ), '
		       . ' period_nb = IFNULL( NULLIF( %s, 0 ), period_nb ), '
		       . ' period_duration = IFNULL( NULLIF( %s, 0 ), period_duration ), '
		       . ' order_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_id ), -1 ), '
		       . ' order_item_id = NULLIF( IFNULL( NULLIF( %d, 0 ), order_item_id ), -1 ), '
		       . ' subscription_id = NULLIF( IFNULL( NULLIF( %d, 0 ), subscription_id ), -1 ), '
		       . ' subscription_plugin = NULLIF( IFNULL( NULLIF( %s, "" ), subscription_plugin ), "null" ), '
		       . ' active = IFNULL( NULLIF( %d, -1 ), active ) '
		       . ' WHERE patron_id = %d '
		       . ' AND id = %d ';
		
		$variables = array( 
			$history_entry[ 'tier_id' ], 
			$history_entry[ 'date_start' ], 
			$history_entry[ 'date_end' ], 
			$history_entry[ 'period_start' ], 
			$history_entry[ 'period_end' ], 
			$history_entry[ 'period_nb' ], 
			$history_entry[ 'period_duration' ], 
			$history_entry[ 'order_id' ], 
			$history_entry[ 'order_item_id' ], 
			$history_entry[ 'subscription_id' ], 
			$history_entry[ 'subscription_plugin' ], 
			$history_entry[ 'active' ], 
			$patron_id, 
			$history_entry[ 'id' ] 
		);
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$updated += (int) $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	return $updated;
}


/**
 * Delete patron history entries
 * @since 0.10.0 (was patips_delete_patron_tiers)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param int $patron_id
 * @param array $entry_ids
 * @param boolean $keep_synced
 * @return int|false
 */
function patips_delete_patron_history( $patron_id = 0, $entry_ids = array(), $keep_synced = false ) {
	if( ! $patron_id && ! $entry_ids ) { return false; }	

	// Delete meta first
	patips_delete_patron_history_meta( $patron_id, $entry_ids, array(), $keep_synced );
	
	global $wpdb;
	
	$query = 'DELETE FROM ' . PATIPS_TABLE_PATRONS_HISTORY
	       . ' WHERE TRUE ';
	
	$variables = array();
	
	if( $patron_id ) {
		$query .= ' AND patron_id = %d ';
		$variables[] = $patron_id;
	}
	
	if( $entry_ids ) {
		$query .= 'AND id IN (';
		for( $i = 0; $i < count( $entry_ids ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $entry_ids ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $entry_ids );
	}
	
	if( $keep_synced ) {
		$query .= 'AND ( order_id IS NULL OR order_id = 0 ) '
		        . 'AND ( subscription_id IS NULL OR subscription_id = 0 ) ';
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $deleted;
}


/**
 * Delete patron history entries meta
 * @since 0.13.3
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param int $patron_id
 * @param array $entry_ids
 * @param array $meta_keys
 * @param boolean $keep_synced
 * @return int|false
 */
function patips_delete_patron_history_meta( $patron_id = 0, $entry_ids = array(), $meta_keys = array(), $keep_synced = false ) {
	if( ! $patron_id && ! $entry_ids ) { return false; }
	
	global $wpdb;
	
	$query = 'DELETE M FROM ' . PATIPS_TABLE_META . ' as M '
	       . ' JOIN ' . PATIPS_TABLE_PATRONS_HISTORY . ' as H ON M.object_type = "patron_history" AND M.object_id = H.id '
	       . ' WHERE M.object_type = "patron_history" ';
	
	$variables = array();
	
	if( $patron_id ) {
		$query .= ' AND H.patron_id = %d ';
		$variables[] = $patron_id;
	}
	
	if( $entry_ids ) {
		$query .= 'AND M.object_id IN (';
		for( $i = 0; $i < count( $entry_ids ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $entry_ids ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $entry_ids );
	}
	
	if( $meta_keys ) {
		$query .= 'AND M.meta_key IN (';
		for( $i = 0; $i < count( $meta_keys ); $i++ ) {
			$query .= ' %s';
			if( $i < ( count( $meta_keys ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $meta_keys );
	}
	
	if( $keep_synced ) {
		$query .= 'AND ( H.order_id IS NULL OR H.order_id = 0 ) '
		        . 'AND ( H.subscription_id IS NULL OR H.subscription_id = 0 ) ';
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $deleted;
}