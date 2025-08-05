<?php
/**
 * Site editor template for patronage sales page
 * @since 0.20.0
 * @version 0.26.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<!-- wp:template-part {"slug":"header","area":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"0"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:0">

	<!-- wp:columns {"align":"wide"} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column {"width":"66.66%"} -->
		<div class="wp-block-column" style="flex-basis:66.66%">

			<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

				<!-- wp:post-title {"level":1} /-->

				<!-- wp:post-content {"align":"full","layout":{"type":"constrained"}} /-->

			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"33.33%","style":{"spacing":{"padding":{"top":"70px","bottom":"70px"}}},"layout":{"type":"default"}} -->
		<div class="wp-block-column" style="padding-top:70px;padding-bottom:70px;flex-basis:33.33%">

			<!-- wp:group {"className":"patips-sales-page-sidebar","layout":{"type":"default"}} -->
			<div id="sidebar" class="wp-block-group patips-sales-page-sidebar">
				<?php do_action( 'patips_sales_page_sidebar_before' ); ?>

				<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
				<section id="period-results" class="wp-block-group">
					<!-- wp:heading -->
					<h2 class="wp-block-heading"><?php esc_html_e( 'This month', 'patrons-tips' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:patrons-tips/period-results /-->
				</section>
				<!-- /wp:group -->

				<?php do_action( 'patips_sales_page_sidebar_results_after' ); ?>

				<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
				<section id="patronage-form" class="wp-block-group">
					<!-- wp:heading -->
					<h2 class="wp-block-heading"><?php esc_html_e( 'Select your tier', 'patrons-tips' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:patrons-tips/tier-form /-->
				</section>
				<!-- /wp:group -->

				<?php do_action( 'patips_sales_page_sidebar_form_after' ); ?>

				<!-- wp:group {"tagName":"section","layout":{"type":"default"}} -->
				<section id="period-media" class="wp-block-group">
					<!-- wp:heading -->
					<h2 class="wp-block-heading"><?php esc_html_e( 'Image of the month', 'patrons-tips' ); ?></h2>
					<!-- /wp:heading -->

					<!-- wp:patrons-tips/period-media /-->
				</section>
				<!-- /wp:group -->

				<?php do_action( 'patips_sales_page_sidebar_after' ); ?>
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer","tagName":"footer"} /-->