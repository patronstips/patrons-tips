<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GENERAL

/**
 * Add js variables with WC data
 * @since 0.21.0
 * @version 0.26.0
 * @param array $vars
 * @return array
 */
function patips_wc_js_variables( $vars ) {
	// Used by Blocks
	$vars[ 'is_woocommerce' ]       = 1;
	$vars[ 'subscription_plugin' ]  = patips_get_subscription_plugin();
	$vars[ 'tiers_products_price' ] = patips_wc_get_tiers_products_price();
	$vars[ 'include_tax' ]          = get_option( 'woocommerce_tax_display_shop', 'excl' ) === 'incl';
	$vars[ 'no_tier_products' ]     = esc_html__( 'There are no products associated with this tier.', 'patrons-tips' );
	$vars[ 'no_tiers_products' ]    = esc_html__( 'There are no tiers, or no products associated with the tiers.', 'patrons-tips' );
	$vars[ 'no_tiers_products_for_frequency' ] = esc_html__( 'There are no products associated with the tiers for this frequency.', 'patrons-tips' );
	
	return $vars;
}
add_filter( 'patips_js_variables', 'patips_wc_js_variables' );


/**
 * Replace price args with WC config
 * @since 0.21.0
 * @param array $args
 * @return array
 */
function patips_wc_price_args( $args ) {
	$args[ 'currency_symbol' ]    = get_woocommerce_currency_symbol();
	$args[ 'decimal_separator' ]  = wc_get_price_decimal_separator();
	$args[ 'thousand_separator' ] = wc_get_price_thousand_separator();
	$args[ 'decimals' ]           = wc_get_price_decimals();
	$args[ 'price_format' ]       = get_woocommerce_price_format();
	
	return $args;
}
add_filter( 'patips_price_args', 'patips_wc_price_args' );


/**
 * Format price with WC settings
 * @since 0.21.0
 * @version 0.25.0
 * @param string $formatted_price
 * @param int|float $amount
 * @param int|float $price_raw
 * @param array $args_raw
 * @return string
 */
function patips_wc_formatted_price( $formatted_price, $amount, $price_raw, $args_raw ) {
	if( ! empty( $args_raw[ 'currency_symbol' ] ) ) { return $formatted_price; }
	
	$wc_price = html_entity_decode( wc_price( $price_raw, $args_raw ) );
	
	if( empty( $args_raw[ 'plain_text' ] ) ) { 
		$wc_price = '<span class="patips-price">' . $wc_price . '</span>';
	} else {
		$wc_price = trim( wp_strip_all_tags( $wc_price ) );
	}
	
	return $wc_price;
}
add_filter( 'patips_formatted_price', 'patips_wc_formatted_price', 10, 4 );


/**
 * Get WC taxonomies that can be restricted by post type
 * @since 0.22.0
 * @param array $taxonomies
 * @return array
 */
function patips_wc_restrictable_taxonomies_by_post_type( $taxonomies ) {
	$taxonomies[ 'product' ] = array( 'product_cat', 'product_tag' );
	return $taxonomies;
}
add_filter( 'patips_restrictable_taxonomies_by_post_type', 'patips_wc_restrictable_taxonomies_by_post_type', 10, 1 );


/**
 * Ad WC post taxonomies slugs
 * @since 0.22.0
 * @param array $taxonomies
 * @param WP_Post $post
 * @return array
 */
function patips_wc_post_taxonomies( $taxonomies, $post ) {
	if( substr( $post->post_type, 0, 7 ) === 'product' ) {
		$taxonomies = array( 'product_cat', 'product_tag' );
	}
	return $taxonomies;
}
add_filter( 'patips_post_taxonomies', 'patips_wc_post_taxonomies', 10, 2 );


/**
 * Replace default login link with WC My Account page
 * @since 0.23.2
 * @version 0.25.5
 * @param string $link
 * @param boolean $redirect
 * @param boolean $url_only
 * @return string
 */
function patips_wc_login_link( $link, $redirect, $url_only ) {
	$my_account_page_id = get_option( 'woocommerce_myaccount_page_id' );
	$login_url          = $my_account_page_id ? get_permalink( $my_account_page_id ) : '';

	if( $login_url ) {
		if( $redirect ) {
			$request_uri  = ! empty( $_SERVER[ 'REQUEST_URI' ] ) ? home_url( sanitize_url( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) ) : '';
			$redirect_url = is_string( $redirect ) ? $redirect : $request_uri;
			$login_url    = add_query_arg( array( 'redirect_to' => urlencode( $redirect_url ) ), $login_url );
		}

		$link = $url_only ? $login_url : '<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log in', 'patrons-tips' ) . '</a>';
	}
	
	return $link;
}
add_filter( 'patips_login_link', 'patips_wc_login_link', 10, 3 );


/**
 * Change restricted post content with WC data
 * @since 0.21.0
 * @version 0.26.2
 * @param array $args
 * @param WP_Post $post
 * @param array $unlock_tiers
 * @param int $user_id
 * @return array
 */
function patips_wc_restricted_post_content_args( $args, $post, $unlock_tiers, $user_id ) {
	// Add "back_to_shop" action
	$is_product = $post && substr( $post->post_type, 0, 7 ) === 'product';
	if( $is_product ) {
		$shop_page_id  = wc_get_page_id( 'shop' );
		$shop_page_url = $shop_page_id ? get_permalink( $shop_page_id ) : '';
		$args[ 'actions' ][ 'back_to_shop' ] = '<a href="' . $shop_page_url . '">' . esc_html__( 'Back to shop', 'patrons-tips' ) . '</a>';
	}
	
	// Get the min tier price to unlock this post
	$min_price = $unlock_tiers ? patips_wc_get_tiers_lowest_product_price( $unlock_tiers ) : false;
	if( $unlock_tiers ) {
		$unlock_tier_titles = array();
		if( ! is_numeric( $min_price ) ) {
			foreach( $unlock_tiers as $unlock_tier ) {
				if( ! empty( $unlock_tier[ 'price' ] ) && ( $min_price === false || floatval( $unlock_tier[ 'price' ] ) < floatval( $min_price ) ) ) {
					$min_price = floatval( $unlock_tier[ 'price' ] );
				}
				$unlock_tier_titles[] = $unlock_tier[ 'title' ];
			}
		}
		
		if( is_numeric( $min_price ) ) {
			$args[ 'message' ] = sprintf(
				/* translators: %1$s = "product" or "content". %2$s = Price (e.g. 5€) */
				esc_html__( 'This %1$s is available for patrons from %2$s.', 'patrons-tips' ),
				$is_product ? esc_html__( 'product', 'patrons-tips' ) : esc_html__( 'content', 'patrons-tips' ),
				patips_format_price( $min_price )
			);
		} else if( $unlock_tier_titles ) {
			$message = sprintf( 
				/* translators: %1$s = "product" or "content". %2$s = Slash separated list of tier titles (e.g.: "Tier 1 / Tier 2"). */
				esc_html__( 'This %1$s is restricted to "%2$s" patrons.', 'patrons-tips' ),
				$is_product ? esc_html__( 'product', 'patrons-tips' ) : esc_html__( 'content', 'patrons-tips' ),
				implode( ' / ', $unlock_tier_titles )
			);
		}
	}
	
	if( ! $unlock_tiers && $is_product ) {
		// Change the message
		$args[ 'message' ] = sprintf( 
			/* translators: %s = "product" or "content". */
			esc_html__( 'This %s is restricted to patrons at the time it was posted, it is no longer available for newcomers.', 'patrons-tips' ),
			esc_html__( 'product', 'patrons-tips' )
		);
		
		if( isset( $args[ 'actions' ][ 'become_patron' ] ) ) {
			$args[ 'message' ] .= ' ' . esc_html__( 'Become a patron to never miss exclusive content anymore!', 'patrons-tips' ); 
		}
	}
	
	return $args;
}
add_filter( 'patips_restricted_post_content_args', 'patips_wc_restricted_post_content_args', 10, 4 );




// SHORTCODES

/**
 * Default subscription form filters
 * @since 0.22.0 (was patips_wc_default_add_to_cart_tier_form_filters)
 * @version 0.25.5
 * @param array $defaults
 * @return array
 */
function patips_wc_default_subscription_form_filters( $defaults ) {
	$defaults[ 'redirect' ]     = wc_get_checkout_url();
	$defaults[ 'submit_label' ] = esc_html__( 'Add to cart', 'patrons-tips' );
	$defaults[ 'include_tax' ]  = get_option( 'woocommerce_tax_display_shop', 'excl' ) === 'incl';
	
	return $defaults;
}
add_filter( 'patips_default_subscription_form_filters', 'patips_wc_default_subscription_form_filters', 10, 1 );


/**
 * Enhance patron status list item with WC data
 * @since 0.21.0
 * @version 0.26.0
 * @param string $li
 * @param array $current_history_entry
 * @param array $patron
 * @param array $args
 * return string
 */
function patips_wc_shortcode_patron_status_list_item( $li, $current_history_entry, $patron, $args ) {
	// Get tiers
	$tiers = patips_get_tiers_data();
	
	$tier_id    = $current_history_entry[ 'tier_id' ];
	$tier       = isset( $tiers[ $tier_id ] ) ? $tiers[ $tier_id ] : array();
	/* translators: %s is the tier ID */
	$tier_title = ! empty( $tier[ 'title' ] ) ? $tier[ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );

	$subscription_id     = ! empty( $current_history_entry[ 'subscription_id' ] ) ? $current_history_entry[ 'subscription_id' ] : 0;
	$subscription_plugin = ! empty( $current_history_entry[ 'subscription_plugin' ] ) ? $current_history_entry[ 'subscription_plugin' ] : '';
	$subscription        = $subscription_id ? patips_get_subscription_data( $subscription_id, $subscription_plugin ) : array();
	$subscription_url    = $subscription_id ? patips_get_subscription_url_by_plugin( $subscription_id, $subscription_plugin ) : '';

	$price = patips_wc_get_patron_history_entry_price( $current_history_entry );
	
	if( $subscription && $subscription[ 'active' ] && ! $subscription[ 'pending_cancel' ] ) {
		/* translators: %1$s = the Tier name.  %2$s = Formatted price (e.g. $20.00). %3$s = Link to "Manage your subcription". */
		$li = sprintf( esc_html__( 'You are a recurring "%1$s" (%2$s) patron (%3$s).', 'patrons-tips' ), $tier_title, patips_format_price( $price ), '<a href="' . $subscription_url . '">' . esc_html__( 'Manage your subcription', 'patrons-tips' ) . '</a>' );
	} else {
		/* translators: %1$s = the Tier name. %2$s = Formatted price (e.g. $20.00). %3$s = formatted date (e.g. "September 30th 2024") */
		$li = sprintf( esc_html__( 'You are a "%1$s" (%2$s) patron until %3$s.', 'patrons-tips' ), $tier_title, patips_format_price( $price ), patips_format_datetime( $current_history_entry[ 'date_end' ] . ' 00:00:00' ) );
	}
	
	return apply_filters( 'patips_wc_patron_status_list_item', $li, $current_history_entry, $patron, $args, $subscription, $price );
}
add_filter( 'patips_patron_status_list_item', 'patips_wc_shortcode_patron_status_list_item', 20, 4 );


/**
 * Add WC data to patron history list item
 * @since 0.21.0
 * @version 1.0.2
 * @param array $data
 * @param array $history_entry
 * @param array $filters
 * @return array
 */
function patips_wc_shortcode_patron_history_list_item_data( $data, $history_entry, $filters ) {
	$price            = patips_wc_get_patron_history_entry_price( $history_entry, false );
	$order            = $history_entry[ 'order_id' ] ? wc_get_order( $history_entry[ 'order_id' ] ) : null;
	$order_url        = $order ? $order->get_view_order_url() : '';
	$subscription_url = $history_entry[ 'subscription_id' ] ? patips_get_subscription_url_by_plugin( $history_entry[ 'subscription_id' ], $history_entry[ 'subscription_plugin' ] ) : '';
	
	$data[ 'price' ]    = $price ? patips_format_price( $price ) : '';
	/* translators: %s = order ID */
	$data[ 'actions' ] .= $order_url ? '<a href="' . $order_url . '" class="woocommerce-button button view">' . sprintf( esc_html__( 'View order #%s', 'patrons-tips' ), $history_entry[ 'order_id' ] ) . '</a>' : '';
	/* translators: %s = subscription ID */
	$data[ 'actions' ] .= $subscription_url ? '<a href="' . $subscription_url . '" class="woocommerce-button button view">' . sprintf( esc_html__( 'View subscription #%s', 'patrons-tips' ), $history_entry[ 'subscription_id' ] ) . '</a>' : '';

	return $data;
}
add_filter( 'patips_patron_history_list_item_data', 'patips_wc_shortcode_patron_history_list_item_data', 10, 3 );


/**
 * Compute period income based on WC data
 * @since 0.21.0
 * @param int|float $total
 * @param array $patrons
 * @param array $args
 * @param array $filters
 * @param array $raw_args
 * return string
 */
function patips_wc_shortcode_period_income_total( $total, $patrons, $args, $filters, $raw_args ) {
	$args[ 'include_tax' ] = isset( $raw_args[ 'include_tax' ] ) ? intval( $raw_args[ 'include_tax' ] ) : get_option( 'woocommerce_tax_display_shop', 'excl' ) === 'incl';
	return patips_wc_get_total_income_based_on_patrons_history( $patrons, $args );
}
add_filter( 'patips_period_income_total', 'patips_wc_shortcode_period_income_total', 10, 5 );


/**
 * Display both patron number and income by default in patron results
 * @since 0.23.2
 * @param array $args
 * @param array $raw_args
 * return array
 */
function patips_wc_shortcode_period_results_args( $args, $raw_args ) {
	if( isset( $raw_args[ 'display' ] ) 
	&&  ! in_array( $raw_args[ 'display' ], array( 'both', 'patron_nb', 'income' ) , true ) ) {
		$args[ 'display' ] = 'both';
	}
	
	return $args;
}
add_filter( 'patips_shortcode_period_results_args', 'patips_wc_shortcode_period_results_args', 10, 2 );


/**
 * Disallow free subscriptions via subscription form
 * @since 0.22.0
 */
add_filter( 'patips_is_free_subscription_allowed', '__return_false' );


/**
 * Display add to cart tier form instead of subscription form
 * @since 0.22.0 (was patips_wc_shortcode_add_to_cart_tier_form)
 * @param string $form
 * @param array $filters
 * @param array $raw_args
 * return string
 */
function patips_wc_shortcode_subscription_form( $form, $filters, $raw_args ) {
	return patips_wc_get_add_to_cart_tier_form( $filters );
}
add_filter( 'patips_shortcode_subscription_form', 'patips_wc_shortcode_subscription_form', 10, 3 );


/**
 * Display product price in patron post list
 * @since 0.26.0
 * @param WP_Post $post
 * @param array $filters
 * @param int $offset
 */
function patips_wc_display_patron_post_list_item_price( $post, $filters, $offset ) {
	if( $post->post_type !== 'product' ) { return; }
	
	$product       = wc_get_product( $post );
	$incl_tax      = get_option( 'woocommerce_tax_display_shop', 'excl' ) === 'incl';
	$product_price = $product ? ( $incl_tax ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product ) ) : '';
	$price         = $product_price !== '' ? patips_format_price( $product_price ) : '';
	
	?>
		<div class='patips-patron-post-list-item-price'>
			<?php echo wp_kses_post( $price ); ?>
		</div>
	<?php
}
add_action( 'patips_patron_post_list_item_after', 'patips_wc_display_patron_post_list_item_price', 10, 3 );




// BLOCKS

/**
 * Display both patron number and period income by default if WC is active
 * @since 0.25.0
 * @param array $args
 * @param array $block_atts
 * @return array
 */
function patips_wc_block_period_results_args( $args, $block_atts ) {
	if( empty( $block_atts[ 'display' ] ) ) {
		$args[ 'display' ] = 'both';
	}
	
	return $args;
}
add_filter( 'patips_block_period_results_args', 'patips_wc_block_period_results_args', 10, 2 );




// TIERS
		
/**
 * Add default WC tier data
 * @since 0.22.0
 * @param string $context
 * @return array
 */
function patips_wc_default_tier_data( $defaults, $context ) {
	// Product ids
	$defaults[ 'product_ids' ] = array( 
		'one_off' => array(), 
		'1_month' => array()
	);
	
	return $defaults;
}
add_filter( 'patips_default_tier_data', 'patips_wc_default_tier_data', 10, 2 );


/**
 * Add WC tier data
 * @since 0.22.0
 * @param array $tier
 * @param array $tier_raw_data
 * @param int $tier_id
 * @param boolean $raw
 * @return array
 */
function patips_wc_tier_data( $tier, $tier_raw_data, $tier_id, $raw ) {
	// Get tier product ids
	$tiers_product_ids     = $tier_id ? patips_wc_get_tiers_product_ids( array( $tier_id ) ) : array();
	$tier[ 'product_ids' ] = isset( $tiers_product_ids[ $tier_id ] ) ? $tiers_product_ids[ $tier_id ] : array();
	
	return $tier;
}
add_filter( 'patips_tier_data', 'patips_wc_tier_data', 10, 4 );


/**
 * Format WC tier data
 * @since 0.22.0
 * @version 0.26.0
 * @param array $tier_data
 * @param array $raw_tier_data
 * @param string $context
 * @return array
 */
function patips_wc_formatted_tier_data( $tier_data, $raw_tier_data, $context = '' ) {
	// Product IDs
	$tier_data[ 'product_ids' ] = isset( $raw_tier_data[ 'product_ids' ] ) ? array_map( 'patips_ids_to_array', $raw_tier_data[ 'product_ids' ] ) : array();
	if( ! isset( $tier_data[ 'product_ids' ][ 'one_off' ] ) ) { $tier_data[ 'product_ids' ][ 'one_off' ] = array(); }
	if( ! isset( $tier_data[ 'product_ids' ][ '1_month' ] ) ) { $tier_data[ 'product_ids' ][ '1_month' ] = array(); }
	
	return $tier_data;
}
add_filter( 'patips_formatted_tier_data', 'patips_wc_formatted_tier_data', 10, 3 );
add_filter( 'patips_sanitized_tier_data', 'patips_wc_formatted_tier_data', 10, 2 );


/**
 * Get product price instead of tier price
 * @since 0.22.0
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param string $query
 * @param array $filters
 * @return string
 */
function patips_wc_get_tiers_query( $query, $filters ) {
	if( patips_wc_is_tier_price_manual() ) { return $query; }
	
	global $wpdb;
	
	$old_query = array( ' T.price', ' price' );
	$new_query = ' TPM.price';
	$query     = str_replace( $old_query, $new_query, $query );
	
	$JOIN_query = ' LEFT JOIN ( ' 
	           .   ' SELECT TP.tier_id, IFNULL( TP.product_id, 0 ) as product_id, IFNULL( MIN( CAST( PM.meta_value as DECIMAL( 8, %d ) ) ), 0 ) as price ' 
	           .   ' FROM ' . PATIPS_TABLE_TIERS_PRODUCTS . ' as TP '
	           .   ' LEFT JOIN ' . $wpdb->postmeta . ' as PM ON PM.post_id = TP.product_id AND PM.meta_key = "_regular_price" '
	           .   ' GROUP BY TP.tier_id '
	           . ' ) as TPM ON TPM.tier_id = T.id ';
	
	$price_args = patips_get_price_args();
	$JOIN_query = $wpdb->prepare( $JOIN_query, array( $price_args[ 'decimals' ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$WHERE_i    = strpos( $query, 'WHERE' );
	$query      = substr( $query, 0, $WHERE_i ) . $JOIN_query . substr( $query, $WHERE_i );
	
	return $query;
}
add_filter( 'patips_get_tiers_query', 'patips_wc_get_tiers_query', 10, 2 );




// PATRONS

/**
 * Try to generate patron nickname from WC order data
 * @since 0.21.0
 * @param string $nickname
 * @param WP_User $user
 * @param array|WP_User $object
 * @return string
 */
function patips_wc_pre_generate_patron_nickname( $nickname, $user, $object ) {
	if( $nickname ) { return $nickname; }
	
	$email      = is_array( $object ) && ! empty( $object[ 'user_email' ] ) && is_email( $object[ 'user_email' ] ) ? $object[ 'user_email' ] : ( $user instanceof WP_User ? $user->user_email : '' );
	$first_name = '';
	$last_name  = '';
	
	// Try to find user details from WC orders
	if( $email ) {
		$orders = wc_get_orders( array( 'customer' => $email, 'limit' => 1 ) );
		$order  = $orders ? reset( $orders ) : array();
		if( $order ) {
			$first_name = trim( $order->get_billing_first_name() ? $order->get_billing_first_name() : $order->get_shipping_first_name() );
			$last_name  = trim( $order->get_billing_last_name() ? $order->get_billing_last_name() : $order->get_shipping_last_name() );
		
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
	}
	
	return $nickname;
}
add_filter( 'patips_pre_generate_patron_nickname', 'patips_wc_pre_generate_patron_nickname', 10, 3 );




// PRODUCT PAGE AND LOOP

/**
 * Display resctricted message on product pages
 * @since 0.10.0 (was patips_restrict_products_informations)
 * @version 1.0.2
 * @global WC_Product $product
 */
function patips_wc_restrict_product_page() {
	global $product;
	$is_restricted = patips_is_post_restricted( $product->get_id() );
	if( $is_restricted ) {
		echo patips_get_restricted_post_content( $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		
		// Remove add to cart form (for non block sites)
		remove_action( 'woocommerce_single_product_summary' , 'woocommerce_template_single_add_to_cart', 30 );	
	}	
}
add_action( 'woocommerce_single_product_summary' , 'patips_wc_restrict_product_page', 25 );


/**
 * Add CSS classes to restricted products
 * @since 0.10.0 (was patips_add_css_post_classes_to_product)
 * @version 1.0.4
 * @global WC_Product $product
 * @param string $class
 * @return array
 */
function patips_wc_add_css_classes_to_restricted_product( $class ) {
	global $product;
	
	if( $product ) {
		$classes = patips_add_css_classes_to_restricted_post( array(), array(), $product->get_id() );
		if( in_array( 'patips-locked-post', $classes, true ) )   { $class .= ' patips-locked'; }
		if( in_array( 'patips-unlocked-post', $classes, true ) ) { $class .= ' patips-unlocked'; }
	}
	
	return $class;
}
add_filter( 'woocommerce_product_loop_title_classes', 'patips_wc_add_css_classes_to_restricted_product', 10, 2 );


/**
 * Add CSS classes to restricted products
 * @since 0.1.0
 * @param string $button
 * @param WC_Product $product
 * @param array $args
 * @return array
 */
function patips_wc_remove_add_to_cart_button_for_restricted_product( $button, $product, $args ) {
	$is_restricted = patips_is_post_restricted( $product->get_id() );
	return $is_restricted ? '' : $button;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'patips_wc_remove_add_to_cart_button_for_restricted_product', 100, 3 );


/**
 * Do not restrict post content for WC products
 * @since 0.1.0
 * @param boolean $true
 * @param WP_Post $post
 * @return string
 */
function patips_wc_do_not_restrict_product_content( $true, $post ) {
	return $true && substr( $post->post_type, 0, 7 ) !== 'product';
}
add_filter( 'patips_restrict_post_content', 'patips_wc_do_not_restrict_product_content', 10, 2 );


/**
 * Do not automatically display thank you lists on product pages
 * @since 0.26.0
 * @global WP_Post $post
 * @param string $content
 * @return string
 */
function patips_wc_do_not_display_thank_you_list_on_product_pages( $content ) {
	global $post;
	
	if( $post && substr( $post->post_type, 0, 7 ) === 'product' ) {
		remove_filter( 'the_content', 'patips_post_display_patron_list', 20 );
	}
	
	return $content;
}
add_filter( 'the_content', 'patips_wc_do_not_display_thank_you_list_on_product_pages', 5, 1 );




// CART AND CHECKOUT

/**
 * Allow only one tier product per order
 * @since 0.13.4
 * @param boolean $true
 * @param int $product_id
 * @param int $quantity
 * @param int $variation_id
 * @return boolean
 */
function patips_validate_add_to_cart_tier_product( $true, $product_id, $quantity, $variation_id = 0 ) {
	$new_product_id   = $variation_id ? $variation_id : $product_id;
	$new_product_tier = patips_wc_get_product_tier( $new_product_id );
	$cart_has_tier    = patips_wc_cart_has_tiers();
	
	if( $cart_has_tier && $new_product_tier ) {
		wc_add_notice( esc_html__( 'This product could not be added to the cart because there is already a patronage product in your cart.', 'patrons-tips' ), 'error' );
		return false;
	}
	
	return $true;
}
add_filter( 'woocommerce_add_to_cart_validation', 'patips_validate_add_to_cart_tier_product', 1000, 4 );


/**
 * Validate add to cart for restricted products
 * @since 0.10.0 (was patips_validate_add_to_cart_restricted_product)
 * @version 0.26.0
 * @param boolean $true
 * @param int $product_id
 * @param int $quantity
 * @param int $variation_id
 * @return boolean
 */
function patips_wc_prevent_add_to_cart_restricted_product( $true, $product_id, $quantity, $variation_id = 0 ) {
	$is_restricted = patips_is_post_restricted( $product_id );
	
	if( $is_restricted ) {
		$message = esc_html__( 'Sorry, this product is restricted to patrons, you cannot add it to your cart.', 'patrons-tips' );
		
		// Add a link to become patron
		$sales_page_id  = patips_get_option( 'patips_settings_general', 'sales_page_id' );
		$sales_page_url = $sales_page_id ? get_permalink( $sales_page_id ) : '';
		if( $sales_page_url ) {
			$message .= ' <a href="' . esc_url( $sales_page_url  ). '">' . esc_html__( 'Become patron', 'patrons-tips' ) . '</a>';
		}
		
		wc_add_notice( $message, 'error' );
		return false;
	}
	
	return $true;
}
add_filter( 'woocommerce_add_to_cart_validation', 'patips_wc_prevent_add_to_cart_restricted_product', 100, 4 );


/**
 * Display a message if a user that is already patron has added a patron product to cart
 * @since 0.1.0
 * @version 0.25.6
 */
function patips_wc_display_already_patron_notice() {
	if( ! is_cart() && ! is_checkout() ) { return; }
	if( is_wc_endpoint_url( 'order-received' ) ) { return; }
	
	// Check if the cart includes any product bound to a tier
	$has_tiers = patips_wc_cart_has_tiers( array(), true );
	if( ! $has_tiers ) { return; }
	
	$patron = patips_get_user_patron_data();
	if( ! $patron ) { return; }
	
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	// Check if the user is patron of the current period
	$is_patron   = false;
	$tier_id     = 0;
	$period_name = '';
	foreach( $patron[ 'history' ] as $history_entry ) {
		$start_dt     = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'period_start' ] . ' 00:00:00', $timezone );
		$end_dt       = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'period_end' ] . ' 23:59:59', $timezone );
		$is_period_in = $start_dt <= $now_dt && $end_dt >= $now_dt && $history_entry[ 'active' ];
		if( ! $is_period_in ) { continue; }
		
		$is_patron   = true;
		$tier_id     = $history_entry[ 'tier_id' ];
		$period_name = patips_get_patron_history_period_name( $history_entry );
		break;
	}
	if( ! $is_patron ) { return; }
	
	$tier       = $tier_id ? patips_get_tier_data( $tier_id ) : array();
	/* translators: %s is the tier ID */
	$tier_title = ! empty( $tier[ 'title' ] ) ? $tier[ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
	
	$wc_patron_area_page_url   = patips_get_patron_area_page_url();
	$wc_patron_area_page_label = esc_html__( 'patron area', 'patrons-tips' );
	$wc_patron_area_page_link  = $wc_patron_area_page_url ? '<a href="' . $wc_patron_area_page_url . '">' . $wc_patron_area_page_label . '</a>' : $wc_patron_area_page_label;

	/* translators: %1$s = Tier title. %2$s = period name (e.g. February 2024) */
	$message  = sprintf( esc_html__( 'Oh! You are already a "%1$s" patron of %2$s.', 'patrons-tips' ), $tier_title, $period_name );
	/* translators: %s = Link to "patron area" */
	$message .= ' ' . sprintf( esc_html__( 'See your current tier and rewards in your %s.', 'patrons-tips' ), $wc_patron_area_page_link );
	$message .= ' ' . esc_html__( 'Be careful, if you place this order, you will have participated twice for the same period.', 'patrons-tips' );

	wc_add_notice( $message, 'notice' );
}
add_action( 'wp', 'patips_wc_display_already_patron_notice', 10 );




// ORDERS

/**
 * Auto complete virtual orders
 * @since 0.1.0
 * @version 0.13.0
 * @param boolean $needs_processing
 * @param WC_Product $product
 * @param int $order_id
 * @return boolean
 */
function patips_wc_autocomplete_virtual_orders( $needs_processing, $product, $order_id ) {
	if( ! $needs_processing ) { return $needs_processing; }
	
	// Check if the product is bound to a tier
	$product_tier = patips_wc_get_product_tier( $product->get_id() );
	if( ! $product_tier ) { return $needs_processing; }
	
	$virtual_item = $product->is_virtual();
	
	return ! $virtual_item;
}
add_filter( 'woocommerce_order_item_needs_processing', 'patips_wc_autocomplete_virtual_orders', 100, 3 );


/**
 * Sync patron history according to order status
 * @since 0.23.0 (was patips_wc_order_sync_patron_history)
 * @version 0.23.4
 * @param int $order_id
 * @param string $status_from
 * @param string $status_to
 * @param WC_Order $order
 */
function patips_wc_controller_order_status_changed_sync_patron_history( $order_id, $status_from, $status_to, $order = null ) {
	$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
	if( ! $order ) { return; }
	
	$has_tiers = patips_wc_order_has_tiers( $order );
	if( ! $has_tiers ) { return; }
	
	// Sync patron history only if the order turned from active to inactive (and vice-versa), or unpaid to paid
	$active_status        = wc_get_is_paid_statuses();
	$is_old_status_active = in_array( $status_from, $active_status, true );
	$is_new_status_active = in_array( $status_to, $active_status, true );
	$active_has_changed   = $is_old_status_active !== $is_new_status_active;
	$request_dt           = new DateTime();
	$request_dt->setTimeZone( new DateTimeZone( 'UTC' ) );
	$request_time         = ! empty( $_SERVER[ 'REQUEST_TIME' ] ) ? intval( $_SERVER[ 'REQUEST_TIME' ]  ) : $request_dt->getTimestamp() - 3;
	$request_dt->setTimestamp( $request_time );
	$paid_dt              = $order->get_date_paid( 'edit' );
	$was_paid             = $paid_dt && $paid_dt < $request_dt;
	$is_paid              = $paid_dt ? true : false;
	$paid_has_changed     = $was_paid !== $is_paid;
	$perform_sync         = $active_has_changed || $paid_has_changed;
	
	if( ! $perform_sync ) { return; }
	
	// Get patron by order user ID or email
	$user_id    = $order->get_customer_id();
	$user_email = $order->get_billing_email();
	$patron     = patips_get_patron_by_user_id_or_email( $user_id, $user_email, true );
	$patron_id  = isset( $patron[ 'id' ] ) ? $patron[ 'id' ] : 0;
	
	// Sync patron history
	if( $patron_id ) {
		patips_wc_sync_patron_history( $patron_id );
	}
}
add_action( 'woocommerce_order_status_changed', 'patips_wc_controller_order_status_changed_sync_patron_history', 100, 4 ); 


/**
 * Display a message on the order received page if the order contains a patronage
 * @since 0.1.0
 * @version 0.25.6
 * @param int $order_id
 */
function patips_wc_order_received_patronage_message( $order_id ) {
	// Check if the order has tiers
	$has_tiers = patips_wc_order_has_tiers( $order_id );
	if( ! $has_tiers ) { return; }
	
	$wc_patron_area_page_url   = patips_get_patron_area_page_url();
	$wc_patron_area_page_label = esc_html__( 'patron area', 'patrons-tips' );
	$wc_patron_area_page_link  = $wc_patron_area_page_url ? '<a href="' . esc_url( $wc_patron_area_page_url ) . '">' . $wc_patron_area_page_label . '</a>' : $wc_patron_area_page_label;
	
	$order = wc_get_order( $order_id );
	
	$message = '';	
	if( $order->get_date_paid( 'edit' ) ) {
		/* translators: %s = link to "patron area" */
		$message = sprintf( esc_html__( 'You are now patron! See your rewards in your %s.', 'patrons-tips' ), $wc_patron_area_page_link );
	} else {
		/* translators: %s = link to "patron area" */
		$message = sprintf( esc_html__( 'You are almost a patron! This order has not been paid yet. Once the order will be completed by an administrator, you will see your rewards in your %s.', 'patrons-tips' ), $wc_patron_area_page_link );
	}
	
	echo '<p>' . $message . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_thankyou', 'patips_wc_order_received_patronage_message', 5, 1 );


/**
 * Display a message in the customer emails related to the order if it contains a patronage product
 * @since 0.1.0
 * @version 0.25.6
 * @param WC_Order $order
 * @param boolean $is_admin_email
 * @param boolean $plain_text
 * @param WC_Email $email
 */
function patips_wc_order_email_patronage_message( $order, $is_admin_email, $plain_text, $email ) {
	if( $is_admin_email || $email->id === 'patips_patronage_reminder' ) { return; }
	
	$has_tiers = patips_wc_order_has_tiers( $order->get_id() );
	if( ! $has_tiers ) { return; }
	
	$wc_patron_area_page_url   = patips_get_patron_area_page_url();
	$wc_patron_area_page_label = esc_html__( 'patron area', 'patrons-tips' );
	$wc_patron_area_page_link  = $wc_patron_area_page_url ? '<a href="' . esc_url( $wc_patron_area_page_url ) . '">' . $wc_patron_area_page_label . '</a>' : $wc_patron_area_page_label;
	
	if( $plain_text ) { 
		$wc_patron_area_page_link = $wc_patron_area_page_label . ' ' . esc_url( $wc_patron_area_page_url );
	}
	
	$message = '';	
	if( $order->get_date_paid( 'edit' ) ) {
		/* translators: %s = link to "patron area" */
		$message = sprintf( esc_html__( 'You are now patron! See your rewards in your %s.', 'patrons-tips' ), $wc_patron_area_page_link );
	} else {
		/* translators: %s = link to "patron area" */
		$message = sprintf( esc_html__( 'You are almost a patron! This order has not been paid yet. Once the order will be completed by an administrator, you will see your rewards in your %s.', 'patrons-tips' ), $wc_patron_area_page_link );
	}
	
	if( ! $plain_text ) {
	?>
		<div style='margin-bottom: 40px;'>
			<h2><?php esc_html_e( 'Patronage info', 'patrons-tips' ); ?></h2>
			<p>
			<?php 
				echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</p>
		</div>
	<?php
	} 
	else { 
		echo PHP_EOL . PHP_EOL . esc_html__( 'Patronage info', 'patrons-tips' ) . PHP_EOL . PHP_EOL;
		echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo PHP_EOL . PHP_EOL;
	}
}
add_action( 'woocommerce_email_after_order_table', 'patips_wc_order_email_patronage_message', 14, 4 );




// MY ACCOUNT

/**
 * Redirect users to the desired page
 * @since 0.1.0
 * @version 0.26.3
 * @param string $redirect
 * @param object $user
 * @return string
 */
function patips_wc_login_form_redirect( $redirect, $user = null ) {
	if( ! empty( $_GET[ 'redirect_to' ] ) ) { 
		$redirect = rawurldecode( sanitize_url( wp_unslash( $_GET[ 'redirect_to' ] ) ) );
	}
	
	return $redirect;
}
add_filter( 'woocommerce_login_redirect', 'patips_wc_login_form_redirect', 10, 2 ); 
add_filter( 'woocommerce_registration_redirect', 'patips_wc_login_form_redirect', 10, 2 ); 


/**
 * Add "patronage" WC query var
 * @since 1.0.4
 */
function patips_wc_add_wc_query_vars( $vars ) {
    $vars[ 'patronage' ] = get_option( 'woocommerce_myaccount_patronage_endpoint', 'patronage' );
	
    return $vars;
}
add_filter( 'woocommerce_get_query_vars', 'patips_wc_add_wc_query_vars', 0 );


/**
 * Change the Patronage endpoint URL
 * @since 0.1.0
 * @version 1.0.4
 * @param string $url
 * @param string $endpoint  Endpoint slug.
 * @param string $value     Query param value.
 * @param string $permalink Permalink.
 * @return string
 */
function patips_wc_set_patronage_endpoint_url( $url, $endpoint, $value, $permalink ){
	if( $endpoint === 'patronage' ) {
		$url = get_permalink( wc_get_page_id( 'myaccount' ) ) . get_option( 'woocommerce_myaccount_patronage_endpoint', 'patronage' );
	}
	
	return $url;
}
add_filter( 'woocommerce_get_endpoint_url', 'patips_wc_set_patronage_endpoint_url', 10, 4 );


/**
 * Set the Patron area page title in WC account
 * @since 0.20.0 (was patips_wc_account_patronage_page_title)
 * @version 1.0.4
 * @param string $title Default title.
 * @param string $endpoint Endpoint key.
 * @param string $action Optional action or variation within the endpoint.
 * @return string
 */
function patips_wc_account_patron_area_page_title( $title, $endpoint = '', $action = '' ) {
	// Get custom page title
	$page_id    = patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
	$page       = $page_id > 0 ? get_page( $page_id ) : null;
	$is_trashed = $page ? $page->post_status === 'trash' : false;
	$page_title = $page && ! $is_trashed && ! empty( $page->post_title ) ? esc_html( apply_filters( 'patips_translate_text_external', $page->post_title, false, true, array( 'domain' => 'wordpress', 'object_type' => 'page', 'object_id' => $page_id, 'field' => 'post_title' ) ) ) : '';
	$title      = $page_title ? $page_title : esc_html__( 'Patron area', 'patrons-tips' );
	
	return $title;
}
add_filter( 'woocommerce_endpoint_patronage_title', 'patips_wc_account_patron_area_page_title', 10, 3 );


/**
 * Add the "Patron area" tab to the My Account menu
 * @since 0.1.0
 * @version 0.26.0
 * @param array $tabs
 * @return array
 */
function patips_wc_add_patronage_tab_to_my_account_menu( $tabs ) {
	$page_id = patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
	if( $page_id < 0 ) { return $tabs; }
	
	// Get custom page title
	$page       = $page_id > 0 ? get_page( $page_id ) : null;
	$is_trashed = $page ? $page->post_status === 'trash' : false;
	$page_title = $page && ! $is_trashed && ! empty( $page->post_title ) ? esc_html( apply_filters( 'patips_translate_text_external', $page->post_title, false, true, array( 'domain' => 'wordpress', 'object_type' => 'page', 'object_id' => $page_id, 'field' => 'post_title' ) ) ) : '';
	$title      = $page_title ? $page_title : esc_html__( 'Patron area', 'patrons-tips' );
	
	$inserted = false;
	$new_tabs = array();
	foreach( $tabs as $tab_key => $tab_title ) {
		// Insert the "Patronage" tab before the "Logout" tab
		if( $tab_key === 'customer-logout' && ! $inserted ) {
			$new_tabs[ 'patronage' ] = $title;
			$inserted = true;
		}

		$new_tabs[ $tab_key ] = $tab_title;

		// Insert the "Patronage" tab after the "Dashboard" tab
		if( $tab_key === 'dashboard' && ! $inserted ) {
			$new_tabs[ 'patronage' ] = $title;
			$inserted = true;
		}
	}

	// Insert the "Patronage" tab at the end if it hasn't been yet
	if( ! $inserted ) { $new_tabs[ 'patronage' ] = $title; }

	return $new_tabs;
}
add_filter( 'woocommerce_account_menu_items', 'patips_wc_add_patronage_tab_to_my_account_menu', 50 );


/**
 * Display the content of the "Patronage" tab in My Account
 * @since 0.1.0
 * @version 0.26.0
 */
function patips_wc_display_my_account_patronage_tab_content() {
	$page_id    = intval( patips_get_option( 'patips_settings_general', 'patron_area_page_id' ) );
	$page       = $page_id > 0 ? get_page( $page_id ) : null;
	$is_trashed = $page ? $page->post_status === 'trash' : false;
	
	if( $page && ! $is_trashed && ! empty( $page->post_content ) ) {
		echo apply_filters( 'the_content', apply_filters( 'patips_translate_text_external', $page->post_content, false, true, array( 'domain' => 'wordpress', 'object_type' => 'page', 'object_id' => $page_id, 'field' => 'post_content' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		$html     = wp_kses_post( patips_get_page_template( 'patron-area' ) );
		$is_block = use_block_editor_for_post_type( 'page' );
		echo $is_block ? do_blocks( $html ) : do_shortcode( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'woocommerce_account_patronage_endpoint', 'patips_wc_display_my_account_patronage_tab_content', 10 );




// PATRON AREA

/**
 * Get patron area page URL in My Account
 * @since 0.23.0
 * @param string $url
 * @return string
 */
function patips_wc_patron_area_page_url( $url ) {
	$page_id = intval( patips_get_option( 'patips_settings_general', 'patron_area_page_id' ) );
	
	if( $page_id >= 0 ) {
		$url = wc_get_endpoint_url( 'patronage' );
	}
	
	return $url ? $url : '';
}
add_filter( 'patips_patron_area_page_url', 'patips_wc_patron_area_page_url' );


/**
 * Add WC rewards sections in Patron Area
 * @since 0.22.0
 */
function patips_wc_patron_area_tab_rewards_after() {
	$is_block = use_block_editor_for_post_type( 'page' );
	?>
	<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
	<section id="unlocked-products" class="wp-block-group">
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Unlocked products', 'patrons-tips' ); ?></h2>
		<!-- /wp:heading -->

		<?php if( $is_block ) { ?>
		<!-- wp:patrons-tips/patron-post-list {"types":["product"],"restricted":1,"unlocked":1,"perPage":6} /-->
		<?php } else { ?>
		<!-- wp:shortcode -->
		[patronstips_patron_posts types="product" restricted="1" unlocked="1" per_page="6"]
		<!-- /wp:shortcode -->
		<?php } ?>
	</section>
	<!-- /wp:group -->
<?php
}
add_action( 'patips_patron_area_tab_rewards_after', 'patips_wc_patron_area_tab_rewards_after', 10 );