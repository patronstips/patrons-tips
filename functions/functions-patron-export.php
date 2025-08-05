<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get export expiry delay (days)
 * @since 0.8.0
 * @return int
 */
function patips_get_export_expiration_delay() {
	return apply_filters( 'patips_export_expiration_delay', 31 );
}

/**
 * Patron data that can be exported
 * @since 0.8.0
 * @version 0.13.2
 * @return array
 */
function patips_get_patron_export_columns() {
	$columns = array(
		'patron_id'            => esc_html__( 'Patron ID', 'patrons-tips' ),
		'patron_status'        => esc_html__( 'Patron status', 'patrons-tips' ),
		'patron_creation_date' => esc_html__( 'Patron creation date', 'patrons-tips' ),
		'patron_nickname'      => esc_html__( 'Patron nickname', 'patrons-tips' ),
		
		'user_id'              => esc_html__( 'User ID', 'patrons-tips' ),
		'user_first_name'      => esc_html__( 'First name', 'patrons-tips' ),
		'user_last_name'       => esc_html__( 'Last name', 'patrons-tips' ),
		'user_email'           => esc_html__( 'Email', 'patrons-tips' ),
		'user_roles'           => esc_html__( 'User roles', 'patrons-tips' ),
		
		'tier_title'           => esc_html__( 'Tier title', 'patrons-tips' ),
		'tier_description'     => esc_html__( 'Tier description', 'patrons-tips' ),
		'tier_icon_url'        => esc_html__( 'Tier icon URL', 'patrons-tips' ),
		'tier_status'          => esc_html__( 'Tier status', 'patrons-tips' ),
		
		'history_tier_id'             => esc_html__( 'Tier ID', 'patrons-tips' ),
		'history_order_id'            => esc_html__( 'Order ID', 'patrons-tips' ),
		'history_subscription_id'     => esc_html__( 'Subscription ID', 'patrons-tips' ),
		'history_subscription_plugin' => esc_html__( 'Subscription plugin', 'patrons-tips' ),
		'history_date_start'          => esc_html__( 'Patronage date start', 'patrons-tips' ),
		'history_date_end'            => esc_html__( 'Patronage date end', 'patrons-tips' ),
		'history_period'              => esc_html__( 'Patronage period(s)', 'patrons-tips' ),
		'history_period_start'        => esc_html__( 'Patronage period start', 'patrons-tips' ),
		'history_period_end'          => esc_html__( 'Patronage period end', 'patrons-tips' ),
		'history_period_nb'           => esc_html__( 'Patronage period number', 'patrons-tips' ),
		'history_period_duration'     => esc_html__( 'Patronage period duration', 'patrons-tips' ),
		'history_status'              => esc_html__( 'Patronage status', 'patrons-tips' )
	);
	
	return apply_filters( 'patips_patron_export_columns_labels', $columns );
}


/**
 * Get patrons export default settings
 * @since 0.8.0
 * @version 0.13.2
 * @return array
 */
function patips_get_patron_export_default_settings() {
	$defaults = array(
		// CSV
		'csv_columns'     => array(
			'patron_id',
			'patron_nickname',
			'user_email',
			'tier_title',
			'history_date_start',
			'history_date_end',
			'history_period'
		),
		'csv_raw'         => 0,
		'csv_row_type'    => 'patron', // "patron" or "history"
		
		// Global
		'per_page'        => 200
	);
	
	return apply_filters( 'patips_patron_export_default_settings', $defaults );
}


/**
 * Get patrons export settings per user
 * @since 0.8.0
 * @param int $user_id
 * @return array
 */
function patips_get_patron_export_settings( $user_id = 0 ) {
	if( ! $user_id ) { 
		$user_id = get_current_user_id();
	}
	
	$user_settings = $user_id ? get_user_meta( $user_id, 'patips_patron_export_settings', true ) : array();
	$settings      = patips_format_patron_export_settings( $user_settings );
	
	return apply_filters( 'patips_patron_export_settings', $settings, $user_id );
}


/**
 * Format patron export settings
 * @since 0.8.0
 * @param array $raw_settings
 * @return array
 */
function patips_format_patron_export_settings( $raw_settings ) {
	$default_settings = patips_get_patron_export_default_settings();
	
	$keys_by_type = array( 
		'str_id' => array( 'csv_row_type' ),
		'int'    => array( 'per_page' ),
		'bool'   => array( 'csv_raw' ),
		'array'  => array( 'csv_columns' )
	);
	$settings = patips_sanitize_values( $default_settings, $raw_settings, $keys_by_type );
	
	// Format export type
	if( ! in_array( $settings[ 'csv_row_type' ], array( 'patron', 'history' ), true ) ) { 
		$settings[ 'csv_row_type' ] = $default_settings[ 'csv_row_type' ];
	}
	
	// Keep only allowed columns
	$allowed_columns           = patips_get_patron_export_columns();
	$settings[ 'csv_columns' ] = array_values( array_intersect( $settings[ 'csv_columns' ], array_keys( $allowed_columns ) ) );
	
	return apply_filters( 'patips_formatted_patron_export_settings', $settings, $raw_settings );
}


/**
 * Sanitize patron export settings
 * @since 0.8.0
 * @param array $raw_settings
 * @return array
 */
function patips_sanitize_patron_export_settings( $raw_settings ) {
	$default_settings = patips_get_patron_export_default_settings();
	
	$keys_by_type = array( 
		'str_id' => array( 'csv_row_type' ),
		'int'    => array( 'per_page' ),
		'bool'   => array( 'csv_raw' ),
		'array'  => array( 'csv_columns' )
	);
	$settings = patips_sanitize_values( $default_settings, $raw_settings, $keys_by_type );
	
	// Sanitize export type
	if( ! in_array( $settings[ 'csv_row_type' ], array( 'patron', 'history' ), true ) ) { 
		$settings[ 'csv_row_type' ] = $default_settings[ 'csv_row_type' ];
	}
	
	// Keep only allowed columns
	$allowed_columns           = patips_get_patron_export_columns();
	$settings[ 'csv_columns' ] = array_values( array_intersect( $settings[ 'csv_columns' ], array_keys( $allowed_columns ) ) );
	
	return apply_filters( 'patips_sanitized_patron_export_settings', $settings, $raw_settings );
}


/**
 * Convert a list of patrons to CSV format
 * @since 0.8.0
 * @param array $filters
 * @param array $args_raw
 * @return string
 */
function patips_convert_patrons_to_csv( $filters, $args_raw = array() ) {
	$default_settings = patips_get_patron_export_default_settings();
	$args_default = array( 
		'columns'  => $default_settings[ 'csv_columns' ],
		'raw'      => $default_settings[ 'csv_raw' ],
		'row_type' => $default_settings[ 'csv_row_type' ],
		'locale'   => '',
	);
	$args = wp_parse_args( $args_raw, $args_default );
	
	// Get column titles
	$column_titles = array();
	$allowed_columns = patips_get_patron_export_columns();
	foreach( $args[ 'columns' ] as $i => $column_name ) {
		// Remove unknown columns
		if( ! isset( $allowed_columns[ $column_name ] ) ) { 
			unset( $args[ 'columns' ][ $i ] );
			continue;
		}
		
		$column_titles[ $column_name ] = $allowed_columns[ $column_name ];
	}
	
	// Get patron items
	$export_args = array_merge( $args, array( 'filters' => $filters, 'type' => 'csv' ) );
	$items = patips_get_patrons_for_export( $export_args );
	
	return patips_generate_csv( $items, $column_titles );
}


/**
 * Generate CSV file
 * @since 0.8.0
 * @version 0.25.5
 * @param array $items
 * @param array $column_titles
 * @return string
 */
function patips_generate_csv( $items, $column_titles = array() ) {
	if( $column_titles ) {
		$items = array_merge( array( $column_titles ), $items );
	}
	if( ! $items ) { return ''; }
	
	$first_row    = reset( $items );
	$column_names = array_keys( $first_row );
	
	ob_start();
	
	$i = 0;
	foreach( $items as $item ) {
		if( $i ) { echo PHP_EOL; }
		++$i;
		
		$j = 0;
		foreach( $column_names as $column_name ) {
			if( $j ) { echo ','; }
			++$j;
			
			if( ! isset( $item[ $column_name ] ) ) { continue; }
			
			$value = html_entity_decode( wp_strip_all_tags( $item[ $column_name ] ) );
			
			if( strpos( $value, ',' ) !== false || strpos( $value, PHP_EOL ) !== false ) {
				$value = '"' . str_replace( '"', '""', $value ) . '"';
			}
			
			echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	return ob_get_clean();
}


/**
 * Get an array of patrons data formatted to be exported
 * @since 0.8.0
 * @version 0.26.0
 * @param array $args_raw
 * @return array
 */
function patips_get_patrons_for_export( $args_raw = array() ) {
	// Format args
	$default_args = array(
		'filters'  => array(),
		'columns'  => array(),
		'raw'      => true,
		'row_type' => 'patron',
		'type'     => 'csv',
		'locale'   => ''
	);
	$args = wp_parse_args( $args_raw, $default_args );
	
	// Get patrons and tiers
	$patrons = patips_get_patrons_data( $args[ 'filters' ] );
	$tiers   = patips_get_tiers_data( array( 'active' => false ) );
	
	// Check if we will need data
	$has_user_data = false;
	$has_attachment_data = false;
	foreach( $args[ 'columns' ] as $column_name ) {
		if( $column_name !== 'user_id' && substr( $column_name, 0, 5 ) === 'user_' ) { 
			$has_user_data = true;
		}
		if( substr( $column_name, 0, 9 ) === 'tier_icon' ) { 
			$has_attachment_data = true;
		}
	}
	$get_user_data = apply_filters( 'patips_patron_export_get_user_data', $has_user_data, $args );
	
	// Get users
	$users             = array();
	$user_ids          = array();
	$user_ids_by_email = array();
	$roles_names       = array();
	$tier_ids          = array();
	if( $get_user_data ) {
		foreach( $patrons as $patron ) {
			$user_id = $patron[ 'user_id' ] ? intval( $patron[ 'user_id' ] ) : 0;
			if( $user_id ) { 
				$user_ids[] = $user_id;
			}
			else if( $patron[ 'user_email' ] ) { 
				$user = is_email( $patron[ 'user_email' ] ) ? get_user_by( 'email', $patron[ 'user_email' ] ) : null;
				if( $user ) {
					$user_id = intval( $user->ID );
					$user_ids_by_email[ $patron[ 'user_email' ] ] = $user_id;
					$users[ $user_id ] = $user;
				}
			}
		}
		if( $user_ids ) {
			$users = $users + patips_get_users_data( array( 'include' => $user_ids ) );
		}
		
		$roles_names = patips_get_roles();
	}
	
	// Get date format
	$date_format     = $args[ 'raw' ] ? 'Y-m-d' : get_option( 'date_format' );
	$datetime_format = $args[ 'raw' ] ? 'Y-m-d H:i:s' : $date_format;
	$timezone        = patips_get_wp_timezone();
	$utc_timezone    = new DateTimeZone( 'UTC' );
	
	// Build patron list
	$patron_items = array();
	foreach( $patrons as $patron ) {
		// User
		$user_id    = $patron[ 'user_id' ] ? intval( $patron[ 'user_id' ] ) : 0;
		$user_email = is_email( $patron[ 'user_email' ] ) ? $patron[ 'user_email' ] : '';
		if( ! $user_id && $user_email ) {
			$user_id = isset( $user_ids_by_email[ $patron[ 'user_email' ] ] ) ? $user_ids_by_email[ $patron[ 'user_email' ] ] : 0;
		}
		$user = ! empty( $users[ $user_id ] ) ? $users[ $user_id ] : null;
		
		$patron_nickname = patips_generate_patron_nickname( $user ? $user : $patron );
		
		// Creation date
		$creation_date_raw = patips_sanitize_datetime( $patron[ 'creation_date' ] );
		$creation_date_dt  = new DateTime( $creation_date_raw, $utc_timezone );
		$creation_date_dt->setTimezone( $timezone );
		$creation_date = $creation_date_raw ? patips_format_datetime( $creation_date_dt->format( 'Y-m-d H:i:s' ), $date_format ) : '';
		
		$patron_data = array( 
			'patron_id'            => $patron[ 'id' ],
			'patron_status'        => $patron[ 'active' ] ? esc_html__( 'Active', 'patrons-tips' ) : esc_html__( 'Inactive', 'patrons-tips' ),
			'patron_creation_date' => $args[ 'raw' ] ? $creation_date_raw : $creation_date,
			'patron_nickname'      => $patron_nickname,
			
			'user_id'              => $user_id,
			'user_first_name'      => ! empty( $user->first_name ) ? $user->first_name : '',
			'user_last_name'       => ! empty( $user->last_name ) ? $user->last_name : '',
			'user_email'           => ! empty( $user->user_email ) ? $user->user_email : $user_email,
			'user_roles'           => ! empty( $user->roles ) ? implode( ', ', array_replace( array_combine( $user->roles, $user->roles ), array_intersect_key( $roles_names, array_flip( $user->roles ) ) ) ) : '',
		);
		
		// History data
		$history = $patron[ 'history' ] ? $patron[ 'history' ] : array( array() );
		
		if( $args[ 'row_type' ] !== 'history' ) {
			$last_entry = reset( $history );
			$history    = array( $last_entry );
		}
		
		foreach( $history as $history_entry ) {
			$history_date_start_raw   = ! empty( $history_entry[ 'date_start' ] ) ? patips_sanitize_date( $history_entry[ 'date_start' ] ) : '';
			$history_date_end_raw     = ! empty( $history_entry[ 'date_end' ] ) ? patips_sanitize_date( $history_entry[ 'date_end' ] ) : '';
			$history_period_start_raw = ! empty( $history_entry[ 'period_start' ] ) ? patips_sanitize_date( $history_entry[ 'period_start' ] ) : '';
			$history_period_end_raw   = ! empty( $history_entry[ 'period_end' ] ) ? patips_sanitize_date( $history_entry[ 'period_end' ] ) : '';
			$history_date_start       = $history_date_start_raw ? patips_format_datetime( $history_date_start_raw . ' 00:00:00', $date_format ) : '';
			$history_date_end         = $history_date_end_raw ? patips_format_datetime( $history_date_end_raw . ' 23:59:59', $date_format ) : '';
			$history_period_start     = $history_period_start_raw ? patips_format_datetime( $history_period_start_raw . ' 00:00:00', $date_format ) : '';
			$history_period_end       = $history_period_end_raw ? patips_format_datetime( $history_period_end_raw . ' 23:59:59', $date_format ) : '';
			
			// Tier
			$tier = ! empty( $history_entry[ 'tier_id' ] ) && ! empty( $tiers[ $history_entry[ 'tier_id' ] ] ) ? $tiers[ $history_entry[ 'tier_id' ] ] : array();

			$history_entry_data = array( 
				'tier_title'                  => ! empty( $tier[ 'title' ] ) ? $tier[ 'title' ] : '',
				'tier_description'            => ! empty( $tier[ 'description' ] ) ? $tier[ 'description' ] : '',
				'tier_icon_url'               => ! empty( $tier[ 'icon_id' ] ) && $has_attachment_data ? wp_get_attachment_url( $tier[ 'icon_id' ] ) : '',
				'tier_status'                 => ! empty( $tier[ 'active' ] ) ? esc_html__( 'Active', 'patrons-tips' ) : esc_html__( 'Inactive', 'patrons-tips' ),
				
				'history_tier_id'             => ! empty( $history_entry[ 'tier_id' ] ) ? $history_entry[ 'tier_id' ] : '',
				'history_order_id'            => ! empty( $history_entry[ 'order_id' ] ) ? $history_entry[ 'order_id' ] : '',
				'history_subscription_id'     => ! empty( $history_entry[ 'subscription_id' ] ) ? $history_entry[ 'subscription_id' ] : '',
				'history_subscription_plugin' => ! empty( $history_entry[ 'subscription_plugin' ] ) ? $history_entry[ 'subscription_plugin' ] : '',
				'history_date_start'          => $args[ 'raw' ] ? $history_date_start_raw : $history_date_start,
				'history_date_end'            => $args[ 'raw' ] ? $history_date_end_raw : $history_date_end,
				'history_period'              => $history_entry ? patips_get_patron_history_period_name( $history_entry ) : '',
				'history_period_start'        => $args[ 'raw' ] ? $history_period_start_raw : $history_period_start,
				'history_period_end'          => $args[ 'raw' ] ? $history_period_start_raw : $history_period_start,
				'history_period_nb'           => ! empty( $history_entry[ 'period_nb' ] ) ? $history_entry[ 'period_nb' ] : '',
				'history_period_duration'     => ! empty( $history_entry[ 'period_duration' ] ) ? $history_entry[ 'period_duration' ] : '',
				'history_status'              => ! empty( $history_entry[ 'active' ] ) ? esc_html__( 'Active', 'patrons-tips' ) : esc_html__( 'Inactive', 'patrons-tips' )
			);

			if( $args[ 'raw' ] ) {
				$history_entry_data[ 'tier_title' ]       = wp_strip_all_tags( $history_entry_data[ 'tier_title' ] );
				$history_entry_data[ 'tier_description' ] = wp_strip_all_tags( $history_entry_data[ 'tier_description' ] );
			}

			/**
			 * Third parties can add or change columns content, but do your best to optimize your process
			 */
			$patron_item = apply_filters( 'patips_patron_export_item', array_merge( $patron_data, $history_entry_data ), $patron, $user, $args );
			
			$item_key                  = $args[ 'row_type' ] === 'history' && ! empty( $history_entry[ 'id' ] ) ? $patron[ 'id' ] . '_' . $history_entry[ 'id' ] : $patron[ 'id' ];
			$patron_items[ $item_key ] = $patron_item;
			
			if( $args[ 'row_type' ] !== 'history' ) {
				break;
			}
		}
	}

	/**
	 * Third parties can add or change rows and columns, but do your best to optimize your process
	 */
	return apply_filters( 'patips_patron_export_items', $patron_items, $patrons, $users, $args );
}