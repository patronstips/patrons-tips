<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Retrieve exports from database
 * @since 0.8.0
 * @version 0.25.5
 * @param array $raw_filters
 * @return array
 */
function patips_get_exports( $raw_filters = array() ) {
	$default_filters = array(
		'export_ids' => array(),
		'types'      => array(),
		'user_ids'   => array(),
		'expired'    => 0,       // 0 for non-expired only, 1 for expired only, false for all
		'active'     => false    // 1 for active only, 0 for inactive only, false for all
	);
	$filters = wp_parse_args( $raw_filters, $default_filters );
	
	global $wpdb;
	
	$query = 'SELECT XP.id, XP.user_id, XP.type, XP.args, XP.creation_date, XP.expiration_date, XP.sequence, XP.active '
			. ' FROM ' . PATIPS_TABLE_EXPORTS . ' as XP '
			. ' WHERE TRUE ';
	
	$variables = array();
	$exports = array();
	
	if( $filters[ 'export_ids' ] ) {
		$query .= ' AND XP.id IN ( %d ';
		$array_count = count( $filters[ 'export_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'export_ids' ] );
	}
	
	if( $filters[ 'types' ] ) {
		$query .= ' AND XP.types IN ( %s ';
		$array_count = count( $filters[ 'types' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'types' ] );
	}
	
	if( $filters[ 'user_ids' ] ) {
		$query .= ' AND XP.user_id IN ( %d ';
		$array_count = count( $filters[ 'user_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'user_ids' ] );
	}
	
	if( $filters[ 'expired' ] !== false ) {
		if( $filters[ 'expired' ] ) {
			$query .= ' AND XP.expiration_date <= %s ';
		} else {
			$query .= ' AND XP.expiration_date > %s ';
		}
		
		$timezone_obj = patips_get_wp_timezone();
		$now_dt = new DateTime( 'now', $timezone_obj );
		$variables[] = $now_dt->format( 'Y-m-d H:i:s' );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND XP.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	if( ! $results ) { return $exports; }
	
	// Index by ID
	foreach( $results as $result ) {
		$exports[ $result[ 'id' ] ] = array();
		foreach( $result as $key => $value ) {
			$exports[ $result[ 'id' ] ][ $key ] = maybe_unserialize( $value );
		}
	}
	
	return $exports;
}


/**
 * Get an export by ID
 * @since 0.8.0
 * @param int $export_id
 * @return array|false
 */
function patips_get_export( $export_id ) {
	$filters = array( 'export_ids' => array( $export_id ) );
	$exports = patips_get_exports( $filters );
	return isset( $exports[ $export_id ] ) ? $exports[ $export_id ] : false;
}


/**
 * Insert a new export
 * @since 0.8.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $raw_args
 * @return int|false
 */
function patips_insert_export( $raw_args = array() ) {
	$expiration_delay = patips_get_export_expiration_delay();
	$timezone_obj     = patips_get_wp_timezone();
	$expiration_dt    = new DateTime( 'now', $timezone_obj );
	$expiration_dt->add( new DateInterval( 'P' . $expiration_delay . 'D' ) );
	
	$default_args = array(
		'type'            => '',
		'user_id'         => 0,
		'creation_date'   => gmdate( 'Y-m-d H:i:s' ),
		'expiration_date' => $expiration_dt->format( 'Y-m-d H:i:s' ),
		'sequence'        => 0,
		'args'            => array(),
		'active'          => 1
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	$query = 'INSERT INTO ' . PATIPS_TABLE_EXPORTS 
	       . ' ( user_id, type, args, creation_date, expiration_date, sequence, active ) ' 
	       . ' VALUES ( NULLIF( %d, 0 ), %s, %s, %s, NULLIF( %s, "" ), %d, %d )';
	
	$variables = array(
		$args[ 'user_id' ],
		$args[ 'type' ],
		maybe_serialize( $args[ 'args' ] ),
		$args[ 'creation_date' ],
		$args[ 'expiration_date' ],
		$args[ 'sequence' ],
		$args[ 'active' ]
	);
	
	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$inserted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	if( ! $inserted ) { return false; }
	
	$export_id = $wpdb->insert_id;
	
	return $export_id;
}


/**
 * Update an export
 * @since 0.8.0
 * @version 0.25.5
 * @param array $args
 * @return int|false
 */
function patips_update_export( $export_id, $raw_args = array() ) {
	$expiration_delay = patips_get_export_expiration_delay();
	$timezone_obj     = patips_get_wp_timezone();
	$expiration_dt    = new DateTime( 'now', $timezone_obj );
	$expiration_dt->add( new DateInterval( 'P' . $expiration_delay . 'D' ) );
	
	$default_args = array(
		'type'            => false,
		'user_id'         => false,
		'creation_date'   => false,
		'expiration_date' => $expiration_dt->format( 'Y-m-d H:i:s' ),
		'sequence_inc'    => 1,
		'args'            => false,
		'active'          => false
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	global $wpdb;
	
	$query = 'UPDATE ' . PATIPS_TABLE_EXPORTS . ' SET ';
	
	$variables = array();
	
	if( $args[ 'type' ] !== false )            { $query .= ' type = %s, '; $variables[] = $args[ 'type' ]; }
	if( $args[ 'user_id' ] !== false )         { $query .= ' user_id = %d, '; $variables[] = $args[ 'user_id' ]; }
	if( $args[ 'creation_date' ] !== false )   { $query .= ' creation_date = %s, '; $variables[] = $args[ 'creation_date' ]; }
	if( $args[ 'expiration_date' ] !== false ) { $query .= ' expiration_date = %s, '; $variables[] = $args[ 'expiration_date' ]; }
	if( $args[ 'sequence_inc' ] !== false )    { $query .= ' sequence = sequence + %d, '; $variables[] = intval( $args[ 'sequence_inc' ] ); }
	if( $args[ 'args' ] !== false )            { $query .= ' args = %s, '; $variables[] = maybe_serialize( $args[ 'args' ] ); }
	if( $args[ 'active' ] !== false )          { $query .= ' active = %d, '; $variables[] = $args[ 'active' ]; }
	
	if( ! $variables ) { return 0; }
	
	// Remove trailing comma
	$query = rtrim( $query, ', ' );
	
	$query .= ' WHERE id = %d ';
	$variables[] = $export_id;

	$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$updated = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	return $updated;
}


/**
 * Delete exports by ID
 * @since 0.8.0
 * @global wpdb $wpdb
 * @param array $export_ids
 * @return int|false
 */
function patips_delete_exports( $export_ids ) {
	if( ! $export_ids ) { return false; }
	
	global $wpdb;
		
	// Delete exports
	$query = 'DELETE FROM ' . PATIPS_TABLE_EXPORTS . ' as XP '
	       . ' WHERE XP.id IN ( %d ';
	
	$array_count = count( $export_ids );
	if( $array_count >= 2 ) {
		for( $i=1; $i<$array_count; ++$i ) {
			$query .= ', %d ';
		}
	}
	$query .= ') ';

	$query   = $wpdb->prepare( $query, $export_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	// Delete meta
	patips_delete_metadata( 'export', $export_ids );

	return $deleted;
}