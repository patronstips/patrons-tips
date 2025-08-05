<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// METABOXES

/**
 * Add patron editor meta boxes
 * @since 0.6.0
 * @version 0.25.5
 */
function patips_patron_editor_meta_boxes() {
	if( empty( $_GET[ 'action' ] ) ) { return; }
	if( ! in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
	// Main
	add_meta_box( 'patips_patron_info', esc_html__( 'Patron info', 'patrons-tips' ), 'patips_display_patron_info_meta_box', 'patrons-tips_page_patips_patrons', 'normal', 'high' );
	add_meta_box( 'patips_patron_history', esc_html__( 'Patronage history', 'patrons-tips' ), 'patips_display_patron_history_meta_box', 'patrons-tips_page_patips_patrons', 'normal', 'high' );
	add_meta_box( 'patips_patron_loyalty', esc_html__( 'Loyalty', 'patrons-tips' ), 'patips_display_patron_loyalty_meta_box', 'patrons-tips_page_patips_patrons', 'normal', 'high' );
	
	// Sidebar
	add_meta_box( 'patips_patron_publish', esc_html__( 'Publish', 'patrons-tips' ), 'patips_display_patron_publish_meta_box', 'patrons-tips_page_patips_patrons', 'side', 'high' );
}
add_action( 'add_meta_boxes_patrons-tips_page_patips_patrons', 'patips_patron_editor_meta_boxes' );


/*
 * Allow metaboxes in patron editor
 * @since 0.6.0
 */
function patips_allow_meta_boxes_in_patron_editor() {
	if( empty( $_GET[ 'action' ] ) ) { return; }
	if( ! in_array( $_GET[ 'action' ], array( 'edit', 'new' ), true ) ) { return; }
	
    /* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
    do_action( 'add_meta_boxes_patrons-tips_page_patips_patrons', null );
    do_action( 'add_meta_boxes', 'patrons-tips_page_patips_patrons', null );
	
	/* Enqueue WordPress' script for handling the meta boxes */
	if( wp_script_is( 'postbox', 'registered' ) ) { wp_enqueue_script( 'postbox' ); }
}
add_action( 'load-patrons-tips_page_patips_patrons', 'patips_allow_meta_boxes_in_patron_editor' );




// CRUD

/**
 * Update a patron
 * @since 0.6.0
 * @version 1.0.2
 */
function patips_controller_update_patron_data() {
	if( empty( $_POST[ 'action' ] ) || empty( $_POST[ 'patron_id' ] ) ) { return; }
	if( $_POST[ 'action' ] !== 'edit' ) { return; }
	
	// Exit if wrong nonce
	check_admin_referer( 'patips_update_patron', 'nonce' );
	
	// Exit if not allowed to create a patron
	$is_allowed = current_user_can( 'patips_edit_patrons' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Sanitize data
	$patron_id   = intval( $_POST[ 'patron_id' ] );
	$patron_data = patips_sanitize_patron_data( array_merge( $_POST, array( 'id' => $patron_id ) ) );
	
	$patron_data[ 'creation_date' ] = '';
	
	if( ! $patron_data[ 'user_id' ] )     { $patron_data[ 'user_id' ] = -1; }
	if( ! $patron_data[ 'user_email' ] )  { $patron_data[ 'user_email' ] = 'null'; }
	
	// Update the patron data
	$updated = patips_update_patron_data( $patron_id, $patron_data );
	
	// Delete patron history entries
	$deleted_entry_ids = isset( $_POST[ 'deleted_history_entry_ids' ] ) ? patips_ids_to_array( wp_unslash( $_POST[ 'deleted_history_entry_ids' ] ) ) : array();
	if( $deleted_entry_ids ) {
		$deleted = patips_delete_patron_history( $patron_id, $deleted_entry_ids, true );
		
		if( $deleted ) {
			$updated = is_numeric( $updated ) ? $updated + $deleted : $deleted;
		}
	}
	
	if( $updated ) {
		wp_cache_delete( 'patron_' . $patron_id, 'patrons-tips' );
		
		do_action( 'patips_patron_edited', $patron_id );
	}
	
	$GLOBALS[ 'patips_patron_updated' ] = $updated;
}
add_action( 'load-patrons-tips_page_patips_patrons', 'patips_controller_update_patron_data', 5 );


/**
 * Deactivate a patron
 * @since 0.6.0
 * @version 1.0.2
 */
function patips_controller_deactivate_patron() {
	if( empty( $_GET[ 'action' ] ) || empty( $_GET[ 'patron_id' ] ) ) { return; }
	if( $_GET[ 'action' ] !== 'trash' ) { return; }
	
	// Exit if wrong nonce
	$patron_id = intval( $_GET[ 'patron_id' ] );
	check_admin_referer( 'trash-patron_' . $patron_id );
	
	// Exit if not allowed to create a patron
	$is_allowed = current_user_can( 'patips_delete_patrons' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Deactivate the patron
	$deactivated = patips_deactivate_patron( $patron_id );
	
	if( $deactivated ) {
		wp_cache_delete( 'patron_' . $patron_id, 'patrons-tips' );
		
		do_action( 'patips_patron_deactivated', $patron_id );
	}
	
	$GLOBALS[ 'patips_patron_deactivated' ] = $deactivated;
}
add_action( 'load-patrons-tips_page_patips_patrons', 'patips_controller_deactivate_patron', 5 );


/**
 * Activate a patron
 * @since 0.6.0
 * @version 1.0.2
 */
function patips_controller_activate_patron() {
	if( empty( $_GET[ 'action' ] ) || empty( $_GET[ 'patron_id' ] ) ) { return; }
	if( $_GET[ 'action' ] !== 'restore' ) { return; }
	
	// Exit if wrong nonce
	$patron_id = intval( $_GET[ 'patron_id' ] );
	check_admin_referer( 'restore-patron_' . $patron_id );
	
	// Exit if not allowed to create a patron
	$is_allowed = current_user_can( 'patips_edit_patrons' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); 
		exit;
	}
	
	// Restore the patron
	$restored = patips_activate_patron( $patron_id );
	
	if( $restored ) {
		wp_cache_delete( 'patron_' . $patron_id, 'patrons-tips' );
		
		do_action( 'patips_patron_restored', $patron_id );
	}
	
	$GLOBALS[ 'patips_patron_restored' ] = $restored;
}
add_action( 'load-patrons-tips_page_patips_patrons', 'patips_controller_activate_patron', 5 );


/**
 * Delete a patron
 * @since 0.6.0
 * @version 1.0.2
 */
function patips_controller_delete_patron_data() {
	if( empty( $_GET[ 'action' ] ) || empty( $_GET[ 'patron_id' ] ) ) { return; }
	if( $_GET[ 'action' ] !== 'delete' ) { return; }
	
	// Exit if wrong nonce
	$patron_id = intval( $_GET[ 'patron_id' ] );
	check_admin_referer( 'delete-patron_' . $patron_id );
	
	// Exit if not allowed to create a patron
	$is_allowed = current_user_can( 'patips_delete_patrons' );
	if( ! $is_allowed ) { 
		esc_html_e( 'You are not allowed to do that.', 'patrons-tips' );
		exit;
	}
	
	// Delete the patron
	$deleted = patips_delete_patron( $patron_id );
	
	if( $deleted ) {
		wp_cache_delete( 'patron_' . $patron_id, 'patrons-tips' );
		
		do_action( 'patips_patron_deleted', $patron_id );
	}
	
	$GLOBALS[ 'patips_patron_deleted' ] = $deleted;
}
add_action( 'load-patrons-tips_page_patips_patrons', 'patips_controller_delete_patron_data', 5 );


/**
 * Display an admin notice to feedback the result of an action taken on a patron
 * @since 0.6.0
 * @version 1.0.2
 */
function patips_controller_patron_admin_notices() {
	$message = '';
	
	// Create
	if( isset( $GLOBALS[ 'patips_patron_created' ] ) ) {
		if( $GLOBALS[ 'patips_patron_created' ] ) {
			$message_type = 'success';
			$message = esc_html__( 'The patron has been created.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to create the patron.', 'patrons-tips' );
		}
	}
	
	// Update
	if( isset( $GLOBALS[ 'patips_patron_updated' ] ) ) {
		if( $GLOBALS[ 'patips_patron_updated' ] !== false ) {
			$message_type = 'success';
			$message = esc_html__( 'The patron has been updated.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to update the patron.', 'patrons-tips' );
		}
	}
	
	// Trash
	if( isset( $GLOBALS[ 'patips_patron_deactivated' ] ) || isset( $GLOBALS[ 'patips_patron_deleted' ] ) ) {
		if( ! empty( $GLOBALS[ 'patips_patron_deactivated' ] ) || ! empty( $GLOBALS[ 'patips_patron_deleted' ] ) ) {
			$message_type = 'success';
			$message = esc_html__( 'The patron has been removed.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to remove the patron.', 'patrons-tips' );
		}
	}
	
	// Restore
	if( isset( $GLOBALS[ 'patips_patron_restored' ] ) ) {
		if( $GLOBALS[ 'patips_patron_restored' ] ) {
			$message_type = 'success';
			$message = esc_html__( 'The patron has been restored.', 'patrons-tips' );
		} else {
			$message_type = 'error';
			$message = esc_html__( 'An error occurred while trying to restore the patron.', 'patrons-tips' );
		}
	}
	
	if( $message ) {
	?>
		<div class='notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible patips-patron-notice'>
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php
	}
}
add_action( 'admin_notices', 'patips_controller_patron_admin_notices', 10 );




// SCREEN OPTIONS

/**
 * Add screen options
 * @since 0.6.0
 */
function patips_add_patrons_screen_options() {
	add_action( 'load-patrons-tips_page_patips_patrons', 'patips_display_patrons_screen_options' );
}
add_action( 'admin_menu', 'patips_add_patrons_screen_options', 20 );


/**
 * Add patron page columns screen options
 * @since 0.6.0
 * @version 0.26.3
 */
function patips_add_patrons_screen_options_columns() {
	$action = ! empty( $_REQUEST[ 'action' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'action' ] ) ) : '';
	if( ! $action || ! in_array( $action, array( 'edit', 'new' ), true ) ) {
		new PATIPS_Patrons_List_Table();
	}
}
add_action( 'admin_head-patrons-tips_page_patips_patrons', 'patips_add_patrons_screen_options_columns' );


/**
 * Save screen options
 * @since 0.6.0
 */
function patips_save_patrons_screen_options( $status, $option, $value ) {
	if( 'patips_patrons_per_page' == $option ) {
		return $value;
	}
	return $status;
}
add_filter( 'set-screen-option', 'patips_save_patrons_screen_options', 10, 3 );




// EDIT PAGE

/**
 * Display patron history Start column value
 * @since 0.6.0
 * @version 0.25.5
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_start( $entry, $field, $patron ) {
	$start_dt           = ! empty( $entry[ 'date_start' ] ) ? DateTime::createFromFormat( 'Y-m-d', $entry[ 'date_start' ] ) : null;
	$start_date         = $start_dt ? patips_format_datetime( $start_dt->format( 'Y-m-d' ) . ' 00:00:00' ) : '';
	$is_readonly_synced = ! empty( $field[ 'readonly' ] ) && $field[ 'readonly' ] === 'synced';
	
	if( ! $is_readonly_synced || ( $is_readonly_synced && ( ! empty( $entry[ 'order_id' ] ) || ! empty( $entry[ 'subscription_id' ] ) ) ) ) {
	?>
		<span class='patips-history-date_start'><?php echo esc_html( $start_date ); ?></span>
	<?php
	}
}
add_action( 'patips_patron_settings_history_column_start', 'patips_patron_settings_history_column_start', 20, 3 );


/**
 * Display patron history Duration column value
 * @since 0.6.0
 * @version 0.25.5
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_duration( $entry, $field, $patron ) {
	$end_dt   = ! empty( $entry[ 'date_end' ] ) ? DateTime::createFromFormat( 'Y-m-d', $entry[ 'date_end' ] ) : null;
	$end_date = $end_dt ? patips_format_datetime( $end_dt->format( 'Y-m-d' ) . ' 23:59:59' ) : '';
?>
	<span class='patips-history-date_end'><?php echo esc_html( $end_date ); ?></span>
<?php
}
add_action( 'patips_patron_settings_history_column_duration', 'patips_patron_settings_history_column_duration', 20, 3 );


/**
 * Display patron history Period column value
 * @since 0.11.0
 * @version 0.25.5
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_period( $entry, $field, $patron ) {
	$period_name = ! empty( $entry[ 'period_start' ] ) ? patips_get_patron_history_period_name( $entry ) : '';
?>
	<span class='patips-history-period'><?php echo esc_html( $period_name ); ?></span>
<?php
}
add_action( 'patips_patron_settings_history_column_period', 'patips_patron_settings_history_column_period', 20, 3 );


/**
 * Display patron history Tier column value
 * @since 0.6.0
 * @version 0.25.5
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_tier( $entry, $field, $patron ) {
	$tier_id            = ! empty( $entry[ 'tier_id' ] ) ? intval( $entry[ 'tier_id' ] ) : '';
	/* translators: %s is the tier ID */
	$tier_title         = ! empty( $field[ 'options' ][ $tier_id ] ) ? $field[ 'options' ][ $tier_id ] : ( $tier_id ? sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id ) : '' );
	$is_readonly_synced = ! empty( $field[ 'readonly' ] ) && $field[ 'readonly' ] === 'synced';
	
	if( ! $is_readonly_synced || ( $is_readonly_synced && ( ! empty( $entry[ 'order_id' ] ) || ! empty( $entry[ 'subscription_id' ] ) ) ) ) {
	?>
		<span class='patips-history-tier_title'><?php echo esc_html( $tier_title ); ?></span>
	<?php
	}
}
add_action( 'patips_patron_settings_history_column_tier', 'patips_patron_settings_history_column_tier', 20, 3 );


/**
 * Display patron history Active column value
 * @since 0.23.1
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_active( $entry, $field, $patron ) {
?>
	<span class='patips-history-active'><?php echo ! empty( $entry[ 'active' ] ) ? esc_html__( 'Yes', 'patrons-tips' ) : esc_html__( 'No', 'patrons-tips' ); ?></span>
<?php
}
add_action( 'patips_patron_settings_history_column_active', 'patips_patron_settings_history_column_active', 20, 3 );


/**
 * Display patron history Origin column value
 * @since 0.6.0
 * @version 0.25.5
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_origin( $entry, $field, $patron ) {
	$origin = '';
	
	if( ! empty( $entry[ 'subscription_plugin' ] ) ) {
		$origin = $entry[ 'subscription_plugin' ];
	}
	else if( ! empty( $entry[ 'order_id' ] ) ) {
		/* translators: This is a plugin name. */
		$origin = esc_html__( 'WooCommerce', 'patrons-tips' );
	}
	else {
		$origin = esc_html__( 'Manual', 'patrons-tips' );
	}
	
	$origin = apply_filters( 'patips_patron_history_entry_origin', $origin, $entry, $field, $patron );
	?>
		<span class='patips-history-origin_label'><?php echo esc_html( $origin ); ?></span>
	<?php
}
add_action( 'patips_patron_settings_history_column_origin', 'patips_patron_settings_history_column_origin', 20, 3 );


/**
 * Display delete action in patron history table
 * @since 0.23.0
 * @param array $entry
 * @param array $field
 * @param array $patron
 */
function patips_patron_settings_history_column_actions( $entry, $field, $patron ) {
	if( empty( $entry[ 'order_id' ] ) && empty( $entry[ 'subscription_id' ] ) ) {
	?>
		<span class='patips-history-action patips-history-delete button button-secondary patips-delete-button'>
			<?php esc_html_e( 'Delete', 'patrons-tips' ); ?>
		</span>
	<?php 
	}
}
add_action( 'patips_patron_settings_history_column_actions', 'patips_patron_settings_history_column_actions', 20, 3 );