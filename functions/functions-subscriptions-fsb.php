<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Convert raw subscription to subscription format
 * @since 0.23.0
 * @version 0.24.0
 * @param array $fsb_raw_subscription
 * @return array
 */
function patips_fsb_convert_raw_subscription( $fsb_raw_subscription ) {
	$order_item    = isset( $fsb_raw_subscription[ 'order_items' ] ) ? reset( $fsb_raw_subscription[ 'order_items' ] ) : array();
	$order_item_id = isset( $order_item[ 'order_item_id' ] ) ? intval( $order_item[ 'order_item_id' ] ) : 0;
	$total         = isset( $order_item[ 'line_subtotal' ] ) ? floatval( $order_item[ 'line_subtotal' ] ) : 0;
	$total_tax     = isset( $order_item[ 'line_subtotal_tax' ] ) ? floatval( $order_item[ 'line_subtotal_tax' ] ) : 0;
	$variation_id  = isset( $order_item[ 'variation_id' ] ) ? intval( $order_item[ 'variation_id' ] ) : 0;
	$product_id    = isset( $order_item[ 'product_id' ] ) ? intval( $order_item[ 'product_id' ] ) : 0;
	
	$status      = isset( $fsb_raw_subscription[ 'status' ] ) ? $fsb_raw_subscription[ 'status' ] : '';
	$di_duration = isset( $fsb_raw_subscription[ 'billing_frequency' ] ) ? $fsb_raw_subscription[ 'billing_frequency' ] : '';
	
	$subscription = array( 
		'id'             => intval( $fsb_raw_subscription[ 'id' ] ),
		'plugin'         => 'fsb',
		'frequency'      => $di_duration ? patips_convert_date_interval_to_subscription_frequency( $di_duration ) : '',
		'user_id'        => ! empty( $fsb_raw_subscription[ 'customer_id' ] ) ? intval( $fsb_raw_subscription[ 'customer_id' ] ) : 0,
		'user_email'     => ! empty( $fsb_raw_subscription[ 'billing_email' ] ) ? sanitize_email( $fsb_raw_subscription[ 'customer_id' ] ) : '',
		'product_id'     => $variation_id ? $variation_id : $product_id,
		'order_id'       => intval( $fsb_raw_subscription[ 'parent_order_id' ] ),
		'order_item_id'  => intval( $order_item_id ),
		'price'          => $total + $total_tax,
		'date_created'   => ! empty( $fsb_raw_subscription[ 'date_created_gmt' ] ) ? $fsb_raw_subscription[ 'date_created_gmt' ] : '',
		'date_start'     => ! empty( $fsb_raw_subscription[ 'start_date_utc' ] ) ? $fsb_raw_subscription[ 'start_date_utc' ] : '',
		'date_end'       => ! empty( $fsb_raw_subscription[ 'end_date_utc' ] ) ? $fsb_raw_subscription[ 'end_date_utc' ] : '',
		'date_renewal'   => ! empty( $fsb_raw_subscription[ 'current_period_end_utc' ] ) ? $fsb_raw_subscription[ 'current_period_end_utc' ] : '',
		'date_trial_end' => ! empty( $fsb_raw_subscription[ 'trial_end_date_utc' ] ) ? $fsb_raw_subscription[ 'trial_end_date_utc' ] : '',
		'deferred'       => 0,
		'retroactive'    => 0,
		'ignore_parent'  => 0,
		'pending_cancel' => $status === 'wc-pending-cancel' ? 1 : 0,
		'active'         => in_array( $status, array( 'wc-active', 'wc-pending-cancel' ), true ) ? 1 : 0
	);
	
	return apply_filters( 'patips_fsb_converted_subscription', $subscription, $fsb_raw_subscription );
}