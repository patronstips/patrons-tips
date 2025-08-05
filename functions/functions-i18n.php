<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// LOCALE

/**
 * Get current translation plugin identifier
 * @since 0.5.0
 * @return string
 */
function patips_get_translation_plugin() {
	return apply_filters( 'patips_translation_plugin', '' );
}


/**
 * Get current site language
 * @since 0.5.0
 * @version 0.26.1
 * @global string $patips_locale
 * @param boolean $with_locale
 * @return string 
 */
function patips_get_current_lang_code( $with_locale = false ) {
	global $patips_locale;
	
	// If the language is temporarily switched use $patips_locale, else, use get_locale()
	$locale    = $patips_locale ? $patips_locale : get_locale();
	$i         = strpos( $locale, '_' );
	$lang_code = $with_locale ? $locale : substr( $locale, 0, $i !== false ? $i : null );
	
	$lang_code = apply_filters( 'patips_current_lang_code', $lang_code, $with_locale );
	
	if( ! $lang_code ) { 
		$lang_code = $with_locale ? 'en_US' : 'en';
	}
	
	return $lang_code;
}


/**
 * Get site default locale
 * @since 0.5.0
 * @version 0.26.1
 * @param boolean $with_locale Whether to return also country code
 * @return string
 */
function patips_get_site_default_locale( $with_locale = true ) {
	$locale = get_locale();
	if( ! $with_locale ) {
		$i      = strpos( $locale, '_' );
		$locale = substr( $locale, 0, $i !== false ? $i : null );
	}

	$locale = apply_filters( 'patips_site_default_locale', $locale, $with_locale );
	
	if( ! $locale ) {
		$locale = $with_locale ? 'en_US' : 'en';
	}
	
	return $locale;
}


/* 
 * Get user locale, and default to site or current locale
 * @since 0.5.0
 * @param int|WP_User $user_id
 * @param string $default 'current' or 'site'
 * @param boolean $country_code Whether to return also country code
 * @return string
 */
function patips_get_user_locale( $user_id, $default = 'current', $country_code = true ) {
	if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
		$user = wp_get_current_user();
	} elseif ( $user_id instanceof WP_User ) {
		$user = $user_id;
	} elseif ( $user_id && is_numeric( $user_id ) ) {
		$user = get_user_by( 'id', $user_id );
	}
	
	if( ! $user ) { $locale = get_locale(); }
	else {
		if( $default === 'site' ) {
			// Get user locale
			$locale = strval( $user->locale );
			// If not set, get site default locale
			if( ! $locale ) {
				$alloptions	= wp_load_alloptions();
				$locale		= ! empty( $alloptions[ 'WPLANG' ] ) ? strval( $alloptions[ 'WPLANG' ] ) : get_locale();
			}
		} else {
			// Get user locale, if not set get current locale
			$locale = $user->locale ? strval( $user->locale ) : get_locale();
		}
	}

	// Remove country code from locale string
	if( ! $country_code ) {
		$_pos = strpos( $locale, '_' );
		if( $_pos !== false ) {
			$locale = substr( $locale, 0, $_pos );
		}
	}

	return apply_filters( 'patips_user_locale', $locale, $user_id, $default, $country_code );
}


/* 
 * Get site locale, and default to site or current locale
 * @since 0.5.0
 * @param boolean $country_code Whether to return also country code
 * @return string
 */
function patips_get_site_locale( $country_code = true ) {
	// Get raw site locale, or current locale by default
	$locale = get_locale();

	// Remove country code from locale string
	if( ! $country_code ) {
		$_pos = strpos( $locale, '_' );
		if( $_pos !== false ) {
			$locale = substr( $locale, 0, $_pos );
		}
	}

	return apply_filters( 'patips_site_locale', $locale, $country_code );
}


/**
 * Switch Patrons Tips locale
 * @since 0.5.0
 * @version 0.26.0
 * @global string $patips_locale
 * @param string $locale
 * @return string|false
 */
function patips_switch_locale( $locale ) {
	$callback = apply_filters( 'patips_switch_locale_callback', 'switch_to_locale', $locale );
	if( ! function_exists( $callback ) ) { return false; }
	
	// Convert lang code to locale and check if locale is installed
	$locale = patips_is_available_locale( $locale );
	if( ! $locale ) { return false; }
	
	$switched_locale = call_user_func( $callback, $locale );
	
	if( $switched_locale ) {
		if( ! is_string( $switched_locale ) ) { $switched_locale = $locale; }
		
		// Set $patips_locale global affects 'plugin_locale' hook
		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		global $patips_locale;
		$patips_locale = $switched_locale;
		
		do_action( 'patips_locale_switched', $switched_locale );
	}
	
	return $switched_locale;
}


/**
 * Switch Patrons Tips locale back to the original
 * @since 0.5.0
 * @global string $patips_locale
 * @return string
 */
function patips_restore_locale() {
	$restored_locale = '';
	
	$callback = apply_filters( 'patips_restore_locale_callback', 'restore_previous_locale' );
	if( function_exists( $callback ) ) {
		$restored_locale = call_user_func( $callback );
		if( $restored_locale ) {
			if( ! is_string( $restored_locale ) ) { $restored_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale(); }
			
			// Set $patips_locale global affects 'plugin_locale' hook
			// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
			global $patips_locale;
			$patips_locale = $restored_locale;
			
			do_action( 'patips_locale_restored', $restored_locale );
		}
	}
	
	return $restored_locale;
}


/**
 * Switch the locale used to filter the query results
 * @since 1.0.4
 * @param string $locale
 * @return string|false
 */
function patips_switch_query_locale( $locale ) {
	$callback = apply_filters( 'patips_switch_query_locale_callback', '', $locale );
	if( ! ( $callback && function_exists( $callback ) ) ) { return false; }
	
	// Convert lang code to locale and check if locale is installed
	$locale = patips_is_available_locale( $locale );
	if( ! $locale ) { return false; }
	
	$switched_locale = call_user_func( $callback, $locale );
	
	if( $switched_locale ) {
		do_action( 'patips_admin_query_switched', $switched_locale );
	}
	
	return $switched_locale;
}


/**
 * Restore the locale used to filter the query results back to the previous one
 * @since 1.0.4
 * @return string| false
 */
function patips_restore_query_locale() {
	$callback = apply_filters( 'patips_restore_query_locale_callback', '' );
	if( ! ( $callback && function_exists( $callback ) ) ) { return false; }
	
	$restored_locale = call_user_func( $callback );
	
	if( $restored_locale ) {
		if( ! is_string( $restored_locale ) ) { $restored_locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale(); }
		do_action( 'patips_query_locale_restored', $restored_locale );
	}
	
	return $restored_locale;
}


/**
 * Set plugin_locale to $patips_locale if defined
 * @since 0.5.0
 * @version 0.26.1
 * @global string $patips_locale
 * @param string $locale
 * @return string
 */
function patips_set_plugin_locale( $locale ) {
	global $patips_locale;
	return $patips_locale ? $patips_locale : $locale;
}
add_filter( 'plugin_locale', 'patips_set_plugin_locale', 100, 1 );


/**
 * Check if a locale exists and is installed
 * @since 0.26.0
 * @param string $locale
 * @return string|false
 */
function patips_is_available_locale( $locale ) {
	$is_available        = false;
	$installed_locales   = array_values( get_available_languages() );
	$installed_locales[] = 'en_US'; // Installed by default
	
	if( in_array( $locale, $installed_locales, true ) ) {
		$is_available = $locale;
		
	} else {
		// Try to find locale from lang code
		$i         = strpos( $locale, '_' );
		$lang_code = $i ? substr( $locale, 0, $i ) : $locale;
		foreach( $installed_locales as $installed_locale ) {
			if( $installed_locale === $lang_code || strpos( $installed_locale, $lang_code . '_' ) === 0 ) {
				$is_available = $installed_locale;
				break;
			}
		}
	}
	
	return apply_filters( 'patips_is_available_locale', $is_available, $locale );
}




// GLOBAL

/**
 * Get all translatable texts
 * @since 0.25.3
 * @version 0.26.0
 * @return array
 */
function patips_get_translatable_texts() {
	// Get data to translate in the default language
	$lang_switched = patips_switch_locale( patips_get_site_default_locale() );
	
	$texts = array();
	
	// Tiers
	$tiers = patips_get_tiers_data( array(), true );
	if( !$tiers ) {
		foreach( $tiers as $tier ) {
			$tier_texts = array();

			if( ! empty( $tier[ 'title' ] ) )       { $tier_texts[] = array( 'value' => $tier[ 'title' ], 'string_name' => 'Tier #' . $tier[ 'id' ] . ' - title' ); }
			if( ! empty( $tier[ 'description' ] ) ) { $tier_texts[] = array( 'value' => $tier[ 'description' ], 'string_name' => 'Tier #' . $tier[ 'id' ] . ' - description' ); }

			$tier_texts = apply_filters( 'patips_translatable_texts_tier', $tier_texts, $tier );
			
			if( $tier_texts ) { $texts = array_merge( $texts, $tier_texts ); }
		}
	}
	
	$texts = apply_filters( 'patips_translatable_texts', $texts );
	
	if( $lang_switched ) { patips_restore_locale(); }
	
	return $texts;
}




// WPML

/**
 * Translate a Patrons Tips string into the desired language (default to current site language) with WPML
 * @since 0.25.3
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. Not implemented (see patips_wpml_fallback_text filter). False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function patips_translate_text_with_wpml( $text, $lang = '', $fallback = true, $args = array() ) {
	if( ! $text ) { return $text; }
	
	// Get current language
	if( ! $lang ) { $lang = patips_get_current_lang_code(); }
	
	// Translate
	$string_name     = ! empty( $args[ 'string_name' ] ) ? $args[ 'string_name' ] : $text;
	$translated_text = apply_filters( 'wpml_translate_single_string', $text, 'Patrons Tips', $string_name, $lang );
	
	// WPML returns the original text if the translation is not found, 
	// but we don't know if that string is actually not registered, or if the translation is actually the same as the original
	if( $text === $translated_text ) {
		$default_lang_code = patips_get_site_default_locale( false );
		
		// Register the string (it's ok if it's already registered)
		do_action( 'wpml_register_single_string', 'Patrons Tips', $string_name, $text, false, $default_lang_code );
		
		if( $lang !== $default_lang_code && ! $fallback ) {
			// You may want to return an empty string here instead of the original text
			$translated_text = apply_filters( 'patips_wpml_fallback_text', $translated_text, $lang, $args );
		}
	}

	return $translated_text;
}


/**
 * Translate a non-Patrons Tips string into the desired language with WPML (default to current site language)
 * @since 0.25.3
 * @version 1.0.4
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function patips_translate_external_text_with_wpml( $text, $lang = '', $fallback = true, $args = array() ) {
	// Get current language
	if( ! $lang ) { $lang = patips_get_current_lang_code(); }
	
	$default_args    = array( 'domain' => '', 'string_name' => '', 'object_type' => '', 'object_id' => 0, 'field' => '' );
	$args            = wp_parse_args( $args, $default_args );
	$translated_text = $fallback ? $text : '';
	
	// Wordpress texts
	if( $args[ 'domain' ] === 'wordpress' ) {
		// WP options
		if( $args[ 'field' ] === 'blogname' ) { $translated_text = apply_filters( 'wpml_translate_single_string', $text, 'WP', 'Blog Title', $lang ); }
		
		// Posts & Terms
		else if( intval( $args[ 'object_id' ] ) ) {
			$translated_object_id = apply_filters( 'wpml_object_id', intval( $args[ 'object_id' ] ), $args[ 'object_type' ], false, $lang );
			if( $translated_object_id ) {
				$translated_post = taxonomy_exists( $args[ 'object_type' ] ) ? get_term( $translated_object_id ) : get_post( $translated_object_id );
				if( $translated_post && isset( $translated_post->{ $args[ 'field' ] } ) ) {
					$translated_text = $fallback && ! $translated_post->{ $args[ 'field' ] } ? $text : $translated_post->{ $args[ 'field' ] };
				}
			}
		}
	}
	
	return apply_filters( 'patips_translate_external_text_with_wpml', $translated_text, $text, $lang, $fallback, $args );
}


/**
 * Translate a WooCommerce string into the desired language with WPML (default to current site language)
 * @since 0.25.3
 * @version 1.0.4
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function patips_translate_wc_text_with_wpml( $translated_text, $text, $lang, $fallback, $args ) {
	if( $args[ 'domain' ] !== 'woocommerce' ) { return $translated_text; }
	
	// Translate product and product_variation
	if( intval( $args[ 'object_id' ] ) && in_array( $args[ 'object_type' ], array( 'product', 'product_variation' ), true ) ) {
		$translated_object_id = apply_filters( 'wpml_object_id', intval( $args[ 'object_id' ] ), $args[ 'object_type' ], false, $lang );
		if( $translated_object_id ) {
			$product         = wc_get_product( $translated_object_id );
			$property        = substr( $args[ 'field' ], 0, 5 ) === 'post_' && ! in_array( $args[ 'field' ], array( 'post_type', 'post_password' ), true ) ? strtolower( substr( $args[ 'field' ], 5 ) ) : strtolower( $args[ 'field' ] );
			$meta_value      = method_exists( $product, 'get_' . $property ) ? call_user_func( array( $product, 'get_' . $property ) ) : $product->get_meta( $args[ 'field' ] );
			$translated_text = $fallback && ! $meta_value ? $text : $meta_value;
		}
	}
	
	// No need to translate order_item, product_attribute_key, product_attribute_option
	
	return $translated_text;
}
add_filter( 'patips_translate_external_text_with_wpml', 'patips_translate_wc_text_with_wpml', 10, 5 );


/**
 * WPML's function for switch_to_locale
 * @since 0.25.3
 * @global array $patips_wpml_stack
 * @param string $locale
 * @return string
 */
function patips_wpml_switch_locale( $locale ) {
	global $patips_wpml_stack;
	if( ! $patips_wpml_stack ) { $patips_wpml_stack = array(); }
	
	$old_locale = patips_get_current_lang_code( true );
	
	$lang_code = strpos( $locale, '_' ) !== false ? substr( $locale, 0, strpos( $locale, '_' ) ) : $locale;
	do_action( 'wpml_switch_language', $lang_code );
	
	$new_locale = patips_get_current_lang_code( true );
	
	if( $new_locale !== $old_locale ) {
		$patips_wpml_stack[] = $old_locale;
	}
	
	return $new_locale;
}


/**
 * WPML's function for restore_previous_locale
 * @since 0.25.3
 * @return string
 */
function patips_wpml_restore_locale() {
	global $patips_wpml_stack;
	if( ! $patips_wpml_stack ) { $patips_wpml_stack = array(); }
	
	$old_locale = $patips_wpml_stack ? array_pop( $patips_wpml_stack ) : null;
	$lang_code  = $old_locale && strpos( (string) $old_locale, '_' ) !== false ? substr( $old_locale, 0, strpos( $old_locale, '_' ) ) : $old_locale;
	do_action( 'wpml_switch_language', $lang_code );
	
	$new_locale = patips_get_current_lang_code( true );
	
	return $new_locale;
}