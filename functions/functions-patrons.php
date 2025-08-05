<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// PATRON LISTS

/**
 * Display a comma separated list of patrons
 * @since 0.11.0 (was patips_display_current_month_members_list)
 * @version 0.25.5
 * @param array $patrons
 * @param boolean $return_array
 * @return string
 */
function patips_get_patron_list( $patrons, $return_array = false ) {
	$items      = array();
	$edit_links = apply_filters( 'patips_patron_list_edit_links', current_user_can( 'patips_edit_patrons' ) );
	$nicknames  = array();
	
	foreach( $patrons as $patron ) {
		$nickname = patips_get_patron_nickname( $patron );
		$nicknames[ $patron[ 'id' ] ] = $nickname;
		
		$item = apply_filters( 'patips_patron_list_item', '<em>' . esc_html( $nickname ) . '</em>', $patron, $nickname );
		
		if( $edit_links ) {
			$item = '<a href="' . esc_url( admin_url( 'admin.php?page=patips_patrons&action=edit&patron_id=' . $patron[ 'id' ] ) ) . '" >' . wp_kses_post( $item ) . '</a>';
		}
		
		$items[] = $item;
	}
	
	shuffle( $items );
	
	$items = apply_filters( 'patips_patron_list_items', $items, $patrons, $nicknames );
	$html  = apply_filters( 'patips_patron_list', $items ? '<div class="patips-patron-list">' . implode( ', ', $items ) . '</div>' : '', $patrons, $nicknames );
	
	return $return_array ? $items : $html;
}


/**
 * Display a comma separated list of patrons in a "Thank you" container
 * @since 0.11.0
 * @version 0.25.5
 * @param array $patrons
 * @param string $period_name
 * @return string
 */
function patips_get_patron_list_thanks( $patrons, $period_name = '' ) {
	// Get the patron list
	$patron_list = patips_get_patron_list( $patrons );
	
	$html = '';
	if( $patron_list ) {
		// Get patron sales page link
		$sales_page_id    = patips_get_option( 'patips_settings_general', 'sales_page_id' );
		$sales_page_url   = $sales_page_id ? get_permalink( $sales_page_id ) : '';
		$sales_page_label = esc_html__( 'patrons', 'patrons-tips' );
		$sales_page_link  = $sales_page_url ? '<a href="' . esc_url( $sales_page_url ) . '">' . $sales_page_label . '</a>' : $sales_page_label;
		
		// Display the patron list
		ob_start();
	?>
		<div class='patips-patron-list-thanks-container'>
			<div class='patips-patron-list-thanks-ribbon'>
			<?php 
				/* translators: %s = link to "patrons" page */
				echo sprintf( esc_html__( 'This content was published with the support of my %s, thank you very much!', 'patrons-tips' ), $sales_page_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</div>
			<fieldset>
				<legend>
					<span class='patips-patron-list-legend'><?php esc_html_e( 'Patrons', 'patrons-tips' ); ?></span>
					<?php if( $period_name ) { ?>
						<span class='patips-patron-list-legend-separator'> - </span>
						<span class='patips-patron-list-period'><?php echo esc_html( $period_name ); ?></span>
					<?php } ?>
				</legend>
				
				<div class='patips-patron-list-container'>
				<?php
					echo $patron_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				</div>
			</fieldset>
		</div>
	<?php
		$html = ob_get_clean();
	}
	
	$html = apply_filters( 'patips_patron_list_thanks', $html, $patron_list, $patrons, $period_name );
	
	return $html;
}




// FILTERS

/**
 * Get patron filters
 * @since 0.6.0
 * @version 1.0.4
 * @param array $filters
 * @return array
 */
function patips_get_default_patron_filters() {
	return apply_filters( 'patips_default_patron_filters', array(
		'in__id'          => array(),
		'user'            => '', 
		'user_ids'        => array(), 
		'user_emails'     => array(), 
		'tier_ids'        => array(), 
		'from'            => '',
		'to'              => '',
		'end_from'        => '',
		'end_to'          => '',
		'created_from'    => '',
		'created_to'      => '',
		'period_from'     => '', // The first month of the first period (e.g. "2024-08")
		'period_to'       => '', // The first month of the last period (e.g. "2024-10")
		'period'          => '', // Alias for "period_from" and "period_to" when they are equal
		'period_duration' => 0,
		'date'            => '',
		'current'         => false, // "false" to ignore, "true" to select only current patron
		'no_account'      => false, // "false" to ignore, 1 to select only patrons without account, 0 to select only patrons with an account
		'active'          => false, // "false" to ignore, 1 to select only active patrons, 0 to select only inactive patrons
		'order_by'        => array( 'id' ), 
		'order'           => 'desc',
		'offset'          => 0,
		'per_page'        => 0
	));
}


/**
 * Format patron filters
 * @since 0.6.0
 * @version 1.0.4
 * @param array $filters
 * @return array
 */
function patips_format_patron_filters( $filters = array() ) {
	$default_filters = patips_get_default_patron_filters();
	
	// "period" is an alias for "period_from" and "period_to" when they are equal
	if( ! empty( $filters[ 'period' ] ) && empty( $filters[ 'period_from' ] ) && empty( $filters[ 'period_to' ] ) ) {
		$filters[ 'period_from' ] = $filters[ 'period' ];
		$filters[ 'period_to' ]   = $filters[ 'period' ];
	}
	
	$formatted_filters = array();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) {
			$formatted_filters[ $filter ] = $default_value;
			continue;
		}

		$current_value = $filters[ $filter ];
		
		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'in__id', 'user_ids', 'tier_ids' ), true ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = patips_ids_to_array( $current_value, false );
			
		} else if( in_array( $filter, array( 'user_emails' ), true ) ) {
			if( is_string( $current_value ) )  { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = array_values( array_filter( array_unique( array_map( 'sanitize_email', array_filter( $current_value, 'is_string' ) ) ) ) );
			
		} else if( in_array( $filter, array( 'user' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = sanitize_text_field( $current_value );
			
		} else if( in_array( $filter, array( 'date', 'from', 'to', 'end_from', 'end_to' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_sanitize_date( $current_value );
		
		} else if( in_array( $filter, array( 'created_from', 'created_to' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_sanitize_datetime( $current_value );
			
		} else if( in_array( $filter, array( 'period', 'period_from', 'period_to' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_sanitize_date( $current_value );
			
			// Set the date to the first day of the period
			if( $current_value ) {
				$current_value = substr( $current_value, 0, 7 ) . '-01';
			}
			
		} else if( in_array( $filter, array( 'period_duration' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
			
		} else if( in_array( $filter, array( 'active', 'no_account' ), true ) ) {
			     if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) )               { $current_value = 0; }
			else if( in_array( $current_value, array( false, 'false' ), true ) )       { $current_value = false; }
			if( ! in_array( $current_value, array( 0, 1, false ), true ) )             { $current_value = $default_value; }
		
		} else if( in_array( $filter, array( 'current' ), true ) ) {
			     if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )   { $current_value = true; }
			else if( in_array( $current_value, array( false, 'false', 0, '0' ), true ) ) { $current_value = false; }
			if( ! in_array( $current_value, array( true, false ), true ) )               { $current_value = $default_value; }
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 'id', 'user_id', 'user_email', 'creation_date', 'active' );
			if( is_string( $current_value ) && $current_value !== '' ) { 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_str_ids_to_array( $current_value );
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
		}
		
		$formatted_filters[ $filter ] = $current_value;
	}
	
	return apply_filters( 'patips_formatted_patron_filters', $formatted_filters, $filters );
}


/**
 * Get patron history filters
 * @since 0.8.0
 * @version 1.0.4
 * @param array $filters
 * @return array
 */
function patips_get_default_patron_history_filters() {
	return apply_filters( 'patips_default_patron_history_filters', array(
		'in__id'              => array(),
		'patron_ids'          => array(), 
		'tier_ids'            => array(), 
		'order_ids'           => array(), 
		'order_item_ids'      => array(), 
		'subscription_ids'    => array(), 
		'subscription_plugin' => '', 
		'from'                => '',
		'to'                  => '',
		'end_from'            => '',
		'end_to'              => '',
		'period_from'         => '', // The first month of the first period (e.g. "2024-08")
		'period_to'           => '', // The first month of the last period (e.g. "2024-10")
		'period'              => '', // Alias for "period_from" and "period_to" when they are equal
		'period_duration'     => 0,
		'date'                => '',
		'current'             => false, // "false" to ignore, "true" to select only current patron
		'active'              => false, // "false" to ignore, 1 to select only active entries, 0 to select only inactive entries
		'order_by'            => array( 'patron_id', 'date_start', 'date_end', 'tier_id' ), 
		'order'               => 'desc',
		'offset'              => 0,
		'per_page'            => 0
	));
}


/**
 * Format patron history filters
 * @since 0.8.0
 * @version 1.0.4
 * @param array $filters
 * @return array
 */
function patips_format_patron_history_filters( $filters = array() ) {
	$default_filters = patips_get_default_patron_history_filters();
	
	// "period" is an alias for "period_from" and "period_to" when they are equal
	if( ! empty( $filters[ 'period' ] ) && empty( $filters[ 'period_from' ] ) && empty( $filters[ 'period_to' ] ) ) {
		$filters[ 'period_from' ] = $filters[ 'period' ];
		$filters[ 'period_to' ]   = $filters[ 'period' ];
	}
	
	$formatted_filters = array();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) {
			$formatted_filters[ $filter ] = $default_value;
			continue;
		}

		$current_value = $filters[ $filter ];
		
		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'in__id', 'patron_ids', 'tier_ids', 'order_ids', 'order_item_ids', 'subscription_ids' ), true ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = patips_ids_to_array( $current_value, false );
			
		} else if( in_array( $filter, array( 'subscription_plugin' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = sanitize_title_with_dashes( $current_value );
			
		} else if( in_array( $filter, array( 'date', 'from', 'to', 'end_from', 'end_to' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_sanitize_date( $current_value );
		
		} else if( in_array( $filter, array( 'period', 'period_from', 'period_to' ), true ) && $current_value ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_sanitize_date( $current_value );
			
			// Set the date to the first day of the period
			if( $current_value ) {
				$current_value = substr( $current_value, 0, 7 ) . '-01';
			}
			
		} else if( in_array( $filter, array( 'period_duration' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
			
		} else if( in_array( $filter, array( 'active' ), true ) ) {
			     if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) )               { $current_value = 0; }
			else if( in_array( $current_value, array( false, 'false' ), true ) )       { $current_value = false; }
			if( ! in_array( $current_value, array( 0, 1, false ), true ) )             { $current_value = $default_value; }
		
		} else if( in_array( $filter, array( 'current' ), true ) ) {
			     if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )   { $current_value = true; }
			else if( in_array( $current_value, array( false, 'false', 0, '0' ), true ) ) { $current_value = false; }
			if( ! in_array( $current_value, array( true, false ), true ) )               { $current_value = $default_value; }
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 'id', 'patron_id', 'tier_id', 'order_id', 'date_start', 'date_end', 'period_start', 'period_end', 'period_nb', 'period_duration', 'active' );
			if( is_string( $current_value ) && $current_value !== '' ) { 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_str_ids_to_array( $current_value );
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
		}
		
		$formatted_filters[ $filter ] = $current_value;
	}
	
	return apply_filters( 'patips_formatted_patron_history_filters', $formatted_filters, $filters );
}


/**
 * Format patron filters manually input
 * @since 0.8.0
 * @version 0.17.0
 * @param array $filters
 * @return array
 */
function patips_format_string_patron_filters( $filters = array() ) {
	// Format arrays
	$formatted_filters = array();
	$int_keys = array( 'in__id', 'user_ids', 'tier_ids', 'patron_ids' );
	$str_keys = array( 'order_by', 'columns' );

	foreach( array_merge( $int_keys, $str_keys ) as $key ) {
		$formatted_filters[ $key ] = array();
		if( empty( $filters[ $key ] ) )       { continue; }
		if( is_array( $filters[ $key ] ) )    { $formatted_filters[ $key ] = $filters[ $key ]; continue; }
		if( ! is_string( $filters[ $key ] ) ) { continue; }

		$formatted_value = preg_replace( array(
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', $filters[ $key ] );

		if( in_array( $key, $int_keys, true ) ) { 
			$formatted_filters[ $key ] = explode( ',', preg_replace( array(
				'/[^\d,]/',    // Matches anything that's not a comma or number.
			), '', $formatted_value ) );
			$formatted_filters[ $key ] = patips_ids_to_array( $formatted_filters[ $key ], false ); 
		}
		if( in_array( $key, $str_keys, true ) ) { 
			$formatted_filters[ $key ] = explode( ',', $formatted_value );
			$formatted_filters[ $key ] = patips_str_ids_to_array( $formatted_filters[ $key ] ); 
		}
	}
	
	// Convert relative date format to date
	$date_keys     = array( 'from', 'to', 'end_from', 'end_to', 'period', 'period_from', 'period_to', 'date' );
	$datetime_keys = array( 'created_from', 'created_to' );
	$timezone      = patips_get_wp_timezone();
	
	foreach( $date_keys as $key ) {
		$date_sanitized = ! empty( $filters[ $key ] ) ? sanitize_text_field( $filters[ $key ] ) : '';
		if( $date_sanitized && ! patips_sanitize_date( $date_sanitized ) && (bool) strtotime( $date_sanitized ) ) {
			$dt = new DateTime( $date_sanitized, $timezone );
			$formatted_filters[ $key ] = $dt->format( 'Y-m-d' );
		}
	}
	foreach( $datetime_keys as $key ) {
		$date_sanitized = ! empty( $filters[ $key ] ) ? sanitize_text_field( $filters[ $key ] ) : '';
		if( $date_sanitized && ! patips_sanitize_datetime( $date_sanitized ) && (bool) strtotime( $date_sanitized ) ) {
			$is_to = strpos( $key, '_to' ) !== false;
			$dt    = new DateTime( $date_sanitized, $timezone );
			$formatted_filters[ $key ] = $is_to && $dt->format( 'H:i:s' ) === '00:00:00' ? $dt->format( 'Y-m-d' ) . ' 23:59:59' : $dt->format( 'Y-m-d H:i:s' );
		}
	}
	
	return apply_filters( 'patips_formatted_string_patron_filters', array_merge( $filters, $formatted_filters ), $filters );
}




// DATA

/**
 * Get default patron data
 * @since 0.6.0
 * @version 0.8.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_default_patron_data( $context = 'view' ) {
	return apply_filters( 'patips_default_patron_data', array( 
		'id'            => 0,
		'nickname'      => '',
		'user_id'       => 0,
		'user_email'    => '',
		'creation_date' => '',
		'active'        => 1,
		'history'       => array()
	), $context );
}


/**
 * Get default patron meta
 * @since 0.6.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_default_patron_meta( $context = 'view' ) {
	return apply_filters( 'patips_default_patron_meta', array(), $context );
}


/**
 * Get patrons data and metadata
 * @since 0.6.0
 * @version 0.25.2
 * @param array $filters
 * @param array $history_filters
 * @param boolean $raw
 * @return array
 */
function patips_get_patrons_data( $filters = array(), $history_filters = array(), $raw = false ) {
	$patron_filters = patips_format_patron_filters( $filters );
	$patrons_raw    = patips_get_patrons( $patron_filters );
	if( ! $patrons_raw ) { return array(); }
	
	$patron_ids   = array_keys( $patrons_raw );
	$patrons_meta = patips_get_metadata( 'patron', $patron_ids );
	
	$use_cache = false;
	if( ! $history_filters ) {
		$use_patron_filters = array( 'tier_ids', 'from', 'to', 'end_from', 'end_to', 'period', 'period_from', 'period_to', 'period_duration', 'date', 'current' );
		$history_filters    = array_intersect_key( $filters, array_flip( $use_patron_filters ) );
		$use_cache          = ! $history_filters && ! $raw;
		$history_filters[ 'active' ] = $raw ? false : 1;
	}
	$history_filters[ 'patron_ids' ] = $patron_ids;
	$patrons_history = patips_get_patrons_history_data( $patron_ids, $history_filters, true );
	
	$patrons = array();
	foreach( $patrons_raw as $patron_id => $patron_raw ) {
		$patron = (array) $patron_raw;
		
		// Patron metadata
		$patron_meta = is_array( $patrons_meta ) && isset( $patrons_meta[ $patron_id ] ) ? $patrons_meta[ $patron_id ] : array();
		$patron      = array_merge( $patron, $patron_meta );
		
		// Patron history
		$patron_history      = isset( $patrons_history[ $patron_id ] ) ? array_values( $patrons_history[ $patron_id ] ) : array();
		$patron[ 'history' ] = $patron_history;
		
		// Format patron data
		$patron_raw_data = $patron;
		if( ! $raw ) {
			$patron = patips_format_patron_data( $patron );
		}
		
		$patrons[ $patron_id ] = apply_filters( 'patips_patron_data', $patron, $patron_raw_data, $patron_id, $history_filters, $raw );
		
		// Update cache
		if( $use_cache ) {
			wp_cache_set( 'patron_' . $patron_id, $patrons[ $patron_id ], 'patrons-tips' );
		}
	}
	
	return $patrons;
}


/**
 * Get patron data and metadata
 * @since 0.6.0
 * @version 0.13.3
 * @param int $patron_id
 * @param array $history_filters
 * @param boolean $raw
 * @return array
 */
function patips_get_patron_data( $patron_id, $history_filters = array(), $raw = false ) {
	// Get the cached value
	$use_cache = ! $history_filters && ! $raw;
	$patron    = $use_cache ? wp_cache_get( 'patron_' . $patron_id, 'patrons-tips' ) : false;
	
	if( $patron === false ) {
		$patrons = patips_get_patrons_data( array( 'in__id' => array( $patron_id ) ), $history_filters, $raw );
		$patron  = isset( $patrons[ $patron_id ] ) ? $patrons[ $patron_id ] : array();
		
		// Update cache
		if( $use_cache && ! $patron ) {
			wp_cache_set( 'patron_' . $patron_id, $patron, 'patrons-tips' );
		}
	}
	
	return $patron;
}


/**
 * Get patron data by user ID or email
 * @since 0.10.0
 * @version 0.23.1
 * @param int|string $user_id User ID or User email
 * @return array
 */
function patips_get_user_patron_data( $user_id = 'current' ) {
	// Get current user ID
	if( $user_id === 'current' ) {
		$user_id = get_current_user_id();
	}
	
	// Get user ID by email
	$user_email = '';
	if( is_email( $user_id ) ) {
		$user_email = $user_id;
		$user       = get_user_by( 'email', $user_email );
		$user_id    = $user ? intval( $user->ID ) : 0;
	}
	
	$user_uid = $user_id ? $user_id : $user_email;
	if( ! $user_uid ) { return array(); }
	
	// Get the cached value
	$cache = wp_cache_get( 'patron_user_' . $user_uid, 'patrons-tips' );
	if( $cache !== false ) { return $cache; }
	
	$patrons = array();
	
	// Get patron by user ID
	if( $user_id ) {
		$filters = array( 'user_ids' => array( $user_id ), 'active' => 1 );
		$patrons = patips_get_patrons_data( $filters );
	}
	
	// Get patron by user email
	if( ! $patrons && $user_email ) {
		$filters = array( 'user_emails' => array( $user_email ), 'active' => 1 );
		$patrons = patips_get_patrons_data( $filters );
	}
	
	$patron = $patrons ? reset( $patrons ) : array();
	
	// Update patron user_id and remove user_email
	$patron = $patron ? patips_update_patron_user( $patron ) : array();
	
	// Update cache
	wp_cache_set( 'patron_user_' . $user_uid, $patron, 'patrons-tips' );
	
	return $patron;
}


/**
 * Get patron by user ID or by user email
 * @since 0.13.4
 * @version 0.23.1
 * @param int $user_id
 * @param string $user_email
 * @param boolean $create_patron Create the patron if it doesn't exist
 * @return array
 */
function patips_get_patron_by_user_id_or_email( $user_id, $user_email = '', $create_patron = true ) {
	$user_id    = is_numeric( $user_id ) ? intval( $user_id ) : 0;
	$user_email = is_string( $user_email ) ? sanitize_email( $user_email ) : '';
	if( ! $user_id && ! $user_email ) { return array(); }
	
	// Find patron by user ID
	$patron = $user_id ? patips_get_user_patron_data( $user_id ) : array();
	
	// Find patron by user email
	if( ! $patron && $user_email ) {
		$patron = patips_get_user_patron_data( $user_email );
	}
	
	// Create the patron
	if( ! $patron && $create_patron ) {
		$patron_data = patips_sanitize_patron_data( $user_id ? array( 'user_id' => $user_id ) : array( 'user_email' => $user_email ) );
		$patron_id   = patips_create_patron( $patron_data );
		$patron      = $patron_id ? patips_get_patron_data( $patron_id ) : array();
		
		if( $patron ) {
			$user_uid = $user_id ? $user_id : $user_email;
			wp_cache_delete( 'patron_user_' . $user_id, 'patrons-tips' );
			wp_cache_delete( 'patron_user_' . $user_email, 'patrons-tips' );
			wp_cache_set( 'patron_user_' . $user_uid, $patron, 'patrons-tips' );
		}
	}
	
	return $patron ? $patron : array();
}


/**
 * Format patron data
 * @since 0.6.0
 * @version 0.25.0
 * @param array $raw_patron_data
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_format_patron_data( $raw_patron_data = array(), $context = 'view' ) {
	if( ! is_array( $raw_patron_data ) ) { $raw_patron_data = array(); }
	
	$default_data = array_merge( patips_get_default_patron_data( $context ), patips_get_default_patron_meta( $context ) );
	$keys_by_type = array( 
		'int'       => array( 'id', 'user_id' ),
		'email'     => array( 'user_email' ),
		'datetime'  => array( 'creation_date' ),
		'bool'      => array( 'active' ),
		'array'     => array( 'history' )
	);
	$patron_data = patips_sanitize_values( $default_data, $raw_patron_data, $keys_by_type );
	
	// Nickname (PRO)
	$patron_data[ 'nickname' ] = $default_data[ 'nickname' ];
	
	// History
	$patron_data[ 'history' ] = patips_format_patron_history_data( $patron_data[ 'history' ], $patron_data[ 'id' ], $context );
	
	return apply_filters( 'patips_formatted_patron_data', $patron_data, $raw_patron_data, $context );
}


/**
 * Sanitize patron data
 * @since 0.6.0
 * @verison 0.25.0
 * @param array $raw_patron_data
 * @return array
 */
function patips_sanitize_patron_data( $raw_patron_data = array() ) {
	if( ! is_array( $raw_patron_data ) ) { $raw_patron_data = array(); }
	
	$default_data = array_merge( patips_get_default_patron_data( 'edit' ), patips_get_default_patron_meta( 'edit' ) );
	$keys_by_type = array( 
		'int'      => array( 'id', 'user_id' ),
		'email'    => array( 'user_email' ),
		'datetime' => array( 'creation_date' ),
		'bool'     => array( 'active' ),
		'array'    => array( 'history' )
	);
	$patron_data = patips_sanitize_values( $default_data, $raw_patron_data, $keys_by_type );
	
	// Nickname (PRO)
	$patron_data[ 'nickname' ] = $default_data[ 'nickname' ];
	
	// Validate user ID and user email
	$user_object              = $patron_data[ 'user_id' ] ? get_user_by( 'id', $patron_data[ 'user_id' ] ) : ( $patron_data[ 'user_email' ] ? get_user_by( 'email', $patron_data[ 'user_email' ] ) : null );
	$patron_data[ 'user_id' ] = $user_object ? $user_object->ID : 0;
	if( $user_object ) { 
		$patron_data[ 'user_email' ] = '';
	}
	
	// History
	$patron_data[ 'history' ] = patips_sanitize_patron_history_data( $patron_data[ 'history' ], $patron_data[ 'id' ] );
	
	return apply_filters( 'patips_sanitized_patron_data', $patron_data, $raw_patron_data );
}


/**
 * Get default patron history data
 * @since 0.6.0
 * @version 0.22.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_default_patron_history_data( $context = 'view' ) {
	return apply_filters( 'patips_default_patron_history_data', array( 
		'id'                  => 0,
		'patron_id'           => 0,
		'tier_id'             => 0,
		'date_start'          => '',
		'date_end'            => '',
		'period_start'        => '',
		'period_end'          => '',
		'period_nb'           => 1,
		'period_duration'     => patips_get_default_period_duration(),
		'order_id'            => 0,
		'order_item_id'       => 0,
		'subscription_id'     => 0,
		'subscription_plugin' => '',
		'active'              => 1
	), $context );
}


/**
 * Get default patron history meta
 * @since 0.6.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_default_patron_history_meta( $context = 'view' ) {
	return apply_filters( 'patips_default_patron_history_meta', array(), $context );
}


/**
 * Get patrons history data and metadata
 * @since 0.13.3
 * @param array $patron_ids
 * @param array $filters
 * @param boolean $raw
 * @return array
 */
function patips_get_patrons_history_data( $patron_ids = array(), $filters = array(), $raw = false ) {
	if( $patron_ids ) {
		$filters[ 'patron_ids' ] = $patron_ids;
	}
	if( ! $filters ) {
		$filters[ 'active' ] = $raw ? false : 1;
	}
	
	$filters             = patips_format_patron_history_filters( $filters );
	$patrons_history_raw = patips_get_patrons_history( $filters );
	if( ! $patrons_history_raw ) { return array(); }
	
	$history_ids = array();
	foreach( $patrons_history_raw as $patron_id => $patron_history_raw ) {
		$history_ids = array_merge( $history_ids, array_keys( $patron_history_raw ) );
	}
	$history_ids = patips_ids_to_array( $history_ids );
	
	$patrons_history_meta = $history_ids ? patips_get_metadata( 'patron_history', $history_ids ) : array();
	
	$patrons_history = array();
	foreach( $patrons_history_raw as $patron_id => $patron_history_raw ) {
		$patron_history = $patron_history_raw;
		
		// Merge meta
		$patron_history_meta = is_array( $patrons_history_meta ) ? array_intersect_key( $patrons_history_meta, $patron_history_raw ) : array();
		$patron_history = array_replace_recursive( $patron_history, $patron_history_meta );
		
		// Format data
		$patron_history_raw_data = $patron_history;
		if( ! $raw ) {
			$patron_history = patips_format_patron_history_data( $patron_history );
		}
		
		$patrons_history[ $patron_id ] = apply_filters( 'patips_patron_history_data', $patron_history, $patron_history_raw_data, $patron_id, $filters, $raw );
	}
	
	return $patrons_history;
}


/**
 * Get patron history data and metadata
 * @since 0.13.2
 * @param array $patron_id
 * @param array $filters
 * @param boolean $raw
 * @return array
 */
function patips_get_patron_history_data( $patron_id = array(), $filters = array(), $raw = false ) {
	$patrons_history = patips_get_patrons_history_data( array( $patron_id ), $filters, $raw );
	return isset( $patrons_history[ $patron_id ] ) ? $patrons_history[ $patron_id ] : array();
}


/**
 * Format patron data
 * @since 0.10.0
 * @version 0.13.3
 * @param array $raw_history_entries
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_format_patron_history_data( $raw_history_entries = array(), $patron_id = 0, $context = 'view' ) {
	if( ! is_array( $raw_history_entries ) ) { $raw_history_entries = array(); }
	
	$default_history_data = array_merge( 
		patips_get_default_patron_history_data( $context ), 
		patips_get_default_patron_history_meta( $context )
	);
	
	// Format common values
	$history_keys_by_type = array( 
		'int'    => array( 'id', 'patron_id', 'tier_id', 'period_nb', 'period_duration', 'order_id', 'order_item_id', 'subscription_id' ),
		'str_id' => array( 'subscription_plugin' ),
		'date'   => array( 'date_start', 'date_end', 'period_start', 'period_end' ),
		'bool'   => array( 'active' )
	);
	
	$history = array();
	if( $raw_history_entries ) {
		foreach( $raw_history_entries as $raw_history_entry ) {
			$history_entry = patips_sanitize_values( $default_history_data, (array) $raw_history_entry, $history_keys_by_type );
			
			if( $patron_id && $history_entry[ 'patron_id' ] !== $patron_id ) {
				continue;
			}
			if( ! $history_entry[ 'tier_id' ] || ! $history_entry[ 'date_start' ] || ! $history_entry[ 'date_end' ] ) {
				continue;
			}
			
			$history[] = apply_filters( 'patips_formatted_patron_history_entry_data', $history_entry, $raw_history_entry, $patron_id, $context );
		}
		$history = array_values( array_filter( $history ) );
	}
	
	return apply_filters( 'patips_formatted_patron_history_data', $history, $raw_history_entries, $patron_id, $context );
}


/**
 * Sanitize patron history data
 * @since 0.10.0
 * @version 0.13.3
 * @param array $raw_history_entries
 * @param int $patron_id
 * @return array
 */
function patips_sanitize_patron_history_data( $raw_history_entries = array(), $patron_id = 0 ) {
	if( ! is_array( $raw_history_entries ) ) { $raw_history_entries = array(); }
	
	$default_history_data = array_merge( 
		patips_get_default_patron_history_data( 'edit' ), 
		patips_get_default_patron_history_meta( 'edit' )
	);
	
	$history_keys_by_type = array( 
		'int'    => array( 'id', 'patron_id', 'tier_id', 'period_nb', 'period_duration', 'order_id', 'order_item_id', 'subscription_id' ),
		'str_id' => array( 'subscription_plugin' ),
		'date'   => array( 'date_start', 'date_end', 'period_start', 'period_end' ),
		'bool'   => array( 'active' )
	);
	
	$history = array();
	if( $raw_history_entries ) {
		foreach( $raw_history_entries as $raw_history_entry ) {
			$history_entry = patips_sanitize_values( $default_history_data, $raw_history_entry, $history_keys_by_type );
			if( ! $history_entry[ 'tier_id' ] || ! $history_entry[ 'date_start' ] ) { continue; }
			
			if( $patron_id )                            { $history_entry[ 'patron_id' ] = $patron_id; }
			if( ! $history_entry[ 'period_nb' ] )       { $history_entry[ 'period_nb' ] = 1; }
			if( ! $history_entry[ 'period_duration' ] ) { $history_entry[ 'period_duration' ] = 1; }
			
			if( ! $history_entry[ 'tier_id' ] || ! $history_entry[ 'date_start' ] || ! $history_entry[ 'date_end' ] || ! $history_entry[ 'period_start' ] || ! $history_entry[ 'period_end' ] ) {
				continue;
			}
			
			$history[] = apply_filters( 'patips_sanitized_patron_history_entry_data', $history_entry, $raw_history_entry, $patron_id );
		}
		$history = array_values( array_filter( $history ) );
	}
	
	return apply_filters( 'patips_sanitized_patron_history_data', $history, $raw_history_entries, $patron_id );
}


/**
 * Get WP user associated to a patron
 * @since 0.13.0
 * @param array $patron
 * @return WP_User|false
 */
function patips_get_patron_user( $patron ) {
	$user = false;
	
	// Get user by ID
	if( $patron[ 'user_id' ] ) {
		$user = get_user_by( 'id', $patron[ 'user_id' ] );
	}
	
	// Get user by email
	if( ! $user && is_email( $patron[ 'user_email' ] ) ) {
		$user = get_user_by( 'email', $patron[ 'user_email' ] );
	}
	
	return $user;
}


/**
 * Get WP user ID associated to a patron
 * @since 0.13.0
 * @param array $patron
 * @return int
 */
function patips_get_patron_user_id( $patron ) {
	$user_id = 0;
	
	$user = patips_get_patron_user( $patron );
	
	if( $user ) {
		$user_id = $user->ID;
	}
	else if( $patron[ 'user_id' ] ) {
		$user_id = $patron[ 'user_id' ];
	}
	
	return intval( $user_id );
}


/**
 * Get WP user email associated to a patron
 * @since 0.13.0
 * @param array $patron
 * @return string
 */
function patips_get_patron_user_email( $patron ) {
	$user_email = '';
	
	$user = patips_get_patron_user( $patron );
	
	if( $user ) {
		$user_email = $user->user_email;
	}
	else if( $patron[ 'user_email' ] ) {
		$user_email = $patron[ 'user_email' ];
	}
	
	return is_email( $user_email ) ? $user_email : '';
}


/**
 * Update patron user ID and email
 * @since 0.23.1
 * @param array $patron
 * @return array
 */
function patips_update_patron_user( $patron ) {
	$user = $patron && ( ! $patron[ 'user_id' ] || $patron[ 'user_email' ] ) ? patips_get_patron_user( $patron ) : null;
	if( ! ( $user && ! empty( $user->ID ) ) ) { return $patron; }
	
	$patron_data                 = patips_sanitize_patron_data( $patron );
	$patron_data[ 'id' ]         = $patron[ 'id' ];
	$patron_data[ 'user_id' ]    = intval( $user->ID );
	$patron_data[ 'user_email' ] = 'null';

	$updated = patips_update_patron( $patron_data );
	
	if( $updated ) {
		$patron[ 'user_id' ]    = intval( $user->ID );
		$patron[ 'user_email' ] = '';

		wp_cache_delete( 'patron_' . $patron[ 'id' ], 'patrons-tips' );
		wp_cache_delete( 'patron_user_' . intval( $user->ID ), 'patrons-tips' );
		wp_cache_delete( 'patron_user_' . $patron[ 'user_email' ], 'patrons-tips' );
	}
	
	return $patron;
}


/**
 * Get patron nickname
 * @since 0.8.0
 * @version 0.12.0
 * @param array $patron
 * @param string $fallback
 * @return string
 */
function patips_get_patron_nickname( $patron, $fallback = 'front' ) {
	$nickname = ! empty( $patron[ 'nickname' ] ) ? $patron[ 'nickname' ] : patips_generate_patron_nickname( $patron, $fallback );
	return apply_filters( 'patips_patron_nickname', $nickname, $patron, $fallback );
}


/**
 * Generate a patron nickname
 * @since 0.8.0
 * @version 0.25.3
 * @param WP_User|array $object WP_User or Patron array
 * @param string $fallback
 * @return string
 */
function patips_generate_patron_nickname( $object, $fallback = 'front' ) {
	$nickname   = is_array( $object ) && ! empty( $object[ 'nickname' ] ) ? $object[ 'nickname' ] : '';
	$email      = is_array( $object ) && ! empty( $object[ 'user_email' ] ) && is_email( $object[ 'user_email' ] ) ? $object[ 'user_email' ] : '';
	$user_id    = is_array( $object ) && ! empty( $object[ 'user_id' ] ) ? intval( $object[ 'user_id' ] ) : 0;
	$first_name = '';
	$last_name  = '';
	$user       = null;
	
	// Try to find the user by ID or email
	if( ! $nickname ) {
		if( $object instanceof WP_User ) {
			$user = $object;
		} else if( $user_id ) {
			$user = get_user_by( 'id', $user_id );
		}
	}
	
	// Get user patron nickname, or other details
	if( ! $nickname && ( $user instanceof WP_User ) ) {
		$nickname   = ! empty( $user->patron_nickname ) ? $user->patron_nickname : '';
		$first_name = trim( $user->first_name );
		$last_name  = trim( $user->last_name );
		$email      = $user->user_email;
		$user_id    = $user->ID;
	}
	
	// Captitalized first name and the first letter of the last name
	if( ! $nickname && ( $first_name || $last_name ) ) {
		// Captitalize first name
		if( $first_name ) {
			$nickname = ucfirst( strtolower( $first_name ) );
		}
		if( $last_name ) {
			// First letter of the last name
			if( $first_name ) {
				$nickname .= ' ' . strtoupper( substr( $last_name , 0, 1 ) ) . '.';
			} 
			// If there is no first name, captitalize last name
			else {
				$nickname = ucfirst( strtolower( $last_name ) );
			}
		}
	}
	
	$nickname = apply_filters( 'patips_pre_generate_patron_nickname', $nickname, $user, $object );
	
	// Try to make a nickname with the first part of the email address
	if( ! $nickname && $email ) {
		$at_pos     = strpos( $email, '@' );
		$part_str   = trim( str_replace( array( '-', '_' ), ' ', sanitize_title_with_dashes( substr( $email, 0, $at_pos ) ) ) );
		$part_array = array_filter( explode( ' ', $part_str ) );

		$i = 1;
		foreach( $part_array as $part ) {
			if( $i > 1 ) {
				$nickname .= ' ';
			}
			if( $i < count( $part_array ) || $i === 1 ) {
				$nickname .= ucfirst( strtolower( $part ) );
			} else {
				$nickname .= substr( strtoupper( $part ), 0, 1 ) . '.';
			}
			$i++;
		}
	}
	
	// Fallback string
	if( ! $nickname && $fallback ) {
		if( $fallback === 'admin' ) {
			$patron_id = is_array( $object ) && ! empty( $object[ 'id' ] ) ? intval( $object[ 'id' ] ) : 0;
			if( $patron_id ) {
				/* translators: %s is the patron ID */ 
				$nickname = sprintf( esc_html__( 'Patron #%s', 'patrons-tips' ), $patron_id );
			}
		}
		if( $fallback === 'front' || ( $fallback === 'admin' && ! $nickname ) ) {
			$nickname = esc_html__( 'Anonymous patron', 'patrons-tips' );
		} 
		else if( ! in_array( $fallback, array( 'front', 'admin' ), true ) ) {
			$nickname = $fallback;
		}
	}
	
	return apply_filters( 'patips_generated_patron_nickname', $nickname, $user, $object, $fallback );
}


/**
 * Get the period name for a patron history entry
 * @since 0.10.0
 * @version 0.13.0
 * @param array $history_entry
 * @return string
 */
function patips_get_patron_history_period_name( $history_entry ) {
	$period = $history_entry[ 'period_nb' ] === 1 ? patips_get_period_by_date( $history_entry[ 'period_start' ] ) : array(
		'start'    => $history_entry[ 'period_start' ] . ' 00:00:00',
		'end'      => $history_entry[ 'period_end' ] . ' 23:59:59',
		'duration' => $history_entry[ 'period_duration' ],
		'index'    => 0,
		'count'    => $history_entry[ 'period_nb' ]
	);
	
	$period_name = patips_get_period_name( $period );
	
	return apply_filters( 'patips_patron_history_period_name', $period_name, $history_entry );
}


/**
 * Get total income based on patron history
 * @since 0.23.2
 * @version 0.26.0
 * @param array $patrons
 * @param boolean $include_manual
 * @return float|int
 */
function patips_get_total_income_based_on_patrons_history( $patrons, $include_manual = true ) {
	$tiers = patips_get_tiers_data( array( 'active' => false ) );
	
	$total = 0;
	foreach( $patrons as $patron ) {
		foreach( $patron[ 'history' ] as $history_entry ) {
			if( empty( $history_entry[ 'active' ] ) ) { continue; }
			
			$tier_id = $history_entry[ 'tier_id' ];
			$tier    = isset( $tiers[ $tier_id ] ) ? $tiers[ $tier_id ] : array();
			if( empty( $tier[ 'price' ] ) ) { continue; }
			
			$is_manual = empty( $history_entry[ 'order_id' ] ) && empty( $history_entry[ 'subscription_id' ] );
			if( $is_manual && ! $include_manual ) { continue; }
			
			$total += $tier[ 'price' ];
		}
	}
	
	return apply_filters( 'patips_total_income_based_on_patrons_history', $total );
}




// PATRON HISTORY LIST

/**
 * Get patron history column titles
 * @since 0.12.0
 * @version 0.13.5
 * @return array
 */
function patips_get_patron_history_column_titles() {
	return apply_filters( 'patips_patron_history_column_titles', array(
		'start'   => esc_html__( 'Start', 'patrons-tips' ),
		'end'     => esc_html__( 'End', 'patrons-tips' ),
		'period'  => esc_html__( 'Period', 'patrons-tips' ),
		'tier'    => esc_html__( 'Tier', 'patrons-tips' ),
		'price'   => esc_html__( 'Price', 'patrons-tips' ),
		'actions' => esc_html__( 'Actions', 'patrons-tips' )
	) );
}

/**
 * Get default patron history columns
 * @since 0.13.5
 * @return array
 */
function patips_get_default_patron_history_columns() {
	return apply_filters( 'patips_default_patron_history_columns', array( 'period', 'end', 'tier', 'price', 'actions' ) );
}


/**
 * Display a list of patron history entries
 * @since 0.12.0
 * @version 1.0.1
 * @param array $filters See patips_format_patron_history_filters
 * @param array $columns
 * @param boolean $return_array
 * @return string
 */
function patips_get_patron_history_list( $filters, $columns = array(), $return_array = false ) {
	// Get column titles
	$column_titles = patips_get_patron_history_column_titles();
	if( ! $columns ) { $columns = patips_get_default_patron_history_columns(); }
	
	// Get list items
	$list_item = patips_get_patron_history_list_items( array_merge( $filters, array( 'per_page' => $filters[ 'per_page' ] + 1 ) ), $columns );
	
	// Check if there are more history entries to display and remove the exedent history entries
	$history_nb = count( $list_item );
	$has_more   = $history_nb > $filters[ 'per_page' ];
	if( $has_more ) { 
		$list_item = array_slice( $list_item, 0, $filters[ 'per_page' ] );
	}
	
	$history_list = '';
	
	if( $list_item ) {
		ob_start();
		?>
		<div class='patips-ajax-list patips-patron-history-list'>
			<table class='patips-responsive-table'>
				<thead>
					<tr>
					<?php
						foreach( $columns as $column ) {
							$column_title = ! empty( $column_titles[ $column ] ) ? $column_titles[ $column ] : $column;
							?>
							<th class='patips-patron-history-list-column-<?php echo esc_attr( $column ); ?>'><?php echo esc_html( $column_title ); ?></th>
							<?php
						}
					?>
					</tr>
				</thead>
				<tbody class='patips-ajax-list-items patips-patron-history-list-items'>
				<?php
					// Display history item and increase offset
					$offset = $filters[ 'offset' ] + $filters[ 'per_page' ];
					foreach( $list_item as $history_item ) { 
						echo $history_item[ 'html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$offset = $history_item[ 'offset' ] + 1;
					}
				?>
				</tbody>
			</table>
			<?php 
			if( $has_more ) {
				$more_filters = $filters;
				$more_filters[ 'offset' ] = $offset;
				?>
				<div class='patips-ajax-list-more patips-patron-history-list-more'>
					<a href='#'
					   class='patips-ajax-list-more-link patips-patron-history-list-more-link'
					   data-type='patron_history'
					   data-nonce='<?php echo esc_attr( wp_create_nonce( 'patips_nonce_get_list_items' ) ); ?>'
					   data-args='<?php echo wp_json_encode( array( 'filters' => $more_filters, 'columns' => $columns ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>'>
						   <?php esc_html_e( 'See more', 'patrons-tips' ); ?>
					</a>
				</div>
			<?php } ?>
		</div>
		<?php
		$history_list = ob_get_clean();
	}
	
	$html = apply_filters( 'patips_patron_history_list', $history_list, $list_item, $filters );
	
	return $return_array ? $list_item : $html;
}


/**
 * Get patron history list items
 * @since 0.12.0
 * @version 0.25.5
 * @param array $filters See patips_format_patron_history_filters
 * @param array $columns
 * @return string
 */
function patips_get_patron_history_list_items( $filters, $columns = array() ) {
	// Get column titles
	$column_titles = patips_get_patron_history_column_titles();
	if( ! $columns ) { $columns = patips_get_default_patron_history_columns(); }
	
	// Get patrons history
	$patrons_history = patips_get_patrons_history_data( array(), $filters );
	$history_entries = array();
	foreach( $patrons_history as $patron_id => $patron_history ) {
		$history_entries = array_merge( $history_entries, array_values( $patron_history ) );
	}
	
	// Get history entries offset
	$offset                 = $filters[ 'offset' ];
	$history_entries_offset = array();
	foreach( $history_entries as $history_entry ) {
		$history_entries_offset[ $history_entry[ 'id' ] ] = $offset;
		++$offset;
	}
	
	// Get tiers data
	$tiers = patips_get_tiers_data();
	
	// Get list item
	$list_items = array();
	foreach( $history_entries as $i => $history_entry ) {
		/* translators: %s is the tier ID */
		$tier_title  = ! empty( $tiers[ $history_entry[ 'tier_id' ] ][ 'title' ] ) ? $tiers[ $history_entry[ 'tier_id' ] ][ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $history_entry[ 'tier_id' ] );
		$period_name = patips_get_patron_history_period_name( $history_entry );
		
		$column_values = apply_filters( 'patips_patron_history_list_item_data', array(
			'start'   => $history_entry[ 'date_start' ] ? patips_format_datetime( $history_entry[ 'date_start' ] . ' 00:00:00' ) : '',
			'end'     => $history_entry[ 'date_end' ] ? patips_format_datetime( $history_entry[ 'date_end' ] . ' 23:59:59' ) : '',
			'period'  => $period_name,
			'tier'    => $tier_title,
			'price'   => '',
			'actions' => ''
		), $history_entry, $filters );
		
		ob_start();
		?>
			<tr>
			<?php 
				foreach( $columns as $column ) {
					$column_title = ! empty( $column_titles[ $column ] ) ? $column_titles[ $column ] : $column;
					$column_value = ! empty( $column_values[ $column ] ) ? $column_values[ $column ] : '';
					?>
					<td data-title='<?php echo esc_attr( $column_title ); ?>' class='patips-patron-history-list-column-<?php echo esc_attr( $column ); ?>'><?php echo wp_kses_post( $column_value ); ?></td>
					<?php
				}
			?>
			</tr>
		<?php

		$list_items[] = array(
			'html'    => ob_get_clean(),
			'history' => $history_entry,
			'offset'  => ! empty( $history_entries_offset[ $history_entry[ 'id' ] ] ) ? intval( $history_entries_offset[ $history_entry[ 'id' ] ] ) : $i
		);
	}
	
	return apply_filters( 'patips_patron_history_list_items', $list_items, $history_entries, $filters );
}


/**
 * Get the message displayed to non-patron users
 * @since 0.12.0
 * @version 0.25.5
 * @return string
 */
function patips_get_become_patron_notice() {
	// Get patron sales page link
	$sales_page_id  = patips_get_option( 'patips_settings_general', 'sales_page_id' );
	$sales_page_url = $sales_page_id ? get_permalink( $sales_page_id ) : '';
	
	ob_start();
	?>
	<div id='patips-not-patron-notice'>
		<p><?php esc_html_e( 'Oh! It seems that you have never been a patron before. Patrons financially support my work and get amazing rewards in return!', 'patrons-tips' ); ?></p>
		
		<?php 
		// Become a patron link
		if( $sales_page_url ) { ?>
		<a class='patips-button' href='<?php echo esc_url( $sales_page_url ); ?>'>
			<?php esc_html_e( 'Become patron', 'patrons-tips' ); ?>
		</a>
		<?php }
		
		// Log in link
		if( ! is_user_logged_in() ) { ?>
		<small class='patips-log-in-link-container'>
		<?php
			echo sprintf( 
				/* translators: %s = "Log in" link */
				esc_html__( 'Already a patron? %s.', 'patrons-tips' ),
				patips_get_login_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		?>
		</small>
		<?php } ?>
	</div>
	<?php
	
	return ob_get_clean();
}