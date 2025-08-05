<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// METABOXES

/**
 * Add tier editor meta boxes
 * @since 0.5.0
 * @version 0.25.5
 */
function patips_tier_editor_meta_boxes() {
	if( empty( $_GET[ 'action' ] ) ) { return; }
	if( ! in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
	// Main
	add_meta_box( 'patips_tier_settings', esc_html__( 'Settings', 'patrons-tips' ), 'patips_display_tier_settings_meta_box', 'patrons-tips_page_patips_tiers', 'normal', 'high' );
	
	// Sidebar
	add_meta_box( 'patips_tier_publish', esc_html__( 'Publish', 'patrons-tips' ), 'patips_display_tier_publish_meta_box', 'patrons-tips_page_patips_tiers', 'side', 'high' );
}
add_action( 'add_meta_boxes_patrons-tips_page_patips_tiers', 'patips_tier_editor_meta_boxes' );


/*
 * Allow metaboxes on for editor
 * @since 0.5.0
 */
function patips_allow_meta_boxes_in_tier_editor() {
	if( empty( $_GET[ 'action' ] ) ) { return; }
	if( ! in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
    /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
    do_action( 'add_meta_boxes_patrons-tips_page_patips_tiers', null );
    do_action( 'add_meta_boxes', 'patrons-tips_page_patips_tiers', null );
	
	/* Enqueue WordPress' script for handling the meta boxes */
	if( wp_script_is( 'postbox', 'registered' ) ) { wp_enqueue_script( 'postbox' ); }
}
add_action( 'load-patrons-tips_page_patips_tiers', 'patips_allow_meta_boxes_in_tier_editor' );




// CRUD

/**
 * Create a tier
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_controller_create_tier() {
	if( empty( $_POST[ 'action' ] ) ) { return; }
	if( $_POST[ 'action' ] !== 'create' ) { return; }
	
	// Exit if wrong nonce
	check_admin_referer( 'patips_update_tier', 'nonce' );
	
	// Exit if not allowed to create a tier
	$is_allowed = current_user_can( 'patips_create_tiers' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Sanitize data
	$tier_data = patips_sanitize_tier_data( $_POST );
	
	// Create the tier
	$created = patips_create_tier( $tier_data );
	
	// Remove tier data from $_REQUEST to avoid filtering the list
	foreach( $tier_data as $key => $value ) {
		unset( $_REQUEST[ $key ] );
	}
	
	if( $created ) {
		wp_cache_delete( 'tiers_data', 'patrons-tips' );
	}
	
	$GLOBALS[ 'patips_tier_created' ] = $created;
}
add_action( 'load-patrons-tips_page_patips_tiers', 'patips_controller_create_tier', 5 );


/**
 * Update a tier
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_controller_update_tier_data() {
	if( empty( $_POST[ 'action' ] ) || empty( $_POST[ 'tier_id' ] ) ) { return; }
	if( $_POST[ 'action' ] !== 'edit' ) { return; }
	
	// Exit if wrong nonce
	check_admin_referer( 'patips_update_tier', 'nonce' );
	
	// Exit if not allowed to create a tier
	$is_allowed = current_user_can( 'patips_edit_tiers' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Sanitize data
	$tier_id   = intval( $_POST[ 'tier_id' ] );
	$tier_data = patips_sanitize_tier_data( array_merge( $_POST, array( 'id' => $tier_id ) ) );
	
	$tier_data[ 'user_id' ]       = 0;
	$tier_data[ 'creation_date' ] = '';
	$tier_data[ 'active' ]        = 1;
	
	if( ! $tier_data[ 'title' ] )       { $tier_data[ 'title' ]       = 'null'; }
	if( ! $tier_data[ 'icon_id' ] )     { $tier_data[ 'icon_id' ]     = -1; }
	if( ! $tier_data[ 'description' ] ) { $tier_data[ 'description' ] = 'null'; }
	if( ! isset( $_POST[ 'price' ] ) )  { $tier_data[ 'price' ]       = -1; }
	
	// Update the tier data
	$updated = patips_update_tier_data( $tier_id, $tier_data );
	
	if( $updated ) {
		wp_cache_delete( 'tiers_data', 'patrons-tips' );
	}
	
	$GLOBALS[ 'patips_tier_updated' ] = $updated;
}
add_action( 'load-patrons-tips_page_patips_tiers', 'patips_controller_update_tier_data', 5 );


/**
 * Deactivate a tier
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_controller_deactivate_tier() {
	if( empty( $_GET[ 'action' ] ) || empty( $_GET[ 'tier_id' ] ) ) { return; }
	if( $_GET[ 'action' ] !== 'trash' ) { return; }
	
	// Exit if wrong nonce
	$tier_id = intval( $_GET[ 'tier_id' ] );
	check_admin_referer( 'trash-tier_' . $tier_id );
	
	// Exit if not allowed to create a tier
	$is_allowed = current_user_can( 'patips_delete_tiers' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Deactivate the tier
	$deactivated = patips_deactivate_tier( $tier_id );
	
	if( $deactivated ) {
		wp_cache_delete( 'tiers_data', 'patrons-tips' );
		
		do_action( 'patips_tier_deactivated', $tier_id );
	}
	
	$GLOBALS[ 'patips_tier_deactivated' ] = $deactivated;
}
add_action( 'load-patrons-tips_page_patips_tiers', 'patips_controller_deactivate_tier', 5 );


/**
 * Activate a tier
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_controller_activate_tier() {
	if( empty( $_GET[ 'action' ] ) || empty( $_GET[ 'tier_id' ] ) ) { return; }
	if( $_GET[ 'action' ] !== 'restore' ) { return; }
	
	// Exit if wrong nonce
	$tier_id = intval( $_GET[ 'tier_id' ] );
	check_admin_referer( 'restore-tier_' . $tier_id );
	
	// Exit if not allowed to create a tier
	$is_allowed = current_user_can( 'patips_edit_tiers' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Restore the tier
	$restored = patips_activate_tier( $tier_id );
	
	if( $restored ) {
		wp_cache_delete( 'tiers_data', 'patrons-tips' );
		
		do_action( 'patips_tier_restored', $tier_id );
	}
	
	$GLOBALS[ 'patips_tier_restored' ] = $restored;
}
add_action( 'load-patrons-tips_page_patips_tiers', 'patips_controller_activate_tier', 5 );


/**
 * Delete a tier
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_controller_delete_tier_data() {
	if( empty( $_GET[ 'action' ] ) || empty( $_GET[ 'tier_id' ] ) ) { return; }
	if( $_GET[ 'action' ] !== 'delete' ) { return; }
	
	// Exit if wrong nonce
	$tier_id = intval( $_GET[ 'tier_id' ] );
	check_admin_referer( 'delete-tier_' . $tier_id );
	
	// Exit if not allowed to create a tier
	$is_allowed = current_user_can( 'patips_delete_tiers' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' );
		exit;
	}
	
	// Delete the tier
	$deleted = patips_delete_tier( $tier_id );
	
	if( $deleted ) {
		wp_cache_delete( 'tiers_data', 'patrons-tips' );
		
		do_action( 'patips_tier_deleted', $tier_id );
	}
	
	$GLOBALS[ 'patips_tier_deleted' ] = $deleted;
}
add_action( 'load-patrons-tips_page_patips_tiers', 'patips_controller_delete_tier_data', 5 );


/**
 * Display an admin notice to feedback the result of an action taken on a tier
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_controller_tier_admin_notices() {
	$message = '';
	
	// Create
	if( isset( $GLOBALS[ 'patips_tier_created' ] ) ) {
		if( $GLOBALS[ 'patips_tier_created' ] ) {
			$message_type = 'success';
			$message = esc_html__( 'The tier has been created.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to create the tier.', 'patrons-tips' );
		}
	}
	
	// Update
	if( isset( $GLOBALS[ 'patips_tier_updated' ] ) ) {
		if( $GLOBALS[ 'patips_tier_updated' ] !== false ) {
			$message_type = 'success';
			$message = esc_html__( 'The tier has been updated.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to update the tier.', 'patrons-tips' );
		}
	}
	
	// Trash
	if( isset( $GLOBALS[ 'patips_tier_deactivated' ] ) || isset( $GLOBALS[ 'patips_tier_deleted' ] ) ) {
		if( ! empty( $GLOBALS[ 'patips_tier_deactivated' ] ) || ! empty( $GLOBALS[ 'patips_tier_deleted' ] ) ) {
			$message_type = 'success';
			$message = esc_html__( 'The tier has been removed.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to remove the tier.', 'patrons-tips' );
		}
	}
	
	// Restore
	if( isset( $GLOBALS[ 'patips_tier_restored' ] ) ) {
		if( $GLOBALS[ 'patips_tier_restored' ] ) {
			$message_type = 'success';
			$message = esc_html__( 'The tier has been restored.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to restore the tier.', 'patrons-tips' );
		}
	}
	
	if( $message ) {
	?>
		<div class='notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible patips-tier-notice'>
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php
	}
}
add_action( 'admin_notices', 'patips_controller_tier_admin_notices', 10 );




// SCREEN OPTIONS

/**
 * Add screen options
 * @since 0.5.1
 * @version 0.6.0
 */
function patips_add_tiers_screen_options() {
	add_action( 'load-patrons-tips_page_patips_tiers', 'patips_display_tiers_screen_options' );
}
add_action( 'admin_menu', 'patips_add_tiers_screen_options', 20 );


/**
 * Add tier page columns screen options
 * @since 0.6.0
 * @version 0.26.3
 */
function patips_add_tiers_screen_options_columns() {
	$action = ! empty( $_REQUEST[ 'action' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'action' ] ) ) : '';
	if( ! $action || ! in_array( $action, array( 'edit', 'new' ), true ) ) {
		new PATIPS_Tiers_List_Table();
	}
}
add_action( 'admin_head-patrons-tips_page_patips_tiers', 'patips_add_tiers_screen_options_columns' );


/**
 * Save screen options
 * @since 0.5.1
 * @version 0.6.0
 */
function patips_save_tiers_screen_options( $status, $option, $value ) {
	if( 'patips_tiers_per_page' == $option ) {
		return $value;
	}
	return $status;
}
add_filter( 'set-screen-option', 'patips_save_tiers_screen_options', 10, 3 );