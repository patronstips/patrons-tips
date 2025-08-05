<?php  
/**
 * Plugin Name: Patrons Tips
 * Plugin URI: https://patronstips.com/en/?utm_source=plugin&utm_medium=plugin&utm_content=header
 * Description: Patronage system. A free Patreon-like tool to monetize your creations and reward your backers with restricted content. Works great with WooCommerce.
 * Version: 1.0.4
 * Author: Patrons Tips Team
 * Author URI: https://profiles.wordpress.org/patronstips/#content-plugins
 * Text Domain: patrons-tips
 * Domain Path: /languages/
 * Requires at least: 6.7
 * Requires PHP: 7.0
 * WC requires at least: 8.8
 * WC tested up to: 10.0
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
 * This file is part of Patrons Tips.
 * 
 * Patrons Tips is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * 
 * Patrons Tips is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Patrons Tips. If not, see <https://www.gnu.org/licenses/>.
 * 
 * @package Patrons Tips
 * @category Core
 * @author Yoan Cutillas
 * 
 * Copyright 2025 Karolane Bohaer
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// GLOBALS AND CONSTANTS
if( ! defined( 'PATIPS_PLUGIN_VERSION' ) ) { define( 'PATIPS_PLUGIN_VERSION', '1.0.4' ); }
if( ! defined( 'PATIPS_PLUGIN_NAME' ) )    { define( 'PATIPS_PLUGIN_NAME', 'patrons-tips' ); }
if( ! defined( 'PATIPS_PLUGIN_DIR' ) )     { define( 'PATIPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); }
if( ! defined( 'PATIPS_PLUGIN_URL' ) )     { define( 'PATIPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); }

// Get active plugins
$active_plugins = (array) get_option( 'active_plugins', array() );
if( is_multisite() ) {
	$network_active_plugins = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
	$active_plugins         = array_merge( $active_plugins, $network_active_plugins );
}


// Include files
include_once( 'model/model-global.php' );
include_once( 'model/model-install.php' );
include_once( 'model/model-patrons.php' );
include_once( 'model/model-patron-editor.php' );
include_once( 'model/model-tiers.php' );
include_once( 'model/model-exports.php' );
include_once( 'model/model-posts.php' );

include_once( 'functions/functions-global.php' );
include_once( 'functions/functions-forms.php' );
include_once( 'functions/functions-patrons.php' );
include_once( 'functions/functions-patron-editor.php' );
include_once( 'functions/functions-tiers.php' );
include_once( 'functions/functions-posts.php' );
include_once( 'functions/functions-patron-export.php' );
include_once( 'functions/functions-templates.php' );
include_once( 'functions/functions-settings.php' );
include_once( 'functions/functions-i18n.php' );

include_once( 'controller/controller-frontend.php' );
include_once( 'controller/controller-posts.php' );
include_once( 'controller/controller-shortcodes.php' );
include_once( 'controller/controller-patron-export.php' );
include_once( 'controller/controller-templates.php' );
include_once( 'controller/controller-blocks.php' );
include_once( 'controller/controller-i18n.php' );

include_once( 'class/widgets/class-widget-patron-number.php' );
include_once( 'class/widgets/class-widget-period-income.php' );
include_once( 'class/widgets/class-widget-period-media.php' );
include_once( 'class/widgets/class-widget-tier-form.php' );

if( in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
	include_once( 'model/model-woocommerce.php' );
	
	include_once( 'functions/functions-woocommerce.php' );
	include_once( 'functions/functions-patron-history-sync.php' );
	include_once( 'functions/functions-subscriptions.php' );
	include_once( 'functions/functions-subscriptions-wcs.php' );
	include_once( 'functions/functions-subscriptions-fsb.php' );
	include_once( 'functions/functions-subscriptions-sfw.php' );
	
	include_once( 'controller/controller-woocommerce-frontend.php' );
	include_once( 'controller/controller-patron-history-sync.php' );
	include_once( 'controller/controller-subscriptions-wcs.php' );
	include_once( 'controller/controller-subscriptions-fsb.php' );
	include_once( 'controller/controller-subscriptions-sfw.php' );
	
	if( is_admin() ) {
		include_once( 'controller/controller-woocommerce-backend.php' );
	}
}

if( is_admin() ) {
	include_once( 'model/model-tier-editor.php' );
	include_once( 'functions/functions-tier-editor.php' );
	include_once( 'controller/controller-tier-editor.php' );
	include_once( 'controller/controller-patron-editor.php' );
	include_once( 'controller/controller-backend.php' );
	include_once( 'controller/controller-settings.php' );
	include_once( 'class/class-tiers-list-table.php' );
	include_once( 'class/class-patrons-list-table.php' );
}


/**
 * Enqueue the javascript variables early once and for all scripts
 * @since 0.1.0
 * @version 1.0.2
 */
function patips_enqueue_js_variables() {
	wp_enqueue_script( 'patips-js-global-variables', plugins_url( 'js/global-variables.min.js', __FILE__ ), array( 'jquery' ), PATIPS_PLUGIN_VERSION, false );
	
	wp_add_inline_script(
		'patips-js-global-variables',
		sprintf(
			'var patips_var = %s;',
			wp_json_encode( patips_get_js_variables() ) /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */
		),
		'after'
	);
}
add_action( 'admin_enqueue_scripts', 'patips_enqueue_js_variables', 5 );
add_action( 'wp_enqueue_scripts', 'patips_enqueue_js_variables', 5 );


/**
 * Enqueue global CSS and scripts
 * @since 0.2.2
 * @version 1.0.2
 */
function patips_enqueue_global_scripts() {
	// CSS
	wp_enqueue_style( 'patips-css-global', plugins_url( 'css/global.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	
	// Javascript
	wp_enqueue_script( 'patips-js-global-functions', plugins_url( 'js/global-functions.min.js', __FILE__ ), array( 'jquery', 'patips-js-global-variables' ), PATIPS_PLUGIN_VERSION, true );

	if( patips_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		wp_enqueue_style( 'patips-css-woocommerce', plugins_url( 'css/woocommerce.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'patips_enqueue_global_scripts' );
add_action( 'wp_enqueue_scripts', 'patips_enqueue_global_scripts' );


/**
 * Enqueue frontend CSS and scripts
 * @since 0.1.0
 * @version 1.0.2
 */
function patips_enqueue_frontend_scripts() {
	// CSS
	wp_enqueue_style( 'patips-css-frontend', plugins_url( 'css/frontend.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	
	// Javascript
	wp_enqueue_script( 'patips-js-frontend', plugins_url( 'js/frontend.min.js', __FILE__ ), array( 'jquery', 'patips-js-global-variables', 'patips-js-global-functions' ), PATIPS_PLUGIN_VERSION, true );
	
	if( patips_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		wp_enqueue_script( 'patips-js-woocommerce-frontend', plugins_url( 'js/woocommerce-frontend.min.js', __FILE__ ), array( 'jquery', 'patips-js-global-variables' ), PATIPS_PLUGIN_VERSION, true );
	}
}
add_action( 'wp_enqueue_scripts','patips_enqueue_frontend_scripts', 100 );


/**
 * Enqueue backend CSS and scripts
 * @since 0.1.0
 * @version 1.0.3
 */
function patips_enqueue_backend_scripts() {
	// CSS
	wp_enqueue_style( 'patips-css-landing', plugins_url( 'css/landing.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	wp_enqueue_style( 'patips-css-backend', plugins_url( 'css/backend.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	
	// Javascript
	wp_enqueue_script( 'patips-js-backend', plugins_url( 'js/backend.min.js', __FILE__ ), array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-sortable', 'jquery-tiptip', 'patips-js-global-variables' ), PATIPS_PLUGIN_VERSION, true );
	wp_enqueue_script( 'patips-js-patron-editor', plugins_url( 'js/patron-editor.min.js', __FILE__ ), array( 'jquery', 'patips-js-global-variables', 'patips-js-backend' ), PATIPS_PLUGIN_VERSION, true );
	
	if( patips_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		wp_enqueue_script( 'patips-js-woocommerce-backend', plugins_url( 'js/woocommerce-backend.min.js', __FILE__ ), array( 'jquery', 'patips-js-global-variables' ), PATIPS_PLUGIN_VERSION, true );
	}
	
	// MEDIA (to display the media dialog)
	$current_screen = get_current_screen();
    if( $current_screen && in_array( $current_screen->id, array( 'attachment', 'patrons-tips_page_patips_tiers', 'patrons-tips_page_patips_settings' ), true ) ) { 
		wp_enqueue_media();
	}
	
	// JQUERY UI theme
	wp_register_style( 'patips-css-jquery-ui', plugins_url( 'lib/jquery-ui/theme/jquery-ui.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	wp_enqueue_style( 'patips-css-jquery-ui' );
	
	
	// SELECT 2
	$select2_version = '4.0.13';
	if( ! wp_script_is( 'select2', 'registered' ) ) { wp_register_script( 'select2', plugins_url( 'lib/select2/select2.full.min.js', __FILE__ ), array( 'jquery' ), $select2_version, true ); }
	if( ! wp_script_is( 'select2', 'enqueued' ) )   { wp_enqueue_script( 'select2' ); }
	if( ! wp_style_is( 'select2', 'registered' ) )  { wp_register_style( 'select2', plugins_url( 'lib/select2/select2.min.css', __FILE__ ), array(), $select2_version ); }
	if( ! wp_style_is( 'select2', 'enqueued' ) )    { wp_enqueue_style( 'select2' ); }
	
	$lang_code = patips_get_current_lang_code();
	if( $lang_code ) {
		$path   = WP_LANG_DIR . '/select2/' . $lang_code . '.js';
		$url    = content_url( 'languages/select2/' . $lang_code . '.js' );
		$exists = is_readable( $path );
		
		if( ! $exists ) {
			$path   = PATIPS_PLUGIN_DIR . 'lib/select2/i18n/' . $lang_code . '.js';
			$url    = plugins_url( 'lib/select2/i18n/' . $lang_code . '.js', __FILE__ );
			$exists = is_readable( $path );
		}
		
		if( $exists ) {
			if( ! wp_script_is( 'select2-' . $lang_code, 'registered' ) ) { wp_register_script( 'select2-' . $lang_code, $url, array( 'select2' ), $select2_version, true ); }
			if( ! wp_script_is( 'select2-' . $lang_code, 'enqueued' ) )   { wp_enqueue_script( 'select2-' . $lang_code ); }
		}
	}
	
	
	// TIPTIP
	$tiptip_version            = '1.3';
	$registered_tiptip         = wp_scripts()->query( 'jquery-tiptip', 'registered' );
	$registered_tiptip_version = $registered_tiptip && ! empty( $registered_tiptip->ver ) ? $registered_tiptip->ver : '';
	if( ! $registered_tiptip || ( $registered_tiptip_version && version_compare( $registered_tiptip_version, $tiptip_version, '<' ) ) ) { 
		wp_register_script( 'jquery-tiptip', plugins_url( 'lib/jquery-tiptip/jquery.tipTip.min.js', __FILE__ ), array( 'jquery' ), $tiptip_version, true );
	}
	if( ! wp_script_is( 'jquery-tiptip', 'enqueued' ) ) { wp_enqueue_script( 'jquery-tiptip' ); }
	
	wp_enqueue_style( 'jquery-tiptip', plugins_url( 'lib/jquery-tiptip/tipTip.min.css', __FILE__ ), array(), $tiptip_version );
	wp_style_add_data( 'jquery-tiptip', 'rtl', 'replace' );
	wp_style_add_data( 'jquery-tiptip', 'suffix', '.min' );
}
add_action( 'admin_enqueue_scripts', 'patips_enqueue_backend_scripts', 100 );


/**
 * Enqueue CSS and scripts for blocks
 * @since 0.10.0
 * @version 1.0.4
 */
function patips_enqueue_blocks_assets() {
	// CSS
	wp_enqueue_style( 'patips-css-global', plugins_url( 'css/global.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	wp_enqueue_style( 'patips-css-frontend', plugins_url( 'css/frontend.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	
	if( patips_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		wp_enqueue_style( 'patips-css-woocommerce', plugins_url( 'css/woocommerce.min.css', __FILE__ ), array(), PATIPS_PLUGIN_VERSION );
	}
}
add_action( 'enqueue_block_assets', 'patips_enqueue_blocks_assets', 110 );


/**
 * Activate
 * @since 0.1.0
 * @version 1.0.2
 */
function patips_activate() {
	if( ! is_blog_installed() ) { return; }
	
	// Make sure not to run this function twice
	if( get_transient( 'patips_installing' ) === 'yes' ) { return; }
	set_transient( 'patips_installing', 'yes', MINUTE_IN_SECONDS * 10 );
	
	// Set roles and capabilities
	patips_set_role_and_cap();
	
	// Create tables in database
    patips_create_tables();
	
	// Keep in memory the first installed date
	$install_date = get_option( 'patips_install_date' );
	if( ! $install_date ) { 
		update_option( 'patips_install_date', gmdate( 'Y-m-d H:i:s' ) );
		
		// Load translations
		patips_load_textdomain();
		
		// Create default pages on first install
		patips_create_patron_area_page();
		patips_create_sales_page();
	}
	
	// Update current version
	delete_option( 'patips_plugin_version' );
	add_option( 'patips_plugin_version', PATIPS_PLUGIN_VERSION );
	
	do_action( 'patips_activate' );
	
	flush_rewrite_rules();
	
	delete_transient( 'patips_installing' );
}
register_activation_hook( __FILE__, 'patips_activate' );


/**
 * Deactivate
 * @since 0.1.0
 */
function patips_deactivate() {
	do_action( 'patips_deactivate' );
}
register_deactivation_hook( __FILE__, 'patips_deactivate' );


/**
 * Uninstall
 * @since 0.1.0
 * @version 0.9.0
 */
function patips_uninstall() {
	if( patips_get_option( 'patips_settings_general', 'delete_data_on_uninstall' ) ) {
		// Remove options from database
		patips_delete_options();

		// Delete tables and their data
		patips_drop_tables();
	}
	
	// Unset roles and capabilities
	patips_unset_role_and_cap();
	
	do_action( 'patips_uninstall' );
	
	// Clear any cached data that has been removed
	wp_cache_flush();
}
register_uninstall_hook( __FILE__, 'patips_uninstall' );


/**
 * Update
 * @since 0.1.0
 * @version 1.0.2
 */
function patips_check_version() {
	if( defined( 'IFRAME_REQUEST' ) ) { return; }
	$old_version = get_option( 'patips_plugin_version' );
	if( $old_version !== PATIPS_PLUGIN_VERSION ) {
		patips_activate();
		do_action( 'patips_updated', $old_version );
	}
	
	// Update database version
	$db_version = get_option( 'patips_db_version', $old_version );
	if( version_compare( $db_version, PATIPS_PLUGIN_VERSION, '<' ) ) {
		delete_option( 'patips_db_version' );
		add_option( 'patips_db_version', PATIPS_PLUGIN_VERSION );
		do_action( 'patips_db_updated', $db_version );
	}
}
add_action( 'init', 'patips_check_version', 5 );


// ADMIN MENU

/**
 * Create the Admin Menu
 * @since 0.5.0
 * @version 0.7.0
 */
function patips_create_menu() {
    // Add a menu and submenus
	add_menu_page( 'Patrons Tips', 'Patrons Tips', 'patips_manage_patrons_tips', 'patrons-tips', null, 'dashicons-coffee', '58.6' );
	add_submenu_page( 'patrons-tips', 'Patrons Tips', esc_html__( 'Home', 'patrons-tips' ), 'patips_manage_patrons_tips', 'patrons-tips', 'patips_landing_page' );
	add_submenu_page( 'patrons-tips', esc_html__( 'Tiers', 'patrons-tips' ), esc_html__( 'Tiers', 'patrons-tips' ), 'patips_manage_tiers', 'patips_tiers', 'patips_tiers_page' );
	add_submenu_page( 'patrons-tips', esc_html__( 'Patrons', 'patrons-tips' ), esc_html__( 'Patrons', 'patrons-tips' ), 'patips_manage_patrons', 'patips_patrons', 'patips_patrons_page' );
	
	do_action( 'patips_admin_menu' );

	add_submenu_page( 'patrons-tips', esc_html__( 'Settings', 'patrons-tips' ), esc_html__( 'Settings', 'patrons-tips' ), 'patips_manage_settings', 'patips_settings', 'patips_settings_page' );
}
add_action( 'admin_menu', 'patips_create_menu' );


/**
 * Include content of Patrons Tips landing page
 * @since 0.5.0
 */
function patips_landing_page() {
    include_once( 'view/view-landing.php' );
}


/**
 * Include content of Tiers top-level menu page
 * @since 0.5.0
 * @version 0.11.0
 */
function patips_tiers_page() {
	$can_create_tier  = current_user_can( 'patips_create_tiers' );
	$can_edit_tier    = current_user_can( 'patips_edit_tiers' );
	$load_tier_editor = false;
	
	if( ! empty( $_GET[ 'action' ] ) ) {
		if( ( $_GET[ 'action' ] === 'new' && $can_create_tier )
		 || ( $_GET[ 'action' ] === 'edit' && ! empty( $_GET[ 'tier_id' ] ) && is_numeric( $_GET[ 'tier_id' ] ) && $can_edit_tier ) ) {
			$load_tier_editor = true;
		}
	}
	
	if( $load_tier_editor ) {
		include_once( 'view/view-tier-editor.php' );
	} else {
		include_once( 'view/view-tier-list-table.php' );
	}
}


/**
 * Include content of Patrons top-level menu page
 * @since 0.5.0
 * @version 0.11.0
 */
function patips_patrons_page() {
    $can_create_patron  = current_user_can( 'patips_create_patrons' );
	$can_edit_patron    = current_user_can( 'patips_edit_patrons' );
	$load_patron_editor = false;
	
	if( ! empty( $_GET[ 'action' ] ) ) {
		if( ( $_GET[ 'action' ] === 'new' && $can_create_patron )
		 || ( $_GET[ 'action' ] === 'edit' && ! empty( $_GET[ 'patron_id' ] ) && is_numeric( $_GET[ 'patron_id' ] ) && $can_edit_patron ) ) {
			$load_patron_editor = true;
		}
	}
	
	if( $load_patron_editor ) {
		include_once( 'view/view-patron-editor.php' );
	} else {
		include_once( 'view/view-patron-list-table.php' );
	}
}


/**
 * Include content of Settings top-level menu page
 * @since 0.5.0
 */
function patips_settings_page() {
    include_once( 'view/view-settings.php' );
}