<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get tiers according to filters
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $filters See patips_format_tier_filters()
 * @return object[]
 */
function patips_get_tiers( $filters = array() ) {
	global $wpdb;
	
	$query = ' SELECT T.id, T.title, T.description, T.price, T.icon_id, T.user_id, T.creation_date, T.active ' 
	       . ' FROM ' . PATIPS_TABLE_TIERS . ' as T '
	       . ' WHERE TRUE ';
	
	$variables = array();
	
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
	
	if( $filters[ 'order_by' ] ) {
		$query .= ' ORDER BY ';
		for( $i=0,$len=count($filters[ 'order_by' ]); $i<$len; ++$i ) {
			// Make NULL price last when order by price
			if( strtoupper( $filters[ 'order' ] ) === 'ASC' && $filters[ 'order_by' ][ $i ] === 'price' ) { 
				$query .= ' ISNULL( ' . $filters[ 'order_by' ][ $i ] . ' ) ' . $filters[ 'order' ] . ', ';
			}
			
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
	
	$query = apply_filters( 'patips_get_tiers_query', $query, $filters );
	
	$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$tiers = array();
	if( $results ) {
		foreach( $results as $result ) {
			$tiers[ intval( $result->id ) ] = $result;
		}
	}
	
	return $tiers;
}




// RESTRICTED TERMS

/**
 * Get tiers restricted terms ids
 * @since 0.5.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $tier_ids
 * @param array $scopes
 * @return array
 */
function patips_get_tiers_restricted_term_ids( $tier_ids = array(), $scopes = array() ) {
	global $wpdb;
	
	if( ! $scopes ) {
		$scopes = patips_get_tier_restricted_term_scopes();
	}
	
	$query = 'SELECT RT.tier_id, RT.term_id, RT.scope FROM ' . PATIPS_TABLE_RESTRICTED_TERMS . ' as RT '
	       . ' WHERE TRUE ';
	
	$variables = array();
	
	if( $tier_ids ) {
		$query .= ' AND RT.tier_id IN ( %d';
		for( $i=1,$len=count( $tier_ids ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $tier_ids );
	}
	
	if( $scopes ) {
		$query .= ' AND RT.scope IN ( %s';
		for( $i=1,$len=count( $scopes ); $i < $len; ++$i ) {
			$query .= ', %s';
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $scopes );
	}
	
	if( $variables ) { 
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	// Organize term ids per tier per scope
	$return_array = array();
	if( $results ) {
		foreach( $results as $result ) {
			$tier_id = intval( $result->tier_id );
			if( ! isset( $return_array[ $tier_id ] ) )                   { $return_array[ $tier_id ] = array(); }
			if( ! isset( $return_array[ $tier_id ][ $result->scope ] ) ) { $return_array[ $tier_id ][ $result->scope ] = array(); }
			$return_array[ $tier_id ][ $result->scope ][] = intval( $result->term_id );
		}
	}
	
	// Format tier ids
	foreach( $return_array as $tier_id => $term_ids_by_scope ) {
		foreach( $term_ids_by_scope as $scope => $term_ids ) {
			$return_array[ $tier_id ][ $scope ] = patips_ids_to_array( $term_ids );
		}
	}
	
	return $return_array;
}