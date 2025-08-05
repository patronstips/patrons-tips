<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get patron by id
 * @since 0.6.0
 * @version 0.10.0
 * @param int $patron_id
 * @return object|false
 */
function patips_get_patron( $patron_id ) {
	$fitlers = patips_format_patron_filters( array( 'in__id' => array( $patron_id ) ) );
	$patrons = patips_get_patrons( $fitlers );
	return isset( $patrons[ $patron_id ] ) ? $patrons[ $patron_id ] : false;
}


/**
 * Get patrons according to filters
 * @since 0.6.0
 * @version 1.0.4
 * @global wpdb $wpdb
 * @param array $filters See patips_format_patron_filters()
 * @return object[]
 */
function patips_get_patrons( $filters = array() ) {
	global $wpdb;
	
	$timezone = patips_get_wp_timezone();
	$now_dt = new DateTime( 'now', $timezone );
	
	$variables = array();
	
	$query = ' SELECT DISTINCT P.id, P.nickname, P.user_id, P.user_email, P.creation_date, P.active '
	       . ' FROM ' . PATIPS_TABLE_PATRONS . ' as P '
	       . ' LEFT JOIN ' . $wpdb->users . ' as U ON P.user_id = U.ID ' 
	       . ' LEFT JOIN ' . PATIPS_TABLE_PATRONS_HISTORY . ' as H ON P.id = H.patron_id AND H.active = 1 ';
	
	$query .= ' WHERE TRUE ';
	
	if( $filters[ 'in__id' ] ) {
		$query .= ' AND P.id IN ( %d ';
		$array_count = count( $filters[ 'in__id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__id' ] );
	}
	
	if( $filters[ 'user' ] ) {
		if( is_numeric( $filters[ 'user' ] ) ) {
			if( ! in_array( intval( $filters[ 'user' ] ), $filters[ 'user_ids' ], true ) ) {
				$filters[ 'user_ids' ][] = intval( $filters[ 'user' ] );
			}
		} else {
			$query .= ' AND ( P.user_email LIKE %s OR U.user_email LIKE %s OR U.user_login LIKE %s OR U.display_name LIKE %s )';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
		}
	}
	
	if( $filters[ 'user_ids' ] ) {
		$query .= ' AND P.user_id IN (';
		for( $i = 0; $i < count( $filters[ 'user_ids' ] ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $filters[ 'user_ids' ] ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'user_ids' ] );
	}
	
	if( $filters[ 'user_emails' ] ) {
		$query .= ' AND P.user_email IN (';
		for( $i = 0; $i < count( $filters[ 'user_emails' ] ); $i++ ) {
			$query .= ' %s';
			if( $i < ( count( $filters[ 'user_emails' ] ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'user_emails' ] );
	}
	
	if( $filters[ 'tier_ids' ] ) {
		$query .= ' AND H.tier_id IN (';
		for( $i = 0; $i < count( $filters[ 'tier_ids' ] ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $filters[ 'tier_ids' ] ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'tier_ids' ] );
	}
	
	if( $filters[ 'no_account' ] !== false ) {
		if( $filters[ 'no_account' ] ) {
			$query .= ' AND ( P.user_id IS NULL OR P.user_id = 0 ) ';
		} else {
			$query .= ' AND P.user_id IS NOT NULL AND P.user_id != 0 ';
		}
	}
	
	if( $filters[ 'created_from' ] ) {
		$query .= ' AND P.creation_date >= %s ';
		$variables[] = $filters[ 'created_from' ];
	}
	
	if( $filters[ 'created_to' ] ) {
		$query .= ' AND P.creation_date <= %s ';
		$variables[] = $filters[ 'created_to' ];
	}
	
	if( ( $filters[ 'period_from' ] || $filters[ 'period_to' ] ) && ! $filters[ 'period_duration' ] ) {
		$filters[ 'period_duration' ] = patips_get_default_period_duration();
	}
	
	if( $filters[ 'period_from' ] ) {
		$query .= ' AND H.period_start >= %s';
		$first_period = patips_get_period_by_date( $filters[ 'period_from' ], $filters[ 'period_duration' ] );
		$variables[]  = substr( $first_period[ 'start' ], 0, 10 );
	} 

	if( $filters[ 'period_to' ] ) {
		$query .= ' AND H.period_end <= %s';
		$last_period = patips_get_period_by_date( $filters[ 'period_to' ], $filters[ 'period_duration' ] );
		$variables[] = substr( $last_period[ 'end' ], 0, 10 );
	}
	
	if( $filters[ 'period_duration' ] ) {
		$query .= ' AND H.period_duration = %d ';
		$variables[] = $filters[ 'period_duration' ];
	}
	
	if( $filters[ 'from' ] ) {
		$query .= ' AND H.date_start >= %s ';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND H.date_start <= %s ';
		$variables[] = $filters[ 'to' ];
	}
	
	if( $filters[ 'end_from' ] ) {
		$query .= ' AND H.date_end >= %s ';
		$variables[] = $filters[ 'end_from' ];
	}
	
	if( $filters[ 'end_to' ] ) {
		$query .= ' AND H.date_end <= %s ';
		$variables[] = $filters[ 'end_to' ];
	}
	
	if( $filters[ 'date' ] ) {
		$query .= ' AND H.date_start <= %s AND H.date_end >= %s ';
		$variables[] = $filters[ 'date' ];
		$variables[] = $filters[ 'date' ];
	}
	
	if( $filters[ 'current' ] ) {
		$query .= ' AND H.date_start <= %s AND H.date_end >= %s';
		$variables[] = $now_dt->format( 'Y-m-d' );
		$variables[] = $now_dt->format( 'Y-m-d' );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND P.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	$query .= ' GROUP BY P.id ';
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$query .= $filters[ 'order_by' ][ $i ];
			if( $filters[ 'order' ] ) { $query .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$query .= '%d';
			if( $filters[ 'per_page' ] ) { $query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) { 
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$query = apply_filters( 'patips_get_patrons_query', $query, $filters );
	
	$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$patrons = array();
	if( $results ) {
		foreach( $results as $result ) {
			$patrons[ intval( $result->id ) ] = $result;
		}
	}
		
	return $patrons;
}


/**
 * Get the number of patron rows according to filters
 * @since 0.6.0
 * @version 1.0.4
 * @global wpdb $wpdb
 * @param array $filters See patips_format_patron_filters()
 * @return int
 */
function patips_get_number_of_patron_rows( $filters = array() ) {
	global $wpdb;
	
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	$variables = array();
	
	$query = ' SELECT COUNT( DISTINCT P.id ) as list_items_count '
	       . ' FROM ' . PATIPS_TABLE_PATRONS . ' as P '
	       . ' LEFT JOIN ' . $wpdb->users . ' as U ON P.user_id = U.ID ' 
	       . ' LEFT JOIN ' . PATIPS_TABLE_PATRONS_HISTORY . ' as H ON P.id = H.patron_id AND H.active = 1 ';
	
	$query .= ' WHERE TRUE ';
	
	if( $filters[ 'in__id' ] ) {
		$query .= ' AND P.id IN ( %d ';
		$array_count = count( $filters[ 'in__id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d ';
			}
		}
		$query .= ') ';
		$variables = array_merge( $variables, $filters[ 'in__id' ] );
	}
	
	if( $filters[ 'user' ] ) {
		if( is_numeric( $filters[ 'user' ] ) ) {
			if( ! in_array( intval( $filters[ 'user' ] ), $filters[ 'user_ids' ], true ) ) {
				$filters[ 'user_ids' ][] = intval( $filters[ 'user' ] );
			}
		} else {
			$query .= ' AND ( P.user_email LIKE %s OR U.user_email LIKE %s OR U.user_login LIKE %s OR U.display_name LIKE %s )';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
			$variables[] = '%' . $wpdb->esc_like( $filters[ 'user' ] ) . '%';
		}
	}
	
	if( $filters[ 'user_ids' ] ) {
		$query .= ' AND P.user_id IN (';
		for( $i = 0; $i < count( $filters[ 'user_ids' ] ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $filters[ 'user_ids' ] ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'user_ids' ] );
	}
	
	if( $filters[ 'user_emails' ] ) {
		$query .= ' AND P.user_email IN (';
		for( $i = 0; $i < count( $filters[ 'user_emails' ] ); $i++ ) {
			$query .= ' %s';
			if( $i < ( count( $filters[ 'user_emails' ] ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'user_emails' ] );
	}
	
	if( $filters[ 'tier_ids' ] ) {
		$query .= ' AND H.tier_id IN (';
		for( $i = 0; $i < count( $filters[ 'tier_ids' ] ); $i++ ) {
			$query .= ' %d';
			if( $i < ( count( $filters[ 'tier_ids' ] ) - 1 ) ) {
				$query .= ',';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'tier_ids' ] );
	}
	
	if( $filters[ 'no_account' ] !== false ) {
		if( $filters[ 'no_account' ] ) {
			$query .= ' AND ( P.user_id IS NULL OR P.user_id = 0 ) ';
		} else {
			$query .= ' AND P.user_id IS NOT NULL AND P.user_id != 0 ';
		}
	}
	
	if( $filters[ 'created_from' ] ) {
		$query .= ' AND P.creation_date >= %s ';
		$variables[] = $filters[ 'created_from' ];
	}
	
	if( $filters[ 'created_to' ] ) {
		$query .= ' AND P.creation_date <= %s ';
		$variables[] = $filters[ 'created_to' ];
	}
	
	if( ( $filters[ 'period_from' ] || $filters[ 'period_to' ] ) && ! $filters[ 'period_duration' ] ) {
		$filters[ 'period_duration' ] = patips_get_default_period_duration();
	}
	
	if( $filters[ 'period_from' ] ) {
		$query .= ' AND H.period_start >= %s';
		$first_period = patips_get_period_by_date( $filters[ 'period_from' ], $filters[ 'period_duration' ] );
		$variables[]  = substr( $first_period[ 'start' ], 0, 10 );
	} 

	if( $filters[ 'period_to' ] ) {
		$query .= ' AND H.period_end <= %s';
		$last_period = patips_get_period_by_date( $filters[ 'period_to' ], $filters[ 'period_duration' ] );
		$variables[] = substr( $last_period[ 'end' ], 0, 10 );
	}
	
	if( $filters[ 'period_duration' ] ) {
		$query .= ' AND H.period_duration = %d';
		$variables[] = $filters[ 'period_duration' ];
	}
	
	if( $filters[ 'from' ] ) {
		$query .= ' AND H.date_start >= %s';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND H.date_start <= %s';
		$variables[] = $filters[ 'to' ];
	}
	
	if( $filters[ 'end_from' ] ) {
		$query .= ' AND H.date_end >= %s ';
		$variables[] = $filters[ 'end_from' ];
	}
	
	if( $filters[ 'end_to' ] ) {
		$query .= ' AND H.date_end <= %s ';
		$variables[] = $filters[ 'end_to' ];
	}
	
	if( $filters[ 'date' ] ) {
		$query .= ' AND H.date_start <= %s AND H.date_end >= %s';
		$variables[] = $filters[ 'date' ];
		$variables[] = $filters[ 'date' ];
	}
	
	if( $filters[ 'current' ] ) {
		$query .= ' AND H.date_start <= %s AND H.date_end >= %s';
		$variables[] = $now_dt->format( 'Y-m-d' );
		$variables[] = $now_dt->format( 'Y-m-d' );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND P.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$query = apply_filters( 'patips_get_patron_rows_nb_query', $query, $filters );
	
	$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return $count ? intval( $count ) : 0;
}




// PATRON HISTORY

/**
 * Get patrons history
 * @since 0.10.0 (was patips_get_patrons_tiers)
 * @version 1.0.4
 * @global wpdb $wpdb
 * @param array $filters See patips_format_patron_history_filters()
 * @return array
 */
function patips_get_patrons_history( $filters = array() ) {
	global $wpdb;
	
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	$query = 'SELECT H.id, H.patron_id, H.tier_id, H.date_start, H.date_end, H.period_start, H.period_end, H.period_nb, H.period_duration, H.order_id, H.order_item_id, H.subscription_id, H.subscription_plugin, H.active '
	       . ' FROM ' . PATIPS_TABLE_PATRONS_HISTORY . ' as H '
	       . ' WHERE TRUE ';
	
	$variables = array();
	
	if( $filters[ 'patron_ids' ] ) {
		$query .= ' AND H.patron_id IN ( %d';
		for( $i=1,$len=count( $filters[ 'patron_ids' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'patron_ids' ] );
	}
	
	if( $filters[ 'tier_ids' ] ) {
		$query .= ' AND H.tier_id IN ( %d';
		for( $i=1,$len=count( $filters[ 'tier_ids' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'tier_ids' ] );
	}
	
	if( $filters[ 'order_ids' ] ) {
		$query .= ' AND H.order_id IN ( %d';
		for( $i=1,$len=count( $filters[ 'order_ids' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'order_ids' ] );
	}
	
	if( $filters[ 'order_item_ids' ] ) {
		$query .= ' AND H.order_item_id IN ( %d';
		for( $i=1,$len=count( $filters[ 'order_item_ids' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'order_item_ids' ] );
	}
	
	if( $filters[ 'subscription_ids' ] ) {
		$query .= ' AND H.subscription_id IN ( %d';
		for( $i=1,$len=count( $filters[ 'subscription_ids' ] ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $filters[ 'subscription_ids' ] );
	}
	
	if( $filters[ 'subscription_plugin' ] ) {
		$query .= ' AND H.subscription_plugin = %s ';
		$variables[] = $filters[ 'subscription_plugin' ];
	}
	
	if( ( $filters[ 'period_from' ] || $filters[ 'period_to' ] ) && ! $filters[ 'period_duration' ] ) {
		$filters[ 'period_duration' ] = patips_get_default_period_duration();
	}
	
	if( $filters[ 'period_from' ] ) {
		$query .= ' AND H.period_start >= %s';
		$first_period = patips_get_period_by_date( $filters[ 'period_from' ], $filters[ 'period_duration' ] );
		$variables[]  = substr( $first_period[ 'start' ], 0, 10 );
	} 

	if( $filters[ 'period_to' ] ) {
		$query .= ' AND H.period_end <= %s';
		$last_period = patips_get_period_by_date( $filters[ 'period_to' ], $filters[ 'period_duration' ] );
		$variables[] = substr( $last_period[ 'end' ], 0, 10 );
	}
	
	if( $filters[ 'period_duration' ] ) {
		$query .= ' AND H.period_duration = %d ';
		$variables[] = $filters[ 'period_duration' ];
	}
	
	if( $filters[ 'from' ] ) {
		$query .= ' AND H.date_start >= %s ';
		$variables[] = $filters[ 'from' ];
	}
	
	if( $filters[ 'to' ] ) {
		$query .= ' AND H.date_start <= %s ';
		$variables[] = $filters[ 'to' ];
	}
	
	if( $filters[ 'end_from' ] ) {
		$query .= ' AND H.date_end >= %s ';
		$variables[] = $filters[ 'end_from' ];
	}
	
	if( $filters[ 'end_to' ] ) {
		$query .= ' AND H.date_end <= %s ';
		$variables[] = $filters[ 'end_to' ];
	}
	
	if( $filters[ 'date' ] ) {
		$query .= ' AND H.date_start <= %s AND H.date_end >= %s ';
		$variables[] = $filters[ 'date' ];
		$variables[] = $filters[ 'date' ];
	}
	
	if( $filters[ 'current' ] ) {
		$query .= ' AND H.date_start <= %s AND H.date_end >= %s';
		$variables[] = $now_dt->format( 'Y-m-d' );
		$variables[] = $now_dt->format( 'Y-m-d' );
	}
	
	if( $filters[ 'active' ] !== false ) {
		$query .= ' AND H.active = %d ';
		$variables[] = $filters[ 'active' ];
	}
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			$query .= $filters[ 'order_by' ][ $i ];
			if( $filters[ 'order' ] ) { $query .= ' ' . $filters[ 'order' ]; }
			if( $i < $len-1 ) { $query .= ', '; }
		}
	}
	
	if( $filters[ 'offset' ] || $filters[ 'per_page' ] ) {
		$query .= ' LIMIT ';
		if( $filters[ 'offset' ] ) {
			$query .= '%d';
			if( $filters[ 'per_page' ] ) { $query .= ', '; }
			$variables[] = $filters[ 'offset' ];
		}
		if( $filters[ 'per_page' ] ) { 
			$query .= '%d ';
			$variables[] = $filters[ 'per_page' ];
		}
	}
	
	if( $variables ) { 
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$query = apply_filters( 'patips_get_patrons_history_query', $query, $filters );
	
	$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Sort per patron
	$return_array = array();
	if( $results ) {
		foreach( $results as $result ) {
			$patron_id = intval( $result[ 'patron_id' ] );
			if( ! isset( $return_array[ $patron_id ] ) ) { $return_array[ $patron_id ] = array(); }
			$return_array[ $patron_id ][ intval( $result[ 'id' ] ) ] = $result;
		}
	}
	
	return $return_array;
}