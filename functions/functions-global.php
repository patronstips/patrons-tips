<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get the variables used with javascript
 * @since 0.5.0
 * @version 0.25.2
 * @return array
 */
function patips_get_js_variables() {
	// Used by Blocks
	$tiers = patips_get_tiers_data();
	foreach( $tiers as $tier_id => $tier ) {
		$tiers[ $tier_id ][ 'icon' ]  = $tier[ 'icon_id' ] ? wp_get_attachment_image( $tier[ 'icon_id' ], 'thumbnail' ) : '';
	}
	
	$vars = array(
		'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
		'locale'                      => patips_get_current_lang_code( true ),
		'tiers'                       => $tiers,
		'patron_history_columns'      => patips_get_patron_history_column_titles(),
		'period_duration'             => patips_get_default_period_duration(),
		'price_args'                  => patips_get_price_args(),
		'nonce_query_select2_options' => wp_create_nonce( 'patips_query_select2_options' ),
		'select2_search_placeholder'  => esc_html__( 'Please enter {nb} or more characters.', 'patrons-tips' ),
		'button_generate_export_link' => esc_html__( 'Generate export link', 'patrons-tips' ),
		'button_reset'                => esc_html__( 'Reset', 'patrons-tips' ),
		'loading'                     => esc_html__( 'Loading', 'patrons-tips' ),
		'error'                       => esc_html__( 'An error has occured.', 'patrons-tips' ),
		'nonce_get_period_results'    => wp_create_nonce( 'patips_nonce_get_period_results' ),
		'nonce_get_period_media'      => wp_create_nonce( 'patips_nonce_get_period_media' ),
		'nonce_get_patron_list'       => wp_create_nonce( 'patips_nonce_get_patron_list' ),
		'nonce_get_patron_post_list'  => wp_create_nonce( 'patips_nonce_get_patron_post_list' )
	);
	
	if( is_admin() ) {
		$vars[ 'no_history' ] = esc_html__( 'No history.', 'patrons-tips' );
		$vars[ 'nonce_dismiss_admin_notice' ] = wp_create_nonce( 'patips_nonce_dismiss_admin_notice' );
	}
	
	return apply_filters( 'patips_js_variables', $vars );
}


/**
 * Get add-on data by prefix
 * @since 0.9.0
 * @version 1.0.2
 * @param string $prefix
 * @param array $exclude
 * @return array
 */
function patips_get_add_ons_data( $prefix = '', $exclude = array() ) {
	$addons_data = array( 
		'patips_pro' => array(
			'prefix'        => 'patips_pro',
			'title'         => 'Patrons Tips - Pro',
			'slug'          => 'patrons-tips-pro',
			'plugin_name'   => 'patrons-tips-pro',
			'version_const' => 'PATIPS_PRO_PLUGIN_VERSION',
			'dir_const'     => 'PATIPS_PRO_PLUGIN_DIR',
			'download_id'   => 28,
			'end_of_life'   => '',
			'min_version'   => '1.0.1'
		)
	);
	
	// Exclude undesired add-ons
	if( $exclude ) {
		$addons_data = array_diff_key( $addons_data, array_flip( $exclude ) );
	}
	
	return ! $prefix ? $addons_data : ( isset( $addons_data[ $prefix ] ) ? $addons_data[ $prefix ] : array() );
}


/**
 * Get active Patrons Tips add-ons
 * @since 0.9.0
 * @param string $prefix
 * @param array $exclude
 */
function patips_get_active_add_ons( $prefix = '', $exclude = array() ) {
	$add_ons_data = patips_get_add_ons_data( $prefix, $exclude );
	
	$active_add_ons = array();
	foreach( $add_ons_data as $add_on_prefix => $add_on_data ) {
		$add_on_path = $add_on_data[ 'plugin_name' ] . '/' . $add_on_data[ 'plugin_name' ] . '.php';
		if( patips_is_plugin_active( $add_on_path ) ) {
			$active_add_ons[ $add_on_prefix ] = $add_on_data;
		}
	}

	return $active_add_ons;
}


/**
 * Check if a plugin is active
 * @since 0.2.2
 * @param string $plugin_path_and_name
 * @return boolean
 */
function patips_is_plugin_active( $plugin_path_and_name ) {
	if( ! function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return is_plugin_active( $plugin_path_and_name );
}


/**
 * Check if the current theme is a block theme.
 * @since 0.21.0
 * @return bool
 */
function patips_is_block_theme() {
	if ( function_exists( 'wp_is_block_theme' ) ) {
		return (bool) wp_is_block_theme();
	}
	if ( function_exists( 'gutenberg_is_fse_theme' ) ) {
		return (bool) gutenberg_is_fse_theme();
	}

	return false;
}


/**
 * Get WP timezone (set in General settings)
 * @since 0.1.0
 * @version 0.8.0
 * @return DateTimeZone
 */
function patips_get_wp_timezone() {
	$utc_timezone = new DateTimeZone( 'UTC' );
	$timezone     = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
	
	try { 
		$timezone = new DateTimeZone( $timezone ); 
	}
	catch ( Exception $ex ) { 
		$timezone = clone $utc_timezone;
	}
	
	return $timezone;
}


/**
 * Get login link or URL
 * @since 0.22.0
 * @version 0.25.5
 * @param boolean $redirect
 * @param boolean $url_only
 * @return string
 */
function patips_get_login_link( $redirect = true, $url_only = false ) {
	$redirect_url = '';
	if( $redirect ) {
		$request_uri  = ! empty( $_SERVER[ 'REQUEST_URI' ] ) ? home_url( sanitize_url( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) ) : '';
		$redirect_url = is_string( $redirect ) ? $redirect : $request_uri;
	}
	
	$link = wp_login_url( $redirect_url );
	
	if( ! $url_only ) {
		$link = '<a href="' . esc_url( $link ) . '">' . esc_html__( 'Log in', 'patrons-tips' ) . '</a>';
	}
	
	return apply_filters( 'patips_login_link', $link, $redirect, $url_only );
}


/**
 * Get Patrons Tips Pro link or URL
 * @since 0.25.0
 * @version 0.25.3
 * @param boolean $url_only
 * @return string
 */
function patips_get_pro_sales_link( $url_only = false ) {
	$link = 'https://patronstips.com/?utm_source=plugin&utm_medium=plugin&utm_campaign=pro';
	
	if( ! $url_only ) {
		$link = '<a href="' . esc_url( $link ) . '" class="patips-pro-sales-link" target="_blank">Patrons Tips - Pro</a>';
	}
	
	return apply_filters( 'patips_pro_sales_link', $link, $url_only );
}


/**
 * Get supported reccuring payment plugins
 * @since 0.26.0
 * @return array
 */
function patips_get_recurring_payment_plugins() {
	return apply_filters( 'patips_recurring_payment_plugins', array(
		'wcs' => array(
			/* translators: This is a plugin name. */
			'title'   => esc_html__( 'WooCommerce Subscriptions', 'patrons-tips' ),
			'slug'    => 'woocommerce-subscriptions',
			'author'  => esc_html__( 'WooCommerce', 'patrons-tips' ),
			'url'     => 'https://woocommerce.com/products/woocommerce-subscriptions/',
			'pricing' => 'premium'
		),
		'sfw' => array(
			/* translators: This is a plugin name. */
			'title'   => esc_html__( 'Subscriptions for WooCommerce', 'patrons-tips' ),
			'slug'    => 'subscriptions-for-woocommerce',
			'author'  => esc_html__( 'WP Swings', 'patrons-tips' ),
			'url'     => 'https://wordpress.org/plugins/subscriptions-for-woocommerce/',
			'pricing' => 'freemium'
		),
		'fsb' => array(
			/* translators: This is a plugin name. */
			'title'   => esc_html__( 'Flexible Subscriptions', 'patrons-tips' ),
			'slug'    => 'flexible-subscriptions',
			'author'  => esc_html__( 'WP Desk', 'patrons-tips' ),
			'url'     => 'https://wordpress.org/plugins/flexible-subscriptions/',
			'pricing' => 'freemium'
		)
	) );
}


/**
 * Get supported reccuring payment plugin links
 * @since 0.26.0
 * @param array $data
 * @return array
 */
function patips_get_recurring_payment_plugin_links( $data = array() ) {
	$plugins = patips_get_recurring_payment_plugins();
	$links   = array();
	
	foreach( $plugins as $i => $plugin ) {
		$str = '';
		
		if( ( ! $data || in_array( 'title', $data, true ) ) && ( $plugin[ 'title' ] || $plugin[ 'slug' ] ) ) {
			$str .= $plugin[ 'title' ] ? esc_html( $plugin[ 'title' ] ) : esc_html( $plugin[ 'slug' ] );
		} else if( ( ! $data || in_array( 'url', $data, true ) ) && $plugin[ 'url' ] ) {
			$str .= esc_url( $plugin[ 'url' ] );
		}
		
		if( ( ! $data || in_array( 'link', $data, true ) ) && $plugin[ 'url' ] ) {
			$str = '<a href="' . esc_url( $plugin[ 'url' ] ) . '" target="_blank">' . esc_html( $str ) . '</a>';
		}

		if( ( ! $data || in_array( 'author', $data, true ) ) && $plugin[ 'author' ] ) {
			/* translators: %s = plugin author. */
			$str .= ' <em>' . sprintf( esc_html__( 'by %s', 'patrons-tips' ), esc_html( $plugin[ 'author' ] ) ) . '</em>';
		}

		if( ( ! $data || in_array( 'pricing', $data, true ) ) && $plugin[ 'pricing' ] ) {
			$str .= ' <em>(' . ( substr( $plugin[ 'pricing' ], 0, 4 ) === 'free' ? esc_html_x( 'free', 'opposite of paid', 'patrons-tips' ) : esc_html_x( 'paid', 'opposite of free', 'patrons-tips' ) ) . ')</em>';
		}
		
		$links[ $i ] = $str;
	}
	
	return $links;
}


/**
 * Sanitize int ids to array
 * @version 0.5.0
 * @param array|int $ids
 * @param bool|?callable $filter
 * @return array 
 */
function patips_ids_to_array( $ids, $filter = 'empty' ) {
	if( is_array( $ids ) ){
		$ids = array_unique( array_map( 'intval', array_filter( $ids, 'is_numeric' ) ) );
	} else if( ! empty( $ids ) && is_numeric( $ids ) ) {
		$ids = array( intval( $ids ) );
	} else {
		$ids = array();
	}
	
	if( $ids && $filter && is_callable( $filter ) ) {
		$ids = array_filter( $ids, $filter );
	}
	
	return array_values( $ids );
}


/**
 * Sanitize str ids to array
 * @since 0.5.0
 * @param array|string $ids
 * @return array 
 */
function patips_str_ids_to_array( $ids, $filter = 'empty' ) {
	if( is_array( $ids ) ){
		$ids = array_unique( array_map( 'sanitize_title_with_dashes', array_filter( $ids, 'is_string' ) ) );
	} else if( ! empty( $ids ) && is_string( $ids ) ){
		$ids = array( sanitize_title_with_dashes( $ids ) );
	} else {
		$ids = array();
	}
	
	if( $ids && $filter && is_callable( $filter ) ) {
		$ids = array_filter( $ids, $filter );
	}
	
	return array_values( $ids );
}


/**
 * Check if a string is in a correct date format
 * @since 0.1.0
 * @version 0.2.2
 * @param string $date Date format Y-m-d or Y-m is expected
 * @return string 
 */
function patips_sanitize_date( $date ) {
	if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $date ) ) {
		return $date . '-01';
	}
	if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $date ) ) {
		return $date;
	}
	return '';
}


/**
 * Check if a string is in a correct datetime format
 * @since 0.1.0
 * @param string $datetime Date format "Y-m-d H:i:s" is expected
 * @return string
 */
function patips_sanitize_datetime( $datetime ) {
	if( preg_match( '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])T([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $datetime ) 
	||  preg_match( '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]) ([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $datetime ) ) {
		return $datetime;
	}
	return '';
}


/**
 * Format datetime to be displayed in a human comprehensible way
 * @since 0.1.0
 * @version 1.0.1
 * @param string $datetime_raw Date format "Y-m-d" or "Y-m-d H:i:s" is expected
 * @param string $format 
 * @return string
 */
function patips_format_datetime( $datetime_raw, $format = '' ) {
	// Check input format
	$datetime = patips_sanitize_datetime( $datetime_raw );
	if( ! $datetime && $datetime_raw ) { 
		$datetime = patips_sanitize_date( $datetime_raw );
		if( $datetime ) { $datetime .= ' 00:00:00'; }
	}
	
	if( $datetime ) {
		if( ! $format ) { $format = get_option( 'date_format' ); }

		// Force timezone to UTC to avoid offsets because datetimes should be displayed regarless of timezones
		$dt = new DateTime( $datetime, new DateTimeZone( 'UTC' ) );
		$timestamp = $dt->getTimestamp();
		if( $timestamp === false ) { return $datetime; }
		
		// Do not use date_i18n() function to force the UTC timezone
		$datetime = apply_filters( 'date_i18n', wp_date( $format, $timestamp, new DateTimeZone( 'UTC' ) ), $format, $timestamp, false );

		// Encode to UTF8 to avoid any bad display of special chars
		$datetime = patips_utf8_encode( $datetime );
	}
	
	return $datetime;
}


/**
 * Get the price format depending on the currency position.
 * @since 0.21.0
 * @return string %1$s = currency symbol, %2$s = price amount
 */
function patips_get_price_format() {
	$currency_pos = patips_get_option( 'patips_settings_general', 'price_currency_position' );
	$format       = '%1$s%2$s';

	switch( $currency_pos ) {
		case 'left':
			$format = '%1$s%2$s';
			break;
		case 'right':
			$format = '%2$s%1$s';
			break;
		case 'left_space':
			$format = '%1$s&nbsp;%2$s';
			break;
		case 'right_space':
			$format = '%2$s&nbsp;%1$s';
			break;
	}

	return apply_filters( 'patips_price_format', $format, $currency_pos );
}


/**
 * Get default price args
 * @since 0.21.0
 * @return array
 */
function patips_get_price_args() {
	return apply_filters( 'patips_price_args', array(
		'currency_symbol'    => patips_get_option( 'patips_settings_general', 'price_currency_symbol' ),
		'decimal_separator'  => patips_get_option( 'patips_settings_general', 'price_decimal_separator' ),
		'thousand_separator' => patips_get_option( 'patips_settings_general', 'price_thousand_separator' ),
		'decimals'           => patips_get_option( 'patips_settings_general', 'price_decimals_number' ),
		'price_format'       => patips_get_price_format(),
		'plain_text'         => false
	) );
}


/**
 * Format a price with currency symbol
 * @since 0.21.0
 * @version 0.25.5
 * @param int|float $price_raw
 * @param array $args_raw Arguments to format a price (default to price settings values) {
 *     @type string $currency_symbol    Currency symbol.
 *     @type string $decimal_separator  Decimal separator.
 *     @type string $thousand_separator Thousand separator.
 *     @type string $decimals           Number of decimals.
 *     @type string $price_format       Price format depending on the currency position.
 *     @type boolean $plain_text        Return plain text instead of HTML.
 * }
 * @return string
 */
function patips_format_price( $price_raw, $args_raw = array() ) {
	if( ! is_numeric( $price_raw ) ) { return ''; }
	
	$price_args = patips_get_price_args();
	$args       = array_merge( $price_args, $args_raw );

	// Convert to float to avoid issues on PHP 8.
	$price  = (float) $price_raw;
	$amount = number_format( abs( $price ), $args[ 'decimals' ], $args[ 'decimal_separator' ], $args[ 'thousand_separator' ] );

	if( apply_filters( 'patips_price_trim_zeros', false ) && $args[ 'decimals' ] > 0 ) {
		$amount = preg_replace( '/' . preg_quote( $args[ 'decimal_separator' ], '/' ) . '0++$/', '', $amount );
	}

	ob_start();
	if( empty( $args[ 'plain_text' ] ) ) {
	?>
	<span class='patips-price'>
		<bdi>
		<?php if( $price < 0 ) { ?>
			<span class='patips-price-sign'>-</span>
		<?php } 
			echo sprintf( 
				esc_html( $args[ 'price_format' ] ), 
				'<span class="patips-price-currency-symbol">' . esc_html( trim( $args[ 'currency_symbol' ] ) ) . '</span>',
				'<span class="patips-price-amount">' . esc_html( $amount ) . '</span>'
			);
		?>
		</bdi>
	</span>
	<?php
	} else {
		if( $price < 0 ) { echo '-'; }
		echo sprintf( esc_html( $args[ 'price_format' ] ), esc_html( $amount ), esc_html( trim( $args[ 'currency_symbol' ] ) ) );
	}

	return apply_filters( 'patips_formatted_price', str_replace( array( "\t", "\r", "\n" ), '', trim( ob_get_clean() ) ), $amount, $price_raw, $args_raw );
}


/**
 * Compute the end date of a patronage period based on its start date
 * @since 0.1.0
 * @version 0.13.0
 * @param DateTime $start_dt
 * @param int $nb_months Number of month in the period
 * @return DateTime
 */
function patips_compute_period_end( $start_dt, $nb_months = 1 ) {
	if( ! $nb_months ) { return clone $start_dt; }
	
	$end_dt = clone $start_dt;
	$end_dt->setDate( $end_dt->format( 'Y' ), $end_dt->format( 'm' ), 1 );
	$end_dt->add( new DateInterval( 'P' . $nb_months . 'M' ) );
	$is_last_day = $start_dt->format( 'd' ) === $start_dt->format( 't' );
	$end_dt_day  = $is_last_day ? $end_dt->format( 't' ) : min( intval( $start_dt->format( 'd' ) ), intval( $end_dt->format( 't' ) ) );
	$end_dt->setDate( $end_dt->format( 'Y' ), $end_dt->format( 'm' ), $end_dt_day );
	
	// Subtract 1 day because the end date is included in the period, unless the end date is already less than the start date
	$is_shorter_month = intval( $end_dt->format( 't' ) ) < intval( $start_dt->format( 'd' ) );
	if( ! $is_shorter_month && ! $is_last_day ) {
		$day_nb = new DateInterval( 'P1D' );
		$day_nb->invert = 1;
		$end_dt->add( $day_nb );
	}
	
	return $end_dt;
}


/**
 * Compute the start date of a patronage period based on its end date
 * @since 0.13.0
 * @param DateTime $end_dt
 * @param int $nb_months Number of month in the period
 * @return DateTime
 */
function patips_compute_period_start( $end_dt, $nb_months = 1 ) {
	if( ! $nb_months ) { return clone $end_dt; }
	
	$start_dt = clone $end_dt;
	$start_dt->setDate( $start_dt->format( 'Y' ), $start_dt->format( 'm' ), 1 );
	$start_dt->sub( new DateInterval( 'P' . $nb_months . 'M' ) );
	$is_last_day  = $end_dt->format( 'd' ) === $end_dt->format( 't' );
	$start_dt_day = $is_last_day ? $start_dt->format( 't' ) : min( intval( $end_dt->format( 'd' ) ), intval( $start_dt->format( 't' ) ) );
	$start_dt->setDate( $start_dt->format( 'Y' ), $start_dt->format( 'm' ), $start_dt_day );
	
	// Add 1 day because the end date is included in the period
	$is_already_shorter = intval( $start_dt->format( 'd' ) ) > intval( $end_dt->format( 'd' ) );
	if( ! $is_already_shorter || $is_last_day ) {
		$day_nb = new DateInterval( 'P1D' );
		$start_dt->add( $day_nb );
	}
	
	return $start_dt;
}


/**
 * Get the number of months in a period
 * @since 0.13.0
 * @return int
 */
function patips_get_default_period_duration() {
	return apply_filters( 'patips_default_period_duration', 1 );
}


/**
 * Get current period
 * @since 0.10.0
 * @return array
 */
function patips_get_current_period() {
	return apply_filters( 'patips_current_period', patips_get_period_by_date() );
}

/**
 * Get period of the desired date
 * @since 0.10.0
 * @version 0.14.0
 * @param string $datetime_raw
 * @param int $period_duration
 * @param string $until_datetime_raw
 * @return array
 */
function patips_get_period_by_date( $datetime_raw = '', $period_duration = 0, $until_datetime_raw = '' ) {
	// Sanitize datetimes
	$datetime = patips_sanitize_datetime( $datetime_raw );
	$until    = patips_sanitize_datetime( $until_datetime_raw );
	if( ! $datetime && $datetime_raw ) { 
		$datetime = patips_sanitize_date( $datetime_raw );
		if( $datetime ) { $datetime .= ' 00:00:00'; }
	}
	if( ! $datetime && $datetime_raw ) { return array(); }
	
	if( ! $until && $until_datetime_raw ) { 
		$until = patips_sanitize_date( $until_datetime_raw );
		if( $until ) { $until .= ' 23:59:59'; }
	}
	
	$timezone = patips_get_wp_timezone();
	$date_dt  = $datetime ? DateTime::createFromFormat( 'Y-m-d H:i:s', $datetime, $timezone ) : new DateTime( 'now', $timezone );
	$until_dt = $until ? DateTime::createFromFormat( 'Y-m-d H:i:s', $until, $timezone ) : null;
	$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $date_dt->format( 'Y' ) . '-01-01 00:00:00', $timezone );
	$month_nb = $period_duration ? intval( $period_duration ) : patips_get_default_period_duration();
	
	// Find the the period corresponding to $date_dt
	$i = 0;
	do {
		if( $i !== 0 ) {
			$start_dt->add( new DateInterval( 'P' . $month_nb . 'M' ) );
		}
		$end_dt = patips_compute_period_end( $start_dt, $month_nb );
		$end_dt->setTime( 23, 59, 59 );
		++$i;
	} while( $end_dt < $date_dt );
	
	// Extend it with all **full** periods that fit up to the deadline
	$count = 1;
	if( $until_dt ) {
		$next_period_start_dt = clone $start_dt;
		do {
			$next_period_start_dt->add( new DateInterval( 'P' . $month_nb . 'M' ) );
			$next_period_end_dt = patips_compute_period_end( $next_period_start_dt, $month_nb );
			
			if( $next_period_end_dt < $until_dt ) {
				$end_dt = clone $next_period_end_dt;
				++$count;
			}
		} while( $next_period_end_dt < $until_dt );
	}
	
	$period = array(
		'start'    => $start_dt->format( 'Y-m-d H:i:s' ),
		'end'      => $end_dt->format( 'Y-m-d H:i:s' ),
		'duration' => $month_nb, // Period duration in months
		'index'    => $i,        // Period index in the year (index of the first included period only)
		'count'    => $count     // Number of periods included (usually "1")
	);
	
	return apply_filters( 'patips_date_period', $period, $datetime, $period_duration, $until );
}


/**
 * Get formatted frequency by frequency identifier
 * @since 0.13.0
 * @version 0.25.4
 * @param string $frequency
 * @param string $adverb
 * @return string
 */
function patips_get_frequency_name( $frequency, $adverb = true ) {
	$separator_i = strpos( $frequency, '_' );
	$interval    = $separator_i !== false ? intval( substr( $frequency, 0, $separator_i ) ) : 1;
	$period      = $separator_i !== false ? substr( $frequency, $separator_i + 1 ) : $frequency;
	
	if( $frequency === 'one_off' ) {
		$interval = 0;
		$period   = 'one_off';
	}
	
	$frequency_names = array(
		'one_off' => esc_html__( 'one-off', 'patrons-tips' ),
		'day'     => $interval === 1 && $adverb ? esc_html__( 'daily', 'patrons-tips' ) : /* translators: %s = a number. */ sprintf( esc_html( _n( '%s day', '%s days', $interval, 'patrons-tips' ) ), $interval ),
		'week'    => $interval === 1 && $adverb ? esc_html__( 'weekly', 'patrons-tips' ) : /* translators: %s = a number. */ sprintf( esc_html( _n( '%s week', '%s weeks', $interval, 'patrons-tips' ) ), $interval ),
		'month'   => $interval === 1 && $adverb ? esc_html__( 'monthly', 'patrons-tips' ) : /* translators: %s = a number. */ sprintf( esc_html( _n( '%s month', '%s months', $interval, 'patrons-tips' ) ), $interval ),
		'year'    => $interval === 1 && $adverb ? esc_html__( 'yearly', 'patrons-tips' ) : /* translators: %s = a number. */ sprintf( esc_html( _n( '%s year', '%s years', $interval, 'patrons-tips' ) ), $interval )
	);
	
	$frequency_name = isset( $frequency_names[ $period ] ) ? $frequency_names[ $period ] : $frequency;
	
	if( $adverb && $interval !== 1 && $frequency !== 'one_off' && isset( $frequency_names[ $period ] ) ) {
		/* translators: %s = number + period (e.g. "2 months", "4 weeks", "3 years", etc.) */
		$frequency_name = sprintf( esc_html__( 'every %s', 'patrons-tips' ), $frequency_name );
	}
	
	return apply_filters( 'patips_frequency_name', $frequency_name, $frequency );
}


/**
 * Sort array of frequencies
 * @since 0.23.1
 * @param array $frequencies
 * @return array
 */
function patips_sort_frequencies( $frequencies ) {
	usort( $frequencies, function( $a, $b ) {
		$a_i = $b_i = 0;
		$periods = array( 0 => 'one_off', 1 => 'month', 12 => 'year' );
		foreach( $periods as $i => $period ) {
			if( strpos( $a, $period ) !== false ) { $a_i = $i; }
			if( strpos( $b, $period ) !== false ) { $b_i = $i; }
		}
		
		if( $a_i === $b_i ) {
			$a_interval = substr( $a, 0, strpos( $a, '_' ) );
			$b_interval = substr( $b, 0, strpos( $b, '_' ) );
			if( is_numeric( $a_interval ) ) { $a_i *= $a_interval; }
			if( is_numeric( $b_interval ) ) { $b_i *= $b_interval; }
		}
		
		return $a_i === $b_i ? 0 : ( $a_i > $b_i ? 1 : -1 );
	});
	
	return $frequencies;
}


/**
 * Get current period
 * @since 0.10.0
 * @version 0.11.0
 * @param array $period
 * @return string
 */
function patips_get_period_name( $period ) {
	$period_name = '';
	$nb_months   = isset( $period[ 'count' ] ) && $period[ 'count' ] > 1 ? 0 : $period[ 'duration' ];
	$start_dt    = DateTime::createFromFormat( 'Y-m-d H:i:s', $period[ 'start' ] );
	
	if( $nb_months === 1 ) {
		$period_name = patips_format_datetime( $period[ 'start' ], 'F Y' );
	}
	else if( $nb_months === 3 && $period[ 'index' ] ) {
		if( $period[ 'index' ] === 1 ) {
			/* translators: %s = Year */
			$period_name = sprintf( esc_html__( 'First quarter %s', 'patrons-tips' ), $start_dt->format( 'Y' ) );
		} else if( $period[ 'index' ] === 2 ) {
			/* translators: %s = Year */
			$period_name = sprintf( esc_html__( 'Second quarter %s', 'patrons-tips' ), $start_dt->format( 'Y' ) );
		} else if( $period[ 'index' ] === 3 ) {
			/* translators: %s = Year */
			$period_name = sprintf( esc_html__( 'Third quarter %s', 'patrons-tips' ), $start_dt->format( 'Y' ) );
		} else {
			/* translators: %s = Year */
			$period_name = sprintf( esc_html__( 'Fourth quarter %s', 'patrons-tips' ), $start_dt->format( 'Y' ) );
		}
	}
	else if( $nb_months === 6 && $period[ 'index' ] ) {
		if( $period[ 'index' ] === 1 ) {
			/* translators: %s = Year */
			$period_name = sprintf( esc_html__( 'First half %s', 'patrons-tips' ), $start_dt->format( 'Y' ) );
		} else {
			/* translators: %s = Year */
			$period_name = sprintf( esc_html__( 'Second half %s', 'patrons-tips' ), $start_dt->format( 'Y' ) );
		}
	}
	else if( $nb_months === 12 ) {
		$period_name = $start_dt->format( 'Y' );
	}
	else {
		$from_name   = patips_format_datetime( $period[ 'start' ], 'F' );
		$to_name     = patips_format_datetime( $period[ 'end' ], 'F Y' );
		/* translators: %1$s = Month name, %2$s = Month name and year. E.g. January to February 2024. */
		$period_name = sprintf( esc_html__( '%1$s to %2$s', 'patrons-tips' ), $from_name, $to_name );
	}
	
	return apply_filters( 'patips_period_name', $period_name, $period );
}


/**
 * Check if a string is valid for UTF-8 use
 * @since 0.1.0
 * @param string $string
 * @return boolean
 */
function patips_is_utf8( $string ) {
	if( function_exists( 'mb_check_encoding' ) ) {
		if( mb_check_encoding( $string, 'UTF-8' ) ) { 
			return true;
		}
	}
	else if( preg_match( '%^(?:
			[\x09\x0A\x0D\x20-\x7E]            # ASCII
		  | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		  | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
		  | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		  | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
		  | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
		  | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		  | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
		)*$%xs', $string ) ) {
		return true;
	}
	return false;
}


/**
 * Encode to UTF-8
 * @since 1.0.1
 * @param string $string
 * @param boolean $check
 * @return string
 */
function patips_utf8_encode( $string, $check = true ) {
	if( $check && patips_is_utf8( $string ) ) {
		return $string;
	}
	if( function_exists( 'mb_convert_encoding' ) ) {
		return mb_convert_encoding( $string, 'UTF-8', 'ISO-8859-1' );
	}
	else if( function_exists( 'iconv' ) ) {
		return iconv( 'ISO-8859-1', 'UTF-8', $string );
	}
	return patips_iso8859_1_to_utf8( $string );
}


/**
 * Decode UTF-8
 * @since 1.0.1
 * @param string $string
 * @param boolean $check
 * @return string
 */
function patips_utf8_decode( $string, $check = true ) {
    if( $check && ! patips_is_utf8( $string ) ) {
        return $string;
    }
    if( function_exists( 'mb_convert_encoding' ) ) {
        return mb_convert_encoding( $string, 'ISO-8859-1', 'UTF-8' );
    }
    else if( function_exists( 'iconv' ) ) {
        return iconv( 'UTF-8', 'ISO-8859-1', $string );
    }
    return patips_utf8_to_iso8859_1( $string );
}


/**
 * Replacement for utf8_encode
 * https://github.com/symfony/polyfill-php72/blob/v1.30.0/Php72.php#L24-L38
 * @since 1.0.1
 * @param string $s
 * @return string
 */
function patips_iso8859_1_to_utf8( $s ) {
    $s .= $s;
	$len = \strlen($s);

	for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
		switch (true) {
			case $s[$i] < "\x80": $s[$j] = $s[$i]; break;
			case $s[$i] < "\xC0": $s[$j] = "\xC2"; $s[++$j] = $s[$i]; break;
			default: $s[$j] = "\xC3"; $s[++$j] = \chr(\ord($s[$i]) - 64); break;
		}
	}

	return substr($s, 0, $j);
}


/**
 * Replacement for utf8_decode
 * https://github.com/symfony/polyfill-php72/blob/v1.30.0/Php72.php#L40-L68
 * @since 1.0.1
 * @param string $s
 * @return string
 */
function patips_utf8_to_iso8859_1( $s ) {
    $s = (string) $s;
    $len = \strlen($s);

    for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
        switch ($s[$i] & "\xF0") {
            case "\xC0":
            case "\xD0":
                $c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
                $s[$j] = $c < 256 ? \chr($c) : '?';
                break;

            case "\xF0":
                ++$i;
                // no break

            case "\xE0":
                $s[$j] = '?';
                $i += 2;
                break;

            default:
                $s[$j] = $s[$i];
        }
    }

    return substr($s, 0, $j);
}


/**
 * Get users data and meta
 * @version 0.1.0
 * @global $blog_id
 * @param array $args
 * @return array
 */
function patips_get_users_data( $args = array() ) {
	$defaults = array(
		'blog_id' => $GLOBALS[ 'blog_id' ],
		'include' => array(), 'exclude' => array(),
		'role' => '', 'role__in' => array(), 'role__not_in' => array(), 'who' => '',
		'meta_key' => '', 'meta_value' => '', 'meta_compare' => '', 'meta_query' => array(),
		'date_query' => array(),
		'orderby' => 'login', 'order' => 'ASC', 'offset' => '',
		'number' => '', 'paged' => '', 'count_total' => false,
		'search' => '', 'search_columns' => array(), 'fields' => 'all', 
		'meta' => true, 'meta_single' => true
	 ); 

	$args = apply_filters( 'patips_users_data_args', wp_parse_args( $args, $defaults ), $args );

	$users = get_users( $args );
	if( ! $users ) { return $users; }
	
	// Index the array by user ID
	$sorted_users = array();
	foreach( $users as $user ) {
		$sorted_users[ $user->ID ] = $user;
	}
	
	// Add user meta
	if( $args[ 'meta' ] ) {
		// Make sure that all the desired users meta are in cache with a single db query
		update_meta_cache( 'user', array_keys( $sorted_users ) );
		
		// Add cached meta to user object
		foreach( $sorted_users as $user_id => $user ) {
			$meta = array();
			$meta_raw = wp_cache_get( $user_id, 'user_meta' );
			foreach( $meta_raw as $key => $values ) {
				$meta[ $key ] = $args[ 'meta_single' ] ? maybe_unserialize( $values[ 0 ] ) : array_map( 'maybe_unserialize', $values );
			}
			$sorted_users[ $user_id ]->meta = $meta; 
		}
	}
		
	return $sorted_users;
}


/**
 * Get all available user roles
 * @since 0.8.0
 * @return array
 */
function patips_get_roles() {
	global $wp_roles;
	if( ! $wp_roles ) { $wp_roles = new WP_Roles(); }
    $roles = array_map( 'translate_user_role', $wp_roles->get_names() );
	return $roles;
}


/**
 * Get public attachment taxonomies
 * @since 0.5.0
 * @return array
 */
function patips_get_attachment_public_taxonomies() {
	$taxonomies        = get_object_taxonomies( array( 'attachment' ), 'objects' );
	$public_taxonomies = array();
	
	foreach( $taxonomies as $tax_name => $taxonomy ) {
		if( $taxonomy->public ) {
			$public_taxonomies[ $tax_name ] = $taxonomy;
		}
	}
	
	return $public_taxonomies;
}


/**
 * Check if a string is valid JSON
 * @since 0.11.0
 * @param string $string
 * @return boolean
 */
function patips_is_json( $string ) {
	if( ! is_string( $string ) ) { return false; }
	json_decode( $string );
	return ( json_last_error() == JSON_ERROR_NONE );
}


/**
 * Decode JSON if it is valid else return self
 * @since 0.11.0
 * @param string $string
 * @param boolean $assoc
 * @return array|$string
 */
function patips_maybe_decode_json( $string, $assoc = false ) {
	if( ! is_string( $string ) ) { return $string; }
	$decoded = json_decode( $string, $assoc );
	if( json_last_error() == JSON_ERROR_NONE ) { return $decoded; }
	return $string;
}


/**
 * Send a filtered array via json during an ajax process
 * @since 1.5.0
 * @version 1.5.3
 * @param array $array Array to encode as JSON, then print and die.
 * @param string $action Name of the filter to allow third-party modifications
 */
function patips_send_json( $array, $action = '' ) {
	if( empty( $array[ 'status' ] ) ) { $array[ 'status' ] = 'failed'; }
	$response = apply_filters( 'patips_send_json_' . $action, $array );
	wp_send_json( $response );
}


/**
 * Send a filtered array via json to stop an ajax process running with an invalid nonce
 * @since 0.5.0
 * @param string $action Name of the filter to allow third-party modifications
 */
function patips_send_json_invalid_nonce( $action = '' ) {
	$return_array = array( 
		'status'  => 'failed', 
		'error'   => 'invalid_nonce',
		'action'  => $action, 
		'message' => esc_html__( 'Invalid nonce.', 'patrons-tips' ) . ' ' . esc_html__( 'Please reload the page and try again.', 'patrons-tips' )
	);
	patips_send_json( $return_array, $action );
}


/**
 * Send a filtered array via json to stop a not allowed an ajax process
 * @since 0.5.0
 * @param string $action Name of the filter to allow third-party modifications
 */
function patips_send_json_not_allowed( $action = '' ) {
	$return_array = array( 
		'status'  => 'failed', 
		'error'   => 'not_allowed', 
		'action'  => $action, 
		'message' => esc_html__( 'You are not allowed to do that.', 'patrons-tips' )
	);
	patips_send_json( $return_array, $action );
}


/**
 * Write logs to uploads/patips-debug.log file
 * @since 0.1.0
 * @version 1.0.2
 * @param string $message
 * @return boolean
 */
function patips_log( $message = '' ) {
	if( is_array( $message ) || is_object( $message ) ) { 
		$message = print_r( $message, true ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	}

	if( is_bool( $message ) ) { 
		$message = $message ? 'true' : 'false';
	}

	$wp_upload_dir = wp_upload_dir();
	$file = $wp_upload_dir[ 'basedir' ] . '/patips-debug.log';

	$time = gmdate( 'Y-m-d H:i:s' );
	$log  = $time . ' - ' . $message . PHP_EOL;

	$write = error_log( $log, 3, $file ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

	return $write;
}