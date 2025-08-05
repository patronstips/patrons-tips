<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// RESTRICTED TERMS

/**
 * Get the taxonomies that can be restricted by post type
 * @since 0.5.0
 * @return array
 */
function patips_get_restrictable_taxonomies_by_post_type() {
	return apply_filters( 'patips_restrictable_taxonomies_by_post_type', array(
		'post'       => array( 'category', 'post_tag' ),
		'attachment' => array_keys( patips_get_attachment_public_taxonomies() )
	));
}


/**
 * Get the restricted terms (categories and tags)
 * @since 0.1.0
 * @version 0.26.2
 * @return array
 */
function patips_get_restricted_terms() {
	// Get the cached value
	$cache = wp_cache_get( 'restricted_terms', 'patrons-tips' );
	if( $cache !== false ) { return is_array( $cache ) ? $cache : array(); }
	
	// Get restricted term ids by tier
	$tiers = patips_get_tiers_data();
	$restricted_terms = array();
	foreach( $tiers as $tier_id => $tier ) {
		foreach( $tier[ 'term_ids' ] as $scope => $term_ids ) {
			foreach( $term_ids as $term_id ) {
				if( ! isset( $restricted_terms[ $term_id ] ) ) {
					$restricted_terms[ $term_id ] = array(
						'tier_scopes' => array(),
						'term'        => null
					);
				}
				if( ! isset( $restricted_terms[ $term_id ][ 'tier_scopes' ][ $tier_id ] ) ) {
					$restricted_terms[ $term_id ][ 'tier_scopes' ][ $tier_id ] = array();
				}
				
				$restricted_terms[ $term_id ][ 'tier_scopes' ][ $tier_id ] = patips_str_ids_to_array( array_merge( $restricted_terms[ $term_id ][ 'tier_scopes' ][ $tier_id ], array( $scope ) ) );
			}
		}
	}
	
	// Get the associated WP_Term
	$term_ids = array_keys( $restricted_terms );
	$terms    = $term_ids ? get_terms( array( 'include' => $term_ids, 'hide_empty' => false, 'number' => 0 ) ) : array();
	foreach( $terms as $term ) {
		$term_id = intval( $term->term_id );
		if( isset( $restricted_terms[ $term_id ] ) ) {
			$restricted_terms[ $term_id ][ 'term' ] = $term;
		}
	}
	
	$restricted_terms = apply_filters( 'patips_restricted_terms', $restricted_terms );
	
	// Update cache
	wp_cache_set( 'restricted_terms', $restricted_terms, 'patrons-tips' );
	
	return $restricted_terms;
}




// FILTERS

/**
 * Get tiers filters
 * @since 0.5.0
 * @version 0.26.0
 * @param array $filters
 * @return array
 */
function patips_get_default_tier_filters() {
	return apply_filters( 'patips_default_tier_filters', array(
		'in__id'   => array(),
		'title'    => '', 
		'user_id'  => 0, 
		'active'   => 1,
		'order_by' => array( 'id' ), 
		'order'    => 'desc',
		'offset'   => 0,
		'per_page' => 0
	));
}


/**
 * Format tiers filters
 * @since 0.5.0
 * @version 0.25.7
 * @param array $filters
 * @return array
 */
function patips_format_tier_filters( $filters = array() ) {
	$default_filters = patips_get_default_tier_filters();

	$formatted_filters = array();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) {
			$formatted_filters[ $filter ] = $default_value;
			continue;
		}

		$current_value = $filters[ $filter ];

		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'in__id' ), true ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = patips_ids_to_array( $current_value, false );
			
		} else if( in_array( $filter, array( 'title' ), true ) ) {
			if( ! is_string( $current_value ) ) { $current_value = $default_value; }
			$current_value = sanitize_text_field( $current_value );
			
		} else if( in_array( $filter, array( 'active' ), true ) ) {
			     if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) ) { $current_value = 1; }
			else if( in_array( $current_value, array( 0, '0' ), true ) )               { $current_value = 0; }
			else if( in_array( $current_value, array( false, 'false' ), true ) )       { $current_value = false; }
			if( ! in_array( $current_value, array( 0, 1, false ), true ) )             { $current_value = $default_value; }
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 'id', 'title', 'user_id', 'creation_date', 'active', 'price' );
			if( is_string( $current_value ) && $current_value !== '' ) { 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_str_ids_to_array( $current_value );
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'user_id', 'offset', 'per_page' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
		}
		
		$formatted_filters[ $filter ] = $current_value;
	}
	
	return apply_filters( 'patips_formatted_tier_filters', $formatted_filters, $filters );
}



// DATA

/**
 * Get default tier meta
 * @since 0.11.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_tier_restricted_term_scopes() {
	return apply_filters( 'patips_tier_restricted_term_scopes', array( 'active' ) );
}

/**
 * Get default tier data
 * @since 0.5.0
 * @version 0.22.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_default_tier_data( $context = 'view' ) {
	return apply_filters( 'patips_default_tier_data', array( 
		'id'            => 0,
		'title'         => '',
		'icon_id'       => 0,
		'description'   => '',
		'price'         => 0,
		'user_id'       => 0,
		'creation_date' => '',
		'active'        => 1,
		'term_ids'      => array(
			'active' => array()
		)
	), $context );
}


/**
 * Get default tier meta
 * @since 0.5.0
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_get_default_tier_meta( $context = 'view' ) {
	return apply_filters( 'patips_default_tier_meta', array(), $context );
}


/**
 * Get tiers data and metadata
 * @since 0.5.0
 * @version 0.25.7
 * @param array $filters
 * @param boolean $raw
 * @return array
 */
function patips_get_tiers_data( $filters = array(), $raw = false ) {
	// Cache results if no filters are applied
	$use_cache = ! $filters && ! $raw;
	$tiers     = $use_cache ? wp_cache_get( 'tiers_data', 'patrons-tips' ) : false;
	
	if( $tiers === false ) {
		$tier_filters = patips_format_tier_filters( $filters );
		$tiers_raw    = patips_get_tiers( $tier_filters );
		
		$tier_ids       = array_keys( $tiers_raw );
		$tiers_meta     = $tier_ids ? patips_get_metadata( 'tier', $tier_ids ) : array();
		$tiers_term_ids = $tier_ids ? patips_get_tiers_restricted_term_ids( $tier_ids ) : array();
		
		$tiers = array();
		foreach( $tiers_raw as $tier_id => $tier_raw ) {
			$tier = (array) $tier_raw;

			// Add tier metadata
			$tier_meta = is_array( $tiers_meta ) && isset( $tiers_meta[ $tier_id ] ) && is_array( $tiers_meta[ $tier_id ] ) ? $tiers_meta[ $tier_id ] : array();
			$tier      = array_merge( $tier, $tier_meta );
			
			// Add tier restricted categories
			$tier[ 'term_ids' ] = isset( $tiers_term_ids[ $tier_id ] ) ? $tiers_term_ids[ $tier_id ] : array();

			// Format data
			$tier_raw_data = $tier;
			if( ! $raw ) {
				$tier = patips_format_tier_data( $tier );
			}
			
			$tiers[ $tier_id ] = apply_filters( 'patips_tier_data', $tier, $tier_raw_data, $tier_id, $raw );
		}
		
		// Update cache
		if( $use_cache ) {
			wp_cache_set( 'tiers_data', $tiers, 'patrons-tips' );
		}
	}
	
	return $tiers;
}


/**
 * Get tier data and metadata
 * @since 0.5.0
 * @version 0.26.0
 * @param int $tier_id
 * @param boolean $raw
 * @return array
 */
function patips_get_tier_data( $tier_id, $raw = false ) {
	$tier_filters = $raw ? array( 'in__id' => array( $tier_id ), 'active' => false ) : array( 'active' => false );
	$tiers        = patips_get_tiers_data( $tier_filters, $raw );
	
	return isset( $tiers[ $tier_id ] ) ? $tiers[ $tier_id ] : array();
}


/**
 * Format tier data
 * @since 0.5.0
 * @version 0.25.3
 * @param array $raw_tier_data
 * @param string $context 'view' or 'edit'
 * @return array
 */
function patips_format_tier_data( $raw_tier_data = array(), $context = 'view' ) {
	if( ! is_array( $raw_tier_data ) ) { $raw_tier_data = array(); }
	
	$default_data = array_merge( patips_get_default_tier_data( $context ), patips_get_default_tier_meta( $context ) );
	$keys_by_type = array( 
		'int'       => array( 'id', 'user_id', 'icon_id' ),
		'absfloat'  => array( 'price' ),
		'str'       => array( 'title' ),
		'str_html'  => array( 'description' ),
		'datetime'  => array( 'creation_date' ),
		'bool'      => array( 'active' ),
		'array'     => array( 'term_ids' )
	);
	$tier_data = patips_sanitize_values( $default_data, $raw_tier_data, $keys_by_type );
	
	// Term IDs
	$allowed_scopes          = patips_get_tier_restricted_term_scopes();
	$tier_data[ 'term_ids' ] = array_map( 'patips_ids_to_array', $tier_data[ 'term_ids' ] );
	$tier_data[ 'term_ids' ] = array_intersect_key( $tier_data[ 'term_ids' ], array_flip( $allowed_scopes ) );
	if( ! isset( $tier_data[ 'term_ids' ][ 'active' ] ) ) { $tier_data[ 'term_ids' ][ 'active' ] = array(); }
	
	// Translate texts
	if( $context !== 'edit' ) {
		$tier_data[ 'title' ]       = apply_filters( 'patips_translate_text', $tier_data[ 'title' ], '', true, array( 'string_name' => 'Tier #' . $tier_data[ 'id' ] . ' - title' ) );
		$tier_data[ 'description' ] = apply_filters( 'patips_translate_text', $tier_data[ 'description' ], '', true, array( 'string_name' => 'Tier #' . $tier_data[ 'id' ] . ' - description' ) );
	}
	
	return apply_filters( 'patips_formatted_tier_data', $tier_data, $raw_tier_data, $context );
}


/**
 * Sanitize tier data
 * @since 0.5.0
 * @version 0.22.0
 * @param array $raw_tier_data
 * @return array
 */
function patips_sanitize_tier_data( $raw_tier_data = array() ) {
	if( ! is_array( $raw_tier_data ) ) { $raw_tier_data = array(); }
	
	$default_data = array_merge( patips_get_default_tier_data( 'edit' ), patips_get_default_tier_meta( 'edit' ) );
	$keys_by_type = array( 
		'int'       => array( 'id', 'user_id', 'icon_id' ),
		'absfloat'  => array( 'price' ),
		'str'       => array( 'title' ),
		'str_html'  => array( 'description' ),
		'datetime'  => array( 'creation_date' ),
		'bool'      => array( 'active' ),
		'array'     => array( 'term_ids' )
	);
	$tier_data = patips_sanitize_values( $default_data, $raw_tier_data, $keys_by_type );
	
	// Term IDs
	$allowed_scopes = patips_get_tier_restricted_term_scopes();
	$tier_data[ 'term_ids' ] = array_intersect_key( $tier_data[ 'term_ids' ], array_flip( $allowed_scopes ) );
	foreach( $tier_data[ 'term_ids' ] as $scope => $post_type_term_ids ) {
		foreach( $post_type_term_ids as $post_type => $term_ids ) {
			if( is_array( $term_ids ) ) {
				$tier_data[ 'term_ids' ][ $scope ] = array_merge( $tier_data[ 'term_ids' ][ $scope ], $term_ids );
			} else if( is_numeric( $term_ids ) ) {
				$tier_data[ 'term_ids' ][ $scope ][] = intval( $term_ids );
			}
		}
		$tier_data[ 'term_ids' ][ $scope ] = patips_ids_to_array( $tier_data[ 'term_ids' ][ $scope ] );
	}
	if( ! isset( $tier_data[ 'term_ids' ][ 'active' ] ) ) { $tier_data[ 'term_ids' ][ 'active' ] = array(); }
	
	return apply_filters( 'patips_sanitized_tier_data', $tier_data, $raw_tier_data );
}




// TIER FORM

/**
 * Get default subscription form filters
 * @since 0.22.0 (was patips_get_default_subscription_form_filters)
 * @version 0.23.2
 * @return array
 */
function patips_get_default_subscription_form_filters() {
	return apply_filters( 'patips_default_subscription_form_filters', array( 
		'redirect'          => is_user_logged_in() ? patips_get_patron_area_page_url() : '',
		'submit_label'      => esc_html__( 'Become patron', 'patrons-tips' ),
		'tiers'             => array(),
		'frequencies'       => array(),
		'default_tier'      => 0,
		'default_frequency' => '',
		'include_tax'       => true,
		'decimals'          => false,
		'image_size'        => 'thumbnail',
		'order_by'          => array( 'price' ),
		'order'             => 'asc'
	) );
}


/**
 * Format subscription form filters manually input
 * @since 0.22.0 (was patips_format_string_add_to_cart_tier_form_filters)
 * @param array $filters
 * @return array
 */
function patips_format_string_subscription_form_filters( $filters = array() ) {
	// Format arrays
	$formatted_filters = array();
	$int_keys = array( 'tiers' );
	$str_keys = array( 'frequencies', 'order_by' );

	foreach( array_merge( $int_keys, $str_keys ) as $key ) {
		$formatted_filters[ $key ] = array();
		if( empty( $filters[ $key ] ) )       { continue; }
		if( is_array( $filters[ $key ] ) )    { $formatted_filters[ $key ] = $filters[ $key ]; continue; }
		if( ! is_string( $filters[ $key ] ) ) { continue; }

		$formatted_value = preg_replace( array(
			'/(?<=,),+/',  // Matches consecutive commas.
			'/^,+/',       // Matches leading commas.
			'/,+$/'        // Matches trailing commas.
		), '', $filters[ $key ] );

		if( in_array( $key, $int_keys, true ) ) { 
			$formatted_filters[ $key ] = explode( ',', preg_replace( array(
				'/[^\d,]/',    // Matches anything that's not a comma or number.
			), '', $formatted_value ) );
			$formatted_filters[ $key ] = patips_ids_to_array( $formatted_filters[ $key ], false ); 
		}
		if( in_array( $key, $str_keys, true ) ) { 
			$formatted_filters[ $key ] = explode( ',', $formatted_value );
			$formatted_filters[ $key ] = patips_str_ids_to_array( $formatted_filters[ $key ] ); 
		}
	}
	
	return apply_filters( 'patips_formatted_string_subscription_form_filters', array_merge( $filters, $formatted_filters ), $filters );
}


/**
 * Format subscription form filters
 * @since 0.22.0 (was patips_format_add_to_cart_tier_form_filters)
 * @version 0.25.7
 * @param array $filters
 * @return array
 */
function patips_format_subscription_form_filters( $filters = array() ) {
	$default_filters = patips_get_default_subscription_form_filters();
	
	$formatted_filters = array();
	foreach( $default_filters as $filter => $default_value ) {
		// If a filter isn't set, use the default value
		if( ! isset( $filters[ $filter ] ) ) {
			$formatted_filters[ $filter ] = $default_value;
			continue;
		}

		$current_value = $filters[ $filter ];
		
		// Else, check if its value is correct, or use default
		if( in_array( $filter, array( 'tiers' ), true ) ) {
			if( is_numeric( $current_value ) ) { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = patips_ids_to_array( $current_value, false );
			
		} else if( in_array( $filter, array( 'frequencies' ), true ) ) {
			if( is_string( $current_value ) )  { $current_value = array( $current_value ); }
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			else if( ( $i = array_search( 'all', $current_value, true ) ) !== false ) { unset( $current_value[ $i ] ); }
			$current_value = patips_str_ids_to_array( $current_value, false );
		
		} else if( in_array( $filter, array( 'include_tax' ), true ) ) {
				 if( in_array( $current_value, array( true, 'true', 1, '1' ), true ) )   { $current_value = 1; }
			else if( in_array( $current_value, array( false, 'false', 0, '0' ), true ) ) { $current_value = 0; }
			if( ! in_array( $current_value, array( 0, 1 ), true ) ) { $current_value = $default_value; }
		
		} else if( in_array( $filter, array( 'redirect', 'submit_label' ), true ) ) {
			$current_value = is_string( $current_value ) ? sanitize_text_field( stripslashes( $current_value ) ) : $default_value;
		
		} else if( in_array( $filter, array( 'default_frequency', 'image_size' ), true ) ) {
			$current_value = is_string( $current_value ) ? sanitize_title_with_dashes( stripslashes( $current_value ) ) : $default_value;
		
		} else if( $filter === 'order_by' ) {
			$sortable_columns = array( 'id', 'title', 'user_id', 'creation_date', 'active', 'price' );
			if( is_string( $current_value ) && $current_value !== '' ) { 
				if( ! in_array( $current_value, $sortable_columns, true ) ) { $current_value = $default_value; }
				else { $current_value = array( $current_value ); }
			}
			if( ! is_array( $current_value ) ) { $current_value = $default_value; }
			$current_value = patips_str_ids_to_array( $current_value );
			
		} else if( $filter === 'order' ) {
			if( ! in_array( $current_value, array( 'asc', 'desc' ), true ) ) { $current_value = $default_value; }

		} else if( in_array( $filter, array( 'decimals', 'default_tier' ), true ) ) {
			if( ! is_numeric( $current_value ) ) { $current_value = $default_value; }
			$current_value = intval( $current_value );
		}
		
		$formatted_filters[ $filter ] = $current_value;
	}
	
	return apply_filters( 'patips_formatted_subscription_form_filters', $formatted_filters, $filters );
}


/**
 * Allow free subcription via subscription form
 * @since 0.22.0
 * @param int $product_id
 * @return array
 */
function patips_is_free_subscription_allowed() {
	return apply_filters( 'patips_is_free_subscription_allowed', true );
}


/**
 * Get subscription form
 * @since 0.22.0
 * @version 1.0.4
 * @param array $args
 * @return string
 */
function patips_get_subscription_form( $args = array() ) {
	if( ! patips_is_free_subscription_allowed() ) { return ''; }
	
	if( ! $args ) {
		$args = patips_get_default_subscription_form_filters();
	}
	
	$tiers   = patips_get_tiers_data( array_merge( $args, array( 'in__id' => $args[ 'tiers' ] ) ) );
	$user_id = is_user_logged_in() ? get_current_user_id() : 0;
	
	// Get price args
	$price_args = array();
	if( $args[ 'decimals' ] !== false ) {
		$price_args[ 'decimals' ] = $args[ 'decimals' ];
	}
	
	// Get default tier
	$default_tier_id = $tiers && $args[ 'default_tier' ] && isset( $tiers[ $args[ 'default_tier' ] ] ) ? $args[ 'default_tier' ] : 0;
	if( $tiers && ! $default_tier_id ) {
		$tier_ids        = array_keys( $tiers );
		$default_tier_id = $tier_ids ? $tier_ids[ max( 0, ceil( count( $tier_ids ) / 2 ) - 1 ) ] : 0;
	}
	
	ob_start();
	
	if( $tiers ) {
	?>
	<form action='<?php echo esc_attr( $args[ 'redirect' ] ); ?>' method='post' class='patips-subscription-form patips-free-subscription-form'>
		<input type='hidden' name='nonce' value='<?php echo esc_attr( wp_create_nonce( 'patips_nonce_submit_subscription_form' ) ); ?>'/>
		
		<?php do_action( 'patips_subscription_form_before', $args ); ?>

		<div class='patips-tier-options-container'>
		<?php 
			foreach( $tiers as $tier_id => $tier ) { 
				if( $args[ 'tiers' ] && ! in_array( $tier_id, $args[ 'tiers' ], true ) ) { continue; }
				
				/* translators: %s is the tier ID */
				$title = $tier[ 'title' ] ? $tier[ 'title' ] : sprintf( esc_html__( 'Tier #%s', 'patrons-tips' ), $tier_id );
				$icon  = $tier[ 'icon_id' ] ? wp_get_attachment_image( $tier[ 'icon_id' ], $args[ 'image_size' ] ) : '';
				$attr  = array();
				$input_key = md5( wp_rand() );
				
				if( $default_tier_id === $tier_id ) {
					$attr[ 'checked' ] = 'checked';
				}

				$attr = apply_filters( 'patips_subscription_form_tier_option_input_attr', $attr, $tier, $args );

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
						   name='tier_id' 
						   value='<?php echo esc_attr( $tier_id ); ?>' 
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
								<div class='patips-tier-option-price' data-price-args='<?php echo wp_json_encode( $price_args ); ?>'><?php echo $tier[ 'price' ] ? wp_kses_post( patips_format_price( $tier[ 'price' ], $price_args ) ) : ''; ?></div>
								<div class='patips-tier-option-description'><?php echo wp_kses_post( nl2br( $tier[ 'description' ] ) ); ?></div>
								<?php do_action( 'patips_subscription_form_tier_option_text_after', $tier, $args ); ?>
							</div>
							<?php do_action( 'patips_subscription_form_tier_option_after', $tier, $args ); ?>
						</div>
					</label>
					<?php do_action( 'patips_subscription_form_tier_option_container_after', $tier, $args ); ?>
				</div>
				<?php 
			}
		?>
		</div>
		
		<?php if( ! $user_id ) { ?>
		<div class='patips-subscription-email-container'>
			<label>
				<?php esc_html_e( 'Email', 'patrons-tips' ); ?>
			</label>
			<input type='text' name='email'/>
			<small class='patips-log-in-link-container'>
			<?php
				echo sprintf( 
					/* translators: %s = "Log in" link */
					esc_html__( 'Already have an account? %s.', 'patrons-tips' ),
					patips_get_login_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			?>
			</small>
		</div>
		<?php } ?>

		<?php do_action( 'patips_subscription_form_before_submit', $args ); ?>

		<div class='patips-tier-form-submit-container'>
			<input type='submit' value='<?php echo $args[ 'submit_label' ] ? esc_html( $args[ 'submit_label' ] ) : esc_html__( 'Subscribe', 'patrons-tips' ); ?>' class='patips-tier-form-submit'/>
		</div>

		<?php do_action( 'patips_subscription_form_after', $args ); ?>
	</form>
	<?php
	}
	
	return apply_filters( 'patips_subscription_form', ob_get_clean(), $args );
}


/**
 * Get notice and action button to create first tier
 * @since 0.25.5 (was patips_get_first_tier_notice)
 */
function patips_display_first_tier_notice() {
	$can_create_tier = current_user_can( 'patips_create_tiers' );
?>
	<div id='patips-first-tier-notice'>
		<h2>
			<?php echo $can_create_tier ? esc_html__( 'Welcome to Patrons Tips! Let\'s start by creating your first tier.', 'patrons-tips' ) : esc_html__( 'There are no tiers available, and you are not allowed to create one.', 'patrons-tips' ); ?>
		</h2>
		<?php if( $can_create_tier ) { ?>
		<div id='patips-first-tier-button-container'>
			<a href='<?php echo esc_url( admin_url( 'admin.php?page=patips_tiers&action=new' ) ); ?>' class='button button-primary'>
				<?php esc_html_e( 'Create your first tier', 'patrons-tips' ); ?>
			</a>
		</div>
		<?php } ?>
	</div>
<?php
}