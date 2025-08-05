<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add a taxonomy for media
 * @since 0.1.0
 * @version 1.0.4
 */
function patips_add_taxonomies() {
	// If "attachment" already have taxonomies, no need to add one
	if( patips_get_attachment_public_taxonomies() ) { return ; }
	
    $args = array(
        'hierarchical'          => true,
        'rewrite'               => true,
        'show_admin_column'     => true,
		'show_in_rest'          => true,
		'update_count_callback' => '_update_generic_term_count'
	);
	
    register_taxonomy( 'patips_attachment_cat', 'attachment', $args );
}
add_action( 'init' , 'patips_add_taxonomies' );


/**
 * Add CSS classes to restricted post
 * @since 0.1.0
 * @version 1.0.4
 * @param array $classes
 * @param array $class
 * @param int $post_id
 * @return array
 */
function patips_add_css_classes_to_restricted_post( $classes, $class, $post_id ) {
	$is_restricted = patips_is_post_restricted( $post_id );
	
	if( is_array( $is_restricted ) ) {
		$classes[] = 'patips-restricted-post';
		if( $is_restricted ) {
			$classes[] = 'patips-locked-post';
			
			$gray_out = patips_get_option( 'patips_settings_general', 'gray_out_locked_posts' );
			if( $gray_out ) {
				$classes[] = 'patips-gray-out-locked-post';
			}
			
		} else {
			$classes[] = 'patips-unlocked-post';
		}
	} 
	
	return $classes;
}
add_filter( 'post_class', 'patips_add_css_classes_to_restricted_post', 10, 3 );


/**
 * Replace restricted post content
 * @since 0.1.0
 * @version 0.26.0
 * @global WP_Post $post
 * @param string $content
 * @return string
 */
function patips_restrict_post_content( $content ) {
	global $post;
	$is_restricted = $post ? apply_filters( 'patips_restrict_post_content', patips_is_post_restricted( $post ), $post ) : false;
	return $is_restricted ? patips_get_restricted_post_content( $post ) : $content;
}
add_filter( 'the_content', 'patips_restrict_post_content', 10, 1 );


/**
 * Display the patrons lists at the end of the posts
 * @since 0.11.0 (was patips_display_patrons_thank_you_message_in_post)
 * @version 1.0.1
 * @global WP_Post $post
 * @param string $content
 * @return string
 */
function patips_post_display_patron_list( $content ) {
	global $post;
	
	// Check if the post has the required terms
	$term_ids = patips_get_option( 'patips_settings_general', 'display_patron_list_term_ids' );
	if( ! is_array( $term_ids ) ) { $term_ids = $term_ids ? array( $term_ids ) : array(); }
	
	if( ! in_array( 'all', $term_ids, true ) ) {
		if( in_array( 'restricted', $term_ids, true ) ) {
			$term_ids = array_keys( patips_get_restricted_terms() ) + $term_ids;
		}

		$term_ids      = patips_ids_to_array( $term_ids );
		$post_term_ids = patips_ids_to_array( array_keys( patips_get_post_terms( $post ) ) );
		
		if( ! array_intersect( $post_term_ids, $term_ids ) ) { return $content; }
	}
	
	// Get patrons to display
	$filters = apply_filters( 'patips_post_patron_list_thanks_filters', patips_format_patron_filters( array( 'current' => 1 ) ), $post );
	$patrons = patips_get_patrons_data( $filters );
	
	// Get period name
	$period_name = apply_filters( 'patips_post_patron_list_thanks_period_name', '', $filters );
	
	// Get HTML
	$html = patips_get_patron_list_thanks( $patrons, $period_name );
	
	$html = apply_filters( 'patips_post_patron_list_thanks', $html, $patrons, $filters, $post );
	
	return $content . $html;
}
add_filter( 'the_content', 'patips_post_display_patron_list', 20, 1 );