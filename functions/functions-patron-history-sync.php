<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Sync patron history with WC orders
 * @since 0.13.0
 * @version 0.22.0
 * @param int $patron_id
 */
function patips_wc_sync_patron_history( $patron_id ) {
	$patron = $patron_id ? patips_get_patron_data( $patron_id, array( 'active' => false ) ) : array();
	if( ! $patron ) { return; }
	
	$synced_entry_ids    = array();
	$user_id             = patips_get_patron_user_id( $patron );
	$user_email          = patips_get_patron_user_email( $patron );
	$one_off_product_ids = patips_wc_get_tier_product_ids_by_frequency( array( 'one_off' ) );
	$order_args          = array( 'customer_id' => $user_id, 'billing_email' => $user_email, 'product_ids' => $one_off_product_ids );
	$one_off_orders      = $one_off_product_ids ? patips_wc_get_orders( $order_args ) : array();
	
	// Sync patron history one-off orders
	if( $one_off_orders ) { 
		$synced_entry_ids = patips_wc_sync_patron_history_orders( $patron, $one_off_orders, $one_off_product_ids );
	}
	
	// Hook to sync subscriptions by plugins
	$synced_entry_ids = apply_filters( 'patips_wc_sync_patron_history', $synced_entry_ids, $patron );
	
	// Delete history entries linked to an order that have not been synced
	$entry_ids_to_delete = array();
	foreach( $patron[ 'history' ] as $history_entry ) {
		$order_id        = ! empty( $history_entry[ 'order_id' ] ) ? intval( $history_entry[ 'order_id' ] ) : 0;
		$subscription_id = ! empty( $history_entry[ 'subscription_id' ] ) ? intval( $history_entry[ 'subscription_id' ] ) : 0;
		if( ! $order_id || $subscription_id || in_array( $history_entry[ 'id' ], $synced_entry_ids, true ) ) { continue; }
		
		$entry_ids_to_delete[] = $history_entry[ 'id' ];
	}
	
	if( $entry_ids_to_delete ) {
		patips_delete_patron_history( $patron[ 'id' ], $entry_ids_to_delete, false );
	}
}


/**
 * Sync patron history one-off orders
 * @since 0.13.0
 * @version 0.23.1
 * @param array $patron
 * @param array orders
 * @param array $product_ids
 * @return WC_Order array
 */
function patips_wc_sync_patron_history_orders( $patron, $orders, $product_ids = array() ) {
	$history_entries_to = array( 'keep' => array(), 'add' => array(), 'update' => array() );
	$period_duration    = patips_get_default_period_duration();
	$timezone           = patips_get_wp_timezone();
	
	// Sort patron history entries by order and by item
	$history_by_order = array();
	foreach( $patron[ 'history' ] as $history_entry ) {
		$order_id      = ! empty( $history_entry[ 'order_id' ] ) ? intval( $history_entry[ 'order_id' ] ) : 0;
		$order_item_id = ! empty( $history_entry[ 'order_item_id' ] ) ? intval( $history_entry[ 'order_item_id' ] ) : 0;
		
		if( ! in_array( $order_id, array_keys( $orders ), true ) || ! $order_item_id ) { continue; }
		if( ! empty( $history_by_order[ $order_id ][ $order_item_id ] ) ) { continue; }
		
		if( ! isset( $history_by_order[ $order_id ] ) ) { $history_by_order[ $order_id ] = array(); }
		$history_by_order[ $order_id ][ $order_item_id ] = $history_entry;
	}
	
	// Find the history entries to add or update for each order
	foreach( $orders as $order_id => $order ) {
		$order_items      = $order->get_items();
		$is_active_status = $order->is_paid(); // This actually checks if the order is processing or completed, but not necessarily paid
		$start_dt         = $order->get_date_created( 'edit' );
		$start_dt->setTimezone( $timezone );
		
		// If the order is not paid, no history entry should be created
		if( ! $order->get_date_paid( 'edit' ) ) { continue; }
		
		foreach( $order_items as $order_item_id => $order_item ) {
			$variation_id = intval( $order_item->get_variation_id() );
			$product_id   = $variation_id ? $variation_id : intval( $order_item->get_product_id() );
			if( $product_ids && ! in_array( $product_id, $product_ids, true ) ) { continue; }
			
			$order_item_tier = patips_wc_get_product_tier( $product_id );
			if( ! $order_item_tier ) { continue; }
			
			$period_nb = patips_wc_get_product_period_nb( $product_id );
			if( ! $period_nb ) { continue; }

			$history_entry = isset( $history_by_order[ $order_id ][ $order_item_id ] ) ? $history_by_order[ $order_id ][ $order_item_id ] : array();
			$end_dt = patips_compute_period_end( $start_dt, $period_nb * $period_duration );
			$period = patips_get_period_by_date( $start_dt->format( 'Y-m-d H:i:s' ), $period_duration, $end_dt );
			
			$new_entry = apply_filters( 'patips_wc_sync_patron_history_entry_order', array(
				'patron_id'       => $patron[ 'id' ],
				'tier_id'         => $order_item_tier[ 'id' ],
				'date_start'      => $start_dt->format( 'Y-m-d' ),
				'date_end'        => $end_dt->format( 'Y-m-d' ),
				'period_start'    => substr( $period[ 'start' ], 0, 10 ),
				'period_end'      => substr( $period[ 'end' ], 0, 10 ),
				'period_nb'       => $period[ 'count' ],
				'period_duration' => $period[ 'duration' ],
				'order_id'        => $order_id,
				'order_item_id'   => $order_item_id,
				'active'          => $is_active_status
			), $history_entry, $patron, $order, $order_item );
			
			// Update the entry
			if( $history_entry ) {
				if( array_intersect_key( $history_entry, $new_entry ) != $new_entry ) {
					$history_entries_to[ 'update' ][ $history_entry[ 'id' ] ] = array_merge( $history_entry, $new_entry );
				}
				$history_entries_to[ 'keep' ][] = $history_entry[ 'id' ];
			}
			// Create a new entry
			else {
				$history_entries_to[ 'add' ][] = $new_entry;
			}
		}
	}
	
	$history_entries_to = apply_filters( 'patips_wc_sync_patron_history_orders', $history_entries_to, $patron, $orders, $product_ids );
	
	// Create new history entries
	if( $history_entries_to[ 'add' ] ) {
		$history_entries_to_add = patips_sanitize_patron_history_data( $history_entries_to[ 'add' ], $patron[ 'id' ] );
		if( $history_entries_to_add ) {
			patips_insert_patron_history( $patron[ 'id' ], $history_entries_to_add );
		}
	}
	
	// Update existing history entries
	if( $history_entries_to[ 'update' ] ) {
		$history_entries_to_update = patips_sanitize_patron_history_data( $history_entries_to[ 'update' ], $patron[ 'id' ] );
		if( $history_entries_to_update ) {
			patips_update_existing_patron_history( $patron[ 'id' ], $history_entries_to_update );
		}
	}
	
	return array_merge( $history_entries_to[ 'keep' ], array_keys( $history_entries_to[ 'update' ] ) );
}


/**
 * Sync patron history subscriptions
 * @since 0.23.0 (was patips_wcs_sync_patron_history_subscriptions)
 * @version 0.24.0
 * @param array $patron
 * @param array $subscriptions
 * @param WC_Order[] $orders
 * @param array $product_ids
 * @param string $plugin
 * @return array
 */
function patips_wc_sync_patron_history_subscriptions( $patron, $subscriptions, $orders, $product_ids = array(), $plugin = '' ) {
	$history_entries_to = array( 'keep' => array(), 'add' => array(), 'update' => array() );
	$period_duration    = patips_get_default_period_duration();
	$timezone           = patips_get_wp_timezone();
	$now_dt             = new DateTime( 'now', $timezone );
	
	// Get subscription parent order ids
	$subscription_id_by_order = array();
	foreach( $subscriptions as $subscription_id => $subscription ) {
		$parent_order_id = intval( $subscription[ 'order_id' ] );
		if( $parent_order_id ) {
			$subscription_id_by_order[ $parent_order_id ] = $subscription_id;
		}
	}
	
	// Find the relevent orders to be synced
	$orders_per_subscription = $switches_per_subscription = array();
	foreach( $orders as $order_id => $order ) {
		// If the order is not paid, no history entry should be created
		if( ! $order->get_date_paid( 'edit' ) ) { continue; }
		
		// Identify what kind of order it is
		$is_switch       = patips_wc_is_subscription_switch_order( $order, $plugin );
		$is_renewal      = patips_wc_is_subscription_renewal_order( $order, $plugin );
		$is_parent       = ! empty( $subscription_id_by_order[ $order_id ] ) ? intval( $subscription_id_by_order[ $order_id ] ) : 0;
		$subscription_id = $is_parent ? $is_parent : ( $is_renewal ? $is_renewal : 0 );
		
		// Sort parent and renewal orders by subscription
		if( $subscription_id ) {
			if( ! isset( $orders_per_subscription[ $subscription_id ] ) ) {
				$orders_per_subscription[ $subscription_id ] = array();
			}
			$orders_per_subscription[ $subscription_id ][ $order_id ] = $order;
		}
		
		// Sort switch orders by subscription
		// Ignore switch orders if no payment was made right away or if payments are retroactive
		// Because in that case, the switch will be automatically taken into account in the next renewal
		if( $is_switch && $order->get_subtotal() != 0 && $order->is_paid() ) {
			$subscription_id = $is_switch;
			if( ! isset( $switches_per_subscription[ $subscription_id ] ) ) {
				$switches_per_subscription[ $subscription_id ] = array();
			}
			$switch_dt      = $order->get_date_created( 'edit' );
			$switch_product = patips_wc_get_order_product( $order );
			$switch_tier    = $switch_product ? patips_wc_get_product_tier( $switch_product->get_id() ) : array();
			if( ! empty( $switch_tier[ 'id' ] ) ) {
				$switches_per_subscription[ $subscription_id ][ $switch_dt->format( 'Y-m-d H:i:s' ) ] = intval( $switch_tier[ 'id' ] );
			}
		}
	}
	
	// Sort patron history entries by order and by item
	$history_by_order = array();
	foreach( $patron[ 'history' ] as $history_entry ) {
		$order_id            = ! empty( $history_entry[ 'order_id' ] ) ? intval( $history_entry[ 'order_id' ] ) : 0;
		$order_item_id       = ! empty( $history_entry[ 'order_item_id' ] ) ? intval( $history_entry[ 'order_item_id' ] ) : 0;
		$subscription_id     = ! empty( $history_entry[ 'subscription_id' ] ) ? intval( $history_entry[ 'subscription_id' ] ) : 0;
		$subscription_plugin = ! empty( $history_entry[ 'subscription_plugin' ] ) ? $history_entry[ 'subscription_plugin' ] : 0;
		
		if( ! $order_id && $subscription_id ) {
			$order_id = $subscription_id;
		}
		
		if( ! in_array( $order_id, array_merge( array_keys( $orders ), array_keys( $subscriptions ) ), true ) ) { continue; }
		if( ! empty( $history_by_order[ $order_id ][ $order_item_id ] ) ) { continue; }
		
		if( ! isset( $history_by_order[ $order_id ] ) ) { $history_by_order[ $order_id ] = array(); }
		$history_by_order[ $order_id ][ $order_item_id ] = $history_entry;
	}
	
	// Backtrace the subscriptions life time and turn it into patron history entries
	foreach( $orders_per_subscription as $subscription_id => $orders ) {
		$subscription = isset( $subscriptions[ $subscription_id ] ) ? $subscriptions[ $subscription_id ] : patips_get_subscription_data( $subscription_id, $plugin );
		if( ! $subscription ) { continue; }
		
		// Get subscription data
		$is_sub_active       = ! empty( $subscription[ 'active' ] );
		$is_retroactive      = ! empty( $subscription[ 'retroactive' ] );
		$ignore_parent       = ! empty( $subscription[ 'ignore_parent' ] );
		$trial_end_dt        = $subscription[ 'date_trial_end' ] ? DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription[ 'date_trial_end' ], new DateTimeZone( 'UTC' ) ) : null;
		$subscription_end_dt = $subscription[ 'date_end' ] ? DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription[ 'date_end' ], new DateTimeZone( 'UTC' ) ) : null;
		$next_payment_dt     = $subscription[ 'date_renewal' ] ? DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription[ 'date_renewal' ], new DateTimeZone( 'UTC' ) ) : null;
		$last_payment_dt     = $subscription[ 'date_created' ] ? DateTime::createFromFormat( 'Y-m-d H:i:s', $subscription[ 'date_created' ], new DateTimeZone( 'UTC' ) ) : null;
		if( $trial_end_dt )        { $trial_end_dt->setTimezone( $timezone ); }
		if( $subscription_end_dt ) { $subscription_end_dt->setTimezone( $timezone ); }
		if( $next_payment_dt )     { $next_payment_dt->setTimezone( $timezone ); }
		if( $last_payment_dt )     { $last_payment_dt->setTimezone( $timezone ); }
		$is_ongoing          = ( $next_payment_dt && $next_payment_dt > $now_dt ) || ( $subscription_end_dt && $subscription_end_dt > $now_dt );
		
		foreach( $orders as $order_id => $order ) {
			$order_items      = $order->get_items();
			$is_active_status = $order->is_paid(); // This actually checks if the order is processing or completed, but not necessarily paid
			
			// Identify what kind of order it is
			$is_renewal = patips_wc_is_subscription_renewal_order( $order, $plugin );
			$is_parent  = ! empty( $subscription_id_by_order[ $order_id ] );
			
			$order_created_dt = $order->get_date_created( 'edit' );
			if( $order_created_dt ) { $order_created_dt->setTimezone( $timezone ); }
			if( $last_payment_dt && $order_created_dt > $last_payment_dt ) { $order_created_dt = clone $last_payment_dt; }
			
			foreach( $order_items as $order_item_id => $order_item ) {
				$variation_id = intval( $order_item->get_variation_id() );
				$product_id   = $variation_id ? $variation_id : intval( $order_item->get_product_id() );
				if( $product_ids && ! in_array( $product_id, $product_ids, true ) ) { continue; }
				
				$order_item_tier = patips_wc_get_product_tier( $product_id );
				if( ! $order_item_tier ) { continue; }
				
				$period_nb = patips_wc_get_product_period_nb( $product_id );
				if( ! $period_nb ) { continue; }
				
				$tier_id  = $order_item_tier[ 'id' ];
				$start_dt = $order->get_date_created( 'edit' );
				$start_dt->setTimezone( $timezone );
				$end_dt   = patips_compute_period_end( $start_dt, $period_nb * $period_duration );
				
				// PARENT ORDER: free trial + the first period if not retroactive
				if( $is_parent ) {
					// Maybe ignore the parent order
					if( $ignore_parent ) { continue; }
					
					if( $trial_end_dt ) {
						$end_dt = $is_retroactive ? clone $trial_end_dt : ( $next_payment_dt ? clone $next_payment_dt : clone $trial_end_dt );
					}
				}
				
				// RENEWAL ORDER: compute the period starting on the renewal date, or ending on that date if payment is retroactive
				if( $is_renewal ) {
					if( $is_retroactive ) {
						$end_dt   = clone $start_dt;
						$start_dt = patips_compute_period_start( $end_dt, $period_nb * $period_duration );
					}
				}
				
				// SWITCH ORDER: update the period tier ID
				if( isset( $switches_per_subscription[ $subscription_id ] ) && ! $is_retroactive ) {
					foreach( $switches_per_subscription[ $subscription_id ] as $datetime => $switch_to_tier_id ) {
						$switch_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $datetime, new DateTimeZone( 'UTC' ) );
						$switch_dt->setTimezone( $timezone );
						if( $start_dt <= $switch_dt && $end_dt >= $switch_dt ) {
							$tier_id = $switch_to_tier_id;
						}
					}
				}
				
				$period        = patips_get_period_by_date( $start_dt->format( 'Y-m-d H:i:s' ), $period_duration, $end_dt->format( 'Y-m-d H:i:s' ) );
				$history_entry = isset( $history_by_order[ $order_id ][ $order_item_id ] ) ? $history_by_order[ $order_id ][ $order_item_id ] : array();
				
				$new_entry = apply_filters( 'patips_wc_sync_patron_history_entry_subscription', array(
					'patron_id'           => $patron[ 'id' ],
					'tier_id'             => $tier_id,
					'date_start'          => $start_dt->format( 'Y-m-d' ),
					'date_end'            => $end_dt->format( 'Y-m-d' ),
					'period_start'        => substr( $period[ 'start' ], 0, 10 ),
					'period_end'          => substr( $period[ 'end' ], 0, 10 ),
					'period_nb'           => $period[ 'count' ],
					'period_duration'     => $period[ 'duration' ],
					'order_id'            => $order_id,
					'order_item_id'       => $order_item_id,
					'subscription_id'     => $subscription_id,
					'subscription_plugin' => $plugin,
					'active'              => $is_active_status
				), $history_entry, $patron, $subscription, $order, $order_item );
				
				// Update the entry
				if( $history_entry ) {
					if( array_intersect_key( $history_entry, $new_entry ) != $new_entry ) {
						$history_entries_to[ 'update' ][ $history_entry[ 'id' ] ] = array_merge( $history_entry, $new_entry );
					}
					$history_entries_to[ 'keep' ][] = $history_entry[ 'id' ];
				}
				// Create a new entry
				else {
					$history_entries_to[ 'add' ][] = $new_entry;
				}
			}
		}
		
		// If payments are retroactive, add a fake entry for the current period
		if( $is_retroactive && $is_sub_active && $is_ongoing ) {
			$subscription_tier = patips_wc_get_product_tier( $subscription[ 'product_id' ] );
			if( ! $subscription_tier ) { continue; }
			
			$period_nb = patips_wc_get_product_period_nb( $subscription[ 'product_id' ] );
			$end_dt    = $next_payment_dt ? clone $next_payment_dt : clone $subscription_end_dt;
			$start_dt  = patips_compute_period_start( $end_dt, $period_nb * $period_duration );
			if( $trial_end_dt && $trial_end_dt > $start_dt ) {
				$start_dt = clone $trial_end_dt;
				if( $start_dt > $end_dt ) { continue; }
			}
			
			// SWITCH ORDER: change the tier ID if a switch has been paid meanwhile
			$tier_id = $subscription_tier[ 'id' ];
			if( isset( $switches_per_subscription[ $subscription_id ] ) ) {
				foreach( $switches_per_subscription[ $subscription_id ] as $datetime => $switch_to_tier_id ) {
					$switch_dt = DateTime::createFromFormat( 'Y-m-d H:i:s', $datetime, new DateTimeZone( 'UTC' ) );
					$switch_dt->setTimezone( $timezone );
					if( $last_payment_dt < $switch_dt ) {
						$tier_id = $switch_to_tier_id;
					}
				}
			}
			
			$period        = patips_get_period_by_date( $start_dt->format( 'Y-m-d H:i:s' ), $period_duration, $end_dt->format( 'Y-m-d H:i:s' ) );
			$history_entry = isset( $history_by_order[ $subscription_id ][ 0 ] ) ? $history_by_order[ $subscription_id ][ 0 ] : array();
			
			$new_entry = apply_filters( 'patips_wc_temp_patron_history_entry_for_retroactive_subscription', array(
				'patron_id'           => $patron[ 'id' ],
				'tier_id'             => $tier_id,
				'date_start'          => $start_dt->format( 'Y-m-d' ),
				'date_end'            => $end_dt->format( 'Y-m-d' ),
				'period_start'        => substr( $period[ 'start' ], 0, 10 ),
				'period_end'          => substr( $period[ 'end' ], 0, 10 ),
				'period_nb'           => $period[ 'count' ],
				'period_duration'     => $period[ 'duration' ],
				'order_id'            => 0,
				'order_item_id'       => 0,
				'subscription_id'     => $subscription_id,
				'subscription_plugin' => $plugin,
				'active'              => 1
			), $history_entry, $patron, $subscription );

			// Update the entry
			if( $history_entry ) {
				if( array_intersect_key( $history_entry, $new_entry ) != $new_entry ) {
					$history_entries_to[ 'update' ][ $history_entry[ 'id' ] ] = array_merge( $history_entry, $new_entry );
				}
				$history_entries_to[ 'keep' ][] = $history_entry[ 'id' ];
			}
			// Create a new entry
			else {
				$history_entries_to[ 'add' ][] = $new_entry;
			}
		}
	}
	
	$history_entries_to = apply_filters( 'patips_wc_sync_patron_history_subscriptions', $history_entries_to, $patron, $subscriptions, $orders, $product_ids );
	
	// Create new history entries
	if( $history_entries_to[ 'add' ] ) {
		$history_entries_to_add = patips_sanitize_patron_history_data( $history_entries_to[ 'add' ], $patron[ 'id' ] );
		if( $history_entries_to_add ) {
			patips_insert_patron_history( $patron[ 'id' ], $history_entries_to_add );
		}
	}
	
	// Update existing history entries
	if( $history_entries_to[ 'update' ] ) {
		$history_entries_to_update = patips_sanitize_patron_history_data( $history_entries_to[ 'update' ], $patron[ 'id' ] );
		if( $history_entries_to_update ) {
			patips_update_existing_patron_history( $patron[ 'id' ], $history_entries_to_update );
		}
	}
	
	return array_merge( $history_entries_to[ 'keep' ], array_keys( $history_entries_to[ 'update' ] ) );
}