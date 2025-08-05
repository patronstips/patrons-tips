<?php
/**
 * Settings page
 * @since 0.5.0
 * @version 0.26.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class='wrap'>
	<h1 class='wp-heading-inline'><?php esc_html_e( 'Settings', 'patrons-tips' ); ?></h1>
	
	<?php do_action( 'patips_settings_page_header' ); ?>
	
	<hr class='wp-header-end'/>
	
	<?php
	// Check if there are tiers
	$tiers = patips_get_tiers_data();
	if( ! $tiers ) {
		patips_display_first_tier_notice();
		?><hr/><?php
	}
	
	// Display errors
	settings_errors();
	
	$active_tab = isset( $_GET[ 'tab' ] ) ? sanitize_title_with_dashes( wp_unslash( $_GET[ 'tab' ] ) ) : 'general';
	
	// Define the ordered tabs here: 'tab slug' => 'tab title'
	$tabs = apply_filters( 'patips_settings_tabs', array (
		'general'  => esc_html__( 'General', 'patrons-tips' ),
		'licenses' => esc_html__( 'Licenses', 'patrons-tips' )
	) );
	
	// Display the tabs
	?>
	<h2 class='nav-tab-wrapper patips-nav-tab-wrapper'>
		<?php
		foreach ( $tabs as $tab_id => $tab_title ) {
			$active_tab_class = $tab_id === $active_tab ? 'nav-tab-active' : '';
			?>
			<a href='<?php echo esc_url( "?page=patips_settings&tab=" . $tab_id ); ?>' class='nav-tab <?php echo esc_attr( $active_tab_class ); ?>'><?php echo esc_html( $tab_title ); ?></a>
			<?php 
		}
		?>
	</h2>
	
	<form method='post' action='options.php' id='patips-settings' class='patips-settings-tab-<?php echo esc_attr( $active_tab ); ?>' >
	<?php
		// Display the tabs content
		settings_fields( 'patips_settings_' . $active_tab );
		do_settings_sections( 'patips_settings_' . $active_tab );
		
		do_action( 'patips_settings_tab_' . $active_tab );
		
		// Display the submit button
		$display_submit = apply_filters( 'patips_settings_display_submit_button', true, $active_tab );
		if( $display_submit ) {
			submit_button();
		}
	?>
	</form>	
</div>
<?php

