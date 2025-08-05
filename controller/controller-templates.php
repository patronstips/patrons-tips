<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// WIDGETS

/**
 * Register widgets and sidebars
 * @since 0.17.0
 */
function patips_register_widgets_and_sidebars() {
	// Register Widgets
	register_widget( 'PATIPS_widget_patron_number' );
	register_widget( 'PATIPS_widget_period_income' );
	register_widget( 'PATIPS_widget_period_media' );
	register_widget( 'PATIPS_widget_tier_form' );
	
	// Register Sidebars
    register_sidebar(
        array(
            'name'          => esc_html__( 'Patronage Sales Sidebar', 'patrons-tips' ),
            'id'            => 'patips-sales-sidebar',
            'description'   => esc_html__( 'Sidebar to be displayed on the patronage sales page.', 'patrons-tips' ),
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget'  => '</aside>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>'
        )
    );
	
	// Add default widgets to the sidebar
	patips_add_default_widgets_to_sales_sidebar();
}
add_action( 'widgets_init', 'patips_register_widgets_and_sidebars' );




// TEMPLATES

/**
 * Register templates for site editor
 * @since 0.20.0
 */
function patips_register_site_editor_templates() {
	if( ! function_exists( 'register_block_template' ) ) { return; }
	
	// Register Sales Page Template
	register_block_template( 'patrons-tips//patips-sales-page', apply_filters( 'patips_sales_page_template_data', array( 
		'title'       => esc_html__( 'Page: Patronage sales', 'patrons-tips' ),
		'description' => esc_html__( 'Introduce the patronage system and sell patronage tiers.', 'patrons-tips' ),
		'plugin'      => 'patrons-tips',
		'post_types'  => array( 'page', 'post' ),
		'content'     => patips_get_page_template( 'sales-page-template' )
	) ) );
}
add_action( 'init', 'patips_register_site_editor_templates' );


/**
 * Register page templates for classic editor
 * @since 0.20.0
 */
function patips_register_page_templates_for_classic_editor( $page_templates, $wp_theme, $post ) {
	if( ! isset( $page_templates[ 'patips-sales-page' ] ) ) {
		$page_templates[ 'patips-sales-page' ] = esc_html__( 'Page: Patronage sales', 'patrons-tips' );
	}
	return $page_templates;
}
add_filter( 'theme_page_templates', 'patips_register_page_templates_for_classic_editor', 10, 3 );


/**
 * Promote Patrons Tips Pro - Add blocks to sales page sidebar
 * @since 0.25.0
 * @version 1.0.2
 * @return array
 */
function patips_add_pro_ad_to_sales_page_sidebar() {
	if( patips_is_plugin_active( 'patrons-tips-pro/patrons-tips-pro.php' ) ) { return; }
?>
	<!-- wp:group {"tagName":"section","className":"patips-pro-ad-period-goals","layout":{"type":"default"}} -->
	<section id="period-goals" class="wp-block-group patips-pro-ad-period-goals">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">
			<?php esc_html_e( 'Month goals', 'patrons-tips' ); ?>
			<span class="patips-pro-ad-icon"></span>
		</h2>
		<!-- /wp:heading -->
		
		<!-- wp:group {"className":"patips-pro-ad","layout":{"type":"default"}} -->
		<div class="wp-block-group patips-pro-ad">
			<!-- wp:paragraph -->
			<p><?php /* translators: %s = link to 'Patrons Tips - Pro' sales page */ echo sprintf( esc_html__( '%s lets you to set monthly goals to encourage patrons to contribute.', 'patrons-tips' ), patips_get_pro_sales_link() ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"align":"center"} -->
			<p class="has-text-align-center"><img src="<?php echo esc_url( PATIPS_PLUGIN_URL . 'img/demo-goals.png' ); ?>" title="<?php esc_html_e( 'Patronage period goals', 'patrons-tips' ); ?>" class="patips-demo-period-goals-img"></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph -->
			<p><em><?php esc_html_e( 'Goals can be a number of patrons or an amount of money.', 'patrons-tips' ); ?></em></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</section>
	<!-- /wp:group -->
<?php
}
add_action( 'patips_sales_page_sidebar_after', 'patips_add_pro_ad_to_sales_page_sidebar' );




// PAGES

/**
 * Controller to create the patron area page by URL
 * @since 0.20.0
 * @version 1.0.3
 * @param array $post_states
 * @param WP_Post $post
 * @return array
 */
function patips_controller_create_patron_area_page() {
	// Check action
	if( ! ( is_admin() && empty( $_REQUEST[ 'trashed' ] ) && isset( $_REQUEST[ 'patips_action' ] ) && $_REQUEST[ 'patips_action' ] === 'create-patron-area-page' ) ) { return; }
	
	// Check nonce
	check_admin_referer( 'patips_create_patron_area_page', 'nonce' );
	
	// Check permission
	if( ! current_user_can( 'publish_pages' ) ) { return; }
	
	// Create the patron area page
	patips_create_patron_area_page();
}
add_filter( 'wp_loaded', 'patips_controller_create_patron_area_page', 5 );


/**
 * Controller to create the sales page by URL
 * @since 0.20.0
 * @version 1.0.3
 * @param array $post_states
 * @param WP_Post $post
 * @return array
 */
function patips_controller_create_sales_page() {
	// Check action
	if( ! ( is_admin() && empty( $_REQUEST[ 'trashed' ] ) && isset( $_REQUEST[ 'patips_action' ] ) && $_REQUEST[ 'patips_action' ] === 'create-sales-page' ) ) { return; }
	
	// Check nonce
	check_admin_referer( 'patips_create_sales_page', 'nonce' );
	
	// Check permission
	if( ! current_user_can( 'publish_pages' ) ) { return; }
	
	// Create the sales page
	patips_create_sales_page();
}
add_filter( 'wp_loaded', 'patips_controller_create_sales_page', 5 );