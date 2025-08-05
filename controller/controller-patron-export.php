<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Register a daily cron event to clean expired exports
 * @since 0.8.0
 */
function patips_register_cron_event_to_clean_expired_exports() {
	if( ! wp_next_scheduled ( 'patips_clean_expired_exports' ) ) {
		wp_schedule_event( time(), 'daily', 'patips_clean_expired_exports' );
	}
}
add_action( 'patips_activate', 'patips_register_cron_event_to_clean_expired_exports' );


/**
 * Deregister the daily cron event to clean expired exports
 * @since 0.8.0
 */
function patips_deregister_cron_event_to_clean_expired_exports() {
	wp_clear_scheduled_hook( 'patips_clean_expired_exports' );
}
add_action( 'patips_deactivate', 'patips_deregister_cron_event_to_clean_expired_exports' );


/**
 * Clean expired exports
 * @since 0.8.0
 */
function patips_clean_expired_exports() {
	$exports    = patips_get_exports( array( 'expired' => 1 ) );
	$export_ids = $exports ? array_keys( $exports ) : array();
	$export_ids = apply_filters( 'patips_expired_export_ids', $export_ids );
	$deleted    = $export_ids ? patips_delete_exports( $export_ids ) : false;
	
	if( $deleted ) {
		do_action( 'patips_expired_exports_deleted', $export_ids );
	}
}
add_action( 'patips_clean_expired_exports', 'patips_clean_expired_exports' );


/**
 * Generate the export patrons URL according to current filters and export settings
 * @since 0.8.0
 * @version 0.26.3
 */
function patips_controller_patron_export_url() {
	// Check nonce
	if( ! check_ajax_referer( 'patips_patron_export_url', 'nonce', false ) ) { 
		patips_send_json_invalid_nonce( 'patron_export_url' );
	}

	// Check capabilities
	if( ! current_user_can( 'patips_manage_patrons' ) ) {
		patips_send_json_not_allowed( 'patron_export_url' );
	}
	
	$message = esc_html__( 'The link has been correctly generated. Use the link above to export your patrons.', 'patrons-tips' );
	
	// Get or generate current user export secret key
	$reset_key       = ! empty( $_POST[ 'reset_key' ] );
	$current_user_id = get_current_user_id();
	$secret_key      = $current_user_id ? get_user_meta( $current_user_id, 'patips_secret_key', true ) : '';
	if( ( ! $secret_key || $reset_key ) && $current_user_id ) {
		// Update secret key
		$secret_key = md5( wp_rand() );
		update_user_meta( $current_user_id, 'patips_secret_key', $secret_key );
		
		// Remove existing exports
		$exports    = patips_get_exports( array( 'user_ids' => array( $current_user_id ), 'expired' => false ) );
		$export_ids = $exports ? array_keys( $exports ) : array();
		if( $export_ids ) {
			patips_delete_exports( $export_ids );
		}
		
		// Feedback user
		if( $reset_key ) {
			$message .= '<br/><em>' . esc_html__( 'Your secret key has been changed. The old links that you have generated won\'t work anymore.', 'patrons-tips' ) . '</em>';
		}
	}

	// Get formatted patron filters
	$default_fitlers = patips_get_default_patron_filters();
	$patron_filters  = ! empty( $_POST[ 'patron_filters' ] ) ? patips_format_patron_filters( wp_unslash( $_POST[ 'patron_filters' ] ) ) : array();
	
	$default_settings = patips_get_patron_export_default_settings();
	$export_settings  = patips_sanitize_patron_export_settings( $_POST );
	
	// Do not save default values
	foreach( $patron_filters as $filter_name => $filter_value ) {
		if( is_numeric( $filter_value ) && is_string( $filter_value ) ) { 
			$filter_value = is_float( $filter_value + 0 ) ? floatval( $filter_value ) : intval( $filter_value );
		}
		if( $default_fitlers[ $filter_name ] === $filter_value ) {
			unset( $patron_filters[ $filter_name ] );
		}
	}
	
	$export_type = ! empty( $_POST[ 'export_type' ] ) ? sanitize_title_with_dashes( wp_unslash( $_POST[ 'export_type' ] ) ) : 'csv';
	
	// Additional URL attributes
	$add_url_atts = array(
		'action'      => 'patips_patron_export',
		'export_type' => $export_type,
		'filename'    => '',
		'key'         => $secret_key ? $secret_key : '',
		'locale'      => patips_get_current_lang_code( true ),
		'per_page'    => $export_settings[ 'per_page' ]
	);
	
	// Add CSV specific args
	if( $export_type === 'csv' ) {
		$patron_filters[ 'raw' ]      = $export_settings[ 'csv_raw' ];
		$patron_filters[ 'row_type' ] = $export_settings[ 'csv_row_type' ];
		if( array_diff_assoc( array_values( $default_settings[ 'csv_columns' ] ), $export_settings[ 'csv_columns' ] ) ) {
			$add_url_atts[ 'columns' ] = $export_settings[ 'csv_columns' ];
		}
	}
	
	// Let third party plugins change the URL attributes
	$url_atts = apply_filters( 'patips_patron_export_url_attributes', array_merge( $add_url_atts, $patron_filters ), $export_type, $export_settings, array_merge( $default_fitlers, $default_settings ) );
	
	// Save export
	$export_id = patips_insert_export( array( 'type' => 'patron_' . $export_type, 'user_id' => $current_user_id, 'args' => $url_atts ) );
	
	if( ! $export_id ) {
		patips_send_json( array( 
			'status' => 'failed', 
			'error'  => 'cannot_save_export_data', 
			'message' => esc_html__( 'An error occurred while trying to save export data.', 'patrons-tips' )
		), 'patron_export_url' ); 
	}
	
	// Add URL attributes
	$short_url_atts = array(
		'action'    => $url_atts[ 'action' ],
		'key'       => $url_atts[ 'key' ],
		'export_id' => $export_id
	);
	$export_url = add_query_arg( $short_url_atts, home_url() );
	
	// Update settings
	update_user_meta( $current_user_id, 'patips_patron_export_settings', $export_settings );
	
	patips_send_json( array( 'status' => 'success', 'url' => esc_url_raw( $export_url ), 'message' => $message ), 'patron_export_url' ); 
}
add_action( 'wp_ajax_patipsPatronExportUrl', 'patips_controller_patron_export_url' );


/**
 * Export patrons according to filters
 * @since 0.8.0
 * @version 1.0.4
 */
function patips_controller_patron_export() {
	if( empty( $_REQUEST[ 'action' ] ) ) { return; }
	if( $_REQUEST[ 'action' ] !== 'patips_patron_export' ) { return; }
	
	// Check if the secret key exists
	$key = ! empty( $_REQUEST[ 'key' ] ) ? sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'key' ] ) ) : '';
	if( ! $key ) { esc_html_e( 'Missing secret key.', 'patrons-tips' ); exit; }
	
	// Check if the user exists
	$user_id = patips_get_user_id_by_secret_key( $key );
	if( ! $user_id ) { esc_html_e( 'Invalid secret key.', 'patrons-tips' ); exit; }
	
	// Check if the user can export patrons
	$is_allowed = user_can( $user_id, 'patips_manage_patrons' );
	if( ! $is_allowed ) { esc_html_e( 'You are not allowed to do that.', 'patrons-tips' ); exit; }
	
	// Get the export
	$args      = array();
	$export_id = ! empty( $_REQUEST[ 'export_id' ] ) ? intval( $_REQUEST[ 'export_id' ] ) : 0;
	$export    = $export_id ? patips_get_export( $export_id ) : array();
	if( ! $export ) { esc_html_e( 'Invalid or expired export ID.', 'patrons-tips' ); exit; }
	
	// Check the user secret key
	if( intval( $export[ 'user_id' ] ) !== intval( $user_id ) ) {
		esc_html_e( 'The secret key doesn\'t match the user who has generated the export.', 'patrons-tips' );
		exit;
	}
	
	// Allow to override the patron filters and some parameters with URL parameters
	$override_args = array_intersect_key( $_REQUEST, patips_get_default_patron_filters() );
	if( ! empty( $_REQUEST[ 'columns' ] ) ) {
		$override_args[ 'columns' ] = patips_str_ids_to_array( wp_unslash( $_REQUEST[ 'columns' ] ) );
	}
	if( ! empty( $_REQUEST[ 'raw' ] ) ) {
		$override_args[ 'raw' ] = intval( $_REQUEST[ 'raw' ] );
	}
	if( ! empty( $_REQUEST[ 'row_type' ] ) ) {
		$override_args[ 'row_type' ] = intval( $_REQUEST[ 'row_type' ] );
	}
	if( ! empty( $_REQUEST[ 'filename' ] ) ) {
		$override_args[ 'filename' ] = sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'filename' ] ) );
	}
	if( ! empty( $_REQUEST[ 'locale' ] ) ) {
		$override_args[ 'locale' ] = sanitize_title_with_dashes( wp_unslash( $_REQUEST[ 'locale' ] ) );
	}
	$args = array_merge( $export[ 'args' ], $override_args );
	
	$args[ 'sequence' ] = $export[ 'sequence' ];
	if( empty( $args[ 'filename' ] ) ) { $args[ 'filename' ] = 'patrons-tips-patrons-' . $export_id; }
	
	// Check the filename
	$filename = ! empty( $args[ 'filename' ] ) ? sanitize_title_with_dashes( $args[ 'filename' ] ) : 'patrons-tips-patrons';
	if( ! $filename ) { esc_html_e( 'Invalid filename.', 'patrons-tips' ); exit; }
	
	$export_type = sanitize_title_with_dashes( $args[ 'export_type' ] );
	if( $export_type === 'csv' && substr( $filename, -4 ) !== '.csv' ) { 
		$filename .= '.csv';
	}
	
	// Format the patron filters
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_filters( $formatted_args );
	$filters[ 'display_private_columns' ] = 1;
	
	// Let third party plugins change the patron filters and the file headers
	$filters = apply_filters( 'patips_patron_export_filters', $filters, $export_type );
	$headers = apply_filters( 'patips_patron_export_headers', array(
		'Content-type'        => 'text/csv',
		'charset'             => 'utf-8',
		'Content-Disposition' => 'attachment',
		'filename'            => $filename,
		'Cache-Control'       => 'no-cache, must-revalidate',  // HTTP/1.1
		'Expires'             => 'Sat, 26 Dec 1992 00:50:00 GMT'  // Expired date to force third-party apps to refresh soon
	), $export_type );
	
	// Get the user export settings (to use as defaults)
	$user_settings = patips_get_patron_export_settings( $user_id );
	
	// Format the patron list columns
	$columns = ! empty( $args[ 'columns' ] ) && is_array( $args[ 'columns' ] ) ? $args[ 'columns' ] : ( ! empty( $user_settings[ $export_type . '_columns' ] ) ? $user_settings[ $export_type . '_columns' ] : array() );
	
	// Temporarily switch locale to the desired one or user default's
	$locale = ! empty( $args[ 'locale' ] ) ? sanitize_text_field( $args[ 'locale' ] ) : patips_get_user_locale( $user_id, 'site' );
	$lang_switched = patips_switch_locale( $locale );
	
	header( 'Content-type: ' . $headers[ 'Content-type' ] . '; charset=' . $headers[ 'charset' ] );
	header( 'Content-Disposition: ' . $headers[ 'Content-Disposition' ] . '; filename=' . $headers[ 'filename' ] );
	header( 'Cache-Control: ' . $headers[ 'Cache-Control' ] );
	header( 'Expires: ' . $headers[ 'Expires' ] );
	
	// Generate export according to type
	if( $export_type === 'csv' ) { 
		$csv_args = apply_filters( 'patips_patron_export_csv_args', array(
			'columns'  => $columns,
			'raw'      => ! empty( $args[ 'raw' ] ) ? 1 : 0,
			'row_type' => ! empty( $args[ 'row_type' ] ) ? $args[ 'row_type' ] : 'patron',
			'locale'   => $locale
		) );
		echo patips_convert_patrons_to_csv( $filters, $csv_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	
	// Switch locale back to normal
	if( $lang_switched ) { patips_restore_locale(); }
	
	// Increment the expiry date and sequence
	if( $export_id ) { patips_update_export( $export_id ); }
	
	exit;
}
add_action( 'init', 'patips_controller_patron_export', 10 );