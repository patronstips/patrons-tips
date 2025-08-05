<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// SUBSCRIPTION CONNECT API

/**
 * Get current subscription plugin identifier
 * @since 0.9.0
 * @return string
 */
function patips_get_subscription_plugin() {
	return apply_filters( 'patips_subscription_plugin', '' );
}


/**
 * Get subscriptions of the desired subscription plugin
 * @since 0.10.0
 * @param array $subscription_ids
 * @param string $plugin
 * @return array
 */
function patips_get_subscriptions_by_plugin( $subscription_ids, $plugin = '' ) {
	if( ! $plugin ) {
		$plugin = patips_get_subscription_plugin();
	}
	return apply_filters( 'patips_' . $plugin . '_subscriptions', array(), $subscription_ids );
}


/**
 * Get subscription url according to subscription plugin
 * @since 0.10.0
 * @param int $subscription_id
 * @param string $plugin
 * @return string
 */
function patips_get_subscription_url_by_plugin( $subscription_id, $plugin = '' ) {
	if( ! $plugin ) {
		$plugin = patips_get_subscription_plugin();
	}
	return apply_filters( 'patips_' . $plugin . '_subscription_url', '', $subscription_id );
}


/**
 * Get subscription edit url according to subscription plugin
 * @since 0.13.2
 * @param int $subscription_id
 * @param string $plugin
 * @return string
 */
function patips_get_subscription_edit_url_by_plugin( $subscription_id, $plugin = '' ) {
	if( ! $plugin ) {
		$plugin = patips_get_subscription_plugin();
	}
	return apply_filters( 'patips_' . $plugin . '_subscription_edit_url', '', $subscription_id );
}


/**
 * Check if the cart item is a subscription switch based on desired subscription plugin
 * @since 0.10.0
 * @version 0.13.4
 * @param array $cart_item
 * @param string $plugin
 * @return boolean
 */
function patips_wc_is_cart_item_subscription_switch( $cart_item, $plugin = '' ) {
	if( ! $plugin ) {
		$plugin = patips_get_subscription_plugin();
	}
	return apply_filters( 'patips_' . $plugin . '_is_cart_item_subscription_switch', false, $cart_item );
}


/**
 * Check if the order is a subscription switch based on desired subscription plugin
 * @since 0.23.0
 * @param WC_Order $order
 * @param string $plugin
 * @return int|false Subscription ID
 */
function patips_wc_is_subscription_switch_order( $order, $plugin = '' ) {
	if( ! $plugin ) {
		$plugin = patips_get_subscription_plugin();
	}
	return apply_filters( 'patips_' . $plugin . '_is_subscription_switch_order', false, $order );
}


/**
 * Check if the order is a subscription renewal based on desired subscription plugin
 * @since 0.23.0
 * @param WC_Order $order
 * @param string $plugin
 * @return int|false Subscription ID
 */
function patips_wc_is_subscription_renewal_order( $order, $plugin = '' ) {
	if( ! $plugin ) {
		$plugin = patips_get_subscription_plugin();
	}
	return apply_filters( 'patips_' . $plugin . '_is_subscription_renewal_order', false, $order );
}


/**
 * Get the subscription frequency of a product
 * @since 0.13.0
 * @param int $product_id
 * @return array
 */
function patips_wc_get_product_subscription_frequency( $product_id ) {
	$product    = is_numeric( $product_id ) ? wc_get_product( $product_id ) : $product_id;
	$product_id = is_a( $product, 'WC_Product' ) ? $product->get_id() : $product_id;
	return apply_filters( 'patips_wc_product_subscription_frequency', 'one_off', $product, $product_id );
}


/**
 * Get the subscription period duration in months of a product
 * @since 0.13.0
 * @param int $product_id
 * @return int
 */
function patips_wc_get_product_subscription_period_duration( $product_id ) {
	$product    = is_numeric( $product_id ) ? wc_get_product( $product_id ) : $product_id;
	$product_id = is_a( $product, 'WC_Product' ) ? $product->get_id() : $product_id;
	return apply_filters( 'patips_wc_product_subscription_period_duration', patips_get_default_period_duration(), $product, $product_id );
}


/**
 * Get the subscription number of periods of a product
 * @since 0.13.0
 * @param int $product_id
 * @return int
 */
function patips_wc_get_product_subscription_period_nb( $product_id ) {
	$product    = is_numeric( $product_id ) ? wc_get_product( $product_id ) : $product_id;
	$product_id = is_a( $product, 'WC_Product' ) ? $product->get_id() : $product_id;
	$period_duration = patips_wc_get_product_subscription_period_duration( $product_id );
	return apply_filters( 'patips_wc_product_subscription_period_nb', floor( $period_duration / patips_get_default_period_duration() ), $product, $product_id );
}


/**
 * Get error notice if tier products are not configured properly
 * @since 0.13.0
 * @version 0.26.0
 * @param array $tier_product_ids
 * @param boolean $show_tier_link
 * @param boolean $return_array
 * @return string
 */
function patips_wc_get_tier_product_misconfiguration_notice( $tier_product_ids, $return_array = false ) {
	$notices_by_type = array( 'error' => array(), 'warning' => array(), 'info' => array() );
	
	foreach( $tier_product_ids as $tier_frequency => $product_ids ) {
		$tier_frequency_name = patips_get_frequency_name( $tier_frequency );
		foreach( $product_ids as $product_id ) {
			$product                = wc_get_product( $product_id );
			$product_frequency      = patips_wc_get_product_subscription_frequency( $product ? $product : $product_id );
			$product_frequency_name = patips_get_frequency_name( $product_frequency );
			
			if( $product ) {
				/* translators: %s = Product ID. */
				$product_title = $product->get_title() ? esc_html( $product->get_title() ) : sprintf( esc_html__( 'Product #%s', 'patrons-tips' ), $product_id );
				if( is_a( $product, 'WC_Product_Variation' ) ) {
					$variation_title           = $product->get_name() !== '' ? esc_html( apply_filters( 'patips_translate_text_external', $product->get_name(), false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_variation', 'object_id' => $product_id, 'field' => 'post_excerpt', 'product_id' => $product->get_parent_id() ) ) ) : $product->get_name();
					$formatted_variation_title = trim( preg_replace( '/,[\s\S]+?:/', ',', ',' . $variation_title ), ', ' );
					if( $formatted_variation_title ) { 
						$product_title = $formatted_variation_title;
					}
				}
				
				$product_edit_url  = is_a( $product, 'WC_Product_Variation' ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product_id );
				$product_edit_link = $product_edit_url ? '<a href="' . esc_url( $product_edit_url ) . '">' . $product_title . '</a>' : $product_title;
			}
			
			if( $product && $tier_frequency !== $product_frequency ) {
				if( ! $tier_frequency || $tier_frequency === 'one_off' ) {
					/* translators: %1$s = Product title and link to the product page. %2$s = Frequency name like "every 2 months", "yearly"... */
					$notices_by_type[ 'error' ][] = sprintf( esc_html__( 'The product "%1$s" should not be recurring, however it is configured to renew %2$s.', 'patrons-tips' ), '<strong>' . $product_edit_link . '</strong>', '<strong>' . $product_frequency_name . '</strong>' );
				} else if( ! $product_frequency || $product_frequency === 'one_off' ) {
					/* translators: %1$s = Product title and link to the product page. %2$s = Frequency name like "every 2 months", "yearly"... */
					$notices_by_type[ 'error' ][] = sprintf( esc_html__( 'The product "%1$s" should renew "%2$s", however it is configured as not recurring.', 'patrons-tips' ), '<strong>' . $product_edit_link . '</strong>', '<strong>' . $tier_frequency_name . '</strong>' );
				} else {
					/* translators: %1$s = Product title and link to the product page. %2$s and %3$s = Frequency name like "every 2 months", "yearly"... */
					$notices_by_type[ 'error' ][] = sprintf( esc_html__( 'The product "%1$s" should renew "%2$s", however it is configured to renew "%3$s".', 'patrons-tips' ), '<strong>' . $product_edit_link . '</strong>', '<strong>' . $tier_frequency_name . '</strong>', '<strong>' . $product_frequency_name . '</strong>' );
				}
			}
			
			// Check if the product / variation is virtual
			if( $product && ! $product->get_virtual() ) {
				/* translators: %s = Product title and link to the product page. */
				$notices_by_type[ 'warning' ][] = sprintf( esc_html__( 'The product "%s" is not configured as "Virtual", the order will not be automatically completed.', 'patrons-tips' ), '<strong>' . $product_edit_link . '</strong>' );
			}
		}
	}
	
	$notices_by_type = apply_filters( 'patips_tier_product_misconfiguration_notices', $notices_by_type, $tier_product_ids );
	
	// Return array
	if( $return_array ) {
		return $notices_by_type;
	}
	
	// Notice string
	ob_start();
	
	foreach( $notices_by_type as $type => $notices ) {
		if( ! $notices ) { continue; }
		?>
			<div class='<?php echo esc_attr( 'patips-' . $type ); ?>'>
				<span>
					<?php
						echo count( $notices ) > 1 ? wp_kses_post( '<ul><li>' . implode( '<li>', $notices ) . '</ul>' ) : wp_kses_post( current( $notices ) );
					?>
				</span>
			</div>
		<?php
	}
	
	$html = ob_get_clean();
	
	return $html;
}




// SUBSCRIPTION DATA

/**
 * Get default subscription data
 * @since 0.10.0
 * @version 0.24.0
 * @return array
 */
function patips_get_default_subscription_data() {
	return apply_filters( 'patips_default_subscription_data', array( 
		'id'             => 0,
		'plugin'         => '',
		'frequency'      => '',
		'user_id'        => 0,
		'user_email'     => '',
		'order_id'       => 0,
		'order_item_id'  => 0,
		'product_id'     => 0,
		'price'          => 0,
		'date_created'   => '',
		'date_start'     => '',
		'date_end'       => '',
		'date_renewal'   => '',
		'date_trial_end' => '',
		'deferred'       => 0,
		'retroactive'    => 0,
		'ignore_parent'  => 0,
		'pending_cancel' => 0,
		'active'         => 0
	) );
}


/**
 * Get subscriptions data
 * @since 0.13.1
 * @version 0.13.2
 * @param int $subscription_ids
 * @param string $plugin
 * @return array
 */
function patips_get_subscriptions_data( $subscription_ids, $plugin = '' ) {
	$subscriptions_raw = $subscription_ids ? patips_get_subscriptions_by_plugin( $subscription_ids, $plugin ) : array();
	if( ! $subscriptions_raw ) { return array(); }
	
	$subscriptions = array();
	foreach( $subscriptions_raw as $subscription_id => $subscription_raw ) {
		$subscriptions[ $subscription_id ] = patips_format_subscription_data( array_merge( (array) $subscription_raw, array( 
			'id'     => $subscription_id, 
			'plugin' => $plugin 
		) ) );
		
		$subscriptions[ $subscription_id ] = apply_filters( 'patips_subscription_data', $subscriptions[ $subscription_id ], $subscription_raw, $subscription_id, $plugin );
	}

	return $subscriptions;
}


/**
 * Get subscription data
 * @since 0.10.0
 * @version 0.23.0
 * @param int $subscription_id
 * @param string $plugin
 * @return array
 */
function patips_get_subscription_data( $subscription_id, $plugin = '' ) {
	$subscriptions = $subscription_id ? patips_get_subscriptions_data( array( $subscription_id ), $plugin ) : array();
	return ! empty( $subscriptions[ $subscription_id ] ) ? $subscriptions[ $subscription_id ] : array();
}


/**
 * Format subscription data
 * @since 0.10.0
 * @version 0.24.0
 * @param array $raw_subscription_data
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_format_subscription_data( $raw_subscription_data = array(), $context = 'view' ) {
	if( ! is_array( $raw_subscription_data ) ) { $raw_subscription_data = array(); }
	
	$default_data = patips_get_default_subscription_data( $context );
	$keys_by_type = array( 
		'int'      => array( 'id', 'user_id', 'order_id', 'order_item_id', 'product_id' ),
		'email'    => array( 'user_email' ),
		'float'    => array( 'price' ),
		'str_id'   => array( 'plugin', 'frequency' ),
		'datetime' => array( 'date_created', 'date_start', 'date_end', 'date_renewal', 'date_trial_end' ),
		'bool'     => array( 'deferred', 'retroactive', 'ignore_parent', 'pending_cancel', 'active' )
	);
	$subscription_data = patips_sanitize_values( $default_data, $raw_subscription_data, $keys_by_type );
	
	return apply_filters( 'patips_formatted_subscription_data', $subscription_data, $raw_subscription_data, $context );
}


/**
 * Convert DateInterval to subscription frequency
 * @since 0.23.0
 * @param DateInterval|string $di
 * @return string
 */
function patips_convert_date_interval_to_subscription_frequency( $di ) {
	// Sanitize DateInterval
	$di = is_string( $di ) ? new DateInterval( $di ) : ( is_a( $di, 'DateInterval' ) ? $di : null );
	if( ! $di ) { return ''; }
	
	$interval = 0;
	$period   = '';
	
	if( intval( $di->format( '%y' ) ) ) {
		$interval = intval( $di->format( '%y' ) ) * 12;
		$period   = 'month';
	}
	
	else if( intval( $di->format( '%m' ) ) ) {
		$interval = intval( $di->format( '%m' ) );
		$period   = 'month';
	}
	
	else if( intval( $di->format( '%d' ) ) ) {
		$interval = intval( $di->format( '%d' ) );
		$period   = 'day';
	}
	
	$frequency = $interval && $period ? $interval . '_' . $period : '';
	
	return $frequency;
}