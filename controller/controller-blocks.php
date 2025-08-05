<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add block categories
 * @since 0.14.0
 * @version 0.17.0
 * @param array $categories
 * @return string
 */
function patips_block_categories( $categories ) {
    $categories[] = array(
    	'slug'  => 'patrons-tips',
    	'title' => 'Patrons Tips'
    );
    return apply_filters( 'patips_block_categories', $categories );
}
add_filter( 'block_categories_all', 'patips_block_categories' );


/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 * @since 0.17.0
 * @version 1.0.2
 */
function patips_register_block_types() {
	// Register scripts early to be used in block editor
	wp_register_script( 'patips-js-global-variables', PATIPS_PLUGIN_URL . 'js/global-variables.min.js', array( 'jquery' ), PATIPS_PLUGIN_VERSION, false );
	wp_register_script( 'patips-js-global-functions', PATIPS_PLUGIN_URL . 'js/global-functions.min.js', array( 'jquery', 'patips-js-global-variables' ), PATIPS_PLUGIN_VERSION, true );

	// Register blocks
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/tier-form', 
		array( 
			'render_callback' => 'patips_tier_form_block_render' // Render with PHP
		)
	);
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/period-results', 
		array( 
			'render_callback' => 'patips_period_results_block_render' // Render with PHP
		)
	);
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/period-media', 
		array( 
			'render_callback' => 'patips_period_media_block_render' // Render with PHP
		)
	);
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/patron-status', 
		array( 
			'render_callback' => 'patips_patron_status_block_render' // Render with PHP
		)
	);
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/patron-list', 
		array( 
			'render_callback' => 'patips_patron_list_block_render' // Render with PHP
		)
	);
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/patron-post-list', 
		array( 
			'render_callback' => 'patips_patron_post_list_block_render' // Render with PHP
		)
	);
	register_block_type( PATIPS_PLUGIN_DIR . 'blocks/patron-history', 
		array( 
			'render_callback' => 'patips_patron_history_block_render' // Render with PHP
		)
	);
	
	do_action( 'patips_register_block_types' );
}
add_action( 'init', 'patips_register_block_types' );


/**
 * Register translations for blocks
 * @see https://developer.wordpress.org/block-editor/how-to-guides/internationalization/#load-the-translation-file
 * @since 0.25.6
 * @version 1.0.2
 */
function patips_register_block_translations( $locale = '' ) {
	if( ! $locale ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : ( is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
		$locale = apply_filters( 'plugin_locale', $locale, 'patrons-tips' );
	}
	
	$add_ons = patips_get_add_ons_data();
	
	$handles_per_domain = apply_filters( 'patips_block_translation_handles', array(
		'patrons-tips' => array(
			'blocks/tier-form/index.js'        => 'patrons-tips-tier-form-editor-script',
			'blocks/period-results/index.js'   => 'patrons-tips-period-results-editor-script',
			'blocks/period-media/index.js'     => 'patrons-tips-period-media-editor-script',
			'blocks/patron-status/index.js'    => 'patrons-tips-patron-status-editor-script',
			'blocks/patron-list/index.js'      => 'patrons-tips-patron-list-editor-script',
			'blocks/patron-post-list/index.js' => 'patrons-tips-patron-post-list-editor-script',
			'blocks/patron-history/index.js'   => 'patrons-tips-patron-history-editor-script'
		)
	) );
	
	foreach( $handles_per_domain as $domain => $handles ) {
		// Paths to try loading the translation files from, in the order
		// These are the same paths as the ones for .mo files (see patips_load_textdomain())
		$paths = array(
			// Load .json from wp-content/languages/**plugin-name**/
			WP_LANG_DIR . '/' . $domain . '/',
			// Load .json from wp-content/languages/plugins/
			WP_LANG_DIR . '/plugins/'
		);
		
		// Fallback on .json from wp-content/plugins/**plugin-name**/languages
		if( $domain === 'patrons-tips' ) {
			$paths[] = PATIPS_PLUGIN_DIR . 'languages/';
		} else {
			foreach( $add_ons as $add_on ) {
				if( $domain === $add_on[ 'plugin_name' ] && defined( $add_on[ 'dir_const' ] ) ) {
					$paths[] = constant( $add_on[ 'dir_const' ] ) . '/languages/';
					break;
				}
			}
		}
		
		foreach( $handles as $rel_path => $handle ) {
			$md5      = strpos( $rel_path, '/' ) !== false ? md5( $rel_path ) : $rel_path;
			$filename = $domain . '-' . $locale . '-' . $md5 . '.json';
			
			foreach( $paths as $path ) {
				if( ! file_exists( trailingslashit( $path ) . $filename ) ) { continue; }
				
				// Register translation and break
				$is_translated = wp_set_script_translations( $handle, $domain, $path );
				
				if( $is_translated ) {
					break;
				}
			}
		}
	}
}
add_action( 'patips_register_block_types', 'patips_register_block_translations', 100 );


/**
 * Hide legacy widgets in favor of blocks
 * @since 0.17.0
 * @version 1.0.1
 * @param array $widget_types
 * @return string
 */
function patips_hide_legacy_widgets( $widget_types ) {
	$widget_types[] = 'patips_widget_tier_form';
	$widget_types[] = 'patips_widget_patron_number';
	$widget_types[] = 'patips_widget_period_income';
	$widget_types[] = 'patips_widget_period_media';
	
    return apply_filters( 'patips_hidden_legacy_widgets', $widget_types );
}
add_filter( 'widget_types_to_hide_from_legacy_widget_block', 'patips_hide_legacy_widgets' );


/**
 * Generate Tier Form block HTML
 * @since 0.17.0
 * @version 0.25.0
 * @param array $block_atts
 * @return string
 */
function patips_tier_form_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_tier_form_args', array( 
		'tiers'             => isset( $block_atts[ 'tiers' ] ) ? $block_atts[ 'tiers' ] : array(),
		'default_tier'      => isset( $block_atts[ 'defaultTier' ] ) ? $block_atts[ 'defaultTier' ] : '',
		'frequencies'       => isset( $block_atts[ 'frequencies' ] ) ? $block_atts[ 'frequencies' ] : array(),
		'default_frequency' => isset( $block_atts[ 'defaultFrequency' ] ) ? $block_atts[ 'defaultFrequency' ] : '',
		'decimals'          => isset( $block_atts[ 'decimals' ] ) ? intval( $block_atts[ 'decimals' ] ) : 0,
		'submit_label'      => isset( $block_atts[ 'submitLabel' ] ) ? sanitize_text_field( $block_atts[ 'submitLabel' ] ) : ''
	), $block_atts );
	
	$html = patips_shortcode_subscription_form( $args );

	return apply_filters( 'patips_block_tier_form', $html, $args, $block_atts );
}


/**
 * Generate Period Results block HTML
 * @since 0.18.0
 * @version 0.25.0
 * @param array $block_atts
 * @return string
 */
function patips_period_results_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_period_results_args', array( 
		'display'           => isset( $block_atts[ 'display' ] ) && in_array( $block_atts[ 'display' ], array( 'both', 'patron_nb', 'income' ) , true ) ? $block_atts[ 'display' ] : 'patron_nb',
		'period'            => isset( $block_atts[ 'period' ] ) ? sanitize_text_field( $block_atts[ 'period' ] ) : '',
		'zero_text'         => isset( $block_atts[ 'zeroText' ] ) ? sanitize_text_field( $block_atts[ 'zeroText' ] ) : '',
		'raw'               => ! empty( $block_atts[ 'raw' ] ),
		'decimals'          => isset( $block_atts[ 'decimals' ] ) ? intval( $block_atts[ 'decimals' ] ) : 0,
		'include_tax'       => ! empty( $block_atts[ 'includeTax' ] ),
		'include_discounts' => ! empty( $block_atts[ 'includeDiscounts' ] ),
		'include_scheduled' => ! empty( $block_atts[ 'includeScheduled' ] ),
		'include_manual'    => ! empty( $block_atts[ 'includeManual' ] )
	), $block_atts );
	
	$html = patips_shortcode_period_results( $args );

	return apply_filters( 'patips_block_period_results', $html, $args, $block_atts );
}


/**
 * Generate Period Media block HTML
 * @since 0.18.0
 * @version 1.0.4
 * @param array $block_atts
 * @return string
 */
function patips_period_media_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_period_media_args', array( 
		'date'       => ! empty( $block_atts[ 'period' ] ) ? sanitize_text_field( $block_atts[ 'period' ] ) : '',
		'categories' => ! empty( $block_atts[ 'categories' ] ) && is_array( $block_atts[ 'categories' ] ) ? $block_atts[ 'categories' ] : array(),
		'image_size' => ! empty( $block_atts[ 'imageSize' ] ) && in_array( $block_atts[ 'imageSize' ], array( 'full', 'large', 'medium', 'thumbnail' ), true ) ? $block_atts[ 'imageSize' ] : '',
		'per_page'   => ! empty( $block_atts[ 'perPage' ] ) ? intval( $block_atts[ 'perPage' ] ) : 1
	), $block_atts );

	$html = patips_shortcode_period_media( $args );

	return apply_filters( 'patips_block_period_media', $html, $args, $block_atts );
}


/**
 * Generate Patron Status block HTML
 * @since 0.19.0
 * @version 0.25.0
 * @param array $block_atts
 * @return string
 */
function patips_patron_status_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_patron_status_args', array( 
		'user_id'   => ! empty( $block_atts[ 'userID' ] ) ? intval( $block_atts[ 'userID' ] ) : get_current_user_id(),
		'patron_id' => ! empty( $block_atts[ 'patronID' ] ) ? intval( $block_atts[ 'patronID' ] ) : 0
	), $block_atts );
	
	$html = patips_shortcode_patron_status( $args );

	return apply_filters( 'patips_block_patron_status', $html, $args, $block_atts );
}


/**
 * Generate Patron List block HTML
 * @since 0.19.0
 * @version 0.25.0
 * @param array $block_atts
 * @return string
 */
function patips_patron_list_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_patron_list_args', array( 
		'period'   => isset( $block_atts[ 'period' ] ) ? sanitize_text_field( $block_atts[ 'period' ] ) : '',
		'date'     => isset( $block_atts[ 'date' ] ) ? sanitize_text_field( $block_atts[ 'date' ] ) : '',
		'current'  => ! empty( $block_atts[ 'current' ] ),
		'tier_ids' => isset( $block_atts[ 'tiers' ] ) ? patips_ids_to_array( $block_atts[ 'tiers' ] ) : array()
	), $block_atts );
	
	$html = ! empty( $block_atts[ 'showThanks' ] ) ? patips_shortcode_patron_list_thanks( $args ) : patips_shortcode_patron_list( $args );

	return apply_filters( 'patips_block_patron_list', $html, $args, $block_atts );
}


/**
 * Generate Patron Post List block HTML
 * @since 0.19.0
 * @version 0.25.0
 * @param array $block_atts
 * @return string
 */
function patips_patron_post_list_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_patron_post_list_args', array( 
		'user_id'    => ! empty( $block_atts[ 'userID' ] ) ? intval( $block_atts[ 'userID' ] ) : get_current_user_id(),
		'user_email' => ! empty( $block_atts[ 'userEmail' ] ) ? sanitize_text_field( $block_atts[ 'userEmail' ] ) : '',
		'patron_id'  => ! empty( $block_atts[ 'patronID' ] ) ? intval( $block_atts[ 'patronID' ] ) : 0,
		'types'      => ! empty( $block_atts[ 'types' ] ) && is_array( $block_atts[ 'types' ] ) ? $block_atts[ 'types' ] : array( 'post' ), // 'post', 'page', 'attachment', 'product'
		'categories' => ! empty( $block_atts[ 'categories' ] ) && is_array( $block_atts[ 'categories' ] ) ? $block_atts[ 'categories' ] : array(),
		'tags'       => ! empty( $block_atts[ 'tags' ] ) && is_array( $block_atts[ 'tags' ] ) ? $block_atts[ 'tags' ] : array(),
		'cat_and_tag' => ! empty( $block_atts[ 'catAndTag' ] ),
		'restricted'  => isset( $block_atts[ 'restricted' ] ) && is_numeric( $block_atts[ 'restricted' ] ) && intval( $block_atts[ 'restricted' ] ) >= 0 ? intval( $block_atts[ 'restricted' ] ) : false, // false = all, 1 = restricted posts only, 0 = unrestricted posts only
		'unlocked'    => isset( $block_atts[ 'unlocked' ] ) && is_numeric( $block_atts[ 'unlocked' ] ) && intval( $block_atts[ 'unlocked' ] ) >= 0 ? intval( $block_atts[ 'unlocked' ] ) : false, // false = all, 1 = unlocked posts only, 0 = locked posts only
		'per_page'    => ! empty( $block_atts[ 'perPage' ] ) ? intval( $block_atts[ 'perPage' ] ) : 12,
		'gray_out'    => ! empty( $block_atts[ 'grayOut' ] ),
		'image_only'  => ! empty( $block_atts[ 'imageOnly' ] ),
		'image_size'  => ! empty( $block_atts[ 'imageSize' ] ) && in_array( $block_atts[ 'imageSize' ], array( 'full', 'large', 'medium', 'thumbnail' ), true ) ? $block_atts[ 'imageSize' ] : '',
		'no_link'     => ! empty( $block_atts[ 'noLink' ] )
	), $block_atts );
	
	$html = patips_shortcode_patron_post_list( $args );

	return apply_filters( 'patips_block_patron_post_list', $html, $args, $block_atts );
}


/**
 * Generate Patron History block HTML
 * @since 0.19.0
 * @version 0.25.0
 * @param array $block_atts
 * @return string
 */
function patips_patron_history_block_render( $block_atts ) {
	$args = apply_filters( 'patips_block_patron_history_args', array( 
		'user_id'   => ! empty( $block_atts[ 'userID' ] ) ? intval( $block_atts[ 'userID' ] ) : get_current_user_id(),
		'patron_id' => ! empty( $block_atts[ 'patronID' ] ) ? intval( $block_atts[ 'patronID' ] ) : 0,
		'tier_ids'  => isset( $block_atts[ 'tiers' ] ) ? patips_ids_to_array( $block_atts[ 'tiers' ] ) : array(),
		'period'    => isset( $block_atts[ 'period' ] ) ? sanitize_text_field( $block_atts[ 'period' ] ) : '',
		'date'      => isset( $block_atts[ 'date' ] ) ? sanitize_text_field( $block_atts[ 'date' ] ) : '',
		'current'   => ! empty( $block_atts[ 'current' ] ),
		'active'    => ! empty( $block_atts[ 'active' ] ),
		'columns'   => isset( $block_atts[ 'columns' ] ) ? patips_str_ids_to_array( $block_atts[ 'columns' ] ) : array(),
		'per_page'  => ! empty( $block_atts[ 'perPage' ] ) ? intval( $block_atts[ 'perPage' ] ) : 12
	), $block_atts );
	
	$html = patips_shortcode_patron_history( $args );

	return apply_filters( 'patips_block_patron_history', $html, $args, $block_atts );
}


/**
 * AJAX Controller - Get period results
 * @since 0.18.0
 * @version 0.26.3
 */
function patips_controller_get_period_results() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_get_period_results', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'get_period_results' ); }
	
	// Sanitize parameters
	$args = array(
		'include_tax'       => isset( $_POST[ 'include_tax' ] ) ? ! empty( $_POST[ 'include_tax' ] ) : true,
		'include_discounts' => isset( $_POST[ 'include_discounts' ] ) ? ! empty( $_POST[ 'include_discounts' ] ) : false,
		'include_scheduled' => isset( $_POST[ 'include_scheduled' ] ) ? ! empty( $_POST[ 'include_scheduled' ] ) : true,
		'include_manual'    => isset( $_POST[ 'include_manual' ] ) ? ! empty( $_POST[ 'include_manual' ] ) : false,
	);
	
	$period_date = isset( $_POST[ 'period' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'period' ] ) ) : '';
	
	// Sanitize relative date format
	if( $period_date && ! patips_sanitize_date( $period_date ) && (bool) strtotime( $period_date ) ) {
		$timezone = patips_get_wp_timezone();
		$dt       = new DateTime( $period_date, $timezone );
		$period_date = $dt->format( 'Y-m-d' );
	}
	$period_date = patips_sanitize_date( $period_date );
	
	$period = $period_date ? patips_get_period_by_date( $period_date ) : patips_get_current_period();
	
	$filters = patips_format_patron_filters( array( 
		'period' => substr( $period[ 'start' ], 0, 7 ),
		'active' => 1
	) );
	
	$patrons = patips_get_patrons_data( $filters );
	
	$response = array(
		'status'    => 'success',
		'patron_nb' => apply_filters( 'patips_patron_nb', count( $patrons ), $patrons, $args, $filters, $_POST ),
		'income'    => apply_filters( 'patips_period_income_total', patips_get_total_income_based_on_patrons_history( $patrons, $args[ 'include_manual' ] ), $patrons, $args, $filters, $_POST )
	);
	
	patips_send_json( $response, 'get_period_results' );
}
add_action( 'wp_ajax_patipsGetPeriodResults', 'patips_controller_get_period_results' );
add_action( 'wp_ajax_nopriv_patipsGetPeriodResults', 'patips_controller_get_period_results' );


/**
 * AJAX Controller - Get period media
 * @since 0.18.0
 * @version 1.0.4
 */
function patips_controller_get_period_media() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_get_period_media', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'get_period_media' ); }
	
	$args = array( 
		'date'       => isset( $_POST[ 'period' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'period' ] ) ) : '',
		'categories' => isset( $_POST[ 'categories' ] ) ? patips_ids_to_array( patips_maybe_decode_json( wp_unslash( $_POST[ 'categories' ] ) ) ) : array(),
		'image_size' => isset( $_POST[ 'image_size' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'image_size' ] ) ) : 'large',
		'per_page'   => isset( $_POST[ 'per_page' ] ) ? intval( $_POST[ 'per_page' ] ) : 1
	);
	
	$html = patips_shortcode_period_media( $args );
	
	$response = array(
		'status' => 'success',
		'html'   => $html
	);
	
	patips_send_json( $response, 'get_period_media' );
}
add_action( 'wp_ajax_patipsGetPeriodMedia', 'patips_controller_get_period_media' );
add_action( 'wp_ajax_nopriv_patipsGetPeriodMedia', 'patips_controller_get_period_media' );


/**
 * AJAX Controller - Get patron list
 * @since 0.19.0
 * @version 0.26.3
 */
function patips_controller_get_patron_list() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_get_patron_list', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'get_patron_list' ); }
	
	$tier_ids = isset( $_POST[ 'tier_ids' ] ) ? patips_ids_to_array( patips_maybe_decode_json( wp_unslash( $_POST[ 'tier_ids' ] ) ) ) : array();
	
	$args = array(
		'period'   => isset( $_POST[ 'period' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'period' ] ) ) : '',
		'date'     => isset( $_POST[ 'date' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'date' ] ) ) : '',
		'current'  => ! empty( $_POST[ 'current' ] ),
		'tier_ids' => $tier_ids
	);
	
	// Remove edit links from list
	add_filter( 'patips_patron_list_edit_links', '__return_false', 100 );
	
	$html = ! empty( $_POST[ 'show_thanks' ] ) ? patips_shortcode_patron_list_thanks( $args ) : patips_shortcode_patron_list( $args );
	
	// Reset edit links hook
	remove_filter( 'patips_patron_list_edit_links', '__return_false', 100 );
	
	$response = array(
		'status' => 'success',
		'html'   => $html
	);
	
	patips_send_json( $response, 'get_patron_list' );
}
add_action( 'wp_ajax_patipsGetPatronList', 'patips_controller_get_patron_list' );
add_action( 'wp_ajax_nopriv_patipsGetPatronList', 'patips_controller_get_patron_list' );


/**
 * AJAX Controller - Get patron post list
 * @since 0.19.0
 * @verison 0.26.3
 */
function patips_controller_get_patron_post_list() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_nonce_get_patron_post_list', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_invalid_nonce( 'get_patron_post_list' ); }
	
	$types      = isset( $_POST[ 'types' ] ) ? patips_str_ids_to_array( patips_maybe_decode_json( wp_unslash( $_POST[ 'types' ] ) ) ) : array();
	$categories = isset( $_POST[ 'categories' ] ) ? patips_ids_to_array( patips_maybe_decode_json( wp_unslash( $_POST[ 'categories' ] ) ) ) : array();
	$tags       = isset( $_POST[ 'tags' ] ) ? patips_ids_to_array( patips_maybe_decode_json( wp_unslash( $_POST[ 'tags' ] ) ) ) : array();
	
	$args = array(
		'user_id'     => ! empty( $_POST[ 'user_id' ] ) ? intval( $_POST[ 'user_id' ] ) : get_current_user_id(),
		'user_email'  => ! empty( $_POST[ 'user_email' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'user_email' ] ) ) : '',
		'patron_id'   => ! empty( $_POST[ 'patron_id' ] ) ? intval( $_POST[ 'patron_id' ] ) : 0,
		'types'       => $types ? $types : array( 'post' ),
		'categories'  => $categories,
		'tags'        => $tags,
		'cat_and_tag' => ! empty( $_POST[ 'cat_and_tag' ] ),
		'restricted'  => isset( $_POST[ 'restricted' ] ) && is_numeric( $_POST[ 'restricted' ] ) && intval( $_POST[ 'restricted' ] ) >= 0 ? intval( $_POST[ 'restricted' ] ) : false,
		'unlocked'    => isset( $_POST[ 'unlocked' ] ) && is_numeric( $_POST[ 'unlocked' ] ) && intval( $_POST[ 'unlocked' ] ) >= 0 ? intval( $_POST[ 'unlocked' ] ) : false,
		'per_page'    => ! empty( $_POST[ 'per_page' ] ) ? intval( $_POST[ 'per_page' ] ) : 12,
		'gray_out'    => ! empty( $_POST[ 'gray_out' ] ),
		'image_only'  => ! empty( $_POST[ 'image_only' ] ),
		'image_size'  => ! empty( $_POST[ 'image_size' ] ) && in_array( wp_unslash( $_POST[ 'image_size' ] ), array( 'full', 'large', 'medium', 'thumbnail' ), true ) ? sanitize_title_with_dashes( wp_unslash( $_POST[ 'image_size' ] ) ) : ''
	);
	
	// Remove links in editor
	$args[ 'no_link' ] = true;
	
	$html = patips_shortcode_patron_post_list( $args );
	
	$response = array(
		'status' => 'success',
		'html'   => $html
	);
	
	patips_send_json( $response, 'get_patron_post_list' );
}
add_action( 'wp_ajax_patipsGetPatronPostList', 'patips_controller_get_patron_post_list' );
add_action( 'wp_ajax_nopriv_patipsGetPatronPostList', 'patips_controller_get_patron_post_list' );