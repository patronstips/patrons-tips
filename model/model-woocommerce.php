<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// TIERS

/**
 * Get tiers product ids by frequency
 * @since 0.22.0 (was patips_get_tiers_product_ids)
 * @version 0.26.0
 * @global wpdb $wpdb
 * @param array $tier_ids
 * @return array
 */
function patips_wc_get_tiers_product_ids( $tier_ids = array() ) {
	global $wpdb;
	
	$query = 'SELECT TP.tier_id, TP.product_id, TP.frequency, TP.is_default '
	       . ' FROM ' . PATIPS_TABLE_TIERS_PRODUCTS . ' as TP '
	       . ' WHERE TRUE ';
	
	$variables = array();
	
	if( $tier_ids ) {
		$query .= ' AND TP.tier_id IN ( %d';
		for( $i=1,$len=count( $tier_ids ); $i < $len; ++$i ) {
			$query .= ', %d';
		}
		$query .= ' ) ';
		$variables = $tier_ids;
	}
	
	$query .= ' ORDER BY TP.tier_id ASC, TP.frequency ASC, TP.is_default DESC';
	
	if( $variables ) { 
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$return_array = array();
	if( $results ) {
		foreach( $results as $result ) {
			$tier_id = intval( $result->tier_id );

			if( ! isset( $return_array[ $tier_id ] ) ) { 
				$return_array[ $tier_id ] = array();
			}
			if( ! isset( $return_array[ $tier_id ][ $result->frequency ] ) ) { 
				$return_array[ $tier_id ][ $result->frequency ] = array();
			}

			$return_array[ $tier_id ][ $result->frequency ][] = intval( $result->product_id );
		}
	}
	
	foreach( $return_array as $tier_id => $product_ids_by_freq ) {
		$return_array[ $tier_id ] = array_filter( array_map( 'patips_ids_to_array', $product_ids_by_freq ) );
	}
	
	return $return_array;
}


/**
 * Insert tier product ids by frequency
 * @since 0.22.0 (was patips_insert_tier_product_ids)
 * @version 0.26.0
 * @global wpdb $wpdb
 * @param int $tier_id
 * @param array $product_ids_by_freq
 * @return int|false
 */
function patips_wc_insert_tier_product_ids( $tier_id, $product_ids_by_freq ) {
	if( empty( $product_ids_by_freq ) || ! is_array( $product_ids_by_freq ) || ! $tier_id ) {
		return false;
	}
	
	global $wpdb;

	$query = 'INSERT INTO ' . PATIPS_TABLE_TIERS_PRODUCTS 
	       . ' ( tier_id, product_id, frequency, is_default ) '
	       . ' VALUES ';

	$i = 0;
	$variables = array();
	foreach( $product_ids_by_freq as $frequency => $product_ids ) {
		foreach( $product_ids as $product_id ) {
			if( $i > 0 ) { $query .= ','; }
			$query .= ' ( %d, %d, %s, NULL ) ';
			$variables[] = $tier_id;
			$variables[] = $product_id;
			$variables[] = $frequency;
			$i++;
		}
	}
	
	$inserted = 0;
	if( $i > 0 ) {
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$inserted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	return $inserted;
}


/**
 * Delete tier product ids
 * @since 0.22.0 (was patips_delete_tier_product_ids)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $tier_id
 * @param array $product_ids_by_freq
 * @return int|false
 */
function patips_wc_delete_tier_product_ids( $tier_id, $product_ids_by_freq ) {
	$variables = array( $tier_id );
	global $wpdb;
	
	$query = 'DELETE FROM ' . PATIPS_TABLE_TIERS_PRODUCTS 
	       . ' WHERE tier_id = %d '
	       . ' AND (';
	
	$variables = array( $tier_id );
	
	$i = 0;
	foreach( $product_ids_by_freq as $frequency => $product_ids ) {
		foreach( $product_ids as $product_id ) {
			if( $i > 0 ) { $query .= ' OR'; }
			$query .= ' ( product_id = %d AND frequency = %s )';
			$variables[] = $product_id;
			$variables[] = $frequency;
			$i++;
		}
	}
	
	$query .= ' )';
	
	$deleted = 0;
	if( $i > 0 ) {
		$query   = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$deleted = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	return $deleted;
}


/**
 * Set default tier product id by frequency
 * @since 0.26.0
 * @global wpdb $wpdb
 * @param int $tier_id
 * @param array $product_ids_by_freq
 * @return int|false
 */
function patips_wc_set_tier_product_ids_as_default( $tier_id, $product_ids_by_freq ) {
	if( empty( $product_ids_by_freq ) || ! is_array( $product_ids_by_freq ) || ! $tier_id ) {
		return false;
	}
	
	global $wpdb;

	$query = 'UPDATE ' . PATIPS_TABLE_TIERS_PRODUCTS . ' SET '
	       . ' is_default = IF( ';
	
	$i = 0;
	$variables = array();
	foreach( $product_ids_by_freq as $frequency => $product_id ) {
		if( $i > 0 ) { 
			$query .= ' OR';
		}
		
		$query .= ' ( frequency = %s AND product_id = %d )';
		$variables[] = $frequency;
		$variables[] = $product_id;
		
		++$i;
	}
	
	$query .= ', 1, NULL ) ';
	
	$query .= 'WHERE tier_id = %d;';
	$variables[] = $tier_id;
	
	$updated = 0;
	if( $i > 0 ) {
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$updated = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	return $updated;
}




// PRODUCTS

/**
 * Get array of woocommerce products and variations titles ordered by ids
 * @since 0.5.0
 * @version 1.0.2
 * @global wpdb $wpdb
 * @param array $raw_args
 * @return array
 */
function patips_wc_get_product_titles( $raw_args = array() ) {
	global $wpdb;
	
	$default_args = array(
		'search'    => '',
		'status'    => array( 'draft', 'pending', 'private', 'publish', 'future' ),
		'include'   => array(),
		'frequency' => array()
	);
	$args = wp_parse_args( $raw_args, $default_args );
	
	if( is_numeric( $args[ 'search' ] ) ) {
		$args[ 'include' ] = patips_ids_to_array( array_merge( $args[ 'include' ], array( intval( $args[ 'search' ] ) ) ) );
		$args[ 'search' ]  = '';
	}
	
	if( ! $args[ 'status' ] ) {
		$args[ 'status' ] = $default_args[ 'status' ];
	}
	
	// Try to retrieve the product array from cache
	$products_array = ! $args[ 'search' ] || $args[ 'include' ] ? wp_cache_get( 'products_titles', 'patips_wc' ) : array();
	if( $products_array ) { 
		if( ! $args[ 'include' ] ) { return $products_array; }
		else {
			foreach( $products_array as $product_id => $product ) {
				if( in_array( $product_id, $args[ 'include' ], true ) ) {
					continue;
				}

				if( ! empty( $product[ 'variations' ] ) ) { 
					$products_array[ $product_id ][ 'variations' ] = array_intersect_key( $product[ 'variations' ], array_flip( $args[ 'include' ] ) );
					if( $products_array[ $product_id ][ 'variations' ] ) {
						continue;
					}
				}

				unset( $products_array[ $product_id ] );
			}
			return $products_array;
		}
	}
	
	// Retrieve product array from database
	else {
		$products_array = array();
		$variables      = array();
		
		$query	= 'SELECT DISTINCT P.ID as id, P.post_title as title, P.post_excerpt as variations_title, P.post_type, T.slug as product_type, P.post_parent as parent FROM ' . $wpdb->posts . ' as P '
				. ' LEFT JOIN ' . $wpdb->term_relationships . ' as TR ON TR.object_id = P.ID '
				. ' LEFT JOIN ' . $wpdb->term_taxonomy . ' as TT ON TT.term_taxonomy_id = TR.term_taxonomy_id AND TT.taxonomy = "product_type" '
				. ' LEFT JOIN ' . $wpdb->terms . ' as T ON T.term_id = TT.term_id '
				. ' WHERE ( ( P.post_type = "product" AND T.name IS NOT NULL ) OR P.post_type = "product_variation" )';
		
		// Filter by status
		$status_placeholders = '';
		$query .= ' AND P.post_status IN (';
		for( $i = 0; $i < count( $args[ 'status' ] ); $i++ ) {
			$status_placeholders .= ' %s';
			if( $i < ( count( $args[ 'status' ] ) - 1 ) ) {
				$status_placeholders .= ',';
			}
		}
		$query .= $status_placeholders . ' ) ';
		$variables = array_merge( $variables, $args[ 'status' ] );
		
		// Filter by search term
		if( $args[ 'search' ] ) {
			$search_conditions = 'P.post_title LIKE %s OR ( P.post_type = "product_variation" AND P.post_excerpt LIKE %s )';

			// Include the variations' parents so the user knows to what product it belongs
			$parent_ids_query = 'SELECT P.post_parent FROM ' . $wpdb->posts . ' as P '
			                  . 'WHERE P.post_type = "product_variation" '
			                  . 'AND ' . $search_conditions
			                  . 'AND P.post_status IN (' . $status_placeholders . ' ) ';
			
			$query .= ' AND ( ' . $search_conditions . ' OR P.ID IN ( ' . $parent_ids_query . ' ) )';

			$sanitized_search = '%' . $wpdb->esc_like( $args[ 'search' ] ) . '%';
			
			$variables = array_merge( $variables, array( $sanitized_search, $sanitized_search, $sanitized_search, $sanitized_search ), $args[ 'status' ] );
		}
		
		// Filter by product ID
		if( $args[ 'include' ] ) {
			$include_condition = 'P.ID IN (';
			for( $i = 0; $i < count( $args[ 'include' ] ); $i++ ) {
				$include_condition .= ' %d';
				if( $i < ( count( $args[ 'include' ] ) - 1 ) ) {
					$include_condition .= ',';
				}
			}
			$include_condition .= ' ) ';
			
			// Include the variations' parents so the user knows to what product it belongs
			$parent_ids_query = 'SELECT P.post_parent FROM ' . $wpdb->posts . ' as P '
			                  . 'WHERE P.post_type = "product_variation" '
			                  . 'AND ' . $include_condition
			                  . 'AND P.post_status IN (' . $status_placeholders . ' ) ';
			
			$query .= ' AND ( ' . $include_condition . ' OR P.ID IN ( ' . $parent_ids_query . ' ) )';

			$variables = array_merge( $variables, $args[ 'include' ], $args[ 'include' ], $args[ 'status' ] );
		}
		
		if( $variables ) {
			$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		
		$query = apply_filters( 'patips_wc_product_titles_query', $query, $args );
		
		$results = $wpdb->get_results( $query, OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		
		if( $results ) {
			foreach( $results as $result ) {
				// Remove auto-draft entries
				if( strpos( $result->title, 'AUTO-DRAFT' ) !== false ) { continue; }

				if( $result->post_type !== 'product_variation' ){
					if( ! isset( $products_array[ $result->id ] ) ) { $products_array[ $result->id ] = array(); }
					$products_array[ $result->id ][ 'title' ] = $result->title;
					$products_array[ $result->id ][ 'type' ] = $result->product_type;
				}
				else {
					if( ! isset( $products_array[ $result->parent ][ 'variations' ] ) ) { $products_array[ $result->parent ][ 'variations' ] = array(); }
					$products_array[ $result->parent ][ 'variations' ][ $result->id ][ 'title' ] = $result->variations_title ? $result->variations_title : $result->id;
				}
			}

			// Remove variations if their parent variable product doesn't exist
			foreach( $products_array as $product_id => $product ) {
				if( ! isset( $product[ 'type' ] ) ) { unset( $products_array[ $product_id ] ); }
			}
		}

		if( ! $args[ 'search' ] && ! $args[ 'include' ] ) {
			wp_cache_set( 'products_titles', $products_array, 'patips_wc' );
		}
	}
	
	return $products_array;
}




// ORDERS

/**
 * Get WC orders
 * @since 0.13.0
 * @version 0.25.5
 * @param array $raw_args
 * @return WC_Order[]
 */
function patips_wc_get_orders( $raw_args = array() ) {
	global $wpdb;
	
	$default_args = array(
		'product_ids'            => array(),
		'type'                   => array( 'shop_order' ),
		'status'                 => array(), // 'completed', 'processing', 'on-hold', etc.
		'not_in__status'         => array( 'draft', 'checkout-draft', 'trash' ),
		'customer_id'            => 0,
		'billing_email'          => '',
		'not_in__customer_id'    => array(),
		'not_in__billing_email'  => array(),
		'return_ids'             => false
	);
	$args = wp_parse_args( $raw_args, $default_args );
	$args[ 'status' ]         = preg_filter( '/^/', 'wc-', $args[ 'status' ] );
	$args[ 'not_in__status' ] = preg_filter( '/^/', 'wc-', $args[ 'not_in__status' ] );
	if( ! $args[ 'type' ] ) { $args[ 'type' ] = array( 'shop_order' ); }
	
	$variables = array();
	
	$query = 'SELECT DISTINCT O.id'
	       . ' FROM ' . $wpdb->prefix . 'wc_orders as O'
	       . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_items as I ON I.order_id = O.id';
	
	if( $args[ 'product_ids' ] ) {
		// Do not use wc_order_product_lookup table because it may not be up to date
		$query .= ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta as IM ON I.order_item_id = IM.order_item_id AND IM.meta_key IN ( "_product_id", "_variation_id" )';
	}
	
	$query .= ' WHERE TRUE';
	
	if( $args[ 'type' ] ) {
		$query .= ' AND O.type IN ( %s';
		$array_count = count( $args[ 'type' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'type' ] );
	}
	
	if( $args[ 'status' ] ) {
		$query .= ' AND O.status IN ( %s';
		$array_count = count( $args[ 'status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'status' ] );
	}
	
	if( $args[ 'not_in__status' ] ) {
		$query .= ' AND O.status NOT IN ( %s';
		$array_count = count( $args[ 'not_in__status' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'not_in__status' ] );
	}
	
	if( $args[ 'not_in__customer_id' ] ) {
		$query .= ' AND O.customer_id NOT IN ( %d';
		$array_count = count( $args[ 'not_in__customer_id' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'not_in__customer_id' ] );
	}
	
	
	if( $args[ 'not_in__billing_email' ] ) {
		$query .= ' AND O.billing_email NOT IN ( %s';
		$array_count = count( $args[ 'not_in__billing_email' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %s';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'not_in__billing_email' ] );
	}
	
	if( $args[ 'customer_id' ] || $args[ 'billing_email' ] ) {
		$query .= ' AND (';
		if( $args[ 'customer_id' ] ) {
			$query .= ' O.customer_id = %d';
			$variables[] = $args[ 'customer_id' ];
		}
		if( $args[ 'billing_email' ] ) {
			$query .= $args[ 'customer_id' ] ? ' OR' : '';
			$query .= ' O.billing_email = %s';
			$variables[] = $args[ 'billing_email' ];
		}
		$query .= ' ) ';
	}
	
	if( $args[ 'product_ids' ] ) {
		$query .= ' AND IM.meta_value IN ( %d';
		$array_count = count( $args[ 'product_ids' ] );
		if( $array_count >= 2 ) {
			for( $i=1; $i<$array_count; ++$i ) {
				$query .= ', %d';
			}
		}
		$query .= ' ) ';
		$variables = array_merge( $variables, $args[ 'product_ids' ] );
	}
	
	$query .= ' ORDER BY O.id ASC';
	
	
	// Convert the query for non-HPOS compatibility
	$hpos_enabled = patips_wc_is_hpos_enabled();
	if( ! $hpos_enabled ) {
		$variables = array();
		
		$query = 'SELECT DISTINCT P.ID'
		       . ' FROM ' . $wpdb->prefix . 'posts as P'
		       . ' JOIN ' . $wpdb->prefix . 'woocommerce_order_items as I ON I.order_id = P.ID';
		
		if( $args[ 'product_ids' ] ) {
			$query .= ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta as IM ON I.order_item_id = IM.order_item_id AND IM.meta_key IN ( "_product_id", "_variation_id" )';
		}
		
		if( $args[ 'billing_email' ] || $args[ 'not_in__billing_email' ] ) {
			$query .= ' LEFT JOIN ' . $wpdb->prefix . 'postmeta as PM ON PM.post_id = P.ID AND PM.meta_key = "_billing_email"';
		}
		
		$query .= ' WHERE TRUE';
		
		if( $args[ 'type' ] ) {
			$query .= ' AND P.post_type IN ( %s';
			$array_count = count( $args[ 'type' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %s';
				}
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $args[ 'type' ] );
		}
		
		if( $args[ 'status' ] ) {
			$query .= ' AND P.post_status IN ( %s';
			$array_count = count( $args[ 'status' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %s';
				}
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $args[ 'status' ] );
		}
		
		if( $args[ 'not_in__status' ] ) {
			$query .= ' AND P.post_status NOT IN ( %s';
			$array_count = count( $args[ 'not_in__status' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %s';
				}
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $args[ 'not_in__status' ] );
		}
		
		if( $args[ 'not_in__customer_id' ] ) {
			$query .= ' AND P.post_author NOT IN ( %d';
			$array_count = count( $args[ 'not_in__customer_id' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d';
				}
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $args[ 'not_in__customer_id' ] );
		}

		if( $args[ 'not_in__billing_email' ] ) {
			$query .= ' AND PM.meta_value NOT IN ( %s';
			$array_count = count( $args[ 'not_in__billing_email' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %s';
				}
			}
			$query .= ' ) ';
			$variables = array_merge( $variables, $args[ 'not_in__billing_email' ] );
		}
	
		if( $args[ 'customer_id' ] || $args[ 'billing_email' ] ) {
			$query .= ' AND (';
			if( $args[ 'customer_id' ] ) {
				$query .= ' P.post_author = %d';
				$variables[] = $args[ 'customer_id' ];
			}
			if( $args[ 'billing_email' ] ) {
				$query .= $args[ 'customer_id' ] ? ' OR' : '';
				$query .= ' PM.meta_value = %s';
				$variables[] = $args[ 'billing_email' ];
			}
			$query .= ' )';
		}
		
		if( $args[ 'product_ids' ] ) {
			$query .= ' AND IM.meta_value IN ( %d';
			$array_count = count( $args[ 'product_ids' ] );
			if( $array_count >= 2 ) {
				for( $i=1; $i<$array_count; ++$i ) {
					$query .= ', %d';
				}
			}
			$query .= ' )';
			$variables = array_merge( $variables, $args[ 'product_ids' ] );
		}
		
		$query .= ' ORDER BY I.order_id ASC';
	}
	
	
	if( $variables ) {
		$query = $wpdb->prepare( $query, $variables ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
	
	$query = apply_filters( 'patips_wc_get_orders_query', $query, $args );
	
	$order_ids = (array) $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$orders = array();
	if( ! $args[ 'return_ids' ] ) {
		foreach( $order_ids as $order_id ) {
			$orders[ $order_id ] = wc_get_order( $order_id );
		}
	} else {
		$orders = $order_ids;
	}
	
	return $orders;
}


/**
 * Get subscriptions based on WC orders from database
 * @since 0.23.0 (was patips_wcs_get_raw_subscriptions)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $subscription_ids
 * @param string $type
 * @return array
 */
function patips_wc_get_raw_subscriptions( $subscription_ids, $type = 'shop_subscription' ) {
	$raw_subscriptions = array();
	
	// Get subscriptions from cache
	foreach( $subscription_ids as $i => $subscription_id ) {
		$cache = wp_cache_get( 'wc_raw_subscription_' . $subscription_id, 'patrons-tips' );
		if( $cache !== false ) {
			if( $cache ) {
				$raw_subscriptions[ $subscription_id ] = $cache;
			}
			unset( $subscription_ids[ $i ] );
		}
	}
	if( ! $subscription_ids ) {
		return $raw_subscriptions;
	}
	
	global $wpdb;
	
	// IDs placeholders
	$placeholders = '%d';
	$array_count = count( $subscription_ids );
	if( $array_count >= 2 ) {
		for( $i=1; $i<$array_count; ++$i ) {
			$placeholders .= ', %d';
		}
	}
	
	// Get subscription data
	$query = 'SELECT * FROM ' . $wpdb->prefix . 'wc_orders as O'
	       . ' WHERE O.type = %s'
	       . ' AND O.id IN ( ' . $placeholders . ' ) ';
	
	$hpos_enabled = patips_wc_is_hpos_enabled();
	if( ! $hpos_enabled ) {
		$query = 'SELECT P.ID as id, P.post_status as status, P.post_type as type, P.post_parent as parent_order_id, P.post_date_gmt as date_created_gmt, P.post_modified_gmt as date_updated_gmt FROM ' . $wpdb->posts . ' as P'
	           . ' WHERE P.post_type = %s'
	           . ' AND P.ID IN ( ' . $placeholders . ' ) ';
	}
	
	$query = $wpdb->prepare( $query, array_merge( array( $type ), $subscription_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	if( $results ) {
		foreach( $results as $result ) {
			$raw_subscriptions[ intval( $result[ 'id' ] ) ] = $result;
		}
	}
	
	// Get subscriptions meta
	$query = 'SELECT M.order_id, M.meta_key, M.meta_value FROM ' . $wpdb->prefix . 'wc_orders_meta as M'
	       . ' WHERE M.order_id IN( ' . $placeholders . ' ) ';
	
	$hpos_enabled = patips_wc_is_hpos_enabled();
	if( ! $hpos_enabled ) {
		$query = 'SELECT M.post_id as order_id, M.meta_key, M.meta_value FROM ' . $wpdb->postmeta . ' as M'
	       . ' WHERE M.post_id IN ( ' . $placeholders . ' ) ';
	}
	
	$query = $wpdb->prepare( $query, $subscription_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	if( $results ) {
		$key_map = array(
			'customer_user'       => 'customer_id',
			'customer_user_agent' => 'user_agent',
			'customer_ip_address' => 'ip_address',
			'order_currency'      => 'currency',
			'order_total'         => 'total_amount',
			'order_tax'           => 'tax_amount',
		);
		
		foreach( $results as $result ) {
			$order_id = intval( $result->order_id );
			if( ! isset( $raw_subscriptions[ $order_id ] ) ) { continue; }
			
			$meta_key = ltrim( $result->meta_key, '_' );
			if( isset( $key_map[ $meta_key ] ) ) {
				$meta_key = $key_map[ $meta_key ];
			}
			$raw_subscriptions[ $order_id ][ $meta_key ] = $result->meta_value;
		}
	}
	
	// Get order items data
	$orders_items = patips_wc_get_orders_items_meta( $subscription_ids );
	
	foreach( $subscription_ids as $subscription_id ) {
		$raw_subscription = isset( $raw_subscriptions[ $subscription_id ] ) ? $raw_subscriptions[ $subscription_id ] : array();
		
		if( $raw_subscription ) {
			$raw_subscription[ 'order_items' ] = ! empty( $orders_items[ $subscription_id ] ) ? $orders_items[ $subscription_id ] : array();
			$raw_subscriptions[ $subscription_id ] = $raw_subscription;
		}
		
		wp_cache_set( 'wc_raw_subscription_' . $subscription_id, $raw_subscription, 'patrons-tips' );
	}
	
	return $raw_subscriptions;
}


/**
 * Get WC orders items meta from database
 * @since 0.23.0 (was patips_wcs_get_subscriptions_order_items_meta)
 * @version 0.25.5
 * @global wpdb $wpdb
 * @param array $order_ids
 * @return array
 */
function patips_wc_get_orders_items_meta( $order_ids ) {
	global $wpdb;
	
	$query = 'SELECT I.order_id, IM.order_item_id, IM.meta_key, IM.meta_value FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta as IM'
	       . ' JOIN ' . $wpdb->prefix . 'woocommerce_order_items as I ON I.order_item_id = IM.order_item_id'
	       . ' WHERE I.order_item_type = "line_item"'
	       . ' AND I.order_id IN ( %d';
	
	$array_count = count( $order_ids );
	if( $array_count >= 2 ) {
		for( $i=1; $i<$array_count; ++$i ) {
			$query .= ', %d';
		}
	}
	
	$query .= ' )';
	
	$query   = $wpdb->prepare( $query, $order_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	
	$orders_items = array();
	if( $results ) {
		foreach( $results as $result ) {
			$order_id      = intval( $result->order_id );
			$order_item_id = intval( $result->order_item_id );
			$meta_key      = ltrim( sanitize_title_with_dashes( $result->meta_key ), '_' );
			$meta_value    = intval( $result->meta_value );
			
			if( ! isset( $orders_items[ $order_id ] ) )                   { $orders_items[ $order_id ] = array(); }
			if( ! isset( $orders_items[ $order_id ][ $order_item_id ] ) ) { $orders_items[ $order_id ][ $order_item_id ] = array( 'order_item_id' => $order_item_id ); }
			
			$orders_items[ $order_id ][ $order_item_id ][ $meta_key ] = $meta_value;
		}
	}
	
	return $orders_items;
}