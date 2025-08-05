<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// SCREEN OPTIONS

/**
 * Display tier page options in screen options area
 * @since 0.5.1
 * @version 0.23.0
 */
function patips_display_tiers_screen_options() {
	$screen = get_current_screen();

	// Don't do anything if we are not on the tier page
	if( ! is_object( $screen ) || $screen->id != 'patrons-tips_page_patips_tiers' ) { return; }

	if( ! empty( $_GET[ 'action' ] ) && in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ) {
		add_screen_option( 'layout_columns', array( 
			'max'     => 2, 
			'default' => 2 
		));
	} else {
		add_screen_option( 'per_page', array(
			'label'   => esc_html__( 'Tiers per page:', 'patrons-tips' ),
			'default' => 20,
			'option'  => 'patips_tiers_per_page'
		));
	}
}




// CREATE - UPDATE

/**
 * Create a tier
 * @since 0.5.0
 * @version 0.22.0
 * @param array $tier_data
 * @return int|false
 */
function patips_create_tier( $tier_data ) {
	// Insert the tier
	$tier_id = patips_insert_tier( $tier_data );
	if( ! $tier_id ) { return false; }
	
	// Insert metadata
	$tier_meta = array_intersect_key( $tier_data, patips_get_default_tier_meta() );
	if( $tier_meta ) {
		$tier_meta = array_map( 'maybe_unserialize', $tier_meta );
		patips_insert_metadata( 'tier', $tier_id, $tier_meta );
	}
	
	// Insert tier restricted term ids
	$term_ids_by_scope = array_filter( $tier_data[ 'term_ids' ] );
	if( $term_ids_by_scope ) {
		patips_insert_tiers_restricted_terms( $term_ids_by_scope, array( $tier_id ) );
	}
	
	do_action( 'patips_tier_created', $tier_id, $tier_data );
	
	return $tier_id;
}


/**
 * Update a tier data
 * @since 0.5.0
 * @version 0.22.0
 * @param array $tier_data
 * @return int|false
 */
function patips_update_tier_data( $tier_id, $tier_data ) {
	// Update the tier
	$updated1 = patips_update_tier( array_merge( $tier_data, array( 'id' => $tier_id ) ) );
	
	// Update metadata
	$updated2  = 0;
	$tier_meta = array_intersect_key( $tier_data, patips_get_default_tier_meta() );
	if( $tier_meta ) {
		$tier_meta = array_map( 'maybe_unserialize', $tier_meta );
		$updated2  = patips_update_metadata( 'tier', $tier_id, $tier_meta );
	}
	
	// Update tier restricted term ids
	$updated3 = patips_update_tier_restricted_terms( $tier_id, $tier_data[ 'term_ids' ] );
	
	if( $updated1 === false || $updated2 === false || $updated3 === false ) { $updated = false; }
	else { $updated = (int)$updated1 + (int)$updated2 + (int)$updated3; }
	
	$updated = apply_filters( 'patips_tier_updated', $updated, $tier_id, $tier_data );
	
	return $updated;
}


/**
 * Update a tier restricted terms
 * @since 0.5.0
 * @version 0.12.0
 * @param int $tier_id
 * @param array $new_terms
 * @return int|false
 */
function patips_update_tier_restricted_terms( $tier_id, $new_terms ) {
	$all_terms      = patips_get_tiers_restricted_term_ids( array( $tier_id ) );
	$old_terms      = ! empty( $all_terms[ $tier_id ] ) ? $all_terms[ $tier_id ] : array();
	$allowed_scopes = patips_get_tier_restricted_term_scopes();
	$scopes         = array_values( array_intersect( $allowed_scopes, patips_str_ids_to_array( array_merge( array_keys( $old_terms ), array_keys( $new_terms ) ) ) ) );
	$inserted_terms = array();
	$deleted_terms  = array();
	
	// Find terms to insert and terms to delete
	foreach( $scopes as $scope ) {
		if( ! isset( $new_terms[ $scope ] ) ) { $new_terms[ $scope ] = array(); }
		if( ! isset( $old_terms[ $scope ] ) ) { $old_terms[ $scope ] = array(); }
		
		$inserted_terms[ $scope ] = array_diff( $new_terms[ $scope ], $old_terms[ $scope ] );
		$deleted_terms[ $scope ]  = array_diff( $old_terms[ $scope ], $new_terms[ $scope ] );
		
		if( empty( $inserted_terms[ $scope ] ) ) { unset( $inserted_terms[ $scope ] ); }
		if( empty( $deleted_terms[ $scope ] ) )  { unset( $deleted_terms[ $scope ] ); }
	}
	
	// Update term ids
	$inserted = $inserted_terms ? patips_insert_tiers_restricted_terms( $inserted_terms, array( $tier_id ) ) : 0;
	$deleted  = $deleted_terms ? patips_delete_tiers_restricted_terms( $deleted_terms, array( $tier_id ) ) : 0;
	
	if( $inserted === false || $deleted === false ) { return false; }
	
	return $inserted + $deleted;
}