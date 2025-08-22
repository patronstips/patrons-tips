<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Init settings
 * @since 0.9.0
 * @version 1.0.4
 */
function patips_init_settings() { 
	/* General settings Section - 1 - Misc */
	add_settings_section( 
		'patips_settings_section_general',
		esc_html__( 'General settings', 'patrons-tips' ),
		'patips_settings_section_general',
		'patips_settings_general',
		array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'patips-settings-section-general'
		)
	);
	
	add_settings_field(
		'patronage_launch_date', 
		esc_html__( 'Patronage launch date', 'patrons-tips' ), 
		'patips_settings_field_patronage_launch_date', 
		'patips_settings_general', 
		'patips_settings_section_general' 
	);
	
	add_settings_field( 
		'sales_page_id',
		esc_html__( 'Patronage sales page', 'patrons-tips' ),
		'patips_settings_field_sales_page_id',
		'patips_settings_general',
		'patips_settings_section_general'
	);
	
	add_settings_field( 
		'patron_area_page_id',
		esc_html__( 'Patron area page', 'patrons-tips' ),
		'patips_settings_field_patron_area_page_id',
		'patips_settings_general',
		'patips_settings_section_general'
	);
	
	add_settings_field( 
		'display_patron_list_term_ids',
		esc_html__( 'Display patron list in posts with category', 'patrons-tips' ),
		'patips_settings_field_display_patron_list_term_ids',
		'patips_settings_general',
		'patips_settings_section_general'
	);
	
	add_settings_field( 
		'restricted_post_image_url',
		esc_html__( 'Restricted post image', 'patrons-tips' ),
		'patips_settings_field_restricted_post_image_url',
		'patips_settings_general',
		'patips_settings_section_general'
	);
	
	add_settings_field( 
		'gray_out_locked_posts',
		esc_html__( 'Gray out locked posts', 'patrons-tips' ),
		'patips_settings_field_gray_out_locked_posts',
		'patips_settings_general',
		'patips_settings_section_general'
	);
	
	add_settings_field(
		'delete_data_on_uninstall', 
		esc_html__( 'Delete data on uninstall', 'patrons-tips' ), 
		'patips_settings_field_delete_data_on_uninstall', 
		'patips_settings_general', 
		'patips_settings_section_general' 
	);
	
	
	/* General settings Section - 2 - Price */
	add_settings_section( 
		'patips_settings_section_general_price',
		esc_html__( 'Price format', 'patrons-tips' ),
		'patips_settings_section_general_price_callback',
		'patips_settings_general',
		array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'patips-settings-section-price'
		)
	);
	
	add_settings_field(
		'price_currency_symbol', 
		esc_html__( 'Currency', 'patrons-tips' ), 
		'patips_settings_field_price_currency_symbol_callback', 
		'patips_settings_general', 
		'patips_settings_section_general_price'
	);
	
	
	add_settings_field(
		'price_currency_position', 
		esc_html__( 'Currency position', 'patrons-tips' ), 
		'patips_settings_field_price_currency_position_callback', 
		'patips_settings_general', 
		'patips_settings_section_general_price'
	);
	
	add_settings_field(
		'price_thousand_separator', 
		esc_html__( 'Thousand separator', 'patrons-tips' ), 
		'patips_settings_field_price_thousand_separator_callback', 
		'patips_settings_general', 
		'patips_settings_section_general_price'
	);
	
	add_settings_field(
		'price_decimal_separator', 
		esc_html__( 'Decimal separator', 'patrons-tips' ), 
		'patips_settings_field_price_decimal_separator_callback', 
		'patips_settings_general', 
		'patips_settings_section_general_price'
	);
	
	add_settings_field(  
		'price_decimals_number', 
		esc_html__( 'Number of decimals', 'patrons-tips' ), 
		'patips_settings_field_price_decimals_number_callback', 
		'patips_settings_general', 
		'patips_settings_section_general_price'
	);
	
	
	/* Licenses settings Section */
	add_settings_section( 
		'patips_settings_section_licenses',
		esc_html__( 'Licenses settings', 'patrons-tips' ),
		'patips_settings_section_licenses',
		'patips_settings_licenses',
		array(
			'before_section' => '<div class="%s">',
			'after_section'  => '</div>',
			'section_class'  => 'patips-settings-section-licenses'
		)
	);
	
	
	// Regiter settings
	register_setting( 'patips_settings_general', 'patips_settings_general', array( 
			'type'              => 'array',
			'sanitize_callback' => 'patips_sanitize_settings_general',
			'default'           => array()
		)
	);
	
	register_setting( 'patips_settings_licenses', 'patips_settings_licenses', array( 
			'type'              => 'array',
			'sanitize_callback' => 'patips_sanitize_settings_licenses',
			'default'           => array()
		)
	);
		
	
	/* Allow plugins to add settings and sections */
	do_action( 'patips_add_settings' );
}
add_action( 'admin_init', 'patips_init_settings' );


/**
 * Settings - General section
 * @since 0.9.0
 */
function patips_settings_section_general() {}


/**
 * Settings - Licenses section
 * @since 0.9.0
 * @version 0.25.5
 */
function patips_settings_section_licenses() {
	$active_add_ons = patips_get_active_add_ons();
	if( ! $active_add_ons ) { 
		?>
		<div class='patips-licenses-settings-description'>
			<p><?php echo sprintf(
					/* translators: %s = Add-on name ("Patrons Tips - Pro") */
					esc_html__( 'Here you will be able to activate your %s license key.', 'patrons-tips' ),
					'<strong>Patrons Tips - Pro</strong>'
				); ?></p>
			<strong>
				<?php 
				echo sprintf( 
					/* translators: %s is a link to "Patrons Tips - Pro" (link label) sales page */
					esc_html__( 'Take a look at %s features.', 'patrons-tips' ), 
					' ' . patips_get_pro_sales_link() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
				?>
			</strong>
		</div>
		<?php 
	}
	
	if( $active_add_ons && ! patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) {
		$active_add_ons_titles = array();
		foreach( $active_add_ons as $prefix => $add_on_data ) {
			$active_add_ons_titles[] = esc_html( $add_on_data[ 'title' ] );
		}
		?>
		<div class='patips-licenses-settings-description'>
			<p>
				<em><?php esc_html_e( 'The following add-ons are installed on your site:', 'patrons-tips' ); ?></em>
				<strong>
				<?php 
					echo implode( '</strong>, <strong>', $active_add_ons_titles ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				</strong>
			</p>
			<h3>
			<?php 
				/* translators: %s is a link to download "Patrons Tips - Pro" (link label) add-on */
				echo sprintf( esc_html__( 'Please install "%s" in order to activate your license key.', 'patrons-tips' ), patips_get_pro_sales_link() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</h3>
		</div>
		<?php
	}
	
	do_action( 'patips_settings_section_licenses_after' );
}


/**
 * Setting field - Patronage launch date
 * @since 0.9.0
 */
function patips_settings_field_patronage_launch_date() {
	$args = array(
		'type'  => 'date',
		'name'  => 'patips_settings_general[patronage_launch_date]',
		'id'    => 'patronage_launch_date',
		'value' => patips_get_option( 'patips_settings_general', 'patronage_launch_date' ),
		'tip'   => esc_html__( 'The launch date of your patronage system. Restricted content and patron history will be taken into account from this date.', 'patrons-tips' )
	);
	patips_display_field( $args );
}


/**
 * Setting field - Sales page ID
 * @since 0.10.0
 * @version 1.0.4
 */
function patips_settings_field_sales_page_id() {
	$options = array( 0 => esc_html__( 'None', 'patrons-tips' ) );
	
	$default_locale  = patips_get_site_default_locale();
	$locale_switched = patips_switch_query_locale( $default_locale );
	
	$pages = get_pages( array( 'post_status' => array( 'publish', 'private', 'draft', 'pending', 'future' ), 'sort_column' => 'menu_order', 'sort_order' => 'ASC' ) );
	foreach( $pages as $page ) {
		$options[ $page->ID ] = $page->post_title;
	}
	
	if( $locale_switched ) {
		patips_restore_query_locale();
	}
	
	$page_id = patips_get_option( 'patips_settings_general', 'sales_page_id' );
	
	// Make sure the page is retrieved
	if( $page_id && ! isset( $options[ $page_id ] ) ) {
		$page = get_post( $page_id );
		if( $page ) {
			$options[ $page->ID ] = $page->post_title;
		}
	}
	
	$args = array(
		'type'    => 'select',
		'name'    => 'patips_settings_general[sales_page_id]',
		'id'      => 'sales_page_id',
		'class'   => 'patips-select2-no-ajax',
		'options' => $options,
		'value'   => $page_id,
		'tip'     => esc_html__( 'Select the page on which you sell your patronage offer.', 'patrons-tips' ),
		'after'   => $page_id && intval( $page_id ) > 0 && isset( $options[ $page_id ] ) ? 
			'<a class="button" href="' . esc_url( admin_url( 'post.php?&action=edit&post=' . $page_id ) ) . '">' . esc_html__( 'Edit page', 'patrons-tips' ) . '</a>' 
		  : '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=page&patips_action=create-sales-page&nonce=' . wp_create_nonce( 'patips_create_sales_page' ) ) ) . '">' . esc_html__( 'Create page', 'patrons-tips' ) . '</a>'
	);
	patips_display_fields( array( 'sales_page_id' => $args ) );
}


/**
 * Setting field - Patron area page ID
 * @since 0.20.0 (was patips_settings_field_wc_patron_area_page_id)
 * @version 1.0.4
 */
function patips_settings_field_patron_area_page_id() {
	$options = array(
		-1 => esc_html__( 'Disabled', 'patrons-tips' ),
		0  => esc_html__( 'Default content', 'patrons-tips' ),
	);
	
	$default_locale  = patips_get_site_default_locale();
	$locale_switched = patips_switch_query_locale( $default_locale );
	
	$pages = get_pages( array( 'post_status' => array( 'publish', 'private', 'draft', 'pending', 'future' ), 'sort_column' => 'menu_order', 'sort_order' => 'ASC' ) );
	foreach( $pages as $page ) {
		$options[ $page->ID ] = $page->post_title;
	}
	
	if( $locale_switched ) {
		patips_restore_query_locale();
	}
	
	$page_id    = patips_get_option( 'patips_settings_general', 'patron_area_page_id' );
	$page       = $page_id > 0 ? get_post( $page_id ) : null;
	$is_trashed = $page ? $page->post_status === 'trash' : false;
	
	// Make sure the selected page is in the options
	if( $page && ! isset( $options[ $page_id ] ) ) {
		$options[ $page->ID ] = $page->post_title;
	}
	
	$args = array(
		'type'    => 'select',
		'name'    => 'patips_settings_general[patron_area_page_id]',
		'id'      => 'patron_area_page_id',
		'class'   => 'patips-select2-no-ajax',
		'options' => $options,
		'value'   => $page && ! $is_trashed ? $page_id : 0,
		'tip'     => esc_html__( 'Select the page to display in the "Patronage" tab of the "My account" area. You can also display the default content, or completely disable this tab.', 'patrons-tips' ),
		'after'   => $page_id && intval( $page_id ) > 0 && isset( $options[ $page_id ] ) ? 
			'<a class="button" href="' . esc_url( admin_url( 'post.php?&action=edit&post=' . $page_id ) ) . '">' . esc_html__( 'Edit page', 'patrons-tips' ) . '</a>' 
		  : '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=page&patips_action=create-patron-area-page&nonce=' . wp_create_nonce( 'patips_create_patron_area_page' ) ) ) . '">' . esc_html__( 'Create page', 'patrons-tips' ) . '</a>'
	);
	patips_display_fields( array( 'sales_page_id' => $args ) );
}


/**
 * Setting field - Display patron list in posts with category
 * @since 0.11.0
 * @version 0.26.0
 */
function patips_settings_field_display_patron_list_term_ids() {
	// Get taxonomies that can be restricted by post type
	$taxonomies_by_type = patips_get_restrictable_taxonomies_by_post_type();
	
	// Default term options
	$term_options = array(
		'all'        => esc_html__( 'All', 'patrons-tips' ),
		'restricted' => esc_html__( 'All restricted categories', 'patrons-tips' )
	);
	
	// Get terms by post type
	foreach( $taxonomies_by_type as $post_type => $taxonomies ) {
		$taxonomies = array_filter( $taxonomies, 'taxonomy_exists' );
		$terms      = $taxonomies ? get_terms( array( 'taxonomy' => $taxonomies, 'hide_empty' => false ) ) : array();
		foreach( $terms as $term ) {
			$term_options[ intval( $term->term_id ) ] = $term->name;
		}
	}
	
	// Sanitize value
	$term_ids = patips_get_option( 'patips_settings_general', 'display_patron_list_term_ids' );
	if( ! is_array( $term_ids ) ) { $term_ids = $term_ids ? array( $term_ids ) : array(); }
	
	$args = array(
		'type'     => 'select',
		'multiple' => 1,
		'name'     => 'patips_settings_general[display_patron_list_term_ids]',
		'id'       => 'display_patron_list_term_ids',
		'class'    => 'patips-select2-no-ajax',
		'options'  => $term_options,
		'value'    => $term_ids,
		'tip'      => esc_html__( 'Select the desired categories / tags. The patron list will be display at the end of the posts of these categories, only if it contains at least one patron.', 'patrons-tips' )
	);
	patips_display_field( $args );
	
	// Notice displayed after the field
	?>
		<div class='patips-info'>
			<span><?php esc_html_e( 'The patron list will be displayed only if it contains at least one patron.', 'patrons-tips' ); ?></span>
		</div>
	<?php
}


/**
 * Setting field - Restricted post image
 * @since 1.0.1
 */
function patips_settings_field_restricted_post_image_url() {
	// Sanitize value
	$img_url = patips_get_option( 'patips_settings_general', 'restricted_post_image_url' );
	
	$args = array(
		'type'    => 'media',
		'name'    => 'patips_settings_general[restricted_post_image_url]',
		'options' => array( 'hide_input' => 1, 'desired_data' => 'url' ),
		'value'   => $img_url ? esc_url( $img_url ) : '',
		'title'   => esc_html__( 'Restricted post image', 'patrons-tips' ),
		'tip'     => esc_html__( 'Image displayed in the restricted posts message.', 'patrons-tips' )
	);
	
	patips_display_field( $args );
}


/**
 * Setting field - Gray out locked posts
 * @since 1.0.4
 */
function patips_settings_field_gray_out_locked_posts() {
	// Sanitize value
	$gray_out = patips_get_option( 'patips_settings_general', 'gray_out_locked_posts' );
	
	$args = array(
		'type'    => 'checkbox',
		'name'    => 'patips_settings_general[gray_out_locked_posts]',
		'value'   => $gray_out ? 1 : 0,
		'title'   => esc_html__( 'Gray Out locked posts', 'patrons-tips' ),
		'tip'     => esc_html__( 'Locked posts\' image and title will be grayed out in post lists.', 'patrons-tips' )
	);
	
	patips_display_field( $args );
}


/**
 * Setting field - Delete data on uninstall
 * @since 0.9.0
 * @version 0.23.0
 */
function patips_settings_field_delete_data_on_uninstall() {
	$args = array(
		'type'  => 'checkbox',
		'name'  => 'patips_settings_general[delete_data_on_uninstall]',
		'id'    => 'delete_data_on_uninstall',
		'value' => patips_get_option( 'patips_settings_general', 'delete_data_on_uninstall' ),
		'tip'   => esc_html__( 'Delete all Patrons Tips data (patrons, history, tiers, settings...) when you uninstall the plugin.', 'patrons-tips' )
	);
	patips_display_field( $args );
}




// PRICE SETTINGS

/**
 * Display price settings section
 * @since 0.21.0
 */
function patips_settings_section_general_price_callback() {
	do_action( 'patips_settings_price_section' );
}


/**
 * Display currency symbol field
 * @since 0.21.0
 */
function patips_settings_field_price_currency_symbol_callback() {
	$args = array(
		'type'  => 'text',
		'name'  => 'patips_settings_general[price_currency_symbol]',
		'id'    => 'price_currency_symbol',
		'value' => patips_get_option( 'patips_settings_general', 'price_currency_symbol' ),
		'tip'   => esc_html__( 'The currency symbol used for displaying prices (e.g.: $ € £ ¥ etc.).', 'patrons-tips' )
	);
	patips_display_field( $args );
}


/**
 * Display currency position field
 * @since 0.21.0
 */
function patips_settings_field_price_currency_position_callback() {
	$args = array(
		'type'    => 'select',
		'name'    => 'patips_settings_general[price_currency_position]',
		'id'      => 'price_currency_position',
		'options' => array(
			'left'        => esc_html__( 'Left', 'patrons-tips' ),
			'right'       => esc_html__( 'Right', 'patrons-tips' ),
			'left_space'  => esc_html__( 'Left with space', 'patrons-tips' ),
			'right_space' => esc_html__( 'Right with space', 'patrons-tips' )
		),
		'value'   => patips_get_option( 'patips_settings_general', 'price_currency_position' ),
		'tip'     => esc_html__( 'Position of the currency symbol in prices (e.g.: $50; 50€).', 'patrons-tips' )
	);
	patips_display_field( $args );
}


/**
 * Display price thousand separator field
 * @since 0.21.0
 */
function patips_settings_field_price_thousand_separator_callback() {
	$args = array(
		'type'  => 'text',
		'name'  => 'patips_settings_general[price_thousand_separator]',
		'id'    => 'price_thousand_separator',
		'value' => patips_get_option( 'patips_settings_general', 'price_thousand_separator' ),
		'tip'   => esc_html__( 'Thousand separator used for displaying prices (e.g.: $9,999; 9 999€).', 'patrons-tips' )
	);
	patips_display_field( $args );
}


/**
 * Display price decimal separator field
 * @since 0.21.0
 */
function patips_settings_field_price_decimal_separator_callback() {
	$args = array(
		'type'  => 'text',
		'name'  => 'patips_settings_general[price_decimal_separator]',
		'id'    => 'price_decimal_separator',
		'value' => patips_get_option( 'patips_settings_general', 'price_decimal_separator' ),
		'tip'   => esc_html__( 'Decimal separator used for displaying prices (e.g.: $9.99; 9,99€).', 'patrons-tips' )
	);
	patips_display_field( $args );
}


/**
 * Display price number of decimals field
 * @since 0.21.0
 * @version 0.23.0
 */
function patips_settings_field_price_decimals_number_callback() {
	$args = array(
		'type'    => 'number',
		'name'    => 'patips_settings_general[price_decimals_number]',
		'id'      => 'price_decimals_number',
		'value'   => patips_get_option( 'patips_settings_general', 'price_decimals_number' ),
		'options' => array( 'min' => 0, 'max' => 4, 'step' => 1 ),
		'tip'     => esc_html__( 'Number of decimals in prices (e.g.: 9,999KD; $9.99; ¥9).', 'patrons-tips' )
	);
	patips_display_field( $args );
}




// SANITIZE SETTINGS

/**
 * Sanitize General settings
 * @since 0.25.4
 * @version 1.0.4
 * @param array $settings_raw
 * @return array
 */
function patips_sanitize_settings_general( $settings_raw ) {
	$defaults = patips_get_default_options();
	
	$keys_by_type = array( 
		'int'    => array( 'sales_page_id', 'patron_area_page_id', 'price_decimals_number' ),
		'str'    => array( 'price_currency_symbol', 'price_thousand_separator', 'price_decimal_separator' ),
		'str_id' => array( 'price_currency_position' ),
		'url'    => array( 'restricted_post_image_url' ),
		'date'   => array( 'patronage_launch_date' ),
		'bool'   => array( 'delete_data_on_uninstall', 'gray_out_locked_posts' )
	);
	
	$settings = patips_sanitize_values( $defaults, $settings_raw, $keys_by_type );
	
	// Sanitize term ids
	$term_ids = isset( $settings_raw[ 'display_patron_list_term_ids' ] ) ? $settings_raw[ 'display_patron_list_term_ids' ] : array();
	if( ! is_array( $term_ids ) ) { $term_ids = $term_ids ? array( $term_ids ) : array(); }
	
	$has_all        = in_array( 'all', $term_ids, true );
	$has_restricted = in_array( 'restricted', $term_ids, true );
	
	if( $has_all ) { 
		$settings[ 'display_patron_list_term_ids' ] = array( 'all' );
	} else {
		$settings[ 'display_patron_list_term_ids' ] = patips_ids_to_array( $term_ids );
		if( $has_restricted ) {
			$settings[ 'display_patron_list_term_ids' ] = array( 'restricted' ) + $settings[ 'display_patron_list_term_ids' ];
		}
	}
	
	return apply_filters( 'patips_sanitized_settings_general', $settings, $settings_raw );
}


/**
 * Sanitize License settings
 * @since 0.25.4
 * @param array $settings_raw
 * @return array
 */
function patips_sanitize_settings_licenses( $settings_raw ) {
	return apply_filters( 'patips_sanitized_settings_licenses', array(), $settings_raw );
}




// CUSTOM LINKS

/** 
 * Add actions in plugin list table
 * @since 0.23.0
 * @version 0.25.6
 * @param array $links
 * @return array
 */
function patips_action_links_in_plugin_list_table( $links ) {
	$new_links = array( 
		'settings' => '<a'
			. ' href="' . admin_url( 'admin.php?page=patips_settings' ) . '"'
			. ' title="' . esc_attr( esc_html__( 'Manage Patrons Tips settings', 'patrons-tips' ) ) . '">' 
				. esc_html__( 'Settings', 'patrons-tips' ) 
		. '</a>'
	);
	
	return $new_links + $links;
}
add_filter( 'plugin_action_links_patrons-tips/patrons-tips.php', 'patips_action_links_in_plugin_list_table', 10, 1 );


/** 
 * Add meta links in plugin list table
 * @since 0.23.0
 * @version 1.0.5
 * @param array $links
 * @param string $file
 * @return array
 */
function patips_meta_links_in_plugin_list_table( $links, $file ) {
	if( $file == PATIPS_PLUGIN_NAME . '/' . PATIPS_PLUGIN_NAME . '.php' ) {
		$links[ 'docs' ]    = '<a href="' . esc_url( 'https://patronstips.com/en/user-documentation/?utm_source=plugin&utm_medium=plugin&utm_content=plugin-list' ) . '" title="' . esc_attr( __( 'View documentation', 'patrons-tips' ) ) . '" target="_blank" >' . esc_html__( 'Docs', 'patrons-tips' ) . '</a>';
		$links[ 'contact' ] = '<a href="' . esc_attr( 'mailto:contact@patronstips.com' ) . '" title="' . esc_attr( __( 'Contact us directly', 'patrons-tips' ) ) . '" target="_blank" >' . esc_html__( 'Contact us', 'patrons-tips' ) . '</a>';
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'patips_meta_links_in_plugin_list_table', 10, 2 );




// PRIVACY

/**
 * Register the personal data exporters for privacy
 * @since 0.25.3
 * @param array $exporters
 * @return array
 */
function patips_register_privacy_exporters( $exporters ) {
	$exporters[ 'patips-patrons' ] = array(
		'exporter_friendly_name' => 'Patrons Tips user patrons data',
		'callback'               => 'patips_privacy_exporter_patrons_data',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'patips_register_privacy_exporters', 10 );


/**
 * Register the personal data erasers for privacy
 * @since 0.25.3
 * @param array $erasers
 * @return array
 */
function patips_register_privacy_erasers( $erasers ) {
	$erasers[ 'patips-patrons' ] = array(
		'eraser_friendly_name' => 'Patrons Tips user patrons data',
		'callback'             => 'patips_privacy_eraser_patrons_data',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'patips_register_privacy_erasers', 10 );