<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Display a thank you message with the patrons list for the desired month
 * @since 0.11.0 (was patips_shortcode_thank_you_monthly_patrons_list)
 * @version 1.0.1
 * @global WP_Post $post
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_patron_list_thanks( $raw_args = array() ) {
	// Sanitize shortcode attributes
	$default_args   = patips_get_default_patron_filters();
	$args           = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_patron_list_thanks' );
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_filters( $formatted_args );
	
	// Get patrons
	$filters = apply_filters( 'patips_shortcode_patron_list_thanks_filters', $filters, $raw_args );
	$patrons = patips_get_patrons_data( $filters );
	
	// Get period name
	$period_name = apply_filters( 'patips_shortcode_patron_list_thanks_period_name', '', $filters, $raw_args );
	
	// Get HTML
	$html = patips_get_patron_list_thanks( $patrons, $period_name );
	
	return apply_filters( 'patips_shortcode_patron_list_thanks', $html, $patrons, $filters, $raw_args );
}
add_shortcode( 'patronstips_patron_list_thanks', 'patips_shortcode_patron_list_thanks' );


/**
 * Display the patrons list
 * @since 0.11.0 (was patips_shortcode_monthly_patrons_list)
 * @version 1.0.1
 * @global WP_Post $post
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_patron_list( $raw_args = array() ) {
	// Sanitize shortcode attributes
	$default_args   = patips_get_default_patron_filters();
	$args           = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_patron_list' );
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_filters( $formatted_args );
	
	// Get patrons
	$filters = apply_filters( 'patips_shortcode_patron_list_filters', $filters, $raw_args );
	$patrons = patips_get_patrons_data( $filters );
	
	// Get HTML
	$html = patips_get_patron_list( $patrons );
	
	return apply_filters( 'patips_shortcode_patron_list', $html, $patrons, $filters, $raw_args );
}
add_shortcode( 'patronstips_patron_list', 'patips_shortcode_patron_list' );


/**
 * Shortcode to display posts
 * @since 0.11.0 (was patips_shortcode_patronage_posts_list)
 * @version 0.25.5
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_patron_post_list( $raw_args = array() ) {
	$default_args   = array_merge( patips_get_default_patron_post_filters(), array(
		'per_page'   => 12,
		'gray_out'   => 1,
		'image_only' => 0,
		'image_size' => '',
		'no_link'    => 0
	) );
	$args           = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_patron_posts' );
	$formatted_args = patips_format_string_patron_post_filters( $args );
	$filters        = patips_format_patron_post_filters( $formatted_args );
	
	// Sanitize additionnal args
	$filters[ 'image_size' ] = sanitize_title_with_dashes( $args[ 'image_size' ] );
	$filters[ 'gray_out' ]   = intval( $args[ 'gray_out' ] );
	$filters[ 'image_only' ] = intval( $args[ 'image_only' ] );
	$filters[ 'no_link' ]    = intval( $args[ 'no_link' ] );
	
	$filters = apply_filters( 'patips_shortcode_patron_post_list_filters', $filters, $raw_args );
	
	$post_list = patips_get_patron_post_list( $filters );
	
	if( ! $post_list ) {
		$post_list = '<div class="patips-no-results">' . esc_html__( 'No items found.', 'patrons-tips' ) . '</div>';
	}
	
	return apply_filters( 'patips_shortcode_patron_post_list', $post_list, $filters, $raw_args );
}
add_shortcode( 'patronstips_patron_posts', 'patips_shortcode_patron_post_list' );


/**
 * Shortcode to display patron current patronage
 * @since 0.15.0 (was patips_shortcode_patron_current_patronage_list)
 * @version 0.26.0
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_patron_status( $raw_args = array() ) {
	$default_args = array( 
		'user_id'   => get_current_user_id(), 
		'patron_id' => 0
	);
	$args = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_patron_status' );
	
	// Get patron data and history
	$patron_id = ! empty( $args[ 'patron_id' ] ) && is_numeric( $args[ 'patron_id' ] ) ? intval( $args[ 'patron_id' ] ) : 0;
	$user_id   = is_numeric( $args[ 'user_id' ] ) ? intval( $args[ 'user_id' ] ) : ( is_email( $args[ 'user_id' ] ) ? sanitize_email( $args[ 'user_id' ] ) : 0 );
	$patron    = $patron_id ? patips_get_patron_data( $patron_id ) : patips_get_user_patron_data( $user_id );
	
	$html = '';
	
	// If the user is not a patron
	if( empty( $patron[ 'history' ] ) ) {
		$html = patips_get_become_patron_notice();
	}
	
	// If the user is or was a patron
	else {
		// Get current dt and current period
		$timezone    = patips_get_wp_timezone();
		$now_dt      = new DateTime( 'now', $timezone );
		$period      = patips_get_current_period();
		$period_name = patips_get_period_name( $period );
		
		// Get tiers
		$tiers = patips_get_tiers_data();
		
		// Get current patronages
		$current_history = array();
		foreach( $patron[ 'history' ] as $history_entry ) {
			if( ! $history_entry[ 'active' ] ) { continue; }
			
			$start_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_start' ] . ' 00:00:00', $timezone );
			$end_dt   = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_end' ] . ' 23:59:59', $timezone );
			
			if( $start_dt <= $now_dt && $end_dt >= $now_dt ) {
				$current_history[] = $history_entry;
			}
		}
		
		// Get the current patronage list items
		$li_items = array();
		foreach( $current_history as $current_history_entry ) {
			$tier_id    = $current_history_entry[ 'tier_id' ];
			$tier       = isset( $tiers[ $tier_id ] ) ? $tiers[ $tier_id ] : array();
			/* translators: %s is the tier ID */
			$tier_title = ! empty( $tier[ 'title' ] ) ? $tier[ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
			
			$li = sprintf( 
				/* translators: %1$s = the Tier name. %2$s = formatted date (e.g. "September 30th 2024") */
				esc_html__( 'You are a "%1$s" patron until %2$s.', 'patrons-tips' ), 
				$tier_title, 
				patips_format_datetime( $current_history_entry[ 'date_end' ] . ' 00:00:00' )
			);
			
			$li = apply_filters( 'patips_patron_status_list_item', $li, $current_history_entry, $patron, $args );
			
			$li_items[] = $li;
		}
		
		ob_start();
		
		?>
		<div class='patips-patron-status'>
			<?php do_action( 'patips_shortcode_patron_status_before', $patron, $raw_args ); ?>
			
			<div class='patips-patron-status-list'>
				<?php
				// Display the current user patronage list
				foreach( $li_items as $li ) { ?>
					<div class='patips-patron-status-list-item'><?php echo wp_kses_post( $li ); ?></div>
				<?php } ?>
			</div>
			
			<?php do_action( 'patips_shortcode_patron_status_after', $patron, $raw_args ); ?>
		</div>
		<?php
		
		$html = ob_get_clean();
	}
	
	return apply_filters( 'patips_shortcode_patron_status', $html, $patron, $raw_args );
}
add_shortcode( 'patronstips_patron_status', 'patips_shortcode_patron_status' );


/**
 * Display the patron history table
 * @since 0.12.0
 * @version 0.26.0
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_patron_history( $raw_args = array() ) {
	// Sanitize shortcode attributes
	$default_args = array_merge( patips_get_default_patron_history_filters(), array( 
		'active'    => 1,
		'user_id'   => get_current_user_id(),
		'patron_id' => 0,
		'per_page'  => 12,
		'columns'   => array()
	) );
	$args = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_patron_history' );
	
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_history_filters( $formatted_args );
	
	// Get patron data and history
	if( empty( $filters[ 'patron_ids' ] ) ) {
		$patron_id = ! empty( $args[ 'patron_id' ] ) && is_numeric( $args[ 'patron_id' ] ) ? intval( $args[ 'patron_id' ] ) : 0;
		if( $patron_id ) {
			$filters[ 'patron_ids' ][] = $patron_id;
		} else {
			$user_id = is_numeric( $args[ 'user_id' ] ) ? intval( $args[ 'user_id' ] ) : ( is_email( $args[ 'user_id' ] ) ? sanitize_email( $args[ 'user_id' ] ) : 0 );
			$patron  = $user_id ? patips_get_user_patron_data( $user_id ) : array();
			if( ! empty( $patron[ 'id' ] ) ) {
				$filters[ 'patron_ids' ][] = $patron[ 'id' ];
			}
		}
	}
	
	// Get columns
	$column_titles = patips_get_patron_history_column_titles();
	$columns       = ! empty( $formatted_args[ 'columns' ] ) ? array_intersect( $formatted_args[ 'columns' ], array_keys( $column_titles ) ) : array();
	
	// Get patron history filters
	$filters = apply_filters( 'patips_shortcode_patron_history_filters', array_merge( $filters, array( 'columns' => $columns ) ), $raw_args );
	
	// Get HTML
	$history_list = ! empty( $filters[ 'patron_ids' ] ) ? patips_get_patron_history_list( $filters, $filters[ 'columns' ] ) : '';
	
	if( ! $history_list ) {
		$history_list = '<div class="patips-no-results">' . esc_html__( 'No patronage history.', 'patrons-tips' ) . '</div>';
	}
	
	return apply_filters( 'patips_shortcode_patron_history', $history_list, $filters, $raw_args );
}
add_shortcode( 'patronstips_patron_history', 'patips_shortcode_patron_history' );


/**
 * Shortcode to display number of patrons
 * @since 0.14.0
 * @version 0.25.5
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_patron_number( $raw_args = array() ) {
	$default_args = array_merge( patips_get_default_patron_filters(), array( 
		'current'   => 1,
		'active'    => 1,
		'zero_text' => '',
		'raw'       => false
	) );
	$args           = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_patron_number' );
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_filters( $formatted_args );
	
	// Sanitize additionnal args
	$args[ 'zero_text' ] = sanitize_text_field( $args[ 'zero_text' ] );
	$args[ 'raw' ]       = intval( $args[ 'raw' ] );
	
	$has_zero_text = isset( $raw_args[ 'zero_text' ] ) && $raw_args[ 'zero_text' ] !== '';
	
	// Get patron number
	$args      = apply_filters( 'patips_shortcode_patron_number_args', $args, $raw_args );
	$filters   = apply_filters( 'patips_shortcode_patron_number_filters', $filters, $raw_args );
	$patrons   = patips_get_patrons_data( $filters );
	$patron_nb = apply_filters( 'patips_patron_nb', count( $patrons ), $patrons, $args, $filters, $raw_args );
	
	ob_start();
	
	if( $args[ 'raw' ] ) {
		echo ! $patron_nb && $has_zero_text ? wp_kses_post( $args[ 'zero_text' ] ) : (int) $patron_nb;
	}
	else {
	?>
		<div class='patips-patron-nb-container'>
		<?php 
			if( ! $patron_nb && $has_zero_text ) {
			?>
				<div class='patips-zero-text'>
					<?php echo wp_kses_post( $args[ 'zero_text' ] ); ?>
				</div>
			<?php
			} else {
				/* translators: %s = a number. */
				echo sprintf( esc_html( _n( '%s patron', '%s patrons', (int) $patron_nb, 'patrons-tips' ) ), '<strong class="patips-patron-nb">' . (int) $patron_nb . '</strong>' );
			}
		?>
		</div>
	<?php
	}
	
	return apply_filters( 'patips_shortcode_patron_number', ob_get_clean(), intval( $patron_nb ), $patrons, $filters, $args, $raw_args );
}
add_shortcode( 'patronstips_patron_number', 'patips_shortcode_patron_number' );


/**
 * Shortcode to display total patronage income for the period
 * @since 0.15.0
 * @version 0.25.5
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_period_income( $raw_args = array() ) {
	$current_period = patips_get_current_period();
	$default_args   = array_merge( patips_get_default_patron_filters(), array( 
		'period'            => substr( $current_period[ 'start' ], 0, 7 ),
		'active'            => 1,
		'zero_text'         => '',
		'raw'               => false,
		'include_tax'       => true,
		'include_discounts' => false,
		'include_scheduled' => true,
		'include_manual'    => false,
		'decimals'          => false
	) );
	$args = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_period_income' );
		
	if( isset( $raw_args[ 'period' ] ) && ( $raw_args[ 'period' ] === 'current' || empty( $raw_args[ 'period' ] ) ) ) {
		$args[ 'period' ] = $default_args[ 'period' ];
	}
	
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_filters( $formatted_args );
	
	// Sanitize additionnal args
	$args[ 'zero_text' ]         = sanitize_text_field( $args[ 'zero_text' ] );
	$args[ 'raw' ]               = intval( $args[ 'raw' ] );
	$args[ 'include_tax' ]       = intval( $args[ 'include_tax' ] );
	$args[ 'include_discounts' ] = intval( $args[ 'include_discounts' ] );
	$args[ 'include_scheduled' ] = intval( $args[ 'include_scheduled' ] );
	$args[ 'include_manual' ]    = intval( $args[ 'include_manual' ] );
	$args[ 'decimals' ]          = is_numeric( $args[ 'decimals' ] ) ? intval( $args[ 'decimals' ] ) : false;
	
	$has_zero_text = isset( $raw_args[ 'zero_text' ] ) && $raw_args[ 'zero_text' ] !== '';
	
	// Get patrons with their history filtered for the period
	$args    = apply_filters( 'patips_shortcode_period_income_args', $args, $raw_args );
	$filters = apply_filters( 'patips_shortcode_period_income_filters', $filters, $raw_args );
	$patrons = patips_get_patrons_data( $filters );
	
	// Compute income
	$total = apply_filters( 'patips_period_income_total', patips_get_total_income_based_on_patrons_history( $patrons, $args[ 'include_manual' ] ), $patrons, $args, $filters, $raw_args );
	
	ob_start();
	
	if( $args[ 'raw' ] ) {
		echo ! $total && $has_zero_text ? wp_kses_post( $args[ 'zero_text' ] ) : ( $args[ 'decimals' ] !== false ? number_format( $total, $args[ 'decimals' ] ) : $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	else {
	?>
		<div class='patips-period-income-container'>
		<?php
			if( ! $total && $has_zero_text ) {
			?>
				<div class='patips-zero-text'>
					<?php echo wp_kses_post( $args[ 'zero_text' ] ); ?>
				</div>
			<?php
			} else {
			?>
				<strong class='patips-period-income'>
					<?php echo wp_kses_post( patips_format_price( $total, $args[ 'decimals' ] !== false ? array( 'decimals' => $args[ 'decimals' ] ) : array() ) ); ?>
				</strong>
			<?php
			}
		?>
		</div>
	<?php
	}
	
	return apply_filters( 'patips_shortcode_period_income', ob_get_clean(), $patrons, $filters, $args, $raw_args );
}
add_shortcode( 'patronstips_period_income', 'patips_shortcode_period_income' );


/**
 * Shortcode to display period results (number of patrons and incomes)
 * @since 0.18.0
 * @version 0.25.5
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_period_results( $raw_args = array() ) {
	$current_period = patips_get_current_period();
	$default_args   = array_merge( patips_get_default_patron_filters(), array( 
		'period'            => substr( $current_period[ 'start' ], 0, 7 ),
		'active'            => 1,
		'display'           => 'patron_nb', // 'both', 'patron_nb', or 'income'
		'zero_text'         => '',
		'raw'               => false,
		'include_tax'       => true,
		'include_discounts' => false,
		'include_scheduled' => true,
		'include_manual'    => false,
		'decimals'          => false
	) );
	$args = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_period_results' );
	
	if( isset( $raw_args[ 'period' ] ) && ( $raw_args[ 'period' ] === 'current' || empty( $raw_args[ 'period' ] ) ) ) {
		$args[ 'period' ] = $default_args[ 'period' ];
	}
	
	$formatted_args = patips_format_string_patron_filters( $args );
	$filters        = patips_format_patron_filters( $formatted_args );
	
	// Sanitize additionnal args
	$args[ 'display' ]           = in_array( $args[ 'display' ], array( 'both', 'patron_nb', 'income' ) , true ) ? $args[ 'display' ] : $default_args[ 'display' ];
	$args[ 'zero_text' ]         = sanitize_text_field( $args[ 'zero_text' ] );
	$args[ 'raw' ]               = intval( $args[ 'raw' ] );
	$args[ 'include_tax' ]       = intval( $args[ 'include_tax' ] );
	$args[ 'include_discounts' ] = intval( $args[ 'include_discounts' ] );
	$args[ 'include_scheduled' ] = intval( $args[ 'include_scheduled' ] );
	$args[ 'include_manual' ]    = intval( $args[ 'include_manual' ] );
	$args[ 'decimals' ]          = is_numeric( $args[ 'decimals' ] ) ? intval( $args[ 'decimals' ] ) : false;
	
	$has_zero_text = isset( $raw_args[ 'zero_text' ] ) && $raw_args[ 'zero_text' ] !== '';
	
	// Get patrons with their history filtered for the period, and compute their number and income
	$args      = apply_filters( 'patips_shortcode_period_results_args', $args, $raw_args );
	$filters   = apply_filters( 'patips_shortcode_period_results_filters', $filters, $raw_args );
	$patrons   = patips_get_patrons_data( $filters );
	$patron_nb = apply_filters( 'patips_patron_nb', count( $patrons ), $patrons, $args, $filters, $raw_args );
	$total     = apply_filters( 'patips_period_income_total', patips_get_total_income_based_on_patrons_history( $patrons, $args[ 'include_manual' ] ), $patrons, $args, $filters, $raw_args );
	
	ob_start();
	?>
	<div class='patips-period-results'>
	<?php
		// If there are no patrons and no income, display the zero text once
		if( $args[ 'display' ] === 'both' && ! $patron_nb && ! $total && $has_zero_text ) {
		?>
			<div class='patips-zero-text'>
				<?php echo wp_kses_post( $args[ 'zero_text' ] ); ?>
			</div>
		<?php
		}
		else {
			// Display number of patrons
			if( in_array( $args[ 'display' ], array( 'both', 'patron_nb' ) , true ) ) {
			?>
				<div class='patips-patron-nb-container'>
				<?php 
					if( ! $patron_nb && $has_zero_text ) {
					?>
						<div class='patips-zero-text'>
							<?php echo wp_kses_post( $args[ 'zero_text' ] ); ?>
						</div>
					<?php
					} else {
						if( $args[ 'raw' ] ) {
							echo (int) $patron_nb;
						} else {
							/* translators: %s = a number. */
							echo sprintf( esc_html( _n( '%s patron', '%s patrons', (int) $patron_nb, 'patrons-tips' ) ), '<strong class="patips-patron-nb">' . (int) $patron_nb . '</strong>' );
						}
					}
				?>
				</div>
			<?php
			}
			
			// Display total income
			if( in_array( $args[ 'display' ], array( 'both', 'income' ) , true ) ) {
			?>
				<div class='patips-period-income-container'>
				<?php
					if( ! $total && $has_zero_text ) {
					?>
						<div class='patips-zero-text'>
							<?php echo wp_kses_post( $args[ 'zero_text' ] ); ?>
						</div>
					<?php
					} else {
						if( $args[ 'raw' ] ) {
							echo $args[ 'decimals' ] !== false ? number_format( $total, $args[ 'decimals' ] ) : $total; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
						?>
							<strong class='patips-period-income'>
								<?php echo wp_kses_post( patips_format_price( $total, $args[ 'decimals' ] !== false ? array( 'decimals' => $args[ 'decimals' ] ) : array() ) ); ?>
							</strong>
						<?php
						}
					}
				?>
				</div>
			<?php
			}
		}
	?>
	</div>
	<?php
	
	return apply_filters( 'patips_shortcode_period_results', ob_get_clean(), $patrons, $filters, $args, $raw_args );
}
add_shortcode( 'patronstips_period_results', 'patips_shortcode_period_results' );


/**
 * Shortcode to display period name
 * @since 0.15.0
 * @version 0.25.5
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_period_name( $raw_args = array() ) {
	$default_args = array( 
		'show_period_start' => '',
		'show_period_end'   => '',
		'date_format'       => '',
		'date'              => '',
		'until'             => '',
		'duration'          => patips_get_default_period_duration(),
		'raw'               => false
	);
	$args = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_period_name' );
	
	// Sanitize args
	$args[ 'show_period_start' ] = intval( $args[ 'show_period_start' ] );
	$args[ 'show_period_end' ]   = intval( $args[ 'show_period_end' ] );
	$args[ 'date_format' ]       = sanitize_text_field( $args[ 'date_format' ] );
	$args[ 'duration' ]          = intval( $args[ 'duration' ] );
	$args[ 'raw' ]               = intval( $args[ 'raw' ] );
	
	// Sanitize relative date format
	$date_keys = array( 'date', 'until' );
	foreach( $date_keys as $key ) {
		$date_sanitized = ! empty( $args[ $key ] ) ? sanitize_text_field( $args[ $key ] ) : '';
		if( $date_sanitized && ! patips_sanitize_date( $date_sanitized ) ) {
			if( (bool) strtotime( $date_sanitized ) ) {
				$dt = new DateTime( $date_sanitized );
				$args[ $key ] = $dt->format( 'Y-m-d' );
			} else {
				$args[ $key ] = '';
			}
		}
	}
	
	// Allow plugins to change args
	$args = apply_filters( 'patips_shortcode_period_name_args', $args, $raw_args );
	
	// Compute name
	$period      = patips_get_period_by_date( $args[ 'date' ], $args[ 'duration' ], $args[ 'until' ] );
	$period_name = '';
	
	if( $period ) {
		if( $args[ 'show_period_start' ] ) {
			$period_name = patips_format_datetime( $period[ 'start' ], $args[ 'date_format' ] );
		} else if( $args[ 'show_period_end' ] ) {
			$period_name = patips_format_datetime( $period[ 'end' ], $args[ 'date_format' ] );
		} else {
			$period_name = patips_get_period_name( $period );
		}
	}
	
	$period_name = esc_html( $period_name );
	
	// Prepare HTML to return
	if( ! $args[ 'raw' ] ) {
		$period_name = '<div class="patips-period-name">' . $period_name . '</div>';
	}
	
	return apply_filters( 'patips_shortcode_period_name', $period_name, $args, $raw_args );
}
add_shortcode( 'patronstips_period_name', 'patips_shortcode_period_name' );


/**
 * Shortcode to display period media
 * @since 0.15.0
 * @version 0.23.0
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_period_media( $raw_args = array() ) {
	$default_args = array_merge( patips_get_default_patron_post_filters(), array( 
		'restricted' => 1,
		'per_page'   => 1,
		'gray_out'   => 0,
		'image_only' => 1,
		'image_size' => 'large',
		'no_link'    => 1,
		'date'       => '',
		'until'      => '',
		'duration'   => patips_get_default_period_duration()
	) );
	$args = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_period_media' );
	
	// Sanitize args
	$args[ 'gray_out' ]   = intval( $args[ 'gray_out' ] );
	$args[ 'image_only' ] = intval( $args[ 'image_only' ] );
	$args[ 'image_size' ] = sanitize_title_with_dashes( $args[ 'image_size' ] );
	$args[ 'no_link' ]    = intval( $args[ 'no_link' ] );
	$args[ 'duration' ]   = intval( $args[ 'duration' ] );

	// Sanitize relative date format
	$date_keys = array( 'date', 'until' );
	foreach( $date_keys as $key ) {
		$date_sanitized = ! empty( $args[ $key ] ) ? sanitize_text_field( $args[ $key ] ) : '';
		if( $date_sanitized && ! patips_sanitize_date( $date_sanitized ) ) {
			if( (bool) strtotime( $date_sanitized ) ) {
				$dt = new DateTime( $date_sanitized );
				$args[ $key ] = $dt->format( 'Y-m-d' );
			} else {
				$args[ $key ] = '';
			}
		}
	}
	
	// Get period
	$period = patips_get_period_by_date( $args[ 'date' ], $args[ 'duration' ], $args[ 'until' ] );
	
	// Get restricted attachments
	$filters = array_merge( $args, patips_format_patron_post_filters( array_merge( $args, array(
		'types' => array( 'attachment' ),
		'from'  => $period[ 'start' ],
		'to'    => $period[ 'end' ]
	) ) ) );
	$filters = apply_filters( 'patips_shortcode_period_media_filters', $filters, $raw_args );
	
	$post_list = patips_get_patron_post_list( $filters );
	
	if( ! $post_list ) {
		$post_list = '<div class="patips-no-results">' . esc_html__( 'No media for this period.', 'patrons-tips' ) . '</div>';
	}
	
	return apply_filters( 'patips_shortcode_period_media', $post_list, $filters, $raw_args );
}
add_shortcode( 'patronstips_period_media', 'patips_shortcode_period_media' );


/**
 * Shortcode to display tier add to cart form
 * @since 0.22.0 (was patips_shortcode_add_to_cart_tier_form)
 * @version 0.23.0
 * @param array $raw_args
 * @return string
 */
function patips_shortcode_subscription_form( $raw_args = array() ) {
	$default_args   = patips_get_default_subscription_form_filters();
	$args           = shortcode_atts( $default_args, array_change_key_case( (array) $raw_args, CASE_LOWER ), 'patronstips_tier_form' );
	$formatted_args = patips_format_string_subscription_form_filters( $args );
	$filters        = patips_format_subscription_form_filters( $formatted_args );
	
	$filters = apply_filters( 'patips_shortcode_subscription_form_filters', $filters, $raw_args );
	
	$form = patips_get_subscription_form( $filters );
	
	if( ! $form ) {
		$form = '<div class="patips-no-results">' . esc_html__( 'No tiers available.', 'patrons-tips' ) . '</div>';
	}
	
	return apply_filters( 'patips_shortcode_subscription_form', $form, $filters, $raw_args );
}
add_shortcode( 'patronstips_tier_form', 'patips_shortcode_subscription_form' );