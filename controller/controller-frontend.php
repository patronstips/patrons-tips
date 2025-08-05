<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add class to body if current user is currently patron
 * @since 1.0.4
 * @param array $classes
 * @param array $css_classes
 * @return array
 */
function patips_body_class( $classes, $css_classes = array() ) {
	$patron = patips_get_user_patron_data();
	if( ! $patron ) { return $classes; }
	
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	// Check if the user is current patron
	$is_patron = false;
	foreach( $patron[ 'history' ] as $history_entry ) {
		$start_dt     = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_start' ] . ' 00:00:00', $timezone );
		$end_dt       = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_end' ] . ' 23:59:59', $timezone );
		$is_period_in = $start_dt <= $now_dt && $end_dt >= $now_dt && $history_entry[ 'active' ];
		if( ! $is_period_in ) { continue; }
		
		$is_patron = true;
		break;
	}
	
	if( $is_patron ) {
		if( ! in_array( 'patips-is-patron', $classes, true ) ) { $classes[] = 'patips-is-patron'; }
	}
	
	return $classes;
}
add_filter( 'body_class', 'patips_body_class', 10, 2 );


/**
 * Update patron without account with the newly created user ID
 * @since 0.23.1
 * @param int $user_id
 */
function patips_update_patron_without_account_to_user( $user_id ) {
	$user = get_user_by( 'id', $user_id );
	if( $user && ! empty( $user->user_email ) ) {
		// This function does update the patron data if a user with the same email exists, so calling it is enough
		$patron = patips_get_user_patron_data( $user->user_email );
	}
}
add_action( 'user_register', 'patips_update_patron_without_account_to_user', 10, 1 );


/**
 * AJAX Controller - Process Subscription Form
 * @since 0.22.0
 * @version 0.26.3
 */
function patips_controller_process_subscription_form() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_submit_subscription_form', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'submit_subscription_form' ); }
	
	// Check if free susbcriptions are enabled
	if( ! patips_is_free_subscription_allowed() ) {
		patips_send_json( array( 
			'status'  => 'failed',
			'error'   => 'free_subscription_not_allowed',
			'message' => esc_html__( 'Free subscriptions are not allowed.', 'patrons-tips' )
		), 'submit_subscription_form' );
	}
	
	// Check if a tier has been selected
	$tier_id = isset( $_POST[ 'tier_id' ] ) ? intval( $_POST[ 'tier_id' ] ) : 0;
	if( ! $tier_id ) {
		patips_send_json( array( 
			'status'  => 'failed',
			'error'   => 'tier_not_selected',
			'message' => esc_html__( 'Please select a tier.', 'patrons-tips' )
		), 'submit_subscription_form' );
	}
	
	// Check if the selected tier exists
	$tiers = patips_get_tiers_data();
	$tier  = isset( $tiers[ $tier_id ] ) ? $tiers[ $tier_id ] : array();
	if( ! $tier ) {
		patips_send_json( array( 
			'status'  => 'failed',
			'error'   => 'tier_not_found',
			'message' => esc_html__( 'The selected tier was not found.', 'patrons-tips' )
		), 'submit_subscription_form' );
	}
	
	// Check if the user exists or if the email is valid and free
	$user_id    = is_user_logged_in() ? get_current_user_id() : 0;
	$user_email = '';
	if( ! $user_id ) {
		$user_email = isset( $_POST[ 'email' ] ) ? sanitize_email( wp_unslash( $_POST[ 'email' ] ) ) : '';
		if( ! $user_email ) {
			patips_send_json( array( 
				'status'  => 'failed',
				'error'   => 'invalid_email',
				'message' => esc_html__( 'The email address is not valid.', 'patrons-tips' )
			), 'submit_subscription_form' );
		}
		
		$user = get_user_by( 'email', $user_email );
		if( $user ) {
			patips_send_json( array( 
				'status'  => 'failed',
				'error'   => 'email_already_exists',
				/* translators: %s = "Log in" link*/
				'message' => sprintf( esc_html__( 'The email address is already associated with an account. Please %s first.', 'patrons-tips' ), patips_get_login_link() )
			), 'submit_subscription_form' );
		}
	}
	
	// Get the associated patron data or create one
	$patron = patips_get_patron_by_user_id_or_email( $user_id, $user_email, true );
	if( ! $patron ) {
		patips_send_json( array( 
			'status'  => 'failed',
			'error'   => 'patron_not_created',
			'message' => esc_html__( 'The patron account could not be retrieved or created.', 'patrons-tips' )
		), 'submit_subscription_form' );
	}
	
	// Check if the customer is already patron
	$timezone   = patips_get_wp_timezone();
	$now_dt     = new DateTime( 'now', $timezone );
	/* translators: %s is the tier ID */
	$tier_title = ! empty( $tier[ 'title' ] ) ? $tier[ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
	
	foreach( $patron[ 'history' ] as $history_entry ) {
		if( ! $history_entry[ 'active' ] || $history_entry[ 'tier_id' ] !== $tier[ 'id' ] ) { continue; }

		$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_start' ] . ' 00:00:00', $timezone );
		$end_dt   = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_end' ] . ' 23:59:59', $timezone );
		
		if( $start_dt <= $now_dt && $end_dt >= $now_dt ) {
			patips_send_json( array( 
				'status'  => 'success',
				'message' => esc_html__( 'You are already patron.', 'patrons-tips' ) 
				          . '</li><li>'
						  /* translators: %1$s = the Tier name. %2$s = formatted date (e.g. "September 30th 2024") */
				          . sprintf( esc_html__( 'You are a "%1$s" patron until %2$s.', 'patrons-tips' ), $tier_title, patips_format_datetime( $history_entry[ 'date_end' ] . ' 00:00:00' ) )
			), 'submit_subscription_form' );
			
			break;
		}
	}
	
	// Prepare the new history entry
	$start_dt       = clone $now_dt;
	$end_dt         = patips_compute_period_end( $start_dt, patips_get_default_period_duration() );
	$current_period = patips_get_current_period();
	$new_history_entry = apply_filters( 'patips_subscription_form_new_history_entry', array(
		'patron_id'    => $patron[ 'id' ],
		'tier_id'      => $tier_id,
		'date_start'   => $start_dt->format( 'Y-m-d' ),
		'date_end'     => $end_dt->format( 'Y-m-d' ),
		'period_start' => substr( $current_period[ 'start' ], 0, 10 ),
		'period_end'   => substr( $current_period[ 'end' ], 0, 10 ),
		'active'       => 1
	), $patron, $tier );
	
	// Allow third-party validation
	do_action( 'patips_validate_subscription_form', $new_history_entry, $patron, $tier );
	
	// Add the new entry to the patron history
	$new_history_entries = patips_sanitize_patron_history_data( array( $new_history_entry ), $patron[ 'id' ] );
	$inserted            = $new_history_entries ? patips_insert_patron_history( $patron[ 'id' ], $new_history_entries ) : false;
	
	if( ! $inserted ) {
		patips_send_json( array( 
			'status'  => 'failed',
			'error'   => 'insert_failed',
			'message' => esc_html__( 'An error occurred while trying to subscribe.', 'patrons-tips' )
		), 'submit_subscription_form' );
	}
	
	// Remove cache
	wp_cache_delete( 'patron_' . $patron[ 'id' ], 'patrons-tips' );
	wp_cache_delete( 'patron_user_' . $user_id, 'patrons-tips' );
	wp_cache_delete( 'patron_user_' . $user_email, 'patrons-tips' );
	
	// Allow third-party actions after successful subscription
	do_action( 'patips_subscription_form_processed', $new_history_entry, $patron, $tier );
	
	patips_send_json( array( 
		'status'  => 'success',
		'message' => esc_html__( 'Your subscription has been processed successfully.', 'patrons-tips' ) 
		          . '</li><li>' 
		          /* translators: %1$s = the Tier name. %2$s = formatted date (e.g. "September 30th 2024") */
		          . sprintf( esc_html__( 'You are a "%1$s" patron until %2$s.', 'patrons-tips' ), $tier_title, patips_format_datetime( $new_history_entry[ 'date_end' ] . ' 00:00:00' ) )
	), 'submit_subscription_form' );
}
add_action( 'wp_ajax_patipsSubmitSusbcriptionForm', 'patips_controller_process_subscription_form' );
add_action( 'wp_ajax_nopriv_patipsSubmitSusbcriptionForm', 'patips_controller_process_subscription_form' );


/**
 * AJAX Controller - Get list items
 * @since 0.12.0
 * @version 0.26.3
 */
function patips_controller_get_list_items() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_get_list_items', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'get_list_items' ); }
	
	$type = isset( $_POST[ 'type' ] ) ? sanitize_title_with_dashes( wp_unslash( $_POST[ 'type' ] ) ) : '';
	$args = isset( $_POST[ 'args' ] ) ? ( is_array( $_POST[ 'args' ] ) ? wp_unslash( $_POST[ 'args' ] ) : ( is_string( $_POST[ 'args' ] ) ? patips_maybe_decode_json( wp_unslash( $_POST[ 'args' ] ), true ) : array() ) ) : array();
	
	// Patron history entries
	if( $type === 'patron_history' ) {
		// Get filters and format them
		$filters = ! empty( $args[ 'filters' ] ) ? patips_format_patron_history_filters( $args[ 'filters' ] ) : array();

		// Get the desired history entries, + 1 to know if there are more to be displayed
		$columns    = ! empty( $args[ 'columns' ] ) ? patips_str_ids_to_array( $args[ 'columns' ] ) : array();
		$list_items = $filters ? patips_get_patron_history_list_items( array_merge( $filters, array( 'per_page' => $filters[ 'per_page' ] + 1 ) ), $columns ) : array();
	}
	
	// Patron posts
	else if( $type === 'patron_post' ) {
		// Get post filters and format them
		$filters = ! empty( $args[ 'filters' ] ) ? patips_format_patron_post_filters( $args[ 'filters' ] ) : array();
		
		// Sanitize additionnal args
		$filters[ 'image_size' ] = isset( $args[ 'filters' ][ 'image_size' ] ) ? sanitize_title_with_dashes( $args[ 'filters' ][ 'image_size' ] ) : '';
		$filters[ 'gray_out' ]   = isset( $args[ 'filters' ][ 'gray_out' ] ) ? intval( $args[ 'filters' ][ 'gray_out' ] ) : 1;
		$filters[ 'image_only' ] = isset( $args[ 'filters' ][ 'image_only' ] ) ? intval( $args[ 'filters' ][ 'image_only' ] ) : 0;
		$filters[ 'no_link' ]    = isset( $args[ 'filters' ][ 'no_link' ] ) ? intval( $args[ 'filters' ][ 'no_link' ] ) : 0;
		
		// Get the desired posts, + 1 to know if there are more to be displayed
		$list_items = $filters ? patips_get_patron_post_list_items( array_merge( $filters, array( 'per_page' => $filters[ 'per_page' ] + 1 ) ) ) : array();
	}
	
	$filters    = apply_filters( 'patips_get_more_list_items_filters', $filters, $type, $args );
	$list_items = apply_filters( 'patips_get_more_list_items', $list_items, $type, $args );
	
	if( ! $list_items ) {
		patips_send_json( array( 
			'status'  => 'failed', 
			'error'   => 'no_more_items', 
			'message' => esc_html__( 'All items are displayed.', 'patrons-tips' )
		), 'get_list_items' );
	}
	
	// Check if there are more entries to display and remove the exedent entry
	$item_nb  = count( $list_items );
	$has_more = $item_nb > $filters[ 'per_page' ];
	if( $has_more ) { 
		$list_items = array_slice( $list_items, 0, $filters[ 'per_page' ] );
	}
	
	// Concatenate the history list
	$html   = '';
	$offset = $filters[ 'offset' ] + $filters[ 'per_page' ];
	foreach( $list_items as $item_item ) { 
		$html  .= $item_item[ 'html' ]; 
		$offset = $item_item[ 'offset' ] + 1;
	}
	
	patips_send_json( array( 
		'status'   => 'success',
		'html'     => $html,
		'has_more' => $has_more ? 1 : 0,
		'offset'   => $offset
	), 'get_list_items' );
}
add_action( 'wp_ajax_patipsGetListItems', 'patips_controller_get_list_items' );
add_action( 'wp_ajax_nopriv_patipsGetListItems', 'patips_controller_get_list_items' );