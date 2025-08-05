<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// LINK FSB TO PATRONS TIPS API

/**
 * Set Flexible Subscriptions as main subscription plugin if active
 * @since 0.23.0
 * @param string $plugin_name
 * @return string
 */
function patips_fsb_controller_subscription_plugin( $plugin_name ) {
	if( patips_is_plugin_active( 'flexible-subscriptions/flexible-subscriptions.php' ) ) {
		$plugin_name = 'fsb';
	}
	return $plugin_name;
}
add_filter( 'patips_subscription_plugin', 'patips_fsb_controller_subscription_plugin', 10, 1 );


/**
 * Get FSB subscriptions by ID
 * @since 0.23.0
 * @version 0.24.0
 * @param array $subscriptions
 * @param array $subscription_ids
 * @return array
 */
function patips_fsb_controller_get_subscriptions( $subscriptions, $subscription_ids ) {
	$fsb_raw_subscriptions = $subscription_ids ? patips_wc_get_raw_subscriptions( $subscription_ids, 'fsb_subscription' ) : array();
	if( ! $fsb_raw_subscriptions ) { return $subscriptions; }
	
	$raw_subscriptions = array();
	foreach( $fsb_raw_subscriptions as $subscription_id => $fsb_raw_subscription ) {
		$raw_subscriptions[ $subscription_id ] = patips_fsb_convert_raw_subscription( $fsb_raw_subscription );
	}
	
	return $raw_subscriptions;
}
add_filter( 'patips_fsb_subscriptions', 'patips_fsb_controller_get_subscriptions', 10, 2 );


/**
 * Get Flexible Subscriptions view URL
 * @since 0.23.0
 * @param string $subscription_url
 * @param int $subscription_id
 * @return string
 */
function patips_fsb_controller_get_subscription_url( $subscription_url, $subscription_id ) {
	if( ! patips_is_plugin_active( 'flexible-subscriptions/flexible-subscriptions.php' ) ) { return $subscription_url; }
	
	$subscription_url = wc_get_endpoint_url( 'view-fsb-subscription', $subscription_id, wc_get_page_permalink( 'myaccount' ) );
	
	return $subscription_url;
}
add_filter( 'patips_fsb_subscription_url', 'patips_fsb_controller_get_subscription_url', 10, 2 );


/**
 * Get Flexible Subscriptions view edit URL
 * @since 0.23.0
 * @param string $subscription_url
 * @param int $subscription_id
 * @return string
 */
function patips_fsb_controller_get_subscription_edit_url( $subscription_url, $subscription_id ) {
	if( ! patips_is_plugin_active( 'flexible-subscriptions/flexible-subscriptions.php' ) ) { return $subscription_url; }
	
	if( patips_wc_is_hpos_enabled() ) {
		$subscription_url = admin_url( 'admin.php?page=wc-orders--fsb_subscription&action=edit&id=' . $subscription_id );
	} else {
		$subscription_url = admin_url( 'edit.php?post_type=fsb_subscription&action=edit&id=' . $subscription_id );
	}
	
	return $subscription_url;
}
add_filter( 'patips_fsb_subscription_edit_url', 'patips_fsb_controller_get_subscription_edit_url', 10, 2 );


/**
 * Get product FSB Subscription frequency
 * @since 0.23.0
 * @param string $frequency
 * @param WC_Product $product
 * @param int $product_id
 * @return string
 */
function patips_fsb_controller_get_product_subscription_frequency( $frequency, $product, $product_id ) {
	$period   = $product ? $product->get_meta( '_fsb_subscription_period' ) : '';
	$interval = $product ? $product->get_meta( '_fsb_subscription_interval' ) : 0;
	
	if( is_numeric( $interval ) ) {
		$interval = intval( $interval );
	}

	if( $period && $interval ) {
		$frequency = patips_convert_date_interval_to_subscription_frequency( 'P' . $interval . $period );
	}
	
	return $frequency;
}
add_filter( 'patips_wc_product_subscription_frequency', 'patips_fsb_controller_get_product_subscription_frequency', 10, 3 );


/**
 * Get product FSB Subscription period duration in months
 * @since 0.23.0
 * @param string $period_duration
 * @param WC_Product $product
 * @param int $product_id
 * @return int
 */
function patips_fsb_controller_get_product_subscription_period_duration( $period_duration, $product, $product_id ) {
	$period   = $product ? $product->get_meta( '_fsb_subscription_period' ) : '';
	$interval = $product ? $product->get_meta( '_fsb_subscription_interval' ) : 0;
	
	if( is_numeric( $interval ) ) {
		$interval = intval( $interval );
	}
	
	if( $period && $interval ) {
		if( $period === 'M' ) {
			$period_duration = intval( $interval );
		} else if( $period === 'Y' ) {
			$period_duration = 12 * intval( $interval );
		} else {
			$period_duration = 0;
		}
	}
	
	return $period_duration;
}
add_filter( 'patips_wc_product_subscription_period_duration', 'patips_fsb_controller_get_product_subscription_period_duration', 10, 3 );


/**
 * Get product FSB Subscription period number
 * @since 0.23.0
 * @param int $period_nb
 * @param WC_Product $product
 * @param int $product_id
 * @return int
 */
function patips_fsb_controller_get_product_subscription_period_nb( $period_nb, $product, $product_id ) {
	$period_duration = patips_wc_get_product_subscription_period_duration( $product_id );
	return floor( $period_duration / patips_get_default_period_duration() );
}
add_filter( 'patips_wc_product_period_nb', 'patips_fsb_controller_get_product_subscription_period_nb', 10, 3 );


/**
 * Check if an order is a Flexible Subscriptions switch
 * @since 0.23.0
 * @param boolean $true
 * @param array $order
 * @return boolean
 */
function patips_fsb_controller_is_subscription_switch_order( $true, $order ) {
	$true = false;
	return $true;
}
add_filter( 'patips_fsb_is_subscription_switch_order', 'patips_fsb_controller_is_subscription_switch_order', 10, 2 );


/**
 * Check if an order is a Flexible Subscriptions renewal
 * @since 0.23.0
 * @version 0.25.4
 * @param boolean $true
 * @param WC_Order $order
 * @return int|boolean
 */
function patips_fsb_controller_is_subscription_renewal_order( $true, $order ) {
	$sub_id = $order->get_parent_id();
	$true   = $order->get_meta( '_fsb_order_type', true, 'edit' ) === 'renewal' || did_action( 'fsub/subscription/payment_request/process' );
	
	// If the order has a parent (a subscription), but doesn't have a _fsb_order_type meta yet, maybe the order was just created
	if( ! $true && $sub_id ) {
		$request_dt   = new DateTime();
		$request_dt->setTimeZone( new DateTimeZone( 'UTC' ) );
		$request_time = ! empty( $_SERVER[ 'REQUEST_TIME' ] ) ? intval( $_SERVER[ 'REQUEST_TIME' ]  ) : $request_dt->getTimestamp() - 3;
		$request_dt->setTimestamp( $request_time );
		$created_dt   = $order->get_date_created( 'edit' );
		
		// If the order was created during this very request, it is a renewal
		$true = $created_dt && $created_dt >= $request_dt && did_action( 'fsub/subscription/payment_request/process' );
	}
	
	return $true && $sub_id ? $sub_id : false;
}
add_filter( 'patips_fsb_is_subscription_renewal_order', 'patips_fsb_controller_is_subscription_renewal_order', 10, 2 );




// SYNC PATRON HISTORY

/**
 * Sync patron history with all their FSB subscriptions
 * @since 0.23.0
 * @param array $synced_entry_ids
 * @param array $patron
 * @return array
 */
function patips_fsb_controller_sync_patron_history( $synced_entry_ids, $patron ) {
	// Get patron subscription, renewal and switch orders
	$user_id           = patips_get_patron_user_id( $patron );
	$user_email        = patips_get_patron_user_email( $patron );
	$order_args        = array( 'customer_id' => $user_id, 'billing_email' => $user_email );
	$product_ids       = patips_wc_get_tier_product_ids_by_frequency( array(), array( 'one_off' ) );
	$orders            = $product_ids ? patips_wc_get_orders( array_merge( $order_args, array( 'product_ids' => $product_ids ) ) ) : array();
	$subscription_ids  = $product_ids ? patips_wc_get_orders( array_merge( $order_args, array( 'type' => array( 'fsb_subscription' ), 'return_ids' => true ) ) ) : array();
	$subscriptions     = $subscription_ids ? patips_get_subscriptions_data( $subscription_ids, 'fsb' ) : array();
	
	// Sync FSB subscriptions with patronage history
	$recurring_synced_entry_ids = $subscriptions ? patips_wc_sync_patron_history_subscriptions( $patron, $subscriptions, $orders, $product_ids, 'fsb' ) : array();
	
	// Delete history entries linked to a FSB subscription that have not been synced
	$entry_ids_to_delete = array();
	foreach( $patron[ 'history' ] as $history_entry ) {
		$subscription_plugin = ! empty( $history_entry[ 'subscription_plugin' ] ) ? $history_entry[ 'subscription_plugin' ] : '';
		if( $subscription_plugin !== 'fsb' || in_array( $history_entry[ 'id' ], $recurring_synced_entry_ids, true ) ) { continue; }
		
		$entry_ids_to_delete[] = $history_entry[ 'id' ];
	}
	
	if( $entry_ids_to_delete ) {
		patips_delete_patron_history( $patron[ 'id' ], $entry_ids_to_delete, false );
	}
	
	return patips_ids_to_array( array_merge( $synced_entry_ids, $recurring_synced_entry_ids ) );
}
add_filter( 'patips_wc_sync_patron_history', 'patips_fsb_controller_sync_patron_history', 10, 2 );


/**
 * Display FSB label in patron entry origin
 * @since 0.23.0
 * @version 0.25.5
 * @param string $origin
 * @param array $entry
 * @param array $field
 * @param array $patron
 * @return string
 */
function patips_fsb_patron_history_entry_origin( $origin, $entry, $field, $patron ) {
	if( $origin === 'fsb' ) {
		/* translators: This is a plugin name. */
		$origin = esc_html__( 'Flexible Subscriptions', 'patrons-tips' );
	}
	return $origin;
}
add_filter( 'patips_patron_history_entry_origin', 'patips_fsb_patron_history_entry_origin', 10, 4 );


/**
 * Sync patron history according to FSB subscription status
 * @since 0.23.0
 * @version 0.23.2
 * @param WPDesk\FlexibleSubscriptions\Subscription $fsb_subscription
 * @param string $status_to
 * @param string $status_from // MISSING
 */
function patips_fsb_controller_subscription_status_changed_sync_patron_history( $fsb_subscription, $status_to, $status_from = '' ) {
	$fsb_subscription = is_a( $fsb_subscription, 'WC_Order' ) ? $fsb_subscription : null;
	if( ! $fsb_subscription ) { return; }
	
	$has_tiers = patips_wc_order_has_tiers( $fsb_subscription );
	if( ! $has_tiers ) { return; }
	
	// WE CANNOT DO THAT YET BECAUSE $status_from IS CURRENTLY MISSING
	// 
	// Sync patron history only if the subscription turned from active to inactive, or inactive to active
	// and do not sync if the status was "pending", as it is already handled by WooCommcerce order
//	$active_status      = array( 'active', 'pending-cancel' );
//	$active_has_changed = in_array( $status_from, $active_status, true ) !== in_array( $status_to, $active_status, true );
//	if( ! $active_has_changed || $status_from === 'pending' ) { return; }
	
	// Get patron by FSB subscription user ID or email
	$user_id    = $fsb_subscription->get_customer_id();
	$user_email = $fsb_subscription->get_billing_email();
	$patron     = patips_get_patron_by_user_id_or_email( $user_id, $user_email, true );
	$patron_id  = isset( $patron[ 'id' ] ) ? $patron[ 'id' ] : 0;
	
	// Sync patron history
	if( $patron_id ) {
		patips_wc_sync_patron_history( $patron_id );
	}
}
add_action( 'fsub/subscription/status/updated', 'patips_fsb_controller_subscription_status_changed_sync_patron_history', 100, 3 );


/**
 * Require a valid payment method even for subscriptions with free trial period
 * @since 0.25.7
 */
add_filter( 'fsub/cart/require_payment_on_trial', '__return_true' );