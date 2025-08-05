<?php
/**
 * Patron area page
 * @version 0.20.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

$is_block = use_block_editor_for_post_type( 'page' );
?>

<!-- wp:group {"className":"patips-patron-area","layout":{"type":"default"}} -->
<div class="wp-block-group patips-patron-area">
	<?php do_action( 'patips_patron_area_before' ); ?>

	<!-- wp:group {"className":"patips-tabs","layout":{"type":"default"}} -->
	<div id="tabs" class="wp-block-group patips-tabs">
		<?php do_action( 'patips_patron_area_tabs_before' ); ?>
		
		<!-- wp:group {"className":"patips-tab-link patips-tab-active","layout":{"type":"default"}} -->
		<div id="tab-home" class="wp-block-group patips-tab-link patips-tab-active">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Home', 'patrons-tips' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"patips-tab-link","layout":{"type":"default"}} -->
		<div id="tab-rewards" class="wp-block-group patips-tab-link">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Rewards', 'patrons-tips' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"patips-tab-link","layout":{"type":"default"}} -->
		<div id="tab-history" class="wp-block-group patips-tab-link">
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'History', 'patrons-tips' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		
		<?php do_action( 'patips_patron_area_tabs_after' ); ?>
	</div>
	<!-- /wp:group -->


	<!-- wp:group {"className":"patips-tab-content patips-tab-active","layout":{"type":"default"}} -->
	<div id="home" class="wp-block-group patips-tab-content patips-tab-active">
		<?php do_action( 'patips_patron_area_tab_home_before' ); ?>
		
		<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
		<section id="patron-status" class="wp-block-group">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Welcome to the patron area!', 'patrons-tips' ); ?></h2>
			<!-- /wp:heading -->

			<?php if( $is_block ) { ?>
			<!-- wp:patrons-tips/patron-status /-->
			<?php } else { ?>
			<!-- wp:shortcode -->
			[patronstips_patron_status]
			<!-- /wp:shortcode -->
			<?php } ?>
		</section>
		<!-- /wp:group -->
		
		<?php do_action( 'patips_patron_area_tab_home_after' ); ?>
	</div>
	<!-- /wp:group -->


	<!-- wp:group {"className":"patips-tab-content","layout":{"type":"default"}} -->
	<div id="rewards" class="wp-block-group patips-tab-content">
		<?php do_action( 'patips_patron_area_tab_rewards_before' ); ?>

		<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
		<section id="unlocked-content" class="wp-block-group">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Unlocked content', 'patrons-tips' ); ?></h2>
			<!-- /wp:heading -->

			<?php if( $is_block ) { ?>
			<!-- wp:patrons-tips/patron-post-list {"types":["post","page"],"restricted":1,"unlocked":1,"perPage":6} /-->
			<?php } else { ?>
			<!-- wp:shortcode -->
			[patronstips_patron_posts types="post,page" restricted="1" unlocked="1" per_page="6"]
			<!-- /wp:shortcode -->
			<?php } ?>
		</section>
		<!-- /wp:group -->

		<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
		<section id="unlocked-media" class="wp-block-group">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Unlocked media', 'patrons-tips' ); ?></h2>
			<!-- /wp:heading -->

			<?php if( $is_block ) { ?>
			<!-- wp:patrons-tips/patron-post-list {"types":["attachment"],"restricted":1,"unlocked":1,"perPage":6} /-->
			<?php } else { ?>
			<!-- wp:shortcode -->
			[patronstips_patron_posts types="attachment" restricted="1" unlocked="1" per_page="6"]
			<!-- /wp:shortcode -->
			<?php } ?>
		</section>
		<!-- /wp:group -->

		<?php do_action( 'patips_patron_area_tab_rewards_after' ); ?>
	</div>
	<!-- /wp:group -->


	<!-- wp:group {"className":"patips-tab-content","layout":{"type":"default"}} -->
	<div id="history" class="wp-block-group patips-tab-content">
		<?php do_action( 'patips_patron_area_tab_history_before' ); ?>

		<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
		<section id="patron-history" class="wp-block-group">
			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Patronage History', 'patrons-tips' ); ?></h2>
			<!-- /wp:heading -->

			<?php if( $is_block ) { ?>
			<!-- wp:patrons-tips/patron-history {"perPage":6} /-->
			<?php } else { ?>
			<!-- wp:shortcode -->
			[patronstips_patron_history per_page="6"]
			<!-- /wp:shortcode -->
			<?php } ?>
		</section>
		<!-- /wp:group -->

		<?php do_action( 'patips_patron_area_tab_history_after' ); ?>
	</div>
	<!-- /wp:group -->

	<?php do_action( 'patips_patron_area_after' ); ?>
</div>
<!-- /wp:group -->