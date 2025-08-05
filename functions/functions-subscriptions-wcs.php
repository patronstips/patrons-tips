<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Whether to compute the period retroactively if payment was deferred
 * @since 0.13.0
 * @return bool
 */
function patips_wcs_is_deferred_payment_retroactive() {
	return apply_filters( 'patips_wcs_is_deferred_payment_retroactive', true );
}


/**
 * Convert raw subscription to subscription format
 * @since 0.13.1
 * @version 0.24.0
 * @param array $wcs_raw_subscription
 * @return array
 */
function patips_wcs_convert_raw_subscription( $wcs_raw_subscription ) {
	$order_item    = isset( $wcs_raw_subscription[ 'order_items' ] ) ? reset( $wcs_raw_subscription[ 'order_items' ] ) : array();
	$order_item_id = isset( $order_item[ 'order_item_id' ] ) ? intval( $order_item[ 'order_item_id' ] ) : 0;
	$total         = isset( $order_item[ 'line_subtotal' ] ) ? floatval( $order_item[ 'line_subtotal' ] ) : 0;
	$total_tax     = isset( $order_item[ 'line_subtotal_tax' ] ) ? floatval( $order_item[ 'line_subtotal_tax' ] ) : 0;
	$variation_id  = isset( $order_item[ 'variation_id' ] ) ? intval( $order_item[ 'variation_id' ] ) : 0;
	$product_id    = isset( $order_item[ 'product_id' ] ) ? intval( $order_item[ 'product_id' ] ) : 0;
	
	$status   = isset( $wcs_raw_subscription[ 'status' ] ) ? $wcs_raw_subscription[ 'status' ] : '';
	$interval = isset( $wcs_raw_subscription[ 'billing_interval' ] ) ? intval( $wcs_raw_subscription[ 'billing_interval' ] ) : 0;
	$period   = isset( $wcs_raw_subscription[ 'billing_period' ] ) ? sanitize_title_with_dashes( $wcs_raw_subscription[ 'billing_period' ] ) : '';
	if( $period === 'year' ) {
		$interval = $interval * 12;
		$period   = 'month';
	}
	
	// Is retroactive
	$is_deferred_retro = patips_wcs_is_deferred_payment_retroactive();
	$is_deferred       = ! empty( $wcs_raw_subscription[ 'contains_synced_subscription' ] );
	$parent_order      = $wcs_raw_subscription[ 'parent_order_id' ] ? wc_get_order( $wcs_raw_subscription[ 'parent_order_id' ] ) : null;
	$parent_has_total  = $parent_order ? $parent_order->get_subtotal() > 0 : false;
	$is_retroactive    = $is_deferred && $is_deferred_retro && ! $parent_has_total;
	
	$subscription = array( 
		'id'             => intval( $wcs_raw_subscription[ 'id' ] ),
		'plugin'         => 'wcs',
		'frequency'      => $interval && $period ? $interval . '_' . $period : '',
		'user_id'        => ! empty( $wcs_raw_subscription[ 'customer_id' ] ) ? intval( $wcs_raw_subscription[ 'customer_id' ] ) : 0,
		'user_email'     => ! empty( $wcs_raw_subscription[ 'billing_email' ] ) ? sanitize_email( $wcs_raw_subscription[ 'customer_id' ] ) : '',
		'product_id'     => $variation_id ? $variation_id : $product_id,
		'order_id'       => intval( $wcs_raw_subscription[ 'parent_order_id' ] ),
		'order_item_id'  => intval( $order_item_id ),
		'price'          => $total + $total_tax,
		'date_created'   => ! empty( $wcs_raw_subscription[ 'date_created_gmt' ] ) ? $wcs_raw_subscription[ 'date_created_gmt' ] : '',
		'date_start'     => ! empty( $wcs_raw_subscription[ 'date_created_gmt' ] ) ? $wcs_raw_subscription[ 'date_created_gmt' ] : '',
		'date_end'       => ! empty( $wcs_raw_subscription[ 'schedule_end' ] ) ? $wcs_raw_subscription[ 'schedule_end' ] : '',
		'date_renewal'   => ! empty( $wcs_raw_subscription[ 'schedule_next_payment' ] ) ? $wcs_raw_subscription[ 'schedule_next_payment' ] : '',
		'date_trial_end' => ! empty( $wcs_raw_subscription[ 'schedule_trial_end' ] ) ? $wcs_raw_subscription[ 'schedule_trial_end' ] : '',
		'deferred'       => $is_deferred ? 1 : 0,
		'retroactive'    => $is_retroactive ? 1 : 0,
		'ignore_parent'  => $is_retroactive && empty( $wcs_raw_subscription[ 'schedule_trial_end' ] ) ? 1 : 0, // If the payment is retroactive and there are no free trial, ignore the parent order
		'pending_cancel' => $status === 'wc-pending-cancel' ? 1 : 0,
		'active'         => in_array( $status, array( 'wc-active', 'wc-pending-cancel' ), true ) ? 1 : 0
	);
	
	return apply_filters( 'patips_wcs_converted_subscription', $subscription, $wcs_raw_subscription );
}