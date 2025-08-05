<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// LIST - TIERS

/**
 * Get number of patrons per tier
 * @since 0.11.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $tier_ids
 * @return object[]
 */
function patips_get_tiers_count( $tier_ids = array() ) {
	global $wpdb;
	
	$query = ' SELECT '
	       .   ' H.tier_id, '
	       .   ' COUNT( DISTINCT H.patron_id ) as all_time, '
	       .   ' COUNT( DISTINCT IF( H.date_start <= %s AND H.date_end >= %s, H.patron_id, NULL ) ) as current ' 
	       . ' FROM ' . PATIPS_TABLE_PATRONS_HISTORY . ' as H '
	       . ' LEFT JOIN ' . PATIPS_TABLE_PATRONS . ' as P ON H.patron_id = P.id '
	       . ' WHERE H.active = 1 '
	       . ' AND P.active = 1 ';
	
	$timezone  = patips_get_wp_timezone();
	$now_dt    = new DateTime( 'now', $timezone );
	$variables = array( $now_dt->format( 'Y-m-d' ), $now_dt->format( 'Y-m-d' ) );
	
	if( $tier_ids ) {
		$query .= ' AND H.tier_id IN ( %d ';
		$array_count = count( $tier_ids );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $tier_ids );
	}
	
	$query .= ' GROUP BY H.tier_id ';
	
	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$query = apply_filters( 'patips_get_tiers_count_query', $query, $variables );
	
	$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$tiers_count = array();
	if( $results ) {
		foreach( $results as $result ) {
			$tiers_count[ intval( $result->tier_id ) ] = $result;
		}
	}
	
	return $tiers_count;
}


/**
 * Get the number of tier rows according to filters
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $filters See patips_format_tier_filters()
 * @return int
 */
function patips_get_number_of_tier_rows( $filters = array() ) {
	global $wpdb;
	
	$variables = array();
	
	$query = ' SELECT COUNT( DISTINCT T.id ) ' 
	       . ' FROM ' . PATIPS_TABLE_TIERS . ' as T '
	       . ' WHERE true ';
	
	if( $filters[ 'in__id' ] ) {
		$query .= ' AND T.id IN ( %d ';
		$array_count = count( $filters[ 'in__id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__id' ] );
	}
	
	if( $filters[ 'title' ] ) {
		$query .= ' AND T.title LIKE %s ';
		$variables[] = '%' . $wpdb->esc_like( $filters[ 'title' ] ) . '%' ;
	}
	
	if( $filters[ 'user_id' ] ) {
		$query .= ' AND T.user_id = %d ';
		$variables[] = $filters[ 'user_id' ];
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND T.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$query = apply_filters( 'patips_get_tier_rows_nb_query', $query, $filters );
	
	$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $count ? intval( $count ) : 0;
}




// CRUD - TIERS

/**
 * Insert a tier
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function patips_insert_tier( $data ) {
	global $wpdb;
	
	// Get current UTC datetime
	$utc_timezone = new DateTimeZone( 'UTC' );
	$current_datetime_object = new DateTime( 'now', $utc_timezone );
	
	$query = 'INSERT INTO ' . PATIPS_TABLE_TIERS . ' ( title, description, price, icon_id, user_id, creation_date, active ) '
			. ' VALUES ( NULLIF( %s, "" ), NULLIF( %s, "" ), NULLIF( %d, 0 ), NULLIF( %d, 0 ), NULLIF( %d, 0 ), NULLIF( %s, "" ), 1 )';
	
	$variables = array(
		$data[ 'title' ] ? $data[ 'title' ] : '',
		$data[ 'description' ] ? $data[ 'description' ] : '',
		$data[ 'price' ] ? $data[ 'price' ] : 0,
		$data[ 'icon_id' ] ? $data[ 'icon_id' ] : 0,
		get_current_user_id(),
		$current_datetime_object->format( 'Y-m-d H:i:s' )
	);
	
	// Safely apply variables to the query
	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Update
	$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$tier_id = $wpdb->insert_id;
	
	return $tier_id;
}


/**
 * Update a tier
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $data
 * @return int|false
 */
function patips_update_tier( $data ) {
	global $wpdb;
	
	$query = 'UPDATE ' . PATIPS_TABLE_TIERS
	       . ' SET '
	       . ' title = NULLIF( IFNULL( NULLIF( %s, "" ), title ), "null" ), '
	       . ' description = NULLIF( IFNULL( NULLIF( %s, "" ), description ), "null" ), '
	       . ' price = NULLIF( IFNULL( NULLIF( %d, -1 ), price ), 0 ), '
	       . ' icon_id = NULLIF( IFNULL( NULLIF( %d, 0 ), icon_id ), -1 ), '
	       . ' user_id = IFNULL( NULLIF( %d, 0 ), user_id ), '
	       . ' creation_date = IFNULL( NULLIF( %s, "" ), creation_date ), '
	       . ' active = IFNULL( NULLIF( %d, -1 ), active ) '
	       . ' WHERE id = %d ';
	
	$variables = array( 
		isset( $data[ 'title' ] )         ? $data[ 'title' ] : '', 
		isset( $data[ 'description' ] )   ? $data[ 'description' ] : '', 
		isset( $data[ 'price' ] )         ? $data[ 'price' ] : -1, 
		isset( $data[ 'icon_id' ] )       ? $data[ 'icon_id' ] : 0, 
		isset( $data[ 'user_id' ] )       ? $data[ 'user_id' ] : 0, 
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
 * Deactivate a tier
 * @since 0.5.0
 * @global wpdb $wpdb
 * @param int $tier_id
 * @return int|false
 */
function patips_deactivate_tier( $tier_id ) {
	global $wpdb;
	
	$deactivated = $wpdb->update( 
		PATIPS_TABLE_TIERS, 
		array( 'active' => 0 ),
		array( 'id' => $tier_id ),
		array( '%d' ),
		array( '%d' )
	);
	
	return $deactivated;
}


/**
 * Activate a tier
 * @since 0.5.0
 * @global wpdb $wpdb
 * @param int $tier_id
 * @return int|false
 */
function patips_activate_tier( $tier_id ) {
	global $wpdb;
	
	$activated = $wpdb->update( 
		PATIPS_TABLE_TIERS, 
		array( 'active' => 1 ),
		array( 'id' => $tier_id ),
		array( '%d' ),
		array( '%d' )
	);
	
	return $activated;
}


/**
 * Delete a tier
 * @since 0.5.0
 * @global wpdb $wpdb
 * @param int $tier_id
 * @return int|false
 */
function patips_delete_tier( $tier_id ) {
	global $wpdb;
	
	$deleted = $wpdb->delete( 
		PATIPS_TABLE_TIERS,
		array( 'id' => $tier_id ),
		array( '%d' )
	);
	
	if( ! $deleted ) { return $deleted; }
	
	// Delete restricted terms 
	$wpdb->delete( 
		PATIPS_TABLE_RESTRICTED_TERMS, 
		array( 'tier_id' => $tier_id ), 
		array( '%d' ) 
	);
	
	// Delete metadata
	$wpdb->delete( 
		PATIPS_TABLE_META, 
		array( 
			'object_type' => 'tier',
			'object_id' => $tier_id
		), 
		array( '%s', '%d' ) 
	);
	
	return $deleted;
}




// CRUD - RESTRICTED TERMS

/**
 * Insert restricted terms for the desired tiers
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $restricted_term_ids
 * @param array $tiers_ids
 * @return int|false
 */
function patips_insert_tiers_restricted_terms( $restricted_term_ids, $tiers_ids ) {
	if( empty( $restricted_term_ids ) || ! is_array( $restricted_term_ids ) || empty( $tiers_ids ) || ! is_array( $tiers_ids ) ) {
		return false;
	}

	global $wpdb;

	$query = 'INSERT INTO ' . PATIPS_TABLE_RESTRICTED_TERMS . ' ( term_id, tier_id, scope ) ' . ' VALUES ';

	$i = 0;
	$variables = array();
	foreach( $tiers_ids as $tier_id ) {
		foreach( $restricted_term_ids as $scope => $term_ids ) {
			foreach( $term_ids as $term_id ) {
				if( $i > 0 ) { $query .= ','; } 
				$query .= ' ( %d, %d, %s ) ';
				$variables[] = $term_id;
				$variables[] = $tier_id;
				$variables[] = $scope;
				$i++;
			}
		}
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$inserted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $inserted;
}


/**
 * Delete restricted terms
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $restricted_term_ids
 * @param array $tiers_ids
 * @return int|false
 */
function patips_delete_tiers_restricted_terms( $restricted_term_ids, $tiers_ids = array() ) {
	global $wpdb;
	
	$query = 'DELETE FROM ' . PATIPS_TABLE_RESTRICTED_TERMS 
	       . ' WHERE ';
	
	$i = 0;
	$variables = array();
	foreach( $restricted_term_ids as $scope => $term_ids ) {
		if( $i > 0 ) { $query .= ' OR '; } 
		$query .= '( scope = %s AND term_id IN (';
		$variables[] = $scope;
		
		$j = 0;
		foreach( $term_ids as $term_id ) {
			if( $j > 0 ) { $query .= ','; } 
			$query .= ' %d';
			$variables[] = $term_id;
			$j++;
		}
		$query .= ' ) )';
		$i++;
	}
	
	if( $tiers_ids ) {
		$query .= 'AND tier_id IN (';
		for( $i = 0; $i < count( $tiers_ids ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $tiers_ids ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $tiers_ids );
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $deleted;
}