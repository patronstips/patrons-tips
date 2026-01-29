<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GENERAL

/**
 * Declare support for WC specific features (HPOS, blocks, etc.)
 * @since 0.3.0
 * @version 1.0.2
 */
function patips_wc_declare_compatibility() {
	if( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) { return; }
	
	$plugin_names = array( PATIPS_PLUGIN_NAME );
	if( defined( 'PATIPS_PRO_PLUGIN_NAME' ) ) {
		$plugin_names[] = PATIPS_PRO_PLUGIN_NAME;
	}
	
	foreach( $plugin_names as $plugin_name ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $plugin_name . '/' . $plugin_name . '.php', true );
	}
}
add_action( 'before_woocommerce_init', 'patips_wc_declare_compatibility' );


/**
 * Add "product_id" field type
 * @since 0.5.0
 * @param array $args
 */
function patips_wc_display_field_product_id( $args ) {
	$args[ 'selected' ] = $args[ 'value' ];
	patips_wc_display_product_selectbox( array_merge( $args, $args[ 'options' ] ) );
}
add_action( 'patips_display_field_product_id', 'patips_wc_display_field_product_id', 10, 1 );


/**
 * Search products for AJAX selectbox
 * @since 0.5.0
 * @version 1.0.2
 */
function patips_wc_controller_search_select2_products() {
	// Check nonce
	$is_nonce_valid	= check_ajax_referer( 'patips_query_select2_options', 'nonce', false );
	if( ! $is_nonce_valid ) { patips_send_json_not_allowed( 'search_select2_wc_products' ); }
	
	// Check query
	$term = isset( $_REQUEST[ 'term' ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ 'term' ] ) ) : '';
	if( ! $term ) { patips_send_json( array( 'status' => 'failed', 'error' => 'empty_query' ), 'search_select2_wc_products' ); }
	
	$defaults = array(
		'search'    => $term,
		'status'    => isset( $_REQUEST[ 'status' ] ) ? patips_str_ids_to_array( wp_unslash( $_REQUEST[ 'status' ] ) ) : array( 'draft', 'pending', 'private', 'publish', 'future' ),
		'include'   => isset( $_REQUEST[ 'include' ] ) ? patips_ids_to_array( wp_unslash( $_REQUEST[ 'include' ] ) ) : array(),
		'frequency' => isset( $_REQUEST[ 'frequency' ] ) ? patips_str_ids_to_array( wp_unslash( $_REQUEST[ 'frequency' ] ) ) : array(),
	);
	$args = apply_filters( 'patips_ajax_select2_wc_products_args', $defaults );
	
	$products_titles = patips_wc_get_product_titles( $args );
	$options = array();
	
	// Add products options
	foreach( $products_titles as $product_id => $product ) {
		$product_title = $product[ 'title' ] !== '' ? esc_html( apply_filters( 'patips_translate_text_external', $product[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product', 'object_id' => $product_id, 'field' => 'post_title' ) ) ) : $product[ 'title' ];
		if( ! empty( $product[ 'variations' ] ) ) {
			$children_options = array();
			foreach( $product[ 'variations' ] as $variation_id => $variation ) {
				$variation_title = $variation[ 'title' ] !== '' ? esc_html( apply_filters( 'patips_translate_text_external', $variation[ 'title' ], false, true, array( 'domain' => 'woocommerce', 'object_type' => 'product_variation', 'object_id' => $variation_id, 'field' => 'post_excerpt', 'product_id' => $product_id ) ) ) : $variation[ 'title' ];
				$formatted_variation_title = trim( preg_replace( '/,[\s\S]+?:/', ',', ',' . $variation_title ), ', ' );
				$children_options[] = array( 'id' => $variation_id, 'text' => $formatted_variation_title );
			}
			$options[] = array( 'children' => $children_options, 'text' => $product_title );
		} else {
			$options[] = array( 'id' => $product_id, 'text' => $product_title );
		}
	}
	
	// Allow plugins to add their values
	$select2_options = apply_filters( 'patips_ajax_select2_wc_products_options', $options, $args );
	
	if( ! $select2_options ) {
		patips_send_json( array( 'status' => 'failed', 'error' => 'no_results' ), 'search_select2_wc_products' );
	}
	
	patips_send_json( array( 'status' => 'success', 'options' => $select2_options ), 'search_select2_wc_products' );
}
add_action( 'wp_ajax_patipsSelect2Query_wc_products', 'patips_wc_controller_search_select2_products' );




// TIER EDITOR

/**
 * Add WC data to fields in Tier settings, Presentation area
 * @since 0.22.0
 * @version 1.1.0
 * @param array $fields
 * @param array $tier
 * @return array
 */
function patips_wc_tier_settings_fields_presentation( $fields, $tier ) {
	if( patips_wc_is_tier_price_manual() ) { return $fields; }
	
	// Check if a product is assigned
	$has_product = array_filter( $tier[ 'product_ids' ] );
	if( ! $has_product || ! $tier[ 'price' ] ) { 
		$fields[ 'price' ][ 'value' ] = ! empty( $tier[ 'default_price' ] ) ? $tier[ 'default_price' ] : '';
		return $fields;
	}
	
	if( ! isset( $fields[ 'price' ][ 'attr' ] ) )  { $fields[ 'price' ][ 'attr' ] = ''; }
	if( ! isset( $fields[ 'price' ][ 'after' ] ) ) { $fields[ 'price' ][ 'after' ] = ''; }
	
	ob_start();
	?>
		<div class='patips-info'>
			<span><?php esc_html_e( 'The price is determined automatically based on the associated products.', 'patrons-tips' ); ?></span>
		</div>
	<?php
	$after = ob_get_clean();
	
	$fields[ 'price' ][ 'value' ]  = floatval( $tier[ 'price' ] );
	$fields[ 'price' ][ 'attr' ]  .= ' disabled="disabled"';
	$fields[ 'price' ][ 'after' ] .= $after;
	$fields[ 'price' ][ 'tip' ]    = esc_html__( 'The tier price.', 'patrons-tips' ) . ' ' . esc_html__( 'The price is determined automatically based on the associated products.', 'patrons-tips' );
	
	return $fields;
}
add_filter( 'patips_tier_settings_fields_presentation', 'patips_wc_tier_settings_fields_presentation', 10, 2 );


/**
 * Display product IDs fields in tier settings page
 * @since 0.21.0
 * @version 1.1.0
 * @param array $tier
 */
function patips_wc_tier_settings_product_ids_fields( $tier ) {
	?>
	<hr/>
	<h4><?php esc_html_e( 'Products', 'patrons-tips' ) ?></h4>
	<?php
	do_action( 'patips_wc_tier_settings_fields_products_before', $tier );
	
	$subscription_plugin = patips_get_subscription_plugin();
	$product_ids_one_off = ! empty( $tier[ 'product_ids' ][ 'one_off' ] ) ? patips_ids_to_array( $tier[ 'product_ids' ][ 'one_off' ] ) : array();
	$product_ids_1_month = ! empty( $tier[ 'product_ids' ][ '1_month' ] ) ? patips_ids_to_array( $tier[ 'product_ids' ][ '1_month' ] ) : array();
	
	ob_start();
	?>
		<span class='patips-tier-product-buttons'>
			<button type='button' class='patips-tier-create-product-button button'><?php esc_html_e( 'Create product', 'patrons-tips' ); ?></button>
			<button type='button' class='patips-tier-edit-product-button button'><?php esc_html_e( 'Edit product', 'patrons-tips' ); ?></button>
		</span>
	<?php
	$buttons = ob_get_clean();
	
	$fields = apply_filters( 'patips_tier_settings_fields_products', array(
		'one_off' => array( 
			'name'        => 'product_ids[one_off]',
			'type'        => 'product_id',
			'multiple'    => count( $product_ids_one_off ) > 1,
			'placeholder' => esc_html__( 'Select a product', 'patrons-tips' ),
			'title'       => esc_html__( 'One-off', 'patrons-tips' ),
			'options'     => array(
				'frequency' => 'one_off',
				'sortable'  => 1
			),
			'value'       => $product_ids_one_off,
			'after'       => $buttons . patips_wc_get_tier_product_misconfiguration_notice( array( 'one_off' => $product_ids_one_off ) ),
			'tip'         => esc_html__( 'Select a non-recurring payment product. Purchasing this product will grant access to this tier for one month.', 'patrons-tips' )
		),
		'1_month' => array( 
			'name'        => 'product_ids[1_month]',
			'type'        => 'product_id',
			'multiple'    => count( $product_ids_1_month ) > 1,
			'hidden'      => $subscription_plugin ? 0 : 1,
			'placeholder' => esc_html__( 'Select a product', 'patrons-tips' ),
			'title'       => esc_html__( 'Monthly', 'patrons-tips' ),
			'options'     => array(
				'frequency' => '1_month',
				'sortable'  => 1
			),
			'value'       => $product_ids_1_month,
			'after'       => $buttons . patips_wc_get_tier_product_misconfiguration_notice( array( '1_month' => $product_ids_1_month ) ),
			'tip'         => esc_html__( 'Select a monthly recurring payment product. Purchasing this product will grant access to this tier for one month.', 'patrons-tips' )
		),
	), $tier );
	?>
	<div id='patips-tier-settings-fields-products' class='patips-settings-fields-container'>
		<input type='hidden' id='patips-wc-create-tier-product-nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_wc_create_tier_product' ) ); ?>'/>
		<input type='hidden' id='patips-wc-edit-tier-product-nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_wc_edit_tier_product' ) ); ?>'/>
		<?php patips_display_fields( $fields ); ?>
	</div>
	<?php
	
	if( ! $subscription_plugin ) {
	?>
		<div class='patips-info patips-align-start'>
			<span>
				<?php
					echo sprintf(
						/* translators: %s = link to the "documentation" */
						esc_html__( 'In order to subscribe to a tier on a recurring basis and make automatic renewal payments, you need to install a supported recurring payment plugin (see this %s):', 'patrons-tips' ),
						'<a href="https://patronstips.com/en/user-documentation/recurring-tiers/?utm_source=plugin&utm_medium=plugin&utm_content=tier-settings" target="_blank">' . esc_html__( 'documentation', 'patrons-tips' ) . '</a>'
					);
				?>
				<ul>
					<li><?php echo wp_kses_post( implode( '<li>', patips_get_recurring_payment_plugin_links() ) ); ?>
				</ul>
			</span>
		</div>
	<?php
	}
	
	do_action( 'patips_wc_tier_settings_fields_products_after', $tier );
}
add_action( 'patips_tier_settings_fields_presentation_after', 'patips_wc_tier_settings_product_ids_fields' );


/**
 * Insert new tier product ids
 * @since 0.22.0
 * @param int $tier_id
 * @param array $tier_data
 * @return int
 */
function patips_wc_tier_created( $tier_id, $tier_data ) {
	// Insert tier product ids
	$product_ids_by_freq = array_filter( $tier_data[ 'product_ids' ] );
	if( $product_ids_by_freq ) {
		patips_wc_insert_tier_product_ids( $tier_id, $product_ids_by_freq );
	}
	
	return $tier_id;
}
add_filter( 'patips_tier_created', 'patips_wc_tier_created', 10, 2 );


/**
 * Update tier product ids and WC product attributes
 * @since 0.22.0
 * @version 1.1.0
 * @param int|false $updated
 * @param int $tier_id
 * @param array $tier_data
 * @return int|false
 */
function patips_wc_tier_updated( $updated, $tier_id, $tier_data ) {
	// Update tier product ids
	$updated4 = patips_wc_update_tier_product_ids( $tier_id, $tier_data[ 'product_ids' ] );
	
	if( $updated === false || $updated4 === false ) { $updated = false; }
	else { $updated += (int)$updated4; }
	
	// Get new tier data
	$tier = patips_get_tier_data( $tier_id );
	
	// Update the corresponding product attribute title
	if( taxonomy_exists( 'pa_tier' ) ) {
		// Get the "Tier" attribute value corresponding to this tier
		$tier_term = get_term_by( 'slug', sanitize_title( 'tier-' . $tier[ 'id' ] ), 'pa_tier' );
		// Update term title to tier title
		if( $tier_term && $tier[ 'title' ] !== $tier_term->name ) {
			wp_update_term( intval( $tier_term->term_id ), $tier_term->taxonomy, array( 'name' => $tier[ 'title' ] ) );
		}
	}
	
	return $updated;
}
add_filter( 'patips_tier_updated', 'patips_wc_tier_updated', 10, 3 );


/**
 * AJAX Controller - Create a tier product
 * @since 1.1.0
 */
function patips_wc_controller_create_tier_product() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_wc_create_tier_product', 'nonce', false );
	if( ! $is_nonce_valid ) {
		patips_send_json_invalid_nonce( 'create_tier_product' );
	}
	
	// Check if allowed to edit a tier
	$is_allowed = current_user_can( 'patips_edit_tiers' );
	if( ! $is_allowed ) { 
		patips_send_json_not_allowed( 'create_tier_product' );
	}
	
	// Get tier
	$tier_id = ! empty( $_POST[ 'tier_id' ] ) ? intval( $_POST[ 'tier_id' ] ) : 0;
	$tier    = $tier_id ? patips_get_tier_data( $tier_id ) : array();
	if( ! $tier ) {
		patips_send_json( array( 
			'status'  => 'failed', 
			'error'   => 'tier_not_found',
			'message' => esc_html__( 'The selected tier was not found.', 'patrons-tips' ) . ' ' . esc_html__( 'Please publish the tier first and try again.', 'patrons-tips' )
		), 'create_tier_product' );
	}
	
	// Get frequency
	$frequency = ! empty( $_POST[ 'frequency' ] ) ? sanitize_title_with_dashes( $_POST[ 'frequency' ] ) : '';
	if( ! in_array( $frequency, array_keys( $tier[ 'product_ids' ] ), true ) ) {
		$frequency = '';
	}
	
	if( ! $frequency ) {
		patips_send_json( array( 
			'status'  => 'failed', 
			'error'   => 'invalid_frequency',
			'message' => esc_html__( 'Subscription product frequency not valid.', 'patrons-tips' )
		), 'create_tier_product' );
	}
	
	// Check price
	$has_product = array_filter( $tier[ 'product_ids' ] );
	if( ! $tier[ 'price' ] && ! $tier[ 'default_price' ] && ! $has_product ) {
		patips_send_json( array(
			'status'  => 'failed', 
			'error'   => 'price_not_set',
			'message' => esc_html__( 'Please set a price and update the tier first.', 'patrons-tips' )
		), 'create_tier_product' );
	}
	
	// Create product
	$product_id = $frequency === 'one_off' ? patips_wc_create_tier_product( $tier_id, $frequency ) : patips_wc_create_tier_subscription_product( $tier_id, $frequency );
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	
	if( ! $product ) {
		patips_send_json( array( 
			'status'  => 'failed', 
			'error'   => 'product_not_created',
			'message' => esc_html__( 'An error occurred while trying to create the product.', 'patrons-tips' )
		), 'create_tier_product' );
	}
	
	patips_send_json( array( 
		'status'               => 'success',
		'product_id'           => $product_id,
		'product_title'        => $product->get_name(),
		'parent_product_id'    => $product->get_id(),
		'parent_product_title' => $product->get_title()
	), 'create_tier_product' );
}
add_action( 'wp_ajax_patipsWCCreateTierProduct', 'patips_wc_controller_create_tier_product' );


/**
 * AJAX Controller - Get edit page URL of a tier product 
 * @since 1.1.0
 */
function patips_wc_controller_get_tier_product_edit_url() {
	// Check nonce
	$is_nonce_valid = check_ajax_referer( 'patips_wc_edit_tier_product', 'nonce', false );
	if( ! $is_nonce_valid ) {
		patips_send_json_invalid_nonce( 'get_tier_product_edit_url' );
	}
	
	// Check if allowed to edit a tier
	$is_allowed = current_user_can( 'edit_products' );
	if( ! $is_allowed ) { 
		patips_send_json_not_allowed( 'get_tier_product_edit_url' );
	}
	
	// Get product ID
	$product_id = ! empty( $_POST[ 'product_id' ] ) ? intval( $_POST[ 'product_id' ] ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	
	if( ! $product ) {
		patips_send_json( array( 
			'status'  => 'failed', 
			'error'   => 'product_not_found',
			'message' => esc_html__( 'An error occurred while trying to retrieve the product.', 'patrons-tips' )
		), 'get_tier_product_edit_url' );
	}
	
	// Get product edit page URL
	$product_edit_url = is_a( $product, 'WC_Product_Variation' ) ? get_edit_post_link( $product->get_parent_id() ) : get_edit_post_link( $product_id );
	
	patips_send_json( array(
		'status'       => 'success',
		'redirect_url' => htmlspecialchars_decode( $product_edit_url )
	), 'get_tier_product_edit_url' );
}
add_action( 'wp_ajax_patipsWCGetProductEditURL', 'patips_wc_controller_get_tier_product_edit_url' );




// QUICK START

/**
 * Create a default restricted product category for patronage
 * @since 1.1.0
 * @param array $term_ids_by_type
 */
function patips_wc_controller_create_quick_start_tiers_data( $term_ids_by_type ) {
	// Get restricted category
	$restricted_product_term_id = ! empty( $term_ids_by_type[ 'product' ] ) ? reset( $term_ids_by_type[ 'product' ] ) : 0;
	
	// Create restricted product category
	if( ! $restricted_product_term_id ) {
		$restricted_product_term_exists = term_exists( 'patronage-restricted-products', 'product_cat' );
		if( $restricted_product_term_exists && ! empty( $restricted_product_term_exists[ 'term_id' ] ) ) {
			$restricted_product_term_id = intval( $restricted_product_term_exists[ 'term_id' ] );
		}
		if( ! $restricted_product_term_id ) {
			wp_insert_term( esc_html__( 'Restricted products', 'patrons-tips' ), 'product_cat', array(
					'slug' => 'patronage-restricted-products'
				)
			);
		}
	}
}
add_action( 'patips_create_quick_start_tiers_before', 'patips_wc_controller_create_quick_start_tiers_data', 10, 1 );


/**
 * Add WC data to quick start tiers default data
 * @since 1.1.0
 * @param array $quick_start_tiers_default_data
 * @param array $term_ids_by_type
 * @return array
 */
function patips_wc_controller_quick_start_tiers_default_data( $quick_start_tiers_default_data, $term_ids_by_type ) {
	// Get restricted product category
	$restricted_product_term_id = ! empty( $term_ids_by_type[ 'product' ] ) ? reset( $term_ids_by_type[ 'product' ] ) : 0;
	if( ! $restricted_product_term_id ) {
		$restricted_product_term_exists = term_exists( 'patronage-restricted-products', 'product_cat' );
		if( $restricted_product_term_exists && ! empty( $restricted_product_term_exists[ 'term_id' ] ) ) {
			$restricted_product_term_id = intval( $restricted_product_term_exists[ 'term_id' ] );
		}
	}
	
	// Assign restricted product category to Premium tier
	if( $restricted_product_term_id ) {
		if( empty( $quick_start_tiers_default_data[ 'premium' ][ 'term_ids' ][ 'active' ] ) ) {
			$quick_start_tiers_default_data[ 'premium' ][ 'term_ids' ][ 'active' ] = array();
		}
		$quick_start_tiers_default_data[ 'premium' ][ 'term_ids' ][ 'active' ][] = $restricted_product_term_id;
	}
	
	// Multiply price per 100 if currency has no decimals
	if( ! wc_get_price_decimals() ) {
		foreach( $quick_start_tiers_default_data as $key => $quick_start_tier_default_data ) {
			$quick_start_tiers_default_data[ $key ][ 'price' ] *= 100;
		}
	}
	
	return $quick_start_tiers_default_data;
}
add_filter( 'patips_quick_start_tiers_default_data', 'patips_wc_controller_quick_start_tiers_default_data', 10, 2 );


/**
 * Create WC products for quick start tiers
 * @since 1.1.0
 * @param array $quick_start_tier_ids
 */
function patips_wc_controller_create_quick_start_tiers_products( $quick_start_tier_ids ) {
	// Get quick start tiers
	$tiers             = patips_get_tiers_data();
	$quick_start_tiers = array_intersect_key( $tiers, array_flip( $quick_start_tier_ids ) );
	
	$updated = false;
	foreach( $quick_start_tiers as $tier_id => $tier ) {
		// Create one-off product
		$one_off_product_id = patips_wc_create_tier_product( $tier_id, 'one_off', false );
		
		// Create subscription product
		$monthly_product_id = patips_wc_create_tier_subscription_product( $tier_id, '1_month', false );
		
		// Link products to tier
		if( $one_off_product_id ) {
			$tier[ 'product_ids' ][ 'one_off' ] = array( $one_off_product_id );
		}
		if( $monthly_product_id ) {
			$tier[ 'product_ids' ][ '1_month' ] = array( $monthly_product_id );
		}
		if( $one_off_product_id || $monthly_product_id ) {
			patips_wc_update_tier_product_ids( $tier_id, $tier[ 'product_ids' ] );
			$updated = true;
		}
	}
	
	// Clear cache
	if( $updated ) {
		wp_cache_delete( 'tiers_data', 'patrons-tips' );
	}
}
add_action( 'patips_quick_start_tiers_created', 'patips_wc_controller_create_quick_start_tiers_products', 10, 1 );




// PATRON EDITOR

/**
 * Display button to sync patron history with WC orders
 * @since 0.21.0
 * @version 0.25.5
 * @param array $patron
 */
function patips_wc_patron_settings_fields_sync( $patron ) {
?>
	<hr/>
	<div id='patips-sync-patron-history-container'>
		<?php do_action( 'patips_wc_patron_settings_sync_history_before', $patron ); ?>

		<a id='patips-sync-patron-history' 
		   class='button button-primary' 
		   data-patron_id='<?php echo intval( $patron[ 'id' ] ); ?>' 
		   data-nonce='<?php echo esc_attr( wp_create_nonce( 'patips_nonce_sync_patron_history' ) ); ?>'>
			<?php esc_html_e( 'Sync history', 'patrons-tips' ); ?>
		</a>

		<strong><?php esc_html_e( 'Update patronage history based on WooCommerce orders.', 'patrons-tips' ); ?></strong>

		<?php do_action( 'patips_wc_patron_settings_sync_history_after', $patron ); ?>
	</div>
<?php
}
add_action( 'patips_patron_settings_fields_history_after', 'patips_wc_patron_settings_fields_sync' );




// SETTINGS PAGE

/**
 * Add WC settings
 * @since 0.25.0
 */
function patips_wc_add_settings() {
	/* General settings Section - 3 - Notifications */
	add_settings_section( 
		'patips_settings_section_general_wc_notifications',
		esc_html__( 'Notifications', 'patrons-tips' ),
		'patips_wc_settings_section_general_notifications_callback',
		'patips_settings_general',
		array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'patips-settings-section-wc-notifications'
		)
	);
}
add_action( 'patips_add_settings', 'patips_wc_add_settings' );


/**
 * Show Reminder Notification settings section
 * @since 0.25.0
 * @version 0.25.5
 */
function patips_wc_settings_section_general_notifications_callback() {
	// Promote Patrons Tips Pro
	if( ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) { ?>
		<div id='patips-pro-ad-reminder-notification' class='patips-pro-ad'>
			<h4>
				<?php esc_html_e( 'Automatic email reminders', 'patrons-tips' ); ?>
				<span class='patips-pro-ad-icon'></span>
			</h4>
			<p>
			<?php
				echo sprintf(
					/* translators: %s = link to "Patrons Tips - Pro" sales page */
					esc_html__( '%s automatically sends an email reminder to your patrons few days before their patronage expires. You will no longer lose a patron because they forgot to renew.', 'patrons-tips' ),
					patips_get_pro_sales_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			?>
			</p>
		</div>
	<?php }
	
	do_action( 'patips_wc_settings_notifications_section' );
}


/**
 * Hide Price settings section if WC is active
 * @since 0.21.0
 * @version 1.0.2
 */
function patips_wc_hide_price_settings_fields() {
?>
	<p class='patips-settings-section-price-description'>
		<span>
		<?php
			// Display a message to redirect the user to WC options
			echo sprintf( 
					/* translators: %s = link to "WooCommerce currency options" */
					esc_html__( 'Please configure the price format in %s', 'patrons-tips' ), 
					'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=general#pricing_options-description' ) ) . '">' 
						. esc_html__( 'WooCommerce currency options', 'patrons-tips' ) 
					. '</a>' 
				);
		?>
		</span>
	</p>	
<?php
}
add_action( 'patips_settings_price_section', 'patips_wc_hide_price_settings_fields', 100 );


/**
 * Add options to WC settings
 * @since 1.0.4
 * @param array $settings
 * @return array
 */
function patips_wc_settings_page( $settings ) {
	$new_option = array(
		'title'    => esc_html__( 'Patron area', 'patrons-tips' ),
		'desc'     => esc_html__( 'Endpoint for the "My account &rarr; Patron area" page.', 'patrons-tips' ),
		'id'       => 'woocommerce_myaccount_patronage_endpoint',
		'type'     => 'text',
		'default'  => 'patronage',
		'desc_tip' => true,
	);
	
	// Add 'patronage' endpoint option at the begining of the enpoint section
	$new_settings = array();
	$inserted     = false;
	foreach( $settings as $key => $setting ) {
		// Keep options and string keys
		if( is_string( $key ) ) {
			$new_settings[ $key ] = $setting;
		} else {
			$new_settings[] = $setting;
		}
		
		// Add 'patronage' endpoint option in endpoint section
		if( ! $inserted && ! empty( $setting[ 'id' ] ) && $setting[ 'id' ] === 'account_endpoint_options' ) {
			$new_settings[] = $new_option;
			$inserted = true;
		}
	}
	
	// Otherwise, add it after
	if( ! $inserted ) {
		$new_settings[] = $new_option;
	}
	
	return $new_settings;
}
add_filter( 'woocommerce_settings_pages', 'patips_wc_settings_page' );




// ORDERS

/**
 * Keep trashed order status in memory
 * @since 0.23.1
 * @global string $patips_trashed_order_status
 * @param int $order_id
 * @param WC_Order $order
 */
function patips_wc_controller_before_trash_order( $order_id, $order = null ) {
	if( did_action( 'woocommerce_order_status_changed' ) ) { return; }
	
	$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id );
	if( ! $order ) { return; }
	
	global $patips_trashed_order_status;
	$patips_trashed_order_status = $order->get_status();
}
add_action( 'woocommerce_before_trash_order', 'patips_wc_controller_before_trash_order', 10, 2 );


/**
 * Sync patron history when an order is trashed
 * @since 0.23.1
 * @global string $patips_trashed_order_status
 * @param int $order_id
 */
function patips_wc_controller_trashed_order_sync_patron_history( $order_id ) {
	global $patips_trashed_order_status;
	if( ! $patips_trashed_order_status || did_action( 'woocommerce_order_status_changed' ) ) { return; }
	
	patips_wc_controller_order_status_changed_sync_patron_history( $order_id, $patips_trashed_order_status, 'trash' );
}
add_action( 'woocommerce_trash_order', 'patips_wc_controller_trashed_order_sync_patron_history', 100, 1 );


/**
 * Sync patron history when an order is restrored from trash
 * @since 0.23.1
 * @global string $patips_trashed_order_status
 * @param int $order_id
 * @param string $status_to
 */
function patips_wc_controller_restored_order_sync_patron_history( $order_id, $status_to = '' ) {
	if( did_action( 'woocommerce_order_status_changed' ) ) { return; }

	$status_to = strpos( $status_to, 'wc-' ) === 0 ? substr( $status_to, 3 ) : $status_to;

	patips_wc_controller_order_status_changed_sync_patron_history( $order_id, 'trash', $status_to );
}
add_action( 'woocommerce_untrash_order', 'patips_wc_controller_restored_order_sync_patron_history', 100, 2 );


/**
 * Delete corresponding patron history entries when an order is deleted
 * @since 0.23.1
 * @param int $order_id
 * @param WC_Order $order
 */
function patips_wc_controller_before_delete_order( $order_id, $order = null ) {
	// Get history entries related the the deleted order
	$filters         = patips_format_patron_history_filters( array( 'order_ids' => array( $order_id ) ) );
	$patrons_history = ! empty( $filters[ 'order_ids' ] ) ? patips_get_patrons_history( $filters ) : array();
	
	// Get history entry ids
	$entry_ids = array();
	if( $patrons_history ) {
		foreach( $patrons_history as $patron_id => $entries ) {
			$entry_ids = array_merge( $entry_ids, array_keys( $entries ) );
		}
	}
	$entry_ids = patips_ids_to_array( $entry_ids );
	
	// Delete history entries
	if( $entry_ids ) {
		patips_delete_patron_history( 0, $entry_ids, false );
	}
}
add_action( 'woocommerce_before_delete_order', 'patips_wc_controller_before_delete_order', 10, 2 );




// NOTICES

/**
 * Remove warning notice if the patron area page is not published, as patron area is integrated to My Account page with WooCommerce
 * @since 0.25.2
 * @version 0.26.0
 */
function patips_wc_remove_notice_publish_patron_area() {
	remove_action( 'admin_notices', 'patips_display_notice_publish_patron_area_page' );
}
add_action( 'admin_init', 'patips_wc_remove_notice_publish_patron_area' );


/** 
 * Display a warning notice if a tier is not bound to any product
 * @since 0.25.2
 * @version 0.26.0
 */
function patips_wc_display_notice_link_tiers_to_products() {
	if( ! current_user_can( 'patips_manage_patrons_tips' ) ) { return; }
	if( ! patips_is_own_screen() ) { return; }
	
	// Check if a tier has no products
	$sub_plugin  = patips_get_subscription_plugin();
	$tiers       = patips_get_tiers_data();
	$tier_titles = array();
	foreach( $tiers as $tier_id => $tier ) {
		if( empty( $tier[ 'active' ] ) ) { continue; }
		
		$has_product = ! empty( $tier[ 'product_ids' ][ 'one_off' ] );
		if( ! $has_product && $sub_plugin ) {
			foreach( $tier[ 'product_ids' ] as $frequency => $product_ids ) {
				if( $frequency === 'one_off' ) { continue; }
				if( $product_ids ) { $has_product = true; break; }
			}
		}
		
		if( ! $has_product ) {
			/* translators: %s is the tier ID */
			$tier_title = ! empty( $tier[ 'title' ] ) ? esc_html( $tier[ 'title' ] ) : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
			$tier_url   = admin_url( 'admin.php?page=patips_tiers&action=edit&tier_id=' . $tier_id );
			$tier_link  = current_user_can( 'patips_edit_tiers' ) ? '<a href="' . esc_url( $tier_url ) . '">' . $tier_title . '</a>' : $tier_title;
			
			$tier_titles[ $tier_id ] = $tier_link;
		}
	}
	
	if( ! $tier_titles ) { return; }
	
	// Display notice
	?>
		<div id='patips-link-tiers-to-products-notice' class='notice notice-warning patips-admin-notice' data-notice-id='link-tiers-to-products'>
			<p>
				<?php echo esc_html__( 'You must associate a product with your tiers, otherwise, customers will not be able to select them and become a patron.', 'patrons-tips' ); ?>
				<br/>
				<?php echo sprintf( 
					/* translators: %s = comma separated list of tier titles */
					esc_html( _n( 'The following tier is not associated with any product: %s.', 'The following tiers are not associated with any product: %s.', count( $tier_titles ), 'patrons-tips' ) ),
					'<span><strong>' . wp_kses_post( implode( '</strong>, <strong>', $tier_titles ) ) . '</strong></span>'
				);
			?>
			</p>
		</div>
	<?php
}
add_action( 'admin_notices', 'patips_wc_display_notice_link_tiers_to_products' );


/** 
 * Display a warning notice on the product edit page if there is an inconsistency between the produc configuration and the tier it is linked with
 * @since 0.26.0
 * @global WP_Post $post
 */
function patips_wc_display_notice_tier_product_misconfiguration() {
	if( ! current_user_can( 'patips_manage_patrons_tips' ) ) { return; }
	
	// Allow hiding these notices with custom code
	if( apply_filters( 'patips_wc_hide_tier_product_misconfiguration_notices', false ) ) { return; }
	
	$is_product_list_table = patips_is_wc_screen( 'edit-product' );
	$is_product_edit_page  = patips_is_wc_screen( 'product' );
	if( ! $is_product_list_table && ! $is_product_edit_page ) { return; }
	
	global $post;
    $product_id    = $is_product_edit_page && $post ? intval( $post->ID ) : 0;
	$product       = $product_id ? wc_get_product( $product_id ) : null;
	$variation_ids = $product ? patips_ids_to_array( $product->get_children() ) : array();
	
	$tiers           = patips_get_tiers_data();
	$notices_by_type = array();
	
	foreach( $tiers as $tier_id => $tier ) {
		/* translators: %s is the tier ID */
		$tier_title  = ! empty( $tier[ 'title' ] ) ? esc_html( $tier[ 'title' ] ) : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
		$tier_url    = admin_url( 'admin.php?page=patips_tiers&action=edit&tier_id=' . $tier_id );
		$tier_link   = current_user_can( 'patips_edit_tiers' ) ? '<a href="' . esc_url( $tier_url ) . '">' . $tier_title . '</a>' : $tier_title;
		$text_before = sprintf(
			/* translators: %s = Tier title (with a link to the tier edit page). */
			esc_html__( 'A product may not be correctly configured according to "%s" tier settings.', 'patrons-tips' ),
			'<strong>' . $tier_link . '</strong>'
		);
		
		$product_ids_per_freq = $tier[ 'product_ids' ];
		
		// On edit product page, display only the notices related to the corresponding product
		if( $is_product_edit_page ) {
			if( ! $product_id && ! $variation_ids ) { break; }
			
			foreach( $product_ids_per_freq as $freq => $product_ids ) {
				$intersect = array_intersect( array_merge( $variation_ids, array( $product_id ) ), $product_ids );
				if( $intersect ) {
					$product_ids_per_freq[ $freq ] = $intersect;
				} else {
					unset( $product_ids_per_freq[ $freq ] );
				}
			}
		}
		
		$tier_notices_by_type = $product_ids_per_freq ? patips_wc_get_tier_product_misconfiguration_notice( $product_ids_per_freq, true, true ) : array();
		
		if( $tier_notices_by_type ) {
			// Add link to tier settings before each error
			foreach( $tier_notices_by_type as $type => $notices ) {
				foreach( $notices as $i => $notice ) {
					$tier_notices_by_type[ $type ][ $i ] = $text_before . ' ' . $notice;
				}
			}
			
			$notices_by_type = array_merge_recursive( $notices_by_type, $tier_notices_by_type );
		}
	}
	
	// Display notice
	if( $notices_by_type ) {
		foreach( $notices_by_type as $type => $notices ) {
			if( ! $notices ) { continue; }
			
			// On product list table page, display only errors
			if( $is_product_list_table && $type !== 'error' ) { continue; }
			
			?>
				<div id='patips-tier-product-misconfiguration-notice' class='notice patips-admin-notice <?php echo esc_attr( 'notice-' . $type ); ?>' data-notice-id='tier-product-misconfiguration'>
					<?php
						echo count( $notices ) > 1 ? wp_kses_post( '<ul><li>' . implode( '<li>', $notices ) . '</ul>' ) : wp_kses_post( '<p>' . current( $notices ) . '</p>' );
					?>
				</div>
			<?php
		}
	}
}
add_action( 'admin_notices', 'patips_wc_display_notice_tier_product_misconfiguration' );


/** 
 * Display a warning notice if orders can be placed without account, but user cannot create account afterwards
 * @since 0.26.0
 * @version 1.1.0
 * @global WP_Post $post
 */
function patips_wc_display_notice_order_account_creation() {
	if( ! patips_is_own_screen() ) { return; }
	if( ! current_user_can( 'patips_manage_patrons_tips' ) || ! empty( $_GET[ 'action' ] ) ) { return; }
	
	$dismissed = get_option( 'patips_notice_dismissed_wc_order_account_creation' );
	if( $dismissed ) { return; }
	
	// Ignore if patron area is disabled in My Account
	$page_id = patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
	if( intval( $page_id ) < 0 ) { return; }
	
	// Ignore if guest checkout is disabled
	$guest_checkout = get_option( 'woocommerce_enable_guest_checkout' ) === 'yes';
	if( ! $guest_checkout ) { return; }
	
	// Ignore if there are no tiers
	$tiers = patips_get_tiers_data();
	if( ! $tiers ) { return; }
	
	// Check if account creation is enabled
	$delayed_account_creation   = get_option( 'woocommerce_enable_delayed_account_creation' ) === 'yes';
	$myaccount_account_creation = get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes';
	$users_can_register         = intval( get_option( 'users_can_register' ) );
	if( $delayed_account_creation || $myaccount_account_creation || $users_can_register ) { return; }
	
	// Display notice
	?>
		<div id='patips-wc-order-account-creation-notice' class='notice notice-warning is-dismissible patips-admin-notice' data-notice-id='wc_order_account_creation'>
			<p>
			<?php
				 echo wp_kses_post( sprintf(
					/* translators: %s = path to WC account creation settings "WooCommerce > Settings > Accounts & Privacy" (with a link). */
					esc_html__( 'Patrons may not be able to access their patron area. Indeed, customers can purchase a patronage without account, but they cannot create an account afterwards. However, an account is required to access patron area. You should disable guest checkout, or allow customers to create an account after checkout in %s.', 'patrons-tips' ),
					'<a href="' . esc_url( admin_url( '/admin.php?page=wc-settings&tab=account' ) ) . '"><strong>'
						. esc_html__( 'WooCommerce > Settings > Accounts & Privacy', 'patrons-tips' )
					. '</strong></a>'
				) );
			?>
			</p>
		</div>
	<?php
}
add_action( 'admin_notices', 'patips_wc_display_notice_order_account_creation' );