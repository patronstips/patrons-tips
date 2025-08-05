<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Get post filters
 * @since 0.11.0
 * @verison 0.19.0
 * @param array $filters
 * @return array
 */
function patips_get_default_patron_post_filters() {
	return apply_filters( 'patips_default_patron_post_filters', array(
		'types'       => array( 'post' ), // 'post', 'page', 'attachment', 'product'
		'statuses'    => array( 'publish' ), 
		'categories'  => array(),
		'tags'        => array(),
		'cat_and_tag' => true,
		'from'        => patips_get_option( 'patips_settings_general', 'patronage_launch_date' ),
		'to'          => '',
		'patron_id'   => 0,
		'user_id'     => get_current_user_id(),
		'user_email'  => '',
		'restricted'  => false, // false = all, 1 = restricted posts only, 0 = unrestricted posts only
		'unlocked'    => false, // false = all, 1 = unlocked posts only, 0 = locked posts only
		'order_by'    => array( 'post_date' ), 
		'order'       => 'desc',
		'offset'      => 0,
		'per_page'    => 0
	));
}


/**
 * Format post filters
 * @since 0.12.0
 * @version 0.25.7
 * @param array $filters
 * @return array
 */
function patips_format_patron_post_filters( $filters = array() ) {
	$default_filters = patips_get_default_patron_post_filters();
	
	$formatted_filters = array();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) {
			$formatted_filters[ $filter ] = $default_value;
			continue;
		}

		$current_value = $filters[ $filter ];
		
		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'types', 'statuses' ), true ) ) {
			if( is_string( $current_value ) )  { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_str_ids_to_array( $current_value );
			
		} else if( in_array( $filter, array( 'categories', 'tags' ), true ) ) {
			if( is_string( $current_value ) )  { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value_ids  = array_values( array_filter( $current_value, 'is_numeric' ) );
			$current_value_strs = array_values( array_diff( $current_value, $current_value_ids ) );
			$current_value      = array_merge( patips_ids_to_array( $current_value_ids ), patips_str_ids_to_array( $current_value_strs ) );
			
		} else if( in_array( $filter, array( 'from' ), true ) ) {
			if( ! is_string( $current_value ) )          { $current_value = $default_value; }
			if( patips_sanitize_date( $current_value ) ) { $current_value .= ' 00:00:00'; }
			$current_value = patips_sanitize_datetime( $current_value );
		
		} else if( in_array( $filter, array( 'to' ), true ) ) {
			if( ! is_string( $current_value ) )          { $current_value = $default_value; }
			if( patips_sanitize_date( $current_value ) ) { $current_value .= ' 23:59:59'; }
			$current_value = patips_sanitize_datetime( $current_value );
		
		} else if( in_array( $filter, array( 'user_id', 'patron_id' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
		
		} else if( in_array( $filter, array( 'user_email' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = sanitize_email( $current_value );
		
		} else if( in_array( $filter, array( 'restricted', 'unlocked' ), true ) ) {
			     if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) )               { $current_value = 0; }
			else if( in_array( $current_value, array( false, 'false' ), true ) )       { $current_value = false; }
			if( ! in_array( $current_value, array( 0, 1, false ), true ) )             { $current_value = $default_value; }
		
		} else if( in_array( $filter, array( 'cat_and_tag' ), true ) ) {
				 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )   { $current_value = 1; }
			else if( in_array( $current_value, array( false, 'false', 0, '0' ), true ) ) { $current_value = 0; }
			if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 'ID', 'post_author', 'post_date', 'post_title', 'post_status', 'post_name', 'post_modified', 'post_parent', 'menu_order', 'post_type', 'post_mime_type', 'comment_count' );
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
	
	return apply_filters( 'patips_formatted_patron_post_filters', $formatted_filters, $filters );
}


/**
 * Format post filters manually input
 * @since 0.11.0
 * @version 0.17.0
 * @param array $filters
 * @return array
 */
function patips_format_string_patron_post_filters( $filters = array() ) {
	// Format arrays
	$formatted_filters = array();
	$str_keys = array( 'types', 'statuses', 'categories', 'tags', 'order_by' );

	foreach( $str_keys as $key ) {
		$formatted_filters[ $key ] = array();
		if( empty( $filters[ $key ] ) )       { continue; }
		if( is_array( $filters[ $key ] ) )    { $formatted_filters[ $key ] = $filters[ $key ]; continue; }
		if( ! is_string( $filters[ $key ] ) ) { continue; }

		$formatted_value = preg_replace( array(
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', $filters[ $key ] );

		$formatted_filters[ $key ] = explode( ',', $formatted_value );
		$formatted_filters[ $key ] = patips_str_ids_to_array( $formatted_filters[ $key ] ); 
	}
	
	// Convert relative date format to date
	$date_keys = array( 'from', 'to' );
	$timezone  = patips_get_wp_timezone();
	
	foreach( $date_keys as $key ) {
		$date_sanitized = ! empty( $filters[ $key ] ) ? sanitize_text_field( $filters[ $key ] ) : '';
		if( $date_sanitized && ! patips_sanitize_date( $date_sanitized ) && (bool) strtotime( $date_sanitized ) ) {
			$dt = new DateTime( $date_sanitized, $timezone );
			$formatted_filters[ $key ] = $dt->format( 'Y-m-d' );
		}
	}
	
	return apply_filters( 'patips_formatted_string_patron_post_filters', array_merge( $filters, $formatted_filters ), $filters );
}


/**
 * Check if the post is restricted
 * @since 0.1.0
 * @version 0.26.2
 * @param WP_Post|int $post
 * @param int|string|array $user_id User ID or User email or Patron data
 * @return array|false array of restricted terms = locked, empty array = unlocked, false = not restricted
 */
function patips_is_post_restricted( $post, $user_id = 0 ) {
	if( is_numeric( $post ) )        { $post = get_post( $post ); }
	if( ! is_a( $post, 'WP_Post' ) ) { return false; }
	if( ! $user_id )                 { $user_id = get_current_user_id(); }
	
	$user_uid = is_array( $user_id ) ? ( isset( $user_id[ 'id' ] ) ? 'patron_' . $user_id[ 'id' ] : 0 ) : ( $user_id ? $user_id : 0 );
	
	// Get the cached value
	$cache = wp_cache_get( 'is_post_restricted_' . $post->ID . '_' . $user_uid, 'patrons-tips' );
	if( $cache !== false ) { return ! is_array( $cache ) ? false : $cache; }
	
	// If the post has a restricted term and the user is not logged in
	$post_restricted_terms = patips_get_post_restricted_terms( $post );
	if( ! $post_restricted_terms || ( $post_restricted_terms && ! $user_uid ) ) {
		$is_restricted = $post_restricted_terms ? $post_restricted_terms : 0;
		wp_cache_set( 'is_post_restricted_' . $post->ID . '_' . $user_uid, $is_restricted, 'patrons-tips' );
		return $is_restricted;
	}
	
	// Get tiers having terms restricted to "Active" patrons
	$active_term_tier_ids = array();
	foreach( $post_restricted_terms as $term_id => $restricted_term ) {
		foreach( $restricted_term[ 'tier_scopes' ] as $tier_id => $scopes ) {
			if( in_array( 'active', $scopes, true ) ) {
				if( ! isset( $active_term_tier_ids[ $term_id ] ) ) { $active_term_tier_ids[ $term_id ] = array(); }
				$active_term_tier_ids[ $term_id ] = patips_ids_to_array( array_merge( $active_term_tier_ids[ $term_id ], array( $tier_id ) ) );
			}
		}
	}
	
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	// Get the user's patron data
	$patron  = is_array( $user_id ) ? $user_id : patips_get_user_patron_data( $user_id );
	$history = array(); 
	if( $patron ) {
		foreach( $patron[ 'history' ] as $history_entry ) {
			// Check current patronage only
			$start_dt   = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_start' ] . ' 00:00:00', $timezone );
			$end_dt     = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_end' ] . ' 23:59:59', $timezone );
			$is_current = $start_dt <= $now_dt && $end_dt >= $now_dt && $history_entry[ 'active' ];
			if( ! $is_current ) { continue; }

			$history[] = $history_entry;
		}
	}
	
	// Unlock the terms that restrict the post to active patrons
	$is_restricted = $post_restricted_terms;
	if( $active_term_tier_ids && $patron ) {
		// If at least one tier allows the term, the post is unlocked
		foreach( $active_term_tier_ids as $term_id => $tier_ids ) {
			foreach( $history as $history_entry ) {
				if( in_array( $history_entry[ 'tier_id' ], $tier_ids, true ) ) {
					$is_restricted = array();
					break;
				}
			}
		}
	}
	
	$is_restricted = apply_filters( 'patips_is_post_restricted', $is_restricted, $patron, $post, $user_id );
	
	// Update cache
	wp_cache_set( 'is_post_restricted_' . $post->ID . '_' . $user_uid, $is_restricted, 'patrons-tips' );
	
	return $is_restricted;
}


/**
 * Get post taxonomies slugs
 * @since 0.11.0
 * @version 0.22.0
 * @param WP_Post $post
 * @return array
 */
function patips_get_post_taxonomies( $post ) {
	$tag_taxonomies = array( 'post_tag' );
	$cat_taxonomies = array( 'category' );
	
	if( $post->post_type === 'attachment' ) { $cat_taxonomies = array_keys( patips_get_attachment_public_taxonomies() ); }
	
	$taxonomies = array_merge( $cat_taxonomies, $tag_taxonomies );
	
	return apply_filters( 'patips_post_taxonomies', $taxonomies, $post );
}


/**
 * Get post terms (both categories and tags) in 
 * @since 0.10.0
 * @version 0.21.0
 * @param WP_Post $post
 * @return WP_Term[]
 */
function patips_get_post_terms( $post ) {
	// Get the cached value
	$cache = wp_cache_get( 'post_terms_' . $post->ID, 'patrons-tips' );
	if( $cache !== false ) { return $cache; }
	
	$taxonomies = patips_get_post_taxonomies( $post );
	$post_terms = array();
	foreach( $taxonomies as $taxonomy ) {
		$terms = get_the_terms( $post, $taxonomy );
		if( $terms ) {
			foreach( $terms as $term ) {
				$term_id = intval( $term->term_id );
				$post_terms[ $term_id ] = $term;
			}
		}
	}
	
	$post_terms = apply_filters( 'patips_post_terms', $post_terms, $post );
	
	// Update cache
	wp_cache_set( 'post_terms_' . $post->ID, $post_terms, 'patrons-tips' );
	
	return $post_terms;
}


/**
 * Get the restricted terms (categories and tags) for a specific post
 * @since 0.10.0
 * @version 0.26.2
 * @param WP_Post $post
 * @return array
 */
function patips_get_post_restricted_terms( $post ) {
	// Get the cached value
	$cache = wp_cache_get( 'post_restricted_terms_' . $post->ID, 'patrons-tips' );
	if( $cache !== false ) { return $cache; }
	
	// Get the retricted terms and keep the ones assigned to the post only
	$post_terms            = patips_get_post_terms( $post );
	$restricted_terms      = patips_get_restricted_terms();
	$allowed_scopes        = patips_get_tier_restricted_term_scopes();
	$post_restricted_terms = array();
	foreach( $post_terms as $term_id => $post_term ) {
		if( isset( $restricted_terms[ $term_id ] ) ) {
			foreach( $restricted_terms[ $term_id ][ 'tier_scopes' ] as $tier_id => $scopes ) {
				if( array_intersect( $allowed_scopes, $scopes ) ) {
					$post_restricted_terms[ $term_id ] = $restricted_terms[ $term_id ];
				}
			}
		}
	}
	
	$post_restricted_terms = apply_filters( 'patips_post_restricted_terms', $post_restricted_terms, $post );
	
	// Update cache
	wp_cache_set( 'post_restricted_terms_' . $post->ID, $post_restricted_terms, 'patrons-tips' );
	
	return $post_restricted_terms;
}


/**
 * Get the tiers that can unlock a specific post by subscribing to it now
 * @since 0.10.0
 * @version 0.26.2
 * @param WP_Post $post
 * @return array
 */
function patips_get_post_unlock_tiers( $post ) {
	$tiers                 = patips_get_tiers_data();
	$post_restricted_terms = patips_get_post_restricted_terms( $post );
	$unlock_tiers          = array();
	
	foreach( $post_restricted_terms as $term_id => $restricted_term ) {
		foreach( $restricted_term[ 'tier_scopes' ] as $tier_id => $scopes ) {
			if( in_array( 'active', $scopes, true ) && isset( $tiers[ $tier_id ] ) ) {
				$unlock_tiers[ $tier_id ] = $tiers[ $tier_id ];
			}
		}
	}
	
	return apply_filters( 'patips_post_unlock_tiers', $unlock_tiers, $post );
}


/**
 * Display message for restricted post content
 * @since 0.1.0
 * @version 1.0.4
 * @param WP_Post|int $post
 * @param int $user_id
 * @return string
 */
function patips_get_restricted_post_content( $post, $user_id = 0 ) {
	$post    = get_post( $post );
	$actions = array();
	
	// Get tiers that can unlock this post
	$unlock_tiers = $post ? patips_get_post_unlock_tiers( $post ) : array();
	
	// Add "become_patron" action
	$sales_page_id  = patips_get_option( 'patips_settings_general', 'sales_page_id' );
	$sales_page_url = $sales_page_id ? get_permalink( $sales_page_id ) : '';
	if( $sales_page_url ) {
		$actions[ 'become_patron' ] = '<a href="' . esc_url( $sales_page_url  ). '">' . esc_html__( 'Become patron', 'patrons-tips' ) . '</a>';
	}
	
	// Add "login" action
	if( ! is_user_logged_in() ) {
		$actions[ 'login' ] = sprintf( 
			/* translators: %s = "Log in" link */
			esc_html__( 'Already a patron? %s.', 'patrons-tips' ),
			patips_get_login_link()
		);
	}
	
	// Get restricted terms description
	$post_restricted_terms = patips_get_post_restricted_terms( $post );
	$term_descriptions = array();
	foreach( $post_restricted_terms as $term_id => $restricted_term ) {
		$term_description = $restricted_term[ 'term' ] ? term_description( $restricted_term[ 'term' ] ) : '';
		if( $term_description ) {
			$term_descriptions[ $term_id ] = $term_description;
		}
	}
	
	// Restricted post default message
	$message = esc_html__( 'This content is restricted to patrons.', 'patrons-tips' );
	
	// If the post cannot be unlocked anymore
	if( ! $unlock_tiers ) {
		// Change the message
		$message = sprintf(
			/* translators: %s = "product" or "content". */
			esc_html__( 'This %s is restricted to patrons at the time it was posted, it is no longer available for newcomers.', 'patrons-tips' ),
			esc_html__( 'content', 'patrons-tips' )
		);
		
		if( isset( $actions[ 'become_patron' ] ) ) {
			$message .= ' ' . esc_html__( 'Become a patron to never miss exclusive content anymore!', 'patrons-tips' ); 
		}
	}
	
	else {
		// Get the min tier price to unlock this post
		$min_price = false;
		$unlock_tier_titles = array();
		foreach( $unlock_tiers as $unlock_tier ) {
			if( ! empty( $unlock_tier[ 'price' ] ) && ( $min_price === false || floatval( $unlock_tier[ 'price' ] ) < floatval( $min_price ) ) ) {
				$min_price = floatval( $unlock_tier[ 'price' ] );
			}
			$unlock_tier_titles[] = $unlock_tier[ 'title' ];
		}
		
		if( is_numeric( $min_price ) ) {
			$message = sprintf( 
				/* translators: %1$s = "product" or "content". %2$s = Price (e.g. 5€) */
				esc_html__( 'This %1$s is available for patrons from %2$s.', 'patrons-tips' ),
				esc_html__( 'content', 'patrons-tips' ),
				patips_format_price( $min_price )
			);
		} else if( $unlock_tier_titles ) {
			$message = sprintf( 
				/* translators: %1$s = "product" or "content". %2$s = Slash separated list of tier titles (e.g.: "Tier 1 / Tier 2"). */
				esc_html__( 'This %1$s is restricted to "%2$s" patrons.', 'patrons-tips' ),
				esc_html__( 'content', 'patrons-tips' ),
				implode( ' / ', $unlock_tier_titles )
			);
		}
	}
	
	$args = apply_filters( 'patips_restricted_post_content_args', array(
		'img_url'           => patips_get_option( 'patips_settings_general', 'restricted_post_image_url' ),
		'message'           => $message,
		'term_descriptions' => $term_descriptions,
		'actions'           => $actions,
	), $post, $unlock_tiers, $user_id );
	
	ob_start();
	?>
		<div class='patips-restricted-post-content'>
			<?php if( $args[ 'img_url' ] ) { ?>
			<div class='patips-restricted-post-image'>
				<img src='<?php echo esc_url( $args[ 'img_url' ] ); ?>'/>
			</div>
			<?php } ?>
			<div class='patips-restricted-post-text'>
				<?php if( $args[ 'message' ] ) { ?>
					<p class='patips-restricted-post-message'><?php echo wp_kses_post( $args[ 'message' ] ); ?></p>
				<?php }
				foreach( $args[ 'term_descriptions' ] as $term_id => $term_description ) { ?>
					<p class='patips-restricted-post-term-description' data-term-id='<?php echo esc_attr( $term_id ); ?>'><?php echo wp_kses_post( trim( preg_replace( '%<p(.*?)>|</p>%s', '', $term_description ) ) ); ?></p>
				<?php } ?>
			</div>
			<div class='patips-restricted-post-actions'>
			<?php if( $args[ 'actions' ] ) { ?>
				<ul>
				<?php foreach( $args[ 'actions' ] as $action_name => $action ) { ?>
					<li data-action='<?php echo esc_attr( $action_name ); ?>'><?php echo wp_kses_post( $action ); ?>
				<?php } ?>
				</ul>
			<?php } ?>
			</div>
		</div>
	<?php
	
	return apply_filters( 'patips_restricted_post_content', ob_get_clean(), $post, $args, $unlock_tiers, $user_id );
}


/**
 * Display a list of posts with restricted content for the desired patron
 * @since 0.11.0
 * @version 1.0.1
 * @param array $filters See patips_format_patron_post_filters
 * @param boolean $return_array
 * @return string
 */
function patips_get_patron_post_list( $filters, $return_array = false ) {
	$post_items = patips_get_patron_post_list_items( array_merge( $filters, array( 'per_page' => $filters[ 'per_page' ] + 1 ) ) );
	
	// Check if there are more posts to display and remove the exedent post
	$post_nb  = count( $post_items );
	$has_more = $post_nb > $filters[ 'per_page' ];
	if( $has_more ) { 
		$post_items = array_slice( $post_items, 0, $filters[ 'per_page' ] );
	}
	
	$post_list = '';
	
	if( $post_items ) {
		ob_start();
		?>
		<div class='patips-ajax-list patips-patron-post-list'>
			<div class='patips-ajax-list-items patips-patron-post-list-items'>
			<?php
				// Display post item and increase offset
				$offset = $filters[ 'offset' ] + $filters[ 'per_page' ];
				foreach( $post_items as $post_item ) { 
					echo $post_item[ 'html' ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$offset = $post_item[ 'offset' ] + 1;
				}
			?>
			</div>
			<?php if( $has_more ) {
				$more_filters = $filters;
				$more_filters[ 'offset' ] = $offset;
			?>
				<div class='patips-ajax-list-more patips-patron-post-list-more'>
					<a href='#'
					   class='patips-ajax-list-more-link patips-patron-post-list-more-link'
					   data-type='patron_post'
					   data-nonce='<?php echo esc_attr( wp_create_nonce( 'patips_nonce_get_list_items' ) ); ?>'
					   data-args='<?php echo wp_json_encode( array( 'filters' => $more_filters ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>'>
						   <?php esc_html_e( 'See more', 'patrons-tips' ) ?>
					</a>
				</div>
			<?php } ?>
		</div>
		<?php
		$post_list = ob_get_clean();
	}
	
	$html = apply_filters( 'patips_patron_post_list', $post_list, $post_items, $filters );
	
	return $return_array ? $post_items : $html;
}


/**
 * Get patron post items
 * @since 0.11.0 (was patips_get_patronage_posts_array)
 * @version 1.0.4
 * @param array $filters See patips_format_patron_post_filters
 * @return string
 */
function patips_get_patron_post_list_items( $filters ) {
	// Get patron
	$patron = $filters[ 'patron_id' ] ? patips_get_patron_data( $filters[ 'patron_id' ] ) : array();
	if( ! $patron ) {
		$user_uid = $filters[ 'user_id' ] ? $filters[ 'user_id' ] : $filters[ 'user_email' ];
		$patron   = $user_uid ? patips_get_user_patron_data( $user_uid ) : array();
	}
	$patron_id = $patron ? $patron[ 'id' ] : ( $filters[ 'patron_id' ] ? $filters[ 'patron_id' ] : 0 );
	
	// Get posts
	$posts = patips_get_patron_posts( $filters, $patron_id );
	
	// Get posts offset
	$offset       = $filters[ 'offset' ];
	$posts_offset = array();
	foreach( $posts as $post ) {
		$posts_offset[ $post->ID ] = $offset;
		++$offset;
	}
	
	// Get list item
	$list_items = array();
	foreach( $posts as $i => $post ) {
		// Get offset
		$offset = ! empty( $posts_offset[ $post->ID ] ) ? intval( $posts_offset[ $post->ID ] ) : $i;
		
		// Get post thumbnail
		$img_srcs   = $post->post_type === 'attachment' ? wp_get_attachment_image_src( $post->ID, 'full' ) : array();
		$link       = $img_srcs ? $img_srcs[ 0 ] : get_post_permalink( $post );
		$thumb_id   = $post->post_type === 'attachment' ? get_post_meta( $post->ID, 'thumbnail', true ) : 0;
		$preview_id = $post->post_type === 'attachment' ? get_post_meta( $post->ID, 'preview', true ) : 0;
		if( ! empty( $filters[ 'image_only' ] ) && $preview_id ) { $thumb_id = $preview_id; }
		$image_size = ! empty( $filters[ 'image_size' ] ) ? $filters[ 'image_size' ] : ( ! empty( $filters[ 'image_only' ] ) ? 'full' : 'medium' );
		$image      = $post->post_type === 'attachment' ? wp_get_attachment_image( $thumb_id ? $thumb_id : $post->ID, $image_size ) : get_the_post_thumbnail( $post, $image_size );
		
		// Check if the post is restricted
		$classes = array();
		if( ! empty( $post->is_restricted ) ) { 
			$classes[] = 'patips-restricted-post'; 
			if( ! empty( $post->is_unlocked ) ) { 
				$classes[] = 'patips-unlocked-post';
			} else {
				$classes[] = 'patips-locked-post'; 
				if( ! empty( $filters[ 'gray_out' ] ) ) {
					$classes[] = 'patips-gray-out';
				}
				
				if( $post->post_type === 'attachment' ) {
					$link = '#';
					if( $preview_id ) {
						$img_srcs = wp_get_attachment_image_src( $preview_id, 'full' );
						if( $img_srcs ) {
							$link = $img_srcs[ 0 ];
						}
					}
				}
			}
		}
		if( ! empty( $filters[ 'image_only' ] ) ) { 
			$classes[] = 'patips-image-only'; 
			if( ! $image ) {
				$image     = '<span class="patips-no-images-text">' . esc_html__( 'No images.', 'patrons-tips' ) . '</span>';
				$classes[] = 'patips-no-images'; 
			}
		}
		if( ! empty( $filters[ 'no_link' ] ) ) { 
			$link = '#';
		}

		// Get post terms and order them by category or tag
		$terms = patips_get_post_terms( $post );
		$categories = $tags = array();
		foreach( $terms as $term ) {
			if( substr( $term->taxonomy, -4 ) !== '_tag' ) {
				$categories[ $term->slug ] = $term;
			} else {
				$tags[ $term->slug ] = $term;
			}
			$classes[] = $term->taxonomy . '-' . $term->slug;
		}
		$terms_ordered = array_merge( $categories, $tags );

		// Default post classes
		$classes = array_merge( array(
			'post-' . $post->ID,
			'type-' . $post->post_type,
			'status-' . $post->post_status
		), $classes );
		$classes = apply_filters( 'patips_patron_post_list_item_classes', $classes, $post, $filters, $offset );
		
		ob_start();
		?>
			<a href='<?php echo esc_url( $link ); ?>' class='patips-patron-post-list-item <?php echo esc_attr( implode( ' ', $classes ) ); ?>'>
				<?php do_action( 'patips_patron_post_list_item_before', $post, $filters, $offset ); ?>
				
				<div class='patips-patron-post-list-item-image'><?php echo wp_kses_post( $image ); ?></div>
				
				<div class='patips-patron-post-list-item-text'>
					<div class='patips-patron-post-list-item-terms'>
					<?php
						foreach( $terms_ordered as $term ) {
						?>
							<span class='patips-patron-post-list-item-<?php echo esc_attr( $term->taxonomy ); ?>' data-term='<?php echo esc_attr( $term->slug ); ?>'><?php echo esc_html( $term->name ); ?></span>
						<?php 
						}
					?>
					</div>
					<h4 class='patips-patron-post-list-item-title'><?php echo esc_html( $post->post_title ); ?></h4>
					<span class='patips-patron-post-list-item-date'><?php echo esc_html( get_the_date( '', $post ) ); ?></span>
					
					<?php do_action( 'patips_patron_post_list_item_text_after', $post, $filters, $offset ); ?>
				</div>
				
				<?php do_action( 'patips_patron_post_list_item_after', $post, $filters, $offset ); ?>
			</a>
		<?php

		$list_items[] = array(
			'html'   => ob_get_clean(),
			'post'   => $post,
			'offset' => $offset
		);
	}
	
	return apply_filters( 'patips_patron_post_list_items', $list_items, $posts, $filters );
}