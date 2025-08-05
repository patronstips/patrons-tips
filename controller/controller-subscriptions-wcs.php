<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// LINK WCS TO PATRONS TIPS API

/**
 * Set WC Subscriptions as main subscription plugin if active
 * @since 0.13.0
 * @version 0.23.0
 * @param string $plugin_name
 * @return string
 */
function patips_wcs_controller_subscription_plugin( $plugin_name ) {
	if( patips_is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
		$plugin_name = 'wcs';
	}
	return $plugin_name;
}
add_filter( 'patips_subscription_plugin', 'patips_wcs_controller_subscription_plugin', 10, 1 );


/**
 * Get WCS subscriptions by ID
 * @since 0.13.1
 * @version 0.24.0
 * @param array $subscriptions
 * @param array $subscription_ids
 * @return array
 */
function patips_wcs_controller_get_subscriptions( $subscriptions, $subscription_ids ) {
	$wcs_raw_subscriptions = $subscription_ids ? patips_wc_get_raw_subscriptions( $subscription_ids, 'shop_subscription' ) : array();
	if( ! $wcs_raw_subscriptions ) { return $subscriptions; }
	
	$raw_subscriptions = array();
	foreach( $wcs_raw_subscriptions as $subscription_id => $wcs_raw_subscription ) {
		$raw_subscriptions[ $subscription_id ] = patips_wcs_convert_raw_subscription( $wcs_raw_subscription );
	}
	
	return $raw_subscriptions;
}
add_filter( 'patips_wcs_subscriptions', 'patips_wcs_controller_get_subscriptions', 10, 2 );


/**
 * Get WC Subscriptions view URL
 * @since 0.13.0
 * @version 1.0.2
 * @param string $subscription_url
 * @param int $subscription_id
 * @return string
 */
function patips_wcs_controller_get_subscription_url( $subscription_url, $subscription_id ) {
	if( ! patips_is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) { return $subscription_url; }
	
	$subscription = $subscription_id && function_exists( 'wcs_get_subscription' ) ? wcs_get_subscription( $subscription_id ) : null;
	if( $subscription ) {
		$subscription_url = $subscription->get_view_order_url();
	}
	
	return $subscription_url;
}
add_filter( 'patips_wcs_subscription_url', 'patips_wcs_controller_get_subscription_url', 10, 2 );


/**
 * Get WC Subscriptions view edit URL
 * @since 0.13.2
 * @version 0.23.0
 * @param string $subscription_url
 * @param int $subscription_id
 * @return string
 */
function patips_wcs_controller_get_subscription_edit_url( $subscription_url, $subscription_id ) {
	if( ! patips_is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) { return $subscription_url; }
	
	if( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
		$subscription_url = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_edit_url( $subscription_id );
	}

	return $subscription_url;
}
add_filter( 'patips_wcs_subscription_edit_url', 'patips_wcs_controller_get_subscription_edit_url', 10, 2 );


/**
 * Get product WCS Subscription frequency
 * @since 0.13.0
 * @version 0.13.1
 * @param string $frequency
 * @param WC_Product $product
 * @param int $product_id
 * @return string
 */
function patips_wcs_controller_get_product_subscription_frequency( $frequency, $product, $product_id ) {
	$period   = $product ? $product->get_meta( '_subscription_period' ) : '';
	$interval = $product ? $product->get_meta( '_subscription_period_interval' ) : 0;
	
	if( $period || $interval ) {
		if( $period === 'year' ) {
			$interval = $interval * 12;
			$period   = 'month';
		}
		$frequency = $interval . '_' . $period;
	}
	
	return $frequency;
}
add_filter( 'patips_wc_product_subscription_frequency', 'patips_wcs_controller_get_product_subscription_frequency', 10, 3 );


/**
 * Get product WCS Subscription period duration
 * @since 0.13.0
 * @version 0.13.1
 * @param string $period_duration
 * @param WC_Product $product
 * @param int $product_id
 * @return int
 */
function patips_wcs_controller_get_product_subscription_period_duration( $period_duration, $product, $product_id ) {
	$period   = $product ? $product->get_meta( '_subscription_period' ) : '';
	$interval = $product ? $product->get_meta( '_subscription_period_interval' ) : 0;
	
	if( $period || $interval ) {
		if( $period === 'month' ) {
			$period_duration = intval( $interval );
		} else if( $period === 'year' ) {
			$period_duration = 12 * intval( $interval );
		} else {
			$period_duration = 0;
		}
	}
	
	return $period_duration;
}
add_filter( 'patips_wc_product_subscription_period_duration', 'patips_wcs_controller_get_product_subscription_period_duration', 10, 3 );


/**
 * Get product WCS Subscription period number
 * @since 0.13.0
 * @param int $period_nb
 * @param WC_Product $product
 * @param int $product_id
 * @return int
 */
function patips_wcs_controller_get_product_subscription_period_nb( $period_nb, $product, $product_id ) {
	$period_duration = patips_wc_get_product_subscription_period_duration( $product_id );
	return floor( $period_duration / patips_get_default_period_duration() );
}
add_filter( 'patips_wc_product_period_nb', 'patips_wcs_controller_get_product_subscription_period_nb', 10, 3 );


/**
 * Check if a cart item is a WC Subscriptions switch
 * @since 0.13.0
 * @since 0.23.0
 * @param boolean $true
 * @param array $cart_item
 * @return boolean
 */
function patips_wcs_controller_is_cart_item_subscription_switch( $true, $cart_item ) {
	if( ! patips_is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) { return $true; }
	
	return ! empty( $cart_item[ 'subscription_switch' ] );
}
add_filter( 'patips_wcs_is_cart_item_subscription_switch', 'patips_wcs_controller_is_cart_item_subscription_switch', 10, 2 );


/**
 * Check if an order is a WC Subscriptions switch
 * @since 0.23.0
 * @param boolean $true
 * @param array $order
 * @return int|false Subscription ID
 */
function patips_wcs_controller_is_subscription_switch_order( $true, $order ) {
	$subscription_id = $order->get_meta( '_subscription_switch', true, 'edit' );
	return $subscription_id ? intval( $subscription_id ) : false;
}
add_filter( 'patips_wcs_is_subscription_switch_order', 'patips_wcs_controller_is_subscription_switch_order', 10, 2 );


/**
 * Check if an order is a WC Subscriptions renewal
 * @since 0.23.0
 * @param boolean $true
 * @param array $order
 * @return int|false Subscription ID
 */
function patips_wcs_controller_is_subscription_renewal_order( $true, $order ) {
	$subscription_id = intval( $order->get_meta( '_subscription_renewal', true, 'edit' ) );
	return $subscription_id ? intval( $subscription_id ) : false;
}
add_filter( 'patips_wcs_is_subscription_renewal_order', 'patips_wcs_controller_is_subscription_renewal_order', 10, 2 );




// SYNC PATRON HISTORY

/**
 * Sync patron history with all their WCS subscriptions
 * @since 0.13.0
 * @version 0.22.0
 * @param array $synced_entry_ids
 * @param array $patron
 * @return array
 */
function patips_wcs_controller_sync_patron_history( $synced_entry_ids, $patron ) {
	// Get patron subscription, renewal and switch orders
	$user_id           = patips_get_patron_user_id( $patron );
	$user_email        = patips_get_patron_user_email( $patron );
	$order_args        = array( 'customer_id' => $user_id, 'billing_email' => $user_email );
	$product_ids       = patips_wc_get_tier_product_ids_by_frequency( array(), array( 'one_off' ) );
	$orders            = $product_ids ? patips_wc_get_orders( array_merge( $order_args, array( 'product_ids' => $product_ids ) ) ) : array();
	$subscription_ids  = $product_ids ? patips_wc_get_orders( array_merge( $order_args, array( 'type' => array( 'shop_subscription' ), 'return_ids' => true ) ) ) : array();
	$subscriptions     = $subscription_ids ? patips_get_subscriptions_data( $subscription_ids, 'wcs' ) : array();
	
	// Sync WCS subscriptions with patronage history
	$recurring_synced_entry_ids = $subscriptions ? patips_wc_sync_patron_history_subscriptions( $patron, $subscriptions, $orders, $product_ids, 'wcs' ) : array();
	
	// Delete history entries linked to a wcs subscription that have not been synced
	$entry_ids_to_delete = array();
	foreach( $patron[ 'history' ] as $history_entry ) {
		$subscription_plugin = ! empty( $history_entry[ 'subscription_plugin' ] ) ? $history_entry[ 'subscription_plugin' ] : '';
		if( $subscription_plugin !== 'wcs' || in_array( $history_entry[ 'id' ], $recurring_synced_entry_ids, true ) ) { continue; }
		
		$entry_ids_to_delete[] = $history_entry[ 'id' ];
	}
	
	if( $entry_ids_to_delete ) {
		patips_delete_patron_history( $patron[ 'id' ], $entry_ids_to_delete, false );
	}
	
	return patips_ids_to_array( array_merge( $synced_entry_ids, $recurring_synced_entry_ids ) );
}
add_filter( 'patips_wc_sync_patron_history', 'patips_wcs_controller_sync_patron_history', 10, 2 );


/**
 * Display WCS label in patron entry origin
 * @since 0.13.2
 * @version 0.25.5
 * @param string $origin
 * @param array $entry
 * @param array $field
 * @param array $patron
 * @return string
 */
function patips_wcs_patron_history_entry_origin( $origin, $entry, $field, $patron ) {
	if( $origin === 'wcs' ) {
		/* translators: This is a plugin name. */
		$origin = esc_html__( 'WooCommerce Subscriptions', 'patrons-tips' );
	}
	return $origin;
}
add_filter( 'patips_patron_history_entry_origin', 'patips_wcs_patron_history_entry_origin', 10, 4 );


/**
 * Sync patron history according to WCS subscription status
 * @since 0.13.4
 * @version 0.23.2
 * @param WC_Subscription $wcs_subscription
 * @param string $status_to
 * @param string $status_from
 */
function patips_wcs_controller_subscription_status_changed_sync_patron_history( $wcs_subscription, $status_to, $status_from ) {
	$wcs_subscription = is_a( $wcs_subscription, 'WC_Order' ) ? $wcs_subscription : null;
	if( ! $wcs_subscription ) { return; }
	
	$has_tiers = patips_wc_order_has_tiers( $wcs_subscription );
	if( ! $has_tiers ) { return; }

	// Sync patron history only if the subscription turned from active to inactive, or inactive to active
	// and do not sync if the status was "pending", as it is already handled by WooCommcerce order
	$active_status      = array( 'active', 'pending-cancel' );
	$active_has_changed = in_array( $status_from, $active_status, true ) !== in_array( $status_to, $active_status, true );
	if( ! $active_has_changed || $status_from === 'pending' ) { return; }
	
	// Get patron by WCS subscription user ID or email
	$user_id    = $wcs_subscription->get_customer_id();
	$user_email = $wcs_subscription->get_billing_email();
	$patron     = patips_get_patron_by_user_id_or_email( $user_id, $user_email, true );
	$patron_id  = isset( $patron[ 'id' ] ) ? $patron[ 'id' ] : 0;
	
	// Sync patron history
	if( $patron_id ) {
		patips_wc_sync_patron_history( $patron_id );
	}
}
add_action( 'woocommerce_subscription_status_updated', 'patips_wcs_controller_subscription_status_changed_sync_patron_history', 100, 3 ); 




// SPECIFIC FEATURES

/**
 * Prevent WCS Subscription containing tier products from being renewed early
 * @since 0.13.0
 * @param bool $can_renew_early
 * @param WC_Subscription $wcs_subscription
 * @param int $user_id
 * @param string $reason
 * @return bool
 */
function patips_wcs_prevent_renew_early( $can_renew_early, $wcs_subscription, $user_id, $reason ) {
	if( ! $can_renew_early ) { return $can_renew_early; }
	
	$has_tiers = patips_wc_order_has_tiers( $wcs_subscription );
	if( ! $has_tiers ) { return $can_renew_early; }
	
	return false;
}
add_filter( 'woocommerce_subscriptions_can_user_renew_early', 'patips_wcs_prevent_renew_early', 10, 4 );


/**
 * Change restricted post content with WC Subscriptions data
 * @since 0.23.0
 * @version 0.26.2
 * @param array $args
 * @param WP_Post $post
 * @param array $unlock_tiers
 * @param int $user_id
 * @return array
 */
function patips_wcs_restricted_post_content_args( $args, $post, $unlock_tiers, $user_id ) {
	if( ! patips_is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) { return $args; }
	if( ! class_exists( 'WC_Subscriptions_Switcher' ) ) { return $args; }
	
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	$patron   = patips_get_user_patron_data( $user_id );
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	// Get patron current subscription url
	$subscription_id  = false;
	$subscription_url = false;
	if( $patron && ! empty( $patron[ 'history' ] ) ) {
		foreach( $patron[ 'history' ] as $history_entry ) {
			if( $history_entry[ 'subscription_plugin' ] !== 'wcs' ) { continue; }
			
			// Check current patronage only
			$start_dt   = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_start' ] . ' 00:00:00', $timezone );
			$end_dt     = DateTime::createFromFormat( 'Y-m-d H:i:s', $history_entry[ 'date_end' ] . ' 23:59:59', $timezone );
			$is_current = $start_dt <= $now_dt && $end_dt >= $now_dt && $history_entry[ 'active' ];
			
			if( ! $is_current ) { continue; }
			
			$subscription_id  = $history_entry[ 'subscription_id' ];
			$subscription_url = patips_get_subscription_url_by_plugin( $history_entry[ 'subscription_id' ], $history_entry[ 'subscription_plugin' ] );
			break;
		}
	}
	
	// Get subscription
	$subscription = $subscription_id && function_exists( 'wcs_get_subscription' ) ? wcs_get_subscription( $subscription_id ) : null;

	// Check if the subscription can be switched and get the switch URL
	$is_switch_allowed = false;
	if( $subscription ) {
		foreach( $subscription->get_items( 'line_item' ) as $line_item_id => $line_item ) {
			$item_id           = $line_item_id;
			$item              = $line_item;
			$is_switch_allowed = WC_Subscriptions_Switcher::can_item_be_switched_by_user( $item, $subscription );
			$switch_url        = WC_Subscriptions_Switcher::get_switch_url( $line_item_id, $line_item, $subscription );
			if( $switch_url ) { $subscription_url = $switch_url; }
			break;
		}
	}
	
	// Add "upgrade" action, remove "become_patron" action 
	if( $subscription_url && $is_switch_allowed ) {
		$pos_i = array_search( 'become_patron', array_keys( $args[ 'actions' ] ) );
		if( $pos_i === false ) { $pos_i = count( $args[ 'actions' ] ); } 
		
		$args[ 'actions' ] = array_slice( $args[ 'actions' ], 0, $pos_i ) 
		                   + array( 'upgrade' => '<a href="' . $subscription_url . '">' . esc_html__( 'Upgrade my subscription', 'patrons-tips' ) . '</a>' ) 
		                   + $args[ 'actions' ];
		
		unset( $args[ 'actions' ][ 'become_patron' ] );
	}
	
	return $args;
}
add_filter( 'patips_restricted_post_content_args', 'patips_wcs_restricted_post_content_args', 20, 4 );