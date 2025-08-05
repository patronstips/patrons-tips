<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Whether to compute the period retroactively if payment was deferred
 * @since 0.24.0
 * @return bool
 */
function patips_sfw_is_deferred_payment_retroactive() {
	return apply_filters( 'patips_sfw_is_deferred_payment_retroactive', true );
}


/**
 * Convert raw subscription to subscription format
 * @since 0.24.0
 * @param array $sfw_raw_subscription
 * @return array
 */
function patips_sfw_convert_raw_subscription( $sfw_raw_subscription ) {
	$order_item    = isset( $sfw_raw_subscription[ 'order_items' ] ) ? reset( $sfw_raw_subscription[ 'order_items' ] ) : array();
	$order_item_id = isset( $order_item[ 'order_item_id' ] ) ? intval( $order_item[ 'order_item_id' ] ) : 0;
	$total         = isset( $order_item[ 'line_subtotal' ] ) ? floatval( $order_item[ 'line_subtotal' ] ) : 0;
	$total_tax     = isset( $order_item[ 'line_subtotal_tax' ] ) ? floatval( $order_item[ 'line_subtotal_tax' ] ) : 0;
	$variation_id  = isset( $order_item[ 'variation_id' ] ) ? intval( $order_item[ 'variation_id' ] ) : 0;
	$product_id    = isset( $order_item[ 'product_id' ] ) ? intval( $order_item[ 'product_id' ] ) : 0;
	
	$status   = isset( $sfw_raw_subscription[ 'wps_subscription_status' ] ) ? $sfw_raw_subscription[ 'wps_subscription_status' ] : '';
	$interval = isset( $sfw_raw_subscription[ 'wps_sfw_subscription_number' ] ) ? intval( $sfw_raw_subscription[ 'wps_sfw_subscription_number' ] ) : 0;
	$period   = isset( $sfw_raw_subscription[ 'wps_sfw_subscription_interval' ] ) ? sanitize_title_with_dashes( $sfw_raw_subscription[ 'wps_sfw_subscription_interval' ] ) : '';
	if( $period === 'year' ) {
		$interval = $interval * 12;
		$period   = 'month';
	}
	
	// Compute dates
	$utc_dt   = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
	$start_dt = $end_dt = $next_payment_dt = $trial_end_dt = null;
	if( ! empty( $sfw_raw_subscription[ 'wps_schedule_start' ] ) ) {
		$start_dt = clone $utc_dt;
		$start_dt->setTimestamp( $sfw_raw_subscription[ 'wps_schedule_start' ] );
	}
	if( ! empty( $sfw_raw_subscription[ 'wps_susbcription_end' ] ) ) {
		$end_dt = clone $utc_dt;
		$end_dt->setTimestamp( $sfw_raw_subscription[ 'wps_susbcription_end' ] );
	}
	if( ! empty( $sfw_raw_subscription[ 'wps_next_payment_date' ] ) ) {
		$next_payment_dt = clone $utc_dt;
		$next_payment_dt->setTimestamp( $sfw_raw_subscription[ 'wps_next_payment_date' ] );
	}
	if( ! empty( $sfw_raw_subscription[ 'wps_susbcription_trial_end' ] ) ) {
		$trial_end_dt = clone $utc_dt;
		$trial_end_dt->setTimestamp( $sfw_raw_subscription[ 'wps_susbcription_trial_end' ] );
	}
	$created_date_str = ! empty( $sfw_raw_subscription[ 'date_created_gmt' ] ) ? $sfw_raw_subscription[ 'date_created_gmt' ] : '';
	
	// Is retroactive
	$is_deferred_retro = patips_sfw_is_deferred_payment_retroactive();
	$is_deferred       = ! empty( $sfw_raw_subscription[ 'wps_wsp_first_payment_date' ] );
	$is_retroactive    = $is_deferred && $is_deferred_retro;
	
	// Build subscription array
	$subscription = array( 
		'id'             => intval( $sfw_raw_subscription[ 'id' ] ),
		'plugin'         => 'sfw',
		'frequency'      => $interval && $period ? $interval . '_' . $period : '',
		'user_id'        => ! empty( $sfw_raw_subscription[ 'wps_customer_id' ] ) ? intval( $sfw_raw_subscription[ 'wps_customer_id' ] ) : 0,
		'user_email'     => ! empty( $sfw_raw_subscription[ 'billing_email' ] ) ? sanitize_email( $sfw_raw_subscription[ 'billing_email' ] ) : '',
		'product_id'     => $variation_id ? $variation_id : $product_id,
		'order_id'       => ! empty( $sfw_raw_subscription[ 'wps_parent_order' ] ) ? intval( $sfw_raw_subscription[ 'wps_parent_order' ] ) : 0,
		'order_item_id'  => intval( $order_item_id ),
		'price'          => $total + $total_tax,
		'date_created'   => $created_date_str,
		'date_start'     => $start_dt ? $start_dt->format( 'Y-m-d H:i:s' ) : $created_date_str,
		'date_end'       => $end_dt ? $end_dt->format( 'Y-m-d H:i:s' ) : '',
		'date_renewal'   => $next_payment_dt ? $next_payment_dt->format( 'Y-m-d H:i:s' ) : '',
		'date_trial_end' => $trial_end_dt ? $trial_end_dt->format( 'Y-m-d H:i:s' ) : '',
		'deferred'       => $is_deferred ? 1 : 0,
		'retroactive'    => $is_retroactive ? 1 : 0,
		'ignore_parent'  => 0, // The first payment is never deferred, so always take the first payment into account
		'pending_cancel' => 0,
		'active'         => in_array( $status, array( 'active' ), true ) ? 1 : 0
	);
	
	return apply_filters( 'patips_sfw_converted_subscription', $subscription, $sfw_raw_subscription );
}