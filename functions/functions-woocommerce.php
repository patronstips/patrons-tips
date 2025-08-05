<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GENERAL

/**
 * Check if WC HPOS is enabled
 * @since 0.3.0
 * @return boolean
 */
function patips_wc_is_hpos_enabled() {
	$hpos_enabled = false;
	if( method_exists( 'Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) {
		$hpos_enabled = Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
	return $hpos_enabled;
}


/**
 * Display a product selectbox
 * @since 0.5.0
 * @version 1.0.2
 * @param array $raw_args
 * @return string
 */
function patips_wc_display_product_selectbox( $raw_args = array() ) {
	$defaults = array(
		'name'        => 'product_id',
		'selected'    => array(),
		'status'      => array( 'draft', 'pending', 'private', 'publish', 'future' ),
		'include'     => array(),
		'frequency'   => array(),
		'id'          => '',
		'class'       => '',
		'allow_tags'  => 0,
		'allow_clear' => 1,
		'ajax'        => 1,
		'select2'     => 1,
		'sortable'    => 0,
		'multiple'    => 0,
		'disabled'    => 0,
		'echo'        => 1,
		'placeholder' => esc_html__( 'Search...', 'patrons-tips' )
	);
	$args = apply_filters( 'patips_wc_product_selectbox_args', wp_parse_args( $raw_args, $defaults ), $raw_args );

	$products_titles = ! $args[ 'ajax' ] ? patips_wc_get_product_titles( $args ) : array();
	
	// Format selected product ids
	if( ! is_array( $args[ 'selected' ] ) ) { $args[ 'selected' ] = $args[ 'selected' ] || in_array( $args[ 'selected' ], array( 0, '0' ), true ) ? array( $args[ 'selected' ] ) : array(); }
	$selected_product_ids           = patips_ids_to_array( $args[ 'selected' ] );
	$remaining_selected_product_ids = $selected_product_ids;
	$selected_options               = array();
	
	if( $args[ 'ajax' ] && $args[ 'selected' ] ) {
		$selected_products_titles = $selected_product_ids ? patips_wc_get_product_titles( array( 'include' => $selected_product_ids ) ) : array();
		if( $selected_products_titles ) { 
			$products_titles = $selected_products_titles;
		}
	}
	
	if( $args[ 'multiple' ] && strpos( $args[ 'name' ], '[]' ) === false ) { 
		$args[ 'name' ] .= '[]';
	}
	
	if( $args[ 'ajax' ] )         { $args[ 'class' ] .= ' patips-select2-ajax'; }
	else if( $args[ 'select2' ] ) { $args[ 'class' ] .= ' patips-select2-no-ajax'; }
	
	ob_start();
	?>
	<select <?php if( $args[ 'id' ] ) { echo 'id="' . esc_attr( $args[ 'id' ] ) . '"'; } ?> 
		name='<?php echo esc_attr( trim( $args[ 'name' ] ) ); ?>' 
		class='patips-wc-products-selectbox <?php echo esc_attr( $args[ 'class' ] ); ?>'
		data-tags='<?php echo ! empty( $args[ 'allow_tags' ] ) ? 1 : 0; ?>'
		data-allow-clear='<?php echo ! empty( $args[ 'allow_clear' ] ) ? 1 : 0; ?>'
		data-placeholder='<?php echo ! empty( $args[ 'placeholder' ] ) ? esc_attr( $args[ 'placeholder' ] ) : ''; ?>'
		data-sortable='<?php echo ! empty( $args[ 'sortable' ] ) ? 1 : 0; ?>'
		data-type='wc_products'
		data-params='<?php echo wp_json_encode( array_intersect_key( $args, array_flip( array( 'status', 'include', 'frequency' ) ) ) ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>'
		<?php if( $args[ 'disabled' ] ) { echo ' disabled="disabled"'; } ?>
		<?php if( $args[ 'multiple' ] ) { echo ' multiple'; } ?>>
		
		<?php if( ! $args[ 'multiple' ] ) { ?>
			<option><!-- Used for the placeholder --></option>
		<?php }
		
		do_action( 'patips_wc_add_product_selectbox_options', $args, $products_titles );

		$is_selected = false;
		if( $products_titles ) {
			foreach( $products_titles as $product_id => $product ) {
				// Display simple products options
				if( empty( $product[ 'variations' ] ) ) {
					$selected_key = array_search( intval( $product_id ), $remaining_selected_product_ids, true );
					if( $selected_key !== false ) { unset( $remaining_selected_product_ids[ $selected_key ] ); }
					
					if( $args[ 'sortable' ] && $selected_key !== false ) { ob_start(); }
				?>
					<option class='patips-wc-product-option' value='<?php echo esc_attr( $product_id ); ?>' <?php if( $selected_key !== false ) { echo 'selected'; } ?>>
						<?php echo $product[ 'title' ] ? esc_html( apply_filters( 'patips_translate_text_external', $product[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product', 'object_id' => $product_id, 'field' => 'post_title' ) ) ) : esc_html( $product[ 'title' ] ); ?>
					</option>
				<?php
					if( $args[ 'sortable' ] && $selected_key !== false ) { $selected_options[ intval( $product_id ) ] = ob_get_clean(); }

				// Display variations options
				} else {
					if( ! $args[ 'sortable' ] ) { ?>
						<optgroup class='patips-wc-variable-product-option-group' label='<?php echo $product[ 'title' ] ? esc_attr( apply_filters( 'patips_translate_text_external', $product[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product', 'object_id' => $product_id, 'field' => 'post_title' ) ) ) : esc_attr( $product[ 'title' ] ); ?>'>
					<?php }
						foreach( $product[ 'variations' ] as $variation_id => $variation ) {
							$selected_key = array_search( intval( $variation_id ), $remaining_selected_product_ids, true );
							if( $selected_key !== false ) { unset( $remaining_selected_product_ids[ $selected_key ] ); }
							$variation_title = $variation[ 'title' ] ? esc_html( apply_filters( 'patips_translate_text_external', $variation[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_variation', 'object_id' => $variation_id, 'field' => 'post_excerpt', 'product_id' => $product_id ) ) ) : esc_html( $variation[ 'title' ] );
							$formatted_variation_title = trim( preg_replace( '/,[\s\S]+?:/', ',', ',' . $variation_title ), ', ' );
							
							if( $args[ 'sortable' ] && $selected_key !== false ) { ob_start(); }
							?>
								<option class='patips-wc-product-variation-option' value='<?php echo esc_attr( $variation_id ); ?>' <?php if( $selected_key !== false ) { echo 'selected'; } ?>>
								<?php 
									echo $formatted_variation_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
								</option>
							<?php
							if( $args[ 'sortable' ] && $selected_key !== false ) { $selected_options[ intval( $variation_id ) ] = ob_get_clean(); }
						}
					
					if( ! $args[ 'sortable' ] ) { ?>
						</optgroup>
					<?php }
				}
			}
		}

		// Display non existing products if "allow_tags"
		if( $args[ 'allow_tags' ] && $remaining_selected_product_ids ) {
			foreach( $remaining_selected_product_ids as $selected_product_id ) {
				if( $args[ 'sortable' ] ) { ob_start(); }
			?>
				<option value='<?php echo esc_attr( $selected_product_id ); ?>' selected>
				<?php
					/* translators: %s = Product ID. */
					echo esc_html( sprintf( esc_html__( 'Product #%s', 'patrons-tips' ), $selected_product_id ) );
				?>
				</option>
			<?php
				if( $args[ 'sortable' ] ) { $selected_options[ intval( $selected_product_id ) ] = ob_get_clean(); }
			}
		}
		
		// If the selectbox is sortable, display the selected options in the selected order
		if( $args[ 'sortable' ] && $selected_options ) {
			foreach( $selected_product_ids as $selected_product_id ) {
				if( empty( $selected_options[ intval( $selected_product_id ) ] ) ) { continue; }
				echo $selected_options[ intval( $selected_product_id ) ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	?>
	</select>
	<?php
	$output = ob_get_clean();

	if( empty( $args[ 'echo' ] ) ) { return $output; }
	
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}


/**
 * Check if the current page is a WooCommerce screen
 * @since 0.26.0
 * @param string $screen_id
 * @return boolean
 */
function patips_is_wc_screen( $screen_id = '' ) {
	if( ! function_exists( 'get_current_screen' ) ) { return false; }
	
	$current_screen = get_current_screen();
	if( ! $current_screen ) { return false; }
	
	$wc_screens = wc_get_screen_ids();
	if( ! isset( $current_screen->id ) ) { return false; }
	if( $screen_id && $current_screen->id !== $screen_id ) { return false; }
	
	return in_array( $current_screen->id, $wc_screens, true );
}




// TIERS

/**
 * Toggle to allow the tier price to be set manually
 * @since 0.22.0
 * @param int $product_id
 * @return array
 */
function patips_wc_is_tier_price_manual() {
	return apply_filters( 'patips_wc_is_tier_price_manual', false );
}


/**
 * Get tier associated to the product
 * @since 0.10.0
 * @param int $product_id
 * @return array
 */
function patips_wc_get_product_tier( $product_id ) {
	$tiers        = patips_get_tiers_data();
	$product_tier = array();
	
	foreach( $tiers as $tier_id => $tier ) {
		foreach( $tier[ 'product_ids' ] as $product_ids ) {
			if( in_array( intval( $product_id ), $product_ids, true ) ) {
				$product_tier = $tier;
				break;
			}
		}
		if( $product_tier ) { break; }
	}
	
	return $product_tier;
}


/**
 * Get product ID associated to the tier
 * @since 0.15.0
 * @param int $tier_id
 * @return int
 */
function patips_wc_get_tier_product_id( $tier_id ) {
	$tier       = patips_get_tier_data( $tier_id );
	$product_id = 0;

	if( ! empty( $tier[ 'product_ids' ] ) ) {
		foreach( $tier[ 'product_ids' ] as $tier_product_ids ) {
			foreach( $tier_product_ids as $frequency => $tier_product_id ) {
				if( $tier_product_id ) {
					$product_id = $tier_product_id;
					break;
				}
			}
		}
	}
	
	return $product_id;
}


/**
 * Get price per product, per frequency, per tier
 * @since 0.21.0 (was patips_get_tiers_products_price)
 * @param array $tiers
 * @return array
 */
function patips_wc_get_tiers_products_price( $tiers = array() ) {
	if( ! $tiers ) { $tiers = patips_get_tiers_data(); }
	
	$tiers_products_price = array();
	foreach( $tiers as $tier_id => $tier ) {
		foreach( $tier[ 'product_ids' ] as $frequency => $product_ids ) {
			foreach( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				$price   = $product ? $product->get_price() : '';

				if( $price !== '' ) {
					if( ! isset( $tiers_products_price[ $tier_id ] ) )               { $tiers_products_price[ $tier_id ] = array(); }
					if( ! isset( $tiers_products_price[ $tier_id ][ $frequency ] ) ) { $tiers_products_price[ $tier_id ][ $frequency ] = array(); }

					$tiers_products_price[ $tier_id ][ $frequency ][ $product_id ] = floatval( $price );
				}
			}
		}
	}
	
	return apply_filters( 'patips_tiers_products_price', $tiers_products_price, $tiers );
}


/**
 * Get the lowest price among tiers
 * @since 0.21.0 (was patips_get_tiers_lowest_product_price)
 * @version 0.23.0
 * @param array $tiers
 * @return float|false
 */
function patips_wc_get_tiers_lowest_product_price( $tiers = array() ) {
	$tiers_products_price = patips_wc_get_tiers_products_price( $tiers );
	
	$min_price = false;
	foreach( $tiers_products_price as $tier_id => $products_price ) {
		foreach( $products_price as $frequency => $prices ) {
			$tier_min_price = min( $prices );
			if( $min_price === false || $tier_min_price < floatval( $min_price ) ) {
				$min_price = $tier_min_price;
			}
		}
	}
	
	return apply_filters( 'patips_tiers_lowest_product_price', $min_price, $tiers );
}


/**
 * Get tier product ids by frequency
 * @since 0.22.0 (was patips_get_tier_product_ids_by_frequency)
 * @param string|array $include
 * @param string|array $exclude
 * @return array
 */
function patips_wc_get_tier_product_ids_by_frequency( $include = array(), $exclude = array() ) {
	$include     = patips_str_ids_to_array( $include );
	$exclude     = patips_str_ids_to_array( $exclude );
	$tiers       = patips_get_tiers_data();
	$product_ids = array();
	
	foreach( $tiers as $tier ) {
		foreach( $tier[ 'product_ids' ] as $freq => $ids ) {
			if( ( $include && ! in_array( $freq, $include, true ) )
			||  ( $exclude &&   in_array( $freq, $exclude, true ) ) ) { continue; }
			$product_ids = array_merge( $product_ids, $ids );
		}
	}
	
	return patips_ids_to_array( $product_ids );
}


/**
 * Update a tier product ids
 * @since 0.22.0 (was patips_update_tier_product_ids)
 * @version 0.26.0
 * @param int $tier_id
 * @param array $new_product_ids_by_freq
 * @return int|false
 */
function patips_wc_update_tier_product_ids( $tier_id, $new_product_ids_by_freq ) {
	$old_product_ids_by_tier = patips_wc_get_tiers_product_ids( array( $tier_id ) );
	$old_product_ids_by_freq = ! empty( $old_product_ids_by_tier[ $tier_id ] ) ? $old_product_ids_by_tier[ $tier_id ] : array();
	
	// Remove deleted product ids
	$deleted_product_ids = array();
	foreach( $old_product_ids_by_freq as $frequency => $old_product_ids ) {
		$deleted_product_ids[ $frequency ] = isset( $new_product_ids_by_freq[ $frequency ] ) ? array_diff( $old_product_ids, $new_product_ids_by_freq[ $frequency ] ) : $old_product_ids;
	}
	$deleted_product_ids = array_filter( $deleted_product_ids );
	$deleted = $deleted_product_ids ? patips_wc_delete_tier_product_ids( $tier_id, $deleted_product_ids ) : 0;
	
	// Insert new product ids
	$inserted_product_ids = array();
	foreach( $new_product_ids_by_freq as $frequency => $new_product_ids ) {
		$inserted_product_ids[ $frequency ] = isset( $old_product_ids_by_freq[ $frequency ] ) ? array_diff( $new_product_ids, $old_product_ids_by_freq[ $frequency ] ) : $new_product_ids;
	}
	$inserted_product_ids = array_filter( $inserted_product_ids );
	$inserted = $inserted_product_ids ? patips_wc_insert_tier_product_ids( $tier_id, $inserted_product_ids ) : 0;
	
	// Update default product per frequency
	$default_product_by_freq = array();
	foreach( $new_product_ids_by_freq as $freq => $new_product_ids ) {
		if( count( $new_product_ids ) > 1 ) {
			$default_product_by_freq[ $freq ] = reset( $new_product_ids );
		}
	}
	if( $default_product_by_freq ) {
		patips_wc_set_tier_product_ids_as_default( $tier_id, $default_product_by_freq );
	}
	
	if( $inserted === false || $deleted === false ) { return false; }
	
	return $inserted + $deleted;
}




// PRODUCTS

/**
 * Get the number of periods given by a product
 * @since 0.13.0
 * @version 0.13.1
 * @param int $product_id
 * @return int
 */
function patips_wc_get_product_period_nb( $product_id ) {
	$product      = is_numeric( $product_id ) && $product_id ? wc_get_product( $product_id ) : $product_id;
	$product_id   = is_a( $product, 'WC_Product' ) ? $product->get_id() : $product_id;
	$tiers        = patips_get_tiers_data();
	$frequency    = 1;
	$product_tier = array();
	
	foreach( $tiers as $tier_id => $tier ) {
		foreach( $tier[ 'product_ids' ] as $product_frequency => $product_ids ) {
			if( in_array( intval( $product_id ), $product_ids, true ) ) {
				$new_frequency = strpos( '_month', $product_frequency ) !== false ? str_replace( '_month', '', $product_frequency ) : $frequency;
				if( is_numeric( $new_frequency ) ) {
					$frequency = intval( $new_frequency );
				}
				break;
			}
		}
		if( $product_tier ) { break; }
	}
	
	return apply_filters( 'patips_wc_product_period_nb', floor( $frequency / patips_get_default_period_duration() ), $product, $product_id );
}




// CART

/**
 * Check if cart has a product bound to a tier
 * @since 0.10.0
 * @version 0.14.0
 * @param array $tier_ids
 * @param boolean $exclude_switch
 * @return boolean|int
 */
function patips_wc_cart_has_tiers( $tier_ids = array(), $exclude_switch = false ) {
	$has_tiers  = false;
	if( empty( WC()->cart ) ) { return $has_tiers; }
	
	$tiers      = patips_get_tiers_data();
	$cart_items = WC()->cart->get_cart();
	
	foreach( $cart_items as $cart_item_key => $cart_item ) {
		$is_switch = patips_wc_is_cart_item_subscription_switch( $cart_item );
		if( $exclude_switch && $is_switch ) { continue; }
		
		$product      = $cart_item[ 'data' ];
		$product_id   = is_a( $product, 'WC_Product' ) ? intval( $product->get_id() ) : 0;
		$product_tier = $product_id ? patips_wc_get_product_tier( $product_id ) : array();
		
		if( $product_tier ) {
			$has_tiers = $tier_ids ? in_array( $product_tier[ 'id' ], $tier_ids, true ) : true;
			
			if( $has_tiers ) {
				$has_tiers = $product_tier[ 'id' ];
				break;
			}
		}
	}
	
	return $has_tiers;
}




// ORDERS

/**
 * Check if an item in the order is a product bound to a tier
 * @since 0.10.0
 * @version 0.13.4
 * @param WC_Order|int $order_id
 * @param array $tier_ids
 * @return boolean|int
 */
function patips_wc_order_has_tiers( $order_id, $tier_ids = array() ) {
	$has_tiers   = false;
	$order       = is_a( $order_id, 'WC_Order' ) ? $order_id : wc_get_order( $order_id );
	$order_items = $order ? $order->get_items() : array();
	if( ! $order_items ) { return $has_tiers; }

	foreach( $order_items as $order_item ) {
		if( ! is_a( $order_item, 'WC_Order_Item_Product' ) ) { continue; }
		
		$variation_id = intval( $order_item->get_variation_id() );
		$product_id   = $variation_id ? $variation_id : intval( $order_item->get_product_id() );
		$product_tier = $product_id ? patips_wc_get_product_tier( $product_id ) : array();
		
		if( $product_tier ) {
			$has_tiers = $tier_ids ? in_array( $product_tier[ 'id' ], $tier_ids, true ) : true;
			
			if( $has_tiers ) {
				$has_tiers = $product_tier[ 'id' ];
				break;
			}
		}
	}

	return $has_tiers;
}


/**
 * Get first product in an order
 * @since 0.13.0
 * @param WC_Order $order
 * @return WC_Product|null|false
 */
function patips_wc_get_order_product( $order ) {
	$product_id  = 0;
	$order_items = $order ? $order->get_items() : array();
	foreach( $order_items as $order_item ) {
		if( ! is_a( $order_item, 'WC_Order_Item_Product' ) ) { continue; }
		$variation_id = intval( $order_item->get_variation_id() );
		$product_id   = $variation_id ? $variation_id : intval( $order_item->get_product_id() );
		if( $product_id ) { break; }
	}
	
	return $product_id ? wc_get_product( $product_id ) : null;
}




// PATRONS

/**
 * Get total income based on patron history
 * @since 0.15.0
 * @param array $patrons
 * @param array $args_raw
 * @return float|int
 */
function patips_wc_get_total_income_based_on_patrons_history( $patrons, $args_raw = array() ) {
	$default_args = array(
		'include_tax'       => get_option( 'woocommerce_tax_display_shop', 'excl' ) === 'incl',
		'include_discounts' => false,
		'include_scheduled' => true,
		'include_manual'    => false
	);
	$args = array_merge( $default_args, $args_raw );
	
	$total = 0;
	foreach( $patrons as $patron ) {
		foreach( $patron[ 'history' ] as $history_entry ) {
			if( empty( $history_entry[ 'active' ] ) ) { continue; }
			
			// Add order item price
			$order = $history_entry[ 'order_id' ] ? wc_get_order( $history_entry[ 'order_id' ] ) : null;
			if( $order && $order->is_paid() ) {
				$order_items = $order->get_items();
				foreach( $order_items as $item ) {
					if( $history_entry[ 'order_item_id' ] && $history_entry[ 'order_item_id' ] !== intval( $item->get_id() ) ) { continue; }
					
					$item_price = $args[ 'include_discounts' ] ? $item->get_total() : $item->get_subtotal();
					
					$total += $item_price;
					
					if( $args[ 'include_tax' ] ) {
						$item_tax = $args[ 'include_discounts' ] ? $item->get_total_tax() : $item->get_subtotal_tax();
						
						$total += $item_tax;
					}
				}
			}
			
			// For scheduled payments, add the subscription price
			else if( $args[ 'include_scheduled' ] && ! $history_entry[ 'order_id' ] && $history_entry[ 'subscription_id' ] ) {
				$subscription       = patips_get_subscription_data( $history_entry[ 'subscription_id' ], $history_entry[ 'subscription_plugin' ] );
				$subscription_price = ! empty( $subscription[ 'price' ] ) && ! empty( $subscription[ 'active' ] ) ? $subscription[ 'price' ] : 0;
				
				$total += $subscription_price;
			}
			
			// For manually added entries, add the default product price
			else if( $args[ 'include_manual' ] && ! $history_entry[ 'order_id' ] && ! $history_entry[ 'subscription_id' ] && $history_entry[ 'tier_id' ] ) {
				$product_id    = patips_wc_get_tier_product_id( $history_entry[ 'tier_id' ] );
				$product       = $product_id ? wc_get_product( $product_id ) : null;
				if( ! $product ) { continue; }
				
				$product_price = $args[ 'include_tax' ] ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product );
				
				$total += $product_price;
			}
		}
	}
	
	return apply_filters( 'patips_wc_total_income_based_on_patrons_history', $total, $args_raw );
}


/**
 * Get price of a patron history entry
 * @since 0.21.0 (was patips_get_patron_history_price)
 * @version 0.23.1
 * @param array $history_entry
 * @param boolean $fallback_to_tier_price
 * @return float|int
 */
function patips_wc_get_patron_history_entry_price( $history_entry, $fallback_to_tier_price = true ) {
	// Get tier lowest product price
	$tier_price = 0;
	if( $fallback_to_tier_price ) {
		$tier              = $history_entry[ 'tier_id' ] ? patips_get_tier_data( $history_entry[ 'tier_id' ] ) : array();
		$min_product_price = $tier ? patips_wc_get_tiers_lowest_product_price( array( $history_entry[ 'tier_id' ] => $tier ) ) : 0;
		$tier_price        = $tier && $min_product_price === false ? $tier[ 'price' ] : $min_product_price;
	}
	
	// Get subscription price
	$subscription_id     = ! empty( $history_entry[ 'subscription_id' ] ) ? $history_entry[ 'subscription_id' ] : 0;
	$subscription_plugin = ! empty( $history_entry[ 'subscription_plugin' ] ) ? $history_entry[ 'subscription_plugin' ] : '';
	$subscription        = $subscription_id ? patips_get_subscription_data( $subscription_id, $subscription_plugin ) : array();
	$subcription_price   = ! empty( $subscription[ 'price' ] ) ? $subscription[ 'price' ] : 0;

	// Get order item price
	$order            = $history_entry[ 'order_id' ] ? wc_get_order( $history_entry[ 'order_id' ] ) : null;
	$order_item_price = 0;
	
	if( $order ) {
		foreach( $order->get_items() as $item_id => $item ) {
			if( intval( $item_id ) !== $history_entry[ 'order_item_id' ] ) { continue; }
			$order_item_price = $order->get_line_total( $item, true );
			break;
		}
	}
	
	$price = $order_item_price ? $order_item_price : ( $subcription_price ? $subcription_price : $tier_price );
	
	return apply_filters( 'patips_wc_patron_history_entry_price', $price, $history_entry );
}




// SUBSCRIPTION FORM

/**
 * Get add to cart tier form
 * @since 0.16.0
 * @version 1.0.4
 * @param array $args
 * @return string
 */
function patips_wc_get_add_to_cart_tier_form( $args = array() ) {
	if( ! $args ) {
		$args = patips_get_default_subscription_form_filters();
	}
	
	$tiers = patips_get_tiers_data( array_merge( $args, array( 'in__id' => $args[ 'tiers' ] ) ) );
	
	// Get price args
	$price_args = array();
	if( $args[ 'decimals' ] !== false ) {
		$price_args[ 'decimals' ] = $args[ 'decimals' ];
	}
	
	// Get frequencies
	$frequencies = patips_get_subscription_plugin() ? $args[ 'frequencies' ] : array( 'one_off' );
	if( ! $frequencies ) {
		foreach( $tiers as $tier_id => $tier ) { 
			foreach( $tier[ 'product_ids' ] as $frequency => $product_ids ) {
				if( ! in_array( $frequency, $frequencies, true ) ) {
					$frequencies[] = $frequency;
				}
			}
		}
	}
	
	// Sort frequencies and get default frequency
	$frequencies       = patips_sort_frequencies( $frequencies );
	$default_frequency = in_array( $args[ 'default_frequency' ], $frequencies, true ) ? $args[ 'default_frequency' ] : end( $frequencies );
	
	// Get default tier
	$default_tier_id = $tiers && $args[ 'default_tier' ] && isset( $tiers[ $args[ 'default_tier' ] ] ) ? $args[ 'default_tier' ] : 0;
	if( $tiers && ! $default_tier_id ) {
		$tier_ids        = array_keys( $tiers );
		$default_tier_id = $tier_ids ? $tier_ids[ max( 0, ceil( count( $tier_ids ) / 2 ) - 1 ) ] : 0;
	}
	
	// Get default product
	$default_product_id = '';
	foreach( $tiers as $tier_id => $tier ) { 
		if( $default_tier_id && $tier_id !== $default_tier_id ) { continue; }
		foreach( $tier[ 'product_ids' ] as $frequency => $product_ids ) {
			if( $frequency !== $default_frequency ) { continue; }
			$default_tier_id    = $tier_id;
			$default_product_id = intval( reset( $product_ids ) );
			break;
		}
		if( $default_product_id ) { break; }
	}
	
	ob_start();
	?>
	<form action='<?php echo esc_attr( $args[ 'redirect' ] ); ?>' method='post' class='patips-subscription-form patips-add-to-cart-tier-form'>
		<input type='hidden' name='add-to-cart' value='<?php echo esc_attr( $default_product_id ); ?>'/>

		<?php do_action( 'patips_wc_add_to_cart_tier_form_before', $args ); ?>

		<div class='patips-tier-options-container'>
		<?php 
			foreach( $tiers as $tier_id => $tier ) { 
				if( $args[ 'tiers' ] && ! in_array( $tier_id, $args[ 'tiers' ], true ) ) { continue; }
				
				/* translators: %s is the tier ID */
				$title = $tier[ 'title' ] ? esc_html( $tier[ 'title' ] ) : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
				$icon  = $tier[ 'icon_id' ] ? wp_get_attachment_image( $tier[ 'icon_id' ], $args[ 'image_size' ] ) : '';
				$price = false;
				$attr  = array( 'value' => '' );
				$input_key = md5( wp_rand() );

				if( ! $default_product_id && $default_tier_id === $tier_id ) { 
					$attr[ 'checked' ] = 'checked';
				}

				$i = 0;
				foreach( $tier[ 'product_ids' ] as $frequency => $product_ids ) {
					$product_id = intval( reset( $product_ids ) );

					if( $price === false || ( $product_id && $frequency === $default_frequency ) ) {
						$product = $product_id ? wc_get_product( $product_id ) : false;
						if( $product ) {
							$price = $args[ 'include_tax' ] ? wc_get_price_including_tax( $product ) : wc_get_price_excluding_tax( $product );
						}
					}

					$attr[ 'data-product-id-' . $frequency ] = $product_id;

					if( $frequency === $default_frequency ) { 
						$attr[ 'value' ] = $product_id;
						if( ! $product_id ) {
							$attr[ 'hidden' ] = 1;
						}
					}
					if( $product_id && $default_product_id && $product_id === $default_product_id ) { 
						$attr[ 'checked' ] = 'checked';
					}

					++$i;
				}

				$attr = apply_filters( 'patips_wc_add_to_cart_tier_form_tier_option_input_attr', $attr, $tier, $args );

				$additional_classes = $container_classes = '';
				if( isset( $attr[ 'class' ] ) ) { 
					$additional_classes = $attr[ 'class' ];
					unset( $attr[ 'class' ] );
				}

				if( isset( $attr[ 'checked' ] ) && $attr[ 'checked' ] === 'checked' )    { $container_classes .= ' patips-selected'; }
				if( isset( $attr[ 'disabled' ] ) && $attr[ 'disabled' ] === 'disabled' ) { $container_classes .= ' patips-disabled'; }
				if( ! empty( $attr[ 'hidden' ] ) )                                       { $container_classes .= ' patips-hidden'; unset( $attr[ 'hidden' ] ); }
				
				?>
				<div id='patips-tier-option-container-<?php echo esc_attr( $tier_id . '-' . $input_key ); ?>'
					 class='patips-tier-option-container patips-tier-option-container-<?php echo esc_attr( $tier_id . ' ' . $container_classes ); ?>'
					 data-tier_id='<?php echo esc_attr( $tier_id ); ?>'>
					<input type='radio' 
						   name='add-to-cart' 
						   id='patips-tier-option-input-<?php echo esc_attr( $tier_id . '-' . $input_key ); ?>' 
						   class='patips-tier-option-input patips-tier-option-input-<?php echo esc_attr( $tier_id . ' ' . $additional_classes ); ?>' 
						   <?php foreach( $attr as $key => $value ) { echo esc_html( $key ) . '="' . esc_attr( $value ) . '"'; } ?>
					/>
					<label for='patips-tier-option-input-<?php echo esc_attr( $tier_id . '-' . $input_key ); ?>'>
						<div class='patips-tier-option'>
							<div class='patips-tier-option-icon'>
								<?php echo wp_kses_post( $icon ); ?>
							</div>
							<div class='patips-tier-option-text'>
								<div class='patips-tier-option-title'><?php echo esc_html( $title ); ?></div>
								<div class='patips-tier-option-price' data-price-args='<?php echo wp_json_encode( $price_args ); ?>'><?php echo $price ? wp_kses_post( patips_format_price( $price, $price_args ) ) : ''; ?></div>
								<div class='patips-tier-option-description'><?php echo wp_kses_post( nl2br( $tier[ 'description' ] ) ); ?></div>
								<?php do_action( 'patips_wc_add_to_cart_tier_form_tier_option_text_after', $tier, $args ); ?>
							</div>
							<?php do_action( 'patips_wc_add_to_cart_tier_form_tier_option_after', $tier, $args ); ?>
						</div>
					</label>
					<?php do_action( 'patips_wc_add_to_cart_tier_form_tier_option_container_after', $tier, $args ); ?>
				</div>
				<?php
			}
		?>
		</div>
		
		<div class='patips-tier-frequency-container <?php echo count( $frequencies ) <= 1 ? 'patips-hidden' : ''; ?>'>
		<?php
			foreach( $frequencies as $frequency ) {
				$frequency_adverb = patips_get_frequency_name( $frequency );

				$attr = array(
					'value'      => $frequency,
					'data-label' => $frequency_adverb ? $frequency_adverb : str_replace( array( '-', '_' ), ' ', $frequency )
				);
				if( $frequency && $default_frequency && $frequency === $default_frequency ) { 
					$attr[ 'checked' ] = 'checked';
				}

				$attr = apply_filters( 'patips_wc_add_to_cart_tier_form_frequency_input_attr', $attr, $frequency, $tiers, $args );

				$additional_classes = $container_classes = '';
				if( isset( $attr[ 'class' ] ) ) { 
					$additional_classes = $attr[ 'class' ];
					unset( $attr[ 'class' ] );
				}

				if( isset( $attr[ 'checked' ] ) && $attr[ 'checked' ] === 'checked' )    { $container_classes .= ' patips-selected'; }
				if( isset( $attr[ 'disabled' ] ) && $attr[ 'disabled' ] === 'disabled' ) { $container_classes .= ' patips-disabled'; }
				if( ! empty( $attr[ 'hidden' ] ) )                                       { $container_classes .= ' patips-hidden'; unset( $attr[ 'hidden' ] ); }

				?>
				<div id='patips-tier-frequency-<?php echo esc_attr( $frequency . '-' . $input_key ); ?>'
					 class='patips-tier-frequency patips-tier-frequency-<?php echo esc_attr( $frequency . ' ' . $container_classes ); ?>'>
					<input type='radio' 
						   name='frequency' 
						   id='patips-tier-frequency-input-<?php echo esc_attr( $frequency . '-' . $input_key ); ?>' 
						   class='patips-tier-frequency-input patips-tier-frequency-input-<?php echo esc_attr( $frequency . ' ' . $additional_classes ); ?>' 
						   <?php foreach( $attr as $key => $value ) { echo esc_html( $key ) . '="' . esc_attr( $value ) . '"'; } ?>
					/>
					<label for='patips-tier-frequency-input-<?php echo esc_attr( $frequency . '-' . $input_key ); ?>'><?php echo esc_html( $attr[ 'data-label' ] ); ?></label>
				</div>
				<?php
			}

			do_action( 'patips_wc_add_to_cart_tier_form_frequency', $args );
		?>
		</div>

		<div class='patips-tier-form-submit-container'>
			<input type='submit' value='<?php echo $args[ 'submit_label' ] ? esc_html( $args[ 'submit_label' ] ) : esc_html__( 'Add to cart', 'patrons-tips' ); ?>' class='patips-tier-form-submit'/>
		</div>

		<div class='patips-tier-form-instructions-container'>
			<p class='patips-tier-form-instruction-one-off' data-text='<?php /* translators: {title} = the tier title. {price} = a formatted price (e.g.: "$10.00"). */ esc_attr_e( 'The selected tier "{title}" ({price}) will be debited as soon as the order is completed. The corresponding rewards will be unlocked for one month.', 'patrons-tips' ); ?>'></p>
			<p class='patips-tier-form-instruction-recurring' data-text='<?php /* translators: {title} = the tier title. {price} = a formatted price (e.g.: "$10.00"). {frequency} = Frequency name like "every 2 months", "yearly"... */ esc_attr_e( 'The selected tier "{title}" ({price}) will be debited recurringly ({frequency}). You can cancel your subscription at any time.', 'patrons-tips' ); ?>'></p>
		</div>

		<?php do_action( 'patips_wc_add_to_cart_tier_form_after', $args ); ?>
	</form>
	<?php
	
	return apply_filters( 'patips_wc_add_to_cart_tier_form', ob_get_clean(), $args );
}