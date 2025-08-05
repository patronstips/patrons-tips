<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// PAGES

/**
 * Get default page template
 * @since 0.20.0
 * @version 1.0.2
 * @param string $page
 * @return string
 */
function patips_get_page_template( $page ) {
	ob_start();
	if( file_exists( PATIPS_PLUGIN_DIR . 'view/templates/' . $page . '.php' ) ) {
		include( PATIPS_PLUGIN_DIR . 'view/templates/' . $page . '.php' );
	}
	$content = ob_get_clean();
	
	return apply_filters( 'patips_page_template_' . $page, $content, $page );
}


/**
 * Create default patron area page
 * @since 0.20.0
 * @return int|WP_Error
 */
function patips_create_patron_area_page() {
	// If the page already exists, do nothing
	$page_id = patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
	$page    = $page_id && intval( $page_id ) > 0 ? get_page( $page_id ) : null;
	if( $page && $page->post_status !== 'trash' ) { 
		return $page_id;
	}
	
	// Page data
	$page_data = array(
		'post_title'   => esc_html__( 'Patron area', 'patrons-tips' ),
		'post_content' => patips_get_page_template( 'patron-area' ),
		'post_status'  => 'draft',
		'post_type'    => 'page'
	);
	
	$page_id = wp_insert_post( $page_data );
	
	if( is_numeric( $page_id ) ) {
		patips_update_option( 'patips_settings_general', 'patron_area_page_id', $page_id );
	}
	
	return $page_id;
}


/**
 * Create default sales page
 * @since 0.20.0
 * @return int|WP_Error
 */
function patips_create_sales_page() {
	// If the page already exists, do nothing
	$page_id = patips_get_option( 'patips_settings_general', 'sales_page_id' );
	$page    = $page_id && intval( $page_id ) > 0 ? get_page( $page_id ) : null;
	if( $page && $page->post_status !== 'trash' ) { 
		return $page_id;
	}
	
	// Page data
	$page_data = array(
		'post_title'   => esc_html__( 'Become my patron', 'patrons-tips' ),
		'post_content' => patips_get_page_template( 'sales-page' ),
		'post_status'  => 'draft',
		'post_type'    => 'page'
	);
	
	$page_id = wp_insert_post( $page_data );
	
	if( is_numeric( $page_id ) ) {
		patips_update_option( 'patips_settings_general', 'sales_page_id', $page_id );
		update_post_meta( $page_id, '_wp_page_template', 'patips-sales-page' );
	}
	
	return $page_id;
}


/**
 * Get patron area page URL
 * @since 0.23.0
 * @version 0.26.0
 * @return string
 */
function patips_get_patron_area_page_url() {
	$page_id    = intval( patips_get_option( 'patips_settings_general', 'patron_area_page_id' ) );
	$page       = $page_id > 0 ? get_page( $page_id ) : null;
	$is_trashed = $page ? $page->post_status === 'trash' : false;
	$url        = $page && ! $is_trashed ? get_permalink( $page ) : '';
	
	return apply_filters( 'patips_patron_area_page_url', $url ? $url : '' );
}




// WIDGETS

/**
 * Get the default widget ids to be inserted in the Sales Sidebar
 * @since 0.17.0
 * @return array
 */
function patips_get_default_sales_sidebar_widget_ids() {
	return apply_filters( 'patips_default_sales_sidebar_widget_ids', array(
		'patips_widget_patron_number',
		'patips_widget_period_income',
		'patips_widget_tier_form',
		'patips_widget_period_media'
	) );
}


/**
 * Add default widgets to the Sales sidebar
 * @since 0.17.0
 * @return array
 */
function patips_add_default_widgets_to_sales_sidebar() {
	$active_widgets = get_option( 'sidebars_widgets' );
	
	// If the sidebar has already been customized by the user, do nothing
	if( ! empty( $active_widgets[ 'patips-sales-sidebar' ] ) ) {
		return;
	}
	
	// Initialize array
	if( ! isset( $active_widgets[ 'patips-sales-sidebar' ] ) ) {
		$active_widgets[ 'patips-sales-sidebar' ] = array();
	}
	
	// Widgets to add
	$widgets_ids = patips_get_default_sales_sidebar_widget_ids();
	
	// Add the widgets
	$counter_per_widget = array();
	foreach( $widgets_ids as $i => $widgets_id ) {
		$counter = isset( $counter_per_widget[ $widgets_id ] ) ? ( $counter_per_widget[ $widgets_id ] + 1 ) : 1;
		$active_widgets[ 'patips-sales-sidebar' ][] = $widgets_id . '-' . $counter;
		
		$widget = get_option( 'widget_' . $widgets_id, array() );
		$widget[ $counter ] = array();
		update_option( 'widget_' . $widgets_id, $widget );
		
		$counter_per_widget[ $widgets_id ] = $counter;
	}
	
	// Save sidebar
	update_option( 'sidebars_widgets', $active_widgets );
}