<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// GLOBAL

/**
 * Load or reload the plugin language files
 * @since 0.1.0
 * @version 1.0.2
 * @param string $locale
 */
function patips_load_textdomain( $locale = '' ) { 
	if( ! $locale ) {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : ( is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale() );
		$locale = apply_filters( 'plugin_locale', $locale, 'patrons-tips' );
	}
	
	// Check if locale is installed
	$locale = patips_is_available_locale( $locale );
	if( ! $locale ) { return; }
	
	unload_textdomain( 'patrons-tips', true );
	// Load .mo from wp-content/languages/patrons-tips/
	load_textdomain( 'patrons-tips', WP_LANG_DIR . '/patrons-tips/patrons-tips-' . $locale . '.mo' );
	// Load .mo from wp-content/languages/plugins/
	// Fallback on .mo from wp-content/plugins/patrons-tips/languages
	load_plugin_textdomain( 'patrons-tips', false, plugin_basename( PATIPS_PLUGIN_DIR ) . '/languages/' );
}
add_action( 'init', 'patips_load_textdomain', 5 );


// Backward compatibility
if( version_compare( get_bloginfo( 'version' ), '6.7', '<' ) ) {
	add_action( 'patips_locale_switched', 'patips_load_textdomain', 10, 1 );
	add_action( 'patips_locale_restored', 'patips_load_textdomain', 10, 1 );
	
	/**
	 * Change callback according to translation plugin when switching language
	 * @since 0.25.3
	 * @param string $locale
	 */
	add_filter( 'patips_switch_locale_callback', function( $callback ) {
		$plugin = patips_get_translation_plugin();
		if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { $callback = 'patips_wpml_switch_locale'; }
		return $callback;
	} );
	
	
	/**
	 * Change callback according to translation plugin when restoring language
	 * @since 0.25.3
	 * @param string $locale
	 */
	add_filter( 'patips_restore_locale_callback', function( $callback ) {
		$plugin = patips_get_translation_plugin();
		if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { $callback = 'patips_wpml_restore_locale'; }
		return $callback;
	} );
}


/**
 * Change query locale callback according to translation plugin
 * @since 1.0.4
 * @param string $locale
 */
add_filter( 'patips_switch_query_locale_callback', function( $callback ) {
	$plugin = patips_get_translation_plugin();
	if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { $callback = 'patips_wpml_switch_locale'; }
	return $callback;
} );


/**
 * Change query locale callback according to translation plugin
 * @since 1.0.4
 * @param string $locale
 */
add_filter( 'patips_restore_query_locale_callback', function( $callback ) {
	$plugin = patips_get_translation_plugin();
	if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { $callback = 'patips_wpml_restore_locale'; }
	return $callback;
} );


/**
 * Define translation plugin
 * @since 0.25.3
 * @param string $translation_plugin
 * @return string
 */
function patips_define_translation_plugin( $translation_plugin = '' ) {
	if( ! $translation_plugin ) {
			if( class_exists( 'SitePress' ) ) { $translation_plugin = 'wpml'; }
	   else if( class_exists( 'Polylang' ) )  { $translation_plugin = 'polylang'; }
	}
	return $translation_plugin;
}
add_filter( 'patips_translation_plugin', 'patips_define_translation_plugin', 10, 1 );


/**
 * Translate a string into the desired language (default to current site language) with translation plugin
 * @since 0.25.3
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function patips_translate_text_with_plugin( $text, $lang = '', $fallback = true, $args = array() ) {
	$plugin = patips_get_translation_plugin();
	
	if( $plugin ) {
		// Keep only the lang code, not country indicator
		$lang_code = $lang && is_string( $lang ) && strpos( $lang, '_' ) !== false ? substr( $lang, 0, strpos( $lang, '_' ) ) : $lang;
		
		if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
			$text = patips_translate_text_with_wpml( $text, $lang_code, $fallback, $args );
		}
	}
	
	return apply_filters( 'patips_translate_text_with_plugin', $text, $lang, $fallback, $args );
}
add_filter( 'patips_translate_text', 'patips_translate_text_with_plugin', 10, 4 );


/**
 * Translate a string external to Patrons Tips into the desired language (default to current site language) with translation plugin
 * @since 0.25.3
 * @param string $text
 * @param string $lang Optional. Two letter lang id (e.g. fr or en) or locale id (e.g. fr_FR or en_US).
 * @param boolean $fallback Optional. False to display empty string if the text doesn't exist in the desired language. True to display the text of another existing language.
 * @param array $args Optional. Data about the string to translate.
 * @return string
 */
function patips_translate_external_text_with_plugin( $text, $lang = '', $fallback = true, $args = array() ) {
	$plugin = patips_get_translation_plugin();
	
	if( $plugin ) {
		// Keep only the lang code, not country indicator
		$lang_code = $lang && is_string( $lang ) && strpos( $lang, '_' ) !== false ? substr( $lang, 0, strpos( $lang, '_' ) ) : $lang;
		
		if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
			$text = patips_translate_external_text_with_wpml( $text, $lang_code, $fallback, $args );
		}
	}
	
	return apply_filters( 'patips_translate_external_text_with_plugin', $text, $lang, $fallback, $args );
}
add_filter( 'patips_translate_text_external', 'patips_translate_external_text_with_plugin', 10, 4 );


/**
 * Get current lang code with plugin
 * @since 0.26.1
 * @global string $patips_locale
 * @param string $lang_code
 * @param boolean $with_locale
 * @return string
 */
function patips_current_lang_code_with_plugin( $lang_code, $with_locale ) {
	// Skip if language is temporarily switched
	global $patips_locale;
	if( $patips_locale ) { return $lang_code; }
	
	$plugin = patips_get_translation_plugin();

	if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
		$lang_code = apply_filters( 'wpml_current_language', '' );
		if( $lang_code && $with_locale ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			if( ! empty( $languages[ $lang_code ][ 'default_locale' ] ) ) { $lang_code = $languages[ $lang_code ][ 'default_locale' ]; }
		}
	}

	return $lang_code;
}
add_filter( 'patips_current_lang_code', 'patips_current_lang_code_with_plugin', 10, 2 );


/**
 * Get site default locale with WPML
 * @since 0.25.3
 * @param string $locale
 * @param boolean $with_locale
 * @return string
 */
function patips_site_default_locale_with_plugin( $locale, $with_locale ) {
	$plugin = patips_get_translation_plugin();
	
	if( in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) {
		$locale = apply_filters( 'wpml_default_language', '' );
		if( $with_locale && $locale ) {
			$languages = apply_filters( 'wpml_active_languages', array() );
			if( $languages && ! empty( $languages[ $locale ][ 'default_locale' ] ) ) { $locale = $languages[ $locale ][ 'default_locale' ]; }
		}
	}
	
	return $locale;
}
add_filter( 'patips_site_default_locale', 'patips_site_default_locale_with_plugin', 10, 2 );




// SETIINGS

/**
 * Add WPML settings section
 * @since 0.25.3
 */
function patips_add_i18n_settings_section() {
	$plugin = patips_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	add_settings_section( 
		'patips_settings_section_i18n',
		esc_html__( 'Translation', 'patrons-tips' ),
		'patips_settings_section_i18n_callback',
		'patips_settings_general',
		array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'patips-settings-section-i18n'
		)
	);
	
	add_settings_field( 
		'i18n_register_translatable_texts',
		esc_html__( 'Search translatable texts', 'patrons-tips' ),
		'patips_settings_i18n_register_translatable_texts_callback',
		'patips_settings_general',
		'patips_settings_section_i18n'
	);
}
add_action( 'patips_add_settings', 'patips_add_i18n_settings_section', 20 );


/**
 * Display "I18N" settings section
 * @since 0.25.3
 */
function patips_settings_section_i18n_callback() {}


/**
 * Display "Register translatable texts" setting
 * @since 0.25.3
 * @version 0.25.5
 */
function patips_settings_i18n_register_translatable_texts_callback() {
	$plugin = patips_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	if( $plugin === 'wpml' ) {
		if( ! patips_is_plugin_active( 'wpml-string-translation/plugin.php' ) ) {
		?>
			<div>
			<?php 
				/* translators: %s = external link to "WPML String Translation" */ 
				echo sprintf( esc_html__( 'You need the %s add-on to translate user generated content.', 'patrons-tips' ), '<strong><a href="https://wpml.org/documentation/getting-started-guide/string-translation/" target="_blank">WPML String Translation</a></strong>' );
			?>
			</div>
		<?php
			return;
		}
		
		$url = defined( 'WPML_ST_FOLDER' ) ? admin_url( 'admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&context=Patrons%20Tips' ) : '';
		/* translators: This is the path to translate strings from the backend with WPML. */
		$string_translation_path = esc_html__( 'WPML > String Translation', 'patrons-tips' );
	}
	
	else if( $plugin === 'polylang' ) {
		$url = admin_url( 'admin.php?page=mlang_strings&group=Patrons%20Tips' );
		/* translators: This is the path to translate strings from the backend with Polylang. */
		$string_translation_path = esc_html__( 'Languages > Translations', 'patrons-tips' );
	}
	
	if( ! $url ) { return; }
	
	// Display search button
	?>
		<a href='<?php echo esc_url( $url . '&register_all=1' ); ?>' class='button secondary-button'>
			<?php esc_html_e( 'Find and translate', 'patrons-tips' ); ?>
		</a>
	<?php
	/* translators: %s = a navigation path the user must follow (e.g. "Languages > Translations") */
	patips_tooltip( sprintf( esc_html__( 'Search all the translatable settings values that you have manually input. Then, you will be able to translate them in %s. This process can last several minutes and can be ressource intensive, depending on your amount of data.', 'patrons-tips' ), $string_translation_path ) );
}




// WPML

/**
 * Register all translatable texts - on plugin translation specific pages only
 * @since 0.25.3
 */
function patips_controller_register_all_translatable_texts() {
	$plugin = patips_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	// Make sure register_all parameter is set to "1"
	if( empty( $_GET[ 'register_all' ] ) ) { return; }
	
	// Make sure we are on the desired page
	if( $plugin === 'wpml' ) {
		// Make sure we are on WPML String Translation page, "Patrons Tips" domain
		if( empty( $_GET[ 'page' ] ) || empty( $_GET[ 'context' ] ) || ! defined( 'WPML_ST_FOLDER' ) ) { return; }
		if( $_GET[ 'page' ]    !== WPML_ST_FOLDER . '/menu/string-translation.php' ) { return; }
		if( $_GET[ 'context' ] !== 'Patrons Tips' ) { return; }
	}
	else if( $plugin === 'polylang' ) {
		// Make sure we are on Polylang String Translation page, "Patrons Tips" group
		if( empty( $_GET[ 'page' ] ) || empty( $_GET[ 'group' ] ) ) { return; }
		if( $_GET[ 'page' ]  !== 'mlang_strings' ) { return; }
		if( $_GET[ 'group' ] !== 'Patrons Tips' )  { return; }
	}
	
	$texts = patips_get_translatable_texts();

	if( $texts ) {
		$default_lang_code = patips_get_site_default_locale( false );
		foreach( $texts as $text ) {
			$string_value  = ! empty( $text[ 'value' ] ) ? $text[ 'value' ] : '';
			$string_domain = ! empty( $text[ 'domain' ] ) ? $text[ 'domain' ] : 'Patrons Tips';
			$string_name   = ! empty( $text[ 'string_name' ] ) ? $text[ 'string_name' ] : $string_value;
			if( $string_value && is_string( $string_value ) ) {
				do_action( 'wpml_register_single_string', $string_domain, $string_name, $string_value, false, $default_lang_code );
			}
		}
	}
}
add_action( 'admin_init', 'patips_controller_register_all_translatable_texts' );


/**
 * Print admin CSS for inputs translatable with WPML
 * @since 1.0.2 (was patips_print_i18n_admin_css)
 * @global SitePress $sitepress
 * @global PLL_Base $polylang
 */
function patips_enqueue_i18n_admin_css() {
	$plugin = patips_get_translation_plugin();
	if( ! in_array( $plugin, array( 'wpml', 'polylang' ), true ) ) { return; }
	
	$lang_code = patips_get_site_default_locale( false );
	$flag_url  = '';
	
	// Get default site language flag
	if( $plugin === 'wpml' ) {
		global $sitepress;
		if( empty( $sitepress ) || ! defined( 'ICL_PLUGIN_URL' ) || ! defined( 'WPML_PLUGIN_PATH' ) ) { return; }
		
		$flag = $sitepress->get_flag( $lang_code );
		if( $flag ) {
			if( $flag->from_template ) {
				$wp_upload_dir = wp_upload_dir();
				$flag_url      = file_exists( $wp_upload_dir[ 'basedir' ] . '/flags/' . $flag->flag ) ? $wp_upload_dir[ 'baseurl' ] . '/flags/' . $flag->flag : '';
			} else {
				$flag_url = file_exists( WPML_PLUGIN_PATH . '/res/flags/' . $flag->flag ) ? ICL_PLUGIN_URL . '/res/flags/' . $flag->flag : '';
			}
		}
		if( ! $flag_url && file_exists( WPML_PLUGIN_PATH . '/res/flags/en.png' ) ) { $flag_url = ICL_PLUGIN_URL . '/res/flags/en.png'; }
	}
	
	else if( $plugin === 'polylang' ) {
		global $polylang;
		if( empty( $polylang ) ) { return; }
		
		$pll_language = $polylang->model->get_language( $lang_code );
		$flag_url     = $pll_language ? $pll_language->get_display_flag_url() : '';
	}
	
	wp_add_inline_style(
		'patips-css-backend',
		'.patips-translatable {'
			. ( $flag_url ? 'background: url("' . esc_url( $flag_url ) . '") no-repeat top ' . ( is_rtl() ? 'left' : 'right' ) .', white;' : '' )
			. 'border-' . ( is_rtl() ? 'right' : 'left' ) . ': 3px solid #418fb6 !important;'
		. '}'
	);
}
add_action( 'admin_enqueue_scripts', 'patips_enqueue_i18n_admin_css', 110 );