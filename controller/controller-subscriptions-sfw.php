<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// LINK SFW TO PATRONS TIPS API

/**
 * Set Subscriptions For WooCommerce as main subscription plugin if active
 * @since 0.24.0
 * @param string $plugin_name
 * @return string
 */
function patips_sfw_controller_subscription_plugin( $plugin_name ) {
	if( patips_is_plugin_active( 'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php' ) ) {
		$plugin_name = 'sfw';
	}
	return $plugin_name;
}
add_filter( 'patips_subscription_plugin', 'patips_sfw_controller_subscription_plugin', 10, 1 );


/**
 * Get SFW subscriptions by ID
 * @since 0.24.0
 * @param array $subscriptions
 * @param array $subscription_ids
 * @return array
 */
function patips_sfw_controller_get_subscriptions( $subscriptions, $subscription_ids ) {
	$sfw_raw_subscriptions = $subscription_ids ? patips_wc_get_raw_subscriptions( $subscription_ids, 'wps_subscriptions' ) : array();
	if( ! $sfw_raw_subscriptions ) { return $subscriptions; }
	
	$raw_subscriptions = array();
	foreach( $sfw_raw_subscriptions as $subscription_id => $sfw_raw_subscription ) {
		$raw_subscriptions[ $subscription_id ] = patips_sfw_convert_raw_subscription( $sfw_raw_subscription );
	}
	
	return $raw_subscriptions;
}
add_filter( 'patips_sfw_subscriptions', 'patips_sfw_controller_get_subscriptions', 10, 2 );


/**
 * Get Subscriptions For WooCommerce view URL
 * @since 0.24.0
 * @param string $subscription_url
 * @param int $subscription_id
 * @return string
 */
function patips_sfw_controller_get_subscription_url( $subscription_url, $subscription_id ) {
	if( ! patips_is_plugin_active( 'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php' ) ) { return $subscription_url; }
	
	$subscription_url = wc_get_endpoint_url( 'show-subscription', $subscription_id, wc_get_page_permalink( 'myaccount' ) );
	
	return $subscription_url;
}
add_filter( 'patips_sfw_subscription_url', 'patips_sfw_controller_get_subscription_url', 10, 2 );


/**
 * Get Subscriptions For WooCommerce view edit URL
 * @since 0.24.0
 * @param string $subscription_url
 * @param int $subscription_id
 * @return string
 */
function patips_sfw_controller_get_subscription_edit_url( $subscription_url, $subscription_id ) {
	if( ! patips_is_plugin_active( 'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php' ) ) { return $subscription_url; }
	
	$subscription_url = admin_url( 'admin.php?page=subscriptions_for_woocommerce_menu&sfw_tab=subscriptions-for-woocommerce-subscriptions-table&wps_order_type=subscription&id=' . $subscription_id );
	
	return $subscription_url;
}
add_filter( 'patips_sfw_subscription_edit_url', 'patips_sfw_controller_get_subscription_edit_url', 10, 2 );


/**
 * Get product SFW Subscription frequency
 * @since 0.24.0
 * @param string $frequency
 * @param WC_Product $product
 * @param int $product_id
 * @return string
 */
function patips_sfw_controller_get_product_subscription_frequency( $frequency, $product, $product_id ) {
	$period   = $product ? $product->get_meta( 'wps_sfw_subscription_interval' ) : '';
	$interval = $product ? $product->get_meta( 'wps_sfw_subscription_number' ) : 0;
	
	if( $period || $interval ) {
		if( $period === 'year' ) {
			$interval = $interval * 12;
			$period   = 'month';
		}
		$frequency = $interval . '_' . $period;
	}
	
	return $frequency;
}
add_filter( 'patips_wc_product_subscription_frequency', 'patips_sfw_controller_get_product_subscription_frequency', 10, 3 );


/**
 * Get product SFW Subscription period duration
 * @since 0.24.0
 * @param string $period_duration
 * @param WC_Product $product
 * @param int $product_id
 * @return int
 */
function patips_sfw_controller_get_product_subscription_period_duration( $period_duration, $product, $product_id ) {
	$period   = $product ? $product->get_meta( 'wps_sfw_subscription_interval' ) : '';
	$interval = $product ? $product->get_meta( 'wps_sfw_subscription_number' ) : 0;
	
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
add_filter( 'patips_wc_product_subscription_period_duration', 'patips_sfw_controller_get_product_subscription_period_duration', 10, 3 );


/**
 * Get product SFW Subscription period number
 * @since 0.24.0
 * @param int $period_nb
 * @param WC_Product $product
 * @param int $product_id
 * @return int
 */
function patips_sfw_controller_get_product_subscription_period_nb( $period_nb, $product, $product_id ) {
	$period_duration = patips_wc_get_product_subscription_period_duration( $product_id );
	return floor( $period_duration / patips_get_default_period_duration() );
}
add_filter( 'patips_wc_product_period_nb', 'patips_sfw_controller_get_product_subscription_period_nb', 10, 3 );


/**
 * Check if a cart item is a Subscriptions For WooCommerce switch
 * @since 0.24.0
 * @param boolean $true
 * @param array $cart_item
 * @return boolean
 */
function patips_sfw_controller_is_cart_item_subscription_switch( $true, $cart_item ) {
	if( ! patips_is_plugin_active( 'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php' ) ) { return $true; }
	
	return ! empty( $cart_item[ 'wps_upgrade_downgrade_data' ] );
}
add_filter( 'patips_sfw_is_cart_item_subscription_switch', 'patips_sfw_controller_is_cart_item_subscription_switch', 10, 2 );


/**
 * Check if an order is a Subscriptions For WooCommerce switch
 * @since 0.24.0
 * @param boolean $true
 * @param array $order
 * @return int|false Subscription ID
 */
function patips_sfw_controller_is_subscription_switch_order( $true, $order ) {
	$is_switch       = $order->get_meta( 'wps_upgrade_downgrade_order', true, 'edit' ) === 'yes';
	$subscription_id = $order->get_meta( 'wps_subscription_id', true, 'edit' );
	return $is_switch && $subscription_id ? intval( $subscription_id ) : false;
}
add_filter( 'patips_sfw_is_subscription_switch_order', 'patips_sfw_controller_is_subscription_switch_order', 10, 2 );


/**
 * Check if an order is a Subscriptions For WooCommerce renewal
 * @since 0.24.0
 * @param boolean $true
 * @param array $order
 * @return int|false Subscription ID
 */
function patips_sfw_controller_is_subscription_renewal_order( $true, $order ) {
	$is_renewal      = $order->get_meta( 'wps_sfw_renewal_order', true, 'edit' ) === 'yes';
	$subscription_id = $order->get_meta( 'wps_sfw_subscription', true, 'edit' );
	return $is_renewal && $subscription_id ? intval( $subscription_id ) : false;
}
add_filter( 'patips_sfw_is_subscription_renewal_order', 'patips_sfw_controller_is_subscription_renewal_order', 10, 2 );




// SYNC PATRON HISTORY

/**
 * Sync patron history with all their SFW subscriptions
 * @since 0.24.0
 * @param array $synced_entry_ids
 * @param array $patron
 * @return array
 */
function patips_sfw_controller_sync_patron_history( $synced_entry_ids, $patron ) {
	// Get patron subscription, renewal and switch orders
	$user_id           = patips_get_patron_user_id( $patron );
	$user_email        = patips_get_patron_user_email( $patron );
	$order_args        = array( 'customer_id' => $user_id, 'billing_email' => $user_email );
	$product_ids       = patips_wc_get_tier_product_ids_by_frequency( array(), array( 'one_off' ) );
	$orders            = $product_ids ? patips_wc_get_orders( array_merge( $order_args, array( 'product_ids' => $product_ids ) ) ) : array();
	$subscription_ids  = $product_ids ? patips_wc_get_orders( array_merge( $order_args, array( 'type' => array( 'wps_subscriptions' ), 'return_ids' => true ) ) ) : array();
	$subscriptions     = $subscription_ids ? patips_get_subscriptions_data( $subscription_ids, 'sfw' ) : array();
	
	// Sync SFW subscriptions with patronage history
	$recurring_synced_entry_ids = $subscriptions ? patips_wc_sync_patron_history_subscriptions( $patron, $subscriptions, $orders, $product_ids, 'sfw' ) : array();
	
	// Delete history entries linked to a sfw subscription that have not been synced
	$entry_ids_to_delete = array();
	foreach( $patron[ 'history' ] as $history_entry ) {
		$subscription_plugin = ! empty( $history_entry[ 'subscription_plugin' ] ) ? $history_entry[ 'subscription_plugin' ] : '';
		if( $subscription_plugin !== 'sfw' || in_array( $history_entry[ 'id' ], $recurring_synced_entry_ids, true ) ) { continue; }
		
		$entry_ids_to_delete[] = $history_entry[ 'id' ];
	}
	
	if( $entry_ids_to_delete ) {
		patips_delete_patron_history( $patron[ 'id' ], $entry_ids_to_delete, false );
	}
	
	return patips_ids_to_array( array_merge( $synced_entry_ids, $recurring_synced_entry_ids ) );
}
add_filter( 'patips_wc_sync_patron_history', 'patips_sfw_controller_sync_patron_history', 10, 2 );


/**
 * Display SFW label in patron entry origin
 * @since 0.24.0
 * @version 0.25.5
 * @param string $origin
 * @param array $entry
 * @param array $field
 * @param array $patron
 * @return string
 */
function patips_sfw_patron_history_entry_origin( $origin, $entry, $field, $patron ) {
	if( $origin === 'sfw' ) {
		/* translators: This is a plugin name. */
		$origin = esc_html__( 'WP Swings Subscriptions', 'patrons-tips' );
	}
	return $origin;
}
add_filter( 'patips_patron_history_entry_origin', 'patips_sfw_patron_history_entry_origin', 10, 4 );


/**
 * Sync patron history according to SFW subscription status
 * @since 0.24.0
 * @param WPS_Subscription $sfw_subscription
 * @param string $status_to
 * @param string $status_from
 */
function patips_sfw_controller_subscription_status_changed_sync_patron_history( $sfw_subscription, $status_to, $status_from ) {
	$sfw_subscription = is_a( $sfw_subscription, 'WC_Order' ) ? $sfw_subscription : null;
	if( ! $sfw_subscription ) { return; }
	
	$has_tiers = patips_wc_order_has_tiers( $sfw_subscription );
	if( ! $has_tiers ) { return; }

	// Sync patron history only if the subscription turned from active to inactive, or inactive to active
	// and do not sync if the status was "pending", as it is already handled by WooCommcerce order
	$active_status      = array( 'active' );
	$active_has_changed = in_array( $status_from, $active_status, true ) !== in_array( $status_to, $active_status, true );
	if( ! $active_has_changed || $status_from === 'pending' ) { return; }
	
	// Get patron by SFW subscription user ID or email
	$user_id    = $sfw_subscription->get_customer_id();
	$user_email = $sfw_subscription->get_billing_email();
	$patron     = patips_get_patron_by_user_id_or_email( $user_id, $user_email, true );
	$patron_id  = isset( $patron[ 'id' ] ) ? $patron[ 'id' ] : 0;
	
	// Sync patron history
	if( $patron_id ) {
		patips_wc_sync_patron_history( $patron_id );
	}
}
add_action( 'patips_sfw_subscription_status_updated', 'patips_sfw_controller_subscription_status_changed_sync_patron_history', 100, 3 ); 


/**
 * Sync patron history when SFW subscription is deleted
 * @since 0.24.0
 * @param int $subscription_id
 */
function patips_sfw_controller_subscription_deleted_sync_patron_history( $subscription_id ) {
	// Get history entries related the the deleted subscription
	$filters         = patips_format_patron_history_filters( array( 'subscription_ids' => array( $subscription_id ) ) );
	$patrons_history = ! empty( $filters[ 'subscription_ids' ] ) ? patips_get_patrons_history( $filters ) : array();
	
	// Get patron id
	$patron_ids = $patrons_history ? array_keys( $patrons_history ) : array();
	$patron_id  = $patron_ids ? reset( $patron_ids ) : 0;
	
	// Sync patron history
	if( $patron_id ) {
		patips_wc_sync_patron_history( $patron_id );
	}
}
add_action( 'woocommerce_delete_order', 'patips_sfw_controller_subscription_deleted_sync_patron_history', 10, 1 );


/**
 * Monitor SFW subscriptions status changes
 * @since 0.24.0
 * @global array $patips_sfw_subscription_status_transition
 * @param WPS_Subscription $sfw_subscription
 * @param Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore $data_store
 */
function patips_sfw_controller_monitor_subscriptions_status_changes( $sfw_subscription, $data_store ) {
	global $patips_sfw_subscription_status_transition;
	$patips_sfw_subscription_status_transition = array();
	
	foreach( $sfw_subscription->get_meta_data() as $array_key => $meta ) {
		if( $meta->key !== 'wps_subscription_status' ) { continue; }
		
		$current_meta  = $meta->get_data();
		$current_value = ! empty( $current_meta[ 'value' ] ) ? $current_meta[ 'value' ] : '';
		$from          = $current_value;
		$to            = $meta->value;
		
		// Deleted
		if( is_null( $meta->value ) && ! empty( $meta->id ) ) {
			$to = 'deleted';
		}
		
		if( $to && $from !== $to ) {
			$patips_sfw_subscription_status_transition = array(
				'subscription_id' => $sfw_subscription->get_id(),
				'meta_id'         => $meta->id,
				'from'            => $from,
				'to'              => $to,
			);
		}
		break;
	}
}
add_action( 'woocommerce_before_order_object_save', 'patips_sfw_controller_monitor_subscriptions_status_changes', 10, 2 );


/**
 * Trigger hook when a SFW subscription status has changed
 * @since 0.24.0
 * @global array $patips_sfw_subscription_status_transition
 * @param WPS_Subscription $sfw_subscription
 * @param Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore $data_store
 */
function patips_sfw_controller_trigger_subscription_status_change_hook( $sfw_subscription, $data_store ) {
	global $patips_sfw_subscription_status_transition;
	
	if( empty( $patips_sfw_subscription_status_transition ) ) { return; }
	if( $sfw_subscription->get_id() !== $patips_sfw_subscription_status_transition[ 'subscription_id' ] ) { return; }
	
	do_action( 'patips_sfw_subscription_status_updated', $sfw_subscription, $patips_sfw_subscription_status_transition[ 'to' ], $patips_sfw_subscription_status_transition[ 'from' ] );
}
add_action( 'woocommerce_after_order_object_save', 'patips_sfw_controller_trigger_subscription_status_change_hook', 10, 2 );




// SPECIFIC FEATURES

/**
 * Change restricted post content with Subscriptions For WooCommerce data
 * @since 0.24.0
 * @version 0.26.2
 * @param array $args
 * @param WP_Post $post
 * @param array $unlock_tiers
 * @param int $user_id
 * @return array
 */
function patips_sfw_restricted_post_content_args( $args, $post, $unlock_tiers, $user_id ) {
	if( ! patips_is_plugin_active( 'subscriptions-for-woocommerce/subscriptions-for-woocommerce.php' ) ) { return $args; }
	if( ! class_exists( 'Woocommerce_Subscriptions_Pro_Public' ) ) { return $args; }
	
	if( ! $user_id ) { $user_id = get_current_user_id(); }
	$patron   = patips_get_user_patron_data( $user_id );
	$timezone = patips_get_wp_timezone();
	$now_dt   = new DateTime( 'now', $timezone );
	
	// Get patron current subscription url
	$subscription_id  = false;
	$subscription_url = false;
	if( $patron && ! empty( $patron[ 'history' ] ) ) {
		foreach( $patron[ 'history' ] as $history_entry ) {
			if( $history_entry[ 'subscription_plugin' ] !== 'sfw' ) { continue; }
			
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
	
	// Check if the subscription can be switched and get the switch URL
	$is_switch_allowed = false;
	if( $subscription_id ) {
		if( wps_wsp_check_upgrade_downgrade() ) {
			if( wps_sfw_check_valid_subscription( $subscription_id ) ) {
				$product_id = wps_wsp_get_meta_data( $subscription_id, 'product_id', true );
				$product    = wc_get_product( $product_id );
				if( wps_sfw_check_variable_product_is_subscription( $product ) ) {
					$is_switch_allowed = true;
					$product_url       = get_permalink( $product_id );
					$switch_url        = Woocommerce_Subscriptions_Pro_Public::wps_wsp_get_upgrade_downgrade_url( $subscription_id, $product_id, $product_url );
				}
			}
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
add_filter( 'patips_restricted_post_content_args', 'patips_sfw_restricted_post_content_args', 20, 4 );